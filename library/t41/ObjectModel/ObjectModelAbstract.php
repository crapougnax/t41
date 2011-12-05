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
 * @version    $Revision: 865 $
 */

/**
 * Class providing basic functions needed to handle environment building.
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class ObjectModelAbstract {

	
	/**
	 * Object unique identifier
	 *
	 * @var string
	 */
	protected $_id;
	
	
	/**
	 * Object parameters
	 * a collection of t41_Parameter instances
	 *
	 * @var array
	 */
	protected $_params = array();
	
	
	/**
	 * Class constructor, defines object id, parameters and their values
	 * 
	 * @param string $id
	 * @param array $params
	 */
	public function __construct($id = null, array $params = null)
	{
		if (! is_null($id)) $this->setId($id);

		$this->_setParameterObjects();
									
		if (is_array($params)) {
			$this->_setParameters($params);
		}
	}
	
	
	/**
	 * Sets object id
	 * 
	 * @param string $id
	 * @return t41_Object_Abstract
	 */
	public function setId($id)
	{
		if (! is_null($this->_id)) {
			
			throw new Exception(array("OBJECT_CANNOT_CHANGE_VALUE", '$id'));
		}
		
		$this->_id = $id;
		return $this;
	}
	
	
	public function getId() 
	{ 
		return $this->_id;
	}		
	
	
	/**
	 * PARAMETERS HANDLING METHODS SECTION
	 */
	
	
	/**
	 * Permet de récupérer la valeur d'un paramètre
	 *
	 * @param string $key
	 * @return mixed valeur du paramètre
	 */
	final public function getParameter($key)
	{
		if (isset($this->_params[$key]) && $this->_params[$key] instanceof \t41\Parameter) {
			return $this->_params[$key]->getValue();
		} else {
			return null;
		}
	}
	
	/**
	 * Permet de définir la valeur d'un paramètre ou modifier la valeur d'un paramètre non protégé
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function setParameter($key, $value)
	{
		if (isset($this->_params[$key]) && $this->_params[$key] instanceof \t41\Parameter) {
			
			try {
				$this->_params[$key]->setValue($value);
				
			} catch (Exception $e) {
				
				throw new Exception("Impossible de definir le parametre '$key' avec la valeur '$value' : " . $e->getMessage());
			}
		}
		
		return $this;
	}
	
	
	/**
	 * Allow definition of multiple parameters from an array
	 *
	 * @param array $params
	 */
	final protected function _setParameters(array $params)
	{
		foreach ($params as $key => $value) {
			
			$this->setParameter($key, $value);
		}
	}
	
	
	/**
	 * Allow to add multiple parameters to the class 
	 *
	 * @param array $objects	if parameter is null, parameters are acquired from xml configuration files
	 * @param boolean $replace
	 */
	final protected function _setParameterObjects(array $objects = null, $replace = false)
	{
		if (is_null($objects)) {
			
			require_once 't41/Parameter.php';
			$objects = \t41\Parameter::getParameters($this);
		}
		
		if ($replace === true) {
			$this->_params = array();
		}
		
		foreach ($objects as $key => $object) {
			
			if (! $object instanceof \t41\Parameter ) {
				continue;
			}
			
			$this->_params[$key] = $object;
		}
	}
	
	
	public function __clone()
	{
		foreach ($this->_params as $key => $param) {
			
			$this->_params[$key] = clone $param;
		}
	}
}
