<?php
/**
 * Plugin Name: Tiamis – AI Live Chat
 * Plugin URI: https://shabnam.dev
 * Description: Multilingual AI live chat for Telegram and Bale with operator inbox, Web Push and smart replies.
 * Version: 1.0.0
 * Requires at least: 7.0
 * Requires PHP: 7.4
 * Author: SHABNAM.DEV
 * Author URI: https://shcd.ir
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tiamis-ai-live-chat
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TIAMIS_CHAT_VERSION', '1.0.0' );
define( 'TIAMIS_CHAT_FILE', __FILE__ );
define( 'TIAMIS_CHAT_DIR', plugin_dir_path( __FILE__ ) );
define( 'TIAMIS_CHAT_URL', plugin_dir_url( __FILE__ ) );
define( 'TIAMIS_CHAT_OPTION', 'shcd_tiamis_settings' );
define( 'TIAMIS_CHAT_DB_VERSION', '1.3.0' );
define( 'TIAMIS_CHAT_DB_OPTION', 'shcd_tiamis_db_version' );
define( 'TIAMIS_CHAT_TABLE_BASE', 'shcd_tiamis_' );

require_once TIAMIS_CHAT_DIR . 'includes/class-shcd-tiamis-i18n.php';
require_once TIAMIS_CHAT_DIR . 'includes/class-shcd-tiamis-db.php';
require_once TIAMIS_CHAT_DIR . 'includes/class-shcd-tiamis-core.php';
require_once TIAMIS_CHAT_DIR . 'includes/class-shcd-tiamis-platform.php';
require_once TIAMIS_CHAT_DIR . 'includes/class-shcd-tiamis-integrations.php';
require_once TIAMIS_CHAT_DIR . 'includes/class-shcd-tiamis-rest.php';
require_once TIAMIS_CHAT_DIR . 'includes/class-shcd-tiamis-admin.php';
require_once TIAMIS_CHAT_DIR . 'includes/class-shcd-tiamis-frontend.php';

function shcd_tiamis_activate() {
	Tiamis_Chat_DB::install();
	Tiamis_Chat_Core::ensure_defaults();
	Tiamis_Chat_Core::schedule_cleanup();
	Tiamis_Chat_Integrations::sync_telegram_polling_schedule();
	Tiamis_Chat_Platform::activate();
}
register_activation_hook( __FILE__, 'shcd_tiamis_activate' );

function shcd_tiamis_deactivate() {
	wp_clear_scheduled_hook( 'shcd_tiamis_daily_cleanup' );
	wp_clear_scheduled_hook( 'shcd_tiamis_telegram_poll' );
	wp_clear_scheduled_hook( 'shcd_tiamis_telegram_prepare_polling' );
	Tiamis_Chat_Platform::deactivate();
}
register_deactivation_hook( __FILE__, 'shcd_tiamis_deactivate' );
add_filter( 'cron_schedules', array( 'Tiamis_Chat_Integrations', 'cron_schedules' ) );
add_action( 'shcd_tiamis_telegram_poll', array( 'Tiamis_Chat_Integrations', 'telegram_poll_updates' ) );
add_action( 'shcd_tiamis_telegram_prepare_polling', array( 'Tiamis_Chat_Integrations', 'prepare_telegram_polling' ) );
add_action( 'shcd_tiamis_process_outbound', array( 'Tiamis_Chat_Integrations', 'process_outbound_message' ), 10, 2 );

function shcd_tiamis_boot() {
	Tiamis_Chat_Core::ensure_defaults();

	if ( get_option( TIAMIS_CHAT_DB_OPTION ) !== TIAMIS_CHAT_DB_VERSION ) {
		Tiamis_Chat_DB::install();
	}

	Tiamis_Chat_Platform::ensure_roles();
	Tiamis_Chat_Platform::init();
	Tiamis_Chat_REST::init();
	Tiamis_Chat_Admin::init();
	Tiamis_Chat_Frontend::init();
	Tiamis_Chat_Core::schedule_cleanup();
	Tiamis_Chat_Integrations::sync_telegram_polling_schedule();

	add_filter( 'wp_privacy_personal_data_exporters', array( 'Tiamis_Chat_Core', 'register_privacy_exporter' ) );
	add_filter( 'wp_privacy_personal_data_erasers', array( 'Tiamis_Chat_Core', 'register_privacy_eraser' ) );
	add_action( 'admin_init', array( 'Tiamis_Chat_Core', 'privacy_policy_content' ) );
}
add_action( 'plugins_loaded', 'shcd_tiamis_boot' );

add_action( 'shcd_tiamis_daily_cleanup', array( 'Tiamis_Chat_Core', 'cleanup_old_data' ) );
