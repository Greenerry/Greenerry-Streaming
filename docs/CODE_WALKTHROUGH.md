# Greenerry Code Walkthrough

This document explains the project in normal student language, só it can be used to study the code before presenting the PAP.

## 1. What the Project Is

Greenerry is a PHP and MySQL web platform for independent music and artist merch.

It has three main parts:

- Public pages: homepage, music catalog, artist pages, shop, product pages.
- User área: login, register, profile, upload music, upload merch, cart, checkout, orders, favourites.
- Admin área: dashboard, users, products, releases, categories, reports, messages, settings.

The project is made with PHP, MySQL, HTML, CSS, JavaScript, Composer, PHPMailer, and Dompdf.

## 2. How a Page Loads

Most PHP pages start with:

```php
require_once '../includes/config.php';
```

That file is the project bootstrap. It starts the session, connects to the database, loads helper files, reads settings, checks the current user/admin, and prepares values used by JavaScript.

After that, most public pages include:

```php
include '../includes/header.php';
...
include '../includes/footer.php';
```

The header creates the sidebar, top navigation, language buttons, theme button, notifications, and page wrapper.

The footer closes the layout, prints the music player, loads translations, and loads the JavaScript files.

## 3. Database Helpers

The file `includes/db.php` contains helper functions for database queries.

- `db_escape()` escapes text before it is used in SQL.
- `db_one()` returns one row from a query.
- `db_all()` returns many rows.
- `db_prepared()` runs a prepared statement.
- `db_one_prepared()` returns one row using a prepared statement.
- `db_all_prepared()` returns many rows using a prepared statement.

Prepared statements are better for values that come from forms, URLs, or users.

## 4. Login and Permissions

The file `includes/auth.php` controls user/admin sessions.

- `is_user_logged_in()` checks if a normal user is logged in.
- `is_admin_logged_in()` checks if an admin is logged in.
- `current_user()` loads the current user from the database.
- `current_admin()` loads the current admin from the database.
- `require_user_login()` blocks pages unless a user is logged in.
- `require_admin_login()` blocks admin pages unless an admin is logged in.
- `admin_can()` checks if an admin role can access a section.

When a user logs in, the project stores user data in `$_SESSION`. The same idea is used for admins.

## 5. Search and Filtering

Search in the main catalogs is server-side.

Example from `pages/shop.php`:

1. The page reads the URL values:

```php
$category = (int)($_GET['cat'] ?? 0);
$search = trim($_GET['q'] ?? '');
```

2. It builds the SQL filter:

```php
$where = "WHERE p.estado = 'aprovado' AND p.ativo = 1 AND c.estado = 'ativo'";
```

3. If the user chose a category, it adds:

```php
AND p.idCategoria = ...
```

4. If the user typed a search, it adds `LIKE` checks:

```php
p.nomeProduto LIKE '%search%'
OR p.descricaoProduto LIKE '%search%'
OR c.nome LIKE '%search%'
```

5. It counts the total results, calculates pages, and fetches only the current page with `LIMIT` and `OFFSET`.

Music search in `pages/music.php` is similar, but it searches release title, artist name, and track title.

Artist search in `pages/artists.php` filters by artist name and only shows artists with approved releases.

Some filters are client-side:

- Favourites search filters tracks already loaded in the browser.
- Artist page filters hide/show cards without refreshing.
- Order filters hide/show orders by status and search text.
- Admin search boxes filter the already printed admin cards/tables.

## 6. Music Player

The music player is mainly in `assets/js/greenerry/player.js`.

Important ideas:

- `_loadTracks()` loads approved tracks from `api/tracks.php`.
- `playTrack()` is the main function that starts a track.
- The player updates the cover, title, artist, progress bar, queue, and favourite icon.
- `_saveState()` stores the current track in browser storage só the player can continue after navigation or refresh.
- `navigation.js` supports soft navigation, meaning some pages replace only the page content while music keeps playing.

The player uses a normal HTML `<áudio>` element from the footer:

