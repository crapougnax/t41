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
 * @version    $Revision: 865 $
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
class WebDefault extends AbstractWebDecorator {

	
	protected $_css = 'component_default';
	
	
	protected $_cssLib = 't41';
	
	
	protected $_cssStyle = 't41_component_default';

	
	protected $_instanceof = 't41\View\SimpleComponent';
	
	
	public function render()
	{
		return    $this->_headerRendering()
				. $this->_contentRendering()
				. $this->_footerRendering();
	}
	
	
	protected function _headerRendering()
	{
		$status = $this->_obj->getParameter('open_default') ? 'open' : 'close';
		$status .= $this->_obj->getParameter('locked') ? ' locked' : '';
		$title = $this->_obj->getTitle() ? $this->_escape($this->_obj->getTitle()) : 'Component';
		
		$html = <<<HTML
<div class="t41 component" id="{$this->getId()}">
<h4 class="title slide_toggle {$status}"><div class="icon"></div>{$title}</h4>
<div class="content">

HTML;

		return $html;
	}
	
	
	protected function _footerRendering()
	{
		return <<<HTML
</div>
</div>

HTML;
	}
}
