<?php if (!empty($highlights_ordered)) : ?>
    <div class="row highlights" role="main" id="primary">
        <?php foreach ($highlights_ordered as $column_number => $post) : ?>
            <div class="third lede-<?= $column_number ?>">
                <?php
                setup_postdata($GLOBALS['post'] = &$post);
                $permalink = null;
                $linked_id = get_post_meta($post->ID, '_coop_highlights_linked_post', true);
                if ($linked_id) {
                    $permalink = get_permalink($linked_id);
                }
                ?>
                <?php echo $permalink ? '<a href="' . $permalink . '">' : '' ?>
                <?php the_title('<h3>', '</h3>'); ?>
                <?php the_content(); ?>
                <?php echo $permalink ? '</a>' : '' ?>
            </div>
        <?php endforeach;
        wp_reset_postdata(); ?>
    </div><!-- highlights -->
<?php endif; ?>
