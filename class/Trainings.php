<?php

class Trainings extends Items implements itemDetailsInterface, UserInterface {
	
	private $courseNumber;
	private $progId;

	public function createTraining ( $programme, $details, $duration, $fees, $pdo ) {
		//generate Id for the programme
		$this->progId = parent::createNewId ( __CLASS__ );
		//Add Training information to database
		$sql = "INSERT INTO prog_tbl VALUES ( :progId, :programme, :details, :duration, :fees, curdate() )";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':progId' => $this->progId, ':programme' => $programme, ':details' => $details, ':duration' => $duration, ':fees' => $fees]);
		if ($stmt->rowCount()) {
			staticFunc::redirect('programmes.php?add=success');
		} else {
			staticFunc::redirect('programmes.php?add=failed');
		}
	}
	
	public function updateTraining ( $progId, $programme, $details, $duration, $fees, $pdo ) {
		$sql = "UPDATE prog_tbl SET programme = :programme, details = :details, duration = :duration, fees = :fees WHERE prog_id = :progId";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':programme' => $programme, ':details' => $details, ':duration' => $duration, ':fees' => $fees, ':progId' => $progId]);
		if ($stmt->rowCount()) {
			staticFunc::redirect('programmes.php?programme='.staticFunc::maskURLParam($progId).'&update=success');
		} else {
			staticFunc::redirect('programmes.php?programme='.staticFunc::maskURLParam($progId).'&update=failed');
		}
	}

	public function getAllProgrammes ( $pdo, $staffId = NULL ) {
		$sql = "SELECT prog_id, programme FROM prog_tbl";
		if (isset($staffId)) {
			$sql = 'SELECT prog_tbl.prog_id, prog_tbl.programme, prog_staff_tbl.prog_staff_id FROM prog_tbl INNER JOIN prog_staff_tbl ON prog_tbl.prog_id = prog_staff_tbl.prog_id AND prog_staff_tbl.staff_id = :staff_id';
			$stmt = $pdo->prepare($sql);
			$stmt->execute([':staff_id' => $staffId]);
			return $stmt->fetchAll(); 
		}
		return $stmt = $pdo->query($sql)->fetchAll();
	}

	private function generateCourseCode ( $programme ) {
		$progA = explode(' ', $programme, 3);
		if ( count($progId) >= 3 ) {
			substr($progId[0], 0, 1) . substr($progId[1], 0, 1) . substr($progId[2], 0, 1);
		} elseif ( count($progId) == 2 ) {
			substr($progId[0], 0, 1). substr($progId[1], 0, 1) . 'M';
		} else {
			substr($progId[0], 0, 1). 'MD';
		}
	}
	
	private function generateCourseNumber () {
		if (isset($this->courseNumber)) {
			if ( substr($this->courseNumber, 0, 1) < 9 ) {
				$this->courseNumber = substr($this->courseNumber, 0, 1) + 1 . substr($this->courseNumber, 1, 1) . substr($this->courseNumber, 2, 1);
			} else {
				$this->courseNumber = '1' . substr($this->courseNumber, 1, 1) + 1 . substr($this->courseNumber, 2, 1);
			}
		} else {
			$this->courseNumber = '101';
		}
	}
	
	public function getProgrammeTitle ( $progId, $pdo = NULL ) {
		$sql = "SELECT programme FROM prog_tbl WHERE prog_id = :progId";
		$stmt = (isset($pdo)) ? $pdo->prepare($sql): $this->pdo->prepare($sql);
		$stmt->execute([':progId' => $progId]);
		$programme = $stmt->fetchColumn();
		if ($programme) {
			return $programme;
		}
	}
	
	private function getDetails ( $itemId ) {
		//Returns details of given item
		$sql = "SELECT prog_id, programme, details, duration, CONCAT('N ', FORMAT(fees, 2)) as fees, fees as calc_fees FROM prog_tbl WHERE prog_id = :id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':id' => $itemId]);
		$this->detailsId = $itemId;
		return $stmt->fetchAll();
	}
	
	private function confirmProgrammeInTimetable ( $itemId ) {
		$sql = "SELECT prog_id FROM timetable_tbl WHERE prog_id = :itemId";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':itemId' => $itemId]);
		return $stmt->fetchColumn();
	}
	

	/**
	*	VIEW PAGES
	*/

	protected function trainingUI () {
		if ( $this->userType == 'Admin' ) {
?>
			<div class="row">
				<div class="col-sm-6 col-md-4 home-icon">
					<a href="programmes.php" title="Programmes"><span class="fa fa-university home-icon"></span>
					<div class="text-center inline-block home-text">Programmes</div></a>
				</div>
				<div class="col-sm-6 col-md-4 home-icon">
					<a href="students.php" title="Students"><span class="fa fa-graduation-cap home-icon"></span>
					<div class="text-center inline-block home-text">Students</div></a>
				</div>
				<div class="col-sm-6 col-md-4 home-icon">
					<a href="timetable.php" title="Timetable"><span class="glyphicon glyphicon-time home-icon"></span>
					<div class="text-center inline-block home-text">Timetable</div></a>
				</div>
				<div class="col-sm-6 col-md-4 home-icon">
					<a href="results.php" title="Results"><span class="fa fa-book home-icon"></span>
					<div class="text-center inline-block home-text">Results</div></a>
				</div>
				<div class="col-sm-6 col-md-4 home-icon">
					<a href="assignments.php" title="Assignments"><span class="fa fa-calculator home-icon"></span>
					<div class="text-center inline-block home-text">Assignments</div></a>
				</div>
				<div class="col-sm-6 col-md-4 home-icon">
					<a href="feesrecords.php" title="Fees Payment"><span class="fa fa-money home-icon"></span>
					<div class="text-center inline-block home-text">Payments</div></a>
				</div>
				<div class="col-sm-6 col-md-4 home-icon">
					<a href="certificate.php" title="Certficate"><span class="fa fa-certificate home-icon"></span>
					<div class="text-center inline-block home-text">Certificates</div></a>
				</div>
			</div>
<?php	
		} elseif ( $this->userType == 'Customer' || $this->userType == 'Student' ) {
?>
			<div class="row">
				<div class="col-sm-6 col-md-4 home-icon">
					<a href="programmes.php" title="Programmes"><span class="fa fa-university home-icon"></span>
					<div class="text-center inline-block home-text">Programmes</div></a>
				</div>
				<div class="col-sm-6 col-md-4 home-icon">
					<a href="students.php" title="Students"><span class="fa fa-user home-icon"></span>
					<div class="text-center inline-block home-text">Personal Corner</div></a>
				</div>
				<div class="col-sm-6 col-md-4 home-icon">
					<a href="timetable.php" title="Timetable"><span class="glyphicon glyphicon-time home-icon"></span>
					<div class="text-center inline-block home-text">Timetable</div></a>
				</div>
				<div class="col-sm-6 col-md-4 home-icon">
					<a href="results.php" title="Results"><span class="fa fa-book home-icon"></span>
					<div class="text-center inline-block home-text">Results</div></a>
				</div>
				<div class="col-sm-6 col-md-4 home-icon">
					<a href="assignments.php" title="Assignments"><span class="fa fa-calculator home-icon"></span>
					<div class="text-center inline-block home-text">Assignments</div></a>
				</div>
				<div class="col-sm-6 col-md-4 home-icon">
					<a href="feesrecords.php" title="Fees Payment"><span class="fa fa-money home-icon"></span>
					<div class="text-center inline-block home-text">Payments</div></a>
				</div>
				<div class="col-sm-6 col-md-4 home-icon">
					<a href="certificate.php" title="Certficate"><span class="fa fa-certificate home-icon"></span>
					<div class="text-center inline-block home-text">Certificates</div></a>
				</div>
			</div>
<?php
		}
	}
	
	private function getProgrammes () {
		$training = self::getItems( __CLASS__, $this->tableLimit, $this->userId );
		if ( !is_array($training) || empty($training) ) {
			$type = 'error';
			$msg = ( $this->userType == 'Admin') ? 'There Is No Training Information Available.' : 'You Have Not Registered For Any Training Programm Yet.';
			$link = 'index.php';
			$linkValue = 'Back To Home';
			//Display Alert;
			staticFunc::alertDisplay ( $type, $msg, $link, $linkValue );
		} else {
			if ( $this->userType == 'Customer' || $this->userType == 'Student' ) {
				echo '<div class="row add-item-row"><button class="btn btn-info btn-add-item" onclick="window.location.href=\'index.php\'"><b>Back To Home</b></button></div>';
				if (isset($_GET['programme'])) {	
					if ($_GET['programme'] == 'cancel') {
						unset($_SESSION['registerProgramme']);
						$type = 'info';
						$msg = '<b>Your Registration Has Been Cancelled</b>';
						staticFunc::alertDisplay( $type, $msg );
					}
				}
			}
?>
			<div class="row">
				<div class="panel panel-info panel-training">
				<div class="panel-heading"><span><strong>TRAINING PROGRAMMES</strong></span></div>
					<table class="table table-striped table-hover table-responsive table-training-details">
					<tr class="text-center">
						<td><strong>Programme</strong></td>
						<td><strong>Programme Details</strong></td>
						<td><strong>Duration</strong></td>
						<td><strong>Fees</strong></td>
						<?php if ( $this->userType == 'Customer' || $this->userType == 'Student' ) {
							echo '<td></td>';
						}  
						if ( $this->userType == 'Admin') {
							echo '<td></td><td></td><td></td>';
						} ?>
					</tr>
<?php				
			foreach ( $training as $key => $value ) {
				if (isset($_SESSION['registerProgramme']) && in_array($value['prog_id'], $_SESSION['registerProgramme'])) {
					$disableRegisterButton =  'disabled';
					$registerED = 'Registered';
					$btnDisplay = 'btn-success';
				} else {
					$disableRegisterButton =  '';
					$registerED = 'Register';
					$btnDisplay = 'btn-info';
				}
?>
				<tr class="text-center">
					<td class="text-left"><strong><?php echo $value['programme']; ?></strong></td>
					<td class="text-left"><?php echo $value['details']; ?></td>
					<td><?php echo $value['duration']; ?></td>
					<td><?php echo number_format($value['fees'], 2); ?></td>
					<?php if ( $this->userType == 'Customer' || $this->userType == 'Student' || $this->userType == 'CuStudent') {
?>
						<td><button class="btn <?php echo $btnDisplay; ?>" onclick="window.location.href='programmereg.php?programme=<?php echo staticFunc::maskURLParam($value['prog_id']); ?>'" <?php echo $disableRegisterButton; ?> ><?php echo $registerED; ?></button></td>
<?php
					}
					if ( $this->userType == 'Admin' ) {
						if ( self::confirmProgrammeInTimetable($value['prog_id']) ) {
							echo "<td><button class='btn btn-info' onclick=\"window.location.href='addtimetable.php?programme=". staticFunc::maskURLParam($value['prog_id']) ."'\">New Timetable Slot</button></td>";
						} else {
?>	
						<td><button class="btn btn-primary" onclick="window.location.href='addtimetable.php?programme=<?php echo staticFunc::maskURLParam($value['prog_id']); ?>'">Add To Timetable</button></td>
<?php }
?>						
						<td><button class='btn btn-info' onclick="window.location.href='edittraining.php?programme=<?php echo staticFunc::maskURLParam($value['prog_id']); ?>'">Edit</button></td>
						<td><button class="btn btn-danger progId" id="<?php echo staticFunc::maskURLParam($value['prog_id']); ?>" value="<?php echo $value['programme']; ?>" data-toggle="modal" data-target="#myModalDelete">Delete</button></td>
<?php
					}
			echo '</tr>';
			}
			echo '</table>
			</div></div>';
			Paginate::displayPageLink();
			if ( $this->userType == 'Admin' ) {
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
						<form method="post" id="modalFormProg">
							<div class="modal-body">
								<h4 class="text-center to-close">Are You Sure You Want To Delete This Programme?</h4>
								<h1 class="text-center to-close" id="progName"></h1>
								<span class="text-center to-close">The Programme will also be deleted from the Timetable</span>
								<span class="text-center to-close">This action cannot be undone!</span>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-info btn-order" data-dismiss="modal">No</button>
								<button type="button" class="btn btn-danger btn-order" name="deleteTrainingSubmit" id="deleteTrainingSubmit" data-dismiss="modal">Yes! Delete</button>
							</div>
							<input type="hidden" name="deleteTrainingForm" id="deleteTrainingForm" />
							<input type="hidden" name="deleteTrainingConfirm" id="deleteTrainingConfirm" />
						</form>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->
			</div><!-- /.modal -->
<?php
			}
		}
	}

	protected function programmesUI () {
		if ( $this->userType == 'Admin' ) {
			echo '<div class="row add-item-row"><button class="btn btn-info btn-add-item" onclick="window.location.href=\'addtraining.php\'"><b>Add New Programme</b></button></div>';
			if (isset($_GET['programme'])) {
				if (isset($_GET['update'])) {
					$programmeId = staticFunc::unmaskURLParam($_GET['programme']);
					if ($programme = self::getProgrammeTitle($programmeId)) {
						if ( $_GET['update'] == 'success' ) {
							$type = 'success';
							$msg = "The Programme <br /><b> $programme </b><br /> was successfully Updated";
							staticFunc::alertDisplay( $type, $msg );
						} elseif ( $_GET['update'] == 'failed' ) {
							$type = 'error';
							$msg = "Your attempt  to update the Programme <br /> $programme <br /> was not successful";
							staticFunc::alertDisplay( $type, $msg );
						}
					}
				}
			} elseif (isset($_GET['add'])) {
				if ( $_GET['add'] == 'success' ) {
					$type = 'success';
					$msg = "The Programme was successfully Added";
					staticFunc::alertDisplay( $type, $msg );	
				} elseif ( $_GET['add'] == 'success' ) {
					$type = 'error';
					$msg = "There Was An Error Adding The Programme";
					staticFunc::alertDisplay( $type, $msg );	
				}
			}
		}
		self::getProgrammes();
	}
	
	protected function addtrainingUI () {
		echo "<div class='row add-item-row'><button class='btn btn-info btn-add-item' onclick=\"window.location.href='programmes.php'\"><strong>Back To Programmes</strong></button></div>";
?>
		<div class="col-md-8 col-md-offset-2">
			<form class="form-horizontal form-add-info" id="edit-item-form" enctype="multipart/form-data" method="post" action="<?php echo basename($_SERVER['PHP_SELF']); ?>">
				<fieldset>
				<legend class="text-info text-center">Add Programme</legend>
					<div class="row">
						<div class="col-sm-8 <?php if (isset(staticFunc::$formInput['programme'])) { echo 'has-error'; } ?>">
							<label for="programme">Programme Title</label>
							<input type="text" class="form-control" id="programme" maxlength="30" name="programme" value="<?php if (isset($_POST['programme'])) { echo $_POST['programme']; } ?>" placeholder="Enter Programme Title" required/>
							<p class="help-block">Title cannot be more than 30 characters</p>		
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12 <?php if (isset(staticFunc::$formInput['details'])) { echo 'has-error'; } ?>">
							<label for="details">Programme Details</label>
							<textarea name="details" id="details" maxlength="150" rows="3" placeholder="Enter Details of Programme" class="form-control" required><?php if (isset($_POST['details'])) { echo $_POST['details']; } ?></textarea>
							<p class="help-block">Details should be maximum of 150 characters</p>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-8">
							<label for="duration_number">Programme Duration</label>
							<div class="input-group">
								<select name="duration_number" class="item-select form-inline">
									<option value="0" hidden>- Select Number -</option>
									<?php
									$durationNo = range(1,12);
									foreach ($durationNo as $number) {
										if ( isset($_POST['durationNumber']) && $_POST['durationNumber'] == $number) {
											echo "<option value={$number} selected=\"selected\">{$number}</option>";
										} else {
											echo "<option value={$number}>{$number}</option>";
										}
									}
									?>
								</select>
								<select name="duration_period" class=" item-select form-inline">
									<option value="0" hidden> - Select Period - </option>
									<option value="1" <?php if (isset($_POST['durationPeriod']) && $_POST['durationPeriod'] == 1 ) { echo 'selected'; } ?> >Days</option>
									<option value="2" <?php if (isset($_POST['durationPeriod']) && $_POST['durationPeriod'] == 2 ) { echo 'selected'; }?> >Weeks</option>
									<option value="3" <?php if (isset($_POST['durationPeriod']) && $_POST['durationPeriod'] == 3 ) { echo 'selected'; }?> >Months</option>
									<option value="4" <?php if (isset($_POST['durationPeriod']) && $_POST['durationPeriod'] == 4 ) { echo 'selected'; }?> >Years</option>
								</select>
							</div>						
						</div>
					</div>
					<p class="help-block">Select Number + Period for Programme</p>
					<div class="row">
						<div class="col-sm-5 <?php if (isset(staticFunc::$formInput['fees'])) { echo 'has-error'; } ?>">
							<label for="fees">Programme Fees</label>
							<div class="input-group">
								<div class="input-group-addon">N</div>
								<input type="text" id="fees" name="fees" maxlength="8" class="form-control" value="<?php if (isset($_POST['fees'])) { echo $_POST['fees']; } ?>" placeholder="Enter Fees" required/>
								<div class="input-group-addon">.00</div>
							</div>
						</div>					
					</div>
					<p class="help-block">Enter Amount in numerical values only: 10000</p>
					<div class="form-group">
						<input type="submit" id="addTrainingSubmit" name="addTrainingSubmit" class="btn btn-info save-btn" value="Create Programme"/>
					</div>
					<input type="hidden" name="addTrainingForm" />
				</fieldset>
			</form>
		</div>
<?php
	}
	
	protected function edittrainingUI () {
		if (!isset($_GET['programme'])) {
			staticFunc::errorPage( 'error' );
		} else {
			$getTrainingId = staticFunc::unmaskURLParam($_GET['programme']);
			$editTraining = self::getDetails( $getTrainingId );
			if (!is_array($editTraining) || empty($editTraining)) {
				staticFunc::errorPage( 'error' );
			} else {
				echo "<div class='row add-item-row'><button class='btn btn-info btn-add-item' onclick=\"window.location.href='programmes.php'\"><strong>Back To Programmes</strong></button></div>";
				foreach ($editTraining as $key => $value ) {
?>
		<div class="col-md-8 col-md-offset-2">
			<form class="form-horizontal form-add-info" id="edit-item-form" enctype="multipart/form-data" method="post" action="<?php echo basename($_SERVER['PHP_SELF']).'?programme='.staticFunc::maskURLParam($value['prog_id']); ?>">
				<fieldset>
				<legend class="text-info text-center">Edit Programme</legend>
					<div class="row">
						<div class="col-sm-8 <?php if (isset(staticFunc::$formInput['programme'])) { echo 'has-error'; } ?>">
							<label for="programme">Design Title</label>
							<input type="text" class="form-control" id="programme" maxlength="30" name="programme" value="<?php echo $value['programme']; ?>" placeholder="Enter Programme Title" required/>
							<p class="help-block">Title cannot be more than 30 characters</p>		
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12 <?php if (isset(staticFunc::$formInput['details'])) { echo 'has-error'; } ?>">
							<label for="details">Programme Details</label>
							<textarea name="details" id="details" maxlength="150" col="10" placeholder="Enter Details of Programme" class="form-control" required><?php echo $value['details']; ?></textarea>
							<p class="help-block">Details should be maximum of 150 characters</p>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-8">
						<?php $duration = explode(' ', $value['duration'], 2); ?>
							<label for="duration_number">Programme Duration</label>
							<div class="input-group">
								<select name="duration_number" class="item-select form-inline">
									<option value="0" hidden>- Select Number -</option>
									<?php
									$durationNo = range(1,12);
									foreach ($durationNo as $number) {
										if ($duration[0] == $number) {
											echo "<option value={$number} selected=\"selected\">{$number}</option>";
										} else {
											echo "<option value={$number}>{$number}</option>";
										}
									}
									?>
								</select>
								<select name="duration_period" class=" item-select form-inline">
									<option value="0" hidden> - Select Period - </option>
									<option value="1" <?php if ($duration[1] == 'Days') { echo 'selected'; }?> >Days</option>
									<option value="2" <?php if ($duration[1] == 'Weeks' ) { echo 'selected'; }?> >Weeks</option>
									<option value="3" <?php if ($duration[1] == 'Months' ) { echo 'selected'; }?> >Months</option>
									<option value="4" <?php if ($duration[1] == 'Years' ) { echo 'selected'; }?> >Years</option>
								</select>
							</div>
						</div>
					</div>
					<p class="help-block">Select Number + Period for Programme</p>
					<div class="row">
						<div class="col-sm-5 <?php if (isset(staticFunc::$formInput['fees'])) { echo 'has-error'; } ?>">
							<label for="fees">Programme Fees</label>
							<div class="input-group">
								<div class="input-group-addon">N</div>
								<input type="text" id="fees" name="fees" maxlength="8" class="form-control" value="<?php echo $value['fees']; ?>" placeholder="Enter Fees" required/>
								<div class="input-group-addon">.00</div>
							</div>
						</div>					
					</div>
					<p class="help-block">Enter Amount in numerical values only: 10000</p>
					<div class="form-group">
						<input type="submit" id="editTrainingSubmit" name="editTrainingSubmit" class="btn btn-info save-btn" value="Save Changes"/>
					</div>
					<input type="hidden" name="editTrainingForm" value="<?php echo staticFunc::maskURLParam($value['prog_id']); ?>"/>
					<input type="hidden" name="editTraining" value="<?php echo staticFunc::maskURLParam($value['programme']); ?>"/>
				</fieldset>
			</form>
		</div>
<?php
				}
			}
		}
	}
	
	protected function programmeregUI () {
		if (!isset($_GET['programme'])) {
			$type = 'error';
			$msg = 'You Did Not Select A Programme To Register For! <br />Please click to return and select Desired Programme';
			$link = 'programmes.php';
			$linkValue = 'Go Back To Programmes';
			staticFunc::alertDisplay ( $type, $msg, $link, $linkValue );
		} else {
			echo "<div class='row add-item-row'><button class='btn btn-info btn-add-item' onclick=\"window.location.href='programmes.php?programme=cancel'\"><strong>Cancel Registration</strong></button></div>";
			$progId = staticFunc::unmaskURLParam($_GET['programme']);
			$progTitle = self::getProgrammeTitle($progId);
			$progDetails = self::getDetails($progId);
			if (!progDetails) {
				$type = 'error';
				$msg = 'There Was An Error In Your Programme Selection.';
				staticFunc::alertDisplay($type, $msg, 1);
			} else {
				//To Track Programmes/Trainings selected to register for
				if (!isset($_SESSION['registerProgramme'])) {
					$_SESSION['registerProgramme'] = array($progId => $progId);
				} else {
					if (!in_array($progId, $_SESSION['registerProgramme']))
						//Adding array with $key and and $value
					array_push($_SESSION['registerProgramme'], $progId);
				}
				var_dump($_SESSION['registerProgramme']);
?>
				<div class="row">
				<?php if (count($_SESSION['registerProgramme']) < 2) {	?>
				<div class="panel panel-info panel-new">
				<div class="panel-heading"><span><strong>REGISTRATION DETAILS</strong></span></div>
					<table class="table table-striped table-hover table-responsive table-training-details">
<?php				
					foreach ( $progDetails as $key => $value ) {
?>
					<tr>
						<td class="bold">Programme Code</td>
						<td><?php echo $value['prog_id']; ?></td>
					</tr>
					<tr>
						<td class="bold">Programme Title</td>
						<td><?php echo $value['programme']; ?></td>
					</tr>
					<tr>
						<td class="bold">Programme Details</td>
						<td><?php echo $value['details']; ?></td>
					</tr>
					<tr>
						<td class="bold">Duration</td>
						<td><?php echo $value['duration']; ?></td>
					</tr>
					<tr>
						<td class="bold">Fees</td>
						<td><?php echo $value['fees']; ?></td>
					</tr>
					<?php if (count($_SESSION['registerProgramme']) <= 1) { ?>
						<tr>
							<td colspan="2"><button class="btn btn-info btn-add-item" onclick="window.location.href='confirmprogramme.php'">Register For This Programme</button></td>
						</tr>
					<?php } ?>
<?php
				}
			echo '</table></div></div>';

				} else {
					$totalAmount = 0;
?>
				<form method="post" action="<?php echo $_SERVER['PHP_SELF'].'?programme='.$_GET['programme']; ?>">
				<div class="panel panel-info panel-item-details">
				<div class="panel-heading"><span><strong>ALL REGISTERED PROGRAMMES</strong></span></div>
				<table class="table table-striped table-hover table-responsive table-training-details">
				<tr class="text-center">
					<td><strong>Programme</strong></td>
					<td><strong>Duration</strong></td>
					<td><strong>Fees</strong></td>
					<td></td>
				</tr>
<?php				
					foreach ( $_SESSION['registerProgramme'] as $value ) {
					$getDetails = self::getDetails($value);
					
?>
					<tr class="text-center text-sm">
						<td class="text-left"><?php echo $getDetails[0]['programme']; ?></td>
						<td><?php echo $getDetails[0]['duration']; ?></td>
						<td><?php echo $getDetails[0]['fees']; ?></td>
						<td><button class="btn btn-danger" type="submit" name="submitDeleteCourse" value="<?php echo $getDetails[0]['prog_id']; ?>">Remove</button><input type="hidden" name="deleteCourseForm" value="<?php echo staticFunc::maskURLParam($getDetails[0]['prog_id']); ?>" /></td>
					</tr>
<?php
					$totalAmount += $getDetails[0]['calc_fees'];
					}
?>
				<tr>
					<td colspan='2' class='text-right'>Total Fees Payable</td>
					<td class='text-center'><?php echo "N ".number_format($totalAmount, 2); ?></td>
					<td></td>
				</tr>
				<tr>
					<td colspan="4"><button class="btn btn-info btn-add-item" onclick="window.location.href='confirmprogramme.php'">Register For These Programmes</button></td>
				</tr>
<?php
				echo '</table></div></form>';
				echo '</div>';
				}
				echo '<button class="btn btn-info btn-add-item" onclick="window.location.href=\'programmes.php\'">Add Another Programme</button>';
			}
		}
	}
}