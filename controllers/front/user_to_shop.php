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

class AmzpaymentsUser_To_ShopModuleFrontController extends ModuleFrontController
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
                        if (Tools::getValue('access_token')) {
                            if (Tools::getValue('access_token') != 'undefined') {
                                $this->context->cookie->amz_access_token = AmzPayments::prepareCookieValueForPrestaShopUse(Tools::getValue('access_token'));
                                $this->context->cookie->amz_access_token_set_time = time();
                            }
                        } else {
                            if (Tools::getValue('method') == 'redirectAuthentication') {
                                Tools::redirect('index');
                            } else {
                                error_log('Error, method not submitted and no token');
                                die('error');
                            }
                        }
                        if (Tools::getValue('action') == 'fromCheckout') {
                            $accessTokenValue = AmzPayments::prepareCookieValueForAmazonPaymentsUse(Tools::getValue('access_token'));
                        } else {
                            $accessTokenValue = Tools::getValue('access_token');
                        }
                        
                        $d = self::$amz_payments->requestTokenInfo($accessTokenValue);
                        
                        if ($d->aud != self::$amz_payments->client_id) {
                            if (Tools::getValue('method') == 'redirectAuthentication') {
                                Tools::redirect('index');
                            } else {
                                error_log('auth error LPA');
                                die('error');
                            }
                        }
                        
                        $d = self::$amz_payments->requestProfile($accessTokenValue);
                        
                        $customer_userid = $d->user_id;
                        $customer_name = $d->name;
                        $customer_email = $d->email;
                        // $postcode = $d->postal_code;
                        
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
                                $this->context->cookie->id_compare = isset($this->context->cookie->id_compare) ? $this->context->cookie->id_compare : CompareProduct::getIdCompareByIdCustomer($customer->id);
                                $this->context->cookie->id_customer = (int) $customer->id;
                                $this->context->cookie->customer_lastname = $customer->lastname;
                                $this->context->cookie->customer_firstname = $customer->firstname;
                                $this->context->cookie->logged = 1;
                                $customer->logged = 1;
                                $this->context->cookie->is_guest = $customer->isGuest();
                                $this->context->cookie->passwd = $customer->passwd;
                                $this->context->cookie->email = $customer->email;
                                
                                // Add customer to the context
                                $this->context->customer = $customer;
                                
                                if (Configuration::get('PS_CART_FOLLOWING') && (empty($this->context->cookie->id_cart) || Cart::getNbProducts($this->context->cookie->id_cart) == 0) && $id_cart = (int) Cart::lastNoneOrderedCart($this->context->customer->id)) {
                                    $this->context->cart = new Cart($id_cart);
                                } else {
                                    $id_carrier = (int) $this->context->cart->id_carrier;
                                    $this->context->cart->id_carrier = 0;
                                    $this->context->cart->setDeliveryOption(null);
                                    $this->context->cart->id_address_delivery = (int) Address::getFirstCustomerAddressId((int) $customer->id);
                                    $this->context->cart->id_address_invoice = (int) Address::getFirstCustomerAddressId((int) $customer->id);
                                }
                                $this->context->cart->id_customer = (int) $customer->id;
                                $this->context->cart->secure_key = $customer->secure_key;
                                
                                if ($this->ajax && isset($id_carrier) && $id_carrier && Configuration::get('PS_ORDER_PROCESS_TYPE')) {
                                    $delivery_option = array(
                                        $this->context->cart->id_address_delivery => $id_carrier . ','
                                    );
                                    $this->context->cart->setDeliveryOption($delivery_option);
                                }
                                
                                $this->context->cart->save();
                                $this->context->cookie->id_cart = (int) $this->context->cart->id;
                                $this->context->cookie->write();
                                $this->context->cart->autosetProductAddress();
                                
                                Hook::exec('actionAuthentication');
                                
                                // Login information have changed, so we check if the cart rules still apply
                                CartRule::autoRemoveFromCart($this->context);
                                CartRule::autoAddToCart($this->context);
                                
                                if (Tools::getValue('action') == 'fromCheckout' && isset($this->context->cookie->amz_connect_order)) {
                                    AmzPayments::switchOrderToCustomer($this->context->customer->id, $this->context->cookie->amz_connect_order, true);
                                }
                                
                                if (Tools::getValue('action') == 'checkout') {
                                    $goto = $this->context->link->getModuleLink('amzpayments', 'amzpayments', array('session' => Tools::getValue('amazon_id')));
                                } elseif (Tools::getValue('action') == 'fromCheckout') {
                                    $goto = 'index.php?controller=history';
                                } elseif ($this->context->cart->nbProducts()) {
                                    $goto = 'index.php?controller=order';
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
                                $goto = $this->context->link->getModuleLink('amzpayments', 'connect_accounts');
                                if (Tools::getValue('action') && Tools::getValue('action') == 'checkout') {
                                    if (strpos($goto, '?') > 0) {
                                        $goto .= '&checkout=1';
                                    } else {
                                        $goto .= '?checkout=1';
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
                                $customer_name = preg_replace("/[0-9]/", "", $customer_name);
                                if (strpos(trim($customer_name), ' ') !== false) {
                                    list ($firstname, $lastname) = explode(' ', trim($customer_name));
                                } elseif (strpos(trim($customer_name), '-') !== false) {
                                    list ($firstname, $lastname) = explode('-', trim($customer_name));
                                } else {
                                    $firstname = trim($customer_name);
                                    $lastname = '-';
                                }
                                
                                $customer = new Customer();
                                $customer->email = $customer_email;
                                $lastname_address = $lastname;
                                $firstname_address = $firstname;
                                $_POST['lastname'] = Tools::getValue('customer_lastname', $lastname_address);
                                $_POST['firstname'] = Tools::getValue('customer_firstname', $firstname_address);
                                // $addresses_types = array('address');
                                
                                $this->errors = array_unique(array_merge($this->errors, $customer->validateController()));
                                
                                // Check the requires fields which are settings in the BO
                                $this->errors = $this->errors + $customer->validateFieldsRequiredDatabase();
                                
                                if (! count($this->errors)) {
                                    $customer->firstname = Tools::ucwords($customer->firstname);
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
                                            
                                            $this->updateContext($customer);
                                            
                                            $this->context->cart->update();
                                            Hook::exec('actionCustomerAccountAdd', array(
                                                '_POST' => $_POST,
                                                'newCustomer' => $customer
                                            ));
                                            
                                            if (Tools::getValue('action') == 'fromCheckout' && isset($this->context->cookie->amz_connect_order)) {
                                                AmzPayments::switchOrderToCustomer($customer->id, $this->context->cookie->amz_connect_order, true);
                                            }
                                            
                                            if (Tools::getValue('action') == 'checkout') {
                                                $goto = $this->context->link->getModuleLink('amzpayments', 'amzpayments', array('session' => Tools::getValue('amazon_id')));
                                            } elseif (Tools::getValue('action') == 'fromCheckout') {
                                                $goto = 'index.php?controller=history';
                                            } else {
                                                $goto = $this->context->link->getModuleLink('amzpayments', 'select_address');
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
                                    error_log('Error validating customers informations');
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

    /**
     * Update context after customer creation
     *
     * @param Customer $customer
     *            Created customer
     */
    protected function updateContext(Customer $customer)
    {
        $this->context->customer = $customer;
        $this->context->smarty->assign('confirmation', 1);
        $this->context->cookie->id_customer = (int) $customer->id;
        $this->context->cookie->customer_lastname = $customer->lastname;
        $this->context->cookie->customer_firstname = $customer->firstname;
        $this->context->cookie->passwd = $customer->passwd;
        $this->context->cookie->logged = 1;
        // if register process is in two steps, we display a message to confirm account creation
        if (! Configuration::get('PS_REGISTRATION_PROCESS_TYPE')) {
            $this->context->cookie->account_created = 1;
        }
        $customer->logged = 1;
        $this->context->cookie->email = $customer->email;
        $this->context->cookie->is_guest = ! Tools::getValue('is_new_customer', 1);
        // Update cart address
        $this->context->cart->secure_key = $customer->secure_key;
    }

    /**
     * sendConfirmationMail
     *
     * @param Customer $customer
     * @return bool
     */
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
