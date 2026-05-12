# Laravel REST API Backend

Laravel 12 application with API routing enabled.

## Local Setup

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

The app uses SQLite by default. The database file is `database/database.sqlite`.

## API Endpoints

Base URL when using `php artisan serve`:

```text
http://127.0.0.1:8000/api
```

Available routes:

```text
GET     /health
```

## Tests

```bash
php artisan test
```
