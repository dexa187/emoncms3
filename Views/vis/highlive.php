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
 
	$feed=$_GET["feedid"];
  ?>         
<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path; ?>Includes/flot/excanvas.min.js"></script><![endif]-->          
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Includes/flot/jquery.js"></script>          
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Includes/flot/date.format.js"></script>          
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Views/vis/common/api.js"></script>          
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Views/vis/common/daysmonthsyears.js"></script>          
<script language="javascript" type="text/javascript" src="http://code.highcharts.com/highcharts.js"></script>   	   
<script language="javascript" type="text/javascript" src="http://code.highcharts.com/modules/exporting.js"></script>     
<?php if (!$embed) { ?><h2>MyKWHdStacked</h2>
<?php } ?>       
<div id="test" style="height:400px; width:100%; position:relative; ">               
  <div id="placeholder" style="font-family: arial;">        
  </div>               
  <div id="loading" style="position:absolute; top:0px; left:0px; width:100%; height:100%; background-color: rgba(255,255,255,0.5);">        
  </div>               
  <h2 style="position:absolute; top:0px; left:40px;">              
    <span id="out">              
    </span></h2>       
</div>       
<script id="source" language="javascript" type="text/javascript"> 

      Highcharts.setOptions({
		    global: {useUTC: false}
	    });
      var data = [<?php foreach($data as &$feedData){echo json_encode($feedData); echo ",";}?>];
      var path = "<?php echo $path; ?>";
      var feedid ="<?php echo $feed; ?>";  
      var apikey = "<?php echo $apikey; ?>";
      var timeWindow = (300*1000);  //300 seconds
      start = ((new Date()).getTime())-timeWindow;		//Get start time
      end = (new Date()).getTime();				//Get end time
      var feedData = get_feed_data(feedid,start,end,2);
      var feedList = get_feed_list(apikey);
      var feedName = "";
      for (i=0;i<feedList.length;i++){
        if  (feedList[i][0]==feedid){
          feedName=feedList[i][1];
        }
      }
      for (i=0;i<feedData.length;i++){
        feedData[i][1]=parseFloat(feedData[i][1]);
      }
      feedData.reverse();
      
        $('#placeholder').width($("#test").width());
        $('#placeholder').height($('#test').height());
        $('#loading').hide();
 
        highchart(feedid,feedName,feedData);
        
        function highchart(feedid,feedName,feedData){
	        
        var chart;
        chart = new Highcharts.Chart({
            chart: {
                renderTo: 'placeholder',
                type: 'spline',
                events: {
                    load: function() {
    
                        // set up the updating of the chart each second
                        var series = this.series[0];
                        setInterval(function() {
                            start = ((new Date()).getTime())-timeWindow;		//Get start time
                            end = (new Date()).getTime();				//Get end time
                            y = get_feed_data(feedid,start,end,2);
                            series.addPoint([end,parseFloat(y[0][1])],true,true);
                        }, 10000);
                    }
                }
            },
            title: {text: 'Live 5min data'},
            xAxis: {
			         type: 'datetime'
		        },
		        yAxis: {
			        title: {
				      text: 'kWh'
			       },
			       plotLines: [{
				      value: 0,
				      width: 1,
				      color: '#808080'
			       }]
		        },
            series: [{name: feedName ,data: feedData}]
        });         
    	}
    </script>        
</body>
</html>