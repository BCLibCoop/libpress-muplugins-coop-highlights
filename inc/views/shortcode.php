<?php

/**
 * Output for the highlight shortcode
 */

namespace BCLibCoop\CoopHighlights;

?>
<?php if ($highlights = CoopHighlights::highlightsPosts()) : ?>
    <?php $width = 12 / count($highlights); ?>
    <div class="row highlights">
        <?php foreach ($highlights as $post) : ?>
            <div class="highlight col-12 col-md-<?= $width ?>">
                <?php
                /**
                 * Maintaining functionality of wrapping the content in the
                 * anchor tag, even though that's less than useful in most
                 * cases
                 */
                ?>
                <?php
                setup_postdata($GLOBALS['post'] = $post);
                $linked_id = get_post_meta($post->ID, '_coop_highlights_linked_post', true);
                $permalink = $linked_id ? get_permalink($linked_id) : false;
                ?>
                <?= $permalink ? '<a href="' . $permalink . '">' : '' ?>
                    <?php the_title('<h2>', '</h2>'); ?>
                    <?php the_content(); ?>
                <?= $permalink ? '</a>' : '' ?>
            </div>
        <?php endforeach;
        wp_reset_postdata(); ?>
    </div><!-- highlights -->
<?php endif; ?>
