<?php

/**
 * Plugin Name: okzea Chatbot
 * Description: A chatbot plugin generates leads.
 * Version: 1.0.1
 * Author: Okzea
 * Author URI: https://okzea.com
 */

require_once 'okzea-chatbot-settings.php';
require_once 'okzea-chatbot-datatable.php';
require_once 'okzea-chatbot-menu.php';
require_once 'okzea-chatbot-save-submission.php';
require_once 'okzea-chatbot-email-notification.php';
require_once plugin_dir_path(__FILE__) . 'okzea-chatbot-builder.php'; // Include builder logic

// Add Facebook Pixel
function okzea_chatbot_facebook_pixel()
{
    $pixel_id = get_option('okzea_chatbot_facebook_pixel_id');
    if ($pixel_id) {
?>
        <!-- Facebook Pixel Code -->
        <script>
            ! function(f, b, e, v, n, t, s) {
                if (f.fbq) return;
                n = f.fbq = function() {
                    n.callMethod ?
                        n.callMethod.apply(n, arguments) : n.queue.push(arguments)
                };
                if (!f._fbq) f._fbq = n;
                n.push = n;
                n.loaded = !0;
                n.version = '2.0';
                n.queue = [];
                t = b.createElement(e);
                t.async = !0;
                t.src = v;
                s = b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t, s)
            }(window, document, 'script',
                'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '<?php echo $pixel_id; ?>');
            fbq('track', 'PageView');
        </script>
        <noscript>
            <img height="1" width="1" style="display:none"
                src="https://www.facebook.com/tr?id=<?php echo $pixel_id; ?>&ev=PageView&noscript=1" />
        </noscript>
        <!-- End Facebook Pixel Code -->
<?php
    }
}

add_action('wp_head', 'okzea_chatbot_facebook_pixel');

// Enqueue the JavaScript file
function okzea_chatbot_enqueue_scripts()
{
    wp_enqueue_script('okzea-chatbot-script', plugins_url('/dist/js/okzea-chatbot.min.js', __FILE__), array(), '1.0.1', true);

    // Enqueue the contact modal script
    wp_enqueue_script('okzea-contact-modal-script', plugins_url('/dist/js/okzea-contact-modal.min.js', __FILE__), array(), '1.0.1', true);
}
add_action('wp_enqueue_scripts', 'okzea_chatbot_enqueue_scripts');

// Create a rewrite rule for the /chatbot slug
function okzea_chatbot_rewrite_rule()
{
    add_rewrite_rule('^chat/?$', 'index.php?okzea_chatbot=1', 'top');
}
add_action('init', 'okzea_chatbot_rewrite_rule');

// Add query var
function okzea_chatbot_query_vars($query_vars)
{
    $query_vars[] = 'okzea_chatbot';
    return $query_vars;
}
add_filter('query_vars', 'okzea_chatbot_query_vars');

// Template redirect
function okzea_chatbot_template_redirect()
{
    if (get_query_var('okzea_chatbot')) {
        include plugin_dir_path(__FILE__) . 'templates/chatbot-template.php';
        exit;
    }
}
add_action('template_redirect', 'okzea_chatbot_template_redirect');

// Flush rewrite rules on plugin activation and deactivation
function okzea_chatbot_flush_rewrite_rules()
{
    okzea_chatbot_rewrite_rule();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'okzea_chatbot_flush_rewrite_rules');
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

// Define table name
global $wpdb;
define('OKZEA_CHATBOT_QUESTIONS_TABLE', $wpdb->prefix . 'okzea_chatbot_questions');

/**
 * Create database table on plugin activation.
 */
