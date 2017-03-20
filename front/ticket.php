<?php
/*
 -------------------------------------------------------------------------
 intervention plugin for GLPI
 Copyright (C) 2017 by the intervention Development Team.

 https://github.com/pluginsGLPI/intervention
 -------------------------------------------------------------------------

 LICENSE

 This file is part of intervention.

 intervention is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 intervention is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with intervention. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

 /** @file
 * @brief
 */

include ('../../../inc/includes.php');

Html::popHeader(__('Setup'), $_SERVER['PHP_SELF'], true);

if (!isset($_GET["pluginterventionentity"])) {
   throw new \RuntimeException('Invalid params provided!', 'intervention');
} else {
   $_GET['pluginterventionentity'] = intval($_GET['pluginterventionentity']);
}

Session::checkLoginUser();

Session::checkRightsOr('ticket', [Ticket::STEAL, Ticket::OWN]);

PluginInterventionTicket::displayConsumed($_GET['pluginterventionentity']);

Html::popFooter();
