<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$err = '';
$ok = '';
$categories = db_all($conn, "SELECT * FROM categoria WHERE estado = 'ativo' ORDER BY nomeCategoria ASC");
$sizes = db_all($conn, "SELECT * FROM tamanho WHERE ativo = 1 ORDER BY ordem ASC");

function category_supports_sizes(string $categoryName): bool
{
    return in_array($categoryName, ['T-Shirt', 'Hoodie', 'Poster'], true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['nomeProduto'] ?? '');
    $brand = trim($_POST['marca'] ?? '');
    $description = trim($_POST['descricao'] ?? '');
    $price = (float)($_POST['precoAtual'] ?? 0);
    $categoryId = (int)($_POST['idCategoria'] ?? 0);
    $usesSizes = !empty($_POST['usa_tamanhos']) ? 1 : 0;
    $stockTotal = (int)($_POST['stock_total'] ?? 0);
    $selectedSizes = array_map('intval', $_POST['selected_sizes'] ?? []);
    $stockBySize = $_POST['stock_tamanho'] ?? [];
    $categoryRow = null;
    foreach ($categories as $category) {
        if ((int)$category['idCategoria'] === $categoryId) {
            $categoryRow = $category;
            break;
        }
    }
    $categorySupportsSizes = $categoryRow ? category_supports_sizes((string)$categoryRow['nomeCategoria']) : false;
    if (!$categorySupportsSizes) {
        $usesSizes = 0;
    }

    if ($name === '') {
        $err = 'O nome do produto e obrigatorio.';
    } elseif ($price <= 0) {
        $err = 'O preco tem de ser superior a zero.';
    } elseif ($categoryId <= 0) {
        $err = 'Seleciona uma categoria.';
    } elseif (!$usesSizes && $stockTotal < 0) {
        $err = 'O stock nao pode ser negativo.';
    } elseif ($usesSizes && !$categorySupportsSizes) {
        $err = 'Esta categoria nao usa tamanhos.';
    }

    if (!$err && $usesSizes) {
        if (!$selectedSizes) {
            foreach ($sizes as $size) {
                $sizeId = (int)$size['idTamanho'];
                if (max(0, (int)($stockBySize[$sizeId] ?? 0)) > 0) {
                    $selectedSizes[] = $sizeId;
                }
            }
        }
        if (!$selectedSizes) {
            $err = 'Escolhe pelo menos um tamanho ou indica stock num tamanho.';
        }
    }

    if (!$err && $usesSizes) {
        $selectedStockTotal = 0;
        foreach ($selectedSizes as $selectedSizeId) {
            $selectedStockTotal += max(0, (int)($stockBySize[$selectedSizeId] ?? 0));
        }
        if ($selectedStockTotal <= 0) {
            $err = 'Indica stock para pelo menos um dos tamanhos escolhidos.';
        }
    }

    $image = '';
    if (!$err && isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $err = 'A imagem tem de estar em JPG, PNG ou WEBP.';
        } else {
            $image = 'product_' . $uid . '_' . time() . '.' . $ext;
            $imgDir = __DIR__ . '/../assets/img/';
            if (!is_dir($imgDir)) {
                mkdir($imgDir, 0775, true);
            }
            move_uploaded_file($_FILES['imagem']['tmp_name'], $imgDir . $image);
        }
    }

    if (!$err) {
        mysqli_begin_transaction($conn);
        try {
            $nameSafe = db_escape($conn, $name);
            $brandSafe = db_escape($conn, $brand);
            $descriptionSafe = db_escape($conn, $description);
            $imageSafe = db_escape($conn, $image);

            mysqli_query(
                $conn,
                "INSERT INTO produto (idCliente, idCategoria, nomeProduto, descricaoProduto, marca, precoAtual, stock_total, usa_tamanhos, imagem, estado, ativo)
                 VALUES ({$uid}, {$categoryId}, '{$nameSafe}', '{$descriptionSafe}', '{$brandSafe}', {$price}, {$stockTotal}, {$usesSizes}, '{$imageSafe}', 'pendente', 1)"
            );

            $productId = (int)mysqli_insert_id($conn);
            if ($usesSizes) {
                $stockBySize = $_POST['stock_tamanho'] ?? [];
                $sum = 0;
                foreach ($sizes as $size) {
                    $sizeId = (int)$size['idTamanho'];
                    if (!in_array($sizeId, $selectedSizes, true)) {
                        continue;
                    }
                    $sizeStock = max(0, (int)($stockBySize[$sizeId] ?? 0));
                    $sum += $sizeStock;
                    mysqli_query(
                        $conn,
                        "INSERT INTO produto_tamanho_stock (idProduto, idTamanho, stock, ativo)
                         VALUES ({$productId}, {$sizeId}, {$sizeStock}, 1)"
                    );
                }
                mysqli_query($conn, "UPDATE produto SET stock_total = {$sum} WHERE idProduto = {$productId}");
            }

            mysqli_commit($conn);
            $ok = 'Produto enviado para aprovacao do admin.';
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $err = 'Nao foi possivel registar o produto.';
        }
    }
}

