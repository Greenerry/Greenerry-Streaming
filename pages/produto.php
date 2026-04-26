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
     LIMIT 1"
);

if (!$product) {
    header('Location: shop.php');
    exit;
}

$viewerId = current_user_id();
$isOwnProduct = $viewerId > 0 && $viewerId === (int)$product['artist_id'];

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
    "SELECT p.idProduto, p.nomeProduto, p.precoAtual, p.imagem
     FROM produto p
     WHERE p.idCategoria = " . (int)$product['idCategoria'] . "
       AND p.idProduto != {$productId}
       AND p.estado = 'aprovado'
       AND p.ativo = 1
     ORDER BY p.created_at DESC
     LIMIT 3"
);

include '../includes/header.php';

$sizeSummary = array_map(static function ($size) {
    return $size['etiqueta'];
}, $sizes);
?>

<section class="content-shell">
  <div class="wrap">
    <a href="shop.php" class="auth-link">Voltar a loja</a>

    <div class="product-hero">
      <div class="card surface-card product-media-card">
        <div class="card-body">
          <div class="cover product-cover">
            <?php if (!empty($product['imagem'])): ?>
              <img src="<?= h(asset_url('img', $product['imagem'])) ?>" alt="<?= h($product['nomeProduto']) ?>">
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="card surface-card product-info-card">
        <div class="card-body">
          <span class="badge badge-dark"><?= h($product['nomeCategoria']) ?></span>
          <h1 class="product-title"><?= h($product['nomeProduto']) ?></h1>
          <p class="product-copy">Merch oficial do artista <a href="artist.php?id=<?= (int)$product['artist_id'] ?>" class="auth-link"><?= h($product['artista_nome']) ?></a>.</p>

          <div class="product-price-row">
            <strong><?= h(format_eur((float)$product['precoAtual'])) ?></strong>
            <span>IVA a adicionar: <?= number_format((float)$product['iva_percentual'], 2, ',', '.') ?>%</span>
          </div>

          <?php if (!empty($product['descricaoProduto'])): ?>
            <div class="message-reply-box">
              <span class="slabel">Descricao</span>
              <p><?= nl2br(h($product['descricaoProduto'])) ?></p>
            </div>
          <?php endif; ?>

          <?php if ($sizes): ?>
            <div class="message-reply-box">
              <span class="slabel">Tamanhos disponiveis</span>
              <p><?= h(implode(' - ', $sizeSummary)) ?></p>
            </div>
          <?php endif; ?>

          <div
            class="product-buy-box"
            data-product-id="<?= (int)$product['idProduto'] ?>"
            data-product-name="<?= h($product['nomeProduto']) ?>"
            data-product-price="<?= (float)$product['precoAtual'] ?>"
            data-product-img="<?= h($product['imagem'] ?? '') ?>"
            data-product-stock="<?= (int)$product['stock_total'] ?>"
            data-base-stock="<?= (int)$product['stock_total'] ?>"
            data-own-product="<?= $isOwnProduct ? '1' : '0' ?>"
          >
            <?php if ($sizes): ?>
              <div class="fg">
                <label class="flabel" for="product-size">Tamanho</label>
                <select id="product-size" class="finput">
                  <option value="">Seleciona um tamanho</option>
                  <?php foreach ($sizes as $size): ?>
                    <option value="<?= (int)$size['idTamanho'] ?>" data-stock="<?= (int)$size['stock'] ?>" <?= (int)$size['stock'] <= 0 ? 'disabled' : '' ?>>
                      <?= h($size['etiqueta']) ?><?php if ((int)$size['stock'] <= 0): ?> - Sem stock<?php endif; ?>
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
                <?= $isOwnProduct ? 'Produto teu' : 'Adicionar ao carrinho' ?>
              </button>
            </div>

            <p class="product-stock-note" id="product-stock-note">
              <?php if ($isOwnProduct): ?>
                Nao podes comprar o teu proprio produto.
              <?php elseif ($sizes): ?>
                Seleciona um tamanho para ver o stock.
              <?php elseif ((int)$product['stock_total'] > 0): ?>
                Em stock: <?= (int)$product['stock_total'] ?> unidade(s)
              <?php else: ?>
                Sem stock
              <?php endif; ?>
            </p>
          </div>
        </div>
      </div>
    </div>

    <?php if ($relatedProducts): ?>
      <div class="page-intro mt8">
        <span class="slabel">Mais merch</span>
        <h2>Produtos relacionados</h2>
      </div>

      <div class="grid stg">
        <?php foreach ($relatedProducts as $related): ?>
          <a href="produto.php?id=<?= (int)$related['idProduto'] ?>" class="mcard">
            <div class="cover">
              <?php if (!empty($related['imagem'])): ?>
                <img src="<?= h(asset_url('img', $related['imagem'])) ?>" alt="<?= h($related['nomeProduto']) ?>">
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

