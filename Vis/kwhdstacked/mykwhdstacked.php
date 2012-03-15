
<html>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<!--
   All Emoncms code is released under the GNU General Public License v3.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
-->
  <?php $path = "/Vis";
  $path = dirname("http://".$_SERVER['HTTP_HOST'].str_replace('Vis/kwhdstacked', '', $_SERVER['SCRIPT_NAME']))."/";
  ?>

  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <script language="javascript" type="text/javascript" src="<?php echo $path; ?>Vis/flot/jquery.js"></script>
    <script language="javascript" type="text/javascript" src="<?php echo $path; ?>Vis/flot/jquery.flot.js"></script>
    <script language="javascript" type="text/javascript" src="<?php echo $path; ?>Vis/flot/jquery.flot.stack.js"></script>
    <script language="javascript" type="text/javascript" src="<?php echo $path; ?>Vis/flot/date.format.js"></script>
    <script language="javascript" type="text/javascript" src="kwhd_functions.js"></script>

    <?php
      require "../../Includes/db.php";
      $e = db_connect();

      require "../../Models/feed_model.php";
      $data= array();
      $names = array();
      $apikey = $_GET["apikey"];
      unset($_GET["apikey"]);
      foreach ($_GET as &$var) {
	      $data[]=get_all_feed_data($var);
      }
      $names=array_keys($_GET);
    ?>

  </head>
  <body style="margin: 0px; padding:10px; font-family: arial; background-color:rgb(245,245,235);">

    
    <div id="test" style="height:100%; width:100%; position:relative; ">
      <div id="placeholder" style="font-family: arial;"></div>
      <div id="loading" style="position:absolute; top:0px; left:0px; width:100%; height:100%; background-color: rgba(255,255,255,0.5);"></div>
      <h2 style="position:absolute; top:0px; left:40px;"><span id="out"></span></h2>
    </div>

    <script id="source" language="javascript" type="text/javascript">
      var data = [<?php foreach($data as &$feedData){echo json_encode($feedData); echo ",";}?>];
      //var names = [<?php foreach($names as &$name){echo "$name"; echo ",";}?>];
      var names = ["<?php echo join("\", \"", $names); ?>"];
	         
      var path = "<?php echo $path; ?>";  

      // API key
      var apikey = "<?php echo $apikey; ?>";

	  $(function () {
        $('#placeholder').width($("#test").width());
        $('#placeholder').height($('#test').height());

        $('#loading').hide();
        var view = 1;
 
        var days = [];
        var months = [];
        for (i=0; i < data.length;i++){
	    	months[i]= get_months(data[i]);    
        }
        var d = new Date();
        for (i=0; i < data.length;i++){
	    	days[i]= get_last30_days(data[i],d.getDate(),d.getMonth(),d.getFullYear());    
        }
        //mybargraph(months,names,3600*24*20);
        mybargraph(days,names,3600*22);

        $("#placeholder").bind("plotclick", function (event, pos, item)
        {
          if (item!=null)
          {
            if (view==1)
            {

            }
            if (view==0)
            {
              var d = new Date();
              d.setTime(item.datapoint[0]);
              for (i=0; i < data.length;i++){
	    		days[i]= get_days_month(data[i],d.getMonth(),d.getFullYear());    
              }
              mybargraph(days,names,3600*22);
              view = 1;
              $("#out").html("");
            }
          }
          else
          {
   
            if (view==1) { $("#out").html(""); view = 0; mybargraph(months,names,3600*24*20); }     
            if (view==2) { $("#out").html(""); view = 1; mybargraph(days,names,3600*22); }      

          }
        });


    function showTooltip(x, y, contents) {
	    var offset = (x<250)?5:-175;
        $('<div id="tooltip">' + contents + '</div>').css( {
            position: 'absolute',
            display: 'none',
            top: y + 5,
            left: x + offset,
            border: '1px solid #fdd',
            padding: '2px',
            'border-radius': '15px',
            'background-color': 'lightgray',
            opacity: 0.85
        }).appendTo("body").fadeIn(200);
    }
        var previousPoint = null;
        $("#placeholder").bind("plothover", function (event, pos, item) {
        $("#x").text(pos.x.toFixed(2));
        $("#y").text(pos.y.toFixed(2));

            if (item) {
                if (previousPoint != item.dataIndex) {
                    previousPoint = item.dataIndex;
                    
                    $("#tooltip").remove();
                    var x = item.datapoint[0].toFixed(2),
                        y = item.datapoint[1].toFixed(2);
                    var d = new Date();
                    d.setTime(item.datapoint[0]);
                    var mdate = new Date(item.datapoint[0]);

                    if (view==0) showTooltip(pos.pageX, pos.pageY, item.series.label +"<br/>"+mdate.format("mmm yyyy")+"<br/>"+"Energy Used:"+(item.datapoint[1]-item.datapoint[2]).toFixed(1)+" kWh" +"<br/>"+"Cost: $" +((item.datapoint[1]-item.datapoint[2])*.086).toFixed(2));
					if (view==1) showTooltip(item.pageX, item.pageY, item.series.label +"<br/>"+mdate.format("mmm dS yyyy")+"<br/>"+"Energy Used:"+(item.datapoint[1]-item.datapoint[2]).toFixed(1)+" kWh" +"<br/>"+"Cost: $" +((item.datapoint[1]-item.datapoint[2])*.086).toFixed(2));
//                     showTooltip(item.pageX, item.pageY,
//                                 item.series.label + " of " + x + " = " + y);
                }
            }
            else {
                $("#tooltip").remove();
                previousPoint = null;            
            }
    	});
    	
    	function formatKwh(v,axis){
	    	return v+"kWh";
    	}
    	

        function mybargraph(months,names,barwidth)
        {
	      var plotData=[];  
	      var options = {"yaxis":[{"tickFormatter":function (v,axis) { return ''+v; 
}},{"tickFormatter":function (v,axis) { return ''+v; }}]}; 
	      for (i=0;i<months.length;i++){
		      var randColor = '#'+Math.floor(Math.random()*16777215).toString(16);
		      //plotData[i] = {label:names[i] , color: "#0096ff", data:months[i]};
		      plotData[i] = {label:names[i] , data:months[i]};
		      
	      }
          $.plot($("#placeholder"), plotData, 
          {
	        
            series: {
            stack: true,
            bars: { show: true,barWidth: (barwidth*1000),fill: true }
            },
  	    grid: { show: true, hoverable: true, clickable: true },
            xaxis: { mode: "time"},
            yaxis: { position: 'right',tickFormatter: function (v) { return "$"+(v*.086).toFixed(2); }}
          });
        }
     });
    </script>
  </body>
</html>

