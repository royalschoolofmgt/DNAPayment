(function(root, factory) {
	if(typeof define === 'function' && define.amd) {
		define(['jquery'], function($) {
			root.X247OrderDetails = factory(root, $);
		})
	}
	else {
		root.X247OrderDetails = factory(root, root,$);
	}
}(this, function(root, $) {
	root.X247OrderDetails = {};
	var $ = jQuery;
	X247OrderDetails.previousPagelimit = 1;
	X247OrderDetails.cols_data = [
								{val:"invoice_id",name:"Payment Number"},
								{val:"order_id",name:"Bigcommerce <br>order id"},
								{val:"type",name:"Payment<br> type"},
								{val:"status",name:"Payment<br> status"},
								{val:"settlement_status",name:"Settlement<br> status"},
								{val:"currency",name:"Currency"},
								{val:"total_amount",name:"Total"},
								{val:"amount_paid",name:"Amount<br>Paid"},
								{val:"created_date",name:"Created date"},
								{val:"action",name:"Action"},
							];
	
	X247OrderDetails.change_limit = function(value){
		var limit_array = [10,25,50,100];
		var final_html = '<div class="dataTables_paginate paging_simple_numbers" id="aspect_adoptions_paginate" ><span>';
		for(var i=0;i<limit_array.length;i++){
			var selected = '';
			if(limit_array[i] == value){
				selected = 'current';
			}
			final_html += '<a class="paginate_button limit_change '+selected+'" onClick="X247OrderDetails.change_limit_data('+limit_array[i]+')" data-val="'+limit_array[i]+'" aria-controls="traffic_promotion">'+limit_array[i]+'</a>';
		}
		final_html += '</span></div>';
		return final_html;
	}

	$('body').on('click','.paginate_button',function(e){
		if($(this).hasClass('paginate_button')){
			var pagelimit = parseInt($(this).attr('data-dt-idx'));
			if(parseInt(pagelimit) == parseInt(X247OrderDetails.previousPagelimit)){
				
			}else{
				X247OrderDetails.previousPagelimit = pagelimit;
				$('.traditional').addClass('whirl');
			}
		}
	});
	
	X247OrderDetails.main_data = function(main_url,table_id){
		
		$('body #'+table_id+" tbody").html('');
		$('body #'+table_id+" #table_columns").html('');
		$('body #'+table_id+" #table_data_rows").html('');
		
		if(X247OrderDetails.cols_data != ""){
			var table_columns = '<th class="no-sort" ><input type="checkbox" id="ckbCheckAll" /></th>';
			$.each(X247OrderDetails.cols_data,function(k,v){
				table_columns += '<th class="sorting" id="customSort_'+v.val+'"><span title="sort this column">'+v.name+'</span></th>';
			});
			$('body #table_columns').append(table_columns);
		}

		$('#'+table_id).DataTable({
			//destroy,
			//"cache": false,
			"ordering": false,
			/*fixedHeader: {
				header: true
			},*/
			//scrollX: true,
			columnDefs: [
				{ targets: 'no-sort', orderable: false }
			],
			"sDom": '<"top"i>rt<"top"flp<"clear">>rt<"bottom"flp><"clear">',
			//info:false,
			"pageLength": 10,
			"language":{
				"lengthMenu": X247OrderDetails.change_limit(10)
			},
			"processing": true,
			"serverSide": true,
			"ajax": {
				"url": app_base_url+main_url,
				"type": "POST",
				"data":{cols_data:JSON.stringify(X247OrderDetails.cols_data)},
				dataFilter: function(data){
					var json = $.parseJSON( data );
					return data;
				},
			},initComplete: function() {
			},
			"drawCallback": X247OrderDetails.finalize
		});
		$('.dataTables_filter').hide();
	}

	X247OrderDetails.change_limit_data = function(limit){
		var searchVal = $("body #exampleInputEmail1").val();
		var jsonData = {cols_data:JSON.stringify(X247OrderDetails.cols_data),searchVal:searchVal};

		$('#orderdetails_dashboard').DataTable({
			destroy: true,
			"cache": false,
			"ordering": false,
			//scrollX: true,
			/*fixedHeader: {
				header: true
			},*/
			columnDefs: [
				{ targets: 'no-sort', orderable: false }
			],
			"sDom": '<"top"i>rt<"top"flp<"clear">>rt<"bottom"flp><"clear">',
			info:false,
			"pageLength": parseInt(limit),
			"language":{
				"lengthMenu": X247OrderDetails.change_limit(parseInt(limit))
			},
			"processing": true,
			"serverSide": true,
			"ajax": {
				"url": app_base_url+"scripts/orderdetails_processing.php?email_id="+email_id,
				"type": "POST",
				"data":jsonData,
				dataFilter: function(data){
					var json = $.parseJSON( data );
					return data;
				},
			},initComplete: function() {
				
			},
			"drawCallback": X247OrderDetails.finalize
		});
		$('.dataTables_filter').hide();
	}
	
	X247OrderDetails.finalize = function(){
		$('.traditional').removeClass("whirl");
	}
	$('body').on('keypress','#exampleInputEmail1',function(e){
		if(e.which == 13) {
			var limit = $(".limit_change.current:first").text();
			X247OrderDetails.change_limit_data(limit);
		}
	});
	$('body').on('click','#ckbCheckAll',function(){
		$('input:checkbox').not(this).prop('checked', this.checked);
	});
	return root.X247OrderDetails;

}));