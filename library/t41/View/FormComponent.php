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

	
	const SEARCH_MODE = 'search';
	
	
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
		parent::__construct(null, $params);
    						  	   
		$this->_adapter = $adapter ? $adapter : new FormComponent\Adapter\DefaultAdapter();
		if ($source) {
			$this->setSource($source);
		}
	}
	
	
	public function getAdapter()
	{
		return $this->_adapter;
	}
	

	/**
	 * Set form source which could be a BaseObject or a DataObject instance
	 * @param t41\ObjectModel\BaseObject|t41\ObjectModel\DataObject $source
	 */
	public function setSource($source)
	{
		if ($this->_source) {
			throw new Exception("Source can only be defined once");
		}
		
		if ($source instanceof ObjectModel\BaseObject) {
			$this->_source = $source;
			$this->_adapter->build($this->_source->getDataObject(), $this->getParameter('display'), $this->getParameter('identifier'));
				
		} else if ($source instanceof ObjectModel\DataObject) {
			$this->_source = $source->getDataObject();
			$this->_adapter->build($this->_source, $this->getParameter('display'), $this->getParameter('identifier'));
				
		} else if (is_string($source)) {
			$this->_source = ObjectModel\DataObject::factory($source);
			$this->_adapter->build($this->_source, $this->getParameter('display'), $this->getParameter('identifier'));
		}
		
		if ($this->_source->getUri() !== null) {
			$this->setParameter('buttons','savecancel');
		}
		
		return $this;
	}
	
	
	public function getSource()
	{
		return $this->_source;
	}
	
	
	/**
	 * Returns the form element matching the given key
	 * @param string $key
	 * @return t41\View\FormComponent\Element\AbstractElement
	 */
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
    	return $this->getAdapter()->getElements();
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

    		$class = new $class($id == self::USE_ID ? $this->getParameter('rowid') : $id);
    	}
    		
	    try {
    		
    		$result = $class->$method($data);
    		
    	} catch (Exception $e) {
    		
    		var_dump($e);
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
    
    
    public function reduce(array $params = array())
    {
    	$elements = array();
    	foreach ($this->_adapter->getElements() as $element) {
    		$elements[$element->getId()] = $element->reduce();
    	}
    	return array_merge(parent::reduce($params), array('elements' => $elements));
    }
}
