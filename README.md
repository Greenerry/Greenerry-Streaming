# Greenerry

Greenerry is a web platform for independent music discovery, artist profiles, music releases, merchandise sales, and order management. It was built as a complete full-stack project with a public user area and an administration area.

The goal of Greenerry is to give artists a place to share music, promote their profile, sell merch, and manage their presence, while giving listeners a simple way to discover tracks, follow artists, save favourites, and buy products.

## Project Overview

Greenerry includes two main areas:

**Public/User Area**

- Browse music releases, tracks, artists, and merchandise
- Register and log in as a user
- Edit an artist profile with photo and banner
- Upload music releases for approval
- Upload merch products for approval
- Follow artists and save favourite tracks
- Add products to cart and complete checkout
- View order history and download receipts

**Admin Area**

- View dashboard statistics
- Approve, reject, deactivate, or reactivate music releases
- Approve, reject, deactivate, or reactivate products
- Manage product categories and sizes
- Manage users
- Update order and payment states
- Read and reply to support messages
- Manage password recovery requests
- Export platform reports
- Edit general platform settings

## Main Features

### Music

- Release pages for singles, EPs, and albums
- Track listing and playback
- Cover image support
- Favourite tracks
- Artist connection for every release
- Admin approval before releases become public

### Artists

- Public artist profile pages
- Artist avatar and banner
- Artist biography
- Artist releases and merch displayed in one place
- Follow system

### Merchandise

- Product upload system
- Product categories
- Size and stock management
- Product approval workflow
- Product detail pages
- Shopping cart

### Orders

- Checkout form with recipient and delivery details
- Order item storage
- Payment method and payment state tracking
- Order state tracking
- PDF receipt generation
- User order history
- Admin order management

### Administration

- Dashboard with project statistics
- Moderation tools for uploaded content
- User account management
- Category and size management
- Message center
- Password recovery workflow
- Reports/export section
- Site settings section

## Tech Stack

- **PHP** for backend logic
- **MySQL / MariaDB** for the database
- **JavaScript** for interactivity, music player behaviour, favourites, cart actions, language/theme handling, and AJAX requests
- **HTML5** for page structure
- **CSS3** for the interface and responsive layout
- **Composer** for PHP dependencies
- **Dompdf** for PDF receipt generation
- **XAMPP** for local Apache and MySQL development

## Folder Structure

```txt
greenerry/
├── admin/          Admin dashboard and management pages
├── api/            JSON endpoints used by JavaScript
├── assets/
│   ├── audio/      Uploaded music/audio files
│   ├── css/        Main stylesheet
│   ├── img/        Uploaded and static images
│   └── js/         Frontend JavaScript and translations
├── docs/           Project documentation and diagrams
├── includes/       Shared configuration, header, and footer
├── pages/          Public and user-facing pages
├── vendor/         Composer dependencies
├── greenerry.sql   Database structure and initial data
└── README.md       Project documentation
```

## Local Installation

### Requirements

- XAMPP
- PHP
- MySQL / MariaDB
- A modern browser

### Setup Steps

1. Place the project folder inside the XAMPP `htdocs` directory:

```txt
C:\xampp\htdocs\dashboard\greenerry
```

2. Start **Apache** and **MySQL** from the XAMPP control panel.

3. Open phpMyAdmin and create a database named:

```txt
greenerry
```

4. Import the database file:

```txt
greenerry.sql
```

5. Open the project in the browser:

```txt
http://localhost/dashboard/greenerry/
```

## Database Configuration

For local XAMPP development, the default database settings are already prepared:

```txt
host: localhost
user: root
password:
database: greenerry
```

For hosting or deployment, real database credentials should not be written directly into the public repository. The project supports environment variables:

```txt
GREENERRY_DB_HOST
GREENERRY_DB_USER
GREENERRY_DB_PASS
GREENERRY_DB_NAME
```

It also supports a private ignored file named:

```txt
includes/config.local.php
```

Example private configuration:

```php
<?php
$db_host = 'your-host';
$db_user = 'your-user';
$db_pass = 'your-password';
$db_name = 'your-database';
```

