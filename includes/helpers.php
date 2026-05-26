<?php
function h(?string $value): string
{
    // Escape output before printing it into HTML.
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function site_url(string $path = ''): string
{
    // Builds links that work both in XAMPP and on hosting.
    global $_base;

    $path = ltrim($path, '/');
    return $path === '' ? $_base : $_base . '/' . $path;
}

function absolute_site_url(string $path = ''): string
{
    global $_live, $_siteUrlLive, $_siteUrlLocal;

    $configuredUrl = trim((string)($_live ? $_siteUrlLive : $_siteUrlLocal));
    if ($configuredUrl !== '') {
        return rtrim($configuredUrl, '/') . site_url($path);
    }

    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (string)($_SERVER['SERVER_PORT'] ?? '') === '443';
    $scheme = $https ? 'https' : 'http';

    return $scheme . '://' . $host . site_url($path);
}

function asset_url(string $type, ?string $file): string
{
    // Turns a stored filename into a public asset URL.
    if (!$file) {
        return '';
    }

    return site_url('assets/' . trim($type, '/') . '/' . ltrim($file, '/'));
}

function delete_asset_file(string $type, ?string $file): bool
{
    // Safety checks stop accidental deletion outside the assets folder.
    $file = trim((string)$file);
    if ($file === '' || strpos($file, '..') !== false || preg_match('#^[a-z]+://#i', $file)) {
        return false;
    }

    $baseDir = realpath(__DIR__ . '/../assets/' . trim($type, '/'));
    if ($baseDir === false) {
        return false;
    }

    $path = $baseDir . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file), DIRECTORY_SEPARATOR);
    $realPath = realpath($path);
    if ($realPath === false || strpos($realPath, $baseDir . DIRECTORY_SEPARATOR) !== 0 || !is_file($realPath)) {
        return false;
    }

    return @unlink($realPath);
}

function uploaded_asset_is_referenced(mysqli $conn, string $type, ?string $file): bool
{
    $file = trim((string)$file);
    if ($file === '' || strpos($file, '..') !== false || preg_match('#^[a-z]+://#i', $file)) {
        return false;
    }

    if ($type === 'img') {
        return db_one_prepared($conn, "SELECT idCliente FROM cliente WHERE foto = ? OR banner = ? LIMIT 1", 'ss', [$file, $file]) !== null
            || db_one_prepared($conn, "SELECT idRelease FROM release_musical WHERE capa = ? LIMIT 1", 's', [$file]) !== null
            || db_one_prepared($conn, "SELECT idProdutoImagem FROM produto_imagem WHERE ficheiro = ? LIMIT 1", 's', [$file]) !== null;
    }

    if ($type === 'audio') {
        return db_one_prepared($conn, "SELECT idFaixa FROM faixa WHERE ficheiro_audio = ? LIMIT 1", 's', [$file]) !== null;
    }

    return true;
}

function delete_orphan_asset_file(mysqli $conn, string $type, ?string $file): bool
{
    $file = trim((string)$file);
    if ($file === '' || uploaded_asset_is_referenced($conn, $type, $file)) {
        return false;
    }

    return delete_asset_file($type, $file);
}

function delete_orphan_asset_files(mysqli $conn, string $type, array $files): int
{
    $deleted = 0;
    foreach (array_unique(array_filter(array_map('strval', $files))) as $file) {
        if (delete_orphan_asset_file($conn, $type, $file)) {
            $deleted++;
        }
    }
    return $deleted;
}

function cleanup_unused_uploaded_assets(mysqli $conn): int
{
    $deleted = 0;
    $groups = [
        'img' => [
            'dir' => __DIR__ . '/../assets/img',
            'pattern' => '/^(avatar|banner|product|release)_[a-z0-9_ -]+\.(jpe?g|png|webp)$/i',
        ],
        'audio' => [
            'dir' => __DIR__ . '/../assets/audio',
            'pattern' => '/^track_[a-z0-9_ -]+\.(mp3|wav|ogg|flac|m4a)$/i',
        ],
    ];

    foreach ($groups as $type => $group) {
        if (!is_dir($group['dir'])) {
            continue;
        }
        foreach (scandir($group['dir']) ?: [] as $file) {
            if ($file === '.' || $file === '..' || !preg_match($group['pattern'], $file)) {
                continue;
            }
            if (delete_orphan_asset_file($conn, $type, $file)) {
                $deleted++;
            }
        }
    }

    return $deleted;
}

