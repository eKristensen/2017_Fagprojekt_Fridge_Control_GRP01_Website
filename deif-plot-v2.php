<?php 

require __DIR__ . '/vendor/autoload.php';
$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();
header('Content-Type: text/html; charset=ISO-8859-1');
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-1">
  <meta name="viewport" content="width=device-width, initial-scale=1">
<script>
window.paceOptions = {
    ajax: {
        trackMethods: ['GET', 'POST', 'PUT', 'DELETE', 'REMOVE']
    }
};
</script>
  <script src='pace/pace.min.dynamic.js'></script>
  <link href="pace/flat.css" rel="stylesheet" />
  
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
<script src="https://code.highcharts.com/stock/highstock.js"></script>
<script src="https://code.highcharts.com/stock/modules/exporting.js"></script>
<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
  <script>
  var options = null;
  var names = null;
  var chart = null;
  var refeshable = [];
  var time = [];
  var timer = null;
  var lastupdate = null;
  var idleTime = null;
  var initiallength = 0;
  var sock = null;
  var reconnectalert = false;
  var lastredraw = null;
  var tinydb = [];
  var tinydblock = false; //true if data calculation is in proccess, result i that data is paused, and qued.
  var offset = 0;

Highcharts.setOptions({
    global: {
        timezoneOffset: -2 * 60
    }
});
$(document).ajaxStart(function() { Pace.restart(); });

  $(document).ready(function() {

    var idleInterval = setInterval(function(){idleTime++;}, 1000); //*0.2*refeshable.length

    //Zero the idle timer on mouse movement.
    $(this).mousemove(function (e) {
        idleTime = 0;
    });
    
    $(this).keypress(function (e) {
        idleTime = 0;
    });

    $.ajax({ url: "deif-select.php?type=selectgroup",
        context: document.body,
        success: function(data){
            $('#selectgroup').html(data);
        }});
    $.ajax({ url: "deif-select.php?type=selecttype",
        context: document.body,
        success: function(data){
            $('#selecttype').html(data);
        }});
    $.ajax({ url: "deif-submit-v2.php",
        context: document.body,
        success: function(data){
            $('#valgte').html(data);
                $("[data-toggle='toggle']").bootstrapToggle('destroy');             
                $("[data-toggle='toggle']").bootstrapToggle();
        }});

          var last_valid_selection = null;

    $('#reset').click(function() {
          if (chart != null) {
            reconnectalert = false;
        $('#container').highcharts().destroy();
      $('#stoplive').hide();
            $('#info').html('Live data stopped. Press Get plot in order to restart it.');
        clearInterval(timer);
        sock.close();
    }
        chart = null;
      $("#container").animate({
      'width' : '0%',
      'height': '0%' 
    });
        $.ajax({ url: "deif-submit-v2.php?reset",
        context: document.body,
        success: function(data){
            $('#selectdevices').html('');
            $('#selectmes').html('');
            $('#valgte').html('');
            $("option:selected").prop("selected", false);
        }});
    });


    $('#stoplive').click(function() {

      $('#stoplive').hide();
            $('#info').html('Live data stopped. Press Get plot in order to restart it.');
            reconnectalert = false;
        sock.close();
    });

          $('form').submit(function(event) {

            var myForm = $(this);
            var data = $(this).serializeArray();//serialize form inputs and pass them to php
            $.post("deif-submit-v2.php",data,function(data){
                $('#valgte').html(data);
                $("[data-toggle='toggle']").bootstrapToggle('destroy');             
                $("[data-toggle='toggle']").bootstrapToggle();
            });
                $('#selectdevices').html('');
                $('#selectmes').html('');
            $("option:selected").prop("selected", false);
            event.preventDefault();


          });

          $('#selectgroup').change(function(event) {

            var myForm = $(this);
            var data = $(this).serializeArray();//serialize form inputs and pass them to php
            $.post("deif-select.php?type=group",data,function(data){
                $('#selectdevices').html(data);
            });

            if ($(this).val().length > 1) {

              $(this).val(last_valid_selection);
            } else {
              last_valid_selection = $(this).val();
            }
          });

          $('#selecttype').change(function(event) {

            var myForm = $(this);
            var data = $(this).serializeArray();//serialize form inputs and pass them to php
            $.post("deif-select.php?type=type",data,function(data){
                $('#selectmes').html(data);
            });

            if ($(this).val().length > 1) {

              $(this).val(last_valid_selection);
            } else {
              last_valid_selection = $(this).val();
            }
          });
        });

