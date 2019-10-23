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

// Add shortcode for form
function display_blog_subscription_form($attr) {
  $form_attr = shortcode_atts( array(
   'id' => '0'
 ), $attr );

 $domains = array("3m.com","ablehealth.com","acupera.com","advancehlth.com","advantmed.com","adventadvisorygroup.com","advisory.com","aeaallc.com","aimspecialtyhealth.com","ainq.com","alegiscare.com","allscripts.com","altegrahealth.com","amerigroup.com","amerigroupcorp.com","anthem.com","anthemwc.com","anthemww.com","apixio.com","arcadiasolutions.com","argushealth.com","arrohealth.com","aspirehealthcare.com","assistrx.com","athenahealth.com","attacconsulting.com","availity.com","aviacode.com","bactes.com","basehealth.com","bcbsga.com","beammed.com","bestdoctors.com","billdunbar.com","bipc.com","bluehealthintelligence.com","bracketglobal.com","business.att.com","callcarenet.com","caradigm.com","careallies.com","carecentrix.com","caremetx.com","caremore.com","carenethealthcare.com","casenetllc.com","censeohealth.com","centaurihs.com","cernerhealth.com","changehealthcare.com","citiustech.com","citrahealth.com","clearviewhcp.com","clinigence.com","clrviewsolutions.com","cogitocorp.com","cognisight.com","comcast.net","coniferhealth.com","connecture.com","connectyourcare.com","consumerdirectonline.net","contextmatters.com","conveyhs.com","coordinatedcarehealth.com","corepointhealth.com","corp.cozeva.com","cotiviti.com","cozeva.com","cvinfosys.com","datalinksoftware.com","datstat.com","dchstx.org","ddds.com","decare.com","definitivehc.com","drane.me","drfirst.com","dsthealthsolutions.com","dstsystems.com","dynamichealthsys.com","e-imo.com","eclinicalworks.com","edgpartners.com","edifecs.com","ehealthinsurance.com","elizacorp.com","elsevier.com","empireblue.com","emsinet.com","enjoincdi.com","enli.net","enrollamerica.org","episource.com","evicore.com","evolenthealth.com","eyemed.com","forwardhealthgroup.com","fpdi.org","frgsystems.com","gafoods.com","gmail.com","gmsconnect.com","go.extensionhealthcare.com","gobalto.com","goldenwestdental.com","halfpenny.com","hallmarkbusinessconnections.com","hdms.com","healthagen.com","healthcareanalytics.expert","healthcareitleaders.com","healthcatalyst.com","healthcore.com","healthdatavision.com","healthendeavors.com","healthequity.com","healthfair.com","healthfidelity.com","healthlink.com","healthmonix.com","healthport.com","healthscape.com","healthscapeadvisors.com","healthx.com","hidesigns.com","himexperts.com","hmkbc.com","hms.com","homeaccess.com","hotmail.com","hp.com","hspweb.com","humanarc.com","i2ipophealth.com","ibm.com","ica-carealign.com","indegene.com","innovaccer.com","integraserviceconnect.com","interpreta.com","intersystems.com","ionhc.com","ivedix.com","jacobsononline.com","judge.com","kepro.com","lightbeamhealth.com","liquidhub.com","lumeris.com","lumiata.com","lumiradx.com","m2econ.com","madakethealth.com","Magellandx.com","marwoodgroup.com","matrixhealth.net","maxhealth.com","mckesson.com","mdv.com","MDXNET.COM","medeanalytics.com","medecision.com","medhok.com","medicalis.com","medivo.com","mediware.com","medxm1.com","meridianresource.com","milliman.com","mitre.org","mmm.com","motivemi.com","mrocorp.com","nagnoi.com","nammcal.com","navigant.com","neodeckholdings.com","neurometrix.com","ngsservices.com","novu.com","nuance.com","opayq.com","optimityadvisors.com","optum.com","optumrx.com","oracle.com","os2healthcaresolutions.com","peakras.com","pharmmd.com","pilotfish.eu","pointright.com","pophealthcare.com","ppmsi.com","practicefusion.com","predilytics.com","premierinc.com","prometrics.com","public.zirmed.com","pulse8.com","pyapc.com","q-centrix.com","quantiphi.com","questanalytics.com","relayhealth.com","resolutionhealth.com","risehealth.org","riskadjustmentconsulting.com","rsamedical.com","rxante.com","rxbenefits.com","rxreviewllc.com","saludrevenue.com","sas.com","scanhealthplan.com","sdlcpartners.com","selectdata.com","semlerscientific.com","shyftanalytics.com","signaturemedicalgroup.com","silverback-cm.com","simplyhealthcareplans.com","sphanalytics.com","ssigroup.com","strenuus.com","synapticap.com","talix.com","telemedik.com","telligen.com","themisanalytics.com","thinkbrg.com","tmghealth.com","tridentcap.com","triple-tree.com","trizetto.com","truvenhealth.com","ultimatemedical.edu","unicare.com","us-rxcare.com","usmd.com","usmmllc.com","valuecentric.com","vaticahealth.com","vecna.com","verisk.com","veriskhealth.com","verisys.com","verizon.net","verscend.com","visionaryrcm.com","VSP.com","vynecorp.com","wellcentive.com","wellpoint.com","whiteglove.com","wilmingtonplc.com","xby2.com","yahoo.com","zapprx.com","zeomega.com","zephyrhealth.com","zoomrx.com");

  $form_url = 'https://secure.p04.eloqua.com/api/REST/2.0/assets/form/'
  . $form_attr['id'];
  $ch = curl_init($form_url);

  $options = get_option('blog-subscription');

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


  $posSubmit = strpos($removed_script[0], '{');
  $posRemainder = strpos($validation_script, '}');

  $extra_script = '<script>
    var validAddress = true;

    $( document ).ready(function() {

      $("input.elq-item-input").each(function (index, value){
        $(this).attr("value", "");
      });

      $("input[name=\'emailAddress\']").blur(function() {
        domains = ' . json_encode($domains) . ';
        email = $(this).val();
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
        window.location.href = "https://webdev.inovalon.com/form-test-invalid/";
        return false;
      }
    }' . substr($validation_script, $posRemainder + 1) . '


    </script>';

  return $dom->saveHTML() . $extra_script;
};

add_shortcode('blog-subscription-form', 'display_blog_subscription_form');
?>
