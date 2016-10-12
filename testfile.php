<?php 
require_once('include/config.php');
require_once('class/init.php');
session_start();

$sql = "SELECT start_time FROM timetable_tbl WHERE start_time = '9:00:00' AND day = 'Monday'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
echo gettype($stmt->fetchColumn());

$sql = "SELECT prog_id, start_time FROM timetable_tbl WHERE start_time = '9:00:00' AND start_time = '11:00:00' <= start_time BETWEEN ':00:00' AND '11:00:00' AND day = 'Monday'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
echo gettype($stmt->fetchAll());

$NAME = 'ANIEKAN';
$Name = 'Aniekan';
$name = 'aniekan';
$naMe = 'aNiekan';

echo $name. ',  '. $Name . ',  '. $NAME;
echo $naMe;


// JS

			/*$('#reply').click(function(evt){
		//evt.preventDefault();
		alert('clicked');
		$('.thumbnail-mail-span').each(function(index, item) {
			listOfSenders += $(item).attr('id') + ';';
		});
		var finalList = listOfSenders.slice(0,-1);
		$('#replySubmit').val(finalList);
	});
 
	$('#forward').click(function(evt){
		evt.preventDefault();
		alert('clicked All');
		$('.thumbnail-mail-span').each(function(index, item) {
			listOfSenders += $(item).attr('id') + ';';
		});
		var finalList = listOfSenders.slice(0,-1);
		
	});
 	
 	$('#deleteMsg').click(function(evt){
		evt.preventDefault();
		alert('Delete Clicked');
	});*/

		
		//	var html = '<span class="text-info bold">Image Title: </span><input type="text" name="updatePhotoTitle2" class="new_element" id="photoName2" placeholder="Enter Selected Image Title" /><span class="help-info">Current Image Will Be Replaced</span>';
		

	//var getDetails = JSON.parse (userDetails); 
		//console.log(getDetails);
		//console.log(userDetails.photo);
		//console.log(userDetails.name);
		//$('#senderDetails').append(generatingElement);
		//$('#messageContent').html('My Name');
		//e('senderDetails').innerHTML = 'My Name';
		//e('messageContent').innerHTML = 'My Name';
	


			/*setTimeOut(function() {
				$('#addedPreviously').hide();
			}, 5000);

			//.delay(5000).fadeOut().addClass('hidden');

			//$('#addedPreviously').show().delay(5000).fadeOut();
			return;
		}*/
		/*var confirmUser = confirmUserId(userId);
		if (confirmUser) {
			return;
		}*/
		//alert(userId);
	

			/*$('.markToDelete').each(function(e) {
				if (!$(this).is(':checked')) {
					unchecked = 'true';
				}
			});
			if (unchecked == 'true') {
				$('#headSection').addClass('hidden');
			}*/
	

/*echo '<hr />';
$user=new User($pdo);
$user->testClass();
echo '<hr />';
$myVal = 'item1, item2, item3, item4, item5, item6, item7, item8';
$name = explode(',', $myVal, 6);
$sizes = (['xs' => 1, 'sm' => 2,'md' => 3, 'lg' => 4, 'xl' => 5]);
var_dump($sizes);
foreach ($name as $val) {
	echo trim($val).'<br />';
}
echo base64_decode('ZGVzaWducyUyRmRlc2lnbmdtNG1ReHh0dTZhd3FUaER1bkh6SDlPcjEuanBn');
echo '<hr />';
$sql = "SELECT DATEDIFF(order_date, curdate()) < 0 FROM orders_tbl WHERE order_id = 'KHJ12J3K'";
echo $query = $pdo->query($sql)->fetchColumn();

$log = new Log;
$log->createLog ( $pdo, 'FMDLHWPRC6JS5MDJAHDH' );
echo '<hr />';
if ( 8 == '08') {
	echo  '8 = 08';
}
?>
<form method="post" action="<?php echo basename($_SERVER['PHP_SELF']); ?>">
	<input type="text" name="username">
	<input type="password" name="password">
	<input type="checkbox" name="check1" value="getPhoto1">
	<input type="checkbox" name="check2" value="getPhoto2">
	<input type="submit" name="submit" value="Send">
</form>
<?php
$userId = $user->getUserId(); 
$name = $user->getNames( $user, $userId );
$userDetails = $user->getUserDetails( $user, $user->getUserId() );
$userPhoto = $user->getUserImage ( $name['names'], $userDetails['photo']);

$newUI->htmlHeader();
$newUI->homeTopSection();
$newUI->leftSideBar();
$newUI->mainSection();

?>
<div class="item">
Digital signal processing (DSP) refers to various techniques for improving the accuracy and reliability of digital communications. The theory behind DSP is quite complex. Basically, DSP works by clarifying, or standardizing, the levels or states of a digital signal. ADSP circuit is able to differentiate between human-made signals, which are orderly, and noise, which is inherently chaotic.
</div>
<img src="../../photoBu3BFd2KVu78UKahxM1Lo1v38.jpg" />
<?php $newUI->bottomSection();
echo $input = '<bread> and<b> hello</bread> tea. <script> alert(a); </script>';
echo '</b>';
echo '<br />'. $name = urlencode($input );
echo '<br />'.strip_tags($input );
echo '<br />'.preg_replace('/<|>/', '', $input);
echo '<br /><br /><br /><br />';
echo $page = $_SERVER['PHP_SELF'];
$pagelink = explode('.', $page, 2);
echo '<br /><br /><br /><br />';
echo $pagelink[0];
$newUser = new Admin($pdo);
print_r($newName = $newUser->getUserDetails( $newUser, $_SESSION['userId'] ));
if ($image = @getimagesize(urldecode($newName['photo']))) {
?>
<img src='../class/view.php?url=<?php echo $newName['photo']; ?>&name=<?php echo urlencode($newName['names']);?>'<?php echo $image[3]; ?> />
<?php
}
?><!--
<!DOCTYPE html>
<html>
    <head>
        <title>WELCOME TO McSue App</title>
        <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
		
	</head>
    <body>
		<div class="content">
			<header>
				<img src="img/mcsue.gif" width="140.4" height="69.3">
				<link rel="stylesheet" href="styles/styles.css" type="text/css"/> 
			</header>
			<hr>
			<div class="login-div">Admin<br />Login</div>
			<div class="login-div">Customer<br />Login</div>
			<div class="login-div">Student<br />Login</div><br />
			<?php /**
				$user = new Admin;
				$user->setUserId($_SESSION['userId']);
				echo '<h1>'. $user->getUserId() .'</h1>'; */?>
		</div>
	</body>
</html>