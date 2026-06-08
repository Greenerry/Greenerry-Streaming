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
  const icons = document.querySelectorAll('.fav-icon');
  if (!icons.length || !_cur) return;

  const isFav = favGet().some((fav) => fav.title === _cur.title && fav.artist === _cur.artist);
  icons.forEach((icon) => {
    icon.setAttribute('fill', isFav ? '#e5383b' : 'none');
    icon.setAttribute('stroke', isFav ? '#e5383b' : 'currentColor');
  });
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

let _playlistTargetTrack = 0;
let _playlistCache = [];
let _playlistSearch = '';
let _playlistIconRequest = 0;

const PLAYLIST_ADD_ICON = '<svg width="19" height="19" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>';
const PLAYLIST_SAVED_ICON = '<svg width="19" height="19" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12.4 2.6 2.6L16.5 9"/></svg>';

function _playlistText(pt, en) {
  return typeof commerceText === 'function' ? commerceText(pt, en) : ((lang || 'pt') === 'en' ? en : pt);
}

function _setPlaylistButtonsSaved(saved) {
  document.querySelectorAll('.player-playlist-btn').forEach((button) => {
    button.classList.toggle('is-saved', !!saved);
    button.innerHTML = saved ? PLAYLIST_SAVED_ICON : PLAYLIST_ADD_ICON;
    const label = saved
      ? _playlistText('Guardada em playlist', 'Saved in playlist')
      : _playlistText('Adicionar à playlist', 'Add to playlist');
    button.setAttribute('aria-label', label);
    button.title = label;
  });
}

async function refreshCurrentPlaylistButton(trackId = _cur?.id) {
  const currentTrackId = Number(trackId || 0);
  if (!currentTrackId || !_isLoggedIn()) {
    _setPlaylistButtonsSaved(false);
    return false;
  }

  const requestId = ++_playlistIconRequest;
  const url = new URL((window.SITE_BASE || '') + '/api/playlists.php', window.location.origin);
  url.searchParams.set('action', 'list');
  url.searchParams.set('trackId', String(currentTrackId));

  try {
    const response = await fetch(url.toString());
    const result = await response.json();
    if (requestId !== _playlistIconRequest) return false;
    const saved = Array.isArray(result.playlists) && result.playlists.some((playlist) => Number(playlist.in_playlist || 0) > 0);
    _setPlaylistButtonsSaved(saved);
    return saved;
  } catch (error) {
    if (window.DEBUG_GREENERRY) console.warn('Playlist icon state failed:', error);
    return false;
  }
}

async function _loadPlaylistCache() {
  const url = new URL((window.SITE_BASE || '') + '/api/playlists.php', window.location.origin);
  url.searchParams.set('action', 'list');
  if (_playlistTargetTrack) url.searchParams.set('trackId', _playlistTargetTrack);
  const response = await fetch(url.toString());
  const result = await response.json();
  _playlistCache = Array.isArray(result.playlists) ? result.playlists : [];
  return _playlistCache;
}

function _renderPlaylistPicker() {
  const list = document.getElementById('playlist-picker-list');
  if (!list) return;

  const query = _playlistSearch.trim().toLowerCase();
  const playlists = query
    ? _playlistCache.filter((playlist) => String(playlist.nome || '').toLowerCase().includes(query))
    : _playlistCache;

  if (!_playlistCache.length) {
    list.innerHTML = `<p class="color-text3">${_playlistText('Cria uma playlist para guardar esta musica.', 'Create a playlist to save this song.')}</p>`;
    return;
  }

  if (!playlists.length) {
    list.innerHTML = `<p class="color-text3">${_playlistText('Nenhuma playlist encontrada.', 'No playlists found.')}</p>`;
    return;
  }

  list.innerHTML = playlists.map((playlist) => {
    const saved = Number(playlist.in_playlist || 0) > 0;
    const count = Number(playlist.total_faixas || 0);
    const cover = playlist.capa ? _imgPath(playlist.capa) : '';
    return `
    <button type="button" class="playlist-picker-option ${saved ? 'is-saved' : ''}" data-playlist-id="${Number(playlist.idPlaylist)}" data-playlist-saved="${saved ? '1' : '0'}">
      <span class="playlist-picker-art">${cover ? `<img src="${_escapeHtml(cover)}" alt="">` : (saved ? '&#10003;' : '&#9835;')}</span>
      <span class="playlist-picker-copy"><strong>${_escapeHtml(playlist.nome || '')}</strong><small>${count} ${_playlistText(count === 1 ? 'faixa' : 'faixas', count === 1 ? 'track' : 'tracks')}</small></span>
      <span class="playlist-picker-check" aria-hidden="true">&#10003;</span>
    </button>
  `;
  }).join('');

  list.querySelectorAll('[data-playlist-id]').forEach((button) => {
    button.addEventListener('click', () => _toggleTrackInPlaylist(Number(button.dataset.playlistId || 0), button.dataset.playlistSaved === '1'));
  });
}

