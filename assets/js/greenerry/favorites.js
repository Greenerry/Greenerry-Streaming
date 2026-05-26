function _favKey() {
  // Logged-in users and guests get separate localStorage keys.
  const userId = document.body?.dataset?.userId || '0';
  return userId !== '0' ? 'g_favs_u' + userId : 'g_favs_guest';
}

function favGet() {
  try {
    return JSON.parse(localStorage.getItem(_favKey()) || '[]');
  } catch {
    return [];
  }
}

function favSave(favs) {
  localStorage.setItem(_favKey(), JSON.stringify(favs));
}

function toggleFav() {
  if (!_cur || !_cur.id) return;

  // Logged-in users sync with MySQL; guests are kept locally until login.
  const isFav = favGet().some((fav) => fav.title === _cur.title && fav.artist === _cur.artist);
  const action = isFav ? 'remove' : 'add';

  if (_isLoggedIn()) {
    fetch(window.SITE_BASE + '/api/favorites.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: _csrfBody({ action, musicId: _cur.id })
    })
      .then((response) => response.json())
      .then(() => {
        const favs = favGet();
        const index = favs.findIndex((fav) => fav.title === _cur.title && fav.artist === _cur.artist);

        if (action === 'add' && index < 0) favs.unshift(_cur);
        if (action === 'remove' && index >= 0) favs.splice(index, 1);

        favSave(favs);
        _updateFavIcon();
        if (document.getElementById('favs-grid')) renderFavs();
      })
      .catch((error) => {
        if (window.DEBUG_GREENERRY) console.warn('Favorite error:', error);
      });
    return;
  }

  const favs = favGet();
  const index = favs.findIndex((fav) => fav.title === _cur.title && fav.artist === _cur.artist);

  if (action === 'add' && index < 0) {
    favs.unshift(_cur);
  } else if (action === 'remove' && index >= 0) {
    favs.splice(index, 1);
  }

  favSave(favs);
  _updateFavIcon();
  if (document.getElementById('favs-grid')) renderFavs();
  if (action === 'add') {
    toast(lang === 'pt' ? 'Guardado nos favoritos. Inicia sessao para sincronizar.' : 'Saved to favourites. Sign in to sync.');
    setTimeout(() => {
      window.location.href = (window.SITE_BASE || '') + '/pages/login.php?next=favourites.php';
    }, 650);
  } else {
    toast(lang === 'pt' ? 'Removido dos favoritos.' : 'Removed from favourites.');
  }
}

function _updateFavIcon() {
  const icon = document.getElementById('fav-icon');
  if (!icon || !_cur) return;

  const isFav = favGet().some((fav) => fav.title === _cur.title && fav.artist === _cur.artist);
  icon.setAttribute('fill', isFav ? '#e5383b' : 'none');
  icon.setAttribute('stroke', isFav ? '#e5383b' : 'currentColor');
}

/* Favourites page */
const FAVS_PER_PAGE = 10;
const FOLLOWING_PER_PAGE = 5;
let _favsCache = [];
let _favSearchBound = false;
let _followingSearchBound = false;

function _pageNumberFromUrl(key) {
  const value = Number(new URLSearchParams(window.location.search).get(key) || 1);
  return Number.isFinite(value) && value > 0 ? Math.floor(value) : 1;
}

function _libraryPageUrl(key, page) {
  const url = new URL(window.location.href);
  url.searchParams.set(key, page);
  return url.pathname + url.search + url.hash;
}

function _tr(key, fallback) {
  return T[lang]?.[key] || fallback;
}

function _escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  })[char]);
}

function _renderFavPager(total, currentPage) {
  const pager = document.getElementById('favs-pager');
  if (!pager) return;

  const totalPages = Math.max(1, Math.ceil(total / FAVS_PER_PAGE));
  if (totalPages <= 1) {
    pager.innerHTML = '';
    return;
  }

  const previous = currentPage > 1
    ? `<a class="btn btn-ghost btn-sm" href="${_libraryPageUrl('fav_page', currentPage - 1)}">${_tr('pagination_previous', 'Anterior')}</a>`
    : `<span class="btn btn-ghost btn-sm is-disabled">${_tr('pagination_previous', 'Anterior')}</span>`;
  const next = currentPage < totalPages
    ? `<a class="btn btn-ghost btn-sm" href="${_libraryPageUrl('fav_page', currentPage + 1)}">${_tr('pagination_next', 'Seguinte')}</a>`
    : `<span class="btn btn-ghost btn-sm is-disabled">${_tr('pagination_next', 'Seguinte')}</span>`;

  pager.innerHTML = `${previous}
    <span class="pager-status">${_tr('pagination_page', 'Pagina')} ${currentPage} ${_tr('pagination_of', 'de')} ${totalPages}</span>
    ${next}`;
}

