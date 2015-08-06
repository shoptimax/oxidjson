<?php
/**
 *    This file is part of OXIDJson.
 *
 *    OXIDJson is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    OXIDJson is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this package.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.shoptimax.de
 * @package   oxjson
 * @copyright (C) shoptimax GmbH 2013
 * @version 1.0.0
 */
/**
 * Metadata version
 */
$sMetadataVersion = '1.1';
 
/**
 * Module information
 */
$aModule = array(
    'id'           => 'oxjson',
    'title'        => 'OXJSON',
    'description'  => array(
        'de' => 'OXJSON - generisches JSON Modul f&uuml;r OXID mit AngularJS Frontend.',
        'en' => 'OXJSON - generic JSON module for OXID with AngularJS frontend.',
    ),
    'thumbnail'    => 'logo.png',
    'version'      => '1.1.0',
    'author'       => 'shoptimax GmbH',
    'url'          => 'http://www.shoptimax.de/',
    'email'        => 'support@shoptimax.de',
    'extend'       => array(
    ),
    'files' => array(
        'oxjson_setup'      => 'oxjson/oxjson_setup.php',
    ),
    'blocks' => array(
    ),
    'events' => array(
        'onActivate' => 'oxjson_setup::onActivate',
    ),
   'settings' => array(
       array('group' => 'oxidjson', 'name' => 'aOxidJsonBlacklistKeys', 'type' => 'arr',  'value' => array('password', 'passsalt')),
    )
);
?>
