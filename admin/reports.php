<?php
require_once '../includes/config.php';
require_admin_permission('reports');

$range = (string)($_GET['range'] ?? '6m');
$allowedRanges = ['7d', '30d', '6m', '1y', 'all'];
if (!in_array($range, $allowedRanges, true)) {
    $range = '6m';
}
$rangeSqlMap = [
    '7d' => 'DATE_SUB(CURDATE(), INTERVAL 7 DAY)',
    '30d' => 'DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
    '6m' => 'DATE_SUB(CURDATE(), INTERVAL 5 MONTH)',
    '1y' => 'DATE_SUB(CURDATE(), INTERVAL 1 YEAR)',
    'all' => "'1970-01-01'",
];
$rangeLabels = [
    '7d' => ['key' => 'range_7d', 'label' => '7 dias'],
    '30d' => ['key' => 'range_30d', 'label' => '30 dias'],
    '6m' => ['key' => 'range_6m', 'label' => '6 meses'],
    '1y' => ['key' => 'range_1y', 'label' => '1 ano'],
    'all' => ['key' => 'range_all', 'label' => 'Tudo'],
];
$dateFromSql = $rangeSqlMap[$range];
$periodKeySql = in_array($range, ['7d', '30d'], true)
    ? "DATE_FORMAT(e.criado_em, '%Y-%m-%d')"
    : "DATE_FORMAT(e.criado_em, '%Y-%m')";
$periodLabelSql = in_array($range, ['7d', '30d'], true)
    ? "DATE_FORMAT(e.criado_em, '%d/%m')"
    : "DATE_FORMAT(e.criado_em, '%m/%Y')";

$finance = db_one(
    $conn,
    "SELECT
        COALESCE(SUM(CASE WHEN e.estado_pagamento = 'pago' AND e.estado_encomenda != 'cancelada' AND ei.estado_item != 'cancelado' THEN ei.total_linha END), 0) AS paid_revenue,
        COALESCE(SUM(CASE WHEN e.estado_pagamento = 'pago' AND e.estado_encomenda != 'cancelada' AND ei.estado_item != 'cancelado' THEN ei.comissao_valor END), 0) AS commission,
        COALESCE(SUM(CASE WHEN e.estado_pagamento = 'pago' AND e.estado_encomenda != 'cancelada' AND ei.estado_item != 'cancelado' THEN ei.valor_artista END), 0) AS artist_value,
        COALESCE(SUM(CASE WHEN e.estado_encomenda = 'cancelada' OR ei.estado_item = 'cancelado' OR e.estado_pagamento = 'reembolsado' THEN ei.total_linha END), 0) AS blocked_value
     FROM encomenda e
     JOIN encomenda_item ei ON ei.idEncomenda = e.idEncomenda
     WHERE e.criado_em >= {$dateFromSql}"
) ?? [];

$topArtists = db_all(
    $conn,
    "SELECT c.nome,
            COUNT(DISTINCT e.idEncomenda) AS orders_count,
            COALESCE(SUM(ei.valor_artista), 0) AS artist_total,
            COALESCE(SUM(ei.comissao_valor), 0) AS commission_total
     FROM encomenda_item ei
     JOIN encomenda e ON e.idEncomenda = ei.idEncomenda
     JOIN cliente c ON c.idCliente = ei.idArtista
     WHERE e.estado_pagamento = 'pago'
       AND e.estado_encomenda != 'cancelada'
       AND ei.estado_item != 'cancelado'
       AND e.criado_em >= {$dateFromSql}
     GROUP BY c.idCliente
     ORDER BY artist_total DESC
     LIMIT 10"
);

$categoryRevenue = db_all(
    $conn,
    "SELECT COALESCE(ei.categoria_nome, 'Sem categoria') AS categoria_nome,
            COUNT(*) AS items_count,
            COALESCE(SUM(ei.total_linha), 0) AS total_value
     FROM encomenda_item ei
     JOIN encomenda e ON e.idEncomenda = ei.idEncomenda
     WHERE e.estado_pagamento = 'pago'
       AND e.estado_encomenda != 'cancelada'
       AND ei.estado_item != 'cancelado'
       AND e.criado_em >= {$dateFromSql}
     GROUP BY ei.categoria_nome
     ORDER BY total_value DESC"
);

$monthlyRevenue = db_all(
    $conn,
    "SELECT {$periodKeySql} AS period_key,
            {$periodLabelSql} AS period_label,
            COALESCE(SUM(ei.total_linha), 0) AS revenue,
            COALESCE(SUM(ei.comissao_valor), 0) AS commission
     FROM encomenda e
     JOIN encomenda_item ei ON ei.idEncomenda = e.idEncomenda
     WHERE e.criado_em >= {$dateFromSql}
       AND e.estado_pagamento = 'pago'
       AND e.estado_encomenda != 'cancelada'
       AND ei.estado_item != 'cancelado'
     GROUP BY {$periodKeySql}, {$periodLabelSql}
     ORDER BY period_key ASC"
);

$maxMonthlyRevenue = 0.0;
foreach ($monthlyRevenue as $month) {
    $maxMonthlyRevenue = max($maxMonthlyRevenue, (float)$month['revenue']);
}

$maxCategoryRevenue = 0.0;
foreach ($categoryRevenue as $category) {
    $maxCategoryRevenue = max($maxCategoryRevenue, (float)$category['total_value']);
}

