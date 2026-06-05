<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/config.php';

mysqli_set_charset($conn, 'utf8mb4');

$imgDir = $root . '/assets/img';
if (!is_dir($imgDir)) {
    mkdir($imgDir, 0777, true);
}

function seed_slug(string $value): string
{
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = strtolower($ascii !== false ? $ascii : $value);
    $value = preg_replace('/[^a-z0-9]+/', '', $value) ?: 'artist';
    return $value;
}

function seed_sql(mysqli $conn, string $sql): void
{
    if (!mysqli_query($conn, $sql)) {
        throw new RuntimeException(mysqli_error($conn) . "\nSQL: " . $sql);
    }
}

function seed_prepared(mysqli $conn, string $sql, string $types, array $values): mysqli_stmt
{
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new RuntimeException(mysqli_error($conn) . "\nSQL: " . $sql);
    }
    if ($types !== '') {
        if (strlen($types) !== count($values)) {
            throw new RuntimeException('Bind mismatch: ' . strlen($types) . ' types for ' . count($values) . " values\nSQL: " . $sql);
        }
        mysqli_stmt_bind_param($stmt, $types, ...$values);
    }
    if (!mysqli_stmt_execute($stmt)) {
        throw new RuntimeException(mysqli_stmt_error($stmt) . "\nSQL: " . $sql);
    }
    return $stmt;
}

function seed_one(mysqli $conn, string $sql): ?array
{
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        throw new RuntimeException(mysqli_error($conn) . "\nSQL: " . $sql);
    }
    $row = mysqli_fetch_assoc($result);
    return $row ?: null;
}

function seed_insert_id(mysqli $conn): int
{
    return (int)mysqli_insert_id($conn);
}

function seed_color(string $hex): array
{
    $hex = ltrim($hex, '#');
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

function seed_alloc(GdImage $img, string $hex): int
{
    [$r, $g, $b] = seed_color($hex);
    return imagecolorallocate($img, $r, $g, $b);
}

function seed_remote_bytes(string $url): ?string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_USERAGENT => 'Greenerry PAP demo data seeder',
    ]);
    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return is_string($body) && $status >= 200 && $status < 300 ? $body : null;
}

function seed_fit_image(GdImage $src, int $w, int $h): GdImage
{
    $sw = imagesx($src);
    $sh = imagesy($src);
    $scale = max($w / max(1, $sw), $h / max(1, $sh));
    $nw = (int)ceil($sw * $scale);
    $nh = (int)ceil($sh * $scale);
    $tmp = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($tmp, $src, 0, 0, 0, 0, $nw, $nh, $sw, $sh);
    $out = imagecreatetruecolor($w, $h);
    imagecopy($out, $tmp, (int)(($w - $nw) / 2), (int)(($h - $nh) / 2), 0, 0, $nw, $nh);
    imagedestroy($tmp);
    return $out;
}

function seed_text(GdImage $img, int $x, int $y, string $text, int $color, int $size = 5): void
{
    imagestring($img, $size, $x, $y, $text, $color);
}

function seed_generated_portrait(string $file, string $name, array $palette): void
{
    global $imgDir;
    $img = imagecreatetruecolor(720, 720);
    $bg = seed_alloc($img, $palette[0]);
    $accent = seed_alloc($img, $palette[1]);
    $light = seed_alloc($img, '#f7f7f2');
    imagefilledrectangle($img, 0, 0, 720, 720, $bg);
    imagefilledellipse($img, 540, 130, 420, 420, $accent);
    imagefilledellipse($img, 190, 590, 560, 360, $accent);
    $initials = implode('', array_map(static fn($p) => mb_substr($p, 0, 1), array_slice(explode(' ', $name), 0, 2)));
    seed_text($img, 244, 310, strtoupper($initials), $light, 5);
    imagejpeg($img, $imgDir . '/' . $file, 92);
    imagedestroy($img);
}

function seed_wiki_portrait(string $wikiTitle, string $file, string $fallbackName, array $palette): void
{
    global $imgDir;
    $url = 'https://en.wikipedia.org/api/rest_v1/page/summary/' . rawurlencode($wikiTitle);
    $json = seed_remote_bytes($url);
    $data = $json ? json_decode($json, true) : null;
    $imageUrl = $data['thumbnail']['source'] ?? $data['originalimage']['source'] ?? '';
    $bytes = is_string($imageUrl) && $imageUrl !== '' ? seed_remote_bytes($imageUrl) : null;
    $src = $bytes ? @imagecreatefromstring($bytes) : false;
    if (!$src instanceof GdImage) {
        seed_generated_portrait($file, $fallbackName, $palette);
        return;
    }
    $out = seed_fit_image($src, 720, 720);
    imagejpeg($out, $imgDir . '/' . $file, 92);
    imagedestroy($src);
    imagedestroy($out);
}

