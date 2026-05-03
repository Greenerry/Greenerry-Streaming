<?php
require_once '../includes/config.php';
require_admin_login();

$finance = db_one(
    $conn,
    "SELECT
        COALESCE(SUM(CASE WHEN e.estado_pagamento = 'pago' AND e.estado_encomenda != 'cancelada' AND ei.estado_item != 'cancelado' THEN ei.total_linha END), 0) AS paid_revenue,
        COALESCE(SUM(CASE WHEN e.estado_pagamento = 'pago' AND e.estado_encomenda != 'cancelada' AND ei.estado_item != 'cancelado' THEN ei.comissao_valor END), 0) AS commission,
        COALESCE(SUM(CASE WHEN e.estado_pagamento = 'pago' AND e.estado_encomenda != 'cancelada' AND ei.estado_item != 'cancelado' THEN ei.valor_artista END), 0) AS artist_value,
        COALESCE(SUM(CASE WHEN e.estado_encomenda = 'cancelada' OR ei.estado_item = 'cancelado' OR e.estado_pagamento = 'reembolsado' THEN ei.total_linha END), 0) AS blocked_value
     FROM encomenda e
     JOIN encomenda_item ei ON ei.idEncomenda = e.idEncomenda"
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
     GROUP BY ei.categoria_nome
     ORDER BY total_value DESC"
);

