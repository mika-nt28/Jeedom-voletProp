<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class voletProp extends eqLogic {
    public function execPropVolet($hauteur) {
		$HauteurVolet=$this->getCmd(null,'hauteur')->execCmd();
		if($HauteurVolet > $hauteur){
				$cmd=cmd::byId($this->getConfiguration('cmdDown'));
				if(is_object($cmd))
					$cmd->event();
				$Delta=$HauteurVolet-$hauteur;
		}else{
			$cmd=cmd::byId($this->getConfiguration('cmdUp'));
				if(is_object($cmd))
					$cmd->event();
				$Delta=$hauteur-$HauteurVolet;
		}
		sleep($this->TpsAction($Delta));
		$cmd=cmd::byId($this->getConfiguration('cmdStop'));
		if(is_object($cmd))
			$cmd->event();
	}
    public function TpsAction($Hauteur) {
		return $this->getConfiguration('Ttotal')*$Hauteur/100;
	}
	public function AddCommande($Name,$_logicalId,$Type="info", $SubType='binary',$visible,$Value=null,$Template='') {
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
		$Commande->save();
		return $Commande;
	}
	public function postSave() {
		$hauteur=$this->AddCommande("Hauteur","hauteur","info", 'numeric',true);
		$this->AddCommande("Position","position","action", 'slider',true,$hauteur->getId());
		$this->AddCommande("Up","up","action", 'other',true);
		$this->AddCommande("Down","down","action", 'other',true);
		$this->AddCommande("Stop","stop","action", 'other',true);
	}	
}
class voletPropCmd extends cmd {
    public function execute($_options = null) {
		switch($this->getLogicalId()){
			case "up":
				$cmd=cmd::byId($this->getEqLogic()->getConfiguration('cmdUp'));
				if(is_object($cmd))
					$cmd->event();
			break;
			case "down":
				$cmd=cmd::byId($this->getEqLogic()->getConfiguration('cmdDown'));
				if(is_object($cmd))
					$cmd->event();
			break;
			case "stop":
				$cmd=cmd::byId($this->getEqLogic()->getConfiguration('cmdStop'));
				if(is_object($cmd))
					$cmd->event();
			break;
			case "position":
				$this->getEqLogic()->execPropVolet($_options['slider']);
			break;
		}
	}
}
?>
