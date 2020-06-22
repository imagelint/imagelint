FROM balabit/syslog-ng:3.27.1

RUN apt-get update && apt-get install -y freetds-bin freetds-common freetds-dev libct4 libsybdb5 libdbd-mysql

COPY syslog-ng.conf /etc/syslog-ng/syslog-ng.conf