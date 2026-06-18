<?php
// Final delivery smoke checks for the Greenerry project.
// Run from the project root with: php tools/quality/smoke-check.php

$root = dirname(__DIR__, 2);
$failures = [];

function greenerry_check(bool $condition, string $message): void
{
    global $failures;
    echo ($condition ? '[OK] ' : '[FAIL] ') . $message . PHP_EOL;
    if (!$condition) {
        $failures[] = $message;
    }
}

function greenerry_join_path(string ...$parts): string
{
    return implode(DIRECTORY_SEPARATOR, $parts);
}

function greenerry_asset_path(string $root, array $folderParts, string $filename): ?string
{
    $normalized = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filename), DIRECTORY_SEPARATOR);
    if ($normalized === '' || str_contains($normalized, '..')) {
        return null;
    }

    if (str_starts_with($normalized, 'assets' . DIRECTORY_SEPARATOR)) {
        return greenerry_join_path($root, $normalized);
    }

    return greenerry_join_path(...array_merge([$root], $folderParts, [$normalized]));
}

require greenerry_join_path($root, 'includes', 'config.php');

$phpFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$lintOk = true;
foreach ($phpFiles as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)
        || str_contains($path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR)
        || str_contains($path, DIRECTORY_SEPARATOR . '.codex_')) {
        continue;
    }

    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    exec($command, $output, $code);
    if ($code !== 0) {
        $lintOk = false;
        echo implode(PHP_EOL, $output) . PHP_EOL;
    }
}
greenerry_check($lintOk, 'PHP files pass syntax lint');

$translationsPath = greenerry_join_path($root, 'assets', 'js', 'translations.json');
$translations = json_decode((string)file_get_contents($translationsPath), true);
greenerry_check(json_last_error() === JSON_ERROR_NONE && is_array($translations), 'translations.json is valid JSON');

$legacyAssetPattern = '/pap_(real_product|final_release|final_avatar|final_banner|preview|draft|avatar|banner)/i';
$rootSql = greenerry_join_path($root, 'greenerry.sql');
$anexoSql = greenerry_join_path($root, 'docs', 'PAP_ENTREGA', 'Anexo_Base_Dados', 'greenerry_base_dados_final.sql');
greenerry_check(
    is_file($rootSql) && is_file($anexoSql) && hash_file('sha256', $rootSql) === hash_file('sha256', $anexoSql),
    'root SQL export matches Anexo SQL export'
);

$sqlAssetsOk = true;
foreach ([$rootSql, $anexoSql] as $sqlPath) {
    if (is_file($sqlPath) && preg_match($legacyAssetPattern, (string)file_get_contents($sqlPath))) {
        $sqlAssetsOk = false;
        echo '[FAIL] Legacy asset references found in ' . basename($sqlPath) . PHP_EOL;
    }
}
greenerry_check($sqlAssetsOk, 'SQL exports use production-style asset references');

$requiredDocs = [
    greenerry_join_path($root, 'docs', 'PAP_ENTREGA', 'Relatorio_PAP_Greenerry_Srijan_Gautam_ATUALIZADO.docx'),
    greenerry_join_path($root, 'docs', 'PAP_ENTREGA', 'Manual_Tecnico_Greenerry_Srijan_Gautam.docx'),
];
foreach ($requiredDocs as $docPath) {
    greenerry_check(is_file($docPath) && filesize($docPath) > 1000, basename($docPath) . ' exists and is not empty');
}

$assetsDir = greenerry_join_path($root, 'assets');
$legacyAssetsOk = true;
$assetFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($assetsDir, FilesystemIterator::SKIP_DOTS)
);
foreach ($assetFiles as $file) {
    if ($file->isFile() && preg_match($legacyAssetPattern, $file->getFilename())) {
        $legacyAssetsOk = false;
        echo '[FAIL] Legacy asset filename: ' . $file->getPathname() . PHP_EOL;
    }
}
greenerry_check($legacyAssetsOk, 'asset filenames use production-style prefixes');

$mediaReferences = [
    ['table' => 'cliente', 'column' => 'foto', 'folder' => ['assets', 'img']],
    ['table' => 'cliente', 'column' => 'banner', 'folder' => ['assets', 'img']],
    ['table' => 'playlist', 'column' => 'capa', 'folder' => ['assets', 'img']],
    ['table' => 'produto_imagem', 'column' => 'ficheiro', 'folder' => ['assets', 'img']],
    ['table' => 'release_musical', 'column' => 'capa', 'folder' => ['assets', 'img']],
    ['table' => 'faixa', 'column' => 'ficheiro_audio', 'folder' => ['assets', 'audio']],
];

$mediaOk = true;
foreach ($mediaReferences as $reference) {
    $table = $reference['table'];
    $column = $reference['column'];
    $query = "SELECT `$column` AS filename
              FROM `$table`
              WHERE `$column` IS NOT NULL AND `$column` <> ''";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        $mediaOk = false;
        echo "[FAIL] Could not read {$table}.{$column}: " . mysqli_error($conn) . PHP_EOL;
        continue;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $filename = (string)$row['filename'];
        if (preg_match('/^(https?:)?\/\//i', $filename) || str_starts_with($filename, 'data:')) {
            continue;
        }

        $filePath = greenerry_asset_path($root, $reference['folder'], $filename);
        if ($filePath === null || !is_file($filePath)) {
            $mediaOk = false;
            echo "[FAIL] Missing media for {$table}.{$column}: {$filename}" . PHP_EOL;
        }
    }
}
greenerry_check($mediaOk, 'database media references exist on disk');

$scanColumns = [
    'cliente' => ['email', 'bio'],
    'encomenda' => ['observacoes', 'tracking_number'],
    'encomenda_mensagem' => ['mensagem'],
    'mensagem_admin' => ['assunto', 'mensagem'],
    'playlist' => ['nome', 'descricao'],
    'produto' => ['nomeProduto', 'descricaoProduto', 'marca'],
    'produto_review' => ['comentario'],
    'release_musical' => ['titulo', 'descricao'],
    'faixa' => ['titulo'],
    'morada_encomenda' => ['morada'],
    'pagamento' => ['referencia'],
    'notificacao' => ['titulo', 'mensagem'],
];

$contentOk = true;
foreach ($scanColumns as $table => $columns) {
    foreach ($columns as $column) {
        $sql = "SELECT `$column`, COUNT(*) AS total
                FROM `$table`
                WHERE `$column` REGEXP 'PAP|demo|Demo|demonstra|Demonstra|Avenida da PAP|PAP-PAY|PAP[0-9]'
                GROUP BY `$column`
                LIMIT 20";
        $result = mysqli_query($conn, $sql);
        if (!$result) {
            $contentOk = false;
            echo "[FAIL] Could not scan {$table}.{$column}: " . mysqli_error($conn) . PHP_EOL;
            continue;
        }

        while ($row = mysqli_fetch_assoc($result)) {
            if ($table === 'faixa' && $column === 'titulo' && $row[$column] === 'Demon (feat. Joanne Robertson)') {
                continue;
            }
            $contentOk = false;
            echo "[FAIL] Visible demo content in {$table}.{$column}: {$row[$column]}" . PHP_EOL;
        }
    }
}
greenerry_check($contentOk, 'visible database content has no leftover demo/PAP labels');

if ($failures) {
    echo PHP_EOL . 'Smoke check failed with ' . count($failures) . ' issue(s).' . PHP_EOL;
    exit(1);
}

echo PHP_EOL . 'All smoke checks passed.' . PHP_EOL;
