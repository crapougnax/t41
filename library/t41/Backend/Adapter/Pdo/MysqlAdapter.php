<?php

namespace t41\Backend\Adapter\Pdo;

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
 */

use t41\Backend\Adapter\AbstractPdoAdapter;

/**
 * Class used to identify a backend
 *
 * @category   t41
 * @package    t41_Backend
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class MysqlAdapter extends AbstractPdoAdapter {

	
	protected $_adapter = 'pdo_mysql';
	
	
	protected function _connect()
	{
		if (! $this->_ressource) {
			parent::_connect();
		}
		$this->_ressource->query("SET NAMES 'utf8'");
		$this->_ressource->query("SET CHARACTER SET 'utf8'");
	}
}
