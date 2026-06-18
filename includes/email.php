<?php
// Shared email helper: Sends account and support emails through the configured mailer.
// Keep this shared code small, reusable, and safe for every page.
function greenerry_email_text(string $html): string
{
    $text = preg_replace('#<br\s*/?>#i', "\n", $html);
    $text = preg_replace('#</(p|div|tr|h1|h2|h3|li)>#i', "\n", (string)$text);
    $text = html_entity_decode(strip_tags((string)$text), ENT_QUOTES, 'UTF-8');
    return trim(preg_replace("/\n{3,}/", "\n\n", (string)$text));
}

function greenerry_email_shell(string $title, string $intro, string $contentHtml, string $lang = ''): string
{
    $siteName = h(site_setting('site_name', 'Greenerry'));
    $preheader = h($intro);
    $footer = ($lang ?: current_lang()) === 'en'
        ? 'This email was sent automatically by Greenerry.'
        : 'Este email foi enviado automaticamente pela Greenerry.';

    return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;background:#f4f6f5;color:#111827;font-family:Arial,Helvetica,sans-serif;">'
        . '<div style="display:none;max-height:0;overflow:hidden;color:transparent;">' . $preheader . '</div>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6f5;padding:28px 12px;"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#ffffff;border:1px solid #dfe6e2;border-radius:20px;overflow:hidden;">'
        . '<tr><td style="padding:26px 28px 18px;background:#111827;color:#ffffff;">'
        . '<div style="font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:#a7f3d0;font-weight:700;">' . $siteName . '</div>'
        . '<h1 style="margin:10px 0 8px;font-size:30px;line-height:1.12;font-weight:700;">' . h($title) . '</h1>'
        . '<p style="margin:0;color:#cbd5e1;font-size:15px;line-height:1.55;">' . h($intro) . '</p>'
        . '</td></tr><tr><td style="padding:24px 28px;">' . $contentHtml . '</td></tr>'
        . '<tr><td style="padding:18px 28px;background:#f8faf9;color:#64746d;font-size:12px;line-height:1.5;">' . h($footer) . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

function greenerry_send_email(string $to, string $subject, string $message, array $options = []): bool
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
    $html = (string)($options['html'] ?? '');
    $attachments = is_array($options['attachments'] ?? null) ? $options['attachments'] : [];
    $altBody = $html !== '' ? greenerry_email_text($html) : $message;
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
            if ($html !== '') {
                $mail->isHTML(true);
                $mail->Body = $html;
                $mail->AltBody = $altBody;
            } else {
                $mail->Body = $message;
            }
            foreach ($attachments as $attachment) {
                $content = (string)($attachment['content'] ?? '');
                $filename = (string)($attachment['filename'] ?? 'attachment.pdf');
                if ($content !== '') {
                    $mail->addStringAttachment($content, $filename, 'base64', (string)($attachment['mime'] ?? 'application/pdf'));
                }
            }
            return $mail->send();
        } catch (Throwable $e) {
            return false;
        }
    }

    if ($html !== '' || $attachments) {
        $boundary = 'greenerry_' . bin2hex(random_bytes(8));
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
            'From: ' . $siteName . ' <' . $from . '>',
            'Reply-To: ' . $from,
        ];
        $body = "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n"
            . ($html !== '' ? $html : nl2br(h($message))) . "\r\n";
        foreach ($attachments as $attachment) {
            $content = (string)($attachment['content'] ?? '');
            if ($content === '') {
                continue;
            }
            $filename = str_replace(['"', "\r", "\n"], '', (string)($attachment['filename'] ?? 'attachment.pdf'));
            $mime = (string)($attachment['mime'] ?? 'application/pdf');
            $body .= "--{$boundary}\r\n"
                . "Content-Type: {$mime}; name=\"{$filename}\"\r\n"
                . "Content-Transfer-Encoding: base64\r\n"
                . "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n"
                . chunk_split(base64_encode($content)) . "\r\n";
        }
        $body .= "--{$boundary}--";
        return @mail($to, $safeSubject, $body, implode("\r\n", $headers));
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
    $subject = tr('email.welcome_subject');
    $body = tr('email.welcome_body', ['name' => $user['nome'] ?? '']);
    $html = greenerry_email_shell($subject, current_lang() === 'en' ? 'Welcome to Greenerry.' : 'Bem-vindo/a à Greenerry.', '<p style="margin:0;color:#334155;font-size:15px;line-height:1.6;">' . nl2br(h($body)) . '</p>');
    greenerry_send_email(
        (string)$user['email'],
        $subject,
        $body,
        ['html' => $html]
    );
}

