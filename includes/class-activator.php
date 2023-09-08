<?php

namespace CodeSoup\ContentChangeLog;

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
        
        \CodeSoup\ContentChangeLog\Core\Init::capabilities_setup();
    }
}