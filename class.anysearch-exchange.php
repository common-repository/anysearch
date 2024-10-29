<?php

class Anysearch_Exchange extends Anysearch {

	protected static $initiated = false;
	protected static $sync_options;

	public static function init() {
		self::$sync_options = get_option( 'anysearch_sync_settings' );
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	/**
	 * Initializes WordPress hooks
	 */
	private static function init_hooks() {
		if ( self::$sync_options['partial_sync'] ) {
			add_action( 'woocommerce_update_product', array( __CLASS__, 'sync_on_product_update' ), 10, 1 );
			add_action( 'wp_trash_post', array( __CLASS__, 'sync_on_product_delete' ), 10, 1 );
			add_action( 'untrash_post', array( __CLASS__, 'sync_on_product_update' ), 10, 1 );
			add_action( 'woocommerce_thankyou', array( __CLASS__, 'sync_on_order_made' ), 10, 1 );
		}

		if ( self::$sync_options['priod_full_sync'] != 'never' ) {
			add_filter( 'cron_schedules', array( __CLASS__, 'anysearch_full_sync' ) );
			self::anysearch_full_sync_cron();
			add_action( 'anysearch_full_sync_cron_worker_start', array( __CLASS__, 'anysearch_full_sync_job' ) );
		}
	}

	public static function anysearch_full_sync_cron() {
		$time_offset = current_time( 'timestamp' ) - time();
		$cron_time   = strtotime( 'yesterday midnight +2 hours' ) - $time_offset;
		if ( ! wp_next_scheduled( 'anysearch_full_sync_cron_worker_start' ) ) {
			wp_schedule_event( $cron_time, 'anysearch_full_sync_cron_worker', 'anysearch_full_sync_cron_worker_start' );
		}
	}

	public static function anysearch_full_sync( $schedules ) {
		$interval = 0;
		switch ( self::$sync_options['priod_full_sync'] ) {
			case 'daily':
				$interval = 1 * 24 * 60 * 60;
				break;
			case '3daily':
				$interval = 3 * 24 * 60 * 60;
				break;
			case 'weekly':
				$interval = 7 * 24 * 60 * 60;
				break;
		}
		$schedules['anysearch_full_sync_cron_worker'] = array(
			'interval' => $interval,
			'display'  => 'Anysearch full sync worker'
		);

		return $schedules;
	}

	public static function anysearch_full_sync_job() {
		Anysearch::$taskmanager->push_to_queue( array( 'is_full_sync' => true ) );
	}


	public static function sync_on_product_update( $product_id ) {
		$updating_product_id = 'update_product_' . $product_id;
		if ( false === ( $updating_product = get_transient( $updating_product_id ) ) ) {
			self::$taskmanager->push_to_queue( array( 'product_id' => $product_id ) );
			set_transient( $updating_product_id, $product_id, 2 ); // change 2 seconds if not enough
		}
	}

	public static function sync_on_product_delete( $product_id ) {
		$updating_product_id = 'delete_product_' . $product_id;
		if ( false === ( $updating_product = get_transient( $updating_product_id ) ) ) {
			self::$taskmanager->push_to_queue( array( 'product_id' => $product_id, 'do_delete' => true ) );
			set_transient( $updating_product_id, $product_id, 2 ); // change 2 seconds if not enough
		}
	}

	public static function sync_on_order_made( $order_id ) {
		$order = wc_get_order( $order_id );
		$items = $order->get_items();
		foreach ( $items as $item ) {
			$product = $item->get_product();
			if ( $product->is_in_stock() == false ) {
				self::$taskmanager->push_to_queue( array( 'product_id' => $product->get_id() ) );
			}
		}
	}

}
