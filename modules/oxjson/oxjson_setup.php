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
class oxjson_setup extends oxSuperCfg {

    /**
     * Setup routine
     */
    public static function onActivate() {
        if(class_exists('oxRegistry')) {
            $myConfig = oxRegistry::getConfig();
        }
        else {
            $myConfig = oxConfig::getInstance()->getConfig();            
        }
        $bIsEE = $myConfig->getEdition() === "EE";
        try {
            $db = oxDb::getDb();
            // create oxjson groups
            if($bIsEE) {
                $maxRRId = intval($db->getOne("select MAX(OXRRID) from oxgroups"));
                $nextRRId = $maxRRId+1;
                $sQ = "INSERT IGNORE INTO oxgroups (OXID, OXACTIVE, OXTITLE, OXTITLE_1, OXRRID) VALUES ('oxjsonro', '1', 'OXJSON Read-only', 'OXJSON Read-only', '$nextRRId');";
                $db->Execute($sQ);
                $nextRRId++;
                $sQ = "INSERT IGNORE INTO oxgroups (OXID, OXACTIVE, OXTITLE, OXTITLE_1, OXRRID) VALUES ('oxjsonfull', '1', 'OXJSON Full', 'OXJSON Full', '$nextRRId');";
                $db->Execute($sQ);
            }
            else {
                $sQ = "INSERT IGNORE INTO oxgroups (OXID, OXACTIVE, OXTITLE, OXTITLE_1) VALUES ('oxjsonro', '1', 'OXJSON Read-only', 'OXJSON Read-only');";
                $db->Execute($sQ);
                $nextRRId++;
                $sQ = "INSERT IGNORE INTO oxgroups (OXID, OXACTIVE, OXTITLE, OXTITLE_1) VALUES ('oxjsonfull', '1', 'OXJSON Full', 'OXJSON Full');";
                $db->Execute($sQ);                
            }
        } catch (Exception $ex) {
            error_log("Error activating module: " . $ex->getMessage());
        }
    }
}
?>
