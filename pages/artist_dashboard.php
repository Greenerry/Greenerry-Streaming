<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$range = (string)($_GET['range'] ?? '30d');
$rangeOptions = [
    '7d' => ['label' => '7 days', 'key' => 'range_7_days', 'sql' => 'DATE_SUB(CURDATE(), INTERVAL 6 DAY)', 'days' => 7],
    '30d' => ['label' => '30 days', 'key' => 'range_30_days', 'sql' => 'DATE_SUB(CURDATE(), INTERVAL 29 DAY)', 'days' => 30],
    '1y' => ['label' => '1 year', 'key' => 'range_1_year', 'sql' => 'DATE_SUB(CURDATE(), INTERVAL 1 YEAR)', 'days' => 365],
    'all' => ['label' => 'All', 'key' => 'range_all', 'sql' => "'1970-01-01'", 'days' => 365],
];
if (!isset($rangeOptions[$range])) {
    $range = '30d';
}
$dateFromSql = $rangeOptions[$range]['sql'];
$rangeDays = (int)$rangeOptions[$range]['days'];

$summary = db_one_prepared(
    $conn,
    "SELECT
        (SELECT COUNT(*) FROM release_musical WHERE idCliente = ?) AS releases_count,
        (SELECT COUNT(*) FROM produto WHERE idCliente = ?) AS products_count,
        (SELECT COUNT(DISTINCT idEncomenda) FROM encomenda_item WHERE idArtista = ?) AS orders_count,
        (SELECT COALESCE(SUM(valor_artista), 0) FROM encomenda_item WHERE idArtista = ? AND estado_item = 'entregue') AS revenue_total,
        (SELECT COUNT(*) FROM faixa_listen WHERE idArtista = ?) AS listens_count,
        (SELECT COUNT(DISTINCT idCliente) FROM faixa_listen WHERE idArtista = ? AND idCliente IS NOT NULL) AS listeners_count,
        (SELECT COUNT(*) FROM faixa_listen WHERE idArtista = ? AND criado_em >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS listens_7d,
        (SELECT COUNT(*) FROM faixa_listen WHERE idArtista = ? AND criado_em >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND criado_em < DATE_SUB(NOW(), INTERVAL 7 DAY)) AS listens_prev_7d,
        (SELECT COUNT(DISTINCT idEncomenda) FROM encomenda_item WHERE idArtista = ? AND estado_item = 'pendente') AS pending_orders,
        (SELECT COUNT(DISTINCT CONCAT(idEncomenda, '-', idProduto, '-', idComprador)) FROM encomenda_mensagem WHERE idArtista = ? AND remetente = 'comprador' AND lida = 0) AS unread_messages",
    'iiiiiiiiii',
    [$uid, $uid, $uid, $uid, $uid, $uid, $uid, $uid, $uid, $uid]
) ?: [];

$releaseStatus = db_all_prepared(
    $conn,
    "SELECT estado, COUNT(*) AS total
     FROM release_musical
     WHERE idCliente = ?
     GROUP BY estado",
    'i',
    [$uid]
);
$releaseStatusMap = ['aprovado' => 0, 'pendente' => 0, 'rejeitado' => 0, 'inativo' => 0];
foreach ($releaseStatus as $statusRow) {
    $statusKey = (string)$statusRow['estado'];
    if (array_key_exists($statusKey, $releaseStatusMap)) {
        $releaseStatusMap[$statusKey] = (int)$statusRow['total'];
    }
}

$listenRows = db_all_prepared(
    $conn,
    "SELECT DATE(criado_em) AS day_key, COUNT(*) AS listens
     FROM faixa_listen
     WHERE idArtista = ?
       AND criado_em >= {$dateFromSql}
     GROUP BY DATE(criado_em)
     ORDER BY DATE(criado_em) ASC",
    'i',
    [$uid]
);
$listenMap = [];
foreach ($listenRows as $row) {
    $listenMap[(string)$row['day_key']] = (int)$row['listens'];
}
$listenDays = [];
$maxListens = 0;
for ($offset = min(364, max(6, $rangeDays - 1)); $offset >= 0; $offset--) {
    $date = new DateTimeImmutable("-{$offset} days");
    $key = $date->format('Y-m-d');
    $listens = $listenMap[$key] ?? 0;
    $listenDays[] = ['label' => $date->format('d/m'), 'listens' => $listens];
    $maxListens = max($maxListens, $listens);
}

$topTracks = db_all_prepared(
    $conn,
    "SELECT f.titulo, COALESCE(g.nome, f.genero) AS genero, r.capa, COUNT(fl.idListen) AS listens, COUNT(DISTINCT fl.idCliente) AS listeners
     FROM faixa f
     JOIN release_musical r ON r.idRelease = f.idRelease
     LEFT JOIN genero g ON g.idGenero = f.idGenero
     LEFT JOIN faixa_listen fl ON fl.idFaixa = f.idFaixa AND fl.criado_em >= {$dateFromSql}
     WHERE r.idCliente = ?
     GROUP BY f.idFaixa, f.titulo, g.nome, f.genero, r.capa
     ORDER BY listens DESC, f.idFaixa DESC
     LIMIT 6",
    'i',
    [$uid]
);

$latestOrders = db_all_prepared(
    $conn,
    "SELECT ei.idProduto, ei.nome_produto, ei.quantidade, ei.valor_artista, ei.estado_item, e.criado_em
     FROM encomenda_item ei
     JOIN encomenda e ON e.idEncomenda = ei.idEncomenda
     WHERE ei.idArtista = ?
     ORDER BY e.criado_em DESC
     LIMIT 4",
    'i',
    [$uid]
);

$recentMessages = db_all_prepared(
    $conn,
    "SELECT em.mensagem, em.criado_em, c.nome AS buyer_name, ei.nome_produto
     FROM encomenda_mensagem em
     JOIN cliente c ON c.idCliente = em.idComprador
     JOIN encomenda_item ei ON ei.idEncomenda = em.idEncomenda
       AND ei.idProduto = em.idProduto
       AND ei.idArtista = em.idArtista
     WHERE em.idArtista = ?
       AND em.remetente = 'comprador'
     ORDER BY em.lida ASC, em.criado_em DESC
     LIMIT 4",
    'i',
    [$uid]
);

$listens7d = (int)($summary['listens_7d'] ?? 0);
$prevListens7d = (int)($summary['listens_prev_7d'] ?? 0);
$listenDelta = $prevListens7d > 0
    ? (($listens7d - $prevListens7d) / $prevListens7d) * 100
    : ($listens7d > 0 ? 100 : 0);

$chartWidth = 620;
$chartHeight = 220;
$chartPadding = 28;
$chartPoints = [];
$chartAreaPoints = [];
$chartCount = max(1, count($listenDays) - 1);
foreach ($listenDays as $index => $day) {
    $x = $chartPadding + ($index / $chartCount) * ($chartWidth - ($chartPadding * 2));
    $ratio = $maxListens > 0 ? ((int)$day['listens'] / $maxListens) : 0;
    $y = ($chartHeight - $chartPadding) - ($ratio * ($chartHeight - ($chartPadding * 2)));
    $chartPoints[] = round($x, 1) . ',' . round($y, 1);
}
if ($chartPoints) {
    $firstX = explode(',', $chartPoints[0])[0];
    $lastX = explode(',', $chartPoints[count($chartPoints) - 1])[0];
    $chartAreaPoints = array_merge([$firstX . ',' . ($chartHeight - $chartPadding)], $chartPoints, [$lastX . ',' . ($chartHeight - $chartPadding)]);
}

include '../includes/header.php';
?>

<section class="artist-dashboard-v4 artist-overview-page">
  <header class="artist-dash-top">
    <div>
      <h2 data-t="artist_overview_title">Overview</h2>
    </div>
    <nav class="artist-dash-actions artist-dash-range" aria-label="Overview range">
      <?php foreach ($rangeOptions as $rangeKey => $rangeItem): ?>
        <a href="artist_dashboard.php?range=<?= h($rangeKey) ?>" class="<?= $range === $rangeKey ? 'on' : '' ?>" data-t="<?= h($rangeItem['key']) ?>"><?= h($rangeItem['label']) ?></a>
      <?php endforeach; ?>
    </nav>
  </header>

  <div class="artist-dash-kpis">
    <a href="upload_music.php">
      <span data-t="artist_overview_releases">Releases</span>
      <strong><?= (int)($summary['releases_count'] ?? 0) ?></strong>
    </a>
    <a href="#artist-listening">
      <span data-t="artist_overview_plays">Plays</span>
      <strong><?= (int)($summary['listens_count'] ?? 0) ?></strong>
    </a>
    <a href="artist_messages.php">
      <span data-t="artist_overview_messages">Messages</span>
      <strong><?= (int)($summary['unread_messages'] ?? 0) ?></strong>
    </a>
    <a href="revenue.php">
      <span data-t="artist_overview_revenue">Revenue</span>
      <strong><?= h(format_eur((float)($summary['revenue_total'] ?? 0))) ?></strong>
    </a>
  </div>

  <div class="artist-dash-grid artist-animate">
    <section class="artist-dash-card artist-dash-main-chart" id="artist-listening">
      <div class="artist-dash-card-head">
        <div>
          <h3><?= (int)($summary['listens_count'] ?? 0) ?> <span data-t="artist_overview_plays_lower">plays</span></h3>
        </div>
        <span class="artist-dash-note" data-t="<?= h($rangeOptions[$range]['key']) ?>"><?= h($rangeOptions[$range]['label']) ?></span>
      </div>
      <?php if (count($listenDays) < 2): ?>
        <p class="artist-dash-empty" data-t="artist_overview_no_listening">No listening data yet.</p>
      <?php else: ?>
        <svg class="artist-dash-line-chart" viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>" role="img" aria-label="Listening trend">
          <defs>
            <linearGradient id="artistListenFill" x1="0" x2="0" y1="0" y2="1">
              <stop offset="0%" stop-color="#c9d0db" stop-opacity=".42"/>
              <stop offset="100%" stop-color="#c9d0db" stop-opacity="0"/>
            </linearGradient>
          </defs>
          <g class="artist-chart-grid-lines">
            <line x1="28" y1="48" x2="592" y2="48"/>
            <line x1="28" y1="92" x2="592" y2="92"/>
            <line x1="28" y1="136" x2="592" y2="136"/>
            <line x1="28" y1="180" x2="592" y2="180"/>
          </g>
          <?php if ($chartAreaPoints): ?>
            <polygon points="<?= h(implode(' ', $chartAreaPoints)) ?>" fill="url(#artistListenFill)"/>
            <polyline points="<?= h(implode(' ', $chartPoints)) ?>" fill="none" stroke="#c9d0db" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
            <?php foreach ($chartPoints as $index => $point): ?>
              <?php [$cx, $cy] = explode(',', $point); ?>
              <circle cx="<?= h($cx) ?>" cy="<?= h($cy) ?>" r="5">
                <title><?= h($listenDays[$index]['label'] . ': ' . (int)$listenDays[$index]['listens'] . ' plays') ?></title>
              </circle>
            <?php endforeach; ?>
          <?php endif; ?>
        </svg>
        <div class="artist-dash-months">
          <?php $lastLabel = max(0, count($listenDays) - 1); ?>
          <?php $labelStep = max(1, (int)floor(count($listenDays) / 6)); ?>
          <?php foreach ($listenDays as $index => $day): ?>
            <?php if ($index % $labelStep === 0 || $index === $lastLabel): ?><span><?= h($day['label']) ?></span><?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="artist-dash-card artist-dash-attention">
      <div class="artist-dash-card-head">
        <div>
          <h3 data-t="artist_overview_quick_view">Quick view</h3>
        </div>
      </div>
      <div class="artist-dash-list">
        <a href="artist_messages.php"><i>!</i><p><strong><?= (int)($summary['unread_messages'] ?? 0) ?> <span data-t="artist_overview_unread_messages">unread messages</span></strong></p></a>
        <a href="orders.php"><i>#</i><p><strong><?= (int)($summary['pending_orders'] ?? 0) ?> <span data-t="artist_overview_pending_orders">pending orders</span></strong></p></a>
        <a href="upload_music.php"><i>+</i><p><strong><?= (int)$releaseStatusMap['pendente'] ?> <span data-t="artist_overview_pending_releases">pending releases</span></strong></p></a>
      </div>
    </section>

    <section class="artist-dash-card">
      <div class="artist-dash-card-head">
        <div>
          <h3 data-t="artist_overview_most_played">Most played</h3>
        </div>
      </div>
      <?php if (!$topTracks): ?>
        <p class="artist-dash-empty" data-t="artist_overview_no_tracks">No submitted tracks yet.</p>
      <?php else: ?>
        <div class="artist-dash-table">
          <div class="artist-dash-table-head"><span data-t="artist_table_track">Track</span><span data-t="artist_table_genre">Genre</span><span data-t="artist_table_listeners">Listeners</span><span data-t="artist_table_plays">Plays</span></div>
          <?php foreach ($topTracks as $track): ?>
            <div class="artist-dash-table-row">
              <strong class="artist-mini-media">
                <span class="artist-mini-thumb">
                  <?php if (!empty($track['capa'])): ?><img src="<?= h(asset_url('img', $track['capa'])) ?>" alt=""><?php else: ?><span data-t="artist_overview_music">Music</span><?php endif; ?>
                </span>
                <?= h($track['titulo']) ?>
              </strong>
              <span <?= $track['genero'] ? '' : 'data-lang-pt="Sem género" data-lang-en="No genre"' ?>><?= h($track['genero'] ?: (current_lang() === 'en' ? 'No genre' : 'Sem género')) ?></span>
              <span><?= (int)$track['listeners'] ?></span>
              <b><?= (int)$track['listens'] ?></b>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="artist-dash-card">
      <div class="artist-dash-card-head">
        <div>
          <span class="artist-dash-kicker" data-t="artist_overview_buyer_messages">Buyer messages</span>
          <h3 data-t="artist_overview_latest_contact">Latest contact</h3>
        </div>
      </div>
      <?php if (!$recentMessages): ?>
        <p class="artist-dash-empty" data-t="artist_messages_empty">No buyer messages yet.</p>
      <?php else: ?>
        <div class="artist-dash-list">
          <?php foreach ($recentMessages as $message): ?>
            <a href="artist_messages.php"><i>?</i><p><strong><?= h($message['buyer_name']) ?></strong><small><?= h($message['nome_produto']) ?> / <?= h(mb_substr((string)$message['mensagem'], 0, 70)) ?></small></p><time><?= h(date('d/m H:i', strtotime((string)$message['criado_em']))) ?></time></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="artist-dash-card">
      <div class="artist-dash-card-head">
        <div>
          <h3 data-t="artist_overview_latest_orders">Latest orders</h3>
        </div>
      </div>
      <?php if (!$latestOrders): ?>
        <p class="artist-dash-empty" data-t="artist_overview_no_orders">No merch orders yet.</p>
      <?php else: ?>
        <div class="artist-dash-list">
          <?php foreach ($latestOrders as $order): ?>
            <?php $orderImage = product_main_image($conn, (int)$order['idProduto']); ?>
            <a href="orders.php">
              <span class="artist-mini-thumb">
                <?php if ($orderImage): ?><img src="<?= h(asset_url('img', $orderImage)) ?>" alt=""><?php else: ?><span data-t="artist_overview_item">Item</span><?php endif; ?>
              </span>
              <p><strong><?= h($order['nome_produto']) ?></strong><small><?= (int)$order['quantidade'] ?> <span data-t="artist_overview_items">item(s)</span> / <?= h(format_eur((float)$order['valor_artista'])) ?></small></p><time data-status-label="<?= h($order['estado_item']) ?>"><?= h(order_status_label($order['estado_item'])) ?></time>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
