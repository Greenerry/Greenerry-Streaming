<?php if (!isset($_base)) include __DIR__ . '/config.php'; ?>
<?php
$translationsJson = file_get_contents(__DIR__ . '/../assets/js/translations.json');
if ($translationsJson === false) {
    $translationsJson = '{"pt":{},"en":{}}';
}
?>
  </div><!-- end .page-body -->

  <footer>
    <div class="wrap">
      <div class="foot-grid">
        <div>
          <div class="foot-brand">GREENERRY.</div>
          <p class="foot-desc" data-t="foot_desc">Independent music streaming, artist discovery, and merch in one organized website.</p>
        </div>
        <div class="foot-col">
          <h5 data-t="foot_discover">Discover</h5>
          <a href="<?= $_base ?>/pages/music.php" data-t="nav_music">Music</a>
          <a href="<?= $_base ?>/pages/artists.php" data-t="nav_artists">Artists</a>
          <a href="<?= $_base ?>/pages/shop.php" data-t="foot_store">Store</a>
        </div>
        <div class="foot-col">
          <h5 data-t="foot_account">Account</h5>
          <a href="<?= $_base ?>/pages/login.php" data-t="nav_login">Login</a>
          <a href="<?= $_base ?>/pages/registar.php" data-t="nav_register">Register</a>
          <a href="<?= $_base ?>/pages/profile.php" data-t="nav_profile">Profile</a>
        </div>
        <div class="foot-col">
          <h5 data-t="foot_platform">Platform</h5>
          <a href="<?= $_base ?>/pages/upload_music.php" data-t="nav_upload_music">Upload music</a>
          <a href="<?= $_base ?>/pages/upload_merch.php" data-t="nav_upload_merch">Upload merch</a>
          <a href="<?= $_base ?>/pages/contact_admin.php" data-t="nav_contact_admin">Contact admin</a>
        </div>
      </div>
      <div class="foot-btm">
        <span class="foot-copy" data-t="foot_copy">(c) 2026 Greenerry. Built for PAP presentation use.</span>
        <div class="foot-socials">
          <a href="#" class="soc" aria-label="Instagram"><svg width="13" height="13" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg></a>
          <a href="#" class="soc" aria-label="X"><svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
        </div>
      </div>
    </div>
  </footer>

</div><!-- end .main -->

<aside class="sr" id="sr">
  <div class="sr-head">
    <span class="sr-lbl" data-t="player_now_en">Now playing</span>
    <button class="sr-close" id="sr-close" onclick="closeSr()" title="Close">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>

  <div class="sr-body" id="sr-body">
    <div class="np-cover" id="np-cover">
      <div class="np-cover-ph" id="np-ph">
        <svg width="56" height="56" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" opacity=".15"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
      </div>
      <img id="np-img" src="" alt="" class="media-hidden media-cover">
    </div>

    <div class="np-meta-wrap">
      <div class="np-meta-head">
        <div class="min-w-0">
          <div class="np-track" id="np-track">-</div>
          <div class="np-artist" id="np-artist"></div>
        </div>
        <button class="fav-btn" id="fav-btn" onclick="toggleFav()" title="Favorite">
          <svg id="fav-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </button>
      </div>
    </div>

    <div id="sr-artist-card" class="sr-artist-card">
      <div class="sr-artist-card-inner">
        <div class="avatar sr-artist-avatar" id="sr-artist-avatar">
          <svg width="16" height="16" fill="var(--text3)" viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
        </div>
        <div class="min-w-0">
          <div id="sr-artist-name" class="sr-artist-name">-</div>
          <a id="sr-artist-link" href="#" class="sr-artist-link" data-t="player_view_profile">View profile</a>
        </div>
      </div>
    </div>

    <div class="queue queue--spaced">
      <span class="queue-lbl" data-t="player_next">Up next</span>
      <div id="queue-list"></div>
    </div>
  </div>

  <div id="sr-empty" class="sr-empty">
    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" opacity=".2" class="sr-empty-icon"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
    <p class="sr-empty-text" data-t="player_empty">Choose a track to start listening.</p>
  </div>
