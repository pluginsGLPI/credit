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
 * @author    François Legastelois
 * @copyright Copyright (C) 2017-2023 by Credit plugin team.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/pluginsGLPI/credit
 * -------------------------------------------------------------------------
 */

use Glpi\Event;

include('../../../inc/includes.php');

Session::haveRight("entity", UPDATE);

$PluginCreditEntity  = new PluginCreditEntity();

if (isset($_POST["add"])) {
    $PluginCreditEntity->check(-1, CREATE, $_POST);
    if ($PluginCreditEntity->add($_POST)) {
        Event::log(
            $_POST["plugin_credit_types_id"],
            "entity",
            4,
            "setup",
            sprintf(__('%s adds a vouchers to an entity'), $_SESSION["glpiname"])
        );
    }
    Html::back();
}

Html::displayErrorAndDie("lost");
