<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tiamis_Chat_REST {
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'wp_ajax_nopriv_shcd_tiamis_public', array( __CLASS__, 'ajax_public' ) );
		add_action( 'wp_ajax_shcd_tiamis_public', array( __CLASS__, 'ajax_public' ) );
	}

	public static function register_routes() {
		register_rest_route(
			'shcd-tiamis/v1',
			'/session',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'session' ),
				'permission_callback' => array( __CLASS__, 'public_entry_permission' ),
			)
		);
		register_rest_route(
			'shcd-tiamis/v1',
			'/messages',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_messages' ),
					'permission_callback' => array( __CLASS__, 'conversation_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'post_message' ),
					'permission_callback' => array( __CLASS__, 'conversation_permission' ),
				),
			)
		);
		register_rest_route(
			'shcd-tiamis/v1',
			'/messages/(?P<id>\\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( __CLASS__, 'edit_message' ),
					'permission_callback' => array( __CLASS__, 'conversation_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( __CLASS__, 'delete_message' ),
					'permission_callback' => array( __CLASS__, 'conversation_permission' ),
				),
			)
		);
		register_rest_route(
			'shcd-tiamis/v1',
			'/sync',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'sync' ),
				'permission_callback' => array( __CLASS__, 'conversation_permission' ),
			)
		);
		register_rest_route(
			'shcd-tiamis/v1',
			'/reaction',
			array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'reaction' ), 'permission_callback' => array( __CLASS__, 'conversation_permission' ) )
		);
		register_rest_route(
			'shcd-tiamis/v1',
			'/upload',
			array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'upload' ), 'permission_callback' => array( __CLASS__, 'conversation_permission' ) )
		);
		register_rest_route(
			'shcd-tiamis/v1',
			'/resume',
			array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'resume' ), 'permission_callback' => array( __CLASS__, 'public_entry_permission' ) )
		);
		register_rest_route(
			'shcd-tiamis/v1',
			'/conversation/code',
			array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'conversation_code' ), 'permission_callback' => array( __CLASS__, 'conversation_permission' ) )
		);
		register_rest_route(
			'shcd-tiamis/v1',
			'/typing',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'typing' ),
				'permission_callback' => array( __CLASS__, 'conversation_permission' ),
			)
		);
		register_rest_route(
			'shcd-tiamis/v1',
			'/report',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'report_message' ),
				'permission_callback' => array( __CLASS__, 'conversation_permission' ),
			)
		);
		register_rest_route(
			'shcd-tiamis/v1',
			'/rating',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'save_rating' ),
				'permission_callback' => array( __CLASS__, 'conversation_permission' ),
			)
		);
		register_rest_route(
			'shcd-tiamis/v1',
			'/conversation/end',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'end_conversation' ),
				'permission_callback' => array( __CLASS__, 'conversation_permission' ),
			)
		);
		register_rest_route(
			'shcd-tiamis/v1',
			'/telegram/webhook',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'telegram_webhook' ),
				'permission_callback' => array( __CLASS__, 'telegram_webhook_permission' ),
			)
		);
		register_rest_route(
			'shcd-tiamis/v1',
			'/telegram/poll',
			array(
				'methods'             => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
				'callback'            => array( __CLASS__, 'telegram_poll' ),
				'permission_callback' => array( __CLASS__, 'telegram_poll_permission' ),
			)
		);
		register_rest_route(
			'shcd-tiamis/v1',
			'/bale/webhook',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'bale_webhook' ),
				'permission_callback' => array( __CLASS__, 'bale_webhook_permission' ),
			)
		);
	}

	public static function public_entry_permission( WP_REST_Request $request ) {
		unset( $request );
		$settings = Tiamis_Chat_Core::settings();
		if ( '1' !== (string) ( $settings['enabled'] ?? '0' ) ) {
			return new WP_Error( 'shcd_tiamis_disabled', 'گفتگوی سایت غیرفعال است.', array( 'status' => 403 ) );
		}
		return true;
	}

	public static function conversation_permission( WP_REST_Request $request ) {
		$allowed = self::public_entry_permission( $request );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		$conversation = Tiamis_Chat_Core::get_conversation_by_token( self::token_from_request( $request ) );
		if ( ! $conversation ) {
			return new WP_Error( 'shcd_tiamis_invalid_session', 'نشست گفتگو معتبر نیست.', array( 'status' => 401 ) );
		}
		return true;
	}

	public static function telegram_webhook_permission( WP_REST_Request $request ) {
		$settings = Tiamis_Chat_Core::settings();
		if ( '1' !== (string) ( $settings['telegram_enabled'] ?? '0' ) ) {
			return true;
		}
		$provided = (string) $request->get_header( 'x-telegram-bot-api-secret-token' );
		$expected = (string) ( $settings['telegram_webhook_secret'] ?? '' );
		return self::secret_permission_result( $expected, $provided, 'shcd_tiamis_telegram_secret', 'وب‌هوک تلگرام معتبر نیست.' );
	}

	public static function telegram_poll_permission( WP_REST_Request $request ) {
		$settings = Tiamis_Chat_Core::settings();
		$provided = sanitize_text_field( (string) $request->get_param( 'key' ) );
		$expected = (string) ( $settings['telegram_poll_secret'] ?? '' );
		return self::secret_permission_result( $expected, $provided, 'shcd_tiamis_telegram_poll_secret', 'کلید اجرای دریافت دوره‌ای تلگرام معتبر نیست.' );
	}

	public static function bale_webhook_permission( WP_REST_Request $request ) {
		$settings = Tiamis_Chat_Core::settings();
		if ( '1' !== (string) ( $settings['bale_enabled'] ?? '0' ) ) {
			return true;
		}
		$provided = sanitize_text_field( (string) $request->get_param( 'key' ) );
		$expected = (string) ( $settings['bale_webhook_secret'] ?? '' );
		return self::secret_permission_result( $expected, $provided, 'shcd_tiamis_bale_secret', 'وب‌هوک بله معتبر نیست.' );
	}

	private static function secret_permission_result( $expected, $provided, $code, $message ) {
		if ( '' === $expected || '' === $provided || ! hash_equals( $expected, $provided ) ) {
			return new WP_Error( sanitize_key( $code ), sanitize_text_field( $message ), array( 'status' => 403 ) );
		}
		return true;
	}

	private static function request_data( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		return is_array( $data ) ? $data : $request->get_params();
	}

	private static function token_from_request( WP_REST_Request $request ) {
		$token = $request->get_header( 'x-shcd-tiamis-session' );
		if ( '' === $token ) {
			$token = (string) $request->get_param( 'token' );
		}
		$token = sanitize_text_field( $token );
		return strlen( $token ) > 128 ? substr( $token, 0, 128 ) : $token;
	}

	private static function profile_values( $data, $conversation = null ) {
		return array(
			'name'    => trim( (string) ( isset( $data['name'] ) ? $data['name'] : ( $conversation ? $conversation->visitor_name : '' ) ) ),
			'email'   => trim( (string) ( isset( $data['email'] ) ? $data['email'] : ( $conversation ? $conversation->visitor_email : '' ) ) ),
			'phone'   => trim( (string) ( isset( $data['phone'] ) ? $data['phone'] : ( $conversation ? $conversation->visitor_phone : '' ) ) ),
			'consent' => ! empty( $data['consent'] ) || ( $conversation && 1 === (int) $conversation->consent ),
		);
	}

	private static function profile_error( $settings, $data, $conversation = null ) {
		$values = self::profile_values( $data, $conversation );
		if ( '1' === $settings['profile_required'] && '' === $values['name'] ) {
			return new WP_Error( 'shcd_tiamis_name_required', 'نام را وارد کنید تا گفتگو آغاز شود.', array( 'status' => 400 ) );
		}
		if ( '1' === $settings['collect_email'] && ! is_email( $values['email'] ) ) {
			return new WP_Error( 'shcd_tiamis_email_required', 'یک نشانی ایمیل معتبر وارد کنید.', array( 'status' => 400 ) );
		}
		if ( '1' === $settings['collect_phone'] && '' === $values['phone'] ) {
			return new WP_Error( 'shcd_tiamis_phone_required', 'شماره تماس را وارد کنید.', array( 'status' => 400 ) );
		}
		if ( '1' === $settings['consent_required'] && ! $values['consent'] ) {
			return new WP_Error( 'shcd_tiamis_consent_required', 'برای آغاز گفتگو، گزینه رضایت را تأیید کنید.', array( 'status' => 403 ) );
		}
		return null;
	}

	private static function profile_complete( $settings, $conversation ) {
		return null === self::profile_error( $settings, array(), $conversation );
	}

	public static function session( WP_REST_Request $request ) {
		if ( Tiamis_Chat_Core::is_current_visitor_blocked() ) {
			return new WP_Error( 'shcd_tiamis_blocked', 'امکان استفاده از گفتگو برای این اتصال غیرفعال شده است.', array( 'status' => 403 ) );
		}
		if ( ! Tiamis_Chat_Core::rate_limit( 'session', 20, 60 ) ) {
			return new WP_Error( 'shcd_tiamis_rate_limit', 'تعداد درخواست‌ها بیش از حد مجاز است.', array( 'status' => 429 ) );
		}
		$data = self::request_data( $request );
		if ( ! empty( $data['website'] ) ) {
			return new WP_Error( 'shcd_tiamis_spam', 'درخواست نامعتبر است.', array( 'status' => 400 ) );
		}
		$settings = Tiamis_Chat_Core::settings();
		$provided_token = isset( $data['token'] ) ? sanitize_text_field( (string) $data['token'] ) : '';
		$existing = Tiamis_Chat_Core::get_conversation_by_token( $provided_token );
		$profile_error = self::profile_error( $settings, $data, $existing );
		if ( $profile_error ) {
			return $profile_error;
		}
		$result = Tiamis_Chat_Core::create_or_get_conversation( $provided_token, $data );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$conversation = $result['conversation'];
		return rest_ensure_response(
			array(
				'token'        => $result['token'],
				'conversation' => array(
					'public_id'    => $conversation->public_id,
					'status'       => $conversation->status,
					'visitor_name' => $conversation->visitor_name,
					'has_profile'  => '' !== $conversation->visitor_name,
					'has_consent'     => 1 === (int) $conversation->consent,
					'profile_complete' => self::profile_complete( $settings, $conversation ),
					'resume_code'      => Tiamis_Chat_Core::ensure_resume_code( $conversation->id ),
					'assigned_agent_index' => isset( $conversation->assigned_agent_index ) ? (int) $conversation->assigned_agent_index : 0,
				),
			)
		);
	}

	public static function get_messages( WP_REST_Request $request ) {
		return self::sync( $request );
	}

	public static function sync( WP_REST_Request $request ) {
		$conversation = Tiamis_Chat_Core::get_conversation_by_token( self::token_from_request( $request ) );
		if ( ! $conversation ) {
			return new WP_Error( 'shcd_tiamis_invalid_session', 'نشست گفتگو معتبر نیست.', array( 'status' => 401 ) );
		}
		$after = absint( $request->get_param( 'after' ) );
		$items = array_map( array( 'Tiamis_Chat_Core', 'public_message_data' ), Tiamis_Chat_Core::get_messages( $conversation->id, $after, 100 ) );
		Tiamis_Chat_Core::mark_seen_by_visitor( $conversation->id );
		$conversation = Tiamis_Chat_Core::get_conversation( $conversation->id );
		$rating = Tiamis_Chat_Core::get_rating( $conversation->id );
		return rest_ensure_response(
			array(
				'messages'              => $items,
				'status'                => $conversation->status,
				'operator_typing'       => Tiamis_Chat_Core::is_typing( $conversation->id, 'operator' ),
				'operator_typing_agent' => Tiamis_Chat_Core::typing_data( $conversation->id, 'operator' ),
				'operator_online'       => Tiamis_Chat_Core::operator_is_online(),
				'visitor_seen_through'  => Tiamis_Chat_Core::visitor_seen_through( $conversation->id ),
				'conversation'          => Tiamis_Chat_Core::public_conversation_data( $conversation ),
				'rating'                => $rating ? (int) $rating->rating : 0,
				'resume_code'           => Tiamis_Chat_Core::ensure_resume_code( $conversation->id ),
				'server_time'           => time(),
			)
		);
	}

	public static function typing( WP_REST_Request $request ) {
		if ( ! Tiamis_Chat_Core::rate_limit( 'typing', 90, 60 ) ) {
			return new WP_Error( 'shcd_tiamis_rate_limit', 'تعداد درخواست‌ها بیش از حد مجاز است.', array( 'status' => 429 ) );
		}
		$conversation = Tiamis_Chat_Core::get_conversation_by_token( self::token_from_request( $request ) );
		if ( ! $conversation ) {
			return new WP_Error( 'shcd_tiamis_invalid_session', 'نشست گفتگو معتبر نیست.', array( 'status' => 401 ) );
		}
		$data = self::request_data( $request );
		Tiamis_Chat_Core::set_typing( $conversation->id, 'visitor', ! empty( $data['typing'] ), 8 );
		return rest_ensure_response( array( 'ok' => true ) );
	}

	public static function post_message( WP_REST_Request $request ) {
		$settings = Tiamis_Chat_Core::settings();
		if ( ! Tiamis_Chat_Core::rate_limit( 'message', absint( $settings['rate_limit_messages'] ), 60 ) ) {
			return new WP_Error( 'shcd_tiamis_rate_limit', 'تعداد پیام‌ها بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.', array( 'status' => 429 ) );
		}
		$data = self::request_data( $request );
		if ( ! empty( $data['website'] ) ) {
			return new WP_Error( 'shcd_tiamis_spam', 'درخواست نامعتبر است.', array( 'status' => 400 ) );
		}
		$conversation = Tiamis_Chat_Core::get_conversation_by_token( self::token_from_request( $request ) );
		if ( ! $conversation ) {
			return new WP_Error( 'shcd_tiamis_invalid_session', 'نشست گفتگو معتبر نیست.', array( 'status' => 401 ) );
		}
		if ( Tiamis_Chat_Core::conversation_is_blocked( $conversation ) || Tiamis_Chat_Core::is_current_visitor_blocked() ) {
			return new WP_Error( 'shcd_tiamis_blocked', 'امکان ارسال پیام برای این اتصال غیرفعال شده است.', array( 'status' => 403 ) );
		}
		$profile_error = self::profile_error( $settings, $data, $conversation );
		if ( $profile_error ) {
			return $profile_error;
		}
		Tiamis_Chat_Core::update_visitor_profile( $conversation->id, $data );
		Tiamis_Chat_Core::set_typing( $conversation->id, 'visitor', false );
		$message = Tiamis_Chat_Core::add_message(
			$conversation->id,
			'visitor',
			isset( $data['message'] ) ? $data['message'] : '',
			'website',
			$conversation->visitor_name,
			array( 'reply_to_id' => absint( $data['reply_to_id'] ?? 0 ), 'attachment_id' => absint( $data['attachment_id'] ?? 0 ) ),
			isset( $data['client_message_id'] ) ? sanitize_text_field( $data['client_message_id'] ) : ''
		);
		if ( is_wp_error( $message ) ) {
			return $message;
		}
		if ( empty( $message->_tiamis_duplicate ) ) {
			if ( ! empty( $data['attachment_id'] ) ) { Tiamis_Chat_Core::set_attachment_message( absint( $data['attachment_id'] ), $message->id ); }
			Tiamis_Chat_Core::queue_outbound( $message->id, 'visitor' );
		}
		return new WP_REST_Response( array( 'message' => Tiamis_Chat_Core::public_message_data( $message ), 'duplicate' => ! empty( $message->_tiamis_duplicate ) ), empty( $message->_tiamis_duplicate ) ? 201 : 200 );
	}


	public static function edit_message( WP_REST_Request $request ) {
		$conversation = Tiamis_Chat_Core::get_conversation_by_token( self::token_from_request( $request ) );
		if ( ! $conversation ) return new WP_Error( 'shcd_tiamis_invalid_session', 'نشست گفتگو معتبر نیست.', array( 'status' => 401 ) );
		$data = self::request_data( $request );
		$result = Tiamis_Chat_Core::edit_message( $conversation->id, absint( $request['id'] ), (string) ( $data['message'] ?? '' ), 'visitor' );
		return is_wp_error( $result ) ? $result : rest_ensure_response( array( 'message' => Tiamis_Chat_Core::public_message_data( $result ) ) );
	}

	public static function delete_message( WP_REST_Request $request ) {
		$conversation = Tiamis_Chat_Core::get_conversation_by_token( self::token_from_request( $request ) );
		if ( ! $conversation ) return new WP_Error( 'shcd_tiamis_invalid_session', 'نشست گفتگو معتبر نیست.', array( 'status' => 401 ) );
		$result = Tiamis_Chat_Core::delete_message_for_all( $conversation->id, absint( $request['id'] ), 'visitor' );
		return is_wp_error( $result ) ? $result : rest_ensure_response( array( 'message' => Tiamis_Chat_Core::public_message_data( $result ) ) );
	}

	public static function reaction( WP_REST_Request $request ) {
		$conversation = Tiamis_Chat_Core::get_conversation_by_token( self::token_from_request( $request ) );
		if ( ! $conversation ) return new WP_Error( 'shcd_tiamis_invalid_session', 'نشست گفتگو معتبر نیست.', array( 'status' => 401 ) );
		$data = self::request_data( $request );
		$result = Tiamis_Chat_Core::react_message( $conversation->id, absint( $data['message_id'] ?? 0 ), 'visitor', substr( $conversation->session_hash, 0, 32 ), (string) ( $data['reaction'] ?? '👍' ) );
		return is_wp_error( $result ) ? $result : rest_ensure_response( array( 'reactions' => $result ) );
	}

	public static function upload( WP_REST_Request $request ) {
		$conversation = Tiamis_Chat_Core::get_conversation_by_token( self::token_from_request( $request ) );
		if ( ! $conversation ) return new WP_Error( 'shcd_tiamis_invalid_session', 'نشست گفتگو معتبر نیست.', array( 'status' => 401 ) );
		if ( Tiamis_Chat_Core::conversation_is_blocked( $conversation ) ) return new WP_Error( 'shcd_tiamis_blocked', 'ارسال فایل غیرفعال است.', array( 'status' => 403 ) );
		$files = $request->get_file_params();
		$file  = isset( $files['file'] ) && is_array( $files['file'] ) ? $files['file'] : array();
		$result = Tiamis_Chat_Core::create_attachment( $conversation->id, $file, 'visitor' );
		if ( is_wp_error( $result ) ) return $result;
		return new WP_REST_Response( array( 'attachment' => array( 'id' => (int) $result->id, 'name' => $result->file_name, 'url' => $result->file_url, 'mime' => $result->mime_type, 'size' => (int) $result->file_size ) ), 201 );
	}

	public static function resume( WP_REST_Request $request ) {
		$data = self::request_data( $request );
		$result = Tiamis_Chat_Core::claim_by_resume_code( (string) ( $data['code'] ?? '' ) );
		if ( is_wp_error( $result ) ) return $result;
		return rest_ensure_response( array( 'token' => $result['token'], 'conversation' => Tiamis_Chat_Core::public_conversation_data( $result['conversation'] ) ) );
	}

	public static function conversation_code( WP_REST_Request $request ) {
		$conversation = Tiamis_Chat_Core::get_conversation_by_token( self::token_from_request( $request ) );
		if ( ! $conversation ) return new WP_Error( 'shcd_tiamis_invalid_session', 'نشست گفتگو معتبر نیست.', array( 'status' => 401 ) );
		return rest_ensure_response( array( 'code' => Tiamis_Chat_Core::ensure_resume_code( $conversation->id ) ) );
	}

	public static function report_message( WP_REST_Request $request ) {
		if ( ! Tiamis_Chat_Core::rate_limit( 'report', 8, HOUR_IN_SECONDS ) ) {
			return new WP_Error( 'shcd_tiamis_rate_limit', 'تعداد گزارش‌ها بیش از حد مجاز است.', array( 'status' => 429 ) );
		}
		$conversation = Tiamis_Chat_Core::get_conversation_by_token( self::token_from_request( $request ) );
		if ( ! $conversation ) {
			return new WP_Error( 'shcd_tiamis_invalid_session', 'نشست گفتگو معتبر نیست.', array( 'status' => 401 ) );
		}
		$data   = self::request_data( $request );
		$result = Tiamis_Chat_Core::add_report(
			$conversation->id,
			isset( $data['message_id'] ) ? absint( $data['message_id'] ) : 0,
			isset( $data['reason'] ) ? sanitize_key( $data['reason'] ) : 'inappropriate',
			isset( $data['details'] ) ? $data['details'] : ''
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return rest_ensure_response( array( 'ok' => true ) );
	}

	public static function save_rating( WP_REST_Request $request ) {
		$conversation = Tiamis_Chat_Core::get_conversation_by_token( self::token_from_request( $request ) );
		if ( ! $conversation ) {
			return new WP_Error( 'shcd_tiamis_invalid_session', 'نشست گفتگو معتبر نیست.', array( 'status' => 401 ) );
		}
		$data   = self::request_data( $request );
		$result = Tiamis_Chat_Core::save_rating(
			$conversation->id,
			isset( $data['rating'] ) ? absint( $data['rating'] ) : 0,
			isset( $data['label'] ) ? $data['label'] : '',
			isset( $data['comment'] ) ? $data['comment'] : ''
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return rest_ensure_response( array( 'ok' => true ) );
	}

	public static function end_conversation( WP_REST_Request $request ) {
		global $wpdb;
		$conversation = Tiamis_Chat_Core::get_conversation_by_token( self::token_from_request( $request ) );
		if ( ! $conversation ) {
			return new WP_Error( 'shcd_tiamis_invalid_session', 'نشست گفتگو معتبر نیست.', array( 'status' => 401 ) );
		}
		$wpdb->update(
			Tiamis_Chat_DB::table( 'conversations' ),
			array( 'status' => 'closed', 'updated_at' => Tiamis_Chat_Core::now() ),
			array( 'id' => (int) $conversation->id )
		);
		do_action( 'shcd_tiamis_conversation_closed', (int) $conversation->id );
		return rest_ensure_response( array( 'ok' => true, 'status' => 'closed' ) );
	}

	public static function telegram_webhook( WP_REST_Request $request ) {
		$settings = Tiamis_Chat_Core::settings();
		// A previously registered webhook may continue to call the site after the
		// integration is disabled. Acknowledge it without processing user data.
		if ( '1' !== $settings['telegram_enabled'] ) {
			return rest_ensure_response( array( 'ok' => true, 'disabled' => true ) );
		}
		$provided = $request->get_header( 'x-telegram-bot-api-secret-token' );
		$expected = (string) $settings['telegram_webhook_secret'];
		if ( '' === $expected || '' === $provided || ! hash_equals( $expected, $provided ) ) {
			return new WP_Error( 'shcd_tiamis_telegram_secret', 'وب‌هوک تلگرام معتبر نیست.', array( 'status' => 403 ) );
		}
		$update = self::request_data( $request );
		$update_id = isset( $update['update_id'] ) ? absint( $update['update_id'] ) : 0;
		$idempotency_key = $update_id ? 'shcd_tiamis_tg_update_' . $update_id : '';
		if ( $idempotency_key && get_transient( $idempotency_key ) ) {
			return rest_ensure_response( array( 'ok' => true, 'duplicate' => true ) );
		}
		$result = Tiamis_Chat_Integrations::handle_telegram_update( $update );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( $idempotency_key ) {
			set_transient( $idempotency_key, 1, WEEK_IN_SECONDS );
		}
		return rest_ensure_response( array( 'ok' => true ) );
	}


	public static function telegram_poll( WP_REST_Request $request ) {
		$settings = Tiamis_Chat_Core::settings();
		$provided = sanitize_text_field( (string) $request->get_param( 'key' ) );
		$expected = isset( $settings['telegram_poll_secret'] ) ? (string) $settings['telegram_poll_secret'] : '';
		if ( '' === $expected || '' === $provided || ! hash_equals( $expected, $provided ) ) {
			return new WP_Error( 'shcd_tiamis_telegram_poll_secret', 'کلید اجرای دریافت دوره‌ای تلگرام معتبر نیست.', array( 'status' => 403 ) );
		}
		$result = Tiamis_Chat_Integrations::telegram_poll_updates();
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return rest_ensure_response( array( 'ok' => true, 'result' => $result ) );
	}

	public static function bale_webhook( WP_REST_Request $request ) {
		$settings = Tiamis_Chat_Core::settings();
		if ( '1' !== $settings['bale_enabled'] ) {
			return rest_ensure_response( array( 'ok' => true, 'disabled' => true ) );
		}
		$provided = sanitize_text_field( (string) $request->get_param( 'key' ) );
		$expected = (string) $settings['bale_webhook_secret'];
		if ( '' === $expected || '' === $provided || ! hash_equals( $expected, $provided ) ) {
			return new WP_Error( 'shcd_tiamis_bale_secret', 'وب‌هوک بله معتبر نیست.', array( 'status' => 403 ) );
		}
		$update          = self::request_data( $request );
		$update_id       = isset( $update['update_id'] ) ? absint( $update['update_id'] ) : 0;
		$idempotency_key = $update_id ? 'shcd_tiamis_bale_update_' . $update_id : '';
		if ( $idempotency_key && get_transient( $idempotency_key ) ) {
			return rest_ensure_response( array( 'ok' => true, 'duplicate' => true ) );
		}
		$result = Tiamis_Chat_Integrations::handle_bale_update( $update );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( $idempotency_key ) {
			set_transient( $idempotency_key, 1, WEEK_IN_SECONDS );
		}
		return rest_ensure_response( array( 'ok' => true ) );
	}


	public static function ajax_public() {
		check_ajax_referer( 'shcd_tiamis_public_ajax', 'nonce' );
		$route_raw   = filter_input( INPUT_POST, 'route', FILTER_UNSAFE_RAW );
		$payload_raw = filter_input( INPUT_POST, 'payload', FILTER_UNSAFE_RAW );
		$token_raw   = filter_input( INPUT_POST, 'token', FILTER_UNSAFE_RAW );
		$method_raw  = filter_input( INPUT_POST, 'http_method', FILTER_UNSAFE_RAW );
		$route       = is_string( $route_raw ) ? sanitize_key( wp_unslash( $route_raw ) ) : '';
		$payload     = array();
		if ( is_string( $payload_raw ) && '' !== $payload_raw ) {
			$decoded = json_decode( sanitize_textarea_field( wp_unslash( $payload_raw ) ), true );
			$payload = is_array( $decoded ) ? map_deep( $decoded, 'sanitize_textarea_field' ) : array();
		}
		$token  = is_string( $token_raw ) ? sanitize_text_field( wp_unslash( $token_raw ) ) : '';
		$method = is_string( $method_raw ) ? strtoupper( sanitize_text_field( wp_unslash( $method_raw ) ) ) : 'POST';
		$request = new WP_REST_Request( $method, '/shcd-tiamis/v1/' . str_replace( '_', '/', $route ) );
		$request->set_header( 'x-shcd-tiamis-session', $token );
		$request->set_body_params( $payload );
		$request->set_query_params( $payload );
		$routes = array(
			'session'          => array( __CLASS__, 'session' ),
			'messages_get'     => array( __CLASS__, 'get_messages' ),
			'messages_post'    => array( __CLASS__, 'post_message' ),
			'sync'              => array( __CLASS__, 'sync' ),
			'reaction'          => array( __CLASS__, 'reaction' ),
			'resume'            => array( __CLASS__, 'resume' ),
			'conversation_code' => array( __CLASS__, 'conversation_code' ),
			'typing'           => array( __CLASS__, 'typing' ),
			'report'           => array( __CLASS__, 'report_message' ),
			'rating'           => array( __CLASS__, 'save_rating' ),
			'conversation_end' => array( __CLASS__, 'end_conversation' ),
		);
		if ( ! isset( $routes[ $route ] ) ) {
			wp_send_json_error( array( 'message' => 'مسیر درخواست معتبر نیست.' ), 404 );
		}
		$result = call_user_func( $routes[ $route ], $request );
		if ( is_wp_error( $result ) ) {
			$error_data = $result->get_error_data();
			$status     = is_array( $error_data ) && isset( $error_data['status'] ) ? (int) $error_data['status'] : 400;
			wp_send_json_error( array( 'code' => $result->get_error_code(), 'message' => $result->get_error_message() ), $status );
		}
		$response = rest_ensure_response( $result );
		wp_send_json_success( $response->get_data(), $response->get_status() );
	}

}
