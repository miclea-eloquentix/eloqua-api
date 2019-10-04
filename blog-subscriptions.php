<?php
/**
 * Plugin Name: Blog Subscriptions with Eloqua
 * Plugin URI:
 * Description: Use Eloqua to send a notification to subscribers whenever a new blog post in published
 * Version: 1.0
 * Author: Kevin A. Wilson
 * Author URI: http://www.bluecord.org/#kevinawilson
 */

require('blog-subscriptions-options.php');
$settings = new BlogSubscriptionOptions();

// Main function
function inv_send_blog_notification($id, $post) {
    // if ( get_post_meta( $id, 'eloqua_email_sent' ) ) {
    //   return;
    // };

    $options = get_option('blog-subscription');

    $title = $post->post_title;
    $link = get_permalink($post);

    // Retrieve options
    $username = $options['eloqua_username'];
    $password = $options['eloqua_password'];
    $retrieve_email_url = $options['eloqua_retrieve_email_url'] . '/' . $options['eloqua_email_id'];
    $email_header_id = $options['eloqua_email_header_id'];
    $email_footer_id = $options['eloqua_email_footer_id'];
    $create_email_url = $options['eloqua_create_email_url'];
    $create_campaign_url = $options['eloqua_create_campaign_url'];
    $email_name = $options['eloqua_email_name'];
    $email_group_id = $options['eloqua_email_group_id'];
    $email_subject = $options['eloqua_email_subject'];

    $headers = array(
      'Content-Type: application/json',
      'Authorization: Basic '. base64_encode("$username:$password")
    );

    $email_id = inv_create_email($headers, $retrieve_email_url, $create_email_url, $link, $title, $email_name, $email_group_id, $email_subject, $email_header_id, $email_footer_id);

    $campaign = inv_create_eloqua_campaign($headers, $create_campaign_url, $email_id);

    $activate_url = $options['eloqua_activate_campaign_url'] . '/' . $campaign;
    inv_activate_eloqua_campaign($headers, $activate_url);

    add_post_meta($id, 'eloqua_email_sent', true, true);
};

// Retrieve email template, make changes, and create new email from template
function inv_create_email($headers, $retrieve_email_url, $create_email_url, $link, $title, $email_name, $email_group_id, $email_subject, $email_header_id, $email_footer_id) {
    // Retrieve email template

    $ch = curl_init($retrieve_email_url);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $response = json_decode($response, true);
    $htmlContent = $response['htmlContent']['html'];

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
function inv_create_eloqua_campaign($headers, $url, $email_id) {
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
        "name": "Insights Blog Form (8.29.19)",
        "segmentId": "2049",
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
add_action( $post_type, 'inv_send_blog_notification', 10, 2 );

?>
