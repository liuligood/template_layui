<ProductPackage Name="Integartion-produit-cdis<?=$in_id?>" xmlns="clr-namespace:Cdiscount.Service.ProductIntegration.Pivot;assembly=Cdiscount.Service.ProductIntegration" xmlns:x="http://schemas.microsoft.com/winfx/2006/xaml">
	<ProductPackage.Products>
		<ProductCollection Capacity="<?=count($lists)?>">
			<!-- Product variants -->
            <?php foreach($lists as $info) { ?>
			<Product <?php foreach ($info['attr'] as $attr_k=>$attr_v){?><?=$attr_k.'="'.$attr_v.'" ';?><?php } ?>>
				<Product.EanList>
					<ProductEan Ean="<?=$info['ean']?>"/>
				</Product.EanList>
				<Product.ModelProperties>
					<!-- You can see the mandatory properties with the GetModelList method -->
                    <?php foreach ($info['model'] as $model_k=>$model_v){?><x:String x:Key="<?=$model_k?>"><?=$model_v?></x:String><?php } ?>
				</Product.ModelProperties>
				<Product.Pictures>
                    <?php foreach ($info['image'] as $img_v){?><ProductImage Uri="<?=$img_v?>"/><?php } ?>
				</Product.Pictures>
			</Product>
            <?php } ?>
		</ProductCollection>
	</ProductPackage.Products>
</ProductPackage>