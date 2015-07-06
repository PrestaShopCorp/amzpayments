<?php
/**
 * 2013-2015 Amazon Advanced Payment APIs Modul
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
 *  @copyright 2013-2015 patworx multimedia GmbH
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_'))
	exit;

/**
ensure the __DIR__ constant is defined for PHP 4.0.6 and newer
(@__DIR__ == '__DIR__') && define('__DIR__', realpath(dirname(__FILE__)));
*/
define('CURRENT_MODULE_DIR', realpath(dirname(__FILE__)));

require_once (CURRENT_MODULE_DIR.'/classes/AmazonTransactions.php');

class AmzPayments extends PaymentModule
{

	public $merchant_id;
	public $access_key;
	public $secret_key;
	public $client_id;
	public $region;
	public $lpa_mode;
	public $environment;
	public $authorization_mode = 'fast_auth';
	public $authorized_status_id = 3;
	public $capture_mode = 'after_shipping';
	public $capture_status_id = 5;
	public $provocation = 0;
	public $popup = 0;
	public $shippings_not_allowed = '';
	public $products_not_allowed = '';
	public $allow_guests = 1;
	public $button_size = 'x-large';
	public $button_size_lpa = 'x-large';
	public $button_color = 'orange';
	public $button_color_lpa = 'Gold';
	public $type_login = 'LwA';
	public $type_pay = 'PwA';
	public $ipn_status = 0;
	public $cron_status = 0;
	public $cron_password = '';
	public $send_mails_on_decline = 0;

	public $ca_bundle_file;

	private $_postErrors = array();
	private $pfid = 'A1AOZCKI9MBRZA';
	protected static $table_columns = array();
	public static $config_array = array('merchant_id' => 'MERCHANT_ID', 'access_key' => 'ACCESS_KEY', 'secret_key' => 'SECRET_KEY', 'client_id' => 'CLIENT_ID', 'region' => 'REGION', 'lpa_mode' => 'LPA_MODE', 'environment' => 'ENVIRONMENT', 'authorization_mode' => 'AUTHORIZATION_MODE', 'authorized_status_id' => 'AUTHORIZED_STATUS_ID', 'capture_mode' => 'CAPTURE_MODE', 'capture_status_id' => 'CAPTURE_STATUS_ID', 'provocation' => 'PROVOCATION', 'popup' => 'POPUP', 'shippings_not_allowed' => 'SHIPPINGS_NOT_ALLOWED', 'products_not_allowed' => 'PRODUCTS_NOT_ALLOWED', 'allow_guests' => 'ALLOW_GUEST', 'button_size' => 'BUTTON_SIZE', 'button_size_lpa' => 'BUTTON_SIZE_LPA', 'button_color' => 'BUTTON_COLOR', 'button_color_lpa' => 'BUTTON_COLOR_LPA', 'type_login' => 'TYPE_LOGIN', 'type_pay' => 'TYPE_PAY', 'ipn_status' => 'IPN_STATUS', 'cron_status' => 'CRON_STATUS', 'cron_password' => 'CRON_PASSWORD', 'send_mails_on_decline' => 'SEND_MAILS_ON_DECLINE', );

