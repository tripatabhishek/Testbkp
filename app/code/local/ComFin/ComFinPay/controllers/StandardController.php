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

require_once 'ComFin/ComFinPay.php';

class ComFin_ComFinPay_StandardController extends Mage_Core_Controller_Front_Action
{
    /**
     * Order instance
     */
    protected $_order;

    /**
     *  Get order
     *
     *  @return   EmPayTech_Sales_Model_Order
     */
    public function getOrder()
    {
        if ($this->_order == null) {
        }
        return $this->_order;
    }

    /**
     * Get singleton with ComFinPay standard order transaction information
     *
     * @return ComFin_ComFinPay_Model_PaymentMethod
     */
    public function getPaymentMethod()
    {
        return Mage::getSingleton('comfinpay/paymentMethod');
    }

    public function requestAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $quoteId = $session->getQuoteId();
        // FIXME: temporary quote id
        if (!$quoteId) $quoteId = 11;
        $quote = Mage::getModel("sales/quote")->load($quoteId);
       
        $reservedOrderId = $session->getComFinPayCustIdExt();

        $quote->setReservedOrderId($reservedOrderId);
        Mage::log('ComFinPay: requestAction: request loan for cust_id_ext '
            . $reservedOrderId);
            
            
        $comfinpay = new ComFinPay_Mage($this->getPaymentMethod());
        $response = $comfinpay->request($quote);
        $this->_redirectUrl( $response);
        if ($response->status_code == "APPROVED") {
            $this->_redirect('comfinpay/standard/redirect',
                array('_secure' => true));
        }
    }

    /**
     * When a customer chooses GetFinancing on Checkout/Payment page,
     * we open up a lightbox with the getfinancing box
     *
     */
    public function redirectAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Redirected to from getfinancing box on success
     */
    public function successAction()
    {
        $session = Mage::getSingleton('checkout/session');

        // $reservedOrderId = $session->getGetFinancingCustIdExt();
        // $order = $this->_convertQuote($reservedOrderId);

        $this->loadLayout();
        $this->renderLayout();
    }


    private function _respondUnauthorized()
    {
        # FIXME: we need to setHttpResponseCode as well for the unit test to pass
        # http://framework.zend.com/issues/browse/ZF-10374?page=com.atlassian.jira.plugin.system.issuetabpanels:changehistory-tabpanel
        $this->getResponse()
            ->setHttpResponseCode(401)
            ->setRawHeader('HTTP/1.1 401 Unauthorized')
            ->setBody('');
    }

    private function _respond($text)
    {
        $this->getResponse()
            ->setBody($text);
    }

    private function _validateKeys($keys) {
        $request = $this->getRequest();
        $params = $request->getPost();

        foreach ($keys as $key) {
            if (! array_key_exists ($key, $params)) {
                $this->_error("missing key $key");
                return FALSE;
            }
        }

        return TRUE;
    }

    private function _validateParams($allowed, $params)
    {
        foreach ($params as $key => $value) {
            if (array_key_exists($key, $allowed)) {
                if (! in_array($value, $allowed[$key])) {
                    $this->_error("unknown $key $value");
                    return False;
                }
            }
        }

        return True;
    }

    /* log the postback error and respond with it */
    private function _error($msg)
    {
        Mage::log('GetFinancing: postback: ' . $msg);
        $this->_respond($msg . "\n");
    }

    private function _validateAuth()
    {
        $paymentMethod = $this->getPaymentMethod();
        $postback_username = $paymentMethod->getConfigData('postback_username');
        $postback_password = $paymentMethod->getConfigData('postback_password');
        if (!$postback_username ||
            !$postback_password) {
            Mage::log('GetFinancing: postback: no authorization configured');
            return True;
        }

        # see http://evertpot.com/223/

        $username = null;
        $password = null;

        // mod_php
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];

        // most other servers
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            if (strpos(strtolower($_SERVER['HTTP_AUTHORIZATION']),
                'basic') === 0) {
                list($username, $password) = explode(':',
                    base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
            }
        }

        if ($username != $postback_username) {
            Mage::log("GetFinancing: postback: invalid username $username");
            return False;
        }
        if ($password != $postback_password) {
            Mage::log("GetFinancing: postback: invalid password $password");
            return False;
        }
        Mage::log("GetFinancing: postback: authorized");

        return True;
    }


    public function postbackAction()
    {
        $request = $this->getRequest();

        if (! $request->isPost()) {
            Mage::log('GetFinancing: postback: not a POST');
            $this->_respondUnauthorized();
            return;
        }
        if (! $this->_validateAuth()) {
            $this->_respondUnauthorized();
            return;
        }

        $params = $request->getPost();
        $postbackMessage = 'GetFinancing: postback: received postback ' .
            $params['function'];
        Mage::log($postbackMessage);
        Mage::log("GetFinancing: postback: " . http_build_query($params));

        if (! $this->_validateKeys(array('function')))
            return;


        switch ($params['function']) {
            case 'transact':
                return $this->_postbackTransact();
            case 'update_customer':
                return $this->_postbackUpdateCustomer();
            default:
                $this->_error("unknown function " . $params['function']);
                return;
        }
    }

    private function _postbackTransact()
    {
        $request = $this->getRequest();
        $params = $request->getPost();

        $allowed = array(
            'function' => array('transact'),
            'inv_status' => array('Auth', 'AuthOnly'),
            'method' => array('void', 'purchase')
        );

        if (! $this->_validateParams($allowed, $params)) {
            return;
        }

        $postbackMessage = 'GetFinancing: transact: received postback ' .
            $params['function'] . '/' .
            $params['inv_status'] . '/' .
            $params['method'] .
            ' for inv_id ' . $params['inv_id'];

        Mage::log($postbackMessage);

        if (!array_key_exists('cust_id_ext', $params)) {
            Mage::log('GetFinancing: transact: no cust_id_ext specified');
            return;
        }

        $order = Mage::getModel('sales/order')
            ->loadByIncrementId($params['cust_id_ext']);
        // FIXME: sanity check that this is the right order with our id ?
        // FIXME: may not have order yet, for purchase/AuthOnly
        if (!$order->getId()) {
            $message = "The order for cust_id_ext "
                . $params['cust_id_ext'] . " could not be found.";
            Mage::log("GetFinancing: transact: $message");
            Mage::throwException($message);
        }

        $actionMessage = "";

        if ($params['method'] == 'void') {
            if ($order->getState() !=
                Mage_Sales_Model_Order::STATE_CANCELED) {
                $this->_setState($order,
                    Mage_Sales_Model_Order::STATE_CANCELED);
                $actionMessage = 'order canceled by postback.';
            }
        } else if ($params['method'] == 'purchase') {
            if ($params['inv_status'] == 'AuthOnly') {
                $order = $this->_convertQuote($params['cust_id_ext']);
            } else if ($params['inv_status'] == 'Auth') {
                if ($order->getState() !=
                    Mage_Sales_Model_Order::STATE_PROCESSING) {
                    $this->_setState($order,
                        Mage_Sales_Model_Order::STATE_PROCESSING);
                    $actionMessage = 'order loan approved, ready to ship.';
                }
            } else {
                Mage::log('GetFinancing: transact: Unknown inv_action '
                    . $params['inv_action']);
            }
        } else {
            Mage::log('GetFinancing: transact: Unknown method '
                . $params['method']);
        }

        $order->addStatusToHistory($order->getStatus(), $postbackMessage,
            false);
        if ($actionMessage) {
            $order->addStatusToHistory($order->getStatus(),
                "GetFinancing: $actionMessage",
                false);
        }
        $order->save();

        Mage::log('GetFinancing: transact: outputting OK');

        print "OK\n";

    }

    private function _postbackUpdateCustomer()
    {
        /* FIXME: eventually change the order's billing/shipping ? */
        Mage::log('GetFinancing: update_customer: outputting OK');

        print "OK\n";
    }

    public function transactAction()
    {
        // we mistakenly called this action transact in the past, but there
        // should be one endpoint for all postbacks.
        return $this->postbackAction();
    }

    // FIXME: state and status are not the same; status is under state
    private function _setState($order, $state) {
        $oldState = $order->getState();
        $order->setState($state);
        $order->setStatus($state);
        $order->save();
    }

    /*
     * Convert a quote to an order in state holded (before: payment_review)
     * Called on AuthOnly/purchase callback and through onComplete callback
     * from box
     */
    private function _convertQuote($reservedOrderId) // , $custId, $invId)
    {
       // LOOK FOR EXISTING ORDER TO AVOID DUPLICATES
        $order = Mage::getModel('sales/order')->loadByIncrementId($reservedOrderId);
        if ($order->getId()) {
            //print_r($order);
            Mage::log('GetFinancing: convertQuote: we already have order with id ' .
                $reservedOrderId);
            return $order;
        }

        // new order, so look up quote for this order
        $quote = Mage::getModel("sales/quote")->load(
            $reservedOrderId, 'reserved_order_id');
        // FIXME: no way to get persisted additional info
        /*
        $custId = $quote->getGetFinancingCustId();
        $invId = $quote->getGetFinancingInvId();
        $custId = $quote->getAdditionalData('getfinancingCustId');
         */

        Mage::log("GetFinancing: convert quote for cust_id_ext $reservedOrderId " .
        //          ", GetFinancing cust_id $custId, inv_id $invId");
        "");

        // convert quote to order
        $quote->setIsActive(true);
        $quote->getPayment()->importData(array('method'=>'getfinancing'));

        /* see Mage_GoogleCheckout_Model_Api_Xml_Callback */
        $convertQuote = Mage::getSingleton('sales/convert_quote');
        $order = $convertQuote->toOrder($quote);

        if ($quote->isVirtual()) {
            $convertQuote->addressToOrder($quote->getBillingAddress(), $order);
        } else {
            $convertQuote->addressToOrder($quote->getShippingAddress(), $order);
        }

//        $order->setExtOrderId($this->getGoogleOrderNumber());
//        $order->setExtCustomerId($this->getData('root/buyer-id/VALUE'));

        // In Magento 1.4, $order does not have getCustomerEmail
        if (!$order->getCustomerEmail()) {
            $billing = $quote->getBillingAddress();
            $order->setCustomerEmail($billing->getEmail())
                ->setCustomerPrefix($billing->getPrefix())
                ->setCustomerFirstname($billing->getFirstname())
                ->setCustomerMiddlename($billing->getMiddlename())
                ->setCustomerLastname($billing->getLastname())
                ->setCustomerSuffix($billing->getSuffix());
        }

        $order->setBillingAddress($convertQuote->addressToOrderAddress($quote->getBillingAddress()));

        if (!$quote->isVirtual()) {
            $order->setShippingAddress($convertQuote->addressToOrderAddress($quote->getShippingAddress()));
        }


        foreach ($quote->getAllItems() as $item) {
            $orderItem = $convertQuote->itemToOrderItem($item);
            if ($item->getParentItem()) {
                $orderItem->setParentItem($order->getItemByQuoteItemId($item->getParentItem()->getId()));
            }
            $order->addItem($orderItem);
        }

        /*
         * Adding transaction for correct transaction information displaying on order view at back end.
         * It has no influence on api interaction logic.
         */
        $payment = Mage::getModel('sales/order_payment')
            ->setMethod('getfinancing')
//            ->setTransactionId($this->getGoogleOrderNumber())
            ->setIsTransactionClosed(false);
        $order->setPayment($payment);
        /* Payment->addTransaction does not exist in 1.4.0.1 but does exist
         * in 1.4.1.1 */
        $version = Mage::getVersion();
        if (version_compare ($version, '1.4.1', '<')) {
            $method = new ReflectionMethod('Mage_Sales_Model_Order_Payment', '_addTransaction');
            $method->setAccessible(true);
            $method->invoke($payment, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        } else {
            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        }

        // place sets state to STATE_PROCESSING
        $order->place();
        /* PAYMENT_REVIEW does not exist in 1.4.0.1 but does exist in 1.4.1.1
         */
        $version = Mage::getVersion();
        if (version_compare ($version, '1.4.1', '<')) {
            $this->_setState($order,
                Mage_Sales_Model_Order::STATE_HOLDED);
        } else {
            $this->_setState($order,
                Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW);
        }

        $order->save();
        $order->sendNewOrderEmail();

        $quote->setIsActive(false)->save();

        /* FIXME: get our getfinancing data
        $custId = $order->getGetFinancingCustId();
        $invId = $order->getGetFinancingInvId();
        */

        Mage::log("GetFinancing: convert to order for cust_id_ext $reservedOrderId " .
        //          ", GetFinancing cust_id $custId, inv_id $invId");
        "");


        /* add order comment */
        $order->addStatusToHistory($order->getStatus(),
            "GetFinancing: order created; hold off shipping for loan verification.",
            false);
        $order->save();

        return $order;
    }
}
