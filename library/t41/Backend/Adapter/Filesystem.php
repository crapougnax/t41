<?php
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
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 880 $
 */


/** Required files */
require_once 't41/Backend/Adapter/Abstract.php';

/**
 * Class providing Filesystem methods to backend
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * 
 */



abstract class t41_Backend_Adapter_Filesystem extends t41_Backend_Adapter_Abstract {


	protected $_basePath;	
	
	
	/**
	 * Initialiser un backend à partir d'une Uri
	 *
	 * @param t41_Backend_Uri $uri
	 * @param string $alias
	 */
	public function __construct(t41_Backend_Uri $uri, $alias = null)
	{
		parent::__construct($uri, $alias);
	}
	
	
	public function create(t41_Data_Object $dataObj = null)
	{
		return file_put_contents($this->_basePath . $dataObj->getUri()->getUrl(), $dataObj);
	}
	
	
	public function read(t41_Data_Object $do)
	{
		return file_get_contents($this->_basePath . $do->getUri()->getUrl());
	}
	
	
	public function update(t41_Data_Object $do)
	{
		throw new t41_Backend_Exception(__CLASS__ . " backend doesn't implement the update() method");
	}
	
	
	public function delete(t41_Data_Object $do)
	{
		return unlink($this->_basePath . $uri->getUrl());
	}
	
	
	public function find(t41_Object_Collection $co)
	{
		
	}
}