FROM docker.io/library/php:7.4-fpm

RUN sed -i s/http:/https:/g /etc/apt/sources.list

RUN export DEBIAN_FRONTEND=noninteractive && apt-get update && apt-get -y install procps lighttpd krb5-user libldap-2.4-2 && rm -rf /var/cache/apt
RUN apt-get -y install libldap2-dev && docker-php-ext-install pdo_mysql pdo mysqli ldap && apt-get -y remove libldap2-dev
RUN pear install DB
RUN echo "TLS_REQCERT never" > /var/www/.ldaprc && echo "[libdefaults]\n	default_realm = LOCAL.LESBG.COM" > /etc/krb5.conf


COPY entrypoint.sh /
RUN chmod 0755 /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]

COPY lighttpd.conf mimetypes.conf /etc/lighttpd/
COPY src/ /var/www/html/

RUN chmod o+rX /var/www/html -R

CMD ["/usr/local/sbin/php-fpm"]
