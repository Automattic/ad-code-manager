jQuery( document ).ready( function( $ ) {
  jQuery("#acm-codes-list").jqGrid({
	datatype: "json",
	url:'/wp-admin/admin.php?page=acm&acm-action=datasource', // remove hardcode?
	height: 400,
   	colNames:['Id','Site Name', 'Zone1', 's1','Fold','Size'],
   	colModel:[
   		{name:'id',index:'id', width:60, sorttype:"int"},
   		{name:'site_name',index:'site_name', width:200, editable: true, edittype:'text'},
   		{name:'zone1',index:'zone1', width:100, editable: true},
   		{name:'s1',index:'s1', width:100, align:"right", editable: true},
   		{name:'fold',index:'fold', width:60, align:"right", editable: true},		
   		{name:'sz',index:'sz', width:80,align:"right"}		
   	],
	prmNames:{ page: 'acm-grid-page' },
	rowNum:10,
   	rowList:[10,20,30],
   	pager: '#acm-codes-pager',
   	sortname: 'id',
    viewrecords: true,
    sortorder: "desc",
    caption:"Ad Codes",
	jsonReader : { repeatitems: false } // ???
})
  ;
jQuery("#acm-codes-list").jqGrid('navGrid','#acm-codes-pager',{edit:false,add:false,del:false});  
} );
