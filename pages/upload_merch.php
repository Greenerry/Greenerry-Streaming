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
$editImages = [];

if ($editId > 0) {
    // Product edits are allowed only for the owner and only while not admin-blocked.
    $editProduct = db_one($conn, "SELECT * FROM produto WHERE idProduto = {$editId} AND idCliente = {$uid} LIMIT 1");
    if (!$editProduct || (int)($editProduct['bloqueado_admin'] ?? 0) === 1) {
        header('Location: profile.php?tab=merch');
        exit;
    }

    foreach (db_all($conn, "SELECT idTamanho, stock FROM produto_tamanho_stock WHERE idProduto = {$editId}") as $stockRow) {
        $editStocks[(int)$stockRow['idTamanho']] = (int)$stockRow['stock'];
    }
    $editImages = product_images($conn, $editId);
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
    } elseif (!$err && ($err = required_field($brand, 'A marca', 'Brand'))) {
    } elseif (!$err && ($err = required_field($description, 'A descrição', 'Description'))) {
    } elseif (!$err && $price <= 0) {
        $err = tr('error.price_positive');
    } elseif (!$err && $categoryId <= 0) {
        $err = tr('error.category_required');
    } elseif (!$err && !$usesSizes && $stockTotal <= 0) {
        $err = current_lang() === 'en' ? 'Stock must be greater than zero.' : 'O stock tem de ser maior que zero.';
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

    $currentImages = $editProduct ? product_images($conn, $editId) : [];
    $images = $currentImages;
    if ($editProduct) {
        // Existing image hidden inputs say which current images the artist kept.
        $requestedExistingImages = $_POST['existing_images'] ?? [];
        if (is_array($requestedExistingImages)) {
            $images = [];
            foreach ($requestedExistingImages as $requestedImage) {
                $cleanImage = clean_product_image_name((string)$requestedImage);
                if ($cleanImage !== '' && in_array($cleanImage, $currentImages, true)) {
                    $images[] = $cleanImage;
                }
            }
        }
    }

    $uploadedImages = [];
    $imageFiles = $_FILES['imagens'] ?? null;
    if (!$err && $imageFiles && is_array($imageFiles['name'] ?? null)) {
        foreach ($imageFiles['name'] as $index => $sourceName) {
            if ($sourceName === '') {
                continue;
            }

            $file = [
                'name' => $sourceName,
                'type' => $imageFiles['type'][$index] ?? '',
                'tmp_name' => $imageFiles['tmp_name'][$index] ?? '',
                'error' => $imageFiles['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $imageFiles['size'][$index] ?? 0,
            ];

            $err = validate_uploaded_image($file);
            if ($err) {
                break;
            }

            [$savedImage, $saveErr] = save_uploaded_file($file, 'img', 'product_' . $uid, ['jpg', 'jpeg', 'png', 'webp'], 5_000_000);
            if ($saveErr) {
                $err = $saveErr;
                break;
            }
            $uploadedImages[] = $savedImage;
        }
    }
    if (!$err && $uploadedImages) {
        $images = array_merge($images, $uploadedImages);
    }

    if (!$err && !$images) {
        $err = current_lang() === 'en' ? 'Add at least one product image.' : 'Adiciona pelo menos uma imagem do produto.';
    } elseif (!$err && count($images) > GREENERRY_MAX_PRODUCT_IMAGES) {
        $err = current_lang() === 'en'
            ? 'A product can have at most ' . GREENERRY_MAX_PRODUCT_IMAGES . ' images.'
            : 'Um produto pode ter no máximo ' . GREENERRY_MAX_PRODUCT_IMAGES . ' imagens.';
    }

    if (!$err) {
        mysqli_begin_transaction($conn);
        try {
            $nameSafe = db_escape($conn, $name);
            $brandSafe = db_escape($conn, $brand);
            $descriptionSafe = db_escape($conn, $description);
            if ($editProduct) {
                // Any merch edit goes back to pending review before it can appear publicly.
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
                $commissionPercent = max(0, min(100, (float)site_setting('commission_percent', '5')));
                mysqli_query(
                    $conn,
                    "INSERT INTO produto (idCliente, idCategoria, nomeProduto, descricaoProduto, marca, precoAtual, stock_total, usa_tamanhos, iva_percentual, comissao_percentual, estado, ativo)
                     VALUES ({$uid}, {$categoryId}, '{$nameSafe}', '{$descriptionSafe}', '{$brandSafe}', {$price}, {$stockTotal}, {$usesSizes}, 23.00, {$commissionPercent}, 'pendente', 1)"
                );
                $productId = (int)mysqli_insert_id($conn);
            }

            save_product_images($conn, $productId, $images);

            if ($usesSizes) {
                // Size-based products derive stock_total from selected size rows.
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
            delete_orphan_asset_files($conn, 'img', array_diff($currentImages, $images));
            cleanup_unused_uploaded_assets($conn);
            $ok = $editProduct
                ? (current_lang() === 'en' ? 'Product updated and sent for review.' : 'Produto atualizado e enviado para revisão.')
                : tr('success.product_sent');
            if ($editProduct) {
                $editProduct = db_one($conn, "SELECT * FROM produto WHERE idProduto = {$editId} AND idCliente = {$uid} LIMIT 1");
                $editStocks = [];
                foreach (db_all($conn, "SELECT idTamanho, stock FROM produto_tamanho_stock WHERE idProduto = {$editId}") as $stockRow) {
                    $editStocks[(int)$stockRow['idTamanho']] = (int)$stockRow['stock'];
                }
                $editImages = product_images($conn, $editId);
            }
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $err = tr('error.product_save');
        }
    }
}

include '../includes/header.php';
?>

<section class="content-shell upload-merch-page">
  <div class="wrap-sm">
    <div class="submission-hero submission-hero--merch hero-card--single">
      <div class="submission-hero-copy">
        <span class="slabel" data-t="upload_merch_label">Merch</span>
        <h2><?= $editProduct ? (current_lang() === 'en' ? 'Edit product.' : 'Editar produto.') : '<span data-t="upload_merch_title">Novo produto.</span>' ?></h2>
      </div>
    </div>

    <?php if ($err): ?><div class="alert alert-err"><?= h($err) ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="alert alert-ok"><?= h($ok) ?></div><?php endif; ?>

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
              <input id="marca" type="text" name="marca" class="finput" required maxlength="100" value="<?= h($editProduct['marca'] ?? '') ?>">
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
                <input id="stock_total" type="number" name="stock_total" class="finput" min="1" required value="<?= (int)($editProduct['stock_total'] ?? 0) ?>">
              </div>
            </div>

            <div class="fg">
              <label class="flabel" for="descricao" data-t="upload_merch_description">Descrição</label>
              <textarea id="descricao" name="descricao" class="finput" required maxlength="2000"><?= h($editProduct['descricaoProduto'] ?? '') ?></textarea>
            </div>

            <div class="fg">
              <label class="flabel" for="imagens" data-t="upload_merch_image">Imagens</label>
              <?php if ($editProduct && $editImages): ?>
                <div class="edit-media-gallery">
                  <?php foreach ($editImages as $image): ?>
                    <div class="edit-media-gallery-item">
                      <img src="<?= h(asset_url('img', $image)) ?>" alt="<?= h($editProduct['nomeProduto']) ?>">
                      <input type="hidden" name="existing_images[]" value="<?= h($image) ?>">
                      <button type="button" class="merch-image-preview-remove" data-remove-existing-image aria-label="<?= h(current_lang() === 'en' ? 'Remove image' : 'Remover imagem') ?>">X</button>
                    </div>
                  <?php endforeach; ?>
                  <span data-t="upload_merch_current_images">Imagens atuais</span>
                </div>
              <?php endif; ?>
              <div class="upload-zone upload-zone--compact merch-image-field">
                <input id="imagens" type="file" name="imagens[]" class="finput" accept=".jpg,.jpeg,.png,.webp" multiple <?= $editProduct ? '' : 'required' ?>>
                <div class="merch-image-preview-grid" id="merch-image-preview-grid" hidden></div>
                <p data-t="upload_merch_image_help">Escolhe uma ou mais imagens do produto.</p>
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

            <button type="submit" class="btn btn-dark"><?= $editProduct ? (current_lang() === 'en' ? 'Save and send for review' : 'Guardar e enviar para revisão') : '<span data-t="upload_merch_submit">Enviar produto</span>' ?></button>
            <?php if ($editProduct): ?>
              <a href="profile.php?tab=merch" class="btn btn-ghost"><?= current_lang() === 'en' ? 'Back to profile' : 'Voltar ao perfil' ?></a>
            <?php endif; ?>
          </form>
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
const imageInput = document.getElementById('imagens');
const imagePreviewGrid = document.getElementById('merch-image-preview-grid');
let merchImagePreviewUrls = [];
let selectedMerchImages = [];

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

function syncMerchImageInput() {
  if (!imageInput) {
    return;
  }

  const transfer = new DataTransfer();
  selectedMerchImages.forEach((file) => transfer.items.add(file));
  imageInput.files = transfer.files;
}

function clearMerchImagePreviewUrls() {
  merchImagePreviewUrls.forEach((url) => URL.revokeObjectURL(url));
  merchImagePreviewUrls = [];
}

function renderMerchImagePreviews() {
  if (!imagePreviewGrid) {
    return;
  }

  clearMerchImagePreviewUrls();
  imagePreviewGrid.innerHTML = '';
  if (!selectedMerchImages.length) {
    imagePreviewGrid.hidden = true;
    return;
  }

  selectedMerchImages.forEach((file, index) => {
    if (!file.type.startsWith('image/')) {
      return;
    }
    const item = document.createElement('div');
    item.className = 'merch-image-preview-item';

    const image = document.createElement('img');
    const objectUrl = URL.createObjectURL(file);
    merchImagePreviewUrls.push(objectUrl);
    image.src = objectUrl;
    image.alt = file.name;
    image.className = 'merch-image-preview';

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'merch-image-preview-remove';
    removeButton.setAttribute('aria-label', file.name);
    removeButton.textContent = 'X';
    removeButton.addEventListener('click', () => {
      selectedMerchImages.splice(index, 1);
      syncMerchImageInput();
      renderMerchImagePreviews();
    });

    item.appendChild(image);
    item.appendChild(removeButton);
    imagePreviewGrid.appendChild(item);
  });
  imagePreviewGrid.hidden = imagePreviewGrid.children.length === 0;
}

imageInput?.addEventListener('change', () => {
  selectedMerchImages = Array.from(imageInput.files || []).filter((file) => file.type.startsWith('image/'));
  if (selectedMerchImages.length > <?= (int)GREENERRY_MAX_PRODUCT_IMAGES ?>) {
    toast((localStorage.getItem('g_lang') || 'pt') === 'en'
      ? 'A product can have at most <?= (int)GREENERRY_MAX_PRODUCT_IMAGES ?> images.'
      : 'Um produto pode ter no máximo <?= (int)GREENERRY_MAX_PRODUCT_IMAGES ?> imagens.');
    selectedMerchImages = selectedMerchImages.slice(0, <?= (int)GREENERRY_MAX_PRODUCT_IMAGES ?>);
  }
  syncMerchImageInput();
  renderMerchImagePreviews();
});

document.querySelectorAll('[data-remove-existing-image]').forEach((button) => {
  button.addEventListener('click', () => {
    button.closest('.edit-media-gallery-item')?.remove();
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
