<?php

class UI {
	private $page;
	private $pdo;
	private $user;
	private $userData;
	
	public function __construct ( $pdo ) {
		$this->page = $_SERVER["PHP_SELF"];
		$this->pdo = $pdo;
		self::phpHeader();
		self::htmlHeader();
		self::mainSection();
		self::bottomSection();
	}
	
	private function phpHeader () {
		if (isset($_SESSION['userType']) && isset($_SESSION['userId'])) {
			if ( $this->page == '/mcsueapp/index.php' ) {
				staticFunc::redirect('mcsue/index.php');
			} else {
				if ( $_SESSION['userType'] == 'Admin' ) {
					$this->user = new Admin($this->pdo);
					$this->userData = $this->user->getData ( get_class($this->user), $_SESSION['userId'] );
				} elseif ( $_SESSION['userType'] == 'Customer' ) {
					$this->user = new Customer($this->pdo);
					$this->userData = $this->user->getData ( get_class($this->user), $_SESSION['userId'] );
				} elseif ( $_SESSION['userType'] == 'Student' ) {
					$this->user = new Student($this->pdo);
					$this->userData = $this->user->getData ( get_class($this->user), $_SESSION['userId'] );
				}
			}
		} else { //This is an error access
			if ( $this->page !== '/mcsueapp/index.php' && $this->page !== '/mcsueapp/forgotpassword.php' ) {
				staticFunc::endSession( $this->pdo, 1 );
				staticFunc::redirect('../index.php?access=denied');
			}
		}
		//Log Out if this is Logout page
		if ( $this->page == '/mcsueapp/mcsue/logout.php' ) {
			$this->user->logout();
		} elseif ( $this->page !== '/mcsueapp/index.php' && $this->page !== '/mcsueapp/forgotpassword.php' ) {
			//Get User's Preferences
			staticFunc::userPreferences( $this->pdo );
		}
		staticFunc::formHandling( $this->pdo );
	}
	
	public function mainSection () {
		self::confirmIndexPage();
		self::createBodySection();
	} //End of mainSection
	
	private function confirmIndexPage () {
		//Removes Sidebar from Login and Dashboard Pages
		if ( basename($this->page) !== 'index.php' && basename($this->page) !== 'forgotpassword.php'  ) {
			//Adding the left side bar
			self::leftSideBar();
		} else {
			//Login page does not need the Left side bar
			echo '<!-- Main Content Starts Here -->
				<div class="container">';
		}
	}
	
	private function createBodySection () {
		//Removed mcsueapp
		//if ($this->page !== '/index.php') {
		if ( $this->page !== '/mcsueapp/index.php' && $this->page !== '/mcsueapp/forgotpassword.php' ) {
			$page = basename($this->page);
			$this->user->createUI ( $page, $_SESSION['userId'], $this->user );
		} else {
			$page = basename($this->page);
			if ($page == 'index.php') {
				//Call Login Page Method implemented in UI Class
				self::createLoginPage();
			} elseif ($page == 'forgotpassword.php') {
				//Call Forgot Password Method inplemented in UI Class
				self::forgotPassword();
			}
		}
	}
	
	public function htmlHeader () {
		self::htmlHeadSection();
		self::htmlNavBarSection();
	} //End of htmlHeader method

	public function htmlHeadSection () {
	/**	Using method of closing php tags so as to avoid 
	*	echoing all the HTML within PHP
	*/
?>
		<!DOCTYPE html>
		<!--
		Developed by :	Excelling In Motion Enterprises
		Date:			May 2016
		-->
		<html lang="en">
			<head>
				<meta charset="UTF-8">
				<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
				<title>McSue ::<?php echo self::pageTitle(); ?></title>
<?php	
	} //End of htmlHeadSection
	
