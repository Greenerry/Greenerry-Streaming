<?php
require_once '../includes/config.php';
require_admin_permission('home');

$feedback = '';
$error = '';
$settings = [
    'featured_artist_id' => site_setting('featured_artist_id', '0'),
    'featured_release_id' => site_setting('featured_release_id', '0'),
    'featured_product_id' => site_setting('featured_product_id', '0'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = verify_csrf_request() ?? '';
    foreach ($settings as $key => $value) {
        $settings[$key] = (string)max(0, (int)($_POST[$key] ?? 0));
    }

    if ($error === '') {
        mysqli_begin_transaction($conn);
        $ok = true;
        foreach ($settings as $key => $value) {
            $keySafe = db_escape($conn, $key);
            $valueSafe = db_escape($conn, $value);
            $ok = $ok && mysqli_query(
                $conn,
                "INSERT INTO configuracao_site (chave_configuracao, valor_configuracao)
                 VALUES ('{$keySafe}', '{$valueSafe}')
                 ON DUPLICATE KEY UPDATE valor_configuracao = VALUES(valor_configuracao)"
            );
        }

        if ($ok) {
            mysqli_commit($conn);
            reload_site_settings();
            $feedback = tr('success.settings_updated');
        } else {
            mysqli_rollback($conn);
            $error = tr('error.settings_update');
        }
    }
}

$artistOptions = db_all(
    $conn,
    "SELECT c.idCliente, c.nome, c.slug, c.foto,
            COUNT(DISTINCT r.idRelease) AS total_releases,
            COUNT(DISTINCT p.idProduto) AS total_products
     FROM cliente c
     LEFT JOIN release_musical r ON r.idCliente = c.idCliente AND r.estado = 'aprovado' AND r.ativo = 1
     LEFT JOIN produto p ON p.idCliente = c.idCliente AND p.estado = 'aprovado' AND p.ativo = 1
     WHERE c.estado = 'ativo'
     GROUP BY c.idCliente
     ORDER BY c.nome ASC
     LIMIT 200"
);
$releaseOptions = db_all(
    $conn,
    "SELECT r.idRelease, r.titulo, r.tipo, r.capa, r.data_lancamento, c.nome AS artista
     FROM release_musical r
     JOIN cliente c ON c.idCliente = r.idCliente
     WHERE r.estado = 'aprovado'
       AND r.ativo = 1
       AND c.estado = 'ativo'
     ORDER BY r.criado_em DESC
     LIMIT 200"
);
$productOptions = db_all(
    $conn,
    "SELECT p.idProduto, p.nomeProduto, p.precoAtual, c.nome AS artista, cat.nomeCategoria
     FROM produto p
     JOIN cliente c ON c.idCliente = p.idCliente
     JOIN categoria cat ON cat.idCategoria = p.idCategoria
     WHERE p.estado = 'aprovado'
       AND p.ativo = 1
       AND c.estado = 'ativo'
     ORDER BY p.criado_em DESC
     LIMIT 200"
);

$selectedArtist = null;
foreach ($artistOptions as $artist) {
    if ((int)$settings['featured_artist_id'] === (int)$artist['idCliente']) {
        $selectedArtist = $artist;
        break;
    }
}
$selectedRelease = null;
foreach ($releaseOptions as $release) {
    if ((int)$settings['featured_release_id'] === (int)$release['idRelease']) {
        $selectedRelease = $release;
        break;
    }
}
$selectedProduct = null;
foreach ($productOptions as $product) {
    if ((int)$settings['featured_product_id'] === (int)$product['idProduto']) {
        $selectedProduct = $product;
        break;
    }
}

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <span class="admin-page-kicker" data-admin-t="home_curator_kicker">Homepage</span>
    <h2 data-admin-t="settings_home_curator">Curadoria da homepage</h2>
    <p data-admin-t="home_curator_intro">Escolhe os destaques que aparecem na entrada publica da Greenerry.</p>
  </div>
</div>

<?php if ($feedback): ?>
  <div class="alert alert-ok"><?= h($feedback) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-err"><?= h($error) ?></div>
<?php endif; ?>

<section class="curator-overview">
  <a href="#curator-artists" class="curator-overview-card" data-curator-summary="featured_artist_id">
    <span class="admin-kicker" data-admin-t="settings_featured_artist">Artista em destaque</span>
    <strong><?= h($selectedArtist['nome'] ?? 'Automatico') ?></strong>
    <small data-admin-t="<?= $selectedArtist ? 'home_curator_selected_manual' : 'home_curator_selected_auto' ?>"><?= $selectedArtist ? 'Escolhido manualmente' : 'Escolha automatica' ?></small>
  </a>
  <a href="#curator-releases" class="curator-overview-card" data-curator-summary="featured_release_id">
    <span class="admin-kicker" data-admin-t="settings_featured_release">Release em destaque</span>
    <strong><?= h($selectedRelease['titulo'] ?? 'Automatico') ?></strong>
    <small data-admin-t="<?= $selectedRelease ? 'home_curator_selected_manual' : 'home_curator_selected_auto' ?>"><?= $selectedRelease ? 'Escolhido manualmente' : 'Escolha automatica' ?></small>
  </a>
  <a href="#curator-products" class="curator-overview-card" data-curator-summary="featured_product_id">
    <span class="admin-kicker" data-admin-t="settings_featured_product">Produto em destaque</span>
    <strong><?= h($selectedProduct['nomeProduto'] ?? 'Automatico') ?></strong>
    <small data-admin-t="<?= $selectedProduct ? 'home_curator_selected_manual' : 'home_curator_selected_auto' ?>"><?= $selectedProduct ? 'Escolhido manualmente' : 'Escolha automatica' ?></small>
  </a>
</section>

<form method="post" class="home-curator-form">
  <?= csrf_input() ?>

  <section class="acard-box settings-panel home-curator-section" id="curator-artists" data-admin-search-scope>
    <div class="settings-panel-summary settings-panel-summary--static">
      <div>
        <h4 data-admin-t="settings_featured_artist">Artista em destaque</h4>
        <span data-admin-t="home_curator_artist_note">Procura pelo nome, slug ou atividade do artista.</span>
      </div>
      <label class="sbar admin-section-search">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" data-admin-search="curator-artists" placeholder="Pesquisar artista..." data-admin-tp="home_curator_artist_search">
      </label>
    </div>
    <div class="curator-option-grid">
      <label class="curator-option curator-option--auto" data-curator-label="Automatico">
        <input type="radio" name="featured_artist_id" value="0" <?= (int)$settings['featured_artist_id'] === 0 ? 'checked' : '' ?>>
        <span class="curator-thumb"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 3v18M3 12h18"/></svg></span>
        <strong data-admin-t="settings_auto">Automatico</strong>
        <small data-admin-t="home_curator_auto_artist">Melhor artista ativo.</small>
      </label>
      <?php foreach ($artistOptions as $artist): ?>
        <label class="curator-option" data-curator-label="<?= h($artist['nome']) ?>">
          <input type="radio" name="featured_artist_id" value="<?= (int)$artist['idCliente'] ?>" <?= (int)$settings['featured_artist_id'] === (int)$artist['idCliente'] ? 'checked' : '' ?>>
          <span class="curator-thumb">
            <?php if (!empty($artist['foto'])): ?>
              <img src="<?= h(asset_url('img', $artist['foto'])) ?>" alt="">
            <?php else: ?>
              <?= h(mb_substr((string)$artist['nome'], 0, 1)) ?>
            <?php endif; ?>
          </span>
          <strong><?= h($artist['nome']) ?></strong>
          <small><?= h($artist['slug'] ?? '') ?> &middot; <?= (int)$artist['total_releases'] ?> releases &middot; <?= (int)$artist['total_products'] ?> products</small>
        </label>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="acard-box settings-panel home-curator-section" id="curator-releases" data-admin-search-scope>
    <div class="settings-panel-summary settings-panel-summary--static">
      <div>
        <h4 data-admin-t="settings_featured_release">Release em destaque</h4>
        <span data-admin-t="home_curator_release_note">Procura por titulo, artista, tipo ou data.</span>
      </div>
      <label class="sbar admin-section-search">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" data-admin-search="curator-releases" placeholder="Pesquisar release..." data-admin-tp="home_curator_release_search">
      </label>
    </div>
    <div class="curator-option-grid">
      <label class="curator-option curator-option--auto" data-curator-label="Automatico">
        <input type="radio" name="featured_release_id" value="0" <?= (int)$settings['featured_release_id'] === 0 ? 'checked' : '' ?>>
        <span class="curator-thumb"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 3v18M3 12h18"/></svg></span>
        <strong data-admin-t="settings_auto">Automatico</strong>
        <small data-admin-t="home_curator_auto_release">Release aprovado mais relevante.</small>
      </label>
      <?php foreach ($releaseOptions as $release): ?>
        <label class="curator-option" data-curator-label="<?= h($release['titulo']) ?>">
          <input type="radio" name="featured_release_id" value="<?= (int)$release['idRelease'] ?>" <?= (int)$settings['featured_release_id'] === (int)$release['idRelease'] ? 'checked' : '' ?>>
          <span class="curator-thumb curator-thumb--cover">
            <?php if (!empty($release['capa'])): ?>
              <img src="<?= h(asset_url('img', $release['capa'])) ?>" alt="">
            <?php else: ?>
              <?= h(mb_substr((string)$release['titulo'], 0, 1)) ?>
            <?php endif; ?>
          </span>
          <strong><?= h($release['titulo']) ?></strong>
          <small><?= h($release['artista']) ?> &middot; <?= h($release['tipo']) ?><?= !empty($release['data_lancamento']) ? ' &middot; ' . h(date('d/m/Y', strtotime($release['data_lancamento']))) : '' ?></small>
        </label>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="acard-box settings-panel home-curator-section" id="curator-products" data-admin-search-scope>
    <div class="settings-panel-summary settings-panel-summary--static">
      <div>
        <h4 data-admin-t="settings_featured_product">Produto em destaque</h4>
        <span data-admin-t="home_curator_product_note">Procura por produto, artista, categoria ou preco.</span>
      </div>
      <label class="sbar admin-section-search">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" data-admin-search="curator-products" placeholder="Pesquisar produto..." data-admin-tp="home_curator_product_search">
      </label>
    </div>
    <div class="curator-option-grid">
      <label class="curator-option curator-option--auto" data-curator-label="Automatico">
        <input type="radio" name="featured_product_id" value="0" <?= (int)$settings['featured_product_id'] === 0 ? 'checked' : '' ?>>
        <span class="curator-thumb"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 3v18M3 12h18"/></svg></span>
        <strong data-admin-t="settings_auto">Automatico</strong>
        <small data-admin-t="home_curator_auto_product">Produto aprovado mais recente.</small>
      </label>
      <?php foreach ($productOptions as $product): ?>
        <?php $productImage = product_main_image($conn, (int)$product['idProduto']); ?>
        <label class="curator-option" data-curator-label="<?= h($product['nomeProduto']) ?>">
          <input type="radio" name="featured_product_id" value="<?= (int)$product['idProduto'] ?>" <?= (int)$settings['featured_product_id'] === (int)$product['idProduto'] ? 'checked' : '' ?>>
          <span class="curator-thumb curator-thumb--cover">
            <?php if ($productImage): ?>
              <img src="<?= h(asset_url('img', $productImage)) ?>" alt="">
            <?php else: ?>
              <?= h(mb_substr((string)$product['nomeProduto'], 0, 1)) ?>
            <?php endif; ?>
          </span>
          <strong><?= h($product['nomeProduto']) ?></strong>
          <small><?= h($product['artista']) ?> &middot; <?= h($product['nomeCategoria']) ?> &middot; <?= number_format((float)$product['precoAtual'], 2, ',', '.') ?> EUR</small>
        </label>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="acard-box settings-save-card curator-save-card">
    <div>
      <span class="admin-kicker" data-admin-t="settings_live_kicker">User side</span>
      <h4 data-admin-t="home_curator_save_note">Os destaques atualizam a homepage publica.</h4>
    </div>
    <button type="submit" class="btn btn-dark" data-admin-t="settings_save">Guardar definicoes</button>
  </section>
</form>

<?php include 'admin_footer.php'; ?>
