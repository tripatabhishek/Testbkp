<?php
/**
 * ComFin ComFinPay module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   ComFin
 * @package    ComFin_ComFinPay
 * @copyright  Copyright (c) 2015 Community Finance
 * @author     Abhishek Tripathi 
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class ComFinPay1
{
    /**
     * @param string $api_key     // API key is obtained from Community Finance
     */
    public function __construct($api_key) {
        $this->api_key  = $api_key;
    }
    /**
     * @param string $url     // Json data is passed to be parsed in an array.
     * @param array $options  // Optional                     
     */
    function request( $url, $options = array() ) {
    try{
        $this->url  = $url;
        
        $options = array_merge($options, array('apikey' => $this->api_key));
        
        $query = http_build_query($options);
        
        $ch = curl_init($this->url);
        
        curl_setopt($ch, CURLOPT_URL, $this->url);
      //  curl_setopt($ch, CURLOPT_VERBOSE, 1);
      //  curl_setopt($ch, CURLOPT_SSLVERSION, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      //  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_POST, 1);
       
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
     //   curl_setopt($ch, CURLOPT_FOLLOWLOCATION ,1);
        // FIXME: we have to set response_format
        //curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //    "Accept: application/xml"
        //));
//        curl_setopt($ch, CURLOPT_HEADER, 0); // DO NOT RETURN HTTP HEADERS
        /* return contents of the call as a variable; otherwise it prints */
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      //  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

        $result = curl_exec($ch);
        
        $error = curl_error($ch);
        
        curl_close($ch);
       
       if ($error) {
            $msg = "curl_exec error: $error";
        }
        $response = $this->parseResponse($result);
        if($response['error'] > 0) {
            //Mage::throwException("Community Finance API call error: $response['message']");
            throw new Exception('Community Finance API call error:'. $response['message']);
        }
       } catch (Exception $e) {
           echo $e->getMessage();
       }
        return $response;
    }

    /**
     * @param string $jsonString     // Json data is passed to be parsed in an array.
     * @return array $response                            
     */
    public function parseResponse($jsonString)
    {
       $response = json_decode($jsonString, true);
       
       return $response;
    }
    
    /**
     * @param string $url     // URL .
     * @return array $priceSheet                            
     */
    public function priceSheet($url = 'https://communityfinancellc.com/api/?pricesheet')
    {
        $priceSheet = $this->request($url, array());
        
        return $priceSheet;
    }
    
    /**
     * @param string $url     // URL .
     * @return array $linkApplication    // link to application.                        
     */
    public function linkApplication($url = 'https://communityfinancellc.com/api/?link')
    {
        $linkApplication = $this->request($url, array());
        
        return $linkApplication;
    }
    
    /**
     * @param string $url     // URL .
     * @return array $paymentDetail    // Detail of Lease for a Given Amount.                 
     */
    public function paymentDetail($amount, $url = 'https://communityfinancellc.com/api/?detail')
    {
        $paymentDetail = $this->request($url, array('amount' => $amount));
        
        return $paymentDetail;
    }
    
    /**
     * @param string $url     // URL .
     * @return array $approvalStatus    // Approval Code Lookup.                 
     */
    public function approvalStatus($code, $url = 'https://communityfinancellc.com/api/?approval')
    {
        $approvalStatus = $this->request($url, array('code' => $code));
        
        return $approvalStatus;
    }
    
}

class ComFinPay
{
    /**
     * @param string $url      url to redirect.
     * @param string $merch_id merchant id
     * @param string $username
     * @param string $password
     */
    public function __construct($url, $merch_id, $api_key) {
        $this->url = $url;
        $this->merch_id = $merch_id;
        $this->api_key = $api_key;
    }

    public function getDataspace() {
        if (strpos($this->url, 'api-test') !== false) {
            return 'staging';
        } else {
            return 'production';
        }
    }

    public function log($msg) {
    }

