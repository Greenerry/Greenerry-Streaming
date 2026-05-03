-- ============================================================
-- GREENERRY - Esquema de Base de Dados PAP
-- Idioma base: Portugues (Portugal)
-- Projeto: Plataforma de musica independente com streaming,
-- merch, aprovacao administrativa, mensagens e recuperacao
-- manual de palavra-passe.
-- ============================================================

DROP DATABASE IF EXISTS greenerry;
CREATE DATABASE greenerry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE greenerry;

-- ============================================================
-- 1. ADMINISTRACAO
-- ============================================================

CREATE TABLE admin (
    idAdmin INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    palavra_passe VARCHAR(255) NOT NULL,
    cargo VARCHAR(80) DEFAULT 'Administrador',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_login DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Palavra-passe provisoria para ambiente escolar.
-- Nesta versao ja fica guardada com hash para evitar texto simples.
INSERT INTO admin (idAdmin, nome, email, palavra_passe, cargo)
VALUES
(1, 'Admin Principal', 'admin@greenerry.com', '$2y$10$gBHujQ1KDiDGW5o0ERXQC.Tp4/zoy4KFu7jNAO3SMyJ.ctc0.GJl.', 'Administrador Principal');

CREATE TABLE site_config (
    setting_key VARCHAR(80) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO site_config (setting_key, setting_value) VALUES
('site_name', 'Greenerry'),
('contact_email', 'support@greenerry.test'),
('contact_phone', '+351 900 000 000'),
('instagram_url', '#'),
('x_url', '#'),
('footer_note', '(c) 2026 Greenerry. Built for PAP presentation use.'),
('support_hours', 'Mon-Fri, 09:00-18:00'),
('commission_percent', '5'),
('shipping_note', 'Digital support and merch handled by Greenerry admin.');

-- ============================================================
-- 2. UTILIZADORES / ARTISTAS
-- ============================================================

CREATE TABLE cliente (
    idCliente INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    palavra_passe VARCHAR(255) NOT NULL,
    telefone VARCHAR(30) NULL,
    foto VARCHAR(255) NULL,
    banner VARCHAR(255) NULL,
    bio TEXT NULL,
    slug VARCHAR(160) NULL UNIQUE,
    estado ENUM('ativo', 'inativo', 'bloqueado') NOT NULL DEFAULT 'ativo',
    ultimo_login DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE pedido_reset_password (
    idPedidoReset INT AUTO_INCREMENT PRIMARY KEY,
    idCliente INT NOT NULL,
    email VARCHAR(150) NOT NULL,
    motivo TEXT NULL,
    estado ENUM('pendente', 'em_analise', 'concluido', 'recusado') NOT NULL DEFAULT 'pendente',
    observacoes_admin TEXT NULL,
    idAdmin INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    CONSTRAINT fk_pedido_reset_cliente
        FOREIGN KEY (idCliente) REFERENCES cliente(idCliente)
        ON DELETE CASCADE,
    CONSTRAINT fk_pedido_reset_admin
        FOREIGN KEY (idAdmin) REFERENCES admin(idAdmin)
        ON DELETE SET NULL
);

-- ============================================================
-- 3. CATALOGO / CATEGORIAS / TAMANHOS
-- ============================================================

CREATE TABLE categoria (
    idCategoria INT AUTO_INCREMENT PRIMARY KEY,
    nomeCategoria VARCHAR(100) NOT NULL UNIQUE,
    descricaoCategoria TEXT NULL,
    usa_tamanhos TINYINT(1) NOT NULL DEFAULT 0,
    estado ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    idAdminCriador INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_categoria_admin
        FOREIGN KEY (idAdminCriador) REFERENCES admin(idAdmin)
        ON DELETE SET NULL
);

INSERT INTO categoria (nomeCategoria, descricaoCategoria, usa_tamanhos, idAdminCriador) VALUES
('T-Shirt', 'T-shirts oficiais dos artistas.', 1, 1),
('Hoodie', 'Sweatshirts e hoodies de merchandising.', 1, 1),
('Vinil', 'Edicoes em vinil para colecao.', 0, 1),
('CD', 'Edicoes fisicas em CD.', 0, 1),
('Poster', 'Posters e material visual promocional.', 1, 1),
('Acessorio', 'Acessorios como sacos, pins e outros artigos.', 0, 1);

CREATE TABLE tamanho (
    idTamanho INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    etiqueta VARCHAR(30) NOT NULL,
    ordem INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1
);

INSERT INTO tamanho (codigo, etiqueta, ordem) VALUES
('S', 'S', 1),
('M', 'M', 2),
('L', 'L', 3),
('XL', 'XL', 4);

-- ============================================================
-- 4. MUSICA
-- ============================================================

CREATE TABLE release_musical (
    idRelease INT AUTO_INCREMENT PRIMARY KEY,
    idCliente INT NOT NULL,
    titulo VARCHAR(180) NOT NULL,
    tipo ENUM('Single', 'EP', 'Album') NOT NULL DEFAULT 'Single',
    descricao TEXT NULL,
    capa VARCHAR(255) NULL,
    data_lancamento DATE NULL,
    estado ENUM('pendente', 'aprovado', 'rejeitado', 'inativo') NOT NULL DEFAULT 'pendente',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    bloqueado_admin TINYINT(1) NOT NULL DEFAULT 0,
    motivo_rejeicao TEXT NULL,
    idAdminAprovacao INT NULL,
    aprovado_em DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_release_cliente
        FOREIGN KEY (idCliente) REFERENCES cliente(idCliente)
        ON DELETE CASCADE,
    CONSTRAINT fk_release_admin
        FOREIGN KEY (idAdminAprovacao) REFERENCES admin(idAdmin)
        ON DELETE SET NULL
);

CREATE TABLE faixa (
    idFaixa INT AUTO_INCREMENT PRIMARY KEY,
    idRelease INT NOT NULL,
    numero_faixa INT NOT NULL DEFAULT 1,
    titulo VARCHAR(180) NOT NULL,
    ficheiro_audio VARCHAR(255) NOT NULL,
    duracao_segundos INT NULL,
    estado ENUM('pendente', 'aprovada', 'rejeitada', 'inativa') NOT NULL DEFAULT 'pendente',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_faixa_release_numero UNIQUE (idRelease, numero_faixa),
    CONSTRAINT fk_faixa_release
        FOREIGN KEY (idRelease) REFERENCES release_musical(idRelease)
        ON DELETE CASCADE
);

CREATE TABLE favorito_musica (
    idFavorito INT AUTO_INCREMENT PRIMARY KEY,
    idCliente INT NOT NULL,
    idFaixa INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_favorito_musica UNIQUE (idCliente, idFaixa),
    CONSTRAINT fk_favorito_cliente
        FOREIGN KEY (idCliente) REFERENCES cliente(idCliente)
        ON DELETE CASCADE,
    CONSTRAINT fk_favorito_faixa
        FOREIGN KEY (idFaixa) REFERENCES faixa(idFaixa)
        ON DELETE CASCADE
);

CREATE TABLE seguir_artista (
    idSeguirArtista INT AUTO_INCREMENT PRIMARY KEY,
    idSeguidor INT NOT NULL,
    idArtista INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_seguir_artista UNIQUE (idSeguidor, idArtista),
    CONSTRAINT chk_seguir_artista_diff CHECK (idSeguidor <> idArtista),
    CONSTRAINT fk_seguir_artista_seguidor
        FOREIGN KEY (idSeguidor) REFERENCES cliente(idCliente)
        ON DELETE CASCADE,
    CONSTRAINT fk_seguir_artista_artista
        FOREIGN KEY (idArtista) REFERENCES cliente(idCliente)
        ON DELETE CASCADE
);

-- ============================================================
-- 5. PRODUTOS / MERCH
-- ============================================================

CREATE TABLE produto (
    idProduto INT AUTO_INCREMENT PRIMARY KEY,
    idCliente INT NOT NULL,
    idCategoria INT NOT NULL,
    nomeProduto VARCHAR(150) NOT NULL,
    descricaoProduto TEXT NULL,
    marca VARCHAR(100) NULL,
    precoAtual DECIMAL(10,2) NOT NULL,
    iva_percentual DECIMAL(5,2) NOT NULL DEFAULT 23.00,
    comissao_percentual DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    stock_total INT NOT NULL DEFAULT 0,
    usa_tamanhos TINYINT(1) NOT NULL DEFAULT 0,
    imagem VARCHAR(255) NULL,
    estado ENUM('pendente', 'aprovado', 'rejeitado', 'inativo') NOT NULL DEFAULT 'pendente',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    bloqueado_admin TINYINT(1) NOT NULL DEFAULT 0,
    motivo_rejeicao TEXT NULL,
    idAdminAprovacao INT NULL,
    aprovado_em DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_produto_cliente
        FOREIGN KEY (idCliente) REFERENCES cliente(idCliente)
        ON DELETE CASCADE,
    CONSTRAINT fk_produto_categoria
        FOREIGN KEY (idCategoria) REFERENCES categoria(idCategoria)
        ON DELETE RESTRICT,
    CONSTRAINT fk_produto_admin
        FOREIGN KEY (idAdminAprovacao) REFERENCES admin(idAdmin)
        ON DELETE SET NULL
);

CREATE TABLE produto_tamanho_stock (
    idProdutoTamanho INT AUTO_INCREMENT PRIMARY KEY,
    idProduto INT NOT NULL,
    idTamanho INT NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT uq_produto_tamanho UNIQUE (idProduto, idTamanho),
    CONSTRAINT fk_produto_tamanho_produto
        FOREIGN KEY (idProduto) REFERENCES produto(idProduto)
        ON DELETE CASCADE,
    CONSTRAINT fk_produto_tamanho_tamanho
        FOREIGN KEY (idTamanho) REFERENCES tamanho(idTamanho)
        ON DELETE RESTRICT
);

-- ============================================================
-- 6. ENCOMENDAS / PAGAMENTOS
-- ============================================================

CREATE TABLE encomenda (
    idEncomenda INT AUTO_INCREMENT PRIMARY KEY,
    idCliente INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    iva_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    comissao_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_final DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado_encomenda ENUM('pendente', 'em_preparacao', 'enviada', 'entregue', 'cancelada') NOT NULL DEFAULT 'pendente',
    estado_pagamento ENUM('pendente', 'pago', 'falhado', 'reembolsado') NOT NULL DEFAULT 'pendente',
    metodo_pagamento ENUM('cartao', 'mbway', 'transferencia') NOT NULL DEFAULT 'cartao',
    nif VARCHAR(20) NULL,
    observacoes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_encomenda_cliente
        FOREIGN KEY (idCliente) REFERENCES cliente(idCliente)
        ON DELETE RESTRICT
);

CREATE TABLE encomenda_item (
    idEncomendaItem INT AUTO_INCREMENT PRIMARY KEY,
    idEncomenda INT NOT NULL,
    idProduto INT NOT NULL,
    idArtista INT NOT NULL,
    idTamanho INT NULL,
    nome_produto VARCHAR(150) NOT NULL,
    categoria_nome VARCHAR(100) NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    iva_percentual DECIMAL(5,2) NOT NULL,
    iva_valor DECIMAL(10,2) NOT NULL,
    comissao_percentual DECIMAL(5,2) NOT NULL,
    comissao_valor DECIMAL(10,2) NOT NULL,
    subtotal_linha DECIMAL(10,2) NOT NULL,
    total_linha DECIMAL(10,2) NOT NULL,
    valor_artista DECIMAL(10,2) NOT NULL,
    estado_item ENUM('pendente', 'em_preparacao', 'enviado', 'entregue', 'cancelado') NOT NULL DEFAULT 'pendente',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_encomenda_item_encomenda
        FOREIGN KEY (idEncomenda) REFERENCES encomenda(idEncomenda)
        ON DELETE CASCADE,
    CONSTRAINT fk_encomenda_item_produto
        FOREIGN KEY (idProduto) REFERENCES produto(idProduto)
        ON DELETE RESTRICT,
    CONSTRAINT fk_encomenda_item_artista
        FOREIGN KEY (idArtista) REFERENCES cliente(idCliente)
        ON DELETE RESTRICT,
    CONSTRAINT fk_encomenda_item_tamanho
        FOREIGN KEY (idTamanho) REFERENCES tamanho(idTamanho)
        ON DELETE SET NULL
);

CREATE TABLE morada_encomenda (
    idMoradaEncomenda INT AUTO_INCREMENT PRIMARY KEY,
    idEncomenda INT NOT NULL UNIQUE,
    nome_destinatario VARCHAR(120) NOT NULL,
    morada VARCHAR(255) NOT NULL,
    cidade VARCHAR(100) NOT NULL,
    codigo_postal VARCHAR(20) NOT NULL,
    pais VARCHAR(80) NOT NULL DEFAULT 'Portugal',
    telefone VARCHAR(30) NOT NULL,
    CONSTRAINT fk_morada_encomenda
        FOREIGN KEY (idEncomenda) REFERENCES encomenda(idEncomenda)
        ON DELETE CASCADE
);

CREATE TABLE pagamento (
    idPagamento INT AUTO_INCREMENT PRIMARY KEY,
    idEncomenda INT NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    metodo_pagamento ENUM('cartao', 'mbway', 'transferencia') NOT NULL,
    estado_pagamento ENUM('pendente', 'pago', 'falhado', 'reembolsado') NOT NULL DEFAULT 'pago',
    referencia VARCHAR(120) NULL,
    data_pagamento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pagamento_encomenda
        FOREIGN KEY (idEncomenda) REFERENCES encomenda(idEncomenda)
        ON DELETE CASCADE
);

-- ============================================================
-- 7. MENSAGENS / CONTACTO / NOTIFICACOES
-- ============================================================

CREATE TABLE mensagem_admin (
    idMensagem INT AUTO_INCREMENT PRIMARY KEY,
    idCliente INT NOT NULL,
    assunto VARCHAR(160) NOT NULL,
    mensagem TEXT NOT NULL,
    resposta_admin TEXT NULL,
    estado ENUM('aberta', 'respondida', 'fechada') NOT NULL DEFAULT 'aberta',
    idAdminResposta INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    responded_at DATETIME NULL,
    CONSTRAINT fk_mensagem_cliente
        FOREIGN KEY (idCliente) REFERENCES cliente(idCliente)
        ON DELETE CASCADE,
    CONSTRAINT fk_mensagem_admin
        FOREIGN KEY (idAdminResposta) REFERENCES admin(idAdmin)
        ON DELETE SET NULL
);

CREATE TABLE notificacao (
    idNotificacao INT AUTO_INCREMENT PRIMARY KEY,
    idCliente INT NOT NULL,
    titulo VARCHAR(160) NOT NULL,
    mensagem TEXT NOT NULL,
    tipo ENUM('sistema', 'produto', 'musica', 'encomenda', 'mensagem', 'password') NOT NULL DEFAULT 'sistema',
    lida TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notificacao_cliente
        FOREIGN KEY (idCliente) REFERENCES cliente(idCliente)
        ON DELETE CASCADE
);

-- ============================================================
-- 8. INDICES DE APOIO
-- ============================================================

CREATE INDEX idx_cliente_estado ON cliente (estado);
CREATE INDEX idx_release_estado_cliente ON release_musical (estado, idCliente);
CREATE INDEX idx_faixa_release_estado ON faixa (idRelease, estado);
CREATE INDEX idx_seguir_artista_seguidor ON seguir_artista (idSeguidor, created_at);
CREATE INDEX idx_seguir_artista_artista ON seguir_artista (idArtista, created_at);
CREATE INDEX idx_produto_estado_cliente ON produto (estado, idCliente);
CREATE INDEX idx_encomenda_cliente_estado ON encomenda (idCliente, estado_encomenda);
CREATE INDEX idx_encomenda_item_artista_estado ON encomenda_item (idArtista, estado_item);
CREATE INDEX idx_mensagem_admin_estado ON mensagem_admin (estado, created_at);
CREATE INDEX idx_pedido_reset_estado ON pedido_reset_password (estado, created_at);
CREATE INDEX idx_notificacao_cliente_lida ON notificacao (idCliente, lida);

