<?php

namespace t41\ObjectModel;

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
 * @package    t41_Property
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 865 $
 */


/**
 * Class for Property.
 *
 * @category   t41
 * @package    t41_Property
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Property {

	/* constraints that can be enforced on a property's value */
	
	/**
	 * To provide a value is mandatory
	 * use <mandatory/> in your XML
	 * @var string
	 */
	const CONSTRAINT_MANDATORY		= 'mandatory';
	
	/**
	 * Value is unique
	 * use <unique/> in your XML
	 * @var string
	 */
	const CONSTRAINT_UNIQUE			= 'unique';

	/**
	 * Vale is protected, once setted and saved, it can't be changed
	 * use <protected/> in your XML
	 * @var string
	 */
	const CONSTRAINT_PROTECTED		= 'protected';

	/**
	 * Value must be encrypted before being stored (password)
	 * use <encrypted/> in your XML
	 * @var unknown_type
	 */
	const CONSTRAINT_ENCRYPTED		= 'encrypted';
	
	/**
	 * Value's minimum length
	 * use <minlength>NN</minlength> in your XML
	 * @var string
	 */
	const CONSTRAINT_MINLENGTH		= 'minlength';
	
	/**
	 * Value's maximum length
	 * use <maxlength>NN</maxlength> in your XML
	 * @var string
	 */	
	const CONSTRAINT_MAXLENGTH		= 'maxlength';
	
	/**
	 * Value must contain letter(s)
	 * use <hasletters>NN</hasletters> in your XML
	 * @var string
	 */
	const CONSTRAINT_HASLETTERS		= 'hasletters';

	/**
	 * Vaalue must contain digit(s)
	 * use <hasdigits>NN</hasdigits> in your XML
	 * @var string
	 */
	const CONSTRAINT_HASDIGITS		= 'hasdigits';
	
	/**
	 * Value is a valid email address
	 * use <emailaddress/> in your XML
	 * @var string
	 */
	const CONSTRAINT_EMAILADDRESS	= 'emailaddress';
	
	/**
	 * Value is a valid URL scheme
	 * use <urlscheme/> in your XML
	 * @var string
	 */
	const CONSTRAINT_URLSCHEME		= 'urlscheme';
	

	/* available options to get a t41_Property_Object stored value */
	
	const URI		= 'uri';
	
	const DATA		= 'object';
	
	const OBJECT	= 'data';
	
	
	/**
	 * Returns a t41_Property_* instance build from parameters
	 *
	 * @param string $id
	 * @param string $type
	 * @param array  $params
	 * @return t41_Property_Abstract
	 * @throws t41_Property_Exception
	 */
	static public function factory($id, $type = null, array $params = null)
	{
		if (empty($type)) $type = 'string';
		
		$className = 'Property\\' . ucfirst(strtolower($type));
		
		try {
			
			/* @todo fix this pb of autoloader/require_once */
			require_once str_replace('_', '/', $className) . '.php';
				
			/* @var $property t41_Property_Abstract */
			$property = new $className($id, $params, \t41\Parameter::getPropertyParameters($className));
			
			if (! $property instanceof Property\PropertyAbstract) {
				
				throw new Exception("$className is not extending t41_Property_Abstract");
			}
			
			return $property;

		} catch (\Exception $e) {
			
			require_once 't41/Property/Exception.php';
			throw new Exception("PROPERTY_INSTANCIATION_ERROR", $e->getCode(), $e);
		}
	}
}