FROM ubuntu:20.04

RUN useradd -ms /bin/bash -u 1001 imagelint
WORKDIR /var/www/html

### Setup
ENV GOSU_VERSION 1.7
ENV DEBIAN_FRONTEND noninteractive
RUN apt-get update \
    && apt-get install -y --no-install-recommends --no-install-suggests \
        curl \
        software-properties-common \
        zip \
        unzip \
        git \
        supervisor \
        gnupg2 \
        apt-transport-https \
        wget \
        gcc \
        build-essential \
        ca-certificates \
        ### syslog-ng dependencies
        libglib2.0-dev \
        libssl-dev \
        pkg-config \
        freetds-bin \
        freetds-common \
        freetds-dev \
        libdbi-dev \
        libct4 \
        libsybdb5 \
        libdbd-mysql \
    && wget -O /usr/local/bin/gosu "https://github.com/tianon/gosu/releases/download/$GOSU_VERSION/gosu-$(dpkg --print-architecture)" \
    && wget -O /usr/local/bin/gosu.asc "https://github.com/tianon/gosu/releases/download/$GOSU_VERSION/gosu-$(dpkg --print-architecture).asc" \
    && export GNUPGHOME="$(mktemp -d)" \
    && ( gpg --keyserver ha.pool.sks-keyservers.net --recv-keys B42F6819007F00F88E364FD4036A9C25BF357DD4 \
         || gpg --keyserver pgp.mit.edu --recv-keys B42F6819007F00F88E364FD4036A9C25BF357DD4 \
         || gpg --keyserver keyserver.pgp.com --recv-keys B42F6819007F00F88E364FD4036A9C25BF357DD4 ) \
    && gpg --batch --verify /usr/local/bin/gosu.asc /usr/local/bin/gosu \
    && rm -r "$GNUPGHOME" /usr/local/bin/gosu.asc \
    && chmod +x /usr/local/bin/gosu \
    && gosu nobody true \
### Prepare nginx installation
    && set -x \
    && NGINX_GPGKEY=573BFD6B3D8FBC641079A6ABABF5BD827BD9BF62; \
	found=''; \
	for server in \
		ha.pool.sks-keyservers.net \
		hkp://keyserver.ubuntu.com:80 \
		hkp://p80.pool.sks-keyservers.net:80 \
		pgp.mit.edu \
	; do \
		echo "Fetching GPG key $NGINX_GPGKEY from $server"; \
		apt-key adv --keyserver "$server" --keyserver-options timeout=10 --recv-keys "$NGINX_GPGKEY" && found=yes && break; \
	done; \
	test -z "$found" && echo >&2 "error: failed to fetch GPG key $NGINX_GPGKEY" && exit 1; \
     echo "deb-src https://nginx.org/packages/mainline/ubuntu focal nginx" >> /etc/apt/sources.list.d/nginx.list \
    && tempDir="$(mktemp -d)" \
    && chmod 777 "$tempDir" \
    && apt-get update \
    && apt-get build-dep -y nginx \
### Install nginx
    && cd / \
    && wget https://nginx.org/download/nginx-1.19.0.tar.gz \
    && tar xvzf nginx-1.19.0.tar.gz \
    && cd /nginx-1.19.0 \
    && ./configure \
        --with-debug \
        --prefix=/opt/nginx \
    && make -j 15 \
    && make install \
    && mkdir /var/log/nginx \
    && cd / \
    && rm -rf nginx-1.19.0 \
    && rm -rf nginx-1.19.0.tar.gz \
### Redirect nginx logs to stdout/stderr
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log \
### Install syslog-ng
    && wget https://github.com/syslog-ng/syslog-ng/releases/download/syslog-ng-3.28.1/syslog-ng-3.28.1.tar.gz \
    && tar xzvf syslog-ng-3.28.1.tar.gz \
    && cd syslog-ng-3.28.1 \
    && ./configure \
        --disable-smtp \
        --disable-python \
        --disable-json \
        --enable-sql \
    && make -j 15 \
    && make install \
    && ldconfig \
### Install php
    && add-apt-repository ppa:ondrej/php \
    && apt-get update \
    && apt-get install --no-install-recommends -y \
        php7.4-fpm \
        php7.4-cli \
        php7.4-gd \
        php7.4-curl \
        php7.4-mysql \
        php7.4-mbstring \
        php7.4-xml \
        php7.4-zip \
        php7.4-bcmath \
        php7.4-intl \
        php7.4-readline \
        php7.4-msgpack \
        php7.4-igbinary \
        php-redis \
        libfontconfig1 \
        libxrender1 \
    && php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer \
    && mkdir /run/php \
    && sed -i -e "s/post_max_size\s*=\s*8M/post_max_size = 20M/g" /etc/php/7.4/fpm/php.ini \
    && sed -i -e "s/upload_max_filesize\s*=\s*2M/upload_max_filesize = 20M/g" /etc/php/7.4/fpm/php.ini \
### Cleanup the docker image
    && apt-get clean \
    && apt-get purge -y --auto-remove wget apt-transport-https software-properties-common build-essential gcc zip debhelper dh-systemd dpkg-dev quilt lsb-release libssl-dev libpcre3-dev zlib1g-dev git \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*  /usr/share/doc/* /usr/share/man/*

### Copy the nginx/php config to their respective folders
COPY docker/app/h5bp /opt/nginx/conf/h5bp
COPY docker/app/gzip.conf /opt/nginx/conf/gzip.conf
COPY docker/app/fastcgi-php.conf /opt/nginx/conf/fastcgi-php.conf
COPY docker/app/nginx.conf /opt/nginx/conf/nginx.conf
COPY docker/app/php-fpm.conf /etc/php/7.4/fpm/php-fpm.conf

### Copy the syslog-ng config
COPY docker/app/syslog-ng.conf /etc/syslog-ng/syslog-ng.conf

### Copy and setup the container entrypoint
COPY docker/app/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/app/start-container /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

EXPOSE 80

ENTRYPOINT ["start-container"]
