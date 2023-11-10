/**
 * -------------------------------------------------------------------------
 * Credit plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Credit.
 *
 * Credit is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * Credit is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Credit. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @author    François Legastelois
 * @copyright Copyright (C) 2017-2023 by Credit plugin team.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/pluginsGLPI/credit
 * -------------------------------------------------------------------------
 */

var PluginCredit = {
    propagateDefaultVoucherValue: function (dropdown) {
        var value = $(dropdown).val();
        var text  = $(dropdown).find('option:selected').text();
        $('select[name="plugin_credit_entities_id_followups"]').append(new Option(text, value, false, true)).trigger('change');
        $('select[name="plugin_credit_entities_id_tasks"]').append(new Option(text, value, false, true)).trigger('change');
        $('select[name="plugin_credit_entities_id_solutions"]').append(new Option(text, value, false, true)).trigger('change');
    }
};
