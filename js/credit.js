function changeDefaultVisibilityOptions(dropdown){
   if (dropdown.value == 2) {
      $( ".default_option_visibility" ).show();
   } else if (dropdown.value == 1 ) {
      $( ".default_option_visibility" ).find("select").val('1').trigger('change');
   } else {
      $( ".default_option_visibility" ).find("select").val('0').trigger('change');
      $( ".default_option_visibility" ).hide();
   }
}


function propageSelected(dropdown){
   $( "select[name='credit_default_followup'" ).val(dropdown.value ).trigger('change');
   $( "select[name='credit_default_task'" ).val(dropdown.value ).trigger('change');
   $( "select[name='credit_default_solution'" ).val(dropdown.value ).trigger('change');
}