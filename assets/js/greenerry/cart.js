// Frontend script purpose: Handles cart interactions in the browser.
// Keep browser behavior small, readable, and reusable.
/* GREENERRY - commerce helpers */

function commerceLang() {
  return (localStorage.getItem('g_lang') || lang || document.documentElement.lang || 'pt')
    .toLowerCase()
    .startsWith('en') ? 'en' : 'pt';
}

function commerceText(pt, en) {
  return commerceLang() === 'en' ? en : pt;
}

function cartGet() {
  try {
    const parsed = JSON.parse(localStorage.getItem('g_cart') || '[]');
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

function cartSave(cart) {
  localStorage.setItem('g_cart', JSON.stringify(Array.isArray(cart) ? cart : []));
  updateCartBadgeGlobal();
}

function commerceMoney(value) {
  return `${Number(value || 0).toFixed(2).replace('.', ',')} €`;
}

function commerceEscape(value) {
  return String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  })[char]);
}

function updateCartBadgeGlobal() {
  const total = cartGet().length;
  document.querySelectorAll('.cart-badge').forEach((el) => {
    el.textContent = String(total);
    el.style.display = total > 0 ? 'inline-block' : 'none';
  });
}

function addToCart(id, name, price, img, stock, options = {}) {
  const cart = cartGet();
  const sizeId = Number(options.sizeId || 0);
  const key = sizeId > 0 ? `${id}:${sizeId}` : String(id);
  const max = Math.max(1, Number(stock || 9999));
  const qty = Math.max(1, Number(options.qty || 1));
  const existing = cart.find((item) => String(item.key || item.id) === key);

  if (existing) {
    if (Number(existing.qty || 1) >= max) {
      toast(commerceText('Stock esgotado', 'Out of stock'));
      return false;
    }
    existing.qty = Math.min(Number(existing.qty || 1) + qty, max);
  } else {
    cart.push({
      key,
      id: Number(id),
      name,
      price: Number(price),
      img,
      qty,
      stock: max,
      sizeId,
      sizeName: options.sizeName || ''
    });
  }

  cartSave(cart);
  toast(commerceText('Produto adicionado ao carrinho.', 'Product added to cart.'));

  if (!_isLoggedIn()) {
    toast(commerceText('Guardado no carrinho. Inicia sessao para finalizar.', 'Saved to cart. Sign in to checkout.'));
    setTimeout(() => {
      window.location.href = `${window.SITE_BASE || ''}/pages/login.php?next=cart.php`;
    }, 650);
  }

  return true;
}

function changeProductQty(delta, btn) {
  const container = btn.closest('[data-product-id]');
  if (!container) return;

  const sizeSelect = container.querySelector('#product-size');
  const ownProduct = container.dataset.ownProduct === '1';
  const span = container.querySelector('.product-qty');
  const max = parseInt(container.dataset.productStock, 10) || 9999;
  const current = parseInt(span?.textContent || '1', 10) || 1;

  if (ownProduct) {
    toast(commerceText('Nao podes comprar o teu proprio produto.', 'You cannot buy your own product.'));
    return;
  }

  if (sizeSelect && !sizeSelect.value && delta > 0) {
    toast(commerceText('Seleciona um tamanho primeiro.', 'Select a size first.'));
    return;
  }

  if (max <= 0) {
    toast(commerceText('Este produto esta sem stock.', 'This product is out of stock.'));
    return;
  }

  const next = Math.max(1, Math.min(max, current + delta));
  if (span) span.textContent = String(next);

  if (delta > 0 && next === current && current >= max) {
    toast(commerceText('Ja atingiste o stock disponivel.', 'You already reached the available stock.'));
  }
}

