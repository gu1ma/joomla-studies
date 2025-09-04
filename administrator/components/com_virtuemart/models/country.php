<?php
/**
*
* Data module for shop countries
*
* @package	VirtueMart
* @subpackage Country
* @author Max Milbers
* @author RickG
* @link https://virtuemart.net
* @copyright Copyright (c) 2004 - 2022 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: country.php 10853 2023-05-24 14:16:22Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/**
 * Model class for shop countries
 *
 * @package	VirtueMart
 * @subpackage Country
 */
class VirtueMartModelCountry extends VmModel {

	protected static $_countries = array();

	/**
	 * constructs a VmModel
	 * setMainTable defines the maintable of the model
	 * @author Max Milbers
	 */
	function __construct() {
		parent::__construct();
		$this->setMainTable('countries');
		array_unshift($this->_validOrderingFieldName,'country_name');
		$this->_selectedOrdering = 'ordering, country_name';
		$this->_selectedOrderingDir = 'ASC';

	}

	public function getData($id = null){
		if(isset($id)){
			$this->_id = $this->setId($id);
		} else if(empty($this->_id)){
			$this->setIdByRequest();
		}
		return self::getCountry($this->_id);
	}

	static public function getCountry($id, $fieldname = 0){

		if(empty($fieldname) or $fieldname == 'virtuemart_country_id'){
			$h = $id;
		} else {
			$h = $fieldname.'.'.$id;
		}

		$c = VmTable::getInstance('countries');
		if(!isset(self::$_countries[$h])){
			$c->load($id, $fieldname);
			self::$_countries[$h] = self::$_countries['country_name.'.$c->country_name] = self::$_countries['country_2_code.'.$c->country_2_code] = self::$_countries['country_3_code.'.$c->country_3_code] = self::$_countries['country_num_code.'.$c->country_num_code] = $c->loadFieldValues();
			//vmdebug('loaded country '.$h,self::$_countries[$h]);
		} else {
			$c->bind(self::$_countries[$h]);
			//vmdebug('getCountry by Cache '.$h,self::$_countries);
		}

		//vmdebug('loaded country ',$id,$fieldname,self::$_countries[$id]->loadFieldValues());
		if($c){
			return $c;
		} else {
			return false;
		}

	}

	static public function getCountryFieldByID ($id, $fld = 'country_name') {

		$c = self::getCountry($id);
		if($c and isset($c->{$fld})){
			return $c->{$fld};
		} else {
			return false;
		}
	}

    /**
     * Retreive a country record given a country code.
     *
     * @author RickG, Max Milbers
     * @param string $code Country code to lookup
     * @return object Country object from database
     */
    static public function getCountryByCode($code) {

		if(empty($code)) return false;
		$db = JFactory::getDBO();

		$countryCodeLength = strlen($code);
		switch ($countryCodeLength) {
			case 2:
				$fieldname = 'country_2_code';
			break;
			case 3:
				if(is_numeric($code)){
					$fieldname = 'country_num_code';
				} else {
					$fieldname = 'country_3_code';
				}
				vmdebug('getCountryByCode '.strtoupper($code),$fieldname);
			break;
			default:
				$fieldname = 'country_name';
		}

		self::$_countries[$code] = self::getCountry(strtoupper($code), $fieldname);
		if(self::$_countries[$code]){
			return self::$_countries[$code]->loadFieldValues(false);
		} else {
			return false;
		}

    }

    /**
     * Retrieve a list of countries from the database.
     *
     * @author RickG
     * @author Max Milbers
     * @param string $onlyPublished True to only retrieve the publish countries, false otherwise
     * @param string $noLimit True if no record count limit is used, false otherwise
     * @return object List of country objects
     */
    function getCountries($onlyPublished=true, $noLimit=false, $filterCountry = false) {

		static $countries = array();
		$where = array();
		$this->_noLimit = $noLimit;

		if ($onlyPublished) $where[] = '`published` = 1';

		if($filterCountry){
			$db = JFactory::getDBO();
			$filterCountryS = '"%' . $db->escape( $filterCountry, true ) . '%"' ;
			$where[] = '`country_name` LIKE '.$filterCountryS.' OR `country_2_code` LIKE '.$filterCountryS.' OR `country_3_code` LIKE '.$filterCountryS.' OR `country_num_code` LIKE '.$filterCountryS;
		}

		$whereString = '';
		if (count($where) > 0) $whereString = ' WHERE '.implode(' AND ', $where) ;

		$ordering = $this->_getOrdering();
		$hash = $filterCountry.(int)$onlyPublished.$ordering.(int)$noLimit;
		if(!isset($countries[$hash])){
			$setDebug = VmConfig::$_debug;
			VmConfig::$_debug = 0;
			$countries[$hash] = $this->_data = $this->exeSortSearchListQuery(0,'*',' FROM `#__virtuemart_countries`',$whereString,'',$ordering);
			VmConfig::$_debug = $setDebug;
		}
		return $countries[$hash];
    }

	function store(&$data){
		if(!vmAccess::manager('country')){
			vmWarn('Insufficient permissions to store country');
			return false;
		}
		return parent::store($data);
	}

	function remove($ids){
		if(!vmAccess::manager('country')){
			vmWarn('Insufficient permissions to remove country');
			return false;
		}
		return parent::remove($ids);
	}

}

//no closing tag pure php
