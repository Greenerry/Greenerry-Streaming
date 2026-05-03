<?php
require_once '../includes/config.php';
require_admin_login();

$adminId = current_admin_id();
$feedback = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = verify_csrf_request() ?? '';
    $action = $_POST['action'] ?? '';
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $name = trim((string)($_POST['nomeCategoria'] ?? ''));
    $description = trim((string)($_POST['descricaoCategoria'] ?? ''));
    $usesSizes = !empty($_POST['usa_tamanhos']) ? 1 : 0;
    $state = ($_POST['estado'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo';

    if (!$error && in_array($action, ['create_size', 'update_size'], true)) {
        $sizeId = (int)($_POST['size_id'] ?? 0);
        $code = trim((string)($_POST['codigo'] ?? ''));
        $label = trim((string)($_POST['etiqueta'] ?? ''));
        $order = (int)($_POST['ordem'] ?? 0);
        $active = !empty($_POST['ativo']) ? 1 : 0;

        if ($code === '') {
            $error = tr('error.size_code_required');
        } else {
            $codeSafe = db_escape($conn, mb_strtoupper($code));
            $labelSafe = db_escape($conn, $label !== '' ? $label : mb_strtoupper($code));
            $existsSql = "SELECT idTamanho FROM tamanho WHERE codigo = '{$codeSafe}'";
            if ($action === 'update_size') {
                $existsSql .= " AND idTamanho != {$sizeId}";
            }
            $exists = db_one($conn, $existsSql . " LIMIT 1");

            if ($exists) {
                $error = tr('error.size_exists');
            } elseif ($action === 'create_size') {
                mysqli_query(
                    $conn,
                    "INSERT INTO tamanho (codigo, etiqueta, ordem, ativo)
                     VALUES ('{$codeSafe}', '{$labelSafe}', {$order}, {$active})"
                );
                $feedback = tr('success.size_created');
            } elseif ($sizeId > 0) {
                mysqli_query(
                    $conn,
                    "UPDATE tamanho
                     SET codigo = '{$codeSafe}',
                         etiqueta = '{$labelSafe}',
                         ordem = {$order},
                         ativo = {$active}
                     WHERE idTamanho = {$sizeId}"
                );
                $feedback = tr('success.size_updated');
            }
        }
    }

    if (!$error && in_array($action, ['create', 'update'], true)) {
        if ($name === '' || mb_strlen($name) < 2) {
            $error = tr('error.category_name_short');
        } else {
            $nameSafe = db_escape($conn, $name);
            $descriptionSafe = db_escape($conn, $description);
            $stateSafe = db_escape($conn, $state);

            $existsSql = "SELECT idCategoria FROM categoria WHERE nomeCategoria = '{$nameSafe}'";
            if ($action === 'update') {
                $existsSql .= " AND idCategoria != {$categoryId}";
            }
            $exists = db_one($conn, $existsSql . " LIMIT 1");

            if ($exists) {
                $error = tr('error.category_exists');
            } elseif ($action === 'create') {
                mysqli_query(
                    $conn,
                    "INSERT INTO categoria (nomeCategoria, descricaoCategoria, usa_tamanhos, estado, idAdminCriador)
                     VALUES ('{$nameSafe}', '{$descriptionSafe}', {$usesSizes}, '{$stateSafe}', {$adminId})"
                );
                $feedback = tr('success.category_created');
            } elseif ($categoryId > 0) {
                mysqli_query(
                    $conn,
                    "UPDATE categoria
                     SET nomeCategoria = '{$nameSafe}',
                         descricaoCategoria = '{$descriptionSafe}',
                         usa_tamanhos = {$usesSizes},
                         estado = '{$stateSafe}'
                     WHERE idCategoria = {$categoryId}"
                );
                $feedback = tr('success.category_updated');
            }
        }
    }

    if (!$error && $action === 'toggle' && $categoryId > 0) {
        $category = db_one($conn, "SELECT estado FROM categoria WHERE idCategoria = {$categoryId} LIMIT 1");
        if ($category) {
            $nextState = $category['estado'] === 'ativo' ? 'inativo' : 'ativo';
            mysqli_query($conn, "UPDATE categoria SET estado = '{$nextState}' WHERE idCategoria = {$categoryId}");
            $feedback = tr('success.category_state_updated');
        }
    }
}

$categories = db_all(
    $conn,
    "SELECT
        cat.*,
        COUNT(p.idProduto) AS total_produtos,
        SUM(p.estado = 'pendente') AS produtos_pendentes
     FROM categoria cat
     LEFT JOIN produto p ON p.idCategoria = cat.idCategoria
     GROUP BY cat.idCategoria
     ORDER BY cat.estado ASC, cat.nomeCategoria ASC"
);

$sizes = db_all($conn, "SELECT * FROM tamanho ORDER BY ordem ASC, etiqueta ASC");

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <h2 data-admin-t="categories_title">Categorias</h2>
  </div>
</div>

<?php if ($feedback): ?>
  <div class="alert alert-ok"><?= h($feedback) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-err"><?= h($error) ?></div>
<?php endif; ?>

<div class="admin-workspace-grid">
  <section class="acard-box">
    <div class="acard-box-head">
      <div>
        <span class="admin-kicker" data-admin-t="categories_new_kicker">Catalogo</span>
        <h4 data-admin-t="categories_new_title">Nova categoria</h4>
      </div>
    </div>

    <form method="post" class="stack-form admin-category-create-form">
      <?= csrf_input() ?>
      <input type="hidden" name="action" value="create">

      <div class="fg admin-category-name-field">
        <label class="flabel" for="new-category-name" data-admin-t="categories_name">Nome</label>
        <input id="new-category-name" type="text" name="nomeCategoria" class="finput" required maxlength="100">
      </div>

      <div class="fg admin-category-description-field">
        <label class="flabel" for="new-category-description" data-admin-t="categories_description">Descricao</label>
        <textarea id="new-category-description" name="descricaoCategoria" class="finput" rows="4"></textarea>
      </div>

      <label class="admin-size-button admin-category-size-toggle">
        <input type="checkbox" name="usa_tamanhos" value="1">
        <span data-admin-t="categories_uses_sizes">Usa tamanhos</span>
      </label>

      <div class="fg admin-category-state-field">
        <label class="flabel" for="new-category-state" data-admin-t="categories_state">Estado</label>
        <select id="new-category-state" name="estado" class="finput">
          <option value="ativo" data-admin-t="state_active">Ativo</option>
          <option value="inativo" data-admin-t="state_inactive">Inativo</option>
        </select>
      </div>

      <button type="submit" class="btn btn-dark admin-category-submit" data-admin-t="categories_create">Criar categoria</button>
    </form>
  </section>

  <section class="acard-box">
    <div class="acard-box-head">
      <div>
        <span class="admin-kicker" data-admin-t="categories_sizes_kicker">Tamanhos</span>
        <h4 data-admin-t="categories_sizes_title">Gerir tamanhos</h4>
      </div>
    </div>

    <form method="post" class="admin-size-create-form">
      <?= csrf_input() ?>
      <input type="hidden" name="action" value="create_size">

      <div class="fg">
        <label class="flabel" for="new-size-code" data-admin-t="sizes_code">Codigo</label>
        <input id="new-size-code" type="text" name="codigo" class="finput" required maxlength="20" placeholder="XL">
      </div>

      <div class="fg">
        <label class="flabel" for="new-size-label" data-admin-t="sizes_label">Etiqueta</label>
        <input id="new-size-label" type="text" name="etiqueta" class="finput" maxlength="30" placeholder="XXL">
      </div>

      <div class="fg">
        <label class="flabel" for="new-size-order" data-admin-t="sizes_order">Ordem</label>
        <input id="new-size-order" type="number" name="ordem" class="finput" value="0">
      </div>

      <div class="admin-size-actions">
        <label class="admin-size-button admin-size-button--compact">
          <input type="checkbox" name="ativo" value="1" checked>
          <span data-admin-t="state_active">Ativo</span>
        </label>
        <button type="submit" class="btn btn-dark btn-sm" data-admin-t="sizes_create">Criar</button>
      </div>
    </form>

    <div class="simple-list">
      <?php foreach ($sizes as $size): ?>
        <form method="post" class="admin-size-row">
          <?= csrf_input() ?>
          <input type="hidden" name="action" value="update_size">
          <input type="hidden" name="size_id" value="<?= (int)$size['idTamanho'] ?>">

          <div class="fg">
            <label class="flabel" data-admin-t="sizes_code">Codigo</label>
            <input type="text" name="codigo" class="finput" value="<?= h($size['codigo']) ?>" required maxlength="20">
          </div>

          <div class="fg">
            <label class="flabel" data-admin-t="sizes_label">Etiqueta</label>
            <input type="text" name="etiqueta" class="finput" value="<?= h($size['etiqueta']) ?>" required maxlength="30">
          </div>

          <div class="fg">
            <label class="flabel" data-admin-t="sizes_order">Ordem</label>
            <input type="number" name="ordem" class="finput" value="<?= (int)$size['ordem'] ?>">
          </div>

          <div class="admin-size-actions">
            <label class="admin-size-button admin-size-button--compact">
              <input type="checkbox" name="ativo" value="1" <?= (int)$size['ativo'] === 1 ? 'checked' : '' ?>>
              <span data-admin-t="state_active">Ativo</span>
            </label>
            <button type="submit" class="btn btn-ghost btn-sm" data-admin-t="categories_save">Guardar</button>
          </div>
        </form>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<div class="admin-search-row">
  <label class="sbar">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="search" data-admin-search="categories-search" placeholder="Pesquisar..." data-admin-tp="admin_search_placeholder">
  </label>
</div>

<div id="categories-search" data-admin-search-scope>
<section class="acard-box">
  <div class="acard-box-head">
    <h4 data-admin-t="categories_all">Categorias existentes</h4>
    <span class="badge badge-light"><?= count($categories) ?></span>
  </div>

  <div class="admin-card-list">
    <?php foreach ($categories as $category): ?>
      <article class="admin-review-card">
        <form method="post" class="stack-form">
          <?= csrf_input() ?>
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="category_id" value="<?= (int)$category['idCategoria'] ?>">

          <div class="admin-category-row">
            <div class="fg">
              <label class="flabel" data-admin-t="categories_name">Nome</label>
              <input type="text" name="nomeCategoria" class="finput" value="<?= h($category['nomeCategoria']) ?>" required maxlength="100">
            </div>

            <div class="fg">
              <label class="flabel" data-admin-t="categories_state">Estado</label>
              <select name="estado" class="finput">
                <option value="ativo" <?= $category['estado'] === 'ativo' ? 'selected' : '' ?> data-admin-t="state_active">Ativo</option>
                <option value="inativo" <?= $category['estado'] === 'inativo' ? 'selected' : '' ?> data-admin-t="state_inactive">Inativo</option>
              </select>
            </div>

            <div class="fg">
              <label class="flabel" data-admin-t="categories_sizing">Tamanhos</label>
              <label class="admin-size-button">
                <input type="checkbox" name="usa_tamanhos" value="1" <?= (int)$category['usa_tamanhos'] === 1 ? 'checked' : '' ?>>
                <span data-admin-t="categories_uses_sizes">Usa tamanhos</span>
              </label>
            </div>
          </div>

          <div class="fg">
            <label class="flabel" data-admin-t="categories_description">Descricao</label>
            <textarea name="descricaoCategoria" class="finput" rows="3"><?= h($category['descricaoCategoria'] ?? '') ?></textarea>
          </div>

          <div class="admin-category-meta">
            <span class="badge <?= h(state_badge_class($category['estado'])) ?>"><?= h(order_status_label($category['estado'])) ?></span>
            <span>
              <?= (int)$category['total_produtos'] ?>
              <span data-admin-t="categories_products_count">produtos</span>
            </span>
            <?php if ((int)$category['produtos_pendentes'] > 0): ?>
              <span>
                <?= (int)$category['produtos_pendentes'] ?>
                <span data-admin-t="categories_pending_count">pendentes</span>
              </span>
            <?php endif; ?>
          </div>

          <div class="admin-action-buttons">
            <button type="submit" class="btn btn-dark btn-sm" data-admin-t="categories_save">Guardar</button>
          </div>
        </form>
      </article>
    <?php endforeach; ?>
  </div>
</section>
</div>

<?php include 'admin_footer.php'; ?>
