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
            </div>
            <?php if (count($productImages) > 1): ?>
              <div class="product-gallery-controls">
                <button type="button" class="product-gallery-arrow" data-gallery-step="-1" aria-label="<?= h(current_lang() === 'en' ? 'Previous image' : 'Imagem anterior') ?>">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <div class="product-gallery-thumbs" role="tablist" aria-label="<?= h(current_lang() === 'en' ? 'Product images' : 'Imagens do produto') ?>">
                  <?php foreach ($productImages as $thumbIndex => $thumbImage): ?>
                    <button
                      type="button"
                      class="product-gallery-thumb<?= $thumbIndex === 0 ? ' on' : '' ?>"
                      data-gallery-index="<?= (int)$thumbIndex ?>"
                      role="tab"
                      aria-selected="<?= $thumbIndex === 0 ? 'true' : 'false' ?>"
                      aria-label="<?= h((current_lang() === 'en' ? 'Image ' : 'Imagem ') . ($thumbIndex + 1)) ?>"
                    >
                      <img src="<?= h(asset_url('img', $thumbImage)) ?>" alt="">
                    </button>
                  <?php endforeach; ?>
                </div>
                <button type="button" class="product-gallery-arrow" data-gallery-step="1" aria-label="<?= h(current_lang() === 'en' ? 'Next image' : 'Imagem seguinte') ?>">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>
              </div>
              <p class="product-gallery-count" id="product-gallery-count" aria-live="polite">1 / <?= count($productImages) ?></p>
            <?php endif; ?>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
  const box = document.querySelector('.product-buy-box');
  const button = document.getElementById('product-add-btn');
  const sizeSelect = document.getElementById('product-size');
  const stockNote = document.getElementById('product-stock-note');
  const currentLang = () => (localStorage.getItem('g_lang') || document.documentElement.lang || 'pt').toLowerCase().startsWith('en') ? 'en' : 'pt';
  const productText = (pt, en) => currentLang() === 'en' ? en : pt;
  const unitLabel = (stock) => currentLang() === 'en'
    ? (stock === 1 ? 'unit' : 'units')
    : (stock === 1 ? 'unidade' : 'unidades');

  if (!box || !button) {
    return;
  }

  const mainImage = document.getElementById('product-main-image');
  const galleryRoot = document.querySelector('.product-gallery');
  const galleryCover = galleryRoot?.querySelector('.product-cover');
  const galleryCount = document.getElementById('product-gallery-count');
  const galleryThumbs = Array.from(document.querySelectorAll('.product-gallery-thumb'));
  let galleryImages = [];
  try {
    galleryImages = JSON.parse(galleryRoot?.dataset.galleryImages || '[]');
  } catch {
    galleryImages = [];
  }
  let galleryIndex = 0;

  function updateGalleryFrame() {
    if (!mainImage || !galleryCover || !mainImage.naturalWidth || !mainImage.naturalHeight) {
      return;
    }

    const ratio = mainImage.naturalWidth / mainImage.naturalHeight;
    galleryCover.classList.remove('product-cover--portrait', 'product-cover--wide');

    if (ratio < 0.82) {
      galleryCover.classList.add('product-cover--portrait');
    } else if (ratio > 1.18) {
      galleryCover.classList.add('product-cover--wide');
    }
  }

  function syncGalleryUi() {
    galleryThumbs.forEach((thumb, index) => {
      const active = index === galleryIndex;
      thumb.classList.toggle('on', active);
      thumb.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    if (galleryCount && galleryImages.length > 1) {
      galleryCount.textContent = `${galleryIndex + 1} / ${galleryImages.length}`;
    }
  }

  function showGalleryImage(index) {
    if (!mainImage || !galleryImages.length) {
      return;
    }

    const nextIndex = (index + galleryImages.length) % galleryImages.length;
    if (!galleryImages[nextIndex] || nextIndex === galleryIndex) {
      return;
    }

    galleryIndex = nextIndex;
    mainImage.classList.add('is-changing');

    window.setTimeout(() => {
      mainImage.src = galleryImages[galleryIndex];
      syncGalleryUi();
    }, 120);
  }

  if (mainImage) {
    mainImage.addEventListener('load', () => {
      mainImage.classList.remove('is-changing');
      updateGalleryFrame();
    });
    if (mainImage.complete) {
      updateGalleryFrame();
    }
  }

  document.querySelectorAll('[data-gallery-step]').forEach((button) => {
    button.addEventListener('click', () => {
      showGalleryImage(galleryIndex + Number(button.dataset.galleryStep || 0));
    });
  });

  galleryThumbs.forEach((thumb) => {
    thumb.addEventListener('click', () => {
      showGalleryImage(Number(thumb.dataset.galleryIndex || 0));
    });
  });

  syncGalleryUi();

  function updateStockNote(stock, label = '') {
    if (!stockNote) {
      return;
    }
    if (box.dataset.ownProduct === '1') {
      stockNote.textContent = productText('Não podes comprar o teu próprio produto.', 'You cannot buy your own product.');
      return;
    }
    if (sizeSelect) {
      if (!label) {
        stockNote.textContent = productText('Seleciona um tamanho para ver o stock.', 'Select a size to see stock.');
        return;
      }
      stockNote.textContent = stock > 0
        ? productText(`Stock ${label}: ${stock} ${unitLabel(stock)}`, `${label} stock: ${stock} ${unitLabel(stock)}`)
        : productText(`${label} sem stock.`, `${label} is out of stock.`);
      return;
    }
    stockNote.textContent = stock > 0
      ? productText(`Em stock: ${stock} ${unitLabel(stock)}`, `In stock: ${stock} ${unitLabel(stock)}`)
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

  window.addEventListener('greenerry:langchange', () => {
    if (sizeSelect) {
      const selected = sizeSelect.options[sizeSelect.selectedIndex];
      const stock = Number(selected?.dataset?.stock || 0);
      updateStockNote(stock, selected?.value ? (selected.textContent || '').split(' - ')[0] : '');
      return;
    }

    updateStockNote(Number(box.dataset.baseStock || 0));
  });

  window.handleProductAddToCart = () => {
    if (box.dataset.ownProduct === '1') {
      toast(productText('Não podes comprar o teu próprio produto.', 'You cannot buy your own product.'));
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

    if ((document.body?.dataset?.userId || '0') === '0') {
      toast(productText('Guardado no carrinho. Inicia sessao para finalizar.', 'Saved to cart. Sign in to checkout.'));
      setTimeout(() => { window.location.href = 'login.php?next=cart.php'; }, 650);
    }
  };
});
</script>

<?php include '../includes/footer.php'; ?>