function handleProductAddToCart(button = null) {
  const box = button?.closest?.('[data-product-id]') || document.querySelector('.product-buy-box');
  if (!box) return;

  if (box.dataset.ownProduct === '1') {
    toast(commerceText('Nao podes comprar o teu proprio produto.', 'You cannot buy your own product.'));
    return;
  }

  const sizeSelect = box.querySelector('#product-size');
  const qty = Number(box.querySelector('.product-qty')?.textContent || 1);
  const stock = Number(box.dataset.productStock || box.dataset.baseStock || 0);
  const sizeId = sizeSelect ? Number(sizeSelect.value || 0) : 0;

  if (sizeSelect && !sizeId) {
    toast(commerceText('Seleciona um tamanho antes de adicionar ao carrinho.', 'Select a size before adding to cart.'));
    return;
  }

  if (stock <= 0) {
    toast(commerceText('Este produto esta sem stock.', 'This product is out of stock.'));
    return;
  }

  const sizeName = sizeSelect
    ? (sizeSelect.options[sizeSelect.selectedIndex]?.textContent?.split(' - ')[0] || '')
    : '';

  addToCart(
    Number(box.dataset.productId),
    box.dataset.productName || 'Produto',
    Number(box.dataset.productPrice || 0),
    box.dataset.productImg || '',
    stock,
    { qty, sizeId, sizeName }
  );
}

function isProductDescriptionTruncated(text) {
  if (!text) return false;

  const clone = text.cloneNode(true);
  Object.assign(clone.style, {
    position: 'absolute',
    visibility: 'hidden',
    pointerEvents: 'none',
    height: 'auto',
    maxHeight: 'none',
    display: 'block',
    overflow: 'visible',
    WebkitLineClamp: 'unset',
    width: `${text.clientWidth}px`
  });
  text.parentElement?.appendChild(clone);
  const truncated = clone.scrollHeight > text.clientHeight + 1;
  clone.remove();
  return truncated;
}

function syncProductDescriptionToggle(root = document) {
  const description = root.querySelector?.('#product-description') || document.getElementById('product-description');
  const text = root.querySelector?.('#product-description-text') || document.getElementById('product-description-text');
  const toggle = root.querySelector?.('#product-description-toggle') || document.getElementById('product-description-toggle');
  if (!description || !text || !toggle) return;

  const isExpanded = description.classList.contains('is-expanded');
  const key = isExpanded ? 'product_read_less' : 'product_read_more';
  const fallback = isExpanded
    ? commerceText('Ver menos', 'Show less')
    : commerceText('Ver mais', 'Read more');
  toggle.dataset.t = key;
  toggle.textContent = (typeof T !== 'undefined' && T[commerceLang()]?.[key]) ? T[commerceLang()][key] : fallback;
  toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');

  if (isExpanded) {
    toggle.style.display = 'inline-block';
    return;
  }

  // Use style display instead of hidden attribute to avoid being overridden
  // by other code or browser quirks. The JS will show the toggle only when
  // the description is truncated.
  toggle.style.display = isProductDescriptionTruncated(text) ? 'inline-block' : 'none';
}

function toggleProductDescription() {
  const description = document.getElementById('product-description');
  if (!description) return;
  description.classList.toggle('is-expanded');
  syncProductDescriptionToggle();
}

function initProductDescription(root = document) {
  const description = root.querySelector?.('#product-description') || document.getElementById('product-description');
  const toggle = root.querySelector?.('#product-description-toggle') || document.getElementById('product-description-toggle');
  if (!description || !toggle || description.dataset.descriptionReady === '1') return;
  description.dataset.descriptionReady = '1';

  toggle.addEventListener('click', toggleProductDescription);
  syncProductDescriptionToggle(root);
  window.addEventListener('greenerry:langchange', () => syncProductDescriptionToggle(root));
  window.addEventListener('resize', () => syncProductDescriptionToggle(root));
}

