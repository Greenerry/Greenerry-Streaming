<?php
function greenerry_send_email(string $to, string $subject, string $message): bool
{
    if (site_setting('email_enabled', '0') !== '1') {
        return false;
    }

    $to = trim($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $siteName = site_setting('site_name', 'Greenerry');
    $from = site_setting('contact_email', 'support@greenerry.test');
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $safeSubject = str_replace(["\r", "\n"], ' ', $subject);
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    }

    $smtpHost = trim(site_setting('smtp_host', ''));
    if ($smtpHost !== '' && class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->Port = (int)site_setting('smtp_port', '587');
            $mail->SMTPAuth = site_setting('smtp_username', '') !== '';
            $mail->Username = site_setting('smtp_username', '');
            $mail->Password = site_setting('smtp_password', '');
            $secure = site_setting('smtp_secure', 'tls');
            if (in_array($secure, ['tls', 'ssl'], true)) {
                $mail->SMTPSecure = $secure;
            }
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($from, $siteName);
            $mail->addReplyTo($from, $siteName);
            $mail->addAddress($to);
            $mail->Subject = $safeSubject;
            $mail->Body = $message;
            return $mail->send();
        } catch (Throwable $e) {
            return false;
        }
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $siteName . ' <' . $from . '>',
        'Reply-To: ' . $from,
    ];

    return @mail($to, $safeSubject, wordwrap($message, 72), implode("\r\n", $headers));
}

function send_welcome_email(array $user): void
{
    greenerry_send_email(
        (string)$user['email'],
        tr('email.welcome_subject'),
        tr('email.welcome_body', ['name' => $user['nome'] ?? ''])
    );
}

function send_email_verification(array $user, string $token): bool
{
    $link = absolute_site_url('pages/verify_email.php?token=' . urlencode($token));

    return greenerry_send_email(
        (string)$user['email'],
        tr('email.verify_subject'),
        tr('email.verify_body', [
            'name' => $user['nome'] ?? '',
            'link' => $link,
        ])
    );
}

function send_reset_request_email(array $user): bool
{
    $token = (string)($user['reset_token'] ?? '');
    $link = absolute_site_url('pages/reset_password.php?token=' . urlencode($token));

    return greenerry_send_email(
        (string)$user['email'],
        tr('email.reset_request_subject'),
        tr('email.reset_request_body', [
            'name' => $user['nome'] ?? '',
            'link' => $link,
        ])
    );
}

function send_password_changed_email(array $user): void
{
    greenerry_send_email(
        (string)$user['email'],
        tr('email.password_changed_subject'),
        tr('email.password_changed_body', ['name' => $user['nome'] ?? ''])
    );
}

function send_test_email(string $to): bool
{
    return greenerry_send_email(
        $to,
        tr('email.test_subject'),
        tr('email.test_body', ['site' => site_setting('site_name', 'Greenerry')])
    );
}

function send_order_confirmation_email(mysqli $conn, int $orderId): void
{
    $order = db_one_prepared(
        $conn,
        "SELECT e.idEncomenda, e.total_final, e.estado_pagamento, e.metodo_pagamento, c.nome, c.email
         FROM encomenda e
         JOIN cliente c ON c.idCliente = e.idCliente
         WHERE e.idEncomenda = ?
         LIMIT 1",
        'i',
        [$orderId]
    );
    if (!$order) {
        return;
    }

    $items = db_all_prepared(
        $conn,
        "SELECT nome_produto, quantidade, total_linha
         FROM encomenda_item
         WHERE idEncomenda = ?
         ORDER BY idEncomendaItem ASC",
        'i',
        [$orderId]
    );
    $lines = [];
    foreach ($items as $item) {
        $lines[] = '- ' . $item['nome_produto'] . ' x' . (int)$item['quantidade'] . ' - ' . format_eur((float)$item['total_linha']);
    }

    greenerry_send_email(
        (string)$order['email'],
        tr('email.order_subject', ['id' => (string)$orderId]),
        tr('email.order_body', [
            'name' => $order['nome'] ?? '',
            'id' => (string)$orderId,
            'items' => implode("\n", $lines),
            'total' => format_eur((float)$order['total_final']),
            'receipt' => absolute_site_url('pages/receipt.php?id=' . $orderId),
        ])
    );
}

function send_artist_sale_emails(mysqli $conn, int $orderId): void
{
    $artists = db_all_prepared(
        $conn,
        "SELECT art.idCliente, art.nome, art.email,
                GROUP_CONCAT(CONCAT(ei.nome_produto, ' x', ei.quantidade) ORDER BY ei.idEncomendaItem SEPARATOR '\n') AS itens,
                COALESCE(SUM(ei.valor_artista), 0) AS valor_artista
         FROM encomenda_item ei
         JOIN cliente art ON art.idCliente = ei.idArtista
         WHERE ei.idEncomenda = ?
         GROUP BY art.idCliente, art.nome, art.email",
        'i',
        [$orderId]
    );

    foreach ($artists as $artist) {
        greenerry_send_email(
            (string)$artist['email'],
            tr('email.artist_sale_subject'),
            tr('email.artist_sale_body', [
                'name' => $artist['nome'] ?? '',
                'id' => (string)$orderId,
                'items' => (string)($artist['itens'] ?? ''),
                'value' => format_eur((float)$artist['valor_artista']),
            ])
        );
    }
}

function send_product_review_email(mysqli $conn, int $productId, string $action, string $reason = ''): void
{
    if (!in_array($action, ['aprovar', 'rejeitar'], true)) {
        return;
    }

    $product = db_one_prepared(
        $conn,
        "SELECT p.idProduto, p.nomeProduto, c.nome, c.email
         FROM produto p
         JOIN cliente c ON c.idCliente = p.idCliente
         WHERE p.idProduto = ?
         LIMIT 1",
        'i',
        [$productId]
    );
    if (!$product) {
        return;
    }

    $approved = $action === 'aprovar';
    greenerry_send_email(
        (string)$product['email'],
        $approved ? tr('email.product_approved_subject') : tr('email.product_rejected_subject'),
        tr($approved ? 'email.product_approved_body' : 'email.product_rejected_body', [
            'name' => $product['nome'] ?? '',
            'product' => $product['nomeProduto'] ?? '',
            'reason' => $reason !== '' ? $reason : '-',
            'link' => absolute_site_url('pages/produto.php?id=' . (int)$product['idProduto']),
        ])
    );
}

function send_release_review_email(mysqli $conn, int $releaseId, string $action, string $reason = ''): void
{
    if (!in_array($action, ['aprovar', 'rejeitar'], true)) {
        return;
    }

    $release = db_one_prepared(
        $conn,
        "SELECT r.idRelease, r.titulo, c.nome, c.email
         FROM release_musical r
         JOIN cliente c ON c.idCliente = r.idCliente
         WHERE r.idRelease = ?
         LIMIT 1",
        'i',
        [$releaseId]
    );
    if (!$release) {
        return;
    }

    $approved = $action === 'aprovar';
    greenerry_send_email(
        (string)$release['email'],
        $approved ? tr('email.release_approved_subject') : tr('email.release_rejected_subject'),
        tr($approved ? 'email.release_approved_body' : 'email.release_rejected_body', [
            'name' => $release['nome'] ?? '',
            'release' => $release['titulo'] ?? '',
            'reason' => $reason !== '' ? $reason : '-',
            'link' => absolute_site_url('pages/release.php?id=' . (int)$release['idRelease']),
        ])
    );
}
