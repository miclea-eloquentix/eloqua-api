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

require_once('BlogSubscriptionsController.php');
require_once('BlogSubscriptionsOptions.php');
require_once('EloquaConnector.php');

$settings = new BlogSubscriptionOptions();
$controller = new BlogSubscriptionController();

?>
