<?php

defined( 'ABSPATH' ) || exit;

include_once __DIR__ . '/../notification/class-wc-unlimint-notification-abstract.php';
include_once __DIR__ . '/../notification/class-wc-unlimint-notification-webhook.php';
include_once __DIR__ . '/log/class-wc-unlimint-logger.php';
include_once __DIR__ . '/../payments/hooks/class-wc-unlimint-hook-abstract.php';
include_once __DIR__ . '/../payments/hooks/class-wc-unlimint-hook-custom.php';
include_once __DIR__ . '/../payments/hooks/class-wc-unlimint-hook-ticket.php';
include_once __DIR__ . '/preference/class-wc-unlimint-module-abstract.php';
include_once __DIR__ . '/preference/class-wc-unlimint-module-custom.php';
include_once __DIR__ . '/preference/class-wc-unlimint-module-ticket.php';
include_once __DIR__ . '/../payments/class-wc-unlimint-gateway-abstract.php';
include_once __DIR__ . '/../payments/class-wc-unlimint-custom-gateway.php';
include_once __DIR__ . '/../payments/class-wc-unlimint-ticket-gateway.php';

class WC_Unlimint_Module extends WC_Unlimint_Configs {

	/**
	 * @var Unlimint_Sdk
	 */
	public static $instance;

	/**
	 * @var array
	 */
	public static $ul_instance_payment = [];

	/**
	 * @var Unlimint_Sdk
	 */
	public static $unlimint_sdk;

	/**
	 * @var string
	 */
	public static $payments_name;

	/**
	 * @var array
	 */
	public static $notices = [];

	/**
	 * @var WC_Unlimint_Logger
	 */
	private $logger;

	public function __construct() {
		try {
			$this->load_configs();
			$this->logger = new WC_Unlimint_Logger();

			add_filter( 'woocommerce_payment_gateways', [ $this, 'set_payment_gateway' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'load_admin_css' ] );
			add_filter( 'woocommerce_available_payment_gateways', [ $this, 'filter_payment_method_by_shipping' ] );
			add_filter( 'plugin_action_links_' . WC_UNLIMINT_BASENAME, [ $this, 'unlimint_settings_link' ] );
			add_filter( 'plugin_row_meta', [ $this, 'ul_plugin_row_meta' ], 10, 2 );
		} catch ( Exception $e ) {
			if ( ! is_null( $this->logger ) ) {
				$this->logger->error( __FUNCTION__, $e->getMessage() );
			}
		}
	}

	/**
	 * @param $payment_gateway_id
	 *
	 * @return Unlimint_Sdk UnlimintSdk.
	 * @throws WC_Unlimint_Exception Error.
	 */
	public static function get_unlimint_sdk( $payment_gateway_id ) {
		/**
		 * @var Unlimint_Sdk
		 */
		$sdk = new Unlimint_Sdk( $payment_gateway_id );
		if ( ! isset( $sdk ) || is_null( $sdk ) ) {
			return $sdk;
		}

		$email = ( 0 !== wp_get_current_user()->ID ) ? wp_get_current_user()->user_email : null;
		$sdk->set_email( $email );

		$locale              = get_locale();
		$is_underscore_found = strpos( $locale, '_' ) && 5 === strlen( $locale );
		$locale              = ( false !== $is_underscore_found ) ? explode( '_', $locale ) : [ '', '' ];
		$sdk->set_locale( $locale[1] );

		return $sdk;
	}

	/**
	 * @param null|object $payment payment.
	 *
	 * @return Unlimint_Sdk|null
	 * @throws WC_Unlimint_Exception Error.
	 */
	public static function get_sdk_instance_singleton( $payment = null ) {
		$sdk = null;

		if ( ! empty( $payment ) ) {
			$class = get_class( $payment );
			if ( ! isset( self::$ul_instance_payment[ $class ] ) ) {
				self::$ul_instance_payment[ $class ] = self::get_unlimint_sdk( null );
				$sdk                                 = self::$ul_instance_payment[ $class ];
				if ( $sdk !== null ) {
					return $sdk;
				}
			}
		}

		if ( null === self::$unlimint_sdk || empty( $sdk ) ) {
			self::$unlimint_sdk = self::get_unlimint_sdk( null );
		}

		return self::$unlimint_sdk;
	}

