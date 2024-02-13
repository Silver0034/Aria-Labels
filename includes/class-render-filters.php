<?php

namespace Aria_Labels\Includes;

// If this file is called directly, abort.
if (!defined('WPINC')) die;

/**
 * Class Render_Filters
 *
 * Filter the render to add aria-hidden when a element being rendered has an empty alt attribute
 *
 * @package Aria_Labels\Includes
 * @version 2.0.0
 */
class Render_Filters
{
    /**
     * Render_Filters constructor.
     *
     * Adds actions and filters used by the plugin.
     */
    public function __construct()
    {
        add_filter('render_block', [$this, 'add_aria_label_to_empty_alt_tags'], 10, 2);
    }

    /**
     * Add aria-hidden to the block's HTML if the alt attribute is empty.
     *
     * @param string $block_content The block's HTML.
     * @param array  $block         The block's attributes and information.
     *
     * @return string The modified block's HTML.
     */
    public function add_aria_label_to_empty_alt_tags(string $block_content, array $block): string
    {
        if ($block['blockName'] !== 'core/image') return $block_content;

        if (!empty($block['attrs']['alt'])) return $block_content;

        if (preg_match('/alt="[^"]+"/', $block_content)) return $block_content;

        $block_content = str_replace('<img', '<img aria-hidden="true"', $block_content);

        return $block_content;
    }
}
