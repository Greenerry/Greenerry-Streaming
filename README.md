# Greenerry

Greenerry is a web platform for independent music streaming, artist discovery, and merchandise sales. It combines a public user experience with an administration area where content, orders, users, reports, and support requests can be managed.

The project was developed in PHP, MySQL, JavaScript, HTML, and CSS, using XAMPP for local development.

## Features

- Account registration and login
- Artist profiles with avatar, banner, releases, products, and followers
- Music release uploads with admin approval
- Merchandise uploads with categories, sizes, stock, and admin approval
- Music player with favourites
- Product cart, checkout, order history, and PDF receipts
- Admin dashboard for releases, products, users, orders, messages, reports, categories, and settings
- Password recovery request flow handled by the admin
- Portuguese and English interface support
- Form and API CSRF protection

## Tech Stack

- PHP
- MySQL / MariaDB
- JavaScript
- HTML5 and CSS3
- Composer
- Dompdf for PDF receipt generation
- XAMPP for local Apache and MySQL

## Local Setup

1. Place the project in:

```txt
C:\xampp\htdocs\dashboard\greenerry
```

2. Start Apache and MySQL in XAMPP.
3. Create a database named:

```txt
greenerry
```

4. Import `greenerry.sql` into the database.
5. Open the project in the browser:

```txt
http://localhost/dashboard/greenerry/
```

The default local database configuration is already prepared for XAMPP:

```txt
host: localhost
user: root
password:
database: greenerry
```

## Configuration

Local XAMPP works with the default values in `includes/config.php`.

For hosting, keep real database credentials outside GitHub. The project supports either environment variables:

```txt
GREENERRY_DB_HOST
GREENERRY_DB_USER
GREENERRY_DB_PASS
GREENERRY_DB_NAME
```

or a private ignored file named:

```txt
includes/config.local.php
```

That file should define:

```php
<?php
$db_host = 'your-host';
$db_user = 'your-user';
$db_pass = 'your-password';
$db_name = 'your-database';
```

## Database

The database structure and initial data are stored in `greenerry.sql`.

The SQL file includes the tables for:

- users and admins
- music releases and tracks
- favourites and artist follows
- products, categories, sizes, and stock
- orders, order items, addresses, and payments
- admin messages, password requests, notifications, and settings

## Admin Area

The admin user is created by `greenerry.sql`. Admin access is used to approve or reject uploaded music and merch, manage users, update orders, reply to messages, export reports, and edit platform settings.

Admin route:

```txt
/admin/dashboard.php
```

## Suggested Presentation Flow

1. Open the homepage and show the public navigation.
2. Register or log in as a normal user.
3. Edit the artist profile.
4. Upload a music release.
5. Log in as admin and approve the release.
6. Return to the music page and play/favourite the track.
7. Upload a merch product.
8. Approve the product in the admin area.
9. Add the product to the cart and complete checkout.
10. Show the order history, receipt, reports, and admin messages.

## Security Highlights

- Passwords are stored with PHP password hashing.
- Session IDs are regenerated after login.
- Forms and authenticated API actions use CSRF tokens.
- Output is escaped before being displayed.
- Uploaded images are validated before being stored.
- Admin approval controls public content visibility.
- Live database credentials are not stored directly in the repository.

## Validation

Run PHP syntax checks before presenting:

```bash
php -l includes/config.php
php -l pages/login.php
php -l pages/checkout.php
php -l admin/dashboard.php
```

Recommended manual test:

```txt
register -> upload music -> admin approve -> play/favourite -> upload merch -> admin approve -> checkout -> receipt
```
