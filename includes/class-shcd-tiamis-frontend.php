<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tiamis_Chat_Frontend {
	private static $shortcode_rendered = false;
	private static $fallback_assets_printed = false;
	private static $critical_style_added = false;

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render' ) );
		add_shortcode( 'shcd_tiamis', array( __CLASS__, 'shortcode' ) );
	}


	public static function assets() {
		$settings = Tiamis_Chat_Core::settings();
		if ( '1' !== $settings['enabled'] ) {
			return;
		}
		$language = Tiamis_Chat_I18n::normalize( $settings['widget_language'] );
		$strings  = Tiamis_Chat_I18n::widget_strings( $language );

		if ( '1' === $settings['enabled'] ) {
			wp_enqueue_style( 'shcd-tiamis', TIAMIS_CHAT_URL . 'assets/css/shcd-tiamis.css', array(), TIAMIS_CHAT_VERSION );
			self::critical_style( 'shcd-tiamis' );
			self::font_style( 'shcd-tiamis', $settings );
		}
		if ( '1' === $settings['onesignal_enabled'] && '' !== trim( (string) $settings['onesignal_app_id'] ) ) {
			wp_enqueue_script( 'shcd-tiamis-onesignal', 'https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js', array(), null, false );
			wp_script_add_data( 'shcd-tiamis-onesignal', 'strategy', 'defer' );
			wp_add_inline_script( 'shcd-tiamis-onesignal', 'window.OneSignalDeferred = window.OneSignalDeferred || [];', 'before' );
		}
		wp_enqueue_script( 'shcd-tiamis', TIAMIS_CHAT_URL . 'assets/js/shcd-tiamis.js', array(), TIAMIS_CHAT_VERSION, true );
		$worker_path  = '/' . ltrim( (string) wp_parse_url( TIAMIS_CHAT_URL . 'assets/js/OneSignalSDKWorker.js', PHP_URL_PATH ), '/' );
		$worker_scope = trailingslashit( dirname( $worker_path ) );
		wp_localize_script(
			'shcd-tiamis',
			'TiamisChatConfig',
			array(
				'restUrl'              => esc_url_raw( rest_url( 'shcd-tiamis/v1/' ) ),
				'ajaxUrl'              => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
					'ajaxNonce'            => wp_create_nonce( 'shcd_tiamis_public_ajax' ),
				'language'             => $language,
				'locale'               => Tiamis_Chat_I18n::locale( $language ),
				'direction'            => Tiamis_Chat_I18n::direction( $language ),
				'widgetEnabled'        => '1' === $settings['enabled'],
				'profileRequired'      => '1' === $settings['profile_required'],
				'profileGate'          => in_array( '1', array( $settings['profile_required'], $settings['collect_email'], $settings['collect_phone'], $settings['consent_required'] ), true ),
				'randomAnimation'      => 'random' === ( $settings['bubble_animation'] ?? 'random' ),
				'collectEmail'         => '1' === $settings['collect_email'],
				'collectPhone'         => '1' === $settings['collect_phone'],
				'consentRequired'      => '1' === $settings['consent_required'],
				'browserNotifications' => '1' === $settings['browser_notifications'],
				'oneSignal'            => array(
					'enabled'     => '1' === $settings['onesignal_enabled'] && '' !== trim( (string) $settings['onesignal_app_id'] ),
					'appId'       => sanitize_text_field( $settings['onesignal_app_id'] ),
					'workerPath'  => $worker_path,
					'workerScope' => $worker_scope,
				),
				'pollInterval'         => max( 300, min( 5000, absint( $settings['realtime_active_ms'] ?? 450 ) ) ),
				'idlePollInterval'     => max( 800, min( 15000, absint( $settings['realtime_idle_ms'] ?? 2200 ) ) ),
				'offlineQueueEnabled'  => '1' === (string) ( $settings['offline_queue_enabled'] ?? '1' ),
				'realtime'             => array(
					'mode'      => sanitize_key( $settings['realtime_mode'] ?? 'auto' ),
					'websocket' => esc_url_raw( $settings['realtime_websocket_url'] ?? '' ),
				),
				'pwa'                  => array(
					'enabled' => '1' === (string) ( $settings['pwa_enabled'] ?? '0' ),
					'worker'  => esc_url_raw( home_url( '/tiamis-sw.js' ) ),
				),
				'soundsEnabled'        => true,
				'i18n'                 => array(
					'networkError'        => $strings['network_error'],
					'sending'             => $strings['sending'],
					'newReply'            => $strings['new_reply'],
					'you'                 => $strings['you'],
					'support'             => $strings['support'],
					'consentRequired'     => $strings['consent_required'],
					'nameRequired'        => $strings['name_required'],
					'emailRequired'       => $strings['email_required'],
					'phoneRequired'       => $strings['phone_required'],
					'notificationDenied'  => $strings['notification_denied'],
					'notificationEnabled' => $strings['notification_enabled'],
					'typing'              => $strings['typing'],
					'delivered'           => $strings['delivered'],
					'seen'                => $strings['seen'],
					'report'              => $strings['report'],
					'reported'            => $strings['reported'],
					'reportConfirm'       => $strings['report_confirm'],
					'rateTitle'           => $strings['rate_title'],
					'rateLater'           => $strings['rate_later'],
					'rateThanks'          => $strings['rate_thanks'],
					'rateGreat'           => $strings['rate_great'],
					'rateGood'            => $strings['rate_good'],
					'rateBad'             => $strings['rate_bad'],
					'rateAwful'           => $strings['rate_awful'],
					'blocked'             => $strings['blocked'],
					'deletedMessage'      => $strings['deleted_message'],
					'attachment'          => $strings['attachment'],
					'reaction'            => $strings['reaction'],
					'offlineRetry'        => $strings['offline_retry'],
					'filePreparing'       => $strings['file_preparing'],
					'fileReady'           => $strings['file_ready'],
					'typingSuffix'        => $strings['typing_suffix'],
				),
			)
		);
	}


	private static function critical_style( $handle ) {
		if ( self::$critical_style_added || ! wp_style_is( $handle, 'registered' ) ) {
			return;
		}
		self::$critical_style_added = true;
		$css = ':where([data-shcd-tiamis]){position:fixed;z-index:999999;inset-inline-end:24px;bottom:24px;width:auto;max-width:calc(100vw - 28px);font-family:Tahoma,Arial,sans-serif;direction:rtl;isolation:isolate}'
			. ':where([data-shcd-tiamis]),:where([data-shcd-tiamis] *){box-sizing:border-box}'
			. ':where([data-shcd-tiamis] img){max-width:100%;height:auto}'
			. ':where([data-shcd-tiamis] .shcd-tiamis-panel[hidden]){display:none!important}'
			. ':where([data-shcd-tiamis] .shcd-tiamis-panel){position:absolute;inset-inline-end:0;bottom:72px;width:min(390px,calc(100vw - 28px));height:min(610px,calc(100vh - 120px));overflow:hidden;background:#fff;border-radius:24px;box-shadow:0 28px 80px rgba(25,16,52,.28)}'
			. ':where([data-shcd-tiamis] .shcd-tiamis-toggle){min-width:58px;min-height:58px;cursor:pointer}'
			. ':where([data-shcd-tiamis].tiamis-live-chat-inline){position:relative;inset:auto;display:block;width:100%;max-width:390px}'
			. ':where([data-shcd-tiamis].tiamis-live-chat-inline .shcd-tiamis-panel){position:relative;inset:auto;width:100%;max-width:390px}'
			. ':where([data-shcd-tiamis].tiamis-live-chat-inline .shcd-tiamis-toggle){display:none}'
			. '@media(max-width:600px){:where([data-shcd-tiamis]:not(.tiamis-live-chat-inline)){inset-inline:14px;bottom:14px}:where([data-shcd-tiamis]:not(.tiamis-live-chat-inline) .shcd-tiamis-panel){position:fixed;inset-inline:10px;bottom:82px;width:auto;height:min(650px,calc(100vh - 96px))}}';
		wp_add_inline_style( $handle, $css );
	}

	private static function font_style( $handle, $settings ) {
		$url = isset( $settings['admin_font_url'] ) ? esc_url( $settings['admin_font_url'] ) : '';
		if ( '' === $url ) {
			return;
		}
		$font_path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$font_ext  = strtolower( pathinfo( $font_path, PATHINFO_EXTENSION ) );
		$font_type = 'ttf' === $font_ext ? 'truetype' : 'woff2';
		$css = "@font-face{font-family:'Shabnam FD';src:local('Shabnam FD'),url('" . esc_url( $url ) . "') format('" . $font_type . "');font-display:swap;font-style:normal;font-weight:400}.tiamis-live-chat,.tiamis-live-chat *:not(.dashicons){font-family:'Shabnam FD',Tahoma,Arial,sans-serif!important}";
		wp_add_inline_style( $handle, $css );
	}

	public static function render() {
		if ( self::$shortcode_rendered ) {
			return;
		}
		$settings = Tiamis_Chat_Core::settings();
		if ( '1' !== $settings['enabled'] || '1' !== (string) ( $settings['bubble_chat_enabled'] ?? '1' ) ) {
			return;
		}
		echo self::markup( false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function shortcode() {
		self::$shortcode_rendered = true;
		$settings = Tiamis_Chat_Core::settings();
		if ( '1' !== $settings['enabled'] ) {
			return '';
		}
		self::assets();
		return self::markup( true );
	}

	private static function fallback_frontend_assets() {
		if ( self::$fallback_assets_printed ) {
			return;
		}
		self::$fallback_assets_printed = true;

		if ( ! wp_style_is( 'shcd-tiamis', 'registered' ) ) {
			wp_register_style( 'shcd-tiamis', TIAMIS_CHAT_URL . 'assets/css/shcd-tiamis.css', array(), TIAMIS_CHAT_VERSION );
		}
		wp_enqueue_style( 'shcd-tiamis' );
		self::critical_style( 'shcd-tiamis' );
	}

	private static function markup( $inline ) {
		$s        = Tiamis_Chat_Core::settings();
		$language = Tiamis_Chat_I18n::normalize( $s['widget_language'] );
		$strings  = Tiamis_Chat_I18n::widget_strings( $language );
		$agents   = Tiamis_Chat_Core::agents( $s );
		$allowed_styles = array( 'gradient', 'glass', 'neon', 'soft', 'minimal', 'telegram', 'cosmic', 'liquid', 'outline', 'crystal', 'square', 'pill' );
		$allowed_animations = array( 'random', 'none', 'float', 'pulse', 'bounce', 'shake', 'swing', 'wobble', 'heartbeat', 'tada', 'jelly', 'rotate', 'orbit', 'glow', 'wave', 'pop', 'slide' );
		$bubble_style = in_array( $s['bubble_style'] ?? '', $allowed_styles, true ) ? $s['bubble_style'] : 'gradient';
		$bubble_animation = in_array( $s['bubble_animation'] ?? '', $allowed_animations, true ) ? $s['bubble_animation'] : 'random';
		$animation_pool = array_values( array_diff( $allowed_animations, array( 'random', 'none' ) ) );
		$resolved_animation = 'random' === $bubble_animation ? $animation_pool[ wp_rand( 0, count( $animation_pool ) - 1 ) ] : $bubble_animation;
		$profile_gate = in_array( '1', array( $s['profile_required'], $s['collect_email'], $s['collect_phone'], $s['consent_required'] ), true );
		$classes  = array( 'tiamis-live-chat', 'tiamis-theme-' . sanitize_key( $s['theme'] ), 'tiamis-position-' . sanitize_key( $s['position'] ), 'tiamis-bubble-style-' . $bubble_style );
		if ( 'none' !== $resolved_animation ) {
			$classes[] = 'tiamis-bubble-animation-' . $resolved_animation;
		}
		if ( $profile_gate ) {
			$classes[] = 'is-profile-gate';
		}
		if ( $inline ) {
			$classes[] = 'tiamis-live-chat-inline';
		}
		self::fallback_frontend_assets();
		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-shcd-tiamis data-random-animation="<?php echo esc_attr( 'random' === $bubble_animation ? '1' : '0' ); ?>" data-base-animation="<?php echo esc_attr( $bubble_animation ); ?>" data-server-animation="<?php echo esc_attr( $resolved_animation ); ?>" dir="<?php echo esc_attr( Tiamis_Chat_I18n::direction( $language ) ); ?>" lang="<?php echo esc_attr( $language ); ?>">
			<button type="button" class="shcd-tiamis-toggle" aria-expanded="false" aria-label="<?php echo esc_attr( $strings['open'] ); ?>">
				<span class="shcd-tiamis-toggle-agents" aria-hidden="true"><?php foreach ( array_slice( $agents, 0, 3 ) as $agent ) : ?><?php if ( ! empty( $agent['photo'] ) ) : ?><img src="<?php echo esc_url( $agent['photo'] ); ?>" alt=""><?php else : ?><i><?php echo esc_html( function_exists( 'mb_substr' ) ? mb_substr( $agent['name'], 0, 1 ) : substr( $agent['name'], 0, 1 ) ); ?></i><?php endif; ?><?php endforeach; ?><b>💬</b></span>
				<span class="shcd-tiamis-toggle-label"><?php echo esc_html( $s['button_label'] ); ?></span>
				<span class="shcd-tiamis-unread" data-tiamis-unread hidden>0</span>
			</button>
			<section class="shcd-tiamis-panel" aria-hidden="true"<?php if ( ! $inline ) : ?> hidden<?php endif; ?>>
				<header class="shcd-tiamis-header">
					<div class="shcd-tiamis-agent-stack" aria-label="<?php echo esc_attr( implode( '، ', wp_list_pluck( $agents, 'name' ) ) ); ?>"><?php foreach ( array_slice( $agents, 0, 4 ) as $agent_index => $agent ) : ?><?php $agent_title = trim( $agent['name'] . ( $agent['role'] ? ' — ' . $agent['role'] : '' ) ); ?><?php if ( ! empty( $agent['photo'] ) ) : ?><img data-tiamis-agent-profile data-agent-index="<?php echo esc_attr( absint( $agent_index ) ); ?>" data-agent-name="<?php echo esc_attr( $agent['name'] ); ?>" src="<?php echo esc_url( $agent['photo'] ); ?>" alt="<?php echo esc_attr( $agent['name'] ); ?>" title="<?php echo esc_attr( $agent_title ); ?>"><?php else : ?><i data-tiamis-agent-profile data-agent-index="<?php echo esc_attr( absint( $agent_index ) ); ?>" data-agent-name="<?php echo esc_attr( $agent['name'] ); ?>" title="<?php echo esc_attr( $agent_title ); ?>"><?php echo esc_html( function_exists( 'mb_substr' ) ? mb_substr( $agent['name'], 0, 1 ) : substr( $agent['name'], 0, 1 ) ); ?></i><?php endif; ?><?php endforeach; ?></div>
					<div class="shcd-tiamis-header-copy"><h3><?php echo esc_html( $s['widget_title'] ); ?></h3><p><i></i><?php echo esc_html( $s['widget_subtitle'] ); ?></p></div>
					<div class="shcd-tiamis-header-actions">
						<button type="button" class="shcd-tiamis-close" aria-label="<?php echo esc_attr( $strings['close'] ); ?>">×</button>
						<button type="button" class="shcd-tiamis-end" data-tiamis-end-conversation><?php echo esc_html( $strings['end_chat'] ); ?></button>
					</div>
				</header>
				<div class="shcd-tiamis-profile" data-tiamis-profile<?php if ( ! $profile_gate ) : ?> hidden<?php endif; ?>>
					<h4><?php echo esc_html( $strings['before'] ); ?></h4>
					<label><?php echo esc_html( $strings['name'] ); ?><input type="text" data-tiamis-name maxlength="100" autocomplete="name"<?php if ( '1' === $s['profile_required'] ) : ?> required<?php endif; ?>></label>
					<?php if ( '1' === $s['collect_email'] ) : ?><label><?php echo esc_html( $strings['email'] ); ?><input type="email" data-tiamis-email maxlength="190" autocomplete="email" required></label><?php endif; ?>
					<?php if ( '1' === $s['collect_phone'] ) : ?><label><?php echo esc_html( $strings['phone'] ); ?><input type="tel" data-tiamis-phone maxlength="50" autocomplete="tel" required></label><?php endif; ?>
					<?php if ( '1' === $s['consent_required'] ) : ?><label class="shcd-tiamis-consent"><input type="checkbox" data-tiamis-consent> <span><?php echo esc_html( $s['consent_text'] ); ?></span></label><?php endif; ?>
					<input type="text" data-tiamis-honeypot class="shcd-tiamis-honeypot" tabindex="-1" autocomplete="off">
					<button type="button" data-tiamis-profile-submit><?php echo esc_html( $strings['start'] ); ?></button>
					<div class="shcd-tiamis-error" data-tiamis-profile-error></div>
				</div>
				<div class="shcd-tiamis-body" data-shcd-tiamis-body<?php if ( $profile_gate ) : ?> hidden<?php endif; ?>>
					<div class="shcd-tiamis-messages" data-tiamis-messages aria-live="polite"></div>
					<div class="shcd-tiamis-typing" data-tiamis-typing hidden aria-live="polite"><span><i></i><i></i><i></i></span><b><?php echo esc_html( $strings['typing'] ); ?></b></div>
					<?php if ( '1' === $s['browser_notifications'] || '1' === $s['onesignal_enabled'] ) : ?><button type="button" class="shcd-tiamis-notify" data-tiamis-notify><?php echo esc_html( $strings['enable_notifications'] ); ?></button><?php endif; ?>
				</div>
				<form class="shcd-tiamis-form" data-tiamis-form<?php if ( $profile_gate ) : ?> hidden<?php endif; ?>>
					<label class="shcd-tiamis-attach" title="افزودن فایل"><input type="file" data-tiamis-file accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip"><span>＋</span></label>
					<textarea rows="1" maxlength="4000" data-tiamis-input placeholder="<?php echo esc_attr( $strings['message_placeholder'] ); ?>" aria-label="<?php echo esc_attr( $strings['message_label'] ); ?>"></textarea>
					<input type="text" name="website" class="shcd-tiamis-honeypot" tabindex="-1" autocomplete="off">
					<button type="submit" aria-label="<?php echo esc_attr( $strings['send'] ); ?>">➤</button>
					<div class="shcd-tiamis-file-state" data-tiamis-file-state hidden></div>
				</form>
				<div class="shcd-tiamis-rating" data-tiamis-rating hidden aria-live="polite">
					<div><h4><?php echo esc_html( $strings['rate_title'] ); ?></h4><div class="shcd-tiamis-rating-options"><button type="button" data-rating="4" data-label="<?php echo esc_attr( $strings['rate_great'] ); ?>"><i>😍</i><span><?php echo esc_html( $strings['rate_great'] ); ?></span></button><button type="button" data-rating="3" data-label="<?php echo esc_attr( $strings['rate_good'] ); ?>"><i>🙂</i><span><?php echo esc_html( $strings['rate_good'] ); ?></span></button><button type="button" data-rating="2" data-label="<?php echo esc_attr( $strings['rate_bad'] ); ?>"><i>😕</i><span><?php echo esc_html( $strings['rate_bad'] ); ?></span></button><button type="button" data-rating="1" data-label="<?php echo esc_attr( $strings['rate_awful'] ); ?>"><i>😞</i><span><?php echo esc_html( $strings['rate_awful'] ); ?></span></button></div><button type="button" class="shcd-tiamis-rating-later" data-tiamis-rating-later><?php echo esc_html( $strings['rate_later'] ); ?></button></div>
				</div>
			</section>
		</div>
		<?php
		return ob_get_clean();
	}
}
