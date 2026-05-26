
function initArtistFilters(root = document) {
  // Artist profile filters hide/show releases or merch without reloading the page.
  root.querySelectorAll('[data-artist-filter]').forEach((button) => {
    if (button.dataset.artistFilterReady === '1') return;
    button.dataset.artistFilterReady = '1';

    button.addEventListener('click', () => {
      const group = button.getAttribute('data-artist-filter');
      const value = button.getAttribute('data-filter-value') || 'all';
      const itemAttribute = group === 'release' ? 'data-release-type-value' : 'data-merch-category-value';
      const scope = button.closest('.content-shell') || document;
      const grid = scope.querySelector(`[data-artist-filter-grid="${group}"]`);
      const empty = scope.querySelector(`[data-artist-empty="${group}"]`);
      if (!grid || !group) return;

      let visible = 0;

      scope.querySelectorAll(`[data-artist-filter="${group}"]`).forEach((item) => {
        item.classList.toggle('on', item === button);
      });

      grid.querySelectorAll('[data-filter-item]').forEach((item) => {
        const matches = value === 'all' || item.getAttribute(itemAttribute) === value;
        item.classList.toggle('is-hidden', !matches);
        if (matches) visible += 1;
      });

      empty?.classList.toggle('is-hidden', visible > 0);
    });
  });
}

function animateCatalogResults(host) {
  if (!host) return;

  host.classList.remove('catalog-results--enter');
  const items = host.querySelectorAll(
    '.music-catalog-grid > .mcard, .shop-catalog-grid > .mcard, .artist-grid-panels > .artist-panel, .catalog-empty-state, .pager'
  );

  items.forEach((item, index) => {
    item.style.setProperty('--catalog-delay', `${Math.min(index * 48, 420)}ms`);
  });

  window.requestAnimationFrame(() => {
    host.classList.add('catalog-results--enter');
  });
}

function initCatalogFilters(root = document) {
  root.querySelectorAll('form.catalog-filter[data-instant-filter]').forEach((form) => {
    if (form.dataset.instantFilterReady === '1') return;
    form.dataset.instantFilterReady = '1';

    const shell = form.closest('.content-shell') || form.closest('.wrap')?.parentElement;
    const resultsHost = shell?.querySelector('[data-catalog-results]');
    if (!resultsHost) return;

    const search = form.querySelector('input[name="q"]');
    const selects = Array.from(form.querySelectorAll('select'));
    let timer = null;
    let requestId = 0;

    const applyCatalogFilter = async () => {
      const params = new URLSearchParams(new FormData(form));
      const path = form.getAttribute('action') || window.location.pathname;
      const url = `${path}${params.toString() ? `?${params.toString()}` : ''}`;
      const current = `${window.location.pathname}${window.location.search}`;
      if (current === url) return;

      const activeRequest = ++requestId;
      resultsHost.classList.add('catalog-results--loading');

      try {
        const response = await fetch(url, { headers: { 'X-Requested-With': 'fetch' } });
        if (!response.ok || activeRequest !== requestId) return;

        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const nextResults = doc.querySelector('[data-catalog-results]');
        if (!nextResults || activeRequest !== requestId) return;

        resultsHost.innerHTML = nextResults.innerHTML;
        window.history.replaceState(null, '', url);
        animateCatalogResults(resultsHost);

        if (typeof applyDynamicLabels === 'function') {
          applyDynamicLabels(typeof lang !== 'undefined' ? lang : 'pt');
        }
        if (typeof _registerMotion === 'function') {
          _registerMotion(resultsHost);
        }
      } catch (error) {
        if (window.DEBUG_GREENERRY) console.warn('Catalog filter failed:', error);
      } finally {
        if (activeRequest === requestId) {
          resultsHost.classList.remove('catalog-results--loading');
        }
      }
    };

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      applyCatalogFilter();
    });

    search?.addEventListener('input', () => {
      window.clearTimeout(timer);
      timer = window.setTimeout(applyCatalogFilter, 350);
    });

    selects.forEach((select) => {
      select.addEventListener('change', applyCatalogFilter);
    });
  });
}

