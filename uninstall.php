<?php
/**
 * Remove all data created by Tiamis – AI Live Chat.
 *
 * @package SHCD_Tiamis
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$shcd_tiamis_tables = array(
	$wpdb->prefix . 'shcd_tiamis_reactions',
	$wpdb->prefix . 'shcd_tiamis_attachments',
	$wpdb->prefix . 'shcd_tiamis_tasks',
	$wpdb->prefix . 'shcd_tiamis_conversation_tags',
	$wpdb->prefix . 'shcd_tiamis_tickets',
	$wpdb->prefix . 'shcd_tiamis_departments',
	$wpdb->prefix . 'shcd_tiamis_audit',
	$wpdb->prefix . 'shcd_tiamis_reports',
	$wpdb->prefix . 'shcd_tiamis_notes',
	$wpdb->prefix . 'shcd_tiamis_ratings',
	$wpdb->prefix . 'shcd_tiamis_messages',
	$wpdb->prefix . 'shcd_tiamis_events',
	$wpdb->prefix . 'shcd_tiamis_telegram_map',
	$wpdb->prefix . 'shcd_tiamis_bale_map',
	$wpdb->prefix . 'shcd_tiamis_blocks',
	$wpdb->prefix . 'shcd_tiamis_tags',
	$wpdb->prefix . 'shcd_tiamis_knowledge',
	$wpdb->prefix . 'shcd_tiamis_conversations',
);

foreach ( $shcd_tiamis_tables as $shcd_tiamis_table ) {
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $shcd_tiamis_table ) );
}

delete_option( 'shcd_tiamis_settings' );
delete_option( 'shcd_tiamis_db_version' );
delete_option( 'shcd_tiamis_roles_version' );
delete_option( 'shcd_tiamis_ticket_agents' );
delete_option( 'shcd_tiamis_department_insert_lock' );
wp_clear_scheduled_hook( 'shcd_tiamis_daily_cleanup' );
wp_clear_scheduled_hook( 'shcd_tiamis_telegram_poll' );
wp_clear_scheduled_hook( 'shcd_tiamis_telegram_prepare_polling' );
wp_clear_scheduled_hook( 'shcd_tiamis_process_outbound' );
delete_option( 'shcd_tiamis_telegram_offset' );
delete_option( 'shcd_tiamis_telegram_last_poll' );
wp_clear_scheduled_hook( 'shcd_tiamis_qa_conversation' );

foreach ( array( 'tiamis_operator', 'tiamis_supervisor', 'tiamis_ticket_agent' ) as $shcd_tiamis_role_name ) {
	remove_role( $shcd_tiamis_role_name );
}
$shcd_tiamis_administrator = get_role( 'administrator' );
if ( $shcd_tiamis_administrator ) {
	foreach ( array( 'tiamis_manage_inbox', 'tiamis_reply', 'tiamis_view_reports', 'tiamis_manage_settings', 'tiamis_manage_tickets' ) as $shcd_tiamis_capability ) {
		$shcd_tiamis_administrator->remove_cap( $shcd_tiamis_capability );
	}
}

// Remove only the plugin-managed font directory. User-provided source files outside
// this directory are never touched.
$shcd_tiamis_uploads = wp_upload_dir();
if ( empty( $shcd_tiamis_uploads['error'] ) ) {
	$shcd_tiamis_font_dir = trailingslashit( $shcd_tiamis_uploads['basedir'] ) . 'tiamis-fonts';
	if ( is_dir( $shcd_tiamis_font_dir ) ) {
		$shcd_tiamis_font_files = glob( trailingslashit( $shcd_tiamis_font_dir ) . '*' );
		foreach ( is_array( $shcd_tiamis_font_files ) ? $shcd_tiamis_font_files : array() as $shcd_tiamis_font_file ) {
			if ( is_file( $shcd_tiamis_font_file ) ) {
				wp_delete_file( $shcd_tiamis_font_file );
			}
		}
	}
}

$shcd_tiamis_transient_prefixes = array(
	'_transient_shcd_tiamis_',
	'_transient_timeout_shcd_tiamis_',
	'_site_transient_shcd_tiamis_',
	'_site_transient_timeout_shcd_tiamis_',
);

foreach ( $shcd_tiamis_transient_prefixes as $shcd_tiamis_prefix ) {
	$wpdb->query(
		$wpdb->prepare(
			'DELETE FROM %i WHERE option_name LIKE %s',
			$wpdb->options,
			$wpdb->esc_like( $shcd_tiamis_prefix ) . '%'
		)
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required during uninstall cleanup.
}

if ( is_multisite() ) {
	$shcd_tiamis_site_transient_prefixes = array(
		'_site_transient_shcd_tiamis_',
		'_site_transient_timeout_shcd_tiamis_',
	);

	foreach ( $shcd_tiamis_site_transient_prefixes as $shcd_tiamis_prefix ) {
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE meta_key LIKE %s',
				$wpdb->sitemeta,
				$wpdb->esc_like( $shcd_tiamis_prefix ) . '%'
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required during uninstall cleanup.
	}
}

// Remove ticket-specific direct capabilities and department assignments.
$shcd_tiamis_users = get_users( array( 'fields' => 'all' ) );
foreach ( $shcd_tiamis_users as $shcd_tiamis_user ) {
	$shcd_tiamis_user->remove_cap( 'tiamis_manage_tickets' );
	delete_user_meta( $shcd_tiamis_user->ID, 'tiamis_ticket_departments' );
}
