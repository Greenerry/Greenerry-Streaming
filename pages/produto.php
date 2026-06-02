<?php
require_once '../includes/config.php';

$productId = (int)($_GET['id'] ?? 0);
if ($productId <= 0) {
    header('Location: shop.php');
    exit;
}

$product = db_one(
    $conn,
    "SELECT p.*, cat.nomeCategoria, c.nome AS artista_nome, c.idCliente AS artist_id
     FROM produto p
     JOIN categoria cat ON cat.idCategoria = p.idCategoria
     JOIN cliente c ON c.idCliente = p.idCliente
     WHERE p.idProduto = {$productId}
       AND p.estado = 'aprovado'
       AND p.ativo = 1
       AND c.estado = 'ativo'
     LIMIT 1"
);

if (!$product) {
    header('Location: shop.php');
    exit;
}

$viewerId = current_user_id();
if ($viewerId > 0 && !active_user_session($conn)) {
    end_user_session_only();
    $viewerId = 0;
}
$isOwnProduct = $viewerId > 0 && $viewerId === (int)$product['artist_id'];
$reviewOk = '';
$reviewErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if ($viewerId <= 0) {
        $reviewErr = tr('error.api_unauthenticated');
    } else {
        $reviewErr = verify_csrf_request() ?? '';
    }
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim((string)($_POST['comment'] ?? ''));
    $eligibleOrder = null;

    if (!$reviewErr && ($rating < 1 || $rating > 5)) {
        $reviewErr = current_lang() === 'en' ? 'Choose a rating from 1 to 5.' : 'Escolhe uma avaliação de 1 a 5.';
    }

    if (!$reviewErr) {
        $eligibleOrder = db_one_prepared(
            $conn,
            "SELECT e.idEncomenda
             FROM encomenda e
             JOIN encomenda_item ei ON ei.idEncomenda = e.idEncomenda
             WHERE e.idCliente = ?
               AND ei.idProduto = ?
               AND e.estado_pagamento = 'pago'
               AND ei.estado_item IN ('enviado', 'entregue')
             ORDER BY e.criado_em DESC
             LIMIT 1",
            'ii',
            [$viewerId, $productId]
        );
        if (!$eligibleOrder) {
            $reviewErr = current_lang() === 'en'
                ? 'Only customers who bought this product can review it.'
                : 'Só clientes que compraram este produto podem avaliá-lo.';
        }
    }

    if (!$reviewErr) {
        $existingReview = db_one_prepared(
            $conn,
            "SELECT idReview FROM produto_review WHERE idProduto = ? AND idCliente = ? LIMIT 1",
            'ii',
            [$productId, $viewerId]
        );
        if ($existingReview) {
            $reviewErr = current_lang() === 'en'
                ? 'You already reviewed this product.'
                : 'Já avaliaste este produto.';
        }
    }

    if (!$reviewErr && $eligibleOrder) {
        db_prepared(
            $conn,
            "INSERT INTO produto_review (idProduto, idCliente, idEncomenda, rating, comentario)
             VALUES (?, ?, ?, ?, ?)",
            'iiiis',
            [$productId, $viewerId, (int)$eligibleOrder['idEncomenda'], $rating, $comment]
        );
        $reviewOk = current_lang() === 'en' ? 'Review published.' : 'Avaliação publicada.';
    }
}

$sizes = db_all(
    $conn,
    "SELECT pts.idTamanho, pts.stock, t.etiqueta
     FROM produto_tamanho_stock pts
     JOIN tamanho t ON t.idTamanho = pts.idTamanho
     WHERE pts.idProduto = {$productId}
       AND pts.ativo = 1
     ORDER BY t.ordem ASC"
);

$relatedProducts = db_all(
    $conn,
    "SELECT p.idProduto, p.nomeProduto, p.precoAtual
     FROM produto p
     JOIN cliente c ON c.idCliente = p.idCliente
     WHERE p.idCategoria = " . (int)$product['idCategoria'] . "
       AND p.idProduto != {$productId}
       AND p.estado = 'aprovado'
       AND p.ativo = 1
       AND c.estado = 'ativo'
     ORDER BY p.criado_em DESC
     LIMIT 3"
);

