<OfferPackage Name="Nom fichier offres<?=$in_id?>" PurgeAndReplace="false" PackageType="StockAndPrice" xmlns="clr-namespace:Cdiscount.Service.OfferIntegration.Pivot;assembly=Cdiscount.Service.OfferIntegration" xmlns:x="http://schemas.microsoft.com/winfx/2006/xaml">
	<OfferPackage.Offers>
		<OfferCollection Capacity="<?=count($lists)?>">
            <?php foreach($lists as $info) { ?>
			<Offer <?php foreach ($info['attr'] as $attr_k=>$attr_v){?><?=$attr_k.'="'.$attr_v.'" ';?><?php } ?> />
            <?php } ?>
		</OfferCollection>
	</OfferPackage.Offers>

</OfferPackage>