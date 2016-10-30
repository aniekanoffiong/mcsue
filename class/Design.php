<?php

/**	Class Design that defines all design requirements
*/ 
class Design extends Items implements itemDetailsInterface, UserInterface {
	
	/**	@param $pdo connection variable to the database
	*	@param $title title for the design to be created
	*	@param $photo url link to the photo of the design
	*	@param	$pricing the set price for the design
	*/
	public function createDesign ( $title, $photo, $pricing, $stockQty, $sizeVariants, $colourVariants, $access, $pdo ) {
		//Set designId Value from inherited method generateId()
		$designId = parent::createNewId ( __CLASS__ );
		//Add design to database
		$sql = "INSERT INTO designs_tbl VALUES ( :designId, :title, :photo, :pricing, :stockQty, :sizeVariants, :colourVariants, curdate(), :access )";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':designId' => $designId, ':title' => $title, ':photo' => $photo, ':pricing' => $pricing, ':access' => staticFunc::$pref[0]['designs_access'] ]);
		if ($stmt->rowCount()) {
			$msg = 'The Design was Successfully Added';
			$type = 'success';
			$link = 'designdetails.php?design='.staticFunc::maskURLParam($designId);
			$linkValue = 'View Added Design';
			staticFunc::alertDisplay($type, $msg, $link, $linkValue);
			//Reset All array values
			$_POST = array();
			$_FILES = array();
			return;
	} else {
			//Failed
			$msg = 'There was an error adding the new Design';
			$type = 'error';
			staticFunc::alertDisplay($type, $msg, 1 );
			return;
		}
	}
	
	/**	
	*	Method getDetails returns details of the design
	*	@param $pdo connection variable to the database
	*	@return returns result set of the design
	*/
	public function getDetails ( $itemId, $pdo = NULL ) {
		//Returns details of given item
		$sql = "SELECT * FROM designs_tbl WHERE design_id = :id";
		$determinePDO = (isset($pdo)) ? $pdo : $this->pdo;
		$stmt = $determinePDO->prepare($sql);
		$stmt->execute([':id' => $itemId]);
		if (isset($pdo)) {
			return $stmt->fetch();
		} else {
			return $stmt->fetchAll();
		}
	}
	
	/**	
	*	Method editDesign to edit the particular design
	* 	@param $pdo connection variable to the database
	*	@param $designId id of the design to be updated
	*	@param $title title of the design to be updated
	*	@param $photo photo of the design to be updated
	*	@param $pricing pricing of the design to be updated
	*	@return null
	*/
	public function updateDesign ( $designId, $title, $photo, $pricing, $stockQty, $sizeVariants, $colourVariants, $pdo ) {
		//Update Details of designs in database
		$sql = "UPDATE designs_tbl SET title = :title, photo = :photo, pricing = :pricing, stock_quantity = :stockQty, size_variants = :sizeVariants, colour_variants = :colourVariants WHERE design_id = :designId";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':title' => $title, ':photo' => $photo, ':pricing' => $pricing, ':stockQty' => $stockQty, ':sizeVariants' => $sizeVariants, ':colourVariants' => $colourVariants, ':designId' => $designId]);
		//Confirm whether there were rows updated by update query
		if ( $stmt->rowCount() ) { //Update Successful
			staticFunc::redirect("designdetails.php?design=".staticFunc::maskURLParam($designId)."&update=success");
			$_POST == array();
			$_FILES == array();
			return;
		} else { //
			staticFunc::redirect("designdetails.php?design=".staticFunc::maskURLParam($designId)."&update=failed");
			$_POST == array();
			$_FILES == array();
			return;
		}
	}
	
	private function updateAccess ( $designId, $access ) {
		//Update Details of designs access in database
		$accessValue = ('Admin') ? 'All' : 'Admin';
		$sql = "UPDATE designs_tbl SET access = :access WHERE design_id = :designId";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':access' => $accessValue, ':designId' => $designId]);
		//Confirm whether there were rows updated by update query
		if ( $stmt->rowCount() ) { //Update Successful
			$type = 'success';
			$msg = "Access has been changed to ". $accessType = ($accessValue == 'Admin') ? 'Admin Only' : 'All';
			staticFunc::alertDisplay( $type, $msg, 1 );
		} else { //
			$type = 'info';
			$msg = "Access is unchanged!";
			staticFunc::alertDisplay( $type, $msg, 1 );
		}
	}
	
	
	/**
	*	VIEW PAGES
	*/
	
	protected function designsUI () {
		$design = self::getItems ( __CLASS__, $this->gridLimit );
		if ( !is_array($design) || empty($design) ) {
			$type = 'error';
			$msg = "$design There are no Designs available.";
			if ( $this->userType == 'Admin' ) {
				$link = 'adddesign.php';
				$linkValue = 'Add New Design';
				staticFunc::alertDisplay ( $type, $msg, $link, $linkValue );
			} else {
				staticFunc::alertDisplay ( $type, $msg );
			}
		} else {
			if ( $this->userType == 'Admin' ) {
				echo '<div class="row add-item-row"><button class="btn btn-info btn-add-item" onclick="window.location.href=\'adddesign.php\'"><b>Add New Design</b></button></div>';
			} else {
				echo '<div class="row add-item-row"><button class="btn btn-info btn-add-item" onclick="window.location.href=\'index.php\'"><b>Back To Home</b></button></div>';
			}
			if (isset($_GET['noitem'])) {
				$type = 'warning';
				$msg = 'Please Select An Item To Order';
				staticFunc::alertDisplay($type, $msg, 1);
			}
			$detailsBtn = 'View Details';
			$btnType = 'btn-info';
			if (basename($_SERVER['HTTP_REFERER']) == 'orders.php') {
				$fromOrder = 1;
				$detailsBtn = 'Order This Item';
				$btnType = 'btn-link';
			}
			echo '<div class="row">';
			foreach ( $design as $key => $value ) {
?>
				<div class="col-sm-6 col-md-4">
					<div class="thumbnail item-thumbnail">
						<img src="<?php echo urldecode($value['photo']); ?>" alt="<?php echo $value['title']; ?>" class="img-responsive"/>
						<div class="caption">
							<p class="text-center item-title"><?php echo $value['title']; ?></p>
							<hr class="hr-divide">
							<h4 class="text-center"><b>N <?php echo number_format($value['pricing'], 2); ?></b></h4>
							<?php
								if (isset($fromOrder)) echo "<p><a href='createorder.php?item=" . staticFunc::maskURLParam($value['design_id']) ."' class='btn btn-info item-link'><b>Order Item</b></a></p>";
							?>
							<p><a href="<?php echo 'designdetails.php?design=' . staticFunc::maskURLParam($value['design_id']); ?>" class="btn item-link <?php echo $btnType; ?>"><b>View Details</b></a></p>
						</div>
					</div>
				</div>
<?php		}
			echo '</div>';
			Paginate::displayPageLink();
		}
	}

	protected function designdetailsUI () {
		if (!isset($_GET['design'])) {
			if ( !isset($_POST['deleteDesignForm']) ) {
				staticFunc::errorPage(__CLASS__);
			}
		} else {
			$designId = staticFunc::unmaskURLParam($_GET['design']);
			echo '<div class="row add-item-row"><button class="btn btn-info btn-add-item" onclick="window.location.href=\'designs.php\'"><strong>Back To Designs</strong></button></div>';
			$designDetails = self::getDetails ( $designId );
			if ( !is_array($designDetails) || empty($designDetails) ) {
				$alertType = 'error';
				$msg = 'The Details of this design are unavailable.';
				$link = 'designs.php';
				$linkValue = 'Back To Designs';
				staticFunc::alertDisplay ( $alertType, $msg, $link, $linkValue );
			} else {
				$_SESSION['id'] = $designId;				
				if (isset($_GET['update'])) {
					if ( $_GET['update'] == 'success' ) {
						$type = 'success';
						$msg = "The Design Has Been Updated";
						staticFunc::alertDisplay( $type, $msg );
					} elseif ($_GET['update'] == 'failed' ) {
						$type = 'info';
						$msg = "No Information Was Updated!";
						staticFunc::alertDisplay( $type, $msg );
					}
				}
				foreach ( $designDetails as $key => $value ) {
?>
				<div class="col-sm-offset-2 col-sm-8 text-center" id="deleteAlert"></div>
				<div class="row">
					<div class="panel panel-info panel-item-details">
						<div class="panel-heading"><?php echo 'Details of <br /><span><strong>'. $value['title']; ?></strong></span></div>
						<table class="table table-striped table-hover table-responsive table-item-details">
							<tr>
								<td><strong>Photo</strong></td>
								<td><img src="<?php echo urldecode($value['photo']); ?>" alt="<?php $value['title']; ?>"/></td>
							</tr>
							<tr>
								<td><strong>Pricing</strong></td>
								<td>N <?php echo number_format($value['pricing'], 2); ?></td>
							</tr>
							<tr>
								<td><strong>Current Quantity In Stock</strong></td>
								<td><?php echo $value['stock_quantity']; ?></td>
							</tr>
							<tr>
								<td><strong>Size Variants Available</strong></td>
								<td><?php echo $value['size_variants']; ?></td>
							</tr>
							<tr>
								<td><strong>Other Colour Variants Available</strong></td>
								<td><?php echo $value['colour_variants']; ?></td>
							</tr>
<?php			}
				echo '</div>';
				if ( $this->userType == 'Admin' ) {
?>
						<tr>
							<td><strong>Currently Viewable by</strong></td>
							<td><?php echo $viewable = ($value['access'] == 'Admin') ? 'Admin Only' : 'All'; ?></td>
						</tr>
					</table>
				</div>
				<div class="row">
					<div class="delete-item-row">
						<button class="btn btn-info" onclick="window.location .href='editdesign.php?design=<?php echo staticFunc::maskURLParam($value['design_id']); ?>'">Edit Design</button>
						<button class="btn btn-danger myImg" id="<?php echo urldecode($value['photo']); ?>" value="<?php echo $value['title']; ?>" data-toggle="modal" data-target="#myModalDelete">Delete Design</button>
					</div>
				</div>
<?php				
				} else {
?>
					</table>
				</div>
<?php				
					if ( $this->userType == 'Customer' || $this->userType == 'CuStudent' ) {
?>
					<div class="row">
						<div class="add-item-row">
							<button class="btn btn-info save-btn" onclick="window.location.href='createorder.php?order=<?php echo staticFunc::maskURLParam($designId); ?>'">Order This Item</button>
						</div>
					</div>
<?php				
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
					<form method="post" id="modalForm">		 
						<div class="modal-body">
							<img class="img-responsive img-delete" id="img01"/>
							<h3 class="text-center to-close">Are You Sure You Want To Delete This Design?</h3>
							<span class="text-center to-close">This action cannot be undone!</span>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-info" data-dismiss="modal">No</button>
							<button type="button" class="btn btn-danger" name="deleteDesignSubmit" id="deleteDesignSubmit" data-dismiss="modal">Yes! Delete</button>
						</div>
						<input type="hidden" name="deleteDesignForm" id="deleteDesignForm" value="<?php echo staticFunc::maskURLParam($value['design_id']); ?>" />
						<input type="hidden" name="deleteDesignConfirm" id="deleteDesignConfirm" value="<?php echo staticFunc::maskURLParam($value['title']); ?>" />
					</form>
				</div><!-- /.modal-content -->
			</div><!-- /.modal-dialog -->
		</div><!-- /.modal -->				
<?php
			}
		}
	}
	
	protected function adddesignUI () {
		echo '<div class="row add-item-row"><button class="btn btn-info btn-add-item" onclick="window.location.href=\'designs.php\'"><strong>Back To Designs</strong></button></div>';
?>
		<div class="col-md-8 col-md-offset-2">
			<form class="form-horizontal form-add-info" id="edit-item-form" enctype="multipart/form-data" method="post" action="<?php echo basename($_SERVER['PHP_SELF']); ?>">
				<fieldset>
				<legend class="text-info text-center">Add New Design</legend>
					<div class="row">
						<div class="col-md-6">
							<label for="designTitle">Design Title</label>
							<input type="text" class="form-control" id="designTitle" maxlength="30" name="designTitle" value="<?php if (isset($_POST['designTitle'])) { echo $_POST['designTitle']; }?>" placeholder="Enter Design Title" required/>
							<p class="help-block">Title cannot be more than 30 characters</p>		
						</div>
						<div class="col-md-5 <?php if (isset(staticFunc::$formInput['designPricing'])) { echo 'has-error'; } ?>">
							<label for="designPricing">Pricing</label>
							<div class="input-group">
								<div class="input-group-addon">N</div>
								<input type="text" id="designPricing" name="designPricing" value="<?php if (isset($_POST['designPricing'])) { echo $_POST['designPricing']; }?>" class="form-control" placeholder="Enter Pricing for Design" required/>
								<div class="input-group-addon">.00</div>
							</div>
							<p class="help-block">Enter Amount in numerical values only: 10000</p>
						</div>
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['designQuantity'])) { echo 'has-error'; } ?>">
							<label for="designQuantity">Quantity In Stock</label>
							<input type="text" id="designQuantity" name="designQuantity" maxlength="3" class="form-control" value="<?php if (isset($_POST['designQuantity'])) { echo $_POST['designQuantity']; }?>" placeholder="Enter Quantity" required/>
							<p class="help-block">Only numerical values allowed</p>
						</div>
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['sizeVariants'])) { echo 'has-error'; } ?>">
							<label for="sizeVariants">Size Variants</label>
							<p>
								<select name="sizeVariants[]" class="multiple_select form-inline" multiple="multiple">
									<option value="1" <?php if (isset($_POST['sizeVariants']) && in_array(1, $_POST['sizeVariants'])) { echo 'selected'; }?> >Extra Small</option>
									<option value="2" <?php if (isset($_POST['sizeVariants']) && in_array(2, $_POST['sizeVariants'])) { echo 'selected'; }?> >Small</option>
									<option value="3" <?php if (isset($_POST['sizeVariants']) && in_array(2, $_POST['sizeVariants'])) { echo 'selected'; }?> >Medium</option>
									<option value="4" <?php if (isset($_POST['sizeVariants']) && in_array(4, $_POST['sizeVariants'])) { echo 'selected'; }?> >Large</option>
									<option value="5" <?php if (isset($_POST['sizeVariants']) && in_array(5, $_POST['sizeVariants'])) { echo 'selected'; }?> >Extra Large</option>
								</select>
							</p>
							<p class="help-block">Select Multiple sizes by holding <kbd>Ctrl</kbd> + Size</p>
						</div>
						<div class="col-md-4">
							<label for="designColours">Other Colour Variants</label>
							<input type="text" id="designColours" name="designColours" maxlength="30" class="form-control" value="<?php if (isset($_POST['designColours'])) { echo $_POST['designColours']; }?>" placeholder="Enter Colours" required/>
							<p class="help-block">Enter Colour Seperated by <kbd>,</kbd></p>
						</div>
					</div>
					<div class="form-group <?php if (isset(staticFunc::$formInput['file'])) { echo 'has-error-file'; } ?>">
						<label for="designPhoto">Design Photo</label>
						<input type="file" id="designPhoto" name="file1" required/>
						<p class="help-block">Image Format should be in .jpg, .gif, .png formats</p>
					</div>
					<div class="form-group">
						<input type="submit" id="addDesignSubmit" name="addDesignSubmit" class="btn btn-info add-item-btn" value="Add Design"/>
					</div>
					<input type="hidden" name="addDesignForm" />
				</fieldset>
			</form>
		</div>