$breakdownTotal = max(
    1,
    (float)($finance['paid_revenue'] ?? 0) + (float)($finance['commission'] ?? 0) + (float)($finance['artist_value'] ?? 0) + (float)($finance['blocked_value'] ?? 0)
);
$incomeBreakdown = [
    ['label' => 'Receita paga', 'tkey' => 'stat_paid_revenue', 'value' => (float)($finance['paid_revenue'] ?? 0), 'color' => '#c9d0db'],
    ['label' => 'Comissão', 'tkey' => 'label_commission', 'value' => (float)($finance['commission'] ?? 0), 'color' => '#9dafaa'],
    ['label' => 'Base artistas', 'tkey' => 'stat_artist_base', 'value' => (float)($finance['artist_value'] ?? 0), 'color' => '#8b98aa'],
    ['label' => 'Bloqueado', 'tkey' => 'reports_blocked_short', 'value' => (float)($finance['blocked_value'] ?? 0), 'color' => '#d7b676'],
];
$breakdownStops = [];
$breakdownCursor = 0.0;
foreach ($incomeBreakdown as $item) {
    $slice = $item['value'] > 0 ? ($item['value'] / $breakdownTotal) * 100 : 0;
    $next = min(100, $breakdownCursor + $slice);
    if ($next > $breakdownCursor) {
        $breakdownStops[] = $item['color'] . ' ' . round($breakdownCursor, 2) . '% ' . round($next, 2) . '%';
    }
    $breakdownCursor = $next;
}
$donutStyle = $breakdownStops
    ? 'background: conic-gradient(' . implode(', ', $breakdownStops) . ', rgba(255,255,255,.10) ' . round($breakdownCursor, 2) . '% 100%);'
    : 'background: conic-gradient(#c9d0db 0 38%, #9dafaa 38% 62%, #8b98aa 62% 84%, #d7b676 84% 100%);';