	public function htmlNavBarSection () {
		//Removed mcsueapp
		//if ( $this->page !== '/index.php' ) {
		if ( $this->page !== '/mcsueapp/index.php' && $this->page !== '/mcsueapp/forgotpassword.php' ) {
		//If this is not the Login Page
?>	
			<link rel="stylesheet" href="../css/bootstrap.min.css" />
			<link rel="stylesheet" href="../css/bootstrap-theme.min.css" />
			<link rel="stylesheet" href="../css/jquery-ui.min.css" />
			<link rel="stylesheet" href="../css/font-awesome.min.css" />
			<link rel="stylesheet" href="../css/jquery-ui.theme.min.css" />
			<link rel="stylesheet" href="../css/styles.css" />
		</head>
		<body>
			<div id="ajaxUpdate">
					
			<nav class="navbar navbar-fixed-top" role="navigation">
				<div class="container">
					<div class="navbar-header">
						<button type="button" class="navbar-toggle mcsue-toggle" data-toggle="collapse" data-target="#mcsue-navbar">
							<span class="sr-only">Toggle navigation</span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>
						<a href="index.php">
						<img class="img-rounded" src="../img/mcsue.gif" alt="McSue Logo" width="130.3" height="50.4"/></a>
					</div>
					<div class="collapse navbar-collapse navbar-right" id="mcsue-navbar">
						<ul class="nav navbar-nav">
							<li class="active"><a href="index.php"><span class="fa fa-home"></span> Home</a></li>
<?php
			if ( get_class($this->user) == "Admin" ) {
			//User Logged in is an Admin	
?>
							<li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="glyphicon glyphicon-picture"></span> Designs <b class="caret"></b></a>
							<ul class="dropdown-menu">
								<li><a href="designs.php">Designs</a></li>
								<li><a href="customers.php">Customers</a></li>
								<li><a href="orders.php">Orders</a></li>
								<li><a href="finance.php">Finances</a></li>
								<li><a href="debt.php">Debts</a></li>
							</ul>
							</li>
							<li class="active"><a href="training.php"><span class="glyphicon glyphicon-education"></span> Training</a></li>
							<li class="active nav-header-btn" title="Settings"><a href="settings.php"><span class="fa fa-cog"></span></a></li>		
<?php
			} elseif ( get_class($this->user) == "Customer" ) {
			//User Logged in is a Customer
?>
							<li class="active"><a href="designs.php"><span class="glyphicon glyphicon-picture"></span> Designs</a></li>
							<li class="active"><a href="orders.php"><span class="fa fa-shopping-cart"></span> Orders</a></li>
							<li class="active"><a href="contactus.php"><span class="fa fa-user"></span> Contact Us</a></li>
<?php
			} elseif ( get_class($this->user) == "Student" ) {
			//User Logged in is a Student
?>
							<li class="active"><a href="designs.php"><span class="glyphicon glyphicon-picture"></span> Designs</a></li>
							<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="glyphicon glyphicon-education"></span> Training <b class="caret"></b></a>
								<ul class="dropdown-menu">
									<li><a href="training.php">Training</a></li>
									<li><a href="results.php">Results</a></li>
									<li><a href="feepayment.php">Fees Payment</a></li>
								</ul>
							</li>
							<li class="active"><a href="contactus.php"><span class="fa fa-user"></span> Contact Us</a></li>	
<?php		}	?>			
							<li class="nav-header-btn" title="Log Out"><a href="logout.php"><span class="fa fa-power-off"></span></a></li>
						</ul>
						<div class="navbar-right" id="default_img"><a href="user.php" title="<?php echo $this->userData['name']; ?>"><img class="img-thumbnail" src=<?php echo urldecode($this->userData['photo']); ?> alt="User Photo" /><span class="badge mcsue-badge blink"><?php 
						$remind = new Reminder;
						echo $remind->allReminders ( $this->pdo, $_SESSION['userType'] );
						?></span></a></div>
					</div>
				</div>
			</nav>
			<div class="container" id="mainContainer">
				<div class="row">
<?php
		} else {
		//If this is the Login page
?>		
			<link rel="stylesheet" href="css/bootstrap.min.css" />
			<link rel="stylesheet" href="css/bootstrap-theme.min.css" />
			<link rel="stylesheet" href="css/jquery-ui.min.css" />
			<link rel="stylesheet" href="css/font-awesome.min.css" />
			<link rel="stylesheet" href="css/jquery-ui.theme.min.css" />
			<link rel="stylesheet" href="css/styles.css" />
		</head>
		<body>
			<nav class="navbar navbar-fixed-top" role="navigation">
				<div class="container">
					<div class="navbar-header">
						<a href="index.php">
						<img class="img-rounded" src="img/mcsue.gif" alt="McSue Logo" width="130.3" height="50.4"/></a>
					</div>
				</div>
			</nav>
			<div class="container" id="mainContainer">
				<div class="row" id="containerRow">
<?php		
		}
	} //End of htmlNavBarSection
	
