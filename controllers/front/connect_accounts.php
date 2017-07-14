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

class AmzpaymentsConnect_AccountsModuleFrontController extends ModuleFrontController
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
        
        /* Disable some cache related bugs on the cart/order */
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        $this->display_column_left = false;
        $this->display_column_right = false;
        
        $this->service = self::$amz_payments->getService();
    }

    public function initContent()
    {
        parent::initContent();
        
        $this->context->smarty->assign('toCheckout', Tools::getValue('checkout'));
        $this->context->smarty->assign('fromCheckout', Tools::getValue('fromCheckout'));
        $this->context->smarty->assign('amzConnectEmail', $this->context->cookie->amzConnectEmail);
        
        $this->processForm();
        
        $this->setTemplate('connect_accounts.tpl');
    }

    protected function processForm()
    {
        if (Tools::getValue('action') == 'tryConnect') {
            if (Tools::getValue('email') == $this->context->cookie->amzConnectEmail) {
                $customer = new Customer();
                $authentication = $customer->getByEmail(trim(Tools::getValue('email')), trim(Tools::getValue('passwd')));
                
                if (isset($authentication->active) && ! $authentication->active) {
                    $this->errors[] = Tools::displayError('Your account isn\'t available at this time, please contact us');
                } elseif (! $authentication || ! $customer->id) {
                    $this->errors[] = Tools::displayError('Authentication failed.');
                } else {
                    $authentication->save();
                    AmazonPaymentsCustomerHelper::saveCustomersAmazonReference($authentication, $this->context->cookie->amzConnectCustomerId);
                    
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
                    
                    if (Tools::getValue('toCheckout') == '1') {
                        $goto = $this->context->link->getModuleLink('amzpayments', 'amzpayments');
                    } elseif (Tools::getValue('fromCheckout') == '1') {
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
                    
                    Tools::redirect($goto);
                }
            }
        }
    }
}
