<?php
/**
 * A basic interface to display 1 or more MJPEG streams and basic keyboard
 * teleoperation control.
 *
 * @author     Russell Toris <rctoris@wpi.edu>
 * @copyright  2013 Russell Toris, Worcester Polytechnic Institute
 * @license    BSD -- see LICENSE file
 * @version    April, 15 2013
 * @package    api.robot_environments.interfaces.basic
 * @link       http://ros.org/wiki/rms
 */

/**
 * A static class to contain the interface generate function.
 *
 * @author     Russell Toris <rctoris@wpi.edu>
 * @copyright  2013 Russell Toris, Worcester Polytechnic Institute
 * @license    BSD -- see LICENSE file
 * @version    April, 15 2013
 * @package    api.robot_environments.interfaces.basic
 */
#class basic
class fall_risk
{
    /**
     * Generate the HTML for the interface. All HTML is echoed.
     * @param robot_environment $re The associated robot_environment object for
     *     this interface
     */
    static function generate($re)
    {
        // lets begin by checking if we have an MJPEG keyboard at the very least
        if (!$streams = $re->get_widgets_by_name('MJPEG Stream')) {
            robot_environments::create_error_page(
                'No MJPEG streams found.', 
                $re->get_user_account()
            );
        } else if (!$teleop = $re->get_widgets_by_name('Keyboard Teleop')) {
            robot_environments::create_error_page(
                'No Keyboard Teloperation settings found.', 
                $re->get_user_account()
            );
        } else if (!$nav = $re->get_widgets_by_name('2D Navigation')) {
            robot_environments::create_error_page(
                'No 2D Navaigation settings found.',
                $re->get_user_account()
            );
        } else if (!$sensorService = $re->get_widgets_by_name('Sensor Service')) {
            robot_environments::create_error_page(
                'Could not find definition for Sensor service.',
                $re->get_user_account()
            );
        } else if (!$re->authorized()) {
            robot_environments::create_error_page(
                'Invalid experiment for the current user.', 
                $re->get_user_account()
            );
        } else { 
            // lets create a string array of MJPEG streams
            $topics = '[';
            $labels = '[';
            foreach ($streams as $s) {
                $topics .= "'".$s['topic']."', ";
                $labels .= "'".$s['label']."', ";
            }
            $topics = substr($topics, 0, strlen($topics) - 2).']';
            $labels = substr($labels, 0, strlen($topics) - 2).']';

            // we will also need the map
            $widget = widgets::get_widget_by_table('maps');
            $map = widgets::get_widget_instance_by_widgetid_and_id(
                $widget['widgetid'], $nav[0]['mapid']);

            // here we can spit out the HTML for our interface ?>
<!doctype html>
<!--[if lt IE 7 ]><html class="ie ie6" lang="en"> <![endif]-->
<!--[if IE 7 ]><html class="ie ie7" lang="en"> <![endif]-->
<!--[if IE 8 ]><html class="ie ie8" lang="en"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!-->
<html lang="en">
<!--<![endif]-->
<head>
<meta charset="utf-8">
<link href="../api/robot_environments/interfaces/fall_risk/style.css" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="../css/jquery-ui-1.8.22.custom.css" />
<script type="text/javascript" src="../js/rms/common.js"></script>
<script type="text/javascript" src="http://code.jquery.com/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="http://code.jquery.com/ui/1.8.22/jquery-ui.min.js"></script>
<script type="text/javascript" src="../js/rms/study.js"></script> 
<!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->
<!--<?php $re->create_head() // grab the header information ?> -->
<script type="text/javascript"
    src="http://cdn.robotwebtools.org/EventEmitter2/0.4.11/eventemitter2.js">
</script>
<script type="text/javascript"
    src="http://cdn.robotwebtools.org/roslibjs/r5/roslib.min.js"></script>
<script type="text/javascript"  src="http://cdn.robotwebtools.org/mjpegcanvasjs/r1/mjpegcanvas.min.js">
</script>
<script type="text/javascript"
  src="../js/rms/keyboardteleop.min.js">
</script>
<script type="text/javascript" src="http://cdn.robotwebtools.org/EaselJS/current/easeljs.min.js"></script>
<script type="text/javascript" src="http://cdn.robotwebtools.org/ros2djs/current/ros2d.min.js"></script>
<script type="text/javascript" src="http://cdn.robotwebtools.org/nav2djs/current/nav2d.min.js"></script>


<title>Inhome environment screening for fallrisk</title>
<script type="text/javascript">
  //connect to ROS
  var ros = new ROSLIB.Ros({
      url : '<?php echo $re->rosbridge_url()?>'
  });
  
  ros.on('error', function() {
        alert('Lost communication with ROS.');
    });

  /**
   * Load everything on start.
   */
  
function start() {

   // create MJPEG streams
    var main = new MJPEGCANVAS.Viewer({
      divID : 'mjpeg_canvas',
      host : '<?php echo $re->get_mjpeg()?>',
      port : '<?php echo $re->get_mjpegport()?>',
      width : 400,
      height : 300,
      quality : 35,
      topic : <?php echo $topics?>
    });

 // initialize the teleop
    var teleop = new KEYBOARDTELEOP.Teleop({
      ros : ros,
      topic : '<?php echo $teleop[0]['twist']?>',
      throttle : '<?php echo $teleop[0]['throttle']?>'
    });


 
//Initializing viewer for 2D map 
var mapViewer = new ROS2D.Viewer({
      divID : 'nav2d',
      width : 650,
      height : 475
    });

    // Setup the nav client.
     var nav = NAV2D.OccupancyGridClientNav({
      ros : ros,
      rootObject : mapViewer.scene,
      viewer : mapViewer,
      serverName : '<?php echo $nav[0]['actionserver']?>',
      actionName : '<?php echo $nav[0]['action']?>',
      topic : '<?php echo $map['topic']?>',
      continuous : '<?php echo ($map['continuous'] === 0) ? 'true' : 'false'?>'
  });

	//Shift the canvas to display entire map
//	mapViewer.shift(13.8,21.7); //ECE Lounge Map
//	mapViewer.shift(0,18.6); //RIVeR_Lab Map
//	mapViewer.shift(17,11.8); //rdu apartment
        mapViewer.shift(17,3.55); //rdu apartment cropped

 // create a UI slider using JQuery UI
    $('#quality-slider').slider({
      range : 'min',
      min : 0,
      max : 100,
      value : 35,
      slide : function(event, ui) {
        // Change the quality label.
        $('#quality-label').html('Video Quality: ' + ui.value + '%');
        // Scale the quality.
    //    reloadVideo(ui.value);
	main.quality = ui.value;
	main.changeStream(<?php echo $topics?>);
      }
    });
    // set the initial quality
    $('#quality-label').html('Video Quality: '+($('#quality-slider').slider('value'))+'%');


    // create a UI slider using JQuery UI
    $('#speed-slider').slider({
      range : 'min',
      min : 0,
      max : 100,
      value : 50,
      slide : function(event, ui) {
        // Change the speed label.
        $('#speed-label').html('Robot Speed: ' + ui.value + '%');
        // Scale the speed.
        teleop.scale = (ui.value / 100.0);
      }
    });

    // set the initial speed
    $('#speed-label').html('Robot Speed: '+($('#speed-slider').slider('value'))+'%');
    teleop.scale = ($('#speed-slider').slider('value') / 100.0);  

  }

function getChecklistStatus(){ 
	setInterval(function getLuminosity(){
	  var checklistStatus = new ROSLIB.Service({
	    ros : ros,
	    name : '<?php echo $sensorService[0]['topic']?>',
	    serviceType : '<?php echo $sensorService[0]['servicetype']?>'
	  });

	  var request = new ROSLIB.ServiceRequest({});

	  checklistStatus.callService(request, function(result) {
	   var imageSource = result.luminosity >= 200.00?'../img/green.jpg':(result.luminosity >= 100.00?'../img/yellow.jpg':'../img/red.jpg');  
  	   document.getElementById('luminositySensorInfo').src=imageSource;
// document.getElementById('sensorData').innerHTML = result.luminosity;
 	 });

	},5000); 
}
</script>
</head>

<body onload="start(); getChecklistStatus();">
<div class="container">
  <header class="sixteen columns">
   <table style="width:1050px;"><tr> <td style="vertical-align:middle;"><div id="logo" >
      <h1>In-home environment screening for Fall Risk</h1>
      <h2>using turtlebot</h2>
    </div></td><td style="vertical-align:middle;width:150px">
    <img src="../img/logoModified.png" width="190" height="73" alt=""/>
</td></tr></table>
    <hr/>
  </header>

 <div id="overview" class="sixteen columns">
<table><tr>
<td nowrap style="width:30%"><ul>
<li><strong>Common</strong></li>
<li><img src= "../img/green.jpg" id="luminositySensorInfo" width=15 heigth=15> &nbsp&nbsp&nbspLighting condition <div id = 'sensorData' /> </li>
<li><img src= "../img/yellow.jpg" id="wiresInfo" width=15 heigth=15> &nbsp&nbsp&nbspWires/cords close to walls</li>
<li><img src= "../img/red.jpg" id="freeWalkwaysInfo" width=15 heigth=15> &nbsp&nbsp&nbspCommon walkways devoid of furniture</li>
</ul></td>
<td nowrap style="width:25%"><ul>
<li><strong>Stairs</strong></li>
<li><img src= "../img/yellow.jpg" id="bottomStepInfo" width=15 heigth=15> &nbsp&nbsp&nbspBottom step clearly marked</li>
<li><img src= "../img/yellow.jpg" id="stepRisersInfo" width=15 heigth=15> &nbsp&nbsp&nbspSteps have risers</li>
<li><img src= "../img/red.jpg" id="freeWalkwaysInfo" width=15 heigth=15> &nbsp&nbsp&nbspSecure and visible railing</li>
</ul></td>
<td nowrap style="width:20%"><ul>
<li><strong>Bathroom</strong></li>
<li><img src= "../img/yellow.jpg" id="grabBarsInfo" width=15 heigth=15> &nbsp&nbsp&nbspGrab bars in bath/toilet</li>
<li><img src= "../img/yellow.jpg" id="showerChairInfo" width=15 heigth=15> &nbsp&nbsp&nbspShower chair</li>
<li><img src= "../img/red.jpg" id="toiletSeatInfo" width=15 heigth=15> &nbsp&nbsp&nbspRaised toilet seat</li>
</ul></td>
<td nowrap style="width:25%"><ul>
<li><strong>Bedroom</strong></li>
<li><img src= "../img/yellow.jpg" id="bedSpaceInfo" width=15 heigth=15> &nbsp&nbsp&nbspEnough room between side of bed and wall</li>
<li><img src= "../img/yellow.jpg" id="bedBathroomPathInfo" width=15 heigth=15> &nbsp&nbsp&nbspClear path from bed to bathroom</li>
<li><img src= "../img/red.jpg" id="bedHeightInfo" width=15 heigth=15> &nbsp&nbsp&nbspReasonable bed height</li>
</ul></td>
</tr></table>
<hr />
    <table>
      <tr><td><h4 align="center">2D Navigation Map</h4>
<div id="nav2d" align=center> </div>       
</td>
    <td style="padding-left:3px"><h4 align="center">Live Video</h4>
<!-- <div style="float:left;padding-left:5px"> 
Create this using JQuery, javascript wont find the required object :( 
 <form> 

<input type="radio" name="videotype" value="monochrome" onchange="main.changeStream('/camera/rgb/image_mono')">Monochrome</input><br>
<input type="radio" name="videotype" value="color" checked onchange="main.changeStream('/camera/rgb/image_raw_ref_line')">Color</input>
</form> </div> -->

                                <div id="quality-label" style="padding-left:10px"></div>
                                <div id="quality-slider" ></div>
          <div class="webtoolswidget" id="mjpeg_canvas">  </div>
<div id="speed-label" style="padding-left:10px"></div>
<div id="speed-slider" ></div>
        <div style="float:middle;padding-left:26%">
         <img src="../img/keyboardteleopjs-keys.jpg" width="192" height="90" alt=""/></div><br>


</td>
      </tr>
    </table>
  </div>

  <!-- Footer begins ========================================================================== -->
  <footer class="sixteen columns">
    <hr />
    <ul id="footerLinks">
      <li>&copy; 2014 <a href="http://robot.wpi.edu/">RIVeR Lab</a>, WPI</li>
      <li>Powered by <a href="http://www.ros.org/wiki/rms/">Robot Management System</a></li>
    </ul>
  </footer>
</div>
</body>
</html>
<?php
        }
    }
}
