<?php

/**
 * Aria Labels
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           Aria_Labels
 *
 * @wordpress-plugin
 * Plugin Name:       Aria Labels
 * Plugin URI:        http://example.com/plugin-name-uri/
 * Description:       Adds a checkbox to the advanced tab of blocks in the Gutenberg block editor. When checked, it adds the attribute aria-hidden="true" to the block.
 * Version:           1.0.0
 * Author:            Jacob Lodes
 * Author URI:        http://jlodes.com/
 * Text Domain:       aria-hidden
 */

// Stop if this file is called directly.
if (!defined('WPINC')) die;

/**
 * Enqueue the JavaScript file to add the aria-hidden attribute blocks in the editor.
 *
 * @return void
 */
function aria_labels_enqueue_script()
{
    wp_enqueue_script(
        'aria-hidden-script',
        plugins_url('admin.js', __FILE__),
        array('wp-blocks', 'wp-dom-ready', 'wp-edit-post')
    );
}
add_action('enqueue_block_editor_assets', 'aria_labels_enqueue_script');

function aria_labels_render_block($block_content, $block)
{
    // Check if the block has the ariaHidden or ariaLabel attribute
    if (isset($block['attrs']['ariaHidden']) || isset($block['attrs']['ariaLabel'])) {
        // Create a DOMDocument object
        $dom = new DOMDocument();

        // Suppress errors
        libxml_use_internal_errors(true);

        // Load the block content into the DOMDocument object
        @$dom->loadHTML(mb_convert_encoding($block_content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Clear any errors that were suppressed
        libxml_clear_errors();

        // Get the first element in the document
        $target = $dom->documentElement;

        // If the target is not an instance of DOMElement, stop early
        if (!$target instanceof DOMElement) {
            return $block_content;
        }

        // If the block has the ariaHidden attribute and it's true, add the 'aria-hidden' attribute
        if (isset($block['attrs']['ariaHidden']) && $block['attrs']['ariaHidden']) {
            $target->setAttribute('aria-hidden', 'true');
        }

        // If the block has the ariaLabel attribute and it's not empty, add the 'aria-label' attribute
        if (isset($block['attrs']['ariaLabel']) && $block['attrs']['ariaLabel']) {
            $target->setAttribute('aria-label', $block['attrs']['ariaLabel']);
        }

        // Save the modified HTML and return it
        $block_content = $dom->saveHTML();
    }

    return $block_content;
}

// Add the filter
add_filter('render_block', 'aria_labels_render_block', 10, 2);
