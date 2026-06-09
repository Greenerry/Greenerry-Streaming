<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

$seedKey = 'pap_chart_seed_v1';

function esc2(mysqli $conn, mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_int($value) || is_float($value)) {
        return (string)$value;
    }

    return "'" . $conn->real_escape_string((string)$value) . "'";
}

function rows2(mysqli $conn, string $sql): array
{
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function one2(mysqli $conn, string $sql): ?array
{
    $row = $conn->query($sql)->fetch_assoc();
    return $row ?: null;
}

function execute2(mysqli $conn, string $sql): int
{
    $conn->query($sql);
    return $conn->affected_rows;
}

function money2(float $value): string
{
    return number_format($value, 2, '.', '');
}

function pick2(array $items, int $index): array
{
    return $items[$index % count($items)];
}

function ensurePendingProductImages2(mysqli $conn): int
{
    $map = [
        'The Weeknd After Hours collector pin set' => ['pap_real_product_658_1_the-weeknd_poster.jpg'],
        'Drake Take Care tote bag' => ['pap_real_product_803_1_drake_poster.jpg'],
        'Bladee PAP showcase t-shirt' => ['pap_real_product_651_1_bladee_t-shirt.jpg'],
        'Lil Uzi Vert Pink Tape hoodie draft' => ['pap_real_product_702_1_lil-uzi-vert_hoodie.jpg'],
        'Childish Gambino Camp anniversary vinyl' => ['pap_real_product_684_1_childish-gambino_vinil.jpg'],
    ];

    $added = 0;
    foreach ($map as $productName => $images) {
        $product = one2($conn, "SELECT idProduto FROM produto WHERE nomeProduto = " . esc2($conn, $productName) . " ORDER BY idProduto DESC LIMIT 1");
        if (!$product) {
            continue;
        }

        $productId = (int)$product['idProduto'];
        $existing = one2($conn, "SELECT COUNT(*) AS total FROM produto_imagem WHERE idProduto = {$productId}");
        if ((int)($existing['total'] ?? 0) > 0) {
            continue;
        }

        foreach ($images as $order => $image) {
            if (!is_file(__DIR__ . '/../assets/img/' . $image)) {
                continue;
            }
            execute2(
                $conn,
                'INSERT INTO produto_imagem (idProduto, ficheiro, ordem) VALUES (' .
                implode(',', [
                    esc2($conn, $productId),
                    esc2($conn, $image),
                    esc2($conn, $order),
                ]) .
                ')'
            );
            $added++;
        }
    }

    return $added;
}

function stateItem2(string $state): string
{
    return match ($state) {
        'em_preparacao' => 'em_preparacao',
        'enviada' => 'enviado',
        'entregue' => 'entregue',
        'cancelada' => 'cancelado',
        default => 'pendente',
    };
}

function addHistoricalOrder2(
    mysqli $conn,
    array $buyer,
    array $items,
    string $createdAt,
    string $method,
    int $index
): void {
    $subtotal = 0.0;
    $tax = 0.0;
    $commission = 0.0;

    foreach ($items as $item) {
        $quantity = (int)$item['quantidade'];
        $line = (float)$item['precoAtual'] * $quantity;
        $subtotal += $line;
        $tax += $line * ((float)$item['iva_percentual'] / 100);
        $commission += $line * ((float)$item['comissao_percentual'] / 100);
    }

    $paidAt = (new DateTimeImmutable($createdAt))->modify('+18 minutes')->format('Y-m-d H:i:s');
    $sentAt = (new DateTimeImmutable($createdAt))->modify('+2 days')->format('Y-m-d H:i:s');
    $deliveredAt = (new DateTimeImmutable($createdAt))->modify('+6 days')->format('Y-m-d H:i:s');

    execute2(
        $conn,
        'INSERT INTO encomenda (idCliente, subtotal, iva_total, comissao_total, total_final, estado_encomenda, estado_pagamento, metodo_pagamento, nif, observacoes, transportadora, tracking_number, tracking_url, pago_em, enviado_em, entregue_em, criado_em, atualizado_em) VALUES (' .
        implode(',', [
            esc2($conn, (int)$buyer['idCliente']),
            esc2($conn, money2($subtotal)),
            esc2($conn, money2($tax)),
            esc2($conn, money2($commission)),
            esc2($conn, money2($subtotal)),
            esc2($conn, 'entregue'),
            esc2($conn, 'pago'),
            esc2($conn, $method),
            esc2($conn, '248' . str_pad((string)$index, 6, '0', STR_PAD_LEFT)),
            esc2($conn, 'Encomenda histórica coerente para preencher relatórios mensais da PAP.'),
            esc2($conn, 'CTT Expresso'),
            esc2($conn, 'PAPHIST' . str_pad((string)$index, 6, '0', STR_PAD_LEFT)),
            esc2($conn, 'https://www.ctt.pt/feapl_2/app/open/objectSearch/objectSearch.jspx'),
            esc2($conn, $paidAt),
            esc2($conn, $sentAt),
            esc2($conn, $deliveredAt),
            esc2($conn, $createdAt),
            esc2($conn, $createdAt),
        ]) .
        ')'
    );

    $orderId = (int)$conn->insert_id;

    execute2(
        $conn,
        'INSERT INTO morada_encomenda (idEncomenda, nome_destinatario, morada, cidade, codigo_postal, pais, telefone) VALUES (' .
        implode(',', [
            esc2($conn, $orderId),
            esc2($conn, $buyer['nome']),
            esc2($conn, 'Avenida da PAP, ' . (20 + $index)),
            esc2($conn, ['Almada', 'Cacilhas', 'Lisboa', 'Setúbal'][$index % 4]),
            esc2($conn, '2800-' . str_pad((string)(300 + $index), 3, '0', STR_PAD_LEFT)),
            esc2($conn, 'Portugal'),
            esc2($conn, '+351 920 000 ' . str_pad((string)$index, 3, '0', STR_PAD_LEFT)),
        ]) .
        ')'
    );

    foreach ($items as $item) {
        $quantity = (int)$item['quantidade'];
        $line = (float)$item['precoAtual'] * $quantity;
        $lineTax = $line * ((float)$item['iva_percentual'] / 100);
        $lineCommission = $line * ((float)$item['comissao_percentual'] / 100);

        execute2(
            $conn,
            'INSERT INTO encomenda_item (idEncomenda, idProduto, idArtista, idTamanho, nome_produto, categoria_nome, quantidade, preco_unitario, iva_percentual, iva_valor, comissao_percentual, comissao_valor, subtotal_linha, total_linha, valor_artista, estado_item, criado_em) VALUES (' .
            implode(',', [
                esc2($conn, $orderId),
                esc2($conn, (int)$item['idProduto']),
                esc2($conn, (int)$item['idCliente']),
                esc2($conn, null),
                esc2($conn, $item['nomeProduto']),
                esc2($conn, $item['nomeCategoria']),
                esc2($conn, $quantity),
                esc2($conn, money2((float)$item['precoAtual'])),
                esc2($conn, money2((float)$item['iva_percentual'])),
                esc2($conn, money2($lineTax)),
                esc2($conn, money2((float)$item['comissao_percentual'])),
                esc2($conn, money2($lineCommission)),
                esc2($conn, money2($line)),
                esc2($conn, money2($line)),
                esc2($conn, money2($line - $lineCommission)),
                esc2($conn, stateItem2('entregue')),
                esc2($conn, $createdAt),
            ]) .
            ')'
        );
    }

    execute2(
        $conn,
        'INSERT INTO pagamento (idEncomenda, valor, metodo_pagamento, estado_pagamento, referencia, data_pagamento) VALUES (' .
        implode(',', [
            esc2($conn, $orderId),
            esc2($conn, money2($subtotal)),
            esc2($conn, $method),
            esc2($conn, 'pago'),
            esc2($conn, 'PAP-HIST-PAY-' . $orderId),
            esc2($conn, $paidAt),
        ]) .
        ')'
    );
}

try {
    $existingSeed = one2($conn, "SELECT valor_configuracao FROM configuracao_site WHERE chave_configuracao = " . esc2($conn, $seedKey));
    if ($existingSeed) {
        $addedImages = ensurePendingProductImages2($conn);
        echo "A seed '{$seedKey}' já foi aplicada em {$existingSeed['valor_configuracao']}.\n";
        echo "Imagens pendentes adicionadas: {$addedImages}\n";
        exit(0);
    }

    $buyers = rows2($conn, "SELECT idCliente, nome FROM cliente WHERE idCliente IN (223,224,225,226,227,229) ORDER BY idCliente");
    $products = rows2(
        $conn,
        "SELECT p.idProduto, p.idCliente, p.nomeProduto, p.precoAtual, p.iva_percentual, p.comissao_percentual, c.nomeCategoria
         FROM produto p
         LEFT JOIN categoria c ON c.idCategoria = p.idCategoria
         WHERE p.estado = 'aprovado' AND p.ativo = 1
         ORDER BY p.idCliente, p.idProduto"
    );

    if (!$buyers || !$products) {
        throw new RuntimeException('Sem clientes ou produtos aprovados suficientes.');
    }

    $productsByArtist = [];
    foreach ($products as $product) {
        $productsByArtist[(int)$product['idCliente']][] = $product;
    }

    $conn->begin_transaction();

    $pendingProducts = [
        [214, 6, 'The Weeknd After Hours collector pin set', 'Acessório de coleção submetido para revisão administrativa.', 'XO Store', 18.99, 40, 0, '2026-06-08 09:10:00'],
        [222, 6, 'Drake Take Care tote bag', 'Saco de pano promocional para testar aprovação de loja.', 'OVO Demo', 24.99, 35, 0, '2026-06-08 09:25:00'],
        [213, 1, 'Bladee PAP showcase t-shirt', 'T-shirt submetida pelo artista para demonstrar revisão pendente.', 'Drain Demo', 32.99, 48, 1, '2026-06-08 09:40:00'],
        [221, 2, 'Lil Uzi Vert Pink Tape hoodie draft', 'Hoodie em análise antes de ficar visível na loja.', 'Uzi Demo', 74.99, 24, 1, '2026-06-08 10:05:00'],
        [218, 3, 'Childish Gambino Camp anniversary vinyl', 'Vinil de demonstração aguardando validação do administrador.', 'Glassnote Demo', 47.99, 20, 0, '2026-06-08 10:30:00'],
    ];

    $pendingProductIds = [];
    foreach ($pendingProducts as [$artistId, $categoryId, $name, $description, $brand, $price, $stock, $usesSizes, $createdAt]) {
        execute2(
            $conn,
            'INSERT INTO produto (idCliente, idCategoria, nomeProduto, descricaoProduto, marca, precoAtual, iva_percentual, comissao_percentual, stock_total, usa_tamanhos, estado, ativo, criado_em, atualizado_em) VALUES (' .
            implode(',', [
                esc2($conn, $artistId),
                esc2($conn, $categoryId),
                esc2($conn, $name),
                esc2($conn, $description),
                esc2($conn, $brand),
                esc2($conn, money2($price)),
                esc2($conn, '23.00'),
                esc2($conn, '5.00'),
                esc2($conn, $stock),
                esc2($conn, $usesSizes),
                esc2($conn, 'pendente'),
                esc2($conn, 1),
                esc2($conn, $createdAt),
                esc2($conn, $createdAt),
            ]) .
            ')'
        );
        $productId = (int)$conn->insert_id;
        $pendingProductIds[] = $productId;

        if ((int)$usesSizes === 1) {
            foreach ([1, 2, 3, 4] as $sizeId) {
                execute2(
                    $conn,
                    'INSERT INTO produto_tamanho_stock (idProduto, idTamanho, stock, ativo) VALUES (' .
                    implode(',', [
                        esc2($conn, $productId),
                        esc2($conn, $sizeId),
                        esc2($conn, max(3, (int)floor($stock / 4))),
                        esc2($conn, 1),
                    ]) .
                    ')'
                );
            }
        }
    }

    $pendingReleases = [
        [214, 'PAP Draft - Midnight Preview', 'Single', 6, 'Rascunho R&B/Soul usado para demonstrar aprovação musical.', '2026-07-05', 'PAP Midnight Preview', 198],
        [222, 'PAP Draft - Studio Notes', 'EP', 3, 'EP de demonstração para o painel de revisão de lançamentos.', '2026-07-12', 'PAP Studio Notes', 184],
        [213, 'PAP Draft - Alternative Session', 'Single', 1, 'Sessão alternativa submetida como exemplo de validação.', '2026-07-18', 'PAP Alternative Session', 212],
        [220, 'PAP Draft - Pop Focus', 'Single', 5, 'Faixa pop em análise para completar gráficos de catálogo.', '2026-07-20', 'PAP Pop Focus', 176],
    ];

    $pendingReleaseIds = [];
    foreach ($pendingReleases as $i => [$artistId, $title, $type, $genreId, $description, $launchDate, $trackTitle, $duration]) {
        $createdAt = (new DateTimeImmutable('2026-06-08 11:00:00'))->modify('+' . ($i * 18) . ' minutes')->format('Y-m-d H:i:s');
        execute2(
            $conn,
            'INSERT INTO release_musical (idCliente, titulo, tipo, idGenero, descricao, capa, data_lancamento, estado, ativo, criado_em, atualizado_em) VALUES (' .
            implode(',', [
                esc2($conn, $artistId),
                esc2($conn, $title),
                esc2($conn, $type),
                esc2($conn, $genreId),
                esc2($conn, $description),
                esc2($conn, null),
                esc2($conn, $launchDate),
                esc2($conn, 'pendente'),
                esc2($conn, 1),
                esc2($conn, $createdAt),
                esc2($conn, $createdAt),
            ]) .
            ')'
        );
        $releaseId = (int)$conn->insert_id;
        $pendingReleaseIds[] = $releaseId;

        execute2(
            $conn,
            'INSERT INTO faixa (idRelease, numero_faixa, titulo, genero, idGenero, ficheiro_audio, duracao_segundos, estado, ativo, criado_em, atualizado_em) VALUES (' .
            implode(',', [
                esc2($conn, $releaseId),
                esc2($conn, 1),
                esc2($conn, $trackTitle),
                esc2($conn, null),
                esc2($conn, $genreId),
                esc2($conn, 'uploads/audio/pap_draft_' . $releaseId . '.mp3'),
                esc2($conn, $duration),
                esc2($conn, 'pendente'),
                esc2($conn, 1),
                esc2($conn, $createdAt),
                esc2($conn, $createdAt),
            ]) .
            ')'
        );
    }

    $methods = ['cartao', 'mbway', 'transferencia'];
    $artistCycle = [214, 222, 213, 221, 218, 220, 215, 219];
    $monthStarts = ['2026-01-12 14:00:00', '2026-02-10 16:15:00', '2026-03-09 11:30:00', '2026-04-14 18:45:00'];
    $historicalOrders = 0;
    $orderIndex = 101;

    foreach ($monthStarts as $monthIndex => $monthStart) {
        for ($i = 0; $i < 5; $i++) {
            $buyer = pick2($buyers, $monthIndex + $i);
            $artistId = $artistCycle[($monthIndex * 2 + $i) % count($artistCycle)];
            $secondArtistId = $artistCycle[($monthIndex * 2 + $i + 3) % count($artistCycle)];
            $items = [];

            foreach ([$artistId, $secondArtistId] as $itemIndex => $itemArtistId) {
                if (!isset($productsByArtist[$itemArtistId])) {
                    continue;
                }
                $item = pick2($productsByArtist[$itemArtistId], $monthIndex + $i + $itemIndex);
                $item['quantidade'] = 1 + (($monthIndex + $i + $itemIndex) % 2);
                $items[] = $item;
            }

            $createdAt = (new DateTimeImmutable($monthStart))
                ->modify('+' . ($i * 4) . ' days')
                ->modify('+' . (($i * 53) % 360) . ' minutes')
                ->format('Y-m-d H:i:s');

            addHistoricalOrder2($conn, $buyer, $items, $createdAt, $methods[($monthIndex + $i) % count($methods)], $orderIndex++);
            $historicalOrders++;
        }
    }

    execute2(
        $conn,
        'INSERT INTO configuracao_site (chave_configuracao, valor_configuracao) VALUES (' .
        esc2($conn, $seedKey) . ',' . esc2($conn, date('Y-m-d H:i:s')) .
        ') ON DUPLICATE KEY UPDATE valor_configuracao = VALUES(valor_configuracao)'
    );

    $pendingImages = ensurePendingProductImages2($conn);

    $conn->commit();

    echo "Seed de gráficos aplicada com sucesso.\n";
    echo 'Produtos pendentes: ' . count($pendingProductIds) . "\n";
    echo "Imagens pendentes adicionadas: {$pendingImages}\n";
    echo 'Lançamentos pendentes: ' . count($pendingReleaseIds) . "\n";
    echo "Encomendas históricas: {$historicalOrders}\n";
} catch (Throwable $e) {
    try {
        $conn->rollback();
    } catch (Throwable) {
    }

    fwrite(STDERR, 'Erro ao aplicar a seed de gráficos: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
