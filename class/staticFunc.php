<?php

class staticFunc {

	public static $forgotPassword = NULL;
	public static $formInput = array();
	public static $pref = array();
	public static $defaultPref = array( 0 => array('view_orders' => 'current', 'reminders' => 'auto', 'designs_access' => 'all', 'payment_schedule' => 'full', 'payment_instalment' => 1, 'remain_logged_in' => 10, 'table_limit' => 15));	
	
	public static function userPreferences ( $pdo ) {
		//Unset $_SESSION['mailMessages']; used in class Mail Messages for flow between message types
		if (isset($_SESSION['mailMessages'])) {
			//If I leave the messages page to another page, reset the SESSION . . .
			if ((isset($_SERVER['HTTP_REFERER'])) && basename($_SERVER['HTTP_REFERER']) == 'messages.php' && basename($_SERVER['PHP_SELF']) !== 'messages.php') {
				unset($_SESSION['mailMessages']);
			}
		}
		//Only Admin Users can are able to set preferences
		if ($_SESSION['userType'] !== 'Admin') {
			self::$pref = self::$defaultPref;
		} else {
			$sql = "SELECT * FROM preferences_tbl WHERE user_id = :userId";
			$stmt= $pdo->prepare($sql);
			$stmt->execute([':userId' => $_SESSION['userId'] ]);
			$data = $stmt->fetchAll();
			if ($data) {
				self::$pref = $data;
			} else {
				//Initialize Database User Preferences To Default
				$newObject = new Users;
				$defaultSettings[':user_id'] = $_SESSION['userId'];
				foreach (self::$defaultPref[0] as $key => $value) {
					$defaultSettings[":$key"] = $value;
				}
				$newObject->initUserPreferences ( $defaultSettings, $pdo );
				//Set Preferences to Default
				self::$pref = self::$defaultPref;
			}
		}
	}
	
