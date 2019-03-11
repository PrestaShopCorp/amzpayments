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

class AmazonTransactions
{

    public static function handleError($response)
    {
        $amz_paymentsObj = new AmzPayments();
        try {
            $responsearray = $response->toArray();
            if (isset($responsearray['Error']['Code']) && isset($responsearray['Error']['Message'])) {
                $amz_paymentsObj->exceptionLog($responsearray, 'ERROR: ' . $responsearray['Error']['Code'] . ': ' . $responsearray['Error']['Message']);
                return 'ERROR: ' . $responsearray['Error']['Code'] . ': ' . $responsearray['Error']['Message'];
            } elseif (isset($responsearray['Error']['Code'])) {
                $amz_paymentsObj->exceptionLog($responsearray, 'ERROR: ' . $responsearray['Error']['Code']);
                return 'ERROR: ' . $responsearray['Error']['Code'];
            }
        } catch (Exception $e) {
            $amz_paymentsObj->exceptionLog($e);
            return 'Unknown error';
        }
    }
    
    public static function getAuthorizeDetails(AmzPayments $amz_payments, $service, $auth_ref_id)
    {
        $requestParameters = array();
        $requestParameters['merchant_id'] = $amz_payments->merchant_id;
        $requestParameters['amazon_authorization_id'] = $auth_ref_id;
        
        $response = $service->getAuthorizationDetails($requestParameters);
        if ($service->success) {
            return $response->toArray();
        }
        return false;
    }

    public static function getRefundDetails(AmzPayments $amz_payments, $service, $refund_ref_id)
    {
        $requestParameters = array();
        $requestParameters['merchant_id'] = $amz_payments->merchant_id;
        $requestParameters['amazon_refund_id'] = $refund_ref_id;
        
        $response = $service->getRefundDetails($requestParameters);
        if ($service->success) {
            return $response->toArray();
        }
        return false;
    }

    public static function authorize(AmzPayments $amz_payments, $service, $order_ref, $amount, $currency_code = 'EUR', $timeout = 1440, $comment = '')
    {
        if ($currency_code == '0') {
            $currency_code = 'EUR';
        }
        $requestParameters = array();
        $requestParameters['merchant_id'] = $amz_payments->merchant_id;
        $requestParameters['amazon_order_reference_id'] = $order_ref;
        $requestParameters['authorization_amount'] = $amount;
        $requestParameters['currency_code'] = $currency_code;
        $requestParameters['authorization_reference_id'] = self::getNextAuthRef($order_ref);
        $requestParameters['seller_note'] = $comment != '' ? $comment : 'Authorizing payment';
        
        if ($amz_payments->capture_mode == 'after_auth') {
            $requestParameters['capture_now'] = true;
        }
        
        $requestParameters['transaction_timeout'] = $timeout;

        $response = $service->authorize($requestParameters);
        $responsearray = $response->toArray();
        
        if ($service->success) {
            $details = $responsearray['AuthorizeResult']['AuthorizationDetails'];
            $sql_arr = array(
                'amz_tx_order_reference' => pSQL($order_ref),
                'amz_tx_type' => 'auth',
                'amz_tx_time' => pSQL(time()),
                'amz_tx_expiration' => pSQL(strtotime($details['ExpirationTimestamp'])),
                'amz_tx_amount' => pSQL($amount),
                'amz_tx_status' => pSQL($details['AuthorizationStatus']['State']),
                'amz_tx_reference' => pSQL($details['AuthorizationReferenceId']),
                'amz_tx_amz_id' => pSQL($details['AmazonAuthorizationId']),
                'amz_tx_last_change' => pSQL(time()),
                'amz_tx_last_update' => pSQL(time())
            );

            Db::getInstance()->insert('amz_transactions', $sql_arr);
        } else {
            return self::handleError($response);
        }
        return $responsearray;
    }

    public static function refund(AmzPayments $amz_payments, $service, $capture_id, $amount, $currency_code = 'EUR')
    {
        $order_ref = self::getOrderRefFromAmzId($capture_id);
        
        $requestParameters = array();
        $requestParameters['merchant_id'] = $amz_payments->merchant_id;
        $requestParameters['amazon_capture_id'] = $capture_id;
        $requestParameters['refund_reference_id'] = self::getNextRefundRef($order_ref);
        $requestParameters['refund_amount'] = $amount;
        $requestParameters['currency_code'] = $currency_code;

        $response = $service->refund($requestParameters);
        $responsearray = $response->toArray();
        
        if ($service->success) {
            $details = $responsearray['RefundResult']['RefundDetails'];
            
            $sql_arr = array(
                'amz_tx_order_reference' => pSQL($order_ref),
                'amz_tx_type' => 'refund',
                'amz_tx_time' => pSQL(time()),
                'amz_tx_expiration' => 0,
                'amz_tx_amount' => pSQL($amount),
                'amz_tx_status' => pSQL($details['RefundStatus']['State']),
                'amz_tx_reference' => pSQL($details['RefundReferenceId']),
                'amz_tx_amz_id' => pSQL($details['AmazonRefundId']),
                'amz_tx_last_change' => pSQL(time()),
                'amz_tx_last_update' => pSQL(time()),
            );
            Db::getInstance()->insert('amz_transactions', $sql_arr);
        } else {
            return self::handleError($response);
        }
        return $responsearray;
    }

