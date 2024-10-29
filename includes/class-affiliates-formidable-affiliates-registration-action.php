<?php
/**
 * class-affiliates-formidable-affiliates-registration-action.php
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
 * Affiliate registration action for forms.
 */
class Affiliates_Formidable_Affiliates_Registration_Action extends FrmFormAction {

	/**
	 * @var string field key for checkbox to enable affiliate registration on a form
	 */
	const ENABLE       = 'affiliates_enable_registration';

	/**
	 * @var string field key for status dropdown
	 */
	const STATUS       = 'affiliates_affiliate_status';

	/**
	 * @var string field key for opt in field select
	 */
	const SIGN_UP      = 'affiliates_sign_up_field';

	/**
	 * @var string field key for automatic login option
	 */
	const ENABLE_LOGIN = 'affiliates_enable_registration_login';

	/**
	 * Adds a hook to register our affiliate registration action and one to validate field entries.
	 */
	public static function init() {
		add_action( 'frm_registered_form_actions', array( __CLASS__, 'frm_registered_form_actions' ) );
		add_filter( 'frm_validate_field_entry', array( __CLASS__, 'frm_validate_field_entry' ), 20, 3 );
		add_action( 'frm_field_input_html', array( __CLASS__, 'frm_field_input_html' ) );
		add_action( 'frm_display_form_action', array( __CLASS__, 'frm_display_form_action' ), 10, 5 );
		add_filter( 'frm_get_paged_fields', array( __CLASS__, 'frm_get_paged_fields' ), 10, 3 );
	}

	/**
	 * Register our Affiliates Registration action.
	 *
	 * @param array $actions registered actions
	 * @return array with our entry added
	 */
	public static function frm_registered_form_actions( $actions ) {
		$actions['affiliates_registration'] = __CLASS__;
		return $actions;
	}

	/**
	 * Validate the form's fields according to the Affiliates registration fields required.
	 *
	 * @param array $errors
	 * @param Object $field
	 * @param String $value
	 */
	public static function frm_validate_field_entry( $errors, $field, $value ) {
		$form_id = $field->form_id;
		$action = FrmFormAction::get_action_for_form( $form_id, 'affiliates_registration' );
		if ( is_array( $action ) && ( count( $action ) > 0 ) ) {
			$action = reset( $action );
			$affiliates_register = !empty( $action->post_content[self::ENABLE] );
			if ( $affiliates_register ) {
				$registration_fields = self::get_affiliates_registration_fields();
				if ( count( $registration_fields ) > 0 ) {
					foreach ( $registration_fields as $name => $aff_field ) {
						if ( $aff_field['enabled'] && $aff_field['required'] ) {
							if ( isset( $action->post_content[ self::get_mapped_affiliates_field_name( $name ) ] ) ) {
								$field_id = $action->post_content[ self::get_mapped_affiliates_field_name( $name ) ];
								if ( ( $field_id == $field->id ) && ( strlen( $value ) == 0 ) ) {
									$errors['field' . $field->id] = sprintf( __( 'Please fill in the field <em>%s</em>.', 'affiliates-formidable' ), esc_html( $field->name ) );
								}
							}
						}
					}
				}
			}
		}
		return $errors;
	}

