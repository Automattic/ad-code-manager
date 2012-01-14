jQuery( document ).ready( function( $ ) {
	var last_selected;
	var base_url = acm_url;
	var grid_selector = jQuery("#acm-codes-list"); //avoid unnecessary selector calls
	var subgrid_selector = jQuery( '#acm-codes-conditions-list' );
	var actions = {
			  codes_datasource: base_url + '&acm-action=datasource',
			  codes_edit: base_url + '&acm-action=edit',
			  conditions_datasource: base_url + '&acm-action=datasource-conditions',
			  conditions_edit: base_url + '&acm-action=datasource-edit'
	};
	var conditions_options = "is_category:Is Category?;is_page:Is Page;has_category:Has Category?;is_tag:Is Tag?;has_tag:Has Tag?"; // this should probably be printed in head

	grid_selector.jqGrid({
		datatype: "json",
		url: actions.codes_datasource,
		editurl: actions.codes_edit,
		height: 400,
		width: 600,
		colNames:['Id','Site Name', 'Zone1', 's1', 'Actions'],
		colModel:[
			{name:'id',index:'id', width:60, sorttype:"int"},
			{name:'site_name',index:'site_name', width:200, editable: true, edittype:'text'},
			{name:'zone1',index:'zone1', width:100, editable: true},
			{name:'s1',index:'s1', width:100, editable: true},
			{name:'act',index:'act', width:125,sortable:false, align: 'center'},
		],
		prmNames:{ page: 'acm-grid-page' },
		rowNum:10,
		rowList:[10,20,30],
		pager: '#acm-codes-pager',
		sortname: 'id',
		viewrecords: true,
		sortorder: "desc",
		caption:"Ad Codes",
		jsonReader : { repeatitems: false },
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
					subgrid_selector.jqGrid( 'setGridParam',{ url:actions.conditions_datasource + "&id="+ids, page:1, editurl: actions.conditions_edit + "&id="+ids } );
					subgrid_selector.jqGrid( 'setCaption',"Conditions for Ad Code #: "+ids)
					.trigger( 'reloadGrid' );
				}
			} else {
				subgrid_selector.jqGrid( 'setGridParam',{ url:actions.conditions_datasource + "&id="+ids, page:1, editurl: actions.conditions_edit + "&id="+ids } );
				subgrid_selector.jqGrid( 'setCaption',"Conditions for Ad Code # "+ids)
				.trigger( 'reloadGrid' );			
			}
		}	
	});
	
	grid_selector.jqGrid( 'navGrid','#acm-codes-pager',{edit:true,add:true,del:true});

	subgrid_selector.jqGrid({
		height: 100,
		url: actions.conditions_datasource,
		editurl: actions.conditions_edit,
		prmNames:{ page: 'acm-grid-page' },
		datatype: "json",
		colNames:['Condition', 'Value', 'Priority'],
		colModel:[
			{name:'condition',index:'condition', width:180, editable: true, edittype: 'select', editoptions: {value: conditions_options}},
			{name:'value',index:'value', width:80, align:"left", editable: true, edittype: 'text'},
			{name:'priority',index:'priority', width:80, align:"left", editable: true, edittype: 'text'}
		],
		rowNum:5,
		rowList:[5,10,20],
		pager: '#acm-codes-conditions-pager',
		sortname: 'item',
		viewrecords: true,
		sortorder: "asc",
		multiselect: true,
		caption:"Conditions for Ad Code"
	}).navGrid( '#acm-codes-conditions-pager',{ add:true,edit:false,del:false } );


} );