async function openPlaylistPicker(button) {
  _playlistTargetTrack = Number(button?.dataset?.trackId || 0);
  if (!_playlistTargetTrack) return;
  _playlistSearch = '';
  const search = document.getElementById('playlist-picker-search');
  if (search) search.value = '';
  await _loadPlaylistCache();
  _renderPlaylistPicker();
  document.getElementById('playlist-picker')?.showModal();
}

function openCurrentPlaylistPicker() {
  if (!_cur?.id) {
    toast(_playlistText('Escolhe uma musica primeiro.', 'Choose a song first.'));
    return;
  }

  openPlaylistPicker({ dataset: { trackId: String(_cur.id) } });
}

async function _toggleTrackInPlaylist(playlistId, saved) {
  const body = new URLSearchParams();
  body.set('action', saved ? 'remove_track' : 'add_track');
  body.set('playlistId', playlistId);
  body.set('trackId', _playlistTargetTrack);
  if (window.CSRF_TOKEN) body.set('csrf_token', window.CSRF_TOKEN);

  const response = await fetch((window.SITE_BASE || '') + '/api/playlists.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body.toString()
  });
  const result = await response.json();
  if (!result.success) {
    toast(result.error || 'Error');
    return;
  }

  await _loadPlaylistCache();
  _renderPlaylistPicker();
  if (_playlistTargetTrack && _cur?.id && Number(_cur.id) === Number(_playlistTargetTrack)) {
    _setPlaylistButtonsSaved(_playlistCache.some((playlist) => Number(playlist.in_playlist || 0) > 0));
  }
  if (!saved) toast(_playlistText('Musica adicionada a playlist.', 'Song added to playlist.'));
}

