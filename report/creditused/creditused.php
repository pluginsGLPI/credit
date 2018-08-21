<?php

$USEDBREPLICATE= 1;
$DBCONNECION_REQUIRED= 0;

define('GLPI_ROOT', '../../../..');
include(GLPI_ROOT."/inc/includes.php");

$report= new PluginReportsAutoReport($LANG['plugin_credit']['creditused']);
//Filtro fecha
new PluginReportsDateIntervalCriteria($report, 'date', $LANG['creditused_criteria'][0]);
//Filtro activo
$choices = array(0=>'--', 2 =>$LANG['creditused_active'][1], 1 => $LANG['creditused_active'][0]);
$filter_active=new PluginReportsArrayCriteria($report, 'is_active', $LANG['creditused'][7], $choices);
//Filtro entidad
new PluginReportsDropdownCriteria($report, "gpce.id", "glpi_plugin_credit_entities", $LANG['creditused'][4]);

$report->displayCriteriasForm();
$report->setColumns(array(new PluginReportsColumn('id', $LANG['creditused'][5]),
							new PluginReportsColumn('ticket', $LANG['creditused'][0]),
							new PluginReportsColumn('active', $LANG['creditused'][7]),
							new PluginReportsColumn('categoria', $LANG['creditused'][1]),
							new PluginReportsColumnDate('date', $LANG['creditused'][2]),
							new PluginReportsColumn('quantity', $LANG['creditused'][3]),
							new PluginReportsColumn('credit', $LANG['creditused'][4]),
							new PluginReportsColumn('entity', $LANG['creditused'][6])));

if ($report->criteriasValidated()) {
	$query = "SELECT gt.id as id,
			gt.name as ticket,
			case when `gpce`.`is_active` = TRUE then '".$LANG['creditused_active'][0]."' else '".$LANG['creditused_active'][1]."' end as active,
			if(gi.name IS NULL,'".$LANG['creditused_nulo'][0]."',gi.name) as categoria,
			gpct.date_creation as date,
			gpct.consumed as quantity,
			gpce.name as credit,
			`ge`.`name` as entity
			FROM glpi_plugin_credit_tickets as gpct
			inner join glpi_tickets as gt on gt.id=gpct.tickets_id
			left join glpi_itilcategories as gi on gi.id=gt.itilcategories_id
			inner join glpi_plugin_credit_entities as gpce on gpce.id=gpct.plugin_credit_entities_id
			inner JOIN `glpi_entities` as ge ON (`gpce`.`entities_id` = `ge`.`id`)
			".getEntitiesRestrictRequest(" WHERE", "gpce");
	if($filter_active->getParameterValue()==2){
		$report->delCriteria('is_active');
		$query.=" AND is_active='0' ";
	}
	$query.=$report->addSqlCriteriasRestriction();
}else{
	$query = "SELECT gt.id as id,
			gt.name as ticket,
			case when `gpce`.`is_active` = TRUE then '".$LANG['creditused_active'][0]."' else '".$LANG['creditused_active'][1]."' end as active,
			if(gi.name IS NULL,'".$LANG['creditused_nulo'][0]."',gi.name) as categoria,
			gpct.date_creation as date,
			gpct.consumed as quantity,
			gpce.name as credit,
			`ge`.`name` as entity
			FROM glpi_plugin_credit_tickets as gpct
			inner join glpi_tickets as gt on gt.id=gpct.tickets_id
			left join glpi_itilcategories as gi on gi.id=gt.itilcategories_id
			inner join glpi_plugin_credit_entities as gpce on gpce.id=gpct.plugin_credit_entities_id
			inner JOIN `glpi_entities` as ge ON (`gpce`.`entities_id` = `ge`.`id`)
			".getEntitiesRestrictRequest(" WHERE", "gpce")." 
			 ".$report->addSqlCriteriasRestriction();
	$query.="order by gpct.id desc";
}
$report->setSqlRequest($query);
$report->execute();

    