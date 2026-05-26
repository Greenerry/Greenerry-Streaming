<?php
const GREENERRY_MAX_IMAGE_BYTES = 5_000_000;
const GREENERRY_MAX_AUDIO_BYTES = 25_000_000;
const GREENERRY_MAX_PRODUCT_IMAGES = 5;
const GREENERRY_MAX_RELEASE_TRACKS = 20;

function required_field(string $value, string $ptLabel, string $enLabel): ?string
{
    if (trim($value) !== '') {
        return null;
    }

    return current_lang() === 'en'
        ? $enLabel . ' is required.'
        : $ptLabel . ' é obrigatório.';
}

function validate_nome(string $nome): ?string
{
    $nome = trim($nome);
    if ($nome === '') {
        return tr('error.required_name');
    }
    if (mb_strlen($nome) < 2) {
        return tr('error.short_name');
    }
    if (mb_strlen($nome) > 120) {
        return tr('error.long_name');
    }
    if (!preg_match("/^[\\p{L}\\p{M}][\\p{L}\\p{M}\\s.'-]*$/u", $nome)) {
        return tr('error.invalid_name_chars');
    }
    return null;
}

function validate_city(string $city): ?string
{
    $city = trim($city);
    if ($city === '' || mb_strlen($city) < 2) {
        return tr('error.required_city');
    }
    if (!preg_match("/^[\\p{L}\\p{M}][\\p{L}\\p{M}\\s.'-]*$/u", $city)) {
        return tr('error.invalid_city_chars');
    }
    return null;
}

function validate_phone(?string $phone): ?string
{
    $phone = trim((string)$phone);
    if ($phone !== '' && !preg_match('/^(\\+351\\s?)?9\\d{8}$/', $phone)) {
        return tr('error.invalid_phone');
    }
    return null;
}

function validate_postal_code(string $postalCode): ?string
{
    return preg_match('/^\\d{4}-\\d{3}$/', trim($postalCode)) ? null : tr('error.invalid_postal');
}

function validate_nif(?string $nif): ?string
{
    $nif = trim((string)$nif);
    return $nif === '' || preg_match('/^\\d{9}$/', $nif) ? null : tr('error.invalid_nif');
}

function validate_email(string $email): ?string
{
    $email = trim($email);
    if ($email === '') {
        return tr('error.required_email');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return tr('error.invalid_email');
    }
    if (mb_strlen($email) > 150) {
        return tr('error.long_email');
    }
    return null;
}

function validate_password(string $password): ?string
{
    if ($password === '') {
        return tr('error.required_password');
    }
    if (strlen($password) < 8) {
        return tr('error.short_password');
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

    return false;
}

function validate_uploaded_image(array $file, int $maxBytes = GREENERRY_MAX_IMAGE_BYTES): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    if (($file['size'] ?? 0) > $maxBytes) {
        return current_lang() === 'en'
            ? 'The image is too large.'
            : 'A imagem é demasiado grande.';
    }

    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return tr('error.image_format');
    }

    $info = @getimagesize($file['tmp_name'] ?? '');
    if (!$info || !in_array((string)($info['mime'] ?? ''), ['image/jpeg', 'image/png', 'image/webp'], true)) {
        return tr('error.image_format');
    }

    return null;
}

function save_uploaded_file(array $file, string $type, string $prefix, array $allowedExtensions, int $maxBytes): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['', null];
    }

    if (($file['size'] ?? 0) > $maxBytes) {
        return ['', current_lang() === 'en' ? 'The file is too large.' : 'O ficheiro é demasiado grande.'];
    }

    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) {
        return ['', $type === 'audio' ? tr('error.audio_format') : tr('error.image_format')];
    }

    $dir = __DIR__ . '/../assets/' . trim($type, '/') . '/';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        return ['', current_lang() === 'en' ? 'Could not prepare the upload folder.' : 'Não foi possível preparar a pasta de upload.'];
    }

    $name = preg_replace('/[^a-z0-9_-]+/i', '_', $prefix) . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file((string)($file['tmp_name'] ?? ''), $dir . $name)) {
        return ['', current_lang() === 'en' ? 'Could not save the uploaded file.' : 'Não foi possível guardar o ficheiro enviado.'];
    }

    return [$name, null];
}

function validate_uploaded_audio(array $file, int $maxBytes = GREENERRY_MAX_AUDIO_BYTES): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    if (($file['size'] ?? 0) > $maxBytes) {
        return current_lang() === 'en'
            ? 'The audio file is too large.'
            : 'O ficheiro de audio é demasiado grande.';
    }

    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['mp3', 'wav', 'ogg', 'flac', 'm4a'], true)) {
        return tr('error.audio_format');
    }

    $mime = '';
    if (function_exists('finfo_open') && is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = (string)finfo_file($finfo, (string)$file['tmp_name']);
            finfo_close($finfo);
        }
    }

    $allowedMimes = ['audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/ogg', 'audio/flac', 'audio/mp4', 'audio/x-m4a', 'application/octet-stream'];
    if ($mime !== '' && !in_array($mime, $allowedMimes, true)) {
        return tr('error.audio_format');
    }

    return null;
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
        $sql = "SELECT idCliente FROM cliente WHERE slug = ?";
        $types = 's';
        $params = [$slug];
        if ($ignoreId > 0) {
            $sql .= " AND idCliente != ?";
            $types .= 'i';
            $params[] = $ignoreId;
        }
        $exists = db_one_prepared($conn, $sql, $types, $params);
        if (!$exists) {
            return $slug;
        }
        $suffix++;
        $slug = $baseSlug . '-' . $suffix;
    }
}
