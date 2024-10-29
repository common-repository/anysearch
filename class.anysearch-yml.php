<?php

use Bukashk0zzz\YmlGenerator\Model\Offer\OfferSimple;
use Bukashk0zzz\YmlGenerator\Model\Offer\OfferParam;
use Bukashk0zzz\YmlGenerator\Model\Category;
use Bukashk0zzz\YmlGenerator\Model\Currency;
use Bukashk0zzz\YmlGenerator\Model\Delivery;
use Bukashk0zzz\YmlGenerator\Model\ShopInfo;
use Bukashk0zzz\YmlGenerator\Settings;
use Bukashk0zzz\YmlGenerator\Generator;

if ( ! class_exists( 'WC_Background_Process', false ) ) {
	include_once ANYSEARCH__PLUGIN_DIR . 'class.background-task-init.php';
}

class Anysearch_Yml extends WP_Background_Process {

	private $is_full_sync;
	private $is_manual_sync;

	public function __construct() {
		$this->prefix = 'wp_' . get_current_blog_id();
		$this->action = 'Anysearch_Yml';
		add_action( 'shutdown', array( $this, 'dispatch_queue' ), 100 );

		parent::__construct();
	}

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param array $params [product_id, do_delete, is_full_sync, is_manual_sync]
	 *
	 * @return mixed
	 */
	protected function task( $params ) {

		$product_id           = $params['product_id'] ?: false;
		$do_delete            = $params['do_delete'] ?: false;
		$this->is_full_sync   = $params['is_full_sync'] ?: false;
		$this->is_manual_sync = $params['is_manual_sync'] ?: false;

		$file = self::doExport( $product_id, $do_delete );
		self::doFileUpload( $file, $this->is_full_sync );
		unlink( $file );

		return false;
	}

	public function doExport( $product_id, $remove = false ) {

		update_option( 'anysearch_sync_status', 1 );

		$tempfile = tempnam( __DIR__ . '/temp/', 'YMLExport_' . $product_id . '_' );
		$file     = $tempfile . '.yml';
		$settings = ( new Settings() )
			->setOutputFile( $file )
			->setEncoding( 'UTF-8' );

		// Creating ShopInfo object (https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#shop)
		$shopInfo = ( new ShopInfo() )
			->setName( get_bloginfo( 'name' ) )
			->setCompany( get_bloginfo( 'description' ) )
			->setUrl( get_bloginfo( 'wpurl' ) );

		// Creating currencies array (https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#currencies)
		$currencies   = [];
		$currencies[] = ( new Currency() )
			->setId( get_woocommerce_currency() )
			->setRate( 1 );

		// Creating categories array (https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#categories)
		$categories = [];

		$taxonomy     = 'product_cat';
		$orderby      = 'name';
		$show_count   = 0;      // 1 for yes, 0 for no
		$pad_counts   = 0;      // 1 for yes, 0 for no
		$hierarchical = 1;      // 1 for yes, 0 for no
		$title        = '';
		$empty        = 0;

		$cat_args       = array(
			'taxonomy'     => $taxonomy,
			'orderby'      => $orderby,
			'show_count'   => $show_count,
			'pad_counts'   => $pad_counts,
			'hierarchical' => $hierarchical,
			'title_li'     => $title,
			'hide_empty'   => $empty
		);
		$all_categories = get_categories( $cat_args );
		foreach ( $all_categories as $cat ) {
			if ( $cat->category_parent == 0 ) {
				$categories[] = ( new Category() )
					->setId( $cat->cat_ID )
					->setName( $cat->name );
				$sub_cat_args = array(
					'taxonomy'     => $taxonomy,
					'child_of'     => $cat->cat_ID,
					'orderby'      => $orderby,
					'show_count'   => $show_count,
					'pad_counts'   => $pad_counts,
					'hierarchical' => $hierarchical,
					'title_li'     => $title,
					'hide_empty'   => $empty
				);
				$sub_cats     = get_categories( $sub_cat_args );
				if ( $sub_cats ) {
					foreach ( $sub_cats as $sub_category ) {
						$categories[] = ( new Category() )
							->setId( $sub_category->cat_ID )
							->setParentId( $cat->cat_ID )
							->setName( $sub_category->name );
					}
				}
			}
		}

		// Creating offers array (https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#offers)
		$offers         = [];
		$total_products = (int) wc_get_products( array( 'paginate' => true ) )->total;
		$pages          = ceil( $total_products / 25 );
		$page           = 1;
		$products       = false;

		do {
			if ( $product_id && $remove ) {
				$to_be_removed       = wc_get_product( $product_id );
				$offer_to_be_removed = ( new OfferSimple() )
					->setId( $to_be_removed->get_id() )
					->setAvailable( false )
					->setCategoryId( $to_be_removed->get_category_ids()[0] )
					->setName( $to_be_removed->get_name() );
				$offers[]            = $offer_to_be_removed;
			} else if ( $product_id ) {
				$products = wc_get_products( array(
					'limit'   => 25,
					'page'    => $page,
					'include' => array( $product_id )
				) );
			} else {
				$products = wc_get_products( array( 'limit' => 25, 'page' => $page ) );
			}
			if ( $products ) {
				foreach ( $products as $product ) {
					$new_offer = ( new OfferSimple() )
						->setId( $product->get_id() )
						->setUrl( $product->get_permalink() )
						->setAvailable( ( get_option( 'anysearch_sync_settings' )['out_of_stock_remove_sync'] ) ? $product->is_in_stock() : true )
						->setPrice( $product->get_sale_price() )
						->setCurrencyId( get_woocommerce_currency() )
						->setCategoryId( $product->get_category_ids()[0] )
						->setName( $product->get_name() );

					if ( $product->get_sale_price() ) {
						$new_offer->setPrice( $product->get_sale_price() );
						$new_offer->setOldPrice( $product->get_regular_price() );
					} else {
						$new_offer->setPrice( $product->get_price() );
					}

					if ( $product->has_weight() ) {
						$new_offer->setWeight( $product->get_weight() );
					}

					if ( ! empty( wp_get_attachment_image_url( $product->get_image_id() ) ) ) {
						$new_offer->setPictures( array( wp_get_attachment_image_url( $product->get_image_id() ) ) );
					} else {
						$new_offer->setPictures( array( wc_placeholder_img_src() ) );
					}

					$product_attributes = $product->get_attributes();
					foreach ( $product_attributes as $attribute ) {
						if ( $attribute->get_terms() ) {
							$attribute_terms = $attribute->get_terms();

							$param = new OfferParam();
							$param->setName( $attribute->get_name() );
							$new_offer->addParam( $param );

							foreach ( $attribute_terms as $term ) {
								$param = new OfferParam();
//								$param->setName( $attribute->get_name() );
								$param->setName( $term->name );
//								$param->setValue( $term->name );
								$new_offer->addParam( $param );
							}
						} else {
							foreach ( $attribute->get_options() as $option ) {
								$param = new OfferParam();
//								$param->setName( $attribute->get_name() );
								$param->setName( $option );
//								$param->setValue( $option );
								$new_offer->addParam( $param );
							}
						}
					}
					$offers[] = $new_offer;

				}
			}
			$page ++;
		} while ( $page <= $pages );

		( new Generator( $settings ) )->generate(
			$shopInfo,
			$currencies,
			$categories,
			$offers
		);

		unlink( $tempfile );

		return $file;
	}


