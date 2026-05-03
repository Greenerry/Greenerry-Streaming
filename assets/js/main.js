/* GREENERRY - main.js */

/* Path helpers */
function _imgPath(file) {
  if (!file) return '';
  if (file.startsWith('http') || file.startsWith('/')) return file;
  if (file.startsWith('../assets/')) return window.SITE_BASE + '/' + file.substring(3);
  return window.SITE_BASE + '/assets/img/' + file;
}

function _audioPath(file) {
  if (!file) return '';
  if (file.startsWith('http') || file.startsWith('/')) return file;
  if (file.startsWith('../assets/')) return window.SITE_BASE + '/' + file.substring(3);
  return window.SITE_BASE + '/assets/audio/' + file;
}

function _norm(track) {
  if (!track) return track;
  if (track.audio && !track.audioSrc) track.audioSrc = track.audio;
  if (track.audioSrc && !track.audio) track.audio = track.audioSrc;
  return track;
}

/* Translations */
let T = { pt: {}, en: {} };
let lang = localStorage.getItem('g_lang') || 'pt';
const PLAYER_STATE_KEY = 'g_track';
const THEME_KEY = 'g_theme';

function initThemeToggle() {
  const current = localStorage.getItem(THEME_KEY) || document.documentElement.dataset.theme || 'dark';
  document.documentElement.dataset.theme = current;

  document.querySelectorAll('#theme-toggle, [data-theme-toggle]').forEach((button) => {
    button.setAttribute('aria-pressed', current === 'light' ? 'true' : 'false');
    button.addEventListener('click', () => {
      const next = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';
      document.documentElement.dataset.theme = next;
      localStorage.setItem(THEME_KEY, next);
      document.cookie = `g_theme=${encodeURIComponent(next)}; path=/; max-age=31536000; samesite=lax`;
      document.querySelectorAll('#theme-toggle, [data-theme-toggle]').forEach((item) => {
        item.setAttribute('aria-pressed', next === 'light' ? 'true' : 'false');
      });
    });
  });
}

function _readEmbeddedTranslations() {
  const el = document.getElementById('greenerry-translations');
  if (!el) return null;

  try {
    return JSON.parse(el.textContent || '{}');
  } catch (error) {
    console.warn('Embedded translations failed:', error);
    return null;
  }
}

async function loadTranslations() {
  const embedded = _readEmbeddedTranslations();
  if (embedded?.pt || embedded?.en) {
    T = embedded;
    return;
  }

  try {
    const response = await fetch(window.SITE_BASE + '/assets/js/translations.json');
    T = await response.json();
  } catch (error) {
    console.warn('Translations failed:', error);
  }
}

function setLang(nextLang) {
  if (!Object.keys(T.pt || {}).length && !Object.keys(T.en || {}).length) {
    const embedded = _readEmbeddedTranslations();
    if (embedded?.pt || embedded?.en) T = embedded;
  }

  lang = nextLang;
  localStorage.setItem('g_lang', nextLang);
  document.cookie = `g_lang=${encodeURIComponent(nextLang)}; path=/; max-age=31536000; samesite=lax`;
  document.documentElement.lang = nextLang;

  document.querySelectorAll('[data-t]').forEach((el) => {
    if (T[nextLang]?.[el.dataset.t]) el.textContent = T[nextLang][el.dataset.t];
  });

  document.querySelectorAll('[data-tp]').forEach((el) => {
    if (T[nextLang]?.[el.dataset.tp]) el.placeholder = T[nextLang][el.dataset.tp];
  });

  document.querySelectorAll('.lang button').forEach((button) => {
    button.classList.toggle('on', button.dataset.l === nextLang);
  });

  window.dispatchEvent(new CustomEvent('greenerry:langchange', {
    detail: { lang: nextLang }
  }));
}

window.GreenerrySetLang = setLang;

/* Motion */
const _prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
let _motionObserver = null;

