<?php

namespace Aria_Labels\Includes;

// If this file is called directly, abort.
if (!defined('WPINC')) die;

// Stop if the class already exists
if (class_exists('Aria_Labels\Includes\Updater')) {
    return;
}
/**
 * Updater Class
 * 
 * Update a plugin from GitHub
 * 
 * @since 1.0.0
 * @version 1.0.0
 */
class Updater
{
    private const REPOSITORY = 'Silver0034/Aria-Labels';
    private const PLUGIN_FILE = 'aria-labels/aria-labels.php';
    private const BASENAME = 'aria-labels';
    private $github_response;

    /**
     * Constructor class to register all the hooks.
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function __construct()
    {
        // Add details to the plugin popup
        add_filter('plugins_api', [$this, 'plugin_popup'], 10, 3);

        // Modify transient before updating plugins
        add_filter(
            'pre_set_site_transient_update_plugins',
            [$this, 'modify_transient']
        );

        // Run function to install the update
        add_filter('upgrader_post_install', [$this, 'install_update'], 10, 3);
    }

    /**
     * Get the instance of the Updater class
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return Updater
     */
    public static function get_instance(): Updater
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }

    /**
     * Get the latest release from the selected repository
     *
     * @since 1.0.0
     * @version 1.0.0
     * @return array
     */
    private function get_latest_repository_release(): array
    {
        // Create the request URI
        $request_uri = sprintf(
            'https://api.github.com/repos/%s/releases',
            $this::REPOSITORY
        );

        // Get the response from the API
        $request = wp_remote_get($request_uri);

        // If the API response has an error code, stop
        $response_codes = wp_remote_retrieve_response_code($request);
        if ($response_codes < 200 || $response_codes >= 300) {
            return [];
        }

        // Decode the response body
        $response = json_decode(wp_remote_retrieve_body($request), true);

        // If the response is an array, return the first item
        if (is_array($response) && !empty($response[0])) {
            $response = $response[0];
        }

        return $response;
    }

    /**
     * Private method to get repository information for a plugin
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return array $response
     */
    private function get_repository_info(): array
    {
        if (!empty($this->github_response)) return $this->github_response;

        // Get the latest repo
        $response = $this->get_latest_repository_release();

        // Set the github_response property for later use
        $this->github_response = $response;

        // Return the response
        return $response;
    }

    /**
     * Add details to the plugin popup
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param boolean $result
     * @param string $action
     * @param object $args
     * @return boolean|object|array $result
     */
    public function plugin_popup($result, $action, $args)
    {
        // If the action is not set to 'plugin_information', stop
        if ($action !== 'plugin_information') {
            return $result;
        }

        if ($args->slug !== $this::BASENAME) {
            return $result;
        }

        $repo = $this->get_repository_info();

        if (empty($repo)) return $result;

        $details = \get_plugin_data(plugin_dir_path(__FILE__) . '../aria-labels.php');

        // Create array to hold the plugin data
        $plugin = [
            'name' => $details['Name'],
            'slug' => $this::BASENAME,
            'requires' => $details['RequiresWP'],
            'requires_php' => $details['RequiresPHP'],
            'version' => $repo['tag_name'],
            'author' => $details['AuthorName'],
            'author_profile' => $details['AuthorURI'],
            'last_updated' => $repo['published_at'],
            'homepage' => $details['PluginURI'],
            'short_description' => $details['Description'],
            'sections' => [
                'Description' => $details['Description'],
                'Updates' => $repo['body']
            ],
            'download_link' => $repo['zipball_url']
        ];

        // Return the plugin data as an object
        return (object) $plugin;
    }

    /**
     * Modify transient for module
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param object $transient
     * @return object
     */
    public function modify_transient(object $transient): object
    {
        // Stop if the transient does not have a checked property
        if (!isset($transient->checked)) return $transient;

        // Check if WordPress has checked for updates
        $checked = $transient->checked;

        // Stop if WordPress has not checked for updates
        if (empty($checked)) return $transient;

        // If the basename is not in $checked, stop
        if (!array_key_exists($this::PLUGIN_FILE, $checked)) {
            return $transient;
        }

        // Get the repo information
        $repo_info = $this->get_repository_info();

        // Stop if the repository information is empty
        if (empty($repo_info)) return $transient;

        // Github version, trim v if exists
        $github_version = ltrim($repo_info['tag_name'], 'v');

        // Compare the module's version to the version on GitHub
        $out_of_date = version_compare(
            $github_version,
            $checked[$this::PLUGIN_FILE],
            'gt'
        );

        // Stop if the module is not out of date
        if (!$out_of_date) return $transient;

        // Add our module to the transient
        $transient->response[$this::PLUGIN_FILE] = (object) [
            'id' => $repo_info['html_url'],
            'url' => $repo_info['html_url'],
            'slug' => current(explode('/', $this::BASENAME)),
            'package' => $repo_info['zipball_url'],
            'new_version' => $repo_info['tag_name']
        ];

        return $transient;
    }

    /**
     * Install the plugin from GitHub
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param boolean $response
     * @param array $hook_extra
     * @param array $result
     * @return boolean|array $result
     */
    public function install_update($response, $hook_extra, $result)
    {
        // Get the plugin directory
        $directory = plugin_dir_path($this::PLUGIN_FILE);

        // Get the global file system object
        global $wp_filesystem;

        // Move the files from result to the directory
        $wp_filesystem->move($result['destination'], $directory);

        // Set the destination for the rest of the files
        $result['destination'] = $directory;

        // If the plugin was active, reactivate it
        if (\is_plugin_active($this::PLUGIN_FILE)) {
            activate_plugin($this::PLUGIN_FILE);
        }

        // Return the result
        return $result;
    }
}
