<?php
/*
Plugin Name: ice.bio URL Shortener
Plugin URI: https://ice.bio
Description: Short links directly using [shorturl] shortcode or auto external links.
Version: 3.0
Author: ice
Author URI: https://ice.bio/me
Text Domain: ice-bio-url-shortener
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')){
	exit; 
}

// Include the settings page and functions
require_once(plugin_dir_path(__FILE__) . 'link-shortener-shortcode-settings.php');
require_once(plugin_dir_path(__FILE__) . 'link-shortener-shortcode-functions.php');


// Modify external links in post content
add_filter('the_content', 'icebio_modify_external_links');

?>