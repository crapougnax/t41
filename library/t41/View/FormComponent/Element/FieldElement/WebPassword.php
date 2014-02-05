<?php

namespace t41\View\FormComponent\Element\FieldElement;

use t41\ObjectModel\Property;

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
	t41\View,
	t41\View\ViewUri;

/**
 * Web decorator for the FieldElement class
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class WebPassword extends AbstractWebDecorator {

	
	public function render()
	{
		/* bind optional action to field */
		if ($this->_obj->getAction()) {

			$this->_bindAction($this->_obj->getAction());
			View::addEvent(sprintf("jQuery('#%s').focus()", $this->_id), 'js');
		}
		
		$name =  $this->getId();
		
		if ($this->getParameter('mode') == View\FormComponent::SEARCH_MODE) {
			$name = ViewUri::getUriAdapter()->getIdentifier('search') . '[' . $this->_nametoDomId($name) . ']';
		}

		$extraArgs = '';
		if ($this->getParameter('args')) {
			foreach ($this->getParameter('args') as $argKey => $argVal) {
				$extraArgs .= sprintf(' %s="%s"', $argKey, $argVal);
			}
		}

		$size = $this->getParameter('length');
		$max = $this->_obj->getConstraint(Property::CONSTRAINT_MAXLENGTH);
		if ($this->_obj->getValue()) {
			$html  = sprintf('<input type="text" name="%s" id="%s" placeholder="%s" size="%s"%s value="********"%s/>'
								, $name
								, $this->_obj->getId()
								, $this->_obj->getHelp()
								, ($max > $size || $max == 0) ? $size : $max
								, $max ? ' maxlength="' . $max . '"' : null
								, $extraArgs
							);
		
			$html .= sprintf(' <a href="#" onclick="jQuery(\'#%s\').val(\'%s\').focus();this.remove()">Regénérer</a>'
					, $this->_obj->getId()
					, self::password()
			);
		} else {
			$html  = sprintf('<input type="text" name="%s" id="%s" placeholder="%s" size="%s"%s value="%s"%s/>'
					, $name
					, $this->_obj->getId()
					, $this->_obj->getHelp()
					, ($max > $size || $max == 0) ? $size : $max
					, $max ? ' maxlength="' . $max . '"' : null
					, $this->_obj->getValue()
					, $extraArgs
			);			
		}
		$this->addConstraintObserver(array(Property::CONSTRAINT_UPPERCASE,Property::CONSTRAINT_LOWERCASE));
		
		return $html;
	}
	
	
	static public function password($length = 8, $stronger = false)
	{
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		if ($stronger) $chars .= "!@#$%^&*()_-=+;:,.?";
		return substr( str_shuffle( $chars ), 0, $length );
	}
}
