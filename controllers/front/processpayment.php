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

class AmzpaymentsProcesspaymentModuleFrontController extends ModuleFrontController
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
        $service = $this->service;
        
        $cart = $this->context->cart;
        
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active || !isset($this->context->cookie->amazon_id)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        if (Tools::getValue('AuthenticationStatus') != 'Success') {
            if (Tools::getValue('AuthenticationStatus') == 'Failure') {
                $this->context->cookie->amz_logout = true;
                unset(self::$amz_payments->cookie->amz_access_token);
                unset(self::$amz_payments->cookie->amz_access_token_set_time);
                unsetAmazonPayCookie();
                unset($this->context->cookie->amazon_id);
                unset($this->context->cookie->has_set_valid_amazon_address);
                unset($this->context->cookie->setHadErrorNowWallet);
                Tools::redirect($this->context->link->getPageLink('order'));
            }
            Tools::redirect('index.php?controller=order&step=1');
        }
        
        $customer = new Customer((int) $this->context->cart->id_customer);
        
        $order_reference_id = $this->context->cookie->amazon_id;
        
        $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
        
        $currency_order = new Currency((int) $this->context->cart->id_currency);
        $currency_code = $currency_order->iso_code;
        
        $requestParameters = array();
        $responsearray = array();
        $requestParameters['amazon_order_reference_id'] = $order_reference_id;
        $requestParameters['merchant_id'] = self::$amz_payments->merchant_id;
        $requestParameters['platform_id'] = self::$amz_payments->getPfId();

        $response = $service->GetOrderReferenceDetails($requestParameters);
        $responsearray['getorderreference'] = $response->toArray();
        
        if (self::$amz_payments->authorization_mode == 'fast_auth' || self::$amz_payments->authorization_mode == 'auto') {
            $authorization_reference_id = $order_reference_id;
            $authorization_response_wrapper = AmazonTransactions::fastAuth(self::$amz_payments, $this->service, $authorization_reference_id, $total, $currency_code);
            if (is_array($authorization_response_wrapper)) {
                $details = $authorization_response_wrapper['AuthorizeResult']['AuthorizationDetails'];
                $status = $details['AuthorizationStatus']['State'];
                if ($status == 'Declined') {
                    $reason = $details['AuthorizationStatus']['ReasonCode'];
                    if ($reason == 'InvalidPaymentMethod') {
                        $this->context->cookie->setHadErrorNowWallet = 1;
                        $this->context->cookie->amazonpay_errors_message = self::$amz_payments->l('Your selected payment method is currently not available. Please select another one.');
                        if (self::$amz_payments->order_process_type == 'standard') {
                            Tools::redirect($this->context->link->getModuleLink('amzpayments', 'addresswallet', array('amz' => $order_reference_id, 'ro' => '1')));
                        } else {
                            Tools::redirect($this->context->link->getModuleLink('amzpayments', 'amzpayments', array('amazon_id' => $order_reference_id, 'ro' => '1')));
                        }
                    } elseif ($reason == 'TransactionTimedOut') {
                        if (self::$amz_payments->authorization_mode == 'auto') {
                            $jump_to_async = true;
                        } else {
                            \AmazonTransactions::cancelOrder(self::$amz_payments, self::$amz_payments->getService(), $order_reference_id);
                            unset(self::$amz_payments->cookie->amz_access_token);
                            unset(self::$amz_payments->cookie->amz_access_token_set_time);
                            unset($this->context->cookie->amazon_id);
                            unset($this->context->cookie->has_set_valid_amazon_address);
                            unset($this->context->cookie->setHadErrorNowWallet);
                            $this->context->cookie->amazonpay_errors_message = self::$amz_payments->l('Your selected payment method is currently not available. Please select another one.');
                            Tools::redirect($this->context->link->getPageLink('order'));
                        }
                    } elseif ($reason == 'AmazonRejected') {
                        \AmazonTransactions::cancelOrder(self::$amz_payments, self::$amz_payments->getService(), $order_reference_id);
                        $this->context->cookie->amazonpay_errors_message = self::$amz_payments->l('Your selected payment method has been declined. Please chose another one.');
                        $this->context->cookie->amz_logout = true;
                        unset(self::$amz_payments->cookie->amz_access_token);
                        unset(self::$amz_payments->cookie->amz_access_token_set_time);
                        unsetAmazonPayCookie();
                        unset($this->context->cookie->amazon_id);
                        unset($this->context->cookie->has_set_valid_amazon_address);
                        unset($this->context->cookie->setHadErrorNowWallet);
                        Tools::redirect($this->context->link->getPageLink('order'));                        
                    } else {
                        $this->context->cookie->setHadErrorNowWallet = 1;
                        $this->context->cookie->amazonpay_errors_message = self::$amz_payments->l('Your selected payment method has been declined. Please chose another one.');
                        if (self::$amz_payments->order_process_type == 'standard') {
                            Tools::redirect($this->context->link->getModuleLink('amzpayments', 'addresswallet', array('amz' => $order_reference_id)));
                        } else {
                            Tools::redirect($this->context->link->getModuleLink('amzpayments', 'amzpayments', array('amazon_id' => $order_reference_id)));
                        }
                    }
                }
                if (!isset($jump_to_async)) {
                    $amazon_authorization_id = $details['AmazonAuthorizationId'];
                }
            }
        }
        
        if ($this->context->cart->secure_key == '') {
            $this->context->cart->secure_key = $customer->secure_key;
            $this->context->cart->save();
        }
        
        $new_order_status_id = (int)Configuration::get('PS_OS_PREPARATION');
        if ((int)Configuration::get('AMZ_ORDER_STATUS_ID') > 0) {
            $new_order_status_id = Configuration::get('AMZ_ORDER_STATUS_ID');
        }
        try {
            $this->module->validateOrder((int) $this->context->cart->id, $new_order_status_id, $total, $this->module->displayName, null, array(), null, false, $customer->secure_key);
        } catch (Exception $e) {
            $this->exceptionLog($e);
            echo $e->getMessage();
            exit();
        }
        
        self::$amz_payments->setOrderReferenceAtAmazonPay($this->module->currentOrder, $order_reference_id, $total, $currency_code);
        
        if (self::$amz_payments->authorization_mode == 'after_checkout' || isset($jump_to_async)) {
            $authorization_reference_id = $order_reference_id;
            $authorization_response_wrapper = AmazonTransactions::authorize(self::$amz_payments, $this->service, $authorization_reference_id, $total, $currency_code);
            $authorization_response_wrapper['AuthorizeResult']['AuthorizationDetails']['AmazonAuthorizationId'];
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
        unset($this->context->cookie->setHadErrorNowWallet);
        unset($this->context->cookie->amazon_id);
        Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$this->context->cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
    }

    protected function exceptionLog($e)
    {
        self::$amz_payments->exceptionLog($e);
    }
}
