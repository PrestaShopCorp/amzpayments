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

class AmazonPaymentsHelperForm extends HelperForm
{
    
    public function generateAmazonForm(&$smarty, $fields_form)
    {
        $this->fields_form = $fields_form;
        $base_generate = $this->generate();
        $smarty->assign('form_vars', $this->tpl->getTemplateVars());
        return $base_generate;
    }
    
    public function generate()
    {
        return parent::generate();
    }
}
