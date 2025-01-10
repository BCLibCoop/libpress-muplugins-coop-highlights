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
 * Version:           1.6.0
 * Network:           true
 * Requires at least: 5.2
 * Requires PHP:      7.0
 * Author:            BC Libraries Cooperative
 * Author URI:        https://bc.libraries.coop
 * Text Domain:       coop-highlights
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace BCLibCoop\CoopHighlights;

class CoopHighlights
{
    private static $instance;

    public static $slug = 'coop_highlights';

    public function __construct()
    {
        if (isset(self::$instance)) {
            return;
        }

        self::$instance = $this;

        add_action('init', [$this, 'registerCustomPostType']);
        add_action('admin_init', [$this, 'adminInit']);
        add_shortcode('coop-highlights', [$this, 'highlightsShortcode']);
    }

    public function adminInit()
    {
        add_action('admin_print_scripts-edit.php', [$this, 'highlightsPositionEnqueueEditScripts']);

        add_action('add_meta_boxes', [$this, 'addHighlightLinkMetaBox']);
        add_action('add_meta_boxes', [$this, 'addHighlightPositionMetaBox']);

        add_action('save_post', [$this, 'savePostHighlightMeta'], 10, 2);

        add_filter('manage_posts_columns', [$this, 'highlightsPositionManagePostColumns'], 10, 2);
        add_action('manage_posts_custom_column', [$this, 'highlightsPositionPopulateColumn'], 10, 2);

        add_action('quick_edit_custom_box', [$this, 'highlightsPositionQuickEditCustomBox'], 10, 2);
    }

    /**
     * Get and order all highlights
     */
    public static function highlightsPosts($include_blank = false)
    {
        $highlights_ordered = [];

        if ($include_blank) {
            $highlights_ordered = array_fill_keys(range(1, 3), null);
        }

        // Get all highlights, ordered ASC, so that newer ones
        // will get the final position
        $highlights_posts = get_posts([
            'posts_per_page' => -1,
            'order_by' => 'post_date',
            'order' => 'ASC',
            'post_type' => 'highlight',
            'post_status' => 'publish'
        ]);

        foreach ($highlights_posts as $hightlight_post) {
            $position = (int) get_post_meta($hightlight_post->ID, '_' . static::$slug . '_position', true);

            if ($position > 0) {
                $highlights_ordered[$position] = $hightlight_post;
            }
        }

        // If there are published posts, but none with a position set,
        // fall back to showing it as the only column
        if (empty($highlights_ordered) && !empty($highlights_posts)) {
            $highlights_ordered[1] = $highlights_posts[0];
        }

        // Make sure array is sorted by key (aka column number)
        ksort($highlights_ordered);

        return $highlights_ordered;
    }

    /**
     * Returns all of the post_content from all the enabled highlights. Mostly
     * for use by other plugins checking for shortcodes
     */
    public static function allHighlightsContent()
    {
        return array_reduce(self::highlightsPosts(), function ($highlights, $highlight) {
            return "{$highlights}\n{$highlight->post_content}";
        }, '');
    }

    /**
     * Output the highlights template
     */
    public function highlightsShortcode($attr, $content, $tag)
    {
        $attr = shortcode_atts([
            'wrapper' => true,
        ], $attr, $tag);
        $attr['wrapper'] = filter_var($attr['wrapper'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        extract($attr);

        // Get view
        ob_start();

        require 'inc/views/shortcode.php';

        return ob_get_clean();
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

    public function addHighlightLinkMetaBox($hook)
    {
        add_meta_box(
            $this->slug . '_linkage',
            'Link Highlight to Page/Post',
            [$this, 'coopHighlightLinkInnerBox'],
            'highlight',
            'normal',
            'high'
        );
    }

    public function addHighlightPositionMetaBox()
    {
        add_meta_box(
            $this->slug . '_placement',
            'Show Highlight in Column #',
            [$this, 'coopHighlightPositionInnerBox'],
            'highlight',
            'normal',
            'high'
        );
    }

    public function coopHighlightLinkInnerBox($post)
    {
        $current = get_post_meta($post->ID, '_' . $this->slug . '_linked_post', true);

        $posts = get_posts([
            'post_type' => ['post', 'page'],
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        $out = [];
        $out[] = '<p>If you wish the highlight to be linked to a post or page, '
            . 'select that post/page from the list below.</p>';
        $out[] = '<select class="' . $this->slug . '_linked_post' . '" name="' . $this->slug . '_linked_post'
            . '" style="width:90%">';
        $out[] = '<option value="0"></option>';
        $out[] = walk_page_dropdown_tree($posts, 0, [
            'depth'                 => 0,
            'child_of'              => 0,
            'selected'              => $current,
            'value_field'           => 'ID',
        ]);
        $out[] = '</select>';
        // $out[] = '<p>Items in green are posts. Items in blue are pages.</p>';

        echo implode("\n", $out);
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

    public function savePostHighlightMeta($post_id, $post)
    {
        // Don't save for autosave or revisions or non-highlights
        if (
            get_post_type($post) !== 'highlight'
            || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            || wp_is_post_revision($post)
        ) {
            return;
        }

        $tag = $this->slug . '_linked_post';

        if (array_key_exists($tag, $_POST)) {
            $link_id = (int) sanitize_text_field($_POST[$tag]);
            update_post_meta($post_id, '_' . $tag, $link_id);
        }

        $tag = $this->slug . '_position';

        if (array_key_exists($tag, $_POST)) {
            $position = (int) sanitize_text_field($_POST[$tag]);
            update_post_meta($post_id, '_' . $tag, $position);
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
            'public' => false,
            'show_ui' => true,
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
                        <select name="<?= $this->slug . '_position' ?>">
                            <option value="1">Column 1</option>
                            <option value="2">Column 2</option>
                            <option value="3">Column 3</option>
                        </select>
                    </label>
                </div>
            </fieldset>
<?php endif;
    }
}

// No Direct Access
defined('ABSPATH') || die(-1);

new CoopHighlights();