	public function leftSideBar () {
		//HTML div to hold side bar
		//Shows up only on Medium and Large devices
?>		
					<div class="hidden-xs hidden-sm col-md-3 text-center" id="sidebar-main">
						<p class="caption text-info"><b>STATUS</b></p>
						<hr class="hr-divide"/>
						<span class="fa fa-user text-info user-info" ></span><span class="text-info user-info">&nbsp;&nbsp;<?php echo $this->userData['name']; ?></span>
						<br />
						<span id="user_type">(<?php
							if (get_class($this->user) == 'Admin') {
								echo 'Administrator';
							} elseif (get_class($this->user) == 'Customer') {
								echo 'Customer';
							} else {
								echo 'Student';
							}
						?>)</span>
						<p class="text-center text-info" id="logged">Logged In: </p><span class="text-center" id="log-time"><?php $log = new Log; $log->readLog ($this->pdo, $_SESSION['userId'] ); ?></span>
						<hr class="hr-divide"/>
						<div id="sidebar-img"><a href="user.php" title="<?php echo $this->userData['name']; ?>"><img class="img-rounded img-responsive" 
						src=<?php echo urldecode($this->userData['photo']); ?> alt="User Photo"/>
						</a></div>
					</div>
				<!-- Main Content Starts Here -->
						<div class="col-md-9" id="mainContent">
<?php	
	} //End of leftSideBar
	
