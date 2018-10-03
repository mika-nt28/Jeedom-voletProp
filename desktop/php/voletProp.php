<?php
if (!isConnect('admin')) {
throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'voletProp');
$eqLogics = eqLogic::byType('voletProp');
?>
<div class="row row-overflow">
	<link rel="stylesheet" href="https://openlayers.org/en/v4.1.1/css/ol.css" type="text/css">
	<script src="https://openlayers.org/en/v4.3.3/build/ol.js" type="text/javascript"></script>
	<div class="col-lg-2">
		<div class="bs-sidebar">
			<ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
				<a class="btn btn-default eqLogicAction" style="width : 50%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter}}</a>
				<li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
				<?php
					foreach ($eqLogics as $eqLogic) 
						echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
				?>
			</ul>
		</div>
	</div>
	<div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
		<legend>{{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction" data-action="add" style="background-color : #ffffff; height : 140px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
				<center>
					<i class="fa fa-plus-circle" style="font-size : 5em;color:#406E88;"></i>
				</center>
				<span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#406E88"><center>{{Ajouter}}</center></span>
			</div>
			<div class="cursor eqLogicAction" data-action="gotoPluginConf" style="height: 120px; margin-bottom: 10px; padding: 5px; border-radius: 2px; width: 160px; margin-left: 10px; position: absolute; left: 170px; top: 0px; background-color: rgb(255, 255, 255);">
				<center>
			      		<i class="fa fa-wrench" style="font-size : 5em;color:#767676;"></i>
			    	</center>
			    	<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>Configuration</center></span>
			</div>
		</div>
		<legend>{{Mes Volets}}</legend>
		<input class="form-control" placeholder="{{Rechercher}}" style="margin-bottom:4px;" id="in_searchEqlogic" />
		<div class="eqLogicThumbnailContainer">
			<?php
				foreach ($eqLogics as $eqLogic) {
					$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
					echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
					echo "<center>";
					echo '<img src="plugins/voletProp/plugin_info/voletProp_icon.png" height="105" width="95" />';
					echo "</center>";
					echo '<span class="name" style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
					echo '</div>';
				}
			?>
		</div>
	</div>  
	<div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
		<a class="btn btn-success btn-sm eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> Sauvegarder</a>
		<a class="btn btn-danger btn-sm eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> Supprimer</a>
		<a class="btn btn-default btn-sm eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i></a>
		<a class="btn btn-default btn-sm eqLogicAction pull-right " data-action="copy"><i class="fa fa-copy"></i></a>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation">
				<a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay">
					<i class="fa fa-arrow-circle-left"></i>
				</a>
			</li>
			<li role="presentation" class="active">
				<a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="true">
					<i class="fa fa-tachometer"></i> Equipement</a>
			</li>
			<li role="presentation" class="">
				<a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab" aria-expanded="false">
					<i class="fa fa-list-alt"></i> Commandes</a>
			</li>
		</ul>
			<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
				<div role="tabpanel" class="tab-pane active" id="eqlogictab">
					<div class="col-sm-6">
						<form class="form-horizontal">
							<legend>Général</legend>
							<fieldset>
								<div class="form-group ">
									<label class="col-md-3 control-label">{{Nom du volet}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Indiquer le nom de votre volet}}" style="font-size : 1em;color:grey;"></i>
										</sup>
									</label>
									<div class="col-md-5">
										<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
										<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom du volet}}"/>
									</div>
								</div>
								<div class="form-group">
									<label class="col-md-3 control-label" >{{Objet parent}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Indiquer l'objet dans lequel le widget de cette zone apparaîtra sur le Dashboard}}" style="font-size : 1em;color:grey;"></i>
										</sup>
									</label>
									<div class="col-md-5">
										<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
											<option value="">{{Aucun}}</option>
											<?php
												foreach (object::all() as $object) 
													echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
											?>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-md-3 control-label">
										{{Catégorie}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Choisir une catégorie. Cette information n'est pas obigatoire mais peut être utile pour filtrer les widgets}}" style="font-size : 1em;color:grey;"></i>
										</sup>
									</label>
									<div class="col-md-8">
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
									<label class="col-md-3 control-label" >
										{{Etat du widget}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Choisir les options de visibilité et d'activation. Si l'équipement n'est pas activé, il ne sera pas utilisable dans Jeedom ni visible sur le Dashboard. Si l'équipement n'est pas visible, il sera caché sur le Dashboard}}" style="font-size : 1em;color:grey;"></i>
										</sup>
									</label>
									<div class="col-md-5">
										<label>{{Activer}}</label>
										<input type="checkbox" class="eqLogicAttr" data-label-text="{{Activer}}" data-l1key="isEnable" checked/>
										<label>{{Visible}}</label>
										<input type="checkbox" class="eqLogicAttr" data-label-text="{{Visible}}" data-l1key="isVisible" checked/>
									</div>
								</div>
								<div class="form-group ">
									<label class="col-md-3 control-label">{{Délais minimum entre 2 commandes}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Saisir un délais minimum(s) entre 2 commande}}" style="font-size : 1em;color:grey;"></i>
										</sup>
									</label>
									<div class="col-md-5">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration"  data-l2key="delaisMini" placeholder="{{Délais minimum (s)}}"/>
									</div>
								</div>
								<div class="form-group">
									<label class="col-md-3 control-label" >
										{{Synchronisation}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Si actif le volet se fermera completement avant de remonté a la bonne hauteur}}" style="font-size : 1em;color:grey;"></i>
										</sup>
									</label>
									<div class="col-md-5">
										<select class="eqLogicAttr form-control input-sm" data-l1key="configuration" data-l2key="Synchronisation" multiple>
											<option value="">{{Aucune}}</option>
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
									<label class="col-md-3 control-label">{{Objet de montée}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Sélectionner la commande déterminant la montée du volet}}"></i>
										</sup>
									</label>
									<div class="col-md-5">
										<div class="input-group">
											<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="cmdUp" placeholder="{{Séléctionner une commande}}"/>
											<span class="input-group-btn">
												<a class="btn btn-success btn-sm listCmdAction" data-type="action">
													<i class="fa fa-list-alt"></i>
												</a>
											</span>
										</div>
									</div>
								</div>	
								<div class="form-group">
									<label class="col-md-3 control-label">{{Objet de stop}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Sélectionner la commande déterminant la arret du volet}}"></i>
										</sup>
									</label>
									<div class="col-md-5">
										<div class="input-group">
											<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="cmdStop" placeholder="{{Séléctionner une commande}}"/>
											<span class="input-group-btn">
												<a class="btn btn-success btn-sm listCmdAction" data-type="action">
													<i class="fa fa-list-alt"></i>
												</a>
											</span>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-md-3 control-label">{{Objet de decente}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Sélectionner la commande déterminant la decente du volet}}"></i>
										</sup>
									</label>
									<div class="col-md-5">
										<div class="input-group">
											<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="cmdDown" placeholder="{{Séléctionner une commande}}"/>
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
									<label class="col-md-3 control-label">{{Condition d'etat montée}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Saisir la condition qui valide une montée}}"></i>
										</sup>
									</label>
									<div class="col-md-5">
										<div class="input-group">
											<span class="input-group-btn">
												<a class="btn btn-success listCmdAction" data-type="info">
													<i class="fa fa-list-alt"></i>
												</a>
											</span>
											<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="UpStateCmd" placeholder="{{Séléctionner une commande}}"/>
											<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="UpStateOperande">
												<option value="==">{{égal}}</option>                  
												<option value=">">{{supérieur}}</option>                  
												<option value="<">{{inférieur}}</option>                 
												<option value="!=">{{différent}}</option> 
											</select>
											<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="UpStateValue" placeholder="{{Valeur pour validé la condition}}"/>
										</div>
									</div>
								</div>	
								<div class="form-group">
									<label class="col-md-3 control-label">{{Condition d'etat descente}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Saisir la condition qui valide une descente}}"></i>
										</sup>
									</label>
									<div class="col-md-5">
										<div class="input-group">
											<span class="input-group-btn">
												<a class="btn btn-success listCmdAction" data-type="info">
													<i class="fa fa-list-alt"></i>
												</a>
											</span>
											<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="DownStateCmd" placeholder="{{Séléctionner une commande}}"/>
											<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="DownStateOperande">
												<option value="==">{{égal}}</option>                  
												<option value=">">{{supérieur}}</option>                  
												<option value="<">{{inférieur}}</option>                 
												<option value="!=">{{différent}}</option> 
											</select>
											<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="DownStateValue" placeholder="{{Valeur pour validé la condition}}"/>
										</div>
									</div>
								</div>	
								<div class="form-group">
									<label class="col-md-3 control-label">{{Condition d'etat arret}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Saisir la condition qui valide un arret du volet}}"></i>
										</sup>
									</label>
									<div class="col-md-5">
										<div class="input-group">
											<span class="input-group-btn">
												<a class="btn btn-success listCmdAction" data-type="info">
													<i class="fa fa-list-alt"></i>
												</a>
											</span>
											<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="StopStateCmd" placeholder="{{Séléctionner une commande}}"/>
											<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="StopStateOperande">
												<option value="==">{{égal}}</option>                  
												<option value=">">{{supérieur}}</option>                  
												<option value="<">{{inférieur}}</option>                 
												<option value="!=">{{différent}}</option> 
											</select>
											<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="StopStateValue" placeholder="{{Valeur pour validé la condition}}"/>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-md-3 control-label">{{Fin de course}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Sélectionner la commande déterminant la fin de course}}"></i>
										</sup>
									</label>
									<div class="col-md-5">
										<div class="input-group">
											<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="cmdEnd" placeholder="{{Séléctionner une commande}}"/>
											<span class="input-group-btn">
												<a class="btn btn-success btn-sm listCmdAction" data-type="info">
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
							<legend>Delais</legend>
							<fieldset>
								<div class="form-group">
									<label class="col-md-3 control-label">{{Temps total}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Saisissez le temps total pour executer une montée ou une decente}}"></i>
										</sup>
									</label>
									<div class="col-md-5">
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
									<label class="col-md-3 control-label">{{Temps de décollement}}
										<sup>
											<i class="fa fa-question-circle tooltips" title="{{Saisissez le temps de decollement. Temps avant que le volet se decolle de son seuil}}"></i>
										</sup>
									</label>
									<div class="col-md-5">
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
