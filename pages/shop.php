<?php
require_once '../includes/config.php';

$category = (int)($_GET['cat'] ?? 0);
$search = trim($_GET['q'] ?? '');
$searchSafe = db_escape($conn, $search);
$perPage = 20;
$pageNumber = max(1, (int)($_GET['page'] ?? 1));

$where = "WHERE p.estado = 'aprovado' AND p.ativo = 1";
if ($category > 0) {
    $where .= " AND p.idCategoria = {$category}";
}
if ($search !== '') {
    $where .= " AND (
        p.nomeProduto LIKE '%{$searchSafe}%'
        OR p.descricaoProduto LIKE '%{$searchSafe}%'
        OR c.nome LIKE '%{$searchSafe}%'
    )";
}

$totalProducts = (int)(db_one(
    $conn,
    "SELECT COUNT(*) AS total
     FROM produto p
     JOIN cliente c ON c.idCliente = p.idCliente
     {$where}"
)['total'] ?? 0);
$totalPages = max(1, (int)ceil($totalProducts / $perPage));
$pageNumber = min($pageNumber, $totalPages);
$offset = ($pageNumber - 1) * $perPage;

$products = db_all(
    $conn,
    "SELECT p.*, cat.nomeCategoria, c.nome AS artista_nome
     FROM produto p
     JOIN categoria cat ON cat.idCategoria = p.idCategoria
     JOIN cliente c ON c.idCliente = p.idCliente
     {$where}
     ORDER BY p.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);

$categories = db_all($conn, "SELECT * FROM categoria WHERE estado = 'ativo' ORDER BY nomeCategoria ASC");

$paginationQuery = [];
if ($search !== '') {
    $paginationQuery['q'] = $search;
}
if ($category > 0) {
    $paginationQuery['cat'] = $category;
}
$pageUrl = static function (int $targetPage) use ($paginationQuery): string {
    return 'shop.php?' . http_build_query($paginationQuery + ['page' => $targetPage]);
};

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
        <input type="text" name="q" value="<?= h($search) ?>" class="finput" data-tp="shop_search_placeholder" placeholder="Search product or artist">
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
              <div class="cover-ov"><button class="pbt" data-t="product_open">Abrir</button></div>
            </div>
            <div class="meta">
              <span class="badge badge-dark"><?= h($product['nomeCategoria']) ?></span>
              <h4><?= h($product['nomeProduto']) ?></h4>
              <div class="sub"><?= h($product['artista_nome']) ?></div>
              <div class="between mt4">
                <span class="price"><?= h(format_eur((float)$product['precoAtual'])) ?></span>
                <span class="sub" data-t="<?= (int)$product['stock_total'] > 0 ? 'product_in_stock' : 'product_sold_out' ?>">
                  <?= (int)$product['stock_total'] > 0
                    ? (current_lang() === 'en' ? 'In stock' : 'Em stock')
                    : (current_lang() === 'en' ? 'Sold out' : 'Esgotado') ?>
                </span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav class="pager" aria-label="Pagination">
          <?php if ($pageNumber > 1): ?>
            <a class="btn btn-ghost btn-sm" href="<?= h($pageUrl($pageNumber - 1)) ?>" data-t="pagination_previous">Anterior</a>
          <?php else: ?>
            <span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_previous">Anterior</span>
          <?php endif; ?>
          <span class="pager-status">
            <span data-t="pagination_page">Pagina</span> <?= $pageNumber ?>
            <span data-t="pagination_of">de</span> <?= $totalPages ?>
          </span>
          <?php if ($pageNumber < $totalPages): ?>
            <a class="btn btn-ghost btn-sm" href="<?= h($pageUrl($pageNumber + 1)) ?>" data-t="pagination_next">Seguinte</a>
          <?php else: ?>
            <span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_next">Seguinte</span>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
