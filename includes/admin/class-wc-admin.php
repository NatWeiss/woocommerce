<?php
/**
 * WooCommerce Admin.
 *
 * @class       WC_Admin
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Admin class.
 */
class WC_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'current_screen', array( $this, 'conditonal_includes' ) );
		add_action( 'admin_init', array( $this, 'prevent_admin_access' ) );
		add_action( 'admin_init', array( $this, 'preview_emails' ) );
		add_action( 'admin_footer', 'wc_print_js', 25 );
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		// Functions
		include_once( 'wc-admin-functions.php' );
		include_once( 'wc-meta-box-functions.php' );

		// Classes
		include_once( 'class-wc-admin-post-types.php' );
		include_once( 'class-wc-admin-taxonomies.php' );

		// Classes we only need during non-ajax requests
		if ( ! is_ajax() ) {
			include_once( 'class-wc-admin-menus.php' );
			include_once( 'class-wc-admin-welcome.php' );
			include_once( 'class-wc-admin-notices.php' );
			include_once( 'class-wc-admin-assets.php' );
			include_once( 'class-wc-admin-webhooks.php' );

			// Help
			if ( apply_filters( 'woocommerce_enable_admin_help_tab', true ) ) {
				include_once( 'class-wc-admin-help.php' );
			}
		}

		// Importers
		if ( defined( 'WP_LOAD_IMPORTERS' ) ) {
			include_once( 'class-wc-admin-importers.php' );
		}
	}

	/**
	 * Include admin files conditionally
	 */
	public function conditonal_includes() {

		$screen = get_current_screen();

		switch ( $screen->id ) {
			case 'dashboard' :
				include( 'class-wc-admin-dashboard.php' );
			break;
			case 'options-permalink' :
				include( 'class-wc-admin-permalink-settings.php' );
			break;
			case 'users' :
			case 'user' :
			case 'profile' :
			case 'user-edit' :
				include( 'class-wc-admin-profile.php' );
			break;
		}
	}

	/**
	 * Prevent any user who cannot 'edit_posts' (subscribers, customers etc) from accessing admin
	 */
	public function prevent_admin_access() {

		$prevent_access = false;

		if ( 'yes' == get_option( 'woocommerce_lock_down_admin' ) && ! is_ajax() && ! ( current_user_can( 'edit_posts' ) || current_user_can( 'manage_woocommerce' ) ) && basename( $_SERVER["SCRIPT_FILENAME"] ) !== 'admin-post.php' ) {
			$prevent_access = true;
		}

		$prevent_access = apply_filters( 'woocommerce_prevent_admin_access', $prevent_access );

		if ( $prevent_access ) {
			wp_safe_redirect( get_permalink( wc_get_page_id( 'myaccount' ) ) );
			exit;
		}
	}

	/**
	 * Preview email template
	 *
	 * @return string
	 */
	public function preview_emails() {

		if ( isset( $_GET['preview_woocommerce_mail'] ) ) {
			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'preview-mail') ) {
				die( 'Security check' );
			}

			// load the mailer class
			$mailer        = WC()->mailer();

			// get the preview email subject
			$email_heading = __( 'HTML Email Template', 'woocommerce' );

			// get the preview email content
			ob_start();
			include( 'views/html-email-template-preview.php' );
			$message       = ob_get_clean();

			// create a new email
			$email         = new WC_Email();

			// wrap the content with the email template and then add styles
			$message       = $email->style_inline( $mailer->wrap_message( $email_heading, $message ) );

			// print the preview email
			echo $message;
			exit;
		}
	}
}

return new WC_Admin();
