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

class AmazonTransactions
{

    public static function getAuthorizeDetails(AmzPayments $amz_payments, $service, $auth_ref_id)
    {
        $auth_details_request = new OffAmazonPaymentsService_Model_GetAuthorizationDetailsRequest();
        
        $auth_details_request->setSellerId($amz_payments->merchant_id);
        $auth_details_request->setAmazonAuthorizationId($auth_ref_id);
        
        try {
            return $service->getAuthorizationDetails($auth_details_request);
        } catch (OffAmazonPaymentsService_Exception $e) {
            return false;
        }
    }

    public static function getRefundDetails(AmzPayments $amz_payments, $service, $refund_ref_id)
    {
        $refund_details_request = new OffAmazonPaymentsService_Model_GetRefundDetailsRequest();
        
        $refund_details_request->setSellerId($amz_payments->merchant_id);
        $refund_details_request->setAmazonRefundId($refund_ref_id);
        
        try {
            return $service->getRefundDetails($refund_details_request);
        } catch (OffAmazonPaymentsService_Exception $e) {
            return false;
        }
    }

    public static function authorize(AmzPayments $amz_payments, $service, $order_ref, $amount, $currency_code = 'EUR', $timeout = 1440, $comment = '')
    {
        if ($currency_code == '0') {
            $currency_code = 'EUR';
        }
        $currency_code = self::transformCurrencyCode($currency_code);
        $authorize_request = new OffAmazonPaymentsService_Model_AuthorizeRequest();
        $authorize_request->setAmazonOrderReferenceId($order_ref);
        $authorize_request->setSellerId($amz_payments->merchant_id);
        $authorize_request->setTransactionTimeout($timeout);
        $authorize_request->setSoftDescriptor($comment);
        if ($amz_payments->capture_mode == 'after_auth') {
            $authorize_request->setCaptureNow(true);
        }
        
        $authorize_request->setAuthorizationReferenceId(self::getNextAuthRef($order_ref));
        $authorize_request->setAuthorizationAmount(new OffAmazonPaymentsService_Model_Price());
        $authorize_request->getAuthorizationAmount()->setAmount($amount);
        $authorize_request->getAuthorizationAmount()->setCurrencyCode($currency_code);
        try {
            $response = $service->authorize($authorize_request);
            $details = $response->getAuthorizeResult()->getAuthorizationDetails();
            
            $sql_arr = array(
                'amz_tx_order_reference' => pSQL($order_ref),
                'amz_tx_type' => 'auth',
                'amz_tx_time' => pSQL(time()),
                'amz_tx_expiration' => pSQL(strtotime($details->getExpirationTimestamp())),
                'amz_tx_amount' => pSQL($amount),
                'amz_tx_status' => pSQL($details->getAuthorizationStatus()->getState()),
                'amz_tx_reference' => pSQL($details->getAuthorizationReferenceId()),
                'amz_tx_amz_id' => pSQL($details->getAmazonAuthorizationId()),
                'amz_tx_last_change' => pSQL(time()),
                'amz_tx_last_update' => pSQL(time())
            );
            
            Db::getInstance()->insert('amz_transactions', $sql_arr);
        } catch (OffAmazonPaymentsService_Exception $e) {
            $amz_paymentsObj = new AmzPayments();
            $amz_paymentsObj->exceptionLog($e);
            echo 'ERROR: ' . $e->getMessage();
        }
        return $response;
    }

    public static function refund(AmzPayments $amz_payments, $service, $capture_id, $amount, $currency_code = 'EUR')
    {
        $currency_code = self::transformCurrencyCode($currency_code);
        $order_ref = self::getOrderRefFromAmzId($capture_id);
        $refund = new OffAmazonPaymentsService_Model_Price();
        $refund->setCurrencyCode($currency_code);
        $refund->setAmount($amount);
        
        $refund_request = new OffAmazonPaymentsService_Model_RefundRequest();
        $refund_request->setSellerId($amz_payments->merchant_id);
        $refund_request->setAmazonCaptureId($capture_id);
        $refund_request->setRefundReferenceId(self::getNextRefundRef($order_ref));
        $refund_request->setRefundAmount($refund);
        
        try {
            $response = $service->refund($refund_request);
            
            $details = $response->getRefundResult()->getRefundDetails();
            
            $sql_arr = array(
                'amz_tx_order_reference' => pSQL($order_ref),
                'amz_tx_type' => 'refund',
                'amz_tx_time' => pSQL(time()),
                'amz_tx_expiration' => 0,
                'amz_tx_amount' => pSQL($amount),
                'amz_tx_status' => pSQL($details->getRefundStatus()->getState()),
                'amz_tx_reference' => pSQL($details->getRefundReferenceId()),
                'amz_tx_amz_id' => pSQL($details->getAmazonRefundId()),
                'amz_tx_last_change' => pSQL(time()),
                'amz_tx_last_update' => pSQL(time())
            );
            Db::getInstance()->insert('amz_transactions', $sql_arr);
        } catch (OffAmazonPaymentsService_Exception $e) {
            $amz_paymentsObj = new AmzPayments();
            $amz_paymentsObj->exceptionLog($e);
            echo 'ERROR: ' . $e->getMessage();
        }
        return $response;
    }

