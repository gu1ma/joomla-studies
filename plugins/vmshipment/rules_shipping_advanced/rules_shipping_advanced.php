<?php

defined ('_JEXEC') or die('Restricted access');

/**
 * Shipment plugin for general, rules-based shipments, like regular postal services with complex shipping cost structures
 * Advanced part, implementing general mathematical expression evaluation
 *
 * @package VirtueMart
 * @subpackage Plugins - shipment.
 * @copyright Copyright (C) 2013 Reinhold Kainhofer, office@open-tools.net, 2022 iStraxx UG (haftungsbeschränkt)
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 *
 * @author Reinhold Kainhofer, based on the weight_countries shipping plugin by Valerie Isaksen
 *
 */

if (!class_exists( 'VmConfig' )) {
	require(JPATH_ADMINISTRATOR .'/components/com_virtuemart/helpers/config.php');
	VmConfig::loadConfig();
}

if (!class_exists ('plgVmShipmentRules_Shipping_Base')) {
	require (dirname(__FILE__).DS.'rules_shipping_base.php');
}


/** Shipping costs according to general rules.
 *  Derived from the standard plugin, no need to change anything! The standard plugin already uses the advanced rules class defined below, if it can be found
 */
class plgVmShipmentRules_Shipping_Advanced extends plgVmShipmentRules_Shipping_Base {

	function __construct (& $subject, $config) {
		parent::__construct ($subject, $config);
		$this->helper->registerCallback('initRule',				array($this, 'initRule'));
		$this->helper->registerCallback('addCustomCartValues',	array($this, 'addAdvancedCustomCartValues'));
	}
	
	public function initRule ($framework, $rulestring, $countries, $ruleinfo) {
		return new ShippingRule_Advanced ($framework, $rulestring, $countries, $ruleinfo);
	}
	
	/** Allow child classes to add additional variables for the rules 
	 */
	public function addAdvancedCustomCartValues ($cart, $products, $method, &$values) {
		$values['coupon'] = $cart->couponCode;
		
		if (isset($values['zip'])) {
			$zip=strtoupper($values['zip']);
		}
		$values = array_replace($values, $this->helper->getAddressZIP($zip));
		return $values;
	}

    /**
     * plgVmOnSelfCallBE ... Called to execute some plugin action in the backend (e.g. set/reset dl counter, show statistics etc.)
     */
    function plgVmOnSelfCallBE($type, $name, &$output) {
        if ($name != $this->_name || $type != $this->_type) return false;
        vmDebug('plgVmOnSelfCallBE');
        $user = JFactory::getUser();
        $authorized = ($user->authorise('core.admin','com_virtuemart') or
                       $user->authorise('core.manage','com_virtuemart') or 
                       $user->authorise('vm.orders','com_virtuemart'));
        $json = array();
        $json['authorized'] = $authorized;
        if (!$authorized) return FALSE;

        $action = vRequest::getCmd('action');
        $json['action'] = $action;
        $json['success'] = 0; // default: unsuccessfull
        switch ($action) {
			case "check_update_access":
				$order_number = vRequest::getString('order_number');
				$order_pass = vRequest::getString('order_pass');
				$json = $this->checkUpdateAccess($order_number, $order_pass, $json);
				break;
        }
        
        // Also return all messages (in HTML format!):
        // Since we are in a JSON document, we have to temporarily switch the type to HTML
        // to make sure the html renderer is actually used
        $document = JFactory::getDocument ();
        $previoustype = $document->getType();
        $document->setType('html');
        $msgrenderer = $document->loadRenderer('message');
        $json['messages'] = $msgrenderer->render('Message');
        $document->setType($previoustype);

        // WORKAROUND for broken (i.e. duplicate) content-disposition headers in Joomla 2.x:
        // We request everything in raw and here send the headers for JSON and return
        // the raw output in json format
        $document =JFactory::getDocument();
        $document->setMimeEncoding('application/json');
        JResponse::setHeader('Content-Disposition','attachment;filename="opentools_update_access.json"');
        $output = json_encode($json);
    }
    
    
    public function checkUpdateAccess($order_number, $order_pass, $json = array()) {
		// First, extract the update server URL from the manifest, then load 
		// the update XML from the update server, extract the download URL, 
		// append the order number and password and check whether access is 
		// possible.
		$json['success'] = FALSE;
		$xml = simplexml_load_file($this->_xmlFile);
		if (!$xml || !isset($xml->updateservers)) {
			JFactory::getApplication()->enqueueMessage(JText::sprintf('OPENTOOLS_XMLMANIFEST_ERROR', $this->_xmlFile), 'error');
			return $json;
		}
		$updateservers = $xml->updateservers;
		foreach ($updateservers->children() as $server) {
			if ($server->getName()!='server') {
				JFactory::getApplication()->enqueueMessage(JText::sprintf('OPENTOOLS_XMLMANIFEST_ERROR', $this->_xmlFile), 'error');
				continue;
			}
			$updateurl = html_entity_decode((string)$server);
			$updatescript = simplexml_load_file($updateurl);
			if ($updatescript === FALSE) {
				JFactory::getApplication()->enqueueMessage(JText::sprintf('OPENTOOLS_UPDATESCRIPT_ERROR', $updateurl), 'error');
				continue;
			}
			$urls = $updatescript->xpath('/updates/update/downloads/downloadurl');
			while (list( , $node) = each($urls)) {
				$downloadurl = (string)($node);
				if ($order_number) {
					$downloadurl .= (parse_url($downloadurl, PHP_URL_QUERY) ? '&' : '?') . 'order_number=' . urlencode($order_number);
				}
				if ($order_pass) {
					$downloadurl .= (parse_url($downloadurl, PHP_URL_QUERY) ? '&' : '?') . 'order_pass=' . urlencode($order_pass);
				}
				$downloadurl .= (parse_url($downloadurl, PHP_URL_QUERY) ? '&' : '?') . 'check_access=1';

				$headers = get_headers($downloadurl);
				list($version, $status_code, $msg) = explode(' ',$headers[0], 3);
				
				// Check the HTTP Status code
				switch($status_code) {
					case 200:
						$json['success'] = TRUE;
						JFactory::getApplication()->enqueueMessage($msg, 'message');
						$this->setupUpdateCredentials($order_number, $order_pass);
						break;
					default:
						JFactory::getApplication()->enqueueMessage($msg, 'error');
						// Clear the credentials...
						$this->setupUpdateCredentials("", "");
						break;
				}
				$this->setAndSaveParams(array(
					'update_credentials_checked'=>$json['success'],
					'order_number' => $order_number,
					'order_pass' => $order_pass,
				));
			}
		}
		return $json;
    }

