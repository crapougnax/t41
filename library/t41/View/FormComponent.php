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
 * @version    $Revision: 865 $
 */

use t41\View,
	t41\ObjectModel,
	t41\View\FormComponent;

/**
 * Class providing form objects
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2009 Quatrain Technologies SAS (http://technologies.quatrain.com)
 * @license    http://t41.quatrain.com/license/new-bsd     New BSD License
 */
class FormComponent extends View\ViewObject {

	/**
	 * Form Adapter object
	 * 
	 * @var t41_View_Form_Adapter_Abstract
	 */
	protected $_adapter = null;
	
	protected $_source;
	
	protected $_preActions = array();
	
	protected $_postActions = array();

	protected $_columns;
	
	
	public function __construct($source = null, array $params = null, FormComponent\Adapter\AbstractAdapter $adapter = null)
	{
/*    	$this->_setParameterObjects(
    								array(
    									  'redirect_on_success' 	=> new t41_Parameter(t41_Parameter::STRING)
    									, 'redirect_on_failure' 	=> new t41_Parameter(t41_Parameter::STRING)
    									, 'redirect_on_condition'	=> new t41_Parameter(t41_Parameter::MULTIPLE) // array of field, value, comparison operator
    									  // a callback action is defined in an array with a class, a method and optionals object id and params
    									  // action will be delegated treatement on submit, it must return a status
    									, 'callback_action'			=> new t41_Parameter(t41_Parameter::MULTIPLE) // 0 = class, 1 = method, 2 = id, 3 = params
    									, 'columns'					=> new t41_Parameter(t41_Parameter::MULTIPLE)
    									 )
    						  	   );
*/
		parent::__construct('toto', $params);
//    	if (is_array($params)) $this->_setParameters($params);
    						  	   
		$this->_adapter = $adapter ? $adapter : new FormComponent\Adapter\DefaultAdapter();
		
		if ($source instanceof ObjectModel\BaseObject) {
			
			$this->_source = $source;
			$this->_adapter->build($this->_source->getDataObject());
			
		} else if ($source instanceof ObjectModel\DataObject) {
			
			$this->_source = $source->getDataObject();
			$this->_adapter->build($this->_source);
			
		} else if (is_string($source)) {
			
			$this->_source = ObjectModel\DataObject::factory($source);
			$this->_adapter->build($this->_source);
				
		}
	}
	
	
	public function getAdapter()
	{
		return $this->_adapter;
	}
	

	public function getSource()
	{
		return $this->_source;
	}
	
	
	public function getElement($key)
	{
		return $this->_adapter->getElement($key);
	}
	
	
	/**
	 * Adds a button to the form with optional image and returns it 
	 * (and not form object as do other methods)
	 * 
	 * @param string $label
	 * @param t41_View_Image $image
	 * @return t41_View_Form_Element_Button
	 */
/*	public function addButton($key, $labelOrImage = null)
	{
		$button = new t41_View_Form_Element_Button($key);
		
		if ($labelOrImage instanceof t41_View_Form_Element_Button) {
			$button->setImage($labelOrImage);
		} else {
			$button->setLabel($labelOrImage ? $labelOrImage : 'Untitled button');
		}
		$this->_buttons[$key] = $button;
		return $button;
	}
	*/
	
