<!--
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
-->
<?php global $path; ?>
<!------------------------------------------------------------------------------------------
  Dashboard related javascripts
------------------------------------------------------------------------------------------->
<script type="text/javascript" src="<?php print $path; ?>Vis/flot/jquery.js"></script>
<script type="text/javascript" src="<?php print $path; ?>Vis/flot/jquery.flot.js"></script>
<script type="text/javascript" src="<?php print $path; ?>Vis/Dashboard/widgets/dial.js"></script>
<script type="text/javascript" src="<?php print $path; ?>Vis/Dashboard/widgets/led.js"></script>

<!------------------------------------------------------------------------------------------
  Dashboard HTML
------------------------------------------------------------------------------------------->

        <div style="text-align:center; width:100%;">
          <div style="width: 960px; margin: 0px auto; padding:0px; text-align:left; margin-bottom:20px; margin-top:20px;">


<div style="background-color:#ccc; width:99.0%; padding:5px; border-radius: 3px;">
<textarea rows="20"  id="editarea" style="width:99.4%; border-radius: 2px;"></textarea>
<button type="button" id="editsave" class="button05" >Save</button>
<button type="button" id="editclose" class="button05" >Close</button>
<button type="button" id="edit" class="button05" >Edit Dashboard</button>
</div>
<br/>

<div id="page">
<?php echo $page; ?>
</div>
          <div style="clear:both;"></div> 
          </div>
        </div>

<script type="application/javascript">

