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

class AmazonPaymentsAddressHelper
{

    public static function findByAmazonOrderReferenceIdOrNew($amazon_order_reference_id, $boolean = false)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
			SELECT a.`id_address`
			FROM `' . _DB_PREFIX_ . 'address` a
            JOIN `' . _DB_PREFIX_ . 'amz_address` aa ON aa.id_address = a.id_address
			WHERE aa.`amazon_order_reference_id` = "' . pSQL($amazon_order_reference_id) . '"');

        if ($boolean) {
            return $result['id_address'] ? true : false;
        } else {
            return $result['id_address'] ? new Address($result['id_address']) : new Address();
        }
    }

    public static function saveAddressAmazonReference(Address $address, $amazon_order_reference_id)
    {
        if (self::findByAmazonOrderReferenceIdOrNew($amazon_order_reference_id, true)) {
            Db::getInstance(_PS_USE_SQL_SLAVE_)->update('amz_address', array(
                'amazon_order_reference_id' => pSQL($amazon_order_reference_id)
            ), 'id_address = \'' . (int) $address->id . '\'');
        } else {
            Db::getInstance(_PS_USE_SQL_SLAVE_)->insert('amz_address', array(
                'id_address' => pSQL((int)$address->id),
                'amazon_order_reference_id' => pSQL($amazon_order_reference_id)
            ));
        }
    }

    public static function fetchInvalidInput(Address $address, $additional_data = false)
    {
        $fields_to_set = array();
        foreach (Address::getFieldsValidate() as $field_to_validate => $validation_rule) {
            if ($address->validateField($field_to_validate, $address->$field_to_validate) !== true) {
                $fields_to_set[] = $field_to_validate;
            }
        }
        if (is_array($additional_data)) {
            foreach ($additional_data as $field => $value) {
                if (!in_array($field, $fields_to_set)) {
                    $fields_to_set[] = $field;
                }
            }
        }
        return $fields_to_set;
    }

    public static function addAdditionalValues(Address $address, array $additional_data)
    {
        foreach ($additional_data as $field => $value) {
            $address->$field = pSQL($value);
        }
        return $address;
    }

    public static function stateBelongsToCountry($id_state, $id_country)
    {
        $state = new State((int)$id_state);
        return $state->id_country == $id_country;
    }
}
