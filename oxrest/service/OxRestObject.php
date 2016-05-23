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
 * @uri /oxobject/:class/:oxid
 */
class OxRestObject extends OxRestBase
{
    /**
     * @method GET
     * @json
     * @param string $class
     * @param string $oxid
     * @return Tonic\Response
     */
    public function returnOxObject($class = null, $oxid = null)
    {
        try {
            /** @var oxBase $o */
            $o = oxNew($class);
            $o->disableLazyLoading();
            if ($o->load($oxid)) {
                return new Response(200, $this->_oxObject2Array($o));
            }
        } catch (Exception $ex) {

        }

        return new Response(404, "Not found");
    }
    
    /**
     * Saving object data back to DB
     * @method PUT
     * @json
     * @hasRwAccessRights
     * @param string $class
     * @priority 3
     * @return Tonic\Response
     */
    public function saveObject($class)
    {
        try {
            // check for data in request
            if ($this->request->data) {
                $aData = $this->request->data;
                // convert stdObj to array
                $aObjData = $this->_objectToArray($aData);
                // get OXID and create new object by cloning
                $sOxid = $aObjData['oxid'];
                $oxObj = oxNew($class);
                if ($oxObj->load($sOxid)) {
                    // assign new array data
                    $oxObj->assign($aObjData);
                    // save object
                    $oxObj->save();
                }
            }
            return new Response(200, $this->_oxObject2Array($oxObj));
        } catch (Exception $ex) {
            $this->_doLog("Error saving object: " . $ex->getMessage());
        }
        return new Response(500);
    }

    /**
     * Add a new object to DB
     * @method POST
     * @json
     * @hasRwAccessRights
     * @param string $class
     * @priority 4
     * @return Tonic\Response
     */
    public function addObject($class)
    {
        try {
            // check for data in request
            if ($this->request->data) {
                $aData = $this->request->data;
                // convert stdObj to array
                $aObjData = $this->_objectToArray($aData);
                // get OXID and create new object by cloning
                $sOxid = $aObjData['oxid'];
                $oxObj = oxNew($class);
                if (!isset($sOxid) || $sOxid == '') {
                    // create new OXID
                    $sOxid = oxUtilsObject::getInstance()->generateUId();
                } else {
                    // object id must be new!
                    if ($oxObj->load($sOxid)) {
                        return  new Response(500, "Object exists!");
                    }
                }
                // assign new array data
                $oxObj->assign($aObjData);
                // save object
                $oxObj->save();
            }
            return new Response(200, $this->_oxObject2Array($oxObj));
        } catch (Exception $ex) {
            $this->_doLog("Error saving new object: " . $ex->getMessage());
        }
        return new Response(500);
    }
    
    /**
     * @method DELETE
     * @json
     * @hasRwAccessRights
     * @param string $class
     * @param string $oxid
     * @return Tonic\Response
     */
    public function deleteObject($class = null, $oxid = null)
    {
        try {
            /** @var oxBase $o */
            $o = oxNew($class);
            if ($o->load($oxid)) {
                if ($o->delete()) {
                    return new Response(200, "OK");
                }
            }
        } catch (Exception $ex) {

        }
        return new Response(404);
    }
}