function _motionGroups(root = document) {
  return [
    { selector: '.home-hero-copy', type: 'hero-left', step: 0 },
    { selector: '.home-hero-side', type: 'hero-right', step: 0 },
    { selector: '.catalog-hero', type: 'hero-left', step: 0 },
    { selector: '.artist-hero-content > *', type: 'soft', step: 90 },
    { selector: '.auth-card', type: 'zoom', step: 0 },
    { selector: '.hero-card--single', type: 'hero-left', step: 0 },
    { selector: '.product-hero > *', type: 'soft', step: 110 },
    { selector: '.admin-summary-grid > *', type: 'soft', step: 90 },
    { selector: '.stats-grid > *', type: 'soft', step: 70 },
    { selector: '.admin-status-grid > *', type: 'soft', step: 60 },
    { selector: '.admin-chart-col', type: 'soft', step: 45 },
    { selector: '.grid > *', type: 'soft', step: 60 },
    { selector: '.grid-art > *', type: 'soft', step: 60 },
    { selector: '.artist-grid-panels > *', type: 'soft', step: 85 },
    { selector: '.admin-card-list > *', type: 'soft', step: 65 },
    { selector: '.cart-list > *', type: 'soft', step: 65 },
    { selector: '.orders-list > *', type: 'soft', step: 70 },
    { selector: '.surface-card', type: 'soft', step: 0 },
    { selector: '.acard-box', type: 'soft', step: 0 },
    { selector: '.tbl-wrap', type: 'soft', step: 0 }
  ];
}

function _ensureMotionObserver() {
  if (_motionObserver || _prefersReducedMotion.matches) return;

  _motionObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('motion-in');
      _motionObserver.unobserve(entry.target);
    });
  }, {
    threshold: 0.14,
    rootMargin: '0px 0px -8% 0px'
  });
}

function _registerMotion(root = document) {
  if (_prefersReducedMotion.matches) return;

  document.body?.classList.add('motion-enabled');
  _ensureMotionObserver();

  _motionGroups(root).forEach((group) => {
    root.querySelectorAll(group.selector).forEach((element, index) => {
      if (element.hasAttribute('data-motion')) return;

      element.setAttribute('data-motion', group.type);
      element.style.setProperty('--motion-delay', `${index * group.step}ms`);
      _motionObserver?.observe(element);
    });
  });
}

/* Login check */
function _isLoggedIn() {
  return (document.body?.dataset?.userId || '0') !== '0';
}

function _labelForField(field) {
  const id = field.id;
  if (id) {
    const label = document.querySelector(`label[for="${id}"]`);
    if (label) return label.textContent.trim().replace(/\s+/g, ' ');
  }

  return field.getAttribute('aria-label')
    || field.getAttribute('placeholder')
    || field.name
    || (lang === 'pt' ? 'Este campo' : 'This field');
}

function _validationMessage(field) {
  const fieldName = _labelForField(field);
  const value = (field.value || '').trim();

  if (field.validity.valueMissing) {
    if (field.tagName === 'SELECT') {
      return lang === 'pt'
        ? `Seleciona ${fieldName.toLowerCase()}.`
        : `Select ${fieldName.toLowerCase()}.`;
    }

    if (field.type === 'file') {
      return lang === 'pt'
        ? `Escolhe ${fieldName.toLowerCase()}.`
        : `Choose ${fieldName.toLowerCase()}.`;
    }

    return lang === 'pt'
      ? `${fieldName} e obrigatorio.`
      : `${fieldName} is required.`;
  }

  if (field.validity.typeMismatch) {
    if (field.type === 'email') {
      return lang === 'pt'
        ? 'Indica um email valido.'
        : 'Enter a valid email.';
    }

    return lang === 'pt'
      ? `${fieldName} nao e valido.`
      : `${fieldName} is not valid.`;
  }

  if (field.validity.tooShort) {
    return lang === 'pt'
      ? `${fieldName} precisa de pelo menos ${field.minLength} caracteres.`
      : `${fieldName} needs at least ${field.minLength} characters.`;
  }

  if (field.validity.tooLong) {
    return lang === 'pt'
      ? `${fieldName} nao pode ter mais de ${field.maxLength} caracteres.`
      : `${fieldName} cannot have more than ${field.maxLength} characters.`;
  }

  if (field.validity.rangeUnderflow) {
    if (field.type === 'number' && field.min) {
      return lang === 'pt'
        ? `${fieldName} tem de ser pelo menos ${field.min}.`
        : `${fieldName} must be at least ${field.min}.`;
    }

    return lang === 'pt'
      ? `${fieldName} esta abaixo do minimo.`
      : `${fieldName} is below the minimum.`;
  }

  if (field.validity.rangeOverflow) {
    if (field.type === 'number' && field.max) {
      return lang === 'pt'
        ? `${fieldName} nao pode ser maior que ${field.max}.`
        : `${fieldName} cannot be greater than ${field.max}.`;
    }

    return lang === 'pt'
      ? `${fieldName} esta acima do maximo.`
      : `${fieldName} is above the maximum.`;
  }

  if (field.validity.patternMismatch) {
    return lang === 'pt'
      ? `${fieldName} nao tem o formato certo.`
      : `${fieldName} has the wrong format.`;
  }

  if (field.validity.badInput || (field.type === 'number' && value && Number.isNaN(Number(value)))) {
    return lang === 'pt'
      ? `Indica um valor valido para ${fieldName.toLowerCase()}.`
      : `Enter a valid value for ${fieldName.toLowerCase()}.`;
  }

  return field.validationMessage || (lang === 'pt' ? 'Verifica este campo.' : 'Check this field.');
}

