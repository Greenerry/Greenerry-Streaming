<?php
require_once '../includes/config.php';
require_admin_permission('music');

$range = (string)($_GET['range'] ?? '30d');
$allowedRanges = ['7d', '30d', '6m', '1y', 'all'];
if (!in_array($range, $allowedRanges, true)) {
    $range = '30d';
}
$rangeSqlMap = [
    '7d' => 'DATE_SUB(CURDATE(), INTERVAL 7 DAY)',
    '30d' => 'DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
    '6m' => 'DATE_SUB(CURDATE(), INTERVAL 6 MONTH)',
    '1y' => 'DATE_SUB(CURDATE(), INTERVAL 1 YEAR)',
    'all' => "'1970-01-01'",
];
$rangeLabels = [
    '7d' => ['key' => 'range_7d', 'label' => '7 dias'],
    '30d' => ['key' => 'range_30d', 'label' => '30 dias'],
    '6m' => ['key' => 'range_6m', 'label' => '6 meses'],
    '1y' => ['key' => 'range_1y', 'label' => '1 ano'],
    'all' => ['key' => 'range_all', 'label' => 'Tudo'],
];
$dateFromSql = $rangeSqlMap[$range];

$summary = db_one(
    $conn,
    "SELECT
        COUNT(*) AS listens,
        COUNT(DISTINCT fl.idCliente) AS listeners,
        COUNT(DISTINCT fl.idFaixa) AS tracks,
        COALESCE(SUM(fl.segundos_ouvidos), 0) AS seconds_listened
     FROM faixa_listen fl
     WHERE fl.criado_em >= {$dateFromSql}"
);

$topTracks = db_all(
    $conn,
    "SELECT
        f.idFaixa,
        f.titulo,
        f.genero,
        f.ficheiro_audio,
        r.titulo AS release_title,
        r.capa,
        c.nome AS artista,
        COUNT(fl.idListen) AS listens,
        COUNT(DISTINCT fl.idCliente) AS listeners,
        COALESCE(SUM(fl.segundos_ouvidos), 0) AS seconds_listened,
        MAX(fl.criado_em) AS last_played
     FROM faixa_listen fl
     JOIN faixa f ON f.idFaixa = fl.idFaixa
     JOIN release_musical r ON r.idRelease = f.idRelease
     JOIN cliente c ON c.idCliente = fl.idArtista
     WHERE fl.criado_em >= {$dateFromSql}
     GROUP BY f.idFaixa, f.titulo, f.genero, f.ficheiro_audio, r.titulo, r.capa, c.nome
     ORDER BY listens DESC, last_played DESC
     LIMIT 80"
);

$topArtists = db_all(
    $conn,
    "SELECT c.idCliente, c.nome, c.foto, COUNT(fl.idListen) AS listens, COUNT(DISTINCT fl.idCliente) AS listeners
     FROM faixa_listen fl
     JOIN cliente c ON c.idCliente = fl.idArtista
     WHERE fl.criado_em >= {$dateFromSql}
     GROUP BY c.idCliente, c.nome, c.foto
     ORDER BY listens DESC
     LIMIT 8"
);

$periodKeySql = in_array($range, ['7d', '30d'], true)
    ? "DATE_FORMAT(fl.criado_em, '%Y-%m-%d')"
    : "DATE_FORMAT(fl.criado_em, '%Y-%m')";
$periodLabelSql = in_array($range, ['7d', '30d'], true)
    ? "DATE_FORMAT(fl.criado_em, '%d/%m')"
    : "DATE_FORMAT(fl.criado_em, '%m/%Y')";
$listeningTrend = db_all(
    $conn,
    "SELECT
        {$periodKeySql} AS period_key,
        {$periodLabelSql} AS period_label,
        COUNT(*) AS listens,
        COUNT(DISTINCT fl.idCliente) AS listeners
     FROM faixa_listen fl
     WHERE fl.criado_em >= {$dateFromSql}
     GROUP BY period_key, period_label
     ORDER BY period_key ASC"
);
$maxTrendListens = 0;
foreach ($listeningTrend as $entry) {
    $maxTrendListens = max($maxTrendListens, (int)$entry['listens']);
}

$genrePerformance = db_all(
    $conn,
    "SELECT COALESCE(NULLIF(TRIM(f.genero), ''), 'Sem genero') AS genre,
            COUNT(fl.idListen) AS listens,
            COUNT(DISTINCT fl.idCliente) AS listeners
     FROM faixa_listen fl
     JOIN faixa f ON f.idFaixa = fl.idFaixa
     WHERE fl.criado_em >= {$dateFromSql}
     GROUP BY genre
     ORDER BY listens DESC
     LIMIT 6"
);
$maxGenreListens = 0;
foreach ($genrePerformance as $genre) {
    $maxGenreListens = max($maxGenreListens, (int)$genre['listens']);
}

