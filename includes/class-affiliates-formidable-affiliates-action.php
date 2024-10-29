<?php
/**
 * class-affiliates-formidable-affiliates-action.php
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
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin admin section.
 */
class Affiliates_Formidable_Affiliates_Action extends FrmFormAction {

	/**
	 * @var string field key to enable referrals on a form
	 */
	const ENABLE = 'affiliates_enable_referrals';

	/**
	 * @var string field key to indicate the form field that should provide the base amount
	 */
	const BASE_AMOUNT = 'affiliates_base_amount';

	/**
	 * @var string field key to indicate the form field that should provide the currency
	 */
	const CURRENCY = 'affiliates_currency';

	/**
	 * @var string the currency ID used for the form (used when no field is chosen or as fallback)
	 */
	const CURRENCY_ID = 'affiliates_currency_id';

	/**
	 * @var string field key for the amount (used when rates do not apply)
	 */
	const AMOUNT = 'affiliates_referral_amount';

	/**
	 * @var string field key for the rate (used when rates do not apply)
	 */
	const RATE = 'affiliates_referral_rate';

	/**
	 * Adds a hook to register our referral action.
	 */
	public static function init() {
		add_action( 'frm_registered_form_actions', array( __CLASS__, 'frm_registered_form_actions' ) );
	}

	/**
	 * Register our Affiliates Registration action.
	 *
	 * @param array $actions registered actions
	 * @return array with our entry added
	 */
	public static function frm_registered_form_actions( $actions ) {
		$actions['affiliates'] = __CLASS__;
		return $actions;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$action_ops = array(
			'classes'   => 'frm_affiliates_icon',
			'limit'     => 99,
			'active'    => true,
			'priority'  => 50,
		);
		$this->FrmFormAction(
			'affiliates',
			esc_html__( 'Affiliates', 'affiliates-formidable' ),
			$action_ops
		);
	}

