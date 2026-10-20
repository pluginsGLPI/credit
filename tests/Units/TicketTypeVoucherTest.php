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
use PluginCreditTicketConfig;
use Ticket;

final class TicketTypeVoucherTest extends DbTestCase
{
    public function testVoucherIsUsableForBothTicketTypesByDefault(): void
    {
        $this->login();

        $credit = $this->createItem(PluginCreditEntity::class, [
            'name'        => 'Default voucher',
            'entities_id' => 0,
            'is_active'   => 1,
        ]);

        $this->assertTrue($credit->isUsableForTicketType(Ticket::INCIDENT_TYPE));
        $this->assertTrue($credit->isUsableForTicketType(Ticket::DEMAND_TYPE));
    }

    public function testVoucherConsumptionIsRejectedForDisabledTicketType(): void
    {
        $this->login();

        $credit_id = $this->createItem(PluginCreditEntity::class, [
            'name'             => 'Requests only voucher',
            'entities_id'      => 0,
            'is_recursive'     => 1,
            'is_active'        => 1,
            'quantity'         => 10,
            'is_for_incidents' => 0,
            'is_for_requests'  => 1,
        ])->getID();

        $incident_id = $this->createItem(Ticket::class, [
            'name'        => 'Incident',
            'content'     => 'Test',
            'entities_id' => 0,
            'type'        => Ticket::INCIDENT_TYPE,
        ])->getID();

        $request_id = $this->createItem(Ticket::class, [
            'name'        => 'Request',
            'content'     => 'Test',
            'entities_id' => 0,
            'type'        => Ticket::DEMAND_TYPE,
        ])->getID();

        foreach ([$incident_id, $request_id] as $ticket_id) {
            $followup = new ITILFollowup();
            $this->assertGreaterThan(0, $followup->add([
                'itemtype' => Ticket::class,
                'items_id' => $ticket_id,
                'content'  => 'Followup consuming a voucher',
            ]));

            // `item_add` hooks are registered on plugin init, which occurs before login in tests,
            // so the hook is called explicitly.
            $followup->input = [
                'plugin_credit_consumed_voucher' => 1,
                'plugin_credit_entities_id'      => $credit_id,
                'plugin_credit_quantity'         => 1,
            ];
            PluginCreditTicket::consumeVoucher($followup);
        }

        $credit_ticket = new PluginCreditTicket();
        $this->assertFalse(
            $credit_ticket->getFromDBByCrit(['tickets_id' => $incident_id]),
            'Voucher consumption must not be recorded on an incident when the voucher is not usable for incidents',
        );
        $this->assertTrue(
            $credit_ticket->getFromDBByCrit(['tickets_id' => $request_id]),
            'Voucher consumption must be recorded on a request when the voucher is usable for requests',
        );
    }

    public function testConsumptionCannotBeAddedOrMovedToVoucherNotUsableForTicketType(): void
    {
        $this->login();

        $incidents_credit_id = $this->createItem(PluginCreditEntity::class, [
            'name'             => 'Incidents only voucher',
            'entities_id'      => 0,
            'is_recursive'     => 1,
            'is_active'        => 1,
            'is_for_incidents' => 1,
            'is_for_requests'  => 0,
        ])->getID();

        $requests_credit_id = $this->createItem(PluginCreditEntity::class, [
            'name'             => 'Requests only voucher',
            'entities_id'      => 0,
            'is_recursive'     => 1,
            'is_active'        => 1,
            'is_for_incidents' => 0,
            'is_for_requests'  => 1,
        ])->getID();

        $incident_id = $this->createItem(Ticket::class, [
            'name'        => 'Incident',
            'content'     => 'Test',
            'entities_id' => 0,
            'type'        => Ticket::INCIDENT_TYPE,
        ])->getID();

        $credit_ticket = new PluginCreditTicket();
        $this->assertFalse($credit_ticket->add([
            'tickets_id'                => $incident_id,
            'plugin_credit_entities_id' => $requests_credit_id,
            'consumed'                  => 1,
        ]));

        $credit_ticket_id = $credit_ticket->add([
            'tickets_id'                => $incident_id,
            'plugin_credit_entities_id' => $incidents_credit_id,
            'consumed'                  => 1,
        ]);
        $this->assertGreaterThan(0, $credit_ticket_id);

        // Quantity can still be updated
        $this->assertTrue($credit_ticket->update(['id' => $credit_ticket_id, 'consumed' => 2]));

        // Voucher cannot be changed to one not usable for incidents
        $this->assertFalse($credit_ticket->update([
            'id'                        => $credit_ticket_id,
            'plugin_credit_entities_id' => $requests_credit_id,
        ]));
        $this->assertTrue($credit_ticket->getFromDB($credit_ticket_id));
        $this->assertEquals($incidents_credit_id, $credit_ticket->fields['plugin_credit_entities_id']);
    }

    public function testTicketDefaultVoucherNotUsableForTicketTypeIsReset(): void
    {
        $this->login();

        $incidents_credit_id = $this->createItem(PluginCreditEntity::class, [
            'name'             => 'Incidents only voucher',
            'entities_id'      => 0,
            'is_recursive'     => 1,
            'is_active'        => 1,
            'is_for_incidents' => 1,
            'is_for_requests'  => 0,
        ])->getID();

        $request_id = $this->createItem(Ticket::class, [
            'name'        => 'Request',
            'content'     => 'Test',
            'entities_id' => 0,
            'type'        => Ticket::DEMAND_TYPE,
        ])->getID();

        $ticket_config = new PluginCreditTicketConfig();
        $ticket_config_id = $ticket_config->add([
            'tickets_id'                          => $request_id,
            'plugin_credit_entities_id_followups' => $incidents_credit_id,
        ]);
        $this->assertGreaterThan(0, $ticket_config_id);
        $this->assertTrue($ticket_config->getFromDB($ticket_config_id));
        $this->assertEquals(0, $ticket_config->fields['plugin_credit_entities_id_followups']);

        // Type given explicitly (ticket form, type changed in the same submission)
        $this->assertTrue($ticket_config->update([
            'id'                                  => $ticket_config_id,
            'plugin_credit_entities_id_followups' => $incidents_credit_id,
            '_ticket_type'                        => Ticket::INCIDENT_TYPE,
        ]));
        $this->assertTrue($ticket_config->getFromDB($ticket_config_id));
        $this->assertEquals($incidents_credit_id, $ticket_config->fields['plugin_credit_entities_id_followups']);
    }

    public function testHasUsableVoucherForTicketDependsOnTicketType(): void
    {
        $this->login();

        $entity_id = $this->createItem(Entity::class, [
            'name'        => 'Incidents only entity',
            'entities_id' => 0,
        ])->getID();

        $this->createItem(PluginCreditEntity::class, [
            'name'             => 'Incidents only voucher',
            'entities_id'      => $entity_id,
            'is_active'        => 1,
            'is_for_incidents' => 1,
            'is_for_requests'  => 0,
        ]);

        $incident = $this->createItem(Ticket::class, [
            'name'        => 'Incident',
            'content'     => 'Test',
            'entities_id' => $entity_id,
            'type'        => Ticket::INCIDENT_TYPE,
        ]);

        $request = $this->createItem(Ticket::class, [
            'name'        => 'Request',
            'content'     => 'Test',
            'entities_id' => $entity_id,
            'type'        => Ticket::DEMAND_TYPE,
        ]);

        $this->assertTrue(PluginCreditEntity::hasUsableVoucherForTicket($incident));
        $this->assertFalse(PluginCreditEntity::hasUsableVoucherForTicket($request));
    }
}
