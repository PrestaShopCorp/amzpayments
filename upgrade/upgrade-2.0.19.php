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

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_0_19($module)
{
    $config_keys = array('BUTTON_VISIBILITY', 'POPUP', 'ALLOW_GUEST', 'IPN_STATUS', 'CRON_STATUS', 'SEND_MAILS_ON_DECLINE', 'PRESELECT_CREATE_ACCOUNT', 'FORCE_ACCOUNT_CREATION');
    foreach ($config_keys as $config_key) {
        $old_value = Configuration::get($config_key);
        if ($old_value == '') {
            Configuration::updateValue($config_key, '0');
        }
    }
    return true;
}
