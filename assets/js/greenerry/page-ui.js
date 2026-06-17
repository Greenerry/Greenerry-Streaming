// Frontend script purpose: Adds small page interactions and layout polish.
// Keep browser behavior small, readable, and reusable.

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
  const pager = root.querySelector?.('[data-orders-pager]') || document.querySelector('[data-orders-pager]');
  const pageSize = Math.max(1, parseInt(pager?.dataset.ordersPageSize || '6', 10) || 6);
  if (!cards.length || !buttons.length) return;

  let activeStatus = 'all';
  let currentPage = 1;

  const orderText = (key, fallback) => (typeof _tr === 'function' ? _tr(key, fallback) : fallback);

  const renderOrderPager = (totalPages) => {
    if (!pager) return;

    if (totalPages <= 1) {
      pager.innerHTML = '';
      pager.classList.add('is-hidden');
      return;
    }

    pager.classList.remove('is-hidden');
    pager.innerHTML = `
      <button type="button" class="btn btn-ghost btn-sm ${currentPage <= 1 ? 'is-disabled' : ''}" data-order-page="${currentPage - 1}" ${currentPage <= 1 ? 'disabled' : ''} data-t="pagination_previous">${orderText('pagination_previous', 'Anterior')}</button>
      <span class="pager-status"><span data-t="pagination_page">${orderText('pagination_page', 'Pagina')}</span> ${currentPage} <span data-t="pagination_of">${orderText('pagination_of', 'de')}</span> ${totalPages}</span>
      <button type="button" class="btn btn-ghost btn-sm ${currentPage >= totalPages ? 'is-disabled' : ''}" data-order-page="${currentPage + 1}" ${currentPage >= totalPages ? 'disabled' : ''} data-t="pagination_next">${orderText('pagination_next', 'Seguinte')}</button>
    `;

    pager.querySelectorAll('[data-order-page]').forEach((button) => {
      button.addEventListener('click', () => {
        const targetPage = parseInt(button.dataset.orderPage || '1', 10);
        if (!Number.isFinite(targetPage) || targetPage === currentPage) return;
        currentPage = Math.max(1, Math.min(totalPages, targetPage));
        applyOrderFilters();
      });
    });
  };

  const applyOrderFilters = () => {
    const query = normalizeSearchText(search?.value || '');
    const matchingCards = cards.filter((card) => {
      const statusMatch = activeStatus === 'all' || card.dataset.orderStatus === activeStatus;
      const searchMatch = !query || normalizeSearchText(card.dataset.orderSearch || card.textContent || '').includes(query);
      return statusMatch && searchMatch;
    });
    const totalPages = Math.max(1, Math.ceil(matchingCards.length / pageSize));
    currentPage = Math.min(currentPage, totalPages);
    const start = pager ? (currentPage - 1) * pageSize : 0;
    const pagedCards = new Set(pager ? matchingCards.slice(start, start + pageSize) : matchingCards);

    cards.forEach((card) => {
      const show = pagedCards.has(card);
      card.classList.toggle('is-hidden', !show);
    });

    empty?.classList.toggle('is-hidden', matchingCards.length > 0);
    renderOrderPager(totalPages);
  };

  buttons.forEach((button) => {
    if (button.dataset.orderFilterReady === '1') return;
    button.dataset.orderFilterReady = '1';
    button.addEventListener('click', () => {
      activeStatus = button.dataset.orderFilter || 'all';
      currentPage = 1;
      buttons.forEach((item) => item.classList.toggle('on', item === button));
      applyOrderFilters();
    });
  });

  if (search && search.dataset.orderSearchReady !== '1') {
    search.dataset.orderSearchReady = '1';
    search.addEventListener('input', () => {
      currentPage = 1;
      applyOrderFilters();
    });
  }

  applyOrderFilters();
}

