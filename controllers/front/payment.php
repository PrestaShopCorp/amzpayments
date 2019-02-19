<?php
/**
 * 2013-2017 Amazon Advanced Payment APIs Modul
*
* for Support please visit www.patworx.de
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
*  @author    patworx multimedia GmbH <service@patworx.de>
*  @copyright 2013-2017 patworx multimedia GmbH
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

class AmzpaymentsPaymentModuleFrontController extends ModuleFrontController
{

    public $ssl = true;

    public $isLogged = false;

    public $display_column_left = false;

    public $display_column_right = false;

    public $service;

    protected $ajax_refresh = false;

    protected $css_files_assigned = array();

    protected $js_files_assigned = array();

    protected static $amz_payments = '';
    
    public function __construct()
    {
        $this->controller_type = 'modulefront';
        
        $this->module = Module::getInstanceByName(Tools::getValue('module'));
        if (! $this->module->active) {
            Tools::redirect('index');
        }
        $this->page_name = 'module-' . $this->module->name . '-' . Dispatcher::getInstance()->getController();
        
        parent::__construct();
    }

    public function postProcess()
    {
        self::$amz_payments = new AmzPayments();
        $this->service = self::$amz_payments->getService();
        
        $cart = $this->context->cart;
        
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'amzpayments') {
                $authorized = true;
                break;
            }
        }
        
        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'payment'));
        }
        
        $customer = new Customer((int) $this->context->cart->id_customer);
        
        $order_reference_id = $this->context->cookie->amazon_id;
        
        $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
            
        $currency_order = new Currency((int) $this->context->cart->id_currency);
        $currency_code = $currency_order->iso_code;
        if ($currency_code == 'JYP') {
            $currency_code = 'YEN';
        }
            
        if (! AmazonTransactions::isAlreadyConfirmedOrder($order_reference_id)) {
            if (isset($this->context->cookie->setHadErrorNowWallet) && $this->context->cookie->setHadErrorNowWallet == 1) {
            } else {
                $set_order_reference_details_request = new OffAmazonPaymentsService_Model_SetOrderReferenceDetailsRequest();
                $set_order_reference_details_request->setSellerId(self::$amz_payments->merchant_id);
                $set_order_reference_details_request->setAmazonOrderReferenceId($order_reference_id);
                $set_order_reference_details_request->setOrderReferenceAttributes(new OffAmazonPaymentsService_Model_OrderReferenceAttributes());
                $set_order_reference_details_request->getOrderReferenceAttributes()->setOrderTotal(new OffAmazonPaymentsService_Model_OrderTotal());
                $set_order_reference_details_request->getOrderReferenceAttributes()
                ->getOrderTotal()
                ->setCurrencyCode($currency_code);
                $set_order_reference_details_request->getOrderReferenceAttributes()
                ->getOrderTotal()
                ->setAmount($total);
                $set_order_reference_details_request->getOrderReferenceAttributes()->setPlatformId(self::$amz_payments->getPfId());
                $set_order_reference_details_request->getOrderReferenceAttributes()->setSellerOrderAttributes(new OffAmazonPaymentsService_Model_SellerOrderAttributes());
                $set_order_reference_details_request->getOrderReferenceAttributes()
                ->getSellerOrderAttributes()
                ->setSellerOrderId(self::$amz_payments->createUniqueOrderId((int) $this->context->cart->id));
                $set_order_reference_details_request->getOrderReferenceAttributes()
                ->getSellerOrderAttributes()
                ->setStoreName(Configuration::get('PS_SHOP_NAME'));
                $set_order_reference_details_request->getOrderReferenceAttributes()
                ->getSellerOrderAttributes()
                ->setCustomInformation('Prestashop,Patworx,' . self::$amz_payments->version);
                
                $this->service->setOrderReferenceDetails($set_order_reference_details_request);
            }
            
            $get_order_reference_details_request = new OffAmazonPaymentsService_Model_GetOrderReferenceDetailsRequest();
            $get_order_reference_details_request->setSellerId(self::$amz_payments->merchant_id);
            $get_order_reference_details_request->setAmazonOrderReferenceId($order_reference_id);
            if (isset($this->context->cookie->amz_access_token) && $this->context->cookie->amz_access_token != '') {
                $get_order_reference_details_request->setAddressConsentToken(AmzPayments::prepareCookieValueForAmazonPaymentsUse($this->context->cookie->amz_access_token));
            } else {
                if (getAmazonPayCookie()) {
                    $get_order_reference_details_request->setAddressConsentToken(getAmazonPayCookie());
                }
            }
            
            try {
                $reference_details_result_wrapper = $this->service->getOrderReferenceDetails($get_order_reference_details_request);
            } catch (OffAmazonPaymentsService_Exception $e) {
                $this->exceptionLog($e);
                $this->context->cookie->amazonpay_errors_message = self::$amz_payments->l('Your selected payment method is currently not available. Please select another one.');
                Tools::redirect($this->context->link->getModuleLink('amzpayments', 'addresswallet', array('amz' => $order_reference_id)));
            }
            
            $confirm_order_reference_request = new OffAmazonPaymentsService_Model_ConfirmOrderReferenceRequest();
            $confirm_order_reference_request->setAmazonOrderReferenceId($order_reference_id);
            $confirm_order_reference_request->setSellerId(self::$amz_payments->merchant_id);
                
            try {
                $this->service->confirmOrderReference($confirm_order_reference_request);
            } catch (OffAmazonPaymentsService_Exception $e) {
                $this->exceptionLog($e);
                $this->context->cookie->amazonpay_errors_message = self::$amz_payments->l('Your selected payment method is currently not available. Please select another one.');
                Tools::redirect($this->context->link->getModuleLink('amzpayments', 'addresswallet', array('amz' => $order_reference_id)));
            }
                
            $get_order_reference_details_request = new OffAmazonPaymentsService_Model_GetOrderReferenceDetailsRequest();
            $get_order_reference_details_request->setSellerId(self::$amz_payments->merchant_id);
            $get_order_reference_details_request->setAmazonOrderReferenceId($order_reference_id);
            if (isset($this->context->cookie->amz_access_token) && $this->context->cookie->amz_access_token != '') {
                $get_order_reference_details_request->setAddressConsentToken(AmzPayments::prepareCookieValueForAmazonPaymentsUse($this->context->cookie->amz_access_token));
            } else {
                if (getAmazonPayCookie()) {
                    $get_order_reference_details_request->setAddressConsentToken(getAmazonPayCookie());
                }
            }
            $reference_details_result_wrapper = $this->service->getOrderReferenceDetails($get_order_reference_details_request);
            
            $sql_arr = array(
                'amz_tx_time' => pSQL(time()),
                'amz_tx_type' => 'order_ref',
                'amz_tx_status' => pSQL($reference_details_result_wrapper->GetOrderReferenceDetailsResult->getOrderReferenceDetails()
                    ->getOrderReferenceStatus()
                    ->getState()),
                'amz_tx_order_reference' => pSQL($order_reference_id),
                'amz_tx_expiration' => pSQL(strtotime($reference_details_result_wrapper->GetOrderReferenceDetailsResult->getOrderReferenceDetails()->getExpirationTimestamp())),
                'amz_tx_reference' => pSQL($order_reference_id),
                'amz_tx_amz_id' => pSQL($order_reference_id),
                'amz_tx_last_change' => pSQL(time()),
                'amz_tx_amount' => pSQL($reference_details_result_wrapper->GetOrderReferenceDetailsResult->getOrderReferenceDetails()
                    ->getOrderTotal()
                    ->getAmount())
            );
            Db::getInstance()->insert('amz_transactions', $sql_arr);
        } else {
            $get_order_reference_details_request = new OffAmazonPaymentsService_Model_GetOrderReferenceDetailsRequest();
            $get_order_reference_details_request->setSellerId(self::$amz_payments->merchant_id);
            $get_order_reference_details_request->setAmazonOrderReferenceId($order_reference_id);
            if (isset($this->context->cookie->amz_access_token) && $this->context->cookie->amz_access_token != '') {
                $get_order_reference_details_request->setAddressConsentToken(AmzPayments::prepareCookieValueForAmazonPaymentsUse($this->context->cookie->amz_access_token));
            } else {
                if (getAmazonPayCookie()) {
                    $get_order_reference_details_request->setAddressConsentToken(getAmazonPayCookie());
                }
            }
            $reference_details_result_wrapper = $this->service->getOrderReferenceDetails($get_order_reference_details_request);
        }
            
        
            
        if (self::$amz_payments->authorization_mode == 'fast_auth' || self::$amz_payments->authorization_mode == 'auto') {
            $authorization_reference_id = $order_reference_id;
            
            if (isset($this->context->cookie->setHadErrorNowWallet) && $this->context->cookie->setHadErrorNowWallet == 1) {
                $confirm_order_ref_req_model = new OffAmazonPaymentsService_Model_ConfirmOrderReferenceRequest();
                $confirm_order_ref_req_model->setAmazonOrderReferenceId($order_reference_id);
                $confirm_order_ref_req_model->setSellerId(self::$amz_payments->merchant_id);
                try {
                    $this->service->confirmOrderReference($confirm_order_ref_req_model);
                } catch (OffAmazonPaymentsService_Exception $e) {
                    $this->exceptionLog($e);
                    $this->context->cookie->amazonpay_errors_message = self::$amz_payments->l('Your selected payment method has been declined. Please chose another one.');
                    Tools::redirect($this->context->link->getModuleLink('amzpayments', 'addresswallet', array('amz' => $order_reference_id)));
                }
                unset($this->context->cookie->setHadErrorNowWallet);
            }
            
            $authorization_response_wrapper = AmazonTransactions::fastAuth(self::$amz_payments, $this->service, $authorization_reference_id, $total, $currency_code);
            if (is_object($authorization_response_wrapper)) {
                $details = $authorization_response_wrapper->getAuthorizeResult()->getAuthorizationDetails();
                $status = $details->getAuthorizationStatus()->getState();
                
                if ($status == 'Declined') {
                    $reason = $details->getAuthorizationStatus()->getReasonCode();
                    if ($reason == 'InvalidPaymentMethod') {
                        $this->context->cookie->setHadErrorNowWallet = 1;
                        $this->context->cookie->amazonpay_errors_message = self::$amz_payments->l('Your selected payment method is currently not available. Please select another one.');
                        Tools::redirect($this->context->link->getModuleLink('amzpayments', 'addresswallet', array('amz' => $order_reference_id)));
                    } elseif ($reason == 'TransactionTimedOut') {
                        if (self::$amz_payments->authorization_mode == 'auto') {
                            $jump_to_async = true;
                        } else {
                            unset($this->context->cookie->setHadErrorNowWallet);
                            unset($this->context->cookie->has_set_valid_amazon_address);
                            $this->context->cookie->amazonpay_errors_message = self::$amz_payments->l('Your selected payment method is currently not available. Please select another one.');
                            Tools::redirect($this->context->link->getPageLink('order'));
                        }
                    } elseif ($reason == 'AmazonRejected') {
                        if (self::$amz_payments->authorization_mode == 'auto') {
                            $jump_to_async = true;
                        } else {
                            $this->context->cookie->amazonpay_errors_message = self::$amz_payments->l('Your selected payment method has been declined. Please chose another one.');
                            $this->context->cookie->amz_logout = true;
                            unset($this->context->cookie->amz_access_token);
                            unsetAmazonPayCookie();
                            unset($this->context->cookie->amz_access_token_set_time);
                            unset($this->context->cookie->amazon_id);
                            unset($this->context->cookie->has_set_valid_amazon_address);
                            unset($this->context->cookie->setHadErrorNowWallet);
                            Tools::redirect($this->context->link->getPageLink('order'));
                        }
                    } else {
                        $this->context->cookie->setHadErrorNowWallet = 1;
                        $this->context->cookie->amazonpay_errors_message = self::$amz_payments->l('Your selected payment method has been declined. Please chose another one.');
                        Tools::redirect($this->context->link->getModuleLink('amzpayments', 'addresswallet', array('amz' => $order_reference_id)));
                    }
                }
                
                if (!isset($jump_to_async)) {
                    $amazon_authorization_id = $authorization_response_wrapper->getAuthorizeResult()
                    ->getAuthorizationDetails()
                    ->getAmazonAuthorizationId();
                }
            }
        }
        
        if ($this->context->cart->secure_key == '') {
            $this->context->cart->secure_key = $customer->secure_key;
            $this->context->cart->save();
        }
            
        $new_order_status_id = (int) Configuration::get('PS_OS_PREPARATION');
        if ((int) Configuration::get('AMZ_ORDER_STATUS_ID') > 0) {
            $new_order_status_id = Configuration::get('AMZ_ORDER_STATUS_ID');
        }
        
        try {
            $this->module->validateOrder((int) $this->context->cart->id, $new_order_status_id, $total, $this->module->displayName, null, array(), null, false, $customer->secure_key);
        } catch (Exception $e) {
            $this->exceptionLog($e);
            echo $e->getMessage();
            exit();
        }
        
        if (self::$amz_payments->authorization_mode == 'after_checkout' || isset($jump_to_async)) {
            $authorization_reference_id = $order_reference_id;
            $authorization_response_wrapper = AmazonTransactions::authorize(self::$amz_payments, $this->service, $authorization_reference_id, $total, $currency_code);
            $amazon_authorization_id = @$authorization_response_wrapper->getAuthorizeResult()
            ->getAuthorizationDetails()
            ->getAmazonAuthorizationId();
        }
            
        self::$amz_payments->setAmazonReferenceIdForOrderId($order_reference_id, $this->module->currentOrder);
        self::$amz_payments->setAmazonReferenceIdForOrderTransactionId($order_reference_id, $this->module->currentOrder);
        if (isset($authorization_reference_id)) {
            self::$amz_payments->setAmazonAuthorizationReferenceIdForOrderId($authorization_reference_id, $this->module->currentOrder);
        }
        if (isset($amazon_authorization_id)) {
            self::$amz_payments->setAmazonAuthorizationIdForOrderId($amazon_authorization_id, $this->module->currentOrder);
        }
        
        if (isset($this->context->cookie->amzSetStatusAuthorized)) {
            $tmpOrderRefs = Tools::unSerialize($this->context->cookie->amzSetStatusAuthorized);
            if (is_array($tmpOrderRefs)) {
                foreach ($tmpOrderRefs as $order_ref) {
                    AmazonTransactions::setOrderStatusAuthorized($order_ref);
                }
            }
            unset($this->context->cookie->amzSetStatusAuthorized);
        }
        if (isset($this->context->cookie->amzSetStatusCaptured)) {
            $tmpOrderRefs = Tools::unSerialize($this->context->cookie->amzSetStatusCaptured);
            if (is_array($tmpOrderRefs)) {
                foreach ($tmpOrderRefs as $order_ref) {
                    AmazonTransactions::setOrderStatusCaptured($order_ref);
                }
            }
            unset($this->context->cookie->amzSetStatusCaptured);
        }
        unset($this->context->cookie->amazon_id);
        Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$this->context->cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
    }

    protected function exceptionLog($e)
    {
        self::$amz_payments->exceptionLog($e);
    }
}