	public function bottomSection () {
		//Removed mcsueapp
		//if ( $this->page !== '/index.php' ) {
		if ( $this->page !== '/mcsueapp/index.php' && $this->page !== '/mcsueapp/forgotpassword.php' ) {
			echo '</div> <!--Close of main content container div -->
			</div> <!--Close of body row div -->
			</div> <!--Close of main Container div -->
			<!-- Javascript loaded at bottom of page -->
			<script type="text/javascript" src="../js/jquery-1.12.4.min.js"></script>
			<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
			<script type="text/javascript" src="../js/bootstrap.min.js"></script>
			<script type="text/javascript" src="../js/ajax.js"></script>			
			<script type="text/javascript" src="../	js/modal.js"></script>';
		} else {
			echo '</div> <!--Close of main content container div -->
			</div> <!--Close of body row div -->
			</div> <!--Close of main Container div -->
			<!-- Javascript loaded at bottom of page -->
			<script type="text/javascript" src="js/jquery-1.12.4.min.js"></script>
			<script type="text/javascript" src="js/jquery-ui.min.js"></script>
			<script type="text/javascript" src="js/bootstrap.min.js"></script>
			<script type="text/javascript" src="js/ajax.js"></script>			
			<script type="text/javascript" src="js/modal.js"></script>';
		}
?>
			<footer role="navigation">
				<div class="container">
					<div class="row">
						<!-- Removed mcsueapp 
						<div class="<?php //if ($this->page == '/index.php') { echo 'col-sm-offset-4';} ?> -->
						<div class="<?php if ($this->page == '/mcsueapp/index.php' || $this->page == '/mcsueapp/forgotpassword.php') { echo 'col-sm-offset-4';} ?>
						col-sm-4 col-md-4" id="footer-address">
							<div class="caption">Contact Us</div><hr class="hr-class">
							<span class="glyphicon glyphicon-map-marker"></span>&nbsp;&nbsp;&nbsp;<span>Apples Court, Area A, Nyanya, Abuja.</span>
							<br />
							<span class="fa fa-phone"></span>&nbsp;&nbsp;&nbsp;&nbsp;<span  class="text-left">07051640166</span>
							<br />
							<span class="glyphicon glyphicon-envelope"></span>&nbsp;&nbsp;&nbsp;<span><a href="mailto:mcsuedesignz@gmail.com" >mcsuedesignz@gmail.com</a></span>
							<br />
							<span class="fa fa-laptop"></span>&nbsp;&nbsp;&nbsp;<span><a href="http://www.mcsuedesignz.com" target="_blank" title="Open in New Window">www.mcsuedesignz.com</a></span>				
						</div>
<?php 
		//Removed mcsueapp
		//if ($this->page !== '/index.php') {
		if ($this->page !== '/mcsueapp/index.php' && $this->page !== '/mcsueapp/forgotpassword.php') {
		//Don't show navigation links on Login Page
?>
						<div class="col-sm-4 col-md-4" id="footer-links">
							<div class="caption">Navigation</div><hr class="hr-class">
							<div class="row">
							<?php if ($_SESSION['userType'] == 'Admin') {
								$link1 = 'customers.php';
								$linkName1 = 'Customers';
								$link2 = 'orders.php';
								$linkName2 = 'Orders';
								$link3 = 'training.php';
								$linkName3 = 'Training';
								$link4 = 'finance.php';
								$linkName4 = 'Finance';
							} elseif ($_SESSION['userType'] == 'Customer') {
								$link1 = 'reminders.php';
								$linkName1 = 'Reminders';
								$link2 = 'orders.php';
								$linkName2 = 'Orders';
								$link3 = 'training.php';
								$linkName3 = 'Training';
								$link4 = 'contactus.php';
								$linkName4 = 'Contact Us';
							} else {
								$link1 = 'reminders.php';
								$linkName1 = 'Reminders';
								$link2 = 'training.php';
								$linkName2 = 'Training';
								$link3 = 'feespayment.php';
								$linkName3 = 'Fees Payment';
								$link4 = 'contactus.php';
								$linkName4 = 'Contact Us';
							}
							?>
								<div class="col-xs-6" id="footer-links-1">	
									<ul>
										<li><a href="index.php">Home</a></li>
										<li><a href="designs.php"> Designs</a></li>
										<li><a href="<?php echo $link1; ?>"><?php echo $linkName1; ?></a></li>
										<li><a href="<?php echo $link2; ?>"><?php echo $linkName2; ?></a></li>
									</ul>
								</div>
								<div class="col-xs-6" id="footer-links-2">
									<ul>
										<li><a href="<?php echo $link3; ?>"><?php echo $linkName3; ?></a></li>
										<li><a href="user.php">Account</a></li>
										<li><a href="<?php echo $link4; ?>"><?php echo $linkName4; ?></a></li>
										<li><a href="logout.php">Log Out</a></li>
									</ul>
								</div>
							</div>
						</div>
<?php } ?>
						<div class="col-sm-4 col-md-4" id="footer-copyright">
							<div class="row">
								<div class="col-xs-8 col-xs-offset-2" id="footer-mcsue">
									<div id="footer-mcsue-img"></div>
								</div>
							</div>
							<div class="row">
								<div id="copy-text">
									<span class="glyphicon glyphicon-copyright-mark"></span><span>&nbsp;<?php echo date('Y'); ?>;&nbsp;McSue Designz</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</footer>
			</div>
		</body>
	</html>
<?php
	}//End of bottomSection method
	