$(function() {
  var path = "<?php echo $path; ?>";
  var apikey_read = "<?php echo $apikey_read; ?>";
  var apikey_write = "<?php echo $apikey_write; ?>";

  $("#editarea").hide();
  $("#editclose").hide();
  $("#editsave").hide();

  $("#editarea").val($("#page").html());			// Place page html in edit area ready for editing

  //------------------------------------------------------
  // Save changes made to edit area
  //------------------------------------------------------
  $("#editsave").click(function(){
    $("#page").html($("#editarea").val());			// Update page html
    update();							// Run javascript

    // Upload changes to server
    $.ajax({                                      
      type: "POST",
      url: path+"dashboard/set",     
      data: "&content="+encodeURIComponent($("#editarea").val()),
      dataType: 'json',   
      success: function() { }
    });
  });

  //------------------------------------------------------
  // Handle Edit and close buttons
  //------------------------------------------------------
  $("#editclose").click(function(){
      $("#editarea").hide();
      $("#editclose").hide();
      $("#edit").show();
      $("#editsave").hide();
  });

  $("#edit").click(function(){
      $("#editarea").show();
      $("#editclose").show();
      $("#edit").hide();
      $("#editsave").show();
  });

  var feedNames = [];
  var feedids = [];		// Array that holds ID's of feeds of associative key
  var assoc = [];		// Array for exact values
  var assoc_curve = [];		// Array for smooth change values - creation of smooth dial widget

  var firstdraw = 1;

  update();
  setInterval(update,30000);
  setInterval(fast_update,15);
  setInterval(slow_update,60000);
  slow_update();

  function update()
  {
        $.ajax({                                      
          url: path+"feed/list.json",                
          dataType: 'json',
          success: function(data) 
          { 
	        var d = new Date();
            var localOffset = d.getTimezoneOffset() * 60000;
            var day = d.getDate();
            var month = d.getMonth();
            var year = d.getFullYear();
            var start = Date.UTC(year,month,day-1)+localOffset-1000;
            var end = Date.UTC(year,month,day)+localOffset;
              
            

            for (z in data)
            {
              var newstr = data[z][1].replace(/\s/g, '-');

              var value = parseFloat(data[z][4]);
              if (value<100) value = value.toFixed(1); else value = value.toFixed(0);
              var cost = (value *.08692).toFixed(2);
              console.log(newstr);
              $("."+newstr).html(value+"kWh");
              $("."+newstr+"_cost").html(cost);
              assoc[newstr] = value*1;
              feedids[newstr] = data[z][0];
              feedNames[data[z][0]] = newstr;
 
              //ajax call to get yesterday usage to compare to todays
              //feed must be tagged as Daily we arent interested in realtime feeds
              if (data[z][2]=="Daily"){
              $.ajax({
	            url: path+"feed/data.json",
                dataType: 'json',
                data: "id="+data[z][0]+"&start="+start+"&end="+end+"&res=1",
          		success: function(yesterdayData) 
              	{
	              	var feed = this.url.match(/id=\d*/)[0].replace(/id=/,'');
	            	var yesvalue = parseFloat(yesterdayData[0][1]);
	            	var percent = ((assoc[feedNames[feed]]/yesvalue)*100).toFixed(0);
	            	if (yesvalue<100) yesvalue = yesvalue.toFixed(1); else yesvalue = yesvalue.toFixed(0);
	            	var el = document.getElementById(feedNames[feed]+"_yesterday");
	            	if (el !== null){
	            		if (percent > 100) {el.style.background="red"; percent =100;}
	            		el.style.width = percent+"%";
            		}
	            	$("."+feedNames[feed]+"_yesterday").html(percent);
              		assoc[feedNames[feed]+"_yesterday"] = percent*1;
              		feedids[feedNames[feed]+"_yesterday"] = data[z][0];
              		console.log(feedNames[feed]+"_yesterday "+percent);
              	}});
          	   }
            }

            draw_graphs();
  
            // Calls specific page javascript update function for any in page javascript
            if(typeof page_js_update == 'function') { page_js_update(assoc); }
            //--------------------------------------------------------------------------

          }  // End of data return function
        });  // End of AJAX function

  } // End of update function


  function fast_update()
  {
    draw_dials();
    draw_leds();
  }

  function slow_update()
  {
  }

  function curveValue(start,end,rate)
  {
    if (!start) start = 0;
    return start + ((end-start)*rate);
  }

  function draw_dials()
  {
           $('.dial').each(function(index) {
              var feed = $(this).attr("feed");
              var maxval = $(this).attr("max");
              var units = $(this).attr("units");
              var scale = $(this).attr("scale");

              assoc_curve[feed] = curveValue(assoc_curve[feed],parseFloat(assoc[feed]),0.02);
              var val = assoc_curve[feed]*1;

                var id = "can-"+feed+"-"+index;

                if (!$(this).html()) {	// Only calling this when its empty saved a lot of memory! over 100Mb
                  $(this).html('<canvas id="'+id+'" width="200px" height="160px"></canvas>');
                  firstdraw = 1;
                }

              if ((val*1).toFixed(1)!=(assoc[feed]*1).toFixed(1) || firstdraw == 1){ //Only update graphs when there is a change to update
                var canvas = document.getElementById(id);
                var ctx = canvas.getContext("2d");
                draw_gauge(ctx,200/2,100,80,val*scale,maxval,units); firstdraw = 0;
              }
            });
  }

function draw_leds()
  {
           $('.led').each(function(index) {
              var feed = $(this).attr("feed");
              var val = assoc[feed];
	         var id = "canled-"+feed+"-"+index;
                if (!$(this).html()) {	// Only calling this when its empty saved a lot of memory! over 100Mb
                  $(this).html('<canvas id="'+id+'" width="50px" height="50px"></canvas>');
                  firstdraw = 1;
                }

       //   if ( firstdraw == 1){ //Only update graphs when there is a change to update

                var canvas = document.getElementById(id);
                var circle = canvas.getContext("2d");
                draw_led(circle,val); 
			firstdraw = 0;
       //       }
            });
  }



  function draw_graphs()
  {
    $('.graph').each(function(index) {
      var feed = $(this).attr("feed");
      var id = "#"+$(this).attr('id');
      var feedid = feedids[feed];
      $(id).width(200);
      $(id).height(200);

      var data = [];

      var timeWindow = (3600000*12);
      var start = ((new Date()).getTime())-timeWindow;		//Get start time

      var ndp_target = 200;
      var postrate = 5000; //ms
      var ndp_in_window = timeWindow / postrate;
      var res = ndp_in_window / ndp_target;
      if (res<1) res = 1;
      $.ajax({                                      
          url: path+"feed/data.json",                         
          data: "&apikey="+apikey_read+"&id="+feedid+"&start="+start+"&end="+0+"&res="+res,
          dataType: 'json',                           
          success: function(data) 
          { 
             $.plot($(id),
              [{data: data, lines: { fill: true }}],
              {xaxis: { mode: "time", localTimezone: true },
              grid: { show: true }
             });
          } 
      });
    });
  }



}); 

</script>