</aside>

<div class="player-bar" id="player-bar">
  <div class="pb-left">
    <div class="pb-thumb">
      <div class="pb-thumb-ph" id="pb-thumb-ph">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" opacity=".3"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
      </div>
      <img id="pb-img" src="" alt="" class="media-hidden media-cover">
    </div>
    <div class="pb-info">
      <div class="pb-title" id="pb-title">-</div>
      <div class="pb-artist" id="pb-artist"></div>
    </div>
  </div>

  <div class="pb-center">
    <div class="pb-ctrls">
      <button class="pb-btn" id="pb-shuffle" title="Shuffle">
        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.5 3a.5.5 0 0 1 .5.5v1.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 1 1 .708-.708L12 5.293V4h-1.5a2 2 0 0 0-1.6.8l-1.2 1.6a3 3 0 0 1-2.4 1.2H1.5a.5.5 0 0 1 0-1h3.8a2 2 0 0 0 1.6-.8l1.2-1.6A3 3 0 0 1 10.5 3h2zM1.5 11a.5.5 0 0 0 0 1h3.8a3 3 0 0 0 2.4-1.2l.774-1.032-.648-.864A.5.5 0 1 0 7.026 9.5l-.126.168A2 2 0 0 1 5.3 10.5H1.5zm9.354-1.854a.5.5 0 1 0-.708.708L12 11.707V10.5a.5.5 0 0 1 1 0v1.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2z"/></svg>
      </button>
      <button class="pb-btn" onclick="prevTrack()">
        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 3.5A.5.5 0 011 4v3.248l6.267-3.636C7.787 3.31 8.5 3.655 8.5 4.308v2.94l6.267-3.636c.52-.302 1.233.043 1.233.696v7.384c0 .653-.713.998-1.233.696L8.5 8.752v2.94c0 .653-.713.998-1.233.696L1 8.752V12a.5.5 0 01-1 0V4a.5.5 0 01.5-.5z"/></svg>
      </button>
      <button class="pb-play" id="pb-play">
        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M10.804 8L5 4.633v6.734L10.804 8z"/></svg>
      </button>
      <button class="pb-btn" onclick="nextTrack()">
        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M15.5 3.5a.5.5 0 01.5.5v8a.5.5 0 0 1-1 0V8.752l-6.267 3.636c-.52.302-1.233-.043-1.233-.696v-2.94l-6.267 3.636C.713 12.69 0 12.345 0 11.692V4.308c0-.653.713-.998 1.233-.696L7.5 7.248v-2.94c0-.653.713-.998 1.233-.696L15 7.248V4a.5.5 0 01.5-.5z"/></svg>
      </button>
    </div>
    <div class="pb-progress">
      <span class="pb-times" id="pb-cur">0:00</span>
      <div class="pb-bar" id="pb-bar"><div class="pb-fill" id="pb-fill"></div></div>
      <span class="pb-times" id="pb-dur">0:00</span>
    </div>
  </div>

  <div class="pb-right">
    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M9 4a.5.5 0 0 0-.812-.39L5.825 5.5H3.5A.5.5 0 0 0 3 6v4a.5.5 0 0 0 .5.5h2.325l2.363 1.89A.5.5 0 0 0 9 12V4zm3.025 4a4.486 4.486 0 0 1-1.318 3.182L10 10.475A3.49 3.49 0 0 0 11.025 8 3.49 3.49 0 0 0 10 5.525l.707-.707A4.486 4.486 0 0 1 12.025 8z"/></svg>
    <div class="np-vol-bar pb-vol-bar" id="pb-vol-bar"><div class="np-vol-fill" id="pb-vol-fill" style="width:70%;"></div></div>
  </div>
</div>

</div><!-- end .shell -->
<audio id="g-audio" class="media-hidden"></audio>
<script id="greenerry-translations" type="application/json"><?= $translationsJson ?></script>
<script src="<?= $_base ?>/assets/js/main.js?v=<?= filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>
</body>
</html>
