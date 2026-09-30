<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Advanced collaboration, automation, PWA, Abilities API and real-time helpers.
 */
class Tiamis_Chat_Platform {
	const CAP_INBOX    = 'tiamis_manage_inbox';
	const CAP_REPLY    = 'tiamis_reply';
	const CAP_REPORTS  = 'tiamis_view_reports';
	const CAP_SETTINGS = 'tiamis_manage_settings';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_rewrites' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'serve_pwa_assets' ), 0 );
		add_action( 'wp_head', array( __CLASS__, 'manifest_link' ), 2 );
		add_action( 'init', array( __CLASS__, 'register_block' ) );
		add_action( 'wp_abilities_api_categories_init', array( __CLASS__, 'register_ability_category' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );
		add_action( 'shcd_tiamis_message_created', array( __CLASS__, 'on_message_created' ), 10, 2 );
		add_action( 'shcd_tiamis_conversation_closed', array( __CLASS__, 'on_conversation_closed' ), 10, 1 );
		add_action( 'shcd_tiamis_qa_conversation', array( __CLASS__, 'run_quality_review' ), 10, 1 );
	}

	public static function activate() {
		self::ensure_roles();
		self::register_rewrites();
		flush_rewrite_rules( false );
	}

	public static function deactivate() {
		flush_rewrite_rules( false );
	}

	public static function ensure_roles() {
		if ( '1.0.0' === get_option( 'shcd_tiamis_roles_version' ) ) {
			return;
		}
		$administrator = get_role( 'administrator' );
		foreach ( array( self::CAP_INBOX, self::CAP_REPLY, self::CAP_REPORTS, self::CAP_SETTINGS ) as $cap ) {
			if ( $administrator ) {
				$administrator->add_cap( $cap );
			}
		}
		add_role(
			'tiamis_operator',
			'کارشناس تیامیس',
			array(
				'read'              => true,
				self::CAP_INBOX    => true,
				self::CAP_REPLY    => true,
				self::CAP_REPORTS  => true,
			)
		);
		add_role(
			'tiamis_supervisor',
			'سرپرست تیامیس',
			array(
				'read'              => true,
				self::CAP_INBOX    => true,
				self::CAP_REPLY    => true,
				self::CAP_REPORTS  => true,
				self::CAP_SETTINGS => true,
			)
		);
		update_option( 'shcd_tiamis_roles_version', '1.0.0', false );
	}

	public static function can_inbox() {
		return current_user_can( self::CAP_INBOX ) || current_user_can( 'manage_options' );
	}

	public static function can_reply() {
		return current_user_can( self::CAP_REPLY ) || current_user_can( 'manage_options' );
	}

	public static function can_settings() {
		return current_user_can( self::CAP_SETTINGS ) || current_user_can( 'manage_options' );
	}

	public static function audit( $event, $conversation_id = 0, $details = array(), $user_id = null ) {
		global $wpdb;
		return false !== $wpdb->insert(
			Tiamis_Chat_DB::table( 'audit' ),
			array(
				'conversation_id' => absint( $conversation_id ),
				'wp_user_id'      => null === $user_id ? get_current_user_id() : absint( $user_id ),
				'event_type'      => sanitize_key( $event ),
				'details'         => wp_json_encode( is_array( $details ) ? $details : array( 'value' => $details ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
				'created_at'      => Tiamis_Chat_Core::now(),
			)
		);
	}

	public static function get_audit( $conversation_id, $limit = 100 ) {
		global $wpdb;
		$table = Tiamis_Chat_DB::table( 'audit' );
		$users = $wpdb->users;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT a.*, u.display_name FROM %i a LEFT JOIN %i u ON u.ID = a.wp_user_id WHERE a.conversation_id = %d ORDER BY a.id DESC LIMIT %d',
				$table,
				$users,
				absint( $conversation_id ),
				min( 300, max( 1, absint( $limit ) ) )
			)
		);
	}

