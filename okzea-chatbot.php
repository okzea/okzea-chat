<?php
/**
 * Plugin Name: okzea Chatbot
 * Description: A chatbot plugin generates leads.
 * Version: 1.0
 * Author: Okzea
 * Author URI: https://okzea.com
 */

require_once 'okzea-chatbot-settings.php';
require_once 'okzea-chatbot-datatable.php';
require_once 'okzea-chatbot-menu.php';
require_once 'okzea-chatbot-save-submission.php';
require_once 'okzea-chatbot-email-notification.php';

// Add Facebook Pixel
function okzea_chatbot_facebook_pixel() {
  $pixel_id = get_option('okzea_chatbot_facebook_pixel_id');
  if ($pixel_id) {
      ?>
      <!-- Facebook Pixel Code -->
      <script>
          !function(f,b,e,v,n,t,s)
          {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
          n.callMethod.apply(n,arguments):n.queue.push(arguments)};
          if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
          n.queue=[];t=b.createElement(e);t.async=!0;
          t.src=v;s=b.getElementsByTagName(e)[0];
          s.parentNode.insertBefore(t,s)}(window, document,'script',
          'https://connect.facebook.net/en_US/fbevents.js');
          fbq('init', '<?php echo $pixel_id; ?>');
          fbq('track', 'PageView');
      </script>
      <noscript>
          <img height="1" width="1" style="display:none"
          src="https://www.facebook.com/tr?id=<?php echo $pixel_id; ?>&ev=PageView&noscript=1"/>
      </noscript>
      <!-- End Facebook Pixel Code -->
      <?php
  }
}

add_action('wp_head', 'okzea_chatbot_facebook_pixel');

// Enqueue the JavaScript file
function okzea_chatbot_enqueue_scripts() {
    wp_enqueue_script('okzea-chatbot-script', plugins_url('/dist/js/okzea-chatbot.min.js', __FILE__), array(), '1.0', true);
    
    // Enqueue the contact modal script
    wp_enqueue_script('okzea-contact-modal-script', plugins_url('/dist/js/okzea-contact-modal.min.js', __FILE__), array(), '1.0', true);
}
add_action('wp_enqueue_scripts', 'okzea_chatbot_enqueue_scripts');

// Create a rewrite rule for the /chatbot slug
function okzea_chatbot_rewrite_rule() {
    add_rewrite_rule('^chat/?$', 'index.php?okzea_chatbot=1', 'top');
}
add_action('init', 'okzea_chatbot_rewrite_rule');

// Add query var
function okzea_chatbot_query_vars($query_vars) {
    $query_vars[] = 'okzea_chatbot';
    return $query_vars;
}
add_filter('query_vars', 'okzea_chatbot_query_vars');

// Template redirect
function okzea_chatbot_template_redirect() {
    if (get_query_var('okzea_chatbot')) {
        include plugin_dir_path(__FILE__) . 'templates/chatbot-template.php';
        exit;
    }
}
add_action('template_redirect', 'okzea_chatbot_template_redirect');

// Flush rewrite rules on plugin activation and deactivation
function okzea_chatbot_flush_rewrite_rules() {
  okzea_chatbot_rewrite_rule();
  flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'okzea_chatbot_flush_rewrite_rules');
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

?>
