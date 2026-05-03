<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$err = '';
$ok = '';
$orderId = 0;

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

    $err = verify_csrf_request() ?? '';

    if (!$err && ($recipient === '' || mb_strlen($recipient) < 3)) {
        $err = tr('error.required_recipient');
    } elseif (!$err && ($address === '' || mb_strlen($address) < 5)) {
        $err = tr('error.short_address');
    } elseif (!$err && ($city === '' || mb_strlen($city) < 2)) {
        $err = tr('error.required_city');
    } elseif (!$err && !preg_match('/^\d{4}-\d{3}$/', $postalCode)) {
        $err = tr('error.invalid_postal');
    } elseif (!$err && !preg_match('/^(\+351\s?)?9\d{8}$/', $phone)) {
        $err = tr('error.invalid_phone');
    } elseif (!$err && $nif !== '' && !preg_match('/^\d{9}$/', $nif)) {
        $err = tr('error.invalid_nif');
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
        foreach ($cart as $item) {
            $productId = (int)($item['id'] ?? 0);
            $quantity = max(1, (int)($item['qty'] ?? 1));
            $sizeId = (int)($item['sizeId'] ?? 0);

            if ($productId <= 0) {
                $err = tr('error.invalid_cart_product');
                break;
            }

            $product = db_one(
                $conn,
                "SELECT p.*, c.nome AS artist_name, cat.nomeCategoria
                 FROM produto p
                 JOIN cliente c ON c.idCliente = p.idCliente
                 JOIN categoria cat ON cat.idCategoria = p.idCategoria
                 WHERE p.idProduto = {$productId}
                   AND p.estado = 'aprovado'
                   AND p.ativo = 1
                 LIMIT 1"
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
                $size = db_one(
                    $conn,
                    "SELECT pts.stock, t.etiqueta
                     FROM produto_tamanho_stock pts
                     JOIN tamanho t ON t.idTamanho = pts.idTamanho
                     WHERE pts.idProduto = {$productId}
                       AND pts.idTamanho = {$sizeId}
                       AND pts.ativo = 1
                     LIMIT 1"
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

        mysqli_begin_transaction($conn);

        try {
            $nifSafe = db_escape($conn, $nif);
            $notesSafe = db_escape($conn, $notes);
            $paymentSafe = db_escape($conn, $paymentMethod);

            mysqli_query(
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
                    {$uid},
                    {$subtotal},
                    {$ivaTotal},
                    {$commissionTotal},
                    {$totalFinal},
                    'pendente',
                    'pago',
                    '{$paymentSafe}',
                    " . ($nif !== '' ? "'{$nifSafe}'" : "NULL") . ",
                    " . ($notes !== '' ? "'{$notesSafe}'" : "NULL") . "
                )"
            );

            $orderId = (int)mysqli_insert_id($conn);

            foreach ($orderLines as $line) {
                $product = $line['product'];
                $productName = db_escape($conn, $product['nomeProduto']);
                $categoryName = db_escape($conn, $product['nomeCategoria']);

                mysqli_query(
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
                        {$orderId},
                        " . (int)$product['idProduto'] . ",
                        " . (int)$product['idCliente'] . ",
                        " . ($line['sizeId'] ? (int)$line['sizeId'] : 'NULL') . ",
                        '{$productName}',
                        '{$categoryName}',
                        " . (int)$line['quantity'] . ",
                        " . (float)$product['precoAtual'] . ",
                        " . (float)$product['iva_percentual'] . ",
                        {$line['lineIva']},
                        " . (float)$product['comissao_percentual'] . ",
                        {$line['lineCommission']},
                        {$line['lineSubtotal']},
                        {$line['lineTotal']},
                        {$line['artistValue']},
                        'pendente'
                    )"
                );

                mysqli_query(
                    $conn,
                    "UPDATE produto
                     SET stock_total = GREATEST(stock_total - " . (int)$line['quantity'] . ", 0)
                     WHERE idProduto = " . (int)$product['idProduto']
                );

                if ($line['sizeId']) {
                    mysqli_query(
                        $conn,
                        "UPDATE produto_tamanho_stock
                         SET stock = GREATEST(stock - " . (int)$line['quantity'] . ", 0)
                         WHERE idProduto = " . (int)$product['idProduto'] . "
                           AND idTamanho = " . (int)$line['sizeId']
                    );
                }
            }

            $recipientSafe = db_escape($conn, $recipient);
            $addressSafe = db_escape($conn, $address);
            $citySafe = db_escape($conn, $city);
            $postalSafe = db_escape($conn, $postalCode);
            $countrySafe = db_escape($conn, $country === '' ? 'Portugal' : $country);
            $phoneSafe = db_escape($conn, $phone);

            mysqli_query(
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
                    {$orderId},
                    '{$recipientSafe}',
                    '{$addressSafe}',
                    '{$citySafe}',
                    '{$postalSafe}',
                    '{$countrySafe}',
                    '{$phoneSafe}'
                )"
            );

            mysqli_query(
                $conn,
                "INSERT INTO pagamento (
                    idEncomenda,
                    valor,
                    metodo_pagamento,
                    estado_pagamento
                ) VALUES (
                    {$orderId},
                    {$totalFinal},
                    '{$paymentSafe}',
                    'pago'
                )"
            );

            mysqli_commit($conn);
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
    <div class="page-intro">
      <span class="slabel" data-t="checkout_label">Checkout</span>
      <h2 data-t="checkout_title">Finalizar encomenda</h2>
      <p data-t="checkout_intro">Confirma a morada, o pagamento e o resumo dos artigos antes de concluir.</p>
    </div>

    <?php if ($err): ?>
      <div class="alert alert-err"><?= h($err) ?></div>
    <?php endif; ?>

    <?php if ($ok): ?>
      <div class="card surface-card">
        <div class="card-body text-center">
          <span class="badge badge-blue" data-t="checkout_success_badge">Encomenda criada</span>
          <h3 class="mt4"><span data-t="checkout_order_number">Pedido</span> #<?= (int)$orderId ?></h3>
          <p data-t="checkout_success_text">O recibo ja esta disponivel e o carrinho pode ser limpo em seguranca.</p>
          <div class="hero-actions" style="justify-content:center;margin-top:18px;">
            <a href="receipt.php?id=<?= (int)$orderId ?>" class="btn btn-dark" target="_blank" data-t="checkout_open_receipt">Abrir recibo</a>
            <a href="profile.php" class="btn btn-ghost" data-t="checkout_view_profile">Ver perfil</a>
          </div>
        </div>
      </div>

      <script>
      document.addEventListener('DOMContentLoaded', () => {
        localStorage.setItem('g_cart', '[]');
        if (typeof updateCartBadgeGlobal === 'function') {
          updateCartBadgeGlobal();
        }
      });
      </script>
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
                  <label class="flabel" for="nome_destinatario" data-t="checkout_recipient">Nome do destinatario</label>
                  <input id="nome_destinatario" type="text" name="nome_destinatario" class="finput" required>
                </div>
                <div class="fg">
                  <label class="flabel" for="telefone" data-t="checkout_phone">Telefone</label>
                  <input id="telefone" type="tel" name="telefone" class="finput" required placeholder="+351 912345678">
                </div>
              </div>

              <div class="fg">
                <label class="flabel" for="morada" data-t="checkout_address">Morada</label>
                <input id="morada" type="text" name="morada" class="finput" required>
              </div>

              <div class="frow">
                <div class="fg">
                  <label class="flabel" for="cidade" data-t="checkout_city">Cidade</label>
                  <input id="cidade" type="text" name="cidade" class="finput" required>
                </div>
                <div class="fg">
                  <label class="flabel" for="codigo_postal" data-t="checkout_postal">Codigo postal</label>
                  <input id="codigo_postal" type="text" name="codigo_postal" class="finput" required placeholder="1000-001">
                </div>
              </div>

              <div class="frow">
                <div class="fg">
                  <label class="flabel" for="pais" data-t="checkout_country">Pais</label>
                  <input id="pais" type="text" name="pais" class="finput" value="Portugal">
                </div>
                <div class="fg">
                  <label class="flabel" for="nif">NIF</label>
                  <input id="nif" type="text" name="nif" class="finput" maxlength="9">
                </div>
              </div>

              <div class="fg">
                <label class="flabel" for="metodo" data-t="checkout_payment">Pagamento</label>
                <select id="metodo" name="metodo" class="finput">
                  <option value="cartao" data-t="checkout_card">Cartao</option>
                  <option value="mbway">MB Way</option>
                  <option value="transferencia" data-t="checkout_transfer">Transferencia</option>
                </select>
              </div>

              <div class="fg">
                <label class="flabel" for="observacoes" data-t="checkout_notes">Observacoes</label>
                <textarea id="observacoes" name="observacoes" class="finput" data-tp="checkout_notes_placeholder" placeholder="Notas opcionais para a entrega ou pagamento."></textarea>
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
                <span id="checkout-subtotal">0,00 EUR</span>
              </div>
              <div class="simple-list-item">
                <strong data-t="checkout_vat">IVA</strong>
                <span id="checkout-iva">0,00 EUR</span>
              </div>
              <div class="simple-list-item">
                <strong data-t="checkout_final_total">Total final</strong>
                <span id="checkout-total">0,00 EUR</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <script>
      document.addEventListener('DOMContentLoaded', () => {
        const cart = JSON.parse(localStorage.getItem('g_cart') || '[]');
        const items = document.getElementById('checkout-items');
        const subtotalEl = document.getElementById('checkout-subtotal');
        const ivaEl = document.getElementById('checkout-iva');
        const totalEl = document.getElementById('checkout-total');
        const cartJson = document.getElementById('cart_json');

        cartJson.value = JSON.stringify(cart);

        const renderEmptyCart = () => {
          items.innerHTML = `<p>${document.documentElement.lang === 'en' ? 'The cart is empty.' : 'O carrinho esta vazio.'}</p>`;
        };

        if (!cart.length) {
          renderEmptyCart();
          window.addEventListener('greenerry:langchange', renderEmptyCart);
          return;
        }

        let subtotal = 0;

        items.innerHTML = cart.map((item) => {
          const qty = Number(item.qty || 1);
          const price = Number(item.price || 0);
          const lineTotal = qty * price;
          subtotal += lineTotal;

          return `
            <div class="simple-list-item">
              <div>
                <strong>${item.name || ''}</strong>
                <p>${qty} x ${price.toFixed(2).replace('.', ',')} EUR</p>
              </div>
              <span>${lineTotal.toFixed(2).replace('.', ',')} EUR</span>
            </div>
          `;
        }).join('');

        const iva = subtotal * 0.23;
        const total = subtotal + iva;

        subtotalEl.textContent = subtotal.toFixed(2).replace('.', ',') + ' EUR';
        ivaEl.textContent = iva.toFixed(2).replace('.', ',') + ' EUR';
        totalEl.textContent = total.toFixed(2).replace('.', ',') + ' EUR';
      });
      </script>
    <?php endif; ?>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