	public static function route_conversation( $conversation_id ) {
		global $wpdb;
		$conversation = Tiamis_Chat_Core::get_conversation( $conversation_id );
		if ( ! $conversation || ! empty( $conversation->assigned_to ) ) {
			return $conversation;
		}
		$settings = Tiamis_Chat_Core::settings();
		if ( '1' !== (string) ( $settings['auto_assignment_enabled'] ?? '1' ) ) {
			return $conversation;
		}
		$agents = Tiamis_Chat_Core::agents( $settings );
		if ( ! $agents ) {
			return $conversation;
		}
		$conversation_table = Tiamis_Chat_DB::table( 'conversations' );
		$candidates = array();
		foreach ( $agents as $index => $agent ) {
			if ( isset( $agent['active'] ) && ! $agent['active'] ) {
				continue;
			}
			$capacity = max( 1, absint( $agent['capacity'] ?? 5 ) );
			$wp_user_id = absint( $agent['wp_user_id'] ?? 0 );
			$load = $wp_user_id ? absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE assigned_to = %d AND status IN ('open','pending')", $conversation_table, $wp_user_id ) ) ) : 0;
			if ( $load >= $capacity ) {
				continue;
			}
			$candidates[] = array( 'index' => $index, 'user_id' => $wp_user_id, 'load' => $load, 'agent' => $agent );
		}
		if ( ! $candidates ) {
			return $conversation;
		}
		usort(
			$candidates,
			static function ( $a, $b ) {
				return $a['load'] <=> $b['load'];
			}
		);
		$winner = $candidates[0];
		$department = sanitize_text_field( $winner['agent']['department'] ?? ( $settings['default_department'] ?? 'پشتیبانی' ) );
		$sla_minutes = max( 1, absint( $settings['sla_first_response_minutes'] ?? 15 ) );
		$sla_due = wp_date( 'Y-m-d H:i:s', time() + $sla_minutes * MINUTE_IN_SECONDS, wp_timezone() );
		$wpdb->update(
			$conversation_table,
			array(
				'assigned_to'          => $winner['user_id'],
				'assigned_agent_index' => absint( $winner['index'] ),
				'department'           => $department,
				'sla_due_at'           => $sla_due,
				'updated_at'           => Tiamis_Chat_Core::now(),
			),
			array( 'id' => absint( $conversation_id ) )
		);
		self::audit( 'conversation_assigned', $conversation_id, array( 'agent_index' => $winner['index'], 'user_id' => $winner['user_id'], 'department' => $department ), 0 );
		return Tiamis_Chat_Core::get_conversation( $conversation_id );
	}

