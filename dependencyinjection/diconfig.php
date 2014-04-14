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



namespace OCA\Music\DependencyInjection;

use \OCA\Music\BusinessLayer\AlbumBusinessLayer;
use \OCA\Music\BusinessLayer\ArtistBusinessLayer;
use \OCA\Music\BusinessLayer\TrackBusinessLayer;
use \OCA\Music\Controller\AmpacheController;
use \OCA\Music\Controller\ApiController;
use \OCA\Music\Controller\LogController;
use \OCA\Music\Controller\PageController;
use \OCA\Music\Controller\SettingController;
use \OCA\Music\Core\API;
use \OCA\Music\DB\AlbumMapper;
use \OCA\Music\DB\AmpacheSessionMapper;
use \OCA\Music\DB\AmpacheUserMapper;
use \OCA\Music\DB\ArtistMapper;
use \OCA\Music\DB\TrackMapper;
use \OCA\Music\Middleware\AmpacheMiddleware;
use \OCA\Music\Utility\AmpacheUser;
use \OCA\Music\Utility\ExtractorGetID3;
use \OCA\Music\Utility\Scanner;

use \OCA\Music\AppFramework\Middleware\MiddlewareDispatcher;

// in stable5 getid3 is already loaded
if(!class_exists('getid3_exception')) {
	require_once __DIR__ . '/../3rdparty/getID3/getid3/getid3.php';
}

$this['Server'] = $this->share(function(){
	return \OC::$server;
});

$this['API'] = $this->share(function($c){
	return new API($c['AppName']);
});

/**
 * Controllers
 */

$this['AmpacheController'] = $this->share(function($c){
	return new AmpacheController($c['API'], $c['Request'], $c['AmpacheUserMapper'], $c['AmpacheSessionMapper'],
		$c['AlbumMapper'], $c['ArtistMapper'], $c['TrackMapper'], $c['AmpacheUser'], $c['Server']);
});

$this['ApiController'] = $this->share(function($c){
	return new ApiController($c['API'], $c['Request'],
		$c['TrackBusinessLayer'], $c['ArtistBusinessLayer'], $c['AlbumBusinessLayer'], $c['Scanner']);
});

$this['PageController'] = $this->share(function($c){
	return new PageController($c['API'], $c['Request'], $c['Scanner']);
});

$this['LogController'] = $this->share(function($c){
	return new LogController($c['API'], $c['Request']);
});

$this['SettingController'] = $this->share(function($c){
	return new SettingController($c['API'], $c['Request'], $c['AmpacheUserMapper']);
});

/**
 * Mappers
 */

$this['AlbumMapper'] = $this->share(function($c){
	return new AlbumMapper($c['API']);
});

$this['AmpacheSessionMapper'] = $this->share(function($c){
	return new AmpacheSessionMapper($c['API']);
});

$this['AmpacheUserMapper'] = $this->share(function($c){
	return new AmpacheUserMapper($c['API']);
});

$this['ArtistMapper'] = $this->share(function($c){
	return new ArtistMapper($c['API']);
});

$this['TrackMapper'] = $this->share(function($c){
	return new TrackMapper($c['API']);
});

/**
 * Business Layer
 */

$this['TrackBusinessLayer'] = $this->share(function($c){
	return new TrackBusinessLayer($c['TrackMapper'], $c['API']);
});

$this['ArtistBusinessLayer'] = $this->share(function($c){
	return new ArtistBusinessLayer($c['ArtistMapper'], $c['API']);
});

$this['AlbumBusinessLayer'] = $this->share(function($c){
	return new AlbumBusinessLayer($c['AlbumMapper'], $c['API']);
});

/**
 * Utilities
 */

$this['AmpacheUser'] = $this->share(function(){
	return new AmpacheUser();
});

$this['Scanner'] = $this->share(function($c){
	return new Scanner($c['API'], $c['ExtractorGetID3'], $c['ArtistBusinessLayer'],
		$c['AlbumBusinessLayer'], $c['TrackBusinessLayer']);
});

$this['getID3'] = $this->share(function(){
	$getID3 = new \getID3();
	$getID3->encoding = 'UTF-8';
	// On 32-bit systems, getid3 tries to make a 2GB size check,
	// which does not work with fopen. Disable it.
	// Therefore the filesize (determined by getID3) could be wrong
	// (for files over ~2 GB) but this isn't used in any way.
	$getID3->option_max_2gb_check = false;
	return $getID3;
});

$this['ExtractorGetID3'] = $this->share(function($c){
	return new ExtractorGetID3($c['API'], $c['getID3']);
});

$this['Scanner'] = $this->share(function($c){
	return new Scanner($c['API'], $c['ExtractorGetID3'], $c['ArtistBusinessLayer'],
		$c['AlbumBusinessLayer'], $c['TrackBusinessLayer']);
});

/**
 * Middleware
 */

$this['AmpacheMiddleware'] = $this->share(function($c){
	return new AmpacheMiddleware($c['API'], $c['Request'], $c['AmpacheSessionMapper'], $c['AmpacheUser']);
});

$this['MiddlewareDispatcher'] = $this->share(function($c){
	$dispatcher = new MiddlewareDispatcher();
	$dispatcher->registerMiddleware($c['AmpacheMiddleware']);
	$dispatcher->registerMiddleware($c['HttpMiddleware']);
	$dispatcher->registerMiddleware($c['SecurityMiddleware']);

	return $dispatcher;
});
