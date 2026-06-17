// Frontend script purpose: Controls the bottom audio player, queue, shuffle, and loop actions.
// Keep browser behavior small, readable, and reusable.
let _cur = null;
let _allTracks = [];
let _queue = [];
let _contextTracks = [];
let _contextIndex = -1;
let _contextMode = 'global';
let _shuffle = true;
let _loop = false;
let _history = [];

function _syncPlayerLayoutVisible(visible = !!_cur) {
  // Adds page spacing when the bottom player is visible.
  document.querySelector('.main')?.classList.toggle('pb-open', visible);
  document.documentElement.classList.toggle('player-open', visible);
  document.body?.classList.toggle('player-open', visible);
}

async function _loadTracks() {
  _allTracks = [];

  // First choice: load every approved track from the API.
  try {
    const response = await fetch(window.SITE_BASE + '/api/tracks.php');
    if (response.ok) {
      _allTracks = ((await response.json()) || []).map(_norm).filter((track) => track?.audio);
      if (_allTracks.length) return;
    }
  } catch {}

  // Fallback: use the tracks already printed in the current page HTML.
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

function playTrackCollection(tracks, startIndex = 0) {
  const list = Array.isArray(tracks) ? tracks.map(_norm).filter((track) => track?.audio) : [];
  if (!list.length) return;
  const index = Math.max(0, Math.min(Number(startIndex) || 0, list.length - 1));
  const track = list[index];
  playTrack(track.title, track.artist, track.cover, track.audio, track.artistId, track.artistFoto, track.id, {
    contextTracks: list
  });
}

const QUEUE_DISPLAY_MAX = 5;

function _fillRandomQueue(minItems = QUEUE_DISPLAY_MAX) {
  if (!_allTracks.length) return;

  // Keeps a small queue ready so next/previous can feel instant.
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

  if (_contextMode === 'collection' && _contextTracks.length) {
    _queue = [];
    for (let offset = 1; offset <= Math.min(QUEUE_DISPLAY_MAX, _contextTracks.length); offset++) {
      const next = _contextTracks[(_contextIndex + offset) % _contextTracks.length];
      if (next && !_sameTrack(next, _cur)) _queue.push(next);
    }
  } else {
    _fillRandomQueue(QUEUE_DISPLAY_MAX);
  }

  if (!_queue.length) {
    list.innerHTML = '';
    return;
  }

  list.innerHTML = _queue.slice(0, QUEUE_DISPLAY_MAX).map((track, index) => {
    const cover = _imgPath(track.cover);
    const title = (track.title || '').replace(/'/g, '&#39;');
    const artist = (track.artist || '').replace(/'/g, '&#39;');

    return `<button type="button" class="qi" onclick="_playQueuedTrack(${index})">
      <span class="qi-thumb">${cover ? `<img src="${cover}" alt="" onerror="this.style.display='none'">` : ''}</span>
      <span class="qi-info">
        <span class="qi-title">${_escapeHtml(track.title || title)}</span>
        <span class="qi-sub">${_escapeHtml(track.artist || artist)}</span>
      </span>
      <span class="qi-play" aria-hidden="true">
        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
      </span>
    </button>`;
  }).join('');
}

function _playQueuedTrack(index = 0) {
  const track = _queue[Number(index) || 0];
  if (!track) return;
  if (_contextMode === 'collection' && _contextTracks.length) {
    const contextIndex = _contextTracks.findIndex((item) => _sameTrack(item, track));
    playTrackCollection(_contextTracks, contextIndex >= 0 ? contextIndex : 0);
    return;
  }
  playTrack(track.title, track.artist, track.cover, track.audio, track.artistId, track.artistFoto, track.id, { keepQueue: true });
}

/* Navigation */
async function nextTrack() {
  if (!_allTracks.length) await _loadTracks();
  let track = null;
  if (_contextMode === 'collection' && _contextTracks.length) {
    _contextIndex = (_contextIndex + 1) % _contextTracks.length;
    track = _contextTracks[_contextIndex];
  } else {
    _fillRandomQueue(1);
    track = _queue.shift();
  }
  if (track) playTrack(track.title, track.artist, track.cover, track.audio, track.artistId, track.artistFoto, track.id, { keepQueue: true });
}

async function prevTrack() {
  if (!_allTracks.length) await _loadTracks();
  let track = null;
  if (_history.length) {
    track = _history.pop();
  } else if (_contextMode === 'collection' && _contextTracks.length) {
    _contextIndex = (_contextIndex - 1 + _contextTracks.length) % _contextTracks.length;
    track = _contextTracks[_contextIndex];
  }
  if (track) playTrack(track.title, track.artist, track.cover, track.audio, track.artistId, track.artistFoto, track.id, { keepQueue: true, skipHistory: true });
}

async function playReleaseByKey(key) {
  if (!_allTracks.length) await _loadTracks();
  const tracks = _allTracks.filter((track) => track.releaseKey === key);
  if (!tracks.length) return;
  _contextTracks = [];
  _contextIndex = -1;
  _contextMode = 'global';
  _queue = tracks.slice(1);
  _fillRandomQueue(QUEUE_DISPLAY_MAX);
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

function toggleLoop() {
  _loop = !_loop;
  const button = document.getElementById('pb-loop');
  if (button) {
    button.classList.toggle('on', _loop);
    button.setAttribute('aria-pressed', _loop ? 'true' : 'false');
    button.style.color = _loop ? 'var(--text)' : 'var(--text3)';
  }
  _saveState();
}

/* Right sidebar */
let _srAnimationTimer = null;

function _markSidebarAnimating() {
  document.body?.classList.add('sr-animating');
  window.clearTimeout(_srAnimationTimer);
  _srAnimationTimer = window.setTimeout(() => {
    document.body?.classList.remove('sr-animating');
  }, 280);
}

function openSr() {
  closeMobileSidebar();
  _markSidebarAnimating();
  document.getElementById('sr')?.classList.add('open');
  document.querySelector('.main')?.classList.add('sr-open');
  _syncPlayerLayoutVisible(true);
  document.getElementById('main-nav')?.classList.add('sr-open');

  const playerBar = document.getElementById('player-bar');
  if (playerBar) {
    playerBar.style.display = 'flex';
    playerBar.classList.add('sr-open');
  }

  const button = document.getElementById('sr-open-btn');
  if (button) button.classList.remove('visible');
  _saveState();
}

function closeSr() {
  _markSidebarAnimating();
  document.getElementById('sr')?.classList.remove('open');
  document.querySelector('.main')?.classList.remove('sr-open');
  document.getElementById('main-nav')?.classList.remove('sr-open');
  document.getElementById('player-bar')?.classList.remove('sr-open');

  const button = document.getElementById('sr-open-btn');
  if (button) button.classList.toggle('visible', !!_cur);
  _saveState();
}

/* Mobile sidebar */
function openMobileSidebar() {
  closeSr();
  document.getElementById('sl')?.classList.add('open');
  document.getElementById('sl-overlay')?.classList.add('visible');
  document.body?.classList.add('nav-open');
  if (window.innerWidth <= 768) document.body?.classList.add('sidebar-expanded');

  const playerBar = document.getElementById('player-bar');
  if (playerBar) playerBar.style.display = 'none';
}

function closeMobileSidebar() {
  const sidebar = document.getElementById('sl');
  const overlay = document.getElementById('sl-overlay');
  if (sidebar) sidebar.classList.remove('open');
  if (overlay) overlay.classList.remove('visible');
  document.body?.classList.remove('nav-open');
  if (window.innerWidth <= 768) document.body?.classList.remove('sidebar-expanded');

  const playerBar = document.getElementById('player-bar');
  if (playerBar && (playerBar.classList.contains('sr-open') || _cur)) {
    playerBar.style.display = 'flex';
    _syncPlayerLayoutVisible(true);
  }
}

/* Play a track */
async function playTrack(title, artist, cover, audioSrc, artistId, artistFoto, musicId, options = {}) {
  // Central player function: updates UI, audio source, queue, favourites icon, and saved state.
  if (_cur && !options.skipHistory && !_sameTrack(_cur, { id: musicId, audio: audioSrc, audioSrc })) {
    _history = [_cur, ..._history.filter((track) => !_sameTrack(track, _cur))].slice(0, 30);
  }
  _cur = { id: musicId, title, artist, cover, audioSrc, audio: audioSrc, artistId, artistFoto };

  if (!_allTracks.length) await _loadTracks();

  if (Array.isArray(options.contextTracks) && options.contextTracks.length) {
    _contextTracks = options.contextTracks.map(_norm).filter((track) => track?.audio);
    _contextIndex = _contextTracks.findIndex((track) => _sameTrack(track, _cur));
    if (_contextIndex < 0) _contextIndex = 0;
    _contextMode = 'collection';
  } else if (!options.keepQueue) {
    _contextTracks = [];
    _contextIndex = -1;
    _contextMode = 'global';
  } else if (_contextMode === 'collection' && _contextTracks.length) {
    const currentContextIndex = _contextTracks.findIndex((track) => _sameTrack(track, _cur));
    if (currentContextIndex >= 0) _contextIndex = currentContextIndex;
  }

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
  _syncPlayerLayoutVisible(true);

  _setPlayerTitle('np-track', title);
  _setText('np-artist', artist);
  _setPlayerTitle('pb-title', title);
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
  if (typeof refreshCurrentPlaylistButton === 'function') {
    refreshCurrentPlaylistButton(musicId);
  }
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
let _listenReportedKey = '';

function _reportListen(seconds = 0) {
  if (!_cur?.id || !_cur?.artistId) return;
  const key = `${_cur.id}:${Math.floor(Date.now() / 60000)}`;
  if (_listenReportedKey === key) return;
  _listenReportedKey = key;

  const body = new URLSearchParams();
  body.set('trackId', _cur.id);
  body.set('seconds', Math.max(0, Math.floor(seconds || 0)));
  if (window.CSRF_TOKEN) body.set('csrf_token', window.CSRF_TOKEN);
  fetch((window.SITE_BASE || '') + '/api/listen.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body.toString()
  }).catch(() => {});
}

function _bindAudio(audio) {
  if (_audioBound) return;
  _audioBound = true;

  // Keep the progress bar in sync while the real audio plays.
  // _saveState is throttled: only writes storage once every 5 s to avoid jank.
  let _saveThrottle = 0;
  audio.ontimeupdate = () => {
    if (!audio.duration) return;
    _setFill('pb-fill', (audio.currentTime / audio.duration) * 100);
    _setText('pb-cur', _fmt(audio.currentTime));
    _setText('pb-dur', _fmt(audio.duration));
    const now = Date.now();
    if (now - _saveThrottle > 5000) { _saveThrottle = now; _saveState(); }
    if (audio.currentTime >= 20 || (audio.duration && audio.currentTime / audio.duration >= 0.5)) {
      _reportListen(audio.currentTime);
    }
  };

  audio.onended = () => {
    if (_loop) {
      audio.currentTime = 0;
      audio.play().catch(() => {});
      return;
    }
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
      if (_loop) {
        _fakeT = 0;
        return;
      }
      clearInterval(_fakeTimer);
      _playing = false;
      _updatePlayBtn(false);
      nextTrack();
    }
    if (_fakeT >= 20) _reportListen(_fakeT);
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

function _isTypingTarget(target) {
  const element = target instanceof Element ? target : null;
  if (!element) return false;
  return !!element.closest('input, textarea, select, [contenteditable="true"], [contenteditable=""], .finput');
}

function _seekPlayer(seconds) {
  const audio = document.getElementById('g-audio');
  const hasAudio = audio?.src && !audio.src.endsWith(window.location.pathname);
  if (hasAudio && Number.isFinite(audio.duration)) {
    audio.currentTime = Math.max(0, Math.min(audio.duration, (audio.currentTime || 0) + seconds));
    return;
  }
  if (_cur) {
    _fakeT = Math.max(0, Math.min(_fakeDur, _fakeT + seconds));
    _setFill('pb-fill', (_fakeT / _fakeDur) * 100);
    _setText('pb-cur', _fmt(_fakeT));
  }
}

document.addEventListener('keydown', (event) => {
  if (event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey || _isTypingTarget(event.target)) return;
  if (!_cur && event.code !== 'ArrowRight' && event.code !== 'ArrowLeft') return;

  if (event.code === 'Space') {
    event.preventDefault();
    togglePlay();
  } else if (event.code === 'ArrowRight') {
    event.preventDefault();
    if (event.shiftKey) nextTrack();
    else _seekPlayer(10);
  } else if (event.code === 'ArrowLeft') {
    event.preventDefault();
    if (event.shiftKey) prevTrack();
    else _seekPlayer(-10);
  } else if (event.code === 'KeyN') {
    event.preventDefault();
    nextTrack();
  } else if (event.code === 'KeyP') {
    event.preventDefault();
    prevTrack();
  }
});

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

function _setPlayerTitle(id, value) {
  const el = document.getElementById(id);
  if (!el) return;

  el.classList.remove('is-marquee');
  el.style.removeProperty('--marquee-box');
  el.style.removeProperty('--marquee-duration');
  el.innerHTML = `<span>${_escapeHtml(String(value || '-'))}</span>`;

  requestAnimationFrame(() => {
    const span = el.querySelector('span');
    if (!span) return;
    const box = Math.max(1, el.clientWidth);
    if (span.scrollWidth <= box + 4) return;
    el.style.setProperty('--marquee-box', `${box}px`);
    el.style.setProperty('--marquee-duration', `${Math.max(10, Math.min(22, span.scrollWidth / 14))}s`);
    el.classList.add('is-marquee');
  });
}

function _escapeHtml(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
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

  // Saves the current track so the player can survive refreshes and soft navigation.
  const audio = document.getElementById('g-audio');
  const hasAudio = audio?.src && !audio.src.endsWith(window.location.pathname);
  const state = {
    ..._cur,
    playing: _playing,
    currentTime: hasAudio ? (audio.currentTime || 0) : _fakeT,
    duration: hasAudio ? (audio.duration || _fakeDur) : _fakeDur,
    volume: hasAudio ? audio.volume : 0.7,
    queue: _queue,
    history: _history,
    loop: _loop,
    contextTracks: _contextTracks,
    contextIndex: _contextIndex,
    contextMode: _contextMode,
    srOpen: !!document.getElementById('sr')?.classList.contains('open')
  };

  try {
    const payload = JSON.stringify(state);
    sessionStorage.setItem(PLAYER_STATE_KEY, payload);
    localStorage.setItem(PLAYER_STATE_KEY, payload);
  } catch {}
}

/* Cart */
