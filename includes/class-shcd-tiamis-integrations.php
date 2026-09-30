<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tiamis_Chat_Integrations {
	private static function telegram_method( $method ) {
		$allowed = array( 'sendMessage', 'sendChatAction', 'setWebhook', 'getWebhookInfo', 'deleteWebhook', 'getUpdates', 'getMe' );
		return in_array( $method, $allowed, true ) ? $method : 'sendMessage';
	}

	public static function telegram_token() {
		$settings = Tiamis_Chat_Core::settings();
		return Tiamis_Chat_Core::decrypt_secret( $settings['telegram_bot_token'] );
	}

	public static function telegram_chat_ids() {
		$settings = Tiamis_Chat_Core::settings();
		$raw = preg_split( '/[\\s,;]+/', (string) $settings['telegram_admin_chat_ids'] );
		$ids = array();
		foreach ( $raw as $id ) {
			$id = trim( $id );
			if ( preg_match( '/^-?\\d+$/', $id ) ) {
				$ids[] = $id;
			}
		}
		return array_values( array_unique( $ids ) );
	}

	private static function telegram_api_base() {
		$settings = Tiamis_Chat_Core::settings();
		$base     = isset( $settings['telegram_api_base'] ) ? trim( (string) $settings['telegram_api_base'] ) : 'https://api.telegram.org';
		$base     = untrailingslashit( esc_url_raw( $base ) );
		$parts    = wp_parse_url( $base );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) || 'https' !== strtolower( isset( $parts['scheme'] ) ? $parts['scheme'] : '' ) ) {
			return 'https://api.telegram.org';
		}
		return $base;
	}

	public static function telegram_api( $method, $payload = array() ) {
		$token = self::telegram_token();
		$token = preg_replace( '/[^0-9A-Za-z:_-]/', '', (string) $token );
		if ( '' === $token ) {
			return new WP_Error( 'tiamis_telegram_token_missing', 'توکن ربات تلگرام تنظیم نشده است.' );
		}
		$method   = self::telegram_method( $method );
		$endpoint = self::telegram_api_base() . '/bot' . $token . '/' . $method;
		$args     = array(
			'timeout'     => 'getUpdates' === $method ? 20 : 10,
			'redirection' => 1,
			'httpversion' => '1.1',
			'headers'     => array(
				'Content-Type' => 'application/json',
				'Connection'   => 'close',
				'User-Agent'   => 'Tiamis/' . TIAMIS_CHAT_VERSION . '; ' . home_url( '/' ),
			),
			'body'        => wp_json_encode( $payload ),
			'data_format' => 'body',
		);
		$response = wp_safe_remote_post( $endpoint, $args );
		if ( is_wp_error( $response ) ) {
			// Some restricted hosts reset the first TLS connection. A short second
			// attempt over HTTP/1.1 often succeeds, while the queued dispatcher keeps
			// this delay away from the visitor-facing chat request.
			usleep( 120000 );
			$response = wp_safe_remote_post( $endpoint, $args );
		}
		if ( is_wp_error( $response ) ) {
			Tiamis_Chat_Core::log( 'telegram_transport_error', array( 'method' => $method, 'error' => $response->get_error_message() ) );
			$message = $response->get_error_message();
			if ( false !== stripos( $message, 'cURL error 35' ) || false !== stripos( $message, 'Connection was reset' ) ) {
				return new WP_Error( 'tiamis_telegram_tls_reset', 'اتصال TLS هاست به تلگرام بازنشانی شد. در تنظیمات ربات، حالت دریافت دوره‌ای و در صورت نیاز نشانی Relay را فعال کنید.' );
			}
			return $response;
		}
		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 || ! is_array( $body ) || empty( $body['ok'] ) ) {
			$description = is_array( $body ) && isset( $body['description'] ) ? $body['description'] : 'پاسخ نامعتبر از تلگرام.';
			Tiamis_Chat_Core::log( 'telegram_api_error', array( 'method' => $method, 'status' => $status, 'error' => $description ) );
			return new WP_Error( 'tiamis_telegram_api_error', sanitize_text_field( $description ) );
		}
		return isset( $body['result'] ) ? $body['result'] : true;
	}

	public static function set_telegram_webhook() {
		$settings      = Tiamis_Chat_Core::settings();
		$stored_secret = (string) $settings['telegram_webhook_secret'];
		$secret        = preg_replace( '/[^A-Za-z0-9_-]/', '', $stored_secret );
		if ( strlen( $secret ) < 12 ) {
			$secret = wp_generate_password( 48, false, false );
		}
		if ( $secret !== $stored_secret ) {
			$settings['telegram_webhook_secret'] = $secret;
			Tiamis_Chat_Core::update_settings( $settings );
		}
		wp_clear_scheduled_hook( 'shcd_tiamis_telegram_poll' );
		return self::telegram_api(
			'setWebhook',
			array(
				'url'                  => rest_url( 'shcd-tiamis/v1/telegram/webhook' ),
				'secret_token'         => $secret,
				'allowed_updates'      => array( 'message' ),
				'drop_pending_updates' => false,
			)
		);
	}

	public static function delete_telegram_webhook() {
		return self::telegram_api( 'deleteWebhook', array( 'drop_pending_updates' => false ) );
	}

	public static function prepare_telegram_polling() {
		$settings = Tiamis_Chat_Core::settings();
		if ( '1' !== $settings['telegram_enabled'] || 'polling' !== $settings['telegram_update_mode'] ) {
			return;
		}
		self::delete_telegram_webhook();
		self::telegram_poll_updates();
	}

	public static function cron_schedules( $schedules ) {
		$schedules['tiamis_30_seconds']  = array( 'interval' => 30, 'display' => 'Tiamis: every 30 seconds' );
		$schedules['tiamis_60_seconds']  = array( 'interval' => 60, 'display' => 'Tiamis: every minute' );
		$schedules['tiamis_120_seconds'] = array( 'interval' => 120, 'display' => 'Tiamis: every two minutes' );
		return $schedules;
	}

	private static function telegram_poll_recurrence( $seconds ) {
		$seconds = absint( $seconds );
		if ( $seconds >= 120 ) {
			return 'tiamis_120_seconds';
		}
		if ( $seconds >= 60 ) {
			return 'tiamis_60_seconds';
		}
		return 'tiamis_30_seconds';
	}

	public static function sync_telegram_polling_schedule( $force = false ) {
		$settings = Tiamis_Chat_Core::settings();
		$enabled  = '1' === $settings['telegram_enabled'] && 'polling' === $settings['telegram_update_mode'];
		if ( ! $enabled ) {
			wp_clear_scheduled_hook( 'shcd_tiamis_telegram_poll' );
			return;
		}
		$recurrence = self::telegram_poll_recurrence( isset( $settings['telegram_poll_interval'] ) ? $settings['telegram_poll_interval'] : 30 );
		if ( $force ) {
			wp_clear_scheduled_hook( 'shcd_tiamis_telegram_poll' );
		}
		if ( ! wp_next_scheduled( 'shcd_tiamis_telegram_poll' ) ) {
			wp_schedule_event( time() + 5, $recurrence, 'shcd_tiamis_telegram_poll' );
		}
	}

	public static function telegram_poll_updates() {
		$settings = Tiamis_Chat_Core::settings();
		if ( '1' !== $settings['telegram_enabled'] || 'polling' !== $settings['telegram_update_mode'] ) {
			return array( 'processed' => 0, 'disabled' => true );
		}
		if ( get_transient( 'shcd_tiamis_tg_poll_lock' ) ) {
			return array( 'processed' => 0, 'locked' => true );
		}
		set_transient( 'shcd_tiamis_tg_poll_lock', 1, 25 );
		$offset  = absint( get_option( 'shcd_tiamis_telegram_offset', 0 ) );
		$payload = array(
			'offset'          => $offset ? $offset + 1 : 0,
			'limit'           => 100,
			'timeout'         => 0,
			'allowed_updates' => array( 'message' ),
		);
		$updates = self::telegram_api( 'getUpdates', $payload );
		if ( is_wp_error( $updates ) && false !== stripos( $updates->get_error_message(), 'webhook' ) ) {
			// Telegram does not allow getUpdates while a webhook is active. Remove an
			// older webhook once and retry, which also covers upgrades from prior versions.
			self::delete_telegram_webhook();
			$updates = self::telegram_api( 'getUpdates', $payload );
		}
		if ( is_wp_error( $updates ) ) {
			delete_transient( 'shcd_tiamis_tg_poll_lock' );
			return $updates;
		}
		$processed = 0;
		foreach ( is_array( $updates ) ? $updates : array() as $update ) {
			$update_id = isset( $update['update_id'] ) ? absint( $update['update_id'] ) : 0;
			if ( $update_id ) {
				$offset = max( $offset, $update_id );
			}
			$key = $update_id ? 'shcd_tiamis_tg_update_' . $update_id : '';
			if ( $key && get_transient( $key ) ) {
				continue;
			}
			$result = self::handle_telegram_update( $update );
			if ( ! is_wp_error( $result ) ) {
				$processed++;
				if ( $key ) {
					set_transient( $key, 1, WEEK_IN_SECONDS );
				}
			}
		}
		if ( $offset ) {
			update_option( 'shcd_tiamis_telegram_offset', $offset, false );
		}
		update_option( 'shcd_tiamis_telegram_last_poll', current_time( 'mysql' ), false );
		delete_transient( 'shcd_tiamis_tg_poll_lock' );
		return array( 'processed' => $processed, 'offset' => $offset );
	}

	private static function bale_method( $method ) {
		$allowed = array( 'sendMessage', 'sendChatAction', 'setWebhook', 'getWebhookInfo', 'deleteWebhook' );
		return in_array( $method, $allowed, true ) ? $method : 'sendMessage';
	}

	public static function bale_token() {
		$settings = Tiamis_Chat_Core::settings();
		return Tiamis_Chat_Core::decrypt_secret( $settings['bale_bot_token'] );
	}

	public static function bale_chat_ids() {
		$settings = Tiamis_Chat_Core::settings();
		$raw      = preg_split( '/[\s,;]+/', (string) $settings['bale_admin_chat_ids'] );
		$ids      = array();
		foreach ( $raw as $id ) {
			$id = trim( $id );
			if ( preg_match( '/^-?\d+$/', $id ) ) {
				$ids[] = $id;
			}
		}
		return array_values( array_unique( $ids ) );
	}

	public static function bale_api( $method, $payload = array() ) {
		$token = preg_replace( '/[^0-9A-Za-z:_-]/', '', (string) self::bale_token() );
		if ( '' === $token ) {
			return new WP_Error( 'tiamis_bale_token_missing', 'توکن ربات بله تنظیم نشده است.' );
		}
		$response = wp_remote_post(
			'https://tapi.bale.ai/bot' . $token . '/' . self::bale_method( $method ),
			array(
				'timeout' => 15,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $payload ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 || ! is_array( $body ) || empty( $body['ok'] ) ) {
			$description = is_array( $body ) && isset( $body['description'] ) ? $body['description'] : 'پاسخ معتبری از بله دریافت نشد.';
			return new WP_Error( 'tiamis_bale_api_error', sanitize_text_field( $description ) );
		}
		return isset( $body['result'] ) ? $body['result'] : true;
	}

	public static function set_bale_webhook() {
		$settings      = Tiamis_Chat_Core::settings();
		$stored_secret = (string) $settings['bale_webhook_secret'];
		$secret        = preg_replace( '/[^A-Za-z0-9_-]/', '', $stored_secret );
		if ( strlen( $secret ) < 20 ) {
			$secret = wp_generate_password( 48, false, false );
		}
		if ( $secret !== $stored_secret ) {
			$settings['bale_webhook_secret'] = $secret;
			Tiamis_Chat_Core::update_settings( $settings );
		}
		$url = add_query_arg( 'key', rawurlencode( $secret ), rest_url( 'shcd-tiamis/v1/bale/webhook' ) );
		return self::bale_api( 'setWebhook', array( 'url' => $url ) );
	}



	private static function attachment_suffix( $message, $html = false ) {
		if ( ! $message || empty( $message->attachment_id ) ) {
			return '';
		}
		$attachment = Tiamis_Chat_Core::get_attachment( (int) $message->attachment_id );
		if ( ! $attachment || empty( $attachment->file_url ) ) {
			return '';
		}
		$label = $attachment->file_name ? $attachment->file_name : 'فایل پیوست';
		if ( $html ) {
			return "\n📎 <a href=\"" . esc_url( $attachment->file_url ) . "\">" . esc_html( $label ) . '</a>';
		}
		return "\n📎 " . sanitize_text_field( $label ) . ': ' . esc_url_raw( $attachment->file_url );
	}

	public static function process_outbound_message( $message_id, $direction = 'visitor' ) {
		$message = Tiamis_Chat_Core::get_message( absint( $message_id ) );
		if ( ! $message ) {
			return;
		}
		$conversation = Tiamis_Chat_Core::get_conversation( (int) $message->conversation_id );
		if ( ! $conversation ) {
			return;
		}
		if ( 'operator' === $direction ) {
			self::deliver_reply_to_visitor_channel( $conversation, $message );
			self::mirror_reply_to_telegram( $conversation, $message );
			self::mirror_reply_to_bale( $conversation, $message );
			return;
		}
		self::send_visitor_message_to_telegram( $conversation, $message );
		self::send_visitor_message_to_bale( $conversation, $message );
		self::maybe_auto_reply( $conversation->id );
	}

	public static function send_visitor_message_to_telegram( $conversation, $message ) {
		global $wpdb;
		$settings = Tiamis_Chat_Core::settings();
		if ( '1' !== $settings['telegram_enabled'] || ! $conversation || ! $message ) {
			return;
		}
		$ids = self::telegram_chat_ids();
		if ( ! $ids ) {
			return;
		}
		$name = $conversation->visitor_name ? $conversation->visitor_name : 'بازدیدکننده ناشناس';
		$ip_label = 'none' === $conversation->ip_mode ? 'ثبت نشده' : $conversation->ip_value;
		$channel_label = empty( $conversation->visitor_telegram_chat_id ) ? 'پیام جدید سایت' : 'پیام جدید کاربر تلگرام';
		$text  = '🟣 <b>' . esc_html( $channel_label ) . "</b>\n";
		$text .= '👤 ' . esc_html( $name ) . "\n";
		if ( $conversation->visitor_email ) {
			$text .= '✉️ ' . esc_html( $conversation->visitor_email ) . "\n";
		}
		if ( $conversation->visitor_phone ) {
			$text .= '📞 ' . esc_html( $conversation->visitor_phone ) . "\n";
		}
		$text .= '🆔 <code>' . esc_html( $conversation->public_id ) . "</code>\n";
		$text .= '🌐 ' . esc_html( wp_html_excerpt( $conversation->page_url, 180, '…' ) ) . "\n";
		$text .= '📍 IP: <code>' . esc_html( wp_html_excerpt( $ip_label, 80, '…' ) ) . "</code>\n\n";
		$text .= '💬 <b>' . esc_html( wp_html_excerpt( $message->body, 2400, '…' ) ) . "</b>" . self::attachment_suffix( $message, true ) . "\n\n";
		$text .= 'برای پاسخ، روی همین پیام Reply بزنید.';

		foreach ( $ids as $chat_id ) {
			$result = self::telegram_api(
				'sendMessage',
				array(
					'chat_id'                  => $chat_id,
					'text'                     => $text,
					'parse_mode'               => 'HTML',
					'link_preview_options'      => array( 'is_disabled' => true ),
				)
			);
			if ( ! is_wp_error( $result ) && isset( $result['message_id'] ) ) {
				$wpdb->replace(
					Tiamis_Chat_DB::table( 'telegram_map' ),
					array(
						'conversation_id'    => (int) $conversation->id,
						'telegram_chat_id'   => (string) $chat_id,
						'telegram_message_id'=> (int) $result['message_id'],
						'created_at'         => Tiamis_Chat_Core::now(),
					)
				);
			}
		}
	}

	public static function mirror_reply_to_telegram( $conversation, $message, $exclude_chat_id = '' ) {
		global $wpdb;
		$settings = Tiamis_Chat_Core::settings();
		if ( '1' !== $settings['telegram_enabled'] || ! $conversation || ! $message ) {
			return;
		}
		foreach ( self::telegram_chat_ids() as $chat_id ) {
			if ( '' !== $exclude_chat_id && (string) $chat_id === (string) $exclude_chat_id ) {
				continue;
			}
			$text = "✅ <b>پاسخ ارسال‌شده به کاربر</b>\n🆔 <code>" . esc_html( $conversation->public_id ) . "</code>\n\n" . esc_html( wp_html_excerpt( $message->body, 3000, '…' ) );
			$result = self::telegram_api( 'sendMessage', array( 'chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML' ) );
			if ( ! is_wp_error( $result ) && isset( $result['message_id'] ) ) {
				$wpdb->replace(
					Tiamis_Chat_DB::table( 'telegram_map' ),
					array(
						'conversation_id'     => (int) $conversation->id,
						'telegram_chat_id'    => (string) $chat_id,
						'telegram_message_id' => (int) $result['message_id'],
						'created_at'          => Tiamis_Chat_Core::now(),
					)
				);
			}
		}
	}

	public static function send_visitor_message_to_bale( $conversation, $message ) {
		global $wpdb;
		$settings = Tiamis_Chat_Core::settings();
		if ( '1' !== $settings['bale_enabled'] || ! $conversation || ! $message ) {
			return;
		}
		$ids = self::bale_chat_ids();
		if ( ! $ids ) {
			return;
		}
		$name          = $conversation->visitor_name ? $conversation->visitor_name : 'بازدیدکننده ناشناس';
		$channel_label = ! empty( $conversation->visitor_bale_chat_id ) ? 'پیام کاربر بله' : ( ! empty( $conversation->visitor_telegram_chat_id ) ? 'پیام کاربر تلگرام' : 'پیام کاربر سایت' );
		$text  = "🟣 {$channel_label}\n";
		$text .= '👤 ' . $name . "\n";
		if ( $conversation->visitor_email ) {
			$text .= '✉️ ' . $conversation->visitor_email . "\n";
		}
		if ( $conversation->visitor_phone ) {
			$text .= '📞 ' . $conversation->visitor_phone . "\n";
		}
		$text .= '🆔 ' . $conversation->public_id . "\n";
		if ( $conversation->page_url ) {
			$text .= '🌐 ' . wp_html_excerpt( $conversation->page_url, 180, '…' ) . "\n";
		}
		$text .= "\n💬 " . wp_html_excerpt( $message->body, 3000, '…' ) . self::attachment_suffix( $message ) . "\n\nبرای پاسخ، روی همین پیام Reply بزنید.";
		foreach ( $ids as $chat_id ) {
			$result = self::bale_api( 'sendMessage', array( 'chat_id' => $chat_id, 'text' => $text ) );
			if ( ! is_wp_error( $result ) && is_array( $result ) && isset( $result['message_id'] ) ) {
				$wpdb->replace(
					Tiamis_Chat_DB::table( 'bale_map' ),
					array(
						'conversation_id' => (int) $conversation->id,
						'bale_chat_id'     => (string) $chat_id,
						'bale_message_id'  => (int) $result['message_id'],
						'created_at'       => Tiamis_Chat_Core::now(),
					)
				);
			}
		}
	}

	public static function mirror_reply_to_bale( $conversation, $message, $exclude_chat_id = '' ) {
		global $wpdb;
		$settings = Tiamis_Chat_Core::settings();
		if ( '1' !== $settings['bale_enabled'] || ! $conversation || ! $message ) {
			return;
		}
		foreach ( self::bale_chat_ids() as $chat_id ) {
			if ( '' !== $exclude_chat_id && (string) $chat_id === (string) $exclude_chat_id ) {
				continue;
			}
			$text   = "✅ پاسخ برای کاربر ارسال شد\n🆔 " . $conversation->public_id . "\n\n" . wp_html_excerpt( $message->body, 3200, '…' );
			$result = self::bale_api( 'sendMessage', array( 'chat_id' => $chat_id, 'text' => $text ) );
			if ( ! is_wp_error( $result ) && is_array( $result ) && isset( $result['message_id'] ) ) {
				$wpdb->replace(
					Tiamis_Chat_DB::table( 'bale_map' ),
					array(
						'conversation_id' => (int) $conversation->id,
						'bale_chat_id'     => (string) $chat_id,
						'bale_message_id'  => (int) $result['message_id'],
						'created_at'       => Tiamis_Chat_Core::now(),
					)
				);
			}
		}
	}

	public static function send_web_push( $conversation, $message ) {
		$settings = Tiamis_Chat_Core::settings();
		if ( '1' !== $settings['onesignal_enabled'] || ! $conversation || ! $message || ! empty( $conversation->visitor_telegram_chat_id ) || ! empty( $conversation->visitor_bale_chat_id ) ) {
			return true;
		}
		$app_id = sanitize_text_field( (string) $settings['onesignal_app_id'] );
		$api_key = Tiamis_Chat_Core::decrypt_secret( $settings['onesignal_rest_api_key'] );
		if ( '' === $app_id || '' === $api_key || empty( $conversation->public_id ) ) {
			return true;
		}
		$title = 'ai' === $message->sender ? 'پاسخ دستیار هوشمند' : 'پاسخ جدید پشتیبانی';
		$body = 'پاسخ جدیدی در گفتگوی پشتیبانی شما ثبت شد.';
		$target_url = home_url( '/' );
		if ( ! empty( $conversation->page_url ) && 0 === strpos( $conversation->page_url, home_url() ) ) {
			$target_url = $conversation->page_url;
		}
		return wp_remote_post(
			'https://api.onesignal.com/notifications',
			array(
				'timeout'  => 8,
				'blocking' => false,
				'headers'  => array(
					'Authorization' => 'Key ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'     => wp_json_encode(
					array(
						'app_id'          => $app_id,
						'target_channel'  => 'push',
						'include_aliases' => array( 'external_id' => array( (string) $conversation->public_id ) ),
						'headings'         => array( 'en' => $title, 'fa' => $title ),
						'contents'         => array( 'en' => $body, 'fa' => $body ),
						'url'              => esc_url_raw( $target_url ),
						'idempotency_key'  => wp_generate_uuid4(),
					)
				),
			)
		);
	}

	private static function send_typing_to_visitor_channel( $conversation ) {
		if ( ! $conversation ) {
			return;
		}
		if ( ! empty( $conversation->visitor_telegram_chat_id ) ) {
			self::telegram_api( 'sendChatAction', array( 'chat_id' => (string) $conversation->visitor_telegram_chat_id, 'action' => 'typing' ) );
			return;
		}
		if ( ! empty( $conversation->visitor_bale_chat_id ) ) {
			self::bale_api( 'sendChatAction', array( 'chat_id' => (string) $conversation->visitor_bale_chat_id, 'action' => 'typing' ) );
		}
	}

	public static function deliver_reply_to_visitor_channel( $conversation, $message ) {
		if ( ! $conversation || ! $message ) {
			return true;
		}
		self::send_web_push( $conversation, $message );
		$prefix = 'ai' === $message->sender ? '🤖 پاسخ دستیار هوشمند' : '💬 پاسخ پشتیبانی';
		$text   = $prefix . "\n\n" . wp_html_excerpt( $message->body, 3400, '…' ) . self::attachment_suffix( $message );
		if ( ! empty( $conversation->visitor_telegram_chat_id ) ) {
			return self::telegram_api( 'sendMessage', array( 'chat_id' => (string) $conversation->visitor_telegram_chat_id, 'text' => $text ) );
		}
		if ( ! empty( $conversation->visitor_bale_chat_id ) ) {
			return self::bale_api( 'sendMessage', array( 'chat_id' => (string) $conversation->visitor_bale_chat_id, 'text' => $text ) );
		}
		return true;
	}

	private static function telegram_user_rate_limit( $chat_id ) {
		$key = 'shcd_tiamis_tg_rl_' . md5( (string) $chat_id );
		$count = (int) get_transient( $key );
		if ( $count >= 20 ) {
			return false;
		}
		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
		return true;
	}

	public static function handle_telegram_update( $update ) {
		global $wpdb;
		if ( ! is_array( $update ) || empty( $update['message'] ) || ! is_array( $update['message'] ) ) {
			return true;
		}
		$message = $update['message'];
		$chat_id = isset( $message['chat']['id'] ) ? (string) $message['chat']['id'] : '';
		$text = isset( $message['text'] ) ? trim( (string) $message['text'] ) : '';
		$is_admin = '' !== $chat_id && in_array( $chat_id, self::telegram_chat_ids(), true );
		$settings = Tiamis_Chat_Core::settings();

		if ( ! $is_admin ) {
			if ( '1' !== $settings['telegram_customer_enabled'] || empty( $message['chat']['type'] ) || 'private' !== $message['chat']['type'] ) {
				return true;
			}
			if ( ! self::telegram_user_rate_limit( $chat_id ) ) {
				self::telegram_api( 'sendMessage', array( 'chat_id' => $chat_id, 'text' => 'تعداد پیام‌های شما زیاد است. یک دقیقه بعد دوباره تلاش کنید.' ) );
				return true;
			}
			$first = isset( $message['from']['first_name'] ) ? $message['from']['first_name'] : '';
			$last = isset( $message['from']['last_name'] ) ? $message['from']['last_name'] : '';
			$profile = array(
				'name' => trim( sanitize_text_field( $first . ' ' . $last ) ),
				'username' => isset( $message['from']['username'] ) ? sanitize_text_field( $message['from']['username'] ) : '',
			);
			$conversation = Tiamis_Chat_Core::create_or_get_telegram_conversation( $chat_id, $profile );
			if ( is_wp_error( $conversation ) ) {
				return true;
			}
			if ( 0 === strpos( $text, '/start' ) || 0 === strpos( $text, '/help' ) ) {
				self::telegram_api( 'sendMessage', array( 'chat_id' => $chat_id, 'text' => $settings['telegram_customer_welcome'] ) );
				return true;
			}
			if ( '' === $text ) {
				self::telegram_api( 'sendMessage', array( 'chat_id' => $chat_id, 'text' => 'در نسخه فعلی فقط پیام متنی پشتیبانی می‌شود.' ) );
				return true;
			}
			$saved = Tiamis_Chat_Core::add_message( $conversation->id, 'visitor', $text, 'telegram-user', $profile['name'], array( 'telegram_chat_id' => $chat_id ) );
			if ( ! is_wp_error( $saved ) ) {
				$fresh = Tiamis_Chat_Core::get_conversation( $conversation->id );
				self::send_visitor_message_to_telegram( $fresh, $saved );
				self::send_visitor_message_to_bale( $fresh, $saved );
				self::telegram_api( 'sendMessage', array( 'chat_id' => $chat_id, 'text' => '✅ پیام شما برای پشتیبانی ارسال شد.' ) );
				self::maybe_auto_reply( $conversation->id );
			}
			return true;
		}

		if ( '' === $text ) {
			self::telegram_api( 'sendMessage', array( 'chat_id' => $chat_id, 'text' => 'در نسخه فعلی فقط پاسخ متنی پشتیبانی می‌شود.' ) );
			return true;
		}
		if ( 0 === strpos( $text, '/start' ) || 0 === strpos( $text, '/help' ) ) {
			self::telegram_api( 'sendMessage', array( 'chat_id' => $chat_id, 'text' => "برای پاسخ به کاربر، روی پیام دریافتی Reply بزنید.\nروش جایگزین: /reply شناسه-گفتگو متن پاسخ" ) );
			return true;
		}

		$conversation = null;
		if ( ! empty( $message['reply_to_message']['message_id'] ) ) {
			$map_table = Tiamis_Chat_DB::table( 'telegram_map' );
			$map = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE telegram_chat_id = %s AND telegram_message_id = %d LIMIT 1', $map_table, $chat_id, (int) $message['reply_to_message']['message_id'] ) );
			if ( $map ) {
				$conversation = Tiamis_Chat_Core::get_conversation( (int) $map->conversation_id );
			}
		}
		if ( ! $conversation && preg_match( '/^\/reply\s+([a-f0-9-]{20,40})\s+(.+)$/isu', $text, $matches ) ) {
			$conversation = Tiamis_Chat_Core::get_conversation_by_public_id( $matches[1] );
			$text = trim( $matches[2] );
		}
		if ( ! $conversation ) {
			self::telegram_api( 'sendMessage', array( 'chat_id' => $chat_id, 'text' => 'گفتگوی مرتبط پیدا نشد. روی همان پیام سایت Reply بزنید یا از دستور /reply استفاده کنید.' ) );
			return true;
		}
		$sender_name = isset( $message['from']['first_name'] ) ? sanitize_text_field( $message['from']['first_name'] ) : 'اپراتور تلگرام';
		$saved = Tiamis_Chat_Core::add_message( $conversation->id, 'admin', $text, 'telegram', $sender_name, array( 'telegram_chat_id' => $chat_id ) );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		$wpdb->update(
			Tiamis_Chat_DB::table( 'messages' ),
			array( 'telegram_chat_id' => $chat_id, 'telegram_message_id' => isset( $message['message_id'] ) ? (int) $message['message_id'] : 0 ),
			array( 'id' => (int) $saved->id )
		);
		$ack_payload = array( 'chat_id' => $chat_id, 'text' => '✅ پاسخ به کاربر تحویل داده شد.' );
		if ( isset( $message['message_id'] ) ) {
			$ack_payload['reply_parameters'] = array( 'message_id' => (int) $message['message_id'] );
		}
		self::deliver_reply_to_visitor_channel( $conversation, $saved );
		self::telegram_api( 'sendMessage', $ack_payload );
		self::mirror_reply_to_telegram( $conversation, $saved, $chat_id );
		self::mirror_reply_to_bale( $conversation, $saved );
		return true;
	}

	private static function bale_user_rate_limit( $chat_id ) {
		$key   = 'shcd_tiamis_bale_rl_' . md5( (string) $chat_id );
		$count = (int) get_transient( $key );
		if ( $count >= 20 ) {
			return false;
		}
		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
		return true;
	}

	public static function handle_bale_update( $update ) {
		global $wpdb;
		if ( ! is_array( $update ) || empty( $update['message'] ) || ! is_array( $update['message'] ) ) {
			return true;
		}
		$message  = $update['message'];
		$chat_id  = isset( $message['chat']['id'] ) ? (string) $message['chat']['id'] : '';
		$text     = isset( $message['text'] ) ? trim( (string) $message['text'] ) : '';
		$is_admin = '' !== $chat_id && in_array( $chat_id, self::bale_chat_ids(), true );
		$settings = Tiamis_Chat_Core::settings();

		if ( ! $is_admin ) {
			if ( '1' !== $settings['bale_customer_enabled'] || empty( $message['chat']['type'] ) || 'private' !== $message['chat']['type'] ) {
				return true;
			}
			if ( ! self::bale_user_rate_limit( $chat_id ) ) {
				self::bale_api( 'sendMessage', array( 'chat_id' => $chat_id, 'text' => 'پیام‌ها با فاصله خیلی کوتاه فرستاده شده‌اند؛ لطفاً یک دقیقه بعد دوباره امتحان کنید.' ) );
				return true;
			}
			$first   = isset( $message['from']['first_name'] ) ? $message['from']['first_name'] : '';
			$last    = isset( $message['from']['last_name'] ) ? $message['from']['last_name'] : '';
			$profile = array(
				'name'     => trim( sanitize_text_field( $first . ' ' . $last ) ),
				'username' => isset( $message['from']['username'] ) ? sanitize_text_field( $message['from']['username'] ) : '',
			);
			$conversation = Tiamis_Chat_Core::create_or_get_bale_conversation( $chat_id, $profile );
			if ( is_wp_error( $conversation ) ) {
				return true;
			}
			if ( 0 === strpos( $text, '/start' ) || 0 === strpos( $text, '/help' ) ) {
				self::bale_api( 'sendMessage', array( 'chat_id' => $chat_id, 'text' => $settings['bale_customer_welcome'] ) );
				return true;
			}
			if ( '' === $text ) {
				self::bale_api( 'sendMessage', array( 'chat_id' => $chat_id, 'text' => 'در حال حاضر پیام‌های متنی در این بخش پشتیبانی می‌شوند.' ) );
				return true;
			}
			$saved = Tiamis_Chat_Core::add_message( $conversation->id, 'visitor', $text, 'bale-user', $profile['name'], array( 'bale_chat_id' => $chat_id ) );
			if ( ! is_wp_error( $saved ) ) {
				$fresh = Tiamis_Chat_Core::get_conversation( $conversation->id );
				self::send_visitor_message_to_bale( $fresh, $saved );
				self::send_visitor_message_to_telegram( $fresh, $saved );
				self::bale_api( 'sendMessage', array( 'chat_id' => $chat_id, 'text' => '✅ پیام شما به تیم پشتیبانی رسید.' ) );
				self::maybe_auto_reply( $conversation->id );
			}
			return true;
		}

		if ( '' === $text ) {
			self::bale_api( 'sendMessage', array( 'chat_id' => $chat_id, 'text' => 'برای پاسخ به کاربر، یک پیام متنی بفرستید.' ) );
			return true;
		}
		if ( 0 === strpos( $text, '/start' ) || 0 === strpos( $text, '/help' ) ) {
			self::bale_api( 'sendMessage', array( 'chat_id' => $chat_id, 'text' => "روی پیام کاربر Reply بزنید و پاسخ را بنویسید.\nروش جایگزین: /reply شناسه-گفتگو متن پاسخ" ) );
			return true;
		}
		$conversation = null;
		$reply_id = 0;
		if ( ! empty( $message['reply_to_message']['message_id'] ) ) {
			$reply_id = (int) $message['reply_to_message']['message_id'];
		} elseif ( ! empty( $message['reply_to_message_id'] ) ) {
			$reply_id = (int) $message['reply_to_message_id'];
		}
		if ( $reply_id ) {
			$bale_map_table = Tiamis_Chat_DB::table( 'bale_map' );
			$map = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE bale_chat_id = %s AND bale_message_id = %d LIMIT 1', $bale_map_table, $chat_id, $reply_id ) );
			if ( $map ) {
				$conversation = Tiamis_Chat_Core::get_conversation( (int) $map->conversation_id );
			}
		}
		if ( ! $conversation && preg_match( '/^\/reply\s+([a-f0-9-]{20,40})\s+(.+)$/isu', $text, $matches ) ) {
			$conversation = Tiamis_Chat_Core::get_conversation_by_public_id( $matches[1] );
			$text         = trim( $matches[2] );
		}
		if ( ! $conversation ) {
			self::bale_api( 'sendMessage', array( 'chat_id' => $chat_id, 'text' => 'گفتگوی مرتبط پیدا نشد. روی همان پیام کاربر Reply بزنید یا دستور /reply را به‌کار ببرید.' ) );
			return true;
		}
		$sender_name = isset( $message['from']['first_name'] ) ? sanitize_text_field( $message['from']['first_name'] ) : 'اپراتور بله';
		$saved       = Tiamis_Chat_Core::add_message( $conversation->id, 'admin', $text, 'bale', $sender_name, array( 'bale_chat_id' => $chat_id ) );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		self::deliver_reply_to_visitor_channel( $conversation, $saved );
		$ack = array( 'chat_id' => $chat_id, 'text' => '✅ پاسخ برای کاربر ارسال شد.' );
		if ( isset( $message['message_id'] ) ) {
			$ack['reply_to_message_id'] = (int) $message['message_id'];
		}
		self::bale_api( 'sendMessage', $ack );
		self::mirror_reply_to_bale( $conversation, $saved, $chat_id );
		self::mirror_reply_to_telegram( $conversation, $saved );
		return true;
	}

	public static function ai_generate( $conversation_id, $extra_instruction = '', $use_site_knowledge = true ) {
		$settings = Tiamis_Chat_Core::settings();
		if ( 'off' === $settings['ai_mode'] ) {
			return new WP_Error( 'shcd_tiamis_ai_disabled', 'هوش مصنوعی غیرفعال است.' );
		}

		// The Free edition executes AI requests only through Cloudflare Workers AI.
		$provider = 'cloudflare';

		$history  = Tiamis_Chat_Core::get_messages( $conversation_id, 0, 30 );
		$language_instruction = 'پاسخ را به زبان آخرین پیام کاربر بنویس و قواعد نگارشی همان زبان را رعایت کن: فارسی با نیم‌فاصله و نثر طبیعی، عربی با صرف و نحو درست، و انگلیسی با نثر حرفه‌ای. از ترجمه تحت‌اللفظی و لحن ماشینی پرهیز کن.';
		$system_prompt = trim( sanitize_textarea_field( $settings['ai_system_prompt'] ) . "\n\n" . $language_instruction );
		if ( '' !== trim( (string) $extra_instruction ) ) {
			$system_prompt .= "\n\n" . sanitize_textarea_field( $extra_instruction );
		}
		if ( $use_site_knowledge && '1' === (string) ( $settings['ai_rag_enabled'] ?? '0' ) ) {
			$last_query = '';
			foreach ( array_reverse( $history ) as $history_item ) {
				if ( 'visitor' === $history_item->sender ) {
					$last_query = wp_strip_all_tags( (string) $history_item->body );
					break;
				}
			}
			$knowledge = $last_query ? Tiamis_Chat_Platform::knowledge_search( $last_query, 4 ) : array();
			if ( $knowledge ) {
				$context = array();
				foreach ( $knowledge as $source ) {
					$excerpt = function_exists( 'mb_substr' ) ? mb_substr( wp_strip_all_tags( $source->content ), 0, 1800 ) : substr( wp_strip_all_tags( $source->content ), 0, 1800 );
					$context[] = 'منبع: ' . sanitize_text_field( $source->title ) . "\n" . $excerpt;
				}
				$system_prompt .= "\n\nاطلاعات پایگاه دانش سایت:\n" . implode( "\n\n---\n\n", $context ) . "\n\nفقط وقتی این منابع به پرسش مرتبط‌اند از آن‌ها استفاده کن و چیزی را حدس نزن.";
			}
		}
		$messages = array( array( 'role' => 'system', 'content' => $system_prompt ) );
		foreach ( $history as $item ) {
			if ( 'system' === $item->sender ) {
				continue;
			}
			$messages[] = array(
				'role'    => 'visitor' === $item->sender ? 'user' : 'assistant',
				'content' => wp_strip_all_tags( (string) $item->body ),
			);
		}

		$temperature = min( 1, max( 0, (float) $settings['ai_temperature'] ) );
		$max_tokens  = max( 64, min( 2000, absint( $settings['ai_max_tokens'] ) ) );
		$headers     = array( 'Content-Type' => 'application/json' );
		$endpoint    = '';
		$payload     = array();
		$is_safe_url = true;

		if ( 'cloudflare' === $provider ) {
			$account_id = preg_replace( '/[^a-f0-9]/i', '', (string) $settings['cloudflare_account_id'] );
			$model      = trim( (string) $settings['cloudflare_model'] );
			$token      = Tiamis_Chat_Core::decrypt_secret( $settings['cloudflare_api_token'] );
			if ( ! preg_match( '/^[a-f0-9]{20,64}$/i', $account_id ) || ! preg_match( '#^@cf/[A-Za-z0-9._-]+/[A-Za-z0-9._-]+$#', $model ) || '' === $token ) {
				return new WP_Error( 'shcd_tiamis_cloudflare_config', 'Account ID، API Token یا نام مدل Cloudflare Workers AI معتبر نیست.' );
			}
			$endpoint                 = 'https://api.cloudflare.com/client/v4/accounts/' . $account_id . '/ai/run/' . $model;
			$headers['Authorization'] = 'Bearer ' . $token;
			$payload                  = array(
				'messages'    => $messages,
				'temperature' => $temperature,
				'max_tokens'  => $max_tokens,
			);
		} elseif ( 'openrouter' === $provider ) {
			$model = sanitize_text_field( (string) $settings['ai_model'] );
			$key   = Tiamis_Chat_Core::decrypt_secret( $settings['ai_api_key'] );
			if ( '' === $model || '' === $key ) {
				return new WP_Error( 'shcd_tiamis_ai_config', 'مدل یا کلید API سرویس OpenRouter تنظیم نشده است.' );
			}
			$endpoint                  = 'https://openrouter.ai/api/v1/chat/completions';
			$headers['Authorization']  = 'Bearer ' . $key;
			$headers['HTTP-Referer']   = home_url( '/' );
			$headers['X-Title']        = get_bloginfo( 'name' ) . ' - Tiamis Chat';
			$payload                   = array( 'model' => $model, 'messages' => $messages, 'temperature' => $temperature, 'max_tokens' => $max_tokens );
		} else {
			$endpoint = esc_url_raw( (string) $settings['ai_endpoint'] );
			$model    = sanitize_text_field( (string) $settings['ai_model'] );
			$key      = Tiamis_Chat_Core::decrypt_secret( $settings['ai_api_key'] );
			if ( '' === $endpoint || '' === $model ) {
				return new WP_Error( 'shcd_tiamis_ai_config', 'نشانی API یا مدل هوش مصنوعی تنظیم نشده است.' );
			}
			if ( 'ollama' === $provider ) {
				$host       = strtolower( (string) wp_parse_url( $endpoint, PHP_URL_HOST ) );
				$local_host = in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true );
				$is_safe_url = false;
				if ( ! $local_host && ! apply_filters( 'shcd_tiamis_allow_private_ai_endpoint', false, $endpoint ) ) {
					return new WP_Error( 'shcd_tiamis_ollama_endpoint', 'برای امنیت، Endpoint پیش‌فرض Ollama باید روی localhost باشد؛ برای شبکه خصوصی از فیلتر توسعه‌دهنده استفاده کنید.' );
				}
			}
			if ( '' !== $key ) {
				$headers['Authorization'] = 'Bearer ' . $key;
			}
			$payload = array( 'model' => $model, 'messages' => $messages, 'temperature' => $temperature, 'max_tokens' => $max_tokens );
			if ( 'ollama' === $provider && false !== strpos( $endpoint, '/api/chat' ) ) {
				$payload['stream']  = false;
				$payload['options'] = array( 'temperature' => $temperature, 'num_predict' => $max_tokens );
				unset( $payload['temperature'], $payload['max_tokens'] );
			}
		}

		$args = array(
			'timeout'     => 45,
			'redirection' => 2,
			'headers'     => $headers,
			'body'        => wp_json_encode( $payload ),
			'data_format' => 'body',
		);
		$response = $is_safe_url ? wp_safe_remote_post( $endpoint, $args ) : wp_remote_post( $endpoint, $args );
		if ( is_wp_error( $response ) ) {
			Tiamis_Chat_Core::log( 'ai_transport_error', array( 'provider' => $provider, 'error' => $response->get_error_message() ) );
			return $response;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 || ! is_array( $data ) ) {
			$error = isset( $data['errors'][0]['message'] ) ? $data['errors'][0]['message'] : ( isset( $data['error']['message'] ) ? $data['error']['message'] : 'سرویس هوش مصنوعی خطای HTTP ' . $status . ' برگرداند.' );
			Tiamis_Chat_Core::log( 'ai_http_error', array( 'provider' => $provider, 'status' => $status, 'error' => $error ) );
			return new WP_Error( 'shcd_tiamis_ai_http', sanitize_text_field( $error ) );
		}

		$content = '';
		if ( isset( $data['result']['response'] ) && is_string( $data['result']['response'] ) ) {
			$content = trim( $data['result']['response'] );
		} elseif ( isset( $data['result']['choices'][0]['message']['content'] ) ) {
			$content = trim( (string) $data['result']['choices'][0]['message']['content'] );
		} elseif ( isset( $data['choices'][0]['message']['content'] ) ) {
			$content = trim( (string) $data['choices'][0]['message']['content'] );
		} elseif ( isset( $data['message']['content'] ) ) {
			$content = trim( (string) $data['message']['content'] );
		} elseif ( isset( $data['response'] ) && is_string( $data['response'] ) ) {
			$content = trim( $data['response'] );
		}
		if ( '' === $content ) {
			Tiamis_Chat_Core::log( 'ai_empty_response', array( 'provider' => $provider, 'status' => $status ) );
			return new WP_Error( 'shcd_tiamis_ai_response', 'پاسخ قابل استفاده‌ای از سرویس هوش مصنوعی دریافت نشد.' );
		}
		return wp_strip_all_tags( $content );
	}


	public static function ai_insights( $conversation_id ) {
		$conversation = Tiamis_Chat_Core::get_conversation( $conversation_id );
		if ( ! $conversation ) {
			return new WP_Error( 'shcd_tiamis_missing_conversation', 'گفتگو پیدا نشد.' );
		}
		$history = Tiamis_Chat_Core::get_messages( $conversation_id, 0, 80 );
		$visitor_text = array();
		foreach ( $history as $item ) {
			if ( 'visitor' === $item->sender ) {
				$visitor_text[] = trim( wp_strip_all_tags( (string) $item->body ) );
			}
		}
		$joined = trim( implode( ' — ', array_filter( $visitor_text ) ) );
		$short = function_exists( 'mb_substr' ) ? mb_substr( $joined, 0, 420 ) : substr( $joined, 0, 420 );
		$fallback = array(
			'summary'     => $short ? $short : 'هنوز پیام کافی برای خلاصه‌سازی ثبت نشده است.',
			'sentiment'   => preg_match( '/(ناراضی|افتضاح|بد|شکایت|مشکل|خطا|عصبانی|refund|angry|complaint|سيئ|مشكلة)/iu', $joined ) ? 'نیازمند توجه؛ نشانه‌هایی از نارضایتی در پیام‌ها دیده می‌شود.' : 'خنثی یا مثبت',
			'topic'       => preg_match( '/(قیمت|خرید|سفارش|پرداخت|فاکتور|price|order|شراء|طلب)/iu', $joined ) ? 'فروش و سفارش' : ( preg_match( '/(خطا|فنی|نصب|اتصال|api|وب.?هوک|error|technical)/iu', $joined ) ? 'پشتیبانی فنی' : 'پشتیبانی عمومی' ),
			'next_action' => 'آخرین درخواست کاربر را تأیید کنید، پاسخ روشن بدهید و در صورت نیاز یک مورد در چک‌لیست پیگیری بسازید.',
			'quality'     => count( $history ) > 1 ? 'گفتگو برای ارزیابی اولیه داده کافی دارد.' : 'برای ارزیابی کیفیت، پیام‌های بیشتری لازم است.',
		);

		$settings = Tiamis_Chat_Core::settings();
		if ( 'off' === ( $settings['ai_mode'] ?? 'off' ) ) {
			return $fallback;
		}
		$instruction = 'این گفتگو را برای کارشناس پشتیبانی تحلیل کن. فقط یک شیء JSON معتبر و بدون Markdown با کلیدهای summary، sentiment، topic، next_action و quality برگردان. هر مقدار کوتاه، طبیعی و به زبان فارسی باشد. در quality نقاط قوت یا ضعف پاسخ‌گویی را بی‌طرفانه بگو.';
		$result = self::ai_generate( $conversation_id, $instruction );
		if ( is_wp_error( $result ) ) {
			return $fallback;
		}
		$clean = trim( preg_replace( '/^```(?:json)?|```$/mi', '', (string) $result ) );
		$decoded = json_decode( $clean, true );
		if ( ! is_array( $decoded ) ) {
			return $fallback;
		}
		foreach ( array_keys( $fallback ) as $key ) {
			if ( empty( $decoded[ $key ] ) || ! is_scalar( $decoded[ $key ] ) ) {
				$decoded[ $key ] = $fallback[ $key ];
			} else {
				$decoded[ $key ] = sanitize_textarea_field( (string) $decoded[ $key ] );
			}
		}
		return array_intersect_key( $decoded, $fallback );
	}

	public static function maybe_auto_reply( $conversation_id ) {
		$settings = Tiamis_Chat_Core::settings();
		if ( 'auto' !== $settings['ai_mode'] ) {
			return;
		}
		$policy          = isset( $settings['ai_operator_policy'] ) ? $settings['ai_operator_policy'] : 'offline_only';
		$operator_online = Tiamis_Chat_Core::operator_is_online();
		if ( 'never' === $policy || ( 'offline_only' === $policy && $operator_online ) ) {
			Tiamis_Chat_Core::log( 'ai_auto_reply_skipped', array( 'conversation_id' => $conversation_id, 'policy' => $policy, 'operator_online' => $operator_online ? 1 : 0 ) );
			return;
		}

		$history = Tiamis_Chat_Core::get_messages( $conversation_id, 0, 30 );
		$max_auto = max( 0, absint( $settings['ai_max_auto_replies'] ?? 2 ) );
		$consecutive_ai = 0;
		$last_visitor_text = '';
		for ( $index = count( $history ) - 1; $index >= 0; $index-- ) {
			$item = $history[ $index ];
			if ( 'visitor' === $item->sender && '' === $last_visitor_text ) {
				$last_visitor_text = (string) $item->body;
			}
			if ( 'ai' === $item->sender ) {
				$consecutive_ai++;
				continue;
			}
			if ( 'visitor' !== $item->sender ) {
				break;
			}
		}
		if ( 0 === $max_auto || $consecutive_ai >= $max_auto ) {
			Tiamis_Chat_Core::log( 'ai_auto_reply_limit', array( 'conversation_id' => $conversation_id, 'count' => $consecutive_ai ) );
			return;
		}
		$sensitive = array_filter( array_map( 'trim', preg_split( '/[،,\r\n]+/u', (string) ( $settings['ai_sensitive_topics'] ?? '' ) ) ) );
		foreach ( $sensitive as $topic ) {
			if ( '' !== $topic && false !== ( function_exists( 'mb_stripos' ) ? mb_stripos( $last_visitor_text, $topic ) : stripos( $last_visitor_text, $topic ) ) ) {
				Tiamis_Chat_Platform::add_task( $conversation_id, 'بررسی انسانی موضوع حساس: ' . $topic );
				Tiamis_Chat_Core::log( 'ai_sensitive_topic_skipped', array( 'conversation_id' => $conversation_id, 'topic' => $topic ) );
				return;
			}
		}
		Tiamis_Chat_Core::set_typing( $conversation_id, 'operator', true, 20 );
		self::send_typing_to_visitor_channel( Tiamis_Chat_Core::get_conversation( $conversation_id ) );
		$reply = self::ai_generate( $conversation_id );
		Tiamis_Chat_Core::set_typing( $conversation_id, 'operator', false );
		if ( is_wp_error( $reply ) ) {
			return;
		}
		$message = Tiamis_Chat_Core::add_message( $conversation_id, 'ai', $reply, 'ai', 'دستیار هوشمند' );
		if ( ! is_wp_error( $message ) ) {
			$conversation = Tiamis_Chat_Core::get_conversation( $conversation_id );
			self::deliver_reply_to_visitor_channel( $conversation, $message );
			self::mirror_reply_to_telegram( $conversation, $message );
			self::mirror_reply_to_bale( $conversation, $message );
		}
	}
}
