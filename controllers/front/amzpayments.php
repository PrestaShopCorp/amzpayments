<?php

/**
 * 2013-2015 Amazon Advanced Payment APIs Modul
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
 *  @copyright 2013-2015 patworx multimedia GmbH
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
class AmzpaymentsAmzpaymentsModuleFrontController extends ModuleFrontController
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

    public function init()
    {
        self::$amz_payments = new AmzPayments();
        $this->isLogged = (bool) $this->context->customer->id && Customer::customerIdExistsStatic((int) $this->context->cookie->id_customer);
        
        parent::init();
        
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        $this->display_column_left = false;
        $this->display_column_right = false;
        
        $this->service = self::$amz_payments->getService();
        
        $this->nbProducts = $this->context->cart->nbProducts();
        
        if (Configuration::get('PS_CATALOG_MODE'))
            $this->errors[] = Tools::displayError('This store has not accepted your new order.');
        
        if ($this->nbProducts) {
            if (CartRule::isFeatureActive()) {
                if (Tools::isSubmit('submitAddDiscount')) {
                    if (! ($code = trim(Tools::getValue('discount_name')))) {
                        $this->errors[] = Tools::displayError('You must enter a voucher code.');
                    } elseif (! Validate::isCleanHtml($code)) {
                        $this->errors[] = Tools::displayError('The voucher code is invalid.');
                    } else {
                        if (($cart_rule = new CartRule(CartRule::getIdByCode($code))) && Validate::isLoadedObject($cart_rule)) {
                            if ($error = $cart_rule->checkValidity($this->context, false, true)) {
                                $this->errors[] = $error;
                            } else {
                                $this->context->cart->addCartRule($cart_rule->id);
                                if (Configuration::get('PS_ORDER_PROCESS_TYPE') == 1) {
                                    Tools::redirect('index.php?controller=order-opc&addingCartRule=1');
                                }
                                Tools::redirect('index.php?controller=order&addingCartRule=1');
                            }
                        } else {
                            $this->errors[] = Tools::displayError('This voucher does not exists.');
                        }
                    }
                    $this->context->smarty->assign(array(
                        'errors' => $this->errors,
                        'discount_name' => Tools::safeOutput($code)
                    ));
                } elseif (($id_cart_rule = (int) Tools::getValue('deleteDiscount')) && Validate::isUnsignedId($id_cart_rule)) {
                    $this->context->cart->removeCartRule($id_cart_rule);
                    Tools::redirect('index.php?controller=order-opc');
                }
            }
            if ($this->context->cart->isVirtualCart()) {
                $this->setNoCarrier();
            }
        } else {
            Tools::redirect('index.php?controller=order-opc');
        }
        
        $this->context->smarty->assign('back', Tools::safeOutput(Tools::getValue('back')));
        
        if ($this->nbProducts) {
            $this->context->smarty->assign('virtual_cart', $this->context->cart->isVirtualCart());
        }
        
        $this->context->smarty->assign('is_multi_address_delivery', $this->context->cart->isMultiAddressDelivery() || ((int) Tools::getValue('multi-shipping') == 1));
        $this->context->smarty->assign('open_multishipping_fancybox', (int) Tools::getValue('multi-shipping') == 1);
        
        if ($this->context->cart->nbProducts()) {
            if (Tools::isSubmit('ajax')) {
                if (Tools::isSubmit('method')) {
                    switch (Tools::getValue('method')) {
                        case 'setsession':
                            $this->context->cookie->amazon_id = Tools::getValue('amazon_id');
                            $this->context->cookie->amz_access_token = AmzPayments::prepareCookieValueForPrestaShopUse(Tools::getValue('access_token'));
                            $this->context->cookie->amz_access_token_set_time = time();
                            exit();
                        
                        case 'updateMessage':
                            if (Tools::isSubmit('message')) {
                                $txt_message = urldecode(Tools::getValue('message'));
                                $this->_updateMessage($txt_message);
                                if (count($this->errors)) {
                                    die('{"hasError" : true, "errors" : ["' . implode('\',\'', $this->errors) . '"]}');
                                }
                                die(true);
                            }
                            break;
                        
                        case 'updateCarrierAndGetPayments':
                            if ((Tools::isSubmit('delivery_option') || Tools::isSubmit('id_carrier')) && Tools::isSubmit('recyclable') && Tools::isSubmit('gift') && Tools::isSubmit('gift_message')) {
                                $this->_assignWrappingAndTOS();
                                if ($this->_processCarrier()) {
                                    $carriers = $this->context->cart->simulateCarriersOutput();
                                    $return = array_merge(array(
                                        'HOOK_TOP_PAYMENT' => Hook::exec('displayPaymentTop'),
                                        'HOOK_PAYMENT' => $this->_getPaymentMethods(),
                                        'carrier_data' => $this->_getCarrierList(),
                                        'HOOK_BEFORECARRIER' => Hook::exec('displayBeforeCarrier', array(
                                            'carriers' => $carriers
                                        ))
                                    ), $this->getFormatedSummaryDetail());
                                    Cart::addExtraCarriers($return);
                                    die(Tools::jsonEncode($return));
                                } else {
                                    $this->errors[] = Tools::displayError('An error occurred while updating the cart.');
                                }
                                if (count($this->errors)) {
                                    die('{"hasError" : true, "errors" : ["' . implode('\',\'', $this->errors) . '"]}');
                                }
                                exit();
                            }
                            break;
                        
                        case 'updateTOSStatusAndGetPayments':
                            if (Tools::isSubmit('checked')) {
                                $this->context->cookie->checkedTOS = (int) (Tools::getValue('checked'));
                                die(Tools::jsonEncode(array(
                                    'HOOK_TOP_PAYMENT' => Hook::exec('displayPaymentTop'),
                                    'HOOK_PAYMENT' => $this->_getPaymentMethods()
                                )));
                            }
                            break;
                        
                        case 'getCarrierList':
                            die(Tools::jsonEncode($this->_getCarrierList()));
                        
                        case 'getAddressBlockAndCarriersAndPayments':
                            if ($this->context->customer->isLogged()) {
                                if (! Customer::getAddressesTotalById($this->context->customer->id)) {
                                    die(Tools::jsonEncode(array(
                                        'no_address' => 1
                                    )));
                                }
                                if (file_exists(_PS_MODULE_DIR_ . 'blockuserinfo/blockuserinfo.php')) {
                                    include_once (_PS_MODULE_DIR_ . 'blockuserinfo/blockuserinfo.php');
                                    $block_user_info = new BlockUserInfo();
                                }
                                $this->context->smarty->assign('isVirtualCart', $this->context->cart->isVirtualCart());
                                $this->_processAddressFormat();
                                $this->_assignAddress();
                                $wrapping_fees = $this->context->cart->getGiftWrappingPrice(false);
                                $wrapping_fees_tax_inc = $wrapping_fees = $this->context->cart->getGiftWrappingPrice();
                                $return = array_merge(array(
                                    'order_opc_adress' => $this->context->smarty->fetch(_PS_THEME_DIR_ . 'order-address.tpl'),
                                    'block_user_info' => (isset($block_user_info) ? $block_user_info->hookTop(array()) : ''),
                                    'carrier_data' => $this->_getCarrierList(),
                                    'HOOK_TOP_PAYMENT' => Hook::exec('displayPaymentTop'),
                                    'HOOK_PAYMENT' => $this->_getPaymentMethods(),
                                    'no_address' => 0,
                                    'gift_price' => Tools::displayPrice(Tools::convertPrice(Product::getTaxCalculationMethod() == 1 ? $wrapping_fees : $wrapping_fees_tax_inc, new Currency((int) ($this->context->cookie->id_currency))))
                                ), $this->getFormatedSummaryDetail());
                                die(Tools::jsonEncode($return));
                            }
                            die(Tools::displayError());
                        
                        case 'makeFreeOrder':
                            if (($id_order = $this->_checkFreeOrder()) && $id_order) {
                                $order = new Order((int) $id_order);
                                $email = $this->context->customer->email;
                                if ($this->context->customer->is_guest) {
                                    $this->context->customer->logout();
                                }
                                die('freeorder:' . $order->reference . ':' . $email);
                            }
                            exit();
                        
                        case 'updateAddressesSelected':
                            
                            $get_order_reference_details_request = new OffAmazonPaymentsService_Model_GetOrderReferenceDetailsRequest();
                            $get_order_reference_details_request->setSellerId(self::$amz_payments->merchant_id);
                            $get_order_reference_details_request->setAmazonOrderReferenceId(Tools::getValue('amazonOrderReferenceId'));
                            if (isset($this->context->cookie->amz_access_token)) {
                                $get_order_reference_details_request->setAddressConsentToken(AmzPayments::prepareCookieValueForAmazonPaymentsUse($this->context->cookie->amz_access_token));
                            }
                            $reference_details_result_wrapper = $this->service->getOrderReferenceDetails($get_order_reference_details_request);
                            $physical_destination = $reference_details_result_wrapper->GetOrderReferenceDetailsResult->getOrderReferenceDetails()
                                ->getDestination()
                                ->getPhysicalDestination();
                            
                            $iso_code = (string) $physical_destination->GetCountryCode();
                            $city = (string) $physical_destination->GetCity();
                            $postcode = (string) $physical_destination->GetPostalCode();
                            
                            $address_delivery = AmazonPaymentsAddressHelper::findByAmazonOrderReferenceIdOrNew(Tools::getValue('amazonOrderReferenceId'));
                            $address_delivery->id_country = Country::getByIso($iso_code);
                            $address_delivery->alias = 'Amazon Payments Delivery';
                            $address_delivery->lastname = 'amzLastname';
                            $address_delivery->firstname = 'amzFirstname';
                            $address_delivery->address1 = 'amzAddress1';
                            $address_delivery->city = $city;
                            $address_delivery->postcode = $postcode;
                            $address_delivery->save();
                            AmazonPaymentsAddressHelper::saveAddressAmazonReference($address_delivery, Tools::getValue('amazonOrderReferenceId'));
                            
                            $this->context->smarty->assign('isVirtualCart', $this->context->cart->isVirtualCart());
                            
                            $this->context->cart->id_address_delivery = $address_delivery->id;
                            $this->context->cart->id_address_invoice = $address_delivery->id;
                            
                            $this->context->cart->setNoMultishipping();
                            
                            if (! $this->context->cart->update()) {
                                $this->errors[] = Tools::displayError('An error occurred while updating your cart.');
                            }
                            
                            $infos = Address::getCountryAndState((int) ($this->context->cart->id_address_delivery));
                            if (isset($infos['id_country']) && $infos['id_country']) {
                                $country = new Country((int) $infos['id_country']);
                                $this->context->country = $country;
                            }
                            
                            $cart_rules = $this->context->cart->getCartRules();
                            CartRule::autoRemoveFromCart($this->context);
                            CartRule::autoAddToCart($this->context);
                            if ((int) Tools::getValue('allow_refresh')) {
                                $cart_rules2 = $this->context->cart->getCartRules();
                                if (count($cart_rules2) != count($cart_rules)) {
                                    $this->ajax_refresh = true;
                                } else {
                                    $rule_list = array();
                                    foreach ($cart_rules2 as $rule) {
                                        $rule_list[] = $rule['id_cart_rule'];
                                    }
                                    foreach ($cart_rules as $rule) {
                                        if (! in_array($rule['id_cart_rule'], $rule_list)) {
                                            $this->ajax_refresh = true;
                                            break;
                                        }
                                    }
                                }
                            }
                            
                            if (! $this->context->cart->isMultiAddressDelivery()) {
                                $this->context->cart->setNoMultishipping();
                            }
                            
                            if (! count($this->errors)) {
                                $result = $this->_getCarrierList();
                                
                                if (isset($result['hasError'])) {
                                    unset($result['hasError']);
                                }
                                if (isset($result['errors'])) {
                                    unset($result['errors']);
                                }
                                
                                $wrapping_fees = $this->context->cart->getGiftWrappingPrice(false);
                                $wrapping_fees_tax_inc = $wrapping_fees = $this->context->cart->getGiftWrappingPrice();
                                $result = array_merge($result, array(
                                    'HOOK_TOP_PAYMENT' => Hook::exec('displayPaymentTop'),
                                    'HOOK_PAYMENT' => $this->_getPaymentMethods(),
                                    'gift_price' => Tools::displayPrice(Tools::convertPrice(Product::getTaxCalculationMethod() == 1 ? $wrapping_fees : $wrapping_fees_tax_inc, new Currency((int) ($this->context->cookie->id_currency)))),
                                    'carrier_data' => $this->_getCarrierList(),
                                    'refresh' => (bool) $this->ajax_refresh
                                ), $this->getFormatedSummaryDetail());
                                die(Tools::jsonEncode($result));
                            }
                            
                            if (count($this->errors)) {
                                die(Tools::jsonEncode(array(
                                    'hasError' => true,
                                    'errors' => $this->errors
                                )));
                            }
                            break;
                        
                        case 'multishipping':
                            $this->_assignSummaryInformations();
                            $this->context->smarty->assign('product_list', $this->context->cart->getProducts());
                            
                            if ($this->context->customer->id) {
                                $this->context->smarty->assign('address_list', $this->context->customer->getAddresses($this->context->language->id));
                            } else {
                                $this->context->smarty->assign('address_list', array());
                            }
                            $this->setTemplate(_PS_THEME_DIR_ . 'order-address-multishipping-products.tpl');
                            $this->display();
                            die();
                        
                        case 'cartReload':
                            $this->_assignSummaryInformations();
                            if ($this->context->customer->id) {
                                $this->context->smarty->assign('address_list', $this->context->customer->getAddresses($this->context->language->id));
                            } else {
                                $this->context->smarty->assign('address_list', array());
                            }
                            $this->context->smarty->assign('opc', true);
                            $this->setTemplate(_PS_THEME_DIR_ . 'shopping-cart.tpl');
                            $this->display();
                            die();
                        
                        case 'noMultiAddressDelivery':
                            $this->context->cart->setNoMultishipping();
                            die();
                        
                        case 'executeOrder':
                            $customer = new Customer((int) $this->context->cart->id_customer);
                            if (! Validate::isLoadedObject($customer)) {
                                $customer->is_guest = true;
                                $customer->lastname = 'AmazonPayments';
                                $customer->firstname = 'AmazonPayments';
                                $customer->email = 'amazon' . time() . '@localshop.xyz';
                                $customer->passwd = Tools::substr(md5(time()), 0, 10);
                                $customer->save();
                            }
                            
                            if (Tools::getValue('confirm')) {
                                $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
                                
                                $currency_order = new Currency((int) $this->context->cart->id_currency);
                                $currency_code = $currency_order->iso_code;
                                
                                if (! AmazonTransactions::isAlreadyConfirmedOrder(Tools::getValue('amazonOrderReferenceId'))) {
                                    $set_order_reference_details_request = new OffAmazonPaymentsService_Model_SetOrderReferenceDetailsRequest();
                                    $set_order_reference_details_request->setSellerId(self::$amz_payments->merchant_id);
                                    $set_order_reference_details_request->setAmazonOrderReferenceId(Tools::getValue('amazonOrderReferenceId'));
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
                                    
                                    $this->service->setOrderReferenceDetails($set_order_reference_details_request);
                                    
                                    $confirm_order_reference_request = new OffAmazonPaymentsService_Model_ConfirmOrderReferenceRequest();
                                    $confirm_order_reference_request->setAmazonOrderReferenceId(Tools::getValue('amazonOrderReferenceId'));
                                    $confirm_order_reference_request->setSellerId(self::$amz_payments->merchant_id);
                                    $this->service->confirmOrderReference($confirm_order_reference_request);
                                    
                                    $get_order_reference_details_request = new OffAmazonPaymentsService_Model_GetOrderReferenceDetailsRequest();
                                    $get_order_reference_details_request->setSellerId(self::$amz_payments->merchant_id);
                                    $get_order_reference_details_request->setAmazonOrderReferenceId(Tools::getValue('amazonOrderReferenceId'));
                                    if (isset($this->context->cookie->amz_access_token)) {
                                        $get_order_reference_details_request->setAddressConsentToken(AmzPayments::prepareCookieValueForAmazonPaymentsUse($this->context->cookie->amz_access_token));
                                    }
                                    $reference_details_result_wrapper = $this->service->getOrderReferenceDetails($get_order_reference_details_request);
                                    
                                    $sql_arr = array(
                                        'amz_tx_time' => time(),
                                        'amz_tx_type' => 'order_ref',
                                        'amz_tx_status' => $reference_details_result_wrapper->GetOrderReferenceDetailsResult->getOrderReferenceDetails()
                                            ->getOrderReferenceStatus()
                                            ->getState(),
                                        'amz_tx_order_reference' => Tools::getValue('amazonOrderReferenceId'),
                                        'amz_tx_expiration' => strtotime($reference_details_result_wrapper->GetOrderReferenceDetailsResult->getOrderReferenceDetails()->getExpirationTimestamp()),
                                        'amz_tx_reference' => Tools::getValue('amazonOrderReferenceId'),
                                        'amz_tx_amz_id' => Tools::getValue('amazonOrderReferenceId'),
                                        'amz_tx_last_change' => time(),
                                        'amz_tx_amount' => $reference_details_result_wrapper->GetOrderReferenceDetailsResult->getOrderReferenceDetails()
                                            ->getOrderTotal()
                                            ->getAmount()
                                    );
                                    Db::getInstance()->insert('amz_transactions', $sql_arr);
                                } else {
                                    $get_order_reference_details_request = new OffAmazonPaymentsService_Model_GetOrderReferenceDetailsRequest();
                                    $get_order_reference_details_request->setSellerId(self::$amz_payments->merchant_id);
                                    $get_order_reference_details_request->setAmazonOrderReferenceId(Tools::getValue('amazonOrderReferenceId'));
                                    if (isset($this->context->cookie->amz_access_token)) {
                                        $get_order_reference_details_request->setAddressConsentToken(AmzPayments::prepareCookieValueForAmazonPaymentsUse($this->context->cookie->amz_access_token));
                                    }
                                    $reference_details_result_wrapper = $this->service->getOrderReferenceDetails($get_order_reference_details_request);
                                }
                                
                                $physical_destination = $reference_details_result_wrapper->GetOrderReferenceDetailsResult->getOrderReferenceDetails()
                                    ->getDestination()
                                    ->getPhysicalDestination();
                                
                                $iso_code = (string) $physical_destination->GetCountryCode();
                                $city = (string) $physical_destination->GetCity();
                                $postcode = (string) $physical_destination->GetPostalCode();
                                
                                $names_array = explode(' ', (string) $physical_destination->getName(), 2);
                                
                                $regex = '/[^a-zA-ZäöüÄÖÜß\s]/';
                                $names_array[0] = preg_replace($regex, '', $names_array[0]);
                                $names_array[1] = preg_replace($regex, '', $names_array[1]);
                                
                                if ($customer->is_guest) {
                                    $customer->lastname = $names_array[1];
                                    $customer->firstname = $names_array[0];
                                    $customer->email = (string) $reference_details_result_wrapper->GetOrderReferenceDetailsResult->getOrderReferenceDetails()
                                        ->getBuyer()
                                        ->getEmail();
                                    $customer->save();
                                    $this->context->cart->id_customer = $customer->id;
                                    $this->context->cart->save();
                                }
                                
                                $s_company_name = '';
                                if ((string) $physical_destination->getAddressLine3() != '') {
                                    $s_street = Tools::substr($physical_destination->getAddressLine3(), 0, Tools::strrpos($physical_destination->getAddressLine3(), ' '));
                                    $s_street_nr = Tools::substr($physical_destination->getAddressLine3(), Tools::strrpos($physical_destination->getAddressLine3(), ' ') + 1);
                                    $s_company_name = trim($physical_destination->getAddressLine1() . $physical_destination->getAddressLine2());
                                } else 
                                    if ((string) $physical_destination->getAddressLine2() != '') {
                                        $s_street = Tools::substr($physical_destination->getAddressLine2(), 0, Tools::strrpos($physical_destination->getAddressLine2(), ' '));
                                        $s_street_nr = Tools::substr($physical_destination->getAddressLine2(), Tools::strrpos($physical_destination->getAddressLine2(), ' ') + 1);
                                        $s_company_name = trim($physical_destination->getAddressLine1());
                                    } else {
                                        $s_street = Tools::substr($physical_destination->getAddressLine1(), 0, Tools::strrpos($physical_destination->getAddressLine1(), ' '));
                                        $s_street_nr = Tools::substr($physical_destination->getAddressLine1(), Tools::strrpos($physical_destination->getAddressLine1(), ' ') + 1);
                                    }
                                
                                $phone = '';
                                if ((string) $physical_destination->getPhone() != '' && ValidateCore::isPhoneNumber((string) $physical_destination->getPhone())) {
                                    $phone = (string) $physical_destination->getPhone();
                                }
                                
                                $address_delivery = AmazonPaymentsAddressHelper::findByAmazonOrderReferenceIdOrNew(Tools::getValue('amazonOrderReferenceId'));
                                $address_delivery->lastname = $names_array[1];
                                $address_delivery->firstname = $names_array[0];
                                if ($s_company_name != '') {
                                    $address_delivery->company = $s_company_name;
                                }
                                $address_delivery->address1 = (string) $s_street . ' ' . (string) $s_street_nr;
                                $address_delivery->postcode = (string) $physical_destination->getPostalCode();
                                $address_delivery->id_country = Country::getByIso((string) $physical_destination->getCountryCode());
                                if ($phone != '') {
                                    $address_delivery->phone = $phone;
                                }
                                $address_delivery->save();
                                AmazonPaymentsAddressHelper::saveAddressAmazonReference($address_delivery, Tools::getValue('amazonOrderReferenceId'));
                                
                                $this->context->cart->id_address_delivery = $address_delivery->id;
                                
                                $billing_address_object = $reference_details_result_wrapper->GetOrderReferenceDetailsResult->getOrderReferenceDetails()->getBillingAddress();
                                
                                if (method_exists($billing_address_object, 'getPhysicalAddress')) {
                                    $amz_billing_address = $reference_details_result_wrapper->GetOrderReferenceDetailsResult->getOrderReferenceDetails()
                                        ->getBillingAddress()
                                        ->getPhysicalAddress();
                                    
                                    $iso_code = (string) $amz_billing_address->GetCountryCode();
                                    $city = (string) $amz_billing_address->GetCity();
                                    $postcode = (string) $amz_billing_address->GetPostalCode();
                                    
                                    $invoice_names_array = explode(' ', (string) $amz_billing_address->getName(), 2);
                                    
                                    $regex = '/[^a-zA-ZäöüÄÖÜß\s]/';
                                    $invoice_names_array[0] = preg_replace($regex, '', $invoice_names_array[0]);
                                    $invoice_names_array[1] = preg_replace($regex, '', $invoice_names_array[1]);
                                    
                                    $s_company_name = '';
                                    if ((string) $amz_billing_address->getAddressLine3() != '') {
                                        $s_street = Tools::substr($amz_billing_address->getAddressLine3(), 0, Tools::strrpos($amz_billing_address->getAddressLine3(), ' '));
                                        $s_street_nr = Tools::substr($amz_billing_address->getAddressLine3(), Tools::strrpos($amz_billing_address->getAddressLine3(), ' ') + 1);
                                        $s_company_name = trim($amz_billing_address->getAddressLine1() . $amz_billing_address->getAddressLine2());
                                    } else 
                                        if ((string) $amz_billing_address->getAddressLine2() != '') {
                                            $s_street = Tools::substr($amz_billing_address->getAddressLine2(), 0, Tools::strrpos($amz_billing_address->getAddressLine2(), ' '));
                                            $s_street_nr = Tools::substr($amz_billing_address->getAddressLine2(), Tools::strrpos($amz_billing_address->getAddressLine2(), ' ') + 1);
                                            $s_company_name = trim($amz_billing_address->getAddressLine1());
                                        } else {
                                            $s_street = Tools::substr($amz_billing_address->getAddressLine1(), 0, Tools::strrpos($amz_billing_address->getAddressLine1(), ' '));
                                            $s_street_nr = Tools::substr($amz_billing_address->getAddressLine1(), Tools::strrpos($amz_billing_address->getAddressLine1(), ' ') + 1);
                                        }
                                    
                                    $phone = '';
                                    if ((string) $amz_billing_address->getPhone() != '' && ValidateCore::isPhoneNumber((string) $amz_billing_address->getPhone())) {
                                        $phone = (string) $amz_billing_address->getPhone();
                                    }
                                    
                                    $address_invoice = AmazonPaymentsAddressHelper::findByAmazonOrderReferenceIdOrNew(Tools::getValue('amazonOrderReferenceId') . '-inv');
                                    $address_invoice->alias = 'Amazon Payments Invoice';
                                    $address_invoice->lastname = $invoice_names_array[1];
                                    $address_invoice->firstname = $invoice_names_array[0];
                                    if ($s_company_name != '') {
                                        $address_invoice->company = $s_company_name;
                                    }
                                    $address_invoice->address1 = (string) $s_street . ' ' . (string) $s_street_nr;
                                    $address_invoice->postcode = (string) $amz_billing_address->getPostalCode();
                                    $address_invoice->city = $city;
                                    $address_invoice->id_country = Country::getByIso((string) $amz_billing_address->getCountryCode());
                                    if ($phone != '') {
                                        $address_invoice->phone = $phone;
                                    }
                                    $address_invoice->save();
                                    AmazonPaymentsAddressHelper::saveAddressAmazonReference($address_invoice, Tools::getValue('amazonOrderReferenceId') . '-inv');
                                    $this->context->cart->id_address_invoice = $address_invoice->id;
                                } else {
                                    $this->context->cart->id_address_invoice = $address_delivery->id;
                                    $address_invoice = $address_delivery;
                                }
                                $this->context->cart->save();
                                
                                if (self::$amz_payments->authorization_mode == 'fast_auth') {
                                    $authorization_reference_id = Tools::getValue('amazonOrderReferenceId');
                                    
                                    if (isset($this->context->cookie->setHadErrorNowWallet) && $this->context->cookie->setHadErrorNowWallet == 1) {
                                        $confirm_order_ref_req_model = new OffAmazonPaymentsService_Model_ConfirmOrderReferenceRequest();
                                        $confirm_order_ref_req_model->setAmazonOrderReferenceId(Tools::getValue('amazonOrderReferenceId'));
                                        $confirm_order_ref_req_model->setSellerId(self::$amz_payments->merchant_id);
                                        try {
                                            $this->service->confirmOrderReference($confirm_order_ref_req_model);
                                        } catch (OffAmazonPaymentsService_Exception $e) {
                                            echo 'ERROR: ' . $e->getMessage();
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
                                                die(Tools::jsonEncode(array(
                                                    'hasError' => true,
                                                    'errors' => array(
                                                        Tools::displayError(self::$amz_payments->l('Your selected payment method is currently not available. Please select another one.'))
                                                    )
                                                )));
                                            } else {
                                                die(Tools::jsonEncode(array(
                                                    'hasError' => true,
                                                    'redirection' => 'index.php?controller=order',
                                                    'errors' => array(
                                                        Tools::displayError(self::$amz_payments->l('Your selected payment method has been declined. Please chose another one.'))
                                                    )
                                                )));
                                            }
                                        }
                                        
                                        $amazon_authorization_id = $authorization_response_wrapper->getAuthorizeResult()
                                            ->getAuthorizationDetails()
                                            ->getAmazonAuthorizationId();
                                        if (self::$amz_payments->capture_mode == 'after_auth') {
                                            $amazon_capture_response = AmazonTransactions::capture(self::$amz_payments, $this->service, $amazon_authorization_id, $total, $currency_code);
                                            $amazon_capture_id = $amazon_capture_response->getCaptureResult()
                                                ->getCaptureDetails()
                                                ->getAmazonCaptureId();
                                            $amazon_capture_reference_id = $amazon_capture_response->getCaptureResult()
                                                ->getCaptureDetails()
                                                ->getCaptureReferenceId();
                                        }
                                    }
                                }
                                
                                if ($this->context->cart->secure_key == '') {
                                    $this->context->cart->secure_key = $customer->secure_key;
                                    $this->context->cart->save();
                                }
                                
                                $this->module->validateOrder((int) $this->context->cart->id, Configuration::get('PS_OS_PREPARATION'), $total, $this->module->displayName, null, array(), null, false, $customer->secure_key);
                                
                                if (self::$amz_payments->authorization_mode == 'after_checkout') {
                                    $authorization_reference_id = Tools::getValue('amazonOrderReferenceId');
                                    $authorization_response_wrapper = AmazonTransactions::authorize(self::$amz_payments, $this->service, $authorization_reference_id, $total, $currency_code);
                                    $amazon_authorization_id = @$authorization_response_wrapper->getAuthorizeResult()
                                        ->getAuthorizationDetails()
                                        ->getAmazonAuthorizationId();
                                    if (self::$amz_payments->capture_mode == 'after_auth' && isset($amazon_authorization_id) && $amazon_authorization_id !== false && $amazon_authorization_id != null) {
                                        $amazon_capture_response = AmazonTransactions::capture(self::$amz_payments, $this->service, $amazon_authorization_id, $total, $currency_code);
                                        $amazon_capture_id = $amazon_capture_response->getCaptureResult()
                                            ->getCaptureDetails()
                                            ->getAmazonCaptureId();
                                        $amazon_capture_reference_id = $amazon_capture_response->getCaptureResult()
                                            ->getCaptureDetails()
                                            ->getCaptureReferenceId();
                                    }
                                }
                                
                                self::$amz_payments->setAmazonReferenceIdForOrderId(Tools::getValue('amazonOrderReferenceId'), $this->module->currentOrder);
                                self::$amz_payments->setAmazonReferenceIdForOrderTransactionId(Tools::getValue('amazonOrderReferenceId'), $this->module->currentOrder);
                                if (isset($authorization_reference_id)) {
                                    self::$amz_payments->setAmazonAuthorizationReferenceIdForOrderId($authorization_reference_id, $this->module->currentOrder);
                                }
                                if (isset($amazon_authorization_id)) {
                                    self::$amz_payments->setAmazonAuthorizationIdForOrderId($amazon_authorization_id, $this->module->currentOrder);
                                }
                                if (isset($amazon_capture_reference_id)) {
                                    self::$amz_payments->setAmazonCaptureReferenceIdForOrderId($amazon_capture_reference_id, $this->module->currentOrder);
                                }
                                if (isset($amazon_capture_id)) {
                                    self::$amz_payments->setAmazonCaptureIdForOrderId($amazon_capture_id, $this->module->currentOrder);
                                }
                                
                                if (isset($this->context->cookie->amzSetStatusAuthorized)) {
                                    $tmpOrderRefs = unserialize($this->context->cookie->amzSetStatusAuthorized);
                                    if (is_array($tmpOrderRefs)) {
                                        foreach ($tmpOrderRefs as $order_ref) {
                                            AmazonTransactions::setOrderStatusAuthorized($order_ref);
                                        }
                                    }
                                    unset($this->context->cookie->amzSetStatusAuthorized);
                                }
                                if (isset($this->context->cookie->amzSetStatusCaptured)) {
                                    $tmpOrderRefs = unserialize($this->context->cookie->amzSetStatusCaptured);
                                    if (is_array($tmpOrderRefs)) {
                                        foreach ($tmpOrderRefs as $order_ref) {
                                            AmazonTransactions::setOrderStatusCaptured($order_ref);
                                        }
                                    }
                                    unset($this->context->cookie->amzSetStatusCaptured);
                                }
                                
                                if (Tools::getValue('connect_amz_account') == '1') {
                                    $this->context->cookie->amz_connect_order = $this->module->currentOrder;
                                    $this->context->cookie->amz_payments_address_id = $address_delivery->id;
                                    $this->context->cookie->amz_payments_invoice_address_id = $address_invoice->id;
                                    $login_redirect = $this->context->link->getModuleLink('amzpayments', 'process_login');
                                    $login_redirect = str_replace('http://', 'https://', $login_redirect);
                                    $login_redirect .= '?fromCheckout=1&access_token=' . $this->context->cookie->amz_access_token;
                                    die(Tools::jsonEncode(array(
                                        'orderSucceed' => true,
                                        'redirection' => $login_redirect
                                    )));
                                }
                                
                                if (! $customer->is_guest) {
                                    if (! AmzPayments::addressAlreadyExists($address_delivery, $customer)) {
                                        $address_delivery->id_customer = $customer->id;
                                        $address_delivery->save();
                                    }
                                    if (! AmzPayments::addressAlreadyExists($address_invoice, $customer)) {
                                        $address_invoice->id_customer = $customer->id;
                                        $address_invoice->save();
                                    }
                                } else {
                                    if ($registered_customer = Customer::AmazonPaymentsCustomerHelper($customer->email)) {
                                        if (! AmzPayments::addressAlreadyExists($address_delivery, $registered_customer)) {
                                            $address_delivery->id_customer = $registered_customer->id;
                                            $address_delivery->save();
                                        }
                                        if (! AmzPayments::addressAlreadyExists($address_invoice, $registered_customer)) {
                                            $address_invoice->id_customer = $registered_customer->id;
                                            $address_invoice->save();
                                        }
                                    }
                                }
                                die(Tools::jsonEncode(array(
                                    'orderSucceed' => true,
                                    'redirection' => __PS_BASE_URI__ . 'index.php?controller=order-confirmation&id_cart=' . (int) $this->context->cart->id . '&id_module=' . $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key
                                )));
                            }
                            die();
                        
                        default:
                            throw new PrestaShopException('Unknown method "' . Tools::getValue('method') . '"');
                    }
                } else
                    throw new PrestaShopException('Method is not defined');
            }
        } elseif (Tools::isSubmit('ajax'))
            throw new PrestaShopException('Method is not defined');
    }

    public function initContent()
    {
        $this->context->controller->addJS(self::$amz_payments->getPathUri() . 'views/js/amzpayments_checkout.js');
        
        $this->context->cart->id_address_delivery = null;
        $this->context->cart->id_address_invoice = null;
        
        parent::initContent();
        
        if (empty($this->context->cart->id_carrier)) {
            $checked = $this->context->cart->simulateCarrierSelectedOutput();
            $checked = ((int) Cart::desintifier($checked));
            $this->context->cart->id_carrier = $checked;
            $this->context->cart->update();
            CartRule::autoRemoveFromCart($this->context);
            CartRule::autoAddToCart($this->context);
        }
        
        $this->_assignSummaryInformations();
        $this->_assignWrappingAndTOS();
        
        $selected_country = (int) (Configuration::get('PS_COUNTRY_DEFAULT'));
        
        if (Configuration::get('PS_RESTRICT_DELIVERED_COUNTRIES')) {
            $countries = Carrier::getDeliveredCountries($this->context->language->id, true, true);
        } else {
            $countries = Country::getCountries($this->context->language->id, true);
        }
        
        $free_shipping = false;
        foreach ($this->context->cart->getCartRules() as $rule)
            if ($rule['free_shipping'] && ! $rule['carrier_restriction']) {
                $free_shipping = true;
                break;
            }
        
        $this->context->smarty->assign(array(
            'advanced_payment_api' => false,
            'free_shipping' => $free_shipping,
            'isGuest' => isset($this->context->cookie->is_guest) ? $this->context->cookie->is_guest : 0,
            'countries' => $countries,
            'sl_country' => isset($selected_country) ? $selected_country : 0,
            'PS_GUEST_CHECKOUT_ENABLED' => Configuration::get('PS_GUEST_CHECKOUT_ENABLED'),
            'errorCarrier' => Tools::displayError('You must choose a carrier.', false),
            'errorTOS' => Tools::displayError('You must accept the Terms of Service.', false),
            'isPaymentStep' => (bool) Tools::getIsset(Tools::getValue('isPaymentStep')) && Tools::getValue('isPaymentStep'),
            'genders' => Gender::getGenders(),
            'one_phone_at_least' => (int) Configuration::get('PS_ONE_PHONE_AT_LEAST'),
            'HOOK_CREATE_ACCOUNT_FORM' => Hook::exec('displayCustomerAccountForm'),
            'HOOK_CREATE_ACCOUNT_TOP' => Hook::exec('displayCustomerAccountFormTop')
        ));
        $years = Tools::dateYears();
        $months = Tools::dateMonths();
        $days = Tools::dateDays();
        $this->context->smarty->assign(array(
            'years' => $years,
            'months' => $months,
            'days' => $days
        ));
        
        $this->_assignCarrier();
        Tools::safePostVars();
        
        $blocknewsletter = Module::getInstanceByName('blocknewsletter');
        $this->context->smarty->assign('newsletter', (bool) $blocknewsletter && $blocknewsletter->active);
        
        $this->context->smarty->assign(array(
            'amz_module_path' => self::$amz_payments->getPathUri(),
            'amz_session' => Tools::getValue('session') ? Tools::getValue('session') : $this->context->cookie->amazon_id,
            'sellerID' => Configuration::get('MERCHANT_ID'),
            'sandboxMode' => false
        ));
        
        if (isset($this->context->cookie->amz_access_token) && $this->context->cookie->amz_access_token != '' && ! AmazonPaymentsCustomerHelper::customerHasAmazonCustomerId($this->context->cookie->id_customer)) {
            $this->context->smarty->assign('show_amazon_account_creation_allowed', true);
        } else {
            $this->context->smarty->assign('show_amazon_account_creation_allowed', false);
        }
        
        $this->context->smarty->assign('preselect_create_account', Configuration::get('PRESELECT_CREATE_ACCOUNT') == 1);
        $this->context->smarty->assign('force_account_creation', Configuration::get('FORCE_ACCOUNT_CREATION') == 1);
        
        $this->setTemplate('amzpayments.tpl');
    }

    public function setMedia()
    {
        parent::setMedia();
        
        if ($this->context->getMobileDevice() === false)
            $this->addCSS(_THEME_CSS_DIR_ . 'addresses.css');
        
        $this->addJS(_THEME_JS_DIR_ . 'tools.js');
        if ((Configuration::get('PS_ORDER_PROCESS_TYPE') == 0 && Tools::getValue('step') == 1) || Configuration::get('PS_ORDER_PROCESS_TYPE') == 1) {
            $this->addJS(_THEME_JS_DIR_ . 'order-address.js');
        }
        $this->addJqueryPlugin('fancybox');
        if ((int) (Configuration::get('PS_BLOCK_CART_AJAX')) || Configuration::get('PS_ORDER_PROCESS_TYPE') == 1 || Tools::getValue('step') == 2) {
            $this->addJqueryPlugin('typewatch');
            $this->addJS(_THEME_JS_DIR_ . 'cart-summary.js');
        }
        
        if ($this->context->getMobileDevice() == false) {
            $this->addCSS(_THEME_CSS_DIR_ . 'order-opc.css');
            $this->addJqueryPlugin('scrollTo');
        } else {
            $this->addJS(_THEME_MOBILE_JS_DIR_ . 'opc.js');
        }
    }

    protected function _assignSummaryInformations()
    {
        $summary = $this->context->cart->getSummaryDetails();
        $customized_datas = Product::getAllCustomizedDatas($this->context->cart->id);
        
        if ($customized_datas) {
            foreach ($summary['products'] as &$product_update) {
                $product_id = (int) (isset($product_update['id_product']) ? $product_update['id_product'] : $product_update['product_id']);
                $product_attribute_id = (int) (isset($product_update['id_product_attribute']) ? $product_update['id_product_attribute'] : $product_update['product_attribute_id']);
                
                if (isset($customized_datas[$product_id][$product_attribute_id]))
                    $product_update['tax_rate'] = Tax::getProductTaxRate($product_id, $this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
            }
            
            Product::addCustomizationPrice($summary['products'], $customized_datas);
        }
        
        $cart_product_context = Context::getContext()->cloneContext();
        foreach ($summary['products'] as $key => &$product) {
            $product['quantity'] = $product['cart_quantity'];
            
            $null = null;
            
            if ($cart_product_context->shop->id != $product['id_shop']) {
                $cart_product_context->shop = new Shop((int) $product['id_shop']);
            }
            $product['price_without_specific_price'] = Product::getPriceStatic($product['id_product'], ! Product::getTaxCalculationMethod(), $product['id_product_attribute'], 2, null, false, false, 1, false, null, null, null, $null, true, true, $cart_product_context);
            
            if (Product::getTaxCalculationMethod()) {
                $product['is_discounted'] = $product['price_without_specific_price'] != $product['price'];
            } else {
                $product['is_discounted'] = $product['price_without_specific_price'] != $product['price_wt'];
            }
        }
        
        $available_cart_rules = CartRule::getCustomerCartRules($this->context->language->id, (isset($this->context->customer->id) ? $this->context->customer->id : 0), true, true, true, $this->context->cart);
        $cart_cart_rules = $this->context->cart->getCartRules();
        foreach ($available_cart_rules as $key => $available_cart_rule) {
            if (! $available_cart_rule['highlight'] || strpos($available_cart_rule['code'], 'BO_ORDER_') === 0) {
                unset($available_cart_rules[$key]);
                continue;
            }
            foreach ($cart_cart_rules as $cart_cart_rule)
                if ($available_cart_rule['id_cart_rule'] == $cart_cart_rule['id_cart_rule']) {
                    unset($available_cart_rules[$key]);
                    continue 2;
                }
        }
        
        $show_option_allow_separate_package = (! $this->context->cart->isAllProductsInStock(true) && Configuration::get('PS_SHIP_WHEN_AVAILABLE'));
        
        $this->context->smarty->assign($summary);
        $this->context->smarty->assign(array(
            'token_cart' => Tools::getToken(false),
            'isLogged' => $this->isLogged,
            'isVirtualCart' => $this->context->cart->isVirtualCart(),
            'productNumber' => $this->context->cart->nbProducts(),
            'voucherAllowed' => CartRule::isFeatureActive(),
            'shippingCost' => $this->context->cart->getOrderTotal(true, Cart::ONLY_SHIPPING),
            'shippingCostTaxExc' => $this->context->cart->getOrderTotal(false, Cart::ONLY_SHIPPING),
            'customizedDatas' => $customized_datas,
            'CUSTOMIZE_FILE' => Product::CUSTOMIZE_FILE,
            'CUSTOMIZE_TEXTFIELD' => Product::CUSTOMIZE_TEXTFIELD,
            'lastProductAdded' => $this->context->cart->getLastProduct(),
            'displayVouchers' => $available_cart_rules,
            'currencySign' => $this->context->currency->sign,
            'currencyRate' => $this->context->currency->conversion_rate,
            'currencyFormat' => $this->context->currency->format,
            'currencyBlank' => $this->context->currency->blank,
            'show_option_allow_separate_package' => $show_option_allow_separate_package,
            'smallSize' => Image::getSize(ImageType::getFormatedName('small'))
        ));
        
        $this->context->smarty->assign(array(
            'HOOK_SHOPPING_CART' => Hook::exec('displayShoppingCartFooter', $summary),
            'HOOK_SHOPPING_CART_EXTRA' => Hook::exec('displayShoppingCart', $summary)
        ));
    }

    protected function _assignCarrier()
    {
        $carriers = $this->context->cart->simulateCarriersOutput();
        $checked = $this->context->cart->simulateCarrierSelectedOutput();
        $delivery_option_list = $this->context->cart->getDeliveryOptionList();
        $this->setDefaultCarrierSelection($delivery_option_list);
        
        $this->context->smarty->assign(array(
            'address_collection' => $this->context->cart->getAddressCollection(),
            'delivery_option_list' => $delivery_option_list,
            'carriers' => $carriers,
            'checked' => $checked,
            'delivery_option' => $this->context->cart->getDeliveryOption(null, false)
        ));
        
        $vars = array(
            'HOOK_BEFORECARRIER' => Hook::exec('displayBeforeCarrier', array(
                'carriers' => $carriers,
                'checked' => $checked,
                'delivery_option_list' => $delivery_option_list,
                'delivery_option' => $this->context->cart->getDeliveryOption(null, false)
            ))
        );
        
        Cart::addExtraCarriers($vars);
        
        $this->context->smarty->assign($vars);
    }

    protected function _assignWrappingAndTOS()
    {
        $wrapping_fees = $this->context->cart->getGiftWrappingPrice(false);
        $wrapping_fees_tax_inc = $wrapping_fees = $this->context->cart->getGiftWrappingPrice();
        
        $cms = new CMS(Configuration::get('PS_CONDITIONS_CMS_ID'), $this->context->language->id);
        $this->link_conditions = $this->context->link->getCMSLink($cms, $cms->link_rewrite, (bool) Configuration::get('PS_SSL_ENABLED'));
        if (! strpos($this->link_conditions, '?')) {
            $this->link_conditions .= '?content_only=1';
        } else {
            $this->link_conditions .= '&content_only=1';
        }
        
        $free_shipping = false;
        foreach ($this->context->cart->getCartRules() as $rule) {
            if ($rule['free_shipping'] && ! $rule['carrier_restriction']) {
                $free_shipping = true;
                break;
            }
        }
        $this->context->smarty->assign(array(
            'free_shipping' => $free_shipping,
            'checkedTOS' => (int) ($this->context->cookie->checkedTOS),
            'recyclablePackAllowed' => (int) (Configuration::get('PS_RECYCLABLE_PACK')),
            'giftAllowed' => (int) (Configuration::get('PS_GIFT_WRAPPING')),
            'cms_id' => (int) (Configuration::get('PS_CONDITIONS_CMS_ID')),
            'conditions' => (int) (Configuration::get('PS_CONDITIONS')),
            'link_conditions' => $this->link_conditions,
            'recyclable' => (int) ($this->context->cart->recyclable),
            'delivery_option_list' => $this->context->cart->getDeliveryOptionList(),
            'carriers' => $this->context->cart->simulateCarriersOutput(),
            'checked' => $this->context->cart->simulateCarrierSelectedOutput(),
            'address_collection' => $this->context->cart->getAddressCollection(),
            'delivery_option' => $this->context->cart->getDeliveryOption(null, false),
            'gift_wrapping_price' => (float) $wrapping_fees,
            'total_wrapping_cost' => Tools::convertPrice($wrapping_fees_tax_inc, $this->context->currency),
            'total_wrapping_tax_exc_cost' => Tools::convertPrice($wrapping_fees, $this->context->currency)
        ));
    }

    /**
     * Set id_carrier to 0 (no shipping price)
     */
    protected function setNoCarrier()
    {
        $this->context->cart->setDeliveryOption(null);
        $this->context->cart->update();
    }

    /**
     * Decides what the default carrier is and update the cart with it
     *
     * @param array $carriers            
     *
     * @deprecated since 1.5.0
     *            
     * @return number the id of the default carrier
     */
    protected function setDefaultCarrierSelection($carriers)
    {
        unset($carriers);
        if (! $this->context->cart->getDeliveryOption(null, true)) {
            $this->context->cart->setDeliveryOption($this->context->cart->getDeliveryOption());
        }
    }

    /**
     * Decides what the default carrier is and update the cart with it
     *
     * @param array $carriers            
     *
     * @deprecated since 1.5.0
     *            
     * @return number the id of the default carrier
     */
    protected function _setDefaultCarrierSelection($carriers)
    {
        $this->context->cart->id_carrier = Carrier::getDefaultCarrierSelection($carriers, (int) $this->context->cart->id_carrier);
        
        if ($this->context->cart->update()) {
            return $this->context->cart->id_carrier;
        }
        return 0;
    }

    protected function _processCarrier()
    {
        $this->context->cart->recyclable = (int) (Tools::getValue('recyclable'));
        $this->context->cart->gift = (int) (Tools::getValue('gift'));
        if ((int) (Tools::getValue('gift'))) {
            if (! Validate::isMessage(Tools::getValue('gift_message'))) {
                $this->errors[] = Tools::displayError('Invalid gift message.');
            } else {
                $this->context->cart->gift_message = strip_tags(Tools::getValue('gift_message'));
            }
        }
        
        if (isset($this->context->customer->id) && $this->context->customer->id) {
            $address = new Address((int) ($this->context->cart->id_address_delivery));
            if (! (Address::getZoneById($address->id))) {
                $this->errors[] = Tools::displayError('No zone matches your address.');
            }
        } else {
            Country::getIdZone((int) Configuration::get('PS_COUNTRY_DEFAULT'));
        }
        
        if (Tools::getIsset('delivery_option')) {
            if ($this->validateDeliveryOption(Tools::getValue('delivery_option'))) {
                $this->context->cart->setDeliveryOption(Tools::getValue('delivery_option'));
            }
        } elseif (Tools::getIsset('id_carrier')) {
            $delivery_option_list = $this->context->cart->getDeliveryOptionList();
            if (count($delivery_option_list) == 1) {
                reset($delivery_option_list);
                $key = Cart::desintifier(Tools::getValue('id_carrier'));
                foreach ($delivery_option_list as $id_address => $options)
                    if (isset($options[$key])) {
                        $this->context->cart->id_carrier = (int) Tools::getValue('id_carrier');
                        $this->context->cart->setDeliveryOption(array(
                            $id_address => $key
                        ));
                        if (isset($this->context->cookie->id_country)) {
                            unset($this->context->cookie->id_country);
                        }
                        if (isset($this->context->cookie->id_state)) {
                            unset($this->context->cookie->id_state);
                        }
                    }
            }
        }
        
        Hook::exec('actionCarrierProcess', array(
            'cart' => $this->context->cart
        ));
        
        if (! $this->context->cart->update()) {
            return false;
        }
        
        CartRule::autoRemoveFromCart($this->context);
        CartRule::autoAddToCart($this->context);
        
        return true;
    }

    protected function validateDeliveryOption($delivery_option)
    {
        if (! is_array($delivery_option)) {
            return false;
        }
        
        foreach ($delivery_option as $option) {
            if (! preg_match('/(\d+,)?\d+/', $option)) {
                return false;
            }
        }
        
        return true;
    }

    protected function _getPaymentMethods()
    {
        if (! $this->isLogged) {
            return '<p class="warning">' . Tools::displayError('Please sign in to see payment methods.') . '</p>';
        }
        if ($this->context->cart->OrderExists()) {
            return '<p class="warning">' . Tools::displayError('Error: This order has already been validated.') . '</p>';
        }
        if (! $this->context->cart->id_customer || ! Customer::customerIdExistsStatic($this->context->cart->id_customer) || Customer::isBanned($this->context->cart->id_customer)) {
            return '<p class="warning">' . Tools::displayError('Error: No customer.') . '</p>';
        }
        $address_delivery = new Address($this->context->cart->id_address_delivery);
        $address_invoice = ($this->context->cart->id_address_delivery == $this->context->cart->id_address_invoice ? $address_delivery : new Address($this->context->cart->id_address_invoice));
        if (! $this->context->cart->id_address_delivery || ! $this->context->cart->id_address_invoice || ! Validate::isLoadedObject($address_delivery) || ! Validate::isLoadedObject($address_invoice) || $address_invoice->deleted || $address_delivery->deleted) {
            return '<p class="warning">' . Tools::displayError('Error: Please select an address.') . '</p>';
        }
        if (count($this->context->cart->getDeliveryOptionList()) == 0 && ! $this->context->cart->isVirtualCart()) {
            if ($this->context->cart->isMultiAddressDelivery()) {
                return '<p class="warning">' . Tools::displayError('Error: None of your chosen carriers deliver to some of  the addresses you\'ve selected.') . '</p>';
            } else {
                return '<p class="warning">' . Tools::displayError('Error: None of your chosen carriers deliver to the address you\'ve selected.') . '</p>';
            }
        }
        if (! $this->context->cart->getDeliveryOption(null, false) && ! $this->context->cart->isVirtualCart()) {
            return '<p class="warning">' . Tools::displayError('Error: Please choose a carrier.') . '</p>';
        }
        if (! $this->context->cart->id_currency) {
            return '<p class="warning">' . Tools::displayError('Error: No currency has been selected.') . '</p>';
        }
        if (! $this->context->cookie->checkedTOS && Configuration::get('PS_CONDITIONS')) {
            return '<p class="warning">' . Tools::displayError('Please accept the Terms of Service.') . '</p>';
        }
        
        /* If some products have disappear */
        if (! $this->context->cart->checkQuantities()) {
            return '<p class="warning">' . Tools::displayError('An item in your cart is no longer available. You cannot proceed with your order.') . '</p>';
        }
        
        /* Check minimal amount */
        $currency = Currency::getCurrency((int) $this->context->cart->id_currency);
        
        $minimal_purchase = Tools::convertPrice((float) Configuration::get('PS_PURCHASE_MINIMUM'), $currency);
        if ($this->context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS) < $minimal_purchase) {
            return '<p class="warning">' . sprintf(Tools::displayError('A minimum purchase total of %1s (tax excl.) is required in order to validate your order, current purchase total is %2s (tax excl.).'), Tools::displayPrice($minimal_purchase, $currency), Tools::displayPrice($this->context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS), $currency)) . '</p>';
        }
        
        /* Bypass payment step if total is 0 */
        if ($this->context->cart->getOrderTotal() <= 0) {
            return '<p class="center"><input type="button" class="exclusive_large" name="confirmOrder" id="confirmOrder" value="' . Tools::displayError('I confirm my order.') . '" onclick="confirmFreeOrder();" /></p>';
        }
        
        $return = Hook::exec('displayPayment');
        if (! $return) {
            return '<p class="warning">' . Tools::displayError('No payment method is available for use at this time. ') . '</p>';
        }
        return $return;
    }

    protected function _getCarrierList()
    {
        $address_delivery = new Address($this->context->cart->id_address_delivery);
        
        $cms = new CMS(Configuration::get('PS_CONDITIONS_CMS_ID'), $this->context->language->id);
        $link_conditions = $this->context->link->getCMSLink($cms, $cms->link_rewrite);
        if (! strpos($link_conditions, '?')) {
            $link_conditions .= '?content_only=1';
        } else {
            $link_conditions .= '&content_only=1';
        }
        
        $carriers = $this->context->cart->simulateCarriersOutput();
        $delivery_option = $this->context->cart->getDeliveryOption(null, false, false);
        
        $wrapping_fees = $this->context->cart->getGiftWrappingPrice(false);
        $wrapping_fees_tax_inc = $wrapping_fees = $this->context->cart->getGiftWrappingPrice();
        $old_message = Message::getMessageByCartId((int) ($this->context->cart->id));
        
        $free_shipping = false;
        foreach ($this->context->cart->getCartRules() as $rule) {
            if ($rule['free_shipping'] && ! $rule['carrier_restriction']) {
                $free_shipping = true;
                break;
            }
        }
        
        $this->context->smarty->assign('isVirtualCart', $this->context->cart->isVirtualCart());
        
        $delivery_option_list = $this->context->cart->getDeliveryOptionList(null, true);
        foreach ($delivery_option_list as $key1 => $del_opts) {
            if ($key1 != $this->context->cart->id_address_delivery) {
                unset($delivery_option_list[$key1]);
            } else {
                foreach (array_keys($del_opts) as $key) {
                    if ($this->shippingNotAllowed((int) $key)) {
                        unset($delivery_option_list[$key1][$key]);
                    }
                }
            }
        }
        
        $vars = array(
            'free_shipping' => $free_shipping,
            'advanced_payment_api' => false,
            'checkedTOS' => (int) ($this->context->cookie->checkedTOS),
            'recyclablePackAllowed' => (int) (Configuration::get('PS_RECYCLABLE_PACK')),
            'giftAllowed' => (int) (Configuration::get('PS_GIFT_WRAPPING')),
            'cms_id' => (int) (Configuration::get('PS_CONDITIONS_CMS_ID')),
            'conditions' => (int) (Configuration::get('PS_CONDITIONS')),
            'link_conditions' => $link_conditions,
            'recyclable' => (int) ($this->context->cart->recyclable),
            'gift_wrapping_price' => (float) $wrapping_fees,
            'total_wrapping_cost' => Tools::convertPrice($wrapping_fees_tax_inc, $this->context->currency),
            'total_wrapping_tax_exc_cost' => Tools::convertPrice($wrapping_fees, $this->context->currency),
            'delivery_option_list' => $delivery_option_list,
            'carriers' => $carriers,
            'checked' => $this->context->cart->simulateCarrierSelectedOutput(),
            'delivery_option' => $delivery_option,
            'address_collection' => $this->context->cart->getAddressCollection(),
            'opc' => true,
            'oldMessage' => isset($old_message['message']) ? $old_message['message'] : '',
            'HOOK_BEFORECARRIER' => Hook::exec('displayBeforeCarrier', array(
                'carriers' => $carriers,
                'delivery_option_list' => $this->context->cart->getDeliveryOptionList(),
                'delivery_option' => $delivery_option
            ))
        );
        
        Cart::addExtraCarriers($vars);
        
        $this->context->smarty->assign($vars);
        
        if (! Address::isCountryActiveById((int) ($this->context->cart->id_address_delivery)) && $this->context->cart->id_address_delivery != 0) {
            $this->errors[] = Tools::displayError('This address is not in a valid area.');
        } elseif ((! Validate::isLoadedObject($address_delivery) || $address_delivery->deleted) && $this->context->cart->id_address_delivery != 0) {
            $this->errors[] = Tools::displayError('This address is invalid.');
        } else {
            $result = array(
                'HOOK_BEFORECARRIER' => Hook::exec('displayBeforeCarrier', array(
                    'carriers' => $carriers,
                    'delivery_option_list' => $this->context->cart->getDeliveryOptionList(),
                    'delivery_option' => $this->context->cart->getDeliveryOption(null, true)
                )),
                'carrier_block' => $this->context->smarty->fetch(_PS_THEME_DIR_ . 'order-carrier.tpl')
            );
            
            Cart::addExtraCarriers($result);
            return $result;
        }
        if (count($this->errors)) {
            return array(
                'hasError' => true,
                'errors' => $this->errors,
                'carrier_block' => $this->context->smarty->fetch(_PS_THEME_DIR_ . 'order-carrier.tpl')
            );
        }
    }

    protected function _updateMessage($message_content)
    {
        if ($message_content) {
            if (! Validate::isMessage($message_content)) {
                $this->errors[] = Tools::displayError('Invalid message');
            } else {
                if ($old_message = Message::getMessageByCartId((int) ($this->context->cart->id))) {
                    $message = new Message((int) ($old_message['id_message']));
                    $message->message = $message_content;
                    $message->update();
                } else {
                    $message = new Message();
                    $message->message = $message_content;
                    $message->id_cart = (int) ($this->context->cart->id);
                    $message->id_customer = (int) ($this->context->cart->id_customer);
                    $message->add();
                }
            }
        } else {
            if ($old_message = Message::getMessageByCartId($this->context->cart->id)) {
                $message = new Message($old_message['id_message']);
                $message->delete();
            }
        }
        return true;
    }

    protected function shippingNotAllowed($carrier_id)
    {
        if (self::$amz_payments->shippings_not_allowed != '') {
            $blocked_shipping_ids = explode(',', self::$amz_payments->shippings_not_allowed);
            foreach ($blocked_shipping_ids as $k => $v) {
                $blocked_shipping_ids[$k] = (int) $v;
            }
            if (in_array($carrier_id, $blocked_shipping_ids)) {
                return true;
            }
        }
    }

    protected function getFormatedSummaryDetail()
    {
        $result = array(
            'summary' => $this->context->cart->getSummaryDetails(),
            'customizedDatas' => Product::getAllCustomizedDatas($this->context->cart->id, null, true)
        );
        
        foreach ($result['summary']['products'] as &$product) {
            $product['quantity_without_customization'] = $product['quantity'];
            if ($result['customizedDatas']) {
                if (isset($result['customizedDatas'][(int) $product['id_product']][(int) $product['id_product_attribute']])) {
                    foreach ($result['customizedDatas'][(int) $product['id_product']][(int) $product['id_product_attribute']] as $addresses) {
                        foreach ($addresses as $customization) {
                            $product['quantity_without_customization'] -= (int) $customization['quantity'];
                        }
                    }
                }
            }
        }
        
        if ($result['customizedDatas']) {
            Product::addCustomizationPrice($result['summary']['products'], $result['customizedDatas']);
        }
        return $result;
    }
}
