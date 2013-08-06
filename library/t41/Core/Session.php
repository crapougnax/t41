<?php

namespace t41\Core;

/**
 * t41 Toolkit
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.t41.org/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@t41.org so we can send you a copy immediately.
 *
 * @category   t41
 * @package    t41_Backend
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 961 $
 */

use t41\Core;
use t41\ObjectModel\BaseObject;

/**
 * Simple session handler
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Session extends BaseObject {

	
	public function __construct($val = null, array $params = null)
	{
		parent::__construct($val,$params);
		
		$this->start = $this->latest = date('U');
		$this->ip = $_SERVER['REMOTE_ADDR'];
		$this->browser = $_SERVER['HTTP_USER_AGENT'];
	}
	
	
	public function __wakeup()
	{
		if (! $this->isExpired()) {
			$this->setLatest(time('U'));
			return $this->save();
		}
	}
	
	
	public function save($backend = null)
	{
		if (is_null($backend)) {
			$this->setKey(Core::cacheSet($this, $this->getKey(), true, array('tags' => 'session')));
			return true;
		} else {
			return parent::save($backend);
		}
	}
	
	
	public function isExpired()
	{
		return (time('U') - $this->getLatest()) > $this->getParameter('latency');
	}
	
	
	public function getStart($hr = false)
	{
		return $hr ? date('Y-m-d H:i:s', $this->_start) : $this->_start;
	}
	
	
	protected function toDate($timestamp)
	{
		
	}
}
