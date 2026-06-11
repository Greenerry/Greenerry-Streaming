# Estrutura do Relatorio da PAP - Greenerry

## 1. Capa

Usar capa profissional com escola, Republica Portuguesa, curso, titulo do projeto, nome do aluno, orientadora e ano letivo.

Titulo recomendado: **Greenerry - Plataforma Web de Musica, Artistas e Merchandising**

## 2. Elementos iniciais

- Resumo.
- Indice automatico.
- Indice de figuras.
- Lista de siglas.

## 3. Introducao e enquadramento

Apresentar a Greenerry como plataforma Web onde visitantes descobrem musica, artistas e produtos; clientes compram e guardam conteudos; artistas gerem publicacoes, pedidos e rendimento; administradores moderam e acompanham a plataforma.

## 4. Problema, publico-alvo e objetivos

- Problema: fragmentacao entre musica, loja, comunicacao e gestao de artistas.
- Publico-alvo: visitantes, clientes, artistas independentes e administradores.
- Objetivo geral: criar website funcional, responsivo e ligado a base de dados.
- Objetivos especificos: autenticacao, verificacao por e-mail, loja, carrinho, compras, biblioteca, area de artista, mensagens por encomenda, relatorios e admin.

## 5. Metodologia

Explicar pesquisa, planeamento, divisao por modulos, construcao por etapas, testes em XAMPP, seed de dados de demonstracao, Git/GitHub e preparacao dos anexos.

## 6. Tecnologias utilizadas

- HTML, CSS e JavaScript.
- PHP.
- MySQL/MariaDB.
- XAMPP e phpMyAdmin.
- Composer.
- PHPMailer.
- Dompdf.
- Git e GitHub.

## 7. Estrutura da base de dados

Incluir a figura limpa por modulos no relatorio e remeter o detalhe tecnico para os anexos:

- `docs/DER/DER_GREENERRY.pdf`
- `docs/MER_FNN/greenerry_mer_fnn.pdf`
- `docs/MER_FNN/greenerry_mer_fnn.drawio`

Explicar contas/seguranca, musica/biblioteca, loja/encomendas e comunicacao/admin.

## 8. Desenvolvimento do projeto

Separar por secoes:

- Frontend publico: inicio, musica, artistas, loja, produto e leitor.
- Backend: ligacao a base de dados, sessoes, permissoes, validacoes e CSRF.
- Cliente autenticado: perfil, carrinho, compras, suporte, notificacoes, favoritos e playlists.
- Area de artista: dashboard, publicacoes, pedidos, mensagens por encomenda, analise e rendimento.
- Administracao: dashboard, produtos, categorias, generos, lancamentos, encomendas, utilizadores, mensagens, relatorios, manutencao, admins e definicoes.

## 9. Capturas de ecra

No relatorio atual, as capturas de interface devem ficar como espacos reservados identificados. As screenshots finais devem ser inseridas apenas depois de o website estar fechado visualmente.

Exemplo: `Espaco reservado para captura final: Dashboard do artista`.

## 10. Seguranca e regras importantes

- Login/admin apenas com e-mail e palavra-passe de admin ativo.
- Verificacao e recuperacao por codigo enviado por e-mail.
- Passwords com hash.
- Prepared statements e CSRF.
- Prioridade administrativa: produtos/lancamentos bloqueados ou inativados pelo admin nao podem ser reativados pelo artista.

## 11. Testes

Documentar testes por fluxo:

- Visitante.
- Cliente.
- Artista.
- Administrador.
- Carrinho/checkout/compras.
- Mensagens por encomenda.
- Relatorios.
- Tema claro/escuro.

## 12. Resultados, dificuldades e conclusao

Apresentar resultado final, dificuldades tecnicas, resolucao das dificuldades, aprendizagens e melhorias futuras.

## 13. Anexos

- DER.
- MER/FNN.
- Manual tecnico.
- Roteiro do video.
- Checklist de entrega.
- Capturas finais, se a escola pedir anexos visuais separados.
