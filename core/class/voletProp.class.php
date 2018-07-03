<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class voletProp extends eqLogic {
	public static function cron() {
		foreach(eqLogic::byType('voletProp') as $Volet){ 
			if(cache::byKey('voletProp::Move::'.$Volet->getId())->getValue(false)){
				$ChangeStateStart = cache::byKey('voletProp::ChangeStateStart::'.$Volet->getId())->getValue(time());
				if(time()-$ChangeStateStart >=$Volet->getConfiguration('Ttotal')){
					$cmd=cmd::byId(str_replace('#','',$Volet->getConfiguration('cmdStop')));
					if(is_object($cmd))
						$cmd->execute(null);
				}
			}
		}
	}
	public static function deamon_info() {
		$return = array();
		$return['log'] = 'voletProp';
		$return['launchable'] = 'ok';
		$return['state'] = 'nok';
		foreach(eqLogic::byType('voletProp') as $Volet){
			if($Volet->getIsEnable() && $Volet->getConfiguration('cmdMoveState')!= '' && $Volet->getConfiguration('cmdStopState') != '' && $Volet->getConfiguration('cmdEnd') != ''){
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
		foreach(eqLogic::byType('voletProp') as $Volet){
			cache::set('voletProp::Move::'.$Volet->getId(),false, 0);
			$Volet->StartListener();
		}
	}
	public static function deamon_stop() {	
		foreach(eqLogic::byType('voletProp') as $Volet){
			$Volet->StopListener();
		}
	}
	public static function pull($_option) {
		log::add('voletProp','debug','Evenement sur les etat'.json_encode($_option));
		$Volet = eqLogic::byId($_option['Volets_id']);
		$detectedCmd = cmd::byId($_option['event_id']);
		if (is_object($detectedCmd) && is_object($Volet) && $Volet->getIsEnable()) {
			switch($_option['event_id']){
				case str_replace('#','',$Volet->getConfiguration('cmdMoveState')):
					log::add('voletProp','debug',$Volet->getHumanName().' Detection d\'un mouvement');
					$Move=cache::byKey('voletProp::Move::'.$Volet->getId());
					if(is_object($Move) && $Move->getValue(false)){
						log::add('voletProp','debug',$Volet->getHumanName().' Mouvement en cours => Stop');
						$Volet->UpdateHauteur();
						cache::set('voletProp::Move::'.$Volet->getId(),false, 0);
						break;
					}
					cache::set('voletProp::Move::'.$Volet->getId(),true, 0);
					cache::set('voletProp::ChangeState::'.$Volet->getId(),$_option['value'], 0);
					cache::set('voletProp::ChangeStateStart::'.$Volet->getId(),strtotime($detectedCmd->getCollectDate(time())), 0);
					
				break;
				case str_replace('#','',$Volet->getConfiguration('cmdStopState')):
					$Move=cache::byKey('voletProp::Move::'.$Volet->getId());
					cache::set('voletProp::ChangeStateStop::'.$Volet->getId(),strtotime($detectedCmd->getCollectDate(time())), 0);
					if(is_object($Move) && $Move->getValue(false))
						$Volet->UpdateHauteur();
					cache::set('voletProp::Move::'.$Volet->getId(),false, 0);
				break;
				case str_replace('#','',$Volet->getConfiguration('cmdEnd')):
					if($_option['value'])
						$Volet->checkAndUpdateCmd('hauteur',0);
				break;
			}
		}
	}
    	public function UpdateHauteur() {
		$ChangeState = cache::byKey('voletProp::ChangeState::'.$this->getId())->getValue(false);
		$ChangeStateStart = cache::byKey('voletProp::ChangeStateStart::'.$this->getId())->getValue(time());
		$ChangeStateStop = cache::byKey('voletProp::ChangeStateStop::'.$this->getId())->getValue(time());		
		$Tps=$ChangeStateStop-$ChangeStateStart;
		$Hauteur=$Tps*100/$this->getConfiguration('Ttotal');
		$HauteurActuel=$this->getCmd(null,'hauteur')->execCmd();
		if($ChangeState)
			$Hauteur=round($HauteurActuel+$Hauteur);
		else
			$Hauteur=round($HauteurActuel-$Hauteur);
		if($Hauteur<0)
			$Hauteur=0;
		if($Hauteur>100)
			$Hauteur=100;
		if($this->getConfiguration('Inverser'))
			$Hauteur=100-$Hauteur;
		log::add('voletProp','debug',$this->getHumanName().' Le volet est a '.$Hauteur.'%');
		$this->checkAndUpdateCmd('hauteur',$Hauteur);
		$Synchronisation = cache::byKey('voletProp::Synchronisation::'.$this->getId());
		if(is_object($Synchronisation) && !$Synchronisation->getValue(false)){
			$this->execPropVolet($Synchronisation->getValue(false));
			$Synchronisation->remove();
		}
	}
    	public function execPropVolet($Hauteur) {
		$Stop=cmd::byId(str_replace('#','',$this->getConfiguration('cmdStop')));
		if(!is_object($Stop))
			return false;
		$Down=cmd::byId(str_replace('#','',$this->getConfiguration('cmdDown')));
		if(!is_object($Down))
			return false;
		$Up=cmd::byId(str_replace('#','',$this->getConfiguration('cmdUp')));
		if(!is_object($Up))
			return false;
		$Stop->execute(null);
		//cache::set('voletProp::Move::'.$this->getId(),false, 0);
		$HauteurVolet=$this->getCmd(null,'hauteur')->execCmd();
		if($this->getConfiguration('Inverser'))
			$HauteurVolet=100-$HauteurVolet;
		if($HauteurVolet == $Hauteur)
			return;
		
		$Decol=false;
		if($Hauteur == 0 || $HauteurVolet == 0)
			$Decol=true;
		if($HauteurVolet > $Hauteur){
			$Delta=$HauteurVolet-$Hauteur;
			$temps=$this->TpsAction($Delta,$Decol);
			$Down->execute(null);
			log::add('voletProp','debug',$this->getHumanName().' Nous allons descendre le volet de '.$Delta.'%');
		}else{
			$Delta=$Hauteur-$HauteurVolet;
			$temps=$this->TpsAction($Delta,$Decol);
			$Up->execute(null);
			log::add('voletProp','debug',$this->getHumanName().' Nous allons monter le volet de '.$Delta.'%');
		}
		sleep($temps);
		$Stop->execute(null);
		log::add('voletProp','debug',$this->getHumanName().' Le volet est a '.$Hauteur.'%');
		if ($this->getConfiguration('cmdMoveState') == '' && $this->getConfiguration('cmdStopState') == '' )			
			$this->checkAndUpdateCmd('hauteur',$Hauteur);
	}
    	public function TpsAction($Hauteur, $Decol) {
		$TpsGlobal=$this->getConfiguration('Ttotal');
		if(!$Decol)
			$TpsGlobal-=$this->getConfiguration('Tdecol');
		$tps=round($TpsGlobal*$Hauteur/100);
		log::add('voletProp','debug',$this->getHumanName().' Temps d\'action '.$tps.'s');
		return $tps;
	}
	public function StopListener() {
		$listener = listener::byClassAndFunction('voletProp', 'pull', array('Volets_id' => $this->getId()));
		if (is_object($listener))
			$listener->remove();
	}
	public function StartListener() {
		if($this->getIsEnable()){
			if(($this->getConfiguration('cmdMoveState') != '' && $this->getConfiguration('cmdStopState') != '') || $this->getConfiguration('cmdEnd') != ''){
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
				$listener->save();
			}
		}
	}
	public function AddCommande($Name,$_logicalId,$Type="info", $SubType='binary',$visible,$Value=null,$Template=null,$icon=null,$generic_type=null) {
		$Commande = $this->getCmd(null,$_logicalId);
		if (!is_object($Commande))
		{
			$Commande = new voletPropCmd();
			$Commande->setId(null);
			$Commande->setName($Name);
			$Commande->setIsVisible($visible);
			$Commande->setLogicalId($_logicalId);
			$Commande->setEqLogic_id($this->getId());
			$Commande->setType($Type);
			$Commande->setSubType($SubType);
		}
		if($Value != null)
			$Commande->setValue($Value);
		if($Template != null){
			$Commande->setTemplate('dashboard',$Template );
			$Commande->setTemplate('mobile', $Template);
		}
		if($icon != null)
			$Commande->setDisplay('icon', $icon);
		if($generic_type != null)
			$Commande->setDisplay('generic_type', $generic_type);
		$Commande->save();
		return $Commande;
	}
	public function postSave() {
		$this->StopListener();
		$hauteur=$this->AddCommande("Hauteur","hauteur","info",'numeric',0,null,null,null,'FLAP_STATE');
		$this->AddCommande("Position","position","action",'slider',1,$hauteur->getId(),'Volet',null,'FLAP_SLIDER');
		$this->AddCommande("Up","up","action", 'other',1,null,null,'<i class="fa fa-arrow-up"></i>','FLAP_UP');
		$this->AddCommande("Down","down","action", 'other',1,null,null,'<i class="fa fa-arrow-down"></i>','FLAP_DOWN');
		$this->AddCommande("Stop","stop","action", 'other',1,null,null,'<i class="fa fa-stop"></i>','FLAP_STOP');
		$this->StartListener();
	}	
}
class voletPropCmd extends cmd {
    public function execute($_options = null) {
		switch($this->getLogicalId()){
			case "up":
				$cmd=cmd::byId(str_replace('#','',$this->getEqLogic()->getConfiguration('cmdUp')));
				if(is_object($cmd))
					$cmd->execute(null);
			break;
			case "down":
				$cmd=cmd::byId(str_replace('#','',$this->getEqLogic()->getConfiguration('cmdDown')));
				if(is_object($cmd))
					$cmd->execute(null);
			break;
			case "stop":
				$cmd=cmd::byId(str_replace('#','',$this->getEqLogic()->getConfiguration('cmdStop')));
				if(is_object($cmd))
					$cmd->execute(null);
			break;
			case "position":
				if($this->getEqLogic()->getConfiguration('Synchronisation')){
					cache::set('voletProp::Synchronisation::'.$this->getEqLogic()->getId(),$_options['slider'], 0);
					$cmd=cmd::byId(str_replace('#','',$this->getEqLogic()->getConfiguration('cmdDown')));
					if(is_object($cmd))
						$cmd->execute(null);
				}else{
					$this->getEqLogic()->execPropVolet($_options['slider']);
				}
			break;
		}
	}
}
?>