	//Handles All Form Submissions
	public static function formHandling ( $pdo ) {
		//LOGIN FORM HANDLING
		if ( (isset($_POST['loginSubmitForm']) && isset($_POST['confirmLoginForm'])) ) {
			//Validate $username and $password data
			$username = self::sanitize($_POST['username']);
			$password = self::sanitize($_POST['password']);
			//Confirm $username in database
			$stmt = $pdo->prepare("SELECT * FROM login_tbl WHERE username = :username");
			$stmt->execute([':username' => $username]);
			$foundUser = $stmt->fetch();
			if ($foundUser) {
				//Confirm the user password matches database record
				$confirmPass = password_verify($password, $foundUser['hash_pass']);
				if ($confirmPass) {
					//Create Log for User Login
					$createLog = new Log;
					if ( $createLog->createLog( $pdo, $foundUser['user_id'] ) == 'success' ) {
						//Set Session Variable if password authenticates
						$_SESSION['userId'] = $foundUser['user_id'];
						$_SESSION['userType'] = $foundUser['user_type'];
						//Redirect to Admin Dashboard
						//self::redirect("mcsueapp/index.php");
						self::redirect("mcsue/index.php");
					} else {
						//Error caused by inability to write log
						self::redirect("index.php?login=failed");
					}
				} else {
					//Display Page with error shown
					$type = 'error';
					$msg = '<b>Your Login Attempt Failed <br /> Please Check That &nbsp;<kbd>CAPS LOCK</kbd>&nbsp; is Turned Off and Try Again</b>';
					self::alertDisplay( $type, $msg );
				}
			} else {
				//Display Page with error shown
				$type = 'error';
				$msg = '<b>Your Login Attempt Failed <br /> Please Check That &nbsp;<kbd>CAPS LOCK</kbd>&nbsp; is Turned Off and Try Again</b>';
				self::alertDisplay( $type, $msg );
			}
		}
		//REGISTRATION FOR TRAINING/PROGRAMME FORM HANDLING
		if (isset($_POST['submitDeleteCourse']) && isset($_POST['deleteCourseForm'])) {
			$progId = self::unmaskURLParam($_POST['deleteCourseForm']);
			if (in_array($progId, $_SESSION['registerProgramme'])) {				
				unset($_SESSION['registerProgramme'][$progId]);
			}
		} elseif (isset($_POST['addAssgnSubmit']) && isset($_POST['addAssgnForm'])) {
			$progId = self::unmaskURLParam($_POST['selectCourse']);
			$assgnTitle = self::sanitize($_POST['assignmentTitle']);
			$deadlineDate = self::dateValidator ( $_POST['assgnDeadlineDate'] );
			$deadlineTime = (isset($_POST['assgnDeadlineTime']) && $_POST['assgnDeadlineTime'] > 0 && $_POST['assgnDeadlineTime'] < 25 && is_numeric($_POST['assgnDeadlineTime'])) ? $_POST['assgnDeadlineTime'] : NULL;
			$deadline = (!is_null($deadlineTime)) ? $deadlineDate.' '.$deadlineTime.':00:00' : $deadlineDate;
			$numberOfQuestions = (is_numeric($_POST['numberofquestions']) && $_POST['numberofquestions'] > 0 && $_POST['numberofquestions'] <= 10) ? $_POST['numberofquestions'] : NULL;
			$currentInfo = array('progId' => $progId, 'assgnTitle' => $assgnTitle, 'deadline' => $deadline, 'numberofquestions' => $numberOfQuestions);
			$_SESSION['creatingAssgn'] = $currentInfo;
		//DESIGN FORM HANDLING
		} elseif ((isset($_POST['addDesignSubmit']) && isset($_POST['addDesignForm'])) || (isset($_POST['editDesignSubmit']) && isset($_POST['editDesignForm']))) {
			$newObject = new Design;
			$errorExist = 0;
			$formErrors = array();
			$msg = '';
			$errorMessage = '<b><u>You have some Errors</u></b><br />';
			if (!is_numeric($_POST['designPricing'])) {
				$formErrors[] = 'Value of Pricing should be only Numbers. <br />';
				self::$formInput['designPricing'] = 1;
				$errorExist = 1;
			} 
			if (!is_numeric($_POST['designQuantity'])) {
				$formErrors[] = 'Value of Quantity should be only Numbers. <br />';
				self::$formInput['designQuantity'] = 1;
				$errorExist = 1;
			}
			if (isset($_POST['sizeVariants']) && is_array($_POST['sizeVariants'])) {
				$sizes = '';
				for ( $i = 0;  $i < sizeof($_POST['sizeVariants']); $i++ ) {
					if ( (int) $_POST['sizeVariants'][$i] == 1 ) {
						$sizes .= 'Extra Small, ';
					} elseif ( (int) $_POST['sizeVariants'][$i] === 2 ) {
						$sizes .= 'Small, ';
					} elseif ( (int) $_POST['sizeVariants'][$i] === 3 ) {
						$sizes .= 'Medium, ';
					} elseif ( (int) $_POST['sizeVariants'][$i] === 4 ) {
						$sizes .= 'Large, ';
					} elseif ( (int) $_POST['sizeVariants'][$i] === 5 ) {
						$sizes .= 'Extra Large, ';
					} else {
						$errorExist = 1;
						$formErrors[] = 'Wrong Size Variants Selection <br />';
						self::$formInput['sizeVariants'] = 1;
					}
				}
			}
			if (isset($_POST['addDesignForm']) && !isset($_FILES['file1'])) {
				$formErrors[] = 'Please select a Photo<br />';
				self::$formInput['file'] = 1;
				$errorExist = 1;
			}
			if ( $errorExist == 1 ) {
				for ( $i = 0; $i < sizeof($formErrors); $i++ ) {
					$errorMessage .= $formErrors[$i];
				}
				$msg = rtrim($errorMessage, '<br />');
				$type = 'error';
				self::alertDisplay($type, $msg);
				return;
			} else {
				$designTitle = self::sanitize($_POST['designTitle']);
				$designPricing = (int) $_POST['designPricing'];
				$designQuantity = (int) $_POST['designQuantity'];
				$designColours = self::sanitize($_POST['designColours']);
				$sizeVariants = rtrim(trim($sizes), ',');
				if (isset($_POST['addDesignForm'])) {
					$fileUpload = self::upload('Design');
					if (is_array($fileUpload)) {
						for ( $i = 0; $i < sizeof($fileUpload); $i++ ) {
							$msg .= $fileUpload[$i];
						}
						$type = 'error';
						self::alertDisplay($type, $msg);
						return;
					} else {
						$addDesign = $newObject->createDesign( $designTitle, $fileUpload, $designPricing, $designQuantity, $sizeVariants, $designColours, $pdo );
					}
				} elseif (isset($_POST['editDesignForm'])) {
					$editDesignId = self::unmaskURLParam($_POST['editDesignForm']);
					if (empty($_FILES['file1']['error']) || !empty($_FILES['file1']['tmp_name'])) {
					//Check that a file has been selected for uploaded
					//There would be no errors
						$fileUpload = self::upload('Design');
						if (is_array($fileUpload)) {
							for ( $i = 0; $i < sizeof($fileUpload); $i++ ) {
								$msg .= $fileUpload[$i];
							}
							$type = 'error';
							self::alertDisplay($type, $msg);
							return;
						} else {
							$updateDesign = $newObject->updateDesign( $editDesignId, $designTitle, $fileUpload, $designPricing, $designQuantity, $sizeVariants, $designColours, $pdo );
						}
					} else {
						$fileUpload = self::unmaskURLParam($_POST['editDesign']);
						$updateDesign = $newObject->updateDesign( $editDesignId, $designTitle, $fileUpload, $designPricing, $designQuantity, $sizeVariants, $designColours, $pdo );
					}
				}
			}
		} elseif (isset($_POST['deleteDesignForm']) && isset($_POST['deleteDesignConfirm'])) {
			$newObject = new Design;
			$designTitle = self::unmaskURLParam($_POST['deleteDesignConfirm']);
			$designId = self::unmaskURLParam($_POST['deleteDesignForm']);
			if ( $designId == $_SESSION['id'] && $_SESSION['userType'] == 'Admin' ) {
				$deletingItem = $newObject->deleteItem ( 'Design', $designId, $pdo );
				if ($deletingItem == 'success') {
					$type = "success";
					$msg = "The Design Has Been Successfully Deleted
					<br /> To view other Designs, click below";
					$link = "designs.php";
					$linkValue = 'Back to All Designs';
					self::alertDisplay ( $type, $msg, $link, $linkValue, 'vertical' );
					$_SESSION['id'] = '';
				} else {
					$type = "error";
					$msg = "You Attempted To Delete The Design <br />
					<span class='h3'><b>$designTitle</b></span><br />
					However, Your Action Was Unsuccessful.<br />
					To go back to the Design, click below";
					$link = "designdetails.php?design=".$_POST['deleteDesignForm'];
					$linkValue = 'Go Back to The Design';
					self::alertDisplay ( $type, $msg, $link, $linkValue );
				}
			} else {
				$type = "error";
				$msg = "You Attempted To Delete The Design <br />
				<span class='h3'><b>$designTitle</b></span><br />
				However, Your Action Was Unsuccessful.<br />
				To go back to the Design, click below";
				$link = "designdetails.php?design=".$_POST['deleteDesignForm'];
				$linkValue = 'Go Back to Design';
				self::alertDisplay ( $type, $msg, $link, $linkValue );
			}
		//CUSTOMER FORM HANDLING
		} elseif ((isset($_POST['createCustomerSubmit']) && isset($_POST['createCustomerForm'])) || (isset($_POST['editCustomerSubmit']) && isset($_POST['editCustomerForm']))) {
			$newObject = new Customer;
			$errorExist = 0;
			$formErrors = array();
			$msg = '';
			$errorMessage = '<b><u>You have some Errors</u></b><br />';
			if ( !is_numeric($_POST['gender']) && ((int)$_POST['gender'] !== 1 || (int)$_POST['gender'] !== 2) ) {
				$formErrors[] = 'Please Select A Gender.<br />';
				self::$formInput['gender'] = 1;
				$errorExist = 1;
			}
			if (isset($_POST['createCustomerForm']) && !isset($_FILES['file1'])) {
				if (!isset($_POST['addPhotoLater'])) {
					$formErrors[] = 'Please select a Photo<br />To Upload Later Instead, select the checkbox: <b>Select To Add Photo Later</b>.<br />';
					self::$formInput['file'] = 1;
					$errorExist = 1;
				}
			}
			if ( $errorExist == 1 ) {
				for ( $i = 0; $i < sizeof($formErrors); $i++ ) {
					$errorMessage .= $formErrors[$i];
				}
				$msg = rtrim($errorMessage, '<br />');
				$type = 'error';
				self::alertDisplay($type, $msg);
				return;
			} else {
				$surname = self::sanitize($_POST['surname']);
				$firstname = self::sanitize($_POST['firstname']);
				$othername = self::sanitize($_POST['othername']);
				$street = self::sanitize($_POST['street']);
				$city = self::sanitize($_POST['city']);
				$state = self::sanitize($_POST['state']);
				$country = self::sanitize($_POST['country']);
				$phone = self::sanitize($_POST['phone']);
				$phoneAlt = self::sanitize($_POST['phone_alt']);
				$email = self::sanitize($_POST['email']);
				$gender = ((int) $_POST['gender'] == 1) ? 'Male' : 'Female';
				if (isset($_POST['createCustomerForm'])) {
					$username = self::sanitize($_POST['username']);
					if ($_POST['password'] == $_POST['re-password']) {
						$password = self::sanitize($_POST['password']);
					} else {
						$type = 'error';
						$msg = 'The Passwords entered Do Not Match';
						self::alertDisplay( $type, $msg );
						return;
					}
					if (!isset($_FILES['file1']) && isset($_POST['addPhotoLater'])) {
						//Set Photo to default
						$fileUpload = 'users%2Fphotodefault.gif';
					} else {
						$fileUpload = self::upload('Customer');
					}
					if (is_array($fileUpload)) {
						for ( $i = 0; $i < sizeof($fileUpload); $i++ ) {
							$msg .= $fileUpload[$i];
						}
						$type = 'error';
						self::alertDisplay($type, $msg);
						return;
					} else {
						$createLogin = $newObject->createUser( $username, $password, 'Customer', $pdo );
						if ($createLogin == 'success') {
							$createCustomer = $newObject->addUserDetails ( 'Customer',  $surname, $firstname, $othername, $gender, $street, $city, $state, $country, $phone, $phoneAlt, $email, $fileUpload, $pdo );
						} else {
							$type = 'error';
							$msg = 'There Was An Error Creating The Customer\'s Account';
							self::alertDisplay($type, $msg);	
						}
					}
				} elseif (isset($_POST['editCustomerForm'])) {
					$editCustomerId = self::unmaskURLParam($_POST['editCustomerForm']);
					if (empty($_FILES['file1']['error']) || !empty($_FILES['file1']['tmp_name'])) {
					//Checking that a file has been selected for uploaded
						unlink(self::unmaskURLParam($_POST['editCustomer']));
						$fileUpload = self::upload('Customer');
						if (is_array($fileUpload)) {
							for ( $i = 0; $i < sizeof($fileUpload); $i++ ) {
								$msg .= $fileUpload[$i];
							}
							$type = 'error';
							self::alertDisplay($type, $msg);
							return;
						} else {
							$updateCustomer = $newObject->updateUserDetails( 'Customer', $surname, $firstname, $othername, $gender, $street, $city, $state, $country, $phone, $phoneAlt, $email, $fileUpload, $editCustomerId, $pdo );
							if ( $updateCustomer == 'success' ) {
								self::redirect("customerdetails.php?customer=".self::maskURLParam($userId)."&update=success");
							} else {
								self::redirect("customerdetails.php?customer=".self::maskURLParam($userId)."&update=failed");
							}							
						}
					} else {
						$fileUpload = self::unmaskURLParam($_POST['editCustomer']);
						$updateCustomer = $newObject->updateUserDetails( 'Customer', $surname, $firstname, $othername, $gender, $street, $city, $state, $country, $phone, $phoneAlt, $email, $fileUpload, $editCustomerId, $pdo );
						if ( $updateCustomer == 'success' ) {
							self::redirect("customerdetails.php?customer=".self::maskURLParam($userId)."&update=success");
						} else {
							self::redirect("customerdetails.php?customer=".self::maskURLParam($userId)."&update=failed");
						}
					}
				}
			}
		} elseif (isset($_POST['deleteCustomerForm']) && isset($_POST['deleteCustomerConfirm'])){
			$newObject = new Customer;
			$customerName = self::unmaskURLParam($_POST['deleteCustomerConfirm']);
			$customerId = self::unmaskURLParam($_POST['deleteCustomerForm']);
			if ( $customerId == $_SESSION['id'] && $_SESSION['userType'] == 'Admin' ) {
				$deletingItem = $newObject->deleteUser ( 'Customer', $customerId, $pdo );
				if ( $deletingItem == 'success' ) {
					$type = "success";
					$msg = "The Customer Has Been Successfully Deleted
					<br /> To view other Customers, click below";
					$link = "customers.php";
					$linkValue = 'Back to All Customers';
					self::alertDisplay ( $type, $msg, $link, $linkValue, 'vertical' );
					$_SESSION['id'] = '';
				} else {
					$type = "error";
					$msg = "You Attempted To Delete The Customer <br />
					<span class='h3'><b>$customerName</b></span><br />
					However, Your Action Was Unsuccessful.<br />
					To go back to the Customer Details, click below";
					$link = "customerdetails.php?customer=".$_POST['deleteCustomerForm'];
					$linkValue = 'Go Back to The Customer Details';
					self::alertDisplay ( $type, $msg, $link, $linkValue );
				}
			} else {
				$type = "error";
				$msg = "You Attempted To Delete The Customer <br />
				<span class='h3'><b>$customerName</b></span><br />
				However, Your Action Was Unsuccessful.<br />
				To go back to the Customer Details, click below";
				$link = "customerdetails.php?customer=".$_POST['deleteCustomerForm'];
				$linkValue = 'Go Back to The Customer Details';
				self::alertDisplay ( $type, $msg, $link, $linkValue );
			}
		//TRAINING FORM HANDLING
		} elseif ((isset($_POST['addTrainingSubmit']) && isset($_POST['addTrainingForm'])) || (isset($_POST['editTrainingSubmit']) && isset($_POST['editTrainingForm']))) {
			$newObject = new Trainings;
			$rangeNumber = range( 1, 12 );
			$rangePeriod = range( 1, 4 );
			$errorExist = 0;
			$formErrors = array();
			$msg = '';
			$errorMessage = '<b><u>You have some Errors</u></b><br />';
			if (!is_numeric($_POST['duration_number']) || !is_numeric($_POST['duration_period']) || !in_array($_POST['duration_number'], $rangeNumber) || !in_array($_POST['duration_period'], $rangePeriod))  {
				$formErrors[] = 'Your Selection of Duration is Invalid. <br />';
				self::$formInput['duration'] = 1;
				$errorExist = 1;
			}
			if (!is_numeric($_POST['fees'])) {
				$formErrors[] = 'Value of Fees should be only numbers. <br />';
				self::$formInput['fees'] = 1;
				$errorExist = 1;
			}
			if ( $errorExist == 1 ) {
				for ( $i = 0; $i < sizeof($formErrors); $i++ ) {
					$errorMessage .= $formErrors[$i];
				}
				$msg = rtrim($errorMessage, '<br />');
				$type = 'error';
				self::alertDisplay($type, $msg);
				return;
			} else {
				$programme = self::sanitize($_POST['programme']);
				$details = self::sanitize($_POST['details']);
				$durationNumber = (int) $_POST['duration_number'];
				$durationPeriod = (int) $_POST['duration_period'];
				$fees = (int) $_POST['fees'];
				if ( $durationPeriod == 1 ) {
					$duration = ( $durationNumber == 1 ) ? $durationNumber.' Day' : $durationNumber.' Days';
				} elseif ( $durationPeriod == 2 ) {
					$duration = ( $durationNumber == 1 ) ? $durationNumber.' Week' : $durationNumber.' Weeks';  
				} elseif ( $durationPeriod == 3 ) {
					$duration = ( $durationNumber == 1 ) ? $durationNumber.' Month' : $durationNumber.' Months';  
				} elseif ( $durationPeriod == 4 ) {
					$duration = ( $durationNumber == 1 ) ? $durationNumber.' Year' : $durationNumber.' Years';
				}
				if (isset($_POST['addTrainingForm'])) {
					$newObject->createTraining ( $programme, $details, $duration, $fees, $pdo );
				} elseif (isset($_POST['editTrainingForm'])) {
					$editTrainingId = self::unmaskURLParam($_POST['editTrainingForm']);
					$newObject->updateTraining ( $editTrainingId, $programme, $details, $duration, $fees, $pdo );
				}
			}
		} elseif (isset($_POST['deleteTrainingForm']) && isset($_POST['deleteTrainingConfirm'])) {
			$newObject = new Trainings;
			$programme = self::unmaskURLParam($_POST['deleteTrainingConfirm']);
			$progId = self::unmaskURLParam($_POST['deleteTrainingForm']);
			if ( $_SESSION['userType'] == 'Admin' ) {
				$deleteTimetable = $newObject->deleteItem ( 'Timetables', $progId, $pdo );
				if ( $deleteTimetable == 'success' ) {
					$deletingItem = $newObject->deleteItem ( 'Trainings', $progId, $pdo );
					if ($deletingItem == 'success') {
						$type = "success";
						$msg = "The Programme Has Been Successfully Deleted";
						self::alertDisplay ( $type, $msg );
					} else {
						$type = "error";
						$msg = "You Attempted To Delete The Programme <br />
						<span class='h3'><b>$programme</b></span><br />
						However, Your Action Was Unsuccessful.<br />
						To go back to Programmes, click below";
						$link = "programmes.php";
						$linkValue = 'Back to Programmes';
						self::alertDisplay ( $type, $msg, $link, $linkValue );
					}
				} else {
					$type = "error";
					$msg = "You Attempted To Delete The Programme <br />
					<span class='h3'><b>$programme</b></span><br />
					However, Your Action Was Unsuccessful.<br />
					To go back to Programmes, click below";
					$link = "programmes.php";
					$linkValue = 'Back to Programmes';
					self::alertDisplay ( $type, $msg, $link, $linkValue );
				}
			} else {
				$type = "error";
				$msg = "You Attempted To Delete The Programme <br />
				<span class='h3'><b>$programme</b></span><br />
				However, Your Action Was Unsuccessful.<br />
				To go back to Programmes, click below";
				$link = "programmes.php";
				$linkValue = 'Back to Programmes';
				self::alertDisplay ( $type, $msg, $link, $linkValue );
			}
		//STUDENT FORM HANDLING
		} elseif ((isset($_POST['createStudentSubmit']) && isset($_POST['createStudentForm'])) || (isset($_POST['editStudentSubmit']) && isset($_POST['editStudentForm']))) {
			$newObject = new Student;
			$errorExist = 0;
			$formErrors = array();
			$msg = '';
			$errorMessage = '<b><u>You have some Errors</u></b><br />';
			if ( !is_numeric($_POST['gender']) && ((int)$_POST['gender'] !== 1 || (int)$_POST['gender'] !== 2) ) {
				$formErrors[] = 'Please Select A Gender.<br />';
				self::$formInput['gender'] = 1;
				$errorExist = 1;
			}
			if (isset($_POST['createStudentForm']) && !isset($_FILES['file1'])) {
				if (!isset($_POST['addPhotoLater'])) {
					$formErrors[] = 'Please select a Photo<br />To Upload Later Instead, select the checkbox: <b>Select To Add Photo Later</b>.<br />';
					self::$formInput['file'] = 1;
					$errorExist = 1;
				}
			}
			if ( $errorExist == 1 ) {
				for ( $i = 0; $i < sizeof($formErrors); $i++ ) {
					$errorMessage .= $formErrors[$i];
				}
				$msg = rtrim($errorMessage, '<br />');
				$type = 'error';
				self::alertDisplay($type, $msg);
				return;
			} else {
				$surname = self::sanitize($_POST['surname']);
				$firstname = self::sanitize($_POST['firstname']);
				$othername = self::sanitize($_POST['othername']);
				$street = self::sanitize($_POST['street']);
				$city = self::sanitize($_POST['city']);
				$state = self::sanitize($_POST['state']);
				$country = self::sanitize($_POST['country']);
				$phone = self::sanitize($_POST['phone']);
				$phoneAlt = self::sanitize($_POST['phone_alt']);
				$email = self::sanitize($_POST['email']);
				$gender = ((int) $_POST['gender'] == 1) ? 'Male' : 'Female';
				echo $gender;
				if (isset($_POST['createStudentForm'])) {
					$username = self::sanitize($_POST['username']);
					if ($_POST['password'] == $_POST['re-password']) {
						$password = self::sanitize($_POST['password']);
					} else {
						$type = 'error';
						$msg = 'The Passwords Entered Do Not Match';
						self::alertDisplay( $type, $msg );
						return;
					}
					if (!isset($_FILES['file1']) && isset($_POST['addPhotoLater'])) {
						//Set Photo to default
						$fileUpload = 'users%2Fphotodefault.gif';
					} else {
						$fileUpload = self::upload('Student');
					}
					if (is_array($fileUpload)) {
						for ( $i = 0; $i < sizeof($fileUpload); $i++ ) {
							$msg .= $fileUpload[$i];
						}
						$type = 'error';
						self::alertDisplay($type, $msg);
						return;
					} else {
						$createLogin = $newObject->createUser( $username, $password, 'Student', $pdo );
						if ($createLogin == 'success') {
							$createStudent = $newObject->addUserDetails ( 'Student',  $surname, $firstname, $othername, $gender, $street, $city, $state, $country, $phone, $phoneAlt, $email, $fileUpload, $pdo );
							if ( $updateStudent == 'success' ) {
								self::redirect("studentdetails.php?student=".self::maskURLParam($userId)."&update=success");
							} else {
								self::redirect("studentdetails.php?student=".self::maskURLParam($userId)."&update=failed");
							}
						} else {
							$type = 'error';
							$msg = 'There Was An Error Creating The Student\'s Account';
							self::alertDisplay($type, $msg);	
						}
					}
				} elseif (isset($_POST['editStudentForm'])) {
					$editStudentId = self::unmaskURLParam($_POST['editStudentForm']);
					if (empty($_FILES['file1']['error']) || !empty($_FILES['file1']['tmp_name'])) {
					//Checking that a file has been selected for uploaded
						unlink(self::unmaskURLParam($_POST['editStudent']));
						$fileUpload = self::upload( 'Student' );
						if (is_array($fileUpload)) {
							for ( $i = 0; $i < sizeof($fileUpload); $i++ ) {
								$msg .= $fileUpload[$i];
							}
							$type = 'error';
							self::alertDisplay($type, $msg);
							return;
						} else {
							$updateStudent = $newObject->updateUserDetails( 'Student', $surname, $firstname, $othername, $gender, $street, $city, $state, $country, $phone, $phoneAlt, $email, $fileUpload, $editStudentId, $pdo );
							if ( $updateStudent == 'success' ) {
								self::redirect("studentdetails.php?student=".self::maskURLParam($userId)."&update=success");
							} else {
								self::redirect("studentdetails.php?student=".self::maskURLParam($userId)."&update=failed");
							}
						}
					} else {
						$fileUpload = self::unmaskURLParam($_POST['editStudent']);
						$updateCustomer = $newObject->updateUserDetails( 'Student', $surname, $firstname, $othername, $gender, $street, $city, $state, $country, $phone, $phoneAlt, $email, $fileUpload, $editCustomerId, $pdo );
					}
				}
			}
		} elseif (isset($_POST['deleteStudentForm']) && isset($_POST['deleteStudentConfirm'])){
			$newObject = new Student;
			$studentName = self::unmaskURLParam($_POST['deleteStudentConfirm']);
			$studentId = self::unmaskURLParam($_POST['deleteStudentForm']);
			if ( $studentId == $_SESSION['id'] && $_SESSION['userType'] == 'Admin' ) {
				$deletingItem = $newObject->deleteUser ( 'Student', $studentId, $pdo );
				if ( $deletingItem == 'success' ) {
					$type = "success";
					$msg = "The Student $studentName Has Been Successfully Deleted
					<br /> To view other Students, click below";
					$link = "students.php";
					$linkValue = 'Back to All Students';
					self::alertDisplay ( $type, $msg, $link, $linkValue, 'vertical' );
					$_SESSION['id'] = '';
				} else {
					$type = "error";
					$msg = "You Attempted To Delete The Student <br />
					<span class='h3'><b>$studentName</b></span><br />
					However, Your Action Was Unsuccessful.<br />
					To go back to the Student Details, click below";
					$link = "studentdetails.php?student=".$_POST['deleteStudentForm'];
					$linkValue = 'Go Back to The Student Details';
					self::alertDisplay ( $type, $msg, $link, $linkValue );
				}
			} else {
				$type = "error";
				$msg = "You Attempted To Delete The Student <br />
				<span class='h3'><b>$studentName</b></span><br />
				However, Your Action Was Unsuccessful.<br />
				To go back to the Student Details, click below";
				$link = "studentdetails.php?student=".$_POST['deleteStudentForm'];
				$linkValue = 'Go Back to The Student Details';
				self::alertDisplay ( $type, $msg, $link, $linkValue );
			}
		//TIMETABLE FORM HANDLING
		} elseif ((isset($_POST['addTimetableSubmit']) && isset($_POST['addTimetableForm'])) || (isset($_POST['editTimetableSubmit']) && isset($_POST['editTimetableForm']))) {
			$newObject = new Timetables;
			$errorExist = 0;
			$formErrors = array();
			$msg = '';
			$errorMessage = '<b><u>You have some Errors</u></b><br />';
			if (!is_numeric($_POST['day']) || $_POST['day'] > 5 || $_POST['day'] < 1) {
				$formErrors[] = 'You Made A Wrong Selection of Day. <br />';
				self::$formInput['day'] = 1;
				$errorExist = 1;
			} 
			if (!is_numeric($_POST['startTime']) || $_POST['startTime'] > 16 || $_POST['startTime'] < 8 ) {
				$formErrors[] = 'You Made A Wrong Selection of Start Time. <br />';
				self::$formInput['startTime'] = 1;
				$errorExist = 1;
			} 
			if (!is_numeric($_POST['duration']) || $_POST['duration'] > 4 || $_POST['duration'] < 1 ) {
				$formErrors[] = 'You Made A Wrong Selection of Duration. <br />';
				self::$formInput['duration'] = 1;
				$errorExist = 1;
			}
			if ( $errorExist == 1 ) {
				for ( $i = 0; $i < sizeof($formErrors); $i++ ) {
					$errorMessage .= $formErrors[$i];
				}
				$msg = rtrim($errorMessage, '<br />');
				$type = 'error';
				self::alertDisplay($type, $msg);
				return;
			} else {
				$duration = (int) $_POST['duration'];
				$setStartTime = (int) ($_POST['startTime']);
				$startTime = "$setStartTime:00:00";
				$setEndTime = $setStartTime + $duration;
				$endTime = "$setEndTime:00:00";
				$setDay = (int) ($_POST['day']);
				if ( $setDay == 1 ) {
					$day = 'Monday';
				} elseif ( $setDay == 2 ) {
					$day = 'Tuesday';
				} elseif ( $setDay == 3 ) {
					$day = 'Wednesday';
				} elseif ( $setDay == 4 ) {
					$day = 'Thursday';
				} elseif ( $setDay == 5 ) {
					$day = 'Friday';
				}
				$programme = self::sanitize($_POST['programme']);
				$venue = self::sanitize($_POST['venue']);
				if (isset($_POST['addTimetableForm'])) {
					$progId = self::unmaskURLParam($_POST['addTimetableForm']);
					$confirmProgId = $newObject->getProgramme( $progId, $pdo );
					foreach ( $confirmProgId as $key => $value ) {
						$confirmProgramme = $value['programme'];
						$confirmProgId = $value['prog_id'];
					}
					if ( $confirmProgramme == $programme && $confirmProgId == $progId ) {
						$confirmAvailable = $newObject->confirmAvailableSpace ( $progId, $startTime, $endTime, $day, $pdo );
						if ($confirmAvailable) {
							$type = 'error';
							if ( gettype($confirmAvailable) == 'string' ) {
								$msg = "Another Programme <b>$confirmAvailable</b> Has The Same Commencement Schedule";
							} else {
								$setProgId = $confirmAvailable[0]['prog_id'];
								$started = $confirmAvailable[0]['start_time'];
								$msg = "Another Programme <b>$setProgId</b> Commences At <b>$started</b> Which Clashes With The Chosen Schedule";
							}
							self::alertDisplay($type, $msg);
						} else {
								$newObject->createTimetable ( $day, $startTime, $duration, $progId, $venue, $pdo );
						}
					} else {
						$type = 'error';
						$msg = 'The Programme Could Not Be Added To The Timetable';
						self::alertDisplay($type, $msg);
					}
				} elseif (isset($_POST['editTimetableForm'])) {
					$progId = self::unmaskURLParam($_POST['editTimetableProgId']);
					$timetableId = self::unmaskURLParam($_POST['editTimetableForm']);
					$confirmProgId = $newObject->getProgramme($timetableId, $pdo );
					$confirmAvailable = $newObject->confirmAvailableSpace ( $progId, $startTime, $endTime, $day, $pdo );
					if ($confirmAvailable) {
						$type = 'error';
						if ( gettype($confirmAvailable) == 'string' ) { 
							$msg = "Another Programme <b>$confirmAvailable</b> Has The Same Commencement Schedule";
						} else {
							$setProgId = $confirmAvailable[0]['prog_id'];
							$started = $confirmAvailable[0]['start_time'];
							$msg = "Another Programme <b>$setProgId</b> Commences At <b>$started</b> Which Clashes With The Chosen  Schedule";
						}
						self::alertDisplay($type, $msg);
					} else {
						$newObject->updateTimetable ( $timetableId, $day, $startTime, $duration, $venue, $pdo );
					}
				}
			}
		} elseif (isset($_POST['deleteTimetableForm']) && isset($_POST['deleteTimetableConfirm'])) {
			$newObject = new Timetables;
			$progTitle = $_POST['deleteTimetableConfirm'];
			$timetableId = self::unmaskURLParam($_POST['deleteTimetableForm']);
			if ( $_SESSION['userType'] == 'Admin' ) {
				$deleteTimetable = $newObject->deleteProgramFromTimetable ( $timetableId, $pdo );
				if ( $deleteTimetable == 'success' ) {
					self::redirect('timetable.php?delete=success&programme='.$progTitle);
				} else {
					self::redirect('timetable.php?delete=failed');
				}
			} else {
				$type = "error";
				$msg = "You Attempted To Delete The Programme <br />
				<span class='h3'><b>$programme</b></span><br />
				However, Your Action Was Unsuccessful.<br />
				To go back to Programmes, click below";
				$link = "programmes.php";
				$linkValue = 'Back to Programmes';
				self::alertDisplay ( $type, $msg, $link, $linkValue );
			}
		//USER PREFERENCES SUBMIT
		} elseif (isset($_POST['userSettingsSubmit']) && isset($_POST['saveUserSettings'])) {
			$newObject = new Users;
			if ( !empty($_POST['deleteSlidePhotoSubmit1']) || !empty($_POST['deleteSlidePhotoSubmit2']) ) {
				//To delete a SlideShow Photo
				if ( !empty($_POST['deleteSlidePhotoSubmit1']) || empty($_POST['deleteSlidePhotoSubmit2'])) {
					//Photo 1 was selected
					$imgId = 1;
					$deleteSlide =  $newObject->deleteSlidePhoto ( $imgId, $pdo );
					if ( $deleteSlide == 'success' ) {
						self::redirect('settings.php?update=sphoto1');
					} else {
						self::redirect('settings.php?update=ephoto1');
					}
				} elseif ( empty($_POST['deleteSlidePhotoSubmit1']) || !empty($_POST['deleteSlidePhotoSubmit2'])) {
					//Photo 2 was selected;
					$imgId = 2;
					$deleteSlide =  $newObject->deleteSlidePhoto ( $imgId, $pdo );
					if ( $deleteSlide == 'success' ) {
						self::redirect('settings.php?update=sphoto2');
					} else {
						self::redirect('settings.php?update=ephoto2');
					}					
				} else {
					//Fatal Error! Both Cannot Be Set To be Deleted At Once
					
				}
			} elseif ( empty($_POST['deleteSlidePhotoSubmit1']) || empty($_POST['deleteSlidePhotoSubmit2']) ) {
			
			}
			if ( isset($_FILES['updatePhoto']) || isset($_FILES['newPhoto']) ) {
				if ( isset($_FILES['newPhoto']) ) {
					$imgTitle = (isset($_POST['newSlidePhoto'])) ? self::sanitize($_POST['newSlidePhoto']) : 'New Photo SlideShow';
					$_FILES['file1'] = $_FILES['newPhoto'];
					unset($_FILES['newPhoto']);
					$fileUpload = self::upload( 'Slide' );
					if (is_array($fileUpload)) {
						for ( $i = 0; $i < sizeof($fileUpload); $i++ ) {
							$msg .= $fileUpload[$i];
						}
						$type = 'error';
						self::alertDisplay($type, $msg);
						return;
					} else {
						$imgLink = "mcsue%2F$fileUpload";
						$addSlidePhoto = $newObject->addSlidePhotos ( $imgTitle, $imgLink, $pdo );
					}
				}
				$updatePhotoOrder =  ( ($_POST['selectOrder1'] == 1 && !isset($_POST['selectOrder2'])) || (!isset($_POST['selectOrder1']) && $_POST['selectOrder2'] == 2) || ($_POST['selectOrder1'] == 1 && $_POST['selectOrder2'] == 2) ) ? 'N/A' : $newObject->updatePhotoOrder( $pdo );
				if ( isset($_FILES['updatePhoto']) ) {
					$getImgURL = self::multipleUpload ( 'Slide' );
					if ( !empty($getImgURL[0]) && !empty($getImgURL[1])  ) {
						//Both Files Were Selected For Upload
						$imgTitle1 = (isset($_POST['updatePhotoTitle1'])) ? self::sanitize($_POST['updatePhotoTitle1']) : 'New Photo';
						$imgTitle2 = (isset($_POST['updatePhotoTitle2'])) ? self::sanitize($_POST['updatePhotoTitle2']) : 'New Photo';
						if ( $updatePhotoOrder == 'success' ) {
							//Means The Order Has Been InterChanged 1->2; 2->1
							$setImgLink1 = "mcsue%2F".$getImgURL[0];
							$updatePhoto1 = $newObject->updateSlidePhotos( 2, $imgTitle1, $setImgLink, $pdo );
							if ( $updatePhoto1 == 'success' ) {
								$setImgLink = "mcsue%2F".$getImgURL[1];
								$updatePhoto2 = $newObject->updateSlidePhotos( 1, $imgTitle2, $setImgLink, $pdo );
								if ( $updatePhoto2 == 'error' ) {
									//Redirect to Page Again
									self::redirect('settings.php?slide=error#appRow');
								}
							} else {
								self::redirect('settings.php?slide=error#appRow');
							}
						} else {
							//The Order Is Unchanged
							$setImgLink1 = "mcsue%2F".$getImgURL[0];
							$updatePhoto1 = $newObject->updateSlidePhotos( 1, $imgTitle1, $setImgLink1, $pdo );
							if ( $updatePhoto1 == 'success' ) {
								$setImgLink = "mcsue%2F".$getImgURL[1];
								$updatePhoto2 = $newObject->updateSlidePhotos( 2, $imgTitle2, $setImgLink, $pdo );
								if ( $updatePhoto2 == 'error' ) {
									//Redirect to Page Again
									self::redirect('settings.php?slide=error2#appRow');
								}
							} else {
								self::redirect('settings.php?slide=error#appRow');
							}
						}
					} else {
						if ( !empty($getImgURL[0]) && empty($getImgURL[1]) ) {
							//Only first file is selected
							$imgTitle1 = (isset($_POST['updatePhotoTitle1'])) ? self::sanitize($_POST['updatePhotoTitle1']) : 'New Photo';
							if ( $updatePhotoOrder == 'success' ) {
								//Means The Order Has Been InterChanged 1->2; 2->1
								$setImgLink = "mcsue%2F".$getImgURL[0];
								$updatePhoto1 = $newObject->updateSlidePhotos( 2, $imgTitle1, $setImgLink, $pdo );
								if ( $updatePhoto1 == 'error' ) {
									self::redirect('settings.php?slide=error1#appRow');
								}
							} else {
							//The Order Is Unchanged
								$setImgLink = "mcsue%2F".$getImgURL[0];
								$updatePhoto1 = $newObject->updateSlidePhotos( 1, $imgTitle1, $setImgLink, $pdo );
								if ( $updatePhoto1 == 'error' ) {
									self::redirect('settings.php?slide=error1#appRow');
								}
							}
						} elseif ( empty($getImgURL[0]) && !empty($getImgURL[1]) ) {
							//Only Second file is selected
							$imgTitle2 = (isset($_POST['updatePhotoTitle2'])) ? self::sanitize($_POST['updatePhotoTitle2']) : 'New Photo';
							if ( $updatePhotoOrder == 'success' ) {
								//Means The Order Has Been InterChanged 1->2; 2->1
								$setImgLink = "mcsue%2F".$getImgURL[1];
								$updatePhoto2 = $newObject->updateSlidePhotos( 1, $imgTitle2, $setImgLink, $pdo );
								if ( $updatePhoto2 == 'error' ) {
									self::redirect('settings.php?slide=error2#appRow');
								}
							} else {
							//The Order Is Unchanged
								$setImgLink = "mcsue%2F".$getImgURL[1];
								$updatePhoto2 = $newObject->updateSlidePhotos( 2, $imgTitle2, $setImgLink, $pdo );
								if ( $updatePhoto2 == 'error' ) {
									self::redirect('settings.php?slide=error2#appRow');
								}
							}
						}
					}
				}
			}
			$viewOrders = (isset($_POST['view_orders'])) ? 'all' : 'current';
			$reminders = (isset($_POST['reminders'])) ? 'user' : 'auto';
			$designsAccess = (isset($_POST['designs_access'])) ? 'admin' : 'all';
			$paymentSchedule = (isset($_POST['payment_schedule'])) ? 'instalment' : 'full';
			$paymentInstalment = (is_numeric($_POST['payment_instalment'])) ? $_POST['payment_instalment'] : self::$pref[0]['payment_instalment'];
			$remainLoggedIn = (is_numeric($_POST['remain_logged_in'])) ? $_POST['remain_logged_in'] : self::$pref[0]['remain_logged_in'];
			$tableLimit = (is_numeric($_POST['table_limit'])) ? $_POST['table_limit'] : self::$pref[0]['table_limit'];
			$updateSettings = $newObject->updateUserSettings ( $viewOrders, $reminders, $designsAccess, $paymentSchedule, $paymentInstalment, $remainLoggedIn, $tableLimit, $_SESSION['userId'], $pdo );
			if ( $updateSettings == 'success' ) {
				self::redirect("settings.php?update=success");
			} else {
				self::redirect("settings.php?update=");
			}
		//USER PREFERENCES SUBMIT
		} elseif (isset($_POST['userDataSubmit']) && isset($_POST['saveUserData'])) {
			$newObject = new Users;
			if ( isset($_FILES['updateUserPhoto']) && !empty($_FILES['updateUserPhoto']['name'])) {
				$_FILES['file1'] = $_FILES['updateUserPhoto'];
				unset($_FILES['updateUserPhoto']);
				$updatePhoto = self::upload( 'User' );
				if (is_array($updatePhoto)) {
					for ( $i = 0; $i < sizeof($updatePhoto); $i++ ) {
						$msg .= $updatePhoto[$i];
					}
					$formatMsg = self::maskURLParam($msg);
					self::redirect("user.php?photo=$formatMsg#top");
					return;
				}
			} else {
				$updatePhoto = NULL;
			}
			if ( !empty($_POST['currentPassword']) && !empty($_POST['updatePassword']) && !empty($_POST['updateRePassword']) ) {
				$currentPassword = self::sanitize($_POST['currentPassword']);
				$updatePassword = self::sanitize($_POST['updatePassword']);
				$updateRePassword = self::sanitize($_POST['updateRePassword']);
				$validateCurrentPassword = $newObject->verifyCurrentPassword ( $currentPassword, $_SESSION['userId'], $pdo );
				if ( $validateCurrentPassword == 'success' ) {
					//Confirm if New Password is Same as Current Password
					if ($currentPassword == $updatePassword ) {
						self::redirect('user.php?password=same#top');
					}
					//Confirm that both New Passwords are the same
					if ( $updatePassword == $updateRePassword ) {
						//Confirm that this Password has not been used before
						$confirmPrevious = self::confirmPreviousPass ( $pdo, $updatePassword );
						if ($confirmPrevious) {
							self::redirect('user.php?password=used#top');
						}
					} else {
						self::redirect('user.php?password=mismatch#top');
					}
				} else {
					self::redirect('user.php?password=error#top');
				}
			} else {
				$updatePassword = NULL;
			}
			$updatePhone = self::sanitize($_POST['updatePhone']);
			$updatePhoneAlt = self::sanitize($_POST['updatePhoneAlt']);
			$updateEmail = self::sanitize($_POST['updateEmail']);
			$securityQuestion = self::sanitize($_POST['security_question']);
			$securityAnswer = self::sanitize($_POST['security_answer']);
			$updateUserData = $newObject->updateUserData ( $_SESSION['userType'], $updatePassword, $updatePhone, $updatePhoneAlt, $updateEmail, $securityQuestion, $securityAnswer, $updatePhoto, $_SESSION['userId'], $pdo );
			if ( $updateUserData == 'success' ) {
				self::redirect('user.php?user=success#top');
			} else {
				self::redirect('user.php?user=failed#top');
			}
		//USER PREFERENCES SUBMIT
		} elseif (isset($_POST['restoreDefaultSubmit']) && isset($_POST['restoreDefaultForm'])) {
			$newObject = new Users;
			$defaultSettings = array();
			foreach (self::$defaultPref[0] as $key => $value) {
				$defaultSettings[":$key"] = $value;
			}
			$defaultSettings[':user_id'] = $_SESSION['userId'];
			$newObject->restoreDefaultSettings ( $defaultSettings, $pdo );
		//USER PREFERENCES SUBMIT
		} elseif (isset($_POST['deleteSlidePhoto1']) || isset($_POST['deleteSlidePhoto2'])) {
			var_dump($_POST);
			echo 'Delete Photo Has Been Clicked';
		//STUDENT'S RESULTS SUBMIT
		} elseif ((isset($_POST['addResultSubmit']) && isset($_POST['addResultForm'])) || (isset($_POST['editResultSubmit']) && isset($_POST['editResultForm']))) {
			$newObject = new StudentResult;
			$totalStudents = (int) $_POST['totalStudents'];
			$courseCode = self::sanitize(self::unmaskURLParam($_POST['courseCode']));
			for ( $i = 1; $i <= $totalStudents; $i++ ) {
				$getStudentId = self::sanitize($_POST['studentGrade' . $i]);
				$studentId = self::sanitize(self::unmaskURLParam($getStudentId));
				if ( !empty($_POST[$getStudentId]) && is_numeric($_POST[$getStudentId]) ) {
					$studentScore = (int) $_POST[$getStudentId];
					if (isset($_POST['addResultForm'])) {
						$setResult = $newObject->createResult ( $studentId, $courseCode, $studentScore, $pdo );
						if ( $setResult == 'success' ) {
							continue;
						}
					} elseif (isset($_POST['editResultForm'])) {
						$setResult = $newObject->updateResult ( $studentId, $courseCode, $studentScore, $pdo );
						if ( $setResult == 'success' ) {
							continue;
						}
					}
				} else {
					continue;
				}
			}
			if ($setResult == 'success') {
				$msg = '<b>The Result Was Successfully Updated</b>';
				if ($totalStudents > 1) {
					$msg = '<b>The Results Were Successfully Updated</b>';
				}
				$type = 'success';
				self::alertDisplay($type, $msg);
			} else {
				$msg = '<b>There Was An Error Updating the Result</b>';
				if ($totalStudents > 1) {
					$msg = '<b>The Results Were Successfully Updated</b>';
				}
				$type = 'error';
				self::alertDisplay($type, $msg);
			}
		//RESET PASSWORD SUBMIT
		} elseif ((isset($_POST['confirmEmailSubmit']) && isset($_POST['confirmEmailForm']))) {
			if (!is_numeric($_POST['accountType']) || ($_POST['accountType'] < 1 || $_POST['accountType'] > 3)) {
				$type = 'error';
				$msg = '<b>You Have Made Wrong Selection of Account Type</b>';
				if ($_POST['accountType'] == 0) {
					$msg = '<b>Please Select An Account Type</b>';
				}
				self::alertDisplay($type, $msg);
			} else {
				$_SESSION['setUserType'] = $_POST['accountType'];
				$email = self::sanitize($_POST['forgot-email']);
				$getDetails = self::confirmEmail($pdo, $email, $_POST['accountType']);
				if ($getDetails) {
					self::$forgotPassword = $getDetails['email'];
					$_SESSION['securityQuestion'] = $getDetails['security_question'];
					$_SESSION['getUserId'] = $getDetails['userId'];
				} else {
					$type = 'error';
					$msg = '<b>Please Confirm That The Email Address Entered Is Spelt Correctly</b>';
					self::alertDisplay($type, $msg);
				}
			}
		} elseif ((isset($_POST['confirmResetSubmit']) && isset($_POST['confirmResetForm']))) {
			self::$forgotPassword = $_POST['forgot-email'];
			$username = self::sanitize($_POST['username']);
			$phone = self::sanitize($_POST['phone']);
			$birthday = self::sanitize($_POST['birthday']);
			$question = self::sanitize($_POST['question']);
			$confirmReset = self::confirmResetDetails($pdo, $username, $phone, $birthday, $question);
			if ($confirmReset) {
				$_SESSION['confirmedResetId'] = $confirmReset['returnedId'];
			} else {
				$type = 'error';
				$msg = '<b>The Details You Entered Are Incorrect. Please Check And Try Again</b>';
				self::alertDisplay($type, $msg);			
			}
		} elseif ((isset($_POST['finalPassResetSubmit']) && isset($_POST['finalPassResetForm']))) {
			if (!isset($_SESSION['confirmedResetId'])) {
				self::redirect('forgotpassword.php');
			}
			$newObject = new Users;
			$newPassword = self::sanitize($_POST['new_password']);
			$confirmPassword = self::sanitize($_POST['confirm_new_password']);
			if (strlen($newPassword) < 8) {
				$type = 'error';
				$msg = '<b>Your Password Must Be At Least 8 Characters</b>';
				self::alertDisplay($type, $msg);
				return;	
			}
			if ($newPassword !== $confirmPassword) {
				$type = 'error';
				$msg = '<b>Your Passwords Do Not Match. Please Try Again</b>';
				self::alertDisplay($type, $msg);
				return;
			} else {
				$confirmPrevious = self::confirmPreviousPass ( $pdo, $newPassword );
				if ($confirmPrevious) {
					$type = 'error';
					$msg = '<b>Please Select A Password You Have Not Used Before</b>';
					self::alertDisplay($type, $msg);				
				} else {
					$updatePassword = $newObject->updatePassword($newPassword, $pdo);
					if ($updatePassword == 'success') {
						unset($_SESSION['confirmedResetId']);
						unset($_SESSION['setUserType']);
						unset($_SESSION['securityQuestion']);
						self::$forgotPassword = NULL;
						$_SESSION['resetSuccess'] = 1;
					} else {
						$type = 'error';
						$msg = '<b>There Was An Error Updating Your Password</b>';
						self::alertDisplay($type, $msg);
					}
				}
			}
		//NORMAL REMINDERS AND PERSONAL REMINDERS FORM HANDLING 
		} elseif ((isset($_POST['updateReminderSubmit']) && isset($_POST['updateReminderForm'])) || (isset($_POST['setPersonalReminderSubmit']) && isset($_POST['setPersonalReminderForm'])) || (isset($_POST['updatePersonalReminderSubmit']) && isset($_POST['updatePersonalReminderForm']))) {
			$reminder = new Reminder;
			$success = 0;
			$_SESSION['reminderStatus'] = array();
			if (isset($_POST['updateReminderSubmit'])) {
				if (isset($_POST['countReminders'])) {
					$totalReminders = (int) $_POST['countReminders'];
					for ( $i = 1; $i <= $totalReminders; $i++ ) {
						if (isset($_POST["switch-$i"])) {
							$reminderId = self::unmaskURLParam($_POST["switch-$i"]);
							if ( isset($_POST["futureRemind$i"]) && !empty($_POST["futureRemind$i"]) ) {
								if (!is_numeric($_POST["futureRemind$i"])) {
									self::redirect('settings.php?reminder=errorval&item='.self::maskURLParam($reminderId));
									return;
								} else {
									$updateReminder = $reminder->updateReminder( $reminderId, $_POST["futureRemind$i"], $pdo );
								}
							} else {
								$updateReminder = $reminder->updateReminderStatus ( $reminderId, $pdo );
							}
							//Confirm that Current Reminder has been updated					
							if ( $updateReminder == 'success' ) {
								$_SESSION['reminderStatus'][$i] = array('type' => 'success', 'id' => $reminderId);
								continue;
							} else {
								$_SESSION['reminderStatus'][$i] = array('type' => 'error', 'id' => $reminderId);
								continue;
							}
						} else {
							continue;
						}
					}
				}
				if (isset($_POST['countPersonalReminders'])) {
					$totalPersonalReminders = (int) $_POST['countPersonalReminders'];
					$totalCount = (isset($totalReminders)) ? $totalReminders + $totalPersonalReminders : $totalPersonalReminders;
					$initCount = (isset($totalReminders)) ?  $totalReminders + 1 : 1;
					for ( $i = $initCount; $i <= $totalCount; $i++ ) {
						if (isset($_POST["switch-$i"])) {
							$reminderId = self::unmaskURLParam($_POST["switch-$i"]);
							if ( isset($_POST["personalRemind$i"]) && !empty($_POST["personalRemind$i"]) ) {
								if (!self::dateValidator ( $_POST["personalRemind$i"] )) {
									$_SESSION['reminderStatus'][$i] = array('type' => 'error', 'id' => $reminderId);
									continue;
								} else {
									$remindTime = ((isset($_POST["eventTime$i"]) && !empty($_POST["eventTime$i"])) && (is_numeric($_POST["eventTime$i"]) && $_POST["eventTime$i"] > 0 || $_POST["eventTime$i"] <= 24)) ? $_POST["eventTime$i"] : '00:00:00';
									$confirmRemindTime = ($remindTime == 24) ? '00' : $remindTime;
									$reminderDateTime = $_POST["personalRemind$i"].' '.$confirmRemindTime;
									$updatePersonalReminder = $reminder->postponePersonalReminder ( $reminderId, $reminderDateTime, $pdo );
								}
							} else {
								$personal = 1;
								$updatePersonalReminder = $reminder->updateReminderStatus ( $reminderId, $pdo, $personal );
							}
							//Confirm that Current Reminder has been updated					
							if ( $updatePersonalReminder == 'success' ) {
								$_SESSION['reminderStatus'][$i] = array('type' => 'success', 'id' => $reminderId);
								continue;
							} else {
								$_SESSION['reminderStatus'][$i] = array('type' => 'error', 'id' => $reminderId);
								continue;
							}
						} else {
							$_SESSION['reminderStatus'][$i] = array ('type' => 'info');
							continue;
						}	
					}
				}
				self::redirect(basename($_SERVER['PHP_SELF']));
			//PERSONAL REMINDERS FORM HANDLING
			} elseif (isset($_POST['setPersonalReminderSubmit']) || isset($_POST['updatePersonalReminderSubmit'])) {
				$reminderDesc = self::sanitize($_POST['reminderDesc']);
				$eventDate = self::dateValidator($_POST['eventDate']);
				$targetDate = self::dateValidator($_POST['targetDate']);
				if ( is_null($eventDate) ) {
					$_SESSION['eventDate'] = 1;
					return;
				} elseif ( is_null($targetDate) ) {
					$_SESSION['targetDate'] = 1;
					return;
				}
				$eventTime = (is_numeric($_POST['eventTime']) && $_POST['eventTime'] > 0 || $_POST['eventTime'] <= 24) ? $_POST['eventTime'] : '00:00:00';
				$targetTime = (is_numeric($_POST['targetTime']) && $_POST['targetTime'] > 0 || $_POST['targetTime'] <= 24) ? $_POST['targetTime'] : '00:00:00';
				$confirmEventTime = ($eventTime == 24) ? '00' : $eventTime;
				$confirmTargetTime = ($targetTime == 24) ? '00' : $targetTime;
				$eventDateTime = $eventDate.' '.$confirmEventTime;
				$remindDateTime = $targetDate.' '.$confirmTargetTime;
				if ($_POST['remindOthers'] == 1) {
					$othersInvolved = 'Admins';
				} elseif ($_POST['remindOthers'] == 2) {
					if (!isset($_POST['addedAdmin'])) {
						$_SESSION['addedAdminError'] = 1;
						return;
					}
					for ($i = 0; $i < count($_POST['addedAdmin']); $i++) {
						$involved[$i] = self::shortenID(self::unmaskURLParam($_POST['addedAdmin'][$i]));
					}
					$othersInvolved = implode(',', $involved);
				} else {
					$othersInvolved = 'None';
				}
				if (isset($_POST['setPersonalReminderSubmit'])) {
					$setReminder = $reminder->setPersonalReminder ( $reminderDesc,  $eventDateTime, $remindDateTime, $othersInvolved, $pdo );
				} elseif (isset($_POST['updatePersonalReminderSubmit'])) {
					$updateReminderId = self::unmaskURLParam($_POST['updatePersonalReminderForm']);
					$setReminder = $reminder->updatePersonalReminder ( $updateReminderId, $reminderDesc, $eventDateTime, $remindDateTime, $othersInvolved, $pdo );
				}
				if ($setReminder == 'success') {
					$_SESSION['reminderStatus'] = 'success';
				} else {
					$_SESSION['reminderStatus'] = 'error';
				}
			}
		//MESSAGES FORM HANDLING
		} elseif ((isset($_POST['createNewMsg']) || isset($_POST['readMessage']) || isset($_POST['readSentMessage']) || isset($_POST['readDraftMessage']) || isset($_POST['inboxMessages']) || isset($_POST['sentMessages']) || isset($_POST['draftMessages']) || isset($_POST['reply']) || isset($_POST['forwardMsg']) || isset($_POST['editMsg']) || isset($_POST['deleteMsg'])) && isset($_POST['messagesPanelForm'])) {
			$newObject = new MailMessages;
			if (isset($_POST['createNewMsg'])) {
				if (isset($_SESSION['messageReply'])) unset($_SESSION['messageReply']);
				if (isset($_SESSION['messageEdit'])) unset($_SESSION['messageEdit']);
				if (isset($_SESSION['messageForward'])) unset($_SESSION['messageForward']);
				unset($_SESSION['mailMessages']);
				$_SESSION['mailMessages'][] = 'createNewMsg';
				self::redirect('messages.php');
			} elseif (isset($_POST['readMessage'])) {
				unset($_SESSION['mailMessages']);
				$_SESSION['mailMessages'][] = 'readMessage';
				$msgId = self::unmaskURLParam($_POST['readMessage']);
				$newObject->updateMessageReadStatus ( $msgId, $pdo );
				$_SESSION['mailMessages'][] = $msgId;
				self::redirect('messages.php');
			} elseif (isset($_POST['readSentMessage'])) {
				var_dump($_POST);
				unset($_SESSION['mailMessages']);
				$_SESSION['mailMessages'][] = 'readSentMessage';
				$msgId = self::unmaskURLParam($_POST['readSentMessage']);
				$_SESSION['mailMessages'][] = $msgId;
				self::redirect('messages.php');
			} elseif (isset($_POST['readDraftMessage'])) {
				unset($_SESSION['mailMessages']);
				$_SESSION['mailMessages'][] = 'readDraftMessage';
				$msgId = self::unmaskURLParam($_POST['readDraftMessage']);
				$_SESSION['mailMessages'][] = $msgId;
				self::redirect('messages.php');
			} elseif (isset($_POST['inboxMessages'])) {
				unset($_SESSION['mailMessages']);
				self::redirect('messages.php');
			} elseif (isset($_POST['sentMessages'])) {
				unset($_SESSION['mailMessages']);
				$_SESSION['mailMessages'][] = 'sentMessages';
				self::redirect('messages.php');
			} elseif (isset($_POST['draftMessages'])) {
				unset($_SESSION['mailMessages']);
				$_SESSION['mailMessages'][] = 'draftMessages';
				self::redirect('messages.php');
			} elseif (isset($_POST['reply'])) {
				if (isset($_SESSION['messageForward'])) unset($_SESSION['messageForward']);
				if (isset($_SESSION['messageEdit'])) unset($_SESSION['messageEdit']);
				if (isset($_SESSION['messageReply'])) unset($_SESSION['messageReply']);
				unset($_SESSION['mailMessages']);
				$receivers = self::sanitize($_POST['replySubmit']);
				$_SESSION['messageReply'] = $receivers;
				$_SESSION['mailMessages'][] = 'createNewMsg';
				self::redirect('messages.php');
			} elseif (isset($_POST['forwardMsg'])) {
				if (isset($_SESSION['messageForward'])) unset($_SESSION['messageForward']);
				if (isset($_SESSION['messageEdit'])) unset($_SESSION['messageEdit']);
				if (isset($_SESSION['messageReply'])) unset($_SESSION['messageReply']);
				$msgId = self::sanitize($_POST['forwardMsg']);
				$_SESSION['messageForward'][] = $_SESSION['mailMessages'];
				$_SESSION['messageForward'][] = $msgId;
				unset($_SESSION['mailMessages']);
				$_SESSION['mailMessages'][] = 'createNewMsg';
				self::redirect('messages.php');
			} elseif (isset($_POST['editMsg'])) {
				if (isset($_SESSION['messageForward'])) unset($_SESSION['messageForward']);
				if (isset($_SESSION['messageEdit'])) unset($_SESSION['messageEdit']);
				if (isset($_SESSION['messageReply'])) unset($_SESSION['messageReply']);
				$msgId = self::sanitize($_POST['editMsg']);
				$_SESSION['messageEdit'] = $msgId;
				unset($_SESSION['mailMessages']);
				$_SESSION['mailMessages'][] = 'createNewMsg';
				self::redirect('messages.php');
			} elseif (isset($_POST['deleteMsg'])) {
				$msgId = self::unmaskURLParam($_POST['deleteMsg']);
				$_SESSION['confirmDelete'] = $newObject->deleteMessage( $msgId, $pdo );
				if ($_SESSION['mailMessages'][0] == 'readDraftMessage') {
					unset($_SESSION['mailMessages']);
					$_SESSION['mailMessages'][] = 'draftMessages';
				} elseif ($_SESSION['mailMessages'][0] == 'readSentMessage') {
					unset($_SESSION['mailMessages']);
					$_SESSION['mailMessages'][] = 'sentMessages';
				} elseif ($_SESSION['mailMessages'][0] == 'readMessage') {
					unset($_SESSION['mailMessages']);
				}
				self::redirect('messages.php');
			}
		} elseif ((isset($_POST['mailMessageSubmit']) || isset($_POST['saveAsDraft'])) && isset($_POST['mailMessageForm']) && isset($_POST['messagesPanelForm'])) {
			//validate that values are not empty
			$newObject = new MailMessages;
			$messageSubject = ($_POST['messageSubject'] !== '') ? self::sanitize($_POST['messageSubject']) : 'New Message';
			$messageContent = self::sanitize($_POST['messageContent']);
			$getAttachments = (isset($_POST['attachedFiles']) && $_POST['attachedFiles'] !== '') ? self::sanitize($_POST['attachedFiles']) : '';
			$allReceiver = '';
			if (isset($_POST['mailMessageForm']) && $_POST['mailMessageForm'] !== '') {
				$receiver = explode(';', $_POST['mailMessageForm']);
				if (count($receiver) > 1) {
					for ($i = 0; $i < count($receiver); $i++) 
					$allReceiver .= self::unmaskURLParam($receiver[$i]) .';';
					$senderDetails = rtrim($allReceiver, ';');
				} else {
					$senderDetails = self::unmaskURLParam($receiver[0]);
				}
			} else {
				$senderDetails = '';
			}
			$readMsg = (isset($_POST['saveMessageDraft']) && $_POST['saveMessageDraft'] == 'saveDraftCurrently') ? '' : 'N';
			$sendMessage = $newObject->sendMessage( $_SESSION['userId'], $senderDetails, $messageSubject, $messageContent, $getAttachments, $readMsg, $pdo );
			if ($sendMessage == 'success') {
				unset($_SESSION['mailMessages']);
				$_SESSION['messageReply'] = 'success';
				if (isset($_POST['saveAsDraft'])) $_SESSION['mailMessages'] == 'draftMessages';
			} else {
				$_SESSION['messageReply'] = $error;
			}
			self::redirect('messages.php');
		} elseif (isset($_POST['deleteMessagesConfirmed']) && isset($_POST['messagesPanelForm'])) {
			$newObject = new MailMessages;
			$messagesToDelete = $_POST['markToDelete'];
			for ($i = 0; $i < count($messagesToDelete); $i++) {
				$msgId = self::unmaskURLParam($messagesToDelete[$i]);
				$_SESSION['confirmDelete'] = $newObject->deleteMessage( $msgId, $pdo );
			}
			self::redirect('messages.php');
		} elseif (isset($_POST['obtainUserId']) && isset($_POST['mailUserType'])) {
			$newObject = new Users;
			$userId = self::unmaskURLParam($_POST['obtainUserId']);
			$userType = $_POST['mailUserType'];
			$getUserDetails = $newObject->getData ( $userType, $userId, $pdo, 1 );
			$getUserDetails['userId'] = self::maskURLParam($getUserDetails['userId']);
			$getUserDetails['photo'] = urldecode($getUserDetails['photo']);
			echo json_encode($getUserDetails);
			exit();
		} elseif (isset($_POST['uploadAttachments'])) {
			if (isset($_FILES['updatePhoto'])) {
				$uploadFiles = self::multipleUpload ( 'Attachments' );
				echo json_encode($uploadFiles);
			}
			die();
		} elseif (isset($_POST['photoId']) || isset($_POST['deleteUpload'])) {
			$attachment = 'attachments/' . $_POST['photoId'];
			if (file_exists($attachment)) {
				unlink($attachment);
			}
			echo 'success';
			die();
		}
	}

