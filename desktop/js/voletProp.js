var DayPlanning = null;
$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
$('#tab_zones a').click(function(e) {
    e.preventDefault();
    $(this).tab('show');
});
$("body").on('click', ".listCmdAction", function() {
	var el = $(this).closest('.input-group').find('.CmdAction');
	var type=$(this).attr('data-type');
	jeedom.cmd.getSelectModal({cmd: {type: type}}, function (result) {
		el.value(result.human);
	});
});
function saveEqLogic(_eqLogic) {
	if (!isset(_eqLogic.configuration)) {
		_eqLogic.configuration = {};
	}
	_eqLogic.configuration.DayPlanning = DayPlanning.getSheetStates();
	return _eqLogic;
}
function printEqLogic(_eqLogic) {
	WeekPlanning(_eqLogic.configuration.DayPlanning,$('#DayPlanning'));
	$('.eqLogicAttr[data-l1key=configuration][data-l2key=Synchronisation] option').attr("selected", false);
	$.each(_eqLogic.configuration.Synchronisation, function( index, value ) {
		if(typeof value !== 'undefined' && value != '') 
			$('.eqLogicAttr[data-l1key=configuration][data-l2key=Synchronisation] option[value='+value+']').attr("selected", true);
	});
}
function addCmdToTable(_cmd) {
	var tr =$('<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">');
	tr.append($('<td>')
		.append($('<input type="hidden" class="cmdAttr form-control input-sm" data-l1key="id">'))
		.append($('<input type="hidden" class="cmdAttr form-control input-sm" data-l1key="type">'))
		.append($('<input type="hidden" class="cmdAttr form-control input-sm" data-l1key="subType">'))
		.append($('<input class="cmdAttr form-control input-sm" data-l1key="name" value="' + init(_cmd.name) + '" placeholder="{{Name}}" title="Name">')));
	var parmetre=$('<td>');	
	if (is_numeric(_cmd.id)) {
		parmetre.append($('<a class="btn btn-default btn-xs cmdAction" data-action="test">')
			.append($('<i class="fa fa-rss">')
				.text('{{Tester}}')));
	}
	parmetre.append($('<a class="btn btn-default btn-xs cmdAction tooltips" data-action="configure">')
		.append($('<i class="fa fa-cogs">')));
	parmetre.append($('<div>')
		.append($('<span>')
			.append($('<label class="checkbox-inline">')
				.append($('<input type="checkbox" class="cmdAttr checkbox-inline" data-size="mini" data-label-text="{{Historiser}}" data-l1key="isHistorized"/>'))
				.append('{{Historiser}}')
				.append($('<sup>')
					.append($('<i class="fa fa-question-circle tooltips" style="font-size : 1em;color:grey;">')
					.attr('title','Souhaitez-vous historiser les changements de valeurs ?'))))));
	parmetre.append($('<div>')
		.append($('<span>')
			.append($('<label class="checkbox-inline">')
				.append($('<input type="checkbox" class="cmdAttr checkbox-inline" data-size="mini" data-label-text="{{Afficher}}" data-l1key="isVisible" checked/>'))
				.append('{{Afficher}}')
				.append($('<sup>')
					.append($('<i class="fa fa-question-circle tooltips" style="font-size : 1em;color:grey;">')
					.attr('title','Souhaitez-vous afficher cette commande sur le dashboard ?'))))));
	tr.append(parmetre);
	$('#table_cmd tbody').append(tr);
	$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
	jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}
function WeekPlanning(_planning,_el) { 
	var dayList = [{name:"Lundi"},{name:"Mardi"},{name:"Mercredi"},{name:"Jeudi"},{name:"Vendredi"},{name:"Samedi"},{name:"Dimanche"},{name:"Congée"}];
	var hourList = [
		{name:"00",title:"00:00-00:30"},{name:":30",title:"00:30-01:00"},{name:"01",title:"01:00-01:30"},{name:":30",title:"01:30-02:00"},
		{name:"02",title:"02:00-02:30"},{name:":30",title:"02:30-03:00"},{name:"03",title:"03:00-03:30"},{name:":30",title:"03:30-04:00"},
		{name:"04",title:"04:00-04:30"},{name:":30",title:"04:30-05:00"},{name:"05",title:"05:00-05:30"},{name:":30",title:"05:03-06:00"},
		{name:"06",title:"06:00-06:30"},{name:":30",title:"06:30-07:00"},{name:"07",title:"07:00-07:30"},{name:":30",title:"07:30-08:00"},
		{name:"08",title:"08:00-08:30"},{name:":30",title:"08:30-09:00"},{name:"09",title:"09:00-09:30"},{name:":30",title:"09:30-10:00"},
		{name:"10",title:"10:00-10:30"},{name:":30",title:"10:30-11:00"},{name:"11",title:"11:00-11:30"},{name:":30",title:"11:30-12:00"},
		{name:"12",title:"12:00-12:30"},{name:":30",title:"12:30-13:00"},{name:"13",title:"13:00-13:30"},{name:":30",title:"13:30-14:00"},
		{name:"14",title:"14:00-14:30"},{name:":30",title:"14:30-15:00"},{name:"15",title:"15:00-15:30"},{name:":30",title:"15:30-16:00"},
		{name:"16",title:"16:00-16:30"},{name:":30",title:"16:30-17:00"},{name:"17",title:"17:00-17:30"},{name:":30",title:"17:03-18:00"},
		{name:"18",title:"18:00-18:30"},{name:":30",title:"18:30-19:00"},{name:"19",title:"19:00-19:30"},{name:":30",title:"19:30-20:00"},
		{name:"20",title:"20:00-20:30"},{name:":30",title:"20:30-21:00"},{name:"21",title:"21:00-21:30"},{name:":30",title:"21:30-22:00"},
		{name:"22",title:"22:00-22:30"},{name:":30",title:"22:30-23:00"},{name:"23",title:"23:00-23:30"},{name:":30",title:"23:30-00:00"},
	];
	var dimensions = [dayList.length,hourList.length];
   	DayPlanning = _el.TimeSheet({
		data: {
			dimensions : dimensions,
			colHead : hourList,
			rowHead : dayList,
			sheetHead : {name:""},
			sheetData : _planning
		}
        });
}
