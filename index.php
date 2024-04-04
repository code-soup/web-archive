<?php

defined('WPINC') || die;

/**
 * Plugin Name: Web Archive
 * Plugin URI: https://github.com/code-soup/content-changelog
 * Description: Track content changes on WordPress website for complience purposes
 * Version: 0.0.1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Code Soup
 * Author URI: https://www.codesoup.co
 * License: GPL-3.0+
 * Text Domain: web-archive
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