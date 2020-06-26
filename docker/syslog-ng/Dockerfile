FROM debian:stretch-slim

RUN apt-get update -qq && apt-get install -y \
        wget \
        gnupg2 \
    && apt-get install -y --no-install-recommends --no-install-suggests \
            curl \
            software-properties-common \
            git \
            supervisor \
            gnupg2 \
            apt-transport-https \
            wget \
            gcc \
            libglib2.0-dev \
            libssl-dev \
            build-essential \
            pkg-config \
            ca-certificates \
            freetds-bin \
            freetds-common \
            freetds-dev \
            libdbi-dev \
            libct4 \
            libsybdb5 \
            libdbd-mysql \
    && wget https://github.com/syslog-ng/syslog-ng/releases/download/syslog-ng-3.28.1/syslog-ng-3.28.1.tar.gz \
    && tar xzvf syslog-ng-3.28.1.tar.gz \
    && cd syslog-ng-3.28.1 \
    && ./configure --enable-debug --disable-smtp --disable-python --disable-json --enable-sql \
    && make -j 15 \
    && make install \
    && ldconfig \
    && apt-get clean \
    && apt-get purge -y --auto-remove wget apt-transport-https software-properties-common build-essential gcc curl \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY syslog-ng.conf /etc/syslog-ng/syslog-ng.conf

HEALTHCHECK --interval=2m --timeout=3s --start-period=30s CMD /usr/sbin/syslog-ng-ctl stats || exit 1

ENTRYPOINT ["/usr/local/sbin/syslog-ng", "-f", "/etc/syslog-ng/syslog-ng.conf", "-F"]