function initOrderActionForms(root = document) {
  const forms = Array.from(root.querySelectorAll?.('.order-actions-form') || document.querySelectorAll('.order-actions-form'));
  forms.forEach((form) => {
    if (form.dataset.ajaxOrderActionReady === '1') return;
    form.dataset.ajaxOrderActionReady = '1';

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const submitter = event.submitter || form.querySelector('button[type="submit"]');
      if (submitter?.disabled) return;

      const data = new FormData(form);
      if (submitter?.name) data.set(submitter.name, submitter.value || '');
      const buttons = Array.from(form.querySelectorAll('button'));
      buttons.forEach((button) => { button.disabled = true; });

      try {
        const response = await fetch(form.action || window.location.href, {
          method: 'POST',
          body: data,
          headers: { 'X-Requested-With': 'fetch' }
        });
        if (!response.ok) throw new Error('order update failed');

        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const nextFilterBar = doc.querySelector('.orders-filter-bar');
        const nextList = doc.querySelector('#orders-list');
        const nextEmpty = doc.querySelector('#orders-filter-empty');
        const currentFilterBar = document.querySelector('.orders-filter-bar');
        const currentList = document.querySelector('#orders-list');
        const currentEmpty = document.querySelector('#orders-filter-empty');

        if (!nextList || !currentList) {
          window.location.reload();
          return;
        }

        if (currentFilterBar && nextFilterBar) currentFilterBar.replaceWith(nextFilterBar);
        currentList.replaceWith(nextList);
        if (currentEmpty && nextEmpty) currentEmpty.replaceWith(nextEmpty);

        initOrderFilters(document);
        initOrderAccordions(document);
        initOrderActionForms(document);
        initOrderMessageForms(document);
        if (typeof applyDynamicLabels === 'function') {
          applyDynamicLabels(typeof lang !== 'undefined' ? lang : 'pt');
        }
      } catch (error) {
        if (window.DEBUG_GREENERRY) console.warn('Order update failed:', error);
        form.submit();
      } finally {
        buttons.forEach((button) => { button.disabled = false; });
      }
    });
  });
}

function scrollToOrderDetails(details) {
  const target = details.querySelector('.order-delivery-card, .buyer-order-item, .simple-list, .order-actions-bar, .order-accordion-body') || details;
  const navOffset = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nav'), 10) || 68;
  const top = Math.max(0, window.scrollY + target.getBoundingClientRect().top - navOffset - 18);
  const scroller = document.scrollingElement || document.documentElement;

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    window.scrollTo(0, top);
    return;
  }

  if (typeof window.anime === 'function') {
    window.anime({
      targets: scroller,
      scrollTop: top,
      duration: 780,
      easing: 'easeInOutQuart'
    });
    return;
  }

  window.scrollTo({ top, behavior: 'smooth' });
}

function initOrderAccordions(root = document) {
  const orders = Array.from(root.querySelectorAll?.('[data-order-card]') || document.querySelectorAll('[data-order-card]'));
  orders.forEach((details) => {
    if (details.dataset.orderAccordionReady === '1') return;
    details.dataset.orderAccordionReady = '1';

    details.addEventListener('toggle', () => {
      const body = details.querySelector('.order-accordion-body');
      if (!details.open || !body) return;

      body.style.overflow = 'hidden';
      body.style.height = '0px';
      body.style.opacity = '0';
      body.style.transform = 'translateY(14px)';
      body.style.willChange = 'height, opacity, transform';

      requestAnimationFrame(() => {
        const fullHeight = body.scrollHeight;
        if (typeof window.anime === 'function' && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
          window.anime({
            targets: body,
            height: [0, fullHeight],
            opacity: [0, 1],
            translateY: [14, 0],
            duration: 560,
            easing: 'easeOutExpo',
            complete: () => {
              body.style.height = '';
              body.style.overflow = '';
              body.style.opacity = '';
              body.style.transform = '';
              body.style.willChange = '';
            }
          });
          return;
        }

        body.style.height = '';
        body.style.overflow = '';
        body.style.opacity = '';
        body.style.transform = '';
        body.style.willChange = '';
      });
    });
  });
}

