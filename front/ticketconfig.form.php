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

use Glpi\Exception\Http\BadRequestHttpException;

Session::checkLoginUser();

$ticket_config = new PluginCreditTicketConfig();

if (!Session::haveRight($ticket_config::$rightname, PluginCreditTicketConfig::TICKET_TAB)) {
    throw new BadRequestHttpException();
}

if (isset($_POST["update"])) {
    $tickets_id = (int) $_POST['tickets_id'];
    if ($ticket_config->getFromDBByCrit(['tickets_id' => $tickets_id])) {
        $_POST['id'] = $ticket_config->getID();
        $ticket_config->update($_POST);
    } else {
        $ticket_config->add($_POST);
    }
    Html::back();
}

throw new BadRequestHttpException();
