<?php
/**
 * affiliates-formidable.php
 *
 * Copyright (c) www.itthinx.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author itthinx
 * @package affiliates-formidable
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin main class.
 */
class Affiliates_Formidable {

	const PLUGIN_OPTIONS = 'affiliates_formidable';

	const REFERRAL_TYPE = 'fform';

	/**
	 * Currencies suppported.
	 *
	 * @var array
	 */
	private static $supported_currencies = array(
		// Australian Dollar
		'AUD',
		// Brazilian Real
		'BRL',
		// Canadian Dollar
		'CAD',
		// Czech Koruna
		'CZK',
		// Danish Krone
		'DKK',
		// Euro
		'EUR',
		// Hong Kong Dollar
		'HKD',
		// Hungarian Forint
		'HUF',
		// Israeli New Sheqel
		'ILS',
		// Indian Rupee
		'INR',
		// Japanese Yen
		'JPY',
		// Malaysian Ringgit
		'MYR',
		// Mexican Peso
		'MXN',
		// Norwegian Krone
		'NOK',
		// New Zealand Dollar
		'NZD',
		// Philippine Peso
		'PHP',
		// Polish Zloty
		'PLN',
		// Pound Sterling
		'GBP',
		// Singapore Dollar
		'SGD',
		// Swedish Krona
		'SEK',
		// Swiss Franc
		'CHF',
		// Taiwan New Dollar
		'TWD',
		// Thai Baht
		'THB',
		// Turkish Lira
		'TRY',
		// U.S. Dollar
		'USD',
		// South African Rand
		'ZAR'
	);

	/**
	 * Admin messages array.
	 *
	 * @var array
	 */
	private static $admin_messages = array();

	/**
	 * Activation handler.
	 */
	public static function activate() {
		$options = get_option( self::PLUGIN_OPTIONS , null );
		if ( $options === null ) {
			$options = array();
			// add the options and there's no need to autoload these
			add_option( self::PLUGIN_OPTIONS, $options, '', 'no' );
		}
	}

	/**
	 * Prints admin notices.
	 */
	public static function admin_notices() {
		if ( !empty( self::$admin_messages ) ) {
			foreach ( self::$admin_messages as $msg ) {
				echo wp_kses(
					$msg,
					array(
						'a'      => array( 'href' => array(), 'target' => array(), 'title' => array() ),
						'br'     => array(),
						'div'    => array( 'class' => array() ),
						'em'     => array(),
						'p'      => array( 'class' => array() ),
						'strong' => array()
					)
				);
			}
		}
	}

	/**
	 * Initializes the integration if dependencies are verified.
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
		add_action( 'init', array( __CLASS__, 'wp_init' ) );
		if ( self::check_dependencies() ) {
			register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
		}

		sort( self::$supported_currencies );

		if ( class_exists( 'FrmFormAction' ) ) {
			require_once 'class-affiliates-formidable-affiliates-action.php';
			require_once 'class-affiliates-formidable-affiliates-registration-action.php';
			require_once 'class-affiliates-formidable-handler.php';
			require_once 'class-affiliates-formidable-registration-handler.php';
			if ( is_admin() ) {
				require_once 'class-affiliates-formidable-admin.php';
			}
		}
	}

	/**
	 * Load translations.
	 */
	public static function wp_init() {
		load_plugin_textdomain( 'affiliates-formidable', false, 'affiliates-formidable/languages' );
	}

	/**
	 * Check dependencies and print notices if they are not met.
	 *
	 * @return true if ok, false if plugins are missing
	 */
	public static function check_dependencies() {

		$result = true;

		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

		// required plugins
		$affiliates_is_active =
			in_array( 'affiliates/affiliates.php', $active_plugins ) ||
			in_array( 'affiliates-pro/affiliates-pro.php', $active_plugins ) ||
			in_array( 'affiliates-enterprise/affiliates-enterprise.php', $active_plugins );
		if ( !$affiliates_is_active ) {
			self::$admin_messages[] =
				"<div class='error'>" .
				wp_kses(
					__( 'The <strong>Affiliates Formidable Integration</strong> plugin requires an appropriate Affiliates plugin: <a href="http://www.itthinx.com/plugins/affiliates" target="_blank">Affiliates</a>, <a href="http://www.itthinx.com/plugins/affiliates-pro" target="_blank">Affiliates Pro</a> or <a href="http://www.itthinx.com/plugins/affiliates-enterprise" target="_blank">Affiliates Enterprise</a>.', 'affiliates-formidable' ),
					array(
						'strong' => array(),
						'a' => array(
							'href'   => array(),
							'target' => array()
						)
					)
				) .
				'</div>';
		}

		if ( !$affiliates_is_active ) {
			$result = false;
		}

		return $result;
	}

	/**
	 * Returns true if we are using rates.
	 *
	 * @return boolean true if using rates
	 */
	public static function using_rates() {
		$using_rates = false;
		if (
			defined( 'AFFILIATES_EXT_VERSION' ) &&
			version_compare( AFFILIATES_EXT_VERSION, '3.0.0' ) >= 0 &&
			class_exists( 'Affiliates_Referral' ) &&
			(
				!defined( 'Affiliates_Referral::DEFAULT_REFERRAL_CALCULATION_KEY' ) ||
				!get_option( Affiliates_Referral::DEFAULT_REFERRAL_CALCULATION_KEY, null )
			)
		) {
			$using_rates = true;
		}
		return $using_rates;
	}

	/**
	 * Returns currency IDs for our supported currencies.
	 * Applies the affiliates_formidable_supported_currencies filter on the array to allow modification.
	 *
	 * @return array of currency IDs
	 */
	public static function get_supported_currencies() {
		return apply_filters(
			'affiliates_formidable_supported_currencies',
			self::$supported_currencies
		);
	}
}
Affiliates_Formidable::init();
