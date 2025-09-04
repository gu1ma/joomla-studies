<?php

/**
 * virtuemart dispatcher class
 *
 *
 * @version $Id$
 * @package    VirtueMart
 * @subpackage Helpers
 * @author Max Milbers
 * @copyright Copyright (c) 2019 - 2021 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */

use Joomla\Event\Dispatcher as EventDispatcher;

class vDispatcher {

	static $dispatcher = null;

	static function trigger ($name, $params){

		if(self::$dispatcher === null){
			if(JVM_VERSION<4){
				self::$dispatcher = JEventDispatcher::getInstance();
			} else {
				self::$dispatcher = JFactory::getApplication();
			}
		}

		if(JVM_VERSION<4){
			return self::$dispatcher->trigger($name, $params);
		} else {
			return self::$dispatcher->triggerEvent($name, $params);
		}
	}

	static function setDispatcher() {

	}

	/**
	 * Triggers joomla 5 plugins directly, backward compatible for j3,j4
	 * @author stAn of RuposTel, Max Milbers
	 * @param $plg
	 * @param $trigger
	 * @param $args
	 * @param $ret
	 * @return array|mixed|void|null
	 * @throws ReflectionException
	 */
	static function triggerJ5($plg,$trigger,$args, &$ret){

		//stAn - future versions first, we use Event if triggerer is subscribed:
		if (is_object($plg)) {
			$className = get_class($plg);
			if (method_exists($className, 'getSubscribedEvents')) {
				$evts = $className::getSubscribedEvents();
				vmdebug('my $plugins triggerJ5 getSubscribedEvents',$evts);
				if (!empty($evts[$trigger])) {
					$fn = $evts[$trigger];
					$reflector = new ReflectionClass($plg);
					$parameters = $reflector->getMethod($fn)->getParameters();
					//maybe default should be AbstractEvent as all others are possibly derived and would still work
					$EventClass = '\Joomla\Event\Event';
					foreach ($parameters as $param) {
						$cTest = $param->gettype();

						if (!empty($cTest) &&
							($cTest instanceof ReflectionNamedType) &&
							(strpos($cTest->getName(), 'Event') !== false)) {
							$EventClass = $cTest->getName();
							break;
						}
					}

					$event = new $EventClass($trigger, $args);
					if (is_array($args)) {
						foreach ($args as $named => $value) {
							$event->setArgument($named, $value);
						}
					}
					vmdebug('my $plugins triggerJ5',$EventClass);
					if(is_array($ret)){
						$ret[] = call_user_func_array(array($plg,$fn),array($event));
					} else {
						return call_user_func_array(array($plg,$fn),array($event));
					}

				}
			} elseif (method_exists($plg, $trigger)) {
				/*stan note - we can compare number of parameters vs args provided here:
				$reflector = new ReflectionClass($plg);
				$parameters = $reflector->getMethod($trigger)->getParameters();
				*/
				vmdebug('my $plugins triggerJ5 method_exists',$trigger,$ret);
				if(is_array($ret)){
					vmdebug('my $plugins triggerJ5 method_exists return array',$trigger);
					$ret[] = call_user_func_array(array($plg,$trigger),$args);
				} else {
					vmdebug('my $plugins triggerJ5 method_exists return single',$trigger);
					return call_user_func_array(array($plg,$trigger),$args);
				}
				
			}
			return $ret;
		}
		return null;
	}

	/**
	 * Executes a function of a plugin directly, which is loaded via element
	 *
	 * @author Max Milbers
	 * @param $type type of the plugin, for example vmpayment
	 * @param $element the element of the plugin as written in the extensions table (usually lowercase)
	 * @param $trigger the function which was the trigger to execute
	 * @param $args the arguments (as before for the triggers)
	 * @param $enabled trigger only enabled plugins
	 * @return mixed
	 */
	static public function directTrigger($type,$element,$trigger, $args, $enabled = TRUE){
		//vmdebug('Calling directTrigger',$type,$element,$trigger, $args);

		JPluginHelper::importPlugin($type);
		if(empty($element)){
			$plugins = JPluginHelper::getPlugin($type);

			$ret = array(); 
			foreach($plugins as $plugin){
				$plg = self::createPlugin($type, $plugin->name, $enabled);
				if (empty($plg)) continue;

				self::triggerJ5($plg,$trigger,$args,$ret);

			}
			//vmdebug('my $plugins '.$type,$plugins,$ret);
			return $ret; 
			
		} else {
			$plg = self::createPlugin($type,$element, $enabled);

			if (empty($plg)) return null;
			$ret = false;
			return self::triggerJ5($plg,$trigger,$args,$ret);

		}
	}

	static function importVMPlugins($ptype){

		static $types = array('vmextended'=>TRUE,'vmuserfield'=>TRUE, 'vmcalculation'=>TRUE, 'vmcustom'=>TRUE, 'vmcoupon'=>TRUE, 'vmshipment'=>TRUE, 'vmpayment'=>TRUE);
		if(!isset($types[$ptype])) return;

		foreach($types as $type => $v){
			//vmStartTimer('importPlugins');
			JPluginHelper::importPlugin($type);
			unset($types[$type]);
			//vmTime('time to import plugins '.$type,'importPlugins');
			if($type == $ptype){
				break;
			}
		}
	}

