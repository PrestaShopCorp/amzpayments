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

class AmazonPaymentsCustomerHelper
{

    public static function findByAmazonCustomerId($amazon_customer_id, $ignore_guest = true)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
				SELECT c.*
				FROM `' . _DB_PREFIX_ . 'customer` c
                JOIN `' . _DB_PREFIX_ . 'amz_customer` ac ON c.`id_customer` = ac.`id_customer`
				WHERE ac.`amazon_customer_id` = \'' . pSQL($amazon_customer_id) . '\'
				' . Shop::addSqlRestriction(Shop::SHARE_CUSTOMER) . '
				AND c.`deleted` = 0
				' . ($ignore_guest ? ' AND c.`is_guest` = 0' : ''));
        return $result['id_customer'] ? $result['id_customer'] : false;
    }

    public static function findByEmailAddress($email, $ignore_guest = true)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
				SELECT *
				FROM `' . _DB_PREFIX_ . 'customer`
				WHERE `email` = \'' . pSQL($email) . '\'
				' . Shop::addSqlRestriction(Shop::SHARE_CUSTOMER) . '
				AND `deleted` = 0
				' . ($ignore_guest ? ' AND `is_guest` = 0' : ''));
        return $result['id_customer'] ? new Customer($result['id_customer']) : false;
    }

    public static function getByCustomerID($id_customer, $ignore_guest = true, $customer = '')
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
				SELECT *
				FROM `' . _DB_PREFIX_ . 'customer`
				WHERE `id_customer` = \'' . pSQL($id_customer) . '\'
				' . Shop::addSqlRestriction(Shop::SHARE_CUSTOMER) . '
				AND `deleted` = 0
				' . ($ignore_guest ? ' AND `is_guest` = 0' : ''));
        
        if (! $result) {
            return false;
        }
        $customer->id = $result['id_customer'];
        foreach ($result as $key => $value) {
            if (array_key_exists($key, $customer)) {
                $customer->{$key} = $value;
            }
        }
        
        return $customer;
    }

    public static function saveCustomersAmazonReference(Customer $customer, $amazon_customer_id)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
            SELECT * FROM `' . _DB_PREFIX_ . 'amz_customer` WHERE `id_customer` = \'' . (int)$customer->id . '\'    
        ');
        
        if ($result) {
            Db::getInstance(_PS_USE_SQL_SLAVE_)->update('amz_customer', array(
                'amazon_customer_id' => pSQL($amazon_customer_id)
            ), 'id_customer = \'' . (int) $customer->id . '\'');
        } else {
            Db::getInstance(_PS_USE_SQL_SLAVE_)->insert('amz_customer', array(
                'id_customer' => pSQL((int)$customer->id),
                'amazon_customer_id' => pSQL($amazon_customer_id)
            ));
        }
    }

    public static function customerHasAmazonCustomerId($id_customer)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
				SELECT ac.*
				  FROM `' . _DB_PREFIX_ . 'customer` c
                  JOIN `' . _DB_PREFIX_ . 'amz_customer` ac ON c.`id_customer` = ac.`id_customer`
				 WHERE c.`id_customer` = \'' . pSQL($id_customer) . '\'
				    ' . Shop::addSqlRestriction(Shop::SHARE_CUSTOMER) . '
				   AND c.`deleted` = 0');
        if (!$result) {
            return false;
        } else {
            return $result['amazon_customer_id'] ? $result['amazon_customer_id'] : false;
        }
    }
}
