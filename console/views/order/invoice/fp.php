<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title></title>
</head>
<body>

<table style="width: 800px;">
    <tr>
        <td align="left" rowspan="2" width="300">
            <p><?=$model['company_name']?></p>
            <p><?=$model['buyer_name']?></p>
            <p><?=$model['address']?></p>
            <p><?=$model['postcode']?></p>
            <p><?=$model['city']?> <?=$model['country']?></p>
        </td>
        <td width="280"></td>
        <td align="right">
            <img src="static/other/images/1_logo.png" width="200" border="0" />
        </td>
    </tr>
    <tr>
        <td></td>
        <td align="left" >
            <div style="width: 150px;display: block;">
            <p>Rechnung Nr. <?=$model['invoice_no']?></p>
            <p>Kunde Nr.  <?=$model['user_no']?></p>
            </div>
        </td>
    </tr>
    <tr>
        <td align="left" colspan="3">
            <p>Datum <?=date('Y-m-d', $model['date'])?></p>
            <p>Bestellnummer  <?=$model['relation_no']?></p>
            <p> </p>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <br/>
            <table border="1" style="line-height: 20px;text-align: center">
                <thead>
                <tr>
                    <th width="310">Artikel</th>
                    <th width="100">Menge</th>
                    <th width="120">Nettobetrag(EUR)</th>
                    <th width="120">Umsatzsteuer 19%(EUR)</th>
                    <th width="120">Endbetrag(EUR)</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $a_count = $a_price1 = $a_price2 = $a_price3 = 0;
                foreach ($goods as $v){
                    $a_count += $v['goods_num'];
                    $a_price1 += $v['price1'];
                    $a_price2 += $v['price2'];
                    $a_price3 += $v['price3'];
                    ?>
                <tr>
                    <td><?= $v['goods_name']?></td>
                    <td><?= $v['goods_num']?></td>
                    <td><?= $v['price1']?></td>
                    <td><?= $v['price2']?></td>
                    <td><?= $v['price3']?></td>
                </tr>
                <?php }?>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Summe</td>
                    <td><?= $a_count?></td>
                    <td><?= $a_price1?></td>
                    <td><?= $a_price2?></td>
                    <td><?= $a_price3?></td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <div>
                <p>USt-IdNr. DE327693393</p>
                <p>meizhoushifenglingwangluokejiyouxiangongsi Ltd.</p>
                <p>meizhoushimeijiangqujinshanjiedongxiangcun3zuyuhualou18hao erlou1-5jian 514000 meizhou</p>
                <p>China</p>
            </div>
        </td>
    </tr>
</table>

</body>
</html>