function initProductPage(root = document) {
  initProductDescription(root);

  const box = root.querySelector?.('.product-buy-box') || document.querySelector('.product-buy-box');
  if (!box || box.dataset.commerceReady === '1') return;
  box.dataset.commerceReady = '1';

  const sizeSelect = box.querySelector('#product-size');
  const stockNote = box.querySelector('#product-stock-note') || document.getElementById('product-stock-note');
  const mainImage = document.getElementById('product-main-image');
  const galleryRoot = document.querySelector('.product-gallery');
  const galleryCover = galleryRoot?.querySelector('.product-cover');
  let galleryImages = [];
  let galleryIndex = 0;

  try {
    galleryImages = JSON.parse(galleryRoot?.dataset.galleryImages || '[]');
  } catch {
    galleryImages = [];
  }

  const normalizeGalleryUrl = (url) => {
    try {
      return new URL(url, window.location.href).pathname;
    } catch {
      return String(url || '');
    }
  };

  const syncGalleryIndex = () => {
    if (!mainImage || !galleryImages.length) return;
    const current = normalizeGalleryUrl(mainImage.getAttribute('src') || mainImage.src);
    const index = galleryImages.findIndex((image) => normalizeGalleryUrl(image) === current);
    if (index >= 0) galleryIndex = index;
  };

  syncGalleryIndex();

  const unitLabel = (stock) => commerceLang() === 'en'
    ? (stock === 1 ? 'unit' : 'units')
    : (stock === 1 ? 'unidade' : 'unidades');

  const updateGalleryFrame = () => {
    if (!mainImage || !galleryCover || !mainImage.naturalWidth || !mainImage.naturalHeight) return;
    const ratio = mainImage.naturalWidth / mainImage.naturalHeight;
    galleryCover.classList.remove('product-cover--portrait', 'product-cover--wide');
    if (ratio < 0.82) galleryCover.classList.add('product-cover--portrait');
    else if (ratio > 1.18) galleryCover.classList.add('product-cover--wide');
  };

  const showGalleryImage = (index) => {
    if (!mainImage || !galleryImages.length) return;
    const nextIndex = (index + galleryImages.length) % galleryImages.length;
    if (!galleryImages[nextIndex] || nextIndex === galleryIndex) return;
    galleryIndex = nextIndex;
    mainImage.classList.add('is-changing');
    window.setTimeout(() => {
      mainImage.src = galleryImages[galleryIndex];
    }, 120);
  };

  const updateStockNote = (stock, label = '') => {
    if (!stockNote) return;
    if (box.dataset.ownProduct === '1') {
      stockNote.textContent = commerceText('Nao podes comprar o teu proprio produto.', 'You cannot buy your own product.');
      return;
    }
    if (sizeSelect) {
      if (!label) {
        stockNote.textContent = commerceText('Seleciona um tamanho para ver o stock.', 'Select a size to see stock.');
        return;
      }
      stockNote.textContent = stock > 0
        ? commerceText(`Stock ${label}: ${stock} ${unitLabel(stock)}`, `${label} stock: ${stock} ${unitLabel(stock)}`)
        : commerceText(`${label} sem stock.`, `${label} is out of stock.`);
      return;
    }
    stockNote.textContent = stock > 0
      ? commerceText(`Em stock: ${stock} ${unitLabel(stock)}`, `In stock: ${stock} ${unitLabel(stock)}`)
      : commerceText('Sem stock', 'Out of stock');
  };

  if (mainImage) {
    mainImage.addEventListener('load', () => {
      mainImage.classList.remove('is-changing');
      updateGalleryFrame();
    });
    if (mainImage.complete) updateGalleryFrame();
  }

  root.querySelectorAll?.('[data-gallery-step]').forEach((button) => {
    button.addEventListener('click', () => {
      syncGalleryIndex();
      showGalleryImage(galleryIndex + Number(button.dataset.galleryStep || 0));
    });
  });

  if (sizeSelect) {
    sizeSelect.addEventListener('change', () => {
      const selected = sizeSelect.options[sizeSelect.selectedIndex];
      const stock = Number(selected?.dataset?.stock || 0);
      box.dataset.productStock = stock;
      const qtyEl = box.querySelector('.product-qty');
      if (qtyEl) qtyEl.textContent = String(stock > 0 ? Math.min(Number(qtyEl.textContent) || 1, stock) : 1);
      updateStockNote(stock, selected?.textContent?.split(' - ')[0] || '');
    });
    updateStockNote(0, '');
  } else {
    box.dataset.productStock = box.dataset.baseStock || box.dataset.productStock || '0';
    updateStockNote(Number(box.dataset.baseStock || box.dataset.productStock || 0));
  }

  window.addEventListener('greenerry:langchange', () => {
    if (sizeSelect) {
      const selected = sizeSelect.options[sizeSelect.selectedIndex];
      updateStockNote(Number(selected?.dataset?.stock || 0), selected?.value ? (selected.textContent || '').split(' - ')[0] : '');
      return;
    }
    updateStockNote(Number(box.dataset.baseStock || box.dataset.productStock || 0));
  });
}