function initPlaylistPicker(root = document) {
  const createButton = root.getElementById?.('playlist-picker-create') || document.getElementById('playlist-picker-create');
  const search = root.getElementById?.('playlist-picker-search') || document.getElementById('playlist-picker-search');
  const newToggle = root.getElementById?.('playlist-picker-new-toggle') || document.getElementById('playlist-picker-new-toggle');
  const createRow = root.getElementById?.('playlist-picker-create-row') || document.getElementById('playlist-picker-create-row');
  const coverInput = root.getElementById?.('playlist-picker-cover') || document.getElementById('playlist-picker-cover');

  if (search && search.dataset.playlistReady !== '1') {
    search.dataset.playlistReady = '1';
    search.addEventListener('input', () => {
      _playlistSearch = search.value || '';
      _renderPlaylistPicker();
    });
  }

  if (newToggle && newToggle.dataset.playlistReady !== '1') {
    newToggle.dataset.playlistReady = '1';
    newToggle.addEventListener('click', () => {
      if (!createRow) return;
      createRow.hidden = !createRow.hidden;
      if (!createRow.hidden) document.getElementById('playlist-picker-new')?.focus();
    });
  }

  if (coverInput && coverInput.dataset.playlistReady !== '1') {
    coverInput.dataset.playlistReady = '1';
    coverInput.addEventListener('change', () => {
      const field = coverInput.closest('.playlist-cover-field');
      const file = coverInput.files?.[0];
      if (!field || !file || !file.type.startsWith('image/')) return;
      if (field.dataset.previewUrl) URL.revokeObjectURL(field.dataset.previewUrl);
      const previewUrl = URL.createObjectURL(file);
      field.dataset.previewUrl = previewUrl;
      field.style.backgroundImage = `url("${previewUrl}")`;
      field.classList.add('has-preview');
    });
  }

  if (!createButton || createButton.dataset.playlistReady === '1') return;
  createButton.dataset.playlistReady = '1';
  createButton.addEventListener('click', async () => {
    const input = document.getElementById('playlist-picker-new');
    const name = (input?.value || '').trim();
    if (!name) return;

    const cover = document.getElementById('playlist-picker-cover');
    const body = new FormData();
    body.set('action', 'create');
    body.set('name', name);
    if (window.CSRF_TOKEN) body.set('csrf_token', window.CSRF_TOKEN);
    if (cover?.files?.[0]) body.set('cover', cover.files[0]);

    const response = await fetch((window.SITE_BASE || '') + '/api/playlists.php', {
      method: 'POST',
      body
    });
    const result = await response.json();

    if (result.success) {
      if (input) input.value = '';
      if (cover) {
        const field = cover.closest('.playlist-cover-field');
        if (field?.dataset.previewUrl) URL.revokeObjectURL(field.dataset.previewUrl);
        if (field) {
          field.style.removeProperty('background-image');
          field.classList.remove('has-preview');
          delete field.dataset.previewUrl;
        }
        cover.value = '';
      }
      if (createRow) createRow.hidden = true;
      await _loadPlaylistCache();
      _renderPlaylistPicker();
      return;
    }

    toast(result.error || 'Error');
  });
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
    window.__greenerryFavTracks = [];
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
  window.__greenerryFavTracks = filteredFavs.map((fav) => {
    const track = _norm(fav);
    return {
      id: track.id || track.idMusica || 0,
      title: track.title || '',
      artist: track.artist || '',
      cover: track.cover || '',
      audio: track.audioSrc || track.audio || '',
      artistId: track.artistId || 0,
      artistFoto: track.artistFoto || ''
    };
  }).filter((track) => track.audio);
  const rows = visibleFavs.map((fav, index) => {
    const track = _norm(fav);
    const cover = _imgPath(track.cover);
    const musicId = track.id || track.idMusica || 0;
    const removeLabel = _escapeHtml(_tr('remove', lang === 'pt' ? 'Remover' : 'Remove'));
    const collectionIndex = ((currentPage - 1) * FAVS_PER_PAGE) + index;

    return `<div class="library-track-row">
      <span class="library-track-index">${((currentPage - 1) * FAVS_PER_PAGE) + index + 1}</span>
      <button type="button" class="library-track-main" onclick="playCurrentFavCollection(${collectionIndex})">
        <span class="library-track-cover">${cover ? `<img src="${cover}" alt="" onerror="this.style.display='none'">` : ''}</span>
        <span><strong>${_escapeHtml(track.title || '')}</strong><small>${_escapeHtml(track.artist || '')}</small></span>
      </button>
      <span>${_playlistText('Músicas curtidas', 'Liked Songs')}</span>
      <span>${_playlistText('Guardada', 'Saved')}</span>
      <button type="button" class="cart-remove-btn" onclick="event.stopPropagation();removeFav(${musicId})" title="${removeLabel}" aria-label="${removeLabel}">${removeLabel}</button>
    </div>`;
  }).join('');

  grid.innerHTML = `<div class="library-track-head"><span>#</span><span>${_playlistText('Titulo', 'Title')}</span><span>Album</span><span>${_playlistText('Adicionada', 'Date added')}</span><span></span></div>${rows}`;

  _renderFavPager(filteredFavs.length, currentPage);
  updateFavBadge();
  _registerMotion(grid);
}

function playCurrentFavCollection(startIndex = 0) {
  const tracks = Array.isArray(window.__greenerryFavTracks) ? window.__greenerryFavTracks : [];
  if (tracks.length && typeof playTrackCollection === 'function') {
    playTrackCollection(tracks, startIndex);
    return;
  }
  document.querySelector('#favs-grid .library-track-main')?.click();
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
