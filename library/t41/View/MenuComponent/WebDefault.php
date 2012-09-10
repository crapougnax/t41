<?php

namespace t41\View\MenuComponent;

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
 * @version    $Revision: 865 $
 */

use t41\View,t41\View\Decorator\AbstractWebDecorator;

/**
 * Web Decorator for the MenuComponent View Element
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class WebDefault extends AbstractWebDecorator {
	
	
	public function render()
	{
		View::addCoreLib('style.css');
		
		$html = '';
		
		foreach ($this->_obj->getMenu()->getItems() as $moduleKey => $module) {
	
			$menu = '';
	
			foreach ($module->getItems() as $item) {
	
				$menu .= $this->_renderMenu($item, $moduleKey);
			}
			
			if ($menu) {
				
				// top level menu
				$html .= sprintf('<ul><li class="head" data-help="%s"><a class="head">%s</a><div class="drop">%s</div></li></ul>'
						, $this->_escape($module->getHelp())
						, $this->_escape($module->getLabel())
						, $menu
				);
			}
		}
	
		return '<div class="t41 component menu">' . "\n" . $html . "</div>\n";
	}
	

	protected function _renderMenu($item, $moduleKey)
	{
		$html = ''; $sublevel = false;
		
		$resource = $item->getId();
		if (! $item->fullRes) $resource = $moduleKey . '/' . $resource;
		if (! $item->noLink && (! $this->_obj->isGranted($resource) || $item->hidden)) {
		
			return '';
		}
				
		$prefix = sprintf('<li data-help="%s" id="%s">%s</li>' . "\n"
				, $this->_escape($item->getHelp())
				, $this->_makeJsId($item, $moduleKey)
				, $this->_makeLink($item, $moduleKey)
		);
		
		if ($item->getItems()) {
			
			$sublevel = true;
				
			foreach ($item->getItems() as $item2) {
				
				$html .= $this->_renderMenu($item2, $moduleKey);
			}
		}
		
		if ($item->noLink != true) {
			
			 $html = $prefix;
		}

		if ($sublevel && $html) {
				
			$html = $prefix . $html;
				
		}
		
		return $html ? '<ul>' . $html . '</ul>' : '';
	}
	
	
	/**
	 * Return a Javascript id build from parameters
	 * @param t41\Core\Layout\Menu\Item $item
	 * @param string $module
	 * @return string
	 */
	protected function _makeJsId($item, $module = null)
	{
		$rsc = ($item->fullRes != true) ? $module . '/' . $item->getId() : $item->getId(); 
		return str_replace('/','-', $item->getId());
	}
	
	
	/**
	 * Create a link for given item
	 * @param t41\Core\Layout\Menu\MenuItem $item
	 * @param string $module
	 * @return string
	 */
	protected function _makeLink($item, $module = null)
	{
		if ($item->fullRes) {
			$module = null;
		} else {
			if ($module) $module .= '/';
		}
		
		return sprintf('<a%s>%s</a>'
						, $item->noLink ? null : sprintf(' href="/%s%s"', $module, $item->getId())
						, $this->_escape($item->getLabel())
					  );
	}
}
