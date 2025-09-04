<?php
defined ('_JEXEC') or die('Restricted access');

/**
 *
 * VirtueMart script file
 *
 * This file is executed during install/upgrade and uninstall
 *
 * @author Max Milbers, Valérie Isaksen
 * @package VirtueMart
 */

use Joomla\CMS\Factory;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;
use Joomla\CMS\Installer\InstallerAdapter;

class Com_TcpdfInstallerScript {
	/**
	 * Constructor
	 *
	 * @param   InstallerAdapter  $adapter  The object responsible for running this script
	 */
	public function __construct(InstallerAdapter $adapter)
	{
	}

	public function preflight(string $type, InstallerAdapter $adapter)
	{
		$max_execution_time = ini_get ('max_execution_time');
		if ((int)$max_execution_time < 120) {
			@ini_set ('max_execution_time', '120');
		}

		$mL = ini_get('memory_limit');
		$mLimit = 0;
		if(!empty($mL)){
			$u = strtoupper(substr($mL,-1));
			$mLimit = (int)substr($mL,0,-1);
			if($mLimit>0){

				if($u == 'M'){
					//$mLimit = $mLimit * 1048576;
				} else if($u == 'G'){
					$mLimit = $mLimit * 1024;
				} else if($u == 'K'){
					$mLimit = $mLimit / 1024.0;
				} else {
					$mLimit = $mLimit / 1048576.0;
				}
				$mLimit = (int) $mLimit - 5; // 5 MB reserve
				if($mLimit<=0){
					$mLimit = 1;
					$m = 'Increase your php memory limit, which is must too low to run VM, your current memory limit is set as '.$mL.' ='.$mLimit.'MB';
					vmError($m,$m);
				}
			}
		}
		if ($mLimit < 128) {
			@ini_set ('memory_limit', '128M');
		}

		if (is_file(JPATH_ROOT .'/administrator/components/com_tcpdf/install.xml')) {
			File::delete(JPATH_ROOT .'/administrator/components/com_tcpdf/install.xml');
		}

	}

	public function install (InstallerAdapter $adapter) {
		$this->tcpdfInstall();

		return true;
	}

	public function update (InstallerAdapter $adapter) {

		$this->tcpdfInstall();
	}

	public function tcpdfInstall () {

		$this->path =  dirname(__FILE__);

		// libraries auto move
		$src = $this->path . "/libraries";
		$dst = JPATH_ROOT .'/libraries';
		$this->recurse_copy ($src, $dst);

		$html = '<a
				href="http://virtuemart.net"
				target="_blank"> <img
					border="0"
					align="left" style="margin-right: 20px"
					src="components/com_virtuemart/assets/images/vm_menulogo.png"
					alt="Cart" /> </a>';
		$html .= '<h3 style="clear: both;">TcPdf moved to the joomla libraries folder</h3>';
		$html .= "<h3>Installation Successful.</h3>";

		echo $html;

		if(class_exists('vRequest')) vRequest::setVar('tcpdf_html',$html);

		return true;

	}

	/**
	 * copy all $src to $dst folder and remove it
	 *
	 * @author Max Milbers
	 * @param String $src path
	 * @param String $dst path
	 * @param String $type modulesBE, modules, plugins, languageBE, languageFE
	 */
	private function recurse_copy ($src, $dst) {

		static $failed = false;
		$dir = opendir ($src);

		if (is_resource ($dir)) {
			while (FALSE !== ($file = readdir ($dir))) {
				if (($file != '.') && ($file != '..')) {
					if (is_dir ($src . '/' . $file)) {
						if(!Folder::create($dst . '/' . $file)){
							$app = Factory::getApplication ();
							$app->enqueueMessage ('Couldnt create folder ' . $dst . '/' . $file);
						}
						$this->recurse_copy ($src . '/' . $file, $dst . '/' . $file);
					} else {
						if (is_file($dst . '/' . $file)) {
							if (!File::delete ($dst . '/' . $file)) {
								$app = Factory::getApplication ();
								$app->enqueueMessage ('Couldnt delete ' . $dst . '/' . $file);
								//return false;
							}
						}
						if (!File::move ($src . '/' . $file, $dst . '/' . $file)) {
							$app = Factory::getApplication ();
							$app->enqueueMessage ('Couldnt move ' . $src . '/' . $file . ' to ' . $dst . '/' . $file);
							$failed = true;
							//return false;
						}
					}
				}
			}
			closedir ($dir);
			if (is_dir ($src) and !$failed) {
				Folder::delete ($src);
			}
		} else {
			$app = Factory::getApplication ();
			$app->enqueueMessage ('TcPdf Installer recurse_copy; Couldnt read source directory '.$dir);
			return false;
		}
		return true;
	}


	public function uninstall (InstallerAdapter $adapter) {

		if(is_dir(JPATH_ROOT .'/libraries/vendor/tecnickcom/tcpdf')){
			Folder::delete(JPATH_ROOT .'/libraries/vendor/tecnickcom/tcpdf');
		}
		if(is_file(JPATH_ROOT .'/libraries/src/Document/PdfDocument.php')){
			File::delete(JPATH_ROOT .'/libraries/src/Document/PdfDocument.php');
		}
		if(is_dir(JPATH_ROOT .'/administrator/components/com_tcpdf')){
			Folder::delete(JPATH_ROOT .'/administrator/components/com_tcpdf');
		}

		$html = '<h3 style="clear: both;">TcPdf removed from joomla libraries folder</h3>';
		$html .= "<h3>Uninstall Successful.</h3>";
		echo $html;

		return true;
	}

	public function postflight (string $type, InstallerAdapter $adapter) {
		if(is_dir(JPATH_ROOT .'/administrator/components/com_tcpdf/libraries')){
			Folder::delete(JPATH_ROOT .'/administrator/components/com_tcpdf/libraries');
		}

		if(is_dir(JPATH_ROOT .'/libraries/joomla/pdf')){
			Folder::delete(JPATH_ROOT .'/libraries/joomla/pdf');
		}

		if(is_dir(JPATH_ROOT .'/libraries/tcpdf')){
			Folder::delete(JPATH_ROOT .'/libraries/tcpdf');
		}

		return true;
	}
}


// pure php no tag
