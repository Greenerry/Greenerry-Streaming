<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$releases = db_all_prepared(
    $conn,
    "SELECT r.*, COUNT(f.idFaixa) AS tracks_count, COALESCE(SUM(fl.idListen IS NOT NULL), 0) AS listens
     FROM release_musical r
     LEFT JOIN faixa f ON f.idRelease = r.idRelease
     LEFT JOIN faixa_listen fl ON fl.idFaixa = f.idFaixa
     WHERE r.idCliente = ?
     GROUP BY r.idRelease
     ORDER BY r.criado_em DESC",
    'i',
    [$uid]
);

include '../includes/header.php';
?>

<section class="artist-dashboard-v4 artist-animate">
  <header class="artist-dash-top">
    <div>
      <h2 data-t="nav_artist_releases">Releases</h2>
    </div>
  </header>

  <article class="artist-dash-card artist-admin-table-card">
    <div class="artist-dash-card-head">
      <div><h3 data-t="artist_releases_music_title">Music releases</h3></div>
      <div class="admin-card-head-tools">
        <label class="sbar admin-section-search">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="search" data-artist-search="artist-releases" data-tp="artist_releases_search" placeholder="Search releases">
        </label>
        <span class="badge badge-light"><?= count($releases) ?> <span data-t="artist_total">total</span></span>
      </div>
    </div>
    <?php if (!$releases): ?>
      <p class="artist-dash-empty" data-t="artist_releases_empty_submitted">No releases submitted yet.</p>
    <?php else: ?>
      <div class="tbl-wrap" data-artist-search-scope="artist-releases">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th data-t="artist_table_cover">Cover</th>
              <th data-t="artist_table_title">Title</th>
              <th data-t="artist_table_type">Type</th>
              <th data-t="artist_table_tracks">Tracks</th>
              <th data-t="artist_table_plays">Plays</th>
              <th data-t="artist_table_status">Status</th>
              <th data-t="artist_table_action">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($releases as $release): ?>
              <?php
              $canToggle = in_array((string)$release['estado'], ['aprovado', 'inativo'], true) && (int)($release['bloqueado_admin'] ?? 0) !== 1;
              $isActive = (int)($release['ativo'] ?? 0) === 1;
              ?>
              <tr data-artist-row data-artist-search-row>
                <td>#<?= (int)$release['idRelease'] ?></td>
                <td>
                  <div class="admin-table-thumb">
                    <?php if (!empty($release['capa'])): ?>
                      <img src="<?= h(asset_url('img', $release['capa'])) ?>" alt="">
                    <?php else: ?>
                      <span data-t="artist_no_image">No image</span>
                    <?php endif; ?>
                  </div>
                </td>
                <td><strong><?= h($release['titulo']) ?></strong></td>
                <td><span data-release-type="<?= h($release['tipo']) ?>"><?= h(release_type_label((string)$release['tipo'])) ?></span></td>
                <td><?= (int)$release['tracks_count'] ?></td>
                <td><?= (int)$release['listens'] ?></td>
                <td><span class="badge <?= h(state_badge_class($release['estado'])) ?>" data-state-label="<?= h($release['estado']) ?>"><?= h(order_status_label($release['estado'])) ?></span></td>
                <td>
                  <div class="artist-actions-cell">
                    <a class="btn btn-ghost btn-sm" href="upload_music.php?edit=<?= (int)$release['idRelease'] ?>" data-t="artist_action_edit">Edit</a>
                    <button type="button" class="btn btn-ghost btn-sm js-toggle-item" data-type="music" data-id="<?= (int)$release['idRelease'] ?>" <?= $canToggle ? '' : 'disabled' ?>>
                      <span data-t="<?= $isActive ? 'artist_action_deactivate' : 'artist_action_activate' ?>"><?= $isActive ? 'Deactivate' : 'Activate' ?></span>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </article>
</section>

<script>
function artistText(key, fallback) {
  const activeLang = typeof lang !== 'undefined' ? lang : (localStorage.getItem('g_lang') || 'pt');
  return (typeof T !== 'undefined' && T[activeLang] && T[activeLang][key]) || fallback;
}

document.querySelectorAll('.js-toggle-item').forEach((button) => {
  if (button.dataset.toggleReady === '1') return;
  button.dataset.toggleReady = '1';
  button.addEventListener('click', async () => {
    button.disabled = true;
    const body = new URLSearchParams({ type: button.dataset.type, id: button.dataset.id, csrf_token: window.CSRF_TOKEN || '' });
    try {
      const response = await fetch((window.SITE_BASE || '..') + '/api/toggle_item.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
      });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.error || artistText('artist_update_failed', 'Update failed'));
      const enabled = Number(result.enabled) === 1;
      const label = button.querySelector('[data-t]');
      const labelKey = enabled ? 'artist_action_deactivate' : 'artist_action_activate';
      if (label) {
        label.dataset.t = labelKey;
        label.textContent = artistText(labelKey, enabled ? 'Deactivate' : 'Activate');
      }
      const stateLabel = button.closest('[data-artist-row]')?.querySelector('[data-state-label]');
      if (stateLabel) {
        stateLabel.dataset.stateLabel = enabled ? 'aprovado' : 'inativo';
        stateLabel.textContent = enabled ? artistText('status_approved', 'Approved') : artistText('status_inactive', 'Inactive');
      }
      button.disabled = false;
    } catch (error) {
      button.disabled = false;
      if (typeof toast === 'function') toast(error.message || artistText('artist_update_failed', 'Update failed'));
    }
  });
});
</script>

<?php include '../includes/footer.php'; ?>
