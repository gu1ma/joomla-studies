<?php
defined('_JEXEC') or die('Restricted access');
/**
 * Installation script for the plugin
 *
 * @copyright Copyright (C) 2013 Reinhold Kainhofer, office@open-tools.net
 * @license GPL v3+,  http://www.gnu.org/copyleft/gpl.html 
 */

class plgVmShipmentRules_Shipping_AdvancedInstallerScript
{
    /**
     * Constructor
     *
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     */
//     public function __constructor(JAdapterInstance $adapter);
 
    /**
     * Called before any type of action
     *
     * @param   string  $route  Which action is happening (install|uninstall|discover_install)
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
//     public function preflight($route, JAdapterInstance $adapter);
 
    /**
     * Called after any type of action
     *
     * @param   string  $route  Which action is happening (install|uninstall|discover_install)
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
//     public function postflight($route, JAdapterInstance $adapter);
 
    /**
     * Called on installation
     *
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function install()
    {
        // enabling plugin
        $db = JFactory::getDBO();
        $db->setQuery('update #__extensions set enabled = 1 where type = "plugin" and element = "rules_shipping_advanced" and folder = "vmshipment"');
        $db->execute();
        
        return True;
    }
 
    /**
     * Called on update
     *
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
//     public function update(JAdapterInstance $adapter)
//     {
//         jimport( 'joomla.filesystem.file' ); 
//         $file = JPATH_ROOT . DS . "administrator" . DS . "language" . DS . "en-GB" . DS . "en-GB.plg_vmshopper_ordernumber.sys.ini";
//         if (JFile::exists($file)) JFile::delete($file); 
//         $file = JPATH_ROOT . DS . "administrator" . DS . "language" . DS . "de-DE" . DS . "de-DE.plg_vmshopper_ordernumber.sys.ini"; 
//         if (JFile::exists($file)) JFile::delete($file); 
//         return true;
//     }
 
    /**
     * Called on uninstallation
     *
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     */
    public function uninstall()
    {
        // Remove plugin table
        $db =& JFactory::getDBO();
        $db->setQuery('DROP TABLE IF EXISTS `#__virtuemart_shipment_plg_rules_shipping_advanced`;');
        $db->query();
    }
}