var seriesOptions = [],
    seriesCounter = 0,
    updateCounter = 0;

function remove(what,id) {

            $.ajax({ url: "deif-submit-v2.php?remove="+id+"&what="+what,
        context: document.body,
        success: function(data){
                $('#valgte').html(data);
                $("[data-toggle='toggle']").bootstrapToggle('destroy');             
                $("[data-toggle='toggle']").bootstrapToggle();
        }});
}

function toggle(key,keyy) {

            $.ajax({ url: "deif-submit-v2.php?key="+key+"&keyy="+keyy,
        context: document.body,
        success: function(data){
                $('#valgte').html(data);
                $("[data-toggle='toggle']").bootstrapToggle('destroy');             
                $("[data-toggle='toggle']").bootstrapToggle();
        }});
}

function updatePlot(series) {
    var now = Date.now() / 1000 | 0
    if (!document.hidden && (idleTime < 120 || (now-lastupdate) > 60)) {
    lastupdate = Date.now() / 1000 | 0;
    updateCounter = 0;
    $.each(names, function (i, name) {
      if (refeshable[i]) {
          $.getJSON('https://it.pf.dk/fagprojekt/deif-getdata-v2.php?sID=' + name[0] + '&dID=' + name[1] + '&tID=' + name[2] + '&time='+ time[i] +'&callback=?',    function (data) {
                $.each(data, function(j,points) {
              series[i].addPoint([data[j][0],data[j][1]], false, true);
              time[i] = data[j][0]/1000;
                })

        updateCounter += 1;

        if (updateCounter === names.length) {
    chart.redraw();
        }
    });
        }
    });
    chart.redraw()
  } else console.log('Didnt update, window is hidden/reduced interval due to inactivity');

}

function getplot() {
    var now = Date.now();
    if (chart != null) {
      reconnectalert = false;
        $('#container').highcharts().destroy();
        clearInterval(timer);
        sock.close();
    }
      $('#stoplive').hide();
            $('#info').html('Live streaming will start after plot has loaded.');
      $("#container").animate({
      'width' : '100%',
      'height': '100%' 
    });
      $("body").animate({"scrollTop": "0px"}, 1000);
      $( "#matlab" ).html('');
    seriesOptions = [];
    seriesCounter = 0;
    initiallength = 0;
    options = null;
    names = null;
    twoload = [];
    tdata = [];
    cached = [];
    tname = [];
    //time = Date.now() / 1000 | 0; //unixtimestamp in secs
    $.getJSON('deif-getplot-v2.php?&callback=?',    function (data) {
        options = data;
        names = data[1];
        //alert(JSON.stringify(options[0]));
               $.each(names, function (i, name) {

$( "#matlab" ).append( '<a href=\"https://it.pf.dk/fagprojekt/deif-getdata-v2.php?sID=' + name[0] + '&dID=' + name[1] + '&tID=' + name[2] + '&type=MATLAB\">'+name[4]+'</a><br>');

twoload[i] = 0;

    if ($.cookie("FC_Data_Plot_DEIF_toggle").toString() == "0") {
        $.getJSON('https://it.pf.dk/fagprojekt/deif-getdata-cached-v2.php?sID=' + name[0] + '&dID=' + name[1] + '&tID=' + name[2] + '&callback=?',    function (data2) {
            
            cached[i] = data2;
            twoload[i] += 1;
            if (twoload[i] === 2) {
              addToPlot(tdata[i],cached[i],tname[i],i,seriesOptions)
            }
            
            
            seriesCounter += 1;
            if (seriesCounter === names.length*2) {
            chart = Highcharts.stockChart('container', options[0]);
            var done = Date.now();
            var loadtime = done-now;
            console.log(initiallength+" datapoints loaded time: " + loadtime/1000 + " s");
        }
        });
        }
        

        $.getJSON('https://it.pf.dk/fagprojekt/deif-getdata-v2.php?sID=' + name[0] + '&dID=' + name[1] + '&tID=' + name[2] + '&callback=?',    function (data) {
        
            tdata[i] = data;
            tname[i] = name;
            twoload[i] += 1;
            if (twoload[i] === 2 || $.cookie("FC_Data_Plot_DEIF_toggle").toString() == "1") {
              addToPlot(tdata[i],cached[i],tname[i],i,seriesOptions)
            }
        

        seriesCounter += 1;

        if (seriesCounter === names.length*2 || $.cookie("FC_Data_Plot_DEIF_toggle").toString() == "1" && seriesCounter === names.length) {
            chart = Highcharts.stockChart('container', options[0]);
            var done = Date.now();
            var loadtime = done-now;
            console.log(initiallength+" datapoints loaded time: " + loadtime/1000 + " s");
        }
    });
}); 
    });

}

