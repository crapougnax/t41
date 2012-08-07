<?php

namespace t41\View\Action\AutocompleteAction;

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
 * @version    $Revision: 876 $
 */

use t41\View\Decorator\AbstractWebDecorator,
	t41\Parameter,
	t41\View,
	t41\Core;

/**
 * Web decorator for the FieldElement class
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class WebDefault extends AbstractWebDecorator {

	
	public function render()
	{
		$id = $this->_nametoDomId($this->_obj->getBoundObject()->getId());
		$event = sprintf("t41.view.registry['%s'] = new t41.view.action.autocomplete(%s,'%s')"
						, $id
						, \Zend_Json::encode($this->_obj->reduce(array('params' => array(), 'collections' => 1)))
						, $id
						);
		View::addEvent($event, 'js');
		View::addEvent(sprintf("t41.view.registry['%s'].init()", $id), 'js');
	}
}
