<?php
require_once '../includes/config.php';
include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <div class="library-hero hero-card--single">
      <div class="library-hero-copy">
        <span class="slabel" data-t="library_label">Biblioteca</span>
        <h2 data-t="library_title">A tua biblioteca.</h2>
      </div>
    </div>

    <section class="library-section">
      <div class="section-band">
        <div class="page-intro">
          <span class="slabel" data-t="library_tracks_label">Faixas</span>
          <h2 data-t="library_favourites_title">Favoritas</h2>
        </div>
      </div>

      <div id="favs-empty" class="cart-empty-state is-hidden">
        <div class="cart-empty-icon">Fav</div>
        <h3 data-t="library_empty_favourites">Ainda nao tens favoritas.</h3>
        <a href="music.php" class="btn btn-ghost btn-sm" data-t="library_discover_music">Descobrir musica</a>
      </div>

      <div class="catalog-filter catalog-filter--single library-search library-search--solo">
        <input type="search" id="favs-search" class="finput" placeholder="Procurar favoritas" data-tp="library_favourites_search_placeholder">
      </div>
      <div id="favs-search-empty" class="cart-empty-state is-hidden">
        <div class="cart-empty-icon">Search</div>
        <h3 data-t="library_no_favourite_results">Nenhuma favorita encontrada.</h3>
      </div>
      <div class="grid stg" id="favs-grid"></div>
      <nav class="pager" id="favs-pager" aria-label="Pagination"></nav>
    </section>

    <section class="library-section">
      <div class="section-band">
        <div class="page-intro">
          <span class="slabel" data-t="library_artists_label">Artistas</span>
          <h2 data-t="library_following_title">A seguir</h2>
        </div>
      </div>

      <?php if (!is_user_logged_in()): ?>
        <div class="card surface-card surface-card--soft">
          <div class="card-body">
            <p data-t="library_login_artists">Faz login para guardares artistas.</p>
          </div>
        </div>
      <?php else: ?>
        <div class="catalog-filter catalog-filter--single library-search library-search--solo">
          <input type="search" id="following-search" class="finput" placeholder="Procurar artistas seguidos" data-tp="library_following_search_placeholder">
        </div>
        <div id="following-empty" class="card surface-card surface-card--soft is-hidden">
          <div class="card-body">
            <p data-t="library_no_following">Ainda nao segues artistas.</p>
          </div>
        </div>
        <div id="following-search-empty" class="card surface-card surface-card--soft is-hidden">
          <div class="card-body">
            <p data-t="library_no_following_results">Nenhum artista seguido encontrado.</p>
          </div>
        </div>
        <div class="artist-grid-panels" id="following-grid"></div>
        <nav class="pager" id="following-pager" aria-label="Pagination"></nav>
      <?php endif; ?>
    </section>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
