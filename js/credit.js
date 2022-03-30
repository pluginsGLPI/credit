
var PluginCredit = {
    propagateDefaultVoucherValue: function (dropdown) {
       var value = $(dropdown).val();
       var text  = $(dropdown).find('option:selected').text();
       $('select[name="credit_default_followup"]').append(new Option(text, value, false, true)).trigger('change');
       $('select[name="credit_default_task"]').append(new Option(text, value, false, true)).trigger('change');
       $('select[name="credit_default_solution"]').append(new Option(text, value, false, true)).trigger('change');
    }
};
