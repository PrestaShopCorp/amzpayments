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

use Symfony\Component\Translation\TranslatorInterface;

class CustomerFormatterAmazonPayPersonalData extends CustomerFormatter
{
    
    public function __construct(TranslatorInterface $translator, Language $language)
    {
        $this->translator = $translator;
        $this->language = $language;
    }
    
    public function getFormat()
    {
        $format = array();
        
        $format['id_customer'] = (new FormField)
            ->setName('id_customer')
            ->setType('hidden')
        ;
        
        $format['email'] = (new FormField)
            ->setName('email')
            ->setType('hidden')
        ;
            
        $format['firstname'] = (new FormField)
            ->setName('firstname')
            ->setLabel(
                $this->translator->trans(
                    'First name',
                    array(),
                    'Shop.Forms.Labels'
                )
            )
            ->setRequired(true)
        ;
            
        $format['lastname'] = (new FormField)
            ->setName('lastname')
            ->setLabel(
                $this->translator->trans(
                    'Last name',
                    array(),
                    'Shop.Forms.Labels'
                )
            )
            ->setRequired(true)
        ;
        return $format;
    }
}
