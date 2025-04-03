<?php

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

/**
 * Send email notification to admin when a submission is saved
 */
function okzea_chatbot_send_email_notification($submission_id) {
  global $wpdb;
  
  // Get the submission data
  $submission = $wpdb->get_row(
    $wpdb->prepare(
      "SELECT * FROM {$wpdb->prefix}okzea_chatbot_submissions WHERE id = %d",
      $submission_id
    )
  );
  
  if (!$submission) {
    return;
  }
  
  // Unserialize the submission data
  $data = unserialize($submission->submission_data);
  
  // Get admin email
  $admin_email = get_option('admin_email');
  
  // Prepare email subject
  $subject = '[' . get_bloginfo('name') . '] New Chatbot Submission';
  
  // Prepare email body
  $body = "A new submission has been received from the chatbot:\n\n";
  
  // Format the submission data for the email
  foreach ($data as $key => $value) {
    if (is_array($value)) {
      $value = implode(', ', $value);
    }
    $body .= ucfirst(str_replace('_', ' ', $key)) . ": " . $value . "\n";
  }
  
  $body .= "\n\nSubmission Date: " . $submission->submitted_at;
  $body .= "\n\nView all submissions in your WordPress admin: " . admin_url('admin.php?page=okzea-chatbot-submissions');
  
  // Send the email
  wp_mail($admin_email, $subject, $body);
}

// Hook into the submission action
add_action('okzea_chatbot_after_save_submission', 'okzea_chatbot_send_email_notification'); 
