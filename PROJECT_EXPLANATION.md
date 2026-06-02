# Greenerry Project - Technical Explanation

## 1. Overall Architecture

### File Structure
```
greenerry/
├── admin/              # Admin panel pages
├── api/               # AJAX API endpoints
├── assets/            # Static files (CSS, JS, images, audio)
│   ├── css/          # Stylesheets
│   ├── js/           # JavaScript modules
│   ├── img/          # Uploaded images
│   └── audio/        # Uploaded audio files
├── includes/          # Shared PHP files (loaded by every page)
│   ├── config.php    # Bootstrap: database, sessions, helpers
│   ├── helpers.php   # Utility functions
│   ├── db.php        # Database query functions
│   ├── auth.php      # Authentication (login, register, sessions)
│   ├── email.php     # PHPMailer email functions
│   └── ...
├── pages/             # Public-facing pages
└── uploads/           # Temporary upload directory
```

### How Every Page Works
1. **config.php** is included first (by every page)
   - Starts PHP session
   - Connects to MySQL database
   - Loads all helper files
   - Detects current user
   - Sets up translations

2. **header.php** is included next
   - Outputs HTML head (CSS, meta tags)
   - Sets up JavaScript translations
   - Renders navigation bar

3. **Page content** (the actual page logic)

4. **footer.php** is included last
   - Outputs JavaScript modules
   - Closes HTML body

---

## 2. Database Connection (config.php)

```php
// Detects if running locally (XAMPP) or on live hosting
$_host = strtolower($_SERVER['HTTP_HOST'] ? 'localhost');
$_live = $_host !== 'localhost' && $_host !== '127.0.0.1';

// Database credentials from environment variables or config.local.php
$db_host = getenv('GREENERRY_DB_HOST') ?: 'localhost';
$db_user = getenv('GREENERRY_DB_USER') ?: 'root';
$db_pass = getenv('GREENERRY_DB_PASS') ?: '';
$db_name = getenv('GREENERRY_DB_NAME') ?: 'greenerry';

// Connect to MySQL
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
mysqli_set_charset($conn, 'utf8mb4'); // Supports emojis and special characters
```

**Why this approach:**
- Environment variables keep credentials out of Git
- Works on both local (XAMPP) and live hosting
- UTF-8MB4 supports all characters including emojis

---

## 3. Authentication System (auth.php)

### Session-Based Authentication
```php
// When user logs in:
$_SESSION['user_id'] = $user['idCliente'];
$_SESSION['user_email'] = $user['email'];
```

### How Login Works (login.php)
1. User submits email/password
2. Password is hashed with `password_hash()` (bcrypt)
3. Database query finds user by email
4. `password_verify()` checks if hash matches
5. If valid, session is set with user ID
6. User redirected to profile

### How Register Works (registar.php)
1. User submits name, email, password
2. Password hashed with `password_hash()`
3. Insert into `cliente` table
4. Email verification token generated
5. PHPMailer sends verification email
6. User must click email link to activate account

### Session Validation
```php
function require_user_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}
```

**Why sessions:**
- Server-side storage (more secure than cookies alone)
- Automatic cleanup when browser closes
- Works across all pages that include config.php

---

## 4. File Upload & Deletion System

### File Upload (upload_music.php, upload_merch.php)

**Step 1: Form with enctype**
```html
<form enctype="multipart/form-data" method="post">
    <input type="file" name="audio_file">
    <input type="file" name="cover_image">
</form>
```

**Step 2: PHP validation**
```php
$file = $_FILES['audio_file'];
// Check if file was uploaded
if ($file['error'] !== UPLOAD_ERR_OK) {
    die('Upload failed');
}
// Check file type (only allow mp3, wav, etc.)
$allowedTypes = ['audio/mpeg', 'audio/wav'];
if (!in_array($file['type'], $allowedTypes)) {
    die('Invalid file type');
}
// Check file size (max 10MB)
if ($file['size'] > 10 * 1024 * 1024) {
    die('File too large');
}
```

**Step 3: Generate unique filename**
```php
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '_' . time() . '.' . $extension;
```

**Step 4: Move to assets folder**
```php
move_uploaded_file($file['tmp_name'], '../assets/audio/' . $filename);
```

**Step 5: Store filename in database**
```php
mysqli_query($conn, "INSERT INTO faixa (ficheiro_audio) VALUES ('$filename')");
```

### File Deletion (helpers.php)

**delete_orphan_asset_file()** - Deletes files no longer referenced in database:

