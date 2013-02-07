<?php

namespace t41\View\FormComponent\Element;

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
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 876 $
 */

use t41\Parameter;

/**
 * t41 Data Object handling a set of properties tied to an object
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class DateElement extends AbstractElement {

	
	const TODAY = '___NOW___';
	
	
	public function __construct($id = null, array $params = null, $backend = null)
	{
		$this->_setParameterObjects(array(	'enable_quickset'		=> new Parameter(Parameter::BOOLEAN, true))
		);
		
		parent::__construct($id, $params, $backend);
		
	}
	

	public function setDefaultValue($val)
	{
		 $this->_defaultVal = ($val == self::TODAY) ? date('Y-m-d') : $val;
	}
	
	
	public function formatValue($str = null, $fancy = false)
	{
		 if (! is_null($str)) {

		 	$date = new \Zend_Date($str);
		 	return $fancy ? $date->toString(\Zend_Date::DATE_LONG) : $date->toString('d/M/Y');
			
		} else {
			
			return $str;
		}
	}
}
