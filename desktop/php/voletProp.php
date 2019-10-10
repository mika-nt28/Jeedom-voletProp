<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('voletProp');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">    
   	<div class="col-xs-12 eqLogicThumbnailDisplay">
  		<legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter}}</span>
			</div>
      			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
      				<i class="fas fa-wrench"></i>
    				<br>
    				<span>{{Configuration}}</span>
  			</div>
  		</div>
  		<legend><i class="fas fa-table"></i> {{Mes volets proportionnel}}</legend>
	   	<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
		<div class="eqLogicThumbnailContainer">
    		<?php
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '</div>';
			}
		?>
		</div>
	</div>
	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure">
					<i class="fa fa-cogs"></i>
					 {{Configuration avancée}}
				</a>
				<a class="btn btn-default btn-sm eqLogicAction" data-action="copy">
					<i class="fas fa-copy"></i>
					 {{Dupliquer}}
				</a>
				<a class="btn btn-sm btn-success eqLogicAction" data-action="save">
					<i class="fas fa-check-circle"></i>
					 {{Sauvegarder}}
				</a>
				<a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove">
					<i class="fas fa-minus-circle"></i>
					 {{Supprimer}}
				</a>
			</span>
		</div>
		<ul class="nav nav-tabs" role="tablist">
    			<li role="presentation">
				<a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay">
					<i class="fa fa-arrow-circle-left"></i>
				</a>
			</li>
    			<li role="presentation" class="active">
				<a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab">
				<i class="fa fa-tachometer"></i> 
					{{Equipement}}
				</a>
			</li>
    			<li role="presentation">
				<a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab">
					<i class="fa fa-list-alt"></i> 
					{{Commandes}}
				</a>
			</li>
  		</ul>
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<div class="col-sm-6">
    					<form class="form-horizontal">
						<legend>Général</legend>
							<fieldset>
								<div class="form-group ">
									<label class="col-sm-3 control-label">{{Nom du volet}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Indiquer le nom de votre volet}}" style="font-size : 1em;color:grey;"></i>
										</sup>
									</label>
									<div class="col-sm-3">
										<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
										<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom du volet}}"/>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label" >{{Objet parent}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Indiquer l'objet dans lequel le widget de cette zone apparaîtra sur le Dashboard}}" style="font-size : 1em;color:grey;"></i>
										</sup>
									</label>
									<div class="col-sm-3">
										<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
											<option value="">{{Aucun}}</option>
											<?php
												foreach (jeeObject::all() as $object) 
													echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
											?>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label">
										{{Catégorie}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Choisir une catégorie. Cette information n'est pas obigatoire mais peut être utile pour filtrer les widgets}}" style="font-size : 1em;color:grey;"></i>
										</sup>
									</label>
									<div class="col-sm-9">
										<?php
										foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
											echo '<label class="checkbox-inline">';
											echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
											echo '</label>';
										}
										?>

									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label" >
										{{Etat du widget}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Choisir les options de visibilité et d'activation. Si l'équipement n'est pas activé, il ne sera pas utilisable dans Jeedom ni visible sur le Dashboard. Si l'équipement n'est pas visible, il sera caché sur le Dashboard}}" style="font-size : 1em;color:grey;"></i>
										</sup>
									</label>
									<div class="col-sm-9">
										<label class="checkbox-inline">
											<input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>
											{{Activer}}
										</label>
										<label class="checkbox-inline">
											<input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>
											{{Visible}}
										</label>
									</div>
								</div>
								<div class="form-group ">
									<label class="col-sm-3 control-label">{{Délais minimum entre 2 commandes}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Saisir un délai minimum(s) entre 2 commande}}" style="font-size : 1em;color:grey;"></i>
										</sup>
									</label>
									<div class="col-sm-5">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration"  data-l2key="delaisMini" placeholder="{{Délais minimum (s)}}"/>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label" >
										{{Synchronisation}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Si actif le volet se fermera complètement avant de remonter à la bonne hauteur}}" style="font-size : 1em;color:grey;"></i>
										</sup>
									</label>
									<div class="col-sm-5">
										<select class="eqLogicAttr form-control custom-select" data-l1key="configuration" data-l2key="Synchronisation" multiple>
											<option value="all">{{A chaque mouvement}}</option>
											<option value="100">{{Lors d'une montée total (100%)}}</option>
											<option value="0">{{Lors d'une descente total (0%)}}</option>
										</select>
									</div>
								</div>
							</fieldset>
						</form>
					</div>
					<div class="col-sm-6">
						<form class="form-horizontal">
							<legend>Objet de control du volet</legend>
							<fieldset>
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Objet de montée}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Sélectionner la commande déterminant la montée du volet}}"></i>
										</sup>
									</label>
									<div class="col-sm-5">
										<div class="input-group">
											<input type="text" class="eqLogicAttr form-control CmdAction" data-l1key="configuration" data-l2key="cmdUp" placeholder="{{Séléctionner une commande}}"/>
											<span class="input-group-btn">
												<a class="btn btn-success btn-sm listCmdAction" data-type="action">
													<i class="fa fa-list-alt"></i>
												</a>
											</span>
										</div>
									</div>
								</div>	
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Objet de stop}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Sélectionner la commande déterminant l’arrêt du volet}}"></i>
										</sup>
									</label>
									<div class="col-sm-5">
										<div class="input-group">
											<input type="text" class="eqLogicAttr form-control CmdAction" data-l1key="configuration" data-l2key="cmdStop" placeholder="{{Séléctionner une commande}}"/>
											<span class="input-group-btn">
												<a class="btn btn-success btn-sm listCmdAction" data-type="action">
													<i class="fa fa-list-alt"></i>
												</a>
											</span>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Objet de decente}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Sélectionner la commande déterminant la descente du volet}}"></i>
										</sup>
									</label>
									<div class="col-sm-5">
										<div class="input-group">
											<input type="text" class="eqLogicAttr form-control CmdAction" data-l1key="configuration" data-l2key="cmdDown" placeholder="{{Séléctionner une commande}}"/>
											<span class="input-group-btn">
												<a class="btn btn-success btn-sm listCmdAction" data-type="action">
													<i class="fa fa-list-alt"></i>
												</a>
											</span>
											
										</div>
									</div>
								</div>	
							</fieldset>
						</form>
					</div>
					<div class="col-sm-6">
						<form class="form-horizontal">
							<legend>Objet d'état du volet</legend>
							<fieldset>
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Utiliser les etat sur :}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Cocher les options d’utilisation du retour d’état}}"></i>
										</sup>
									</label>
									<div class="col-sm-9">
										<label>{{Les mouvement Jeedom}}</label>
										<input type="checkbox" class="eqLogicAttr" data-label-text="{{Jeedom}}" data-l1key="configuration" data-l2key="useStateJeedom"/>
										<label>{{Les mouvement manuel}}</label>
										<input type="checkbox" class="eqLogicAttr" data-label-text="{{Manuel}}" data-l1key="configuration" data-l2key="useStateManual"/>
									</div>
								</div>	
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Condition d'etat montée}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Saisir la condition qui valide une montée}}"></i>
										</sup>
									</label>
									<div class="col-sm-9">
										<div class="input-group input-group-sm">
											<span class="input-group-btn">
												<a class="btn btn-success listCmdAction input-group-addon roundedLeft" data-type="info">
													<i class="fa fa-list-alt"></i>
												</a>
											</span>
											<input type="text" class="eqLogicAttr form-control CmdAction" data-l1key="configuration" data-l2key="UpStateCmd" placeholder="Séléctionner une commande" style="width: 180px">
											<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="UpStateOperande" style="width: 80px">
												<option value="==" selected="selected">égal</option>                  
												<option value=">">supérieur</option>                  
												<option value="<">inférieur</option>                 
												<option value="!=">différent</option> 
											</select>
											<input type="text" class="eqLogicAttr form-control roundedRight" data-l1key="configuration" data-l2key="UpStateValue" placeholder="Valeur pour validé la condition" style="width: 80px">
										</div>
									</div>
								</div>	
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Condition d'etat descente}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Saisir la condition qui valide une descente}}"></i>
										</sup>
									</label>
									<div class="col-sm-9">
										<div class="input-group input-group-sm">
											<span class="input-group-btn">
												<a class="btn btn-success listCmdAction input-group-addon roundedLeft" data-type="info">
													<i class="fa fa-list-alt"></i>
												</a>
											</span>
											<input type="text" class="eqLogicAttr form-control CmdAction" data-l1key="configuration" data-l2key="DownStateCmd" placeholder="Séléctionner une commande" style="width: 180px">
											<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="DownStateOperande" style="width: 80px">
												<option value="==" selected="selected">égal</option>                  
												<option value=">">supérieur</option>                  
												<option value="<">inférieur</option>                 
												<option value="!=">différent</option> 
											</select>
											<input type="text" class="eqLogicAttr form-control roundedRight" data-l1key="configuration" data-l2key="DownStateValue" placeholder="Valeur pour validé la condition" style="width: 80px">
										</div>
									</div>
								</div>	
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Condition d'etat arret}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Saisir la condition qui valide un arret du volet}}"></i>
										</sup>
									</label>
									<div class="col-sm-9">
										<div class="input-group input-group-sm">
											<span class="input-group-btn">
												<a class="btn btn-success listCmdAction input-group-addon roundedLeft" data-type="info">
													<i class="fa fa-list-alt"></i>
												</a>
											</span>
											<input type="text" class="eqLogicAttr form-control CmdAction" data-l1key="configuration" data-l2key="StopStateCmd" placeholder="Séléctionner une commande" style="width: 180px">
											<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="StopStateOperande" style="width: 80px">
												<option value="==" selected="selected">égal</option>                  
												<option value=">">supérieur</option>                  
												<option value="<">inférieur</option>                 
												<option value="!=">différent</option> 
											</select>
											<input type="text" class="eqLogicAttr form-control roundedRight" data-l1key="configuration" data-l2key="StopStateValue" placeholder="Valeur pour validé la condition" style="width: 80px">
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Fin de course haute}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Saisir la condition qui valide une fin de course du volet ouvert}}"></i>
										</sup>
									</label>
									<div class="col-sm-9">
										<div class="input-group input-group-sm">
											<span class="input-group-btn">
												<a class="btn btn-success listCmdAction input-group-addon roundedLeft" data-type="info">
													<i class="fa fa-list-alt"></i>
												</a>
											</span>
											<input type="text" class="eqLogicAttr form-control CmdAction" data-l1key="configuration" data-l2key="EndUpCmd" placeholder="Séléctionner une commande" style="width: 180px">
											<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="EndUpOperande" style="width: 80px">
												<option value="==" selected="selected">égal</option>                  
												<option value=">">supérieur</option>                  
												<option value="<">inférieur</option>                 
												<option value="!=">différent</option> 
											</select>
											<input type="text" class="eqLogicAttr form-control roundedRight" data-l1key="configuration" data-l2key="EndUpValue" placeholder="Valeur pour validé la condition" style="width: 80px">
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Fin de course basse}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Saisir la condition qui valide une fin de course du volet fermé}}"></i>
										</sup>
									</label>
									<div class="col-sm-9">
										<div class="input-group input-group-sm">
											<span class="input-group-btn">
												<a class="btn btn-success listCmdAction input-group-addon roundedLeft" data-type="info">
													<i class="fa fa-list-alt"></i>
												</a>
											</span>
											<input type="text" class="eqLogicAttr form-control CmdAction" data-l1key="configuration" data-l2key="EndDownCmd" placeholder="Séléctionner une commande" style="width: 180px">
											<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="EndDownOperande" style="width: 80px">
												<option value="==" selected="selected">égal</option>                  
												<option value=">">supérieur</option>                  
												<option value="<">inférieur</option>                 
												<option value="!=">différent</option> 
											</select>
											<input type="text" class="eqLogicAttr form-control roundedRight" data-l1key="configuration" data-l2key="EndDownValue" placeholder="Valeur pour validé la condition" style="width: 80px">
										</div>
									</div>
								</div>
							</fieldset>
						</form>
					</div>
					<div class="col-sm-6">
						<form class="form-horizontal">
							<legend>Delais</legend>
							<fieldset>
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Temps total}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Saisissez le temps total pour exécuter une montée ou une descente}}"></i>
										</sup>
									</label>
									<div class="col-sm-5">
										<div class="input-group">
											<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="Ttotal" placeholder="{{Saisir le temps de décollement}}"/>
											<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="TtotalBase">
												<option value="1000000">{{Seconde}}</option>                  
												<option value="1000">{{Miliseconde}}</option>                  
												<option value="1">{{Microseconde}}</option>   
											</select>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Temps de décollement}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Saisissez le temps de décollement. Temps avant que le volet se décolle de son seuil}}"></i>
										</sup>
									</label>
									<div class="col-sm-5">
										<div class="input-group">
											<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="Tdecol" placeholder="{{Saisir le temps de décollement}}"/>
											<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="TdecolBase">
												<option value="1000000">{{Seconde}}</option>                  
												<option value="1000">{{Miliseconde}}</option>                  
												<option value="1">{{Microseconde}}</option>   
											</select>
										</div>
									</div>
								</div>
							</fieldset>
						</form>
					</div>
				</div>
				<div role="tabpanel" class="tab-pane" id="commandtab">	
					<table id="table_cmd" class="table table-bordered table-condensed">
					    <thead>
						<tr>
						    <th>{{Nom}}</th>
						    <th>{{Paramètre}}</th>
						</tr>
					    </thead>
					    <tbody></tbody>
					</table>
				</div>	
			</div>
		</div>
</div>

<?php include_file('desktop', 'voletProp', 'js', 'voletProp'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
