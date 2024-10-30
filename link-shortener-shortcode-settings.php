<?php

if(!defined('ABSPATH')){
	exit; 
}

add_action('admin_init', 'icebio_register_plugin_settings');
add_action('admin_menu', 'icebio_add_plugin_page');
add_action('add_meta_boxes', 'icebio_add_shortened_links_meta_box');
add_action('save_post', 'icebio_save_shortened_links_meta_box');
function icebio_add_plugin_page()
{
    add_management_page(
        __('ice.bio URL Shortener Settings','ice-bio-url-shortener'),
        __('ice.bio URL Shortener','ice-bio-url-shortener'),
        'manage_options',
        'icebio-plugin-settings',
        'icebio_render_plugin_settings_page'
    );
}

function icebio_add_shortened_links_meta_box() {
    add_meta_box(
        'icebio_shortened_links_meta_box',
        __('Shortened Links','ice-bio-url-shortener'),
        'icebio_display_shortened_links_meta_box',
        'post',
        'side',
        'default'
    );
}

function icebio_display_shortened_links_meta_box($post) {
    $shortened_links = get_post_meta($post->ID, 'icebio_shortened_links', true);

    if (!$shortened_links) {
        $shortened_links = [];
    }

    echo '<table class="widefat">';
    echo '<thead><tr><th>' . _e('Original URL','ice-bio-url-shortener') . '</th><th>' . _e('Shortened URL','ice-bio-url-shortener') . '</th></tr></thead>';
    echo '<tbody>';

    foreach ($shortened_links as $original_url => $short_url) {
        echo '<tr>';
        echo '<td><input type="text" name="icebio_original_urls[]" value="' . esc_url($original_url) . '" readonly></td>';
        echo '<td><input type="text" name="icebio_short_urls[]" value="' . esc_url($short_url) . '"></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    wp_nonce_field('icebio_save_shortened_links', 'icebio_shortened_links_nonce');
}

function icebio_save_shortened_links_meta_box($post_id) {
    if (!isset($_POST['icebio_shortened_links_nonce']) || !wp_verify_nonce($_POST['icebio_shortened_links_nonce'], 'icebio_save_shortened_links')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['icebio_original_urls']) && isset($_POST['icebio_short_urls'])) {
    // Sanitize and validate the submitted URLs
    $original_urls = array_map('sanitize_url', $_POST['icebio_original_urls']);
    $short_urls = array_map('sanitize_url', $_POST['icebio_short_urls']);

    // Validate the data (e.g., ensure the URLs are not empty)
    $original_urls = array_filter($original_urls);
    $short_urls = array_filter($short_urls);

    // Combine the sanitized and validated arrays
    $shortened_links = array_combine($original_urls, $short_urls);

    // Escape the data before storing it in post meta
    $escaped_links = array_map('esc_url', $shortened_links);

    // Update the post meta
    update_post_meta($post_id, 'icebio_shortened_links', $escaped_links);
	}
}

// Register the plugin settings
function icebio_register_plugin_settings()
{
    register_setting('icebio-plugin-settings-group', 'icebio_api_key');
    register_setting('icebio-plugin-settings-group', 'icebio_auto_shorten_external_links');
    register_setting('icebio-plugin-settings-group', 'icebio_custom_shortcode', 'shorturl');
}

// Render the options page
function icebio_render_plugin_settings_page()
{
    ?>
    <div class="wrap">
        <h1>Link Shortener Settings</h1>

        <form method="post" action="options.php">
            <?php settings_fields('icebio-plugin-settings-group'); ?>
            <?php do_settings_sections('icebio-plugin-settings-group'); ?>
            <table class="form-table" role="presentation">
				<tbody>
                <tr valign="top">
                    <th scope="row"><label for="icebio_api_key"><?php _e('API Key','ice-bio-url-shortener') ?></label></th>
                    <td><input type="text" name="icebio_api_key" id="icebio_api_key" value="<?php echo esc_attr(get_option('icebio_api_key')); ?>" />
					<p class="description" id="icebio_api_key"><?php _e('Find your API Key in ','ice-bio-url-shortener') ?><a href="<?php echo esc_url('https://ice.bio/user/settings'); ?>"><?php echo esc_url('https://ice.bio/user/settings'); ?></a>.</p></td>
                </tr>
					<tr valign="top">
                    <th scope="row"><label for="icebio_custom_shortcode"><?php _e('Custom Shortcode','ice-bio-url-shortener') ?></label></th>
                    <td><input type="text" name="icebio_custom_shortcode" id="pus_custom_shortcode" value="<?php echo esc_attr(get_option('icebio_custom_shortcode')); ?>" />
						<p class="description" id="icebio_custom_shortcode"><?php _e('Use a custom Shortcode like [shorturl]https://example.com/long-url[/shorturl] .','ice-bio-url-shortener') ?></p></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="icebio_auto_shorten_external_links"><?php _e('Auto Shorten External Links','ice-bio-url-shortener') ?></label></th>
                    <td><input type="checkbox" name="icebio_auto_shorten_external_links" id="icebio_auto_shorten_external_links" <?php checked((bool) get_option('icebio_auto_shorten_external_links', false)); ?> /></td>
                </tr>
                </tbody>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
?>