function initLibraryTabs(root = document) {
  const buttons = Array.from(root.querySelectorAll?.('[data-library-tab]') || document.querySelectorAll('[data-library-tab]'));
  const playlistButtons = Array.from(root.querySelectorAll?.('[data-library-open-playlist]') || document.querySelectorAll('[data-library-open-playlist]'));
  const panels = Array.from(root.querySelectorAll?.('[data-library-panel]') || document.querySelectorAll('[data-library-panel]'));
  const search = root.querySelector?.('.library-main-search input') || document.querySelector('.library-main-search input');
  const tiles = Array.from(root.querySelectorAll?.('.library-spotify-grid .playlist-card, .library-spotify-grid .library-empty-tile') || document.querySelectorAll('.library-spotify-grid .playlist-card, .library-spotify-grid .library-empty-tile'));
  if ((!buttons.length && !playlistButtons.length) || !panels.length) return;
  const libraryShell = root.querySelector?.('.library-shell') || document.querySelector('.library-shell');

  const activatePanel = (active) => {
    buttons.forEach((item) => item.classList.toggle('on', item.dataset.libraryTab === active));
    panels.forEach((panel) => panel.classList.toggle('is-hidden', panel.dataset.libraryPanel !== active));
    if (libraryShell) {
      libraryShell.dataset.libraryActivePanel = active;
      libraryShell.classList.toggle('is-playlist-open', String(active).startsWith('playlist-'));
    }
    search?.closest('.library-main-search')?.classList.toggle('is-hidden', active !== 'playlists');
    if (active === 'artists' && document.getElementById('following-grid')) renderFollowing();
    if (active === 'tracks' && document.getElementById('favs-grid')) renderFavs();
    window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
  };

  buttons.forEach((button) => {
    if (button.dataset.libraryReady === '1') return;
    button.dataset.libraryReady = '1';
    button.addEventListener('click', () => {
      activatePanel(button.dataset.libraryTab || 'tracks');
    });
  });

  playlistButtons.forEach((button) => {
    if (button.dataset.libraryPlaylistReady === '1') return;
    button.dataset.libraryPlaylistReady = '1';
    button.addEventListener('click', () => {
      const playlistId = button.dataset.libraryOpenPlaylist;
      if (playlistId) activatePanel(`playlist-${playlistId}`);
    });
  });

  if (search && search.dataset.librarySearchReady !== '1') {
    search.dataset.librarySearchReady = '1';
    search.addEventListener('input', () => {
      const query = search.value.trim().toLowerCase();
      tiles.forEach((tile) => {
        tile.classList.toggle('is-hidden', query && !tile.textContent.toLowerCase().includes(query));
      });
    });
  }
}

function initPlaylistTrackRemoval(root = document) {
  const forms = Array.from(root.querySelectorAll?.('[data-playlist-remove-form]') || document.querySelectorAll('[data-playlist-remove-form]'));
  forms.forEach((form) => {
    if (form.dataset.ajaxRemoveReady === '1') return;
    form.dataset.ajaxRemoveReady = '1';

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const playlistId = form.dataset.playlistId || form.querySelector('[name="playlist_id"]')?.value || '';
      const trackId = form.dataset.trackId || form.querySelector('[name="track_id"]')?.value || '';
      if (!playlistId || !trackId) return;

      const button = form.querySelector('button');
      if (button) button.disabled = true;

      const body = new URLSearchParams();
      body.set('action', 'remove_track');
      body.set('playlistId', playlistId);
      body.set('trackId', trackId);
      if (window.CSRF_TOKEN) body.set('csrf_token', window.CSRF_TOKEN);

      try {
        const response = await fetch((window.SITE_BASE || '') + '/api/playlists.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: body.toString()
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok || result.error) throw new Error(result.error || 'Remove failed');

        const row = form.closest('[data-playlist-track-row]');
        const list = row?.closest('.library-track-list');
        row?.classList.add('is-removing');
        window.setTimeout(() => {
          row?.remove();
          const section = list?.closest('[data-playlist-id]');
          const playlistIdForRows = section?.dataset.playlistId || playlistId;
          if (section?.dataset.playlistTracks) {
            try {
              const tracks = JSON.parse(section.dataset.playlistTracks || '[]').filter((track) => String(track.id || '') !== String(trackId));
              section.dataset.playlistTracks = JSON.stringify(tracks);
            } catch {}
          }
          list?.querySelectorAll('[data-playlist-track-row]').forEach((item, index) => {
            item.dataset.playlistIndex = String(index);
            item.querySelector('.library-track-index').textContent = String(index + 1);
            item.querySelector('.library-track-main')?.setAttribute('onclick', `playLibraryPlaylist(${playlistIdForRows}, ${index})`);
          });
        }, 160);
      } catch (error) {
        if (button) button.disabled = false;
        if (typeof toast === 'function') {
          toast(error.message || _tr('error.order_update', 'Não foi possível atualizar.'));
        }
      }
    });
  });
}

