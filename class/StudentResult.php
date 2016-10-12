<?php

class StudentResult extends Items implements itemDetailsInterface, UserInterface {
	
	/**	Method createResult creates new result for a student
	*	@param $studentId, $courseCode, $score information to be
	*	added to the database
	*/
	public function createResult ( $studentId, $progCode, $score, $pdo ) {
		//Set resultId Value from inherited method generateId()
		$resultId = parent::createNewId ( __CLASS__, 8, $pdo );
		//Determine score value
		$scoreValue = staticFunc::scoreValue( $score );
		//Add Result to database
		$sql = "INSERT INTO result_tbl VALUES (:resultId, :studentId, :courseCode, :score, :scoreValue, NULL)";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':resultId' => $resultId, ':studentId' => $studentId, ':courseCode' => $progCode, ':score' => $score, ':scoreValue' => $scoreValue]);
		if ($stmt->rowCount()) {
			return 'success';
		} else {
			return 'error';
		}
	}
	
	/**	Method updateResult updates the result of the student
	*	@param $pdo connection to the database
	*	@param $resultId, $courseCode, $score information to be 
	*	updated
	*/
	public function updateResult ( $studentId, $progCode, $score, $pdo ) {
		//Update Details of designs in database
		$sql = "UPDATE result_tbl SET score = :score WHERE student_id = :studentId AND prog_code = :progCode";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':progCode' => $progCode, ':score' => $score, ':studentId' => $studentId]);
		//Confirm whether there were rows updated by update query
		if ($stmt->rowCount()) {
			return 'success';
		} else {
			return 'error';
		}
	}
	
	private function getStudentsProgrammes ( $studentId ) {
		$sql = "SELECT prog_tbl.programme FROM prog_tbl INNER JOIN prog_reg_tbl ON prog_reg_tbl.prog_id = prog_tbl.prog_id AND student_id = :studentId";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':studentId' => $studentId]);
		return $stmt->fetchAll();	
	}

	private function getAllStudents () {
		$students = new Student;
		return $students->getAllUsers( 'Student', $this->pdo );
	}
	
	private function getStudentInfo ( $studentId ) {
		$sql = "SELECT CONCAT_WS(' ', CONCAT(surname,', ', firstname), othername) AS name, CONCAT_WS(', ', street, city, state, country) AS address, gender, CONCAT_WS(', ', phone, phone_alt) as phone, email, photo FROM student_tbl WHERE student_id = :studentId";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':studentId' => $studentId]);
		return $stmt->fetch();
	}
	
	private function getStudentResult ( $studentId ) {
		$sql = "SELECT prog_tbl.prog_id, prog_tbl.programme, result_tbl.score, DATE_FORMAT(prog_reg_tbl.reg_date, '%D %M, %Y') AS reg_date FROM result_tbl INNER JOIN prog_reg_tbl INNER JOIN prog_tbl INNER JOIN student_tbl ON student_tbl.student_id = result_tbl.student_id AND student_tbl.student_id = prog_reg_tbl.student_id AND prog_tbl.prog_id = result_tbl.course_code AND prog_reg_tbl.prog_id = result_tbl.course_code AND result_tbl.student_id = :studentId ORDER BY prog_reg_tbl.reg_date ASC";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':studentId' => $studentId]);
		return $stmt->fetchAll();
	}
	
	private function getAllProgrammes () {
		$programmes = new Trainings;
		return $programmes->getAllProgrammes ( $this->pdo );
	}
	
	private function getProgTitle ( $progId ) {
		$prog = new Trainings;
		return $prog->getProgrammeTitle( $progId, $this->pdo );
	}
	
	private function getProgResult ( $progCode ) {
		$sql = "SELECT prog_tbl.prog_id, prog_tbl.programme. student_tbl.student_id, CONCAT_WS(' ', student_tbl.surname, student_tbl.firstname) AS name, result_tbl.score FROM result_tbl INNER JOIN prog_tbl INNER JOIN student_tbl ON student_tbl.student_id = result_tbl.student_id AND student_tbl.student_id = prog_tbl.student_id AND prog_tbl.prog_id = result_tbl.course_code AND result_tbl.course_code = :progId";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':progId' => $progCode]);
		return $stmt->fetchAll();
	}

	private function getStudentProgResult ( $progCode, $studentId ) {
		$sql = "SELECT * FROM result_tbl WHERE prog_code = :progCode AND student_id = :studentId";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':progCode' => $progCode, ':studentId' => $studentId]);
		return $stmt->fetchAll();
	}
	
	private function getDetailsForResultCompile ( $progId, $onlyResults = NULL ) {
		$sql = "SELECT CONCAT_WS(' ', student_tbl.surname, student_tbl.firstname, student_tbl.othername) AS name, student_tbl.photo, prog_reg_tbl.student_id, result_tbl.score, prog_reg_tbl.reg_id FROM prog_reg_tbl INNER JOIN student_tbl INNER JOIN result_tbl ON student_tbl.student_id = prog_reg_tbl.student_id AND prog_reg_tbl.prog_id = result_tbl.course_code AND prog_reg_tbl.prog_id = :progId";
		if (isset($onlyResults)) {
			$sql .= " AND !ISNULL(NULLIF(result_tbl.score, ''))";
		}
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':progId' => $progId]);
		return $stmt->fetchAll();
	}
	
	
	/**
	*	VIEW PAGES
	*/
	
	protected function resultsUI () {
		echo '<div class="row add-item-row"><button class="btn btn-info btn-add-item" onclick="window.location.href=\'training.php\'"><strong>Back To Training</strong></button></div>';
		if (isset($_GET['course'])) {
			$courseCode = staticFunc::unmaskURLParam($_GET['course']);
			$getProgTitle = self::getProgTitle ( $courseCode );
			$onlyResults = 1;
			$getCourse = self::getDetailsForResultCompile ( $courseCode, $onlyResults );
			if ($getCourse) {
				$totalStudents = count($getCourse);
				$submitButtonVal = ($totalStudents == 1) ? 'Submit Result' : 'Submit Results';
				$serial = 0;
?>
		<div class="col-md-8 col-md-offset-2">
			<legend class="text-info text-center">View Course Results</legend>
			<h4 class="bold">Programme Code: <u><?php echo $courseCode; ?></u></h4>
			<h4 class="bold">Programme Title: <u><?php echo $getProgTitle; ?></u></h4>
			<h4 class="bold">Total Number of Registered Students: <u><?php echo $totalStudents; ?></u></h4>
			<table class="table table-hover table-responsive table-striped table-center">
			<tr>
				<th>S/N</th>
				<th>Image</th>
				<th>Student Name</th>
				<th>Enter Score</th>
				<th>Grade</th>
			</tr>
<?php
			foreach ( $getCourse as $key => $value ) {
				$serial++;
?>
				<tr class="text-sm">
					<td class="bold"><?php echo $serial; ?></td>
					<td><img src="<?php echo urldecode($value['photo']); ?>" class="img-thumbnail"</td>
					<td class="bold"><?php echo $value['name']; ?></td>
					<td class="bold"><?php echo $value['score']; ?></td>
					<td><div id="setGrade<?php echo $serial; ?>" class="setGrade bold <?php echo $gradeColour = (isset($value['score'])) ? staticFunc::gradeColorCode( $value['score'] ) : ''; ?>"><?php echo $gradeValue = (isset($value['score'])) ? staticFunc::scoreValue ( $value['score'] ) : '';?></div></td>
				</tr>
<?php 		}
?>
			</table>
			<button onclick="window.location.href='editresults.php?course=<?php echo staticFunc::maskURLParam($courseCode); ?>'" id="addDesignSubmit" class="btn btn-info add-item-btn">Edit Results</button>
		</div>
<?php
			} else {
				$type = 'error';
				$msg = "<b>No Student Has Registered For This Course</b>";
				staticFunc::alertDisplay ( $type, $msg );
			}
		} elseif (isset($_GET['student'])) {
			$getStudentId = staticFunc::unmaskURLParam($_GET['student']);
			$getStudent = self::getStudentInfo ( $getStudentId );
			$getResults = self::getStudentResult ( $getStudentId );
			if ($getResults) {
				$serial = 0;
?>
		<div class="col-md-10 col-md-offset-1 outline">
			<div class="cert_logo center"></div>
			<h3 class="bold text-center">McSue Training Programme</h3>
			<h4 class="bold text-center text-info">Results<hr class="hr-class"></h4>

			<div class="col-sm-8">
				<p class="student-name text-left inline"><b>Name:</b>&nbsp;<?php echo $getStudent['name']; ?></p><br />
				<p class="student-name text-left inline"><b>Address:</b>&nbsp;<?php echo $getStudent['address']; ?></p><br />
				<p class="student-name text-left inline"><b>Gender:</b>&nbsp;<?php echo $getStudent['gender']; ?></p><br />
				<p class="student-name text-left inline"><b>Phone:</b>&nbsp;<?php echo $getStudent['phone']; ?></p><br />
				<p class="student-name text-left inline"><b>Email:</b>&nbsp;<?php echo $getStudent['email']; ?></p>
			</div>
			<div class="studentPhoto inline col-sm-4"><img src="<?php echo urldecode($getStudent['photo']); ?>" class="img-round img-responsive" /></div>
			<table class="table table-hover table-responsive table-striped table-center">
			<tr class="text-md">
				<th>S/N</th>
				<th>Course Code</th>
				<th>Course Title</th>
				<th>Date Registered</th>
				<th>Date Completed</th>
				<th>Score Obtained</th>
				<th>Grade</th>
			</tr>
<?php
			foreach ( $getResults as $key => $value ) {
				$serial++;
?>
				<tr class="text-sm">
					<td><?php echo $serial; ?></td>
					<td><?php echo $value['prog_id']; ?></td>
					<td><?php echo $value['programme']; ?></td>
					<td><?php echo $value['reg_date']; ?></td>
					<td><?php echo $completed = (isset($value['complete_date'])) ? $value['complete_date'] : 'In Progress'; ?></td>
					<td><?php echo $value['score']; ?></td>
					<td><div class="setGrade"><?php echo $gradeValue = (isset($value['score'])) ? staticFunc::scoreValue ( $value['score'] ) : '';?></div></td>
				</tr>
<?php 		}
?>
			</table>
		</div>
<?php
			} else {
				$type = 'error';
				$msg = "<b>No Student Has Registered For This Course</b>";
				staticFunc::alertDisplay ( $type, $msg );
			}
		} else {
			$getProgrammes = self::getAllProgrammes();
			if ($getProgrammes) {
				echo '<h3 class="text-center text-info" id="top"><strong>COURSES RESULTS</strong><br /><small class="text-info">View Results By Courses</small></h3><hr class="hr-divide">';
				echo '<div class="row">';
				foreach ( $getProgrammes as $key => $value ) {
?>
					<div class="col-sm-6 col-md-4 home-icon">
						<a href="results.php?course=<?php echo staticFunc::maskURLParam($value['prog_id']); ?>" title="<?php echo $value['programme']; ?>"><span class="fa fa-graduation-cap home-icon"></span>
						<div class="text-center inline-block home-text"><?php echo $value['programme']; ?></div></a>
					</div>
<?php
				}
				echo '</div>';
				echo '<hr class="hr-divide"><div class="row"><button class="btn btn-info btn-add-item" onclick="window.location.href=\'addresults.php\'"><strong>Add New Results</strong></button></div><hr class="hr-padding">'; 

			}
			$getStudents = self::getAllStudents();
			if ($getStudents) {
				echo '<h3 class="text-center text-info" id="top"><strong>STUDENT\'S RESULTS</strong><br /><small class="text-info">View Each Student\'s Result</small></h3><hr class="hr-divide">';
				echo '<div class="row">';
				foreach ( $getStudents as $key => $value ) {
?>
				<div class="col-sm-6 col-md-4">
					<a href="results.php?student=<?php echo staticFunc::maskURLParam($value['userId']); ?>" title="View <?php echo $value['name']; ?>'s Result">
					<div class="thumbnail item-thumbnail">
						<img src="<?php echo urldecode($value['photo']); ?>" alt="<?php echo $value['name']; ?>" class="img-responsive" />
						<div class="caption">
							<p class="text-center item-title"><?php echo $value['name']; ?></p>
						</div>
					</div>
					</a>
				</div>
<?php
				}
				echo '</div>';
			}
		}
	}
	
	protected function addresultsUI () {
		echo '<div class="row add-item-row"><button class="btn btn-info btn-add-item" onclick="window.location.href=\'results.php\'"><strong>Back To Results</strong></button></div>';
		if (isset($_GET['course'])) {
			$course = staticFunc::unmaskURLParam($_GET['course']);
			$getProgTitle = self::getProgTitle ( $course );
			$getCourse = self::getDetailsForResultCompile ( $course );
			if ($getCourse) {
				$totalStudents = count($getCourse);
				$submitButtonVal = ($totalStudents == 1) ? 'Submit Result' : 'Submit Results';
				$serial = 0;
?>
		<div class="col-md-8 col-md-offset-2">
			<form class="form-horizontal form-add-info" id="edit-item-form" method="post" action="<?php echo basename($_SERVER['PHP_SELF'])."?course=". staticFunc::maskURLParam($_GET['course']); ?>">
				<legend class="text-info text-center">Add New Results</legend>
				<h4 class="bold">Programme Code: <u><?php echo $course; ?></u></h4>
				<h4 class="bold">Programme Title: <u><?php echo $getProgTitle; ?></u></h4>
				<h4 class="bold">Total Number of Registered Students: <u><?php echo $totalStudents; ?></u></h4>
				<table class="table table-hover table-responsive table-striped table-center">
				<tr>
					<th>S/N</th>
					<th>Image</th>
					<th>Student Name</th>
					<th>Enter Score</th>
					<th>Grade</th>
				</tr>
<?php
				foreach ( $getCourse as $key => $value ) {
					$serial++;
?>
					<tr class="text-sm">
						<td class="bold"><?php echo $serial; ?></td>
						<td><img src="<?php echo urldecode($value['photo']); ?>" class="img-thumbnail"</td>
						<td class="bold"><?php echo $value['name']; ?></td>
						<td><?php if (isset($value['score'])) { 
						echo '<span class="bold">'.$value['score'].'</span>'; 
						} else { ?>
						<input type="number" name="<?php echo staticFunc::maskURLParam($value['student_id'])?>" class="input-width bold" id="studentGrade<?php echo $serial; ?>" /><input type="hidden" name="studentGrade<?php echo $serial; ?>" value="<?php echo staticFunc::maskURLParam($value['student_id']); ?>" /></td>
						<?php } ?>
						<td><div id="setGrade<?php echo $serial; ?>" class="setGrade bold <?php echo $gradeColour = (isset($value['score'])) ? staticFunc::gradeColorCode( $value['score'] ) : ''; ?>"><?php echo $gradeValue = (isset($value['score'])) ? staticFunc::scoreValue ( $value['score'] ) : '';?></div></td>
					</tr>
<?php 			}
?>
					<tr>
						<td colspan="5"><input type="submit" id="addDesignSubmit" name="addResultSubmit" class="btn btn-info add-item-btn" value="<?php echo $submitButtonVal; ?>"/>
						<input type="hidden" name="addResultForm" /><input type="hidden" name="totalStudents" value="<?php echo $totalStudents; ?>" /><input type="hidden" name="courseCode" value="<?php echo staticFunc::maskURLParam($_GET['course']); ?>" /></td>
					</tr>
				</table>
			</form>
		</div>
<?php
			} else {
				$type = 'error';
				$msg = "<b>No Student Has Registered For This Course</b>";
				staticFunc::alertDisplay ( $type, $msg );
			}
		} else {
			$getProgrammes = self::getAllProgrammes();
			if ($getProgrammes) {
				echo '<h3 class="text-center text-info" id="top"><strong>SELECT COURSE</strong><br /><small class="text-info">Select Course To Add Result For</small></h3><hr class="hr-divide">';
				echo '<div class="row">';
				foreach ( $getProgrammes as $key => $value ) {
?>
					<div class="col-sm-6 col-md-4 home-icon">
						<a href="addresults.php?course=<?php echo staticFunc::maskURLParam($value['prog_id']); ?>" title="<?php echo $value['programme']; ?>"><span class="fa fa-graduation-cap home-icon"></span>
						<div class="text-center inline-block home-text"><?php echo $value['programme']; ?></div></a>
					</div>
<?php
				}
				echo '</div>';
			}		
		}
	}

	protected function editresultsUI () {
		echo '<div class="row add-item-row"><button class="btn btn-info btn-add-item" onclick="window.location.href=\'results.php\'"><strong>Back To Results</strong></button></div>';
		if (isset($_GET['course'])) {
			$course = staticFunc::unmaskURLParam($_GET['course']);
			$getProgTitle = self::getProgTitle ( $course );
			$getCourse = self::getDetailsForResultCompile ( $course );
			if ($getCourse) {
				$totalStudents = count($getCourse);
				$submitButtonVal = ($totalStudents == 1) ? 'Update Result' : 'Update Results';
				$serial = 0;
?>
		<div class="col-md-8 col-md-offset-2">
			<form class="form-horizontal form-add-info" id="edit-item-form" method="post" action="results.php?course=<?php echo staticFunc::maskURLParam($_GET['course']); ?>">
				<legend class="text-info text-center">Edit Results<h6 class="text-center text-info">You Cannot Edit The Results More Than Three Times</h6></legend>
				<h4 class="bold">Programme Code: <u><?php echo $course; ?></u></h4>
				<h4 class="bold">Programme Title: <u><?php echo $getProgTitle; ?></u></h4>
				<h4 class="bold">Total Number of Register Students: <u><?php echo $totalStudents; ?></u></h4>
				<table class="table table-hover table-responsive table-striped table-center">
				<tr>
					<th>S/N</th>
					<th>Image</th>
					<th>Student Name</th>
					<th>Enter Score</th>
					<th>Grade</th>
				</tr>
<?php
				foreach ( $getCourse as $key => $value ) {
					$serial += 1;
?>
					<tr>
						<td class="bold"><?php echo $serial; ?></td>
						<td><img src="<?php echo urldecode($value['photo']); ?>" class="img-thumbnail"</td>
						<td class="bold"><?php echo $value['name']; ?></td>
						<td><input type="number" name="<?php echo staticFunc::maskURLParam($value['student_id'])?>" class="input-width bold" id="studentGrade<?php echo $serial; ?>" value="<?php echo $studentScore = (isset($value['score'])) ? $value['score'] : ''; ?>" /><input type="hidden" name="studentGrade<?php echo $serial; ?>" value="<?php echo staticFunc::maskURLParam($value['student_id'])?>" /></td>
						<td><div id="setGrade<?php echo $serial; ?>" class="setGrade bold <?php echo $gradeColour = (isset($value['score'])) ? staticFunc::gradeColorCode( $value['score'] ) : ''; ?>"><?php echo $gradeValue = (isset($value['score'])) ? staticFunc::scoreValue ( $value['score'] ) : '';?></div></td>
					</tr>
<?php 			}
?>
					<tr>
						<td colspan="5"><input type="submit" id="editResultSubmit" name="editResultSubmit" class="btn btn-info add-item-btn" value="<?php echo $submitButtonVal; ?>"/>
						<input type="hidden" name="editResultForm" /><input type="hidden" name="totalStudents" value="<?php echo $totalStudents; ?>" /><input type="hidden" name="courseCode" value="<?php echo staticFunc::maskURLParam($_GET['course']); ?>" /></td>
					</tr>
				</table>
			</form>
		</div>
<?php
			}
		}
	}
	
	protected function certificateUI () {
		echo '<div class="row add-item-row"><button class="btn btn-info btn-add-item" onclick="window.location.href=\'training.php\'"><strong>Back To Trainings Dashboard</strong></button></div>';
		if (isset($_GET['student'])) {
			$getStudentId = staticFunc::unmaskURLParam($_GET['student']);
			$getStudent = self::getStudentInfo ( $getStudentId );
			$getResults = self::getStudentResult ( $getStudentId );
			if ($getResults) {
				$serial = 0;
?>
		<div class="col-md-12 outline shadow">
			<div><img src="../img/mcsue.gif" class="img-responsive img-round inline cert-img" width="130" /><div class="text-center bold cert">CERTIFICATE</div></div>
			<hr class="hr-class">
			<h4 class="text-center certify">This is to certify that <span class="bold text-lg"><?php echo $getStudent['name']; ?></span>  has successfully completed the following Programmes in McSue Training Institute:<br />
				<ul type="circle">
					<?php 
						$studentProgrammes = self::getStudentsProgrammes ( $getStudentId );
						foreach ($studentProgrammes as $key => $value) {
							$prog = $value['programme'];	
							echo "<li>$prog</li>";
						}
					?>
				</ul>
			completed this <br /><u><?php echo staticFunc::formatDate(date('d')); ?> Day of <?php echo staticFunc::formatMonth(date('m')); ?>, <?php echo date('Y'); ?></u>
			</h4>
			<div class="col-md-4">
				<img src="../img/sample1.jpg" class="img-responsive" />
				<hr class="hr-none" />
				<div class="text-center text-sm bold">Odok, Susan Mma</div>
				<div class="text-center">Chief Executive Officer</div>
			</div>
			<div class="col-md-4">
				<div class="stamp"><span class="fa fa-certificate"></span></div>
			</div>
			<div class="col-md-4">
				<img src="../img/sample2.jpg" class="img-responsive" />
				<hr class="hr-none" />
				<div class="text-center text-sm bold">Offiong, Aniekan Emmanuel</div>
				<div class="text-center">Executive Secretary</div>
			</div>

			<span class="fixed-left"></span>
			
			<span class="fixed-right"></span>
		</div>
<?php
			} else {
				$type = 'error';
				$msg = "<b>The Student's Certificate Is Not Ready</b>";
				staticFunc::alertDisplay ( $type, $msg );
			}
		} else {
			$getStudents = self::getAllStudents();
			if ($getStudents) {
				echo '<h3 class="text-center text-info" id="top"><strong>CERTIFICATES</strong><br /><small class="text-info">Prepare Student\'s Certificates</small></h3><hr class="hr-divide">';
				echo '<div class="row">';
				foreach ( $getStudents as $key => $value ) {
?>
				<div class="col-sm-6 col-md-4">
					<a href="certificate.php?student=<?php echo staticFunc::maskURLParam($value['userId']); ?>" title="View <?php echo $value['name']; ?>'s Result">
					<div class="thumbnail item-thumbnail">
						<img src="<?php echo urldecode($value['photo']); ?>" alt="<?php echo $value['name']; ?>" class="img-responsive" />
						<div class="caption">
							<p class="text-center item-title"><?php echo $value['name']; ?></p>
						</div>
					</div>
					</a>
				</div>
<?php
				}
				echo '</div>';
			}
		}
	}
}