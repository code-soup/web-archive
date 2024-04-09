<?php

// Exit if accessed directly
defined( 'WPINC' ) || die; ?>

<div class="wrap">
    <h2>Web Archive Settings</h2>
    <form method="post" action="options.php">
        <?php
        settings_fields('wa_general_page');
        do_settings_sections('wa_settings');
        submit_button();
        ?>
    </form>
</div>