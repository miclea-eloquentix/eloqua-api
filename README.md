# Blog Subscriptions for Eloqua

This plugin for WordPress connects to Eloqua to send notification emails to subscribers when a new blog (or other custom post type) is published. Please note: You must have an Eloqua account for this plugin to work.

## Installation

Upload the blog-subscriptions.zip file and activate the plugin.

## Setup in Eloqua

An Eloqua administrator will need to create three assets in Eloqua.

The first is the email segment that will be used to store the email addresses of people who subscribe. The ID and name of this segment will need to be entered in the plugin setting screen in WordPress.

The second is the form that will feed registrants to the segment. This form will be retrieved by the plugin and added to the appropriate WordPress pages. The ID of this form is used in the shortcode that is used to place the form on a page.

The final asset is an email to serve as a template. The plugin will retrieve this email and use it to create each subscription notification email. The email should contain the code `<a href="[[[link]]]">[[[title]]]</a>` as a placeholder for the URL and title of the new blog post. These will automatically be replaced with the correct values by the plugin when it creates a new email. The email ID and email group ID for the template will need to be entered into the plugin settings page.

## Setup in WordPress

Under the Settings menu on the left side of the WordPress backend, click on Blog Subscriptions.

Enter the relevant settings:

### Eloqua Authentication
A username and password for Eloqua are required. All actions taken by the plugin on Eloqua will appear as an action of this user. You will need to ensure that the account used has sufficient permissions to make API calls to Eloqua.

### Eloqua Campaign Information
This information is used to retrieve the email template, save the new email for each insight, and trigger the campaign. The Email ID and Email Group ID should correspond to the relevant fields in the proper email template in Eloqua. The Eloqua Campaign Name and Eloqua Email Name are the base names of the campaign and email associated with each insight. The name of each asset will appear in Eloqua with the base name you supply plus a generated date and time stamp. For example, if you name the email New Inovalon Insight Notification, the email in Eloqua may be saved as New Inovalon Insight Notification 1571922076 2019-10-24. The Default Email Subject Line will be used as the subject line of the email when no email subject line is specified in the blog post. The Eloqua Segment ID and Eloqua Segment Name indicate the list of email addresses to which notifications will be sent. Your Eloqua administrator should create this segment for you and give you the ID and name.

### Eloqua Endpoints
These are the endpoints used to make the API calls. The URLs comprise two parts. The first is the base URL assigned to you by Eloqua. This will be the same for every endpoint. Your Eloqua administrator should be able to locate this. The second part of the URL is the endpoint target for the given action. These can be located at https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAC/rest-endpoints.html. Use the endpoints under Tasks > Application > 2.0. Please ensure that you put https:// at the beginning of the URL. Do not put a / at the end.

### Subscription Post Type
The dropdown will display the various post types your site uses. Select the one for which you would like to send subscription notifications. Only one post type can be selected.

### Invalid Email Domains
If you wish to exclude any domains from subscribing, enter a comma-separated list of the domains. Examples of domains you might wish to exclude include disposable email account (e.g., gmail.com) and competitors. Also enter the full URL of the page to which invalid submissions should be sent.

## Accept Subscriptions
To include a form to allow people to subscribe, use the shortcode `[blog-subscription-form id='###']` where ### is the ID of the appropriate form in Eloqua.

## Create a Post

When you create a post, two custom fields will be available. One will be the Email Subject Line. If no email subject line is entered, the default subject line from the settings page (see above) will be used.

The other is a checkbox that determines whether a notification email will be sent. If the box is checked, the email will be sent. If not, then no email will be sent. If you have published a post without sending a notification, you can send a notification by checking the box and republishing the post. Only one notification can be sent per post. If you have already sent a notification, the status of the checkbox is irrelevant. No further emails will be sent.
