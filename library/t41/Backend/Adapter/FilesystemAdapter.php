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
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 880 $
 */

use t41\Backend;
use t41\ObjectModel;

/**
 * Class providing Filesystem methods to backend
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * 
 */



abstract class FilesystemAdapter extends AbstractAdapter {


	protected $_basePath;	
	
	
	/**
	 * Initialiser un backend à partir d'une Uri
	 *
	 * @param \t41\Backend\BackendUri $uri
	 * @param string $alias
	 */
	public function __construct(Backend\BackendUri $uri, $alias = null)
	{
		parent::__construct($uri, $alias);
	}
	
	
	public function create(ObjectModel\DataObject $dataObj = null)
	{
		return file_put_contents($this->_basePath . $dataObj->getUri()->getUrl(), $dataObj);
	}
	
	
	public function read(ObjectModel\DataObject $do)
	{
		return file_get_contents($this->_basePath . $do->getUri()->getUrl());
	}
	
	
	public function update(ObjectModel\DataObject $do)
	{
		throw new Exception(__CLASS__ . " backend doesn't implement the update() method");
	}
	
	
	public function delete(ObjectModel\DataObject $do)
	{
		return unlink($this->_basePath . $do->getUri()->getUrl());
	}
	
	
	public function find(ObjectModel\Collection $co)
	{
		
	}
}
