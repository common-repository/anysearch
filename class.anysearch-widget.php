<?php

class Anysearch_Widget {

	private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}

	}

	public static function init_hooks() {

		self::$initiated = true;

		add_action( 'wp_enqueue_scripts', array( 'Anysearch_Widget', 'load_widget' ) );
		add_filter( 'get_search_form', array( 'Anysearch_Widget', 'my_search_form' ) );
		add_filter( 'get_product_search_form', array( 'Anysearch_Widget', 'my_search_form' ) );

	}

	public static function load_widget() {
		wp_register_script( 'app.js', plugin_dir_url( __FILE__ ) . 'dist/js/app.js', array( 'jquery' ), ANYSEARCH_VERSION, true );
		wp_enqueue_script( 'app.js' );
		wp_add_inline_script( 'app.js', '
        var AnysearchConfig = {url: "https://' . ANYSEARCH_API_HOST . '", customer_id: "' . Anysearch::get_frontend_key() . '"}
        function show() {
			Anysearch.init();
			Anysearch.show()
			document.getElementsByTagName(\'body\')[0].style.overflow = "hidden";
		}
		function hide() {
		    document.getElementsByTagName(\'body\')[0].style.overflow = "unset";
			Anysearch.hide()
		}
		
        ', 'before' );
	}

	public static function my_search_form( $form ) {
		$matches = array();
		if ( preg_match( '/class.*?=.*?"(.*?)\"/', $form, $matches ) ) {
			$match = $matches[0];
			$form  = str_replace(
				$match,
				$match . ' onClick="show()"',
				$form
			);

			return $form;
		} else {
			return $form;
		}
	}


}
