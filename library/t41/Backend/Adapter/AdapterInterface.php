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
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 832 $
 */

use t41\Backend;
use t41\ObjectModel;
use t41\ObjectModel\Property;

/**
 * Interface for backend adapters
 *
 * @category   t41
 * @package    t41_Backend
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * 
 */


interface AdapterInterface {

	
	public function __construct(Backend\BackendUri $uri);
	
	
	public function setMapper(Backend\Mapper $mapper);
	
	
	public function create(ObjectModel\DataObject $do);
	
	
	public function read(ObjectModel\DataObject $do);
	
	
	public function update(ObjectModel\DataObject $do);
	
	
	public function delete(ObjectModel\DataObject $do);

	
	public function find(ObjectModel\Collection $collection);
	
	
	public function loadBlob(ObjectModel\DataObject $do, Property\AbstractProperty $property);
	
	
	public function transactionStart($key = null);
	
	
	public function transactionCommit();
	
	
	public function transactionExists();
}