if (($_GET['export'] ?? '') === 'excel') {
    $exportLang = strtolower((string)($_GET['lang'] ?? current_lang())) === 'en' ? 'en' : 'pt';
    $excelText = [
        'pt' => [
            'file' => 'greenerry-relatorio-executivo',
            'report_title' => 'Greenerry - Relatorio Executivo',
            'report_subtitle' => 'Exportação organizada com finanças, catálogo, utilizadores, releases, suporte e definições.',
            'generated' => 'Gerado em',
            'summary' => 'Resumo',
            'finance' => 'Financas',
            'catalog' => 'Catalogo',
            'products' => 'Produtos',
            'releases' => 'Lançamentos',
            'users' => 'Utilizadores',
            'order_items' => 'Itens encomenda',
            'support' => 'Suporte',
            'settings' => 'Configuracoes',
            'top_artists' => 'Top artistas',
            'categories' => 'Categorias',
            'orders' => 'Encomendas',
            'paid_revenue' => 'Receita paga',
            'commission' => 'Comissão da plataforma',
            'artist_base' => 'Base para artistas',
            'blocked_value' => 'Cancelado / reembolsado',
            'active_clients' => 'Clientes ativos',
            'active_products' => 'Produtos ativos',
            'pending_products' => 'Produtos pendentes',
            'open_messages' => 'Mensagens abertas',
            'active_releases' => 'Lançamentos ativos',
            'pending_releases' => 'Lançamentos pendentes',
            'inactive_clients' => 'Clientes inativos / bloqueados',
            'rejected_products' => 'Produtos rejeitados',
            'rejected_releases' => 'Lançamentos rejeitados',
            'tracks_registered' => 'Faixas registadas',
            'indicator' => 'Indicador',
            'value' => 'Valor',
            'period' => 'Periodo',
            'product' => 'Produto',
            'artist' => 'Artista',
            'category' => 'Categoria',
            'price' => 'Preco',
            'vat' => 'IVA %',
            'stock' => 'Stock',
            'state' => 'Estado',
            'sizes' => 'Tamanhos',
            'no_sizes' => 'Sem tamanhos',
            'title' => 'Título',
            'type' => 'Tipo',
            'tracks' => 'Faixas',
            'release_date' => 'Lançamento',
            'created_at' => 'Criado em',
            'track_list' => 'Lista de faixas',
            'name' => 'Nome',
            'email' => 'Email',
            'phone' => 'Telefone',
            'orders_count' => 'Encomendas',
            'items_sold' => 'Itens vendidos',
            'total' => 'Total',
            'customer' => 'Cliente',
            'qty' => 'Qtd',
            'subtotal' => 'Subtotal',
            'artist_value' => 'Valor artista',
            'message' => 'Mensagem',
            'subject_state' => 'Assunto / estado',
            'resolved_at' => 'Resolvido em',
            'admin' => 'Admin',
            'key' => 'Chave',
            'updated_at' => 'Atualizado em',
            'payment' => 'Pagamento',
            'recent_orders' => 'Encomendas recentes',
            'complete_catalog' => 'Catalogo completo',
            'account_activity' => 'Atividade por conta',
            'last_200_items' => 'Ultimos 200 itens',
            'status_operational' => 'Estado operacional',
            'commercial_performance' => 'Performance comercial',
            'ranking_by_artist_value' => 'Ranking por valor artista',
            'last_records' => 'Ultimos registos',
            'approved' => 'Aprovado',
            'pending' => 'Pendente',
            'rejected' => 'Rejeitado',
            'active' => 'Ativo',
            'inactive' => 'Inativo',
            'paid' => 'Pago',
            'refunded' => 'Reembolsado',
            'cancelled' => 'Cancelado',
            'processing' => 'Em processamento',
            'delivered' => 'Entregue',
            'open' => 'Aberta',
            'answered' => 'Respondida',
            'closed' => 'Fechada',
        ],
        'en' => [
            'file' => 'greenerry-executive-report',
            'report_title' => 'Greenerry Executive Report',
            'report_subtitle' => 'Organized export with finance, catalog, users, releases, support, and settings.',
            'generated' => 'Generated at',
            'summary' => 'Summary',
            'finance' => 'Finance',
            'catalog' => 'Catalog',
            'products' => 'Products',
            'releases' => 'Releases',
            'users' => 'Users',
            'order_items' => 'Order items',
            'support' => 'Support',
            'settings' => 'Settings',
            'top_artists' => 'Top artists',
            'categories' => 'Categories',
            'orders' => 'Orders',
            'paid_revenue' => 'Paid revenue',
            'commission' => 'Platform commission',
            'artist_base' => 'Artist base',
            'blocked_value' => 'Cancelled / refunded',
            'active_clients' => 'Active clients',
            'active_products' => 'Active products',
            'pending_products' => 'Pending products',
            'open_messages' => 'Open messages',
            'active_releases' => 'Active releases',
            'pending_releases' => 'Pending releases',
            'inactive_clients' => 'Inactive / blocked clients',
            'rejected_products' => 'Rejected products',
            'rejected_releases' => 'Rejected releases',
            'tracks_registered' => 'Registered tracks',
            'indicator' => 'Indicator',
            'value' => 'Value',
            'period' => 'Period',
            'product' => 'Product',
            'artist' => 'Artist',
            'category' => 'Category',
            'price' => 'Price',
            'vat' => 'VAT %',
            'stock' => 'Stock',
            'state' => 'State',
            'sizes' => 'Sizes',
            'no_sizes' => 'No sizes',
            'title' => 'Title',
            'type' => 'Type',
            'tracks' => 'Tracks',
            'release_date' => 'Release date',
            'created_at' => 'Created at',
            'track_list' => 'Track list',
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'orders_count' => 'Orders',
            'items_sold' => 'Items sold',
            'total' => 'Total',
            'customer' => 'Customer',
            'qty' => 'Qty',
            'subtotal' => 'Subtotal',
            'artist_value' => 'Artist value',
            'message' => 'Message',
            'subject_state' => 'Subject / state',
            'resolved_at' => 'Resolved at',
            'admin' => 'Admin',
            'key' => 'Key',
            'updated_at' => 'Updated at',
            'payment' => 'Payment',
            'recent_orders' => 'Recent orders',
            'complete_catalog' => 'Complete catalog',
            'account_activity' => 'Account activity',
            'last_200_items' => 'Last 200 items',
            'status_operational' => 'Operational status',
            'commercial_performance' => 'Commercial performance',
            'ranking_by_artist_value' => 'Ranking by artist value',
            'last_records' => 'Latest records',
            'approved' => 'Approved',
            'pending' => 'Pending',
            'rejected' => 'Rejected',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'paid' => 'Paid',
            'refunded' => 'Refunded',
            'cancelled' => 'Cancelled',
            'processing' => 'Processing',
            'delivered' => 'Delivered',
            'open' => 'Open',
            'answered' => 'Answered',
            'closed' => 'Closed',
        ],
    ];
    $xl = static fn(string $key): string => $excelText[$exportLang][$key] ?? $excelText['pt'][$key] ?? $key;

    $recentOrdersExport = db_all(
        $conn,
        "SELECT e.idEncomenda, c.nome AS cliente, e.estado_encomenda, e.estado_pagamento,
                e.metodo_pagamento, e.total_final, e.criado_em
         FROM encomenda e
         JOIN cliente c ON c.idCliente = e.idCliente
         ORDER BY e.criado_em DESC
         LIMIT 30"
    );

    $catalogExport = db_one(
        $conn,
        "SELECT
            (SELECT COUNT(*) FROM cliente WHERE estado = 'ativo') AS clientes_ativos,
            (SELECT COUNT(*) FROM cliente WHERE estado != 'ativo') AS clientes_inativos,
            (SELECT COUNT(*) FROM produto WHERE estado = 'aprovado' AND ativo = 1) AS produtos_ativos,
            (SELECT COUNT(*) FROM produto WHERE estado = 'pendente') AS produtos_pendentes,
            (SELECT COUNT(*) FROM produto WHERE estado = 'rejeitado') AS produtos_rejeitados,
            (SELECT COUNT(*) FROM release_musical WHERE estado = 'aprovado' AND ativo = 1) AS releases_ativos,
            (SELECT COUNT(*) FROM release_musical WHERE estado = 'pendente') AS releases_pendentes,
            (SELECT COUNT(*) FROM release_musical WHERE estado = 'rejeitado') AS releases_rejeitados,
            (SELECT COUNT(*) FROM faixa) AS faixas_total,
            (SELECT COUNT(*) FROM mensagem_admin WHERE estado = 'aberta') AS mensagens_abertas"
    ) ?? [];

    $productsExport = db_all(
        $conn,
        "SELECT p.idProduto, p.nomeProduto, c.nome AS artista, cat.nomeCategoria,
                p.precoAtual, p.iva_percentual, p.comissao_percentual, p.stock_total,
                p.usa_tamanhos, p.estado, p.ativo, p.bloqueado_admin, p.criado_em, p.aprovado_em,
                GROUP_CONCAT(CONCAT(t.etiqueta, ': ', pts.stock) ORDER BY t.ordem SEPARATOR ', ') AS tamanhos
         FROM produto p
         JOIN cliente c ON c.idCliente = p.idCliente
         JOIN categoria cat ON cat.idCategoria = p.idCategoria
         LEFT JOIN produto_tamanho_stock pts ON pts.idProduto = p.idProduto
         LEFT JOIN tamanho t ON t.idTamanho = pts.idTamanho
         GROUP BY p.idProduto
         ORDER BY p.criado_em DESC"
    );

    $releasesExport = db_all(
        $conn,
        "SELECT r.idRelease, r.titulo, c.nome AS artista, r.tipo, r.estado, r.ativo,
                r.bloqueado_admin, r.data_lancamento, r.criado_em, r.aprovado_em,
                COUNT(f.idFaixa) AS total_faixas,
                GROUP_CONCAT(f.titulo ORDER BY f.numero_faixa SEPARATOR ', ') AS faixas
         FROM release_musical r
         JOIN cliente c ON c.idCliente = r.idCliente
         LEFT JOIN faixa f ON f.idRelease = r.idRelease
         GROUP BY r.idRelease
         ORDER BY r.criado_em DESC"
    );

    $usersExport = db_all(
        $conn,
        "SELECT c.idCliente, c.nome, c.email, c.telefone, c.estado, c.criado_em,
                COUNT(DISTINCT p.idProduto) AS total_produtos,
                COUNT(DISTINCT r.idRelease) AS total_releases,
                COUNT(DISTINCT e.idEncomenda) AS total_encomendas
         FROM cliente c
         LEFT JOIN produto p ON p.idCliente = c.idCliente
         LEFT JOIN release_musical r ON r.idCliente = c.idCliente
         LEFT JOIN encomenda e ON e.idCliente = c.idCliente
         GROUP BY c.idCliente
         ORDER BY c.criado_em DESC"
    );

    $orderItemsExport = db_all(
        $conn,
        "SELECT ei.idEncomenda, e.criado_em, c.nome AS cliente, ei.nome_produto,
                art.nome AS artista, ei.categoria_nome, ei.quantidade, ei.preco_unitario,
                ei.subtotal_linha, ei.iva_valor, ei.comissao_valor, ei.valor_artista,
                ei.total_linha, ei.estado_item
         FROM encomenda_item ei
         JOIN encomenda e ON e.idEncomenda = ei.idEncomenda
         JOIN cliente c ON c.idCliente = e.idCliente
         JOIN cliente art ON art.idCliente = ei.idArtista
         ORDER BY e.criado_em DESC, ei.idEncomendaItem DESC
         LIMIT 200"
    );

    $messagesExport = db_all(
        $conn,
        "SELECT m.idMensagem, c.nome AS cliente, c.email, m.assunto, m.estado,
                m.criado_em, m.respondido_em, a.nome AS admin_nome
         FROM mensagem_admin m
         JOIN cliente c ON c.idCliente = m.idCliente
         LEFT JOIN admin a ON a.idAdmin = m.idAdminResposta
         ORDER BY FIELD(m.estado, 'aberta', 'respondida', 'fechada'), m.criado_em DESC"
    );

    $settingsExport = db_all($conn, "SELECT chave_configuracao, valor_configuracao, atualizado_em FROM configuracao_site ORDER BY chave_configuracao ASC");

    $xml = static function ($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    };
    $cell = static function ($value, string $style = 'Text', string $type = 'String') use ($xml): string {
        if ($type === 'Number') {
            $value = number_format((float)$value, 2, '.', '');
        }
        return '<Cell ss:StyleID="' . $style . '"><Data ss:Type="' . $type . '">' . $xml($value) . '</Data></Cell>';
    };
    $mergedCell = static function ($value, int $mergeAcross = 1, string $style = 'Text', string $type = 'String') use ($xml): string {
        if ($type === 'Number') {
            $value = number_format((float)$value, 2, '.', '');
        }
        return '<Cell ss:MergeAcross="' . max(1, $mergeAcross) . '" ss:StyleID="' . $style . '"><Data ss:Type="' . $type . '">' . $xml($value) . '</Data></Cell>';
    };
    $row = static function (array $cells): string {
        return '<Row>' . implode('', $cells) . '</Row>' . "\n";
    };
    $tallRow = static function (array $cells, int $height = 28): string {
        return '<Row ss:Height="' . max(18, $height) . '">' . implode('', $cells) . '</Row>' . "\n";
    };
    $moneyCell = static fn($value): string => $cell((float)$value, 'Money', 'Number');
    $numberCell = static fn($value): string => $cell((float)$value, 'Number', 'Number');
    $statusLabel = static function (string $status) use ($xl): string {
        return match ($status) {
            'aprovado' => $xl('approved'),
            'pendente' => $xl('pending'),
            'rejeitado' => $xl('rejected'),
            'ativo' => $xl('active'),
            'inativo' => $xl('inactive'),
            'pago' => $xl('paid'),
            'reembolsado' => $xl('refunded'),
            'cancelada', 'cancelado' => $xl('cancelled'),
            'processamento' => $xl('processing'),
            'entregue' => $xl('delivered'),
            'aberta' => $xl('open'),
            'respondida' => $xl('answered'),
            'fechada' => $xl('closed'),
            default => $status,
        };
    };
    $statusCell = static function (string $status) use ($cell, $statusLabel): string {
        $label = $statusLabel($status);
        $style = match ($status) {
            'aprovado', 'ativo', 'pago', 'entregue', 'respondida', 'fechada' => 'StatusGood',
            'pendente', 'aberta', 'processamento' => 'StatusWarn',
            'rejeitado', 'inativo', 'cancelada', 'cancelado', 'reembolsado' => 'StatusBad',
            default => 'Text',
        };
        return $cell($label, $style);
    };
    $paymentStatusLabelExport = static function (string $status) use ($xl): string {
        return match ($status) {
            'pago' => $xl('paid'),
            'reembolsado' => $xl('refunded'),
            'pendente' => $xl('pending'),
            default => $status,
        };
    };
    $titleRows = static function (string $title, string $subtitle) use ($row, $tallRow, $mergedCell): string {
        return $tallRow([$mergedCell($title, 9, 'Title')], 34)
            . $tallRow([$mergedCell($subtitle, 9, 'Subtitle')], 24)
            . $row([$mergedCell('', 9, 'Spacer')]);
    };
    $sheetStart = static function (string $name): string {
        return '<Worksheet ss:Name="' . htmlspecialchars($name, ENT_QUOTES | ENT_XML1, 'UTF-8') . '"><Table>'
            . '<Column ss:Width="92"/><Column ss:Width="210"/><Column ss:Width="170"/><Column ss:Width="150"/><Column ss:Width="145"/>'
            . '<Column ss:Width="135"/><Column ss:Width="135"/><Column ss:Width="155"/><Column ss:Width="175"/><Column ss:Width="260"/>';
    };
    $sheetEnd = static function (): string {
        return '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>2</SplitHorizontal><TopRowBottomPane>2</TopRowBottomPane><ActivePane>2</ActivePane></WorksheetOptions></Worksheet>';
    };

    $workbook = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<?mso-application progid="Excel.Sheet"?>' . "\n"
        . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
        . '<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office"><Author>Greenerry Admin</Author><Company>Greenerry</Company><Title>Greenerry Executive Report</Title></DocumentProperties>'
        . '<Styles>'
        . '<Style ss:ID="Default" ss:Name="Normal"><Font ss:FontName="Aptos" ss:Size="11" ss:Color="#111827"/><Alignment ss:Vertical="Center"/></Style>'
        . '<Style ss:ID="Title"><Font ss:FontName="Georgia" ss:Bold="1" ss:Size="22" ss:Color="#FFFFFF"/><Interior ss:Color="#111827" ss:Pattern="Solid"/><Alignment ss:Vertical="Center"/></Style>'
        . '<Style ss:ID="Subtitle"><Font ss:FontName="Aptos" ss:Size="10" ss:Color="#CBD5E1"/><Interior ss:Color="#111827" ss:Pattern="Solid"/><Alignment ss:Vertical="Center"/></Style>'
        . '<Style ss:ID="MetricLabel"><Font ss:Bold="1" ss:Size="9" ss:Color="#64748B"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/><Alignment ss:Vertical="Center"/></Style>'
        . '<Style ss:ID="MetricMoney"><Font ss:Bold="1" ss:Size="16" ss:Color="#111827"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/><NumberFormat ss:Format="#,##0.00 &quot;EUR&quot;"/></Style>'
        . '<Style ss:ID="MetricNumber"><Font ss:Bold="1" ss:Size="16" ss:Color="#111827"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/><NumberFormat ss:Format="0"/></Style>'
        . '<Style ss:ID="Spacer"><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/></Style>'
        . '<Style ss:ID="Header"><Font ss:Bold="1" ss:Size="9" ss:Color="#FFFFFF"/><Interior ss:Color="#1F2937" ss:Pattern="Solid"/><Alignment ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders></Style>'
        . '<Style ss:ID="Text"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/></Borders></Style>'
        . '<Style ss:ID="Number"><NumberFormat ss:Format="0"/><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/></Borders></Style>'
        . '<Style ss:ID="Money"><NumberFormat ss:Format="#,##0.00 &quot;EUR&quot;"/><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/></Borders></Style>'
        . '<Style ss:ID="StatusGood"><Font ss:Bold="1" ss:Size="9" ss:Color="#14532D"/><Interior ss:Color="#DCFCE7" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>'
        . '<Style ss:ID="StatusWarn"><Font ss:Bold="1" ss:Size="9" ss:Color="#713F12"/><Interior ss:Color="#FEF3C7" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>'
        . '<Style ss:ID="StatusBad"><Font ss:Bold="1" ss:Size="9" ss:Color="#7F1D1D"/><Interior ss:Color="#FEE2E2" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>'
        . '</Styles>';

    $workbook .= $sheetStart($xl('summary'));
    $workbook .= $tallRow([$mergedCell($xl('report_title'), 4, 'Title'), $mergedCell($xl('generated') . ' ' . date('Y-m-d H:i'), 4, 'Title')], 34);
    $workbook .= $tallRow([$mergedCell($xl('report_subtitle'), 9, 'Subtitle')], 24);
    $workbook .= $row([$cell('', 'Spacer')]);
    $workbook .= $row([$cell($xl('paid_revenue'), 'MetricLabel'), $cell($xl('commission'), 'MetricLabel'), $cell($xl('artist_base'), 'MetricLabel'), $cell($xl('blocked_value'), 'MetricLabel')]);
    $workbook .= $tallRow([$cell($finance['paid_revenue'] ?? 0, 'MetricMoney', 'Number'), $cell($finance['commission'] ?? 0, 'MetricMoney', 'Number'), $cell($finance['artist_value'] ?? 0, 'MetricMoney', 'Number'), $cell($finance['blocked_value'] ?? 0, 'MetricMoney', 'Number')], 32);
    $workbook .= $row([$cell('', 'Spacer')]);
    $workbook .= $row([$cell($xl('active_clients'), 'MetricLabel'), $cell($xl('active_products'), 'MetricLabel'), $cell($xl('pending_products'), 'MetricLabel'), $cell($xl('open_messages'), 'MetricLabel')]);
    $workbook .= $tallRow([$cell($catalogExport['clientes_ativos'] ?? 0, 'MetricNumber', 'Number'), $cell($catalogExport['produtos_ativos'] ?? 0, 'MetricNumber', 'Number'), $cell($catalogExport['produtos_pendentes'] ?? 0, 'MetricNumber', 'Number'), $cell($catalogExport['mensagens_abertas'] ?? 0, 'MetricNumber', 'Number')], 32);
    $workbook .= $row([$cell('', 'Spacer')]);
    $workbook .= $row([$cell($xl('indicator'), 'Header'), $cell($xl('value'), 'Header')]);
    $workbook .= $row([$cell($xl('active_releases')), $numberCell($catalogExport['releases_ativos'] ?? 0)]);
    $workbook .= $row([$cell($xl('pending_releases')), $numberCell($catalogExport['releases_pendentes'] ?? 0)]);
    $workbook .= $row([$cell($xl('inactive_clients')), $numberCell($catalogExport['clientes_inativos'] ?? 0)]);
    $workbook .= $row([$cell($xl('rejected_products')), $numberCell($catalogExport['produtos_rejeitados'] ?? 0)]);
    $workbook .= $row([$cell($xl('rejected_releases')), $numberCell($catalogExport['releases_rejeitados'] ?? 0)]);
    $workbook .= $row([$cell($xl('tracks_registered')), $numberCell($catalogExport['faixas_total'] ?? 0)]);
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart($xl('finance'));
    $workbook .= $titleRows($xl('finance'), $xl('paid_revenue') . ' + ' . $xl('commission'));
    $workbook .= $row([$cell($xl('period'), 'Header'), $cell($xl('paid_revenue'), 'Header'), $cell($xl('commission'), 'Header')]);
    foreach ($monthlyRevenue as $month) {
        $workbook .= $row([$cell($month['period_label']), $moneyCell($month['revenue']), $moneyCell($month['commission'])]);
    }
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart($xl('products'));
    $workbook .= $titleRows($xl('products'), $xl('complete_catalog'));
    $workbook .= $row([$cell('ID', 'Header'), $cell($xl('product'), 'Header'), $cell($xl('artist'), 'Header'), $cell($xl('category'), 'Header'), $cell($xl('price'), 'Header'), $cell($xl('vat'), 'Header'), $cell($xl('commission'), 'Header'), $cell($xl('stock'), 'Header'), $cell($xl('state'), 'Header'), $cell($xl('sizes'), 'Header')]);
    foreach ($productsExport as $product) {
        $workbook .= $row([
            $numberCell($product['idProduto']), $cell($product['nomeProduto']), $cell($product['artista']), $cell($product['nomeCategoria']),
            $moneyCell($product['precoAtual']), $numberCell($product['iva_percentual']), $numberCell($product['comissao_percentual']),
            $numberCell($product['stock_total']), $statusCell((string)$product['estado']),
            $cell($product['tamanhos'] ?: $xl('no_sizes'))
        ]);
    }
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart($xl('releases'));
    $workbook .= $titleRows($xl('releases'), $xl('tracks'));
    $workbook .= $row([$cell('ID', 'Header'), $cell($xl('title'), 'Header'), $cell($xl('artist'), 'Header'), $cell($xl('type'), 'Header'), $cell($xl('tracks'), 'Header'), $cell($xl('state'), 'Header'), $cell($xl('release_date'), 'Header'), $cell($xl('created_at'), 'Header'), $cell($xl('track_list'), 'Header')]);
    foreach ($releasesExport as $release) {
        $workbook .= $row([
            $numberCell($release['idRelease']), $cell($release['titulo']), $cell($release['artista']), $cell($release['tipo']),
            $numberCell($release['total_faixas']), $statusCell((string)$release['estado']),
            $cell($release['data_lancamento'] ?: '-'), $cell($release['criado_em']), $cell($release['faixas'] ?: '-')
        ]);
    }
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart($xl('users'));
    $workbook .= $titleRows($xl('users'), $xl('account_activity'));
    $workbook .= $row([$cell('ID', 'Header'), $cell($xl('name'), 'Header'), $cell($xl('email'), 'Header'), $cell($xl('phone'), 'Header'), $cell($xl('state'), 'Header'), $cell($xl('products'), 'Header'), $cell($xl('releases'), 'Header'), $cell($xl('orders_count'), 'Header'), $cell($xl('created_at'), 'Header')]);
    foreach ($usersExport as $user) {
        $workbook .= $row([
            $numberCell($user['idCliente']), $cell($user['nome']), $cell($user['email']), $cell($user['telefone'] ?: '-'), $statusCell((string)$user['estado']),
            $numberCell($user['total_produtos']), $numberCell($user['total_releases']), $numberCell($user['total_encomendas']), $cell($user['criado_em'])
        ]);
    }
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart($xl('order_items'));
    $workbook .= $titleRows($xl('order_items'), $xl('last_200_items'));
    $workbook .= $row([$cell($xl('orders_count'), 'Header'), $cell($xl('customer'), 'Header'), $cell($xl('product'), 'Header'), $cell($xl('artist'), 'Header'), $cell($xl('category'), 'Header'), $cell($xl('qty'), 'Header'), $cell($xl('price'), 'Header'), $cell('IVA', 'Header'), $cell($xl('commission'), 'Header'), $cell($xl('artist_value'), 'Header')]);
    foreach ($orderItemsExport as $item) {
        $workbook .= $row([
            $numberCell($item['idEncomenda']), $cell($item['cliente']), $cell($item['nome_produto']), $cell($item['artista']), $cell($item['categoria_nome']),
            $numberCell($item['quantidade']), $moneyCell($item['preco_unitario']), $moneyCell($item['iva_valor']), $moneyCell($item['comissao_valor']), $moneyCell($item['valor_artista'])
        ]);
    }
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart($xl('support'));
    $workbook .= $titleRows($xl('support'), $xl('status_operational'));
    $workbook .= $row([$cell($xl('type'), 'Header'), $cell('ID', 'Header'), $cell($xl('customer'), 'Header'), $cell($xl('email'), 'Header'), $cell($xl('subject_state'), 'Header'), $cell($xl('created_at'), 'Header'), $cell($xl('resolved_at'), 'Header'), $cell($xl('admin'), 'Header')]);
    foreach ($messagesExport as $message) {
        $workbook .= $row([$cell($xl('message')), $numberCell($message['idMensagem']), $cell($message['cliente']), $cell($message['email']), $cell($message['assunto'] . ' / ' . $statusLabel((string)$message['estado'])), $cell($message['criado_em']), $cell($message['respondido_em'] ?: '-'), $cell($message['admin_nome'] ?: '-')]);
    }
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart($xl('settings'));
    $workbook .= $titleRows($xl('settings'), 'configuracao_site');
    $workbook .= $row([$cell($xl('key'), 'Header'), $cell($xl('value'), 'Header'), $cell($xl('updated_at'), 'Header')]);
    foreach ($settingsExport as $setting) {
        $workbook .= $row([$cell($setting['chave_configuracao']), $cell($setting['valor_configuracao']), $cell($setting['atualizado_em'])]);
    }
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart($xl('top_artists'));
    $workbook .= $titleRows($xl('top_artists'), $xl('ranking_by_artist_value'));
    $workbook .= $row([$cell($xl('artist'), 'Header'), $cell($xl('orders_count'), 'Header'), $cell($xl('artist_value'), 'Header'), $cell($xl('commission'), 'Header')]);
    foreach ($topArtists as $artist) {
        $workbook .= $row([$cell($artist['nome']), $numberCell($artist['orders_count']), $moneyCell($artist['artist_total']), $moneyCell($artist['commission_total'])]);
    }
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart($xl('categories'));
    $workbook .= $titleRows($xl('categories'), $xl('commercial_performance'));
    $workbook .= $row([$cell($xl('category'), 'Header'), $cell($xl('items_sold'), 'Header'), $cell($xl('total'), 'Header')]);
    foreach ($categoryRevenue as $category) {
        $workbook .= $row([$cell($category['categoria_nome']), $numberCell($category['items_count']), $moneyCell($category['total_value'])]);
    }
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart($xl('recent_orders'));
    $workbook .= $titleRows($xl('recent_orders'), $xl('last_records'));
    $workbook .= $row([$cell('ID', 'Header'), $cell($xl('customer'), 'Header'), $cell($xl('state'), 'Header'), $cell($xl('payment'), 'Header'), $cell($xl('total'), 'Header')]);
    foreach ($recentOrdersExport as $order) {
        $workbook .= $row([
            $numberCell($order['idEncomenda']),
            $cell($order['cliente']),
            $statusCell((string)$order['estado_encomenda']),
            $cell($paymentStatusLabelExport((string)$order['estado_pagamento']) . ' / ' . payment_method_label((string)$order['metodo_pagamento'])),
            $moneyCell($order['total_final'])
        ]);
    }
    $workbook .= $sheetEnd();
    $workbook .= '</Workbook>';

    $filename = $xl('file') . '-' . date('Y-m-d') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $workbook;
    exit;
}

