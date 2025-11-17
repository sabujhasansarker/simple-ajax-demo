<?php

/**
 * Plugin Name: Simple Ajax Demo
 * description : A minimal plugin demonstration AJAX requests with nonce verification and CPT storage
 * Version: 1.0.0
 * Author: Sabuj
 */

if (!defined('ABSPATH')) {
     exit;
}

class Simple_Ajax_Demo
{
     public function __construct()
     {
          add_action('init', array($this, 'init'));
     }

     function init()
     {
          $this->register_post_type();
          add_action("wp_enqueue_scripts", array($this, 'enqueue_assets'));
          add_shortcode('ajax-demo', array($this, 'render_form'));
          add_action('wp_ajax_submit_newsletter', array($this, 'handel_ajax_submission'));
          // for gust user
          add_action('wp_ajax_nopriv_submit_newsletter', array($this, 'handel_ajax_submission'));
     }
     public function handel_ajax_submission()
     {
          if (!wp_verify_nonce($_POST['newsletter_nonce_field'], 'newsletter_nonce')) {
               wp_send_json_error('Security check failed');
          } else {
               $email = sanitize_email($_POST['newsletter_email']);
               if (empty($email)) {
                    wp_send_json_error('Email address is required');
               }
               if (!is_email($email)) {
                    wp_send_json_error('Please enter a valid email address');
               }

               $ip_address = $this->get_user_ip();
               $post_data = array(
                    'post_title' => 'Newsletter Subscription: ' . $email,
                    'post_content' => sprintf(
                         "Email: %s\nIP Address: %s\nSubscribed on: %s",
                         $email,
                         $ip_address,
                         current_time('mysql')
                    ),
                    'post_status' => 'publish',
                    'post_type' => 'newsletter_sub'
               );
               $post_id = wp_insert_post($post_data);
               if ($post_id) {
                    wp_send_json_success('Thank you! You have been successfully subscribed to our newsletter.');
               } else {
                    wp_send_json_error('Sorry, there was an error processing your subscription. Please try again.');
               }
          }
     }
     public function render_form($atts)
     {
          $atts = shortcode_atts(array(
               'title' => 'Newsletter Subscription'
          ), $atts);

          ob_start();
?>
          <div class="ajax-demo-container">
               <h3><?php echo esc_html($atts['title']); ?></h3>

               <div id="ajax-demo-messages"></div>

               <form id="ajax-demo-form" class="ajax-demo-form">
                    <div class="ajax-demo-field">
                         <label for="newsletter-email">Email Address *</label>
                         <input type="email" id="newsletter-email" name="newsletter_email" required
                              placeholder="Enter your email address">
                    </div>

                    <div class="ajax-demo-field">
                         <button type="submit" class="ajax-demo-submit">Subscribe</button>
                    </div>
               </form>
          </div>
<?php
          return ob_get_clean();
     }
     public function enqueue_assets()
     {
          wp_enqueue_style('ajax-demo-style', plugin_dir_url(__FILE__) . 'style.css', [], '1.0.0');
          wp_enqueue_script('ajax-demo-script', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], '1.0.0');

          wp_localize_script('ajax-demo-script', 'ajax_demo', array(
               'ajax_url' => admin_url('admin-ajax.php'),
               'nonce' => wp_create_nonce('newsletter_nonce')
          ));
     }
     private function register_post_type()
     {
          $args = array(
               'label' => 'Newsletter Subscriptions',
               'labels' => array(
                    'name' => 'Newsletter Subscriptions',
                    'singular_name' => 'Newsletter Subscription',
                    'add_new' => 'Add New',
                    'add_new_item' => 'Add New Subscription',
                    'edit_item' => 'Edit Subscription',
                    'new_item' => 'New Subscription',
                    'view_item' => 'View Subscription',
                    'search_items' => 'Search Subscriptions',
                    'not_found' => 'No subscriptions found',
                    'not_found_in_trash' => 'No subscriptions found in trash'
               ),
               'public' => false,
               'show_ui' => true,
               'show_in_menu' => true,
               'capability_type' => 'post',
               'capabilities' => array(
                    'create_posts' => false,
               ),
               'map_meta_cap' => true,
               'supports' => array('title', 'editor'),
               'menu_icon' => 'dashicons-email-alt2'
          );
          register_post_type('newsletter_sub', $args);
     }
     private function get_user_ip()
     {
          $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');

          foreach ($ip_keys as $key) {
               if (array_key_exists($key, $_SERVER) === true) {
                    foreach (explode(',', $_SERVER[$key]) as $ip) {
                         $ip = trim($ip);
                         if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                              return $ip;
                         }
                    }
               }
          }

          return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown';
     }
}
new Simple_Ajax_Demo();
