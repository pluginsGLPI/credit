<?php

$USEDBREPLICATE= 1;
$DBCONNECION_REQUIRED= 0;

define('GLPI_ROOT', '../../../..');
include(GLPI_ROOT."/inc/includes.php");

$report= new PluginReportsAutoReport($LANG['plugin_credit']['creditvoucher']);
//Filtro Activo
$choices = array(0=>'--', 2 =>$LANG['creditvoucher_active'][1], 1 => $LANG['creditvoucher_active'][0]);
$filter_active=new PluginReportsArrayCriteria($report, 'is_active', $LANG['creditvoucher'][7], $choices);

$report->displayCriteriasForm();
$report->setColumns(array(new PluginReportsColumn('name', $LANG['creditvoucher'][0]),
							new PluginReportsColumn('type', $LANG['creditvoucher'][1]),
							new PluginReportsColumn('active', $LANG['creditvoucher'][7]),
							new PluginReportsColumnDate('begin_date', $LANG['creditvoucher'][2]),
							new PluginReportsColumnDate('end_date', $LANG['creditvoucher'][3]),
							new PluginReportsColumn('quantity', $LANG['creditvoucher'][4]),
							new PluginReportsColumn('consumed', $LANG['creditvoucher'][5]),
							new PluginReportsColumn('rest', $LANG['creditvoucher'][6]),
							new PluginReportsColumn('entity', $LANG['creditvoucher'][8])
						));

if ($report->criteriasValidated()) {
	$query = "SELECT `gpce`.`name`,
				`gpct`.`name` AS type,
				case when `gpce`.`is_active` = TRUE then '".$LANG['creditvoucher_active'][0]."' else '".$LANG['creditvoucher_active'][1]."' end as active,
				`gpce`.`begin_date`,
				`gpce`.`end_date`,
				`gpce`.`quantity`,
				SUM(consumed) as consumed,
				`gpce`.`quantity`-SUM(consumed) as rest,
				`ge`.`name` as entity
			FROM `glpi_plugin_credit_entities` as gpce
			inner JOIN `glpi_plugin_credit_types` as gpct ON (`gpce`.`plugin_credit_types_id` = `gpct`.`id`)
			left join glpi_plugin_credit_tickets as pct on (`pct`.`plugin_credit_entities_id` = `gpce`.`id`)
			inner JOIN `glpi_entities` as ge ON (`gpce`.`entities_id` = `ge`.`id`)".
			getEntitiesRestrictRequest(" WHERE", "gpce");
	if($filter_active->getParameterValue()==2){
		$report->delCriteria('is_active');
		$query.=" AND is_active='0' ";
	}
	$query.=$report->addSqlCriteriasRestriction()."
			GROUP BY `gpce`.id
			ORDER BY `gpce`.`name`";
}else{
	$query = "SELECT `gpce`.`name`,
				`gpct`.`name` AS type,
				case when `gpce`.`is_active` = TRUE then '".$LANG['creditvoucher_active'][0]."' else '".$LANG['creditvoucher_active'][1]."' end as active,
				`gpce`.`begin_date`,
				`gpce`.`end_date`,
				`gpce`.`quantity`,
				SUM(consumed) as consumed,
				`gpce`.`quantity`-SUM(consumed) as rest,
				`ge`.`name` as entity
			FROM `glpi_plugin_credit_entities` as gpce
			inner JOIN `glpi_plugin_credit_types` as gpct ON (`gpce`.`plugin_credit_types_id` = `gpct`.`id`)
			left join glpi_plugin_credit_tickets as pct on (`pct`.`plugin_credit_entities_id` = `gpce`.`id`)
			inner JOIN `glpi_entities` as ge ON (`gpce`.`entities_id` = `ge`.`id`)".
			getEntitiesRestrictRequest(" WHERE", "gpce")."
			GROUP BY `gpce`.id
			ORDER BY `gpce`.`name`";
}
$report->setSqlRequest($query);
$report->execute();