<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tiamis_Chat_Admin {
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_menu', array( __CLASS__, 'decorate_menu_badges' ), 99 );
		add_action( 'admin_head', array( __CLASS__, 'menu_pro_badge_styles' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_shcd_tiamis_save_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_shcd_tiamis_reply', array( __CLASS__, 'reply' ) );
		add_action( 'wp_ajax_shcd_tiamis_reply', array( __CLASS__, 'reply' ) );
		add_action( 'admin_post_shcd_tiamis_status', array( __CLASS__, 'change_status' ) );
		add_action( 'wp_ajax_shcd_tiamis_ai_draft', array( __CLASS__, 'ajax_ai_draft' ) );
		add_action( 'wp_ajax_shcd_tiamis_poll_admin', array( __CLASS__, 'ajax_poll_admin' ) );
		add_action( 'wp_ajax_shcd_tiamis_set_language', array( __CLASS__, 'ajax_set_language' ) );
		add_action( 'wp_ajax_shcd_tiamis_db_action', array( __CLASS__, 'ajax_db_action' ) );
		add_action( 'wp_ajax_shcd_tiamis_admin_typing', array( __CLASS__, 'ajax_admin_typing' ) );
		add_action( 'wp_ajax_shcd_tiamis_operator_heartbeat', array( __CLASS__, 'ajax_operator_heartbeat' ) );
		add_action( 'wp_ajax_shcd_tiamis_poll_notifications', array( __CLASS__, 'ajax_poll_notifications' ) );
		add_action( 'wp_ajax_shcd_tiamis_save_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'wp_ajax_shcd_tiamis_save_note', array( __CLASS__, 'ajax_save_note' ) );
		add_action( 'wp_ajax_shcd_tiamis_conversation_lock', array( __CLASS__, 'ajax_conversation_lock' ) );
		add_action( 'wp_ajax_shcd_tiamis_task', array( __CLASS__, 'ajax_task' ) );
		add_action( 'wp_ajax_shcd_tiamis_tag', array( __CLASS__, 'ajax_tag' ) );
		add_action( 'wp_ajax_shcd_tiamis_manage_conversation', array( __CLASS__, 'ajax_manage_conversation' ) );
		add_action( 'wp_ajax_shcd_tiamis_ai_insights', array( __CLASS__, 'ajax_ai_insights' ) );
		add_action( 'wp_ajax_shcd_tiamis_knowledge_sync', array( __CLASS__, 'ajax_knowledge_sync' ) );
		add_action( 'admin_post_shcd_tiamis_export', array( __CLASS__, 'export_conversation' ) );
		add_action( 'admin_post_shcd_tiamis_delete_conversation', array( __CLASS__, 'delete_conversation' ) );
		add_action( 'admin_post_shcd_tiamis_toggle_block', array( __CLASS__, 'toggle_block' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( TIAMIS_CHAT_FILE ), array( __CLASS__, 'action_links' ) );
		add_filter( 'parent_file', array( __CLASS__, 'menu_parent_file' ) );
		add_filter( 'submenu_file', array( __CLASS__, 'menu_submenu_file' ), 10, 2 );
	}

	public static function menu() {
		$brand = Tiamis_Chat_I18n::brand_name();
		add_menu_page( $brand, $brand, Tiamis_Chat_Platform::CAP_INBOX, 'shcd-tiamis', array( __CLASS__, 'render_inbox' ), TIAMIS_CHAT_URL . 'assets/image/tiamis-logo.png', 58 );
		add_submenu_page(
			'shcd-tiamis',
			Tiamis_Chat_I18n::admin_text( 'تیکتینگ هوشمند' ),
			Tiamis_Chat_I18n::admin_text( 'تیکتینگ هوشمند' ),
			Tiamis_Chat_Platform::CAP_INBOX,
			'shcd-tiamis-tickets',
			array( __CLASS__, 'render_tickets_pro' )
		);
		add_submenu_page( 'shcd-tiamis', Tiamis_Chat_I18n::admin_text( 'تحلیل رفتار و نقشه کلیک' ), Tiamis_Chat_I18n::admin_text( 'تحلیل و نقشه کلیک' ), Tiamis_Chat_Platform::CAP_REPORTS, 'shcd-tiamis-analytics', array( __CLASS__, 'render_analytics' ) );
		add_submenu_page( 'shcd-tiamis', Tiamis_Chat_I18n::admin_text( 'تنظیمات' ), Tiamis_Chat_I18n::admin_text( 'تنظیمات' ), Tiamis_Chat_Platform::CAP_SETTINGS, 'shcd-tiamis-settings', array( __CLASS__, 'render_settings' ) );

		// WordPress automatically creates the first child menu for the top-level
		// page. Rename that native item instead of registering a duplicate entry.
		global $submenu;
		if ( isset( $submenu['shcd-tiamis'][0] ) ) {
			$submenu['shcd-tiamis'][0][0] = Tiamis_Chat_I18n::admin_text( 'گفتگوها' );
			$submenu['shcd-tiamis'][0][3] = Tiamis_Chat_I18n::admin_text( 'صندوق گفتگوها' );
		}
	}


	public static function menu_pro_badge_styles() {
		echo '<style id="tiamis-menu-pro-badge-style">#adminmenu .tiamis-menu-pro-pill{display:inline-flex;align-items:center;justify-content:center;margin-inline-start:6px;padding:1px 7px;border:1px solid rgba(255,215,96,.78);border-radius:999px;color:#3d2700;background:linear-gradient(135deg,#fff2a8,#d9a928);box-shadow:inset 0 1px 0 rgba(255,255,255,.65),0 2px 8px rgba(148,99,0,.24);font-size:9px;font-weight:800;line-height:16px;letter-spacing:.35px;vertical-align:middle}#adminmenu .toplevel_page_shcd-tiamis .wp-submenu a{padding-inline-start:12px!important;padding-inline-end:12px!important;text-indent:0!important;white-space:nowrap}#adminmenu .toplevel_page_shcd-tiamis .wp-submenu li{direction:inherit}</style>';
	}

	private static function menu_badge_markup( $count, $type ) {
		$count = absint( $count );
		$class = $count > 0 ? '' : ' is-zero';
		return sprintf(
			' <span class="awaiting-mod tiamis-menu-badge%1$s" data-tiamis-badge="%2$s"><span class="pending-count">%3$s</span></span>',
			esc_attr( $class ),
			esc_attr( sanitize_key( $type ) ),
			esc_html( number_format_i18n( $count ) )
		);
	}

	public static function unread_counts() {
		global $wpdb;
		$counts = array( 'chat' => 0, 'total' => 0 );
		if ( Tiamis_Chat_Platform::can_inbox() ) {
			$counts['chat'] = absint( $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE unread_admin > 0', Tiamis_Chat_DB::table( 'conversations' ) ) ) );
		}
		$counts['total'] = $counts['chat'];
		return $counts;
	}

	public static function decorate_menu_badges() {
		if ( ! is_user_logged_in() ) {
			return;
		}
		$counts = self::unread_counts();
		global $menu, $submenu;

		foreach ( (array) $menu as $index => $item ) {
			$slug = isset( $item[2] ) ? (string) $item[2] : '';
			if ( 'shcd-tiamis' === $slug ) {
				$menu[ $index ][0] = Tiamis_Chat_I18n::brand_name() . self::menu_badge_markup( $counts['total'], 'total' );
			}
		}

		if ( isset( $submenu['shcd-tiamis'] ) ) {
			foreach ( $submenu['shcd-tiamis'] as $index => $item ) {
				$slug = isset( $item[2] ) ? (string) $item[2] : '';
				if ( 'shcd-tiamis' === $slug ) {
					$submenu['shcd-tiamis'][ $index ][0] = Tiamis_Chat_I18n::admin_text( 'گفتگوها' ) . self::menu_badge_markup( $counts['chat'], 'chat' );
				}
			}
		}
	}


	public static function menu_parent_file( $parent_file ) {
		$page = self::request_value( 'page', 'GET', 'key' );
		if ( 0 === strpos( $page, 'shcd-tiamis' ) ) {
			return 'shcd-tiamis';
		}
		return $parent_file;
	}

	public static function menu_submenu_file( $submenu_file, $parent_file ) {
		$page = self::request_value( 'page', 'GET', 'key' );
		if ( 'shcd-tiamis' !== $parent_file && 0 !== strpos( $page, 'shcd-tiamis' ) ) {
			return $submenu_file;
		}
		$allowed = array( 'shcd-tiamis', 'shcd-tiamis-tickets', 'shcd-tiamis-analytics', 'shcd-tiamis-settings' );
		return in_array( $page, $allowed, true ) ? $page : 'shcd-tiamis';
	}

	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=shcd-tiamis-settings' ) ) . '">' . esc_html( Tiamis_Chat_I18n::admin_text( 'تنظیمات' ) ) . '</a>' );
		return $links;
	}

	public static function assets( $hook ) {
		$settings = Tiamis_Chat_Core::settings();
		$can_receive_notifications = Tiamis_Chat_Platform::can_inbox();
		if ( $can_receive_notifications ) {
			wp_enqueue_style( 'shcd-tiamis-admin-notify', TIAMIS_CHAT_URL . 'assets/css/shcd-tiamis-admin-notify.css', array(), TIAMIS_CHAT_VERSION );
			wp_enqueue_script( 'shcd-tiamis-admin-notify', TIAMIS_CHAT_URL . 'assets/js/shcd-tiamis-admin-notify.js', array(), TIAMIS_CHAT_VERSION, true );
			if ( ! empty( $settings['admin_font_url'] ) ) {
				$notify_font_path = (string) wp_parse_url( $settings['admin_font_url'], PHP_URL_PATH );
				$notify_font_ext  = strtolower( pathinfo( $notify_font_path, PATHINFO_EXTENSION ) );
				$notify_font_type = 'ttf' === $notify_font_ext ? 'truetype' : 'woff2';
				$notify_font_css  = "@font-face{font-family:'Shabnam FD';src:local('Shabnam FD'),url('" . esc_url( $settings['admin_font_url'] ) . "') format('" . $notify_font_type . "');font-display:swap;font-style:normal;font-weight:400}.tiamis-global-chat-notice,.tiamis-global-chat-notice *{font-family:'Shabnam FD',Tahoma,Arial,sans-serif!important}";
				wp_add_inline_style( 'shcd-tiamis-admin-notify', $notify_font_css );
			}
			wp_localize_script(
				'shcd-tiamis-admin-notify',
				'TiamisAdminNotify',
				array(
					'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
					'nonce'       => wp_create_nonce( 'shcd_tiamis_admin_ajax' ),
					'inboxUrl'    => admin_url( 'admin.php?page=shcd-tiamis' ),
					'title'       => Tiamis_Chat_I18n::admin_text( 'پیام تازه در تیامیس' ),
					'body'        => Tiamis_Chat_I18n::admin_text( 'یک پیام تازه از %s دریافت شد.' ),
					'unreadChatSummary'  => Tiamis_Chat_I18n::admin_text( '%s گفتگوی خوانده‌نشده در صندوق تیامیس دارید.' ),
				)
			);
		}
		if ( false === strpos( $hook, 'shcd-tiamis' ) ) {
			return;
		}
		wp_enqueue_style( 'shcd-tiamis-admin', TIAMIS_CHAT_URL . 'assets/css/shcd-tiamis-admin.css', array(), TIAMIS_CHAT_VERSION );
		if ( ! empty( $settings['admin_font_url'] ) ) {
			$font_path = (string) wp_parse_url( $settings['admin_font_url'], PHP_URL_PATH );
			$font_ext  = strtolower( pathinfo( $font_path, PATHINFO_EXTENSION ) );
			$font_type = 'ttf' === $font_ext ? 'truetype' : 'woff2';
			$font_css  = "@font-face{font-family:'Shabnam FD';src:local('Shabnam FD'),url('" . esc_url( $settings['admin_font_url'] ) . "') format('" . $font_type . "');font-display:swap;font-style:normal;font-weight:400}.tiamis-admin-wrap{--tiamis-font:'Shabnam FD',Tahoma,Arial,sans-serif}.tiamis-admin-wrap,.tiamis-admin-wrap *:not(code):not(pre):not(.dashicons){font-family:var(--tiamis-font)!important}.tiamis-admin-wrap .ltr{font-family:var(--tiamis-font)!important;direction:ltr;text-align:left}";
			wp_add_inline_style( 'shcd-tiamis-admin', $font_css );
		}
		wp_enqueue_script( 'shcd-tiamis-admin', TIAMIS_CHAT_URL . 'assets/js/shcd-tiamis-admin.js', array( 'jquery' ), TIAMIS_CHAT_VERSION, true );
		$language = Tiamis_Chat_I18n::admin_language();
		wp_localize_script(
			'shcd-tiamis-admin',
			'TiamisChatAdmin',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'shcd_tiamis_admin_ajax' ),
				'language'     => $language,
				'direction'    => Tiamis_Chat_I18n::direction( $language ),
				'translations' => Tiamis_Chat_I18n::admin_dictionary(),
				'cacheTtl'     => 20000,
				'i18n'         => array(
					'thinking'       => Tiamis_Chat_I18n::admin_text( 'در حال آماده‌سازی پاسخ پیشنهادی…', $language ),
					'error'          => Tiamis_Chat_I18n::admin_text( 'ساخت پاسخ پیشنهادی انجام نشد.', $language ),
					'savingLanguage' => Tiamis_Chat_I18n::admin_text( 'در حال ذخیره زبان پنل…', $language ),
					'languageSaved'  => Tiamis_Chat_I18n::admin_text( 'زبان پنل ذخیره شد.', $language ),
					'languageError'  => Tiamis_Chat_I18n::admin_text( 'ذخیره زبان پنل انجام نشد.', $language ),
					'loading'        => Tiamis_Chat_I18n::admin_text( 'در حال بارگذاری…', $language ),
					'saved'          => Tiamis_Chat_I18n::admin_text( 'تغییرات ذخیره شد.', $language ),
					'dbConfirm'      => Tiamis_Chat_I18n::admin_text( 'برای حذف همه داده‌ها، عبارت RESET را وارد کنید.', $language ),
					'dbDone'         => Tiamis_Chat_I18n::admin_text( 'عملیات پایگاه داده با موفقیت انجام شد.', $language ),
					'visitorTyping'  => Tiamis_Chat_I18n::admin_text( 'کاربر در حال تایپ است…', $language ),
					'operatorOnline' => Tiamis_Chat_I18n::admin_text( 'اپراتور آنلاین است', $language ),
					'operatorOffline'=> Tiamis_Chat_I18n::admin_text( 'اپراتور آفلاین است', $language ),
				),
			)
		);
	}

	private static function guard( $capability = 'inbox' ) {
		$allowed = 'settings' === $capability ? Tiamis_Chat_Platform::can_settings() : ( 'reports' === $capability ? current_user_can( Tiamis_Chat_Platform::CAP_REPORTS ) : ( 'reply' === $capability ? Tiamis_Chat_Platform::can_reply() : Tiamis_Chat_Platform::can_inbox() ) );
		if ( ! $allowed ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'tiamis-ai-live-chat' ) );
		}
	}

	/**
	 * Read a scalar value from the current admin request.
	 *
	 * Read-only navigation parameters do not require a nonce. Mutating actions
	 * are still protected by check_admin_referer() or check_ajax_referer().
	 *
	 * @param string $key     Request key.
	 * @param string $method  GET or POST.
	 * @param string $type    key, text, textarea, email, url, int or raw.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	private static function request_value( $key, $method = 'GET', $type = 'text', $default = '' ) {
		$source = 'POST' === strtoupper( $method ) ? INPUT_POST : INPUT_GET;
		$value  = filter_input( $source, $key, FILTER_UNSAFE_RAW );
		if ( null === $value || false === $value || is_array( $value ) ) {
			return $default;
		}

		$value = wp_unslash( (string) $value );
		switch ( $type ) {
			case 'key':
				return sanitize_key( $value );
			case 'textarea':
				return sanitize_textarea_field( $value );
			case 'email':
				return sanitize_email( $value );
			case 'url':
				return esc_url_raw( $value );
			case 'int':
				return absint( $value );
			case 'raw':
				return $value;
			case 'text':
			default:
				return sanitize_text_field( $value );
		}
	}

	private static function page_open( $page ) {
		$language = Tiamis_Chat_I18n::admin_language();
		printf(
			'<div class="wrap tiamis-admin-wrap" data-tiamis-page="%1$s" dir="%2$s" lang="%3$s">',
			esc_attr( $page ),
			esc_attr( Tiamis_Chat_I18n::direction( $language ) ),
			esc_attr( $language )
		);
		if ( 'inbox' !== $page ) {
			echo '<div class="tiamis-rgb-loader" data-tiamis-loader hidden><span></span><b>در حال بارگذاری…</b></div>';
		}
	}

	private static function page_close() {
		echo '</div>';
	}

	private static function header( $title, $description = '' ) {
		$settings = Tiamis_Chat_Core::settings();
		?>
		<header class="tiamis-admin-head tw-flex tw-items-center tw-justify-between tw-gap-5">
			<div class="tiamis-admin-heading tw-flex tw-items-center tw-gap-4">
				<span class="tiamis-brand-orb" aria-hidden="true"><img src="<?php echo esc_url( TIAMIS_CHAT_URL . 'assets/image/tiamis-logo.png' ); ?>" alt="" width="56" height="56"></span>
				<div><h1><?php echo esc_html( $title ); ?></h1><?php if ( $description ) : ?><p><?php echo esc_html( $description ); ?></p><?php endif; ?></div>
			</div>
			<div class="tiamis-head-controls tw-flex tw-items-center tw-gap-3">
				<button type="button" class="tiamis-icon-button" data-tiamis-refresh title="تازه‌سازی بدون بارگذاری کامل صفحه" aria-label="تازه‌سازی بدون بارگذاری کامل صفحه">↻</button>
				<label class="tiamis-language-switch"><span>زبان پنل</span><select data-tiamis-language-switch aria-label="زبان پنل"><?php foreach ( Tiamis_Chat_I18n::language_names() as $code => $name ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( Tiamis_Chat_I18n::admin_language(), $code ); ?>><?php echo esc_html( $name ); ?></option><?php endforeach; ?></select><small data-tiamis-language-status></small></label>
				<div class="tiamis-admin-head-status"><span class="tiamis-status-dot <?php echo esc_attr( '1' === $settings['enabled'] ? 'is-online' : '' ); ?>"></span><?php echo esc_html( '1' === $settings['enabled'] ? 'چت سایت فعال است' : 'چت سایت غیرفعال است' ); ?></div>
			</div>
		</header>
		<?php self::notice(); ?>
		<?php
	}

	private static function notice() {
		$notice = self::request_value( 'tiamis_notice', 'GET', 'key' );
		if ( '' === $notice ) {
			return;
		}
		$messages = array(
			'saved'         => array( 'success', 'تنظیمات با موفقیت ذخیره شد.' ),
			'replied'       => array( 'success', 'پاسخ برای کاربر ارسال شد.' ),
			'reply_error'   => array( 'error', 'ارسال پاسخ انجام نشد.' ),
			'status'        => array( 'success', 'وضعیت گفتگو به‌روزرسانی شد.' ),
			'webhook_ok'    => array( 'success', 'وب‌هوک تلگرام با موفقیت ثبت شد.' ),
			'webhook_error' => array( 'error', 'ثبت وب‌هوک تلگرام انجام نشد؛ توکن ربات و دسترسی HTTPS سایت را بررسی کنید.' ),
				'bale_webhook_ok' => array( 'success', 'وب‌هوک بله با موفقیت ثبت شد.' ),
				'bale_webhook_error' => array( 'error', 'ثبت وب‌هوک بله انجام نشد؛ توکن ربات و دسترسی HTTPS سایت را بررسی کنید.' ),
				'bots_webhook_ok' => array( 'success', 'وب‌هوک ربات‌های فعال با موفقیت ثبت شد.' ),
				'bots_webhook_error' => array( 'error', 'ثبت وب‌هوک یکی از ربات‌ها کامل نشد؛ تنظیمات تلگرام و بله را بررسی کنید.' ),
			'font_error'    => array( 'error', 'فونت بارگذاری نشد. یک فایل معتبر WOFF2 یا TTF با حجم حداکثر ۳ مگابایت انتخاب کنید.' ),
		);
		if ( isset( $messages[ $notice ] ) ) {
			echo '<div class="tiamis-toast is-' . esc_attr( $messages[ $notice ][0] ) . '" data-tiamis-toast><span>' . esc_html( $messages[ $notice ][1] ) . '</span><button type="button" aria-label="بستن">×</button></div>';
		}
	}

	public static function render_inbox() {
		self::guard();
		global $wpdb;
		$table  = Tiamis_Chat_DB::table( 'conversations' );
		$status = self::request_value( 'status', 'GET', 'key' );
		$search = self::request_value( 's', 'GET', 'text' );
		$valid_status = in_array( $status, array( 'open', 'pending', 'closed' ), true );
		$has_search   = '' !== $search;
		$like         = $has_search ? '%' . $wpdb->esc_like( $search ) . '%' : '';

		if ( $valid_status && $has_search ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE status = %s AND (visitor_name LIKE %s OR visitor_email LIKE %s OR visitor_phone LIKE %s OR public_id LIKE %s) ORDER BY unread_admin DESC, last_message_at DESC LIMIT 150',
					$table,
					$status,
					$like,
					$like,
					$like,
					$like
				)
			);
		} elseif ( $valid_status ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE status = %s ORDER BY unread_admin DESC, last_message_at DESC LIMIT 150',
					$table,
					$status
				)
			);
		} elseif ( $has_search ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE visitor_name LIKE %s OR visitor_email LIKE %s OR visitor_phone LIKE %s OR public_id LIKE %s ORDER BY unread_admin DESC, last_message_at DESC LIMIT 150',
					$table,
					$like,
					$like,
					$like,
					$like
				)
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i ORDER BY unread_admin DESC, last_message_at DESC LIMIT 150',
					$table
				)
			);
		}
		$selected_id = self::request_value( 'conversation', 'GET', 'int', $rows ? (int) $rows[0]->id : 0 );
		$selected    = $selected_id ? Tiamis_Chat_Core::get_conversation( $selected_id ) : null;
		$messages    = $selected ? Tiamis_Chat_Core::get_messages( $selected->id, 0, 300 ) : array();
		$notes       = $selected ? Tiamis_Chat_Core::get_notes( $selected->id ) : array();
		$reports     = $selected ? Tiamis_Chat_Core::get_reports( $selected->id ) : array();
		$rating      = $selected ? Tiamis_Chat_Core::get_rating( $selected->id ) : null;
		$is_blocked  = $selected ? Tiamis_Chat_Core::conversation_is_blocked( $selected ) : false;
		$tasks       = $selected ? Tiamis_Chat_Platform::get_tasks( $selected->id ) : array();
		$tags        = $selected ? Tiamis_Chat_Platform::get_tags( $selected->id ) : array();
		$all_tags    = Tiamis_Chat_Platform::get_tags();
		$audit       = $selected ? Tiamis_Chat_Platform::get_audit( $selected->id ) : array();
		$settings    = Tiamis_Chat_Core::settings();
		$agents      = Tiamis_Chat_Core::agents( $settings );
		$macros      = isset( $settings['macros'] ) && is_array( $settings['macros'] ) ? $settings['macros'] : array();
		$lock        = null;
		if ( $selected ) {
			Tiamis_Chat_Core::mark_seen_by_admin( $selected->id );
			$lock = Tiamis_Chat_Platform::acquire_lock( $selected->id );
			$selected = Tiamis_Chat_Core::get_conversation( $selected->id );
		}
		self::page_open( 'inbox' );
		self::header( 'صندوق گفتگوها', 'گفتگوها، پیگیری‌ها، یادداشت‌ها و اطلاعات مخاطب را در یک فضای منظم مدیریت کنید.' );
		?>
		<div class="tiamis-inbox-layout tiamis-inbox-v2 tw-grid">
			<aside class="tiamis-conversation-sidebar">
				<form method="get" class="tiamis-inbox-filter" data-tiamis-spa-form>
					<input type="hidden" name="page" value="shcd-tiamis">
					<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="نام، ایمیل، شماره تماس یا شناسه…">
					<select name="status"><option value="">همه وضعیت‌ها</option><option value="open" <?php selected( $status, 'open' ); ?>>باز</option><option value="pending" <?php selected( $status, 'pending' ); ?>>در انتظار</option><option value="closed" <?php selected( $status, 'closed' ); ?>>بسته</option></select>
					<button class="button">جستجو</button>
				</form>
				<div class="tiamis-conversation-list">
					<?php if ( ! $rows ) : ?><div class="tiamis-empty">هنوز گفتگویی ثبت نشده است.</div><?php endif; ?>
					<?php foreach ( $rows as $row ) :
						$name = $row->visitor_name ? $row->visitor_name : 'بازدیدکننده ناشناس';
						$url  = add_query_arg( array( 'page' => 'shcd-tiamis', 'conversation' => $row->id, 'status' => $status, 's' => $search ), admin_url( 'admin.php' ) );
						?>
						<a data-tiamis-spa-link href="<?php echo esc_url( $url ); ?>" class="tiamis-conversation-item <?php echo esc_attr( (int) $row->id === $selected_id ? 'is-active' : '' ); ?>">
							<div><strong><?php echo esc_html( $name ); ?></strong><span><?php echo esc_html( $row->visitor_phone ?: $row->visitor_email ?: wp_html_excerpt( $row->public_id, 18, '…' ) ); ?></span></div>
							<div class="tiamis-conversation-meta"><span class="tiamis-status-badge is-<?php echo esc_attr( $row->status ); ?>"><?php echo esc_html( self::status_label( $row->status ) ); ?></span><?php if ( $row->unread_admin ) : ?><b><?php echo esc_html( absint( $row->unread_admin ) ); ?></b><?php endif; ?></div>
						</a>
					<?php endforeach; ?>
				</div>
			</aside>
			<main class="shcd-tiamis-workspace tiamis-workspace-v2" data-tiamis-admin-workspace data-conversation="<?php echo esc_attr( $selected ? absint( $selected->id ) : 0 ); ?>" data-last-message="<?php echo esc_attr( $messages ? absint( end( $messages )->id ) : 0 ); ?>">
				<?php if ( ! $selected ) : ?>
					<div class="tiamis-empty tiamis-empty-large">برای دیدن پیام‌ها، یک گفتگو را انتخاب کنید.</div>
				<?php else : ?>
					<div class="tiamis-conversation-topbar">
						<div class="tiamis-contact-identity">
							<div class="tiamis-contact-avatar" aria-hidden="true"><?php echo esc_html( function_exists( 'mb_substr' ) ? mb_substr( $selected->visitor_name ?: 'ک', 0, 1 ) : substr( $selected->visitor_name ?: 'ک', 0, 1 ) ); ?></div>
							<div class="tiamis-contact-copy"><h2><?php echo esc_html( $selected->visitor_name ?: 'بازدیدکننده ناشناس' ); ?></h2><p><?php echo esc_html( $selected->visitor_phone ?: $selected->visitor_email ?: 'کاربر وب‌سایت' ); ?></p></div>
						</div>
						<div class="tiamis-conversation-badges">
							<span class="is-priority-<?php echo esc_attr( $selected->priority ?? 'normal' ); ?>">اولویت: <?php echo esc_html( self::priority_label( $selected->priority ?? 'normal' ) ); ?></span>
							<span><?php echo esc_html( $selected->department ?: 'پشتیبانی' ); ?></span>
							<?php if ( ! empty( $selected->sla_due_at ) && 'closed' !== $selected->status ) : ?><span data-tiamis-sla="<?php echo esc_attr( $selected->sla_due_at ); ?>">مهلت پاسخ: <?php echo esc_html( $selected->sla_due_at ); ?></span><?php endif; ?>
						</div>
						<div class="tiamis-conversation-actions" aria-label="اقدامات گفتگو">
							<a class="tiamis-action-button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=shcd_tiamis_export&conversation=' . $selected->id ), 'shcd_tiamis_export_' . $selected->id ) ); ?>"><span class="dashicons dashicons-download" aria-hidden="true"></span><span>خروجی CSV</span></a>
							<a class="tiamis-action-button <?php echo esc_attr( $is_blocked ? 'is-success' : 'is-warning' ); ?>" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=shcd_tiamis_toggle_block&conversation=' . $selected->id ), 'shcd_tiamis_toggle_block_' . $selected->id ) ); ?>"><span class="dashicons <?php echo esc_attr( $is_blocked ? 'dashicons-unlock' : 'dashicons-lock' ); ?>" aria-hidden="true"></span><span><?php echo esc_html( $is_blocked ? 'رفع بلاک IP' : 'بلاک IP' ); ?></span></a>
							<a class="tiamis-action-button is-danger" data-tiamis-confirm="این گفتگو و تمام پیام‌هایش حذف شود؟" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=shcd_tiamis_delete_conversation&conversation=' . $selected->id ), 'shcd_tiamis_delete_' . $selected->id ) ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span>حذف گفتگو</span></a>
						</div>
					</div>

					<?php if ( is_wp_error( $lock ) ) : ?><div class="tiamis-lock-warning"><?php echo esc_html( $lock->get_error_message() ); ?></div><?php else : ?><div class="tiamis-lock-state" data-tiamis-lock-state>این گفتگو برای پاسخگویی شما رزرو شده است.</div><?php endif; ?>

					<nav class="tiamis-workspace-tabs" data-tiamis-workspace-tabs aria-label="بخش‌های گفتگو">
						<button type="button" class="is-active" data-tiamis-workspace-tab="chat"><span class="dashicons dashicons-format-chat" aria-hidden="true"></span><span>گفتگو</span><b><?php echo esc_html( absint( count( $messages ) ) ); ?></b></button>
						<button type="button" data-tiamis-workspace-tab="profile"><span class="dashicons dashicons-id" aria-hidden="true"></span><span>مخاطب</span></button>
						<button type="button" data-tiamis-workspace-tab="followup"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><span>پیگیری</span><b><?php echo esc_html( absint( count( array_filter( $tasks, static function( $task ) { return 'done' !== $task->status; } ) ) ) ); ?></b></button>
						<button type="button" data-tiamis-workspace-tab="notes"><span class="dashicons dashicons-edit-page" aria-hidden="true"></span><span>یادداشت‌ها</span><b><?php echo esc_html( absint( count( $notes ) ) ); ?></b></button>
						<button type="button" data-tiamis-workspace-tab="reports"><span class="dashicons dashicons-warning" aria-hidden="true"></span><span>گزارش‌ها</span><b><?php echo esc_html( absint( count( $reports ) ) ); ?></b></button>
						<button type="button" data-tiamis-workspace-tab="ai"><span class="dashicons dashicons-lightbulb" aria-hidden="true"></span><span>دستیار هوشمند</span></button>
						<button type="button" data-tiamis-workspace-tab="history"><span class="dashicons dashicons-backup" aria-hidden="true"></span><span>تاریخچه</span></button>
					</nav>

					<section class="tiamis-workspace-panel is-active tiamis-chat-panel" data-tiamis-workspace-panel="chat">
						<div class="tiamis-admin-messages" id="tiamis-admin-messages">
							<?php $reported_ids = array_map( 'intval', wp_list_pluck( $reports, 'message_id' ) ); foreach ( $messages as $message ) : $public_message = Tiamis_Chat_Core::public_message_data( $message ); ?>
								<div class="tiamis-admin-message is-<?php echo esc_attr( $message->sender ); ?> <?php echo esc_attr( in_array( (int) $message->id, $reported_ids, true ) ? 'is-reported' : '' ); ?>" data-message-id="<?php echo esc_attr( absint( $message->id ) ); ?>">
									<div><span><?php echo wp_kses_post( nl2br( esc_html( $public_message['body'] ) ) ); ?></span><?php if ( ! empty( $public_message['attachment'] ) ) : ?><a class="tiamis-message-attachment" target="_blank" rel="noopener" href="<?php echo esc_url( $public_message['attachment']['url'] ); ?>">📎 <?php echo esc_html( $public_message['attachment']['name'] ); ?></a><?php endif; ?><small><?php echo esc_html( ( $message->sender_name ? $message->sender_name . ' • ' : '' ) . $message->created_at ); ?><?php if ( in_array( (int) $message->id, $reported_ids, true ) ) : ?> • ⚠ گزارش‌شده<?php endif; ?></small></div>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="tiamis-visitor-typing" data-tiamis-visitor-typing hidden><i></i><span>کاربر در حال تایپ است…</span></div>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="tiamis-reply-form tiamis-reply-dock" data-tiamis-spa-form>
							<input type="hidden" name="action" value="shcd_tiamis_reply"><input type="hidden" name="conversation" value="<?php echo esc_attr( absint( $selected->id ) ); ?>"><?php wp_nonce_field( 'shcd_tiamis_reply_' . $selected->id ); ?>
							<div class="tiamis-reply-toolbar">
								<label><span>کارشناس</span><select name="agent_index" data-tiamis-reply-agent><?php foreach ( $agents as $agent_index => $agent ) : ?><option value="<?php echo esc_attr( absint( $agent_index ) ); ?>" <?php selected( (int) ( $selected->assigned_agent_index ?? 0 ), $agent_index ); ?>><?php echo esc_html( $agent['name'] . ( $agent['role'] ? ' — ' . $agent['role'] : '' ) ); ?></option><?php endforeach; ?></select></label>
								<?php if ( $macros ) : ?><label><span>پاسخ آماده</span><select data-tiamis-macro><option value="">انتخاب کنید…</option><?php foreach ( $macros as $macro ) : ?><option value="<?php echo esc_attr( $macro['message'] ?? '' ); ?>"><?php echo esc_html( ( $macro['shortcut'] ?? '' ) . ' ' . ( $macro['title'] ?? '' ) ); ?></option><?php endforeach; ?></select></label><?php endif; ?>
								<button type="button" class="button" data-tiamis-ai-draft data-conversation="<?php echo esc_attr( absint( $selected->id ) ); ?>">✨ پیشنهاد پاسخ</button>
							</div>
							<div class="tiamis-reply-compose"><textarea id="tiamis-reply-text" name="message" rows="3" required placeholder="پاسخ را بنویسید؛ Enter برای ارسال و Shift+Enter برای خط بعد…"></textarea><button type="submit" class="button button-primary">ارسال</button></div>
						</form>
					</section>

					<section class="tiamis-workspace-panel" data-tiamis-workspace-panel="profile" hidden>
						<div class="tiamis-panel-grid-two">
							<div class="tiamis-compact-card"><h3>مشخصات مخاطب</h3><dl><dt>نام</dt><dd><?php echo esc_html( $selected->visitor_name ?: 'ثبت نشده' ); ?></dd><dt>شماره تماس</dt><dd><?php echo esc_html( $selected->visitor_phone ?: 'ثبت نشده' ); ?></dd><dt>ایمیل</dt><dd><?php echo esc_html( $selected->visitor_email ?: 'ثبت نشده' ); ?></dd><dt>شناسه</dt><dd class="ltr"><?php echo esc_html( $selected->public_id ); ?></dd><dt>IP</dt><dd class="ltr"><?php echo esc_html( $selected->ip_value ?: 'ثبت نشده' ); ?></dd><dt>دستگاه</dt><dd><?php echo esc_html( wp_html_excerpt( $selected->user_agent ?: 'ثبت نشده', 120, '…' ) ); ?></dd></dl></div>
							<div class="tiamis-compact-card"><h3>منبع ورود</h3><dl><dt>صفحه آغاز</dt><dd><?php if ( $selected->page_url ) : ?><a class="ltr" target="_blank" rel="noopener" href="<?php echo esc_url( $selected->page_url ); ?>"><?php echo esc_html( wp_html_excerpt( $selected->page_url, 90, '…' ) ); ?></a><?php else : ?>ثبت نشده<?php endif; ?></dd><dt>ارجاع‌دهنده</dt><dd><?php echo esc_html( $selected->referrer ? wp_html_excerpt( $selected->referrer, 90, '…' ) : 'ثبت نشده' ); ?></dd><dt>کمپین</dt><dd><?php echo esc_html( $selected->utm_campaign ?: 'ثبت نشده' ); ?></dd><dt>منبع کمپین</dt><dd><?php echo esc_html( $selected->utm_source ?: 'ثبت نشده' ); ?></dd><dt>زمان ایجاد</dt><dd><?php echo esc_html( $selected->created_at ); ?></dd><dt>آخرین پیام</dt><dd><?php echo esc_html( $selected->last_message_at ?: 'ثبت نشده' ); ?></dd></dl></div>
						</div>
					</section>

					<section class="tiamis-workspace-panel" data-tiamis-workspace-panel="followup" hidden>
						<div class="tiamis-followup-grid tw-grid tw-gap-4">
							<article class="tiamis-compact-card tiamis-followup-card tiamis-followup-card--assignment">
								<header class="tiamis-card-heading">
									<span class="dashicons dashicons-groups" aria-hidden="true"></span>
									<div><h3>مسئول، بخش و اولویت</h3><p>گفتگو را به کارشناس مناسب بسپارید و سطح رسیدگی را مشخص کنید.</p></div>
								</header>
								<form class="tiamis-form-grid tiamis-assignment-form" data-tiamis-conversation-manage>
									<input type="hidden" name="conversation" value="<?php echo esc_attr( absint( $selected->id ) ); ?>">
									<label class="tiamis-control"><span>کارشناس</span><div class="tiamis-control-shell"><span class="dashicons dashicons-businessperson" aria-hidden="true"></span><select name="agent_index"><?php foreach ( $agents as $agent_index => $agent ) : ?><option value="<?php echo esc_attr( absint( $agent_index ) ); ?>" <?php selected( (int) ( $selected->assigned_agent_index ?? 0 ), $agent_index ); ?>><?php echo esc_html( $agent['name'] ); ?></option><?php endforeach; ?></select></div></label>
									<label class="tiamis-control"><span>بخش</span><div class="tiamis-control-shell"><span class="dashicons dashicons-category" aria-hidden="true"></span><select name="department"><?php foreach ( (array) ( $settings['departments'] ?? array( 'پشتیبانی' ) ) as $department ) : ?><option <?php selected( $selected->department, $department ); ?>><?php echo esc_html( $department ); ?></option><?php endforeach; ?></select></div></label>
									<label class="tiamis-control"><span>اولویت</span><div class="tiamis-control-shell"><span class="dashicons dashicons-flag" aria-hidden="true"></span><select name="priority"><option value="low" <?php selected( $selected->priority, 'low' ); ?>>کم</option><option value="normal" <?php selected( $selected->priority, 'normal' ); ?>>عادی</option><option value="high" <?php selected( $selected->priority, 'high' ); ?>>زیاد</option><option value="urgent" <?php selected( $selected->priority, 'urgent' ); ?>>فوری</option></select></div></label>
									<button class="button button-primary tiamis-gradient-button" type="submit"><span class="dashicons dashicons-saved" aria-hidden="true"></span><span>ذخیره تغییرات</span></button>
								</form>
							</article>

							<article class="tiamis-compact-card tiamis-followup-card tiamis-followup-card--tags">
								<header class="tiamis-card-heading">
									<span class="dashicons dashicons-tag" aria-hidden="true"></span>
									<div><h3>برچسب‌ها</h3><p>برای جستجو و دسته‌بندی سریع‌تر، برچسب مناسب اضافه کنید.</p></div>
								</header>
								<div class="tiamis-tag-list" data-tiamis-tag-list><?php foreach ( $tags as $tag ) : ?><button type="button" data-tag-id="<?php echo esc_attr( absint( $tag->id ) ); ?>" style="--tag-color:<?php echo esc_attr( $tag->color ); ?>"><?php echo esc_html( $tag->name ); ?><span aria-hidden="true">×</span></button><?php endforeach; ?></div>
								<form class="tiamis-inline-form" data-tiamis-tag-form>
									<input type="hidden" name="conversation" value="<?php echo esc_attr( absint( $selected->id ) ); ?>">
									<label class="tiamis-control tiamis-control--grow"><span>برچسب تازه</span><div class="tiamis-control-shell"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><input list="tiamis-existing-tags" name="name" placeholder="برای نمونه: مشتری ویژه"></div></label>
									<datalist id="tiamis-existing-tags"><?php foreach ( $all_tags as $tag ) : ?><option value="<?php echo esc_attr( $tag->name ); ?>"><?php endforeach; ?></datalist>
									<button class="button tiamis-secondary-button" type="submit"><span class="dashicons dashicons-plus" aria-hidden="true"></span><span>افزودن</span></button>
								</form>
							</article>

							<article class="tiamis-compact-card tiamis-followup-card tiamis-task-card">
								<header class="tiamis-card-heading">
									<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
									<div><h3>چک‌لیست پیگیری</h3><p>کارهای بعدی را ثبت کنید تا هیچ مرحله‌ای فراموش نشود.</p></div>
								</header>
								<form class="tiamis-inline-form" data-tiamis-task-form>
									<input type="hidden" name="conversation" value="<?php echo esc_attr( absint( $selected->id ) ); ?>">
									<label class="tiamis-control tiamis-control--grow"><span>کار بعدی</span><div class="tiamis-control-shell"><span class="dashicons dashicons-editor-ul" aria-hidden="true"></span><input name="title" placeholder="برای نمونه: تماس فردا ساعت ۱۰"></div></label>
									<button class="button button-primary tiamis-gradient-button" type="submit"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><span>افزودن</span></button>
								</form>
								<ul class="tiamis-task-list" data-tiamis-task-list><?php if ( ! $tasks ) : ?><li class="is-empty"><span class="dashicons dashicons-clipboard" aria-hidden="true"></span><p>هنوز کاری برای پیگیری ثبت نشده است.</p></li><?php endif; ?><?php foreach ( $tasks as $task ) : ?><li class="<?php echo esc_attr( 'done' === $task->status ? 'is-done' : '' ); ?>"><button type="button" data-task-id="<?php echo esc_attr( absint( $task->id ) ); ?>"><i></i><span><?php echo esc_html( $task->title ); ?></span></button></li><?php endforeach; ?></ul>
							</article>


						</div>
					</section>

					<section class="tiamis-workspace-panel" data-tiamis-workspace-panel="notes" hidden>
						<div class="tiamis-compact-card tiamis-note-panel"><h3>یادداشت داخلی کارشناسان</h3><form data-tiamis-note-form><input type="hidden" name="conversation" value="<?php echo esc_attr( absint( $selected->id ) ); ?>"><textarea name="note" rows="3" placeholder="نکته‌ای که فقط کارشناسان ببینند…"></textarea><button type="submit" class="button button-primary">ثبت یادداشت</button></form><div data-tiamis-note-list><?php if ( ! $notes ) : ?><p class="tiamis-empty-note">هنوز یادداشتی ثبت نشده است.</p><?php endif; ?><?php foreach ( $notes as $note ) : ?><article><strong><?php echo esc_html( $note->display_name ?: 'کارشناس' ); ?></strong><time><?php echo esc_html( $note->created_at ); ?></time><p><?php echo wp_kses_post( nl2br( esc_html( $note->note ) ) ); ?></p></article><?php endforeach; ?></div></div>
					</section>

					<section class="tiamis-workspace-panel" data-tiamis-workspace-panel="reports" hidden>
						<div class="tiamis-compact-card tiamis-report-panel"><h3>گزارش‌های کاربر</h3><?php if ( ! $reports ) : ?><p>پیامی گزارش نشده است.</p><?php else : ?><div class="tiamis-report-list"><?php foreach ( $reports as $report ) : ?><article><b>پیام #<?php echo esc_html( absint( $report->message_id ) ); ?></b><span><?php echo esc_html( $report->reason ); ?></span><time><?php echo esc_html( $report->created_at ); ?></time><?php if ( $report->details ) : ?><p><?php echo esc_html( $report->details ); ?></p><?php endif; ?></article><?php endforeach; ?></div><?php endif; ?></div>
					</section>

					<section class="tiamis-workspace-panel" data-tiamis-workspace-panel="ai" hidden>
						<div class="tiamis-panel-grid-two"><div class="tiamis-compact-card"><h3>خلاصه و تحلیل گفتگو</h3><div data-tiamis-ai-insights><p>برای دریافت خلاصه، احساس کاربر، موضوع و اقدام پیشنهادی، تحلیل را اجرا کنید.</p></div><button type="button" class="button button-primary" data-tiamis-ai-insights-button data-conversation="<?php echo esc_attr( absint( $selected->id ) ); ?>">تحلیل گفتگو</button></div><div class="tiamis-compact-card"><h3>پایگاه دانش</h3><p>پاسخ‌های پیشنهادی می‌توانند از نوشته‌ها، برگه‌ها و محصولات منتشرشده سایت استفاده کنند.</p><button type="button" class="button" data-tiamis-knowledge-sync>همگام‌سازی پایگاه دانش</button><small data-tiamis-knowledge-status></small></div></div>
					</section>

					<section class="tiamis-workspace-panel" data-tiamis-workspace-panel="history" hidden>
						<div class="tiamis-compact-card"><h3>تاریخچه اقدامات</h3><ol class="tiamis-audit-list"><?php if ( ! $audit ) : ?><li>هنوز رویدادی ثبت نشده است.</li><?php endif; ?><?php foreach ( $audit as $item ) : ?><li><time><?php echo esc_html( $item->created_at ); ?></time><b><?php echo esc_html( $item->event_type ); ?></b><span><?php echo esc_html( $item->display_name ?: 'سیستم' ); ?></span></li><?php endforeach; ?></ol></div>
					</section>
				<?php endif; ?>
			</main>
		</div>
		<?php
		self::page_close();
	}

	private static function priority_label( $priority ) {
		$labels = array( 'low' => 'کم', 'normal' => 'عادی', 'high' => 'زیاد', 'urgent' => 'فوری' );
		return isset( $labels[ $priority ] ) ? $labels[ $priority ] : 'عادی';
	}

	private static function status_label( $status ) {
		$labels = array( 'open' => 'باز', 'pending' => 'در انتظار', 'closed' => 'بسته' );
		return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
	}

	public static function reply() {
		self::guard( 'reply' );
		$id = self::request_value( 'conversation', 'POST', 'int' );
		check_admin_referer( 'shcd_tiamis_reply_' . $id );
		$conversation = Tiamis_Chat_Core::get_conversation( $id );
		if ( ! $conversation ) {
			wp_die( esc_html( Tiamis_Chat_I18n::admin_text( 'گفتگو پیدا نشد.' ) ) );
		}
		$message = self::request_value( 'message', 'POST', 'textarea' );
		$user        = wp_get_current_user();
		$agent_index = self::request_value( 'agent_index', 'POST', 'int' );
		$agent       = Tiamis_Chat_Core::agent_profile( '', $agent_index );
		$sender_name = ! empty( $agent['name'] ) ? $agent['name'] : $user->display_name;
		Tiamis_Chat_Core::touch_operator_presence();
		Tiamis_Chat_Core::set_typing( $id, 'operator', false );
		$saved = Tiamis_Chat_Core::add_message(
			$id,
			'admin',
			$message,
			'wordpress',
			$sender_name,
			array(
				'agent_index' => $agent_index,
				'agent_name'  => $sender_name,
				'agent_role'  => isset( $agent['role'] ) ? $agent['role'] : '',
				'agent_photo' => isset( $agent['photo'] ) ? $agent['photo'] : '',
				'wp_user_id'  => (int) $user->ID,
			)
		);
		$notice  = 'reply_error';
		if ( ! is_wp_error( $saved ) ) {
			Tiamis_Chat_Core::queue_outbound( $saved->id, 'operator' );
			$notice = 'replied';
		}
		if ( wp_doing_ajax() ) {
			if ( is_wp_error( $saved ) ) {
				wp_send_json_error( array( 'message' => $saved->get_error_message() ), 400 );
			}
			wp_send_json_success( array( 'message' => Tiamis_Chat_Core::public_message_data( $saved ) ) );
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'shcd-tiamis', 'conversation' => $id, 'tiamis_notice' => $notice ), admin_url( 'admin.php' ) ) );
		exit;
	}


	public static function ajax_save_note() {
		self::guard();
		check_ajax_referer( 'shcd_tiamis_admin_ajax', 'nonce' );
		$id   = self::request_value( 'conversation', 'POST', 'int' );
		$note = self::request_value( 'note', 'POST', 'textarea' );
		if ( ! $id || ! Tiamis_Chat_Core::get_conversation( $id ) ) {
			wp_send_json_error( array( 'message' => 'گفتگو پیدا نشد.' ), 404 );
		}
		$result = Tiamis_Chat_Core::add_note( $id, $note, get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}
		$user = wp_get_current_user();
		wp_send_json_success( array( 'id' => (int) $result, 'note' => wp_strip_all_tags( $note ), 'author' => $user->display_name, 'created_at' => Tiamis_Chat_Core::now() ) );
	}

	public static function ajax_conversation_lock() {
		self::guard( 'reply' );
		check_ajax_referer( 'shcd_tiamis_admin_ajax', 'nonce' );
		$id   = self::request_value( 'conversation', 'POST', 'int' );
		$mode = self::request_value( 'mode', 'POST', 'key', 'acquire' );
		$result = 'release' === $mode ? Tiamis_Chat_Platform::release_lock( $id ) : Tiamis_Chat_Platform::acquire_lock( $id, 50 );
		if ( is_wp_error( $result ) ) wp_send_json_error( array( 'message' => $result->get_error_message() ), 409 );
		wp_send_json_success( array( 'lock' => $result ) );
	}

	public static function ajax_task() {
		self::guard( 'reply' );
		check_ajax_referer( 'shcd_tiamis_admin_ajax', 'nonce' );
		$id      = self::request_value( 'conversation', 'POST', 'int' );
		$task_id = self::request_value( 'task_id', 'POST', 'int' );
		if ( $task_id ) {
			$result = Tiamis_Chat_Platform::toggle_task( $task_id, $id );
			if ( is_wp_error( $result ) ) wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
			wp_send_json_success( array( 'task_id' => $task_id, 'status' => $result ) );
		}
		$title  = self::request_value( 'title', 'POST', 'text' );
		$due_at = self::request_value( 'due_at', 'POST', 'text' );
		$result = Tiamis_Chat_Platform::add_task( $id, $title, $due_at );
		if ( is_wp_error( $result ) ) wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		wp_send_json_success( array( 'task_id' => (int) $result, 'title' => $title ) );
	}

	public static function ajax_tag() {
		self::guard( 'reply' );
		check_ajax_referer( 'shcd_tiamis_admin_ajax', 'nonce' );
		$id     = self::request_value( 'conversation', 'POST', 'int' );
		$remove = self::request_value( 'tag_id', 'POST', 'int' );
		if ( $remove ) {
			Tiamis_Chat_Platform::remove_tag( $id, $remove );
			wp_send_json_success( array( 'removed' => $remove ) );
		}
		$name = self::request_value( 'name', 'POST', 'text' );
		$result = Tiamis_Chat_Platform::assign_tag( $id, $name );
		if ( is_wp_error( $result ) ) wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		wp_send_json_success( array( 'tag_id' => (int) $result, 'name' => $name ) );
	}

	public static function ajax_manage_conversation() {
		self::guard( 'reply' );
		check_ajax_referer( 'shcd_tiamis_admin_ajax', 'nonce' );
		global $wpdb;
		$id = self::request_value( 'conversation', 'POST', 'int' );
		$agents = Tiamis_Chat_Core::agents();
		$agent_index = self::request_value( 'agent_index', 'POST', 'int' );
		$agent = isset( $agents[ $agent_index ] ) ? $agents[ $agent_index ] : $agents[0];
		$priority = self::request_value( 'priority', 'POST', 'key', 'normal' );
		$priority = in_array( $priority, array( 'low', 'normal', 'high', 'urgent' ), true ) ? $priority : 'normal';
		$department = self::request_value( 'department', 'POST', 'text' );
		$wpdb->update( Tiamis_Chat_DB::table( 'conversations' ), array( 'assigned_agent_index' => $agent_index, 'assigned_to' => absint( $agent['wp_user_id'] ?? 0 ), 'department' => $department, 'priority' => $priority, 'updated_at' => Tiamis_Chat_Core::now() ), array( 'id' => $id ) );
		Tiamis_Chat_Platform::audit( 'conversation_updated', $id, array( 'agent_index' => $agent_index, 'department' => $department, 'priority' => $priority ) );
		wp_send_json_success( array( 'message' => 'تغییرات گفتگو ذخیره شد.' ) );
	}

	public static function ajax_ai_insights() {
		self::guard( 'reply' );
		check_ajax_referer( 'shcd_tiamis_admin_ajax', 'nonce' );
		$id = self::request_value( 'conversation', 'POST', 'int' );
		$result = Tiamis_Chat_Integrations::ai_insights( $id );
		if ( is_wp_error( $result ) ) wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		wp_send_json_success( array( 'insights' => $result ) );
	}

	public static function ajax_knowledge_sync() {
		self::guard( 'settings' );
		check_ajax_referer( 'shcd_tiamis_admin_ajax', 'nonce' );
		$count = Tiamis_Chat_Platform::sync_knowledge_from_posts();
		wp_send_json_success( array( 'count' => (int) $count, 'message' => sprintf( '%d محتوای منتشرشده همگام شد.', $count ) ) );
	}

	public static function export_conversation() {
		self::guard();
		$id = self::request_value( 'conversation', 'GET', 'int' );
		check_admin_referer( 'shcd_tiamis_export_' . $id );
		$conversation = Tiamis_Chat_Core::get_conversation( $id );
		if ( ! $conversation ) {
			wp_die( 'گفتگو پیدا نشد.' );
		}
		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="tiamis-conversation-' . $id . '.csv"' );
		echo "\xEF\xBB\xBF"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- UTF-8 BOM for a CSV download.
		echo self::csv_line( array( 'شناسه گفتگو', 'نام', 'ایمیل', 'شماره تماس', 'فرستنده', 'نام فرستنده', 'پیام', 'تاریخ' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Deliberate CSV response.
		foreach ( Tiamis_Chat_Core::get_messages( $id, 0, 10000 ) as $message ) {
			echo self::csv_line( array( $conversation->public_id, $conversation->visitor_name, $conversation->visitor_email, $conversation->visitor_phone, $message->sender, $message->sender_name, $message->body, $message->created_at ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Deliberate CSV response.
		}
		exit;
	}

	/**
	 * Build one RFC 4180 compatible CSV row without opening a PHP stream.
	 *
	 * @param array $cells Row values.
	 * @return string
	 */
	private static function csv_line( $cells ) {
		$encoded = array_map(
			static function ( $cell ) {
				$value = str_replace( array( "\r\n", "\r" ), "\n", (string) $cell );
				return '"' . str_replace( '"', '""', $value ) . '"';
			},
			(array) $cells
		);
		return implode( ',', $encoded ) . "\r\n";
	}

	public static function delete_conversation() {
		self::guard();
		$id = self::request_value( 'conversation', 'GET', 'int' );
		check_admin_referer( 'shcd_tiamis_delete_' . $id );
		Tiamis_Chat_Core::delete_conversation( $id );
		wp_safe_redirect( admin_url( 'admin.php?page=shcd-tiamis' ) );
		exit;
	}

	public static function toggle_block() {
		self::guard();
		$id = self::request_value( 'conversation', 'GET', 'int' );
		check_admin_referer( 'shcd_tiamis_toggle_block_' . $id );
		$conversation = Tiamis_Chat_Core::get_conversation( $id );
		if ( $conversation ) {
			if ( Tiamis_Chat_Core::conversation_is_blocked( $conversation ) ) {
				Tiamis_Chat_Core::unblock_conversation_ip( $id );
			} else {
				Tiamis_Chat_Core::block_conversation_ip( $id, 'بلاک‌شده از صندوق گفتگوها' );
			}
		}
		wp_safe_redirect( admin_url( 'admin.php?page=shcd-tiamis&conversation=' . $id ) );
		exit;
	}

	public static function change_status() {
		self::guard();
		global $wpdb;
		$id = self::request_value( 'conversation', 'GET', 'int' );
		check_admin_referer( 'shcd_tiamis_status_' . $id );
		$status = self::request_value( 'status', 'GET', 'key', 'open' );
		$status = in_array( $status, array( 'open', 'pending', 'closed' ), true ) ? $status : 'open';
		$wpdb->update( Tiamis_Chat_DB::table( 'conversations' ), array( 'status' => $status, 'updated_at' => Tiamis_Chat_Core::now() ), array( 'id' => $id ) );
		wp_safe_redirect( add_query_arg( array( 'page' => 'shcd-tiamis', 'conversation' => $id, 'tiamis_notice' => 'status' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function ajax_ai_draft() {
		self::guard();
		check_ajax_referer( 'shcd_tiamis_admin_ajax', 'nonce' );
		$id     = self::request_value( 'conversation', 'POST', 'int' );
		$result = Tiamis_Chat_Integrations::ai_generate( $id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}
		wp_send_json_success( array( 'reply' => $result ) );
	}

	public static function ajax_poll_admin() {
		self::guard();
		check_ajax_referer( 'shcd_tiamis_admin_ajax', 'nonce' );
		$id           = self::request_value( 'conversation', 'POST', 'int' );
		$after        = self::request_value( 'after', 'POST', 'int' );
		$conversation = Tiamis_Chat_Core::get_conversation( $id );
		if ( ! $conversation ) {
			wp_send_json_error( array( 'message' => 'گفتگو پیدا نشد.' ), 404 );
		}
		Tiamis_Chat_Core::touch_operator_presence();
		$items = array_map( array( 'Tiamis_Chat_Core', 'public_message_data' ), Tiamis_Chat_Core::get_messages( $id, $after, 100 ) );
		if ( $items ) {
			Tiamis_Chat_Core::mark_seen_by_admin( $id );
		}
		wp_send_json_success( array( 'messages' => $items, 'visitor_typing' => Tiamis_Chat_Core::is_typing( $id, 'visitor' ), 'conversation' => Tiamis_Chat_Core::public_conversation_data( Tiamis_Chat_Core::get_conversation( $id ) ) ) );
	}

	public static function ajax_admin_typing() {
		self::guard();
		check_ajax_referer( 'shcd_tiamis_admin_ajax', 'nonce' );
		$id = self::request_value( 'conversation', 'POST', 'int' );
		if ( ! $id || ! Tiamis_Chat_Core::get_conversation( $id ) ) {
			wp_send_json_error( array( 'message' => 'گفتگو پیدا نشد.' ), 404 );
		}
		Tiamis_Chat_Core::touch_operator_presence();
		$agent_index = self::request_value( 'agent_index', 'POST', 'int' );
		$agent       = Tiamis_Chat_Core::agent_profile( '', $agent_index );
		Tiamis_Chat_Core::set_typing(
			$id,
			'operator',
			'1' === self::request_value( 'typing', 'POST', 'key', '0' ),
			8,
			array(
				'agent_index' => $agent_index,
				'agent_name'  => isset( $agent['name'] ) ? $agent['name'] : '',
				'agent_role'  => isset( $agent['role'] ) ? $agent['role'] : '',
				'agent_photo' => isset( $agent['photo'] ) ? $agent['photo'] : '',
			)
		);
		wp_send_json_success();
	}

	public static function ajax_operator_heartbeat() {
		self::guard();
		check_ajax_referer( 'shcd_tiamis_admin_ajax', 'nonce' );
		Tiamis_Chat_Core::touch_operator_presence();
		wp_send_json_success( array( 'online' => true ) );
	}

	public static function ajax_poll_notifications() {
		if ( ! Tiamis_Chat_Platform::can_inbox() ) {
			wp_send_json_error( array( 'message' => 'دسترسی مجاز نیست.' ), 403 );
		}
		check_ajax_referer( 'shcd_tiamis_admin_ajax', 'nonce' );
		global $wpdb;
		$after         = self::request_value( 'after', 'POST', 'int' );
		$messages      = Tiamis_Chat_DB::table( 'messages' );
		$conversations = Tiamis_Chat_DB::table( 'conversations' );
		$latest        = absint( $wpdb->get_var( $wpdb->prepare( "SELECT MAX(id) FROM %i WHERE sender = 'visitor'", $messages ) ) );
		$items         = array();
		if ( $after > 0 && $latest > $after ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT m.id, m.conversation_id, m.body, m.created_at, c.visitor_name FROM %i m LEFT JOIN %i c ON c.id = m.conversation_id WHERE m.sender = 'visitor' AND m.id > %d ORDER BY m.id ASC LIMIT 50",
					$messages,
					$conversations,
					$after
				)
			);
			foreach ( $rows as $row ) {
				$items[] = array(
					'id'              => (int) $row->id,
					'type'            => 'chat',
					'conversation_id' => (int) $row->conversation_id,
					'visitor_name'    => $row->visitor_name ? sanitize_text_field( $row->visitor_name ) : Tiamis_Chat_I18n::admin_text( 'بازدیدکننده' ),
					'body'            => function_exists( 'mb_substr' ) ? mb_substr( wp_strip_all_tags( $row->body ), 0, 180 ) : substr( wp_strip_all_tags( $row->body ), 0, 180 ),
					'created_at'      => $row->created_at,
					'url'             => esc_url_raw( admin_url( 'admin.php?page=shcd-tiamis&conversation=' . absint( $row->conversation_id ) ) ),
				);
			}
		}
		wp_send_json_success( array( 'latest_id' => $latest, 'messages' => $items, 'counts' => self::unread_counts() ) );
	}

	public static function ajax_set_language() {
		self::guard();
		check_ajax_referer( 'shcd_tiamis_admin_ajax', 'nonce' );
		$language                   = Tiamis_Chat_I18n::normalize( self::request_value( 'language', 'POST', 'key', 'fa' ) );
		$settings                   = Tiamis_Chat_Core::settings();
		$settings['admin_language'] = $language;
		Tiamis_Chat_Core::update_settings( $settings );
		wp_send_json_success( array( 'language' => $language, 'direction' => Tiamis_Chat_I18n::direction( $language ) ) );
	}

	public static function ajax_db_action() {
		self::guard();
		check_ajax_referer( 'shcd_tiamis_admin_ajax', 'nonce' );
		$operation = self::request_value( 'operation', 'POST', 'key' );
		if ( 'optimize' === $operation ) {
			$result = Tiamis_Chat_DB::optimize_tables();
		} elseif ( 'repair' === $operation ) {
			$result = Tiamis_Chat_DB::repair_auto_increment();
		} elseif ( 'purge' === $operation ) {
			$confirmation = self::request_value( 'confirmation', 'POST', 'text' );
			if ( 'RESET' !== $confirmation ) {
				wp_send_json_error( array( 'message' => 'عبارت تأیید صحیح نیست.' ), 400 );
			}
			$result = Tiamis_Chat_DB::purge_all_data();
		} else {
			wp_send_json_error( array( 'message' => 'عملیات ناشناخته است.' ), 400 );
		}
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
		}
		wp_send_json_success( array( 'stats' => Tiamis_Chat_DB::table_stats() ) );
	}

	public static function render_tickets_pro() {
		self::guard();
		self::page_open( 'tickets' );
		self::header(
			Tiamis_Chat_I18n::admin_text( 'سامانه تیکتینگ هوشمند' ),
			Tiamis_Chat_I18n::admin_text( 'این قابلیت در نسخه PRO موجود است.' )
		);
		self::render_pro_locked_panel( 'ticketing', '۰۱' );
		self::page_close();
	}

	public static function render_analytics() {
		self::guard( 'reports' );
		self::page_open( 'analytics' );
		self::header( 'نقشه حرارتی تعامل کاربران', 'تحلیل بصری کلیک‌ها و رفتار کاربران در نسخه حرفه‌ای تیامیس ارائه می‌شود.' );
		self::render_pro_locked_panel( 'heatmap', '۰۱' );
		self::page_close();
	}

	public static function render_settings() {
		self::guard( 'settings' );
		$s           = Tiamis_Chat_Core::settings();
		$s['bubble_style'] = 'gradient';
		$s['bubble_animation'] = 'tada';
		$s['bubble_random_animation'] = '0';
		$s['ai_provider'] = 'cloudflare';
		$s['bale_enabled'] = '0';
		$s['bale_customer_enabled'] = '0';
		$telegram_webhook_url = rest_url( 'shcd-tiamis/v1/telegram/webhook' );
		$bale_webhook_url = add_query_arg( 'key', (string) $s['bale_webhook_secret'], rest_url( 'shcd-tiamis/v1/bale/webhook' ) );
		$telegram_poll_url = add_query_arg( 'key', (string) $s['telegram_poll_secret'], rest_url( 'shcd-tiamis/v1/telegram/poll' ) );
		$macro_lines = array();
		foreach ( (array) ( $s['macros'] ?? array() ) as $macro ) {
			$macro_lines[] = ( $macro['shortcut'] ?? '' ) . ' | ' . ( $macro['title'] ?? '' ) . ' | ' . ( $macro['message'] ?? '' );
		}
		$macro_text = implode( "\n", $macro_lines );
		$tabs = array(
			'widget'       => array( '۰۱', 'ویجت و تجربه کاربر', 'ظاهر، زبان و اطلاعات آغاز گفتگو' ),
			'bots'         => array( '۰۲', 'ربات', 'اتصال دوطرفه تلگرام و بله' ),
			'notification' => array( '۰۳', 'اعلان‌ها', 'اعلان مرورگر و OneSignal' ),
			'ai'           => array( '۰۴', 'هوش مصنوعی', 'پاسخ پیشنهادی، پایگاه دانش و کنترل کیفیت' ),
			'team'         => array( '۰۵', 'تیم و بلادرنگ', 'تخصیص، SLA، صف آفلاین، WebSocket و PWA' ),
			'ticketing'    => array( '۰۶', 'تیکتینگ هوشمند', 'پرتال ثبت و پیگیری تیکت' ),
			'privacy'      => array( '۰۷', 'حریم خصوصی', 'IP، رضایت و مدت نگهداری داده‌ها' ),
			'api'          => array( '۰۸', 'API و Webhook', 'اتصال CRM و ابزارهای خارجی' ),
			'guide'        => array( '۰۹', 'راهنمای راه‌اندازی', 'آموزش دریافت کلیدها و اتصال سرویس‌ها' ),
			'developer'    => array( '۱۰', 'توسعه‌دهنده', 'وضعیت سیستم، فونت و پایگاه داده' ),
		);
		self::page_open( 'settings' );
		self::header( 'تنظیمات تیامیس', 'تنظیمات در یک رابط سریع، مرحله‌ای و بدون بارگذاری کامل صفحه مدیریت می‌شوند.' );
		?>
		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="tiamis-settings-form" data-tiamis-spa-form>
			<input type="hidden" name="action" value="shcd_tiamis_save_settings"><?php wp_nonce_field( 'shcd_tiamis_save_settings' ); ?>
			<div class="tiamis-settings-shell">
				<aside class="tiamis-tab-rail" aria-label="بخش‌های تنظیمات">
					<div class="tiamis-tab-rail-intro"><span>TIAMIS</span><b>مرکز کنترل</b><small>نسخه <?php echo esc_html( TIAMIS_CHAT_VERSION ); ?></small></div>
					<nav><?php foreach ( $tabs as $key => $tab ) : $is_pro = in_array( $key, array( 'ticketing', 'api' ), true ); ?>
						<button type="button" class="<?php echo esc_attr( $is_pro ? 'is-pro-locked' : '' ); ?>" data-tiamis-tab="<?php echo esc_attr( $key ); ?>"><em><?php echo esc_html( $tab[0] ); ?></em><span><b><?php echo esc_html( $tab[1] ); ?></b><small><?php echo esc_html( $tab[2] ); ?></small></span><?php if ( $is_pro ) : ?><strong class="tiamis-pro-badge">PRO</strong><?php else : ?><i>⌄</i><?php endif; ?></button>
					<?php endforeach; ?></nav>
				</aside>
				<div class="tiamis-settings-main">
					<label class="tiamis-mobile-tab"><span>بخش تنظیمات</span><select data-tiamis-tab-select><?php foreach ( $tabs as $key => $tab ) : $is_pro = in_array( $key, array( 'ticketing', 'api' ), true ); ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $tab[1] . ( $is_pro ? ' — PRO' : '' ) ); ?></option><?php endforeach; ?></select></label>

					<section class="tiamis-tab-panel" data-tiamis-tab-panel="widget">
						<div class="tiamis-panel-heading"><div><span>۰۱</span><h2>ویجت و تجربه کاربر</h2><p>نحوه نمایش چت، اطلاعات اولیه و متن‌های ارتباط با کاربر را تنظیم کنید.</p></div></div>
						<div class="tiamis-settings-grid">
							<?php self::checkbox( 'enabled', 'فعال‌سازی سامانه چت', $s, 'زیرساخت گفتگو و شورت‌کد چت فعال باشد.' ); ?>
							<?php self::checkbox( 'bubble_chat_enabled', 'نمایش Bubble Chat شناور', $s, 'دکمه شناور چت در صفحه‌های عمومی نمایش داده شود؛ شورت‌کد چت مستقل باقی می‌ماند.' ); ?>
							<?php self::checkbox( 'profile_required', 'دریافت نام پیش از شروع', $s, 'کاربر پیش از ارسال نخستین پیام، نام خود را وارد کند.' ); ?>
							<?php self::checkbox( 'collect_email', 'دریافت ایمیل', $s, 'فیلد ایمیل در فرم آغاز گفتگو نمایش داده شود.' ); ?>
							<?php self::checkbox( 'collect_phone', 'دریافت شماره تماس', $s, 'فیلد شماره تماس در فرم آغاز گفتگو نمایش داده شود.' ); ?>
							<?php self::checkbox( 'consent_required', 'الزام تأیید حریم خصوصی', $s, 'تا پیش از تأیید کاربر، نشست گفتگو ایجاد نشود.' ); ?>
							<?php self::checkbox( 'browser_notifications', 'اعلان مرورگر برای پاسخ جدید', $s, 'هنگام بازبودن مرورگر، پاسخ جدید به کاربر اطلاع داده شود.' ); ?>
							<label>زبان ویجت<select name="settings[widget_language]"><option value="fa" <?php selected( $s['widget_language'], 'fa' ); ?>>فارسی</option><option value="ar" <?php selected( $s['widget_language'], 'ar' ); ?>>العربية</option><option value="en" <?php selected( $s['widget_language'], 'en' ); ?>>English</option></select></label>
							<?php self::field( 'widget_title', 'عنوان ویجت', $s ); ?><?php self::field( 'widget_subtitle', 'توضیح کوتاه زیر عنوان', $s ); ?>
							<?php self::field( 'button_label', 'متن دکمه شناور', $s ); ?>
							<div class="tiamis-span-2"><?php self::render_agents_fields( $s ); ?></div>
							<label>جایگاه ویجت<select name="settings[position]"><option value="left" <?php selected( $s['position'], 'left' ); ?>>چپ</option><option value="right" <?php selected( $s['position'], 'right' ); ?>>راست</option></select></label>
							<label>پوسته رنگی<select name="settings[theme]"><option value="violet" <?php selected( $s['theme'], 'violet' ); ?>>بنفش تیامیس</option><option value="blue" <?php selected( $s['theme'], 'blue' ); ?>>آبی</option><option value="green" <?php selected( $s['theme'], 'green' ); ?>>سبز</option><option value="dark" <?php selected( $s['theme'], 'dark' ); ?>>تیره</option></select></label>
							<label>طرح دکمه گفتگو<select name="settings[bubble_style]" data-tiamis-bubble-style><option value="gradient" selected>گرادیان تیامیس</option><option value="glass" disabled>شیشه‌ای — PRO</option><option value="neon" disabled>نئون — PRO</option><option value="soft" disabled>نئومورفیک نرم — PRO</option><option value="minimal" disabled>مینیمال — PRO</option><option value="telegram" disabled>تلگرام — PRO</option><option value="cosmic" disabled>کهکشانی — PRO</option><option value="liquid" disabled>مایع — PRO</option><option value="outline" disabled>خطی — PRO</option><option value="crystal" disabled>کریستالی — PRO</option><option value="square" disabled>مربع مدرن — PRO</option><option value="pill" disabled>کپسولی — PRO</option></select><small class="tiamis-ai-pro-note"><?php echo esc_html( Tiamis_Chat_I18n::admin_text( 'در نسخه رایگان فقط «گرادیان تیامیس» فعال است. سایر طرح‌ها در نسخه حرفه‌ای ارائه می‌شوند.' ) ); ?> <strong class="tiamis-pro-badge">PRO</strong></small></label>
							<label>انیمیشن دکمه گفتگو<select name="settings[bubble_animation]" data-tiamis-bubble-animation><option value="tada" selected>تادا</option><option value="random" disabled>انیمیشن تصادفی — PRO</option><option value="none" disabled>بدون انیمیشن — PRO</option><option value="float" disabled>شناور — PRO</option><option value="pulse" disabled>تپش — PRO</option><option value="bounce" disabled>پرش — PRO</option><option value="shake" disabled>لرزش — PRO</option><option value="swing" disabled>تاب‌خوردن — PRO</option><option value="wobble" disabled>نوسان — PRO</option><option value="heartbeat" disabled>ضربان قلب — PRO</option><option value="jelly" disabled>ژله‌ای — PRO</option><option value="rotate" disabled>چرخش — PRO</option><option value="orbit" disabled>مداری — PRO</option><option value="glow" disabled>درخشش — PRO</option><option value="wave" disabled>موج — PRO</option><option value="pop" disabled>پاپ — PRO</option><option value="slide" disabled>لغزش — PRO</option></select><small class="tiamis-ai-pro-note"><?php echo esc_html( Tiamis_Chat_I18n::admin_text( 'در نسخه رایگان فقط انیمیشن «تادا» فعال است. سایر انیمیشن‌ها در نسخه حرفه‌ای ارائه می‌شوند.' ) ); ?> <strong class="tiamis-pro-badge">PRO</strong></small></label>
							<div class="tiamis-bubble-preview-card tiamis-span-2"><div><b>پیش‌نمایش دکمه گفتگو</b><small>طرح و انیمیشن انتخابی بدون ذخیره‌سازی، همین‌جا نمایش داده می‌شود.</small></div><button type="button" data-tiamis-bubble-preview class="tiamis-bubble-live-preview"><span>💬</span><em><?php echo esc_html( $s['button_label'] ); ?></em></button></div>
							<?php self::textarea( 'welcome_message', 'پیام خوشامدگویی', $s ); ?><?php self::textarea( 'consent_text', 'متن رضایت حریم خصوصی', $s ); ?>
						</div>
					</section>

					<section class="tiamis-tab-panel" data-tiamis-tab-panel="bots" hidden>
						<div class="tiamis-panel-heading"><div><span>۰۲</span><h2>اتصال ربات‌ها</h2><p>پیام‌های سایت را در تلگرام یا بله دریافت کنید و پاسخ را با Reply به همان گفتگو برگردانید.</p></div></div>
						<div class="tiamis-bot-tabs" data-tiamis-bot-tabs>
							<div class="tiamis-bot-tab-buttons" role="tablist"><button type="button" class="is-active" data-tiamis-bot-tab="telegram">تلگرام</button><button type="button" class="is-pro-locked" data-tiamis-bot-tab="bale">بله <strong class="tiamis-pro-badge">PRO</strong></button></div>
							<div class="tiamis-bot-panel is-active" data-tiamis-bot-panel="telegram">
								<div class="tiamis-settings-grid">
									<?php self::checkbox( 'telegram_enabled', 'فعال‌سازی تلگرام', $s, 'پیام‌های سایت برای کارشناسان تلگرام فرستاده شود.' ); ?>
									<?php self::checkbox( 'telegram_customer_enabled', 'گفتگوی مستقیم با ربات تلگرام', $s, 'کاربران تلگرام بتوانند مستقیماً به ربات پیام بدهند.' ); ?>
									<label>روش دریافت پیام‌های تلگرام<select name="settings[telegram_update_mode]" data-tiamis-telegram-mode><option value="polling" <?php selected( $s['telegram_update_mode'], 'polling' ); ?>>دریافت دوره‌ای؛ پیشنهادشده برای هاست ایران</option><option value="webhook" <?php selected( $s['telegram_update_mode'], 'webhook' ); ?>>Webhook؛ مناسب سرور دارای دسترسی مستقیم</option></select><small>در حالت دریافت دوره‌ای، وردپرس پیام‌های تازه را از تلگرام می‌گیرد و نیازی به اتصال ورودی تلگرام به هاست نیست.</small></label>
									<label data-tiamis-telegram-poll-control>فاصله دریافت دوره‌ای<select name="settings[telegram_poll_interval]"><option value="30" <?php selected( (int) $s['telegram_poll_interval'], 30 ); ?>>هر ۳۰ ثانیه</option><option value="60" <?php selected( (int) $s['telegram_poll_interval'], 60 ); ?>>هر ۱ دقیقه</option><option value="120" <?php selected( (int) $s['telegram_poll_interval'], 120 ); ?>>هر ۲ دقیقه</option></select><small>۳۰ ثانیه برای پاسخ‌گویی سریع‌تر مناسب است؛ روی هاست کم‌منبع می‌توانید بازه را بیشتر کنید.</small></label>
									<label>توکن BotFather<input type="password" name="telegram_bot_token" value="" autocomplete="new-password" placeholder="برای نگه‌داشتن مقدار فعلی، این کادر را خالی بگذارید"><small class="tiamis-field-source">ساخت ربات و دریافت توکن: <a href="https://t.me/BotFather" target="_blank" rel="noopener noreferrer">t.me/BotFather ↗</a></small></label>
									<?php self::field( 'telegram_admin_chat_ids', 'Chat ID کارشناسان، جداشده با ویرگول', $s, 'ltr' ); ?>
									<?php self::field( 'telegram_webhook_secret', 'کلید بررسی Webhook تلگرام', $s, 'ltr' ); ?>
									<?php self::textarea( 'telegram_customer_welcome', 'پیام خوشامد کاربران تلگرام', $s, 'tiamis-span-2' ); ?>
									<label class="tiamis-span-2">نشانی Webhook تلگرام<input class="ltr" type="text" readonly value="<?php echo esc_attr( $telegram_webhook_url ); ?>"><small>این نشانی فقط در حالت Webhook استفاده می‌شود.</small></label>
									<?php self::field( 'telegram_api_base', 'نشانی پایه Telegram Bot API یا پراکسی اختصاصی', $s, 'url ltr' ); ?>
									<label class="tiamis-span-2" data-tiamis-telegram-poll-control>نشانی اجرای دریافت دوره‌ای<input class="ltr" type="text" readonly value="<?php echo esc_attr( $telegram_poll_url ); ?>"><small>برای اجرای پایدار روی سایت کم‌ترافیک، این نشانی را در Cron هاست هر یک دقیقه فراخوانی کنید.</small></label>
								</div>
								<div data-tiamis-telegram-webhook-control><?php self::standalone_checkbox( 'register_telegram_webhook', 'پس از ذخیره، Webhook تلگرام ثبت شود.' ); ?></div>
							</div>
							<div class="tiamis-bot-panel" data-tiamis-bot-panel="bale" hidden>
								<div class="tiamis-inline-pro-lock">
									<div class="tiamis-inline-pro-preview" aria-hidden="true">
										<fieldset class="tiamis-pro-preview-fieldset" disabled>
											<div class="tiamis-settings-grid">
												<?php self::checkbox( 'bale_enabled', 'فعال‌سازی بله', $s, 'پیام‌های سایت برای کارشناسان بله فرستاده شود.' ); ?>
												<?php self::checkbox( 'bale_customer_enabled', 'گفتگوی مستقیم با ربات بله', $s, 'کاربران بله بتوانند مستقیماً به ربات پیام بدهند.' ); ?>
												<label>توکن ربات بله<input type="password" name="bale_bot_token" value="" autocomplete="new-password" placeholder="برای نگه‌داشتن مقدار فعلی، این کادر را خالی بگذارید"><small class="tiamis-field-source">راهنمای ساخت و اتصال ربات: <a href="https://docs.bale.ai/" target="_blank" rel="noopener noreferrer">docs.bale.ai ↗</a></small></label>
												<?php self::field( 'bale_admin_chat_ids', 'Chat ID کارشناسان بله، جداشده با ویرگول', $s, 'ltr' ); ?>
												<?php self::field( 'bale_webhook_secret', 'کلید اختصاصی Webhook بله', $s, 'ltr' ); ?>
												<?php self::textarea( 'bale_customer_welcome', 'پیام خوشامد کاربران بله', $s, 'tiamis-span-2' ); ?>
												<label class="tiamis-span-2">نشانی Webhook بله<input class="ltr" type="text" readonly value="<?php echo esc_attr( $bale_webhook_url ); ?>"></label>
											</div>
											<?php self::standalone_checkbox( 'register_bale_webhook', 'پس از ذخیره، Webhook بله ثبت شود.' ); ?>
										</fieldset>
									</div>
									<div class="tiamis-inline-pro-overlay" role="note">
										<strong class="tiamis-pro-badge">PRO</strong>
										<span class="dashicons dashicons-lock" aria-hidden="true"></span>
										<h3><?php echo esc_html( Tiamis_Chat_I18n::admin_text( 'ربات بله' ) ); ?></h3>
										<p><?php echo esc_html( Tiamis_Chat_I18n::admin_text( 'این قابلیت در نسخه PRO موجود است.' ) ); ?></p>
										<div class="tiamis-pro-feature-list"><span><?php echo esc_html( Tiamis_Chat_I18n::admin_text( 'گفتگوی مستقیم کاربران بله' ) ); ?></span><span><?php echo esc_html( Tiamis_Chat_I18n::admin_text( 'Webhook اختصاصی بله' ) ); ?></span><span><?php echo esc_html( Tiamis_Chat_I18n::admin_text( 'ارسال و دریافت دوطرفه پیام‌ها' ) ); ?></span></div>
									</div>
								</div>
							</div>
						</div>
					</section>

					<section class="tiamis-tab-panel" data-tiamis-tab-panel="notification" hidden>
						<div class="tiamis-panel-heading"><div><span>۰۳</span><h2>اعلان‌های مرورگر</h2><p>اعلان داخلی برای صفحه باز و Web Push پایدار برای بازگشت کاربر را مدیریت کنید.</p></div></div>
						<div class="tiamis-settings-grid">
							<?php self::checkbox( 'onesignal_enabled', 'فعال‌سازی Web Push پایدار', $s, 'پاسخ جدید پس از بسته‌شدن صفحه نیز به دستگاه مشترک ارسال شود.' ); ?>
							<?php self::field( 'onesignal_app_id', 'OneSignal App ID', $s, 'ltr' ); ?>
							<label>OneSignal REST API Key<input type="password" name="onesignal_rest_api_key" value="" autocomplete="new-password" placeholder="برای نگه‌داشتن مقدار فعلی، خالی بگذارید"><small class="tiamis-field-source">ساخت برنامه و دریافت کلیدها: <a href="https://dashboard.onesignal.com/" target="_blank" rel="noopener noreferrer">dashboard.onesignal.com ↗</a></small></label>
							<label class="tiamis-span-2">نشانی Service Worker<input class="ltr" type="text" readonly value="<?php echo esc_attr( TIAMIS_CHAT_URL . 'assets/js/OneSignalSDKWorker.js' ); ?>"></label>
						</div><div class="tiamis-info-box">در OneSignal، روش اتصال را روی Custom Code قرار دهید. اگر افزونه رسمی OneSignal فعال است، راه‌اندازی هم‌زمان این گزینه ممکن است باعث تداخل شود.</div>
					</section>

					<section class="tiamis-tab-panel" data-tiamis-tab-panel="ai" hidden>
						<div class="tiamis-panel-heading"><div><span>۰۴</span><h2>دستیار هوش مصنوعی</h2><p>حالت پیشنهادی فقط پیش‌نویس می‌سازد؛ حالت خودکار مستقیماً به کاربر پاسخ می‌دهد.</p></div></div>
						<div class="tiamis-settings-grid">
							<label>شیوه پاسخ‌گویی<select name="settings[ai_mode]"><option value="off" <?php selected( $s['ai_mode'], 'off' ); ?>>غیرفعال</option><option value="draft" <?php selected( $s['ai_mode'], 'draft' ); ?>>پیشنهاد برای اپراتور</option><option value="auto" <?php selected( $s['ai_mode'], 'auto' ); ?>>پاسخ خودکار</option></select></label>
							<label>سیاست پاسخ خودکار نسبت به اپراتور<select name="settings[ai_operator_policy]"><option value="offline_only" <?php selected( $s['ai_operator_policy'], 'offline_only' ); ?>>فقط وقتی اپراتور آفلاین است</option><option value="always" <?php selected( $s['ai_operator_policy'], 'always' ); ?>>همیشه؛ مستقل از حضور اپراتور</option><option value="never" <?php selected( $s['ai_operator_policy'], 'never' ); ?>>هرگز پاسخ خودکار ندهد</option></select></label>
							<label>تشخیص وضعیت اپراتور<select name="settings[operator_presence_mode]"><option value="auto" <?php selected( $s['operator_presence_mode'], 'auto' ); ?>>خودکار؛ بر اساس بازبودن صندوق گفتگوها</option><option value="online" <?php selected( $s['operator_presence_mode'], 'online' ); ?>>همیشه آنلاین در نظر بگیر</option><option value="offline" <?php selected( $s['operator_presence_mode'], 'offline' ); ?>>همیشه آفلاین در نظر بگیر</option></select></label>
							<?php self::field( 'operator_presence_timeout', 'مهلت تشخیص آفلاین‌شدن، به ثانیه', $s, 'ltr', 'number', '30', '600', '1' ); ?>
							<label>ارائه‌دهنده هوش مصنوعی<select name="settings[ai_provider]" data-tiamis-ai-provider><option value="cloudflare" selected>Cloudflare Workers AI</option><option value="openrouter" disabled>OpenRouter — PRO</option><option value="openai_compatible" disabled>OpenAI-compatible API — PRO</option><option value="ollama" disabled>Ollama — PRO</option></select><small class="tiamis-field-source tiamis-ai-pro-note"><?php echo esc_html( Tiamis_Chat_I18n::admin_text( 'فقط Cloudflare در نسخه رایگان فعال است.' ) ); ?></small><span class="tiamis-pro-pill-list"><span>OpenRouter <strong class="tiamis-pro-badge">PRO</strong></span><span>OpenAI-compatible API <strong class="tiamis-pro-badge">PRO</strong></span><span>Ollama <strong class="tiamis-pro-badge">PRO</strong></span></span></label>
							<div class="tiamis-provider-fields tiamis-span-2" data-tiamis-provider-group="cloudflare"><div class="tiamis-settings-grid"><?php self::field( 'cloudflare_account_id', 'Cloudflare Account ID', $s, 'ltr' ); ?><?php self::field( 'cloudflare_model', 'مدل Cloudflare Workers AI', $s, 'ltr' ); ?><label>Cloudflare API Token<input type="password" name="cloudflare_api_token" value="" autocomplete="new-password" placeholder="برای نگه‌داشتن مقدار فعلی، خالی بگذارید"><small class="tiamis-field-source">دریافت Account ID و توکن Workers AI: <a href="https://dash.cloudflare.com/" target="_blank" rel="noopener noreferrer">dash.cloudflare.com ↗</a></small></label></div></div>
							<?php self::field( 'ai_temperature', 'میزان خلاقیت پاسخ', $s, 'ltr', 'number', '0.0', '1.0', '0.1' ); ?><?php self::field( 'ai_max_tokens', 'حداکثر توکن پاسخ', $s, 'ltr', 'number', '64', '2000', '1' ); ?>
							<?php self::textarea( 'ai_system_prompt', 'دستور اصلی دستیار', $s, 'tiamis-span-2' ); ?>
							<?php self::checkbox( 'ai_rag_enabled', 'استفاده از پایگاه دانش سایت', $s, 'نوشته‌ها، برگه‌ها و محصولات منتشرشده در پاسخ پیشنهادی بررسی شوند.' ); ?>
							<?php self::checkbox( 'ai_qa_enabled', 'ارزیابی کیفی گفتگوهای بسته‌شده', $s, 'پس از پایان گفتگو، خلاصه و نکات قابل بهبود برای مدیر ثبت شود.' ); ?>
							<?php self::field( 'ai_max_auto_replies', 'حداکثر پاسخ خودکار پیاپی', $s, 'ltr', 'number', '0', '10', '1' ); ?>
							<?php self::textarea( 'ai_sensitive_topics', 'موضوعاتی که باید به کارشناس ارجاع شوند', $s, 'tiamis-span-2' ); ?>
						</div>
					</section>
<section class="tiamis-tab-panel" data-tiamis-tab-panel="team" hidden>
						<div class="tiamis-panel-heading"><div><span>۰۵</span><h2>تیم، سرعت و ارتباط بلادرنگ</h2><p>گفتگوها را میان کارشناسان تقسیم کنید و برای اینترنت ضعیف یا اتصال WebSocket مسیر جایگزین داشته باشید.</p></div></div>
						<div class="tiamis-settings-grid">
							<?php self::checkbox( 'auto_assignment_enabled', 'تخصیص خودکار گفتگو', $s, 'گفتگوی تازه به کارشناس فعال با ظرفیت آزاد واگذار شود.' ); ?>
							<?php self::checkbox( 'offline_queue_enabled', 'صف آفلاین پیام‌ها', $s, 'پیام ناموفق در مرورگر نگه داشته شود و پس از برگشت اینترنت دوباره ارسال شود.' ); ?>
							<?php self::checkbox( 'pwa_enabled', 'فعال‌سازی PWA سبک', $s, 'پوسته گفتگو و اعلان‌ها از Service Worker تیامیس استفاده کنند.' ); ?>
							<label>حالت ارتباط زنده<select name="settings[realtime_mode]"><option value="auto" <?php selected( $s['realtime_mode'], 'auto' ); ?>>خودکار؛ WebSocket و سپس AJAX</option><option value="websocket" <?php selected( $s['realtime_mode'], 'websocket' ); ?>>WebSocket با مسیر جایگزین AJAX</option><option value="ajax" <?php selected( $s['realtime_mode'], 'ajax' ); ?>>فقط AJAX تطبیقی</option></select></label>
							<?php self::field( 'realtime_websocket_url', 'نشانی WebSocket عمومی', $s, 'url ltr' ); ?>
							<?php self::field( 'realtime_publish_url', 'نشانی انتشار رویداد در Worker', $s, 'url ltr' ); ?>
							<?php self::field( 'realtime_active_ms', 'فاصله AJAX هنگام بازبودن گفتگو، میلی‌ثانیه', $s, 'ltr', 'number', '300', '5000', '50' ); ?>
							<?php self::field( 'realtime_idle_ms', 'فاصله AJAX در حالت کم‌فعالیت، میلی‌ثانیه', $s, 'ltr', 'number', '800', '15000', '100' ); ?>
							<?php self::field( 'sla_first_response_minutes', 'مهلت اولین پاسخ، دقیقه', $s, 'ltr', 'number', '1', '1440', '1' ); ?>
							<?php self::field( 'default_department', 'بخش پیش‌فرض', $s ); ?>
							<?php self::textarea( 'departments_text', 'بخش‌ها؛ هر مورد در یک خط', array( 'departments_text' => implode( "\n", (array) ( $s['departments'] ?? array() ) ) ), 'tiamis-span-2' ); ?>
							<?php self::textarea( 'macros_text', 'پاسخ‌های آماده؛ هر خط: /میانبر | عنوان | متن', array( 'macros_text' => $macro_text ), 'tiamis-span-2' ); ?>
						</div>
						<div class="tiamis-info-box">برای WebSocket، نمونه Worker آماده در پوشه <code>docs</code> قرار دارد. اگر WebSocket در دسترس نباشد، تیامیس بدون قطع گفتگو به AJAX تطبیقی برمی‌گردد.</div>
					</section>


					<section class="tiamis-tab-panel" data-tiamis-tab-panel="ticketing" hidden>
						<?php self::render_pro_locked_panel( 'ticketing', '۰۶' ); ?>
					</section>

					<section class="tiamis-tab-panel" data-tiamis-tab-panel="privacy" hidden>
						<div class="tiamis-panel-heading"><div><span>۰۷</span><h2>حریم خصوصی</h2><p>حداقل‌گرایی داده، رضایت کاربر و مدت نگهداری اطلاعات گفتگو را تنظیم کنید.</p></div></div>
						<div class="tiamis-settings-grid">
							<label>شیوه ذخیره IP<select name="settings[ip_mode]"><option value="none" <?php selected( $s['ip_mode'], 'none' ); ?>>ذخیره نشود</option><option value="hash" <?php selected( $s['ip_mode'], 'hash' ); ?>>هش HMAC مستعارسازی‌شده ـ پیشنهادی</option><option value="full" <?php selected( $s['ip_mode'], 'full' ); ?>>نشانی IP کامل</option></select></label>
							<?php self::field( 'retention_days', 'مدت نگهداری داده‌ها، به روز', $s, 'ltr', 'number', '7', '3650', '1' ); ?>
						</div><div class="tiamis-info-box">فقط داده‌های لازم برای ارائه پشتیبانی نگهداری می‌شوند. برای کاهش ریسک حریم خصوصی، حالت هش IP و دوره نگهداری محدود پیشنهاد می‌شود.</div>
					</section>


					<section class="tiamis-tab-panel" data-tiamis-tab-panel="api" hidden>
						<?php self::render_pro_locked_panel( 'api', '۰۸' ); ?>
					</section>

					<section class="tiamis-tab-panel tiamis-guide-panel" data-tiamis-tab-panel="guide" hidden>
						<?php self::render_guide_panel( $telegram_webhook_url, $telegram_poll_url, $bale_webhook_url ); ?>
					</section>

					<section class="tiamis-tab-panel tiamis-developer-panel" data-tiamis-tab-panel="developer" hidden>
						<?php self::render_developer_panel( $s ); ?>
					</section>
				</div>
			</div>
			<div class="tiamis-save-row"><div data-tiamis-save-status></div><button type="submit" class="button button-primary button-hero">ذخیره همه تنظیمات</button></div>
		</form>
		<?php
		self::page_close();
	}

	private static function pro_feature_copy( $feature ) {
		$copy = array(
			'ticketing' => array(
				'title'       => 'سامانه تیکتینگ هوشمند',
				'description' => 'این قابلیت در نسخه حرفه‌ای (PRO) تیامیس ارائه می‌شود. برای استفاده از پرتال ثبت و پیگیری تیکت، پاسخ‌گویی کارشناسان و شورت‌کدهای مرتبط، نسخه PRO را فعال کنید.',
				'features'    => array( 'پرتال چندزبانه تیکت', 'کد پیگیری امن', 'مدیریت کارشناسان و وضعیت‌ها' ),
			),
			'heatmap' => array(
				'title'       => 'نقشه حرارتی تعامل کاربران',
				'description' => 'این قابلیت در نسخه حرفه‌ای (PRO) تیامیس ارائه می‌شود. برای ثبت و تحلیل نقشه حرارتی کلیک‌ها، مسیر تعامل کاربران و گزارش‌های بصری، نسخه PRO را فعال کنید.',
				'features'    => array( 'نقشه تراکم کلیک', 'تحلیل صفحه‌های پرتفاعل', 'گزارش بصری رفتار کاربران' ),
			),
			'api' => array(
				'title'       => 'API و Webhook حرفه‌ای',
				'description' => 'این قابلیت در نسخه حرفه‌ای (PRO) تیامیس ارائه می‌شود. برای استفاده از REST API خارجی، Webhook خروجی و اتصال امن به CRM و ابزارهای اتوماسیون، نسخه PRO را فعال کنید.',
				'features'    => array( 'REST API خارجی امن', 'Webhook امضاشده', 'اتصال CRM و اتوماسیون', 'رویدادهای مکالمه و پاسخ' ),
			),
		);
		return isset( $copy[ $feature ] ) ? $copy[ $feature ] : $copy['api'];
	}

	private static function render_pro_feature_preview( $feature ) {
		if ( 'ticketing' === $feature ) {
			$preview = array(
				'ticketing_enabled'          => '1',
				'ticket_login_required'      => '0',
				'ticket_guest_replies'        => '1',
				'ticket_auto_ai_enabled'      => '1',
				'ticket_show_departments'     => '1',
				'ticket_attachments_enabled'  => '1',
				'ticket_voice_enabled'        => '1',
				'ticket_signature_enabled'    => '1',
				'ticket_portal_title'         => 'مرکز پشتیبانی و پیگیری تیکت',
				'ticket_code_length'          => '10',
				'ticket_max_message_length'   => '5000',
				'ticket_allowed_extensions'   => 'jpg, png, pdf, zip, mp3, webm',
				'ticket_max_file_size_mb'     => '10',
				'ticket_company_phone'        => '021-00000000',
				'ticket_company_email'        => 'support@example.com',
			);
			?>
			<fieldset class="tiamis-pro-preview-fieldset" disabled>
				<div class="tiamis-settings-grid">
					<?php self::checkbox( 'ticketing_enabled', 'فعال‌سازی سامانه تیکت', $preview ); ?>
					<?php self::checkbox( 'ticket_login_required', 'الزام ورود برای ثبت و پیگیری تیکت', $preview ); ?>
					<?php self::checkbox( 'ticket_guest_replies', 'پاسخ‌گویی مهمان با کد پیگیری', $preview ); ?>
					<?php self::checkbox( 'ticket_auto_ai_enabled', 'پاسخ خودکار هوشمند تیکت', $preview ); ?>
					<?php self::checkbox( 'ticket_show_departments', 'نمایش انتخاب دپارتمان به مشتری', $preview ); ?>
					<?php self::field( 'ticket_portal_title', 'عنوان پرتال تیکت', $preview ); ?>
					<label>پیشوند کد پیگیری<input class="ltr" type="text" value="SHCD" readonly></label>
					<?php self::field( 'ticket_code_length', 'تعداد نویسه تصادفی کد پیگیری', $preview, 'ltr', 'number', '6', '16', '1' ); ?>
					<?php self::field( 'ticket_max_message_length', 'حداکثر طول متن تیکت', $preview, 'ltr', 'number', '500', '20000', '100' ); ?>
					<?php self::checkbox( 'ticket_attachments_enabled', 'فعال‌سازی پیوست تیکت', $preview ); ?>
					<?php self::checkbox( 'ticket_voice_enabled', 'فعال‌سازی ضبط وویس زنده', $preview ); ?>
					<?php self::field( 'ticket_allowed_extensions', 'پسوندهای مجاز پیوست', $preview, 'ltr' ); ?>
					<?php self::field( 'ticket_max_file_size_mb', 'حداکثر حجم هر فایل (مگابایت)', $preview, 'ltr', 'number', '1', '100', '1' ); ?>
					<?php self::checkbox( 'ticket_signature_enabled', 'افزودن امضای پاسخ کارشناس', $preview ); ?>
					<?php self::field( 'ticket_company_phone', 'شماره تماس شرکت در امضا', $preview, 'ltr' ); ?>
					<?php self::field( 'ticket_company_email', 'ایمیل شرکت در امضا', $preview, 'ltr', 'email' ); ?>
					<label class="tiamis-span-2">امضای فارسی<textarea rows="3">با احترام، تیم پشتیبانی</textarea></label>
					<label class="tiamis-span-2">امضای عربی<textarea rows="3" dir="rtl">مع التقدير، فريق الدعم</textarea></label>
					<label class="tiamis-span-2">امضای انگلیسی<textarea rows="3" dir="ltr">Regards, Support Team</textarea></label>
				</div>
				<div class="tiamis-info-box"><?php echo esc_html( Tiamis_Chat_I18n::admin_text( 'در نسخه PRO، مشتری می‌تواند تیکت را ثبت و با کد پیگیری امن دنبال کند؛ کارشناسان نیز پاسخ، پیوست، وویس و وضعیت تیکت را مدیریت می‌کنند.' ) ); ?></div>
			</fieldset>
			<?php
			return;
		}

		if ( 'api' === $feature ) {
			$preview = array( 'api_enabled' => '1' );
			$api_base = trailingslashit( rest_url( 'shcd-tiamis/v1/external' ) );
			?>
			<fieldset class="tiamis-pro-preview-fieldset" disabled>
				<div class="tiamis-settings-grid">
					<?php self::checkbox( 'api_enabled', 'فعال‌سازی API خارجی', $preview, 'دسترسی فقط با کلید API اختصاصی امکان‌پذیر باشد.' ); ?>
					<label>کلید API<input class="ltr" type="text" value="tiamis_pro_••••••••••••••••" readonly></label>
					<label>Webhook مقصد؛ CRM، n8n یا Make<input class="ltr" type="url" value="https://crm.example.com/webhooks/tiamis" readonly></label>
					<label>کلید امضای Webhook<input class="ltr" type="text" value="••••••••••••••••••••••••" readonly></label>
					<label class="tiamis-span-2">نشانی پایه API<input class="ltr" type="text" readonly value="<?php echo esc_attr( $api_base ); ?>"></label>
				</div>
				<div class="tiamis-code-help"><code>GET /conversations</code><code>GET /conversations/{id}/messages</code><code>POST /conversations/{id}/reply</code><span>Header: <b>X-SHCD-Tiamis-API-Key</b></span></div>
				<div class="tiamis-info-box"><?php echo esc_html( Tiamis_Chat_I18n::admin_text( 'در نسخه PRO، تیامیس امکان ارسال رویدادهای گفتگو به CRM، دریافت پاسخ از ابزارهای اتوماسیون، و استفاده از Webhook امضاشده برای یکپارچه‌سازی امن را فراهم می‌کند.' ) ); ?></div>
			</fieldset>
			<?php
			return;
		}
		?>
		<div class="tiamis-pro-preview-toolbar"><i></i><i></i><i></i><span></span></div>
		<div class="tiamis-pro-preview-grid"><article><b></b><span></span><span></span></article><article><b></b><span></span><span></span></article><article><b></b><span></span><span></span></article><article class="is-wide"><b></b><span></span><span></span><span></span></article></div>
		<?php
	}

	private static function render_pro_locked_panel( $feature, $number ) {
		$copy = self::pro_feature_copy( $feature );
		$has_real_preview = in_array( $feature, array( 'ticketing', 'api' ), true );
		?>
		<div class="tiamis-panel-heading tiamis-pro-heading"><div><span><?php echo esc_html( $number ); ?></span><h2><?php echo esc_html( Tiamis_Chat_I18n::admin_text( $copy['title'] ) ); ?></h2><p><?php echo esc_html( Tiamis_Chat_I18n::admin_text( 'این قابلیت در نسخه PRO موجود است.' ) ); ?></p></div><strong class="tiamis-pro-badge">PRO</strong></div>
		<section class="tiamis-pro-lock-panel" data-tiamis-pro-feature="<?php echo esc_attr( $feature ); ?>">
			<div class="tiamis-pro-lock-preview<?php echo $has_real_preview ? ' is-feature-preview' : ''; ?>" aria-hidden="true">
				<?php self::render_pro_feature_preview( $feature ); ?>
			</div>
			<div class="tiamis-pro-lock-overlay" role="note">
				<strong class="tiamis-pro-badge">PRO</strong>
				<span class="dashicons dashicons-lock" aria-hidden="true"></span>
				<h2><?php echo esc_html( Tiamis_Chat_I18n::admin_text( $copy['title'] ) ); ?></h2>
				<p><?php echo esc_html( Tiamis_Chat_I18n::admin_text( $copy['description'] ) ); ?></p>
				<div class="tiamis-pro-feature-list"><?php foreach ( $copy['features'] as $item ) : ?><span><?php echo esc_html( Tiamis_Chat_I18n::admin_text( $item ) ); ?></span><?php endforeach; ?></div>
			</div>
		</section>
		<?php
	}

	private static function render_guide_panel( $telegram_webhook_url, $telegram_poll_url, $bale_webhook_url ) {
		?>
		<div class="tiamis-panel-heading"><div><span>۰۹</span><h2>راهنمای راه‌اندازی تیامیس</h2><p>هر سرویس را جداگانه راه‌اندازی و همان مرحله آزمایش کنید. تا وقتی چت داخلی سایت درست کار نکرده است، اتصال ربات یا هوش مصنوعی را فعال نکنید.</p></div></div>
		<div class="tiamis-guide-index">
			<a href="#tiamis-guide-start">شروع سریع</a><a href="#tiamis-guide-telegram">تلگرام</a><a href="#tiamis-guide-bale">بله</a><a href="#tiamis-guide-ai">هوش مصنوعی</a><a href="#tiamis-guide-push">اعلان‌ها</a><a href="#tiamis-guide-troubleshooting">عیب‌یابی</a>
		</div>
		<div class="tiamis-guide-grid">
			<article class="tiamis-guide-card" id="tiamis-guide-start"><header><i>1</i><div><h3>شروع سریع و آزمایش چت سایت</h3><p>پیش از اتصال سرویس‌های بیرونی</p></div></header><ol>
				<li>در «ویجت و تجربه کاربر»، نمایش چت را روشن کنید و عنوان، متن دکمه و کارشناسان را تنظیم کنید.</li>
				<li>اگر نام، ایمیل یا شماره تماس را اجباری کرده‌اید، فرم آغاز گفتگو را در پنجره ناشناس کامل کنید و مطمئن شوید کادر پیام پیش از تکمیل فرم نمایش داده نمی‌شود.</li>
				<li>یک پیام آزمایشی بفرستید. پیام باید بدون تازه‌سازی صفحه در صندوق وردپرس ثبت شود.</li>
				<li>از صندوق گفتگوها پاسخ دهید و سپس ویجت سایت را بررسی کنید. تا این مرحله هیچ سرویس بیرونی لازم نیست.</li>
				<li>پس از اطمینان از چت داخلی، کش وردپرس، مرورگر و CDN را پاک کنید و سرویس بعدی را فعال کنید.</li>
			</ol></article>

			<article class="tiamis-guide-card" id="tiamis-guide-telegram"><header><i>2</i><div><h3>تلگرام؛ ساخت ربات و اتصال روی هاست ایران</h3><p>Webhook، دریافت دوره‌ای و پراکسی</p></div></header><ol>
				<li>در تلگرام وارد <code>@BotFather</code> شوید، فرمان <code>/newbot</code> را بفرستید و توکن ربات را دریافت کنید. نشانی: <a href="https://t.me/BotFather" target="_blank" rel="noopener noreferrer">t.me/BotFather</a></li>
				<li>توکن را در «ربات ← تلگرام» وارد کنید. هر کارشناس یا گروه پشتیبانی یک پیام برای ربات بفرستد؛ سپس Chat IDها را با ویرگول جدا کنید.</li>
				<li>روی بیشتر هاست‌های ایرانی، روش «دریافت دوره‌ای» مطمئن‌تر است. تیامیس با <code>getUpdates</code> پیام تازه را دریافت می‌کند و تلگرام لازم نیست مستقیماً به هاست شما وصل شود.</li>
				<li>نشانی Cron زیر را در پنل هاست، هر یک دقیقه اجرا کنید: <code class="ltr"><?php echo esc_html( $telegram_poll_url ); ?></code></li>
				<li>اگر درخواست خروجی هاست به <code>api.telegram.org</code> هم مسدود است، از یک Relay HTTPS اختصاصی و مورداعتماد استفاده کنید و نشانی پایه آن را در تنظیمات وارد کنید.</li>
				<li>Webhook را فقط روی دامنه HTTPS عمومی با گواهی معتبر فعال کنید. نشانی Webhook تیامیس: <code class="ltr"><?php echo esc_html( $telegram_webhook_url ); ?></code></li>
				<li>Webhook و Polling هم‌زمان کار نمی‌کنند. پس از تغییر روش، یک پیام سایت بفرستید و در تلگرام دقیقاً روی همان پیام Reply کنید.</li>
			</ol><div class="tiamis-guide-sites"><b>نشانی‌های رسمی</b><a href="https://core.telegram.org/bots/tutorial" target="_blank" rel="noopener noreferrer">آموزش ساخت ربات</a><a href="https://core.telegram.org/bots/api" target="_blank" rel="noopener noreferrer">Telegram Bot API</a></div></article>

			<article class="tiamis-guide-card" id="tiamis-guide-bale"><header><i>3</i><div><h3>بله؛ ارتباط دوطرفه</h3><p>دریافت پیام و پاسخ از صندوق وردپرس</p></div></header><ol>
				<li>طبق راهنمای رسمی بله یک ربات بسازید و توکن آن را دریافت کنید.</li>
				<li>توکن و Chat ID کارشناسان یا گروه پشتیبانی را در «ربات ← بله» وارد کنید.</li>
				<li>دامنه باید HTTPS عمومی باشد. نشانی Webhook بله: <code class="ltr"><?php echo esc_html( $bale_webhook_url ); ?></code></li>
				<li>ثبت Webhook را روشن و تنظیمات را ذخیره کنید. سپس یک پیام از سایت بفرستید و در بله روی همان پیام Reply کنید.</li>
				<li>اگر کاربران مستقیم با ربات بله گفتگو می‌کنند، گزینه گفتگوی مستقیم را فعال کنید تا پیام آن‌ها در صندوق مشترک تیامیس ثبت شود.</li>
			</ol><div class="tiamis-guide-sites"><b>نشانی رسمی</b><a href="https://docs.bale.ai/" target="_blank" rel="noopener noreferrer">docs.bale.ai</a></div></article>

			<article class="tiamis-guide-card" id="tiamis-guide-ai"><header><i>4</i><div><h3>Cloudflare Workers AI</h3><p>دریافت Account ID و API Token</p></div></header><ol>
				<li>وارد داشبورد Cloudflare شوید و بخش Workers AI را باز کنید.</li>
				<li>از راهنمای REST API، گزینه ساخت Workers AI API Token را انتخاب کنید و Account ID را نیز کپی کنید.</li>
				<li>مدل را با شناسه کامل، مانند <code>@cf/...</code> وارد کنید و ابتدا حالت «پیشنهاد پاسخ» را آزمایش کنید.</li>
				<li>پاسخ خودکار را فقط پس از بررسی کیفیت، هزینه و سیاست حضور کارشناس روشن کنید.</li>
			</ol><div class="tiamis-guide-sites"><b>دریافت دسترسی</b><a href="https://dash.cloudflare.com/" target="_blank" rel="noopener noreferrer">dash.cloudflare.com</a><a href="https://developers.cloudflare.com/workers-ai/get-started/rest-api/" target="_blank" rel="noopener noreferrer">راهنمای REST API</a></div></article>

			<article class="tiamis-guide-card tiamis-guide-card-pro"><header><i>5</i><div><h3>سایر ارائه‌دهندگان هوش مصنوعی <strong class="tiamis-pro-badge">PRO</strong></h3><p>OpenRouter، OpenAI-compatible و Ollama در نسخه PRO</p></div></header><div class="tiamis-info-box">در نسخه رایگان، ارائه‌دهنده فعال هوش مصنوعی فقط Cloudflare Workers AI است. اتصال سایر ارائه‌دهندگان در نسخه PRO ارائه می‌شود.</div></article>

			<article class="tiamis-guide-card" id="tiamis-guide-push"><header><i>6</i><div><h3>اعلان مرورگر و OneSignal</h3><p>اعلان در صفحه باز و پس از بسته‌شدن صفحه</p></div></header><ol>
				<li>اعلان داخلی فقط زمانی قابل اتکاست که صفحه یا مرورگر باز باشد.</li>
				<li>برای Web Push پایدار، در OneSignal یک Web App بسازید و روش Custom Code را انتخاب کنید.</li>
				<li>App ID و REST API Key را در تیامیس قرار دهید. سایت باید HTTPS و Service Worker باید از همان دامنه قابل دریافت باشد.</li>
				<li>در مرورگر آزمایشی اجازه اعلان را تأیید و سپس از صندوق وردپرس یک پاسخ تازه ارسال کنید.</li>
			</ol><div class="tiamis-guide-sites"><b>ساخت برنامه</b><a href="https://dashboard.onesignal.com/" target="_blank" rel="noopener noreferrer">dashboard.onesignal.com</a><a href="https://documentation.onesignal.com/docs/en/web-push-setup" target="_blank" rel="noopener noreferrer">راهنمای Web Push</a></div></article>

			<article class="tiamis-guide-card" id="tiamis-guide-troubleshooting"><header><i>7</i><div><h3>عیب‌یابی سریع</h3><p>مشکل را از داخل به بیرون پیدا کنید</p></div></header><ol>
				<li>اگر پیام سایت ثبت نمی‌شود، ابتدا مسیر داخلی REST وردپرس و <code>admin-ajax.php</code> را از فایروال یا افزونه امنیتی خارج کنید.</li>
				<li>خطای <code>rest_no_route</code> معمولاً از کش قدیمی، غیرفعال‌بودن افزونه، پیوندهای یکتا یا مسدودشدن مسیر <code>/wp-json/</code> می‌آید. پیوندهای یکتا را یک‌بار ذخیره و کش را پاک کنید.</li>
				<li>خطای <code>cURL error 35</code> به اتصال TLS، فایروال هاست یا محدودیت مقصد مربوط است؛ با میزبان بررسی کنید و برای تلگرام از Polling یا Relay استفاده کنید.</li>
				<li>اگر پیام در دیتابیس ثبت می‌شود ولی به ربات نمی‌رسد، توکن، Chat ID، روش اتصال و آخرین اجرای Cron را بررسی کنید.</li>
				<li>هر بار فقط یک تنظیم را تغییر دهید و بعد از آن یک پیام آزمایشی بفرستید.</li>
			</ol></article>
		</div>
		<div class="tiamis-guide-warning"><b>نگهداری توکن و کلیدها</b><p>کلید هر سرویس را فقط در تنظیمات همان سرویس وارد کنید. اگر کلیدی ناخواسته در اختیار شخص دیگری قرار گرفت، آن را از پنل سرویس باطل و یک کلید تازه جایگزین کنید.</p></div>
		<?php
	}

	private static function render_developer_panel( $settings ) {
		global $wpdb;
		$stats    = Tiamis_Chat_DB::table_stats();
		$statuses = array(
			array( 'وردپرس 7.0.1 یا جدیدتر', version_compare( get_bloginfo( 'version' ), '7.0.1', '>=' ), get_bloginfo( 'version' ) ),
			array( 'PHP 7.4 یا جدیدتر', version_compare( PHP_VERSION, '7.4', '>=' ), PHP_VERSION ),
			array( 'ارتباط امن HTTPS', is_ssl(), is_ssl() ? 'فعال' : 'غیرفعال' ),
			array( 'جداول پایگاه داده', ! in_array( false, wp_list_pluck( $stats, 'exists' ), true ), $wpdb->prefix . TIAMIS_CHAT_TABLE_BASE ),
			array( 'پاک‌سازی زمان‌بندی‌شده', (bool) wp_next_scheduled( 'shcd_tiamis_daily_cleanup' ), wp_next_scheduled( 'shcd_tiamis_daily_cleanup' ) ? 'فعال' : 'ثبت نشده' ),
			array( 'اتصال تلگرام', '1' === $settings['telegram_enabled'] && ! empty( $settings['telegram_bot_token'] ), '1' === $settings['telegram_enabled'] ? ( 'polling' === $settings['telegram_update_mode'] ? 'دریافت دوره‌ای' : 'Webhook' ) : 'غیرفعال' ),
			array( 'آخرین دریافت تلگرام', 'polling' === $settings['telegram_update_mode'] && (bool) get_option( 'shcd_tiamis_telegram_last_poll' ), get_option( 'shcd_tiamis_telegram_last_poll' ) ? mysql2date( 'Y/m/d H:i:s', get_option( 'shcd_tiamis_telegram_last_poll' ) ) : 'هنوز اجرا نشده است' ),
			array( 'اتصال بله', '1' === $settings['bale_enabled'] && ! empty( $settings['bale_bot_token'] ), '1' === $settings['bale_enabled'] ? 'پیکربندی‌شده' : 'غیرفعال' ),
			array( 'وضعیت اپراتور', Tiamis_Chat_Core::operator_is_online(), Tiamis_Chat_Core::operator_is_online() ? 'آنلاین' : 'آفلاین' ),
			array( 'دستیار هوش مصنوعی', 'off' !== $settings['ai_mode'], 'off' !== $settings['ai_mode'] ? $settings['ai_provider'] : 'غیرفعال' ),
		);
		?>
		<div class="tiamis-mac-window">
			<div class="tiamis-mac-titlebar"><div class="tiamis-traffic-lights"><i class="is-red"></i><i class="is-yellow"></i><i class="is-green"></i></div><strong>TIAMIS Developer Console</strong><span><?php echo esc_html( home_url() ); ?></span></div>
			<div class="tiamis-mac-wallpaper">
				<div class="tiamis-dev-hero"><span>۱۰</span><div><h2>مرکز توسعه‌دهنده</h2><p>سلامت اتصال‌ها، فونت پنل و ابزارهای پایگاه داده را از همین بخش بررسی و مدیریت کنید.</p></div></div>
				<div class="tiamis-status-grid"><?php foreach ( $statuses as $status ) : ?><article class="<?php echo esc_attr( $status[1] ? 'is-ok' : 'is-warn' ); ?>"><i></i><div><b><?php echo esc_html( $status[0] ); ?></b><small><?php echo esc_html( $status[2] ); ?></small></div></article><?php endforeach; ?></div>
			</div>
		</div>

		<div class="tiamis-dev-columns">
			<section class="tiamis-admin-card">
				<div class="tiamis-card-title"><div><h2>فونت اختصاصی پنل</h2><p>فایل شبنم را با فرمت WOFF2 یا TTF بارگذاری کنید؛ پس از ذخیره، متن‌ها و کنترل‌های پنل و ویجت گفتگو با همان فونت نمایش داده می‌شوند.</p></div></div>
				<label class="tiamis-file-drop"><input type="file" name="tiamis_font" accept=".woff2,.ttf,font/woff2,font/ttf,application/x-font-ttf"><span>فایل فونت شبنم (WOFF2 یا TTF) را انتخاب کنید یا اینجا رها کنید</span><small>حداکثر حجم: ۳ مگابایت</small></label>
				<?php if ( ! empty( $settings['admin_font_url'] ) ) : ?><div class="tiamis-current-font"><code class="ltr"><?php echo esc_html( $settings['admin_font_url'] ); ?></code><label><input type="checkbox" name="remove_custom_font" value="1"> حذف فونت سفارشی</label></div><?php endif; ?>
				<div class="tiamis-info-box">برای سرعت بهتر، WOFF2 انتخاب مناسب‌تری است؛ فایل TTF نیز پذیرفته می‌شود. پس از بارگذاری، حتی نشانی‌ها و فیلدهای چپ‌به‌راست نیز با شبنم نمایش داده می‌شوند.</div>
			</section>
			<section class="tiamis-admin-card">
				<div class="tiamis-card-title"><div><h2>گزارش فنی</h2><p>این گزینه را فقط هنگام بررسی خطا روشن کنید. تیامیس مقدارهای محرمانه را در گزارش نمایش نمی‌دهد؛ با پایان عیب‌یابی، گزارش‌گیری را خاموش کنید.</p></div></div>
				<div class="tiamis-settings-grid"><?php self::checkbox( 'debug_enabled', 'ثبت گزارش فنی با WP_DEBUG', $settings, 'فقط تا پایان بررسی خطا روشن بماند.' ); ?><label>پیشوند جداول<input class="ltr" type="text" readonly value="<?php echo esc_attr( $wpdb->prefix . TIAMIS_CHAT_TABLE_BASE ); ?>"></label><label>نسخه افزونه<input class="ltr" type="text" readonly value="<?php echo esc_attr( TIAMIS_CHAT_VERSION ); ?>"></label></div>
			</section>
		</div>

		<section class="tiamis-admin-card tiamis-db-manager">
			<div class="tiamis-card-title"><div><h2>مدیریت پایگاه داده</h2><p>در این بخش می‌توانید جدول‌های تیامیس را بهینه کنید یا شماره بعدی رکوردها را بدون حذف اطلاعات مرتب کنید.</p></div><div class="tiamis-db-actions"><button type="button" class="button" data-tiamis-db-action="optimize">بهینه‌سازی جدول‌ها</button><button type="button" class="button" data-tiamis-db-action="repair">تنظیم شماره بعدی رکوردها</button><button type="button" class="button tiamis-danger-button" data-tiamis-db-action="purge">حذف همه داده‌ها و شروع از ۱</button></div></div>
			<div class="tiamis-table-scroll"><table class="widefat tiamis-data-table" data-tiamis-db-table data-tiamis-sortable><thead><tr><th data-sort="text">نام جدول</th><th data-sort="number">تعداد تقریبی ردیف</th><th data-sort="number">شماره بعدی رکورد</th><th data-sort="number">حجم</th><th data-sort="text">موتور</th></tr></thead><tbody><?php foreach ( $stats as $stat ) : ?><tr><td><code class="ltr"><?php echo esc_html( $stat['name'] ); ?></code></td><td data-value="<?php echo esc_attr( $stat['rows'] ); ?>"><?php echo esc_html( number_format_i18n( $stat['rows'] ) ); ?></td><td data-value="<?php echo esc_attr( $stat['auto_increment'] ); ?>"><?php echo esc_html( number_format_i18n( $stat['auto_increment'] ) ); ?></td><td data-value="<?php echo esc_attr( $stat['size'] ); ?>"><?php echo esc_html( size_format( $stat['size'], 2 ) ); ?></td><td><?php echo esc_html( $stat['engine'] ); ?></td></tr><?php endforeach; ?></tbody></table></div>
			<div class="tiamis-info-box">«تنظیم شماره بعدی» هیچ پیامی را پاک نمی‌کند و فقط شمارنده جدول را اصلاح می‌کند. پاک‌سازی کامل، فقط با دکمه قرمز و واردکردن عبارت تأیید انجام می‌شود.</div>
		</section>
		<?php
	}

	private static function render_agents_fields( $settings ) {
		$agents = array_slice( Tiamis_Chat_Core::agents( $settings ), 0, 1 );
		$users = get_users( array( 'fields' => array( 'ID', 'display_name' ), 'orderby' => 'display_name', 'number' => 200 ) );
		?>
		<div class="tiamis-agents-card" data-tiamis-agents>
			<div class="tiamis-card-title"><div><h3>کارشناسان گفتگو</h3><p><?php echo esc_html( Tiamis_Chat_I18n::admin_text( 'در نسخه رایگان یک کارشناس فعال پشتیبانی می‌شود. برای افزودن کارشناسان بیشتر، نسخه PRO را فعال کنید.' ) ); ?></p></div><button type="button" class="button tiamis-pro-button" disabled><span>افزودن کارشناس</span><strong class="tiamis-pro-badge">PRO</strong></button></div>
			<div class="tiamis-agents-list" data-tiamis-agents-list>
				<?php foreach ( $agents as $index => $agent ) : ?>
					<div class="tiamis-agent-row tiamis-agent-row-advanced" data-tiamis-agent-row>
						<div class="tiamis-agent-preview"><?php if ( $agent['photo'] ) : ?><img src="<?php echo esc_url( $agent['photo'] ); ?>" alt=""><?php else : ?><span>👤</span><?php endif; ?></div>
						<label>نام کارشناس<input type="text" data-agent-field="name" name="settings[agents][<?php echo esc_attr( absint( $index ) ); ?>][name]" value="<?php echo esc_attr( $agent['name'] ); ?>" maxlength="100"></label>
						<label>سمت یا حوزه پاسخ‌گویی<input type="text" data-agent-field="role" name="settings[agents][<?php echo esc_attr( absint( $index ) ); ?>][role]" value="<?php echo esc_attr( $agent['role'] ); ?>" maxlength="100"></label>
						<label class="tiamis-agent-photo">نشانی تصویر<input class="ltr" type="url" data-agent-field="photo" name="settings[agents][<?php echo esc_attr( absint( $index ) ); ?>][photo]" value="<?php echo esc_attr( $agent['photo'] ); ?>" data-tiamis-agent-photo></label>
						<button type="button" class="tiamis-agent-remove" data-tiamis-agent-remove aria-label="حذف کارشناس" hidden>×</button>
						<div class="tiamis-agent-advanced">
							<label>بخش<input type="text" data-agent-field="department" name="settings[agents][<?php echo esc_attr( absint( $index ) ); ?>][department]" value="<?php echo esc_attr( $agent['department'] ?? ( $settings['default_department'] ?? 'پشتیبانی' ) ); ?>"></label>
							<label>زبان‌ها<input type="text" data-agent-field="languages" name="settings[agents][<?php echo esc_attr( absint( $index ) ); ?>][languages]" value="<?php echo esc_attr( $agent['languages'] ?? 'fa' ); ?>" placeholder="fa, ar, en"></label>
							<label>مهارت‌ها<input type="text" data-agent-field="skills" name="settings[agents][<?php echo esc_attr( absint( $index ) ); ?>][skills]" value="<?php echo esc_attr( $agent['skills'] ?? '' ); ?>" placeholder="فروش، فنی، مالی"></label>
							<label>ظرفیت هم‌زمان<input type="number" min="1" max="50" data-agent-field="capacity" name="settings[agents][<?php echo esc_attr( absint( $index ) ); ?>][capacity]" value="<?php echo esc_attr( absint( $agent['capacity'] ?? 5 ) ); ?>"></label>
							<label>حساب وردپرس<select data-agent-field="wp_user_id" name="settings[agents][<?php echo esc_attr( absint( $index ) ); ?>][wp_user_id]"><option value="0">بدون اتصال</option><?php foreach ( $users as $user ) : ?><option value="<?php echo esc_attr( absint( $user->ID ) ); ?>" <?php selected( absint( $agent['wp_user_id'] ?? 0 ), $user->ID ); ?>><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select></label>
							<label class="tiamis-agent-active"><input type="checkbox" value="1" data-agent-field="active" name="settings[agents][<?php echo esc_attr( absint( $index ) ); ?>][active]" <?php checked( ! isset( $agent['active'] ) || $agent['active'] ); ?>> فعال و آماده تخصیص</label>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<template data-tiamis-agent-template></template>
		</div>
		<?php
	}

	private static function checkbox( $key, $label, $settings, $description = '' ) {
		echo '<label class="tiamis-switch-card"><input type="checkbox" name="settings[' . esc_attr( $key ) . ']" value="1" ' . checked( $settings[ $key ], '1', false ) . '><span class="tiamis-switch"><i></i></span><span><b>' . esc_html( $label ) . '</b>' . ( $description ? '<small>' . esc_html( $description ) . '</small>' : '' ) . '</span></label>';
	}

	private static function standalone_checkbox( $key, $label ) {
		echo '<label class="tiamis-switch-card tiamis-span-2"><input type="checkbox" name="' . esc_attr( $key ) . '" value="1"><span class="tiamis-switch"><i></i></span><span><b>' . esc_html( $label ) . '</b></span></label>';
	}

	private static function field( $key, $label, $settings, $class = '', $type = 'text', $min = '', $max = '', $step = '' ) {
		echo '<label class="' . esc_attr( $class ) . '">' . esc_html( $label );
		echo '<input type="' . esc_attr( $type ) . '" name="settings[' . esc_attr( $key ) . ']" value="' . esc_attr( $settings[ $key ] ) . '"';
		if ( '' !== $min ) {
			echo ' min="' . esc_attr( $min ) . '"';
		}
		if ( '' !== $max ) {
			echo ' max="' . esc_attr( $max ) . '"';
		}
		if ( '' !== $step ) {
			echo ' step="' . esc_attr( $step ) . '"';
		}
		echo '></label>';
	}

	private static function textarea( $key, $label, $settings, $class = '' ) {
		echo '<label class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '<textarea name="settings[' . esc_attr( $key ) . ']" rows="4">' . esc_textarea( $settings[ $key ] ) . '</textarea></label>';
	}

	private static function remove_managed_font( $url ) {
		$uploads = wp_upload_dir();
		$baseurl = trailingslashit( $uploads['baseurl'] ) . 'tiamis-fonts/';
		$basedir = trailingslashit( $uploads['basedir'] ) . 'tiamis-fonts/';
		if ( 0 === strpos( (string) $url, $baseurl ) ) {
			$file = $basedir . basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
	}

	private static function handle_font_upload( $current_url ) {
		check_admin_referer( 'shcd_tiamis_save_settings' );
		if ( ! isset( $_FILES['tiamis_font'] ) || ! is_array( $_FILES['tiamis_font'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- The upload is optional and validated field-by-field below.
			return $current_url;
		}
		$raw_file = wp_unslash( $_FILES['tiamis_font'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The upload is nonce-protected and every field is validated below.
		$file     = array(
			'name'     => isset( $raw_file['name'] ) ? sanitize_file_name( wp_unslash( (string) $raw_file['name'] ) ) : '',
			'type'     => isset( $raw_file['type'] ) ? sanitize_mime_type( wp_unslash( (string) $raw_file['type'] ) ) : '',
			'tmp_name' => isset( $raw_file['tmp_name'] ) ? sanitize_text_field( wp_unslash( (string) $raw_file['tmp_name'] ) ) : '',
			'error'    => isset( $raw_file['error'] ) ? absint( $raw_file['error'] ) : UPLOAD_ERR_NO_FILE,
			'size'     => isset( $raw_file['size'] ) ? absint( $raw_file['size'] ) : 0,
		);
		if ( UPLOAD_ERR_NO_FILE === (int) $file['error'] ) {
			return $current_url;
		}
		if ( UPLOAD_ERR_OK !== (int) $file['error'] || (int) $file['size'] < 4 || (int) $file['size'] > 3 * MB_IN_BYTES ) {
			return new WP_Error( 'shcd_tiamis_font_upload', 'invalid_upload' );
		}
		$name      = sanitize_file_name( (string) $file['name'] );
		$extension = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( ! in_array( $extension, array( 'woff2', 'ttf' ), true ) ) {
			return new WP_Error( 'shcd_tiamis_font_extension', 'invalid_extension' );
		}
		if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		}
		$filesystem    = new WP_Filesystem_Direct( null );
		$font_contents = $filesystem->get_contents( $file['tmp_name'] );
		$magic         = is_string( $font_contents ) ? substr( $font_contents, 0, 4 ) : '';
		$is_woff2 = 'woff2' === $extension && 'wOF2' === $magic;
		$is_ttf   = 'ttf' === $extension && ( "\x00\x01\x00\x00" === $magic || 'true' === $magic );
		if ( ! $is_woff2 && ! $is_ttf ) {
			return new WP_Error( 'shcd_tiamis_font_signature', 'invalid_signature' );
		}
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$filename          = 'tiamis-shabnam-' . substr( hash( 'sha256', (string) $font_contents ), 0, 12 ) . '.' . $extension;
		$upload_dir_filter = static function ( $paths ) {
			$paths['path']   = trailingslashit( $paths['basedir'] ) . 'tiamis-fonts';
			$paths['url']    = trailingslashit( $paths['baseurl'] ) . 'tiamis-fonts';
			$paths['subdir'] = '/tiamis-fonts';
			return $paths;
		};

		add_filter( 'upload_dir', $upload_dir_filter );
		$uploaded = wp_handle_upload(
			$file,
			array(
				'test_form'                => false,
				'mimes'                    => array(
					'woff2' => 'font/woff2',
					'ttf'   => 'font/ttf',
				),
				'unique_filename_callback' => static function () use ( $filename ) {
					return $filename;
				},
			)
		);
		remove_filter( 'upload_dir', $upload_dir_filter );

		if ( ! is_array( $uploaded ) || ! empty( $uploaded['error'] ) || empty( $uploaded['url'] ) ) {
			return new WP_Error( 'shcd_tiamis_font_upload', 'upload_failed' );
		}

		self::remove_managed_font( $current_url );
		return esc_url_raw( $uploaded['url'] );
	}

	public static function save_settings() {
		self::guard( 'settings' );
		check_admin_referer( 'shcd_tiamis_save_settings' );
		$current = Tiamis_Chat_Core::settings();
		$posted_raw = filter_input( INPUT_POST, 'settings', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY );
		$posted     = is_array( $posted_raw )
			? map_deep( wp_unslash( $posted_raw ), 'sanitize_textarea_field' )
			: array();
		$input   = wp_parse_args( $posted, $current );
		$checkboxes = array( 'enabled', 'bubble_chat_enabled', 'profile_required', 'collect_email', 'collect_phone', 'consent_required', 'browser_notifications', 'onesignal_enabled', 'telegram_enabled', 'telegram_customer_enabled', 'bale_enabled', 'bale_customer_enabled', 'debug_enabled', 'auto_assignment_enabled', 'offline_queue_enabled', 'pwa_enabled', 'ai_rag_enabled', 'ai_qa_enabled' );
		foreach ( $checkboxes as $key ) {
			$input[ $key ] = empty( $posted[ $key ] ) ? '0' : '1';
		}
		$text_keys = array( 'widget_title', 'widget_subtitle', 'welcome_message', 'agent_name', 'button_label', 'consent_text', 'telegram_admin_chat_ids', 'telegram_customer_welcome', 'telegram_api_base', 'bale_admin_chat_ids', 'bale_customer_welcome', 'onesignal_app_id', 'ai_model', 'ai_system_prompt', 'cloudflare_account_id', 'cloudflare_model', 'default_department', 'ai_sensitive_topics' );
		foreach ( $text_keys as $key ) {
			$input[ $key ] = isset( $input[ $key ] ) ? sanitize_textarea_field( $input[ $key ] ) : $current[ $key ];
		}
		$webhook_secret = isset( $input['telegram_webhook_secret'] ) ? preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $input['telegram_webhook_secret'] ) : (string) $current['telegram_webhook_secret'];
		$input['telegram_webhook_secret'] = strlen( $webhook_secret ) >= 12 ? $webhook_secret : wp_generate_password( 48, false, false );
		$bale_webhook_secret = isset( $input['bale_webhook_secret'] ) ? preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $input['bale_webhook_secret'] ) : (string) $current['bale_webhook_secret'];
		$input['bale_webhook_secret'] = strlen( $bale_webhook_secret ) >= 20 ? $bale_webhook_secret : wp_generate_password( 48, false, false );
		$telegram_poll_secret = isset( $current['telegram_poll_secret'] ) ? preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $current['telegram_poll_secret'] ) : '';
		$input['telegram_poll_secret'] = strlen( $telegram_poll_secret ) >= 20 ? $telegram_poll_secret : wp_generate_password( 40, false, false );
		$input['agents'] = array();
		$posted_agents   = isset( $posted['agents'] ) && is_array( $posted['agents'] ) ? array_slice( $posted['agents'], 0, 1 ) : array();
		foreach ( $posted_agents as $agent ) {
			if ( ! is_array( $agent ) ) {
				continue;
			}
			$name       = sanitize_text_field( isset( $agent['name'] ) ? $agent['name'] : '' );
			$role       = sanitize_text_field( isset( $agent['role'] ) ? $agent['role'] : '' );
			$photo      = esc_url_raw( isset( $agent['photo'] ) ? $agent['photo'] : '' );
			$department = sanitize_text_field( isset( $agent['department'] ) ? $agent['department'] : ( $current['default_department'] ?? 'پشتیبانی' ) );
			$languages  = sanitize_text_field( isset( $agent['languages'] ) ? $agent['languages'] : 'fa' );
			$skills     = sanitize_text_field( isset( $agent['skills'] ) ? $agent['skills'] : '' );
			$capacity   = min( 50, max( 1, absint( $agent['capacity'] ?? 5 ) ) );
			$wp_user_id = absint( $agent['wp_user_id'] ?? 0 );
			$active     = empty( $agent['active'] ) ? 0 : 1;
			if ( '' === $name && '' === $photo ) {
				continue;
			}
			$input['agents'][] = compact( 'name', 'role', 'photo', 'department', 'languages', 'skills', 'capacity', 'wp_user_id', 'active' );
		}
		if ( ! $input['agents'] ) {
			$input['agents'] = Tiamis_Chat_Core::agents( $current );
		}
		$input['agent_name']  = $input['agents'][0]['name'];
		$input['agent_photo'] = $input['agents'][0]['photo'];

		foreach ( array( 'agent_photo', 'ai_endpoint', 'realtime_websocket_url', 'realtime_publish_url' ) as $key ) {
			$input[ $key ] = isset( $input[ $key ] ) ? esc_url_raw( $input[ $key ] ) : '';
		}
		$input['admin_language']  = Tiamis_Chat_I18n::normalize( $current['admin_language'] );
		$input['widget_language'] = Tiamis_Chat_I18n::normalize( $input['widget_language'] ?? $current['widget_language'] );
		$input['position']        = in_array( $input['position'] ?? '', array( 'left', 'right' ), true ) ? $input['position'] : 'left';
		$input['theme']           = in_array( $input['theme'] ?? '', array( 'violet', 'blue', 'green', 'dark' ), true ) ? $input['theme'] : 'violet';
		$input['bubble_style']    = 'gradient';
		$input['bubble_animation'] = 'tada';
		$input['bubble_random_animation'] = '0';
		$input['telegram_update_mode'] = in_array( $input['telegram_update_mode'] ?? '', array( 'webhook', 'polling' ), true ) ? $input['telegram_update_mode'] : 'polling';
		$input['telegram_poll_interval'] = in_array( absint( $input['telegram_poll_interval'] ?? 30 ), array( 30, 60, 120 ), true ) ? absint( $input['telegram_poll_interval'] ) : 30;
		$telegram_api_base = untrailingslashit( esc_url_raw( $input['telegram_api_base'] ?? 'https://api.telegram.org' ) );
		$telegram_api_parts = wp_parse_url( $telegram_api_base );
		$input['telegram_api_base'] = is_array( $telegram_api_parts ) && ! empty( $telegram_api_parts['host'] ) && 'https' === strtolower( $telegram_api_parts['scheme'] ?? '' ) ? $telegram_api_base : 'https://api.telegram.org';
		$input['ip_mode']         = in_array( $input['ip_mode'] ?? '', array( 'none', 'hash', 'full' ), true ) ? $input['ip_mode'] : 'hash';
		$input['ai_mode']         = in_array( $input['ai_mode'] ?? '', array( 'off', 'draft', 'auto' ), true ) ? $input['ai_mode'] : 'off';
		$input['ai_provider']     = 'cloudflare';
		$input['bale_enabled'] = '0';
		$input['bale_customer_enabled'] = '0';
		$input['ai_operator_policy'] = in_array( $input['ai_operator_policy'] ?? '', array( 'offline_only', 'always', 'never' ), true ) ? $input['ai_operator_policy'] : 'offline_only';
		$input['operator_presence_mode'] = in_array( $input['operator_presence_mode'] ?? '', array( 'auto', 'online', 'offline' ), true ) ? $input['operator_presence_mode'] : 'auto';
		$input['operator_presence_timeout'] = min( 600, max( 30, absint( $input['operator_presence_timeout'] ?? 90 ) ) );
		$input['realtime_mode'] = in_array( $input['realtime_mode'] ?? '', array( 'auto', 'websocket', 'ajax' ), true ) ? $input['realtime_mode'] : 'auto';
		$input['realtime_active_ms'] = min( 5000, max( 300, absint( $input['realtime_active_ms'] ?? 450 ) ) );
		$input['realtime_idle_ms'] = min( 15000, max( 800, absint( $input['realtime_idle_ms'] ?? 2200 ) ) );
		$input['sla_first_response_minutes'] = min( 1440, max( 1, absint( $input['sla_first_response_minutes'] ?? 15 ) ) );
		$input['ai_max_auto_replies'] = min( 10, max( 0, absint( $input['ai_max_auto_replies'] ?? 2 ) ) );
		$departments_text = isset( $posted['departments_text'] ) ? (string) $posted['departments_text'] : '';
		$input['departments'] = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', preg_split( '/[\r\n,]+/', $departments_text ) ) ) ) );
		if ( ! $input['departments'] ) $input['departments'] = array( $input['default_department'] ?: 'پشتیبانی' );
		$input['macros'] = array();
		$macros_text = isset( $posted['macros_text'] ) ? (string) $posted['macros_text'] : '';
		foreach ( preg_split( '/\r?\n/', $macros_text ) as $macro_line ) {
			$parts = array_map( 'trim', explode( '|', $macro_line, 3 ) );
			if ( count( $parts ) < 3 || '' === $parts[2] ) continue;
			$input['macros'][] = array( 'shortcut' => sanitize_text_field( $parts[0] ), 'title' => sanitize_text_field( $parts[1] ), 'message' => sanitize_textarea_field( $parts[2] ) );
		}
		if ( ! $input['macros'] ) $input['macros'] = $current['macros'];
		$input['cloudflare_account_id'] = preg_replace( '/[^a-f0-9]/i', '', (string) $input['cloudflare_account_id'] );
		$input['cloudflare_model']      = preg_match( '#^@cf/[A-Za-z0-9._-]+/[A-Za-z0-9._-]+$#', (string) $input['cloudflare_model'] ) ? (string) $input['cloudflare_model'] : '@cf/meta/llama-3.1-8b-instruct-fast';
		$input['retention_days']        = min( 3650, max( 7, absint( $input['retention_days'] ?? 90 ) ) );
		$input['ai_temperature']        = (string) min( 1, max( 0, (float) ( $input['ai_temperature'] ?? 0.3 ) ) );
		$input['ai_max_tokens']         = min( 2000, max( 64, absint( $input['ai_max_tokens'] ?? 500 ) ) );
		$input['poll_interval']         = $current['poll_interval'];
		$input['rate_limit_messages']   = $current['rate_limit_messages'];

		$secret_fields = array(
			'telegram_bot_token'      => 'telegram_bot_token',
			'bale_bot_token'          => 'bale_bot_token',
			'ai_api_key'              => 'ai_api_key',
			'cloudflare_api_token'    => 'cloudflare_api_token',
			'onesignal_rest_api_key'  => 'onesignal_rest_api_key',
			'realtime_secret'        => 'realtime_secret',
		);
		foreach ( $secret_fields as $post_key => $setting_key ) {
			$value = trim( (string) self::request_value( $post_key, 'POST', 'raw' ) );
			$input[ $setting_key ] = '' !== $value ? Tiamis_Chat_Core::encrypt_secret( sanitize_text_field( $value ) ) : $current[ $setting_key ];
		}

		$notice = 'saved';
		if ( '1' === self::request_value( 'remove_custom_font', 'POST', 'key', '0' ) ) {
			self::remove_managed_font( $current['admin_font_url'] );
			$input['admin_font_url'] = '';
		} else {
			$font = self::handle_font_upload( $current['admin_font_url'] );
			if ( is_wp_error( $font ) ) {
				$input['admin_font_url'] = $current['admin_font_url'];
				$notice = 'font_error';
			} else {
				$input['admin_font_url'] = esc_url_raw( $font );
			}
		}

		Tiamis_Chat_Core::update_settings( $input );
		Tiamis_Chat_Integrations::sync_telegram_polling_schedule( true );
		if ( '1' === $input['telegram_enabled'] && 'polling' === $input['telegram_update_mode'] ) {
			// Prepare polling asynchronously so saving settings never waits for Telegram.
			if ( ! wp_next_scheduled( 'shcd_tiamis_telegram_prepare_polling' ) ) {
				wp_schedule_single_event( time() + 1, 'shcd_tiamis_telegram_prepare_polling' );
			}
		}
		$webhook_results = array();
		if ( 'webhook' === $input['telegram_update_mode'] && '1' === self::request_value( 'register_telegram_webhook', 'POST', 'key', '0' ) ) {
			$webhook_results['telegram'] = Tiamis_Chat_Integrations::set_telegram_webhook();
		}
		if ( '1' === self::request_value( 'register_bale_webhook', 'POST', 'key', '0' ) ) {
			$webhook_results['bale'] = Tiamis_Chat_Integrations::set_bale_webhook();
		}
		if ( $webhook_results ) {
			$has_error = false;
			foreach ( $webhook_results as $result ) {
				$has_error = $has_error || is_wp_error( $result );
			}
			if ( 1 === count( $webhook_results ) && isset( $webhook_results['telegram'] ) ) {
				$notice = $has_error ? 'webhook_error' : 'webhook_ok';
			} elseif ( 1 === count( $webhook_results ) && isset( $webhook_results['bale'] ) ) {
				$notice = $has_error ? 'bale_webhook_error' : 'bale_webhook_ok';
			} else {
				$notice = $has_error ? 'bots_webhook_error' : 'bots_webhook_ok';
			}
		}
		if ( wp_doing_ajax() ) {
			$messages = array(
				'saved'              => 'تنظیمات ذخیره شد.',
				'font_error'         => 'فونت بارگذاری نشد؛ فایل TTF یا WOFF2 را دوباره بررسی کنید.',
				'webhook_ok'         => 'تنظیمات و وب‌هوک تلگرام ذخیره شد.',
				'webhook_error'      => 'تنظیمات ذخیره شد؛ ثبت وب‌هوک تلگرام کامل نشد.',
				'bale_webhook_ok'    => 'تنظیمات و وب‌هوک بله ذخیره شد.',
				'bale_webhook_error' => 'تنظیمات ذخیره شد؛ ثبت وب‌هوک بله کامل نشد.',
				'bots_webhook_ok'    => 'تنظیمات و وب‌هوک ربات‌ها ذخیره شد.',
				'bots_webhook_error' => 'تنظیمات ذخیره شد؛ ثبت یکی از وب‌هوک‌ها کامل نشد.',
			);
			wp_send_json_success( array( 'notice' => $notice, 'message' => isset( $messages[ $notice ] ) ? $messages[ $notice ] : $messages['saved'] ) );
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'shcd-tiamis-settings', 'tiamis_notice' => $notice ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
