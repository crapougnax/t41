<?php

namespace t41\Core\Layout;

use t41\ObjectModel\ObjectModelAbstract,
	t41\Core\Layout\Menu,
	t41\Core\Layout\Menu\MenuItem;


class Menu extends ObjectModelAbstract {


	/**
	 * Menu element label
	 * @var string
	 */
	protected $_label;
	
	
	/**
	 * Menu element help text
	 * @var string
	 */
	protected $_help;
	
	
	/**
	 * Menu element optional sub-elements
	 * @var array
	 */
	protected $_items = array();
	
	
	/**
	 * Declaration of menu item extension coming from another menu item
	 * @var array
	 */
	protected $_extends = array();
	
	
	/**
	 * Optional module id
	 * This is useful when menu item comes from a different module
	 * @var string
	 */
	protected $_module;
	
	
	/**
	 * 
	 * @var boolean
	 */
	public $noLink = false;
	
	
	/**
	 * Is menu item hidden?
	 * @var boolean
	 */
	public $hidden = false;
	
	
	/**
	 * Is Id a full resource identifier
	 * @var boolean
	 */
	public $fullRes = false;
	
	
	public function getLabel()
	{
		return $this->_label;
	}

	public function getHelp()
	{
		return $this->_help;
	}
	
	public function getItems()
	{
		return $this->_items;
	}

	
	/**
	 * Return the menu item matching the given key
	 * @param string $key
	 * @return t41\Core\Layout\Menu\MenuItem
	 */
	public function getItem($key)
	{
		return isset($this->_items[$key]) ? $this->_items[$key] : false;
	}
	
	
	public function setLabel($str)
	{
		$this->_label = $str;
		return $this;
	}

	
	public function setHelp($str)
	{
		$this->_help = $str;
		return $this;
	}
	
	
	/**
	 * Add items to current menu with optional module id
	 * @param array $array
	 * @param string $module
	 */
	public function setItems(array $array, $module = null)
	{
		foreach ($array as $key => $val) {
			
			$this->addItem($key, $val, $module);
		}
		return $this;
	}
	
	
	public function addItem($id, $val, $module = null)
	{
		$menu = new MenuItem();
		
		if ($module) {
			$id = $module . '/' . $id;
			$menu->fullRes = true;
		}
		
		$menu->setId($id);
		
		if (isset($val['label'])) $menu->setLabel($val['label']);
		if (isset($val['help'])) $menu->setHelp($val['help']);
		if (isset($val['items']) && is_array($val['items'])) {
			$menu->setItems($val['items'],  $module);
		}
		if (isset($val['nolink'])) $menu->noLink = true;
		if (isset($val['hidden'])) $menu->hidden = true;
//		if (isset($val['fullres'])) $menu->fullRes = true;
		
		$this->_items[$id] = $menu;
		return $this;
	}

	
	/**
	 * 
	 * @param string $id	Module base (not id)
	 * @param array $val
	 */
	public function registerExtends($id, $val)
	{
		$this->_extends[$id] = $val;
		return $this;
	}
	
	
	
	public function proceedExtends()
	{
		foreach ($this->_extends as $module => $controllers) {
			
			foreach ($controllers as $controller => $data) {
				
				if ($this->getItem($controller) == false) {
					
					throw new \Exception("Can't extend not existing menu " . $controller);
				}
				
				$this->getItem($controller)->setItems($data['items'], $module);
				unset($this->_extends[$module]);
			}
		}
		
		//\Zend_Debug::dump($this->_items); die;
	}
	
	
	/**
	 * Reduce menu and menu item
	 * @see t41\ObjectModel.ObjectModelAbstract::reduce()
	 * @return array
	 */
	public function reduce(array $params = null)
	{
		$items = array();
		foreach ($this->_items as $key => $val) {
			
			$items[str_replace('/','-',$key)] = $val->reduce();
		}
		
		$array = array('label' => $this->_label, 'help' => $this->_help);
		if ($this->noLink !== true) $array['rsc'] = $this->_id;
		if (count($items) > 0) $array['items'] = $items;
		
		return $array;
	}
}