$chartTipLabels = current_lang() === 'en'
    ? ['paid' => 'Paid', 'commission' => 'Commission', 'artists' => 'Artists', 'blocked' => 'Blocked', 'items' => 'items', 'revenue' => 'Revenue']
    : ['paid' => 'Pago', 'commission' => 'Comissão', 'artists' => 'Artistas', 'blocked' => 'Bloqueado', 'items' => 'itens', 'revenue' => 'Receita'];

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <span class="admin-page-kicker" data-admin-t="reports_kicker">Analytics</span>
    <h2 data-admin-t="reports_title">Relatórios</h2>
    <p data-admin-t="reports_intro">Receita, categorias, artistas e exportacao executiva num so lugar.</p>
  </div>
  <div class="dash-v4-actions">
    <nav class="admin-range-pills" aria-label="Reports range">
      <?php foreach ($rangeLabels as $rangeKey => $rangeItem): ?>
        <a href="reports.php?range=<?= h($rangeKey) ?>" class="<?= $range === $rangeKey ? 'on' : '' ?>" data-admin-t="<?= h($rangeItem['key']) ?>"><?= h($rangeItem['label']) ?></a>
      <?php endforeach; ?>
    </nav>
    <a href="reports.php?export=excel&range=<?= h($range) ?>&lang=<?= h(current_lang()) ?>" class="btn btn-dark btn-sm" data-admin-export-link data-admin-t="reports_export_excel">Exportar Excel</a>
  </div>
