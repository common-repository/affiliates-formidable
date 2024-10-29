<?php
/**
 * class-affiliates-formidable-admin.php
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
 * Plugin admin section.
 */
class Affiliates_Formidable_Admin {

	const NONCE = 'aff_formidable_admin_nonce';
	const SET_ADMIN_OPTIONS = 'set_admin_options';

	/**
	 * Adds the proper initialization action on the wp_init hook.
	 */
	public static function init() {
		add_action( 'affiliates_admin_menu', array( __CLASS__, 'affiliates_admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
	}

	/**
	 * Admin CSS.
	 */
	public static function admin_init() {
		wp_register_style( 'affiliates_formidable_admin', AFFILIATES_FORMIDABLE_PLUGIN_URL . '/css/affiliates_formidable_admin.css', array(), AFFILIATES_FORMIDABLE_VERSION );
		wp_enqueue_style( 'affiliates_formidable_admin' );
	}

	/**
	 * Adds a submenu item to the Affiliates menu for integration options.
	 */
	public static function affiliates_admin_menu() {
		$page = add_submenu_page(
			'affiliates-admin',
			__( 'Affiliates Formidable', 'affiliates-formidable' ),
			__( 'Formidable', 'affiliates-formidable' ),
			AFFILIATES_ADMINISTER_OPTIONS,
			'affiliates-admin-formidable',
			array( __CLASS__, 'affiliates_admin_formidable' )
		);
		$pages[] = $page;
		add_action( 'admin_print_styles-' . $page, 'affiliates_admin_print_styles' );
		add_action( 'admin_print_scripts-' . $page, 'affiliates_admin_print_scripts' );
	}

	/**
	 * Affiliates Formidable Forms Integration : admin section.
	 */
	public static function affiliates_admin_formidable() {
		if ( !current_user_can( AFFILIATES_ADMINISTER_OPTIONS ) ) {
			wp_die( esc_html__( 'Access denied.', 'affiliates-formidable' ) );
		}

		// css
		echo '<style type="text/css">';
		echo 'div.field { padding: 0 1em 1em 0; }';
		echo 'div.field span.label { display: inline-block; width: 20%; }';
		echo 'div.field span.description { display: block; }';
		echo 'div.buttons { padding-top: 1em; }';
		echo '</style>';

		echo '<div>';
		echo '<h2>';
		esc_html_e( 'Affiliates Formidable Forms Integration', 'affiliates-formidable' );
		echo '</h2>';
		echo '</div>';

		echo '<div class="manage" style="padding:2em;margin-right:1em;">';

		echo wp_kses(
			self::get_info(),
			array(
				'p'  => array(),
				'a'  => array( 'href' => array() ),
				'ul' => array( 'style' => array() ),
				'li' => array(),
				'img' => array( 'src' => array(), 'style' => array(), 'width' => array(), 'height' => array() ),
				'strong' => array(),
				'em' => array()
			)
		);

		echo '</div>'; // .manage

		affiliates_footer();

	}

	/**
	 * Returns information on the integration.
	 *
	 * @return string
	 */
	private static function get_info() {
		return
		'<p>' .
		sprintf(
			__( 'You have the <strong>Affiliates</strong> integration by <a href="%s">itthinx</a> for Formidable Forms installed.', 'affiliates-formidable' ),
			esc_url( 'https://www.itthinx.com/' )
		) .
		'</p>' .
		'<p>' .
		sprintf(
			wp_kses(
				__( 'It integrates <a href="%1$s">Affiliates</a>, <a href="%2$s">Affiliates Pro</a> and <a href="%3$s">Affiliates Enterprise</a> with <a href="%4$s">Formidable Forms</a>.', 'affiliates-formidable' ),
				array( 'a' => array( 'href' => array() ) )
			),
			esc_url( 'https://wordpress.org/plugins/affiliates/' ),
			esc_url( 'https://www.itthinx.com/shop/affiliates-pro/' ),
			esc_url( 'https://www.itthinx.com/shop/affiliates-enterprise/' ),
			esc_url( 'https://wordpress.org/plugins/formidable/' )
		) .
		'</p>' .
		'<p>' .
		esc_html__( 'This integration features:', 'affiliates-formidable' ) . '</li>' .
		'</p>' .
		'<ul style="list-style:inside">' .
		'<li>' .
		__( '<strong>Affiliate Registration Forms</strong> &mdash; Allow affiliates to sign up through a form provided by Formidable Forms.', 'affiliates-formidable' ) .
		' ' .
		wp_kses(
			sprintf(
				__( 'To allow affiliates to register through a form, edit the form Settings, under Form Actions add and configure the <img style="width:16px;height:16px;vertical-align:middle;" src="%s"/> <strong>Affiliates Registration</strong> action.', 'affiliates-formidable' ),
				esc_url( AFFILIATES_FORMIDABLE_PLUGIN_URL . '/images/affiliates-registration-formidable-icon.png' )
			),
			array( 'strong' => array(), 'img' => array( 'src' => array(), 'style' => array(), 'width' => array(), 'height' => array() ) )
		) .
		'</li>' .
		'<li>' .
		__( '<strong>Referrals and Leads</strong> &mdash; Allow affiliates to refer others to the site, record referrals to grant commissions on form submissions and gather leads.', 'affiliates-formidable' ) .
		' ' .
		wp_kses(
			sprintf(
				__( 'To enable referrals for a form, edit the form Settings, under Form Actions add and configure the <img style="width:16px;height:16px;vertical-align:middle;" src="%s"/> <strong>Affiliates</strong> action.', 'affiliates-formidable' ),
				esc_url( AFFILIATES_FORMIDABLE_PLUGIN_URL . '/images/affiliates-formidable-icon.png' )
			),
			array( 'strong' => array(), 'img' => array( 'src' => array(), 'style' => array(), 'width' => array(), 'height' => array() ) )
		) .
		'</li>' .
		'</ul>' .
		'<p>' .
		esc_html__( 'Please refer to these documentation pages for more details:', 'affiliates-formidable' ) .
		'<ul style="list-style:inside">' .
		'<li>' .
		sprintf(
			wp_kses( __( 'Integration with <a href="%s">Affiliates</a>', 'affiliates-formidable' ), array( 'a' => array( 'href' => array() ) ) ),
			esc_url( 'http://docs.itthinx.com/document/affiliates/setup/settings/integrations/' )
		) .
		'</li>' .
		'<li>' .
		sprintf(
			wp_kses( __( 'Integration with <a href="%s">Affiliates Pro</a>', 'affiliates-formidable' ), array( 'a' => array( 'href' => array() ) ) ),
			esc_url( 'http://docs.itthinx.com/document/affiliates-pro/setup/settings/integrations/' )
		) .
		'</li>' .
		'<li>' .
		sprintf(
			wp_kses( __( 'Integration with <a href="%s">Affiliates Enterprise</a>', 'affiliates-formidable' ), array( 'a' => array( 'href' => array() ) ) ),
			esc_url( 'http://docs.itthinx.com/document/affiliates-enterprise/setup/settings/integrations/' )
		) .
		'</li>' .
		'</ul>' .
		'</p>';
	}
}
Affiliates_Formidable_Admin::init();
