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

class AmzpaymentsSelect_AddressModuleFrontController extends ModuleFrontController
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
        parent::init();
        self::$amz_payments = new AmzPayments();
        $this->isLogged = (bool) $this->context->customer->id && Customer::customerIdExistsStatic((int) $this->context->cookie->id_customer);
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        if (Tools::isSubmit('ajax')) {
            if (Tools::isSubmit('method')) {
                $this->service = self::$amz_payments->getService();
                
                switch (Tools::getValue('method')) {
                    case 'updateAddressesSelected':
                        if (Tools::getValue('src') == 'addresswallet') {
                            $currency_order = new Currency((int) $this->context->cart->id_currency);
                            $currency_code = $currency_order->iso_code;
                            if ($currency_code == 'JYP') {
                                $currency_code = 'YEN';
                            }
                            $this->context->cookie->amazon_id = Tools::getValue('amazonOrderReferenceId');
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
                            ->setAmount(10);
                            $set_order_reference_details_request->getOrderReferenceAttributes()->setPlatformId(self::$amz_payments->getPfId());
                            try {
                                $this->service->setOrderReferenceDetails($set_order_reference_details_request);
                            } catch (Exception $e) {
                            }
                        }
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
                        $state = (string) $physical_destination->GetStateOrRegion();
                        
                        if (method_exists($physical_destination, 'getName')) {
                            $names_array = explode(' ', (string) $physical_destination->getName(), 2);
                            $names_array = AmzPayments::prepareNamesArray($names_array);
                        } else {
                            $names_array = array('amzFirstname', 'amzLastname');
                        }
                        
                        $phone = '0000000000';
                        if (method_exists($physical_destination, 'getPhone') && (string) $physical_destination->getPhone() != '' && Validate::isPhoneNumber((string) $physical_destination->getPhone())) {
                            $phone = (string) $physical_destination->getPhone();
                        }
                        
                        $address_delivery = AmazonPaymentsAddressHelper::findByAmazonOrderReferenceIdOrNew(Tools::getValue('amazonOrderReferenceId'), false, $physical_destination);
                        $address_delivery->company = '';
                        $address_delivery->address1 = '';
                        $address_delivery->address2 = '';
                        $address_delivery->id_customer = (int) $this->context->cookie->id_customer;
                        $address_delivery->id_country = Country::getByIso($iso_code);
                        $country = new Country((int)Country::getByIso($iso_code));
                        $address_delivery->alias = 'Amazon Pay';
                        $address_delivery->lastname = $names_array[1];
                        $address_delivery->firstname = $names_array[0];
                        
                        if (method_exists($physical_destination, 'getAddressLine3') && method_exists($physical_destination, 'getAddressLine2') && method_exists($physical_destination, 'getAddressLine1')) {
                            $s_company_name = '';
                            if ((string) $physical_destination->getAddressLine3() != '') {
                                $s_street = Tools::substr($physical_destination->getAddressLine3(), 0, Tools::strrpos($physical_destination->getAddressLine3(), ' '));
                                $s_street_nr = Tools::substr($physical_destination->getAddressLine3(), Tools::strrpos($physical_destination->getAddressLine3(), ' ') + 1);
                                $s_company_name = trim($physical_destination->getAddressLine1() . $physical_destination->getAddressLine2());
                            } else {
                                if ((string) $physical_destination->getAddressLine2() != '') {
                                    $s_street = Tools::substr($physical_destination->getAddressLine2(), 0, Tools::strrpos($physical_destination->getAddressLine2(), ' '));
                                    $s_street_nr = Tools::substr($physical_destination->getAddressLine2(), Tools::strrpos($physical_destination->getAddressLine2(), ' ') + 1);
                                    $s_company_name = trim($physical_destination->getAddressLine1());
                                } else {
                                    $s_street = Tools::substr($physical_destination->getAddressLine1(), 0, Tools::strrpos($physical_destination->getAddressLine1(), ' '));
                                    $s_street_nr = Tools::substr($physical_destination->getAddressLine1(), Tools::strrpos($physical_destination->getAddressLine1(), ' ') + 1);
                                }
                            }
                            if (in_array(Tools::strtolower((string)$physical_destination->getCountryCode()), array('de', 'at', 'uk'))) {
                                if ($s_company_name != '') {
                                    $address_delivery->company = $s_company_name;
                                }
                                $address_delivery->address1 = (string) $s_street . ' ' . (string) $s_street_nr;
                            } else {
                                $address_delivery->address1 = (string) $physical_destination->getAddressLine1();
                                if (trim($address_delivery->address1) == '') {
                                    $address_delivery->address1 = (string) $physical_destination->getAddressLine2();
                                } else {
                                    if (trim((string)$physical_destination->getAddressLine2()) != '') {
                                        $address_delivery->address2 = (string) $physical_destination->getAddressLine2();
                                    }
                                }
                                if (trim((string)$physical_destination->getAddressLine3()) != '') {
                                    $address_delivery->address2.= ' ' . (string) $physical_destination->getAddressLine3();
                                }
                            }
                        } else {
                            $address_delivery->address1 = 'amzAddress1';
                        }
                        $address_delivery = AmzPayments::prepareAddressLines($address_delivery);
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
                        $address_delivery->phone = $phone;
                        
                        if (Tools::getValue('add') && is_array(Tools::getValue('add'))) {
                            $address_delivery = AmazonPaymentsAddressHelper::addAdditionalValues($address_delivery, Tools::getValue('add'));
                        }
                        
                        $fields_to_set = array();
                        if ($address_delivery->id_state > 0 && !AmazonPaymentsAddressHelper::stateBelongsToCountry($address_delivery->id_state, (int)Country::getByIso($iso_code))) {
                            $address_delivery->id_state = 0;
                        }
                        if ($address_delivery->id_state == 0) {
                            if ($country->contains_states) {
                                if (sizeof(State::getStatesByIdCountry((int)Country::getByIso($iso_code))) > 0) {
                                    $address_delivery->id_state = -1;
                                }
                            }
                        }
                        
                        $htmlstr = '';
                        if (!$country->active) {
                            $this->errors[] = $this->module->l('We are unable to ship to this address. Please select a different address.');
                        } else {
                            try {
                                $address_delivery->save();
                            } catch (Exception $e) {
                                $fields_to_set = array_merge($fields_to_set, AmazonPaymentsAddressHelper::fetchInvalidInput($address_delivery, Tools::getValue('add')));
                                $htmlstr = '';
                                foreach ($fields_to_set as $field_to_set) {
                                    $this->context->smarty->assign('states', State::getStatesByIdCountry((int)Country::getByIso($iso_code)));
                                    $this->context->smarty->assign('field_name', $field_to_set);
                                    $this->context->smarty->assign('field_value', isset($address_delivery->$field_to_set) ? $address_delivery->$field_to_set : '');
                                    $htmlstr.= $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/front/address_field.tpl');
                                }
                                $this->errors[] = $this->module->l('Please fill in the missing fields to save your address.');
                            }
                            AmazonPaymentsAddressHelper::saveAddressAmazonReference($address_delivery, Tools::getValue('amazonOrderReferenceId'), $physical_destination);
                        }
                        
                        if (!count($this->errors)) {
                            if (self::$amz_payments->order_process_type == 'standard') {
                                $this->context->cart->id_address_delivery = $address_delivery->id;
                                $billing_address_object = $reference_details_result_wrapper->GetOrderReferenceDetailsResult->getOrderReferenceDetails()->getBillingAddress();
                                if (method_exists($billing_address_object, 'getPhysicalAddress')) {
                                    $amz_billing_address = $reference_details_result_wrapper->GetOrderReferenceDetailsResult->getOrderReferenceDetails()
                                    ->getBillingAddress()
                                    ->getPhysicalAddress();
                                    
                                    $iso_code = (string) $amz_billing_address->GetCountryCode();
                                    $city = (string) $amz_billing_address->GetCity();
                                    $postcode = (string) $amz_billing_address->GetPostalCode();
                                    $state = (string) $amz_billing_address->GetStateOrRegion();
                                    
                                    $invoice_names_array = explode(' ', (string) $amz_billing_address->getName(), 2);
                                    $invoice_names_array = AmzPayments::prepareNamesArray($invoice_names_array);
                                    
                                    $s_company_name = '';
                                    if ((string) $amz_billing_address->getAddressLine3() != '') {
                                        $s_street = Tools::substr($amz_billing_address->getAddressLine3(), 0, Tools::strrpos($amz_billing_address->getAddressLine3(), ' '));
                                        $s_street_nr = Tools::substr($amz_billing_address->getAddressLine3(), Tools::strrpos($amz_billing_address->getAddressLine3(), ' ') + 1);
                                        $s_company_name = trim($amz_billing_address->getAddressLine1() . $amz_billing_address->getAddressLine2());
                                    } else {
                                        if ((string) $amz_billing_address->getAddressLine2() != '') {
                                            $s_street = Tools::substr($amz_billing_address->getAddressLine2(), 0, Tools::strrpos($amz_billing_address->getAddressLine2(), ' '));
                                            $s_street_nr = Tools::substr($amz_billing_address->getAddressLine2(), Tools::strrpos($amz_billing_address->getAddressLine2(), ' ') + 1);
                                            $s_company_name = trim($amz_billing_address->getAddressLine1());
                                        } else {
                                            $s_street = Tools::substr($amz_billing_address->getAddressLine1(), 0, Tools::strrpos($amz_billing_address->getAddressLine1(), ' '));
                                            $s_street_nr = Tools::substr($amz_billing_address->getAddressLine1(), Tools::strrpos($amz_billing_address->getAddressLine1(), ' ') + 1);
                                        }
                                    }
                                    
                                    $phone = '0000000000';
                                    if ((string) $amz_billing_address->getPhone() != '' && Validate::isPhoneNumber((string) $amz_billing_address->getPhone())) {
                                        $phone = (string) $amz_billing_address->getPhone();
                                    }
                                    
                                    $address_invoice = AmazonPaymentsAddressHelper::findByAmazonOrderReferenceIdOrNew(Tools::getValue('amazonOrderReferenceId') . '-inv', false, $amz_billing_address);
                                    $address_invoice->company = '';
                                    $address_invoice->address1 = '';
                                    $address_invoice->address2 = '';
                                    $address_invoice->id_customer = (int) $this->context->cookie->id_customer;
                                    $address_invoice->alias = 'Amazon Pay Invoice';
                                    $address_invoice->lastname = $invoice_names_array[1];
                                    $address_invoice->firstname = $invoice_names_array[0];
                                    
                                    if (in_array(Tools::strtolower((string) $amz_billing_address->getCountryCode()), array(
                                        'de',
                                        'at',
                                        'uk'
                                    ))) {
                                        if ($s_company_name != '') {
                                            $address_invoice->company = $s_company_name;
                                        }
                                        $address_invoice->address1 = (string) $s_street . ' ' . (string) $s_street_nr;
                                    } else {
                                        $address_invoice->address1 = (string) $amz_billing_address->getAddressLine1();
                                        if (trim($address_invoice->address1) == '') {
                                            $address_invoice->address1 = (string) $amz_billing_address->getAddressLine2();
                                        } else {
                                            if (trim((string) $amz_billing_address->getAddressLine2()) != '') {
                                                $address_invoice->address2 = (string) $amz_billing_address->getAddressLine2();
                                            }
                                        }
                                        if (trim((string) $amz_billing_address->getAddressLine3()) != '') {
                                            $address_invoice->address2 .= ' ' . (string) $amz_billing_address->getAddressLine3();
                                        }
                                    }
                                    $address_invoice = AmzPayments::prepareAddressLines($address_invoice);
                                    $address_invoice->postcode = (string) $amz_billing_address->getPostalCode();
                                    $address_invoice->city = $city;
                                    $address_invoice->id_country = Country::getByIso((string) $amz_billing_address->getCountryCode());
                                    if ($phone != '') {
                                        $address_invoice->phone = $phone;
                                    }
                                    if ($state != '') {
                                        $state_id = State::getIdByIso($state, Country::getByIso((string) $amz_billing_address->getCountryCode()));
                                        if (! $state_id) {
                                            $state_id = State::getIdByName($state);
                                        }
                                        if ($state_id) {
                                            $address_invoice->id_state = $state_id;
                                        }
                                    }
                                    
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
                                } else {
                                    $this->context->cart->id_address_invoice = $address_delivery->id;
                                    $address_invoice = $address_delivery;
                                }
                                $this->context->cart->save();
                                $this->context->cookie->has_set_valid_amazon_address = true;
                            }
                            if ($this->context->cart->nbProducts()) {
                                if (Configuration::get('PS_SSL_ENABLED')) {
                                    $goto = _PS_BASE_URL_SSL_ . __PS_BASE_URI__;
                                } else {
                                    $goto = _PS_BASE_URL_ . __PS_BASE_URI__;
                                }
                                if (Tools::getValue('returnback')) {
                                    $goto.= 'index.php?controller='.Tools::str_replace_once(".php", "", Tools::getValue('returnback'));
                                } else {
                                    $goto.= 'index.php?controller=order';
                                }
                            } else {
                                if (Configuration::get('PS_SSL_ENABLED')) {
                                    $goto = _PS_BASE_URL_SSL_ . __PS_BASE_URI__;
                                } else {
                                    $goto = _PS_BASE_URL_ . __PS_BASE_URI__;
                                }
                            }
                            
                            $result = array('state' => 'success',
                                'hasError' => false,
                                'redirect' => $goto
                            );
                            die(Tools::jsonEncode($result));
                        }
                        
                        if (count($this->errors)) {
                            die(Tools::jsonEncode(array(
                                'hasError' => true,
                                'fields_to_set' => $fields_to_set,
                                'fields_html' => $htmlstr,
                                'errors' => $this->errors
                            )));
                        }
                        break;
                }
            }
        }
    }
    
    public function initContent()
    {
        parent::initContent();
        $this->context->smarty->assign(array(
            'ajaxSetAddressUrl' => $this->context->link->getModuleLink('amzpayments', 'select_address', array(), true),
            'sellerID' => Configuration::get('AMZ_MERCHANT_ID')
        ));
        $this->setTemplate('select_address.tpl');
    }
}