    public static function capture(AmzPayments $amz_payments, $service, $auth_id, $amount, $currency_code = 'EUR', $display_error_message = false)
    {
        $currency_code = self::transformCurrencyCode($currency_code);
        if ($auth_id) {
            $order_ref = self::getOrderRefFromAmzId($auth_id);
            $capture_request = new OffAmazonPaymentsService_Model_CaptureRequest();
            $capture_request->setAmazonAuthorizationId($auth_id);
            $capture_request->setSellerId($amz_payments->merchant_id);
            $capture_request->setCaptureReferenceId(self::getNextCaptureRef($order_ref));
            $capture_request->setCaptureAmount(new OffAmazonPaymentsService_Model_Price());
            $capture_request->getCaptureAmount()->setAmount($amount);
            $capture_request->getCaptureAmount()->setCurrencyCode($currency_code);
            if ($amz_payments->provocation == 'capture_decline' && $amz_payments->environment == 'SANDBOX') {
                $capture_request->setSellerCaptureNote('{"SandboxSimulation":{"State":"Declined", "ReasonCode":"AmazonRejected"}}');
            }
            
            try {
                $response = $service->capture($capture_request);
                $details = $response->getCaptureResult()->getCaptureDetails();
                
                $sql_arr = array(
                    'amz_tx_order_reference' => pSQL($order_ref),
                    'amz_tx_type' => 'capture',
                    'amz_tx_time' => pSQL(time()),
                    'amz_tx_expiration' => 0,
                    'amz_tx_amount' => pSQL($amount),
                    'amz_tx_status' => pSQL($details->getCaptureStatus()->getState()),
                    'amz_tx_reference' => pSQL($details->getCaptureReferenceId()),
                    'amz_tx_amz_id' => pSQL($details->getAmazonCaptureId()),
                    'amz_tx_last_change' => pSQL(time()),
                    'amz_tx_last_update' => pSQL(time())
                );
                Db::getInstance()->insert('amz_transactions', $sql_arr);
                
                self::setOrderStatusCapturedSuccesfully($order_ref);
            } catch (OffAmazonPaymentsService_Exception $e) {
                $amz_paymentsObj = new AmzPayments();
                $amz_paymentsObj->exceptionLog($e);
                if ($display_error_message) {
                    echo 'ERROR: ' . $e->getMessage();
                }
                return false;
            }
            
            return $response;
        }
    }

    public static function closeOrder(AmzPayments $amz_payments, $service, $orderRef)
    {
        $orderRefRequest = new OffAmazonPaymentsService_Model_CloseOrderReferenceRequest();
        $orderRefRequest->setSellerId($amz_payments->merchant_id);
        $orderRefRequest->setAmazonOrderReferenceId($orderRef);
        try {
            $response = $service->closeOrderReference($orderRefRequest);
        } catch (OffAmazonPaymentsService_Exception $e) {
            $amz_paymentsObj = new AmzPayments();
            $amz_paymentsObj->exceptionLog($e);
            echo 'ERROR: ' . $e->getMessage();
        }
        return $response;
    }