$reviewStats = db_one_prepared(
    $conn,
    "SELECT COUNT(*) AS total_reviews, COALESCE(AVG(rating), 0) AS avg_rating
     FROM produto_review
     WHERE idProduto = ?",
    'i',
    [$productId]
) ?: ['total_reviews' => 0, 'avg_rating' => 0];
$reviews = db_all_prepared(
    $conn,
    "SELECT pr.*, c.nome, c.foto
     FROM produto_review pr
     JOIN cliente c ON c.idCliente = pr.idCliente
     WHERE pr.idProduto = ?
     ORDER BY pr.criado_em DESC",
    'i',
    [$productId]
);
$canReview = false;
if ($viewerId > 0 && !$isOwnProduct) {
    $canReview = (bool)db_one_prepared(
        $conn,
        "SELECT e.idEncomenda
         FROM encomenda e
         JOIN encomenda_item ei ON ei.idEncomenda = e.idEncomenda
         LEFT JOIN produto_review pr ON pr.idProduto = ei.idProduto AND pr.idCliente = e.idCliente
         WHERE e.idCliente = ?
           AND ei.idProduto = ?
           AND e.estado_pagamento = 'pago'
           AND ei.estado_item IN ('enviado', 'entregue')
           AND pr.idReview IS NULL
         LIMIT 1",
        'ii',
        [$viewerId, $productId]
    );
}

include '../includes/header.php';

$sizeSummary = array_map(static function ($size) {
    return $size['etiqueta'];
}, $sizes);
$productImages = product_images($conn, $productId);
$mainImage = $productImages[0] ?? '';
$productMediaCloud = [];
foreach ($productImages as $image) {
    $url = asset_url('img', $image);
    if ($url !== '') {
        $productMediaCloud[$url] = [
            'src' => $url,
            'label' => (string)$product['nomeProduto'],
            'type' => 'store',
        ];
    }
}
foreach ($relatedProducts as $related) {
    $image = asset_url('img', product_main_image($conn, (int)$related['idProduto']));
    if ($image !== '') {
        $productMediaCloud[$image] = [
            'src' => $image,
            'label' => (string)$related['nomeProduto'],
            'type' => 'store',
        ];
    }
}
$productMediaCloud = array_values(array_slice($productMediaCloud, 0, 12));
?>

