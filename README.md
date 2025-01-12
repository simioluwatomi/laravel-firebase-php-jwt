# Laravel Firebase PHP JWT API Authentication

This project demonstrates how to implement JWT authentication in a Laravel API using the Firebase PHP JWT package.

## Prerequisites

- PHP >= 8.1
- Laravel >= 10.0
- Composer
- Familiarity with API authentication concepts

## Installation

1. Clone the repository:

```bash
git clone https://github.com/simioluwatomi/laravel-firebase-php-jwt.git
cd laravel-jwt-auth
```

2. Install application dependencies:

```bash
composer install
```

3. Create an environment file:

```bash
cp .env.example .env
php artisan key:generate
```

4. Configure your database in the `.env` file:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## Next Steps

Read the [tutorial](https://simioluwatomi.com/laravel-api-authentication-using-firebase-php-jwt) to follow along.

## License

This project is licensed under the MIT License - see the LICENSE file for details.