function format_eur(float $value): string
{
    // Portuguese-style currency formatting for shop/orders/revenue.
    return number_format($value, 2, ',', '.') . ' EUR';
}

function count_label(int $count, string $type): string
{
    $lang = function_exists('current_lang') ? current_lang() : 'pt';
    $labels = [
        'release' => [
            'pt' => ['lançamento', 'lançamentos'],
            'en' => ['release', 'releases'],
        ],
        'track' => [
            'pt' => ['faixa', 'faixas'],
            'en' => ['track', 'tracks'],
        ],
        'product' => [
            'pt' => ['produto', 'produtos'],
            'en' => ['product', 'products'],
        ],
        'unit' => [
            'pt' => ['unidade', 'unidades'],
            'en' => ['unit', 'units'],
        ],
        'record' => [
            'pt' => ['registo', 'registos'],
            'en' => ['record', 'records'],
        ],
    ];

    $pair = $labels[$type][$lang] ?? $labels[$type]['pt'] ?? ['', ''];
    return $count . ' ' . ($count === 1 ? $pair[0] : $pair[1]);
}

function create_notification(mysqli $conn, int $userId, string $title, string $message, string $type = 'sistema'): bool
{
    // Stores a small message shown later in the notification menu/page.
    if ($userId <= 0 || trim($title) === '' || trim($message) === '') {
        return false;
    }

    $allowedTypes = ['sistema', 'produto', 'musica', 'encomenda', 'mensagem', 'password'];
    $type = in_array($type, $allowedTypes, true) ? $type : 'sistema';

    return (bool)db_prepared(
        $conn,
        "INSERT INTO notificacao (idCliente, titulo, mensagem, tipo) VALUES (?, ?, ?, ?)",
        'isss',
        [$userId, $title, $message, $type]
    );
}

function notification_context(mysqli $conn, array $note, int $userId): array
{
    // Builds the visual/link data used by the bell menu and notifications page.
    $type = (string)($note['tipo'] ?? 'sistema');
    $title = (string)($note['titulo'] ?? '');
    $message = (string)($note['mensagem'] ?? '');
    $quotedName = '';

    if (preg_match('/"([^"]+)"/u', $title . ' ' . $message, $match)) {
        $quotedName = trim((string)$match[1]);
    }

    $context = [
        'url' => site_url('pages/notifications.php'),
        'image' => '',
        'type' => $type,
        'label' => notification_type_label($type),
    ];

    if ($type === 'produto') {
        $product = $quotedName !== ''
            ? db_one_prepared(
                $conn,
                "SELECT idProduto FROM produto WHERE idCliente = ? AND nomeProduto = ? ORDER BY idProduto DESC LIMIT 1",
                'is',
                [$userId, $quotedName]
            )
            : null;

        if ($product) {
            $productId = (int)$product['idProduto'];
            $context['url'] = site_url('pages/produto.php?id=' . $productId);
            $image = product_main_image($conn, $productId);
            $context['image'] = $image !== '' ? asset_url('img', $image) : '';
        } else {
            $context['url'] = site_url('pages/profile.php');
        }
    } elseif ($type === 'musica') {
        $release = $quotedName !== ''
            ? db_one_prepared(
                $conn,
                "SELECT idRelease, capa FROM release_musical WHERE idCliente = ? AND titulo = ? ORDER BY idRelease DESC LIMIT 1",
                'is',
                [$userId, $quotedName]
            )
            : null;

        if ($release) {
            $context['url'] = site_url('pages/release.php?id=' . (int)$release['idRelease']);
            $context['image'] = asset_url('img', $release['capa'] ?? '');
        } else {
            $context['url'] = site_url('pages/profile.php');
        }
    } elseif ($type === 'encomenda') {
        if (preg_match('/#\s*(\d+)/', $title . ' ' . $message, $match)) {
            $orderId = (int)$match[1];
            $order = db_one_prepared(
                $conn,
                "SELECT idEncomenda FROM encomenda WHERE idEncomenda = ? AND idCliente = ? LIMIT 1",
                'ii',
                [$orderId, $userId]
            );
            if ($order) {
                $context['url'] = site_url('pages/receipt.php?id=' . $orderId);
            }
        }
    } elseif ($type === 'mensagem') {
        $context['url'] = site_url('pages/contact_admin.php');
    } elseif ($type === 'password') {
        $context['url'] = site_url('pages/profile.php');
    }

    return $context;
}