```html
<áudio id="g-áudio"></áudio>
```

## 7. Favourites

Favourites are handled by `assets/js/greenerry/favorites.js` and `api/favorites.php`.

If the user is logged in:

- clicking favourite sends a POST request to the API;
- the API saves/removes the track in MySQL;
- localStorage is updated too só the UI stays fast.

If the user is not logged in:

- favourites are stored in localStorage as guest favourites;
- after login, `syncGuestFavorites()` can sync them into the database.

## 8. Cart and Checkout

The cart starts in `assets/js/greenerry/cart.js`.

- Products are stored in localStorage.
- Quantity checks respect the product stock.
- If the user is not logged in, the site saves the cart and sends the user to login.

Checkout is handled by PHP pages. It validates the cart, checks product availability and stock, creates the order, saves address/payment data, and sends emails if email is enabled.

## 9. Uploads

Music upload and merch upload are handled in:

- `pages/upload_music.php`
- `pages/upload_merch.php`
- `includes/validation.php`
- `includes/product_images.php`

The project validates file extension, file size, and image/áudio type. Uploaded files are renamed with generated names só users cannot control the final filename directly.

Music releases and products are sent as pending first. Admin approval is required before they appear publicly.

## 10. Admin Side

Admin pages live in the `admin/` folder.

Important pages:

- `dashboard.php`: statistics and recent pending items.
- `products.php`: review, approve, reject, activate, deactivate products.
- `releases.php`: review music releases.
- `users.php`: manage user state.
- `categories.php`: manage merch categories and sizes.
- `messages.php`: reply to user messages.
- `reports.php`: revenue and platform statistics.
- `settings.php`: site/email/social settings.

Most admin actions check permissions before allowing access.

## 11. Security Features

Good security choices already present:

- passwords use `password_hash()` and `password_verify()`;
- login regenerates session ID;
- CSRF tokens exist for forms and API actions;
- HTML output is escaped with `h()`;
- file uploads are validated;
- admin/user pages require login;
- uploaded products/releases need admin approval.

Areas to improve:

- more SQL should use prepared statements, especially queries built from URL/form data;
- there should be stronger password rules;
- checkout/payment is simulated, só it should be presented honestly as a demo;
- older versions had some missing Portuguese accents, so the app text should be checked once more before the final presentation.

## 12. Main Files to Know for the Presentation

- `includes/config.php`: starts the project.
- `includes/db.php`: database helper functions.
- `includes/auth.php`: login, sessions, permissions.
- `includes/helpers.php`: escaping, URLs, formatting, notifications.
- `pages/index.php`: homepage content.
- `pages/music.php`: music search/catalog.
- `pages/shop.php`: merch search/catalog.
- `pages/artists.php`: artist catalog.
- `pages/profile.php`: user profile, own releases/products/orders.
- `assets/js/greenerry/player.js`: áudio player.
- `assets/js/greenerry/favorites.js`: favourites and following.
- `assets/js/greenerry/cart.js`: cart storage and quantity logic.
- `admin/dashboard.php`: admin overview.
- `greenerry.sql`: database structure.

## 13. Honest PAP Assessment

Strong parts:

- The project is much bigger than a basic PAP site.
- It has a real platform idea, not just static pages.
- Authentication, admin moderation, uploads, cart, checkout, receipts, notifications, reports, settings, and translations are all strong features.
- The frontend is more polished than many 12th grade projects.
- The database has real relationships and covers many workflows.

Weak parts:

- Some files are long and hard to explain quickly.
- Some SQL is still built manually instead of always using prepared statements.
- Some Portuguese text was cleaned up, but it is still worth checking every page visually before delivery.
- The project needs consistent comments and naming because some names are Portuguese and some are English.
- Payment is not real payment integration, só call it order simulation/demo checkout.

Best way to present it:

Explain it as a marketplace/streaming prototype for independent artists. Focus on the workflow: user registers, uploads music/merch, admin reviews it, public users discover it, then cart/orders/revenue complete the business idea.