	public static function acquire_lock( $conversation_id, $ttl = 45 ) {
		global $wpdb;
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error( 'tiamis_lock_auth', 'کاربر معتبر نیست.' );
		}
		$conversation = Tiamis_Chat_Core::get_conversation( $conversation_id );
		if ( ! $conversation ) {
			return new WP_Error( 'tiamis_lock_missing', 'گفتگو پیدا نشد.' );
		}
		$now = current_time( 'timestamp' );
		$lock_expires = ! empty( $conversation->lock_expires_at ) ? strtotime( $conversation->lock_expires_at ) : 0;
		if ( ! empty( $conversation->lock_user_id ) && (int) $conversation->lock_user_id !== $user_id && $lock_expires > $now ) {
			$owner = get_userdata( (int) $conversation->lock_user_id );
			return new WP_Error( 'tiamis_locked', $owner ? sprintf( 'این گفتگو اکنون در اختیار %s است.', $owner->display_name ) : 'این گفتگو در اختیار کارشناس دیگری است.', array( 'owner' => (int) $conversation->lock_user_id ) );
		}
		$expires = wp_date( 'Y-m-d H:i:s', $now + max( 15, min( 120, absint( $ttl ) ) ), wp_timezone() );
		$wpdb->update( Tiamis_Chat_DB::table( 'conversations' ), array( 'lock_user_id' => $user_id, 'lock_expires_at' => $expires ), array( 'id' => absint( $conversation_id ) ) );
		return array( 'owner' => $user_id, 'expires_at' => $expires );
	}

	public static function release_lock( $conversation_id ) {
		global $wpdb;
		$conversation = Tiamis_Chat_Core::get_conversation( $conversation_id );
		if ( ! $conversation ) {
			return false;
		}
		if ( (int) $conversation->lock_user_id && (int) $conversation->lock_user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		return false !== $wpdb->update( Tiamis_Chat_DB::table( 'conversations' ), array( 'lock_user_id' => 0, 'lock_expires_at' => null ), array( 'id' => absint( $conversation_id ) ) );
	}

	public static function get_tasks( $conversation_id ) {
		global $wpdb;
		$table = Tiamis_Chat_DB::table( 'tasks' );
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE conversation_id = %d ORDER BY status = 'done', id ASC", $table, absint( $conversation_id ) ) );
	}

	public static function add_task( $conversation_id, $title, $due_at = '' ) {
		global $wpdb;
		$title = trim( sanitize_text_field( $title ) );
		if ( '' === $title ) {
			return new WP_Error( 'tiamis_task_empty', 'عنوان پیگیری را بنویسید.' );
		}
		$now = Tiamis_Chat_Core::now();
		$wpdb->insert(
			Tiamis_Chat_DB::table( 'tasks' ),
			array(
				'conversation_id' => absint( $conversation_id ),
				'title'           => $title,
				'status'          => 'open',
				'assigned_to'     => get_current_user_id(),
				'due_at'          => $due_at ? sanitize_text_field( $due_at ) : null,
				'created_by'      => get_current_user_id(),
				'created_at'      => $now,
				'updated_at'      => $now,
			)
		);
		$id = (int) $wpdb->insert_id;
		self::audit( 'task_created', $conversation_id, array( 'task_id' => $id, 'title' => $title ) );
		return $id ?: new WP_Error( 'tiamis_task_failed', 'پیگیری ذخیره نشد.' );
	}

	public static function toggle_task( $task_id, $conversation_id ) {
		global $wpdb;
		$table = Tiamis_Chat_DB::table( 'tasks' );
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d AND conversation_id = %d', $table, absint( $task_id ), absint( $conversation_id ) ) );
		if ( ! $row ) {
			return new WP_Error( 'tiamis_task_missing', 'پیگیری پیدا نشد.' );
		}
		$status = 'done' === $row->status ? 'open' : 'done';
		$wpdb->update( $table, array( 'status' => $status, 'updated_at' => Tiamis_Chat_Core::now() ), array( 'id' => (int) $row->id ) );
		self::audit( 'task_' . $status, $conversation_id, array( 'task_id' => (int) $row->id ) );
		return $status;
	}

	public static function get_tags( $conversation_id = 0 ) {
		global $wpdb;
		$tags = Tiamis_Chat_DB::table( 'tags' );
		if ( ! $conversation_id ) {
			return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY name ASC', $tags ) );
		}
		$map = Tiamis_Chat_DB::table( 'conversation_tags' );
		return $wpdb->get_results( $wpdb->prepare( 'SELECT t.* FROM %i t INNER JOIN %i m ON m.tag_id = t.id WHERE m.conversation_id = %d ORDER BY t.name', $tags, $map, absint( $conversation_id ) ) );
	}

	public static function assign_tag( $conversation_id, $name, $color = '#38008a' ) {
		global $wpdb;
		$name = trim( sanitize_text_field( $name ) );
		if ( '' === $name ) {
			return new WP_Error( 'tiamis_tag_empty', 'نام برچسب را بنویسید.' );
		}
		$slug = sanitize_title( $name );
		$tags = Tiamis_Chat_DB::table( 'tags' );
		$tag_id = absint( $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE slug = %s', $tags, $slug ) ) );
		if ( ! $tag_id ) {
			$wpdb->insert( $tags, array( 'name' => $name, 'slug' => $slug, 'color' => sanitize_hex_color( $color ) ?: '#38008a', 'created_at' => Tiamis_Chat_Core::now() ) );
			$tag_id = (int) $wpdb->insert_id;
		}
		$wpdb->replace( Tiamis_Chat_DB::table( 'conversation_tags' ), array( 'conversation_id' => absint( $conversation_id ), 'tag_id' => $tag_id, 'created_at' => Tiamis_Chat_Core::now() ) );
		self::audit( 'tag_added', $conversation_id, array( 'tag_id' => $tag_id, 'name' => $name ) );
		return $tag_id;
	}

	public static function remove_tag( $conversation_id, $tag_id ) {
		global $wpdb;
		$result = $wpdb->delete( Tiamis_Chat_DB::table( 'conversation_tags' ), array( 'conversation_id' => absint( $conversation_id ), 'tag_id' => absint( $tag_id ) ) );
		self::audit( 'tag_removed', $conversation_id, array( 'tag_id' => absint( $tag_id ) ) );
		return false !== $result;
	}

	public static function knowledge_search( $query, $limit = 5 ) {
		global $wpdb;
		$query = trim( wp_strip_all_tags( (string) $query ) );
		if ( '' === $query ) {
			return array();
		}
		$table = Tiamis_Chat_DB::table( 'knowledge' );
		$like = '%' . $wpdb->esc_like( $query ) . '%';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, title, content, source_type, source_id FROM %i WHERE status = 'active' AND (title LIKE %s OR content LIKE %s OR keywords LIKE %s) ORDER BY updated_at DESC LIMIT %d",
				$table,
				$like,
				$like,
				$like,
				min( 10, max( 1, absint( $limit ) ) )
			)
		);
	}

	public static function sync_knowledge_from_posts( $post_types = array( 'post', 'page', 'product' ) ) {
		global $wpdb;
		$post_types = array_values( array_filter( array_map( 'sanitize_key', (array) $post_types ), 'post_type_exists' ) );
		if ( ! $post_types ) {
			$post_types = array( 'post', 'page' );
		}
		$posts = get_posts( array( 'post_type' => $post_types, 'post_status' => 'publish', 'numberposts' => 500, 'orderby' => 'modified', 'order' => 'DESC' ) );
		$table = Tiamis_Chat_DB::table( 'knowledge' );
		$count = 0;
		foreach ( $posts as $post ) {
			$content = trim( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) );
			if ( '' === $content ) {
				continue;
			}
			$existing = absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM %i WHERE source_type = 'post' AND source_id = %d", $table, $post->ID ) ) );
			$data = array(
				'source_type' => 'post',
				'source_id'   => $post->ID,
				'title'       => get_the_title( $post ),
				'content'     => function_exists( 'mb_substr' ) ? mb_substr( $content, 0, 30000 ) : substr( $content, 0, 30000 ),
				'keywords'    => implode( ', ', wp_get_post_terms( $post->ID, array( 'post_tag', 'product_tag' ), array( 'fields' => 'names' ) ) ?: array() ),
				'language'    => 'fa',
				'status'      => 'active',
				'updated_at'  => Tiamis_Chat_Core::now(),
			);
			if ( $existing ) {
				$wpdb->update( $table, $data, array( 'id' => $existing ) );
			} else {
				$data['created_at'] = Tiamis_Chat_Core::now();
				$wpdb->insert( $table, $data );
			}
			$count++;
		}
		return $count;
	}

	public static function dashboard_metrics( $days = 30 ) {
		global $wpdb;
		$days = min( 365, max( 1, absint( $days ) ) );
		$since = wp_date( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS, wp_timezone() );
		$c = Tiamis_Chat_DB::table( 'conversations' );
		$m = Tiamis_Chat_DB::table( 'messages' );
		$r = Tiamis_Chat_DB::table( 'ratings' );
		return array(
			'conversations' => absint( $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE created_at >= %s', $c, $since ) ) ),
			'open'          => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE status = 'open'", $c ) ) ),
			'unread'        => absint( $wpdb->get_var( $wpdb->prepare( 'SELECT SUM(unread_admin) FROM %i', $c ) ) ),
			'messages'      => absint( $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE created_at >= %s', $m, $since ) ) ),
			'ai_messages'   => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE sender = 'ai' AND created_at >= %s", $m, $since ) ) ),
			'avg_rating'    => round( (float) $wpdb->get_var( $wpdb->prepare( 'SELECT AVG(rating) FROM %i WHERE created_at >= %s', $r, $since ) ), 2 ),
			'avg_first_response_minutes' => round( (float) $wpdb->get_var( $wpdb->prepare( 'SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, first_response_at))/60 FROM %i WHERE first_response_at IS NOT NULL AND created_at >= %s', $c, $since ) ), 1 ),
		);
	}

	public static function on_message_created( $message, $conversation ) {
		if ( ! $message || ! $conversation ) {
			return;
		}
		if ( 'visitor' === $message->sender ) {
			self::route_conversation( $conversation->id );
		}
		if ( 'admin' === $message->sender && empty( $conversation->first_response_at ) ) {
			global $wpdb;
			$wpdb->update( Tiamis_Chat_DB::table( 'conversations' ), array( 'first_response_at' => Tiamis_Chat_Core::now() ), array( 'id' => (int) $conversation->id ) );
		}
		self::publish_realtime(
			$conversation->public_id,
			array(
				'type'         => 'message.created',
				'message'      => Tiamis_Chat_Core::public_message_data( $message ),
				'conversation' => Tiamis_Chat_Core::public_conversation_data( Tiamis_Chat_Core::get_conversation( $conversation->id ) ),
			)
		);
	}

	public static function on_conversation_closed( $conversation_id ) {
		global $wpdb;
		$conversation_id = absint( $conversation_id );
		$wpdb->update( Tiamis_Chat_DB::table( 'conversations' ), array( 'resolved_at' => Tiamis_Chat_Core::now() ), array( 'id' => $conversation_id ) );
		self::audit( 'conversation_closed', $conversation_id );
		$settings = Tiamis_Chat_Core::settings();
		if ( '1' === (string) ( $settings['ai_qa_enabled'] ?? '0' ) && ! wp_next_scheduled( 'shcd_tiamis_qa_conversation', array( $conversation_id ) ) ) {
			wp_schedule_single_event( time() + 5, 'shcd_tiamis_qa_conversation', array( $conversation_id ) );
		}
	}

	public static function run_quality_review( $conversation_id ) {
		global $wpdb;
		$conversation_id = absint( $conversation_id );
		if ( ! $conversation_id ) {
			return;
		}
		$insights = Tiamis_Chat_Integrations::ai_insights( $conversation_id );
		if ( ! is_array( $insights ) ) {
			return;
		}
		$quality = sanitize_textarea_field( (string) ( $insights['quality'] ?? '' ) );
		$score = 70.0;
		if ( preg_match( '/(عالی|روشن|کامل|حل شد|مثبت|excellent|resolved|ممتاز|واضح)/iu', $quality ) ) {
			$score = 90.0;
		} elseif ( preg_match( '/(ضعف|نامناسب|ناکافی|نیازمند|negative|poor|ضعيف|غير كاف)/iu', $quality ) ) {
			$score = 45.0;
		}
		$wpdb->update(
			Tiamis_Chat_DB::table( 'conversations' ),
			array(
				'qa_score'   => $score,
				'qa_summary' => $quality,
				'sentiment'  => sanitize_key( (string) ( $insights['sentiment'] ?? 'neutral' ) ),
			),
			array( 'id' => $conversation_id )
		);
		self::audit( 'quality_reviewed', $conversation_id, array( 'score' => $score, 'summary' => $quality ), 0 );
	}

	public static function publish_realtime( $room, $payload ) {
		$settings = Tiamis_Chat_Core::settings();
		$url = esc_url_raw( $settings['realtime_publish_url'] ?? '' );
		if ( '' === $url || 'websocket' !== ( $settings['realtime_mode'] ?? 'auto' ) ) {
			return;
		}
		$secret = Tiamis_Chat_Core::decrypt_secret( $settings['realtime_secret'] ?? '' );
		wp_remote_post(
			$url,
			array(
				'timeout'  => 2,
				'blocking' => false,
				'headers'  => array( 'Content-Type' => 'application/json', 'X-Tiamis-Realtime-Secret' => $secret ),
				'body'     => wp_json_encode( array( 'room' => sanitize_text_field( $room ), 'payload' => $payload ) ),
			)
		);
	}

	public static function register_rewrites() {
		add_rewrite_rule( '^tiamis-sw\.js$', 'index.php?shcd_tiamis_sw=1', 'top' );
		add_rewrite_rule( '^tiamis-manifest\.webmanifest$', 'index.php?shcd_tiamis_manifest=1', 'top' );
	}

	public static function query_vars( $vars ) {
		$vars[] = 'shcd_tiamis_sw';
		$vars[] = 'shcd_tiamis_manifest';
		return $vars;
	}

	public static function serve_pwa_assets() {
		$settings = Tiamis_Chat_Core::settings();
		if ( '1' !== (string) ( $settings['pwa_enabled'] ?? '0' ) ) {
			return;
		}
		if ( get_query_var( 'shcd_tiamis_manifest' ) ) {
			nocache_headers();
			header( 'Content-Type: application/manifest+json; charset=UTF-8' );
			echo wp_json_encode(
				array(
					'name'             => get_bloginfo( 'name' ) . ' — پشتیبانی',
					'short_name'       => 'Tiamis',
					'start_url'        => home_url( '/' ),
					'display'          => 'standalone',
					'background_color' => '#f7f4fb',
					'theme_color'      => '#38008a',
					'icons'            => array(),
				)
			);
			exit;
		}
		if ( get_query_var( 'shcd_tiamis_sw' ) ) {
			nocache_headers();
			header( 'Content-Type: application/javascript; charset=UTF-8' );
			header( 'Service-Worker-Allowed: /' );
			$cache = 'tiamis-' . preg_replace( '/[^a-z0-9._-]/i', '-', TIAMIS_CHAT_VERSION );
			echo "const CACHE='" . esc_js( $cache ) . "';\n";
			echo "self.addEventListener('install',e=>{self.skipWaiting();e.waitUntil(caches.open(CACHE));});\n";
			echo "self.addEventListener('activate',e=>{e.waitUntil(caches.keys().then(k=>Promise.all(k.filter(x=>x.indexOf('tiamis-')===0&&x!==CACHE).map(x=>caches.delete(x)))).then(()=>self.clients.claim()));});\n";
			echo "self.addEventListener('fetch',e=>{if(e.request.method!=='GET'||new URL(e.request.url).origin!==location.origin)return;e.respondWith(fetch(e.request).then(r=>{const c=r.clone();caches.open(CACHE).then(x=>x.put(e.request,c));return r;}).catch(()=>caches.match(e.request)));});\n";
			echo "self.addEventListener('notificationclick',e=>{e.notification.close();e.waitUntil(clients.matchAll({type:'window',includeUncontrolled:true}).then(list=>list.length?list[0].focus():clients.openWindow('/')));});\n";
			exit;
		}
	}

	public static function manifest_link() {
		$settings = Tiamis_Chat_Core::settings();
		if ( '1' === (string) ( $settings['pwa_enabled'] ?? '0' ) ) {
			echo '<link rel="manifest" href="' . esc_url( home_url( '/tiamis-manifest.webmanifest' ) ) . '">';
			echo '<meta name="theme-color" content="#38008a">';
		}
	}

	public static function register_block() {
		$dir = TIAMIS_CHAT_DIR . 'blocks/chat-button';
		if ( file_exists( $dir . '/block.json' ) && function_exists( 'register_block_type' ) ) {
			register_block_type(
				$dir,
				array(
					'render_callback' => static function ( $attributes ) {
						$label = sanitize_text_field( $attributes['label'] ?? 'گفتگو با پشتیبانی' );
						return '<button type="button" class="wp-block-shcd-tiamis-chat-button" data-tiamis-open-chat>' . esc_html( $label ) . '</button>';
					},
				)
			);
		}
	}

	public static function register_ability_category() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}
		wp_register_ability_category(
			'tiamis-support',
			array(
				'label'       => 'پشتیبانی تیامیس',
				'description' => 'ابزارهای امن برای مشاهده و مدیریت گفتگوهای پشتیبانی.',
			)
		);
	}

	public static function register_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}
		wp_register_ability(
			'shcd-tiamis/list-conversations',
			array(
				'label'               => 'فهرست گفتگوهای تیامیس',
				'description'         => 'گفتگوهای اخیر را برای گزارش‌گیری یا اتوماسیون بازمی‌گرداند.',
				'category'            => 'tiamis-support',
				'input_schema'        => array( 'type' => 'object', 'properties' => array( 'status' => array( 'type' => 'string' ), 'limit' => array( 'type' => 'integer' ) ) ),
				'output_schema'       => array( 'type' => 'array', 'items' => array( 'type' => 'object' ) ),
				'execute_callback'    => array( __CLASS__, 'ability_list_conversations' ),
				'permission_callback' => array( __CLASS__, 'can_inbox' ),
				'meta'                => array( 'annotations' => array( 'readonly' => true ), 'show_in_rest' => true ),
			)
		);
		wp_register_ability(
			'shcd-tiamis/reply-conversation',
			array(
				'label'               => 'پاسخ به گفتگو',
				'description'         => 'یک پاسخ انسانی ثبت می‌کند و آن را به کانال کاربر می‌فرستد.',
				'category'            => 'tiamis-support',
				'input_schema'        => array( 'type' => 'object', 'required' => array( 'conversation_id', 'message' ), 'properties' => array( 'conversation_id' => array( 'type' => 'integer' ), 'message' => array( 'type' => 'string', 'minLength' => 1 ) ) ),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( __CLASS__, 'ability_reply' ),
				'permission_callback' => array( __CLASS__, 'can_reply' ),
				'meta'                => array( 'annotations' => array( 'readonly' => false, 'destructive' => false ), 'show_in_rest' => true ),
			)
		);
	}

	public static function ability_list_conversations( $input = array() ) {
		global $wpdb;
		$input = is_array( $input ) ? $input : array();
		$status = isset( $input['status'] ) && in_array( $input['status'], array( 'open', 'pending', 'closed' ), true ) ? $input['status'] : '';
		$limit = min( 100, max( 1, absint( $input['limit'] ?? 20 ) ) );
		$table = Tiamis_Chat_DB::table( 'conversations' );
		if ( $status ) {
			$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE status = %s ORDER BY last_message_at DESC LIMIT %d', $table, $status, $limit ) );
		} else {
			$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY last_message_at DESC LIMIT %d', $table, $limit ) );
		}
		return array_map( array( 'Tiamis_Chat_Core', 'public_conversation_data' ), $rows );
	}

	public static function ability_reply( $input ) {
		$input = is_array( $input ) ? $input : array();
		$conversation = Tiamis_Chat_Core::get_conversation( absint( $input['conversation_id'] ?? 0 ) );
		if ( ! $conversation ) {
			return new WP_Error( 'tiamis_missing', 'گفتگو پیدا نشد.' );
		}
		$user = wp_get_current_user();
		$message = Tiamis_Chat_Core::add_message( $conversation->id, 'admin', (string) ( $input['message'] ?? '' ), 'ability', $user->display_name, array( 'wp_user_id' => $user->ID ) );
		if ( ! is_wp_error( $message ) ) {
			Tiamis_Chat_Core::queue_outbound( $message->id, 'operator' );
			return Tiamis_Chat_Core::public_message_data( $message );
		}
		return $message;
	}

}
