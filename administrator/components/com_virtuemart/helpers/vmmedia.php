<?php
/**
 * Created by PhpStorm.
 * User: Milbo
 * Date: 28.10.2019
 * Time: 21:51
 */


class VmMedia {

	static $url = array();

	static $theme_url = null;

/*	public $theme_url = null;
	static $stheme_url = null;
	static $relPaths = null;

	var $file_name = '';
	var $file_extension = '';

	var $height = 0;
	var $width = 0;

	var $media_attributes = 0;
	var $setRole = false;
	var $isImage = null;*/

/*	var $virtuemart_media_id = 0;
	var $virtuemart_vendor_id = 0;


	var $file_title = '';

	var $file_description = '';

	var $file_meta = '';

	var $file_class = '';

	var $file_mimetype = '';

	var $file_type = '';

	var $file_url = '';
	var $file_url_thumb = '';

	var $published = 0;
	var $file_is_downloadable = 0;
	var $file_is_forSale = 0;
	var $file_is_product_image = 0;

	var $shared = 0;
	var $file_params = '';
	var $file_lang = '';
*/

/*	function __construct () {
		$db = JFactory::getDbo();
		parent::__construct ( $db);




		vmdebug('__construct $media',$this->getProperties());
	}*/
	static $inst = null;
	static $table = null;

	static function init(){

		if(self::$inst === null){
			//$db = JFactory::getDbo();
			self::$inst = new VmMedia( );
			if(!isset(self::$theme_url)){
				self::$theme_url = VmConfig::get('vm_themeurl',0);
				if(empty(self::$theme_url)){
					self::$theme_url = 'components/com_virtuemart/';
				}
			}
			self::$table = VmTable::getInstance('Medias', 'Table', array('dbo'=>JFactory::getDbo()));
		}
	}

	static function getMedia($id){
		self::$table->load($id);
		$attribsImage = self::$table->getProperties();
		foreach($attribsImage as $k=>$v){
			self::$inst->{$k} = $v;
		}
		self::$inst->setFilePaths();
		return clone(self::$inst);
	}

	function setFilePaths(){
		$choosed = false;
		if($this->file_type == 'product' || $this->file_type == 'products'){
			$this->file_url_folder = VmConfig::get('media_product_path');
			$choosed = true;
		}
		else if($this->file_type == 'category' || $this->file_type == 'categories'){
			$this->file_url_folder = VmConfig::get('media_category_path');
			$choosed = true;
		}
		else if($this->file_type == 'shop'){
			$this->file_url_folder = VmConfig::get('media_path');
			$choosed = true;
		}
		else if($this->file_type == 'vendor' || $this->file_type == 'vendors'){
			$this->file_url_folder = VmConfig::get('media_vendor_path');
			$choosed = true;
		}
		else if($this->file_type == 'manufacturer' || $this->file_type == 'manufacturers'){
			$this->file_url_folder = VmConfig::get('media_manufacturer_path');
			$choosed = true;
		}
		else if($this->file_type == 'forSale' || $this->file_type== 'file_is_forSale'){
			$this->file_url_folder = shopFunctions::checkSafePathBase();
			if($this->file_url_folder){
				$choosed = true;
				$this->file_is_forSale=1;
			}

		}

		if($choosed && empty($this->file_url_folder)){
			$link = JURI::root() . 'administrator/index.php?option=com_virtuemart&view=config';
			vmInfo('COM_VIRTUEMART_MEDIA_NO_PATH_TYPE',$this->file_type,$link );
			//Todo add general media_path to config
			//$this->file_url_folder = VmConfig::get('media_path');
			$this->file_url_folder = self::getStoriesFb('typeless').'/';
			$this->setRole=true;
			// 		} else if(!$choosed and empty($this->file_url_folder) and $this->file_is_forSale==0){
		} else if(!$choosed and empty($this->file_url_folder) ){

			if(empty($this->file_type) and !empty($this->file_url)){
				vmAdminInfo('COM_VIRTUEMART_MEDIA_CHOOSE_TYPE',$this->file_title );
				// 	vmError('Ignore this message, when it appears while the media synchronisation process, else report to http://forum.virtuemart.net/index.php?board=127.0 : cant create media of unknown type, a programmers error, used type ',$this->file_type);
			}
			$this->file_url_folder = self::getStoriesFb('typeless').'/';
			$this->setRole=true;

		} else if(!$choosed and $this->file_is_forSale==1){
			$this->file_url_folder = '';
			$this->setRole=false;
		}

		//return $this->file_url_folder;

		//$this->file_url_folder = '';
		$this->file_url_folder_thumb = '';

		//if( substr( $this->file_url, 0, 2) == "//" ) {
			$rdspos = strrpos($this->file_url,'/');
			if($rdspos!==false){
				$this->file_name = substr($this->file_url,$rdspos+1);
				$rdspos = strrpos($this->file_name,'.');
				if($rdspos!==false){
					$this->file_extension = strtolower(JFile::getExt($this->file_name));
					$this->file_name = substr($this->file_name, 0,$rdspos);

				}
			}else {
				vmdebug('$name',$this->file_url);
			}
			//vmdebug('Remote image URL, created file_name',$this->file_name,$this->file_extension);
		//}
		//vmdebug('$this->file_url '.$this->file_url,$this->file_name,$this->file_extension);
		if($this->file_is_forSale==0 and $this->file_type!='forSale'){


			$this->file_url_folder_thumb = $this->file_url_folder.'resized/';
			$this->file_path_folder = str_replace('/',DS,$this->file_url_folder);
		} else {
			$safePath = shopFunctions::checkSafePathBase();
			if(!$safePath){
				return FALSE;
			}
			$this->file_path_folder = $safePath;
			$this->file_url_folder = $this->file_path_folder;//str_replace(DS,'/',$this->file_path_folder);
			$this->file_url_folder_thumb = VmConfig::get('forSale_path_thumb');
		}

	}