function initOrderFilters(root = document) {
  // Orders can be filtered by status buttons and by a text search box.
  const search = root.getElementById?.('orders-search') || document.getElementById('orders-search');
  const cards = Array.from(root.querySelectorAll?.('[data-order-card]') || document.querySelectorAll('[data-order-card]'));
  const buttons = Array.from(root.querySelectorAll?.('[data-order-filter]') || document.querySelectorAll('[data-order-filter]'));
  const empty = root.getElementById?.('orders-filter-empty') || document.getElementById('orders-filter-empty');
  if (!cards.length || !buttons.length) return;

  let activeStatus = 'all';

  const applyOrderFilters = () => {
    const query = (search?.value || '').trim().toLowerCase();
    let visible = 0;

    cards.forEach((card) => {
      const statusMatch = activeStatus === 'all' || card.dataset.orderStatus === activeStatus;
      const searchMatch = !query || (card.dataset.orderSearch || '').includes(query);
      const show = statusMatch && searchMatch;
      card.classList.toggle('is-hidden', !show);
      if (show) visible += 1;
    });

    empty?.classList.toggle('is-hidden', visible > 0);
  };

  buttons.forEach((button) => {
    if (button.dataset.orderFilterReady === '1') return;
    button.dataset.orderFilterReady = '1';
    button.addEventListener('click', () => {
      activeStatus = button.dataset.orderFilter || 'all';
      buttons.forEach((item) => item.classList.toggle('on', item === button));
      applyOrderFilters();
    });
  });

  if (search && search.dataset.orderSearchReady !== '1') {
    search.dataset.orderSearchReady = '1';
    search.addEventListener('input', applyOrderFilters);
  }
}

function initLibraryTabs(root = document) {
  const buttons = Array.from(root.querySelectorAll?.('[data-library-tab]') || document.querySelectorAll('[data-library-tab]'));
  const panels = Array.from(root.querySelectorAll?.('[data-library-panel]') || document.querySelectorAll('[data-library-panel]'));
  if (!buttons.length || !panels.length) return;

  buttons.forEach((button) => {
    if (button.dataset.libraryReady === '1') return;
    button.dataset.libraryReady = '1';
    button.addEventListener('click', () => {
      const active = button.dataset.libraryTab || 'tracks';
      buttons.forEach((item) => item.classList.toggle('on', item === button));
      panels.forEach((panel) => panel.classList.toggle('is-hidden', panel.dataset.libraryPanel !== active));
      if (active === 'artists' && document.getElementById('following-grid')) renderFollowing();
      if (active === 'tracks' && document.getElementById('favs-grid')) renderFavs();
    });
  });
}

function initFollowersModal(root = document) {
  // Moves follower modals to the body so overlays stack correctly.
  const modals = Array.from(root.querySelectorAll?.('[data-followers-modal]') || []);
  if (!modals.length) {
    const modal = document.querySelector('[data-followers-modal]');
    if (modal) modals.push(modal);
  }

  modals.forEach((modal) => {
    if (!modal || modal.dataset.followersReady === '1') return;
    modal.dataset.followersReady = '1';

    if (modal.parentElement !== document.body) {
      document.body.appendChild(modal);
    }

    const target = modal.id || '';
    const openButtons = target
      ? Array.from(document.querySelectorAll(`[data-open-followers="${target}"]`))
      : Array.from(document.querySelectorAll('[data-open-followers]'));
    const search = modal.querySelector('[data-followers-search]');
    const items = Array.from(modal.querySelectorAll('[data-follower-name]'));
    const empty = modal.querySelector('[data-followers-empty]');

    if (modal.classList.contains('is-open')) {
      document.body.classList.add('modal-open');
    }

    const open = () => {
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('modal-open');
      window.requestAnimationFrame(() => search?.focus());
    };

    const close = () => {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      if (!document.querySelector('[data-followers-modal].is-open')) {
        document.body.classList.remove('modal-open');
      }
    };

    openButtons.forEach((button) => button.addEventListener('click', open));
    modal.querySelectorAll('[data-close-followers]').forEach((button) => button.addEventListener('click', close));
    modal.querySelectorAll('.followers-list-item').forEach((link) => {
      link.addEventListener('click', close);
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
    });

    search?.addEventListener('input', () => {
      const query = search.value.trim().toLowerCase();
      let visible = 0;
      items.forEach((item) => {
        const show = !query || (item.dataset.followerName || '').includes(query);
        item.classList.toggle('is-hidden', !show);
        if (show) visible += 1;
      });
      empty?.classList.toggle('is-hidden', visible > 0);
    });
  });
}

