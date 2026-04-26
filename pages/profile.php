<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$ok = '';
$err = '';
$user = db_one($conn, "SELECT * FROM cliente WHERE idCliente = {$uid} LIMIT 1");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $foto = $user['foto'] ?? '';
    $banner = $user['banner'] ?? '';

    $err = validate_nome($nome);

    $imgDir = __DIR__ . '/../assets/img/';
    if (!is_dir($imgDir)) {
        mkdir($imgDir, 0775, true);
    }

    if (!$err && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $err = 'A foto tem de estar em JPG, PNG ou WEBP.';
        } else {
            $foto = 'avatar_' . $uid . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $imgDir . $foto);
        }
    }

    if (!$err && isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $err = 'O banner tem de estar em JPG, PNG ou WEBP.';
        } else {
            $banner = 'banner_' . $uid . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['banner']['tmp_name'], $imgDir . $banner);
        }
    }

    if (!$err) {
        $nomeSafe = db_escape($conn, $nome);
        $telefoneSafe = db_escape($conn, $telefone);
        $bioSafe = db_escape($conn, $bio);
        $fotoSafe = db_escape($conn, $foto);
        $bannerSafe = db_escape($conn, $banner);
        $slugSafe = db_escape($conn, ensure_unique_cliente_slug($conn, $nome, $uid));

        mysqli_query(
            $conn,
            "UPDATE cliente
             SET nome = '{$nomeSafe}',
                 telefone = " . ($telefone !== '' ? "'{$telefoneSafe}'" : "NULL") . ",
                 bio = " . ($bio !== '' ? "'{$bioSafe}'" : "NULL") . ",
                 foto = " . ($foto !== '' ? "'{$fotoSafe}'" : "NULL") . ",
                 banner = " . ($banner !== '' ? "'{$bannerSafe}'" : "NULL") . ",
                 slug = '{$slugSafe}'
             WHERE idCliente = {$uid}"
        );

        $_SESSION['user_name'] = $nome;
        $user = db_one($conn, "SELECT * FROM cliente WHERE idCliente = {$uid} LIMIT 1");
        $ok = 'Perfil atualizado com sucesso.';
    }
}

$orders = db_all(
    $conn,
    "SELECT e.*, COUNT(ei.idEncomendaItem) AS total_itens
     FROM encomenda e
     LEFT JOIN encomenda_item ei ON ei.idEncomenda = e.idEncomenda
     WHERE e.idCliente = {$uid}
     GROUP BY e.idEncomenda
     ORDER BY e.created_at DESC"
);

$releases = db_all(
    $conn,
    "SELECT r.*, COUNT(f.idFaixa) AS total_faixas
     FROM release_musical r
     LEFT JOIN faixa f ON f.idRelease = r.idRelease
     WHERE r.idCliente = {$uid}
     GROUP BY r.idRelease
     ORDER BY r.created_at DESC"
);

$products = db_all(
    $conn,
    "SELECT p.*, cat.nomeCategoria
     FROM produto p
     LEFT JOIN categoria cat ON cat.idCategoria = p.idCategoria
     WHERE p.idCliente = {$uid}
     ORDER BY p.created_at DESC"
);

include '../includes/header.php';
?>

<section class="artist-hero">
  <div class="artist-hero-backdrop">
    <?php if (!empty($user['banner'])): ?>
      <img src="<?= h(asset_url('img', $user['banner'])) ?>" alt="<?= h($user['nome']) ?>">
    <?php endif; ?>
  </div>
  <div class="artist-hero-overlay"></div>
  <div class="artist-hero-content wrap">
    <div class="artist-hero-avatar avatar">
      <?php if (!empty($user['foto'])): ?>
        <img src="<?= h(asset_url('img', $user['foto'])) ?>" alt="<?= h($user['nome']) ?>">
      <?php endif; ?>
    </div>
    <div>
      <span class="badge">A minha conta</span>
      <h1><?= h($user['nome']) ?></h1>
      <p><?= h($user['bio'] ?: 'Completa o perfil para mostrares melhor a tua identidade artistica.') ?></p>
    </div>
  </div>
</section>

