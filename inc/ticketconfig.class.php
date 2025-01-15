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

class PluginCreditTicketConfig extends CommonDBTM
{
    public static $rightname = 'plugin_credit_ticketconfig';

    const TICKET_TAB  = 1024;
    const TICKET_FORM = 2048;

    public static function getTypeName($nb = 0)
    {
        return _n('Default voucher option', 'Default voucher options', $nb, 'credit');
    }

    /**
     * Get default credit for ticket and itemtype
     *
     * @param int     $ticket_id
     * @param string  $itemtype
     *
     * @return null|int
     */
    public static function getDefaultForTicket($ticket_id, $itemtype)
    {
        $ticket_config = new self();
        $ticket_config->getFromDBByCrit(['tickets_id' => $ticket_id]);

        $voucher_id = null;
        switch ($itemtype) {
            case ITILFollowup::getType():
                $voucher_id = $ticket_config->fields['plugin_credit_entities_id_followups'] ?? null;
                break;

            case TicketTask::getType():
                $voucher_id = $ticket_config->fields['plugin_credit_entities_id_tasks'] ?? null;
                break;

            case ITILSolution::getType():
                $voucher_id = $ticket_config->fields['plugin_credit_entities_id_solutions'] ?? null;
                break;
        }

        $criteria = array_merge(
            ['id' => $voucher_id],
            PluginCreditEntity::getActiveFilter()
        );
        if (countElementsInTable(PluginCreditEntity::getTable(), $criteria) === 0) {
            $voucher_id = null;
        }

        return $voucher_id ?: null;
    }


