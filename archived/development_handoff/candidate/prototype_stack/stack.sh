#!/usr/bin/env bash

set -eu

# Git invokes GIT_ASKPASS with the prompt as argv[1]. This mode is entered only
# in the short-lived credential environment created by git_with_auth below.
if [ "${STACK_ASKPASS_MODE:-0}" = "1" ]; then
    case "${1:-}" in
        *sername*|*Username*) printf '%s\n' "${APP_GIT_USERNAME:-}" ;;
        *) printf '%s\n' "${APP_GIT_TOKEN:-}" ;;
    esac
    exit 0
fi

STACK_DIR="$(CDPATH= cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
STACK_SCRIPT="$STACK_DIR/stack.sh"
COMPOSE_FILE="$STACK_DIR/compose.yaml"
ENV_FILE="${STACK_ENV_FILE:-$STACK_DIR/.env}"
GIT_CONFIG_FILE="${STACK_GIT_CONFIG:-$STACK_DIR/config/git-source.conf}"

die() {
    printf 'ERROR: %s\n' "$*" >&2
    exit 2
}

warn() {
    printf 'WARNING: %s\n' "$*" >&2
}

info() {
    printf '%s\n' "$*"
}

usage() {
    cat <<'EOF'
Usage: ./stack.sh COMMAND [OPTION]

Commands:
  init                 Create local config files from examples without overwriting.
  doctor               Check Docker/Compose, configuration, and app checkout.
  sync                 Clone or safely fast-forward/pin the configured app source.
  up [--sync]          Start DB, bootstrap data, build and start the application.
  verify [--frozen]    Run the current data/persistence checks; optionally require a pinned commit.
  status               Show Compose status and the checked-out application commit.
  down                 Stop containers while preserving the MySQL volume.

Secrets are never accepted in git-source.conf. Set APP_GIT_TOKEN in the
environment or place the raw token in the configured ignored token file.
EOF
}

init_config() {
    mkdir -p "$STACK_DIR/config" "$STACK_DIR/.secrets" "$STACK_DIR/.runtime"
    chmod 700 "$STACK_DIR/.secrets" 2>/dev/null || true

    if [ ! -e "$ENV_FILE" ]; then
        cp "$STACK_DIR/.env.example" "$ENV_FILE"
        info "Created $ENV_FILE"
    else
        info "Kept existing $ENV_FILE"
    fi

    if [ ! -e "$GIT_CONFIG_FILE" ]; then
        cp "$STACK_DIR/config/git-source.conf.example" "$GIT_CONFIG_FILE"
        info "Created $GIT_CONFIG_FILE"
    else
        info "Kept existing $GIT_CONFIG_FILE"
    fi

    info "No token file was created. Configure repository/ref, database secrets, and optional Git authentication before use."
}

require_git_config() {
    [ -f "$GIT_CONFIG_FILE" ] || die "Git source config is missing: $GIT_CONFIG_FILE (run ./stack.sh init first)"

    APP_GIT_URL=""
    APP_GIT_REF=""
    APP_GIT_REF_TYPE="branch"
    APP_GIT_USERNAME=""
    APP_GIT_TOKEN_FILE=""
    APP_SOURCE_DIR=".runtime/app"

    while IFS= read -r line || [ -n "$line" ]; do
        line=${line%$'\r'}
        case "$line" in
            ''|'#'*) continue ;;
            *=*) ;;
            *) die "invalid Git source config line; expected KEY=value" ;;
        esac
        key=${line%%=*}
        value=${line#*=}
        case "$key" in
            APP_GIT_URL) APP_GIT_URL=$value ;;
            APP_GIT_REF) APP_GIT_REF=$value ;;
            APP_GIT_REF_TYPE) APP_GIT_REF_TYPE=$value ;;
            APP_GIT_USERNAME) APP_GIT_USERNAME=$value ;;
            APP_GIT_TOKEN_FILE) APP_GIT_TOKEN_FILE=$value ;;
            APP_SOURCE_DIR) APP_SOURCE_DIR=$value ;;
            APP_GIT_TOKEN) die "APP_GIT_TOKEN must not be stored in git-source.conf" ;;
            *) die "unknown Git source config key: $key" ;;
        esac
    done < "$GIT_CONFIG_FILE"

    [ -n "$APP_GIT_URL" ] || die "APP_GIT_URL is empty"
    [ -n "$APP_GIT_REF" ] || die "APP_GIT_REF is empty"
    case "$APP_GIT_REF_TYPE" in
        branch|revision) ;;
        *) die "APP_GIT_REF_TYPE must be branch or revision" ;;
    esac
    case "$APP_GIT_URL" in
        http://*@*|https://*@*) die "credentials/userinfo must not be embedded in APP_GIT_URL" ;;
    esac
    case "$APP_SOURCE_DIR" in
        ''|/*|../*|*/../*|*/..) die "APP_SOURCE_DIR must stay inside prototype_stack and may not contain '..'" ;;
    esac
    SOURCE_PATH="$STACK_DIR/$APP_SOURCE_DIR"
}

