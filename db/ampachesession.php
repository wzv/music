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


namespace OCA\Music\Db;

use \OCA\Music\AppFramework\Db\Entity;
use \OCA\Music\Core\API;


/**
 * @method string getUserId()
 * @method setUserId(string $userId)
 * @method string getToken()
 * @method setToken(string $token)
 * @method int getExpiry()
 * @method setExpiry(int $expiry)
 */
class AmpacheSession extends Entity {

	public $userId;
	public $token;
	public $expiry;

	public function __construct(){
		$this->addType('expiry', 'int');
	}
}