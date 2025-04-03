<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

add_action('admin_menu', 'okzea_chatbot_menu');
function okzea_chatbot_menu()
{
    $icon_url = plugins_url('images/okzea.ico', __FILE__);
    add_menu_page(
        'Chatbot okzea',
        'Chatbot okzea',
        'manage_options',
        'okzea-chatbot',
        'okzea_chatbot_display_submissions',
        $icon_url
    );

    add_submenu_page(
        'okzea-chatbot',
        'Statistiques du Chatbot',
        'Statistiques',
        'manage_options',
        'okzea-chatbot',
        'okzea_chatbot_display_submissions'
    );

    add_submenu_page(
        'okzea-chatbot',
        'Paramètres du Chatbot',
        'Paramètres',
        'manage_options',
        'okzea-chatbot-settings',
        'okzea_chatbot_options_page'
    );
}

function okzea_chatbot_options_page()
{
    // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    // add error/update messages
    settings_errors('okzea_chatbot_messages');

    // Enqueue admin styles
    wp_enqueue_style('okzea-chatbot-admin-style', plugin_dir_url(__FILE__) . 'css/okzea-chatbot-admin.css?v=1.0.1');
?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            // output security fields for the registered setting "okzea_chatbot"
            settings_fields('okzea_chatbot');
            // output setting sections and their fields
            do_settings_sections('okzea_chatbot');
            // output save settings button
            submit_button('Save Settings');
            ?>
        </form>
    </div>
<?php
}
