<?php

namespace CodeSoup\ContentChangeLog;

// If this file is called directly, abort.
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