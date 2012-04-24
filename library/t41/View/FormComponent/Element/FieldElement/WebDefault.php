<?php

namespace t41\View\FormComponent\Element\FieldElement;

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

	
	public function __construct($obj, array $params = null)
	{
		/**
		 * mode:	if set to t41_Form::SEARCH value, field identifiers are modified to accomodate search form version
		 * data:	data array
		 * args:	extra args array (key and value) to be inserted in the field rendering
		 */
		$this->_setParameterObjects(array('mode' => new Parameter()
										, 'data' => new Parameter()
										, 'args' => new Parameter(Parameter::MULTIPLE)
										 )
								   );
		parent::__construct($obj, $params);
	}
	
	
	public function render()
	{
		/* bind optional action to field */
		if ($this->_obj->getAction()) {

			$this->_bindAction($this->_obj->getAction());
			View::addEvent(sprintf("jQuery('#%s').focus()", $this->_id), 'js');
		}
		
		$name =  $this->getId();

		$extraArgs = '';
		if ($this->getParameter('args')) {

			foreach ($this->getParameter('args') as $argKey => $argVal) {
				$extraArgs .= sprintf(' %s="%s"', $argKey, $argVal);
			}
		}
//		$size = ($this->getParameter('mode') == t41_Form::SEARCH) ? round(t41_View_Web_Decorator::FIELD_SIZE/2) : t41_View_Web_Decorator::FIELD_SIZE;
		$size = 30;
		$max = $this->_obj->getValueConstraint('maxval');
		$html  = sprintf('<input type="text" name="%s" id="%s" size="%s" value="%s"%s/>'
							, $name
							, $name
							, ($max > $size || $max == 0) ? $size : $max
							, $this->_obj->getValue()
							, $extraArgs
						);
		
		return $html;
	}
}