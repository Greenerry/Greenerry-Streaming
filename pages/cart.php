<?php
require_once '../includes/config.php';

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap cart-shell">
    <div class="cart-hero hero-card--single">
      <div class="cart-hero-copy">
        <h2 data-t="cart_title">Carrinho</h2>
      </div>
    </div>

    <div id="cart-empty" class="cart-empty-state is-hidden">
      <div class="cart-empty-icon">Cart</div>
      <h3 data-t="cart_empty_title">O teu carrinho esta vazio.</h3>
      <p data-t="cart_empty_text">Explora a loja, escolhe merch oficial e volta aqui para finalizar a encomenda.</p>
      <a href="shop.php" class="btn btn-ghost btn-sm" data-t="cart_empty_cta">Ver loja</a>
    </div>

    <div class="cart-layout">
      <div id="cart-items" class="cart-items-stack"></div>

      <aside id="cart-footer" class="cart-summary-card is-hidden">
        <div class="cart-summary-head">
          <span class="slabel" data-t="cart_summary_label">Resumo</span>
          <h3 data-t="cart_summary_title">Total da encomenda</h3>
        </div>

        <div class="cart-summary-line">
          <span data-t="cart_subtotal">Subtotal</span>
          <strong id="cart-subtotal">0,00 EUR</strong>
        </div>
        <div class="cart-summary-line">
          <span data-t="cart_vat">IVA estimado</span>
          <strong id="cart-iva">0,00 EUR</strong>
        </div>
        <div class="cart-summary-line cart-summary-line--total">
          <span data-t="cart_total">Total</span>
          <strong id="cart-total">0,00 EUR</strong>
        </div>

        <?php if (is_user_logged_in()): ?>
          <a href="checkout.php" class="btn btn-dark btn-full btn-lg" data-t="cart_checkout_cta">Finalizar compra</a>
        <?php else: ?>
          <a href="login.php" class="btn btn-dark btn-full btn-lg" data-t="cart_login_cta">Entrar para finalizar</a>
        <?php endif; ?>

        <button type="button" id="clear-cart-btn" class="btn btn-ghost btn-full btn-sm" data-t="cart_clear">Limpar carrinho</button>
      </aside>
    </div>
  </div>
</section>

<script>
(() => {
  const SITE_BASE = window.SITE_BASE || '';
  const cartItemsEl = document.getElementById('cart-items');
  const cartEmptyEl = document.getElementById('cart-empty');
  const cartFooterEl = document.getElementById('cart-footer');
  const subtotalEl = document.getElementById('cart-subtotal');
  const ivaEl = document.getElementById('cart-iva');
  const totalEl = document.getElementById('cart-total');
  const clearBtn = document.getElementById('clear-cart-btn');
  const sizeText = document.documentElement.lang === 'en' ? 'Size' : 'Tamanho';
  const stockText = document.documentElement.lang === 'en' ? 'Stock' : 'Stock';
  const merchText = document.documentElement.lang === 'en' ? 'Merch' : 'Merch';
  const perUnitText = document.documentElement.lang === 'en' ? 'per unit' : 'por unidade';
  const removeText = document.documentElement.lang === 'en' ? 'Remove' : 'Remover';

  function readCart() {
    try {
      const parsed = JSON.parse(localStorage.getItem('g_cart') || '[]');
      return Array.isArray(parsed) ? parsed : [];
    } catch {
      return [];
    }
  }

  function saveCart(cart) {
    localStorage.setItem('g_cart', JSON.stringify(cart));
    renderCart();
  }

  function formatMoney(value) {
    return Number(value || 0).toFixed(2).replace('.', ',') + ' EUR';
  }

  function updateBadge(cart) {
    document.querySelectorAll('.cart-badge').forEach((badge) => {
      badge.textContent = String(cart.length);
      badge.style.display = cart.length > 0 ? 'inline-block' : 'none';
    });
  }

  function renderCart() {
    const cart = readCart();

    if (!cart.length) {
      cartEmptyEl.style.display = 'grid';
      cartFooterEl.style.display = 'none';
      cartItemsEl.innerHTML = '';
      updateBadge(cart);
      return;
    }

    let subtotal = 0;

    cartItemsEl.innerHTML = cart.map((item, index) => {
      const quantity = Math.max(1, Number(item.qty || 1));
      const price = Number(item.price || 0);
      const lineTotal = quantity * price;
      const maxStock = Math.max(1, Number(item.stock || 9999));
      const sizeLabel = item.sizeName ? `<span class="cart-item-tag">${sizeText} ${item.sizeName}</span>` : '';

      subtotal += lineTotal;

      return `
        <article class="cart-item-card">
          <div class="cart-item-media">
            ${item.img ? `<img src="${SITE_BASE}/assets/img/${item.img}" alt="">` : `<span>${merchText}</span>`}
          </div>
          <div class="cart-item-copy">
            <div class="cart-item-top">
              <div>
                <h3>${item.name || 'Produto'}</h3>
                <p>${formatMoney(price)} ${perUnitText}</p>
              </div>
              <strong>${formatMoney(lineTotal)}</strong>
            </div>
            <div class="cart-item-meta">
              ${sizeLabel}
              <span class="cart-item-tag">${stockText} ${maxStock}</span>
            </div>
            <div class="cart-item-actions">
              <div class="qty-picker">
                <button type="button" data-action="decrease" data-index="${index}">-</button>
                <span>${quantity}</span>
                <button type="button" data-action="increase" data-index="${index}" data-max="${maxStock}">+</button>
              </div>
              <button type="button" class="cart-remove-btn" data-action="remove" data-index="${index}">${removeText}</button>
            </div>
          </div>
        </article>
      `;
    }).join('');

    const iva = subtotal * 0.23;
    const total = subtotal + iva;

    subtotalEl.textContent = formatMoney(subtotal);
    ivaEl.textContent = formatMoney(iva);
    totalEl.textContent = formatMoney(total);
    cartEmptyEl.style.display = 'none';
    cartFooterEl.style.display = 'grid';
    updateBadge(cart);
  }

  cartItemsEl.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
      return;
    }

    const action = target.dataset.action;
    const index = Number(target.dataset.index);
    if (!action || Number.isNaN(index)) {
      return;
    }

    const cart = readCart();
    if (!cart[index]) {
      return;
    }

    if (action === 'remove') {
      cart.splice(index, 1);
      saveCart(cart);
      return;
    }

    if (action === 'decrease') {
      cart[index].qty = Math.max(1, Number(cart[index].qty || 1) - 1);
      saveCart(cart);
      return;
    }

    if (action === 'increase') {
      const max = Math.max(1, Number(target.dataset.max || cart[index].stock || 9999));
      cart[index].qty = Math.min(max, Number(cart[index].qty || 1) + 1);
      saveCart(cart);
    }
  });

  clearBtn?.addEventListener('click', () => {
    saveCart([]);
  });

  document.querySelectorAll('.lang button').forEach((button) => {
    button.addEventListener('click', () => {
      setTimeout(renderCart, 0);
    });
  });

  renderCart();
})();
</script>

<?php include '../includes/footer.php'; ?>
