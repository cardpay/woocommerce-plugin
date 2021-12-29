<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lc-panel-checkout">
    <div class="lc-row-checkout">
		<?php if ( 0 !== (int) $credito ) : ?>
            <div id="framePayments" class="lc-col-md-12">
                <div class="frame-tarjetas">
                    <p class="lc-subtitle-basic-checkout">
						<?php echo esc_html_e( 'Credit cards', 'unlimint' ); ?>
                        <span class="lc-badge-checkout"><?php echo esc_html_e( 'Until', 'unlimint' ); ?><?php echo esc_html( $installments ); ?>
							<?php if ( '1' === $installments ) : ?>
								<?php echo esc_html_e( 'installment', 'unlimint' ); ?>
							<?php else : ?>
								<?php echo esc_html_e( 'installments', 'unlimint' ); ?>
							<?php endif; ?></span>
                    </p>
					<?php foreach ( $tarjetas as $tarjeta ) : ?>
						<?php if ( 'credit_card' === $tarjeta['type'] ) : ?>
                            <img src="<?php echo esc_html( $tarjeta['image'] ); ?>" class="lc-img-fluid lc-img-tarjetas"
                                 alt=""/>
						<?php endif; ?>
					<?php endforeach; ?>
                </div>
            </div>
		<?php endif; ?>

		<?php if ( 0 !== $debito ) : ?>
            <div id="framePayments" class="lc-col-md-6 lc-pr-15">
                <div class="frame-tarjetas">
                    <p class="submp-title-checkout"><?php echo esc_html_e( 'Debit card', 'unlimint' ); ?></p>

					<?php foreach ( $tarjetas as $tarjeta ) : ?>
						<?php if ( 'debit_card' === $tarjeta['type'] || 'prepaid_card' === $tarjeta['type'] ) : ?>
                            <img src="<?php echo esc_html( $tarjeta['image'] ); ?>" class="lc-img-fluid lc-img-tarjetas"
                                 alt=""/>
						<?php endif; ?>
					<?php endforeach; ?>
                </div>
            </div>
		<?php endif; ?>

		<?php if ( 0 !== $efectivo ) : ?>
            <div id="framePayments" class="lc-col-md-6">
                <div class="frame-tarjetas">
                    <p class="submp-title-checkout"><?php echo esc_html_e( 'Payments in cash', 'unlimint' ); ?></p>

					<?php foreach ( $tarjetas as $tarjeta ) : ?>
						<?php if ( 'credit_card' !== $tarjeta['type'] && 'debit_card' !== $tarjeta['type'] && 'prepaid_card' !== $tarjeta['type'] ) : ?>
                            <img src="<?php echo esc_html( $tarjeta['image'] ); ?>" class="lc-img-fluid lc-img-tarjetas"
                                 alt=""/>
						<?php endif; ?>
					<?php endforeach; ?>
                </div>
            </div>
		<?php endif; ?>

		<?php if ( 'redirect' === $method ) : ?>
            <div class="lc-col-md-12 lc-pt-20">
                <div class="lc-redirect-frame">
                    <img src="<?php echo esc_html( $cho_image ); ?>" class="lc-img-fluid lc-img-redirect" alt=""/>
                    <p><?php echo esc_html_e( 'We take you to our site to complete the payment', 'unlimint' ); ?></p>
                </div>
            </div>
		<?php endif; ?>

    </div>
</div>
