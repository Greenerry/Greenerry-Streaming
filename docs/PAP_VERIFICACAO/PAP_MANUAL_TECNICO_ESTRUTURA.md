# Manual Técnico - Estrutura Greenerry

## 1. Objetivo

Explicar como instalar, configurar e executar o website Greenerry através do XAMPP.

## 2. Requisitos

- Windows com XAMPP instalado.
- Apache ativo.
- MySQL/MariaDB ativo.
- PHP compatível com o XAMPP usado.
- Composer instalado ou pasta `vendor` incluída no projeto.
- Navegador moderno.

## 3. Estrutura do projeto

Pastas principais:

- `admin/`: painel administrativo.
- `api/`: endpoints usados por ações assíncronas.
- `assets/`: CSS, JavaScript, imagens e áudio.
- `docs/`: documentação, DER e ficheiros PAP.
- `includes/`: configuração, ligação à base de dados, autenticação e helpers.
- `pages/`: páginas públicas e área de utilizador/artista.
- `vendor/`: dependências Composer.

Ficheiros principais:

- `index.php`: entrada do projeto.
- `greenerry.sql`: base de dados final.
- `composer.json`: dependências.

## 4. Instalação em XAMPP

1. Copiar a pasta `greenerry` para `C:\xampp\htdocs\dashboard\greenerry`.
2. Abrir o painel do XAMPP.
3. Iniciar Apache.
4. Iniciar MySQL.
5. Abrir phpMyAdmin.
6. Criar/importar a base de dados `greenerry`.
7. Importar o ficheiro `greenerry.sql`.
8. Aceder a `http://localhost/dashboard/greenerry/`.

## 5. Configuração da base de dados local

Configuração local esperada:

- Host: `localhost`
- Utilizador: `root`
- Password: vazia
- Base de dados: `greenerry`

O ficheiro `includes/config.php` carrega as configurações e liga ao MySQL. Em ambiente local, os valores acima são os usados por defeito.

## 6. Dependências

O projeto usa Composer:

- `phpmailer/phpmailer`: envio de emails, verificação e recuperação de password.
- `dompdf/dompdf`: geração de documentos PDF, como faturas/recibos.

Se a pasta `vendor` não existir, executar:

```bash
composer install
```

## 7. Acessos de demonstração

Admin principal:

- Email: `greenerry333@gmail.com`
- Password: `Srijan123@`

Utilizadores de demonstração:

- Existem contas de artistas e clientes no ficheiro `greenerry.sql`.
- Confirmar a password final antes da entrega, caso seja necessário demonstrar login com várias contas.

## 8. Funcionalidades técnicas

- Sessões PHP para user/admin.
- Passwords guardadas com hash.
- Prepared statements em operações sensíveis.
- CSRF em formulários.
- Upload de imagens e músicas.
- Aprovação/rejeição administrativa.
- Notificações.
- Geração de faturas PDF.
- Envio de emails.

## 9. Testes recomendados

- Abrir homepage.
- Pesquisar música.
- Pesquisar produtos.
- Criar conta.
- Fazer login.
- Editar perfil.
- Adicionar produto ao carrinho.
- Finalizar compra.
- Ver histórico de compras.
- Enviar mensagem ao admin.
- Entrar no admin.
- Gerir categorias.
- Aprovar/rejeitar produto ou música.
- Alterar estado de encomenda.
- Ver relatórios.

## 10. Problemas comuns

- Se a página não abrir: confirmar Apache e caminho do projeto.
- Se a base de dados falhar: confirmar que `greenerry.sql` foi importado e que o nome da base é `greenerry`.
- Se emails não enviarem: confirmar SMTP nas definições.
- Se imagens não aparecerem: confirmar a pasta `assets/img`.
- Se dependências falharem: confirmar `vendor` ou executar `composer install`.
