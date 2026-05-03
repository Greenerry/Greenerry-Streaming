<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$err = '';
$ok = '';
$categories = db_all($conn, "SELECT * FROM categoria WHERE estado = 'ativo' ORDER BY nomeCategoria ASC");
$sizes = db_all($conn, "SELECT * FROM tamanho WHERE ativo = 1 ORDER BY ordem ASC");
$editId = (int)($_GET['edit'] ?? $_POST['product_id'] ?? 0);
$editProduct = null;
$editStocks = [];

if ($editId > 0) {
    $editProduct = db_one($conn, "SELECT * FROM produto WHERE idProduto = {$editId} AND idCliente = {$uid} LIMIT 1");
    if (!$editProduct || (int)($editProduct['bloqueado_admin'] ?? 0) === 1) {
        header('Location: upload_merch.php');
        exit;
    }

    foreach (db_all($conn, "SELECT idTamanho, stock FROM produto_tamanho_stock WHERE idProduto = {$editId}") as $stockRow) {
        $editStocks[(int)$stockRow['idTamanho']] = (int)$stockRow['stock'];
    }
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
    $categorySupportsSizes = $categoryRow && (int)($categoryRow['usa_tamanhos'] ?? 0) === 1;
    if (!$categorySupportsSizes) {
        $usesSizes = 0;
    }

    $err = verify_csrf_request() ?? '';

    if (!$err && $name === '') {
        $err = tr('error.product_name_required');
    } elseif (!$err && $price <= 0) {
        $err = tr('error.price_positive');
    } elseif (!$err && $categoryId <= 0) {
        $err = tr('error.category_required');
    } elseif (!$err && !$usesSizes && $stockTotal < 0) {
        $err = tr('error.stock_negative');
    } elseif (!$err && $usesSizes && !$categorySupportsSizes) {
        $err = tr('error.category_no_sizes');
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
            $err = tr('error.size_or_stock_required');
        }
    }

    if (!$err && $usesSizes) {
        $selectedStockTotal = 0;
        foreach ($selectedSizes as $selectedSizeId) {
            $selectedStockTotal += max(0, (int)($stockBySize[$selectedSizeId] ?? 0));
        }
        if ($selectedStockTotal <= 0) {
            $err = tr('error.size_stock_required');
        }
    }

    $image = (string)($editProduct['imagem'] ?? '');
    if (!$err && isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $err = validate_uploaded_image($_FILES['imagem']);
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        if (!$err) {
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

            if ($editProduct) {
                mysqli_query(
                    $conn,
                    "UPDATE produto
                     SET idCategoria = {$categoryId},
                         nomeProduto = '{$nameSafe}',
                         descricaoProduto = '{$descriptionSafe}',
                         marca = '{$brandSafe}',
                         precoAtual = {$price},
                         stock_total = {$stockTotal},
                         usa_tamanhos = {$usesSizes},
                         imagem = '{$imageSafe}',
                         estado = 'pendente',
                         ativo = 1,
                         motivo_rejeicao = NULL,
                         idAdminAprovacao = NULL,
                         aprovado_em = NULL
                     WHERE idProduto = {$editId} AND idCliente = {$uid}"
                );
                $productId = $editId;
                mysqli_query($conn, "DELETE FROM produto_tamanho_stock WHERE idProduto = {$productId}");
            } else {
                mysqli_query(
                    $conn,
                    "INSERT INTO produto (idCliente, idCategoria, nomeProduto, descricaoProduto, marca, precoAtual, stock_total, usa_tamanhos, imagem, estado, ativo)
                     VALUES ({$uid}, {$categoryId}, '{$nameSafe}', '{$descriptionSafe}', '{$brandSafe}', {$price}, {$stockTotal}, {$usesSizes}, '{$imageSafe}', 'pendente', 1)"
                );
                $productId = (int)mysqli_insert_id($conn);
            }

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
            $ok = $editProduct
                ? (current_lang() === 'en' ? 'Product updated and sent for review.' : 'Produto atualizado e enviado para revisao.')
                : tr('success.product_sent');
            if ($editProduct) {
                $editProduct = db_one($conn, "SELECT * FROM produto WHERE idProduto = {$editId} AND idCliente = {$uid} LIMIT 1");
                $editStocks = [];
                foreach (db_all($conn, "SELECT idTamanho, stock FROM produto_tamanho_stock WHERE idProduto = {$editId}") as $stockRow) {
                    $editStocks[(int)$stockRow['idTamanho']] = (int)$stockRow['stock'];
                }
            }
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $err = tr('error.product_save');
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
        <span class="slabel" data-t="upload_merch_label">Merch</span>
        <h2><?= $editProduct ? (current_lang() === 'en' ? 'Edit product.' : 'Editar produto.') : '<span data-t="upload_merch_title">Novo produto.</span>' ?></h2>
      </div>
    </div>

    <?php if ($err): ?><div class="alert alert-err"><?= h($err) ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="alert alert-ok"><?= h($ok) ?></div><?php endif; ?>

    <div class="two-column-layout">
      <div class="card surface-card surface-card--soft">
        <div class="card-body">
          <form method="post" enctype="multipart/form-data" class="stack-form">
            <?= csrf_input() ?>
            <?php if ($editProduct): ?>
              <input type="hidden" name="product_id" value="<?= (int)$editProduct['idProduto'] ?>">
            <?php endif; ?>
            <div class="fg">
              <label class="flabel" for="nomeProduto" data-t="upload_merch_name">Nome do produto</label>
              <input id="nomeProduto" type="text" name="nomeProduto" class="finput" required maxlength="150" value="<?= h($editProduct['nomeProduto'] ?? '') ?>">
            </div>

            <div class="frow">
              <div class="fg">
                <label class="flabel" for="marca" data-t="upload_merch_brand">Marca</label>
                <input id="marca" type="text" name="marca" class="finput" maxlength="100" value="<?= h($editProduct['marca'] ?? '') ?>">
              </div>
              <div class="fg">
                <label class="flabel" for="idCategoria" data-t="upload_merch_category">Categoria</label>
                <select id="idCategoria" name="idCategoria" class="finput" required>
                  <option value="" data-t="upload_select">Seleciona</option>
                  <?php foreach ($categories as $category): ?>
                    <option
                      value="<?= (int)$category['idCategoria'] ?>"
                      data-supports-sizes="<?= (int)($category['usa_tamanhos'] ?? 0) === 1 ? '1' : '0' ?>"
                      <?= $editProduct && (int)$editProduct['idCategoria'] === (int)$category['idCategoria'] ? 'selected' : '' ?>
                    ><?= h($category['nomeCategoria']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="frow">
              <div class="fg">
                <label class="flabel" for="precoAtual" data-t="upload_merch_price">Preco</label>
                <input id="precoAtual" type="number" name="precoAtual" class="finput" step="0.01" min="0.01" required value="<?= h((string)($editProduct['precoAtual'] ?? '')) ?>">
              </div>
              <div class="fg" id="stock-total-field">
                <label class="flabel" for="stock_total" data-t="upload_merch_stock_total">Stock total</label>
                <input id="stock_total" type="number" name="stock_total" class="finput" min="0" value="<?= (int)($editProduct['stock_total'] ?? 0) ?>">
              </div>
            </div>

            <div class="fg">
              <label class="flabel" for="descricao" data-t="upload_merch_description">Descricao</label>
              <textarea id="descricao" name="descricao" class="finput" maxlength="2000"><?= h($editProduct['descricaoProduto'] ?? '') ?></textarea>
            </div>

            <div class="fg">
              <label class="flabel" for="imagem" data-t="upload_merch_image">Imagem</label>
              <?php if ($editProduct && !empty($editProduct['imagem'])): ?>
                <div class="edit-media-preview">
                  <img src="<?= h(asset_url('img', $editProduct['imagem'])) ?>" alt="<?= h($editProduct['nomeProduto']) ?>">
                  <span><?= current_lang() === 'en' ? 'Current image' : 'Imagem atual' ?></span>
                </div>
              <?php endif; ?>
              <div class="upload-zone upload-zone--compact">
                <input id="imagem" type="file" name="imagem" class="finput" accept=".jpg,.jpeg,.png,.webp">
                <p data-t="upload_merch_image_help">Carrega a imagem principal do produto.</p>
              </div>
            </div>

            <label class="segmented-option segmented-option--block merch-size-toggle" id="size-toggle-row">
              <input type="checkbox" id="usa_tamanhos" name="usa_tamanhos" value="1" <?= !empty($editProduct['usa_tamanhos']) ? 'checked' : '' ?>>
              <span data-t="upload_merch_use_sizes">Usar tamanhos para este produto</span>
            </label>

            <div id="size-stock-box" class="stack-form is-hidden">
              <?php foreach ($sizes as $size): ?>
                <div class="frow merch-size-row">
                  <label class="segmented-option segmented-option--block merch-size-check">
                    <input type="checkbox" name="selected_sizes[]" value="<?= (int)$size['idTamanho'] ?>" class="size-choice" <?= isset($editStocks[(int)$size['idTamanho']]) ? 'checked' : '' ?>>
                    <span><?= h($size['etiqueta']) ?></span>
                  </label>
                  <div class="fg merch-size-stock">
                    <label class="flabel">Stock <?= h($size['etiqueta']) ?></label>
                    <input
                      type="number"
                      name="stock_tamanho[<?= (int)$size['idTamanho'] ?>]"
                      class="finput size-stock-input"
                      min="0"
                      value="<?= (int)($editStocks[(int)$size['idTamanho']] ?? 0) ?>"
                      data-size-id="<?= (int)$size['idTamanho'] ?>"
                      disabled
                    >
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn-dark"><?= $editProduct ? (current_lang() === 'en' ? 'Save and send for review' : 'Guardar e enviar para revisao') : '<span data-t="upload_merch_submit">Enviar produto</span>' ?></button>
            <?php if ($editProduct): ?>
              <a href="profile.php" class="btn btn-ghost"><?= current_lang() === 'en' ? 'Back to profile' : 'Voltar ao perfil' ?></a>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <div class="card surface-card surface-card--soft">
        <div class="card-body">
          <h3 class="section-card-title" data-t="upload_merch_requests">Os meus pedidos</h3>
          <?php if (!$myProducts): ?>
            <p data-t="upload_merch_empty">Ainda nao submeteste nenhum produto.</p>
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
                      <span class="slabel" data-t="upload_rejection_reason">Motivo de rejeicao</span>
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
