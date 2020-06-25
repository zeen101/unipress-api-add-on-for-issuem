<?php
/**
 * Main PHP file used to for initial calls to UniPress API class for IssueM classes and functions.
 *
 * @package UniPress API class for IssueM
 * @since 1.0.0
 */
 
/*
Plugin Name: UniPress API Add-on for IssueM
Plugin URI: http://getunipress.com/
Description: A premium WordPress plugin by UniPress.
Author: UniPress Development Team
Version: 1.0.0
Author URI: http://zeen101.com/
Tags:
*/

//Define global variables...
if ( !defined( 'ZEEN101_STORE_URL' ) )
	define( 'ZEEN101_STORE_URL',	'http://zeen101.com' );
	
define( 'UPAPI_ISSUEM_NAME', 	'UniPress API Add-on for IssueM' );
define( 'UPAPI_ISSUEM_SLUG', 	'unipress-api-issuem' );
define( 'UPAPI_ISSUEM_VERSION', 	'1.0.0' );
define( 'UPAPI_ISSUEM_DB_VERSION', '1.0.0' );
define( 'UPAPI_ISSUEM_URL', 		plugin_dir_url( __FILE__ ) );
define( 'UPAPI_ISSUEM_PATH', 	plugin_dir_path( __FILE__ ) );
define( 'UPAPI_ISSUEM_BASENAME', plugin_basename( __FILE__ ) );
define( 'UPAPI_ISSUEM_REL_DIR', 	dirname( UPAPI_ISSUEM_BASENAME ) );

/**
 * Instantiate UniPress API class for IssueM, require helper files
 *
 * @since 1.0.0
 */
function unipress_api_issuem_plugins_loaded() {
	
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	if ( is_plugin_active( 'issuem/issuem.php' ) && is_plugin_active( 'unipress-api/unipress-api.php' ) ) {

		require_once( 'class.php' );
	
		// Instantiate the Pigeon Pack class
		if ( class_exists( 'UniPress_API_for_IssueM' ) ) {
			
			global $unipress_api_issuem;
			
			$unipress_api_issuem = new UniPress_API_for_IssueM();
			
			//Internationalization
			load_plugin_textdomain( 'unipress-api-issuem', false, UPAPI_ISSUEM_REL_DIR . '/i18n/' );
				
		}
	
	} else {
	
		add_action( 'admin_notices', 'unipress_api_issuem_requirement_nag' );
		
	}

}
add_action( 'plugins_loaded', 'unipress_api_issuem_plugins_loaded', 4815162342 ); //wait for the plugins to be loaded before init

function unipress_api_issuem_requirement_nag() {
	?>
	<div id="leaky-paywall-requirement-nag" class="update-nag">
		<?php _e( 'You must have the IssueM and UniPress API plugins activated to use the UniPress API Add-on for IssueM plugin.' ); ?>
	</div>
	<?php
}
