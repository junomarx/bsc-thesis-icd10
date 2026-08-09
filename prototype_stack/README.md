# ICD prototype reproducible stack

**Status:** candidate infrastructure scaffold. It was prepared outside the
actual application repository and is supplied for inspection/adoption. No
successful Docker/Compose, Git synchronization, database bootstrap, or stack
verification result is inherited with this directory.

This directory is intended to wrap the prototype in a small Docker Compose environment. The
host-side application source is deliberately synchronized before Docker builds
it; Git credentials are never part of a Dockerfile, image layer, build
argument, repository URL, or saved `origin` URL.

## Services

```text
host checkout (.runtime/app)
          |
          v
       app build ---------------------+
                                      |
db: MySQL (latest) <--- bootstrap      |  app: PHP/React image
  persistent volume     one-shot       +----> http://localhost:5860
```

`db` and `app` are the long-running services. `bootstrap` is a one-shot Python
container that accepts an empty database or the exact known runtime table set,
applies the schema only when needed, and runs the immutable/idempotent baseline
loader. A partial or unexpected schema fails instead of being repaired. Normal
`down` never removes the named MySQL volume.

The application repository owns its root `Dockerfile`. This stack therefore
does not assume a frontend/backend directory layout before the PHP/React
implementation is created. The intended app image is multi-stage: Node can be
used to compile React, while the final runtime contains PHP/Apache and the
compiled static frontend rather than a permanent Node service.

## First configuration

Run:

```bash
./stack.sh init
```

This creates ignored local copies of `.env.example` and
`config/git-source.conf.example`; it never overwrites an existing file and
does not create a token. Edit `.env` to replace the database-password
placeholders. Edit `config/git-source.conf` to identify the application source:

```text
APP_GIT_URL=https://github.com/OWNER/REPOSITORY.git
APP_GIT_REF=main
APP_GIT_REF_TYPE=branch
APP_GIT_USERNAME=YOUR_GIT_USERNAME
APP_GIT_TOKEN_FILE=.secrets/git-token
APP_SOURCE_DIR=.runtime/app
```

The config parser accepts unquoted `KEY=value` records only and rejects unknown
keys. `APP_SOURCE_DIR` must remain inside this stack directory.

For an authenticated HTTPS repository, put only the raw token in the ignored
`.secrets/git-token` file (prefer filesystem mode `0600`) or set
`APP_GIT_TOKEN` in the environment. The environment value takes precedence.
The username remains non-secret configuration. Do **not** put a token into
`APP_GIT_URL`; the script rejects HTTP(S) URLs containing userinfo.

The token is supplied to Git through `GIT_ASKPASS` with terminal prompting
disabled. In explicit-token mode configured Git credential helpers are also
disabled, so the wrapper does not ask a host helper to persist that token. The
token therefore does not occur in Git command arguments, the stored remote URL,
the Docker build context, or an image. SSH repository URLs may use the host's
normal SSH authentication and should not be combined with token configuration.

## Source synchronization

During development:

```bash
./stack.sh sync
```

If no checkout exists, the command clones into `.runtime/app`. For a configured
branch it then fetches the remote and permits only a fast-forward update. It
requires the checkout to be clean and verifies that `origin` is exactly the
configured repository; it never resets or deletes local source.

For a reproducible frozen run, change the source configuration to:

```text
APP_GIT_REF=<full 40-hex commit SHA>
APP_GIT_REF_TYPE=revision
```

`sync` checks out that revision detached. `verify --frozen` additionally
refuses a branch, abbreviated SHA, or checkout that does not equal the pinned
commit. Thus "latest branch" is a development convenience, not the scientific
identity of the evaluated software.

## Stack commands

```bash
./stack.sh doctor
./stack.sh sync
./stack.sh up
./stack.sh up --sync
./stack.sh verify
./stack.sh verify --frozen
./stack.sh status
./stack.sh down
```

`doctor` requires Docker with Compose v2, Git for source/revision management, a
non-placeholder `.env`, and an application checkout containing a root
`Dockerfile`. No host PHP, MySQL, Python/pip, Node, or npm installation is
required. The wrapper intentionally does not attempt to install Docker itself
because that operation is host/OS-specific and may require administrative
privileges.

`up --sync` performs source synchronization first, validates the Compose
configuration, pulls MySQL, builds the bootstrap and application images,
starts MySQL and waits for health, executes the one-shot bootstrap, and starts
the application. `up` omits the source mutation step and uses the existing
checkout.

`verify` currently covers the implemented data-contract and live MySQL
persistence checks. It does not claim to be the eventual complete evaluator
verification suite; rule/API/end-to-end verification will be added with those
implementation layers. The bootstrap image deliberately does not contain the
`RCBASE-*` verification oracle.

There is no automatic reset command. If destructive database reset behaviour
is needed later, it should remain an explicit, separately guarded operation;
ordinary `down` preserves `mysql_data`.
