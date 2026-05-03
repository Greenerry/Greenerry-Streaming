<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_host = strtolower((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
$_live = $_host !== 'localhost' && $_host !== '127.0.0.1' && substr($_host, 0, 10) !== 'localhost:';
$_scriptDir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '')));
$_base = preg_replace('#/(pages|admin|api|includes)$#', '', $_scriptDir);
$_base = $_base === '/' || $_base === '.' ? '' : rtrim((string)$_base, '/');

$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    require $localConfig;
}

$db_host = getenv('GREENERRY_DB_HOST') ?: ($db_host ?? ($_live ? '' : 'localhost'));
$db_user = getenv('GREENERRY_DB_USER') ?: ($db_user ?? ($_live ? '' : 'root'));
$db_pass = getenv('GREENERRY_DB_PASS') ?: ($db_pass ?? '');
$db_name = getenv('GREENERRY_DB_NAME') ?: ($db_name ?? ($_live ? '' : 'greenerry'));

if ($db_host === '' || $db_user === '' || $db_name === '') {
    die('Configura as credenciais da base de dados em variaveis de ambiente ou em includes/config.local.php.');
}

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die('Erro de base de dados: ' . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function site_url(string $path = ''): string
{
    global $_base;

    $path = ltrim($path, '/');
    return $path === '' ? $_base : $_base . '/' . $path;
}

function asset_url(string $type, ?string $file): string
{
    if (!$file) {
        return '';
    }

    return site_url('assets/' . trim($type, '/') . '/' . ltrim($file, '/'));
}

function delete_asset_file(string $type, ?string $file): bool
{
    $file = trim((string)$file);
    if ($file === '' || strpos($file, '..') !== false || preg_match('#^[a-z]+://#i', $file)) {
        return false;
    }

    $baseDir = realpath(__DIR__ . '/../assets/' . trim($type, '/'));
    if ($baseDir === false) {
        return false;
    }

    $path = $baseDir . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file), DIRECTORY_SEPARATOR);
    $realPath = realpath($path);
    if ($realPath === false || strpos($realPath, $baseDir . DIRECTORY_SEPARATOR) !== 0 || !is_file($realPath)) {
        return false;
    }

    return @unlink($realPath);
}

function format_eur(float $value): string
{
    return number_format($value, 2, ',', '.') . ' EUR';
}

