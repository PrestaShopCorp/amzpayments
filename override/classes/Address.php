<?php /**
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
 *  @author    patworx multimedia GmbH
 <service@patworx.de>
 *  @copyright 2013-2015 patworx multimedia GmbH
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Address extends AddressCore
{

	public $amazon_order_reference_id;

	public function __construct($id_address = null, $id_lang = null)
	{
		self::$definition['fields']['amazon_order_reference_id'] = array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64);

		parent::__construct($id_address, $id_lang);

	}

	public static function findByAmazonOrderReferenceIdOrNew($amazon_order_reference_id)
	{
		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
	SELECT a.`id_address`
	FROM `'._DB_PREFIX_.'address` a
	WHERE a.`amazon_order_reference_id` = "'.pSQL($amazon_order_reference_id).'"');

		return $result['id_address'] ? new self($result['id_address']) : new self();
	}

}
