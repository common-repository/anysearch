<?php

class Anysearch {

	protected static $initiated = false;
	public static $taskmanager;

	/**
	 * Initializes classes
	 */
	public static function init() {

		if ( ! self::$initiated ) {
			self::init_hooks();
		}

		self::$taskmanager = new Anysearch_Yml();
		Anysearch_Exchange::init();
	}

	/**
	 * Initializes WordPress hooks
	 */
	private static function init_hooks() {
		self::$initiated = true;
	}

	/**
	 * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
	 * @static
	 */
	private static function bail_on_activation( $message, $deactivate = true ) {
		?>
        <!doctype html>
        <html>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>"/>
            <style>
                * {
                    text-align: center;
                    margin: 0;
                    padding: 0;
                    font-family: "Lucida Grande", Verdana, Arial, "Bitstream Vera Sans", sans-serif;
                }

                p {
                    margin-top: 1em;
                    font-size: 18px;
                }
            </style>
        </head>
        <body>
        <p><?php echo esc_html( $message ); ?></p>
        </body>
        </html>
		<?php
		if ( $deactivate ) {
			$plugins   = get_option( 'active_plugins' );
			$anysearch = plugin_basename( ANYSEARCH__PLUGIN_DIR . 'anysearch.php' );
			$update    = false;
			foreach ( $plugins as $i => $plugin ) {
				if ( $plugin === $anysearch ) {
					$plugins[ $i ] = false;
					$update        = true;
				}
			}

			if ( $update ) {
				update_option( 'active_plugins', array_filter( $plugins ) );
			}
		}
		exit;
	}

	public static function view( $name, array $args = array() ) {
		$args = apply_filters( 'anysearch_view_arguments', $args, $name );

		foreach ( $args as $key => $val ) {
			$$key = $val;
		}

		load_plugin_textdomain( 'anysearch', false, 'anysearch/languages' );

		$file = ANYSEARCH__PLUGIN_DIR . 'views/' . $name . '.php';
		include( $file );
	}

