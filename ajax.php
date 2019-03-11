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

use PrestaShopBundle\Security\Admin\Employee;

include_once('../../config/config.inc.php');
include_once('../../init.php');
include_once('../../modules/amzpayments/amzpayments.php');

$cookies = new Cookie('psAdmin');
if (isset($cookies->id_employee)) {
    $empl = new EmployeeCore((int)$cookies->id_employee);
    if (!$empl) {
        die('unauthorized access');
    }
} else {
    die('unauthorized access');
}

$module_name = Tools::getValue('moduleName');

$amz_payments = new AmzPayments();

if (Tools::getValue('action')) {
    if (Tools::getValue('action') == 'shippingCapture') {
        $_POST['action'] = 'shippingCapture';
    }
}
switch (Tools::getValue('action')) {
    case 'getHistory':
        echo $amz_payments->getOrderHistory(Tools::getValue('orderRef'));
        break;
    case 'getSummary':
        echo $amz_payments->getOrderSummary(Tools::getValue('orderRef'));
        break;
    case 'getActions':
        echo $amz_payments->getOrderActions(Tools::getValue('orderRef'));
        break;
    case 'closeOrder':
        $amz_payments->closeOrder(Tools::getValue('orderRef'));
        echo '<br/><b>' . $amz_payments->l('Order completed') . '</b>';
        break;
    case 'cancelOrder':
        $amz_payments->cancelOrder(Tools::getValue('orderRef'));
        echo '<br/><b>' . $amz_payments->l('Payment process cancelled') . '</b>';
        break;
    case 'refreshOrder':
        $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions 
				WHERE amz_tx_order_reference = \'' . pSQL(Tools::getValue('orderRef')) . '\' 
				AND amz_tx_status != \'Closed\' AND amz_tx_status != \'Declined\'';
        $rs = Db::getInstance()->ExecuteS($q);
        foreach ($rs as $r) {
            $amz_payments->intelligentRefresh($r);
        }
        echo '<br/><b>' . $amz_payments->l('Update is completed!') . '</b>';
        break;
    
    case 'authorizeAmount':
        $order_id = AmazonTransactions::getOrdersIdFromOrderRef(Tools::getValue('orderRef'));
        $order = new Order((int) $order_id);
        $currency = new Currency($order->id_currency);
        $response = AmazonTransactions::authorize($amz_payments, $amz_payments->getService(), Tools::getValue('orderRef'), Tools::getValue('amount'), $currency->iso_code);
        if ($response) {
            $details = $response['AuthorizeResult']['AuthorizationDetails'];
            $status = $details['AuthorizationStatus']['State'];
            if ($status == 'Open' || $status == 'Pending') {
                echo $amz_payments->l('Authorisation request was started successfully');
            } else {
                echo '<br/><b>' . $amz_payments->l('Creation of the authorisation request has failed') . '</b>';
            }
        } else {
            echo '<br/><b>' . $amz_payments->l('Creation of the authorisation request has failed') . '</b>';
        }
        break;
    
    case 'captureTotalFromAuth':
        $response = AmazonTransactions::captureTotalFromAuth($amz_payments, $amz_payments->getService(), Tools::getValue('authId'));
        
        $details = $response['CaptureResult']['CaptureDetails'];
        $status = $details['CaptureStatus']['State'];
        if ($status == 'Completed') {
            echo $amz_payments->l('Capture successful');
        } else {
            echo '<br/><b>' . $amz_payments->l('Capture failed') . '</b>';
        }
        break;
    
    case 'captureAmountFromAuth':
        $order_ref = AmazonTransactions::getOrderRefFromAmzId(Tools::getValue('authId'));
        $order_id = AmazonTransactions::getOrdersIdFromOrderRef($order_ref);
        $order = new Order((int) $order_id);
        $currency = new Currency($order->id_currency);
        $response = AmazonTransactions::capture($amz_payments, $amz_payments->getService(), Tools::getValue('authId'), Tools::getValue('amount'), $currency->iso_code);
        if (is_array($response)) {
            $details = $response['CaptureResult']['CaptureDetails'];
            $status = $details['CaptureStatus']['State'];
            if ($status == 'Completed') {
                echo $amz_payments->l('Capture successful');
            } else {
                echo '<br/><b>' . $amz_payments->l('Capture failed') . '</b>';
            }
        } else {
            echo $response;
        }
        break;
    
    case 'refundAmount':
        $order_ref = AmazonTransactions::getOrderRefFromAmzId(Tools::getValue('captureId'));
        $order_id = AmazonTransactions::getOrdersIdFromOrderRef($order_ref);
        $order = new Order((int) $order_id);
        $currency = new Currency($order->id_currency);
        $response = AmazonTransactions::refund($amz_payments, $amz_payments->getService(), Tools::getValue('captureId'), Tools::getValue('amount'), $currency->iso_code);
        if (is_array($response)) {
            $details = $response['RefundResult']['RefundDetails'];
            $status = $details['RefundStatus']['State'];
            if ($status == 'Pending') {
                $q = 'UPDATE ' . _DB_PREFIX_ . 'amz_transactions 
						SET amz_tx_amount_refunded = amz_tx_amount_refunded + ' . (float) Tools::getValue('amount') . '
						WHERE amz_tx_amz_id = \'' . pSQL(Tools::getValue('captureId')) . '\'';
                DB::getInstance()->execute($q);
                echo $amz_payments->l('Refund request was started successfully');
            } else {
                echo $amz_payments->l('Refund failed');
            }
        } else {
            echo $response;
        }
        break;
    
    case 'shippingCapture':
        $amz_payments->shippingCapture();
        break;
    
    case 'versionCheck':
        if (function_exists('curl_version')) {
            $url = 'https://www.patworx.de/API/amazon_advanced_payments.php';
            $fields_string = '';
            foreach ($_POST as $key => $value) {
                $fields_string .= $key . '=' . $value . '&';
            }
            
            $fields_string = rtrim($fields_string, '&');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, count($_POST));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            $result = curl_exec($ch);
            curl_close($ch);
        } else {
            echo 'Please activate `curl´ or ask your hosting provider.';
        }
        die();
}
