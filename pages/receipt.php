<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$orderId = (int)($_GET['id'] ?? 0);

if ($orderId <= 0) {
    header('Location: profile.php');
    exit;
}

$order = db_one(
    $conn,
    "SELECT e.*, c.nome, c.email, me.nome_destinatario, me.morada, me.cidade, me.codigo_postal, me.pais, me.telefone
     FROM encomenda e
     JOIN cliente c ON c.idCliente = e.idCliente
     LEFT JOIN morada_encomenda me ON me.idEncomenda = e.idEncomenda
     WHERE e.idEncomenda = {$orderId}
       AND e.idCliente = {$uid}
     LIMIT 1"
);

if (!$order) {
    header('Location: profile.php');
    exit;
}

$items = db_all(
    $conn,
    "SELECT nome_produto, quantidade, preco_unitario, subtotal_linha, total_linha
     FROM encomenda_item
     WHERE idEncomenda = {$orderId}
     ORDER BY idEncomendaItem ASC"
);

$payment = db_one($conn, "SELECT * FROM pagamento WHERE idEncomenda = {$orderId} LIMIT 1");

$html = '<!DOCTYPE html><html><head><meta charset="utf-8">
<style>
body{font-family:DejaVu Sans,Arial,sans-serif;font-size:13px;color:#102418;margin:0;padding:40px;background:#ffffff;}
.brand{font-size:30px;font-weight:700;letter-spacing:.04em;color:#163524;margin-bottom:4px;}
.sub{font-size:13px;color:#5f6f66;margin-bottom:24px;}
.panel{border:1px solid #d9e0dc;border-radius:18px;padding:18px;margin-bottom:22px;}
.row{display:table;width:100%;}
.col{display:table-cell;vertical-align:top;width:50%;}
.right{text-align:right;}
.label{font-size:10px;text-transform:uppercase;letter-spacing:.12em;color:#7f8b84;margin-bottom:6px;}
.value{font-size:13px;color:#24352d;margin:4px 0;}
table{width:100%;border-collapse:collapse;}
th{padding:12px 10px;background:#f3f6f4;color:#567062;font-size:10px;text-transform:uppercase;letter-spacing:.12em;text-align:left;}
td{padding:12px 10px;border-bottom:1px solid #e4e9e6;color:#1f3028;}
.totals td{font-weight:700;border-bottom:none;}
.foot{margin-top:24px;font-size:11px;color:#7f8b84;text-align:center;}
</style></head><body>
<div class="brand">GREENERRY</div>
<div class="sub">Recibo da encomenda #' . (int)$orderId . '</div>

<div class="panel">
  <div class="row">
    <div class="col">
      <div class="label">Cliente</div>
      <p class="value"><strong>' . h($order['nome']) . '</strong></p>
      <p class="value">' . h($order['email']) . '</p>
      ' . ($order['nif'] ? '<p class="value">NIF: ' . h($order['nif']) . '</p>' : '') . '
    </div>
    <div class="col right">
      <div class="label">Detalhes</div>
      <p class="value">Data: <strong>' . date('d/m/Y', strtotime($order['created_at'])) . '</strong></p>
      <p class="value">Estado: <strong>' . h(order_status_label($order['estado_encomenda'])) . '</strong></p>
      <p class="value">Pagamento: <strong>' . h(payment_method_label($order['metodo_pagamento'])) . '</strong></p>
    </div>
  </div>
</div>

<div class="panel">
  <div class="label">Entrega</div>
  <p class="value"><strong>' . h($order['nome_destinatario'] ?? $order['nome']) . '</strong></p>
  <p class="value">' . h($order['morada'] ?? '') . '</p>
  <p class="value">' . h(trim(($order['codigo_postal'] ?? '') . ' ' . ($order['cidade'] ?? ''))) . '</p>
  <p class="value">' . h($order['pais'] ?? 'Portugal') . '</p>
  <p class="value">' . h($order['telefone'] ?? '') . '</p>
</div>

<table>
  <thead>
    <tr>
      <th>Produto</th>
      <th>Qtd</th>
      <th>Preco</th>
      <th style="text-align:right">Linha</th>
    </tr>
  </thead>
  <tbody>';

foreach ($items as $item) {
    $html .= '<tr>
      <td>' . h($item['nome_produto']) . '</td>
      <td>' . (int)$item['quantidade'] . '</td>
      <td>' . h(format_eur((float)$item['preco_unitario'])) . '</td>
      <td style="text-align:right">' . h(format_eur((float)$item['total_linha'])) . '</td>
    </tr>';
}

$html .= '
    <tr class="totals">
      <td colspan="3">Subtotal</td>
      <td style="text-align:right">' . h(format_eur((float)$order['subtotal'])) . '</td>
    </tr>
    <tr class="totals">
      <td colspan="3">IVA</td>
      <td style="text-align:right">' . h(format_eur((float)$order['iva_total'])) . '</td>
    </tr>
    <tr class="totals">
      <td colspan="3">Total final</td>
      <td style="text-align:right">' . h(format_eur((float)$order['total_final'])) . '</td>
    </tr>
  </tbody>
</table>

<div class="foot">Greenerry receipt generated for school demonstration use.</div>
</body></html>';

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
    $pdf = new Dompdf\Dompdf(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);
    $pdf->loadHtml($html);
    $pdf->setPaper('A4', 'portrait');
    $pdf->render();
    $pdf->stream('recibo_' . $orderId . '.pdf', ['Attachment' => false]);
    exit;
}

echo $html;
