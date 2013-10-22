<?php

namespace t41\View\FormComponent\Element;

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

use t41\Core;

/**
 * t41 Data Object handling a set of properties tied to an object
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class MultipleElement extends AbstractElement {
	

	public function setValue($val)
	{
		$this->_value = explode('|',$val);
		return $this;
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see \t41\View\FormComponent\Element\AbstractElement::formatValue()
	 */
	public function formatValue($val = null)
	{
		$formatted = '';
		
		foreach ($val as $v) {
			if (isset($this->_enumValues[$v])) {
				if ($formatted) $formatted .= ', ';
				$formatted .= is_array($this->_enumValues[$v]) ? $this->_enumValues[$v][Core::$lang] : $this->_enumValues[$v];
			}
		}
		return $formatted;
	}
}
