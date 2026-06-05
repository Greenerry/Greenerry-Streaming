<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$err = '';
$ok = '';
$orderId = 0;
// Country-specific rules keep checkout validation aligned between PHP and the browser.
$checkoutCountries = [
    'Portugal' => ['key' => 'country_portugal', 'postal' => '/^\d{4}-\d{3}$/', 'html' => '\d{4}-\d{3}', 'placeholder' => '1000-001', 'phone' => '+351 912345678'],
];
$recipient = '';
$address = '';
$city = '';
$postalCode = '';
$country = 'Portugal';
$phone = '';
$nif = '';
$paymentMethod = 'cartao';
$notes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $recipient = trim($_POST['nome_destinatario'] ?? '');
    $address = trim($_POST['morada'] ?? '');
    $city = trim($_POST['cidade'] ?? '');
    $postalCode = trim($_POST['codigo_postal'] ?? '');
    $country = trim($_POST['pais'] ?? 'Portugal');
    $phone = trim($_POST['telefone'] ?? '');
    $nif = trim($_POST['nif'] ?? '');
    $paymentMethod = $_POST['metodo'] ?? 'cartao';
    $notes = trim($_POST['observacoes'] ?? '');
    $cart = json_decode($_POST['cart_json'] ?? '[]', true);

    // The cart comes from localStorage, so the server validates every line again.
    $err = verify_csrf_request() ?? '';

    if (!$err && ($recipient === '' || mb_strlen($recipient) < 3)) {
        $err = tr('error.required_recipient');
    } elseif (!$err && (($nameErr = validate_nome($recipient)) !== null)) {
        $err = $nameErr;
    } elseif (!$err && ($address === '' || mb_strlen($address) < 5)) {
        $err = tr('error.short_address');
    } elseif (!$err && (($cityErr = validate_city($city)) !== null)) {
        $err = $cityErr;
    } elseif (!$err && !isset($checkoutCountries[$country])) {
        $err = tr('error.invalid_country');
    } elseif (!$err && !preg_match($checkoutCountries[$country]['postal'], $postalCode)) {
        $err = tr('error.invalid_postal');
    } elseif (!$err && $phone === '') {
        $err = tr('error.invalid_phone');
    } elseif (!$err && (($phoneErr = validate_phone($phone)) !== null)) {
        $err = $phoneErr;
    } elseif (!$err && (($nifErr = validate_nif($nif)) !== null)) {
        $err = $nifErr;
    } elseif (!$err && !in_array($paymentMethod, ['cartao', 'mbway', 'transferencia'], true)) {
        $err = tr('error.invalid_payment_method');
    } elseif (!$err && !$cart) {
        $err = tr('error.empty_cart');
    }

    $orderLines = [];
    $subtotal = 0.0;
    $ivaTotal = 0.0;
    $commissionTotal = 0.0;

    if (!$err) {
        // Re-check availability, ownership, stock, and prices before creating the order.
        foreach ($cart as $item) {
            $productId = (int)($item['id'] ?? 0);
            $quantity = max(1, (int)($item['qty'] ?? 1));
            $sizeId = (int)($item['sizeId'] ?? 0);

            if ($productId <= 0) {
                $err = tr('error.invalid_cart_product');
                break;
            }

            $product = db_one_prepared(
                $conn,
                "SELECT p.*, c.nome AS artist_name, cat.nomeCategoria
                 FROM produto p
                 JOIN cliente c ON c.idCliente = p.idCliente
                 JOIN categoria cat ON cat.idCategoria = p.idCategoria
                 WHERE p.idProduto = ?
                   AND p.estado = 'aprovado'
                   AND p.ativo = 1
                   AND c.estado = 'ativo'
                 LIMIT 1",
                'i',
                [$productId]
            );

            if (!$product) {
                $err = tr('error.product_unavailable');
                break;
            }

            if ((int)$product['idCliente'] === $uid) {
                $err = tr('error.own_product_purchase');
                break;
            }

            if ((int)$product['usa_tamanhos'] === 1) {
                $size = db_one_prepared(
                    $conn,
                    "SELECT pts.stock, t.etiqueta
                     FROM produto_tamanho_stock pts
                     JOIN tamanho t ON t.idTamanho = pts.idTamanho
                     WHERE pts.idProduto = ?
                       AND pts.idTamanho = ?
                       AND pts.ativo = 1
                     LIMIT 1",
                    'ii',
                    [$productId, $sizeId]
                );

                if (!$size) {
                    $err = tr('error.invalid_size_for_product', ['product' => $product['nomeProduto']]);
                    break;
                }

                if ((int)$size['stock'] < $quantity) {
                    $err = tr('error.insufficient_size_stock', ['product' => $product['nomeProduto'], 'size' => $size['etiqueta']]);
                    break;
                }
            } elseif ((int)$product['stock_total'] < $quantity) {
                $err = tr('error.insufficient_stock', ['product' => $product['nomeProduto']]);
                break;
            }

            $lineSubtotal = (float)$product['precoAtual'] * $quantity;
            $lineIva = $lineSubtotal * ((float)$product['iva_percentual'] / 100);
            $lineCommission = ($lineSubtotal + $lineIva) * ((float)$product['comissao_percentual'] / 100);
            $lineTotal = $lineSubtotal + $lineIva;

            $subtotal += $lineSubtotal;
            $ivaTotal += $lineIva;
            $commissionTotal += $lineCommission;

            $orderLines[] = [
                'product' => $product,
                'quantity' => $quantity,
                'sizeId' => $sizeId > 0 ? $sizeId : null,
                'sizeLabel' => isset($size['etiqueta']) ? (string)$size['etiqueta'] : '',
                'lineSubtotal' => $lineSubtotal,
                'lineIva' => $lineIva,
                'lineCommission' => $lineCommission,
                'lineTotal' => $lineTotal,
                'artistValue' => $lineTotal - $lineCommission,
            ];
        }
    }

    if (!$err && $orderLines) {
        $totalFinal = $subtotal + $ivaTotal;

        // Order, items, address, payment, and stock updates must succeed together.
        mysqli_begin_transaction($conn);

        try {
            $paymentState = $paymentMethod === 'transferencia' ? 'pendente' : 'pago';

            if (!db_prepared(
                $conn,
                "INSERT INTO encomenda (
                    idCliente,
                    subtotal,
                    iva_total,
                    comissao_total,
                    total_final,
                    estado_encomenda,
                    estado_pagamento,
                    metodo_pagamento,
                    nif,
                    observacoes
                ) VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'pendente',
                    ?,
                    ?,
                    ?,
                    ?
                )",
                'iddddssss',
                [
                    $uid,
                    $subtotal,
                    $ivaTotal,
                    $commissionTotal,
                    $totalFinal,
                    $paymentState,
                    $paymentMethod,
                    $nif !== '' ? $nif : null,
                    $notes !== '' ? $notes : null,
                ]
            )) {
                throw new RuntimeException('order insert failed');
            }

            $orderId = (int)mysqli_insert_id($conn);

            foreach ($orderLines as $lineIndex => $line) {
                $product = $line['product'];
                $orderLines[$lineIndex]['image'] = product_main_image($conn, (int)$product['idProduto']);

                if (!db_prepared(
                    $conn,
                    "INSERT INTO encomenda_item (
                        idEncomenda,
                        idProduto,
                        idArtista,
                        idTamanho,
                        nome_produto,
                        categoria_nome,
                        quantidade,
                        preco_unitario,
                        iva_percentual,
                        iva_valor,
                        comissao_percentual,
                        comissao_valor,
                        subtotal_linha,
                        total_linha,
                        valor_artista,
                        estado_item
                    ) VALUES (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'pendente'
                    )",
                    'iiiissidddddddd',
                    [
                        $orderId,
                        (int)$product['idProduto'],
                        (int)$product['idCliente'],
                        $line['sizeId'] ? (int)$line['sizeId'] : null,
                        (string)$product['nomeProduto'],
                        (string)$product['nomeCategoria'],
                        (int)$line['quantity'],
                        (float)$product['precoAtual'],
                        (float)$product['iva_percentual'],
                        (float)$line['lineIva'],
                        (float)$product['comissao_percentual'],
                        (float)$line['lineCommission'],
                        (float)$line['lineSubtotal'],
                        (float)$line['lineTotal'],
                        (float)$line['artistValue'],
                    ]
                )) {
                    throw new RuntimeException('order item insert failed');
                }

                // Stock is decremented only after the order item was stored.
                if (!db_prepared(
                    $conn,
                    "UPDATE produto
                     SET stock_total = GREATEST(stock_total - ?, 0)
                     WHERE idProduto = ?",
                    'ii',
                    [(int)$line['quantity'], (int)$product['idProduto']]
                )) {
                    throw new RuntimeException('product stock update failed');
                }

                if ($line['sizeId']) {
                    if (!db_prepared(
                        $conn,
                        "UPDATE produto_tamanho_stock
                         SET stock = GREATEST(stock - ?, 0)
                         WHERE idProduto = ?
                           AND idTamanho = ?",
                        'iii',
                        [(int)$line['quantity'], (int)$product['idProduto'], (int)$line['sizeId']]
                    )) {
                        throw new RuntimeException('size stock update failed');
                    }
                }
            }

            if (!db_prepared(
                $conn,
                "INSERT INTO morada_encomenda (
                    idEncomenda,
                    nome_destinatario,
                    morada,
                    cidade,
                    codigo_postal,
                    pais,
                    telefone
                ) VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )",
                'issssss',
                [$orderId, $recipient, $address, $city, $postalCode, $country === '' ? 'Portugal' : $country, $phone]
            )) {
                throw new RuntimeException('order address insert failed');
            }

            if (!db_prepared(
                $conn,
                "INSERT INTO pagamento (
                    idEncomenda,
                    valor,
                    metodo_pagamento,
                    estado_pagamento
                ) VALUES (
                    ?,
                    ?,
                    ?,
                    ?
                )",
                'idss',
                [$orderId, $totalFinal, $paymentMethod, $paymentState]
            )) {
                throw new RuntimeException('payment insert failed');
            }

            mysqli_commit($conn);
            send_order_confirmation_email($conn, $orderId);
            send_artist_sale_emails($conn, $orderId);
            $ok = tr('success.order_created');
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $err = tr('error.order_create');
        }
    }
}

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <div class="page-intro page-intro--simple">
      <h2 data-t="checkout_title">Finalizar compra</h2>
    </div>

    <?php if ($err): ?>
      <div class="alert alert-err"><?= h($err) ?></div>
    <?php endif; ?>

    <?php if ($ok): ?>
      <div class="card surface-card" data-clear-cart-on-load>
        <div class="card-body text-center">
          <span class="badge badge-blue" data-t="checkout_success_badge">Encomenda criada</span>
          <h3 class="mt4"><span data-t="checkout_order_number">Pedido</span> #<?= (int)$orderId ?></h3>
          <?php if (!empty($orderLines)): ?>
            <div class="checkout-success-items text-left mt4">
              <?php foreach ($orderLines as $line): ?>
                <div class="checkout-success-item">
                  <span class="checkout-success-thumb">
                    <?php if (!empty($line['image'])): ?><img src="<?= h(asset_url('img', $line['image'])) ?>" alt=""><?php endif; ?>
                  </span>
                  <span class="checkout-success-copy">
                    <strong><?= h($line['product']['nomeProduto']) ?></strong>
                    <small><?= h($line['product']['artist_name']) ?> · <?= h(count_label((int)$line['quantity'], 'unit')) ?><?= $line['sizeId'] ? ' · ' . h((string)($line['sizeLabel'] ?? '')) : '' ?></small>
                  </span>
                  <span><?= h(format_eur((float)$line['lineTotal'])) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <p data-t="checkout_success_text">A fatura já está disponível e o carrinho pode ser limpo em segurança.</p>
          <div class="hero-actions" style="justify-content:center;margin-top:18px;">
            <a href="receipt.php?id=<?= (int)$orderId ?>" class="btn btn-dark" target="_blank" data-t="checkout_open_receipt">Abrir fatura</a>
            <a href="profile.php" class="btn btn-ghost" data-t="checkout_view_profile">Ver perfil</a>
          </div>
        </div>
      </div>

    <?php else: ?>
      <div class="checkout-layout">
        <div class="card surface-card">
          <div class="card-body">
            <form method="post" class="stack-form" id="checkout-form">
              <?= csrf_input() ?>
              <input type="hidden" name="cart_json" id="cart_json">

              <h3 class="section-card-title" data-t="checkout_delivery">Entrega</h3>

              <div class="frow">
                <div class="fg">
                  <label class="flabel" for="nome_destinatario" data-t="checkout_recipient">Nome do destinatário</label>
                  <input id="nome_destinatario" type="text" name="nome_destinatario" class="finput" required minlength="3" maxlength="120" pattern="^[^\d]+$" data-name-only value="<?= h($recipient) ?>">
                </div>
                <div class="fg">
                  <label class="flabel" for="telefone" data-t="checkout_phone">Telefone</label>
                  <input id="telefone" type="tel" name="telefone" class="finput" required maxlength="20" placeholder="<?= h($checkoutCountries[$country]['phone'] ?? '+351 912345678') ?>" value="<?= h($phone) ?>">
                </div>
              </div>

              <div class="fg">
                <label class="flabel" for="morada" data-t="checkout_address">Morada</label>
                <input id="morada" type="text" name="morada" class="finput" required minlength="5" maxlength="180" value="<?= h($address) ?>">
              </div>

              <div class="frow">
                <div class="fg">
                  <label class="flabel" for="cidade" data-t="checkout_city">Cidade</label>
                  <input id="cidade" type="text" name="cidade" class="finput" required maxlength="120" data-name-only value="<?= h($city) ?>">
                </div>
                <div class="fg">
                  <label class="flabel" for="codigo_postal" data-t="checkout_postal">Código postal</label>
                  <input id="codigo_postal" type="text" name="codigo_postal" class="finput" required maxlength="12" placeholder="<?= h($checkoutCountries[$country]['placeholder'] ?? '1000-001') ?>" value="<?= h($postalCode) ?>">
                </div>
              </div>

              <div class="frow">
                <div class="fg">
                  <label class="flabel" for="pais" data-t="checkout_country">País</label>
                  <input id="pais" name="pais" class="finput" value="Portugal" readonly data-postal-placeholder="1000-001" data-postal-pattern="\d{4}-\d{3}" data-phone-placeholder="+351 912345678">
                </div>
                <div class="fg">
                  <label class="flabel" for="nif" id="tax-label" data-t="checkout_tax_nif">NIF</label>
                  <input id="nif" type="text" name="nif" class="finput" maxlength="30" value="<?= h($nif) ?>">
                </div>
              </div>

              <div class="fg">
                <label class="flabel" for="metodo" data-t="checkout_payment">Pagamento</label>
                <select id="metodo" name="metodo" class="finput">
                  <option value="cartao" data-t="checkout_card" <?= $paymentMethod === 'cartao' ? 'selected' : '' ?>>Cartao</option>
                  <option value="mbway" <?= $paymentMethod === 'mbway' ? 'selected' : '' ?>>MB Way</option>
                  <option value="transferencia" data-t="checkout_transfer" <?= $paymentMethod === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                </select>
                <p class="form-note" data-t="checkout_payment_demo_note">Pagamento demonstrativo: o método fica registado na encomenda, mas não há cobrança bancária real.</p>
              </div>

              <div class="fg">
                <label class="flabel" for="observacoes" data-t="checkout_notes">Observacoes</label>
                <textarea id="observacoes" name="observacoes" class="finput" data-tp="checkout_notes_placeholder" placeholder="Notas opcionais para a entrega ou pagamento."><?= h($notes) ?></textarea>
              </div>

              <button type="submit" name="place_order" class="btn btn-dark btn-full" data-t="checkout_confirm">Confirmar encomenda</button>
            </form>
          </div>
        </div>

        <div class="card surface-card">
          <div class="card-body">
            <h3 class="section-card-title" data-t="checkout_summary">Resumo</h3>
            <div id="checkout-items" class="simple-list"></div>
            <div class="divider"></div>
            <div class="simple-list">
              <div class="simple-list-item">
                <strong data-t="cart_subtotal">Subtotal</strong>
                <span id="checkout-subtotal">0,00 €</span>
              </div>
              <div class="simple-list-item">
                <strong data-t="checkout_vat">IVA</strong>
                <span id="checkout-iva">0,00 €</span>
              </div>
              <div class="simple-list-item">
                <strong data-t="checkout_final_total">Total final</strong>
                <span id="checkout-total">0,00 €</span>
              </div>
            </div>
          </div>
        </div>
      </div>

    <?php endif; ?>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
