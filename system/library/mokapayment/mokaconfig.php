<?php

Class MokaConfig {

    const max_installment = 12;

    public static function getAvailablePrograms() {
        return array(
            'axess' => array('name' => 'Axess', 'bank' => 'Akbank A.Ş.', 'installments' => true),
            'world' => array('name' => 'WordCard', 'bank' => 'Yapı Kredi Bankası', 'installments' => true),
            'bonus' => array('name' => 'BonusCard', 'bank' => 'Garanti Bankası A.Ş.', 'installments' => true),
            'cardfinans' => array('name' => 'CardFinans', 'bank' => 'FinansBank A.Ş.', 'installments' => true),
            'maximum' => array('name' => 'Maximum', 'bank' => 'T.C. İş Bankası', 'installments' => true),
	    'paraf' => array('name' => 'Paraf', 'bank' => 'Halk Bankası', 'installments' => true),
        );
    }

    public static function setRatesFromPost($posted_data) {
        $banks = MokaConfig::getAvailablePrograms();
        $return = array();
        foreach ($banks as $k => $v) {
            $return[$k] = array();
            for ($i = 1; $i <= self::max_installment; $i++) {
                $return[$k]['installments'][$i]['value'] = isset($posted_data[$k]['installments'][$i]['value']) ? ((float) $posted_data[$k]['installments'][$i]['value']) : 0.0;
                $return[$k]['installments'][$i]['active'] = isset($posted_data[$k]['installments'][$i]['active']) ? ((int) $posted_data[$k]['installments'][$i]['active']) : 0;
            }
        }
        return $return;
    }

    public static function setRatesDefault() {
        $banks = MokaConfig::getAvailablePrograms();
        $return = array();
        foreach ($banks as $k => $v) {
            $return[$k] = array('active' => 0);
            for ($i = 1; $i <= self::max_installment; $i++) {
                $return[$k]['installments'][$i]['value'] = (float) (1 + $i + ($i / 5) + 0.1);
                $return[$k]['installments'][$i]['active'] = $v['installments'];
                if ($i == 1) {
                    $return[$k]['installments'][$i]['value'] = 0.00;
                    $return[$k]['installments'][$i]['active'] = 1;
                }
            }
        }
        return $return;
    }

    public static function setRatesNull() {
        $banks = MokaConfig::getAvailablePrograms();
        $return = array();
        foreach ($banks as $k => $v) {
            $return[$k] = array('active' => 0);
            for ($i = 1; $i <= self::max_installment; $i++) {
                $return[$k]['installments'][$i]['value'] = 0;
                $return[$k]['installments'][$i]['active'] = 0;
            }
        }
        return $return;
    }

    public static function createRatesUpdateForm($rates) {
        $return = '<table class="moka_table table">'
                . '<thead>'
                . '<tr><th>Banka</th><th>Durum</th>';
        for ($i = 1; $i <= self::max_installment; $i++) {
            $return .= '<th>' . $i . ' taksit</th>';
        }
        $return .= '</tr></thead><tbody>';

        $banks = MokaConfig::getAvailablePrograms();
        foreach ($banks as $k => $v) {
            $return .= '<tr>'
                    . '<th><img src="' . HTTP_CATALOG . 'catalog/view/theme/default/image/moka_payment/' . $k . '.svg" width="105px"></th>'
                    . '<th><select  name="payment_moka_payment_rates[' . $k . '][active]" >'
                    . '<option value="1">Aktif</option>'
                    . '<option value="0" ' . ((int) $rates[$k]['active'] == 0 ? 'selected="selected"' : '') . '>Pasif</option>'
                    . '</select></th>';
            for ($i = 1; $i <= self::max_installment; $i++) {
                if (!isset($rates[$k]['installments'][$i]['active']))
                    $rates[$k]['installments'][$i]['active'] = 0;
                if (!isset($rates[$k]['installments'][$i]['value']))
                    $rates[$k]['installments'][$i]['value'] = 0;
                $return .= '<td>'
                        . ' Aktif <input type="checkbox"  name="payment_moka_payment_rates[' . $k . '][installments][' . $i . '][active]" '
                        . ' value="1" ' . ((int) $rates[$k]['installments'][$i]['active'] == 1 ? 'checked="checked"' : '') . '/>'
                        . ' % <input type="number" step="0.01" maxlength="4" size="4" style="width:60px" '
                        . ((int) $rates[$k]['installments'][$i]['active'] == 0 ? 'disabled="disabled"' : '')
                        . ' value="' . ((float) $rates[$k]['installments'][$i]['value']) . '"'
                        . ' name="payment_moka_payment_rates[' . $k . '][installments][' . $i . '][value]"/></td>';
            }
            $return .= '</tr>';
        }
        $return .= '</tbody></table>';
        return $return;
    }

    public static function calculatePrices($price, $rates) {
        $banks = MokaConfig::getAvailablePrograms();
        $return = array();
        foreach ($banks as $k => $v) {
            if ($v['installments'] == false)
                continue;
            $return[$k] = array('active' => $rates[$k]['active']);
            for ($i = 1; $i <= self::max_installment; $i++) {
                $return[$k]['installments'][$i] = array(
                    'active' => $rates[$k]['installments'][$i]['active'],
                    'total' => number_format((((100 + $rates[$k]['installments'][$i]['value']) * $price) / 100), 2, '.', ''),
                    'monthly' => number_format((((100 + $rates[$k]['installments'][$i]['value']) * $price) / 100) / $i, 2, '.', ''),
                );
            }
        }
        return $return;
    }

    public static function getProductInstallments($price, $rates) {
//        print_r($rates);
//        exit;
        $prices = MokaConfig::calculatePrices($price, $rates);
        $banks = MokaConfig::getAvailablePrograms();
        $return = '<style>
		   
   .moka-rates-table {      border-spacing: 0;      border-collapse: collapse;      width: 100%;   }
   .moka-rates-table * {      font-family: "Helvetica Neue",Helvetica,Arial,sans-serif!important;   } 
   .moka-rates-table-with-bg img{      width: 64px;   } 
   .moka-rates-table-with-bg {      color: #111;      background: #f8f8f8;      border: 1px solid #e3e3e3;   } 
   .moka-rates-table td.axess{    background-color:#e2b631;    color:#fff;   }
   .moka-rates-table td.maximum{       background-color:#f52295;       color:#fff;   } 
   .moka-rates-table td.cardfinans{       background-color: #2d5fc2;       color:#fff;   }
   .moka-rates-table td.world{       background-color: #6f6b99;       color:#fff;   } 
   .moka-rates-table td.bonus{       background-color: #479279;       color:#fff;   }
   .moka-rates-table td{      padding: 5px 10px;      text-align: center;   } 
   .moka-amount {      font-size: 13px;      font-weight: 700;      line-height: 20px;   }
   .moka-rates-table td span {      display: inline-block;      width: 100%;      text-align: center;   } 
   .moka-rates-table 
   .moka-total-amount {      font-size: 11px;      font-weight: 400;      line-height: 20px;   } 
   .moka-rates-table td {      border: 1px dashed #d6d6d6;   } 
   .moka-rates-table-with-bg {      font-size: 13px!important;   }
		</style>
		
		<table  style="width: 100%;" class="moka-rates-table"> <tbody>   <tr style="height:50px;">  <th>Taksit</th>    ';
        foreach ($banks as $k => $v) {
            $return .= '	<th class="moka-rates-table-with-bg">   
		   <img src="catalog/view/theme/default/image/moka_payment/' . $k . '.svg">	 </th> ';
        }
        $return .= '</tr>       
     <tr>           
	 </tr><tr> ';


        for ($ins = 1; $ins < 10; $ins++) {
            if ($ins == 1) {
                $return .= '<td class="moka-rates-table-with-bg" style="height:50px;"> Peşin </td> ';
            } else {

                $return .= '<td class="moka-rates-table-with-bg" style="height:50px;"> ' . $ins . ' Taksit </td> ';
            }
            foreach ($banks as $k => $v) {

                if ($ins == 1) {
                    $return .= ' <td class="' . $k . '">  <span class="moka-amount"> ' . $prices{$k}['installments']{$ins}['total'] . '  TL</span> </td>   ';
                } else {

                    $return .= ' <td class="' . $k . '">  <span class="moka-amount"> ' . $prices{$k}['installments']{$ins}['monthly'] . ' x ' . $ins . ' </span><span class="moka-total-amount"> TOPLAM ' . $prices{$k}['installments']{$ins}['total'] . ' TL </span> </td>   ';
                }
            }

            $return .= '</tr>';
        }
        $return .= '<tbody></table>';



        return $return;
    }

}
