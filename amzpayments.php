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

if (! defined('_PS_VERSION_')) {
    exit();
}

/**
 * ensure the __DIR__ constant is defined for PHP 4.0.6 and newer
 * (@__DIR__ == '__DIR__') && define('__DIR__', realpath(dirname(__FILE__)));
 */
define('CURRENT_MODULE_DIR', realpath(dirname(__FILE__)));

require_once(CURRENT_MODULE_DIR . '/classes/AmazonTransactions.php');
require_once(CURRENT_MODULE_DIR . '/classes/AmazonPaymentsCustomerHelper.php');
require_once(CURRENT_MODULE_DIR . '/classes/AmazonPaymentsAddressHelper.php');
require_once(CURRENT_MODULE_DIR . '/classes/AmazonPaymentsHelperForm.php');
require_once(CURRENT_MODULE_DIR . '/classes/AmazonPaymentsLogHelper.php');

class AmzPayments extends PaymentModule
{
    
    public $merchant_id;
    
    public $access_key;
    
    public $secret_key;
    
    public $client_id;
    
    public $region;
    
    public $lpa_mode;
    
    public $order_process_type = 'optimized';
    
    public $config_setting_mode = 0;
    
    public $button_visibility = 1;
    
    public $environment;
    
    public $order_status_id = 0;
    
    public $authorization_mode = 'after_checkout';
    
    public $authorized_status_id = 3;
    
    public $capture_mode = 'after_shipping';
    
    public $capture_status_id = 5;
    
    public $capture_success_status_id = 5;
    
    public $decline_status_id = 0;
    
    public $provocation = 0;
    
    public $popup = 1;
    
    public $shippings_not_allowed = '';
    
    public $products_not_allowed = '';
    
    public $allow_guests = 1;
    
    public $button_size = 'x-large';
    
    public $button_size_lpa = 'x-large';
    
    public $button_color = 'orange';
    
    public $button_color_lpa = 'Gold';
    
    public $button_color_lpa_navi = 'Gold';
    
    public $type_login = 'LwA';
    
    public $type_pay = 'PwA';
    
    public $ipn_status = 0;
    
    public $cron_status = 0;
    
    public $cron_password = '';
    
    public $send_mails_on_decline = 1;
    
    public $preselect_create_account = 0;
    
    public $force_account_creation = 0;
    
    public $template_variant_bs = 1;
    
    public $clear_cache = 1;
    
    public $hide_login_btns = 0;
    
    public $hide_minicart_button = 1;
    
    public $hide_login_on_menu = 1;
    
    public $promo_header = 0;
    
    public $promo_product = 1;
    
    public $promo_footer = 1;
    
    public $ca_bundle_file;
    
    private $_postErrors = array();
    
    private $_postSuccess = array();
    
    private $pfid = 'A1AOZCKI9MBRZA';
    
    protected static $table_columns = array();
    
    public static $config_array = array(
        'merchant_id' => 'AMZ_MERCHANT_ID',
        'access_key' => 'ACCESS_KEY',
        'secret_key' => 'SECRET_KEY',
        'client_id' => 'AMZ_CLIENT_ID',
        'region' => 'REGION',
        'config_setting_mode' => 'AMZ_CONFIG_SETTING_MODE',
        'lpa_mode' => 'LPA_MODE',
        'order_process_type' => 'AMZ_ORDER_PROCESS_TYPE',
        'button_visibility' => 'BUTTON_VISIBILITY',
        'environment' => 'ENVIRONMENT',
        'authorization_mode' => 'AUTHORIZATION_MODE',
        'order_status_id' => 'AMZ_ORDER_STATUS_ID',
        'authorized_status_id' => 'AUTHORIZED_STATUS_ID',
        'capture_mode' => 'CAPTURE_MODE',
        'capture_status_id' => 'CAPTURE_STATUS_ID',
        'capture_success_status_id' => 'CAPTURE_SUCCESS_STATUS_ID',
        'decline_status_id' => 'AMZ_DECLINE_STATUS_ID',
        'provocation' => 'PROVOCATION',
        'popup' => 'POPUP',
        'shippings_not_allowed' => 'SHIPPINGS_NOT_ALLOWED',
        'products_not_allowed' => 'PRODUCTS_NOT_ALLOWED',
        'allow_guests' => 'ALLOW_GUEST',
        'button_size_lpa' => 'BUTTON_SIZE_LPA',
        'button_color_lpa' => 'BUTTON_COLOR_LPA',
        'button_color_lpa_navi' => 'BUTTON_COLOR_LPA_NAVI',
        'type_login' => 'TYPE_LOGIN',
        'type_pay' => 'TYPE_PAY',
        'ipn_status' => 'IPN_STATUS',
        'cron_status' => 'CRON_STATUS',
        'cron_password' => 'CRON_PASSWORD',
        'send_mails_on_decline' => 'SEND_MAILS_ON_DECLINE',
        'preselect_create_account' => 'PRESELECT_CREATE_ACCOUNT',
        'force_account_creation' => 'FORCE_ACCOUNT_CREATION',
        'template_variant_bs' => 'TEMPLATE_VARIANT_BS',
        'clear_cache' => 'AMZ_CLEAR_CACHE',
        'hide_login_btns' => 'AMZ_HIDE_LOGIN_BTNS',
        'hide_login_on_menu' => 'AMZ_HIDE_LOGIN_ON_MENU',
        'hide_minicart_button' => 'AMZ_HIDE_MINICART_BUTTON',
        'promo_header' => 'AMZ_PROMO_HEADER',
        'promo_header_style' => 'AMZ_PROMO_HEADER_STYLE',
        'promo_product' => 'AMZ_PROMO_PRODUCT',
        'promo_product_style' => 'AMZ_PROMO_PRODUCT_STYLE',
        'promo_footer' => 'AMZ_PROMO_FOOTER',
        'promo_footer_style' => 'AMZ_PROMO_FOOTER_STYLE',
    );
    
    public function __construct()
    {
        $this->name = 'amzpayments';
        $this->tab = 'payments_gateways';
        $this->version = '2.2.1';
        $this->author = 'patworx multimedia GmbH';
        $this->need_instance = 1;
        
        $this->bootstrap = true;
        $this->module_key = '26d778fa5cb6735a816107ce4345b32d';
        
        $this->ps_versions_compliancy = array(
            'min' => '1.6',
            'max' => _PS_VERSION_
        );
        $this->dependencies = array();
        $this->is_eu_compatible = 1;
        
        $this->has_curl = function_exists('curl_version');
        
        $this->reloadConfigVars();
        
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        
        parent::__construct();
        
        $this->displayName = $this->l('Payments Advanced');
        $this->description = $this->l('Simple integration of Amazon Payments for your prestaShop.');
        
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        
        if (! isset($this->merchant_id) || ! isset($this->access_key) || ! isset($this->secret_key) || ! isset($this->region) || ! isset($this->environment)) {
            $this->warning = $this->l('Your Amazon Payments details must be configured before using this module.');
        }
        if (! count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this payment module');
        }
        
        if (isset($this->context->cookie->amz_access_token_set_time)) {
            if ($this->context->cookie->amz_access_token_set_time < time() - 3000) {
                unset($this->context->cookie->amz_access_token);
            }
        }
    }
    
    private function reloadConfigVars()
    {
        $config = Configuration::getMultiple(self::$config_array);
        foreach (self::$config_array as $class_var => $config_var) {
            if (isset($config[$config_var])) {
                $this->$class_var = $config[$config_var];
            }
        }
    }
    
    public function getService($override = false, $serviceType = 'service')
    {
        include_once(CURRENT_MODULE_DIR . '/vendor/config.php');
        include_once(CURRENT_MODULE_DIR . '/vendor/functions.php');
        
        $config = array();
        $config['environment'] = Tools::strtolower($this->environment);
        $config['merchantId'] = $this->merchant_id;
        $config['accessKey'] = $this->access_key;
        $config['secretKey'] = $this->secret_key;
        
        $config['applicationName'] = $this->name;
        $config['applicationVersion'] = $this->version;
        $config['region'] = $this->region;
        $config['serviceURL'] = '';
        $config['widgetURL'] = '';
        $config['caBundleFile'] = CURRENT_MODULE_DIR . '/vendor/ca-bundle.crt';
        $config['clientId'] = '';
        $config['cnName'] = 'sns.amazonaws.com';
        
        $this->ca_bundle_file = $config['caBundleFile'];
        
        if ($override && is_array($override)) {
            foreach ($override as $k => $v) {
                $config[$k] = $v;
            }
        }
        
        if ($serviceType == 'service') {
            return new OffAmazonPaymentsService_Client($config);
        } elseif ($serviceType == 'notification') {
            return new OffAmazonPaymentsNotifications_Client($config);
        }
    }
    
    public function getPfId()
    {
        return $this->pfid;
    }
    
