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
  'upload_music.php',
  'upload_merch.php',
  'orders.php',
  'revenue.php',
  'contact_admin.php'
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

  return _softNavPages.has(_pageNameFromUrl(url));
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
    // Fetch the next page, copy its .page-body, then re-run page scripts.
    _saveState();
    const response = await fetch(url.href, { headers: { 'X-Requested-With': 'fetch' } });
    if (!response.ok) throw new Error('Navigation failed');

    const html = await response.text();
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const nextBody = doc.querySelector('.page-body');
    const currentBody = document.querySelector('.page-body');
    if (!nextBody || !currentBody) throw new Error('Missing page body');

    currentBody.innerHTML = nextBody.innerHTML;
    _runPageScripts(currentBody);
    document.title = doc.title || document.title;

    if (push) history.pushState({ greenerrySoftNav: true }, '', url.href);
    window.scrollTo(0, 0);

    setLang(lang);
    closeMobileSidebar();
    updateCartBadgeGlobal();
    _syncNavActive();
    _registerMotion(currentBody);
    await _initPageContent();
    await _loadTracks();
    _renderQueue();
    window.dispatchEvent(new CustomEvent('greenerry:page-ready', { detail: { root: currentBody } }));
    requestAnimationFrame(() => window.scrollTo({ top: 0, left: 0, behavior: 'auto' }));
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
