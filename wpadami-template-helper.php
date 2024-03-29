<?php

/**
 * WPAdamı Template Helper
 *
 * @package   WPAdami_Template_Helper
 * @author    Serkan Algur <info@wpadami.com>
 * @license   GPL-3.0+
 * @link      https://github.com/serkanalgur/wpadami-template-helper
 * @copyright 2019 Serkan Algur, WPAdamı
 *
 * @wordpress-plugin
 * Plugin Name:       WPAdamı Template Helper
 * Plugin URI:        https://github.com/serkanalgur/wpadami-template-helper
 * Description:       Template helper by Serkan Algur
 * Version:           1.1.1
 * Author:            Serkan Algur
 * Author URI:        https://github.com/serkanalgur
 * Text Domain:       wpadami-template-helper
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/serkanalgur/wpadami-template-helper
 * GitHub Branch:     master
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Begin

if ( ! class_exists( 'WPAdami_Template_Helper', true ) ) {

	class WPAdami_Template_Helper {

		public function __construct() {

			add_filter( 'wp_handle_upload_prefilter', array( __CLASS__, 'correct_utf_chars_filename' ) );
			add_action( 'dashboard_glance_items', array( __CLASS__, 'cpt_to_dashboard_info' ) );
			add_action( 'wp_dashboard_setup', array( __CLASS__, 'disable_default_dashboard_widgets' ), 999 );
			add_action( 'init', array( __CLASS__, 'multiple_init_for_add_action_init' ) );
			add_filter( 'tiny_mce_plugins', array( __CLASS__, 'disable_emojis_tinymce_wpa' ) );
			add_filter( 'wp_resource_hints', array( __CLASS__, 'disable_emojis_remove_dns_prefetch_wpa' ), 10, 2 );
			add_filter( 'upload_mimes', array( __CLASS__, 'add_more_mime_types_to_wordpress' ) );
			add_filter( 'style_loader_src', array( __CLASS__, 'remove_version_info_from_styles_and_scripts' ), 9999 );
			add_filter( 'script_loader_src', array( __CLASS__, 'remove_version_info_from_styles_and_scripts' ), 9999 );
		}

		/**
		 * correct wrong chars on filenames
		 *
		 * @since 1.0.0
		*/

		public static function correct_utf_chars_filename( $file = array() ) {

			include plugin_dir_path( __FILE__ ) . '/helpers/foreign_chars.php';

			$file['name'] = strtolower( preg_replace( array_keys( $foreign_characters ), array_values( $foreign_characters ), $file['name'] ) );

			return $file;
		}

		/**
		 * show post types info in to dashboard widget
		 *
		 * @since 1.0.0
		*/

		public static function cpt_to_dashboard_info() {

			$post_types = get_post_types( array( '_builtin' => false ), 'objects' );
			if ( $post_types ) :
				foreach ( $post_types as $post_type ) {
					$num_posts       = wp_count_posts( $post_type->name );
					$num             = number_format_i18n( $num_posts->publish );
					$cptsingularname = $post_type->labels->singular_name;
					$cptname         = $post_type->labels->name;
					$text            = _n( $cptsingularname, $cptname, $num_posts->publish );
					if ( current_user_can( 'edit_posts' ) ) {
						$tt = '<a href="edit.php?post_type=' . $post_type->name . '">' . $num . ' ' . $text . '</a>';
					}
					echo '<li class="post-count">' . $tt . '</li>';

					if ( $num_posts->pending > 0 ) {
						$num  = number_format_i18n( $num_posts->pending );
						$text = _n( $cptsingularname . ' pending', $cptname . ' pending', $num_posts->pending );
						if ( current_user_can( 'edit_posts' ) ) {
							$tt = '<a href="edit.php?post_status=pending&post_type=' . $post_type->name . '">' . $num . ' ' . $text . '</a>';
						}
						echo '<li class="first b b-' . $post_type->name . 's">' . $tt . '</li>';
					}
				}
			endif;

		}

		/**
		 * remove unused or not needed dashboard widgets
		 *
		 * @since 1.0.0
		*/

		public static function disable_default_dashboard_widgets() {
			global $wp_meta_boxes;
			// wp..
			unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity'] );
			unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments'] );
			unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links'] );
			unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins'] );
			unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_primary'] );
			unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary'] );
			unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press'] );
			unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_recent_drafts'] );
			// bbpress
			unset( $wp_meta_boxes['dashboard']['normal']['core']['bbp-dashboard-right-now'] );
			// yoast seo
			unset( $wp_meta_boxes['dashboard']['normal']['core']['yoast_db_widget'] );
			// gravity forms
			unset( $wp_meta_boxes['dashboard']['normal']['core']['rg_forms_dashboard'] );
		}

		/**
		 * remove WordPress Emoji from theme
		 *
		 * @since 1.0.0
		*/

		public static function disable_emojis_in_theme_wpa() {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		}

		public static function disable_emojis_tinymce_wpa( $plugins ) {
			if ( is_array( $plugins ) ) {
				return array_diff( $plugins, array( 'wpemoji' ) );
			}

			return array();
		}

		public static function disable_emojis_remove_dns_prefetch_wpa( $urls, $relation_type ) {

			if ( 'dns-prefetch' === $relation_type ) {

				// Strip out any URLs referencing the WordPress.org emoji location
				$emoji_svg_url_bit = 'https://s.w.org/images/core/emoji/';
				foreach ( $urls as $key => $url ) {
					if ( strpos( $url, $emoji_svg_url_bit ) !== false ) {
						unset( $urls[ $key ] );
					}
				}
			}

			return $urls;
		}

		/**
		 * Add more mimetypes
		 *
		 * @since 1.1.0
		*/

		public static function add_more_mime_types_to_wordpress( $mimes = array() ) {
			$mimes['svg']   = 'image/svg+xml';
			$mimes['webp']  = 'image/webp';
			$mimes['ttf']   = 'application/x-font-ttf';
			$mimes['otf']   = 'application/x-font-opentype';
			$mimes['woff']  = 'application/font-woff';
			$mimes['woff2'] = 'application/font-woff2';
			$mimes['eot']   = 'application/vnd.ms-fontobject';
			$mimes['sfnt']  = 'application/font-sfnt';
			return $mimes;
		}

		/**
		 * remove generator and other tags
		 *
		 * @since 1.0.0
		*/

		public static function generator_other_stuff_remove_wpa() {
			add_filter( 'show_admin_bar', '__return_false' );
			remove_action( 'wp_head', 'wp_generator' );
			add_filter( 'xmlrpc_enabled', '__return_false' );
			remove_action( 'wp_head', 'rsd_link' );
			remove_action( 'wp_head', 'wlwmanifest_link' );
			remove_action( 'wp_head', 'wp_shortlink_wp_head' );
			remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
			remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
			remove_action( 'template_redirect', 'rest_output_link_header', 11, 0 );
		}

		public static function multiple_init_for_add_action_init() {
			self::disable_emojis_in_theme_wpa();
			self::generator_other_stuff_remove_wpa();
		}

		/**
		 * remove version tags
		 *
		 * @since 1.1.1
		*/


		public static function remove_version_info_from_styles_and_scripts( $src ) {
			if ( strpos( $src, 'ver=' ) ) {
				$src = remove_query_arg( 'ver', $src );
			}
			return $src;
		}

	}
}

$WPAdami_Template_Helper = new WPAdami_Template_Helper();
