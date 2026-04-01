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
    private const PLUGIN_MAIN_FILE = __DIR__ . '/../aria-labels.php';
    private $github_response;
    private string $plugin_file;
    private string $slug;

    /**
     * Constructor class to register all the hooks.
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function __construct()
    {
        $this->plugin_file = plugin_basename(realpath(self::PLUGIN_MAIN_FILE));
        $this->slug = dirname($this->plugin_file);

        // Add details to the plugin popup
        add_filter('plugins_api', [$this, 'plugin_popup'], 10, 3);

        // Modify transient before updating plugins
        add_filter(
            'pre_set_site_transient_update_plugins',
            [$this, 'modify_transient']
        );

        // Normalize GitHub extracted folder name before installation copy starts.
        add_filter('upgrader_source_selection', [$this, 'rename_source_directory'], 10, 4);

        // Run function to install the update
        add_filter('upgrader_post_install', [$this, 'install_update'], 10, 3);
    }

    /**
     * Write updater diagnostics to debug.log when WP_DEBUG is enabled.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    private function log_update(string $message, array $context = []): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $suffix = !empty($context) ? ' ' . wp_json_encode($context) : '';
        error_log('[Aria Labels Updater] ' . $message . $suffix);
    }

    /**
     * Send a line to the upgrader "More details" output when available.
     *
     * @param mixed $upgrader
     * @param string $message
     * @return void
     */
    private function feedback($upgrader, string $message): void
    {
        if (is_object($upgrader) && isset($upgrader->skin) && is_object($upgrader->skin) && method_exists($upgrader->skin, 'feedback')) {
            $upgrader->skin->feedback('Aria Labels: ' . $message);
        }
    }

    /**
     * Rename extracted GitHub source folder to the expected plugin folder name
     * before WordPress copies files into the plugins directory.
     *
     * @param string $source
     * @param string $remote_source
     * @param mixed $upgrader
     * @param array $hook_extra
     * @return string
     */
    public function rename_source_directory($source, $remote_source, $upgrader, $hook_extra)
    {
        if (empty($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_file) {
            return $source;
        }

        global $wp_filesystem;

        $expected_dir_name = basename($this->slug);
        $expected_path = trailingslashit($remote_source) . $expected_dir_name;
        $expected_source = trailingslashit($expected_path);

        $this->feedback($upgrader, sprintf('source selected: %s', $source));
        $this->log_update('source-selection', [
            'source' => $source,
            'remote_source' => $remote_source,
            'expected_path' => $expected_source,
            'plugin' => $hook_extra['plugin']
        ]);

        if (trailingslashit($source) === $expected_source) {
            $this->feedback($upgrader, 'source folder already matches expected plugin folder.');
            return $source;
        }

        $downloaded_main_file = trailingslashit($source) . 'aria-labels.php';
        if (empty($expected_dir_name) || stripos($expected_dir_name, 'aria-labels') === false || !$wp_filesystem || !$wp_filesystem->exists($downloaded_main_file)) {
            $this->feedback($upgrader, 'source safety check failed; keeping original source folder name.');
            $this->log_update('source-safety-check-failed', [
                'expected_dir_name' => $expected_dir_name,
                'downloaded_main_file' => $downloaded_main_file,
                'exists' => ($wp_filesystem && $wp_filesystem->exists($downloaded_main_file))
            ]);
            return $source;
        }

        if ($wp_filesystem->is_dir($expected_path)) {
            $target_main_file = trailingslashit($expected_path) . 'aria-labels.php';
            if (!$wp_filesystem->exists($target_main_file)) {
                $this->feedback($upgrader, 'expected target exists but is not Aria Labels; aborting source rename.');
                $this->log_update('target-exists-not-plugin', ['expected_path' => $expected_path]);
                return $source;
            }

            $this->feedback($upgrader, sprintf('removing existing target folder: %s', $expected_path));
            $wp_filesystem->delete($expected_path, true);
        }

        $move_success = $wp_filesystem->move($source, $expected_path);
        $this->log_update('source-rename-attempt', [
            'from' => $source,
            'to' => $expected_source,
            'success' => (bool) $move_success
        ]);

        if ($move_success) {
            $this->feedback($upgrader, sprintf('renamed source folder to: %s', $expected_dir_name));
            return $expected_source;
        }

        $this->feedback($upgrader, 'failed to rename source folder; keeping original source folder name.');
        return $source;
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

        if ($args->slug !== $this->slug) {
            return $result;
        }

        $repo = $this->get_repository_info();

        if (empty($repo)) return $result;

        $details = \get_plugin_data(plugin_dir_path(__FILE__) . '../aria-labels.php');

        // Create array to hold the plugin data
        $plugin = [
            'name' => $details['Name'],
            'slug' => $this->slug,
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
        if (!array_key_exists($this->plugin_file, $checked)) {
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
            $checked[$this->plugin_file],
            'gt'
        );

        // Stop if the module is not out of date
        if (!$out_of_date) return $transient;

        // Add our module to the transient
        $transient->response[$this->plugin_file] = (object) [
            'id' => $repo_info['html_url'],
            'url' => $repo_info['html_url'],
            'slug' => $this->slug,
            'package' => $repo_info['zipball_url'],
            'new_version' => $github_version
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
        // Only run for this plugin.
        if (empty($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_file) {
            return $result;
        }

        // Get the global file system object
        global $wp_filesystem;

        if (empty($wp_filesystem)) {
            return $result;
        }

        // Use the expected plugin directory name from plugin slug.
        $correct_directory_name = basename($this->slug);

        // Get the path to the downloaded directory
        $downloaded_directory_path = $result['destination'];

        // Get the path to the parent directory
        $parent_directory_path = dirname($downloaded_directory_path);

        // Construct the correct path
        $correct_directory_path = $parent_directory_path . '/' . $correct_directory_name;

        $this->log_update('post-install-paths', [
            'downloaded_directory_path' => $downloaded_directory_path,
            'correct_directory_path' => $correct_directory_path,
            'correct_directory_name' => $correct_directory_name,
            'hook_plugin' => $hook_extra['plugin']
        ]);

        // Safety checks before proceeding:
        //   1. The correct_directory_name must be non-empty (guards against basename() returning '' on trailing slash).
        //   2. The directory name must contain 'aria-labels' (case-insensitive).
        //   3. The downloaded directory must contain aria-labels.php — confirms this is our plugin.
        //      (We check the downloaded dir, NOT the target — WP already deleted the old folder by this point.)
        $downloaded_main_file = \trailingslashit($downloaded_directory_path) . 'aria-labels.php';
        $safe_to_proceed = (
            !empty($correct_directory_name) &&
            stripos($correct_directory_name, 'aria-labels') !== false &&
            $wp_filesystem->exists($downloaded_main_file)
        );

        if (!$safe_to_proceed) {
            // Something doesn't look right — bail without touching anything.
            $this->log_update('post-install-safety-check-failed', [
                'correct_directory_name' => $correct_directory_name,
                'downloaded_main_file' => $downloaded_main_file,
                'exists' => $wp_filesystem->exists($downloaded_main_file)
            ]);
            return $response;
        }

        // If the paths already match, no rename needed.
        if (\trailingslashit($downloaded_directory_path) === \trailingslashit($correct_directory_path)) {
            $this->log_update('post-install-no-rename-needed');
            return $result;
        }

        // If the target somehow still exists (e.g. WP skipped cleanup), only delete it
        // if it definitely contains aria-labels.php so we don't nuke an unrelated folder.
        if ($wp_filesystem->is_dir($correct_directory_path)) {
            $target_main_file = \trailingslashit($correct_directory_path) . 'aria-labels.php';
            if (!$wp_filesystem->exists($target_main_file)) {
                // Target exists but isn't our plugin — bail.
                $this->log_update('post-install-target-not-plugin', ['target_main_file' => $target_main_file]);
                return $response;
            }
            $wp_filesystem->delete($correct_directory_path, true);
        }

        // Move and rename the downloaded directory to the correct name.
        $moved = $wp_filesystem->move($downloaded_directory_path, $correct_directory_path);
        $this->log_update('post-install-rename-attempt', [
            'from' => $downloaded_directory_path,
            'to' => $correct_directory_path,
            'success' => (bool) $moved
        ]);

        if (!$moved) {
            return $response;
        }

        // Update the destination in the result
        $result['destination'] = $correct_directory_path;

        // If the plugin was active, reactivate it
        if (\is_plugin_active($this->plugin_file)) {
            activate_plugin($this->plugin_file);
        }

        // Return the result
        return $result;
    }
}
