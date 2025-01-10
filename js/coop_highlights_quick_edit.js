(function ($) {
  // Copy  WP inline edit post function
  var $wp_inline_edit = inlineEditPost.edit;

  inlineEditPost.edit = function (id) {
    // Invoke  original WP edit function
    $wp_inline_edit.apply(this, arguments);

    var $post_id = 0;

    if (typeof (id) == 'object') {
      $post_id = parseInt(this.getId(id));
    }

    if ($post_id > 0) {
      // Define the edit row
      var $editRow = $('#edit-' + $post_id);
      var highlightPosition = $('#position-' + $post_id).text();

      $posSelect = $editRow.find(':input[name="coop_highlight_position"]');
      $posSelect.val(highlightPosition);
    }
  };
})(jQuery);
