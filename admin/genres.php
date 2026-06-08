<?php
require_once '../includes/config.php';
require_admin_permission('genres');

$feedback = '';
$error = '';

function admin_genre_public_count(mysqli $conn, int $genreId): int
{
    if ($genreId <= 0) {
        return 0;
    }

    $row = db_one(
        $conn,
        "SELECT COUNT(DISTINCT f.idFaixa) AS total
         FROM faixa f
         JOIN release_musical r ON r.idRelease = f.idRelease
         JOIN cliente c ON c.idCliente = r.idCliente
         WHERE f.idGenero = {$genreId}
           AND f.estado = 'aprovada'
           AND f.ativo = 1
           AND r.estado = 'aprovado'
           AND r.ativo = 1
           AND c.estado = 'ativo'"
    );

    return (int)($row['total'] ?? 0);
}

function admin_genre_unique_slug(mysqli $conn, string $name, int $ignoreId = 0): string
{
    $baseSlug = greenerry_genre_slug($name);
    $slug = $baseSlug;
    $suffix = 2;

    while (true) {
        $existing = db_one_prepared(
            $conn,
            "SELECT idGenero FROM genero WHERE slug = ? AND idGenero != ? LIMIT 1",
            'si',
            [$slug, $ignoreId]
        );
        if (!$existing) {
            return $slug;
        }

        $slug = mb_substr($baseSlug, 0, 94) . '-' . $suffix;
        $suffix++;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = verify_csrf_request() ?? '';
    $action = (string)($_POST['action'] ?? '');
    $genreId = (int)($_POST['genre_id'] ?? 0);
    $name = mb_substr(trim((string)($_POST['nome'] ?? '')), 0, 80);
    $state = ($_POST['estado'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo';

    if (!$error && in_array($action, ['create', 'update'], true)) {
        if (mb_strlen($name) < 2) {
            $error = current_lang() === 'en' ? 'Genre name must have at least 2 characters.' : 'O nome do genero deve ter pelo menos 2 caracteres.';
        } else {
            $exists = db_one_prepared(
                $conn,
                "SELECT idGenero FROM genero WHERE LOWER(nome) = LOWER(?) AND idGenero != ? LIMIT 1",
                'si',
                [$name, $genreId]
            );

            if ($exists) {
                $error = current_lang() === 'en' ? 'There is already a genre with that name.' : 'Ja existe um genero com esse nome.';
            } elseif ($action === 'update' && $genreId > 0) {
                $current = db_one($conn, "SELECT estado FROM genero WHERE idGenero = {$genreId} LIMIT 1");
                $publicTracks = admin_genre_public_count($conn, $genreId);
                $confirmedImpact = (string)($_POST['confirm_impact'] ?? '') === '1';

                if (($current['estado'] ?? '') === 'ativo' && $state === 'inativo' && $publicTracks > 0 && !$confirmedImpact) {
                    $error = current_lang() === 'en'
                        ? "Confirm before deactivating this genre. It is used by {$publicTracks} public track(s)."
                        : "Confirma antes de inativar este genero. Esta em {$publicTracks} faixa(s) publica(s).";
                }
            }

            if (!$error) {
                $slug = admin_genre_unique_slug($conn, $name, $action === 'update' ? $genreId : 0);

                if ($action === 'create') {
                    db_prepared(
                        $conn,
                        "INSERT INTO genero (nome, slug, estado) VALUES (?, ?, ?)",
                        'sss',
                        [$name, $slug, $state]
                    );
                    $feedback = current_lang() === 'en' ? 'Genre created.' : 'Genero criado.';
                } elseif ($genreId > 0) {
                    db_prepared(
                        $conn,
                        "UPDATE genero SET nome = ?, slug = ?, estado = ? WHERE idGenero = ?",
                        'sssi',
                        [$name, $slug, $state, $genreId]
                    );
                    db_prepared($conn, "UPDATE faixa SET genero = ? WHERE idGenero = ?", 'si', [$name, $genreId]);
                    $feedback = current_lang() === 'en' ? 'Genre updated.' : 'Genero atualizado.';
                }
            }
        }
    }
}

$genres = db_all(
    $conn,
    "SELECT
        g.*,
        COUNT(DISTINCT f.idFaixa) AS total_tracks,
        COUNT(DISTINCT r.idRelease) AS total_releases,
        SUM(f.estado = 'pendente') AS pending_tracks
     FROM genero g
     LEFT JOIN faixa f ON f.idGenero = g.idGenero
     LEFT JOIN release_musical r ON r.idGenero = g.idGenero
     GROUP BY g.idGenero
     ORDER BY g.estado ASC, g.nome ASC"
);

$genreStats = [
    'ativos' => 0,
    'inativos' => 0,
    'tracks' => 0,
    'releases' => 0,
];
foreach ($genres as $genre) {
    if (($genre['estado'] ?? '') === 'ativo') {
        $genreStats['ativos']++;
    } else {
        $genreStats['inativos']++;
    }
    $genreStats['tracks'] += (int)($genre['total_tracks'] ?? 0);
    $genreStats['releases'] += (int)($genre['total_releases'] ?? 0);
}

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <span class="admin-page-kicker" data-admin-t="genres_kicker">Music taxonomy</span>
    <h2 data-admin-t="genres_title">Generos</h2>
    <p data-admin-t="genres_intro">Lista, cria e edita os generos usados nas faixas e lancamentos.</p>
  </div>
  <div class="stats-grid admin-top-stats">
    <button type="button" class="stat stat-button" data-admin-stat-filter="genres-search" data-filter-value="ativo"><div class="stat-val"><?= (int)$genreStats['ativos'] ?></div><div class="stat-lbl" data-admin-t="state_active">Ativo</div></button>
    <button type="button" class="stat stat-button" data-admin-stat-filter="genres-search" data-filter-value="inativo"><div class="stat-val"><?= (int)$genreStats['inativos'] ?></div><div class="stat-lbl" data-admin-t="state_inactive">Inativos</div></button>
    <div class="stat"><div class="stat-val"><?= (int)$genreStats['tracks'] ?></div><div class="stat-lbl" data-admin-t="label_tracks">Faixas</div></div>
    <div class="stat"><div class="stat-val"><?= (int)$genreStats['releases'] ?></div><div class="stat-lbl" data-admin-t="releases_title">Lancamentos</div></div>
  </div>
</div>

<?php if ($feedback): ?>
  <div class="alert alert-ok"><?= h($feedback) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-err"><?= h($error) ?></div>
<?php endif; ?>

<details class="acard-box admin-collapse-panel">
  <summary class="acard-box-head admin-collapse-summary">
    <div>
      <span class="admin-kicker" data-admin-t="genres_new_kicker">Musica</span>
      <h4 data-admin-t="genres_new_title">Novo genero</h4>
    </div>
  </summary>

  <form method="post" class="stack-form admin-category-create-form">
    <?= csrf_input() ?>
    <input type="hidden" name="action" value="create">

    <div class="fg admin-category-name-field">
      <label class="flabel" for="new-genre-name" data-admin-t="categories_name">Nome</label>
      <input id="new-genre-name" type="text" name="nome" class="finput" required maxlength="80">
    </div>

    <div class="fg admin-category-state-field">
      <label class="flabel" for="new-genre-state" data-admin-t="categories_state">Estado</label>
      <select id="new-genre-state" name="estado" class="finput">
        <option value="ativo" data-admin-t="state_active">Ativo</option>
        <option value="inativo" data-admin-t="state_inactive">Inativo</option>
      </select>
    </div>

    <button type="submit" class="btn btn-dark admin-category-submit" data-admin-t="genres_create">Criar genero</button>
  </form>
</details>

<div id="genres-search" data-admin-search-scope>
<section class="acard-box">
  <div class="acard-box-head">
    <h4 data-admin-t="genres_all">Generos existentes</h4>
    <div class="admin-card-head-tools">
      <label class="sbar admin-section-search">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" data-admin-search="genres-search" placeholder="Pesquisar..." data-admin-tp="admin_search_placeholder">
      </label>
      <span class="badge badge-light"><?= count($genres) ?></span>
    </div>
  </div>

  <?php if (!$genres): ?>
    <p data-admin-t="genres_empty">Ainda nao existem generos.</p>
  <?php else: ?>
    <div class="admin-card-list">
      <?php foreach ($genres as $genre): ?>
        <?php
          $genrePublicTracks = admin_genre_public_count($conn, (int)$genre['idGenero']);
          $genreConfirm = current_lang() === 'en'
              ? 'Deactivate this genre? It will stop appearing as an active suggestion/filter.'
              : 'Inativar este genero? Vai deixar de aparecer como sugestao/filtro ativo.';
        ?>
        <article class="admin-review-card admin-genre-card" data-admin-state="<?= h($genre['estado'] . ' ' . $genre['nome'] . ' ' . $genre['slug']) ?>">
          <form method="post" class="stack-form admin-genre-edit-form" <?= $genrePublicTracks > 0 && $genre['estado'] === 'ativo' ? 'data-confirm="' . h($genreConfirm) . '" data-confirm-if-state="inativo"' : '' ?>>
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="genre_id" value="<?= (int)$genre['idGenero'] ?>">

            <div class="admin-genre-fields">
              <div class="fg">
                <label class="flabel" data-admin-t="categories_name">Nome</label>
                <input type="text" name="nome" class="finput" value="<?= h($genre['nome']) ?>" required maxlength="80">
              </div>

              <div class="fg">
                <label class="flabel" data-admin-t="categories_state">Estado</label>
                <select name="estado" class="finput">
                  <option value="ativo" <?= $genre['estado'] === 'ativo' ? 'selected' : '' ?> data-admin-t="state_active">Ativo</option>
                  <option value="inativo" <?= $genre['estado'] === 'inativo' ? 'selected' : '' ?> data-admin-t="state_inactive">Inativo</option>
                </select>
              </div>
            </div>

            <div class="admin-genre-meta">
              <span class="badge <?= h(state_badge_class($genre['estado'])) ?>"><?= h(order_status_label($genre['estado'])) ?></span>
              <span><strong><?= (int)$genre['total_tracks'] ?></strong> <span data-admin-t="label_tracks">faixas</span></span>
              <span><strong><?= (int)$genre['total_releases'] ?></strong> <span data-admin-t="releases_title">lancamentos</span></span>
              <span class="admin-genre-slug"><?= h($genre['slug']) ?></span>
            </div>

            <div class="admin-genre-actions">
              <button type="submit" class="btn btn-dark btn-sm" data-admin-t="categories_save">Guardar</button>
            </div>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
</div>

<?php include 'admin_footer.php'; ?>
