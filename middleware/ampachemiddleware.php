<?php

/**
 * ownCloud - Music app
 *
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


namespace OCA\Music\Middleware;

use \OCA\Music\AppFramework\Utility\MethodAnnotationReader;
use \OCA\Music\AppFramework\DB\Mapper;
use \OCA\Music\AppFramework\Http\Request;
use \OCA\Music\AppFramework\Http\TemplateResponse;
use \OCA\Music\AppFramework\Middleware\Middleware;
use \OCA\Music\Core\API;

/**
 * Used to do the authentication and checking stuff for an ampache controller method
 * It reads out the annotations of a controller method and checks which if
 * ampache authentification stuff has to be done.
 */
class AmpacheMiddleware extends Middleware {

	private $api;
	private $request;
	private $mapper;
	private $isAmpacheCall;
	private $ampacheUser;

	/**
	 * @param API $api an instance of the api
	 * @param Request $request an instance of the request
	 */
	public function __construct(API $api, Request $request, Mapper $mapper, $ampacheUser){
		$this->api = $api;
		$this->request = $request;
		$this->mapper = $mapper;

		// used to share user info with controller
		$this->ampacheUser = $ampacheUser;
	}


	/**
	 * This runs all the security checks before a method call. The
	 * security checks are determined by inspecting the controller method
	 * annotations
	 * @param string/Controller $controller the controllername or string
	 * @param string $methodName the name of the method
	 * @throws AmpacheException when a security check fails
	 */
	public function beforeController($controller, $methodName){

		// get annotations from comments
		$annotationReader = new MethodAnnotationReader($controller, $methodName);

		$this->isAmpacheCall = $annotationReader->hasAnnotation('AmpacheAPI');

		// don't try to authenticate for the handshake request
		if($this->isAmpacheCall && $this->request['action'] !== 'handshake'){
			$token = $this->request['auth'];
			if($token !== null && $token !== '') {
				$user = $this->mapper->find($token);
				if($user !== false && array_key_exists('user_id', $user)) {
					// setup the filesystem for the user - actual login isn't really needed
					\OC_Util::setupFS($user['user_id']);
					$this->ampacheUser->setUserId($user['user_id']);
					return;
				}
			} else {
				// for ping action without token the version information is provided
				if($this->request['action'] === 'ping') {
					return;
				}
			}
			throw new AmpacheException('Invalid Login', 401);
		}
	}

	/**
	 * If an AmpacheException is being caught, the appropiate ampache
	 * exception response is rendered
	 * @param Controller $controller the controller that is being called
	 * @param string $methodName the name of the method that will be called on
	 *                           the controller
	 * @param \Exception $exception the thrown exception
	 * @throws \Exception the passed in exception if it cant handle it
	 * @return Response a Response object or null in case that the exception could not be handled
	 */
	public function afterException($controller, $methodName, \Exception $exception){
		if($exception instanceof AmpacheException && $this->isAmpacheCall){
			$response = new TemplateResponse($this->api, 'ampache/error');
			$response->renderAs('blank');
			$response->addHeader('Content-Type', 'text/xml; charset=UTF-8');
			$response->setparams(array(
				'code' => $exception->getCode(),
				'message' => $exception->getMessage()
			));
			return $response;
		}
		throw $exception;
	}

}
