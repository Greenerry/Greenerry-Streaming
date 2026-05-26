<?php
// Authentication and permission helpers.
// Pages call these functions instead of repeating session checks everywhere.

function is_user_logged_in(): bool
{
    return !empty($_SESSION['user_logged_in']) && !empty($_SESSION['user_id']);
}

function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_id']);
}

function current_user_id(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

function current_admin_id(): int
{
    return (int)($_SESSION['admin_id'] ?? 0);
}

function current_user(mysqli $conn): ?array
{
    static $user = false;

    // Static cache avoids querying the same user many times on one page load.
    if ($user !== false) {
        return $user;
    }

    if (!is_user_logged_in()) {
        $user = null;
        return $user;
    }

    $uid = current_user_id();
    $user = db_one($conn, "SELECT * FROM cliente WHERE idCliente = {$uid} LIMIT 1");
    return $user;
}

function active_user_session(mysqli $conn): bool
{
    if (!is_user_logged_in()) {
        return false;
    }

    $user = current_user($conn);
    return $user !== null && (string)($user['estado'] ?? '') === 'ativo';
}

function end_user_session_only(): void
{
    unset(
        $_SESSION['user_logged_in'],
        $_SESSION['user_id'],
        $_SESSION['user_email'],
        $_SESSION['user_name']
    );
}

function current_admin(mysqli $conn): ?array
{
    static $admin = false;

    if ($admin !== false) {
        return $admin;
    }

    if (!is_admin_logged_in()) {
        $admin = null;
        return $admin;
    }

    $aid = current_admin_id();
    $admin = db_one($conn, "SELECT * FROM admin WHERE idAdmin = {$aid} LIMIT 1");
    return $admin;
}

function admin_role_key(?array $admin = null): string
{
    // Converts admin job titles into simple permission roles used by admin_can().
    $role = strtolower(trim((string)($admin['cargo'] ?? $_SESSION['admin_role'] ?? '')));
    $role = str_replace(['ç', 'ã', 'á', 'à', 'â', 'é', 'ê', 'í', 'ó', 'õ', 'ô', 'ú'], ['c', 'a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u'], $role);

    if ($role === '' || str_contains($role, 'principal') || str_contains($role, 'super')) {
        return str_contains($role, 'principal') || str_contains($role, 'super') ? 'super' : 'admin';
    }
    if (str_contains($role, 'produto') || str_contains($role, 'catalog')) {
        return 'products';
    }
    if (str_contains($role, 'musica') || str_contains($role, 'music') || str_contains($role, 'release')) {
        return 'releases';
    }
    if (str_contains($role, 'suporte') || str_contains($role, 'message') || str_contains($role, 'mensagem')) {
        return 'messages';
    }
    if (str_contains($role, 'relatorio') || str_contains($role, 'report')) {
        return 'reports';
    }

    return 'admin';
}

function is_super_admin(?array $admin = null): bool
{
    $email = strtolower((string)($admin['email'] ?? $_SESSION['admin_email'] ?? ''));
    return $email === 'greenerry333@gmail.com' || admin_role_key($admin) === 'super';
}

function admin_can(string $permission, ?array $admin = null): bool
{
    // This is the admin permission map. Super admin can do everything.
    if (!$admin && isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
        $admin = current_admin($GLOBALS['conn']);
    }

    if (!$admin || (string)($admin['ativo'] ?? '0') !== '1') {
        return false;
    }

    if (is_super_admin($admin)) {
        return true;
    }

    $role = admin_role_key($admin);
    $map = [
        'admin' => ['products', 'categories', 'releases', 'users', 'messages', 'reports', 'home', 'maintenance', 'settings'],
        'products' => ['products', 'categories'],
        'releases' => ['releases'],
        'messages' => ['messages'],
        'reports' => ['reports'],
    ];

    return in_array($permission, $map[$role] ?? [], true);
}

function admin_default_page(?array $admin = null): string
{
    if (!$admin && isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
        $admin = current_admin($GLOBALS['conn']);
    }

    if (!$admin || is_super_admin($admin)) {
        return 'dashboard.php';
    }

    $preferred = [
        'products' => 'products.php',
        'categories' => 'categories.php',
        'releases' => 'releases.php',
        'messages' => 'messages.php',
        'reports' => 'reports.php',
        'users' => 'users.php',
        'home' => 'home_curator.php',
        'maintenance' => 'page_maintenance.php',
        'settings' => 'settings.php',
    ];

    foreach ($preferred as $permission => $page) {
        if (admin_can($permission, $admin)) {
            return $page;
        }
    }

    return 'dashboard.php';
}

function login_user_session(array $user): void
{
    // Regenerating the session id helps prevent session fixation after login.
    session_regenerate_id(true);
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = (int)$user['idCliente'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['nome'];
}

function login_admin_session(array $admin): void
{
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = (int)$admin['idAdmin'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_name'] = $admin['nome'];
    $_SESSION['admin_role'] = $admin['cargo'] ?? 'Administrador';
}

function logout_all_sessions(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

function require_user_login(): void
{
    global $conn;

    // Used by private user pages such as profile, checkout, orders, and uploads.
    if (!is_user_logged_in()) {
        header('Location: login.php');
        exit;
    }

    if (!active_user_session($conn)) {
        end_user_session_only();
        header('Location: login.php?inactive=1');
        exit;
    }
}

function require_admin_login(): void
{
    global $conn;

    if (!is_admin_logged_in()) {
        header('Location: ../pages/login.php');
        exit;
    }

    $admin = current_admin($conn);
    if (!$admin || (int)($admin['ativo'] ?? 0) !== 1) {
        unset($_SESSION['admin_logged_in'], $_SESSION['admin_id'], $_SESSION['admin_email'], $_SESSION['admin_name'], $_SESSION['admin_role']);
        header('Location: ../pages/login.php');
        exit;
    }
}

function require_admin_permission(string $permission): void
{
    global $conn;

    // Used by admin pages that need a specific permission area.
    require_admin_login();
    if (!admin_can($permission, current_admin($conn))) {
        http_response_code(403);
        include __DIR__ . '/../admin/admin_forbidden.php';
        exit;
    }
}

function redirect_if_authenticated(): void
{
    if (is_user_logged_in()) {
        global $conn;

        if (active_user_session($conn)) {
            header('Location: index.php');
            exit;
        }

        end_user_session_only();
    }

    if (is_admin_logged_in()) {
        global $conn;
        header('Location: ../admin/' . admin_default_page(current_admin($conn)));
        exit;
    }
}