	/**
	 * Get the HTML for the affiliates action settings
	 *
	 * @param Object $form_action
	 * @param array $args
	 */
	public function form( $form_action, $args = array() ) {

		$form       = isset( $args['form'] ) ? $args['form'] : null;
		$action_key = isset( $args['action_key'] ) ? $args['action_key'] : null;
		$values     = isset( $args['values'] ) ? $args['values'] : null;

		if ( $form === null ) {
			return;
		}

		$action_control = $this;

		$form_id = $form->id;

		$enable      = !empty( $form_action->post_content[self::ENABLE] );
		$base_amount = !empty( $form_action->post_content[self::BASE_AMOUNT] ) ? trim( $form_action->post_content[self::BASE_AMOUNT] ) : '';
		$currency    = !empty( $form_action->post_content[self::CURRENCY] ) ? trim( $form_action->post_content[self::CURRENCY] ) : '';
		$currency_id = isset( $form_action->post_content[self::CURRENCY_ID] ) ? $form_action->post_content[self::CURRENCY_ID] : '';

		if ( !Affiliates_Formidable::using_rates() ) {
			$amount_value = isset( $form_action->post_content[self::AMOUNT] ) ? $form_action->post_content[self::AMOUNT] : '';
			$rate_value   = isset( $form_action->post_content[self::RATE] ) ? $form_action->post_content[self::RATE] : '';
		}

		echo '<div class="affiliates-form-settings">';

		echo '<h5>';
		echo esc_html__( 'Referrals', 'affiliates-formidable' );
		echo '</h5>';

		echo '<p>';
		echo '<label>';
		echo '<input type="checkbox" name="' . esc_attr( $action_control->get_field_name( self::ENABLE ) ) . '" ' . esc_html( $enable ? ' checked="checked" ' : '' ) . ' />';
		echo ' ';
		echo esc_html__( 'Enable Referrals', 'affiliates-formidable' );
		echo ' ';
		printf(
			'<span class="frm_help frm_icon_font frm_tooltip_icon" title="" data-original-title="%s"></span>',
			esc_attr__( 'Allow affiliates to earn commissions on submissions of this form.', 'affiliates-formidable' ) .
			' ' .
			esc_attr__( 'If enabled, referrals can be recorded for form submissions that have been referred by an affiliate.', 'affiliates-formidable' )
		);
		echo '</label>';
		echo '</p>';

		$fields = FrmField::getAll( array( 'fi.form_id' => $form_id ), 'field_order' );
		echo '<p>';
		echo '<label>';
		echo esc_html__( 'Transaction Base Amount', 'affiliates-formidable' );
		echo ' ';
		printf( '<select name="%s">', esc_attr( $this->get_field_name( self::BASE_AMOUNT ) ) );
		printf(
			'<option value="" %s>%s</option>',
			empty( $base_amount ) ? ' selected="selected" ' : '',
			esc_attr__( '--', 'affiliates-formidable' )
		);
		foreach ( $fields as $field ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $field->id ),
				$base_amount === $field->id ? ' selected="selected" ' : '',
				esc_html( $field->name )
			);
		}
		echo '</select>';
		echo ' ';
		printf(
			'<span class="frm_help frm_icon_font frm_tooltip_icon" title="" data-original-title="%s"></span>',
			esc_attr__( 'You can choose the field that is used to calculate the commission here.', 'affiliates-formidable' )
		);
		echo '</label>';
		echo '</p>';

		echo '<p>';
		echo '<label>';
		echo esc_html__( 'Currency by Field', 'affiliates-formidable' );
		echo ' ';
		printf( '<select name="%s">', esc_attr( $this->get_field_name( self::CURRENCY ) ) );
		printf(
			'<option value="" %s>%s</option>',
			empty( $currency ) ? ' selected="selected" ' : '',
			esc_attr__( '--', 'affiliates-formidable' )
		);
		foreach ( $fields as $field ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $field->id ),
				$currency === $field->id ? ' selected="selected" ' : '',
				esc_html( $field->name )
			);
		}
		echo '</select>';
		echo ' ';
		printf(
			'<span class="frm_help frm_icon_font frm_tooltip_icon" title="" data-original-title="%s"></span>',
			esc_attr__( 'You can choose the field that provides the currency code (USD, EUR, ...) used for referrals recorded through this form.', 'affiliates-formidable' )
		);
		echo '</label>';
		echo ' ';
		echo __( 'or', 'affiliates-formidable' );
		echo ' ';
		echo '<label>';
		echo esc_html__( 'Fixed Currency', 'affiliates-formidable' );
		echo ' ';
		printf(
			'<select name="%s">',
			esc_attr( $action_control->get_field_name( self::CURRENCY_ID ) )
		);
		printf(
			'<option value="" %s>%s</option>',
			empty( $currency_id ) ? ' selected="selected" ' : '',
			esc_attr__( '--', 'affiliates-formidable' )
		);
		foreach ( Affiliates_Formidable::get_supported_currencies() as $c_id ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $c_id ),
				$currency_id === $c_id ? ' selected="selected" ' : '',
				esc_html( $c_id )
			);
		}
		echo '</select>';
		echo ' ';
		printf(
			'<span class="frm_help frm_icon_font frm_tooltip_icon" title="" data-original-title="%s"></span>',
			esc_attr__( 'The currency code used for referrals recorded for this form.', 'affiliates-formidable' ) .
			' ' .
			esc_attr__( 'This is used when no currency field is chosen or as a fallback when the chosen field does not provide a currency.', 'affiliates-formidable' )
		);
		echo '</label>';
		echo '</p>';

		if ( Affiliates_Formidable::using_rates() ) {
			echo '<h5>';
			echo esc_html__( 'Rates', 'affiliates-formidable' );
			echo '</h5>';
			$rates = Affiliates_Rate::get_rates( array( 'integration' => 'affiliates-formidable', 'object_id' => $form_id ) );
			if ( count( $rates ) > 0 ) {
				echo '<p>';
				echo esc_html( _n( 'This specific rate applies to this form', 'These specific rates apply to this form.', count( $rates ), 'affiliates-formidable' ) );
				echo '</p>';
				$odd      = true;
				$is_first = true;
				echo '<table style="width:100%">';
				foreach ( $rates as $rate ) {
					if ( $is_first ) {
						echo wp_kses_post( $rate->view( array( 'style' => 'table', 'titles' => true, 'exclude' => 'integration', 'prefix_class' => 'odd' ) ) );
					} else {
						echo wp_kses_post( $rate->view( array( 'style' => 'table', 'exclude' => 'integration', 'prefix_class' => $odd ? 'odd' : 'even' ) ) );
					}
					$is_first = false;
					$odd      = !$odd;
				}
				echo '</table>';
			} else {
				echo '<p>';
				echo esc_html( __( 'This form has no specific applicable rates.', 'affiliates-formidable' ) );
				echo '</p>';
			}
			if ( current_user_can( AFFILIATES_ADMINISTER_OPTIONS ) ) {
				echo '<p>';
				$url = wp_nonce_url(
					add_query_arg(
						array(
							'object_id'   => $form_id,
							'integration' => 'affiliates-formidable',
							'action'      => 'create-rate'
						),
						admin_url( 'admin.php?page=affiliates-admin-rates' )
					)
				);
				echo sprintf(
					'<a href="%s">',
					esc_url( $url )
				);
				echo esc_html__( 'Create a rate', 'affiliates-formidable' );
				echo '</a>';
				echo '</p>';
			}
		} else {
			echo '<p>';
			echo '<label>';
			echo esc_html__( 'Referral Amount', 'affiliates-formidable' );
			echo ' ';
			printf(
				'<input type="text" name="%s" value="%s" />',
				esc_attr( $action_control->get_field_name( self::AMOUNT ) ),
				esc_attr( $amount_value !== null ? $amount_value : '' )
			);
			echo ' ';
			printf(
				'<span class="frm_help frm_icon_font frm_tooltip_icon" title="" data-original-title="%s"></span>',
				esc_attr__( 'If a fixed amount is desired, input the referral amount to be credited for form submissions.', 'affiliates-formidable' ) .
				' ' .
				esc_attr__( 'Leave this empty if a commission based on the Transaction Base Amount should be granted.', 'affiliates-formidable' )
			);
			echo '</label>';
			echo '</p>';

			echo '<p>';
			echo '<label>';
			echo esc_html__( 'Referral Rate', 'affiliates-formidable' );
			echo ' ';
			printf(
				'<input type="text" name="%s" value="%s" />',
				esc_attr( $action_control->get_field_name( self::RATE ) ),
				esc_attr( $rate_value !== null ? $rate_value : '' )
			);
			echo ' ';
			printf(
				'<span class="frm_help frm_icon_font frm_tooltip_icon" title="" data-original-title="%s"></span>',
				esc_attr__( 'If the referral amount should be calculated based on the form total, input the rate to be used.', 'affiliates-formidable' ) .
				' ' .
				esc_attr__( 'For example, use 0.1 to grant a commission of 10%.', 'affiliates-formidable' ) .
				' ' .
				esc_attr__( 'Leave this empty if a fixed commission should be granted.', 'affiliates-formidable' )
			);
			echo '</label>';
			echo '</p>';
		}

		echo '</div>'; // .affiliates-form-settings

	}

	/**
	 * Add the default values for the affiliates action field.
	 */
	public function get_defaults() {
		$defaults = array();
		$defaults[self::ENABLE] = 0;
		$defaults[self::BASE_AMOUNT] = '';
		$defaults[self::CURRENCY] = '';
		$defaults[self::CURRENCY_ID] = '';
		if ( !Affiliates_Formidable::using_rates() ) {
			$defaults[self::AMOUNT] = '';
			$defaults[self::RATE] = '';
		}
		return $defaults;
	}

}

Affiliates_Formidable_Affiliates_Action::init();