$topTrack = $topTracks[0] ?? null;
$chartWidth = 620;
$chartHeight = 230;
$chartLeft = 28;
$chartRight = $chartWidth - 28;
$chartTop = 32;
$chartBottom = $chartHeight - 34;
$chartPoints = [];
$chartAreaPoints = [];
$trendCount = count($listeningTrend);
foreach ($listeningTrend as $index => $entry) {
    $x = $trendCount > 1
        ? $chartLeft + (($chartRight - $chartLeft) * ($index / ($trendCount - 1)))
        : ($chartLeft + $chartRight) / 2;
    $ratio = $maxTrendListens > 0 ? ((int)$entry['listens'] / $maxTrendListens) : 0;
    $y = $chartBottom - (($chartBottom - $chartTop) * $ratio);
    $chartPoints[] = round($x, 1) . ',' . round($y, 1);
}
if ($chartPoints) {
    $firstX = explode(',', $chartPoints[0])[0];
    $lastX = explode(',', $chartPoints[count($chartPoints) - 1])[0];
    $chartAreaPoints = array_merge([$firstX . ',' . $chartBottom], $chartPoints, [$lastX . ',' . $chartBottom]);
}

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <span class="admin-page-kicker" data-admin-t="music_kicker">Relatório musical</span>
    <h2 data-admin-t="music_title">Relatório musical</h2>
    <p data-admin-t="music_intro">Performance de faixas, artistas e ouvintes ativos da plataforma.</p>
  </div>
  <nav class="admin-range-pills" aria-label="Music report range">
    <?php foreach ($rangeLabels as $rangeKey => $rangeItem): ?>
      <a href="music.php?range=<?= h($rangeKey) ?>" class="<?= $range === $rangeKey ? 'on' : '' ?>" data-admin-t="<?= h($rangeItem['key']) ?>"><?= h($rangeItem['label']) ?></a>
    <?php endforeach; ?>
  </nav>
</div>

<section class="stats-grid admin-top-stats">
  <div class="stat"><div class="stat-val"><?= (int)($summary['listens'] ?? 0) ?></div><div class="stat-lbl" data-admin-t="music_total_plays">Reproduções</div></div>
  <div class="stat"><div class="stat-val"><?= (int)($summary['listeners'] ?? 0) ?></div><div class="stat-lbl" data-admin-t="music_unique_listeners">Ouvintes únicos</div></div>
  <div class="stat"><div class="stat-val"><?= (int)($summary['tracks'] ?? 0) ?></div><div class="stat-lbl" data-admin-t="music_tracks_played">Faixas tocadas</div></div>
  <div class="stat"><div class="stat-val"><?= h(number_format(((int)($summary['seconds_listened'] ?? 0)) / 3600, 1, ',', '.')) ?></div><div class="stat-lbl" data-admin-t="music_hours">Horas ouvidas</div></div>
</section>

