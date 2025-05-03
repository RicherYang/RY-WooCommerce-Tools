<?php

function rywt_bank_code_to_name($bank_code)
{
    static $bank_name = [];
    if (empty($bank_name)) {
        $bank_name = [
            '004' => _x('004', 'Bank code', 'ry-woocommerce-tools'), // 臺灣銀行
            '005' => _x('005', 'Bank code', 'ry-woocommerce-tools'), // 臺灣土地銀行
            '006' => _x('006', 'Bank code', 'ry-woocommerce-tools'), // 合作金庫商業銀行
            '007' => _x('007', 'Bank code', 'ry-woocommerce-tools'), // 第一商業銀行
            '008' => _x('008', 'Bank code', 'ry-woocommerce-tools'), // 華南商業銀行
            '009' => _x('009', 'Bank code', 'ry-woocommerce-tools'), // 彰化商業銀行
            '011' => _x('011', 'Bank code', 'ry-woocommerce-tools'), // 上海商業儲蓄銀行
            '012' => _x('012', 'Bank code', 'ry-woocommerce-tools'), // 台北富邦商業銀行
            '013' => _x('013', 'Bank code', 'ry-woocommerce-tools'), // 國泰世華商業銀行
            '015' => _x('015', 'Bank code', 'ry-woocommerce-tools'), // 中國輸出入銀行
            '016' => _x('016', 'Bank code', 'ry-woocommerce-tools'), // 高雄銀行
            '017' => _x('017', 'Bank code', 'ry-woocommerce-tools'), // 兆豐國際商業銀行
            '021' => _x('021', 'Bank code', 'ry-woocommerce-tools'), // 花旗(台灣)商業銀行
            '048' => _x('048', 'Bank code', 'ry-woocommerce-tools'), // 王道商業銀行
            '050' => _x('050', 'Bank code', 'ry-woocommerce-tools'), // 臺灣中小企業銀行
            '052' => _x('052', 'Bank code', 'ry-woocommerce-tools'), // 渣打國際商業銀行
            '053' => _x('053', 'Bank code', 'ry-woocommerce-tools'), // 台中商業銀行
            '054' => _x('054', 'Bank code', 'ry-woocommerce-tools'), // 京城商業銀行
            '081' => _x('081', 'Bank code', 'ry-woocommerce-tools'), // 滙豐(台灣)商業銀行
            '101' => _x('101', 'Bank code', 'ry-woocommerce-tools'), // 瑞興商業銀行
            '102' => _x('102', 'Bank code', 'ry-woocommerce-tools'), // 華泰商業銀行
            '103' => _x('103', 'Bank code', 'ry-woocommerce-tools'), // 臺灣新光商業銀行
            '108' => _x('108', 'Bank code', 'ry-woocommerce-tools'), // 陽信商業銀行
            '118' => _x('118', 'Bank code', 'ry-woocommerce-tools'), // 板信商業銀行
            '147' => _x('147', 'Bank code', 'ry-woocommerce-tools'), // 三信商業銀行
            '803' => _x('803', 'Bank code', 'ry-woocommerce-tools'), // 聯邦商業銀行
            '805' => _x('805', 'Bank code', 'ry-woocommerce-tools'), // 遠東國際商業銀行
            '806' => _x('806', 'Bank code', 'ry-woocommerce-tools'), // 元大商業銀行
            '807' => _x('807', 'Bank code', 'ry-woocommerce-tools'), // 永豐商業銀行
            '808' => _x('808', 'Bank code', 'ry-woocommerce-tools'), // 玉山商業銀行
            '809' => _x('809', 'Bank code', 'ry-woocommerce-tools'), // 凱基商業銀行
            '810' => _x('810', 'Bank code', 'ry-woocommerce-tools'), // 星展(台灣)商業銀行
            '812' => _x('812', 'Bank code', 'ry-woocommerce-tools'), // 台新國際商業銀行
            '816' => _x('816', 'Bank code', 'ry-woocommerce-tools'), // 安泰商業銀行
            '822' => _x('822', 'Bank code', 'ry-woocommerce-tools'), // 中國信託商業銀行
            '823' => _x('823', 'Bank code', 'ry-woocommerce-tools'), // 將來商業銀行
            '824' => _x('824', 'Bank code', 'ry-woocommerce-tools'), // 連線商業銀行
            '826' => _x('826', 'Bank code', 'ry-woocommerce-tools'), // 樂天國際商業銀行
            '020' => _x('020', 'Bank code', 'ry-woocommerce-tools'), // 日商瑞穗銀行
            '022' => _x('022', 'Bank code', 'ry-woocommerce-tools'), // 美商美國銀行
            '023' => _x('023', 'Bank code', 'ry-woocommerce-tools'), // 泰國盤谷銀行
            '025' => _x('025', 'Bank code', 'ry-woocommerce-tools'), // 菲商菲律賓首都銀行
            '028' => _x('028', 'Bank code', 'ry-woocommerce-tools'), // 美商美國紐約梅隆銀行
            '029' => _x('029', 'Bank code', 'ry-woocommerce-tools'), // 新加坡商大華銀行
            '030' => _x('030', 'Bank code', 'ry-woocommerce-tools'), // 美商道富銀行
            '037' => _x('037', 'Bank code', 'ry-woocommerce-tools'), // 法商法國興業銀行
            '039' => _x('039', 'Bank code', 'ry-woocommerce-tools'), // 澳商澳盛銀行
            '072' => _x('072', 'Bank code', 'ry-woocommerce-tools'), // 德商德意志銀行
            '075' => _x('075', 'Bank code', 'ry-woocommerce-tools'), // 香港商東亞銀行
            '076' => _x('076', 'Bank code', 'ry-woocommerce-tools'), // 美商摩根大通銀行
            '078' => _x('078', 'Bank code', 'ry-woocommerce-tools'), // 新加坡商星展銀行
            '082' => _x('082', 'Bank code', 'ry-woocommerce-tools'), // 法商法國巴黎銀行
            '083' => _x('083', 'Bank code', 'ry-woocommerce-tools'), // 英商渣打銀行
            '085' => _x('085', 'Bank code', 'ry-woocommerce-tools'), // 新加坡商新加坡華僑銀行
            '086' => _x('086', 'Bank code', 'ry-woocommerce-tools'), // 法商東方匯理銀行
            '092' => _x('092', 'Bank code', 'ry-woocommerce-tools'), // 瑞士商瑞士銀行
            '093' => _x('093', 'Bank code', 'ry-woocommerce-tools'), // 荷蘭商安智銀行
            '097' => _x('097', 'Bank code', 'ry-woocommerce-tools'), // 美商富國銀行
            '098' => _x('098', 'Bank code', 'ry-woocommerce-tools'), // 日商三菱日聯銀行
            '321' => _x('321', 'Bank code', 'ry-woocommerce-tools'), // 日商三井住友銀行
            '324' => _x('324', 'Bank code', 'ry-woocommerce-tools'), // 美商花旗銀行
            '325' => _x('325', 'Bank code', 'ry-woocommerce-tools'), // 香港商香港上海滙豐銀行
            '326' => _x('326', 'Bank code', 'ry-woocommerce-tools'), // 西班牙商西班牙對外銀行
            '328' => _x('328', 'Bank code', 'ry-woocommerce-tools'), // 法商法國外貿銀行
            '329' => _x('329', 'Bank code', 'ry-woocommerce-tools'), // 印尼商印尼人民銀行
            '330' => _x('330', 'Bank code', 'ry-woocommerce-tools'), // 韓商韓亞銀行
            '104' => _x('104', 'Bank code', 'ry-woocommerce-tools'), // 台北市第五信用合作社
            '114' => _x('114', 'Bank code', 'ry-woocommerce-tools'), // 基隆第一信用合作社
            '115' => _x('115', 'Bank code', 'ry-woocommerce-tools'), // 基隆市第二信用合作社
            '119' => _x('119', 'Bank code', 'ry-woocommerce-tools'), // 淡水第一信用合作社
            '120' => _x('120', 'Bank code', 'ry-woocommerce-tools'), // 新北市淡水信用合作社
            '124' => _x('124', 'Bank code', 'ry-woocommerce-tools'), // 宜蘭信用合作社
            '127' => _x('127', 'Bank code', 'ry-woocommerce-tools'), // 桃園信用合作社
            '130' => _x('130', 'Bank code', 'ry-woocommerce-tools'), // 新竹第一信用合作社
            '132' => _x('132', 'Bank code', 'ry-woocommerce-tools'), // 新竹第三信用合作社
            '146' => _x('146', 'Bank code', 'ry-woocommerce-tools'), // 台中市第二信用合作社
            '158' => _x('158', 'Bank code', 'ry-woocommerce-tools'), // 彰化第一信用合作社
            '161' => _x('161', 'Bank code', 'ry-woocommerce-tools'), // 彰化第五信用合作社
            '162' => _x('162', 'Bank code', 'ry-woocommerce-tools'), // 彰化第六信用合作社
            '163' => _x('163', 'Bank code', 'ry-woocommerce-tools'), // 彰化第十信用合作社
            '165' => _x('165', 'Bank code', 'ry-woocommerce-tools'), // 彰化縣鹿港信用合作社
            '178' => _x('178', 'Bank code', 'ry-woocommerce-tools'), // 嘉義市第三信用合作社
            '188' => _x('188', 'Bank code', 'ry-woocommerce-tools'), // 臺南第三信用合作社
            '204' => _x('204', 'Bank code', 'ry-woocommerce-tools'), // 高雄市第三信用合作社
            '215' => _x('215', 'Bank code', 'ry-woocommerce-tools'), // 花蓮第一信用合作社
            '216' => _x('216', 'Bank code', 'ry-woocommerce-tools'), // 花蓮第二信用合作社
            '222' => _x('222', 'Bank code', 'ry-woocommerce-tools'), // 澎湖縣第一信用合作社
            '223' => _x('223', 'Bank code', 'ry-woocommerce-tools'), // 澎湖第二信用合作社
            '224' => _x('224', 'Bank code', 'ry-woocommerce-tools'), // 金門縣信用合作社
        ];
    }

    return $bank_name[$bank_code] ?? $bank_code;
}
