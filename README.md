# Aria Labels WordPress Plugin

## Table of Contents

-   [Overview](#overview)
-   [Features](#features)
-   [Usage](#usage)
-   [Installation](#installation)
-   [Updating](#updating)
-   [Developer Notes](#developer-notes)
-   [Documentation](#documentation)

## Overview

The Aria Labels plugin is designed to enhance accessibility on your WordPress website by adding `aria-hidden` and `aria-label` attributes to Gutenberg blocks. It is developed by Jacob Lodes, and more about his work can be found at [Jacob Lodes' Website](http://jlodes.com/).

## Features

-   Adds `aria-hidden` and `aria-label` attributes to Gutenberg blocks to improve accessibility.
-   The `aria-hidden` attribute is added to the block's HTML if it is set and true in the block's attributes.
-   The `aria-label` attribute is added to the block's HTML if it is set in the block's attributes.
-   The plugin automatically adds `aria-hidden` to the HTML of image blocks if the 'alt' attribute is empty.
-   The plugin can be updated directly from GitHub using the `Updater` class.

## Usage

### Admin Side

1. After installing and activating the plugin, it automatically adds `aria-hidden` and `aria-label` attributes to Gutenberg blocks.

## Installation

1. Download the latest release of the plugin from the [GitHub repository](https://github.com/Silver0034/Aria-Labels/releases).
2. Log in to your WordPress admin dashboard.
3. Navigate to Plugins > Add New.
4. Click on the "Upload Plugin" button at the top of the page.
5. Click "Choose File" and select the downloaded zip file.
6. Click "Install Now" and then "Activate Plugin".

## Updating

1. The plugin checks for updates from the GitHub repository automatically.
2. If an update is available, you will see an update notification in your WordPress admin dashboard.
3. Click on the "update now" link to update the plugin.

## Developer Notes

This section contains information for developers who want to contribute to the plugin or understand its structure for maintenance purposes.

-   File Structure: The main plugin file is `aria-labels.php`. The `includes` directory contains the PHP classes for each feature. The `admin` directory contains the code for the admin interface.
-   Key Classes: The `Aria_Attributes` class in `includes/class-aria-attributes.php` handles adding the `aria-hidden` and `aria-label` attributes. The `Updater` class in `includes/class-updater.php` handles updates to the plugin.
-   The `Aria_Attributes` class uses the `enqueue_block_editor_assets` action to enqueue the JavaScript file in the Gutenberg editor and the `render_block` filter to add `aria-hidden` and `aria-label` attributes to the block's HTML if they are set in the block's attributes.
-   The `Updater` class uses the GitHub API to fetch the latest release of the plugin and update it if necessary. It also adds details to the plugin popup and modifies the transient before updating plugins.

## Documentation

This section contains detailed documentation for the Aria Labels WordPress Plugin.

### Aria Attributes

The `Aria_Attributes` class is responsible for adding `aria-hidden` and `aria-label` attributes to Gutenberg blocks. It uses the `enqueue_block_editor_assets` action to enqueue the JavaScript file in the Gutenberg editor and the `render_block` filter to add `aria-hidden` and `aria-label` attributes to the block's HTML if they are set in the block's attributes.

### Updater

The `Updater` class is responsible for updating the plugin. It uses the GitHub API to fetch the latest release of the plugin and update it if necessary. It also adds details to the plugin popup and modifies the transient before updating plugins.

For more details, please refer to the inline comments in the code.

### Render Filters

The `Render_Filters` class is responsible for enhancing the accessibility of image blocks. It adds the `aria-hidden` attribute to the block's HTML if the `alt` attribute is empty.

The class uses the `render_block` filter to modify the block's HTML. The `add_aria_label_to_empty_alt_tags` method checks if the block is an image block and if the `alt` attribute is empty. If both conditions are met, it adds `aria-hidden="true"` to the `<img` tag in the block's HTML.

Here's a brief overview of the methods in the `Render_Filters` class:

-   `__construct()`: This method adds the `render_block` filter, which is used to modify the block's HTML.
-   `add_aria_label_to_empty_alt_tags(string $block_content, array $block)`: This method checks if the block is an image block and if the `alt` attribute is empty. If both conditions are met, it adds `aria-hidden="true"` to the `<img` tag in the block's HTML.
