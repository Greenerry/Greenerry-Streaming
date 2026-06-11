# Manual Tecnico - Estrutura Greenerry

## 1. Objetivo

Explicar como instalar, configurar, executar e validar o website Greenerry em ambiente local com XAMPP.

## 2. Requisitos

- Windows com XAMPP instalado.
- Apache ativo.
- MySQL/MariaDB ativo.
- PHP compativel com o projeto.
- Composer instalado ou pasta `vendor/` incluida.
- Navegador moderno.
- Ficheiro `greenerry.sql` para importacao da base de dados.

## 3. Estrutura do projeto

- `admin/`: painel administrativo.
- `api/`: endpoints usados por acoes assincronas.
- `assets/`: CSS, JavaScript, imagens, logos e ficheiros multimedia.
- `docs/`: documentacao, DER, MER/FNN e materiais PAP.
- `includes/`: configuracao, ligacao a base de dados, autenticacao e helpers.
- `pages/`: paginas publicas, area de utilizador e area de artista.
- `vendor/`: dependencias Composer.
- `greenerry.sql`: script de criacao/importacao da base de dados.

## 4. Instalacao em XAMPP

1. Copiar a pasta `greenerry` para `C:\xampp\htdocs\dashboard\greenerry`.
2. Abrir o painel do XAMPP.
3. Iniciar Apache.
4. Iniciar MySQL.
5. Abrir phpMyAdmin.
6. Criar a base de dados `greenerry`, se ainda nao existir.
7. Importar o ficheiro `greenerry.sql`.
8. Aceder a `http://localhost/dashboard/greenerry/`.

## 5. Configuracao local

- Host: `localhost`
- Utilizador: `root`
- Password: vazia
- Base de dados: `greenerry`
- Configuracao principal: `includes/config.php`

O acesso administrativo e feito numa pagina reservada com e-mail e palavra-passe de uma conta admin ativa.

## 6. Dependencias

O projeto usa Composer:

- `phpmailer/phpmailer`: envio de e-mails de verificacao, recuperacao e notificacoes.
- `dompdf/dompdf`: geracao de faturas/recibos em PDF.

Se a pasta `vendor/` nao existir, executar:

```bash
composer install
```

## 7. Acessos de demonstracao

Admin principal:

- Email: `greenerry333@gmail.com`
- Password: `Srijan123@`

Utilizadores de demonstracao:

- Existem contas de clientes e artistas no ficheiro `greenerry.sql`.
- Antes da defesa, confirmar as palavras-passe finais que serao usadas no video e na demonstracao.

## 8. Funcionalidades tecnicas a explicar

- Sessoes separadas para cliente/artista e administrador.
- Passwords guardadas com hash.
- Prepared statements em operacoes sensiveis.
- CSRF em formularios.
- Verificacao por codigo enviado por e-mail.
- Recuperacao de palavra-passe por codigo temporario.
- Upload de capas, imagens de produto e ficheiros de audio.
- Moderacao administrativa de produtos e lancamentos.
- Prioridade administrativa: conteudos bloqueados/inativados pelo admin nao podem ser reativados pelo artista.
- Carrinho, checkout, encomendas, estados e faturas PDF.
- Mensagens por encomenda entre comprador e artista.
- Notificacoes, relatorios, tema claro/escuro e idioma PT/EN.

## 9. Testes recomendados

- Abrir homepage, Musica, Artistas e Loja sem login.
- Criar conta e validar codigo de e-mail.
- Fazer login, editar perfil, usar favoritos e playlists.
- Adicionar produto ao carrinho e finalizar compra.
- Consultar compras, fatura e notificacoes.
- Entrar como artista, publicar musica/produto e consultar analise/rendimento.
- Ver mensagens por encomenda.
- Entrar como admin, gerir produtos, lancamentos, generos, categorias, utilizadores e encomendas.
- Confirmar que o admin entra apenas com e-mail e palavra-passe.
- Confirmar que produto/lancamento bloqueado pelo admin nao pode ser reativado pelo artista.

## 10. Problemas comuns

- Pagina nao abre: confirmar Apache e caminho do projeto.
- Base de dados falha: confirmar importacao de `greenerry.sql` e nome `greenerry`.
- E-mails nao enviam: confirmar configuracao SMTP usada em ambiente local.
- Imagens nao aparecem: confirmar caminhos em `assets/img/` e permissao das pastas.
- Dependencias falham: confirmar `vendor/` ou executar `composer install`.
