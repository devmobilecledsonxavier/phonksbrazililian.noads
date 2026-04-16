FROM php:8.3-apache

RUN a2enmod headers rewrite expires

WORKDIR /var/www/html
COPY . /var/www/html

COPY start-render.sh /usr/local/bin/start-render.sh
RUN chmod +x /usr/local/bin/start-render.sh

ENV PORT=10000
EXPOSE 10000

CMD ["start-render.sh"]
