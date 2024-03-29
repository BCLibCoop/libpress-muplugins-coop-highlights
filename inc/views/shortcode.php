<?php

/**
 * Output for the highlight shortcode
 */

namespace BCLibCoop\CoopHighlights;

?>
<?php if ($highlights = CoopHighlights::highlightsPosts()) : ?>
    <?php if ($wrapper) : ?>
        <div class="row highlights">
    <?php endif; ?>
        <?php foreach ($highlights as $column_number => $post) : ?>
            <div class="third lede-<?= $column_number ?>">
                <?php
                setup_postdata($GLOBALS['post'] = $post);
                $linked_id = get_post_meta($post->ID, '_coop_highlights_linked_post', true);
                $permalink = $linked_id ? get_permalink($linked_id) : false;
                ?>
                <?= $permalink ? '<a href="' . $permalink . '">' : '' ?>
                    <?php the_title('<h2>', '</h2>'); ?>
                    <?php
                    /**
                     * Maintaining functionality of wrapping the content in the
                     * anchor tag, even though that's less than useful in most
                     * cases
                     */
                    ?>
                    <?php the_content(); ?>
                <?= $permalink ? '</a>' : '' ?>
            </div>
        <?php endforeach;
        wp_reset_postdata(); ?>
    <?php if ($wrapper) : ?>
        </div><!-- highlights -->
    <?php endif; ?>
<?php endif; ?>