function okzea_chatbot_activate()
{
    global $wpdb;
    $table_name = OKZEA_CHATBOT_QUESTIONS_TABLE;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        question_order mediumint(9) DEFAULT 0 NOT NULL,
        question_text text NOT NULL,
        field_type varchar(50) DEFAULT NULL,
        field_name varchar(100) DEFAULT NULL,
        placeholder varchar(255) DEFAULT NULL,
        is_required tinyint(1) DEFAULT 0 NOT NULL,
        options text DEFAULT NULL, -- JSON encoded array of { value: '...', label: '...' } or optionsSets
        depends_on varchar(100) DEFAULT NULL,
        starts_with varchar(50) DEFAULT NULL,
        direction varchar(20) DEFAULT 'default', -- column, default
        min_value int DEFAULT NULL, -- For range
        max_value int DEFAULT NULL, -- For range
        step_value int DEFAULT NULL, -- For range
        default_value varchar(255) DEFAULT NULL, -- For range initial value
        date_start_from varchar(50) DEFAULT NULL, -- For date
        show_if text DEFAULT NULL, -- Conditional logic (sub-questions)
        is_hidden tinyint(1) DEFAULT 0 NOT NULL, -- Conditional logic
        copy_from varchar(100) DEFAULT NULL, -- Conditional logic
        static_value text DEFAULT NULL, -- For hidden fields
        parent_id mediumint(9) DEFAULT 0 NOT NULL, -- For grouping sub-questions
        is_group tinyint(1) DEFAULT 0 NOT NULL, -- Indicates a question group
        add_item tinyint(1) DEFAULT 0 NOT NULL, -- For 'add another item' logic
        skip_typing tinyint(1) DEFAULT 0 NOT NULL,
        is_submit tinyint(1) DEFAULT 0 NOT NULL, -- Submit action flag
        submit_message text DEFAULT NULL,
        submit_message_failed text DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY question_order (question_order),
        KEY parent_id (parent_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Check if table exists after creation attempt
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // Log error or display admin notice
        error_log("Error creating Okzea Chatbot questions table ($table_name).");
    } else {
        // Optionally: Populate with default questions if table is newly created and empty
        okzea_chatbot_populate_default_questions();
    }
}
register_activation_hook(__FILE__, 'okzea_chatbot_activate');

/**
 * Populate the questions table with default questions from the JS file
 * if the table is empty.
 */