function site_setting(string $key, string $default = ''): string
{
    global $conn;

    static $settings = null;
    if ($settings === null) {
        $settings = [];
        $table = mysqli_query($conn, "SHOW TABLES LIKE 'site_config'");
        if ($table && mysqli_num_rows($table) > 0) {
            $rows = db_all($conn, "SELECT setting_key, setting_value FROM site_config");
            foreach ($rows as $row) {
                $settings[(string)$row['setting_key']] = (string)$row['setting_value'];
            }
        }
    }

    return $settings[$key] ?? $default;
}

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
            'status.preparing' => 'Em preparacao',
            'status.sent' => 'Enviado',
            'status.delivered' => 'Entregue',
            'status.cancelled' => 'Cancelado',
            'status.open' => 'Aberta',
            'status.answered' => 'Respondida',
            'status.closed' => 'Fechada',
            'status.review' => 'Em analise',
            'status.done' => 'Concluido',
            'status.refused' => 'Recusado',
            'status.blocked' => 'Bloqueado',
            'payment.pending' => 'Pendente',
            'payment.paid' => 'Pago',
            'payment.failed' => 'Falhado',
            'payment.refunded' => 'Reembolsado',
            'payment.card' => 'Cartao',
            'payment.mbway' => 'MB Way',
            'payment.transfer' => 'Transferencia',
            'error.required_name' => 'O nome e obrigatorio.',
            'error.short_name' => 'O nome deve ter pelo menos 2 caracteres.',
            'error.long_name' => 'O nome nao pode ter mais de 120 caracteres.',
            'error.required_email' => 'O email e obrigatorio.',
            'error.invalid_email' => 'O email introduzido nao e valido.',
            'error.long_email' => 'O email nao pode ter mais de 150 caracteres.',
            'error.required_password' => 'A palavra-passe e obrigatoria.',
            'error.short_password' => 'A palavra-passe deve ter pelo menos 8 caracteres.',
            'error.invalid_access_type' => 'Tipo de acesso invalido.',
            'error.invalid_session' => 'A sessao expirou. Tenta novamente.',
            'error.invalid_admin_credentials' => 'Credenciais de administrador invalidas.',
            'error.use_admin_login' => 'Usa a opcao Administracao para entrar como admin.',
            'error.invalid_login' => 'Email ou palavra-passe incorretos.',
            'error.account_inactive' => 'A conta nao esta ativa.',
            'error.password_mismatch' => 'As palavras-passe nao coincidem.',
            'error.account_exists' => 'Ja existe uma conta com esse email.',
            'error.create_account' => 'Nao foi possivel criar a conta. Tenta novamente.',
            'success.account_created' => 'Conta criada com sucesso. Ja podes entrar.',
            'success.profile_updated' => 'Perfil atualizado com sucesso.',
            'error.subject_required' => 'O assunto e obrigatorio.',
            'error.subject_long' => 'O assunto nao pode ter mais de 160 caracteres.',
            'error.message_required' => 'A mensagem e obrigatoria.',
            'error.message_short' => 'A mensagem deve ter pelo menos 10 caracteres.',
            'success.message_sent' => 'Mensagem enviada ao admin com sucesso.',
            'error.message_send' => 'Nao foi possivel enviar a mensagem.',
            'messages_reply_label' => 'Resposta do admin',
            'error.reset_request_long' => 'O pedido nao pode ter mais de 500 caracteres.',
            'error.account_not_found' => 'Nao existe nenhuma conta com esse email.',
            'success.reset_request' => 'Pedido enviado. O admin ira analisar e responder manualmente.',
            'error.reset_request_save' => 'Nao foi possivel registar o pedido.',
            'error.invalid_postal' => 'Codigo postal invalido. Usa o formato 0000-000.',
            'error.invalid_phone' => 'Telefone invalido. Usa 912345678 ou +351 912345678.',
            'error.invalid_nif' => 'NIF invalido. Deve ter 9 digitos.',
            'error.invalid_payment_method' => 'Metodo de pagamento invalido.',
            'error.required_recipient' => 'Indica o nome do destinatario.',
            'error.short_address' => 'A morada deve ter pelo menos 5 caracteres.',
            'error.required_city' => 'Indica a cidade.',
            'error.empty_cart' => 'O carrinho esta vazio.',
            'error.invalid_cart_product' => 'Existe um produto invalido no carrinho.',
            'error.product_unavailable' => 'Um dos produtos ja nao esta disponivel.',
            'error.own_product_purchase' => 'Nao podes comprar os teus proprios produtos.',
            'error.invalid_size_for_product' => 'Seleciona um tamanho valido para :product.',
            'error.insufficient_size_stock' => 'Nao ha stock suficiente para :product no tamanho :size.',
            'error.insufficient_stock' => 'Nao ha stock suficiente para :product.',
            'success.order_created' => 'Encomenda registada com sucesso.',
            'error.order_create' => 'Nao foi possivel concluir a encomenda.',
            'error.order_update' => 'Nao foi possivel atualizar a encomenda.',
            'error.cancelled_order_locked' => 'Esta encomenda foi cancelada e ja nao pode ser alterada.',
            'error.admin_blocked_item' => 'O admin bloqueou este item. Nao podes ativa-lo.',
            'error.user_update' => 'Nao foi possivel atualizar o utilizador.',
            'error.settings_update' => 'Nao foi possivel guardar as definicoes.',
            'success.order_admin_updated' => 'Encomenda atualizada com sucesso.',
            'success.user_state_updated' => 'Estado do utilizador atualizado.',
            'success.settings_updated' => 'Definicoes atualizadas com sucesso.',
            'error.release_title_required' => 'O titulo do lancamento e obrigatorio.',
            'error.release_type_invalid' => 'Tipo de lancamento invalido.',
            'error.release_date_invalid' => 'A data de lancamento nao e valida.',
            'error.image_format' => 'A imagem deve estar em JPG, PNG ou WEBP.',
            'error.cover_format' => 'A capa deve estar em JPG, PNG ou WEBP.',
            'error.audio_required' => 'Tens de enviar o ficheiro de audio.',
            'error.audio_format' => 'Formato de audio nao suportado.',
            'error.track_incomplete' => 'Cada faixa tem de ter titulo e ficheiro de audio.',
            'error.track_format' => 'Foi encontrado um formato de audio nao suportado.',
            'error.track_required' => 'Adiciona pelo menos uma faixa ao lancamento.',
            'success.release_sent' => 'Lancamento enviado para aprovacao do admin.',
            'success.release_updated' => 'Lancamento atualizado e enviado para revisao.',
            'error.release_save' => 'Nao foi possivel guardar o lancamento.',
            'error.product_name_required' => 'O nome do produto e obrigatorio.',
            'error.price_positive' => 'O preco tem de ser superior a zero.',
            'error.category_required' => 'Seleciona uma categoria.',
            'error.category_name_short' => 'O nome da categoria deve ter pelo menos 2 caracteres.',
            'error.category_exists' => 'Ja existe uma categoria com esse nome.',
            'error.size_code_required' => 'O codigo do tamanho e obrigatorio.',
            'error.size_exists' => 'Ja existe um tamanho com esse codigo.',
            'error.stock_negative' => 'O stock nao pode ser negativo.',
            'error.category_no_sizes' => 'Esta categoria nao usa tamanhos.',
            'error.size_or_stock_required' => 'Escolhe pelo menos um tamanho ou indica stock num tamanho.',
            'error.size_stock_required' => 'Indica stock para pelo menos um dos tamanhos escolhidos.',
            'success.product_sent' => 'Produto enviado para aprovacao do admin.',
            'success.category_created' => 'Categoria criada.',
            'success.category_updated' => 'Categoria atualizada.',
            'success.category_state_updated' => 'Estado da categoria atualizado.',
            'success.size_created' => 'Tamanho criado.',
            'success.size_updated' => 'Tamanho atualizado.',
            'error.product_save' => 'Nao foi possivel registar o produto.',
            'success.admin_reply_sent' => 'Resposta enviada com sucesso.',
            'success.product_approved' => 'Produto aprovado com sucesso.',
            'success.product_rejected' => 'Produto rejeitado.',
            'success.product_deactivated' => 'Produto inativado.',
            'success.product_reactivated' => 'Produto reativado.',
            'success.release_approved' => 'Lancamento aprovado com sucesso.',
            'success.release_rejected' => 'Lancamento rejeitado.',
            'success.release_deactivated' => 'Lancamento inativado.',
            'success.release_reactivated' => 'Lancamento reativado.',
            'success.reset_updated' => 'Pedido atualizado com sucesso.',
            'error.api_unauthenticated' => 'Nao autenticado',
            'error.api_invalid_request' => 'Pedido invalido',
            'error.api_forbidden' => 'Sem permissao',
            'misc.no_action' => 'Sem acao',
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
            'receipt.footer' => 'Recibo gerado automaticamente pela plataforma Greenerry.'
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
            'error.account_inactive' => 'The account is not active.',
            'error.password_mismatch' => 'Passwords do not match.',
            'error.account_exists' => 'An account with that email already exists.',
            'error.create_account' => 'Could not create the account. Try again.',
            'success.account_created' => 'Account created successfully. You can now sign in.',
            'success.profile_updated' => 'Profile updated successfully.',
            'error.subject_required' => 'Subject is required.',
            'error.subject_long' => 'Subject cannot have more than 160 characters.',
            'error.message_required' => 'Message is required.',
            'error.message_short' => 'Message must have at least 10 characters.',
            'success.message_sent' => 'Message sent to admin successfully.',
            'error.message_send' => 'Could not send the message.',
            'messages_reply_label' => 'Admin reply',
            'error.reset_request_long' => 'The request cannot have more than 500 characters.',
            'error.account_not_found' => 'There is no account with that email.',
            'success.reset_request' => 'Request sent. The admin will review and reply manually.',
            'error.reset_request_save' => 'Could not save the request.',
            'error.invalid_postal' => 'Invalid postal code. Use the format 0000-000.',
            'error.invalid_phone' => 'Invalid phone number. Use 912345678 or +351 912345678.',
            'error.invalid_nif' => 'Invalid tax number. It must have 9 digits.',
            'error.invalid_payment_method' => 'Invalid payment method.',
            'error.required_recipient' => 'Enter the recipient name.',
            'error.short_address' => 'The address must have at least 5 characters.',
            'error.required_city' => 'Enter the city.',
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
            'error.cancelled_order_locked' => 'This order was cancelled and can no longer be changed.',
            'error.admin_blocked_item' => 'The admin blocked this item. You cannot activate it.',
            'error.user_update' => 'Could not update the user.',
            'error.settings_update' => 'Could not save the settings.',
            'success.order_admin_updated' => 'Order updated successfully.',
            'success.user_state_updated' => 'User state updated.',
            'success.settings_updated' => 'Settings updated successfully.',
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
            'error.release_save' => 'Could not save the release.',
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
            'success.product_approved' => 'Product approved successfully.',
            'success.product_rejected' => 'Product rejected.',
            'success.product_deactivated' => 'Product deactivated.',
            'success.product_reactivated' => 'Product reactivated.',
            'success.release_approved' => 'Release approved successfully.',
            'success.release_rejected' => 'Release rejected.',
            'success.release_deactivated' => 'Release deactivated.',
            'success.release_reactivated' => 'Release reactivated.',
            'success.reset_updated' => 'Request updated successfully.',
            'error.api_unauthenticated' => 'Not authenticated',
            'error.api_invalid_request' => 'Invalid request',
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
            'receipt.footer' => 'Receipt generated automatically by the Greenerry platform.'
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

