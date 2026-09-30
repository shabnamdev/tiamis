<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tiamis_Chat_Core {
	public static function defaults() {
		return array(
			'admin_language'          => 'fa',
			'widget_language'         => 'fa',
			'enabled'                 => '1',
			'bubble_chat_enabled'     => '1',
			'widget_title'            => 'گفتگوی آنلاین',
			'widget_subtitle'         => 'پیامتان را بنویسید؛ پاسخ پشتیبانی را همین‌جا دریافت می‌کنید.',
			'welcome_message'         => 'سلام و خوش آمدید. چگونه می‌توانیم راهنمایی‌تان کنیم؟',
			'agent_name'              => 'تیم پشتیبانی',
			'agent_photo'             => TIAMIS_CHAT_URL . 'assets/image/agent1.webp',
			'agents'                  => array(
					array( 'name' => 'کارشناس پشتیبانی', 'role' => 'پشتیبانی آنلاین', 'photo' => TIAMIS_CHAT_URL . 'assets/image/agent1.webp' ),
				),
			'position'                => 'left',
			'theme'                   => 'violet',
			'bubble_style'            => 'gradient',
			'bubble_animation'        => 'tada',
			'bubble_random_animation' => '0',
			'button_label'            => 'پشتیبانی آنلاین',
			'profile_required'        => '0',
			'collect_email'           => '1',
			'collect_phone'           => '0',
			'consent_required'        => '0',
			'consent_text'            => 'با آغاز گفتگو، با ذخیره اطلاعات لازم برای ارائه خدمات پشتیبانی موافقت می‌کنم.',
			'poll_interval'           => 1,
			'realtime_mode'          => 'auto',
			'realtime_websocket_url' => '',
			'realtime_publish_url'   => '',
			'realtime_secret'        => self::encrypt_secret( wp_generate_password( 48, false, false ) ),
			'realtime_active_ms'     => 450,
			'realtime_idle_ms'       => 2200,
			'offline_queue_enabled'  => '1',
			'pwa_enabled'            => '0',
			'ip_mode'                 => 'hash',
			'retention_days'          => 90,
			'browser_notifications'   => '1',
			'onesignal_enabled'       => '0',
			'onesignal_app_id'        => '',
			'onesignal_rest_api_key'  => '',
			'telegram_enabled'        => '0',
			'telegram_customer_enabled'=> '1',
			'telegram_customer_welcome'=> 'سلام و خوش آمدید. پیام شما دریافت شد و تیم پشتیبانی از همین گفتگو پاسخ خواهد داد.',
			'telegram_bot_token'      => '',
			'telegram_admin_chat_ids' => '',
			'telegram_webhook_secret' => wp_generate_password( 48, false, false ),
			'telegram_update_mode'    => 'polling',
			'telegram_api_base'       => 'https://api.telegram.org',
			'telegram_poll_interval'  => 30,
			'telegram_poll_secret'    => wp_generate_password( 48, false, false ),
			'bale_enabled'            => '0',
			'bale_customer_enabled'   => '1',
			'bale_customer_welcome'   => 'سلام و خوش آمدید. پیام شما به تیم پشتیبانی رسید و پاسخ را در همین گفتگو دریافت می‌کنید.',
			'bale_bot_token'          => '',
			'bale_admin_chat_ids'     => '',
			'bale_webhook_secret'     => wp_generate_password( 48, false, false ),
			'auto_assignment_enabled'=> '1',
			'default_department'     => 'پشتیبانی',
			'departments'            => array( 'پشتیبانی', 'فروش', 'فنی' ),
			'sla_first_response_minutes' => 15,
			'macros'                 => array(
				array( 'shortcut' => '/سلام', 'title' => 'خوشامدگویی', 'message' => 'سلام و وقت بخیر. پیام شما را دیدم؛ با کمال میل راهنمایی‌تان می‌کنم.' ),
				array( 'shortcut' => '/پیگیری', 'title' => 'در حال پیگیری', 'message' => 'موضوع شما در حال بررسی است. به‌محض آماده‌شدن نتیجه، همین‌جا اطلاع می‌دهم.' ),
				array( 'shortcut' => '/پایان', 'title' => 'پایان گفتگو', 'message' => 'خوشحالیم که توانستیم کمک کنیم. اگر پرسش دیگری داشتید، همین‌جا پیام بگذارید.' ),
			),
			'ai_mode'                 => 'off',
			'ai_operator_policy'      => 'offline_only',
			'operator_presence_mode'  => 'auto',
			'operator_presence_timeout'=> 90,
			'ai_provider'             => 'cloudflare',
			'ai_endpoint'             => 'https://openrouter.ai/api/v1/chat/completions',
			'ai_api_key'              => '',
			'ai_model'                => 'openrouter/free',
			'ai_system_prompt'        => 'شما دستیار پشتیبانی این وب‌سایت هستید. زبان کاربر را از متن گفتگو تشخیص دهید و پاسخ را به همان زبان بنویسید. در فارسی، نیم‌فاصله، نشانه‌گذاری و نثر طبیعی فارسی را رعایت کنید؛ در عربی، قواعد صرفی و نحوی و نشانه‌گذاری عربی را دقیق به‌کار ببرید؛ و در انگلیسی، از نثر روشن و حرفه‌ای استفاده کنید. از عبارت‌های ماشینی، ترجمه تحت‌اللفظی و تکرار بی‌دلیل پرهیز کنید. پاسخ باید دقیق، مؤدبانه، روان و متناسب با بافت مکالمه باشد. فقط بر اطلاعات موجود تکیه کنید و اگر پاسخ قطعی ندارید، موضوع را شفاف به اپراتور انسانی ارجاع دهید.',
			'ai_temperature'          => '0.3',
			'ai_max_tokens'           => 500,
			'ai_rag_enabled'          => '1',
			'ai_qa_enabled'           => '0',
			'ai_sensitive_topics'     => 'حقوقی,پزشکی,پرداخت,شکایت,بازپرداخت',
			'ai_max_auto_replies'     => 2,
			'cloudflare_account_id'   => '',
			'cloudflare_api_token'    => '',
			'cloudflare_model'        => '@cf/meta/llama-3.1-8b-instruct-fast',
			'rate_limit_messages'     => 15,
			'admin_font_url'          => '',
			'debug_enabled'           => '0',
		);
	}

	public static function ensure_defaults() {
		$stored   = get_option( TIAMIS_CHAT_OPTION, array() );
		$stored   = is_array( $stored ) ? $stored : array();
		$settings = wp_parse_args( $stored, self::defaults() );

		if ( $stored !== $settings ) {
			update_option( TIAMIS_CHAT_OPTION, $settings, false );
		}
	}

	public static function settings() {
		$settings = get_option( TIAMIS_CHAT_OPTION, array() );
		$settings = wp_parse_args( is_array( $settings ) ? $settings : array(), self::defaults() );
		$settings['ai_provider'] = 'cloudflare';
		$settings['bubble_style'] = 'gradient';
		$settings['bubble_animation'] = 'tada';
		$settings['bubble_random_animation'] = '0';
		$settings['bale_enabled'] = '0';
		$settings['bale_customer_enabled'] = '0';
		$settings['agents'] = isset( $settings['agents'] ) && is_array( $settings['agents'] ) ? array_slice( $settings['agents'], 0, 1 ) : array();
		return $settings;
	}

	public static function agents( $settings = null ) {
		$settings = is_array( $settings ) ? $settings : self::settings();
		$raw      = isset( $settings['agents'] ) && is_array( $settings['agents'] ) ? $settings['agents'] : array();
		$agents   = array();
		foreach ( array_slice( $raw, 0, 1 ) as $agent ) {
			if ( ! is_array( $agent ) ) {
				continue;
			}
			$name       = isset( $agent['name'] ) ? sanitize_text_field( $agent['name'] ) : '';
			$role       = isset( $agent['role'] ) ? sanitize_text_field( $agent['role'] ) : '';
			$photo      = isset( $agent['photo'] ) ? esc_url_raw( $agent['photo'] ) : '';
			$department = isset( $agent['department'] ) ? sanitize_text_field( $agent['department'] ) : '';
			$languages  = isset( $agent['languages'] ) ? sanitize_text_field( $agent['languages'] ) : 'fa';
			$skills     = isset( $agent['skills'] ) ? sanitize_text_field( $agent['skills'] ) : '';
			$capacity   = max( 1, min( 50, absint( $agent['capacity'] ?? 5 ) ) );
			$wp_user_id = absint( $agent['wp_user_id'] ?? 0 );
			$active     = ! isset( $agent['active'] ) || ! empty( $agent['active'] );
			if ( '' === $name && '' === $photo ) {
				continue;
			}
			$agents[] = array( 'name' => $name, 'role' => $role, 'photo' => $photo, 'department' => $department, 'languages' => $languages, 'skills' => $skills, 'capacity' => $capacity, 'wp_user_id' => $wp_user_id, 'active' => $active );
		}
		if ( ! $agents ) {
			$agents[] = array(
				'name'  => sanitize_text_field( isset( $settings['agent_name'] ) ? $settings['agent_name'] : 'تیم پشتیبانی' ),
				'role'  => 'پشتیبانی آنلاین',
				'photo' => esc_url_raw( isset( $settings['agent_photo'] ) ? $settings['agent_photo'] : '' ),
				'department' => sanitize_text_field( $settings['default_department'] ?? 'پشتیبانی' ),
				'languages' => 'fa', 'skills' => '', 'capacity' => 5, 'wp_user_id' => 0, 'active' => true,
			);
		}
		return $agents;
	}

	public static function agent_profile( $name = '', $index = null, $settings = null ) {
		$agents = self::agents( is_array( $settings ) ? $settings : self::settings() );
		if ( null !== $index && isset( $agents[ absint( $index ) ] ) ) {
			$profile          = $agents[ absint( $index ) ];
			$profile['index'] = absint( $index );
			return $profile;
		}
		$name = trim( sanitize_text_field( (string) $name ) );
		foreach ( $agents as $agent_index => $agent ) {
			if ( '' !== $name && 0 === strcasecmp( $name, (string) $agent['name'] ) ) {
				$agent['index'] = $agent_index;
				return $agent;
			}
		}
		$profile          = $agents[0];
		$profile['index'] = 0;
		return $profile;
	}

	public static function update_settings( $settings ) {
		$settings = wp_parse_args( $settings, self::defaults() );
		$settings['ai_provider'] = 'cloudflare';
		update_option( TIAMIS_CHAT_OPTION, $settings, false );
	}

	public static function log( $event, $context = array() ) {
		$settings = self::settings();
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG || '1' !== $settings['debug_enabled'] ) {
			return;
		}
		$blocked = array( 'token', 'api_key', 'authorization', 'secret', 'password', 'telegram_bot_token', 'bale_bot_token', 'cloudflare_api_token' );
		$clean   = array();
		foreach ( is_array( $context ) ? $context : array( 'value' => $context ) as $key => $value ) {
			$normalized = strtolower( (string) $key );
			$redact     = false;
			foreach ( $blocked as $fragment ) {
				if ( false !== strpos( $normalized, $fragment ) ) {
					$redact = true;
					break;
				}
			}
			$clean[ sanitize_key( (string) $key ) ] = $redact ? '[redacted]' : ( is_scalar( $value ) ? wp_strip_all_tags( (string) $value ) : $value );
		}
		error_log( '[SHCD Tiamis] ' . sanitize_key( (string) $event ) . ' ' . wp_json_encode( $clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	public static function now() {
		return current_time( 'mysql' );
	}

	public static function encrypt_secret( $value ) {
		$value = (string) $value;
		if ( '' === $value ) {
			return '';
		}
		$key = hash( 'sha256', wp_salt( 'auth' ), true );
		if ( function_exists( 'sodium_crypto_secretbox' ) ) {
			$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$cipher = sodium_crypto_secretbox( $value, $nonce, $key );
			return 'sodium:' . base64_encode( $nonce . $cipher );
		}
		if ( function_exists( 'openssl_encrypt' ) ) {
			$iv = random_bytes( 16 );
			$cipher = openssl_encrypt( $value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
			return 'openssl:' . base64_encode( $iv . $cipher );
		}
		return 'plain:' . base64_encode( $value );
	}

	public static function decrypt_secret( $value ) {
		$value = (string) $value;
		if ( '' === $value ) {
			return '';
		}
		$key = hash( 'sha256', wp_salt( 'auth' ), true );
		if ( 0 === strpos( $value, 'sodium:' ) && function_exists( 'sodium_crypto_secretbox_open' ) ) {
			$decoded = base64_decode( substr( $value, 7 ), true );
			if ( false === $decoded || strlen( $decoded ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
				return '';
			}
			$nonce = substr( $decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$plain = sodium_crypto_secretbox_open( substr( $decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ), $nonce, $key );
			return false === $plain ? '' : $plain;
		}
		if ( 0 === strpos( $value, 'openssl:' ) && function_exists( 'openssl_decrypt' ) ) {
			$decoded = base64_decode( substr( $value, 8 ), true );
			if ( false === $decoded || strlen( $decoded ) <= 16 ) {
				return '';
			}
			$plain = openssl_decrypt( substr( $decoded, 16 ), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, substr( $decoded, 0, 16 ) );
			return false === $plain ? '' : $plain;
		}
		if ( 0 === strpos( $value, 'plain:' ) ) {
			$plain = base64_decode( substr( $value, 6 ), true );
			return false === $plain ? '' : $plain;
		}
		return $value;
	}

	public static function random_token() {
		return rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' );
	}

	public static function token_hash( $token ) {
		return hash_hmac( 'sha256', (string) $token, wp_salt( 'nonce' ) );
	}

	public static function visitor_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ip = filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
		/**
		 * Filters the server-observed visitor IP. Sites behind a trusted reverse proxy
		 * may use this hook to provide a validated client IP header.
		 */
		$filtered = apply_filters( 'shcd_tiamis_visitor_ip', $ip );
		return filter_var( $filtered, FILTER_VALIDATE_IP ) ? $filtered : '';
	}

	public static function stored_ip( $mode = null ) {
		$settings = self::settings();
		$mode = null === $mode ? $settings['ip_mode'] : $mode;
		$ip = self::visitor_ip();
		if ( 'none' === $mode || '' === $ip ) {
			return '';
		}
		if ( 'full' === $mode ) {
			return $ip;
		}
		return hash_hmac( 'sha256', $ip, wp_salt( 'secure_auth' ) );
	}

	public static function sanitize_url( $url ) {
		$url = esc_url_raw( (string) $url );
		return strlen( $url ) > 2000 ? substr( $url, 0, 2000 ) : $url;
	}

	public static function sanitize_tracking_url( $url ) {
		$url = self::sanitize_url( $url );
		if ( '' === $url ) {
			return '';
		}
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return $url;
		}
		$query = array();
		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $query );
			foreach ( array_keys( $query ) as $key ) {
				if ( preg_match( '/pass|password|token|secret|nonce|auth|session|code|email|phone/i', (string) $key ) ) {
					unset( $query[ $key ] );
				}
			}
		}
		$clean = ( isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : 'https://' ) . $parts['host'];
		if ( isset( $parts['port'] ) ) {
			$clean .= ':' . absint( $parts['port'] );
		}
		$clean .= isset( $parts['path'] ) ? $parts['path'] : '/';
		if ( $query ) {
			$clean .= '?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
		}
		return esc_url_raw( $clean );
	}

	public static function rate_limit( $bucket, $limit, $window = 60 ) {
		$key = 'shcd_tiamis_rl_' . md5( $bucket . '|' . self::visitor_ip() );
		$current = get_transient( $key );
		$current = is_array( $current ) ? $current : array( 'count' => 0 );
		if ( (int) $current['count'] >= (int) $limit ) {
			return false;
		}
		$current['count']++;
		set_transient( $key, $current, max( 10, (int) $window ) );
		return true;
	}

	public static function create_or_get_conversation( $token, $data = array() ) {
		global $wpdb;
		$table = Tiamis_Chat_DB::table( 'conversations' );
		$token = is_string( $token ) ? trim( $token ) : '';
		$hash  = '' !== $token ? self::token_hash( $token ) : '';
		$row   = '' !== $hash ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE session_hash = %s LIMIT 1', $table, $hash ) ) : null;

		if ( $row ) {
			$settings = self::settings();
			// If consent has become mandatory, do not refresh page/profile metadata for
			// an older unconsented session until the visitor explicitly accepts it.
			if ( '1' !== $settings['consent_required'] || 1 === (int) $row->consent || ! empty( $data['consent'] ) ) {
				self::update_visitor_profile( $row->id, $data );
			}
			return array( 'token' => $token, 'conversation' => self::get_conversation( (int) $row->id ) );
		}

		$token = self::random_token();
		$hash  = self::token_hash( $token );
		$settings = self::settings();
		$now = self::now();
		$insert = array(
			'public_id'       => wp_generate_uuid4(),
			'session_hash'    => $hash,
			'status'          => 'open',
			'visitor_name'    => isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '',
			'visitor_email'   => isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '',
			'visitor_phone'   => isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '',
			'ip_value'        => self::stored_ip( $settings['ip_mode'] ),
			'ip_mode'         => in_array( $settings['ip_mode'], array( 'none', 'hash', 'full' ), true ) ? $settings['ip_mode'] : 'hash',
			'user_agent'      => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 1000 ) : '',
			'page_url'        => isset( $data['page_url'] ) ? self::sanitize_tracking_url( $data['page_url'] ) : '',
			'referrer'        => isset( $data['referrer'] ) ? self::sanitize_url( $data['referrer'] ) : '',
			'utm_source'      => isset( $data['utm_source'] ) ? sanitize_text_field( $data['utm_source'] ) : '',
			'utm_medium'      => isset( $data['utm_medium'] ) ? sanitize_text_field( $data['utm_medium'] ) : '',
			'utm_campaign'    => isset( $data['utm_campaign'] ) ? sanitize_text_field( $data['utm_campaign'] ) : '',
			'consent'         => empty( $data['consent'] ) ? 0 : 1,
			'last_message_at' => $now,
			'created_at'      => $now,
			'updated_at'      => $now,
		);
		$wpdb->insert( $table, $insert );
		$id = (int) $wpdb->insert_id;
		if ( ! $id ) {
			return new WP_Error( 'shcd_tiamis_db_error', 'ایجاد گفتگو ممکن نشد.', array( 'status' => 500 ) );
		}

		self::add_message( $id, 'system', $settings['welcome_message'], 'system', $settings['agent_name'] );
		return array( 'token' => $token, 'conversation' => self::get_conversation( $id ) );
	}

	public static function create_or_get_telegram_conversation( $chat_id, $profile = array() ) {
		global $wpdb;
		$table = Tiamis_Chat_DB::table( 'conversations' );
		$chat_id = sanitize_text_field( (string) $chat_id );
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE visitor_telegram_chat_id = %s ORDER BY id DESC LIMIT 1', $table, $chat_id ) );
		if ( $row ) {
			$update = array(
				'visitor_name' => isset( $profile['name'] ) ? sanitize_text_field( $profile['name'] ) : $row->visitor_name,
				'visitor_telegram_username' => isset( $profile['username'] ) ? sanitize_text_field( $profile['username'] ) : $row->visitor_telegram_username,
				'updated_at' => self::now(),
			);
			$wpdb->update( $table, $update, array( 'id' => (int) $row->id ) );
			return self::get_conversation( (int) $row->id );
		}
		$now = self::now();
		$token = self::random_token();
		$wpdb->insert(
			$table,
			array(
				'public_id' => wp_generate_uuid4(),
				'session_hash' => self::token_hash( $token ),
				'status' => 'open',
				'visitor_name' => isset( $profile['name'] ) ? sanitize_text_field( $profile['name'] ) : 'کاربر تلگرام',
				'visitor_telegram_chat_id' => $chat_id,
				'visitor_telegram_username' => isset( $profile['username'] ) ? sanitize_text_field( $profile['username'] ) : '',
				'ip_mode' => 'none',
				'page_url' => 'telegram://chat/' . rawurlencode( $chat_id ),
				'last_message_at' => $now,
				'created_at' => $now,
				'updated_at' => $now,
			)
		);
		$id = (int) $wpdb->insert_id;
		if ( ! $id ) {
			return new WP_Error( 'shcd_tiamis_db_error', 'ایجاد گفتگوی تلگرام ممکن نشد.' );
		}
		$conversation = self::get_conversation( $id );
		return $conversation;
	}

	public static function create_or_get_bale_conversation( $chat_id, $profile = array() ) {
		global $wpdb;
		$table   = Tiamis_Chat_DB::table( 'conversations' );
		$chat_id = sanitize_text_field( (string) $chat_id );
		$row     = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE visitor_bale_chat_id = %s ORDER BY id DESC LIMIT 1', $table, $chat_id ) );
		if ( $row ) {
			$wpdb->update(
				$table,
				array(
					'visitor_name'          => isset( $profile['name'] ) ? sanitize_text_field( $profile['name'] ) : $row->visitor_name,
					'visitor_bale_username' => isset( $profile['username'] ) ? sanitize_text_field( $profile['username'] ) : $row->visitor_bale_username,
					'updated_at'             => self::now(),
				),
				array( 'id' => (int) $row->id )
			);
			return self::get_conversation( (int) $row->id );
		}
		$now   = self::now();
		$token = self::random_token();
		$wpdb->insert(
			$table,
			array(
				'public_id'             => wp_generate_uuid4(),
				'session_hash'          => self::token_hash( $token ),
				'status'                => 'open',
				'visitor_name'          => isset( $profile['name'] ) ? sanitize_text_field( $profile['name'] ) : 'کاربر بله',
				'visitor_bale_chat_id'  => $chat_id,
				'visitor_bale_username' => isset( $profile['username'] ) ? sanitize_text_field( $profile['username'] ) : '',
				'ip_mode'               => 'none',
				'page_url'              => 'bale://chat/' . rawurlencode( $chat_id ),
				'last_message_at'       => $now,
				'created_at'            => $now,
				'updated_at'            => $now,
			)
		);
		$id = (int) $wpdb->insert_id;
		if ( ! $id ) {
			return new WP_Error( 'shcd_tiamis_db_error', 'ایجاد گفتگوی بله ممکن نشد.' );
		}
		$conversation = self::get_conversation( $id );
		return $conversation;
	}

	public static function update_visitor_profile( $conversation_id, $data ) {
		global $wpdb;
		$update = array();
		if ( isset( $data['name'] ) && '' !== trim( (string) $data['name'] ) ) {
			$update['visitor_name'] = sanitize_text_field( $data['name'] );
		}
		if ( isset( $data['email'] ) && is_email( $data['email'] ) ) {
			$update['visitor_email'] = sanitize_email( $data['email'] );
		}
		if ( isset( $data['phone'] ) && '' !== trim( (string) $data['phone'] ) ) {
			$update['visitor_phone'] = sanitize_text_field( $data['phone'] );
		}
		if ( isset( $data['page_url'] ) ) {
			$update['page_url'] = self::sanitize_tracking_url( $data['page_url'] );
		}
		if ( isset( $data['consent'] ) ) {
			$update['consent'] = empty( $data['consent'] ) ? 0 : 1;
		}
		if ( $update ) {
			$update['updated_at'] = self::now();
			$wpdb->update( Tiamis_Chat_DB::table( 'conversations' ), $update, array( 'id' => (int) $conversation_id ) );
		}
	}

	public static function get_conversation( $id ) {
		global $wpdb;
		$table = Tiamis_Chat_DB::table( 'conversations' );
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d LIMIT 1', $table, (int) $id ) );
	}

	public static function get_conversation_by_public_id( $public_id ) {
		global $wpdb;
		$table = Tiamis_Chat_DB::table( 'conversations' );
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE public_id = %s LIMIT 1', $table, sanitize_text_field( $public_id ) ) );
	}

	public static function get_conversation_by_token( $token ) {
		global $wpdb;
		if ( ! is_string( $token ) || strlen( $token ) < 30 ) {
			return null;
		}
		$table = Tiamis_Chat_DB::table( 'conversations' );
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE session_hash = %s LIMIT 1', $table, self::token_hash( $token ) ) );
	}

	public static function public_conversation_data( $conversation ) {
		if ( ! $conversation ) {
			return array();
		}
		return array(
			'id'               => (int) $conversation->id,
			'public_id'        => $conversation->public_id,
			'status'           => $conversation->status,
			'visitor_name'     => $conversation->visitor_name,
			'visitor_email'    => $conversation->visitor_email,
			'visitor_phone'    => $conversation->visitor_phone,
			'telegram_chat_id'  => $conversation->visitor_telegram_chat_id,
			'telegram_username' => $conversation->visitor_telegram_username,
			'bale_chat_id'       => isset( $conversation->visitor_bale_chat_id ) ? $conversation->visitor_bale_chat_id : '',
			'bale_username'      => isset( $conversation->visitor_bale_username ) ? $conversation->visitor_bale_username : '',
			'page_url'         => $conversation->page_url,
			'created_at'       => $conversation->created_at,
			'last_message_at'  => $conversation->last_message_at,
			'unread_admin'     => (int) $conversation->unread_admin,
			'unread_visitor'   => (int) $conversation->unread_visitor,
			'blocked'          => self::conversation_is_blocked( $conversation ),
			'rating'           => ( function() use ( $conversation ) { $row = self::get_rating( $conversation->id ); return $row ? (int) $row->rating : 0; } )(),
		);
	}

	public static function add_message( $conversation_id, $sender, $body, $source = 'website', $sender_name = '', $meta = array(), $client_message_id = '' ) {
		global $wpdb;
		$messages_table      = Tiamis_Chat_DB::table( 'messages' );
		$conversations_table = Tiamis_Chat_DB::table( 'conversations' );
		$body = trim( wp_strip_all_tags( (string) $body ) );
		$client_message_id = substr( preg_replace( '/[^a-zA-Z0-9._:-]/', '', (string) $client_message_id ), 0, 64 );
		if ( '' !== $client_message_id ) {
			$existing = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE conversation_id = %d AND client_message_id = %s LIMIT 1',
					$messages_table,
					(int) $conversation_id,
					$client_message_id
				)
			);
			if ( $existing ) {
				$existing->_tiamis_duplicate = true;
				return $existing;
			}
		}
		if ( '' === $body ) {
			return new WP_Error( 'shcd_tiamis_empty_message', 'متن پیام خالی است.', array( 'status' => 400 ) );
		}
		$message_limit = 4000;
		$body = function_exists( 'mb_substr' ) ? mb_substr( $body, 0, $message_limit ) : substr( $body, 0, $message_limit );
		$allowed_senders = array( 'visitor', 'admin', 'ai', 'system' );
		$sender = in_array( $sender, $allowed_senders, true ) ? $sender : 'system';
		$now = self::now();
		$wpdb->insert(
			$messages_table,
			array(
				'conversation_id' => (int) $conversation_id,
				'sender'           => $sender,
				'sender_name'      => sanitize_text_field( $sender_name ),
				'body'             => $body,
				'source'           => sanitize_key( $source ),
				'meta'             => maybe_serialize( is_array( $meta ) ? $meta : array() ),
				'client_message_id'=> $client_message_id,
				'reply_to_id'      => absint( $meta['reply_to_id'] ?? 0 ),
				'attachment_id'    => absint( $meta['attachment_id'] ?? 0 ),
				'created_at'       => $now,
			)
		);
		$message_id = (int) $wpdb->insert_id;
		if ( ! $message_id && '' !== $client_message_id ) {
			// A concurrent request may have inserted the same client message first.
			$existing = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE conversation_id = %d AND client_message_id = %s LIMIT 1',
					$messages_table,
					(int) $conversation_id,
					$client_message_id
				)
			);
			if ( $existing ) {
				$existing->_tiamis_duplicate = true;
				return $existing;
			}
		}
		if ( ! $message_id ) {
			return new WP_Error( 'shcd_tiamis_db_error', 'ذخیره پیام ممکن نشد.', array( 'status' => 500 ) );
		}
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE %i SET last_message_at = %s, updated_at = %s, unread_admin = unread_admin + %d, unread_visitor = unread_visitor + %d, status = IF(%d = 1, 'open', status) WHERE id = %d",
				$conversations_table,
				$now,
				$now,
				'visitor' === $sender ? 1 : 0,
				in_array( $sender, array( 'admin', 'ai' ), true ) ? 1 : 0,
				'visitor' === $sender ? 1 : 0,
				(int) $conversation_id
			)
		);
		$message = self::get_message( $message_id );
		$conversation = self::get_conversation( $conversation_id );
		do_action( 'shcd_tiamis_message_created', $message, $conversation );
		return $message;
	}

	public static function get_message( $id ) {
		global $wpdb;
		$table = Tiamis_Chat_DB::table( 'messages' );
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d LIMIT 1', $table, (int) $id ) );
	}

	public static function get_messages( $conversation_id, $after = 0, $limit = 100 ) {
		global $wpdb;
		$table = Tiamis_Chat_DB::table( 'messages' );
		$limit = min( 200, max( 1, (int) $limit ) );
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE conversation_id = %d AND id > %d ORDER BY id ASC LIMIT %d', $table, (int) $conversation_id, (int) $after, $limit ) );
	}

	public static function public_message_data( $message ) {
		$meta = maybe_unserialize( isset( $message->meta ) ? $message->meta : array() );
		$meta = is_array( $meta ) ? $meta : array();
		$profile = array( 'name' => '', 'role' => '', 'photo' => '', 'index' => 0 );
		if ( 'admin' === $message->sender ) {
			$profile = self::agent_profile(
				$message->sender_name,
				isset( $meta['agent_index'] ) ? absint( $meta['agent_index'] ) : null
			);
			if ( ! empty( $meta['agent_name'] ) ) {
				$profile['name'] = sanitize_text_field( $meta['agent_name'] );
			}
			if ( ! empty( $meta['agent_role'] ) ) {
				$profile['role'] = sanitize_text_field( $meta['agent_role'] );
			}
			if ( ! empty( $meta['agent_photo'] ) ) {
				$profile['photo'] = esc_url_raw( $meta['agent_photo'] );
			}
		}
		$attachment = ! empty( $message->attachment_id ) ? self::get_attachment( (int) $message->attachment_id ) : null;
		return array(
			'id'           => (int) $message->id,
			'sender'       => $message->sender,
			'sender_name'  => $message->sender_name,
			'sender_role'  => $profile['role'],
			'sender_photo' => $profile['photo'],
			'agent_index'  => absint( $profile['index'] ),
			'body'         => ! empty( $message->deleted_at ) ? 'این پیام حذف شده است.' : $message->body,
			'client_message_id' => isset( $message->client_message_id ) ? $message->client_message_id : '',
			'reply_to_id'  => isset( $message->reply_to_id ) ? (int) $message->reply_to_id : 0,
			'edited_at'    => isset( $message->edited_at ) ? $message->edited_at : null,
			'deleted_at'   => isset( $message->deleted_at ) ? $message->deleted_at : null,
			'attachment'   => $attachment ? array( 'id' => (int) $attachment->id, 'name' => $attachment->file_name, 'url' => $attachment->file_url, 'mime' => $attachment->mime_type, 'size' => (int) $attachment->file_size ) : null,
			'reactions'    => self::get_reactions( (int) $message->id ),
			'source'       => $message->source,
			'seen_at'      => $message->seen_at,
			'is_seen'      => ! empty( $message->seen_at ),
			'created_at'   => $message->created_at,
		);
	}

	public static function visitor_seen_through( $conversation_id ) {
		global $wpdb;
		$table = Tiamis_Chat_DB::table( 'messages' );
		return absint( $wpdb->get_var( $wpdb->prepare( "SELECT MAX(id) FROM %i WHERE conversation_id = %d AND sender = 'visitor' AND seen_at IS NOT NULL", $table, (int) $conversation_id ) ) );
	}

	private static function typing_key( $conversation_id, $actor ) {
		$actor = in_array( $actor, array( 'visitor', 'operator' ), true ) ? $actor : 'operator';
		return 'shcd_tiamis_typing_' . $actor . '_' . absint( $conversation_id );
	}

	public static function set_typing( $conversation_id, $actor, $typing = true, $ttl = 8, $data = array() ) {
		$key = self::typing_key( $conversation_id, $actor );
		if ( $typing ) {
			$value = array( 'time' => time() );
			if ( is_array( $data ) ) {
				$value = array_merge( $value, $data );
			}
			set_transient( $key, $value, max( 3, min( 30, absint( $ttl ) ) ) );
		} else {
			delete_transient( $key );
		}
	}

	public static function typing_data( $conversation_id, $actor ) {
		$value = get_transient( self::typing_key( $conversation_id, $actor ) );
		return is_array( $value ) ? $value : array();
	}

	public static function is_typing( $conversation_id, $actor ) {
		return false !== get_transient( self::typing_key( $conversation_id, $actor ) );
	}

	public static function touch_operator_presence() {
		$user = wp_get_current_user();
		set_transient(
			'shcd_tiamis_operator_presence',
			array( 'user_id' => (int) $user->ID, 'name' => sanitize_text_field( $user->display_name ), 'time' => time() ),
			3 * MINUTE_IN_SECONDS
		);
	}

	public static function operator_is_online() {
		$settings = self::settings();
		$mode = isset( $settings['operator_presence_mode'] ) ? $settings['operator_presence_mode'] : 'auto';
		if ( 'online' === $mode ) {
			return true;
		}
		if ( 'offline' === $mode ) {
			return false;
		}
		$presence = get_transient( 'shcd_tiamis_operator_presence' );
		$timeout = max( 30, min( 600, absint( $settings['operator_presence_timeout'] ?? 90 ) ) );
		return is_array( $presence ) && ! empty( $presence['time'] ) && ( time() - absint( $presence['time'] ) ) <= $timeout;
	}

	public static function mark_seen_by_visitor( $conversation_id ) {
		global $wpdb;
		$now = self::now();
		$messages_table      = Tiamis_Chat_DB::table( 'messages' );
		$conversations_table = Tiamis_Chat_DB::table( 'conversations' );
		$wpdb->query( $wpdb->prepare( "UPDATE %i SET seen_at = %s WHERE conversation_id = %d AND sender IN ('admin','ai') AND seen_at IS NULL", $messages_table, $now, (int) $conversation_id ) );
		$wpdb->update( $conversations_table, array( 'unread_visitor' => 0 ), array( 'id' => (int) $conversation_id ) );
	}

	public static function mark_seen_by_admin( $conversation_id ) {
		global $wpdb;
		$now = self::now();
		$messages_table      = Tiamis_Chat_DB::table( 'messages' );
		$conversations_table = Tiamis_Chat_DB::table( 'conversations' );
		$wpdb->query( $wpdb->prepare( "UPDATE %i SET seen_at = %s WHERE conversation_id = %d AND sender = 'visitor' AND seen_at IS NULL", $messages_table, $now, (int) $conversation_id ) );
		$wpdb->update( $conversations_table, array( 'unread_admin' => 0 ), array( 'id' => (int) $conversation_id ) );
	}

	public static function current_block_record() {
		global $wpdb;
		$table = Tiamis_Chat_DB::table( 'blocks' );
		$full  = self::stored_ip( 'full' );
		$hash  = self::stored_ip( 'hash' );
		if ( '' === $full && '' === $hash ) {
			return null;
		}
		$now = self::now();
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM %i WHERE ((ip_mode = 'full' AND ip_value = %s) OR (ip_mode = 'hash' AND ip_value = %s)) AND (expires_at IS NULL OR expires_at = '0000-00-00 00:00:00' OR expires_at > %s) ORDER BY id DESC LIMIT 1",
				$table,
				$full,
				$hash,
				$now
			)
		);
	}

	public static function is_current_visitor_blocked() {
		return (bool) self::current_block_record();
	}

	public static function block_conversation_ip( $conversation_id, $reason = '' ) {
		global $wpdb;
		$conversation = self::get_conversation( $conversation_id );
		if ( ! $conversation || empty( $conversation->ip_value ) || 'none' === $conversation->ip_mode ) {
			return new WP_Error( 'shcd_tiamis_no_ip', 'برای این گفتگو IP قابل‌بلاک‌کردن ثبت نشده است.' );
		}
		$data = array(
			'ip_value'   => sanitize_text_field( $conversation->ip_value ),
			'ip_mode'    => in_array( $conversation->ip_mode, array( 'full', 'hash' ), true ) ? $conversation->ip_mode : 'hash',
			'reason'     => sanitize_text_field( $reason ),
			'created_by' => get_current_user_id(),
			'created_at' => self::now(),
		);
		$result = $wpdb->replace( Tiamis_Chat_DB::table( 'blocks' ), $data );
		if ( false === $result ) {
			return new WP_Error( 'shcd_tiamis_block_failed', 'بلاک‌کردن IP انجام نشد.' );
		}
		return true;
	}

	public static function unblock_conversation_ip( $conversation_id ) {
		global $wpdb;
		$conversation = self::get_conversation( $conversation_id );
		if ( ! $conversation || empty( $conversation->ip_value ) ) {
			return false;
		}
		return false !== $wpdb->delete(
			Tiamis_Chat_DB::table( 'blocks' ),
			array( 'ip_value' => $conversation->ip_value, 'ip_mode' => $conversation->ip_mode )
		);
	}

	public static function conversation_is_blocked( $conversation ) {
		global $wpdb;
		if ( ! $conversation || empty( $conversation->ip_value ) ) {
			return false;
		}
		$table = Tiamis_Chat_DB::table( 'blocks' );
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM %i WHERE ip_value = %s AND ip_mode = %s AND (expires_at IS NULL OR expires_at = '0000-00-00 00:00:00' OR expires_at > %s) LIMIT 1",
				$table,
				$conversation->ip_value,
				$conversation->ip_mode,
				self::now()
			)
		);
	}

	public static function add_report( $conversation_id, $message_id, $reason = 'inappropriate', $details = '' ) {
		global $wpdb;
		$message = self::get_message( $message_id );
		if ( ! $message || (int) $message->conversation_id !== (int) $conversation_id || ! in_array( $message->sender, array( 'admin', 'ai' ), true ) ) {
			return new WP_Error( 'shcd_tiamis_report_invalid', 'این پیام برای گزارش‌کردن معتبر نیست.', array( 'status' => 400 ) );
		}
		$allowed = array( 'inappropriate', 'incorrect', 'spam', 'other' );
		$reason  = in_array( $reason, $allowed, true ) ? $reason : 'other';
		$result  = $wpdb->replace(
			Tiamis_Chat_DB::table( 'reports' ),
			array(
				'conversation_id' => (int) $conversation_id,
				'message_id'      => (int) $message_id,
				'reason'          => $reason,
				'details'         => wp_strip_all_tags( (string) $details ),
				'status'          => 'new',
				'created_at'      => self::now(),
			)
		);
		return false === $result ? new WP_Error( 'shcd_tiamis_report_failed', 'ثبت گزارش انجام نشد.', array( 'status' => 500 ) ) : true;
	}

	public static function get_reports( $conversation_id ) {
		global $wpdb;
		$table = Tiamis_Chat_DB::table( 'reports' );
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE conversation_id = %d ORDER BY id DESC', $table, (int) $conversation_id ) );
	}

	public static function add_note( $conversation_id, $note, $user_id = 0 ) {
		global $wpdb;
		$note = trim( wp_strip_all_tags( (string) $note ) );
		if ( '' === $note ) {
			return new WP_Error( 'shcd_tiamis_note_empty', 'متن یادداشت خالی است.' );
		}
		$now = self::now();
		$result = $wpdb->insert(
			Tiamis_Chat_DB::table( 'notes' ),
			array(
				'conversation_id' => (int) $conversation_id,
				'wp_user_id'      => (int) $user_id,
				'note'            => function_exists( 'mb_substr' ) ? mb_substr( $note, 0, 4000 ) : substr( $note, 0, 4000 ),
				'created_at'      => $now,
				'updated_at'      => $now,
			)
		);
		return false === $result ? new WP_Error( 'shcd_tiamis_note_failed', 'ذخیره یادداشت انجام نشد.' ) : (int) $wpdb->insert_id;
	}

	public static function get_notes( $conversation_id ) {
		global $wpdb;
		$table = Tiamis_Chat_DB::table( 'notes' );
		$users = $wpdb->users;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT n.*, u.display_name FROM %i n LEFT JOIN %i u ON u.ID = n.wp_user_id WHERE n.conversation_id = %d ORDER BY n.id DESC LIMIT 100',
				$table,
				$users,
				(int) $conversation_id
			)
		);
	}

	public static function save_rating( $conversation_id, $rating, $label = '', $comment = '' ) {
		global $wpdb;
		$rating = max( 1, min( 4, absint( $rating ) ) );
		$result = $wpdb->replace(
			Tiamis_Chat_DB::table( 'ratings' ),
			array(
				'conversation_id' => (int) $conversation_id,
				'rating'          => $rating,
				'label'           => sanitize_text_field( $label ),
				'comment'         => wp_strip_all_tags( (string) $comment ),
				'created_at'      => self::now(),
			)
		);
		return false === $result ? new WP_Error( 'shcd_tiamis_rating_failed', 'ثبت امتیاز انجام نشد.', array( 'status' => 500 ) ) : true;
	}

	public static function get_rating( $conversation_id ) {
		global $wpdb;
		$table = Tiamis_Chat_DB::table( 'ratings' );
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE conversation_id = %d LIMIT 1', $table, (int) $conversation_id ) );
	}

	public static function delete_conversation( $conversation_id ) {
		global $wpdb;
		$id = absint( $conversation_id );
		if ( ! $id ) {
			return false;
		}
		foreach ( array( 'reactions', 'attachments', 'tasks', 'conversation_tags', 'audit', 'reports', 'notes', 'ratings', 'messages', 'telegram_map', 'bale_map' ) as $key ) {
			$wpdb->delete( Tiamis_Chat_DB::table( $key ), array( 'conversation_id' => $id ) );
		}
		return false !== $wpdb->delete( Tiamis_Chat_DB::table( 'conversations' ), array( 'id' => $id ) );
	}


	public static function get_attachment( $id ) {
		global $wpdb;
		$table = Tiamis_Chat_DB::table( 'attachments' );
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d LIMIT 1', $table, absint( $id ) ) );
	}

	public static function create_attachment( $conversation_id, $file, $actor = 'visitor' ) {
		global $wpdb;
		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new WP_Error( 'tiamis_upload_missing', 'فایلی برای بارگذاری دریافت نشد.' );
		}
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$allowed = array(
			'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif',
			'pdf' => 'application/pdf', 'txt' => 'text/plain', 'zip' => 'application/zip',
			'mp3|m4a' => 'audio/mpeg', 'ogg|oga' => 'audio/ogg', 'wav' => 'audio/wav',
			'mp4|m4v' => 'video/mp4', 'webm' => 'video/webm',
		);
		if ( (int) $file['size'] > 10 * MB_IN_BYTES ) {
			return new WP_Error( 'tiamis_upload_large', 'حجم فایل بیشتر از ۱۰ مگابایت است.' );
		}
		$uploaded = wp_handle_upload( $file, array( 'test_form' => false, 'mimes' => $allowed ) );
		if ( isset( $uploaded['error'] ) ) {
			return new WP_Error( 'tiamis_upload_failed', $uploaded['error'] );
		}
		$wpdb->insert(
			Tiamis_Chat_DB::table( 'attachments' ),
			array(
				'conversation_id' => absint( $conversation_id ),
				'file_name'       => sanitize_file_name( basename( $uploaded['file'] ) ),
				'mime_type'       => sanitize_mime_type( $uploaded['type'] ),
				'file_size'       => absint( filesize( $uploaded['file'] ) ),
				'file_url'        => esc_url_raw( $uploaded['url'] ),
				'created_by'      => in_array( $actor, array( 'visitor', 'admin' ), true ) ? $actor : 'visitor',
				'created_at'      => self::now(),
			)
		);
		$id = (int) $wpdb->insert_id;
		return $id ? self::get_attachment( $id ) : new WP_Error( 'tiamis_upload_db', 'اطلاعات فایل ذخیره نشد.' );
	}

	public static function set_attachment_message( $attachment_id, $message_id ) {
		global $wpdb;
		return false !== $wpdb->update( Tiamis_Chat_DB::table( 'attachments' ), array( 'message_id' => absint( $message_id ) ), array( 'id' => absint( $attachment_id ) ) );
	}

	public static function react_message( $conversation_id, $message_id, $actor, $actor_id, $reaction ) {
		global $wpdb;
		$allowed = array( '👍', '❤️', '😂', '😮', '😢', '🙏' );
		$reaction = in_array( $reaction, $allowed, true ) ? $reaction : '👍';
		$message = self::get_message( $message_id );
		if ( ! $message || (int) $message->conversation_id !== absint( $conversation_id ) ) {
			return new WP_Error( 'tiamis_reaction_invalid', 'پیام معتبر نیست.' );
		}
		$wpdb->replace( Tiamis_Chat_DB::table( 'reactions' ), array( 'conversation_id' => absint( $conversation_id ), 'message_id' => absint( $message_id ), 'actor' => sanitize_key( $actor ), 'actor_id' => sanitize_text_field( $actor_id ), 'reaction' => $reaction, 'created_at' => self::now() ) );
		return self::get_reactions( $message_id );
	}

	public static function get_reactions( $message_id ) {
		global $wpdb;
		$table = Tiamis_Chat_DB::table( 'reactions' );
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT reaction, COUNT(*) total FROM %i WHERE message_id = %d GROUP BY reaction ORDER BY total DESC', $table, absint( $message_id ) ) );
		return array_map( static function ( $row ) { return array( 'reaction' => $row->reaction, 'count' => (int) $row->total ); }, $rows ?: array() );
	}

	public static function edit_message( $conversation_id, $message_id, $body, $actor = 'visitor' ) {
		global $wpdb;
		$message = self::get_message( $message_id );
		if ( ! $message || (int) $message->conversation_id !== absint( $conversation_id ) || $message->sender !== $actor || ! empty( $message->deleted_at ) ) {
			return new WP_Error( 'tiamis_edit_forbidden', 'این پیام قابل ویرایش نیست.' );
		}
		if ( 'visitor' === $actor && strtotime( $message->created_at ) < time() - 15 * MINUTE_IN_SECONDS ) {
			return new WP_Error( 'tiamis_edit_expired', 'زمان ویرایش این پیام گذشته است.' );
		}
		$body = trim( wp_strip_all_tags( (string) $body ) );
		if ( '' === $body ) return new WP_Error( 'tiamis_edit_empty', 'متن پیام خالی است.' );
		$wpdb->update( Tiamis_Chat_DB::table( 'messages' ), array( 'body' => function_exists( 'mb_substr' ) ? mb_substr( $body, 0, 4000 ) : substr( $body, 0, 4000 ), 'edited_at' => self::now() ), array( 'id' => absint( $message_id ) ) );
		return self::get_message( $message_id );
	}

	public static function delete_message_for_all( $conversation_id, $message_id, $actor = 'visitor' ) {
		global $wpdb;
		$message = self::get_message( $message_id );
		if ( ! $message || (int) $message->conversation_id !== absint( $conversation_id ) || ( $message->sender !== $actor && ! current_user_can( 'manage_options' ) ) ) {
			return new WP_Error( 'tiamis_delete_forbidden', 'این پیام قابل حذف نیست.' );
		}
		$wpdb->update( Tiamis_Chat_DB::table( 'messages' ), array( 'body' => '', 'deleted_at' => self::now() ), array( 'id' => absint( $message_id ) ) );
		return self::get_message( $message_id );
	}

	public static function ensure_resume_code( $conversation_id ) {
		global $wpdb;
		$conversation = self::get_conversation( $conversation_id );
		if ( ! $conversation ) return '';
		if ( ! empty( $conversation->resume_code ) ) return $conversation->resume_code;
		$code = strtoupper( substr( preg_replace( '/[^A-Z0-9]/', '', base64_encode( random_bytes( 12 ) ) ), 0, 12 ) );
		$wpdb->update( Tiamis_Chat_DB::table( 'conversations' ), array( 'resume_code' => $code ), array( 'id' => absint( $conversation_id ) ) );
		return $code;
	}

	public static function claim_by_resume_code( $code ) {
		global $wpdb;
		$code = strtoupper( preg_replace( '/[^A-Z0-9]/', '', (string) $code ) );
		if ( strlen( $code ) < 8 ) return new WP_Error( 'tiamis_resume_invalid', 'کد ادامه گفتگو معتبر نیست.' );
		$table = Tiamis_Chat_DB::table( 'conversations' );
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE resume_code = %s LIMIT 1', $table, $code ) );
		if ( ! $row ) return new WP_Error( 'tiamis_resume_missing', 'گفتگویی با این کد پیدا نشد.' );
		$token = self::random_token();
		$wpdb->update( $table, array( 'session_hash' => self::token_hash( $token ), 'updated_at' => self::now() ), array( 'id' => (int) $row->id ) );
		return array( 'token' => $token, 'conversation' => self::get_conversation( $row->id ) );
	}

	public static function queue_outbound( $message_id, $direction ) {
		$message_id = absint( $message_id );
		$direction  = in_array( $direction, array( 'visitor', 'operator' ), true ) ? $direction : 'visitor';
		if ( ! $message_id ) {
			return;
		}
		$args = array( $message_id, $direction );
		if ( ! wp_next_scheduled( 'shcd_tiamis_process_outbound', $args ) ) {
			wp_schedule_single_event( time() + 1, 'shcd_tiamis_process_outbound', $args );
		}
		if ( function_exists( 'spawn_cron' ) ) {
			spawn_cron( time() );
		}
	}

	public static function schedule_cleanup() {
		if ( ! wp_next_scheduled( 'shcd_tiamis_daily_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'shcd_tiamis_daily_cleanup' );
		}
	}

	public static function cleanup_old_data() {
		global $wpdb;
		$settings = self::settings();
		$days = min( 3650, max( 7, absint( $settings['retention_days'] ) ) );
		$cutoff = ( new DateTimeImmutable( 'now', wp_timezone() ) )->modify( '-' . $days . ' days' )->format( 'Y-m-d H:i:s' );
		$conversations = Tiamis_Chat_DB::table( 'conversations' );
		$ids           = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM %i WHERE updated_at < %s LIMIT 1000', $conversations, $cutoff ) );
		if ( $ids ) {
			foreach ( array_map( 'absint', $ids ) as $conversation_id ) {
				self::delete_conversation( $conversation_id );
			}
		}
	}

	public static function register_privacy_exporter( $exporters ) {
		$exporters['shcd-tiamis'] = array(
			'exporter_friendly_name' => 'Tiamis Chat',
			'callback' => array( __CLASS__, 'privacy_export' ),
		);
		return $exporters;
	}

	public static function register_privacy_eraser( $erasers ) {
		$erasers['shcd-tiamis'] = array(
			'eraser_friendly_name' => 'Tiamis Chat',
			'callback' => array( __CLASS__, 'privacy_erase' ),
		);
		return $erasers;
	}

	public static function privacy_export( $email_address, $page = 1 ) {
		global $wpdb;
		$email = sanitize_email( $email_address );
		$page = max( 1, absint( $page ) );
		$limit = 20;
		$offset = ( $page - 1 ) * $limit;
		$table = Tiamis_Chat_DB::table( 'conversations' );
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE visitor_email = %s ORDER BY id ASC LIMIT %d OFFSET %d', $table, $email, $limit, $offset ) );
		$data = array();
		foreach ( $rows as $conversation ) {
			$history = array();
			foreach ( self::get_messages( $conversation->id, 0, 200 ) as $message ) {
				$history[] = '[' . $message->created_at . '] ' . $message->sender . ': ' . $message->body;
			}
			$data[] = array(
				'group_id' => 'shcd-tiamis',
				'group_label' => 'گفتگوهای پشتیبانی Tiamis Chat',
				'item_id' => 'shcd-tiamis-' . $conversation->id,
				'data' => array(
					array( 'name' => 'شناسه گفتگو', 'value' => $conversation->public_id ),
					array( 'name' => 'نام', 'value' => $conversation->visitor_name ),
					array( 'name' => 'ایمیل', 'value' => $conversation->visitor_email ),
					array( 'name' => 'شماره تماس', 'value' => $conversation->visitor_phone ),
					array( 'name' => 'IP ذخیره‌شده', 'value' => $conversation->ip_value ),
					array( 'name' => 'صفحه شروع', 'value' => $conversation->page_url ),
					array( 'name' => 'تاریخ ایجاد', 'value' => $conversation->created_at ),
					array( 'name' => 'پیام‌ها', 'value' => implode( "\n", $history ) ),
				),
			);
		}
		return array( 'data' => $data, 'done' => count( $rows ) < $limit );
	}

	public static function privacy_erase( $email_address, $page = 1 ) {
		global $wpdb;
		$email = sanitize_email( $email_address );
		$conversations = Tiamis_Chat_DB::table( 'conversations' );
		$ids           = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM %i WHERE visitor_email = %s LIMIT 100', $conversations, $email ) );
		if ( $ids ) {
			foreach ( array_map( 'absint', $ids ) as $conversation_id ) {
				self::delete_conversation( $conversation_id );
			}
		}
		$remaining = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE visitor_email = %s', $conversations, $email ) );
		return array(
			'items_removed' => ! empty( $ids ),
			'items_retained' => false,
			'messages' => array(),
			'done' => 0 === $remaining,
		);
	}

	public static function privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		wp_add_privacy_policy_content(
			'Tiamis Chat',
			'<p>این افزونه ممکن است متن گفتگو، نام، ایمیل، شماره تماس، صفحه ورود، اطلاعات فنی مرورگر و بر اساس تنظیم مدیر سایت، IP کامل یا IP مستعارسازی‌شده با HMAC را برای ارائه پشتیبانی ذخیره کند. داده‌ها پس از مدت نگهداری تعیین‌شده توسط مدیر سایت پاک می‌شوند.</p>' .
			'<p>در صورت فعال‌سازی اتصال ربات‌ها، پیام و داده‌های نمایه‌ای انتخاب‌شده برای اپراتورهای تعیین‌شده از طریق Telegram Bot API یا Bale Bot API ارسال می‌شود. در صورت فعال‌سازی هوش مصنوعی، بخش اخیر تاریخچه گفتگو و دستور سیستم به endpoint انتخابی مدیر سایت ارسال می‌شود. در صورت فعال‌سازی OneSignal، شناسه عمومی گفتگو و اشتراک Push مرورگر برای ارسال اعلان پردازش می‌شود؛ متن کامل پاسخ در اعلان پایدار قرار نمی‌گیرد.</p>'
		);
	}

}