function send_email_verification(array $user, string $code): bool
{
    $subject = tr('email.verify_subject');
    $verifyUrl = absolute_site_url('pages/verify_email.php?email=' . urlencode((string)($user['email'] ?? '')) . '&sent=1');
    $body = tr('email.verify_body', [
        'name' => $user['nome'] ?? '',
        'code' => $code,
    ]);
    $buttonLabel = current_lang() === 'en' ? 'Open verification page' : 'Abrir pagina de verificacao';
    $html = greenerry_email_shell($subject, current_lang() === 'en' ? 'Enter this code to activate your account.' : 'Insere este codigo para ativar a conta.', '<p style="margin:0 0 18px;color:#334155;font-size:15px;line-height:1.6;">' . nl2br(h($body)) . '</p><div style="display:inline-block;letter-spacing:8px;background:#111827;color:#fff;border-radius:16px;padding:16px 22px;font-size:28px;font-weight:800;">' . h($code) . '</div><p style="margin:22px 0 0;"><a href="' . h($verifyUrl) . '" style="display:inline-block;background:#111827;color:#ffffff;text-decoration:none;border-radius:999px;padding:12px 18px;font-size:14px;font-weight:700;">' . h($buttonLabel) . '</a></p>');
    return greenerry_send_email(
        (string)$user['email'],
        $subject,
        $body,
        ['html' => $html]
    );
}

function send_reset_request_email(array $user): bool
{
    $code = (string)($user['reset_code'] ?? '');

    $subject = tr('email.reset_request_subject');
    $body = tr('email.reset_request_body', [
        'name' => $user['nome'] ?? '',
        'code' => $code,
    ]);
    $html = greenerry_email_shell($subject, current_lang() === 'en' ? 'Enter this code to choose a new password.' : 'Insere este código para escolher uma nova password.', '<p style="margin:0 0 18px;color:#334155;font-size:15px;line-height:1.6;">' . nl2br(h($body)) . '</p><div style="display:inline-block;letter-spacing:8px;background:#111827;color:#fff;border-radius:16px;padding:16px 22px;font-size:28px;font-weight:800;">' . h($code) . '</div>');
    return greenerry_send_email(
        (string)$user['email'],
        $subject,
        $body,
        ['html' => $html]
    );
}

function send_password_changed_email(array $user): void
{
    $subject = tr('email.password_changed_subject');
    $body = tr('email.password_changed_body', ['name' => $user['nome'] ?? '']);
    $html = greenerry_email_shell($subject, current_lang() === 'en' ? 'Your password was changed.' : 'A tua password foi alterada.', '<p style="margin:0;color:#334155;font-size:15px;line-height:1.6;">' . nl2br(h($body)) . '</p>');
    greenerry_send_email(
        (string)$user['email'],
        $subject,
        $body,
        ['html' => $html]
    );
}

function send_test_email(string $to): bool
{
    $title = tr('email.test_subject');
    $body = tr('email.test_body', ['site' => site_setting('site_name', 'Greenerry')]);
    $html = greenerry_email_shell($title, $body, '<p style="margin:0;font-size:15px;line-height:1.6;color:#334155;">' . nl2br(h($body)) . '</p>');
    return greenerry_send_email(
        $to,
        $title,
        $body,
        ['html' => $html]
    );
}

function send_notification_email(mysqli $conn, int $userId, string $title, string $message, string $type = 'sistema'): bool
{
    $user = db_one_prepared(
        $conn,
        "SELECT nome, email FROM cliente WHERE idCliente = ? AND estado = 'ativo' LIMIT 1",
        'i',
        [$userId]
    );
    if (!$user) {
        return false;
    }

    $notificationsUrl = absolute_site_url('pages/notifications.php');
    $subject = 'Greenerry - ' . trim($title);
    $intro = current_lang() === 'en'
        ? 'There is an update in your Greenerry account.'
        : 'Tens uma atualização na tua conta Greenerry.';
    $body = trim($message) . "\n\n" . $notificationsUrl;
    $html = greenerry_email_shell(
        $title,
        $intro,
        '<p style="margin:0 0 18px;color:#334155;font-size:15px;line-height:1.6;">' . nl2br(h($message)) . '</p>'
        . '<p style="margin:0;"><a href="' . h($notificationsUrl) . '" style="display:inline-block;background:#111827;color:#ffffff;text-decoration:none;border-radius:999px;padding:12px 18px;font-weight:700;">'
        . h(current_lang() === 'en' ? 'Open notifications' : 'Abrir notificações')
        . '</a></p>'
    );

    return greenerry_send_email(
        (string)$user['email'],
        $subject,
        $body,
        ['html' => $html]
    );
}