function seed_banner(string $file, string $name, string $avatarFile, array $palette): void
{
    global $imgDir;
    $img = imagecreatetruecolor(1600, 640);
    $c1 = seed_color($palette[0]);
    $c2 = seed_color($palette[1]);
    for ($x = 0; $x < 1600; $x++) {
        $ratio = $x / 1599;
        $r = (int)round($c1[0] * (1 - $ratio) + $c2[0] * $ratio);
        $g = (int)round($c1[1] * (1 - $ratio) + $c2[1] * $ratio);
        $b = (int)round($c1[2] * (1 - $ratio) + $c2[2] * $ratio);
        imageline($img, $x, 0, $x, 640, imagecolorallocate($img, $r, $g, $b));
    }
    $dark = imagecolorallocatealpha($img, 0, 0, 0, 55);
    imagefilledrectangle($img, 0, 0, 1600, 640, $dark);
    $accent = seed_alloc($img, $palette[2]);
    imagefilledellipse($img, 1240, 250, 760, 760, $accent);
    $avatar = @imagecreatefromjpeg($imgDir . '/' . $avatarFile);
    if ($avatar instanceof GdImage) {
        $fit = seed_fit_image($avatar, 440, 440);
        imagecopy($img, $fit, 980, 104, 0, 0, 440, 440);
        imagedestroy($fit);
        imagedestroy($avatar);
    }
    $white = seed_alloc($img, '#ffffff');
    seed_text($img, 88, 250, strtoupper($name), $white, 5);
    seed_text($img, 92, 292, 'GREENERRY ARTIST PROFILE', $white, 3);
    imagejpeg($img, $imgDir . '/' . $file, 92);
    imagedestroy($img);
}

function seed_product_image(string $file, string $artist, string $name, string $category, int $variant, array $palette): void
{
    global $imgDir;
    $img = imagecreatetruecolor(980, 980);
    imagefilledrectangle($img, 0, 0, 980, 980, seed_alloc($img, '#f4f1ea'));
    imagefilledrectangle($img, 42, 42, 938, 938, seed_alloc($img, '#12161f'));
    imagefilledrectangle($img, 78, 78, 902, 902, seed_alloc($img, $palette[$variant % count($palette)]));
    $ink = seed_alloc($img, '#111111');
    $paper = seed_alloc($img, '#ffffff');
    $muted = seed_alloc($img, '#d8dee8');

    $cat = strtolower($category);
    if (str_contains($cat, 'shirt')) {
        imagefilledrectangle($img, 320, 260, 660, 760, $paper);
        imagefilledpolygon($img, [250, 285, 320, 260, 345, 405, 275, 430], 4, $paper);
        imagefilledpolygon($img, [660, 260, 730, 285, 705, 430, 635, 405], 4, $paper);
        seed_text($img, 376, 450, strtoupper(substr($artist, 0, 18)), $ink, 5);
    } elseif (str_contains($cat, 'hoodie')) {
        imagefilledrectangle($img, 300, 340, 680, 765, $paper);
        imagefilledellipse($img, 490, 330, 210, 170, $paper);
        imagearc($img, 490, 338, 150, 112, 0, 180, $ink);
        seed_text($img, 380, 510, strtoupper(substr($artist, 0, 16)), $ink, 5);
    } elseif (str_contains($cat, 'vinil')) {
        imagefilledellipse($img, 490, 470, 560, 560, $ink);
        imagefilledellipse($img, 490, 470, 170, 170, $paper);
        imagefilledellipse($img, 490, 470, 36, 36, $ink);
        seed_text($img, 332, 785, 'VINIL EDITION', $paper, 5);
    } elseif (str_contains($cat, 'cd')) {
        imagefilledellipse($img, 490, 455, 510, 510, $paper);
        imagefilledellipse($img, 490, 455, 122, 122, seed_alloc($img, $palette[1]));
        imagerectangle($img, 270, 715, 710, 805, $paper);
        seed_text($img, 385, 750, 'COMPACT DISC', $paper, 5);
    } elseif (str_contains($cat, 'poster')) {
        imagefilledrectangle($img, 255, 170, 725, 800, $paper);
        imagefilledrectangle($img, 285, 200, 695, 770, seed_alloc($img, $palette[2]));
        seed_text($img, 348, 455, strtoupper(substr($artist, 0, 16)), $ink, 5);
    } else {
        imagefilledellipse($img, 490, 460, 430, 300, $paper);
        imagefilledrectangle($img, 340, 440, 640, 700, $paper);
        seed_text($img, 380, 540, strtoupper(substr($name, 0, 18)), $ink, 4);
    }

    seed_text($img, 96, 104, strtoupper($artist), $paper, 5);
    seed_text($img, 96, 146, strtoupper($name), $muted, 4);
    seed_text($img, 96, 842, strtoupper($category) . ' / GREENERRY', $paper, 4);
    imagepng($img, $imgDir . '/' . $file, 7);
    imagedestroy($img);
}

