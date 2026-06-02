<?php
require_once '../includes/config.php';

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap cart-shell">
    <div class="cart-hero hero-card--single">
      <div class="cart-hero-copy">
        <h2 data-t="cart_title">Carrinho</h2>
      </div>
    </div>

    <div id="cart-empty" class="cart-empty-state is-hidden">
      <div class="cart-empty-icon">Cart</div>
      <h3 data-t="cart_empty_title">O teu carrinho esta vazio.</h3>
      <p data-t="cart_empty_text">Explora a loja, escolhe merch oficial e volta aqui para finalizar a encomenda.</p>
      <a href="shop.php" class="btn btn-ghost btn-sm" data-t="cart_empty_cta">Ver loja</a>
    </div>

    <div class="cart-layout">
      <div id="cart-items" class="cart-items-stack"></div>

      <aside id="cart-footer" class="cart-summary-card is-hidden">
        <div class="cart-summary-head">
          <span class="slabel" data-t="cart_summary_label">Resumo</span>
          <h3 data-t="cart_summary_title">Total da encomenda</h3>
        </div>

        <div class="cart-summary-line">
          <span data-t="cart_subtotal">Subtotal</span>
          <strong id="cart-subtotal">0,00 EUR</strong>
        </div>
        <div class="cart-summary-line">
          <span data-t="cart_vat">IVA estimado</span>
          <strong id="cart-iva">0,00 EUR</strong>
        </div>
        <div class="cart-summary-line cart-summary-line--total">
          <span data-t="cart_total">Total</span>
          <strong id="cart-total">0,00 EUR</strong>
        </div>

        <?php if (is_user_logged_in()): ?>
          <a href="checkout.php" class="btn btn-dark btn-full btn-lg" data-t="cart_checkout_cta">Finalizar compra</a>
        <?php else: ?>
          <a href="login.php" class="btn btn-dark btn-full btn-lg" data-t="cart_login_cta">Entrar para finalizar</a>
        <?php endif; ?>

        <button type="button" id="clear-cart-btn" class="btn btn-ghost btn-full btn-sm" data-t="cart_clear">Limpar carrinho</button>
      </aside>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
