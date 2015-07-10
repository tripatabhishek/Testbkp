<?php
/**
 * Product View block (Override)
 *
 * @category ComFin
 * @package  ComFin_ComFinPay
 * @copyright  Copyright (c) 2015 Community Finance
 * @author     Abhishek Tripathi
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
require_once 'ComFin/ComFinPay.php';
 
class ComFin_ComFinPay_Block_Catalog_Product_View extends Mage_Catalog_Block_Product_View
{
    public function displayAdd(){
    
        $objConFin = new ComFinPay1('0a9179b0f33161ce3c315f15f405f923');
        $data = $objConFin->priceSheet();
        return $abc;
    }


}