    protected function setAndSaveParams($params) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('extension_id')
			->from('#__extensions')
			->where('folder = '.$db->quote($this->_type))
			->where('element = '.$db->quote($this->_name))
			->where('type =' . $db->quote('plugin'))
			->order('ordering');

		$plugin = $db->setQuery($query)->loadObject();
		if (!$plugin)
			return;
		$pluginId=$plugin->extension_id;
		
		foreach ($params as $param=>$parvalue) {
			$this->params->set($param, $parvalue);
		}
		
		$extensions = JTable::getInstance('extension');
		$extensions->load($pluginId);
		$extensions->bind(array('params' => $this->params->toString()));
		
		// check and store 
		if (!$extensions->check()) {
			$this->setError($extensions->getError());
			return false;
		}
		if (!$extensions->store()) {
			$this->setError($extensions->getError());
			return false;
		}
    }


	function setupUpdateCredentials($ordernumber, $orderpass) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('extension_id AS id')
			->from('#__extensions')
			->where('folder = '.$db->quote($this->_type))
			->where('element = '.$db->quote($this->_name))
			->where('type =' . $db->quote('plugin'))
			->order('ordering');

		$plugin = $db->setQuery($query)->loadObject();
		if (empty($plugin))
			return;

		$ordernumber = preg_replace("/[^-A-Za-z0-9_]/", '', $ordernumber);
		$orderpass = preg_replace("/[^-A-Za-z0-9_]/", '', $orderpass);
		
		$extra_query = array();
		if ($ordernumber!='') {
			$extra_query[] = 'order_number='.preg_replace("/[^-A-Za-z0-9_]/", '', $ordernumber);
		}
		if ($orderpass!='') {
			$extra_query[] = 'order_pass='.preg_replace("/[^-A-Za-z0-9_]/", '', $orderpass);
		}
		$extra_query = implode('&amp;', $extra_query);
		
		// The following code is based on Nicholas K. Dionysopoulos' Joomla Pull request:
		//     https://github.com/joomla/joomla-cms/pull/2508
		
		// Load the update site record, if it exists
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('update_site_id AS id')
			->from($db->qn('#__update_sites_extensions'))
			->where($db->qn('extension_id').' = '.$db->q($plugin->id));
		$db->setQuery($query);
		$updateSites = $db->loadObjectList();

		foreach ($updateSites as $updateSite) {
			// Update the update site record
			$query = $db->getQuery(true)
				->update($db->qn('#__update_sites'))
				->set('extra_query = '.$db->q($extra_query))
				->set('last_check_timestamp = 0')
				->where($db->qn('update_site_id').' = '.$db->q($updateSite->id));
			$db->setQuery($query);
			$db->execute();

			// Delete any existing updates (essentially flushes the updates cache for this update site)
			$query = $db->getQuery(true)
				->delete($db->qn('#__updates'))
				->where($db->qn('update_site_id').' = '.$db->q($updateSite->id));
			$db->setQuery($query);
			$db->execute();
		}
		
	}
}
