<?php require_once('class/init_2.php');

	if (isset($_POST)) {
		$user = new Users;
		$user->uploadUserPhoto ( 'photo' );
	}
?>

<html>
<head>
	<script src="js/jquery-1.12.4.min.js"></script>
	<script src="js/ajax.js"></script>

</head>
<body>
	<script>
		$(function() {
			console.log('Window Started!');
		
		});
	</script>
	<form action="upload.php" method="POST" enctype="multipart/form-data" id="file-form">
		<input type="file" name="file1" id="file1"/><br />
		<input type="submit" name="submit" value="Upload File" id="setUploadFile" />
	</form>
	<div id="response"></div>
	<span id="report"></span>
</body>
</html>