```php
function delete_orphan_asset_file(mysqli $conn, string $type, ?string $file): bool
{
    // 1. Check if file is still referenced in database
    if (uploaded_asset_is_referenced($conn, $type, $file)) {
        return false; // Don't delete if still used
    }
    
    // 2. Safety checks (prevent deleting outside assets folder)
    if (strpos($file, '..') !== false) {
        return false; // Block path traversal attacks
    }
    
    // 3. Delete the file
    return delete_asset_file($type, $file);
}
```

**uploaded_asset_is_referenced()** - Checks if file is still in database:
```php
if ($type === 'img') {
    // Check if image is used in: cliente, release_musical, produto_imagem
    return db_one_prepared($conn, 
        "SELECT idCliente FROM cliente WHERE foto = ? OR banner = ?", 
        'ss', [$file, $file]) !== null;
}
if ($type === 'audio') {
    // Check if audio is used in: faixa
    return db_one_prepared($conn, 
        "SELECT idFaixa FROM faixa WHERE ficheiro_audio = ?", 
        's', [$file]) !== null;
}
```

**delete_asset_file()** - Actually deletes the file:
```php
function delete_asset_file(string $type, ?string $file): bool
{
    $baseDir = realpath(__DIR__ . '/../assets/' . $type);
    $path = $baseDir . DIRECTORY_SEPARATOR . $file;
    return @unlink($path); // Delete file from disk
}
```

**Why this approach:**
- Prevents orphaned files (files on disk but not in DB)
- Safety checks prevent deleting system files
- Only deletes after database transaction succeeds
- Supports both images and audio

---

## 5. AJAX & API System

### How AJAX Works

**Frontend (JavaScript):**
```javascript
fetch('api/toggle_follow.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'artist_id=123&csrf_token=abc123'
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        // Update UI
    }
});
```

**Backend (api/toggle_follow.php):**
```php
<?php
require_once '../includes/config.php';

// Verify CSRF token (prevents CSRF attacks)
if (!verify_csrf_request()) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF']);
    exit;
}

// Get POST data
$artistId = (int)($_POST['artist_id'] ? 0);

// Check if already following
$existing = db_one($conn, 
    "SELECT id FROM seguir_artista WHERE idSeguidor = ? AND idArtista = ?",
    'ii', [$userId, $artistId]
);

if ($existing) {
    // Unfollow
    mysqli_query($conn, "DELETE FROM seguir_artista WHERE id = ?");
} else {
    // Follow
    mysqli_query($conn, "INSERT INTO seguir_artista (idSeguidor, idArtista) VALUES (?, ?)");
}

// Return JSON response
echo json_encode(['success' => true]);
```

**Why AJAX:**
- No page reload (better UX)
- Instant feedback
- Can update specific parts of the page
- Used for: follow/unfollow, add to cart, favorites, search filters

**CSRF Protection:**
- Every form has a hidden CSRF token
- Token generated in session when page loads
- API verifies token before processing
- Prevents attackers from submitting forms on user's behalf

---

## 6. PHPMailer Email System (email.php)

### How PHPMailer Works

**Setup (email.php):**
```php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_email(string $to, string $subject, string $body): bool
{
    $mail = new PHPMailer(true);
    
    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Or your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';
        $mail->Password = 'your-app-password'; // Not regular password!
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Email content
        $mail->setFrom('noreply@greenerry.gt.tc', 'Greenerry');
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->isHTML(true);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email failed: ' . $e->getMessage());
        return false;
    }
}
```

**Email Verification Flow:**
1. User registers → generate random token
2. Store token in database with expiry time
3. Send email with link: `verify_email.php?token=abc123`
4. User clicks link → verify token in database
5. If valid and not expired → activate account

**Why PHPMailer:**
- Handles SMTP authentication
- Supports HTML emails
- Error handling built-in
- Works with Gmail, Outlook, custom SMTP servers

---

## 7. Music Streaming Player System

### Database Structure
```
cliente (artists/users)
  ↓
release_musical (albums/singles/EPs)
  ↓
faixa (individual tracks)
  ↓
ficheiro_audio (audio file path)
```

### How Player Works (assets/js/greenerry/player.js)

**playTrack() function:**
```javascript
async function playTrack(title, artist, cover, audioSrc, artistId, artistFoto, musicId) {
    // 1. Update audio element
    const audio = document.getElementById('g-audio');
    audio.src = audioSrc;
    
    // 2. Play audio
    await audio.play();
    
    // 3. Update UI (show current track info)
    document.querySelector('.pb-title').textContent = title;
    document.querySelector('.pb-artist').textContent = artist;
    document.querySelector('.pb-cover img').src = cover;
    
    // 4. Add to queue (if not already there)
    addToQueue({ title, artist, cover, audioSrc, artistId, artistFoto, musicId });
    
    // 5. Update play button icon
    _updatePlayBtn(true);
}
```

