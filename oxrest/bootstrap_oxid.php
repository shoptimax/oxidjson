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
 * This file is only used for OXID < 4.7 / 5.0 where no bootstrapping and no oxRegistry are 
 * available. We include oxRegistry here to avoid OXID version distinctions in the REST classes.
 */
if(!function_exists('getShopBasePath')) {
    function getShopBasePath()
    {
        return dirname(__FILE__) . '/../';
    }
}
require_once( getShopBasePath() . "core/oxsupercfg.php");
require_once( getShopBasePath() . "modules/functions.php");
require_once( getShopBasePath() . "core/oxfunctions.php");
require_once( getShopBasePath() . "core/adodblite/adodb.inc.php");
require_once( getShopBasePath() . "core/oxutilsfile.php");
require_once( getShopBasePath() . "core/oxconfig.php");
require_once( oxConfig::getInstance()->getConfigParam('sCoreDir') . "oxutils.php");

/**
 * Object registry design pattern implementation. Stores the instances of objects
 */
class oxRegistry
{
    /**
     * Instance array
     *
     * @var array
     */
    protected static $_aInstances = array();

    /**
     * Instance getter. Return existing instance or initializes the new one
     *
     * @param string $sClassName Class name
     *
     * @static
     *
     * @return Object
     */
    public static function get( $sClassName )
    {
        $sClassName = strtolower( $sClassName );
        if ( isset( self::$_aInstances[$sClassName] ) ) {
            return self::$_aInstances[$sClassName];
        } else {
            self::$_aInstances[$sClassName] = oxNew( $sClassName );
            return self::$_aInstances[$sClassName];
        }
    }

    /**
     * Instance setter
     *
     * @param string $sClassName Class name
     * @param object $oInstance  Object instance
     *
     * @static
     *
     * @return null
     */
    public static function set( $sClassName, $oInstance )
    {
        $sClassName = strtolower( $sClassName );

        if ( is_null( $oInstance ) ) {
            unset( self::$_aInstances[$sClassName] );
            return;
        }

        self::$_aInstances[$sClassName] = $oInstance;
    }

    /**
     * Returns OxConfig instance
     *
     * @static
     *
     * @return OxConfig
     */
    public static function getConfig()
    {
        return self::get( "oxConfig" );
    }

    /**
     * Returns OxSession instance
     *
     * @static
     *
     * @return OxSession
     */
    public static function getSession()
    {
        return self::get( "oxSession" );
    }

    /**
     * Returns oxLang instance
     *
     * @static
     *
     * @return oxLang
     */
    public static function getLang()
    {
        return self::get("oxLang");
    }

    /**
     * Returns oxUtils instance
     *
     * @static
     *
     * @return oxUtils
     */
    public static function getUtils()
    {
        return self::get("oxUtils");
    }

    /**
     * Return set instances.
     *
     * @return array
     */
    public static function getKeys()
    {
        return array_keys( self::$_aInstances );
    }
}