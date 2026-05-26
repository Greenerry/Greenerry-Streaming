<?php
// Main bootstrap file.
// Every page includes this first so sessions, database, helpers, auth, and settings are ready.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detect if the project is running locally or on a live host.
$_host = strtolower((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
$_live = $_host !== 'localhost' && $_host !== '127.0.0.1' && substr($_host, 0, 10) !== 'localhost:';
$_scriptDir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '')));
$_base = preg_replace('#/(pages|admin|api|includes)$#', '', $_scriptDir);
$_base = $_base === '/' || $_base === '.' ? '' : rtrim((string)$_base, '/');
$_siteUrlLive = 'https://greenerry.gt.tc';
$_siteUrlLocal = '';

$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    // Hosting credentials can stay outside Git in this private local file.
    require $localConfig;
}

// Environment variables are checked first, then config.local.php, then XAMPP defaults.
$db_host = getenv('GREENERRY_DB_HOST') ?: ($db_host ?? ($_live ? '' : 'localhost'));
$db_user = getenv('GREENERRY_DB_USER') ?: ($db_user ?? ($_live ? '' : 'root'));
$db_pass = getenv('GREENERRY_DB_PASS') ?: ($db_pass ?? '');
$db_name = getenv('GREENERRY_DB_NAME') ?: ($db_name ?? ($_live ? '' : 'greenerry'));

if ($db_host === '' || $db_user === '' || $db_name === '') {
    die('Configura as credenciais da base de dados em variaveis de ambiente ou em includes/config.local.php.');
}

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die('Erro de base de dados: ' . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');

// Shared project functions. The order matters because some files depend on earlier helpers.
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/email.php';
require_once __DIR__ . '/maintenance.php';
require_once __DIR__ . '/auth_tokens.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/product_images.php';

$currentUser = current_user($conn);
$currentAdmin = current_admin($conn);
$jsUserId = $currentUser ? (int)$currentUser['idCliente'] : 0;
