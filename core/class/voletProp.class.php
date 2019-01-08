<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class voletProp extends eqLogic {
	public static function cron() {
		foreach(eqLogic::byType('voletProp') as $Volet){ 
			if(cache::byKey('voletProp::Move::'.$Volet->getId())->getValue(false)){
				$ChangeStateStart = cache::byKey('voletProp::ChangeStateStart::'.$Volet->getId())->getValue(microtime(true));
				if(microtime(true)-$ChangeStateStart >=$Volet->getTime('Ttotal')){
					$cmd=cmd::byId(str_replace('#','',$Volet->getConfiguration('cmdStop')));
					if(is_object($cmd))
						$cmd->execute(null);
					cache::set('voletProp::ChangeStateStop::'.$Volet->getId(),microtime(true), 0);
					$Volet->UpdateHauteur();
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
			if($Volet->getIsEnable() && $Volet->getConfiguration('UpStateCmd') != '' && $Volet->getConfiguration('DownStateCmd') != ''&& $Volet->getConfiguration('StopStateCmd') != '' && $Volet->getConfiguration('cmdEnd') != ''){
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
	public static function Up($_option) {
		log::add('voletProp','debug','Detection sur le listener Up : '.json_encode($_option));
		$Volet = eqLogic::byId($_option['Volets_id']);
		$detectedCmd = cmd::byId($_option['event_id']);
		if (is_object($detectedCmd) && is_object($Volet) && $Volet->getIsEnable()) {
			log::add('voletProp','info',$Volet->getHumanName().$detectedCmd->getHumanName());
			if($this->getConfiguration('cmdStop') == '' && cache::byKey('voletProp::Move::'.$Volet->getId())->getValue(false) && cache::byKey('voletProp::ChangeState::'.$Volet->getId())->getValue(false)){
				cache::set('voletProp::ChangeStateStop::'.$Volet->getId(),microtime(true), 0);
				$Volet->UpdateHauteur();
				cache::set('voletProp::Move::'.$Volet->getId(),false, 0);
			}else{
				$isUp=$Volet->getConfiguration('UpStateCmd').$Volet->getConfiguration('UpStateOperande').$Volet->getConfiguration('UpStateValue');
				if($Volet->EvaluateCondition($isUp)){
					cache::set('voletProp::ChangeState::'.$Volet->getId(),true, 0);
					cache::set('voletProp::Move::'.$Volet->getId(),true, 0);
					cache::set('voletProp::ChangeStateStart::'.$Volet->getId(),microtime(true), 0);
				}
			}
		}
	}
	public static function Down($_option) {
		log::add('voletProp','debug','Detection sur le listener Down : '.json_encode($_option));
		$Volet = eqLogic::byId($_option['Volets_id']);
		$detectedCmd = cmd::byId($_option['event_id']);
		if (is_object($detectedCmd) && is_object($Volet) && $Volet->getIsEnable()) {
			log::add('voletProp','info',$Volet->getHumanName().$detectedCmd->getHumanName());
			if($this->getConfiguration('cmdStop') == '' && cache::byKey('voletProp::Move::'.$Volet->getId())->getValue(false) && !cache::byKey('voletProp::ChangeState::'.$Volet->getId())->getValue(false)){
				cache::set('voletProp::ChangeStateStop::'.$Volet->getId(),microtime(true), 0);
				$Volet->UpdateHauteur();
				cache::set('voletProp::Move::'.$Volet->getId(),false, 0);
			}else{
				$isDown=$Volet->getConfiguration('DownStateCmd').$Volet->getConfiguration('DownStateOperande').$Volet->getConfiguration('DownStateValue');
				if($Volet->EvaluateCondition($isDown)){
					cache::set('voletProp::ChangeState::'.$Volet->getId(),false, 0);
					cache::set('voletProp::Move::'.$Volet->getId(),true, 0);
					cache::set('voletProp::ChangeStateStart::'.$Volet->getId(),microtime(true), 0);
				}
			}
		}
	}
	public static function Stop($_option) {
		log::add('voletProp','debug','Detection sur le listener Stop : '.json_encode($_option));
		$Volet = eqLogic::byId($_option['Volets_id']);
		$detectedCmd = cmd::byId($_option['event_id']);
		if (is_object($detectedCmd) && is_object($Volet) && $Volet->getIsEnable()) {
			log::add('voletProp','info',$Volet->getHumanName().$detectedCmd->getHumanName());
			$isStop=$Volet->getConfiguration('StopStateCmd').$Volet->getConfiguration('StopStateOperande').$Volet->getConfiguration('StopStateValue');
			if($Volet->EvaluateCondition($isStop)){
				$Move=cache::byKey('voletProp::Move::'.$Volet->getId());
				cache::set('voletProp::ChangeStateStop::'.$Volet->getId(),microtime(true), 0);
				if(is_object($Move) && $Move->getValue(false)){
					$Volet->UpdateHauteur();
					cache::set('voletProp::Move::'.$Volet->getId(),false, 0);
				}
			}
		}
	}
	public static function End($_option) {
		log::add('voletProp','debug','Detection sur le listener End : '.json_encode($_option));
		$Volet = eqLogic::byId($_option['Volets_id']);
		$detectedCmd = cmd::byId($_option['event_id']);
		if (is_object($detectedCmd) && is_object($Volet) && $Volet->getIsEnable()) {
			log::add('voletProp','info',$Volet->getHumanName().$detectedCmd->getHumanName());
			if($_option['value'])
				$Volet->checkAndUpdateCmd('hauteur',0);
		}
	}
	public function boolToText($value){
		if (is_bool($value)) {
			if ($value) 
				return __('Vrai', __FILE__);
			else 
				return __('Faux', __FILE__);
		} else 
			return $value;
	}
	public function EvaluateCondition($Condition){
		$_scenario = null;
		$expression = scenarioExpression::setTags($Condition, $_scenario, true);
		$message = __('Evaluation de la condition : ['.jeedom::toHumanReadable($Condition).'][', __FILE__) . trim($expression) . '] = ';
		$result = evaluate($expression);
		$message .=$this->boolToText($result);
		log::add('voletProp','info',$this->getHumanName().$message);
		if(!$result)
			return false;		
		return true;
	}
    	public function UpdateHauteur() {
		$ChangeState = cache::byKey('voletProp::ChangeState::'.$this->getId())->getValue(false);
		$ChangeStateStart = cache::byKey('voletProp::ChangeStateStart::'.$this->getId())->getValue(microtime(true));
		$ChangeStateStop = cache::byKey('voletProp::ChangeStateStop::'.$this->getId())->getValue(microtime(true));	
		$TempsAction=$ChangeStateStop-$ChangeStateStart;	
		$TempsAction=round($TempsAction*1000000);
		log::add('voletProp','debug',$this->getHumanName().' Temps de mouvement du volet de '.$TempsAction.'µs');
		$HauteurActuel=$this->getCmd(null,'hauteur')->execCmd();
		if($HauteurActuel != 0)
			$TempsAction-=$this->getTime('Tdecol');
		$Hauteur=round($TempsAction*100/($this->getTime('Ttotal')-$this->getTime('Tdecol')));
		log::add('voletProp','debug',$this->getHumanName().' Mouvement du volet de '.$Hauteur.'%');
		if($ChangeState)
			$Hauteur=round($HauteurActuel+$Hauteur);
		else
			$Hauteur=round($HauteurActuel-$Hauteur);
		if($Hauteur<0)
			$Hauteur=0;
		if($Hauteur>100)
			$Hauteur=100;
		log::add('voletProp','debug',$this->getHumanName().' Le volet est a '.$Hauteur.'%');
		$this->checkAndUpdateCmd('hauteur',$Hauteur);
	}
    	public function CheckSynchro($Hauteur) {
		if($this->getConfiguration('Synchronisation') == "")
			return true;
		if($this->getConfiguration('cmdStop') != ''){
			$Stop=cmd::byId(str_replace('#','',$this->getConfiguration('cmdStop')));
			if(!is_object($Stop))
				return false;
		}
		$Down=cmd::byId(str_replace('#','',$this->getConfiguration('cmdDown')));
		if(!is_object($Down))
			return false;
		$Up=cmd::byId(str_replace('#','',$this->getConfiguration('cmdUp')));
		if(!is_object($Up))
			return false;
		foreach($this->getConfiguration('Synchronisation') as $Synchronisation){
			if($Synchronisation == '100' && $Hauteur == 100){
				log::add('voletProp','info',$this->getHumanName().'[Synchronisation] Montée complete');
				$Up->execute(null);
				if(!isset($Stop))
					$Stop=$Down;
				usleep($this->getTime('Ttotal'));
				$Stop->execute(null);		
				if($this->getConfiguration('UpStateCmd') == '' && $this->getConfiguration('DownStateCmd') == '')
					$this->checkAndUpdateCmd('hauteur',100);
				return false;
			}
			if($Synchronisation == '0' && $Hauteur == 0){
				log::add('voletProp','info',$this->getHumanName().'[Synchronisation] Descente complete');
				$Down->execute(null);
				if(!isset($Stop))
					$Stop=$Up;
				usleep($this->getTime('Ttotal'));
				$Stop->execute(null);		
				if($this->getConfiguration('UpStateCmd') == '' && $this->getConfiguration('DownStateCmd') == '')
					$this->checkAndUpdateCmd('hauteur',0);
				return false;
			}
			if($Synchronisation == 'all'){
				log::add('voletProp','info',$this->getHumanName().'[Synchronisation] Montée complete');
				$Up->execute(null);
				if(!isset($Stop))
					$Stop=$Down;
				usleep($this->getTime('Ttotal'));
				$Stop->execute(null);		
				if($this->getConfiguration('UpStateCmd') == '' && $this->getConfiguration('DownStateCmd') == '')
					$this->checkAndUpdateCmd('hauteur',100);
				return true;
			}
		}
		return true;
	}
    	public function execPropVolet($Hauteur) {
		if(!$this->CheckSynchro($Hauteur))
			return false;
		if($this->getConfiguration('cmdStop') != ''){
			$Stop=cmd::byId(str_replace('#','',$this->getConfiguration('cmdStop')));
			if(!is_object($Stop))
				return false;
		}
		$Down=cmd::byId(str_replace('#','',$this->getConfiguration('cmdDown')));
		if(!is_object($Down))
			return false;
		$Up=cmd::byId(str_replace('#','',$this->getConfiguration('cmdUp')));
		if(!is_object($Up))
			return false;
		$HauteurVolet=$this->getCmd(null,'hauteur')->execCmd();
		if($HauteurVolet == $Hauteur)
			return;
		$Decol=false;
		if($Hauteur == 0 || $HauteurVolet == 0)
			$Decol=true;
		cache::set('voletProp::Move::'.$this->getId(),true, 0);
		cache::set('voletProp::ChangeStateStart::'.$this->getId(),microtime(true), 0);
		if($HauteurVolet > $Hauteur){
			$Delta=$HauteurVolet-$Hauteur;
			$temps=$this->TpsAction($Delta,$Decol);
			cache::set('voletProp::ChangeState::'.$this->getId(),false, 0);
			$Down->execute(null);
			if(!isset($Stop))
				$Stop=$Down;
			log::add('voletProp','debug',$this->getHumanName().' Nous allons descendre le volet de '.$Delta.'%');
		}else{
			$Delta=$Hauteur-$HauteurVolet;
			$temps=$this->TpsAction($Delta,$Decol);
			cache::set('voletProp::ChangeState::'.$this->getId(),true, 0);
			$Up->execute(null);
			if(!isset($Stop))
				$Stop=$Up;
			log::add('voletProp','debug',$this->getHumanName().' Nous allons monter le volet de '.$Delta.'%');
		}
		usleep($temps);
		$Stop->execute(null);
		cache::set('voletProp::Move::'.$this->getId(),false, 0);
		if($this->getConfiguration('UpStateCmd') == '' && $this->getConfiguration('DownStateCmd') == ''){		
			cache::set('voletProp::ChangeStateStop::'.$this->getId(),microtime(true), 0);
			$this->UpdateHauteur();
		}
	}
    	private function getTime($Type) {
		return $this->getConfiguration($Type)*$this->getConfiguration($Type.'Base',1000000);
	}
    	public function TpsAction($Hauteur, $Decole) {
		$TempsAction=round(($this->getTime('Ttotal')-$this->getTime('Tdecol'))*$Hauteur/100);
		if(!$Decole)
			$TempsAction += $this->getTime('Tdecol');	
		if($TempsAction <= $this->getConfiguration('delaisMini')*1000000) 
			$TempsAction = $this->getConfiguration('delaisMini')*1000000;
		log::add('voletProp','debug',$this->getHumanName().' Temps d\'action '.$TempsAction.'µs');
		return $TempsAction;
	}
	public function StopListener() {
		$listener = listener::byClassAndFunction('voletProp', 'pull', array('Volets_id' => $this->getId()));
		if (is_object($listener))
			$listener->remove();
		$listener = listener::byClassAndFunction('voletProp', 'Up', array('Volets_id' => $this->getId()));
		if (is_object($listener))
			$listener->remove();
		$listener = listener::byClassAndFunction('voletProp', 'Down', array('Volets_id' => $this->getId()));
		if (is_object($listener))
			$listener->remove();
		$listener = listener::byClassAndFunction('voletProp', 'Stop', array('Volets_id' => $this->getId()));
		if (is_object($listener))
			$listener->remove();
		$listener = listener::byClassAndFunction('voletProp', 'End', array('Volets_id' => $this->getId()));
		if (is_object($listener))
			$listener->remove();
		$cache = cache::byKey('voletProp::ChangeStateStart::'.$this->getId());
		if (is_object($cache))
			$cache->remove();
		$cache = cache::byKey('voletProp::ChangeStateStop::'.$this->getId());
		if (is_object($cache))
			$cache->remove();
		$cache = cache::byKey('voletProp::Move::'.$this->getId());
		if (is_object($cache))
			$cache->remove();
		$cache = cache::byKey('voletProp::ChangeState::'.$this->getId());
		if (is_object($cache))
			$cache->remove();
	}
	public function StartListener() {
		if($this->getIsEnable()){
			$listener = listener::byClassAndFunction('voletProp', 'Up', array('Volets_id' => $this->getId()));
			$UpStateCmd=$this->getConfiguration('UpStateCmd');
			if ($UpStateCmd != ''){
				if (!is_object($listener))
				    $listener = new listener();
				$listener->setClass('voletProp');
				$listener->setFunction('Up');
				$listener->setOption(array('Volets_id' => $this->getId()));
				$listener->emptyEvent();	
				$listener->addEvent($UpStateCmd);
				$listener->save();			
			}
			$listener = listener::byClassAndFunction('voletProp', 'Down', array('Volets_id' => $this->getId()));
			$DownStateCmd=$this->getConfiguration('DownStateCmd');
			if ($DownStateCmd != ''){
				if (!is_object($listener))
				    $listener = new listener();
				$listener->setClass('voletProp');
				$listener->setFunction('Down');
				$listener->setOption(array('Volets_id' => $this->getId()));
				$listener->emptyEvent();	
					$listener->addEvent($DownStateCmd);
				$listener->save();			
			}
			$listener = listener::byClassAndFunction('voletProp', 'Stop', array('Volets_id' => $this->getId()));
			$StopStateCmd=$this->getConfiguration('StopStateCmd');
			if ($StopStateCmd != ''){
				if (!is_object($listener))
				    $listener = new listener();
				$listener->setClass('voletProp');
				$listener->setFunction('Stop');
				$listener->setOption(array('Volets_id' => $this->getId()));
				$listener->emptyEvent();	
				$listener->addEvent($StopStateCmd);
				$listener->save();				
			}
			$listener = listener::byClassAndFunction('voletProp', 'End', array('Volets_id' => $this->getId()));
			if ($this->getConfiguration('cmdEnd') != ''){
				if (!is_object($listener))
				    $listener = new listener();
				$listener->setClass('voletProp');
				$listener->setFunction('End');
				$listener->setOption(array('Volets_id' => $this->getId()));
				$listener->emptyEvent();	
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
	
	public function preRemove() {
		$listener = listener::byClassAndFunction('voletProp', 'Up', array('Volets_id' => $this->getId()));
		if (is_object($listener))
			$listener->remove();
		$listener = listener::byClassAndFunction('voletProp', 'Down', array('Volets_id' => $this->getId()));
		if (is_object($listener))
			$listener->remove();
		$listener = listener::byClassAndFunction('voletProp', 'Stop', array('Volets_id' => $this->getId()));
		if (is_object($listener))
			$listener->remove();
		$listener = listener::byClassAndFunction('voletProp', 'End', array('Volets_id' => $this->getId()));
		if (is_object($listener))
			$listener->remove();
	}
}
class voletPropCmd extends cmd {
    public function execute($_options = null) {
		switch($this->getLogicalId()){
			case "up":
				$cmd=cmd::byId(str_replace('#','',$this->getEqLogic()->getConfiguration('cmdUp')));
				if(!is_object($cmd))
					return;
				$cmd->execute(null);
				cache::set('voletProp::ChangeStateStart::'.$this->getEqLogic()->getId(),microtime(true), 0);
				cache::set('voletProp::Move::'.$this->getEqLogic()->getId(),true, 0);
				cache::set('voletProp::ChangeState::'.$this->getEqLogic()->getId(),true, 0);
			break;
			case "down":
				$cmd=cmd::byId(str_replace('#','',$this->getEqLogic()->getConfiguration('cmdDown')));
				if(!is_object($cmd))
					return;
				$cmd->execute(null);
				cache::set('voletProp::ChangeStateStart::'.$this->getEqLogic()->getId(),microtime(true), 0);
				cache::set('voletProp::Move::'.$this->getEqLogic()->getId(),true, 0);
				cache::set('voletProp::ChangeState::'.$this->getEqLogic()->getId(),false, 0);
			break;
			case "stop":
				if($this->getEqLogic()->getConfiguration('cmdStop') != ''){
					$cmd=cmd::byId(str_replace('#','',$this->getEqLogic()->getConfiguration('cmdStop')));
					if(is_object($cmd))
						$cmd->execute(null);
				}else{
					if(cache::byKey('voletProp::ChangeState::'.$this->getEqLogic()->getId())->getValue(false))
						$cmd=cmd::byId(str_replace('#','',$this->getEqLogic()->getConfiguration('cmdUp')));
					else
						$cmd=cmd::byId(str_replace('#','',$this->getEqLogic()->getConfiguration('cmdDown')));
					if(is_object($cmd))
						$cmd->execute(null);
				}				
				if($this->getEqLogic()->getConfiguration('UpStateCmd') == '' && $this->getEqLogic()->getConfiguration('DownStateCmd') == ''){		
					if(cache::byKey('voletProp::Move::'.$this->getEqLogic()->getId())->getValue(false)){
						cache::set('voletProp::ChangeStateStop::'.$this->getEqLogic()->getId(),microtime(true), 0);
						$this->getEqLogic()->UpdateHauteur();
					}
				}
				cache::set('voletProp::Move::'.$this->getEqLogic()->getId(),false, 0);
			break;
			case "position":
				if(!cache::byKey('voletProp::Move::'.$this->getEqLogic()->getId())->getValue(false))
					$this->getEqLogic()->execPropVolet($_options['slider']);
			break;
		}
	}
}
?>