function _bindValidationToasts() {
  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;

    if (form.checkValidity()) return;

    event.preventDefault();
    const invalidField = form.querySelector(':invalid');
    if (!invalidField) return;

    toast(_validationMessage(invalidField));
    invalidField.focus({ preventScroll: false });
  }, true);

  const pageAlert = document.querySelector('.alert-err, .alert-ok');
  if (pageAlert?.textContent?.trim()) {
    setTimeout(() => toast(pageAlert.textContent.trim()), 120);
  }
}

/* Favourites */
function _favKey() {
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

  const isFav = favGet().some((fav) => fav.title === _cur.title && fav.artist === _cur.artist);
  const action = isFav ? 'remove' : 'add';

  if (_isLoggedIn()) {
    fetch(window.SITE_BASE + '/api/favorites.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=' + action + '&musicId=' + _cur.id
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
      .catch((error) => console.log('Favorite error:', error));
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
  toast(lang === 'pt' ? 'Guardado localmente. Faz login para sincronizar!' : 'Saved locally. Sign in to sync!');
}

function _updateFavIcon() {
  const icon = document.getElementById('fav-icon');
  if (!icon || !_cur) return;

  const isFav = favGet().some((fav) => fav.title === _cur.title && fav.artist === _cur.artist);
  icon.setAttribute('fill', isFav ? '#e5383b' : 'none');
  icon.setAttribute('stroke', isFav ? '#e5383b' : 'currentColor');
}

/* Favourites page */
function renderFavs() {
  const grid = document.getElementById('favs-grid');
  const empty = document.getElementById('favs-empty');
  if (!grid) return;

  if (!_isLoggedIn()) {
    const favs = favGet();
    if (!favs.length) {
      if (empty) empty.style.display = 'block';
      grid.innerHTML = '';
      return;
    }

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
        grid.innerHTML = '';
        return;
      }

      if (empty) empty.style.display = 'none';
      _displayFavGrid(favs, grid);
    })
    .catch((error) => {
      console.log('Load favs error:', error);
      const favs = favGet();
      if (!favs.length) {
        if (empty) empty.style.display = 'block';
        grid.innerHTML = '';
        return;
      }

      if (empty) empty.style.display = 'none';
      _displayFavGrid(favs, grid);
    });
}