	protected function doFileUpload( $file, $is_full_sync ) {
		update_option( 'anysearch_sync_status', 2 );
		$local_file  = $file; //path to a local file on your server
		$post_fields = array(
			'accessToken' => Anysearch::get_api_key(),
			'isFullSync'  => $is_full_sync
		);
		$boundary    = wp_generate_password( 24 );
		$headers     = array(
			'content-type' => 'multipart/form-data; boundary=' . $boundary,
		);
		$payload     = '';
		// First, add the standard POST fields:
		foreach ( $post_fields as $name => $value ) {
			$payload .= '--' . $boundary;
			$payload .= "\r\n";
			$payload .= 'Content-Disposition: form-data; name="' . $name .
			            '"' . "\r\n\r\n";
			$payload .= $value;
			$payload .= "\r\n";
		}
		// Upload the file
		if ( $local_file ) {
			$payload .= '--' . $boundary;
			$payload .= "\r\n";
			$payload .= 'Content-Disposition: form-data; name="' . 'catalog' .
			            '"; filename="' . basename( $local_file ) . '"' . "\r\n";
			$payload .= "\r\n";
			$payload .= file_get_contents( $local_file );
			$payload .= "\r\n";
		}
		$payload .= '--' . $boundary . '--';
		wp_remote_post( 'http://' . ANYSEARCH_API_HOST . ':' . ANYSEARCH_API_PORT . '/api/upload',
			array(
				'headers' => $headers,
				'body'    => $payload,
			)
		);
		update_option( 'anysearch_sync_status', 3 );
	}


	/**
	 * Save and run queue.
	 */
	public function dispatch_queue() {
		if ( ! empty( $this->data ) ) {
			$this->save()->dispatch();
		}
	}

	/**
	 * When queue is completed.
	 */
	protected function complete() {
		$this->is_full_sync ? update_option( 'anysearch_last_sync', date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) ) : false;
		$this->is_manual_sync ? Anysearch_Admin::add_notice( 'sync-finished' ) : false;
		parent::complete();

	}
}