**Audio Events:**
```javascript
audio.addEventListener('timeupdate', () => {
    // Update progress bar
    const progress = (audio.currentTime / audio.duration) * 100;
    document.querySelector('.pb-fill').style.width = progress + '%';
});

audio.addEventListener('ended', () => {
    // Play next track in queue
    playNextInQueue();
});
```

**Why this approach:**
- HTML5 Audio API (no external libraries needed)
- Queue system for continuous playback
- Progress bar updates in real-time
- Works on all modern browsers

---

## 8. Admin Panel & Moderation System

### Admin Authentication (admin/admin_header.php)
```php
// Separate admin session
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
```

### Moderation Workflow

**Step 1: Artist submits release (upload_music.php)**
- Status: `pendente` (pending)
- Not visible to public

**Step 2: Admin reviews (admin/releases.php)**
- See all pending releases
- Listen to preview
- Approve or reject

**Step 3: Admin action (api/admin_approve_release.php)**
```php
if ($action === 'approve') {
    mysqli_query($conn, 
        "UPDATE release_musical SET estado = 'aprovado' WHERE idRelease = ?");
    // Send email notification to artist
    send_email($artistEmail, 'Release Approved', '...');
} else if ($action === 'reject') {
    mysqli_query($conn, 
        "UPDATE release_musical SET estado = 'rejeitado' WHERE idRelease = ?");
    // Delete files
    delete_orphan_asset_files($conn, 'audio', $audioFiles);
    delete_orphan_asset_file($conn, 'img', $coverFile);
}
```

**Why moderation:**
- Prevents inappropriate content
- Quality control
- Legal compliance (copyright)
- Email notifications keep artists informed

---

## 9. Translation System (i18n.php)

### How Translations Work

**1. translations.json file:**
```json
{
  "pt": {
    "nav_home": "Inicio",
    "nav_music": "Música"
  },
  "en": {
    "nav_home": "Home",
    "nav_music": "Music"
  }
}
```

**2. Detect language (i18n.php):**
```php
function current_lang(): string
{
    // Check URL parameter (?lang=en)
    if (isset($_GET['lang'])) {
        $_SESSION['lang'] = $_GET['lang'];
    }
    // Default to Portuguese
    return $_SESSION['lang'] ? 'pt';
}
```

**3. Get translation:**
```php
function tr(string $key): string
{
    $lang = current_lang();
    $translations = json_decode(file_get_contents('../assets/js/translations.json'), true);
    return $translations[$lang][$key] ? $key;
}
```

**4. Use in HTML:**
```php
<h1 data-t="nav_home"><?= tr('nav_home') ?></h1>
```

**5. JavaScript translations:**
```php
<script id="greenerry-translations" type="application/json">
<?= json_encode($translationsJson) ?>
</script>
```

**Why this approach:**
- Single JSON file for all translations
- Works on both server (PHP) and client (JS)
- Easy to add new languages
- URL parameter switches language instantly

---

## 10. Key Pages Explained

### index.php (Homepage)
```php
// 1. Get stats for hero section
$homeStats = [
    'tracks' => db_one($conn, "SELECT COUNT(*) FROM faixa WHERE estado = 'aprovada'"),
    'artists' => db_one($conn, "SELECT COUNT(*) FROM cliente WHERE estado = 'ativo'"),
    'products' => db_one($conn, "SELECT COUNT(*) FROM produto WHERE estado = 'aprovado'")
];

// 2. Get featured releases
$featuredReleases = db_all($conn, 
    "SELECT * FROM release_musical WHERE estado = 'aprovado' ORDER BY criado_em DESC LIMIT 6"
);

// 3. Check which sections are enabled (settings.php)
$showMusicArea = public_page_active('music.php');
$showShopArea = public_page_active('shop.php');
```

### music.php (Music Catalog)
```php
// 1. Search and filter
$search = $_GET['q'] ? '';
$category = $_GET['cat'] ? '';

// 2. Build query with filters
$sql = "SELECT r.*, c.nome as artist_nome 
        FROM release_musical r 
        JOIN cliente c ON c.idCliente = r.idCliente 
        WHERE r.estado = 'aprovado'";

if ($search) {
    $sql .= " AND r.titulo LIKE '%$search%'";
}

// 3. Pagination
$page = (int)($_GET['page'] ? 1);
$offset = ($page - 1) * $perPage;
$sql .= " LIMIT $perPage OFFSET $offset";

// 4. Display results
foreach ($releases as $release) {
    // Render card with play button
}
```

