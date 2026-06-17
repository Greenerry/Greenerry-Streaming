<?php
// Shared setup helper: Creates or updates database tables needed by the app.
// Keep this shared code small, reusable, and safe for every page.

function greenerry_column_exists(mysqli $conn, string $table, string $column): bool
{
    $row = db_one_prepared(
        $conn,
        "SELECT COLUMN_NAME
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?
         LIMIT 1",
        'ss',
        [$table, $column]
    );

    return (bool)$row;
}

function greenerry_index_exists(mysqli $conn, string $table, string $index): bool
{
    $row = db_one_prepared(
        $conn,
        "SELECT INDEX_NAME
         FROM INFORMATION_SCHEMA.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND INDEX_NAME = ?
         LIMIT 1",
        'ss',
        [$table, $index]
    );

    return (bool)$row;
}

function greenerry_constraint_exists(mysqli $conn, string $table, string $constraint): bool
{
    $row = db_one_prepared(
        $conn,
        "SELECT CONSTRAINT_NAME
         FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND CONSTRAINT_NAME = ?
         LIMIT 1",
        'ss',
        [$table, $constraint]
    );

    return (bool)$row;
}

function greenerry_column_type(mysqli $conn, string $table, string $column): ?string
{
    $row = db_one_prepared(
        $conn,
        "SELECT COLUMN_TYPE
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?
         LIMIT 1",
        'ss',
        [$table, $column]
    );

    return $row ? strtolower((string)$row['COLUMN_TYPE']) : null;
}

