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

$response = Tools::file_get_contents('php://input');
include_once ('../../config/config.inc.php');
include_once ('../../init.php');
include_once ('../../modules/amzpayments/amzpayments.php');

$module_name = Tools::getValue('moduleName');

$amz_payments = new AmzPayments();

function jsonCleanDecode($json, $assoc = false, $depth = 512, $options = 0)
{
	$json = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t](//).*)#", '', $json);
	if (version_compare(phpversion(), '5.4.0', '>='))
		$json = Tools::jsonDecode($json, $assoc, $depth, $options);
	elseif (version_compare(phpversion(), '5.3.0', '>='))
		$json = Tools::jsonDecode($json, $assoc, $depth);
	else
		$json = Tools::jsonDecode($json, $assoc);
	return $json;
}

ob_start();

$response = jsonCleanDecode($response);
$message = jsonCleanDecode($response->Message);
$response_xml = simplexml_load_string($message->NotificationData);
$response_xml = $response_xml;
var_dump($response, $message, $response_xml);

if ($amz_payments->ipn_status == '1')
{
	switch ($message->NotificationType)
	{
		case 'PaymentAuthorize' :
			$q = 'SELECT * FROM '._DB_PREFIX_.'amz_transactions 
					WHERE amz_tx_type = \'auth\' AND amz_tx_amz_id = \''.pSQL($response_xml->AuthorizationDetails->AmazonAuthorizationId).'\'';
			$r = Db::getInstance()->getRow($q);

			$sqlArr = array('amz_tx_status' => (string)$response_xml->AuthorizationDetails->AuthorizationStatus->State, 
					'amz_tx_last_change' => time(), 
					'amz_tx_expiration' => strtotime($response_xml->AuthorizationDetails->ExpirationTimestamp), 
					'amz_tx_last_update' => time());
			Db::getInstance()->update('amz_transactions', $sqlArr, ' amz_tx_id = '.(int)$r['amz_tx_id']);
			$amz_payments->refreshAuthorization($response_xml->AuthorizationDetails->AmazonAuthorizationId);
			if ($sqlArr['amz_tx_status'] == 'Open')
			{
				if ($amz_payments->capture_mode == 'after_auth')
					AmazonTransactions::capture($amz_payments, $amz_payments->getService(), $r['amz_tx_order_reference'], $r['amz_tx_amount']);
			}
			elseif ($sqlArr['amz_tx_status'] == 'Declined')
			{
				$reason = (string)$response_xml->AuthorizationDetails->AuthorizationStatus->ReasonCode;
				$amz_payments->intelligentDeclinedMail($response_xml->AuthorizationDetails->AmazonAuthorizationId, $reason);
			}

			break;
		case 'PaymentCapture' :
			$q = 'SELECT * FROM '._DB_PREFIX_.'amz_transactions 
					WHERE amz_tx_type = \'capture\' AND amz_tx_amz_id = \''.pSQL($response_xml->CaptureDetails->AmazonCaptureId).'\'';
			$r = Db::getInstance()->getRow($q);

			$sqlArr = array('amz_tx_status' => (string)$response_xml->CaptureDetails->CaptureStatus->State, 
					'amz_tx_last_change' => time(), 
					'amz_tx_amount_refunded' => (float)$response_xml->CaptureDetails->RefundedAmount->Amount, 
					'amz_tx_last_update' => time());
			Db::getInstance()->update('amz_transactions', $sqlArr, ' amz_tx_id = '.(int)$r['amz_tx_id']);

			break;

		case 'PaymentRefund' :
			$q = 'SELECT * FROM '._DB_PREFIX_.'amz_transactions 
					WHERE amz_tx_type = \'refund\' AND amz_tx_amz_id = \''.pSQL($response_xml->RefundDetails->AmazonRefundId).'\'';
			$r = Db::getInstance()->getRow($q);

			$sqlArr = array('amz_tx_status' => (string)$response_xml->RefundDetails->RefundStatus->State, 
					'amz_tx_last_change' => time(), 
					'amz_tx_last_update' => time());
			Db::getInstance()->update('amz_transactions', $sqlArr, ' amz_tx_id = '.(int)$r['amz_tx_id']);

			break;
		case 'OrderReferenceNotification' :
			$q = 'SELECT * FROM '._DB_PREFIX_.'amz_transactions 
					WHERE amz_tx_type = \'order_ref\' AND amz_tx_amz_id = \''.pSQL($response_xml->OrderReference->AmazonOrderReferenceId).'\'';
			$r = Db::getInstance()->getRow($q);

			$sqlArr = array('amz_tx_status' => (string)$response_xml->OrderReference->OrderReferenceStatus->State, 
					'amz_tx_last_change' => time(), 
					'amz_tx_last_update' => time());
			Db::getInstance()->update('amz_transactions', $sqlArr, ' amz_tx_id = '.(int)$r['amz_tx_id']);

			break;
	}
	if ($amz_payments->capture_mode == 'after_shipping')
		$amz_payments->shippingCapture();
}

$str = ob_get_contents();
ob_end_clean();

file_put_contents('amz.log', $str, FILE_APPEND);
echo 'OK';
