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

use Glpi\Exception\Http\AccessDeniedHttpException;

Session::checkRight(Ticket::$rightname, UPDATE);
Session::checkRightsOr(PluginCreditTicketConfig::$rightname, [PluginCreditTicketConfig::TICKET_TAB, PluginCreditTicketConfig::TICKET_TAB]);

$PluginCreditTicket = new PluginCreditTicket();
if (isset($_POST["add"])) {
    $ticket = new Ticket();
    if (
        !$ticket->getFromDB((int) ($_POST['tickets_id'] ?? 0))
        || !$ticket->can($ticket->getID(), READ)
        || !PluginCreditTicket::canUpdateCreditsForTicket($ticket)
    ) {
        throw new AccessDeniedHttpException();
    }

    $input = [
        'tickets_id'                => $_POST['tickets_id'] ?? null,
        'plugin_credit_entities_id' => $_POST['plugin_credit_entities_id'] ?? null,
        'consumed'                  => $_POST['plugin_credit_quantity'] ?? null,
        'users_id'                  => Session::getLoginUserID(),
    ];

    if ($PluginCreditTicket->add($input)) {
        Session::addMessageAfterRedirect(
            __s('Credit voucher successfully added.', 'credit'),
            true,
            INFO,
        );
    }
}
Html::back();