	/**
	 * Init Unlimint Class
	 *
	 * @return WC_Unlimint_Module|null
	 * Singleton
	 */
	public static function init_unlimint_class() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load Config / Categories
	 *
	 * @return void
	 */
	public function load_configs() {
		self::$payments_name = $this->set_payment_gateway();
	}

	/**
	 * @return void
	 */
	public function load_admin_css() {
		if ( is_admin() ) {
			wp_enqueue_style(
				'unlimint-basic-config-styles',
				plugins_url( '../assets/css/config_unlimint.css', plugin_dir_path( __FILE__ ) ),
				[],
				WC_Unlimint_Constants::VERSION
			);
		}
	}

	/**
	 * @param array $methods methods.
	 *
	 * @return array
	 */
	public function filter_payment_method_by_shipping( $methods ) {
		return $methods;
	}

	/**
	 * Unlimint Settings Link add settings link on plugin page.
	 * Enable Payment Notice
	 *
	 * @param array $links links.
	 *
	 * @return array
	 */
	public function unlimint_settings_link( $links ) {
		$plugin_links   = [];
		$plugin_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Set up', 'unlimint' ) . '</a>';

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param mixed $links Plugin Row Meta.
	 * @param mixed $file Plugin Base file.
	 *
	 * @return array
	 */
	public function ul_plugin_row_meta( $links, $file ) {
		return (array) $links;
	}

	/**
	 * Get WooCommerce instance
	 * Summary: Check if we have valid credentials for v1.
	 * Description: Check if we have valid credentials.
	 *
	 * @return WooCommerce true/false depending on the validation result.
	 */
	public static function woocommerce_instance() {
		if ( function_exists( 'WC' ) ) {
			return WC();
		}

		global $woocommerce;

		return $woocommerce;
	}

	/**
	 * Get Common Error Messages function
	 *
	 * @param string $key Key.
	 *
	 * @return string
	 */
	public static function get_common_error_message( $key ) {
		switch ( $key ) {
			case  'Invalid payment_method_id':
				$error_message = __( 'The payment method is not valid or not available.', 'unlimint' );
				break;
			case  'Invalid transaction_amount':
				$error_message = __( 'The transaction amount cannot be processed by Unlimint.', 'unlimint' ) . ' ' .
				                 __( 'Possible causes: Currency not supported; Amounts below the minimum or above the maximum allowed.', 'unlimint' );
				break;

			case 'Invalid users involved':
				$error_message = __( 'The users are not valid.', 'unlimint' ) . ' ' .
				                 __( 'Possible causes: Buyer and seller have the same account in Unlimint; The transaction involving production and test users.', 'unlimint' );
				break;

			case 'Unauthorized use of live credentials':
				$error_message = __( 'Unauthorized use of production credentials.', 'unlimint' ) . ' ' .
				                 __( 'Possible causes: Use permission in use for the credential of the seller.', 'unlimint' );
				break;

			default:
				$error_message = $key;
		}

		return $error_message;
	}

	/**
	 * Fix url ampersand
	 * Fix to URL Problem : #038; replaces & and breaks the navigation.
	 *
	 * @param string $link Link.
	 *
	 * @return string
	 */
	public static function fix_url_ampersand( $link ) {
		return str_replace( [ '&#038;', '\/' ], [ '&', '/' ], $link );
	}

	/**
	 * Summary: Find template's folder.
	 * Description: Find template's folder.
	 *
	 * @return string string that identifies the path.
	 */
	public static function get_templates_path() {
		return plugin_dir_path( __FILE__ ) . '../../templates/';
	}

	/**
	 * @return bool
	 */
	public static function is_wc_new_version() {
		return WC()->version > '4.0.0';
	}

	/**
	 * @return bool
	 */
	public static function is_mobile() {
		$mobile = false;

		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		if ( preg_match( '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $user_agent ) || preg_match( '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr( $user_agent, 0, 4 ) ) ) {
			$mobile = true;
		}

		return $mobile;
	}
}
