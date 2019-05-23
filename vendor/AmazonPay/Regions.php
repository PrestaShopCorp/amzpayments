<?php
namespace AmazonPay;

/* Class Regions
 * Defines all Region specific properties
 */

class Regions
{
    public $mwsServiceUrls = array('eu' => 'mws-eu.amazonservices.com',
        'na' => 'mws.amazonservices.com',
        'jp' => 'mws.amazonservices.jp');
    
    public $profileEndpointUrls = array('uk' => 'amazon.co.uk',
        'us' => 'amazon.com',
        'de' => 'amazon.de',
        'at' => 'amazon.de',
        'fr' => 'amazon.de',
        'it' => 'amazon.de',
        'es' => 'amazon.de',
        'jp' => 'amazon.co.jp');
    
    public $regionMappings = array('de' => 'eu',
        'at' => 'eu',
        'fr' => 'eu',
        'it' => 'eu',
        'es' => 'eu',
        'uk' => 'eu',
        'us' => 'na',
        'jp' => 'jp');
}