	private function forgotPassword () {
?>
		<div class="col-md-6 col-md-offset-3">
		<?php if (isset($_SESSION['confirmedResetId'])) { ?>
			<form class="form-horizontal form-add-info" method="post" action="<?php echo basename($_SERVER['PHP_SELF']); ?>">
				<div class="row">
					<p class="text-info text-center text-lg bold">Set New Password</p>
					<p class="text-center">Please Do Not Select A Password You Have Used Before</p>
					<hr class="hr-class">
					<div class="col-md-6 <?php if (isset(staticFunc::$formInput['new_password'])) { echo 'has-error'; } ?>">
						<label for="new_password">New Password</label>
						<input type="password" id="new_password" name="new_password" maxlength="50" class="form-control" value="<?php if (isset($_POST['new_password'])) { echo $_POST['new_password']; } ?>" placeholder="Enter Your New Password" required />
						<p class="help-block">Select A New Password</p>
					</div>
					<div class="col-md-6 <?php if (isset(staticFunc::$formInput['confirm_new_password'])) { echo 'has-error'; } ?>">
						<label for="confirm_new_password">Confirm New Password</label>
						<input type="password" id="confirm_new_password" name="confirm_new_password" maxlength="50" class="form-control" value="<?php if (isset($_POST['confirm_new_password'])) { echo $_POST['confirm_new_password']; } ?>" placeholder="Please Reenter New New Password" required />
						<p class="help-block">Re-Enter New Password To Confirm Selection</p>
					</div>
				</div>
				<div class="row">
					<div class="form-group">
						<input type="submit" id="finalPassResetSubmit" name="finalPassResetSubmit" class="btn btn-info save-btn" value="Change Password" />
					</div>
					<input type="hidden" name="finalPassResetForm">
				</div>
			</form>
		<?php } elseif (isset($_SESSION['resetSuccess'])) {?>
			<div class="row">
				<div class="row">
					<p class="text-info text-center text-lg bold">Password Change Successful</p>
					<hr class="hr-wide">
					<div class="col-md-8 col-md-offset-2">
						<div class="bold text-center text-sm margin-wide">Your Password Has Been Successfully Changed.</div>
						<button onclick='window.location.href="index.php"' class="btn btn-info btn-add-item">Login Now</button>
					</div>
				</div>
				<?php unset($_SESSION['resetSuccess']); ?>
			</div>
		<?php } else {?>
			<?php if (is_null(staticFunc::$forgotPassword)) { ?>
				<form class="form-horizontal form-add-info" method="post" action="<?php echo basename($_SERVER['PHP_SELF']); ?>">
					<div class="row">
						<p class="text-info text-center text-lg bold">Reset Password</p>
						<p class="text-center">Please Enter Your Email Address To Confirm Your Account</p>
						<hr class="hr-class">
						<div class="col-md-6 <?php if (isset(staticFunc::$formInput['forgot-email'])) { echo 'has-error'; } ?>">
							<label for="username">Email Address</label>
							<input type="email" id="forgot-email" name="forgot-email" maxlength="50" class="form-control" value="<?php if (isset($_POST['forgot-email'])) { echo $_POST['forgot-email']; } ?>" placeholder="Enter Your Email Address" required />
							<p class="help-block">Enter Your Email Address Email</p>
						</div>
						<div class="col-md-6 <?php if (isset(staticFunc::$formInput['accountType'])) { echo 'has-error'; } ?>">
							<label for="accountType">Select Type of Account</label><br />
							<select name="accountType" class="select-full-width form-inline">
								<option value="0" hidden> - Select Account Type - </option>
								<option value="1" <?php if (isset($_POST['accountType']) && $_POST['accountType'] == 1) { echo 'selected'; }?> >Admin Account</option>
								<option value="2" <?php if (isset($_POST['accountType']) && $_POST['accountType'] == 2) { echo 'selected'; }?> >Customer Account</option>
								<option value="3" <?php if (isset($_POST['accountType']) && $_POST['accountType'] == 3) { echo 'selected'; }?> >Student Account</option>
							</select>
							<p class="help-block">Select Your Account Type</p>
						</div>
					</div>
					<div class="row">
						<div class="form-group">
							<input type="submit" id="confirmEmailSubmit" name="confirmEmailSubmit" class="btn btn-info save-btn" value="Confirm Email Address" />
						</div>
						<input type="hidden" name="confirmEmailForm">
					</div>
				</form>
			<?php } else { ?>
				<form class="form-horizontal form-add-info" method="post" action="<?php echo basename($_SERVER['PHP_SELF']); ?>">
					<fieldset>
					<p class="text-info text-center text-lg bold">Reset Password</p>
					<p class="text-center">Please provide the following additional details to confirm your ownership of the account. They must match the information that you provided upon registration</p>
						<div class="row">
							<div class="col-md-6 <?php if (isset(staticFunc::$formInput['forgot-email'])) { echo 'has-error'; } ?>">
								<label for="username">Email Address</label>
								<input type="email" id="forgot-email" name="forgot-email" maxlength="50" class="form-control" value="<?php if (isset($_POST['forgot-email'])) { echo $_POST['forgot-email']; } ?>" readonly />
							</div>
							<div class="col-md-6 <?php if (isset(staticFunc::$formInput['accountType'])) { echo 'has-error'; } ?>">
								<label for="userType">Type of Account</label><br />
								<p id="userType" class="bold text-sm form-control-static"><?php echo staticFunc::returnAccountType($_SESSION['setUserType']).' Account'; ?></p>
							</div>
						</div>
						<hr class="hr-wide"/>
						<div class="row">
							<div class="col-md-6 <?php if (isset(staticFunc::$formInput['username'])) { echo 'has-error'; } ?>">
								<label for="username">Username</label>
								<input type="text" id="username" name="username" maxlength="30" class="form-control" value="<?php if (isset($_POST['username'])) { echo $_POST['username']; } ?>" placeholder="Enter Your Username" required/>
								<p class="help-block">Enter Username</p>
							</div>
							<div class="col-md-6 <?php if (isset(staticFunc::$formInput['phone'])) { echo 'has-error'; } ?>">
								<label for="phone">Phone Number</label>
								<input type="tel" id="phone" name="phone" maxlength="30" class="form-control" value="<?php if (isset($_POST['phone'])) { echo $_POST['phone']; } ?>" placeholder="Enter Your Phone Number" required/>
								<p class="help-block">Enter Phone Number</p>
							</div>
							<div class="col-md-6 <?php if (isset(staticFunc::$formInput['birthday'])) { echo 'has-error'; } ?>">
								<label for="birthday">Select Birthday</label>
								<input type="date" id="birthday" name="birthday" maxlength="30" class="form-control" value="<?php if (isset($_POST['birthday'])) { echo $_POST['birthday']; } ?>" placeholder="YYYY-MM-DD" required/>
								<p class="help-block">Select Birthday; Format: YYYY-MM-DD</p>
							</div>
							<div class="col-md-6 <?php if (isset(staticFunc::$formInput['question'])) { echo 'has-error'; } ?>">
								<label for="question">Answer Security Question</label><br />
								<span class="text-info bold"><?php echo $_SESSION['securityQuestion']; ?></span>
								<input type="text" id="question" name="question" maxlength="30" class="form-control" value="<?php if (isset($_POST['question'])) { echo $_POST['question']; } ?>" placeholder="Answer Your Security Question" required />
								<p class="help-block">Answer the Security Question You Set Earlier</p>
							</div>
						</div>
						<div class="row">
							<div class="form-group">
								<input type="submit" id="confirmResetSubmit" name="confirmResetSubmit" class="btn btn-info save-btn" value="Confirm Additional Details"/>
							</div>
							<input type="hidden" name="confirmResetForm">
						</div>
					</fieldset>
				</form>
		<?php 	} 
			}
		?>
		</div>
<?php
	}
	
