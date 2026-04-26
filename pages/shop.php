<?php
require_once '../includes/config.php';

$category = (int)($_GET['cat'] ?? 0);
$search = trim($_GET['q'] ?? '');
$searchSafe = db_escape($conn, $search);

$where = "WHERE p.estado = 'aprovado' AND p.ativo = 1";
if ($category > 0) {
    $where .= " AND p.idCategoria = {$category}";
}
if ($search !== '') {
    $where .= " AND (p.nomeProduto LIKE '%{$searchSafe}%' OR p.descricaoProduto LIKE '%{$searchSafe}%')";
}

$products = db_all(
    $conn,
    "SELECT p.*, cat.nomeCategoria, c.nome AS artista_nome
     FROM produto p
     JOIN categoria cat ON cat.idCategoria = p.idCategoria
     JOIN cliente c ON c.idCliente = p.idCliente
     {$where}
     ORDER BY p.created_at DESC"
);

$categories = db_all($conn, "SELECT * FROM categoria WHERE estado = 'ativo' ORDER BY nomeCategoria ASC");

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <div class="catalog-hero">
      <div>
        <span class="slabel" data-t="shop_label">Merch store</span>
        <h1 data-t="shop_title">Shop artist merch</h1>
      </div>

      <form method="get" class="catalog-filter">
        <input type="text" name="q" value="<?= h($search) ?>" class="finput" data-tp="shop_search_placeholder" placeholder="Search product">
        <select name="cat" class="finput">
          <option value="0" data-t="shop_all_categories">All categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['idCategoria'] ?>" <?= $category === (int)$cat['idCategoria'] ? 'selected' : '' ?>><?= h($cat['nomeCategoria']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-dark" data-t="shop_filter">Filter</button>
      </form>
    </div>

    <?php if (!$products): ?>
      <div class="card surface-card">
        <div class="card-body text-center">
          <p data-t="shop_empty">No products matched your search.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="grid stg">
        <?php foreach ($products as $product): ?>
          <a href="produto.php?id=<?= (int)$product['idProduto'] ?>" class="mcard">
            <div class="cover">
              <?php if (!empty($product['imagem'])): ?>
                <img src="<?= h(asset_url('img', $product['imagem'])) ?>" alt="<?= h($product['nomeProduto']) ?>">
              <?php endif; ?>
              <div class="cover-ov"><button class="pbt">Open</button></div>
            </div>
            <div class="meta">
              <span class="badge badge-dark"><?= h($product['nomeCategoria']) ?></span>
              <h4><?= h($product['nomeProduto']) ?></h4>
              <div class="sub"><?= h($product['artista_nome']) ?></div>
              <div class="between mt4">
                <span class="price"><?= h(format_eur((float)$product['precoAtual'])) ?></span>
                <span class="sub"><?= (int)$product['stock_total'] > 0 ? 'In stock' : 'Sold out' ?></span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
