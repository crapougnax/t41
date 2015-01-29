<?php

namespace t41\View\SimpleComponent;

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

use t41\View,
	t41\View\Decorator\AbstractWebDecorator;

/**
 * Decorator class for component objects in a Web context.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class WebAlert extends AbstractWebDecorator {

	
	protected $_css = 'component_default';
	
	
	protected $_cssLib = 't41';
	
	
	protected $_cssStyle = 't41_component_default';

	
	protected $_instanceof = 't41\View\SimpleComponent';
	
	
	public function render()
	{
		View::addCoreLib(array('style.css','view:alert.js'));
		$params = array(  'title' => $this->_obj->getTitle(), 
		                  'level' => 'error', 
		                  'buttons' => array('confirm' => true),
		                  'labels' => array('confirm' => "Afficher mes livraisons"),
		                  'callbacks' => array('confirm' => "document.location.replace('/livraisons/livraison/validation')")
		               );
		$event = sprintf('new t41.view.alert("%s",%s)'
		    , implode('<br/>',$this->_obj->getContent())
		    , \Zend_Json::encode($params)
		);
		View::addEvent($event, 'js');
	}
}
