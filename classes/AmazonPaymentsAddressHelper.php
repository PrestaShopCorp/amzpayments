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

class AmazonPaymentsAddressHelper
{
    public static $validation_errors = array();

    public static function findByAmazonOrderReferenceIdOrNew($amazon_order_reference_id, $boolean = false, $amazon_address = false)
    {
        $amazon_hash = self::createHash($amazon_address);
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
			SELECT a.`id_address`
			FROM `' . _DB_PREFIX_ . 'address` a
            JOIN `' . _DB_PREFIX_ . 'amz_address` aa ON aa.id_address = a.id_address
			WHERE aa.`amazon_order_reference_id` = "' . pSQL($amazon_order_reference_id) . '"' .
            ($amazon_hash != '' ? ' OR aa.`amazon_hash` = "' . pSQL($amazon_hash) . '"' : false));
        if ($boolean) {
            return $result['id_address'] ? true : false;
        } else {
            return $result['id_address'] ? new Address($result['id_address']) : new Address();
        }
    }
    
    public static function saveAddressAmazonReference(Address $address, $amazon_order_reference_id, $amazon_address = false)
    {
        $amazon_hash = self::createHash($amazon_address);
        if (self::findByAmazonOrderReferenceIdOrNew($amazon_order_reference_id, true, $amazon_address)) {
            Db::getInstance(_PS_USE_SQL_SLAVE_)->update('amz_address', array(
                'amazon_order_reference_id' => pSQL($amazon_order_reference_id),
                'amazon_hash' => pSQL($amazon_hash)
            ), 'id_address = \'' . (int) $address->id . '\'');
        } else {
            Db::getInstance(_PS_USE_SQL_SLAVE_)->insert('amz_address', array(
                'id_address' => pSQL((int)$address->id),
                'amazon_order_reference_id' => pSQL($amazon_order_reference_id),
                'amazon_hash' => pSQL($amazon_hash)
            ));
        }
    }
    
    public static function fetchInvalidInput(Address $address, $additional_data = false)
    {
        $fields_to_set = array();
        foreach (Address::getFieldsValidate() as $field_to_validate => $validation_rule) {
            $validation = $address->validateField($field_to_validate, $address->$field_to_validate, null, array(), true);
            if ($validation !== true) {
                $fields_to_set[] = $field_to_validate;
                self::$validation_errors[] = $validation;
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
            if (!($field == 'id_state' && (int)$value < 0) && trim($value) != '') {
                $address->$field = pSQL($value);
            }
        }
        return $address;
    }
    
    public static function stateBelongsToCountry($id_state, $id_country)
    {
        $state = new State((int)$id_state);
        return $state->id_country == $id_country;
    }
    
    public static function createHash($amazon_address)
    {
        $amazon_hash = '';
        if ($amazon_address && is_array($amazon_address)) {
            $amazon_hash .= (string) AmzPayments::getFromArray($amazon_address, 'CountryCode');
            $amazon_hash .= (string) AmzPayments::getFromArray($amazon_address, 'City');
            $amazon_hash .= (string) AmzPayments::getFromArray($amazon_address, 'PostalCode');
            $amazon_hash .= (string) AmzPayments::getFromArray($amazon_address, 'State');
            $amazon_hash .= (string) AmzPayments::getFromArray($amazon_address, 'Name');
            $amazon_hash .= (string) AmzPayments::getFromArray($amazon_address, 'Phone');
            $amazon_hash .= (string) AmzPayments::getFromArray($amazon_address, 'AddressLine1');
            $amazon_hash .= (string) AmzPayments::getFromArray($amazon_address, 'AddressLine2');
            $amazon_hash .= (string) AmzPayments::getFromArray($amazon_address, 'AddressLine3');
            $amazon_hash = md5($amazon_hash);
        }
        return $amazon_hash;
    }
    
    public static function getThemeTranslation($s, $controller = false)
    {
        if (!$controller) {
            $controller = 'address';
        }
        switch ($s) {
            case 'company':
                $s = 'Company';
                break;
            case 'vat_number':
                $s = 'VAT number';
                break;
            case 'dni':
                $s = 'Identification number';
                break;
            case 'firstname':
                $s = 'First name';
                break;
            case 'lastname':
                $s = 'Last name';
                break;
            case 'address1':
                $s = 'Address';
                break;
            case 'address2':
                $s = 'Address (Line 2)';
                break;
            case 'postcode':
                $s = 'Zip/Postal Code';
                break;
            case 'city':
                $s = 'City';
                break;
            case 'phone':
                $s = 'Home phone';
                break;
            case 'phone_mobile':
                $s = 'Mobile phone';
                break;
            case 'other':
                $s = 'Additional information';
                break;
        }
        $iso = Context::getContext()->language->iso_code;
        if (Validate::isLangIsoCode($iso)) {
            $lang_file = _PS_THEME_DIR_.'lang/'.$iso.'.php';
            if (file_exists($lang_file) && include($lang_file)) {
                $key = $controller.'_'.md5($s);
                if (isset($_LANG)) {
                    if (isset($_LANG[$key])) {
                        return $_LANG[$key];
                    }
                }
            }
        }
        return $s;
    }
}
