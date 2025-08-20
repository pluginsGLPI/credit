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

Session::haveRight("ticket", UPDATE);

$PluginCreditTicket = new PluginCreditTicket();
if ($_REQUEST['plugin_credit_entities_id'] == 0) {
    Session::addMessageAfterRedirect(
        __s('Credit voucher entity must be selected.', 'credit'),
        true,
        ERROR
    );
    Html::back();
} elseif ($_REQUEST['plugin_credit_quantity'] == 0) {
    Session::addMessageAfterRedirect(
        __s('Credit voucher quantity must be greater than 0.', 'credit'),
        true,
        ERROR
    );
    Html::back();
}
$input = [
    'tickets_id'                => $_REQUEST['tickets_id'],
    'plugin_credit_entities_id' => $_REQUEST['plugin_credit_entities_id'],
    'consumed'                  => $_REQUEST['plugin_credit_quantity'],
    'users_id'                  => Session::getLoginUserID(),
];
if ($PluginCreditTicket->add($input)) {
    Session::addMessageAfterRedirect(
        __s('Credit voucher successfully added.', 'credit'),
        true,
        INFO
    );
    Html::back();
}

throw new Glpi\Exception\Http\BadRequestHttpException();
