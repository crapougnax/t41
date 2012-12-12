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
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 876 $
 */

use t41\Backend,
	t41\Core;

/**
 * Class providing exchange interface with data sources
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class ObjectUri implements Core\ClientSideInterface {
	
	
	const IDENTIFIER = '#identifier';
	

	/**
	 * Backend uri object
	 *
	 * @var \t41\Backend\BackendUri
	 */
	protected $_backendUri;
	
	
	/**
	 * Class name of the object matching this uri
	 * 
	 * @var string
	 */
	protected $_class;
	
	/**
	 * Url part
	 * Defines access path to the ressource within the backend
	 * typically database/table/primarykey
	 *
	 * @var string
	 */
	protected $_url;
	
	
	/**
	 * @var $_identifier string
	 */
	protected $_identifier;
	
	
	/**
	 * Instanciate an object uri from a string 
	 * (ex: '1', '/1', '@backend/1', '/table/1', '@backend/table/1', 'mysql://user:pwd@host:port/database/table/1')
	 *
	 * @param string $str
	 * @param t41\Backend\BackendUri $backendUri
	 */
	public function __construct($str = null, Backend\BackendUri $backendUri = null)
	{
		if (! is_null($str)) {
			$this->setUrl($str);
			
			$parts = explode('/', $str);
			
			// we only got one identifier, use default backend
			if (count($parts) == 1) {
				$this->_backendUri = $backendUri ? $backendUri->getUri() : Backend::getDefaultBackend()->getUri();
				$this->_identifier = $str;
			
			} else {
				if (substr($parts[0], 0, 1) == Backend::PREFIX) {
					$this->_backendUri = $backendUri ? $backendUri : Backend::getBackendUri($parts[0]);
					$this->_identifier = $parts[count($parts) - 1];
					unset($parts[count($parts) - 1]);
					unset($parts[array_search($this->_backendUri->getDbName(), $parts)]);
					unset($parts[array_search($this->_backendUri->getAlias(), $parts)]);
					$this->_url = implode('/', $parts);
				
				} else if ($backendUri) {
					$this->_backendUri = $backendUri ? $backendUri : Backend::getBackendUri($parts[0]);
					$this->_url = $str;
					
				} else {
					// uri contains a backend reference which is not an alias
					// @todo implement tests
					throw new Exception('litteral backend definition not yet implemented');
				}
			}
		}
	}
	
	
	public function setBackendUri(Backend\BackendUri $uri)
	{
		$this->_backendUri = $uri;
	}
	
	
	/**
	 * Returns t41_Backend_Uri instance
	 * 
	 * @return t41_Backend_Uri
	 */
	public function getBackendUri()
	{
		return $this->_backendUri;
	}
	
	
	public function setIdentifier($id)
	{
		$this->_identifier = $id;
		return $this;
	}
	
	
	public function getIdentifier()
	{
		return $this->_identifier;
	}
	
	
	public function getUrl()
	{
		return $this->_url;
	}
	
	
	public function setUrl($url)
	{
		if (is_string($url)) {

			$this->_url = $url;
			$this->_identifier = substr($this->_url, strrpos($this->_url, '/')+1);
		}
		
		return $this;
	}
	
	
	public function setClass($class)
	{
		$this->_class = $class;
		return $this;
	}
	
	
	public function getClass()
	{
		return $this->_class;
	}
	
	
	/**
	 * Returns a string version of the current uri object
	 * 
	 * @todo implement all possible case scenarios (no backend, no alias, etc.)
	 * @return string
	 */
	public function __toString()
	{
		return $this->_backendUri ? $this->_backendUri->__toString() . $this->_url : '';
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see t41\Core.ClientSideInterface::reduce()
	 */
	public function reduce(array $params = array())
	{
		return Core\Registry::set($this);
		//return $this->asString();
	}
	
	
	/**
	 * Returns the correct string version of the object based on the given backend
	 * This is intended mainly for SQL backends where the identifier is almost always used as foreign key
	 * 
	 * @param t41_Backend_Uri $backend
	 * @return string
	 */
	public function asString(BackendUri $backend = null)
	{
		if ($backend && $backend->getAlias() == $this->_backendUri->getAlias()) {
				
			return $this->_identifier;
		
		} else {
		
			return $this->__toString();
		}
	}
}
