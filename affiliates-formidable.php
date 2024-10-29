<?php
/**
 * affiliates-formidable.php
 *
 * Copyright (c) 2015 - 2017 www.itthinx.com
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
 *
 * Plugin Name: Affiliates Formidable
 * Plugin URI: http://www.itthinx.com/plugins/affiliates-formidable/
 * Description: Integrates <a href="https://wordpress.org/plugins/affiliates/">Affiliates</a>, <a href="https://www.itthinx.com/shop/affiliates-pro/">Affiliates Pro</a> and <a href="https://www.itthinx.com/shop/affiliates-enterprise/">Affiliates Enterprise</a> with <a href="https://wordpress.org/plugins/formidable/">Formidable Forms</a>.
 * Author: itthinx
 * Author URI: http://www.itthinx.com/
 * Donate-Link: http://www.itthinx.com/shop/affiliates-enterprise/
 * License: GPLv3
 * Version: 3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AFFILIATES_FORMIDABLE_VERSION', '3.1.0' );

define( 'AFFILIATES_FORMIDABLE_PLUGIN_DOMAIN', 'affiliates-formidable' );
define( 'AFFILIATES_FORMIDABLE_FILE', __FILE__ );
define( 'AFFILIATES_FORMIDABLE_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'AFFILIATES_FORMIDABLE_INCLUDES', AFFILIATES_FORMIDABLE_DIR . '/includes' );
define( 'AFFILIATES_FORMIDABLE_PLUGIN_URL', plugins_url( 'affiliates-formidable' ) );

/**
 * Boot the plugin.
 */
function affiliates_formidable_plugins_loaded() {
	require_once AFFILIATES_FORMIDABLE_INCLUDES . '/class-affiliates-formidable.php';
}
add_action( 'plugins_loaded', 'affiliates_formidable_plugins_loaded' );
