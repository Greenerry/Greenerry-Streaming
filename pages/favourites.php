<?php
require_once '../includes/config.php';
include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <div class="library-compact-head">
      <div>
        <span class="slabel" data-t="library_label">Biblioteca</span>
        <h2 data-t="library_title">A tua biblioteca.</h2>
      </div>
      <nav class="library-switch" aria-label="Library filters">
        <button type="button" class="on" data-library-tab="tracks" data-t="library_favourite_tracks_title">Musicas favoritas</button>
        <button type="button" data-library-tab="artists" data-t="library_favourite_artists_title">Artistas favoritos</button>
      </nav>
    </div>

    <section class="library-section" id="library-tracks-panel" data-library-panel="tracks">
      <div class="library-panel-head">
        <div class="page-intro">
          <span class="slabel" data-t="library_tracks_label">Faixas</span>
          <h2 data-t="library_favourite_tracks_title">Musicas favoritas</h2>
        </div>
        <div class="catalog-filter catalog-filter--single library-search library-search--solo">
          <input type="search" id="favs-search" class="finput" placeholder="Procurar favoritas" data-tp="library_favourites_search_placeholder">
        </div>
      </div>
      <div id="favs-empty" class="cart-empty-state is-hidden">
        <div class="cart-empty-icon">Fav</div>
        <h3 data-t="library_empty_favourites">Ainda não tens favoritas.</h3>
        <a href="music.php" class="btn btn-ghost btn-sm" data-t="library_discover_music">Descobrir música</a>
      </div>

      <div id="favs-search-empty" class="cart-empty-state is-hidden">
        <div class="cart-empty-icon">Search</div>
        <h3 data-t="library_no_favourite_results">Nenhuma favorita encontrada.</h3>
      </div>
      <div class="grid stg" id="favs-grid"></div>
      <nav class="pager" id="favs-pager" aria-label="Pagination"></nav>
    </section>

    <section class="library-section is-hidden" id="library-artists-panel" data-library-panel="artists">
      <div class="library-panel-head">
        <div class="page-intro">
          <span class="slabel" data-t="library_artists_label">Artistas</span>
          <h2 data-t="library_favourite_artists_title">Artistas favoritos</h2>
        </div>
      </div>

      <?php if (!is_user_logged_in()): ?>
        <div class="card surface-card surface-card--soft">
          <div class="card-body">
            <p data-t="library_login_artists">Faz login para guardares artistas.</p>
          </div>
        </div>
      <?php else: ?>
        <div class="catalog-filter catalog-filter--single library-search library-search--solo library-search--inline">
          <input type="search" id="following-search" class="finput" placeholder="Procurar artistas favoritos" data-tp="library_following_search_placeholder">
        </div>
        <div id="following-empty" class="card surface-card surface-card--soft is-hidden">
          <div class="card-body">
            <p data-t="library_no_following">Ainda não segues artistas.</p>
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
