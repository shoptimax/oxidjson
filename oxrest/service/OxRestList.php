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
 * CRUD for lists of objects, uses oxList based collections.
 * @uri /oxlist/:class
 * @uri /oxlist/:class/:page
 * @uri /oxlist/:class/:page/:pageSize
 * @uri /oxlist/:class/:page/:pageSize/:orderBy
 * @uri /oxlist/:class/:propertyName/:comparator/:propertyValue/:page
 * @uri /oxlist/:class/:propertyName/:comparator/:propertyValue/:page/:pageSize
 * @uri /oxlist/:class/:propertyName/:comparator/:propertyValue/:page/:pageSize/:orderBy
 */
class OxRestList extends OxRestBase
{
    /**
     * @method GET
     * @json
     * @priority 2
     * @param string $class
     * @return Tonic\Response
     */
    public function getList($class)
    {
        try {
            $ret = array();

            /** @var oxList $list */
            $list = oxNew($class);
            /** @var oxI18n $bo */
            $bo = $list->getBaseObject();

            $sSql = "select * from " . $bo->getViewName();

            if ($this->propertyName !== null && $this->propertyName !== '' && $this->propertyValue !== null && $this->propertyValue !== '') {

                $sSql .= " WHERE {$this->propertyName} ";

                if ($this->comparator === "like") {
                    $sSql .= "LIKE '%{$this->propertyValue}%'";
                } else {
                    $sSql .= "= '{$this->propertyValue}'";
                }

                $ret['condition']  = $this->propertyName . ':' . $this->propertyValue;
                $ret['comparator'] = $this->comparator;
            }

            $sSqlCount = "SELECT COUNT(*) AS cnt FROM (" . $sSql . ") AS t1";

            // order by
            $sOrderBy = "oxid ASC";
            if (isset($this->orderBy)) {
                $sOrderBy = urldecode($this->orderBy);
            }
            $sSql .= " ORDER BY $sOrderBy";

            $iPerPage  = 10;
            if (isset($this->pageSize) && is_numeric($this->pageSize)) {
                $iPerPage = (int)$this->pageSize;
            }
            $cnt       = (int)oxDb::getDb()->getOne($sSqlCount);
            $iNumPages = $cnt > 0 ? (ceil($cnt / $iPerPage)) : 1;

            $iCurPage = 0;
            if (isset($this->page)) {
                $iCurPage = (int)$this->page;
            }
            $iLimitOffset = $iPerPage * $iCurPage;

            $sSql = $sSql . " LIMIT $iLimitOffset,$iPerPage";

            //$this->_doLog($sSql);

            $l = array();
            $list->selectString(
                $sSql
            );
            foreach ($list->getArray() as $oxid => $oxObject) {
                $l[$oxid] = $this->_oxObject2Array($oxObject);
            }

            $ret['numPages']   = $iNumPages;
            $ret['curPage']    = $iCurPage;
            $ret['numObjects'] = $cnt;
            $ret['objectType'] = get_class($bo);
            $ret['result']     = $l;
            $ret['numCurr'] = count($l);
            //$this->_doLog("list: " . print_r($l, true));

            return new Response(200, $ret);
        } catch (Exception $ex) {
            $this->_doLog("Error getting list: " . $ex->getMessage());
        }

        return new Response(404, "No data");
    }

    /**
     * Saving list data back to DB
     * @method PUT
     * @json
     * @hasRwAccessRights
     * @param string $class
     * @priority 3
     * @return Tonic\Response
     */
    public function saveList($class)
    {
        try {
            $ret = array();
            // check for data in request
            if ($this->request->data && $this->request->data != "") {
                $this->_doLog("CLASS: $class\nDATA: " . print_r(json_encode($this->request->data), true));

                /** @var oxList $list */
                $list = oxNew($class);
                $bo = $list->getBaseObject();
                $aData = $this->request->data;
                foreach ($aData as $idx => $oData) {
                    // convert stdObj to array
                    $aObjData = $this->_objectToArray($oData);
                    // get OXID and create new object by cloning
                    $sOxid = $aObjData['oxid'];
                    $oxObj = clone $bo;
                    if ($oxObj->load($sOxid)) {
                        // assign new array data
                        $oxObj->assign($aObjData);
                        // save object
                        $oxObj->save();
                    }
                }
            }
            return new Response(200, $ret);
        } catch (Exception $ex) {
            $this->_doLog("Error saving list: " . $ex->getMessage());
        }
        return new Response(500);
    }

    /**
     * Create new objects from list data
     * @method POST
     * @json
     * @hasRwAccessRights
     * @param string $class
     * @priority 4
     * @return Tonic\Response
     */
    public function createFromList($class)
    {
        try {
            $ret = array();
            // check for data in request
            if ($this->request->data && $this->request->data != "") {
                /** @var oxList $list */
                $list = oxNew($class);
                $bo = $list->getBaseObject();
                $aData = $this->request->data;
                foreach ($aData as $idx => $oData) {
                    // convert stdObj to array
                    $aObjData = $this->_objectToArray($oData);
                    // get OXID and create new object by cloning
                    $sOxid = $aObjData['oxid'];
                    $oxObj = clone $bo;
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
                }
            }
            return new Response(200, $ret);
        } catch (Exception $ex) {
            $this->_doLog("Error saving list: " . $ex->getMessage());
        }
        return new Response(500);
    }
}
