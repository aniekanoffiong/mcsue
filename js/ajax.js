$(function(){
	$('#myToggleButton').button();
});


$(function(){
	$('.disabled').click(function(event){
		event.preventDefault();
	});
		
	$("button#deleteDesignSubmit").click(function(){
		$.ajax({
			type	:	"POST",
			url		: 	"designdetails.php",
			data	:	$('form#modalForm').serialize(),
			success	:	function(msg){
				console.log(msg);
				$("#ajaxUpdate").html(msg)
			},
		});
	});
});

$(function(){
	$("button#deleteCustomerSubmit").click(function(){
		$.ajax({
			type	:	"POST",
			url		: 	"customerdetails.php",
			data	:	$('form#modalForm').serialize(),
			success	:	function(msg){
				$("#ajaxUpdate").html(msg)
			},
		});
	});
});

$(function(){
	$("#deleteTrainingSubmit").click(function(){
		$.ajax({
			type	:	"POST",
			url		: 	"programmes.php",
			data	:	$('form#modalFormProg').serialize(),
			success	:	function(msg){
				$("#ajaxUpdate").html(msg)
			},
		});
	});
});

$(function(){
	$("#deleteTimetableSubmit").click(function(){
		$.ajax({
			type	:	"POST",
			url		: 	"edittimetable.php",
			data	:	$('form#modalFormTimetable').serialize(),
			success	:	function(msg){
				$("#ajaxUpdate").html(msg)
			},
		});
	});
});

$(function(){
	$("#restoreDefaultSettings").click(function(){
		$.ajax({
			type	:	"POST",
			url		: 	"user.php",
			data	:	$('form#modalRestoreDefault').serialize(),
			success	:	function(msg){
				$("#ajaxUpdate").html(msg)
			},
		});
	});
});