    /**
     * Show default credit option ticket
     *
     * @param Ticket $ticket
     * @param bool $embed_in_ticket_form
     */
    public static function showForTicket(Ticket $ticket, bool $embed_in_ticket_form = false)
    {

        if ($embed_in_ticket_form && !Session::haveRight(self::$rightname, self::TICKET_FORM)) {
            return;
        }
        if (!$embed_in_ticket_form && !Session::haveRight(self::$rightname, self::TICKET_TAB)) {
            return;
        }

        //load ticket configuration
        $ticket_config = new PluginCreditTicketConfig();
        if (!$ticket->isNewItem()) {
            $ticket_config->getFromDBByCrit(["tickets_id" => $ticket->getID()]);
        }

        $rand = mt_rand();
        $out = "";

        $default_ticket_dropdown = PluginCreditEntity::dropdown(
            [
                'name'        => 'plugin_credit_entities_id_default',
                'entity'      => $ticket->getEntityID(),
                'entity_sons' => true,
                'display'     => false,
                'value'       => $ticket->input['plugin_credit_entities_id_default'] ?? 0,
                'condition'   => PluginCreditEntity::getActiveFilter(),
                'comments'    => false,
                'rand'        => $rand,
                'on_change'   => 'PluginCredit.propagateDefaultVoucherValue(this)',
                'width'       => $embed_in_ticket_form ? '100%' : '',
            ]
        );
        $default_fup_dropdown = PluginCreditEntity::dropdown(
            [
                'name'        => 'plugin_credit_entities_id_followups',
                'entity'      => $ticket->getEntityID(),
                'entity_sons' => true,
                'display'     => false,
                'value'       => $ticket->input['plugin_credit_entities_id_followups'] ??
                                 $ticket_config->fields['plugin_credit_entities_id_followups'] ?? 0,
                'condition'   => PluginCreditEntity::getActiveFilter(),
                'comments'    => false,
                'rand'        => $rand,
                'width'       => $embed_in_ticket_form ? '100%' : '',
            ]
        );
        $default_tasks_dropdown = PluginCreditEntity::dropdown(
            [
                'name'        => 'plugin_credit_entities_id_tasks',
                'entity'      => $ticket->getEntityID(),
                'entity_sons' => true,
                'display'     => false,
                'value'       => $ticket->input['plugin_credit_entities_id_tasks'] ??
                                 $ticket_config->fields['plugin_credit_entities_id_tasks'] ?? 0,
                'condition'   => PluginCreditEntity::getActiveFilter(),
                'comments'    => false,
                'rand'        => $rand,
                'width'       => $embed_in_ticket_form ? '100%' : '',
            ]
        );
        $default_sol_dropdown = PluginCreditEntity::dropdown(
            [
                'name'        => 'plugin_credit_entities_id_solutions',
                'entity'      => $ticket->getEntityID(),
                'entity_sons' => true,
                'display'     => false,
                'value'       => $ticket->input['plugin_credit_entities_id_solutions'] ??
                                 $ticket_config->fields['plugin_credit_entities_id_solutions'] ?? 0,
                'condition'   => PluginCreditEntity::getActiveFilter(),
                'comments'    => false,
                'rand'        => $rand,
                'width'       => $embed_in_ticket_form ? '100%' : '',
            ]
        );

        if ($embed_in_ticket_form) {
            $uncollapsed = (importArrayFromDB(Config::getSafeConfig()['itil_layout'])['items']['plugin-credit-ticket-config'] ?? 'true') == 'true';
            $out .= '</div>'; // class="accordion-body"
            $out .= '</div>'; // class="accordion-collapse"
            $out .= '</div>'; // class="accordion-item"
            $out .= '<div class="accordion-item">';
            $out .= '<h2 class="accordion-header" id="heading-plugin-credit-ticket-config">';
            $out .= '<button class="accordion-button ' . ($uncollapsed ? '' : 'collapsed') . '" type="button" data-bs-toggle="collapse" data-bs-target="#plugin-credit-ticket-config" aria-expanded="true" aria-controls="plugin-credit-ticket-config">';
            $out .= '<span class="item-title">';
            $out .= self::getTypeName();
            $out .= '</span>';
            $out .= '</button>';
            $out .= '</h2>';
            $out .= '<div id="plugin-credit-ticket-config" class="accordion-collapse collapse ' . ($uncollapsed ? 'show' : '') . '" aria-labelledby="heading-plugin-credit-ticket-config">';
            $out .= '<div class="accordion-body row m-0 mt-n2">';

            $out .= '<div class="form-field row col-12  mb-2">';
            $out .= '<label class="col-form-label col-xxl-4 text-xxl-end">';
            $out .= __('Default for ticket', 'credit');
            $out .= '</label>';
            $out .= '<div class="col-xxl-8 field-container">';
            $out .= $default_ticket_dropdown;
            $out .= '</div>';
            $out .= '</div>';

            $out .= '<div class="form-field row col-12  mb-2">';
            $out .= '<label class="col-form-label col-xxl-4 text-xxl-end">';
            $out .= __('Default for followups', 'credit');
            $out .= '</label>';
            $out .= '<div class="col-xxl-8 field-container">';
            $out .= $default_fup_dropdown;
            $out .= '</div>';
            $out .= '</div>';

            $out .= '<div class="form-field row col-12  mb-2">';
            $out .= '<label class="col-form-label col-xxl-4 text-xxl-end">';
            $out .= __('Default for tasks', 'credit');
            $out .= '</label>';
            $out .= '<div class="col-xxl-8 field-container">';
            $out .= $default_tasks_dropdown;
            $out .= '</div>';
            $out .= '</div>';

            $out .= '<div class="form-field row col-12  mb-2">';
            $out .= '<label class="col-form-label col-xxl-4 text-xxl-end">';
            $out .= __('Default for solutions', 'credit');
            $out .= '</label>';
            $out .= '<div class="col-xxl-8 field-container">';
            $out .= $default_sol_dropdown;
            $out .= '</div>';
            $out .= '</div>';

            // $out .= '</div>'; // class="accordion-body"
            // $out .= '</div>'; // class="accordion-collapse"
            // $out .= '</div>'; // class="accordion-item"
        } else {
            $out .= "<form method='post' action='" . self::getFormUrl() . "'>";
            $out .= "<input type='hidden' name='tickets_id' value='{$ticket->getID()}' />";
            $out .= "<table class='tab_cadre_fixe'><tbody>";
            $out .= "<tr>";
            $out .= "<th colspan='8'>" . self::getTypeName() . "</th>";
            $out .= "</tr>";
            $out .= "<tr>";
            $out .= "<td>" . __('Default for ticket', 'credit') . "</td>";
            $out .= "<td>";
            $out .= $default_ticket_dropdown;
            $out .= "</td>";
            $out .= "<td>" . __('Default for followups', 'credit') . "</td>";
            $out .= "<td>";
            $out .= $default_fup_dropdown;
            $out .= "</td>";
            $out .= "<td>" . __('Default for tasks', 'credit') . "</td>";
            $out .= "<td>";
            $out .= $default_tasks_dropdown;
            $out .= "</td>";
            $out .= "<td>" . __('Default for solutions', 'credit') . "</td>";
            $out .= "<td>";
            $out .= $default_sol_dropdown;
            $out .= "</td>";
            $out .= "</tr>";
            $out .= "<tr>";
            $out .= "<td colspan='8' class='center'>";
            $out .= "<input type='submit' name='update' value='" . _sx('button', 'Update') . "' class='submit'>";
            $out .= "</td>";
            $out .= "</tr>";
            $out .= "</tbody></table>";
            $out .= Html::closeForm(false);
        }

        return $out;
    }

