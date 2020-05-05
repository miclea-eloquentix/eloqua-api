<?php
/*
Plugin Name: Blog Subscriptions with Eloqua
Description: Use Eloqua to send a notification to subscribers whenever a new blog post is published
Version: 1.0
Author: Kevin A. Wilson
Author URI: http://www.bluecord.org/#kevinawilson
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


Blog Subscriptions with Eloqua is free software: you can redistribute it or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License or any later version.

Blog Subscriptions with Eloqua is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with Blog Subscriptions with Eloqua. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

require('blog-subscriptions-options.php');
$settings = new BlogSubscriptionOptions();

// Main function
function inv_send_blog_notification($id, $post) {

    if ( !isset( $_POST['send_subscription_email'] ) ) {
      return;
    }

    if ( get_post_meta( $id, 'eloqua_email_sent' ) ) {
      return;
    };

    $options = get_option('blog-subscription');

    $title = $post->post_title;
    $link = get_permalink($post);

    // Retrieve options
    $username = $options['eloqua_username'];
    $password = $options['eloqua_password'];
    $retrieve_email_url = $options['eloqua_retrieve_email_url'] . '/' . $options['eloqua_email_id'];
    $create_email_url = $options['eloqua_create_email_url'];
    $create_campaign_url = $options['eloqua_create_campaign_url'];
    $email_name = $options['eloqua_email_name'];
    $email_group_id = $options['eloqua_email_group_id'];
    $segment_id = $options['eloqua_segment_id'];
    $segment_name = $options['eloqua_segment_name'];

    if ( $_POST['eloqua_email_subject_line'] != '' ) {
      $email_subject = $_POST['eloqua_email_subject_line'];
    } else {
      $email_subject = $options['eloqua_email_subject_line'];
    }

    $headers = array(
      'Content-Type: application/json',
      'Authorization: Basic '. base64_encode("$username:$password")
    );

    $email_id = inv_create_email($headers, $retrieve_email_url, $create_email_url, $link, $title, $email_name, $email_group_id, $email_subject);

    $campaign = inv_create_eloqua_campaign($headers, $create_campaign_url, $email_id, $segment_id, $segment_name);

    $activate_url = $options['eloqua_activate_campaign_url'] . '/' . $campaign;
    inv_activate_eloqua_campaign($headers, $activate_url);

    add_post_meta($id, 'eloqua_email_sent', true, true);
};

// Retrieve email template, make changes, and create new email from template
function inv_create_email($headers, $retrieve_email_url, $create_email_url, $link, $title, $email_name, $email_group_id, $email_subject) {
    // Retrieve email template

    $ch = curl_init($retrieve_email_url);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $response = json_decode($response, true);
    $htmlContent = $response['htmlContent']['html'];
    $email_header_id = $response['emailHeaderId'];
    $email_footer_id = $response['emailFooterId'];

    // Add replace link and title in email
    $htmlContent = str_replace('[[[title]]]', $title, $htmlContent);
    $htmlContent = str_replace('[[[link]]]', $link, $htmlContent);

    curl_close($ch);

    // Create new email
    $ch = curl_init($create_email_url);

    $t = time();
    $d = date("Y-m-d",$t);
    $stamp = ' ' . $t . ' ' . $d;

    $email_name = $email_name . ' ' . $stamp;

    $new_email = array(
      "name" => $email_name,
      "emailGroupId" => $email_group_id,
      "emailHeaderId" => $email_header_id,
      "emailFooterId" => $email_footer_id,
      "subject" => $email_subject,
      "htmlContent" => array(
          "type" => "RawHtmlContent",
          "html" => $htmlContent
      )
    );

    $email_data = json_encode($new_email);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $email_data);

    $response = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($response, true);
    return $response['id'];
};

// Create an Eloqua campaign with the new email and relevant segment
function inv_create_eloqua_campaign($headers, $url, $email_id, $segment_id, $segment_name) {
    $ch = curl_init($url);

    $t = time();
    $d = date("Y-m-d",$t);
    $stamp = ' ' . $t . ' ' . $d;
    $endAt = $t + (7 * 24 * 60 * 60);

    $json_body = '{
      "name": "Inovalon Blog Subscription Campaign ' . $stamp . '",
      "endAt": ' . $endAt . ',
      "elements": [{
        "type": "CampaignSegment",
        "id": "-1",
        "name": "'. $segment_name . '",
        "segmentId": "'. $segment_id . '",
        "position": {
          "type": "Position",
          "x": "100",
          "y": "100"
        },
        "outputTerminals": [{
          "type": "CampaignOutputTerminal",
          "connectedId": "-2",
          "connectedType": "CampaignEmail",
          "terminalType": "out"
          }]
        },
        {
          "type": "CampaignEmail",
          "emailId": "' . $email_id .'",
          "id": "-2",
          "position": {
            "type": "Position",
            "x": "100",
            "y": "200"
          }
        }
      ]
    }';

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $response = json_decode($response, true);

    return $response['id'];
};

// Activate the new campaign
function inv_activate_eloqua_campaign($headers, $url) {
    $ch = curl_init($url);

    $params = '{
      "activateNow": true,
      "scheduledFor": "now"
    }';

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);
};

$options = get_option('blog-subscription');
$post_type = 'publish_' . $options['subscription_post_type'];

add_action( $post_type, 'inv_send_blog_notification', 99, 2 );

// Add shortcode for form
function display_blog_subscription_form($attr) {
  $form_attr = shortcode_atts( array(
   'id' => '0'
  ), $attr );

  $form_url = 'https://secure.p04.eloqua.com/api/REST/2.0/assets/form/' . $form_attr['id'];
  $ch = curl_init($form_url);

  $options = get_option('blog-subscription');

  $domains = explode(',', $options['invalid_domains']);

  $username = $options['eloqua_username'];
  $password = $options['eloqua_password'];

  $headers = array(
    'Content-Type: application/json',
    'Authorization: Basic '. base64_encode("$username:$password")
  );

  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  $response = curl_exec($ch);
  $response = json_decode($response, true);
  //curl_close($ch);

  $dom = new DOMDocument();
  $dom->loadHTML($response['html']);

  $scripts = $dom->getElementsByTagName('script');
  $validation_script = $dom->saveHTML($scripts[1]);
  $scripts[1]->parentNode->removeChild($scripts[1]);

  preg_match('/(\bfunction handleFormSubmit\b)[\s\S]*?\}/', $validation_script, $removed_script);

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

  if ($options['include_css']) {
    $domCSS = new DOMDocument();
    $domCSS->loadHTML($response['customCSS']);
    $style = $domCSS->getElementsByTagName('style');
    $extra_script = $extra_script . $domCSS->saveHTML($style[0]);
  };

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