	/**
	 * Tests if a function is an image by mime or extension
	 *
	 * @author Max Milbers
	 * @param string $file_mimetype
	 * @param string $file_extension
	 */
	private function isImage(){

		if(!isset($this->isImage)){
			//$this->file_extension = JFile::stripExt($this->file_name );
			//vmdebug('isImage ',$this->file_name,$this->file_extension);
			if($this->file_extension == 'jpg' || $this->file_extension == 'jpeg' || $this->file_extension == 'png' || $this->file_extension == 'gif'){
				$this->isImage = TRUE;

			} else {
				$this->isImage = FALSE;
				vmTrace('is no image '.$this->file_extension);
			}
		}

		return $this->isImage;
	}



	function filterImageArgs($imageArgs){
		if(!empty($imageArgs)){
			if(!is_array($imageArgs)){
				$imageArgs = str_replace(array('class','"','='),'',$imageArgs);
				$imageArgs = array('class' => $imageArgs.' '.$this->file_class);
			} else {
				if(!isset($imageArgs['class'])) $imageArgs['class'] = '';
				$imageArgs['class'] .= ' '.$this->file_class;
			}
		} else {
			$imageArgs = array('class' => $imageArgs.' '.$this->file_class);
		}
		return $imageArgs;
	}

	function setNoImageSet(){
		/*if($this->file_is_downloadable){
			$file_name = VmConfig::get('downloadable','zip.png');
		} else {*/
			$file_name = VmConfig::get('no_image_set','noimage_new.gif');
		//}
		$this->file_name = JFile::stripExt($file_name);
		$this->file_url_folder = self::theme_url.'assets/images/vmgeneral/';
		$this->file_url = $this->file_url_folder.$file_name;
		$this->file_url_folder_thumb = static::getStoriesFb('typeless').'/';;
		$this->file_meta = vmText::_('COM_VIRTUEMART_NO_IMAGE_SET').' '.$this->file_description;
		$this->file_extension = strtolower(JFile::getExt($file_name));
	}

