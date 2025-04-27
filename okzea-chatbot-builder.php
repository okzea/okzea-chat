<?php

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

/**
 * Add Question Builder submenu page.
 */
function okzea_chatbot_add_builder_page()
{
  add_submenu_page(
    'okzea-chatbot', // Correct parent slug
    __('Question Builder', 'okzea-chatbot'), // Page title
    __('Question Builder', 'okzea-chatbot'), // Menu title
    'manage_options', // Capability
    'okzea-chatbot-builder', // Menu slug
    'okzea_chatbot_builder_page_html' // Callback function
  );
}
add_action('admin_menu', 'okzea_chatbot_add_builder_page');

/**
 * Display the Question Builder page HTML.
 */
function okzea_chatbot_builder_page_html()
{
  // Check user capabilities
  if (!current_user_can('manage_options')) {
    return;
  }

  // Prepare data for JavaScript
  global $wpdb;
  $table_name = OKZEA_CHATBOT_QUESTIONS_TABLE;
  $db_questions = [];
  // Check if table exists before querying
  if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
    $db_questions = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY question_order ASC", ARRAY_A);
  } else {
    // Display an admin notice if the table doesn't exist?
    echo '<div class="notice notice-error"><p>' . sprintf(__('Error: Chatbot questions table %s not found. Please deactivate and reactivate the plugin.', 'okzea-chatbot'), $table_name) . '</p></div>';
  }

  // Enqueue scripts and styles
  wp_enqueue_script('okzea-chatbot-builder-script');
  wp_enqueue_style('okzea-chatbot-admin-style');

  // Localize script data AFTER enqueueing
  wp_localize_script('okzea-chatbot-builder-script', 'chatbotBuilderData', [
    'questions' => $db_questions, // Pass DB format, JS will process
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('okzea_chatbot_builder_nonce')
  ]);

?>
  <div class="wrap chatbot-builder-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div id="chatbot-question-builder-app">
      <div class="builder-controls">
        <button id="add-question-btn" class="button button-secondary"><?php _e('Add Question', 'okzea-chatbot'); ?></button>
        <button id="save-questions-btn" class="button button-primary" disabled><?php _e('Save Questions', 'okzea-chatbot'); ?></button>
        <span id="builder-status" style="display: none;"></span>
      </div>

      <div class="builder-list-container">
        <ul id="question-list" class="question-list-area">
          <li class="loading-placeholder"><?php _e('Loading questions...', 'okzea-chatbot'); ?></li>
        </ul>
      </div>

      <!-- Question Editor Modal -->
      <div id="question-editor-overlay"></div>
      <div id="question-editor" class="question-editor-modal">
        <form id="question-form">
          <h2>Edit Question</h2>
          <div class="form-content">
            <input type="hidden" id="edit-question-id" value="">
            <input type="hidden" id="edit-question-parent-id" value="0">

            <div class="form-row">
              <label for="question-text">Question Text <span class="required">*</span></label>
              <textarea id="question-text" required rows="3"></textarea>
            </div>

            <div class="form-row">
              <label for="field-type">Question Type <span class="required">*</span></label>
              <select id="field-type" required>
                <option value="" disabled selected>-- Select Type --</option>
                <option value="group">Group (Container for Sub-Questions)</option>
                <option value="submit">Submit Button</option>
                <option value="">Informational Message (No Input)</option>
                <optgroup label="Input Fields">
                  <option value="text">Text</option>
                  <option value="textarea">Textarea</option>
                  <option value="email">Email</option>
                  <option value="tel">Telephone</option>
                  <option value="number">Number</option>
                  <option value="date">Date</option>
                  <option value="select">Select Dropdown</option>
                  <option value="radio">Radio Buttons</option>
                  <option value="range">Range Slider</option>
                  <option value="hidden">Hidden Field</option>
                </optgroup>
              </select>
            </div>

            <!-- Field Name -->
            <div class="form-row field-specific" data-depends-on="text textarea email tel number date select radio hidden">
              <label for="field-name">Field Name / Key <span class="required">*</span></label>
              <input type="text" id="field-name" placeholder="e.g., user_name">
              <p class="help-text">Unique key for this field in submitted data. Use {index} for repeatable groups.</p>
            </div>

            <!-- Placeholder -->
            <div class="form-row field-specific" data-depends-on="text textarea email tel number date">
              <label for="placeholder">Placeholder Text</label>
              <input type="text" id="placeholder">
            </div>

            <!-- Options -->
            <div class="form-row field-specific" data-depends-on="select radio">
              <label for="options">Options</label>
              <textarea id="options" rows="4" placeholder="value1:Label One&#10;value2:Label Two"></textarea>
              <p class="help-text">Enter one option per line (value:Label)</p>
            </div>

            <!-- Direction -->
            <div class="form-row field-specific" data-depends-on="radio">
              <label for="direction">Radio Button Direction</label>
              <select id="direction">
                <option value="default">Default (Horizontal)</option>
                <option value="column">Column (Vertical)</option>
              </select>
            </div>

            <!-- Range Settings -->
            <div class="form-row field-specific" data-depends-on="range">
              <label>Range Slider Settings</label>
              <div class="range-settings">
                <div>
                  <label for="min-value">Min Value</label>
                  <input type="number" id="min-value" value="0">
                </div>
                <div>
                  <label for="max-value">Max Value</label>
                  <input type="number" id="max-value" value="10">
                </div>
                <div>
                  <label for="step-value">Step</label>
                  <input type="number" id="step-value" value="1">
                </div>
              </div>
              <div>
                <label for="default-value">Default Value</label>
                <input type="number" id="default-value">
              </div>
            </div>

            <!-- Date Settings -->
            <div class="form-row field-specific" data-depends-on="date">
              <label for="date-start-from">Date Picker Start Year</label>
              <input type="number" id="date-start-from" placeholder="e.g., 1950">
              <p class="help-text">Set the earliest year selectable in the date picker.</p>
            </div>

            <!-- Hidden Field Settings -->
            <div class="form-row field-specific" data-depends-on="hidden">
              <label for="static-value">Static Value</label>
              <input type="text" id="static-value" placeholder="Value to store directly">
              <p class="help-text">Set a fixed value for this hidden field.</p>
            </div>

            <!-- Conditional Logic -->
            <div class="form-row field-specific" data-depends-on="text textarea email tel number date select radio group hidden">
              <label>Conditional Logic</label>
              <div class="conditional-logic">
                <div>
                  <label for="depends-on">Depends On Field Name</label>
                  <input type="text" id="depends-on" placeholder="e.g., has_website">
                </div>
                <div>
                  <label for="starts-with">Show if Value Starts With</label>
                  <input type="text" id="starts-with" placeholder="e.g., yes">
                </div>
              </div>
            </div>

            <!-- Submit Messages -->
            <div class="form-row field-specific" data-depends-on="submit">
              <label for="submit-message">Success Message</label>
              <textarea id="submit-message" rows="2" placeholder="Thank you! We received your submission."></textarea>
            </div>
            <div class="form-row field-specific" data-depends-on="submit">
              <label for="submit-message-failed">Failure Message</label>
              <textarea id="submit-message-failed" rows="2" placeholder="Sorry, there was an error. Please try again."></textarea>
            </div>

            <!-- Boolean Options -->
            <div class="form-row field-specific" data-depends-on="text textarea email tel number date select radio">
              <label>Options</label>
              <div class="checkbox-group">
                <div class="checkbox-option">
                  <input id="is-required" type="checkbox">
                  <label for="is-required">Required Field</label>
                </div>
                <div class="checkbox-option">
                  <input id="skip-typing" type="checkbox">
                  <label for="skip-typing">Skip Typing Effect</label>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="form-actions">
            <div class="delete-action">
              <button type="button" id="delete-question-btn" class="button-link delete" style="display: none;">Delete Question</button>
            </div>
            <div class="save-actions">
              <button type="button" id="cancel-edit-btn" class="button button-secondary">Cancel</button>
              <button type="submit" class="button button-primary">Save Changes</button>
            </div>
          </div>
        </form>
      </div>

    </div>
  </div>