<section class="content-shell content-shell--cloud content-shell--catalog-cloud">
  <div class="section-media-cloud section-media-cloud--catalog" data-media-cloud='<?= h(json_encode($productMediaCloud, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>' aria-hidden="true"></div>
  <div class="wrap">
    <a href="shop.php" class="auth-link" data-t="product_back">Voltar a loja</a>

    <div class="product-hero <?= $sizes ? 'product-hero--sized' : '' ?>">
      <div class="card surface-card product-media-card">
        <div class="card-body">
          <div class="product-gallery" data-gallery-images='<?= h(json_encode(array_map(static fn($image) => asset_url('img', $image), $productImages), JSON_UNESCAPED_SLASHES)) ?>'>
            <div class="cover product-cover">
              <?php if ($mainImage): ?>
                <img src="<?= h(asset_url('img', $mainImage)) ?>" alt="<?= h($product['nomeProduto']) ?>" id="product-main-image" class="product-gallery-main">
              <?php endif; ?>
              <?php if (count($productImages) > 1): ?>
                <button type="button" class="product-gallery-arrow product-gallery-arrow--overlay product-gallery-arrow--prev" data-gallery-step="-1" aria-label="<?= h(current_lang() === 'en' ? 'Previous image' : 'Imagem anterior') ?>">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <button type="button" class="product-gallery-arrow product-gallery-arrow--overlay product-gallery-arrow--next" data-gallery-step="1" aria-label="<?= h(current_lang() === 'en' ? 'Next image' : 'Imagem seguinte') ?>">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="card surface-card product-info-card">
        <div class="card-body">
          <span class="badge badge-dark" data-product-category="<?= h($product['nomeCategoria']) ?>"><?= h(category_label($product['nomeCategoria'])) ?></span>
          <h1 class="product-title"><?= h($product['nomeProduto']) ?></h1>
          <p class="product-copy"><span data-t="product_official_merch">Merch oficial do artista</span> <a href="artist.php?id=<?= (int)$product['artist_id'] ?>" class="auth-link"><?= h($product['artista_nome']) ?></a>.</p>

          <div class="product-price-row">
            <strong><?= h(format_eur((float)$product['precoAtual'])) ?></strong>
            <span><span data-t="product_vat_add">IVA a adicionar</span>: <?= number_format((float)$product['iva_percentual'], 2, ',', '.') ?>%</span>
          </div>

          <?php if (!empty($product['descricaoProduto'])): ?>
            <div class="message-reply-box">
              <span class="slabel" data-t="product_description">Descrição</span>
              <p><?= nl2br(h($product['descricaoProduto'])) ?></p>
            </div>
          <?php endif; ?>

          <div
            class="product-buy-box"
            data-product-id="<?= (int)$product['idProduto'] ?>"
            data-product-name="<?= h($product['nomeProduto']) ?>"
            data-product-price="<?= (float)$product['precoAtual'] ?>"
            data-product-img="<?= h($mainImage) ?>"
            data-product-stock="<?= (int)$product['stock_total'] ?>"
            data-base-stock="<?= (int)$product['stock_total'] ?>"
            data-own-product="<?= $isOwnProduct ? '1' : '0' ?>"
          >
            <?php if ($sizes): ?>
              <div class="fg">
                <label class="flabel" for="product-size" data-t="product_size">Tamanho</label>
                <select id="product-size" class="finput">
                  <option value="" data-t="product_select_size">Seleciona um tamanho</option>
                  <?php foreach ($sizes as $size): ?>
                    <option value="<?= (int)$size['idTamanho'] ?>" data-stock="<?= (int)$size['stock'] ?>" <?= (int)$size['stock'] <= 0 ? 'disabled' : '' ?>>
                      <?= h($size['etiqueta']) ?><?php if ((int)$size['stock'] <= 0): ?> - <?= current_lang() === 'en' ? 'Out of stock' : 'Sem stock' ?><?php endif; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>

            <div class="product-buy-row">
              <div class="qty-picker <?= $isOwnProduct ? 'qty-picker--disabled' : '' ?>">
                <button type="button" class="btn btn-ghost btn-sm" onclick="changeProductQty(-1, this)" <?= $isOwnProduct ? 'disabled' : '' ?>>-</button>
                <span class="product-qty">1</span>
                <button type="button" class="btn btn-ghost btn-sm" onclick="changeProductQty(1, this)" <?= $isOwnProduct ? 'disabled' : '' ?>>+</button>
              </div>

              <button type="button" class="btn btn-dark" id="product-add-btn" onclick="handleProductAddToCart()" <?= $isOwnProduct ? 'disabled aria-disabled="true"' : '' ?>>
                <span data-t="<?= $isOwnProduct ? 'product_own' : 'product_add_to_cart' ?>"><?= $isOwnProduct ? 'Produto teu' : 'Adicionar ao carrinho' ?></span>
              </button>
            </div>

            <p class="product-stock-note" id="product-stock-note">
              <?php if ($isOwnProduct): ?>
                <?= current_lang() === 'en' ? 'You cannot buy your own product.' : 'Não podes comprar o teu próprio produto.' ?>
              <?php elseif ($sizes): ?>
                <?= current_lang() === 'en' ? 'Select a size to see stock.' : 'Seleciona um tamanho para ver o stock.' ?>
              <?php elseif ((int)$product['stock_total'] > 0): ?>
                <?= current_lang() === 'en' ? 'In stock' : 'Em stock' ?>: <?= h(count_label((int)$product['stock_total'], 'unit')) ?>
              <?php else: ?>
                <?= current_lang() === 'en' ? 'Out of stock' : 'Sem stock' ?>
              <?php endif; ?>
            </p>
          </div>
        </div>
      </div>
    </div>

    <section class="product-reviews-section">
      <div class="page-intro mt8">
        <span class="slabel" data-t="product_reviews_label">Avaliações</span>
        <h2 data-t="product_reviews_title">Reviews do produto</h2>
        <p>
          <strong><?= number_format((float)$reviewStats['avg_rating'], 1, ',', '.') ?>/5</strong>
          · <?= h(count_label((int)$reviewStats['total_reviews'], 'record')) ?>
        </p>
      </div>

      <?php if ($reviewOk): ?><div class="alert alert-ok"><?= h($reviewOk) ?></div><?php endif; ?>
      <?php if ($reviewErr): ?><div class="alert alert-err"><?= h($reviewErr) ?></div><?php endif; ?>

      <?php if ($canReview): ?>
        <form method="post" class="card surface-card product-review-form">
          <div class="card-body">
            <?= csrf_input() ?>
            <div class="frow">
              <div class="fg">
                <label class="flabel" for="rating" data-t="product_review_rating">Avaliação</label>
                <select id="rating" name="rating" class="finput" required>
                  <option value="5">5 - <?= current_lang() === 'en' ? 'Excellent' : 'Excelente' ?></option>
                  <option value="4">4 - <?= current_lang() === 'en' ? 'Good' : 'Bom' ?></option>
                  <option value="3">3 - <?= current_lang() === 'en' ? 'Okay' : 'Razoável' ?></option>
                  <option value="2">2</option>
                  <option value="1">1</option>
                </select>
              </div>
              <div class="fg">
                <label class="flabel" for="comment" data-t="product_review_comment">Comentário</label>
                <textarea id="comment" name="comment" class="finput" maxlength="1200" data-tp="product_review_placeholder" placeholder="Partilha a tua opinião sobre o produto"></textarea>
              </div>
            </div>
            <button type="submit" name="submit_review" value="1" class="btn btn-dark" data-t="product_review_submit">Publicar review</button>
          </div>
        </form>
      <?php elseif ($viewerId > 0 && !$isOwnProduct): ?>
        <p class="color-text3" data-t="product_review_purchase_only">Só podes avaliar depois de comprares este produto, e apenas uma vez.</p>
      <?php endif; ?>

      <?php if ($reviews): ?>
        <div class="review-list">
          <?php foreach ($reviews as $review): ?>
            <article class="message-thread-item review-item">
              <div class="between">
                <div class="order-product-info">
                  <div class="avatar review-avatar">
                    <?php if (!empty($review['foto'])): ?><img src="<?= h(asset_url('img', $review['foto'])) ?>" alt=""><?php endif; ?>
                  </div>
                  <div>
                    <strong><?= h($review['nome']) ?></strong>
                    <p><?= date('d/m/Y', strtotime($review['criado_em'])) ?></p>
                  </div>
                </div>
                <span class="badge badge-dark"><?= (int)$review['rating'] ?>/5</span>
              </div>
              <?php if (!empty($review['comentario'])): ?>
                <p><?= nl2br(h($review['comentario'])) ?></p>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="color-text3" data-t="product_reviews_empty">Ainda não existem reviews.</p>
      <?php endif; ?>
    </section>

    <?php if ($relatedProducts): ?>
      <div class="page-intro mt8">
        <span class="slabel" data-t="product_more_merch">Mais merch</span>
        <h2 data-t="product_related">Produtos relacionados</h2>
      </div>

      <div class="grid stg">
        <?php foreach ($relatedProducts as $related): ?>
          <?php $relatedImage = product_main_image($conn, (int)$related['idProduto']); ?>
          <a href="produto.php?id=<?= (int)$related['idProduto'] ?>" class="mcard">
            <div class="cover">
              <?php if ($relatedImage): ?>
                <img src="<?= h(asset_url('img', $relatedImage)) ?>" alt="<?= h($related['nomeProduto']) ?>">
              <?php endif; ?>
            </div>
            <div class="meta">
              <h4><?= h($related['nomeProduto']) ?></h4>
              <div class="price"><?= h(format_eur((float)$related['precoAtual'])) ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>


<?php include '../includes/footer.php'; ?>
