
function propageSelected(dropdown) {
   $('select[name="credit_default_followup"]').val(dropdown.value).trigger('change');
   $('select[name="credit_default_task"]').val(dropdown.value).trigger('change');
   $('select[name="credit_default_solution"]').val(dropdown.value).trigger('change');
}
