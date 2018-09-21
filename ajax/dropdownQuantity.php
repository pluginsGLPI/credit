<?php

include ("../../../inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST["entity"])) {

   $query = "SELECT `glpi_plugin_credit_entities`.`quantity`,
   					(SELECT SUM(`glpi_plugin_credit_tickets`.`consumed`) FROM `glpi_plugin_credit_tickets` WHERE `glpi_plugin_credit_tickets`.`plugin_credit_entities_id` = `glpi_plugin_credit_entities`.`id`) AS  `consumed_total`
			FROM `glpi_plugin_credit_entities`
			WHERE `glpi_plugin_credit_entities`.`id`={$_POST['entity']}";

   $result = $DB->query($query);
   $data = $DB->fetch_assoc($result);
   $max=$data['quantity']-$data['consumed_total'];
   Dropdown::showNumber("plugin_credit_quantity", ['value'   => '',
                                                   'min'     => 0,
                                                   'max'     => (int)$max,
                                                   'step'    => 1,]);
}