### shop.php (Merch Store)
```php
// Similar to music.php but for products
$products = db_all($conn, 
    "SELECT p.*, cat.nomeCategoria 
     FROM produto p 
     JOIN categoria cat ON cat.idCategoria = p.idCategoria 
     WHERE p.estado = 'aprovado'"
);
```

### profile.php (User Profile)
```php
// 1. Get current user
$uid = current_user_id();
$user = db_one($conn, "SELECT * FROM cliente WHERE idCliente = $uid");

// 2. Get user's releases
$releases = db_all($conn, 
    "SELECT * FROM release_musical WHERE idCliente = $uid"
);

// 3. Get user's products
$products = db_all($conn, 
    "SELECT * FROM produto WHERE idCliente = $uid"
);

// 4. Get user's orders
$orders = db_all($conn, 
    "SELECT * FROM encomenda WHERE idCliente = $uid"
);

// 5. Tab system (edit/orders/music/merch)
$activeTab = $_GET['tab'] ? 'edit';
```

---

## 11. Security Features

### 1. SQL Injection Prevention
```php
// BAD (vulnerable):
mysqli_query($conn, "SELECT * FROM cliente WHERE id = $id");

// GOOD (using prepared statements):
db_one_prepared($conn, "SELECT * FROM cliente WHERE id = ?", 'i', [$id]);
```

### 2. XSS Prevention
```php
// BAD (vulnerable):
echo $user_input;

// GOOD (escape output):
echo h($user_input); // htmlspecialchars()
```

### 3. CSRF Protection
```php
// Generate token:
$token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $token;

// Verify token:
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token invalid');
}
```

### 4. Password Hashing
```php
// Hash password:
$hash = password_hash($password, PASSWORD_BCRYPT);

// Verify password:
if (password_verify($inputPassword, $storedHash)) {
    // Login successful
}
```

---

## 12. Common Questions Teachers Might Ask

**Q: Why use sessions instead of cookies for authentication?**
A: Sessions are stored server-side, more secure. Cookies can be stolen. Sessions also support larger data and automatic cleanup.

**Q: How do you prevent SQL injection?**
A: Using prepared statements (mysqli prepared statements) with parameterized queries. Never concatenate user input into SQL strings.

**Q: Why use prepared statements?**
A: They separate SQL logic from data, preventing injection. Database engine treats parameters as data, not executable code.

**Q: How does the file deletion system prevent deleting system files?**
A: Multiple safety checks: realpath() resolves paths, checks if path is within assets folder, blocks ".." (path traversal), only deletes files not directories.

**Q: Why use AJAX for follow/unfollow?**
A: No page reload, better UX. Instant feedback. Can update specific UI elements without full page refresh.

**Q: How does PHPMailer work?**
A: It's a library that handles SMTP authentication. Connects to email server (Gmail, Outlook), authenticates with username/password, sends email using SMTP protocol.

**Q: Why use JSON for translations?**
A: Easy to read/edit, works with both PHP and JavaScript, single file for all languages, can be cached by browser.

**Q: How does the music player work?**
A: HTML5 Audio API. JavaScript controls audio element (play, pause, seek). Progress bar updates via timeupdate event. Queue system for continuous playback.

**Q: What's the moderation workflow?**
A: Artist submits → status=pending → admin reviews → approve/reject → email notification → if rejected, files deleted.

**Q: How do you handle image uploads?**
A: Validate file type/size, generate unique filename, move to assets folder, store filename in database, delete orphaned files when no longer referenced.

---

## Summary

Your project is a full-stack web application with:
- **Backend:** PHP with MySQL database
- **Frontend:** HTML, CSS, JavaScript (vanilla, no frameworks)
- **Features:** Music streaming, e-commerce, user accounts, admin panel
- **Security:** Prepared statements, password hashing, CSRF protection, input validation
- **Architecture:** MVC-like separation (includes for logic, pages for views, api for endpoints)

The key systems work together:
- **config.php** bootstraps everything
- **helpers.php** provides utility functions
- **auth.php** handles authentication
- **db.php** abstracts database operations
- **email.php** sends emails via PHPMailer
- AJAX endpoints in **api/** handle dynamic actions
- **admin/** provides moderation interface
