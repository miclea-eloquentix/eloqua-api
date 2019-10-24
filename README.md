# Blog Subscriptions for Eloqua

This plugin for WordPress connects to Eloqua to send notification emails to subscribers when a new blog (or other custom post type) is published. Please note: You must have an Eloqua account for this plugin to work.

## Installation

Upload the blog-subscriptions.zip file and activate the plugin.

## Setup in Eloqua



## Setup in WordPress

Under the Settings menu on the left side of the WordPress backend, click on Blog Subscriptions.

Enter the relevant settings:

### Eloqua Authentication
Username and password for Eloqua are required. All actions taken by the plugin on Eloqua will appear as an action of this user. You will need to ensure that the account used has sufficient permissions to make API calls to Eloqua.

### Eloqua Campaign Information
This information is used to retrieve the email template, save the new email for each insight, and trigger the campaign. The Email ID and Email Group ID should correspond to the relevant fields in the proper email template in Eloqua. The Eloqua Campaign Name and Eloqua Email Name are the base names of the campaign and email associated with each insight. The name of each asset will appear in Eloqua with the base name you supply plus a generated date and time stamp. For example, if you name the email New Inovalon Insight Notification, the email in Eloqua may be saved as New Inovalon Insight Notification 1571922076 2019-10-24. The Eloqua Email Subject Line will appear in the subject line of each outgoing email. The Eloqua Segment ID and Eloqua Segment Name indicate the list of email addresses to which notifications will be sent. Your Eloqua administrator should create this segment for you and give you the ID and name.

### Eloqua Endpoints
These are the endpoints used to make the API calls. The URLs comprise two parts. The first is the base URL assigned to you by Eloqua. This will be the same for every endpoint. Your Eloqua administrator should be able to locate this. The second part of the URL is the endpoint target for the given action. These can be located at https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAC/rest-endpoints.html. Use the endpoints under Tasks > Application > 2.0. Please ensure that you put https:// at the beginning of the URL. Do not put a / at the end.

### Subscription Post Type
The dropdown will display the various post types your site uses. Select the one for which you would like to send subscription notifications. Only one post type can be selected.

### Invalid Email Domains
If you wish to exclude any domains from subscribing, enter a comma-separated list of the domains. Examples of domains you might wish to exclude include disposable email account (e.g., gmail.com) and competitors. Also enter the full URL of the page to which invalid submissions should be sent.

## Accept Subscriptions
To include a form to allow people to subscribe, use the shortcode `[blog-subscription-form id='###']` where ### is the ID of the appropriate form in Eloqua.
