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

class PluginCreditEntityConfig extends CommonDBTM
{
    public static $rightname = 'entity';

    public static function getTypeName($nb = 0)
    {
        return _n('Credit voucher', 'Credit vouchers', $nb, 'credit');
    }

    public static function showEntityConfigForm($entity_id)
    {
        $config = new self();
        $config->getFromDBByCrit(['entities_id' => $entity_id]);

        $out = "";
        $rand = mt_rand();
        $out .= "<div class='firstbloc'>";
        $out .= "<form name='creditentityconfig_form$rand' id='creditentityconfig_form$rand' method='post' action='";
        $out .= self::getFormUrl() . "'>";
        $out .= "<input type='hidden' name='entities_id' value='$entity_id'>";
        $out .= "<table class='tab_cadre_fixe'>";
        $out .= "<tr class='tab_bg_1'>";
        $out .= "<th colspan='8'>" . __('Default options for entity', 'credit') . "</th>";
        $out .= "</tr>";

        $out .= "<tr>";
        $out .= "<td>" . __('By default consume a voucher for followups', 'credit') . "</td>";
        $out .= "<td>";
        $out .= Dropdown::showYesNo("consume_voucher_for_followups", $config->fields['consume_voucher_for_followups'] ?? 0, -1, ['display' => false]);
        $out .= "</td>";

        $out .= "<td>" . __('By default consume a voucher for tasks', 'credit') . "</td>";
        $out .= "<td>";
        $out .= Dropdown::showYesNo("consume_voucher_for_tasks", $config->fields['consume_voucher_for_tasks'] ?? 0, -1, ['display' => false]);
        $out .= "</td>";

        $out .= "<td>" . __('By default consume a voucher for solutions', 'credit') . "</td>";
        $out .= "<td>";
        $out .= Dropdown::showYesNo("consume_voucher_for_solutions", $config->fields['consume_voucher_for_solutions'] ?? 0, -1, ['display' => false]);
        $out .= "</td>";
        $out .= "</tr>";

        $out .= "<tr>";
        $out .= "<td>" . __('Default for followups', 'credit') . "</td>";
        $out .= "<td>";
        $out .= PluginCreditEntity::dropdown(
            [
                'name'        => 'plugin_credit_entities_id_followups',
                'entity'      => $entity_id,
                'entity_sons' => true,
                'display'     => false,
                'value'       => $config->fields['plugin_credit_entities_id_followups'] ?? 0,
                'condition'   => PluginCreditEntity::getActiveFilter(),
                'comments'    => false,
                'rand'        => $rand,
            ]
        );
        $out .= "</td>";
        $out .= "<td>" . __('Default for tasks', 'credit') . "</td>";
        $out .= "<td>";
        $out .= PluginCreditEntity::dropdown(
            [
                'name'        => 'plugin_credit_entities_id_tasks',
                'entity'      => $entity_id,
                'entity_sons' => true,
                'display'     => false,
                'value'       => $config->fields['plugin_credit_entities_id_tasks'] ?? 0,
                'condition'   => PluginCreditEntity::getActiveFilter(),
                'comments'    => false,
                'rand'        => $rand,
            ]
        );
        $out .= "</td>";
        $out .= "<td>" . __('Default for solutions', 'credit') . "</td>";
        $out .= "<td>";
        $out .= PluginCreditEntity::dropdown(
            [
                'name'        => 'plugin_credit_entities_id_solutions',
                'entity'      => $entity_id,
                'entity_sons' => true,
                'display'     => false,
                'value'       => $config->fields['plugin_credit_entities_id_solutions'] ?? 0,
                'condition'   => PluginCreditEntity::getActiveFilter(),
                'comments'    => false,
                'rand'        => $rand,
            ]
        );
        $out .= "</td>";
        $out .= "</tr>";

        $out .= "</table>";
        if ($config->isNewItem()) {
            $out .= "<input type='submit' name='add' value='" . _sx('button', 'Update') . "' class='submit'>";
        } else {
            $out .= "<input type='hidden' name='id' value='{$config->getID()}'>";
            $out .= "<input type='submit' name='update' value='" . _sx('button', 'Update') . "' class='submit'>";
        }
        $out .= Html::closeForm(false);
        $out .= "</div>";
        return $out;
    }

    /**
     * Get default credit for entity and itemtype
     *
     * @param int     $entity_id
     * @param string  $itemtype
     * @param int     $ticket_type Ticket type (1=Incident, 2=Request)
     *
     * @return null|int
     */
    public static function getDefaultForEntityAndType($entity_id, $itemtype, $ticket_type = null)
    {
        $config = new self();
        $config->getFromDBByCrit(['entities_id' => $entity_id]);

        $voucher_id = null;
        switch ($itemtype) {
            case ITILFollowup::getType():
                $voucher_id = $config->fields['plugin_credit_entities_id_followups'] ?? null;
                break;

            case TicketTask::getType():
                $voucher_id = $config->fields['plugin_credit_entities_id_tasks'] ?? null;
                break;

            case ITILSolution::getType():
                $voucher_id = $config->fields['plugin_credit_entities_id_solutions'] ?? null;
                break;
        }

        if ($voucher_id && $ticket_type) {
            $criteria = array_merge(
                ['id' => $voucher_id],
                PluginCreditEntity::getActiveFilterForTicketType($ticket_type)
            );
        } else {
            $criteria = array_merge(
                ['id' => $voucher_id],
                PluginCreditEntity::getActiveFilter()
            );
        }

        if (countElementsInTable(PluginCreditEntity::getTable(), $criteria) === 0) {
            $voucher_id = null;
        }

        return $voucher_id ?: null;
    }

