FROM debian:stretch-slim

RUN apt-get update -qq && apt-get install -y \
        wget \
        gnupg2 \
    && wget -qO - https://download.opensuse.org/repositories/home:/laszlo_budai:/syslog-ng/Debian_9.0/Release.key | apt-key add - \
    && echo 'deb http://download.opensuse.org/repositories/home:/laszlo_budai:/syslog-ng/Debian_9.0 ./' | tee --append /etc/apt/sources.list.d/syslog-ng-obs.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends --no-install-suggests \
        syslog-ng \
        freetds-bin \
        freetds-common \
        freetds-dev \
        libct4 \
        libsybdb5 \
        libdbd-mysql \
    && ldconfig \
    && apt-get clean \
    && apt-get purge -y --auto-remove gnupg2 wget \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY syslog-ng.conf /etc/syslog-ng/syslog-ng.conf

HEALTHCHECK --interval=2m --timeout=3s --start-period=30s CMD /usr/sbin/syslog-ng-ctl stats || exit 1

ENTRYPOINT ["/usr/sbin/syslog-ng", "-F"]