	public function __construct()
	{
		$this->name = 'amzpayments';
		$this->tab = 'payments_gateways';
		$this->version = '2.0.0';
		$this->author = 'patworx multimedia & alkim media';
		$this->need_instance = 1;
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6');
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

		if (!isset($this->merchant_id) || !isset($this->access_key) || !isset($this->secret_key) || !isset($this->region) || !isset($this->environment))
			$this->warning = $this->l('Your Amazon Payments details must be configured before using this module.');
		if (!count(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency has been set for this payment module');

		#$this->uninstallOverrides();
		#$this->installOverrides();

		if (isset($this->context->cookie->amz_access_token_set_time))
		{
			if ($this->context->cookie->amz_access_token_set_time < time() - 3000)
				unset($this->context->cookie->amz_access_token);
		}

	}

	private function reloadConfigVars()
	{
		$config = Configuration::getMultiple(self::$config_array);
		foreach (self::$config_array as $class_var => $config_var)
		{
			if (isset($config[$config_var]))
				$this->$class_var = $config[$config_var];
		}

	}

	public function getService($override = false)
	{
		include_once (CURRENT_MODULE_DIR.'/vendor/config.php');
		include_once (CURRENT_MODULE_DIR.'/vendor/functions.php');

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
		$config['caBundleFile'] = CURRENT_MODULE_DIR.'/vendor/ca-bundle.crt';
		$config['clientId'] = '';
		$config['cnName'] = 'sns.amazonaws.com';

		$this->ca_bundle_file = $config['caBundleFile'];

		if ($override && is_array($override))
		{
			foreach ($override as $k => $v)
				$config[$k] = $v;
		}

		return new OffAmazonPaymentsService_Client($config);
	}

	public function getPfId()
	{
		return $this->pfid;
	}

	public function install()
	{
		if (Shop::isFeatureActive())
			Shop::setContext(Shop::CONTEXT_ALL);

		/* set database */
		if (!$this->checkTableForColumn(_DB_PREFIX_.'address', 'amazon_order_reference_id'))
			Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'address` ADD `amazon_order_reference_id` varchar(50) NULL AFTER `dni`');
		if (!$this->checkTableForColumn(_DB_PREFIX_.'orders', 'amazon_auth_reference_id'))
			Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'orders` ADD `amazon_auth_reference_id` varchar(50) NULL AFTER `valid`');
		if (!$this->checkTableForColumn(_DB_PREFIX_.'orders', 'amazon_authorization_id'))
			Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'orders` ADD `amazon_authorization_id` varchar(50) NULL AFTER `valid`');
		if (!$this->checkTableForColumn(_DB_PREFIX_.'orders', 'amazon_order_reference_id'))
			Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'orders` ADD `amazon_order_reference_id` varchar(50) NULL AFTER `valid`');
		if (!$this->checkTableForColumn(_DB_PREFIX_.'orders', 'amazon_capture_id'))
			Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'orders` ADD `amazon_capture_id` varchar(50) NULL AFTER `valid`');
		if (!$this->checkTableForColumn(_DB_PREFIX_.'orders', 'amazon_capture_reference_id'))
			Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'orders` ADD `amazon_capture_reference_id` varchar(50) NULL AFTER `valid`');

		if (!$this->checkTableForColumn(_DB_PREFIX_.'customer', 'amazon_customer_id'))
			Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'customer` ADD `amazon_customer_id` varchar(50) NULL AFTER `date_upd`');

		Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'amz_transactions`;');
		Db::getInstance()->execute('
				CREATE TABLE `'._DB_PREFIX_.'amz_transactions` (
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

		$this->installOrderStates();

		return parent::install() && $this->registerHook('displayShoppingCartFooter') && $this->registerHook('displayNav') && $this->registerHook('adminOrder') && $this->registerHook('updateOrderStatus') && $this->registerHook('displayBackOfficeFooter') && $this->registerHook('displayPayment') && $this->registerHook('paymentReturn') && $this->registerHook('payment') && $this->registerhook('displayPaymentEU') && $this->registerHook('header');

	}

	protected function installOrderStates()
	{
		$values_to_insert = array('invoice' => 0, 'send_email' => 0, 'module_name' => $this->name, 'color' => 'RoyalBlue', 'unremovable' => 0, 'hidden' => 0, 'logable' => 1, 'delivery' => 0, 'shipped' => 0, 'paid' => 0, 'deleted' => 0);
		if (!Db::getInstance()->autoExecute(_DB_PREFIX_.'order_state', $values_to_insert, 'INSERT'))
			return false;
		$id_order_state = (int)Db::getInstance()->Insert_ID();
		$languages = Language::getLanguages(false);
		foreach ($languages as $language)
			Db::getInstance()->autoExecute(_DB_PREFIX_.'order_state_lang', array('id_order_state' => $id_order_state, 'id_lang' => $language['id_lang'], 'name' => 'Amazon Payments - autorisiert', 'template' => ''), 'INSERT');
		Configuration::updateValue('AUTHORIZED_STATUS_ID', $id_order_state);
		unset($id_order_state);

		$values_to_insert = array('invoice' => 0, 'send_email' => 0, 'module_name' => $this->name, 'color' => 'RoyalBlue', 'unremovable' => 0, 'hidden' => 0, 'logable' => 1, 'delivery' => 0, 'shipped' => 0, 'paid' => 1, 'deleted' => 0);
		if (!Db::getInstance()->autoExecute(_DB_PREFIX_.'order_state', $values_to_insert, 'INSERT'))
			return false;
		$id_order_state = (int)Db::getInstance()->Insert_ID();
		$languages = Language::getLanguages(false);
		foreach ($languages as $language)
			Db::getInstance()->autoExecute(_DB_PREFIX_.'order_state_lang', array('id_order_state' => $id_order_state, 'id_lang' => $language['id_lang'], 'name' => 'Amazon Payments - Zahlung eingegangen', 'template' => ''), 'INSERT');
		Configuration::updateValue('CAPTURE_STATUS_ID', $id_order_state);
		unset($id_order_state);
	}

	public function checkTableForColumn($table, $column)
	{
		if (!isset(self::$table_columns[$table][$column]))
		{
			$res = Db::getInstance()->executeS('SHOW COLUMNS FROM `'.$table.'` LIKE \''.$column.'\'');
			if ($res)
				self::$table_columns[$table][$column] = true;
			else
				self::$table_columns[$table][$column] = false;
		}
		return self::$table_columns[$table][$column];
	}

	public function uninstall()
	{
		if (!Configuration::deleteByName('MERCHANT_ID') || !Configuration::deleteByName('ACCESS_KEY') || !Configuration::deleteByName('SECRET_KEY') || !Configuration::deleteByName('REGION') || !Configuration::deleteByName('ENVIRONMENT') || !Configuration::deleteByName('AUTHORIZATION_MODE') || !Configuration::deleteByName('CAPTURE_MODE') || !Configuration::deleteByName('CAPTURE_STATUS_ID') || !parent::uninstall())
			return false;
		return true;
	}

	private function _postValidation()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			foreach (array_keys(self::$config_array) as $f)
			{
				if (Tools::getValue($f) === false)
					$this->_postErrors[] = $this->l($f.' details are required.');
			}
			if (Tools::getValue('region') == '')			
				$this->_postErrors[] = $this->l('Region is wrong.');
			else
			{			
				$service = $this->getService(array('merchantId' => Tools::getValue('merchant_id'), 'accessKey' => Tools::getValue('access_key'), 'environment' => Tools::getValue('environment'), 'authorization_mode' => Tools::getValue('authorization_mode'), 'capture_mode' => Tools::getValue('capture_mode'), 'capture_status_id' => Tools::getValue('capture_status_id'), 'region' => Tools::getValue('region'), 'secretKey' => Tools::getValue('secret_key')));
				$order_ref_request = new OffAmazonPaymentsService_Model_GetOrderReferenceDetailsRequest();
				$order_ref_request->setSellerId(Tools::getValue('merchant_id'));
				$order_ref_request->setAmazonOrderReferenceId('S00-0000000-0000000');
				try
				{
					$service->getOrderReferenceDetails($order_ref_request);
				} catch (OffAmazonPaymentsService_Exception $e)
				{
					switch ($e->getErrorCode()) 
					{
						case 'InvalidAccessKeyId' :
							$this->_postErrors[] = $this->l('MWS Access Key is wrong.');
							break;
	
						case 'SignatureDoesNotMatch' :
							$this->_postErrors[] = $this->l('MWS Secret Key is wrong.');
							break;
	
						case 'InvalidParameterValue' :
							if (strpos($e->getErrorMessage(), 'Invalid seller id') !== false)
								$this->_postErrors[] = $this->l('Merchant ID is wrong.');
							break;
					}
				}
			}
		}
	}

	private function _postProcess()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			foreach (self::$config_array as $f => $conf_key)
				Configuration::updateValue($conf_key, Tools::getValue($f));
		}
		$this->_html .= '<div class="conf confirm"> '.$this->l('Settings updated').'</div>';
	}

	private function _displayForm()
	{
		$this->_html .= '<form action="'.Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']).'" method="post">
		<fieldset>
		<legend><img src="../img/admin/contact.gif" />'.$this->l('Your Amazon Payments Details').'</legend>

		<div style="float: right; border: 1px dotted #ccc; padding: 5px; width: 230px; height: 230px;" id="amzVersionChecker">
		<p style="text-align: center" id="versionCheck">
		<img src="'._PS_BASE_URL_.__PS_BASE_URI__.'modules/'.$this->name.'/views/img/loading_indicator.gif'.'" />
		<br /><br />
		'.$this->l('Wir prüfen, ob eine neue Modul-Version bereit steht').'
		<br /><br />
		</p>
		<p style="text-align: center" id="versionCheckResult">
		'.$this->l('Ihre Version: ').' <strong>'.$this->version.'</strong>
		<br /><br />
		</p>
		</div>
		<script language="javascript">
		$(document).ready(function() {
		$.post("../modules/amzpayments/ajax.php",
		{
		action: "versionCheck",
		asv: "'.$this->version.'",
		psv: "'._PS_VERSION_.'",
		ref: location.host
	}, function(data) {
	console.log(data);
	if (data.newversion == 1) {
	$("#versionCheckResult").append("'.$this->l('Es gibt eine neue Version: ').' <strong>" + data.newversion_number + "</strong><br /><br /><a href=\"http://www.patworx.de/Amazon-Advanced-Payment-APIs/PrestaShop\" target=\"_blank\">&gt; Download</a>");
	} else {
	$("#versionCheckResult").append("'.$this->l('Alles bestens, Sie nutzen die aktuelle Version').'");
	}
	$("#versionCheck").hide();
	}, "json"
	);
	});
	</script>

	<table border="0" width="" cellpadding="0" cellspacing="0" id="form">
	<tr><td colspan="2">'.$this->l('Please configure your details before using the module.').'.<br /><br /></td></tr>
	';

		// Da Presta die Lang-Vars sonst nicht findet...
		$this->l('d_merchant_id');
		$this->l('d_access_key');
		$this->l('d_secret_key');
		$this->l('d_client_id');
		$this->l('d_region');
		$this->l('d_environment');
		$this->l('d_lpa_mode');
		$this->l('d_authorization_mode');
		$this->l('d_authorized_status_id');
		$this->l('d_capture_mode');
		$this->l('d_capture_status_id');
		$this->l('d_provocation');
		$this->l('d_popup');
		$this->l('d_shippings_not_allowed');
		$this->l('d_products_not_allowed');
		$this->l('d_allow_guests');
		$this->l('d_button_size');
		$this->l('d_button_size_lpa');
		$this->l('d_button_color');
		$this->l('d_button_color_lpa');
		$this->l('d_type_login');
		$this->l('d_type_pay');
		$this->l('d_ipn_status');
		$this->l('d_cron_status');
		$this->l('d_cron_password');
		$this->l('d_send_mails_on_decline');
		$this->l('Buy now');

		foreach (array_keys(self::$config_array) as $f)
		{
			$langvar = 'd_'.$f;
			$this->_html .= '<tr><td width="300" style="height: 35px;">'.$this->l($langvar).'</td>
			<td>'.$this->buildConfigInput($f).'</td></tr>';
			if ($f == 'ipn_status')
			{
				$this->_html .= '<tr><td width="300" style="height: 35px;">'.$this->l('d_url_ipn').'</td>
				<td>'.$this->getIPNURL().'</td></tr>';
			}
			if ($f == 'cron_password')
			{
				$this->_html .= '<tr><td width="300" style="height: 35px;">'.$this->l('d_url_cronjob').'</td>
				<td>'.$this->getCronURL().'</td></tr>';
			}
		}

		$this->_html .= '
		<tr><td colspan="2" align="center"><input class="button" name="btnSubmit" value="'.$this->l('Update settings').'" type="submit" /></td></tr>
		</table>
		</fieldset>
		</form>';
	}

	protected function getCronURL()
	{
		return _PS_BASE_URL_.__PS_BASE_URI__.'modules/'.$this->name.'/cron.php?pw='.$this->cron_password;
	}

	protected function getIPNURL()
	{
		return str_replace('http://', 'https://', _PS_BASE_URL_).__PS_BASE_URI__.'modules/'.$this->name.'/ipn.php';
	}

	protected function getAllowedReturnUrls($type = 1)
	{
		$url = str_replace('http://', 'https://', _PS_BASE_URL_).__PS_BASE_URI__.'modules/'.$this->name.'/process_login';
		if ($type == 2)
			$url .= '?toCheckout=1';
		return $url;
	}

	public function buildConfigInput($f)
	{
		switch ($f) 
		{
			case 'environment' :
				return '<select name="'.$f.'">
				<option value="SANDBOX"'.($this->$f == 'SANDBOX' ? ' selected="selected"' : false).'>'.$this->l('Testbetrieb').'</option>
				<option value="LIVE"'.($this->$f == 'LIVE' ? ' selected="selected"' : false).'>'.$this->l('Livebetrieb').'</option>
				</select>';
				
			case 'lpa_mode' :
				return '<select name="'.$f.'">
				<option value="pay"'.($this->$f == 'pay' ? ' selected="selected"' : false).'>'.$this->l('mode_pay').'</option>
				<option value="login"'.($this->$f == 'login' ? ' selected="selected"' : false).'>'.$this->l('mode_login').'</option>
				<option value="login_pay"'.($this->$f == 'login_pay' ? ' selected="selected"' : false).'>'.$this->l('mode_login_pay').'</option>
				</select>';
				
			case 'provocation' :
				return '<select name="'.$f.'">
				<option value="0"'.($this->$f == '0' ? ' selected="selected"' : false).'>'.$this->l('Nein').'</option>
				<option value="hard_decline"'.($this->$f == 'hard_decline' ? ' selected="selected"' : false).'>'.$this->l('Hard Decline').'</option>
				<option value="soft_decline"'.($this->$f == 'soft_decline' ? ' selected="selected"' : false).'>'.$this->l('Soft Decline (2min)').'</option>
				<option value="capture_decline"'.($this->$f == 'capture_decline' ? ' selected="selected"' : false).'>'.$this->l('Capture Decline').'</option>
				</select>';
				
			case 'popup' :
			case 'ipn_status' :
			case 'cron_status' :
			case 'send_mails_on_decline' :
			case 'allow_guests' :
				return '<select name="'.$f.'">
				<option value="1"'.($this->$f == '1' ? ' selected="selected"' : false).'>'.$this->l('Ja').'</option>
				<option value="0"'.($this->$f == '0' ? ' selected="selected"' : false).'>'.$this->l('Nein').'</option>
				</select>';
				
			case 'authorization_mode' :
				return '<select name="'.$f.'">
				<option value="fast_auth"'.($this->$f == 'fast_auth' ? ' selected="selected"' : false).'>'.$this->l('waehrend des Checkouts/vor Abschluss der Bestellung').'</option>
				<option value="after_checkout"'.($this->$f == 'after_checkout' ? ' selected="selected"' : false).'>'.$this->l('direkt nach der Bestellung').'</option>
				<option value="manually"'.($this->$f == 'manually' ? ' selected="selected"' : false).'>'.$this->l('manuell').'</option>
				</select>';
				
			case 'capture_mode' :
				return '<select name="'.$f.'">
				<option value="after_shipping"'.($this->$f == 'after_shipping' ? ' selected="selected"' : false).'>'.$this->l('nach Versand').'</option>
				<option value="after_auth"'.($this->$f == 'after_auth' ? ' selected="selected"' : false).'>'.$this->l('direkt nach der Autorisierung').'</option>
				<option value="manually"'.($this->$f == 'manually' ? ' selected="selected"' : false).'>'.$this->l('manuell').'</option>
				</select>';
				
			case 'button_size' :
				return '<select name="'.$f.'">
				<option value="medium"'.($this->$f == 'medium' ? ' selected="selected"' : false).'>'.$this->l('normal').'</option>
				<option value="large"'.($this->$f == 'large' ? ' selected="selected"' : false).'>'.$this->l('groß').'</option>
				<option value="x-large"'.($this->$f == 'x-large' ? ' selected="selected"' : false).'>'.$this->l('sehr groß').'</option>
				</select>';
				
			case 'button_size_lpa' :
				return '<select name="'.$f.'">
				<option value="small"'.($this->$f == 'small' ? ' selected="selected"' : false).'>'.$this->l('klein').'</option>
				<option value="medium"'.($this->$f == 'medium' ? ' selected="selected"' : false).'>'.$this->l('normal').'</option>
				<option value="large"'.($this->$f == 'large' ? ' selected="selected"' : false).'>'.$this->l('groß').'</option>
				<option value="x-large"'.($this->$f == 'x-large' ? ' selected="selected"' : false).'>'.$this->l('sehr groß').'</option>
				</select>';
				
			case 'button_color' :
				return '<select name="'.$f.'">
				<option value="orange"'.($this->$f == 'orange' ? ' selected="selected"' : false).'>'.$this->l('Amazon-Gelb').'</option>
				<option value="tan"'.($this->$f == 'tan' ? ' selected="selected"' : false).'>'.$this->l('Grau').'</option>
				</select>';
				
			case 'button_color_lpa' :
				return '<select name="'.$f.'">
				<option value="Gold"'.($this->$f == 'Gold' ? ' selected="selected"' : false).'>'.$this->l('Amazon-Gelb').'</option>
				<option value="LightGray"'.($this->$f == 'LightGray' ? ' selected="selected"' : false).'>'.$this->l('Hell-Grau').'</option>
				<option value="DarkGray"'.($this->$f == 'DarkGray' ? ' selected="selected"' : false).'>'.$this->l('Dunkel-Grau').'</option>
				</select>';
				
			case 'type_login' :
				return '<select name="'.$f.'">
				<option value="LwA"'.($this->$f == 'LwA' ? ' selected="selected"' : false).'>'.$this->l('Login über Amazon').'</option>
				<option value="Login"'.($this->$f == 'Login' ? ' selected="selected"' : false).'>'.$this->l('Login').'</option>
				<option value="A"'.($this->$f == 'A' ? ' selected="selected"' : false).'>'.$this->l('Nur ein "A"').'</option>
				</select>';
				
			case 'type_pay' :
				return '<select name="'.$f.'">
				<option value="PwA"'.($this->$f == 'PwA' ? ' selected="selected"' : false).'>'.$this->l('Bezahlen über Amazon').'</option>
				<option value="Pay"'.($this->$f == 'Pay' ? ' selected="selected"' : false).'>'.$this->l('Bezahlen').'</option>
				<option value="A"'.($this->$f == 'A' ? ' selected="selected"' : false).'>'.$this->l('Nur ein "A"').'</option>
				</select>';
				
			case 'secret_key' :
				return '<input type="password" name="'.$f.'" value="'.htmlentities(Tools::getValue($f, $this->$f), ENT_COMPAT, 'UTF-8').'" style="width: 300px;" />';
				
			default :
				return '<input type="text" name="'.$f.'" value="'.htmlentities(Tools::getValue($f, $this->$f), ENT_COMPAT, 'UTF-8').'" style="width: 300px;" />';
				
		}

	}

	public function getContent()
	{
		$this->_html = '<h2>'.$this->displayName.'</h2>';

		if (Tools::isSubmit('btnSubmit'))
		{
			$this->_postValidation();
			if (!count($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors as $err)
					$this->_html .= '<div class="alert error">'.$err.'</div>';
		}
		else
			$this->_html .= '<br />';

		$this->reloadConfigVars();

		$this->_displayForm();

		$this->_html .= '<fieldset>';
		$this->_html .= '<p>'.$this->l('Tragen Sie diese URLs in Ihrem Amazon SellerCentral in der "Login mit Amazon"-Konfiguration unter dem Punkt "Allowed Return URLs" ein:').'</p>';
		$this->_html .= '<ul><li>'.$this->getAllowedReturnUrls(1).'</li><li>'.$this->getAllowedReturnUrls(2).'</li></ul>';
		$this->_html .= '<p>'.$this->l('Tragen Sie diese URL in Ihrem Amazon SellerCentral in der "Login mit Amazon"-Konfiguration unter dem Punkt "Allowed JavaScript Origins" ein:').'</p>';
		$this->_html .= '<ul><li>'.str_replace('http://', 'https://', _PS_BASE_URL_).'</li></ul>';
		$this->_html .= '<p>'.$this->l('Sie können in Ihrem Template an beliebigen Stellen den "Login mit Amazon"-Button integrieren. Nutzen Sie hier für den folgenden HTML-Code und tragen Sie beim Attribut "id" immer (!) einen eindeutigen Wert ein:').'</p>';
		$this->_html .= '<code> &lt;div id=&quot;&quot; class=&quot;amazonLoginWr&quot;&gt;&lt;/div&gt; </code>';
		$this->_html .= '</fieldset>';

		return $this->_html;
	}

	public function hookDisplayNav()
	{
		if ($this->lpa_mode != 'pay' && !$this->context->customer->isLogged() && ((isset($this->context->controller->module->name) && $this->context->controller->module->name != 'amzpayments') || !(isset($this->context->controller->module->name))))
			return '<div id="amazonLogin" class="amazonLoginWr"></div>';
		return '';
	}

	public function hookDisplayBackOfficeFooter()
	{
		if ($this->capture_mode == 'after_shipping')
			return '<iframe style="width:1px; height:1px; visibility:hidden;" src="../modules/amzpayments/ajax.php?action=shippingCapture" />';
		else
			return '';
	}

	public function getRegionalCodeForURL()
	{
		if (Tools::strtolower($this->region) == 'de')
			return 'de';
		elseif (Tools::strtolower($this->region) == 'uk')
			return 'uk';
		elseif (Tools::strtolower($this->region) == 'us')
			return 'us';
		return 'de';
	}

	public function getButtonURL()
	{
		$this->registerHook('paymentReturn');
		if ($this->environment == 'SANDBOX')
		{
			if (Tools::strtolower($this->region) == 'de')
				return 'https://payments-sandbox.amazon.de/gp/widgets/button';
			elseif (Tools::strtolower($this->region) == 'uk')
				return 'https://payments-sandbox.amazon.co.uk/gp/widgets/button';
			elseif (Tools::strtolower($this->region) == 'us')
				return 'https://payments-sandbox.amazon.com/gp/widgets/button';
		}
		else
		{
			if (Tools::strtolower($this->region) == 'de')
				return 'https://payments.amazon.de/gp/widgets/button';
			elseif (Tools::strtolower($this->region) == 'uk')
				return 'https://payments.amazon.co.uk/gp/widgets/button';
			elseif (Tools::strtolower($this->region) == 'us')
				return 'https://payments.amazon.com/gp/widgets/button';
		}
	}

	protected function checkForTemporarySessionVarsAndKillThem()
	{		
		if (isset($this->context->cart->id_address_delivery))
		{
			$check_address = new Address((int)$this->context->cart->id_address_delivery);
			if ($check_address->lastname == 'amzLastname' || $check_address->firstname == 'amzFirstname' || $check_address->address1 == 'amzAddress1')
			{
				$this->context->cart->id_address_delivery = 0;
				$this->context->cart->update();
			}
		}
		if (isset($this->context->cart->id_address_invoice))
		{
			$check_address = new Address((int)$this->context->cart->id_address_invoice);
			if ($check_address->lastname == 'amzLastname' || $check_address->firstname == 'amzFirstname' || $check_address->address1 == 'amzAddress1')
			{
				$this->context->cart->id_address_invoice = 0;
				$this->context->cart->update();
			}
		}

	}

	public function hookDisplayShoppingCartFooter($params)
	{
		$this->checkForTemporarySessionVarsAndKillThem();

		$show_amazon_button = true;
		if (isset($this->context->controller->module))
		{
			if ($this->context->controller->module->name == 'amzpayments')
				$show_amazon_button = false;
		}
		if ($this->context->controller->php_self == 'cart' && Tools::isSubmit('ajax'))
			$show_amazon_button = false;

		if (($this->allow_guests == '0') && (!$this->context->customer->isLogged()))
			$show_amazon_button = false;

		if (!$this->checkCurrency($params['cart']))
			$show_amazon_button = false;

		if ($this->lpa_mode == 'login')
			$show_amazon_button = false;

		$summary = $this->context->cart->getSummaryDetails();
		foreach ($summary['products'] as &$product_update)
		{
			$product_id = (int)(isset($product_update['id_product']) ? $product_update['id_product'] : $product_update['product_id']);
			if ($this->productNotAllowed($product_id))
				$show_amazon_button = false;
		}
		if ($show_amazon_button)
		{
			$this->context->smarty->assign('sellerID', $this->merchant_id);
			$this->context->smarty->assign('size', $this->button_size);
			$this->context->smarty->assign('color', $this->button_color);
			$this->context->smarty->assign('btn_url', $this->getButtonURL());
			$this->context->smarty->assign('preBuildButton', $this->lpa_mode == 'pay');
			return $this->display(__FILE__, 'views/templates/hooks/amzpayments.tpl');
		}
	}

	public function hookPayment($params)
	{
		if (!$this->active)
			return;
		if (!$this->checkCurrency($params['cart']))
			return;

		$this->smarty->assign(array('this_path' => $this->_path, 'this_path_amzpayments' => $this->_path, 'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'));
		return $this->display(__FILE__, 'views/templates/hooks/payment.tpl');
	}

	public function hookDisplayPaymentEU($params)
	{
	}

	public function checkCurrency($cart)
	{
		$currency_order = new Currency((int)($cart->id_currency));
		$currencies_module = $this->getCurrency((int)$cart->id_currency);

		if (is_array($currencies_module))
			foreach ($currencies_module as $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
		return false;
	}

	protected function productNotAllowed($product_id)
	{
		if ($this->products_not_allowed != '')
		{
			$products_not_allowed_ids = explode(',', $this->products_not_allowed);
			foreach ($products_not_allowed_ids as $k => $v)
				$products_not_allowed_ids[$k] = (int)$v;
			if (in_array($product_id, $products_not_allowed_ids))
				return true;
		}
	}

	public function hookDisplayPayment($params)
	{
		return $this->hookPayment($params);
	}

	public function hookDisplayHeader($params)
	{
		$show_amazon_button = true;
		if (($this->allow_guests == '0') && (!$this->context->customer->isLogged()))
			$show_amazon_button = false;

		if (!$this->checkCurrency($params['cart']))
			$show_amazon_button = false;

		$this->context->controller->addCSS($this->_path.'views/css/amzpayments.css', 'all');
		$redirect = $this->context->link->getModuleLink('amzpayments', 'amzpayments');

		if (Configuration::get('PS_SSL_ENABLED'))
			$redirect = str_replace('http://', 'https://', $redirect);

		if (strpos($redirect, '?') > 0)
			$redirect .= '&session=';
		else
			$redirect .= '?session=';

		$login_redirect = $this->context->link->getModuleLink('amzpayments', 'process_login');

		// always SSL, as amazon has nothing else allowed!
		$login_redirect = str_replace('http://', 'https://', $login_redirect);

		if (strpos($login_redirect, '?') > 0)
			$login_checkout_redirect = $login_redirect.'&toCheckout=1';
		else
			$login_checkout_redirect = $login_redirect.'?toCheckout=1';

		$set_user_ajax = $this->context->link->getModuleLink('amzpayments', 'user_to_shop');

		// always SSL, as amazon has nothing else allowed!
		$set_user_ajax = str_replace('http://', 'https://', $set_user_ajax);

		$ext_js = '';

		if ($this->getRegionalCodeForURL() == 'us')
		{
			if ($this->environment == 'SANDBOX')
				$ext_js = 'https://static-na.payments-amazon.com/OffAmazonPayments/us/sandbox/js/Widgets.js ';
			else
				$ext_js = 'https://static-na.payments-amazon.com/OffAmazonPayments/us/js/Widgets.js ';
		}
		else
		{
			if ($this->environment == 'SANDBOX')
			{
				if ($this->lpa_mode == 'pay')
					$ext_js = 'https://static-eu.payments-amazon.com/OffAmazonPayments/'.$this->getRegionalCodeForURL().'/sandbox/js/Widgets.js?sellerId='.$this->merchant_id;
				else
					$ext_js = 'https://static-eu.payments-amazon.com/OffAmazonPayments/'.$this->getRegionalCodeForURL().'/sandbox/lpa/js/Widgets.js?sellerId='.$this->merchant_id;
			}
			else
			{
				if ($this->lpa_mode == 'pay')
					$ext_js = 'https://static-eu.payments-amazon.com/OffAmazonPayments/'.$this->getRegionalCodeForURL().'/js/Widgets.js?sellerId='.$this->merchant_id;
				else
					$ext_js = 'https://static-eu.payments-amazon.com/OffAmazonPayments/'.$this->getRegionalCodeForURL().'/lpa/js/Widgets.js?sellerId='.$this->merchant_id;
			}
		}

		$ext_js = '<script type="text/javascript" src="'.$ext_js.'"></script>';

		$amz_login_ready = '';
		if ($this->lpa_mode != 'pay')
			$amz_login_ready = '<script type="text/javascript"> window.onAmazonLoginReady = function() { amazon.Login.setClientId("'.$this->client_id.'"); }; </script>';

		$acc_tk = '';
		$is_logged = 'false';
		if (isset($this->context->cookie->amz_access_token) && $this->context->cookie->amz_access_token != '')
		{
			$is_logged = 'true';
			if (!isset($this->context->cookie->amazon_id))
			{
				$acc_tk = self::prepareCookieValueForAmazonPaymentsUse($this->context->cookie->amz_access_token);
				$amz_login_ready .= '<script type="text/javascript">
				var accessToken = "'.$acc_tk.'";
				if (typeof accessToken === \'string\' && accessToken.match(/^Atza/)) {
				document.cookie = "amazon_Login_accessToken=" + accessToken +";secure";
			}
			window.onAmazonLoginReady = function() {
			amazon.Login.setClientId("'.$this->client_id.'");
			amazon.Login.setUseCookie(true);
			};
			</script>';
			}
		}

		$logout_str = '';
		if ($this->context->controller->php_self == 'guest-tracking')
		{
			if ($this->lpa_mode != 'pay')
				$logout_str .= '<script type="text/javascript"> amazonLogout(); </script>';
		}

		if ((float)_PS_VERSION_ > 1.5)
		{
			$js_file = ($this->lpa_mode == 'pay' ? 'views/js/amzpayments.js' : 'views/js/amzpayments_login.js');
			$js_file = Tools::file_get_contents(_PS_MODULE_DIR_.$this->name.'/'.$js_file);
			$js_file = str_replace(array("\t", "\r\n", "\n"), array(' ', ' ', ' '), $js_file);
			return $amz_login_ready.$ext_js.'<script type="text/javascript"> var AMZACTIVE = \''.($show_amazon_button ? '1' : '0').'\'; var AMZSELLERID = "'.$this->merchant_id.'"; var AMZ_BUTTON_TYPE_LOGIN = "'.$this->type_login.'"; var AMZ_BUTTON_TYPE_PAY = "'.$this->type_pay.'"; var AMZ_BUTTON_SIZE_LPA = "'.$this->button_size_lpa.'"; var AMZ_BUTTON_COLOR_LPA = "'.$this->button_color_lpa.'"; var CLIENT_ID = "'.$this->client_id.'"; var useRedirect = '.(!self::currentSiteIsSSL() || $this->popup == '0' ? 'true' : 'false').'; var LPA_MODE = "'.$this->lpa_mode.'"; var REDIRECTAMZ = "'.$redirect.'"; var LOGINREDIRECTAMZ_CHECKOUT = "'.$login_checkout_redirect.'"; var LOGINREDIRECTAMZ = "'.$login_redirect.'"; var is_logged = '.$is_logged.'; var AMZACCTK = "'.$acc_tk.'"; var SETUSERAJAX = "'.$set_user_ajax.'";'.$js_file.' </script>'.$logout_str;
		}
		else
		{
			return $amz_login_ready.$ext_js.'<script type="text/javascript"> var AMZACTIVE = \''.($show_amazon_button ? '1' : '0').'\'; var AMZSELLERID = "'.$this->merchant_id.'"; var AMZ_BUTTON_TYPE_LOGIN = "'.$this->type_login.'"; var AMZ_BUTTON_TYPE_PAY = "'.$this->type_pay.'"; var AMZ_BUTTON_SIZE_LPA = "'.$this->button_size_lpa.'"; var AMZ_BUTTON_COLOR_LPA = "'.$this->button_color_lpa.'"; var CLIENT_ID = "'.$this->client_id.'"; var useRedirect = '.(!self::currentSiteIsSSL() || $this->popup == '0' ? 'true' : 'false').'; var LPA_MODE = "'.$this->lpa_mode.'"; var REDIRECTAMZ = "'.$redirect.'"; var LOGINREDIRECTAMZ_CHECKOUT = "'.$login_checkout_redirect.'"; var LOGINREDIRECTAMZ = "'.$login_redirect.'"; var is_logged = '.$is_logged.'; var AMZACCTK = "'.$acc_tk.'"; var SETUSERAJAX = "'.$set_user_ajax.'"; </script>
			<script type="text/javascript" src="'.($this->lpa_mode == 'pay' ? $this->_path.'views/js/amzpayments.js' : $this->_path.'views/js/amzpayments_login.js').'"></script>'.$logout_str;
		}
	}

	public function hookDisplayAdminOrder($params)
	{
		$order = new Order($params['id_order']);
		if ($order->module == $this->name)
		{
			$q = 'SELECT amazon_order_reference_id FROM '._DB_PREFIX_.'orders WHERE id_order = '.(int)$params['id_order'];
			$r = Db::getInstance()->getRow($q);
			$amz_reference_id = $r['amazon_order_reference_id'];

			$q = 'SELECT * FROM '._DB_PREFIX_.'amz_transactions WHERE amz_tx_order_reference = \''.pSQL($amz_reference_id).'\' AND amz_tx_status != \'Closed\' AND amz_tx_status != \'Declined\'';
			$rs = Db::getInstance()->ExecuteS($q);
			foreach ($rs as $r)
				$this->intelligentRefresh($r);

			return $this->getAdminSkeleton($params['id_order'], true);
		}
	}

	public function hookUpdateOrderStatus($params)
	{
		// not needed anymore
	}

	public function hookPaymentReturn($params)
	{
		return $this->display(__FILE__, 'views/templates/hooks/confirmation.tpl');
	}

	public function setAmazonReferenceIdForOrderId($amazon_reference_id, $order_id)
	{
		return Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'orders` SET `amazon_order_reference_id` = \''.pSQL($amazon_reference_id).'\' WHERE `id_order` = '.(int)($order_id));
	}

	public function setAmazonAuthorizationReferenceIdForOrderId($authorization_reference_id, $order_id)
	{
		return Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'orders` SET `amazon_auth_reference_id` = \''.pSQL($authorization_reference_id).'\' WHERE `id_order` = '.(int)($order_id));
	}

	public function setAmazonAuthorizationIdForOrderId($authorization_id, $order_id)
	{
		return Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'orders` SET `amazon_authorization_id` = \''.pSQL($authorization_id).'\' WHERE `id_order` = '.(int)($order_id));
	}

	public function setAmazonCaptureIdForOrderId($amazon_capture_id, $order_id)
	{
		return Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'orders` SET `amazon_capture_id` = \''.pSQL($amazon_capture_id).'\' WHERE `id_order` = '.(int)($order_id));
	}

	public function setAmazonCaptureReferenceIdForOrderId($amazon_capture_reference_id, $order_id)
	{
		return Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'orders` SET `amazon_capture_reference_id` = \''.pSQL($amazon_capture_reference_id).'\' WHERE `id_order` = '.(int)($order_id));
	}

	public function setAmazonReferenceIdForOrderTransactionId($amazon_reference_id, $order_id)
	{
		$q = 'SELECT `reference` FROM '._DB_PREFIX_.'orders WHERE `id_order` = '.(int)$order_id;
		if ($r = Db::getInstance()->getRow($q))
			return Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'order_payment` SET `transaction_id` = \''.pSQL($amazon_reference_id).'\' WHERE `order_reference` = \''.pSQL($r['reference']).'\'');
		
		return false;
	}

	public function getAmazonReferenceIdForOrderTransactionId($order_id)
	{
		$q = 'SELECT `reference` FROM '._DB_PREFIX_.'orders WHERE `id_order` = '.(int)$order_id;
		if ($r = Db::getInstance()->getRow($q))
			return $r['reference'];
			
		return false;
	}

	public function createUniqueOrderId($cart_id)
	{
		return 'AP'.$cart_id.'-'.Tools::substr(Tools::getToken(false), 0, 8);
	}

	public function getAdminSkeleton($orders_id, $direct_include = false)
	{
		$q = 'SELECT amazon_order_reference_id FROM '._DB_PREFIX_.'orders WHERE id_order = '.(int)$orders_id;
		$r = Db::getInstance()->getRow($q);
		if ($r['amazon_order_reference_id'])
		{

			if ((float)_PS_VERSION_ > 1.5)
			{
				$ret = '
				<script src="../modules/amzpayments/views/js/admin.js"></script>
				<input type="hidden" class="amzAjaxHandler" value="../modules/amzpayments/ajax.php" />
				<br />
				<div class="panel">
				<div class="row">
				<h3>
				<i class="icon-money"></i>
				'.$this->displayName.'
				</h3>
				<div class="amzAdminWr amzContainer16" data-orderRef="'.$r['amazon_order_reference_id'].'">
				<div class="panel amzAdminOrderHistoryWr">
				<div class="amzAdminOrderHistory">
				'.($direct_include ? $this->getOrderHistory($r['amazon_order_reference_id']) : '').'
				</div>
				</div>
				<div class="panel amzAdminOrderSummary">
				'.($direct_include ? $this->getOrderSummary($r['amazon_order_reference_id']) : '').'
				</div>
				<div class="panel amzAdminOrderActions">
				'.($direct_include ? $this->getOrderActions($r['amazon_order_reference_id']) : '').'
				</div>
				</div>
				</div>
				</div>';
			}
			else
			{
				$ret = '
				<script src="../modules/amzpayments/views/js/admin.js"></script>
				<input type="hidden" class="amzAjaxHandler" value="../modules/amzpayments/ajax.php" />
				<br />
				<fieldset>
				<legend><img src="../img/admin/money.gif" />'.$this->displayName.'</legend>
				<div class="amzAdminWr amzContainer15" data-orderRef="'.$r['amazon_order_reference_id'].'">
				<div class="amzAdminOrderHistoryWr">

				<div class="amzAdminOrderHistory">
				'.($direct_include ? $this->getOrderHistory($r['amazon_order_reference_id']) : '').'
				</div>
				</div>
				<div class="amzAdminOrderSummary">
				'.($direct_include ? $this->getOrderSummary($r['amazon_order_reference_id']) : '').'
				</div>
				<div class="amzAdminOrderActions">
				'.($direct_include ? $this->getOrderActions($r['amazon_order_reference_id']) : '').'
				</div>
				</div>
				</fieldset>';
			}
			return $ret;
		}
	}

	public function getOrderHistory($order_ref)
	{
		$q = 'SELECT * FROM '._DB_PREFIX_.'amz_transactions WHERE amz_tx_order_reference = \''.pSQL($order_ref).'\' ORDER BY amz_tx_time';
		$rs = Db::getInstance()->ExecuteS($q);
		$ret = '';
		foreach ($rs as $r)
		{
			if ($r['amz_tx_type'] == 'order_ref')
				$reference_status = $r['amz_tx_status'];

			$ret .= '<tr>
			<td>
			'.$this->translateTransactionType($r['amz_tx_type']).'
			</td>
			<td>
			'.self::formatAmount($r['amz_tx_amount']).'
			</td>
			<td>
			'.date('Y-m-d H:i:s', $r['amz_tx_time']).'
			</td>
			<td>
			'.$r['amz_tx_status'].'
			</td>
			<td>
			'.date('Y-m-d H:i:s', $r['amz_tx_last_change']).'
			</td>
			<td>
			'.$r['amz_tx_amz_id'].'
			</td>
			<td>
			'.($r['amz_tx_expiration'] != 0 ? date('Y-m-d H:i:s', $r['amz_tx_expiration']) : '-').'
			</td>
			</tr>';

		}

		if ($ret != '')
		{
			return '<h3>'.$this->l('AMZ_HISTORY').'</h3><table class="table">
			<thead>
			<tr>
			<th>
			'.$this->l('AMZ_TX_TYPE_HEADING').'
			</th>
			<th>
			'.$this->l('AMZ_TX_AMOUNT_HEADING').'
			</th><th>
			'.$this->l('AMZ_TX_TIME_HEADING').'
			</th>
			<th>
			'.$this->l('AMZ_TX_STATUS_HEADING').'
			</th>
			<th>
			'.$this->l('AMZ_TX_LAST_CHANGE_HEADING').'
			</th>
			<th>
			'.$this->l('AMZ_TX_ID_HEADING').'
			</th>
			<th>
			'.$this->l('AMZ_TX_EXPIRATION_HEADING').'
			</th>
			</tr>
			</thead>
			<tbody>
			'.$ret.'</tbody></table>
			<div>
			<a href="#" class="amzAjaxLink btn btn-default button" data-action="refreshOrder" data-orderRef="'.$order_ref.'">'.$this->l('AMZ_REFRESH').'</a>
			'.($reference_status == 'Open' || $reference_status == 'Suspended' ? '
					<a href="#" class="amzAjaxLink btn btn-default button" data-action="cancelOrder" data-orderRef="'.$order_ref.'">'.$this->l('AMZ_CANCEL_ORDER').'</a>
					<a href="#" class="amzAjaxLink btn btn-default button" data-action="closeOrder" data-orderRef="'.$order_ref.'">'.$this->l('AMZ_CLOSE_ORDER').'</a>
					' : '').'
					</div>';
		}

	}

	public function getOrderAuthorizedAmount($order_ref)
	{
		$q = 'SELECT SUM(amz_tx_amount) AS auth_sum FROM '._DB_PREFIX_.'amz_transactions WHERE
		amz_tx_order_reference = \''.pSQL($order_ref).'\'
		AND
		amz_tx_type=\'auth\'
		AND
		amz_tx_status = \'Open\'';
		
		$r = Db::getInstance()->getRow($q);
		return (float)$r['auth_sum'];
	}

	public function getOrderCapturedAmount($order_ref)
	{
		$q = 'SELECT SUM(amz_tx_amount) AS capture_sum FROM '._DB_PREFIX_.'amz_transactions WHERE
		amz_tx_order_reference = \''.pSQL($order_ref).'\'
		AND
		amz_tx_type=\'capture\'
		AND
		amz_tx_status = \'Completed\'';
		$r = Db::getInstance()->getRow($q);
		return (float)$r['capture_sum'];
	}

	public function getOrderRefundedAmount($order_ref)
	{
		$q = 'SELECT SUM(amz_tx_amount) AS refund_sum FROM '._DB_PREFIX_.'amz_transactions WHERE
		amz_tx_order_reference = \''.pSQL($order_ref).'\'
		AND
		amz_tx_type=\'refund\'
		AND
		amz_tx_status = \'Completed\'';		
		$r = Db::getInstance()->getRow($q);
		return (float)$r['refund_sum'];
	}

	public static function getOrderOpenAuthorizations($order_ref)
	{
		$q = 'SELECT * FROM '._DB_PREFIX_.'amz_transactions WHERE
		amz_tx_order_reference = \''.pSQL($order_ref).'\'
		AND
		amz_tx_type=\'auth\'
		AND
		amz_tx_status = \'Open\'';
		$rs = Db::getInstance()->ExecuteS($q);
		$ret = array();
		foreach ($rs as $r)
			$ret[] = $r;
		return $ret;
	}

	public static function getOrderCaptures($order_ref)
	{
		$q = 'SELECT * FROM '._DB_PREFIX_.'amz_transactions WHERE
		amz_tx_order_reference = \''.pSQL($order_ref).'\'
		AND
		amz_tx_type=\'capture\'';
		$rs = Db::getInstance()->ExecuteS($q);
		$ret = array();
		foreach ($rs as $r)
			$ret[] = $r;

		return $ret;
	}

	public static function getOrderUnclosedCaptures($order_ref)
	{
		$q = 'SELECT * FROM '._DB_PREFIX_.'amz_transactions WHERE
		amz_tx_order_reference = \''.pSQL($order_ref).'\'
		AND
		amz_tx_status != \'Closed\'
		AND
		amz_tx_type=\'capture\'';
		$rs = Db::getInstance()->ExecuteS($q);
		$ret = array();
		foreach ($rs as $r)
			$ret[] = $r;

		return $ret;
	}

	public static function getOrderState($order_ref)
	{
		$q = 'SELECT * FROM '._DB_PREFIX_.'amz_transactions WHERE
		amz_tx_order_reference = \''.pSQL($order_ref).'\'
		AND
		amz_tx_type=\'order_ref\'';
		$r = Db::getInstance()->getRow($q);
		return $r['amz_tx_status'];
	}

	public function intelligentRefresh($r)
	{
		switch ($r['amz_tx_type']) 
		{
			case 'refund' :
				$this->refreshRefund($r['amz_tx_amz_id']);
				
			case 'capture' :
				$this->refreshCapture($r['amz_tx_amz_id']);
				
			case 'auth' :
				$this->refreshAuthorization($r['amz_tx_amz_id']);
				
			case 'order_ref' :
				$this->refreshOrderReference($r['amz_tx_amz_id']);
				
		}
	}

	public function refreshRefund($refund_id)
	{
		$service = $this->getService();
		$refund_request = new OffAmazonPaymentsService_Model_GetRefundDetailsRequest();
		$refund_request->setSellerId($this->merchant_id);
		$refund_request->setAmazonRefundId($refund_id);
		try
		{
			$response = $service->getRefundDetails($refund_request);
			$details = $response->getGetRefundDetailsResult()->getRefundDetails();
			$sql_arr = array('amz_tx_status' => (string)$details->getRefundStatus()->getState(), 'amz_tx_last_change' => strtotime((string)$details->getRefundStatus()->getLastUpdateTimestamp()), 'amz_tx_last_update' => time());
			Db::getInstance()->update('amz_transactions', $sql_arr, " amz_tx_amz_id = '".pSQL($refund_id)."'");

		} catch (OffAmazonPaymentsService_Exception $e)
		{
			echo 'ERROR: '.$e->getMessage();
		}
	}

	public function refreshCapture($capture_id)
	{
		$service = $this->getService();
		$capture_request = new OffAmazonPaymentsService_Model_GetCaptureDetailsRequest();
		$capture_request->setSellerId($this->merchant_id);
		$capture_request->setAmazonCaptureId($capture_id);
		try
		{
			$response = $service->getCaptureDetails($capture_request);
			$details = $response->getGetCaptureDetailsResult()->getCaptureDetails();

			$sql_arr = array('amz_tx_status' => (string)$details->getCaptureStatus()->getState(), 'amz_tx_last_change' => strtotime((string)$details->getCaptureStatus()->getLastUpdateTimestamp()), 'amz_tx_amount_refunded' => (float)$details->getRefundedAmount()->getAmount(), 'amz_tx_last_update' => time());
			Db::getInstance()->update('amz_transactions', $sql_arr, " amz_tx_amz_id = '".pSQL($capture_id)."'");

		} catch (OffAmazonPaymentsService_Exception $e)
		{
			echo 'ERROR: '.$e->getMessage();
		}
	}

	public function refreshAuthorization($auth_id)
	{
		$service = $this->getService();
		$authorization_request = new OffAmazonPaymentsService_Model_GetAuthorizationDetailsRequest();
		$authorization_request->setSellerId($this->merchant_id);
		$authorization_request->setAmazonAuthorizationId($auth_id);
		try
		{
			$response = $service->getAuthorizationDetails($authorization_request);
			$details = $response->getGetAuthorizationDetailsResult()->getAuthorizationDetails();

			//$address = $details->getAuthorizationBillingAddress();

			$sql_arr = array('amz_tx_status' => (string)$details->getAuthorizationStatus()->getState(), 'amz_tx_last_change' => strtotime((string)$details->getAuthorizationStatus()->getLastUpdateTimestamp()), 'amz_tx_last_update' => time());
			Db::getInstance()->update('amz_transactions', $sql_arr, " amz_tx_amz_id = '".pSQL($auth_id)."'");

			if ((string)$details->getAuthorizationStatus()->getState() == 'Declined')
			{
				$reason = (string)$details->getAuthorizationStatus()->getReasonCode();

				if ($reason == 'AmazonRejected')
				{
					$order_ref = AmazonTransactions::getOrderRefFromAmzId($auth_id);
					$this->cancelOrder($order_ref);
				}
				$this->intelligentDeclinedMail($auth_id, $reason);
			}

		} catch (OffAmazonPaymentsService_Exception $e)
		{
			echo 'ERROR: '.$e->getMessage();
		}
	}

	public function refreshOrderReference($order_ref)
	{
		$service = $this->getService();
		$order_ref_request = new OffAmazonPaymentsService_Model_GetOrderReferenceDetailsRequest();
		$order_ref_request->setSellerId($this->merchant_id);
		$order_ref_request->setAmazonOrderReferenceId($order_ref);
		try
		{
			$response = $service->getOrderReferenceDetails($order_ref_request);
			$details = $response->getGetOrderReferenceDetailsResult()->getOrderReferenceDetails();
			$sql_arr = array('amz_tx_status' => (string)$details->getOrderReferenceStatus()->getState(), 'amz_tx_last_change' => strtotime((string)$details->getOrderReferenceStatus()->getLastUpdateTimestamp()), 'amz_tx_last_update' => time());
			Db::getInstance()->update('amz_transactions', $sql_arr, " amz_tx_amz_id = '".pSQL($order_ref)."'");

		} catch (OffAmazonPaymentsService_Exception $e)
		{
			echo 'ERROR: '.$e->getMessage();
		}
	}

	public function closeOrder($order_ref)
	{
		$service = $this->getService();
		$order_ref_request = new OffAmazonPaymentsService_Model_CloseOrderReferenceRequest();
		$order_ref_request->setSellerId($this->merchant_id);
		$order_ref_request->setAmazonOrderReferenceId($order_ref);
		try
		{
			$response = $service->closeOrderReference($order_ref_request);
		} catch (OffAmazonPaymentsService_Exception $e)
		{
			echo 'ERROR: '.$e->getMessage();
		}
		return $response;
	}

	public function cancelOrder($order_ref)
	{
		$service = $this->getService();
		$order_ref_request = new OffAmazonPaymentsService_Model_CancelOrderReferenceRequest();
		$order_ref_request->setSellerId($this->merchant_id);
		$order_ref_request->setAmazonOrderReferenceId($order_ref);
		try
		{
			$response = $service->cancelOrderReference($order_ref_request);
		} catch (OffAmazonPaymentsService_Exception $e)
		{
			echo 'ERROR: '.$e->getMessage();
		}
		return $response;
	}

	public static function getClassForStatus($status)
	{
		switch ($status) 
		{
			case 'Open' :
			case 'Completed' :
			case 'Closed' :
				return 'amzGreen';
				
			case 'Pending' :
				return 'amzOrange';
				
			default :
				return 'amzRed';
				
		}

	}

	public function getOrderActions($order_ref)
	{
		$order_state = $this->getOrderState($order_ref);
		$ret = '';
		if ($order_state == 'Open' || $order_state == 'Closed')
		{
			$open_auth = self::getOrderOpenAuthorizations($order_ref);
			if (count($open_auth) > 0)
			{
				$ret .= '<h4>'.$this->l('AMZ_CAPTURE_FROM_AUTH_HEADING').'</h4>';
				$ret .= '<table class="table">
				<thead>
				<tr class="headline">
				<th class="amzAmountCell">
				'.$this->l('AMZ_TX_AMOUNT_HEADING').'
				</th><th>

				'.$this->l('AMZ_TX_TIME_HEADING').'
				</th>
				<th>
				'.$this->l('AMZ_TX_ID_HEADING').'
				</th>
				<th>
				'.$this->l('AMZ_TX_EXPIRATION_HEADING').'
				</th>
				<th>
				'.$this->l('AMZ_TX_ACTION_HEADING').'
				</th>
				</tr>
				</thead>
				<tbody>';

				foreach ($open_auth as $r)
				{
					$ret .= '<tr>
					<td class="amzAmountCell">

					'.self::formatAmount($r['amz_tx_amount']).'
					</td>
					<td>
					'.date('Y-m-d H:i:s', $r['amz_tx_time']).'
					</td>
					<td>

					'.$r['amz_tx_amz_id'].'
					</td>
					<td>
					'.($r['amz_tx_expiration'] != 0 ? date('Y-m-d H:i:s', $r['amz_tx_expiration']) : '-').'
					</td>
					<td>
					<div>
					<a href="#" class="amzAjaxLink btn btn-default button amzButton" data-action="captureTotalFromAuth" data-authid="'.$r['amz_tx_amz_id'].'">'.$this->l('AMZ_CAPTURE_TOTAL_FROM_AUTH').'</a>
					</div>
					<div>
					<input type="text" class="amzAmountField" value="'.self::formatAmount($r['amz_tx_amount']).'" />
					<a href="#" class="amzAjaxLink btn btn-default button amzButton" data-action="captureAmountFromAuth" data-authid="'.$r['amz_tx_amz_id'].'">'.$this->l('AMZ_CAPTURE_AMOUNT_FROM_AUTH').'</a>

					</div>

					</td>
					</tr>';

				}
				$ret .= '</tbody></table>';
			}
		}
		if ($order_state == 'Open')
		{
			$amount_left_to_authorize = $this->getAmountLeftToAuthorize($order_ref);
			$amount_left_to_over_authorize = $this->getAmountLeftToOverAuthorize($order_ref);
			if ($amount_left_to_authorize > 0 || $amount_left_to_over_authorize > 0)
			{
				$ret .= '<h4>'.$this->l('AMZ_AUTHORIZE').'</h4>';
				$ret .= '<table style="width:100%" class="table">
				<thead>
				<tr class="headline">
				<th class="amzAmountCell">
				'.$this->l('AMZ_TX_AMOUNT_NOT_AUTHORIZED_YET_HEADING').'
				</th>
				<th class="amzAmountCell">
				'.$this->l('AMZ_TX_AMOUNT_POSSIBLE_HEADING').'
				</th>
				<th>
				'.$this->l('AMZ_TX_ACTION_HEADING').'
				</th>
				</tr>
				</thead>
				<tbody>';

				if ($amount_left_to_authorize + $amount_left_to_over_authorize > 0)
				{
					$ret .= '<tr>
					<td class="amzAmountCell">
					'.self::formatAmount($amount_left_to_authorize).'
					</td>
					<td class="amzAmountCell">
					'.self::formatAmount($amount_left_to_authorize + $amount_left_to_over_authorize).'
					</td>
					<td>
					'.($amount_left_to_authorize > 0 ? '
							<a href="#" class="amzAjaxLink btn btn-default button amzButton" data-action="authorizeAmount" data-amount="'.$amount_left_to_authorize.'" data-orderRef="'.$order_ref.'">'.$this->l('AMZ_AUTHORIZE').'</a>
							' : '').'
							<div>
							<nobr>
							<input type="text" class="amzAmountField" value="'.self::formatAmount(($amount_left_to_authorize > 0 ? $amount_left_to_authorize : $amount_left_to_over_authorize)).'" />
							<a href="#" class="amzAjaxLink btn btn-default button amzButton" data-action="authorizeAmountFromField" data-orderRef="'.$order_ref.'">'.($amount_left_to_authorize > 0 ? $this->l('AMZ_AUTHORIZE_AMOUNT') : $this->l('AMZ_OVER_AUTHORIZE_AMOUNT')).'</a>
							</nobr>
							</div>
							</td>
							</tr>';
				}

				$ret .= '</tbody></table>';

			}

		}

		$captures = self::getOrderUnclosedCaptures($order_ref);
		if (count($captures) > 0)
		{
			$ret .= '<h4>'.$this->l('AMZ_REFUNDS').'</h4><table class="table">
			<thead>
			<tr class="headline">

			<th class="amzAmountCell">
			'.$this->l('AMZ_TX_AMOUNT_HEADING').'
			</th>
			<th class="amzAmountCell">
			'.$this->l('AMZ_TX_AMOUNT_REFUNDED_HEADING').'
			</th>
			<th>
			'.$this->l('AMZ_TX_AMOUNT_REFUNDABLE_HEADING').'
			</th class="amzAmountCell">
			<th>
			'.$this->l('AMZ_TX_TIME_HEADING').'
			</th>
			<th>
			'.$this->l('AMZ_TX_STATUS_HEADING').'
			</th>
			<th>
			'.$this->l('AMZ_TX_LAST_CHANGE_HEADING').'
			</th>
			<th>
			'.$this->l('AMZ_TX_ID_HEADING').'
			</th>
			<th>
			'.$this->l('AMZ_TX_ACTION_HEADING').'
			</th>
			</tr>
			</thead>
			<tbody>
			';
			foreach ($captures as $r)
			{
				$ret .= '<tr>

				<td class="amzAmountCell">
				'.self::formatAmount($r['amz_tx_amount']).'
				</td>
				<td class="amzAmountCell">
				'.self::formatAmount($r['amz_tx_amount_refunded']).'
				</td>
				<td class="amzAmountCell">
				'.self::formatAmount(($refundable = (min((75 + $r['amz_tx_amount']), (round($r['amz_tx_amount'] * 1.15, 2))) - $r['amz_tx_amount_refunded']))).'
				</td>
				<td>
				'.date('Y-m-d H:i:s', $r['amz_tx_time']).'
				</td>
				<td>
				<span class="'.self::getClassForStatus($r['amz_tx_status']).'">'.$r['amz_tx_status'].'</span>
				</td>
				<td>
				'.date('Y-m-d H:i:s', $r['amz_tx_last_change']).'
				</td>
				<td>
				'.$r['amz_tx_amz_id'].'
				</td>
					
				<td>
				'.($r['amz_tx_amount'] - $r['amz_tx_amount_refunded'] > 0 ? '
						<div>
						<a href="#" class="amzAjaxLink btn btn-default button amzButton" data-action="refundAmount" data-amount="'.($r['amz_tx_amount'] - $r['amz_tx_amount_refunded']).'" data-captureid="'.$r['amz_tx_amz_id'].'">'.$this->l('AMZ_REFUND_TOTAL').'</a>
						</div>
						' : '').'
						<div>
						<nobr>
						<input type="text" class="amzAmountField" value="'.self::formatAmount(($r['amz_tx_amount'] - $r['amz_tx_amount_refunded'] > 0 ? ($r['amz_tx_amount'] - $r['amz_tx_amount_refunded']) : $refundable)).'" />
						<a href="#" class="amzAjaxLink btn btn-default button amzButton" data-action="refundAmountFromField" data-captureid="'.$r['amz_tx_amz_id'].'">'.($r['amz_tx_amount'] - $r['amz_tx_amount_refunded'] > 0 ? $this->l('AMZ_REFUND_AMOUNT') : $this->l('AMZ_REFUND_OVER_AMOUNT')).'</a>
						</nobr>
						</div>
						</td>
						</tr>';

			}

		}
		$ret .= '</tbody></table>';

		if ($ret != '')
			$ret = $ret = '<h3>'.$this->l('AMZ_ACTIONS').'</h3>'.$ret;

		return $ret;
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

		if ($left > 75)
			$left = 75;

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
		$ret = '<h3>'.$this->l('AMZ_SUMMARY').'</h3><table>
		<tr>
		<td><b>'.$this->l('AMZ_ORDER_AUTH_TOTAL').'</b></td>
		<td>'.self::formatAmount(self::getOrderAuthorizedAmount($order_ref)).'</td>
		</tr>
		<tr>
		<td><b>'.$this->l('AMZ_ORDER_CAPTURE_TOTAL').'</b></td>
		<td>'.self::formatAmount(self::getOrderCapturedAmount($order_ref)).'</td>
		</tr>
		<tr>
		<td><b>'.$this->l('AMZ_ORDER_REFUND_TOTAL').'</b></td>
		<td>'.self::formatAmount(self::getOrderRefundedAmount($order_ref)).'</td>
		</tr>
		</table>';
		return $ret;
	}

	public static function formatAmount($amount)
	{
		return number_format($amount, 2, ',', '');
	}

	public function translateTransactionType($str)
	{
		switch ($str) 
		{
			case 'auth' :
				$str = $this->l('AMZ_AUTH_TEXT');
				break;
			case 'order_ref' :
				$str = $this->l('AMZ_ORDER_TEXT');
				break;
			case 'capture' :
				$str = $this->l('AMZ_CAPTURE_TEXT');
				break;
			case 'refund' :
				$str = $this->l('AMZ_REFUND_TEXT');
				break;
		}

		return $str;

	}

	public function shippingCapture()
	{
		if ($this->capture_mode == 'after_shipping')
		{
			$q = 'SELECT DISTINCT o.amazon_order_reference_id FROM  '._DB_PREFIX_.'orders o
			JOIN '._DB_PREFIX_.'amz_transactions AS a1 ON (o.amazon_order_reference_id = a1.amz_tx_order_reference AND a1.amz_tx_type = \'auth\' AND a1.amz_tx_status = \'Open\')
			LEFT JOIN '._DB_PREFIX_.'amz_transactions AS a2 ON (o.amazon_order_reference_id = a2.amz_tx_order_reference AND a2.amz_tx_type = \'capture\')
			WHERE
			o.amazon_order_reference_id != \'\'
			AND
			o.current_state = \''.$this->capture_status_id.'\'
			AND
			a2.amz_tx_id IS NULL';
			$rs = Db::getInstance()->ExecuteS($q);
			foreach ($rs as $r)
			{
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
		$q = 'SELECT * FROM '._DB_PREFIX_.'orders WHERE amazon_order_reference_id = \''.pSQL($order_ref).'\'';
		$rs = Db::getInstance()->ExecuteS($q);
		foreach ($rs as $r)
		{

			$order = new Order($r['id_order']);

			$lang_id = $order->id_lang;
			$reference = $order->reference;
			$order_date = $order->date_add;
			$customer = new Customer($order->id_customer);
			$email = $customer->email;

			if ($type == 'soft')
				$subject = $this->l('Ihre Zahlung wurde von Amazon abgelehnt');
			elseif ($type == 'hard')
				$subject = $this->l('Ihre Zahlung wurde von Amazon abgelehnt - bitte kontaktieren Sie uns');

			Mail::Send($lang_id, 'amazon_'.$type.'_decline', $subject, array('{$ORDER_NR}' => $reference, '{$ORDER_DATE}' => $order_date), $email, null, null, null, null, null, dirname(__FILE__).'/mails/', false, $this->context->shop->id);

			$str = 'Mail sent: '.'amazon_'.$type.'_decline'.' -> '.$subject.' -> '.$email;
			file_put_contents('amz.log', $str, FILE_APPEND);

		}
	}

	public function intelligentDeclinedMail($amz_id, $reason)
	{
		if ($this->send_mails_on_decline == '1')
		{
			$q = 'SELECT * FROM '._DB_PREFIX_.'amz_transactions WHERE amz_tx_amz_id = \''.pSQL($amz_id).'\'';
			$rs = Db::getInstance()->ExecuteS($q);
			foreach ($rs as $r)
			{
				if ($r['amz_tx_status'] == 'Declined' && $r['amz_tx_customer_informed'] == 0)
				{
					$informed = 0;
					if ($reason == 'InvalidPaymentMethod')
					{
						$this->sendSoftDeclinedMail($r['amz_tx_order_reference']);
						$informed = 1;
					}
					elseif ($reason == 'AmazonRejected')
					{
						$this->sendHardDeclinedMail($r['amz_tx_order_reference']);
						$informed = 1;
					}

					if ($informed == 1)
					{
						$q = 'UPDATE '._DB_PREFIX_.'amz_transactions SET amz_tx_customer_informed = 1 WHERE amz_tx_id = \''.(int)$r['amz_tx_id'].'\'';
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

}
