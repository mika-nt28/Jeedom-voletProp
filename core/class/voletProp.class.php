<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class voletProp extends eqLogic {
	public static function deamon_info() {
		$return = array();
		$return['log'] = 'voletProp';
		$return['launchable'] = 'ok';
		$return['state'] = 'nok';
		foreach(eqLogic::byType('voletProp') as $Volet){
			if($Volet->getIsEnable()){
				$listener = listener::byClassAndFunction('voletProp', 'pull', array('Volets_id' => $Volet->getId()));
				if (!is_object($listener))
					return $return;
			}
		}
		$return['state'] = 'ok';
		return $return;
	}
	public static function deamon_start($_debug = false) {
		log::remove('voletProp');
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') 
			return;
		if ($deamon_info['state'] == 'ok') 
			return;
		foreach(eqLogic::byType('voletProp') as $Volet)
			$Volet->StartListener();
	}
	public static function deamon_stop() {	
		foreach(eqLogic::byType('voletProp') as $Volet){
			$listener = listener::byClassAndFunction('voletProp', 'pull', array('Volets_id' => $Volet->getId()));
			if (is_object($listener))
				$listener->remove();
		}
	}
	public static function pull($_option) {
		$Volet = voletProp::byId($_option['Volets_id']);
		if (is_object($Volet) && $Volet->getIsEnable()) {
			$Event = cmd::byId($_option['event_id']);
			if(is_object($Event)){
				
			}
		}
	}
    	public function execPropVolet($Hauteur) {
		$HauteurVolet=$this->getCmd(null,'hauteur')->execCmd();
		if($HauteurVolet > $Hauteur){
			$cmd=cmd::byId(str_replace('#','',$this->getConfiguration('cmdDown')));
			if(!is_object($cmd))
				return false;
			$cmd->event(null);
			$Delta=$HauteurVolet-$Hauteur;
			log::add('voletProp','debug',$this->getHumanName().' Nous allons descendre le volet de '.$Delta.'%');
		}else{
			$cmd=cmd::byId(str_replace('#','',$this->getConfiguration('cmdUp')));
			if(!is_object($cmd))
				return false;
			$cmd->event(null);
			$Delta=$Hauteur-$HauteurVolet;
			log::add('voletProp','debug',$this->getHumanName().' Nous allons monter le volet de '.$Delta.'%');
		}
		sleep($this->TpsAction($Delta));
		$cmd=cmd::byId(str_replace('#','',$this->getConfiguration('cmdStop')));
		if(!is_object($cmd))
			return false;
		$cmd->event(null);
		log::add('voletProp','debug',$this->getHumanName().' Le volet est a '.$Hauteur.'%');
		$this->checkAndUpdateCmd('hauteur',$Hauteur);
	}
    	public function TpsAction($Hauteur) {
		$tps=$this->getConfiguration('Ttotal')*$Hauteur/100;
		log::add('voletProp','debug',$this->getHumanName().' Temps d\'action '.$tps.'s');
		return $tps;
	}
	public function StartListener() {
		if($this->getIsEnable()){
			$listener = listener::byClassAndFunction('voletProp', 'pull', array('Volets_id' => $this->getId()));
			if (!is_object($listener))
			    $listener = new listener();
			$listener->setClass('voletProp');
			$listener->setFunction('pull');
			$listener->setOption(array('Volets_id' => $this->getId()));
			$listener->emptyEvent();				
			if ($this->getConfiguration('cmdMoveState') != '')
				$listener->addEvent($this->getConfiguration('cmdMoveState'));
			if ($this->getConfiguration('cmdStopState') != '')
				$listener->addEvent($this->getConfiguration('cmdStopState'));
			if ($this->getConfiguration('cmdEnd') != '')
				$listener->addEvent($this->getConfiguration('cmdEnd'));
		}
	}
	public function AddCommande($Name,$_logicalId,$Type="info", $SubType='binary',$visible,$Value=null,$Template='',$icon='',$generic_type='') {
		$Commande = $this->getCmd(null,$_logicalId);
		if (!is_object($Commande))
		{
			$Commande = new voletPropCmd();
			$Commande->setId(null);
			$Commande->setName($Name);
			$Commande->setIsVisible($visible);
			$Commande->setLogicalId($_logicalId);
			$Commande->setEqLogic_id($this->getId());
		}
		if($Value!=null)
			$Commande->setValue($Value);
		$Commande->setType($Type);
		$Commande->setSubType($SubType);
   		$Commande->setTemplate('dashboard',$Template );
		$Commande->setTemplate('mobile', $Template);
		$Commande->setDisplay('icon', $icon);
		$Commande->setDisplay('generic_type', $generic_type);
		$Commande->save();
		return $Commande;
	}
	public function postSave() {
		$hauteur=$this->AddCommande("Hauteur","hauteur","info", 'numeric',true,null,'','','FLAP_STATE');
		$this->AddCommande("Position","position","action", 'slider',true,null,$hauteur->getId(),'','','FLAP_SLIDER');
		$this->AddCommande("Up","up","action", 'other',true,null,'','<i class="fa fa-arrow-up"></i>','FLAP_UP');
		$this->AddCommande("Down","down","action", 'other',true,null,'','<i class="fa fa-arrow-down"></i>','FLAP_DOWN');
		$this->AddCommande("Stop","stop","action", 'other',true,null,'','<i class="fa fa-stop"></i>','FLAP_STOP');
		$this->StartListener();
	}	
}
class voletPropCmd extends cmd {
    public function execute($_options = null) {
		switch($this->getLogicalId()){
			case "up":
				$cmd=cmd::byId(str_replace('#','',$this->getEqLogic()->getConfiguration('cmdUp')));
				if(is_object($cmd))
					$cmd->event(null);
			break;
			case "down":
				$cmd=cmd::byId(str_replace('#','',$this->getEqLogic()->getConfiguration('cmdDown')));
				if(is_object($cmd))
					$cmd->event(null);
			break;
			case "stop":
				$cmd=cmd::byId(str_replace('#','',$this->getEqLogic()->getConfiguration('cmdStop')));
				if(is_object($cmd))
					$cmd->event(null);
			break;
			case "position":
				$this->getEqLogic()->execPropVolet($_options['slider']);
			break;
		}
	}
}
?>
