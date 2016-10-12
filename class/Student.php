<?php
/**	Class Student
*/
class Student extends Users implements UserInterface {
	
	/**
	*	VIEWS PAGE
	*/
	protected function indexUI () {
?>		
		<div class="row">
			<div class="well index-well">
				<h3 class="text-center"><strong>
					Welcome to McSue Training Institute . . . Your Avenue for Dream Actualization<br />
					<small>We specialize in building the best skills-set for an outstanding career</small><br />
					<a href="about.php" class="btn btn-info"><b>Learn More About Us</b></a>
				</strong></h3>
			</div>
			<div class="col-sm-6 col-md-4 home-icon">
				<a href="designs.php" title="Designs"><span class="fa fa-image"></span>
				<div class="text-center inline-block home-text">Designs</div></a>
			</div>
			<div class="col-sm-6 col-md-4 home-icon">
				<a href="orders.php" title="Designs"><span class="fa fa-shopping-cart home-icon"></span>
				<div class="text-center inline-block home-text">Orders</div></a>
			</div>
			<div class="col-sm-6 col-md-4 home-icon">
				<a href="training.php" title="Designs"><span class="glyphicon glyphicon-education home-icon"></span>
				<div class="text-center inline-block home-text">Training Programmes</div></a>
			</div>
			<div class="col-sm-6 col-md-4 home-icon">
				<a href="reminders.php" title="Designs"><span class="glyphicon glyphicon-calendar home-icon"></span>
				<div class="text-center inline-block home-text">Reminders</div></a>
			</div>
			<div class="col-sm-6 col-md-4 home-icon">
				<a href="user.php" title="Designs"><span class="fa fa-gear home-icon"></span>
				<div class="text-center inline-block home-text">User Settings</div></a>
			</div>
		</div>		
<?php	
	}
}