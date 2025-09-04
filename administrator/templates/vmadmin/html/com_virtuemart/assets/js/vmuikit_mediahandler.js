/**
 * mediahandler.js: for VirtueMart Mediahandler
 *
 * @package    VirtueMart
 * @subpackage Javascript Library
 * @authors    Patrick Kohl, Max Milbers, Valerie Isaksen
 * @copyright  Copyright (c) 2011-2021 VirtueMart Team. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

jQuery(document).ready(function ($) {

	//media = $('#vmuikit-js-search-media').data()
	Virtuemart.start = 0
	var searchMedia = $('input#vmuikit-js-search-media')
	searchMedia.on('click', function () {
		Virtuemart.start = 0;
		console.log('Mediahandler set Virtuemart.start = 0');
	})
	var searchMediaAuto = searchMedia.autocomplete({
		source:Virtuemart.medialink,
		position: {
			offset: '-8 0' // Shift 20px to the left, 4px down.
		},
		select:function (event, ui) {
			var item = ui.item
			//	image.file_url_thumb_img='<img src="'+item.file_url_thumb+'" alt="'+item.file_title+'" />'
			var template = $('#display-selected-media-template').html()
			var rendered = Mustache.render(template, {'medias':ui.item})
			var container = $('#vmuikit-js-medias-container')
			container.append(rendered);

			$(this).val('')
			//container.vmuikitmedia('media',Virtuemart.mediaType,'0')
			event.preventDefault()
		},
		minLength:1,
		delay:400,
		html:true
	})

	if( typeof searchMediaAuto.data('ui-autocomplete') !== "undefined" ){
		searchMediaAuto.data('ui-autocomplete')._resizeMenu = function () {
			var width = $('.search-media-boundary').outerWidth()
			this.menu.element.outerWidth(width)
		}

		searchMediaAuto.data('ui-autocomplete')._renderItem = function (ul, item) {
			// sublayouts/mustache/search_media.php
			var template = $('#search-media-template').html()
			var rendered = Mustache.render(template, {'media':item})
			ul.addClass('uk-child-width-1-2@s uk-child-width-1-4@m uk-child-width-1-6@l') //Ul custom class here
			return $('<li>')
				.append('<a>' + rendered + '</a>')
				.appendTo(ul)
		}
	}

	
	$('.vmuikit-js-pages').on('click', function (e) {
		e.preventDefault();
		console.log('clicked  vmuikit-js-pages',Virtuemart.start);
		if (searchMedia.val() =='') {
			searchMedia.val(' ');
			Virtuemart.start = 0;
		} else if ($(this).hasClass('vmuikit-js-next')) Virtuemart.start = Virtuemart.start+16 ;
		else if (Virtuemart.start > 0) Virtuemart.start = Virtuemart.start-16 ;
		searchMedia.autocomplete( 'option' , 'source' , Virtuemart.medialink+'&start='+Virtuemart.start );
		searchMedia.autocomplete( 'search');
	});
	
	
	function imgPreviewCard (readerResult, filename) {
		$('#vmuikit-image-preview').removeClass('uk-hidden')
		$('#image-preview-card-title').text(filename)
		
		const img = document.createElement('img')
		img.setAttribute('id', 'img-preview-responsive')
		img.setAttribute('src', readerResult)
		img.setAttribute('data-name', filename)
		img.setAttribute('alt', filename)
		$('#image-preview-card-image').html(img)
		return
	}
	
	$('[name="upload"]').on('change', function (e) {
		e.preventDefault()
		const file = e.target.files[0]
		
		/* TODO
		const fileInput = uploadContainer.querySelector('.uk-form-custom>input')
		const preview = uploadContainer.querySelector('#image-preview')
		const alert = uploadContainer.parentElement.querySelector('.uk-upload-box>#vmuikit-error-alert-file-upload')
		const alertMessage = uploadContainer.parentElement.querySelector('.uk-upload-box>#vmuikit-error-alert-file-upload>div')
		*/
		let filename = file['name']
		
		/* TODO Can do some checkings on client side
		* 	 acceptedDocMimes = ['application/pdf', 'image/png', 'image/jpeg']
				 size = file['size']
				 fileType = file['type']
				 * add errors in alert box
		* */
		const reader = new FileReader()
		reader.onload = () => {
			imgPreviewCard(reader.result, filename)
		}
		reader.readAsDataURL(file)
		
		var media_action = $('#vmuikit-js-upload').find('[name=\'media[media_action]\']:checked')
		if (typeof $(media_action[0]).val() != 'undefined' && $(media_action[0]).val() == 0) {
			var mediaActionDefaultChecked = $('#vmuikit-js-upload').find('[id=\'media[media_action]upload\']')
			if (mediaActionDefaultChecked.length == 0) {
				mediaActionDefaultChecked = $('#vmuikit-js-upload').find('[id=\'media[media_action]replace\']')
			}
			mediaActionDefaultChecked.attr('checked', true)
		}
		
	})
	$('.vmuikit-media-action').on('change', function () {
		var media_action = $('#vmuikit-js-upload').find('[name=\'media[media_action]\']:checked')
		if (typeof $(media_action[0]).val() != 'undefined' && $(media_action[0]).val() == 0) {
			$('#vmuikit-image-preview').addClass('uk-hidden')
			$('#image-preview-card-title').text('')
			$('#image-preview-card-image').text('')
		}
	})
	
	$('#vmuikit-js-medias-container').sortable({
		update:function (event, ui) {
			$(this).find('.ordering').each(function (index, element) {
				$(element).val(index)
			})
		}
	})

	$('#vmuikit-js-medias-container').on('click', '.vmuikit-js-edit-image', function (e) {
		//var virtuemart_media_id = $(this).parent().find("input").val();

		$("#vmuikit-js-medias-container").find('.uk-card-vm').removeClass("vmuikit-js-thumb_image-selected");
		var closest = $(this).closest('.vmuikit-js-thumb-image')
		closest.find('.uk-card-vm').addClass("vmuikit-js-thumb_image-selected");
		var virtuemart_media_id = closest.find('input[name=\'virtuemart_media_id[]\']').val()
		console.log('edit-image', virtuemart_media_id)
		$.getJSON('index.php?option=com_virtuemart&view=media&format=json&virtuemart_media_id=' + virtuemart_media_id,
				function (datas, textStatus) {
					if (datas.msg == 'OK') {
						$('#vmuikit-js-display-info').attr('src', datas.file_root + datas.file_url)
						$('#vmuikit-js-display-info').attr('alt', datas.file_title)
						$('#file_title').html(datas.file_title)
						if (datas.published == 1) $('#adminForm [name=\'media[media_published]\']').attr('checked', true)
						else $('#adminForm [name=media_published]').attr('checked', false)
						if (datas.file_is_downloadable == 0) {
							$('#media_rolesfile_is_displayable').attr('checked', true)
							//$("#adminForm [name=media_roles]").filter("value='file_is_downloadable'").attr('checked', false);
						} else {
							//$("#adminForm [name=media_roles]").filter("value='file_is_displayable'").attr('checked', false);
							$('#media_rolesfile_is_downloadable').attr('checked', true)
						}
						$('#adminForm [name=\'media[file_title]\']').val(datas.file_title)
						$('#adminForm [name=\'media[file_description]\']').val(datas.file_description)
						$('#adminForm [name=\'media[file_meta]\']').val(datas.file_meta)
						$('#adminForm [name=\'media[file_class]\']').val(datas.file_class)
						$('#adminForm [name=\'media[file_url]\']').val(datas.file_url)
						$('#adminForm [name=\'media[file_url_thumb]\']').val(datas.file_url_thumb)
						var lang = datas.file_lang.split(',')
						$('#adminForm [name=\'media[active_languages][]\']').val(lang).trigger('liszt:updated')
						$('[name=\'media[active_media_id]\']').val(datas.virtuemart_media_id)
						if (typeof datas.file_url_thumb !== 'undefined') {
							$('.vmuikit-js-info-image').attr('src', datas.file_root + datas.file_url_thumb_dyn)
						} else {
							$('.vmuikit-js-info-image').attr('src', '')
						}
					} else $('#file_title').html(datas.msg)
				})
	})

	$('#media-dialog').on('click', '.vmuikit-js-thumb-image', function (event) {
		event.preventDefault()
		var id = $(this).find('input').val(), ok = 0
		var inputArray = new Array()
		$('#vmuikit-js-medias-container input:hidden').each(
			function () {
				inputArray.push($(this).val())
			}
		)
	})


});


