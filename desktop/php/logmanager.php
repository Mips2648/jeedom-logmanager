<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('logmanager');
sendVarToJS('eqType', $plugin->getId());
/** @var logmanager[] */
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoSecondary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
			<div class="cursor pluginAction logoSecondary" data-action="openLocation" data-location="<?= $plugin->getDocumentation() ?>">
				<i class="fas fa-book"></i>
				<br>
				<span>{{Documentation}}</span>
			</div>
			<div class="cursor pluginAction logoSecondary" data-action="openLocation" data-location="https://community.jeedom.com/tag/plugin-<?= $plugin->getId() ?>">
				<i class="fas fa-comments"></i>
				<br>
				<span>Community</span>
			</div>
		</div>
		<legend><i class="fas fa-file-alt"></i> {{Mes Logs}}</legend>
		<div class="input-group" style="margin:5px;">
			<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
			<div class="input-group-btn">
				<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i>
				</a><a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>
			</div>
		</div>
		<div class="eqLogicThumbnailContainer">
			<?php
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $eqLogic->getImage() . '"/>';
				echo "<br>";
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '<span class="hiddenAsCard displayTableRight hidden">';
				echo '<span class="label label-info">' . ucfirst(logManagerLevel::LOGLEVEL[$eqLogic->getConfiguration('loglevel', 100)]) . '</span>';
				echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
				echo '</span>';
				echo '</div>';
			}
			?>
		</div>
	</div>

	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fa fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs"> {{Dupliquer}}</span>
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i><span class="hidden-xs"> {{Supprimer}}</span></a>
				</a>
			</span>
		</div>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">


				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-7">
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}" />
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Objet parent}}</label>
								<div class="col-sm-6">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Catégorie}}</label>
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
								<label class="col-sm-3 control-label">{{Options}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked />{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked />{{Visible}}</label>
								</div>
							</div>

							<legend><i class="fas fa-cogs"></i> {{Paramètres spécifiques}}</legend>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Niveau de log}}</label>
								<div class="col-sm-3">
									<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="loglevel">
										<?php
										$options = '';
										foreach (logManagerLevel::LOGLEVEL as $key => $value) {
											$options .= '<option value="' . $key . '">' . ucfirst($value) . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label help" data-help="Générer un événement pour tout log écrit à partir du niveau de log sélectionné">{{Générer un événement}}</label>
								<div class="col-sm-3">
									<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="eventlevel">
										<option value="">{{Désactivé}}</option>
										<?php
										$options = '';
										foreach (logManagerLevel::LOGLEVEL as $key => $value) {
											$options .= '<option value="' . $key . '">' . ucfirst($value) . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
								<div class="col-sm-6">
									<div class="alert alert-info globalRemark">{{Génére un événement #lm-debug#, #lm-info#, #lm-warning# ou #lm-error# qui peut être utilisé comme déclencheur de scénario.}}</div>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Tuile}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="displayContentWidget" />{{Afficher le contenu du log sur la tuile}}</label>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label help" data-help="{{entre 0 et 3000}}">{{Nombre de lignes à afficher}}</label>
								<div class="col-sm-3">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="nbrLinesWidget" placeholder="1000" />
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label help" data-help="{{Par défaut les entrées les plus récentes sont affichées en haut et les plus anciennes en bas}}">{{Ordre d'affichage}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="topToBottom" />{{Afficher par ordre chronologique}}</label>
								</div>
							</div>
						</div>
					</fieldset>
				</form>
			</div>
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<div class="table-responsive">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th style="min-width:220px;width:350px;">{{Nom}}</th>
								<th style="min-width:140px;width:200px;">{{Type}}</th>
								<th style="min-width:260px;">{{Options}}</th>
								<th style="min-width:80px;width:140px;">{{Actions}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<?php include_file('desktop', 'logmanager', 'js', 'logmanager'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>