</div>

<div class="stats-grid">
  <div class="stat"><div class="stat-val"><?= h(format_eur((float)($finance['paid_revenue'] ?? 0))) ?></div><div class="stat-lbl" data-admin-t="stat_paid_revenue">Receita paga</div></div>
  <div class="stat"><div class="stat-val"><?= h(format_eur((float)($finance['commission'] ?? 0))) ?></div><div class="stat-lbl" data-admin-t="stat_platform_commission">Comissão da plataforma</div></div>
  <div class="stat"><div class="stat-val"><?= h(format_eur((float)($finance['artist_value'] ?? 0))) ?></div><div class="stat-lbl" data-admin-t="stat_artist_base">Base para artistas</div></div>
  <div class="stat"><div class="stat-val"><?= h(format_eur((float)($finance['blocked_value'] ?? 0))) ?></div><div class="stat-lbl" data-admin-t="reports_blocked_value">Cancelado/reembolsado</div></div>
</div>

<section class="admin-report-grid">
  <article class="acard-box admin-donut-card">
    <div class="acard-box-head">
      <h4 data-admin-t="reports_income_breakdown">Receita detalhada</h4>
      <span class="admin-card-note" data-admin-t="reports_live_finance">Financas em direto</span>
    </div>
    <div class="admin-donut admin-chart-tip" style="<?= h($donutStyle) ?>" data-chart-tip="<?= h($chartTipLabels['paid'] . ': ' . format_eur((float)($finance['paid_revenue'] ?? 0)) . ' | ' . $chartTipLabels['commission'] . ': ' . format_eur((float)($finance['commission'] ?? 0)) . ' | ' . $chartTipLabels['artists'] . ': ' . format_eur((float)($finance['artist_value'] ?? 0)) . ' | ' . $chartTipLabels['blocked'] . ': ' . format_eur((float)($finance['blocked_value'] ?? 0))) ?>">
      <div>
        <strong><?= $breakdownTotal > 1 ? round(((float)($finance['paid_revenue'] ?? 0) / $breakdownTotal) * 100, 1) : 0 ?>%</strong>
        <span data-admin-t="reports_paid_short">pago</span>
      </div>
    </div>
    <div class="admin-donut-legend">
      <?php foreach ($incomeBreakdown as $item): ?>
        <div>
          <span style="background: <?= h($item['color']) ?>"></span>
          <strong data-admin-t="<?= h($item['tkey']) ?>"><?= h($item['label']) ?></strong>
          <em><?= h(format_eur($item['value'])) ?></em>
        </div>
      <?php endforeach; ?>
    </div>
  </article>

  <article class="acard-box admin-report-bars-card">
    <div class="acard-box-head">
      <h4 data-admin-t="reports_category_revenue">Receita por categoria</h4>
      <span class="admin-card-note" data-admin-t="reports_top_categories">Top categorias</span>
    </div>
    <?php if (!$categoryRevenue): ?>
      <p data-admin-t="reports_empty_categories">Ainda não existem vendas por categoria.</p>
    <?php else: ?>
      <div class="admin-bar-list">
        <?php foreach (array_slice($categoryRevenue, 0, 5) as $category): ?>
          <?php $width = $maxCategoryRevenue > 0 ? max(8, (int)round(((float)$category['total_value'] / $maxCategoryRevenue) * 100)) : 8; ?>
          <div class="admin-bar-row admin-chart-tip" data-chart-tip="<?= h($category['categoria_nome'] . ' | ' . (int)$category['items_count'] . ' ' . $chartTipLabels['items'] . ' | ' . format_eur((float)$category['total_value'])) ?>">
            <div class="admin-bar-row-head">
              <strong><?= h($category['categoria_nome']) ?></strong>
              <p><?= (int)$category['items_count'] ?> <span data-admin-t="orders_items">items</span></p>
            </div>
            <span><?= h(format_eur((float)$category['total_value'])) ?></span>
            <div class="admin-bar-track"><div style="width: <?= $width ?>%"></div></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </article>
