<?php

defined( 'ABSPATH' ) || exit;

$redirect_url = $transaction_details['redirect_url'];
?>

<p>
<p>
	<?php echo esc_html__( 'Great, we processed your purchase order. Complete the payment with Boleto so that we finish approving it.', 'unlimint' ); ?>
</p>
<p>
    <iframe src="<?php echo esc_attr( $redirect_url ); ?>" style="width:100%; height:1000px;" title="Boleto"></iframe>
</p>
<a id="submit-payment" target="_blank" href="<?php echo esc_attr( $redirect_url ); ?>" class="button alt" rel="noopener"
   style="font-size:1.25rem; width:75%; height:48px; line-height:24px; text-align:center;">
	<?php echo esc_html__( 'Print ticket', 'unlimint' ); ?>
</a>
</p>
