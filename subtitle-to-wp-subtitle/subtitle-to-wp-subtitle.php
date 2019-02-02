<?php
/*
Plugin Name:  Subtitle to WP Subtitle - Database Migration
Plugin URI:   https://github.com/ibes/subtitles-to-wp-subtitle
Description:  Helps to move from the plugin "subtitles" to "wp subtitle"
Version:      0.1.0
Author:       Sebastian Gärtner
Author URI:   https://github.com/ibes
License:      GPL2
Text Domain:  s2ws
*/


/**
 * Activation
 */

register_activation_hook( __FILE__, 's2ws_activation_hook' );
function s2ws_activation_hook() {
	set_transient( 's2ws_show_activation_admin_notice', true, 5 );
}

add_action( 'admin_notices', 's2ws_add_activation_admin_notice' );
function s2ws_add_activation_admin_notice() {

	if ( get_transient( 's2ws_show_activation_admin_notice' ) ) {
		?>
		<div class="updated notice is-dismissible">
			<p>You are half way to transform your database from plugin "Subtitles" to plugin "WP Subtitle".</p>
			<p><a href="<?php echo esc_url( get_admin_url( null, 'tools.php?page=subtitles-to-wp-subtitle' ) ); ?>"> <?php echo esc_html__( 'Transform Database Now', 's2ws' ); ?></a></p>
		</div>
		<?php
		delete_transient( 's2ws_show_activation_admin_notice' );
	}
}



/**
 * Deactivation
 */

register_deactivation_hook( __FILE__, 's2ws_deactivation_hook' );
function s2ws_deactivation_hook() {
	delete_option( 's2ws_success' );
}



/**
 * Plugin Link
 */

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 's2ws_plugin_action_links' );
function s2ws_plugin_action_links( $links ) {
	$links[] = '<a href="' . esc_url( get_admin_url( null, 'tools.php?page=subtitles-to-wp-subtitle' ) ) . '">' . __( 'Transform Database', 's2ws' ) . '</a>';
	return $links;
}



/**
 * Setting Page
 */

add_action( 'admin_menu', 's2ws_create_settings_page' );
function s2ws_create_settings_page() {
	$page_title = 'Subtitles to WP Subtitle';
	$menu_title = 'Subtitles to WP Subtitle';
	$capability = 'manage_options';
	$slug       = 'subtitles-to-wp-subtitle';
	$callback   = 's2ws_settings_page_content';
	add_management_page( $page_title, $menu_title, $capability, $slug, $callback );
}

function s2ws_settings_page_content() {
	?>
	<div class="wrap">
		<h1>Subtitles to WP Subtitle</h1>

		<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

			<p><?php echo esc_html__( 'This tool helps to transform the database to be able to switch from plugin "Subtitles" to plugin "WP Subtitle". Please backup your database before using this tool.', 's2ws' ); ?>

			<input type="hidden" name="action" value="s2ws_transform_database">

			<?php
				submit_button( 'Transform Database' );
			?>

		</form>

	</div>
	<?php
}



/**
 * Handle transformation
 * This is actually what this plugin is about
 */

add_action( 'admin_post_s2ws_transform_database', 's2ws_handle_database_transformation' );
function s2ws_handle_database_transformation() {

	global $wpdb;

	$result = $wpdb->query(
		"
		UPDATE $wpdb->postmeta
		SET meta_key = 'wps_subtitle'
		WHERE meta_key = '_subtitle'
		"
	);

	if ( false === $result ) {
		set_transient( 's2ws_db_error', true, 20 );
	} else {
		add_option( 's2ws_success', 'true' );
	}

	wp_safe_redirect( $_SERVER['HTTP_REFERER'] );
	exit();
}



/**
 * Admin Notice as Feedback
 */

add_action( 'admin_notices', 's2ws_admin_notice_db_error' );
function s2ws_admin_notice_db_error() {

	if ( ! get_transient( 's2ws_db_error' ) ) {
		return;
	}

	$class   = 'notice notice-error';
	$message = __( 'There had been an error. Please try again.', 's2sw' );
	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );

	delete_transient( 's2ws_db_error' );
}



add_action( 'admin_notices', 's2ws_admin_notice_success' );
function s2ws_admin_notice_success() {

	if ( true != get_option( 's2ws_success' ) ) {
		return;
	}

	$class   = 'notice notice-success';
	$message = __( 'You successfully changed your database to now use the plugin WP Subtitle for your subtitles. Please deactivate plugin "Subtitles" and activate "WP Subtitle". This helper plugin should be deleted now. Likely template modifications are needed.', 's2sw' );
	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
}