	public static function getUserTypeFromUserId ( $userId ) {
		if ($userId{6} == 'A' && $userId{13} == 'M') {
			return 'Admin';
		} elseif ($userId{6} == 'C' && $userId{13} == 'T') {
			return 'Customer';
		} elseif ($userId{6} == 'S' && $userId{13} == 'D') {
			return 'Student';
		}
	}

	public static function getAllUsers ( $pdo ) {
		$user = new Users;
		$sql = "SELECT user_id, user_type FROM login_tbl ORDER BY user_type ASC";
		$allUsers = $pdo->query($sql)->fetchAll();
		$confirmUserType = 1;
		$noPhoto = 1;
		foreach ( $allUsers as $key => $value ) {
			if ($value['user_type'] == $_SESSION['userType'] && $value['user_id'] == $_SESSION['userId']) continue;
			$userDetails[] = $user->getData ( $value['user_type'], $value['user_id'], $pdo, $confirmUserType, $noPhoto );
		}
		return $userDetails; 
	}
	
	public static function shortenID ( $ID ) {
		//Shortening Id to be saved in Database for Reminders
		return substr($ID, 0, 10);
	}

	public static function compareShortenedID ( $list, $all, $user, $pdo, $display = NULL ) {
		$splitList = explode(',', $list);
		foreach ($all as $key => $value) {
			$allAdmin[$key + 1] = $value['staff_id'];
			$adminIds[$key + 1] = self::shortenID($value['staff_id']);
		}
		for ($i = 0; $i < count($splitList); $i++ ) {
			$check = array_search($splitList[$i], $adminIds);
			if ($check) {
				if (isset($display)) {
					$getAdminDetails[] = $allAdmin[$check];
				} else {
					$getAdminDetails[] = $user->getData (get_class($user), $allAdmin[$check], $pdo);
				}
			}
		}
		return $getAdminDetails;
	}

