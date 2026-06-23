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

use Glpi\Tests\DbTestCase;

class PluginCreditTicketTest extends DbTestCase
{
    private function createTicket(array $extra = []): Ticket
    {
        return $this->createItem(Ticket::class, array_merge([
            'name'        => 'Credit test ticket',
            'content'     => 'Credit test content',
            'entities_id' => 0,
        ], $extra));
    }

    private function createCreditVoucher(array $extra = []): PluginCreditEntity
    {
        $input = array_merge([
            'name'                    => 'Credit test voucher',
            'entities_id'             => 0,
            'is_active'               => 1,
            'quantity'                => 10,
            'overconsumption_allowed' => 0,
            'low_credit_alert'        => -1,
        ], $extra);

        return $this->createItem(PluginCreditEntity::class, $input, ['begin_date', 'end_date']);
    }

    public function testConsumeVoucherOnSolvedTicketFromSolutionHook(): void
    {
        $this->login('glpi', 'glpi');

        $tech = new User();
        $this->assertTrue($tech->getFromDBByCrit(['name' => 'tech']));

        $ticket = $this->createTicket();
        $credit = $this->createCreditVoucher();

        $this->createItem(Ticket_User::class, [
            'tickets_id' => $ticket->getID(),
            'users_id'   => $tech->getID(),
            'type'       => CommonITILActor::ASSIGN,
        ]);

        $this->assertTrue($ticket->update([
            'id'     => $ticket->getID(),
            'status' => CommonITILObject::SOLVED,
        ]));

        $this->login('tech', 'tech');

        $solution = new ITILSolution();
        $solution->fields = [
            'itemtype' => Ticket::class,
            'items_id' => $ticket->getID(),
        ];
        $solution->input = [
            'plugin_credit_consumed_voucher' => 1,
            'plugin_credit_entities_id'      => $credit->getID(),
            'plugin_credit_quantity'         => 1,
        ];

        PluginCreditTicket::consumeVoucher($solution);

        $this->assertSame(1, countElementsInTable(PluginCreditTicket::getTable(), [
            'tickets_id'                => $ticket->getID(),
            'plugin_credit_entities_id' => $credit->getID(),
            'consumed'                  => 1,
            'users_id'                  => $tech->getID(),
        ]));
    }

    public function testPrepareInputForAddRejectsOutOfWindowVouchers(): void
    {
        $this->login('glpi', 'glpi');

        $ticket = $this->createTicket();
        $future_credit = $this->createCreditVoucher([
            'name'       => 'Future credit test voucher',
            'begin_date' => date('Y-m-d', strtotime('+1 day')),
        ]);
        $expired_credit = $this->createCreditVoucher([
            'name'     => 'Expired credit test voucher',
            'end_date' => date('Y-m-d', strtotime('-1 day')),
        ]);

        $credit_ticket = new PluginCreditTicket();

        $this->assertFalse($credit_ticket->prepareInputForAdd([
            'tickets_id'                => $ticket->getID(),
            'plugin_credit_entities_id' => $future_credit->getID(),
            'consumed'                  => 1,
            'users_id'                  => Session::getLoginUserID(),
        ]));

        $this->assertFalse($credit_ticket->prepareInputForAdd([
            'tickets_id'                => $ticket->getID(),
            'plugin_credit_entities_id' => $expired_credit->getID(),
            'consumed'                  => 1,
            'users_id'                  => Session::getLoginUserID(),
        ]));
    }
}
