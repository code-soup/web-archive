<?php

namespace CodeSoup\ContentChangeLog\Core;

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

	use \CodeSoup\ContentChangeLog\Traits\HelpersTrait;

	// Main plugin instance.
	protected static $instance = null;

	
	// Assets loader class.
	protected $assets;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		// Main plugin instance.
		$instance     = \CodeSoup\ContentChangeLog\plugin_instance();
		$hooker       = $instance->get_hooker();
		$this->assets = $instance->get_assets();

		$hooker->add_action( 'init', $this );
		$hooker->add_action( 'admin_menu', $this );
	}

	/**
	 * Register Post types & Taxonomies
	 */
	public function init()
	{
		/**
		 * Register Post Types
		 */
		$this->types = array(
			'snapshot',
		);

		foreach ( $this->types as $name )
		{
			$args = require_once "{$name}/post-type.php";
			register_post_type( $name, $args );	
		}
	}

	
	/**
	 * Admin menu
	 */
	public function admin_menu() {

		add_menu_page(
	        'Content ChangeLog',
	        'ChangeLog',
	        'manage_snapshots',
	        'content-changelog',
	        array( &$this, 'change_log_dashboard'),
	        'dashicons-welcome-view-site'
	    );

	    add_submenu_page(
	        'content-changelog',
	        'Dashboard',
	        'Dashboard',
	        'manage_content_changelog',
	        'dashboard',
	        array( &$this, 'change_log_dashboard'),
	    );

	    add_submenu_page(
	        'content-changelog',
	        'Settings',
	        'Settings',
	        'manage_content_changelog',
	        'settings',
	        array( &$this, 'change_log_dashboard'),
	    );
	}

	// Function to create the subpage content
	function change_log_dashboard() {
    	// Add your dashboard content here
    	echo '<h1>ChangeLog Dashboard</h1>';
	}


	/**
	 * - Generate user roles and capabilities
	 * - Add custom caps to admin
	 */
	public static function capabilities_setup() {

		// Role for Compliance Manager
        $admin_caps = array(
            'edit_snapshot',
            'read_snapshot',
            'delete_snapshot',
            'edit_snapshots',
            'edit_others_snapshots',
            'publish_snapshots',
            'read_private_snapshots',
            'delete_snapshots',
            'delete_private_snapshots',
            'delete_published_snapshots',
            'delete_others_snapshots',
            'edit_private_snapshots',
            'edit_published_snapshots',
            'manage_snapshots',
            'manage_content_changelog',
        );

        add_role('compliance_admin', 'Compliance Admin', $admin_caps);

        $manager_caps = array(
            'edit_snapshot',
            'read_snapshot',
            'manage_snapshots',
        );

        add_role('compliance_manager', 'Compliance Manager', $manager_caps);

        // Grant Compliance Manager capabilities to Administrator
        $admin_role = get_role('administrator');

        if ($admin_role)
        {
            foreach ($admin_caps as $cap) {
                $admin_role->add_cap($cap);
            }
        }
	}
}