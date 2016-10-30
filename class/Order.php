<?php

/**
 * Class Order that defines all orders requirements
 */
class Order extends Items implements itemDetailsInterface,  UserInterface {
	
	private function makeOrder ( $pdo, $designId, $custId, $added_details, $order_date, $delivery_date, $delivery_time, $delivery_location ) {
		$orderId = parent::createNewId ( __CLASS__ );
		//Add design to database
		$sql = "INSERT INTO orders_tbl VALUES ( :orderId, :designId, :cust_id, :added_details, :order_date, :delivery_date, :delivery_time, :delivery_location )";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':orderId' => $orderId, ':designId' => $designId, ':cust_id' => $custId, ':added_details' => $added_details, ':order_date' => $order_date, ':delivery_date' => $delivery_date, ':delivery_time' => $delivery_time, ':delivery_location' => $delivery_location]);
		if ($stmt->rowCount()) {
			//Return Success Message
			return 'success';
		} else {
			//Throw Error exception
			return 'error';
		}
	}
	
	/**	
	 *	Method getDetails returns details of the order
	 *	@param $orderId string to the database
	 *	@return array set of the order
	 */
	private function getDetails ( $orderId ) {
		//Returns details of given item
		$sql = "SELECT * FROM orders_tbl WHERE order_id = :orderId";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':orderId' => $orderId]);
		return $stmt->fetchAll();
	}
	
	/**	
	 *	Method updateOrder to edit the particular design
	 *	@param $pdo connection variable to the database
	 *	@param $orderId id of the order to be updated
	 *	@param $custId id of the customer that placed the order
	 *	@param $photo photo of the order to be updated
	 *	@param $pricing pricing of the design to be updated
	 *	@return returns result of update
	 */
	private function updateOrder ( $orderId, $addedDetails, $orderDate, $delvDate, $delvTime, $delvLocation ) {
		//Update Details of designs in database
		$sql = "UPDATE orders_tbl SET added_details = :added_details, order_date = :order_date, delivery_date = :delivery_date, delivery_time = :delivery_time, delivery_location = :delivery_location WHERE order_id = :order_id" ;
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':added_details' => $addedDetails, ':order_date' => $orderDate, ':delivery_date' => $delvDate, ':delivery_time' => $delvTime, ':delivery_location' => $delvLocation, ':order_id' => $orderId]);
		//Confirm whether there were rows updated by update query
		if ( $stmt->rowCount() > 0 ) {
			return 'success';
		} else {
			return 'error';
		}
	}
		
	
	/**
	 *	VIEW PAGES
	 */	
	protected function ordersUI () {
		if ($this->userType == 'Customer') echo '<div class="row add-item-row"><button class="btn btn-info btn-add-item" onclick="window.location.href=\'designs.php\'"><strong>Make Order</strong></button></div>';
		$orders = ( $this->userType !== 'Admin' ) ? self::getItems ( __CLASS__, $this->tableLimit, $this->userId ) : self::getItems ( __CLASS__, $this->tableLimit );
		if ( !is_array($orders) || empty($orders) ) {
			$type = 'error';
			$msg = "There Are No Current Orders Available To View";
			//Display Alert;
			staticFunc::alertDisplay ( $type, $msg, 1 );
		} else {
?>
			<div class="row">
			<div class="panel panel-info panel-order">
				<div class="panel-heading"><span CLASS="text-center"><strong>ORDERS</strong></span></div>
				<table class="table table-striped table-hover table-responsive table-center">
				<tr>
					<th>Customer Name</th>
					<th>Item</th>
					<th>Date Ordered</th>
					<th>Due</th>
					<th>Delivery Venue</th>
					<th>Delivery Status</th>
				</tr>
<?php
			foreach ( $orders as $key => $value ) {
				$daysAway = staticFunc::determinePeriod($value['days_away'], $value['hours_away']);
?>
				<tr>
					<td><button id="<?php echo urldecode($value['user_photo']); ?>" value="<?php echo $value['cust_name']; ?>" class="btn btn-link myImg" title="View Customer's Photo" data-toggle="modal" data-target="#myModalOrder"><?php echo $value['cust_name']; ?></button></td>
					<td><button id="<?php echo urldecode($value['photo']); ?>" value="<?php echo $value['title']; ?>" class="btn btn-link myImg" title="View Photo of Item" data-toggle="modal" data-target="#myModalOrder"><?php echo $value['title']; ?></button></td>
					<td><?php echo $value['order_date']; ?></td>
					<td><?php echo $order = ($value['days_away'] <= 3 && $value['delivered_date'] == '0000-00-00') ? "<span class='text-danger bold'>{$value['due']}{$daysAway}</span>" : $value['due'].$daysAway;?></td>
					<td><?php echo $value['delivery_venue']; ?></td>
					<td><strong><?php echo $delivery = ($value['delivered_date'] == '0000-00-00') ? '<span class="text-danger">Not Yet</span>' : "<span class='text-success'>Delivered<br />({$value['delivered_date']})</span>"; ?></strong></td>
				</tr>				
<?php		}
			echo '</table></div></div>';
			Paginate::displayPageLink();
?>
		<!-- Modal -->
		<div class = "modal fade" id = "myModalOrder" tabindex = "-1" role = "dialog" aria-labelledby = "myModalLabel" aria-hidden = "true">
			<div class = "modal-dialog modal-dialog-order">
				<div class = "modal-content modal-transparent">
					<div class = "modal-header modal-header-order">
						<button type="button" class="close" data-dismiss="modal" aria-hidden = "true"><span>&times;</span></button>
						<h4 class = "modal-title modal-title-order" id = "myModalLabel">
						</h4>
					</div>
					<div class="modal-body modal-body-order">
						<img class="img-responsive" id="img01" width="500"/>
						<span class="to-close">Press <kbd>ESC</kbd> or click outside image to close</span>
					</div>
					<div class="modal-footer modal-footer-order">
						<button type="button" class="btn btn-default btn-order" data-dismiss="modal">Close</button>
						<button type="button" class="btn btn-primary btn-order">Submit changes</button>
					</div>
				</div><!-- /.modal-content -->
			</div><!-- /.modal-dialog -->
		</div><!-- /.modal -->
<?php
		}		
	}

	protected function createorderUI () {
		echo '<div class="row add-item-row"><button class="btn btn-info btn-add-item" onclick="window.location.href=\'orders.php\'"><strong>Back To Orders</strong></button></div>';
		if (isset($_GET['item'])) {
			$itemId = staticFunc::unmaskURLParam($_GET['item']);
		} else {
			staticFunc::redirect('designs.php?noitem=');
		}
		$design = new Design;
		$getItemdetails = $design->getDetails ( $itemId, $this->pdo );
		var_dump($getItemdetails);
?>
		<div class="col-md-8 col-md-offset-2">
			<form class="form-horizontal form-add-info" id="edit-item-form" enctype="multipart/form-data" method="post" action="<?php echo basename($_SERVER['PHP_SELF']); ?>">
				<fieldset>
				<legend class="text-info text-center">Place New Order</legend>
					<div class="row">
						<div class="col-md-6 col-md-offset-3">
							<input type="hidden" name="itemImage" value="<?php echo $itemId; ?>" />
							<div><img src="<?php echo urldecode($getItemdetails['photo']) ?>" class="img-responsive img-rounded" /></div>
						</div>
						<div class="col-md-4 col-md-offset-4">
							<label for="itemPricing">Pricing</label>
							<div class="input-group">
								<div class="input-group-addon">N</div>
								<input type="text" id="itemPricing" name="itemPricing" value="<?php echo number_format($getItemdetails['pricing'], 2) ?>" class="form-control bold" z-index="-10" readonly />
							</div>
							<p class="help-block">Amount for the Item Selected</p>
						</div>
						<div class="clearfix"></div>
						<div class="col-md-3 <?php if (isset(staticFunc::$formInput['orderQuantity'])) { echo 'has-error'; } ?>">
							<label for="orderQuantity">Desired Quantity</label>
							<?php if ($getItemdetails['stock_quantity'] == 1) {
								echo '<input type="text" id="orderQuantity" name="orderQuantity" maxlength="3" class="form-control" value="'. $getItemdetails['stock_quantity'] .'" placeholder="Enter Desired Quantity" readonly />';
								echo '<p class="help-block">Available Quantity</p>';
							} else {
								echo '<select></select>';
							}
							?>
						</div>
						<div class="col-md-4 <?php if (isset(staticFunc::$formInput['sizeVariants'])) { echo 'has-error'; } ?>">
							<label for="sizeVariants">Select Size Variants</label>
							<p>
								<select name="sizeVariants[]" class="multiple_select form-inline" multiple="multiple">
									<?php 
										$sizeVariants = explode(',', $getItemdetails['size_variants']);
										for ($i = 0; $i < count($sizeVariants); $i++) {
											echo "<option value='$sizeVariants[$i]'>$sizeVariants[$i]</option>";
										}
									?>
								</select>
							</p>
							<p class="help-block">Select Multiple sizes by holding <kbd>Ctrl</kbd> + Size</p>
						</div>
						<div class="col-md-5">
							<label for="designColours">Other Colour Variants</label>
							<p>
								<select name="designColours[]" class="multiple_select form-inline" multiple="multiple">
									<?php 
										$colourVariants = explode(',', $getItemdetails['colour_variants']);
										for ($i = 0; $i < count($colourVariants); $i++) {
											echo "<option value='$colourVariants[$i]'>$colourVariants[$i]</option>";
										}
									?>
								</select>
							</p>
							<p class="help-block">Select the Desired Colour Variant(s)</p>
						</div>
						<div class="clearfix"></div>
						<div class="col-md-5 <?php if (isset(staticFunc::$formInput['dueDate'])) { echo 'has-error'; } ?>">
							<label for="datepicker">Intended Delivery Date</label>
							<input type="date" class="form-control" id="datepicker" name="dueDate" value="<?php if (isset($_POST['dueDate'])) { echo $_POST['dueDate']; }?>" placeholder="YYYY-MM-DD" />
							<p class="help-block">Select The Intended Date For Order Delivery</p>
						</div>
						<div class="col-md-7">
							<label for="assgnDeadlineTime">Intended Delivery Time</label><br />
							<button class="btn btn-info" id="addTimeDeadline">Click To Add</button>
							<div class="pad inline hidden" id="selectTimeDeadline" >
								<select name="dueTime" id="dueTime" class="select-smaller form-inline" required>
									<option value="0" hidden>- Time -</option>
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
							<p class="help-block">Select Intended Delivery Time</p>
						</div>
						<div class="col-md-12">
							<label for="deliveryVenue">Delivery Venue (Address)</label>
							<input type="text" id="deliveryVenue" name="deliveryVenue" maxlength="100" class="form-control" value="<?php if (isset($_POST['deliveryVenue'])) { echo $_POST['deliveryVenue']; }?>" placeholder="Enter Delivery Venue" required/>
							<p class="help-block">Enter The Intended Delivery Venue (Address)</p>
						</div>
					</div>
					<div class="form-group">
						<button type="submit" id="createOrderSubmit" name="createOrderSubmit" class="btn btn-info margin-left-md" ><span class="fa fa-save"></span> Save And Add Other Items</button>
						<button id="checkOutOrder" name="checkOutOrder" class="btn btn-info"><span class="fa fa-check-out"></span> Check Out and Complete Order</button>
					</div>
					<input type="hidden" name="createOrderForm" />
				</fieldset>
			</form>
		</div>
<?php	
	}
}