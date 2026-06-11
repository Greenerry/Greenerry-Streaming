<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$rangeOptions = [
    '7d' => ['label' => '7 days', 'key' => 'range_7_days', 'days' => 7],
    '30d' => ['label' => '30 days', 'key' => 'range_30_days', 'days' => 30],
    '1y' => ['label' => '1 year', 'key' => 'range_1_year', 'days' => 365],
    'all' => ['label' => 'All', 'key' => 'range_all', 'days' => 365],
];
$selectedRange = (string)($_GET['range'] ?? '30d');
if (!isset($rangeOptions[$selectedRange])) {
    $selectedRange = '30d';
}
$rangeDays = (int)$rangeOptions[$selectedRange]['days'];
$rangeSqlDays = $selectedRange === 'all' ? 20000 : max(1, $rangeDays - 1);

$listenRows = db_all_prepared(
    $conn,
    "SELECT DATE(criado_em) AS day_key, DATE_FORMAT(criado_em, '%d/%m') AS label, COUNT(*) AS listens, COUNT(DISTINCT idCliente) AS listeners
     FROM faixa_listen
     WHERE idArtista = ?
       AND criado_em >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
     GROUP BY DATE(criado_em), DATE_FORMAT(criado_em, '%d/%m')
     ORDER BY DATE(criado_em) ASC",
    'ii',
    [$uid, $rangeSqlDays]
);
$listenMap = [];
foreach ($listenRows as $row) {
    $listenMap[(string)$row['day_key']] = ['listens' => (int)$row['listens'], 'listeners' => (int)$row['listeners']];
}
$days = [];
$maxListens = 0;
$activeDays = 0;
$bestDay = ['label' => '-', 'listens' => 0, 'listeners' => 0];
for ($offset = min(364, max(6, $rangeSqlDays)); $offset >= 0; $offset--) {
    $date = new DateTimeImmutable("-{$offset} days");
    $key = $date->format('Y-m-d');
    $listens = $listenMap[$key]['listens'] ?? 0;
    $listeners = $listenMap[$key]['listeners'] ?? 0;
    $dayData = ['label' => $date->format('d/m'), 'listens' => $listens, 'listeners' => $listeners];
    $days[] = $dayData;
    $maxListens = max($maxListens, $listens);
    if ($listens > 0) {
        $activeDays++;
    }
    if ($listens > (int)$bestDay['listens']) {
        $bestDay = $dayData;
    }
}

$summary = db_one_prepared(
    $conn,
    "SELECT COUNT(*) AS listens, COUNT(DISTINCT idCliente) AS listeners, COALESCE(SUM(segundos_ouvidos), 0) AS seconds_listened
     FROM faixa_listen
     WHERE idArtista = ?
       AND criado_em >= DATE_SUB(CURDATE(), INTERVAL ? DAY)",
    'ii',
    [$uid, $rangeSqlDays]
) ?: [];
$averagePerActiveDay = $activeDays > 0 ? (int)round(((int)($summary['listens'] ?? 0)) / $activeDays) : 0;

$topTracks = db_all_prepared(
    $conn,
    "SELECT f.titulo, COALESCE(g.nome, f.genero) AS genero, r.capa, COUNT(fl.idListen) AS listens, COUNT(DISTINCT fl.idCliente) AS listeners, COALESCE(SUM(fl.segundos_ouvidos), 0) AS seconds_listened
     FROM faixa f
     JOIN release_musical r ON r.idRelease = f.idRelease
     LEFT JOIN genero g ON g.idGenero = f.idGenero
     LEFT JOIN faixa_listen fl ON fl.idFaixa = f.idFaixa AND fl.criado_em >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
     WHERE r.idCliente = ?
     GROUP BY f.idFaixa, f.titulo, g.nome, f.genero, r.capa
     ORDER BY listens DESC, f.idFaixa DESC
     LIMIT 6",
    'ii',
    [$rangeSqlDays, $uid]
);

$chartWidth = 760;
$chartHeight = 280;
$chartPadding = 34;
$points = [];
$areaPoints = [];
$chartCount = max(1, count($days) - 1);
foreach ($days as $index => $day) {
    $x = $chartPadding + ($index / $chartCount) * ($chartWidth - ($chartPadding * 2));
    $ratio = $maxListens > 0 ? ((int)$day['listens'] / $maxListens) : 0;
    $y = ($chartHeight - $chartPadding) - ($ratio * ($chartHeight - ($chartPadding * 2)));
    $points[] = round($x, 1) . ',' . round($y, 1);
}
if ($points) {
    $firstX = explode(',', $points[0])[0];
    $lastX = explode(',', $points[count($points) - 1])[0];
    $areaPoints = array_merge([$firstX . ',' . ($chartHeight - $chartPadding)], $points, [$lastX . ',' . ($chartHeight - $chartPadding)]);
}

include '../includes/header.php';
?>

