# Backend PHP
- An simple framework built with php, focus for microservices.

# Requirement
- PHP 8.2+.
- Redis CLI
- RabbitMQ
- MySQL / SQLite

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
create table users : storage/database/migrations/mysql
create sample user : storage/database/migrations/mysql/insert_user.sql
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
- Listen Message.
```bash
     php bin/console app:testing 1
```
- Setup app (planned)
```bash
     php bin/console app:setup
```
- Get app info (planned)
```bash
     php bin/console app:info
```

# Demo
Please read DEMO.txt

# Development Proccess
Please read DEV.txt

**Please note this framework it's still in development and not tested in production environment.**