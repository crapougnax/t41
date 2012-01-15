<?php

namespace t41;

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
 * @version    $Revision: 854 $
 */

use t41\View;

/**
 * Static class used for all interactions with the view
 * 
 * @package t41_View
 * @copyright 2006-2009 Quatrain Technologies
 * @version $Revision: 854 $
 */
class View {
	
	const PH_DEFAULT	= 'default';
	
	const PH_LEFT		= 'left';
	
	const PH_RIGHT		= 'right';
	
	const PH_MENU		= 'menu';
	
	const PH_HEADER		= 'header';
	
	const PH_FOOTER		= 'footer';
	
	
	/**
	 * View Adapter type
	 *
	 * @var string
	 */
	private static $_viewType;
	
	
	/**
	 * View object instance
	 *
	 * @var t41_View_Abstract
	 */
	private static $_view = array ();
	
	
	private static $_childView = null;
	
	
	private static $_envData = array();
	
	
	/**
	 * View Adapter instance
	 *
	 * @todo implement support for multiple instances
	 * @var t41_View_Adapter_Abstract
	 */
	private static $_display;
	
	
	/**
	 * Array of view objects
	 * 
	 * @var array
	 */
	private static $_objects = array();
	
	
	/**
	 * Array of view components
	 * 
	 * @var array
	 */
	private static $_components = array();
	
	
	/**
	 * Array of visual themes
	 * 
	 * @var array
	 */
	private static $_theme = array('all' => null);
	
	
	/**
	 * Array of visual color palettes
	 * 
	 * @var array
	 */
	private static $_color = array('all' => null);

	
	private static $_errors = array();
	
	/**
	 * Set display via instanciation of the given view adapter. 
	 * If an adapter has already been declared, it will be silently replaced by the new one
	 *
	 * @param string $adapter Must match one of all available adapter classes ID constant (ex: Web, Pdf, Csv)
	 * @param array $parameters
	 * @throws t41_View_Exception
	 */
	static public function setDisplay($adapter, array $parameters = null)
	{
		$adapter = ucfirst(strtolower($adapter));
		$adapterClass = 't41_View_Adapter_' . $adapter;
		try {
			self::$_display = new $adapterClass($parameters);
			
		} catch (\Exception $e) {
			
			throw new View\Exception("The adapter class '$adapterClass' can't be instanciated: " . $e->getMessage());
		}
		
		// set adapter type (ID const) for futur use
		self::$_viewType = $adapter;
	}
	
	
	/**
	 * Set parameters to view instance
	 *
	 * @param array $params Array of parameters
	 * @param string $view View type to apply parameters to
	 */
	static public function setViewParameters(array $params, $view = null)
	{
		if (self::_isInstanciated()) {
			
			foreach ($params as $key => $val) {
				
				self::$_display->setParameter($key, $val);
			}
		}
	}
	
	/**
	 * Controls wether the given view type has been instanciated. Throws an exception otherwise.
	 *
	 * @param string $view view adapter identifier
	 * @throws t41_View_Exception
	 */
	static protected function _isInstanciated($view = null)
	{
		if (! self::$_display instanceof View\Adapter\AdapterAbstract) {
			
			throw new View\Exception("No view adapter has been selected");

		} else {
			
			return true;
		}
	}
	
	/**
	 * Definit le contexte d'affichage particulier
	 *
	 * @param string $context
	 */
	static public function setDisplayContext($context) {
		
		if (self::_isInstanciated ()) {
			
			self::$_display->setSubContext($context);
		}
	}
	
	/**
	 * Ajout d'une feuille de style CSS à la vue
	 *
	 * @param unknown_type $sheet
	 * @return unknown
	 * @deprecated use t41_View::addRequiredLib() instead
	 */
	static public function addRequiredCssSheet($sheet) {
		if (self::_isInstanciated ()) {
			return self::$_display->componentAdd ( $sheet, 'css' );
		}
	}
	

	static public function addRequiredLib($file, $type, $lib = null, $priority = 0)
	{
		if (self::_isInstanciated ()) {
			
			return self::$_display->componentAdd ($file, $type, $lib, $priority);
		}

		// to be tested
	    if (substr($file, 0, 4) != 'http') {
	        if ($lib) {
		        $filePath = '/lib/' . $lib . '/' . $type . '/' . $file . '.' . $type;
        	} else {
        		$filePath = '/' . $type . '/' . $file . '.' . $type;
        	}
        } else {
        	$filePath = $file;
        }
        
        if (! isset(self::$_components[$type])) {
        	
        	self::$_components[$type] = array();
        }
        // return true if component is already listed
        if (in_array($filePath, self::$_components[$type])) return true;
        
	    if ($priority == -1) {
        		array_unshift(self::$_components[$type], $filePath);
        	} else {
	           self::$_components[$type][] = $filePath;
        	}
        	
        return true;
	}
	
	
	static public function getRequiredLibs($type)
	{
		return (array) self::$_components[$type];
	}
	
	
	
	/**
	 * Returns current View Adapter
	 * 
	 * @return t41_View_Adapter_Abstract
	 */
	static public function getAdapter()
	{
		return self::$_display;
	} 
	
	
	static public function addRequiredMedia($name, $lib = 't41')
	{
		if (self::_isInstanciated ()) {
			
			return self::$_display->mediaAdd($name, $lib);
		}		
	}
	
