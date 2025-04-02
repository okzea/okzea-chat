<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function okzea_generate_oauth1_headers($url, $method, $data, $consumer_key, $consumer_secret)
{
    $oauth = array(
        'oauth_consumer_key' => $consumer_key,
        'oauth_nonce' => md5(mt_rand()),
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_timestamp' => time(),
        'oauth_version' => '1.0'
    );

    $base_info = okzea_build_base_string($url, $method, $oauth);
    $composite_key = rawurlencode($consumer_secret) . '&';
    $oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
    $oauth['oauth_signature'] = $oauth_signature;

    $header = array(
        'Authorization' => okzea_build_authorization_header($oauth),
        'Content-Type' => 'application/json'
    );
    return $header;
}

function okzea_build_base_string($baseURI, $method, $params)
{
    $r = array();
    ksort($params);
    foreach ($params as $key => $value) {
        $r[] = "$key=" . rawurlencode($value);
    }
    return $method . "&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
}

function okzea_build_authorization_header($oauth)
{
    $r = 'OAuth ';
    $values = array();
    foreach ($oauth as $key => $value)
        $values[] = "$key=\"" . rawurlencode($value) . "\"";
    $r .= implode(', ', $values);
    return $r;
}

function okzea_chatbot_send_to_oggodata($submission_id)
{
    global $wpdb;

    // Retrieve the submission data using the submission_id
    $submission = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}okzea_chatbot_submissions WHERE id = %d",
        $submission_id
    ));

    if ($submission) {
        $data = unserialize($submission->submission_data);

        // Get consumer key and secret from DB
        $consumer_key = get_option('okzea_chatbot_oggodata_consumer_key');
        $consumer_secret = get_option('okzea_chatbot_oggodata_consumer_secret');

        // OGGODATA API https://okzea.oggo-data.net/sites/all/modules/gcc/gcc_api/OGGO%20Data%20-%20API%20Fournisseurs.pdf
        // https://docs.google.com/spreadsheets/d/1B-UQzsed8OE_2ddN96GbY_YZXseiSXtgAAOe3AGXlf0/edit?gid=0#gid=0
        $url = 'https://oggo-data.net/api/project/health';
        $method = 'POST';

        // Generating OAuth 1.0 headers
        $headers = okzea_generate_oauth1_headers($url, $method, json_encode($data), $consumer_key, $consumer_secret);

        // Sending data to the webhook
        $response = wp_remote_post($url, array(
            'method'    => $method,
            'body'      => json_encode($data),
            'headers'   => $headers,
        ));

        $code = $response['response']['code'];

        if (is_wp_error($response)) {
            error_log('Error: ' . $response->get_error_message(), 3, __DIR__ . '/error.log');
            update_submission_status($submission_id, 'failed');
        } elseif ($code == 200) {
            error_log('Response: OGGODATA API sent successfully', 3, __DIR__ . '/error.log');
            update_submission_status($submission_id, 'sent');
        } else {
            $error_message = $response['body'];
            error_log('OGGODATA Error[error_message]: ' . $error_message, 3, __DIR__ . '/error.log');
            error_log('OGGODATA Error[response]: ' . json_encode($response, JSON_PRETTY_PRINT), 3, __DIR__ . '/error.log');
            update_submission_status($submission_id, 'failed');
        }
    } else {
        error_log('Submission not found "' . $submission_id . '"' . "\n", 3, __DIR__ . '/error.log');
    }
}

function update_submission_status($submission_id, $status)
{
    global $wpdb;
    $wpdb->update(
        $wpdb->prefix . 'okzea_chatbot_submissions',
        array('oggodata_status' => $status),
        array('id' => $submission_id)
    );
}

// Add action to send data to webhook after form submission
add_action('okzea_chatbot_after_save_submission', 'okzea_chatbot_send_to_oggodata');
