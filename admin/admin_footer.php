</main>
</div>
<script>
(() => {
  const adminTranslations = {
    pt: {
      chip_kicker: 'Admin',
      nav_summary: 'Resumo',
      nav_dashboard: 'Painel',
      nav_manage: 'Gestao',
      nav_products: 'Produtos',
      nav_releases: 'Lancamentos',
      nav_messages: 'Mensagens',
      nav_password: 'Password reset',
      nav_logout: 'Sair',
      dash_title: 'Painel',
      dash_kicker: 'Greenerry Control',
      dash_hero_title: 'Tudo o que importa, num so painel.',
      stat_paid_revenue: 'Receita paga',
      stat_platform_commission: 'Comissao da plataforma',
      stat_artist_base: 'Base para artistas',
      card_active_clients: 'Clientes ativos',
      card_pending_products: 'Produtos pendentes',
      card_pending_releases: 'Lancamentos pendentes',
      card_open_messages: 'Mensagens abertas',
      card_reset_requests: 'Pedidos de reset',
      card_orders: 'Encomendas',
      box_order_states: 'Estado das encomendas',
      box_recent_performance: 'Performance recente',
      box_last_six_months: 'Ultimos 6 meses',
      empty_monthly: 'Sem atividade suficiente para desenhar a evolucao mensal.',
      box_products_review: 'Produtos por aprovar',
      box_releases_review: 'Lancamentos por aprovar',
      box_open_messages: 'Mensagens em aberto',
      box_top_artists: 'Artistas com maior retorno',
      box_by_value: 'Por valor acumulado',
      btn_view_all: 'Ver tudo',
      btn_reply: 'Responder',
      empty_pending_products: 'Sem produtos pendentes.',
      empty_pending_releases: 'Sem lancamentos pendentes.',
      empty_open_messages: 'Sem mensagens em aberto.',
      empty_top_artists: 'Ainda nao existem vendas suficientes para calcular o ranking.',
      products_title: 'Produtos',
      products_pending: 'Produtos pendentes',
      products_empty_pending: 'Sem produtos pendentes neste momento.',
      products_all: 'Todos os produtos',
      products_reason_placeholder: 'Motivo de rejeicao.',
      releases_title: 'Lancamentos',
      releases_pending: 'Lancamentos pendentes',
      releases_empty_pending: 'Sem lancamentos pendentes.',
      releases_all: 'Todos os lancamentos',
      releases_reason_placeholder: 'Motivo de rejeicao.',
      btn_approve: 'Aprovar',
      btn_reject: 'Rejeitar',
      messages_title: 'Mensagens',
      messages_kicker: 'Support Desk',
      messages_hero_title: 'Mensagens e respostas.',
      messages_inbox: 'Inbox',
      messages_empty: 'Sem mensagens recebidas.',
      messages_reply_placeholder: 'Escreve a resposta do admin.',
      messages_state_after: 'Estado apos resposta',
      messages_state_answered: 'Respondida',
      messages_state_closed: 'Fechada',
      messages_save_reply: 'Guardar resposta',
      password_title: 'Pedidos de recuperacao',
      password_kicker: 'Recovery Flow',
      password_hero_title: 'Reset requests.',
      password_requests_all: 'Pedidos registados',
      password_empty: 'Sem pedidos de recuperacao.',
      password_note_placeholder: 'Nota interna do admin.',
      password_new_state: 'Novo estado',
      password_state_review: 'Em analise',
      password_state_done: 'Concluido',
      password_state_refused: 'Recusado',
      password_update: 'Atualizar pedido'
    },
    en: {
      chip_kicker: 'Admin',
      nav_summary: 'Summary',
      nav_dashboard: 'Dashboard',
      nav_manage: 'Manage',
      nav_products: 'Products',
      nav_releases: 'Releases',
      nav_messages: 'Messages',
      nav_password: 'Password reset',
      nav_logout: 'Logout',
      dash_title: 'Dashboard',
      dash_kicker: 'Greenerry Control',
      dash_hero_title: 'Everything that matters, in one panel.',
      stat_paid_revenue: 'Paid revenue',
      stat_platform_commission: 'Platform commission',
      stat_artist_base: 'Artist base',
      card_active_clients: 'Active clients',
      card_pending_products: 'Pending products',
      card_pending_releases: 'Pending releases',
      card_open_messages: 'Open messages',
      card_reset_requests: 'Reset requests',
      card_orders: 'Orders',
      box_order_states: 'Order states',
      box_recent_performance: 'Recent performance',
      box_last_six_months: 'Last 6 months',
      empty_monthly: 'Not enough activity to draw monthly performance.',
      box_products_review: 'Products to review',
      box_releases_review: 'Releases to review',
      box_open_messages: 'Open messages',
      box_top_artists: 'Top artists',
      box_by_value: 'By total value',
      btn_view_all: 'View all',
      btn_reply: 'Reply',
      empty_pending_products: 'No pending products.',
      empty_pending_releases: 'No pending releases.',
      empty_open_messages: 'No open messages.',
      empty_top_artists: 'Not enough sales yet to calculate ranking.',
      products_title: 'Products',
      products_pending: 'Pending products',
      products_empty_pending: 'No pending products right now.',
      products_all: 'All products',
      products_reason_placeholder: 'Rejection reason.',
      releases_title: 'Releases',
      releases_pending: 'Pending releases',
      releases_empty_pending: 'No pending releases.',
      releases_all: 'All releases',
      releases_reason_placeholder: 'Rejection reason.',
      btn_approve: 'Approve',
      btn_reject: 'Reject',
      messages_title: 'Messages',
      messages_kicker: 'Support Desk',
      messages_hero_title: 'Messages and replies.',
      messages_inbox: 'Inbox',
      messages_empty: 'No messages received.',
      messages_reply_placeholder: 'Write the admin reply.',
      messages_state_after: 'State after reply',
      messages_state_answered: 'Answered',
      messages_state_closed: 'Closed',
      messages_save_reply: 'Save reply',
      password_title: 'Recovery requests',
      password_kicker: 'Recovery Flow',
      password_hero_title: 'Reset requests.',
      password_requests_all: 'Registered requests',
      password_empty: 'No recovery requests.',
      password_note_placeholder: 'Internal admin note.',
      password_new_state: 'New state',
      password_state_review: 'In review',
      password_state_done: 'Completed',
      password_state_refused: 'Refused',
      password_update: 'Update request'
    }
  };

  const currentLang = localStorage.getItem('g_lang') || 'pt';

  function applyAdminLang(lang) {
    const strings = adminTranslations[lang] || adminTranslations.pt;
    document.documentElement.lang = lang;

    document.querySelectorAll('[data-admin-t]').forEach((el) => {
      const key = el.dataset.adminT;
      if (strings[key] !== undefined) el.textContent = strings[key];
    });

    document.querySelectorAll('[data-admin-tp]').forEach((el) => {
      const key = el.dataset.adminTp;
      if (strings[key] !== undefined) el.placeholder = strings[key];
    });

    document.querySelectorAll('.admin-lang button').forEach((button) => {
      button.classList.toggle('on', button.dataset.l === lang);
    });
  }

  document.querySelectorAll('.admin-lang button').forEach((button) => {
    button.addEventListener('click', () => {
      localStorage.setItem('g_lang', button.dataset.l);
      applyAdminLang(button.dataset.l);
    });
  });

  applyAdminLang(currentLang);
})();
</script>
</body>
</html>