	/**
	 * Set the default_value for fields related to the current user.
	 *
	 * @param array $fields form fields
	 * @param int $form_id form ID
	 * @param array $error any errors
	 *
	 * @return array form fields
	 */
	public static function frm_get_paged_fields( $fields, $form_id, $error ) {
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$action = FrmFormAction::get_action_for_form( $form_id, 'affiliates_registration' );
			if ( is_array( $action ) && ( count( $action ) > 0 ) ) {
				$action = reset( $action );
				$affiliates_register = !empty( $action->post_content[self::ENABLE] );
				if ( $affiliates_register ) {
					$field_id_to_registration_field_name = array();
					$registration_fields = self::get_affiliates_registration_fields();
					if ( count( $registration_fields ) > 0 ) {
						foreach ( $registration_fields as $name => $aff_field ) {
							if ( $aff_field['enabled'] ) {
								if ( isset( $action->post_content[ self::get_mapped_affiliates_field_name( $name ) ] ) ) {
									$mapped_field_id = $action->post_content[ self::get_mapped_affiliates_field_name( $name ) ];
									$field_id_to_registration_field_name[$mapped_field_id] = $name;
								}
							}
						}
					}
					foreach( $fields as $i => $field ) {
						if ( isset( $field_id_to_registration_field_name[$field->id] ) ) {
							$name = $field_id_to_registration_field_name[$field->id];
							switch( $name ) {
								case 'first_name' :
								case 'last_name' :
								case 'user_login' :
								case 'user_email' :
								case 'user_url' :
								case 'password' :
									if ( !empty( $user->$name ) || $name === 'password' ) {
										if ( $name !== 'password' ) {
											$fields[$i]->default_value = sanitize_user_field( $name, $user->$name, $user->ID, 'display' );
										} else {
											$fields[$i]->default_value = '********';
										}
									}
									break;
							}
						}
					}
				}
			}
		}
		return $fields;
	}

	/**
	 * Add readonly and placeholder attributes as needed.
	 *
	 * @param array $field field details
	 */
	public static function frm_field_input_html( $field ) {

		if ( !is_user_logged_in() ) {
			return;
		}

		$user = wp_get_current_user();

		$field_id = isset( $field['id'] ) ? $field['id'] : null;
		$form_id = isset( $field['form_id'] ) ? $field['form_id'] : null;
		if ( $field_id !== null && $form_id !== null ) {
			$action = FrmFormAction::get_action_for_form( $form_id, 'affiliates_registration' );
			if ( is_array( $action ) && ( count( $action ) > 0 ) ) {
				$action = reset( $action );
				$affiliates_register = !empty( $action->post_content[self::ENABLE] );
				if ( $affiliates_register ) {
					$registration_fields = self::get_affiliates_registration_fields();
					if ( count( $registration_fields ) > 0 ) {
						foreach ( $registration_fields as $name => $aff_field ) {
							if ( $aff_field['enabled'] ) {
								if ( isset( $action->post_content[ self::get_mapped_affiliates_field_name( $name ) ] ) ) {
									$mapped_field_id = $action->post_content[ self::get_mapped_affiliates_field_name( $name ) ];
									if ( ( $field_id == $mapped_field_id ) ) {
										switch( $name ) {
											case 'first_name' :
											case 'last_name' :
											case 'user_login' :
											case 'user_email' :
											case 'user_url' :
											case 'password' :
												if ( !empty( $user->$name ) || $name === 'password' ) {
													echo ' readonly="readonly" ';
													if ( $name !== 'password' ) {
														printf( ' placeholder="%s" ', esc_attr( sanitize_user_field( $name, $user->$name, $user->ID, 'display' ) ) );
													} else {
														echo ' placeholder="********" ';
													}
												}
												break;
										}
										break;
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Don't display an affiliate registration form to affiliates.
	 *
	 * @param array $params
	 * @param array $fields
	 * @param object $form
	 * @param string $title
	 * @param string $description
	 */
	public static function frm_display_form_action( $params, $fields, $form, $title, $description ) {
		remove_filter( 'frm_continue_to_new', '__return_false', 50 );
		if ( is_user_logged_in() ) {
			$action = FrmFormAction::get_action_for_form( $form->id, 'affiliates_registration' );
			if ( is_array( $action ) && ( count( $action ) > 0 ) ) {
				$action = reset( $action );
				$affiliates_register = !empty( $action->post_content[self::ENABLE] );
				if ( $affiliates_register ) {
					if (
						affiliates_user_is_affiliate() ||
						affiliates_user_is_affiliate_status( null, 'pending' ) ||
						affiliates_user_is_affiliate_status( null, 'deleted' )
					) {
						add_filter( 'frm_continue_to_new', '__return_false', 50 );
					}
				}
			}
		}
	}

	/**
	 * Produces the form field reference based on the name of an affiliate registration field.
	 *
	 * @param string $name
	 * @return string
	 */
	public static function get_mapped_affiliates_field_name( $name ) {
		return sprintf( 'affiliates_field_%s', $name );
	}

	/**
	 * Returns an array with registration fields from Affiliates > Settings > Registration.
	 *
	 * @return array of affiliate registration fields
	 */
	public static function get_affiliates_registration_fields() {
		$registration_fields = array();
		if ( defined( 'AFFILIATES_CORE_LIB' ) ) {
			include_once AFFILIATES_CORE_LIB . '/class-affiliates-settings.php';
			include_once AFFILIATES_CORE_LIB . '/class-affiliates-settings-registration.php';
			if ( class_exists( 'Affiliates_Settings_Registration' ) && method_exists( 'Affiliates_Settings_Registration', 'get_fields' ) ) {
				$registration_fields = Affiliates_Settings_Registration::get_fields();
			}
		}
		return $registration_fields;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$action_ops = array(
			'classes'  => 'frm_affiliates_registration_icon',
			'limit'    => 99,
			'active'   => true,
			'priority' => 50,
		);
		$this->FrmFormAction(
			'affiliates_registration',
			__( 'Affiliates Registration', 'affiliates-formidable' ),
			$action_ops
		);
	}

	/**
	 * Get the HTML for the affiliates registration action settings
	 *
	 * @param Object  $form_action
	 * @param array $args
	 */
	public function form( $form_action, $args = array() ) {

		$form       = isset( $args['form'] ) ? $args['form'] : null;
		$action_key = isset( $args['action_key'] ) ? $args['action_key'] : null;
		$values     = isset( $args['values'] ) ? $args['values'] : null;

		if ( $form === null ) {
			return;
		}

		$form_id = $form->id;

		$all_ff_fields = FrmField::getAll( array( 'fi.form_id' => $form_id ), 'field_order' );

		$affiliates_register = $form_action->post_content[self::ENABLE];
		$affiliates_login    = $form_action->post_content[self::ENABLE_LOGIN];
		$affiliates_optin    = $form_action->post_content[self::SIGN_UP];

		$affiliate_status = $form_action->post_content[self::STATUS];

		$selected = array();
		$registration_fields = self::get_affiliates_registration_fields();
		if ( count( $registration_fields ) > 0 ) {
			foreach ( $registration_fields as $name => $field ) {
				if ( $field['enabled'] || $field['required'] ) {
					if ( isset( $form_action->post_content[ self::get_mapped_affiliates_field_name( $name ) ] ) ) {
						$selected[$name] = $form_action->post_content[ self::get_mapped_affiliates_field_name( $name ) ];
					}
				}
			}
		}

		echo '<div class="affiliates-form-settings">';

		echo '<div>';

		echo '<h3>';
		echo esc_html__( 'Affiliate Registration', 'affiliates-formidable' );
		echo '<span class="frm_help frm_icon_font frm_tooltip_icon" title="" data-original-title="' . esc_html__( 'Create an affiliate account for new users who register through this form?', 'affiliates-formidable' ) . '"></span>';
		echo '</h3>';

		echo '<h4>';
		echo esc_html__( 'Register an Affiliate Account', 'affiliates-formidable' );
		echo '</h4>';
		echo '<label>';
		echo sprintf( '<input type="checkbox" name="' . esc_attr( $this->get_field_name( self::ENABLE ) ) . '" %s />', esc_html( $affiliates_register ? ' checked="checked" ' : '' ) );
		echo ' ';
		echo esc_html__( 'Enabled', 'affiliates-formidable' );
		echo '</label>';
		echo '<p>';
		echo esc_html__( 'If enabled, an affiliate account will be created for new users registered with this form.', 'affiliates-formidable' );
		echo '</p>';

		echo '<br/>';

		echo '<h4>';
		echo esc_html__( 'Affiliate status', 'affiliates-formidable' );
		echo '</h4>';
		echo '<label>';
		echo '<select name="' . esc_attr( $this->get_field_name( self::STATUS ) ) . '">';
		echo sprintf(
			'<option value="active" %s >%s</option>',
			esc_attr( ( $affiliate_status == 'active' ) ? 'selected="selected"' : '' ),
			esc_html__( 'Active', 'affiliates-formidable' )
		);
		echo sprintf(
			'<option value="pending" %s >%s</option>',
			esc_attr( ( $affiliate_status == 'pending' ) ? 'selected="selected"' : '' ),
			esc_html__( 'Pending', 'affiliates-formidable' )
		);
		echo '</select>';
		echo '<p>';
		echo esc_html__( 'The default status of affiliates who register through this form.', 'affiliates-formidable' );
		echo '</p>';

		echo '<br/>';

		echo '<h4>';
		echo esc_html__( 'Automatic login', 'affiliates-formidable' );
		echo '</h4>';
		echo '<label>';
		echo sprintf( '<input type="checkbox" name="' . esc_attr( $this->get_field_name( self::ENABLE_LOGIN ) ) . '" %s />', esc_html( $affiliates_login ? ' checked="checked" ' : '' ) );
		echo ' ';
		echo esc_html__( 'Enabled', 'affiliates-formidable' );
		echo '</label>';
		echo '<p>';
		echo esc_html__( 'Automatically logs new users in upon successful registration.', 'affiliates-formidable' );
		echo '</p>';

		echo '<br/>';

		echo '<h4>';
		echo esc_html__( 'Optin', 'affiliates-formidable' );
		echo '</h4>';
		echo '<label>';
		echo '<select name="' . esc_attr( $this->get_field_name( self::SIGN_UP ) ) . '">';
		$select = '';
		if ( $affiliates_optin == '' ) {
			$select = 'selected="selected"';
		}
		echo '<option value="" ' . esc_attr( $select ) . ' >' . esc_html__( '---', 'affiliates-formidable' ) . '</option>';
		foreach ( $all_ff_fields as $ff_field ) {
			$select = '';
			if ( $affiliates_optin == $ff_field->id ) {
				$select = 'selected="selected"';
			}
			echo '<option value="' . esc_attr( $ff_field->id ) . '" ' . esc_attr( $select ) . ' >' . esc_html( $ff_field->name ) . '</option>';
		}
		echo '</select>';
		echo '<p>';
		echo esc_html__( 'You can use a checkbox to let the user choose whether to sign up as an affiliate or not. The field must be a single Checkbox, appropriately labelled, usually inviting the user to "Join the Affiliate Program". If the user does not opt in, the form submission will simply allow to create the user account (without joining the affiliate program).', 'affiliates-formidable' );
		echo '</p>';

		echo '<br/>';

		echo '<h3>';
		echo esc_html__( 'Affiliates Registration Field Mapping', 'affiliates-formidable' );
		echo '<span class="frm_help frm_icon_font frm_tooltip_icon" title="" data-original-title="' . esc_html__( 'Choose the form fields that are mapped to these affiliate registration fields.', 'affiliates-formidable' ) . '"></span>';
		echo '</h3>';

		$registration_fields = self::get_affiliates_registration_fields();
		if ( count( $registration_fields ) > 0 ) {
			foreach ( $registration_fields as $name => $field ) {
				if ( $field['enabled'] || $field['required'] ) {
					$required_str = '';
					if ( $field['required'] ) {
						$required_str = '*';
					}
					echo '<p>';
					echo '<span class="frm_left_label">' . esc_html( $field['label'] . $required_str ) . ':</span>';
					echo '<select name="' . esc_attr( $this->get_field_name( self::get_mapped_affiliates_field_name( $name ) ) ) . ']">';
					echo '<option value="">' . esc_html__( 'Select a field', 'affiliates-formidable' ) . '</option>';

					foreach ( $all_ff_fields as $ff_field ) {
						$select = '';
						if ( isset( $selected[$name] ) && ( $selected[$name] == $ff_field->id ) ) {
							$select = 'selected="selected"';
						}
						echo '<option value="' . esc_attr( $ff_field->id ) . '" ' . esc_attr( $select ) . ' >' . esc_html( $ff_field->name ) . '</option>';
					}
					echo '</select>';
					echo '</p>';
				}
			}
		}

		echo '<br/>';

		echo '</div>';
		echo '</div>'; // .affiliates-form-settings
	}

	/**
	 * Add the default values for the affiliates registration action field.
	 */
	public function get_defaults() {
		$defaults = array();

		// checkbox fields must have as default value 0. It seems a bug on Formidable forms.
		$defaults[self::ENABLE]  = 0;
		$defaults[self::SIGN_UP] = 0;
		$defaults[self::STATUS]  = get_option( 'aff_status', 'active' );
		$defaults[self::ENABLE_LOGIN] = 0;

		return $defaults;
	}

}

Affiliates_Formidable_Affiliates_Registration_Action::init();
