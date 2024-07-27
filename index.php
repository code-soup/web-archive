<?php

defined('WPINC') || die;

/**
 * Plugin Name: Web Archive
 * Plugin URI: https://github.com/code-soup/web-archive
 * Description: Automatically creates HTML copies of your posts and pages when they're published, allowing you to track every version.
 * Version: 1.0.1
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Author: Code Soup
 * Author URI: https://www.codesoup.co
 * License: GPL-2.0-or-later
 * Text Domain: webarchive
 */

register_activation_hook( __FILE__, function() {

    // On activate do this
    \CodeSoup\WebArchive\Activator::activate();
});

register_deactivation_hook( __FILE__, function () {
    
    // On deactivate do that
    \CodeSoup\WebArchive\Deactivator::deactivate();
});

include "run.php";