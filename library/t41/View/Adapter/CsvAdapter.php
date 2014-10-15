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
 */

use t41\View;
use t41\View\Decorator;
use t41\Parameter;

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
	
	protected $_allowedEvents = array();
	
	protected $_componentsBasePath;
	
	
	public function __construct(array $parameters = null)
	{
		error_reporting(E_ALL);
		
		$params = array();
		$params['title'] = new Parameter(Parameter::STRING);
		$params['destination']	= new Parameter(Parameter::STRING, 'D'); // D = download
		
		$this->_setParameterObjects($params);
		parent::__construct($parameters);
	}

    
    public function display()
    {
    	if ($this->getParameter('destination') == 'D') {
	    	$filename = $this->getParameter('title') ? $this->getParameter('title') : 'Export';
    		$filename .= '.csv';
    	
        	header("Content-type: text/x-csv");
        	header("Content-Disposition: attachment; filename=\"$filename\"");
    	}
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
    			
    			/* @var $object t41\View\ViewObject */ 
    			switch (get_class($object)) {
    				
    				case 't41\View\ListComponent':
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
