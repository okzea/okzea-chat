<?php

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

// Register REST API endpoint
add_action('rest_api_init', function () {
  register_rest_route('okzea-chatbot/v1', '/submit', array(
    'methods' => 'POST',
    'callback' => 'okzea_chatbot_save_submission',
    'permission_callback' => '__return_true',
  ));
});

function okzea_chatbot_save_submission(WP_REST_Request $request)
{
  global $wpdb;
  $data = $request->get_json_params();

  if (empty($data)) {
    return new WP_Error('no_data', 'No data provided', array('status' => 400));
  }

  $submission_data = array(
    'submission_data' => serialize($data),
    'submitted_at' => current_time('mysql', 1),
    'oggodata_status' => 'pending', // Initial status
  );

  $wpdb->insert($wpdb->prefix . 'okzea_chatbot_submissions', $submission_data);

  // get the last inserted ID and send it to the action hook
  $submission_id = $wpdb->insert_id;

  // Create action hook for other integrations and send data to them
  do_action('okzea_chatbot_after_save_submission', $submission_id);

  return array('submission_id' => $submission_id);
}