	function displayMedia($imageArgs='',$lightbox=true,$effect="class='modal' rel='group'",$return = true,$withDescr = false,$absUrl = false, $width=0,$height=0){


		$imageArgs = $this->imageArgsToArray($imageArgs);


		if($withDescr) $withDescr = $this->file_description;

		if(!$this->isImage()){
			return $this->getIcon($imageArgs,$lightbox,$return,$withDescr,$absUrl);
		}

		if( substr( $this->file_url, 0, 2) == "//" ) {
			$toChk = $this->file_url;
			try {
				$resObj = VmConnector::getHttp(array(), array('curl', 'stream'))->get($toChk);
				//vmdebug('Object per URL',$resObj);
				if($resObj->code!=200){
					vmdebug('URL does not exists',$toChk,$resObj);
					vmError(vmText::sprintf('COM_VIRTUEMART_FILE_NOT_FOUND',$toChk));
				};
			} catch (RuntimeException $e) {
				vmError(vmText::sprintf('COM_VIRTUEMART_FILE_NOT_FOUND',$toChk));
			}
		} else {
			if($this->file_is_forSale){
				$toChk = $this->file_url;
			} else {
				$toChk = VMPATH_ROOT.'/'.$this->file_url;
			}
			if(!empty($this->file_name) and !JFile::exists($toChk)){
				vmdebug('Media file does not exists',$toChk);
				vmError(vmText::sprintf('COM_VIRTUEMART_FILE_NOT_FOUND',$toChk));
			}
		}

		if(empty($this->file_meta)){
			if(!empty($this->file_description)){
				$file_alt = $this->file_description;
			} else if(!empty($this->file_name)) {
				$file_alt = $this->file_name;
			} else {
				$file_alt = '';
			}
		} else {
			$file_alt = $this->file_meta;
		}

		$file_url_thumb = $this -> getFileUrlThumb($width, $height);
		$media_path = VMPATH_ROOT.DS.str_replace('/',DS,$file_url_thumb);

		if (empty($file_url_thumb) || !file_exists($media_path)) {
			$file_url_thumb = $this->createThumb($width,$height);
		}

		if($return) return $this->displayIt($file_url_thumb, $file_alt, $imageArgs,$lightbox,$effect,$withDescr,$absUrl);
	}

	function displayMediaFull($imageArgs='',$lightbox=true,$effect ="class='modal'",$description = true ){



	}

	/**
	 * This function displays the image, when the image is not already a resized one,
	 * it tries to get first the resized one, or create a resized one or fallback in case
	 *
	 * @author Max Milbers
	 *
	 * @param string $imageArgs Attributes to be included in the <img> tag.
	 * @param boolean $lightbox alternative display method
	 * @param string $effect alternative lightbox display
	 * @param boolean $withDesc display the image media description
	 */
	function displayMediaThumb($imageArgs='',$lightbox=true,$effect="class='modal' rel='group'",$return = true,$withDescr = false,$absUrl = false, $width=0,$height=0){


		$imageArgs = $this->imageArgsToArray($imageArgs);

		//vmdebug('displayMediaThumb ');
		$typelessUrl = '';
		if(empty($this->file_name)){
			$typelessUrl = static::getStoriesFb('typeless').'/';
		}

		if( substr( $this->file_url, 0, 2) == "//" ) {
			$toChk = $this->file_url;
			try {
				$resObj = VmConnector::getHttp(array(), array('curl', 'stream'))->get($toChk);
				//vmdebug('Object per URL',$resObj);
				if($resObj->code!=200){
					vmdebug('URL does not exists',$toChk,$resObj);
					vmError(vmText::sprintf('COM_VIRTUEMART_FILE_NOT_FOUND',$toChk));
				};
			} catch (RuntimeException $e) {
				vmError(vmText::sprintf('COM_VIRTUEMART_FILE_NOT_FOUND',$toChk));
			}
		} else {
			if($this->file_is_forSale){
				$toChk = $this->file_url;
			} else {
				$toChk = VMPATH_ROOT.'/'.$this->file_url;
			}
			if(empty($typelessUrl) and !JFile::exists($toChk)){
				vmdebug('Media file does not exists',$toChk);
				vmError(vmText::sprintf('COM_VIRTUEMART_FILE_NOT_FOUND',$toChk));
			}
		}

		//needs file_url_thumb, or  file_url_folder_thumb and file_name and file_extension
		$file_url_thumb = $this -> getFileUrlThumb($width, $height);
	//vmdebug('displayMediaThumb '.$file_url_thumb);
		$media_path = VMPATH_ROOT.DS.str_replace('/',DS,$file_url_thumb);

		if(empty($this->file_meta)){
			if(!empty($this->file_description)){
				$file_alt = $this->file_description;
			} else if(!empty($this->file_name)) {
				$file_alt = $this->file_name;
			} else {
				$file_alt = '';
			}
		} else {
			$file_alt = $this->file_meta;
		}

		if ((empty($file_url_thumb) || !file_exists($media_path)) && is_a($this,'VmImage')) {
			$file_url_thumb = $this->createThumb($width,$height);
			$media_path = VMPATH_ROOT.DS.str_replace('/',DS,$file_url_thumb);
		}
		//$this->file_url_thumb = $file_url_thumb;

		if($withDescr) $withDescr = $this->file_description;

		if (empty($file_url_thumb) || !file_exists($media_path)) {
			return $this->getIcon($imageArgs,$lightbox,$return,$withDescr,$absUrl);
		}

		if($return) return $this->displayIt($file_url_thumb, $file_alt, $imageArgs,$lightbox,$effect,$withDescr,$absUrl);

	}