<section class="content-shell">
  <div class="wrap">
    <div class="tabs">
      <button class="tab on" data-tab="edit">Perfil</button>
      <button class="tab" data-tab="orders">Compras</button>
      <button class="tab" data-tab="music">Lancamentos</button>
      <button class="tab" data-tab="merch">Merch</button>
    </div>

    <div id="tab-edit">
      <?php if ($err): ?>
        <div class="alert alert-err"><?= h($err) ?></div>
      <?php endif; ?>
      <?php if ($ok): ?>
        <div class="alert alert-ok"><?= h($ok) ?></div>
      <?php endif; ?>

      <div class="two-column-layout">
        <div class="card surface-card">
          <div class="card-body">
            <h3 class="section-card-title">Editar perfil</h3>
            <form method="post" enctype="multipart/form-data" class="stack-form">
              <div class="fg">
                <label class="flabel" for="nome">Nome</label>
                <input id="nome" type="text" name="nome" class="finput" required value="<?= h($user['nome']) ?>">
              </div>

              <div class="fg">
                <label class="flabel" for="telefone">Telefone</label>
                <input id="telefone" type="text" name="telefone" class="finput" value="<?= h($user['telefone'] ?? '') ?>">
              </div>

              <div class="fg">
                <label class="flabel" for="bio">Bio</label>
                <textarea id="bio" name="bio" class="finput"><?= h($user['bio'] ?? '') ?></textarea>
              </div>

              <div class="fg">
                <label class="flabel" for="foto">Foto de perfil</label>
                <input id="foto" type="file" name="foto" class="finput" accept=".jpg,.jpeg,.png,.webp">
              </div>

              <div class="fg">
                <label class="flabel" for="banner">Banner</label>
                <input id="banner" type="file" name="banner" class="finput" accept=".jpg,.jpeg,.png,.webp">
              </div>

              <button type="submit" name="update_profile" class="btn btn-dark">Guardar alteracoes</button>
            </form>
          </div>
        </div>

        <div class="card surface-card">
          <div class="card-body">
            <h3 class="section-card-title">Resumo</h3>
            <div class="simple-list">
              <div class="simple-list-item">
                <div>
                  <strong>Email</strong>
                  <p><?= h($user['email']) ?></p>
                </div>
              </div>
              <div class="simple-list-item">
                <div>
                  <strong>Estado</strong>
                  <p><?= h(ucfirst($user['estado'])) ?></p>
                </div>
              </div>
              <div class="simple-list-item">
                <div>
                  <strong>Lancamentos</strong>
                  <p><?= count($releases) ?> registo(s)</p>
                </div>
              </div>
              <div class="simple-list-item">
                <div>
                  <strong>Produtos</strong>
                  <p><?= count($products) ?> registo(s)</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div id="tab-orders" class="is-hidden">
      <div class="card surface-card">
        <div class="card-body">
          <h3 class="section-card-title">As minhas compras</h3>
          <?php if (!$orders): ?>
            <p>Ainda nao fizeste compras.</p>
          <?php else: ?>
            <div class="tbl-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Encomenda</th>
                    <th>Data</th>
                    <th>Estado</th>
                    <th>Pagamento</th>
                    <th>Total</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($orders as $order): ?>
                    <tr>
                      <td><strong>#<?= (int)$order['idEncomenda'] ?></strong></td>
                      <td><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
                      <td><span class="badge <?= h(state_badge_class($order['estado_encomenda'])) ?>"><?= h(order_status_label($order['estado_encomenda'])) ?></span></td>
                      <td><span class="badge <?= h(state_badge_class($order['estado_pagamento'])) ?>"><?= h(payment_status_label($order['estado_pagamento'])) ?></span></td>
                      <td><?= h(format_eur((float)$order['total_final'])) ?></td>
                      <td><a href="receipt.php?id=<?= (int)$order['idEncomenda'] ?>" class="btn btn-ghost btn-sm" target="_blank">Recibo</a></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div id="tab-music" class="is-hidden">
      <div class="card surface-card">
        <div class="card-body">
          <div class="between mb6">
            <h3 class="section-card-title">Os meus lancamentos</h3>
            <a href="upload_music.php" class="btn btn-dark btn-sm">Novo lancamento</a>
          </div>

          <?php if (!$releases): ?>
            <p>Ainda nao submeteste lancamentos.</p>
          <?php else: ?>
            <div class="tbl-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Titulo</th>
                    <th>Tipo</th>
                    <th>Faixas</th>
                    <th>Estado</th>
                    <th>Acao</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($releases as $release): ?>
                    <tr>
                      <td>
                        <strong><?= h($release['titulo']) ?></strong>
                        <?php if (!empty($release['motivo_rejeicao'])): ?>
                          <br><span class="color-text3"><?= h($release['motivo_rejeicao']) ?></span>
                        <?php endif; ?>
                      </td>
                      <td><?= h($release['tipo']) ?></td>
                      <td><?= (int)$release['total_faixas'] ?></td>
                      <td><span class="badge <?= h(state_badge_class($release['estado'])) ?>"><?= h(ucfirst($release['estado'])) ?></span></td>
                      <td>
                        <?php if (in_array($release['estado'], ['aprovado', 'inativo'], true)): ?>
                          <button type="button" class="btn btn-ghost btn-sm js-toggle-item" data-type="music" data-id="<?= (int)$release['idRelease'] ?>">
                            <?= (int)$release['ativo'] === 1 ? 'Inativar' : 'Ativar' ?>
                          </button>
                        <?php else: ?>
                          <span class="color-text3">Sem acao</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div id="tab-merch" class="is-hidden">
      <div class="card surface-card">
        <div class="card-body">
          <div class="between mb6">
            <h3 class="section-card-title">Os meus produtos</h3>
            <a href="upload_merch.php" class="btn btn-dark btn-sm">Novo produto</a>
          </div>

          <?php if (!$products): ?>
            <p>Ainda nao submeteste merch.</p>
          <?php else: ?>
            <div class="tbl-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Preco</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Acao</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($products as $product): ?>
                    <tr>
                      <td>
                        <strong><?= h($product['nomeProduto']) ?></strong>
                        <?php if (!empty($product['motivo_rejeicao'])): ?>
                          <br><span class="color-text3"><?= h($product['motivo_rejeicao']) ?></span>
                        <?php endif; ?>
                      </td>
                      <td><?= h($product['nomeCategoria'] ?? 'Sem categoria') ?></td>
                      <td><?= h(format_eur((float)$product['precoAtual'])) ?></td>
                      <td><?= (int)$product['stock_total'] ?></td>
                      <td><span class="badge <?= h(state_badge_class($product['estado'])) ?>"><?= h(ucfirst($product['estado'])) ?></span></td>
                      <td>
                        <?php if (in_array($product['estado'], ['aprovado', 'inativo'], true)): ?>
                          <button type="button" class="btn btn-ghost btn-sm js-toggle-item" data-type="merch" data-id="<?= (int)$product['idProduto'] ?>">
                            <?= (int)$product['ativo'] === 1 ? 'Inativar' : 'Ativar' ?>
                          </button>
                        <?php else: ?>
                          <span class="color-text3">Sem acao</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
document.querySelectorAll('.tab[data-tab]').forEach((button) => {
  button.addEventListener('click', () => {
    document.querySelectorAll('.tab[data-tab]').forEach((tab) => tab.classList.remove('on'));
    button.classList.add('on');
    ['edit', 'orders', 'music', 'merch'].forEach((name) => {
      const section = document.getElementById('tab-' + name);
      if (section) {
        section.style.display = button.dataset.tab === name ? 'block' : 'none';
      }
    });
  });
});

document.querySelectorAll('.js-toggle-item').forEach((button) => {
  button.addEventListener('click', async () => {
    const body = new URLSearchParams();
    body.set('type', button.dataset.type);
    body.set('id', button.dataset.id);

    const response = await fetch('../api/toggle_item.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString()
    });

    const result = await response.json();
    if (result.success) {
      window.location.reload();
      return;
    }

    toast(result.error || 'Nao foi possivel atualizar o item.');
  });
});
</script>

<?php include '../includes/footer.php'; ?>
