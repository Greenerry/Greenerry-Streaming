<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

$seedKey = 'pap_rich_seed_v1';

function esc(mysqli $conn, mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_int($value) || is_float($value)) {
        return (string)$value;
    }

    return "'" . $conn->real_escape_string((string)$value) . "'";
}

function rows(mysqli $conn, string $sql): array
{
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function one(mysqli $conn, string $sql): ?array
{
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row ?: null;
}

function execute(mysqli $conn, string $sql): int
{
    $conn->query($sql);
    return $conn->affected_rows;
}

function requireRows(array $items, string $label): void
{
    if (!$items) {
        throw new RuntimeException("Sem dados suficientes para criar: {$label}");
    }
}

function pick(array $items, int $index): array
{
    return $items[$index % count($items)];
}

function money(float $value): string
{
    return number_format($value, 2, '.', '');
}

function itemState(string $orderState): string
{
    return match ($orderState) {
        'em_preparacao' => 'em_preparacao',
        'enviada' => 'enviado',
        'entregue' => 'entregue',
        'cancelada' => 'cancelado',
        default => 'pendente',
    };
}

function addNotification(
    mysqli $conn,
    int $clientId,
    string $title,
    string $message,
    string $type,
    int $read,
    string $createdAt
): void {
    execute(
        $conn,
        'INSERT INTO notificacao (idCliente, titulo, mensagem, tipo, lida, criado_em) VALUES (' .
        implode(',', [
            esc($conn, $clientId),
            esc($conn, $title),
            esc($conn, $message),
            esc($conn, $type),
            esc($conn, $read),
            esc($conn, $createdAt),
        ]) .
        ')'
    );
}

function addFollow(mysqli $conn, int $followerId, int $artistId, string $createdAt): void
{
    execute(
        $conn,
        'INSERT IGNORE INTO seguir_artista (idSeguidor, idArtista, criado_em) VALUES (' .
        implode(',', [esc($conn, $followerId), esc($conn, $artistId), esc($conn, $createdAt)]) .
        ')'
    );
}

function addFavorite(mysqli $conn, int $clientId, int $trackId, string $createdAt): void
{
    execute(
        $conn,
        'INSERT IGNORE INTO favorito_musica (idCliente, idFaixa, criado_em) VALUES (' .
        implode(',', [esc($conn, $clientId), esc($conn, $trackId), esc($conn, $createdAt)]) .
        ')'
    );
}

function tracksForArtist(array $tracksByArtist, int $artistId): array
{
    return $tracksByArtist[$artistId] ?? [];
}

function makePlaylist(
    mysqli $conn,
    int $clientId,
    string $name,
    string $description,
    array $tracks,
    string $createdAt
): int {
    execute(
        $conn,
        'INSERT INTO playlist (idCliente, nome, descricao, visibilidade, criado_em, atualizado_em) VALUES (' .
        implode(',', [
            esc($conn, $clientId),
            esc($conn, $name),
            esc($conn, $description),
            esc($conn, 'publica'),
            esc($conn, $createdAt),
            esc($conn, $createdAt),
        ]) .
        ')'
    );

    $playlistId = (int)$conn->insert_id;
    $order = 1;
    foreach ($tracks as $track) {
        execute(
            $conn,
            'INSERT IGNORE INTO playlist_faixa (idPlaylist, idFaixa, ordem, criado_em) VALUES (' .
            implode(',', [
                esc($conn, $playlistId),
                esc($conn, (int)$track['idFaixa']),
                esc($conn, $order++),
                esc($conn, $createdAt),
            ]) .
            ')'
        );
        addFavorite($conn, $clientId, (int)$track['idFaixa'], $createdAt);
    }

    return $playlistId;
}

function addOrder(
    mysqli $conn,
    array $buyer,
    array $items,
    string $state,
    string $paymentState,
    string $method,
    string $createdAt,
    int $index
): int {
    $subtotal = 0.0;
    $tax = 0.0;
    $commission = 0.0;

    foreach ($items as $item) {
        $quantity = (int)$item['quantidade'];
        $lineSubtotal = (float)$item['precoAtual'] * $quantity;
        $lineTax = $lineSubtotal * ((float)$item['iva_percentual'] / 100);
        $lineCommission = $lineSubtotal * ((float)$item['comissao_percentual'] / 100);
        $subtotal += $lineSubtotal;
        $tax += $lineTax;
        $commission += $lineCommission;
    }

    $paidAt = in_array($paymentState, ['pago', 'reembolsado'], true)
        ? (new DateTimeImmutable($createdAt))->modify('+25 minutes')->format('Y-m-d H:i:s')
        : null;
    $sentAt = in_array($state, ['enviada', 'entregue'], true)
        ? (new DateTimeImmutable($createdAt))->modify('+2 days')->format('Y-m-d H:i:s')
        : null;
    $deliveredAt = $state === 'entregue'
        ? (new DateTimeImmutable($createdAt))->modify('+5 days')->format('Y-m-d H:i:s')
        : null;

    execute(
        $conn,
        'INSERT INTO encomenda (idCliente, subtotal, iva_total, comissao_total, total_final, estado_encomenda, estado_pagamento, metodo_pagamento, nif, observacoes, transportadora, tracking_number, tracking_url, pago_em, enviado_em, entregue_em, criado_em, atualizado_em) VALUES (' .
        implode(',', [
            esc($conn, (int)$buyer['idCliente']),
            esc($conn, money($subtotal)),
            esc($conn, money($tax)),
            esc($conn, money($commission)),
            esc($conn, money($subtotal)),
            esc($conn, $state),
            esc($conn, $paymentState),
            esc($conn, $method),
            esc($conn, '247' . str_pad((string)$index, 6, '0', STR_PAD_LEFT)),
            esc($conn, 'Dados coerentes adicionados para demonstrar encomendas e relatórios da PAP.'),
            esc($conn, $state === 'pendente' ? null : 'CTT Expresso'),
            esc($conn, in_array($state, ['enviada', 'entregue'], true) ? 'PAP' . str_pad((string)$index, 8, '0', STR_PAD_LEFT) : null),
            esc($conn, in_array($state, ['enviada', 'entregue'], true) ? 'https://www.ctt.pt/feapl_2/app/open/objectSearch/objectSearch.jspx' : null),
            esc($conn, $paidAt),
            esc($conn, $sentAt),
            esc($conn, $deliveredAt),
            esc($conn, $createdAt),
            esc($conn, $createdAt),
        ]) .
        ')'
    );

    $orderId = (int)$conn->insert_id;

    execute(
        $conn,
        'INSERT INTO morada_encomenda (idEncomenda, nome_destinatario, morada, cidade, codigo_postal, pais, telefone) VALUES (' .
        implode(',', [
            esc($conn, $orderId),
            esc($conn, $buyer['nome']),
            esc($conn, 'Rua da Escola Secundária, ' . (10 + $index)),
            esc($conn, ['Almada', 'Cacilhas', 'Lisboa', 'Setúbal', 'Seixal'][$index % 5]),
            esc($conn, '2800-' . str_pad((string)(100 + $index), 3, '0', STR_PAD_LEFT)),
            esc($conn, 'Portugal'),
            esc($conn, '+351 910 000 ' . str_pad((string)$index, 3, '0', STR_PAD_LEFT)),
        ]) .
        ')'
    );

    foreach ($items as $item) {
        $quantity = (int)$item['quantidade'];
        $lineSubtotal = (float)$item['precoAtual'] * $quantity;
        $lineTax = $lineSubtotal * ((float)$item['iva_percentual'] / 100);
        $lineCommission = $lineSubtotal * ((float)$item['comissao_percentual'] / 100);
        execute(
            $conn,
            'INSERT INTO encomenda_item (idEncomenda, idProduto, idArtista, idTamanho, nome_produto, categoria_nome, quantidade, preco_unitario, iva_percentual, iva_valor, comissao_percentual, comissao_valor, subtotal_linha, total_linha, valor_artista, estado_item, criado_em) VALUES (' .
            implode(',', [
                esc($conn, $orderId),
                esc($conn, (int)$item['idProduto']),
                esc($conn, (int)$item['idCliente']),
                esc($conn, null),
                esc($conn, $item['nomeProduto']),
                esc($conn, $item['nomeCategoria']),
                esc($conn, $quantity),
                esc($conn, money((float)$item['precoAtual'])),
                esc($conn, money((float)$item['iva_percentual'])),
                esc($conn, money($lineTax)),
                esc($conn, money((float)$item['comissao_percentual'])),
                esc($conn, money($lineCommission)),
                esc($conn, money($lineSubtotal)),
                esc($conn, money($lineSubtotal)),
                esc($conn, money($lineSubtotal - $lineCommission)),
                esc($conn, itemState($state)),
                esc($conn, $createdAt),
            ]) .
            ')'
        );
    }

    execute(
        $conn,
        'INSERT INTO pagamento (idEncomenda, valor, metodo_pagamento, estado_pagamento, referencia, data_pagamento) VALUES (' .
        implode(',', [
            esc($conn, $orderId),
            esc($conn, money($subtotal)),
            esc($conn, $method),
            esc($conn, $paymentState),
            esc($conn, 'PAP-PAY-' . $orderId),
            esc($conn, $paidAt ?? (new DateTimeImmutable($createdAt))->modify('+12 minutes')->format('Y-m-d H:i:s')),
        ]) .
        ')'
    );

    $firstItem = $items[0];
    execute(
        $conn,
        'INSERT INTO encomenda_mensagem (idEncomenda, idProduto, idComprador, idArtista, remetente, mensagem, lida, criado_em) VALUES (' .
        implode(',', [
            esc($conn, $orderId),
            esc($conn, (int)$firstItem['idProduto']),
            esc($conn, (int)$buyer['idCliente']),
            esc($conn, (int)$firstItem['idCliente']),
            esc($conn, 'comprador'),
            esc($conn, 'Olá, estou a acompanhar esta encomenda no contexto da demonstração da PAP.'),
            esc($conn, $state === 'pendente' ? 0 : 1),
            esc($conn, (new DateTimeImmutable($createdAt))->modify('+1 hour')->format('Y-m-d H:i:s')),
        ]) .
        ')'
    );

    if ($state !== 'pendente') {
        execute(
            $conn,
            'INSERT INTO encomenda_mensagem (idEncomenda, idProduto, idComprador, idArtista, remetente, mensagem, lida, criado_em) VALUES (' .
            implode(',', [
                esc($conn, $orderId),
                esc($conn, (int)$firstItem['idProduto']),
                esc($conn, (int)$buyer['idCliente']),
                esc($conn, (int)$firstItem['idCliente']),
                esc($conn, 'artista'),
                esc($conn, 'Obrigado pela compra. A encomenda está a ser tratada com cuidado.'),
                esc($conn, 1),
                esc($conn, (new DateTimeImmutable($createdAt))->modify('+5 hours')->format('Y-m-d H:i:s')),
            ]) .
            ')'
        );
    }

    if ($state === 'entregue') {
        foreach (array_slice($items, 0, 2) as $reviewIndex => $item) {
            execute(
                $conn,
                'INSERT IGNORE INTO produto_review (idProduto, idCliente, idEncomenda, rating, comentario, criado_em, atualizado_em) VALUES (' .
                implode(',', [
                    esc($conn, (int)$item['idProduto']),
                    esc($conn, (int)$buyer['idCliente']),
                    esc($conn, $orderId),
                    esc($conn, 4 + (($index + $reviewIndex) % 2)),
                    esc($conn, 'Boa qualidade e apresentação profissional para a demonstração da PAP.'),
                    esc($conn, $deliveredAt ?? $createdAt),
                    esc($conn, $deliveredAt ?? $createdAt),
                ]) .
                ')'
            );
        }
    }

    addNotification(
        $conn,
        (int)$buyer['idCliente'],
        'Atualização da encomenda #' . $orderId,
        'A sua encomenda de demonstração Greenerry foi atualizada para "' . $state . '".',
        'encomenda',
        $state === 'pendente' ? 0 : 1,
        $createdAt
    );

    foreach ($items as $item) {
        addNotification(
            $conn,
            (int)$item['idCliente'],
            'Venda registada na loja',
            'Recebeu uma nova venda de "' . $item['nomeProduto'] . '" para a PAP.',
            'produto',
            $state === 'pendente' ? 0 : 1,
            $createdAt
        );
    }

    return $orderId;
}

try {
    $existingSeed = one($conn, "SELECT valor_configuracao FROM configuracao_site WHERE chave_configuracao = " . esc($conn, $seedKey));
    if ($existingSeed) {
        echo "A seed '{$seedKey}' já foi aplicada em {$existingSeed['valor_configuracao']}.\n";
        exit(0);
    }

    $buyers = rows($conn, "SELECT idCliente, nome FROM cliente WHERE idCliente IN (223,224,225,226,227,229) ORDER BY idCliente");
    $artists = rows($conn, "SELECT idCliente, nome FROM cliente WHERE idCliente BETWEEN 213 AND 222 ORDER BY idCliente");
    $products = rows(
        $conn,
        "SELECT p.idProduto, p.idCliente, p.nomeProduto, p.precoAtual, p.iva_percentual, p.comissao_percentual, c.nomeCategoria
         FROM produto p
         LEFT JOIN categoria c ON c.idCategoria = p.idCategoria
         WHERE p.estado = 'aprovado' AND p.ativo = 1
         ORDER BY p.idCliente, p.idProduto"
    );
    $tracks = rows(
        $conn,
        "SELECT f.idFaixa, f.titulo, r.idCliente AS idArtista, COALESCE(g.nome, f.genero) AS genero
         FROM faixa f
         INNER JOIN release_musical r ON r.idRelease = f.idRelease
         LEFT JOIN genero g ON g.idGenero = COALESCE(f.idGenero, r.idGenero)
         WHERE r.estado = 'aprovado' AND f.estado = 'aprovada' AND f.ativo = 1
         ORDER BY r.idCliente, f.idFaixa"
    );

    requireRows($buyers, 'clientes');
    requireRows($artists, 'artistas');
    requireRows($products, 'produtos');
    requireRows($tracks, 'faixas');

    $productsByArtist = [];
    foreach ($products as $product) {
        $productsByArtist[(int)$product['idCliente']][] = $product;
    }

    $tracksByArtist = [];
    $tracksByGenre = [];
    foreach ($tracks as $track) {
        $tracksByArtist[(int)$track['idArtista']][] = $track;
        $tracksByGenre[$track['genero'] ?? 'Sem género'][] = $track;
    }

    $conn->begin_transaction();

    $paymentsAdded = 0;
    $ordersWithNoPayment = rows(
        $conn,
        "SELECT idEncomenda, total_final, metodo_pagamento, estado_pagamento, COALESCE(pago_em, criado_em) AS data_pagamento
         FROM encomenda e
         WHERE NOT EXISTS (SELECT 1 FROM pagamento p WHERE p.idEncomenda = e.idEncomenda)
           AND e.estado_pagamento IN ('pago','reembolsado','falhado')"
    );

    foreach ($ordersWithNoPayment as $order) {
        $paymentsAdded += execute(
            $conn,
            'INSERT INTO pagamento (idEncomenda, valor, metodo_pagamento, estado_pagamento, referencia, data_pagamento) VALUES (' .
            implode(',', [
                esc($conn, (int)$order['idEncomenda']),
                esc($conn, money((float)$order['total_final'])),
                esc($conn, $order['metodo_pagamento']),
                esc($conn, $order['estado_pagamento']),
                esc($conn, 'PAP-PAY-' . $order['idEncomenda']),
                esc($conn, $order['data_pagamento']),
            ]) .
            ')'
        );
    }

    $orderStates = [
        ['entregue', 'pago', 'mbway'],
        ['entregue', 'pago', 'cartao'],
        ['enviada', 'pago', 'transferencia'],
        ['em_preparacao', 'pago', 'mbway'],
        ['pendente', 'pendente', 'cartao'],
        ['entregue', 'pago', 'cartao'],
        ['cancelada', 'reembolsado', 'mbway'],
        ['enviada', 'pago', 'cartao'],
        ['entregue', 'pago', 'transferencia'],
        ['em_preparacao', 'pago', 'cartao'],
        ['entregue', 'pago', 'mbway'],
        ['pendente', 'pendente', 'transferencia'],
        ['enviada', 'pago', 'mbway'],
        ['entregue', 'pago', 'cartao'],
        ['entregue', 'pago', 'transferencia'],
        ['em_preparacao', 'pago', 'mbway'],
        ['cancelada', 'reembolsado', 'cartao'],
        ['entregue', 'pago', 'mbway'],
        ['enviada', 'pago', 'cartao'],
        ['entregue', 'pago', 'transferencia'],
    ];

    $orderIds = [];
    $artistCycle = [214, 222, 213, 221, 218, 220, 215, 219, 216, 217];
    foreach ($orderStates as $i => [$state, $paymentState, $method]) {
        $buyer = pick($buyers, $i);
        $createdAt = (new DateTimeImmutable('2026-06-08 13:20:00'))
            ->modify('-' . ($i * 2) . ' days')
            ->modify('+' . (($i * 37) % 420) . ' minutes')
            ->format('Y-m-d H:i:s');

        $mainArtistId = $artistCycle[$i % count($artistCycle)];
        $secondArtistId = $artistCycle[($i + 3) % count($artistCycle)];
        $thirdArtistId = $artistCycle[($i + 6) % count($artistCycle)];
        $items = [];

        foreach ([$mainArtistId, $secondArtistId, $thirdArtistId] as $artistIndex => $artistId) {
            if (!isset($productsByArtist[$artistId])) {
                continue;
            }
            $item = pick($productsByArtist[$artistId], $i + $artistIndex);
            $item['quantidade'] = 1 + (($i + $artistIndex) % 2);
            $items[] = $item;
            if (($i + $artistIndex) % 3 === 0) {
                break;
            }
        }

        $orderIds[] = addOrder($conn, $buyer, $items, $state, $paymentState, $method, $createdAt, $i + 1);
    }

    $playlistPlans = [
        [223, 'PAP R&B para a apresentação', [214, 222, 220], 'Faixas mais melódicas para mostrar o player e as recomendações.'],
        [224, 'PAP Alternativo e dream pop', [213, 215, 216], 'Seleção alternativa para testar a organização por géneros.'],
        [225, 'PAP Pop e foco', [220, 214, 215], 'Playlist usada para demonstrar favoritos e descoberta musical.'],
        [226, 'PAP Hip-Hop/Rap em destaque', [222, 221, 218, 219], 'Faixas de rap para alimentar gráficos por género e artista.'],
        [227, 'PAP Loja e música', [218, 214, 213], 'Mistura de artistas com merch ativo na loja.'],
        [229, 'PAP Demonstração Greenerry', [214, 222, 213, 221, 220], 'Playlist principal usada no relatório da PAP.'],
        [223, 'PAP Noite eletrónica', [216, 217, 213], 'Ambiente eletrónico para demonstrar dados por estilo musical.'],
        [226, 'PAP Top artistas recentes', [214, 222, 221, 218], 'Faixas que também aparecem nos gráficos de audições.'],
    ];

    $playlistsAdded = 0;
    foreach ($playlistPlans as $planIndex => [$clientId, $name, $artistIds, $description]) {
        $playlistTracks = [];
        foreach ($artistIds as $artistId) {
            $playlistTracks = array_merge($playlistTracks, array_slice(tracksForArtist($tracksByArtist, $artistId), $planIndex % 3, 4));
        }
        $playlistTracks = array_slice($playlistTracks, 0, 12);
        makePlaylist(
            $conn,
            $clientId,
            $name,
            $description . ' Dados criados para a PAP.',
            $playlistTracks,
            (new DateTimeImmutable('2026-06-01 18:00:00'))->modify('+' . $planIndex . ' days')->format('Y-m-d H:i:s')
        );
        $playlistsAdded++;
    }

    $followPairs = [
        223 => [214, 222, 220, 213],
        224 => [213, 215, 216, 217],
        225 => [220, 214, 222, 215],
        226 => [222, 221, 218, 219],
        227 => [218, 214, 213, 220],
        229 => [214, 222, 213, 221, 218, 220],
    ];
    foreach ($followPairs as $clientId => $artistIds) {
        foreach ($artistIds as $artistId) {
            addFollow($conn, $clientId, $artistId, '2026-06-03 12:00:00');
        }
    }

    $listensAdded = 0;
    $listenerIds = array_map(static fn(array $buyer): int => (int)$buyer['idCliente'], $buyers);
    $artistListenWeights = [
        214 => 18,
        222 => 16,
        213 => 12,
        221 => 14,
        218 => 13,
        220 => 10,
        215 => 8,
        216 => 7,
        217 => 5,
        219 => 9,
    ];

    for ($day = 0; $day < 30; $day++) {
        foreach ($artistListenWeights as $artistId => $baseCount) {
            $artistTracks = tracksForArtist($tracksByArtist, $artistId);
            if (!$artistTracks) {
                continue;
            }

            $dailyCount = $baseCount + (($day + $artistId) % 7);
            if ($day < 5 && in_array($artistId, [214, 222, 221], true)) {
                $dailyCount += 8;
            }

            for ($i = 0; $i < $dailyCount; $i++) {
                $track = pick($artistTracks, $day + $i);
                $listenerId = $listenerIds[($day + $i + $artistId) % count($listenerIds)];
                $createdAt = (new DateTimeImmutable('2026-06-08 21:00:00'))
                    ->modify("-{$day} days")
                    ->modify('-' . (($i * 11 + $artistId) % 780) . ' minutes')
                    ->format('Y-m-d H:i:s');
                $seconds = 45 + (($day * 13 + $i * 17 + $artistId) % 205);

                execute(
                    $conn,
                    'INSERT INTO faixa_listen (idFaixa, idCliente, idArtista, segundos_ouvidos, criado_em) VALUES (' .
                    implode(',', [
                        esc($conn, (int)$track['idFaixa']),
                        esc($conn, $listenerId),
                        esc($conn, $artistId),
                        esc($conn, $seconds),
                        esc($conn, $createdAt),
                    ]) .
                    ')'
                );
                $listensAdded++;
            }
        }
    }

    $supportMessages = [
        [223, 'PAP - Dúvida sobre uma encomenda', 'Queria confirmar se consigo acompanhar o envio dentro da conta.', 'Sim, o acompanhamento fica visível no histórico da encomenda.', 'respondida', 1, '2026-06-04 10:20:00', '2026-06-04 12:05:00'],
        [224, 'PAP - Pedido de alteração de morada', 'Preciso de corrigir a morada antes do envio.', null, 'aberta', null, '2026-06-05 09:12:00', null],
        [225, 'PAP - Problema ao guardar favoritos', 'A lista de favoritos deve ficar associada à minha conta?', 'Sim, os favoritos usam a sua sessão e ficam guardados na base de dados.', 'respondida', 1, '2026-06-05 15:40:00', '2026-06-05 16:10:00'],
        [226, 'PAP - Questão sobre pagamentos', 'Queria perceber se o MB WAY aparece nos relatórios.', null, 'aberta', null, '2026-06-06 11:32:00', null],
        [227, 'PAP - Ajuda com playlist pública', 'Como posso mostrar a minha playlist a outros utilizadores?', 'Pode alterar a visibilidade para pública na área da playlist.', 'fechada', 1, '2026-06-06 18:15:00', '2026-06-07 10:10:00'],
        [229, 'PAP - Validação final do projeto', 'Mensagem de teste para demonstrar o painel de suporte administrativo.', null, 'aberta', null, '2026-06-08 14:45:00', null],
    ];

    foreach ($supportMessages as [$clientId, $subject, $message, $reply, $state, $adminId, $createdAt, $answeredAt]) {
        execute(
            $conn,
            'INSERT INTO mensagem_admin (idCliente, assunto, mensagem, resposta_admin, estado, idAdminResposta, criado_em, respondido_em) VALUES (' .
            implode(',', [
                esc($conn, $clientId),
                esc($conn, $subject),
                esc($conn, $message),
                esc($conn, $reply),
                esc($conn, $state),
                esc($conn, $adminId),
                esc($conn, $createdAt),
                esc($conn, $answeredAt),
            ]) .
            ')'
        );
    }

    foreach ($artists as $i => $artist) {
        addNotification(
            $conn,
            (int)$artist['idCliente'],
            'Resumo PAP do artista',
            'Os seus dados de vendas, audições e mensagens foram preparados para a demonstração.',
            'sistema',
            $i % 2,
            (new DateTimeImmutable('2026-06-08 09:00:00'))->modify('+' . ($i * 12) . ' minutes')->format('Y-m-d H:i:s')
        );
    }

    execute(
        $conn,
        'INSERT INTO configuracao_site (chave_configuracao, valor_configuracao) VALUES (' .
        esc($conn, $seedKey) . ',' . esc($conn, date('Y-m-d H:i:s')) .
        ') ON DUPLICATE KEY UPDATE valor_configuracao = VALUES(valor_configuracao)'
    );

    $conn->commit();

    echo "Seed aplicada com sucesso.\n";
    echo "Pagamentos criados para encomendas antigas: {$paymentsAdded}\n";
    echo "Novas encomendas: " . count($orderIds) . "\n";
    echo "Playlists novas: {$playlistsAdded}\n";
    echo "Audições recentes novas: {$listensAdded}\n";
} catch (Throwable $e) {
    try {
        $conn->rollback();
    } catch (Throwable) {
    }

    fwrite(STDERR, 'Erro ao aplicar a seed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
