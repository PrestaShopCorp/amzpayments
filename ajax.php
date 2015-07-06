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

include_once ('../../config/config.inc.php');
include_once ('../../init.php');
include_once ('../../modules/amzpayments/amzpayments.php');

$module_name = Tools::getValue('moduleName');

$amz_payments = new AmzPayments();

if (Tools::getValue('action'))
{
	if (Tools::getValue('action') == 'shippingCapture')
		$_POST['action'] = 'shippingCapture';
}
switch (Tools::getValue('action'))
{
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
		echo '<br/><b>'.$amz_payments->l('AMZ_ORDER_CLOSED').'</b>';
		break;
	case 'cancelOrder':
		$amz_payments->cancelOrder(Tools::getValue('orderRef'));
		echo '<br/><b>'.$amz_payments->l('AMZ_ORDER_CANCELLED').'</b>';
		break;
	case 'refreshOrder':
		$q = 'SELECT * FROM '._DB_PREFIX_.'amz_transactions 
				WHERE amz_tx_order_reference = \''.pSQL(Tools::getValue('orderRef')).'\' 
				AND amz_tx_status != \'Closed\' AND amz_tx_status != \'Declined\'';
		$rs = Db::getInstance()->ExecuteS($q);
		foreach ($rs as $r)
			$amz_payments->intelligentRefresh($r);
		echo '<br/><b>'.$amz_payments->l('AMZ_FINISHED_REFRESHING_ORDER').'</b>';
		break;

	case 'authorizeAmount':
		$response = AmazonTransactions::authorize($amz_payments, $amz_payments->getService(), Tools::getValue('orderRef'), Tools::getValue('amount'));
		if ($response)
		{
			$details = $response->getAuthorizeResult()->getAuthorizationDetails();
			$status = $details->getAuthorizationStatus()->getState();
			if ($status == 'Open' || $status == 'Pending')
				echo $amz_payments->l('AMZ_AUTHORIZATION_SUCCESSFULLY_REQUESTED');
			else
				echo '<br/><b>'.$amz_payments->l('AMZ_AUTHORIZATION_REQUEST_FAILED').'</b>';
		}
		else
			echo '<br/><b>'.$amz_payments->l('AMZ_AUTHORIZATION_REQUEST_FAILED').'</b>';
		break;

	case 'captureTotalFromAuth':
		$response = AmazonTransactions::captureTotalFromAuth($amz_payments, $amz_payments->getService(), Tools::getValue('authId'));

		$details = $response->getCaptureResult()->getCaptureDetails();
		$status = $details->getCaptureStatus()->getState();
		if ($status == 'Completed')
			echo $amz_payments->l('AMZ_CAPTURE_SUCCESS');
		else
			echo '<br/><b>'.$amz_payments->l('AMZ_CAPTURE_FAILED').'</b>';
		break;
	case 'captureAmountFromAuth':
		$response = AmazonTransactions::capture($amz_payments, $amz_payments->getService(), Tools::getValue('authId'), Tools::getValue('amount'));
		if (is_object($response))
		{
			$details = $response->getCaptureResult()->getCaptureDetails();
			$status = $details->getCaptureStatus()->getState();
			if ($status == 'Completed')
				echo $amz_payments->l('AMZ_CAPTURE_SUCCESS');
			else
				echo '<br/><b>'.$amz_payments->l('AMZ_CAPTURE_FAILED').'</b>';
		}
		break;

	case 'refundAmount':
		$response = AmazonTransactions::refund($amz_payments, $amz_payments->getService(), Tools::getValue('captureId'), Tools::getValue('amount'));
		if (is_object($response))
		{
			$details = $response->getRefundResult()->getRefundDetails();
			$status = $details->getRefundStatus()->getState();
			if ($status == 'Pending')
			{
				$q = 'UPDATE '._DB_PREFIX_.'amz_transactions 
						SET amz_tx_amount_refunded = amz_tx_amount_refunded + '.(float)Tools::getValue('amount').'
						WHERE amz_tx_amz_id = \''.pSQL(Tools::getValue('captureId')).'\'';
				DB::getInstance()->execute($q);
				echo $amz_payments->l('AMZ_REFUND_SUCCESS');
			}
			else
				echo $amz_payments->l('AMZ_REFUND_FAILED');
		}
		break;

	case 'shippingCapture':
		$amz_payments->shippingCapture();
		break;

	case 'versionCheck':
		if (function_exists('curl_version'))
		{
			$url = 'http://www.patworx.de/API/amazon_advanced_payments.php';
			$fields_string = '';
			foreach ($_POST as $key => $value)
				$fields_string .= $key.'='.$value.'&';

			$fields_string = rtrim($fields_string, '&');
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, count($_POST));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
			$result = curl_exec($ch);
			curl_close($ch);
		}
		else
			echo 'Please activate `curl´ or ask your hosting provider.';
		die();
}