function initArtistFollow(root = document) {
  const forms = Array.from(root.querySelectorAll?.('[data-follow-form]') || document.querySelectorAll('[data-follow-form]'));
  forms.forEach((form) => {
    if (form.dataset.followReady === '1') return;
    form.dataset.followReady = '1';

    const button = form.querySelector('[data-follow-button]');
    const label = button?.querySelector('[data-t]');
    const artistId = Number(form.dataset.artistId || 0);
    if (!button || !label || !artistId) return;

    const setFollowingState = (following) => {
      button.classList.toggle('btn-outline', following);
      button.classList.toggle('btn-dark', !following);
      label.dataset.t = following ? 'artist_following' : 'artist_follow';
      label.textContent = _tr(label.dataset.t, following ? 'A seguir' : 'Seguir artista');
    };

    const setCount = (selector, value) => {
      const target = document.querySelector(selector);
      const number = Number(value);
      if (target && Number.isFinite(number)) target.textContent = number;
    };

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (button.disabled) return;

      button.disabled = true;
      const previousFollowing = button.classList.contains('btn-outline');

      try {
        const response = await fetch((window.SITE_BASE || '') + '/api/toggle_follow.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: _csrfBody({ artist_id: artistId })
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
          throw new Error(result.error || 'Follow update failed.');
        }

        setFollowingState(!!result.following);
        setCount('[data-artist-followers-count]', result.followers);
        setCount('[data-artist-following-count]', result.followingCount);

        if (typeof toast === 'function' && result.message) {
          toast(result.message);
        }

        if (document.getElementById('following-grid')) {
          renderFollowing();
        }
      } catch (error) {
        setFollowingState(previousFollowing);
        if (typeof toast === 'function') {
          toast(error.message || _tr('error.order_update', 'Não foi possível atualizar.'));
        }
        if (window.DEBUG_GREENERRY) console.warn('Follow artist error:', error);
      } finally {
        button.disabled = false;
      }
    });
  });
}

function initImageFilePreviews(root = document) {
  const inputs = Array.from(root.querySelectorAll?.('input[type="file"][accept*=".jpg"], input[type="file"][accept*=".png"], input[type="file"][accept*=".webp"], input[type="file"][accept*="image"]') || []);
  inputs.forEach((input) => {
    if (input.dataset.previewReady === '1') return;
    const preview = root.querySelector?.(`[data-image-preview-for="${input.id}"]`) || document.querySelector(`[data-image-preview-for="${input.id}"]`);
    const image = preview?.querySelector('img');
    if (!preview || !image) return;

    input.dataset.previewReady = '1';
    let objectUrl = '';
    input.addEventListener('change', () => {
      if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = '';
      }

      const file = input.files?.[0];
      if (!file || !file.type.startsWith('image/')) {
        preview.hidden = true;
        image.removeAttribute('src');
        return;
      }

      objectUrl = URL.createObjectURL(file);
      image.src = objectUrl;
      preview.hidden = false;
    });
  });
}