function initCartPage(root = document) {
  const cartItemsEl = root.getElementById?.('cart-items') || document.getElementById('cart-items');
  if (!cartItemsEl || cartItemsEl.dataset.commerceReady === '1') return;
  cartItemsEl.dataset.commerceReady = '1';

  const cartEmptyEl = document.getElementById('cart-empty');
  const cartFooterEl = document.getElementById('cart-footer');
  const subtotalEl = document.getElementById('cart-subtotal');
  const ivaEl = document.getElementById('cart-iva');
  const totalEl = document.getElementById('cart-total');
  const clearBtn = document.getElementById('clear-cart-btn');

  const renderCart = () => {
    const cart = cartGet();
    if (!cart.length) {
      if (cartEmptyEl) cartEmptyEl.style.display = 'grid';
      if (cartFooterEl) cartFooterEl.style.display = 'none';
      cartItemsEl.innerHTML = '';
      updateCartBadgeGlobal();
      return;
    }

    let subtotal = 0;
    cartItemsEl.innerHTML = cart.map((item, index) => {
      const quantity = Math.max(1, Number(item.qty || 1));
      const price = Number(item.price || 0);
      const lineTotal = quantity * price;
      const maxStock = Math.max(1, Number(item.stock || 9999));
      const sizeLabel = item.sizeName
        ? `<span class="cart-item-tag">${commerceText('Tamanho', 'Size')} ${commerceEscape(item.sizeName)}</span>`
        : '';
      subtotal += lineTotal;

      return `
        <article class="cart-item-card" style="--cart-delay:${Math.min(index * 70, 420)}ms">
          <a href="produto.php?id=${Number(item.id || 0)}" class="cart-item-media">
            ${item.img ? `<img src="${window.SITE_BASE || ''}/assets/img/${commerceEscape(item.img)}" alt="">` : `<span>Merch</span>`}
          </a>
          <div class="cart-item-copy">
            <div class="cart-item-top">
              <div>
                <a href="produto.php?id=${Number(item.id || 0)}" class="cart-item-title"><h3>${commerceEscape(item.name || 'Produto')}</h3></a>
                <p>${commerceMoney(price)} ${commerceText('por unidade', 'per unit')}</p>
              </div>
              <strong>${commerceMoney(lineTotal)}</strong>
            </div>
            <div class="cart-item-meta">
              ${sizeLabel}
              <span class="cart-item-tag">${commerceText('Stock', 'Stock')} ${maxStock}</span>
            </div>
            <div class="cart-item-actions">
              <div class="qty-picker">
                <button type="button" data-action="decrease" data-index="${index}">-</button>
                <span>${quantity}</span>
                <button type="button" data-action="increase" data-index="${index}" data-max="${maxStock}">+</button>
              </div>
              <button type="button" class="cart-remove-btn" data-action="remove" data-index="${index}">${commerceText('Remover', 'Remove')}</button>
            </div>
          </div>
        </article>
      `;
    }).join('');

    const iva = subtotal * 0.23;
    if (subtotalEl) subtotalEl.textContent = commerceMoney(subtotal);
    if (ivaEl) ivaEl.textContent = commerceMoney(iva);
    if (totalEl) totalEl.textContent = commerceMoney(subtotal + iva);
    if (cartEmptyEl) cartEmptyEl.style.display = 'none';
    if (cartFooterEl) cartFooterEl.style.display = 'grid';
    updateCartBadgeGlobal();
  };

  cartItemsEl.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    const action = target.dataset.action;
    const index = Number(target.dataset.index);
    if (!action || Number.isNaN(index)) return;

    const cart = cartGet();
    if (!cart[index]) return;
    if (action === 'remove') cart.splice(index, 1);
    if (action === 'decrease') cart[index].qty = Math.max(1, Number(cart[index].qty || 1) - 1);
    if (action === 'increase') {
      const max = Math.max(1, Number(target.dataset.max || cart[index].stock || 9999));
      cart[index].qty = Math.min(max, Number(cart[index].qty || 1) + 1);
    }
    cartSave(cart);
    renderCart();
  });

  clearBtn?.addEventListener('click', () => {
    cartSave([]);
    renderCart();
  });

  window.addEventListener('greenerry:langchange', renderCart);
  renderCart();
}

