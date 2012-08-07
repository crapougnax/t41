<?php

namespace t41\View\FormComponent\Adapter;

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
 * @version    $Revision: 851 $
 */

use t41\ObjectModel\Property\ObjectProperty;

use t41\View;
use t41\ObjectModel;
use t41\ObjectModel\Property;

/**
 * t41 View Form Adapter interface
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
interface AdapterInterface {

	
	public function build(ObjectModel\DataObject $do, array $display = null);
	
	public function addElementFromProperty(Property\AbstractProperty $property, $position);
	
	public function addElement($element, $position);
	
	public function validate();
	
	public function getElement($key);
	
	public function getButton($key);
	
	public function getErrors();
	
	public function keepOnly($var);
	
	public function position($key);
}
