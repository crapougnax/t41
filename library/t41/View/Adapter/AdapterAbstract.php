<?php

namespace t41\View\Adapter;

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
 * @version    $Revision: 911 $
 */

use t41\ObjectModel;

/**
 * Abstract class providing the view engine with basic methods and
 * parameters to handle multiple context adapters.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class AdapterAbstract extends ObjectModel\ObjectModelAbstract implements AdapterInterface {

		
    /**
     * Which type of components are allowed in this view adapter
     *
     * @var array
     */
	protected $_allowedComponents = array();

	
	/**
	 * Components dependencies definition
	 *
	 * @var array
	 */
	protected $_componentDependancies = array();
	
	/**
	 * display context variants
	 *
	 * @var array
	 */
	protected $_displayContexts = array();
	
    /**
     * tableau des composants à insérer dans la vue 
     *
     * @var array
     */
    protected $_component = array();

    /**
     * Tableau des événements à insérer dans la vue
     *
     * @var array
     */
    protected $_event = array();

    /**
     * Contexte d'affichage de la vue
     *
     * @var string
     */
    protected $_context;
    
    /**
     * Possible variante du contexte
     *
     * @var string
     */
    protected $_subContext;
    
    
    protected $_title = null;
    
    
    protected $_template = null;
    
    /**
     * Base directory where components are to be found
     *
     * @var string
     */
	protected $_componentsBasePath;
    
    
    public function __construct(array $parameters = null)
    {
        foreach ($this->_allowedComponents as $allowedComp) {
        	$this->_component[$allowedComp] = array();
        }
        
        if (is_array($parameters)) {
        	
        	$this->_setParameters($parameters);
        }
    }
    
    
    public function setSubContext($context)
    {
    	if (in_array($context, $this->_displayContexts)) {
    		
        	$this->_subContext = $context;
        	
    	} else {
    		
    		throw new Exception("Le sous-contexte $context n'est pas reconnu par la classe " . __CLASS__);
    	}
    }
    
    
    public function getSubContext() 
    {
    	return $this->_subContext;
    }
    
    
    public function getContext()
    {
        return $this->_context;
    }

    
    public function setTitle($title)
    {
        $this->_title = $title;
    }

    
    /**
     * Set template to be used when view rendering will occur.
     * Template can either be an html skeleton or an xml configuration file for the generation of a PDF view
     * 
     * @param string $tpl file name (if template is in views directory) or complete access path
     * @throws t41_View_Exception
     */
    public function setTemplate($tpl)
    {
    	/* reset template */
    	$this->_template = null;
    	
    	foreach (\t41\Config::getPaths(\t41\Config::REALM_TEMPLATES) as $path) {
			
			$filePath = (substr($tpl, 0, 1) == DIRECTORY_SEPARATOR) ? $tpl : $path . $tpl;
			
			if (file_exists($filePath)) {
				
	    		$this->_template = $filePath;
			}
		}
		
		if (is_null($this->_template)) {
			
			throw new Exception("Unable to find '$tpl' template file in paths");
		}
		return;
		
		$basepath = (substr($tpl, 0, 1) != '/') ? 'application/views/' : null;
    	
    	if (file_exists($basepath . $tpl)) {
    		
    		$this->_template = $basepath . $tpl;
    		
    	} else if (\t41\Core::getEnvData('webEnv') == \t41\Core::ENV_DEV) {
    		
			throw new Exception("Unable to find $tpl template in '$basepath");
    	}
    }
    
    
    /**
     * Returns template file name if exists
     * 
     * @return string
     */
    public function getTemplate()
    {
    	return $this->_template;
    }
    
    
	/**
	 * Ajoute un composant externe à la page sous réserve que le fichier de référence existe
	 *
	 * @param string $file
	 * @param string $type
	 * @return boolean
	 */
    public function componentAdd($file, $type, $lib = null, $priority = 0) { }
    
    
    public function mediaAdd($file, $lib = null) { }
    
    
	/**
	 * Ajout d'un événement à la vue
	 *
	 * @param string $event
	 * @param string $type
	 * @param boolean $isFile
	 * @return boolean
	 */
    public function eventAdd($event, $type, $isFile = false) { }

    
    public function eventAttach() { }
    

    public function componentAttach($type = null) { }
    
    
    protected function _renderComponents($type, $params = null) { }
}
