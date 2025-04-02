<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

add_action('admin_init', 'okzea_chatbot_settings_init');
function okzea_chatbot_settings_init()
{
    // Register a new setting for "okzea_chatbot" page
    register_setting('okzea_chatbot', 'okzea_chatbot_form_id');
    register_setting('okzea_chatbot', 'okzea_chatbot_webhook_url');
    register_setting('okzea_chatbot', 'okzea_chatbot_facebook_pixel_id');

    // Add a new section in the "okzea_chatbot" page
    add_settings_section(
        'okzea_chatbot_section',
        __('Webhook Integration Settings', 'okzea-chatbot'),
        'okzea_chatbot_section_callback',
        'okzea_chatbot'
    );

    add_settings_field(
        'okzea_chatbot_facebook_pixel_id',
        __('Facebook Pixel ID', 'okzea-chatbot'),
        'okzea_chatbot_facebook_pixel_id_render',
        'okzea_chatbot',
        'okzea_chatbot_section'
    );
}

function okzea_chatbot_section_callback()
{
    echo __('Enter your consumer key and secret here.', 'okzea-chatbot');
}

function okzea_chatbot_form_id_render()
{
    $options = get_option('okzea_chatbot_form_id');
    echo '<input type="text" name="okzea_chatbot_form_id" value="' . esc_attr($options) . '">';
}

function okzea_chatbot_webhook_url_render()
{
    $options = get_option('okzea_chatbot_webhook_url');
    echo '<input type="text" name="okzea_chatbot_webhook_url" value="' . esc_attr($options) . '">';
}

function okzea_chatbot_learnybox_api_key_render()
{
    $options = get_option('okzea_chatbot_learnybox_api_key');
    echo '<input type="text" name="okzea_chatbot_learnybox_api_key" value="' . esc_attr($options) . '">';
}

function okzea_chatbot_facebook_pixel_id_render()
{
    $options = get_option('okzea_chatbot_facebook_pixel_id');
    echo '<input type="text" name="okzea_chatbot_facebook_pixel_id" value="' . esc_attr($options) . '">';
}
