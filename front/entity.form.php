<?php
/*
 -------------------------------------------------------------------------
 credit plugin for GLPI
 Copyright (C) 2017 by the credit Development Team.

 https://github.com/pluginsGLPI/credit
 -------------------------------------------------------------------------

 LICENSE

 This file is part of credit.

 credit is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 credit is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with credit. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

include ('../../../inc/includes.php');

Session::haveRight("entity", UPDATE);

$Entity              = new Entity();
$PluginCreditEntity  = new PluginCreditEntity();
$PluginCreditType    = new PluginCreditType();

if (isset($_POST["add"])) {
   $PluginCreditEntity->check(-1, CREATE, $_POST);
   if ($PluginCreditEntity->add($_POST)) {
      Event::log($_POST["plugin_credit_types_id"], "entity", 4, "setup",
                 sprintf(__('%s adds a vouchers to an entity'), $_SESSION["glpiname"]));
   }
   Html::back();
}

Html::displayErrorAndDie("lost");
