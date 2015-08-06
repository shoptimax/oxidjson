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
 * @copyright (C) shoptimax GmbH 2013-2016
 * @version 1.1.0
 */

use Tonic\Resource;

/**
 * Class OxRestBase
 */
class OxRestBase extends Resource {

    /**
     * @var bool Activate logging
     */
    protected $_debug = true;
    
    /**
     * @var oxUser Logged in user.
     */
    protected $_oUser = null;


    /**
     * @throws Tonic\UnauthorizedException
     * @return null
     */
    public function setup()
    {
        $oSession = oxRegistry::getSession();
        $oUtilsServer = oxRegistry::get('oxUtilsServer');
        $oUser = oxNew('oxuser');
        // user already signed in?
        if ($oUser->loadActiveUser() && $this->hasValidLogin($oUser)) {
            // set user
            $this->_oUser = $oUser;
            return;
        }
        // get auth header and check login data submitted
        $authHeader = $oUtilsServer->getServerVar('HTTP_AUTHORIZATION');
        if (!isset($authHeader) || '' === $authHeader) {
            $authHeader = $oUtilsServer->getServerVar('REDIRECT_HTTP_AUTHORIZATION');
        }
        if ($authHeader) {
            $this->_doLog("Auth header: " . $authHeader);
        }
        // Auth comes as "Ox <base64_encoded username:password>"...
        // So strip the "Ox " prefix, base64_decode the remaining string
        // and split username and password at the ":" char
        $userNamePassword = explode(":", base64_decode(substr($authHeader, 3)));
        if (!is_array($userNamePassword) || count($userNamePassword) < 2) {
            throw new Tonic\UnauthorizedException;
        }
        $sUser = $userNamePassword[0];
        $sPassword = $userNamePassword[1];
        try {
            if (!$oSession->isSessionStarted()) {
                $oSession->start();
            }
            if ($oUser->login($sUser, $sPassword)) {
                // set user
                $this->_oUser = $oUser;
                // check groups
                if (!$this->hasValidLogin()) {
                    $this->_doLog("Login NOT successful for user $sUser");
                    throw new Tonic\UnauthorizedException;
                } else {
                    $this->_doLog("Login successful for user $sUser - " . $oSession->getVariable('usr'));
                    $oSession->regenerateSessionId();
                }
            }
        } catch (Exception $ex) {
            $this->_doLog("Login NOT successful for user $sUser, Error: " . $ex->getMessage());
            throw new Tonic\UnauthorizedException;
        }
    }

    /**
     * Is the user authorized to use OXJSON?
     * @param oxUser $oUser
     * @return boolean
     */
    protected function hasValidLogin($oUser = null)
    {
        if (!$this->_oUser && !$oUser) {
            return false;
        }
        if (!$oUser) {
            $oUser = $this->_oUser;
        }
        $isAdmin = $oUser->inGroup('oxidadmin');
        $isRoUser = $oUser->inGroup('oxjsonro');
        $isFullUser = $oUser->inGroup('oxjsonfull');
        if ($isAdmin || $isRoUser || $isFullUser) {
            return true;
        }
        return false;
    }
    
    /**
     * Is the user authorized for full access (CRUD)?
     * @return boolean
     */
    protected function hasFullAccess()
    {
        if (!$this->_oUser) {
            return false;
        }
        $isAdmin = $this->_oUser->inGroup('oxidadmin');
        $isFullUser = $this->_oUser->inGroup('oxjsonfull');
        if ($isAdmin || $isFullUser) {
            return true;
        }
        return false;
    }
    
    /**
     * Check access rights for POST, PUT, DELETE
     * @throws Tonic\ConditionException
     */
    public function hasRwAccessRights()
    {
        if (!$this->hasFullAccess()) {
            throw new Tonic\ConditionException;
        }
    }

    /**
     * Condition method to turn output into JSON.
     *
     * This condition sets a before and an after filter for the request and response. The
     * before filter decodes the request body if the request content type is JSON, while the
     * after filter encodes the response body into JSON.
     */
    protected function json()
    {
        $this->before(function ($request) {
            if ($request->contentType == "application/json") {
                $request->data = json_decode($request->data);
            }
        });
        $this->after(function ($response) {
            $response->contentType = "application/json";
            if (isset($_GET['jsonp'])) {
                $response->body = $_GET['jsonp'] . '(' . json_encode($response->body) . ');';
            } else {
                $response->body = json_encode($response->body);
            }
        });
    }

    /**
     * Convert an OXID object to an array
     * Hide some specific fields, for now password and passsalt of oxusers
     * @param object $o
     * @return array
     */
    protected function _oxObject2Array($o)
    {
        $vars = get_object_vars($o);
        $a = array();
        foreach ($vars as $key => $value) {

            $vars = get_object_vars($o);
            foreach ($vars as $key => $value) {
                if (($pos = strpos($key, '__')) > 0) {
                    $key = substr($key, $pos + 2);
                    if ($this->_keyIsBlacklisted($key)) {
                        continue;
                    }
                    $value = $value->getRawValue();
                    $a[$key] = $value;
                }
            }
        }
        return $a;
    }

    /**
     * Check if a certain object key is blacklisted, e.g. password fields
     *
     * @param string $sKey
     * @return bool
     */
    protected function _keyIsBlacklisted($sKey)
    {
        $aBlacklisted = oxRegistry::getConfig()->getShopConfVar('aOxidJsonBlacklistKeys');
        if ($aBlacklisted && is_array($aBlacklisted)) {
            foreach ($aBlacklisted as $sTerm) {
                if (strpos($sKey, $sTerm) > 0) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Convert stdClass to array
     * @param object $d
     * @return array
     */
    protected function _objectToArray($d)
    {
        if (is_object($d)) {
            // Gets the properties of the given object
            // with get_object_vars function
            $d = get_object_vars($d);
        }

        if (is_array($d)) {
            // for recursive call
            return array_map(array('oxRestBase','_objectToArray'), $d);
        } else {
            // return filtered array
            foreach ($d as $k => $v) {
                $k = strtolower($k);
                if ($this->_keyIsBlacklisted($k)) {
                    unset($d[$k]);
                }
            }
            return $d;
        }
    }
    
    /**
     * General logging function
     * @param string $msg
     * @param string $filename
     */
    protected function _doLog($msg, $filename = "rest.log")
    {
        if ($this->_debug) {
            oxRegistry::getUtils()->writeToLog(date("Y-m-d H:i:s") . " " . $msg . "\n", $filename);
        }
    }
}
