<?php

if(!defined('ABSPATH')){
	exit; 
}
// Function to send the request to the shortening API
function icebio_short_url($url) {

	$body = ['url'  => $url,];
	$body = json_encode( $body );
	$args = array(
		'body' 		=> $body,
		'method'      => 'POST',
		'timeout'     => 10,
		'redirection' => 5,
		'httpversion' => '1.0',
		
    	'headers' 	=> array(
        	'Authorization' => 'Bearer ' . get_option('icebio_api_key'),
			'Content-Type'  => 'application/json'
    )
);
	$response = wp_remote_post('https://ice.bio/api/url/add', $args );
	$body = wp_remote_retrieve_body($response);
	

	if (is_wp_error($response)) {
	$error_message = $response->get_error_message();
	return "Something went wrong: $error_message";
	}
	
    if ($object = json_decode($body)) {
        if (isset($object->shorturl)) {
            return $object->shorturl;
        }
    }

    return $body;
}

// Function to modify external links in content
function icebio_modify_external_links($content) {
    // Check if auto shortening feature is turned on
    $auto_shorten = get_option('icebio_auto_shorten_external_links', false);

    if ($auto_shorten && !is_admin()) {
        $post_id = get_the_ID();
        $shortened_links = get_post_meta($post_id, 'icebio_shortened_links', true);

        if (!$shortened_links) {
            $shortened_links = [];
        }

        $dom = new DOMDocument();
        @$dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $links = $dom->getElementsByTagName('a');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');

            // Ignore non-HTTP/HTTPS links
            if (stripos($href, 'http') !== 0) {
                continue;
            }

            // Ignore links to the current site
            if (stripos($href, site_url()) === 0) {
                continue;
            }

            // Check if the short URL is already saved
            if (isset($shortened_links[$href])) {
                $short_url = $shortened_links[$href];
            } else {
                $short_url = icebio_short_url($href);
                $shortened_links[$href] = $short_url;
            }

            $link->setAttribute('href', $short_url);
        }

        update_post_meta($post_id, 'icebio_shortened_links', $shortened_links);

        $new_content = $dom->saveHTML();

        return $new_content;
    }

    return $content;
}

// Register the shortcode
add_shortcode(esc_attr(get_option('icebio_custom_shortcode')), function($atts, $content = null) {
    if ($content !== null) {
		$post_id = get_the_ID();
        $shortened_links = get_post_meta($post_id, 'icebio_shortened_links', true);

        	if (isset($shortened_links[$content])) {
                $short_url = $shortened_links[$content];
            } else {
                $short_url = icebio_short_url($content);
                $shortened_links[$content] = $short_url;
            }
        update_post_meta($post_id, 'icebio_shortened_links', $shortened_links);
		return $shortened_links[$content];
    }
});