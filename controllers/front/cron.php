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
 *  @copyright 2013-2017 patworx multimedia GmbH
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class AmzpaymentsCronModuleFrontController extends ModuleFrontController
{
    
    public $ssl = true;
    
    public $isLogged = false;
    
    public $display_column_left = false;
    
    public $display_column_right = false;
    
    public function __construct()
    {
        $this->controller_type = 'modulefront';
        $this->module = \Module::getInstanceByName(\Tools::getValue('module'));
        if (! $this->module->active) {
            \Tools::redirect('index');
        }
        
        $module_name = \Tools::getValue('moduleName');
        
        $amz_payments = new AmzPayments();
        
        if ($amz_payments->cron_status == '1' && \Tools::getValue('pw') == $amz_payments->cron_password) {
            if ($amz_payments->capture_mode == 'after_shipping') {
                $amz_payments->shippingCapture();
            }
            
            $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions WHERE amz_tx_status = \'Pending\' ORDER BY amz_tx_last_update ASC';
            $rs = \Db::getInstance()->ExecuteS($q);
            foreach ($rs as $r) {
                $amz_payments->intelligentRefresh($r);
                sleep(1);
            }
            
            $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'amz_transactions WHERE amz_tx_status != \'Closed\' AND amz_tx_status != \'Completed\' AND amz_tx_status != \'Declined\' ORDER BY amz_tx_last_update ASC LIMIT 40';
            $rs = \Db::getInstance()->ExecuteS($q);
            foreach ($rs as $r) {
                $amz_payments->intelligentRefresh($r);
                sleep(1);
            }
            echo 'COMPLETED';
        }
        
        exit();
    }
}
