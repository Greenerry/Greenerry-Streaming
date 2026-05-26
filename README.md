# Greenerry

Greenerry is a PHP and MySQL web project created for a PAP. It is a platform for independent music, artist profiles, merch sales, orders, and admin moderation.

The project has two main sides:

- Public/user side: visitors and registered users can browse music, view artists, buy merch, and manage their own account.
- Admin side: the admin can approve content, manage users, update orders, reply to messages, and change site settings.

## Main Features

- User registration with email verification
- Login/logout for users and admin
- Password recovery by email link
- Artist profile with photo, banner, biography, releases, merch, followers, and following
- Music release upload with tracks and cover image
- Admin approval/rejection for music releases
- Music player with favourites
- Merch product upload with categories, sizes, stock, and multiple images
- Admin approval/rejection for products
- Shopping cart and checkout
- Order history and receipt page
- Admin dashboard, reports, messages, users, categories, products, releases, and settings
- Portuguese/English translations
- Dark/light theme

## Tech Stack

- PHP
- MySQL/MariaDB
- HTML
- CSS
- JavaScript
- Composer
- PHPMailer for SMTP email
- Dompdf for receipts
- XAMPP for local development

## Project Structure

```text
greenerry/
|-- admin/                 Admin pages
|-- api/                   AJAX/JSON endpoints
|-- assets/
|   |-- audio/             Audio files
|   |-- css/               Stylesheets
|   |-- img/               Images
|   `-- js/
|       |-- greenerry/     Split frontend scripts
|       `-- translations.json
|-- docs/                  Diagrams and PAP documents
|-- includes/              Shared PHP files
|-- pages/                 Public/user pages
|-- vendor/                Composer dependencies
|-- greenerry.sql          Final database schema and seed data
|-- index.php              Redirects to the homepage
`-- README.md
```

## Important Files

```text
includes/config.php        Starts session, connects to DB, loads helpers
includes/db.php            Database helper functions
includes/auth.php          Login/session helper functions
includes/auth_tokens.php   Email verification and password reset tokens
includes/email.php         Email sending and notification helpers
includes/i18n.php          PHP translations and label helpers
includes/validation.php    Form and upload validation
includes/product_images.php Product image helpers
includes/header.php        Public layout header
includes/footer.php        Public layout footer and frontend scripts
```

Frontend JavaScript is split into smaller files:

```text
assets/js/greenerry/core.js
assets/js/greenerry/favorites.js
assets/js/greenerry/player.js
assets/js/greenerry/cart.js
assets/js/greenerry/navigation.js
assets/js/greenerry/page-ui.js
```

## Local Setup

1. Put the project in XAMPP:

```text
C:\xampp\htdocs\dashboard\greenerry
```

2. Start Apache and MySQL in XAMPP.

3. Import `greenerry.sql` in phpMyAdmin.

The SQL file creates the database, tables, default site settings, default categories, default sizes, and one admin account.

4. Open the project:

```text
http://localhost/dashboard/greenerry/
```

## Default Admin

```text
Email: greenerry333@gmail.com
Password: Srijan123@
```

## Database Configuration

For local XAMPP, the default database settings are:

```text
host: localhost
user: root
password:
database: greenerry
```

For hosting, create a private file:

```text
includes/config.local.php
```

Example:

```php
<?php
$db_host = 'your-host';
$db_user = 'your-user';
$db_pass = 'your-password';
$db_name = 'your-database';
```

The project also supports environment variables:

```text
GREENERRY_DB_HOST
GREENERRY_DB_USER
GREENERRY_DB_PASS
GREENERRY_DB_NAME
```

## Hosting Notes

The project can run locally and on hosting.

Local links use the current localhost address. Live email links use:

```text
https://greenerry.gt.tc
```

When uploading to InfinityFree or another host, make sure to:

- upload the project files
- import `greenerry.sql`
- configure `includes/config.local.php`
- configure SMTP/email settings in the admin settings page
- keep `includes/config.local.php` private

## Database Design

Main tables:

- `admin`
- `configuracao_site`
- `cliente`
- `verificacao_email`
- `recuperacao_password`
- `release_musical`
- `faixa`
- `favorito_musica`
- `seguir_artista`
- `categoria`
- `tamanho`
- `produto`
- `produto_imagem`
- `produto_tamanho_stock`
- `encomenda`
- `encomenda_item`
- `morada_encomenda`
- `pagamento`
- `mensagem_admin`
- `notificacao`

Important relationships:

- One `cliente` can create many releases, products, favourites, follows, orders, messages, notifications, email verification tokens, and password recovery tokens.
- One `release_musical` has many `faixa`.
- One `produto` can have many images and size stock rows.
- One `encomenda` has many `encomenda_item`.
- One `encomenda` has one delivery address and one payment record.

## Security

- Passwords are stored with PHP password hashing.
- Login regenerates the session ID.
- Forms use CSRF tokens.
- API POST actions validate CSRF tokens.
- Output is escaped with `h()`.
- Uploads validate file size, extension, and type.
- Uploaded files get generated filenames.
- Private pages require login.
- Admin pages require admin login.
- User uploads only become public after admin approval.
- Database credentials can be kept outside the repository in `includes/config.local.php`.

## PAP Demo Flow

1. Open the homepage.
2. Register a user and explain email verification.
3. Log in as a user.
4. Edit the artist profile.
5. Upload a music release.
6. Log in as admin.
7. Approve the release.
8. Play a track and add it to favourites.
9. Upload a merch product.
10. Approve the product as admin.
11. Add the product to cart.
12. Complete checkout.
13. Open order history and receipt.
14. Show admin dashboard, reports, messages, and settings.

## Testing Checklist

- User registration
- Email verification
- User login/logout
- Admin login/logout
- Password recovery
- Profile update
- Music upload
- Release approval/rejection
- Music player
- Favourites
- Follow/unfollow artist
- Product upload
- Product approval/rejection
- Cart
- Checkout
- Receipt
- Order status update
- Contact admin
- Admin reply
- Language switch
- Theme switch
- Mobile navigation

## Validation Commands

Check all PHP files except `vendor`:

```powershell
Get-ChildItem -Recurse -Filter *.php |
  Where-Object { $_.FullName -notlike '*\vendor\*' } |
  ForEach-Object { php -l $_.FullName }
```

Check frontend scripts:

```powershell
Get-ChildItem assets\js\greenerry -Filter *.js |
  ForEach-Object { node --check $_.FullName }
```

## Diagrams

The `docs/` folder contains PAP support files:

- `DER_greenerry_Srijan.pdf`
- `greenerry_mer_fnn.drawio`
- `greenerry_mer_fnn.drawio.pdf`

## Final Status

Greenerry is ready as a functional PAP project with authentication, email verification, password recovery, music uploads, merch sales, checkout, receipts, admin moderation, settings, reports, and organized code.
