<?php
function current_lang(): string
{
    $lang = strtolower((string)($_COOKIE['g_lang'] ?? $_SESSION['g_lang'] ?? 'pt'));
    return $lang === 'en' ? 'en' : 'pt';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf_request(): ?string
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return null;
    }

    $submitted = (string)($_POST['csrf_token'] ?? '');
    $sessionToken = (string)($_SESSION['csrf_token'] ?? '');

    if ($submitted === '' || $sessionToken === '' || !hash_equals($sessionToken, $submitted)) {
        return tr('error.invalid_session');
    }

    return null;
}

function verify_csrf_token(?string $token): bool
{
    $sessionToken = (string)($_SESSION['csrf_token'] ?? '');
    return $token !== null && $token !== '' && $sessionToken !== '' && hash_equals($sessionToken, $token);
}

function tr(string $key, array $replace = []): string
{
    static $strings = [
        'pt' => [
            'status.approved' => 'Aprovado',
            'status.rejected' => 'Rejeitado',
            'status.inactive' => 'Inativo',
            'status.active' => 'Ativo',
            'status.pending' => 'Pendente',
            'status.preparing' => 'Em preparação',
            'status.sent' => 'Enviado',
            'status.delivered' => 'Entregue',
            'status.cancelled' => 'Cancelado',
            'status.open' => 'Aberta',
            'status.answered' => 'Respondida',
            'status.closed' => 'Fechada',
            'status.review' => 'Em análise',
            'status.done' => 'Concluído',
            'status.refused' => 'Recusado',
            'status.blocked' => 'Bloqueado',
            'release_type.single' => 'Single',
            'release_type.ep' => 'EP',
            'release_type.album' => 'Álbum',
            'category.t_shirt' => 'T-Shirt',
            'category.hoodie' => 'Hoodie',
            'category.vinyl' => 'Vinil',
            'category.cd' => 'CD',
            'category.poster' => 'Poster',
            'category.accessory' => 'Acessório',
            'payment.pending' => 'Pendente',
            'payment.paid' => 'Pago',
            'payment.failed' => 'Falhado',
            'payment.refunded' => 'Reembolsado',
            'payment.card' => 'Cartão',
            'payment.mbway' => 'MB Way',
            'payment.transfer' => 'Transferência',
            'error.required_name' => 'O nome é obrigatório.',
            'error.short_name' => 'O nome deve ter pelo menos 2 caracteres.',
            'error.long_name' => 'O nome não pode ter mais de 120 caracteres.',
            'error.invalid_name_chars' => 'O nome deve conter apenas letras, espaços, hifens ou apóstrofes.',
            'error.required_email' => 'O email é obrigatório.',
            'error.invalid_email' => 'O email introduzido não e válido.',
            'error.long_email' => 'O email não pode ter mais de 150 caracteres.',
            'error.required_password' => 'A palavra-passe é obrigatória.',
            'error.short_password' => 'A palavra-passe deve ter pelo menos 8 caracteres.',
            'error.invalid_access_type' => 'Tipo de acesso inválido.',
            'error.invalid_session' => 'A sessão expirou. Tenta novamente.',
            'error.invalid_admin_credentials' => 'Credenciais de administrador invalidas.',
            'error.use_admin_login' => 'Usa a opção Administração para entrar como admin.',
            'error.invalid_login' => 'Email ou palavra-passe incorretos.',
            'error.account_inactive' => 'A conta não esta ativa. Se acabaste de criar a conta, verifica o teu email.',
            'error.password_mismatch' => 'As palavras-passe não coincidem.',
            'error.account_exists' => 'Já existe uma conta com esse email.',
            'error.create_account' => 'Não foi possível criar a conta. Tenta novamente.',
            'success.account_created' => 'Conta criada. Verifica o teu email para ativar a conta.',
            'error.verification_email_send' => 'Não foi possível enviar o email de verificação. Confirma as definições de email.',
            'success.email_verified' => 'Email verificado com sucesso. Já podes entrar.',
            'error.email_verify_invalid' => 'Link de verificação inválido.',
            'error.email_verify_expired' => 'O link de verificação expirou. Cria a conta novamente ou pede um novo link.',
            'email.verify_subject' => 'Verifica o teu email Greenerry',
            'email.verify_body' => "Olá :name,\n\nClica no link abaixo para ativar a tua conta Greenerry:\n\n:link\n\nEste link expira em 24 horas.\n\nGreenerry",
            'success.profile_updated' => 'Perfil atualizado com sucesso.',
            'error.subject_required' => 'O assunto é obrigatório.',
            'error.subject_long' => 'O assunto não pode ter mais de 160 caracteres.',
            'error.message_required' => 'A mensagem é obrigatória.',
            'error.message_short' => 'A mensagem deve ter pelo menos 10 caracteres.',
            'success.message_sent' => 'Mensagem enviada ao admin com sucesso.',
            'error.message_send' => 'Não foi possível enviar a mensagem.',
            'messages_reply_label' => 'Resposta do admin',
            'error.account_not_found' => 'Não existe nenhuma conta com esse email.',
            'success.reset_request' => 'Se existir uma conta ativa com esse email, vais receber um link para mudar a palavra-passe.',
            'error.reset_request_save' => 'Não foi possível enviar o link de recuperação.',
            'success.password_reset' => 'Palavra-passe alterada com sucesso. Já podes entrar.',
            'error.reset_invalid' => 'Link de recuperação inválido.',
            'error.reset_expired' => 'O link de recuperação expirou. Pede um novo link.',
            'email.welcome_subject' => 'Bem-vindo ao Greenerry',
            'email.welcome_body' => "Olá :name,\n\nA tua conta Greenerry foi criada com sucesso.\n\nJa podes entrar, ouvir música, seguir artistas e publicar os teus projetos.\n\nGreenerry",
            'email.reset_request_subject' => 'Mudar palavra-passe Greenerry',
            'email.reset_request_body' => "Olá :name,\n\nClica no link abaixo para mudar a tua palavra-passe:\n\n:link\n\nEste link expira em 1 hora.\n\nGreenerry",
            'email.password_changed_subject' => 'A tua palavra-passe foi alterada',
            'email.password_changed_body' => "Olá :name,\n\nA tua palavra-passe Greenerry foi alterada com sucesso.\n\nSe não foste tu, contacta o suporte.\n\nGreenerry",
            'email.test_subject' => 'Teste de email Greenerry',
            'email.test_body' => "Este e um email de teste enviado por :site.\n\nSe recebeste isto, o SMTP esta a funcionar.",
            'email.order_subject' => 'Confirmação da encomenda #:id',
            'email.order_body' => "Olá :name,\n\nA tua encomenda #:id foi criada com sucesso.\n\nItens:\n:items\n\nTotal: :total\n\nRecibo: :receipt\n\nGreenerry",
            'email.artist_sale_subject' => 'Vendeste um produto no Greenerry',
            'email.artist_sale_body' => "Olá :name,\n\nRecebeste uma nova venda na encomenda #:id.\n\nItens:\n:items\n\nValor para artista: :value\n\nGreenerry",
            'email.product_approved_subject' => 'Produto aprovado',
            'email.product_approved_body' => "Olá :name,\n\nO teu produto \":product\" foi aprovado e já pode aparecer na loja.\n\nVer produto: :link\n\nGreenerry",
            'email.product_rejected_subject' => 'Produto rejeitado',
            'email.product_rejected_body' => "Olá :name,\n\nO teu produto \":product\" foi rejeitado.\n\nMotivo: :reason\n\nPodes corrigir e tentar novamente.\n\nGreenerry",
            'email.release_approved_subject' => 'Lançamento aprovado',
            'email.release_approved_body' => "Olá :name,\n\nO teu lançamento \":release\" foi aprovado e já pode aparecer na música.\n\nVer lançamento: :link\n\nGreenerry",
            'email.release_rejected_subject' => 'Lançamento rejeitado',
            'email.release_rejected_body' => "Olá :name,\n\nO teu lançamento \":release\" foi rejeitado.\n\nMotivo: :reason\n\nPodes corrigir e tentar novamente.\n\nGreenerry",
            'maintenance.title' => 'Em manutenção',
            'maintenance.text' => 'Esta área esta temporariamente indisponivel. Volta a tentar mais tarde.',
            'maintenance.back' => 'Voltar ao inicio',
            'error.invalid_postal' => 'Código postal inválido. Usa o formato 0000-000.',
            'error.invalid_country' => 'Seleciona um pais válido.',
            'error.invalid_phone' => 'Telefone inválido. Usa 912345678 ou +351 912345678.',
            'error.invalid_nif' => 'NIF inválido. Deve ter 9 digitos.',
            'error.invalid_payment_method' => 'Método de pagamento inválido.',
            'error.required_recipient' => 'Indica o nome do destinatário.',
            'error.short_address' => 'A morada deve ter pelo menos 5 caracteres.',
            'error.required_city' => 'Indica a cidade.',
            'error.invalid_city_chars' => 'A cidade deve conter apenas letras, espaços, hifens ou apóstrofes.',
            'error.empty_cart' => 'O carrinho esta vazio.',
            'error.invalid_cart_product' => 'Existe um produto inválido no carrinho.',
            'error.product_unavailable' => 'Um dos produtos já não esta disponível.',
            'error.own_product_purchase' => 'Não podes comprar os teus próprios produtos.',
            'error.invalid_size_for_product' => 'Seleciona um tamanho válido para :product.',
            'error.insufficient_size_stock' => 'Não há stock suficiente para :product no tamanho :size.',
            'error.insufficient_stock' => 'Não há stock suficiente para :product.',
            'success.order_created' => 'Encomenda registada com sucesso.',
            'error.order_create' => 'Não foi possível concluir a encomenda.',
            'error.order_update' => 'Não foi possível atualizar a encomenda.',
            'success.order_artist_updated' => 'Estado da encomenda atualizado.',
            'error.order_no_editable_items' => 'Esta encomenda já não tem itens editáveis.',
            'error.cancelled_order_locked' => 'Esta encomenda foi cancelada e já não pode ser alterada.',
            'error.admin_blocked_item' => 'O admin bloqueou este item. Não podes ativá-lo.',
            'error.user_update' => 'Não foi possível atualizar o utilizador.',
            'error.settings_update' => 'Não foi possível guardar as definições.',
            'error.test_email_send' => 'Não foi possível enviar o email de teste.',
            'success.order_admin_updated' => 'Encomenda atualizada com sucesso.',
            'success.user_state_updated' => 'Estado do utilizador atualizado.',
            'success.settings_updated' => 'Definicoes atualizadas com sucesso.',
            'success.test_email_sent' => 'Email de teste enviado com sucesso.',
            'error.release_title_required' => 'O título do lançamento é obrigatório.',
            'error.release_type_invalid' => 'Tipo de lançamento inválido.',
            'error.release_date_invalid' => 'A data de lançamento não é válida.',
            'error.image_format' => 'A imagem deve estar em JPG, PNG ou WEBP.',
            'error.cover_format' => 'A capa deve estar em JPG, PNG ou WEBP.',
            'error.audio_required' => 'Tens de enviar o ficheiro de audio.',
            'error.audio_format' => 'Formato de audio não suportado.',
            'error.track_incomplete' => 'Cada faixa tem de ter titulo e ficheiro de audio.',
            'error.track_format' => 'Foi encontrado um formato de audio não suportado.',
            'error.track_required' => 'Adiciona pelo menos uma faixa ao lançamento.',
            'success.release_sent' => 'Lançamento enviado para aprovação do admin.',
            'success.release_updated' => 'Lançamento atualizado e enviado para revisão.',
            'success.release_deleted' => 'Lançamento eliminado com sucesso.',
            'error.release_save' => 'Não foi possível guardar o lançamento.',
            'error.release_delete' => 'Não foi possível eliminar o lançamento.',
            'confirm.release_delete' => 'Eliminar este lançamento? Isto também remove os ficheiros de áudio e a capa.',
            'error.product_name_required' => 'O nome do produto é obrigatório.',
            'error.price_positive' => 'O preço tem de ser superior a zero.',
            'error.category_required' => 'Seleciona uma categoria.',
            'error.category_name_short' => 'O nome da categoria deve ter pelo menos 2 caracteres.',
            'error.category_exists' => 'Já existe uma categoria com esse nome.',
            'error.size_code_required' => 'O código do tamanho é obrigatório.',
            'error.size_exists' => 'Já existe um tamanho com esse codigo.',
            'error.stock_negative' => 'O stock não pode ser negativo.',
            'error.category_no_sizes' => 'Esta categoria não usa tamanhos.',
            'error.size_or_stock_required' => 'Escolhe pelo menos um tamanho ou indica stock num tamanho.',
            'error.size_stock_required' => 'Indica stock para pelo menos um dos tamanhos escolhidos.',
            'success.product_sent' => 'Produto enviado para aprovação do admin.',
            'success.category_created' => 'Categoria criada.',
            'success.category_updated' => 'Categoria atualizada.',
            'success.category_state_updated' => 'Estado da categoria atualizado.',
            'success.size_created' => 'Tamanho criado.',
            'success.size_updated' => 'Tamanho atualizado.',
            'error.product_save' => 'Não foi possível registar o produto.',
            'success.admin_reply_sent' => 'Resposta enviada com sucesso.',
            'success.admin_created' => 'Admin criado com sucesso.',
            'success.admin_updated' => 'Admin atualizado com sucesso.',
            'success.notification_read' => 'Notificação marcada como lida.',
            'success.notifications_read' => 'Notificações marcadas como lidas.',
            'error.admin_email_exists' => 'Este email de admin já existe.',
            'error.super_admin_locked' => 'O cargo e estado do super admin ficam bloqueados.',
            'error.admin_self_deactivate' => 'Não podes desativar a tua propria conta admin.',
            'success.product_approved' => 'Produto aprovado com sucesso.',
            'success.product_rejected' => 'Produto rejeitado.',
            'success.product_deactivated' => 'Produto inativado.',
            'success.product_reactivated' => 'Produto reativado.',
            'success.release_approved' => 'Lançamento aprovado com sucesso.',
            'success.release_rejected' => 'Lançamento rejeitado.',
            'success.release_deactivated' => 'Lançamento inativado.',
            'success.release_reactivated' => 'Lançamento reativado.',
            'error.api_unauthenticated' => 'Não autenticado',
            'error.api_invalid_request' => 'Pedido inválido',
            'error.api_forbidden' => 'Não tens permissão para esta ação',
            'error.api_forbidden' => 'Sem permissão',
            'misc.no_action' => 'Sem ação',
            'receipt.title' => 'Recibo da encomenda #:id',
            'receipt.customer' => 'Cliente',
            'receipt.details' => 'Detalhes',
            'receipt.date' => 'Data',
            'receipt.status' => 'Estado',
            'receipt.payment' => 'Pagamento',
            'receipt.delivery' => 'Entrega',
            'receipt.product' => 'Produto',
            'receipt.qty' => 'Qtd',
            'receipt.price' => 'Preco',
            'receipt.line' => 'Linha',
            'receipt.subtotal' => 'Subtotal',
            'receipt.vat' => 'IVA',
            'receipt.total' => 'Total final',
            'receipt.footer' => 'Recibo gerado automaticamente pela plataforma Greenerry.',
            'orders.view_receipt' => 'Ver recibo'
        ],
        'en' => [
            'status.approved' => 'Approved',
            'status.rejected' => 'Rejected',
            'status.inactive' => 'Inactive',
            'status.active' => 'Active',
            'status.pending' => 'Pending',
            'status.preparing' => 'Preparing',
            'status.sent' => 'Sent',
            'status.delivered' => 'Delivered',
            'status.cancelled' => 'Cancelled',
            'status.open' => 'Open',
            'status.answered' => 'Answered',
            'status.closed' => 'Closed',
            'status.review' => 'In review',
            'status.done' => 'Completed',
            'status.refused' => 'Refused',
            'status.blocked' => 'Blocked',
            'release_type.single' => 'Single',
            'release_type.ep' => 'EP',
            'release_type.album' => 'Album',
            'category.t_shirt' => 'T-Shirt',
            'category.hoodie' => 'Hoodie',
            'category.vinyl' => 'Vinyl',
            'category.cd' => 'CD',
            'category.poster' => 'Poster',
            'category.accessory' => 'Accessory',
            'payment.pending' => 'Pending',
            'payment.paid' => 'Paid',
            'payment.failed' => 'Failed',
            'payment.refunded' => 'Refunded',
            'payment.card' => 'Card',
            'payment.mbway' => 'MB Way',
            'payment.transfer' => 'Bank transfer',
            'error.required_name' => 'Name is required.',
            'error.short_name' => 'Name must have at least 2 characters.',
            'error.long_name' => 'Name cannot have more than 120 characters.',
            'error.invalid_name_chars' => 'Name can only contain letters, spaces, hyphens or apostrophes.',
            'error.required_email' => 'Email is required.',
            'error.invalid_email' => 'The email address is not valid.',
            'error.long_email' => 'Email cannot have more than 150 characters.',
            'error.required_password' => 'Password is required.',
            'error.short_password' => 'Password must have at least 8 characters.',
            'error.invalid_access_type' => 'Invalid access type.',
            'error.invalid_session' => 'The session expired. Try again.',
            'error.invalid_admin_credentials' => 'Invalid administrator credentials.',
            'error.use_admin_login' => 'Use the Administration option to sign in as admin.',
            'error.invalid_login' => 'Incorrect email or password.',
            'error.account_inactive' => 'The account is not active. If you just created it, check your email.',
            'error.password_mismatch' => 'Passwords do not match.',
            'error.account_exists' => 'An account with that email already exists.',
            'error.create_account' => 'Could not create the account. Try again.',
            'success.account_created' => 'Account created. Check your email to activate it.',
            'error.verification_email_send' => 'Could not send the verification email. Check the email settings.',
            'success.email_verified' => 'Email verified successfully. You can now sign in.',
            'error.email_verify_invalid' => 'Invalid verification link.',
            'error.email_verify_expired' => 'The verification link has expired. Create the account again or request a new link.',
            'email.verify_subject' => 'Verify your Greenerry email',
            'email.verify_body' => "Hello :name,\n\nClick the link below to activate your Greenerry account:\n\n:link\n\nThis link expires in 24 hours.\n\nGreenerry",
            'success.profile_updated' => 'Profile updated successfully.',
            'error.subject_required' => 'Subject is required.',
            'error.subject_long' => 'Subject cannot have more than 160 characters.',
            'error.message_required' => 'Message is required.',
            'error.message_short' => 'Message must have at least 10 characters.',
            'success.message_sent' => 'Message sent to admin successfully.',
            'error.message_send' => 'Could not send the message.',
            'messages_reply_label' => 'Admin reply',
            'error.account_not_found' => 'There is no account with that email.',
            'success.reset_request' => 'If an active account exists with that email, you will receive a password reset link.',
            'error.reset_request_save' => 'Could not send the recovery link.',
            'success.password_reset' => 'Password changed successfully. You can now sign in.',
            'error.reset_invalid' => 'Invalid recovery link.',
            'error.reset_expired' => 'The recovery link expired. Request a new one.',
            'email.welcome_subject' => 'Welcome to Greenerry',
            'email.welcome_body' => "Hello :name,\n\nYour Greenerry account was created successfully.\n\nYou can now sign in, listen to music, follow artists, and publish your projects.\n\nGreenerry",
            'email.reset_request_subject' => 'Change your Greenerry password',
            'email.reset_request_body' => "Hello :name,\n\nClick the link below to change your password:\n\n:link\n\nThis link expires in 1 hour.\n\nGreenerry",
            'email.password_changed_subject' => 'Your password was changed',
            'email.password_changed_body' => "Hello :name,\n\nYour Greenerry password was changed successfully.\n\nIf this was not you, contact support.\n\nGreenerry",
            'email.test_subject' => 'Greenerry email test',
            'email.test_body' => "This is a test email sent by :site.\n\nIf you received this, SMTP is working.",
            'email.order_subject' => 'Order confirmation #:id',
            'email.order_body' => "Hello :name,\n\nYour order #:id was created successfully.\n\nItems:\n:items\n\nTotal: :total\n\nReceipt: :receipt\n\nGreenerry",
            'email.artist_sale_subject' => 'You sold a product on Greenerry',
            'email.artist_sale_body' => "Hello :name,\n\nYou received a new sale in order #:id.\n\nItems:\n:items\n\nArtist value: :value\n\nGreenerry",
            'email.product_approved_subject' => 'Product approved',
            'email.product_approved_body' => "Hello :name,\n\nYour product \":product\" was approved and can now appear in the store.\n\nView product: :link\n\nGreenerry",
            'email.product_rejected_subject' => 'Product rejected',
            'email.product_rejected_body' => "Hello :name,\n\nYour product \":product\" was rejected.\n\nReason: :reason\n\nYou can fix it and try again.\n\nGreenerry",
            'email.release_approved_subject' => 'Release approved',
            'email.release_approved_body' => "Hello :name,\n\nYour release \":release\" was approved and can now appear in music.\n\nView release: :link\n\nGreenerry",
            'email.release_rejected_subject' => 'Release rejected',
            'email.release_rejected_body' => "Hello :name,\n\nYour release \":release\" was rejected.\n\nReason: :reason\n\nYou can fix it and try again.\n\nGreenerry",
            'maintenance.title' => 'Under maintenance',
            'maintenance.text' => 'This area is temporarily unavailable. Please try again later.',
            'maintenance.back' => 'Back home',
            'error.invalid_postal' => 'Invalid postal code. Use the format 0000-000.',
            'error.invalid_country' => 'Select a valid country.',
            'error.invalid_phone' => 'Invalid phone number. Use 912345678 or +351 912345678.',
            'error.invalid_nif' => 'Invalid tax number. It must have 9 digits.',
            'error.invalid_payment_method' => 'Invalid payment method.',
            'error.required_recipient' => 'Enter the recipient name.',
            'error.short_address' => 'The address must have at least 5 characters.',
            'error.required_city' => 'Enter the city.',
            'error.invalid_city_chars' => 'City can only contain letters, spaces, hyphens or apostrophes.',
            'error.empty_cart' => 'The cart is empty.',
            'error.invalid_cart_product' => 'There is an invalid product in the cart.',
            'error.product_unavailable' => 'One of the products is no longer available.',
            'error.own_product_purchase' => 'You cannot buy your own products.',
            'error.invalid_size_for_product' => 'Select a valid size for :product.',
            'error.insufficient_size_stock' => 'There is not enough stock for :product in size :size.',
            'error.insufficient_stock' => 'There is not enough stock for :product.',
            'success.order_created' => 'Order registered successfully.',
            'error.order_create' => 'Could not complete the order.',
            'error.order_update' => 'Could not update the order.',
            'success.order_artist_updated' => 'Order status updated.',
            'error.order_no_editable_items' => 'This order has no editable items left.',
            'error.cancelled_order_locked' => 'This order was cancelled and can no longer be changed.',
            'error.admin_blocked_item' => 'The admin blocked this item. You cannot activate it.',
            'error.user_update' => 'Could not update the user.',
            'error.settings_update' => 'Could not save the settings.',
            'error.test_email_send' => 'Could not send the test email.',
            'success.order_admin_updated' => 'Order updated successfully.',
            'success.user_state_updated' => 'User state updated.',
            'success.settings_updated' => 'Settings updated successfully.',
            'success.test_email_sent' => 'Test email sent successfully.',
            'error.release_title_required' => 'Release title is required.',
            'error.release_type_invalid' => 'Invalid release type.',
            'error.release_date_invalid' => 'Release date is not valid.',
            'error.image_format' => 'The image must be JPG, PNG or WEBP.',
            'error.cover_format' => 'The cover must be JPG, PNG or WEBP.',
            'error.audio_required' => 'You must upload the audio file.',
            'error.audio_format' => 'Unsupported audio format.',
            'error.track_incomplete' => 'Each track must have a title and audio file.',
            'error.track_format' => 'An unsupported audio format was found.',
            'error.track_required' => 'Add at least one track to the release.',
            'success.release_sent' => 'Release sent for admin approval.',
            'success.release_updated' => 'Release updated and sent for review.',
            'success.release_deleted' => 'Release deleted successfully.',
            'error.release_save' => 'Could not save the release.',
            'error.release_delete' => 'Could not delete the release.',
            'confirm.release_delete' => 'Delete this release? This also removes the audio files and cover.',
            'error.product_name_required' => 'Product name is required.',
            'error.price_positive' => 'Price must be greater than zero.',
            'error.category_required' => 'Select a category.',
            'error.category_name_short' => 'Category name must have at least 2 characters.',
            'error.category_exists' => 'There is already a category with that name.',
            'error.size_code_required' => 'Size code is required.',
            'error.size_exists' => 'There is already a size with that code.',
            'error.stock_negative' => 'Stock cannot be negative.',
            'error.category_no_sizes' => 'This category does not use sizes.',
            'error.size_or_stock_required' => 'Choose at least one size or enter stock in a size.',
            'error.size_stock_required' => 'Enter stock for at least one selected size.',
            'success.product_sent' => 'Product sent for admin approval.',
            'success.category_created' => 'Category created.',
            'success.category_updated' => 'Category updated.',
            'success.category_state_updated' => 'Category state updated.',
            'success.size_created' => 'Size created.',
            'success.size_updated' => 'Size updated.',
            'error.product_save' => 'Could not save the product.',
            'success.admin_reply_sent' => 'Reply sent successfully.',
            'success.admin_created' => 'Admin created successfully.',
            'success.admin_updated' => 'Admin updated successfully.',
            'success.notification_read' => 'Notification marked as read.',
            'success.notifications_read' => 'Notifications marked as read.',
            'error.admin_email_exists' => 'This admin email already exists.',
            'error.super_admin_locked' => 'The super admin role and state are locked.',
            'error.admin_self_deactivate' => 'You cannot deactivate your own admin account.',
            'success.product_approved' => 'Product approved successfully.',
            'success.product_rejected' => 'Product rejected.',
            'success.product_deactivated' => 'Product deactivated.',
            'success.product_reactivated' => 'Product reactivated.',
            'success.release_approved' => 'Release approved successfully.',
            'success.release_rejected' => 'Release rejected.',
            'success.release_deactivated' => 'Release deactivated.',
            'success.release_reactivated' => 'Release reactivated.',
            'error.api_unauthenticated' => 'Not authenticated',
            'error.api_invalid_request' => 'Invalid request',
            'error.api_forbidden' => 'You do not have permission for this action',
            'error.api_forbidden' => 'No permission',
            'misc.no_action' => 'No action',
            'receipt.title' => 'Order receipt #:id',
            'receipt.customer' => 'Customer',
            'receipt.details' => 'Details',
            'receipt.date' => 'Date',
            'receipt.status' => 'Status',
            'receipt.payment' => 'Payment',
            'receipt.delivery' => 'Delivery',
            'receipt.product' => 'Product',
            'receipt.qty' => 'Qty',
            'receipt.price' => 'Price',
            'receipt.line' => 'Line',
            'receipt.subtotal' => 'Subtotal',
            'receipt.vat' => 'VAT',
            'receipt.total' => 'Final total',
            'receipt.footer' => 'Receipt generated automatically by the Greenerry platform.',
            'orders.view_receipt' => 'View receipt'
        ],
    ];

    $lang = current_lang();
    $text = $strings[$lang][$key] ?? $strings['pt'][$key] ?? $key;

    foreach ($replace as $name => $value) {
        $text = str_replace(':' . $name, (string)$value, $text);
    }

    return $text;
}

