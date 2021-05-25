<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class voletProp extends eqLogic {
	public static function timeout($_option) {	
		$Volet = eqlogic::byId($_option['Volets_id']); 
		if (is_object($Volet) && $Volet->getIsEnable()) {
			while(true){
				$PropMove = cache::byKey('voletProp::PropMove::'.$Volet->getId());
				$Synchro = cache::byKey('voletProp::Synchro::'.$Volet->getId());
				$Move = cache::byKey('voletProp::Move::'.$Volet->getId());
				if(!is_object($Move) || !$Move->getValue(false)){
					if(is_object($PropMove) && $PropMove->getValue(false) !== false){
						$Hauteur = $PropMove->getValue(0);
						$HauteurVolet=$Volet->getCmd(null,'hauteur')->execCmd();
						if($HauteurVolet == $Hauteur){
							cache::set('voletProp::PropMove::'.$Volet->getId(),false, 0);
						}
						if(!is_object($Synchro) || !$Synchro->getValue(false)){
							log::add('voletProp','debug',$Volet->getHumanName()."[Démon] Synchronisation");
							$HauteurVolet=$Volet->CheckSynchro($Hauteur,$HauteurVolet);
							$TimeMove = cache::byKey('voletProp::TimeMove::'.$Volet->getId());
							$TempsTimeout = $TimeMove->getValue(microtime(true));
							if($HauteurVolet === false){
								$TempsTimeout *= 1.1;
								cache::set('voletProp::Synchro::'.$Volet->getId(),false, 0);
								cache::set('voletProp::PropMove::'.$Volet->getId(),false, 0);
							}
							cache::set('voletProp::TempsTimeout::'.$Volet->getId(),$TempsTimeout, 0);
							continue;
						}else{
							log::add('voletProp','debug',$Volet->getHumanName()."[Démon] Execution du mouvement proportionnel");
							$Volet->execPropVolet($Hauteur,$HauteurVolet);
							$TimeMove = cache::byKey('voletProp::TimeMove::'.$Volet->getId());
							cache::set('voletProp::TempsTimeout::'.$Volet->getId(),$TimeMove->getValue(microtime(true)), 0);
							continue;
						}
					}else{
						if(cache::byKey('voletProp::ChangeState::'.$Volet->getId())->getValue(false))
							$TempsTimeout = $Volet->getTime('TpsUp') * 1.1;
						else
							$TempsTimeout = $Volet->getTime('TpsDown') * 1.1;
						cache::set('voletProp::TempsTimeout::'.$Volet->getId(),$TempsTimeout, 0);
					}
				}else{
					$ChangeStateStart = cache::byKey('voletProp::ChangeStateStart::'.$Volet->getId())->getValue(microtime(true));
					$TempsTimeout = cache::byKey('voletProp::TempsTimeout::'.$Volet->getId())->getValue(0);
					$Timeout = microtime(true)-$ChangeStateStart;
					$Timeout *= 1000000;
                			//log::add('voletProp','debug',$Volet->getHumanName()."[Démon] FIN ".$Timeout.' >= '.$TempsTimeout);
					if($Timeout >= $TempsTimeout){
						log::add('voletProp','info',$Volet->getHumanName()."[Démon] Execution du stop");
						$Volet->getCmd(null,'stop')->execute(null);	
					}
					if(($TempsTimeout - $Timeout) <= 0)
						sleep(1);
					else
						usleep(($TempsTimeout - $Timeout) / 2);
					continue;
				}
				sleep(1);		
			}
		}
	}
	public static function deamon_info() {
		$return = array();
		$return['log'] = 'voletProp';
		$return['launchable'] = 'ok';
		$return['state'] = 'nok';
		foreach(eqLogic::byType('voletProp') as $Volet){
			if($Volet->getIsEnable()){
				if($Volet->getConfiguration('UpStateCmd') != '' ){				
					$listener = listener::byClassAndFunction('voletProp', 'UpVolet', array('Volets_id' => $Volet->getId()));
					if (!is_object($listener))
						return $return;
				}
				if($Volet->getConfiguration('DownStateCmd') != ''){				
					$listener = listener::byClassAndFunction('voletProp', 'DownVolet', array('Volets_id' => $Volet->getId()));
					if (!is_object($listener))
						return $return;
				}
				if($Volet->getConfiguration('StopStateCmd') != ''){				
					$listener = listener::byClassAndFunction('voletProp', 'StopVolet', array('Volets_id' => $Volet->getId()));
					if (!is_object($listener))
						return $return;
				}
				if($Volet->getConfiguration('EndUpCmd') != '' || $Volet->getConfiguration('EndDownCmd') != ''){
					$listener = listener::byClassAndFunction('voletProp', 'EndVolet', array('Volets_id' => $Volet->getId()));
					if (!is_object($listener))
						return $return;
				}
				$cron = cron::byClassAndFunction('voletProp', 'timeout', array('Volets_id' => $Volet->getId()));
				if(!is_object($cron) || !$cron->running()) 	
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
			$Volet->CreateDemon();   
		}
	}
	public static function deamon_stop() {	
		foreach(eqLogic::byType('voletProp') as $Volet){
			$Volet->StopListener();
			$cron = cron::byClassAndFunction('voletProp', 'timeout', array('Volets_id' => $Volet->getId()));
			if (is_object($cron)) 	
				$cron->remove();
		}
	}
	public static function UpVolet($_option) {
		log::add('voletProp','debug','Detection sur le listener Up : '.json_encode($_option));
		$Volet = eqLogic::byId($_option['Volets_id']);
		$detectedCmd = cmd::byId($_option['event_id']);
		if (is_object($detectedCmd) && is_object($Volet) && $Volet->getIsEnable()) {
			$isUp=$Volet->getConfiguration('UpStateCmd').$Volet->getConfiguration('UpStateOperande').$Volet->getConfiguration('UpStateValue');
			if($Volet->EvaluateCondition($isUp)){
				if($Volet->getConfiguration('StopStateCmd') == '' && cache::byKey('voletProp::Move::'.$Volet->getId())->getValue(false) && cache::byKey('voletProp::ChangeState::'.$Volet->getId())->getValue(false)){
					log::add('voletProp','info',$Volet->getHumanName().'[Up] Stop du mouvement détecté par '.$detectedCmd->getHumanName());
					cache::set('voletProp::ChangeStateStop::'.$Volet->getId(),microtime(true), 0);
					$Volet->UpdateHauteur();
					cache::set('voletProp::Move::'.$Volet->getId(),false, 0);
				}else{
					log::add('voletProp','info',$Volet->getHumanName().'[Up] Mouvement détécter sur '.$detectedCmd->getHumanName());
					cache::set('voletProp::ChangeState::'.$Volet->getId(),true, 0);
					cache::set('voletProp::Move::'.$Volet->getId(),true, 0);
					cache::set('voletProp::ChangeStateStart::'.$Volet->getId(),microtime(true), 0);
				}
			}
		}
	}
	public static function DownVolet($_option) {
		log::add('voletProp','debug','Detection sur le listener Down : '.json_encode($_option));
		$Volet = eqLogic::byId($_option['Volets_id']);
		$detectedCmd = cmd::byId($_option['event_id']);
		if (is_object($detectedCmd) && is_object($Volet) && $Volet->getIsEnable()) {
			$isDown=$Volet->getConfiguration('DownStateCmd').$Volet->getConfiguration('DownStateOperande').$Volet->getConfiguration('DownStateValue');
			if($Volet->EvaluateCondition($isDown)){
				if($Volet->getConfiguration('StopStateCmd') == '' && cache::byKey('voletProp::Move::'.$Volet->getId())->getValue(false) && !cache::byKey('voletProp::ChangeState::'.$Volet->getId())->getValue(true)){
					log::add('voletProp','info',$Volet->getHumanName().'[Down] Stop du mouvement détecté par '.$detectedCmd->getHumanName());
					cache::set('voletProp::ChangeStateStop::'.$Volet->getId(),microtime(true), 0);
					$Volet->UpdateHauteur();
					cache::set('voletProp::Move::'.$Volet->getId(),false, 0);
				}else{
					log::add('voletProp','info',$Volet->getHumanName().'[Down] Mouvement détecté sur '.$detectedCmd->getHumanName());
					cache::set('voletProp::ChangeState::'.$Volet->getId(),false, 0);
					cache::set('voletProp::Move::'.$Volet->getId(),true, 0);
					cache::set('voletProp::ChangeStateStart::'.$Volet->getId(),microtime(true), 0);
				}
			}
		}
	}
	public static function StopVolet($_option) {
		log::add('voletProp','debug','Detection sur le listener Stop : '.json_encode($_option));
		$Volet = eqLogic::byId($_option['Volets_id']);
		$detectedCmd = cmd::byId($_option['event_id']);
		if (is_object($detectedCmd) && is_object($Volet) && $Volet->getIsEnable()) {			
			$isStop=$Volet->getConfiguration('StopStateCmd').$Volet->getConfiguration('StopStateOperande').$Volet->getConfiguration('StopStateValue');
			if($Volet->EvaluateCondition($isStop)){
				log::add('voletProp','info',$Volet->getHumanName().'[Stop]: Action détectée sur '.$detectedCmd->getHumanName());
				$Move=cache::byKey('voletProp::Move::'.$Volet->getId());
				cache::set('voletProp::ChangeStateStop::'.$Volet->getId(),microtime(true), 0);
				if(is_object($Move) && $Move->getValue(false)){
					$Volet->UpdateHauteur();
					cache::set('voletProp::Move::'.$Volet->getId(),false, 0);
				}
			}
		}
	}
	public static function EndVolet($_option) {
		log::add('voletProp','debug','Detection sur le listener End : '.json_encode($_option));
		$Volet = eqLogic::byId($_option['Volets_id']);
		$detectedCmd = cmd::byId($_option['event_id']);
		if (is_object($detectedCmd) && is_object($Volet) && $Volet->getIsEnable()) {
			$isEndUp=$Volet->getConfiguration('EndUpCmd').$Volet->getConfiguration('EndUpOperande').$Volet->getConfiguration('EndUpValue');
			if($Volet->EvaluateCondition($isEndUp)){
				log::add('voletProp','info',$Volet->getHumanName().'[Fin de cours]: Fin de course haute détectée, mise a 100% de l\'etat');
				$Volet->checkAndUpdateCmd('hauteur',100);
				cache::set('voletProp::Move::'.$Volet->getId(),false, 0);
			}
			$isEndDown=$Volet->getConfiguration('EndDownCmd').$Volet->getConfiguration('EndDownOperande').$Volet->getConfiguration('EndDownValue');
			if($Volet->EvaluateCondition($isEndDown)){
				log::add('voletProp','info',$Volet->getHumanName().'[Fin de cours]: Fin de course basse détectée, mise a 0% de l\'etat');
				$Volet->checkAndUpdateCmd('hauteur',0);
				cache::set('voletProp::Move::'.$Volet->getId(),false, 0);
			}
		}
	}
	private function CreateDemon() {
		$cron =cron::byClassAndFunction('voletProp', 'timeout', array('Volets_id' => $this->getId()));
		if (!is_object($cron)) {
			$cron = new cron();
			$cron->setClass('voletProp');
			$cron->setFunction('timeout');
			$cron->setOption(array('Volets_id' => $this->getId()));
			$cron->setEnable(1);
			$cron->setDeamon(1);
			$cron->setSchedule('* * * * *');
			$cron->setTimeout('1');
			$cron->save();
		}
		$cron->save();
		$cron->start();
		$cron->run();
		return $cron;
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
		$HauteurActuel=$this->getCmd(null,'hauteur')->execCmd();
		log::add('voletProp','debug',$this->getHumanName().' Temps de mouvement du volet de '.$TempsAction.'µs');
		if($HauteurActuel == 0){
			$TempsAction -= $this->getTime('Tdecol');
			log::add('voletProp','debug',$this->getHumanName().' Suppression du temps de décollement');
		}
		if($ChangeState)
			$Temps = $this->getTime('TpsUp') - $this->getTime('Tdecol');
		else
			$Temps = $this->getTime('TpsDown') - $this->getTime('Tdecol');
		$Hauteur=round($TempsAction*100/$Temps);
		log::add('voletProp','debug',$this->getHumanName().' Mouvement du volet de '.$Hauteur.'%');
		if($ChangeState)
			$Hauteur=round($HauteurActuel+$Hauteur);
		else
			$Hauteur=round($HauteurActuel-$Hauteur);
		if($Hauteur<0)
			$Hauteur=0;
		if($Hauteur>100)
			$Hauteur=100;
		log::add('voletProp','debug',$this->getHumanName().' Le volet est à '.$Hauteur.'%');
		$this->checkAndUpdateCmd('hauteur',$Hauteur);
	}
	public function CheckSynchro($Hauteur,$HauteurVolet) {
		cache::set('voletProp::Synchro::'.$this->getId(),true, 0);
		if($HauteurVolet == 0)
			return 0;
		if($HauteurVolet == 100)
			return 100;
		$Stop=$this->getCmd(null,'stop');
		if(!is_object($Stop))
			return false;
		$Down=$this->getCmd(null,'down');
		if(!is_object($Down))
			return false;
		$Up=$this->getCmd(null,'up');
		if(!is_object($Up))
			return false;
		if($Hauteur == 100){
			log::add('voletProp','info',$this->getHumanName().'[Synchronisation] Montée complete');
			$Up->execute(null);
			cache::set('voletProp::Move::'.$this->getId(),true, 0);
			cache::set('voletProp::TimeMove::'.$this->getId(),$this->getTime('TpsUp'), 0);
			return false;
		}
		if($Hauteur == 0){
			log::add('voletProp','info',$this->getHumanName().'[Synchronisation] Descente complete');
			$Down->execute(null);
			cache::set('voletProp::Move::'.$this->getId(),true, 0);
			cache::set('voletProp::TimeMove::'.$this->getId(),$this->getTime('TpsDown'), 0);
			return false;
		}
		if($this->getConfiguration('Synchronisation')){
			if($HauteurVolet - $Hauteur < 0){
				log::add('voletProp','info',$this->getHumanName().'[Synchronisation] Descente complete');
				$Down->execute(null);
				cache::set('voletProp::TimeMove::'.$this->getId(),$this->getTime('TpsDown'), 0);
				return 0;
			}else{
				log::add('voletProp','info',$this->getHumanName().'[Synchronisation] Montée complete');
				$Up->execute(null);
				cache::set('voletProp::TimeMove::'.$this->getId(),$this->getTime('TpsUp'), 0);
				return 100;
			}
			cache::set('voletProp::Move::'.$this->getId(),true, 0);
		}
		return $HauteurVolet;
	}
	public function execPropVolet($Hauteur,$HauteurVolet) {
		$Stop=$this->getCmd(null,'stop');
		if(!is_object($Stop))
			return false;
		$Down=$this->getCmd(null,'down');
		if(!is_object($Down))
			return false;
		$Up=$this->getCmd(null,'up');
		if(!is_object($Up))
			return false;
		$AutorisationDecollement=false;
		if($Hauteur == 0 || $HauteurVolet == 0)
			$AutorisationDecollement=true;
		if($HauteurVolet > $Hauteur){
			$Delta=$HauteurVolet-$Hauteur;
			cache::set('voletProp::ChangeState::'.$this->getId(),false, 0);
			$Down->execute(null);
			log::add('voletProp','debug',$this->getHumanName().' Le volet est à '.$HauteurVolet.'% et nous allons le descendre  de '.$Delta.'%');
		}else{
			$Delta=$Hauteur-$HauteurVolet;
			cache::set('voletProp::ChangeState::'.$this->getId(),true, 0);
			$Up->execute(null);
			log::add('voletProp','debug',$this->getHumanName().' Le volet est à '.$HauteurVolet.'% et nous allons le monter de '.$Delta.'%');
		}
		cache::set('voletProp::ChangeStateStart::'.$this->getId(),microtime(true), 0);
		cache::set('voletProp::TimeMove::'.$this->getId(),$this->TpsAction($Delta,$AutorisationDecollement), 0);
	}
	public function getTime($Type) {
		return intval($this->getConfiguration($Type,0))*intval($this->getConfiguration($Type.'Base',1000000));
	}
	public function TpsAction($Hauteur, $AutorisationDecollement) {
		if(cache::byKey('voletProp::ChangeState::'.$this->getId())->getValue(false))
			$Temps = $this->getTime('TpsUp') - $this->getTime('Tdecol');
		else
			$Temps = $this->getTime('TpsDown') - $this->getTime('Tdecol');
		$TempsAction=round($Hauteur*$Temps/100);
		if($AutorisationDecollement){
			$TempsAction += $this->getTime('Tdecol');
			log::add('voletProp','debug',$this->getHumanName().' Ajout du temps de décollement');
		}
		if($TempsAction <= $this->getConfiguration('delaisMini')*1000000) 
			$TempsAction = $this->getConfiguration('delaisMini')*1000000;
		log::add('voletProp','debug',$this->getHumanName().' Temps d\'action '.$TempsAction.'µs');
		return $TempsAction;
	}
	public function StopListener() {
		$listener = listener::byClassAndFunction('voletProp', 'UpVolet', array('Volets_id' => $this->getId()));
		if (is_object($listener))
			$listener->remove();
		$listener = listener::byClassAndFunction('voletProp', 'DownVolet', array('Volets_id' => $this->getId()));
		if (is_object($listener))
			$listener->remove();
		$listener = listener::byClassAndFunction('voletProp', 'StopVolet', array('Volets_id' => $this->getId()));
		if (is_object($listener))
			$listener->remove();
		$listener = listener::byClassAndFunction('voletProp', 'EndVolet', array('Volets_id' => $this->getId()));
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
		$cache = cache::byKey('voletProp::PropMove::'.$this->getId());
		if (is_object($cache))
			$cache->remove();
		$cache = cache::byKey('voletProp::Synchro::'.$this->getId());
		if (is_object($cache))
			$cache->remove();
	}
	public function StartListener() {
		if($this->getIsEnable()){
			$listener = listener::byClassAndFunction('voletProp', 'UpVolet', array('Volets_id' => $this->getId()));
			$UpStateCmd=$this->getConfiguration('UpStateCmd');
			if ($UpStateCmd != ''){
				if (!is_object($listener))
				    $listener = new listener();
				$listener->setClass('voletProp');
				$listener->setFunction('UpVolet');
				$listener->setOption(array('Volets_id' => $this->getId()));
				$listener->emptyEvent();	
				$listener->addEvent($UpStateCmd);
				$listener->save();			
			}
			$listener = listener::byClassAndFunction('voletProp', 'DownVolet', array('Volets_id' => $this->getId()));
			$DownStateCmd=$this->getConfiguration('DownStateCmd');
			if ($DownStateCmd != ''){
				if (!is_object($listener))
				    $listener = new listener();
				$listener->setClass('voletProp');
				$listener->setFunction('DownVolet');
				$listener->setOption(array('Volets_id' => $this->getId()));
				$listener->emptyEvent();	
					$listener->addEvent($DownStateCmd);
				$listener->save();			
			}
			$listener = listener::byClassAndFunction('voletProp', 'StopVolet', array('Volets_id' => $this->getId()));
			$StopStateCmd=$this->getConfiguration('StopStateCmd');
			if ($StopStateCmd != ''){
				if (!is_object($listener))
				    $listener = new listener();
				$listener->setClass('voletProp');
				$listener->setFunction('StopVolet');
				$listener->setOption(array('Volets_id' => $this->getId()));
				$listener->emptyEvent();	
				$listener->addEvent($StopStateCmd);
				$listener->save();				
			}
			$listener = listener::byClassAndFunction('voletProp', 'EndVolet', array('Volets_id' => $this->getId()));
			if ($this->getConfiguration('EndUpCmd') != '' || $this->getConfiguration('EndDownCmd') != ''){
				if (!is_object($listener))
				    $listener = new listener();
				$listener->setClass('voletProp');
				$listener->setFunction('EndVolet');
				$listener->setOption(array('Volets_id' => $this->getId()));
				$listener->emptyEvent();	
				if ($this->getConfiguration('EndUpCmd') != '')
					$listener->addEvent($this->getConfiguration('EndUpCmd'));
				if ($this->getConfiguration('EndDownCmd') != '')
					$listener->addEvent($this->getConfiguration('EndDownCmd'));
				$listener->save();
			}
		}
	}
	public function AddCommande($Name,$_logicalId,$Type="info", $SubType='binary',$visible,$Value=null,$icon=null,$generic_type=null) {
		$Commande = $this->getCmd(null,$_logicalId);
		if (!is_object($Commande)){
			$Commande = new voletPropCmd();
			$Commande->setId(null);
			$Commande->setName($Name);
			$Commande->setIsVisible($visible);
			$Commande->setLogicalId($_logicalId);
			$Commande->setEqLogic_id($this->getId());
			$Commande->setType($Type);
			$Commande->setSubType($SubType);		
			if($Value != null)
				$Commande->setValue($Value);
			if($icon != null)
				$Commande->setDisplay('icon', $icon);
			if($generic_type != null)
				$Commande->setDisplay('generic_type', $generic_type);
			$Commande->save();
		} 
		return $Commande;
	}
	public function postSave() {
		$this->StopListener();
		$hauteur=$this->AddCommande("Hauteur","hauteur","info",'numeric',0,null,null,'FLAP_STATE');
		$this->AddCommande("Position","position","action",'slider',1,$hauteur->getId(),null,'FLAP_SLIDER');
		$this->AddCommande("Up","up","action", 'other',1,null,'<i class="fa fa-arrow-up"></i>','FLAP_UP');
		$this->AddCommande("Down","down","action", 'other',1,null,'<i class="fa fa-arrow-down"></i>','FLAP_DOWN');
		$this->AddCommande("Stop","stop","action", 'other',1,null,'<i class="fa fa-stop"></i>','FLAP_STOP');
		$this->StartListener();
		$this->CreateDemon();   
	}	
	
	public function preRemove() {
		$listener = listener::byClassAndFunction('voletProp', 'UpVolet', array('Volets_id' => $this->getId()));
		if (is_object($listener))
			$listener->remove();
		$listener = listener::byClassAndFunction('voletProp', 'DownVolet', array('Volets_id' => $this->getId()));
		if (is_object($listener))
			$listener->remove();
		$listener = listener::byClassAndFunction('voletProp', 'StopVolet', array('Volets_id' => $this->getId()));
		if (is_object($listener))
			$listener->remove();
		$listener = listener::byClassAndFunction('voletProp', 'EndVolet', array('Volets_id' => $this->getId()));
		if (is_object($listener))
			$listener->remove();
		$cron = cron::byClassAndFunction('voletProp', 'timeout', array('Volets_id' => $this->getId()));
		if (is_object($cron)) 	
			$cron->remove();
	}
}
class voletPropCmd extends cmd {
    public function execute($_options = null) {
		switch($this->getLogicalId()){
			case "up":
				if(cache::byKey('voletProp::Move::'.$this->getEqLogic()->getId())->getValue(false)){
					$this->getEqLogic()->getCmd('stop')->execute(null);
				}
				cache::set('voletProp::ChangeStateStart::'.$this->getEqLogic()->getId(),microtime(true), 0);
				$TempsTimeout = $this->getEqLogic()->getTime('TpsUp') * 1.1;
				cache::set('voletProp::TempsTimeout::'.$this->getEqLogic()->getId(),$TempsTimeout, 0);
				cache::set('voletProp::PropMove::'.$this->getEqLogic()->getId(),false, 0);
				cache::set('voletProp::Synchro::'.$this->getEqLogic()->getId(),true, 0);
				cache::set('voletProp::Move::'.$this->getEqLogic()->getId(),true, 0);
				cache::set('voletProp::ChangeState::'.$this->getEqLogic()->getId(),true, 0);
				$cmd=cmd::byId(str_replace('#','',$this->getEqLogic()->getConfiguration('cmdUp')));
				if(is_object($cmd)){
					log::add('voletProp','debug',$this->getEqLogic()->getHumanName().' Exécution de la commande '.$cmd->getHumanName());
					$cmd->execute(null);
				}
			break;
			case "down":
				if(cache::byKey('voletProp::Move::'.$this->getEqLogic()->getId())->getValue(false)){
					$this->getEqLogic()->getCmd('stop')->execute(null);
				}
				$TempsTimeout = $this->getEqLogic()->getTime('TpsDown') * 1.1;
				cache::set('voletProp::TempsTimeout::'.$this->getEqLogic()->getId(),$TempsTimeout, 0);
				cache::set('voletProp::ChangeStateStart::'.$this->getEqLogic()->getId(),microtime(true), 0);
				cache::set('voletProp::PropMove::'.$this->getEqLogic()->getId(),false, 0);
				cache::set('voletProp::Synchro::'.$this->getEqLogic()->getId(),true, 0);
				cache::set('voletProp::Move::'.$this->getEqLogic()->getId(),true, 0);
				cache::set('voletProp::ChangeState::'.$this->getEqLogic()->getId(),false, 0);
				$cmd=cmd::byId(str_replace('#','',$this->getEqLogic()->getConfiguration('cmdDown')));
				if(is_object($cmd)){
					log::add('voletProp','debug',$this->getEqLogic()->getHumanName().' Exécution de la commande '.$cmd->getHumanName());
					$cmd->execute(null);
				}
			break;
			case "stop":
				if(!cache::byKey('voletProp::Move::'.$this->getEqLogic()->getId())->getValue(false))
					return;
				if($this->getEqLogic()->getConfiguration('cmdStop') != ''){
					$cmd=cmd::byId(str_replace('#','',$this->getEqLogic()->getConfiguration('cmdStop')));
					if(is_object($cmd)){
						log::add('voletProp','debug',$this->getEqLogic()->getHumanName().' Exécution de la commande '.$cmd->getHumanName());
						$cmd->execute(null);
					}
				}else{
					if(cache::byKey('voletProp::ChangeState::'.$this->getEqLogic()->getId())->getValue(false))
						$cmd=cmd::byId(str_replace('#','',$this->getEqLogic()->getConfiguration('cmdUp')));
					else
						$cmd=cmd::byId(str_replace('#','',$this->getEqLogic()->getConfiguration('cmdDown')));
					if(is_object($cmd)){
						log::add('voletProp','debug',$this->getEqLogic()->getHumanName().' Exécution de la commande '.$cmd->getHumanName());
						$cmd->execute(null);
					}
				}
				cache::set('voletProp::Move::'.$this->getEqLogic()->getId(),false, 0);
				cache::set('voletProp::Synchro::'.$this->getEqLogic()->getId(),false, 0);
				cache::set('voletProp::PropMove::'.$this->getEqLogic()->getId(),false, 0);
				if(($this->getEqLogic()->getConfiguration('UpStateCmd') == '' || $this->getEqLogic()->getConfiguration('DownStateCmd') == '')){
					log::add('voletProp','debug',$this->getEqLogic()->getHumanName().' Mise à jour manuelle de la hauteur');
					cache::set('voletProp::ChangeStateStop::'.$this->getEqLogic()->getId(),microtime(true), 0);
					$this->getEqLogic()->UpdateHauteur();
				}
			break;
			case "position":
				if(!cache::byKey('voletProp::Move::'.$this->getEqLogic()->getId())->getValue(false)){
					cache::set('voletProp::PropMove::'.$this->getEqLogic()->getId(),$_options['slider'], 0);
				}
			break;
		}
	}
}
?>