<section class="admin-music-report-grid">
  <article class="acard-box admin-music-line-card">
    <div class="acard-box-head">
      <h4 data-admin-t="dash_music_listening">Relat?rio musical</h4>
      <span class="admin-card-note" data-admin-t="<?= h($rangeLabels[$range]['key']) ?>"><?= h($rangeLabels[$range]['label']) ?></span>
    </div>
    <?php if (!$listeningTrend): ?>
      <p data-admin-t="dash_no_listening">Sem atividade de escuta neste per?odo.</p>
    <?php else: ?>
      <svg class="admin-music-line-chart" viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>" role="img" aria-label="Music listening chart">
        <defs>
          <linearGradient id="musicLineFill" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0%" stop-color="currentColor" stop-opacity=".16"/>
            <stop offset="100%" stop-color="currentColor" stop-opacity="0"/>
          </linearGradient>
        </defs>
        <g class="chart-grid-lines">
          <line x1="28" y1="48" x2="592" y2="48"/>
          <line x1="28" y1="92" x2="592" y2="92"/>
          <line x1="28" y1="136" x2="592" y2="136"/>
          <line x1="28" y1="180" x2="592" y2="180"/>
        </g>
        <?php if ($chartAreaPoints): ?><polygon points="<?= h(implode(' ', $chartAreaPoints)) ?>" fill="url(#musicLineFill)"/><?php endif; ?>
        <?php if ($chartPoints): ?><polyline points="<?= h(implode(' ', $chartPoints)) ?>" fill="none"/><?php endif; ?>
        <?php foreach ($chartPoints as $index => $point): ?>
          <?php [$cx, $cy] = explode(',', $point); $entry = $listeningTrend[$index] ?? null; ?>
          <circle cx="<?= h($cx) ?>" cy="<?= h($cy) ?>" r="5"><title><?= $entry ? h($entry['period_label'] . ': ' . (int)$entry['listens'] . ' plays') : '' ?></title></circle>
        <?php endforeach; ?>
      </svg>
      <div class="dash-v4-months">
        <?php foreach ($listeningTrend as $entry): ?><span><?= h($entry['period_label']) ?></span><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </article>

  <aside class="admin-music-side">
    <article class="acard-box admin-music-top-card">
      <div class="acard-box-head"><h4 data-admin-t="music_track_table">Performance por faixa</h4></div>
      <?php if ($topTrack): ?>
        <div class="admin-music-top-track">
          <span><?php if (!empty($topTrack['capa'])): ?><img src="<?= h(asset_url('img', $topTrack['capa'])) ?>" alt=""><?php endif; ?></span>
          <div><strong><?= h($topTrack['titulo']) ?></strong><small><?= h($topTrack['artista']) ?> / <?= (int)$topTrack['listens'] ?> <span data-admin-t="dash_plays_lower">reprodu??es</span></small></div>
        </div>
      <?php else: ?>
        <p data-admin-t="dash_no_listening">Sem atividade de escuta neste per?odo.</p>
      <?php endif; ?>
    </article>

    <article class="acard-box admin-music-genre-card">
      <div class="acard-box-head"><h4 data-admin-t="label_genre">G?nero</h4><span class="admin-card-note">Top</span></div>
      <?php if (!$genrePerformance): ?>
        <p data-admin-t="dash_no_listening">Sem atividade de escuta neste per?odo.</p>
      <?php else: ?>
        <div class="admin-music-genre-list">
          <?php foreach ($genrePerformance as $genre): ?>
            <?php $width = $maxGenreListens > 0 ? max(8, (int)round(((int)$genre['listens'] / $maxGenreListens) * 100)) : 8; ?>
            <div class="admin-chart-tip" data-chart-tip="<?= h($genre['genre'] . ' | Plays: ' . (int)$genre['listens'] . ' | Listeners: ' . (int)$genre['listeners']) ?>">
              <div><strong><?= h($genre['genre']) ?></strong><span><?= (int)$genre['listeners'] ?> <span data-admin-t="dash_listeners_lower">ouvintes</span></span></div>
              <em><?= (int)$genre['listens'] ?></em>
              <i><span style="width: <?= $width ?>%"></span></i>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </article>
  </aside>
</section>

<div id="music-search" data-admin-search-scope>
  <section class="acard-box admin-music-track-table-card">
    <div class="acard-box-head">
      <h4 data-admin-t="music_track_table">Performance por faixa</h4>
      <div class="admin-card-head-tools">
        <label class="sbar admin-section-search">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="search" data-admin-search="music-search" placeholder="Pesquisar..." data-admin-tp="admin_search_placeholder">
        </label>
        <span class="badge badge-light"><?= count($topTracks) ?></span>
      </div>
    </div>
    <div class="tbl-wrap admin-music-track-table-wrap">
      <table class="admin-music-track-table">
        <thead>
          <tr>
            <th data-admin-t="products_image">Imagem</th>
            <th data-admin-t="artist_table_track">Faixa</th>
            <th data-admin-t="label_artist">Artista</th>
            <th data-admin-t="releases_title">Lançamento</th>
            <th data-admin-t="music_total_plays">Reproduções</th>
            <th data-admin-t="music_unique_listeners">Ouvintes únicos</th>
            <th data-admin-t="releases_audio">Áudio</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($topTracks as $track): ?>
            <tr data-admin-state="<?= h($track['titulo'] . ' ' . $track['artista'] . ' ' . $track['release_title'] . ' ' . $track['genero']) ?>">
              <td><span class="thumb-sm"><?php if (!empty($track['capa'])): ?><img src="<?= h(asset_url('img', $track['capa'])) ?>" alt=""><?php endif; ?></span></td>
              <td><strong><?= h($track['titulo']) ?></strong><br><span><?= h($track['genero'] ?: '-') ?></span></td>
              <td><?= h($track['artista']) ?></td>
              <td><?= h($track['release_title']) ?></td>
              <td><strong><?= (int)$track['listens'] ?></strong></td>
              <td><?= (int)$track['listeners'] ?></td>
              <td>
                <?php if (!empty($track['ficheiro_audio'])): ?>
                  <div class="admin-mini-player" data-audio-src="<?= h(asset_url('audio', $track['ficheiro_audio'])) ?>">
                    <button type="button"><span data-admin-t="releases_listen">Ouvir</span></button>
                    <audio preload="none" src="<?= h(asset_url('audio', $track['ficheiro_audio'])) ?>"></audio>
                  </div>
                <?php else: ?>
                  <span class="badge badge-soft" data-admin-t="releases_no_tracks">Sem faixas</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>

<?php include 'admin_footer.php'; ?>
