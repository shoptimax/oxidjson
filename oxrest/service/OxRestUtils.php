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
 * @version 1.0.1
 */

use Tonic\Response;

/**
 * @uri /action/:action
 */
class OxRestUtils extends OxRestBase
{

    /**
     * @method GET
     * @json
     * @priority 2
     * @param string $action
     * @return Tonic\Response
     */
    public function getAction($action)
    {
        switch ($action) {
            case "checklogin":
                $ret = $this->_checkLogin();
                break;
            case "login":
                $ret = $this->_doLogin();
                break;
            case "logout":
                $ret = $this->_doLogout();
                break;
            default:
        }
        return new Response(200, $ret);
    }
    /**
     * @method POST
     * @json
     * @priority 1
     * @param string $action
     * @return Tonic\Response
     */
    public function postAction($action)
    {
        switch ($action) {
            case "checklogin":
                $ret = $this->_checkLogin();
                break;
            case "login":
                $ret = $this->_doLogin();
                break;
            case "logout":
                $ret = $this->_doLogout();
                break;
            default:
        }
        return new Response(200, $ret);
    }
    
    /**
     * Checklogin method
     * Doesn't do anything since our dispatcher does the
     * hard work when checking the access to the service :)
     * @see OxRestBase::setup()
     * @return bool
     */
    protected function _checkLogin()
    {
        $oSession = oxRegistry::getSession();
        if (!$oSession->getUser()) {
            $this->_doLog("No valid user!");
            return array("status" => "ERROR", "sid" => null);
        }
                
        return array("status" => "OK", "sid" => $oSession->getId());
    }
    /**
     * Login method
     * Doesn't do much since our dispatcher does the
     * hard work when checking the access to the service :)
     * @see OxRestBase::setup()
     * @return bool
     */
    protected function _doLogin()
    {
        $oSession = oxRegistry::getSession();
                
        return array("status" => "OK", "sid" => $oSession->getId());
    }
    /**
     * Logout method
     * @return bool
     */
    protected function _doLogout()
    {
        $oUser = oxNew('oxuser');

        if ($oUser->logout()) {
            return true;
        }
        return false;
    }
}
