<?php

namespace t41\View;

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
 * @version    $Revision: 832 $
 */

use t41\View;

/**
 * Basic class providing a decorator factory.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Decorator {

	
	/**
	 * Factory pattern used to instanciate a proper decorator for the given object extended from t41_Object_Abstract
	 *
	 * @param t41\View\ViewObject $object disabled for now because of legacy problems
	 * @param array $params
	 * @return t41\View\AbstractDecorator
	 */
	public static function factory($object, array $params = null)
	{
    	$decoratorData = $object->getDecorator();
    	if (is_null($params) && isset($decoratorData['params'])) {
    		$params = $decoratorData['params'];
    	} elseif (!is_null($params) && isset($decoratorData['params'])) {
    		$params = array_merge($params, $decoratorData['params']);
    	}
    	

		/* if an alternative decorator library is indicated, we use is to build the class name */
    	if (isset($decoratorData['class'])) {
    								
    		$decoratorClass = $decoratorData['class'];
    		
    	} else if (isset($decoratorData['lib'])) {

    		$decoratorClass = str_replace('t41', $decoratorData['lib'], get_class($object));
    		
    	} else {
    							
	    	$decoratorClass = get_class($object);
    	}

    	if (View::getViewType() != Adapter\WebAdapter::ID) {
    		
    		$decoratorData['name'] = 'Default';
    	}
    	
    	if (! isset($decoratorData['name']) || trim($decoratorData['name'])=='') {
    		
    		$decoratorData['name'] = 'Default';
    	}
    	
    	$decoratorClass .= '\\' . View::getContext() . ucfirst($decoratorData['name']);
								
    	if (! class_exists($decoratorClass)) {

    		throw new Exception("The decorator class '$decoratorClass' doesn't exist or was not found");
    	}

    	try {
    			$decorator = new $decoratorClass($object, $params);
    			
    	} catch (Exception $e) {

    			throw new Exception("Error instanciating '$decoratorClass' decorator.", $e->getCode(), $e);
    	}
    	
    	return $decorator;
	}
}
