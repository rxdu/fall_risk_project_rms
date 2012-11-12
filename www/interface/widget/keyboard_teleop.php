<?php
/*********************************************************************
 *
* Software License Agreement (BSD License)
*
*  Copyright (c) 2012, Worcester Polytechnic Institute
*  All rights reserved.
*
*  Redistribution and use in source and binary forms, with or without
*  modification, are permitted provided that the following conditions
*  are met:
*
*   * Redistributions of source code must retain the above copyright
*     notice, this list of conditions and the following disclaimer.
*   * Redistributions in binary form must reproduce the above
*     copyright notice, this list of conditions and the following
*     disclaimer in the documentation and/or other materials provided
*     with the distribution.
*   * Neither the name of the Worcester Polytechnic Institute nor the
*     names of its contributors may be used to endorse or promote
*     products derived from this software without specific prior
*     written permission.
*
*  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
*  "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
*  LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
*  FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
*  COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
*  INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
        *  BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
        *  LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
*  CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
*  LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
*  ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
*  POSSIBILITY OF SUCH DAMAGE.
*
*   Author: Russell Toris
*  Version: September 27, 2012
*
*********************************************************************/
?>

<?php
// include path relative to home directory
include_once('../../inc/config.inc.php');

session_start();
// check if there is a user logged in
if (!isset($_SESSION['userid'])) {
  echo "INVALID USER SESSION";
} else if(isset($_GET['envid']) && isset($_GET['id'])) {
  // grab the environment and widget
  $sql = sprintf("SELECT * FROM environments WHERE envid = %d", $db->real_escape_string($_GET['envid']));
  $query = mysqli_query($db, $sql);
  $environment = mysqli_fetch_array($query);
  $sql = sprintf("SELECT * FROM keyboard_teleop WHERE id = %d", $db->real_escape_string($_GET['id']));
  $query = mysqli_query($db, $sql);
  $teleop = mysqli_fetch_array($query);

  if(!$environment) {
    echo "INVALID ENVIRONMENT";
  } else if(!$teleop) {
    echo "INVALID KEYBOARD TELEOP ID";
	} else {?>

<script type="text/javascript">
	// create the teleop 
	var teleop = new KeyboardTeleop({
		ros : ros, 
		topic : '<?php echo $teleop['twist']?>', 
		throttle: <?php echo $teleop['throttle']?>
	});

		<?php
		// check for a callback
		if(isset($_GET['callback'])) {
			echo $_GET['callback']."(teleop);";
		}?>
</script>
<?php
	}
} else {
  echo "INVALID KEYBOARD TELEOP PARAMETERS";
}
?>
