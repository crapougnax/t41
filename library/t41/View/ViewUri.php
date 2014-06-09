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
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 832 $
 */

use t41\View\ViewUri\Adapter\AbstractAdapter;

/**
 * Class providing basic URI manipulation methods.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2009 Quatrain Technologies SAS (http://technologies.quatrain.com)
 * @license    http://t41.quatrain.com/license/new-bsd     New BSD License
 */
class ViewUri {

	/**
	 * Instance of Uri builder class
	 *
	 * @var t41\View\ViewUri\AbstractAdapter
	 */
	static protected $_uriAdapter;
	
	
	public static function setUriAdapter($adapter = 'get', array $params = null)
	{
		if ($adapter instanceof AbstractAdapter) {
			self::$_uriAdapter = $adapter;
			return self::$_uriAdapter;
		}
		
		$className = sprintf('\t41\View\ViewUri\Adapter\%sAdapter', ucfirst(strtolower($adapter)));
		try {
			self::$_uriAdapter = new $className(null, $params);
		} catch (Exception $e) {
			throw new Exception("Unable to instanciate $className uri adapter: " . $e->getMessage());
		}
		return self::$_uriAdapter;
	}
	
	
	/**
	 * Return the current view uri adapter
	 * @return \t41\View\t41\View\ViewUri\AbstractAdapter
	 */
	public static function getUriAdapter()
	{
		if (self::$_uriAdapter instanceof AbstractAdapter) {
			return self::$_uriAdapter;
		} else {
			return self::setUriAdapter();
		}
	}
	
	
	/**
	 * Call the makeUri method of the instanciated adapter
	 * intented as a sortcut
	 *
	 * @param array $args
	 * @param boolean $noBase
	 * @return string
	 */
	public static function makeUri($args = null, $noBase = false)
	{
		self::getUriAdapter();
		return self::$_uriAdapter->makeUri($args, $noBase);
	}	
	
	
	public static function getIdentifier($identifier)
	{
		self::getUriAdapter();
		return self::$_uriAdapter->getIdentifier($identifier);
	}
	
	
	public static function getEnv()
	{
		self::getUriAdapter();
		return self::$_uriAdapter->getEnv();
	}
}
