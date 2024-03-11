<OfferPackage Name="Nom fichier offres<?=$in_id?>" PackageType="Full" PurgeAndReplace="false" xmlns="clr-namespace:Cdiscount.Service.OfferIntegration.Pivot;assembly=Cdiscount.Service.OfferIntegration" xmlns:x="http://schemas.microsoft.com/winfx/2006/xaml">
	<OfferPackage.Offers>
		<OfferCollection Capacity="<?=count($lists)?>">
			<!--Offer with SmallParcel (<30kg) delivery and PreparationTime -->
            <?php foreach($lists as $info) { ?>
			<Offer <?php foreach ($info['attr'] as $attr_k=>$attr_v){?><?=$attr_k.'="'.$attr_v.'" ';?><?php } ?> EcoPart="0" DeaTax="0" Vat="20" Comment="" >
				<Offer.ShippingInformationList>
                    <ShippingInformationList Capacity="3">
                        <ShippingInformation AdditionalShippingCharges="0" DeliveryMode="Standard" ShippingCharges="0" />
                        <ShippingInformation AdditionalShippingCharges="0" DeliveryMode="Tracked" ShippingCharges="0" />
                        <ShippingInformation AdditionalShippingCharges="0" DeliveryMode="Registered" ShippingCharges="0" />
                    </ShippingInformationList>
				</Offer.ShippingInformationList>
			</Offer>
            <?php } ?>
		<!--Offer with SmallParcel (>30kg) delivery and PreparationTime -->
		<!--<Offer SellerProductId="32427220" ProductEan="0080605625006" ProductCondition="6" Price="19.95" EcoPart="0.10" DeaTax="3.14" Vat="19.6" Stock="10" StrikedPrice="39.95" Comment="Offre avec tous les modes de livraisons possibles" PreparationTime="2">
			<Offer.ShippingInformationList>
				<ShippingInformationList Capacity="3">
					<ShippingInformation AdditionalShippingCharges="1.95" DeliveryMode="BigParcelEco" ShippingCharges="2.0" />
					<ShippingInformation AdditionalShippingCharges="2.95" DeliveryMode="BigParcelStandard" ShippingCharges="3.0" />
					<ShippingInformation AdditionalShippingCharges="2.95" DeliveryMode="BigParcelComfort" ShippingCharges="3.0" />
				</ShippingInformationList>
			</Offer.ShippingInformationList>
		</Offer>-->
	</OfferCollection>
</OfferPackage.Offers>
</OfferPackage>