<?php
}

/**
 * Handles saving a single question recursively.
 * Used by the AJAX handler.
 *
 * @param array $question Question data array.
 * @param int $current_order Reference to the current order counter.
 * @param int $parent_db_id Parent question's database ID (0 for root).
 * @return bool|int Returns the new DB ID on success, false on failure.
 */
function okzea_chatbot_insert_question_recursive($question, &$current_order, $parent_db_id = 0)
{
  global $wpdb;
  $table_name = OKZEA_CHATBOT_QUESTIONS_TABLE; // Ensure constant is available

  // --- Basic Sanitization/Validation --- 
  $data = [
    'question_order' => $current_order,
    'question_text' => sanitize_textarea_field($question['question_text'] ?? ''),
    'field_type' => sanitize_text_field($question['field_type'] ?? null),
    'field_name' => sanitize_text_field($question['field_name'] ?? null),
    'placeholder' => sanitize_text_field($question['placeholder'] ?? null),
    'is_required' => !empty($question['is_required']) ? 1 : 0,
    'options' => isset($question['options']) ? wp_json_encode($question['options']) : null,
    'depends_on' => sanitize_text_field($question['depends_on'] ?? null),
    'starts_with' => sanitize_text_field($question['starts_with'] ?? null),
    'direction' => sanitize_text_field($question['direction'] ?? 'default'),
    'min_value' => isset($question['min_value']) ? intval($question['min_value']) : null,
    'max_value' => isset($question['max_value']) ? intval($question['max_value']) : null,
    'step_value' => isset($question['step_value']) ? intval($question['step_value']) : null,
    'default_value' => sanitize_text_field($question['default_value'] ?? null),
    'date_start_from' => sanitize_text_field($question['date_start_from'] ?? null),
    'show_if' => sanitize_text_field($question['show_if'] ?? null),
    'is_hidden' => !empty($question['is_hidden']) ? 1 : 0,
    'copy_from' => sanitize_text_field($question['copy_from'] ?? null),
    'static_value' => sanitize_text_field($question['static_value'] ?? null),
    'parent_id' => intval($parent_db_id),
    'is_group' => !empty($question['is_group']) ? 1 : 0,
    'add_item' => !empty($question['add_item']) ? 1 : 0,
    'skip_typing' => !empty($question['skip_typing']) ? 1 : 0,
    'is_submit' => !empty($question['is_submit']) ? 1 : 0,
    'submit_message' => sanitize_textarea_field($question['submit_message'] ?? null),
    'submit_message_failed' => sanitize_textarea_field($question['submit_message_failed'] ?? null),
  ];

  // Remove null keys to use DB defaults if applicable
  $data = array_filter($data, function ($value) {
    return $value !== null;
  });

  // Define formats for wpdb->insert
  $formats = [
    'question_order' => '%d',
    'question_text' => '%s',
    'field_type' => '%s',
    'field_name' => '%s',
    'placeholder' => '%s',
    'is_required' => '%d',
    'options' => '%s',
    'depends_on' => '%s',
    'starts_with' => '%s',
    'direction' => '%s',
    'min_value' => '%d',
    'max_value' => '%d',
    'step_value' => '%d',
    'default_value' => '%s',
    'date_start_from' => '%s',
    'show_if' => '%s',
    'is_hidden' => '%d',
    'copy_from' => '%s',
    'static_value' => '%s',
    'parent_id' => '%d',
    'is_group' => '%d',
    'add_item' => '%d',
    'skip_typing' => '%d',
    'is_submit' => '%d',
    'submit_message' => '%s',
    'submit_message_failed' => '%s',
  ];

  // Filter formats to match the actual data being inserted
  $current_formats = array_intersect_key($formats, $data);

  $result = $wpdb->insert($table_name, $data, $current_formats);

  if ($result === false) {
    error_log("Okzea Chatbot DB Error inserting question: " . $wpdb->last_error);
    return false; // Indicate error
  }

  $new_db_id = $wpdb->insert_id;
  $current_order++; // Increment order for the next item

  // If it's a group and has sub-questions (passed in original structure), insert them
  // Note: The JS sends a flat structure now, so sub-questions aren't in $question.
  // The current AJAX save logic handles the ordered flat list directly.
  // This recursive part is kept for potential future use if the data structure changes.
  /*
    if (!empty($question['is_group']) && !empty($question['sub_questions']) && is_array($question['sub_questions'])) {
        foreach ($question['sub_questions'] as $sub_q) {
            if (okzea_chatbot_insert_question_recursive($sub_q, $current_order, $new_db_id) === false) {
                return false; // Propagate error
            }
        }
    }
    */

  return $new_db_id; // Return the new ID
}

