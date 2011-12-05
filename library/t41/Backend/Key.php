<?php

namespace t41\Backend;

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
 * @version    $Revision: 907 $
 */

/**
 * Class used to identify a backend key
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * 
 */


class Key {
	
	/**
	 * Key name
	 *
	 * @var string
	 */
	protected $_name;
	
	/**
	 * Key type
	 *
	 * @var string
	 */
	protected $_type;
	
	
	/**
	 * Class constructor, defines the basic properties
	 *
	 * @param string $name
	 * @param string $type
	 */
	public function __construct($name, $type = null)
	{
		$this->setName($name)->setType($type);
	}
	
	
	
	/**
	 * Define key name
	 * 
	 * @param string $str
	 * @return t41_Backend_Uri
	 */
	public function setName($str)
	{
		$this->_name = $str;
		return $this;
	}
	

	/**
	 * Define key type
	 * 
	 * @param string $type
	 * @return t41_Backend_Uri
	 */
	public function setType($type)
	{
		$this->_type = $type;
		return $this;
	}
	
	
	public function getName()
	{
		return $this->_name;
	}
	
	
	public function getType()
	{
		return $this->_type;
	}
	
	
	public function castValue($value)
	{
		if ($this->_type) {
			
			settype($value, $this->_type);
		}
		
		return $value;
	}
}