$myProducts = db_all(
    $conn,
    "SELECT p.*, cat.nomeCategoria
     FROM produto p
     JOIN categoria cat ON cat.idCategoria = p.idCategoria
     WHERE p.idCliente = {$uid}
     ORDER BY p.created_at DESC"
);

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <div class="submission-hero submission-hero--merch hero-card--single">
      <div class="submission-hero-copy">
        <span class="slabel">Merch</span>
        <h2>Novo produto.</h2>
      </div>
    </div>

    <?php if ($err): ?><div class="alert alert-err"><?= h($err) ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="alert alert-ok"><?= h($ok) ?></div><?php endif; ?>

    <div class="two-column-layout">
      <div class="card surface-card surface-card--soft">
        <div class="card-body">
          <form method="post" enctype="multipart/form-data" class="stack-form">
            <div class="fg">
              <label class="flabel" for="nomeProduto">Nome do produto</label>
              <input id="nomeProduto" type="text" name="nomeProduto" class="finput" required maxlength="150">
            </div>

            <div class="frow">
              <div class="fg">
                <label class="flabel" for="marca">Marca</label>
                <input id="marca" type="text" name="marca" class="finput" maxlength="100">
              </div>
              <div class="fg">
                <label class="flabel" for="idCategoria">Categoria</label>
                <select id="idCategoria" name="idCategoria" class="finput" required>
                  <option value="">Seleciona</option>
                  <?php foreach ($categories as $category): ?>
                    <option
                      value="<?= (int)$category['idCategoria'] ?>"
                      data-supports-sizes="<?= category_supports_sizes((string)$category['nomeCategoria']) ? '1' : '0' ?>"
                    ><?= h($category['nomeCategoria']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="frow">
              <div class="fg">
                <label class="flabel" for="precoAtual">Preco</label>
                <input id="precoAtual" type="number" name="precoAtual" class="finput" step="0.01" min="0.01" required>
              </div>
              <div class="fg" id="stock-total-field">
                <label class="flabel" for="stock_total">Stock total</label>
                <input id="stock_total" type="number" name="stock_total" class="finput" min="0" value="0">
              </div>
            </div>

            <div class="fg">
              <label class="flabel" for="descricao">Descricao</label>
              <textarea id="descricao" name="descricao" class="finput" maxlength="2000"></textarea>
            </div>

            <div class="fg">
              <label class="flabel" for="imagem">Imagem</label>
              <div class="upload-zone upload-zone--compact">
                <input id="imagem" type="file" name="imagem" class="finput" accept=".jpg,.jpeg,.png,.webp">
                <p>Carrega a imagem principal do produto.</p>
              </div>
            </div>

            <label class="segmented-option segmented-option--block merch-size-toggle" id="size-toggle-row">
              <input type="checkbox" id="usa_tamanhos" name="usa_tamanhos" value="1">
              <span>Usar tamanhos para este produto</span>
            </label>

            <div id="size-stock-box" class="stack-form is-hidden">
              <?php foreach ($sizes as $size): ?>
                <div class="frow merch-size-row">
                  <label class="segmented-option segmented-option--block merch-size-check">
                    <input type="checkbox" name="selected_sizes[]" value="<?= (int)$size['idTamanho'] ?>" class="size-choice">
                    <span><?= h($size['etiqueta']) ?></span>
                  </label>
                  <div class="fg merch-size-stock">
                    <label class="flabel">Stock <?= h($size['etiqueta']) ?></label>
                    <input
                      type="number"
                      name="stock_tamanho[<?= (int)$size['idTamanho'] ?>]"
                      class="finput size-stock-input"
                      min="0"
                      value="0"
                      data-size-id="<?= (int)$size['idTamanho'] ?>"
                      disabled
                    >
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn-dark">Enviar produto</button>
          </form>
        </div>
      </div>

      <div class="card surface-card surface-card--soft">
        <div class="card-body">
          <h3 class="section-card-title">Os meus pedidos</h3>
          <?php if (!$myProducts): ?>
            <p>Ainda nao submeteste nenhum produto.</p>
          <?php else: ?>
            <div class="message-thread-list">
              <?php foreach ($myProducts as $product): ?>
                <article class="message-thread-item">
                  <div class="message-thread-head">
                    <strong><?= h($product['nomeProduto']) ?></strong>
                    <span class="badge <?= h(state_badge_class($product['estado'])) ?>"><?= h(order_status_label($product['estado'])) ?></span>
                  </div>
                  <p class="message-thread-meta"><?= h($product['nomeCategoria']) ?> - <?= number_format((float)$product['precoAtual'], 2, ',', '.') ?> EUR</p>
                  <?php if (!empty($product['motivo_rejeicao'])): ?>
                    <div class="message-reply-box">
                      <span class="slabel">Motivo de rejeicao</span>
                      <p><?= h($product['motivo_rejeicao']) ?></p>
                    </div>
                  <?php endif; ?>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
const sizeToggle = document.getElementById('usa_tamanhos');
const sizeStockBox = document.getElementById('size-stock-box');
const totalStock = document.getElementById('stock_total');
const stockTotalField = document.getElementById('stock-total-field');
const categorySelect = document.getElementById('idCategoria');
const sizeToggleRow = document.getElementById('size-toggle-row');
const sizeChoices = Array.from(document.querySelectorAll('.size-choice'));
const sizeStockInputs = Array.from(document.querySelectorAll('.size-stock-input'));
const merchForm = document.querySelector('form.stack-form');

function categoryAllowsSizes() {
  const option = categorySelect?.options?.[categorySelect.selectedIndex];
  return option?.dataset?.supportsSizes === '1';
}

function syncIndividualSizeInputs() {
  sizeChoices.forEach((choice) => {
    const sizeId = choice.value;
    const stockInput = document.querySelector(`.size-stock-input[data-size-id="${sizeId}"]`);
    if (!stockInput) {
      return;
    }
    const enabled = !choice.disabled;
    stockInput.disabled = !enabled;
    if (!enabled) {
      choice.checked = false;
      stockInput.value = '0';
    }
  });
}

function syncSizeFields() {
  const allowed = categoryAllowsSizes();
  sizeToggleRow.style.display = allowed ? 'block' : 'none';
  stockTotalField.style.display = allowed ? 'none' : 'block';

  if (allowed) {
    sizeToggle.checked = true;
  } else {
    sizeToggle.checked = false;
  }

  sizeToggle.disabled = !allowed;
  const on = allowed;
  sizeStockBox.style.display = on ? 'grid' : 'none';
  totalStock.disabled = on;
  sizeChoices.forEach((choice) => {
    choice.disabled = !on;
    if (!on) {
      choice.checked = false;
    }
  });
  syncIndividualSizeInputs();
}

sizeToggle?.addEventListener('change', syncSizeFields);
categorySelect?.addEventListener('change', syncSizeFields);
sizeChoices.forEach((choice) => {
  choice.addEventListener('change', syncIndividualSizeInputs);
});
sizeStockInputs.forEach((input) => {
  input.addEventListener('input', () => {
    const sizeId = input.dataset.sizeId;
    const choice = document.querySelector(`.size-choice[value="${sizeId}"]`);
    if (!choice) {
      return;
    }
    choice.checked = Number(input.value || 0) > 0;
  });
});

merchForm?.addEventListener('submit', (event) => {
  if (!categoryAllowsSizes()) {
    return;
  }

  const chosen = sizeChoices.filter((choice) => choice.checked);
  const totalStock = sizeStockInputs.reduce((sum, input) => sum + Math.max(0, Number(input.value || 0)), 0);

  if (!chosen.length && totalStock <= 0) {
    event.preventDefault();
    toast((localStorage.getItem('g_lang') || 'pt') === 'en'
      ? 'Choose at least one size or enter stock in a size.'
      : 'Escolhe pelo menos um tamanho ou indica stock num tamanho.');
    return;
  }

  if (totalStock <= 0) {
    event.preventDefault();
    toast((localStorage.getItem('g_lang') || 'pt') === 'en'
      ? 'Enter stock for at least one selected size.'
      : 'Indica stock para pelo menos um dos tamanhos escolhidos.');
  }
});

syncSizeFields();
</script>

<?php include '../includes/footer.php'; ?>