/**
 * AJAX handler for saving questions.
 */
function okzea_chatbot_save_questions_ajax()
{
  // Verify nonce
  check_ajax_referer('okzea_chatbot_builder_nonce', 'nonce');

  // Check user capabilities
  if (!current_user_can('manage_options')) {
    wp_send_json_error(['message' => 'Permission denied.'], 403);
    return;
  }

  // Get flat questions data from the POST request
  $questions_data = isset($_POST['questions']) ? json_decode(stripslashes($_POST['questions']), true) : null;

  if (is_null($questions_data) || !is_array($questions_data)) {
    wp_send_json_error(['message' => 'Invalid data format received.'], 400);
    return;
  }

  global $wpdb;
  $table_name = OKZEA_CHATBOT_QUESTIONS_TABLE;

  // Start transaction
  $wpdb->query('START TRANSACTION');

  // Clear existing questions
  $truncate_result = $wpdb->query("TRUNCATE TABLE $table_name");
  if ($truncate_result === false) {
    $wpdb->query('ROLLBACK');
    error_log("Okzea Chatbot DB Error truncating table: " . $wpdb->last_error);
    wp_send_json_error(['message' => 'Error clearing existing questions.'], 500);
    return;
  }

  $order = 0;
  $error = false;
  $parent_map = []; // parent_temp_id (from JS if needed) => new_db_id

  // Loop through the flat, ordered list submitted by JS
  foreach ($questions_data as $question) {
    // Here, $question['parent_id'] might be the *old* DB ID from the JS state before saving.
    // Since we TRUNCATE, we rely on the order of the flat array.
    // The parent_id in the DB will be implicitly correct because parents are inserted before children.
    // For this simple TRUNCATE/INSERT, we don't need complex parent mapping.
    // We just insert in the order received.

    $inserted_id = okzea_chatbot_insert_question_recursive($question, $order, $question['parent_id'] ?? 0); // Use parent_id from data

    if ($inserted_id === false) {
      $error = true;
      break;
    }
  }

  if ($error) {
    $wpdb->query('ROLLBACK');
    wp_send_json_error(['message' => 'Error saving questions. Check server logs.'], 500);
  } else {
    $wpdb->query('COMMIT');
    wp_send_json_success(['message' => 'Questions saved successfully.']);
  }

  wp_die();
}
add_action('wp_ajax_save_chatbot_questions', 'okzea_chatbot_save_questions_ajax');