$monthlyRevenue = db_all(
    $conn,
    "SELECT DATE_FORMAT(e.created_at, '%Y-%m') AS period_key,
            DATE_FORMAT(e.created_at, '%m/%Y') AS period_label,
            COALESCE(SUM(ei.total_linha), 0) AS revenue,
            COALESCE(SUM(ei.comissao_valor), 0) AS commission
     FROM encomenda e
     JOIN encomenda_item ei ON ei.idEncomenda = e.idEncomenda
     WHERE e.created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
       AND e.estado_pagamento = 'pago'
       AND e.estado_encomenda != 'cancelada'
       AND ei.estado_item != 'cancelado'
     GROUP BY DATE_FORMAT(e.created_at, '%Y-%m'), DATE_FORMAT(e.created_at, '%m/%Y')
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

if (($_GET['export'] ?? '') === 'excel') {
    $recentOrdersExport = db_all(
        $conn,
        "SELECT e.idEncomenda, c.nome AS cliente, e.estado_encomenda, e.estado_pagamento,
                e.metodo_pagamento, e.total_final, e.created_at
         FROM encomenda e
         JOIN cliente c ON c.idCliente = e.idCliente
         ORDER BY e.created_at DESC
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
            (SELECT COUNT(*) FROM mensagem_admin WHERE estado = 'aberta') AS mensagens_abertas,
            (SELECT COUNT(*) FROM pedido_reset_password WHERE estado IN ('pendente', 'em_analise')) AS resets_abertos"
    ) ?? [];

    $productsExport = db_all(
        $conn,
        "SELECT p.idProduto, p.nomeProduto, c.nome AS artista, cat.nomeCategoria,
                p.precoAtual, p.iva_percentual, p.comissao_percentual, p.stock_total,
                p.usa_tamanhos, p.estado, p.ativo, p.bloqueado_admin, p.created_at, p.aprovado_em,
                GROUP_CONCAT(CONCAT(t.etiqueta, ': ', pts.stock) ORDER BY t.ordem SEPARATOR ', ') AS tamanhos
         FROM produto p
         JOIN cliente c ON c.idCliente = p.idCliente
         JOIN categoria cat ON cat.idCategoria = p.idCategoria
         LEFT JOIN produto_tamanho_stock pts ON pts.idProduto = p.idProduto
         LEFT JOIN tamanho t ON t.idTamanho = pts.idTamanho
         GROUP BY p.idProduto
         ORDER BY p.created_at DESC"
    );

    $releasesExport = db_all(
        $conn,
        "SELECT r.idRelease, r.titulo, c.nome AS artista, r.tipo, r.estado, r.ativo,
                r.bloqueado_admin, r.data_lancamento, r.created_at, r.aprovado_em,
                COUNT(f.idFaixa) AS total_faixas,
                GROUP_CONCAT(f.titulo ORDER BY f.numero_faixa SEPARATOR ', ') AS faixas
         FROM release_musical r
         JOIN cliente c ON c.idCliente = r.idCliente
         LEFT JOIN faixa f ON f.idRelease = r.idRelease
         GROUP BY r.idRelease
         ORDER BY r.created_at DESC"
    );

    $usersExport = db_all(
        $conn,
        "SELECT c.idCliente, c.nome, c.email, c.telefone, c.estado, c.created_at,
                COUNT(DISTINCT p.idProduto) AS total_produtos,
                COUNT(DISTINCT r.idRelease) AS total_releases,
                COUNT(DISTINCT e.idEncomenda) AS total_encomendas
         FROM cliente c
         LEFT JOIN produto p ON p.idCliente = c.idCliente
         LEFT JOIN release_musical r ON r.idCliente = c.idCliente
         LEFT JOIN encomenda e ON e.idCliente = c.idCliente
         GROUP BY c.idCliente
         ORDER BY c.created_at DESC"
    );

    $orderItemsExport = db_all(
        $conn,
        "SELECT ei.idEncomenda, e.created_at, c.nome AS cliente, ei.nome_produto,
                art.nome AS artista, ei.categoria_nome, ei.quantidade, ei.preco_unitario,
                ei.subtotal_linha, ei.iva_valor, ei.comissao_valor, ei.valor_artista,
                ei.total_linha, ei.estado_item
         FROM encomenda_item ei
         JOIN encomenda e ON e.idEncomenda = ei.idEncomenda
         JOIN cliente c ON c.idCliente = e.idCliente
         JOIN cliente art ON art.idCliente = ei.idArtista
         ORDER BY e.created_at DESC, ei.idEncomendaItem DESC
         LIMIT 200"
    );

    $messagesExport = db_all(
        $conn,
        "SELECT m.idMensagem, c.nome AS cliente, c.email, m.assunto, m.estado,
                m.created_at, m.responded_at, a.nome AS admin_nome
         FROM mensagem_admin m
         JOIN cliente c ON c.idCliente = m.idCliente
         LEFT JOIN admin a ON a.idAdmin = m.idAdminResposta
         ORDER BY FIELD(m.estado, 'aberta', 'respondida', 'fechada'), m.created_at DESC"
    );

    $passwordExport = db_all(
        $conn,
        "SELECT pr.idPedidoReset, c.nome AS cliente, pr.email, pr.estado,
                pr.created_at, pr.resolved_at, a.nome AS admin_nome
         FROM pedido_reset_password pr
         JOIN cliente c ON c.idCliente = pr.idCliente
         LEFT JOIN admin a ON a.idAdmin = pr.idAdmin
         ORDER BY FIELD(pr.estado, 'pendente', 'em_analise', 'concluido', 'recusado'), pr.created_at DESC"
    );

    $settingsExport = db_all($conn, "SELECT setting_key, setting_value, updated_at FROM site_config ORDER BY setting_key ASC");

    $xml = static function ($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    };
    $cell = static function ($value, string $style = 'Text', string $type = 'String') use ($xml): string {
        if ($type === 'Number') {
            $value = number_format((float)$value, 2, '.', '');
        }
        return '<Cell ss:StyleID="' . $style . '"><Data ss:Type="' . $type . '">' . $xml($value) . '</Data></Cell>';
    };
    $row = static function (array $cells): string {
        return '<Row>' . implode('', $cells) . '</Row>' . "\n";
    };
    $moneyCell = static fn($value): string => $cell((float)$value, 'Money', 'Number');
    $numberCell = static fn($value): string => $cell((float)$value, 'Number', 'Number');
    $sheetStart = static function (string $name): string {
        return '<Worksheet ss:Name="' . htmlspecialchars($name, ENT_QUOTES | ENT_XML1, 'UTF-8') . '"><Table>'
            . '<Column ss:Width="80"/><Column ss:Width="190"/><Column ss:Width="150"/><Column ss:Width="130"/><Column ss:Width="130"/>'
            . '<Column ss:Width="130"/><Column ss:Width="130"/><Column ss:Width="150"/><Column ss:Width="160"/><Column ss:Width="220"/>';
    };
    $sheetEnd = static function (): string {
        return '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>2</SplitHorizontal><TopRowBottomPane>2</TopRowBottomPane><ActivePane>2</ActivePane></WorksheetOptions></Worksheet>';
    };

    $workbook = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<?mso-application progid="Excel.Sheet"?>' . "\n"
        . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
        . '<Styles>'
        . '<Style ss:ID="Title"><Font ss:Bold="1" ss:Size="18" ss:Color="#111827"/><Interior ss:Color="#E7EDF5" ss:Pattern="Solid"/></Style>'
        . '<Style ss:ID="Subtle"><Font ss:Color="#64748B"/></Style>'
        . '<Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#111827" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders></Style>'
        . '<Style ss:ID="Section"><Font ss:Bold="1" ss:Color="#111827"/><Interior ss:Color="#DDE7F2" ss:Pattern="Solid"/></Style>'
        . '<Style ss:ID="Text"><Alignment ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/></Borders></Style>'
        . '<Style ss:ID="Number"><NumberFormat ss:Format="0"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/></Borders></Style>'
        . '<Style ss:ID="Money"><NumberFormat ss:Format="#,##0.00 &quot;EUR&quot;"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/></Borders></Style>'
        . '<Style ss:ID="Good"><Font ss:Bold="1" ss:Color="#0F5132"/><Interior ss:Color="#D1E7DD" ss:Pattern="Solid"/></Style>'
        . '<Style ss:ID="Warn"><Font ss:Bold="1" ss:Color="#7A4E00"/><Interior ss:Color="#FFF3CD" ss:Pattern="Solid"/></Style>'
        . '</Styles>';

    $workbook .= $sheetStart('Resumo');
    $workbook .= $row([$cell('Greenerry - Relatorio Executivo', 'Title'), $cell(date('Y-m-d H:i'), 'Title')]);
    $workbook .= $row([$cell('Indicador', 'Header'), $cell('Valor', 'Header')]);
    $workbook .= $row([$cell('Receita paga'), $moneyCell($finance['paid_revenue'] ?? 0)]);
    $workbook .= $row([$cell('Comissao da plataforma'), $moneyCell($finance['commission'] ?? 0)]);
    $workbook .= $row([$cell('Base para artistas'), $moneyCell($finance['artist_value'] ?? 0)]);
    $workbook .= $row([$cell('Cancelado / reembolsado'), $moneyCell($finance['blocked_value'] ?? 0)]);
    $workbook .= $row([$cell('Clientes ativos'), $numberCell($catalogExport['clientes_ativos'] ?? 0)]);
    $workbook .= $row([$cell('Produtos ativos'), $numberCell($catalogExport['produtos_ativos'] ?? 0)]);
    $workbook .= $row([$cell('Produtos pendentes'), $numberCell($catalogExport['produtos_pendentes'] ?? 0)]);
    $workbook .= $row([$cell('Lancamentos ativos'), $numberCell($catalogExport['releases_ativos'] ?? 0)]);
    $workbook .= $row([$cell('Lancamentos pendentes'), $numberCell($catalogExport['releases_pendentes'] ?? 0)]);
    $workbook .= $row([$cell('Mensagens abertas'), $numberCell($catalogExport['mensagens_abertas'] ?? 0)]);
    $workbook .= $row([$cell('Clientes inativos / bloqueados'), $numberCell($catalogExport['clientes_inativos'] ?? 0)]);
    $workbook .= $row([$cell('Produtos rejeitados'), $numberCell($catalogExport['produtos_rejeitados'] ?? 0)]);
    $workbook .= $row([$cell('Lancamentos rejeitados'), $numberCell($catalogExport['releases_rejeitados'] ?? 0)]);
    $workbook .= $row([$cell('Faixas registadas'), $numberCell($catalogExport['faixas_total'] ?? 0)]);
    $workbook .= $row([$cell('Pedidos de reset abertos'), $numberCell($catalogExport['resets_abertos'] ?? 0)]);
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart('Receita mensal');
    $workbook .= $row([$cell('Receita mensal', 'Title'), $cell('Ultimos 6 meses', 'Title')]);
    $workbook .= $row([$cell('Periodo', 'Header'), $cell('Receita', 'Header'), $cell('Comissao', 'Header')]);
    foreach ($monthlyRevenue as $month) {
        $workbook .= $row([$cell($month['period_label']), $moneyCell($month['revenue']), $moneyCell($month['commission'])]);
    }
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart('Produtos');
    $workbook .= $row([$cell('Produtos e merch', 'Title'), $cell('Catalogo completo', 'Title')]);
    $workbook .= $row([$cell('ID', 'Header'), $cell('Produto', 'Header'), $cell('Artista', 'Header'), $cell('Categoria', 'Header'), $cell('Preco', 'Header'), $cell('IVA %', 'Header'), $cell('Comissao %', 'Header'), $cell('Stock', 'Header'), $cell('Estado', 'Header'), $cell('Tamanhos', 'Header')]);
    foreach ($productsExport as $product) {
        $workbook .= $row([
            $numberCell($product['idProduto']), $cell($product['nomeProduto']), $cell($product['artista']), $cell($product['nomeCategoria']),
            $moneyCell($product['precoAtual']), $numberCell($product['iva_percentual']), $numberCell($product['comissao_percentual']),
            $numberCell($product['stock_total']), $cell(order_status_label((string)$product['estado']) . ((int)$product['ativo'] === 1 ? ' / ativo' : ' / inativo')),
            $cell($product['tamanhos'] ?: 'Sem tamanhos')
        ]);
    }
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart('Lancamentos');
    $workbook .= $row([$cell('Lancamentos musicais', 'Title'), $cell('Releases e faixas', 'Title')]);
    $workbook .= $row([$cell('ID', 'Header'), $cell('Titulo', 'Header'), $cell('Artista', 'Header'), $cell('Tipo', 'Header'), $cell('Faixas', 'Header'), $cell('Estado', 'Header'), $cell('Lancamento', 'Header'), $cell('Criado em', 'Header'), $cell('Lista de faixas', 'Header')]);
    foreach ($releasesExport as $release) {
        $workbook .= $row([
            $numberCell($release['idRelease']), $cell($release['titulo']), $cell($release['artista']), $cell($release['tipo']),
            $numberCell($release['total_faixas']), $cell(order_status_label((string)$release['estado']) . ((int)$release['ativo'] === 1 ? ' / ativo' : ' / inativo')),
            $cell($release['data_lancamento'] ?: '-'), $cell($release['created_at']), $cell($release['faixas'] ?: '-')
        ]);
    }
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart('Utilizadores');
    $workbook .= $row([$cell('Utilizadores e artistas', 'Title'), $cell('Atividade por conta', 'Title')]);
    $workbook .= $row([$cell('ID', 'Header'), $cell('Nome', 'Header'), $cell('Email', 'Header'), $cell('Telefone', 'Header'), $cell('Estado', 'Header'), $cell('Produtos', 'Header'), $cell('Lancamentos', 'Header'), $cell('Encomendas', 'Header'), $cell('Criado em', 'Header')]);
    foreach ($usersExport as $user) {
        $workbook .= $row([
            $numberCell($user['idCliente']), $cell($user['nome']), $cell($user['email']), $cell($user['telefone'] ?: '-'), $cell(order_status_label((string)$user['estado'])),
            $numberCell($user['total_produtos']), $numberCell($user['total_releases']), $numberCell($user['total_encomendas']), $cell($user['created_at'])
        ]);
    }
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart('Itens encomenda');
    $workbook .= $row([$cell('Detalhe financeiro por item', 'Title'), $cell('Ultimos 200 itens', 'Title')]);
    $workbook .= $row([$cell('Encomenda', 'Header'), $cell('Cliente', 'Header'), $cell('Produto', 'Header'), $cell('Artista', 'Header'), $cell('Categoria', 'Header'), $cell('Qtd', 'Header'), $cell('Preco', 'Header'), $cell('IVA', 'Header'), $cell('Comissao', 'Header'), $cell('Valor artista', 'Header')]);
    foreach ($orderItemsExport as $item) {
        $workbook .= $row([
            $numberCell($item['idEncomenda']), $cell($item['cliente']), $cell($item['nome_produto']), $cell($item['artista']), $cell($item['categoria_nome']),
            $numberCell($item['quantidade']), $moneyCell($item['preco_unitario']), $moneyCell($item['iva_valor']), $moneyCell($item['comissao_valor']), $moneyCell($item['valor_artista'])
        ]);
    }
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart('Suporte');
    $workbook .= $row([$cell('Mensagens e pedidos', 'Title'), $cell('Estado operacional', 'Title')]);
    $workbook .= $row([$cell('Tipo', 'Header'), $cell('ID', 'Header'), $cell('Cliente', 'Header'), $cell('Email', 'Header'), $cell('Assunto/Estado', 'Header'), $cell('Criado em', 'Header'), $cell('Resolvido em', 'Header'), $cell('Admin', 'Header')]);
    foreach ($messagesExport as $message) {
        $workbook .= $row([$cell('Mensagem'), $numberCell($message['idMensagem']), $cell($message['cliente']), $cell($message['email']), $cell($message['assunto'] . ' / ' . order_status_label((string)$message['estado'])), $cell($message['created_at']), $cell($message['responded_at'] ?: '-'), $cell($message['admin_nome'] ?: '-')]);
    }
    foreach ($passwordExport as $request) {
        $workbook .= $row([$cell('Password reset'), $numberCell($request['idPedidoReset']), $cell($request['cliente']), $cell($request['email']), $cell(order_status_label((string)$request['estado'])), $cell($request['created_at']), $cell($request['resolved_at'] ?: '-'), $cell($request['admin_nome'] ?: '-')]);
    }
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart('Configuracoes');
    $workbook .= $row([$cell('Configuracoes do site', 'Title'), $cell('site_config', 'Title')]);
    $workbook .= $row([$cell('Chave', 'Header'), $cell('Valor', 'Header'), $cell('Atualizado em', 'Header')]);
    foreach ($settingsExport as $setting) {
        $workbook .= $row([$cell($setting['setting_key']), $cell($setting['setting_value']), $cell($setting['updated_at'])]);
    }
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart('Top artistas');
    $workbook .= $row([$cell('Artistas com maior retorno', 'Title'), $cell('Ranking por valor artista', 'Title')]);
    $workbook .= $row([$cell('Artista', 'Header'), $cell('Encomendas', 'Header'), $cell('Valor artista', 'Header'), $cell('Comissao', 'Header')]);
    foreach ($topArtists as $artist) {
        $workbook .= $row([$cell($artist['nome']), $numberCell($artist['orders_count']), $moneyCell($artist['artist_total']), $moneyCell($artist['commission_total'])]);
    }
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart('Categorias');
    $workbook .= $row([$cell('Receita por categoria', 'Title'), $cell('Performance comercial', 'Title')]);
    $workbook .= $row([$cell('Categoria', 'Header'), $cell('Itens vendidos', 'Header'), $cell('Total', 'Header')]);
    foreach ($categoryRevenue as $category) {
        $workbook .= $row([$cell($category['categoria_nome']), $numberCell($category['items_count']), $moneyCell($category['total_value'])]);
    }
    $workbook .= $sheetEnd();

    $workbook .= $sheetStart('Encomendas recentes');
    $workbook .= $row([$cell('Encomendas recentes', 'Title'), $cell('Ultimos registos', 'Title')]);
    $workbook .= $row([$cell('ID', 'Header'), $cell('Cliente', 'Header'), $cell('Estado', 'Header'), $cell('Pagamento', 'Header'), $cell('Total', 'Header')]);
    foreach ($recentOrdersExport as $order) {
        $workbook .= $row([
            $numberCell($order['idEncomenda']),
            $cell($order['cliente']),
            $cell(order_status_label((string)$order['estado_encomenda'])),
            $cell(payment_status_label((string)$order['estado_pagamento']) . ' / ' . payment_method_label((string)$order['metodo_pagamento'])),
            $moneyCell($order['total_final'])
        ]);
    }
    $workbook .= $sheetEnd();
    $workbook .= '</Workbook>';

    $filename = 'greenerry-relatorio-executivo-' . date('Y-m-d') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $workbook;
    exit;
}

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <h2 data-admin-t="reports_title">Relatorios</h2>
  </div>
  <a href="reports.php?export=excel" class="btn btn-dark btn-sm" data-admin-t="reports_export_excel">Exportar Excel</a>
</div>

<div class="stats-grid">
  <div class="stat"><div class="stat-val"><?= h(format_eur((float)($finance['paid_revenue'] ?? 0))) ?></div><div class="stat-lbl" data-admin-t="stat_paid_revenue">Receita paga</div></div>
  <div class="stat"><div class="stat-val"><?= h(format_eur((float)($finance['commission'] ?? 0))) ?></div><div class="stat-lbl" data-admin-t="stat_platform_commission">Comissao da plataforma</div></div>
  <div class="stat"><div class="stat-val"><?= h(format_eur((float)($finance['artist_value'] ?? 0))) ?></div><div class="stat-lbl" data-admin-t="stat_artist_base">Base para artistas</div></div>
  <div class="stat"><div class="stat-val"><?= h(format_eur((float)($finance['blocked_value'] ?? 0))) ?></div><div class="stat-lbl" data-admin-t="reports_blocked_value">Cancelado/reembolsado</div></div>
</div>

<section class="acard-box">
  <div class="acard-box-head">
    <h4 data-admin-t="reports_money_chart">Grafico do dinheiro</h4>
    <span class="admin-card-note" data-admin-t="box_last_six_months">Ultimos 6 meses</span>
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
        <div class="admin-chart-col">
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

<div class="admin-search-row">
  <label class="sbar">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="search" data-admin-search="reports-search" placeholder="Pesquisar..." data-admin-tp="admin_search_placeholder">
  </label>
</div>

<div id="reports-search" data-admin-search-scope>
<div class="dashboard-grid">
  <section class="acard-box">
    <div class="acard-box-head">
      <h4 data-admin-t="box_top_artists">Artistas com maior retorno</h4>
    </div>
    <?php if (!$topArtists): ?>
      <p data-admin-t="empty_top_artists">Ainda nao existem vendas suficientes para calcular o ranking.</p>
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

  <section class="acard-box">
    <div class="acard-box-head">
      <h4 data-admin-t="reports_category_revenue">Receita por categoria</h4>
    </div>
    <?php if (!$categoryRevenue): ?>
      <p data-admin-t="reports_empty_categories">Ainda nao existem vendas por categoria.</p>
    <?php else: ?>
      <div class="admin-bar-list">
        <?php foreach ($categoryRevenue as $category): ?>
          <?php $width = $maxCategoryRevenue > 0 ? max(8, (int)round(((float)$category['total_value'] / $maxCategoryRevenue) * 100)) : 8; ?>
          <div class="admin-bar-row">
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
  </section>
</div>
</div>

<?php include 'admin_footer.php'; ?>
