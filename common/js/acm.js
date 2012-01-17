jQuery( document ).ready( function( $ ) {
	var last_selected, subgrid_lastsel;
	var base_url = acm_url;
	var grid_selector = jQuery("#acm-codes-list"); //avoid unnecessary selector calls
	var subgrid_selector = jQuery( '#acm-codes-conditionals-list' );
	var actions = {
			  codes_datasource: ajaxurl + '?acm-action=datasource',
			  codes_edit: ajaxurl + '?acm-action=edit',
			  conditionals_datasource: ajaxurl + '?acm-action=datasource-conditionals',
			  conditionals_edit: ajaxurl + '?acm-action=edit-conditionals'
	};
	var conditionals_options = acm_conditionals;

	grid_selector.jqGrid({
		datatype: "json",
		url: actions.codes_datasource,
		editurl: actions.codes_edit,
		height: 400,
		width: 600,
		colNames:['Id','Site Name', 'Zone1', 'Actions'],
		colModel:[
			{name:'id',index:'id', width:60, sorttype:"int"},
			{name:'site_name',index:'site_name', width:200, editable: true, edittype:'text'},
			{name:'zone1',index:'zone1', width:100, editable: true},
			{name:'act',index:'act', width:125,sortable:false, align: 'center'},
		],
		prmNames:{ page: 'acm-grid-page' },
		postData: {nonce: acm_ajax_nonce, action: 'acm_ajax_handler'},
		rowNum:10,
		rowList:[10,20,30],
		pager: '#acm-codes-pager',
		sortname: 'id',
		viewrecords: true,
		sortorder: "desc",
		caption:"Ad Codes",
		jsonReader : { repeatitems: false }, // workaround for jqGrid issue
		gridComplete: function(){
			var ids = grid_selector.jqGrid( 'getDataIDs' );
			for(var i=0;i < ids.length;i++){
				var cl = ids[i];
				be = "<input style='height:22px;width:50px;' type='button' value='Edit' onclick=\"jQuery( '#acm-codes-list' ).editRow( '"+cl+"' );\"  />"; 
				se = "<input style='height:22px;width:50px;' type='button' value='Save' onclick=\"jQuery( '#acm-codes-list' ).saveRow( '"+cl+"' );\"  />";  
				grid_selector.jqGrid( 'setRowData',ids[i],{act:be+se});
			}	
		},
		onSelectRow: function(ids) {
			if(ids == null) {
				ids=0;
				if(subgrid_selector.jqGrid( 'getGridParam','records' ) >0 )
				{
					subgrid_selector.jqGrid( 'setGridParam',{ url:actions.conditionals_datasource + "&id="+ids, page:1, editurl: actions.conditionals_edit + "&id="+ids } );
					subgrid_selector.jqGrid( 'setCaption',"Conditionals for Ad Code #: "+ids)
					.trigger( 'reloadGrid' );
				}
			} else {
				subgrid_selector.jqGrid( 'setGridParam',{ url:actions.conditionals_datasource + "&id="+ids, page:1, editurl: actions.conditionals_edit + "&id="+ids } );
				subgrid_selector.jqGrid( 'setCaption',"Conditionals for Ad Code # "+ids)
				.trigger( 'reloadGrid' );			
			}
		}	
	});
	
	grid_selector.jqGrid( 'navGrid','#acm-codes-pager',{edit:true,add:true,del:true});

	subgrid_selector.jqGrid({
		height: 100,
		url: actions.conditionals_datasource,
		editurl: actions.conditionals_edit,
		prmNames:{ page: 'acm-grid-page' },
		postData: {nonce: acm_ajax_nonce, action: 'acm_ajax_handler'},
		datatype: "json",
		colNames:['Conditional', 'Value'],
		colModel:[
			{name:'conditional',index:'conditional', width:180, editable: true, edittype: 'select', editoptions: {value: conditionals_options}},
			{name:'value',index:'value', width:80, align:"left", editable: true, edittype: 'text'},
		],
		onSelectRow: function(id){
		if(id && id!==subgrid_lastsel){
			subgrid_selector.jqGrid('restoreRow',subgrid_lastsel);
			subgrid_selector.jqGrid('editRow',id,true);
			subgrid_lastsel=id;
		}},
		rowNum:5,
		rowList:[5,10,20],
		pager: '#acm-codes-conditionals-pager',
		sortname: 'item',
		jsonReader : { repeatitems: false }, // workaround for jqGrid issue
		viewrecords: true,
		sortorder: "asc",
		multiselect: true,
		caption:"Conditionals for Ad Code"
	}).navGrid( '#acm-codes-conditionals-pager',{ add:true,edit:true,del:true } );


} );
