<?php

class Timetables extends Items implements itemDetailsInterface, UserInterface {
	
	public function createTimetable ( $day, $startTime, $duration, $progId, $venue, $pdo ) {
		$timetableId = parent::createNewId ( __CLASS__, 5 );
		$sql = "INSERT INTO timetable_tbl VALUES ( :timetableId, :progId, :day, :startTime, :duration, :venue )";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':timetableId' => $timetableId, ':progId' => $progId, ':day' => $day, ':startTime' => $startTime, ':duration' => $duration, ':venue' => $venue]);
		if ($stmt->rowCount() > 0) {
			staticFunc::redirect('timetable.php?create=success&programme='.staticFunc::maskURLParam($timetableId));
		} else {
			staticFunc::redirect('timetable.php?create=failed');
		}
	}
	
	public function updateTimetable ( $timetableId, $day, $startTime, $duration, $venue, $pdo ) {
		$sql = "UPDATE timetable_tbl SET day = :day, start_time = :startTime, duration = :duration, venue = :venue WHERE timetable_id = :timetableId";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':day' => $day, ':startTime' => $startTime, ':duration' => $duration, ':venue' => $venue, ':timetableId' => $timetableId]);
		if ($stmt->rowCount() > 0) {
			staticFunc::redirect('timetable.php?update=success&programme='.staticFunc::maskURLParam($timetableId));
		} else {
			staticFunc::redirect('timetable.php?update=failed');
		}
	}
	
	public function deleteProgramFromTimetable ( $timetableId, $pdo ) {
		$deletedProgramme = self::getProgrammeTitle( $timetableId, $pdo );
		$sql = "DELETE FROM timetable_tbl WHERE timetable_id = :timetableId";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':timetableId' => $timetableId]);
		if ($stmt->rowCount()) {
			staticFunc::redirect('timetable.php?delete=success&programme='.staticFunc::maskURLParam($deletedProgramme));
		} else {
			staticFunc::redirect('timetable.php?delete=failed');
		}
	}
	
	private function getTimetable ( $timetableId ) {
		$sql = "SELECT timetable_tbl.timetable_id, timetable_tbl.day, DATE_FORMAT(timetable_tbl.start_time, '%k') as start_time, timetable_tbl.duration, timetable_tbl.prog_id, timetable_tbl.venue, prog_tbl.programme FROM timetable_tbl INNER JOIN prog_tbl ON timetable_tbl.prog_id = prog_tbl.prog_id AND timetable_tbl.timetable_id = :timetableId";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':timetableId' => $timetableId]);
		return $stmt->fetchAll();
	}
	
	private function getProgrammeTitle ( $timetableId, $pdo = NULL ) {
		$sql = "SELECT prog_tbl.programme FROM prog_tbl INNER JOIN timetable_tbl ON prog_tbl.prog_id = timetable_tbl.prog_id and timetable_tbl.timetable_id = :timetableId";
		$stmt = (isset($pdo)) ? $pdo->prepare($sql) : $this->pdo->prepare($sql);
		$stmt->execute([':timetableId' => $timetableId]);
		return $stmt->fetchColumn();
	}
	
	public function getProgramme ( $progId, $pdo ) {
		$sql = "SELECT prog_id, programme FROM prog_tbl WHERE prog_id = :progId";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':progId' => $progId]);
		return $stmt->fetchAll();
	}
	
	public function confirmAvailableSpace ( $progId, $startTime, $endTime, $day, $pdo ) {
		$sql = "SELECT prog_id FROM timetable_tbl WHERE start_time = :startTime AND prog_id != :progId AND day = :day";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':startTime' => $startTime, ':progId' => $progId, ':day' => $day]);
		if ($stmt->rowCount()) {
			return $stmt->fetchColumn();
		} else {		
			$sql = "SELECT prog_id, DATE_FORMAT(start_time, '%h:%i %p') as start_time FROM timetable_tbl WHERE start_time = ? < start_time BETWEEN ? AND ? AND prog_id != ? AND day= ?";
			$stmt = $pdo->prepare($sql);
			//Binding Parameters because Time in format HH:MM:SS interfers with MySQL search
			$stmt->bindParam(1, $endTime, PDO::PARAM_STR);
			$stmt->bindParam(2, $startTime, PDO::PARAM_STR);
			$stmt->bindParam(3, $endTime, PDO::PARAM_STR);
			$stmt->bindParam(4, $progId, PDO::PARAM_STR);
			$stmt->bindParam(5, $day, PDO::PARAM_STR);
			$stmt->execute();
			return $stmt->fetchAll();
		}
	}
	
	private function getStudentProgramme () {
		$sql = "SELECT prog_id FROM prog_reg_tbl WHERE student_id = :userId ORDER BY reg_date DESC LIMIT 1";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':userId' => $this->userId]);
		return $stmt->fetchColumn();
	}
	
	private function generateTimetable ( $day, $progCode = NULL ) {
		if (isset($progCode)) {
			$sql = "SELECT timetable_tbl.timetable_id, timetable_tbl.day, DATE_FORMAT(timetable_tbl.start_time, '%k') as start_time, timetable_tbl.duration, timetable_tbl.prog_id, timetable_tbl.venue, prog_tbl.programme FROM timetable_tbl INNER JOIN prog_tbl ON timetable_tbl.prog_id = prog_tbl.prog_id AND prog_tbl.prog_id = :progCode AND timetable_tbl.day = :day ORDER BY timetable_tbl.start_time ASC";
		} else {
			$sql = "SELECT timetable_tbl.timetable_id, timetable_tbl.day, DATE_FORMAT(timetable_tbl.start_time, '%k') as start_time, timetable_tbl.duration, timetable_tbl.prog_id, timetable_tbl.venue, prog_tbl.programme FROM timetable_tbl INNER JOIN prog_tbl ON timetable_tbl.prog_id = prog_tbl.prog_id AND timetable_tbl.day = :day ORDER BY timetable_tbl.start_time ASC";
		}
		$stmt = $this->pdo->prepare($sql);
		if (isset($progCode)) {
			$stmt->execute([':day' => $day, ':progCode' => $progCode]);
		} else {
			$stmt->execute([':day' => $day]);
		}
		return $stmt->fetchAll();
	}
	
	private function getTimetableSnippet ( $timetableId ) {
		$sql = "SELECT day FROM timetable_tbl WHERE timetable_id = :timetableId";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':timetableId' => $timetableId]);
		return $stmt->fetchColumn();
	}
	

	/**
	*	VIEW PAGES
	*/
	protected function timetableUI () {
		if ( $this->userType == 'Admin' ) {
			if ( isset($_GET['create']) ) {
				if ( $_GET['create'] == 'success' ) {
					$programId = staticFunc::unmaskURLParam($_GET['programme']);
					$programme = self::getProgrammeTitle ( $programId );
					$type = 'success';
					$msg = "You Have SuccessFully Added <b>$programme</b> To The Timetable";
					staticFunc::alertDisplay( $type, $msg );
				} else {
					$programId = staticFunc::unmaskURLParam($_GET['programme']);
					$programme = self::getProgrammeTitle ( $programId );
					$type = 'error';
					$msg = "The Programme <b>$programme</b> Could Not Be Added To The Timetable";
					staticFunc::alertDisplay( $type, $msg );
				}
			} elseif ( isset($_GET['update']) ) {
				if ( $_GET['update'] == 'success' ) {
					$programId = staticFunc::unmaskURLParam($_GET['programme']);
					$programme = self::getProgrammeTitle ( $programId );
					$type = 'success';
					$msg = "You Have SuccessFully Updated <b>$programme</b> In The Timetable";
					staticFunc::alertDisplay( $type, $msg );
				} else {
					$programId = staticFunc::unmaskURLParam($_GET['programme']);
					$programme = self::getProgrammeTitle ( $programId );
					$type = 'success';
					$msg = "The Programme <b>$programme</b> Could Not Be Updated";
					staticFunc::alertDisplay( $type, $msg );
				}
			} elseif ( isset($_GET['delete']) ) {
				if ( $_GET['delete'] == 'success' ) {			
					$deletedProgramme = staticFunc::unmaskURLParam($_GET['programme']);
					$type = "success";
					$msg = "The Programme <b>$deletedProgramme</b> Has Been Successfully Deleted From The Timetable";
					staticFunc::alertDisplay ( $type, $msg );
				} else {
					$type = "error";
					$msg = "The Programme Could Not Been Deleted From The Timetable";
					staticFunc::alertDisplay ( $type, $msg );
				}
			}
		} elseif ( $this->userType == 'Student' || $this->userType == 'CuStudent' ) {
			//Get List of All Course the Student is currently studying
			$progCode = self::getStudentProgramme();
			if (!isset($progCode)) {
				$type = 'error';
				$msg = 'Timetable For This Program is Not Yet Set';
				//Display Alert;
				staticFunc::alertDisplay ( $type, $msg );
				return;
			}
		}
		$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
?>
		<div class='row add-item-row'><button class='btn btn-info btn-add-item' onclick="window.location.href='training.php'"><strong>Back To Programmes</strong></button></div>
		<?php if ( $this->userType == 'Admin' ) echo '<div class="text-center text-info course-schedule"><b>Click on Course to change its Schedule</b></div>'; ?>
		<div class="row">
			<div class="panel panel-info panel-training">
			<div class="panel-heading"><span><strong>TIME TABLE</strong><br /></span></div>
				<table class="table table-bordered table-responsive table-training-details">
				<tr class="text-center">
					<td></td>
					<td><strong>8am - 9am</strong></td>
					<td><strong>9am - 10am</strong></td>
					<td><strong>10am - 11am</strong></td>
					<td><strong>11am - 12pm</strong></td>
					<td><strong>12pm - 1pm</strong></td>
					<td><strong>1pm - 2pm</strong></td>
					<td><strong>2pm - 3pm</strong></td>
					<td><strong>3pm - 4pm</strong></td>
				</tr>
<?php
		foreach ( $daysOfWeek as $day ) {
			$timetable = ( $this->userType == 'Admin' ) ? self::generateTimetable( $day ) : self::generateTimetable( $day, $progCode );
			if ( !is_array($timetable) || empty($timetable) ) {
				echo "<tr><td style='vertical-align: middle;'><strong>{$day}</strong></td>";
				for ( $i = 8; $i <= 15; $i++ ) {
					echo '<td></td>';
				}
				continue;
			} else {
				//Set or Reset Value of Previous for New Day
				$previous = NULL;
				echo "<tr><td style='vertical-align: middle;'><strong>{$day}</strong></td>";
				$countResult = count($timetable);
				$setCounter = 0;
				foreach ( $timetable as $key => $value ) {
					for ( $i = (isset($previous)) ? $j : 8; $i <= 15; $i++ ) {
						$size = ( $value['duration'] == 1 ) ? 'h3' :  'h2';
						$size1 = ( $value['duration'] == 1 ) ? 'h6' :  'h5';
						$highlight = ( isset($programId) && $programId == $value['timetable_id'] ) ? 'bg-success' : '';
						if ( $setCounter < $countResult ) {
							$editable = ( $this->userType == 'Admin' ) ? "<a href='edittimetable.php?programme=". staticFunc::maskURLParam($value['timetable_id']) ."' title='Change Schedule' class='timetable_link'>" : '';
							$closeEditable = ( $this->userType == 'Admin' ) ? "</a>" : '';
							if ( $value['start_time'] == $i && !isset($previous) ) {
								$setCounter++;
								echo "<td colspan='{$value['duration']}' class='text-center timetable $highlight'>". $editable ."<span class='{$size} '><strong>{$value['prog_id']}</strong></span><br /><span class='{$size1}'>{$value['programme']}</span>". $closeEditable ."</td>";
								'new val '. $j = $i + $value['duration'];
								$i =  $j - 1;
								$previous = 1;
								if ( $setCounter < $countResult ) {
									break;
								}
							} elseif ( $value['start_time'] !== $i && isset($previous) ) {
								if ( $value['start_time'] == $i ) {
									$setCounter++;
									echo "<td colspan='{$value['duration']}' class='text-center timetable $highlight'>". $editable ."<span class='{$size}'><strong>{$value['prog_id']}</strong></span><br /><span class='{$size1}'>{$value['programme']}</span>". $closeEditable ."</td>";
									$j = $i + $value['duration'];
									$i = $j - 1;
									$previous = 1;
									if ( $setCounter < $countResult ) {
										break;
									}
								} else {
									echo '<td></td>';
								}							
							} else {
								echo "<td></td>";
							}
						} else {
							echo '<td></td>';
						}
					}
				}
				echo '</tr>';
			}
		}
		echo '</table></div>';
		echo '</div>';
	}

	protected function addtimetableUI () {
		echo "<div class='row add-item-row'><button class='btn btn-info btn-add-item' onclick=\"window.location.href='programmes.php'\"><strong>Back To Programmes</strong></button></div>";
		if (!isset($_GET['programme']) && empty($_POST)) {
			$type = 'error';
			$msg = "You are accessing this Page incorrectly. <br />click on <b>Back To Programmes</b> and follow the link: <b>Add To Timetable</b> to add a programme to the timetable 0"; 
			staticFunc::alertDisplay( $type, $msg, 1 );
		} elseif (isset($_GET['programme'])) {
			$progId = staticFunc::unmaskURLParam($_GET['programme']);
			$programme = self::getProgramme( $progId, $this->pdo );
			if (empty($programme)) {
				$type = 'error';
				$msg = "You are accessing this Page incorrectly. <br />click on <b>Back To Programmes</b> and follow the link: <b>Add To Timetable</b> to add a programme to the timetable 1"; 
				staticFunc::alertDisplay( $type, $msg, 1 );
			} else {
				$getProgramme = $programme[0]['programme'];
				$getProgId = $programme[0]['prog_id'];
?>
		<div class="col-md-8 col-md-offset-2">
			<form class="form-horizontal form-add-info" id="edit-item-form" method="post" action="<?php echo basename($_SERVER['PHP_SELF']).'?programme='.staticFunc::maskURLParam($getProgId); ?>">
				<fieldset>
				<legend class="text-info text-center">Add Programme To Timetable</legend>
					<div class="row">
						<div class="col-sm-8 col-sm-offset-2 <?php if (isset(staticFunc::$formInput['programme'])) { echo 'has-error'; } ?>">
							<label for="programme">Course</label>
							<input type="text" class="form-control" id="programme" maxlength="30" name="programme" value="<?php if (isset($_POST['programme'])) { echo $_POST['programme']; } else { echo $getProgramme; } ?>" required readonly />
							<p class="help-block">ReadOnly...Cannot be edited</p>		
						</div>
					</div>
					<div class="row">
						<div class="col-sm-4 col-sm-offset-2">
							<label for="startTime">Day</label>
							<div class="input-group">
								<select name="day" class="item-select form-inline">
									<option value="0" hidden>- Select Day -</option>
									<option value=1 <?php if (isset($_POST['day']) && $_POST['day'] == 1) { echo 'selected'; } ?>>Monday</option>
									<option value=2 <?php if (isset($_POST['day']) && $_POST['day'] == 2) { echo 'selected'; } ?>>Tuesday</option>
									<option value=3 <?php if (isset($_POST['day']) && $_POST['day'] == 3) { echo 'selected'; } ?>>Wednesday</option>
									<option value=4 <?php if (isset($_POST['day']) && $_POST['day'] == 4) { echo 'selected'; } ?>>Thursday</option>
									<option value=5 <?php if (isset($_POST['day']) && $_POST['day'] == 5) { echo 'selected'; } ?>>Friday</option>
								</select>
							</div>
							<p class="help-block">Select Day for The Class</p>
						</div>
						<div class="col-sm-4">
							<label for="startTime">Start Time</label>
							<div class="input-group">
								<select name="startTime" class="item-select form-inline">
									<option value="0" hidden>- Select Time -</option>
									<?php
									$durationNo = range(8,16);
									foreach ($durationNo as $number) {
										$timeAll = staticFunc::timeAMPM ( $number );
										if ( isset($_POST['startTime']) && $_POST['startTime'] == $number) {
											echo "<option value={$number} selected>{$timeAll}</option>";
										} else {
											echo "<option value={$number}>{$timeAll}</option>";
										}
									} 
									?>
								</select>
							</div>
							<p class="help-block">Select Time of Commencement of The Class</p>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-8 col-sm-offset-2">
							<label for="duration">Duration</label>
							<div class="input-group">
								<select name="duration" class="item-select form-inline">
									<option value="0" hidden>- Select Duration -</option>
									<?php
									$durationNo = range(1,3);
									foreach ($durationNo as $number) {
										$hour = ( $number == 1 ) ? ' Hour' : ' Hours'; 
										if ( isset($_POST['duration']) && $_POST['duration'] == $number) {
											echo "<option value={$number} selected>$number$hour</option>";
										} else {
											echo "<option value={$number}>$number$hour </option>";
										}
									}
									?>
								</select>
							</div>
							<p class="help-block">Select the Duration of The Class in Hours</p>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-8 col-sm-offset-2 <?php if (isset(staticFunc::$formInput['venue'])) { echo 'has-error'; } ?>">
							<label for="details">Venue</label>
							<input type='text' name="venue" id="venue" maxlength="30" placeholder="Enter Venue" class="form-control" value="<?php if (isset($_POST['venue'])) { echo $_POST['venue']; } ?>" />
							<p class="help-block">Cannot be more than 30 characters</p>
						</div>
					</div>
					<div class="form-group">
						<input type="submit" id="addTimetableSubmit" name="addTimetableSubmit" class="btn btn-info save-btn" value="Add To Timetable"/>
					</div>
					<input type="hidden" name="addTimetableForm" value="<?php if (isset($_POST['addTimetableForm'])) { echo $_POST['addTimetableForm']; } else { echo staticFunc::maskURLParam($getProgId); } ?>"/>
				</fieldset>
			</form>
		</div>
<?php
			}
		}
	}
	
	protected function edittimetableUI () {
		echo "<div class='row add-item-row'><button class='btn btn-info btn-add-item' onclick=\"window.location.href='timetable.php'\"><strong>Back To Timetable</strong></button></div>";
		if (!isset($_GET['programme']) && empty($_POST)) {
			$type = 'error';
			$msg = "You are accessing this Page incorrectly. <br />click on <b>Back To Programmes</b> and follow the link: <b>Add To Timetable</b> to add a programme to the timetable"; 
			staticFunc::alertDisplay( $type, $msg, 1 );
		} elseif (isset($_GET['programme'])) {
			$timetableId = staticFunc::unmaskURLParam($_GET['programme']);
			$day = self::getTimetableSnippet( $timetableId );
			$timetableSnippet = self::generateTimetable( $day );
			if (empty($timetableSnippet)) {
				$type = 'error';
				$msg = "You are accessing this Page incorrectly. <br />click on <b>Back To Timetable</b> and click on the Course to Edit its Schedule";
				staticFunc::alertDisplay( $type, $msg, 1 );
			} else {
?>
			<h5 class="text-danger text-center"><strong>Click On Highlighted Timetable Item To Delete</strong></h5>
			<table class="table table-bordered table-responsive table-training-details">
			<caption class="text-center h4 text-info table-snip bg-info"><b>Timetable Snippet</b></caption>
			<tbody>
				<tr class="text-center">
					<td></td>
					<td><strong>8am - 9am</strong></td>
					<td><strong>9am - 10am</strong></td>
					<td><strong>10am - 11am</strong></td>
					<td><strong>11am - 12pm</strong></td>
					<td><strong>12pm - 1pm</strong></td>
					<td><strong>1pm - 2pm</strong></td>
					<td><strong>2pm - 3pm</strong></td>
					<td><strong>3pm - 4pm</strong></td>
				</tr>
<?php
				$previous = NULL;
				echo "<tr><td style='vertical-align: middle;'><strong>{$day}</strong></td>";
				$countResult = count($timetableSnippet);
				$setCounter = 0;
				foreach ( $timetableSnippet as $key => $value ) {
					$current = ( $timetableId == $value['timetable_id'] ) ? 'currentItem' : '';
					$linkStart = ( $timetableId == $value['timetable_id'] ) ? "<button class='btn btn-link progId' id='". staticFunc::maskURLParam($value['timetable_id']) ."' value='{$value['programme']}' data-target='#myModalDelete' data-toggle='modal' title='Delete Programme from Timetable'>" : '';
					$linkClose = ( $timetableId == $value['timetable_id'] ) ? '</button>' : '';
					for ( $i = (isset($previous)) ? $j : 8; $i <= 15; $i++ ) {
						$size = ( $value['duration'] == 1 ) ? 'h3' :  'h2';
						$size1 = ( $value['duration'] == 1 ) ? 'h6' :  'h5';
						if ( $setCounter < $countResult ) {
							if ( $value['start_time'] == $i && !isset($previous) ) {
								$setCounter++;
								echo "<td colspan='{$value['duration']}' class='text-center timetable text-info {$current}'>{$linkStart}<span class='{$size} text-info'><strong>{$value['prog_id']}</strong></span><br /><span class='{$size1}'>{$value['programme']}</span>{$linkClose}</td>";
								'new val '. $j = $i + $value['duration'];
								$i =  $j - 1;
								$previous = 1;
								if ( $setCounter < $countResult ) {
									break;
								}
							} elseif ( $value['start_time'] !== $i && isset($previous) ) {
								if ( $value['start_time'] == $i ) {
									$setCounter++;
									echo "<td colspan='{$value['duration']}' class='text-center timetable text-info {$current}'>{$linkStart}<span class='{$size}'><strong>{$value['prog_id']}</strong></span><br /><span class='{$size1}'>{$value['programme']}</span>{$linkClose}</td>";
									$j = $i + $value['duration'];
									$i = $j - 1;					$previous = 1;
									if ( $setCounter < $countResult ) {
										break;
									}
								} else {
									echo '<td></td>';
								}							
							} else {
								echo "<td></td>";
							}
						} else {
							echo '<td></td>';
						}
					}
				}
				echo '</tr></tbody></table>';				

				$programme = self::getTimetable ( $timetableId );
				if (empty($programme)) {
					$type = 'error';
					$msg = "You are accessing this Page incorrectly. <br />click on <b>Back To Programmes</b> and follow the link: <b>Add To Timetable</b> to add a programme to the timetable";
					staticFunc::alertDisplay( $type, $msg, 1 );
				} else {
					foreach ( $programme as $key => $value ) {
?>
		<div class="col-md-8 col-md-offset-2">
			<form class="form-horizontal form-add-info" id="edit-item-form" method="post" action="<?php echo basename($_SERVER['PHP_SELF']).'?programme='.staticFunc::maskURLParam($timetableId); ?>">
				<fieldset>
				<legend class="text-info text-center">Edit <?php echo $value['programme']; ?></legend>
					<div class="row">
						<div class="col-sm-8 col-sm-offset-2 <?php if (isset(staticFunc::$formInput['programme'])) { echo 'has-error'; } ?>">
							<label for="programme">Course</label>
							<input type="text" class="form-control" id="programme" maxlength="30" name="programme" value="<?php echo $value['programme']; ?>" required readonly />
							<p class="help-block">ReadOnly...Cannot be edited</p>		
						</div>
					</div>
					<div class="row">
						<div class="col-sm-4 col-sm-offset-2">
							<label for="startTime">Day</label>
							<div class="input-group">
								<select name="day" class="item-select form-inline">
									<option value="0" hidden>- Select Day -</option>
									<option value=1 <?php if ( $value['day'] == 'Monday' ) { echo 'selected'; } ?>>Monday</option>
									<option value=2 <?php if ( $value['day'] == 'Tuesday' ) { echo 'selected'; } ?>>Tuesday</option>
									<option value=3 <?php if ( $value['day'] == 'Wednesday' ) { echo 'selected'; } ?>>Wednesday</option>
									<option value=4 <?php if ( $value['day'] == 'Thursday' ) { echo 'selected'; } ?>>Thursday</option>
									<option value=5 <?php if ( $value['day'] == 'Friday' ) { echo 'selected'; } ?>>Friday</option>				
								</select>
							</div>
							<p class="help-block">Select Day for The Class</p>
						</div>
						<div class="col-sm-4">
							<label for="startTime">Start Time</label>
							<div class="input-group">
								<select name="startTime" class="item-select form-inline">
									<option value="0" hidden>- Select Time -</option>
									<?php
									$durationNo = range(8 , 16);
									foreach ($durationNo as $number) {
										$timeAll = staticFunc::timeAMPM ( $number );
										if ( $value['start_time'] == $number ) {
											echo "<option value={$number} selected=\"selected\">{$timeAll}</option>";
										} else {
											echo "<option value={$number}>{$timeAll}</option>";
										}
									}
									?>
								</select>
							</div>
							<p class="help-block">Select Time of Commencement of The Class</p>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-8 col-sm-offset-2">
							<label for="duration">Duration</label>
							<div class="input-group">
								<select name="duration" class="item-select form-inline">
									<option value="0" hidden>- Select Duration -</option>
									<?php
									$durationNo = range(1,3);
									foreach ($durationNo as $number) {
										$hour = ( $number == 1 ) ? ' Hour' : ' Hours'; 
										if ( $value['duration'] == $number) {
											echo "<option value={$number} selected=\"selected\">$number$hour</option>";
										} else {
											echo "<option value={$number}>$number$hour </option>";
										}
									}
									?>
								</select>
							</div>
							<p class="help-block">Select the Duration of The Class in Hours</p>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-8 col-sm-offset-2 <?php if (isset(staticFunc::$formInput['venue'])) { echo 'has-error'; } ?>">
							<label for="details">Venue</label>
							<input type='text' name="venue" id="venue" maxlength="30" placeholder="Enter Venue" class="form-control" value="<?php echo $value['venue']; ?>" />
							<p class="help-block">Cannot be more than 30 characters</p>
						</div>
					</div>
					<div class="form-group">
						<input type="submit" id="editTimetableSubmit" name="editTimetableSubmit" class="btn btn-info save-btn" value="Save To Timetable" />
					</div>
					<input type="hidden" name="editTimetableForm" value="<?php if (isset($_POST['editTimetableForm'])) { echo $_POST['editTimetableForm']; } else { echo staticFunc::maskURLParam($timetableId); } ?>"/>
					<input type="hidden" name="editTimetableProgId" value="<?php if (isset($_POST['editTimetableProgId'])) { echo $_POST['editTimetableProgId']; } else { echo staticFunc::maskURLParam($value['prog_id']); } ?>"/>
				</fieldset>
			</form>
		</div>
<?php
					}
				}
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
						<form method="post" id="modalFormTimetable">
							<div class="modal-body">
								<h4 class="text-center to-close">Are You Sure You Want To Delete This Programme From The Timetable?</h4>
								<h1 class="text-center to-close" id="progName"></h1>
								<span class="text-center to-close">This action cannot be undone!</span>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-info btn-order" data-dismiss="modal">No</button>
								<button type="button" class="btn btn-danger btn-order" name="deleteTimetableSubmit" id="deleteTimetableSubmit" data-dismiss="modal">Yes! Delete</button>
							</div>
							<input type="hidden" name="deleteTimetableForm" id="deleteTimetableForm" />
							<input type="hidden" name="deleteTimetableConfirm" id="deleteTimetableConfirm" />
						</form>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->
			</div><!-- /.modal -->
<?php
		}
	}
}