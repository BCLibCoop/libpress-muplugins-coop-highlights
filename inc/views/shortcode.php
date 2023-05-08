<?php

/**
 * Output for the highlight shortcode
 */

?>
<?php if (!empty($highlights_ordered)) : ?>
    <div class="row highlights">
        <?php foreach ($highlights_ordered as $column_number => $post) : ?>
            <div class="third lede-<?= $column_number ?>">
                <?php
                setup_postdata($GLOBALS['post'] = $post);
                $linked_id = get_post_meta($post->ID, '_coop_highlights_linked_post', true);
                $permalink = $linked_id ? get_permalink($linked_id) : false;
                ?>
                <?php echo $permalink ? '<a href="' . $permalink . '">' : '' ?>
                    <?php the_title('<h3>', '</h3>'); ?>
                    <?php
                    /**
                     * Maintaining functionality of wrapping the content in the
                     * anchor tag, even though that's less than useful in most
                     * cases
                     */
                    ?>
                    <?php the_content(); ?>
                <?php echo $permalink ? '</a>' : '' ?>
            </div>
        <?php endforeach;
        wp_reset_postdata(); ?>
    </div><!-- highlights -->
<?php endif; ?>
