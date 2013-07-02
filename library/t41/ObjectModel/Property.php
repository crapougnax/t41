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
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 865 $
 */

use t41\ObjectModel\Property;
use t41\Core\Tag;

/**
 * Class for Property.
 *
 * @category   t41
 * @package    t41_Property
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Property {

	
	const UNDEFINED_LABEL	= "No defined display value";
	
	
	const EMPTY_VALUE		= '_NONE_';
	
	
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
	
	
	const CONSTRAINT_UPPERCASE		= 'uppercase';
	
	
	const CONSTRAINT_LOWERCASE		= 'lowercase';

	
	/* available options to get a t41_Property_Object stored value */
	
	const URI		= 'uri';
	
	const DATA		= 'object';
	
	const OBJECT	= 'data';
	
	
	/**
	 * Returns a t41\ObjectModel\PropertyAbstract-derived instance build from parameters
	 *
	 * @param string $id
	 * @param string $type
	 * @param array  $params
	 * @return t41_Property_Abstract
	 * @throws t41_Property_Exception
	 */
	static public function factory($id, $type = null, array $params = null)
	{
		if (is_null($type) || ! is_string($type)) $type = 'string';
		
		$className = sprintf('\t41\ObjectModel\Property\%sProperty', ucfirst(strtolower($type)));
		
		try {
			/* @var $property \t41\ObjectModel\Property\PropertyAbstract */
			$property = new $className($id, $params); //, Parameter::getPropertyParameters($className));

			if (! $property instanceof Property\AbstractProperty) {
				
				throw new Exception("$className is not extending t41\ObjectModel\Property\AbstractProperty");
			}
			
			return $property;

		} catch (\Exception $e) {
			
			throw new Property\Exception(array("INSTANCIATION_ERROR",array($type, $e->getMessage())));
		}
	}
	
	

	/**
	 * Parse given object properties to return a string version
	 * @param ObjectModelAbstract $object
	 * @param string $display
	 * @return mixed|string
	 */
	static public function parseDisplayProperty(ObjectModelAbstract $object, $display = null)
	{
		if (is_null($display) && $object instanceof BaseObject) {
			return $object->__toString();
		}
		
		if (substr($display,0,1) == '[') {
			
			// mask
			Tag\ObjectTag::$object = $object;
			return Tag::parse(substr($display, 1, strlen($display)-2));
			
		} else {
			
			$displayProps = explode(',', $display);
			if (count($displayProps) == 1 && $displayProps[0] == '') {
				return $object->__toString(); //self::UNDEFINED_LABEL;
			
			} else {
				$displayValue = array();
				foreach ($displayProps as $disProp) {
					// display the identifier part of an uri
					if ($disProp == ObjectUri::IDENTIFIER) {
						$displayValue[] = $object->getUri() ? $object->getUri()->getIdentifier() : '';
					} else {
						// display property value, if property exists!
						if (($prop = $object->getProperty($disProp)) !== false) {
							$displayValue[] = $prop->getDisplayValue();
						}
					}
				}
			
				return implode(' ', $displayValue);
			}
		}
	}
}
