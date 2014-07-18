<?php

$nodes_json = file_get_contents('http://www.dns-lg.com/nodes.json');
$nodes = json_decode($nodes_json);

?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>DNS Propagation Checker</title>

	<!-- Bootstrap -->
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<style>
	iframe {
		border: none;
		width: 100%;
		height: 84px;
	}
	</style>

	<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
<![endif]-->
</head>
<body>

	<div class="container">

		<div class="col-md-12">

			<div class="page-header">
			  <h1>DNS Propagation Checker</h1>
			</div>
		</div>

		<div class="col-md-4" id="toolbar">

		<div class="panel panel-default">
		  <div class="panel-heading">
		  	<h3 class="panel-title"><span class="glyphicon glyphicon-flash"></span></h3>
		  </div>
		  <div class="panel-body">
		    <form action="?">

		    	<div class="form-group">
		    		<label for="domain">Domain</label>
		    		<div class="input-group">
		    			<span class="input-group-addon">http://</span>
		    			<input type="text" class="form-control" id="domain" required>
		    		</div>
		    	</div><!-- /form-group -->

		    	<div class="form-group">
		    		<label for="record-type">Record Type</label>
		    		<div class="input-group">
		    			<input type="text" class="form-control" id="record-type" value="a" required>
		    			<div class="input-group-btn">
		    				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
		    				<ul class="dropdown-menu dropdown-menu-right" role="menu" id="record-type-list">
		    					<li><a href="#" data-record-type="a">a</a></li>
		    					<li><a href="#" data-record-type="cname">cname</a></li>
		    					<li><a href="#" data-record-type="mx">mx</a></li>
		    					<li><a href="#" data-record-type="ns">ns</a></li>
		    					<li><a href="#" data-record-type="spf">spf</a></li>
		    					<li><a href="#" data-record-type="txt">txt</a></li>
		    				</ul>
		    			</div><!-- /btn-group -->
		    		</div><!-- /input-group -->
		    	</div>

		    	<div class="form-group">
		    		<label for="domain">Expected Value</label>
		    		<input type="text" class="form-control" id="expected" name="expected">
		    	</div><!-- /form-group -->

		    	<div class="form-group">
		    		<button class="btn btn-primary" type="submit" id="go">Go!</button>
		    	</div>

		    </form>
		  </div>
		</div>

			<div class="panel panel-default">
			  <div class="panel-body">
			  	<a href="http://www.dns-lg.com/" target="_blank">API Documentation</a>
			  </div>
			</div>

		</div><!-- /.col-lg-6 -->

		<div class="col-md-8">

			<table class="table table-striped table-condensed">
				<tr>
					<th>Server</th>
					<th>Result</th>
					<th>TTL</th>
				</tr>
				<?php
				foreach($nodes->nodes as $node) {
					echo '<tr id="' . $node->name . '">';
					echo '<td width="200" class="country"><span data-toggle="tooltip" title="' . $node->operator . '">' . $node->country . ' ' . $node->name{3} . '</span></td>';
						echo '<td class="result"></td>';
						echo '<td width="200" class="ttl"></td>';
					echo '</tr>';
				}
				?>
			</table>

		</div>

	</div>


<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<!-- Include all compiled plugins (below), or include individual files as needed -->
<script src="js/bootstrap.min.js"></script>

<script>
	var nodes = <?php echo json_encode($nodes->nodes); ?>;

	$('.country span').tooltip({
		placement: "right"
	});

	function secondsToStr(seconds) {
		var totalSec = seconds;
		var hours = parseInt( totalSec / 3600 );
		var minutes = parseInt( totalSec / 60 ) % 60;
		var seconds = totalSec % 60;

		return hours + "h " + minutes + "m " + seconds + "s";
	}

	$("#record-type-list a").on("click", function () {
		$("#record-type").val($(this).data("record-type"));
	});

	function reset() {
		$(".result, .ttl").html("");
		$("#go").removeAttr("disabled");
		$("tr").removeClass("warning success danger");
	}

	$("form").on("submit", function (event) {

		event.preventDefault();

		var domain = $("#domain").val();
		var recordType = $("#record-type").val();
		var expected = $("#expected").val();
		var completedRequests = 0;

		reset();

		$("#go").attr("disabled", "disabled");

		nodes.forEach(function(node) {

			function display(fieldClass, str) {
				$("#" + node.name).find(fieldClass).html(str);
			}

			function addClass(className) {
				$("#" + node.name).addClass(className);
			}

			var req = $.ajax({
				url : "http://www.dns-lg.com/" + node.name + "/" + domain + "/" + recordType,
				dataType : "jsonp",
				timeout : 2000
			});

			req.always(function() {
				completedRequests++;

				if(completedRequests === nodes.length) {
					$("#go").removeAttr("disabled");
				}
			});

			req.done(function(data) {
				if(data.code) {
					display(".result", data.message);
				} else {

					display(".result", data.answer[0].rdata);

					display(".ttl", data.answer[0].ttl + " <small>" + secondsToStr(data.answer[0].ttl) + "</small>");

					if(expected) {
						if(data.answer[0].rdata === expected) {
							addClass("success");
						} else {
							addClass("danger");
						}
					}
				}
			});

			req.fail(function(jqXHR) {
				display(".result", "<iframe src=\"http://www.dns-lg.com/" + node.name + "/" + domain + "/" + recordType + "\"></iframe>");
				addClass("warning");
			});

		});
	});
</script>
</body>
</html>