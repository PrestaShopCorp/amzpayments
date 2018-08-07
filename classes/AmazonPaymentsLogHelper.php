<?php
/**
 * 2013-2018 Amazon Advanced Payment APIs Modul
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
 *  @copyright 2013-2018 patworx multimedia GmbH
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class AmazonPaymentsLogHelper
{

    public static function generateAndSendLogfile(AmzPayments $amzpayments)
    {
        $debuginfo = array(
            'PrestaShop Version' => _PS_VERSION_,
            'PrestaShop URL' => _PS_BASE_URL_,
            'PrestaShop URL SSL' => _PS_BASE_URL_SSL_,
            'SSL State' => Configuration::get('PS_SSL_ENABLED') ? '1' : '0',
            'Plugin Version' => $amzpayments->version,
            'Configuration options' => $amzpayments->getConfigFormValuesForDebug(),
            'SimplePathData' => $amzpayments->getSimplePathData(),
        );
                
        $logdir = CURRENT_AMZ_MODULE_DIR . '/logs';
        if (!is_dir($logdir)) {
            mkdir($logdir);
        }
        $newfilename = 'amazonpay_log_' . date("Y-m-d_His") . '.log';
        if (file_exists(CURRENT_AMZ_MODULE_DIR . '/amz_exception.log')) {
            Tools::copy(CURRENT_AMZ_MODULE_DIR . '/amz_exception.log', $logdir . '/' . $newfilename);
        }
        
        $debuginfo = "\r\n\r\n" . "DEBUG INFO: " . "\r\n\r\n" . print_r($debuginfo, true);
        
        file_put_contents($logdir . '/' . $newfilename, $debuginfo, FILE_APPEND);
        header('Content-Type: application/download');
        header('Content-Disposition: attachment; filename="' . $newfilename . '"');
        header("Content-Length: " . filesize($logdir . '/' . $newfilename));
        
        readfile($logdir . '/' . $newfilename);
        exit();
    }
}
