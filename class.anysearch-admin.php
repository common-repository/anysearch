<?php

class Anysearch_Admin {
	const NONCE = 'anysearch-update-key';

	private static $initiated = false;
	private static $notices = array();
	private static $allowed = array(
		'a'      => array(
			'href'  => true,
			'title' => true,
		),
		'b'      => array(),
		'code'   => array(),
		'del'    => array(
			'datetime' => true,
		),
		'em'     => array(),
		'i'      => array(),
		'q'      => array(
			'cite' => true,
		),
		'strike' => array(),
		'strong' => array(),
	);

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}


		if ( isset( $_POST['action'] ) && $_POST['action'] == 'enter-key' ) {
			self::enter_api_key();
		}

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'manual_sync' ) {
			self::sync_file();
		}

		if ( isset( $_POST['action'] ) && isset( $_POST['sync-options-submit'] ) ) {
			self::save_sync_setting();
		}
	}

	public static function init_hooks() {

		self::$initiated = true;

		add_action( 'admin_init', array( 'Anysearch_Admin', 'admin_init' ) );
		add_action( 'admin_menu', array( 'Anysearch_Admin', 'admin_menu' ), 5 );
		add_action( 'admin_notices', array( 'Anysearch_Admin', 'display_notice' ) );
		add_action( 'admin_enqueue_scripts', array( 'Anysearch_Admin', 'load_resources' ) );

		add_action( 'wp_ajax_get_sync_status', array( 'Anysearch_Admin', 'get_sync_status_callback' ) );
		add_action( 'wp_ajax_get_notices', array( 'Anysearch_Admin', 'get_notices_callback' ) );

		add_filter( 'all_plugins', array( 'Anysearch_Admin', 'modify_plugin_description' ) );


	}

	public static function get_sync_status_callback() {
		$sync_status = self::get_sync_status();
		$last_upload = get_option( 'anysearch_last_sync' );
		echo json_encode( array( 'sync_status' => $sync_status, 'last_upload' => $last_upload ) );
		wp_die();
	}

	public static function get_notices_callback() {
		self::display_notice();
		wp_die();
	}


	public static function admin_init() {
		if ( get_option( 'activated_anysearch' ) ) {
			delete_option( 'activated_anysearch' );
			if ( ! headers_sent() ) {
				wp_redirect( add_query_arg( array(
					'page' => 'anysearch-config',
					'view' => 'start'
				), admin_url( 'admin.php' ) ) );
			}
		}

		if ( ! get_option( 'anysearch_sync_settings' ) ) {
			$sync_settings = array(
				'partial_sync'             => true,
				'out_of_stock_remove_sync' => true,
				'priod_full_sync'          => 'daily'
			);
			update_option( 'anysearch_sync_settings', $sync_settings );
		}

		load_plugin_textdomain( 'anysearch' );

	}

	public static function admin_menu() {
		self::load_menu();
	}

	public static function admin_head() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
	}

	public static function load_menu() {
		add_menu_page( esc_html__( 'Anysearch', 'anysearch' ), esc_html__( 'Anysearch', 'anysearch' ), 'manage_options', 'anysearch-config', array(
			'Anysearch_Admin',
			'display_page'
		) );
	}

	public static function load_resources() {
		global $hook_suffix;
		if ( in_array( $hook_suffix, apply_filters( 'anysearch_admin_page_hook_suffixes', array(
			'toplevel_page_anysearch-config',
			'plugins.php',
		) ) ) ) {
			wp_register_style( 'anysearch.css', plugin_dir_url( __FILE__ ) . '_inc/anysearch.css', array(), ANYSEARCH_VERSION );
			wp_enqueue_style( 'anysearch.css' );

			wp_register_script( 'anysearch.js', plugin_dir_url( __FILE__ ) . '_inc/anysearch.js', array( 'jquery' ), ANYSEARCH_VERSION );
			wp_enqueue_script( 'anysearch.js' );
		}
	}

	public static function enter_api_key() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( __( 'Cheatin&#8217; uh?', 'anysearch' ) );
		}

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], self::NONCE ) ) {
			return false;
		}

		$new_key = sanitize_text_field( $_POST['key'] );
		$old_key = Anysearch::get_api_key();

		if ( empty( $new_key ) ) {
			if ( ! empty( $old_key ) ) {
				delete_option( 'anysearch_api_key' );
				delete_option( 'anysearch_frontend_key' );
				self::$notices[] = 'new-key-empty';
			}
		} elseif ( $new_key != $old_key ) {
			self::save_key( $new_key );
		}

		return true;
	}

	public static function save_key( $api_key ) {
		$key_status = Anysearch::verify_key( $api_key );
		if ( $key_status == 'valid' ) {
			update_option( 'anysearch_api_key', $api_key );
			update_option( 'anysearch_frontend_key', self::get_frontend_key() );
			self::$notices['status'] = 'new-key-valid';
		} elseif ( in_array( $key_status, array( 'invalid', 'failed' ) ) ) {
			self::$notices['status'] = 'new-key-' . $key_status;
		}
	}

	public static function save_sync_setting() {
		if ( wp_verify_nonce( $_POST['_wpnonce'], self::NONCE ) ) {
			$sync_settings = get_option( 'anysearch_sync_settings' );

			$partial_sync_checkbox = isset( $_POST['anysearch_partial_sync'] )
			                         && sanitize_key( $_POST['anysearch_partial_sync'] ) == 'true';

			$sync_settings['partial_sync'] = $partial_sync_checkbox;

			$out_of_stock_remove_sync = isset( $_POST['anysearch_out_of_stock_remove_sync'] )
			                            && sanitize_key( $_POST['anysearch_out_of_stock_remove_sync'] ) == 'true';

			$sync_settings['out_of_stock_remove_sync'] = $out_of_stock_remove_sync;

			$allowed_periods = array( 'never', 'daily', '3daily', 'weekly' );

			$priod_full_sync                  = ( isset( $_POST['anysearch_priod_full_sync'] )
			                                      && in_array( $_POST['anysearch_priod_full_sync'], $allowed_periods ) )
				? sanitize_key( $_POST['anysearch_priod_full_sync'] ) : 'never';
			$sync_settings['priod_full_sync'] = $priod_full_sync;

			update_option( 'anysearch_sync_settings', $sync_settings );
			self::$notices['status'] = 'sync-settings-save';
		}
	}

	public static function get_sync_status() {
		$status = get_option( 'anysearch_sync_status' );

		function get_remote_status() {
			$api_key        = Anysearch::get_api_key();
			$anysearch_user = Anysearch_Admin::get_Anysearch_user( $api_key );

			return $anysearch_user->file_status;
		}

		if ( $status == 1 ) {
			return 'creating YML';
		} elseif ( $status == 2 ) {
			return 'uploading to server';
		} elseif ( $status == 3 ) {
			update_option( 'anysearch_sync_status', 0 );

			return get_remote_status();
		} else {
			return get_remote_status();
		}
	}

	public static function sync_file() {
		Anysearch::$taskmanager->push_to_queue( array( 'is_manual_sync' => true, 'is_full_sync' => true ) );
		update_option( 'anysearch_sync_status', 1 );
		self::add_notice( 'sync-started' );

		return true;
	}

	public static function get_frontend_key() {
		$response = Anysearch::http_post( Anysearch::build_query( array( 'accessToken' => Anysearch::get_api_key() ) ), 'get-widget-token' );

		if ( isset( $response[1] ) && strlen( $response[1] ) == 70 ) {
			return $response[1];
		} else {
			return false;
		}

	}

	public static function get_server_connectivity() {
		if ( wp_http_supports( array( 'ssl' ) ) ) {
			$response = wp_remote_get( 'https://' . ANYSEARCH_API_HOST . '/api/test' );
			if ( wp_remote_retrieve_body( $response ) != 'connected' ) {
				$response = wp_remote_get( 'http://' . ANYSEARCH_API_HOST . '/api/test' );
			}
		} else {
			$response = 'Can not establish connection';
		}

		if ( $response && 'connected' == wp_remote_retrieve_body( $response ) ) {
			return true;
		}

		return false;
	}

	public static function get_page_url( $page = 'config' ) {
		$args = array( 'page' => 'anysearch-config' );

		if ( $page == 'delete_key' ) {
			$args = array(
				'page'     => 'anysearch-config',
				'view'     => 'start',
				'action'   => 'delete-key',
				'_wpnonce' => wp_create_nonce( self::NONCE )
			);
		}

		$url = add_query_arg( $args, admin_url( 'admin.php' ) );

		return $url;
	}

	public static function get_anysearch_user( $api_key ) {
		$anysearch_user = false;

		$subscription_verification = Anysearch::http_post( Anysearch::build_query( array( 'accessToken' => $api_key ) ), 'get-subscription' );


		if ( ! empty( $subscription_verification[1] ) ) {
			if ( 'invalid' !== $subscription_verification[1] ) {
				$anysearch_user = json_decode( $subscription_verification[1] );
			}
		}

		return $anysearch_user;
	}

	public static function add_notice( $notice ) {
		$notices   = get_option( 'anysearch_notices' );
		$notices[] = $notice;
		update_option( 'anysearch_notices', $notices );
	}

	public static function display_notice() {
		global $hook_suffix;

		$notices = get_option( 'anysearch_notices' );
		if ( $notices ) {
			foreach ( $notices as $notice ) {
				self::$notices['status'] = $notice;
			}
			self::display_status();
			delete_option( 'anysearch_notices' );
		}

		if ( $hook_suffix == 'plugins.php' && ! Anysearch::get_api_key() ) {
			self::display_api_key_warning();
		}

	}

	public static function display_api_key_warning() {
		Anysearch::view( 'notice', array( 'type' => 'plugin' ) );
	}

	public static function display_page() {
		if ( ! Anysearch::get_api_key() || ! Anysearch::get_frontend_key() || ( isset( $_GET['view'] ) && $_GET['view'] == 'start' ) ) {
			self::display_start_page();
		} else {
			self::display_configuration_page();
		}
	}

	public static function display_start_page() {
		if ( isset( $_GET['action'] ) ) {
			if ( $_GET['action'] == 'delete-key' ) {
				if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], self::NONCE ) ) {
					delete_option( 'anysearch_frontend_key' );
					delete_option( 'anysearch_api_key' );
				}
			}
		}

		Anysearch::view( 'start' );
	}

	public static function display_expired_page( $anysearch_user ) {

		Anysearch::view( 'expired', compact( 'anysearch_user' ) );
	}

	public static function display_configuration_page() {
		$api_key        = Anysearch::get_api_key();
		$anysearch_user = self::get_Anysearch_user( $api_key );


		if ( ! $anysearch_user ) {
			// This could happen if the user's key became invalid after it was previously valid and successfully set up.
			self::$notices['status'] = 'existing-key-invalid';
			self::display_start_page();

			return;
		}

		if ( $anysearch_user->status == false ) {
			// This could happen if the user's key became invalid after it was previously valid and successfully set up.
//			self::$notices['status'] = 'license-expired';
			self::display_expired_page( $anysearch_user );

			return;
		}

		$notices = array();

		Anysearch::view( 'config', compact( 'api_key', 'anysearch_user', 'notices' ) );
	}

	public static function display_status() {
		if ( ! self::get_server_connectivity() ) {
			Anysearch::view( 'notice', array( 'type' => 'servers-be-down' ) );
		} else if ( ! empty( self::$notices ) ) {
			foreach ( self::$notices as $index => $type ) {
				if ( is_object( $type ) ) {
					$notice_header = $notice_text = '';

					if ( property_exists( $type, 'notice_header' ) ) {
						$notice_header = wp_kses( $type->notice_header, self::$allowed );
					}

					if ( property_exists( $type, 'notice_text' ) ) {
						$notice_text = wp_kses( $type->notice_text, self::$allowed );
					}

					if ( property_exists( $type, 'status' ) ) {
						$type = wp_kses( $type->status, self::$allowed );
						Anysearch::view( 'notice', compact( 'type', 'notice_header', 'notice_text' ) );

						unset( self::$notices[ $index ] );
					}
				} else {
					Anysearch::view( 'notice', compact( 'type' ) );

					unset( self::$notices[ $index ] );
				}
			}
		}
	}

	/**
	 * When Anysearch is active, remove the "Activate Anysearch" step from the plugin description.
	 */
	public static function modify_plugin_description( $all_plugins ) { //todo
		if ( isset( $all_plugins['anysearch/anysearch.php'] ) ) {
			if ( Anysearch::get_api_key() ) {
				$all_plugins['anysearch/anysearch.php']['Description'] = esc_html__( 'Anysearch - smart product search widget for web stores. It replaces regular search field with an advanced search.', 'anysearch' );
			} else {
				$all_plugins['anysearch/anysearch.php']['Description'] = esc_html__( 'Anysearch - smart product search widget for web stores. It replaces regular search field with an advanced search.', 'anysearch' );
			}
		}

		return $all_plugins;
	}
}
