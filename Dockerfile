FROM php:8-cli-buster

WORKDIR /app

ARG APP_ENV=prod
ARG DATABASE_URL=postgresql://database_user:database_password@0.0.0.0:5432/database_name?serverVersion=12&charset=utf8
ARG COMPILER_HOST=compiler
ARG COMPILER_PORT=8000
ARG COMPILER_SOURCE_DIRECTORY=/app/source
ARG COMPILER_TARGET_DIRECTORY=/app/tests
ARG DELEGATOR_HOST=delegator
ARG DELEGATOR_PORT=8000
ARG MESSENGER_TRANSPORT_DSN=amqp://rabbitmq_user:rabbitmq_password@rabbitmq_host:5672/%2f/messages
ARG CALLBACK_RETRY_LIMIT=3
ARG JOB_TIMEOUT_CHECK_PERIOD_MS=30000

ENV APP_ENV=$APP_ENV
ENV DATABASE_URL=$DATABASE_URL
ENV COMPILER_HOST=$COMPILER_HOST
ENV COMPILER_PORT=$COMPILER_PORT
ENV COMPILER_SOURCE_DIRECTORY=$COMPILER_SOURCE_DIRECTORY
ENV COMPILER_TARGET_DIRECTORY=$COMPILER_TARGET_DIRECTORY
ENV DELEGATOR_HOST=$DELEGATOR_HOST
ENV DELEGATOR_PORT=$DELEGATOR_PORT
ENV MESSENGER_TRANSPORT_DSN=$MESSENGER_TRANSPORT_DSN
ENV CALLBACK_RETRY_LIMIT=$CALLBACK_RETRY_LIMIT
ENV JOB_TIMEOUT_CHECK_PERIOD_MS=$JOB_TIMEOUT_CHECK_PERIOD_MS

ENV DOCKERIZE_VERSION v1.2.0

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/install-php-extensions
COPY composer.json composer.lock /app/
COPY bin/console /app/bin/console
COPY public/index.php public/
COPY src /app/src
COPY config/bundles.php config/services.yaml /app/config/
COPY config/packages/*.yaml /app/config/packages/
COPY config/packages/prod /app/config/packages/prod
COPY config/routes/annotations.yaml /app/config/routes/
COPY migrations /app/migrations

RUN apt-get -qq update && apt-get -qq -y install  \
  librabbitmq-dev \
  libpq-dev \
  libzip-dev \
  supervisor \
  zip \
  && docker-php-ext-install \
  pdo_pgsql \
  zip \
  && install-php-extensions amqp \
  && rm /usr/bin/install-php-extensions \
  && docker-php-ext-enable amqp \
  && apt-get autoremove -y \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
  && composer check-platform-reqs --ansi \
  && composer install --no-dev --no-scripts \
  && rm composer.lock \
  && chmod +x /app/bin/console \
  && touch /app/.env \
  && curl -L --output dockerize.tar.gz \
     https://github.com/presslabs/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
  && tar -C /usr/local/bin -xzvf dockerize.tar.gz \
  && rm dockerize.tar.gz \
  && mkdir -p var/log/supervisor \
  && php bin/console cache:clear --env=prod

COPY build/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY build/supervisor/conf.d/app.conf /etc/supervisor/conf.d/supervisord.conf

CMD dockerize -wait tcp://rabbitmq:5672 -timeout 30s -wait tcp://postgres:5432 -timeout 30s supervisord -c /etc/supervisor/supervisord.conf
