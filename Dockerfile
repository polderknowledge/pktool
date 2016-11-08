FROM php:7.0-alpine

COPY .docker/entry-point.sh /usr/bin/entry-point

RUN apk add --update git coreutils su-exec \
    && addgroup pktool \
    && adduser -G pktool -D pktool \
    && mkdir -p /data \
    && chown -R pktool:pktool /data \
    && chmod +x /usr/bin/entry-point \
    && rm -rf /var/cache/apk/*

ADD . /usr/local/pktool

ENTRYPOINT ["/usr/bin/entry-point"]



WORKDIR /data