<script>
document.addEventListener('DOMContentLoaded', () => {
  const box = document.querySelector('.product-buy-box');
  const button = document.getElementById('product-add-btn');
  const sizeSelect = document.getElementById('product-size');
  const stockNote = document.getElementById('product-stock-note');
  const currentLang = () => (localStorage.getItem('g_lang') || document.documentElement.lang || 'pt').toLowerCase().startsWith('en') ? 'en' : 'pt';
  const productText = (pt, en) => currentLang() === 'en' ? en : pt;

  if (!box || !button) {
    return;
  }

  function updateStockNote(stock, label = '') {
    if (!stockNote) {
      return;
    }
    if (box.dataset.ownProduct === '1') {
      stockNote.textContent = productText('Nao podes comprar o teu proprio produto.', 'You cannot buy your own product.');
      return;
    }
    if (sizeSelect) {
      if (!label) {
        stockNote.textContent = productText('Seleciona um tamanho para ver o stock.', 'Select a size to see stock.');
        return;
      }
      stockNote.textContent = stock > 0
        ? productText(`Stock ${label}: ${stock} unidade(s)`, `${label} stock: ${stock} unit(s)`)
        : productText(`${label} sem stock.`, `${label} is out of stock.`);
      return;
    }
    stockNote.textContent = stock > 0
      ? productText(`Em stock: ${stock} unidade(s)`, `In stock: ${stock} unit(s)`)
      : productText('Sem stock', 'Out of stock');
  }

  if (sizeSelect) {
    sizeSelect.addEventListener('change', () => {
      const selected = sizeSelect.options[sizeSelect.selectedIndex];
      const stock = Number(selected?.dataset?.stock || 0);
      box.dataset.productStock = stock;
      const qtyEl = box.querySelector('.product-qty');
      if (qtyEl) {
        const nextQty = stock > 0 ? Math.min(Number(qtyEl.textContent) || 1, stock) : 1;
        qtyEl.textContent = String(nextQty);
      }
      updateStockNote(stock, selected?.textContent?.split(' - ')[0] || '');
    });
    updateStockNote(0, '');
  } else {
    updateStockNote(Number(box.dataset.baseStock || 0));
  }

  window.handleProductAddToCart = () => {
    if (box.dataset.ownProduct === '1') {
      toast(productText('Nao podes comprar o teu proprio produto.', 'You cannot buy your own product.'));
      return;
    }

    const qtyEl = box.querySelector('.product-qty');
    const qty = Number(qtyEl?.textContent || 1);
    const stock = Number(box.dataset.productStock || 0);
    const sizeId = sizeSelect ? Number(sizeSelect.value || 0) : 0;

    if (sizeSelect && !sizeId) {
      toast(productText('Seleciona um tamanho antes de adicionar ao carrinho.', 'Select a size before adding to cart.'));
      return;
    }

    if (stock <= 0) {
      toast(productText('Este produto esta sem stock.', 'This product is out of stock.'));
      return;
    }

    if ((document.body?.dataset?.userId || '0') === '0') {
      toast(productText('Precisas de iniciar sessao.', 'You need to sign in.'));
      setTimeout(() => { window.location.href = 'login.php'; }, 500);
      return;
    }

    const cart = JSON.parse(localStorage.getItem('g_cart') || '[]');
    const itemKey = sizeId > 0 ? `${box.dataset.productId}:${sizeId}` : `${box.dataset.productId}`;
    const existing = cart.find((item) => item.key === itemKey);
    const sizeName = sizeSelect ? (sizeSelect.options[sizeSelect.selectedIndex]?.textContent?.split(' - ')[0] || '') : '';

    if (existing) {
      existing.qty = Math.min(existing.qty + qty, stock);
    } else {
      cart.push({
        key: itemKey,
        id: Number(box.dataset.productId),
        name: box.dataset.productName,
        price: Number(box.dataset.productPrice),
        img: box.dataset.productImg,
        qty,
        stock,
        sizeId,
        sizeName
      });
    }

    localStorage.setItem('g_cart', JSON.stringify(cart));

    if (typeof updateCartBadgeGlobal === 'function') {
      updateCartBadgeGlobal();
    }

    toast(productText('Produto adicionado ao carrinho.', 'Product added to cart.'));
  };
});
</script>

<?php include '../includes/footer.php'; ?>
