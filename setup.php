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

define('PLUGIN_CREDIT_VERSION', '1.1.1');

// Minimal GLPI version, inclusive
define("PLUGIN_CREDIT_MIN_GLPI", "9.2");
// Maximum GLPI version, exclusive
define("PLUGIN_CREDIT_MAX_GLPI", "9.3");

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_credit() {
   global $PLUGIN_HOOKS, $CFG_GLPI;

   $plugin = new Plugin();

   // Hack for vertical display
   if (isset($CFG_GLPI['layout_excluded_pages'])) {
      array_push($CFG_GLPI['layout_excluded_pages'], "type.form.php");
   }

   $PLUGIN_HOOKS['csrf_compliant']['credit'] = true;

   if (Session::getLoginUserID() && $plugin->isActivated('credit')) {

      if (Session::haveRight('entity', UPDATE)) {
         Plugin::registerClass('PluginCreditEntity', ['addtabon' => 'Entity']);
      }

      if (Session::haveRightsOr('ticket', [Ticket::STEAL, Ticket::OWN])) {
         Plugin::registerClass('PluginCreditTicket', ['addtabon' => 'Ticket']);

         $PLUGIN_HOOKS['post_item_form']['credit'] =
            ['PluginCreditTicket', 'postSolutionForm'];

         $PLUGIN_HOOKS['pre_item_update']['credit'] =
            ['Ticket' => ['PluginCreditTicket', 'beforeUpdate']];
      }
   }
}


/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_credit() {
   return [
      'name'           => __('Credit vouchers', 'credit'),
      'version'        => PLUGIN_CREDIT_VERSION,
      'author'         => '<a href="http://www.teclib.com">Teclib\'</a>',
      'license'        => 'GPLv3',
      'homepage'       => 'https://github.com/pluginsGLPI/credit',
      'requirements'   => [
         'glpi' => [
            'min' => PLUGIN_CREDIT_MIN_GLPI,
            'max' => PLUGIN_CREDIT_MAX_GLPI,
            'dev' => true, //Required to allow 9.2-dev
         ]
      ]
   ];
}

/**
 * Check pre-requisites before install
 *
 * @return boolean
 */
function plugin_credit_check_prerequisites() {

   // Version check not automatically done by GLPI < 9.2.
   $version = preg_replace('/^((\d+\.?)+).*$/', '$1', GLPI_VERSION);
   if (!version_compare($version, PLUGIN_CREDIT_MIN_GLPI, '>=')
       || !version_compare($version, PLUGIN_CREDIT_MAX_GLPI, '<')) {
      echo vsprintf(
         'This plugin requires GLPI >= %1$s and < %2$s',
         [
            PLUGIN_CREDIT_MIN_GLPI,
            PLUGIN_CREDIT_MAX_GLPI,
         ]
      );
      return false;
   }

   return true;
}

/**
 * Check configuration process
 *
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_credit_check_config($verbose = false) {
   return true;
}
