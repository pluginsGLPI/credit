<?php

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
 * @copyright Copyright (C) 2017-2022 by Credit plugin team.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/pluginsGLPI/credit
 * -------------------------------------------------------------------------
 */

class PluginCreditProfile extends Profile
{
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        return self::createTabEntry(PluginCreditTicket::getTypeName(Session::getPluralNumber()));
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        $profile = new self();
        $profile->showForm($item->getID());
        return true;
    }

    function showForm($ID, $options = []) {
        if (!self::canView()) {
           return false;
        }

        echo "<div class='spaced'>";
        $profile = new Profile();
        $profile->getFromDB($ID);
        echo "<form method='post' action='" . $profile->getFormURL() . "'>";

        $rights = [['itemtype'  => PluginCreditTicketConfig::getType(),
                    'label'     => PluginCreditTicketConfig::getTypeName(Session::getPluralNumber()),
                    'field'     => 'plugin_creditticketconfig']];
        $matrix_options['title'] = PluginCreditTicketConfig::getTypeName(Session::getPluralNumber());
        $profile->displayRightsChoiceMatrix($rights, $matrix_options);
  
        echo "<div class='center'>";
        echo Html::hidden('id', ['value' => $ID]);
        echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
        echo "</div>\n";
        Html::closeForm();
        echo "</div>";
    }
}
