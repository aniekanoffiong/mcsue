<?php

require_once('../include/config.php');
require_once('init.php');
session_start();

$user = new Admin($pdo);
echo '<br />';

//$user->addUserDetails ( new Admin($pdo, 1), $user->userId, 'Offiong', 'Aniekanabasi', 'Emmanuel', 'Male', '19 Udo Ekpo Mkpo street', 'Uyo', 'Akwa Ibom', 'Nigeria', '07064704861', 'N/A', 'gentleekan@gmail.com', '2016-05-29');
echo '<br />';
/*$resultSet = $user->getUserDetails ( new Admin($pdo, 1), $user->userId); */
//$user->createNewTraining ( "Sewing 101", "Sewing programme for absolute beginners", "3 Months", "10000.00" );
//echo staticFunc::scoreValue('52');
echo '<br />';
echo '<br />';
/**$user->setUserId();
$assgn = new Assignment;
$assgn->sendAssignment ( $user->getPdo(), 'JASDJLK232LKJ2K', $user->getUserId(), '2016-06-02', 'my name is Aniekan Offiong' )
/**	Testing the Login System
*/
//$user->setUserId('FMDLHWPRC6JS5MD');
//$userId = $user->getUserId(); 
//$name = $user->getNames( $user, $userId );
//echo $name['names'];
//$user->setUserId();
//$user->createUser( 'gentleekan', 'gentle11', 'Admin' );
$user->login('gentleekan', 'gentle11');

/*$user->setUserId('JLASD129FJ2JF9S');
echo '<br />';
echo $user->getUserId();
echo '<br />';
echo '<br />';
$user->setUserId();
echo '<br />';
echo $user->getUserId();

//$debt = new DebtRecord;
//$debt->createDebtRecord ( $user->getPdo(), $user->getUserId(), 'HKJA2SD6', '15000', '2016-06-15' );

/**Testing Reminders */
/*$reminder = new Reminder;
$totalReminders = $reminder->getReminder($user->getPdo());
$reminderdetails = $reminder->getReminderDetails ( $user->getPdo(), $totalReminders['reminder_id'], $totalReminders['item_type'] );
var_dump($reminderdetails);
//echo $reminder->setReminder ( $user->getPdo(), $debt, $debt->getItemId() );
//echo staticFunc::compareDates ('2016-06-03');
//$result = new StudentResult;
//echo $result->createResult ( $user->pdo, 'H77UAENC6NW96Z7', 'SEW 202', 66);
//$user->createDesign
/*Method 1 to retrieve array data, key-value pairs
$i = 1;
foreach ($resultSet as $key => $val) {
	echo "Result Set ".$i++.'<br />';
	echo $val['staff_id'].'<br />';
	echo $val['surname'].'<br />';
	echo $val['firstname'].'<br />';
	echo $val['othername'].'<br />';
	echo $val['gender'].'<br />';
	echo $val['phone'].'<br />';
	echo $val['phone_alt'].'<br />';
	echo $val['email'].'<br />';
	echo '<br /><br />';
}
/*Method 2 to retrieve array data 
while ($val = array_shift($resultSet)) {
	echo $val['staff_id'].'<br />';
	echo $val['surname'].'<br />';
	echo $val['firstname'].'<br />';
	echo $val['othername'].'<br />';
	echo $val['gender'].'<br />';
	echo $val['phone'].'<br />';
	echo $val['phone_alt'].'<br />';
	echo $val['email'].'<br />';
	echo '<br />';
}


/*
*/
/*$user->setUserType(new Admin($pdo, 1));
echo '<br />';
$user->setUserType(new Student($pdo, 1));
echo '<br />';
$user->setUserType(new Customer($pdo, 1));
/*echo '<br />';
$user2 = new Users($pdo, 1);
echo '<br />';
echo '<br />';
echo '<br />';
$admin = new Admin($pdo);
echo '<br />';
echo '<br />';
echo $admin->getUserId();
echo '<br />';
echo '<br />';
$admin = new Admin($pdo, 1);
echo '<br />';
echo '<br />';
echo $adminId = $admin->getUserId();
*/

//$admin->getDesigns();
//$admin = createAdmin ( 'gentleekan2', 'gentle123', 1 );
//$admin->login('gentleekan2', 'gentle123');
//echo '<br />';
//$design = new Designs($pdo, "my Name", "photo.jpg", "1500.00");
//$admin->getDesignDetails ( 1 );
//echo '<br />';
//echo $hash_password = password_hash('gentle11', PASSWORD_DEFAULT);

		
//$design = new Designs($pdo);
//$design->getDetails (1);