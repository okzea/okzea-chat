<?php

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

function okzea_chatbot_create_table()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'okzea_chatbot_submissions';
  $charset_collate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      submission_data longtext NOT NULL,
      submitted_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      PRIMARY KEY  (id)
  ) $charset_collate;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  
  // Check if dbDelta function exists
  if (function_exists('dbDelta')) {
    dbDelta($sql);
  } else {
    error_log('dbDelta function does not exist. Table creation failed.');
  }
}
register_activation_hook(__FILE__, 'okzea_chatbot_create_table');

function okzea_chatbot_check_and_create_table()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'okzea_chatbot_submissions';
  if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    okzea_chatbot_create_table();
  }
}
add_action('plugins_loaded', 'okzea_chatbot_check_and_create_table');

// Display 'data' as html list 
function okzea_chatbot_recursive_list($data)
{
  $html = '<ul>';
  foreach ($data as $key => $value) {
    if (is_array($value)) {
      $html .= '<li>';
      $html .= '<h3>' . esc_html($key) . '</h3>';
      $html .= '<ul style="margin-left: 20px; margin-bottom: 5px;">';
      $html .= '<li>';
      $html .= okzea_chatbot_recursive_list($value);
      $html .= '</li>';
      $html .= '</ul>';
      $html .= '</li>';
    } else {
      $html .= '<li><h3>' . esc_html($key) . '</h3>' . esc_html($value) . '</li>';
    }
  }
  $html .= '</ul>';
  return $html;
}

function okzea_chatbot_display_submissions()
{
  global $wpdb;

  // Enqueue admin styles
  wp_enqueue_style('okzea-chatbot-admin-style', plugin_dir_url(__FILE__) . 'dist/css/okzea-chatbot-admin.min.css');

  $table_name = $wpdb->prefix . 'okzea_chatbot_submissions';
  $submissions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");

  // colors
  $utms = array(
    'utm_source' => 'background-color: #f8d7da; color: #721c24;',
    'utm_medium' => 'background-color: #cce5ff; color: #004085;',
    'utm_campaign' => 'background-color: #d4edda; color: #155724;',
    'utm_term' => 'background-color: #fff3cd; color: #856404;',
    'utm_content' => 'background-color: #d1ecf1; color: #0c5460;',
  );

  echo '<div class="wrap"><h1>Okzea Chatbot Statistics</h1>';

  // Display cards with stats (utm source, medium, campaign, term, content)
  echo '<div style="display: flex; gap: 10px; margin-bottom: 20px;">';

  // count submissions with utm_source=meta
  $submissionsMeta = array_map(function ($submission) {
    return unserialize($submission->submission_data);
  }, $submissions);

  $totalSubmissions = count($submissions);
  $metaSubmissionsCount = count(array_filter($submissionsMeta, function ($data) {
    return isset($data['utm_source']) && $data['utm_source'] === 'meta';
  }));

  $submissionsCount = $metaSubmissionsCount . '/' . $totalSubmissions;

  $submissionsCountAsPercentage = $totalSubmissions > 0 ? round($metaSubmissionsCount / $totalSubmissions * 100, 2) : 0;

  echo '<div class="card" style="flex: 1;">';
  echo '<div class="card-body">';
  echo '<p class="card-title">Meta Source <h1>' . esc_html($submissionsCount) . ' <small>(' . esc_html($submissionsCountAsPercentage) . '%)</small></h1></p>';
  echo '</div></div>';

  echo '</div>';

  // color legend
  echo '<div style="margin-bottom: 10px; display: flex; flex-wrap: wrap; gap: 5px; align-items: center;">';
  echo '<span><b>UTM Legend</b></span>';
  foreach ($utms as $key => $color) {
    echo '<span class="badge badge-secondary" style="' . $color . '">' . esc_html($key) . '</span>';
  }
  echo '</div>';

  echo '<table class="wp-list-table widefat fixed">';
  echo '<thead><tr>';
  echo '<th style="width: 50px;">ID</th>';
  echo '<th>Name</th>';
  echo '<th>Has Website</th>';
  echo '<th>Urgency</th>';
  echo '<th>UTMs</th>';
  echo '<th style="width: 140px;">Date</th>';
  echo '<th style="width: 80px;"></th>';
  echo '</tr></thead>';

  echo '<tbody>';
  foreach ($submissions as $submission) {
    $data = unserialize($submission->submission_data);
    echo '<tr>';

    // Display 'id'
    echo '<td>' . esc_html($submission->id) . '</td>';

    // Display 'name'
    if (isset($data['contact']['name'])) {
        echo '<td>' . esc_html($data['contact']['name']) . '</td>';
    } else {
        echo '<td>N/A</td>';
    }

    // Display 'has_website'
    if (isset($data['has_website'])) {
        echo '<td>' . esc_html($data['has_website']) . '</td>';
    } else {
        echo '<td>N/A</td>';
    }

    // Display 'urgency'
    if (isset($data['urgency'])) {
        echo '<td>' . esc_html($data['urgency']) . '</td>';
    } else {
        echo '<td>N/A</td>';
    }

    // Display UTMs as badge (extandable td)
    echo '<td>';
    echo '<div style="display: flex; flex-wrap: wrap; gap: 5px;">';

    foreach ($utms as $key => $color) {
        if (isset($data[$key])) {
            echo '<span class="badge badge-secondary" style="' . $color . '">' . esc_html($data[$key]) . '</span>';
        }
    }

    echo '</div>';
    echo '</td>';

    // Display 'submitted_at'
    echo '<td>' . esc_html($submission->submitted_at) . '</td>';

    // Display 'data' in a modal dialog
    echo '<td style="text-align: center;">';
    add_thickbox();
    echo '<a href="#TB_inline?width=600&height=550&inlineId=okzea_chatbot_submission_data_' . $submission->id . '" class="thickbox">View data</a>';
    echo '<div id="okzea_chatbot_submission_data_' . $submission->id . '" style="display:none;">';
    echo okzea_chatbot_recursive_list($data);
    echo '</div>';
    echo '</td>';

    echo '</tr>';
  }
  echo '</tbody></table></div>';
}
