<?php
/**
 * --------------------------------------------------------------------------
 * LICENSE
 *
 * This file is part of credit.
 *
 * credit is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * credit is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * --------------------------------------------------------------------------
 * @author    François Legastelois
 * @copyright Copyright (C) 2017-2018 by Teclib'.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/pluginsGLPI/credit
 * @link      https://pluginsglpi.github.io/credit/
 * -------------------------------------------------------------------------
 */

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_credit_install() {

   $migration = new Migration(PLUGIN_CREDIT_VERSION);

   // Parse inc directory
   foreach (glob(dirname(__FILE__).'/inc/*') as $filepath) {
      // Load *.class.php files and get the class name
      if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
         $classname = 'PluginCredit' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if (method_exists($classname, 'install')) {
            $classname::install($migration);
         }
      }
   }
   return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_credit_uninstall() {

   $migration = new Migration(PLUGIN_CREDIT_VERSION);

   // Parse inc directory
   foreach (glob(dirname(__FILE__).'/inc/*') as $filepath) {
      // Load *.class.php files and get the class name
      if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
         $classname = 'PluginCredit' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if (method_exists($classname, 'uninstall')) {
            $classname::uninstall($migration);
         }
      }
   }
   return true;
}

/**
 * Define Dropdown tables to be manage in GLPI :
 */
function plugin_credit_getDropdown() {
   return ['PluginCreditType' => _n('Credit voucher type', 'Credit vouchers types',
                                    Session::getPluralNumber(),
                                    'credit')];
}

function plugin_credit_get_datas(NotificationTargetTicket $target) {
   global $DB;
   $target->data['##lang.credit.voucher##']=__('Credit voucher', 'credit');
   $target->data['##lang.credit.used##']=__('Quantity consumed', 'credit');
   $target->data['##lang.credit.left##']=__('Quantity remaining', 'credit');
   $id=$target->data['##ticket.id##'];
   $query = "SELECT gpce.id as id,
            count(gpct.consumed) as quantity,
            gpce.name as credit,
            rest
         FROM glpi_plugin_credit_tickets as gpct
         inner join glpi_tickets as gt on gt.id=gpct.tickets_id
         inner join glpi_plugin_credit_entities as gpce on gpce.id=gpct.plugin_credit_entities_id
         inner join (SELECT gpce.id,`gpce`.`quantity`-SUM(consumed) as rest
            FROM `glpi_plugin_credit_entities` as gpce
            left join glpi_plugin_credit_tickets as gpct on (`gpct`.`plugin_credit_entities_id` = `gpce`.`id`)
            group by gpce.id) as gpcr on gpcr.id=gpce.id
         where gt.id=".$id."
         group by id,gpce.name";
   foreach ($DB->request($query) as $id=>$credit) {
      $target->data["credit.ticket"][] =[
         '##credit.voucher##' => $credit['credit'],
         '##credit.used##' => $credit['quantity'],
         '##credit.left##' => $credit['rest'],
      ];
   }
}