<section class="artist-dashboard-v4">
  <header class="artist-dash-top">
    <div>
      <h2 data-t="nav_artist_analytics">Analytics</h2>
    </div>
    <nav class="artist-dash-actions artist-dash-range" aria-label="Analytics range">
      <?php foreach ($rangeOptions as $rangeKey => $range): ?>
        <a href="artist_analytics.php?range=<?= h($rangeKey) ?>" class="<?= $selectedRange === $rangeKey ? 'on' : '' ?>"><span data-t="<?= h($range['key']) ?>"><?= h($range['label']) ?></span></a>
      <?php endforeach; ?>
    </nav>
  </header>

  <div class="artist-dash-kpis artist-dash-kpis--three artist-animate">
    <a href="#analytics-trend"><span data-t="artist_overview_plays">Plays</span><strong><?= (int)($summary['listens'] ?? 0) ?></strong></a>
    <a href="#analytics-trend"><span data-t="artist_table_listeners">Listeners</span><strong><?= (int)($summary['listeners'] ?? 0) ?></strong></a>
    <a href="#analytics-trend"><span data-t="artist_analytics_listening_hours">Listening hours</span><strong><?= h(number_format(((int)($summary['seconds_listened'] ?? 0)) / 3600, 1, '.', '')) ?></strong></a>
  </div>

  <div class="artist-dash-grid artist-animate">
    <article class="artist-dash-card artist-dash-main-chart" id="analytics-trend">
      <div class="artist-dash-card-head"><div><h3 data-t="artist_analytics_play_trend">Play trend</h3></div><span class="artist-dash-note" data-t="<?= h($rangeOptions[$selectedRange]['key']) ?>"><?= h($rangeOptions[$selectedRange]['label']) ?></span></div>
      <svg class="artist-dash-line-chart" viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>" role="img" aria-label="Play trend">
        <defs><linearGradient id="analyticsListenFill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#f4f7fb" stop-opacity=".28"/><stop offset="100%" stop-color="#f4f7fb" stop-opacity="0"/></linearGradient></defs>
        <g><line x1="34" y1="54" x2="726" y2="54"/><line x1="34" y1="102" x2="726" y2="102"/><line x1="34" y1="150" x2="726" y2="150"/><line x1="34" y1="198" x2="726" y2="198"/><line x1="34" y1="246" x2="726" y2="246"/></g>
        <polygon points="<?= h(implode(' ', $areaPoints)) ?>" fill="url(#analyticsListenFill)"/>
        <polyline points="<?= h(implode(' ', $points)) ?>" fill="none" stroke="#f4f7fb" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
        <?php foreach ($points as $index => $point): ?>
          <?php [$cx, $cy] = explode(',', $point); ?>
          <circle cx="<?= h($cx) ?>" cy="<?= h($cy) ?>" r="4"><title><?= h($days[$index]['label'] . ': ' . (int)$days[$index]['listens'] . ' plays') ?></title></circle>
        <?php endforeach; ?>
      </svg>
      <div class="artist-dash-months">
        <?php foreach ($days as $index => $day): ?>
          <?php if ($index % max(1, (int)floor(count($days) / 6)) === 0 || $index === count($days) - 1): ?><span><?= h($day['label']) ?></span><?php endif; ?>
        <?php endforeach; ?>
      </div>
      <div class="artist-analytics-insights">
        <div><span data-t="artist_analytics_best_day">Best day</span><strong><?= h($bestDay['label']) ?></strong><small><?= (int)$bestDay['listens'] ?> <span data-t="artist_overview_plays_lower">plays</span></small></div>
        <div><span data-t="artist_analytics_active_days">Active days</span><strong><?= (int)$activeDays ?></strong><small data-t="<?= h($rangeOptions[$selectedRange]['key']) ?>"><?= h($rangeOptions[$selectedRange]['label']) ?></small></div>
        <div><span data-t="artist_analytics_average">Average</span><strong><?= (int)$averagePerActiveDay ?></strong><small><span data-t="artist_analytics_plays_active_day">plays / active day</span></small></div>
      </div>
    </article>

    <article class="artist-dash-card">
      <div class="artist-dash-card-head">
        <div><h3 data-t="artist_analytics_tracks_performance">Tracks performance</h3></div>
        <label class="artist-search-field">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="search" data-artist-search="analytics-tracks" data-tp="artist_analytics_search_tracks" placeholder="Search tracks">
        </label>
      </div>
      <div class="artist-track-card-grid" data-artist-search-scope="analytics-tracks">
        <?php foreach ($topTracks as $track): ?>
          <article class="artist-track-card" data-artist-search-row>
            <span class="artist-mini-thumb">
              <?php if (!empty($track['capa'])): ?><img src="<?= h(asset_url('img', $track['capa'])) ?>" alt=""><?php else: ?><span data-t="artist_overview_music">Music</span><?php endif; ?>
            </span>
            <div>
              <strong><?= h($track['titulo']) ?></strong>
              <small <?= $track['genero'] ? '' : 'data-lang-pt="Sem género" data-lang-en="No genre"' ?>><?= h($track['genero'] ?: (current_lang() === 'en' ? 'No genre' : 'Sem género')) ?></small>
              <div class="artist-track-stats"><span><?= (int)$track['listeners'] ?> <span data-t="artist_table_listeners">listeners</span></span><span><?= (int)$track['listens'] ?> <span data-t="artist_overview_plays_lower">plays</span></span></div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </article>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
