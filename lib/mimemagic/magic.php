<?php

	$mimemagic = array(
		/* Media */
		/* -- Images -- */
		'image/jpeg' => array('start' => '0', 'type' => 'hex', 'value' => 'ffd8ff'),
		'image/gif' => array('start' => '0', 'type' => 'string', 'value' => 'GIF'),
		'image/png' => array('start' => '0', 'type' => 'hex', 'value' => '89504E47'),
		'image/bmp' => array('start' => '0', 'type' => 'string', 'value' => 'BM'),
		'image/photoshop' => array('start' => '0', 'type' => 'string', 'value' => '8BPS'),
		
		/* -- Audio -- */
		'application/ogg' => array('start' => '0', 'type' => 'string', 'value' => 'OggS'),
		'audio/midi' => array('start' => '0', 'type' => 'string', 'value' => 'MThd'),
		'audio/x-pn-realaudio' => array('start' => '0', 'type' => 'hex', 'value' => '2e524d460000001200'),
		'audio/x-wav' => array(
			array('start' => '8', 'type' => 'string', 'value' => 'WAVE'),
			array('start' => '8', 'type' => 'string', 'value' => 'WAV'),
		),
		'audio/mp3' => array(
			array('start' => '0', 'type' => 'hex', 'value' => 'fffb'),
			array('start' => '0', 'type' => 'string', 'value' => 'ID3')
		),
		
		/* -- Video -- */
		'video/x-flv' => array('start' => '0', 'type' => 'string', 'value' => 'FLV'),
		'video/mpeg' => array(
			array('start' => '0', 'type' => 'hex', 'value' => '000001ba'), 
			array('start' => '0', 'type' => 'hex', 'value' => '000001bb'),
		),		
		'video/h264' => array(
			'start' => '0', 'type' => 'string', 'value' => 'blah'
		),
		'video/x-matroska' => array('start' => '0', 'type' => 'hex', 'value' => '1a45dfa3934282886d6174726f736b61428781014285810118538067'),
		'video/mp4' => array('start' => '4', 'type' => 'string', 'value' => 'ftyp',
			'dependencies' => array('start' => '8', 'type' => 'string', 'value' => 'isom'),
			'dependencies' => array('start' => '8', 'type' => 'string', 'value' => 'mp41'),
			'dependencies' => array('start' => '8', 'type' => 'string', 'value' => 'mp42'),
			'dependencies' => array('start' => '8', 'type' => 'string', 'value' => 'mmp4'),
			'dependencies' => array('start' => '8', 'type' => 'string', 'value' => 'M4A'),
			'dependencies' => array('start' => '8', 'type' => 'string', 'value' => 'M4V'),
		),
		'video/quicktime' => array(
			array('start' => '4', 'type' => 'string', 'value' => 'mdat'),
			array('start' => '24', 'type' => 'string', 'value' => 'moov'),
			array('start' => '4', 'type' => 'string', 'value' => 'wide'),
			array('start' => '4', 'type' => 'string', 'value' => 'skip'),
			array('start' => '24', 'type' => 'string', 'value' => 'free'),
			array('start' => '4', 'type' => 'string', 'value' => 'idat'),
			array('start' => '4', 'type' => 'string', 'value' => 'idsc'),
			array('start' => '4', 'type' => 'string', 'value' => 'pnot'),
			array('start' => '0', 'type' => 'hex', 'value' => '6d646174'),
			array('start' => '0', 'type' => 'hex', 'value' => '000000206674797071742020200503007174'),
		),
		'video/x-msvideo' => array(
			array('start' => '0', 'type' => 'hex', 'value' => '52494646', 
					'dependencies' => array('start' => '8', 'type' => 'hex', 'value' => '415649204c495354')
			),
		),
		'video/x-ms-wmv' => array(
			array('start' => '0', 'type' => 'hex', 'value' => '3026B2758E66CF11A6D900AA0062CE6C'),
		),
		/* -- Flash -- */
		'application/x-shockwave-flash' => array(
			array('start' => '0', 'type' => 'hex', 'value' => '435753'),
			array('start' => '0', 'type' => 'hex', 'value' => '465753'),
		),
		
		/* Documents */
		'application/excel' => array(
			array('start' => '2114', 'type' => 'string', 'value' => 'Biff5'),
			array('start' => '2080', 'type' => 'string', 'value' => 'Microsoft Excel 5.0 Worksheet'),
			array('start' => '512', 'type' => 'hex', 'value' => '0908100000060500'),
			array('start' => '512', 'type' => 'hex', 'value' => 'fdffffff1002'),
			array('start' => '512', 'type' => 'hex', 'value' => 'fdffffff2202'),
			array('start' => '512', 'type' => 'hex', 'value' => 'fdffffff2302'),
			array('start' => '512', 'type' => 'hex', 'value' => 'fdffffff2802'),
			array('start' => '512', 'type' => 'hex', 'value' => 'fdffffff2902'),
			array('start' => '512', 'type' => 'hex', 'value' => 'fdffffff20000000'),
		),
		'application/msword' => array(
			array('start' => '0', 'type' => 'hex', 'value' => '31be0000'),
			array('start' => '512', 'type' => 'string', 'value' => 'PO^Q`'),
			array('start' => '0', 'type' => 'oct', 'value' => '\376\067\0\043'),
			array('start' => '0', 'type' => 'oct', 'value' => '\61\276\0\0'),
			array('start' => '0', 'type' => 'oct', 'value' => '\120\117\136\121\140'),
			array('start' => '512', 'type' => 'oct', 'value' => '\354\245\301'),
			array('start' => '0', 'type' => 'oct', 'value' => '\320\317\021\340\241\261\032\341'),
			array('start' => '2112', 'type' => 'string', 'value' => 'MSWordDoc'),
			array('start' => '2108', 'type' => 'string', 'value' => 'MSWordDoc'),
			array('start' => '2112', 'type' => 'string', 'value' => 'Microsoft Word document data'),
			array('start' => '2080', 'type' => 'oct', 'value' => '\115\151\143\162\157\163\157\146\164\40\127\157\162\144\40\66\56\60\40\104\157\143\165\155\145\156\164'),
			array('start' => '2080', 'type' => 'oct', 'value' => '\104\157\143\165\155\145\156\164\157\40\115\151\143\162\157\163\157\146\164\40\127\157\162\144\40\66'),
		),
		'application/msaccess' => array('start' => '4', 'type' => 'string', 'value' => 'Standard Jet DB'),
		'application/vnd.ms-powerpoint' => array('start' => '512', 'type' => 'hex', 'value' => '006e1ef0'),
		'text/rtf' => array('start' => '0', 'type' => 'hex', 'value' => '7b5c72746631'),
		
		'application/pdf' => array('start' => '0', 'type' => 'string', 'value' => '%PDF-'),

		'text/html' => array(
			array('start' => '0', 'type' => 'string', 'value' => '<!DOCTYPE html'),
			array('start' => '0', 'type' => 'string', 'value' => '<head'),
			array('start' => '0', 'type' => 'string', 'value' => '<title'),
			array('start' => '0', 'type' => 'string', 'value' => '<html'),
			array('start' => '0', 'type' => 'string', 'value' => '<!--'),
			array('start' => '0', 'type' => 'string', 'value' => '<h1'),
		),
		
		/* Archives */
		'application/x-rar' => array('start' => '0', 'type' => 'string', 'value' => 'Rar!'),
		'application/application/x-rar-compressed' => array('start' => '0', 'type' => 'hex', 'value' => '526172211a'),
		'application/x-gzip' => array('start' => '0', 'type' => 'hex', 'value' => '1F8B08'),
		'application/zip' => array(
			array('start' => '0', 'type' => 'hex', 'value' => '504B0304'),
			array('start' => '30', 'type' => 'hex', 'value' => '504b4c495445'),
			array('start' => '29152', 'type' => 'hex', 'value' => '57696e5a6970'),
			array('start' => '0', 'type' => 'hex', 'value' => '504B3030504B0304'),
		),
		'application/x-bzip2' => array('start' => '0', 'type' => 'hex', 'value' => '425a683931'),
		'application/x-tar' => array('start' => '257', 'type' => 'string', 'value' => 'ustar'),

		/* Other */
		'font/ttf' => array(
			array('start' => '0', 'type' => 'oct', 'value' => '\0\1\0\0\0'),
			array('start' => '0', 'type' => 'oct', 'value' => '\106\106\111\114'),
		),
			
	);

?>