function _favSearchQuery() {
  return (document.getElementById('favs-search')?.value || '').trim().toLowerCase();
}

function _bindFavSearch(grid) {
  const input = document.getElementById('favs-search');
  if (!input || _favSearchBound) return;

  // This search is client-side: it filters the favourites already loaded in the browser.
  input.value = new URLSearchParams(window.location.search).get('fav_q') || '';
  _favSearchBound = true;
  input.addEventListener('input', () => {
    const url = new URL(window.location.href);
    const query = input.value.trim();
    if (query) url.searchParams.set('fav_q', query);
    else url.searchParams.delete('fav_q');
    url.searchParams.set('fav_page', '1');
    history.replaceState(history.state, '', url.pathname + url.search + url.hash);
    _displayFavGrid(_favsCache, grid);
  });
}

function _renderFollowingPager(totalPages, currentPage) {
  const pager = document.getElementById('following-pager');
  if (!pager) return;

  if (totalPages <= 1) {
    pager.innerHTML = '';
    return;
  }

  const previous = currentPage > 1
    ? `<a class="btn btn-ghost btn-sm" href="${_libraryPageUrl('following_page', currentPage - 1)}">${_tr('pagination_previous', 'Anterior')}</a>`
    : `<span class="btn btn-ghost btn-sm is-disabled">${_tr('pagination_previous', 'Anterior')}</span>`;
  const next = currentPage < totalPages
    ? `<a class="btn btn-ghost btn-sm" href="${_libraryPageUrl('following_page', currentPage + 1)}">${_tr('pagination_next', 'Seguinte')}</a>`
    : `<span class="btn btn-ghost btn-sm is-disabled">${_tr('pagination_next', 'Seguinte')}</span>`;

  pager.innerHTML = `${previous}
    <span class="pager-status">${_tr('pagination_page', 'Pagina')} ${currentPage} ${_tr('pagination_of', 'de')} ${totalPages}</span>
    ${next}`;
}

function _followingQuery() {
  return (document.getElementById('following-search')?.value || '').trim();
}

function _bindFollowingSearch() {
  const input = document.getElementById('following-search');
  if (!input || _followingSearchBound) return;

  // This search is server-side through api/following.php, with a small delay while typing.
  input.value = new URLSearchParams(window.location.search).get('following_q') || '';
  _followingSearchBound = true;
  let timer = null;
  input.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
      const url = new URL(window.location.href);
      const query = input.value.trim();
      if (query) url.searchParams.set('following_q', query);
      else url.searchParams.delete('following_q');
      url.searchParams.set('following_page', '1');
      history.replaceState(history.state, '', url.pathname + url.search + url.hash);
      renderFollowing();
    }, 260);
  });
}

