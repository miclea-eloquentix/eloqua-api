<?php

class EloquaConnector {

    private $options;
    private $headers;
    private $old_headers;

    public function __construct() {
        // Set options
        $this->options = get_option('blog-subscription');

        $username = $this->options['eloqua_username'];
        $password = $this->options['eloqua_password'];

        $this->old_headers = array(
          'Content-Type: application/json',
          'Authorization: Basic '. base64_encode("$username:$password")
        );

        $this->headers = array(
          'headers' => array(
            'Authorization' => 'Basic ' . base64_encode("$username:$password")
          )
        );
    }

    // Retrieve email template, make changes, and create new email from template
    public function create_email($link, $title) {

        // Set options
        $retrieve_email_url = $this->options['eloqua_retrieve_email_url'] . '/' . $this->options['eloqua_email_id'];
        $create_email_url = $this->options['eloqua_create_email_url'];
        $email_name = $this->options['eloqua_email_name'];
        $email_group_id = $this->options['eloqua_email_group_id'];

        if ( $_POST['eloqua_email_subject_line'] != '' ) {
          $email_subject = $_POST['eloqua_email_subject_line'];
        } else {
          $email_subject = $this->options['eloqua_email_subject_line'];
        }

        // Retrieve email template
        $ch = curl_init($retrieve_email_url);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->old_headers);
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

        $new_email_name = $email_name . ' ' . $stamp;

        $new_email = array(
          "name" => $new_email_name,
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

        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->old_headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $email_data);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response === false) {
          $response = json_decode($response, true);
          add_action( 'admin_notices', 'email_success_notice' );
        } else {
          add_action( 'admin_notices', 'email_error_notice' );
        }

        return $response['id'];
    }

    // Create an Eloqua campaign with the new email and relevant segment
    public function create_campaign($email_id) {

        // Set options
        $create_campaign_url = $this->options['eloqua_create_campaign_url'];
        $segment_id = $this->options['eloqua_segment_id'];
        $segment_name = $this->options['eloqua_segment_name'];


        $ch = curl_init($create_campaign_url);

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

        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->old_headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (!$response === false) {
          $response = json_decode($response, true);
        } else {
          add_action( 'admin_notices', 'create_campaign_error_notice' );
        }

        return $response['id'];
    }

    // Activate the new campaign
    public function activate_campaign($campaign_id) {

        // Set options
        $activate_url = $this->options['eloqua_activate_campaign_url'] . '/' . $campaign_id;

        /*
        $ch = curl_init($activate_url);

        $params = '{
          "activateNow": true,
          "scheduledFor": "now"
        }';

        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->old_headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);
        */

        $body = array(
          'activateNow' => true,
          'scheduledFor' => 'now'
        );

        $args = array(
          'body' => $body,
          'timeout' => '5',
          'redirection' => '5',
          'httpversion' => '1.0',
          'blocking' => true,
          'headers' => $this->headers,
          'cookies' => array()
        );

        $response = wp_remote_post($activate_url, $args);

        if (!$response === false) {
          $response = json_decode($response, true);
          add_action( 'admin_notices', 'campaign_success_notice' );
        } else {
          add_action( 'admin_notices', 'activate_campaign_error_notice' );
        }
    }

    // Retrieve a form from Eloqua
    public function retrieve_form($attr) {
      $form_attr = shortcode_atts( array( 'id' => '0' ), $attr );

      $form_url = 'https://secure.p04.eloqua.com/api/REST/2.0/assets/form/' . $form_attr['id'];

      $response = wp_remote_get($form_url, $this->headers);
      $response = json_decode($response['body'], true);

      return $response;
    }

    // Error Messages
    public function email_success_notice() {
      ?>
      <div class="updated notice">
        <h2>Activate Campaign</h2>
        <p>Campaign was activated successfully.</p>
      </div>
      <?php
    }

    public function campaign_success_notice() {
      ?>
      <div class="updated notice">
        <h2>Create Email</h2>
        <p>Email was created successfully.</p>
      </div>
      <?php
    }

    public function email_error_notice() {
      ?>
      <div class="error notice">
        <h2>Create Email Error</h2>
        <p>Unable to create email. Please check the Eloqua dashboard for more details.</p>
      </div>
      <?php
    }

    public function create_campaign_error_notice() {
      ?>
      <div class="error notice">
        <h2>Create Campaign Error</h2>
        <p>Unable to create campaign. Please check the Eloqua dashboard for more details.</p>
      </div>
      <?php
    }

    public function activate_campaign_error_notice() {
      ?>
      <div class="error notice">
        <h2>Activate Campaign Error</h2>
        <p>Unable to activate campaign. Please check the Eloqua dashboard for more details.</p>
      </div>
      <?php
    }

} ?>