    function request($fields) {
        $fields = array_merge($fields, array(
            'dg_username'=> $this->_username,
            'dg_password'=> $this->_password,
            'merch_id'=> $this->merch_id));

        $query = http_build_query($fields);
        $this->log("request: $query");

        $ch = curl_init($this->url);

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_SSLVERSION, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION ,1);
        // FIXME: we have to set response_format
        //curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //    "Accept: application/xml"
        //));
//        curl_setopt($ch, CURLOPT_HEADER, 0); // DO NOT RETURN HTTP HEADERS
        /* return contents of the call as a variable; otherwise it prints */
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $msg = "curl_exec error: $error";
            $this->log($msg);
            Mage::throwException("GetFinancing: $msg");
        }

        $response = $this->parseResponse($result);

        $this->log('response: ' . $response->asXML());
        return $response;
    }

    public function parseResponse($xmlString)
    {
        /* SimpleXMLElement returns an element proxying for the root <response>
           tag */
        $response = new SimpleXMLElement($xmlString);

        return $response;
    }

}

class ComFinPay_Mage extends ComFinPay
{
    public function __construct($paymentMethod) {

        $this->configured = FALSE;

        $errors = FALSE;
        $message = "";

        $keys = array("platform", "merch_id", "api_key");

        foreach ($keys as $key) {
            $$key = $paymentMethod->getConfigData($key);
            if (!$$key) {
                $message = "Please specify '$key' in the configuration.";
                $errorkey = $key;
                $errors = TRUE;
            }
        }

        $platforms = array('staging', 'production');
        if (!in_array($platform, $platforms)) {
            $message = "platform is set to unsupported value '$platform'";
            $errorkey = 'platform';
            $errors = TRUE;
        }

        if ($errors) {
            $this->log($message);
            $this->misconfigured($message, $errorkey);

            /* FIXME: can we return half-constructed ? */
            return;
        }

        $key = "gateway_url_$platform";
        $url = $paymentMethod->getConfigData($key);

        if (!$url) {
            $message = "Please specify '$key' in the configuration of the payment method.";
            $errors = TRUE;
        }

        if ($errors) {
            $this->log($message);
            $this->misconfigured($message, $key);

            /* FIXME: can we return half-constructed ? */
            return;
        }


        $this->configured = TRUE;

        parent::__construct($url, $merch_id, $api_key);
    }

    /* parent class implementations */
    public function log($msg) {
        Mage::log("GetFinancing: $msg");
    }

    private function misconfigured($message, $option = "")
    {
        if ($option)
            $option = " (option '$option')";
//        Mage::throwException(Mage::helper('paygate')->__("The web store has misconfigured the GetFinancing payment module${option}."));
        $this->_adminnotification(
            $title          = Mage::helper('comfinpay')->__('GetFinancing: the payment method is misconfigured.%s', $option),
            $description    = $message
        );
    }

