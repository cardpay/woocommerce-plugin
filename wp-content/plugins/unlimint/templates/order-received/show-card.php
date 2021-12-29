<?php

defined( 'ABSPATH' ) || exit;

$redirect_url = $transaction_details['redirect_url'];
?>

<p>
<p>
	<?php echo esc_html__( 'Great, we are processing your purchase order.', 'unlimint' ); ?>
</p>
<p>
    <iframe src="<?php echo esc_attr( $redirect_url ); ?>" style="width:100%; height:400px;" title="Card"></iframe>
</p>
<a id="submit-payment" target="_blank" href="<?php echo esc_attr( $redirect_url ); ?>" class="button alt" rel="noopener"
   style="display: none; font-size:1.25rem; width:75%; height:48px; line-height:24px; text-align:center;">
	<?php echo esc_html__( 'Authenticate Card', 'unlimint' ); ?>
</a>
</p>
