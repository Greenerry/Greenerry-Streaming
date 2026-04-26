<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_live = false;

if ($_live) {
    $db_host = 'sql101.infinityfree.com';
    $db_user = 'if0_41557620';
    $db_pass = '57bfNlBy0k';
    $db_name = 'if0_41557620_greenerry';
    $_base = '/greenerry';
} else {
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'greenerry';
    $_base = '/dashboard/greenerry';
}

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die('Erro de base de dados: ' . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function site_url(string $path = ''): string
{
    global $_base;

    $path = ltrim($path, '/');
    return $path === '' ? $_base : $_base . '/' . $path;
}

function asset_url(string $type, ?string $file): string
{
    if (!$file) {
        return '';
    }

    return site_url('assets/' . trim($type, '/') . '/' . ltrim($file, '/'));
}

function format_eur(float $value): string
{
    return number_format($value, 2, ',', '.') . ' EUR';
}

function order_status_label(string $status): string
{
    return match ($status) {
        'aprovado', 'aprovada' => 'Aprovado',
        'rejeitado', 'rejeitada' => 'Rejeitado',
        'inativo', 'inativa' => 'Inativo',
        'ativo', 'ativa' => 'Ativo',
        'pendente' => 'Pendente',
        'em_preparacao' => 'Em preparacao',
        'enviado' => 'Enviado',
        'enviada' => 'Enviada',
        'entregue' => 'Entregue',
        'cancelado' => 'Cancelado',
        'cancelada' => 'Cancelada',
        default => ucfirst($status),
    };
}

function payment_status_label(string $status): string
{
    return match ($status) {
        'pendente' => 'Pendente',
        'pago' => 'Pago',
        'falhado' => 'Falhado',
        'reembolsado' => 'Reembolsado',
        default => ucfirst($status),
    };
}

function payment_method_label(string $method): string
{
    return match ($method) {
        'cartao' => 'Cartao',
        'mbway' => 'MB Way',
        'transferencia' => 'Transferencia',
        default => ucfirst($method),
    };
}

function state_badge_class(string $state): string
{
    return match ($state) {
        'aprovado', 'aprovada', 'ativo', 'ativa', 'pago', 'entregue' => 'badge-blue',
        'pendente', 'em_preparacao', 'enviada', 'enviado', 'em_analise' => 'badge-red',
        'rejeitado', 'rejeitada', 'inativo', 'inativa', 'cancelada', 'cancelado', 'falhado', 'reembolsado', 'bloqueado', 'recusado' => 'badge-light',
        default => 'badge-dark',
    };
}

function db_escape(mysqli $conn, ?string $value): string
{
    return mysqli_real_escape_string($conn, (string)$value);
}

function db_one(mysqli $conn, string $sql): ?array
{
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    return $row ?: null;
}

function db_all(mysqli $conn, string $sql): array
{
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_free_result($result);
    return $rows;
}

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

function login_user_session(array $user): void
{
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = (int)$user['idCliente'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['nome'];
}

function login_admin_session(array $admin): void
{
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = (int)$admin['idAdmin'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_name'] = $admin['nome'];
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
    if (!is_user_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin_login(): void
{
    if (!is_admin_logged_in()) {
        header('Location: ../pages/login.php');
        exit;
    }
}

function redirect_if_authenticated(): void
{
    if (is_user_logged_in()) {
        header('Location: index.php');
        exit;
    }

    if (is_admin_logged_in()) {
        header('Location: ../admin/dashboard.php');
        exit;
    }
}

function validate_nome(string $nome): ?string
{
    $nome = trim($nome);
    if ($nome === '') {
        return 'O nome e obrigatorio.';
    }
    if (mb_strlen($nome) < 2) {
        return 'O nome deve ter pelo menos 2 caracteres.';
    }
    if (mb_strlen($nome) > 120) {
        return 'O nome nao pode ter mais de 120 caracteres.';
    }
    return null;
}

function validate_email(string $email): ?string
{
    $email = trim($email);
    if ($email === '') {
        return 'O email e obrigatorio.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'O email introduzido nao e valido.';
    }
    if (mb_strlen($email) > 150) {
        return 'O email nao pode ter mais de 150 caracteres.';
    }
    return null;
}

function validate_password(string $password): ?string
{
    if ($password === '') {
        return 'A palavra-passe e obrigatoria.';
    }
    if (strlen($password) < 8) {
        return 'A palavra-passe deve ter pelo menos 8 caracteres.';
    }
    return null;
}

function password_matches(string $plainPassword, string $storedPassword): bool
{
    if ($storedPassword === '') {
        return false;
    }

    if (password_verify($plainPassword, $storedPassword)) {
        return true;
    }

    return hash_equals($storedPassword, $plainPassword);
}

function normalize_slug(string $text): string
{
    $text = trim(mb_strtolower($text));
    $replace = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a',
        'é' => 'e', 'ê' => 'e',
        'í' => 'i',
        'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ú' => 'u',
        'ç' => 'c'
    ];
    $text = strtr($text, $replace);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string)$text, '-');
}

function ensure_unique_cliente_slug(mysqli $conn, string $nome, int $ignoreId = 0): string
{
    $baseSlug = normalize_slug($nome);
    if ($baseSlug === '') {
        $baseSlug = 'artista';
    }

    $slug = $baseSlug;
    $suffix = 1;
    while (true) {
        $slugSafe = db_escape($conn, $slug);
        $sql = "SELECT idCliente FROM cliente WHERE slug = '{$slugSafe}'";
        if ($ignoreId > 0) {
            $sql .= " AND idCliente != {$ignoreId}";
        }
        $exists = db_one($conn, $sql);
        if (!$exists) {
            return $slug;
        }
        $suffix++;
        $slug = $baseSlug . '-' . $suffix;
    }
}

$currentUser = current_user($conn);
$currentAdmin = current_admin($conn);
$jsUserId = $currentUser ? (int)$currentUser['idCliente'] : 0;
