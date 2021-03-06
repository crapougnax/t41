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
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 865 $
 */

use t41\Core\Layout;
use t41\Core\Acl;


class MenuComponent extends ViewObject {
	
	
	protected $_menu;
	
	
	protected $_role;
	
	
	public function setMenu(Layout\Menu $menu)
	{
		$this->_menu = $menu;
		return $this;
	}
	
	
	public function setRole(Acl\Role $role)
	{
		$this->_role = $role;
		return $this;
	}
	
	
	public function isGranted($resource)
	{
		if (! $this->_role) return true;
		
		return $this->_role->granted($resource);
	}
	
	
	public function getMenu()
	{
		return $this->_menu;
		return $this->_role ? $this->_getAclMenu() : $this->_menu;
	}
	
	
	protected function _getAclMenu()
	{
		$menu = clone $this->_menu;
		foreach ($menu->getItems() as $key => $item) {
			
			if (! in_array($key . '/' . $item->getId(), $this->_role->getPrivileges())) {
				
			}
		}
	}
}
