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

function upgrade_module_2_0_14($module)
{
    Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amz_orders` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
                `id_order` int(11) NOT NULL,
				`amazon_auth_reference_id` varchar(255) NOT NULL,
				`amazon_authorization_id` varchar(255) NOT NULL,
				`amazon_order_reference_id` varchar(255) NOT NULL,
				`amazon_capture_id` varchar(255) NOT NULL,
				`amazon_capture_reference_id` varchar(255) NOT NULL,
				PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
				');
    
    Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amz_address` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
                `id_address` int(11) NOT NULL,
				`amazon_order_reference_id` varchar(255) NOT NULL,
				PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
				');
    
    Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amz_customer` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
                `id_customer` int(11) NOT NULL,
				`amazon_customer_id` varchar(255) NOT NULL,
				PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
				');
    return true;
}