	public static function maskURLParam ( $value ) {
		return urlencode(base64_encode($value));
	}

	public static function unmaskURLParam ( $value ) {
		return base64_decode(urldecode($value));
	}

	public static function dateValidator ( $value ) {
		$date = DateTime::createFromFormat("Y-m-d", $value);
		$check = DateTime::getLastErrors();
		if (!empty($check['errors']) || !empty($check['warnings'])) {
			return NULL;
		} else {
			return $date->format('Y-m-d');
		}
	}

	private static function confirmPreviousPass ( $pdo, $newPassword ) {
		//Get Current Password
		$sql = "SELECT hash_pass FROM login_tbl WHERE user_id = :userId";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':userId' => $_SESSION['confirmedResetId'] ]);
		$currentPassword = $stmt->fetchColumn();
		//Confirm if New Password is same as current password
		if ($confirmCurrent = password_verify($newPassword,$currentPassword)) {
			return 'exist';
		}
		//Get Record of All Previous Passwords
		$sql = "SELECT passwords FROM passwords_tbl WHERE user_id = :userId";
		$stmt = $pdo->prepare($sql);
		$userId = (isset($_SESSION['confirmedResetId'])) ? $_SESSION['confirmedResetId'] : $_SESSION['userId'];
		$stmt->execute([':userId' => $userId ]);
		$confirmResult = $stmt->fetchColumn();
		if ($confirmResult) {
			$retrievedPasswords = explode(' , ', $confirmResult, 5);
			foreach ($retrievedPasswords as $value) {
				$confirmPass = password_verify($newPassword, $value);
				if ($confirmPass) {
					$exist = 1;
				}
			}
			if (isset($exist)) {
				return 'exist';
			} else {
				$newPasswordList = $confirmResult .' , '. $currentPassword;
				$sql = "UPDATE passwords_tbl SET passwords = :newList WHERE user_id = :userId";
				$stmt = $pdo->prepare($sql);
				$stmt->execute([':newList' => $newPasswordList, ':userId' => $_SESSION['confirmedResetId'] ]);
				if ($stmt->rowCount()) {
					return NULL;
				}
			}
		} else {
			$sql = "INSERT into passwords_tbl VALUES (:userId, :currentPassword)";
			$stmt= $pdo->prepare($sql);
			$stmt->execute([':userId' => $_SESSION['confirmedResetId'], ':currentPassword' => $currentPassword]);
			if ($stmt->rowCount()) {
				return NULL;
			}
		}
	}
	
	public static function formatDateTime ( $dateTime ) {
		//$dateTime in format: YYYY-MM-DD HH:MM:SS
		$seperateAll = explode(' ', $dateTime, 2);
		$seperateDate = explode('-', $seperateAll[0]);
		$dateFormat = self::formatDate($seperateDate[2]).' '.self::formatMonth($seperateDate[1]).', '. $seperateDate[0];
		$timeFormat = self::formatTime($seperateAll[1]);
		return $dateFormat.' <b>|</b> '.$timeFormat;
	}

	public static function formatDate ( $value ) {
		if ($value == 1 || $value == 21 || $value == 31) {
			return $value.'st';
		} elseif ( $value == 2 || $value == 22) {
			return $value.'nd';
		} elseif ( $value == 3 || $value == 23) {
			return $value.'rd';
		} else {
			return $value.'th';
		}
	}
	
	public static function formatMonth ( $value ) {
		if ($value == 1) {
			return 'January';
		} elseif ($value == 2) {
			return 'February';
		} elseif ($value == 3) {
			return 'March';
		} elseif ($value == 4) {
			return 'April';
		} elseif ($value == 5) {
			return 'May';
		} elseif ($value == 6) {
			return 'June';
		} elseif ($value == 7) {
			return 'July';
		} elseif ($value == 8) {
			return 'August';
		} elseif ($value == 9) {
			return 'September';
		} elseif ($value == 10) {
			return 'October';
		} elseif ($value == 11) {
			return 'November';
		} elseif ($value == 12) {
			return 'December';			
		}
	}

	public static function formatTime ( $timeValue ) {
		//timeValue is sent in format HH:MM:SS
		$seperate = explode(':', $timeValue, 3);
		return self::timeAMPM($seperate[0]);
	}

	public static function timeAMPM ( $number ) {
		if ( $number >= 12 ) {
			$time = ($number == 12) ? $number : $number - 12;
			$amOrPm = 'PM';
			if ($number == 24) {
				$amOrPm = 'MIDNIGHT';
			} elseif ($number == 12) {
				$amOrPm = 'NOON';
			}
		} else {
			$time = $number; $amOrPm = 'AM';
		}
		return $time.' '.$amOrPm;	
	}

	private static function confirmEmail ( $pdo, $email, $type ) {
		if ($type == 1) {
			$dbTbl = 'staff_tbl';
			$dbId = 'staff_id';
		} elseif ($type == 2) {
			$dbTbl = 'cust_tbl';
			$dbId = 'cust_id';
		} elseif ($type == 3) {
			$dbTbl = 'student_tbl';
			$dbId = 'student_id';
		}
		$sql = "SELECT email, security_question, $dbId as userId FROM $dbTbl WHERE email = :email";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':email' => $email]);
		return $stmt->fetch();
	}
	
	private static function confirmResetDetails ( $pdo, $username, $phone, $birthday, $question ) {
		$type = $_SESSION['setUserType'];
		$getUserId = $_SESSION['getUserId'];
		if ($type == 1) {
			$dbTbl = 'staff_tbl';
			$dbId = 'staff_id';
		} elseif ($type == 2) {
			$dbTbl = 'cust_tbl';
			$dbId = 'cust_id';
		} elseif ($type == 3) {
			$dbTbl = 'student_tbl';
			$dbId = 'student_id';
		}
		$sql = "SELECT login_tbl.username, login_tbl.user_id as returnedId FROM login_tbl INNER JOIN $dbTbl ON login_tbl.username = :username AND login_tbl.user_id = $dbTbl.$dbId AND login_tbl.user_id = :userId AND $dbTbl.phone = :phone AND $dbTbl.birthday = :birthday AND $dbTbl.security_answer = :answer";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':username' => $username, ':userId' => $getUserId, ':phone' => $phone, ':birthday' => $birthday, ':answer' => $question ]);
		return $stmt->fetch();
	}
	
	public static function returnAccountType ( $value ) {
		if ($value == 1) {
			return 'Admin';
		} elseif ($value == 2) {
			return 'Customer';
		} elseif ($value == 3) {
			return 'Student';
		} else {
			return 'Account Error!';
		}
	}
	
	public static function sanitize ( $input ) {
		return strip_tags(htmlentities(trim( $input )));
	}
	
	public static function errorPage ( $type ) {
		if ( $type == 'restricted' ) {
			$type = 'error';
			$msg = 'You Do Not Have The Permissions Required To View This Page';
			$link = 'index.php';
			$linkValue = 'Back To Home';
			if (isset($_SERVER['HTTP_REFERER'])) {
				$link = basename($_SERVER['HTTP_REFERER']);
				$linkValue = 'Go Back';
			}
			self::alertDisplay ( $type, $msg, $link, $linkValue, 1 );
		} else {
			$type = 'error';
			$msg = 'The Page You Are Trying to Access Doesn\'t Exist';
			$link = 'index.php';
			$linkValue = 'Back To Home';
			if (isset($_SERVER['HTTP_REFERER'])) {
				$link = basename($_SERVER['HTTP_REFERER']);
				$linkValue = 'Go Back';
			}
			self::alertDisplay ( $type, $msg, $link, $linkValue, 1 );
		}
	}
	/**
	*	Method redirect redirects to a particular page
	*	@param location url to which redirect targets
	*	@return void
	*/
	public static function redirect( $location = NULL ) {		
		if ($location != NULL) {
			$_POST = array();
			$_FILES = array();
			header("Location: {$location}");
			exit;
		}
	}
	
	public static function endSession ( $pdo, $loginFail = NULL ) {
		//Find Session
		if (!isset( $_SESSION )) {
			session_start();
		}
		//Unset all the session variables
		$_SESSION = array();
		
		//Destroy the session cookie
		if(isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()-100000, '/');
		}
		
		if ($loginFail !== NULL ) {
			//Destroy the session; Cannot Destroy uninitialized session
			session_destroy();
		}
		
		//unset the connection
		$pdo = "NULL";
	}
	
	public static function generateId( $length ) {
		//0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ
		if ($length == 25) {
			return substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", 15)), 0, $length);
		} else { //Using only UPPER ALPHABETHS AND NUMBERS
			return substr(str_shuffle(str_repeat("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ", 10)), 0, $length);
		}
	}
	 	
	public static function checkGeneratedId ( $pdo ) {
		do {$newUserId = self::generateId( 15 );
		//Check just in case ID exist in Database; using Do-While loop
		$stmt = $pdo->prepare("SELECT user_id FROM login_tbl WHERE user_id = ?");
		$stmt->execute([$newUserId]);
		$foundId = $stmt->fetchColumn();
		} while ($foundId);
		return $newUserId;
	}
	
	public static function startSession() {
		//Prevents Session from being accessible through javascript
		ini_set('session.cookie_httponly', true);
		
		session_start(); 
		
		//Sets Session to the IP Address of the user
		if (isset($_SESSION['last_ip']) === false){
			$_SESSION['last_ip'] = $_SERVER['REMOTE_ADDR'];
			//Use for security related things only
		}
		
		if ($_SESSION['last_ip'] !== $_SERVER['REMOTE_ADDR']){
			session_unset();
			session_destroy();
		}
	}
		
	public static function scoreValue ( $score ) {
		if ( $score >= 70 ) {
			return "A";
		} elseif ( $score < 70 && $score >= 60 ) {
			return "B";
		} elseif ( $score < 60 && $score >= 50 ) {
			return "C";
		} elseif ( $score < 50 && $score >= 45 ) {
			return "D";
		} elseif ( $score < 45 && $score >= 40 ) {
			return "E";
		} elseif ( $score < 40 ) {
			return "F";		
		}
	}
	
	public static function gradeColorCode ( $gradeValue ) {
		if ( $gradeValue >= 70 ) {
			return "text-success";
		} elseif ( $gradeValue < 70 && $gradeValue >= 60 ) {
			return "text-info";
		} elseif ( $gradeValue < 60 && $gradeValue >= 50 ) {
			return "text-normal";
		} elseif ( $gradeValue < 50 && $gradeValue >= 45 ) {
			return "text-warn";
		} elseif ( $gradeValue < 45 && $gradeValue >= 40 ) {
			return "text-warn";
		} elseif ( $gradeValue < 40 ) {
			return "text-danger";		
		}
	}
	
	/**
	*	Method alertDisplay to display alerts
	*/	
	public static function alertDisplay ( $alertType, $msg, $link = NULL, $linkValue = NULL, $verticalAlign = NULL ) {
		$hidden = (isset($link)) ? '' : ' hidden';
		$alertId = (isset($link)) ? '' : 'id="alertDisplay"';
		echo '<div class="row">';
		echo "<div class=\"col-sm-offset-2 col-sm-8 text-center\" >";
		$bold = (isset($link) && isset($linkValue)) ? '' : 'bold';
		if ( $alertType == 'error' ) {
			if (isset($verticalAlign)) {
				echo "<div class=\"alert alert-danger vertical-child col-xs-12 $bold $hidden\" $alertId role=\"alert\">";
			} else {
				echo "<div class=\"alert alert-danger $bold $hidden\" $alertId role=\"alert\">";
			}
		} elseif ( $alertType == 'info' ) {
			if (isset($verticalAlign)) {
				echo "<div class=\"alert alert-info vertical-child col-xs-12 $bold $hidden\" $alertId role=\"alert\">";
			} else {
				echo "<div class=\"alert alert-info $bold $hidden\" $alertId role=\"alert\">";
			}
		} elseif ( $alertType == 'warning' ) {
			if (isset($verticalAlign)) {
				echo "<div class=\"alert alert-warning vertical-child col-xs-12 $bold $hidden\" $alertId role=\"alert\">";
			} else {
				echo "<div class=\"alert alert-warning $bold $hidden\" $alertId role=\"alert\">";
			}
		} elseif ( $alertType == 'success' ) {
			if (isset($verticalAlign)) {
				echo "<div class=\"alert alert-success vertical-child $bold $hidden\" $alertId role=\"alert\">";
			} else {
				echo "<div class=\"alert alert-success $bold $hidden\" $alertId role=\"alert\">";
			}
		} else {
			echo "<div class=\"alert text-info $bold $hidden\" $alertId role=\"alert\">";
		}
		echo $msg ."<br />";
		if (isset($link) && isset($linkValue)) {
			echo "<a class=\"alert-link\" href=\"$link\" >$linkValue</a>";
		}
		echo "</div>
		</div></div>";
	}
	
	public static function compareDates ( $date1 ) {
		$deadline = date_create( $date1 );
		$today = date_create(Date('Y-m-d'));
		$dateDifference = date_diff($today, $deadline);
		$setDate = $dateDifference->format("%a");
		return $setDate;
	}
	
	public static function determinePeriod ( $getDaysAway, $getHoursAway = NULL, $pastDate = NULL ) {
		$AwayOrAgo = (isset($pastDate)) ? 'Ago' : 'Away';
		$initOpen = (!isset($pastDate)) ? '<br />(' : '';
		$initClose = (!isset($pastDate)) ? ')' : '';
		if ( $getDaysAway > 1 ) {
			if ( $getDaysAway > 7 ) {
				$getWeeksAway = $getDaysAway / 7;
				settype($getWeeksAway, 'integer');
				$weekAway = ( $getWeeksAway > 1 ) ? $getWeeksAway ." Weeks, " : $getWeeksAway ." Week, " ;
				$setDaysAway = (( $getDaysAway % 7 ) > 1) ? $getDaysAway % 7 ." Days " : $getDaysAway % 7 ." Day ";
				return "<b>$initOpen$weekAway $setDaysAway $AwayOrAgo$initClose</b>";
			} elseif ( $getDaysAway == 7 ) {
				return '<b>'.$initOpen."1 Week $AwayOrAgo$initClose</b>";
			} else {
				return "<b>$initOpen$getDaysAway Days $AwayOrAgo$initClose</b>";
			}
		} elseif ( $getDaysAway == 1 ) {
			return $daysAway = "<b>$initOpen$getDaysAway Day $AwayOrAgo$initClose</b>";
		} else {
			if (!isset($getHoursAway)) {
				return '<b>'.$initOpen .'Today'. $initClose .'</b>';
			} else {
				$getHoursAway = explode(':', $getHours, 3);
				if ( $getHoursAway[0] > 1 ) {
					return "$initOpen$getHoursAway[0] Hours $getHoursAway[1] Minutes $AwayOrAgo$initClose";
				} elseif ( $getHoursAway[0] == 1 ) {
					return "$initOpen$getHoursAway[0] Hour $getHoursAway[1] Minutes $AwayOrAgo$initClose";
				} else {
					if ( $getHoursAway[1] > 1 ) {
						return $initOpen . (int)$getHoursAway[1] ." Minutes $AwayOrAgo$initClose";
					} elseif ( $getHoursAway[1] == 1 ) {
						return $initOpen . (int) $getHoursAway[1] ." Minute $AwayOrAgo$initClose";	
					} else {
						if ( $getHoursAway[2] > 1 ) {
							return $initOpen . (int)$getHoursAway[2] ." Seconds $AwayOrAgo$initClose";
						} else {
							return $initOpen . (int) $getHoursAway[2] ." Second $AwayOrAgo$initClose";				
						}
					}
				}
			}
		}
	}

	public static function checkFileType ( $fileName ) {
		$getExtension = explode('.', $fileName, 2);
		if ($getExtension[1] == 'pdf') {
			$file['type'] = 'PDF';
			$file['icon'] = '<span class="fa fa-file-pdf-o"></span>';
			return $file;
		} elseif ($getExtension[1] == 'doc' || $getExtension[1] == 'docx' ) {
			$file['type'] = 'WORD';
			$file['icon'] = '<span class="fa fa-file-word-o"></span>';
			return $file;
		} elseif ($getExtension[1] == 'jpg' || $getExtension[1] == 'png' || $getExtension[1] == 'gif' ) {
			$file['type'] = 'PIX';
			$file['icon'] = '<span class="fa fa-file-photo-o"></span>';
			return $file;
		} else {
			$file['type'] = 'FILE';
			$file['icon'] = '<span class="fa fa-file-o"></span>';
			return $file;
		}
	}
	
	public static function getSlidePhotos ( $pdo ) {
		$sql = "SELECT img_id, img_title, img_link FROM homeslide_tbl ORDER BY img_id ASC";
		return $stmt = $pdo->query($sql)->fetchAll();
	}
	
	private static function multipleUpload ( $type = NULL ) {
		//Gotten From PHP Documentation Site
		$file_ary = array();
		$file_count = count($_FILES['updatePhoto']['name']);
		$file_keys = array_keys($_FILES['updatePhoto']);
		
		for ( $i = 0; $i < $file_count; $i++ ) {
			foreach ($file_keys as $key) {
				$file_ary[$i][$key] = $_FILES['updatePhoto'][$key][$i];
			}		
		}
		$_FILES = $file_ary;
		foreach ($_FILES as $value) {
			$_FILES['file1'] = $value;
			if ( !empty($_FILES['file1']['name']) ) {
				if (isset($type)) {
					$imgURL[] = self::upload ( $type );
				}
			} else {
				$imgURL[] = '';
			}
		}
		return $imgURL;
	}
	
	public static function upload ( $type = NULL ) {
		$error = array();
		//Check that the file is in an image format
		if ((isset($_POST['addDesignSubmit']) && isset($_POST['addDesignForm'])) || (isset($_POST['editDesignSubmit']) && isset($_POST['editDesignForm'])) || (isset($_POST['createCustomerSubmit']) && isset($_POST['createCustomerForm'])) || (isset($_POST['editCustomerSubmit']) && isset($_POST['editCustomerForm'])) || (isset($_POST['userSettingsSubmit']) && isset($_POST['saveUserSettings'])) || (isset($_POST['userDataSubmit']) && isset($_POST['saveUserData'])) || ((isset($_POST['mailMessageSubmit']) || isset($_POST['saveAsDraft'])) && isset($_POST['mailMessageForm'])) || isset($_POST['uploadAttachments'])) {
			if ( isset($type) && $type == 'Design' ) {
				$target_dir = "designs/";
				$newPhotoName = 'design' . self::generateId(25);
			} elseif ( isset($type) && $type == 'Slide' ) {
				$target_dir = "slide/";
				$newPhotoName = self::generateId(20);
			} elseif ( isset($type) && $type == 'Attachments' ) {
				$target_dir = "attachments/";
			} else {
				$target_dir = "users/";
				$newPhotoName = 'photo' . self::generateId(25);
			}
			
			$initName = basename($_FILES["file1"]["name"]);
			$target_file = $target_dir . $initName;
			$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
			//Set Final Name of the file

			$uploadedFile = (isset($type) && $type == 'Attachments') ? $target_dir . $initName : $target_dir . $newPhotoName. '.'.$imageFileType;
			//Initialize an errors array
			$uploadOk = 1;
			// Check if image file is a actual image or fake image
			$check = getimagesize($_FILES["file1"]["tmp_name"]);
			//Check if uploaded file is an image
			if($check == false) {
				$error[] = "File is not an image. <br />";	
				$uploadOk = 0;
			// Check file size not larger than 100 KB
			} elseif ($_FILES["file1"]["size"] > 100000) {
				$error[] = "Sorry, your file is too large.<br />";
				$uploadOk = 0;
			// Allow certain file formats
			} elseif( $imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
				if ( isset($type) && $type == 'Attachments' ) {
					if ( $imageFileType == "exe" ) {
						$error[] = "File Type Not Allowed. <br />";
						$uploadOk = 0;
					}
				} else {
					$error[] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed. <br />";
					$uploadOk = 0;
				}
			// Check if $uploadOk is set to 0 by an error
			} elseif ($uploadOk == 0) {
				$error[] = "Sorry, your file was not uploaded.";
				return $error;
			// if everything is ok, try to upload file
			} else {
				if (move_uploaded_file($_FILES["file1"]["tmp_name"], $uploadedFile)) {
					return urlencode($uploadedFile);
				} else {
					$error[] = "There was an error uploading the file. 1";
					return $error;
				}
			}
		} else {
			$error[] = "There was an error uploading the file. 2";
			return $error;
		}
	}
	
/*	public static function upload ( $item ) {
		//Check that the file is in an image format
		if (isset($_POST['submit'])) {
			$generateId = generateId( 25 );
			$getType = get_class( $item );
			if ( $getType == "Admin" || $getType == "Student" || $getType == "Customer" ) {
				$target_dir = "users/";
				$newPhotoName = 'user' . $generateId;
			} elseif ( $getType == "Design" ) {
				$target_dir = "../items/";
				$newPhotoName = 'item' . $generateId;
			}
			//generate random Id string for storing file
			$target_file = $target_dir . basename($_FILES["file1"]["name"]);
			$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
			$uploadedFile = $target_dir . $newPhotoName. '.'.$imageFileType;
			// Check if image file is a actual image or fake image
			if(isset($_POST)) {
				$check = getimagesize($_FILES["file1"]["tmp_name"]);
				if($check !== false) {
					echo "File is an image - " . $check["mime"] . ".";
				} else {
					return "File is not an image.";
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
	}*/
	
}