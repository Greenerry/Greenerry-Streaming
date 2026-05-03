<?php
require_once '../includes/config.php';

$releaseId = (int)($_GET['id'] ?? 0);
if ($releaseId <= 0) {
    header('Location: music.php');
    exit;
}

$release = db_one(
    $conn,
    "SELECT
        r.*,
        c.idCliente AS artistId,
        c.nome AS artist_nome,
        c.foto AS artist_foto,
        c.bio AS artist_bio
     FROM release_musical r
     JOIN cliente c ON c.idCliente = r.idCliente
     WHERE r.idRelease = {$releaseId}
       AND r.estado = 'aprovado'
       AND r.ativo = 1
       AND c.estado = 'ativo'
     LIMIT 1"
);

if (!$release) {
    header('Location: music.php');
    exit;
}

$tracks = db_all(
    $conn,
    "SELECT idFaixa, numero_faixa, titulo, ficheiro_audio
     FROM faixa
     WHERE idRelease = {$releaseId}
       AND estado = 'aprovada'
       AND ativo = 1
     ORDER BY numero_faixa ASC"
);

$cover = asset_url('img', $release['capa']);
$artistFoto = asset_url('img', $release['artist_foto']);
$releaseKey = $releaseId . '-' . (int)$release['artistId'];

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <button type="button" class="btn btn-ghost btn-sm release-back-btn" data-t="release_back" onclick="if (history.length > 1) history.back(); else location.href='music.php';">Voltar</button>

    <div class="release-detail-hero">
      <div class="release-detail-cover">
        <?php if ($cover): ?>
          <img src="<?= h($cover) ?>" alt="<?= h($release['titulo']) ?>">
        <?php endif; ?>
      </div>
      <div class="release-detail-copy">
        <span class="slabel"><?= h($release['tipo']) ?></span>
        <h1><?= h($release['titulo']) ?></h1>
        <a href="artist.php?id=<?= (int)$release['artistId'] ?>" class="release-artist-link">
          <?php if ($artistFoto): ?>
            <img src="<?= h($artistFoto) ?>" alt="<?= h($release['artist_nome']) ?>">
          <?php endif; ?>
          <span><?= h($release['artist_nome']) ?></span>
        </a>
        <div class="release-detail-meta">
          <?php if (!empty($release['data_lancamento'])): ?>
            <span><?= date('d/m/Y', strtotime($release['data_lancamento'])) ?></span>
          <?php endif; ?>
          <span><?= count($tracks) ?> <span data-t="release_tracks_count">faixas</span></span>
        </div>
        <?php if (!empty($release['descricao'])): ?>
          <p><?= h($release['descricao']) ?></p>
        <?php endif; ?>
        <?php if ($tracks): ?>
          <button type="button" class="btn btn-dark" onclick="playReleaseByKey('<?= h($releaseKey) ?>')" data-t="release_play_all">Tocar lancamento</button>
        <?php endif; ?>
      </div>
    </div>

    <div class="page-intro mt8">
      <span class="slabel" data-t="release_tracklist_label">Faixas</span>
      <h2 data-t="release_tracklist_title">Lista de faixas</h2>
    </div>

    <?php if (!$tracks): ?>
      <div class="card surface-card">
        <div class="card-body text-center">
          <p data-t="release_no_tracks">Nao existem faixas disponiveis.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="release-track-list">
        <?php foreach ($tracks as $track): ?>
          <?php
          $audio = asset_url('audio', $track['ficheiro_audio']);
          $payload = [
              'id' => (int)$track['idFaixa'],
              'title' => $track['titulo'],
              'artist' => $release['artist_nome'],
              'cover' => $cover,
              'audio' => $audio,
              'artistId' => (int)$release['artistId'],
              'artistFoto' => $artistFoto,
              'type' => $release['tipo'],
              'releaseKey' => $releaseKey
          ];
          ?>
          <article class="release-track-row" data-track='<?= h(json_encode($payload)) ?>'>
            <span class="release-track-number"><?= (int)$track['numero_faixa'] ?></span>
            <div class="release-track-thumb">
              <?php if ($cover): ?>
                <img src="<?= h($cover) ?>" alt="<?= h($release['titulo']) ?>">
              <?php endif; ?>
            </div>
            <div class="release-track-info">
              <strong><?= h($track['titulo']) ?></strong>
              <span><?= h($release['artist_nome']) ?></span>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" data-t="release_play_track" onclick="playTrack('<?= h(addslashes($track['titulo'])) ?>','<?= h(addslashes($release['artist_nome'])) ?>','<?= h($cover) ?>','<?= h($audio) ?>',<?= (int)$release['artistId'] ?>,'<?= h($artistFoto) ?>',<?= (int)$track['idFaixa'] ?>)">Play</button>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