function initCheckoutPage(root = document) {
  const checkoutForm = root.getElementById?.('checkout-form') || document.getElementById('checkout-form');
  const clearMarker = root.querySelector?.('[data-clear-cart-on-load]') || document.querySelector('[data-clear-cart-on-load]');

  if (clearMarker && clearMarker.dataset.commerceReady !== '1') {
    clearMarker.dataset.commerceReady = '1';
    cartSave([]);
  }

  if (!checkoutForm || checkoutForm.dataset.commerceReady === '1') return;
  checkoutForm.dataset.commerceReady = '1';

  const cart = cartGet();
  const items = document.getElementById('checkout-items');
  const subtotalEl = document.getElementById('checkout-subtotal');
  const ivaEl = document.getElementById('checkout-iva');
  const totalEl = document.getElementById('checkout-total');
  const cartJson = document.getElementById('cart_json');
  const countrySelect = document.getElementById('pais');
  const postalInput = document.getElementById('codigo_postal');
  const phoneInput = document.getElementById('telefone');
  const taxLabel = document.getElementById('tax-label');

  if (cartJson) cartJson.value = JSON.stringify(cart);

  const syncCountryFields = () => {
    const option = countrySelect?.options?.[countrySelect.selectedIndex] || countrySelect;
    if (!option) return;
    if (postalInput) {
      postalInput.placeholder = option.dataset.postalPlaceholder || '';
      postalInput.pattern = option.dataset.postalPattern || '';
    }
    if (phoneInput) phoneInput.placeholder = option.dataset.phonePlaceholder || '';
    if (taxLabel) {
      taxLabel.dataset.t = 'checkout_tax_nif';
      taxLabel.textContent = 'NIF';
    }
  };

  const renderCheckout = () => {
    if (!items) return;
    if (!cart.length) {
      items.innerHTML = `<p data-t="checkout_empty_cart">${commerceText('O carrinho esta vazio.', 'The cart is empty.')}</p>`;
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
            <strong>${commerceEscape(item.name || '')}</strong>
            <p>${qty} x ${commerceMoney(price)}</p>
          </div>
          <span>${commerceMoney(lineTotal)}</span>
        </div>
      `;
    }).join('');

    const iva = subtotal * 0.23;
    if (subtotalEl) subtotalEl.textContent = commerceMoney(subtotal);
    if (ivaEl) ivaEl.textContent = commerceMoney(iva);
    if (totalEl) totalEl.textContent = commerceMoney(subtotal + iva);
  };

  countrySelect?.addEventListener('change', syncCountryFields);
  window.addEventListener('greenerry:langchange', () => {
    syncCountryFields();
    renderCheckout();
  });
  syncCountryFields();
  renderCheckout();
}

function initCommerce(root = document) {
  initProductPage(root);
  initCartPage(root);
  initCheckoutPage(root);
  updateCartBadgeGlobal();
}

/* Toast */
function toast(msg) {
  const el = document.createElement('div');
  el.textContent = msg;

  Object.assign(el.style, {
    position: 'fixed',
    top: '92px',
    left: '50%',
    transform: 'translateX(-50%) translateY(-12px)',
    background: 'var(--text)',
    color: 'var(--bg2)',
    padding: '10px 22px',
    borderRadius: '40px',
    fontSize: '.84rem',
    fontWeight: '600',
    zIndex: '9999',
    opacity: '0',
    whiteSpace: 'nowrap',
    transition: 'all .25s',
    boxShadow: 'var(--shadowl)'
  });

  document.body.appendChild(el);
  requestAnimationFrame(() => {
    el.style.opacity = '1';
    el.style.transform = 'translateX(-50%) translateY(0)';
  });

  setTimeout(() => {
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 300);
  }, 2200);
}

document.addEventListener('DOMContentLoaded', () => initCommerce());
window.addEventListener('greenerry:page-ready', (event) => initCommerce(event.detail?.root || document));
