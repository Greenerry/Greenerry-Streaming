<?php
require_once '../includes/config.php';

$type = trim($_GET['tipo'] ?? '');
$search = trim($_GET['q'] ?? '');
$typeSafe = db_escape($conn, $type);
$searchSafe = db_escape($conn, $search);

$where = "
    WHERE r.estado = 'aprovado'
      AND r.ativo = 1
      AND f.estado = 'aprovada'
      AND f.ativo = 1
      AND c.estado = 'ativo'
";

if ($type !== '' && in_array($type, ['Single', 'EP', 'Album'], true)) {
    $where .= " AND r.tipo = '{$typeSafe}'";
}
if ($search !== '') {
    $where .= " AND (f.titulo LIKE '%{$searchSafe}%' OR r.titulo LIKE '%{$searchSafe}%' OR c.nome LIKE '%{$searchSafe}%')";
}

$tracks = db_all(
    $conn,
    "SELECT
        f.idFaixa,
        f.titulo AS faixa_titulo,
        f.ficheiro_audio,
        r.idRelease,
        r.titulo AS release_titulo,
        r.tipo,
        r.capa,
        r.data_lancamento,
        c.idCliente AS artistId,
        c.nome AS artist_nome,
        c.foto AS artist_foto
     FROM faixa f
     JOIN release_musical r ON r.idRelease = f.idRelease
     JOIN cliente c ON c.idCliente = r.idCliente
     {$where}
     ORDER BY COALESCE(r.data_lancamento, DATE(r.created_at)) DESC, r.idRelease DESC, f.numero_faixa ASC"
);

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <div class="catalog-hero">
      <div>
        <span class="slabel" data-t="music_label">Music catalog</span>
        <h1 data-t="music_title">Browse the catalog</h1>
      </div>

      <form method="get" class="catalog-filter">
        <input type="text" name="q" class="finput" value="<?= h($search) ?>" data-tp="music_search_placeholder" placeholder="Search track, release, or artist">
        <select name="tipo" class="finput">
          <option value="" data-t="music_all_formats">All formats</option>
          <option value="Single" <?= $type === 'Single' ? 'selected' : '' ?>>Single</option>
          <option value="EP" <?= $type === 'EP' ? 'selected' : '' ?>>EP</option>
          <option value="Album" <?= $type === 'Album' ? 'selected' : '' ?>>Album</option>
        </select>
        <button type="submit" class="btn btn-dark" data-t="music_filter">Filter</button>
      </form>
    </div>

    <?php if (!$tracks): ?>
      <div class="card surface-card">
        <div class="card-body text-center">
          <p data-t="music_empty">No approved tracks matched your search.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="grid stg">
        <?php foreach ($tracks as $track): ?>
          <?php
          $cover = asset_url('img', $track['capa']);
          $audio = asset_url('audio', $track['ficheiro_audio']);
          $artistFoto = asset_url('img', $track['artist_foto']);
          $payload = [
              'id' => (int)$track['idFaixa'],
              'title' => $track['faixa_titulo'],
              'artist' => $track['artist_nome'],
              'cover' => $cover,
              'audio' => $audio,
              'artistId' => (int)$track['artistId'],
              'artistFoto' => $artistFoto,
              'type' => $track['tipo'],
              'releaseKey' => $track['idRelease'] . '-' . $track['artistId']
          ];
          ?>
          <div class="mcard" onclick="playTrack('<?= h(addslashes($track['faixa_titulo'])) ?>','<?= h(addslashes($track['artist_nome'])) ?>','<?= h($cover) ?>','<?= h($audio) ?>',<?= (int)$track['artistId'] ?>,'<?= h($artistFoto) ?>',<?= (int)$track['idFaixa'] ?>)" data-track='<?= json_encode($payload) ?>'>
            <div class="cover">
              <?php if ($cover): ?>
                <img src="<?= h($cover) ?>" alt="<?= h($track['faixa_titulo']) ?>">
              <?php endif; ?>
              <div class="cover-ov"><button class="pbt">Play</button></div>
            </div>
            <div class="meta">
              <span class="badge badge-dark"><?= h($track['tipo']) ?></span>
              <h4><?= h($track['faixa_titulo']) ?></h4>
              <a href="artist.php?id=<?= (int)$track['artistId'] ?>" class="sub" onclick="event.stopPropagation()"><?= h($track['artist_nome']) ?></a>
              <div class="sub"><?= h($track['release_titulo']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