    public static function capture(AmzPayments $amz_payments, $service, $auth_id, $amount, $currency_code = 'EUR')
    {
        if ($auth_id) {
            $order_ref = self::getOrderRefFromAmzId($auth_id);
            
            $requestParameters = array();
            $requestParameters['merchant_id'] = $amz_payments->merchant_id;
            $requestParameters['amazon_order_reference_id'] = $order_ref;
            $requestParameters['amazon_authorization_id'] = $auth_id;
            $requestParameters['capture_amount'] = $amount;
            $requestParameters['currency_code'] = $currency_code;
            $requestParameters['capture_reference_id'] = self::getNextCaptureRef($order_ref);
            if ($amz_payments->provocation == 'capture_decline' && $amz_payments->environment == 'SANDBOX') {
                $requestParameters['seller_capture_note'] = '{"SandboxSimulation":{"State":"Declined", "ReasonCode":"AmazonRejected"}}';
            }
            
            $response = $service->capture($requestParameters);
            $responsearray = $response->toArray();
            
            if ($service->success) {
                $details = $responsearray['CaptureResult']['CaptureDetails'];
                $sql_arr = array(
                    'amz_tx_order_reference' => pSQL($order_ref),
                    'amz_tx_type' => 'capture',
                    'amz_tx_time' => pSQL(time()),
                    'amz_tx_expiration' => 0,
                    'amz_tx_amount' => pSQL($amount),
                    'amz_tx_status' => pSQL($details['CaptureStatus']['State']),
                    'amz_tx_reference' => pSQL($details['CaptureReferenceId']),
                    'amz_tx_amz_id' => pSQL($details['AmazonCaptureId']),
                    'amz_tx_last_change' => pSQL(time()),
                    'amz_tx_last_update' => pSQL(time())
                );
                Db::getInstance()->insert('amz_transactions', $sql_arr);
                
                self::setOrderStatusCapturedSuccesfully($order_ref);
            } else {
                return self::handleError($response);
            }
            return $responsearray;
        }
    }

    public static function closeOrder(AmzPayments $amz_payments, $service, $orderRef)
    {
        $requestParameter = array();
        $requestParameter['merchant_id'] = $amz_payments->merchant_id;
        $requestParameter['amazon_order_reference_id'] = $orderRef;
        $response = $service->closeOrderReference($requestParameter);
        if ($service->success) {
            return $response->toArray();
        } else {
            return self::handleError($response);
        }
        return false;
    }
    
    public static function cancelOrder(AmzPayments $amz_payments, $service, $orderRef)
    {
        $requestParameter = array();
        $requestParameter['merchant_id'] = $amz_payments->merchant_id;
        $requestParameter['amazon_order_reference_id'] = $orderRef;
        $response = $service->cancelOrderReference($requestParameter);
        if ($service->success) {
            return $response->toArray();
        } else {
            return self::handleError($response);
        }
        return false;
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
        if (is_array($response)) {
            if ($response['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['State'] != 'Open') {
                if ($response['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['State'] == 'Closed') {
                    if (!isset(Context::getContext()->cookie->amzSetStatusCaptured)) {
                        Context::getContext()->cookie->amzSetStatusCaptured = serialize(array());
                    }
                    $tmpData = Tools::unSerialize(Context::getContext()->cookie->amzSetStatusCaptured);
                    $tmpData[] = $order_ref;
                    Context::getContext()->cookie->amzSetStatusCaptured = serialize($tmpData);
                }
                return $response;
            }
            self::setOrderStatusAuthorized($order_ref);
        }
        return $response;
    }

    public static function setOrderStatusAuthorized($order_ref, $check = false)
    {
        $oid = self::getOrdersIdFromOrderRef($order_ref);
        if ((int)$oid > 0) {
            $amz_payments = new AmzPayments();
            $new_status = $amz_payments->authorized_status_id;
            if ($check) {
                $order = new Order((int)$oid);
                $history = $order->getHistory(Context::getContext()->language->id, $amz_payments->authorized_status_id);
                if (sizeof($history) > 0) {
                    return false;
                }
                if ($order->getCurrentState() == $amz_payments->authorized_status_id) {
                    return false;
                }
            }
            self::setOrderStatus($oid, $new_status);
        } else {
            if (!isset(Context::getContext()->cookie->amzSetStatusAuthorized)) {
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
        if ((int)$oid > 0) {
            $amz_payments = new AmzPayments();
            $new_status = $amz_payments->capture_success_status_id;
            $order = new Order((int)$oid);
            $history = $order->getHistory(Context::getContext()->language->id, $amz_payments->capture_success_status_id);
            if (sizeof($history) > 0) {
                return false;
            }
            if ($order->getCurrentState() == $amz_payments->capture_success_status_id) {
                return false;
            }
            self::setOrderStatus($oid, $new_status);
        } else {
            if (!isset(Context::getContext()->cookie->amzSetStatusCaptured)) {
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
        if ((int)$oid > 0) {
            $amz_payments = new AmzPayments();
            $new_status = $amz_payments->decline_status_id;
            if ($check) {
                $order = new Order((int)$oid);
                $history = $order->getHistory(Context::getContext()->language->id, $amz_payments->decline_status_id);
                if (sizeof($history) > 0) {
                    return false;
                }
                if ($order->getCurrentState() == $amz_payments->decline_status_id) {
                    return false;
                }
            }
            self::setOrderStatus($oid, $new_status);
        }
    }

    public static function setOrderStatusCapturedSuccesfully($order_ref)
    {
        $oid = self::getOrdersIdFromOrderRef($order_ref);
        if ((int)$oid > 0) {
            $amz_payments = new AmzPayments();
            $new_status = $amz_payments->capture_success_status_id;
            $order = new Order((int)$oid);
            $history = $order->getHistory(Context::getContext()->language->id, $amz_payments->capture_success_status_id);
            if (sizeof($history) > 0) {
                return false;
            }
            if ($order->getCurrentState() == $amz_payments->capture_success_status_id) {
                return false;
            }
            self::setOrderStatus($oid, $new_status);
        } else {
            if (!isset(Context::getContext()->cookie->amzSetStatusCaptured)) {
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
}
