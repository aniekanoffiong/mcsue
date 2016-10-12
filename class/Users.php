<?php

/** Class Users that contains base implementation for all Users
*	Admin, Customers, Students Classes
*/
class Users {
	protected $userId;
	protected $pdo;
	protected $tableLimit = 9;
	protected $gridLimit = 6;
	protected $userType;
	protected $user;
	protected $page;

	/**
	 *	Users Constructor
	 *	@param Connection variable to connect to database
	 */
	public function __construct ( $pdo = NULL ) {
		/**	
		 *	Set $pdo connection to be used in other methods
		 */
		if ( $pdo !== NULL ) {
			$this->pdo = $pdo;
		}
	}
	
	private function createNewUserId ($userType) {
		if ($userType == 'Admin') {
			$idChar1 = 'A';
			$idChar2 = 'M';
		} elseif ($userType == 'Customer') {
			$idChar1 = 'C';
			$idChar2 = 'T';
		} elseif ($userType == 'Student') {
			$idChar1 = 'S';
			$idChar2 = 'D';
		}
		//generate Id for the programme
		do {$newId = staticFunc::generateId( 6 ) . $idChar1 .staticFunc::generateId( 6 ) . $idChar2 .staticFunc::generateId( 6 );
		//Check just in case ID exist in Database using Do-While loop
		$stmt = $this->pdo->prepare("SELECT user_id FROM login_tbl WHERE user_id = ?");
		$stmt->execute([$newId]);
		$foundId = $stmt->fetchColumn();
		} while ($foundId);
		return $newId;
	}
	