read_git_token() {
    GIT_TOKEN_VALUE="${APP_GIT_TOKEN:-}"
    if [ -n "$GIT_TOKEN_VALUE" ]; then
        :
    elif [ -n "$APP_GIT_TOKEN_FILE" ]; then
        case "$APP_GIT_TOKEN_FILE" in
            /*) token_path=$APP_GIT_TOKEN_FILE ;;
            *) token_path="$STACK_DIR/$APP_GIT_TOKEN_FILE" ;;
        esac
        if [ -f "$token_path" ]; then
            IFS= read -r GIT_TOKEN_VALUE < "$token_path" || true
            GIT_TOKEN_VALUE=${GIT_TOKEN_VALUE%$'\r'}
            [ -n "$GIT_TOKEN_VALUE" ] || die "configured Git token file is empty"
        fi
    fi

    if [ -n "$GIT_TOKEN_VALUE" ]; then
        [ -n "$APP_GIT_USERNAME" ] || die "APP_GIT_USERNAME is required when token authentication is used"
        case "$APP_GIT_URL" in
            http://*|https://*) ;;
            *) die "token authentication is supported only for HTTP(S) repository URLs; use host SSH authentication for an SSH URL" ;;
        esac
    fi
}

git_with_auth() {
    if [ -n "${GIT_TOKEN_VALUE:-}" ]; then
        (
            export STACK_ASKPASS_MODE=1
            export APP_GIT_TOKEN="$GIT_TOKEN_VALUE"
            export APP_GIT_USERNAME
            export GIT_ASKPASS="$STACK_SCRIPT"
            export GIT_TERMINAL_PROMPT=0
            # Ignore host credential helpers in explicit-token mode so the
            # supplied token is not persisted by a configured Git helper.
            git -c credential.helper= "$@"
        )
    else
        GIT_TERMINAL_PROMPT=0 git "$@"
    fi
}

assert_clean_checkout() {
    [ -d "$SOURCE_PATH/.git" ] || die "source path exists but is not a Git checkout: $SOURCE_PATH"
    [ -z "$(git -C "$SOURCE_PATH" status --porcelain)" ] || die "application checkout has local changes; refusing to update it"

    origin_url=$(git -C "$SOURCE_PATH" remote get-url origin 2>/dev/null || true)
    [ "$origin_url" = "$APP_GIT_URL" ] || die "application checkout origin does not match APP_GIT_URL; refusing to rewrite it"
}

sync_source() {
    command -v git >/dev/null 2>&1 || die "Git is required for source synchronization"
    require_git_config
    read_git_token

    if [ ! -e "$SOURCE_PATH" ]; then
        mkdir -p "$(dirname -- "$SOURCE_PATH")"
        info "Cloning configured application repository..."
        git_with_auth clone --origin origin "$APP_GIT_URL" "$SOURCE_PATH"
    fi

    assert_clean_checkout
    info "Fetching configured application source..."

    if [ "$APP_GIT_REF_TYPE" = "branch" ]; then
        git_with_auth -C "$SOURCE_PATH" fetch --prune origin
        git -C "$SOURCE_PATH" show-ref --verify --quiet "refs/remotes/origin/$APP_GIT_REF" \
            || die "configured remote branch does not exist: $APP_GIT_REF"

        if git -C "$SOURCE_PATH" show-ref --verify --quiet "refs/heads/$APP_GIT_REF"; then
            git -C "$SOURCE_PATH" checkout "$APP_GIT_REF" >/dev/null
        else
            git -C "$SOURCE_PATH" checkout --track -b "$APP_GIT_REF" "origin/$APP_GIT_REF" >/dev/null
        fi
        git -C "$SOURCE_PATH" merge --ff-only "origin/$APP_GIT_REF"
        remote_head=$(git -C "$SOURCE_PATH" rev-parse "origin/$APP_GIT_REF^{commit}")
        local_head=$(git -C "$SOURCE_PATH" rev-parse HEAD)
        [ "$local_head" = "$remote_head" ] || die "local branch is not identical to the fetched remote branch; refusing to reset it"
    else
        git_with_auth -C "$SOURCE_PATH" fetch --prune --tags origin
        git -C "$SOURCE_PATH" rev-parse --verify --quiet "$APP_GIT_REF^{commit}" >/dev/null \
            || die "configured revision is not available after fetch: $APP_GIT_REF"
        git -C "$SOURCE_PATH" checkout --detach "$APP_GIT_REF" >/dev/null
    fi

    APP_SOURCE_HEAD=$(git -C "$SOURCE_PATH" rev-parse HEAD)
    info "Application source synchronized: $APP_SOURCE_HEAD"
}

check_env_file() {
    [ -f "$ENV_FILE" ] || die "Compose environment file is missing: $ENV_FILE (run ./stack.sh init first)"
    while IFS='=' read -r key value || [ -n "${key:-}${value:-}" ]; do
        case "$value" in
            change-me*) die "$key still contains the example placeholder; set a real local secret in .env" ;;
        esac
    done < "$ENV_FILE"
}

prepare_source_context() {
    require_git_config
    [ -d "$SOURCE_PATH/.git" ] || die "application checkout is missing; run ./stack.sh sync or ./stack.sh up --sync"
    [ -f "$SOURCE_PATH/Dockerfile" ] || die "application checkout has no Dockerfile at its root; the app image contract is not yet satisfied"
    APP_SOURCE_HEAD=$(git -C "$SOURCE_PATH" rev-parse HEAD)
    export APP_SOURCE_DIR="$SOURCE_PATH"
}

compose() {
    docker compose --project-directory "$STACK_DIR" --env-file "$ENV_FILE" -f "$COMPOSE_FILE" "$@"
}

doctor() {
    command -v docker >/dev/null 2>&1 || die "Docker is not installed. Install Docker Engine/Desktop with Compose v2: https://docs.docker.com/get-docker/"
    command -v git >/dev/null 2>&1 || die "Git is required for application source/revision management"
    docker info >/dev/null 2>&1 || die "Docker is installed but its daemon is not reachable"
    docker compose version >/dev/null 2>&1 || die "Docker Compose v2 is unavailable"
    check_env_file
    prepare_source_context
    compose config --quiet

    info "Docker             OK"
    info "Docker Compose     OK"
    info "Stack config       OK"
    info "Application source $APP_SOURCE_HEAD"
}

assert_frozen_source() {
    require_git_config
    [ "$APP_GIT_REF_TYPE" = "revision" ] || die "frozen verification requires APP_GIT_REF_TYPE=revision"
    [[ "$APP_GIT_REF" =~ ^[0-9a-fA-F]{40}$ ]] \
        || die "frozen verification requires APP_GIT_REF to be a full 40-hex commit SHA"
    actual=$(git -C "$SOURCE_PATH" rev-parse HEAD)
    configured=$(git -C "$SOURCE_PATH" rev-parse "$APP_GIT_REF^{commit}")
    [ "$actual" = "$configured" ] || die "checked-out application HEAD does not equal the configured frozen revision"
}

up_stack() {
    do_sync=${1:-0}
    if [ "$do_sync" = "1" ]; then
        sync_source
    fi
    doctor

    info "Pulling pinned database image and building local images..."
    compose pull db
    compose build --pull bootstrap app

    info "Starting MySQL and waiting for its health check..."
    compose up -d --wait db

    info "Applying/verifying the versioned runtime baseline..."
    compose run --rm --no-deps bootstrap

    info "Starting application..."
    compose up -d --wait app

    info "Stack is up. Published application endpoint:"
    compose port app 80
    info "Application commit: $APP_SOURCE_HEAD"
}

verify_stack() {
    frozen=${1:-0}
    doctor
    if [ "$frozen" = "1" ]; then
        assert_frozen_source
        info "Frozen source check  PASS"
    elif [ "$APP_GIT_REF_TYPE" = "branch" ]; then
        warn "verification is using a moving branch ref; use verify --frozen for the principal frozen run"
    fi

    compose up -d --wait db
    compose run --rm --no-deps bootstrap
    compose run --rm --no-deps bootstrap \
        python -m unittest -v tests/test_runtime_contract.py tests/test_mysql_persistence.py
    info "Current data/persistence verification: PASS"
}

status_stack() {
    command -v docker >/dev/null 2>&1 || die "Docker is not installed"
    check_env_file
    compose ps
    if [ -f "$GIT_CONFIG_FILE" ]; then
        require_git_config
        if [ -d "$SOURCE_PATH/.git" ]; then
            info "Application commit: $(git -C "$SOURCE_PATH" rev-parse HEAD)"
        fi
    fi
}

command=${1:-}
case "$command" in
    init)
        [ "$#" -eq 1 ] || die "init takes no options"
        init_config
        ;;
    doctor)
        [ "$#" -eq 1 ] || die "doctor takes no options"
        doctor
        ;;
    sync)
        [ "$#" -eq 1 ] || die "sync takes no options"
        sync_source
        ;;
    up)
        case "${2:-}" in
            '') [ "$#" -eq 1 ] || die "unknown up option"; up_stack 0 ;;
            --sync) [ "$#" -eq 2 ] || die "unknown up option"; up_stack 1 ;;
            *) die "unknown up option: ${2:-}" ;;
        esac
        ;;
    verify)
        case "${2:-}" in
            '') [ "$#" -eq 1 ] || die "unknown verify option"; verify_stack 0 ;;
            --frozen) [ "$#" -eq 2 ] || die "unknown verify option"; verify_stack 1 ;;
            *) die "unknown verify option: ${2:-}" ;;
        esac
        ;;
    status)
        [ "$#" -eq 1 ] || die "status takes no options"
        status_stack
        ;;
    down)
        [ "$#" -eq 1 ] || die "down takes no options"
        command -v docker >/dev/null 2>&1 || die "Docker is not installed"
        check_env_file
        compose down
        info "Stack stopped. The MySQL named volume was preserved."
        ;;
    -h|--help|help)
        usage
        ;;
    '')
        usage
        exit 2
        ;;
    *)
        usage >&2
        die "unknown command: $command"
        ;;
esac