function okzea_chatbot_populate_default_questions()
{
    global $wpdb;
    $table_name = OKZEA_CHATBOT_QUESTIONS_TABLE;

    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    // Only populate if the table is empty
    if ($count == 0) {
        // Define default questions structure (mirroring JS)
        $default_questions = [
            [
                'question_text' => "Hello, I'm your online website creation assistant.",
            ],
            [
                'question_text' => "Please tell me about you and your website needs so I can help you create the perfect website.",
            ],
            [
                'question_text' => "What is your name?",
                'field_type' => "text",
                'field_name' => "contact[name]",
                'placeholder' => "Your name",
                'is_required' => true
            ],
            [
                'question_text' => "What is your email address?",
                'field_type' => "email",
                'field_name' => "contact[email]",
                'placeholder' => "Your email",
                'is_required' => true
            ],
            [
                'question_text' => "What is your phone number?",
                'field_type' => "tel",
                'field_name' => "contact[phone]",
                'placeholder' => "Your phone number"
            ],
            [
                'question_text' => "Do you already have a website?",
                'field_type' => "radio",
                'field_name' => "has_website",
                'options' => json_encode([
                    ['value' => "yes", 'label' => "Yes"],
                    ['value' => "no", 'label' => "No"]
                ]),
                'is_required' => true
            ],
            [
                'question_text' => "Do you already own a domain name for the website you want to build?",
                'field_type' => "text",
                'field_name' => "domain_name",
                'placeholder' => "Your domain name or 'No'",
                'depends_on' => "has_website",
                'starts_with' => "no"
            ],
            [
                'question_text' => "What's the url of your website?",
                'field_type' => "text",
                'field_name' => "website_url",
                'placeholder' => "Your website url",
                'depends_on' => "has_website",
                'starts_with' => "yes"
            ],
            [
                'question_text' => "Please describe the type of business you are running and if there is anything particular you will need on your website.",
                'field_type' => "textarea",
                'field_name' => "business_description",
                'placeholder' => "Tell us about your business and website needs"
            ],
            [
                'question_text' => "Do you already have branding elements for your company such as logo, colors and typography elements?",
                'field_type' => "radio",
                'field_name' => "has_branding",
                'options' => json_encode([
                    ['value' => "yes", 'label' => "Yes"],
                    ['value' => "no", 'label' => "No"]
                ])
            ],
            [
                'question_text' => "What should be the primary color of your website?",
                'field_type' => "text",
                'field_name' => "primary_color",
                'placeholder' => "E.g., #FF5733, blue, etc."
            ],
            [
                'question_text' => "What should be the secondary or accent colors of your website?",
                'field_type' => "text",
                'field_name' => "secondary_colors",
                'placeholder' => "E.g., #33FF57, red, etc."
            ],
            [
                'question_text' => "Do you have a specific typography?",
                'field_type' => "text",
                'field_name' => "typography",
                'placeholder' => "E.g., Roboto, Arial, etc."
            ],
            [
                'question_text' => "Please share a website that you like and would like your own website to be inspired by (Website 1)",
                'field_type' => "text",
                'field_name' => "reference_website_1",
                'placeholder' => "Website URL"
            ],
            [
                'question_text' => "Please share another website that you like (Website 2)",
                'field_type' => "text",
                'field_name' => "reference_website_2",
                'placeholder' => "Website URL"
            ],
            [
                'question_text' => "Please share one more website that you like (Website 3)",
                'field_type' => "text",
                'field_name' => "reference_website_3",
                'placeholder' => "Website URL"
            ],
            [
                'question_text' => "How should your website feel?",
                'field_type' => "radio",
                'field_name' => "website_feel",
                'direction' => "column",
                'options' => json_encode([
                    ['value' => "adventurous", 'label' => "Adventurous"],
                    ['value' => "dark", 'label' => "Dark"],
                    ['value' => "exotic", 'label' => "Exotic"],
                    ['value' => "light", 'label' => "Light"],
                    ['value' => "luxurious", 'label' => "Luxurious"],
                    ['value' => "minimalistic", 'label' => "Minimalistic"],
                    ['value' => "modern", 'label' => "Modern"]
                ])
            ],
            [
                'question_text' => "Is there anything else you would like to share?",
                'field_type' => "textarea",
                'field_name' => "additional_info",
                'placeholder' => "Additional information"
            ],
            [
                'question_text' => "How urgently do you need your website?",
                'field_type' => "radio",
                'field_name' => "urgency",
                'direction' => "column",
                'options' => json_encode([
                    ['value' => "asap", 'label' => "As soon as possible"],
                    ['value' => "2-3-weeks", 'label' => "Within 2 to 3 weeks"],
                    ['value' => "no-hurry", 'label' => "I'm in no hurry"]
                ])
            ],
            [
                'is_submit' => true,
                'submit_message' => "Thank you for providing your information. We'll review your website request and get back to you soon!",
                'submit_message_failed' => "An error occurred while submitting your request. Please try again later."
            ]
        ];

        $order = 0;
        $error = false;
        // Ensure the insert function is available (it's in the builder file)
        if (!function_exists('okzea_chatbot_insert_question_recursive')) {
            error_log('Okzea Chatbot Error: okzea_chatbot_insert_question_recursive function not found during default population.');
            return; // Cannot proceed
        }

        // Start transaction for bulk insert
        $wpdb->query('START TRANSACTION');

        foreach ($default_questions as $question_data) {
            // Convert boolean true to 1 for DB insertion compatibility
            foreach ($question_data as $key => $value) {
                if ($value === true) {
                    $question_data[$key] = 1;
                }
            }

            // Call the recursive insert function (passing order by reference)
            // For default population, all are root questions (parent_id = 0)
            $inserted_id = okzea_chatbot_insert_question_recursive($question_data, $order, 0);

            if ($inserted_id === false) {
                $error = true;
                error_log("Okzea Chatbot Error: Failed to insert default question: " . json_encode($question_data));
                break; // Stop on first error
            }
        }

        if ($error) {
            $wpdb->query('ROLLBACK');
            error_log('Okzea Chatbot Error: Rolled back default question population due to errors.');
        } else {
            $wpdb->query('COMMIT');
            error_log('Okzea Chatbot: Successfully populated default questions.');
        }
    }
}