function db_escape(mysqli $conn, ?string $value): string
{
    return mysqli_real_escape_string($conn, (string)$value);
}

function db_one(mysqli $conn, string $sql): ?array
{
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    return $row ?: null;
}

function db_all(mysqli $conn, string $sql): array
{
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_free_result($result);
    return $rows;
}

function is_user_logged_in(): bool
{
    return !empty($_SESSION['user_logged_in']) && !empty($_SESSION['user_id']);
}

function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_id']);
}

function current_user_id(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

function current_admin_id(): int
{
    return (int)($_SESSION['admin_id'] ?? 0);
}

function current_user(mysqli $conn): ?array
{
    static $user = false;

    if ($user !== false) {
        return $user;
    }

    if (!is_user_logged_in()) {
        $user = null;
        return $user;
    }

    $uid = current_user_id();
    $user = db_one($conn, "SELECT * FROM cliente WHERE idCliente = {$uid} LIMIT 1");
    return $user;
}

function current_admin(mysqli $conn): ?array
{
    static $admin = false;

    if ($admin !== false) {
        return $admin;
    }

    if (!is_admin_logged_in()) {
        $admin = null;
        return $admin;
    }

    $aid = current_admin_id();
    $admin = db_one($conn, "SELECT * FROM admin WHERE idAdmin = {$aid} LIMIT 1");
    return $admin;
}

function login_user_session(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = (int)$user['idCliente'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['nome'];
}

function login_admin_session(array $admin): void
{
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = (int)$admin['idAdmin'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_name'] = $admin['nome'];
}

function logout_all_sessions(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

function require_user_login(): void
{
    if (!is_user_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin_login(): void
{
    if (!is_admin_logged_in()) {
        header('Location: ../pages/login.php');
        exit;
    }
}

function redirect_if_authenticated(): void
{
    if (is_user_logged_in()) {
        header('Location: index.php');
        exit;
    }

    if (is_admin_logged_in()) {
        header('Location: ../admin/dashboard.php');
        exit;
    }
}

function validate_nome(string $nome): ?string
{
    $nome = trim($nome);
    if ($nome === '') {
        return tr('error.required_name');
    }
    if (mb_strlen($nome) < 2) {
        return tr('error.short_name');
    }
    if (mb_strlen($nome) > 120) {
        return tr('error.long_name');
    }
    return null;
}

function validate_email(string $email): ?string
{
    $email = trim($email);
    if ($email === '') {
        return tr('error.required_email');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return tr('error.invalid_email');
    }
    if (mb_strlen($email) > 150) {
        return tr('error.long_email');
    }
    return null;
}

function validate_password(string $password): ?string
{
    if ($password === '') {
        return tr('error.required_password');
    }
    if (strlen($password) < 8) {
        return tr('error.short_password');
    }
    return null;
}

function password_matches(string $plainPassword, string $storedPassword): bool
{
    if ($storedPassword === '') {
        return false;
    }

    if (password_verify($plainPassword, $storedPassword)) {
        return true;
    }

    return false;
}

function validate_uploaded_image(array $file, int $maxBytes = 5_000_000): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    if (($file['size'] ?? 0) > $maxBytes) {
        return current_lang() === 'en'
            ? 'The image is too large.'
            : 'A imagem e demasiado grande.';
    }

    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return tr('error.image_format');
    }

    $info = @getimagesize($file['tmp_name'] ?? '');
    if (!$info || !in_array((string)($info['mime'] ?? ''), ['image/jpeg', 'image/png', 'image/webp'], true)) {
        return tr('error.image_format');
    }

    return null;
}

function validate_uploaded_audio(array $file, int $maxBytes = 25_000_000): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    if (($file['size'] ?? 0) > $maxBytes) {
        return current_lang() === 'en'
            ? 'The audio file is too large.'
            : 'O ficheiro de audio e demasiado grande.';
    }

    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['mp3', 'wav', 'ogg', 'flac', 'm4a'], true)) {
        return tr('error.audio_format');
    }

    return null;
}

function normalize_slug(string $text): string
{
    $text = trim(mb_strtolower($text));
    $replace = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a',
        'é' => 'e', 'ê' => 'e',
        'í' => 'i',
        'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ú' => 'u',
        'ç' => 'c'
    ];
    $text = strtr($text, $replace);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string)$text, '-');
}

function ensure_unique_cliente_slug(mysqli $conn, string $nome, int $ignoreId = 0): string
{
    $baseSlug = normalize_slug($nome);
    if ($baseSlug === '') {
        $baseSlug = 'artista';
    }

    $slug = $baseSlug;
    $suffix = 1;
    while (true) {
        $slugSafe = db_escape($conn, $slug);
        $sql = "SELECT idCliente FROM cliente WHERE slug = '{$slugSafe}'";
        if ($ignoreId > 0) {
            $sql .= " AND idCliente != {$ignoreId}";
        }
        $exists = db_one($conn, $sql);
        if (!$exists) {
            return $slug;
        }
        $suffix++;
        $slug = $baseSlug . '-' . $suffix;
    }
}

$currentUser = current_user($conn);
$currentAdmin = current_admin($conn);
$jsUserId = $currentUser ? (int)$currentUser['idCliente'] : 0;