    public static function install(Migration $migration)
    {
        /** @var DBmysql $DB */
        global $DB;

        $default_charset = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

        $table = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = <<<SQL
                CREATE TABLE IF NOT EXISTS `$table` (
                    `id` int {$default_key_sign} NOT NULL auto_increment,
                    `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                    `consume_voucher_for_followups` tinyint NOT NULL DEFAULT '0',
                    `consume_voucher_for_tasks` tinyint NOT NULL DEFAULT '0',
                    `consume_voucher_for_solutions` tinyint NOT NULL DEFAULT '0',
                    `plugin_credit_entities_id_followups` int {$default_key_sign} NOT NULL DEFAULT '0',
                    `plugin_credit_entities_id_tasks` int {$default_key_sign} NOT NULL DEFAULT '0',
                    `plugin_credit_entities_id_solutions` int {$default_key_sign} NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`),
                    KEY `entities_id` (`entities_id`),
                    KEY `plugin_credit_entities_id_followups` (`plugin_credit_entities_id_followups`),
                    KEY `plugin_credit_entities_id_tasks` (`plugin_credit_entities_id_tasks`),
                    KEY `plugin_credit_entities_id_solutions` (`plugin_credit_entities_id_solutions`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
            $DB->doQuery($query);
        }

        // During 1.10.0 dev phase, entity config were stored in GLPI config table
        $configs = Config::getConfigurationValues('plugin:credit');
        foreach ($configs as $key => $config) {
            $entity_match = [];
            if (preg_match('/^Entity-(?<id>\d+)/', $key, $entity_match) !== 1) {
                continue;
            }
            $entity_id = $entity_match['id'];
            $values = @json_decode($config, true);
            $input = [
                'entities_id'                   => $entity_id,
                'consume_voucher_for_followups' => $values['consume_voucher_followups'] ?? 0,
                'consume_voucher_for_tasks'     => $values['consume_voucher_tasks'] ?? 0,
                'consume_voucher_for_solutions' => $values['consume_voucher_solution'] ?? 0,
            ];

            $entity_config = new self();
            if ($entity_config->getFromDBByCrit(['entities_id' => $entity_id])) {
                $entity_config->update(['id' => $entity_config->getID()] + $input);
            } else {
                $entity_config->add($input);
            }
            Config::deleteConfigurationValues('plugin:credit', [$key]);
        }

        // During 1.10.0 dev phase, defaults were defined by a boulean on glpi_plugin_credit_entities.
        $vouchers_table = PluginCreditEntity::getTable();
        if (
            $DB->fieldExists($vouchers_table, 'is_default_followup')
            || $DB->fieldExists($vouchers_table, 'is_default_task')
            || $DB->fieldExists($vouchers_table, 'is_default_solution')
        ) {
            $vouchers_iterator = $DB->request(['FROM' => PluginCreditEntity::getTable(), 'WHERE' => PluginCreditEntity::getActiveFilter()]);
            foreach ($vouchers_iterator as $voucher_data) {
                $is_default_for_followups = $voucher_data['is_default_followup'] ?? 0;
                $is_default_for_tasks     = $voucher_data['is_default_task'] ?? 0;
                $is_default_for_solutions = $voucher_data['is_default_solution'] ?? 0;

                if (!$is_default_for_followups && !$is_default_for_tasks && !$is_default_for_solutions) {
                    continue;
                }
                $entities_id = $voucher_data['entities_id'];
                $input = [
                    'entities_id' => $entities_id,
                ];
                if ($is_default_for_followups) {
                    $input['plugin_credit_entities_id_followups'] = $voucher_data['id'];
                }
                if ($is_default_for_tasks) {
                    $input['plugin_credit_entities_id_tasks'] = $voucher_data['id'];
                }
                if ($is_default_for_solutions) {
                    $input['plugin_credit_entities_id_solutions'] = $voucher_data['id'];
                }
                $entity_config = new self();
                if ($entity_config->getFromDBByCrit(['entities_id' => $voucher_data['entities_id']])) {
                    $entity_config->update(['id' => $entity_config->getID()] + $input);
                } else {
                    $entity_config->add($input);
                }
            }
            $migration->dropField($vouchers_table, 'is_default_followup');
            $migration->dropField($vouchers_table, 'is_default_task');
            $migration->dropField($vouchers_table, 'is_default_solution');
        }
    }

    public static function uninstall(Migration $migration)
    {
        $table = self::getTable();
        $migration->dropTable($table);
    }
}
