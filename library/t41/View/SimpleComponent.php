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
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 832 $
 */

use t41\View,
	t41\Parameter;

/**
 * Class providing methods and parameters to component objects.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */


class SimpleComponent extends ViewObject {
	
	public function __construct($id = null, array $params = null)
	{
		$this->_setParameterObjects(array(	
								'open_default'		=> new Parameter(Parameter::BOOLEAN, true),
								'columns'			=> new Parameter(Parameter::BOOLEAN, false),
								'locked'			=> new Parameter(Parameter::BOOLEAN, false)
							  ));
		parent::__construct($id, $params);
	}
	
	/**
	 * Returns the number of lines depending on Parameter 'columns'
	 * which splits in two columns
	 *
	 * @return int
	 */
	public function lines($floor=true)
	{
		$cols = count($this->_columns);
		$round = $floor ? 'floor' : 'ceil';
		
		if ($this->getParameter('columns')) {
			$lines = ($cols % 2 == 0) ? $cols / 2 : $round($cols / 2);
		} else {
			$lines = $cols;
		}
		
		return $lines;
	}
	
}
