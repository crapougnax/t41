<?php

namespace t41\View\ViewUri\Adapter;

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
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

use t41\Core;
use t41\Core\Layout;

/**
 * Abstract adapter for URI manipulation.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

abstract class AbstractAdapter {

	
	/**
	 * Character used to glue key/pairs after collation
	 * typically '&' but can be different
	 * 
	 * @var string
	 */
	protected $_pairsSeparator;
	
	/**
	 * Character used to assign value to key in pairs
	 * typically '=' but not always
	 *
	 * @var string
	 */
	protected $_assignSeparator;
	
	/**
	 * Character used to glue base uri and arguments string
	 * typically '?' but can be different
	 *
	 * @var string
	 */
	protected $_partsSeparator;
	
	/**
	 * Uri Base, used as a prefix 
	 *
	 * @var string
	 */
	protected $_uriBase;
	
	/**
	 * Array of sundries identifiers used to pass parameters 
	 *
	 * @var array
	 */
	protected $_identifiers = array();
	
	/**
	 * Environment data
	 *
	 * @var array
	 */
	protected $_env = array();
	
	
	protected $_defaults = array();
	
	
	protected $_params = array();
	
	/**
	 * Array of arguments to be passed in the uri
	 *
	 * @var array
	 */
	protected $_arguments = array();
	
	
	public function __construct($uriBase = null, array $params = null)
	{
		if (! $uriBase) {
			$uriBase = '/' . Layout::$module . '/' . Layout::$controller . '/' . Layout::$action; //@$_SERVER['REDIRECT_URL'];
		}
		
		$this->setUriBase($uriBase);
		$this->_defaults = array($this->_identifiers['search'] => array(),
								 $this->_identifiers['sort']   => array(),
								 $this->_identifiers['offset'] => array()
								);
		if (is_array($params)) $this->_params = $params;
	}
	
	
	public function setUriBase($uri)
	{
		$this->_uriBase = $uri;
	}
	
	
	
	/**
	 * Define a default value for given argument
	 * @param string $key
	 * @param string $val
	 * @return \t41\View\ViewUri\Adapter\AbstractAdapter
	 */
	public function setArgumentDefault($key, $val, $set = 'search')
	{
		$this->_defaults[$this->_identifiers[$set]][$key] = $val;
		return $this;
	}
	
	
	/**
	 * Return given argument default value if exists, FALSE otherwise
	 * @param string $key
	 * @return mixed
	 */
	public function getArgumentDefault($key, $set = 'search')
	{
		return isset($this->_defaults[$set][$key]) ? $this->_defaults[$set][$key] : false;
	}
	
	
	public function setArguments(array $args, $set = null)
	{
		foreach ($args as $key => $val) {
			$this->setArgument($key, $val, $set);
		}
	}
	
	
	/**
	 * Define or replace argument $key with value $value
	 *
	 * @param string $key
	 * @param string $value
	 * @return boolean
	 */
	public function setArgument($key, $value = null, $set = null)
	{
		if (! is_numeric($key)) {
			if ($set && !is_numeric($set)) {
				if (! isset($this->_arguments[$set])) {
					$this->_arguments[$set] = array();
				}
				$this->_arguments[$set][$key] = $value;
			} else {
				$this->_arguments[$key] = $value;
			}
			return true;
		} else {
			return false;
		}
	}
	
	
	public function getArguments()
	{
		return $this->_arguments;
	}
	
	
	/**
	 * Unset a previously declared argument or a whole set of them
	 *
	 * @param string $key
	 * @param string $set
	 * @return boolean
	 */
	public function unsetArgument($key = null, $set = null)
	{
		if ($key) {
			
			if ($set) {
				
				unset($this->_arguments[$set][$key]);
			} else {
				
				unset($this->_arguments[$key]);
			}
			return true;
		} else if ($set) {
			
			unset($this->_arguments[$set]);
			return true;
		}
		
		return false;
	}
	
	public function unsetArguments()
	{
		unset($this->_arguments);
		return true;
	}
	
	
	/**
	 * Build an uri from all setted parameters and arguments
	 *
	 * @param array $args
	 * @param boolean $noBase
	 * @return string
	 */
	public function makeUri(array $args = null, $noBase = false)
	{
		//if (is_array($args)) $this->setArguments($args);
		// Omit URI base
		if ($noBase) {
			return $this->_pairsSeparator . $this->_collateArguments($args);
		} else {
			return $this->_uriBase . $this->_partsSeparator . $this->_collateArguments($args);
		}
	}
	
	/**
	 * Build a string with all arguments pairs
	 *
	 * @return string
	 * 
	 */
	protected function _collateArguments(array $arguments = null)
	{
		if (is_null($arguments)) {
			$arguments = $this->_arguments;
		}
		$args = array();
		foreach ($arguments as $key => $val) {
			if (is_array($val)) {
				foreach ($val as $valKey => $valValue) {
					$args[] = $this->_collateKeyVal($valKey, $valValue, $key);
				}
			} else {
				$args[] = $this->_collateKeyVal($key, $val);
			}
		}
		return implode($args, $this->_pairsSeparator);
	}
	
	
	/**
	 * Returns a properly formatted key/value pair within the uri
	 * ex: "key=val", "key/val", "set[key]=val", "set[key]/val"
	 *
	 * @param string $key
	 * @param string $val
	 * @param string $set
	 * @return string
	 */
	protected function _collateKeyVal($key, $val = null, $set = null)
	{
		return sprintf("%s%s%s"
									, $set ? $set . '[' . rawurlencode($key) . ']' : rawurlencode($key)
									, $this->_assignSeparator
									, $this->_escape($val)
					  );
	}
	
	
	/**
	 * Returns the value of the given uri identifier name
	 *
	 * @param string $identifierName
	 * @return string
	 */
	public function getIdentifier($identifierName)
	{
		if (isset($this->_identifiers[$identifierName])) {
			return $this->_identifiers[$identifierName];
		} else {
			return false;
		}
	}
	
	
	/**
	 * Set a new value for an existing identifier name
	 * 
	 * @param string $identifierName
	 * @param string $newName
	 * @return boolean 
	 */
	public function setIdentifier($identifierName, $newName)
	{
		if (isset($this->_identifiers[$identifierName]) && is_string($newName)) {
			$this->_identifiers[$identifierName] = $newName;
			return true;
		} else {
			return false;
		}		
	}
	
	
	/**
	 * Return current $_env value merged with $_defaults value
	 * 
	 * @return array
	 */
	public function getEnv()
	{
		return array_merge($this->_defaults, $this->_env);
	}
	
	
	/**
	 * Set $_env value
	 * 
	 * @param array $env
	 * @return t41\View\ViewUri\AbstractAdapter
	 */
	public function setEnv(array $env)
	{
		$this->_env = $env;
		return $this;
	}
	
	
	/**
	 * Escape the value from a pair
	 *
	 * @param string $str
	 * @return string
	 */
	protected function _escape($str)
	{
		return rawurlencode($str);
	}
	
	
	/**
	 * Save current environment array
	 * 
	 * @return boolean
	 */
	public function saveSearchTerms()
	{
		return Core::sessionAdd('uri' . str_replace('/', '_', $this->_uriBase), $this->getEnv());
	}
	
	
	/**
	 * Gets the latest session-saved environment and inject it in $_env
	 * Returns true if $_env was modified, false otherwise
	 * 
	 * @return boolean
	 */
	public function restoreSearchTerms()
	{		
    	if (count($this->getEnv()) == 0) {
    		$env = Core::sessionGet('uri' . str_replace('/', '_', $this->_uriBase));
	    	if (is_array($env)) {
	    		$this->setEnv($env);
	    		return true;
	    	}
    	}
    	return false;
	}
}