<?php	
	}
	
	protected function editdesignUI () {
		if (!isset($_GET['design'])) {
			staticFunc::errorPage( 'error' );
		} else {
			$getDetailsId = staticFunc::unmaskURLParam($_GET['design']);
			$editDesign = self::getDetails( $getDetailsId );
			if (!is_array($editDesign) || empty($editDesign)) {
				staticFunc::errorPage( 'error' );
			} else {
				echo "<div class='row add-item-row'><button class='btn btn-info btn-add-item' onclick=\"window.location.href='designdetails.php?design=".staticFunc::maskURLParam($_GET['design'])."'\"><strong>Back To Design Details</strong></button></div>";
				foreach ($editDesign as $key => $value ) {
?>
		<div class="col-md-8 col-md-offset-2">
			<form class="form-horizontal form-add-info" id="edit-item-form" enctype="multipart/form-data" method="post" action="<?php echo basename($_SERVER['PHP_SELF']); ?>">
				<fieldset>
				<legend class="text-info text-center">Edit Design</legend>
					<div class="row">
						<div class="col-md-6 <?php if (isset(staticFunc::$formInput['designPricing'])) { echo 'has-error'; } ?>">
							<label for="designTitle">Design Title</label>
							<input type="text" class="form-control" id="designTitle" maxlength="30" name="designTitle" value="<?php echo $value['title']; ?>" placeholder="Enter Design Title" required/>
							<p class="help-block">Title cannot be more than 30 characters</p>		
						</div>
						<div class="col-md-5 <?php if (isset(staticFunc::$formInput['designPricing'])) { echo 'has-error'; } ?>">
							<label for="designPricing">Pricing</label>
							<div class="input-group">
								<div class="input-group-addon">N</div>
								<input type="text" id="designPricing" name="designPricing" mexlength="8" placeholder="Enter Pricing for Design" value="<?php echo $value['pricing']; ?>" class="form-control" required/>
								<div class="input-group-addon">.00</div>
							</div>
							<p class="help-block">Enter Amount in numerical values only: 10000</p>
						</div>
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['designQuantity'])) { echo 'has-error'; } ?>">
							<label for="designQuantity">Quantity In Stock</label>
							<input type="text" id="designQuantity" name="designQuantity" maxlength="3" class="form-control" placeholder="Enter Quantity" value="<?php echo $value['stock_quantity']; ?>"  required/>
							<p class="help-block">Only numerical values allowed</p>
						</div>
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['sizeVariants'])) { echo 'has-error'; } ?>">
							<label for="sizeVariants">Size Variants</label>
							<p>
								<select name="sizeVariants[]" class="multiple_select form-inline" multiple="multiple">
								<?php 
									$sizes = explode(',', $value['size_variants'], 6);
								?>
									<option value="0" hidden> - Select Size - </option>
									<option value="1" <?php if (in_array('Extra Small', $sizes)) { echo 'selected'; }?> >Extra Small</option>
									<option value="2" <?php if (in_array('Small', $sizes) || in_array(' Small', $sizes)) { echo 'selected'; }?> >Small</option>
									<option value="3" <?php if (in_array('Medium', $sizes) || in_array(' Medium', $sizes)) { echo 'selected'; }?> >Medium</option>
									<option value="4" <?php if (in_array('Large', $sizes) || in_array(' Large', $sizes)) { echo 'selected'; }?> >Large</option>
									<option value="5" <?php if (in_array('Extra Large', $sizes) || in_array(' Extra Large', $sizes)) { echo 'selected'; }?> >Extra Large</option>
								</select>
							</p>
							<p class="help-block">Select Multiple sizes by holding <kbd>Ctrl</kbd> + Size</p>
						</div>
						<div class="col-md-4">
							<label for="designColours">Colour Variants</label>
							<input type="text" id="designColours" name="designColours" maxlength="30" class="form-control" value="<?php echo $value['colour_variants']; ?>" placeholder="Enter Colours" required/>
							<p class="help-block">Enter Colour Seperated by <kbd>,</kbd></p>
						</div>
					</div>
					<div class="form-group <?php if (isset(staticFunc::$formInput['file'])) { echo 'has-error-file'; } ?>">
						<label for="currentPhoto">Design Photo</label><br />
						<img src="<?php echo urldecode($value['photo']); ?>" id="currentPhoto" alt="<?php echo $value['title']; ?>"/><br />
						<label for="designPhoto">Change Current Photo</label>
						<input type="file" id="designPhoto" name="file1"/>
						<p class="help-block">Image Format should be in .jpg, .gif, .png formats</p>
					</div>
					<div class="form-group">
						<input type="submit" id="editDesignSubmit" name="editDesignSubmit" class="btn btn-info" value="Save Changes"/>
					</div>
					<input type="hidden" name="editDesignForm" value="<?php echo staticFunc::maskURLParam($value['design_id']); ?>"/>
					<input type="hidden" name="editDesign" value="<?php echo staticFunc::maskURLParam($value['photo']); ?>"/>
				</fieldset>
			</form>
		</div>
<?php
				}
			}
		}
	}
}