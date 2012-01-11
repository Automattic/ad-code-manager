jQuery( document ).ready( function( $ ) {
  var last_selected;
  var base_url = acm_url; 

  jQuery("#acm-codes-list").jqGrid({
	datatype: "json",
	url: base_url + '&acm-action=datasource',
	height: 400,
   	colNames:['Id','Site Name', 'Zone1', 's1','Fold','Size'],
   	colModel:[
   		{name:'id',index:'id', width:60, sorttype:"int"},
   		{name:'site_name',index:'site_name', width:200, editable: true, edittype:'text'},
   		{name:'zone1',index:'zone1', width:100, editable: true},
   		{name:'s1',index:'s1', width:100, align:"right", editable: true},
   		{name:'fold',index:'fold', width:60, align:"right", editable: true, edittype: 'select', editoptions: {value: "atf:atf;btf:btf"}},
   		{name:'sz',index:'sz', width:80,align:"right", editable: true, edittype: 'select', editoptions: {value: "300x250:300x250;728x90:728x90;160x600:160x600;1x1:1x1"}}
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
	onSelectRow: function(id){
    if( id && id !== last_selected ) {
        jQuery('#acm-codes-list').restoreRow(last_selected);
        jQuery('#acm-codes-list').editRow(id,true);
          last_selected=id;
      }
    },
    editurl: base_url + '&acm-action=update'

	// ???
});
jQuery("#acm-codes-list").jqGrid('navGrid','#acm-codes-pager',{edit:true,add:false,del:false});
} );
