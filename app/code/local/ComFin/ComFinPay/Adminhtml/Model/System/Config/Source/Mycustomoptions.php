<?php

/**
 * My own options
 *
 */
class ComFin_ComFinPay_Adminhtml_Model_System_Config_Source_Mycustomoptions
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
       /* FIXME: for translation, see
        * app/code/community/Creativestyle/CheckoutByAmazon/Block/Adminhtml/Debug
        */
       return array(
            array('value' => 'staging',
                  'label' => Mage::helper('comfinpay')->__('Staging')),
            array('value' => 'production',
                  'label' => Mage::helper('comfinpay')->__('Production')),

        );

    }

}

?>
