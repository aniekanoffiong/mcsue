<?php

	if (isset($_GET['url']) && isset($_GET['name'])) {
		$url = urldecode($_GET['url']);
		$names = staticFunc::maskURLParam($_GET['name']);
		if (file_exists($url) && is_file($url)) {
			
			$info = getimagesize($url);
			$fs = filesize($url);
			//Send content information
			header("Content-Type: {$info['mime']}\n");
			header("Content-Disposition: inline; filename= \"$names\"\n");
			header("Content-Length: $fs\n");
		//	header("X-Sendfile: $url");
			//Send the file
			ob_clean();
			flush();
			$img = readfile($url);
			if ( $info['mime'] == image/jpeg ) {
				imagejpeg($img, NULL, 75);
			} elseif ( $info['mime'] == image/gif ) {
				if (!@imagecreatefromgif($url)) {
					return 'Wrong Image Type';
				} else {
					imagegif($img, NULL, 75);
				}
			} elseif ( $info['mime'] == image/gif ) {
				imagepng($img, NULL, 75);
			} else {
				return 'Wrong Image Type';
			}		
		} else {
			echo 'Invalid Image';
		}
	} else {
		echo 'Invalid Image';
	}