	/** Creates a plugin object. Used by the directTrigger and therefore loads also unpublished plugins.
	 * Otherwise, we would not be able to use the plug-in functions during the method saving process.
	 * @author Max Milbers, stAn of RuposTel
	 * @param $type
	 * @param $element
	 * @return false|mixed
	 */
	static public function createPlugin($type, $element, $enabled = TRUE){

		if(empty($type) or empty($element)){
			vmdebug('Developer error, class vmpluglin function createPlugin: empty type or element');
		}

		$plugin = self::getPlugin($type, $element, $enabled);
		if(!isset($plugin->type) or !isset($plugin->name)){
			if(!empty($type) and !empty($element)) {
				vmdebug('VmPlugin function createPlugin, plugin unpublished', $type, $element, $plugin);
				vmTrace('VmPlugin function createPlugin, plugin unpublished '. $type .' '. $element.' $enabled '.(int)$enabled);
				//vmError('VmPlugin function createPlugin, plugin unpublished '. $type .' '. $element);

			} else {
				vmdebug('VmPlugin function createPlugin, type or name empty',$type,$element);
				vmTrace('VmPlugin function createPlugin, type or name empty '. $type .' '. $element);
				vmError('VmPlugin function createPlugin, type or name empty '. $type .' '. $element);
			}
		}
		
		//stAn - this is default and legacy for J1.5 to J5, so we should always test this:
		$className = 'Plg' . str_replace('-', '', $type) . $element;

		if(!class_exists($className) and JFile::exists(VMPATH_PLUGINS.'/'.$type.'/'.$element.'/'.$element.'.php')){
			//stAn - changed to require_once, fatal is not needed here
			require_once(VMPATH_PLUGINS.'/'.$type.'/'.$element.'/'.$element.'.php');
		}

		//J5 way - if provider.php exists we use it to construct the class, regardless of className
		if (JVM_VERSION > 3) {
			//stAn - here class_exists loads the PHP file with joomla's autloader, thus no require is needed
			//stAn - we need to check for J4+J5 or existance of this...
			if (JFile::exists(VMPATH_PLUGINS.'/'.$type.'/'.$element.'/services/provider.php')) {
				$providerClass = require(VMPATH_PLUGINS.'/'.$type.'/'.$element.'/services/provider.php');
				//stAn - inspired by: https://github.com/joomla-framework/di/blob/3.x-dev/Tests/ContainerSetupTest.php
				if (is_object($providerClass) && (method_exists($providerClass, 'register'))) {

					$containerGlobal = \Joomla\CMS\Factory::getContainer();
				    //this calls the constructor of the plugin regardless of number of arguments (reCaptcha J4 got 3 arguments in constructor, thus we should let the plugin to construct it)
					$providerClass->register($containerGlobal);
					$pluginObject = $containerGlobal->get(Joomla\CMS\Extension\PluginInterface::class);
					if (!empty($pluginObject)) {
						return $pluginObject;
					}
				}
			}
		}

		if(class_exists($className)){
			// Instantiate and register the plugin.
			if(JVM_VERSION<4){
				if(self::$dispatcher === null){
					self::$dispatcher = JEventDispatcher::getInstance();
				}
				return new $className(self::$dispatcher, (array) $plugin);
			} else {
				$dummy = new EventDispatcher();
				return new $className($dummy, (array) $plugin);
			}

		} else {
			vmdebug('VmPlugin function createPlugin, class does not exist '.$className, $type, $element);
			vmTrace('VmPlugin function createPlugin, class does not exist '. $type .' '. $element,1,10);
			vmError('VmPlugin function createPlugin, class does not exist '.$className.' '. $type .' '. $element,'VmPlugin function createPlugin, class does not exist');
			return false;
		}

	}

	static function getPlugin($type, $element, $enabled = TRUE){

		$q = 'SELECT `extension_id` as `id`, `folder` as `type`, `element` as `name`, `params`, `state` FROM #__extensions WHERE 
						`type`="plugin" and `folder`="'.$type.'" and `element` = "'.$element.'"';
		if($enabled){
			$q .= ' AND enabled = "1" AND `state` = "0"';
		}
		$db = JFactory::getDbo();
		$db->setQuery($q);

		try{
			$plugin = $db->loadObject();
		} catch (Exception $e) {
			$t = 'Could not load Plugin '.$type.' '.$element;
			vmError($t.' '.$e->getMessage(),$t);
		}

		/* I wonder about this one,
		 * if(!empty($plugin->state)){
			$q = 'UPDATE #__extensions SET `state`="0" WHERE `extension_id` = "'.$plugin->extension_id.'"';
			$db->setQuery($q);
			$db->execute();
		}*/
		return $plugin;
	}

}