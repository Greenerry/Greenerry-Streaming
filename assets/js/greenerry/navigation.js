// Frontend script purpose: Controls responsive navigation and sidebars.
// Keep browser behavior small, readable, and reusable.
const _softNavPages = new Set([
  'index.php',
  'music.php',
  'release.php',
  'artists.php',
  'artist.php',
  'shop.php',
  'produto.php',
  'favourites.php',
  'profile.php',
  'cart.php',
  'checkout.php',
  'my_orders.php',
  'notifications.php',
  'receipt.php',
  'artist_dashboard.php',
  'artist_analytics.php',
  'artist_releases.php',
  'artist_products.php',
  'artist_messages.php',
  'artist_customers.php',
  'upload_music.php',
  'upload_merch.php',
  'orders.php',
  'revenue.php',
  'contact_admin.php'
]);
const _softNavBlockedPages = new Set([
  'login.php',
  'registar.php',
  'forgot_password.php',
  'reset_password.php',
  'verify_email.php',
  'logout.php'
]);
let _softNavBusy = false;

// Soft navigation replaces only the page body while music keeps playing.

function _pageNameFromUrl(url) {
  const path = url.pathname.replace(/\/+$/, '');
  return path.substring(path.lastIndexOf('/') + 1) || 'index.php';
}

function _canSoftNavigate(link, event) {
  if (!link || link.target || link.hasAttribute('download')) return false;
  if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) return false;

  const url = new URL(link.href, window.location.href);
  if (url.origin !== window.location.origin) return false;
  if (url.hash && url.pathname === window.location.pathname && url.search === window.location.search) return false;

  const currentPage = _pageNameFromUrl(new URL(window.location.href));
  const targetPage = _pageNameFromUrl(url);
  if (_softNavBlockedPages.has(currentPage) || _softNavBlockedPages.has(targetPage)) return false;

  return _softNavPages.has(targetPage);
}

function _runPageScripts(root) {
  root.querySelectorAll('script').forEach((oldScript) => {
    const script = document.createElement('script');
    Array.from(oldScript.attributes).forEach((attr) => {
      script.setAttribute(attr.name, attr.value);
    });

    if (!oldScript.src) {
      script.textContent = `
(() => {
  const originalAddEventListener = document.addEventListener.bind(document);
  const readyEvent = new Event('DOMContentLoaded', { bubbles: true, cancelable: true });
  document.addEventListener = function(type, listener, options) {
    if (type === 'DOMContentLoaded' && document.readyState !== 'loading' && typeof listener === 'function') {
      queueMicrotask(() => listener.call(document, readyEvent));
      return;
    }
    return originalAddEventListener(type, listener, options);
  };
  try {
${oldScript.textContent}
  } finally {
    document.addEventListener = originalAddEventListener;
  }
})();
`;
    }

    oldScript.replaceWith(script);
  });
}

function _syncNavActive() {
  const current = _pageNameFromUrl(new URL(window.location.href));

  document.querySelectorAll('.sl-link').forEach((link) => {
    try {
      const linkPage = _pageNameFromUrl(new URL(link.href, window.location.href));
      link.classList.toggle('on', linkPage === current);
    } catch {}
  });
}

async function _softNavigate(url, push = true) {
  if (_softNavBusy) return;
  _softNavBusy = true;

  try {
    // Fetch the next page, copy the app chrome and page body, then re-run page scripts.
    _saveState();
    const currentPage = _pageNameFromUrl(new URL(window.location.href));
    const targetPage = _pageNameFromUrl(url);
    const keepScroll = currentPage === 'artist.php' && targetPage === 'release.php';
    const previousScroll = { x: window.scrollX || 0, y: window.scrollY || 0 };
    const response = await fetch(url.href, { headers: { 'X-Requested-With': 'fetch' } });
    if (!response.ok) throw new Error('Navigation failed');

    const html = await response.text();
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const nextBody = doc.querySelector('.page-body');
    const currentBody = document.querySelector('.page-body');
    const nextSidebar = doc.querySelector('#sl');
    const currentSidebar = document.querySelector('#sl');
    const nextNav = doc.querySelector('#main-nav');
    const currentNav = document.querySelector('#main-nav');
    if (!nextBody || !currentBody) throw new Error('Missing page body');

    document.body.classList.toggle('artist-sidebar-mode', doc.body.classList.contains('artist-sidebar-mode'));
    if (nextSidebar && currentSidebar) currentSidebar.replaceWith(nextSidebar);
    if (nextNav && currentNav) currentNav.replaceWith(nextNav);
    currentBody.innerHTML = nextBody.innerHTML;
    const rightSidebarOpen = document.getElementById('sr')?.classList.contains('open');
    document.querySelector('.main')?.classList.toggle('sr-open', !!rightSidebarOpen);
    document.getElementById('main-nav')?.classList.toggle('sr-open', !!rightSidebarOpen);
    document.getElementById('player-bar')?.classList.toggle('sr-open', !!rightSidebarOpen);
    [document.querySelector('#sl'), document.querySelector('#main-nav'), currentBody].forEach((element) => {
      element?.style.removeProperty('opacity');
      element?.style.removeProperty('transform');
      element?.style.removeProperty('will-change');
    });
    _runPageScripts(currentBody);
    document.title = doc.title || document.title;

    if (push) history.pushState({ greenerrySoftNav: true, scrollY: keepScroll ? previousScroll.y : 0 }, '', url.href);
    if (!keepScroll) window.scrollTo(0, 0);
    document.body.classList.remove('artist-mode-switching');
    document.querySelectorAll('[data-artist-mode-toggle]').forEach((item) => {
      item.disabled = false;
    });

    if (typeof initThemeToggle === 'function') initThemeToggle();
    setLang(lang);
    closeMobileSidebar();
    updateCartBadgeGlobal();
    _syncNavActive();
    _registerMotion(currentBody);
    await _initPageContent();
    await _loadTracks();
    _renderQueue();
    window.dispatchEvent(new CustomEvent('greenerry:page-ready', { detail: { root: currentBody } }));
    requestAnimationFrame(() => window.scrollTo({ top: keepScroll ? previousScroll.y : 0, left: keepScroll ? previousScroll.x : 0, behavior: 'auto' }));
  } catch (error) {
    window.location.href = url.href;
  } finally {
    _softNavBusy = false;
  }
}

function _bindSoftNavigation() {
  history.replaceState({ greenerrySoftNav: true }, '', window.location.href);

  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!_canSoftNavigate(link, event)) return;

    event.preventDefault();
    _softNavigate(new URL(link.href, window.location.href));
  });

  window.addEventListener('popstate', () => {
    _softNavigate(new URL(window.location.href), false);
  });
}

function _bindScrollState() {
  const sync = () => {
    const scrolled = window.scrollY > 8;
    document.body.classList.toggle('is-scrolled', scrolled);
    document.getElementById('main-nav')?.classList.toggle('solid', scrolled);
  };
  sync();
  window.addEventListener('scroll', sync, { passive: true });
}
