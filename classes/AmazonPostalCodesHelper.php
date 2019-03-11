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

class AmazonPostalCodesHelper
{
    
    public static function getIdByPostalCodeAndCountry($postcode, $iso_code)
    {
        if ($iso_code == 'IT') {
            $province = self::getItalianProvince($postcode);
            if ($province) {
                return State::getIdByName($province);
            }
        } elseif ($iso_code == 'ES') {
            $province = self::getSpanishProvince($postcode);
            if ($province) {
                return State::getIdByName($province);
            }
        }
        return false;
    }
    
    
    /**
     * @param string PostalCode $pc
     */
    public static function getItalianProvince($pc)
    {
        $pc = (int)$pc;
        if ($pc >= 15121 && $pc <= 15122) {
            return 'Alessandria';
        } elseif ($pc >= 60121 && $pc <= 60131) {
            return 'Acona';
        } elseif ($pc == 11100) {
            return 'Aosta';
        } elseif ($pc == 52100) {
            return 'Arezzo';
        } elseif ($pc == 63100) {
            return 'Ascoli Piceno';
        } elseif ($pc == 14100) {
            return 'Asti';
        } elseif ($pc == 83100) {
            return 'Avellino';
        } elseif ($pc >= 70121 && $pc <= 70132) {
            return 'Bari';
        } elseif (in_array($pc, array(76123,76011,76016,76017,76125,76121,76012,76013,76014,76015))) {
            return 'Barletta-Andria-Trani';
        } elseif ($pc == 32100) {
            return 'Belluno';
        } elseif ($pc == 82100) {
            return 'Beneveto';
        } elseif ($pc >= 24121 && $pc <= 24129) {
            return 'Bergamo';
        } elseif ($pc == 13900) {
            return 'Biella';
        } elseif ($pc >= 40121 && $pc <= 40141) {
            return 'Bologna';
        } elseif ($pc == 39100) {
            return 'Bolzano';
        } elseif ($pc >= 25121 && $pc <= 25136) {
            return 'Brescia';
        } elseif ($pc == 72100) {
            return 'Brindisi';
        } elseif ($pc >= /*0*/9121 && $pc <= /*0*/9134) {
            return 'Cagliari';
        } elseif ($pc == 93100) {
            return 'Caltanissetta';
        } elseif ($pc == 86100) {
            return 'Campobasso';
        } elseif ($pc == /*0*/9013) {
            return 'Carbonia-Iglesias';
        } elseif ($pc == 81100) {
            return 'Caserta';
        } elseif ($pc >= 95121 && $pc <= 95131) {
            return 'Catania';
        } elseif ($pc == 88100) {
            return 'Catanzaro';
        } elseif ($pc == 66100) {
            return 'Chieti';
        } elseif ($pc == 22100) {
            return 'Como';
        } elseif ($pc == 87100) {
            return 'Cosenza';
        } elseif ($pc == 26100) {
            return 'Cremona';
        } elseif ($pc == 88900) {
            return 'Crontone';
        } elseif ($pc == 12100) {
            return 'Cuneo';
        } elseif ($pc == 94100) {
            return 'Enna';
        } elseif ($pc == 63900) {
            return 'Fermo';
        } elseif ($pc >= 44121 && $pc <= 44124) {
            return 'Ferrara';
        } elseif ($pc >= 50121 && $pc <= 50145) {
            return 'Firenze';
        } elseif ($pc >= 71121 && $pc <= 71122) {
            return 'Foggia';
        } elseif (in_array($pc, array(47121,47122,47021,47032,47030,47011,47521,47522,47042,47012,47013,47034,47010,47035,47043,47020,47014,47025,47015,47020,47010,47016,47017,47030,47018,47027,47039,47019,47028))) {
            return 'Forli-Cesena';
        } elseif ($pc == /*0*/3100) {
            return 'Frosinone';
        } elseif ($pc >= 16121 && $pc <= 16167) {
            return 'Genova';
        } elseif ($pc == 34170) {
            return 'Gorizia';
        } elseif ($pc == 58100) {
            return 'Grosetto';
        } elseif ($pc == 18100) {
            return 'Imperia';
        } elseif ($pc == 86170) {
            return 'Isernia';
        } elseif ($pc == 67100) {
            return 'L\'Aquila';
        } elseif ($pc >= 19121 && $pc <= 19137) {
            return 'La Spezia';
        } elseif ($pc == /*0*/4100) {
            return 'Latina';
        } elseif ($pc == 73100) {
            return 'Lecce';
        } elseif ($pc == 23900) {
            return 'Lecco';
        } elseif ($pc >= 57121 && $pc <= 57128) {
            return 'Livorno';
        } elseif ($pc == 26900) {
            return 'Lodi';
        } elseif ($pc == 55100) {
            return 'Lucca';
        } elseif ($pc == 62100) {
            return 'Macerata';
        } elseif ($pc == 46100) {
            return 'Mantova';
        } elseif ($pc == 54100) {
            return 'Massa';
        } elseif ($pc == 75100) {
            return 'Matera';
        } elseif (in_array($pc, array(/*0*/9020,/*0*/9021,/*0*/9022,/*0*/9025,/*0*/9027,/*0*/9029,/*0*/9030,/*0*/9031,/*0*/9035,/*0*/9036,/*0*/9037,/*0*/9038,/*0*/9039,/*0*/9040))) {
            return 'Medio Campidano';
        } elseif ($pc >= 98121 && $pc <= 98168) {
            return 'Messina';
        } elseif ($pc >= 20121 && $pc <= 20162) {
            return 'Milano';
        } elseif ($pc >= 41121 && $pc <= 41126) {
            return 'Modena';
        } elseif ($pc == 20900) {
            return 'Monza e della Brianza';
        } elseif ($pc >= 80121 && $pc <= 80147) {
            return 'Napoli';
        } elseif ($pc == 28100) {
            return 'Novara';
        } elseif ($pc == /*0*/8100) {
            return 'Nuoro';
        } elseif ($pc == 84061) {
            return 'Ogliastra';
        } elseif ($pc == /*0*/7026) {
            return 'Olbia-Tempio';
        } elseif ($pc == /*0*/9170) {
            return 'Oristano';
        } elseif ($pc >= 35121 && $pc <= 35143) {
            return 'Padova';
        } elseif ($pc >= 90121 && $pc <= 90151) {
            return 'Palermo';
        } elseif ($pc >= 43121 && $pc <= 43126) {
            return 'Parma';
        } elseif ($pc == 27100) {
            return 'Pavia';
        } elseif ($pc >= /*0*/6121 && $pc <= /*0*/6135) {
            return 'Perugia';
        } elseif ($pc >= 61121 && $pc <= 61122) {
            return 'Pesaro-Urbino';
        } elseif ($pc >= 65121 && $pc <= 65129) {
            return 'Pescara';
        } elseif ($pc >= 29121 && $pc <= 29122) {
            return 'Piacenza';
        } elseif ($pc >= 56121 && $pc <= 56128) {
            return 'Pisa';
        } elseif ($pc == 51100) {
            return 'Pistoia';
        } elseif ($pc == 33170) {
            return 'Pordenone';
        } elseif ($pc == 85100) {
            return 'Potenza';
        } elseif ($pc == 59100) {
            return 'Prato';
        } elseif ($pc == 97100) {
            return 'Ragusa';
        } elseif ($pc >= 48121 && $pc <= 48125) {
            return 'Ravenna';
        } elseif ($pc >= 89121 && $pc <= 89135) {
            return 'Reggio Calabria';
        } elseif ($pc >= 42121 && $pc <= 42124) {
            return 'Reggio Emilia';
        } elseif ($pc == /*0*/2100) {
            return 'Rieti';
        } elseif ($pc >= 47921 && $pc <= 47924) {
            return 'Rimini';
        } elseif ($pc >= /*00*/118 && $pc <= /*00*/199) {
            return 'Roma';
        } elseif ($pc == 45100) {
            return 'Rovigo';
        } elseif ($pc >= 84121 && $pc <= 84135) {
            return 'Salerno';
        } elseif ($pc == /*0*/7100) {
            return 'Sassari';
        } elseif ($pc == 17100) {
            return 'Savona';
        } elseif ($pc == 53100) {
            return 'Siena';
        } elseif ($pc == 96100) {
            return 'Siracusa';
        } elseif ($pc == 23100) {
            return 'Sondrio';
        } elseif ($pc >= 74121 && $pc <= 74123) {
            return 'Taranto';
        } elseif ($pc == 64100) {
            return 'Teramo';
        } elseif ($pc == /*0*/5100) {
            return 'Terni';
        } elseif ($pc >= 10121 && $pc <= 10156) {
            return 'Torino';
        } elseif ($pc == 91100) {
            return 'Trapani';
        } elseif ($pc >= 38121 && $pc <= 38123) {
            return 'Trento';
        } elseif ($pc == 31100) {
            return 'Treviso';
        } elseif ($pc >= 34121 && $pc <= 34151) {
            return 'Trieste';
        } elseif ($pc == 33100) {
            return 'Udine';
        } elseif ($pc == 21100) {
            return 'Varese';
        } elseif ($pc >= 30121 && $pc <= 30176) {
            return 'Venezia';
        } elseif (in_array($pc, array(28921,28922,28923,28924,28925,28877,28899,28861,28831,28832,28842,28833,28814,28822,28881,28875,28801,28865,28827,28853,28863,28823,28883,28816,28876,28854,28895,28817,28843,28824,28877,28885,28818,28803,28896,28804,28838,28826,28859,28879,28819,
            28856,28841,28813,28851,28846,28873,28821,28815,28825,28891,28852,28862,28845,28827,28887,28836,28828,28893,28894,28855,28802,28864,28891,28887,28884,28886,28866,28898,28856,28857,58858,28868,28897,28868,28844,28805)) ) {
                return 'Verbano-Cusio-Ossola';
        } elseif ($pc == 13100) {
            return 'Vercelli';
        } elseif ($pc >= 37121 && $pc <= 37142) {
            return 'Verona';
        } elseif ($pc == 89900) {
            return 'Vibo Valentia';
        } elseif ($pc == 36100) {
            return 'Vicenza';
        } elseif ($pc == /*0*/1100) {
            return 'Viterbo';
        }
        return false;
    }
    
