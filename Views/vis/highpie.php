<!--
   All Emoncms code is released under the GNU General Public License v3.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
-->
  <?php  
    global $path, $embed;
	$data= array();
    $names = array();
    $apikey = $_GET["apikey"];
	unset($_GET["embed"]);
    unset($_GET["apikey"]);
	unset($_GET["q"]);
 
	foreach ($_GET as &$var) {
	      $data[]=get_feed_data($var,0,0,0);
    }
    $names=array_keys($_GET);
  ?>

   <!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path; ?>Includes/flot/excanvas.min.js"></script><![endif]-->
    <script language="javascript" type="text/javascript" src="<?php echo $path; ?>Includes/flot/jquery.js"></script>
    <script language="javascript" type="text/javascript" src="<?php echo $path; ?>Includes/flot/date.format.js"></script>
    <script language="javascript" type="text/javascript" src="<?php echo $path; ?>Views/vis/common/api.js"></script>
    <script language="javascript" type="text/javascript" src="<?php echo $path; ?>Views/vis/common/daysmonthsyears.js"></script>
    <script language="javascript" type="text/javascript" src="http://code.highcharts.com/highcharts.js"></script>
	<script language="javascript" type="text/javascript" src="http://code.highcharts.com/modules/exporting.js"></script>

<?php if (!$embed) { ?>
<h2>MyKWHdStacked</h2>
<?php } ?>

    <div id="test" style="height:400px; width:100%; position:relative; ">
      <div id="placeholder" style="font-family: arial;"></div>
      <div id="loading" style="position:absolute; top:0px; left:0px; width:100%; height:100%; background-color: rgba(255,255,255,0.5);"></div>
      <h2 style="position:absolute; top:0px; left:40px;"><span id="out"></span></h2>
    </div>

    <script id="source" language="javascript" type="text/javascript">
      var data = [<?php foreach($data as &$feedData){echo json_encode($feedData); echo ",";}?>];
      var names = ["<?php echo join("\", \"", $names); ?>"];
      var monthNames = [ "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ];
      var path = "<?php echo $path; ?>";  
      var apikey = "<?php echo $apikey; ?>";

        $('#placeholder').width($("#test").width());
        $('#placeholder').height($('#test').height());
        $('#loading').hide();
 
        var days = [];
        var months = [];
        for (i=0; i < data.length;i++){
	    	months[i]= get_months(data[i]);    
        }
        var d = new Date();
        for (i=0; i < data.length;i++){
			days[i]= get_last_30days(data[i]);    
        }
        highchart(days,names,3600*22);

    	        
        function highchart(months,names,barwidth)
        {
	        //Holds the Data from each monitored device per day
	        var plotData=[];
	        //Columns represents each day
	        var columns=[]; 
	        for (i=0;i<months.length;i++){
		      var roomData=[];
		      for (z=0;z<months[0].length;z++){
            try{
			        roomData[z]=parseFloat(parseFloat(months[i][z][1]).toFixed(2));
            }catch(err){roomData[z]=0}
		      }
		      roomData.reverse();    
		      plotData[i] = {name:names[i] , data:roomData};
	      	}
	      	for(i=0;i<months[0].length;i++){
		      var d = new Date(months[0][i][0]);
		      columns[i]=monthNames[d.getMonth()]+" "+d.getDate();
	      	}
	      	columns.reverse();
	      	chart = new Highcharts.Chart({
            chart: {renderTo: 'placeholder', type: 'column'},
            title: {text: 'Daily power stack up'},
            xAxis: {
                categories: columns,
                tickmarkPlacement: 'on',
                title: {enabled: false}
            },
            yAxis: { // Primary yAxis
            labels: {
				formatter: function() {
					return this.value+'kWh</b><br/>$'+(this.value*.11).toFixed(2);
				},
				style: {
					color: '#89A54E'
				}
			},
			title: {
				text: 'Usage',
				style: {
					color: '#89A54E'
				}
			},
			opposite: true

			},
            tooltip: {
			formatter: function() {
				return '<b>'+ this.x +' '+this.series.name+'</b><br/>'+
					'Power Usage: '+ this.y +'kWh<br/>'+
					'Cost: $'+ (this.y*.11).toFixed(2) +'<br/>'+
					Math.round(((this.y/this.total)*100))+'% of Total';
			}
            },
            plotOptions: {
                column: {
                    stacking: 'column',
                    lineColor: '#ffffff',
                    lineWidth: 1,
                    marker: {
                        lineWidth: 1,
                        lineColor: '#ffffff'
                    }
                }
            },
            series: plotData
        });
        }

    </script>
  </body>
</html>

