<?php

/**
 * ownCloud - Music app
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt dev@bernhard-posselt.com
 * @author Morris Jobke
 * @copyright 2013 Morris Jobke <morris.jobke@gmail.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Music\DependencyInjection;

use OCA\Music\AppFramework\Http\Http;
use OCA\Music\AppFramework\Http\Request;
use OCA\Music\AppFramework\Http\Dispatcher;
use OCA\Music\Core\API;
use OCA\Music\AppFramework\Middleware\MiddlewareDispatcher;
use OCA\Music\AppFramework\Middleware\Http\HttpMiddleware;
use OCA\Music\AppFramework\Middleware\Security\SecurityMiddleware;

// in OC6 pimple is already loaded
if(!class_exists('Pimple')) {
	require_once __DIR__ . '/../3rdparty/Pimple/Pimple.php';
}


/**
 * This class extends Pimple (http://pimple.sensiolabs.org/) for reusability
 * To use this class, extend your own container from this. Should you require it
 * you can overwrite the dependencies with your own classes by simply redefining
 * a dependency
 */
class DIContainer extends \Pimple {

	/**
	 * Put your class dependencies in here
	 */
	public function __construct(){

		$this['AppName'] = 'music';

		$this['API'] = $this->share(function($c){
			return new API($c['AppName']);
		});

		/**
		 * Http
		 */
		$this['Request'] = $this->share(function($c) {
			$params = json_decode(file_get_contents('php://input'), true);
			$params = is_array($params) ? $params: array();

			return new Request(
				array(
					'get' => $_GET,
					'post' => $_POST,
					'files' => $_FILES,
					'server' => $_SERVER,
					'env' => $_ENV,
					'session' => $_SESSION,
					'cookies' => $_COOKIE,
					'method' => (isset($_SERVER) && isset($_SERVER['REQUEST_METHOD']))
							? $_SERVER['REQUEST_METHOD']
							: null,
					'params' => $params,
					'urlParams' => $c['urlParams']
				)
			);
		});

		$this['Protocol'] = $this->share(function(){
			if(isset($_SERVER['SERVER_PROTOCOL'])) {
				return new Http($_SERVER, $_SERVER['SERVER_PROTOCOL']);
			} else {
				return new Http($_SERVER);
			}
		});

		$this['Dispatcher'] = $this->share(function($c) {
			return new Dispatcher($c['Protocol'], $c['MiddlewareDispatcher']);
		});

		/**
		 * Middleware
		 */
		$this['SecurityMiddleware'] = $this->share(function($c){
			return new SecurityMiddleware($c['API'], $c['Request']);
		});

		$this['HttpMiddleware'] = $this->share(function($c){
			return new HttpMiddleware($c['API'], $c['Request']);
		});

		$this['MiddlewareDispatcher'] = $this->share(function($c){
			$dispatcher = new MiddlewareDispatcher();
			$dispatcher->registerMiddleware($c['HttpMiddleware']);
			$dispatcher->registerMiddleware($c['SecurityMiddleware']);
			return $dispatcher;
		});

		require_once __DIR__ . '/diconfig.php';
	}

}