function greenerry_order_invoice_html(mysqli $conn, int $orderId): string
{
    $order = db_one_prepared(
        $conn,
        "SELECT e.*, c.nome, c.email, me.nome_destinatario, me.morada, me.cidade, me.codigo_postal, me.pais, me.telefone
         FROM encomenda e
         JOIN cliente c ON c.idCliente = e.idCliente
         LEFT JOIN morada_encomenda me ON me.idEncomenda = e.idEncomenda
         WHERE e.idEncomenda = ?
         LIMIT 1",
        'i',
        [$orderId]
    );
    if (!$order) {
        return '';
    }

    $items = db_all_prepared(
        $conn,
        "SELECT nome_produto, quantidade, preco_unitario, total_linha
         FROM encomenda_item
         WHERE idEncomenda = ?
         ORDER BY idEncomendaItem ASC",
        'i',
        [$orderId]
    );
    $companyName = site_setting('company_name', site_setting('site_name', 'Greenerry'));
    $companyAddress = site_setting('company_address', 'Portugal');
    $companyNif = site_setting('company_nif', '');
    $companyEmail = site_setting('contact_email', '');

    $rows = '';
    foreach ($items as $item) {
        $rows .= '<tr><td>' . h($item['nome_produto']) . '</td><td>' . (int)$item['quantidade'] . '</td><td>' . h(format_eur((float)$item['preco_unitario'])) . '</td><td style="text-align:right">' . h(format_eur((float)$item['total_linha'])) . '</td></tr>';
    }

    return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,Arial,sans-serif;font-size:13px;color:#102418;margin:0;padding:40px;background:#ffffff;}
.brand{font-size:30px;font-weight:700;letter-spacing:.04em;color:#163524;margin-bottom:4px;}
.sub{font-size:13px;color:#5f6f66;margin-bottom:24px;}
.panel{border:1px solid #d9e0dc;border-radius:18px;padding:18px;margin-bottom:22px;}
.row{display:table;width:100%;}.col{display:table-cell;vertical-align:top;width:50%;}.right{text-align:right;}
.label{font-size:10px;text-transform:uppercase;letter-spacing:.12em;color:#7f8b84;margin-bottom:6px;}
.value{font-size:13px;color:#24352d;margin:4px 0;} table{width:100%;border-collapse:collapse;}
th{padding:12px 10px;background:#f3f6f4;color:#567062;font-size:10px;text-transform:uppercase;letter-spacing:.12em;text-align:left;}
td{padding:12px 10px;border-bottom:1px solid #e4e9e6;color:#1f3028;}.totals td{font-weight:700;border-bottom:none;}
.foot{margin-top:24px;font-size:11px;color:#7f8b84;text-align:center;}
</style></head><body>
<div class="brand">GREENERRY</div>
<div class="sub">' . h(tr('receipt.title', ['id' => (string)$orderId])) . '</div>
<div class="panel"><div class="label">Emitente</div><p class="value"><strong>' . h($companyName) . '</strong></p><p class="value">' . h($companyAddress) . '</p>' . ($companyNif !== '' ? '<p class="value">NIF: ' . h($companyNif) . '</p>' : '') . ($companyEmail !== '' ? '<p class="value">Email: ' . h($companyEmail) . '</p>' : '') . '</div>
<div class="panel"><div class="row"><div class="col"><div class="label">' . h(tr('receipt.customer')) . '</div><p class="value"><strong>' . h($order['nome']) . '</strong></p><p class="value">' . h($order['email']) . '</p>' . ($order['nif'] ? '<p class="value">NIF: ' . h($order['nif']) . '</p>' : '') . '</div><div class="col right"><div class="label">' . h(tr('receipt.details')) . '</div><p class="value">' . h(tr('receipt.date')) . ': <strong>' . date('d/m/Y', strtotime((string)$order['criado_em'])) . '</strong></p><p class="value">' . h(tr('receipt.payment')) . ': <strong>' . h(payment_method_label((string)$order['metodo_pagamento'])) . '</strong></p></div></div></div>
<div class="panel"><div class="label">' . h(tr('receipt.delivery')) . '</div><p class="value"><strong>' . h($order['nome_destinatario'] ?? $order['nome']) . '</strong></p><p class="value">' . h($order['morada'] ?? '') . '</p><p class="value">' . h(trim(($order['codigo_postal'] ?? '') . ' ' . ($order['cidade'] ?? ''))) . '</p><p class="value">' . h($order['pais'] ?? 'Portugal') . '</p><p class="value">' . h($order['telefone'] ?? '') . '</p></div>
<table><thead><tr><th>' . h(tr('receipt.product')) . '</th><th>' . h(tr('receipt.qty')) . '</th><th>' . h(tr('receipt.price')) . '</th><th style="text-align:right">' . h(tr('receipt.line')) . '</th></tr></thead><tbody>' . $rows . '<tr class="totals"><td colspan="3">' . h(tr('receipt.subtotal')) . '</td><td style="text-align:right">' . h(format_eur((float)$order['subtotal'])) . '</td></tr><tr class="totals"><td colspan="3">' . h(tr('receipt.vat')) . '</td><td style="text-align:right">' . h(format_eur((float)$order['iva_total'])) . '</td></tr><tr class="totals"><td colspan="3">' . h(tr('receipt.total')) . '</td><td style="text-align:right">' . h(format_eur((float)$order['total_final'])) . '</td></tr></tbody></table>
<div class="foot">' . h(tr('receipt.footer')) . '</div></body></html>';
}

function greenerry_order_invoice_pdf(mysqli $conn, int $orderId): string
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        return '';
    }
    require_once $autoload;
    if (!class_exists('\\Dompdf\\Dompdf')) {
        return '';
    }
    $html = greenerry_order_invoice_html($conn, $orderId);
    if ($html === '') {
        return '';
    }
    $pdf = new Dompdf\Dompdf(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);
    $pdf->loadHtml($html);
    $pdf->setPaper('A4', 'portrait');
    $pdf->render();
    return $pdf->output();
}

function send_order_confirmation_email(mysqli $conn, int $orderId): void
{
    $order = db_one_prepared(
        $conn,
        "SELECT e.idEncomenda, e.total_final, e.estado_pagamento, e.metodo_pagamento, e.criado_em, c.nome, c.email
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
        "SELECT ei.idProduto, ei.nome_produto, ei.quantidade, ei.total_linha, ei.preco_unitario, ei.idTamanho, c.nome AS artista_nome, t.etiqueta
         FROM encomenda_item ei
         JOIN cliente c ON c.idCliente = ei.idArtista
         LEFT JOIN tamanho t ON t.idTamanho = ei.idTamanho
         WHERE ei.idEncomenda = ?
         ORDER BY idEncomendaItem ASC",
        'i',
        [$orderId]
    );
    $lines = [];
    $cards = '';
    foreach ($items as $item) {
        $lines[] = '- ' . $item['nome_produto'] . ' x' . (int)$item['quantidade'] . ' - ' . format_eur((float)$item['total_linha']);
        $image = product_main_image($conn, (int)$item['idProduto']);
        $imageHtml = $image !== ''
            ? '<img src="' . h(absolute_site_url('assets/img/' . ltrim($image, '/'))) . '" alt="" width="76" height="76" style="width:76px;height:76px;object-fit:cover;border-radius:14px;display:block;background:#e5e7eb;">'
            : '<span style="display:block;width:76px;height:76px;border-radius:14px;background:#e5e7eb;"></span>';
        $detail = h($item['artista_nome']) . ' · ' . (int)$item['quantidade'] . 'x' . ($item['etiqueta'] ? ' · ' . h($item['etiqueta']) : '');
        $cards .= '<tr><td style="padding:12px 0;border-bottom:1px solid #e5e7eb;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr>'
            . '<td width="88" style="vertical-align:top;">' . $imageHtml . '</td>'
            . '<td style="vertical-align:middle;padding:0 12px;"><strong style="display:block;color:#111827;font-size:15px;margin-bottom:5px;">' . h($item['nome_produto']) . '</strong><span style="color:#64748b;font-size:13px;">' . $detail . '</span></td>'
            . '<td style="vertical-align:middle;text-align:right;color:#111827;font-weight:700;white-space:nowrap;">' . h(format_eur((float)$item['total_linha'])) . '</td>'
            . '</tr></table></td></tr>';
    }
    $receiptUrl = absolute_site_url('pages/receipt.php?id=' . $orderId);
    $lang = current_lang();
    $title = tr('email.order_subject', ['id' => (string)$orderId]);
    $intro = $lang === 'en'
        ? 'Your order was created and your invoice is attached.'
        : 'A tua encomenda foi criada e a fatura segue em anexo.';
    $html = greenerry_email_shell(
        $title,
        $intro,
        '<p style="margin:0 0 16px;color:#334155;font-size:15px;line-height:1.6;">' . h($lang === 'en' ? 'Hi ' : 'Olá ') . '<strong>' . h($order['nome'] ?? '') . '</strong>,</p>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0">' . $cards . '</table>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:18px;background:#f8faf9;border:1px solid #e2e8f0;border-radius:14px;"><tr><td style="padding:16px;color:#64748b;font-size:13px;">' . h($lang === 'en' ? 'Total' : 'Total') . '</td><td style="padding:16px;text-align:right;color:#111827;font-size:20px;font-weight:800;">' . h(format_eur((float)$order['total_final'])) . '</td></tr></table>'
        . '<p style="margin:18px 0 0;"><a href="' . h($receiptUrl) . '" style="display:inline-block;background:#111827;color:#ffffff;text-decoration:none;border-radius:999px;padding:12px 18px;font-weight:700;">' . h($lang === 'en' ? 'Open invoice' : 'Abrir fatura') . '</a></p>',
        $lang
    );
    $invoicePdf = greenerry_order_invoice_pdf($conn, $orderId);
    $attachments = $invoicePdf !== '' ? [[
        'content' => $invoicePdf,
        'filename' => 'fatura_' . $orderId . '.pdf',
        'mime' => 'application/pdf',
    ]] : [];

    greenerry_send_email(
        (string)$order['email'],
        $title,
        tr('email.order_body', [
            'name' => $order['nome'] ?? '',
            'id' => (string)$orderId,
            'items' => implode("\n", $lines),
            'total' => format_eur((float)$order['total_final']),
            'receipt' => $receiptUrl,
        ]),
        ['html' => $html, 'attachments' => $attachments]
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
        $subject = tr('email.artist_sale_subject');
        $body = tr('email.artist_sale_body', [
            'name' => $artist['nome'] ?? '',
            'id' => (string)$orderId,
            'items' => (string)($artist['itens'] ?? ''),
            'value' => format_eur((float)$artist['valor_artista']),
        ]);
        $html = greenerry_email_shell($subject, current_lang() === 'en' ? 'You made a sale on Greenerry.' : 'Fizeste uma venda na Greenerry.', '<p style="margin:0;color:#334155;font-size:15px;line-height:1.6;">' . nl2br(h($body)) . '</p>');
        greenerry_send_email(
            (string)$artist['email'],
            $subject,
            $body,
            ['html' => $html]
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
    $subject = $approved ? tr('email.product_approved_subject') : tr('email.product_rejected_subject');
    $body = tr($approved ? 'email.product_approved_body' : 'email.product_rejected_body', [
        'name' => $product['nome'] ?? '',
        'product' => $product['nomeProduto'] ?? '',
        'reason' => $reason !== '' ? $reason : '-',
        'link' => absolute_site_url('pages/produto.php?id=' . (int)$product['idProduto']),
    ]);
    $image = product_main_image($conn, (int)$product['idProduto']);
    $imageHtml = $image !== '' ? '<img src="' . h(absolute_site_url('assets/img/' . ltrim($image, '/'))) . '" alt="" style="width:100%;max-height:260px;object-fit:cover;border-radius:16px;margin-bottom:18px;background:#e5e7eb;">' : '';
    $html = greenerry_email_shell($subject, $approved ? (current_lang() === 'en' ? 'Your product is live.' : 'O teu produto foi aprovado.') : (current_lang() === 'en' ? 'Your product needs changes.' : 'O teu produto precisa de alterações.'), $imageHtml . '<p style="margin:0;color:#334155;font-size:15px;line-height:1.6;">' . nl2br(h($body)) . '</p>');
    greenerry_send_email(
        (string)$product['email'],
        $subject,
        $body,
        ['html' => $html]
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
    $subject = $approved ? tr('email.release_approved_subject') : tr('email.release_rejected_subject');
    $body = tr($approved ? 'email.release_approved_body' : 'email.release_rejected_body', [
        'name' => $release['nome'] ?? '',
        'release' => $release['titulo'] ?? '',
        'reason' => $reason !== '' ? $reason : '-',
        'link' => absolute_site_url('pages/release.php?id=' . (int)$release['idRelease']),
    ]);
    $html = greenerry_email_shell($subject, $approved ? (current_lang() === 'en' ? 'Your release is live.' : 'O teu lançamento foi aprovado.') : (current_lang() === 'en' ? 'Your release needs changes.' : 'O teu lançamento precisa de alterações.'), '<p style="margin:0;color:#334155;font-size:15px;line-height:1.6;">' . nl2br(h($body)) . '</p>');
    greenerry_send_email(
        (string)$release['email'],
        $subject,
        $body,
        ['html' => $html]
    );
}
