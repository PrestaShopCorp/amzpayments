<?php
/**
 * 2013-2019 Amazon Advanced Payment APIs Modul
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
 *  @copyright 2013-2019 patworx multimedia GmbH
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class AmazonPaymentsTroubleshooter
{
    
    protected static $orange_color_def = 'style="color:#FF9900;"';
    protected static $eye_icon_def = '<i class="fa fa-eye" aria-hidden="true"></i>&nbsp;';
    
    protected $troubleshooter;
    
    protected static $tests = array(
        array(
            'name' => 'Amazon pay activated',
            'method' => 'Activated',
        ),
        array(
            'name' => 'Amazon keys provided',
            'method' => 'KeysProvided',
        ),
        array(
            'name' => 'KYC passed',
            'method' => 'KYCPassed',
        ),
        array(
            'name' => 'Module up to date',
            'method' => 'Versioncheck',
        ),
        array(
            'name' => 'Amazon Pay hook integrity',
            'method' => 'HookIntegrity',
        ),
        array(
            'name' => 'Amazon Pay module integrity',
            'method' => 'ModuleIntegrity',
        ),
        array(
            'name' => 'SSL Enabled',
            'method' => 'SSLEnabled',
        ),
        array(
            'name' => 'Currency Restrictions',
            'method' => 'CurrencyRestrictions',
        ),
        array(
            'name' => 'Group Restrictions',
            'method' => 'GroupRestrictions',
        ),
        array(
            'name' => 'Country Restrictions',
            'method' => 'CountryRestrictions',
        ),
        array(
            'name' => 'Compulsory fields in datatable',
            'method' => 'CompulsoryFields',
        ),
        array(
            'name' => 'Amazon Servers accessibility',
            'method' => 'ServerConnect',
        ),
        array(
            'name' => 'Delivery Method module incompatibility',
            'method' => 'ModuleCheckDelivery',
        ),
        array(
            'name' => 'Express Checkout module incompatibility',
            'method' => 'ModuleCheckCheckout',
        ),
    );
    
    protected $hooks = array(
        'displayFooter',
        'displayProductButtons',
        'displayBanner',
        'displayTopColumn',
        'actionCarrierUpdate',
        'actionCustomerLogoutAfter',
        'displayBackOfficeHeader',
        'displayShoppingCartFooter',
        'displayNav',
        'adminOrder',
        'updateOrderStatus',
        'displayBackOfficeFooter',
        'displayPayment',
        'paymentReturn',
        'payment',
        'displayPaymentEU',
        'actionDispatcher',
        'displayBeforeShoppingCartBlock',
        'header',
    );
    
    protected $amzpayments;
    
    protected $test_details = array(
        '####MISSING_HOOKS####' => array(),
        '####MODULE_INTEGRITY####' => array(),
        '####COMPULSORY_FIELDS####' => array(),
        '####SERVER_ACCESSIBILITY####' => array(),
        '####DELIVERY_METHOD_INCOMPATIBILITY####' => array(),
        '####EXPRESS_CHECKOUT_INCOMPATIBILITY####' => array()
    );
    
    public function __construct($amzpayments)
    {
        $this->amzpayments = $amzpayments;
    }
    
    public static function fetchCommonlyFacedProblems($amzpayments)
    {
        $troubleshooter = new self($amzpayments);
        $url = $amzpayments->getCommonlyFacedProblemsJsonForLanguageCode();
        $json_data = $troubleshooter->requestJson($url);
        header('Content-Type: application/json');
        if (isset($json_data['CommonlyFacedProblems'])) {
            echo json_encode(
                array(
                'error' => 'false',
                'CommonlyFacedProblems' => $json_data['CommonlyFacedProblems'],
                'l' => array(
                    'rootcause' => $amzpayments->l('Root Cause'),
                    'resolution' => $amzpayments->l('Resolution Step By Step'),
                    'detailedinformation' => $amzpayments->l('Detailed Information'),
                )
                )
            );
        } else {
            echo json_encode(array('error' => 'true', 'CommonlyFacedProblems' => array()));
        }
        die();
    }
        
    public static function generateResults($amzpayments)
    {
        $troubleshooter = new self($amzpayments);
        $troubleshooter->troubleshoot();
        
        $url = $amzpayments->getTroubleshooterJsonForLanguageCode();
        $json_data = $troubleshooter->requestJson($url);
        header('Content-Type: application/json');
        
        $results = array();
        if (isset($json_data['Troubleshooter'])) {
            foreach (self::$tests as $testnr => $test) {
                $results[] = array(
                    'title' => $json_data['Troubleshooter'][$testnr]['TestName'],
                    'status' => $test['status'],
                    'description' => $troubleshooter->prepareTroubleshooterDescription(
                        $json_data['Troubleshooter'][$testnr]['Description'],
                        $test
                    )
                );
            }
        }
        Context::getContext()->smarty->assign('troubleshooter_results', $results);
        $troubleshooter_html = Context::getContext()->smarty->fetch($amzpayments->getLocalPath() . 'views/templates/admin/_troubleshooter.tpl');

        echo json_encode(array('troubleshooter' => $troubleshooter_html));
        die();
    }
    
    public function troubleshoot()
    {
        foreach (self::$tests as &$test) {
            $method = 'ts' . $test['method'];
            if ($this->{$method}()) {
                $test['status'] = 1;
            } else {
                $test['status'] = 0;
            }
        }
    }
    
    public function tsActivated()
    {
        return Module::isEnabled('amzpayments');
    }
    
    public function tsKeysProvided()
    {
        $keysToCheck = array(
            'merchant_id' => 'AMZ_MERCHANT_ID',
            'access_key' => 'ACCESS_KEY',
            'secret_key' => 'SECRET_KEY',
            'client_id' => 'AMZ_CLIENT_ID',
        );
        foreach ($keysToCheck as $k) {
            if (trim(Configuration::get($k)) == '') {
                return false;
            }
        }
        return true;
    }
    
    public function tsKYCPassed()
    {
        if (Configuration::get('AMZ_MERCHANT_ID') != '') {
            $button_url = 'https://payments.amazon.de/gp/widgets/button';
            if (Tools::strtolower(Configuration::get('REGION')) == 'uk') {
                $button_url = 'https://payments.amazon.co.uk/gp/widgets/button';
            } elseif (Tools::strtolower(Configuration::get('REGION')) == 'us') {
                $button_url = 'https://payments.amazon.com/gp/widgets/button';
            } elseif (Tools::strtolower(Configuration::get('REGION')) == 'jp') {
                $button_url = 'https://payments.amazon.co.jp/gp/widgets/button';
            }
            $check = getimagesize($button_url . "?sellerId=" . Configuration::get('AMZ_MERCHANT_ID'));
            if ($check[0] > 1) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }
    
    public function tsVersioncheck()
    {
        try {
            $module_list_addons = Tools::addonsRequest('native');
            $module_list_xml = simplexml_load_string($module_list_addons);
            foreach ($module_list_xml as $module) {
                if ($module->name == 'amzpayments') {
                    $addons_version = $module->version;
                    if (version_compare($module->version, $this->amzpayments->version, '>')) {
                        return false;
                    }
                }
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
    
    public function tsHookIntegrity()
    {
        $have_all_hooks = true;
        foreach ($this->hooks as $hook) {
            $id_hook = Hook::getIdByName($hook);
            if ((int)$id_hook > 0) {
                $sql = 'SELECT `id_hook` FROM `'._DB_PREFIX_.'hook_module` WHERE `id_module` = '.(int)$this->amzpayments->id . ' AND `id_hook` = ' . (int)$id_hook;
                if (!Db::getInstance()->executeS($sql)) {
                    $this->test_details['####MISSING_HOOKS####'][] = $hook;
                    $have_all_hooks = false;
                }
            }
        }
        return $have_all_hooks;
    }
    
    public function tsModuleIntegrity()
    {
        $have_integrity = true;
        if (!is_writeable(CURRENT_MODULE_DIR . '/logs/')) {
            $this->test_details['####MODULE_INTEGRITY####'][] = 'Log directory not writeable';
            $have_integrity = false;
        }
        if (Tools::substr(CURRENT_MODULE_DIR, -11) != 'amzpayments') {
            $this->test_details['####MODULE_INTEGRITY####'][] = 'Wrong module directory name';
            $have_integrity = false;
        }
        if (is_dir(_PS_OVERRIDE_DIR_ . 'modules/amzpayments')) {
            $this->test_details['####MODULE_INTEGRITY####'][] = 'Overrides for module exist';
            $have_integrity = false;
        }
        if (is_dir(_PS_THEME_DIR_ . 'modules/amzpayments/views/templates')) {
            $this->test_details['####MODULE_INTEGRITY####'][] = 'Possible overrides for theme-files exist';
            $have_integrity = false;
        }
        if (!$this->checkDbForTableExists('amz_transactions')) {
            $this->test_details['####MODULE_INTEGRITY####'][] = 'Missing DB table for transactions';
            $have_integrity = false;
        }
        if (!$this->checkDbForTableExists('amz_orders')) {
            $this->test_details['####MODULE_INTEGRITY####'][] = 'Missing DB table for orders';
            $have_integrity = false;
        }
        if (!$this->checkDbForTableExists('amz_address')) {
            $this->test_details['####MODULE_INTEGRITY####'][] = 'Missing DB table for addresses';
            $have_integrity = false;
        }
        if (!$this->checkDbForTableExists('amz_customer')) {
            $this->test_details['####MODULE_INTEGRITY####'][] = 'Missing DB table for customers';
            $have_integrity = false;
        }
        
        return $have_integrity;
        
     /*- access rights (in file system)
- folder location (that it's correctly done)
- overrides (there should be no php overrides on the amazon payments files!)
- specific files in theme-folder that are blocking the default theme-files of the module
- database tables correct*/
    }
    
    public function tsSSLEnabled()
    {
        return Configuration::get('PS_SSL_ENABLED');
    }
    
    public function tsCurrencyRestrictions()
    {
        return $this->checkDbModuleRestriction('currency');
    }
    
    public function tsGroupRestrictions()
    {
        return $this->checkDbModuleRestriction('group');
    }
    
    public function tsCountryRestrictions()
    {
        return $this->checkDbModuleRestriction('country');
    }
    
    public function tsCompulsoryFields()
    {
        $return = Db::getInstance()->executeS('
		SELECT id_required_field, object_name, field_name
		FROM '._DB_PREFIX_.'required_field');
        if ($return) {
            foreach ($return as $r) {
                $this->test_details['####COMPULSORY_FIELDS####'][] = $r['object_name'] . ': ' . $r['field_name'];
            }
        }
        return !$return;
    }
    
    public function tsServerConnect()
    {
        $all_available = true;
        $urls = array('https://api.amazon.com/user/profile', 'https://eu.account.amazon.com/ap/oa', 'https://mws.amazonservices.com');
        foreach ($urls as $url) {
            try {
                if (!$this->urlIsReachable($url)) {
                    $this->test_details['####SERVER_ACCESSIBILITY####'][] = $url;
                    $all_available = false;
                }
            } catch (Exception $e) {
                $all_available = false;
            }
        }
        return $all_available;
    }
    
    public function tsModuleCheckDelivery()
    {
        $return = true;
        $delivery_modules = array('cubyn', 'relaiscolis', 'relaiscolisplus', 'colissimo');
        foreach ($delivery_modules as $m) {
            if ($this->checkForModule($m)) {
                $this->test_details['####DELIVERY_METHOD_INCOMPATIBILITY####'][] = $m;
                $return = false;
            }
        }
        return $return;
    }
    
    public function tsModuleCheckCheckout()
    {
        $return = true;
        $delivery_modules = array('onepagecheckout', 'onepagecheckoutps', 'supercheckout');
        foreach ($delivery_modules as $m) {
            if ($this->checkForModule($m)) {
                $this->test_details['####EXPRESS_CHECKOUT_INCOMPATIBILITY####'][] = $m;
                $return = false;
            }
        }
        return $return;
    }
    
    protected function checkForModule($module)
    {
        return Module::isInstalled($module);
    }
    
    protected function urlIsReachable($url)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        $result = curl_exec($curl);
        if ($result !== false) {
            return true;
        } else {
            return false;
        }
    }
    
    protected function checkDbModuleRestriction($table)
    {
        return Db::getInstance()->executeS('
		SELECT *
		FROM '._DB_PREFIX_.'module_' . pSQL($table) . '
        WHERE id_module = \'' . (int)$this->amzpayments->id . '\'');
    }
    
    protected function checkDbForTableExists($table)
    {
        return Db::getInstance()->executeS("SHOW TABLES LIKE '". _DB_PREFIX_ . pSQL($table) ."'");
    }
    
    protected function prepareTroubleshooterDescription($desc, $test)
    {
        if ($test['status'] == '1') {
            return;
        }
        $details = $this->test_details;
        foreach ($details as &$detail) {
            if (sizeof($detail) > 0) {
                $detail = join(", ", $detail);
            }
        }
        $desc = str_replace(array_keys($details), $details, $desc);
        $desc = str_replace('<a ', ' <a ' . self::$orange_color_def . ' ', $desc);
        $desc = str_replace('target="_blank">', 'target="_blank">' . self::$eye_icon_def, $desc);
        return $desc;
    }
    
    protected function requestJson($url, $utf8_encode = false)
    {
        $c = curl_init($url);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        if ($utf8_encode) {
            $r = utf8_encode(curl_exec($c));
        } else {
            $r = curl_exec($c);
        }
        if (curl_error($c)) {
            $this->amzpayments->exceptionLog(curl_error($c));
        }
        curl_close($c);
        $d = Tools::jsonDecode($r, true);
        return $d;
    }
}
