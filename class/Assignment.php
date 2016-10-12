<?php

/**	class Assignment for functionalities regarding assignments*/
class Assignment extends Items implements itemDetailsInterface, UserInterface {
	/**	
	 *	Method createAssignment for creating new assignments 
	 *	@param $pdo for passing in connection to database
	 *	@param $staffId, $courseCode, $assignment, $deadline 
	 *	for setting details of the assignment
	 */
	public function createAssignment ( $pdo, $staffId, $courseCode, $assignment, $deadline ) {
		$assgnId = parent::createNewId ( __CLASS__, 5 );
		//Insert Assignment data into database
		$sql = "INSERT INTO assgn_tbl VALUES (:assgnId, :staffId, :course_code, :assignment, :deadline)";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':assgnId' => $assgnId, ':staffId' => $staffId, ':course_code' => $courseCode, ':assignment' => $assignment, ':deadline' => $deadline]);
		if ($stmt->rowCount()) {
			return 'success';
		} else {
			return 'error';
		}
	}
	
	public function editAssignment ( $pdo, $assgnId, $assignment, $deadline ) {
		$sql = "UPDATE assgn_tbl SET assignment = :assignment, deadline = :deadline WHERE assgn_id = :assgn_id";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':assignment' => $assignment, ':deadline' => $deadline, ':assgn_id' => $assgnId]);
		if ($stmt->rowCount()) {
			return 'success';
		} else {
			return 'error';
		}
	}
	
	public function sendAssignment ( $pdo, $studentId, $staffId, $dateOfSubmission, $solution ) {
		//Insert assignment details into database
		$sql = "INSERT INTO assgn_submit_tbl VALUES ( :submitId, :studentId, :staffId, :dateOfSubmission, :solution)";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':submitId' => $submitId, ':studentId' => $studentId, ':staffId' => $staffId, ':dateOfSubmission' => $dateOfSubmission, ':solution' => $solution]);
		$confirmResult = confirmQueryResult ( $stmt );
	}
	
	private function getAllSubmissions ( $staffId ) {
		//Gets all assignments for the particular instructor
		$sql = "SELECT * FROM assign_submit_tbl WHERE staff_id = :staff_id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':staff_id' => $staffId]);
		return $stmt->fetchAll();
	}
	
	private function getAllAssignments ( $staffId ) {
		//Gets all assignments for the particular instructor
		$sql = "SELECT assgn_tbl.assgn_id, assgn_tbl.course_code, assgn_tbl.questions_count, assgn_tbl.assignment, DATE_FORMAT(assgn_tbl.deadline, '%a., %D %M, %Y') AS deadline, DATEDIFF(assgn_tbl.deadline, curdate()) AS days_away, assgn_tbl.assgn_posted, prog_tbl.programme FROM assgn_tbl INNER JOIN prog_tbl ON assgn_tbl.course_code = prog_tbl.prog_id AND assgn_tbl.staff_id = :staff_id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':staff_id' => $staffId]);
		return $stmt->fetchAll();
	}

	private function listStudentAssignments ( $studentId ) {
		$sql = "SELECT assgn_tbl.assgn_id, assgn_tbl.course_code, assgn_tbl.assignment, DATE_FORMAT(assgn_tbl.deadline, '%a., %D %M, %Y') AS deadline, DATEDIFF(assgn_tbl.deadline, curdate()) AS days_away, prog_tbl.programme FROM assgn_tbl INNER JOIN prog_tbl INNER JOIN prog_reg_tbl ON assgn_tbl.course_code = prog_tbl.prog_id AND assgn_tbl.course_code = prog_reg_tbl.prog_id AND prog_reg_tbl.student_id = :studentId";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':student_id' => $studentId]);
		return $stmt->fetchAll();
	}
	

	/**
	*	VIEW PAGES
	*/
	
	protected function assignmentsUI () {
		if ($this->userType == 'Admin') {
			echo '<div class="row add-item-row"><button class="btn btn-info btn-add-item" onclick="window.location.href=\'createassignment.php\'"><b>Create Assignment</b></button></div>';
			$allAssignments = self::getAllAssignments ( $this->userId );
			if (!$allAssignments) {
				$type = 'error';
				$msg = 'There Are No Assignments Currently';
				staticFunc::alertDisplay($type, $msg, 1);
			} else {
				$serial = 0;
?>
				<div class="row">
					<div class="panel panel-info panel-training">
					<div class="panel-heading"><span><strong>TRAINING PROGRAMMES</strong></span></div>
						<table class="table table-striped table-hover table-responsive table-training-details">
						<tr class="text-center">
							<td><strong>S/N</strong></td>
							<td><strong>Course Code</strong></td>
							<td><strong>Course Title</strong></td>
							<td><strong>Assignment<br />Click For Details</strong></td>
							<td><strong>Submission Deadline</strong></td>
							<td><strong>Status</strong></td>
							<td></td>
						</tr>
<?php
				foreach ($allAssignments as $key => $value) {
					$serial += 1;
					$assgn_id = $value['assgn_id'];
?>
					<tr>
						<td class="text-center"><?php echo $serial.'.'; ?></td>
						<td><?php echo $value['course_code']; ?></td>
						<td><?php echo $value['programme']; ?></td>
						<td><?php 
							$getAssgnListed = explode('&next;', $value['assignment'], $value['questions_count']);
							$counting = 0;
							$question = '';
							for ($i = 0; $i < $value['questions_count']; $i++) {
								$counting += 1;
								$question .= "$counting. ". $getAssgnListed[$i] .'<br />';
							}
							echo $question;
						?></td>
						<td class="text-center"><?php echo $value['deadline']."<span class='text-xs text-light'>".staticFunc::determinePeriod($value['days_away'])."</span>"; ?></td>
						<td><?php echo $status = ($value['assgn_posted'] == 1) ? 'Posted' : 'Not Posted Yet'; ?></td>
						<td><?php echo $gotoAssignment = ($value['assgn_posted'] == 1) ? "<button onclick='window.location.href=\"viewsubmissions.php?assgn=".staticFunc::maskURLParam($assgn_id)."\"' class='btn btn-info'>View Submissions</button>" : "<button onclick='window.location.href=\"editassignment.php?assgn=".staticFunc::maskURLParam($assgn_id)."\"' class='btn btn-info'>Edit Assignment</button>"; ?>
						</td>
					</tr>
<?php
				echo '</table></div></div>';
				}
			}
		} else {

		}
	}

	protected function createassignmentUI () {
		echo '<div class="row add-item-row"><button class="btn btn-info btn-add-item" onclick="window.location.href=\'assignments.php\'"><strong>Back To Assignments</strong></button></div>';
		if (!isset($_SESSION['creatingAssgn'])) {
?>
		<div class="col-md-8 col-md-offset-2">
			<form class="form-horizontal form-add-info" id="edit-item-form" enctype="multipart/form-data" method="post" action="<?php echo basename($_SERVER['PHP_SELF']); ?>">
				<fieldset>
				<legend class="text-info text-center">Create Assignment</legend>
					<div class="row">
						<div class="col-md-6">
							<label for="selectCourse">Select Course</label>
							<select name="selectCourse" id="selectCourse" class="select-full-width form-inline" required>
								<option value='0' hidden>- - Select Programme - -</option>
								<?php
									$courses = new Trainings;
									$getStaffCourses = $courses->getAllProgrammes ( $this->pdo, $this->userId );
									if ( $getStaffCourses ) {
										foreach ($getStaffCourses as $key => $value ) {
											if (isset($_POST['selectCourse']) && $_POST['selectCourse'] == staticFunc::maskURLParam($value['prog_id'])) {
												echo '<option value=\''.staticFunc::maskURLParam($value['prog_id']).'\' selected>'.$value['programme'].'</option>';
											} else {
												echo '<option value=\''.staticFunc::maskURLParam($value['prog_id']).'\' >'.$value['programme'].'</option>';
											}
										}
									}
								?>
							</select>
							<p class="help-block">Please select the Intended Course</p>		
						</div>
						<div class="col-md-6">
							<label for="assignmentTitle">Assignment Title</label>
							<input type="text" id="assignmentTitle" name="assignmentTitle" maxlength="30" class="form-control" value="<?php if (isset($_POST['assignmentTitle'])) { echo $_POST['assignmentTitle']; }?>" placeholder="Enter Assignment Title" />
							<p class="help-block">Select a title/topic for the assignment</p>
						</div>
						<div class="col-md-6">
							<label for="numberofquestions">Number of Questions</label>
							<select name="numberofquestions" id="numberofquestions" class="item-select form-inline" required>
								<option value="0" hidden>- Select Number -</option>
								<?php
									$durationNo = range(1,15);
									foreach ($durationNo as $number) {
										if ( isset($_POST['numberofquestions']) && $_POST['numberofquestions'] == $number ) {
											echo "<option value={$number} selected>{$number}</option>";
										} else {
											echo "<option value={$number}>{$number}</option>";
										}
									}
								?>
							</select>
							<p class="help-block">Select Time Deadline</p>
						</div>
						<div class="col-md-6">
							<label for="datepicker">Assignment Submission Deadline</label>
							<input type="date" id="datepicker" name="assgnDeadlineDate" maxlength="10" class="form-control" value="<?php if (isset($_POST['assgnDeadlineDate'])) { echo $_POST['assgnDeadlineDate']; }?>" placeholder="YYYY-MM-DD" />
							<p class="help-block">Select the deadline for submission (Date)</p>
						</div>
						<div class="col-md-6 col-md-offset-6">
							<label for="assgnDeadlineTime">Add Time To Deadline</label>
							<button class="btn btn-info" id="addTimeDeadline">Click To Add</button>
							<div class="pad hidden" id="selectTimeDeadline" >
								<select name="assgnDeadlineTime" id="assgnDeadlineTime" class="item-select form-inline" required>
									<option value="0" hidden>- Select Time -</option>
									<?php
										$durationNo = range(01,24);
										foreach ($durationNo as $number) {
											$timeAll = staticFunc::timeAMPM ( $number );
											if ( isset($_POST['assgnDeadlineTime']) && $_POST['assgnDeadlineTime'] == $number ) {
												echo "<option value={$number} selected>{$timeAll}</option>";
											} else {
												echo "<option value={$number}>{$timeAll}</option>";
											}
										}
									?>
								</select>
							</div>
							<p class="help-block">Select Time Deadline</p>
						</div>
					</div>
					<div class="form-group">
						<input type="submit" id="addAssgnSubmit" name="addAssgnSubmit" class="btn btn-info add-item-btn" value="Add Questions"/>
						<input type="hidden" name="addAssgnForm" />
					</div>
				</fieldset>
			</form>
		</div>
<?php	
		} else {
?>
		<div class="col-md-8 col-md-offset-2">
			<form class="form-horizontal form-add-info" id="edit-item-form" enctype="multipart/form-data" method="post" action="<?php echo basename($_SERVER['PHP_SELF']); ?>">
				<fieldset>
				<div class="text-info text-center bold text-lg">Add Questions</div>
					<div class="col-md-10 col-md-offset-1">
						<table class="table table-responsive table-no-borders text-sm">
							<tr>
								<td class="bold">Course:</td>
								<td><?php 
									$courses = new Trainings;
									echo $courses->getProgrammeTitle ( $_SESSION['creatingAssgn']['progId'], $this->pdo );
								?></td>
							</tr>
							<tr>
								<td class="bold">Assignment Title:</td>
								<td><?php echo $_SESSION['creatingAssgn']['assgnTitle'];
								?></td>
							</tr>
							<tr>
								<td class="bold">Deadline (Date | Time):</td>
								<td><?php echo staticFunc::formatDateTime ( $_SESSION['creatingAssgn']['deadline'] );
								?></td>
							</tr>
							<tr>
								<td class="bold">Number of Questions:</td>
								<td id="setQuestionsNumber"><?php echo $_SESSION['creatingAssgn']['numberofquestions'];
								?></td>
							</tr>
						</table>
					</div>
					<div class="row text-left">
					<div class="float-right"><label for="addAnotherQuestion" class="text-info text-xs">Click To Add Another Question</label>&nbsp;<button class="btn btn-info btn-plus bold" id="addAnotherQuestion" value="<?php echo $_SESSION['creatingAssgn']['numberofquestions']; ?>">+</button></div>
					</div>
					<div class="row" id="questionsRow">
						<?php for ($i = 0; $i < $_SESSION['creatingAssgn']['numberofquestions']; $i++) { ?>
						<div class="col-md-6">
							<label for="question<?php echo $i+1; ?>">Question <?php echo $i+1; ?></label>
							<textarea rows='3' class='form-control full-width' id="question<?php echo $i + 1; ?>" name='questions[]' placeholder='Enter Question <?php echo $i+1; ?>'></textarea>
						</div>
						<?php } ?>
					</div>
					<div class="form-group pad">
						<input type="submit" id="addQuesSubmit" name="addQuesSubmit" class="btn btn-info add-item-btn" value="Submit Questions"/>
						<input type="hidden" name="addQuesForm" id="addQuesForm" value="<?php echo $i; ?>"/>
					</div>
				</fieldset>
			</form>
		</div>
<?php	
		}
	}

	protected function viewsubmissionsUI () {


	}
}