function notification_type_label(string $type, ?string $targetLang = null): string
{
    $labels = [
        'produto' => ['pt' => 'Produto', 'en' => 'Product'],
        'musica' => ['pt' => 'Música', 'en' => 'Music'],
        'encomenda' => ['pt' => 'Encomenda', 'en' => 'Order'],
        'mensagem' => ['pt' => 'Mensagem', 'en' => 'Message'],
        'password' => ['pt' => 'Conta', 'en' => 'Account'],
        'sistema' => ['pt' => 'Sistema', 'en' => 'System'],
    ];

    $lang = $targetLang ?? current_lang();
    return $labels[$type][$lang] ?? $labels['sistema'][$lang] ?? $labels['sistema']['pt'];
}

function notification_display_text(array $note, ?string $targetLang = null): array
{
    $title = (string)($note['titulo'] ?? '');
    $message = (string)($note['mensagem'] ?? '');
    $type = (string)($note['tipo'] ?? 'sistema');
    $lang = $targetLang ?? current_lang();
    $text = $title . ' ' . $message;
    $name = '';
    $reason = '';

    if (preg_match('/"([^"]+)"/u', $text, $match)) {
        $name = trim((string)$match[1]);
    }
    if (preg_match('/Motivo:\s*(.+)$/u', $message, $match)) {
        $reason = trim((string)$match[1]);
    }

    if ($type === 'produto' && $name !== '') {
        $actions = [
            'aprovado' => ['pt' => ['Produto aprovado', "O produto \"{$name}\" foi aprovado e já pode aparecer na loja."], 'en' => ['Product approved', "The product \"{$name}\" was approved and can now appear in the store."]],
            'rejeitado' => ['pt' => ['Produto rejeitado', "O produto \"{$name}\" foi rejeitado."], 'en' => ['Product rejected', "The product \"{$name}\" was rejected."]],
            'inativado' => ['pt' => ['Produto inativado', "O produto \"{$name}\" foi pausado pelo admin."], 'en' => ['Product paused', "The product \"{$name}\" was paused by the admin."]],
            'reativado' => ['pt' => ['Produto reativado', "O produto \"{$name}\" voltou a ficar ativo."], 'en' => ['Product reactivated', "The product \"{$name}\" is active again."]],
        ];
        foreach ($actions as $needle => $labels) {
            if (stripos($title, $needle) !== false) {
                $chosen = $labels[$lang] ?? $labels['pt'];
                if ($reason !== '' && $needle === 'rejeitado') {
                    $chosen[1] .= ($lang === 'en' ? ' Reason: ' : ' Motivo: ') . $reason;
                }
                return ['title' => $chosen[0], 'message' => $chosen[1]];
            }
        }
    }

    if ($type === 'musica' && $name !== '') {
        $actions = [
            'aprovado' => ['pt' => ['Lançamento aprovado', "O lançamento \"{$name}\" foi aprovado e já pode aparecer na música."], 'en' => ['Release approved', "The release \"{$name}\" was approved and can now appear in music."]],
            'rejeitado' => ['pt' => ['Lançamento rejeitado', "O lançamento \"{$name}\" foi rejeitado."], 'en' => ['Release rejected', "The release \"{$name}\" was rejected."]],
            'inativado' => ['pt' => ['Lançamento inativado', "O lançamento \"{$name}\" foi pausado pelo admin."], 'en' => ['Release paused', "The release \"{$name}\" was paused by the admin."]],
            'reativado' => ['pt' => ['Lançamento reativado', "O lançamento \"{$name}\" voltou a ficar ativo."], 'en' => ['Release reactivated', "The release \"{$name}\" is active again."]],
        ];
        foreach ($actions as $needle => $labels) {
            if (stripos($title, $needle) !== false) {
                $chosen = $labels[$lang] ?? $labels['pt'];
                if ($reason !== '' && $needle === 'rejeitado') {
                    $chosen[1] .= ($lang === 'en' ? ' Reason: ' : ' Motivo: ') . $reason;
                }
                return ['title' => $chosen[0], 'message' => $chosen[1]];
            }
        }
    }

    if ($type === 'encomenda' && preg_match('/#\s*(\d+)/', $text, $match)) {
        $orderId = (int)$match[1];
        return [
            'title' => $lang === 'en' ? 'Order updated' : 'Encomenda atualizada',
            'message' => $lang === 'en' ? "Order #{$orderId} was updated." : "A encomenda #{$orderId} foi atualizada.",
        ];
    }

    if ($type === 'mensagem' && stripos($title, 'admin') !== false) {
        return [
            'title' => $lang === 'en' ? 'Admin reply' : 'Resposta do admin',
            'message' => $lang === 'en' ? 'The admin replied to your message.' : 'O admin respondeu à tua mensagem.',
        ];
    }

    return ['title' => $title, 'message' => $message];
}