function seed_release_cover(string $file, string $artist, string $title, array $palette): void
{
    global $imgDir;
    $img = imagecreatetruecolor(900, 900);
    imagefilledrectangle($img, 0, 0, 900, 900, seed_alloc($img, $palette[0]));
    imagefilledellipse($img, 680, 190, 560, 560, seed_alloc($img, $palette[1]));
    imagefilledellipse($img, 180, 760, 620, 360, seed_alloc($img, $palette[2]));
    imagefilledrectangle($img, 80, 80, 820, 820, imagecolorallocatealpha($img, 0, 0, 0, 78));
    $white = seed_alloc($img, '#ffffff');
    seed_text($img, 120, 390, strtoupper(substr($title, 0, 24)), $white, 5);
    seed_text($img, 122, 435, strtoupper(substr($artist, 0, 24)), $white, 4);
    imagejpeg($img, $imgDir . '/' . $file, 92);
    imagedestroy($img);
}

$artists = [
    ['name' => 'The Weeknd', 'email' => 'theweeknd@gmail.com', 'wiki' => 'The Weeknd', 'bio' => 'Pop alternativo, R&B e universos visuais cinematograficos.', 'palette' => ['#111827', '#44516f', '#d24b4b']],
    ['name' => 'Rihanna', 'email' => 'rihanna@gmail.com', 'wiki' => 'Rihanna', 'bio' => 'Pop, R&B e atitude global com identidade forte.', 'palette' => ['#4a1022', '#c3406d', '#f5c0c9']],
    ['name' => 'Justin Bieber', 'email' => 'justinbieber@gmail.com', 'wiki' => 'Justin Bieber', 'bio' => 'Pop melódico com faixas reconhecidas internacionalmente.', 'palette' => ['#d9d7ce', '#64748b', '#111827']],
    ['name' => 'ZAYN', 'email' => 'zayn@gmail.com', 'wiki' => 'Zayn Malik', 'bio' => 'R&B pop com ambiente noturno e vocal expressivo.', 'palette' => ['#171717', '#6b7280', '#d4af37']],
    ['name' => 'Childish Gambino', 'email' => 'childishgambino@gmail.com', 'wiki' => 'Donald Glover', 'bio' => 'Rap, funk e soul com uma abordagem visual muito própria.', 'palette' => ['#2f1b12', '#c06c35', '#f2d49b']],
    ['name' => 'A$AP Rocky', 'email' => 'asaprocky@gmail.com', 'wiki' => 'ASAP Rocky', 'bio' => 'Hip-hop, moda e direção criativa com estética urbana.', 'palette' => ['#111111', '#a51f2d', '#d9d9d9']],
    ['name' => 'Cocteau Twins', 'email' => 'cocteautwins@gmail.com', 'wiki' => 'Cocteau Twins', 'bio' => 'Dream pop atmosférico, texturas etéreas e guitarras brilhantes.', 'palette' => ['#28384d', '#8aa6c1', '#f4d2df']],
    ['name' => 'PARTYNEXTDOOR', 'email' => 'partynextdoor@gmail.com', 'wiki' => 'PartyNextDoor', 'bio' => 'R&B noturno com produção minimalista e quente.', 'palette' => ['#120d18', '#51406a', '#d18b5f']],
    ['name' => 'Björk', 'email' => 'bjork@gmail.com', 'wiki' => 'Björk', 'bio' => 'Pop experimental, eletrónica orgânica e mundos visuais únicos.', 'palette' => ['#26342f', '#85b79d', '#f2b5d4']],
    ['name' => 'Bladee', 'email' => 'bladee@gmail.com', 'wiki' => 'Bladee', 'bio' => 'Cloud rap, estética digital e melodias melancólicas.', 'palette' => ['#dfe7f2', '#8ba5c8', '#63738f']],
    ['name' => 'Dean Blunt', 'email' => 'deanblunt@gmail.com', 'wiki' => 'Dean Blunt', 'bio' => 'Música alternativa crua, minimalista e imprevisível.', 'palette' => ['#1f2933', '#6f6a62', '#c4b79d']],
    ['name' => 'JPEGMAFIA', 'email' => 'jpegmafia@gmail.com', 'wiki' => 'JPEGMAFIA', 'bio' => 'Rap experimental, energia intensa e produção disruptiva.', 'palette' => ['#111111', '#ec4899', '#38bdf8']],
    ['name' => 'Frank Ocean', 'email' => 'frankocean@gmail.com', 'wiki' => 'Frank Ocean', 'bio' => 'R&B autoral, escrita íntima e composição sofisticada.', 'palette' => ['#153f3a', '#7dd3c7', '#f3e8d0']],
    ['name' => 'Tyler, The Creator', 'email' => 'tylerthecreator@gmail.com', 'wiki' => 'Tyler, the Creator', 'bio' => 'Rap, jazz e design visual com personalidade muito marcada.', 'palette' => ['#5f2b2b', '#e7a85c', '#f3d9a4']],
    ['name' => 'SZA', 'email' => 'sza@gmail.com', 'wiki' => 'SZA', 'bio' => 'R&B contemporâneo, escrita emocional e atmosferas suaves.', 'palette' => ['#0f3d3e', '#6fb6a7', '#f4d0ba']],
    ['name' => 'Lana Del Rey', 'email' => 'lanadelrey@gmail.com', 'wiki' => 'Lana Del Rey', 'bio' => 'Pop cinematográfico, nostalgia americana e melodias dramáticas.', 'palette' => ['#243447', '#b8c5d6', '#d8a48f']],
    ['name' => 'Charli XCX', 'email' => 'charlixcx@gmail.com', 'wiki' => 'Charli XCX', 'bio' => 'Pop futurista, club music e estética hiperativa.', 'palette' => ['#111827', '#39ff88', '#f72585']],
    ['name' => 'FKA twigs', 'email' => 'fkatwigs@gmail.com', 'wiki' => 'FKA twigs', 'bio' => 'Pop experimental, dança, performance e produção delicada.', 'palette' => ['#251a27', '#9d6b8f', '#f0c4d9']],
    ['name' => 'Travis Scott', 'email' => 'travisscott@gmail.com', 'wiki' => 'Travis Scott', 'bio' => 'Hip-hop atmosférico, energia de palco e mundo visual imersivo.', 'palette' => ['#21150f', '#b45309', '#facc15']],
    ['name' => 'Beyoncé', 'email' => 'beyonce@gmail.com', 'wiki' => 'Beyoncé', 'bio' => 'Pop e R&B de escala global, performance e voz icónica.', 'palette' => ['#20140c', '#b0894f', '#f3dfb4']],
];

