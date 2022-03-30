
var PluginCredit = {
    propagateDefaultVoucherValue: function (dropdown) {
       var value = $(dropdown).val();
       var text  = $(dropdown).find('option:selected').text();
       $('select[name="plugin_credit_entities_id_followups"]').append(new Option(text, value, false, true)).trigger('change');
       $('select[name="plugin_credit_entities_id_tasks"]').append(new Option(text, value, false, true)).trigger('change');
       $('select[name="plugin_credit_entities_id_solutions"]').append(new Option(text, value, false, true)).trigger('change');
    }
};
