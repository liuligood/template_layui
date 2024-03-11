<!DOCTYPE html>
<html lang="en">
<meta>
    <meta charset="UTF-8" />
    <title></title>
    <style>
        .word {
            word-break: break-all;
            margin-top: 25px
        }
    </style>
</head>
<body>

<table style="width: 650px;">
    <tr>
        <td align="left" width="300">
            <b style="font-size: 18px">Invoice 07043366/<?=$model['country_name']?>/<?=$model['date_time']?></b>
        </td>
        <td></td>
        <td align="right" width="300">
            <div style="margin-top: 8px"><b style="font-size: 13px">
            <?=$model['order_date_name']?>: <?=$model['order_date']?><br/>
            <?=$model['delivery_time_name']?>: <?=$model['delivery_time']?>
            </b></div>
        </td>
    </tr>
    <tr>
        <td align="left" width="280">
            <div style="width: 155px;" class="word">
            <b style="font-size: 11px"><?=$model['shop_name']?>:</b><br/>
            zhongshansanlindouwangluokejiyouxiangongsi zhongshanshixiqucaihongdadao88haoerqi4cengB427 528401 Zhongshan<br/>
            Tax number: <br/>
            91442000<?=$model['tax_number']?><br/>
            PL5263636057
            </div>
        </td>
        <td align="right"></td>
        <td align="right" width="340">
            <div class="word" style="width: 340px;text-align: left"><span style="font-size: 11px"><?=$model['correspondence']?>:</span><br/>
                <b>Zhongshan Shangjia Network Technology Co., Ltd.<br/>
                    No. 88 Rainbow Avenue, West District, Zhongshan City (Self-appl<br/>
                    528401 Zhongshan
                    China</b>
            </div>
        </td>
    </tr>
    <tr>
        <td align="left">
            <div class="word" style="margin-top: 10px;width: 230px">
                <b><?=$model['user']?>: </b><br/>
                <?=$model['company_name']?> <?=$model['buyer_name']?><br/>
                <?=$model['address']?><br/>
                <?=$model['postcode']?> <?=$model['city']?> <?=$model['country']?><br/>
                <?php if ($model['country'] == 'PL') {?>
                NIP: <?=$model['buyer_phone']?>
                <?php }?>
            </div>
        </td>
    </tr>

    <?php foreach ($goods as $v):?>
    <tr>
        <td align="center" colspan="4">
        <table border="1" style="line-height: 20px;text-align: center">
            <thead>
            <tr>
                <th width="160" height="70"><?=$model['goods_title_name']?></th>
                <th width="160"><?=$model['date_name']?></th>
                <th width="160"><?=$model['goods_title_desc']?></th>
                <th width="220"><?=$model['title_size']?></th>
                <th width="100"><?=$model['goods_title_num']?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><?=$v['goods_name']?></td>
                <td><?=$v['date']?></td>
                <td><?=$v['goods_desc']?></td>
                <td><?=$v['size']?> cm </td>
                <td><?=$v['goods_num']?></td>
            </tr>
            </tbody>
        </table>
        </td>
    </tr>
    <tr>
        <td colspan="3" width="700">
            <div style="margin-top: 45px;"></div>
            <table border="1" style="line-height: 20px;text-align: center;">
                <thead>
                <tr>
                    <td width="113"><?=$model['price_title1']?></td>
                    <td width="113"><?=$model['price_title2']?></td>
                    <td width="113"><?=$model['price_title3']?></td>
                    <th width="113"><b><?=$model['price_title4']?></b></th>
                    <td width="113"><?=$model['price_title5']?></td>
                    <td width="113"><?=$model['price_title6']?></td>
                    <td width="113"><?=$model['price_title7']?></td>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td height="90"><?=$v['price1']?> <?=$model['currency']?></td>
                    <td><?=$v['price2']?> <?=$model['currency']?></td>
                    <td><?=$model['tax_rate']?></td>
                    <td><?=$v['price3']?> <?=$model['currency']?></td>
                    <td><?=$v['price4']?> <?=$model['currency']?></td>
                    <td></td>
                    <td>0 <?=$model['currency']?></td>
                </tr>
                </tbody>
            </table>
            <div style="margin-bottom: 25px"></div>
        </td>
    </tr>
    <?php endforeach;?>
</table>

</body>
</html>