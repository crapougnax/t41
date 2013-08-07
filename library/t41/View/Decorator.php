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
	 * Array of path where to search for decorators in order
	 * @var array
	 */
	static protected $_paths = array();
	
	
	public static function addPath($path, $ns = 't41')
	{
		if (is_dir($path)) {
			if (substr($path,-1) != DIRECTORY_SEPARATOR) {
				$path .= DIRECTORY_SEPARATOR;
			}
			array_unshift(self::$_paths, array('path' => $path, 'ns' => $ns));
		} else {
			throw new Exception("'$path' is not a directory");
		}
	}
	
	
	/**
	 * Factory pattern used to instanciate a proper decorator for the given object extended from t41\View\ViewObject
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
    	
    	// class name without the first component of the namespace
    	$class = substr(get_class($object), strpos(get_class($object),'\\')+1);
    	
    	if (View::getViewType() != Adapter\WebAdapter::ID) {
	    	$decoratorData['name'] = 'Default';
    	}
    	
    	if (! isset($decoratorData['name']) || trim($decoratorData['name']) == '') {
	    	$decoratorData['name'] = 'Default';
    	}
    	
    	// decorator name
    	$deconame = View::getContext() . ucfirst($decoratorData['name']);
    	 
    	foreach (self::$_paths as $library) {
    		$file = $library['path'] . str_replace('\\','/', $class) . '/' . $deconame . '.php';
    		if (file_exists($file)) {
	    		require_once $file;
	    		$fullclassname = '\\' . $library['ns'] . '\\' . $class . '\\' . $deconame;
	    		 
	    		if (class_exists($fullclassname)) {
	    		    try {
    					$decorator = new $fullclassname($object, $params);
			    	} catch (Exception $e) {
    					throw new Exception("Error instanciating '$fullclassname' decorator.", $e->getCode(), $e);
    				}
    				return $decorator;
	    		}
	    	}
    	}
    	throw new Exception("The decorator class '$class' doesn't exist or was not found");
	}
}
