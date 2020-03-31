<?php
/**
 * --------------------------------------------------------------------------
 * LICENSE
 *
 * This file is part of credit.
 *
 * credit is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * credit is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * --------------------------------------------------------------------------
 * @author    François Legastelois
 * @copyright Copyright (C) 2017-2018 by Teclib'.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/pluginsGLPI/credit
 * @link      https://pluginsglpi.github.io/credit/
 * -------------------------------------------------------------------------
 */

/** @file
* @brief
*/

use Glpi\Event;

include ('../../../inc/includes.php');

Session::haveRight("entity", UPDATE);

$ticketConfig = new PluginCreditTicketConfig;

if (isset($_POST["update"])) {
   $ticketConfig->check(-1, UPDATE, $_POST);
   $ticketConfig->update($_POST);
   Html::back();
}

if (isset($_POST["add"])) {
   $ticketConfig->check(-1, UPDATE, $_POST);
   $ticketConfig->add($_POST);
   Html::back();
}

Html::displayErrorAndDie("lost");