function initLibraryPlaylistCreate(root = document) {
  const forms = Array.from(root.querySelectorAll?.('.library-create-popover') || document.querySelectorAll('.library-create-popover'));
  forms.forEach((form) => {
    if (form.dataset.ajaxPlaylistCreateReady === '1') return;
    form.dataset.ajaxPlaylistCreateReady = '1';

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const nameInput = form.querySelector('input[name="playlist_name"]');
      const coverInput = form.querySelector('input[name="playlist_cover"]');
      const name = (nameInput?.value || '').trim();
      if (!name) {
        nameInput?.focus();
        return;
      }

      const button = form.querySelector('button[type="submit"]');
      button?.setAttribute('disabled', 'disabled');

      const body = new FormData();
      body.set('action', 'create');
      body.set('name', name);
      const csrf = form.querySelector('input[name="csrf_token"]')?.value || window.CSRF_TOKEN || '';
      if (csrf) body.set('csrf_token', csrf);
      if (coverInput?.files?.[0]) body.set('cover', coverInput.files[0]);

      try {
        const response = await fetch((window.SITE_BASE || '') + '/api/playlists.php', {
          method: 'POST',
          body
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.success) throw new Error(result.error || 'playlist create failed');

        const page = await fetch(window.location.href, { headers: { 'X-Requested-With': 'fetch' } });
        const html = await page.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const nextShell = doc.querySelector('.library-shell');
        const currentShell = document.querySelector('.library-shell');
        if (!nextShell || !currentShell) {
          window.location.reload();
          return;
        }

        currentShell.replaceWith(nextShell);
        initLibraryTabs(nextShell);
        initPlaylistTrackRemoval(nextShell);
        initLibraryPlaylistCreate(nextShell);
        initPlaylistCoverFields(nextShell);
        if (typeof applyDynamicLabels === 'function') {
          applyDynamicLabels(typeof lang !== 'undefined' ? lang : 'pt');
        }
      } catch (error) {
        if (typeof toast === 'function') {
          toast(error.message || 'Error');
        } else {
          form.submit();
        }
      } finally {
        button?.removeAttribute('disabled');
      }
    });
  });
}