// Hook to provide questions data to the frontend chatbot script
add_action('wp_enqueue_scripts', 'okzea_chatbot_localize_questions');
function okzea_chatbot_localize_questions()
{
    // Ensure the main script handle is correct
    $script_handle = 'okzea-chatbot-script';

    // Check if the script is actually enqueued before trying to localize
    if (!wp_script_is($script_handle, 'enqueued')) {
        // If the script might not always be enqueued (e.g., only on pages with shortcode),
        // then we shouldn't proceed here.
        return;
    }

    global $wpdb;
    $table_name = OKZEA_CHATBOT_QUESTIONS_TABLE;
    // Check if table exists before querying
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        error_log("Okzea Chatbot: Questions table ($table_name) does not exist. Cannot localize questions.");
        $questions = []; // Provide empty array to JS
    } else {
        $db_questions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY question_order ASC", ARRAY_A);
        // Process questions from DB format to the nested structure JS expects
        $questions = okzea_chatbot_prepare_questions_for_js($db_questions);
    }

    wp_localize_script($script_handle, 'chatbotSettings', [
        'questions' => $questions,
        // Add other global settings if needed
    ]);
}

/**
 * Enqueue assets (CSS/JS) for ALL okzea-chatbot admin pages.
 */
function okzea_chatbot_admin_assets($hook)
{
  // Define the specific hooks for our plugin pages
  $plugin_pages = [
    //'toplevel_page_okzea-chatbot', // Uncomment if you have a true top-level page callback
    'chatbot-okzea_page_okzea-chatbot', // Main page (Statistiques)
    'chatbot-okzea_page_okzea-chatbot-settings', // Settings page
    'chatbot-okzea_page_okzea-chatbot-builder' // Builder page - Using the name from logs
    // Add other plugin page hooks here if necessary
  ];

  // Check if the current hook is one of our plugin pages
  if (!in_array($hook, $plugin_pages)) {
    return;
  }

  // Enqueue Tailwind-generated admin styles for all plugin admin pages
  wp_enqueue_style(
    'okzea-chatbot-admin-tailwind-style',
    plugin_dir_url(__FILE__) . 'dist/css/okzea-chatbot-admin.min.css',
    [],
    '1.0.0'
  );

  // --- Builder-specific assets ---
  // Only load builder JS and localize data on the builder page itself
  if ('chatbot-okzea_page_okzea-chatbot-builder' == $hook) { // Compare directly with $hook
    error_log("Loading builder-specific JS...");
    wp_enqueue_script(
      'okzea-chatbot-builder-script',
      plugin_dir_url(__FILE__) . 'dist/js/okzea-chatbot-builder.min.js',
      [],
      '1.0.0',
      true
    );

    // Load questions from DB to pass to JS
    global $wpdb;
    $table_name = OKZEA_CHATBOT_QUESTIONS_TABLE;
    $questions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY question_order ASC", ARRAY_A);

    // Pass data to the script
    wp_localize_script('okzea-chatbot-builder-script', 'chatbotBuilderData', [
      'questions' => $questions,
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('okzea_chatbot_builder_nonce')
    ]);
  }
}
// Use a priority lower than default (10) if needed, but default should be fine
add_action('admin_enqueue_scripts', 'okzea_chatbot_admin_assets');

