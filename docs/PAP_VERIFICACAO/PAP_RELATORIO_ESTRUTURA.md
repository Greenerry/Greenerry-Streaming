# Estrutura do Relatório da PAP - Greenerry

## 1. Capa

Nome da escola, curso, título do projeto, nome do aluno, ano letivo e professor orientador.

Título recomendado: **Greenerry - Plataforma Web de Música, Artistas e Merchandising**

## 2. Índice automático

Inserir no Word depois de aplicar estilos de títulos.

## 3. Introdução

Explicar que a PAP consiste no desenvolvimento de um website funcional. Apresentar a Greenerry como uma plataforma onde utilizadores descobrem música, seguem artistas, compram produtos e interagem com uma área administrativa.

## 4. Identificação do problema / necessidade

Problema: artistas independentes precisam de um espaço único para divulgar música, gerir produtos e acompanhar encomendas. Utilizadores precisam de uma experiência simples para descobrir artistas, ouvir previews, comprar merchandising e consultar compras.

Público-alvo: fãs de música, artistas independentes e administradores da plataforma.

## 5. Objetivos do projeto

Objetivo geral: desenvolver um website funcional, responsivo e ligado a base de dados.

Objetivos específicos:

- Criar autenticação de utilizadores e administradores.
- Implementar loja online com produtos, categorias, carrinho e checkout.
- Criar histórico de encomendas e recibos.
- Criar biblioteca/favoritos e seguimento de artistas.
- Criar área de artista para uploads e gestão de vendas.
- Criar painel administrativo para moderação, encomendas, mensagens, utilizadores e relatórios.

## 6. Metodologia de trabalho

Descrever pesquisa, planeamento, divisão por páginas, construção por módulos, testes no XAMPP e ajustes finais a partir das necessidades da PAP.

## 7. Ferramentas e tecnologias utilizadas

- HTML, CSS e JavaScript para frontend.
- PHP para backend.
- MySQL/MariaDB para base de dados.
- XAMPP para servidor local.
- phpMyAdmin para gestão da base de dados.
- Composer para dependências.
- PHPMailer para envio de emails.
- Dompdf para geração de PDFs/faturas.

## 8. Estrutura da base de dados

Usar o DER já existente em `docs/DER_greenerry_Srijan.pdf`.

Tabelas principais a explicar:

- `cliente`
- `admin`
- `categoria`
- `produto`
- `produto_imagem`
- `produto_tamanho_stock`
- `release_musical`
- `faixa`
- `encomenda`
- `encomenda_item`
- `favorito_musica`
- `mensagem_admin`
- `notificacao`

## 9. Desenvolvimento do projeto

Frontend:

- Layout responsivo.
- Navegação lateral.
- Catálogo de música, artistas e loja.
- Páginas de perfil, carrinho, checkout e compras.

Backend:

- Ligação à base de dados.
- Sessões de utilizador e admin.
- CRUD de produtos, categorias, utilizadores, mensagens e encomendas.
- Validação, CSRF e prepared statements.
- Uploads e moderação.

## 10. Funcionalidades implementadas e extras

Funcionalidades principais:

- Registo, login, logout e recuperação de password.
- Pesquisa de música, artistas e produtos.
- Carrinho, checkout, encomendas e faturas.
- Favoritos/biblioteca.
- Contacto com admin.
- Dashboard administrativa.

Extras valorizáveis:

- Área de artista.
- Upload de música e merchandising.
- Aprovação/rejeição de lançamentos e produtos.
- Notificações.
- Relatórios e estatísticas.
- Exportações e faturas PDF.
- Multi-idioma PT/EN e tema claro/escuro.

## 11. Resultados obtidos

Mostrar que o website está funcional, com dados reais de demonstração, produtos, músicas, encomendas e administração completa.

## 12. Dificuldades sentidas

Possíveis pontos a desenvolver:

- Organização da base de dados.
- Gestão de sessões diferentes para user/admin.
- Checkout e estados das encomendas.
- Uploads e imagens.
- Responsividade.
- Integração de emails e PDFs.

## 13. Resolução das dificuldades

Explicar como foram resolvidas com pesquisa, testes no XAMPP, prepared statements, organização por includes, validação de formulários e ajustes visuais.

## 14. Conclusão

Refletir sobre aprendizagens em PHP, MySQL, frontend, segurança básica, organização de projeto, documentação e apresentação de software.

## 15. Melhorias futuras

- Pagamentos reais.
- Chat em tempo real.
- Aplicação mobile.
- API pública.
- Recomendações musicais.
- Melhorias de acessibilidade e segurança.

## 16. Referências bibliográficas / webgrafia

Incluir PHP Manual, MDN Web Docs, documentação MySQL/MariaDB, Bootstrap/recursos usados se aplicável, PHPMailer e Dompdf.

## 17. Anexos

- Capturas de ecrã finais.
- DER.
- Trechos de código relevantes.
- Testes realizados.
- Guião do vídeo.