function playLibraryPlaylist(playlistId, startIndex = 0) {
  const section = document.querySelector(`[data-playlist-id="${playlistId}"]`);
  if (!section?.dataset.playlistTracks || typeof playTrackCollection !== 'function') return;

  try {
    const tracks = JSON.parse(section.dataset.playlistTracks || '[]');
    playTrackCollection(tracks, startIndex);
  } catch (error) {
    if (window.DEBUG_GREENERRY) console.warn('Playlist play failed:', error);
  }
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

function initPlaylistCoverFields(root = document) {
  const inputs = Array.from(root.querySelectorAll?.('.playlist-cover-field input[type="file"]') || document.querySelectorAll('.playlist-cover-field input[type="file"]'));
  inputs.forEach((input) => {
    if (input.dataset.playlistCoverReady === '1') return;
    input.dataset.playlistCoverReady = '1';

    const field = input.closest('.playlist-cover-field');
    const form = input.closest('form');
    const clear = form?.querySelector('[data-clear-playlist-cover]');
    if (!field) return;

    const clearPreview = () => {
      if (field.dataset.previewUrl) URL.revokeObjectURL(field.dataset.previewUrl);
      delete field.dataset.previewUrl;
      field.style.removeProperty('background-image');
      field.classList.remove('has-preview');
      input.value = '';
      if (clear) clear.hidden = true;
    };

    input.addEventListener('change', () => {
      const file = input.files?.[0];
      if (field.dataset.previewUrl) URL.revokeObjectURL(field.dataset.previewUrl);
      if (!file || !file.type.startsWith('image/')) {
        clearPreview();
        return;
      }

      const previewUrl = URL.createObjectURL(file);
      field.dataset.previewUrl = previewUrl;
      field.style.backgroundImage = `url("${previewUrl}")`;
      field.classList.add('has-preview');
      if (clear) clear.hidden = false;
    });

    clear?.addEventListener('click', clearPreview);
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
      if (nextOpen && window.innerWidth <= 768 && popover.parentElement !== document.body) {
        document.body.appendChild(popover);
        popover.classList.add('notification-popover--mobile-fixed');
      }
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

function animateWithAnime(targets, options = {}) {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (typeof window.anime === 'function') {
    window.anime({ targets, ...options });
    return;
  }

  const elements = typeof targets === 'string'
    ? Array.from(document.querySelectorAll(targets))
    : Array.from(targets instanceof Element ? [targets] : (targets || []));

  elements.forEach((element) => {
    element.animate([
      { opacity: options.opacity?.[0] ?? 0, transform: `translateY(${options.translateY?.[0] ?? 8}px)` },
      { opacity: options.opacity?.[1] ?? 1, transform: `translateY(${options.translateY?.[1] ?? 0}px)` }
    ], {
      duration: options.duration || 260,
      easing: 'cubic-bezier(.22,1,.36,1)',
      fill: 'both'
    });
  });
}

function animateElementsOnce(targets, options = {}) {
  const elements = Array.from(document.querySelectorAll(targets)).filter((element) => {
    if (element.dataset.animeReady === '1') return false;
    element.dataset.animeReady = '1';
    return true;
  });
  if (!elements.length) return;

  animateWithAnime(elements, options);
}

function initAccountMenus(root = document) {
  const menus = Array.from(root.querySelectorAll?.('[data-account-menu]') || document.querySelectorAll('[data-account-menu]'));
  menus.forEach((menu) => {
    if (menu.dataset.accountReady === '1') return;
    menu.dataset.accountReady = '1';

    const button = menu.querySelector('[data-account-toggle]');
    const popover = menu.querySelector('.account-popover');
    if (!button || !popover) return;

    const close = () => {
      popover.hidden = true;
      button.setAttribute('aria-expanded', 'false');
      menu.classList.remove('is-open');
    };

    button.addEventListener('click', (event) => {
      event.stopPropagation();
      const nextOpen = popover.hidden;
      document.querySelectorAll('.account-popover').forEach((item) => {
        if (item !== popover) item.hidden = true;
      });
      popover.hidden = !nextOpen;
      button.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
      menu.classList.toggle('is-open', nextOpen);
      if (nextOpen) {
        animateWithAnime('.account-popover:not([hidden])', {
          opacity: [0, 1],
          translateY: [-6, 0],
          scale: [.98, 1],
          duration: 190,
          easing: 'easeOutCubic'
        });
      }
    });

    popover.addEventListener('click', (event) => event.stopPropagation());
    document.addEventListener('click', close);
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') close();
    });
  });
}

function initArtistModeSidebar(root = document) {
  const buttons = Array.from(root.querySelectorAll?.('[data-artist-mode-toggle]') || document.querySelectorAll('[data-artist-mode-toggle]'));
  buttons.forEach((button) => {
    if (button.dataset.artistModeReady === '1') return;
    button.dataset.artistModeReady = '1';

    button.addEventListener('click', () => {
      const leavingArtist = document.body.classList.contains('artist-sidebar-mode');
      const targetPage = leavingArtist ? 'index.php' : 'artist_dashboard.php';
      const targetUrl = new URL(`${window.SITE_BASE || ''}/pages/${targetPage}`, window.location.origin);
      const softNavigate = window._softNavigate || (typeof _softNavigate === 'function' ? _softNavigate : null);
      const navigate = () => {
        if (softNavigate) {
          softNavigate(targetUrl);
          return;
        }
        window.location.assign(targetUrl.href);
      };

      button.disabled = true;
      document.body.classList.add('artist-mode-switching');
      document.querySelectorAll('[data-artist-mode-toggle]').forEach((item) => {
        item.setAttribute('aria-pressed', leavingArtist ? 'false' : 'true');
      });

      animateWithAnime(button, {
        scale: [1, 0.97],
        duration: 180,
        easing: 'easeOutCubic'
      });

      animateWithAnime('.sl, .nav, .page-body', {
        opacity: [1, 0],
        translateY: [0, 8],
        duration: 190,
        easing: 'easeInCubic'
      });

      window.setTimeout(navigate, 185);
    });
  });
}

function initStreamSidebarToggle(root = document) {
  const button = root.querySelector?.('#sl-peek') || document.getElementById('sl-peek');
  if (!button || button.dataset.streamSidebarReady === '1') return;
  button.dataset.streamSidebarReady = '1';

  const storageKey = 'g_stream_sidebar_expanded_v6';
  const apply = (expanded) => {
    document.body.classList.toggle('sidebar-expanded', expanded);
    button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    button.setAttribute('aria-label', expanded ? 'Collapse sidebar' : 'Expand sidebar');
    button.title = expanded ? 'Collapse sidebar' : 'Expand sidebar';
  };

  apply(localStorage.getItem(storageKey) === '1');

  button.addEventListener('click', () => {
    const expanded = !document.body.classList.contains('sidebar-expanded');
    localStorage.setItem(storageKey, expanded ? '1' : '0');
    apply(expanded);
  });
}

function initAnimeEnhancements(root = document) {
  animateElementsOnce('.nav-right > *, .sl-brand', {
    opacity: [0, 1],
    translateY: [-8, 0],
    delay: window.anime ? window.anime.stagger(35) : 0,
    duration: 420,
    easing: 'easeOutExpo'
  });

  animateElementsOnce('.page-intro, .catalog-hero, .library-spotify-head, .product-hero, .artist-hero-content, .submission-hero, .cart-page-shell > .wrap > h1', {
    opacity: [0, 1],
    translateY: [14, 0],
    duration: 520,
    easing: 'easeOutExpo'
  });

  animateElementsOnce('.mcard, .product-card, .artist-card, .library-tile, .order-card, .buyer-order-item, .upload-music-page .surface-card, .upload-merch-page .surface-card', {
    opacity: [0, 1],
    translateY: [12, 0],
    delay: window.anime ? window.anime.stagger(28, { start: 80 }) : 0,
    duration: 420,
    easing: 'easeOutCubic'
  });
}

function initNavControls(root = document) {
  const scope = root?.querySelectorAll ? root : document;
  scope.querySelectorAll('.lang button').forEach((button) => {
    if (button.dataset.langReady === '1') return;
    button.dataset.langReady = '1';
    button.addEventListener('click', () => setLang(button.dataset.l));
  });

  const nowPlayingButton = document.getElementById('sr-open-btn');
  if (nowPlayingButton && nowPlayingButton.dataset.nowPlayingReady !== '1') {
    nowPlayingButton.dataset.nowPlayingReady = '1';
    nowPlayingButton.addEventListener('click', openSr);
  }
}

function normalizeSearchText(value = '') {
  return String(value)
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();
}

function initArtistSearch(root = document) {
  const scopeRoot = root?.querySelectorAll ? root : document;
  scopeRoot.querySelectorAll('[data-artist-search]').forEach((input) => {
    if (input.dataset.artistSearchReady === '1') return;
    input.dataset.artistSearchReady = '1';

    const filter = () => {
      const target = input.dataset.artistSearch;
      const scope = target ? document.querySelector(`[data-artist-search-scope="${target}"]`) : document;
      const rows = Array.from(scope?.querySelectorAll('[data-artist-search-row]') || []);
      const empty = scope?.querySelector('[data-artist-search-empty]');
      const query = normalizeSearchText(input.value.trim());
      let visible = 0;

      rows.forEach((row) => {
        const matches = query === '' || normalizeSearchText(row.textContent).includes(query);
        row.classList.toggle('is-hidden', !matches);
        if (matches) visible += 1;
      });

      empty?.classList.toggle('is-hidden', visible > 0 || query === '');
    };

    input.addEventListener('input', filter);
    filter();
  });
}

function initOrderMessageForms(root = document) {
  const forms = Array.from(root.querySelectorAll?.('.order-message-form') || document.querySelectorAll('.order-message-form'));
  forms.forEach((form) => {
    if (form.dataset.ajaxMessageReady === '1') return;
    form.dataset.ajaxMessageReady = '1';
    const textarea = form.querySelector('textarea[name="message"]');

    textarea?.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter' || event.shiftKey) return;
      event.preventDefault();
      form.requestSubmit();
    });

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const message = (textarea?.value || '').trim();
      if (!message) return;
      const button = form.querySelector('button[type="submit"]');
      button?.setAttribute('disabled', 'disabled');

      try {
        const response = await fetch(form.action || window.location.href, {
          method: 'POST',
          body: new FormData(form),
          headers: { 'X-Requested-With': 'fetch' }
        });
        if (!response.ok) throw new Error('message failed');

        let thread = form.parentElement?.querySelector('.order-message-thread');
        if (!thread) {
          thread = document.createElement('div');
          thread.className = 'order-message-thread';
          form.before(thread);
        }
        const bubble = document.createElement('div');
        bubble.className = 'order-message-bubble from-me';
        const label = form.querySelector('input[name="action"][value="reply_message"]')
          ? (typeof _tr === 'function' ? _tr('message_you', 'Tu') : 'Tu')
          : (typeof _tr === 'function' ? _tr('message_you', 'Tu') : 'Tu');
        bubble.innerHTML = `<span>${label}</span><p>${message.replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]).replace(/\n/g, '<br>')}</p>`;
        thread.appendChild(bubble);
        textarea.value = '';
        bubble.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      } catch (error) {
        if (window.DEBUG_GREENERRY) console.warn('Order message failed:', error);
        form.submit();
      } finally {
        button?.removeAttribute('disabled');
      }
    });
  });
}