function initNotificationMenus(root = document) {
  const menus = Array.from(root.querySelectorAll?.('[data-notifications-menu]') || document.querySelectorAll('[data-notifications-menu]'));
  menus.forEach((menu) => {
    if (menu.dataset.notificationsReady === '1') return;
    menu.dataset.notificationsReady = '1';

    const button = menu.querySelector('[data-notification-toggle]');
    const popover = menu.querySelector('.notification-popover');
    if (!button || !popover) return;

    const close = () => {
      popover.hidden = true;
      button.setAttribute('aria-expanded', 'false');
      menu.classList.remove('is-open');
      document.documentElement.classList.remove('notifications-open');
    };

    button.addEventListener('click', (event) => {
      event.stopPropagation();
      const nextOpen = popover.hidden;
      document.querySelectorAll('.notification-popover').forEach((item) => {
        if (item !== popover) item.hidden = true;
      });
      document.querySelectorAll('[data-notifications-menu]').forEach((item) => {
        if (item !== menu) item.classList.remove('is-open');
      });
      popover.hidden = !nextOpen;
      button.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
      menu.classList.toggle('is-open', nextOpen);
      document.documentElement.classList.toggle('notifications-open', nextOpen);
    });

    popover.addEventListener('click', (event) => event.stopPropagation());
    document.addEventListener('click', close);
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') close();
    });
  });
}

/* Page init */
async function _initPageContent() {
  // Called once on normal page load and again after soft navigation.
  initInputValidation();
  initCatalogFilters();
  document.querySelectorAll('[data-catalog-results]').forEach((host) => animateCatalogResults(host));
  initArtistFilters();
  initOrderFilters();
  initLibraryTabs();
  initFollowersModal();
  initArtistFollow();
  initImageFilePreviews();
  initNotificationMenus();
  await syncGuestFavorites();

  if (document.getElementById('favs-grid')) {
    renderFavs();
    updateFavBadge();
  }

  if (document.getElementById('following-grid')) {
    renderFollowing();
  }

  const tabs = document.querySelectorAll('.tab[data-tab]');
  if (!tabs.length) return;

  tabs.forEach((button) => {
    button.replaceWith(button.cloneNode(true));
  });

  document.querySelectorAll('.tab[data-tab]').forEach((button) => {
    button.addEventListener('click', function() {
      document.querySelectorAll('.tab').forEach((tab) => tab.classList.remove('on'));
      this.classList.add('on');

      ['edit', 'orders', 'music', 'merch'].forEach((tabId) => {
        const panel = document.getElementById('tab-' + tabId);
        if (panel) panel.style.display = this.dataset.tab === tabId ? 'block' : 'none';
      });
    });
  });
}

