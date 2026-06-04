<?php

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
    if (!greenerry_column_exists($conn, 'faixa', 'genero')) {
        mysqli_query($conn, "ALTER TABLE faixa ADD genero VARCHAR(80) NULL AFTER titulo");
    }

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

    mysqli_query(
        $conn,
        "UPDATE produto
         SET descricaoProduto = CONCAT(TRIM(SUBSTRING(descricaoProduto, 1, 107)), '...')
         WHERE descricaoProduto IS NOT NULL
           AND CHAR_LENGTH(descricaoProduto) > 110"
    );

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
