<?php

namespace CodeSoup\WebArchive\Core;

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


	private $types;


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

		$hooker->add_action( 'init', $this );
		$hooker->add_action( 'admin_menu', $this );

		$hooker->add_action( 'transition_post_status', $this, 'transition_post_status', 10, 3 );
		$hooker->add_action( 'webarchive_create_snapshot', $this, 'webarchive_create_snapshot', 10, 3 );

		$hooker->add_action( 'rest_api_init', $this);
	}

	public function rest_api_init() {

        $rest = new SnapshotController();
        $rest->register_routes();
    }


	/**
     * Log status transition
     */
    public function transition_post_status($new_status, $old_status, $post)
    {
    	if ( empty($post) )
    		return;

        $snap = new Snapshot();
        $snap->create_log_entry( $new_status, $old_status, $post );
    }



    /**
     * Create
     * @param  [type] $post [description]
     * @param  [type] $time [description]
     * @return [type]       [description]
     */
    public function webarchive_create_snapshot( $snapshot_id ) {

    	if ( empty($snapshot_id) )
    		return;

        $snap = new Snapshot();
        $snap->do_snapshot( $snapshot_id );
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
	        'Web Archive',
	        'Web Archive',
	        'manage_snapshots',
	        'web-archive',
	        array( &$this, 'render_dashboard'),
	        'dashicons-welcome-view-site'
	    );

	    add_submenu_page(
	        'web-archive',
	        'Dashboard',
	        'Dashboard',
	        'manage_webarchive',
	        'web-archive',
	        array( &$this, 'render_dashboard'),
	    );
	}

	function render_dashboard() {
    	printf('<div id="web-archive-app"></div>');
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
            'manage_webarchive',
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