/**
 * Helper function to process flat DB questions into a nested array for JS.
 *
 * @param array $db_questions Array of questions from the database.
 * @return array Nested array of questions.
 */
function okzea_chatbot_prepare_questions_for_js($db_questions)
{
    $questions = [];
    $question_map = []; // Helper map [db_id => array_index in $questions]
    $sub_question_map = []; // Helper map [db_id => reference to sub_question array]

    // First pass: Add all non-grouped questions and group placeholders
    foreach ($db_questions as $q) {
        $processed_q = [
            // Map DB columns to JS properties
            'question' => $q['question_text'],
            'fieldType' => $q['field_type'],
            'fieldName' => $q['field_name'],
            'placeholder' => $q['placeholder'],
            'required' => (bool) $q['is_required'],
            // options/optionsSets handled below
            'dependsOn' => $q['depends_on'],
            'startsWith' => $q['starts_with'],
            'direction' => $q['direction'],
            'min' => $q['min_value'] !== null ? (int) $q['min_value'] : null,
            'max' => $q['max_value'] !== null ? (int) $q['max_value'] : null,
            'step' => $q['step_value'] !== null ? (int) $q['step_value'] : null,
            'value' => $q['default_value'], // For range initial value and hidden field static value
            'dateStartFrom' => $q['date_start_from'],
            'showIf' => $q['show_if'],
            'hidden' => (bool) $q['is_hidden'],
            'copyFrom' => $q['copy_from'],
            // 'value' is potentially overwritten by static_value if set
            'value' => $q['static_value'] ?? $q['default_value'],
            'addItem' => (bool) $q['add_item'],
            'skipTyping' => (bool) $q['skip_typing'],
            'submit' => (bool) $q['is_submit'],
            'submitMessage' => $q['submit_message'],
            'submitMessageFailed' => $q['submit_message_failed'],
        ];

        // Decode and assign options/optionsSets
        $options_data = $q['options'] ? json_decode($q['options'], true) : null;
        if (is_array($options_data)) {
            $first_key = key($options_data);
            // Heuristic: Check if the first element is an array (likely optionsSet) or has 'value'/'label' keys
            if ($first_key !== null && is_array($options_data[$first_key])) {
                $processed_q['optionsSets'] = $options_data;
            } elseif ($first_key === 0 && isset($options_data[0]['value']) && isset($options_data[0]['label'])) {
                $processed_q['options'] = $options_data;
            } else {
                // Assume it might be an optionsSet if it's an associative array not matching the option structure
                $processed_q['optionsSets'] = $options_data;
            }
        }

        // Clean up null/empty values potentially interfering with JS
        $processed_q = array_filter($processed_q, function ($value) {
            return $value !== null && $value !== '';
        });

        // Store based on parent_id
        $parent_id = (int)$q['parent_id'];
        $db_id = (int)$q['id'];

        if ($parent_id === 0) {
            if ((bool)$q['is_group']) {
                $processed_q['questions'] = []; // Initialize for sub-questions
                $sub_question_map[$db_id] = &$processed_q['questions']; // Store reference
            }
            $questions[] = $processed_q;
            $question_map[$db_id] = count($questions) - 1; // Store index
        } else {
            // This is a sub-question, find its parent list reference
            if (isset($sub_question_map[$parent_id])) {
                // If the sub-question is itself a group
                if ((bool)$q['is_group']) {
                    $processed_q['questions'] = [];
                    $sub_question_map[$db_id] = &$processed_q['questions'];
                }
                // Add the sub-question to the parent's 'questions' array by reference
                $sub_question_map[$parent_id][] = $processed_q;
            } else {
                // Parent not found or wasn't marked as a group, log error?
                error_log("Okzea Chatbot: Parent ID $parent_id not found or not a group for sub-question ID $db_id.");
            }
        }
    }

    return $questions;
}

?>
