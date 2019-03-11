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
                            $this->context->cookie->amazon_id = Tools::getValue('amazonOrderReferenceId');
                        }
                        $requestParameters = array();
                        $requestParameters['amazon_order_reference_id'] = Tools::getValue('amazonOrderReferenceId');
                        $requestParameters['merchant_id'] = self::$amz_payments->merchant_id;

                        if (getAmazonPayCookie()) {
                            $requestParameters['address_consent_token'] = AmzPayments::prepareCookieValueForAmazonPaymentsUse(getAmazonPayCookie());
                        } elseif (isset(self::$amz_payments->cookie->amz_access_token)) {
                            $requestParameters['address_consent_token'] = AmzPayments::prepareCookieValueForAmazonPaymentsUse(self::$amz_payments->cookie->amz_access_token);
                        }
                        
                        if (!isset($responsearray)) {
                            $responsearray = array();
                        }
                        $response = $this->service->GetOrderReferenceDetails($requestParameters);
                        $responsearray['getorderreference'] = $response->toArray();
                        
                        $physical_destination = $responsearray['getorderreference']['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['Destination']['PhysicalDestination'];

                        $iso_code = (string) AmzPayments::getFromArray($physical_destination, 'CountryCode');
                        $city = (string) AmzPayments::getFromArray($physical_destination, 'City');
                        $postcode = (string) AmzPayments::getFromArray($physical_destination, 'PostalCode');
                        $state = (string) AmzPayments::getFromArray($physical_destination, 'State');
                        if ($state == '') {
                            $state = (string) AmzPayments::getFromArray($physical_destination, 'StateOrRegion');
                        }

                        $names_array = explode(' ', (string) (string) AmzPayments::getFromArray($physical_destination, 'Name'), 2);
                        $names_array = AmzPayments::prepareNamesArray($names_array);

                        $phone = '0000000000';
                        if ((string) AmzPayments::getFromArray($physical_destination, 'Phone') != '' && Validate::isPhoneNumber((string) AmzPayments::getFromArray($physical_destination, 'Phone'))) {
                            $phone = (string) AmzPayments::getFromArray($physical_destination, 'Phone');
                        }

                        $address_delivery = AmazonPaymentsAddressHelper::findByAmazonOrderReferenceIdOrNew(Tools::getValue('amazonOrderReferenceId'), false, $physical_destination);
                        $address_delivery->company = '';
                        $address_delivery->address1 = '';
                        $address_delivery->address2 = '';
                        $address_delivery->id_customer = (int) $this->context->cookie->id_customer;
                        $address_delivery->alias = 'Amazon Pay';
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
                            if (!$state_id) {
                                $state_id = AmazonPostalCodesHelper::getIdByPostalCodeAndCountry((string)$postcode, $iso_code);
                            }
                            if ($state_id) {
                                $address_delivery->id_state = $state_id;
                            }
                        }
                        $address_delivery = AmzPayments::prepareAddressLines($address_delivery);
                        $address_delivery->city = $city;
                        $address_delivery->phone = $phone;

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
                                    $state_id = AmazonPostalCodesHelper::getIdByPostalCodeAndCountry($postcode, $iso_code);
                                    if ($state_id) {
                                        $address_delivery->id_state = (int)$state_id;
                                    } else {
                                        $address_delivery->id_state = -1;
                                    }
                                }
                            }
                        }
                        $htmlstr = '';
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
                        
                        if (! count($this->errors)) {
                            if (self::$amz_payments->order_process_type == 'standard') {
                                $old_delivery_address_id = $this->context->cart->id_address_delivery;
                                $this->context->cart->id_address_delivery = $address_delivery->id;
                                if (isset($responsearray['getorderreference']['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['BillingAddress'])) {
                                    $billing_address_array = $responsearray['getorderreference']['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['BillingAddress'];
                                    
                                    if (isset($billing_address_array['PhysicalAddress'])) {
                                        $amz_billing_address = $billing_address_array['PhysicalAddress'];
                                        
                                        $iso_code = (string) AmzPayments::getFromArray($amz_billing_address, 'CountryCode');
                                        $city = (string) AmzPayments::getFromArray($amz_billing_address, 'City');
                                        $postcode = (string) AmzPayments::getFromArray($amz_billing_address, 'PostalCode');
                                        $state = (string) AmzPayments::getFromArray($amz_billing_address, 'State');
                                        if ($state == '') {
                                            $state = (string) AmzPayments::getFromArray($amz_billing_address, 'StateOrRegion');
                                        }
                                        
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
                                        $address_invoice->company = '';
                                        $address_invoice->address1 = '';
                                        $address_invoice->address2 = '';
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
                                            if (!$state_id) {
                                                $state_id = AmazonPostalCodesHelper::getIdByPostalCodeAndCountry((string)$postcode, $iso_code);
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
                                $this->context->cart->setNoMultishipping();
                                $this->context->cart->updateAddressId($old_delivery_address_id, $address_delivery->id);
                                $this->context->cart->save();
                            }
                            if ($this->context->cart->nbProducts()) {
                                if (Configuration::get('PS_SSL_ENABLED')) {
                                    $goto = _PS_BASE_URL_SSL_ . __PS_BASE_URI__ . 'index.php?controller=cart&action=show';
                                } else {
                                    $goto = _PS_BASE_URL_ . __PS_BASE_URI__ . 'index.php?controller=cart&action=show';
                                }
                                if (Tools::getValue('returnback')) {
                                    if ($back == Tools::secureReferrer(Tools::getValue('returnback'))) {
                                        $goto = html_entity_decode($back);
                                    }
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
        $this->setTemplate('module:amzpayments/views/templates/front/select_address.tpl');
    }
}
