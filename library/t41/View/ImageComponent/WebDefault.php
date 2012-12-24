<?php

namespace t41\View\ImageComponent;

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

use t41\Core,
	t41\View\Decorator\AbstractWebDecorator;


/**
 * Decorator class for image objects in a Web context.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

class WebDefault extends AbstractWebDecorator {

	
	protected $_obj;
	
	protected $_css = 'component_default';
	
	protected $_cssLib = 't41';
	
	protected $_cssStyle = 't41_component_default';
	
	protected $_instanceof = 't41_View_Image';
	
	
	public function render()
	{
		return sprintf('<img src="%s" alt="%s" title="%s" />', $this->_obj->getContent(), $this->_obj->getTitle(), $this->_obj->getTitle());
	}
}