	/**
	 * Checks if file is ready on server
	 *
	 * @return bool
	 */
	public static function get_anysearch_user_status() {
		$anysearch_user = false;
		$api_key        = self::get_api_key();

		$subscription_verification = self::http_post( Anysearch::build_query( array( 'accessToken' => $api_key ) ), 'get-subscription' );
		if ( ! empty( $subscription_verification[1] ) ) {
			if ( 'invalid' !== $subscription_verification[1] ) {
				$anysearch_user = json_decode( $subscription_verification[1] );
			}
		}
		if ( $anysearch_user ) {
			if ( $anysearch_user->file_status == 'finished parse' && $anysearch_user->status == true) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Make a POST request to the Anysearch API.
	 *
	 * @param string $request The body of the request.
	 * @param string $path The path for the request.
	 * @param string $ip The specific IP address to hit.
	 *
	 * @return array A two-member array consisting of the headers and the response body, both empty in the case of a failure.
	 */
	public static function http_post( $request, $path, $ip = null ) {

		$anysearch_ua = sprintf( 'WordPress/%s | Anysearch/%s', $GLOBALS['wp_version'], constant( 'ANYSEARCH_VERSION' ) );
		$anysearch_ua = apply_filters( 'anysearch_ua', $anysearch_ua );
		$host = '';
		if ( defined( 'ANYSEARCH_API_HOST' ) )
		    $host      = ANYSEARCH_API_HOST;
		$http_host = $host;

		if ( $ip && long2ip( ip2long( $ip ) ) ) {
			$http_host = $ip;
		}

		$http_args = array(
			'body'        => $request,
			'headers'     => array(
				'Content-Type' => 'application/x-www-form-urlencoded; charset=' . get_option( 'blog_charset' ),
				'Host'         => $host,
				'User-Agent'   => $anysearch_ua,
			),
			'httpversion' => '1.0',
			'timeout'     => 15
		);

		$anysearch_url = $http_anysearch_url = "http://{$http_host}/api/{$path}";

		/**
		 * Try SSL first; if that fails, try without it and don't try it again for a while.
		 */

		$ssl = $ssl_failed = false;

		// Check if SSL requests were disabled fewer than X hours ago.
		$ssl_disabled = get_option( 'anysearch_ssl_disabled' );

		if ( $ssl_disabled && $ssl_disabled < ( time() - 60 * 60 * 24 ) ) { // 24 hours
			$ssl_disabled = false;
			delete_option( 'anysearch_ssl_disabled' );
		} else if ( $ssl_disabled ) {
			do_action( 'anysearch_ssl_disabled' );
		}

		if ( ! $ssl_disabled && ( $ssl = wp_http_supports( array( 'ssl' ) ) ) ) {
			$anysearch_url = set_url_scheme( $anysearch_url, 'https' );

			do_action( 'anysearch_https_request_pre' );
		}

		$response = wp_remote_post( $anysearch_url, $http_args );


		Anysearch::log( compact( 'anysearch_url', 'http_args', 'response' ) );

		if ( $ssl && is_wp_error( $response ) ) {
			do_action( 'anysearch_https_request_failure', $response );

			// Intermittent connection problems may cause the first HTTPS
			// request to fail and subsequent HTTP requests to succeed randomly.
			// Retry the HTTPS request once before disabling SSL for a time.
			$response = wp_remote_post( $anysearch_url, $http_args );

			Anysearch::log( compact( 'anysearch_url', 'http_args', 'response' ) );

			if ( is_wp_error( $response ) ) {
				$ssl_failed = true;

				do_action( 'anysearch_https_request_failure', $response );

				do_action( 'anysearch_http_request_pre' );

				// Try the request again without SSL.
				$response = wp_remote_post( $http_anysearch_url, $http_args );

				Anysearch::log( compact( 'http_anysearch_url', 'http_args', 'response' ) );
			}
		}

		if ( is_wp_error( $response ) ) {
			do_action( 'anysearch_request_failure', $response );

			return array( '', '' );
		}

		if ( $ssl_failed ) {
			// The request failed when using SSL but succeeded without it. Disable SSL for future requests.
			update_option( 'anysearch_ssl_disabled', time() );

			do_action( 'anysearch_https_disabled' );
		}

		$simplified_response = array( $response['headers'], $response['body'] );

		return $simplified_response;
	}

	/**
	 * Log debugging info to the error log.
	 *
	 * Enabled when WP_DEBUG_LOG is enabled (and WP_DEBUG, since according to
	 * core, "WP_DEBUG_DISPLAY and WP_DEBUG_LOG perform no function unless
	 * WP_DEBUG is true), but can be disabled via the anysearch_debug_log filter.
	 *
	 * @param mixed $anysearch_debug The data to log.
	 */
	public static function log( $anysearch_debug ) {
		if ( apply_filters( 'anysearch_debug_log', defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG && defined( 'ANYSEARCH_DEBUG' ) && ANYSEARCH_DEBUG ) ) {
			error_log( print_r( compact( 'anysearch_debug' ), true ) );
		}
	}

	public static function get_api_key() {
		return apply_filters( 'anysearch_get_api_key', get_option( 'anysearch_api_key' ) );
	}

	public static function get_frontend_key() {
		return apply_filters( 'anysearch_get_frontend_key', get_option( 'anysearch_frontend_key' ) );
	}

	public static function check_key_status( $key, $ip = null ) {
		return self::http_post( Anysearch::build_query( array(
			'key'  => $key,
			'blog' => get_option( 'home' )
		) ), 'verify-site-id', $ip );
	}

	public static function verify_key( $key, $ip = null ) {
		// Shortcut for obviously invalid keys.
		if ( strlen( $key ) != 100 ) {
			return 'invalid';
		}

		$response = self::check_key_status( $key, $ip );

		if ( $response[1] != 'valid' && $response[1] != 'invalid' ) {
			return 'failed';
		}

		return $response[1];
	}

	/**
	 * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
	 * @static
	 */
	public static function plugin_activation() {
		if ( version_compare( $GLOBALS['wp_version'], ANYSEARCH__MINIMUM_WP_VERSION, '<' ) ) {
			load_plugin_textdomain( 'anysearch' );

			$message = '<strong>' . sprintf( esc_html__( 'Anysearch %s requires WordPress %s or higher.', 'anysearch' ), ANYSEARCH_VERSION, ANYSEARCH__MINIMUM_WP_VERSION ) . '</strong> ' . sprintf( __( 'Please <a href="%1$s">upgrade WordPress</a> to a current version', 'anysearch' ), 'https://codex.wordpress.org/Upgrading_WordPress' );

			Anysearch::bail_on_activation( $message );
		} elseif ( ! empty( $_SERVER['SCRIPT_NAME'] ) && false !== strpos( $_SERVER['SCRIPT_NAME'], '/wp-admin/plugins.php' ) ) {
			add_option( 'activated_anysearch', true );
		}
	}

	/**
	 * Removes all connection options
	 * @static
	 */
	public static function plugin_deactivation() {
		$anysearch_options = array(
			'anysearch_sync_settings',
			'anysearch_ssl_disabled',
			'anysearch_api_key',
			'anysearch_frontend_key',
			'anysearch_sync_status',
			'anysearch_last_sync',
			'anysearch_success_sync'
		);

		foreach ( $anysearch_options as $option ) {
			delete_option( $option );
		}

		// Remove any scheduled cron jobs.
		$anysearch_cron_events = array(
			'anysearch_full_sync_cron_worker_start'
		);

		foreach ( $anysearch_cron_events as $anysearch_cron_event ) {
			$timestamp = wp_next_scheduled( $anysearch_cron_event );

			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $anysearch_cron_event );
			}
		}
	}

	/**
	 * Essentially a copy of WP's build_query but one that doesn't expect pre-urlencoded values.
	 *
	 * @param array $args An array of key => value pairs
	 *
	 * @return string A string ready for use as a URL query string.
	 */
	public static function build_query( $args ) {
		return _http_build_query( $args, '', '&' );
	}

}
