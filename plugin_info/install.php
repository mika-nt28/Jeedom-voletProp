<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
function voletProp_install(){
}
function voletProp_update(){
	log::add('voletProp','debug','Lancement du script de mise a jours'); 
	foreach(eqLogic::byType('voletProp') as $voletProp){
		$voletProp->save();
	}
	log::add('voletProp','debug','Fin du script de mise a jours');
}
function voletProp_remove(){
}
?>