/* Page init */
async function _initPageContent() {
  // Called once on normal page load and again after soft navigation.
  initInputValidation();
  initNavControls();
  initArtistSearch();
  initCatalogFilters();
  document.querySelectorAll('[data-catalog-results]').forEach((host) => animateCatalogResults(host));
  initArtistFilters();
  initOrderFilters();
  initOrderMessageForms();
  initOrderActionForms();
  initOrderAccordions();
  initLibraryTabs();
  initPlaylistTrackRemoval();
  initLibraryPlaylistCreate();
  if (typeof initPlaylistPicker === 'function') initPlaylistPicker();
  initPlaylistCoverFields();
  initFollowersModal();
  initArtistFollow();
  initImageFilePreviews();
  initNotificationMenus();
  initAccountMenus();
  initArtistModeSidebar();
  initStreamSidebarToggle();
  initAnimeEnhancements();
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
  initNavControls();
  initStreamSidebarToggle();
  setLang(lang);
  _bindValidationToasts();
  _bindSoftNavigation();
  _bindScrollState();

  const nav = document.getElementById('main-nav');
  if (nav) {
    const syncNav = () => nav.classList.toggle('solid', window.scrollY > 10);
    window.addEventListener('scroll', syncNav, { passive: true });
    syncNav();
  }

  document.getElementById('pb-play')?.addEventListener('click', togglePlay);

  const shuffleBtn = document.getElementById('pb-shuffle');
  if (shuffleBtn) {
    shuffleBtn.style.color = _shuffle ? 'var(--text)' : 'var(--text3)';
    shuffleBtn.addEventListener('click', toggleShuffle);
  }
  const loopBtn = document.getElementById('pb-loop');
  if (loopBtn) {
    loopBtn.classList.toggle('on', !!_loop);
    loopBtn.setAttribute('aria-pressed', _loop ? 'true' : 'false');
    loopBtn.style.color = _loop ? 'var(--text)' : 'var(--text3)';
    loopBtn.addEventListener('click', toggleLoop);
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

  if (document.body.dataset.mobileMenuReady !== '1') {
    document.body.dataset.mobileMenuReady = '1';
    document.addEventListener('click', (event) => {
      const menuButton = event.target.closest('#ham');
      if (!menuButton) return;
      event.preventDefault();
      const sidebar = document.getElementById('sl');
      if (sidebar && sidebar.classList.contains('open')) closeMobileSidebar();
      else openMobileSidebar();
    });
    document.addEventListener('click', (event) => {
      if (event.target.closest('#sl-overlay')) closeMobileSidebar();
    });
  }

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
      if (Array.isArray(saved.history)) _history = saved.history;
      _loop = !!saved.loop;
      if (Array.isArray(saved.contextTracks)) _contextTracks = saved.contextTracks;
      _contextIndex = Number.isFinite(Number(saved.contextIndex)) ? Number(saved.contextIndex) : -1;
      _contextMode = saved.contextMode === 'collection' ? 'collection' : 'global';

      await _loadTracks();

      _setPlayerTitle('pb-title', saved.title);
      _setText('pb-artist', saved.artist);
      _setPlayerTitle('np-track', saved.title);
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
      if (typeof refreshCurrentPlaylistButton === 'function') {
        refreshCurrentPlaylistButton(saved.id);
      }

      const sidebar = document.getElementById('sr');
      const button = document.getElementById('sr-open-btn');
      if (button && sidebar && !sidebar.classList.contains('open')) {
        button.classList.add('visible');
      }
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
