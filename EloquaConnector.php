<?php

class EloquaConnector {

    private $username;
    private $password;
    private $retrieve_email_url;
    private $create_email_url;
    private $create_campaign_url;
    private $activate_url;
    private $email_name;
    private $email_group_id;
    private $segment_id;
    private $segment_name;
    private $headers;
    private $email_subject;

public function __constructor() {
    $options = get_option('blog-subscription');

    // Retrieve options
    $this->username = $options['eloqua_username'];
    $this->password = $options['eloqua_password'];
    $this->retrieve_email_url = $options['eloqua_retrieve_email_url'] . '/' . $options['eloqua_email_id'];
    $this->create_email_url = $options['eloqua_create_email_url'];
    $this->create_campaign_url = $options['eloqua_create_campaign_url'];
    $this->email_name = $options['eloqua_email_name'];
    $this->email_group_id = $options['eloqua_email_group_id'];
    $this->segment_id = $options['eloqua_segment_id'];
    $this->segment_name = $options['eloqua_segment_name'];
    $this->activate_url = $options['eloqua_activate_campaign_url'];

    if ( $_POST['eloqua_email_subject_line'] != '' ) {
      $this->email_subject = $_POST['eloqua_email_subject_line'];
    } else {
      $this->email_subject = $options['eloqua_email_subject_line'];
    }

    $this->headers = array(
      'Content-Type: application/json',
      'Authorization: Basic '. base64_encode("$username:$password")
    );
}

// Retrieve email template, make changes, and create new email from template
public function create_email($link, $title) {

    // Retrieve email template
    $ch = curl_init($this->retrieve_email_url);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $response = json_decode($response, true);
    $htmlContent = $response['htmlContent']['html'];
    $email_header_id = $response['emailHeaderId'];
    $email_footer_id = $response['emailFooterId'];

    // Replace link and title in email
    $htmlContent = str_replace('[[[title]]]', $title, $htmlContent);
    $htmlContent = str_replace('[[[link]]]', $link, $htmlContent);

    curl_close($ch);

    // Create new email
    $ch = curl_init($create_email_url);

    $t = time();
    $d = date("Y-m-d",$t);
    $stamp = ' ' . $t . ' ' . $d;

    $new_email_name = $this->email_name . ' ' . $stamp;

    $new_email = array(
      "name" => $new_email_name,
      "emailGroupId" => $this->email_group_id,
      "emailHeaderId" => $email_header_id,
      "emailFooterId" => $email_footer_id,
      "subject" => $this->email_subject,
      "htmlContent" => array(
          "type" => "RawHtmlContent",
          "html" => $htmlContent
      )
    );

    $email_data = json_encode($new_email);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $email_data);

    $response = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($response, true);
    return $response['id'];
}

// Create an Eloqua campaign with the new email and relevant segment
public function create_eloqua_campaign($email_id) {
    $ch = curl_init($this->create_campaign_url);

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
        "name": "'. $this->segment_name . '",
        "segmentId": "'. $this->segment_id . '",
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

    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $response = json_decode($response, true);

    return $response['id'];
}

// Activate the new campaign
public function activate_eloqua_campaign($campaign_id) {
    $activate_url_campaign = $this->activate_url . '/' . $campaign;
    $ch = curl_init($activate_url_campaign);

    $params = '{
      "activateNow": true,
      "scheduledFor": "now"
    }';

    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);
}

// Add shortcode for form
public function retrieve_blog_subscription_form($attr) {
  $form_attr = shortcode_atts( array( 'id' => '0' ), $attr );

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

  return $response;
}

// Add meta box for email subject line to selected post type
public function eloqua_subscription_meta_box() {
    $options = get_option('blog-subscription');

    if ( isset( $options['subscription_post_type'] ) ) {
      add_meta_box( 'eloqua_email_subject_line', __( 'Subscription Email Subject Line', 'textdomain' ), 'eloqua_subscription_meta_box_callback', $options['subscription_post_type'] );

      add_meta_box( 'send_subscription_email', __( 'Send Subscription Email?', 'textdomain' ), 'send_subscription_email_callback', $options['subscription_post_type'], 'side' );
    }
  }

public function eloqua_subscription_meta_box_callback($post, $metabox) {
    wp_nonce_field( 'eloqua_email_subject_line_nonce', 'eloqua_email_subject_line_nonce' );
    echo '<input type="text" style="width:100%" id="eloqua_email_subject_line" name="eloqua_email_subject_line" value="' . esc_attr( get_post_meta( $post->ID, 'eloqua_email_subject_line', true ) ) . '">';
}

public function send_subscription_email_callback($post, $metabox) {
    if ( get_post_meta( $post->ID, 'send_subscription_email', true ) ) {
      $checked = "checked";
    } else {
      $checked = "";
    }

    echo '<input type="checkbox" id="send_subscription_email" name="send_subscription_email" value="yes" ' . $checked . '><label for="send_subscription_email">Select to send subscription notification</label>';
}

public function save_subscription_meta( $post_id ) {

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

}
?>
