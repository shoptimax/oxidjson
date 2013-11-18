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

// load autoloader
$al = __DIR__.'/../vendor/autoload.php';
include $al;
// load OXID Framework, OXID >= 4.7. only
if(file_exists(__DIR__ . '/../bootstrap.php')) {
    include __DIR__ . '/../bootstrap.php';
}
else {
    // for older shops before 4.7., do some custom OXID bootstrapping
    // and also include oxRegistry
    include __DIR__ . '/bootstrap_oxid.php';    
}

$config = array(
    'load' => array(
        __DIR__.'/service/*.php'
    ),
);

$app = new Tonic\Application($config);

$uri = $_SERVER['REQUEST_URI'];
$result = preg_replace('/.*oxrest(.*)/x', '\1', $uri);
$request = new Tonic\Request(array('uri' => $result));

try {
    $resource = $app->getResource($request);
    $response = $resource->exec();
} catch (Tonic\NotFoundException $e) {
    $response = new Tonic\Response(404, $e->getMessage());
} catch (Tonic\UnauthorizedException $e) {
    $response = new Tonic\Response(401, $e->getMessage());
} catch (Tonic\Exception $e) {
    $response = new Tonic\Response($e->getCode(), $e->getMessage());
}

$response->output();