</section>

<section class="acard-box">
  <div class="acard-box-head">
    <h4 data-admin-t="reports_money_chart">Grafico do dinheiro</h4>
    <span class="admin-card-note" data-admin-t="<?= h($rangeLabels[$range]['key']) ?>"><?= h($rangeLabels[$range]['label']) ?></span>
  </div>
  <?php if (!$monthlyRevenue): ?>
    <p data-admin-t="empty_monthly">Sem atividade suficiente para desenhar a evolucao mensal.</p>
  <?php else: ?>
    <div class="admin-chart admin-chart--money">
      <?php foreach ($monthlyRevenue as $month): ?>
        <?php
        $revenue = (float)$month['revenue'];
        $height = $maxMonthlyRevenue > 0 ? max(22, (int)round(($revenue / $maxMonthlyRevenue) * 180)) : 22;
        ?>
        <div class="admin-chart-col admin-chart-tip" data-chart-tip="<?= h($month['period_label'] . ' | ' . $chartTipLabels['revenue'] . ': ' . format_eur($revenue) . ' | ' . $chartTipLabels['commission'] . ': ' . format_eur((float)$month['commission'])) ?>">
          <span class="admin-chart-value"><?= h(format_eur($revenue)) ?></span>
          <div class="admin-chart-bar-wrap">
            <div class="admin-chart-bar" style="height: <?= $height ?>px"></div>
          </div>
          <strong><?= h($month['period_label']) ?></strong>
          <span><?= h(format_eur((float)$month['commission'])) ?> <span data-admin-t="stat_platform_commission">comissao</span></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<div id="reports-search" data-admin-search-scope>
<div class="dashboard-grid">
  <section class="acard-box">
    <div class="acard-box-head">
      <h4 data-admin-t="box_top_artists">Artistas com maior retorno</h4>
      <label class="sbar admin-section-search">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" data-admin-search="reports-search" placeholder="Pesquisar..." data-admin-tp="admin_search_placeholder">
      </label>
    </div>
    <?php if (!$topArtists): ?>
      <p data-admin-t="empty_top_artists">Ainda não existem vendas suficientes para calcular o ranking.</p>
    <?php else: ?>
      <div class="simple-list">
        <?php foreach ($topArtists as $artist): ?>
          <div class="simple-list-item">
            <div>
              <strong><?= h($artist['nome']) ?></strong>
              <p><?= (int)$artist['orders_count'] ?> <span data-admin-t="card_orders">encomendas</span></p>
            </div>
            <span><?= h(format_eur((float)$artist['artist_total'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
</div>

<?php include 'admin_footer.php'; ?>