function order_status_label(string $status): string
{
    switch ($status) {
        case 'aprovado':
        case 'aprovada':
            return tr('status.approved');
        case 'rejeitado':
        case 'rejeitada':
            return tr('status.rejected');
        case 'inativo':
        case 'inativa':
            return tr('status.inactive');
        case 'ativo':
        case 'ativa':
            return tr('status.active');
        case 'pendente':
            return tr('status.pending');
        case 'em_preparacao':
            return tr('status.preparing');
        case 'enviado':
        case 'enviada':
            return tr('status.sent');
        case 'entregue':
            return tr('status.delivered');
        case 'cancelado':
        case 'cancelada':
            return tr('status.cancelled');
        case 'aberta':
            return tr('status.open');
        case 'respondida':
            return tr('status.answered');
        case 'fechada':
            return tr('status.closed');
        case 'em_analise':
            return tr('status.review');
        case 'concluido':
            return tr('status.done');
        case 'recusado':
            return tr('status.refused');
        case 'bloqueado':
            return tr('status.blocked');
        default:
            return ucfirst(str_replace('_', ' ', $status));
    }
}

function release_type_label(string $type): string
{
    switch ($type) {
        case 'Single':
            return tr('release_type.single');
        case 'EP':
            return tr('release_type.ep');
        case 'Album':
            return tr('release_type.album');
        default:
            return $type;
    }
}

