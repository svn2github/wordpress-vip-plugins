<div class="wrap">
    <h2>Apester Settings</h2>
    <form method="post" action="options.php">
        <?php
        // This prints out all hidden setting fields
        settings_fields('qmerce-settings-fields');
        do_settings_sections('qmerce-settings-admin');
        submit_button();
        ?>
    </form>
</div>
