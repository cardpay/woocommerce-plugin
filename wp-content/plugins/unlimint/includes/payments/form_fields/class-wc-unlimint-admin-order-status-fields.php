<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-wc-unlimint-admin-fields.php';

class WC_Unlimint_Admin_Order_Status_Fields extends WC_Unlimint_Admin_Fields {

	public const FIELDNAME_PREFIX = 'woocommerce_unlimint_order_status_';

	// Unlimint statuses
	public const NEW_UNLIMINT = 'new';
	public const NEW_LABEL = "Order status when payment is new";
	public const NEW_WC_DEFAULT = self::PENDING_WC;

	public const IN_PROCESS_UNLIMINT = 'in_progress';
	public const IN_PROCESS_LABEL = 'Order status when payment is in process';
	public const IN_PROCESS_WC_DEFAULT = self::PENDING_WC;

	public const DECLINED_UNLIMINT = 'declined';
	public const DECLINED_LABEL = 'Order status when payment is declined';
	public const DECLINED_WC_DEFAULT = self::FAILED_WC;

	public const AUTHORIZED_UNLIMINT = 'authorized';
	public const AUTHORIZED_LABEL = 'Order status when payment is authorized';
	public const AUTHORIZED_WC_DEFAULT = self::ON_HOLD_WC;

	public const COMPLETED_UNLIMINT = 'completed';
	public const COMPLETED_LABEL = 'Order status when payment is completed';
	public const COMPLETED_WC_DEFAULT = self::PROCESSING_WC;

	public const CANCELED_UNLIMINT = 'canceled';
	public const CANCELED_LABEL = 'Order status when payment is cancelled';
	public const CANCELED_WC_DEFAULT = self::CANCELLED_WC;

	public const VOIDED_UNLIMINT = 'voided';
	public const VOIDED_LABEL = 'Order status when payment is voided';
	public const VOIDED_WC_DEFAULT = self::CANCELLED_WC;

	public const REFUNDED_UNLIMINT = 'refunded';
	public const REFUNDED_LABEL = 'Order status when payment is refunded';
	public const REFUNDED_WC_DEFAULT = self::REFUNDED_WC;

	public const CHARGED_BACK_UNLIMINT = 'charged_back';
	public const CHARGED_BACK_LABEL = 'Order status when payment is charged back';
	public const CHARGED_BACK_WC_DEFAULT = self::ON_HOLD_WC;

	public const CHARGEBACK_RESOLVED_UNLIMINT = 'chargeback_resolved';
	public const CHARGEBACK_RESOLVED_LABEL = 'Order status when chargeback is resolved';
	public const CHARGEBACK_RESOLVED_WC_DEFAULT = self::PROCESSING_WC;

	public const TERMINATED_UNLIMINT = 'terminated';
	public const TERMINATED_LABEL = 'Order status when payment is terminated';
	public const TERMINATED_WC_DEFAULT = self::PENDING_WC;

	// WooCommerce statuses
	public const PENDING_WC = 'wc-pending';
	public const FAILED_WC = 'wc-failed';
	public const PROCESSING_WC = 'wc-processing';
	public const ON_HOLD_WC = 'wc-on-hold';
	public const CANCELLED_WC = 'wc-cancelled';
	public const REFUNDED_WC = 'wc-refunded';

	public function get_card_form_fields() {
		$form_fields = $this->get_alt_form_fields();

		$form_fields[ self::FIELDNAME_PREFIX . self::REFUNDED_UNLIMINT ]   = $this->get_order_status_select( self::REFUNDED_LABEL, self::REFUNDED_WC_DEFAULT );
		$form_fields[ self::FIELDNAME_PREFIX . self::VOIDED_UNLIMINT ]     = $this->get_order_status_select( self::VOIDED_LABEL, self::VOIDED_WC_DEFAULT );
		$form_fields[ self::FIELDNAME_PREFIX . self::TERMINATED_UNLIMINT ] = $this->get_order_status_select( self::TERMINATED_LABEL, self::TERMINATED_WC_DEFAULT );

		return $form_fields;
	}

	public function get_alt_form_fields() {
		$form_fields = [];

		$form_fields[ self::FIELDNAME_PREFIX . self::NEW_UNLIMINT ]                 = $this->get_order_status_select( self::NEW_LABEL, self::NEW_WC_DEFAULT );
		$form_fields[ self::FIELDNAME_PREFIX . self::IN_PROCESS_UNLIMINT ]          = $this->get_order_status_select( self::IN_PROCESS_LABEL, self::IN_PROCESS_WC_DEFAULT );
		$form_fields[ self::FIELDNAME_PREFIX . self::DECLINED_UNLIMINT ]            = $this->get_order_status_select( self::DECLINED_LABEL, self::DECLINED_WC_DEFAULT );
		$form_fields[ self::FIELDNAME_PREFIX . self::AUTHORIZED_UNLIMINT ]          = $this->get_order_status_select( self::AUTHORIZED_LABEL, self::AUTHORIZED_WC_DEFAULT );
		$form_fields[ self::FIELDNAME_PREFIX . self::COMPLETED_UNLIMINT ]           = $this->get_order_status_select( self::COMPLETED_LABEL, self::COMPLETED_WC_DEFAULT );
		$form_fields[ self::FIELDNAME_PREFIX . self::CANCELED_UNLIMINT ]            = $this->get_order_status_select( self::CANCELED_LABEL, self::CANCELED_WC_DEFAULT );
		$form_fields[ self::FIELDNAME_PREFIX . self::CHARGED_BACK_UNLIMINT ]        = $this->get_order_status_select( self::CHARGED_BACK_LABEL, self::CHARGED_BACK_WC_DEFAULT );
		$form_fields[ self::FIELDNAME_PREFIX . self::CHARGEBACK_RESOLVED_UNLIMINT ] = $this->get_order_status_select( self::CHARGEBACK_RESOLVED_LABEL, self::CHARGEBACK_RESOLVED_WC_DEFAULT );

		return $form_fields;
	}

	private function get_order_status_select( $label, $wc_status = '' ) {
		return [
			'title'   => __( $label, 'unlimint' ),
			'type'    => 'select',
			'default' => $wc_status,
			'options' => wc_get_order_statuses(),
		];
	}
}
