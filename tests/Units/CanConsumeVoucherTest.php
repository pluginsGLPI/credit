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

use DBmysql;
use Entity;
use Glpi\Tests\DbTestCase;
use PluginCreditEntity;
use Session;

final class CanConsumeVoucherTest extends DbTestCase
{
    public function testVoucherWithIdNotMatchingAnyEntityCanBeConsumed(): void
    {
        /** @var DBmysql $DB */
        global $DB;

        $this->login();

        // Ensure the voucher ID does not match any entity ID
        $max_entity_id = (int) $DB->request([
            'SELECT' => ['MAX' => 'id AS max_id'],
            'FROM'   => Entity::getTable(),
        ])->current()['max_id'];
        $credit_id = $max_entity_id + 1000;

        $this->assertTrue($DB->insert(PluginCreditEntity::getTable(), [
            'id'           => $credit_id,
            'name'         => 'Voucher with ID not matching any entity',
            'entities_id'  => 0,
            'is_recursive' => 1,
            'is_active'    => 1,
            'quantity'     => 10,
        ]));

        $this->assertTrue(PluginCreditEntity::canConsumeVoucher($credit_id));
    }

    public function testVoucherFromInaccessibleEntityCannotBeConsumed(): void
    {
        $this->login();

        $accessible_entity_id = $this->createItem(Entity::class, [
            'name'        => 'Accessible entity',
            'entities_id' => 0,
        ])->getID();

        $restricted_entity_id = $this->createItem(Entity::class, [
            'name'        => 'Restricted entity',
            'entities_id' => 0,
        ])->getID();

        $credit_id = $this->createItem(PluginCreditEntity::class, [
            'name'         => 'Restricted credit voucher',
            'entities_id'  => $restricted_entity_id,
            'is_recursive' => 1,
            'is_active'    => 1,
            'quantity'     => 10,
        ])->getID();

        $this->assertTrue(Session::changeActiveEntities($accessible_entity_id));

        $this->assertFalse(PluginCreditEntity::canConsumeVoucher($credit_id));
    }

    public function testRecursiveVoucherFromParentEntityCanBeConsumed(): void
    {
        $this->login();

        $child_entity_id = $this->createItem(Entity::class, [
            'name'        => 'Child entity',
            'entities_id' => 0,
        ])->getID();

        $recursive_credit_id = $this->createItem(PluginCreditEntity::class, [
            'name'         => 'Recursive parent credit voucher',
            'entities_id'  => 0,
            'is_recursive' => 1,
            'is_active'    => 1,
            'quantity'     => 10,
        ])->getID();

        $non_recursive_credit_id = $this->createItem(PluginCreditEntity::class, [
            'name'         => 'Non recursive parent credit voucher',
            'entities_id'  => 0,
            'is_recursive' => 0,
            'is_active'    => 1,
            'quantity'     => 10,
        ])->getID();

        $this->assertTrue(Session::changeActiveEntities($child_entity_id));

        $this->assertTrue(PluginCreditEntity::canConsumeVoucher($recursive_credit_id));
        $this->assertFalse(PluginCreditEntity::canConsumeVoucher($non_recursive_credit_id));
    }

    public function testUnknownVoucherCannotBeConsumed(): void
    {
        $this->login();

        $this->assertFalse(PluginCreditEntity::canConsumeVoucher(PHP_INT_MAX));
    }
}
