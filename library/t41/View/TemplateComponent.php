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
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 832 $
 */

use t41\View;
use t41\Config;
use t41\Core;

/**
 * Class providing a simple template component.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class TemplateComponent extends ViewObject {

	
	/**
	 * Array of variable to parse into template
	 * 
	 * @var array
	 */
	protected $_variables = array();
	
	/**
	 * Template content
	 * 
	 * @var string
	 */
	protected $_template;
	
	
	protected $_subTemplates = array();
	
	
	public function __construct($id = null, array $params = null)
	{
		parent::__construct($id, $params);
	}
	
	
	/**
	 * Add a variable name and value
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @return t41_View_Template $this instance
	 */
	public function addVariable($name, $value)
	{
		$this->_variable[$name] = $value;
		return $this;
	}
	
	public function addTemplate(self $template, $placeholder = View::PH_DEFAULT)
	{
		if (! isset($this->_subTemplates[$placeholder])) {
			$this->_subTemplates[$placeholder] = array();
		}
		$this->_subTemplates[$placeholder][] = $template;
		return $this;
	}

	
	/**
     * Load the template file $filename
     * 
     * If path is relative (not starting with a "/"), the base path is prefixed
     * 
     * @param string $filename
     * @param string $module
     * @return t41\View\TemplateComponent $this instance
	 */
	public function load($filename, $module = null)
	{
		$file = View::getAdapter()->loadTemplate($filename, $module);
		if (($this->_template = file_get_contents($file)) === false) {
			throw new Exception(array("ERROR_LOADING_FILE", $filename));
		}
		return $this;
	}
	
	
	public function getTemplate()
	{
		return $this->_template;
	}
	
	
	public function getVariable($name)
	{
		return isset($this->_variable[$name]) ? $this->_variable[$name] : null;
	}
	
	
	public function getSubTemplates($placeholder)
	{
		return isset($this->_subTemplates[$placeholder]) ? $this->_subTemplates[$placeholder] : false;
	}
}
