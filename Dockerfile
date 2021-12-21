FROM php:7-alpine

WORKDIR /public

COPY . /public

CMD php -S 0.0.0.0:8000

