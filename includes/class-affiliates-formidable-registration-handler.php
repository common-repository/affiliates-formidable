<?php
/**
 * class-affiliates-formidable-registration-handler.php
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
 * Plugin registration handler class.
 */
class Affiliates_Formidable_Registration_Handler {

	/**
	 * Adds the proper initialization action on the wp_init hook.
	 */
	public static function init() {
		// hook into after form submission, when entry created
		add_action( 'frm_trigger_affiliates_registration_create_action', array( __CLASS__, 'frm_trigger_affiliates_registration_create_action' ), 10, 3 );
	}

	/**
	 * Hook into after form submission, when entry created
	 *
	 * @param WP_Post $action The action as a post object (its post_type is 'frm_form_actions'). The form settings are provided as an array through $action->post_content.
	 * @param object $entry An object with entry data, relates to its form via $entry->form_id, holds field values in $entry->metas.
	 * @param object $form An object holding data for the form $form->id.
	 */
	public static function frm_trigger_affiliates_registration_create_action( $action, $entry, $form ) {
		global $frm_entry, $frm_entry_meta, $wpdb, $affiliates_db;

		$entry_id = $entry->id;
		$form_id  = $form->id;

		$affiliates_register    = !empty( $action->post_content[Affiliates_Formidable_Affiliates_Registration_Action::ENABLE] );
		$affiliates_optin       = true;
		$affiliates_optin_field = !empty( $action->post_content[Affiliates_Formidable_Affiliates_Registration_Action::SIGN_UP] ) ? $action->post_content[Affiliates_Formidable_Affiliates_Registration_Action::SIGN_UP] : null;
		if ( $affiliates_optin_field !== null ) {
			$affiliates_optin_value = FrmEntryMeta::get_entry_meta_by_field( $entry_id, $affiliates_optin_field );
			$affiliates_optin = !empty( $affiliates_optin_value );
		}

		if ( $affiliates_register && $affiliates_optin && ( !is_user_logged_in() || !affiliates_user_is_affiliate() ) ) {

			$status = ( isset( $action->post_content[Affiliates_Formidable_Affiliates_Registration_Action::STATUS] ) ) ? $action->post_content[Affiliates_Formidable_Affiliates_Registration_Action::STATUS] : get_option( 'aff_status', 'active' );

			if ( class_exists( 'FrmEntryMeta' ) && method_exists( 'FrmEntryMeta', 'get_entry_meta_info' ) ) {
				$all = FrmEntryMeta::get_entry_meta_info( $entry_id );
			} else {
				// Deprecated since 2.03.05
				$all = $frm_entry_meta->get_entry_meta_info( $entry_id );
			}

			if ( defined( 'AFFILIATES_CORE_LIB' ) ) {
				$user = wp_get_current_user();
				$registration_fields = Affiliates_Formidable_Affiliates_Registration_Action::get_affiliates_registration_fields();
				if ( count( $registration_fields ) > 0 ) {
					$userdata = array();
					foreach ( $registration_fields as $name => $field ) {
						if ( $field['enabled'] ) {
							$field_id = $action->post_content[ Affiliates_Formidable_Affiliates_Registration_Action::get_mapped_affiliates_field_name( $name ) ];
							$field_value = FrmEntryMeta::get_entry_meta_by_field( $entry_id, $field_id );
							switch ( $name ) {
								case 'first_name' :
								case 'last_name' :
								case 'user_login' :
								case 'user_email' :
								case 'user_url' :
									if ( is_user_logged_in() ) {
										if ( !empty( $user->$name ) ) {
											$field_value = sanitize_user_field( $name, $user->$name, $user->ID, 'display' );
										}
									}
									break;
							}
							$field_value = !empty( $field_value ) ? $field_value : null;
							if ( $field_value !== null ) {
								$userdata[$name] = $field_value;
							}
						}
					}

					$affiliate_user_id = null;
					if ( !is_user_logged_in() ) {
						do_action( 'affiliates_before_register_affiliate', $userdata );
						$affiliate_user_id = Affiliates_Registration::register_affiliate( $userdata );
						do_action( 'affiliates_after_register_affiliate', $userdata );
						$enable_login = !empty( $action->post_content[Affiliates_Formidable_Affiliates_Registration_Action::ENABLE_LOGIN] );
						if ( !is_wp_error( $affiliate_user_id ) && $enable_login ) {
							wp_set_current_user( $affiliate_user_id, $userdata['user_login'] );
							wp_set_auth_cookie( $affiliate_user_id );
							do_action( 'wp_login', $userdata['user_login'], get_user_by( 'id', $affiliate_user_id ) );
						}
					} else {
						$affiliate_user_id = $user->ID;
					}
					if ( !is_wp_error( $affiliate_user_id ) ) {
						if ( $affiliate_user_id !== null ) {
							// It's either a new user who signs up or an existing user who has chosen
							// to opt in (checked above with $affiliates_optin), for the latter there's no need to check it here again.
							$affiliate_id = Affiliates_Registration::store_affiliate( $affiliate_user_id, $userdata, $status );
							// don't update the password
							unset( $userdata['password'] );
							unset( $userdata['user_pass'] ); // shouldn't be there like that, unsetting just in case
							// update user including meta
							Affiliates_Registration::update_affiliate_user( $affiliate_user_id, $userdata );
							do_action( 'affiliates_stored_affiliate', $affiliate_id, $affiliate_user_id );
						}
					} else {
						global $affiliates_formidable_errors;
						if ( !isset( $affiliates_formidable_errors ) ) {
							$affiliates_formidable_errors = array();
						}
						/**
						 * @var WP_Error $wp_error Affiliate registration errors.
						 */
						$wp_error = $affiliate_user_id;
						foreach ( $wp_error->get_error_codes() as $error_code ) {
							$affiliates_formidable_errors[$error_code] = $wp_error->get_error_message( $error_code );
						}
						if ( count( $affiliates_formidable_errors ) > 0 ) {
							add_action( 'frm_validate_entry', array( __CLASS__, 'frm_validate_entry' ), 10, 3 );
						}
					}
				}
			}
		}
	}

	/**
	 * Validate entry - hooked on frm_validate_entry if we encounter errors at the end of processing.
	 * Supposed to add any errors but not sure if this will always work.
	 *
	 * @todo needs work?
	 *
	 * @param array $errors current errors
	 * @param array $values entry values
	 * @param boolean $exclude not sure
	 * @return array with errors
	 */
	public static function frm_validate_entry( $errors, $values, $exclude ) {
		global $affiliates_formidable_errors;
		if ( isset( $affiliates_formidable_errors ) && is_array( $affiliates_formidable_errors ) ) {
			foreach( $affiliates_formidable_errors as $error_code => $error ) {
				$errors[$error_code] = $error;
			}
		}
		remove_action( 'frm_validate_entry', array( __CLASS__, 'frm_validate_entry' ), 10 );
		return $errors;
	}
}
Affiliates_Formidable_Registration_Handler::init();