	private function createLoginPage () {
		//If Reset Password was not completed!
		unset($_SESSION['confirmedResetId']);
		unset($_SESSION['resetSuccess']);
		if ( isset($_GET['logout']) ) {
			if ( $_GET['logout'] == 'success' ) {
				$type = 'success';
				$msg = '<b>You Have Been Successfully Logged Out</b>';
				staticFunc::alertDisplay( $type, $msg );
			}
		} elseif ( isset($_GET['access']) ) {
			if ( $_GET['access'] == 'denied' ) {
				$type = 'error';
				$msg = '<b>You Have To Be Logged In To Access This Content</b>';
				staticFunc::alertDisplay( $type, $msg, 1 );
			}
		}
?>
		<div class="hidden-xs hidden-sm col-md-8" id="login-side-img">
			<div id="mcsue-carousel" class="carousel slide" data-ride="carousel">
			  <!-- Indicators -->
			  <ol class="carousel-indicators">
				<li data-target="#mcsue-carousel" data-slide-to="0" class="active"></li>
				<li data-target="#mcsue-carousel" data-slide-to="1"></li>
				<li data-target="#mcsue-carousel" data-slide-to="2"></li>
			  </ol>

			  <!-- Wrapper for slides -->
			  <div class="carousel-inner" role="listbox">
				<div class="item active">
				  <div class="home-well">
					<div class="align-well">
						<div class="align-content">
							<div class="well-heading">WELCOME TO McSUE DESIGNS</div>
							<p class="text-center well-small">Your Home of Exquisite Ideas</p>
							<a href="about.php" class="btn btn-primary well-btn"><b>Learn More About Us</b></a>
						</div>
					</div>
				  </div>
				</div>
<?php 	$getSlidePhotos = staticFunc::getSlidePhotos( $this->pdo );
		if ( $getSlidePhotos ) {
			foreach ( $getSlidePhotos as $key => $value ) {
?>		
				<div class="item">
				  <img src="<?php echo urldecode($value['img_link']); ?>" alt="<?php echo $value['img_title']; ?>" class="img-carousel">
				  <div class="carousel-caption"><?php echo $value['img_title']; ?></div>
				</div>
<?php 		}
?>
			  </div>
			  <!-- Controls -->
			  <a class="left carousel-control" href="#mcsue-carousel" role="button" data-slide="prev">
				<span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
				<span class="sr-only">Previous</span>
			  </a>
			  <a class="right carousel-control" href="#mcsue-carousel" role="button" data-slide="next">
				<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
				<span class="sr-only">Next</span>
			  </a>
			</div>
<?php 	}
?>
		</div>
		<!--Login Form -->
		<div class="col-md-4" id="login-form">
			<img class="img-rounded img-responsive" src="img/mcsue.gif" alt="Mcsue Logo" />
			<form class="form-horizontal" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
				<div class="form-group">
					<label for="username">Username</label>
					<div class="input-group input-group-md">
						<span class="input-group-addon" id="basic-addon1"><span class="fa fa-user"></span></span>
						<input type="text" class="form-control" id="username" name="username" value="<?php if (isset($_POST['username'])) { echo $_POST['username']; } ?>" placeholder="Enter Username"  tabindex="1" aria-describedby="basic-addon1" required autofocus/>
					</div>
				</div>
				<div class="form-group">
					<label for="username">Password</label><div class="reset_password text-xs bold"  tabindex="3"><a href="forgotpassword.php">Reset Password</a></div>
					<div class="input-group input-group-md">
						<span class="input-group-addon" id="basic-addon2"><span class="fa fa-circle"></span></span>
						<input type="password" id="password" name="password" class="form-control" placeholder="Enter Password" tabindex="2" aria-describedby="basic-addon2" required/>
					</div>
				</div>
				<div class="form-group">
					<input type="submit" name="loginSubmitForm" class="btn btn-primary" value="LOG IN" id="login-btn"/>
				</div>
				<input type="hidden" name="confirmLoginForm" />
			</form>
		</div>
<?php
	}
	
