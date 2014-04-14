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


namespace OCA\Music\Controller;

use \OCA\Music\AppFramework\Core\API;
use \OCA\Music\AppFramework\Db\Mapper;
use \OCA\Music\AppFramework\Http\Request;


class SettingController extends Controller {

	private $ampacheUserMapper;

	public function __construct(API $api, Request $request, Mapper $ampacheUserMapper){
		parent::__construct($api, $request);

		$this->ampacheUserMapper = $ampacheUserMapper;
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function userPath() {
		$success = false;
		$path = $this->params('value');
		$pathInfo = $this->api->getFileInfo($path);
		if ($pathInfo && $pathInfo['mimetype'] === 'httpd/unix-directory') {
			if ($path[0] != '/') {
				$path = '/' . $path;
			}
			if ($path[strlen($path)-1] !== '/') {
				$path .= '/';
			}
			$this->api->setUserValue('path', $path);
			$success = true;
		}
		return $this->renderPlainJSON(array('success' => $success));
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function addUserKey() {
		$userId = $this->api->getUserId();
		$success = false;
		$description = $this->params('description');
		$password = $this->params('password');

		$hash = hash('sha256', $password);
		$id = $this->ampacheUserMapper->addUserKey($userId, $hash, $description);
		if($id !== null) {
			$success = true;
		}
		return $this->renderPlainJSON(array('success' => $success, 'id' => $id));
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function removeUserKey() {
		$userId = $this->api->getUserId();
		$id = $this->params('id');
		$this->ampacheUserMapper->removeUserKey($userId, $id);
		return $this->renderPlainJSON(array('success' => true));
	}
}
