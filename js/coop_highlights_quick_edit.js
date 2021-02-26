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
      // define the edit row
      var $edit_row = $('#edit-' + $post_id);
      var $highlight_position = $('#position-' + $post_id).text();

      var $pos_select = $('select[name="highlight_select"]').first(); //Prevent hidden elements at page bottom from throwing off our selection

      if (typeof $highlight_position == "undefined" || $highlight_position === "") {
        $edit_row.find('option[name="current-position"]').val($highlight_position).text("No column set" + $highlight_position);
      } else {
        $edit_row.find('option[name="current-position"]').val($highlight_position).text("Column " + $highlight_position); //populate the current_position option
      }

      // console.log($highlight_position);
      switch ($highlight_position) {
        case "1":
          if ($($pos_select).children().length <= 2) { //If we haven't appended any options yet (original + hidden = 2)
            $($pos_select)
              .append('<option class="highlight-option" value="2">Column 2</option>')
              .append('<option class="highlight-option" value="3">Column 3</option>');
            // console.log("Ran case 1");
          }
          break;

        case "2": if ($($pos_select).children().length <= 2) {
          $($pos_select)
            .prepend('<option class="highlight-option" value="1">Column 1</option>')
            .append('<option class="highlight-option" value="3">Column 3</option>');
          // console.log("Ran case 2");
        }
          break;

        case "3": if ($($pos_select).children().length <= 2) {
          $($pos_select)
            .prepend('<option class="highlight-option" value="2">Column 2</option>')
            .prepend('<option class="highlight-option" value="1">Column 1</option>');
          // console.log("Ran case 3");
        }
          break;

        case "": if ($($pos_select).children().length <= 2) {
          $($pos_select)
            .append('<option class="highlight-option" value="1">Column 1</option>')
            .append('<option class="highlight-option" value="2">Column 2</option>')
            .append('<option class="highlight-option" value="3">Column 3</option>');
          // console.log("Ran case 4");
        }
      }
    }
  };
})(jQuery);
