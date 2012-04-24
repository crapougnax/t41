<?php

namespace t41\Core;


interface ClientSideInterface {

	/**
	 * Reduce the implementing class object to a basic array
	 * This method is used to send objects to the view
	 * 
	 * Parameters are defined in an array. Available options are:
	 * 
	 * - params:	array	keys list of parameters to return, 
	 * 			 			use an empty array to ignore parameters, default is to return all parameters with a value
	 * 
	 * - extra:		array	array of extra key/value pairs to append to the reduced action
	 * 						with this parameter one can either add a specific value or replace an existing one
	 * 
	 * - props: 	array	array of properties to include
	 * 			 			use an empty array to ignore properties, default is to return all properties
	 * 
	 * - extprops:	array	array of extra properties to include
	 * 						this is useful to get property and collections properties data
	 *	 					ex: array('myobjectprop' => array('objectprop1','objectprop2')
	 *
	 *				boolean	if this parameter is set to true/false, the property expansion is globally applied
	 * 
	 * 
	 * 
	 * @param array $params
	 * @return array
	 */
	public function reduce(array $params = array());
}