    /**
     * @param string PostalCode $pc
     */
    public static function getSpanishProvince($pc)
    {
        $pc = Tools::substr($pc, 0, 2);
        switch ($pc) {
            case '01':
                return 'Álava';
            case '02':
                return 'Albacete';
            case '03':
                return 'Alacant';
            case '04':
                return 'Almería';
            case '33':
                return 'Asturias';
            case '05':
                return 'Ávila';
            case '06':
                return 'Badajoz';
            case '07':
                return 'Balears';
            case '08':
                return 'Barcelona';
            case '09':
                return 'Burgos';
            case '10':
                return 'Cáceres';
            case '11':
                return 'Cádiz';
            case '39':
                return 'Cantabria';
            case '12':
                return 'Castelló';
            case '13':
                return 'Ciudad Real';
            case '14':
                return 'Córdoba';
            case '16':
                return 'Cuenca';
            case '17':
                return 'Girona';
            case '18':
                return 'Granada';
            case '19':
                return 'Guadalajara';
            case '20':
                return 'Gipuzkoa';
            case '21':
                return 'Huelva';
            case '22':
                return 'Huesca';
            case '23':
                return 'Jaén';
            case '26':
                return 'La Rioja';
            case '35':
                return 'Las Palmas';
            case '24':
                return 'León';
            case '25':
                return 'Lleida';
            case '27':
                return 'Lugo';
            case '28':
                return 'Madrid';
            case '29':
                return 'Málaga';
            case '30':
                return 'Murcia';
            case '31':
                return 'Nafarroa';
            case '32':
                return 'Ourense';
            case '34':
                return 'Palencia';
            case '36':
                return 'Pontevedra';
            case '37':
                return 'Salamanca';
            case '38':
                return 'Santa Cruz de Tenerife';
            case '40':
                return 'Segovia';
            case '41':
                return 'Sevilla';
            case '42':
                return 'Soria';
            case '43':
                return 'Tarragona';
            case '44':
                return 'Teruel';
            case '45':
                return 'Toledo';
            case '46':
                return 'València';
            case '47':
                return 'Valladolid';
            case '48':
                return 'Bizkaia';
            case '49':
                return 'Zamora';
            case '50':
                return 'Zaragoza';
            case '51':
                return 'Ceuta';
            case '52':
                return 'Melilla';
        }
        return false;
    }
}
