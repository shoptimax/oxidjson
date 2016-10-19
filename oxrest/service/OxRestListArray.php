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
 * CRUD for lists of objects, uses arrays for collecting data.
 * This is a "variation" of OxRestList and only uses arrays instead of OXID objects.
 * So it is much faster than OxRestList and it is also able to handle non-list-type objects,
 * e.g. you can load / save tables like oxcontents, oxorders which don't have specific
 * oxList implementations.
 * 
 * @uri /list/:class
 * @uri /list/:class/:page
 * @uri /list/:class/:page/:pageSize
 * @uri /list/:class/:page/:pageSize/:orderBy
 * @uri /list/:class/:propertyName/:comparator/:propertyValue/:page
 * @uri /list/:class/:propertyName/:comparator/:propertyValue/:page/:pageSize
 * @uri /list/:class/:propertyName/:comparator/:propertyValue/:page/:pageSize/:orderBy
 */
class OxRestListArray extends OxRestBase
{
    /**
     * @method GET
     * @json
     * @priority 2
     * @param string $class
     * @return Tonic\Response
     */
    public function arrayList($class)
    {
        try {
            $ret = array();
            $tableName = $this->_getTableName($class);
            $sSql = "select * from {$tableName}";

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
            // use only arrays
            $a = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getAll($sSql);
            foreach ($a as $idx => $aData) {
                $aData2 = array();
                // lowercase keys from OXID to oxid etc. for the view
                foreach ($aData as $aKey => $aVal) {
                    $aKey = strtolower($aKey);
                    if (!$this->_keyIsBlacklisted($aKey)) {
                        $aData2[$aKey] = $aVal;
                    }
                }
                $l[$aData2['oxid']] = $aData2;
            }
            
            $ret['numPages']   = $iNumPages;
            $ret['curPage']    = $iCurPage;
            $ret['numObjects'] = $cnt;
            $ret['objectType'] = $class;
            $ret['result']     = $l;
            $ret['numCurr'] = count($l);
            //$this->_doLog("list: " . print_r($a, true));
            
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
        $oDb = oxDb::getDb();
        try {
            $ret = array();
            // check for data in request
            if ($this->request->data && $this->request->data != "") {
                //$this->_doLog("CLASS: $class\nDATA: " . print_r(json_encode($this->request->data), true));
                
                $tableName = $this->_getTableName($class);
                $aData = $this->request->data;
                // use SQL for updates
                foreach ($aData as $idx => $oData) {
                    $sSql = "update {$tableName} set ";
                    // convert stdObj to array
                    $aArr = $this->_objectToArray($oData);
                    $bAddSep = false;
                    $aVals = array();
                    foreach ($aArr as $aKey => $aVal) {
                        if ($bAddSep) {
                            $sSql .= ",";
                        }
                        $sSql .= " {$aKey}= ?";
                        $aVals[] = $aVal;
                        $bAddSep = true;
                    }
                    $sSql .= " where oxid=?";
                    $aVals[] = $aArr['oxid'];
                    $this->_doLog($sSql . " " . print_r($aVals, true));
                    $res = $oDb->execute($sSql, $aVals);
                    if (!$res) {
                        $this->_doLog("Update error: " . mysql_error());
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
     * Could also be used for saving new data since it tries to update
     * on failure (existing entries)
     * @method POST
     * @hasRwAccessRights
     * @json
     * @param string $class
     * @priority 3
     * @return Tonic\Response
     */
    public function createFromList($class)
    {
        $oDb = oxDb::getDb();
        try {
            $ret = array();
            // check for data in request
            if ($this->request->data && $this->request->data != "") {
                $tableName = $this->_getTableName($class);
                $aData = $this->request->data;
                foreach ($aData as $idx => $oData) {
                    $aVals = array();
                    $sSql = "insert into {$tableName} ( ";
                    // convert stdObj to array
                    $aArr = $this->_objectToArray($oData);
                    $bAddSep = false;
                    $bAddUpdateSep = false;
                    $sVals = "";
                    $sUpdates = "";
                    foreach ($aArr as $aKey => $aVal) {
                        if ($bAddSep) {
                            $sSql .= ",";
                            $sVals .= ",";
                        }
                        if ($bAddUpdateSep) {
                            $sUpdates .= ",";
                        }
                        $sSql .= " {$aKey} ";
                        $sVals .= " ? ";
                        if ($aKey !== "oxid") {
                            $sUpdates .= " {$aKey}=" . $oDb->quote($aVal);
                            $bAddUpdateSep = true;
                        }
                        $aVals[] = $aVal;
                        $bAddSep = true;
                    }
                    $sSql .= " ) VALUES ( $sVals )";
                    $sSql .= " on duplicate key update $sUpdates";
                    //$this->_doLog("SQL: " . $sSql);
                    $res = $oDb->execute($sSql, $aVals);
                }
            }
            return new Response(200, $ret);
        } catch (Exception $ex) {
            $this->_doLog("Error saving list: " . $ex->getMessage());
        }
        return new Response(500);
    }

    /**
     * Get table name from object name
     * @param string $class
     * @return string
     */
    protected function _getTableName($class)
    {
        // check if the requested (oxList based) class exists, e.g. "oxarticleList"
        if (class_exists($class)) {
            /** @var oxList $list */
            $list = oxNew($class);
            /** @var oxI18n $bo */
            if (method_exists($list, "getBaseObject")) {
                $bo = $list->getBaseObject();
                return $bo->getViewName();
            }
        }
        // if not, assume we got a simple table name here, e.g. "oxarticles" or "oxorder"
        return $class;
    }
}
