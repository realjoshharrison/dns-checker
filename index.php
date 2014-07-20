<?php

require_once('lib/CURLRequest.php');

$errors = array();

try {
  $req = new CURLRequest('http://www.dns-lg.com/nodes.json');
  $nodes_raw = $req->get();
  $nodes = json_decode($nodes_raw);
  $nodes = $nodes->nodes;
  $nodes_json = json_encode($nodes);
} catch(CURLRequestException $e) {
  $errors[] = '<strong>Oh noes!</strong> ' . $e->getCode() . ': ' . $e->__toString();
}

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
    iframe#autocomplete-host {
      display: none;
    }
    .no-transition {
      -webkit-transition: none;
      -o-transition: none;
      transition: none;
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
        <h1><span class="glyphicon glyphicon-globe"></span> DNS Propagation Checker</h1>
      </div>

      <?php
      if( ! empty($errors)) {
        foreach($errors as $k=>$error) {
          echo '<div class="alert alert-danger" role="alert">' . $error . '</div>';
        }
      }
      ?>

    </div>

    <div class="col-md-4" id="toolbar">

      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">Options</h3>
        </div>
        <div class="panel-body">
          <iframe name="autocomplete_host" id="autocomplete-host" src=""></iframe>
          <form autocomplete="on" target="autocomplete_host">

            <div class="form-group">
              <label for="domain">Domain</label>
              <div class="input-group">
                <span class="input-group-addon">http://</span>
                <input type="text" class="form-control" id="domain" name="domain" required>
              </div>
            </div><!-- /form-group -->

            <div class="form-group">
              <label for="record-type">Record Type</label>
              <div class="btn-group" data-toggle="buttons">
                <label class="btn btn-sm btn-default active">
                  <input type="radio" name="recordType" value="a" checked>A
                </label>
                <label class="btn btn-sm btn-default">
                  <input type="radio" name="recordType" value="cname">CNAME
                </label>
                <label class="btn btn-sm btn-default">
                  <input type="radio" name="recordType" value="mx">MX
                </label>
                <label class="btn btn-sm btn-default">
                  <input type="radio" name="recordType" value="ns">NS
                </label>
                <label class="btn btn-sm btn-default">
                  <input type="radio" name="recordType" value="spf">SPF
                </label>
                <label class="btn btn-sm btn-default">
                  <input type="radio" name="recordType" value="txt">TXT
                </label>
              </div>
            </div>
            <div class="form-group">
              <label for="domain">Expected Value</label> <small>optional regex</small>
              <input type="text" class="form-control" id="expected" name="expected">
            </div><!-- /form-group -->

            <div class="form-group">
              <button class="btn btn-primary" type="submit" id="go" data-loading-text="Running...">Go!</button>
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


      <div class="col-md-12">
        <div class="progress">
          <div class="progress-bar progress-bar-success" style="width: 0%">
            10%
          </div>
          <div class="progress-bar progress-bar-warning" style="width: 0%">
            10%
          </div>
          <div class="progress-bar progress-bar-danger" style="width: 0%">
            10%
          </div>
        </div>
      </div>

      <table class="table table-condensed">
        <tr>
          <th>Server</th>
          <th>Result</th>
          <th>TTL</th>
        </tr>
        <?php
        if( ! empty($nodes)) {
          foreach($nodes as $node) {
            echo '<tr id="' . $node->name . '">';
            echo '<td width="175" class="country"><span data-toggle="tooltip" title="' . $node->operator . '">' . $node->country . ' ' . $node->name{3} . '</span></td>';
            echo '<td class="result"></td>';
            echo '<td width="50" class="ttl"></td>';
            echo '</tr>';
          }
        }
        ?>
      </table>

    </div>

  </div>


  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <?php
  if( ! empty($nodes)) {
  ?>
  <script src="js/app.js"></script>
  <script>
    DNSPC.app.query.setNodes(<?php echo $nodes_json; ?>);
    DNSPC.app.init();
  </script>
  <?php
  }
  ?>
</body>
</html>