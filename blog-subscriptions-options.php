<?php
class BlogSubscriptionOptions {

    private $options;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
    }

    public function add_plugin_page() {
        //This page will be under "Settings"
        add_options_page(
          'Settings Admin',
          'Blog Subscriptions',
          'manage_options',
          'blog-subscription-setting-admin',
          array($this, 'create_admin_page')
        );
    }

    public function create_admin_page() {
        $this->options = get_option('blog-subscription');

        // If new values submitted, save them in the database
        if (isset($_POST[ "options_submitted" ]) && $_POST[ "options_submitted" ] == 'Y') {
            foreach ($_POST['blog-subscription'] as $key => $value) {
                $this->options[$key] = stripslashes($value);
            }

            if ( !isset($_POST['blog-subscription']['include_css']) ) {
              $this->options['include_css'] = 0;
            }

            update_option('blog-subscription', $this->options); ?>
            <div class="updated"><p><strong>Settings saved</strong></p></div>
        <?php } ?>

        <div class="wrap">
          <h1>Blog Subscription Settings</h1>
        <form method="post" action="<?php the_permalink(); ?>">
          <input type="hidden" name="options_submitted" value="Y">
          <?php
          settings_fields('blog-subscription-group');
          do_settings_sections('blog-subscription-setting-admin');
          submit_button(); ?>
        </form>
      </div>
    <?php }

    // Register and add settings
    public function page_init() {
      register_setting(
        'blog-subscription-group', // Option group
        'blog-subscription', // Option name
        array($this, 'sanitize') // Sanitize
      );

      add_settings_section(
        'eloqua-user', // ID
        'Eloqua Authentication', // Title
        array($this, 'print_eloqua_authentication_info'), // Callback
        'blog-subscription-setting-admin' // Page
      );

      add_settings_field(
        'eloqua_username', // ID
        'Eloqua Username', // Title
        array($this, 'eloqua_username_callback'), // Callback
        'blog-subscription-setting-admin', // Page
        'eloqua-user' // Section
      );

      add_settings_field(
        'eloqua_password',
        'Eloqua Password',
        array($this, 'eloqua_password_callback'),
        'blog-subscription-setting-admin',
        'eloqua-user'
      );

      add_settings_section(
        'eloqua-campaign',
        'Eloqua Campaign Information',
        array($this, 'print_eloqua_campaign_info'),
        'blog-subscription-setting-admin'
      );

      add_settings_field(
        'eloqua_email_id',
        'Eloqua Email ID',
        array($this, 'eloqua_email_id_callback'),
        'blog-subscription-setting-admin',
        'eloqua-campaign'
      );

      add_settings_field(
        'eloqua_email_group_id',
        'Eloqua Email Group ID',
        array($this, 'eloqua_email_group_id_callback'),
        'blog-subscription-setting-admin',
        'eloqua-campaign'
      );

      add_settings_field(
        'eloqua_campaign_name',
        'Eloqua Campaign Name',
        array($this, 'eloqua_campaign_name_callback'),
        'blog-subscription-setting-admin',
        'eloqua-campaign'
      );

      add_settings_field(
        'eloqua_email_name',
        'Eloqua Email Name',
        array($this, 'eloqua_email_name_callback'),
        'blog-subscription-setting-admin',
        'eloqua-campaign'
      );

      add_settings_field(
        'eloqua_email_subject_line',
        'Eloqua Email Default Subject Line',
        array($this, 'eloqua_email_subject_line_callback'),
        'blog-subscription-setting-admin',
        'eloqua-campaign'
      );

      add_settings_field(
        'eloqua_form_id',
        'Eloqua Subscription Form ID',
        array($this, 'eloqua_form_id_callback'),
        'blog-subscription-setting-admin',
        'eloqua-campaign'
      );

      add_settings_field(
        'eloqua_segment_name',
        'Eloqua Segment Name',
        array($this, 'eloqua_segment_name_callback'),
        'blog-subscription-setting-admin',
        'eloqua-campaign'
      );

      add_settings_field(
        'eloqua_segment_id',
        'Eloqua Email Segment ID',
        array($this, 'eloqua_segment_id_callback'),
        'blog-subscription-setting-admin',
        'eloqua-campaign'
      );

      add_settings_section(
        'eloqua-urls',
        'Eloqua Endpoints',
        array($this, 'print_eloqua_endpoints_info'),
        'blog-subscription-setting-admin'
      );

      add_settings_field(
        'eloqua_retrieve_email_url',
        'Eloqua Retrieve Email URL',
        array($this, 'eloqua_retrieve_email_url_callback'),
        'blog-subscription-setting-admin',
        'eloqua-urls'
      );

      add_settings_field(
        'eloqua_create_email_url',
        'Eloqua Create Email URL',
        array($this, 'eloqua_create_email_url_callback'),
        'blog-subscription-setting-admin',
        'eloqua-urls'
      );

      add_settings_field(
        'eloqua_create_campaign_url',
        'Eloqua Create Campaign URL',
        array($this, 'eloqua_create_campaign_url_callback'),
        'blog-subscription-setting-admin',
        'eloqua-urls'
      );

      add_settings_field(
        'eloqua_activate_campaign_url',
        'Eloqua Activate Campaign URL',
        array($this, 'eloqua_activate_campaign_url_callback'),
        'blog-subscription-setting-admin',
        'eloqua-urls'
      );

      add_settings_section(
        'subscription_post_type_section',
        'Subscription Post Type',
        array($this, 'print_subscription_post_type_info'),
        'blog-subscription-setting-admin'
      );

      add_settings_field(
        'subscription_post_type',
        'Post Type',
        array($this, 'subscription_post_type_callback'),
        'blog-subscription-setting-admin',
        'subscription_post_type_section'
      );

      add_settings_field(
        'include_css',
        'Check to Include Custom CSS from Eloqua',
        array($this, 'include_css_callback'),
        'blog-subscription-setting-admin',
        'subscription_post_type_section'
      );

      add_settings_section(
        'invalid_domains_section',
        'Invalid Email Domains',
        array($this, 'print_invalid_domains_info'),
        'blog-subscription-setting-admin'
      );

      add_settings_field(
        'invalid_domains',
        'Invalid Domains',
        array($this, 'invalid_domains_callback'),
        'blog-subscription-setting-admin',
        'invalid_domains_section'
      );

      add_settings_field(
        'invalid_domains_redirect',
        'Page to which Invalid Domains Should Be Redirected',
        array($this, 'invalid_domains_redirect_callback'),
        'blog-subscription-setting-admin',
        'invalid_domains_section'
      );
    }

    /**
    * Sanitize each setting field as needed
    *
    * @param array $input Contains all settings fields as array keys
    */

    public function sanitize($input) {
        $new_input = array();

        if (isset($input['eloqua_username'])) {
            $new_input['eloqua_username'] = sanitize_text_field($input['eloqua_username']);
        }

        if (isset($input['eloqua_password'])) {
            $new_input['eloqua_password'] = sanitize_text_field($input['eloqua_password']);
        }

        if (isset($input['eloqua_email_id'])) {
            $new_input['eloqua_email_id'] = intval($input['eloqua_email_id']);
        }

        if (isset($input['eloqua_email_group_id'])) {
            $new_input['eloqua_email_group_id'] = intval($input['eloqua_email_group_id']);
        }

        if (isset($input['eloqua_campaign_name'])) {
            $new_input['eloqua_campaign_name'] = sanitize_text_field($input['eloqua_campaign_name']);
        }

        if (isset($input['eloqua_email_name'])) {
            $new_input['eloqua_email_name'] = sanitize_text_field($input['eloqua_email_name']);
        }

        if (isset($input['eloqua_email_subject_line'])) {
            $new_input['eloqua_email_subject_line'] = sanitize_text_field($input['eloqua_email_subject_line']);
        }

        if (isset($input['eloqua_form_id'])) {
            $new_input['eloqua_form_id'] = sanitize_text_field($input['eloqua_form_id']);
        }

        if (isset($input['eloqua_segment_name'])) {
            $new_input['eloqua_segment_name'] = sanitize_text_field($input['eloqua_segment_name']);
        }

        if (isset($input['eloqua_segment_id'])) {
            $new_input['eloqua_segment_id'] = sanitize_text_field($input['eloqua_segment_id']);
        }

        if (isset($input['eloqua_retrieve_email_url'])) {
            $new_input['eloqua_retrieve_email_url'] = sanitize_text_field($input['eloqua_retrieve_email_url']);
        }

        if (isset($input['eloqua_create_email_url'])) {
            $new_input['eloqua_create_email_url'] = sanitize_text_field($input['eloqua_create_email_url']);
        }

        if (isset($input['eloqua_create_campaign_url'])) {
            $new_input['eloqua_create_campaign_url'] = sanitize_text_field($input['eloqua_create_campaign_url']);
        }

        if (isset($input['eloqua_activate_campaign_url'])) {
            $new_input['eloqua_activate_campaign_url'] = sanitize_text_field($input['eloqua_activate_campaign_url']);
        }

        if (isset($input['subscription_post_type'])) {
            $new_input['subscription_post_type'] = sanitize_text_field($input['subscription_post_type']);
        }

        if (isset($input['include_css'])) {
            $new_input['include_css'] = trim($input['include_css']);
        }

        if (isset($input['invalid_domains'])) {
            $new_input['invalid_domains'] = sanitize_text_field($input['invalid_domains']);
        }

        if (isset($input['invalid_domains_redirect'])) {
            $new_input['invalid_domains_redirect'] = sanitize_text_field($input['invalid_domains_redirect']);
        }

        return $new_input;
    }

    // Print the Section text
    public function print_eloqua_authentication_info() {
        print 'Add the username and password for Eloqua.';
    }

    public function print_eloqua_campaign_info() {
        print 'Add the campaign details.';
    }

    public function print_eloqua_endpoints_info() {
        print "Enter the URLs for the appropriate endpoints. These URLs will consist of your company's base URL plus the endpoint locations.<br/>Please ensure that you put https:// at the beginning of the URL. Do not put a / at the end.";
    }

    public function print_subscription_post_type_info() {
        print 'Select the post type for which notifications will be sent.';
    }

    public function print_invalid_domains_info() {
        print 'Enter a comma separated list of invalid email domains that should not be submitted to Eloqua. The commas separating the domains should not have a space on either side. Do not put quotation marks around the domains. Also enter the URL to which invalid submissions will be sent. Enter the full URL, including https://.';
    }

    // Callbacks to format the form
    public function eloqua_username_callback() {
        printf(
          '<input type="text" id="eloqua_username" name="blog-subscription[eloqua_username]" value="%s"/>',
          isset($this->options['eloqua_username']) ? esc_attr($this->options['eloqua_username']) : ''
        );
    }

    public function eloqua_password_callback() {
        printf(
          '<input type="text" id="eloqua_password" name="blog-subscription[eloqua_password]" value="%s"/>',
          isset($this->options['eloqua_password']) ? esc_attr($this->options['eloqua_password']) : ''
        );
    }

    public function eloqua_email_id_callback() {
        printf(
          '<input type="text" id="eloqua_email_id" name="blog-subscription[eloqua_email_id]" value="%s"/>',
          isset($this->options['eloqua_email_id']) ? esc_attr($this->options['eloqua_email_id']) : ''
        );
    }

    public function eloqua_email_group_id_callback() {
        printf(
          '<input type="text" id="eloqua_email_group_id" name="blog-subscription[eloqua_email_group_id]" value="%s"/>',
          isset($this->options['eloqua_email_group_id']) ? esc_attr($this->options['eloqua_email_group_id']) : ''
        );
    }

    public function eloqua_campaign_name_callback() {
        printf(
          '<input type="text" id="eloqua_campaign_name" name="blog-subscription[eloqua_campaign_name]" value="%s"/>',
          isset($this->options['eloqua_campaign_name']) ? esc_attr($this->options['eloqua_campaign_name']) : ''
        );
    }

    public function eloqua_email_name_callback() {
        printf(
          '<input type="text" id="eloqua_email_name" name="blog-subscription[eloqua_email_name]" value="%s"/>',
          isset($this->options['eloqua_email_name']) ? esc_attr($this->options['eloqua_email_name']) : ''
        );
    }

    public function eloqua_email_subject_line_callback() {
        printf(
          '<input type="text" id="eloqua_email_subject_line" name="blog-subscription[eloqua_email_subject_line]" value="%s"/>',
          isset($this->options['eloqua_email_subject_line']) ? esc_attr($this->options['eloqua_email_subject_line']) : ''
        );
    }

    public function eloqua_form_id_callback() {
        printf(
          '<input type="text" id="eloqua_form_id" name="blog-subscription[eloqua_form_id]" value="%s"/>',
          isset($this->options['eloqua_form_id']) ? esc_attr($this->options['eloqua_form_id']) : ''
        );
    }

    public function eloqua_segment_name_callback() {
        printf(
          '<input type="text" id="eloqua_segment_name" name="blog-subscription[eloqua_segment_name]" value="%s"/>',
          isset($this->options['eloqua_segment_name']) ? esc_attr($this->options['eloqua_segment_name']) : ''
        );
    }

    public function eloqua_segment_id_callback() {
        printf(
          '<input type="text" id="eloqua_segment_id" name="blog-subscription[eloqua_segment_id]" value="%s"/>',
          isset($this->options['eloqua_segment_id']) ? esc_attr($this->options['eloqua_segment_id']) : ''
        );
    }

    public function eloqua_retrieve_email_url_callback() {
        printf(
          '<input type="text" id="eloqua_retrieve_email_url" name="blog-subscription[eloqua_retrieve_email_url]" value="%s" size="50"/>',
          isset($this->options['eloqua_retrieve_email_url']) ? esc_attr($this->options['eloqua_retrieve_email_url']) : ''
        );
    }

    public function eloqua_create_email_url_callback() {
        printf(
          '<input type="text" id="eloqua_create_email_url" name="blog-subscription[eloqua_create_email_url]" value="%s" size="50"/>',
          isset($this->options['eloqua_create_email_url']) ? esc_attr($this->options['eloqua_create_email_url']) : ''
        );
    }

    public function eloqua_create_campaign_url_callback() {
        printf(
          '<input type="text" id="eloqua_create_campaign_url" name="blog-subscription[eloqua_create_campaign_url]" value="%s" size="50"/>',
          isset($this->options['eloqua_create_campaign_url']) ? esc_attr($this->options['eloqua_create_campaign_url']) : ''
        );
    }

    public function eloqua_activate_campaign_url_callback() {
        printf(
          '<input type="text" id="eloqua_activate_campaign_url" name="blog-subscription[eloqua_activate_campaign_url]" value="%s" size="50"/>',
          isset($this->options['eloqua_activate_campaign_url']) ? esc_attr($this->options['eloqua_activate_campaign_url']) : ''
        );
    }

    public function subscription_post_type_callback() {
        $post_types = get_post_types( array('public' => true) );
        $selector_options = '';

        foreach ($post_types as $type) {
          $selector_options .= '<option value="' . $type . '" ';
          if ($type == $this->options['subscription_post_type']) {
            $selector_options .= 'selected';
          };
          $selector_options .= '>' . $type .'</option>';
        }

        printf(
          '<select name="blog-subscription[subscription_post_type]">' . $selector_options .'</select>'
        );
    }

    public function include_css_callback() {
        $includeCSS = ( isset($this->options['include_css']) && $this->options['include_css'] == 1) ? 1 : 0;

        printf(
          '<input type="checkbox" id="include_css" name="blog-subscription[include_css]" value="1" %s>', checked(1, $includeCSS, false)
        );
    }

    public function invalid_domains_callback() {
        if(isset($this->options['invalid_domains'])) {
          $invalidDomainsValue = esc_attr($this->options['invalid_domains']);
        } else {
          $invalidDomainsValue = "";
        };

        printf(
          '<textarea rows="20" cols="100" id="invalid_domains" name="blog-subscription[invalid_domains]" value="%s">' . $invalidDomainsValue . '</textarea>',
          isset($this->options['invalid_domains']) ? esc_attr($this->options['invalid_domains']) : ''
        );
    }

    public function invalid_domains_redirect_callback() {
        printf(
          '<input type="text" id="invalid_domains_redirect" name="blog-subscription[invalid_domains_redirect]" value="%s" size="50"/>',
          isset($this->options['invalid_domains_redirect']) ? esc_attr($this->options['invalid_domains_redirect']) : ''
        );
    }

}
