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

class AmzpaymentsConnectaccountsModuleFrontController extends ModuleFrontController
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
        
        $this->setTemplate('module:amzpayments/views/templates/front/connect_accounts.tpl');
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
                    $this->context->updateCustomer($customer);
                    
                    AmazonPaymentsCustomerHelper::saveCustomersAmazonReference($customer, $this->context->cookie->amzConnectCustomerId);

                    if (Tools::getValue('fromCheckout') == '1' && isset($this->context->cookie->amz_connect_order)) {
                        AmzPayments::switchOrderToCustomer($this->context->customer->id, $this->context->cookie->amz_connect_order, true);
                    }
                    
                    CartRule::autoRemoveFromCart($this->context);
                    CartRule::autoAddToCart($this->context);

                    if (Tools::getValue('toCheckout') == '1') {
                        $goto = $this->context->link->getModuleLink('amzpayments', 'amzpayments');
                    } elseif (Tools::getValue('fromCheckout') == '1') {
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
                    
                    Tools::redirect($goto);
                }
            }
        }
    }
}