function notification_icon_svg(string $type): string
{
    $icons = [
        'produto' => '<svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
        'musica' => '<svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>',
        'encomenda' => '<svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect width="8" height="4" x="8" y="2" rx="1"/><path d="M8 12h8"/><path d="M8 16h6"/></svg>',
        'mensagem' => '<svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        'password' => '<svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    ];

    return $icons[$type] ?? '<svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>';
}

function notify_product_review(mysqli $conn, int $productId, string $action, string $reason = ''): void
{
    $product = db_one_prepared($conn, "SELECT idCliente, nomeProduto FROM produto WHERE idProduto = ? LIMIT 1", 'i', [$productId]);
    if (!$product) {
        return;
    }

    $name = (string)$product['nomeProduto'];
    $messages = [
        'aprovar' => ['Produto aprovado', "O produto \"{$name}\" foi aprovado e já pode aparecer na loja."],
        'rejeitar' => ['Produto rejeitado', "O produto \"{$name}\" foi rejeitado." . ($reason !== '' ? " Motivo: {$reason}" : '')],
        'inativar' => ['Produto inativado', "O produto \"{$name}\" foi pausado pelo admin."],
        'reativar' => ['Produto reativado', "O produto \"{$name}\" voltou a ficar ativo."],
    ];

    if (isset($messages[$action])) {
        create_notification($conn, (int)$product['idCliente'], $messages[$action][0], $messages[$action][1], 'produto');
    }
}

function notify_release_review(mysqli $conn, int $releaseId, string $action, string $reason = ''): void
{
    $release = db_one_prepared($conn, "SELECT idCliente, titulo FROM release_musical WHERE idRelease = ? LIMIT 1", 'i', [$releaseId]);
    if (!$release) {
        return;
    }

    $name = (string)$release['titulo'];
    $messages = [
        'aprovar' => ['Lançamento aprovado', "O lançamento \"{$name}\" foi aprovado e já pode aparecer na música."],
        'rejeitar' => ['Lançamento rejeitado', "O lançamento \"{$name}\" foi rejeitado." . ($reason !== '' ? " Motivo: {$reason}" : '')],
        'inativar' => ['Lançamento inativado', "O lançamento \"{$name}\" foi pausado pelo admin."],
        'reativar' => ['Lançamento reativado', "O lançamento \"{$name}\" voltou a ficar ativo."],
    ];

    if (isset($messages[$action])) {
        create_notification($conn, (int)$release['idCliente'], $messages[$action][0], $messages[$action][1], 'musica');
    }
}