    public function install()
    {
        if (version_compare(phpversion(), '5.3.0', '<')) {
            return false;
        }
        
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
        
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amz_transactions`;');
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amz_orders`;');
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amz_address`;');
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amz_customer`;');
        
        Db::getInstance()->execute('
                CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amz_transactions` (
                `amz_tx_id` int(11) NOT NULL AUTO_INCREMENT,
                `amz_tx_order_reference` varchar(255) NOT NULL,
                `amz_tx_type` varchar(16) NOT NULL,
                `amz_tx_time` int(11) NOT NULL,
                `amz_tx_expiration` varchar(255) NOT NULL,
                `amz_tx_amount` float NOT NULL,
                `amz_tx_amount_refunded` float NOT NULL,
                `amz_tx_status` varchar(32) NOT NULL,
                `amz_tx_reference` varchar(255) NOT NULL,
                `amz_tx_code` varchar(64) NOT NULL,
                `amz_tx_amz_id` varchar(255) NOT NULL,
                `amz_tx_customer_informed` int(11) NOT NULL,
                `amz_tx_last_change` int(11) NOT NULL,
                `amz_tx_last_update` int(11) NOT NULL,
                `amz_tx_order` int(11) NOT NULL,
                PRIMARY KEY (`amz_tx_id`),
                KEY `amz_tx_order_reference` (`amz_tx_order_reference`),
                KEY `amz_tx_type` (`amz_tx_type`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
                ');
        
        Db::getInstance()->execute('
                CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amz_orders` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_order` int(11) NOT NULL,
                `amazon_auth_reference_id` varchar(255) NOT NULL,
                `amazon_authorization_id` varchar(255) NOT NULL,
                `amazon_order_reference_id` varchar(255) NOT NULL,
                `amazon_capture_id` varchar(255) NOT NULL,
                `amazon_capture_reference_id` varchar(255) NOT NULL,
                PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
                ');
        
        Db::getInstance()->execute('
                CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amz_address` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_address` int(11) NOT NULL,
                `amazon_hash` varchar(255) NOT NULL,
                `amazon_order_reference_id` varchar(255) NOT NULL,
                PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
                ');
        
        Db::getInstance()->execute('
                CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amz_customer` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_customer` int(11) NOT NULL,
                `amazon_customer_id` varchar(255) NOT NULL,
                PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
                ');
        
        $this->installOrderStates();
        
        $this->resetDefaultSettings();
        
        return parent::install() &&
        $this->registerHook('displayFooter') &&
        $this->registerHook('displayProductButtons') &&
        $this->registerHook('displayBanner') &&
        $this->registerHook('displayTopColumn') &&
        $this->registerHook('actionCarrierUpdate') &&
        $this->registerHook('displayBackOfficeHeader') &&
        $this->registerHook('displayShoppingCartFooter') &&
        $this->registerHook('displayNav') &&
        $this->registerHook('adminOrder') &&
        $this->registerHook('updateOrderStatus') &&
        $this->registerHook('displayBackOfficeFooter') &&
        $this->registerHook('displayPayment') &&
        $this->registerHook('paymentReturn') &&
        $this->registerHook('payment') &&
        $this->registerHook('displayPaymentEU') &&
        $this->registerHook('actionDispatcher') &&
        $this->registerHook('header');
    }
    
    protected function resetDefaultSettings()
    {
        Configuration::updateValue('BUTTON_VISIBILITY', true);
        Configuration::updateValue('POPUP', true);
        Configuration::updateValue('ALLOW_GUEST', true);
        Configuration::updateValue('ENVIRONMENT', 'LIVE');
        Configuration::updateValue('REGION', $this->getDefaultModuleRegion());
        Configuration::updateValue('BUTTON_SIZE', 'medium');
        Configuration::updateValue('BUTTON_SIZE_LPA', 'medium');
        Configuration::updateValue('AMZ_CLEAR_CACHE', true);
        Configuration::updateValue('TEMPLATE_VARIANT_BS', true);
        Configuration::updateValue('IPN_STATUS', true);
        Configuration::updateValue('AMZ_HIDE_LOGIN_BTNS', false);
        Configuration::updateValue('AMZ_HIDE_LOGIN_ON_MENU', true);
        Configuration::updateValue('AMZ_HIDE_MINICART_BUTTON', true);
        Configuration::updateValue('AMZ_PROMO_HEADER', false);
        Configuration::updateValue('AMZ_PROMO_PRODUCT', true);
        Configuration::updateValue('AMZ_PROMO_FOOTER', true);
        Configuration::updateValue('AMZ_PROMO_HEADER_STYLE', false);
        Configuration::updateValue('AMZ_PROMO_PRODUCT_STYLE', false);
        Configuration::updateValue('AMZ_PROMO_FOOTER_STYLE', false);
        Configuration::updateValue('CAPTURE_MODE', 'after_auth');
        Configuration::updateValue('LPA_MODE', 'login_pay');
        Configuration::updateValue('AMZ_ORDER_PROCESS_TYPE', 'optimized');
    }
    
    protected function installOrderStates()
    {
        $values_to_insert = array(
            'invoice' => 0,
            'send_email' => 0,
            'module_name' => pSQL($this->name),
            'color' => 'RoyalBlue',
            'unremovable' => 0,
            'hidden' => 0,
            'logable' => 1,
            'delivery' => 0,
            'shipped' => 0,
            'paid' => 0,
            'deleted' => 0
        );
        if (! Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state', $values_to_insert, 'INSERT')) {
            return false;
        }
        $id_order_state = (int) Db::getInstance()->Insert_ID();
        $languages = Language::getLanguages(false);
        foreach ($languages as $language) {
            Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state_lang', array(
                'id_order_state' => $id_order_state,
                'id_lang' => (int)$language['id_lang'],
                'name' => pSQL($this->l('Amazon Payments - Authorized')),
                'template' => ''
            ), 'INSERT');
        }
        Configuration::updateValue('AUTHORIZED_STATUS_ID', $id_order_state);
        unset($id_order_state);
        
        $values_to_insert = array(
            'invoice' => 0,
            'send_email' => 0,
            'module_name' => pSQL($this->name),
            'color' => 'RoyalBlue',
            'unremovable' => 0,
            'hidden' => 0,
            'logable' => 1,
            'delivery' => 0,
            'shipped' => 0,
            'paid' => 1,
            'deleted' => 0
        );
        if (! Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state', $values_to_insert, 'INSERT')) {
            return false;
        }
        $id_order_state = (int) Db::getInstance()->Insert_ID();
        $languages = Language::getLanguages(false);
        foreach ($languages as $language) {
            Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state_lang', array(
                'id_order_state' => $id_order_state,
                'id_lang' => (int)$language['id_lang'],
                'name' => pSQL($this->l('Amazon Payments - Payment received')),
                'template' => ''
            ), 'INSERT');
        }
        Configuration::updateValue('CAPTURE_STATUS_ID', $id_order_state);
        unset($id_order_state);
    }
    
    public function checkTableForColumn($table, $column)
    {
        if (! isset(self::$table_columns[$table][$column])) {
            $res = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . pSQL($table) . '` LIKE \'' . pSQL($column) . '\'');
            if ($res) {
                self::$table_columns[$table][$column] = true;
            } else {
                self::$table_columns[$table][$column] = false;
            }
        }
        return self::$table_columns[$table][$column];
    }
    
    public function uninstall()
    {
        if (! Configuration::deleteByName('AMZ_MERCHANT_ID') || ! Configuration::deleteByName('ACCESS_KEY') || ! Configuration::deleteByName('SECRET_KEY') || ! Configuration::deleteByName('REGION') || ! Configuration::deleteByName('BUTTON_VISIBILITY') || ! Configuration::deleteByName('ENVIRONMENT') || ! Configuration::deleteByName('AMZ_DECLINE_STATUS_ID') || ! Configuration::deleteByName('AUTHORIZATION_MODE') || ! Configuration::deleteByName('CAPTURE_MODE') || ! Configuration::deleteByName('CAPTURE_STATUS_ID') || ! parent::uninstall()) {
            return false;
        }
        return true;
    }
    
    private function _postValidation()
    {
        if (Tools::isSubmit('submitAmzpaymentsModule') || Tools::isSubmit('submitAmzpaymentsModuleConnect')) {
            foreach (self::$config_array as $name => $f) {
                if (Tools::getValue($f) === false && !in_array($f, array('SHIPPINGS_NOT_ALLOWED', 'PROVOCATION', 'PRODUCTS_NOT_ALLOWED', 'AMZ_HIDE_LOGIN_BTNS', 'AMZ_HIDE_MINICART_BUTTON', 'AMZ_PROMO_HEADER'))) {
                    $this->_postErrors[] = $this->l($name) . ' ' . $this->l(': details are required.');
                }
            }
            if (Tools::getValue('REGION') == '') {
                $this->_postErrors[] = $this->l('Region is wrong.');
            } elseif (Tools::getValue('AMZ_MERCHANT_ID') == '' || Tools::getValue('ACCESS_KEY') == '' || Tools::getValue('SECRET_KEY') == '') {
                if (Tools::getValue('AMZ_MERCHANT_ID') == '') {
                    $this->_postErrors[] = $this->l('Merchant ID is wrong.');
                }
                if (Tools::getValue('ACCESS_KEY') == '') {
                    $this->_postErrors[] = $this->l('MWS Access Key is wrong.');
                }
                if (Tools::getValue('SECRET_KEY') == '') {
                    $this->_postErrors[] = $this->l('MWS Secret Key is wrong.');
                }
            } else {
                $service = $this->getService(array(
                    'merchantId' => Tools::getValue('AMZ_MERCHANT_ID'),
                    'accessKey' => Tools::getValue('ACCESS_KEY'),
                    'environment' => Tools::getValue('ENVIRONMENT'),
                    'authorization_mode' => Tools::getValue('AUTHORIZATION_MODE'),
                    'capture_mode' => Tools::getValue('CAPTURE_MODE'),
                    'capture_status_id' => Tools::getValue('CAPTURE_STATUS_ID'),
                    'region' => Tools::getValue('REGION'),
                    'secretKey' => Tools::getValue('SECRET_KEY')
                ));
                $order_ref_request = new OffAmazonPaymentsService_Model_GetOrderReferenceDetailsRequest();
                $order_ref_request->setSellerId(Tools::getValue('AMZ_MERCHANT_ID'));
                $order_ref_request->setAmazonOrderReferenceId('S00-0000000-0000000');
                $this->context->smarty->assign('keys_valid', true);
                try {
                    $service->getOrderReferenceDetails($order_ref_request);
                } catch (OffAmazonPaymentsService_Exception $e) {
                    switch ($e->getErrorCode()) {
                        case 'InvalidAccessKeyId':
                            $this->_postErrors[] = $this->l('MWS Access Key is wrong.');
                            $this->context->smarty->assign('keys_valid', false);
                            break;
                            
                        case 'SignatureDoesNotMatch':
                            $this->_postErrors[] = $this->l('MWS Secret Key is wrong.');
                            $this->context->smarty->assign('keys_valid', false);
                            break;
                            
                        case 'InvalidParameterValue':
                            if (strpos($e->getErrorMessage(), 'Invalid seller id') !== false) {
                                $this->_postErrors[] = $this->l('Merchant ID is wrong.');
                                $this->context->smarty->assign('keys_valid', false);
                            }
                            break;
                    }
                }
            }
        }
        if (sizeof($this->_postErrors) > 0) {
            $this->exceptionLog(false, "BE Settings: \r\n" . print_r($this->_postErrors, true));
        }
    }
    
    private function _postProcess()
    {
        if (Tools::isSubmit('submitAmzpaymentsModule') || Tools::isSubmit('submitAmzpaymentsModuleConnect')) {
            foreach (self::$config_array as $f => $conf_key) {
                if ($conf_key == 'SHIPPINGS_NOT_ALLOWED') {
                    $carriers_set = array();
                    foreach ($this->getCarrierOptionsPostNames() as $pname) {
                        if (Tools::getValue($pname)) {
                            $carrier_set = Tools::str_replace_once('SHIPPINGS_NOT_ALLOWED_carrier_', '', $pname);
                            $carrier_set = Tools::str_replace_once('_on', '', $carrier_set);
                            $carriers_set[] = (int)$carrier_set;
                        }
                    }
                    if (sizeof($carriers_set) > 0) {
                        Configuration::updateValue($conf_key, join(",", $carriers_set));
                    } else {
                        Configuration::updateValue($conf_key, '');
                    }
                } else {
                    Configuration::updateValue($conf_key, trim(Tools::getValue($conf_key)));
                }
            }
        }
        $this->_postSuccess[] = $this->l('Settings updated');
    }
    
    private function _displayForm()
    {
        $helper = new AmazonPaymentsHelperForm();
        
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAmzpaymentsModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        
        return $helper->generateAmazonForm($this->context->smarty, $this->getConfigForm());
    }
    
    protected function getPossibleRegionEntries()
    {
        return 'DE, AT, UK, US, FR, IT, ES, JP';
    }
    
    protected function getCronURL()
    {
        return $this->context->link->getModuleLink('amzpayments', 'cron.php', array('pw' => $this->cron_password));
    }
    
    protected function getIPNURL()
    {
        return str_replace('http://', 'https://', $this->context->link->getModuleLink('amzpayments', 'ipn', array()));
    }
    
    protected function getAllowedReturnUrls($type = 1, $joined = false)
    {
        $urls = array();
        $shop_id = isset(Context::getContext()->shop) ? Context::getContext()->shop->id : false;
        $language_ids = Language::getLanguages(true, $shop_id, true);
        foreach ($language_ids as $id_lang) {
            $url = str_replace('http://', 'https://', $this->context->link->getModuleLink('amzpayments', 'process_login', array(), null, (int)$id_lang));
            if ($type == 2) {
                if (strpos($url, '?') > 0) {
                    $url .= '&toCheckout=1';
                } else {
                    $url .= '?toCheckout=1';
                }
            }
            $urls[] = $url;
        }
        if ($joined) {
            return join($joined, $urls);
        } else {
            return $urls;
        }
    }
    
    protected function getDomainForWhitelist()
    {
        $main_url = str_replace(array('http://', 'https://'), '', $this->context->link->getModuleLink('amzpayments', 'ipn', array()));
        return 'https://' . Tools::substr($main_url, 0, Tools::strpos($main_url, '/'));
    }
    
    public function getConfigFormValues()
    {
        $return = array();
        foreach (self::$config_array as $name => $key) {
            $return[$key] = Configuration::get($key);
        }
        $return = $this->addDisabledCarrierOptions($return);
        return $return;
    }
    
    public function getConfigFormValuesForDebug()
    {
        $vars = $this->getConfigFormValues();
        unset($vars['SECRET_KEY']);
        return $vars;
    }
    
    public function getConfigForm()
    {
        $connect_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Connect'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'REGION',
                        'label' => $this->l('region'),
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_region' => 'FR',
                                    'name' => $this->l('France')
                                ),
                                array(
                                    'id_region' => 'DE',
                                    'name' => $this->l('Germany')
                                ),
                                array(
                                    'id_region' => 'UK',
                                    'name' => $this->l('United Kingdom')
                                ),
                                array(
                                    'id_region' => 'US',
                                    'name' => $this->l('USA')
                                ),
                                array(
                                    'id_region' => 'IT',
                                    'name' => $this->l('Italy')
                                ),
                                array(
                                    'id_region' => 'ES',
                                    'name' => $this->l('Spain')
                                ),
                                array(
                                    'id_region' => 'JP',
                                    'name' => $this->l('Japan')
                                ),
                                array(
                                    'id_region' => 'AT',
                                    'name' => $this->l('Austria')
                                ),
                            ),
                            'id' => 'id_region',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'AMZ_MERCHANT_ID',
                        'label' => $this->l('Merchant ID')
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'ACCESS_KEY',
                        'label' => $this->l('Access Key ID')
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'SECRET_KEY',
                        'label' => $this->l('Secret Access Key')
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'AMZ_CLIENT_ID',
                        'label' => $this->l('Client ID')
                    ),
                )
            )
        );
        
        $display_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Amazon Pay Display'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'LPA_MODE',
                        'hint' => $this->l('"Login and Pay" allows users to login and pay using their Amazon account. "Pay only" allows users to pay using their Amazon account.'),
                        'label' => $this->l('Mode'),
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_lpa_mode' => 'login_pay',
                                    'name' => $this->l('Login and Pay')
                                ),
                                array(
                                    'id_lpa_mode' => 'pay',
                                    'name' => $this->l('Pay')
                                ),
                                /*array(
                                 'id_lpa_mode' => 'login',
                                 'name' => $this->l('Login')
                                 ),*/
                            ),
                            'id' => 'id_lpa_mode',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Login in pop-up window'),
                        'hint' => $this->l('This option determines whether your customer is presented with a pop-up window to authenticate (recommended) or if the customer is instead redirected to an Amazon Pay page to authenticate.'),
                        'name' => 'POPUP',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on_popup',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off_popup',
                                'value' => '0',
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Show buttons'),
                        'name' => 'BUTTON_VISIBILITY',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on_bv',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off_bv',
                                'value' => '0',
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Debug mode'),
                        'hint' => $this->l('Enabling this option hides the Amazon Pay and Login with Amazon buttons without deactivating the plugin.  (Default: No)'),
                        'name' => 'AMZ_HIDE_LOGIN_BTNS',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on_hide_lgn_btns',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off_hide_lgn_btns',
                                'value' => '0',
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Hide Button in Frontend in MiniCart'),
                        'name' => 'AMZ_HIDE_MINICART_BUTTON',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on_hide_minicart_btns',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off_hide_minicart_btns',
                                'value' => '0',
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Hide Button in Frontend on Menu bar'),
                        'name' => 'AMZ_HIDE_LOGIN_ON_MENU',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on_hide_login_menu',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off_hide_login_menu',
                                'value' => '0',
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                )
            )
        );
        
        $config_setting_form = array(
            'form' => array(
                'input' => array(
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Configuration mode'),
                        'name' => 'AMZ_CONFIG_SETTING_MODE',
                        'is_bool' => 'true',
                        'values' => array(
                            array(
                                'id' => 'AMZ_CONFIG_SETTING_MODE_off',
                                'value' => 0,
                                'label' => $this->l('Standard Configuration (fits most shops)')
                            ),
                            array(
                                'id' => 'AMZ_CONFIG_SETTING_MODE_on',
                                'value' => 1,
                                'label' => $this->l('Advanced Configuration')
                            )
                        )
                    )
                )
            )
        );
        
        $payment_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Payment Transactions'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'hint' => $this->l('"Synchronous" recommended for an average basket below 300€. "Asynchronous" recommended for an average basket above 300€.'),
                        'name' => 'AUTHORIZATION_MODE',
                        'label' => $this->l('Authorization processing mode'),
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_lpa_auth_mode' => 'fast_auth',
                                    'name' => $this->l('Synchronous')
                                ),
                                array(
                                    'id_lpa_auth_mode' => 'after_checkout',
                                    'name' => $this->l('Asynchronous')
                                ),
                                array(
                                    'id_lpa_auth_mode' => 'manually',
                                    'name' => $this->l('Manual')
                                )
                            ),
                            'id' => 'id_lpa_auth_mode',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'col' => 3,
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'CAPTURE_MODE',
                        'label' => $this->l('Capture mode'),
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_lpa_capt_mode' => 'after_shipping',
                                    'name' => $this->l('On shipment')
                                ),
                                array(
                                    'id_lpa_capt_mode' => 'after_auth',
                                    'name' => $this->l('On order')
                                ),
                                array(
                                    'id_lpa_capt_mode' => 'manually',
                                    'name' => $this->l('Manual')
                                )
                            ),
                            'id' => 'id_lpa_capt_mode',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'col' => 3,
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'AMZ_ORDER_STATUS_ID',
                        'label' => $this->l('Status after order'),
                        'options' => array(
                            'query' => array_merge(array(array('id_order_state' => 0, 'id_lang' => (int) Configuration::get('PS_LANG_DEFAULT'), 'name' => '')), OrderState::getOrderStates((int) Configuration::get('PS_LANG_DEFAULT'))),
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'col' => 3,
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'AUTHORIZED_STATUS_ID',
                        'label' => $this->l('Order status for authorized payments'),
                        'options' => array(
                            'query' => OrderState::getOrderStates((int) Configuration::get('PS_LANG_DEFAULT')),
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'col' => 3,
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'CAPTURE_STATUS_ID',
                        'label' => $this->l('Order status for shipped orders'),
                        'options' => array(
                            'query' => OrderState::getOrderStates((int) Configuration::get('PS_LANG_DEFAULT')),
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'col' => 3,
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'CAPTURE_SUCCESS_STATUS_ID',
                        'label' => $this->l('Order status for successful captures'),
                        'options' => array(
                            'query' => OrderState::getOrderStates((int) Configuration::get('PS_LANG_DEFAULT')),
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'col' => 3,
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'AMZ_DECLINE_STATUS_ID',
                        'label' => $this->l('Order status for declined payments'),
                        'options' => array(
                            'query' => array_merge(array(array('id_order_state' => 0, 'id_lang' => (int) Configuration::get('PS_LANG_DEFAULT'), 'name' => '')), OrderState::getOrderStates((int) Configuration::get('PS_LANG_DEFAULT'))),
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Inform the customer when the payment is rejected'),
                        'name' => 'SEND_MAILS_ON_DECLINE',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on_send_decline',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off_send_decline',
                                'value' => '0',
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable Instant Payment Notifications (IPN)'),
                        'name' => 'IPN_STATUS',
                        'hint' => $this->l('With IPNs enabled, your shop receives changes to the payment status in real time.'),
                        'is_bool' => true,
                        'desc' => $this->l('Use this URL for IPN: ') . ' ' . $this->getIPNURL(),
                        'values' => array(
                            array(
                                'id' => 'active_on_ipn',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off_ipn',
                                'value' => '0',
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable cron job'),
                        'name' => 'CRON_STATUS',
                        'hint' => $this->l('With cron job enabled, your shop polls changes to the payment status on a scheduled basis.'),
                        'is_bool' => true,
                        'desc' => $this->l('Use this URL for your cronjob: ') . ' ' . $this->getCronURL(),
                        'values' => array(
                            array(
                                'id' => 'active_on_cron',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off_cron',
                                'value' => '0',
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'hint' => $this->l('Enter a password for the cron job.'),
                        'name' => 'CRON_PASSWORD',
                        'label' => $this->l('Password for cron job')
                    ),
                )
            )
        );
        
        $account_mngmtn = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('PrestaShop Account Management'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable account creation when customers sign in or pay'),
                        'name' => 'PRESELECT_CREATE_ACCOUNT',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on_preselect',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off_preselect',
                                'value' => '0',
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Force account creation'),
                        'name' => 'FORCE_ACCOUNT_CREATION',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on_force',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off_force',
                                'value' => '0',
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable guest orders'),
                        'name' => 'ALLOW_GUEST',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on_guests',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off_guests',
                                'value' => '0',
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                )
            )
        );
        
        $misc_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Miscellaneous'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'checkbox',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'hint' => $this->l('Excluded shipping methods are removed from the list of options available when using Amazon Pay.'),
                        'name' => 'SHIPPINGS_NOT_ALLOWED',
                        'label' => $this->l('Exclude shipping methods'),
                        'values' => array('query' => $this->getCarrierOptions(),
                            'id' => 'id',
                            'name' => 'label',
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Bootstrap template'),
                        'name' => 'TEMPLATE_VARIANT_BS',
                        'hint' => $this->l('Activate this option if you use a theme in your PrestaShop based on Bootstrap (default).'),
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on_tpl_variant_bs',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off_tpl_variant_bs',
                                'value' => '0',
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Clear cache automatically after saving'),
                        'name' => 'AMZ_CLEAR_CACHE',
                        'hint' => $this->l('Clearing cache is recommended after an update or a change in configuration.'),
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on_clear_cache',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off_clear_cache',
                                'value' => '0',
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'col' => 3,
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'AMZ_ORDER_PROCESS_TYPE',
                        'hint' => $this->l('Optimized checkout enables one page checkout with Amazon Pay. Please ensure all third party modules are compatible before activating this option.'),
                        'label' => $this->l('Order process type'),
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_order_process_type' => 'optimized',
                                    'name' => $this->l('Optimized')
                                ),
                                array(
                                    'id_order_process_type' => 'standard',
                                    'name' => $this->l('Standard')
                                )
                            ),
                            'id' => 'id_order_process_type',
                            'name' => 'name'
                        )
                    ),
                )
            )
        );
        
        $status_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Amazon Pay mode'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'ENVIRONMENT',
                        'label' => $this->l('Mode'),
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_lpa_environment' => 'SANDBOX',
                                    'name' => $this->l('Sandbox (test)')
                                ),
                                array(
                                    'id_lpa_environment' => 'LIVE',
                                    'name' => $this->l('Production (live)')
                                )
                            ),
                            'id' => 'id_lpa_environment',
                            'name' => 'name'
                        )
                    ),
                )
            )
        );
        
        $banners_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Amazon Pay banners and acceptance marks'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Amazon Pay banner in your website header'),
                        'name' => 'AMZ_PROMO_HEADER',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on_promo_header',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off_promo_header',
                                'value' => '0',
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Style'),
                        'name' => 'AMZ_PROMO_HEADER_STYLE',
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_promo_header_style' => '0',
                                    'name' => $this->l('LightGrey')
                                ),
                                array(
                                    'id_promo_header_style' => '1',
                                    'name' => $this->l('DarkGrey')
                                )
                            ),
                            'id' => 'id_promo_header_style',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Amazon Pay banner on your product pages'),
                        'name' => 'AMZ_PROMO_PRODUCT',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on_promo_product',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off_promo_product',
                                'value' => '0',
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Style'),
                        'name' => 'AMZ_PROMO_PRODUCT_STYLE',
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_promo_product_style' => '0',
                                    'name' => $this->l('LightGrey')
                                ),
                                array(
                                    'id_promo_product_style' => '1',
                                    'name' => $this->l('DarkGrey')
                                )
                            ),
                            'id' => 'id_promo_product_style',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Amazon Pay acceptance mark in your website footer'),
                        'name' => 'AMZ_PROMO_FOOTER',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on_promo_footer',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off_promo_footer',
                                'value' => '0',
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Style'),
                        'name' => 'AMZ_PROMO_FOOTER_STYLE',
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_promo_footer_style' => '0',
                                    'name' => $this->l('LightGrey')
                                ),
                                array(
                                    'id_promo_footer_style' => '1',
                                    'name' => $this->l('DarkGrey')
                                )
                            ),
                            'id' => 'id_promo_footer_style',
                            'name' => 'name'
                        )
                    ),
                )
            )
        );
        
        $buttons_form  = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Amazon Pay and Login with Amazon buttons'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'BUTTON_SIZE_LPA',
                        'label' => $this->l('button_size_lpa'),
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_buttonsize' => 'small',
                                    'name' => $this->l('small')
                                ),
                                array(
                                    'id_buttonsize' => 'medium',
                                    'name' => $this->l('medium')
                                ),
                                array(
                                    'id_buttonsize' => 'large',
                                    'name' => $this->l('large')
                                ),
                                array(
                                    'id_buttonsize' => 'x-large',
                                    'name' => $this->l('x-large')
                                )
                            ),
                            'id' => 'id_buttonsize',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'BUTTON_COLOR_LPA',
                        'label' => $this->l('button_color_lpa'),
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_buttonsize' => 'Gold',
                                    'name' => $this->l('gold')
                                ),
                                array(
                                    'id_buttonsize' => 'LightGray',
                                    'name' => $this->l('light gray')
                                ),
                                array(
                                    'id_buttonsize' => 'DarkGray',
                                    'name' => $this->l('dark gray')
                                )
                            ),
                            'id' => 'id_buttonsize',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'TYPE_PAY',
                        'label' => $this->l('type_pay'),
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_buttonsize' => 'PwA',
                                    'name' => $this->l('Amazon Pay')
                                ),
                                array(
                                    'id_buttonsize' => 'Pay',
                                    'name' => $this->l('Pay')
                                ),
                                array(
                                    'id_buttonsize' => 'A',
                                    'name' => $this->l('logo only')
                                )
                            ),
                            'id' => 'id_buttonsize',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'BUTTON_COLOR_LPA_NAVI',
                        'label' => $this->l('button_color_lpa_navi'),
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_buttonsize' => 'Gold',
                                    'name' => $this->l('gold')
                                ),
                                array(
                                    'id_buttonsize' => 'LightGray',
                                    'name' => $this->l('light gray')
                                ),
                                array(
                                    'id_buttonsize' => 'DarkGray',
                                    'name' => $this->l('dark gray')
                                )
                            ),
                            'id' => 'id_buttonsize',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'name' => 'TYPE_LOGIN',
                        'label' => $this->l('type_login'),
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_buttonsize' => 'LwA',
                                    'name' => $this->l('Login with Amazon')
                                ),
                                array(
                                    'id_buttonsize' => 'Login',
                                    'name' => $this->l('Login')
                                ),/*
                                array(
                                'id_buttonsize' => 'A',
                                'name' => $this->l('logo only')
                                )*/
                            ),
                            'id' => 'id_buttonsize',
                            'name' => 'name'
                        )
                    ),
                )
            )
        );
        
        return array($connect_form, $config_setting_form, $display_form, $payment_form, $account_mngmtn, $misc_form, $status_form, $banners_form, $buttons_form);
    }
    
    public function getContent()
    {
        $this->context->smarty->assign('display_cache_hint', false);
        if (Tools::getValue('resetDefault') == 'true') {
            $this->resetDefaultSettings();
            $this->context->smarty->assign('after_reset', '1');
            $this->context->smarty->assign('display_cache_hint', true);
        }
        if (Tools::getValue('getLog') == 'true') {
            AmazonPaymentsLogHelper::generateAndSendLogfile($this);
        }
        if (Tools::getValue('resetHookPosition') == 'true') {
            Module::getInstanceByName($this->name)->updatePosition(Hook::getIdByName('displayPayment'), 0, 1);
            Module::getInstanceByName($this->name)->updatePosition(Hook::getIdByName('displayPaymentEU'), 0, 1);
        }
        
        if (Tools::isSubmit('submitAmzpaymentsModule')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                $this->context->smarty->assign(array('postErrors' => $this->_postErrors));
            }
            if (count($this->_postSuccess)) {
                $this->context->smarty->assign('display_cache_hint', true);
                $this->context->smarty->assign(array('postSuccess' => $this->_postSuccess));
                if (Configuration::get('AMZ_CLEAR_CACHE')) {
                    Tools::clearSmartyCache();
                    Tools::clearXMLCache();
                    Media::clearCache();
                    Tools::generateIndex();
                }
            }
        }
        
        if ($this->access_key != '' && $this->merchant_id != '') {
            $button_url = 'https://payments.amazon.de/gp/widgets/button';
            if (Tools::strtolower($this->region) == 'uk') {
                $button_url = 'https://payments.amazon.co.uk/gp/widgets/button';
            } elseif (Tools::strtolower($this->region) == 'us') {
                $button_url = 'https://payments.amazon.com/gp/widgets/button';
            } elseif (Tools::strtolower($this->region) == 'jp') {
                $button_url = 'https://payments.amazon.co.jp/gp/widgets/button';
            }
            $check = getimagesize($button_url . "?sellerId=" . $this->merchant_id);
            if ($check[0] > 1) {
                $this->context->smarty->assign('kyc_passed', 1);
            } else {
                $this->context->smarty->assign('kyc_passed', 0);
            }
        }
        
        $this->context->smarty->assign('reset_link', $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&resetDefault=true');
        $this->context->smarty->assign('hook_reset_link', $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&resetHookPosition=true');
        $this->context->smarty->assign('banners', $this->getBannersForLanguageCode());
        $this->context->smarty->assign('no_ssl', !Configuration::get('PS_SSL_ENABLED'));
        $this->context->smarty->assign('displayName', $this->displayName);
        $this->context->smarty->assign('module_name', $this->name);
        $this->context->smarty->assign('current_version', $this->version);
        $this->context->smarty->assign('allowed_return_url_1', $this->getAllowedReturnUrls(1));
        $this->context->smarty->assign('allowed_return_url_2', $this->getAllowedReturnUrls(2));
        $this->context->smarty->assign('allowed_js_origins', $this->getDomainForWhitelist());
        $this->context->smarty->assign('base_url', $this->getBaseLink());
        $this->context->smarty->assign('language_code', $this->context->language->iso_code);
        $this->context->smarty->assign('log_url', $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&getLog=true');
        
        $register_link = 'https://sellercentral-europe.amazon.com/hz/me/sp/redirect?ld=';
        
        $this->context->smarty->assign('lang_iso_code', $this->context->language->iso_code);
        switch ($this->context->language->iso_code) {
            case 'de':
                $ld = 'SPEXDEAPA-Prestashop-core_DE';
                $register_link = 'https://sellercentral-europe.amazon.com/hz/me/sp/redirect?spId=A1AOZCKI9MBRZA&language=de_DE&source=SPPL';
                $faq_link = 'https://pay.amazon.com/de/help/201810860';
                $videoamazonyoutube = 'https://www.youtube.com/embed/KjMYIXMETc0?rel=0&showinfo=0';
                $new_customer_link = 'https://pay.amazon.com/de/contactsales';
                $youtube_video_link = 'https://pay.amazon.com/de/help/BC5GMPSFCTKQF5R';
                $youtube_video_embed_link = 'https://www.youtube.com/embed/T5I3Kvd1HWU?rel=0&showinfo=0';
                break;
            case 'en':
                if (isset($this->context->language->local) && Tools::strtolower($this->context->language->local) == 'en-us') {
                    $ld = 'SPEXUSAPA-Prestashop-core_US';
                    $register_link.= 'SPEXUSAPA-PrestashopPL';
                    $faq_link = 'https://pay.amazon.com/us/help/201810860';
                    $new_customer_link = 'https://pay.amazon.com/us/contactsales';
                } else {
                    $ld = 'SPEXUKAPA-Prestashop-core_UK';
                    $register_link.= 'SPEXUKAPA-PrestashopPL';
                    $faq_link = 'https://pay.amazon.com/uk/help/201810860';
                    $new_customer_link = 'https://pay.amazon.com/uk/contactsales';
                }
                $videoamazonyoutube = 'https://www.youtube.com/embed/KjMYIXMETc0?rel=0&showinfo=0';
                $youtube_video_link = 'https://pay.amazon.com/uk/help/BC5GMPSFCTKQF5R';
                $youtube_video_embed_link = 'https://www.youtube.com/embed/t1y6NRqJ3QM?rel=0&showinfo=0';
                break;
            case 'fr':
                $ld = 'SPEXFRAPA-Prestashop-core_FR';
                $register_link = 'https://sellercentral-europe.amazon.com/hz/me/sp/redirect?spId=A1AOZCKI9MBRZA&language=fr_FR&source=SPPL';
                $faq_link = 'https://pay.amazon.com/fr/help/201810860';
                $new_customer_link = 'https://pay.amazon.com/fr/contactsales';
                $videoamazonyoutube = 'https://www.youtube.com/embed/KjMYIXMETc0?rel=0&showinfo=0';
                $youtube_video_link = 'https://pay.amazon.com/fr/help/BC5GMPSFCTKQF5R';
                $youtube_video_embed_link = 'https://www.youtube.com/embed/OgLoSqEgO7U?rel=0&showinfo=0';
                break;
            case 'it':
                $ld = 'SPEXITAPA-Prestashop-core_IT';
                $register_link = 'https://sellercentral-europe.amazon.com/hz/me/sp/redirect?spId=A1AOZCKI9MBRZA&language=it_IT&source=SPPL';
                $faq_link = 'https://pay.amazon.com/it/help/201810860';
                $new_customer_link = 'https://pay.amazon.com/it/contactsales';
                $videoamazonyoutube = 'https://www.youtube.com/embed/KjMYIXMETc0?rel=0&showinfo=0';
                $youtube_video_link = 'https://pay.amazon.com/it/help/BC5GMPSFCTKQF5R';
                $youtube_video_embed_link = 'https://www.youtube.com/embed/dL4niF9HvUY?rel=0&showinfo=0';
                break;
            case 'es':
                $ld = 'SPEXESAPA-Prestashop-core_ES';
                $register_link = 'https://sellercentral-europe.amazon.com/hz/me/sp/redirect?spId=A1AOZCKI9MBRZA&language=es_ES&source=SPPL';
                $faq_link = 'https://pay.amazon.com/es/help/201810860';
                $new_customer_link = 'https://pay.amazon.com/es/contactsales';
                $videoamazonyoutube = 'https://www.youtube.com/embed/KjMYIXMETc0?rel=0&showinfo=0';
                $youtube_video_link = 'https://pay.amazon.com/es/help/BC5GMPSFCTKQF5R';
                $youtube_video_embed_link = 'https://www.youtube.com/embed/-B8cZsOYvsA?rel=0&showinfo=0';
                break;
            default:
                $ld = '';
                $register_link.= 'SPEXDEAPA-PrestashopPL';
                $faq_link = 'https://pay.amazon.com/de/help/201810860';
                $videoamazonyoutube = 'https://www.youtube.com/embed/KjMYIXMETc0?rel=0&showinfo=0';
                $new_customer_link = 'https://pay.amazon.com/de/contactsales';
                $youtube_video_link = 'https://pay.amazon.com/de/help/BC5GMPSFCTKQF5R';
                $youtube_video_embed_link = 'https://www.youtube.com/embed/t1y6NRqJ3QM?rel=0&showinfo=0';
                break;
        }
        
        $this->context->smarty->assign('register_link', $register_link);
        $this->context->smarty->assign('faq_link', $faq_link);
        $this->context->smarty->assign('videoamazonyoutube', $videoamazonyoutube);
        $this->context->smarty->assign('youtube_video_link', $youtube_video_link);
        $this->context->smarty->assign('youtube_video_embed_link', $youtube_video_embed_link);
        $this->context->smarty->assign('new_customer_link', $new_customer_link);
        $this->context->smarty->assign('use_simple_path', true);
        $this->context->smarty->assign('ld', $ld);
        $this->context->smarty->assign('simple_path', $this->getSimplePathData());
        
        $this->reloadConfigVars();
        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('configform', str_replace('</form>', '', $this->_displayForm()));
        
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configuration.tpl');
        
        return $output;
    }
    
    public function getSimplePathData()
    {
        $simple_path_data = array('spId' => $this->getPfId(),
            'ref' => $this->getRef(),
            'uniqueId' => Tools::encryptIV('amzPaymentsSimplePath'),
            'locale' =>  $this->getLocalCodeForSimplePath(),
            'loginRedirectURLs_1' => $this->getAllowedReturnUrls(1),
            'loginRedirectURLs_2' => $this->getAllowedReturnUrls(2),
            'allowedLoginDomains' => $this->getDomainForWhitelist(),
            'storeDescription' => Configuration::get('PS_SHOP_NAME'),
            'language' => $this->getLanguageCodeForSimplePath(),
            'returnMethod' => 'GET',
            'Source' => 'SPPL',
            'sandboxMerchantIPNURL' => $this->getIPNURL(),
            'productionMerchantIPNURL' => $this->getIPNURL(),
        );
        return $simple_path_data;
    }
    
    public function getLocalPath()
    {
        return $this->local_path;
    }
    
    public function hookDisplayNav()
    {
        if ($this->hide_login_on_menu != 1 &&
            $this->hide_login_btns != 1 &&
            $this->lpa_mode != 'pay' &&
            !$this->context->customer->isLogged() &&
            ((isset($this->context->controller->module->name) && $this->context->controller->module->name != 'amzpayments') || !(isset($this->context->controller->module->name)))) {
                $this->smarty->assign(array(
                    'button_hidden' => $this->button_visibility == '0'
                ));
                return $this->display(__FILE__, 'views/templates/hooks/displaynav.tpl');
        }
        return '';
    }
    
    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addJquery();
        $this->context->controller->addJS(($this->_path) . 'views/js/admin.js');
        $this->context->controller->addCSS(($this->_path) . 'views/css/admin.css');
    }
    
    public function hookDisplayBackOfficeFooter()
    {
        $this->context->controller->addCSS(($this->_path) . 'views/css/admin.css');
        if ($this->capture_mode == 'after_shipping') {
            $this->shippingCapture();
        }
    }
    
    public function getRegionalCodeForURL()
    {
        if (in_array(Tools::strtolower($this->region), array('de', 'at', 'fr', 'it', 'es'))) {
            return 'de';
        } elseif (Tools::strtolower($this->region) == 'uk') {
            return 'uk';
        } elseif (Tools::strtolower($this->region) == 'us') {
            return 'us';
        } elseif (Tools::strtolower($this->region) == 'jp') {
            return 'jp';
        }
        return 'de';
    }
    
    public function getRef()
    {
        if (Tools::strtolower($this->region) == 'de') {
            return 'ml_de_ap_np_ba_spli_xx_xx_xx_pre';
        } elseif (Tools::strtolower($this->region) == 'uk') {
            return 'ml_uk_ap_np_ba_spli_xx_xx_xx_pre';
        } elseif (Tools::strtolower($this->region) == 'us') {
            return 'ml_us_ap_np_ba_spli_xx_xx_xx_pre';
        } elseif (Tools::strtolower($this->region) == 'jp') {
            return 'ml_jp_ap_np_ba_spli_xx_xx_xx_pre';
        } elseif (Tools::strtolower($this->region) == 'fr') {
            return 'ml_fr_ap_np_ba_spli_xx_xx_xx_pre';
        } elseif (Tools::strtolower($this->region) == 'es') {
            return 'ml_es_ap_np_ba_spli_xx_xx_xx_pre';
        } elseif (Tools::strtolower($this->region) == 'it') {
            return 'ml_it_ap_np_ba_spli_xx_xx_xx_pre';
        } elseif (Tools::strtolower($this->region) == 'at') {
            return 'ml_at_ap_np_ba_spli_xx_xx_xx_pre';
        }
        return 'ml_de_ap_np_ba_spli_xx_xx_xx_pre';
    }
    
    private function getLocalCodeForSimplePath()
    {
        $currency = Currency::getCurrent();
        if ($currency->iso_code == 'EUR') {
            return 'EUR';
        } elseif ($currency->iso_code == 'GBP') {
            return 'GBP';
        } elseif ($currency->iso_code == 'USD') {
            return 'USD';
        } elseif ($currency->iso_code == 'JPY' || $currency->iso_code == 'YEN') {
            return 'YEN';
        }
        return 'USD';
    }
    
    public function getButtonURL()
    {
        if ($this->environment == 'SANDBOX') {
            if (in_array(Tools::strtolower($this->region), array('de', 'at', 'fr', 'it', 'es'))) {
                return 'https://payments-sandbox.amazon.de/gp/widgets/button';
            } elseif (Tools::strtolower($this->region) == 'uk') {
                return 'https://payments-sandbox.amazon.co.uk/gp/widgets/button';
            } elseif (Tools::strtolower($this->region) == 'us') {
                return 'https://payments-sandbox.amazon.com/gp/widgets/button';
            } elseif (Tools::strtolower($this->region) == 'jp') {
                return 'https://payments-sandbox.amazon.co.jp/gp/widgets/button';
            }
        } else {
            if (in_array(Tools::strtolower($this->region), array('de', 'at', 'fr', 'it', 'es'))) {
                return 'https://payments.amazon.de/gp/widgets/button';
            } elseif (Tools::strtolower($this->region) == 'uk') {
                return 'https://payments.amazon.co.uk/gp/widgets/button';
            } elseif (Tools::strtolower($this->region) == 'us') {
                return 'https://payments.amazon.com/gp/widgets/button';
            } elseif (Tools::strtolower($this->region) == 'jp') {
                return 'https://payments.amazon.co.jp/gp/widgets/button';
            }
        }
    }
    
    public function getLpaApiUrl()
    {
        if ($this->environment == 'SANDBOX') {
            if (in_array(Tools::strtolower($this->region), array('de', 'at', 'fr', 'it', 'es'))) {
                return 'https://api.sandbox.amazon.de';
            } elseif (Tools::strtolower($this->region) == 'uk') {
                return 'https://api.sandbox.amazon.co.uk';
            } elseif (Tools::strtolower($this->region) == 'us') {
                return 'https://api.sandbox.amazon.com';
            } elseif (Tools::strtolower($this->region) == 'jp') {
                return 'https://api-sandbox.amazon.co.jp';
            }
        } else {
            if (in_array(Tools::strtolower($this->region), array('de', 'at', 'fr', 'it', 'es'))) {
                return 'https://api.amazon.de';
            } elseif (Tools::strtolower($this->region) == 'uk') {
                return 'https://api.amazon.co.uk';
            } elseif (Tools::strtolower($this->region) == 'us') {
                return 'https://api.amazon.com';
            } elseif (Tools::strtolower($this->region) == 'jp') {
                return 'https://api.amazon.co.jp';
            }
        }
    }
    
    public function getCarrierOptionsPostNames()
    {
        $ret = array();
        foreach ($this->getCarrierOptions() as $c) {
            $ret[] = 'SHIPPINGS_NOT_ALLOWED_carrier_' . $c['value'] . '_on';
        }
        return $ret;
    }
    
    public function addDisabledCarrierOptions($fields_values)
    {
        if ($this->shippings_not_allowed != '') {
            $blocked_shipping_ids = explode(',', $this->shippings_not_allowed);
            foreach ($blocked_shipping_ids as $k => $v) {
                $fields_values['SHIPPINGS_NOT_ALLOWED_carrier_' . $v . '_on'] = $v;
            }
        }
        return $fields_values;
    }
    
    public function getCarrierOptions()
    {
        $ret = array();
        $carriers = Carrier::getCarriers(Configuration::get('PS_LANG_DEFAULT'), false, false, false, null, Carrier::ALL_CARRIERS);
        foreach ($carriers as $carrier) {
            $ret[] = array('id' => 'carrier_' . $carrier['id_carrier'] . '_on',
                'value' => $carrier['id_carrier'],
                'val' => $carrier['id_carrier'],
                'label' => $carrier['name']
            );
        }
        return $ret;
    }
    
    protected function checkForTemporarySessionVarsAndKillThem()
    {
        $need_update = false;
        if (isset($this->context->cart->id_address_delivery)) {
            $check_address = new Address((int) $this->context->cart->id_address_delivery);
            if ($check_address->lastname == 'amzLastname' || $check_address->firstname == 'amzFirstname' || $check_address->address1 == 'amzAddress1') {
                $check_address->delete();
                $this->context->cart->id_address_delivery = 0;
                
                $sql = 'UPDATE `'._DB_PREFIX_.'cart_product`
                SET `id_address_delivery` = NULL
                WHERE  `id_cart` = '.(int)$this->context->cart->id;
                Db::getInstance()->execute($sql);
                
                $sql = 'UPDATE `'._DB_PREFIX_.'customization`
                SET `id_address_delivery` = NULL
                WHERE  `id_cart` = '.(int)$this->context->cart->id;
                Db::getInstance()->execute($sql);
                
                $need_update = true;
            }
        }
        if (isset($this->context->cart->id_address_invoice)) {
            $check_address = new Address((int) $this->context->cart->id_address_invoice);
            if ($check_address->lastname == 'amzLastname' || $check_address->firstname == 'amzFirstname' || $check_address->address1 == 'amzAddress1') {
                $check_address->delete();
                $this->context->cart->id_address_invoice = 0;
                
                $sql = 'UPDATE `'._DB_PREFIX_.'cart_product`
                SET `id_address_delivery` = NULL
                WHERE  `id_cart` = '.(int)$this->context->cart->id;
                Db::getInstance()->execute($sql);
                
                $sql = 'UPDATE `'._DB_PREFIX_.'customization`
                SET `id_address_delivery` = NULL
                WHERE  `id_cart` = '.(int)$this->context->cart->id;
                Db::getInstance()->execute($sql);
                
                $need_update = true;
            }
        }
        if ($need_update) {
            $this->context->cart->update();
        }
    }
    
    public function hookActionDispatcher($params)
    {
        if (isset($this->context->controller->php_self)) {
            if ($this->context->controller->php_self == 'addresses' || $this->context->controller->php_self == 'address') {
                if ($this->order_process_type == 'standard' && isset($this->context->cookie->amazon_id) && $this->context->customer->isLogged() && $this->isValidOrderReference($this->context->cookie->amazon_id) && $this->hasSetValidAmazonAddress()) {
                    $redirect_params = array();
                    if (Tools::getValue('back') != '') {
                        $redirect_params['back'] = Tools::getValue('back');
                    }
                    Tools::redirect($this->context->link->getModuleLink('amzpayments', 'addresswallet', $redirect_params));
                }
            }
        }
    }
    
    public function hookDisplayShoppingCartFooter($params)
    {
        $show_amazon_button = true;
        if (isset($this->context->controller->module)) {
            if ($this->context->controller->module->name == 'amzpayments') {
                $show_amazon_button = false;
            }
        }
        
        if (! $this->checkIfCurrencyMatchesModuleRegion()) {
            $show_amazon_button = false;
        }
        
        if (! $this->checkCurrency($params['cart'])) {
            $show_amazon_button = false;
        }
        
        if ($this->lpa_mode == 'login') {
            $show_amazon_button = false;
        }
        
        $summary = $this->context->cart->getSummaryDetails();
        foreach ($summary['products'] as &$product_update) {
            $product_id = (int) (isset($product_update['id_product']) ? $product_update['id_product'] : $product_update['product_id']);
            if ($this->productNotAllowed($product_id)) {
                $show_amazon_button = false;
            }
        }
        if ($this->order_process_type == 'standard' && isset($this->context->cookie->amazon_id) && $this->context->customer->isLogged()) {
            if ($this->isValidOrderReference($this->context->cookie->amazon_id) && $this->hasSetValidAmazonAddress()) {
                $show_amazon_button = false;
                $this->context->smarty->assign('resetlink', $this->context->link->getModuleLink('amzpayments', 'resetpayments'));
                return $this->display(__FILE__, 'views/templates/hooks/paymentreset.tpl');
            }
        }
        if ($show_amazon_button) {
            $this->context->smarty->assign('sellerID', $this->merchant_id);
            $this->context->smarty->assign('create_account', (($this->allow_guests == '0' || Configuration::get('PS_GUEST_CHECKOUT_ENABLED') == '0' || $this->order_process_type == 'standard') && (!$this->context->customer->isLogged()) ? '1' : '0'));
            $this->context->smarty->assign('size', $this->button_size);
            $this->context->smarty->assign('color', $this->button_color);
            $this->context->smarty->assign('btn_url', $this->getButtonURL());
            $this->context->smarty->assign('hide_button', $this->button_visibility == '0');
            $this->context->smarty->assign('preBuildButton', $this->lpa_mode == 'pay');
            return $this->display(__FILE__, 'views/templates/hooks/amzpayments.tpl');
        }
    }
    
    public function hookPayment($params)
    {
        if (! $this->active) {
            return;
        }
        if (! $this->checkCurrency($params['cart'])) {
            return;
        }
        
        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_hide_button' => $this->button_visibility == '0',
            'this_path_amzpayments' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
        ));
        if ($this->order_process_type == 'standard' && isset($this->context->cookie->amazon_id) && $this->isValidOrderReference($this->context->cookie->amazon_id) && $this->hasSetValidAmazonAddress()) {
            return $this->display(__FILE__, 'views/templates/hooks/payment_optype_std.tpl');
        } else {
            return $this->display(__FILE__, 'views/templates/hooks/payment.tpl');
        }
    }
    
    public function hookDisplayPaymentEU($params)
    {
        if ($this->order_process_type == 'standard' && isset($this->context->cookie->amazon_id) && $this->context->customer->isLogged()) {
            if (!$this->active) {
                return;
            }
            
            if (! $this->checkCurrency($params['cart'])) {
                return;
            }
            
            $this->context->controller->addJS($this->getPathUri() . 'views/js/amzpayments_eucompl.js');
            
            $payment_options = array(
                'cta_text' => $this->l('Pay with Amazon'),
                'logo' => Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/logo.png'),
                'action' => $this->context->link->getModuleLink($this->name, 'payment', array(), true)
            );
            
            return $payment_options;
        }
        return;
    }
    
    public function checkIfCurrencyMatchesModuleRegion()
    {
        $currency = new Currency((int) (Context::getContext()->cart->id_currency));
        
        if (Tools::strtolower($this->region) == 'de' && Tools::strtoupper($currency->iso_code) == 'EUR') {
            return true;
        } elseif (Tools::strtolower($this->region) == 'at' && Tools::strtoupper($currency->iso_code) == 'EUR') {
            return true;
        } elseif (Tools::strtolower($this->region) == 'fr' && Tools::strtoupper($currency->iso_code) == 'EUR') {
            return true;
        } elseif (Tools::strtolower($this->region) == 'it' && Tools::strtoupper($currency->iso_code) == 'EUR') {
            return true;
        } elseif (Tools::strtolower($this->region) == 'es' && Tools::strtoupper($currency->iso_code) == 'EUR') {
            return true;
        } elseif (Tools::strtolower($this->region) == 'uk' && Tools::strtoupper($currency->iso_code) == 'GBP') {
            return true;
        } elseif (Tools::strtolower($this->region) == 'us' && Tools::strtoupper($currency->iso_code) == 'USD') {
            return true;
        } elseif (Tools::strtolower($this->region) == 'jp' && (Tools::strtoupper($currency->iso_code) == 'JPY' || Tools::strtoupper($currency->iso_code) == 'YEN')) {
            return true;
        }
        return false;
    }
    
    public function checkCurrency($cart)
    {
        $currency_order = new Currency((int) ($cart->id_currency));
        $currencies_module = $this->getCurrency((int) $cart->id_currency);
        
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }
    
    protected function productNotAllowed($product_id)
    {
        if ($this->products_not_allowed != '') {
            $products_not_allowed_ids = explode(',', $this->products_not_allowed);
            foreach ($products_not_allowed_ids as $k => $v) {
                $products_not_allowed_ids[$k] = (int) $v;
            }
            if (in_array($product_id, $products_not_allowed_ids)) {
                return true;
            }
        }
    }
    
    public function hookDisplayTopColumn($params)
    {
        $content = $this->display(__FILE__, 'views/templates/hooks/_tooltip.tpl');
        if (isset($this->context->cookie->show_success_amz_message) && Tools::getValue('controller') == 'guesttracking') {
            unset($this->context->cookie->show_success_amz_message);
            $content.= $this->display(__FILE__, 'views/templates/hooks/displaytopcolumn.tpl');
        }
        return $content;
    }
    
    public function hookDisplayPayment($params)
    {
        return $this->hookPayment($params);
    }
    
    public function hookDisplayHeader($params)
    {
        $additional_dpd_string = '';
        if (is_dir(_PS_MODULE_DIR_ . 'dpdfrance') && Module::isEnabled('dpdfrance') && Tools::getValue('controller') == 'amzpayments') {
            $dpdfrance_module_uri = _MODULE_DIR_ . 'dpdfrance';
            $this->context->controller->addCSS($dpdfrance_module_uri . '/views/css/front/dpdfrance.css');
            $this->context->controller->addJS($dpdfrance_module_uri . '/views/js/front/dpdfrance.js');
            $this->context->controller->addJS('https://maps.googleapis.com/maps/api/js?key=' . Configuration::get('DPDFRANCE_GOOGLE_API_KEY'));
            $this->context->smarty->assign(array(
                'ps_version'                        => (float)_PS_VERSION_,
                'opc'                               => '1',
                'dpdfrance_relais_carrier_id'       => (int)Configuration::get('DPDFRANCE_RELAIS_CARRIER_ID'),
                'dpdfrance_predict_carrier_id'      => (int)Configuration::get('DPDFRANCE_PREDICT_CARRIER_ID')));
            $additional_dpd_string = $this->context->smarty->fetch(_PS_ROOT_DIR_ . '/modules/dpdfrance/views/templates/front/header.tpl');
        }
        
        if (Tools::getValue('controller') == 'order' || Tools::getValue('controller') == 'orderopc') {
            $this->checkForTemporarySessionVarsAndKillThem();
            if ($this->order_process_type == 'standard' && isset($this->context->cookie->amazon_id) && $this->context->customer->isLogged() && $this->isValidOrderReference($this->context->cookie->amazon_id) && $this->hasSetValidAmazonAddress()) {
                $this->context->controller->addJS($this->_path . 'views/js/amzpayments_co_addresses.js');
            }
        }
        
        $show_amazon_button = true;
        
        if (! $this->checkCurrency($params['cart'])) {
            $show_amazon_button = false;
        }
        
        if (! $this->checkIfCurrencyMatchesModuleRegion()) {
            $show_amazon_button = false;
        }
        
        $this->context->controller->addCSS($this->_path . 'views/css/amzpayments.css', 'all');
        $this->context->controller->addCSS($this->_path . 'views/css/tipr.css', 'all');
        $this->context->controller->addJS($this->_path . 'views/js/tipr.min.js');
        $redirect = $this->context->link->getModuleLink('amzpayments', 'amzpayments');
        
        if (isset($this->context->cookie->amazonpay_errors_message)) {
            $this->context->controller->errors[] = $this->context->cookie->amazonpay_errors_message;
            unset($this->context->cookie->amazonpay_errors_message);
        }
        
        if (Configuration::get('PS_SSL_ENABLED')) {
            $redirect = str_replace('http://', 'https://', $redirect);
        }
        
        $login_redirect = $this->context->link->getModuleLink('amzpayments', 'process_login');
        
        // always SSL, as amazon has nothing else allowed!
        $login_redirect = str_replace('http://', 'https://', $login_redirect);
        
        if ($this->order_process_type == 'standard' && !$this->context->customer->isLogged()) {
            $redirect = $login_redirect;
        } elseif ($this->order_process_type == 'standard' && $this->context->customer->isLogged()) {
            $redirect = $this->context->link->getModuleLink('amzpayments', 'addresswallet');
        }
        
        if (strpos($redirect, '?') > 0) {
            $redirect .= '&session=';
        } else {
            $redirect .= '?session=';
        }
        
        if (strpos($login_redirect, '?') > 0) {
            $login_checkout_redirect = $login_redirect . '&toCheckout=1';
        } else {
            $login_checkout_redirect = $login_redirect . '?toCheckout=1';
        }
        
        $set_user_ajax = $this->context->link->getModuleLink('amzpayments', 'user_to_shop');
        
        // always SSL, as amazon has nothing else allowed!
        $set_user_ajax = str_replace('http://', 'https://', $set_user_ajax);
        
        $ext_js = '';
        
        if ($this->getRegionalCodeForURL() == 'us') {
            if ($this->environment == 'SANDBOX') {
                $ext_js = 'https://static-na.payments-amazon.com/OffAmazonPayments/us/sandbox/js/Widgets.js';
            } else {
                $ext_js = 'https://static-na.payments-amazon.com/OffAmazonPayments/us/js/Widgets.js';
            }
        } elseif ($this->getRegionalCodeForURL() == 'jp') {
            if ($this->environment == 'SANDBOX') {
                $ext_js = 'https://static-fe.payments-amazon.com/OffAmazonPayments/jp/sandbox/lpa/js/Widgets.js';
            } else {
                $ext_js = 'https://static-fe.payments-amazon.com/OffAmazonPayments/jp/lpa/js/Widgets.js';
            }
        } else {
            if ($this->environment == 'SANDBOX') {
                $ext_js = 'https://static-eu.payments-amazon.com/OffAmazonPayments/' . $this->getRegionalCodeForURL() . '/sandbox/lpa/js/Widgets.js?sellerId=' . $this->merchant_id;
            } else {
                $ext_js = 'https://static-eu.payments-amazon.com/OffAmazonPayments/' . $this->getRegionalCodeForURL() . '/lpa/js/Widgets.js?sellerId=' . $this->merchant_id;
            }
        }
        
        $ext_js = '<script type="text/javascript" src="' . $ext_js . '"></script>';
        
        $amz_login_ready = ' window.onAmazonLoginReady = function() { amazon.Login.setClientId("' . $this->client_id . '"); }; ';
        
        $acc_tk = '';
        $is_logged = 'false';
        
        if (isset($this->context->cookie->amz_access_token) && $this->context->cookie->amz_access_token != '') {
            $is_logged = 'true';
            if (!isset($this->context->cookie->amazon_id)) {
                $acc_tk = self::prepareCookieValueForAmazonPaymentsUse($this->context->cookie->amz_access_token);
                $amz_login_ready = '
                var accessToken = "' . $acc_tk . '";
                if (typeof accessToken === \'string\' && accessToken.match(/^Atza/)) {
                document.cookie = "amazon_Login_accessToken=" + accessToken +";secure";
            }
            window.onAmazonLoginReady = function() {
            amazon.Login.setClientId("' . $this->client_id . '");
                amazon.Login.setUseCookie(true);
            };
            ';
            }
        }
        
        $logout_str = '';
        if ($this->context->controller->php_self == 'guest-tracking' || isset($this->context->cookie->amz_logout)) {
            unset($this->context->cookie->amz_logout);
            $logout_str .= '<script type="text/javascript"> amazonLogout(); </script>';
        }
        
        if ($this->button_visibility == '0') {
            $css_string = '<style> #jsLoginAuthPage,#payWithAmazonCartDiv,#HOOK_ADVANCED_PAYMENT #payWithAmazonListDiv { display: none; } </style>';
        } else {
            if ($this->order_process_type == 'standard' && isset($this->context->cookie->amazon_id) && $this->context->customer->isLogged()) {
                $css_string = '<style> #HOOK_ADVANCED_PAYMENT #payWithAmazonListDiv { display: none; } </style>';
            } else {
                $css_string = '';
            }
        }
        
        if ($this->hide_login_btns == 1) {
            $css_string.= '<style> #jsLoginAuthPage { display: none; } </style>';
        }
        
        if ($this->hide_minicart_button == 1) {
            $css_string.= '<style> #payWithAmazonCartDiv { display: none; } </style>';
        }
        
        $js_file = 'views/js/amzpayments_login.js';
        $js_file = Tools::file_get_contents(_PS_MODULE_DIR_ . $this->name . '/' . $js_file);
        $js_file = str_replace(array(
            "\t",
            "\r\n",
            "\n"
        ), array(
            ' ',
            ' ',
            ' '
        ), $js_file);
        $this->context->cookie->amz_js_string = self::prepareCookieValueForPrestaShopUse($amz_login_ready);
        
        if ($this->hide_login_on_menu == '1' && $this->hide_minicart_button == '1') {
            if ($this->isSiteWhereWeDoNotNeedAmazonScripts()) {
                return;
            }
        }
        
        $amz_create_account_exp = (($this->allow_guests == '0' || Configuration::get('PS_GUEST_CHECKOUT_ENABLED') == '0' || $this->order_process_type == 'standard') && (!$this->context->customer->isLogged()) ? '1' : '0');
        $amz_login_ready = '<script type="text/javascript" src="' . Tools::str_replace_once((Configuration::get('PS_SSL_ENABLED') ? 'http://' : ''), (Configuration::get('PS_SSL_ENABLED') ? 'https://' : ''), $this->context->link->getModuleLink('amzpayments', 'jsmode', array('c' => 'amz_js_string', 't' => time()))) . '"></script>';
        return $additional_dpd_string . $css_string . $amz_login_ready . $ext_js . '<script type="text/javascript"> var AMZACTIVE = \'' . ($show_amazon_button ? '1' : '0') . '\'; var AMZSELLERID = "' . $this->merchant_id . '"; var AMZ_ADD_MINI_CART_BTN = "' . ($this->hide_minicart_button == '1' ? '0' : '1') . '"; var AMZ_CREATE_ACCOUNT_EXP = "' . $amz_create_account_exp . '"; var AMZ_BUTTON_TYPE_LOGIN = "' . $this->type_login . '"; var AMZ_BUTTON_TYPE_PAY = "' . $this->type_pay . '"; var AMZ_BUTTON_SIZE_PAY = "' . $this->button_size . '"; var AMZ_BUTTON_SIZE_LPA = "' . $this->button_size_lpa . '"; var AMZ_BUTTON_COLOR_LPA = "' . $this->button_color_lpa . '"; var AMZ_BUTTON_COLOR_PAY = "' . $this->button_color . '"; var AMZ_BUTTON_COLOR_LPA_NAVI = "' . $this->button_color_lpa_navi . '"; var AMZ_WIDGET_LANGUAGE = "' . $this->getWidgetLanguageCode() . '"; var CLIENT_ID = "' . $this->client_id . '"; var useRedirect = ' . (! self::currentSiteIsSSL() || $this->popup == '0' ? 'true' : 'false') . '; var LPA_MODE = "' . $this->lpa_mode . '"; var REDIRECTAMZ = "' . $redirect . '"; var LOGINREDIRECTAMZ_CHECKOUT = "' . $login_checkout_redirect . '"; var LOGINREDIRECTAMZ = "' . $login_redirect . '"; var is_logged = ' . $is_logged . '; var AMZACCTK = "' . $acc_tk . '"; var SETUSERAJAX = "' . $set_user_ajax . '";' . $js_file . ' </script>' . $logout_str;
    }
    
    public function isSiteWhereWeDoNotNeedAmazonScripts()
    {
        $need_script = false;
        if (Tools::getValue('module') == 'amzpayments') {
            $need_script = true;
        }
        if (in_array(Tools::getValue('controller'), array('authentication', 'order', 'order-opc', 'cart', 'orderopc'))) {
            $need_script = true;
        }
        return !$need_script;
    }
    
    public function hookDisplayAdminOrder($params)
    {
        $order = new Order($params['id_order']);
        if ($order->module == $this->name) {
            $q = 'SELECT ao.`amazon_order_reference_id`
                    FROM `' . _DB_PREFIX_ . 'amz_orders` ao
                   WHERE `id_order` = ' . (int) $params['id_order'];
            $r = Db::getInstance()->getRow($q);
            $amz_reference_id = $r['amazon_order_reference_id'];
            
            $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions
                   WHERE amz_tx_order_reference = \'' . pSQL($amz_reference_id) . '\' AND
                     (
                       (amz_tx_status != \'Closed\' AND amz_tx_status != \'Declined\')
                       OR
                       (amz_tx_status = \'Closed\' AND amz_tx_type = \'auth\' AND NOT EXISTS
                         (SELECT amz_tx_id FROM ' . _DB_PREFIX_ . 'amz_transactions WHERE amz_tx_order_reference = \'' . pSQL($amz_reference_id) . '\' AND amz_tx_type = \'capture\')
                       )
                     )';
            $rs = Db::getInstance()->ExecuteS($q);
            foreach ($rs as $r) {
                $this->intelligentRefresh($r);
            }
            
            return $this->getAdminSkeleton($params['id_order'], true);
        }
    }
    
    public function hookActionCarrierUpdate($params)
    {
        $old_carrier = $params['id_carrier'];
        $new_carrier = $params['carrier']->id;
        if ($this->shippings_not_allowed != '') {
            $blocked_shipping_ids = explode(',', $this->shippings_not_allowed);
            foreach ($blocked_shipping_ids as $k => $v) {
                $blocked_shipping_ids[$k] = (int)$v == (int)$old_carrier ? (int)$new_carrier : (int)$v;
            }
            Configuration::updateValue('SHIPPINGS_NOT_ALLOWED', join(",", $blocked_shipping_ids));
        }
        return true;
    }
    
    public function hookUpdateOrderStatus($params)
    {
        // not needed anymore
    }
    
    public function hookPaymentReturn($params)
    {
        return $this->display(__FILE__, 'views/templates/hooks/confirmation.tpl');
    }
    
    public function hookDisplayBanner($params)
    {
        if (Configuration::get('AMZ_PROMO_HEADER') == '1') {
            $banners = $this->getBannersForLanguageCode();
            $banners_style = Configuration::get('AMZ_PROMO_HEADER_STYLE') == '1' ? 'dark' : 'light';
            $this->context->smarty->assign('banner_url', $banners[$banners_style]['header']);
            return $this->display(__FILE__, 'views/templates/hooks/display_banner.tpl');
        }
    }
    
    public function hookDisplayProductButtons($params)
    {
        if (Configuration::get('AMZ_PROMO_PRODUCT') == '1') {
            $banners = $this->getBannersForLanguageCode();
            $banners_style = Configuration::get('AMZ_PROMO_PRODUCT_STYLE') == '1' ? 'dark' : 'light';
            $this->context->smarty->assign('banner_url', $banners[$banners_style]['product']);
            return $this->display(__FILE__, 'views/templates/hooks/display_product_buttons.tpl');
        }
    }
    
    public function hookDisplayFooter($params)
    {
        if (Configuration::get('AMZ_PROMO_FOOTER') == '1') {
            $banners = $this->getBannersForLanguageCode();
            $banners_style = Configuration::get('AMZ_PROMO_FOOTER_STYLE') == '1' ? 'dark' : 'light';
            $this->context->smarty->assign('banner_url', $banners[$banners_style]['footer']);
            return $this->display(__FILE__, 'views/templates/hooks/display_footer.tpl');
        }
    }
    
    public function setAmzOrdersReferences($order_id, $value, $field)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
            SELECT * FROM `' . _DB_PREFIX_ . 'amz_orders` WHERE `id_order` = \'' . (int) $order_id . '\'
        ');
        
        if ($result) {
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->update('amz_orders', array(
                $field => pSQL($value)
            ), 'id_order = \'' . (int) $order_id . '\'');
        } else {
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->insert('amz_orders', array(
                'id_order' => pSQL((int) $order_id),
                $field => pSQL($value)
            ));
        }
    }
    
    public function setAmazonReferenceIdForOrderId($amazon_reference_id, $order_id)
    {
        return $this->setAmzOrdersReferences($order_id, $amazon_reference_id, 'amazon_order_reference_id');
    }
    
    public function setAmazonAuthorizationReferenceIdForOrderId($authorization_reference_id, $order_id)
    {
        return $this->setAmzOrdersReferences($order_id, $authorization_reference_id, 'amazon_auth_reference_id');
    }
    
    public function setAmazonAuthorizationIdForOrderId($authorization_id, $order_id)
    {
        return $this->setAmzOrdersReferences($order_id, $authorization_id, 'amazon_authorization_id');
    }
    
    public function setAmazonCaptureIdForOrderId($amazon_capture_id, $order_id)
    {
        return $this->setAmzOrdersReferences($order_id, $amazon_capture_id, 'amazon_capture_id');
    }
    
    public function setAmazonCaptureReferenceIdForOrderId($amazon_capture_reference_id, $order_id)
    {
        return $this->setAmzOrdersReferences($order_id, $amazon_capture_reference_id, 'amazon_capture_reference_id');
    }
    
    public function setAmazonReferenceIdForOrderTransactionId($amazon_reference_id, $order_id)
    {
        $q = 'SELECT `reference` FROM ' . _DB_PREFIX_ . 'orders WHERE `id_order` = ' . (int) $order_id;
        if ($r = Db::getInstance()->getRow($q)) {
            return Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'order_payment` SET `transaction_id` = \'' . pSQL($amazon_reference_id) . '\' WHERE `order_reference` = \'' . pSQL($r['reference']) . '\'');
        }
        
        return false;
    }
    
    public function getAmazonReferenceIdForOrderTransactionId($order_id)
    {
        $q = 'SELECT `reference` FROM ' . _DB_PREFIX_ . 'orders WHERE `id_order` = ' . (int) $order_id;
        if ($r = Db::getInstance()->getRow($q)) {
            return $r['reference'];
        }
        
        return false;
    }
    
    public function createUniqueOrderId($cart_id)
    {
        return 'AP' . $cart_id . '-' . Tools::substr(Tools::getToken(false), 0, 8);
    }
    
    public function getAdminSkeleton($orders_id, $direct_include = false)
    {
        $q = 'SELECT `amazon_order_reference_id` FROM `' . _DB_PREFIX_ . 'amz_orders` WHERE `id_order` = ' . (int) $orders_id;
        $r = Db::getInstance()->getRow($q);
        if ($r['amazon_order_reference_id']) {
            $this->smarty->assign(array(
                'displayName' => $this->displayName,
                'amazon_order_reference_id' => $r['amazon_order_reference_id'],
                'orderHistory' => $direct_include ? $this->getOrderHistory($r['amazon_order_reference_id']) : '',
                'orderSummary' => $direct_include ? $this->getOrderSummary($r['amazon_order_reference_id']) : '',
                'orderActions' => $direct_include ? $this->getOrderActions($r['amazon_order_reference_id']) : ''
            ));
            return $this->display(__FILE__, 'views/templates/admin/skeleton.tpl');
        }
    }
    
    public function getOrderHistory($order_ref)
    {
        $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions WHERE amz_tx_order_reference = \'' . pSQL($order_ref) . '\' ORDER BY amz_tx_time';
        $rs = Db::getInstance()->ExecuteS($q);
        $ret = '';
        
        $rs_to_assign = array();
        foreach ($rs as $r) {
            if ($r['amz_tx_type'] == 'order_ref') {
                $reference_status = $r['amz_tx_status'];
            }
            
            $rs_to_assign[] = array(
                'transaction_type' => $this->translateTransactionType($r['amz_tx_type']),
                'amount' => self::formatAmount($r['amz_tx_amount']),
                'date' => date('Y-m-d H:i:s', $r['amz_tx_time']),
                'status' => $r['amz_tx_status'],
                'last_change' => date('Y-m-d H:i:s', $r['amz_tx_last_change']),
                'tx_id' => $r['amz_tx_amz_id'],
                'tx_expiration' => ($r['amz_tx_expiration'] != 0 ? date('Y-m-d H:i:s', $r['amz_tx_expiration']) : '-')
            );
        }
        
        if (sizeof($rs_to_assign) > 0) {
            $this->smarty->assign(array(
                'rs' => $rs_to_assign,
                'order_ref' => $order_ref,
                'reference_status' => $reference_status
            ));
            return $this->display(__FILE__, 'views/templates/admin/order_history.tpl');
        }
    }
    
    public function getOrderAuthorizedAmount($order_ref)
    {
        $q = 'SELECT SUM(amz_tx_amount) AS auth_sum FROM ' . _DB_PREFIX_ . 'amz_transactions WHERE
        amz_tx_order_reference = \'' . pSQL($order_ref) . '\'
        AND
        amz_tx_type=\'auth\'
        AND
        amz_tx_status = \'Open\'';
        
        $r = Db::getInstance()->getRow($q);
        return (float) $r['auth_sum'];
    }
    
    public function getOrderCapturedAmount($order_ref)
    {
        $q = 'SELECT SUM(amz_tx_amount) AS capture_sum FROM ' . _DB_PREFIX_ . 'amz_transactions WHERE
        amz_tx_order_reference = \'' . pSQL($order_ref) . '\'
        AND
        amz_tx_type=\'capture\'
        AND
        amz_tx_status = \'Completed\'';
        $r = Db::getInstance()->getRow($q);
        return (float) $r['capture_sum'];
    }
    
    public function getOrderRefundedAmount($order_ref)
    {
        $q = 'SELECT SUM(amz_tx_amount) AS refund_sum FROM ' . _DB_PREFIX_ . 'amz_transactions WHERE
        amz_tx_order_reference = \'' . pSQL($order_ref) . '\'
        AND
        amz_tx_type=\'refund\'
        AND
        amz_tx_status = \'Completed\'';
        $r = Db::getInstance()->getRow($q);
        return (float) $r['refund_sum'];
    }
    
    public static function getOrderOpenAuthorizations($order_ref)
    {
        $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions WHERE
        amz_tx_order_reference = \'' . pSQL($order_ref) . '\'
        AND
        amz_tx_type=\'auth\'
        AND
        amz_tx_status = \'Open\'';
        $rs = Db::getInstance()->ExecuteS($q);
        $ret = array();
        foreach ($rs as $r) {
            $ret[] = $r;
        }
        return $ret;
    }
    
    public static function getOrderCaptures($order_ref)
    {
        $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions WHERE
        amz_tx_order_reference = \'' . pSQL($order_ref) . '\'
        AND
        amz_tx_type=\'capture\'';
        $rs = Db::getInstance()->ExecuteS($q);
        $ret = array();
        foreach ($rs as $r) {
            $ret[] = $r;
        }
        
        return $ret;
    }
    
    public static function getOrderUnclosedCaptures($order_ref)
    {
        $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions WHERE
        amz_tx_order_reference = \'' . pSQL($order_ref) . '\'
        AND
        amz_tx_status != \'Closed\'
        AND
        amz_tx_type=\'capture\'';
        $rs = Db::getInstance()->ExecuteS($q);
        $ret = array();
        foreach ($rs as $r) {
            $ret[] = $r;
        }
        
        return $ret;
    }
    
    public static function getOrderState($order_ref)
    {
        $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions WHERE
        amz_tx_order_reference = \'' . pSQL($order_ref) . '\'
        AND
        amz_tx_type=\'order_ref\'';
        $r = Db::getInstance()->getRow($q);
        return $r['amz_tx_status'];
    }
    
    public function intelligentRefresh($r)
    {
        switch ($r['amz_tx_type']) {
            case 'refund':
                $this->refreshRefund($r['amz_tx_amz_id']);
                break;
                
            case 'capture':
                $this->refreshCapture($r['amz_tx_amz_id']);
                break;
                
            case 'auth':
                $this->refreshAuthorization($r['amz_tx_amz_id']);
                break;
                
            case 'order_ref':
                $this->refreshOrderReference($r['amz_tx_amz_id']);
                break;
        }
    }
    
    public function refreshRefund($refund_id)
    {
        $service = $this->getService();
        $refund_request = new OffAmazonPaymentsService_Model_GetRefundDetailsRequest();
        $refund_request->setSellerId($this->merchant_id);
        $refund_request->setAmazonRefundId($refund_id);
        try {
            $response = $service->getRefundDetails($refund_request);
            $details = $response->getGetRefundDetailsResult()->getRefundDetails();
            $sql_arr = array(
                'amz_tx_status' => pSQL((string) $details->getRefundStatus()->getState()),
                'amz_tx_last_change' => pSQL(strtotime((string) $details->getRefundStatus()->getLastUpdateTimestamp())),
                'amz_tx_last_update' => pSQL(time())
            );
            Db::getInstance()->update('amz_transactions', $sql_arr, " amz_tx_amz_id = '" . pSQL($refund_id) . "'");
        } catch (OffAmazonPaymentsService_Exception $e) {
            echo 'ERROR: ' . $e->getErrorMessage();
        }
    }
    
    public function refreshCapture($capture_id)
    {
        $service = $this->getService();
        $capture_request = new OffAmazonPaymentsService_Model_GetCaptureDetailsRequest();
        $capture_request->setSellerId($this->merchant_id);
        $capture_request->setAmazonCaptureId($capture_id);
        try {
            $response = $service->getCaptureDetails($capture_request);
            $details = $response->getGetCaptureDetailsResult()->getCaptureDetails();
            
            $sql_arr = array(
                'amz_tx_status' => pSQL((string) $details->getCaptureStatus()->getState()),
                'amz_tx_last_change' => pSQL(strtotime((string) $details->getCaptureStatus()->getLastUpdateTimestamp())),
                'amz_tx_amount_refunded' => pSQL((float) $details->getRefundedAmount()->getAmount()),
                'amz_tx_last_update' => pSQL(time())
            );
            Db::getInstance()->update('amz_transactions', $sql_arr, " amz_tx_amz_id = '" . pSQL($capture_id) . "'");
            
            if ($sql_arr['amz_tx_status'] == 'Completed') {
                $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions WHERE amz_tx_type=\'capture\'
                AND amz_tx_amz_id = \'' . pSQL($capture_id) . '\'';
                $r = Db::getInstance()->getRow($q);
                if ($r) {
                    $total = AmazonTransactions::getOrderRefTotal($r['amz_tx_order_reference']);
                    if ($r['amz_tx_amount'] == $total) {
                        AmazonTransactions::closeOrder($this, $service, $r['amz_tx_order_reference']);
                    }
                }
            }
        } catch (OffAmazonPaymentsService_Exception $e) {
            echo 'ERROR: ' . $e->getErrorMessage();
        }
    }
    
    public function refreshAuthorization($auth_id)
    {
        $service = $this->getService();
        $authorization_request = new OffAmazonPaymentsService_Model_GetAuthorizationDetailsRequest();
        $authorization_request->setSellerId($this->merchant_id);
        $authorization_request->setAmazonAuthorizationId($auth_id);
        try {
            $response = $service->getAuthorizationDetails($authorization_request);
            $details = $response->getGetAuthorizationDetailsResult()->getAuthorizationDetails();
            
            // $address = $details->getAuthorizationBillingAddress();
            
            $sql_arr = array(
                'amz_tx_status' => pSQL((string) $details->getAuthorizationStatus()->getState()),
                'amz_tx_last_change' => pSQL(strtotime((string) $details->getAuthorizationStatus()->getLastUpdateTimestamp())),
                'amz_tx_last_update' => pSQL(time())
            );
            Db::getInstance()->update('amz_transactions', $sql_arr, " amz_tx_amz_id = '" . pSQL($auth_id) . "'");
            
            if ((string) $details->getAuthorizationStatus()->getState() == 'Declined') {
                $reason = (string) $details->getAuthorizationStatus()->getReasonCode();
                
                if ($reason == 'AmazonRejected') {
                    $order_ref = AmazonTransactions::getOrderRefFromAmzId($auth_id);
                    $this->cancelOrder($order_ref);
                }
                $this->intelligentDeclinedMail($auth_id, $reason);
                if ($this->decline_status_id > 0) {
                    $order_ref = AmazonTransactions::getOrderRefFromAmzId($auth_id);
                    AmazonTransactions::setOrderStatusDeclined($order_ref, true);
                }
            } elseif ((string) $details->getAuthorizationStatus()->getState() == 'Open') {
                $order_ref = AmazonTransactions::getOrderRefFromAmzId($auth_id);
                AmazonTransactions::setOrderStatusAuthorized($order_ref, true);
            } elseif ((string) $details->getAuthorizationStatus()->getState() == 'Closed' && (string) $details->getAuthorizationStatus()->getReasonCode() == 'MaxCapturesProcessed') {
                $captureIds = $details->getIdList()->getMember();
                if (isset($captureIds[0])) {
                    $captureId = (string)$captureIds[0];
                    $order_ref = AmazonTransactions::getOrderRefFromAmzId($auth_id);
                    $capture_request = new OffAmazonPaymentsService_Model_GetCaptureDetailsRequest();
                    $capture_request->setSellerId($this->merchant_id);
                    $capture_request->setAmazonCaptureId($captureId);
                    try {
                        $response = $service->getCaptureDetails($capture_request);
                        $details = $response->getGetCaptureDetailsResult()->getCaptureDetails();
                        
                        $sql_arr = array(
                            'amz_tx_order_reference' => pSQL($order_ref),
                            'amz_tx_type' => 'capture',
                            'amz_tx_time' => pSQL(time()),
                            'amz_tx_expiration' => 0,
                            'amz_tx_amount' => pSQL((string)$details->getCaptureAmount()->getAmount()),
                            'amz_tx_status' => pSQL($details->getCaptureStatus()->getState()),
                            'amz_tx_reference' => pSQL($details->getCaptureReferenceId()),
                            'amz_tx_amz_id' => pSQL($details->getAmazonCaptureId()),
                            'amz_tx_last_change' => pSQL(time()),
                            'amz_tx_last_update' => pSQL(time())
                        );
                        
                        $checkQuery = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions
                            WHERE `amz_tx_order_reference` = \'' . pSQL($order_ref) . '\'
                            AND `amz_tx_type` = \'capture\'
                            ';
                        if ($row = Db::getInstance()->getRow($checkQuery)) {
                            return;
                        } else {
                            Db::getInstance()->insert('amz_transactions', $sql_arr);
                            AmazonTransactions::setOrderStatusCapturedSuccesfully($order_ref);
                        }
                    } catch (OffAmazonPaymentsService_Exception $e) {
                        echo 'ERROR: ' . $e->getMessage();
                    }
                }
            }
        } catch (OffAmazonPaymentsService_Exception $e) {
            echo 'ERROR: ' . $e->getErrorMessage();
        }
    }
    
    public function refreshOrderReference($order_ref)
    {
        $service = $this->getService();
        $order_ref_request = new OffAmazonPaymentsService_Model_GetOrderReferenceDetailsRequest();
        $order_ref_request->setSellerId($this->merchant_id);
        $order_ref_request->setAmazonOrderReferenceId($order_ref);
        try {
            $response = $service->getOrderReferenceDetails($order_ref_request);
            $details = $response->getGetOrderReferenceDetailsResult()->getOrderReferenceDetails();
            $sql_arr = array(
                'amz_tx_status' => pSQL((string) $details->getOrderReferenceStatus()->getState()),
                'amz_tx_last_change' => pSQL(strtotime((string) $details->getOrderReferenceStatus()->getLastUpdateTimestamp())),
                'amz_tx_last_update' => pSQL(time())
            );
            Db::getInstance()->update('amz_transactions', $sql_arr, " amz_tx_amz_id = '" . pSQL($order_ref) . "'");
        } catch (OffAmazonPaymentsService_Exception $e) {
            echo 'ERROR: ' . $e->getErrorMessage();
        }
    }
    
    public function closeOrder($order_ref)
    {
        $service = $this->getService();
        $order_ref_request = new OffAmazonPaymentsService_Model_CloseOrderReferenceRequest();
        $order_ref_request->setSellerId($this->merchant_id);
        $order_ref_request->setAmazonOrderReferenceId($order_ref);
        try {
            $response = $service->closeOrderReference($order_ref_request);
        } catch (OffAmazonPaymentsService_Exception $e) {
            echo 'ERROR: ' . $e->getErrorMessage();
        }
        return $response;
    }
    
    public function cancelOrder($order_ref)
    {
        $service = $this->getService();
        $order_ref_request = new OffAmazonPaymentsService_Model_CancelOrderReferenceRequest();
        $order_ref_request->setSellerId($this->merchant_id);
        $order_ref_request->setAmazonOrderReferenceId($order_ref);
        try {
            $response = $service->cancelOrderReference($order_ref_request);
        } catch (OffAmazonPaymentsService_Exception $e) {
            echo 'ERROR: ' . $e->getErrorMessage();
        }
        return $response;
    }
    
    public static function getClassForStatus($status)
    {
        switch ($status) {
            case 'Open':
            case 'Completed':
            case 'Closed':
                return 'amzGreen';
                
            case 'Pending':
                return 'amzOrange';
                
            default:
                return 'amzRed';
        }
    }
    
    public function getOrderActions($order_ref)
    {
        $order_state = $this->getOrderState($order_ref);
        $got_something = false;
        $this->smarty->assign(array(
            'order_state' => $order_state
        ));
        if ($order_state == 'Open' || $order_state == 'Closed') {
            $open_auth = self::getOrderOpenAuthorizations($order_ref);
            if (count($open_auth) > 0) {
                $open_auth_assigns = array();
                foreach ($open_auth as $r) {
                    $open_auth_assigns[] = array(
                        'amount' => self::formatAmount($r['amz_tx_amount']),
                        'date' => date('Y-m-d H:i:s', $r['amz_tx_time']),
                        'tx_id' => $r['amz_tx_amz_id'],
                        'tx_expiration' => ($r['amz_tx_expiration'] != 0 ? date('Y-m-d H:i:s', $r['amz_tx_expiration']) : '-')
                    );
                }
                $got_something = true;
                $this->smarty->assign(array(
                    'open_auth' => $open_auth_assigns
                ));
            }
        }
        if ($order_state == 'Open') {
            $amount_left_to_authorize = $this->getAmountLeftToAuthorize($order_ref);
            $amount_left_to_over_authorize = $this->getAmountLeftToOverAuthorize($order_ref);
            if ($amount_left_to_authorize > 0 || $amount_left_to_over_authorize > 0) {
                if ($amount_left_to_authorize + $amount_left_to_over_authorize > 0) {
                    $this->smarty->assign(array(
                        'authorize_tab' => true
                    ));
                    $this->smarty->assign(array(
                        'amount_left_to_authorize_raw' => $amount_left_to_authorize,
                        'amount_left_to_authorize' => self::formatAmount($amount_left_to_authorize),
                        'amount_maximum' => self::formatAmount($amount_left_to_authorize + $amount_left_to_over_authorize),
                        'amount_field' => self::formatAmount(($amount_left_to_authorize > 0 ? $amount_left_to_authorize : $amount_left_to_over_authorize)),
                        'order_ref' => $order_ref
                    ));
                    $got_something = true;
                }
            }
        }
        
        $captures = self::getOrderUnclosedCaptures($order_ref);
        if (count($captures) > 0) {
            $this->smarty->assign(array(
                'refunds_tab' => true
            ));
            $captures_to_assign = array();
            foreach ($captures as $r) {
                $captures_to_assign[] = array(
                    'amount' => self::formatAmount($r['amz_tx_amount']),
                    'amount_refunded' => self::formatAmount($r['amz_tx_amount_refunded']),
                    'amount_possible' => self::formatAmount(($refundable = (min((75 + $r['amz_tx_amount']), (round($r['amz_tx_amount'] * 1.15, 2))) - $r['amz_tx_amount_refunded']))),
                    'date' => date('Y-m-d H:i:s', $r['amz_tx_time']),
                    'status_class' => self::getClassForStatus($r['amz_tx_status']),
                    'status' => $r['amz_tx_status'],
                    'last_change' => date('Y-m-d H:i:s', $r['amz_tx_last_change']),
                    'tx_id' => $r['amz_tx_amz_id'],
                    'total_refund_button' => $r['amz_tx_amount'] - $r['amz_tx_amount_refunded'] > 0,
                    'total_refund_button_value' => $r['amz_tx_amount'] - $r['amz_tx_amount_refunded'],
                    'field_value' => self::formatAmount(($r['amz_tx_amount'] - $r['amz_tx_amount_refunded'] > 0 ? ($r['amz_tx_amount'] - $r['amz_tx_amount_refunded']) : $refundable))
                );
            }
            $got_something = true;
            $this->smarty->assign('captures', $captures_to_assign);
        }
        
        if ($got_something) {
            return $this->display(__FILE__, 'views/templates/admin/order_actions.tpl');
        }
        return false;
    }
    
    public function getAmountLeftToAuthorize($order_ref)
    {
        $total = AmazonTransactions::getOrderRefTotal($order_ref);
        $authorized = $this->getOrderAuthorizedAmount($order_ref);
        $captured = $this->getOrderCapturedAmount($order_ref);
        $left = $total - $authorized - $captured;
        $left = min($left, $total);
        $left = round(max(0, $left), 2);
        return $left;
    }
    
    public function getAmountLeftToOverAuthorize($order_ref)
    {
        $total = AmazonTransactions::getOrderRefTotal($order_ref);
        $authorized = $this->getOrderAuthorizedAmount($order_ref);
        $captured = $this->getOrderCapturedAmount($order_ref);
        
        $left = round(($total * 1.15), 2) - $authorized - $captured;
        
        $left -= self::getAmountLeftToAuthorize($order_ref);
        $left = round(max(0, $left), 2);
        
        if ($left > 75) {
            $left = 75;
        }
        
        return $left;
    }
    
    protected function hasNoPendingRefund($amz_reference_id)
    {
        $current_refund_state_and_id = AmazonTransactions::getCurrentAmzTransactionRefundStateAndId($amz_reference_id);
        return $current_refund_state_and_id['amz_tx_status'] != 'Pending';
    }
    
    public function getOrderRefundMaximum($order_ref)
    {
        $captured = $this->getOrderCapturedAmount($order_ref);
        $refunded = $this->getOrderRefundedAmount($order_ref);
        return $captured - $refunded;
    }
    
    public function getOrderSummary($order_ref)
    {
        $this->smarty->assign(array(
            'authorized_amount' => self::formatAmount(self::getOrderAuthorizedAmount($order_ref)),
            'captured_amount' => self::formatAmount(self::getOrderCapturedAmount($order_ref)),
            'refunded_amount' => self::formatAmount(self::getOrderRefundedAmount($order_ref))
        ));
        
        return $this->display(__FILE__, 'views/templates/admin/order_summary.tpl');
    }
    
    public static function formatAmount($amount)
    {
        return Tools::displayPrice($amount, Context::getContext()->currency);
    }
    
    public function translateTransactionType($str)
    {
        switch ($str) {
            case 'auth':
                $str = $this->l('Authorisation');
                break;
            case 'order_ref':
                $str = $this->l('Order');
                break;
            case 'capture':
                $str = $this->l('Withdrawal');
                break;
            case 'refund':
                $str = $this->l('Refund');
                break;
        }
        
        return $str;
    }
    
    public function shippingCapture()
    {
        if ($this->capture_mode == 'after_shipping') {
            $q = 'SELECT DISTINCT ao.amazon_order_reference_id FROM  ' . _DB_PREFIX_ . 'orders o
            JOIN ' . _DB_PREFIX_ . 'amz_orders ao ON o.id_order = ao.id_order
            JOIN ' . _DB_PREFIX_ . 'amz_transactions AS a1 ON (ao.amazon_order_reference_id = a1.amz_tx_order_reference AND a1.amz_tx_type = \'auth\' AND a1.amz_tx_status = \'Open\')
            LEFT JOIN ' . _DB_PREFIX_ . 'amz_transactions AS a2 ON (ao.amazon_order_reference_id = a2.amz_tx_order_reference AND a2.amz_tx_type = \'capture\')
            WHERE
            ao.amazon_order_reference_id != \'\'
            AND
            o.current_state = \'' . pSQL($this->capture_status_id) . '\'
            AND
            a2.amz_tx_id IS NULL';
            $rs = Db::getInstance()->ExecuteS($q);
            foreach ($rs as $r) {
                $ramz = AmazonTransactions::getAuthorizationForCapture($r['amazon_order_reference_id']);
                $auth_id = $ramz['amz_tx_amz_id'];
                AmazonTransactions::captureTotalFromAuth($this, $this->getService(), $auth_id);
            }
        }
    }
    
    public function sendSoftDeclinedMail($order_ref)
    {
        $this->sendDeclinedMail($order_ref, 'soft');
    }
    
    public function sendHardDeclinedMail($order_ref)
    {
        $this->sendDeclinedMail($order_ref, 'hard');
    }
    
    public function sendDeclinedMail($order_ref, $type)
    {
        $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_orders WHERE amazon_order_reference_id = \'' . pSQL($order_ref) . '\'';
        $rs = Db::getInstance()->ExecuteS($q);
        foreach ($rs as $r) {
            $order = new Order($r['id_order']);
            
            $lang_id = $order->id_lang;
            $reference = $order->reference;
            $order_date = $order->date_add;
            $customer = new Customer($order->id_customer);
            $email = $customer->email;
            
            if ($type == 'soft') {
                $subject = $this->l('Your payment was rejected by Amazon');
            } elseif ($type == 'hard') {
                $subject = $this->l('Your payment was rejected by Amazon - please contact us');
            }
            
            Mail::Send($lang_id, 'amazon_' . $type . '_decline', $subject, array(
                '{$ORDER_NR}' => $reference,
                '{$ORDER_DATE}' => $order_date
            ), $email, null, null, null, null, null, dirname(__FILE__) . '/mails/', false, $this->context->shop->id);
            
            $str = 'Mail sent: ' . 'amazon_' . $type . '_decline' . ' -> ' . $subject . ' -> ' . $email;
            file_put_contents('amz.log', $str, FILE_APPEND);
        }
    }
    
    public function intelligentDeclinedMail($amz_id, $reason)
    {
        if ($this->send_mails_on_decline == '1') {
            $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions WHERE amz_tx_amz_id = \'' . pSQL($amz_id) . '\'';
            $rs = Db::getInstance()->ExecuteS($q);
            foreach ($rs as $r) {
                if ($r['amz_tx_status'] == 'Declined' && $r['amz_tx_customer_informed'] == 0) {
                    $informed = 0;
                    if ($reason == 'InvalidPaymentMethod') {
                        $this->sendSoftDeclinedMail($r['amz_tx_order_reference']);
                        $informed = 1;
                    } elseif ($reason == 'AmazonRejected') {
                        $this->sendHardDeclinedMail($r['amz_tx_order_reference']);
                        $informed = 1;
                    }
                    
                    if ($informed == 1) {
                        $q = 'UPDATE ' . _DB_PREFIX_ . 'amz_transactions SET amz_tx_customer_informed = 1 WHERE amz_tx_id = \'' . (int) $r['amz_tx_id'] . '\'';
                        Db::getInstance()->execute($q);
                    }
                }
            }
        }
    }
    
    public static function currentSiteIsSSL()
    {
        return Tools::usingSecureMode();
    }
    
    public static function prepareCookieValueForPrestaShopUse($str)
    {
        return str_replace('|', '-HORDIV-', $str);
    }
    
    public static function prepareCookieValueForAmazonPaymentsUse($str)
    {
        return str_replace('-HORDIV-', '|', $str);
    }
    
    public static function addressAlreadyExists($address, $customer)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
                SELECT a.`id_address`
                FROM `' . _DB_PREFIX_ . 'address` a
                WHERE a.`lastname` = "' . pSQL($address->lastname) . '"
                  AND a.`firstname` = "' . pSQL($address->firstname) . '"
                  AND a.`address1` = "' . pSQL($address->address1) . '"
                  AND a.`postcode` = "' . pSQL($address->postcode) . '"
                  AND a.`city` = "' . pSQL($address->city) . '"
                  AND a.`phone` = "' . pSQL($address->phone) . '"
                  AND a.`id_customer` = "' . pSQL($customer->id) . '"
                  AND a.`deleted` = 0
                ');
        
        return $result['id_address'] ? true : false;
    }
    
    public static function switchOrderToCustomer($customer_id, $order_id, $unset_cookie = false)
    {
        $order = new Order($order_id);
        $order->id_customer = $customer_id;
        $order->save();
        if ($unset_cookie) {
            unset(Context::getContext()->cookie->amz_connect_order);
        }
        return true;
    }
    
    public function getLanguageCodeForSimplePath()
    {
        return str_replace('-', '_', $this->getWidgetLanguageCode());
    }
    
    public function getBannersForLanguageCode()
    {
        $banners = array(
            'de' => array(
                'light' => array('header' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/DE/DELightGrey900x60.jpg', 'product' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/DE/DELightGrey300x60.jpg', 'footer' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/DE/DELightGrey230x60.jpg'),
                'dark' => array('header' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/DE/DEDarkGrey900x60.jpg', 'product' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/DE/DEDarkGrey300x60.jpg', 'footer' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/DE/DEDarkGrey230x60.jpg')
            ),
            'at' => array(
                'light' => array('header' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/DE/DELightGrey900x60.jpg', 'product' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/DE/DELightGrey300x60.jpg', 'footer' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/DE/DELightGrey230x60.jpg'),
                'dark' => array('header' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/DE/DEDarkGrey900x60.jpg', 'product' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/DE/DEDarkGrey300x60.jpg', 'footer' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/DE/DEDarkGrey230x60.jpg')
            ),
            'es' => array(
                'light' => array('header' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/ES/ESLightGrey900x60.jpg', 'product' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/ES/ESLightGrey300x60.jpg', 'footer' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/ES/ESLightGrey230x60.jpg'),
                'dark' => array('header' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/ES/ESDarkGrey900x60.jpg', 'product' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/ES/ESDarkGrey300x60.jpg', 'footer' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/ES/ESDarkGrey230x60.jpg')
            ),
            'fr' => array(
                'light' => array('header' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/FR/FRLightGrey900x60.jpg', 'product' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/FR/FRLightGrey300x60.jpg', 'footer' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/FR/FRLightGrey230x60.jpg'),
                'dark' => array('header' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/FR/FRDarkGrey900x60.jpg', 'product' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/FR/FRDarkGrey300x60.jpg', 'footer' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/FR/FRDarkGrey230x60.jpg')
            ),
            'it' => array(
                'light' => array('header' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/IT/ITLightGrey900x60.jpg', 'product' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/IT/ITLightGrey300x60.jpg', 'footer' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/IT/ITLightGrey230x60.jpg'),
                'dark' => array('header' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/IT/ITDarkGrey900x60.jpg', 'product' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/IT/ITDarkGrey300x60.jpg', 'footer' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/IT/ITDarkGrey230x60.jpg')
            ),
            'uk' => array(
                'light' => array('header' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/UK/UKLightGrey900x60.jpg', 'product' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/UK/UKLightGrey300x60.jpg', 'footer' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/UK/UKLightGrey230x60.jpg'),
                'dark' => array('header' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/UK/UKDarkGrey900x60.jpg', 'product' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/UK/UKDarkGrey300x60.jpg', 'footer' => 'https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Banners/UK/UKDarkGrey230x60.jpg')
            )
        );
        switch ($this->context->language->iso_code) {
            case 'de':
                return $banners['de'];
            case 'at':
                return $banners['at'];
            case 'us':
            case 'en':
                return $banners['uk'];
            case 'fr':
                return $banners['fr'];
            case 'it':
                return $banners['it'];
            case 'es':
                return $banners['es'];
            default:
                return $banners['uk'];
        }
    }
    
    public function getDefaultModuleRegion()
    {
        switch ($this->context->language->iso_code) {
            case 'de':
                return 'DE';
            case 'us':
                return 'US';
            case 'en':
            case 'gb':
            case 'uk':
                return 'UK';
            case 'fr':
                return 'FR';
            case 'it':
                return 'IT';
            case 'es':
                return 'ES';
            case 'jp':
                return 'JP';
        }
    }
    
    public function getWidgetLanguageCode()
    {
        switch ($this->context->language->iso_code) {
            case 'de':
                return 'de-DE';
            case 'us':
            case 'en':
                return 'en-GB';
            case 'fr':
                return 'fr-FR';
            case 'it':
                return 'it-IT';
            case 'es':
                return 'es-ES';
            default:
                return 'en-GB';
        }
    }
    
    protected function hasSetValidAmazonAddress()
    {
        return (isset($this->context->cookie->has_set_valid_amazon_address) && ($this->context->cookie->has_set_valid_amazon_address === true || $this->context->cookie->has_set_valid_amazon_address == '1'));
    }
    
    protected function isValidOrderReference($order_ref)
    {
        $service = $this->getService();
        $get_order_reference_details_request = new OffAmazonPaymentsService_Model_GetOrderReferenceDetailsRequest();
        $get_order_reference_details_request->setSellerId($this->merchant_id);
        $get_order_reference_details_request->setAmazonOrderReferenceId($order_ref);
        $is_valid = false;
        try {
            $reference_details_result_wrapper = $service->getOrderReferenceDetails($get_order_reference_details_request);
            if ($reference_details_result_wrapper->GetOrderReferenceDetailsResult->getOrderReferenceDetails()->getOrderReferenceStatus()->getState() != 'Canceled') {
                $is_valid = true;
            } else {
                $this->exceptionLog(false, 'Method isValidOrderReference: ' . print_r($reference_details_result_wrapper, true));
            }
        } catch (Exception $e) {
            $this->exceptionLog($e);
        }
        return $is_valid;
    }
    
    public function requestTokenInfo($accessTokenValue)
    {
        $c = curl_init($this->getLpaApiUrl() . '/auth/o2/tokeninfo?access_token=' . urlencode($accessTokenValue));
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_CAINFO, $this->ca_bundle_file);
        $r = curl_exec($c);
        curl_close($c);
        $d = Tools::jsonDecode($r);
        return $d;
    }
    
    public function requestProfile($accessTokenValue)
    {
        $c = curl_init($this->getLpaApiUrl() . '/user/profile');
        curl_setopt($c, CURLOPT_HTTPHEADER, array(
            'Authorization: bearer ' . $accessTokenValue
        ));
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_CAINFO, $this->ca_bundle_file);
        $r = curl_exec($c);
        curl_close($c);
        $d = Tools::jsonDecode($r);
        return $d;
    }
    
    public static function prepareNamesArray($names_array)
    {
        $regex = '/[^a-zA-ZäöüÄÖÜßÂâÀÁáàÇçÈÉËëéèÎîÏïÙÛùúòóûêôíÍŸÿªñÑ\s]/u';
        $names_array[0] = preg_replace($regex, '', $names_array[0]);
        $names_array[1] = preg_replace($regex, '', $names_array[1]);
        
        $names_array[0] = preg_replace('/(\d+)/', ' ', $names_array[0]);
        $names_array[0] = trim(preg_replace('/ {2,}/', ' ', $names_array[0]));
        
        $names_array[1] = preg_replace('/(\d+)/', ' ', $names_array[1]);
        $names_array[1] = trim(preg_replace('/ {2,}/', ' ', $names_array[1]));
        
        if (trim($names_array[1]) == '') {
            $splitted_names_array = explode(' ', $names_array[0], 2);
            $names_array[0] = $splitted_names_array[0];
            if (!isset($splitted_names_array[1]) || trim($splitted_names_array[1]) == '') {
                $names_array[1] = $names_array[0];
            } else {
                $names_array[1] = $splitted_names_array[1];
            }
        }
        return $names_array;
    }
    
    public static function prepareAddressLines(Address $address)
    {
        $address->address1 = Tools::str_replace_once('_', '-', $address->address1);
        $address->address2 = Tools::str_replace_once('_', '-', $address->address2);
        return $address;
    }
    
    public function getBaseLink($id_shop = null, $ssl = null, $relative_protocol = false)
    {
        static $force_ssl = null;
        
        if ($ssl === null) {
            if ($force_ssl === null) {
                $force_ssl = (Configuration::get('PS_SSL_ENABLED') && Configuration::get('PS_SSL_ENABLED_EVERYWHERE'));
            }
            $ssl = $force_ssl;
        }
        
        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && $id_shop !== null) {
            $shop = new Shop($id_shop);
        } else {
            $shop = Context::getContext()->shop;
        }
        
        if ($relative_protocol) {
            $base = '//'.($ssl && Configuration::get('PS_SSL_ENABLED') ? $shop->domain_ssl : $shop->domain);
        } else {
            $base = (($ssl && Configuration::get('PS_SSL_ENABLED')) ? 'https://'.$shop->domain_ssl : 'http://'.$shop->domain);
        }
        
        return $base;
    }
    
    public function debugAddressObject($address)
    {
        if (is_object($address)) {
            $log = array();
            $vars = array('alias', 'company', 'lastname', 'firstname', 'vat_number', 'address1', 'address2', 'postcode', 'city', 'phone', 'dni', 'id_state', 'id_country');
            foreach ($vars as $var) {
                if (isset($address->$var)) {
                    $log[] = $var . '=' . $address->$var;
                } else {
                    $log[] = $var . '=';
                }
            }
            return join(";", $log);
        }
    }
    
    public function getLogFileName()
    {
        return CURRENT_MODULE_DIR . '/amz_exception.log';
    }
    
    public function exceptionLog($e, $string = false)
    {
        $logstr = date("Y-m-d H:i:s") . ' Exception logging: ' . "\r\n";
        if ($e) {
            $logstr.= print_r($e, true);
        }
        if ($string) {
            $logstr.= $string;
        }
        $logstr.= "\r\n\r\n";
        file_put_contents($this->getLogFileName(), $logstr, FILE_APPEND);
    }
}