function greenerry_ensure_schema(mysqli $conn): void
{
    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS genero (
            idGenero INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(80) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            estado ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
            criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_genero_nome (nome),
            UNIQUE KEY uq_genero_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    if (!greenerry_column_exists($conn, 'faixa', 'genero')) {
        mysqli_query($conn, "ALTER TABLE faixa ADD genero VARCHAR(80) NULL AFTER titulo");
    }

    if (!greenerry_column_exists($conn, 'faixa', 'idGenero')) {
        mysqli_query($conn, "ALTER TABLE faixa ADD idGenero INT NULL AFTER genero");
    }

    if (!greenerry_column_exists($conn, 'release_musical', 'idGenero')) {
        mysqli_query($conn, "ALTER TABLE release_musical ADD idGenero INT NULL AFTER tipo");
    }

    foreach (db_all($conn, "SELECT DISTINCT TRIM(genero) AS nome FROM faixa WHERE genero IS NOT NULL AND TRIM(genero) <> ''") as $genreRow) {
        $genreName = mb_substr(trim((string)($genreRow['nome'] ?? '')), 0, 80);
        if ($genreName === '') {
            continue;
        }
        greenerry_resolve_genre_id($conn, $genreName);
    }

    mysqli_query(
        $conn,
        "UPDATE faixa f
         JOIN genero g ON LOWER(TRIM(g.nome)) = LOWER(TRIM(f.genero))
         SET f.idGenero = g.idGenero
         WHERE f.idGenero IS NULL
           AND f.genero IS NOT NULL
           AND TRIM(f.genero) <> ''"
    );

    mysqli_query(
        $conn,
        "UPDATE release_musical r
         JOIN faixa f ON f.idRelease = r.idRelease AND f.numero_faixa = 1
         SET r.idGenero = f.idGenero
         WHERE r.idGenero IS NULL
           AND f.idGenero IS NOT NULL"
    );

    if (greenerry_column_exists($conn, 'playlist', 'nome') && !greenerry_column_exists($conn, 'playlist', 'capa')) {
        mysqli_query($conn, "ALTER TABLE playlist ADD capa VARCHAR(255) NULL AFTER descricao");
    }

    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS playlist (
            idPlaylist INT AUTO_INCREMENT PRIMARY KEY,
            idCliente INT NOT NULL,
            nome VARCHAR(140) NOT NULL,
            descricao TEXT NULL,
            capa VARCHAR(255) NULL,
            visibilidade ENUM('privada', 'publica') NOT NULL DEFAULT 'privada',
            criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_playlist_cliente (idCliente, criado_em),
            CONSTRAINT fk_playlist_cliente
                FOREIGN KEY (idCliente) REFERENCES cliente(idCliente)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS playlist_faixa (
            idPlaylistFaixa INT AUTO_INCREMENT PRIMARY KEY,
            idPlaylist INT NOT NULL,
            idFaixa INT NOT NULL,
            ordem INT NOT NULL DEFAULT 0,
            criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT uq_playlist_faixa UNIQUE (idPlaylist, idFaixa),
            INDEX idx_playlist_faixa_faixa (idFaixa),
            CONSTRAINT fk_playlist_faixa_playlist
                FOREIGN KEY (idPlaylist) REFERENCES playlist(idPlaylist)
                ON DELETE CASCADE,
            CONSTRAINT fk_playlist_faixa_faixa
                FOREIGN KEY (idFaixa) REFERENCES faixa(idFaixa)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS produto_review (
            idReview INT AUTO_INCREMENT PRIMARY KEY,
            idProduto INT NOT NULL,
            idCliente INT NOT NULL,
            idEncomenda INT NOT NULL,
            rating TINYINT NOT NULL,
            comentario VARCHAR(180) NULL,
            criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT uq_produto_review_cliente UNIQUE (idProduto, idCliente),
            INDEX idx_produto_review_produto (idProduto, criado_em),
            CONSTRAINT fk_produto_review_produto
                FOREIGN KEY (idProduto) REFERENCES produto(idProduto)
                ON DELETE CASCADE,
            CONSTRAINT fk_produto_review_cliente
                FOREIGN KEY (idCliente) REFERENCES cliente(idCliente)
                ON DELETE CASCADE,
            CONSTRAINT fk_produto_review_encomenda
                FOREIGN KEY (idEncomenda) REFERENCES encomenda(idEncomenda)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS encomenda_mensagem (
            idMensagemEncomenda INT AUTO_INCREMENT PRIMARY KEY,
            idEncomenda INT NOT NULL,
            idProduto INT NOT NULL,
            idComprador INT NOT NULL,
            idArtista INT NOT NULL,
            remetente ENUM('comprador', 'artista') NOT NULL DEFAULT 'comprador',
            mensagem TEXT NOT NULL,
            lida TINYINT(1) NOT NULL DEFAULT 0,
            criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_encomenda_mensagem_thread (idEncomenda, idProduto, criado_em),
            INDEX idx_encomenda_mensagem_artista (idArtista, lida),
            CONSTRAINT fk_encomenda_mensagem_encomenda
                FOREIGN KEY (idEncomenda) REFERENCES encomenda(idEncomenda)
                ON DELETE CASCADE,
            CONSTRAINT fk_encomenda_mensagem_produto
                FOREIGN KEY (idProduto) REFERENCES produto(idProduto)
                ON DELETE CASCADE,
            CONSTRAINT fk_encomenda_mensagem_comprador
                FOREIGN KEY (idComprador) REFERENCES cliente(idCliente)
                ON DELETE CASCADE,
            CONSTRAINT fk_encomenda_mensagem_artista
                FOREIGN KEY (idArtista) REFERENCES cliente(idCliente)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS faixa_listen (
            idListen INT AUTO_INCREMENT PRIMARY KEY,
            idFaixa INT NOT NULL,
            idCliente INT NULL,
            idArtista INT NOT NULL,
            segundos_ouvidos INT NOT NULL DEFAULT 0,
            criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_faixa_listen_artista (idArtista, criado_em),
            INDEX idx_faixa_listen_faixa (idFaixa, criado_em),
            CONSTRAINT fk_faixa_listen_faixa
                FOREIGN KEY (idFaixa) REFERENCES faixa(idFaixa)
                ON DELETE CASCADE,
            CONSTRAINT fk_faixa_listen_cliente
                FOREIGN KEY (idCliente) REFERENCES cliente(idCliente)
                ON DELETE SET NULL,
            CONSTRAINT fk_faixa_listen_artista
                FOREIGN KEY (idArtista) REFERENCES cliente(idCliente)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    if (!greenerry_index_exists($conn, 'faixa', 'idx_faixa_genero')) {
        mysqli_query($conn, "CREATE INDEX idx_faixa_genero ON faixa (genero)");
    }

    if (!greenerry_index_exists($conn, 'faixa', 'idx_faixa_id_genero')) {
        mysqli_query($conn, "CREATE INDEX idx_faixa_id_genero ON faixa (idGenero)");
    }

    if (!greenerry_index_exists($conn, 'release_musical', 'idx_release_genero')) {
        mysqli_query($conn, "CREATE INDEX idx_release_genero ON release_musical (idGenero)");
    }

    if (!greenerry_constraint_exists($conn, 'faixa', 'fk_faixa_genero')) {
        mysqli_query($conn, "ALTER TABLE faixa ADD CONSTRAINT fk_faixa_genero FOREIGN KEY (idGenero) REFERENCES genero(idGenero) ON DELETE SET NULL");
    }

    if (!greenerry_constraint_exists($conn, 'release_musical', 'fk_release_genero')) {
        mysqli_query($conn, "ALTER TABLE release_musical ADD CONSTRAINT fk_release_genero FOREIGN KEY (idGenero) REFERENCES genero(idGenero) ON DELETE SET NULL");
    }

    mysqli_query(
        $conn,
        "UPDATE produto_review
         SET comentario = CONCAT(TRIM(SUBSTRING(comentario, 1, 177)), '...')
         WHERE comentario IS NOT NULL
           AND CHAR_LENGTH(comentario) > 180"
    );

    if (greenerry_column_type($conn, 'produto_review', 'comentario') !== 'varchar(180)') {
        mysqli_query($conn, "ALTER TABLE produto_review MODIFY comentario VARCHAR(180) NULL");
    }
}

greenerry_ensure_schema($conn);
