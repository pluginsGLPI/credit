function changeDefaultVisibilityOptions(dropdown) {
   if (dropdown.value == 2) {
      $('.default_option_visibility').css('visibility', 'visible');
   } else {
      $('.default_option_visibility').find('select').val(dropdown.value).trigger('change');
      $('.default_option_visibility').css('visibility', 'hidden');
   }
}

function propageSelected(dropdown) {
   $('select[name="credit_default_followup"]').val(dropdown.value).trigger('change');
   $('select[name="credit_default_task"]').val(dropdown.value).trigger('change');
   $('select[name="credit_default_solution"]').val(dropdown.value).trigger('change');
}