/* Init */
document.addEventListener('DOMContentLoaded', async () => {
  await loadTranslations();
  initThemeToggle();
  document.querySelectorAll('.lang button').forEach((button) => {
    button.addEventListener('click', () => setLang(button.dataset.l));
  });
  setLang(lang);
  _bindValidationToasts();
  _bindSoftNavigation();

  const nav = document.getElementById('main-nav');
  if (nav) {
    const syncNav = () => nav.classList.toggle('solid', window.scrollY > 10);
    window.addEventListener('scroll', syncNav, { passive: true });
    syncNav();
  }

  document.getElementById('pb-play')?.addEventListener('click', togglePlay);
  document.getElementById('sr-open-btn')?.addEventListener('click', openSr);

  const shuffleBtn = document.getElementById('pb-shuffle');
  if (shuffleBtn) {
    shuffleBtn.style.color = _shuffle ? 'var(--text)' : 'var(--text3)';
    shuffleBtn.addEventListener('click', toggleShuffle);
  }

  document.getElementById('pb-bar')?.addEventListener('click', function(e) {
    const percent = (e.clientX - this.getBoundingClientRect().left) / this.offsetWidth;
    const audio = document.getElementById('g-audio');

    if (audio?.duration) audio.currentTime = percent * audio.duration;
    else _fakeT = Math.floor(percent * _fakeDur);
  });

  document.getElementById('pb-vol-bar')?.addEventListener('click', function(e) {
    const percent = Math.max(0, Math.min(1, (e.clientX - this.getBoundingClientRect().left) / this.offsetWidth));
    const audio = document.getElementById('g-audio');
    if (audio) audio.volume = percent;
    _setFill('pb-vol-fill', percent * 100);
  });

  document.getElementById('ham')?.addEventListener('click', () => {
    const sidebar = document.getElementById('sl');
    if (sidebar && sidebar.classList.contains('open')) closeMobileSidebar();
    else openMobileSidebar();
  });

  document.getElementById('sl-overlay')?.addEventListener('click', closeMobileSidebar);

  document.addEventListener('click', (e) => {
    const link = e.target.closest('.sl-link');
    if (link && window.innerWidth <= 768) closeMobileSidebar();
  });

  document.querySelectorAll('.tog').forEach((toggle) => {
    toggle.addEventListener('click', () => toggle.classList.toggle('on'));
  });

  try {
    const saved = _readSavedState();

    if (saved?.title) {
      _cur = saved;
      _playing = !!saved.playing;
      _fakeT = Number(saved.currentTime || 0);
      _fakeDur = Number(saved.duration || 210);
      if (saved.queue?.length) _queue = saved.queue;

      await _loadTracks();

      _setText('pb-title', saved.title);
      _setText('pb-artist', saved.artist);
      _setText('np-track', saved.title);
      _setText('np-artist', saved.artist);

      _setCover('np-img', 'np-ph', saved.cover);
      _setCover('pb-img', 'pb-thumb-ph', saved.cover);

      const sidebarBody = document.getElementById('sr-body');
      const sidebarEmpty = document.getElementById('sr-empty');
      if (sidebarBody) sidebarBody.style.display = 'flex';
      if (sidebarEmpty) sidebarEmpty.style.display = 'none';
      if (typeof _syncPlayerLayoutVisible === 'function') {
        _syncPlayerLayoutVisible(true);
      } else {
        document.querySelector('.main')?.classList.add('pb-open');
        document.documentElement.classList.add('player-open');
      }
      const playerBar = document.getElementById('player-bar');
      if (playerBar) {
        playerBar.style.display = 'flex';
        playerBar.classList.toggle('sr-open', document.getElementById('sr')?.classList.contains('open'));
      }

      _renderQueue();
      _updatePlayBtn(_playing);

      const audio = document.getElementById('g-audio');
      const audioSrc = saved.audioSrc || saved.audio;
      if (audio && audioSrc) {
        audio.src = _audioPath(audioSrc);
        audio.preload = 'auto';
        if (saved.volume !== undefined) audio.volume = Number(saved.volume);
        _audioBound = false;
        _bindAudio(audio);

        const restoreAudio = () => {
          if (Number.isFinite(saved.currentTime)) {
            audio.currentTime = Math.min(Number(saved.currentTime), audio.duration || 9999);
          }

          _setFill('pb-fill', audio.duration ? (Number(saved.currentTime || 0) / audio.duration) * 100 : 0);
          _setText('pb-cur', _fmt(Number(saved.currentTime || 0)));
          _setText('pb-dur', _fmt(audio.duration || _fakeDur));
          if (_playing) audio.play().catch(() => {});
        };

        if (audio.readyState >= 1) restoreAudio();
        else audio.addEventListener('loadedmetadata', restoreAudio, { once: true });
      }

      const artistCard = document.getElementById('sr-artist-card');
      if (artistCard && saved.artistId) {
        artistCard.style.display = 'flex';
        _setText('sr-artist-name', saved.artist);

        const artistLink = document.getElementById('sr-artist-link');
        if (artistLink) artistLink.href = (window.SITE_BASE || '') + '/pages/artist.php?id=' + saved.artistId;

        const avatar = document.getElementById('sr-artist-avatar');
        if (avatar && saved.artistFoto) {
          const path = _imgPath(saved.artistFoto);
          avatar.innerHTML = path
            ? `<img src="${path}" style="width:100%;height:100%;object-fit:cover;" alt="">`
            : `<svg width="16" height="16" fill="var(--text3)" viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>`;
        }
      }

      _updateFavIcon();

      const sidebar = document.getElementById('sr');
      const button = document.getElementById('sr-open-btn');
      if (button && sidebar && !sidebar.classList.contains('open')) button.classList.add('visible');
    } else {
      await _loadTracks();
    }
  } catch (error) {
    console.warn('Restore failed:', error);
    await _loadTracks();
  }

  updateCartBadgeGlobal();
  _registerMotion();

  window.addEventListener('beforeunload', _saveState);
  window.addEventListener('pagehide', _saveState);
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') _saveState();
  });
  setInterval(_saveState, 2000);

  await _initPageContent();
});
