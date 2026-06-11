# Apresentacao PAP - Outline Greenerry

Objetivo: preparar uma apresentacao clara para 10 a 15 minutos, deixando tempo para perguntas do juri.

## Slide 1 - Titulo

Greenerry - Plataforma Web de Musica, Artistas e Merchandising

Incluir nome do aluno, curso, escola, orientadora e ano letivo.

## Slide 2 - Problema e motivacao

- Artistas precisam de divulgar musica, vender produtos e acompanhar encomendas.
- Utilizadores querem descobrir musica, seguir artistas e comprar merchandising.
- A administracao precisa de controlar conteudos, encomendas, suporte e relatorios.

## Slide 3 - Objetivos

- Criar website funcional, responsivo e ligado a base de dados.
- Implementar autenticacao com verificacao por e-mail.
- Criar loja com carrinho, checkout, compras e faturas.
- Criar biblioteca musical, favoritos e seguimento de artistas.
- Criar area de artista com uploads, pedidos, mensagens e rendimento.
- Criar dashboard administrativa com moderacao e relatorios.

## Slide 4 - Tecnologias

- PHP
- MySQL/MariaDB
- HTML, CSS e JavaScript
- XAMPP e phpMyAdmin
- Composer
- PHPMailer
- Dompdf
- Git e GitHub

## Slide 5 - Estrutura do website

- Visitante: inicio, musica, artistas, loja, pesquisa e leitor.
- Cliente: perfil, carrinho, compras, favoritos, notificacoes e suporte.
- Artista: dashboard, publicar musica, produtos, pedidos, mensagens, analise e rendimento.
- Admin: dashboard, homepage, produtos, categorias, generos, lancamentos, encomendas, utilizadores, mensagens, relatorios, manutencao e definicoes.

## Slide 6 - Base de dados

Mostrar o modelo por modulos no relatorio e referir os anexos DER/MER/FNN.

- contas e seguranca: `cliente`, `admin`, verificacao e recuperacao.
- musica e biblioteca: `genero`, `release_musical`, `faixa`, playlists e favoritos.
- loja e encomendas: `categoria`, `produto`, stock, `encomenda`, itens e pagamentos.
- comunicacao/admin: notificacoes, mensagens, configuracoes e manutencao.

## Slide 7 - Funcionalidades principais

- Login, registo e codigos por e-mail.
- Pesquisa e filtros.
- Loja, detalhe de produto e Ver mais/Ver menos.
- Carrinho, checkout, compras e faturas.
- Favoritos, playlists e artistas seguidos.
- Suporte, notificacoes e mensagens.

## Slide 8 - Area de artista

- Upload de musica e produto.
- Lancamentos e merchandising pendentes de revisao.
- Pedidos com estados: pendente, em preparacao, enviado, entregue e cancelado.
- Mensagens por encomenda, semelhantes a uma conversa direta com o comprador.
- Analise e rendimento.
- Prioridade administrativa sobre conteudos bloqueados/inativados.

## Slide 9 - Administracao

- Login reservado apenas com conta admin ativa.
- Dashboard, curadoria da homepage e manutencao.
- Gestao de produtos, categorias, generos e lancamentos.
- Gestao de encomendas, utilizadores, mensagens e administradores.
- Relatorios financeiro e musical.
- Tema claro/escuro e idioma PT/EN.

## Slide 10 - Seguranca e validacao

- Passwords com hash.
- Prepared statements.
- Sessoes separadas.
- CSRF em formularios.
- Validacao de inputs.
- Permissoes no admin.
- Login administrativo apenas com conta admin ativa.

## Slide 11 - Dificuldades e solucoes

- Base de dados complexa -> DER, MER/FNN e organizacao por modulos.
- Carrinho/checkout -> testes com encomendas de demonstracao.
- Uploads -> validacao e revisao administrativa.
- Responsividade -> ajustes em paginas publicas e admin.
- E-mails/PDF -> PHPMailer e Dompdf.

## Slide 12 - Resultado final

Mostrar capturas finais do site, area de artista, mensagens por encomenda e painel administrativo.

## Slide 13 - Melhorias futuras

- Pagamentos reais.
- Chat em tempo real.
- Recomendacoes musicais.
- App mobile.
- API publica.
- Seguranca avancada.

## Slide 14 - Conclusao

- Aplicacao pratica de PHP, MySQL, frontend e documentacao.
- Experiencia com base de dados relacional, autenticacao, uploads, relatorios e permissões.
- Projeto pronto para demonstracao local e avaliacao PAP.
