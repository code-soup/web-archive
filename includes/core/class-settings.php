<?php

namespace CodeSoup\WebArchive\Core;

// Exit if accessed directly
defined( 'WPINC' ) || die;

class Settings_Page {

    use \CodeSoup\WebArchive\Traits\HelpersTrait;

    // Main plugin instance.
    protected static $instance = null;

    // Assets loader class.
    protected $assets;

    public function __construct() {

        // Main plugin instance.
        $instance     = \CodeSoup\WebArchive\plugin_instance();
        $hooker       = $instance->get_hooker();
        $this->assets = $instance->get_assets();

        $hooker->add_action( 'admin_menu', $this );
        $hooker->add_action( 'admin_init', $this );
    }

    public function admin_menu()
    {
        add_submenu_page(
            'web-archive',
            'Settings',
            'Settings',
            'manage_webarchive',
            'web-archive-settings',
            array( &$this, 'render_settings_page'),
        );
    }

    public function admin_init()
    {
        register_setting(
            'wa_general_page',
            'wa_general'
        );

        add_settings_section(
            'wa_general_page',
            'General Settings',
            null,
            'wa_settings'
        );

        add_settings_field(
            'wa-post-type',
            'Enable for Post Types',
            [$this, 'cb_checkboxes_post_type'],
            'wa_settings',
            'wa_general_page'
        );
    }

    public function render_settings_page()
    {
        include 'settings/settings-general.php';
    }

    public function cb_checkboxes_post_type()
    {
        $options = get_option('wa_general');
        $types   = get_post_types(['publicly_queryable' => true], 'objects');
        $skip    = ['attachment', 'snapshot'];

        if (!is_array($options)) {
            $options = [
                'post_type' => []
            ];
        }

        foreach ($types as $type)
        {
            // Skip post type
            if ( in_array($type->name, $skip) )
                continue;

            
            printf(
                '<label for="%1$s"><input type="checkbox" id="%1$s" name="wa_general[post_type][]" value="%1$s"%2$s> %3$s</label><br>',
                esc_attr($type->name),
                in_array($type->name, $options['post_type']) ? ' checked' : '',
                esc_html($type->labels->name)
            );
        }

        printf(
            '<p class="description">%s</p>',
            'Automatically save new version every time post is published.'
        );
    }
}