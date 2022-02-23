<?php

/**
 * Coop Highlights
 *
 * Adds a custom post type with metaboxes to allow for variable display of
 * custom content on front page.
 *
 * PHP Version 7
 *
 * @package           BCLibCoop\CoopHighlights
 * @author            Erik Stainsby <eric.stainsby@roaringsky.ca>
 * @author            Jonathan Schatz <jonathan.schatz@bc.libraries.coop>
 * @author            Sam Edwards <sam.edwards@bc.libraries.coop>
 * @copyright         2013-2022 BC Libraries Cooperative
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Coop Highlights
 * Description:       Custom content type to present in highlight boxes on home page
 * Version:           1.2.0
 * Network:           true
 * Requires at least: 5.2
 * Requires PHP:      7.0
 * Author:            BC Libraries Cooperative
 * Author URI:        https://bc.libraries.coop
 * Text Domain:       coop-highlights
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace BCLibCoop;

class CoopHighlights
{
    private static $instance;

    public $slug = 'coop_highlights';

    public function __construct()
    {
        if (isset(self::$instance)) {
            return;
        }

        self::$instance = $this;

        add_action('init', [&$this, 'init']);
    }

    public function init()
    {
        $this->registerCustomPostType();

        if (is_admin()) {
            add_action('admin_print_scripts-edit.php', [&$this, 'highlightsPositionEnqueueEditScripts']);

            add_action('add_meta_boxes', [&$this, 'addHighlightPositionMetaBox']);

            add_action('save_post', [&$this, 'savePostHighlightPosition']);
            add_action('save_post', [&$this, 'highlightsPositionSavePost'], 10, 2);

            add_filter('manage_posts_columns', [&$this, 'highlightsPositionManagePostColumns'], 10, 2);
            add_action('manage_posts_custom_column', [&$this, 'highlightsPositionPopulateColumn'], 10, 2);

            add_action('quick_edit_custom_box', [&$this, 'highlightsPositionQuickEditCustomBox'], 10, 2);
        }
    }

    // JQuery script include to target and populate the quick edit box
    public function highlightsPositionEnqueueEditScripts()
    {
        wp_enqueue_script(
            'highlights-admin-edit',
            plugins_url('/js/coop_highlights_quick_edit.js', __FILE__),
            ['jquery', 'inline-edit-post'],
            get_plugin_data(__FILE__, false, false)['Version']
        );
    }

    public function addHighlightPositionMetaBox()
    {
        add_meta_box(
            $this->slug . '_placement',
            'Show Highlight in Column #',
            [&$this, 'coopHighlightPositionInnerBox'],
            'highlight',
            'normal',
            'high'
        );
    }

    public function coopHighlightPositionInnerBox($post)
    {
        $out = [];
        $tag = $this->slug . '_position';
        // Set a default position for more predictable output
        $current = get_post_meta($post->ID, '_' . $tag, true) ? get_post_meta($post->ID, '_' . $tag, true) : 1;

        $out[] = '<p>You must choose which column this Highlight will be displayed in.</p>';
        $out[] = '<p>If multiple published highlights have the same column selected, the newest one will be shown.</p>';

        for ($i = 1; $i <= 3; $i++) {
            $out[] = sprintf(
                '<p><input type="radio" id="highlight_column_%d" value="%d" name="%s"%s>',
                $i,
                $i,
                $tag,
                (($current == $i) ? ' checked="checked"' : '')
            );
            $out[] = sprintf('<label for="highlight_column_%d">Column %d</label></p>', $i, $i);
        }

        echo implode("\n", $out);
    }

    public function savePostHighlightPosition($post_id)
    {
        if (!wp_is_post_revision($post_id)) {
            $tag = $this->slug . '_position';

            if (array_key_exists($tag, $_POST)) {
                $index = (int) sanitize_text_field($_POST[$tag]);
                update_post_meta($post_id, '_' . $tag, $index);
            }
        }
    }

    public function registerCustomPostType()
    {
        $labels = [
            'name' => 'Highlights',
            'singular_name' => 'Highlight',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Highlight',
            'edit_item' => 'Edit Highlight',
            'new_item' => 'New Highlight',
            'all_items' => 'All Highlights',
            'view_item' => 'View Highlight',
            'search_items' => 'Search Highlights',
            'not_found' =>  'No highlights found',
            'not_found_in_trash' => 'No highlights found in Trash',
            'parent_item_colon' => '',
            'menu_name' => 'Highlights',
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'highlight'],
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 17,
            'supports' => ['title', 'editor', 'revisions'],
            'taxonomies' => ['category', 'post_tag'],
        ];

        register_post_type('highlight', $args);
    }

    // Add custom column to Highlights listing after Tags column
    public function highlightsPositionManagePostColumns($columns, $post_type)
    {
        if ($post_type === 'highlight') {
            $new_columns = [];

            foreach ($columns as $key => $value) {
                $new_columns[$key] = $value;

                // Insert after Tags column
                if ($key === 'tags') {
                    $new_columns['highlight_position'] = 'Position';
                }
            }

            return $new_columns;
        }

        return $columns;
    }

    // Populate with the post metadata
    public function highlightsPositionPopulateColumn($column_name, $post_id)
    {
        if ($column_name === 'highlight_position') {
            echo '<div id="position-' . $post_id . '">'
                . get_post_meta($post_id, '_' . $this->slug . '_position', true)
                . '</div>';
        }
    }

    // Add custom column to quick edit
    public function highlightsPositionQuickEditCustomBox($column_name, $post_type)
    {
        if ($post_type === 'highlight' && $column_name === 'highlight_position') : ?>
            <fieldset class="inline-edit-col-right">
                <div class="inline-edit-col">
                    <label class="alignleft">
                        <span class="title">Position</span>
                        <select name="highlight_select">
                            <option value="1">Column 1</option>
                            <option value="2">Column 2</option>
                            <option value="3">Column 3</option>
                        </select>
                    </label>
                </div>
            </fieldset>
        <?php endif;
    }

    // Save our highlight position values on quick edit
    public function highlightsPositionSavePost($post_id, $post)
    {
        // Don't save for autosave or revisions
        if (
            (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            || (isset($post->post_type) && $post->post_type == 'revision')
        ) {
            return;
        }

        if (isset($post->post_type) && $post->post_type === 'highlight') {
            if (array_key_exists('highlight_select', $_POST)) {
                $position = (int) sanitize_text_field($_POST['highlight_select']);
                update_post_meta($post_id, '_' . $this->slug . '_position', $position);
            }
        }
    }
}

// No Direct Access
defined('ABSPATH') || die(-1);

new CoopHighlights();
