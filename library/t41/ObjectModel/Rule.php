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
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 832 $
 */


/**
 * Class for Property.
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Rule {

	
	/**
	 * Returns a t41_Object_Rule_* instance build from parameters
	 *
	 * @param string $id
	 * @param string $type
	 * @param array  $params
	 * @return t41_Property_Abstract
	 * @throws t41_Property_Exception
	 */
	static public function factory($type = 'string', array $params = null)
	{
		$className = 'Rule\\' . ucfirst(strtolower($type));
		
		try {
			
			/* @todo fix this pb of autoloader/require_once */
			require_once str_replace('_', '/', $className) . '.php';
				
			/* @var $property t41_Object_Rule_Abstract */
			$rule = new $className($params);
			
			if (! $rule instanceof Rule\RuleInterface) {
				
				throw new Property\Exception("$className doesn't implement t41_Object_Rule_Interface");
			}
			
			return $rule;

		} catch (Exception $e) {
			
			require_once 't41/Object/Exception.php';
			throw new Exception("RULE_INSTANCIATION_ERROR", $e->getCode(), $e);
		}
	}
}