	function getFileUrlThumb($width = 0,$height = 0){

		if(!empty($this->file_url_thumb)){
			$file_url_thumb = $this->file_url_thumb;
		} else if($this->isImage()) {
			$file_url_thumb = $this->createThumbFileUrl($width,$height);
		} else {
			vmdebug('No image '.$this->file_name);
			$file_url_thumb = '';
		}

		return $file_url_thumb;
	}

	public function createThumbFileUrl($width=0,$height=0){

		$file_name = $this->createThumbName($width,$height);
		if(empty($this->file_name_thumb)) {
			//vmdebug('createThumbFileUrl empty file_name_thumb ',$this);
			return false;
		}
		$file_url_thumb = $this->file_url_folder_thumb.$this->file_name_thumb.'.'.$this->file_extension;
		return $file_url_thumb;
	}

	/**
	 * a small function that ensures that we always build the thumbnail name with the same method
	 */
	public function createThumbName($width=0,$height=0){

		if(empty($this->file_name)) return false;

		$dim = self::determineWH($width, $height);

		$this->file_name_thumb = $this->file_name.'_'.$dim['width'].'x'.$dim['height'];
		return $this->file_name_thumb;
	}

	/**
	 * This function should return later also an icon, if there isnt any automatic thumbnail creation possible
	 * like pdf, zip, ...
	 *
	 * @author Max Milbers
	 * @param string $imageArgs
	 * @param boolean $lightbox
	 */
	function getIcon($imageArgs,$lightbox,$return=false,$withDescr=false,$absUrl = false){

		$file_url = false;
		$file_alt = false;
		static $exists = array();
		$tC = self::$theme_url.'assets/images/vmgeneral/filetype_'.$this->file_extension.'.png';

		if(!empty($this->file_extension)){
			$file_alt = $this->file_description;
			if(!isset($exists[$this->file_extension])){
				$exists[$this->file_extension] = file_exists($tC);
			}

			if($exists[$this->file_extension]){
				$file_url = $tC;
			}
		}

		if(!$file_url){
			$file_url = self::$theme_url.'assets/images/vmgeneral/'.VmConfig::get('no_image_found');
			$file_alt = vmText::_('COM_VIRTUEMART_NO_IMAGE_FOUND').' '.$this->file_description;
		}

		if($return){
			if($this->file_is_downloadable){
				return $this->displayIt($file_url, $file_alt, '',true,'',$withDescr,$absUrl);
			} else {
				return $this->displayIt($file_url, $file_alt, $imageArgs,$lightbox,'',$withDescr,$absUrl);
			}
		}

	}

	/**
	 * This function is just for options how to display an image...
	 * we may add here plugins for displaying images
	 *
	 * @author Max Milbers
	 * @param string $file_url relative Url
	 * @param string $file_alt media description
	 * @param string $imageArgs attributes for displaying the images
	 * @param boolean $lightbox use lightbox
	 */
	public function displayIt( $file_url, $file_alt, $imageArgs, $lightbox, $effect ="class='modal'", $withDesc=false, $absUrl = false){

		if ($withDesc) $desc='<span class="vm-img-desc">'.$withDesc.'</span>';
		else $desc='';
		$root='';

		if( substr( $file_url, 0, 2) == "//" ) {
			$root = JURI::root(true).'/';;
		} else if($absUrl){
			$root = JURI::root(false);
		} else {
			$root = JURI::root(true).'/';
		}

		$args = '';
		if(is_array($imageArgs)){
			foreach($imageArgs as $k=>$v){
				$args .= ' '.$k.'="'.$v.'" ';
			}
		} else {
			$args = $imageArgs;
		}

		if($lightbox){
			$image = '<img src="' . $root.$file_url . '" alt="' . $file_alt . '" ' . $args . ' />';//JHtml::image($file_url, $file_alt, $imageArgs);
			if ($file_alt ) $file_alt = 'title="'.$file_alt.'"';
			if ($file_url and pathinfo($file_url, PATHINFO_EXTENSION) and substr( $file_url, 0, 4) != "http") {
				if($this->file_is_forSale or substr( $file_url, 0, 2) == "//"){
					$href = $file_url ;
				} else {
					$href = JURI::root() .$file_url ;
				}
			} else {
				$href = $root.$file_url ;
			}

			$lightboxImage = '<a '.$file_alt.' '.$effect.' href="'.$href.'">'.$image.'</a>';
			$lightboxImage = $lightboxImage.$desc;

			return $lightboxImage;
		} else {

			return '<img src="' . $root.$file_url . '" alt="' . $file_alt . '" ' . $args . ' />'.$desc;
		}
	}

