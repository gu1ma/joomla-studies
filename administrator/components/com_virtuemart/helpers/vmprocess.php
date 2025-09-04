<?php



class VmProcess {

	protected $_stop = false;
	protected $starttime = null;
	protected $maxScriptTime = null;

	public function __construct () {

		$this->_app = JFactory::getApplication();
		$this->_db = JFactory::getDBO();

		$this->starttime = microtime(true);

		$max_execution_time = VmConfig::getExecutionTime();
		$jrmax_execution_time = vRequest::getInt('max_execution_time');

		if (!empty($jrmax_execution_time)) {
			// 			vmdebug('$jrmax_execution_time',$jrmax_execution_time);
			if ($max_execution_time != $jrmax_execution_time) @ini_set('max_execution_time', $jrmax_execution_time);
		} else if ($max_execution_time < 60) {
			@ini_set('max_execution_time', 60);
		}

		$this->maxScriptTime = VmConfig::getExecutionTime() * 0.95 - 3;    //Lets use 3 seconds of the execution time as reserve to store the progress

		$jrmemory_limit = vRequest::getInt('memory_limit');
		if (!empty($jrmemory_limit)) {
			VmConfig::ensureMemoryLimit($jrmemory_limit);
		} else {
			VmConfig::ensureMemoryLimit(128);
		}

		$this->maxMemoryLimit = VmConfig::getMemoryLimit() * 1024 * 1024;        //Has 5MB left for joomla

		vmLanguage::loadJLang('com_virtuemart_config');

	}


}