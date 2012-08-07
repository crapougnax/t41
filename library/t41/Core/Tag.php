<?php

namespace t41\Core;

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
 * @version    $Revision: 972 $
 */

/**
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Tag {

	/**
	 * tag syntax: %store:val.sub%
	 * 
	 * ex: %env:date.today%
	 */
	
	/**
	 * Tag boundaries marker
	 * @var string
	 */
	const MARKER = '%';
	
	/**
	 * Tag store value separator
	 * @var string
	 */
	const ENVSEP = ':';
	
	/**
	 * Tag sub tag value separator
	 * @var string
	 */
	const SUBSEP = '.';
	
	
	/**
	 * List of declared stores and their optional namespace
	 * @var array
	 */
	static protected $_stores = array('env' => 't41', 'object' => 't41');
	
	
	static protected $_interface = 't41\Core\Tag\TagInterface';
	

	/**
	 * Add or replace a tag store 
	 * @param string $store
	 * @param string $namespace
	 */
	static public function addStore($store, $namespace = 't41')
	{
		if (! is_string($store)) {
			
			throw new \t41\Exception("ARGUMENT_ILLEGAL_TYPEOF", array('string', $store));
		}
		
		if (! is_string($namespace)) {
				
			throw new \t41\Exception("ARGUMENT_ILLEGAL_TYPEOF", array('string', $namespace));
		}
		
		self::$_stores[$store] = $namespace;
	}
	
	
	/**
	 * Return the stores array
	 * @return array
	 */
	static public function getStores()
	{
		return self::$_stores;
	}
	
	
	static public function get($tag)
	{
		if (($ctag = self::_parseTag($tag)) == false) {
			
			return false;
		}
		
		if (! is_array($ctag) || count($ctag) < 2) {
			
			return false;
		}

		/*
		 * Send command to subclass, if available
		 */
		if (array_key_exists($ctag[0], self::$_stores)) {
			
			$class = sprintf('%s\Core\Tag\%sTag', self::$_stores[$ctag[0]], ucfirst(strtolower($ctag[0])));

			if (! in_array(self::$_interface, (array) @class_implements($class))) {
				
				throw new \t41\Exception("CLASS_MISS_INTERFACE", array($class, self::$_interface));
			}
			
			return $class::get($ctag[1], isset($ctag[2]) ? $ctag[2] : null);
		
		} else {
			
			return false;
		}
	}
	

	static public function parse($str)
	{
		$tagPattern = "/%([a-z0-9]+)\\:([a-z0-9.]*)\\{*([a-zA-Z0-9:,\\\"']*)\\}*%/";
		 
		$tags = array();
		 
		preg_match_all($tagPattern, $str, $tags, PREG_SET_ORDER);

		//\Zend_Debug::dump($tags); die;
		foreach ($tags as $tag) {
			
			$str = str_replace($tag[0], self::get($tag[0]), $str);
		}
		
		return $str;
	}
	
	
	/**
	 * Extract relevant parameters from tag in string
	 * @param string $tag
	 * @return boolean|array
	 */
	static protected function _parseTag($tag)
	{
		if (substr($tag, 0, 1) != self::MARKER || substr($tag, -1) != self::MARKER) {
			
			return false;
		}
		
		$pattern = sprintf('/%s([a-z]+)\\%s([a-z]+)\\%s*([a-z]*)%s/', self::MARKER, self::ENVSEP, self::SUBSEP, self::MARKER);
		preg_match($pattern, $tag, $matches);
		array_shift($matches);
		return $matches;
	}
}
