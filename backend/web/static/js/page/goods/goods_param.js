var product_params = {
    color_data: [
        {name:"黑色",rgb:"#000000",color:"Black"},
        {name:"白色",rgb:"#ffffff",color:"White",class:"f-black"},
        {name:"灰色",rgb:"#797979",color:"Grey"},
        {name:"透明",rgb:"url(/static/img/transparent.png) no-repeat",color:"Transparent",class:"f-black"},//
        {name:"红色",rgb:"#FF2600",color:"Red"},
        {name:"粉红",rgb:"#FF2F92",color:"Pink"},
        {name:"酒红色",rgb:"#7A0606",color:"Wine red"},
        {name:"蓝色",rgb:"#0433FF",color:"Blue"},
        {name:"绿色",rgb:"#009051",color:"Green"},
        {name:"紫色",rgb:"#531B93",color:"Purple"},
        {name:"黄色",rgb:"#FFFB00",color:"Yellow",class:"f-black"},
        {name:"米黄",rgb:"#FFFFCC",color:"Beige",class:"f-black"},
        {name:"棕色",rgb:"#941100",color:"Brown"},
        {name:"卡其",rgb:"#C5AF90",color:"Khaki"},
        {name:"橘色",rgb:"#FF9300",color:"Orange"},
        {name:"玫瑰金",rgb:"#F2C3AD",color:"Rose gold"},
        {name:"黄褐",rgb:"#929000",color:"Tan"},
        {name:"乳白",rgb:"#EBEBEB",color:"Ivory",class:"f-black"},
        {name:"藏青",rgb:"#000080",color:"Navy blue"},
        {name:"金色",rgb:"#FDEC90",color:"Gold",class:"f-black"},
        {name:"银色",rgb:"#9195A1",color:"Silver"},
        {name:"铜色",rgb:"#956C43",color:"Copper"},
        {name:"原木色",rgb:"url(/static/img/wood.png) no-repeat",color:"Wood",class:"f-black"},//
        {name:"多彩",rgb:"url(/static/img/multicolor.png) no-repeat",color:"Colorful"},
    ],
    size_data : {
        Man: {
            id: 'Man',
            type :'Man',
            name: '男&#12288;装',
            data: [
                {
                    size: 'XS',
                    chestA: '76 - 81(cm)',
                    chestB: '30" - 32"',
                    waistA: '61 - 66(cm)',
                    waistB: '24" - 26"',
                    neckA: '33 - 34(cm)',
                    neckB: '13" - 13.5"',
                    sleeveA: '79 - 81(cm)',
                    sleeveB: '31" - 32"'
                },
                {
                    size: 'S',
                    chestA: '86 - 91(cm)',
                    chestB: '34" - 36"',
                    waistA: '71 - 76(cm)',
                    waistB: '28" - 30"',
                    neckA: '36 - 37(cm)',
                    neckB: '14" - 14.5"',
                    sleeveA: '81 - 84(cm)',
                    sleeveB: '32" - 33"'
                },
                {
                    size: 'M',
                    chestA: '97 - 102(cm)',
                    chestB: '38" - 40"',
                    waistA: '81 - 86(cm)',
                    waistB: '32" - 34"',
                    neckA: '38 - 39(cm)',
                    neckB: '15" - 15.5"',
                    sleeveA: '84 - 86(cm)',
                    sleeveB: '33" - 34"'
                },
                {
                    size: 'L',
                    chestA: '107 - 112(cm)',
                    chestB: '42" - 44"',
                    waistA: '91 - 97(cm)',
                    waistB: '36" - 38"',
                    neckA: '41 - 42(cm)',
                    neckB: '16" - 16.5"',
                    sleeveA: '86 - 89(cm)',
                    sleeveB: '34" - 35"'
                },
                {
                    size: 'XL',
                    chestA: '117 - 122(cm)',
                    chestB: '46" - 48"',
                    waistA: '102 - 107(cm)',
                    waistB: '40" - 42"',
                    neckA: '43 - 44(cm)',
                    neckB: '17" - 17.5"',
                    sleeveA: '89 - 91(cm)',
                    sleeveB: '35" - 36"'
                },
                {
                    size: 'XXL',
                    chestA: '127 - 132(cm)',
                    chestB: '50" - 52"',
                    waistA: '112 - 117(cm)',
                    waistB: '44" - 46"',
                    neckA: '46 - 47(cm)',
                    neckB: '18" - 18.5"',
                    sleeveA: '91 - 94(cm)',
                    sleeveB: '36" - 37"'
                },
                {
                    size: 'XXXL',
                    chestA: '137 - 142(cm)',
                    chestB: '54" - 56"',
                    waistA: '122 - 127(cm)',
                    waistB: '48" - 50"',
                    neckA: '48 - 50(cm)',
                    neckB: '19" - 19.5"',
                    sleeveA: '94 - 97(cm)',
                    sleeveB: '37" - 38"'
                },
                {
                    size: 'XXXXL',
                    chestA: '147 - 152(cm)',
                    chestB: '58" - 60"',
                    waistA: '132 - 137(cm)',
                    waistB: '52" - 54"',
                    neckA: '51 - 52(cm)',
                    neckB: '20" - 20.5"',
                    sleeveA: '97 - 99(cm)',
                    sleeveB: '38" - 39"'
                },
                {
                    size: 'XXXXXL',
                    chestA: '157 - 163(cm)',
                    chestB: '62" - 64"',
                    waistA: '142 - 147(cm)',
                    waistB: '56" - 58"',
                    neckA: '53 - 55(cm)',
                    neckB: '21" - 21.5"',
                    sleeveA: '99 - 102(cm)',
                    sleeveB: '39" - 40"'
                }
            ]
        },
        Women: {
            id: 'Women',
            type: 'Women',
            name: '女&#12288;装',
            data: [
                {
                    size: 'XXS',
                    bustA: '80 - 83(cm)',
                    bustB: '31.5" - 32.5"',
                    waistA: '60 - 62(cm)',
                    waistB: '23.5" - 24.5"',
                    hipA: '86 - 89(cm)',
                    hipB: '34" - 35"'
                }, {
                    size: 'XS',
                    bustA: '83 - 85(cm)',
                    bustB: '32.5" - 33.5"',
                    waistA: '62 - 65(cm)',
                    waistB: '24.5" - 25.5"',
                    hipA: '89 - 91(cm)',
                    hipB: '35" - 36"'
                }, {
                    size: 'S',
                    bustA: '85 - 88(cm)',
                    bustB: '33.5" - 34.5"',
                    waistA: '65 - 67(cm)',
                    waistB: '25.5" - 26.5"',
                    hipA: '91 - 94(cm)',
                    hipB: '36" - 37"'
                }, {
                    size: 'M',
                    bustA: '90 - 93(cm)',
                    bustB: '35.5" - 36.5"',
                    waistA: '70 - 72(cm)',
                    waistB: '27.5" - 28.5"',
                    hipA: '97 - 99(cm)',
                    hipB: '38" - 39"'
                }, {
                    size: 'L',
                    bustA: '97 - 100(cm)',
                    bustB: '38" - 39.5"',
                    waistA: '76 - 80(cm)',
                    waistB: '30" - 31.5"',
                    hipA: '103 - 107(cm)',
                    hipB: '40.5" - 42"'
                }, {
                    size: 'XL',
                    bustA: '104 - 109(cm)',
                    bustB: '41" - 43"',
                    waistA: '84 - 88(cm)',
                    waistB: '33" - 34.5"',
                    hipA: '110 - 116(cm)',
                    hipB: '43.5" - 45.5"'
                }, {
                    size: 'XXL',
                    bustA: '110 - 116(cm)',
                    bustB: '43.5" - 45.5"',
                    waistA: '91 - 95(cm)',
                    waistB: '36" - 37.5"',
                    hipA: '116 - 123(cm)',
                    hipB: '45.5" - 48.5"'
                }]
        },
        Child: {
            id: 'Child',
            type : 'Checkbox',
            name: '儿&#12288;童',
            data: [{size: 'Newborn'}, {size: '0-3m'}, {size: '4-5 Years'}, {size: '2t'}, {size: '0-6m'}, {size: '5-6 Years'}, {size: '3t'}, {size: '3-6m'}, {size: '6-7 Years'}, {size: '4t'}, {size: '6-12m'}, {size: '7-8 Years'}, {size: '5t'}, {size: '6-9m'}, {size: '8-9 Years'}, {size: '6t'}, {size: '9-12m'}, {size: '9-10 Years'}, {size: '7t'}, {size: '12-18m'}, {size: '10-11 Years'}, {size: '8t'}, {size: '18-24m'}, {size: '11-12 Years'}, {size: '6 Months'}, {size: '1-2 Years'}, {size: '12-13 Years'}, {size: '12 Months'}, {size: '2-3 Years'}, {size: '13-14 Years'}, {size: '18 Months'}, {size: '3-4 Years'}, {size: '14-15 Years'}]
        },
        ChildShoes: {
            id: 'ChildShoes',
            type : 'Checkbox',
            name: '婴/童鞋',
            data: [{size: '7 - 8'}, {size: '11 - 12'}, {size: '2 - 3'}, {size: '9 - 10'}, {size: '13 - 1'}]
        },
        Numbers: {
            id: 'Numbers',
            type : 'Checkbox',
            other : true,
            name: '数&#12288;字',
            data: [{size: '0'}, {size: '7'}, {size: '14'}, {size: '0.5'}, {size: '7.5'}, {size: '14.5'}, {size: '1'}, {size: '8'}, {size: '15'}, {size: '1.5'}, {size: '8.5'}, {size: '15.5'}, {size: '2'}, {size: '9'}, {size: '16'}, {size: '2.5'}, {size: '9.5'}, {size: '16.5'}, {size: '3'}, {size: '10'}, {size: '17'}, {size: '3.5'}, {size: '10.5'}, {size: '17.5'}, {size: '4'}, {size: '11'}, {size: '18'}, {size: '4.5'}, {size: '11.5'}, {size: '18.5'}, {size: '5'}, {size: '12'}, {size: '19'}, {size: '5.5'}, {size: '12.5'}, {size: '19.5'}, {size: '6'}, {size: '13'}, {size: '20'}, {size: '6.5'}, {size: '13.5'}, {size: '20.5'}]
        },
        Bras: {
            id: 'Bras',
            type : 'Checkbox',
            name: '胸&#12288;罩',
            data: [{size: "30B"}, {size: "34DDD"}, {size: "38DDD"}, {size: "30C"}, {size: "34E"}, {size: "38E"}, {size: "30D"}, {size: "36A"}, {size: "38F"}, {size: "32A"}, {size: "36AA"}, {size: "40A"}, {size: "32AA"}, {size: "36B"}, {size: "40B"}, {size: "32B"}, {size: "36C"}, {size: "40C"}, {size: "32C"}, {size: "36D"}, {size: "40D"}, {size: "32D"}, {size: "36DD"}, {size: "40DD"}, {size: "32DD"}, {size: "36DDD"}, {size: "40DDD"}, {size: "32DDD"}, {size: "36E"}, {size: "40E"}, {size: "32E"}, {size: "36F"}, {size: "40F"}, {size: "32/34D"}, {size: "36/38C"}, {size: "42A"}, {size: "32/34DD"}, {size: "36/38D"}, {size: "42B"}, {size: "32/34E"}, {size: "36/38DD"}, {size: "42C"}, {size: "32/34F"}, {size: "36/38E"}, {size: "42D"}, {size: "34A"}, {size: "36/38F"}, {size: "42DD"}, {size: "34AA"}, {size: "38A"}, {size: "42DDD"}, {size: "34B"}, {size: "38B"}, {size: "42E"}, {size: "34C"}, {size: "38C"}, {size: "44DD"}, {size: "34D"}, {size: "38D"}, {size: "44DDD"}, {size: "34DD"}, {size: "38DD"}]
        },
        AdditionalApparelSizes: {
            id: 'AdditionalApparelSizes',
            type : 'Checkbox',
            name: '额外服装尺寸',
            data: [{size: 'One Size'}, {size: '7XL'}, {size: 'Small (9/10)'}, {size: 'XXXS'}, {size: '8XL'}, {size: 'Youth XL(16-18)'}, {size: 'XS/S'}, {size: '1X'}, {size: 'Youth L(14-16)'}, {size: 'S/M'}, {size: '1X/2X'}, {size: 'Youth M(10-12)'}, {size: 'M/L'}, {size: '2X'}, {size: 'Youth S(6-8)'}, {size: 'L/XL'}, {size: '3X'}, {size: 'Adult'}, {size: '6XL'}, {size: '3X/4X'}, {size: 'Kid'}]
        },
        ManShoes:  {
            id: 'ManShoes',
            type : 'ManShoes',
            name: '男&#12288;鞋',
            data:  [
                {size: '6', sizeA: '39', sizeB: '9.25"', sizeC: '23.5(cm)'},
                {size: '6.5', sizeA: '39', sizeB: '9.5"', sizeC: '24.1(cm)'},
                {size: '7', sizeA: '40', sizeB: '9.625"', sizeC: '24.4(cm)'},
                {size: '7.5', sizeA: '40 - 41', sizeB: '9.75"', sizeC: '24.8(cm)'},
                {size: '8', sizeA: '41', sizeB: '9.9375"', sizeC: '25.4(cm)'},
                {size: '8.5', sizeA: '41 - 42', sizeB: '10.125"', sizeC: '25.7(cm)'},
                {size: '9', sizeA: '42', sizeB: '10.25"', sizeC: '26(cm)'},
                {size: '9.5', sizeA: '42 - 43', sizeB: '10.4375"', sizeC: '26.7(cm)'},
                {size: '10', sizeA: '43', sizeB: '10.5625"', sizeC: '27(cm)'},
                {size: '10.5', sizeA: '43 - 44', sizeB: '10.75"', sizeC: '27.3(cm)'},
                {size: '11', sizeA: '44', sizeB: '10.9375"', sizeC: '27.9(cm)'},
                {size: '11.5', sizeA: '44 - 45', sizeB: '11.125"', sizeC: '28.3(cm)'},
                {size: '12', sizeA: '45', sizeB: '11.25"', sizeC: '28.6(cm)'},
                {size: '13', sizeA: '46', sizeB: '11.5625"', sizeC: '29.4(cm)'},
                {size: '14', sizeA: '47', sizeB: '11.875"', sizeC: '30.2(cm)'},
                {size: '15', sizeA: '48', sizeB: '12.1875"', sizeC: '31(cm)'},
                {size: '16', sizeA: '49', sizeB: '12.5"', sizeC: '31.8(cm)'}]
        },
        WomenShoes: {
            id: 'WomenShoes',
            type : 'ManShoes',
            name: '女&#12288;鞋',
            data: [
                {size: '4', sizeA: '35', sizeB: '8.1875"', sizeC: '20.8(cm)'},
                {size: '4.5', sizeA: '35', sizeB: '8.375"', sizeC: '21.3(cm)'},
                {size: '5', sizeA: '35 - 36', sizeB: '8.5"', sizeC: '21.6(cm)'},
                {size: '5.5', sizeA: '36', sizeB: '8.75"', sizeC: '22.2(cm)'},
                {size: '6', sizeA: '36 - 37', sizeB: '8.875"', sizeC: '22.5(cm)'},
                {size: '6.5', sizeA: '37', sizeB: '9.0625"', sizeC: '23(cm)'},
                {size: '7', sizeA: '37 - 38', sizeB: '9.25"', sizeC: '23.5(cm)'},
                {size: '7.5', sizeA: '38', sizeB: '9.375"', sizeC: '23.8(cm)'},
                {size: '8', sizeA: '38 - 39', sizeB: '9.5"', sizeC: '24.1(cm)'},
                {size: '8.5', sizeA: '39', sizeB: '9.6875"', sizeC: '24.6(cm)'},
                {size: '9', sizeA: '39 - 40', sizeB: '9,875"', sizeC: '25.1(cm)'},
                {size: '9.5', sizeA: '40', sizeB: '10"', sizeC: '25.4(cm)'},
                {size: '10', sizeA: '40 - 41', sizeB: '10.1875"', sizeC: '25.9(cm)'},
                {size: '10.5', sizeA: '41', sizeB: '10.3125"', sizeC: '26.2(cm)'},
                {size: '11', sizeA: '41 - 42', sizeB: '10.5"', sizeC: '26.7(cm)'},
                {size: '11.5', sizeA: '42', sizeB: '10.6875"', sizeC: '27.1(cm)'},
                {size: '12', sizeA: '42 - 43', sizeB: '10.875"', sizeC: '27.6(cm)'}]
        },
        Shoes: {
            id: 'Shoes',
            type : 'Checkbox',
            other : true,
            name: '鞋&#12288;子',
            data: [{size: '0'}, {size: '7'}, {size: '14'}, {size: '0.5'}, {size: '7.5'}, {size: '14.5'}, {size: '1'}, {size: '8'}, {size: '15'}, {size: '1.5'}, {size: '8.5'}, {size: '15.5'}, {size: '2'}, {size: '9'}, {size: '16'}, {size: '2.5'}, {size: '9.5'}, {size: '16.5'}, {size: '3'}, {size: '10'}, {size: '17'}, {size: '3.5'}, {size: '10.5'}, {size: '17.5'}, {size: '4'}, {size: '11'}, {size: '18'}, {size: '4.5'}, {size: '11.5'}, {size: '18.5'}, {size: '5'}, {size: '12'}, {size: '19'}, {size: '5.5'}, {size: '12.5'}, {size: '19.5'}, {size: '6'}, {size: '13'}, {size: '20'}, {size: '6.5'}, {size: '13.5'}, {size: '20.5'}]
        },
        Macbooks: {
            id: 'Macbooks',
            type : 'Checkbox',
            name: '苹果电脑',
            data: [{size: '11&quot; MacBook'}, {size: '15&quot; MacBook Air'}, {size: '17&quot; MacBook Pro'}, {size: '11&quot; MacBook Air'}, {size: '13&quot; MacBook Pro'}, {size: 'MacBook Pro (15-inch, 2016)'}, {size: '12&quot; MacBook'}, {size: '13&quot; MacBook Pro with Retina display'}, {size: 'MacBook Pro (13-inch, 2016, Four Thunderbolt 3 Ports)'}, {size: '13&quot; White MacBook'}, {size: '15&quot; MacBook Pro'}, {size: 'MacBook Pro (13-inch, 2016, Two Thunderbolt 3 Ports)'}, {size: '13&quot; MacBook Air'}, {size: '15&quot; MacBook Pro with Retina display'}]
        },
        Smartphones: {
            id: 'Smartphones',
            type : 'Other',
            other : true,
            name: '智能手机',
            example: '例子：iPhone 8,OnePlus 7,XiaoMi Mi 9',
            data: []
        },
        Gaming: {
            id: 'Gaming',
            type : 'Checkbox',
            name: '游戏机',
            data: [{size: 'Playstation 3 Slim'}, {size: 'Playstation 3 Slim 1'}, {size: 'Playstation 3 Original 1'}, {size: 'Playstation 3 Original'}, {size: 'Playstation 3 Slim 2'}, {size: 'Playstation 3 Original 2'}]
        },
        Headphones: {
            id: 'Headphones',
            type : 'Checkbox',
            name: '耳&#12288;机',
            data: [{size: 'Beats Pro'}, {size: 'Beats Solo 2 Wireless'}, {size: 'Beats Studio 2013+ Models'}, {size: 'Beats Solo'}, {size: 'Beats Solo-HD'}, {size: 'Beats MIXR'}, {size: 'Beats Solo 2'}, {size: 'Beats Studio'}, {size: 'Beats Wireless'}]
        },
        Bedding: {
            id: 'Bedding',
            type : 'Checkbox',
            name: '床上用品',
            data: [{size: 'Single'}, {size: 'Full'}, {size: 'Super King'}, {size: 'Twin'}, {size: 'Queen'}, {size: 'California King'}, {size: 'Double'}, {size: 'King'}]
        },
        Memory: {
            id: 'Memory',
            type : 'Checkbox',
            name: '存储设备',
            data: [{size: '1GB'}, {size: '16GB'}, {size: '256GB'}, {size: '2GB'}, {size: '32GB'}, {size: '512GB'}, {size: '4GB'}, {size: '64GB'}, {size: '1TB'}, {size: '8GB'}, {size: '128GB'}]
        },
        Area: {
            id: 'Area',
            type : 'Other',
            other : true,
            name: '面积或体积',
            example: '例子：1.4m x 1.4m,10.5"*10.5",10cm by 10cm by 10cm',
            data: []
        },
        Length: {
            id: 'Length',
            type : 'Other',
            other : true,
            name: '长&#12288;度',
            example: '例子：6\'5",10m,5",11.5cm,6ft 5in',
            data: []
        },
        Volume: {
            id: 'Volume',
            type : 'Other',
            other : true,
            name: '容&#12288;量',
            example: '例子：100mL,200L,40 fl.oz.,10.5pt',
            data: []
        },
        Voltage: {
            id: 'Voltage',
            type : 'Other',
            other : true,
            name: '电&#12288;压',
            example: '例子：100V,50v',
            data: []
        },
        Wattage: {
            id: 'Wattage',
            type : 'Other',
            other : true,
            name: '瓦&#12288;数',
            example: '例子：10W,30W,100W',
            data: []
        },
        Weight: {
            id: 'Weight',
            type : 'Other',
            other : true,
            name: '重&#12288;量',
            example: '例子：100kg,11.5lb,3.14g',
            data: []
        },
        Shapes: {
            id: 'Shapes',
            type : 'Checkbox',
            name: '形&#12288;状',
            data: [{size: 'Pearl'}, {size: 'Heart'}, {size: 'Basic'}, {size: 'Cubic'}, {size: 'Rose'}, {size: 'Twist'}]
        },
        ElectricPlugs:{
            id: 'ElectricPlugs',
            type : 'ElectricPlugs',
            name: '电插头',
            data: [
                {size: 'US Plug', img: 'usplug'},
                {size: 'UK Plug', img: 'ukplug'},
                {size: 'AU Plug', img: 'auplug'},
                {size: 'EU Plug', img: 'euplug'}]
        },
        MenSuitTuxedos: {
            id: 'MenSuitTuxedos',
            type : 'MenSuitTuxedos',
            name: '男士西装礼服',
            data: [
                {
                    size: '36 Short',
                    sizeA: '89 - 94 (cm)',
                    sizeB: '35.0&#34; - 37.0&#34;',
                    sizeC: '163 - 171 (cm)',
                    sizeD: '5&#39;4&#34; - 5&#39;7&#34;'
                },
                {
                    size: '38 Short',
                    sizeA: '94 - 99 (cm)',
                    sizeB: '37.0&#34; - 39.0&#34;',
                    sizeC: '163 - 171 (cm)',
                    sizeD: '5&#39;4&#34; - 5&#39;7&#34;'
                },
                {
                    size: '40 Short',
                    sizeA: '99 - 104 (cm)',
                    sizeB: '39.0&#34; - 41.0&#34;',
                    sizeC: '163 - 171 (cm)',
                    sizeD: '5&#39;4&#34; - 5&#39;7&#34;'
                },
                {
                    size: '42 Short',
                    sizeA: '104 - 109 (cm)',
                    sizeB: '41.0&#34; - 43.0&#34;',
                    sizeC: '163 - 171 (cm)',
                    sizeD: '5&#39;4&#34; - 5&#39;7&#34;'
                },
                {
                    size: '44 Short',
                    sizeA: '109 - 114 (cm)',
                    sizeB: '43.0&#34; - 45.0&#34;',
                    sizeC: '163 - 171 (cm)',
                    sizeD: '5&#39;4&#34; - 5&#39;7&#34;'
                },
                {
                    size: '46 Short',
                    sizeA: '114 - 119 (cm)',
                    sizeB: '45.0&#34; - 47.0&#34;',
                    sizeC: '163 - 171 (cm)',
                    sizeD: '5&#39;4&#34; - 5&#39;7&#34;'
                },
                {
                    size: '48 Short',
                    sizeA: '119 - 124 (cm)',
                    sizeB: '47.0&#34; - 49.0&#34;',
                    sizeC: '163 - 171 (cm)',
                    sizeD: '5&#39;4&#34; - 5&#39;7&#34;'
                },
                {
                    size: '50 Short',
                    sizeA: '124 - 130 (cm)',
                    sizeB: '49.0&#34; - 51.0&#34;',
                    sizeC: '163 - 171 (cm)',
                    sizeD: '5&#39;4&#34; - 5&#39;7&#34;'
                },
                {
                    size: '34 Regular',
                    sizeA: '84 - 89 (cm)',
                    sizeB: '33.0&#34; - 35.0&#34;',
                    sizeC: '173 - 189 (cm)',
                    sizeD: '5&#39;8&#34; - 6&#39;2&#34;'
                },
                {
                    size: '36 Regular',
                    sizeA: '89 - 94 (cm)',
                    sizeB: '35.0&#34; - 37.0&#34;',
                    sizeC: '173 - 189 (cm)',
                    sizeD: '5&#39;8&#34; - 6&#39;2&#34;'
                },
                {
                    size: '38 Regular',
                    sizeA: '94 - 99 (cm)',
                    sizeB: '37.0&#34; - 39.0&#34;',
                    sizeC: '173 - 189 (cm)',
                    sizeD: '5&#39;8&#34; - 6&#39;2&#34;'
                },
                {
                    size: '40 Regular',
                    sizeA: '99 - 104 (cm)',
                    sizeB: '39.0&#34; - 41.0&#34;',
                    sizeC: '173 - 189 (cm)',
                    sizeD: '5&#39;8&#34; - 6&#39;2&#34;'
                },
                {
                    size: '42 Regular',
                    sizeA: '104 - 109 (cm)',
                    sizeB: '41.0&#34; - 43.0&#34;',
                    sizeC: '173 - 189 (cm)',
                    sizeD: '5&#39;8&#34; - 6&#39;2&#34;'
                },
                {
                    size: '44 Regular',
                    sizeA: '109 - 114 (cm)',
                    sizeB: '43.0&#34; - 45.0&#34;',
                    sizeC: '173 - 189 (cm)',
                    sizeD: '5&#39;8&#34; - 6&#39;2&#34;'
                },
                {
                    size: '46 Regular',
                    sizeA: '114 - 119 (cm)',
                    sizeB: '45.0&#34; - 47.0&#34;',
                    sizeC: '173 - 189 (cm)',
                    sizeD: '5&#39;8&#34; - 6&#39;2&#34;'
                },
                {
                    size: '48 Regular',
                    sizeA: '119 - 124 (cm)',
                    sizeB: '47.0&#34; - 49.0&#34;',
                    sizeC: '173 - 189 (cm)',
                    sizeD: '5&#39;8&#34; - 6&#39;2&#34;'
                },
                {
                    size: '50 Regular',
                    sizeA: '124 - 130 (cm)',
                    sizeB: '49.0&#34; - 51.0&#34;',
                    sizeC: '173 - 189 (cm)',
                    sizeD: '5&#39;8&#34; - 6&#39;2&#34;'
                },
                {
                    size: '52 Regular',
                    sizeA: '130 - 135 (cm)',
                    sizeB: '51.0&#34; - 53.0&#34;',
                    sizeC: '173 - 189 (cm)',
                    sizeD: '5&#39;8&#34; - 6&#39;2&#34;'
                },
                {
                    size: '54 Regular',
                    sizeA: '135 - 140 (cm)',
                    sizeB: '53.0&#34; - 55.0&#34;',
                    sizeC: '173 - 189 (cm)',
                    sizeD: '5&#39;8&#34; - 6&#39;2&#34;'
                },
                {
                    size: '56 Regular',
                    sizeA: '140 - 145 (cm)',
                    sizeB: '55.0&#34; - 57.0&#34;',
                    sizeC: '173 - 189 (cm)',
                    sizeD: '5&#39;8&#34; - 6&#39;2&#34;'
                },
                {
                    size: '58 Regular',
                    sizeA: '145 - 150 (cm)',
                    sizeB: '57.0&#34; - 59.0&#34;',
                    sizeC: '173 - 189 (cm)',
                    sizeD: '5&#39;8&#34; - 6&#39;2&#34;'
                },
                {
                    size: '60 Regular',
                    sizeA: '150 - 155 (cm)',
                    sizeB: '59.0&#34; - 61.0&#34;',
                    sizeC: '173 - 189 (cm)',
                    sizeD: '5&#39;8&#34; - 6&#39;2&#34;'
                },
                {
                    size: '62 Regular',
                    sizeA: '155 - 160 (cm)',
                    sizeB: '61.0&#34; - 63.0&#34;',
                    sizeC: '173 - 189 (cm)',
                    sizeD: '5&#39;8&#34; - 6&#39;2&#34;'
                },
                {
                    size: '38 Long',
                    sizeA: '94 - 99 (cm)',
                    sizeB: '37.0&#34; - 39.0&#34;',
                    sizeC: '191 - 197 (cm)',
                    sizeD: '6&#39;3&#34; - 6&#39;5&#34;'
                },
                {
                    size: '40 Long',
                    sizeA: '99 - 104 (cm)',
                    sizeB: '39.0&#34; - 41.0&#34;',
                    sizeC: '191 - 197 (cm)',
                    sizeD: '6&#39;3&#34; - 6&#39;5&#34;'
                },
                {
                    size: '42 Long',
                    sizeA: '104 - 109 (cm)',
                    sizeB: '41.0&#34; - 43.0&#34;',
                    sizeC: '191 - 197 (cm)',
                    sizeD: '6&#39;3&#34; - 6&#39;5&#34;'
                },
                {
                    size: '44 Long',
                    sizeA: '109 - 114 (cm)',
                    sizeB: '43.0&#34; - 45.0&#34;',
                    sizeC: '191 - 197 (cm)',
                    sizeD: '6&#39;3&#34; - 6&#39;5&#34;'
                },
                {
                    size: '46 Long',
                    sizeA: '114 - 119 (cm)',
                    sizeB: '45.0&#34; - 47.0&#34;',
                    sizeC: '191 - 197 (cm)',
                    sizeD: '6&#39;3&#34; - 6&#39;5&#34;'
                },
                {
                    size: '48 Long',
                    sizeA: '119 - 124 (cm)',
                    sizeB: '47.0&#34; - 49.0&#34;',
                    sizeC: '191 - 197 (cm)',
                    sizeD: '6&#39;3&#34; - 6&#39;5&#34;'
                },
                {
                    size: '50 Long',
                    sizeA: '124 - 130 (cm)',
                    sizeB: '49.0&#34; - 51.0&#34;',
                    sizeC: '191 - 197 (cm)',
                    sizeD: '6&#39;3&#34; - 6&#39;5&#34;'
                },
                {
                    size: '52 Long',
                    sizeA: '130 - 135 (cm)',
                    sizeB: '51.0&#34; - 53.0&#34;',
                    sizeC: '191 - 197 (cm)',
                    sizeD: '6&#39;3&#34; - 6&#39;5&#34;'
                },
                {
                    size: '54 Long',
                    sizeA: '135 - 140 (cm)',
                    sizeB: '53.0&#34; - 55.0&#34;',
                    sizeC: '191 - 197 (cm)',
                    sizeD: '6&#39;3&#34; - 6&#39;5&#34;'
                },
                {
                    size: '56 Long',
                    sizeA: '140 - 145 (cm)',
                    sizeB: '55.0&#34; - 57.0&#34;',
                    sizeC: '191 - 197 (cm)',
                    sizeD: '6&#39;3&#34; - 6&#39;5&#34;'
                },
                {
                    size: '58 Long',
                    sizeA: '145 - 150 (cm)',
                    sizeB: '57.0&#34; - 59.0&#34;',
                    sizeC: '191 - 197 (cm)',
                    sizeD: '6&#39;3&#34; - 6&#39;5&#34;'
                },
                {
                    size: '60 Long',
                    sizeA: '150 - 155 (cm)',
                    sizeB: '59.0&#34; - 61.0&#34;',
                    sizeC: '191 - 197 (cm)',
                    sizeD: '6&#39;3&#34; - 6&#39;5&#34;'
                },
                {
                    size: '62 Long',
                    sizeA: '155 - 160 (cm)',
                    sizeB: '61.0&#34; - 63.0&#34;',
                    sizeC: '191 - 197 (cm)',
                    sizeD: '6&#39;3&#34; - 6&#39;5&#34;'
                },
                {
                    size: '38 Extra Long',
                    sizeA: '94 - 99 (cm)',
                    sizeB: '37.0&#34; - 39.0&#34;',
                    sizeC: '198 - 216 (cm)',
                    sizeD: '6&#39;6&#34; - 7&#39;1&#34;'
                },
                {
                    size: '40 Extra Long',
                    sizeA: '99 - 104 (cm)',
                    sizeB: '39.0&#34; - 41.0&#34;',
                    sizeC: '198 - 216 (cm)',
                    sizeD: '6&#39;6&#34; - 7&#39;1&#34;'
                },
                {
                    size: '42 Extra Long',
                    sizeA: '104 - 109 (cm)',
                    sizeB: '41.0&#34; - 43.0&#34;',
                    sizeC: '198 - 216 (cm)',
                    sizeD: '6&#39;6&#34; - 7&#39;1&#34;'
                },
                {
                    size: '44 Extra Long',
                    sizeA: '109 - 114 (cm)',
                    sizeB: '43.0&#34; - 45.0&#34;',
                    sizeC: '198 - 216 (cm)',
                    sizeD: '6&#39;6&#34; - 7&#39;1&#34;'
                },
                {
                    size: '46 Extra Long',
                    sizeA: '114 - 119 (cm)',
                    sizeB: '45.0&#34; - 47.0&#34;',
                    sizeC: '198 - 216 (cm)',
                    sizeD: '6&#39;6&#34; - 7&#39;1&#34;'
                },
                {
                    size: '48 Extra Long',
                    sizeA: '119 - 124 (cm)',
                    sizeB: '47.0&#34; - 49.0&#34;',
                    sizeC: '198 - 216 (cm)',
                    sizeD: '6&#39;6&#34; - 7&#39;1&#34;'
                },
                {
                    size: '50 Extra Long',
                    sizeA: '124 - 130 (cm)',
                    sizeB: '49.0&#34; - 51.0&#34;',
                    sizeC: '198 - 216 (cm)',
                    sizeD: '6&#39;6&#34; - 7&#39;1&#34;'
                },
                {
                    size: '52 Extra Long',
                    sizeA: '130 - 135 (cm)',
                    sizeB: '51.0&#34; - 53.0&#34;',
                    sizeC: '198 - 216 (cm)',
                    sizeD: '6&#39;6&#34; - 7&#39;1&#34;'
                },
                {
                    size: '54 Extra Long',
                    sizeA: '135 - 140 (cm)',
                    sizeB: '53.0&#34; - 55.0&#34;',
                    sizeC: '198 - 216 (cm)',
                    sizeD: '6&#39;6&#34; - 7&#39;1&#34;'
                }
            ]
        },
        Custom: {
            id: 'Custom',
            type : 'Other',
            other : true,
            name: '自定义',
            example: '例子：Hardcover, Cookies & Cream, 12-Pack',
            data: []
        },
        Others: {
            id: 'Others',
            type : 'Checkbox',
            name: '其&#12288;它',
            data: [{size: "MousePad Foam Backed"}, {size: "Pack of 4"}, {size: "Pack of 50"}, {size: "MousePad Stick it"}, {size: "Pack of 6"}, {size: "Pack of 100"}, {size: "Pack of 1"}, {size: "Pack of 10"}, {size: "Left Hand"}, {size: "Pack of 2"}, {size: "Pack of 20"}, {size: "Right Hand"}]
        }
    }
};