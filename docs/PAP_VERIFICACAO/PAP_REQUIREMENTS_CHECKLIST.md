# Greenerry - Checklist PAP 2026

Este ficheiro resume o que ainda deve ser confirmado antes da entrega final da PAP.

## Entrega obrigatoria

- [ ] Projeto de software totalmente funcional numa Pen Drive.
- [ ] Relatorio final da PAP impresso, encadernado e a cores.
- [ ] Manual Tecnico impresso, encadernado e a cores.
- [ ] Manual de Utilizacao em video, com visitante, cliente, artista e admin, num unico MP4.
- [ ] DER, MER e FNN anexados.
- [ ] Submissao no PBX da escola.
- [ ] Submissao na plataforma Teams.
- [ ] Codigo final enviado para GitHub.

Data limite indicada: 22 de junho de 2026, ate as 22h00.

## Requisitos do website

| Requisito esperado | Estado em Greenerry | Onde demonstrar |
| --- | --- | --- |
| Website funcional e navegavel | Implementado | `pages/index.php`, `pages/music.php`, `pages/shop.php` |
| Logo/identidade visual em tema claro e escuro | Implementado | Header/sidebar publica e admin |
| Registo, login e verificacao por e-mail | Implementado | `pages/registar.php`, `pages/login.php`, `pages/verify_email.php` |
| Recuperacao de palavra-passe por codigo | Implementado | `pages/reset_password.php` |
| Perfil do utilizador | Implementado | `pages/profile.php` |
| Pesquisa de musicas, artistas e produtos | Implementado | `pages/music.php`, `pages/artists.php`, `pages/shop.php` |
| Consulta de produtos e detalhe | Implementado | `pages/shop.php`, `pages/produto.php` |
| Carrinho e checkout | Implementado | `pages/cart.php`, `pages/checkout.php` |
| Historico de compras e faturas | Implementado | `pages/my_orders.php`, `pages/receipt.php` |
| Favoritos, playlists e artistas seguidos | Implementado | `pages/favourites.php` e leitor |
| Suporte e notificacoes | Implementado | `pages/contact_admin.php`, `pages/notifications.php` |
| Area de artista | Implementado | dashboard, uploads, analise, pedidos, rendimento |
| Mensagens por encomenda | Implementado | `pages/orders.php`, `pages/artist_messages.php`, `admin/orders.php` |
| Dashboard administrativa | Implementado | `admin/dashboard.php` |
| Gestao de produtos, categorias, generos e lancamentos | Implementado | `admin/products.php`, `admin/categories.php`, `admin/genres.php`, `admin/releases.php` |
| Gestao de encomendas e estados | Implementado | `admin/orders.php` |
| Gestao de utilizadores, mensagens e administradores | Implementado | `admin/users.php`, `admin/messages.php`, `admin/admins.php` |
| Relatorios e estatisticas | Implementado | `admin/reports.php`, `admin/music.php`, `pages/revenue.php` |
| Prioridade administrativa sobre conteudos de artista | Implementado | produtos/lancamentos bloqueados pelo admin nao podem ser reativados pelo artista |

## Entregaveis documentais

- Relatorio final: `docs/PAP_ENTREGA/Relatorio_PAP_Greenerry_Srijan_Gautam_ATUALIZADO.docx`
- Manual Tecnico: `docs/PAP_ENTREGA/Manual_Tecnico_Greenerry_Srijan_Gautam.docx`
- Roteiro do video: `docs/PAP_VERIFICACAO/PAP_VIDEO_ROTEIRO.md`
- Checklist PAP: `docs/PAP_VERIFICACAO/PAP_REQUIREMENTS_CHECKLIST.md`
- Outline da apresentacao: `docs/PAP_VERIFICACAO/PAP_APRESENTACAO_OUTLINE.md`
- DER: `docs/DER/DER_GREENERRY.pdf`
- MER/FNN: `docs/MER_FNN/greenerry_mer_fnn.pdf` e `docs/MER_FNN/greenerry_mer_fnn.drawio`

## Contas de demonstracao

Admin principal:

- Email: `greenerry333@gmail.com`
- Password: `Srijan123@`
- Nota: o login de admin usa apenas e-mail e palavra-passe de uma conta administrativa ativa.

Base de dados local:

- Host: `localhost`
- Utilizador: `root`
- Password: vazia
- Base de dados: `greenerry`
- Ficheiro de importacao: `greenerry.sql`

## Fecho antes da entrega

- [ ] Confirmar que `greenerry.sql` importa sem erros em XAMPP.
- [ ] Confirmar Apache e MySQL ativos.
- [ ] Fazer ronda visitante -> cliente -> artista -> admin.
- [ ] Confirmar login/admin apenas com e-mail e palavra-passe.
- [ ] Capturar screenshots finais apenas quando o site estiver fechado.
- [ ] Substituir os espacos reservados do relatorio pelas capturas finais, se necessario.
- [ ] Rever margens, tabelas, legendas, indices e anexos.
- [ ] Gravar MP4 final com ecra completo, cursor visivel e explicacao oral.
