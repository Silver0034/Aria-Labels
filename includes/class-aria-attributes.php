<?php

namespace Aria_Labels\Includes;

use DOMDocument;
use DOMElement;

// If this file is called directly, abort.
if (!defined('WPINC')) die;

/**
 * Class Aria_Attributes
 *
 * This class handles the addition of aria-hidden and aria-label attributes to Gutenberg blocks.
 *
 * @package Aria_Labels\Includes
 * @version 1.0.0
 */
class Aria_Attributes
{
    /**
     * Aria_Attributes constructor.
     *
     * Adds actions and filters used by the plugin.
     */
    public function __construct()
    {
        // Enqueue the JavaScript file in the Gutenberg editor.
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_script'));

        // Filter the content of each block.
        add_filter('render_block', array($this, 'render_block'), 10, 2);
    }

    /**
     * Enqueue the JavaScript file that adds the aria-hidden and aria-label controls to the block sidebar.
     *
     * @return void
     */
    public function enqueue_script(): void
    {
        wp_enqueue_script(
            'aria-hidden-script',
            plugins_url('../admin.js', __FILE__),
            array('wp-blocks', 'wp-dom-ready', 'wp-edit-post')
        );
    }

    /**
     * Add aria-hidden and aria-label attributes to the block's HTML if they are set in the block's attributes.
     *
     * @param string $block_content The block's HTML.
     * @param array  $block         The block's attributes and information.
     *
     * @return string The modified block's HTML.
     */
    public function render_block(string $block_content, array $block): string
    {
        // Check if the block has aria-hidden or aria-label attributes set.
        if (isset($block['attrs']['ariaHidden']) || isset($block['attrs']['ariaLabel'])) {
            $dom = new DOMDocument();

            // Suppress errors due to libxml's handling of HTML5.
            libxml_use_internal_errors(true);

            // Load the block's HTML into the DOMDocument.
            @$dom->loadHTML(mb_convert_encoding($block_content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            // Clear the libxml errors.
            libxml_clear_errors();

            // Get the root element of the block's HTML.
            $target = $dom->documentElement;

            // If the root element is not a DOMElement, return the original block content.
            if (!$target instanceof DOMElement) {
                return $block_content;
            }

            // If the aria-hidden attribute is set and true, add it to the block's HTML.
            if (isset($block['attrs']['ariaHidden']) && $block['attrs']['ariaHidden']) {
                $target->setAttribute('aria-hidden', 'true');
            }

            // If the aria-label attribute is set, add it to the block's HTML.
            if (isset($block['attrs']['ariaLabel']) && $block['attrs']['ariaLabel']) {
                $target->setAttribute('aria-label', $block['attrs']['ariaLabel']);
            }

            // Save the modified HTML back into the block content.
            $block_content = $dom->saveHTML();
        }

        // Return the possibly modified block content.
        return $block_content;
    }
}
