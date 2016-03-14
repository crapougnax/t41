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

use t41\ObjectModel,
	t41\ObjectModel\DataObject,
	t41\ObjectModel\Property\CurrencyProperty,
	t41\View\FormComponent\Element;

/**
 * Class providing parameters and methods to dsplay a data array in a table.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class TableComponent extends ViewObject {
	
	
	/**
	 * Column-based display 
	 * Use it to display multiple rows (default behavior) 
	 * 
	 * @var integer
	 */
	const DISP_COLS = 1;

	/**
	 * Row-based display
 	 * (only the first row will be displayed in this mode)
 	 * 
	 * @var integer
	 */
	const DISP_ROWS = 2;
	
	
	const FORM_DEFAULT 	= 1;

	const FORM_CURRENCY = 2;

	const FORM_NUMBERS 	= 3;

	const FORM_DATE 	= 4;

	const FORM_IGNORE 	= 5;
	
	
	
	/**
	 * Ignore rows, only merge columns
	 * @var integer
	 */
	const ROWS_IGNORE	= 20;
	
	/**
	 * Add rows 
	 * @var integer
	 */
	const ROWS_ADD		= 21;
	
	/**
	 * Merge rows using row id (missing id in left table are ignored)
	 * @var integer
	 */
	const ROWS_MERGE	= 22;
	
	
	/**
	 * Place given object properties "before" current object
	 * @var integer
	 */
	const MERGE_PREPEND = 23;
	
	/**
	 * Place given object properties "after" current object
	 * @var integer
	 */
	const MERGE_APPEND	= 24;
	
	
	/**
	 * Data array
	 * 
	 * @var array
	 */
	public $_dataRows = array();
	
	
	/**
	 * Array of columns
	 * 
	 * @deprecated will be removed when all decorators comply
	 * @var array
	 */
	public $_columns = array();

	/**
	 * Array of columns represented by field elements
	 * 
	 * @var array
	 */
	protected $_columns2 = array();
	
	
	/**
	 * Table disposition (by rows or by cols)
	 * @var integer
	 */
	public $_disposition = null;

	
	public $_maxRows = null;
	
	
	/**
	 * Array of buttons (for Web decorators)
	 * 
	 * @var $_buttons array
	 */
	protected $_buttons = array();
	
	
	/**
	 * Table constructor
	 * 
	 * @param string $id
	 * @param integer $disposition
	 * @param array $params
	 */
	public function __construct($id, $disposition = self::DISP_COLS, $params = array())
	{
		$this->_disposition = $disposition;
		parent::__construct($id, $params);
	}
	

	/**
	 * Add content from a data object and optional columns ids
	 * 
	 * @param t41\ObjectModel\DataObject $do
	 * @param array $columns
	 * @return t41\View\TableComponent
	 */
	public function setContent(DataObject $do, array $columns = array())
	{
		foreach ($columns as $column) {
			if (($prop = $do->getRecursiveProperty($column)) !== false) {
				
				$format = $prop instanceof CurrencyProperty ? self::FORM_CURRENCY : self::FORM_DEFAULT;
				$this->addColumn($column, $prop->getLabel(), $format);
			}
		}
		
		$data = $do->toArray(null, false, true);
		$this->addDataRow($data['data']);
		
		return $this;
	}
	
	
	public function addColumn($colId, $label = null, $formating = self::FORM_DEFAULT, $preserveLabel = false)
	{
		switch ($formating) {
			
			case self::FORM_CURRENCY:
				$class = '\t41\View\FormComponent\Element\CurrencyElement';
				break;
				
			case self::FORM_DATE:
				$class = '\t41\View\FormComponent\Element\DateElement';
				break;
				
			default:
				$class = '\t41\View\FormComponent\Element\FieldElement';
				break;
		}
		
		$field = new $class(null, array('preserveLabel' => $preserveLabel));
		$field->setTitle($label);
		$field->setId($colId);
		
		$this->_columns2[] = $field;
		
		return $this;
	}
	
	
	/**
	 * Add multiple columns at once from an array
	 * 
	 * @param array $array
	 */
	public function addColumns(array $array)
	{
		foreach ($array as $column) {
			
			$this->addColumn($column['id'], $column['label'], $column['formating']);
		}
	}
	
	
	public function getColumns()
	{
		return $this->_columns2;
	}
	
	/**
	 * Add a button to be appended to the display (at least in Web decorators)
	 * 
	 * @param t41_Form_Element_Button $button
	 */
	public function addButton(Element\ButtonElement $button)
	{
		$this->_buttons[] = $button;
	}
	
	
	/**
	 * Add (or replace if id is given and already exists) a data row
	 * 
	 * @param array $array
	 * @param integer $id
	 */
	public function addDataRow($array, $id = null)
	{
		$id = $id !== null ? $id : count($this->_dataRows);
		$this->_dataRows[$id] = $array;
	}
	
	
	public function setDataRows(array $data)
	{
		$this->_dataRows = $data;
	}
	
	
	/**
	 * Returns an array of declared columns
	 */
	public function getFields()
	{
		return $this->_columns2;
	}
	
	
	public function setFields(array $fields)
	{
		$this->_columns2 = $fields;
	}
	
	
	/**
	 * Returns the total number of data rows
	 * 
	 * @return integer
	 */
	public function getTotalRows()
	{
	    return (int) count($this->_dataRows);
	}
	

	public function getRow($key)
	{
		return isset($this->_dataRows[$key]) ? $this->_dataRows[$key] : false;
	}
	
	
	public function getRows()
	{
		return $this->_dataRows;
	}
	
	
	/**
	 * Return table disposition
	 * 
	 * @todo convert to t41_Parameter
	 * @returns integer
	 */
	public function getDisposition()
	{
		return $this->_disposition;
	}
	
	
	/**
	 * Returns an array of buttons
	 * 
	 * @return array
	 */
	public function getButtons()
	{
		return $this->_buttons;
	}
	
	
	/**
	 * Merge a t41_View_Table object to $this, multiple modes are available for data
	 * t41_View_Table::ROWS_IGNORE : data are ignored, just merge columns (default)
	 * t41_View_Table::ROWS_ADD    : mix data rows from both objects
	 * t41_View_Table::ROWS_MERGE  : $table data rows are the reference, merge is based on key existence
	 * 
	 * Table can be merged in two ways:
	 * 
	 * t41_View_Table::MERGE_PREPEND: fields are inserted before current object
	 * t41_View_Table::MERGE_APPEND: fields are inserted after current object (default)
	 * 
	 * @param t41_View_Table $table
	 * @param integer $mode
	 * @param integer $rowMode
	 * @return t41_View_Table
	 */
	public function merge(TableComponent $table, $mode = self::MERGE_APPEND, $rowMode = self::ROWS_IGNORE)
	{
		// change all keys to avoid duplicates
		$table = self::changeIdentifiers($table);
		
		switch ($mode) {

			case self::MERGE_APPEND:
				
				$leftObj	= $this;
				$rightObj	= $table;
				break;
				
			case self::MERGE_PREPEND:
				
				$leftObj	= $table;
				$rightObj	= $this;
				break;
				
			default:
				throw new Exception("mode parameter value must be either t41_View_Table::MERGE_APPEND or t41_View_Table::MERGE_PREPEND");
				break;
		}
		

		// merge columns
		$leftObj->setFields(array_merge($leftObj->getFields(), $rightObj->getFields()));
		
		switch ($rowMode) {
			
			// right rows are added after left rows
			case self::ROWS_ADD:
				
				$leftObj->setDataRows(array_merge($leftObj->getRows(), $rightObj->getRows()));
				break;
				
			// rows are merged
			case self::ROWS_MERGE:

				$rows = $rightObj->getRows();

				foreach ($leftObj->getRows() as $key => $row) {
					
					if (isset($rows[$key])) {
						
						$leftObj->addDataRow(array_merge($row, $rows[$key]), $key);
					}
				}
				break;
				
			case self::ROWS_IGNORE:
			default:
				break;
		}
		
		return $leftObj;
	}
	

	/**
	 * Change given object field identifiers to avoid duplicates when merging is called
	 * 
	 * @param t41_View_Table $table
	 * @param string $token
	 * @return t41_View_Table
	 */
	public static function changeIdentifiers(TableComponent $table, $token =  null)
	{
		if (is_null($token)) {
			
			$token = '_' . $table->getId() . '_';
			
			$cols = array();
			foreach ($table->getFields() as $key => $val) {
				
				$val->setId($token . $val->getId());
				$cols[] = $val;
			}
			
			$table->setFields($cols);
			
			$array = array();
			
			foreach ($table->getRows() as $key => $row) {
				
				$array[$key] = array();
				foreach ($row as $key2 => $val) {
					
					$array[$key][$token . $key2] = $val;
				}
			}
			
			$table->setDataRows($array);
		}
		
		return $table;
	}
}