function addToPlot(data,cached,name,i,seriesOptions) {
                        refeshable[i] = true;
                        if (data == undefined) data = [];
                        if (cached == undefined) cached = [];
                    if( !$.isArray(data) ||  !data.length || data == "," || data == ",," || data == ",,," || data == "") {
                      var plotdata = cached;
                      refeshable[i] = false;
                      console.log(name[4] + " no new data, only cached used. Won't liveupdate this dataseries.");
                    }
                    else if( !$.isArray(cached) ||  !cached.length || cached == "," || cached == ",," || cached == "") {
                      var plotdata = data;
                      console.log(name[4] + " no cached data, only new data used");
                    } else {
                      var plotdata = cached.concat(data);
                      console.log(name[4] + " no problems");
                    }
                    if (plotdata == undefined) plotdata = [];

                    if (name[2] == "voltage_a_n") col = 0;
        else if (name[2] == "voltage_b_n") col = 1;
        else if (name[2] == "voltage_c_n") col = 2;
        else if (name[2] == "active_power_a") col = 3;
        else col = i;


        seriesOptions[i] = {
            name: name[4],
          yAxis: name[3],
            color: Highcharts.getOptions().colors[col],
            data: plotdata,
                    tooltip: {
            valueSuffix: ' ' + name[5],
            pointFormat: '{series.name}: <b>{point.y:.2f} '+name[5]+'</b><br/>' 
        }
        };
        initiallength+= plotdata.length;
          if (plotdata.length != 0) time[i] = plotdata[plotdata.length-1][0]/1000;
          else time[i] = Date.now();
}
var socketnames = [];
function startLiveUpdate(series) {
  reconnectalert = true;
    sock = new WebSocket("wss://it.pf.dk/fagprojekt/deif");

    lastredraw = Date.now();
    sock.onopen = function(event) {
      $('#stoplive').show();
      console.log('Socket connected successfully');
//
      socketnames = [];
      tinydb = [];
                 $.each(names, function (i, name) {
if (refeshable[i]){
if (name[0] <=1){
      if (socketnames.indexOf(name[1].toString()) == -1) socketnames.push(name[1].toString());
}
else if (name[0] == 2 || name[0] == 3) {
tinydb[i] = [];
$.each(name[1],function (j,nam){
	  //socketnames creation
      if (socketnames.indexOf(nam.toString()) == -1) socketnames.push(nam.toString());


      	tinydb[i].push([nam.toString(),name[2][j],0,0]);
});

}


}
               });
      //console.log("names: "+JSON.stringify(names)+"\n\n"+"sock: "+JSON.stringify(socketnames));
      sock.send(JSON.stringify({
        type: "name",
        data: socketnames
      }))
    };
            $('#info').html('Streaming data.');
    sock.onmessage = function(event) {
      //{"ly325.evse.02.right","ly325.util.evse.04"}
      var msg = JSON.parse(event.data);
      //console.log("id: "+msg.id+" time: "+msg.timestamp+ " mes: "+msg.measurements);
      //console.log(msg);

                 $.each(names, function (i, name) {
                 	var devid = msg.id
                    if (name[1].toString() == devid) {
                      var y = null;
                      var kind = name[2].toString();
                 $.each(msg.measurements, function (j, mes) {
                  if (mes.id.toString() == kind) {
                    y = mes.value;
                    //console.log("mes id: "+mes.id.toString()+ " kind: "+kind);
                  }
                 });
                 /*
                    console.log("id: "+msg.id+" x: "+msg.timestamp+ " y: "+y + " search name: "+name[2].toString() + " series id: "+i);
                    console.log(msg.timestamp);
                    console.log(y);*/
                        if (name[2].toString() == "active_power_a" || name[2].toString() == "active_power_b" || name[2].toString() == "active_power_c" || name[2].toString() == "active_power_sum") y = Math.abs(y);
                      series[i].addPoint([msg.timestamp,y], false, false);
/*
                    for (var q = 0; q < tinydb.length; q++) {
			      	if (tinydb[q][0] == devid && tinydb[q][1] == kind) {
			      		while(tinydblock) {}//Lås tinydb ved udtræk, respekter dette
			      		tinydb[q][2]+=y;
			      		tinydb[q][3]++;
			      	}
      			}*/

            var done = Date.now();
            offset = done-msg.timestamp;
            /*
            console.log("done: "+ done);
            console.log("ts: "+msg.timestamp);
            console.log("Delay: "+(done-msg.timestamp)+" ms")*/
                    }
                 });

                 $.each(tinydb, function(i,td){
                  //console.log("Looking into tinydb no. "+i);
                  if (td != undefined) {
                    //console.log("adding mes to tinydb "+i);
                    $.each(td,function(q,tdb){

                 	if(tdb[0] == msg.id) {
                 		$.each(msg.measurements, function (j, mes) {
                 			if (tdb[1] == mes.id) {
                 				while(tinydblock) {}//Lås tinydb ved udtræk, respekter dette
                        if (tdb[1].toString() == "active_power_a" || tdb[1].toString() == "active_power_b" || tdb[1].toString() == "active_power_c" || tdb[1].toString() == "active_power_sum") mes.value = Math.abs(mes.value);
                 				tdb[2]+=mes.value;
                 				tdb[3]++;
                 			}
                 		});
                 	}

                  });

                    }
                 });

                 if (Date.now()-lastredraw > 1000) {
    lastredraw = Date.now();
    tinydblock = true;// console.log("Locking...");
var toremove = [];
                 	$.each(names, function(i, name){
                 		if ((name[0] == 2 || name[0] == 3) &&  name[2].indexOf(0) == -1){
                 			var val = 0;
                 			var n = 0;
                 			$.each(name[1], function(j,nam){
                 				for (var q = 0;q<tinydb[i].length;q++) {
                 					if (tinydb[i][q][0]==nam && tinydb[i][q][1] == name[2][j]) {
                 						//if (name[0] == 3) console.log(nam+" vals: "+tinydb[i][q][2]/tinydb[i][q][3]);
                 						if (tinydb[i][q][3] != 0) {
                 							val+=tinydb[i][q][2]/tinydb[i][q][3];
                          toremove.push(q);
                 						//	tinydb[i][q][2]=0;
                 				//			tinydb[i][q][3]=0;
                 							n++;
                 						}
                 						
                 					}
                 					//console.log("val: "+val+" name[1]: "+name[1]);
                 				}
                 			});
                 			//console.log("HEY");
                      //console.log("val: "+val+" len: "+name[1].length+" n: "+n+" name[0]: "+name[0]);
                      //console.log(name);
if (n == name[1].length){
$.each(toremove,function(j,rem){
tinydb[i][j][2]=0;
tinydb[i][j][3]=0;
});
                 			if (name[0] == 2 && n != 0) series[i].addPoint([lastredraw-offset,val/n], false, false);
                 			else if (name[0] == 3 && n != 0) series[i].addPoint([lastredraw-offset,val], false, false);
                 		}}
                 	});
                 	/*
                 	$.each(tinydb, function(i,tdb){
                 		tdb[2]=0;
                 		tdb[3]=0;
                 	});*/
                 	tinydblock = false; //console.log("Unlocking...");
    chart.redraw();
                 }
    }

    sock.onclose = function() {
     if (reconnectalert) {
     // if (confirm('Live-update connection lost, reconnect?')) {
    startLiveUpdate(series);
    //} else {
      //$('#stoplive').hide();
        //    $('#info').html('Live data stopped. Press Create plot in order to restart it.');
    // Do nothing!
      //}
 }
    }
}

  </script>



</head>

<body>
<div id=container></div>

<button id=reset>Reset</button> <button id=stoplive style="display:none">Stop liveupdate</button> <div id=info style=display:inline></div>

<div style="margin:0;width=100%">
<div style="float:left;width:50%">

<p><b>Add dataseries from group</b></p>
<form>
<select id=selectgroup name=selectgroup multiple size=10>
</select>
<select id=selectdevices name="selectdevices[]" multiple size=10>
</select>
<input type=submit value="Add chosen">

</form>
</div>

<div style="float:right;width:50%">
<p><b>Add dataseries from kind</b></p>
<form>
<select id=selecttype name=selecttype multiple size=10>
</select>
<select id=selectmes name="selectmes[]" multiple size=10>
</select>
<input type=submit value="Add chosen">

</form>
</div></div>

<form>
<select id=addmes name=addmes>
<option value=2>Average of AVG varlues for each series, minute average</option>
<option value=3 selected>Sum of AVG values for each series, minute average</option>
</select>
Name on dataseries: <input type=text name=name>
<input type=submit value="Add">
</form>

<p><b>Chosen dataseries</b></p>
<div id=valgte></div>

<p><b>Matlab download (for shown data)</b></p>
<div id=matlab></div>

</body>
</html>
