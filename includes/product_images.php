<?php

function clean_product_image_name(?string $image): string
{
    $image = trim((string)$image);
    if ($image === '' || strpos($image, '..') !== false || preg_match('#^[a-z]+://#i', $image)) {
        return '';
    }
    return $image;
}

function product_images(mysqli $conn, int $productId): array
{
    if ($productId <= 0) {
        return [];
    }

    $rows = db_all_prepared(
        $conn,
        "SELECT ficheiro FROM produto_imagem WHERE idProduto = ? ORDER BY ordem ASC, idProdutoImagem ASC",
        'i',
        [$productId]
    );

    $images = [];
    foreach ($rows as $row) {
        $image = clean_product_image_name($row['ficheiro'] ?? '');
        if ($image !== '') {
            $images[] = $image;
        }
    }

    return $images;
}

function product_main_image(mysqli $conn, int $productId): string
{
    $images = product_images($conn, $productId);
    return $images[0] ?? '';
}

function save_product_images(mysqli $conn, int $productId, array $images): void
{
    if ($productId <= 0) {
        return;
    }

    $clean = array_values(array_filter(array_map(static function ($image): string {
        return clean_product_image_name((string)$image);
    }, $images)));

    db_prepared($conn, "DELETE FROM produto_imagem WHERE idProduto = ?", 'i', [$productId]);
    foreach ($clean as $index => $image) {
        db_prepared(
            $conn,
            "INSERT INTO produto_imagem (idProduto, ficheiro, ordem)
             VALUES (?, ?, ?)",
            'isi',
            [$productId, $image, (int)$index]
        );
    }
}
