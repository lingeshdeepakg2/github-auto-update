<?php
/**
 * Plugin Name: GitHub Auto Update
 * Description: Automatically updates WordPress plugins from GitHub repositories.
 * Version: 1.1
 */

add_filter('plugins_api', 'github_auto_update_plugins_api', 10, 3);
function github_auto_update_plugins_api($result, $action, $args)
{   
    error_log("github_auto_update_plugins_api");
    if ($action === 'plugin_information' && isset($args->slug) && $args->slug === 'github-auto-update') {
        $plugin_data = get_plugin_data(__FILE__);
        $response = [
            'name' => $plugin_data['Name'],
            'version' => $plugin_data['Version'],
            'author' => $plugin_data['Author'],
            'homepage' => $plugin_data['PluginURI'],
        ];
        return (object)$response;
    }
    return $result;
}

add_filter('pre_set_site_transient_update_plugins', 'github_auto_update_check_for_updates');
function github_auto_update_check_for_updates($transient)
{
    if (empty($transient->checked)) {
        return $transient;
    }

    $plugin_slug = basename(dirname(__FILE__));
    $package = 'https://api.github.com/repos/lingeshdeepakg2/toolkittest/releases/';
    $response = wp_safe_remote_get($package, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $this->accessToken,
            'User-Agent' => 'Your-User-Agent', // Replace with an appropriate user agent
        ),
    ));
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $github_data = json_decode(wp_remote_retrieve_body($response));

        if (version_compare($transient->checked[$plugin_slug], $github_data->tag_name, '<')) {
            $plugin_info = github_auto_update_plugins_api(null, 'plugin_information', (object)['slug' => 'github-auto-update']);
            if (!empty($plugin_info)) {
                $transient->response[$plugin_slug] = $plugin_info;
            }
        }
    }

    return $transient;
}

add_filter('upgrader_source_selection', 'github_auto_update_change_source_directory', 10, 3);
function github_auto_update_change_source_directory($source, $remote_source, $upgrader)
{
    if ($upgrader->skin->plugin === 'github-auto-update/github-auto-update.php') {
        return dirname(__FILE__);
    }
    return $source;
}
