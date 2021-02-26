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
 * @copyright         2013-2021 BC Libraries Cooperative
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Coop Highlights
 * Description:       Custom content type to present in highlight boxes on home page
 * Version:           1.1.0
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
            add_action('admin_enqueue_scripts', [&$this, 'adminEnqueue']);

            add_action('add_meta_boxes', [&$this, 'addHighlightLinkMetaBox']);
            add_action('add_meta_boxes', [&$this, 'addHighlightPositionMetaBox']);

            add_action('save_post', [&$this, 'savePostHighlightLinkage']);
            add_action('save_post', [&$this, 'savePostHighlightPosition']);

            add_filter('manage_posts_columns', [&$this, 'highlightsPositionManagePostColumns'], 10, 2);
            add_action('manage_posts_custom_column', [&$this, 'highlightsPositionPopulateColumn'], 10, 2);

            add_action('quick_edit_custom_box', [&$this, 'highlightsPositionQuickEditCustomBox'], 10, 2);
            add_action('admin_print_scripts-edit.php', [&$this, 'highlightsPositionEnqueueEditScripts']);
            add_action('save_post', [&$this, 'highlightsPositionSavePost'], 10, 2);
        }
    }

    public function adminEnqueue($hook)
    {
        if (in_array(
            $hook,
            [
                'site-manager_page_highlight',
                'site-manager_page_highlight-admin',
                'edit.php',
                'post.php',
                'post-new.php'
            ]
        )) {
            wp_enqueue_style('coop-highlights-admin', plugins_url('/css/coop-highlights-admin.css', __FILE__));
        }
    }

    public function addHighlightLinkMetaBox($hook)
    {
        add_meta_box(
            $this->slug . '_linkage',
            'Link Highlight to Page/Post',
            [&$this, 'coopHighlightInnerBox'],
            'highlight'
        );
    }

    public function coopHighlightInnerBox($post)
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

    public function addHighlightPositionMetaBox()
    {
        add_meta_box(
            $this->slug . '_placement',
            'Show Highlight in Column #',
            [&$this, 'coopHighlightPositionInnerBox'],
            'highlight'
        );
    }

    public function coopHighlightPositionInnerBox($post)
    {
        $out = [];
        $tag = $this->slug . '_position';
        $current = get_post_meta($post->ID, '_' . $tag, true);

        $out[] = '<p>You must choose which column this Highlight will be displayed in.</p>';

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

    public function savePostHighlightLinkage($post_id)
    {
        if (!wp_is_post_revision($post_id)) {
            if (array_key_exists($this->slug . '_linked_post', $_POST)) {
                $link_id = (int) $_POST[$this->slug . '_linked_post'];
                update_post_meta($post_id, '_' . $this->slug . '_linked_post', $link_id);
            }
        }
    }

    public function savePostHighlightPosition($post_id)
    {
        if (!wp_is_post_revision($post_id)) {
            $tag = $this->slug . '_position';

            if (array_key_exists($tag, $_POST)) {
                $index = (int) $_POST[$tag];
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
            'supports' => ['title', 'editor'],
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
        $tag = $this->slug . '_position';

        if ($column_name === 'highlight_position') {
            echo '<div id="position-' . $post_id . '">' . get_post_meta($post_id, '_' . $tag, true) . '</div>';
        }
    }

    //Add custom column to quick edit
    public function highlightsPositionQuickEditCustomBox($column_name, $post_type)
    {
        if ($post_type === 'highlight' && $column_name === 'highlight_position') : ?>
            <fieldset class="inline-edit-col-right highlight-position">
                <div class="inline-edit-col">
                    <label class="inline-edit-status"><span class="title">Position</span>
                        <select name="highlight_select">
                            <option name="current-position" class="highlight-option" value=""></option>
                        </select>
                    </label>
                </div>
            </fieldset>
        <?php endif;
    }

    // JQuery script include to target and populate the quick edit box
    public function highlightsPositionEnqueueEditScripts()
    {
        wp_enqueue_script(
            'highlights-admin-edit',
            plugins_url('/js/coop_highlights_quick_edit.js', __FILE__),
            ['jquery', 'inline-edit-post'],
            '',
            true
        );
    }

    // Save our highlight position values on quick edit
    public function highlightsPositionSavePost($post_id, $post)
    {
        // Don't save for autosave or revisions
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            || (isset($post->post_type) && $post->post_type == 'revision')
        ) {
            return;
        }

        if (isset($post->post_type) && $post->post_type === 'highlight') {
            if (array_key_exists('highlight_select', $_POST)) {
                $tag = $this->slug . '_position';
                $position = (int) $_POST['highlight_select'];
                update_post_meta($post_id, '_' . $tag, $position);
            }
        }
    } // end class method h_p_save_post
} // Highlight class definition

// No Direct Access
defined('ABSPATH') || die(-1);

new CoopHighlights();
