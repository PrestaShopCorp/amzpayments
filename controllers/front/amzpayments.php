<?php
/**
 * 2013-2016 Amazon Advanced Payment APIs Modul
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
 *  @copyright 2013-2016 patworx multimedia GmbH
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

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
        if (!$this->module->active) {
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
        
        if (self::$amz_payments->order_process_type == 'standard' && Tools::getValue('method') != 'setsession') {
            $params = array();
            if (Tools::getValue('amazon_id')) {
                $params['session'] = Tools::getValue('amazon_id');
            }
            Tools::redirect($this->context->link->getModuleLink('amzpayments', 'addresswallet', $params));
        }
        
        $this->nbProducts = $this->context->cart->nbProducts();
        
        if (Configuration::get('PS_CATALOG_MODE')) {
            $this->errors[] = Tools::displayError('This store has not accepted your new order.');
        }
        
        $this->context->smarty->assign('back', Tools::safeOutput(Tools::getValue('back')));
        
        if ($this->nbProducts) {
            $this->context->smarty->assign('virtual_cart', $this->context->cart->isVirtualCart());
        }
        unset($this->context->cookie->setHadErrorNowWallet);
        
        if ($this->context->cart->nbProducts()) {
            if (Tools::isSubmit('ajax')) {
                if (Tools::isSubmit('method')) {
                    switch (Tools::getValue('method')) {
                        case 'setsession':
                            $this->context->cookie->amazon_id = Tools::getValue('amazon_id');
                            $this->context->cookie->amz_access_token = AmzPayments::prepareCookieValueForPrestaShopUse(Tools::getValue('access_token'));
                            $this->context->cookie->amz_access_token_set_time = time();
                            
                            if (!$this->context->customer->isLogged() && self::$amz_payments->lpa_mode != 'pay') {
                                $d = self::$amz_payments->requestTokenInfo(AmzPayments::prepareCookieValueForAmazonPaymentsUse($this->context->cookie->amz_access_token));
                                                                
                                if ($d->aud != self::$amz_payments->client_id) {
                                    die('error');
                                }
                                
                                $d = self::$amz_payments->requestProfile(AmzPayments::prepareCookieValueForAmazonPaymentsUse($this->context->cookie->amz_access_token));
                                
                                $customer_userid = $d->user_id;
                                $customer_name = $d->name;
                                $customer_email = $d->email;
                                
                                if ($customers_local_id = AmazonPaymentsCustomerHelper::findByAmazonCustomerId($customer_userid)) {
                                    Hook::exec('actionBeforeAuthentication');
                                    $customer = new Customer();
                                    $authentication = AmazonPaymentsCustomerHelper::getByCustomerID($customers_local_id, true, $customer);
                                    
                                    if (isset($authentication->active) && !$authentication->active) {
                                        exit();
                                    } elseif (!$authentication || !$customer->id) {
                                        exit();
                                    } else {
                                        $this->context->updateCustomer($customer);
                                        CartRule::autoRemoveFromCart($this->context);
                                        CartRule::autoAddToCart($this->context);
                                    }
                                }
                            }
                            
                            exit();
                        
                        case 'updateCarrierAndGetPayments':
                            if ((Tools::isSubmit('delivery_option') || Tools::isSubmit('id_carrier')) && Tools::isSubmit('recyclable') && Tools::isSubmit('gift') && Tools::isSubmit('gift_message')) {
                                if ($this->_processCarrier()) {
                                    $carriers = $this->context->cart->simulateCarriersOutput();
                                    $return = array_merge(array(
                                        'carrier_data' => $this->_getCarrierList(),
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
                            
                        case 'addDiscount':
                            if (!($code = trim(Tools::getValue('coupon')))) {
                                $this->errors[] = $this->trans('You must enter a voucher code.', array(), 'Shop.Notifications.Error');
                            } elseif (!Validate::isCleanHtml($code)) {
                                $this->errors[] = $this->trans('The voucher code is invalid.', array(), 'Shop.Notifications.Error');
                            } else {
                                if (($cartRule = new CartRule(CartRule::getIdByCode($code))) && Validate::isLoadedObject($cartRule)) {
                                    if ($error = $cartRule->checkValidity($this->context, false, true)) {
                                        $this->errors[] = $error;
                                    } else {
                                        $this->context->cart->addCartRule($cartRule->id);
                                    }
                                } else {
                                    $this->errors[] = Tools::displayError('This voucher does not exists.');
                                }
                            }
                            if (count($this->errors)) {
                                die('{"hasError" : true, "errors" : ["' . implode('\',\'', $this->errors) . '"]}');
                            } else {
                                $return = array_merge(array(
                                    'success' => true,
                                    'coupon_block' => $this->getFormatedCouponBlock(),
                                ), $this->getFormatedSummaryDetail());
                                die(Tools::jsonEncode($return));
                            }
                            exit();
                            break;
                            
                        case 'removeDiscount':
                            if (($id_cart_rule = (int)Tools::getValue('coupon')) && Validate::isUnsignedId($id_cart_rule)) {
                                $this->context->cart->removeCartRule($id_cart_rule);
                                CartRule::autoAddToCart($this->context);
                            }
                            $return = array_merge(array(
                                'success' => true,
                                'coupon_block' => $this->getFormatedCouponBlock(),
                            ), $this->getFormatedSummaryDetail());
                            die(Tools::jsonEncode($return));
                            break;
                                                
                        case 'updateAddressesSelected':
                            $service = self::$amz_payments->getService();
                            $requestParameters = array();
                            $requestParameters['amazon_order_reference_id'] = Tools::getValue('amazonOrderReferenceId');
                            $requestParameters['merchant_id'] = self::$amz_payments->merchant_id;
                            if (isset($this->context->cookie->amz_access_token)) {
                                $requestParameters['address_consent_token'] = AmzPayments::prepareCookieValueForAmazonPaymentsUse($this->context->cookie->amz_access_token);
                            }
                            try {
                                $response = $service->getOrderReferenceDetails($requestParameters);
                                $response = $response->toArray();
                            } catch (Exception $e) {
                                self::$amz_payments->exceptionLog($e);
                            }
                            
                            if (!isset($response['GetOrderReferenceDetailsResult'])) {
                                self::$amz_payments->exceptionLog($response);
                            }
                                                        
                            $physical_destination = $response['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['Destination']['PhysicalDestination'];
                            
                            $names_array = array('amzFirstname', 'amzLastname');
                            $names_array_tmp = explode(' ', (string) (string) AmzPayments::getFromArray($physical_destination, 'Name'), 2);
                            if ($names_array_tmp) {
                                $names_array = AmzPayments::prepareNamesArray($names_array_tmp);
                            }
                                                        
                            $iso_code = (string) AmzPayments::getFromArray($physical_destination, 'CountryCode');
                            $city = (string) AmzPayments::getFromArray($physical_destination, 'City');
                            $postcode = (string) AmzPayments::getFromArray($physical_destination, 'PostalCode');
                            $state = (string) AmzPayments::getFromArray($physical_destination, 'State');
                            
                            $address_delivery = AmazonPaymentsAddressHelper::findByAmazonOrderReferenceIdOrNew(Tools::getValue('amazonOrderReferenceId'), false, $physical_destination);
                            $address_delivery->id_country = Country::getByIso($iso_code);
                            $address_delivery->alias = 'Amazon Payments Delivery';
                            $address_delivery->lastname = $names_array[1];
                            $address_delivery->firstname = $names_array[0];
                            
                            $s_company_name = '';
                            if ((string) AmzPayments::getFromArray($physical_destination, 'AddressLine3') != '') {
                                $s_street = Tools::substr(AmzPayments::getFromArray($physical_destination, 'AddressLine3'), 0, Tools::strrpos(AmzPayments::getFromArray($physical_destination, 'AddressLine3'), ' '));
                                $s_street_nr = Tools::substr(AmzPayments::getFromArray($physical_destination, 'AddressLine3'), Tools::strrpos(AmzPayments::getFromArray($physical_destination, 'AddressLine3'), ' ') + 1);
                                $s_company_name = trim(AmzPayments::getFromArray($physical_destination, 'AddressLine1') . AmzPayments::getFromArray($physical_destination, 'AddressLine2'));
                            } else {
                                if ((string) AmzPayments::getFromArray($physical_destination, 'AddressLine2') != '') {
                                    $s_street = Tools::substr(AmzPayments::getFromArray($physical_destination, 'AddressLine2'), 0, Tools::strrpos(AmzPayments::getFromArray($physical_destination, 'AddressLine2'), ' '));
                                    $s_street_nr = Tools::substr(AmzPayments::getFromArray($physical_destination, 'AddressLine2'), Tools::strrpos(AmzPayments::getFromArray($physical_destination, 'AddressLine2'), ' ') + 1);
                                    $s_company_name = trim(AmzPayments::getFromArray($physical_destination, 'AddressLine1'));
                                } else {
                                    $s_street = Tools::substr(AmzPayments::getFromArray($physical_destination, 'AddressLine1'), 0, Tools::strrpos(AmzPayments::getFromArray($physical_destination, 'AddressLine1'), ' '));
                                    $s_street_nr = Tools::substr(AmzPayments::getFromArray($physical_destination, 'AddressLine1'), Tools::strrpos(AmzPayments::getFromArray($physical_destination, 'AddressLine1'), ' ') + 1);
                                }
                            }
                            
                            $phone = '0000000000';
                            if ((string) AmzPayments::getFromArray($physical_destination, 'Phone') != '' && Validate::isPhoneNumber((string) AmzPayments::getFromArray($physical_destination, 'Phone'))) {
                                $phone = (string) AmzPayments::getFromArray($physical_destination, 'Phone');
                            }
                            $address_delivery->phone = $phone;
                            
                            if (in_array(Tools::strtolower((string)AmzPayments::getFromArray($physical_destination, 'CountryCode')), array('de', 'at', 'uk'))) {
                                if ($s_company_name != '') {
                                    $address_delivery->company = $s_company_name;
                                }
                                $address_delivery->address1 = (string) $s_street . ' ' . (string) $s_street_nr;
                            } else {
                                $address_delivery->address1 = (string) AmzPayments::getFromArray($physical_destination, 'AddressLine1');
                                if (trim($address_delivery->address1) == '') {
                                    $address_delivery->address1 = (string) AmzPayments::getFromArray($physical_destination, 'AddressLine2');
                                } else {
                                    if (trim((string)AmzPayments::getFromArray($physical_destination, 'AddressLine2')) != '') {
                                        $address_delivery->address2 = (string) AmzPayments::getFromArray($physical_destination, 'AddressLine2');
                                    }
                                }
                                if (trim((string)AmzPayments::getFromArray($physical_destination, 'AddressLine3')) != '') {
                                    $address_delivery->address2.= ' ' . (string) AmzPayments::getFromArray($physical_destination, 'AddressLine3');
                                }
                            }
                            
                            $address_delivery->city = $city;
                            $address_delivery->postcode = $postcode;
                            $address_delivery->id_state = 0;
                            if ($state != '') {
                                $state_id = State::getIdByIso($state, Country::getByIso($iso_code));
                                if (!$state_id) {
                                    $state_id = State::getIdByName($state);
                                }
                                if ($state_id) {
                                    $address_delivery->id_state = $state_id;
                                }
                            }
                            $address_delivery = AmzPayments::prepareAddressLines($address_delivery);
                            $address_delivery->id_customer = (int) $this->context->cart->id_customer;
                            
                            if (Tools::getValue('add') && is_array(Tools::getValue('add'))) {
                                $address_delivery = AmazonPaymentsAddressHelper::addAdditionalValues($address_delivery, Tools::getValue('add'));
                            }
                            
                            $fields_to_set = array();
                            if ($address_delivery->id_state > 0 && !AmazonPaymentsAddressHelper::stateBelongsToCountry($address_delivery->id_state, (int)Country::getByIso($iso_code))) {
                                $address_delivery->id_state = 0;
                            }
                            if ($address_delivery->id_state == 0) {
                                $country = new Country((int)Country::getByIso($iso_code));
                                if ($country->contains_states) {
                                    if (sizeof(State::getStatesByIdCountry((int)Country::getByIso($iso_code))) > 0) {
                                        $address_delivery->id_state = -1;
                                    }
                                }
                            }
                            $htmlstr = '';
                            try {
                                $address_delivery->save();
                                AmazonPaymentsAddressHelper::saveAddressAmazonReference($address_delivery, Tools::getValue('amazonOrderReferenceId'), $physical_destination);
                                
                                $this->context->smarty->assign('isVirtualCart', $this->context->cart->isVirtualCart());
                                
                                $old_delivery_address_id = $this->context->cart->id_address_delivery;
                                $this->context->cart->id_address_delivery = $address_delivery->id;
                                $this->context->cart->id_address_invoice = $address_delivery->id;
                                
                                $this->context->cart->setNoMultishipping();
                                
                                $this->context->cart->updateAddressId($old_delivery_address_id, $address_delivery->id);
                                
                                if (!$this->context->cart->update()) {
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
                                            if (!in_array($rule['id_cart_rule'], $rule_list)) {
                                                $this->ajax_refresh = true;
                                                break;
                                            }
                                        }
                                    }
                                }
                                
                                if (!$this->context->cart->isMultiAddressDelivery()) {
                                    $this->context->cart->setNoMultishipping();
                                }
                            } catch (Exception $e) {
                                $fields_to_set = array_merge($fields_to_set, AmazonPaymentsAddressHelper::fetchInvalidInput($address_delivery, Tools::getValue('add')));
                                $htmlstr = '';
                                foreach ($fields_to_set as $field_to_set) {
                                    $this->context->smarty->assign('states', State::getStatesByIdCountry((int)Country::getByIso($iso_code)));
                                    $this->context->smarty->assign('field_name', $field_to_set);
                                    $this->context->smarty->assign('field_value', isset($address_delivery->$field_to_set) ? $address_delivery->$field_to_set : '');
                                    $htmlstr .= $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/front/address_field.tpl');
                                }
                                $this->errors[] = $this->module->l('Please fill in the missing fields to save your address.');
                                foreach (AmazonPaymentsAddressHelper::$validation_errors as $errMsg) {
                                    $this->errors[] = $errMsg;
                                }
                                self::$amz_payments->exceptionLog(false, "Customer login, missing address Data: \r\n" . print_r($this->errors, true) . "\r\n\r\n" . self::$amz_payments->debugAddressObject($address_delivery));
                            }
                            
                            if (!count($this->errors)) {
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
                                    'gift_price' => Tools::displayPrice(Tools::convertPrice(Product::getTaxCalculationMethod() == 1 ? $wrapping_fees : $wrapping_fees_tax_inc, new Currency((int) ($this->context->cookie->id_currency)))),
                                    'carrier_data' => $this->_getCarrierList(),
                                    'refresh' => (bool) $this->ajax_refresh
                                ), $this->getFormatedSummaryDetail());
                                die(Tools::jsonEncode($result));
                            }
                            
                            if (count($this->errors)) {
                                die(Tools::jsonEncode(array(
                                    'hasError' => true,
                                    'errors' => $this->errors,
                                    'fields_to_set' => $fields_to_set,
                                    'fields_html' => $htmlstr
                                )));
                            }
                            break;
                        
                        case 'executeOrder':
                            $customer = new Customer((int) $this->context->cart->id_customer);
                            if (!Validate::isLoadedObject($customer)) {
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
                                
                                $service = self::$amz_payments->getService();
                                $requestParameters = array();
                                $responsearray = array();
                                $requestParameters['amazon_order_reference_id'] = Tools::getValue('amazonOrderReferenceId');
                                $requestParameters['merchant_id'] = self::$amz_payments->merchant_id;
                                $requestParameters['platform_id'] = self::$amz_payments->getPfId();
                                
                                if (!AmazonTransactions::isAlreadyConfirmedOrder(Tools::getValue('amazonOrderReferenceId'))) {
                                    $requestParameters['amount'] = $total;
                                    $requestParameters['currency_code'] = $currency_code;
                                    $requestParameters['seller_order_id'] = self::$amz_payments->createUniqueOrderId((int) $this->context->cart->id);
                                    $requestParameters['store_name'] = Configuration::get('PS_SHOP_NAME');
                                    $requestParameters['custom_information'] = 'Prestashop,Patworx,' . self::$amz_payments->version;
                                    
                                    $response = $service->SetOrderReferenceDetails($requestParameters);
                                    
                                    try {
                                        $response = $service->confirmOrderReference($requestParameters);
                                    } catch (OffAmazonPaymentsService_Exception $e) {
                                        $this->exceptionLog($e);
                                    }
                                    $responsearray['confirm'] = $response->toArray();
                                    
                                    if ($service->success) {
                                        $requestParameters['address_consent_token'] = null;
                                        $response = $service->GetOrderReferenceDetails($requestParameters);
                                        $responsearray['getorderreference'] = $response->toArray();
                                    }
                                    
                                    if (!isset($responsearray['getorderreference'])) {
                                        die(Tools::jsonEncode(array(
                                            'hasError' => true,
                                            'errors' => array(
                                                self::$amz_payments->l('Your selected payment method is currently not available. Please select another one.')
                                            )
                                        )));
                                    }
                                                                
                                    $sql_arr = array(
                                        'amz_tx_time' => pSQL(time()),
                                        'amz_tx_type' => 'order_ref',
                                        'amz_tx_status' => pSQL($responsearray['getorderreference']['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['OrderReferenceStatus']['State']),
                                        'amz_tx_order_reference' => pSQL(Tools::getValue('amazonOrderReferenceId')),
                                        'amz_tx_expiration' => pSQL(strtotime($responsearray['getorderreference']['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['ExpirationTimestamp'])),
                                        'amz_tx_reference' => pSQL(Tools::getValue('amazonOrderReferenceId')),
                                        'amz_tx_amz_id' => pSQL(Tools::getValue('amazonOrderReferenceId')),
                                        'amz_tx_last_change' => pSQL(time()),
                                        'amz_tx_amount' => pSQL($responsearray['getorderreference']['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['OrderTotal']['Amount'])
                                    );
                                    Db::getInstance()->insert('amz_transactions', $sql_arr);
                                } else {
                                    $response = $service->GetOrderReferenceDetails($requestParameters);
                                    $responsearray['getorderreference'] = $response->toArray();
                                }
                                                                
                                $physical_destination = $responsearray['getorderreference']['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['Destination']['PhysicalDestination'];

                                $iso_code = (string) AmzPayments::getFromArray($physical_destination, 'CountryCode');
                                $city = (string) AmzPayments::getFromArray($physical_destination, 'City');
                                $postcode = (string) AmzPayments::getFromArray($physical_destination, 'PostalCode');
                                $state = (string) AmzPayments::getFromArray($physical_destination, 'State');
                                
                                $names_array = explode(' ', (string) (string) AmzPayments::getFromArray($physical_destination, 'Name'), 2);
                                $names_array = AmzPayments::prepareNamesArray($names_array);
                                                                
                                if ($customer->is_guest) {
                                    $customer->lastname = $names_array[1];
                                    $customer->firstname = $names_array[0];
                                    $customer->email = (string)$responsearray['getorderreference']['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['Buyer']['Email'];
                                    try {
                                        $customer->save();
                                    } catch (Exception $e) {
                                        $address_delivery = AmazonPaymentsAddressHelper::findByAmazonOrderReferenceIdOrNew(Tools::getValue('amazonOrderReferenceId'), false, $physical_destination);
                                        $customer->lastname = $address_delivery->lastname;
                                        $customer->firstname = $address_delivery->firstname;
                                        $customer->save();
                                    }
                                    $this->context->cart->id_customer = $customer->id;
                                    $this->context->cart->save();
                                }
                                
                                $s_company_name = '';
                                if ((string) AmzPayments::getFromArray($physical_destination, 'AddressLine3') != '') {
                                    $s_street = Tools::substr(AmzPayments::getFromArray($physical_destination, 'AddressLine3'), 0, Tools::strrpos(AmzPayments::getFromArray($physical_destination, 'AddressLine3'), ' '));
                                    $s_street_nr = Tools::substr(AmzPayments::getFromArray($physical_destination, 'AddressLine3'), Tools::strrpos(AmzPayments::getFromArray($physical_destination, 'AddressLine3'), ' ') + 1);
                                    $s_company_name = trim(AmzPayments::getFromArray($physical_destination, 'AddressLine1') . AmzPayments::getFromArray($physical_destination, 'AddressLine2'));
                                } else {
                                    if ((string) AmzPayments::getFromArray($physical_destination, 'AddressLine2') != '') {
                                        $s_street = Tools::substr(AmzPayments::getFromArray($physical_destination, 'AddressLine2'), 0, Tools::strrpos(AmzPayments::getFromArray($physical_destination, 'AddressLine2'), ' '));
                                        $s_street_nr = Tools::substr(AmzPayments::getFromArray($physical_destination, 'AddressLine2'), Tools::strrpos(AmzPayments::getFromArray($physical_destination, 'AddressLine2'), ' ') + 1);
                                        $s_company_name = trim(AmzPayments::getFromArray($physical_destination, 'AddressLine1'));
                                    } else {
                                        $s_street = Tools::substr(AmzPayments::getFromArray($physical_destination, 'AddressLine1'), 0, Tools::strrpos(AmzPayments::getFromArray($physical_destination, 'AddressLine1'), ' '));
                                        $s_street_nr = Tools::substr(AmzPayments::getFromArray($physical_destination, 'AddressLine1'), Tools::strrpos(AmzPayments::getFromArray($physical_destination, 'AddressLine1'), ' ') + 1);
                                    }
                                }
                                
                                $phone = '0000000000';
                                if ((string) AmzPayments::getFromArray($physical_destination, 'Phone') != '' && Validate::isPhoneNumber((string) AmzPayments::getFromArray($physical_destination, 'Phone'))) {
                                    $phone = (string) AmzPayments::getFromArray($physical_destination, 'Phone');
                                }
                                
                                $address_delivery = AmazonPaymentsAddressHelper::findByAmazonOrderReferenceIdOrNew(Tools::getValue('amazonOrderReferenceId'), false, $physical_destination);
                                if ((int)$address_delivery->id == 0) {
                                    $address_delivery->lastname = $names_array[1];
                                    $address_delivery->firstname = $names_array[0];
    
                                    if (in_array(Tools::strtolower((string)AmzPayments::getFromArray($physical_destination, 'CountryCode')), array('de', 'at', 'uk'))) {
                                        if ($s_company_name != '') {
                                            $address_delivery->company = $s_company_name;
                                        }
                                        $address_delivery->address1 = (string) $s_street . ' ' . (string) $s_street_nr;
                                    } else {
                                        $address_delivery->address1 = (string) AmzPayments::getFromArray($physical_destination, 'AddressLine1');
                                        if (trim($address_delivery->address1) == '') {
                                            $address_delivery->address1 = (string) AmzPayments::getFromArray($physical_destination, 'AddressLine2');
                                        } else {
                                            if (trim((string)AmzPayments::getFromArray($physical_destination, 'AddressLine2')) != '') {
                                                $address_delivery->address2 = (string) AmzPayments::getFromArray($physical_destination, 'AddressLine2');
                                            }
                                        }
                                        if (trim((string)AmzPayments::getFromArray($physical_destination, 'AddressLine3')) != '') {
                                            $address_delivery->address2.= ' ' . (string) AmzPayments::getFromArray($physical_destination, 'AddressLine3');
                                        }
                                    }
                                    
                                    $address_delivery->postcode = (string) $postcode;
                                    $address_delivery->id_country = Country::getByIso($iso_code);
                                    if ($phone != '') {
                                        $address_delivery->phone = $phone;
                                        $address_delivery->phone_mobile = $phone;
                                    }
                                    $address_delivery->id_state = 0;
                                    if ($state != '') {
                                        $state_id = State::getIdByIso($state, Country::getByIso($iso_code));
                                        if (!$state_id) {
                                            $state_id = State::getIdByName($state);
                                        }
                                        if ($state_id) {
                                            $address_delivery->id_state = $state_id;
                                        }
                                    }
                                    $address_delivery = AmzPayments::prepareAddressLines($address_delivery);
                                }
                                $address_delivery->save();
                                AmazonPaymentsAddressHelper::saveAddressAmazonReference($address_delivery, Tools::getValue('amazonOrderReferenceId'), $physical_destination);
                                
                                $this->context->cart->id_address_delivery = $address_delivery->id;
                                
                                if (isset($responsearray['getorderreference']['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['BillingAddress'])) {
                                    $billing_address_array = $responsearray['getorderreference']['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['BillingAddress'];
                                    
                                    if (isset($billing_address_array['PhysicalAddress'])) {
                                        $amz_billing_address = $billing_address_array['PhysicalAddress'];
                                        
                                        $iso_code = (string) AmzPayments::getFromArray($amz_billing_address, 'CountryCode');
                                        $city = (string) AmzPayments::getFromArray($amz_billing_address, 'City');
                                        $postcode = (string) AmzPayments::getFromArray($amz_billing_address, 'PostalCode');
                                        $state = (string) AmzPayments::getFromArray($amz_billing_address, 'State');
                                        
                                        $invoice_names_array = explode(' ', (string) AmzPayments::getFromArray($amz_billing_address, 'Name'), 2);
                                        $invoice_names_array = AmzPayments::prepareNamesArray($invoice_names_array);
                                                                                
                                        $s_company_name = '';
                                        if ((string) AmzPayments::getFromArray($amz_billing_address, 'AddressLine3') != '') {
                                            $s_street = Tools::substr(AmzPayments::getFromArray($amz_billing_address, 'AddressLine3'), 0, Tools::strrpos(AmzPayments::getFromArray($amz_billing_address, 'AddressLine3'), ' '));
                                            $s_street_nr = Tools::substr(AmzPayments::getFromArray($amz_billing_address, 'AddressLine3'), Tools::strrpos(AmzPayments::getFromArray($amz_billing_address, 'AddressLine3'), ' ') + 1);
                                            $s_company_name = trim(AmzPayments::getFromArray($amz_billing_address, 'AddressLine1') . AmzPayments::getFromArray($amz_billing_address, 'AddressLine2'));
                                        } else {
                                            if ((string) AmzPayments::getFromArray($amz_billing_address, 'AddressLine2') != '') {
                                                $s_street = Tools::substr(AmzPayments::getFromArray($amz_billing_address, 'AddressLine2'), 0, Tools::strrpos(AmzPayments::getFromArray($amz_billing_address, 'AddressLine2'), ' '));
                                                $s_street_nr = Tools::substr(AmzPayments::getFromArray($amz_billing_address, 'AddressLine2'), Tools::strrpos(AmzPayments::getFromArray($amz_billing_address, 'AddressLine2'), ' ') + 1);
                                                $s_company_name = trim(AmzPayments::getFromArray($amz_billing_address, 'AddressLine1'));
                                            } else {
                                                $s_street = Tools::substr(AmzPayments::getFromArray($amz_billing_address, 'AddressLine1'), 0, Tools::strrpos(AmzPayments::getFromArray($amz_billing_address, 'AddressLine1'), ' '));
                                                $s_street_nr = Tools::substr(AmzPayments::getFromArray($amz_billing_address, 'AddressLine1'), Tools::strrpos(AmzPayments::getFromArray($amz_billing_address, 'AddressLine1'), ' ') + 1);
                                            }
                                        }
                                        
                                        $phone = '0000000000';
                                        if ((string) AmzPayments::getFromArray($amz_billing_address, 'Phone') != '' && Validate::isPhoneNumber((string) AmzPayments::getFromArray($amz_billing_address, 'Phone'))) {
                                            $phone = (string) AmzPayments::getFromArray($amz_billing_address, 'Phone');
                                        }
                                        
                                        $address_invoice = AmazonPaymentsAddressHelper::findByAmazonOrderReferenceIdOrNew(Tools::getValue('amazonOrderReferenceId') . '-inv', false, $amz_billing_address);
                                        $address_invoice->alias = 'Amazon Payments Invoice';
                                        $address_invoice->lastname = $invoice_names_array[1];
                                        $address_invoice->firstname = $invoice_names_array[0];

                                        if (in_array(Tools::strtolower((string)AmzPayments::getFromArray($amz_billing_address, 'CountryCode')), array('de', 'at', 'uk'))) {
                                            if ($s_company_name != '') {
                                                $address_invoice->company = $s_company_name;
                                            }
                                            $address_invoice->address1 = (string) $s_street . ' ' . (string) $s_street_nr;
                                        } else {
                                            $address_invoice->address1 = (string) AmzPayments::getFromArray($amz_billing_address, 'AddressLine1');
                                            if (trim($address_invoice->address1) == '') {
                                                $address_invoice->address1 = (string) AmzPayments::getFromArray($amz_billing_address, 'AddressLine2');
                                            } else {
                                                if (trim((string)AmzPayments::getFromArray($amz_billing_address, 'AddressLine2')) != '') {
                                                    $address_invoice->address2 = (string) AmzPayments::getFromArray($amz_billing_address, 'AddressLine2');
                                                }
                                            }
                                            if (trim((string)AmzPayments::getFromArray($amz_billing_address, 'AddressLine3')) != '') {
                                                $address_invoice->address2.= ' ' . (string) AmzPayments::getFromArray($amz_billing_address, 'AddressLine3');
                                            }
                                        }
                                        
                                        $address_invoice->postcode = (string) $postcode;
                                        $address_invoice->city = $city;
                                        $address_invoice->id_country = Country::getByIso($iso_code);
                                        if ($phone != '') {
                                            $address_invoice->phone = $phone;
                                            $address_invoice->phone_mobile = $phone;
                                        }
                                        $address_invoice->id_state = 0;
                                        if ($state != '') {
                                            $state_id = State::getIdByIso($state, Country::getByIso($iso_code));
                                            if (!$state_id) {
                                                $state_id = State::getIdByName($state);
                                            }
                                            if ($state_id) {
                                                $address_invoice->id_state = $state_id;
                                            }
                                        }
                                        $address_invoice = AmzPayments::prepareAddressLines($address_invoice);
                                        
                                        $fields_to_set = array();
                                        $htmlstr = '';
                                        try {
                                            $address_invoice->save();
                                        } catch (Exception $e) {
                                            $fields_to_set = AmazonPaymentsAddressHelper::fetchInvalidInput($address_invoice);
                                            $htmlstr = '';
                                            foreach ($fields_to_set as $field_to_set) {
                                                $address_invoice->$field_to_set = isset($address_delivery->$field_to_set) ? $address_delivery->$field_to_set : '';
                                            }
                                            $address_invoice->save();
                                        }
                                        
                                        AmazonPaymentsAddressHelper::saveAddressAmazonReference($address_invoice, Tools::getValue('amazonOrderReferenceId') . '-inv', $amz_billing_address);
                                        $this->context->cart->id_address_invoice = $address_invoice->id;
                                    }
                                } else {
                                    $this->context->cart->id_address_invoice = $address_delivery->id;
                                    $address_invoice = $address_delivery;
                                }
                            
                                $this->context->cart->save();

                                if (self::$amz_payments->authorization_mode == 'fast_auth') {
                                    $authorization_reference_id = Tools::getValue('amazonOrderReferenceId');
                                    
                                    $authorization_response_wrapper = AmazonTransactions::fastAuth(self::$amz_payments, $this->service, $authorization_reference_id, $total, $currency_code);
                                    if (is_array($authorization_response_wrapper)) {
                                        $details = $authorization_response_wrapper['AuthorizeResult']['AuthorizationDetails'];
                                        $status = $details['AuthorizationStatus']['State'];
                                        
                                        if ($status == 'Declined') {
                                            $reason = $details['AuthorizationStatus']['ReasonCode'];
                                            if ($reason == 'InvalidPaymentMethod') {
                                                die(Tools::jsonEncode(array(
                                                    'hasError' => true,
                                                    'errors' => array(
                                                        self::$amz_payments->l('Your selected payment method is currently not available. Please select another one.')
                                                    )
                                                )));
                                            } else {
                                                die(Tools::jsonEncode(array(
                                                    'hasError' => true,
                                                    'redirection' => 'index.php?controller=cart',
                                                    'errors' => array(
                                                        self::$amz_payments->l('Your selected payment method has been declined. Please chose another one or use a different Amazon account.')
                                                    )
                                                )));
                                            }
                                        }
                                        
                                        $amazon_authorization_id = $details['AmazonAuthorizationId'];
                                        /*
                                        if (self::$amz_payments->capture_mode == 'after_auth') {
                                            $amazon_capture_response = AmazonTransactions::capture(self::$amz_payments, $this->service, $amazon_authorization_id, $total, $currency_code);
                                            if (is_array($amazon_capture_response)) {
                                                $amazon_capture_id = $amazon_capture_response['CaptureResult']['CaptureDetails']['AmazonCaptureId'];
                                                $amazon_capture_reference_id = $amazon_capture_response['CaptureResult']['CaptureDetails']['CaptureReferenceId'];
                                            }
                                        }
                                        */
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
                                $this->module->validateOrder((int) $this->context->cart->id, $new_order_status_id, $total, $this->module->displayName, null, array(), null, false, $customer->secure_key);
                                
                                if (self::$amz_payments->authorization_mode == 'after_checkout') {
                                    $authorization_reference_id = Tools::getValue('amazonOrderReferenceId');
                                    $authorization_response_wrapper = AmazonTransactions::authorize(self::$amz_payments, $this->service, $authorization_reference_id, $total, $currency_code);
                                    $authorization_response_wrapper['AuthorizeResult']['AuthorizationDetails']['AmazonAuthorizationId'];
                                    /*
                                    if (self::$amz_payments->capture_mode == 'after_auth' && isset($amazon_authorization_id) && $amazon_authorization_id !== false && $amazon_authorization_id != null) {
                                        $amazon_capture_response = AmazonTransactions::capture(self::$amz_payments, $this->service, $amazon_authorization_id, $total, $currency_code);
                                        if (is_array($amazon_capture_response)) {
                                            $amazon_capture_id = $amazon_capture_response['CaptureResult']['CaptureDetails']['AmazonCaptureId'];
                                            $amazon_capture_reference_id = $amazon_capture_response['CaptureResult']['CaptureDetails']['CaptureReferenceId'];
                                        }
                                    }
                                    */
                                }
                                
                                self::$amz_payments->setAmazonReferenceIdForOrderId(Tools::getValue('amazonOrderReferenceId'), $this->module->currentOrder);
                                self::$amz_payments->setAmazonReferenceIdForOrderTransactionId(Tools::getValue('amazonOrderReferenceId'), $this->module->currentOrder);
                                if (isset($authorization_reference_id)) {
                                    self::$amz_payments->setAmazonAuthorizationReferenceIdForOrderId($authorization_reference_id, $this->module->currentOrder);
                                }
                                if (isset($amazon_authorization_id)) {
                                    self::$amz_payments->setAmazonAuthorizationIdForOrderId($amazon_authorization_id, $this->module->currentOrder);
                                }
                                /*
                                if (isset($amazon_capture_reference_id)) {
                                    self::$amz_payments->setAmazonCaptureReferenceIdForOrderId($amazon_capture_reference_id, $this->module->currentOrder);
                                }
                                if (isset($amazon_capture_id)) {
                                    self::$amz_payments->setAmazonCaptureIdForOrderId($amazon_capture_id, $this->module->currentOrder);
                                }
                                */

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
                                
                                if (Tools::getValue('connect_amz_account') == '1') {
                                    $this->context->cookie->amz_connect_order = $this->module->currentOrder;
                                    $this->context->cookie->amz_payments_address_id = $address_delivery->id;
                                    $this->context->cookie->amz_payments_invoice_address_id = $address_invoice->id;
                                    $login_redirect = $this->context->link->getModuleLink('amzpayments', 'processlogin');
                                    $login_redirect = str_replace('http://', 'https://', $login_redirect);
                                    $login_redirect .= '?fromCheckout=1&access_token=' . $this->context->cookie->amz_access_token;
                                    die(Tools::jsonEncode(array(
                                        'orderSucceed' => true,
                                        'redirection' => $login_redirect
                                    )));
                                }
                                
                                if (!$customer->is_guest) {
                                    if (!AmzPayments::addressAlreadyExists($address_delivery, $customer)) {
                                        $address_delivery->id_customer = $customer->id;
                                        $address_delivery->save();
                                    }
                                    if (!AmzPayments::addressAlreadyExists($address_invoice, $customer)) {
                                        $address_invoice->id_customer = $customer->id;
                                        $address_invoice->save();
                                    }
                                } else {
                                    if ($registered_customer = AmazonPaymentsCustomerHelper::findByEmailAddress($customer->email)) {
                                        if (!AmzPayments::addressAlreadyExists($address_delivery, $registered_customer)) {
                                            $address_delivery->id_customer = $registered_customer->id;
                                            $address_delivery->save();
                                        }
                                        if (!AmzPayments::addressAlreadyExists($address_invoice, $registered_customer)) {
                                            $address_invoice->id_customer = $registered_customer->id;
                                            $address_invoice->save();
                                        }
                                        $this->context->customer = $registered_customer;
                                        $this->context->cookie->id_customer = (int) $registered_customer->id;
                                    } else {
                                        $this->context->customer = $customer;
                                        $this->context->cookie->id_customer = (int) $customer->id;
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
                } else {
                    throw new PrestaShopException('Method is not defined');
                }
            }
        } elseif (Tools::isSubmit('ajax')) {
            throw new PrestaShopException('Method is not defined');
        }
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
                
        $selected_country = (int) (Configuration::get('PS_COUNTRY_DEFAULT'));
        
        if (Configuration::get('PS_RESTRICT_DELIVERED_COUNTRIES')) {
            $countries = Carrier::getDeliveredCountries($this->context->language->id, true, true);
        } else {
            $countries = Country::getCountries($this->context->language->id, true);
        }
        
        Tools::safePostVars();
        
        $this->context->smarty->assign(array(
            'amz_module_path' => self::$amz_payments->getPathUri(),
            'amz_session' => Tools::getValue('session') ? Tools::getValue('session') : $this->context->cookie->amazon_id,
            'sellerID' => Configuration::get('AMZ_MERCHANT_ID'),
            'sandboxMode' => self::$amz_payments->environment == 'SANDBOX',
        ));
        
        $conditionsToApproveFinder = new ConditionsToApproveFinder(
            $this->context,
            $this->getTranslator()
        );
        $this->context->smarty->assign('conditions_to_approve', $conditionsToApproveFinder->getConditionsToApproveForTemplate());
        
        $presentedCart = $this->cart_presenter->present($this->context->cart);
        $this->context->smarty->assign('cart', $presentedCart);
        
        if (self::$amz_payments->lpa_mode != 'pay' &&
            isset($this->context->cookie->amz_access_token) &&
            $this->context->cookie->amz_access_token != '' &&
            !AmazonPaymentsCustomerHelper::customerHasAmazonCustomerId($this->context->cookie->id_customer)
            ) {
            $this->context->smarty->assign('show_amazon_account_creation_allowed', true);
        } else {
            $this->context->smarty->assign('show_amazon_account_creation_allowed', false);
        }
        
        $this->context->smarty->assign('preselect_create_account', Configuration::get('PRESELECT_CREATE_ACCOUNT') == 1);
        $this->context->smarty->assign('force_account_creation', Configuration::get('FORCE_ACCOUNT_CREATION') == 1);
        $this->context->smarty->assign('tpl_dir', _THEME_DIR_);
        
        $this->setTemplate('module:amzpayments/views/templates/front/amzpayments_bs.tpl');
    }

    private function getCheckoutSession()
    {
        $deliveryOptionsFinder = new DeliveryOptionsFinder(
            $this->context,
            $this->getTranslator(),
            $this->objectPresenter,
            new PriceFormatter()
        );
    
        $session = new CheckoutSession(
            $this->context,
            $deliveryOptionsFinder
        );
    
        return $session;
    }
    
    protected function _processCarrier()
    {
        $this->context->cart->recyclable = (int) (Tools::getValue('recyclable'));
        $this->context->cart->gift = (int) (Tools::getValue('gift'));
        if ((int) (Tools::getValue('gift'))) {
            if (!Validate::isMessage(Tools::getValue('gift_message'))) {
                $this->errors[] = Tools::displayError('Invalid gift message.');
            } else {
                $this->context->cart->gift_message = strip_tags(Tools::getValue('gift_message'));
            }
        }
        
        if (isset($this->context->customer->id) && $this->context->customer->id) {
            $address = new Address((int) ($this->context->cart->id_address_delivery));
            if (!(Address::getZoneById($address->id))) {
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
                foreach ($delivery_option_list as $id_address => $options) {
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
        }
        
        Hook::exec('actionCarrierProcess', array(
            'cart' => $this->context->cart
        ));
        
        if (!$this->context->cart->update()) {
            return false;
        }
        
        CartRule::autoRemoveFromCart($this->context);
        CartRule::autoAddToCart($this->context);
        
        return true;
    }

    protected function validateDeliveryOption($delivery_option)
    {
        if (!is_array($delivery_option)) {
            return false;
        }
        
        foreach ($delivery_option as $option) {
            if (!preg_match('/(\d+,)?\d+/', $option)) {
                return false;
            }
        }
        
        return true;
    }

    protected function _getCarrierList()
    {
        $address_delivery = new Address($this->getCheckoutSession()->getIdAddressDelivery());
        $delivery_options = $this->getCheckoutSession()->getDeliveryOptions();
        
        foreach ($delivery_options as $do_id => $do) {
            if ($this->shippingNotAllowed($do_id)) {
                unset($delivery_options[$do_id]);
            }
        }
        
        $checkoutDeliveryStep = new CheckoutDeliveryStep(
            $this->context,
            $this->getTranslator()
        );
        
        $checkoutDeliveryStep
            ->setRecyclablePackAllowed((bool) Configuration::get('PS_RECYCLABLE_PACK'))
            ->setGiftAllowed((bool) Configuration::get('PS_GIFT_WRAPPING'))
            ->setIncludeTaxes(
                !Product::getTaxCalculationMethod((int) $this->context->cart->id_customer)
                && (int) Configuration::get('PS_TAX')
            )
            ->setDisplayTaxesLabel((Configuration::get('PS_TAX') && !Configuration::get('AEUC_LABEL_TAX_INC_EXC')))
            ->setGiftCost(
                $this->context->cart->getGiftWrappingPrice(
                    $checkoutDeliveryStep->getIncludeTaxes()
                )
            );

        $this->context->smarty->assign('id_address', $this->getCheckoutSession()->getIdAddressDelivery());
        $this->context->smarty->assign('delivery_options', $delivery_options);
        $this->context->smarty->assign('delivery_option', $this->getCheckoutSession()->getSelectedDeliveryOption());
        $this->context->smarty->assign('hookDisplayBeforeCarrier', Hook::exec('displayBeforeCarrier', array('cart' => $this->getCheckoutSession()->getCart())));
        $this->context->smarty->assign('hookDisplayAfterCarrier', Hook::exec('displayAfterCarrier', array('cart' => $this->getCheckoutSession()->getCart())));

        $this->context->smarty->assign(array(
            'recyclable' => $this->getCheckoutSession()->isRecyclable(),
            'recyclablePackAllowed' => (bool) Configuration::get('PS_RECYCLABLE_PACK'),
            'gift' => array(
                'allowed' => (bool) Configuration::get('PS_GIFT_WRAPPING'),
                'isGift' => $this->getCheckoutSession()->getGift()['isGift'],
                'label' => $this->getTranslator()->trans(
                    'I would like my order to be gift wrapped'.$checkoutDeliveryStep->getGiftCostForLabel(),
                    array(),
                    'Checkout'
                ),
                'message' => $this->getCheckoutSession()->getGift()['message'],
            ),
        ));
        $result = array(
            'carrier_block' => $this->context->smarty->fetch('module:amzpayments/views/templates/front/_carriers.tpl'),
        );
        return $result;
    }
    
    protected function getFormatedCouponBlock()
    {
        $presentedCart = $this->cart_presenter->present($this->context->cart);
        $this->context->smarty->assign('cart', $presentedCart);
        return $this->context->smarty->fetch('module:amzpayments/views/templates/front/_coupon.tpl');
    }
    
    protected function renderSummary()
    {
        $presentedCart = $this->cart_presenter->present($this->context->cart);
        $this->context->smarty->assign('products', $presentedCart['products']);
        $this->context->smarty->assign('subtotals', $presentedCart['subtotals']);
        $this->context->smarty->assign('totals', $presentedCart['totals']);
        $this->context->smarty->assign('labels', $presentedCart['labels']);
        $this->context->smarty->assign('add_product_link', true);
        
        $table = $this->context->smarty->fetch('checkout/_partials/order-confirmation-table.tpl');
        $this->context->smarty->assign('summary_table', $table);
        return str_replace('col-md-8', 'col-md-12', $this->context->smarty->fetch('module:amzpayments/views/templates/front/_summary.tpl'));
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
            'summary_block' => $this->renderSummary(),
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
    
    protected function exceptionLog($e)
    {
        self::$amz_payments->exceptionLog($e);
    }
}
