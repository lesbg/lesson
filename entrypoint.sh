#!/bin/bash

lighttpd -f /etc/lighttpd/lighttpd.conf &

exec /usr/local/bin/docker-php-entrypoint "$@"