function _displayFavGrid(favs, grid) {
  grid.innerHTML = favs.map((fav) => {
    const track = _norm(fav);
    const cover = _imgPath(track.cover);
    const title = (track.title || '').replace(/'/g, "\\'");
    const artist = (track.artist || '').replace(/'/g, "\\'");
    const artistPhoto = (track.artistFoto || '').replace(/'/g, "\\'");
    const audio = track.audioSrc || track.audio || '';
    const musicId = track.id || track.idMusica || 0;

    return `<div class="mcard" onclick="playTrack('${title}','${artist}','${track.cover || ''}','${audio}',${track.artistId || 0},'${artistPhoto}',${musicId})">
      <div class="cover">
        ${cover ? `<img src="${cover}" alt="" onerror="this.style.display='none'">` : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--bg4);"><div class="wv"><span></span><span></span><span></span><span></span><span></span></div></div>'}
        <div class="cover-ov"><button class="pbt">▶</button></div>
      </div>
      <div class="meta">
        <h4>${track.title || ''}</h4>
        <div class="sub" style="display:flex;align-items:center;justify-content:space-between;margin-top:4px;">
          <span style="font-size:.75rem;color:var(--text3);">${track.artist || ''}</span>
          <button onclick="event.stopPropagation();removeFav(${musicId})" style="background:transparent;border:none;cursor:pointer;padding:4px;" title="Remover">
            <svg width="12" height="12" fill="#e5383b" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
          </button>
        </div>
      </div>
    </div>`;
  }).join('');

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
    body: 'action=remove&musicId=' + musicId
  })
    .then((response) => response.json())
    .then(() => {
      renderFavs();
      updateFavBadge();
    })
    .catch((error) => console.log('Remove fav error:', error));
}

function updateFavBadge() {
  const total = favGet().length;
  document.querySelectorAll('.fav-badge').forEach((el) => {
    el.textContent = total;
    el.style.display = total > 0 ? 'inline' : 'none';
  });
}

/* Queue and tracks */
let _cur = null;
let _allTracks = [];
let _queue = [];
let _shuffle = true;

async function _loadTracks() {
  _allTracks = [];

  try {
    const response = await fetch(window.SITE_BASE + '/api/tracks.php');
    if (response.ok) {
      _allTracks = ((await response.json()) || []).map(_norm).filter((track) => track?.audio);
      if (_allTracks.length) return;
    }
  } catch {}

  document.querySelectorAll('[data-track]').forEach((el) => {
    try {
      const track = _norm(JSON.parse(el.dataset.track));
      if (track?.audio) _allTracks.push(track);
    } catch {}
  });
}

function _sameTrack(a, b) {
  return !!a && !!b && (String(a.id || '') === String(b.id || '') || (a.audio && b.audio && a.audio === b.audio));
}

function _pickRandomTrack(excluded = []) {
  const pool = _allTracks.filter((track) => !excluded.some((item) => _sameTrack(item, track)));
  const source = pool.length ? pool : _allTracks;
  if (!source.length) return null;
  return source[Math.floor(Math.random() * source.length)];
}

function _fillRandomQueue(minItems = 3) {
  if (!_allTracks.length) return;

  while (_queue.length < minItems) {
    const excluded = _cur ? [_cur, ..._queue] : [..._queue];
    let track = _pickRandomTrack(excluded);
    if (!track) return;

    if (_queue.some((item) => _sameTrack(item, track)) || (_cur && _sameTrack(_cur, track))) {
      const fallback = _allTracks.find((item) => !excluded.some((excludedTrack) => _sameTrack(excludedTrack, item)));
      if (!fallback) return;
      track = fallback;
    }

    _queue.push(track);
  }
}

function _renderQueue() {
  const list = document.getElementById('queue-list');
  if (!list) return;

  _fillRandomQueue(3);

  if (!_queue.length) {
    list.innerHTML = '';
    return;
  }

  list.innerHTML = _queue.slice(0, 3).map((track) => {
    const cover = _imgPath(track.cover);
    const title = (track.title || '').replace(/'/g, '&#39;');
    const artist = (track.artist || '').replace(/'/g, '&#39;');
    const artistPhoto = (track.artistFoto || '').replace(/'/g, '&#39;');

    return `<div class="qi" onclick="playTrack('${title}','${artist}','${track.cover || ''}','${track.audio || ''}',${track.artistId || 0},'${artistPhoto}',${track.id || 0})">
      <div class="qi-thumb">${cover ? `<img src="${cover}" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none'">` : ''}</div>
      <div class="qi-info">
        <div class="qi-title">${track.title || ''}</div>
        <div class="qi-sub">${track.artist || ''}</div>
      </div>
    </div>`;
  }).join('');
}

/* Navigation */
async function nextTrack() {
  if (!_allTracks.length) await _loadTracks();
  _fillRandomQueue(1);
  const track = _queue.shift();
  if (track) playTrack(track.title, track.artist, track.cover, track.audio, track.artistId, track.artistFoto, track.id, { keepQueue: true });
}

async function prevTrack() {
  if (!_allTracks.length) await _loadTracks();
  const track = _pickRandomTrack(_cur ? [_cur] : []);
  if (track) playTrack(track.title, track.artist, track.cover, track.audio, track.artistId, track.artistFoto, track.id, { keepQueue: true });
}

async function playReleaseByKey(key) {
  if (!_allTracks.length) await _loadTracks();
  const tracks = _allTracks.filter((track) => track.releaseKey === key);
  if (!tracks.length) return;
  _queue = tracks.slice(1);
  _fillRandomQueue(3);
  const track = tracks[0];
  playTrack(track.title, track.artist, track.cover, track.audio, track.artistId, track.artistFoto, track.id, { keepQueue: true });
}

/* Shuffle */
function toggleShuffle() {
  _shuffle = !_shuffle;
  _queue = [];
  _renderQueue();

  const button = document.getElementById('pb-shuffle');
  if (button) button.style.color = _shuffle ? 'var(--text)' : 'var(--text3)';
}

/* Right sidebar */
function openSr() {
  document.getElementById('sr')?.classList.add('open');
  document.querySelector('.main')?.classList.add('sr-open', 'pb-open');
  document.getElementById('main-nav')?.classList.add('sr-open');

  const playerBar = document.getElementById('player-bar');
  if (playerBar) {
    playerBar.style.display = 'flex';
    playerBar.classList.add('sr-open');
  }

  const button = document.getElementById('sr-open-btn');
  if (button) button.classList.remove('visible');
}

function closeSr() {
  document.getElementById('sr')?.classList.remove('open');
  document.querySelector('.main')?.classList.remove('sr-open');
  document.getElementById('main-nav')?.classList.remove('sr-open');
  document.getElementById('player-bar')?.classList.remove('sr-open');

  const button = document.getElementById('sr-open-btn');
  if (button) button.classList.toggle('visible', !!_cur);
}

/* Mobile sidebar */
function openMobileSidebar() {
  document.getElementById('sl')?.classList.add('open');
  document.getElementById('sl-overlay')?.classList.add('visible');

  const playerBar = document.getElementById('player-bar');
  if (playerBar) playerBar.style.display = 'none';
}

function closeMobileSidebar() {
  const sidebar = document.getElementById('sl');
  const overlay = document.getElementById('sl-overlay');
  if (sidebar) sidebar.classList.remove('open');
  if (overlay) overlay.classList.remove('visible');

  const playerBar = document.getElementById('player-bar');
  if (playerBar && (playerBar.classList.contains('sr-open') || _cur)) {
    playerBar.style.display = 'flex';
  }
}

/* Play a track */
async function playTrack(title, artist, cover, audioSrc, artistId, artistFoto, musicId, options = {}) {
  _cur = { id: musicId, title, artist, cover, audioSrc, audio: audioSrc, artistId, artistFoto };

  if (!_allTracks.length) await _loadTracks();

  if (!options.keepQueue) {
    _queue = [];
  } else {
    _queue = _queue.filter((track) => !_sameTrack(track, _cur));
  }

  const sidebarBody = document.getElementById('sr-body');
  const sidebarEmpty = document.getElementById('sr-empty');
  if (sidebarBody) sidebarBody.style.display = 'flex';
  if (sidebarEmpty) sidebarEmpty.style.display = 'none';

  const playerBar = document.getElementById('player-bar');
  if (playerBar) playerBar.style.display = 'flex';

  _setText('np-track', title);
  _setText('np-artist', artist);
  _setText('pb-title', title);
  _setText('pb-artist', artist);

  _setCover('np-img', 'np-ph', cover);
  _setCover('pb-img', 'pb-thumb-ph', cover);

  const artistCard = document.getElementById('sr-artist-card');
  if (artistCard) {
    artistCard.style.display = 'flex';
    _setText('sr-artist-name', artist);

    const artistLink = document.getElementById('sr-artist-link');
    if (artistLink && artistId) artistLink.href = (window.SITE_BASE || '') + '/pages/artist.php?id=' + artistId;

    const avatar = document.getElementById('sr-artist-avatar');
    if (avatar) {
      const photo = _imgPath(artistFoto);
      avatar.innerHTML = photo
        ? `<img src="${photo}" style="width:100%;height:100%;object-fit:cover;" alt="">`
        : `<svg width="16" height="16" fill="var(--text3)" viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>`;
    }
  }

  _updateFavIcon();
  _renderQueue();

  const reopenButton = document.getElementById('sr-open-btn');
  if (reopenButton) {
    reopenButton.classList.add('visible');
    reopenButton.style.display = 'flex';
  } else {
    setTimeout(() => {
      const delayedButton = document.getElementById('sr-open-btn');
      if (!delayedButton) return;
      delayedButton.classList.add('visible');
      delayedButton.style.display = 'flex';
    }, 50);
  }

  const audio = document.getElementById('g-audio');
  clearInterval(_fakeTimer);

  if (audio && audioSrc) {
    _audioBound = false;
    audio.src = _audioPath(audioSrc);
    audio.play().catch((error) => console.warn('Audio play:', error));
    _playing = true;
    _updatePlayBtn(true);
    _bindAudio(audio);
  } else {
    _playing = true;
    _updatePlayBtn(true);
    _startFake();
  }

  _saveState();
  _renderQueue();
}

function _setCover(imgId, placeholderId, cover) {
  const image = document.getElementById(imgId);
  const placeholder = document.getElementById(placeholderId);
  if (!image) return;

  const path = _imgPath(cover);
  if (path) {
    image.src = path;
    image.style.display = 'block';
    if (placeholder) placeholder.style.display = 'none';
    return;
  }

  image.style.display = 'none';
  if (placeholder) placeholder.style.display = 'flex';
}

/* Audio engine */
let _playing = false;
let _fakeT = 0;
let _fakeDur = 210;
let _fakeTimer = null;
let _audioBound = false;

function _bindAudio(audio) {
  if (_audioBound) return;
  _audioBound = true;

  audio.ontimeupdate = () => {
    if (!audio.duration) return;
    _setFill('pb-fill', (audio.currentTime / audio.duration) * 100);
    _setText('pb-cur', _fmt(audio.currentTime));
    _setText('pb-dur', _fmt(audio.duration));
    _saveState();
  };

  audio.onended = () => {
    _playing = false;
    _updatePlayBtn(false);
    nextTrack();
  };
}

function _startFake() {
  _fakeT = 0;
  _fakeTimer = setInterval(() => {
    if (!_playing) return;

    _fakeT = Math.min(_fakeT + 1, _fakeDur);
    _setFill('pb-fill', (_fakeT / _fakeDur) * 100);
    _setText('pb-cur', _fmt(_fakeT));
    _setText('pb-dur', _fmt(_fakeDur));

    if (_fakeT >= _fakeDur) {
      clearInterval(_fakeTimer);
      _playing = false;
      _updatePlayBtn(false);
      nextTrack();
    }
  }, 1000);
}

function togglePlay() {
  const audio = document.getElementById('g-audio');
  const hasAudio = audio?.src && !audio.src.endsWith(window.location.pathname);

  if (hasAudio) {
    if (audio.paused) audio.play();
    else audio.pause();
    _playing = !audio.paused;
  } else {
    _playing = !_playing;
    if (_playing) _startFake();
    else clearInterval(_fakeTimer);
  }

  _updatePlayBtn(_playing);
}

function _updatePlayBtn(isPlaying) {
  const button = document.getElementById('pb-play');
  if (!button) return;

  button.innerHTML = isPlaying
    ? '<svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 3.5A1.5 1.5 0 017 5v6a1.5 1.5 0 01-3 0V5A1.5 1.5 0 015.5 3.5zm5 0A1.5 1.5 0 0112 5v6a1.5 1.5 0 01-3 0V5a1.5 1.5 0 011.5-1.5z"/></svg>'
    : '<svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M10.804 8L5 4.633v6.734L10.804 8z"/></svg>';
}

function _setText(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value;
}

function _setFill(id, percent) {
  const el = document.getElementById(id);
  if (el) el.style.width = percent + '%';
}

function _fmt(seconds) {
  const total = Math.floor(seconds || 0);
  return Math.floor(total / 60) + ':' + String(total % 60).padStart(2, '0');
}

function _readSavedState() {
  try {
    return JSON.parse(sessionStorage.getItem(PLAYER_STATE_KEY) || localStorage.getItem(PLAYER_STATE_KEY) || 'null');
  } catch {
    return null;
  }
}

function _saveState() {
  if (!_cur) return;

  const audio = document.getElementById('g-audio');
  const hasAudio = audio?.src && !audio.src.endsWith(window.location.pathname);
  const state = {
    ..._cur,
    playing: _playing,
    currentTime: hasAudio ? (audio.currentTime || 0) : _fakeT,
    duration: hasAudio ? (audio.duration || _fakeDur) : _fakeDur,
    volume: hasAudio ? audio.volume : 0.7,
    queue: _queue
  };

  try {
    const payload = JSON.stringify(state);
    sessionStorage.setItem(PLAYER_STATE_KEY, payload);
    localStorage.setItem(PLAYER_STATE_KEY, payload);
  } catch {}
}

/* Cart */
function cartGet() {
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
  if (!_isLoggedIn()) {
    toast(lang === 'pt' ? 'Precisas de iniciar sessao' : 'Sign in to add to cart');
    setTimeout(() => {
      window.location.href = (window.SITE_BASE || '') + '/pages/login.php';
    }, 500);
    return;
  }

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
}

function updateCartBadgeGlobal() {
  const total = cartGet().length;
  document.querySelectorAll('.cart-badge').forEach((el) => {
    el.textContent = total;
    el.style.display = total > 0 ? 'inline-block' : 'none';
  });
}

function changeProductQty(delta, btn) {
  const container = btn.closest('[data-product-id]');
  if (!container) return;

  const currentPageLang = (localStorage.getItem('g_lang') || lang || 'pt');
  const sizeSelect = container.querySelector('#product-size');
  const ownProduct = container.dataset.ownProduct === '1';
  const span = container.querySelector('.product-qty');
  const max = parseInt(container.dataset.productStock, 10) || 9999;
  const current = parseInt(span.textContent, 10) || 1;
  if (ownProduct) {
    toast(currentPageLang === 'pt' ? 'Nao podes comprar o teu proprio produto.' : 'You cannot buy your own product.');
    return;
  }

  if (sizeSelect && !sizeSelect.value && delta > 0) {
    toast(currentPageLang === 'pt' ? 'Seleciona um tamanho primeiro.' : 'Select a size first.');
    return;
  }

  if (max <= 0) {
    toast(currentPageLang === 'pt' ? 'Este produto esta sem stock.' : 'This product is out of stock.');
    return;
  }

  const next = Math.max(1, Math.min(max, current + delta));
  span.textContent = next;

  if (delta > 0 && next === current && current >= max) {
    toast(currentPageLang === 'pt' ? 'Ja atingiste o stock disponivel.' : 'You already reached the available stock.');
  }
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

/* Smooth internal navigation keeps iPhone audio alive */
const _softNavPages = new Set([
  'index.php',
  'music.php',
  'release.php',
  'artists.php',
  'artist.php',
  'shop.php',
  'favourites.php'
]);
let _softNavBusy = false;

function _pageNameFromUrl(url) {
  const path = url.pathname.replace(/\/+$/, '');
  return path.substring(path.lastIndexOf('/') + 1) || 'index.php';
}

function _canSoftNavigate(link, event) {
  if (!_cur) return false;
  if (!link || link.target || link.hasAttribute('download')) return false;
  if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) return false;

  const url = new URL(link.href, window.location.href);
  if (url.origin !== window.location.origin) return false;
  if (url.hash && url.pathname === window.location.pathname && url.search === window.location.search) return false;

  return _softNavPages.has(_pageNameFromUrl(url));
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
    _saveState();
    const response = await fetch(url.href, { headers: { 'X-Requested-With': 'fetch' } });
    if (!response.ok) throw new Error('Navigation failed');

    const html = await response.text();
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const nextBody = doc.querySelector('.page-body');
    const currentBody = document.querySelector('.page-body');
    if (!nextBody || !currentBody) throw new Error('Missing page body');

    currentBody.innerHTML = nextBody.innerHTML;
    document.title = doc.title || document.title;

    if (push) history.pushState({ greenerrySoftNav: true }, '', url.href);
    window.scrollTo(0, 0);

    setLang(lang);
    closeMobileSidebar();
    updateCartBadgeGlobal();
    _syncNavActive();
    _registerMotion(currentBody);
    _initPageContent();
    await _loadTracks();
    _renderQueue();
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

/* Page init */
function _initPageContent() {
  if (document.getElementById('favs-grid')) {
    renderFavs();
    updateFavBadge();
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

  _initPageContent();
});
