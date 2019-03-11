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

class AmzpaymentsUsertoshopModuleFrontController extends ModuleFrontController
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
        $_REQUEST['customer_privacy'] = 1;
        $_POST['customer_privacy'] = 1;
        self::$amz_payments = new AmzPayments();
        $this->isLogged = (bool) $this->context->customer->id && Customer::customerIdExistsStatic((int) $this->context->cookie->id_customer);
        
        parent::init();
        
        /* Disable some cache related bugs on the cart/order */
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        $this->display_column_left = false;
        $this->display_column_right = false;
        
        // Service initialisieren
        $this->service = self::$amz_payments->getService();
        
        if (Tools::isSubmit('ajax')) {
            if (Tools::isSubmit('method')) {
                switch (Tools::getValue('method')) {
                    case 'redirectAuthentication':
                    case 'setusertoshop':
                        if (Tools::getValue('method') == 'redirectAuthentication') {
                            Tools::redirect('index');
                        } else {
                            if (getAmazonPayCookie()) {
                                $accessTokenValue = getAmazonPayCookie();
                            } else {
                                if (Tools::getValue('access_token') != 'undefined' && Tools::getValue('access_token') != '' && Tools::getValue('access_token') != 'null') {
                                    self::$amz_payments->cookie->amz_access_token = AmzPayments::prepareCookieValueForPrestaShopUse(Tools::getValue('access_token'));
                                    $accessTokenValue = self::$amz_payments->cookie->amz_access_token;
                                    $this->context->cookie->amz_access_token_set_time = time();
                                } else {
                                    self::$amz_payments->exceptionLog(false, 'user_to_shop controller: Error, method not submitted and no token');
                                    die('error');
                                }
                            }
                        }

                        if (is_null($accessTokenValue) || $accessTokenValue === false) {
                            if (getAmazonPayCookie()) {
                                $accessTokenValue = getAmazonPayCookie();
                            }
                        }
                        
                        if (strpos($accessTokenValue, '-HORDIV-') > -1) {
                            $accessTokenValue = AmzPayments::prepareCookieValueForAmazonPaymentsUse($accessTokenValue);
                        }
                        
                        $d = self::$amz_payments->requestTokenInfo($accessTokenValue);
                        
                        if ($d->aud != self::$amz_payments->client_id) {
                            if (Tools::getValue('method') == 'redirectAuthentication') {
                                Tools::redirect('index');
                            } else {
                                self::$amz_payments->exceptionLog(false, 'user_to_shop controller: auth error LPA');
                                die('error');
                            }
                        }
                        $d = self::$amz_payments->requestProfile($accessTokenValue);
                        self::$amz_payments->recreateAmzJsString();
                        $this->context->cookie->write();
                        
                        $customer_userid = $d->user_id;
                        $customer_name = $d->name;
                        $customer_email = $d->email;
                        // $postcode = $d->postal_code;
                        $_POST['psgdpr-consent'] = true;
                                                
                        if ($customers_local_id = AmazonPaymentsCustomerHelper::findByAmazonCustomerId($customer_userid)) {
                            // Customer already exists - login
                            Hook::exec('actionBeforeAuthentication');
                            $customer = new Customer();
                            $authentication = AmazonPaymentsCustomerHelper::getByCustomerID($customers_local_id, true, $customer);
                            
                            if (isset($authentication->active) && ! $authentication->active) {
                                $this->errors[] = Tools::displayError('Your account isn\'t available at this time, please contact us');
                            } elseif (! $authentication || ! $customer->id) {
                                $this->errors[] = Tools::displayError('Authentication failed.');
                            } else {
                                $this->context->updateCustomer($authentication);
                                
                                Hook::exec('actionAuthentication', ['customer' => $this->context->customer]);
                                
                                CartRule::autoRemoveFromCart($this->context);
                                CartRule::autoAddToCart($this->context);
                                                                
                                if (Tools::getValue('action') == 'fromCheckout' && isset($this->context->cookie->amz_connect_order)) {
                                    AmzPayments::switchOrderToCustomer($this->context->customer->id, $this->context->cookie->amz_connect_order, true);
                                }
                                if (Tools::getValue('action') == 'checkout') {
                                    $goto = $this->context->link->getModuleLink('amzpayments', 'amzpayments');
                                } elseif (Tools::getValue('action') == 'fromCheckout') {
                                    $goto = 'index.php?controller=history';
                                } elseif ($this->context->cart->nbProducts()) {
                                    $goto = 'index.php?controller=cart';
                                } else {
                                    if (Configuration::get('PS_SSL_ENABLED')) {
                                        $goto = _PS_BASE_URL_SSL_ . __PS_BASE_URI__;
                                    } else {
                                        $goto = _PS_BASE_URL_ . __PS_BASE_URI__;
                                    }
                                }
                                
                                if (Tools::getValue('method') == 'redirectAuthentication') {
                                    Tools::redirect($goto);
                                } else {
                                    echo $goto;
                                }
                            }
                        } else {
                            if (AmazonPaymentsCustomerHelper::findByEmailAddress($customer_email)) {
                                $this->context->cookie->amzConnectEmail = $customer_email;
                                $this->context->cookie->amzConnectCustomerId = $customer_userid;
                                $goto = $this->context->link->getModuleLink('amzpayments', 'connectaccounts');
                                if (Tools::getValue('action') && Tools::getValue('action') == 'checkout') {
                                    if (strpos($goto, '?') > 0) {
                                        $goto .= '&checkout=1';
                                    } else {
                                        $goto .= '?checkout=1';
                                    }
                                }
                                if (Tools::getValue('action') && Tools::getValue('action') == 'fromCheckout') {
                                    if (strpos($goto, '?') > 0) {
                                        $goto .= '&fromCheckout=1';
                                    } else {
                                        $goto .= '?fromCheckout=1';
                                    }
                                }
                                if (Tools::getValue('method') == 'redirectAuthentication') {
                                    Tools::redirect($goto);
                                } else {
                                    echo $goto;
                                }
                            } else {
                                // Customer does not exist - Create account
                                Hook::exec('actionBeforeSubmitAccount');
                                $this->create_account = true;
                                $_POST['passwd'] = md5(time() . _COOKIE_KEY_);
                                
                                $firstname = '';
                                $lastname = '';
                                $customer_name = preg_replace('/[^A-Za-z ]/', "", $customer_name);
                                if (strpos(trim($customer_name), ' ') !== false) {
                                    list ($firstname, $lastname) = explode(' ', trim($customer_name));
                                } elseif (strpos(trim($customer_name), '-') !== false) {
                                    list ($firstname, $lastname) = explode('-', trim($customer_name));
                                } else {
                                    $firstname = trim($customer_name);
                                    $lastname = '-';
                                }
                                
                                $customer_names_error = self::$amz_payments->customerNamesError($firstname, $lastname);
                                
                                $customer = new Customer();
                                $customer->email = $customer_email;
                                $customer->lastname = $lastname;
                                $customer->firstname = $firstname;
                                $lastname_address = $lastname;
                                $firstname_address = $firstname;
                                $_POST['lastname'] = Tools::getValue('customer_lastname', $lastname_address);
                                $_POST['firstname'] = Tools::getValue('customer_firstname', $firstname_address);
                                $_POST['optin'] = Tools::getValue('optin', '0');
                                $_POST['newsletter'] = Tools::getValue('newsletter', '0');
                                // $addresses_types = array('address');
                                
                                $this->errors = array_unique(array_merge($this->errors, $customer->validateController()));
                                
                                // Check the requires fields which are settings in the BO
                                $this->errors = $this->errors + $customer->validateFieldsRequiredDatabase();
                                
                                if (! count($this->errors)) {
                                    $customer->firstname = Tools::ucwords($customer->firstname);
                                    $customer->lastname = Tools::ucwords($customer->lastname);
                                    $customer->is_guest = 0;
                                    $customer->active = 1;
                                    
                                    if (! count($this->errors)) {
                                        if ($customer->add()) {
                                            if (! $customer->is_guest) {
                                                if (! $this->sendConfirmationMail($customer)) {
                                                    $this->errors[] = Tools::displayError('The email cannot be sent.');
                                                }
                                            }
                                            
                                            AmazonPaymentsCustomerHelper::saveCustomersAmazonReference($customer, $customer_userid);
                                            
                                            $this->context->updateCustomer($customer);
                                            
                                            Hook::exec('actionAuthentication', ['customer' => $this->context->customer]);
                                            
                                            CartRule::autoRemoveFromCart($this->context);
                                            CartRule::autoAddToCart($this->context);
                                                                                        
                                            if (Tools::getValue('action') == 'fromCheckout' && isset($this->context->cookie->amz_connect_order)) {
                                                AmzPayments::switchOrderToCustomer($customer->id, $this->context->cookie->amz_connect_order, true);
                                            }
                                            
                                            if (Tools::getValue('action') == 'checkout') {
                                                $goto = $this->context->link->getModuleLink('amzpayments', 'amzpayments');
                                            } elseif (Tools::getValue('action') == 'fromCheckout') {
                                                $goto = 'index.php?controller=history';
                                            } else {
                                                $goto = $this->context->link->getModuleLink('amzpayments', 'select_address');
                                            }
                                            
                                            if ($customer_names_error && (int)self::$amz_payments->amz_force_name_completion == 1) {
                                                $this->context->cookie->amzEditIdentity = true;
                                                $goto = $this->context->link->getModuleLink('amzpayments', 'personaldata');
                                            }
                                            
                                            if (Tools::getValue('method') == 'redirectAuthentication') {
                                                Tools::redirect($goto);
                                            } else {
                                                echo $goto;
                                            }
                                        } else {
                                            $this->errors[] = Tools::displayError('An error occurred while creating your account.');
                                        }
                                    }
                                } else {
                                    self::$amz_payments->exceptionLog(false, 'user_to_shop controller: Error validating customers informations ' ."\r\n\r\n" . print_r($this->errors, true));
                                    die('error');
                                }
                            }
                        }
                        die();
                }
            }
        }
    }

    public function initContent()
    {
        parent::initContent();
        die('');
    }

    protected function sendConfirmationMail(Customer $customer)
    {
        if (! Configuration::get('PS_CUSTOMER_CREATION_EMAIL')) {
            return true;
        }
        
        return Mail::Send($this->context->language->id, 'account', Mail::l('Welcome!'), array(
            '{firstname}' => $customer->firstname,
            '{lastname}' => $customer->lastname,
            '{email}' => $customer->email,
            '{passwd}' => Tools::getValue('passwd')
        ), $customer->email, $customer->firstname . ' ' . $customer->lastname);
    }
}
