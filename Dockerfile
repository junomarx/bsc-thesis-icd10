# syntax=docker/dockerfile:1

# Lives at the repository root (rather than app/Dockerfile) so that
# prototype_stack/stack.sh's `sync` can pull this same repository as the
# "application source" and find a Dockerfile at the checkout root, as its
# doctor/up commands require. See docs/DEVELOPMENT_DOCUMENTATION.md §10.2.
#
# Node is used only in this build stage to compile the React frontend; the
# runtime image below runs no permanent Node service (brief, Section 17).
# `--platform=$BUILDPLATFORM` pins this stage (and the two Composer stages
# below) to run natively on the builder regardless of which target
# platform(s) the final image is built for. Their output — static JS/CSS/
# HTML, and a pure-PHP vendor tree with no compiled extensions — is not
# architecture-specific, so building it under QEMU emulation for a second
# target arch buys nothing and costs a lot: Vite's esbuild (a native Go
# binary) is a well-known hang/pathological-slowness case under QEMU
# user-mode emulation, turning a 20-second build into a stuck multi-hour
# one. Only `base`/`dev`/`runtime` below need a real per-arch build, since
# `base` compiles the native `pdo_mysql` extension.
#
# Every base image below is pinned to a manifest-list digest, not a
# floating tag, as of the PROTOBASE-1.0 development freeze
# (docs/DEVELOPMENT_DOCUMENTATION.md §10.9/§10.10; exact resolved versions
# in docs/environment_manifest_0_1.json). Before the freeze these were
# deliberately left floating for development convenience (§10.1); the
# freeze is exactly the one point at which REQ-CFG-01 requires pinning
# the execution environment, so this is that pin, applied once, not
# duplicated back to floating afterward.
FROM --platform=$BUILDPLATFORM node:22-alpine@sha256:c610fcdfb1d5b4740dd70c284ed3cb16bb857e0f7166196e36a5501df7a3aa32 AS frontend-build
WORKDIR /app/frontend
COPY app/frontend/package.json app/frontend/package-lock.json* ./
RUN npm ci
COPY app/frontend/ ./
RUN npm run build

FROM --platform=$BUILDPLATFORM composer:2@sha256:4d71c3c2109c61d5415544264b59ad4087e4c5b7244481723664138fd36d5040 AS vendor
WORKDIR /app
COPY app/composer.json app/composer.lock* ./
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts --no-progress

# Same install, but keeping dev dependencies (phpunit/phpunit,
# php-webdriver/webdriver) — only the `dev` target below uses this.
FROM --platform=$BUILDPLATFORM composer:2@sha256:4d71c3c2109c61d5415544264b59ad4087e4c5b7244481723664138fd36d5040 AS vendor-dev
WORKDIR /app
COPY app/composer.json app/composer.lock* ./
RUN composer install --no-interaction --no-scripts --no-progress

FROM php:8.4-apache@sha256:5f8050825b2f3de4efb0d81149c86643a9ee9c0a74ed4595ca2ad69ebfeb35fb AS base
RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && rm -f /etc/apache2/sites-enabled/000-default.conf

COPY app/docker/apache-vhost.conf /etc/apache2/sites-enabled/000-default.conf

WORKDIR /var/www/html
COPY app/public/index.php app/public/.htaccess ./public/
COPY --from=frontend-build /app/public/. ./public/

EXPOSE 80

# Self-contained "everything" image for publishing (docs/DEVELOPMENT_DOCUMENTATION.md
# §10.6): runtime plus dev dependencies and the full test suite, so
# `docker pull` + the published docker-compose.yml bundle gets you code,
# dependencies, and tests together — not only the running app. Built only
# via an explicit `--target dev`; never used for actual deployment.
FROM base AS dev
COPY --from=vendor-dev /app/vendor ./vendor
COPY app/src ./src
COPY app/tests ./tests
COPY app/composer.json app/composer.lock* app/phpunit.xml ./
# ReferenceResponseTest.php locates the RC-* oracle via a path relative to
# its own file (repo-root-relative, matching the host checkout layout); the
# container has no sibling prototype_baseline/ directory otherwise, so only
# the one oracle CSV the test harness actually reads is placed at the
# equivalent path here (never the Python pipeline itself, which stays out
# of this image).
COPY prototype_baseline/verification/reference_responses_0_3.csv /var/www/prototype_baseline/verification/reference_responses_0_3.csv

# Lean deployment image: no dev dependencies, no test files. Last stage, so
# a bare `docker build` (no --target) safely defaults to this one — the
# image `prototype_stack/compose.yaml`'s `app` service builds/runs.
FROM base AS runtime
COPY --from=vendor /app/vendor ./vendor
COPY app/src ./src
