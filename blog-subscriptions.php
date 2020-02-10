<?php
/*
Plugin Name: Blog Subscriptions with Eloqua
Description: Use Eloqua to send a notification to subscribers whenever a new blog post is published
Version: 1.1
Author: Kevin A. Wilson
Author URI: http://www.bluecord.org/#kevinawilson
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


Blog Subscriptions with Eloqua is free software: you can redistribute it or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License or any later version.

Blog Subscriptions with Eloqua is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with Blog Subscriptions with Eloqua. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once('BlogSubscriptionsOptions.php');
require_once('EloquaConnector.php');

$settings = new BlogSubscriptionOptions();


// Main function
function blog_notification($id, $post) {

    if ( !isset( $_POST['send_subscription_email'] ) ) {
      return;
    }

    if ( get_post_meta( $id, 'eloqua_email_sent' ) ) {
      return;
    };

    $connector = new EloquaConnector();

    $title = $post->post_title;
    $link = get_permalink($post);

    $email_id = $connector->create_email($link, $title);

    $campaign = $connector->create_campaign($email_id);

    $connector->activate_campaign($campaign);

    add_post_meta($id, 'eloqua_email_sent', true, true);
};

$options = get_option('blog-subscription');
$post_type = 'publish_' . $options['subscription_post_type'];

add_action( $post_type, 'blog_notification', 99, 2 );

// Add shortcode for form
function display_blog_subscription_form($attr) {

  $connector = new EloquaConnector();
  $response = $connector->retrieve_form($attr);

  $options = get_option('blog-subscription');

  $dom = new DOMDocument();
  $dom->loadHTML($response['html']);
  $scripts = $dom->getElementsByTagName('script');
  $validation_script = $dom->saveHTML($scripts[1]);
  $scripts[1]->parentNode->removeChild($scripts[1]);

  preg_match('/(\bfunction handleFormSubmit\b)[\s\S]*?\}/', $validation_script, $removed_script);

  $domains = explode(',', $options['invalid_domains']);
  $invalidDomainsRedirect = $options['invalid_domains_redirect'];

  $posSubmit = strpos($removed_script[0], '{');
  $posRemainder = strpos($validation_script, '}');

  $extra_script = '<script>
    var validAddress = true;

    jQuery( document ).ready(function() {

      jQuery("input.elq-item-input").each(function (index, value){
        jQuery(this).attr("value", "");
      });

      jQuery("input[name=\'emailAddress\']").blur(function() {
        domains = ' . json_encode($domains) . ';
        email = jQuery(this).val();
        atIndex = email.indexOf("@");

        if(atIndex > -1) {
          emailDomain = email.split("@");
          if(domains.indexOf(emailDomain[1])>-1) {
            validAddress = false;
          };
        };
      });
    });

    function handleFormSubmit(ele) {
      if(validAddress) {
        ' . substr($removed_script[0], $posSubmit + 1) . '
      else {
        window.location.href = "' . $invalidDomainsRedirect . '";
        return false;
      }
    }' . substr($validation_script, $posRemainder + 1) . '

    </script>';

  return $dom->saveHTML() . $extra_script;
};

add_shortcode('blog-subscription-form', 'display_blog_subscription_form');

// Add meta box for email subject line to selected post type
function eloqua_subscription_meta_box() {
    $options = get_option('blog-subscription');

    if ( isset( $options['subscription_post_type'] ) ) {
      add_meta_box( 'eloqua_email_subject_line', __( 'Subscription Email Subject Line', 'textdomain' ), 'eloqua_subscription_meta_box_callback', $options['subscription_post_type'] );

      add_meta_box( 'send_subscription_email', __( 'Send Subscription Email?', 'textdomain' ), 'send_subscription_email_callback', $options['subscription_post_type'], 'side' );
    }
  }

function eloqua_subscription_meta_box_callback($post, $metabox) {
    wp_nonce_field( 'eloqua_email_subject_line_nonce', 'eloqua_email_subject_line_nonce' );
    echo '<input type="text" style="width:100%" id="eloqua_email_subject_line" name="eloqua_email_subject_line" value="' . esc_attr( get_post_meta( $post->ID, 'eloqua_email_subject_line', true ) ) . '">';
}

function send_subscription_email_callback($post, $metabox) {
    if ( get_post_meta( $post->ID, 'send_subscription_email', true ) ) {
      $checked = "checked";
    } else {
      $checked = "";
    }

    echo '<input type="checkbox" id="send_subscription_email" name="send_subscription_email" value="yes" ' . $checked . '><label for="send_subscription_email">Select to send subscription notification</label>';
}

add_action( 'add_meta_boxes', 'eloqua_subscription_meta_box' );

function save_subscription_meta( $post_id ) {

    // Check if nonce is set and valid
    if ( ! isset( $_POST['eloqua_email_subject_line_nonce'] ) ) {
        return;
    }

    if ( ! wp_verify_nonce( $_POST['eloqua_email_subject_line_nonce'], 'eloqua_email_subject_line_nonce' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( isset( $_POST['eloqua_email_subject_line'] ) ) {
        $subject_line = sanitize_text_field( $_POST['eloqua_email_subject_line'] );
        update_post_meta( $post_id, 'eloqua_email_subject_line', $subject_line );
    }

    if ( isset( $_POST['send_subscription_email'] ) ) {
        update_post_meta( $post_id, 'send_subscription_email', true );
    } else {
        update_post_meta( $post_id, 'send_subscription_email', false );
    }
}

add_action( 'save_post', 'save_subscription_meta' );

?>
