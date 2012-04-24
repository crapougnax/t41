<?php

require_once 't41/Form/Element/Abstract.php';

class t41_Form_Element_Multiplekey extends t41_Form_Element_Abstract {

	
	protected $_foreign = array();
	
	protected $_totalValues;
	
	/**
	 * Parent table t41_Db Object
	 *
	 * @var t41_Db
	 */
	protected $_parentDb;
	
	protected $_relTable;
	
	
	public function __construct($id = null, array $params = null)
	{
		$this->_setParameterObjects(array('select_max_values'	=> new t41_Parameter(t41_Parameter::INTEGER, 100)
										, 'custom_label'		=> new t41_Parameter(t41_Parameter::STRING)	
										 )
								   );
		
		parent::__construct($id, $params);

		@list($this->_foreign['table'], $this->_foreign['pkey']) = explode('.', $this->_asArray['t41_field_val_field_id']);
		
		$label_tmpl = $this->getParameter('custom_label') ? $this->getParameter('custom_label') : $this->_asArray['t41_field_val_field_label'];
		
		$this->_foreign['fields'] = $this->_setLabelElements($label_tmpl);
		
		$this->_parentDb = new t41_Legacy_Db($this->_foreign['table']);
		
		$this->_relTable = str_replace('._', '__', $this->getId()); // ex: meta field 'order._product' supposes we have a 'order__product' table
	}
	

