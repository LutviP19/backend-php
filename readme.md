# Backend PHP
- Simple framework built with php, focus for microservices.

# Requirement
- PHP 8.2+.
- Redis CLI
- RabbitMQ
- MySQL / SQLite
- Docker Desktop(optional)

# Setup the app up and running
- Install depedencies.
```bash
    composer install
```
- Create new .env file.
```bash
    cp .env.example .env
```
- Migrate database (mysql dump file)
create table users : storage/database/migrations/mysql/user.sql
create sample user : storage/database/seeders/mysql/insert_user.sql
- Docker Compose (Supported services).
- 
redis : 
```bash
$ docker compose up docker-compose/redis/docker-compose.yaml
```
rabbitmq :
```bash
$ docker compose up docker-compose/rabbitmq-python/docker-compose.yaml
```
mailpit :
```bash
$ docker compose up docker-compose/mailpit/docker-compose.yml
```
- Run server.
```bash
    php -S localhost:8000 -t public/
```

# Console App
- Listen Message (param: userid).
```bash
     php bin/console app:testing 1
```
- Get app info (param: userid).
```bash
     php bin/console app:info 1
```
- Setup app (planned)
```bash
     php bin/console app:setup
```

# Logs
Basepath of logs at: storage/logs

error log: storage/logs/app_error.log

debug log: storage/logs/app_debug.log

info log: storage/logs/app_info.log

# Demo
Please read DEMO.txt

# Development Process
Please read DEV.txt

**Please note this framework still in development and not tested in production environment.**