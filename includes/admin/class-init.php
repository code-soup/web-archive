<?php

namespace CodeSoup\WebArchive\Admin;

// Exit if accessed directly
defined( 'WPINC' ) || die;


/**
 * @file
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 */
class Init {

	use \CodeSoup\WebArchive\Traits\HelpersTrait;

	// Main plugin instance.
	protected static $instance = null;

	
	// Assets loader class.
	protected $assets;


	protected $screen;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		// Main plugin instance.
		$instance     = \CodeSoup\WebArchive\plugin_instance();
		$hooker       = $instance->get_hooker();
		$this->assets = $instance->get_assets();
		$this->screen = empty($_GET['page']) ? null : sanitize_title( $_GET['page'] );

		// Admin hooks.
		$hooker->add_action( 'admin_enqueue_scripts', $this, 'enqueue_styles' );
		$hooker->add_action( 'admin_enqueue_scripts', $this, 'enqueue_scripts' );
	}

	/**
	 * Enqueue the stylesheets for wp-admin.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		if ( 'web-archive' !== $this->screen )
			return;

		wp_enqueue_style(
			$this->get_plugin_id('/wp/css'),
			$this->assets->get('styles/admin.css'),
			array(),
			$this->get_plugin_version(),
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		if ( 'web-archive' !== $this->screen )
			return;

		$script_id = $this->get_plugin_id('/wp/js');

		wp_enqueue_script(
			$script_id,
			wp_make_link_relative( $this->assets->get('scripts/admin.js') ),
			array(),
			$this->get_plugin_version(),
			false
		);

		wp_localize_script(
            $script_id,
            'WebArchive',
            array(
            	'root'     => get_rest_url(),
                'nonce'    => wp_create_nonce( 'wp_rest' ),
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'post_id'  => get_the_ID(),
            )
        );
	}
}
