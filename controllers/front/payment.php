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
        
        $is_suspended = false;
        $is_editable = true;
        try {
            $get_order_reference_details_request = new OffAmazonPaymentsService_Model_GetOrderReferenceDetailsRequest();
            $get_order_reference_details_request->setSellerId(self::$amz_payments->merchant_id);
            $get_order_reference_details_request->setAmazonOrderReferenceId($order_reference_id);
            if (isset($this->context->cookie->amz_access_token) && $this->context->cookie->amz_access_token != '') {
                $get_order_reference_details_request->setAddressConsentToken(AmzPayments::prepareCookieValueForAmazonPaymentsUse($this->context->cookie->amz_access_token));
            } elseif (getAmazonPayCookie()) {
                $get_order_reference_details_request->setAddressConsentToken(getAmazonPayCookie());
            }
            $reference_details_result_wrapper = $this->service->getOrderReferenceDetails($get_order_reference_details_request);
            if ($reference_details_result_wrapper->GetOrderReferenceDetailsResult->getOrderReferenceDetails()->getOrderReferenceStatus()->getState() == 'Open') {
                $is_editable = false;
            }
            if ($reference_details_result_wrapper->GetOrderReferenceDetailsResult->getOrderReferenceDetails()->getOrderReferenceStatus()->getState() == 'Suspended') {
                if ($reference_details_result_wrapper->GetOrderReferenceDetailsResult->getOrderReferenceDetails()->getOrderReferenceStatus()->getReasonCode() == 'PaymentAuthorizationRequired') {
                    $this->context->cookie->setHadErrorNowWallet = 1;
                }
                $is_suspended = true;
            }
        } catch (Exception $e) {
            $is_editable = true;
        }
         
        if ((!AmazonTransactions::isAlreadyConfirmedOrder($order_reference_id) && $is_editable) || $is_suspended) {
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
            $confirm_order_reference_request->setSuccessUrl($this->context->link->getModuleLink('amzpayments', 'processpayment'));
            $confirm_order_reference_request->setFailureUrl($this->context->link->getModuleLink('amzpayments', 'addresswallet'));
            $confirm_order_reference_request->setAmount($total);
            $confirm_order_reference_request->setCurrencyCode($currency_code);
                
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
        
        $this->context->smarty->assign('sellerId', self::$amz_payments->merchant_id);
        $this->context->smarty->assign('orderReferenceId', $order_reference_id);
    }
    
    public function initContent()
    {
        parent::initContent();
        $this->setTemplate('payment.tpl');
    }

    protected function exceptionLog($e)
    {
        self::$amz_payments->exceptionLog($e);
    }
}
