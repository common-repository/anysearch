<?php
/**
 * @package Anysearch
 */
/*
Plugin Name: Anysearch
Plugin URI: http://anysearch.tilda.ws/landing/
Description: AnySearch - виджет умного поиска товаров для интернет-магазинов. Плагин заменяет обычную строку поиска на новую с продвинутым функционалом
Version: 1.0.0
Author: Ukrosoft
Author URI: https://ukrosoftgroup.com/en
License: GPLv2 or later
Text Domain: anysearch
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2020 Ukrosoft
*/

// Make sure we don't expose any info if called directly
if ( ! function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'ANYSEARCH_VERSION', '1.0.0' );
define( 'ANYSEARCH__MINIMUM_WP_VERSION', '4.0' );
define( 'ANYSEARCH__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'ANYSEARCH_API_HOST' ) ) {
	define( 'ANYSEARCH_API_HOST', 'anysearch-backend.demo.ukrohost.com' );
}

if ( ! defined( 'ANYSEARCH_API_PORT' ) ) {
	define( 'ANYSEARCH_API_PORT', '80' );
}

register_activation_hook( __FILE__, array( 'Anysearch', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Anysearch', 'plugin_deactivation' ) );

if ( is_readable( ANYSEARCH__PLUGIN_DIR . '/vendor/autoload.php' ) ) {
	require ANYSEARCH__PLUGIN_DIR . '/vendor/autoload.php';
}

require_once( ANYSEARCH__PLUGIN_DIR . 'class.anysearch.php' );
require_once( ANYSEARCH__PLUGIN_DIR . 'class.anysearch-yml.php' );
require_once( ANYSEARCH__PLUGIN_DIR . 'class.anysearch-exchange.php' );

add_action( 'init', array( 'Anysearch', 'init' ) );


if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	require_once( ANYSEARCH__PLUGIN_DIR . 'class.anysearch-admin.php' );
	add_action( 'init', array( 'Anysearch_Admin', 'init' ) );
}

if ( ! is_admin() && Anysearch::get_anysearch_user_status() ) {
	require_once( ANYSEARCH__PLUGIN_DIR . 'class.anysearch-widget.php' );
	add_action( 'init', array( 'Anysearch_Widget', 'init' ) );
}