	/**
	 * Ajoute un evenement javascript à la vue
	 *
	 * @param unknown_type $event
	 * @param unknown_type $isFile
	 * @return unknown
	 * @deprecated use t41_View::addEvent() instead
	 */
	static public function addJsEvent($event, $isFile = false) {
		if (self::_isInstanciated ()) {
			return self::$_display->eventAdd ( $event, 'js', $isFile );
		}
	}
	

	static public function addEvent($event, $type, $isFile = false) {
		if (self::_isInstanciated ()) {
			return self::$_display->eventAdd ( $event, $type, $isFile );
		}
	}
	
	
	static public function getViewType()
	{
		return self::$_viewType;
	}
	
	
	static public function display($content = null, $error = null)
	{
		\t41\Core::setFancyExceptions(false);
		
		if (self::_isInstanciated()) {
			
			self::$_display->display($content, $error);
		}
	}
	
	
	static public function setPageTitle($title)
	{
		if (self::_isInstanciated ()) {
			
			self::$_display->setTitle($title);
		}
	}
	
	
	static public function addObject($object, $container = 'default', array $params = null)
	{
		if (is_null($container)) $container = self::PH_DEFAULT;
		self::$_objects [$container] [] = array($object, $params);
		
		return true;
	}
	

	static public function resetObjects($container = null)
	{
		if (is_null($container)) {
			
			self::$_objects = array();

		} else {
			
			self::$_objects[$container] = array();
		}
		
		return true;
	}
	
	
	
	static public function getObjects($container)
	{
		if (isset(self::$_objects [$container]) && count(self::$_objects[$container]) > 0) {
			
			return self::$_objects [$container];
			
		} else {
			
			return null;
		}
	}
	
	
	static public function getDisplayContext()
	{
		if (self::_isInstanciated ()) {
			return self::$_display->getSubContext();
		} else {
			return false;
		}
	}

	
	static public function getContext()
	{
		if (self::_isInstanciated ()) {
			return self::$_display->getContext();
		} else {
			return false;
		}
	}
	
	
	/**
	 * Define template file to be used during the rendering of the view
	 * 
	 * @param string $tpl	file name or complete path to template
	 * @param string $view	view type to apply template to
	 * 
	 * @todo implement multiple view instances
	 */
	static public function setTemplate($tpl, $view = null)
	{
		if (self::_isInstanciated($view)) {
			
			self::$_display->setTemplate($tpl);
		}
	}
	
	
	/**
	 * Returns template file name or path to be used during the rendering of the view
	 * 
	 * @param string $view view type
	 * @return t41_View_Adapter_Abstract
	 * 
	 * @todo implement mutiple view instances
	 */
	static public function getTemplate($view = null)
	{
		if (self::_isInstanciated ()) {
			return self::$_display->getTemplate();
		}
	}
	
	
	static public function setEnvData($key, $val)
	{
		if (strpos($key, '.') === false) {
			$registre = 'default';
			$cle = $key;
		} else {
			$tmp = explode('.', $key);
			$registre = $tmp[0];
			$cle = $tmp[1];
		}
		
		if (! isset(self::$_envData[$registre])) {
			
			self::$_envData[$registre] = array();
		}
		
		self::$_envData[$registre][$cle] = $val;
	}
	
	
	static public function getEnvData($key)
	{
		if (strpos($key, '.') === false) {
			$registre = 'default';
			$cle = $key;
		} else {
			$tmp = explode('.', $key);
			$registre = $tmp[0];
			$cle = $tmp[1];
		}
		return isset(self::$_envData[$registre][$cle]) ? self::$_envData[$registre][$cle] : null;
	}
	
	
	static public function getBase($str = null)
	{
		if (method_exists(self::$_display, 'getBase')) {
			return self::$_display->getBase($str);
		} else {
			return NULL;
		}
	}
	
	
	static public function setTheme($str, $view = 'all')
	{
		self::$_theme[$view] = $str; 
	}
	
	
	static public function getTheme($view = 'all')
	{
		return isset(self::$_theme[$view]) ? self::$_theme[$view] : self::$_theme['all']; 
	}
	
	
	static public function setColor($str, $view = 'all')
	{
		self::$_color[$view] = $str; 
	}
	
	
	static public function getColor($view = 'all')
	{
		return isset(self::$_color[$view]) ? self::$_color[$view] : self::$_color['all']; 
	}
	
	
	/**
	 * save a PHP user error for later use
	 * 
	 * @param string $message
	 * @param string $trace
	 * @param integer $code
	 */
	static public function addError($message, $trace = null, $code = 0)
	{
		if (! isset(self::$_errors[$code])) {
			
			self::$_errors[$code] = array();
		}
		
		self::$_errors[$code][] = array($message, $trace);
	}
	
	
	/**
	 * Returns an array of saved errors
	 * 
	 *  @return array
	 */
	static public function getErrors($code = null)
	{
		if (! is_null($code) && isset(self::$_errors[$code])) {
			
			return self::$_errors[$code];
		
		} else {
			
			return null;
		}
		
		return self::$_errors;
	}
}