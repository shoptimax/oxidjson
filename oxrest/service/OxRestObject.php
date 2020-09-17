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
 * @version 1.1.1
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
            $sLoadFunc = "load";
            switch ($class) {
                case 'oxnewssubscribed':
                    // for newsletter, we select by user id, not by oxid
                    $sLoadFunc = "loadFromUserId";
                    break;
            }
            if ($o->$sLoadFunc($oxid)) {
                $aObject = $this->_oxObject2Array($o);
                // enrich data?
                switch (strtolower($class)) {
                    case 'oxuser':
                        // get addresses, too
                        /** @var oxUserAddressList $oAddresses */
                        $oAddresses = $o->getUserAddresses($oxid);
                        $aAddresses = array();
                        if ($oAddresses) {
                            foreach ($oAddresses->getArray() as $oData) {
                                $aAddresses[] = $this->_oxObject2Array($oData);
                            }
                        }
                        // add addresses as special array value
                        $aObject['_oxaddress'] = $aAddresses;
                        break;
                }
                $jsonEncode = json_encode($aObject);

                if(false === $jsonEncode) {

                    $jsonErrormsg = json_last_error_msg();
                    return new Response(404, "jsonErrormsg: ". $jsonErrormsg);
                }

                return new Response(200, $jsonEncode, array('content-type' => 'application/json'));
            }
        } catch (Exception $ex) {

        }

        return new Response(404, "Not found");
    }

    /**
     * Saving object data back to DB
     * @method PUT
     * @json
     * @provides application/json
     * @hasRwAccessRights
     * @param string $class
     * @priority 3
     * @return Tonic\Response
     */
    public function saveObject($class)
    {
        try {
            // check for data in request
            if ($this->request->data && $this->request->data != "") {
                $aAddonData = null;
                $aData = $this->request->data;

                if (false == is_array($aData)) {
                    $aData = json_decode($aData);
                }

                // convert stdObj to array
                $aObjData = $this->_objectToArray($aData);

                foreach ($aObjData as $k => $v) {
                    if (substr($k, 0, 1) === "_") {
                        $aAddonData[substr($k, 1)] = $v;
                        unset($aObjData[$k]);
                    }
                }
                // get OXID and create new object by cloning
                $sOxid = $aObjData['oxid'];
                $oxObj = oxNew($class);
                if ($oxObj->load($sOxid)) {
                    // assign new array data
                    $oxObj->assign($aObjData);
                    // save object
                    $oxObj->save();
                    // re-load to refresh data
                    if ($oxObj->load($sOxid)) {
                        $aObject = $this->_oxObject2Array($oxObj);

                        // enriched data, e.g. addresses for oxuser?
                        if ($aAddonData && is_array($aAddonData)) {
                            foreach ($aAddonData as $objName => $objData) {
                                $oxAddObj = oxNew($objName);
                                // this "enriched" data comes as array, too
                                if (is_array($objData)) {
                                    foreach ($objData as $idx => $data) {
                                        $addOxid = $data['oxid'];
                                        // no OXID, create new object
                                        if (!isset($addOxid) || $addOxid == '') {
                                            // create new OXID
                                            $addOxid = \OxidEsales\Eshop\Core\Registry::getUtilsObject()->generateUID();
                                            $data['oxid'] = $addOxid;
                                            // assign new array data
                                            $oxAddObj->assign($data);
                                            // save object
                                            $oxAddObj->save();
                                        } else {
                                            if ($oxAddObj->load($addOxid)) {
                                                // assign new array data
                                                $oxAddObj->assign($data);
                                                // save object
                                                $oxAddObj->save();
                                            }
                                        }
                                        // reload object
                                        if ($oxAddObj->load($addOxid)) {
                                            $aObject["_" . $objName][] = $this->_oxObject2Array($oxAddObj);
                                        }
                                    }
                                }
                            }
                        }

                        $jsonEncode = json_encode($aObject);

                        if(false === $jsonEncode) {

                            $jsonErrormsg = json_last_error_msg();
                            return new Response(404, "jsonErrormsg: ". $jsonErrormsg);
                        }

                        return new Response(200, $jsonEncode, array('content-type' => 'application/json'));
                    }
                }
            }
        } catch (Exception $ex) {
            $this->_doLog("Error saving object: " . $ex->getMessage());
        }
        return new Response(500, (__METHOD__." ".__LINE__."<br>".PHP_EOL));
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
            if ($this->request->data && $this->request->data != "") {
                $aAddonData = null;
                $aData = $this->request->data;

                if (false == is_array($aData)) {
                    $aData = json_decode($aData);
                }

                // convert stdObj to array
                $aObjData = $this->_objectToArray($aData);
                foreach ($aObjData as $k => $v) {
                    if (substr($k, 0, 1) === "_") {
                        $aAddonData[substr($k, 1)] = $v;
                        unset($aObjData[$k]);
                    }
                }
                // get OXID and create new object by cloning
                $sOxid = $aObjData['oxid'];
                $oxObj = oxNew($class);
                if (!isset($sOxid) || $sOxid == '') {
                    // create new OXID
                    $sOxid = \OxidEsales\Eshop\Core\Registry::getUtilsObject()->generateUID();
                    $aObjData['oxid'] = $sOxid;
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

                if ($oxObj->load($sOxid)) {
                    $aObject = $this->_oxObject2Array($oxObj);
                    // enriched data, e.g. addresses for oxuser?
                    //$this->_doLog("aAddonData: " . print_r($aAddonData, true));
                    foreach ($aAddonData as $objName => $objData) {
                        $oxAddObj = oxNew($objName);
                        // this "enriched" data comes as array, too
                        foreach ($objData as $idx => $data) {
                            $addOxid = $data['oxid'];
                            // no OXID, create new object
                            if (!isset($addOxid) || $addOxid == '') {
                                // create new OXID
                                $addOxid = \OxidEsales\Eshop\Core\Registry::getUtilsObject()->generateUID();
                                $data['oxid'] = $addOxid;
                                // assign new array data
                                $oxAddObj->assign($data);
                                // save object
                                $oxAddObj->save();
                            } else {
                                if ($oxAddObj->load($addOxid)) {
                                    // assign new array data
                                    $oxAddObj->assign($data);
                                    // save object
                                    $oxAddObj->save();
                                }
                            }
                            // reload object
                            if ($oxAddObj->load($addOxid)) {
                                $aObject["_" . $objName][] = $this->_oxObject2Array($oxAddObj);
                            }
                        }
                    }
                    $jsonEncode = json_encode($aObject);

                    if(false === $jsonEncode) {

                        $jsonErrormsg = json_last_error_msg();
                        return new Response(404, "jsonErrormsg: ". $jsonErrormsg);
                    }

                    return new Response(200, $jsonEncode, array('content-type' => 'application/json'));
                }
            }
        } catch (Exception $ex) {
            $this->_doLog("Error saving new object: " . $ex->getMessage());
        }
        return new Response(500, (__METHOD__." ".__LINE__."<br>".PHP_EOL));
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
