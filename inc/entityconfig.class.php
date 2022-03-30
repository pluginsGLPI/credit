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
 * @copyright Copyright (C) 2017-2022 by Credit plugin team.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/pluginsGLPI/credit
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginCreditEntityConfig extends CommonDBTM {

   public static $rightname = 'entity';

   static function getTypeName($nb = 0) {
      return _n('Credit voucher', 'Credit vouchers', $nb, 'credit');
   }

   static function showEntityConfig(Entity $entity, $itemtype = 'Entity') {

         $ID = $entity->getField('id');

         //get configuration values
         $entityConfig = new self();
         $values = $entityConfig->getConfigurationValues($ID);

         $out = "";
         $rand = mt_rand();
         $out .= "<div class='firstbloc'>";
         $out .= "<form name='creditentityconfig_form$rand' id='creditentityconfig_form$rand' method='post' action='";
         $out .= self::getFormUrl()."'>";
         $out .= "<input type='hidden' name='config_name' value='".Entity::getType()."-"."$ID'>";
         $out .= "<table class='tab_cadre_fixe'>";
         $out .= "<tr class='tab_bg_1'>";
         $out .= "<th colspan='8'>" . __('Default options for entity', 'credit') . "</th>";
         $out .= "</tr>";

         $out .= "<td>".__('By default consume a voucher for followups', 'credit')."</td>";
         $out .= "<td>";
         $out .= Dropdown::showYesNo("consume_voucher_followups", $values['consume_voucher_followups'], -1, ['display' => false]);
         $out .= "</td>";

         $out .= "<td>".__('By default consume a voucher for tasks', 'credit')."</td>";
         $out .= "<td>";
         $out .= Dropdown::showYesNo("consume_voucher_tasks", $values['consume_voucher_tasks'], -1, ['display' => false]);
         $out .= "</td>";

         $out .= "<td>".__('By default consume a voucher for solutions', 'credit')."</td>";
         $out .= "<td>";
         $out .= Dropdown::showYesNo("consume_voucher_solution", $values['consume_voucher_solution'], -1, ['display' => false]);
         $out .= "</td>";

         $out .= "</table>";
         $out .= "<input type='submit' name='update' value='"._sx('button', 'Update')."' class='submit'>";
         $out .= Html::closeForm(false);
         $out .= "</div>";
         echo $out;
   }


   public function saveConfiguration($data) {
      $values[$data['config_name']] = json_encode([
         'consume_voucher_followups' => $data['consume_voucher_followups'],
         'consume_voucher_tasks' => $data['consume_voucher_tasks'],
         'consume_voucher_solution' => $data['consume_voucher_solution']]);
      Config::setConfigurationValues('plugin:credit', $values);
   }

   public function getConfigurationValues($entity_id) {
      $config_key = sprintf('%s-%s', Entity::getType(), $entity_id);
      $config = Config::getConfigurationValues('plugin:credit', [$config_key])[$config_key] ?? '[]';

      $values = json_decode($config, true);

      if (empty($values)) {
         $values = [
            'consume_voucher_followups' => 0,
            'consume_voucher_tasks'     => 0,
            'consume_voucher_solution'  => 0,
         ];
      }

      return $values;
   }
}
