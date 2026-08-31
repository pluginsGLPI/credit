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

namespace GlpiPlugin\Credit\Tests\Units;

use Entity;
use Glpi\Tests\DbTestCase;
use ITILFollowup;
use PluginCreditEntity;
use PluginCreditTicket;
use Session;
use Ticket;

final class ConsumeVoucherTest extends DbTestCase
{
    public function testVoucherConsumptionIsRejectedWhenCreditEntityIsNotAccessible(): void
    {
        $this->login();

        $accessible_entity_id = $this->createItem(Entity::class, [
            'name' => 'Accessible entity',
            'entities_id' => 0,
        ])->getID();

        $restricted_entity_id = $this->createItem(Entity::class, [
            'name' => 'Restricted entity',
            'entities_id' => 0,
        ])->getID();

        $credit_entity_id = $this->createItem(PluginCreditEntity::class, [
            'name' => 'Restricted credit voucher',
            'entities_id' => $restricted_entity_id,
            'is_recursive' => 0,
            'is_active' => 1,
            'quantity' => 10,
        ])->getID();

        $ticket_id = $this->createItem(Ticket::class, [
            'name' => 'Consume voucher test',
            'content' => 'Test',
            'entities_id' => $accessible_entity_id,
        ])->getID();

        $this->assertTrue(Session::changeActiveEntities($accessible_entity_id));

        $followup = new ITILFollowup();
        $followup_id = $followup->add([
            'itemtype' => Ticket::class,
            'items_id' => $ticket_id,
            'content' => 'Followup consuming a voucher from an inaccessible entity',
            'plugin_credit_consumed_voucher' => 1,
            'plugin_credit_entities_id' => $credit_entity_id,
            'plugin_credit_quantity' => 1,
        ]);
        $this->assertGreaterThan(0, $followup_id);

        $credit_ticket = new PluginCreditTicket();
        $this->assertFalse(
            $credit_ticket->getFromDBByCrit(['tickets_id' => $ticket_id]),
            'Voucher consumption must not be recorded when the credit entity is outside the accessible entities',
        );
    }
}