	/**	
	 *	Method createAdmin to create a new Admin 
	 *	@param username username for the admin to be created
	 *	@param password password for the admin to be created
	 *	@param userType type of user to be created
	 */
	public function createUser ( $username, $password, $userType ) {
		$sql = "SELECT username FROM login_tbl WHERE username = :username";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':username'=>$username]);
		if ($stmt->rowCount()) {
			$type = 'error';
			$msg = 'The Username Chosen Already Exist';
			staticFunc::alertDisplay( $type, $msg, 1 );		
			return;
		}
		$this->userId = self::createNewUserId($userType);
		//Create Password Hash Value
		$hashPass = password_hash($password, PASSWORD_DEFAULT);
		//After validations and confirmations
		$sql = "INSERT INTO login_tbl VALUES ( :user_id, :username, :hash_pass, :user_type)";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':user_id'=>$this->userId, ':username'=>$username, ':hash_pass'=>$hashPass, ':user_type'=>$userType]);
		if ( $stmt->rowCount() > 0 ) {
			return 'success';
		} else {
			return 'error';
		}
	}
	
	/**	Method addUserDetails adds the contact details of the particular 
	*	User
	*	@param $userType sets the user type of the user
	*	@params $userId, $surname, $firstname, $othername, $gender,
	*	$street, $city, $state, $country, $phone, $phoneAlt, $email, 
	*	$regDate indicate the details of the user
	*/
	public function addUserDetails ( $userType, $surname, $firstname, $othername, $gender, $street, $city, $state, $country, $phone, $phoneAlt, $email, $photo ) {
		if ($userType == "Admin" || $userType == 'Staff') {
			$valTable = 'staff_tbl';
		} elseif ($userType == "Customer") {
			$valTable = 'cust_tbl';
		} elseif ($userType == "Student") {
			$valTable = 'student_tbl';
		}
		//Add user information to database
		$sql = "INSERT INTO $valTable VALUES ( :userId, :surname, :firstname, :othername, :street, :city, :state, :country, :gender, :phone, :phoneAlt, :email, :photo, curDate())";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':userId' => $this->userId, ':surname' => $surname, ':firstname' => $firstname, ':othername' => $othername, ':street' => $street, ':city' => $city, ':state' => $state, ':country' => $country, ':gender' => $gender, ':phone' => $phone, ':phoneAlt' => $phoneAlt, ':email' => $email, ':photo' => $photo]);
		if ($stmt->rowCount() > 0) {
			$type = 'success';
			$msg = 'The Customer Account Has Been Successfully Created';
			$link = 'customerdetails.php?customer='.staticFunc::maskURLParam($this->userId);
			$linkValue = 'View Account Created';
			staticFunc::alertDisplay( $type, $msg, $link, $linkValue );
			$_POST = array();
			$_FILES = array();
			return;
		} else {
			$type = 'error';
			$msg = 'There Was an Error Creating The Customer\'s Account';
			$link = 'customers.php';
			$linkValue = 'Back To Customers';
			staticFunc::alertDisplay( $type, $msg, $link, $linkValue );
			return;
		}
	}
	
	public function verifyCurrentPassword ( $currentPassword, $userId, $pdo ) {
		$sql = "SELECT hash_pass FROM login_tbl WHERE user_id = :userId";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':userId' => $userId]);
		$getPass = $stmt->fetchColumn();
		$confirmPass = password_verify($currentPassword, $getPass);
		if ($confirmPass) {
			return 'success';
		} else {
			return 'error';
		}
	}
	
	public function updatePassword ( $newPassword, $pdo ) {
		$newHashPass = password_hash($newPassword, PASSWORD_DEFAULT);
		$sql = "UPDATE login_tbl SET hash_pass = :newHashPass WHERE user_id = :userId AND user_type = :userType";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':newHashPass' => $newHashPass, ':userId' => $_SESSION['confirmedResetId'], ':userType' => staticFunc::returnAccountType($_SESSION['setUserType']) ]);
		if ( $stmt->rowCount() ) {
			return 'success';
		} else {
			return 'error';
		}
	}
	
	public function updateUserDetails ( $userType, $surname, $firstname, $othername, $gender, $street, $city, $state, $country, $phone, $phoneAlt, $email, $photo, $userId, $pdo ) {
		if ($userType == "Admin" || $userType == 'Staff') {
			$valTable = 'staff_tbl';
			$valId = 'staff_id';
		} elseif ($userType == "Customer") {
			$valTable = 'cust_tbl';
			$valId = 'cust_id';
		} elseif ($userType == "Student") {
			$valTable = 'student_tbl';
			$valId = 'student_id';
		}
		//Add user information to database
		$sql = "UPDATE $valTable SET surname = :surname, firstname = :firstname, othername = :othername, gender = :gender, street = :street, city = :city, state = :state, country = :country, phone = :phone, phone_alt = :phoneAlt, email = :email, photo = :photo WHERE $valId = :userId";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':surname' => $surname, ':firstname' => $firstname, ':othername' => $othername, ':gender' => $gender, ':street' => $street, ':city' => $city, ':state' => $state, ':country' => $country, ':phone' => $phone, ':phoneAlt' => $phoneAlt, ':email' => $email, ':photo' => $photo, ':userId' => $userId]);
		if ($stmt->rowCount()) {
			return 'success';
		} else {
			return 'error';
		}
	}
	
	public function updateUserData ( $userType, $password = NULL, $phone, $phoneAlt, $email, $security_question, $security_answer, $photo = NULL, $userId, $pdo ) {
		if ($userType == "Admin" || $userType == 'Staff') {
			$valTable = 'staff_tbl';
			$valId = 'staff_id';
		} elseif ($userType == "Customer") {
			$valTable = 'cust_tbl';
			$valId = 'cust_id';
		} elseif ($userType == "Student") {
			$valTable = 'student_tbl';
			$valId = 'student_id';
		}
		if ( isset($password) ) {
			$newHashPass = updatePasswordFromReset($password, $pdo);
			if ( $newHashPass !== 'success' ) {
				$error[0] = 1;
			}
		}
		if ( isset($photo) ) {
			$sql = "SELECT photo FROM $valTable WHERE $valId = :userId";
			$stmt = $pdo->prepare($sql);		
			$stmt->execute([':userId' => $userId]);
			$deletePrev = unlink(urldecode($stmt->fetchColumn()));
			if ( !$deletePrev ) { 
				$error[1] = 1;
			}
		}
		//Add user information to database
		$sql = "UPDATE $valTable SET phone = :phone, phone_alt = :phoneAlt, email = :email, security_question = :security_question, security_answer = :security_answer ";
		if ( isset($photo) ) {
			$sql .= ", photo = :photo ";
		}
		$sql .= " WHERE $valId = :userId";
		$stmt = $pdo->prepare($sql);
		$valuesArray = array(':phone' => $phone, ':phoneAlt' => $phoneAlt, ':email' => $email, ':security_question' => $security_question, ':security_answer' => $security_answer, ':userId' => $userId);
		if ( isset($photo) ) {
			$valuesArray[':photo'] = $photo;
		}
		$stmt->execute($valuesArray);
		if ($stmt->rowCount()) {
			return 'success';
		} else {
			return 'error';
		}
	}
	
	/** Method getUserDetails to view details of particular user
	*	@param $userType sets the user type to be viewed
	*	@param $userId id of the particular user
	*	@return result set for the particular user
	*/
	public function getUserDetails ( $userType, $userId ) {
		if ($userType == "Admin" || $userType == "Staff") {
			$valTable = 'staff_tbl';
			$valUserId = 'staff_id';
			$since = 'staff_since';
		} elseif ($userType == "Customer") {
			$valTable = 'cust_tbl';
			$valUserId = 'cust_id';
			$since = 'cust_since';
		} elseif ($userType == "Student") {
			$valTable = 'student_tbl';
			$valUserId = 'student_id';
			$since = 'student_since';
		}
		//Get value from database
		$sql = "SELECT $valUserId as user_id, CONCAT_WS(' ', surname, firstname, othername) AS names, CONCAT_WS(', ', street, city, state, country) as address, CONCAT_WS(', ', phone, phone_alt) as phone_numbers, surname, firstname, othername, gender, street, city, state, country, phone, phone_alt, email, photo, DATE_FORMAT($valTable.birthday, '%D %M, %Y') as birthday, security_question, security_answer, DATE_FORMAT($valTable.$since, '%D %M, %Y') as user_since FROM $valTable WHERE $valUserId = :userId";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':userId' => $userId]);
		return $stmt->fetchAll();
	}
	
	private function detailsForUpdate ( $userId ) {
		if ($this->userType == 'Admin' || $this->userType == 'Staff') {
			$valTable = 'staff_tbl';
			$valUserId = 'staff_id';
		} elseif ($this->userType == 'Customer') {
			$valTable = 'cust_tbl';
			$valUserId = 'cust_id';
		} elseif ($this->userType == 'Student') {
			$valTable = 'student_tbl';
			$valUserId = 'student_id';
		}
		$sql = "SELECT phone, phone_alt, email, photo, security_question, security_answer FROM $valTable where $valUserId = :userId";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':userId' => $userId]);
		return $stmt->fetchAll();
	}
	
	private function getLoginDetails ( $userId ) {
		$sql = "SELECT username FROM login_tbl WHERE user_id = :userId";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':userId' => $userId]);
		return $stmt->fetchColumn();
	}
	
	/** Method getUserDetails to view details of particular user
	*	@param $userType sets the user type to be viewed
	*	@param $userId id of the particular user
	*	@return result set for the particular user
	*/
	protected function allUserDetails ( $userType ) {
		if ($userType == 'Admin' || $userType == 'Staff') {
			$valTable = 'staff_tbl';
			$valUserId = 'staff_id';
			$since = 'staff_since';
		} elseif ($userType == 'Customer') {
			$valTable = 'cust_tbl';
			$valUserId = 'cust_id';
			$since = 'cust_since';
		} elseif ($userType == 'Student') {
			$valTable = 'student_tbl';
			$valUserId = 'student_id';
			$since = 'student_since';
		}
		$count = $this->pdo->query("SELECT count(*) FROM $valTable")->fetchColumn();
		if ($this->userType == 'Admin') {
			$sql = "SELECT count(*) FROM $valTable WHERE $valUserId <> :userId ";
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute([':userId' => $this->userId]);
			$count = $stmt->fetchColumn();
		}
		$currentPage = ( isset($_GET['page']) && is_numeric($_GET['page'] )) ? $_GET['page'] : 1;
		$paginate = new Paginate( $currentPage, $count, $this->tableLimit, $userType );
		$page = $paginate->segmentToPages();
		if (!is_array($page)) {
			return;
		} else {
			$end = $page[0];
			$start = $page[1];
		}		
		//Get value from database
		if ($userType == 'Admin') {
			$sql = "SELECT staff_id as user_id, CONCAT_WS(' ', surname, firstname, othername) AS names, CONCAT_WS(', ', street, city, state, country) as address, CONCAT_WS(', ', phone, phone_alt) as phone_numbers, email, photo FROM staff_tbl WHERE staff_id <> :userId ORDER BY staff_since ASC LIMIT $end OFFSET $start";
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute([':userId' => $this->userId]);
		} elseif ($userType == 'Customer') {
			$sql = "SELECT $valUserId as user_id, CONCAT_WS(' ', surname, firstname, othername) AS names, CONCAT_WS(', ', street, city, state, country) as address, CONCAT_WS(', ', phone, phone_alt) as phone_numbers, email, photo FROM $valTable ORDER BY $since ASC LIMIT $end OFFSET $start";
			$stmt = $this->pdo->query($sql);
		} else {
			$sql = "SELECT $valUserId as user_id, CONCAT_WS(' ', surname, firstname, othername) AS names, CONCAT_WS(', ', street, city, state, country) as address, CONCAT_WS(', ', phone, phone_alt) as phone_numbers, email, photo FROM $valTable ORDER BY $since ASC LIMIT $end OFFSET $start";
			$stmt = $this->pdo->query($sql);
		}
		//return result array
		return $stmt->fetchAll();	
	}
	
	/**	
	 *	Method getNames used to get the names of the user
	 *	@return returns the id, name and photo link of the user
	 */
	public function getData ( $userType, $userId, $pdo = NULL, $confirmUserType = NULL, $noPhoto = NULL ) {
		$photo = (isset($noPhoto)) ? '' : ', photo';
		if (isset($confirmUserType)) {
			$initSql = "SELECT user_type FROM login_tbl WHERE user_id = :userId";
			$determinePDO = (isset($pdo)) ? $pdo : $this->pdo;
			$stmt = $determinePDO->prepare($initSql);
			$stmt->execute([':userId' => $userId]);
			$userType = $stmt->fetchColumn();
		}
		if ($userType == __CLASS__ || $userType == 'Admin' || $userType == 'Staff') {
			$valTable = 'staff_tbl';
			$valUserId = 'staff_id';
		} elseif ($userType == 'Customer') {
			$valTable = 'cust_tbl';
			$valUserId = 'cust_id';
		} elseif ($userType == 'Student') {
			$valTable = 'student_tbl';
			$valUserId = 'student_id';
		}
		$sql = "SELECT $valUserId as userId, CONCAT_WS(' ', surname, firstname) AS name $photo FROM $valTable WHERE $valUserId = :userId";
		$determinePDO = (isset($pdo)) ? $pdo : $this->pdo;
		$stmt = $determinePDO->prepare($sql);
		$stmt->execute([':userId' => $userId]);
		return $stmt->fetch();
	}

	public function getAdminIds ( $pdo ) {
		$sql = "SELECT staff_id FROM staff_tbl ORDER BY staff_since DESC";
		return $stmt = $pdo->query($sql)->fetchAll();
	}
	
	public function getAllUsers ( $userType, $pdo, $userId = NULL ) {
		if ($userType == "Admin" || $userType == 'Staff') {
			$valTable = 'staff_tbl';
			$valUserId = 'staff_id';
		} elseif ($userType == "Customer") {
			$valTable = 'cust_tbl';
			$valUserId = 'cust_id';
		} elseif ($userType == "Student") {
			$valTable = 'student_tbl';
			$valUserId = 'student_id';
		}
		if (isset($userId)) {
			$sql = "SELECT $valUserId as userId, CONCAT_WS(' ', surname, firstname) AS name, photo FROM $valTable WHERE $valUserId <> :userId";
			$stmt = $pdo->prepare($sql);
			$stmt->execute([':userId' => $userId]);
			return $stmt->fetchAll();
		} else {
			$sql = "SELECT $valUserId as userId, CONCAT_WS(' ', surname, firstname) AS name, photo FROM $valTable";
			return $stmt = $pdo->query($sql)->fetchAll();
		}
	}
	
	public function deleteUser ( $userType, $userId, $pdo ) {
		if ($userType == "Admin" || $userType == 'Staff') {
			$valTable = 'staff_tbl';
			$valUserId = 'staff_id';
		} elseif ($userType == "Customer") {
			$valTable = 'cust_tbl';
			$valUserId = 'cust_id';
		} elseif ($userType == "Student") {
			$valTable = 'student_tbl';
			$valUserId = 'student_id';
		}
		//Delete User's Photo From File Location
		$sql = "SELECT photo FROM $valTable WHERE $valUserId = :userId";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':userId' => $userId]);
		if (unlink(urldecode($stmt->fetchColumn()))) {
			//Delete User's Records from Database
			$sql = "DELETE FROM $valTable WHERE $valUserId = :userId LIMIT 1";
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute([':userId' => $userId]);
			if ($stmt->rowCount()) {
				return 'success';
			} else {
				return 'error';
			}
		}
	}
	
	public function logout() {
		//Call endSession function
		staticFunc::endSession( $this->pdo );
		
		//Redirect
		staticFunc::redirect("../index.php?logout=success");
	}
	
	public function loggedIn() {
		return isset($_SESSION['userId']);
	}
		
	public function confirmLoggedIn () {
		if (!self::loggedIn()) {
			staticFunc::redirect("../index.php?login=failed");
		}
	}
	
	//Not In Use due to error with loading images to client
	public function getUserImage ( $names, $imageURL ) {
		if (isset($imageURL) && $image = @getimagesize(urldecode($imageURL))) {
			return "src=\"../class/view.php?url={$imageURL}&name=".staticFunc::maskURLParam($names)."\"" ;// .$image[3];
		} else {
			$defaultURL = '..%2F..%2F..%2Fmcsue%2Fusers%2Fphotodefault.gif';
			$image = @getimagesize($defaultURL);
			return "src=\"../class/view.php?url={$defaultURL}&name=default\"" .$image[3];
		}
	}

	public function initUserPreferences  ( $defaultSettings, $pdo ) {
		$sql = "INSERT INTO preferences_tbl VALUES(NULL, :user_id, :view_orders, :reminders, :designs_access, :payment_schedule, :payment_instalment, :remain_logged_in, :table_limit)";
		$stmt = $pdo->prepare($sql);
		$stmt->execute($defaultSettings);
		if ($stmt->rowCount() > 0) {
			staticFunc::redirect('user.php?default=success#top');
		} else {
			staticFunc::redirect('user.php?default=failed#top');
		}
	}
	
	public function restoreDefaultSettings ( $defaultSettings, $pdo ) {
		$sql = "UPDATE preferences_tbl SET view_orders = :view_orders, reminders = :reminders, designs_access = :designs_access, payment_schedule = :payment_schedule, payment_instalment = :payment_instalment, remain_logged_in = :remain_logged_in, table_limit = :table_limit WHERE user_id = :user_id";
		$stmt = $pdo->prepare($sql);
		$stmt->execute($defaultSettings);
		if ($stmt->rowCount() > 0) {
			staticFunc::redirect('user.php?default=success#top');
		} else {
			staticFunc::redirect('user.php?default=failed#top');
		}
	}
	
	public function updateUserSettings ( $viewOrders, $reminders, $designsAccess, $paymentSchedule, $paymentInstalment, $remainLoggedIn, $tableLimit, $userId, $pdo ) {
		$sql = "UPDATE preferences_tbl SET view_orders = :viewOrders, reminders = :reminders, designs_access = :designsAccess, payment_schedule = :paymentSchedule, payment_instalment = :paymentInstalment, remain_logged_in = :remainLoggedIn, table_limit = :tableLimit WHERE user_id = :userId";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':viewOrders' => $viewOrders, ':reminders' => $reminders, ':designsAccess' => $designsAccess, ':paymentSchedule' => $paymentSchedule, ':paymentInstalment' => $paymentInstalment, ':remainLoggedIn' => $remainLoggedIn, ':tableLimit' => $tableLimit, ':userId' => $userId]);
		if ($stmt->rowCount()) {
			return 'success';
		} else {
			return 'error';
		}
	}
	
	public function updatePhotoOrder ( $pdo ) {
		$sql = "UPDATE homeslide_tbl SET img_id = 3 WHERE img_id = 1";
		$stmt = $pdo->query($sql);
		if ($stmt->rowCount()) {
			$sql = "UPDATE homeslide_tbl SET img_id = 1 WHERE img_id = 2";
			$stmt = $pdo->query($sql);
			if ($stmt->rowCount()) {
				$sql = "UPDATE homeslide_tbl SET img_id = 2 WHERE img_id = 3";
				$stmt = $pdo->query($sql);
				if ($stmt->rowCount()) {
					return 'success';
				}
			}
		}
	}
	
	public function addSlidePhotos ( $imgTitle, $imgLink, $pdo ) {
		$sql = "SELECT img_id FROM homeslide_tbl";
		$stmt = $pdo->query($sql)->fetchColumn();
		$imgId = ( $stmt == 1 ) ? 2 : 1;
		$sql = "INSERT INTO homeslide_tbl VALUES (:imgId, :imgTitle, :imgLink)";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':imgId' => $imgId, ':imgTitle' => $imgTitle, ':imgLink' => $imgLink]);
		if ( $stmt->rowCount() ) {
			return 'success';
		} else {
			return 'error';
		}
	}

	public function updateSlidePhotos ( $imgId, $imgTitle, $imgLink, $pdo ) {
		$sql = "SELECT img_link FROM homeslide_tbl WHERE img_id = :imgId";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':imgId' => $imgId]);
		$currentImg = $stmt->fetchColumn();
		if ($currentImg) {
			$getcurrentImg = explode('/', urldecode($currentImg), 2);
			if (unlink($getcurrentImg[1])) {
			//if (!file_exists($imgURL[1])) {
				$sql = "UPDATE homeslide_tbl SET img_title = :imgTitle, img_link = :imgLink WHERE img_id = :imgId";
				$stmt = $pdo->prepare($sql);
				$stmt->execute([':imgTitle' => $imgTitle, ':imgLink' => $imgLink, ':imgId' => $imgId]);
				if ( $stmt->rowCount() ) {
					return 'success';
				} else {
					return 'error';
				}
			} else {
				$sql = "UPDATE homeslide_tbl SET img_title = :imgTitle, img_link = :imgLink WHERE img_id = :imgId";
				$stmt = $pdo->prepare($sql);
				$stmt->execute([':imgTitle' => $imgTitle, ':imgLink' => $imgLink, ':imgId' => $imgId]);
				if ( $stmt->rowCount() ) {
					return 'success';
				} else {
					return 'error';
				}
			}
		} else {
			$sql = "UPDATE homeslide_tbl SET img_title = :imgTitle, img_link = :imgLink WHERE img_id = :imgId";
			$stmt = $pdo->prepare($sql);
			$stmt->execute([':imgTitle' => $imgTitle, ':imgLink' => $imgLink, ':imgId' => $imgId]);
			if ( $stmt->rowCount() ) {
				return 'success';
			} else {
				return 'error';
			}
		}
	}
	
	public function deleteSlidePhoto ( $imgId, $pdo ) {
		$sql = "SELECT img_link FROM homeslide_tbl WHERE img_id = :imgId";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':imgId' => $imgId]);
		$imgLink = $stmt->fetchColumn();
		if ($imgLink) {
			$imgURL = explode('/', urldecode($imgLink), 2);
			if (unlink($imgURL[1])) {
				$sql = "DELETE FROM homeslide_tbl WHERE img_id = :imgId";
				$stmt = $pdo->prepare($sql);
				$stmt->execute([':imgId' => $imgId]);
				if ( $stmt->rowCount() ) {
					return 'success';
				} else {
					return 'error';
				}
			} else {
				echo 'error';
			}
		} else {
			echo 'error';
		}
	}

	/**
	 * Functions for Accessing Pages Display
	 */

	public function createUI ( $page, $userId, $user ) {
		$this->page = $page;
		$this->userId = $userId;
		$this->userType = get_class($user);
		$this->user = $user;
		self::callPage();
	}

	private function callPage () {
		//Defines the class which will be called for the page
		$confirmedAccess = self::confirmPageAccess();
		if ( $confirmedAccess == __CLASS__ ) {
			/**
			 * 	This will call obtainPage with the Logged In UserType Passed in.
			 *	This is to allow the Logged In User (especially for Admin User) distinguish
			 *	between a Personal Method call and another UserType method call for related methods 
			 */
			self::obtainPage( $this->userType );
		} elseif ( $confirmedAccess == new Customer($this->pdo) || $confirmedAccess == new Student($this->pdo) ) {
			self::obtainPage(get_class($confirmedAccess));
		} elseif ( $confirmedAccess == 'errorAccess') {
			staticFunc::errorPage( 'noAccess' );
		} else {
			$confirmedAccess->obtainPage( $confirmedAccess, $this->pdo, $this->page, $this->userType, $this->userId );
		}
	}
	
	private function confirmPageAccess () {
		//Pages Accessible to All Users 
		if ( $this->page == "index.php" || $this->page == "user.php" || $this->page == "logout.php" || $this->page == "contactus.php" || $this->page == "personal.php" || $this->page == "editpersonal.php" ) {
			return __CLASS__;
		} elseif ($this->page == "reminders.php" || $this->page == "setreminder.php" || $this->page == "editreminder.php" || $this->page == "personalreminders.php" ) {
			//Allowing Reminder Class Generate its own Views
			return new Reminder;
		} elseif ( $this->page == "messages.php" ) {
			return new MailMessages;
		//Pages Accessible to Particular Users or Combination of Users
		/**
		 * The Pages
		 *	$this->page == "adddesign.php" || $this->page == "addresults.php" || $this->page == "addtimetable.php" || $this->page == "addtraining.php" || $this->page == "assignments.php" || $this->page == "certificate.php" || $this->page == "createassignment.php" || $this->page == "createcustomer.php" || $this->page == "createorder.php" || $this->page == "createstaff.php" || $this->page == "createstudent.php" || $this->page == "customerdetails.php" || $this->page == "customers.php" || $this->page == "debts.php" || $this->page == "finance.php" || $this->page == "editassignment.php" || $this->page == "editcustomer.php" || $this->page == "editdesign.php" || $this->page == "editresults.php" || $this->page == "editstaff.php" || $this->page == "editstudent.php" || $this->page == "edittraining.php" || $this->page == "edittimetable.php" || $this->page == "orderdetails.php" || $this->page == "programmes.php" || $this->page == "programmereg.php" || $this->page == "staff.php" || $this->page == "staffdetails.php" || $this->page == "students.php" || $this->page == "studentdetails.php" || $this->page == "training.php" || $this->page == "viewsubmissions.php" 
		 */
		} else {
			return self::restrictedPages();
		}
	}
	
	private function restrictedPages () {
		if ( $this->userType == 'Admin' ) {
			//Pages that are only accessible to the Admin User
			if ( $this->page == "staff.php" || $this->page == "staffdetails.php" || $this->page == "createstaff.php" || $this->page == "editstaff.php" || $this->page == "settings.php" ) {
				return __CLASS__;
			} elseif ( $this->page == "customers.php" || $this->page == "customerorders.php" || $this->page == "createcustomer.php" || $this->page == "customerdetails.php" || $this->page == "editcustomer.php" ) {
				return new Customer($this->pdo);
			} elseif ( $this->page == "students.php" || $this->page == "createstudent.php" || $this->page == "editstudent.php" || $this->page == "studentdetails.php" ) {
				return new Student($this->pdo);
			} elseif ( $this->page == "orders.php" || $this->page == "orderdetails.php"  || $this->page == "createorder.php" ) {
				return new Order;
			} elseif ( $this->page == "designs.php" || $this->page == "designdetails.php" || $this->page == "adddesign.php" || $this->page == "editdesign.php" ) {
				return new Design;
			} elseif ( $this->page == "training.php" || $this->page == "addtraining.php" || $this->page == "edittraining.php" || $this->page == "programmes.php" ) { 
				return new Trainings;
			} elseif ( $this->page == "timetable.php" || $this->page == "viewtimetable.php" || $this->page == "addtimetable.php" || $this->page == "edittimetable.php" ) {
				return new Timetables;
			} elseif ( $this->page == "results.php" || $this->page == "addresults.php" || $this->page == "editresults.php" || $this->page == "certificate.php" ) {
				return new StudentResult;
			} elseif ( $this->page == "assignments.php" || $this->page == "editassignment.php" || $this->page == "createassignment.php" || $this->page == "viewsubmissions.php" ) {
				return new Assignment;
			} elseif ( $this->page == "reminders.php" ) {
				return new Reminder;
			} elseif ( $this->page == "finance.php" || $this->page == "feesrecords.php" ) {
				return new FinanceRecord;
			} elseif ( $this->page == "debts.php" ) {
				return new DebtRecord;
			} else {
				return 'errorAccess';
			}
		} elseif ( $this->userType == 'Customer' || $this->userType == 'CuStudent' ) {
			//Pages Accessible to Customer only (Special User: Custudent are combination of Students and Customers)
			if ( $this->page == "orders.php" || $this->page == "orderdetails.php" || $this->page == "createorder.php" ) {
				return new Order;
			} elseif ( $this->page == "designs.php" || $this->page == "designdetails.php" ) {
				return new Design;
			} elseif ( $this->page == "finance.php" ) {
				return new FinanceRecord;
			} else {
				return 'errorAccess';
			}
		} elseif ( $this->userType == 'Student' || $this->userType == 'CuStudent' ) {
			//Pages Accessible to only Students (Special User: Custudent are combination of Students and Customers)
			if ( $this->page == "programmereg.php" || $this->page == "training.php" || $this->page == "programmes.php" ) {
				return new Trainings;
			} elseif ( $this->page == "designs.php" || $this->page == "designdetails.php" ) {
				return new Design;
			} elseif ( $this->page == "assignments.php" || $this->page == "solveassignment.php" ) {
				return new Assignment;
			} elseif ( $this->page == "results.php" || $this->page == "certificate.php" ) {
				return new StudentResult;
			} elseif ( $this->page == "timetable.php" ) {
				return new Timetables;
			} elseif ( $this->page == "feesrecords.php" ) {
				return new FinanceRecord;
			} else {
				return 'errorAccess';
			}
		}
	}

	protected function obtainPage ( $targettedUserType ) {
		if ( $this->page == "logout.php" ) {
			parent::logout();
		} else {
			$pagelink = explode('.', $this->page, 2);
			//Using User Class for All Users Display
			if ( $this->userType == 'Admin' ) {
				if ( $pagelink[0] == 'staff' || $pagelink[0] == 'customers' || $pagelink[0] == 'students' ) {
					self::viewusersUI ( $targettedUserType );
				} elseif ( $pagelink[0] == 'editstaff' || $pagelink[0] == 'editcustomer' || $pagelink[0] == 'editstudent' ) {
					self::edituserUI ( $targettedUserType );
				} elseif ( $pagelink[0] == 'createstaff' || $pagelink[0] == 'createcustomer' || $pagelink[0] == 'createstudent' ) {
					self::createuserUI ( $targettedUserType );
				} elseif ( $pagelink[0] == 'staffdetails' || $pagelink[0] == 'customerdetails' || $pagelink[0] == 'studentdetails' ) {
					self::userdetails ( $targettedUserType );				
				} else {
					if (method_exists($this->userType, $pagelink[0].'UI')) {
						//Can Use any of the three variations of call_user_func
						//call_user_func( $this->userType.'::'.$pagelink[0].'UI');
						//call_user_func( 'self'.'::'.$pagelink[0].'UI');
						/** Using @ Operator to suppress Strict Standards Error 
						 * 	caused by calling the child function in an possible
						 *	statical way.
						 * 	Error Text: Strict Standards: call_user_func() expects parameter 
						 *	1 to be a valid callback, non-static method Assignment::assignmentsUI()
						 *	should not be called statically, assuming $this from compatible context 
						 *	Assignment in C:\wamp\www\mcSueApp\class\Items.php on line 78
						 */
						@call_user_func( array ( $this->userType, $pagelink[0].'UI' ));
					} else {
						//Error Page; Page not available
						staticFunc::errorPage( 'PageError' );
					}
				}
			} else {
				if (method_exists($this->userType, $pagelink[0].'UI')) {
					//Can Use any of the three variations of call_user_func
					//call_user_func( $this->userType.'::'.$pagelink[0].'UI');
					//call_user_func( 'self'.'::'.$pagelink[0].'UI');
					/** Using @ Operator to suppress Strict Standards Error 
					 * 	caused by calling the child function in an possible
					 *	statical way.
					 * 	Error Text: Strict Standards: call_user_func() expects parameter 
					 *	1 to be a valid callback, non-static method Assignment::assignmentsUI()
					 *	should not be called statically, assuming $this from compatible context 
					 *	Assignment in C:\wamp\www\mcSueApp\class\Items.php on line 78
					 */
					@call_user_func( array ( $this->userType, $pagelink[0].'UI' ));
				} else {
					//Error Page; Page not available
					staticFunc::errorPage( 'PageError' );
				}
			}
		}
	}


	/**
	*	VIEWS PAGE
	*/
	
	protected function settingsUI () {
?>
		<div class="row"><button class="btn btn-danger float-btn-right" data-toggle="modal" data-target="#myModalDefault" title="Click To Reset Settings To Default">Restore Default</button></div>
		<h3 class="text-center text-info" id="top"><strong>APPLICATION PREFERENCES</strong></h3>
		<hr class="hr-divide">
		<p class="text-info text-center text-sm" id="appRow">Change how the application displays certain features</p>
		<form class="form-horizontal" enctype="multipart/form-data" method="post" action="<?php echo basename($_SERVER['PHP_SELF']); ?>">
			<fieldset>
<?php
		if ( isset($_GET['default']) ) {
			if ( $_GET['default'] == 'success' ) {
				$type = 'success';
				$msg = '<b>Default Settings Have Been Successfully Restored</b>';
				staticFunc::alertDisplay($type, $msg);
			} else {
				$type = 'error';
				$msg = '<b>Restoring Default Setting Failed</b>';
				staticFunc::alertDisplay($type, $msg);
			}
		} elseif ( isset($_GET['update']) ) {
			if ( $_GET['update'] == 'success' ) {
				$type = 'success';
				$msg = '<b>Application Settings Were SuccessFully Updated</b>';
				staticFunc::alertDisplay($type, $msg);
			} elseif ( substr($_GET['update'], 0, 6) == 'sphoto' ) {
				if ( substr($_GET['update'], 6, 1) == 1 ) {
					$valSlide = 1;
				} elseif ( substr($_GET['update'], 6, 1) == 2 ) {
					$valSlide = 2;
				} else {
					$valSlide = '';
				}
				$type = 'success';
				$msg = "<b>Slide Photo $valSlide Was Successfully Deleted</b>";
				staticFunc::alertDisplay($type, $msg);
			} elseif ( substr($_GET['update'], 0, 6) == 'ephoto' ) {
				if ( substr($_GET['update'], 6, 1) == 1 ) {
					$valSlide = 1;
				} elseif ( substr($_GET['update'], 6, 1) == 2 ) {
					$valSlide = 2;
				} else {
					$valSlide = '';
				}
				$type = 'error';
				$msg = "<b>There Was An Error Deleting Slide Photo $valSlide</b>";
				staticFunc::alertDisplay($type, $msg, 1);
			} else {
				$type = 'error';
				$msg = '<b>There Was An Error Updating The Application Settings</b>';
				staticFunc::alertDisplay($type, $msg, 1);
			}
		}	
		if ( isset($_GET['slide']) ) {
			$type = 'error';
			if ( $_GET['slide'] == 'error' ) {
				$msg = "<b>The Slide Photos Could Not Be Updated</b>";
			} elseif ( $_GET['slide'] == 'error1' ) {
				$msg = "<b>The Slide Photo 1 Could Not Be Updated</b>";
			} elseif ( $_GET['slide'] == 'error2' ) {
				$msg = "<b>The Slide Photo 2 Could Not Be Updated</b>";
			}
			staticFunc::alertDisplay($type, $msg, 1);
		}
?>
			<div class="row">
				<div class="col-md-6">
					<span class="setting-header text-info">View Orders</span>
					<div class="toggle-switch">
						<div class="div-toggle">Current Orders</div>
						<input id="cmn-toggle-1" class="cmn-toggle cmn-toggle-round" type="checkbox" name='view_orders' <?php if (isset($_POST['view_orders']) || staticFunc::$pref[0]['view_orders'] == 'all') echo 'checked'; ?>>
						<label for="cmn-toggle-1"></label>
						<div class="div-toggle">All Orders</div>
					</div>
					<p class="help-block">Toggle selection to choose your desired option for viewing Orders;<br /><b>Current Orders <i>(Default)</i>: </b>displays only orders from the present day onwards<br /><b>All Orders: </b>Displays all orders ever received</p>
					<hr class="hr-seperator">
					<span class="setting-header text-info">Set Reminders</span>
					<div class="toggle-switch">
						<div class="div-toggle">Auto</div>
						<input id="cmn-toggle-2" class="cmn-toggle cmn-toggle-round" type="checkbox" name='reminders' <?php if (isset($_POST['reminders']) || staticFunc::$pref[0]['reminders'] == 'user') echo 'checked'; ?>>
						<label for="cmn-toggle-2"></label>
						<div class="div-toggle">User</div>
					</div>
					<p class="help-block">Toggle selection to choose desired option for setting Reminders;<br /><b>Auto <i>(Default)</i>: </b>Reminders are set as records are made<br /><b>User: </b>You choose to or not set reminders according to records</p>
					<hr class="hr-seperator">
					<span class="setting-header text-info">New Designs Access</span>
					<div class="toggle-switch">
						<div class="div-toggle">All</div>
						<input id="cmn-toggle-3" class="cmn-toggle cmn-toggle-round" type="checkbox" name='designs_access' <?php if (isset($_POST['designs_access']) || staticFunc::$pref[0]['designs_access'] == 'admin') echo 'checked'; ?>>
						<label for="cmn-toggle-3"></label>
						<div class="div-toggle">Admin Only</div>
					</div>
					<p class="help-block">Toggle selection to choose options for displaying designs when Added;<br /><b>All <i>(Default)</i>: </b>New Designs are immediately accessible by all users<br /><b>Admin Only: </b>New Designs are accessible to Admin Only <i>(until set to All)</i></p>
					<hr class="hr-seperator">
					<span class="setting-header text-info">Training Programme Payment Schedule</span>
					<div class="toggle-switch">
						<div class="div-toggle">Full Payment</div>
						<input id="cmn-toggle-4" class="cmn-toggle cmn-toggle-round" type="checkbox" name='payment_schedule' <?php if (isset($_POST['payment_schedule']) || staticFunc::$pref[0]['payment_schedule'] == 'instalment') echo 'checked'; ?>>
						<label for="cmn-toggle-4"></label>
						<div class="div-toggle">Instalment Payment</div>
					</div>
					<p class="help-block">Toggle selection to choose payment schedule for training programme;<br /><b>Full Payment <i>(Default)</i>: </b>Students must make full payment at beginning of programme<br /><b>Instalment Payment: </b>Students can pay fees in instalments</p>
					<div id="confirmPaymentSchedule" class="initHidden">
						<span class="setting-header text-info">Select Number of Instalments</span>
						<select name="payment_instalment" title="Select Payment Instalment">
							<option value='0' hidden>Number of Instalments</option>
							<option value='1' <?php if (isset($_POST['payment_instalment']) && $_POST['payment_instalment'] == 1 || staticFunc::$pref[0]['payment_instalment'] == 1) { echo "selected='selected'"; } ?>>1 Instalment</option>
							<option value='2' <?php if (isset($_POST['payment_instalment']) && $_POST['payment_instalment'] == 2 || staticFunc::$pref[0]['payment_instalment'] == 2) { echo "selected='selected'"; } ?>>2 Instalments</option>
						</select>
						<p class="help-block">Toggle selection to choose payment schedule for training programme;<br /><b>Full Payment <i>(Default)</i>: </b>Students must make full payment at beginning of programme<br /><b>Instalment Payment: </b>Students can pay fees in instalments</p>
					</div>
				</div>
				<div class="col-md-6">
					<p class="setting-header text-info text-center">Update Photos for Login Page Slideshow</p>
<?php	$getSlidePhotos = staticFunc::getSlidePhotos( $this->pdo );
	if ( $getSlidePhotos ) {
		$count = count($getSlidePhotos);
		$disableNewPhoto = ( $count >= 2 ) ? 'disabled title="You Can Only Add Two Photos to the SlideShow"' : 'title="Select New Photo To Upload"';
		$photoHint = ( $count >= 2 ) ? '<div class="text-danger margin-small">Delete a Photo above for space to upload a new one</div>' : '';
		$hideTitle = ( $count >= 2 ) ? 'hidden' : 'text';
		$new = 0;
		foreach ( $getSlidePhotos as $key => $value ) {
			$new++;
?>
				<div class="row">
					<div class="col-sm-4">
					<img src="<?php 
						$imgURL = explode('/', urldecode($value['img_link']), 2);
						echo $imgURL[1]; ?>" alt="<?php echo $value['img_title']; ?>" class="img-responsive photo_view" />
					</div>
					<div class="col-sm-8 pad-down change_details" id="photoDetails<?php echo $new; ?>">
						<span class="text-info replace<?php echo $new; ?>">Change:&nbsp;</span><input type="file" name="updatePhoto[]" id="changeSlide<?php echo $new; ?>" class="file_upload" title="Change the Photo" /><br /><div id="nameInput<?php echo $new; ?>"></div>
						<span class="text-info replace<?php echo $new; ?>">Change Order for Photo: &nbsp;</span>
						<select name="selectOrder<?php echo $new; ?>">
							<?php
								for ( $i = 1; $i <= $count; $i++ ) {
									if ( $value['img_id'] == $i ) {
										echo "<option value='$i' selected='selected'>$i</option>";
									} else {
										echo "<option value='$i'>$i</option>";	
									}
								}
							?>
						</select><br />
						<button class="btn btn-danger replace<?php echo $new; ?> modalUserSettings" value="<?php echo $imgURL[1]; ?>" data-toggle="modal" id="deleteSlidePhoto<?php echo $new; ?>" data-target="#myModalPhotoDelete" title="Click To Delete Current Slide Photo">Delete This Photo</button>
						<span class="text-danger blink">Careful!</span>
					</div>
				</div>
				<hr class="hr-padding">
<?php		}
			echo "<div class='centralize'><span class='text-info replace'>Add Another Photo: &nbsp;</span><input type='file' class='file_upload_2' name='newPhoto' $disableNewPhoto /><div class='setCenter'><input type='$hideTitle' name='newSlidePhoto' placeholder='Set Image Title' /><span class='help-block'>$photoHint</span></div></div>";
	}
?>
				<!-- Modal -->
				<div class="modal fade" id="myModalPhotoDelete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">		   
					<div class="modal-dialog">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span>&times;</span></button>
								<h3 class="modal-title text-center to-close" id="myModalLabel">
								</h3>
							</div>
							<div class="modal-body">
								<h4 class="text-center to-close" id="h3-modal-info"></h4>
								<img id="availablePhoto" class="img-responsive img-delete" />
								<span class="text-center to-close" id="h3-modal-addedinfo"></span>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-info same-width-sm" data-dismiss="modal">No</button>
								<button type="button" class="btn btn-danger same-width-sm" id="confirmDeleteSlidePhoto" data-dismiss="modal">Yes! Delete</button>
							</div>
						</div><!-- /.modal-content -->
					</div><!-- /.modal-dialog -->
				</div><!-- /.modal -->
				<hr class="hr-seperator">
				<span class="setting-header text-info">Set Number of Items to Display in Table Views: &nbsp;</span><br />
				<select name="table_limit" title="Select Limit Value" class="item-select">
					<?php $total = range(1,20); 
						foreach ( $total as $tableLimitValue ) {
							$items = ($tableLimitValue == 1) ? 'Item' : 'Items';
							if ( staticFunc::$pref[0]['table_limit'] == $tableLimitValue ) {
								echo "<option value='$tableLimitValue' selected='selected'>$tableLimitValue $items</option>";
							} else {
								echo "<option value='$tableLimitValue'>$tableLimitValue $items</option>";
							}
						}
					?>
				</select>
				<p class="help-block">Sets total number of items that can appear at once in a table view throughout application</p>
				<hr class="hr-seperator">
				<span class="setting-header text-info">Set How Long to Remain Logged In: &nbsp;</span><br />
				<select name="remain_logged_in" title="Select Limit Value" class="item-select">
					<?php $total = range(1,30);
						foreach ( $total as $tableLimitValue ) {
							$minutes = ($tableLimitValue == 1) ? 'Minute' : 'Minutes';
							if ( staticFunc::$pref[0]['remain_logged_in'] == $tableLimitValue ) {
								echo "<option value='$tableLimitValue' selected='selected'>$tableLimitValue $minutes</option>";
							} else {
								echo "<option value='$tableLimitValue'>$tableLimitValue $minutes</option>";
							}
						}
					?>
				</select>
				<p class="help-block">Sets how many minutes you stay logged in when inactive; Afterwards, you are logged out and required to log in again</p>
			</div>
		</div>
		<div class="row index-row">
			<input type="submit" name="userSettingsSubmit" class="btn btn-info add-item-btn" value="Save Changes" id="userSettingsSubmit" />
			<input type="hidden" name="saveUserSettings" />
			<input type="hidden" name="deleteSlidePhotoSubmit1" id="inputdeleteSlidePhoto1" />
			<input type="hidden" name="deleteSlidePhotoSubmit2" id="inputdeleteSlidePhoto2" />
		</div>
		</fieldset>
		</form>
		<!-- Modal -->
		<div class="modal fade" id="myModalDefault" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">		   
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span>&times;</span></button>
						<h3 class="modal-title text-center text-info bold" id="myModalLabel">RESTORE DEFAULT SETTINGS</h3>
					</div>
					<form method="post" id="modalRestoreDefault">
						<div class="modal-body">
							<img class="img-responsive img-delete" id="img01"/>
							<h3 class="text-center to-close">Are You Sure You Want To Restore Default Settings?</h3>
							<span class="text-center to-close">All Previous Customizations Will Be Reversed</span>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-info same-width" data-dismiss="modal">No</button>
							<button type="button" class="btn btn-danger same-width" name="restoreDefaultSettings" id="restoreDefaultSettings" data-dismiss="modal">Yes! Restore Default</button>
						</div>
						<input type="hidden" name="restoreDefaultSubmit" value="1"/>
						<input type="hidden" name="restoreDefaultForm" value="1"/>
					</form>
				</div><!-- /.modal-content -->
			</div><!-- /.modal-dialog -->
		</div><!-- /.modal -->
<?php		
	}

	/**
	 * 	method to get the View for All User; Accessible to Admin Only
	 *	@param $intendedUserType sets the types of user to be viewed 
	 */
	protected function viewusersUI ( $intendedUserType ) {
		if ( $intendedUserType == 'Admin' ) {
			$setIntendedUser = 'Staff';
			$setIntendedUSER = 'STAFF';
			$setIntendeduser = 'staff';
		} elseif ( $intendedUserType == 'Customer' ) {
			$setIntendedUser = 'Customer';
			$setIntendedUSER = 'CUSTOMER';
			$setIntendeduser = 'customer';
		} elseif ( $intendedUserType == 'Student' ) {
			$setIntendedUser = 'Student';
			$setIntendedUSER = 'STUDENT';
			$setIntendeduser = 'student';
		}
		$custStud = ($setIntendedUser !== 'Admin') ? 'S' : '';
		$allUsers = self::allUserDetails ( $intendedUserType );
		if ( !is_array($allUsers) || empty($allUsers) ) {
			echo '<div class="row add-item-row"><button class="btn btn-info pad" onclick="window.location.href=\'index.php\'"><b>Back To Home</b></button>';
			echo "<button class='btn btn-info pad' onclick='window.location.href=\"create$setIntendeduser.php\"'><b>Create New $setIntendedUser Account</b></button></div>";
			$type = 'error';
			$msg = "There are no {$setIntendedUser}s available to view.";
			//Display Alert;
			staticFunc::alertDisplay ( $type, $msg );
		} else {
			echo "<div class='row add-item-row'><button class='btn btn-info btn-add-item' onclick='window.location.href=\"create$setIntendeduser.php\"'><b>Create New $setIntendedUser Account</b></button></div>";
?>
			<div class="row">
			<div class="panel panel-info panel-order panel-student">
				<div class="panel-heading"><span class="text-center"><strong><?php echo $setIntendedUSER.$custStud; ?></strong></span></div>
				<table class="table table-striped table-hover table-responsive table-center">
				<tr>
					<th><?php echo $setIntendedUser; ?> Name</th>
					<th>Address</th>
					<th>Phone</th>
					<th>Email</th>
					<th>View <?php echo $setIntendedUser; ?>'s Photo</th>
					<th><?php echo $setIntendedUser; ?>'s Details</th>
				</tr>
<?php
			foreach ( $allUsers as $key => $value ) {
?>
					<tr>
						<td><?php echo $value['names']; ?></td>
						<td><?php echo $value['address']; ?></td>
						<td><?php echo $value['phone_numbers']; ?></td>
						<td><?php echo $value['email']; ?></td>
						<td><button id="<?php echo urldecode($value['photo']); ?>" value="<?php echo $value['names']; ?>" class="btn btn-link myImg" title="View <?php echo $value['names']; ?>'s Photo" data-toggle="modal" data-target="#myModalOrder">View Photo</button></td>
						<td><button class="btn btn-link" onclick="window.location.href='<?php echo $setIntendeduser; ?>details.php?<?php echo $setIntendeduser; ?>=<?php echo staticFunc::maskURLParam($value['user_id'])?>'">Full Details</button></td>
					</tr>
<?php		}
			echo '</table></div></div>';
			Paginate::displayPageLink();
?>
		<!-- Modal VIEW PHOTO -->
		<div class = "modal fade" id = "myModalOrder" tabindex = "-1" role = "dialog" aria-labelledby = "myModalLabel" aria-hidden = "true">
			<div class = "modal-dialog modal-dialog-order">
				<div class = "modal-content modal-transparent">
					<div class = "modal-header modal-header-order">
						<button type = "button" class = "close" data-dismiss = "modal" aria-hidden = "true"><span>&times;</span></button>
						<h4 class = "modal-title modal-title-order" id = "myModalLabel">
						</h4>
					</div>
					<div class = "modal-body modal-body-order">
						<img class="img-responsive" id="img01" width="500"/>
						<span class="to-close">Press <kbd>ESC</kbd> or click outside image to close</span>
					</div>
					<div class = "modal-footer modal-footer-order">
						<button type = "button" id="modal-close" class = "btn btn-default btn-order" data-dismiss = "modal">Close</button>
						<button type = "button" id="modal-save" class = "btn btn-primary btn-order">Submit changes</button>
					</div>
				</div><!-- /.modal-content -->
			</div><!-- /.modal-dialog -->
		</div><!-- /.modal -->
<?php		
		}
	}

	/**
	 * 	method userdetails generate the details for the user to being accessed by Admin class
	 *	@param $intendedUserType sets the User Type of the user being accessed
	 */
	protected function userdetails ( $intendedUserType ) {
		if ( $intendedUserType == 'Admin' ) {
			$setIntendedUser = 'Staff';
			$setIntendedUSER = 'STAFF';
			$setIntendeduser = 'staff';
			$setIntendeduserId = 'staffId';
		} elseif ( $intendedUserType == 'Customer' ) {
			$setIntendedUser = 'Customer';
			$setIntendedUSER = 'CUSTOMER';
			$setIntendeduser = 'customer';
			$setIntendeduserId = 'customerId';
		} elseif ( $intendedUserType == 'Student' ) {
			$setIntendedUser = 'Student';
			$setIntendedUSER = 'STUDENT';
			$setIntendeduser = 'student';
			$setIntendeduserId = 'studentId';
		}
		if (!isset($_GET["$setIntendeduser"])) {
			if ( !isset($_POST["delete{$setIntendedUser}Form"]) ) {
				//Erroneous Access
				staticFunc::errorPage( 'error' );
			}
		} else {
			$setIntendeduserId = staticFunc::unmaskURLParam($_GET["$setIntendeduser"]);
			//Determine if this is Admin intended, and remove 's' from link for staff.php 
			$custStud = ($setIntendedUser !== 'Admin') ? 's' : '';
			echo "<div class='row add-item-row'><button class='btn btn-info btn-add-item' onclick='window.location.href=\"{$setIntendeduser}{$custStud}.php\"'><strong>Back To All $setIntendedUser</strong></button></div>";
			$userDetails = self::getUserDetails ( $setIntendedUser, $setIntendeduserId );
			if ( !is_array($userDetails) || empty($userDetails) ) {
				$type = 'error';
				$msg = "This $setIntendeduser's details have not been set previously.";
				$link = "{$setIntendeduser}s.php";
				$linkValue = "Back To All {$setIntendedUser}s";
				staticFunc::alertDisplay ( $type, $msg, $link, $linkValue );
			} else {
				$_SESSION['id'] = $setIntendeduserId;	
				if (isset($_GET['update'])) {
					if ( $_GET['update'] == 'success' ) {
						$type = 'success';
						$msg = "The {$setIntendedUser}'s Details Have Been Updated";
						staticFunc::alertDisplay( $type, $msg );
					} elseif ($_GET['update'] == 'failed' ) {
						$type = 'info';
						$msg = "No Information Was Updated!";
						staticFunc::alertDisplay( $type, $msg );
					}
				}
				foreach ( $userDetails as $key => $value ) {
?>
				<div class="col-sm-offset-2 col-sm-8 text-center" id="deleteAlert"></div>
					
				<div class="row">
					<div class="panel panel-info panel-item-details">
						<div class="panel-heading"><?php echo 'Details of <br /><span><strong>'. $value['names']; ?></strong></span></div>
						<table class="table table-striped table-hover table-responsive table-item-details">
							<tr>
								<td><strong>Photo</strong></td>
								<td><img src="<?php echo urldecode($value['photo']); ?>" alt="<?php $value['names']; ?>"</td>
							</tr>
							<tr>
								<td><strong>Name</strong></td>
								<td><?php echo $value['names']; ?></td>
							</tr>
							<tr>
								<td><strong>Gender</strong></td>
								<td><?php echo $value['gender']; ?></td>
							</tr>
							<tr>
								<td><strong>Address</strong></td>
								<td><?php echo $value['address']; ?></td>
							</tr>
							<tr>
								<td><strong>Phone Numbers</strong></td>
								<td><?php echo rtrim($value['phone_numbers'], ' ,'); ?></td>
							</tr>
							<tr>
								<td><strong>Email Address</strong></td>
								<td><?php echo $value['email']; ?></td>
							</tr>
							<tr>
								<td><strong><?php echo $setIntendedUser; ?> Since</strong></td>
								<td><?php echo $value['user_since']; ?></td>
							</tr>
						</table>
					</div>
<?php			}
				echo '</div>';
				if ( $this->userType === 'Admin' ) {
?>
				<div class="row">
					<div class="delete-row">
						<button class="btn btn-info" onclick="window.location.href='edit<?php echo $setIntendeduser.'.php?'.$setIntendeduser.'='. staticFunc::maskURLParam($value['user_id']); ?>'">Edit <?php echo $setIntendedUser; ?></button>
						<button class="btn btn-danger myImg" id="<?php echo urldecode($value['photo']); ?>" value="<?php echo $value['names']; ?>" data-toggle="modal" data-target="#myModalDelete">Delete <?php echo $setIntendedUser; ?></button>
					</div>
				</div>
<?php				
				}
?>
		<!-- Modal -->
		<div class="modal fade" id="myModalDelete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">		   
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span>&times;</span></button>
						<h3 class="modal-title text-center to-close" id="myModalLabel">
						</h3>
					</div>
					<form method="post" id="modalForm">
						<div class="modal-body">
							<img class="img-responsive img-delete" id="img01"/>
							<h3 class="text-center to-close">Are You Sure You Want To Delete This <?php echo $setIntendedUser; ?>?</h3>
							<span class="text-center to-close">This action cannot be undone!</span>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-info btn-order" data-dismiss="modal">No</button>
							<button type="button" class="btn btn-danger btn-order" name="delete<?php echo $setIntendedUser; ?>Submit" id="delete<?php echo $setIntendedUser; ?>Submit" data-dismiss="modal">Yes! Delete</button>
						</div>
						<input type="hidden" name="delete<?php echo $setIntendedUser; ?>Form" id="delete<?php echo $setIntendedUser; ?>Form" value="<?php echo staticFunc::maskURLParam($value['user_id']); ?>" />
						<input type="hidden" name="delete<?php echo $setIntendedUser; ?>Confirm" id="delete<?php echo $setIntendedUser; ?>Confirm" value="<?php echo staticFunc::maskURLParam($value['names']); ?>" />
					</form>
				</div><!-- /.modal-content -->
			</div><!-- /.modal-dialog -->
		</div><!-- /.modal -->
				
<?php
			}
		}
	}
	
	/**
	 *	method createuserUI creates the interface for creating a particular user by the Admin Class
	 *	@param $intendeduserType sets the User Type of the user being accessed 
	 */
	protected function createuserUI ( $intendedUserType ) {
		if ( $intendedUserType == 'Admin' ) {
			$setIntendedUser = 'Staff';
			$setIntendedUSER = 'STAFF';
			$setIntendeduser = 'staff';
			$setIntendeduserId = 'staffId';
			$setSubmitBtn = 'createAdminSubmit';
			$setSubmitHidden = 'createAdminForm';
		} elseif ( $intendedUserType == 'Customer' ) {
			$setIntendedUser = 'Customer';
			$setIntendedUSER = 'CUSTOMER';
			$setIntendeduser = 'customer';
			$setIntendeduserId = 'customerId';
			$setSubmitBtn = 'createCustomerSubmit';
			$setSubmitHidden = 'createCustomerForm';
		} elseif ( $intendedUserType == 'Student' ) {
			$setIntendedUser = 'Student';
			$setIntendedUSER = 'STUDENT';
			$setIntendeduser = 'student';
			$setIntendeduserId = 'studentId';
			$setSubmitBtn = 'createStudentSubmit';
			$setSubmitHidden = 'createStudentForm';
		}
		$addedS = ($intendedUserType == 'Admin') ? '' : 's';
		echo "<div class='row add-item-row'><button class='btn btn-info btn-add-item' onclick=\"window.location.href='". $setIntendeduser . $addedS .".php'\"><strong>Back To $setIntendedUser</strong></button></div>";
?>
		<div class="col-md-12">
			<form class="form-horizontal form-add-info" id="add-item-form" enctype="multipart/form-data" method="post" action="<?php echo basename($_SERVER['PHP_SELF']); ?>">
				<fieldset>
				<legend class="text-info text-center">Create <?php echo $setIntendedUser; ?> Account</legend>
					<div class="row">
						<h4 class="text-center text-info">Login Information</h4>
						<hr class="hr-class">
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['username'])) { echo 'has-error'; } ?>">
							<label for="username">Username</label>
							<input type="text" id="username" name="username" maxlength="30" class="form-control" value="<?php if (isset($_POST['username'])) { echo $_POST['username']; } ?>" placeholder="Enter Username" required/>
							<p class="help-block">Username for User Login</p>
						</div>
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['password'])) { echo 'has-error'; } ?>">
							<label for="password">Password</label>
							<input type="password" id="password" name="password" maxlength="30" class="form-control" placeholder="Enter Password" required/>
							<p class="help-block">Enter Password</p>
						</div>
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['re-password'])) { echo 'has-error'; } ?>">
							<label for="re-password">Re-enter Password</label>
							<input type="password" id="re-password" name="re-password" maxlength="30" class="form-control" placeholder="Enter Password Again" required/>
							<p class="help-block">Re-enter Password For Confirmation</p>
						</div>
					</div>
					<h4 class="text-center text-info">Personal Information</h4>
					<hr class="hr-class">
					<div class="row">
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['surname'])) { echo 'has-error'; } ?>">
							<label for="surname">Surname</label>
							<input type="text" id="surname" name="surname" maxlength="30" class="form-control" value="<?php if (isset($_POST['surname'])) { echo $_POST['surname']; } ?>" placeholder="Enter Surname" required/>
							<p class="help-block">Cannot be empty</p>
						</div>
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['firstname'])) { echo 'has-error'; } ?>">
							<label for="surname">First Name</label>
							<input type="text" id="firstname" name="firstname" maxlength="30" class="form-control" value="<?php if (isset($_POST['firstname'])) { echo $_POST['firstname']; } ?>" placeholder="Enter First Name" required/>
							<p class="help-block">Cannot be empty</p>
						</div>
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['othername'])) { echo 'has-error'; } ?>">
							<label for="othername">Other Name</label>
							<input type="text" id="othername" name="othername" maxlength="30" class="form-control" value="<?php if (isset($_POST['othername'])) { echo $_POST['othername']; } ?>" placeholder="Enter Other Name" />
							<p class="help-block">Cannot be empty</p>
						</div>
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['street'])) { echo 'has-error'; } ?>">
							<label for="street">Street</label>
							<input type="text" id="street" name="street" maxlength="30" class="form-control" value="<?php if (isset($_POST['street'])) { echo $_POST['street']; } ?>" placeholder="Enter Street Address" required/>
							<p class="help-block">Cannot be empty</p>
						</div>
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['city'])) { echo 'has-error'; } ?>">
							<label for="city">City</label>
							<input type="text" id="city" name="city" maxlength="30" class="form-control" value="<?php if (isset($_POST['city'])) { echo $_POST['city']; } ?>" placeholder="Enter City of Residence" required/>
							<p class="help-block">Cannot be empty</p>
						</div>
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['state'])) { echo 'has-error'; } ?>">
							<label for="state">State</label>
							<input type="text" id="state" name="state" maxlength="30" class="form-control" value="<?php if (isset($_POST['state'])) { echo $_POST['state']; } ?>" placeholder="Enter State of Residence" required/>
							<p class="help-block">Cannot be empty</p>
						</div>
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['country'])) { echo 'has-error'; } ?>">
							<label for="country">Country</label>
							<input type="text" id="country" name="country" maxlength="30" class="form-control" value="<?php if (isset($_POST['country'])) { echo $_POST['country']; } ?>" placeholder="Enter Country of Residence" required/>
							<p class="help-block">Cannot be empty</p>
						</div>
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['phone'])) { echo 'has-error'; } ?>">
							<label for="phone">Phone Number</label>
							<input type="text" id="phone" name="phone" maxlength="15" class="form-control" value="<?php if (isset($_POST['phone'])) { echo $_POST['phone']; } ?>" placeholder="Enter Official Phone Number" required/>
							<p class="help-block">Cannot be empty</p>
						</div>
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['phone_alt'])) { echo 'has-error'; } ?>">
							<label for="othername">Alternative Phone</label>
							<input type="text" id="phone_alt" name="phone_alt" maxlength="15" class="form-control" value="<?php if (isset($_POST['phoneAlt'])) { echo $_POST['phoneAlt']; } ?>" placeholder="Enter Alternative Phone Number" />
							<p class="help-block">An Alternative Phone Number</p>
						</div>
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['gender'])) { echo 'has-error'; } ?>">
							<label for="gender">Gender</label><br />
							<select name="gender" class="item-select form-inline" id="edit-gender">
								<option value="0" hidden> - Select Gender - </option>
								<option value="1" <?php if (isset($_POST['gender']) && $_POST['gender'] == 1) { echo 'selected'; }?> >Male</option>
								<option value="2" <?php if (isset($_POST['gender']) && $_POST['gender'] == 2) { echo 'selected'; }?> >Female</option>
							</select>
							<p class="help-block">Select the gender</p>
						</div>
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['email'])) { echo 'has-error'; } ?>">
							<label for="email">Email Address</label>
							<input type="text" class="form-control" id="email" maxlength="40" name="email" value="<?php if (isset($_POST['email'])) { echo $_POST['email']; } ?>" placeholder="Enter Email Address" required/>
							<p class="help-block">Email Address should not be more than 40 characters</p>
						</div>
						<div class="col-md-6 <?php if (isset(staticFunc::$formInput['file'])) { echo 'has-error-file'; } ?>">
							<label for="<?php echo $setIntendeduser; ?>Photo"><?php echo $setIntendedUser; ?>'s Photo</label>
							<input type="file" id="<?php echo $setIntendeduser; ?>Photo" name="file1"/>
							<div class="checkbox">
								<label>
								<input type="checkbox" name="addPhotoLater">Select To Add Photo Later
								</label>
							</div>
							<p class="help-block">Image Format should be in .jpg, .gif, .png formats</p>
						</div>
					</div>
					<div class="row">
						<p class="text-info text-center text-lg bold">Account Recovery Details</p>
						<p class="text-center">Please provide the following details to enable you regain access to your account in case you forget your password and are unable to login</p>
						<div class="col-md-6 <?php if (isset(staticFunc::$formInput['birthday'])) { echo 'has-error'; } ?>">
							<label for="birthday">Select Birthday</label>
							<input type="date" id="birthday" name="birthday" maxlength="30" class="form-control" value="<?php if (isset($_POST['birthday'])) { echo $_POST['birthday']; } ?>" placeholder="YYYY-MM-DD" required/>
							<p class="help-block">Select Birthday; Format: YYYY-MM-DD</p>
						</div>
						<div class="col-md-6 <?php if (isset(staticFunc::$formInput['question'])) { echo 'has-error'; } ?>">
							<label for="question">Set Security Question</label><br />
							<input type="text" id="question" name="question" maxlength="30" class="form-control" value="<?php if (isset($_POST['question'])) { echo $_POST['question']; } ?>" placeholder="Set Your Security Question" required />
							<p class="help-block">Use A Question That You Can Easily Remember The Answer In The Long Term But Is Not Easily Guessed By Someone Else</p>
						</div>
						<div class="col-md-6 <?php if (isset(staticFunc::$formInput['answer'])) { echo 'has-error'; } ?>">
							<label for="answer">Set Answer To Security Question</label><br />
							<input type="text" id="answer" name="answer" maxlength="30" class="form-control" value="<?php if (isset($_POST['answer'])) { echo $_POST['answer']; } ?>" placeholder="Answer Your Security Question" required />
							<p class="help-block">Set The Answer To Your Security Question</p>
						</div>
					</div>
					<div class="row">
						<div class="form-group">
							<input type="submit" id="<?php echo $setSubmitBtn; ?>" name="create<?php echo $setSubmitBtn; ?>Submit" class="btn btn-info save-btn" value="Create <?php echo $setIntendedUser; ?>"/>
						</div>
						<input type="hidden" name="<?php echo $setSubmitHidden; ?>">
					</div>
				</fieldset>
			</form>
		</div>		
