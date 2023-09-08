<?php

namespace CodeSoup\ContentChangeLog;

// If this file is called directly, abort.
// Web Archiver
defined('WPINC') || die;

// Autoload all classes via composer.
require "vendor/autoload.php";

/**
* Make main plugin class available via global function call.
*
* @since    1.0.0
*/
function plugin_instance() {

    return \CodeSoup\ContentChangeLog\Init::get_instance();
}

// Init plugin and make instance globally available
$plugin = plugin_instance();
$plugin->init();


add_action( 'init', function() {

    // Homepage
    if ( ! empty($_GET['wtf-loop']) )
    {
        $snap = new \CodeSoup\ContentChangeLog\Core\Snapshot( 10357 );
        $snap->create_snapshot();
    }
});