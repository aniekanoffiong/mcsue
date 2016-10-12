<html>
<head>
<style>
.switch {
	position: relative;
}
  .cmn-toggle {
    position: absolute;
    margin-left: -9999px;
    visibility: hidden;
	opacity: 0;
  }
  .cmn-toggle + label {
    display: inline-block;
    position: relative;
    cursor: pointer;
    outline: none;
    user-select: none;
  }
  input.cmn-toggle-round + label {
    padding: 2px;
    width: 40px;
    height: 20px;
    background-color: #dddddd;
    border-radius: 20px;
    -webkit-border-radius: 20px;
    -moz-border-radius: 20px;
  }
  input.cmn-toggle-round + label:before,
  input.cmn-toggle-round + label:after {
    display: block;
    position: absolute;
    top: 1px;
    left: 1px;
    bottom: 1px;
    content: "";
  }
  input.cmn-toggle-round + label:before {
    right: 1px;
    background-color: #8ce196;
    border-radius: 20px;
    -webkit-border-radius: 20px;
    -moz-border-radius: 20px;
    transition: background 0.4s;
    -webkit-transition: background 0.4s;
  }
  input.cmn-toggle-round + label:after {
    width:24px;
    background-color: #fff;
    border-radius: 12px;
    -webkit-border-radius: 12px;
    -moz-border-radius: 12px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    -webkit-box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    transition: margin 0.4s;
    -webkit-transition: margin 0.4s;
  }
  input.cmn-toggle-round:checked + label:before {
    background-color: #8ce196;
  }
  input.cmn-toggle-round:checked + label:after {
    margin-left: 20px;
  }
</style>
</head>
<body>
<?php 
function reArrayFiles() {
	$file_ary = array();
	$file_count = count($_FILES['files']['name']);
	$file_keys = array_keys($_FILES['files']);
	
	for ( $i = 0; $i < $file_count; $i++ ) {
		foreach ($file_keys as $key) {
			$file_ary[$i][$key] = $_FILES['files'][$key][$i];
		}		
	}
	return $file_ary;
}


if (isset($_POST)) {
	var_dump($_FILES);
	var_dump($_POST);
	
	$_FILES = reArrayFiles();
	
	var_dump($_FILES);
	foreach ($_FILES as $value) {
		$_FILES['file1'] = $value;
		echo 'New Array';
		var_dump ($_FILES);
		$imgURL2[] = $_FILES['file1'];
	}
	
	$imgURL[] = 'Now';
	$imgURL[] = 'Now2';
	var_dump($imgURL2);
	var_dump($imgURL);
	
	echo $state = (is_array($imgURL[0])) ? 'Is Array' : 'Is Not Array';
	echo $state2 = (is_array($imgURL2[0])) ? 'Is Array' : 'Is Not Array';
	
	
	
	
	//	$_FILES['file1']['name'];
}
?>
<form action="toggle.php" method='post' enctype="multipart/form-data">
<div class="switch">
  <div style="display: inline-block; border: 1px solid #eee; padding: 3px; position: relative; top: -6;">Current Orders</div>
  <input id="cmn-toggle-1" class="cmn-toggle cmn-toggle-round" type="checkbox" name='check' value="m89p23p4i" <?php if (isset($_POST['check'])) {echo 'checked'; } ?>>
  <label for="cmn-toggle-1"></label>
  <div style="display: inline-block; border: 1px solid #eee; padding: 3px; position: relative; top: -6; font-weight: bold; color: #000;">All Orders</div>
  <input type="file" name="files[]" />
  <input type="file" name="files[]" />
  <input type='submit' name='submit' />
</div>
</form>
</body>
</html>