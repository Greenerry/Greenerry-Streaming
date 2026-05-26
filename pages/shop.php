<?php
require_once '../includes/config.php';

// Shop filters come from the URL, for example: shop.php?q=hoodie&cat=2
$category = (int)($_GET['cat'] ?? 0);
$search = trim($_GET['q'] ?? '');
$perPage = 20;
$pageNumber = max(1, (int)($_GET['page'] ?? 1));

$whereParts = ["p.estado = 'aprovado'", 'p.ativo = 1', "c.estado = 'ativo'"];
$types = '';
$params = [];
if ($category > 0) {
    $whereParts[] = 'p.idCategoria = ?';
    $types .= 'i';
    $params[] = $category;
}
if ($search !== '') {
    // Search matches product name, description, or artist name.
    $searchLike = '%' . $search . '%';
    $whereParts[] = "(
        p.nomeProduto LIKE ?
        OR p.descricaoProduto LIKE ?
        OR c.nome LIKE ?
    )";
    $types .= 'sss';
    array_push($params, $searchLike, $searchLike, $searchLike);
}
$where = 'WHERE ' . implode(' AND ', $whereParts);

$totalProducts = (int)(db_one_prepared(
    $conn,
    "SELECT COUNT(*) AS total
     FROM produto p
     JOIN cliente c ON c.idCliente = p.idCliente
     {$where}",
    $types,
    $params
)['total'] ?? 0);
$totalPages = max(1, (int)ceil($totalProducts / $perPage));
$pageNumber = min($pageNumber, $totalPages);
$offset = ($pageNumber - 1) * $perPage;

// The page first counts all matching products, then fetches only the current page.
$products = db_all_prepared(
    $conn,
    "SELECT p.*, cat.nomeCategoria, c.nome AS artista_nome
     FROM produto p
     JOIN categoria cat ON cat.idCategoria = p.idCategoria
     JOIN cliente c ON c.idCliente = p.idCliente
     {$where}
     ORDER BY p.criado_em DESC
     LIMIT {$perPage} OFFSET {$offset}",
    $types,
    $params
);

$shopMediaCloud = [];
foreach ($products as $product) {
    $image = asset_url('img', product_main_image($conn, (int)$product['idProduto']));
    if ($image !== '') {
        $shopMediaCloud[$image] = [
            'src' => $image,
            'label' => (string)$product['nomeProduto'],
            'type' => 'store',
        ];
    }
}
$shopMediaCloud = array_values(array_slice($shopMediaCloud, 0, 12));

$categories = db_all($conn, "SELECT * FROM categoria WHERE estado = 'ativo' ORDER BY nomeCategoria ASC");

$paginationQuery = [];
if ($search !== '') {
    $paginationQuery['q'] = $search;
}
if ($category > 0) {
    $paginationQuery['cat'] = $category;
}
$pageUrl = static function (int $targetPage) use ($paginationQuery): string {
    // Pagination links preserve the current search and category.
    return 'shop.php?' . http_build_query($paginationQuery + ['page' => $targetPage]);
};

include '../includes/header.php';
?>

<section class="content-shell content-shell--cloud content-shell--catalog-cloud">
  <div class="section-media-cloud section-media-cloud--catalog" data-media-cloud='<?= h(json_encode($shopMediaCloud, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>' aria-hidden="true"></div>
  <div class="wrap">
    <div class="catalog-hero catalog-hero--shop">
      <div>
        <span class="slabel" data-t="shop_label">Merch store</span>
        <h1 data-t="shop_title">Shop artist merch</h1>
      </div>

      <form method="get" class="catalog-filter" data-instant-filter>
        <input type="text" name="q" value="<?= h($search) ?>" class="finput" data-tp="shop_search_placeholder" placeholder="Search product or artist" autocomplete="off">
        <select name="cat" class="finput">
          <option value="0" data-t="shop_all_categories">All categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['idCategoria'] ?>" data-product-category="<?= h($cat['nomeCategoria']) ?>" <?= $category === (int)$cat['idCategoria'] ? 'selected' : '' ?>><?= h(category_label($cat['nomeCategoria'])) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <div data-catalog-results>
    <?php if (!$products): ?>
      <div class="card surface-card catalog-empty-state">
        <div class="card-body text-center">
          <p data-t="shop_empty">No products matched your search.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="grid stg shop-catalog-grid">
        <?php foreach ($products as $product): ?>
          <?php $mainImage = product_main_image($conn, (int)$product['idProduto']); ?>
          <a href="produto.php?id=<?= (int)$product['idProduto'] ?>" class="mcard">
            <div class="cover">
              <?php if ($mainImage !== ''): ?>
                <img src="<?= h(asset_url('img', $mainImage)) ?>" alt="<?= h($product['nomeProduto']) ?>">
              <?php endif; ?>
              <div class="cover-ov"><button class="pbt" data-t="product_open">Abrir</button></div>
            </div>
            <div class="meta">
              <span class="badge badge-dark" data-product-category="<?= h($product['nomeCategoria']) ?>"><?= h(category_label($product['nomeCategoria'])) ?></span>
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
  </div>
</section>

<?php include '../includes/footer.php'; ?>
