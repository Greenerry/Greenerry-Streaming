# Greenerry

Greenerry is a PAP project for a music and merch platform. Users can create an account, publish music releases, sell merch, follow artists, save favourite tracks, place orders, and download receipts. The admin area manages approvals, users, orders, categories, reports, messages, and password recovery requests.

## Main Features

- User registration and login with hashed passwords
- Artist profile with avatar, banner, releases, merch, and followers
- Music uploads with admin approval before public listing
- Merch uploads with categories, sizes, stock, and admin approval
- Cart, checkout, order history, and PDF receipts
- Favourites and music player
- Admin dashboard for releases, products, users, orders, messages, reports, and settings
- Portuguese and English interface text
- CSRF protection on forms and authenticated API actions

## Setup With XAMPP

1. Put the project folder in `C:\xampp\htdocs\dashboard\greenerry`.
2. Start Apache and MySQL in XAMPP.
3. Create a MySQL database named `greenerry`.
4. Import `greenerry.sql` into that database.
5. Open `http://localhost/dashboard/greenerry/`.

The default local database settings are:

```txt
host: localhost
user: root
password:
database: greenerry
```

## Live Database Config

Do not write live passwords directly in `includes/config.php`.

For hosting, copy `includes/config.local.example.php` to `includes/config.local.php` and fill in the real database values, or define these environment variables:

```txt
GREENERRY_DB_HOST
GREENERRY_DB_USER
GREENERRY_DB_PASS
GREENERRY_DB_NAME
```

`includes/config.local.php` is ignored by Git.

## Admin Access

The admin account is created by `greenerry.sql`.

```txt
email: admin@greenerry.com
password: use the password defined for the school demo database
```

## Suggested Demo Flow

1. Register or log in as a normal user.
2. Edit the profile with an avatar/banner.
3. Upload a music release.
4. Log in as admin and approve the release.
5. Return to the public music page and play/favourite the track.
6. Upload a merch product.
7. Approve it in the admin panel.
8. Add the product to cart, checkout, and open the receipt.
9. Show reports and admin messages.

## Technical Points To Mention In The PAP

- Passwords are stored with PHP `password_hash`.
- Sessions are regenerated after login for safer authentication.
- Forms and API POST actions use CSRF tokens.
- User input is escaped before display with `htmlspecialchars`.
- Checkout and order updates use database transactions.
- Uploaded images are validated by extension and image metadata.
- Admin approval prevents public content from appearing immediately.

## Validation

Before presenting, run:

```bash
php -l includes/config.php
php -l pages/login.php
php -l pages/checkout.php
php -l admin/dashboard.php
```

Also test the full demo flow once with a fresh import of `greenerry.sql`.
