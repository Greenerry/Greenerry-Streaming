<?php
// Page purpose: Shows the shopping cart and quantity controls.
// Keep this page simple: prepare data first, then render the view.
require_once '../includes/config.php';

include '../includes/header.php';
?>

<style>
.cart-page-shell .cart-shell {
  min-height: calc(100svh - var(--nav) - var(--player-h) - 12px) !important;
  display: flex !important;
  flex-direction: column !important;
}

.cart-page-shell #cart-empty:not(.is-hidden) {
  flex: 1 1 auto !important;
  min-height: 360px !important;
  display: flex !important;
  flex-direction: column !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 14px !important;
  padding: 0 !important;
  margin: 0 !important;
  text-align: center !important;
  transform: translateY(-3vh);
}

.cart-page-shell #cart-empty:not(.is-hidden) + .cart-layout {
  display: none !important;
}

.cart-page-shell #cart-empty h3 {
  margin: 0 !important;
  font-size: clamp(1.35rem, 2.1vw, 1.9rem) !important;
  line-height: 1.15 !important;
}

.cart-page-shell #cart-empty .btn {
  width: auto !important;
  height: auto !important;
  min-width: 112px !important;
  min-height: 42px !important;
  margin: 0 !important;
  padding: 0 18px !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  align-self: center !important;
  flex: 0 0 auto !important;
  border-radius: 999px !important;
}
</style>

<section class="content-shell cart-page-shell">
  <div class="wrap cart-shell">
    <div class="cart-hero hero-card--single">
      <div class="cart-hero-copy">
        <h2 data-t="cart_title">Carrinho</h2>
      </div>
    </div>

    <div id="cart-empty" class="cart-empty-state is-hidden">
      <div class="cart-empty-icon">Cart</div>
      <h3 data-t="cart_empty_title">O teu carrinho esta vazio.</h3>
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
          <strong id="cart-subtotal">0,00 €</strong>
        </div>
        <div class="cart-summary-line">
          <span data-t="cart_vat">IVA estimado</span>
          <strong id="cart-iva">0,00 €</strong>
        </div>
        <div class="cart-summary-line cart-summary-line--total">
          <span data-t="cart_total">Total</span>
          <strong id="cart-total">0,00 €</strong>
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
