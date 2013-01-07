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
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 911 $
 */

use t41\ObjectModel\ObjectModelAbstract,
	t41\Config,
	t41\Core;

/**
 * Abstract class providing the view engine with basic methods and
 * parameters to handle multiple context adapters.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class AbstractAdapter extends ObjectModelAbstract implements AdapterInterface {

		
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
     * Templates are searched in module views/ folder bu default then in the global views/ folder
     * 
     * @param string $tpl file name (if template is in views directory) or complete access path
     * @throws t41\View\Exception
     */
    public function setTemplate($tpl)
    {
    	/* reset template */
    	$this->_template = null;
    	
    	$paths = Config::getPaths(Config::REALM_TEMPLATES);
    	if (Core\Layout::$module) {
    		$path = Core::$basePath . 'application/modules/' . Core\Layout::$vendor . DIRECTORY_SEPARATOR
    			  . Core\Layout::$moduleKey . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
    		if (is_dir($path)) {
    			array_unshift($paths, $path);	
    		}
    	}
    	$files = Config\Loader::findFile($tpl, $paths);
    	
    	foreach ($files[Config::DEFAULT_PREFIX] as $file) {
    		if (file_exists($file)) {
	    		$this->_template = $file;
	    		break;
			}
		}
		
		if (is_null($this->_template)) {
			throw new Exception("Unable to find '$tpl' template file in paths");
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
