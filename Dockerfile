FROM php:8.3-cli-bookworm

WORKDIR /app

COPY public /app/public

EXPOSE 8081

# PHP's built-in server handles one request at a time by default, so a
# single slow page (e.g. one waiting on the bot API) blocks every other
# asset on that page. This opts into the built-in worker pool for concurrency.
ENV PHP_CLI_SERVER_WORKERS=4

CMD ["php", "-S", "0.0.0.0:8081", "-t", "/app/public", "/app/public/router.php"]
