<?php

namespace t41\Backend;

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
 * @version    $Revision: 880 $
 */

/**
 * Class used to identify a backend
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * 
 */


class Uri {
	
	/**
	 * Alias name as defined
	 *
	 * @var string
	 */
	protected $_alias;
	
	/**
	 * Uri type (pseudo protocol)
	 *
	 * @var string
	 */
	protected $_type;
	
	/**
	 * Username
	 *
	 * @var string
	 */
	protected $_username;
	
	/**
	 * Password
	 *
	 * @var string
	 */
	protected $_password;
	
	/**
	 * Host part
	 *
	 * @var string
	 */
	protected $_host;
	
	/**
	 * Port part
	 *
	 * @var integer
	 */
	protected $_port;
	
	
	/**
	 * 
	 * Datastore name (may be DB or collection or any other meaningful value for backend)
	 * @var string
	 */
	protected $_dbname;
	
	/**
	 * Url part
	 * Defines access path to the ressource within the backend
	 *
	 * @var string
	 */
	protected $_url;
	
	/**
	 * Construction d'une uri à partir d'une chaine ou d'un array
	 *
	 * @param string|array $params
	 */
	public function __construct($params = null)
	{
		if (is_array($params)) {
			
			if (!empty($params['alias'])) {
				$this->_alias = $params['alias'];
			}
			
			if (! empty($params['adapter'])) {
				$this->_type = $params['adapter'];
			}
				
			if (! empty($params['username'])) {
				
				$this->_username = $this->_getConfigValue($params['username']);		
			}
				
			if (! empty($params['password'])) {
				
				$this->_password = $this->_getConfigValue($params['password']);	
			}
				
			if (! empty($params['host'])) {
				
				$this->_host = $this->_getConfigValue($params['host']);
			}
				
			if (! empty($params['port'])) {
				
				$this->_port = (int) $this->_getConfigValue($params['port']);
			}
				
			if (! empty($params['url'])) {
				
				$url = is_array($params['url']) ? $params['url'][t41_Core::getEnvData('webEnv')] : $params['url'];
				$this->_url = $this->_getConfigValue($url);
				$elem = explode("/", $this->_url);
				$this->_dbname = $elem[0];
			}
				
		} else if (is_string($params)) {
			$regexp = "#(^(@[a-z0-9._-]+)|(([a-z0-9._-]+)://)?(((?<=://)[a-z0-9._-]+)?(?::)?((?<=:)[a-z0-9._-]+)?@)?([a-z0-9.-]+)?(?::)?((?<=:)[0-9]+)?)?(?:/)([a-z0-9._/-]+)?$#i";
			$array = null;
			preg_match($regexp, $params, $array);
			
			$this->_alias		= $array[2];
			$this->_type		= $array[4];
			$this->_username	= $array[6];
			$this->_password	= $array[7];
			$this->_host		= $array[8];
			$this->_port		= $array[9];
			$this->_url			= $array[10];
		}
		
//		Zend_Debug::dump($this); die;
	}
	
	
	
	/**
	 * Define backend alias
	 * 
	 * @param string $str
	 * @return t41_Backend_Uri
	 */
	public function setAlias($str)
	{
		$this->_alias = $str;
		return $this;
	}
	

	/**
	 * Define backend type
	 * 
	 * @param string $type
	 * @return t41_Backend_Uri
	 */
	public function setType($type)
	{
		$this->_type = $type;
		return $this;
	}
	
	
	public function getAlias()
	{
		return $this->_alias;
	}
	
	
	public function getType()
	{
		return $this->_type;
	}
	
	
	public function getUsername()
	{
		return $this->_username;
	}

	
	public function getPassword()
	{
		return $this->_password;
	}
	
	
	public function getHost()
	{
		return $this->_host;
	}
	
	public function getPort()
	{
		return $this->_port;
	}
	
	
	public function getUrl()
	{
		return $this->_url;
	}
	
	
	public function getDbName()
	{
		return $this->_dbname;
	}
	
	
	public function overwriteUrl($url)
	{
		if (is_string($url)) {
			$this->_url = $url;
		}
	}
	

	/**
	 * Créer l'uri d'un backend à partir d'un objet Zend Config
	 *
	 * @param Zend_Config $config
	 * @return t41_Uri
	 */
	static public function backendConfigToUri(SimpleXMLElement $config)
	{
		$params = array();
		$attributes = (array) $config->attributes();
		
		if (isset($attributes['@attributes']['alias'])) {
			$params['alias'] = '@' . $attributes['@attributes']['alias'];
		}

		foreach ($config as $key => $val) {

			$params[$key] = (string) $val;
		}
		
		return new t41_Object_Uri($params);
	}
	
	
	protected function _getConfigValue($var)
	{
		if (is_string($var)) {
			
			return $var;
			
		} else {
			
			return (isset($var[t41_Core::getEnvData('webEnv')])) ? $var[t41_Core::getEnvData('webEnv')] : null; 
		}
	}
	
	
	public function __toString()
	{
		return t41_Backend::PREFIX. $this->_alias . '/';
	}
}