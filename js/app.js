/* global $ */

var DNSPC = {};


DNSPC.$cache = {
  $window: $(window),
  $document: $(document),
  $form: $("form")
};


DNSPC.utility = (function() {

  "use strict";

  function secondsToApproxHours(seconds) {
    var hours = Math.round(seconds / 3600);
    return hours + "h";
  }

  return {
    secondsToApproxHours: secondsToApproxHours
  };

}()); // utility


DNSPC.app = (function() {

  "use strict";


  var ui = (function() {

    var searchForm = (function() {
      var $form = DNSPC.$cache.$form;
      
      function init() {
        $form.on("submit", function(event) {
          query.run(event);
        });
        DNSPC.$cache.$window.on("load", function() {
          $("#domain").trigger("focus");
        });
      }

      function getDomain() {
        return $.trim($("#domain").val());
      }

      function getRecordType() {
        return $form.find("input[name=recordType]:checked").val();
      }

      function getExpectedValue() {
        return $.trim($("#expected").val());
      }

      function submit() {
        $form.trigger("submit");
      }

      function disable() {
        $("#go").button("loading");
      }

      function enable() {
        $("#go").button("reset");
      }

      return {
        init: init,
        submit: submit,
        getDomain: getDomain,
        getRecordType: getRecordType,
        getExpectedValue: getExpectedValue,
        disable: disable,
        enable: enable
      };
    }());

    var hashStorage = (function() {
      function saveInputInHash() {
        window.location.hash = $("form").serialize();
      }

      function setInputFromHash() {
        var parts;
        var raw = window.location.hash;

        if(raw) {
          parts = raw.substring(1).split("&");
          parts.forEach(function(part) {
            var partBits = part.split("=");

            if(partBits[0] === "recordType") {
              $("input[name='recordType'][value='" + partBits[1] + "']").click();
            } else {
              $("input[type='text'][name='" + partBits[0] + "']").val(decodeURIComponent(partBits[1]));
            }
          });
          searchForm.submit();
        }
      }

      function init() {
        DNSPC.$cache.$form.on("submit", saveInputInHash);
        DNSPC.$cache.$window.on("load", setInputFromHash);
        DNSPC.$cache.$window.on("hashchange", setInputFromHash);
      }

      return {
        init: init
      };
    }());

    var progressOmnibar = (function() {
      var $progressBars = $(".progress-bar"),
          progressPercentageUnit = 0;

      function setProgressPercentageUnit(totalUnitsAvailable) {
        progressPercentageUnit = 100 / totalUnitsAvailable;
        return progressOmnibar;
      }

      // which: success, warning, danger
      function incrementSectionProgress(which) {
        var $bar = $(".progress-bar-" + which),
            currentValue = $bar.data("current-progress") || 0,
            newValue = currentValue + 1,
            currentPercent = newValue * progressPercentageUnit;
        $bar.css("width", currentPercent + "%");
        $bar.html(Math.round(currentPercent) + "%");
        $bar.data("current-progress", newValue);
        return progressOmnibar;
      }

      function reset() {
        $progressBars.removeData("current-progress");
        $progressBars.addClass("no-transition");
        $progressBars.css("width", 0);
        $progressBars.html("");
        return progressOmnibar;
      }

      function start() {
        reset();
        $progressBars.addClass("progress-bar-striped active");
        return progressOmnibar;
      }

      function finish() {
        $progressBars.removeClass("progress-bar-striped active");
        return progressOmnibar;
      }

      return {
        setProgressPercentageUnit: setProgressPercentageUnit,
        incrementSectionProgress: incrementSectionProgress,
        reset: reset,
        start: start,
        finish: finish
      };
    }()); // progressOnmibar

    var table = (function() {

      function getRowForNode(nodeCode) {
        return $("#" + nodeCode);
      }

      function makeTTLTooltip($row) {
        $row.find(".ttl-tooltip").tooltip({
          placement: "right"
        });
      }

      function setRowStatus($row, data) {
        // success, warning, danger, info
        if(data.status) {
          $row.addClass(data.status);
        }
        if(data.result) {
          $row.find(".result").html(data.result);
        }
        if(data.ttl) {
          $row.find(".ttl").html(data.ttl);
          makeTTLTooltip($row);
        }
      }

      function makeCountryTooltips() {
        $('.country span').tooltip({
          placement: "right"
        });
      }

      function setupRawErrorViewing() {
        DNSPC.$cache.$document.on("click", ".view-raw", function(event) {
          event.preventDefault();
          var $t = $(this);
          $t.parent().html("<iframe src=\"" + $t.attr("href") + "\"></iframe>");
        });
      }

      function reset() {
        $(".result, .ttl").html("");
        $("tr").removeClass("warning success danger info");
      }

      function init() {
        makeCountryTooltips();
        setupRawErrorViewing();
      }

      return {
        reset: reset,
        init: init,
        getRowForNode: getRowForNode,
        setRowStatus: setRowStatus
      };
    }()); // table

    function init() {
      hashStorage.init();
      progressOmnibar.reset();
      table.init();
      searchForm.init();
    }

    return {
      init: init,
      progressOmnibar: progressOmnibar,
      table: table,
      searchForm: searchForm
    };

  }()); // ui


  var query = (function() {

    var nodes = [],
        requestInProgress = false;

    function setNodes(inputNodes) {
      nodes = inputNodes;
      ui.progressOmnibar.setProgressPercentageUnit(nodes.length);
    }

    function finished() {
      ui.searchForm.enable();
      ui.progressOmnibar.finish();
      requestInProgress = false;
    }

    function run(event) {
      if(requestInProgress) {
        event.preventDefault();
        return false;
      }

      requestInProgress = true;

      var domain = ui.searchForm.getDomain();
      var recordType = ui.searchForm.getRecordType();
      var expected = ui.searchForm.getExpectedValue();
      var completedRequests = 0;

      ui.table.reset();
      ui.progressOmnibar.start();
      ui.searchForm.disable();

      nodes.forEach(function(node) {

        var $row = ui.table.getRowForNode(node.name);

        var req = $.ajax({
          url : "request.php?url=" + encodeURIComponent("http://www.dns-lg.com/" + node.name + "/" + domain + "/" + recordType),
          dataType : "json",
          timeout : 7000
        });

        req.always(function() {
          completedRequests++;
          if(completedRequests === nodes.length) {
            finished();
          }
        });

        req.done(function(data) {
          var rowStatus = {};

          if(typeof data.code !== "undefined") {
            rowStatus.result = data.code + ": " + data.message;
            rowStatus.status = "warning";
            ui.progressOmnibar.incrementSectionProgress("warning");
          } else if( ! data.answer) {
            rowStatus.result = "No record";
            rowStatus.status = "warning";
            ui.progressOmnibar.incrementSectionProgress("warning");
          } else if(data.answer) {

            var results = [];
            var ttls = [];

            data.answer.forEach(function(thisAnswer) {
              results.push(thisAnswer.rdata);
              ttls.push("<span class=\"ttl-tooltip\" data-toggle=\"tooltip\" title=\"" + thisAnswer.ttl + " seconds\">" + DNSPC.utility.secondsToApproxHours(thisAnswer.ttl) + "</span>");
            });

            rowStatus.result = results.join("<br>");
            rowStatus.ttl = ttls.join("<br>");

            if(expected) {
              var allMatch = data.answer.every(function(thisAnswer) {
                return thisAnswer.rdata.match(new RegExp("^" + expected + "$", "i"));
              });
              if(allMatch) {
                rowStatus.status = "success";
                ui.progressOmnibar.incrementSectionProgress("success");
              } else {
                rowStatus.status = "danger";
                ui.progressOmnibar.incrementSectionProgress("danger");
              }
            } else {
              ui.progressOmnibar.incrementSectionProgress("success");
            }
          } else {
            rowStatus.result = "Unknown response";
            rowStatus.status = "warning";
            ui.progressOmnibar.incrementSectionProgress("warning");
          }

          ui.table.setRowStatus($row, rowStatus);
        });

        req.fail(function() {
          ui.table.setRowStatus($row, {
            result: "Request timed out. <a href=\"http://www.dns-lg.com/" + node.name + "/" + domain + "/" + recordType + "\" target=\"_blank\" class=\"view-raw\">View Raw</a>",
            status: "warning"
          });
          ui.progressOmnibar.incrementSectionProgress("warning");
        });

      });
    }

    return {
      setNodes: setNodes,
      run: run
    };

  }()); // query


  function init() {
    ui.init();
    return DNSPC.app;
  }


  return {
    init: init,
    query: {
      setNodes: query.setNodes
    }
  };

}()); // app