    public static function captureTotalFromAuth(AmzPayments $amz_payments, $service, $auth_id)
    {
        $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions WHERE amz_tx_type=\'auth\' 
				AND amz_tx_amz_id = \'' . pSQL($auth_id) . '\'';
        $r = Db::getInstance()->getRow($q);
        if ($r) {
            $order_ref = AmazonTransactions::getOrderRefFromAmzId($auth_id);
            $order_id = AmazonTransactions::getOrdersIdFromOrderRef($order_ref);
            $order = new Order((int) $order_id);
            if (Validate::isLoadedObject($order)) {
                $currency = new Currency($order->id_currency);
                if (Validate::isLoadedObject($currency)) {
                    return self::capture($amz_payments, $service, $auth_id, $r['amz_tx_amount'], $currency->iso_code);
                }
            }
            return false;
        } else {
            return false;
        }
    }

    public static function getAuthorizationForCapture($order_ref)
    {
        $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions WHERE amz_tx_status = \'Open\' 
				AND amz_tx_type = \'auth\' AND amz_tx_order_reference = \'' . pSQL($order_ref) . '\'';
        if ($r = Db::getInstance()->getRow($q)) {
            return $r;
        }
    }

    public static function getCaptureForRefund($order_ref)
    {
        $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions WHERE amz_tx_status = \'Completed\' 
				AND amz_tx_type = \'capture\' AND amz_tx_order_reference = \'' . pSQL($order_ref) . '\'';
        if ($r = Db::getInstance()->getRow($q)) {
            return $r;
        }
    }

    public static function isAlreadyConfirmedOrder($order_ref)
    {
        $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions WHERE amz_tx_status = \'Open\' 
				AND amz_tx_type = \'order_ref\' AND amz_tx_order_reference = \'' . pSQL($order_ref) . '\'';
        if ($r = Db::getInstance()->getRow($q)) {
            return $r;
        }
        return false;
    }

    public static function getNextAuthRef($order_ref)
    {
        return self::getNextRef($order_ref, 'auth');
    }

    public static function getNextCaptureRef($order_ref)
    {
        return self::getNextRef($order_ref, 'capture');
    }

    public static function getNextRefundRef($order_ref)
    {
        return self::getNextRef($order_ref, 'refund');
    }

    public static function getCurrentAmzTransactionStateAndId($order_ref)
    {
        $q = 'SELECT `amz_tx_status`, `amz_tx_amz_id`, `amz_tx_amount` FROM ' . _DB_PREFIX_ . 'amz_transactions 
				WHERE amz_tx_order_reference = \'' . pSQL($order_ref) . '\' ORDER BY amz_tx_time DESC, amz_tx_id DESC';
        $r = Db::getInstance()->getRow($q);
        return $r;
    }

    public static function getCurrentAmzTransactionRefundStateAndId($order_ref)
    {
        $q = 'SELECT `amz_tx_status`, `amz_tx_amz_id`, `amz_tx_amount` FROM ' . _DB_PREFIX_ . 'amz_transactions 
				WHERE amz_tx_type=\'refund\' AND amz_tx_order_reference = \'' . pSQL($order_ref) . '\' ORDER BY amz_tx_time DESC, amz_tx_id DESC';
        $r = Db::getInstance()->getRow($q);
        return $r;
    }

    public static function getNextRef($order_ref, $type)
    {
        $last_id = 0;
        $prefix = Tools::substr($type, 0, 1);
        $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions WHERE amz_tx_type=\'' . pSQL($type) . '\' 
				AND amz_tx_order_reference = \'' . pSQL($order_ref) . '\' ORDER BY amz_tx_id DESC';
        if ($r = Db::getInstance()->getRow($q)) {
            $last_id = (int) str_replace($order_ref . '-' . $prefix, '', $r['amz_tx_reference']);
        }
        $new_id = $last_id + 1;
        return $order_ref . '-' . $prefix . str_pad($new_id, 2, '0', STR_PAD_LEFT);
    }

    public static function fastAuth(AmzPayments $amz_payments, $service, $order_ref, $amount, $currency_code = 'EUR', $comment = '')
    {
        ob_start();
        $response = self::authorize($amz_payments, $service, $order_ref, $amount, $currency_code, 0, $comment);
        ob_end_clean();
        if (is_object($response)) {
            if ($response->getAuthorizeResult()
                ->getAuthorizationDetails()
                ->getAuthorizationStatus()
                ->getState() != 'Open') {
                return $response;
            }
            self::setOrderStatusAuthorized($order_ref, true);
        }
        return $response;
    }

