</main>
</div>
<script>
(() => {
  const adminTranslations = {
    pt: {
      chip_kicker: 'Admin',
      theme_label: 'Tema',
      nav_summary: 'Resumo',
      nav_dashboard: 'Painel',
      nav_manage: 'Gestao',
      nav_products: 'Produtos',
      nav_categories: 'Categorias',
      nav_releases: 'Lancamentos',
      nav_operations: 'Operacoes',
      nav_orders: 'Encomendas',
      nav_users: 'Utilizadores',
      nav_system: 'Sistema',
      nav_reports: 'Relatorios',
      nav_settings: 'Definicoes',
      nav_messages: 'Mensagens',
      nav_password: 'Password reset',
      nav_logout: 'Sair',
      dash_title: 'Painel',
      dash_kicker: 'Greenerry Control',
      dash_hero_title: 'Tudo o que importa, num so painel.',
      stat_paid_revenue: 'Receita paga',
      stat_attention: 'Por rever',
      stat_paid_orders: 'Encomendas pagas',
      stat_average_order: 'Ticket medio',
      stat_platform_commission: 'Comissao da plataforma',
      stat_artist_base: 'Base para artistas',
      card_active_clients: 'Clientes ativos',
      card_active_categories: 'Categorias ativas',
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
      box_catalog_health: 'Catalogo',
      box_by_value: 'Por valor acumulado',
      dash_need_review: 'precisam de revisao',
      dash_need_reply: 'por responder',
      dash_total_registered: 'registadas',
      dash_recent_orders: 'Encomendas recentes',
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
      products_image: 'Imagem',
      products_no_image: 'Sem imagem',
      products_reason_placeholder: 'Motivo de rejeicao.',
      releases_title: 'Lancamentos',
      releases_pending: 'Lancamentos pendentes',
      releases_empty_pending: 'Sem lancamentos pendentes.',
      releases_all: 'Todos os lancamentos',
      releases_audio: 'Audio',
      releases_listen: 'Ouvir',
      releases_tracks: 'Faixas',
      releases_no_tracks: 'Sem faixas',
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
      state_pending: 'Pendentes',
      state_active: 'Ativo',
      state_approved: 'Aprovados',
      state_rejected: 'Rejeitados',
      state_inactive: 'Inativos',
      state_in_review: 'Em revisao',
      state_cancelled: 'Canceladas',
      state_blocked: 'Bloqueados',
      label_artist: 'Artista',
      label_price: 'Preco',
      label_commission: 'Comissao',
      label_total_stock: 'Stock total',
      label_tracks: 'Faixas',
      label_release_date: 'Lancamento',
      btn_deactivate: 'Inativar',
      btn_reactivate: 'Reativar',
      messages_open: 'Em aberto',
      messages_answered: 'Respondidas',
      messages_closed: 'Fechadas',
      messages_current_reply: 'Resposta atual',
      password_admin_note: 'Nota do admin',
      password_requests_all: 'Pedidos registados',
      password_empty: 'Sem pedidos de recuperacao.',
      password_note_placeholder: 'Nota interna do admin.',
      password_new_state: 'Novo estado',
      password_state_review: 'Em analise',
      password_state_done: 'Concluido',
      password_state_refused: 'Recusado',
      password_update: 'Atualizar pedido',
      categories_title: 'Categorias',
      categories_new_kicker: 'Catalogo',
      categories_new_title: 'Nova categoria',
      categories_name: 'Nome',
      categories_description: 'Descricao',
      categories_sizing: 'Tamanhos',
      categories_uses_sizes: 'Usa tamanhos',
      categories_state: 'Estado',
      categories_create: 'Criar categoria',
      categories_sizes_kicker: 'Tamanhos',
      categories_sizes_title: 'Gerir tamanhos',
      categories_all: 'Categorias existentes',
      categories_products_count: 'produtos',
      categories_pending_count: 'pendentes',
      categories_save: 'Guardar',
      sizes_code: 'Codigo',
      sizes_label: 'Etiqueta',
      sizes_order: 'Ordem',
      sizes_create: 'Criar',
      orders_title: 'Encomendas',
      orders_total: 'Total',
      orders_paid_value: 'Valor pago',
      orders_recent: 'Encomendas recentes',
      orders_customer: 'Cliente',
      orders_items: 'Itens',
      orders_total_value: 'Total',
      orders_order_state: 'Estado',
      orders_payment_state: 'Pagamento',
      orders_action: 'Acao',
      orders_locked: 'Bloqueada',
      users_title: 'Utilizadores',
      users_active: 'Ativos',
      users_artists: 'Artistas',
      users_all: 'Todos os utilizadores',
      users_name: 'Nome',
      reports_title: 'Relatorios',
      reports_export_excel: 'Exportar Excel',
      reports_export_label: 'Relatorio executivo',
      reports_export_title: 'Excel profissional',
      reports_export_desc: 'Resumo, receita mensal, artistas, categorias e encomendas recentes.',
      reports_money_chart: 'Grafico do dinheiro',
      reports_blocked_value: 'Cancelado/reembolsado',
      reports_category_revenue: 'Receita por categoria',
      reports_empty_categories: 'Ainda nao existem vendas por categoria.',
      orders_empty: 'Sem encomendas registadas.',
      admin_search_placeholder: 'Pesquisar...',
      admin_no_results: 'Sem resultados para essa pesquisa.',
      settings_title: 'Definicoes',
      settings_public_contact: 'Contacto publico',
      settings_site_name: 'Nome do site',
      settings_email: 'Email do site',
      settings_phone: 'Telefone do site',
      settings_hours: 'Horario de suporte',
      settings_brand: 'Marca e links',
      settings_footer_note: 'Texto do footer',
      settings_business: 'Loja',
      settings_commission: 'Comissao padrao (%)',
      settings_shipping_note: 'Nota da loja',
      settings_live_kicker: 'User side',
      settings_live_title: 'As alteracoes aparecem no footer publico.',
      settings_save: 'Guardar definicoes'
    },
    en: {
      chip_kicker: 'Admin',
      theme_label: 'Theme',
      nav_summary: 'Summary',
      nav_dashboard: 'Dashboard',
      nav_manage: 'Manage',
      nav_products: 'Products',
      nav_categories: 'Categories',
      nav_releases: 'Releases',
      nav_operations: 'Operations',
      nav_orders: 'Orders',
      nav_users: 'Users',
      nav_system: 'System',
      nav_reports: 'Reports',
      nav_settings: 'Settings',
      nav_messages: 'Messages',
      nav_password: 'Password reset',
      nav_logout: 'Logout',
      dash_title: 'Dashboard',
      dash_kicker: 'Greenerry Control',
      dash_hero_title: 'Everything that matters, in one panel.',
      stat_paid_revenue: 'Paid revenue',
      stat_attention: 'To review',
      stat_paid_orders: 'Paid orders',
      stat_average_order: 'Average order',
      stat_platform_commission: 'Platform commission',
      stat_artist_base: 'Artist base',
      card_active_clients: 'Active clients',
      card_active_categories: 'Active categories',
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
      box_catalog_health: 'Catalog',
      box_by_value: 'By total value',
      dash_need_review: 'need review',
      dash_need_reply: 'to reply',
      dash_total_registered: 'registered',
      dash_recent_orders: 'Recent orders',
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
      products_image: 'Image',
      products_no_image: 'No image',
      products_reason_placeholder: 'Rejection reason.',
      releases_title: 'Releases',
      releases_pending: 'Pending releases',
      releases_empty_pending: 'No pending releases.',
      releases_all: 'All releases',
      releases_audio: 'Audio',
      releases_listen: 'Listen',
      releases_tracks: 'Tracks',
      releases_no_tracks: 'No tracks',
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
      state_pending: 'Pending',
      state_active: 'Active',
      state_approved: 'Approved',
      state_rejected: 'Rejected',
      state_inactive: 'Inactive',
      state_in_review: 'In review',
      state_cancelled: 'Cancelled',
      state_blocked: 'Blocked',
      label_artist: 'Artist',
      label_price: 'Price',
      label_commission: 'Commission',
      label_total_stock: 'Total stock',
      label_tracks: 'Tracks',
      label_release_date: 'Release date',
      btn_deactivate: 'Deactivate',
      btn_reactivate: 'Reactivate',
      messages_open: 'Open',
      messages_answered: 'Answered',
      messages_closed: 'Closed',
      messages_current_reply: 'Current reply',
      password_admin_note: 'Admin note',
      password_requests_all: 'Registered requests',
      password_empty: 'No recovery requests.',
      password_note_placeholder: 'Internal admin note.',
      password_new_state: 'New state',
      password_state_review: 'In review',
      password_state_done: 'Completed',
      password_state_refused: 'Refused',
      password_update: 'Update request',
      categories_title: 'Categories',
      categories_new_kicker: 'Catalog',
      categories_new_title: 'New category',
      categories_name: 'Name',
      categories_description: 'Description',
      categories_sizing: 'Sizing',
      categories_uses_sizes: 'Uses sizes',
      categories_state: 'State',
      categories_create: 'Create category',
      categories_sizes_kicker: 'Sizes',
      categories_sizes_title: 'Manage sizes',
      categories_all: 'Existing categories',
      categories_products_count: 'products',
      categories_pending_count: 'pending',
      categories_save: 'Save',
      sizes_code: 'Code',
      sizes_label: 'Label',
      sizes_order: 'Order',
      sizes_create: 'Create',
      orders_title: 'Orders',
      orders_total: 'Total',
      orders_paid_value: 'Paid value',
      orders_recent: 'Recent orders',
      orders_customer: 'Customer',
      orders_items: 'Items',
      orders_total_value: 'Total',
      orders_order_state: 'State',
      orders_payment_state: 'Payment',
      orders_action: 'Action',
      orders_locked: 'Locked',
      users_title: 'Users',
      users_active: 'Active',
      users_artists: 'Artists',
      users_all: 'All users',
      users_name: 'Name',
      reports_title: 'Reports',
      reports_export_excel: 'Export Excel',
      reports_export_label: 'Executive report',
      reports_export_title: 'Professional Excel',
      reports_export_desc: 'Summary, monthly revenue, artists, categories, and recent orders.',
      reports_money_chart: 'Money chart',
      reports_blocked_value: 'Cancelled/refunded',
      reports_category_revenue: 'Revenue by category',
      reports_empty_categories: 'No category sales yet.',
      orders_empty: 'No registered orders.',
      admin_search_placeholder: 'Search...',
      admin_no_results: 'No results for that search.',
      settings_title: 'Settings',
      settings_public_contact: 'Public contact',
      settings_site_name: 'Site name',
      settings_email: 'Site email',
      settings_phone: 'Site phone',
      settings_hours: 'Support hours',
      settings_brand: 'Brand and links',
      settings_footer_note: 'Footer text',
      settings_business: 'Shop',
      settings_commission: 'Default commission (%)',
      settings_shipping_note: 'Shop note',
      settings_live_kicker: 'User side',
      settings_live_title: 'Changes appear in the public footer.',
      settings_save: 'Save settings'
    }
  };

  const currentLang = localStorage.getItem('g_lang') || 'pt';
  const themeButton = document.getElementById('theme-toggle');
  const currentTheme = localStorage.getItem('g_theme') || document.documentElement.dataset.theme || 'dark';
  document.documentElement.dataset.theme = currentTheme;
  themeButton?.setAttribute('aria-pressed', currentTheme === 'light' ? 'true' : 'false');
  themeButton?.addEventListener('click', () => {
    const next = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';
    document.documentElement.dataset.theme = next;
    localStorage.setItem('g_theme', next);
    document.cookie = `g_theme=${encodeURIComponent(next)}; path=/; max-age=31536000; samesite=lax`;
    themeButton.setAttribute('aria-pressed', next === 'light' ? 'true' : 'false');
  });

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
      document.cookie = `g_lang=${encodeURIComponent(button.dataset.l)}; path=/; max-age=31536000; samesite=lax`;
      applyAdminLang(button.dataset.l);
    });
  });

  applyAdminLang(currentLang);

  const adminSidebar = document.getElementById('admin-sidebar');
  const adminMenuButton = document.getElementById('admin-mobile-menu');
  const adminOverlay = document.getElementById('admin-mobile-overlay');

  function setAdminSidebar(open) {
    adminSidebar?.classList.toggle('mobile-open', open);
    adminOverlay?.classList.toggle('visible', open);
    adminMenuButton?.classList.toggle('is-open', open);
    adminMenuButton?.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.style.overflow = open ? 'hidden' : '';
  }

  adminMenuButton?.addEventListener('click', () => {
    setAdminSidebar(!adminSidebar?.classList.contains('mobile-open'));
  });

  adminOverlay?.addEventListener('click', () => setAdminSidebar(false));

  adminSidebar?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 768) setAdminSidebar(false);
    });
  });

  window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setAdminSidebar(false);
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 768) setAdminSidebar(false);
  });

  document.querySelectorAll('[data-admin-search]').forEach((input) => {
    const scope = document.getElementById(input.dataset.adminSearch);
    if (!scope) return;

    const items = Array.from(scope.querySelectorAll('tbody tr, .admin-card-list > *, .simple-list > *, .admin-size-row, .admin-bar-row'));
    let empty = scope.querySelector('.admin-empty-filter');
    if (!empty) {
      empty = document.createElement('p');
      empty.className = 'admin-empty-filter';
      empty.dataset.adminT = 'admin_no_results';
      empty.textContent = (adminTranslations[currentLang] || adminTranslations.pt).admin_no_results;
      scope.appendChild(empty);
    }

    input.addEventListener('input', () => {
      const needle = input.value.trim().toLowerCase();
      let visible = 0;
      items.forEach((item) => {
        const match = needle === '' || item.textContent.toLowerCase().includes(needle);
        item.style.display = match ? '' : 'none';
        if (match) visible += 1;
      });
      empty.style.display = visible === 0 && items.length > 0 ? 'block' : 'none';
    });
  });
})();
</script>
</body>
</html>