	private function pageTitle () {
		$page = basename($this->page);
		switch ( $page ) {
			case "customers.php":
			echo ' Customers';
			break;
			case "customerorders.php":
			echo ' Customer Orders';
			break;
			case "customerdetails.php":
			echo ' Customer Details';
			break;
			case "editcustomer.php":
			echo ' Edit Customer Details';
			break;
			case "createcustomer.php":
			echo ' New Customer Account';
			break;
			case "createstudent.php":
			echo ' New Student Account';
			break;
			case "students.php":
			echo ' Students';
			break;
			case "editstudent.php":
			echo ' Edit Student Details';
			break;
			case "studentdetails.php":
			echo ' Student Details';
			break;
			case "staff.php":
			echo ' Staff';
			break;
			case "createstaff.php":
			echo ' New Staff Account';
			break;
			case "staffdetails.php":
			echo ' Staff Details';
			break;
			case "editstaff.php":
			echo ' Edit Staff Details';
			break;
			case "user.php":
			echo ' Personal Area';
			break;
			case "userdetails.php":
			echo ' Account Details';
			break;
			case "edituserdetails.php":
			echo ' Edit Details';
			break;
			case "logout.php":
			echo ' Logging Out . . .';
			break;
			case "index.php":
				//Removed mcsueapp
				//Changed mcsue to mcsueapp
				//if ($this->page == '/mcsueapp/index.php' ) {
				if ($this->page == '/mcsueapp/mcsue/index.php' ) {
					echo ' Dashboard';
					break;
				//Removed mcsueapp
				//} elseif ( $this->page == '/index.php' ) {
				} elseif ( $this->page == '/mcsueapp/index.php' ) {
					echo ' Login';	
					break;
				} else { 
					//Index Page of internals; Deny Access
					echo ' Access Denied';
					break;
				}
			case "forgotpassword.php":
			echo ' Reset Password';
			break;
			case "designs.php":
			echo ' Designs';
			break;
			case "designdetails.php":
			echo ' Design Details';
			break;
			case "adddesign.php":
			echo ' Add Design';
			break;
			case "editdesign.php":
			echo ' Edit Design';
			break;
			case "orders.php":
			echo ' Orders';
			break;
			case "orderdetails.php":
			echo ' Order Details';
			break;
			case "createorder.php":
			echo " Create New Order";
			break;
			case "programmes.php":
			echo ' Programmes';
			break;
			case "programmereg.php":
			echo ' Programme Registration';
			break;
			case "training.php":
			echo ' Trainings';
			break;
			case "trainingdetails.php":
			echo ' Training Details';
			break;
			case "addtraining.php":
			echo ' Add New Training';
			break;
			case "edittraining.php":
			echo ' Edit Training';
			break;
			case "timetable.php":
			echo ' Timetable';
			break;
			case "addtimetable.php":
			echo ' Add Timetable';
			break;
			case "edittimetable.php":
			echo ' Edit Timetable';
			break;
			case "results.php":
			echo ' Results';
			break;
			case "addresults.php":
			echo ' Add Result';
			break;
			case "editresults.php":
			echo ' Edit Result';
			break;
			case "assignments.php":
			echo ' View Assignments';
			break;
			case "createassignment.php":
			echo ' Create Assignment';
			break;
			case "editassignment.php":
			echo ' Edit Assignment';
			break;
			case "viewsubmissions.php":
			echo ' Assignment Submissions';
			break;
			case "courses.php":
			echo ' Add Courses';
			break;
			case "messages.php":
			echo ' Messages';
			break;
			case "personal.php":
			echo ' Personal Details';
			break;
			case "editpersonal.php":
			echo ' Edit Personal Details';
			break;
			case "finance.php":
			echo ' Financial Records';
			break;
			case "feesrecords.php":
			echo ' Fees Payment';
			break;
			case "contactus.php":
			echo ' Contact Us';
			break;
			case "feepayment.php":
			echo ' Fees Payments';
			break;
			case "reminders.php":
			echo ' Reminders';
			break;
			case "setreminder.php":
			echo ' Set Reminder';
			break;
			case "editreminder.php":
			echo ' Edit Reminder';
			break;
			case "personalreminders.php":
			echo ' Personal Reminders';
			break;
			case "certificate.php":
			echo ' Certificate';
			break;
			// Add for other Pages	
			default:
			echo ' Welcome';
			break;
		}
	}
}