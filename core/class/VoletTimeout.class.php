<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class VoletTimeout {
  function __construct($id) {
    $Volet=eqLogic::byId($id);
    if(cache::byKey('voletProp::Move::'.$Volet->getId())->getValue(false)){
      usleep($Volet->getTime('Ttotal'));
      log::add('voletProp','debug',$Volet->getHumanName()."[Timeout] Temps d'attente > ".$TempsTotal);
      $Volet->getCmd(null,'stop')->execute(null);					
    }
  }
}
?>
