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
 * @version    $Revision: 832 $
 */

use t41\View,
	t41\View\Decorator;

/**
 * Class providing the view engine with a CSV-context adapter.
 * Adaptateur Csv pour le moteur de vue
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class CsvAdapter extends AbstractAdapter {

	
	const ID = 'Csv';
	
	
	protected $_context = 'Csv';
	
	protected $_allowedComponents = array();
	
	protected $_componentDependancies = array();

	protected $_allowedEvents = array();
	
	protected $_displayContexts = array();
	
	protected $_componentsBasePath;
	
	
	/**
	 * Ajoute un composant externe à la page sous réserve que le fichier de référence existe
	 *
	 * @param string $file
	 * @param string $type
	 * @return boolean
	 */
    public function componentAdd($file, $type, $lib = null, $priority = 0)
    {

    }
    
    
    public function mediaAdd($file, $lib = null)
    {
    }
    
    
	/**
	 * Ajout d'un événement à la vue
	 *
	 * @param string $event
	 * @param string $type
	 * @param boolean $isFile
	 * @return boolean
	 */
    public function eventAdd($event, $type, $isFile = false)
    {
    }

    
    public function eventAttach()
    {
    }


    protected function _renderComponents($type, $params = null)
    {
    }

    
    public function display()
    {
    	$filename = $this->_title ? $this->_title : 'Export';
    	$filename .= '.csv';
    	
        header("Content-type: text/x-csv");
        header("Content-Disposition: attachment; filename=\"$filename\"");
    	
    	return $this->_render();
    }

    
    protected function _render()
    {
		$content = null;    	
    	$elems = View::getObjects(View::PH_DEFAULT);
    	
    	if (is_array($elems)) {
    		
    		foreach ($elems as $elem) {
    		
				$object = $elem[0];
    			$params = $elem[1];
    			    					
    			if (! is_object($object)) continue;
    			
    			/* @var $object t41_Form_Abstract */ 
    			switch (get_class($object)) {
    				
    				case 't41\View\TableComponent':
    					$object->setDecorator();
    					$decorator = Decorator::factory($object);
    					$content .= $decorator->render();
    					break;
    					
    				default:
    					break;
    			}
    		}
    	}

    	return $content;
    }
}
