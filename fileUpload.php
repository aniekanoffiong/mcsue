<?php
		//Check that the file is in an image format
		if (isset($_POST['submit'])) {
			$target_dir = "users/";
			//generate random Id string for storing file
			$generateId = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", 15)), 0, 25);
			$newPhotoName = 'photo' . $generateId;
			$target_file = $target_dir . basename($_FILES["file1"]["name"]);
			$uploadOk = 1;
			$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
			$uploadedFile = $target_dir . $newPhotoName. '.'.$imageFileType;
			// Check if image file is a actual image or fake image
			if(isset($_POST)) {
				$check = getimagesize($_FILES["file1"]["tmp_name"]);
				if($check !== false) {
					echo "File is an image - " . $check["mime"] . ".";
					$uploadOk = 1;
				} else {
					echo "File is not an image.";
					$uploadOk = 0;
				}
			}
			// Check file size not larger than 100 KB
			if ($_FILES["file1"]["size"] > 100000) {
				echo "Sorry, your file is too large.";
				$uploadOk = 0;
			}
			// Allow certain file formats
			if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
			&& $imageFileType != "gif" ) {
				echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
				$uploadOk = 0;
			}
			// Check if $uploadOk is set to 0 by an error
			if ($uploadOk == 0) {
				echo "Sorry, your file was not uploaded.";
			// if everything is ok, try to upload file
			} else {
				if (move_uploaded_file($_FILES["file1"]["tmp_name"], $uploadedFile)) {
					echo "The file was Successfully Uploaded";
				} else {
					echo "Sorry, there was an error uploading your file.";
				}
			}
		}