<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/WC_Unlimit_Admin_Fields.php';

class WC_Unlimit_Admin_Order_Status_Fields extends WC_Unlimit_Admin_Fields {

	public const FIELDNAME_PREFIX = 'woocommerce_unlimit_order_status_';

	// Unlimit statuses
	public const NEW_UNLIMIT = 'new';
	public const NEW_WC_DEFAULT = self::PENDING_WC;

	public const IN_PROCESS_UNLIMIT = 'in_progress';

	public const IN_PROCESS_WC_DEFAULT = self::PENDING_WC;

	public const DECLINED_UNLIMIT = 'declined';

	public const DECLINED_WC_DEFAULT = self::FAILED_WC;

	public const AUTHORIZED_UNLIMIT = 'authorized';

	public const AUTHORIZED_WC_DEFAULT = self::ON_HOLD_WC;

	public const COMPLETED_UNLIMIT = 'completed';

	public const COMPLETED_WC_DEFAULT = self::COMPLETED_WC;

	public const CANCELED_UNLIMIT = 'canceled';

	public const CANCELED_WC_DEFAULT = self::CANCELLED_WC;

	public const VOIDED_UNLIMIT = 'voided';

	public const VOIDED_WC_DEFAULT = self::CANCELLED_WC;

	public const REFUNDED_UNLIMIT = 'refunded';

	public const REFUNDED_WC_DEFAULT = self::REFUNDED_WC;

	public const CHARGED_BACK_UNLIMIT = 'charged_back';

	public const CHARGED_BACK_WC_DEFAULT = self::ON_HOLD_WC;

	public const CHARGEBACK_RESOLVED_UNLIMIT = 'chargeback_resolved';

	public const CHARGEBACK_RESOLVED_WC_DEFAULT = self::COMPLETED_WC;

	public const TERMINATED_UNLIMIT = 'terminated';

	public const TERMINATED_WC_DEFAULT = self::PENDING_WC;

	// WooCommerce statuses
	public const PENDING_WC = 'wc-pending';
	public const FAILED_WC = 'wc-failed';
	public const COMPLETED_WC = 'wc-completed';
	public const ON_HOLD_WC = 'wc-on-hold';
	public const CANCELLED_WC = 'wc-cancelled';
	public const REFUNDED_WC = 'wc-refunded';
}
