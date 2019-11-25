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

class AmzpaymentsResetpaymentsModuleFrontController extends ModuleFrontController
{

    public $ssl = true;

    public $isLogged = false;

    public $display_column_left = false;

    public $display_column_right = false;
    
    protected static $amzpayments = '';

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
        self::$amzpayments = new AmzPayments();
        if (isset($this->context->cookie->amazon_id)) {
            unset($this->context->cookie->amazon_id);
        }
        if (isset($this->context->cookie->amz_access_token)) {
            unset($this->context->cookie->amz_access_token);
        }
        if (isset($this->context->cookie->amz_access_token_set_time)) {
            unset($this->context->cookie->amz_access_token_set_time);
        }
        unsetAmazonPayCookie();
        if (isset(self::$amzpayments->cookie->amz_access_token)) {
            unset(self::$amzpayments->cookie->amz_access_token);
        }
        if (isset(self::$amzpayments->cookie->amz_access_token_set_time)) {
            unset(self::$amzpayments->cookie->amz_access_token_set_time);
        }
        if (isset($this->context->cart->id_address_delivery)) {
            $this->context->cart->id_address_delivery = 0;
        }
        if (isset($this->context->cart->id_address_invoice)) {
            $this->context->cart->id_address_invoice = 0;
        }
        Tools::redirect(_PS_BASE_URL_SSL_ . __PS_BASE_URI__ . 'index.php?controller=cart&action=show');
    }
}
