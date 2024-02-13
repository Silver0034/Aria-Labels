<?php

/**
 * Aria Labels
 *
 * @link              https://github.com/Silver0034/Aria-Labels
 * @since             1.0.0
 * @package           Aria_Labels
 *
 * @wordpress-plugin
 * Plugin Name:       Aria Labels
 * Plugin URI:        https://github.com/Silver0034/Aria-Labels
 * Description:       Enhance accessibility by adding aria-hidden and aria-label attributes to Gutenberg blocks.
 * Version:           2.0.2
 * Author:            Jacob Lodes
 * Author URI:        http://jlodes.com/
 * Text Domain:       aria-hidden
 */

namespace Aria_Labels;

// Stop if this file is called directly.
if (!defined('WPINC')) die;

require_once plugin_dir_path(__FILE__) . 'includes/class-aria-attributes.php';
new Includes\Aria_Attributes();

require_once plugin_dir_path(__FILE__) . 'includes/class-render-filters.php';
new Includes\Render_Filters();

if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'includes/class-updater.php';
    new Includes\Updater();
}