$customers = [
    ['Mafalda Silva', 'mafalda.silva@gmail.com'],
    ['Ines Rocha', 'ines.rocha@gmail.com'],
    ['Tiago Martins', 'tiago.martins@gmail.com'],
    ['Beatriz Costa', 'beatriz.costa@gmail.com'],
    ['Diogo Ferreira', 'diogo.ferreira@gmail.com'],
    ['Leonor Santos', 'leonor.santos@gmail.com'],
    ['Rafael Almeida', 'rafael.almeida@gmail.com'],
    ['Carolina Neves', 'carolina.neves@gmail.com'],
];

$passwordHash = password_hash('12345678', PASSWORD_DEFAULT);

mysqli_begin_transaction($conn);
try {
    seed_sql($conn, 'SET FOREIGN_KEY_CHECKS=0');
    foreach (['produto_review', 'encomenda_mensagem', 'morada_encomenda', 'encomenda_item', 'encomenda', 'produto_tamanho_stock', 'produto_imagem', 'produto', 'faixa_listen', 'favorito_musica', 'seguir_artista', 'mensagem_admin', 'notificacao'] as $table) {
        seed_sql($conn, "DELETE FROM {$table}");
    }
    seed_sql($conn, "DELETE FROM release_musical WHERE idCliente > 5 AND idRelease NOT IN (8,9,10,11,12,13,14,15,16,17,22,24)");
    seed_sql($conn, "DELETE FROM cliente WHERE idCliente > 5");

    seed_sql($conn, "UPDATE cliente SET estado='inativo' WHERE idCliente <> 5");
    seed_sql($conn, "UPDATE release_musical SET ativo=0, estado='inativo' WHERE idCliente <> 5");
    seed_sql($conn, "UPDATE faixa f JOIN release_musical r ON r.idRelease = f.idRelease SET f.ativo=0, f.estado='inativa' WHERE r.idCliente <> 5");
    seed_sql($conn, "DELETE f FROM faixa f JOIN release_musical r ON r.idRelease = f.idRelease WHERE r.idCliente = 5 AND f.ficheiro_audio = ''");
    seed_sql($conn, "DELETE FROM release_musical WHERE idCliente = 5 AND idRelease NOT IN (27, 28, 29)");
    seed_sql($conn, "UPDATE release_musical SET estado='aprovado', ativo=1, motivo_rejeicao=NULL, idAdminAprovacao=1, aprovado_em=NOW() WHERE idCliente=5 AND idRelease IN (27,28,29)");
    seed_sql($conn, "UPDATE faixa f JOIN release_musical r ON r.idRelease=f.idRelease SET f.estado='aprovada', f.ativo=1 WHERE r.idCliente=5 AND r.idRelease IN (27,28,29)");

    $artistIds = [];
    $reuseIds = [2, 3, 4];
    foreach ($artists as $index => $artist) {
        $slug = seed_slug($artist['name']);
        $avatar = 'pap_avatar_' . $slug . '.jpg';
        $banner = 'pap_banner_' . $slug . '.jpg';
        seed_wiki_portrait($artist['wiki'], $avatar, $artist['name'], $artist['palette']);
        seed_banner($banner, $artist['name'], $avatar, $artist['palette']);

        if (isset($reuseIds[$index])) {
            $id = $reuseIds[$index];
            seed_prepared(
                $conn,
                "UPDATE cliente SET nome=?, email=?, palavra_passe=?, telefone=?, foto=?, banner=?, bio=?, slug=?, estado='ativo' WHERE idCliente=?",
                'ssssssssi',
                [$artist['name'], $artist['email'], $passwordHash, '+351 900 000 ' . str_pad((string)$id, 3, '0', STR_PAD_LEFT), $avatar, $banner, $artist['bio'], $slug, $id]
            );
        } else {
            seed_prepared(
                $conn,
                "INSERT INTO cliente (nome, email, palavra_passe, telefone, foto, banner, bio, slug, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ativo')",
                'ssssssss',
                [$artist['name'], $artist['email'], $passwordHash, '+351 900 000 ' . str_pad((string)($index + 10), 3, '0', STR_PAD_LEFT), $avatar, $banner, $artist['bio'], $slug]
            );
            $id = seed_insert_id($conn);
        }
        $artistIds[$artist['name']] = $id;
    }

    seed_prepared(
        $conn,
        "UPDATE cliente SET nome='Green', email='green@gmail.com', palavra_passe=?, telefone='+351 926 275 311', bio='Projeto pessoal de música independente, criado para a PAP Greenerry.', slug='green', estado='ativo' WHERE idCliente=5",
        's',
        [$passwordHash]
    );
    $greenAvatar = 'pap_avatar_green.jpg';
    $greenBanner = 'pap_banner_green.jpg';
    seed_generated_portrait($greenAvatar, 'Green', ['#d8c96d', '#1f2937', '#f9fafb']);
    seed_banner($greenBanner, 'Green', $greenAvatar, ['#101418', '#d8c96d', '#f7f7f2']);
    seed_prepared($conn, "UPDATE cliente SET foto=?, banner=? WHERE idCliente=5", 'ss', [$greenAvatar, $greenBanner]);
    $artistIds['Green'] = 5;

    foreach ($customers as $i => $customer) {
        [$name, $email] = $customer;
        $slug = seed_slug($name);
        $avatar = 'pap_avatar_' . $slug . '.jpg';
        $banner = 'pap_banner_' . $slug . '.jpg';
        seed_generated_portrait($avatar, $name, ['#273244', '#64748b', '#f8fafc']);
        seed_banner($banner, $name, $avatar, ['#111827', '#334155', '#94a3b8']);
        seed_prepared(
            $conn,
            "INSERT INTO cliente (nome, email, palavra_passe, telefone, foto, banner, bio, slug, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ativo')",
            'ssssssss',
            [$name, $email, $passwordHash, '+351 910 100 ' . str_pad((string)$i, 3, '0', STR_PAD_LEFT), $avatar, $banner, 'Conta de cliente usada para testar compras, avaliações e mensagens.', $slug]
        );
    }

    $legacyReleaseData = [
        8 => ['artist' => 'Bladee', 'title' => '7 Eleven', 'type' => 'Single', 'cover' => 'release_2_ea9c3b7ed59a3305.jpg', 'date' => '2025-11-08'],
        9 => ['artist' => 'Dean Blunt', 'title' => 'Dean Blunt', 'type' => 'EP', 'cover' => 'release_2_1e3df069c2c12795.jpg', 'date' => '2025-11-09'],
        10 => ['artist' => 'Dean Blunt', 'title' => 'Black Pleasure 2012', 'type' => 'EP', 'cover' => 'release_2_43a366a25be88d12.jpg', 'date' => '2025-11-10'],
        11 => ['artist' => 'Bladee', 'title' => 'Peroxide', 'type' => 'Single', 'cover' => 'release_2_6b21fd4808398888.jpg', 'date' => '2025-11-11'],
        12 => ['artist' => 'Frank Ocean', 'title' => 'Unforgettable', 'type' => 'Single', 'cover' => 'release_3_92eb60f8f1d2b370.jpg', 'date' => '2025-11-12'],
        13 => ['artist' => 'Björk', 'title' => 'Big Time Sensuality', 'type' => 'Single', 'cover' => 'release_3_c38afbb8024d5eb8.jpg', 'date' => '2025-11-13'],
        14 => ['artist' => 'PARTYNEXTDOOR', 'title' => 'Break from Toronto', 'type' => 'Single', 'cover' => 'release_3_1ea208794c55a4c9.png', 'date' => '2025-11-14'],
        15 => ['artist' => 'A$AP Rocky', 'title' => 'Fashion Killa', 'type' => 'Single', 'cover' => 'release_3_cdf541046e5302e3.jpg', 'date' => '2025-11-15'],
        16 => ['artist' => 'SZA', 'title' => 'Sure Thing', 'type' => 'Single', 'cover' => 'release_3_4b5ad7d4e60a0e13.jpg', 'date' => '2025-11-16'],
        17 => ['artist' => 'PARTYNEXTDOOR', 'title' => 'Say it', 'type' => 'Single', 'cover' => 'release_3_ce6da3ff76b2ce4f.jpg', 'date' => '2025-11-17'],
        22 => ['artist' => 'Childish Gambino', 'title' => '3005', 'type' => 'Single', 'cover' => 'release_3_ad30f741db104eed.jpg', 'date' => '2025-11-22'],
        24 => ['artist' => 'ZAYN', 'title' => 'PILLOWTALK', 'type' => 'Single', 'cover' => 'release_3_69564da8adbe9b45.jpg', 'date' => '2025-11-24'],
    ];
    foreach ($legacyReleaseData as $releaseId => $release) {
        if (seed_one($conn, "SELECT idRelease FROM release_musical WHERE idRelease = {$releaseId} LIMIT 1")) {
            continue;
        }
        $targetArtist = $artistIds[$release['artist']] ?? 5;
        seed_prepared(
            $conn,
            "INSERT INTO release_musical (idRelease, idCliente, titulo, tipo, descricao, capa, data_lancamento, estado, ativo, idAdminAprovacao, aprovado_em) VALUES (?, ?, ?, ?, ?, ?, ?, 'aprovado', 1, 1, NOW())",
            'iisssss',
            [$releaseId, $targetArtist, $release['title'], $release['type'], 'Lançamento ligado aos ficheiros áudio existentes da demo Greenerry.', $release['cover'], $release['date']]
        );
    }

    $releaseMap = [
        22 => 'Childish Gambino',
        23 => 'Justin Bieber',
        24 => 'ZAYN',
        25 => 'The Weeknd',
        26 => 'Rihanna',
        15 => 'A$AP Rocky',
        14 => 'PARTYNEXTDOOR',
        13 => 'Björk',
        16 => 'SZA',
        17 => 'PARTYNEXTDOOR',
        8 => 'Bladee',
        9 => 'Dean Blunt',
        10 => 'Dean Blunt',
        11 => 'Bladee',
        12 => 'Frank Ocean',
    ];
    foreach ($releaseMap as $releaseId => $artistName) {
        if (!isset($artistIds[$artistName])) {
            continue;
        }
        seed_prepared($conn, "UPDATE release_musical SET idCliente=?, estado='aprovado', ativo=1, motivo_rejeicao=NULL, idAdminAprovacao=1, aprovado_em=NOW() WHERE idRelease=?", 'ii', [$artistIds[$artistName], $releaseId]);
        seed_prepared($conn, "UPDATE faixa SET estado='aprovada', ativo=1 WHERE idRelease=?", 'i', [$releaseId]);
    }

    $visibleArtists = array_unique(array_merge(array_values($releaseMap), ['Green']));
    foreach ($artists as $artist) {
        if (in_array($artist['name'], $visibleArtists, true)) {
            continue;
        }
        $slug = seed_slug($artist['name']);
        $cover = 'pap_release_' . $slug . '.jpg';
        $title = match ($artist['name']) {
            'Cocteau Twins' => 'Heaven or Las Vegas',
            'JPEGMAFIA' => 'LP!',
            'Tyler, The Creator' => 'Chromakopia',
            'Lana Del Rey' => 'Ultraviolence',
            'Charli XCX' => 'BRAT',
            'FKA twigs' => 'Eusexua',
            'Travis Scott' => 'Utopia',
            'Beyoncé' => 'Renaissance',
            default => 'Greenerry Sessions',
        };
        seed_release_cover($cover, $artist['name'], $title, $artist['palette']);
        seed_prepared(
            $conn,
            "INSERT INTO release_musical (idCliente, titulo, tipo, descricao, capa, data_lancamento, estado, ativo, idAdminAprovacao, aprovado_em) VALUES (?, ?, 'Album', ?, ?, ?, 'aprovado', 1, 1, NOW())",
            'issss',
            [$artistIds[$artist['name']], $title, 'Lançamento de catálogo preparado para demonstração da PAP.', $cover, '2026-05-' . str_pad((string)((count($visibleArtists) % 20) + 1), 2, '0', STR_PAD_LEFT)]
        );
    }

    $productTemplates = [
        ['T-Shirt', 1, 29.99],
        ['Hoodie', 2, 64.99],
        ['Vinil', 3, 34.99],
        ['CD', 4, 18.99],
        ['Poster', 5, 14.99],
        ['Acessório', 6, 22.99],
    ];
    $productIds = [];
    $allArtistNames = array_column($artists, 'name');
    foreach ($allArtistNames as $aIndex => $artistName) {
        $artistId = $artistIds[$artistName];
        $palette = $artistName === 'Green' ? ['#101418', '#d8c96d', '#f7f7f2'] : ($artists[array_search($artistName, array_column($artists, 'name'), true)]['palette'] ?? ['#111827', '#64748b', '#f8fafc']);
        for ($i = 0; $i < 4; $i++) {
            $tpl = $productTemplates[($aIndex + $i) % count($productTemplates)];
            [$categoryLabel, $categoryId, $basePrice] = $tpl;
            $name = $artistName . ' ' . $categoryLabel . ' Oficial';
            $state = (($aIndex + $i) % 9 === 0 && $artistName !== 'Green') ? 'pendente' : 'aprovado';
            $active = $state === 'aprovado' ? 1 : 0;
            $description = "Merch oficial de {$artistName} preparado para a loja Greenerry. Produto criado para a demonstração final da PAP, com acabamento premium, identidade visual do artista e stock controlado.";
            $stock = 18 + (($aIndex * 7 + $i * 5) % 42);
            $usesSizes = in_array($categoryId, [1, 2, 5], true) ? 1 : 0;
            seed_prepared(
                $conn,
                "INSERT INTO produto (idCliente, idCategoria, nomeProduto, descricaoProduto, marca, precoAtual, iva_percentual, comissao_percentual, stock_total, usa_tamanhos, estado, ativo, idAdminAprovacao, aprovado_em) VALUES (?, ?, ?, ?, ?, ?, 23.00, 5.00, ?, ?, ?, ?, 1, IF(?='aprovado', NOW(), NULL))",
                'iisssdiisis',
                [$artistId, $categoryId, $name, $description, $artistName, $basePrice + ($aIndex % 6) * 2, $stock, $usesSizes, $state, $active, $state]
            );
            $productId = seed_insert_id($conn);
            $productIds[] = $productId;
            for ($v = 0; $v < 3; $v++) {
                $image = 'pap_product_' . seed_slug($artistName) . '_' . seed_slug($categoryLabel) . '_' . ($v + 1) . '.png';
                seed_product_image($image, $artistName, $name, $categoryLabel, $v, $palette);
                seed_prepared($conn, "INSERT INTO produto_imagem (idProduto, ficheiro, ordem) VALUES (?, ?, ?)", 'isi', [$productId, $image, $v]);
            }
            if ($usesSizes) {
                foreach ([1, 2, 3, 4] as $sizeId) {
                    seed_prepared($conn, "INSERT INTO produto_tamanho_stock (idProduto, idTamanho, stock, ativo) VALUES (?, ?, ?, 1)", 'iii', [$productId, $sizeId, max(2, (int)floor($stock / 4))]);
                }
            }
        }
    }

    $customerRows = [];
    $res = mysqli_query($conn, "SELECT idCliente, nome FROM cliente WHERE email LIKE '%.%@gmail.com' AND nome NOT IN ('Green') ORDER BY idCliente DESC LIMIT 8");
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $customerRows[] = $row;
    }
    if (!$customerRows) {
        $customerRows[] = ['idCliente' => 5, 'nome' => 'Green'];
    }

    $approvedProducts = [];
    $res = mysqli_query($conn, "SELECT p.*, c.nomeCategoria FROM produto p JOIN categoria c ON c.idCategoria=p.idCategoria WHERE p.estado='aprovado' ORDER BY p.idProduto");
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $approvedProducts[] = $row;
    }

    $statuses = ['pendente', 'em_preparacao', 'enviada', 'entregue', 'entregue', 'entregue', 'cancelada'];
    $payments = ['cartao', 'mbway', 'transferencia'];
    for ($i = 0; $i < 36 && $approvedProducts; $i++) {
        $customer = $customerRows[$i % count($customerRows)];
        $product = $approvedProducts[($i * 3) % count($approvedProducts)];
        $quantity = ($i % 3) + 1;
        $subtotal = (float)$product['precoAtual'] * $quantity;
        $iva = round($subtotal * 0.23, 2);
        $total = round($subtotal + $iva, 2);
        $commission = round($subtotal * 0.05, 2);
        $artistValue = round($subtotal - $commission, 2);
        $status = $statuses[$i % count($statuses)];
        $payStatus = $status === 'cancelada' ? 'reembolsado' : (($i % 5 === 0 && $status === 'pendente') ? 'pendente' : 'pago');
        $method = $payments[$i % count($payments)];
        seed_prepared(
            $conn,
            "INSERT INTO encomenda (idCliente, subtotal, iva_total, comissao_total, total_final, estado_encomenda, estado_pagamento, metodo_pagamento, nif, observacoes, transportadora, tracking_number, pago_em, enviado_em, entregue_em, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))",
            'iddddssssssssssi',
            [
                (int)$customer['idCliente'],
                $subtotal,
                $iva,
                $commission,
                $total,
                $status,
                $payStatus,
                $method,
                '24567890' . ($i % 10),
                'Encomenda de demonstração para validar o fluxo completo.',
                $status === 'pendente' ? null : 'CTT Expresso',
                $status === 'pendente' ? null : 'GRN' . str_pad((string)(90000 + $i), 7, '0', STR_PAD_LEFT),
                $payStatus === 'pago' ? date('Y-m-d H:i:s', strtotime("-{$i} days")) : null,
                in_array($status, ['enviada', 'entregue'], true) ? date('Y-m-d H:i:s', strtotime('-' . max(1, $i - 1) . ' days')) : null,
                $status === 'entregue' ? date('Y-m-d H:i:s', strtotime('-' . max(0, $i - 2) . ' days')) : null,
                $i,
            ]
        );
        $orderId = seed_insert_id($conn);
        seed_prepared($conn, "INSERT INTO morada_encomenda (idEncomenda, nome_destinatario, morada, cidade, codigo_postal, pais, telefone) VALUES (?, ?, ?, ?, ?, 'Portugal', ?)", 'isssss', [$orderId, $customer['nome'], 'Rua das Artes ' . (10 + $i), ['Porto', 'Lisboa', 'Braga', 'Coimbra'][$i % 4], '4' . str_pad((string)(100 + $i), 3, '0', STR_PAD_LEFT) . '-265', '+351 910 000 ' . str_pad((string)$i, 3, '0', STR_PAD_LEFT)]);
        $itemStatus = $status === 'cancelada' ? 'cancelado' : ($status === 'enviada' ? 'enviado' : ($status === 'entregue' ? 'entregue' : $status));
        seed_prepared(
            $conn,
            "INSERT INTO encomenda_item (idEncomenda, idProduto, idArtista, nome_produto, categoria_nome, quantidade, preco_unitario, iva_percentual, iva_valor, comissao_percentual, comissao_valor, subtotal_linha, total_linha, valor_artista, estado_item) VALUES (?, ?, ?, ?, ?, ?, ?, 23.00, ?, 5.00, ?, ?, ?, ?, ?)",
            'iiissidddddds',
            [$orderId, (int)$product['idProduto'], (int)$product['idCliente'], $product['nomeProduto'], $product['nomeCategoria'], $quantity, (float)$product['precoAtual'], $iva, $commission, $subtotal, $total, $artistValue, $itemStatus]
        );
        if ($status === 'entregue') {
            seed_prepared($conn, "INSERT INTO produto_review (idProduto, idCliente, idEncomenda, rating, comentario, criado_em) VALUES (?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))", 'iiiisi', [(int)$product['idProduto'], (int)$customer['idCliente'], $orderId, 4 + ($i % 2), ['Qualidade muito boa e entrega rápida.', 'Produto bem embalado e com ótimo aspeto.', 'O artigo corresponde às fotografias.'][$i % 3], max(1, $i - 1)]);
        }
        if ($i < 16) {
            seed_prepared($conn, "INSERT INTO encomenda_mensagem (idEncomenda, idProduto, idComprador, idArtista, remetente, mensagem, lida, criado_em) VALUES (?, ?, ?, ?, 'comprador', ?, 0, DATE_SUB(NOW(), INTERVAL ? DAY))", 'iiiisi', [$orderId, (int)$product['idProduto'], (int)$customer['idCliente'], (int)$product['idCliente'], 'Olá, podes confirmar o estado desta encomenda?', $i]);
        }
    }

    $trackRows = [];
    $res = mysqli_query($conn, "SELECT f.idFaixa, r.idCliente AS artist_id FROM faixa f JOIN release_musical r ON r.idRelease=f.idRelease WHERE f.estado='aprovada' AND f.ativo=1 AND r.estado='aprovado' AND r.ativo=1 ORDER BY f.idFaixa");
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $trackRows[] = $row;
    }
    foreach ($trackRows as $index => $track) {
        $plays = 18 + (($index * 11) % 70);
        for ($i = 0; $i < $plays; $i++) {
            $customer = $customerRows[($i + $index) % count($customerRows)];
            seed_prepared($conn, "INSERT INTO faixa_listen (idFaixa, idCliente, idArtista, segundos_ouvidos, criado_em) VALUES (?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))", 'iiiii', [(int)$track['idFaixa'], (int)$customer['idCliente'], (int)$track['artist_id'], 55 + (($i * 13) % 180), ($i + $index) % 90]);
        }
    }

    foreach ($artists as $i => $artist) {
        $customer = $customerRows[$i % count($customerRows)];
        seed_prepared($conn, "INSERT INTO seguir_artista (idSeguidor, idArtista, criado_em) VALUES (?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))", 'iii', [(int)$customer['idCliente'], $artistIds[$artist['name']], $i]);
    }

    foreach (['Preciso de ajuda com uma encomenda', 'Pergunta sobre stock', 'Pedido de fatura', 'Alteração de morada', 'Produto chegou danificado'] as $i => $subject) {
        $customer = $customerRows[$i % count($customerRows)];
        seed_prepared($conn, "INSERT INTO mensagem_admin (idCliente, assunto, mensagem, estado, criado_em) VALUES (?, ?, ?, 'aberta', DATE_SUB(NOW(), INTERVAL ? DAY))", 'issi', [(int)$customer['idCliente'], $subject, 'Mensagem de demonstração para a equipa de suporte responder no painel administrativo.', $i + 1]);
    }

    seed_sql($conn, 'SET FOREIGN_KEY_CHECKS=1');
    mysqli_commit($conn);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    seed_sql($conn, 'SET FOREIGN_KEY_CHECKS=1');
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

echo "PAP demo dataset seeded.\n";
echo "Artists: " . count($artists) . " + Green\n";
echo "Password for demo accounts: 12345678\n";
