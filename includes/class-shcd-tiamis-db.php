<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tiamis_Chat_DB {
	private static $tables = array( 'conversations', 'messages', 'telegram_map', 'bale_map', 'reports', 'notes', 'blocks', 'ratings', 'tasks', 'tags', 'conversation_tags', 'attachments', 'reactions', 'knowledge', 'audit' );

	public static function table_keys() {
		return self::$tables;
	}

	public static function table( $name ) {
		global $wpdb;
		$name = sanitize_key( (string) $name );
		if ( ! in_array( $name, self::$tables, true ) ) {
			return '';
		}
		return $wpdb->prefix . TIAMIS_CHAT_TABLE_BASE . $name;
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset       = $wpdb->get_charset_collate();
		$conversations = self::table( 'conversations' );
		$messages      = self::table( 'messages' );
		$telegram_map  = self::table( 'telegram_map' );
		$bale_map      = self::table( 'bale_map' );
		$reports       = self::table( 'reports' );
		$notes         = self::table( 'notes' );
		$blocks        = self::table( 'blocks' );
		$ratings       = self::table( 'ratings' );
		$tasks         = self::table( 'tasks' );
		$tags          = self::table( 'tags' );
		$conversation_tags = self::table( 'conversation_tags' );
		$attachments   = self::table( 'attachments' );
		$reactions     = self::table( 'reactions' );
		$knowledge     = self::table( 'knowledge' );
		$audit         = self::table( 'audit' );

		$sql_conversations = "CREATE TABLE {$conversations} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id varchar(36) NOT NULL,
			session_hash char(64) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'open',
			visitor_name varchar(191) NOT NULL DEFAULT '',
			visitor_email varchar(191) NOT NULL DEFAULT '',
			visitor_phone varchar(64) NOT NULL DEFAULT '',
			visitor_telegram_chat_id varchar(64) NOT NULL DEFAULT '',
			visitor_telegram_username varchar(191) NOT NULL DEFAULT '',
			visitor_bale_chat_id varchar(64) NOT NULL DEFAULT '',
			visitor_bale_username varchar(191) NOT NULL DEFAULT '',
			ip_value varchar(191) NOT NULL DEFAULT '',
			ip_mode varchar(12) NOT NULL DEFAULT 'hash',
			user_agent text NULL,
			page_url text NULL,
			referrer text NULL,
			utm_source varchar(191) NOT NULL DEFAULT '',
			utm_medium varchar(191) NOT NULL DEFAULT '',
			utm_campaign varchar(191) NOT NULL DEFAULT '',
			assigned_to bigint(20) unsigned NOT NULL DEFAULT 0,
			assigned_agent_index int(10) unsigned NOT NULL DEFAULT 0,
			department varchar(80) NOT NULL DEFAULT '',
			priority varchar(20) NOT NULL DEFAULT 'normal',
			sentiment varchar(20) NOT NULL DEFAULT 'neutral',
			first_response_at datetime NULL,
			resolved_at datetime NULL,
			sla_due_at datetime NULL,
			lock_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			lock_expires_at datetime NULL,
			resume_code varchar(24) NOT NULL DEFAULT '',
			qa_score decimal(5,2) NULL,
			qa_summary text NULL,
			consent tinyint(1) unsigned NOT NULL DEFAULT 0,
			unread_admin int(10) unsigned NOT NULL DEFAULT 0,
			unread_visitor int(10) unsigned NOT NULL DEFAULT 0,
			last_message_at datetime NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY session_hash (session_hash),
			KEY status_last (status,last_message_at),
			KEY assigned_to (assigned_to),
			KEY assigned_agent_index (assigned_agent_index),
			KEY department_status (department,status),
			KEY priority_status (priority,status),
			KEY sla_due_at (sla_due_at),
			KEY resume_code (resume_code),
			KEY visitor_telegram_chat_id (visitor_telegram_chat_id),
			KEY visitor_bale_chat_id (visitor_bale_chat_id)
		) {$charset};";

		$sql_messages = "CREATE TABLE {$messages} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) unsigned NOT NULL,
			sender varchar(20) NOT NULL,
			sender_name varchar(191) NOT NULL DEFAULT '',
			body longtext NOT NULL,
			source varchar(30) NOT NULL DEFAULT 'website',
			telegram_chat_id varchar(64) NOT NULL DEFAULT '',
			telegram_message_id bigint(20) unsigned NOT NULL DEFAULT 0,
			meta longtext NULL,
			client_message_id varchar(64) NOT NULL DEFAULT '',
			reply_to_id bigint(20) unsigned NOT NULL DEFAULT 0,
			attachment_id bigint(20) unsigned NOT NULL DEFAULT 0,
			edited_at datetime NULL,
			deleted_at datetime NULL,
			seen_at datetime NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY conversation_created (conversation_id,created_at),
			KEY conversation_id_id (conversation_id,id),
			KEY sender (sender),
			UNIQUE KEY conversation_client (conversation_id,client_message_id),
			KEY reply_to_id (reply_to_id),
			KEY attachment_id (attachment_id)
		) {$charset};";

		$sql_telegram = "CREATE TABLE {$telegram_map} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) unsigned NOT NULL,
			telegram_chat_id varchar(64) NOT NULL,
			telegram_message_id bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY chat_message (telegram_chat_id,telegram_message_id),
			KEY conversation_id (conversation_id)
		) {$charset};";


		$sql_bale = "CREATE TABLE {$bale_map} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) unsigned NOT NULL,
			bale_chat_id varchar(64) NOT NULL,
			bale_message_id bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY chat_message (bale_chat_id,bale_message_id),
			KEY conversation_id (conversation_id)
		) {$charset};";

		$sql_reports = "CREATE TABLE {$reports} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) unsigned NOT NULL,
			message_id bigint(20) unsigned NOT NULL,
			reason varchar(60) NOT NULL DEFAULT 'inappropriate',
			details text NULL,
			status varchar(20) NOT NULL DEFAULT 'new',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY conversation_message (conversation_id,message_id),
			KEY status_created (status,created_at)
		) {$charset};";

		$sql_notes = "CREATE TABLE {$notes} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) unsigned NOT NULL,
			wp_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			note longtext NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY conversation_user (conversation_id,wp_user_id),
			KEY updated_at (updated_at)
		) {$charset};";

		$sql_blocks = "CREATE TABLE {$blocks} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			ip_value varchar(191) NOT NULL,
			ip_mode varchar(12) NOT NULL DEFAULT 'hash',
			reason varchar(255) NOT NULL DEFAULT '',
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			expires_at datetime NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY ip_value_mode (ip_value,ip_mode),
			KEY expires_at (expires_at)
		) {$charset};";

		$sql_ratings = "CREATE TABLE {$ratings} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) unsigned NOT NULL,
			rating tinyint(2) unsigned NOT NULL DEFAULT 0,
			label varchar(40) NOT NULL DEFAULT '',
			comment text NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY conversation_id (conversation_id),
			KEY rating_created (rating,created_at)
		) {$charset};";

		$sql_tasks = "CREATE TABLE {$tasks} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) unsigned NOT NULL,
			title varchar(255) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'open',
			assigned_to bigint(20) unsigned NOT NULL DEFAULT 0,
			due_at datetime NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY conversation_status (conversation_id,status),
			KEY due_at (due_at)
		) {$charset};";

		$sql_tags = "CREATE TABLE {$tags} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(80) NOT NULL,
			slug varchar(100) NOT NULL,
			color varchar(20) NOT NULL DEFAULT '#38008a',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug)
		) {$charset};";

		$sql_conversation_tags = "CREATE TABLE {$conversation_tags} (
			conversation_id bigint(20) unsigned NOT NULL,
			tag_id bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (conversation_id,tag_id),
			KEY tag_id (tag_id)
		) {$charset};";

		$sql_attachments = "CREATE TABLE {$attachments} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) unsigned NOT NULL,
			message_id bigint(20) unsigned NOT NULL DEFAULT 0,
			wp_attachment_id bigint(20) unsigned NOT NULL DEFAULT 0,
			file_name varchar(255) NOT NULL DEFAULT '',
			mime_type varchar(100) NOT NULL DEFAULT '',
			file_size bigint(20) unsigned NOT NULL DEFAULT 0,
			file_url text NULL,
			created_by varchar(20) NOT NULL DEFAULT 'visitor',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY conversation_id (conversation_id),
			KEY message_id (message_id)
		) {$charset};";

		$sql_reactions = "CREATE TABLE {$reactions} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) unsigned NOT NULL,
			message_id bigint(20) unsigned NOT NULL,
			actor varchar(20) NOT NULL,
			actor_id varchar(64) NOT NULL DEFAULT '',
			reaction varchar(20) NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY message_actor (message_id,actor,actor_id),
			KEY conversation_id (conversation_id)
		) {$charset};";

		$sql_knowledge = "CREATE TABLE {$knowledge} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			source_type varchar(30) NOT NULL DEFAULT 'manual',
			source_id bigint(20) unsigned NOT NULL DEFAULT 0,
			department_id bigint(20) unsigned NOT NULL DEFAULT 0,
			wp_attachment_id bigint(20) unsigned NOT NULL DEFAULT 0,
			file_name varchar(255) NOT NULL DEFAULT '',
			mime_type varchar(100) NOT NULL DEFAULT '',
			file_hash char(64) NOT NULL DEFAULT '',
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			title varchar(255) NOT NULL DEFAULT '',
			content longtext NOT NULL,
			keywords text NULL,
			language varchar(10) NOT NULL DEFAULT 'fa',
			status varchar(20) NOT NULL DEFAULT 'active',
			updated_at datetime NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY source (source_type,source_id),
			KEY department_status (department_id,status),
			KEY file_hash (file_hash),
			KEY status_language (status,language),
			FULLTEXT KEY search_text (title,content,keywords)
		) {$charset};";

		$sql_audit = "CREATE TABLE {$audit} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) unsigned NOT NULL DEFAULT 0,
			wp_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			event_type varchar(60) NOT NULL,
			details longtext NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY conversation_created (conversation_id,created_at),
			KEY event_type (event_type)
		) {$charset};";
		dbDelta( $sql_conversations );
		dbDelta( $sql_messages );
		dbDelta( $sql_telegram );
		dbDelta( $sql_bale );
		dbDelta( $sql_reports );
		dbDelta( $sql_notes );
		dbDelta( $sql_blocks );
		dbDelta( $sql_ratings );
		dbDelta( $sql_tasks );
		dbDelta( $sql_tags );
		dbDelta( $sql_conversation_tags );
		dbDelta( $sql_attachments );
		dbDelta( $sql_reactions );
		dbDelta( $sql_knowledge );
		dbDelta( $sql_audit );
		update_option( TIAMIS_CHAT_DB_OPTION, TIAMIS_CHAT_DB_VERSION, false );
	}

	public static function table_stats() {
		global $wpdb;
		$stats = array();
		foreach ( self::$tables as $key ) {
			$table = self::table( $key );
			$row   = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS LIKE %s', $wpdb->esc_like( $table ) ), ARRAY_A );
			$stats[] = array(
				'key'            => $key,
				'name'           => $table,
				'rows'           => $row ? absint( $row['Rows'] ) : 0,
				'auto_increment' => $row && isset( $row['Auto_increment'] ) ? absint( $row['Auto_increment'] ) : 1,
				'size'           => $row ? absint( $row['Data_length'] ) + absint( $row['Index_length'] ) : 0,
				'engine'         => $row && isset( $row['Engine'] ) ? sanitize_text_field( $row['Engine'] ) : '',
				'exists'         => (bool) $row,
			);
		}
		return $stats;
	}

	public static function optimize_tables() {
		global $wpdb;
		$errors = array();
		foreach ( self::$tables as $key ) {
			$table = self::table( $key );
			if ( false === $wpdb->query( $wpdb->prepare( 'OPTIMIZE TABLE %i', $table ) ) ) {
				$errors[] = $table;
			}
		}
		return $errors ? new WP_Error( 'shcd_tiamis_db_optimize', implode( ', ', $errors ) ) : true;
	}

	/**
	 * Repairs counters without deleting data. MySQL starts an empty table at 1;
	 * populated tables safely continue at MAX(id) + 1.
	 */
	public static function repair_auto_increment() {
		global $wpdb;
		$errors = array();
		foreach ( self::$tables as $key ) {
			$table = self::table( $key );
			if ( false === $wpdb->query( $wpdb->prepare( 'ALTER TABLE %i AUTO_INCREMENT = 1', $table ) ) ) {
				$errors[] = $table;
			}
		}
		return $errors ? new WP_Error( 'shcd_tiamis_db_counter', implode( ', ', $errors ) ) : true;
	}

	/**
	 * Deletes all plugin-owned operational data and resets every counter to 1.
	 * Settings remain untouched.
	 */
	public static function purge_all_data() {
		global $wpdb;
		$order  = array( 'reactions', 'attachments', 'tasks', 'conversation_tags', 'audit', 'reports', 'notes', 'ratings', 'messages', 'telegram_map', 'bale_map', 'blocks', 'tags', 'knowledge', 'conversations' );
		$errors = array();
		$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		foreach ( $order as $key ) {
			$table = self::table( $key );
			if ( false === $wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $table ) ) ) {
				$errors[] = $table;
				continue;
			}
			$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i AUTO_INCREMENT = 1', $table ) );
		}
		$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $errors ? new WP_Error( 'shcd_tiamis_db_purge', implode( ', ', $errors ) ) : true;
	}
}
