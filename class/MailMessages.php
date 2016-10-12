<?php

class MailMessages extends Items {
	
	public function sendMessage ( $msgFrom, $msgTo, $messageSubject, $messageContent, $getAttachments, $readMsg, $pdo ) {
		$msgId = self::createNewId ( __CLASS__, 5, $pdo );
		$sql = "INSERT INTO msgs_in_out VALUES(:msgId, NOW(), :msgFrom, :msgTo, :subject, :content, :attachment, :read_msg)";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':msgId' => $msgId, ':msgFrom' => $msgFrom, ':msgTo' => $msgTo, ':subject' => $messageSubject, ':content' => $messageContent, ':attachment' => $getAttachments, ':read_msg' => $readMsg]);
		if ($stmt->rowCount()) {
			return 'success';
		} else {
			return 'error';
		}
	}

	public function deleteMessage( $msgId, $pdo ) {
		$sql = "SELECT deleteMsg FROM msgs_in_out WHERE msg_id = :msgId";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':msgId' => $msgId]);
		$deletedCurrent = $stmt->fetchColumn();
		$allDeleted = ($deletedCurrent == '') ? $_SESSION['userId'] : $deletedCurrent . ';' . $_SESSION['userId'];
		$sql = 'UPDATE msgs_in_out SET deleteMsg = :userId WHERE msg_id = :msgId';
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':userId' => $allDeleted, ':msgId' => $msgId]);
		if ($stmt->rowCount()) {
			return 'success';
		} else {
			return 'error';
		}
		//Check if all Persons for the Message have deleted it
		//Only then can the message be really deleted
		$sql = 'SELECT attachment FROM msgs_in_out WHERE msg_id = :msgId';
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':msgId' => $msgId]);
		$attached = $stmt->fetchColumn();
		$totalAttached = explode(';', $attached);
		if (count($totalAttached) == 1) {
			$sqlPhoto = "SELECT attachment FROM msgs_in_out WHERE MATCH(attachment) AGAINST(:attached)";
			$stmt = $pdo->prepare($sqlPhoto);
			$stmt->execute([':attached' => $totalAttached[0]]);
			$available = $stmt->fetch();
			$countAvailable = count($available);
		} else {
			for ($i = 0; $i < count($totalAttached); $i++) {
				$sqlPhoto = "SELECT attachment FROM msgs_in_out WHERE MATCH(attachment) AGAINST(:attached) AND msg_id <> :msgId";
				$stmt = $pdo->prepare($sqlPhoto);
				$stmt->execute([':attached' => $totalAttached[$i], ':msgId' => $msgId]);
				$available = $stmt->fetch();
				$countAvailable = count($available);
				if ($countAvailable == 0) {
					if (file_exists(urldecode($totalAttached[$i]))) unlink(urldecode($totalAttached[$i]));
				}
			}
		}
		$deleteMsg = self::deleteItem ( __CLASS__, $msgId, $pdo );
		if ($deleteMsg) {
			return 'success';
		} else {
			return 'error';
		}
	}

	private function getAllMessagesIn ( $type ) {
		$count = self::getMsgCount ( $type, 1 );//@param 1 indicates the count to ignore read_msg and count all messages
		if ( $count !== 0 ) {
			$currentPage = ( isset($_GET['page']) && is_numeric($_GET['page'])) ? $_GET['page'] : 1;
			$paginate = new Paginate( $currentPage, $count, $this->tableLimit, 'Message' );
			$page = $paginate->segmentToPages();
			if (!is_array($page)) {
				$type = 'error';
				$msg = 'Please Use the Navigation Links To View Pages<br />';
				staticFunc::alertDisplay( $type, $msg, 1 );
				return;
			} else {
				$end = $page[0];
				$start = $page[1];
				//Getting the Messages	
				$sql = "SELECT msg_id, DATE_FORMAT(_datetime, '%b %d') AS _date, msg_from, subject, read_msg, attachment, content FROM msgs_in_out ";
				if ($type == 'inbox') {
					$sql .= " WHERE MATCH(msg_to) AGAINST(?) AND NOT MATCH(deleteMsg) AGAINST(?) ";
				} elseif ($type == 'sent') {
					$sql .= " WHERE read_msg <> '' AND msg_from = ? AND NOT MATCH(deleteMsg) AGAINST(?) ";
				} elseif ($type == 'draft') {
					$sql .= " WHERE read_msg = '' AND msg_from = ? ";
				}
				$sql .=	" ORDER BY _datetime DESC LIMIT $end OFFSET $start";
				$stmt = $this->pdo->prepare($sql);
				$stmt->bindParam(1, $this->userId, PDO::PARAM_STR);
    			if ( $type !== 'draft') $stmt->bindParam(2, $this->userId, PDO::PARAM_STR);
     			$stmt->execute();
				return $stmt->fetchAll();
			}
		}
	}

	private function getMessageDetails ( $msgId, $type = NULL ) {
		$sql = "SELECT msg_id, msg_from, msg_to, subject, attachment, content ";
		if (isset($type)) {
			$sql .= ", DATE_FORMAT(_datetime, '%D %b., %Y %I:%i %p') AS _datetime, DATE_FORMAT(_datetime, '%Y-%m-%d') AS msgDate, read_msg ";
		}
		$sql .= " FROM msgs_in_out WHERE msg_id = :msgId ";
		if (isset($type)) {
			if ($type == 'inbox') {
				$sql .= " AND msg_to = :userId";
			} elseif ($type == 'sent') {
				$sql .= " AND read_msg <> '' AND msg_from = :userId";
			} elseif ($type == 'draft') {
				$sql .= " AND read_msg = '' AND msg_from = :userId";
			}
		}
		$stmt = $this->pdo->prepare($sql);
		if (isset($type)) {
			$stmt->execute([':msgId' => $msgId, ':userId' => $this->userId]);
			return $stmt->fetchAll();
		} else {
			$stmt->execute([':msgId' => $msgId]);
			return $stmt->fetch();
		}
	}

	public function updateMessageReadStatus ( $msgId, $pdo ) {
		$sql = "UPDATE msgs_in_out SET read_msg = 'Y' WHERE msg_id = :msgId AND read_msg = 'N'";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':msgId' => $msgId]);
		if ($stmt->rowCount()) {
			return 'success';
		} else {
			return 'error';
		}
	}

	private function getMsgCount ( $type, $getMessages = NULL ) {
		if ($type == 'inbox') {
			$sql = "SELECT count(*) FROM msgs_in_out WHERE MATCH(msg_to) AGAINST(?) AND NOT MATCH(deleteMsg) AGAINST(?) ";
			if (!isset($getMessages)) {
				$sql .= " AND read_msg = 'N'";
			}
		} elseif ($type == 'sent') {
			$sql = "SELECT count(*) FROM msgs_in_out WHERE read_msg <> '' AND msg_from = ? AND NOT MATCH(deleteMsg) AGAINST(?)";
		} elseif ($type == 'draft') {
			$sql = "SELECT count(*) FROM msgs_in_out WHERE read_msg = '' AND msg_from = ?";
		}
		$stmt = $this->pdo->prepare($sql);
		$stmt->bindParam(1, $this->userId, PDO::PARAM_STR);
		if ( $type !== 'draft') $stmt->bindParam(2, $this->userId, PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetchColumn();
	}

	/**
	 * VIEW PAGES
	 */

	protected function messagesUI () {
		//Using $_SESSION['mailMessages'] to determine interaction between messages types
		$user = new Users;
		$inboxMessages = self::getMsgCount('inbox');
		$sentMessages = self::getMsgCount('sent');
		$draftMessages = self::getMsgCount('draft');
		$highlighted = 'highlight';
?>
		<!-- Modal -->
		<div class="modal fade" id="confirmMessageDelete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span>&times;</span></button>
						<h3 class="modal-title text-center to-close" id="myModalLabel">DELETE MESSAGES?</h3>
					</div>
					<div class="modal-body">
						<h3 class="text-center to-close">Are You Sure You Want To Delete These Messages?</h3>
						<span class="text-center to-close">This action cannot be undone!</span>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-info same-width-sm" data-dismiss="modal">No</button>
						<button type="button" class="btn btn-danger same-width-sm" name="deleteMessagesSubmit" id="deleteMessagesSubmit" data-dismiss="modal">Yes! Delete</button>
					</div>
				</div><!-- /.modal-content -->
			</div><!-- /.modal-dialog -->
		</div><!-- /.modal -->
		<div class='row add-item-row'><button class='btn btn-info btn-add-item' onclick="window.location.href='user.php'"><strong>Back To User DashBoard</strong></button></div>
		<h3 class="text-center text-info" id="top"><strong>MESSAGES</strong></h3>
		<hr class="hr-class">
		<p class="text-info text-center text-xs" id="appRow">Read Messages From Other Users</p>
		<form method="post" enctype="multipart/form-data" action="<?php echo basename($_SERVER['PHP_SELF']); ?>">
		<input type="submit" class="hidden" name="deleteMessagesConfirmed" id="deleteMessagesConfirmed" />
		<?php
			if (isset($_SESSION['messageReply'])) {
				$type = 'success';
				$msg = ($_SESSION['mailMessages'] == 'draftMessages') ? 'Your Message Was Saved Successfully' : 'Your Message Has Been Sent Successfully';
				staticFunc::alertDisplay($type, $msg);
				unset($_SESSION['messageReply']);
			} elseif (isset($_SESSION['confirmDelete'])) {
				$type = $_SESSION['confirmDelete'];
				$msg = ($_SESSION['confirmDelete'] == 'success') ? 'The Message Was Successfully Deleted' : 'There Was An Error Deleting The Message';
				staticFunc::alertDisplay($type, $msg);
				unset($_SESSION['confirmDelete']);
			}
		?>
		<div class="col-xs-4 col-sm-3 msg-panel">
			<input type="hidden" name="messagesPanelForm" />
			<div class="text-info text-center text-md welcome">Welcome!</div><hr class="hr-divide"/>
			<button class="btn btn-primary add-item-btn new-message" type="submit" name="createNewMsg">NEW MESSAGE</button>
			<p class="<?php if (!isset($_SESSION['mailMessages'])) echo $highlighted; ?>"><button name="inboxMessages" type="submit" class="btn btn-link btn-nounderline">Inbox Messages <span class="badge badge-msg"><?php if (!empty($inboxMessages)) echo $inboxMessages. ' New'; ?></span></button>
			</p>
			<p class="<?php if (isset($_SESSION['mailMessages']) && ($_SESSION['mailMessages'][0] == 'sentMessages' || $_SESSION['mailMessages'][0] == 'readSentMessage')) echo $highlighted; ?>"><button name="sentMessages" type="submit" class="btn btn-link btn-nounderline">Sent Messages <span class="badge badge-msg"><?php if (!empty($sentMessages)) echo $sentMessages; ?></span></button>
			</p>
			<p class="<?php if (isset($_SESSION['mailMessages']) && ($_SESSION['mailMessages'][0] == 'draftMessages' || $_SESSION['mailMessages'][0] == 'readDraftMessage')) echo $highlighted; ?>"><button name="draftMessages" type="submit" class="btn btn-link btn-nounderline">Draft Messages <span class="badge badge-msg"><?php if (!empty($draftMessages)) echo $draftMessages; ?></span></button>
			</p>
		</div>
<?php
		echo '<div class="col-xs-8 col-sm-8 col-sm-offset-1 msgs">';
		if (!isset($_SESSION['mailMessages'])) {
			$getAllMessages = self::getAllMessagesIn('inbox');
			if (!$getAllMessages) {
				$type = 'text';
				$msg= 'You Do Not Have Any Messages In Your Box';
				staticFunc::alertDisplay($type, $msg, 1);
			} else {
				$counter = 0;
?>
				<table class="table table-responsive table-hover">
				<tr class="hidden" id="headSection">
					<td><button class="btn btn-danger" name="deleteMessages" id="deleteMessages">Delete</button></td>
					<td></td>
					<td></td>
					<td></td>
				</tr>
<?php
				foreach ($getAllMessages as $key => $value) {
					$counter++;
					$getUserDetails = $user->getData ( staticFunc::getUserTypeFromUserId ($value['msg_from']), $value['msg_from'], $this->pdo );
					$read = ($value['read_msg'] == 'N') ? 'bold' : '';
					$attachFile = ($value['attachment'] !== '') ? "<span class='file-attachment'></span>" : '';
?>
					<tr>
						<td><input type="checkbox" name="markToDelete[]" id="switch-<?php echo $counter; ?>" class="input-image markToDelete" value="<?php echo staticFunc::maskURLParam($value['msg_id']); ?>" /><label for="switch-<?php echo $counter; ?>"><img src="<?php echo urldecode($getUserDetails['photo']); ?>" class="img-thumbnail img-thumbnail-circle hidden" />a<span class="fa fa-check-circle tick-lg hidden" id="span-<?php echo $counter; ?>"></span></label></td>
						<td><?php echo "<button name='readMessage' type='submit' class='btn btn-link text-normal btn-msg-link' value=". staticFunc::maskURLParam($value['msg_id']) ."><p class='text-left p-no-pad text-sm $read'>".$getUserDetails['name']."</p><p class='text-left $read p-no-pad'>".$value['subject']."</p><span class='readmore'>".$value['content']."</span></button>"; ?></td>
						<td><?php echo $attachFile; ?></td>
						<td><?php echo $value['_date']; ?></td>
					</tr>
<?php
				}
				echo '</table>';
			}
		} else {
			if ($_SESSION['mailMessages'][0] == 'readMessage') {
				$msgId = $_SESSION['mailMessages'][1];
				$getMessage = self::getMessageDetails( $msgId, 'inbox' );
				if ($getMessage) {
					foreach ($getMessage as $key => $value) {
						$getUserDetails = $user->getData ( staticFunc::getUserTypeFromUserId ($value['msg_from']), $value['msg_from'], $this->pdo );
?>
						<div class="pad">
							<img src="<?php echo urldecode($getUserDetails['photo']); ?>" class="img-thumbnail img-thumbnail-circle" />
							<span class="bold text-md margin-left-right"><?php echo $getUserDetails['name'].'</span>|<span class="text-xs margin-left-right" id="days">'. staticFunc::determinePeriod(staticFunc::compareDates($value['msgDate']), NULL, 1) .'</span>|'; ?>
							<a id="viewMsgDetails" href="#" class="bold text-xs margin-left-right"/>View Details</a>
							
							<div class="hidden text-xs mailDetails" id="mailDetails">
								<div class="inline"><div>From:&nbsp;<?php echo $getUserDetails['name']; ?></div><div> Date:&nbsp;<?php echo $value['_datetime']; ?></div>
								</div>
								<div class="inline reply-group"><button class="reply-pad" title="Reply" name="reply" id="reply"><span class="fa fa-reply pad"> Reply</span></button><button class="reply-pad" title="Forward" name="forwardMsg" id="forwardMsg" value="<?php echo staticFunc::maskURLParam($value['msg_id']); ?>">Forward <span class="fa fa-forward pad"></span></button><button class="delete-btn" title="Delete" name="deleteMsg" id="deleteMsg" value="<?php echo staticFunc::maskURLParam($value['msg_id']); ?>"><span class="fa fa-remove pad"> Delete</span></button><input type="hidden" name="replySubmit" value="<?php echo staticFunc::maskURLParam($value['msg_from']); ?>" />
								</div>
							</div>
						</div>
						<hr class="hr-class"/>
						<div class="text-md margin-left-md pad"><?php echo $value['subject']; ?>&nbsp;<?php if ($value['attachment'] !== '') echo '<img src="../css/images/ic_attachment_black_18dp.png" />'; ?></div>
						<hr class="hr-divide"/>
						<div class="messageBody"><?php echo $value['content']; ?></div>
<?php 
						if ($value['attachment'] !== '') {
							$files = explode(';', $value['attachment']);
							$count = 0;
							foreach ($files as $value) {
								$fileName = urldecode($value);
								$checkFileType = staticFunc::checkFileType($fileName); 
								$link = ($checkFileType['type'] == 'PIX') ? '<button href="#" class="btn btn-link viewFileNow" type="button" value="'.$fileName.'" id="viewFileNow'. ++$count .'"><div class="filesView text-center"><span class="text-xlg">'. $checkFileType['icon'] .'</span><br /><span>Click To View</span></div></button>' : '<button type="button" class="btn btn-link"><div class="filesView text-center"><span class="text-xlg">'. $checkFileType['icon'] .'</span><br /><span>Click To Download</span></div></button>';
				
										echo $link;
							}
							echo "<hr class='hr-class'/><div class='hidden pad imageViewer' id='imageViewer'><img id='imageViewerFile' class='img-responsive block center' /></div>";
						}
					}
				}
			} elseif ($_SESSION['mailMessages'][0] == 'readSentMessage') {
				$msgId = $_SESSION['mailMessages'][1];
				$getMessage = self::getMessageDetails ( $msgId, 'sent' );
				if ($getMessage) {
					foreach ($getMessage as $key => $value) {
						$checkTotalReceivers = explode(';', $value['msg_to']);
						if (count($checkTotalReceivers) > 1) {
							$getUserDetails = $user->getData ( staticFunc::getUserTypeFromUserId ( $checkTotalReceivers[0] ), $checkTotalReceivers[0], $this->pdo );
						} else {
							$getUserDetails = $user->getData ( staticFunc::getUserTypeFromUserId ( $value['msg_to'] ), $value['msg_to'], $this->pdo );
						}
?>
						<div class="pad">
							<img src="<?php echo urldecode($getUserDetails['photo']); ?>" class="img-thumbnail img-thumbnail-circle" />
							<span class="bold text-md margin-left-right"><?php echo $getUserDetails['name']; if(count($checkTotalReceivers) > 1) echo ', ...'; echo ' </span>|<span class="text-xs margin-left-right" id="days">'. staticFunc::determinePeriod(staticFunc::compareDates($value['msgDate']), NULL, 1) .'</span>|'; ?>
							<a id="viewMsgDetails" href="#" class="bold text-xs margin-left-right"/>View Details</a>
							
							<div class="hidden text-xs mailDetails" id="mailDetails">
								<div class="inline"><div>From:&nbsp;
								<?php
									$getSender = $user->getData ( staticFunc::getUserTypeFromUserId ( $value['msg_from'] ), $value['msg_from'], $this->pdo, NULL, 1 );
									echo $getSender['name'];
								?>
								</div><div> Date:&nbsp;&nbsp;<?php echo $value['_datetime']; ?>
								</div>
								</div>
								<div class="inline reply-group"><button class="reply-pad" title="Reply" name="reply" id="reply"><span class="fa fa-reply pad"> Reply</span></button><button class="reply-pad" title="Forward" name="forwardMsg" id="forwardMsg" value="<?php echo staticFunc::maskURLParam($value['msg_id']); ?>">Forward <span class="fa fa-forward pad"></span></button><button class="delete-btn" title="Delete" name="deleteMsg" id="deleteMsg" value="<?php echo staticFunc::maskURLParam($value['msg_id']); ?>"><span class="fa fa-remove pad"> Delete</span></button>
								</div>
								<?php
									echo '<div>To: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
									$replyUsers = '';
									for ($i = 0; $i < count($checkTotalReceivers); $i++) {
										$replyUsers .= staticFunc::maskURLParam($checkTotalReceivers[$i]) . ';';
										$getAllSent[] = $user->getData(staticFunc::getUserTypeFromUserId ( $checkTotalReceivers[$i] ), $checkTotalReceivers[$i], $this->pdo, NULL, 1);
									}
									$getAllReplies = rtrim($replyUsers, ';');
									$getSpace = 1;
									foreach ($getAllSent as $key => $newValue) {
										$setSpacing = ($getSpace > 1) ? 'pad-left-lg' : '';
										echo "<div class='getAllSent inline $setSpacing' data-value='". staticFunc::maskURLParam($newValue['userId']) ."'>". $newValue['name'] ."</div><div class='clearfix'></div>";
										++$getSpace;
									}
									echo '</div>';
									$replySubmit = (count($checkTotalReceivers) > 1) ? $getAllReplies : staticFunc::maskURLParam($value['msg_to']);
								?>
								<input type="hidden" name="replySubmit" value="<?php echo $replySubmit; ?>" />
							</div>
						</div>
						<hr class="hr-class"/>
						<div class="text-md margin-left-md pad"><?php echo $value['subject'] . '&nbsp;'; if ($value['attachment'] !== '') echo '<img src="../css/images/ic_attachment_black_18dp.png" />'; ?></div>
						<hr class="hr-divide"/>
						<div class="messageBody"><?php echo $value['content']; ?></div>
<?php 
						if ($value['attachment'] !== '') {
							$files = explode(',', $value['attachment']);
							$count = 0;
							foreach ($files as $value) {
								$fileName = urldecode($value);
								$checkFileType = staticFunc::checkFileType($fileName); 
								$link = ($checkFileType['type'] == 'PIX') ? '<button href="#" class="btn btn-link viewFileNow" type="button" value="'.$fileName.'" id="viewFileNow'. ++$count .'"><div class="filesView text-center"><span class="text-xlg">'. $checkFileType['icon'] .'</span><br /><span>Click To View</span></div></button>' : '<button type="button" class="btn btn-link"><div class="filesView text-center"><span class="text-xlg">'. $checkFileType['icon'] .'</span><br /><span>Click To Download</span></div></button>';
								echo $link;
				
									}
							echo "<hr class='hr-class'/><div class='hidden pad imageViewer' id='imageViewer'><img id='imageViewerFile' class='img-responsive block center' /></div>";
						}
					}
				}
			} elseif ($_SESSION['mailMessages'][0] == 'readDraftMessage') {
				$msgId = $_SESSION['mailMessages'][1];
				$getMessage = self::getMessageDetails( $msgId, 'draft' );
				if ($getMessage) {
					foreach ($getMessage as $key => $value) {
						if ($value['msg_to'] !== '') {
							$checkTotalReceivers = explode(';', $value['msg_to']);
							if (count($checkTotalReceivers) > 1) {
								$getUserDetails = $user->getData ( staticFunc::getUserTypeFromUserId ( $checkTotalReceivers[0] ), $checkTotalReceivers[0], $this->pdo );
							} else {
								$getUserDetails = $user->getData ( staticFunc::getUserTypeFromUserId ( $value['msg_to'] ), $value['msg_to'], $this->pdo );
							}
						}
?>
						<div class="pad">
							<?php if ($value['msg_to'] !== '') {
							echo '<img src="<?php echo urldecode($getUserDetails[\'photo\']); ?>" class="img-thumbnail img-thumbnail-circle" />
								<span class="bold text-md margin-left-right">$getUserDetails[\'name\']'; if(count($checkTotalReceivers) > 1) echo ', ... </span>|<span class="text-xs margin-left-right" id="days">'. staticFunc::determinePeriod(staticFunc::compareDates($value['msgDate']), NULL, 1) .'</span>|<a id="viewMsgDetails" href="#" class="bold text-xs margin-left-right"/>View Details</a>'; } 
							?>
							
							<div class="text-xs <?php if ($value['msg_to'] !== '') echo "mailDetails hidden"; ?>" <?php if ($value['msg_to'] !== '') echo "id='mailDetails'"; ?> >
								<div class="inline"><?php if ($value['msg_to'] !== '') echo '<div>From:&nbsp;'. $getUserDetails['name'] .'</div>'; ?><div> Date:&nbsp;<?php echo $value['_datetime']; ?></div></div>
								<div class="inline reply-group"><button class="reply-pad" title="Edit" name="editMsg" id="editMsg" value="<?php echo staticFunc::maskURLParam($value['msg_id']); ?>">Edit <span class="fa fa-edit pad"></span></button><button class="delete-btn" title="Delete" name="deleteMsg" id="deleteMsg" value="<?php echo staticFunc::maskURLParam($value['msg_id']); ?>"><span class="fa fa-remove pad"> Delete</span></button>
								</div>
							</div>

						</div>
						<hr class="hr-class"/>
						<div class="text-md margin-left-md pad"><?php echo $value['subject']; ?>&nbsp;<?php if ($value['attachment'] !== '') echo '<img src="../css/images/ic_attachment_black_18dp.png" />'; ?></div>
						<hr class="hr-divide"/>
						<div class="messageBody"><?php echo $value['content']; ?></div>
<?php 
						if ($value['attachment'] !== '') {
							$files = explode(',', $value['attachment']);
							$count = 0;
							foreach ($files as $value) {
								$fileName = urldecode($value);
								$checkFileType = staticFunc::checkFileType($fileName); 
								$link = ($checkFileType['type'] == 'PIX') ? '<button href="#" class="btn btn-link viewFileNow" type="button" value="'.$fileName.'" id="viewFileNow'. ++$count .'"><div class="filesView text-center"><span class="text-xlg">'. $checkFileType['icon'] .'</span><br /><span>Click To View</span></div></button>' : '<button type="button" class="btn btn-link"><div class="filesView text-center"><span class="text-xlg">'. $checkFileType['icon'] .'</span><br /><span>Click To Download</span></div></button>';
				
										echo $link;
							}
							echo "<hr class='hr-class'/><div class='hidden pad imageViewer' id='imageViewer'><img id='imageViewerFile' class='img-responsive block center' /></div>";
						}
					}
				}
			} elseif ($_SESSION['mailMessages'][0] == 'createNewMsg') {
				$allReceivers = '';
				if (isset($_SESSION['messageReply'])) {
					$getReceivers = explode(';', $_SESSION['messageReply']);
					for ($i = 0; $i < count($getReceivers); $i++) {
						$userId = staticFunc::unmaskURLParam($getReceivers[$i]);
						$currentReceiver = $user->getData(staticFunc::getUserTypeFromUserId($userId), $getReceivers[$i], $this->pdo);
						$allReceivers .= '<span class="thumbnail-mail-span" id="'. $getReceivers[$i] .'"><img class="thumbnail-width-img" src="'. urldecode($currentReceiver['photo']) .'" class="thumbnail-mail-img" /><span>'. $currentReceiver['name'] .'</span><span class="btn-closeUserDetails" title="Remove This User" >&times;</span></span>';
					}
				} elseif (isset($_SESSION['messageForward'])) {
					$getMessage = self::getMessageDetails(staticFunc::unmaskURLParam($_SESSION['messageForward'][1]));
					if ($getMessage) {
						$subject = 'Fwd: '.$getMessage['subject'];
						$content = $getMessage['content'];
						$finalFiles = '';
						if ($getMessage['attachment'] !== '') {
							$allAttachments = explode(',', $getMessage['attachment']);
							if (count($allAttachments) > 1) {
								for ($i = 0; $i < count($allAttachments); $i++) {
									$attachedFiles = explode('/',urldecode($allAttachments[$i]), 2);
									$finalFiles .= '<div class="newUploadFiles text-info bold" id="'. $attachedFiles[1] .'">'. $attachedFiles[1] .' <span class="fa fa-check-circle"></span><span class="file-closeUserDetails deleteAttachmentFile" title="Remove">X</span></div>';
								}
							} else {
								$attachedFiles = explode('/', urldecode($allAttachments[0]), 2);
								$finalFiles =  '<div class="newUploadFiles text-info bold" id="'. $attachedFiles[1] .'">'. $attachedFiles[1] .' <span class="fa fa-check-circle"></span><span class="file-closeUserDetails deleteAttachmentFile forwarded" title="Remove">X</span></div>';
							}
						}
					}
				} elseif (isset($_SESSION['messageEdit'])) {
					$getMessage = self::getMessageDetails(staticFunc::unmaskURLParam($_SESSION['messageEdit']));
					if ($getMessage) {
						$subject = $getMessage['subject'];
						$content = $getMessage['content'];
						$finalFiles = '';
						if ($getMessage['attachment'] !== '') {
							$allAttachments = explode(',', $getMessage['attachment']);
							if (count($allAttachments) > 1) {
								for ($i = 0; $i < count($allAttachments); $i++) {
									$attachedFiles = explode('/',urldecode($allAttachments[$i]), 2);
									$finalFiles .= '<div class="newUploadFiles text-info bold" id="'. $attachedFiles[1] .'">'. $attachedFiles[1] .' <span class="fa fa-check-circle"></span><span class="file-closeUserDetails deleteAttachmentFile" title="Remove">X</span></div>';
								}
							} else {
								$attachedFiles = explode('/', urldecode($allAttachments[0]), 2);
								$finalFiles =  '<div class="newUploadFiles text-info bold" id="'. $attachedFiles[1] .'">'. $attachedFiles[1] .' <span class="fa fa-check-circle"></span><span class="file-closeUserDetails deleteAttachmentFile forwarded" title="Remove">X</span></div>';
							}
						}
					}
				}					
?>
				<h3 class="text-center text-info" id="top"><strong>CREATE NEW MESSAGE</strong></h3>
				<div class="row"><div id="addedPreviously" class="alert alert-warning col-md-8 col-md-offset-2 bold text-center pad-xs hidden" role="alert"></div></div>
				<label for="senderList">To</label>
				<div id="divContentEditable" class="divContentEditable"><input type="text" name="inputSenderList" id="inputSenderList" list="senderList" class="new-text" placeholder="Type The User's name and Select from Dropdown List" /><div class="divContentEditableInner" id="senderDetails"><?php if (isset($_SESSION['messageReply'])) echo $allReceivers; ?></div></div>	
				<datalist id="senderList">
					<option label="All Staff Members" value="allStaff" />
					<option label="All Customers" value="allCustomers" />
					<option label="All Students" value="allStudents" />
					<?php $getAllUsers = staticFunc::getAllUsers ( $this->pdo );
						foreach ($getAllUsers as $key => $value) {
							echo '<option label="'. $value['name'] .'" value="'. staticFunc::maskURLParam($value['userId']) .'" />';
						}
					?>
				</datalist>
				<label for="messageSubject">Subject</label><input type="text" name="messageSubject" id="messageSubject" maxlength="70" class="form-control" placeholder="Enter The Subject of the Message" value="<?php if (isset($_SESSION['messageForward']) || isset($_SESSION['messageEdit'])) echo $subject; ?>" />
				<label for="messageContent">Message</label>
				<textarea name="messageContent" id="messageContent" class="form-control" rows="10" placeholder="Enter Your Message"><?php if (isset($_SESSION['messageForward']) || isset($_SESSION['messageEdit'])) echo $content; ?></textarea>
				<label for="attachments" class="fileAttachment"><img src="../css/images/ic_attachment_black_24dp.png" class="margin-left-right" />&nbsp;<span class="text-xs">Attach Files (Multiple Files Allowed)</span></label>
				<input type="file" name="attachment[]" id="attachments" class="hidden fileAttachment" multiple>
				<div id="messageAndAttach">
					<?php if (isset($_SESSION['messageForward']) || isset($_SESSION['messageEdit'])) echo $finalFiles; ?>
				</div>
				<hr class="hr-class">
				<div class="saveButton"> 
					<button class="btn btn-info margin" id="getSubmitData">SEND</button>
				
							<input type="submit" id="mailMessageSubmit" name="mailMessageSubmit" class="btn btn-info hidden" value="SEND" />
					<input type="submit" class="btn btn-link text-normal text-xs" name="saveAsDraft" id="saveAsDraft" value="Save As Draft" />
					<input type="hidden" name="mailMessageForm" id="mailMessageForm" />
					<input type="hidden" name="attachedFiles" id="attachedFiles" />
					<input type="hidden" name="saveMessageDraft" id="saveMessageDraft" />
				</div>
<?php
			} elseif ($_SESSION['mailMessages'][0] == 'sentMessages') {
				$getAllMessages = self::getAllMessagesIn('sent');
				if (!$getAllMessages) {
					$type = 'text';
					$msg= 'You Do Not Have Any Sent Messages';
					staticFunc::alertDisplay($type, $msg, 1);
				} else {
					$counter = 0;					
?>
					<table class="table table-responsive table-hover table-condensed">
					<tr class="hidden" id="headSection">
						<td><button class="btn btn-danger" name="deleteMessages" id="deleteMessages">Delete</button></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
<?php
					foreach ($getAllMessages as $key => $value) {
						$counter++;
						$getUserDetails = $user->getData ( staticFunc::getUserTypeFromUserId ( $value['msg_from']), $value['msg_from'], $this->pdo );
						$attachFile = ($value['attachment'] !== '') ? "<img src='../css/images/ic_attachment_black_18dp.png' />" : '';
?>
						<tr>
							<td><input type="checkbox" name="markToDelete[]" id="switch-<?php echo $counter; ?>" class="input-image markToDelete" value="<?php echo staticFunc::maskURLParam($value['msg_id']); ?>" /><label for="switch-<?php echo $counter; ?>"><img src="<?php echo urldecode($getUserDetails['photo']); ?>" class="img-thumbnail img-thumbnail-circle hidden" />a<span class="fa fa-check-circle tick-lg hidden" id="span-<?php echo $counter; ?>"></span></label></td>
							<td><?php echo "<button name='readSentMessage' type='submit' class='btn btn-link text-normal btn-msg-link' value=". staticFunc::maskURLParam($value['msg_id']) ."><p class='text-left p-no-pad text-sm'>".$getUserDetails['name']."</p><p class='text-left p-no-pad text-sm'>".$value['subject']."</p><span class='readmore'>".$value['content']."</span></button>"; ?></td>
							<td class="no-pad"><?php echo $attachFile; ?></td>
				
									<td class="no-pad"><?php echo $value['_date']; ?></td>
						</tr>
<?php
					}
					echo '</table>';
				}
				echo '</div>';
			} elseif ($_SESSION['mailMessages'][0] == 'draftMessages') {
				$getAllMessages = self::getAllMessagesIn('draft');
				if (!$getAllMessages) {
					$type = 'text';
					$msg= 'You Do Not Have Any Draft Messages';
					staticFunc::alertDisplay($type, $msg, 1);
				} else {
					$counter = 0;
?>
					<table class="table table-responsive table-hover">
					<tr class="hidden" id="headSection">
						<td><button class="btn btn-danger" name="deleteMessages" id="deleteMessages">Delete</button></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
<?php
					foreach ($getAllMessages as $key => $value) {
						$counter++;
						$getUserDetails = $user->getData ( staticFunc::getUserTypeFromUserId ($value['msg_from']), $value['msg_from'], $this->pdo );
						$read = ($value['read_msg'] == 'N') ? 'bold' : '';
						$attachFile = ($value['attachment'] !== '') ? "<img src='../css/images/ic_attachment_black_18dp.png' />" : '';
?>
						<tr>
							<td><input type="checkbox" name="markToDelete[]" id="switch-<?php echo $counter; ?>" class="input-image markToDelete" value="<?php echo staticFunc::maskURLParam($value['msg_id']); ?>" /><label for="switch-<?php echo $counter; ?>"><img src="<?php echo urldecode($getUserDetails['photo']); ?>" class="img-thumbnail img-thumbnail-circle hidden" />a<span class="fa fa-check-circle tick-lg hidden" id="span-<?php echo $counter; ?>"></span></label></td>
							<td><?php echo "<button name='readDraftMessage' type='submit' class='btn btn-link text-normal btn-msg-link' value=". staticFunc::maskURLParam($value['msg_id']) ."><span class='text-left text-sm'>".$getUserDetails['name']."</span><p class='text-left p-no-pad text-sm'>".$value['subject']."</p><span class='readmore'>".$value['content']."</span></button>"; ?></td>
							<td><?php echo $attachFile; ?></td>
							<td><?php echo $value['_date']; ?></td>
						</tr>
<?php
					}
					echo '</table>';
				}
				echo '</div>';
			}
			echo '</div>';
		}
		echo '</form>';
	}
}