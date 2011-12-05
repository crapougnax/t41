<?php

namespace t41\ObjectModel\Rule;

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
 * @version    $Revision: 832 $
 */

/** Required files */
require_once 't41/Object/Abstract.php';
require_once 't41/Object/Rule/Interface.php';

/**
 * Abstract abstract class
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class RuleAbstract extends \t41\ObjectModel\ObjectModelAbstract implements RuleInterface {


	protected $_source;
	
	
	protected $_destination;

	
	public function __construct(array $params = null)
	{
		/* deal with class parameters first */
		$this->_setParameterObjects();
		
		if (is_array($params)) {
			
			$this->_setParameters($params);
		}
	}
	
	
	public function setSource($str)
	{
		$this->_source = $str;
		
		if (strpos($this->_source, '.') !== false) {
			
			$this->_source = explode('.', $this->_source);
		}
		
		return $this;
	}
	

	public function setDestination($str)
	{
		$this->_destination = $str;
		
		if (strpos($this->_destination, '.') !== false) {
			
			$this->_destination = explode('.', $this->_destination);
		}
		
		return $this;
	}
	
	
	public function execute(\t41\ObjectModel\DataObject $do)
	{
		return true;
	}
}