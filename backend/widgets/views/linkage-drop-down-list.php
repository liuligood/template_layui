<?= \yii\helpers\Html::dropDownList($name, null, [], $param); ?>
<?php
$this->registerJs("
$(document).ready(function(){
   
    var drop_option = ".json_encode($option)."
   
    $('body').on('change','#".$parent_id."',function (){
       linkageOption('');
    });
    
    linkageOption('{$select}');
    
    function linkageOption(select_id){
        var platform = $('#{$parent_id}').val();
        //console.log(platform)
        $('#{$id}').find('option').remove();
       if (platform != ''){
            var value = drop_option[platform]
             //console.log(value)
           for (var i in value){
                var option = '<option value=' + i + '>'+ value[i] + '</option>'
               $('#{$id}').append(option);
           }
       }else{
            $.each(drop_option,function(key,value){
                for (var i in value){
                    var option = '<option value=' + i + '>'+ value[i] + '</option>'
                    $('#{$id}').append(option);
                }
           });
       }
       $('#{$id}').val(select_id);
    }
   
});
",\yii\web\View::POS_END);
?>