    public static function setOrderStatusAuthorized($order_ref, $check = false)
    {
        $oid = self::getOrdersIdFromOrderRef($order_ref);
        if ($oid) {
            $amz_payments = new AmzPayments();
            $new_status = $amz_payments->authorized_status_id;
            if ($check) {
                $order = new Order((int)$oid);
                $history = $order->getHistory(Context::getContext()->language->id, $amz_payments->authorized_status_id);
                if (sizeof($history) > 0) {
                    return false;
                }
            }
            self::setOrderStatus($oid, $new_status);
        } else {
            if (! isset(Context::getContext()->cookie->amzSetStatusAuthorized)) {
                Context::getContext()->cookie->amzSetStatusAuthorized = serialize(array());
            }
            $tmpData = Tools::unSerialize(Context::getContext()->cookie->amzSetStatusAuthorized);
            $tmpData[] = $order_ref;
            Context::getContext()->cookie->amzSetStatusAuthorized = serialize($tmpData);
        }
    }

    public static function setOrderStatusCaptured($order_ref)
    {
        $oid = self::getOrdersIdFromOrderRef($order_ref);
        if ($oid) {
            $amz_payments = new AmzPayments();
            $new_status = $amz_payments->capture_success_status_id;
            self::setOrderStatus($oid, $new_status);
        } else {
            if (! isset(Context::getContext()->cookie->amzSetStatusCaptured)) {
                Context::getContext()->cookie->amzSetStatusCaptured = serialize(array());
            }
            $tmpData = Tools::unSerialize(Context::getContext()->cookie->amzSetStatusCaptured);
            $tmpData[] = $order_ref;
            Context::getContext()->cookie->amzSetStatusCaptured = serialize($tmpData);
        }
    }
    
    public static function setOrderStatusDeclined($order_ref, $check = true)
    {
        $oid = self::getOrdersIdFromOrderRef($order_ref);
        if ($oid) {
            $amz_payments = new AmzPayments();
            $new_status = $amz_payments->decline_status_id;
            if ($check) {
                $order = new Order((int)$oid);
                $history = $order->getHistory(Context::getContext()->language->id, $amz_payments->decline_status_id);
                if (sizeof($history) > 0) {
                    return false;
                }
            }
            self::setOrderStatus($oid, $new_status);
        }
    }

    public static function setOrderStatusCapturedSuccesfully($order_ref)
    {
        $oid = self::getOrdersIdFromOrderRef($order_ref);
        if ($oid) {
            $amz_payments = new AmzPayments();
            $new_status = $amz_payments->capture_success_status_id;
            self::setOrderStatus($oid, $new_status);
        } else {
            if (! isset(Context::getContext()->cookie->amzSetStatusCaptured)) {
                Context::getContext()->cookie->amzSetStatusCaptured = serialize(array());
            }
            $tmpData = Tools::unSerialize(Context::getContext()->cookie->amzSetStatusCaptured);
            $tmpData[] = $order_ref;
            Context::getContext()->cookie->amzSetStatusCaptured = serialize($tmpData);
        }
    }

    public static function getOrdersIdFromOrderRef($order_ref)
    {
        $q = 'SELECT `id_order` FROM `' . _DB_PREFIX_ . 'amz_orders`
				WHERE `amazon_order_reference_id` = \'' . pSQL($order_ref) . '\'';
        $r = Db::getInstance()->getRow($q);
        return $r['id_order'];
    }

    public static function setOrderStatus($oid, $status, $comment = false)
    {
        unset($comment);
        $order_history = new OrderHistory();
        $order_history->id_order = (int)$oid;
        $order_history->changeIdOrderState((int)$status, (int)$oid, true);
        $order_history->addWithemail(true);
    }

    public static function getOrderRefTotal($order_ref)
    {
        $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions 
				WHERE amz_tx_order_reference = \'' . pSQL($order_ref) . '\' AND amz_tx_type = \'order_ref\'';
        $r = Db::getInstance()->getRow($q);
        return (float) $r['amz_tx_amount'];
    }

    public static function getOrderRefFromAmzId($amz_id)
    {
        $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions 
				WHERE amz_tx_amz_id = \'' . pSQL($amz_id) . '\'';
        $r = Db::getInstance()->getRow($q);
        return $r['amz_tx_order_reference'];
    }
    
    public static function transformCurrencyCode($currency_iso)
    {
        if ($currency_iso == 'JPY') {
            return 'YEN';
        }
        return $currency_iso;
    }
}