    /*
     * Mage_Sales_Model_Quote
     */
    public function request($quote)
    {
        $errors = FALSE;

        $totals = number_format($quote->getGrandTotal(), 2, '.', '');
        $cust_id_ext = $quote->reserveOrderId()->getReservedOrderId();

        $this->log("request: new request for total amount $totals"
                   . " and cust_id_ext $cust_id_ext");

        $version = Mage::getVersion();
        $this->log("request: Magento version: $version");

        $billingaddress = $quote->getBillingAddress();
        $shippingaddress = $quote->getShippingAddress();

        /* FIXME: Magento 1.4.0.1 seems to not have getCustomerEmail ? */
        $this->log("request: billing  email " . $billingaddress->getEmail());
        $this->log("request: shipping email " . $shippingaddress->getEmail());
        $this->log("request: quote    email " . $quote->getCustomerEmail());

        $sparse_array = array(
            $billingaddress->getEmail(),
            $shippingaddress->getEmail(),
            $quote->getCustomerEmail());
        /* array_filter keeps truthy values, so throws out empty strings */
        $email = current(array_filter($sparse_array));

        $this->log("request: Got email $email");

        $description = "";
        $cartHelper = Mage::helper('checkout/cart');
        $items = $cartHelper->getCart()->getItems();

        foreach ($items as $item) {
            $description .= $item->getName() . " (" . $item->getQty() . "), ";
        }
        $description = substr($description, 0, -2);
        $this->log("request: description " . $description);

        $fields = array(
            'cust_fname'=> $billingaddress->getData('firstname'),
            'cust_lname'=> $billingaddress->getData('lastname'),
            'cust_email'=> $email,

            'bill_addr_address'=> $billingaddress->getData('street'),
            'bill_addr_city'=> $billingaddress->getData('city'),
            'bill_addr_state'=> $billingaddress->getData('region'),
            'bill_addr_zip'=> $billingaddress->getData('postcode'),

            'ship_addr_address'=> $shippingaddress->getData('street'),
            'ship_addr_city'=> $shippingaddress->getData('city'),
            'ship_addr_state'=> $shippingaddress->getData('region'),
            'ship_addr_zip'=> $shippingaddress->getData('postcode'),

            'version' => '0.3',
            'response_format' => 'JSON',
            // FIXME
            // 'cust_id_ext'=> $cust_id_ext,
            'reserved_order_id'  =>  $cust_id_ext,    
            'inv_action' => 'AUTH_ONLY',
            'inv_value' => $totals,
            'udf02' => $description,
            'api_key' => $this->api_key,
            'merch_id' => $this->merch_id
        );
     
       // $response = parent::request($fields);  this makes a curl request.
            $query = http_build_query($fields);            
            $url = $this->url;
            $finalUrl = $url.'/?'.$query;
            
            return $finalUrl;

        switch ($response->status_code) {
            case "GW_NOT_AUTH":
                $message = "request: response status: " . $response->status_string;
                $message .= "  Please verify your authentication details.";
                $this->misconfigured($message, "merch_id/username/password");
                break;
            case "GW_MISSING_FIELD":
                $message = "Missing a field in the request: " . $response->status_string;
                $this->log("request: $message");
                Mage::throwException($message);
                break;
            case "APPROVED":
                /* store application url in session so our phtml can use them */
                $this->getSession()->setGetFinancingApplicationURL((string) $response->udf02);
                $this->getSession()->setGetFinancingCustId((string) $response->cust_id);
                $this->getSession()->setGetFinancingInvId((string) $response->inv_id);
                /* store response values on quote too */
                // FIXME: these don't actually persist at all when saved
                //        get them from postback instead
                /*
                $quote->setGetFinancingApplicationURL((string) $response->udf02);
                $quote->setGetFinancingCustId((string) $response->cust_id);
                $quote->setGetFinancingInvId((string) $response->inv_id);
                $quote->save();
                */
                break;
            default:
                /* throwing an exception makes us stay on this page so we can repeat */
                $message = "Unhandled response status code " . $response->status_code;
                $this->log("request: $message");
                Mage::throwException($message);
        }

        return $response;
    }

     /**
     * Get getfinancing session namespace
     *
     * @return Mage_GetFinancing_Model_Session
     */
    public function getSession()
    {
        return Mage::getSingleton('checkout/session');
    }


    /**
     * Create a Magento admin notification.
     */
    private function _adminnotification(
        $title,
        $description,
        $severity = Mage_AdminNotification_Model_Inbox::SEVERITY_MAJOR
    )
    {
        $this->log("creating admin notification $title");

        $date = date('Y-m-d H:i:s');
        /* We use parse because it's the only thing 1.4.0.1 has */
        /* FIXME: can we set url to the payment method config ? */
        Mage::getModel('adminnotification/inbox')->parse(array(
            array(
                'severity'      => $severity,
                'date_added'    => $date,
                'title'         => $title,
                'description'   => $description,
                'url'           => '',
#                'url' => Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit/section/payment'),
                'internal'      => true
            )
        ));
    }

}
