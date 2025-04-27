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
  <div class="wrap chatbot-builder-wrap okzea-admin-page-wrapper">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div id="chatbot-question-builder-app" class="mt-4 bg-gray-100 p-0 rounded shadow-sm border border-gray-300">
      <div class="builder-controls px-4 py-3 border-b border-gray-200 flex items-center justify-start gap-2">
        <button id="add-question-btn" class="button button-secondary"><?php _e('Add Question', 'okzea-chatbot'); ?></button>
        <button id="save-questions-btn" class="button button-primary" disabled><?php _e('Save Questions', 'okzea-chatbot'); ?></button>
        <span id="builder-status" class="text-sm italic text-gray-600 ml-3 align-middle" style="display: none;"></span>
      </div>

      <div class="builder-list-container p-4">
        <ul id="question-list" class="question-list-area space-y-2 min-h-[60px]">
          <!-- Questions will be loaded here by JS -->
          <li class="loading-placeholder p-5 text-center text-gray-500 italic"><?php _e('Loading questions...', 'okzea-chatbot'); ?></li>
        </ul>
      </div>

      <!-- Hidden Form/Modal for Adding/Editing Questions -->
      <div id="question-editor-overlay" class="fixed inset-0 bg-black bg-opacity-60 z-[1000] hidden" style="transition: opacity 0.3s ease; opacity: 0;"></div>
      <div id="question-editor" class="question-editor-modal fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 scale-90 w-11/12 max-w-2xl bg-white rounded-lg shadow-xl z-[1001] max-h-[85vh] overflow-y-auto hidden" style="transition: opacity 0.3s ease, transform 0.3s ease; opacity: 0;">
        <form id="question-form" class="p-6 sm:p-8">
          <h2 class="text-xl font-semibold mb-6 pb-4 border-b border-gray-200"><?php _e('Edit Question', 'okzea-chatbot'); ?></h2>
          <input type="hidden" id="edit-question-id" value="">
          <input type="hidden" id="edit-question-parent-id" value="0">

          <div class="form-row mb-4 pb-4 border-b border-gray-100 last:border-b-0">
            <label for="question-text" class="block text-sm font-bold text-gray-700 mb-1"><?php _e('Question Text', 'okzea-chatbot'); ?> <span class="text-red-500">*</span></label>
            <textarea id="question-text" required rows="3" class="mt-1 block w-full p-2 border border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
          </div>

          <div class="form-row mb-4 pb-4 border-b border-gray-100 last:border-b-0">
            <label for="field-type" class="block text-sm font-bold text-gray-700 mb-1"><?php _e('Question Type', 'okzea-chatbot'); ?> <span class="text-red-500">*</span></label>
            <select id="field-type" required class="mt-1 block w-full p-2 border border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white">
              <option value="" disabled selected><?php _e('-- Select Type --', 'okzea-chatbot'); ?></option>
              <option value="group"><?php _e('Group (Container for Sub-Questions)', 'okzea-chatbot'); ?></option>
              <option value="submit"><?php _e('Submit Button', 'okzea-chatbot'); ?></option>
              <option value=""><?php _e('Informational Message (No Input)', 'okzea-chatbot'); ?></option>
              <optgroup label="<?php _e('Input Fields', 'okzea-chatbot'); ?>">
                <option value="text"><?php _e('Text', 'okzea-chatbot'); ?></option>
                <option value="textarea"><?php _e('Textarea', 'okzea-chatbot'); ?></option>
                <option value="email"><?php _e('Email', 'okzea-chatbot'); ?></option>
                <option value="tel"><?php _e('Telephone', 'okzea-chatbot'); ?></option>
                <option value="number"><?php _e('Number', 'okzea-chatbot'); ?></option>
                <option value="date"><?php _e('Date', 'okzea-chatbot'); ?></option>
                <option value="select"><?php _e('Select Dropdown', 'okzea-chatbot'); ?></option>
                <option value="radio"><?php _e('Radio Buttons', 'okzea-chatbot'); ?></option>
                <option value="range"><?php _e('Range Slider', 'okzea-chatbot'); ?></option>
                <option value="hidden"><?php _e('Hidden Field', 'okzea-chatbot'); ?></option>
              </optgroup>
            </select>
          </div>

          <!-- Field Name (Depends on Type) -->
          <div class="form-row field-specific mb-4 pb-4 border-b border-gray-100 last:border-b-0" data-depends-on="text textarea email tel number date select radio hidden">
            <label for="field-name" class="block text-sm font-bold text-gray-700 mb-1"><?php _e('Field Name / Key', 'okzea-chatbot'); ?> <span class="text-red-500">*</span></label>
            <input type="text" id="field-name" class="mt-1 block w-full p-2 border border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g., user_name">
            <p class="mt-1 text-xs text-gray-500"><?php _e('Unique key for this field in submitted data. Use {index} for repeatable groups.', 'okzea-chatbot'); ?></p>
          </div>

          <!-- Placeholder (Depends on Type) -->
          <div class="form-row field-specific mb-4 pb-4 border-b border-gray-100 last:border-b-0" data-depends-on="text textarea email tel number date">
            <label for="placeholder" class="block text-sm font-bold text-gray-700 mb-1"><?php _e('Placeholder Text', 'okzea-chatbot'); ?></label>
            <input type="text" id="placeholder" class="mt-1 block w-full p-2 border border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
          </div>

          <!-- Options (for Select/Radio) -->
          <div class="form-row field-specific mb-4 pb-4 border-b border-gray-100 last:border-b-0" data-depends-on="select radio">
            <label for="options" class="block text-sm font-bold text-gray-700 mb-1"><?php _e('Options (for Select/Radio)', 'okzea-chatbot'); ?></label>
            <textarea id="options" rows="4" class="mt-1 block w-full p-2 border border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="value1:Label One\nvalue2:Label Two\n\nOR (for conditional options based on 'dependsOn' field's value):\ndependentValue1:[{\"value\":\"v1\",\"label\":\"L1\"},...]
dependentValue2:[{\"value\":\"vA\",\"label\":\"LA\"},...]"></textarea>
            <p class="mt-1 text-xs text-gray-500"><?php _e('Enter one option per line (<code>value:Label</code>). For conditional options based on another field, use <code>dependentValue:JSON_array</code>.', 'okzea-chatbot'); ?></p>
          </div>

          <!-- Direction (for Radio) -->
          <div class="form-row field-specific mb-4 pb-4 border-b border-gray-100 last:border-b-0" data-depends-on="radio">
            <label for="direction" class="block text-sm font-bold text-gray-700 mb-1"><?php _e('Radio Button Direction', 'okzea-chatbot'); ?></label>
            <select id="direction" class="mt-1 block w-full p-2 border border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white">
              <option value="default"><?php _e('Default (Horizontal)', 'okzea-chatbot'); ?></option>
              <option value="column"><?php _e('Column (Vertical)', 'okzea-chatbot'); ?></option>
            </select>
          </div>

          <!-- Range Slider Specific -->
          <div class="form-row field-specific mb-4 pb-4 border-b border-gray-100 last:border-b-0" data-depends-on="range">
            <label class="block text-sm font-bold text-gray-700 mb-1"><?php _e('Range Slider Settings', 'okzea-chatbot'); ?></label>
            <div class="grid grid-cols-3 gap-4 mt-1">
              <div>
                <label for="min-value" class="block text-xs font-medium text-gray-600"><?php _e('Min Value', 'okzea-chatbot'); ?></label>
                <input type="number" id="min-value" value="0" class="w-full p-2 border border-gray-300 rounded shadow-sm">
              </div>
              <div>
                <label for="max-value" class="block text-xs font-medium text-gray-600"><?php _e('Max Value', 'okzea-chatbot'); ?></label>
                <input type="number" id="max-value" value="10" class="w-full p-2 border border-gray-300 rounded shadow-sm">
              </div>
              <div>
                <label for="step-value" class="block text-xs font-medium text-gray-600"><?php _e('Step', 'okzea-chatbot'); ?></label>
                <input type="number" id="step-value" value="1" class="w-full p-2 border border-gray-300 rounded shadow-sm">
              </div>
            </div>
             <div class="mt-2">
                <label for="default-value" class="block text-xs font-medium text-gray-600"><?php _e('Default Value', 'okzea-chatbot'); ?></label>
                <input type="number" id="default-value" class="w-full p-2 border border-gray-300 rounded shadow-sm">
              </div>
          </div>

          <!-- Date Specific -->
          <div class="form-row field-specific mb-4 pb-4 border-b border-gray-100 last:border-b-0" data-depends-on="date">
            <label for="date-start-from" class="block text-sm font-bold text-gray-700 mb-1"><?php _e('Date Picker Start Year', 'okzea-chatbot'); ?></label>
            <input type="number" id="date-start-from" placeholder="e.g., 1950 (Defaults to current year - 100)" class="mt-1 block w-full p-2 border border-gray-300 rounded shadow-sm">
            <p class="mt-1 text-xs text-gray-500"><?php _e('Set the earliest year selectable in the date picker.', 'okzea-chatbot'); ?></p>
          </div>

          <!-- Hidden Field Specific -->
          <div class="form-row field-specific mb-4 pb-4 border-b border-gray-100 last:border-b-0" data-depends-on="hidden">
              <label for="static-value" class="block text-sm font-bold text-gray-700 mb-1"><?php _e('Static Value', 'okzea-chatbot'); ?></label>
              <input type="text" id="static-value" class="mt-1 block w-full p-2 border border-gray-300 rounded shadow-sm" placeholder="Value to store directly">
              <p class="mt-1 text-xs text-gray-500"><?php _e('Set a fixed value for this hidden field.', 'okzea-chatbot'); ?></p>
          </div>
          <div class="form-row field-specific mb-4 pb-4 border-b border-gray-100 last:border-b-0" data-depends-on="hidden">
              <label for="copy-from" class="block text-sm font-bold text-gray-700 mb-1"><?php _e('Copy Value From Field', 'okzea-chatbot'); ?></label>
              <input type="text" id="copy-from" class="mt-1 block w-full p-2 border border-gray-300 rounded shadow-sm" placeholder="e.g., contact[email]">
              <p class="mt-1 text-xs text-gray-500"><?php _e('Dynamically copy the value from another field (use {index} if needed). Overrides Static Value.', 'okzea-chatbot'); ?></p>
          </div>

          <!-- Conditional Logic -->
          <div class="form-row field-specific mb-4 pb-4 border-b border-gray-100 last:border-b-0" data-depends-on="text textarea email tel number date select radio group hidden">
            <label class="block text-sm font-bold text-gray-700 mb-1"><?php _e('Conditional Logic', 'okzea-chatbot'); ?></label>
            <div class="grid grid-cols-2 gap-4 mt-1">
              <div>
                <label for="depends-on" class="block text-xs font-medium text-gray-600"><?php _e('Depends On Field Name', 'okzea-chatbot'); ?></label>
                <input type="text" id="depends-on" placeholder="e.g., has_website" class="w-full p-2 border border-gray-300 rounded shadow-sm">
              </div>
              <div>
                <label for="starts-with" class="block text-xs font-medium text-gray-600"><?php _e('Show if Value Starts With', 'okzea-chatbot'); ?></label>
                <input type="text" id="starts-with" placeholder="e.g., yes" class="w-full p-2 border border-gray-300 rounded shadow-sm">
              </div>
            </div>
            <div class="mt-2">
                <label for="show-if" class="block text-xs font-medium text-gray-600"><?php _e('Advanced Show Condition (JavaScript)', 'okzea-chatbot'); ?></label>
                 <input type="text" id="show-if" placeholder="e.g., this.formData['fieldName'] === 'value' || this.formData['other'] > 5" class="w-full p-2 border border-gray-300 rounded shadow-sm font-mono text-xs">
                  <p class="mt-1 text-xs text-gray-500"><?php _e('Overrides basic check. Use <code>this.formData[\'field\']</code> to access values. Example: <code>this.formData[\'plan\'] == \'premium\'</code>. Use <code>{index}</code> in field names for repeatable groups.', 'okzea-chatbot'); ?></p>
            </div>
            <p class="mt-1 text-xs text-gray-500"><?php _e('Show this question only if the value of the specified field starts with the given text, or if the advanced condition is met.', 'okzea-chatbot'); ?></p>
          </div>

           <!-- Submit Specific -->
          <div class="form-row field-specific mb-4 pb-4 border-b border-gray-100 last:border-b-0" data-depends-on="submit">
            <label for="submit-message" class="block text-sm font-bold text-gray-700 mb-1"><?php _e('Success Message', 'okzea-chatbot'); ?></label>
            <textarea id="submit-message" rows="2" class="mt-1 block w-full p-2 border border-gray-300 rounded shadow-sm" placeholder="Thank you! We received your submission."></textarea>
          </div>
           <div class="form-row field-specific mb-4 pb-4 border-b border-gray-100 last:border-b-0" data-depends-on="submit">
            <label for="submit-message-failed" class="block text-sm font-bold text-gray-700 mb-1"><?php _e('Failure Message', 'okzea-chatbot'); ?></label>
            <textarea id="submit-message-failed" rows="2" class="mt-1 block w-full p-2 border border-gray-300 rounded shadow-sm" placeholder="Sorry, there was an error. Please try again."></textarea>
          </div>

          <!-- Boolean Flags -->
          <div class="form-row mb-4 pb-4 border-b border-gray-100 last:border-b-0 field-specific" data-depends-on="text textarea email tel number date select radio">
            <label class="block text-sm font-bold text-gray-700 mb-1"><?php _e('Options', 'okzea-chatbot'); ?></label>
            <div class="mt-2 space-y-2">
              <div class="flex items-center">
                <input id="is-required" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                <label for="is-required" class="ml-2 block text-sm text-gray-900"><?php _e('Required Field', 'okzea-chatbot'); ?></label>
              </div>
              <div class="flex items-center field-specific" data-depends-on="hidden">
                <input id="is-hidden" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                <label for="is-hidden" class="ml-2 block text-sm text-gray-900"><?php _e('Hidden Field (Advanced)', 'okzea-chatbot'); ?></label>
                 <p class="ml-4 text-xs text-gray-500"><?php _e('Requires Field Name. Use Static Value or Copy From. Use Show Condition for conditional hiding.', 'okzea-chatbot'); ?></p>
              </div>
               <div class="flex items-center field-specific" data-depends-on="radio">
                <input id="add-item" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                <label for="add-item" class="ml-2 block text-sm text-gray-900"><?php _e('Add Another Item (Repeat Group)', 'okzea-chatbot'); ?></label>
                 <p class="ml-4 text-xs text-gray-500"><?php _e('Use on a Yes/No radio button within a Group to repeat the group questions.', 'okzea-chatbot'); ?></p>
              </div>
               <div class="flex items-center field-specific" data-depends-on="text textarea email tel number date select radio">
                <input id="skip-typing" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                <label for="skip-typing" class="ml-2 block text-sm text-gray-900"><?php _e('Skip Typing Effect for this Question', 'okzea-chatbot'); ?></label>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="form-actions mt-6 pt-5 border-t border-gray-200 flex justify-between items-center">
            <div>
              <button type="button" id="delete-question-btn" class="button-link button-link-delete text-red-600 hover:text-red-800" style="display: none;"><?php _e('Delete Question', 'okzea-chatbot'); ?></button>
            </div>
            <div>
              <button type="button" id="cancel-edit-btn" class="button button-secondary mr-2"><?php _e('Cancel', 'okzea-chatbot'); ?></button>
              <button type="submit" class="button button-primary"><?php _e('Save Changes', 'okzea-chatbot'); ?></button>
            </div>
          </div>
        </form>
      </div>

    </div> <!-- /#chatbot-question-builder-app -->
  </div> <!-- /.wrap -->
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
