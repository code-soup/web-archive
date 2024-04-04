<?php

namespace CodeSoup\WebArchive;

// Exit if accessed directly.
defined( 'WPINC' ) || die;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 */
class Activator {

    public static function activate() {
        
        \CodeSoup\WebArchive\Core\Init::capabilities_setup();
    }
}