function renderFollowing() {
  const grid = document.getElementById('following-grid');
  if (!grid) return;
  _bindFollowingSearch();

  const empty = document.getElementById('following-empty');
  const searchEmpty = document.getElementById('following-search-empty');
  const query = _followingQuery();
  const page = _pageNumberFromUrl('following_page');
  const url = new URL((window.SITE_BASE || '') + '/api/following.php', window.location.origin);
  url.searchParams.set('page', page);
  url.searchParams.set('perPage', FOLLOWING_PER_PAGE);
  if (query) url.searchParams.set('q', query);

  fetch(url.toString())
    .then((response) => response.json())
    .then((result) => {
      const artists = Array.isArray(result.artists) ? result.artists : [];
      const currentPage = Number(result.page || 1);
      const totalPages = Number(result.totalPages || 1);

      if (_pageNumberFromUrl('following_page') !== currentPage) {
        history.replaceState(history.state, '', _libraryPageUrl('following_page', currentPage));
      }

      if (!artists.length) {
        grid.innerHTML = '';
        if (empty) empty.classList.toggle('is-hidden', !!query);
        if (searchEmpty) searchEmpty.classList.toggle('is-hidden', !query);
        _renderFollowingPager(1, 1);
        return;
      }

      empty?.classList.add('is-hidden');
      searchEmpty?.classList.add('is-hidden');
      grid.innerHTML = artists.map((artist) => {
        const name = _escapeHtml(artist.nome || '');
        const bio = _escapeHtml(artist.bio || '');
        const banner = _imgPath(artist.banner || '');
        const photo = _imgPath(artist.foto || '');
        const style = banner
          ? ` style="background-image:
              linear-gradient(180deg, rgba(7,9,13,.1), rgba(7,9,13,.18) 24%, rgba(7,9,13,.52) 72%, rgba(7,9,13,.76) 100%),
              linear-gradient(90deg, rgba(7,9,13,.34), rgba(7,9,13,.08) 58%, rgba(7,9,13,.3)),
              url('${_escapeHtml(banner)}');"`
          : '';

        const releaseCount = Number(artist.total_releases || 0);
        const trackCount = Number(artist.total_faixas || 0);
        const releaseLabel = releaseCount === 1
          ? (lang === 'en' ? 'release' : 'lançamento')
          : (lang === 'en' ? 'releases' : 'lançamentos');
        const trackLabel = trackCount === 1
          ? (lang === 'en' ? 'track' : 'faixa')
          : (lang === 'en' ? 'tracks' : 'faixas');

        return `<a href="artist.php?id=${Number(artist.idCliente || 0)}" class="artist-panel"${style}>
          <div class="artist-panel-body">
            <div class="avatar artist-panel-avatar">
              ${photo ? `<img src="${_escapeHtml(photo)}" alt="${name}">` : ''}
            </div>
            <div>
              <h3>${name}</h3>
              ${bio ? `<p>${bio}</p>` : ''}
            </div>
            <div class="artist-panel-stats">
              <span>${releaseCount} ${releaseLabel}</span>
              <span>${trackCount} ${trackLabel}</span>
            </div>
          </div>
        </a>`;
      }).join('');

      _renderFollowingPager(totalPages, currentPage);
      _registerMotion(grid);
    })
    .catch((error) => {
      if (window.DEBUG_GREENERRY) console.warn('Load following error:', error);
    });
}

function renderFavs() {
  const grid = document.getElementById('favs-grid');
  const empty = document.getElementById('favs-empty');
  if (!grid) return;
  _bindFavSearch(grid);

  if (!_isLoggedIn()) {
    const favs = favGet();
    if (!favs.length) {
      if (empty) empty.style.display = 'block';
      document.getElementById('favs-search-empty')?.classList.add('is-hidden');
      grid.innerHTML = '';
      _renderFavPager(0, 1);
      return;
    }

    _favsCache = favs;
    if (empty) empty.style.display = 'none';
    _displayFavGrid(favs, grid);
    return;
  }

  fetch(window.SITE_BASE + '/api/favorites.php?action=get')
    .then((response) => response.json())
    .then((favs) => {
      favSave(favs.map((fav) => ({
        id: fav.idMusica,
        title: fav.title,
        cover: fav.cover,
        artist: fav.artist,
        artistId: fav.artistId,
        artistFoto: fav.artistFoto,
        audio: fav.audio
      })));

      if (!favs.length) {
        if (empty) empty.style.display = 'block';
        document.getElementById('favs-search-empty')?.classList.add('is-hidden');
        grid.innerHTML = '';
        _renderFavPager(0, 1);
        return;
      }

      _favsCache = favs;
      if (empty) empty.style.display = 'none';
      _displayFavGrid(favs, grid);
    })
    .catch((error) => {
      if (window.DEBUG_GREENERRY) console.warn('Load favs error:', error);
      const favs = favGet();
      if (!favs.length) {
        if (empty) empty.style.display = 'block';
        document.getElementById('favs-search-empty')?.classList.add('is-hidden');
        grid.innerHTML = '';
        _renderFavPager(0, 1);
        return;
      }

      _favsCache = favs;
      if (empty) empty.style.display = 'none';
      _displayFavGrid(favs, grid);
    });
}

