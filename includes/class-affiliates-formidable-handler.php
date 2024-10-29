<?php
/**
 * class-affiliates-formidable-handler.php
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
 * Plugin main handler class.
 */
class Affiliates_Formidable_Handler {

	/**
	 * Adds the proper initialization action on the wp_init hook.
	 */
	public static function init() {
		add_action( 'frm_trigger_affiliates_create_action', array( __CLASS__, 'frm_trigger_affiliates_create_action' ), 10, 3 );
	}

	/**
	 * Hook into after form submission, when entry created
	 *
	 * @param object $action
	 * @param object $entry
	 * @param object $form
	 */
	public static function frm_trigger_affiliates_create_action( $action, $entry, $form ) {
		global $frm_entry, $frm_entry_meta, $wpdb, $affiliates_db;

		$affiliates_enabled = $action->post_content[Affiliates_Formidable_Affiliates_Action::ENABLE];

		if ( !$affiliates_enabled ) {
			return;
		}

		$entry_id = $entry->id;
		$form_id  = $form->id;

		$description = sprintf( __( 'Formidable Forms form #%d submission #%d', 'affiliates-formidable' ), intval( $form_id ), intval( $entry_id ) );

		if ( method_exists( 'FrmEntryMeta', 'get_entry_meta_info' ) ) {
			$all = FrmEntryMeta::get_entry_meta_info( $entry_id );
		} else {
			// Deprecated since 2.03.05
			$all = $frm_entry_meta->get_entry_meta_info( $entry_id );
		}

		$data = array();
		$field_object = new FrmField();
		foreach ( $all as $meta ) {
			$field = $field_object->getOne( $meta->field_id );
			$value = maybe_unserialize( $meta->meta_value );
			if ( is_array( $value ) ) {
				$value = implode( ',', $value );
			}
			$value = wp_strip_all_tags( $value );
			$data[$field->field_key] = array(
				'title'  => $field->name,
				'domain' => 'affiliates-formidable',
				'value'  => $value
			);
		}

		$base_amount = null;
		$affiliate_ids = null;

		if ( isset( $data['affiliate_id'] ) && !empty( $data['affiliate_id']['value'] ) && is_numeric( $data['affiliate_id']['value'] ) ) {
			$affiliate_ids = array( intval( $data['affiliate_id']['value'] ) );
		}

		if ( isset( $data['affiliate_login'] ) && !empty( $data['affiliate_login']['value'] ) ) {
			if ( $user = get_user_by( 'login', $data['affiliate_login']['value'] ) ) {
				$affiliate_ids = affiliates_get_user_affiliate( $user->ID );
			}
		}

		$currency_id = '';
		$currency_field  = !empty( $action->post_content[Affiliates_Formidable_Affiliates_Action::CURRENCY] ) ? $action->post_content[Affiliates_Formidable_Affiliates_Action::CURRENCY] : '';
		if ( !empty( $currency_field ) ) {
			// note that if the currency comes through the field, we'll take anything
			$currency_id = FrmEntryMeta::get_entry_meta_by_field( $entry_id, $currency_field );
		}
		if ( empty( $currency_id ) ) {
			$currency_id  = !empty( $action->post_content[Affiliates_Formidable_Affiliates_Action::CURRENCY_ID] ) ? $action->post_content[Affiliates_Formidable_Affiliates_Action::CURRENCY_ID] : '';
		}
		if ( empty( $currency_id ) ) {
			$currency_id = apply_filters( 'affiliates_formidable_fallback_currency_id', 'USD' );
		}

		$base_amount = '0';
		$base_amount_field  = !empty( $action->post_content[Affiliates_Formidable_Affiliates_Action::BASE_AMOUNT] ) ? $action->post_content[Affiliates_Formidable_Affiliates_Action::BASE_AMOUNT] : '';
		if ( !empty( $base_amount_field ) ) {
			$base_amount = FrmEntryMeta::get_entry_meta_by_field( $entry_id, $base_amount_field );
		}
		$base_amount = bcadd( '0', $base_amount, affiliates_get_referral_amount_decimals() );

		if ( Affiliates_Formidable::using_rates() ) {
			// Using Affiliates 3.x API
			$referrer_params = array();
			$rc = new Affiliates_Referral_Controller();
			if ( $affiliate_ids !== null ) {
				foreach ( $affiliate_ids as $affiliate_id ) {
					$referrer_params[] = array( 'affiliate_id' => $affiliate_id );
				}
			} else {
				if ( $params = $rc->evaluate_referrer() ) {
					$referrer_params[] = $params;
				}
			}

			$n = count( $referrer_params );
			if ( $n > 0 ) {
				foreach ( $referrer_params as $params ) {
					$affiliate_id = $params['affiliate_id'];
					$group_ids = null;
					if ( class_exists( 'Groups_User' ) ) {
						if ( $affiliate_user_id = affiliates_get_affiliate_user( $affiliate_id ) ) {
							$groups_user = new Groups_User( $affiliate_user_id );
							$group_ids = $groups_user->group_ids_deep;
							if ( !is_array( $group_ids ) || ( count( $group_ids ) === 0 ) ) {
								$group_ids = null;
							}
						}
					}

					$referral_items = array();
					if ( $rate = $rc->seek_rate( array(
						'affiliate_id' => $affiliate_id,
						'object_id'    => $form_id,
						'term_ids'     => null,
						'integration'  => 'affiliates-formidable',
						'group_ids'    => $group_ids
					) ) ) {
						$rate_id = $rate->rate_id;
						$amount = $base_amount;
						switch ( $rate->type ) {
							case AFFILIATES_PRO_RATES_TYPE_AMOUNT :
								$amount = bcadd( '0', $rate->value, affiliates_get_referral_amount_decimals() );
								break;
							case AFFILIATES_PRO_RATES_TYPE_RATE :
								$amount = bcmul( $amount, $rate->value, affiliates_get_referral_amount_decimals() );
								break;
							case AFFILIATES_PRO_RATES_TYPE_FORMULA :
								$tokenizer = new Affiliates_Formula_Tokenizer( $rate->get_meta( 'formula' ) );
								// We don't support variable quantities on several items here so just use 1 as quantity.
								$quantity = 1;
								$variables = apply_filters(
									'affiliates_formula_computer_variables',
									array(
										's' => $base_amount,
										't' => $base_amount,
										'p' => $base_amount / $quantity,
										'q' => $quantity
									),
									$rate,
									array(
										'affiliate_id' => $affiliate_id,
										'integration'  => 'affiliates-formidable',
										'form_id'      => $form_id,
										'entry_id'     => $entry_id
									)
								);
								$computer = new Affiliates_Formula_Computer( $tokenizer, $variables );
								$amount = $computer->compute();
								if ( $computer->has_errors() ) {
									affiliates_log_error( $computer->get_errors_pretty( 'text' ) );
								}
								if ( $amount === null || $amount < 0 ) {
									$amount = 0.0;
								}
								$amount = bcadd( '0', $amount, affiliates_get_referral_amount_decimals() );
								break;
						}
						// split proportional total if multiple affiliates are involved
						if ( $n > 1 ) {
							$amount = bcdiv( $amount, $n, affiliates_get_referral_amount_decimals() );
						}

						$referral_item = new Affiliates_Referral_Item( array(
							'rate_id'     => $rate_id,
							'amount'      => $amount,
							'currency_id' => $currency_id,
							'type'        => 'frm_forms', // table name
							'reference'   => $entry_id,
							'line_amount' => $amount,
							'object_id'   => $form_id
						) );
						$referral_items[] = $referral_item;
					}
					$params['post_id']          = $entry_id;
					$params['description']      = $description;
					$params['data']             = $data;
					$params['currency_id']      = $currency_id;
					$params['type']             = Affiliates_Formidable::REFERRAL_TYPE;
					$params['referral_items']   = $referral_items;
					$params['reference']        = $form_id;
					$params['reference_amount'] = $amount;
					$params['integration']      = 'affiliates-formidable';

					$rc->add_referral( $params );
				}
			}
		} else {

			$referral_amount = !empty( $action->post_content[Affiliates_Formidable_Affiliates_Action::AMOUNT] ) ? $action->post_content[Affiliates_Formidable_Affiliates_Action::AMOUNT] : '0';
			$referral_rate   = !empty( $action->post_content[Affiliates_Formidable_Affiliates_Action::RATE] ) ? $action->post_content[Affiliates_Formidable_Affiliates_Action::RATE] : '0';

			if ( empty( $referral_amount ) && !empty( $referral_rate ) ) {
				$referral_amount = bcmul( $referral_rate, $base_amount, affiliates_get_referral_amount_decimals() );
			}
			if ( class_exists( 'Affiliates_Referral_WordPress' ) ) {
				$r = new Affiliates_Referral_WordPress();
				$affiliate_id = $r->evaluate( $form_id, $description, $data, null, $referral_amount, $currency_id, null, Affiliates_Formidable::REFERRAL_TYPE, $form_id );
			} else {
				$affiliate_id = affiliates_suggest_referral( $form_id, $description, $data, $referral_amount, $currency_id, null, Affiliates_Formidable::REFERRAL_TYPE, $form_id );
			}
		}

	}

}
Affiliates_Formidable_Handler::init();
