<?php
Cancel an order 48 hours before it is due.



var buttons = document.getElementsByClassName('myImg');
var buttonsCount = buttons.length;
var modalImg = document.getElementById("img01");
var title = document.getElementById("myModalLabel");
for ( var i = 0; i <= buttonsCount; i += 1 ) {
	buttons[i].onclick = function(){
		modalImg.src = this.id;
		modalImg.alt = this.value;
		title.innerHTML = this.value;
	}
}







<form method="post" id="modalForm" class="form-horizontal">
						<div class="modal-body modal-body-customer">
							<div class="row">
								<div class="col-md-6 <?php if (isset($this->formInput['surname'])) { echo 'has-error'; } ?>">
									<label for="surname">Surname</label>
									<input type="text" class="form-control" id="surname" maxlength="20" name="surname" value="<?php echo $value['surname']; ?>" placeholder="Enter Surname" required/>
									<p class="help-block">Surname cannot be more than 20 characters</p>
								</div>
								<div class="col-md-6 <?php if (isset($this->formInput['surname'])) { echo 'has-error'; } ?>">
									<label for="firstname">First Name</label>
									<input type="text" class="form-control" id="firstname" maxlength="20" name="firstname" value="<?php echo $value['firstname']; ?>" placeholder="Enter First Name" required/>
									<p class="help-block">First Name cannot be more than 20 characters</p>
								</div>
								<div class="col-md-6 <?php if (isset($this->formInput['othername'])) { echo 'has-error'; } ?>">
									<label for="othername">Other Name</label>
									<input type="text" class="form-control" id="othername" maxlength="20" name="othername" value="<?php echo $value['othername']; ?>" placeholder="Enter Other Name" required/>
									<p class="help-block">Other Name cannot be more than 20 characters</p>
								</div>
								<div class="col-md-6 <?php if (isset($this->formInput['street'])) { echo 'has-error'; } ?>">
									<label for="street">Street</label>
									<input type="text" class="form-control" id="street" maxlength="20" name="street" value="<?php echo $value['street']; ?>" placeholder="Enter Street Address" required/>
									<p class="help-block">Street cannot be more than 20 characters</p>
								</div>
								<div class="col-md-6 <?php if (isset($this->formInput['city'])) { echo 'has-error'; } ?>">
									<label for="city">City</label>
									<input type="text" class="form-control" id="city" maxlength="20" name="city" value="<?php echo $value['city']; ?>" placeholder="Enter City" required/>
									<p class="help-block">City cannot be more than 15 characters</p>
								</div>
								<div class="col-md-6 <?php if (isset($this->formInput['state'])) { echo 'has-error'; } ?>">
									<label for="surname">State</label>
									<input type="text" class="form-control" id="state" maxlength="20" name="state" value="<?php echo $value['state']; ?>" placeholder="Enter State" required/>
									<p class="help-block">State cannot be more than 15 characters</p>
								</div>
								<div class="col-md-6 <?php if (isset($this->formInput['country'])) { echo 'has-error'; } ?>">
									<label for="country">Country</label>
									<input type="text" class="form-control" id="country" maxlength="20" name="country" value="<?php echo $value['country']; ?>" placeholder="Enter Country" required/>
									<p class="help-block">Country cannot be more than 30 characters</p>
								</div>
								<div class="col-md-6 <?php if (isset($this->formInput['gender'])) { echo 'has-error'; } ?>">
									<label for="gender">Gender</label>
									<select name="gender" class="form-inline">
										<option value="1" <?php if (isset($_POST['gender']) && $_POST['gender'] == 1) { echo 'selected'; }?> >Male</option>
										<option value="2" <?php if (isset($_POST['gender']) && $_POST['gender'] == 2) { echo 'selected'; }?> >Female</option>
									</select>
									<p class="help-block">Select the appropriate gender</p>
								</div>
								<div class="col-md-6 <?php if (isset($this->formInput['phone'])) { echo 'has-error'; } ?>">
									<label for="phone">Phone Number</label>
									<input type="text" class="form-control" id="phone" maxlength="20" name="phone" value="<?php echo $value['phone']; ?>" placeholder="Enter Phone Number" required/>
									<p class="help-block">Phone Number cannot be more than 15 characters</p>
								</div>
								<div class="col-md-6 <?php if (isset($this->formInput['phone_alt'])) { echo 'has-error'; } ?>">
									<label for="phone_alt">Alternative Phone</label>
									<input type="text" class="form-control" id="phone_alt" maxlength="20" name="phone_alt" value="<?php echo $value['phone_alt']; ?>" placeholder="Enter Alternative Phone Number" required/>
									<p class="help-block">Alternative Phone Number cannot also be more than 15 characters</p>
								</div>
								<div class="col-md-6 <?php if (isset($this->formInput['email'])) { echo 'has-error'; } ?>">
									<label for="email">Email Address</label>
									<input type="text" class="form-control" id="email" maxlength="20" name="email" value="<?php echo $value['email']; ?>" placeholder="Enter Email Address" required/>
									<p class="help-block">Email Address should not be more than 40 characters</p>
								</div>
								<div class="col-md-6 <?php if (isset($this->formInput['file'])) { echo 'has-error-file'; } ?>">
									<label for="currentPhoto">Customer's Photo</label><br />
									<img src="<?php echo urldecode($value['photo']); ?>" id="currentPhoto" alt="<?php echo $value['cust_name']; ?>"/><br /><br />
									<label for="custPhoto">
									Change Current Photo</label>
									<input type="file" id="custPhoto" name="file1"/>
									<p class="help-block">Image Format should be in .jpg, .gif, .png formats</p>
								</div>
							</div>						
						</div>
						<div class = "modal-footer">
							<button type = "button" class = "btn btn-default" data-dismiss = "modal">Cancel</button>
							<button type = "button" class = "btn btn-primary">Submit changes</button>
						</div>
						<input type="hidden" name="editCustomerForm" value="<?php echo staticFunc::maskURLParam($value['cust_id']); ?>"/>
					</form>
					
					
					
					
					//Towards Creating Timetable View
					
												
							/*	if (isset($previous)) {
									if ( $value['start_time'] > $i ) {
										for ( $a = $i; $a = $value['start_time']; $a++ ) {
											echo "<td>{$i} {$value['start_time']}</td>";
										}
									} else {
										for ( $a = $value['start_time']; $a = $i; $a++ ) {
											echo "<td>{$i} {$value['start_time']}</td>";
										}
									}
									continue;
								} else {
									if ( $value['start_time'] > $i ) {
										for ( $a = $i; $a = $value['start_time']; $a++ ) {
											echo "<td>{$i} {$value['start_time']}</td>";
										}
									} else {
										for ( $a = $value['start_time']; $a = $i; $a++ ) {
											echo "<td>{$i} {$value['start_time']}</td>";
										}
									}
									continue;
								}
								
								
							/*	if (isset($previous)) {
									if ( $value['start_time'] - $previous > 0 ) {
										echo $value['start_time'] . '  is true ';
										echo ' Loop is '.$loop;
										$i = $previous + $loop;
									//	break;
									} else {
										echo " increase $i ";
										continue;
									}
								} else {
									echo '<td>b</td>';
								}	*/
