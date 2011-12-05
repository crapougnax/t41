<?php

namespace t41\Backend\Adapter;

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
 * @package    t41_Backend
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 832 $
 */

/**
 * Interface for backend adapters
 *
 * @category   t41
 * @package    t41_Backend
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * 
 */


interface AdapterInterface {

	
	public function __construct(t41_Backend_Uri $uri);
	
	
	public function setMapper(t41_Backend_Mapper $mapper);
	
	
	public function create(t41_Data_Object $do);
	
	
	public function read(t41_Data_Object $do);
	
	
	public function update(t41_Data_Object $do);
	
	
	public function delete(t41_Data_Object $do);

	
	public function find(t41_Object_Collection $collection);
}