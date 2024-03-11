var form;
layui.use(['form','layer'],function(){
    form = layui.form;

    $ = layui.jquery;
    var layer = parent.layer === undefined ? layui.layer : top.layer;

    $('#add').on('change',"#count",function(data){
		change_price();
    }).on('change',"#amazon_price",function(data){
    	change_price();	
    }).on('change',"#real_price",function(data){
    	change_price();	
    });

    function change_price(){
    	var count = $('#count').val();
    	count = Number(count);
    	var amazon_price = $('#amazon_price').val();
    	amazon_price = Number(amazon_price).toFixed(2);
    	var real_price = $('#real_price').val();
    	real_price = Number(real_price).toFixed(2);
    	var real_order_amount = count * real_price;
        real_order_amount = Number(real_order_amount).toFixed(2);
        var profit = real_order_amount*0.875 - (amazon_price * count);
        profit = Number(profit).toFixed(2);
        $('#real_order_amount').val(real_order_amount);
    	$('#profit').val(profit);
    }
});