	public function getDataSourceName()
	{
		return $this->_foreign['table'];
	}
	
	
	public function getSqlJoin(Zend_Db_Select $select, $useLocalTable = false)
	{
		if ($useLocalTable) {

			// first join relation table
			$select->joinLeftUsing($this->_table
								 , substr($this->_table, 0, strpos($this->_table, '__')) . '_id'
								 , array()
								 );
			
			// then the counterpart data table 
			return $select->joinLeft( $this->_foreign['table']
									, $this->_foreign['table'] . '.' . $this->_foreign['pkey'] . ' = ' 
									. $this->_table . '.' . $this->_foreign['pkey']
									, array()
									);
		} else {

			return $select->joinLeftUsing($this->_foreign['table'], $this->_foreign['pkey'], '*'); //array());
		}
	}

	
	public function getTotalValues()
	{
		if (is_null($this->_totalValues)) {
			
			$db = t41_Core::dbGetHook();
			$select = $db->select();
			$select->from($this->_foreign['table'], new Zend_Db_Expr('COUNT(' . $this->_foreign['pkey'] .')'));

			try {
				$this->_totalValues = $db->fetchOne($select);
			} catch (Exception $e) {
				
				die($select->__toString() . ' : ' . $e->getMessage());
			}
		}
		
		return $this->_totalValues;
	}
	
	
	public function setEnumValues($str = null, $all = false)
	{
        $this->_enumValues = array();

        //$select = $this->_parentDb->getSqlQueryBase();
        
        $db = t41_Core::dbGetHook();
        $select = $db->select();
        

        $fields = $this->_foreign['fields'];
        $fields[] = $this->_foreign['pkey'];
        
        /* @var $select Zend_Db_Select */
        $select = $this->_parentDb->getSqlQueryBase();
        
        $select->columns($fields);
        //$select->from($this->_foreign['table'], $fields);
            
        if ($this->getParameter('rowid') && $all === false) {
        	
        	$select->joinLeft($this->_relTable, $this->_foreign['table'] . '.' . $this->_foreign['table'] . '_id = ' . $this->_relTable . '.' . $this->_foreign['table'] . '_id');
        	$select->where($this->_relTable . '.' . $this->_table . '_id = ?', $this->getParameter('rowid'));
        	
        } else if (count($this->_conditions) > 0) {
            	
        	foreach ($this->_conditions as $condition) {
            		
            	if ($condition['obj'] instanceof t41_Form_Element_Abstract) {

	            	$select->where(sprintf("%s %s ?", $condition['obj']->getId(), $condition['operator']), $condition['obj']->getValue());
              	
            	} else {
            		
	            	$select->where(sprintf("%s %s ?", $condition['obj'], $condition['operator']), $condition['val']);
            	}
            }
        }
       
       $select->order($this->_foreign['table'] .  '.' . $this->_foreign['table'] . '_id ' . Zend_Db_Select::SQL_DESC);
       $select->limit($this->getParameter('select_max_values') + 1);

//       echo $select;
       $list = $db->fetchAll($select);
            
       foreach ($list as $val) {
       		$this->_enumValues[$val[strtolower($this->_foreign['pkey'])]] = $val;
       }
            
       return $this->_enumValues;
	}

	
    public function formatValue($key = null)
    {
        if ($key == null) return '';

        // value is already available (foreign key with no specific constraint in it
        if (isset($this->_enumValues[$key])) {
        	
        	return $this->_enumValues[$key];
        }

        // value no more available to select, though we need to display it !
       // $db = t41_Core::dbGetHook();
        
        $str = '';

        $val = $this->_parentDb->getRecord($key);

        foreach ($this->_foreign['fields'] as $field) {
	    	
        	$str .= $val[strtolower($field)]  . ' ';
	    }
        
        $this->_enumValues[$key] = $str;

        return $str;
    }
    
    
    public function getEnumValues($str = null, $all = false)
    {
    	if (is_null($str)) {
    		return $this->setEnumValues();
    	} else {
    		return $this->setEnumValues($str, $all);
    	}
    }

    
    protected function _setLabelElements($str = null)
    {
    	if ($str == null) {
    		
    		return array(preg_replace('/_id$/', '_label', $this->_foreign['pkey']));
    		
    	} else {
    		
    		$fields = array();
    		
    		if (substr($str, 0, 4) == 'tpl:') {
    			
    			$this->_foreign['tpl'] = substr($str, 5, -1);

    			//$matches = array();
		        //extract fields from motif
    	        //preg_match_all('/\{([a-z0-9_-]+)\}/', $this->_foreign['tpl'], $matches, PREG_SET_ORDER);

    			$matches = explode(',', $this->_foreign['tpl']);
        	    foreach ($matches as $val) $fields[] = $val;
        	    return $fields;
        	        
    		} else {
    		
                return explode(',', $str);
            }
    	}
    }
    
    
    public function getColumnsIdentifiers()
    {
    	return $this->_foreign['fields'];
    }
    
    
    /**
     * Method called to ensure proper saving of this field data
     * 
     * @param array|string $data array array of primary keys to the referenced records
     * @return boolean
     */
    public function saveData($data)
    {
    	/* define primary key to which we will refer in all records */
    	$pkey = $this->getParameter('rowid') ? $this->getParameter('rowid') : $data[$this->_table . '.' . $this->_table . '_id'];
    	
    	if (! isset($data[$this->getFieldName()]) || ! is_array($data[$this->getFieldName()])) {
    		
    		return false;
    	}

    	$keysArrayCur  = $data[$this->getFieldName()];
    	$keysArrayPrev = isset($data['prev_' . $this->getFieldName()]) ? $data['prev_' . $this->getFieldName()] : array();
    	
    	/* array of unchanged associated rows */
    	$preservedKeys = array_intersect($keysArrayCur, $keysArrayPrev);
    	
    	// table where to save data should be named as follow
    	$table = str_replace('._', '__', $this->getId()); // ex: meta field 'order._product' supposes we have a 'order__product' table

    	$db = t41_Core::dbGetHook();

    	// delete conditions
    	$delConds = array();
    	$delConds[] = $db->quoteInto($this->_table . '_id = ?', $pkey);
    	if (is_array($preservedKeys) && count($preservedKeys) > 0) {
    		
    		// don't delete records that haven't been unselected! */
    		$delConds[] = $db->quoteInto($this->_foreign['pkey'] . ' NOT IN (?)', $preservedKeys);
    	}
    	
    	// delete some previously existing records linked to the pkey
    	$db->delete($table, $delConds);

    	/* array of data for SQL insert */
    	$array = array();
    	
    	// get the associated record primary key
    	$array[$this->_table . '_id'] = $pkey;
    	
    	$keysArrayNew = array_diff($keysArrayCur, $keysArrayPrev);
    	
    	/* insert records of new relations */
    	foreach ($keysArrayNew as $value) {
    		
    		$array[$this->_foreign['pkey']] = $value;
    		
        	try {
	    		$db->insert($table, $array);
    			} catch (Exception $e) {
    			
    			return false;
    		}    		
    	}
    	
    	return true;
    }
}