    public static function updateConfig(Ticket $ticket)
    {
        if (!Session::haveRight(self::$rightname, self::TICKET_FORM)) {
            return;
        }

        $input = [];

        $config_fields = [
            'plugin_credit_entities_id_followups',
            'plugin_credit_entities_id_tasks',
            'plugin_credit_entities_id_solutions',
        ];
        foreach ($config_fields as $field) {
            if (array_key_exists($field, $ticket->input)) {
                $input[$field] = $ticket->input[$field];
            }
        }

        if (empty($input)) {
            return;
        }
        $input['tickets_id'] = $ticket->getID();

        $ticket_config = new self();
        if ($ticket_config->getFromDBByCrit(['tickets_id' => $ticket->getID()])) {
            $ticket_config->update(['id' => $ticket_config->getID()] + $input);
        } else {
            $ticket_config->add($input);
        }
    }

    public static function install(Migration $migration)
    {
        /** @var DBmysql $DB */
        global $DB;

        $table = self::getTable();

        $default_charset = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

        if (!$DB->tableExists($table)) {
            $query = <<<SQL
                CREATE TABLE IF NOT EXISTS `$table` (
                    `id` int {$default_key_sign} NOT NULL auto_increment,
                    `tickets_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                    `credit_default` tinyint NOT NULL DEFAULT '0',
                    `plugin_credit_entities_id_followups` int {$default_key_sign} NOT NULL DEFAULT '0',
                    `plugin_credit_entities_id_tasks` int {$default_key_sign} NOT NULL DEFAULT '0',
                    `plugin_credit_entities_id_solutions` int {$default_key_sign} NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`),
                    KEY `tickets_id` (`tickets_id`),
                    KEY `plugin_credit_entities_id_followups` (`plugin_credit_entities_id_followups`),
                    KEY `plugin_credit_entities_id_tasks` (`plugin_credit_entities_id_tasks`),
                    KEY `plugin_credit_entities_id_solutions` (`plugin_credit_entities_id_solutions`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
            $DB->doQuery($query);
        }

        // During 1.10.0 dev phase, fields were named differently and had no keys
        $migration->changeField($table, 'credit_default_followup', 'plugin_credit_entities_id_followups', "int {$default_key_sign} NOT NULL DEFAULT '0'");
        $migration->changeField($table, 'credit_default_task', 'plugin_credit_entities_id_tasks', "int {$default_key_sign} NOT NULL DEFAULT '0'");
        $migration->changeField($table, 'credit_default_solution', 'plugin_credit_entities_id_solutions', "int {$default_key_sign} NOT NULL DEFAULT '0'");
        $migration->migrationOneTable($table);
        $migration->addKey($table, 'plugin_credit_entities_id_followups');
        $migration->addKey($table, 'plugin_credit_entities_id_tasks');
        $migration->addKey($table, 'plugin_credit_entities_id_solutions');

        $migration->dropField($table, 'credit_default'); // Was created during dev phase of 1.10.0, then removed

        $migration->addKey($table, 'tickets_id');
    }

    public static function uninstall(Migration $migration)
    {
        $table = self::getTable();
        $migration->dropTable($table);
    }

    public function getRights($interface = 'central')
    {
        return [
            self::TICKET_TAB  => __('Update in ticket tab', 'credit'),
            self::TICKET_FORM => __('Update in ticket form', 'credit'),
        ];
    }
}
