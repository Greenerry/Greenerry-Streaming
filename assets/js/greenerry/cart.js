function cartGet() {
  // The cart is stored in localStorage so it stays between page reloads.
  try {
    return JSON.parse(localStorage.getItem('g_cart') || '[]');
  } catch {
    return [];
  }
}

function cartSave(cart) {
  localStorage.setItem('g_cart', JSON.stringify(cart));
}

function addToCart(id, name, price, img, stock) {
  // Adds a product or increases quantity, while respecting the available stock.
  const cart = cartGet();
  const existing = cart.find((item) => item.id == id);
  const max = stock !== undefined ? parseInt(stock, 10) : 9999;

  if (existing) {
    if (existing.qty >= max) {
      toast(lang === 'pt' ? 'Stock esgotado' : 'Out of stock');
      return;
    }
    existing.qty += 1;
  } else {
    cart.push({ id, name, price, img, qty: 1, stock: max });
  }

  cartSave(cart);
  updateCartBadgeGlobal();
  toast(lang === 'pt' ? 'Adicionado ao carrinho' : 'Added to cart');

  if (!_isLoggedIn()) {
    toast(lang === 'pt' ? 'Guardado no carrinho. Inicia sessao para finalizar.' : 'Saved to cart. Sign in to checkout.');
    setTimeout(() => {
      window.location.href = (window.SITE_BASE || '') + '/pages/login.php?next=cart.php';
    }, 650);
  }
}

function updateCartBadgeGlobal() {
  const total = cartGet().length;
  document.querySelectorAll('.cart-badge').forEach((el) => {
    el.textContent = total;
    el.style.display = total > 0 ? 'inline-block' : 'none';
  });
}

function changeProductQty(delta, btn) {
  // Product detail page quantity buttons use this before adding to cart.
  const container = btn.closest('[data-product-id]');
  if (!container) return;

  const currentPageLang = (localStorage.getItem('g_lang') || lang || 'pt');
  const sizeSelect = container.querySelector('#product-size');
  const ownProduct = container.dataset.ownProduct === '1';
  const span = container.querySelector('.product-qty');
  const max = parseInt(container.dataset.productStock, 10) || 9999;
  const current = parseInt(span.textContent, 10) || 1;
  if (ownProduct) {
    toast(currentPageLang === 'pt' ? 'Não podes comprar o teu próprio produto.' : 'You cannot buy your own product.');
    return;
  }

  if (sizeSelect && !sizeSelect.value && delta > 0) {
    toast(currentPageLang === 'pt' ? 'Seleciona um tamanho primeiro.' : 'Select a size first.');
    return;
  }

  if (max <= 0) {
    toast(currentPageLang === 'pt' ? 'Este produto está sem stock.' : 'This product is out of stock.');
    return;
  }

  const next = Math.max(1, Math.min(max, current + delta));
  span.textContent = next;

  if (delta > 0 && next === current && current >= max) {
    toast(currentPageLang === 'pt' ? 'Já atingiste o stock disponível.' : 'You already reached the available stock.');
  }
}

/* Toast */
function toast(msg) {
  // Small temporary message used across the frontend.
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

/* Smooth internal navigation keeps iPhone audio alive */
