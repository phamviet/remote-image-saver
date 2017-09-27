<?php
// exit
defined( 'ABSPATH' ) OR exit;

final class Remote_Image_Saver {

	/**
	 * plugin options
	 *
	 * @since  1.0.0
	 * @var    array
	 */
	public static $options;

	/**
	 * Remote_Image_Saver constructor.
	 */
	public function __construct() {
		// set default vars
		self::_set_default_vars();

		add_filter(
			'content_save_pre',
			[
				__CLASS__,
				'on_content_save_pre',
			]
			,
			99,
			2
		);
	}

	/**
	 * constructor wrapper
	 *
	 * @since   1.0.0
	 */
	public static function instance() {
		new self();
	}

	/**
	 * content hook
	 *
	 * @since   1.0.0
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public static function on_content_save_pre( $content ) {
		if ( isset( $_GET['post'] ) )
			$post_id = $post_ID = (int) $_GET['post'];
		elseif ( isset( $_POST['post_ID'] ) )
			$post_id = $post_ID = (int) $_POST['post_ID'];
		else
			$post_id = $post_ID = 0;

		if ( ! $post_id ) {
			return $content;
		}

		$content = wp_unslash( $content );
		$exclude_hosts = self::$options['exclude_hosts'];

		if ( preg_match_all( '/<img [^>]+>/', $content, $matches ) && current_user_can( 'upload_files' ) ) {
			foreach ( (array) $matches[0] as $image ) {
				// This is inserted from our JS so HTML attributes should always be in double quotes.
				if ( ! preg_match( '/src="([^"]+)"/', $image, $url_matches ) ) {
					continue;
				}

				$image_src = $url_matches[1];

				// filter from excluded hosts
				$image_host = parse_url( $image_src, PHP_URL_HOST );
				if ( in_array( $image_host, $exclude_hosts ) ) {
					continue;
				}

				// Don't try to sideload a file without a file extension, leads to WP upload error.
				if ( ! preg_match( '/[^\?]+\.(?:jpe?g|jpe|gif|png)(?:\?|$)/i', $image_src ) ) {
					continue;
				}

				// Sideload image, which gives us a new image src.
				$new_src = media_sideload_image( $image_src, $post_id, null, 'src' );

				if ( ! is_wp_error( $new_src ) ) {
					// Replace the POSTED content <img> with correct uploaded ones.
					// Need to do it in two steps so we don't replace links to the original image if any.
					$new_image = str_replace( $image_src, $new_src, $image );
					$content = str_replace( $image, $new_image, $content );
				}
			}
		}

		// Expected slashed
		return wp_slash( $content );
	}

	/**
	 * deactivation hook
	 *
	 * @since   1.0.0
	 */
	public static function on_deactivation() {

	}

	/**
	 * activation hook
	 *
	 * @since   1.0.0
	 */
	public static function on_activation() {

	}

	/**
	 * uninstall per multisite blog
	 *
	 * @since   1.0.0
	 * @change  1.0.0
	 */
	public static function on_uninstall() {
		global $wpdb;
		// multisite and network
		if ( is_multisite() && ! empty( $_GET['networkwide'] ) ) {
			// legacy blog
			$old = $wpdb->blogid;
			// blog id
			$ids = self::_get_blog_ids();
			// uninstall per blog
			foreach ( $ids as $id ) {
				switch_to_blog( $id );
				self::_uninstall_backend();
			}
			// restore
			switch_to_blog( $old );
		} else {
			self::_uninstall_backend();
		}
	}

	/**
	 * set default vars
	 *
	 * @since   1.0.0
	 */
	private static function _set_default_vars() {
		// get options
		self::$options = self::_get_options();
	}

	/**
	 * get options
	 *
	 * @since   1.0.0
	 *
	 * @return  array  options array
	 */
	private static function _get_options() {
		return wp_parse_args(
			get_option( RMS_SLUG ),
			[
				'exclude_hosts' => [
					parse_url(get_site_url(), PHP_URL_HOST)
				],
			]
		);
	}

	/**
	 * uninstall
	 *
	 * @since   1.0.0
	 */
	private static function _uninstall_backend() {
		// delete options
		delete_option( RMS_SLUG );
	}

	/**
	 * get blog ids
	 *
	 * @since   1.0.0
	 * @change  1.0.0
	 *
	 * @return  array  blog ids array
	 */
	private static function _get_blog_ids() {
		global $wpdb;

		return $wpdb->get_col( "SELECT blog_id FROM `$wpdb->blogs`" );
	}

}