function category_label(string $name): string
{
    $keys = [
        'T-Shirt' => 'category.t_shirt',
        'Hoodie' => 'category.hoodie',
        'Vinil' => 'category.vinyl',
        'CD' => 'category.cd',
        'Poster' => 'category.poster',
        'Acessorio' => 'category.accessory',
    ];

    $key = $keys[$name] ?? null;
    return $key ? tr($key) : $name;
}

function payment_status_label(string $status): string
{
    switch ($status) {
        case 'pendente':
            return tr('payment.pending');
        case 'pago':
            return tr('payment.paid');
        case 'falhado':
            return tr('payment.failed');
        case 'reembolsado':
            return tr('payment.refunded');
        default:
            return ucfirst(str_replace('_', ' ', $status));
    }
}

function payment_method_label(string $method): string
{
    switch ($method) {
        case 'cartao':
            return tr('payment.card');
        case 'mbway':
            return tr('payment.mbway');
        case 'transferencia':
            return tr('payment.transfer');
        default:
            return ucfirst(str_replace('_', ' ', $method));
    }
}

function state_badge_class(string $state): string
{
    switch ($state) {
        case 'aprovado':
        case 'aprovada':
        case 'ativo':
        case 'ativa':
        case 'pago':
        case 'entregue':
            return 'badge-blue';
        case 'pendente':
        case 'em_preparacao':
        case 'enviada':
        case 'enviado':
        case 'em_analise':
            return 'badge-red';
        case 'rejeitado':
        case 'rejeitada':
        case 'inativo':
        case 'inativa':
        case 'cancelada':
        case 'cancelado':
        case 'falhado':
        case 'reembolsado':
        case 'bloqueado':
        case 'recusado':
            return 'badge-light';
        default:
            return 'badge-dark';
    }
}
