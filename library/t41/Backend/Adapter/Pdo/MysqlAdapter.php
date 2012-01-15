<?php

namespace t41\Backend\Adapter;

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
 * @version    $Revision: 880 $
 */


/**
 * Class used to identify a backend
 *
 * @category   t41
 * @package    t41_Backend
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @todo Actuellement, la classe n'accepte pas d'être utilisée sans préciser de nom de base
 * 		il faudrai donc réfléchir à comment implementer ça au niveau de Zend_DB mais aussi au niveau des URI
 * 		de façon à permettre à utiliser un backend MySQL sur plusieurs base d'un même serveur.
 * 
 */
class MysqlAdapter extends PdoAdapter {

	
	protected $_adapter = 'pdo_mysql';
	
	
	protected function _connect()
	{
		parent::_connect();
		$this->_ressource->query("SET NAMES 'utf8'");
	}
}