	public function determineWH($width,$height){

		$dim = array();
		$dim['width'] = $width;
		$dim['height'] = $height;
		if(!$width and !$height){
			$dim['width'] = VmConfig::get('img_width',90);
			$dim['height'] = VmConfig::get('img_height',90);
		}
		$dim['width'] = (int)$dim['width'];
		$dim['height'] = (int)$dim['height'];;

		return $dim;
	}

	/**
	 * This function actually creates the thumb
	 * and when it is instanciated with one of the getImage function automatically updates the db
	 *
	 * @author Max Milbers
	 * @param boolean $save Execute update function
	 * @return name of the thumbnail
	 */
	public function createThumb($width=0,$height=0) {

		if(empty($this->file_url_folder)){
			vmError('Couldnt create thumb, no directory given. Activate vmdebug to understand which database entry is creating this error');
			vmdebug('createThumb, no directory given',$this);
			return FALSE;
		}

		if(empty($this->file_name)){
			if($this->virtuemart_media_id!=0){
				vmError('Couldnt create thumb, no name given. Activate vmdebug to understand which database entry is creating this error');
				vmdebug('createThumb, no name given',$this);
			}
			return false;
		}

		$synchronise = vRequest::getString('synchronise',false);

		if(!VmConfig::get('img_resize_enable') || $synchronise) return;

		//now lets create the thumbnail, saving is done in this function
		$dim = self::determineWH($width, $height);
		$width = $dim['width'];
		$height = $dim['height'];

		// Don't allow sizes beyond 2000 pixels //I dont think that this is good, should be config
//		$width = min($width, 2000);
//		$height = min($height, 2000);

		$maxsize = false;
		$bgred = 255;
		$bggreen = 255;
		$bgblue = 255;

		$root = '';
		$this->file_name_thumb = $this->createThumbName($width,$height);

		$exists = false;
		if( substr( $this->file_url, 0, 2) == "//" ) {
			$fullSizeFilenamePath = $this->file_url;
			$exists = true;
			//$resizedFilenamePath = vRequest::filterPath(VMPATH_ROOT.'/'.$this->file_url_folder_thumb.$this->file_name_thumb);
			vmdebug('Set file url as $fullSizeFilenamePath',$fullSizeFilenamePath,$this->file_name_thumb);
		} else {
			if($this->file_is_forSale==0){

				$fullSizeFilenamePath = VMPATH_ROOT.'/'.$this->file_url_folder.$this->file_name.'.'.$this->file_extension;
			} else {
				$fullSizeFilenamePath = $this->file_url_folder.$this->file_name.'.'.$this->file_extension;
			}
			$fullSizeFilenamePath = vRequest::filterPath($fullSizeFilenamePath);
			$exists = file_exists($fullSizeFilenamePath);

		}
		$resizedFilenamePath = vRequest::filterPath(VMPATH_ROOT.'/'.$this->file_url_folder_thumb.$this->file_name_thumb.'.'.$this->file_extension);

		$this->checkPathCreateFolders(vRequest::filterPath($this->file_url_folder_thumb));

		if ($exists) {
			if(!file_exists($resizedFilenamePath)) {
				$createdImage = new Img2Thumb( $fullSizeFilenamePath, (int)$width, (int)$height, $resizedFilenamePath, $maxsize, $bgred, $bggreen, $bgblue );
				if(!$createdImage){
					return 0;
				}
			}
			return $this->file_url_folder_thumb.$this->file_name_thumb.'.'.$this->file_extension;
		} else {
			vmError('Couldnt create thumb, file not found '.$fullSizeFilenamePath);
			return 0;
		}

	}

	static function getStoriesFb($suffix = ''){

		if(!isset(self::$url[$suffix])){
			self::$url[$suffix] = 'images/virtuemart/'. $suffix ;
			if(JFolder::exists(VMPATH_ROOT .'/'.self::$url[$suffix])) {
				return self::$url[$suffix];
			} else {
				$urlOld = 'images/stories/virtuemart/'. $suffix;
				if(JFolder::exists(VMPATH_ROOT .'/'.$urlOld)){
					self::$url[$suffix] = $urlOld;
					return $urlOld;
				}
			}

			if(JFolder::create(VMPATH_ROOT .'/'.self::$url[$suffix])) {
				return self::$url[$suffix];
			} else {
				self::$url[$suffix] = false;
				return false;
			}
		} else {
			return self::$url[$suffix];
		}

	}
}