<?php
	}

	/**
	 * 	method editUserUI creates the Interface for editing a particular user by the Admin Class
	 *	@param $intendedUserType sets the User Type of the User being accessed
	 */
	protected function edituserUI ( $intendedUserType ) {
		if ( $intendedUserType == 'Admin' ) {
			$setIntendedUser = 'Staff';
			$setIntendedUSER = 'STAFF';
			$setIntendeduser = 'staff';
			$setIntendeduserId = 'staffId';
			$setSubmitBtn = 'editAdminSubmit';
			$setSubmitHidden = 'editAdminForm';
		} elseif ( $intendedUserType == 'Customer' ) {
			$setIntendedUser = 'Customer';
			$setIntendedUSER = 'CUSTOMER';
			$setIntendeduser = 'customer';
			$setIntendeduserId = 'customerId';
			$setSubmitBtn = 'editCustomerSubmit';
			$setSubmitHidden = 'editCustomerForm';
		} elseif ( $intendedUserType == 'Student' ) {
			$setIntendedUser = 'Student';
			$setIntendedUSER = 'STUDENT';
			$setIntendeduser = 'student';
			$setIntendeduserId = 'studentId';
			$setSubmitBtn = 'editStudentSubmit';
			$setSubmitHidden = 'editStudentForm';
		}
		if (!isset($_GET["$setIntendeduser"])) {
			staticFunc::errorPage( 'error' );
		} else {
			$getUserDetails = staticFunc::unmaskURLParam($_GET["$setIntendeduser"]);
			$userDetails = self::getUserDetails( $setIntendedUser, $getUserDetails );
			if (!is_array($userDetails) || empty($userDetails)) {
				$type = 'error';
				$msg = "The $setIntendedUser Details has not been set previously";
				staticFunc::alertDisplay ( $type, $msg );
			} else {
				echo "<div class='row add-item-row'><button class='btn btn-info btn-add-item' onclick=\"window.location.href='{$setIntendeduser}details.php?{setIntendeduser}=".staticFunc::maskURLParam($_GET[$setIntendeduser])."'\"><strong>Back To $setIntendedUser Details</strong></button></div>";
				foreach ( $userDetails as $key => $value ) {
?>
			<div class="col-md-12">
				<form class="form-horizontal form-add-info" id="add-item-form" enctype="multipart/form-data" method="post" action="<?php echo basename($_SERVER['PHP_SELF']); ?>">
					<fieldset>
					<legend class="text-info text-center">Edit <?php echo $setIntendedUser; ?> Information</legend>
						<div class="row">
							<div class="col-md-4 <?php if (isset(staticFunc::$formInput['surname'])) { echo 'has-error'; } ?>">
								<label for="surname">Surname</label>
								<input type="text" id="surname" name="surname" maxlength="30" class="form-control" value="<?php echo $value['surname']; ?>" placeholder="Enter Surname" required/>
								<p class="help-block">Cannot be empty</p>
							</div>
							<div class="col-md-4 <?php if (isset(staticFunc::$formInput['firstname'])) { echo 'has-error'; } ?>">
								<label for="surname">First Name</label>
								<input type="text" id="firstname" name="firstname" maxlength="30" class="form-control" value="<?php echo $value['firstname']; ?>" placeholder="Enter First Name" required/>
								<p class="help-block">Cannot be empty</p>
							</div>
							<div class="col-md-4 <?php if (isset(staticFunc::$formInput['othername'])) { echo 'has-error'; } ?>">
								<label for="othername">Other Name</label>
								<input type="text" id="othername" name="othername" maxlength="30" class="form-control" value="<?php echo $value['othername']; ?>" placeholder="Enter Other Name" required/>
								<p class="help-block">Cannot be empty</p>
							</div>
							<div class="col-md-4 <?php if (isset(staticFunc::$formInput['street'])) { echo 'has-error'; } ?>">
								<label for="street">Street</label>
								<input type="text" id="street" name="street" maxlength="30" class="form-control" value="<?php echo $value['street']; ?>" placeholder="Enter Street Address" required/>
								<p class="help-block">Cannot be empty</p>
							</div>
							<div class="col-md-4 <?php if (isset(staticFunc::$formInput['city'])) { echo 'has-error'; } ?>">
								<label for="city">City</label>
								<input type="text" id="city" name="city" maxlength="30" class="form-control" value="<?php echo $value['city']; ?>" placeholder="Enter City of Residence" required/>
								<p class="help-block">Cannot be empty</p>
							</div>
							<div class="col-md-4 <?php if (isset(staticFunc::$formInput['state'])) { echo 'has-error'; } ?>">
								<label for="state">State</label>
								<input type="text" id="state" name="state" maxlength="30" class="form-control" value="<?php echo $value['state']; ?>" placeholder="Enter State of Residence" required/>
								<p class="help-block">Cannot be empty</p>
							</div>
							<div class="col-md-4 <?php if (isset(staticFunc::$formInput['country'])) { echo 'has-error'; } ?>">
								<label for="country">Country</label>
								<input type="text" id="country" name="country" maxlength="30" class="form-control" value="<?php echo $value['country']; ?>" placeholder="Enter Country of Residence" required/>
								<p class="help-block">Cannot be empty</p>
							</div>
							<div class="col-md-4 <?php if (isset(staticFunc::$formInput['phone'])) { echo 'has-error'; } ?>">
								<label for="phone">Phone Number</label>
								<input type="text" id="phone" name="phone" maxlength="15" class="form-control" value="<?php echo $value['phone']; ?>" placeholder="Enter Official Phone Number" required/>
								<p class="help-block">Cannot be empty</p>
							</div>
							<div class="col-md-4 <?php if (isset(staticFunc::$formInput['phone_alt'])) { echo 'has-error'; } ?>">
								<label for="othername">Alternative Phone</label>
								<input type="text" id="phone_alt" name="phone_alt" maxlength="15" class="form-control" value="<?php echo $value['phone_alt']; ?>" placeholder="Enter Alternative Phone Number"/>
								<p class="help-block">An Alternative Phone Number</p>
							</div>
							<div class="col-md-4 <?php if (isset(staticFunc::$formInput['gender'])) { echo 'has-error'; } ?>">
								<label for="gender">Gender</label><br />
								<select name="gender" class="item-select form-inline" id="edit-gender">
									<option value="0" hidden> - Select Gender - </option>
									<option value="1" <?php if (isset($_POST['gender']) && $_POST['gender'] == 1 || $value['gender'] == 'Male') { echo 'selected'; }?> >Male</option>
									<option value="2" <?php if (isset($_POST['gender']) && $_POST['gender'] == 2 || $value['gender'] == 'Female') { echo 'selected'; }?> >Female</option>
								</select>
								<p class="help-block">Select the gender</p>
							</div>
							<div class="col-md-4 <?php if (isset(staticFunc::$formInput['email'])) { echo 'has-error'; } ?>">
								<label for="email">Email Address</label>
								<input type="text" class="form-control" id="email" maxlength="40" name="email" value="<?php echo $value['email']; ?>" placeholder="Enter Email Address" required/>
								<p class="help-block">Email Address should not be more than 40 characters</p>
							</div>
							<div class="col-md-6 <?php if (isset(staticFunc::$formInput['file'])) { echo 'has-error-file'; } ?>">
								<label for="currentPhoto"><?php echo $setIntendedUser; ?>'s Photo</label><br />
								<img src="<?php echo urldecode($value['photo']); ?>" id="currentPhoto" alt="<?php echo $value['names']; ?>"/><br /><br />
								<label for="userPhoto">Change <?php echo $setIntendedUser; ?>'s Photo</label>
								<input type="file" id="userPhoto" name="file1" />
								<p class="help-block">Image Format should be in .jpg, .gif, .png formats</p>
							</div>
						</div>
						<div class="row">
							<div class="form-group">
								<input type="submit" id="<?php echo $setSubmitBtn; ?>" name="<?php echo $setSubmitBtn; ?>" class="btn btn-info save-btn" value="Save Changes"/>
							</div>
							<input type="hidden" name="edit<?php echo $setSubmitHidden; ?>" value="<?php echo staticFunc::maskURLParam($value['student_id']); ?>">
							<input type="hidden" name="edit<?php echo $setIntendedUser; ?>" value="<?php echo staticFunc::maskURLParam($value['photo']); ?>">
						</div>
					</fieldset>
				</form>
			</div>
<?php
				}
			}
		}
	}

	/**
	 * 	method userUI displays dashboard interface of several options for navigation of currently logged in user
	 */
	protected function userUI () {
		$payment = ($this->userType == 'Customer') ? 'finance.php' : 'feesrecords.php';
?>		
		<div class="row">
			<div class="well user-well">
				<h3 class="text-center"><strong>
					Welcome to Your Personal Information Page<br />
					<small>From here, you can access all your personal details</small>
				</strong></h3>
			</div>
			<div class="col-sm-6 col-md-4 home-icon">
				<a href="personal.php" title="Personal Details"><span class="fa fa-user home-icon"></span>
				<div class="text-center inline-block home-text">Personal Details</div></a>
			</div>
			<div class="col-sm-6 col-md-4 home-icon">
				<a href="messages.php" title="Messages"><span class="fa fa-inbox home-icon"></span>
				<div class="text-center inline-block home-text">Messages</div></a>
			</div>
			<?php if ($this->userType !== 'Admin') { ?>
			<div class="col-sm-6 col-md-4 home-icon">
				<a href="<?php echo $payment; ?>" title="Payment Records"><span class="fa fa-archive home-icon"></span>
				<div class="text-center inline-block home-text">Payment Records</div></a>
			</div>
			<?php } ?>
			<div class="col-sm-6 col-md-4 home-icon">
				<a href="reminders.php" title="Reminders"><span class="badge notify-badge"><?php $remind = new Reminder; echo $remind->allReminders ( $this->pdo, $_SESSION['userType'] ); ?></span><span class="glyphicon glyphicon-calendar home-icon"></span>
				<div class="text-center inline-block home-text">Reminders</div></a>
			</div>
		</div>		
<?php		
	}
	
	/**
	 * 	method personalUI displays interface of personal records for currently logged in user
	 */
	private function personalUI () {
		echo "<div class='row add-item-row'><button class='btn btn-info btn-add-item' onclick=\"window.location.href='user.php'\"><strong>Back To User DashBoard</strong></button></div>";
		$getPersonalDetails = self::getUserDetails( $this->userType, $this->userId );
		if (!is_array($getPersonalDetails) || empty($getPersonalDetails)) {
			$type = 'error';
			$msg = "There Was An Error Accessing Your Details. Please Report To The Admin <a href='contactus.php?err=nopersonalaccess&errcode=".staticFunc::maskURLParam($this->userId).">HERE</a>";
			staticFunc::alertDisplay ( $type, $msg );
		} else {
			foreach ($getPersonalDetails as $key => $value) {
?>
			<div class="col-md-10 col-md-offset-1">
				<div class="text-info pad text-xlg bold text-center">MY PERSONAL DETAILS</div>
				<table class="table table-responsive text-sm">
					<tr>
						<td class="text-center"><span class="text-left block text-xs">Names:</span><b><?php echo $value['names']; ?></b></td><td class="text-center"><span class="text-left block text-xs">Photo:</span><img src="<?php echo urldecode($value['photo']); ?>" class="img-thumbnail" /></td><td class="text-center"><span class="text-left block text-xs">Gender:</span><b><?php echo $value['gender']; ?></b></td>
					</tr>
					<tr>
						<td colspan="2" class="text-center"><span class="text-left block text-xs">Address:</span><b><?php echo $value['address']; ?></b></td>
						<td class="text-center"><span class="text-left block text-xs">Phones:</span><b><?php echo rtrim($value['phone_numbers'], ' ,'); ?></b></td>
					</tr>
					<tr>
						<td colspan="2" class="text-center"><span class="text-left block text-xs">Birthday:</span><b><?php echo $value['birthday']; ?></b></td>
						<td class="text-center"><span class="text-left block text-xs">Email:</span><b><?php echo $value['email']; ?></b></td>
					</tr>
					<tr>
						<td colspan="2" class="text-center"><span class="text-left block text-xs">Security Question:</span><b><?php echo $value['security_question']; ?></b></td>
						<td class="text-center"><span class="text-left block text-xs">Answer to Security Question:</span><b><?php echo $value['security_answer']; ?></b></td>
					</tr>
				</table>
				<div class="row">
					<button class='btn btn-info btn-add-item' onclick="window.location.href='editpersonal.php'"><strong>Update My Details</strong></button>
				</div>
			</div>
<?php
			}
		}
	}

	/**
	 * method editpersonalUI display interface to edit personal details of currently logged in user
	 */
	private function editpersonalUI () {
?>
		<div class='row add-item-row'><button class='btn btn-info btn-add-item' onclick="window.location.href='user.php'"><strong>Back To User DashBoard</strong></button></div>
		<h3 class="text-center text-info" id="top"><strong>PERSONAL SETTINGS</strong></h3>
		<hr class="hr-divide">
		<p class="text-info text-center text-sm" id="appRow">Update my personal details</p>
		<form class="form-horizontal" enctype="multipart/form-data" method="post" action="<?php echo basename($_SERVER['PHP_SELF']); ?>">
			<fieldset>
			<div class="row" id="targetRow">
<?php
			if ( isset($_GET['password']) ) {
				if ( $_GET['password'] == 'error' ) {
					$type = 'error';
					$msg = '<b>The Old Password You Entered Is Incorrect</b>';
					staticFunc::alertDisplay($type, $msg, 1);
				} elseif ( $_GET['password'] == 'mismatch' ) {
					$type = 'error';
					$msg = '<b>Your New Passwords Do Not Match</b>';
					staticFunc::alertDisplay($type, $msg, 1);
				} elseif ( $_GET['password'] == 'used' ) {
					$type = 'error';
					$msg = '<b>Please Select A Password You Have Not Used Before</b>';
					staticFunc::alertDisplay($type, $msg, 1);
				} elseif ( $_GET['password'] == 'same' ) {
					$type = 'error';
					$msg = '<b>The New Password Has To Be Different From The Current Password</b>';
					staticFunc::alertDisplay($type, $msg, 1);
				}
			} elseif ( isset($_GET['photo']) ) {
				$type = 'error';
				$getMsg = staticFunc::unmaskURLParam($_GET['photo']);
				$msg = "<b>$getMsg</b>";
				staticFunc::alertDisplay($type, $msg, 1);
			} elseif ( isset($_GET['user']) ) {
				if ( $_GET['user'] == 'success' ) {
					$type = 'success';
					$msg = '<b>Your Personal Details Have Been Updated Successfully</b>';
					staticFunc::alertDisplay($type, $msg);
				} elseif ( $_GET['user'] == 'error' ) {
					$type = 'error';
					$msg = '<b>There Was An Error Saving Your Settings</b>';
					staticFunc::alertDisplay($type, $msg, 1);
				}
			}
			echo '<div class="col-md-6 pad-left">';
			$loginData = self::getLoginDetails ( $this->userId );
?>
					<div class="form-group <?php if (isset(staticFunc::$formInput['username'])) { echo 'has-error'; } ?>">
						<label for="username">Username</label>
						<input type="text" id="username" name="updateUsername" maxlength="30" class="form-control" value="<?php if (isset($_POST['updateUsername'])) { echo $_POST['updateUsername']; } else { echo $loginData; } ?>" placeholder="Enter Username" required disabled/>
						<p class="help-block">Your Username cannot be changed</p>
					</div>
					<div class="form-group <?php if (isset(staticFunc::$formInput['password'])) { echo 'has-error'; } ?>">
						<label for="current_password">Old Password</label>
						<input type="password" id="current_password" name="currentPassword" maxlength="30" class="form-control" placeholder="Enter New Password" />
						<p class="help-block">Enter Old Password</p>
					</div>
					<div class="form-group <?php if (isset(staticFunc::$formInput['password'])) { echo 'has-error'; } ?>">
						<label for="password">New Password</label>
						<input type="password" id="password" name="updatePassword" maxlength="30" class="form-control" placeholder="Enter New Password" />
						<p class="help-block">Enter New Password</p>
					</div>
					<div class="form-group <?php if (isset(staticFunc::$formInput['re-password'])) { echo 'has-error'; } ?>">
						<label for="re-password">Confirm New Password</label>
						<input type="password" id="re-password" name="updateRePassword" maxlength="30" class="form-control" placeholder="Enter New Password Again" />
						<p class="help-block">Confirm New Password For Confirmation</p>
					</div>
				</div>
				<div class="col-md-6">
<?php
			$userData = self::detailsForUpdate ( $this->userId );
			foreach ( $userData as $key => $value ) {
?>
					<div class="col-md-6 <?php if (isset(staticFunc::$formInput['phone'])) { echo 'has-error'; } ?>">
						<label for="phone">Phone Number</label>
						<input type="text" id="phone" name="updatePhone" maxlength="30" class="form-control" value="<?php if (isset($_POST['updatePhone'])) { echo $_POST['updatePhone']; } else { echo $value['phone']; } ?>" placeholder="Update Phone Number" required />
						<p class="help-block">Update Your Phone Number</p>
					</div>
					<div class="col-md-6 <?php if (isset(staticFunc::$formInput['phoneAlt'])) { echo 'has-error'; } ?>">
						<label for="phoneAlt">Alternative Phone Number</label>
						<input type="text" id="phoneAlt" name="updatePhoneAlt" maxlength="30" class="form-control" value="<?php if (isset($_POST['updatePhoneAlt'])) { echo $_POST['updatePhoneAlt']; } else { echo $phoneNumber = ($value['phone_alt'] !== NULL) ? $value['phone_alt'] : 'Unavailable'; } ?>" placeholder="Update Alternative Phone Number"  />
						<p class="help-block">Update Your Alternative Phone Number</p>
					</div>
					<div class="col-md-12 <?php if (isset(staticFunc::$formInput['email'])) { echo 'has-error'; } ?>">
						<label for="email">Email Address</label>
						<input type="email" id="email" name="updateEmail" maxlength="30" class="form-control" value="<?php if (isset($_POST['updateEmail'])) { echo $_POST['updateEmail']; } else { echo $value['email']; } ?>" placeholder="Update Email Address" required/>
						<p class="help-block">Update Email Address</p>
					</div>
					<div class="row pad-row">
						<div class="col-md-6 <?php if (isset(staticFunc::$formInput['security_question'])) { echo 'has-error'; } ?>">
							<label for="security_question">Current Security Question</label>
							<textarea type="security_question" id="security_question" name="security_question" rows="2" maxlength="100" class="form-control" placeholder="Update Security Question" required><?php if (isset($_POST['security_question'])) { echo $_POST['security_question']; } else { echo $value['security_question']; } ?></textarea>
							<p class="help-block">Update Security Question</p>
						</div>
						<div class="col-md-6 <?php if (isset(staticFunc::$formInput['security_answer'])) { echo 'has-error'; } ?>">
							<label for="security_answer">Current Answer</label>
							<input type="security_answer" id="security_answer" name="security_answer" maxlength="100" class="form-control" value="<?php if (isset($_POST['security_answer'])) { echo $_POST['security_answer']; } else { echo $value['security_answer']; } ?>" placeholder="Update Answer" required/>
							<p class="help-block">Update Answer To Security Question</p>
						</div>
					</div>
					<div class="col-sm-4 col-md-6"><img src="<?php echo urldecode($value['photo']); ?>" alt="" class="img-responsive img-rounded" /></div>
					<div class="col-sm-8 col-md-6 pad-top <?php if (isset(staticFunc::$formInput['photo'])) { echo 'has-error'; } ?>">
						<label for="userPhoto">Upload Photo to Replace Current</label><br />
						<input type="file" id="userPhoto" name="updateUserPhoto" class="file_upload" />
						<p class="help-block">Upload New Photo to replace current Profile Photo</p>
					</div>
<?php  		}
?>
				</div>
			</div>
			<div class="row index-row">
				<input type="submit" name="userDataSubmit" class="btn btn-info add-item-btn" value="Save Changes"/>
				<input type="hidden" name="saveUserData" />
			</div>
			</fieldset>
		</form>
<?php
	}
}