## Database Design

The database is stored in `greenerry.sql` and includes the main entities needed for the platform:

- admins
- users/artists
- password recovery requests
- music releases
- tracks
- favourites
- artist follows
- product categories
- product sizes
- products
- product stock by size
- orders
- order items
- delivery addresses
- payments
- admin messages
- notifications
- site settings

The database uses foreign keys to connect related data, such as users with releases, releases with tracks, products with orders, and customers with messages.

## Security Features

Greenerry includes several security-focused decisions:

- User passwords are stored using PHP password hashing.
- Session IDs are regenerated after successful login.
- Forms use CSRF tokens.
- Authenticated API POST actions also validate CSRF tokens.
- Output is escaped before being displayed in the browser.
- Uploaded images are validated before being saved.
- Users must be logged in before accessing private pages.
- Admin pages require admin authentication.
- Public content uploaded by users must be approved by an admin before it appears publicly.
- Live database credentials are kept outside the committed source code.
- `.htaccess` blocks direct access to sensitive file types such as SQL and environment files.

## User Roles

### Visitor

A visitor can browse public pages, view music, artists, and merch, and create an account.

### Registered User / Artist

A registered user can manage a profile, upload music, upload merch, follow artists, save favourites, place orders, and contact the admin.

### Admin

An admin can moderate uploaded content, manage users, manage product categories, update orders, handle messages, process password recovery requests, and view reports.

## Recommended Demo Flow

This flow shows the most important parts of the platform in a clear order:

1. Open the homepage and show the navigation.
2. Register or log in as a user.
3. Edit the user/artist profile.
4. Upload a music release.
5. Log in as admin.
6. Approve the music release.
7. Return to the public music page.
8. Play the approved track and add it to favourites.
9. Upload a merch product.
10. Approve the product as admin.
11. Add the product to the cart.
12. Complete checkout.
13. Open the order history.
14. Generate or view the receipt.
15. Show the reports and messages sections in the admin panel.

## Testing Checklist

Before presenting or submitting the project, test these flows:

- User registration
- User login and logout
- Admin login and logout
- Profile update with image upload
- Music release upload
- Admin release approval/rejection
- Music player playback
- Favourite add/remove
- Artist follow/unfollow
- Product upload
- Admin product approval/rejection
- Cart add/remove/update
- Checkout
- Receipt generation
- Order state update in admin
- Contact admin form
- Admin reply to message
- Password recovery request
- Language switch
- Theme switch
- Mobile navigation

## PHP Syntax Validation

Useful syntax checks:

```bash
php -l includes/config.php
php -l pages/login.php
php -l pages/registar.php
php -l pages/upload_music.php
php -l pages/upload_merch.php
php -l pages/checkout.php
php -l admin/dashboard.php
php -l admin/releases.php
php -l admin/products.php
```

To check every project PHP file except dependencies:

```powershell
Get-ChildItem -Recurse -Filter *.php |
  Where-Object { $_.FullName -notlike '*\vendor\*' } |
  ForEach-Object { php -l $_.FullName }
```

## Presentation Points

Important technical points to explain:

- The project has both a public side and an admin side.
- The database is relational and uses connected tables.
- Uploaded content does not become public immediately; it goes through admin approval.
- Checkout uses order, order item, address, and payment records.
- Passwords are hashed instead of stored as plain text.
- CSRF tokens protect forms and important API actions.
- Receipts are generated as PDF files using Dompdf.
- The interface supports Portuguese and English.
- The project is structured into reusable shared files, public pages, admin pages, API endpoints, and assets.

## Future Improvements

Possible improvements for later versions:

- Online payment gateway integration
- Email notifications
- Advanced search and filters
- Playlist creation
- Audio waveform previews
- Better analytics for artists
- Admin audit log
- Automated tests
- More detailed stock and sales reports

## Status

Greenerry is ready as a functional full-stack web project with authentication, content uploads, admin moderation, music playback, merch sales, reports, and receipt generation.
