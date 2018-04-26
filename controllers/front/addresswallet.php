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

class AmzpaymentsAddresswalletModuleFrontController extends ModuleFrontController
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
        if (Tools::isSubmit('ajax')) {
            if (Tools::isSubmit('method')) {
                switch (Tools::getValue('method')) {
                    case 'setsession':
                        if (Tools::getValue('access_token')) {
                            if (Tools::getValue('access_token') != 'undefined') {
                                $this->context->cookie->amz_access_token = AmzPayments::prepareCookieValueForPrestaShopUse(Tools::getValue('access_token'));
                                $this->context->cookie->amz_access_token_set_time = time();
                                die();
                            }
                        }
                }
            }
        }
    }

    public function initContent()
    {
        if (Tools::getValue('session') != '') {
            $this->context->cookie->amazon_id = Tools::getValue('session');
        }
        if (Tools::getValue('amz')) {
            $this->context->smarty->assign('amz_session', Tools::getValue('amz'));
        }
        if (isset($this->context->cookie->setHadErrorNowWallet) && $this->context->cookie->setHadErrorNowWallet == '1') {
            $this->context->smarty->assign('widgetreadonly', true);
        }
        $this->context->cart->id_address_delivery = null;
        $this->context->cart->id_address_invoice = null;
        parent::initContent();
        $this->context->smarty->assign(array(
            'ajaxSetAddressUrl' => $this->context->link->getModuleLink('amzpayments', 'select_address', array(), true),
            'sellerID' => Configuration::get('AMZ_MERCHANT_ID'),
            'back' => Tools::getValue('back')
        ));
        $this->setTemplate('module:amzpayments/views/templates/front/addresswallet.tpl');
    }
}