function _displayFavGrid(favs, grid) {
  const searchEmpty = document.getElementById('favs-search-empty');
  const query = _favSearchQuery();
  // Favourites search checks the track title and artist name in memory.
  const filteredFavs = query
    ? favs.filter((fav) => {
        const track = _norm(fav);
        return `${track.title || ''} ${track.artist || ''}`.toLowerCase().includes(query);
      })
    : favs;

  if (!filteredFavs.length) {
    grid.innerHTML = '';
    if (searchEmpty) searchEmpty.classList.toggle('is-hidden', !query);
    _renderFavPager(0, 1);
    updateFavBadge();
    return;
  }

  if (searchEmpty) searchEmpty.classList.add('is-hidden');
  const totalPages = Math.max(1, Math.ceil(filteredFavs.length / FAVS_PER_PAGE));
  let currentPage = Math.min(_pageNumberFromUrl('fav_page'), totalPages);
  if (_pageNumberFromUrl('fav_page') !== currentPage) {
    history.replaceState(history.state, '', _libraryPageUrl('fav_page', currentPage));
  }

  const visibleFavs = filteredFavs.slice((currentPage - 1) * FAVS_PER_PAGE, currentPage * FAVS_PER_PAGE);
  grid.innerHTML = visibleFavs.map((fav) => {
    const track = _norm(fav);
    const cover = _imgPath(track.cover);
    const title = (track.title || '').replace(/'/g, "\\'");
    const artist = (track.artist || '').replace(/'/g, "\\'");
    const artistPhoto = (track.artistFoto || '').replace(/'/g, "\\'");
    const audio = track.audioSrc || track.audio || '';
    const musicId = track.id || track.idMusica || 0;
    const playLabel = _escapeHtml(_tr('release_play_track', 'Play'));
    const removeLabel = _escapeHtml(_tr('remove', lang === 'pt' ? 'Remover' : 'Remove'));

    return `<div class="mcard" onclick="playTrack('${title}','${artist}','${track.cover || ''}','${audio}',${track.artistId || 0},'${artistPhoto}',${musicId})">
      <div class="cover">
        ${cover ? `<img src="${cover}" alt="" onerror="this.style.display='none'">` : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--bg4);"><div class="wv"><span></span><span></span><span></span><span></span><span></span></div></div>'}
        <div class="cover-ov"><button type="button" class="pbt">${playLabel}</button></div>
      </div>
      <div class="meta">
        <h4>${track.title || ''}</h4>
        <div class="sub" style="display:flex;align-items:center;justify-content:space-between;margin-top:4px;">
          <span style="font-size:.75rem;color:var(--text3);">${track.artist || ''}</span>
          <button onclick="event.stopPropagation();removeFav(${musicId})" style="background:transparent;border:none;cursor:pointer;padding:4px;" title="${removeLabel}" aria-label="${removeLabel}">
            <svg width="12" height="12" fill="#e5383b" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
          </button>
        </div>
      </div>
    </div>`;
  }).join('');

  _renderFavPager(filteredFavs.length, currentPage);
  updateFavBadge();
  _registerMotion(grid);
}

function removeFav(musicId) {
  if (!_isLoggedIn()) {
    const favs = favGet();
    const index = favs.findIndex((fav) => (fav.id || fav.idMusica) === musicId);
    if (index >= 0) favs.splice(index, 1);
    favSave(favs);
    renderFavs();
    updateFavBadge();
    return;
  }

  fetch(window.SITE_BASE + '/api/favorites.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: _csrfBody({ action: 'remove', musicId })
  })
    .then((response) => response.json())
    .then(() => {
      renderFavs();
      updateFavBadge();
    })
    .catch((error) => {
      if (window.DEBUG_GREENERRY) console.warn('Remove fav error:', error);
    });
}

function updateFavBadge() {
  const total = favGet().length;
  document.querySelectorAll('.fav-badge').forEach((el) => {
    el.textContent = total;
    el.style.display = total > 0 ? 'inline' : 'none';
  });
}

async function syncGuestFavorites() {
  if (!_isLoggedIn()) return;

  let guestFavs = [];
  try {
    guestFavs = JSON.parse(localStorage.getItem('g_favs_guest') || '[]');
  } catch {
    guestFavs = [];
  }

  if (!Array.isArray(guestFavs) || !guestFavs.length) return;

  const requests = guestFavs.map((fav) => {
    const musicId = Number(fav.id || fav.idMusica || 0);
    if (!musicId) return Promise.resolve();

    return fetch(window.SITE_BASE + '/api/favorites.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: _csrfBody({ action: 'add', musicId })
    }).catch((error) => {
      if (window.DEBUG_GREENERRY) console.warn('Guest favorite sync error:', error);
    });
  });

  await Promise.all(requests);
  localStorage.removeItem('g_favs_guest');
  if (document.getElementById('favs-grid')) renderFavs();
  updateFavBadge();
}

/* Queue and tracks */
