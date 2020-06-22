## Infrastructure

This document explains which components are needed to run imagelint and how they interact with each other.

### App
Laravel backend which handles image download and compression, as well as providing the admin interface.

Also contains an nginx server which is setup to serve files directly if they are available on disk, otherwise redirects the request to the laravel backend. The nginx logs all image requests to syslog-ng.

### Syslog-ng
Serves as a logging backend for handling image requests. Extracts relevant information from the nginx log and stores them in the mysql database.

### MySQL
Database to store access logs and data for the laravel backend.

### Redis
Cache for the laravel backend.

 