	public function getButton($key)
	{
		return isset($this->_buttons[$key]) ? $this->_buttons[$key] : false;
	}

	
    /**
     * Define an array of printable columns based on list or setted parameter
     * @return array
     */
    public function getColumns()
    {
    	if (! is_array($this->getParameter('display')) || count($this->getParameter('display')) == 0) {
    		
    		return $this->getAdapter()->getElements();
    	}
    	
    	if (! is_array($this->_columns)) {
    		
	    	$fields = $this->getAdapter()->getElements();
    		$this->_columns = array();
    		
    		foreach ($this->getParameter('display') as $column) {
    		
    			if (isset($fields[$column])) {
    			
    				$this->_columns[] = $fields[$column];
    				
    			} else {
    				
    				$this->_columns[] = new t41_View_Form_Element_Generic($column);
    			}
    		}
    	}
    	
    	return $this->_columns;
    }
    
    
	/**
     * Define an action to execute after the form has been successfully saved
     *
     * @param string|object $class
     * @param string $method
     * @param mixed $id
     * @param array $params
     * @param boolean $first
     * @return integer index key of the action
     */
    public function setPostAction($class, $method, $id = null, array $params = null, $first = false)
    {
    	$array = array(	'class'		=> $class
    				,	'method'	=> $method
    				,	'id'		=> $id
    				,	'params'	=> $params
      		  		  );
    	if ($first === true) {
    		
    		array_unshift($this->_postActions, $array);
    	
    	} else {
	    	$this->_postActions[] = $array;
    	}
    						  
    	return count($this->_postActions) - 1;
    }
    
    
    /**
     * Define an action to execute before the form is submitted
     *
     * @param string|object $class
     * @param string $method
     * @param array $params
     * @return integer index key of the action
     */
    public function setPreAction($class, $method, $id = null, array $params = null)
    {
    	$this->_preActions[] = array(	'class'		=> $class
    								,	'method'	=> $method
    								,	'id' 		=> $id
    								,	'params'	=> $params
    						  		 );
    						  
    	return count($this->_preActions) - 1;
    }
    
	
    protected function _executeAction($class, $method, $id, $data)
    {
    	$result = null;
    	
    	if (! is_object($class)) {

    		@Zend_Loader::loadClass($class);
    		$class = new $class($id == self::USE_ID ? $this->getParameter('rowid') : $id);
    	}
    		
	    try {
    		
    		$result = $class->$method($data);
    		
    	} catch (Exception $e) {
    		
    		die($e->getMessage());
    	}
    	
    	return $result;
    }
    

    protected function _executePreActions($data)
    {
    	if (count($this->_preActions) == 0) {
    		
    		return $data;
    	}
    	
        foreach ($this->_preActions as $action) {
    			
    		// define action dataset with optional predefined values
    		$paData = isset($action['params']['values']) ? $action['params']['values'] : $data;

    		foreach ((array) $action['params']['mapping'] as $fromKey => $toKey) {
    				
    			if (isset($data[$fromKey])) {
    					
    				$paData[$toKey] = $data[$fromKey];
    			}
    		}
    			
    		$paData = $this->_executeAction($action['class'], $action['method'], $action['id'], $paData);
    	}
    	
    	return $paData;
    }
    
    
    /**
     * Execute all registered actions after saving occured
     * 
     * @param array $data
     */
    protected function _executePostActions(array $data)
    {
        foreach ($this->_postActions as $action) {
    			
    		// define action dataset with optional predefined values
    		$paData = isset($action['params']['values']) ? $action['params']['values'] : $data;

    		foreach ((array) $action['params']['mapping'] as $fromKey => $toKey) {
    				
    			if (isset($data[$fromKey])) {
    					
    				$paData[$toKey] = $data[$fromKey];
    			}
    		}

    		$this->_executeAction($action['class'], $action['method'], $action['id'], $paData);
    	}
    }
    
    
	/**
	 * Form Factory pattern
	 *
	 * @param string $type
	 * @param mixed $id
	 * @param array $params
	 * @return t41_Form_Abstract
	 */
	public static function factory($source = null, $adapter = 'Default', array $params = null) {
		
		$class = 't41_View_Form_Adapter_' . ucfirst(strtolower($adapter));
		
		try {
			
			Zend_Loader::loadClass($class);
			$adapter = new $class();
			return new self($source, $adapter, $params);
			
		} catch (t41_View_Exception $e) {
			
			die($e->getMessage());
			
		} catch (Exception $e) {
			
			throw new t41_View_Exception($e->getMessage() . "\n" . $e->getTraceAsString());
		}
	}
}
