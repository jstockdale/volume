<?php
// volume.php by jstockdale
//
// A simple script to control the volume of a mac from the web
//
// Copyright 2011 RootMusic, Inc.
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

$VOLUME_CMD_GET = "osascript ".dirname($_SERVER['DOCUMENT_ROOT'])."/applescript/get.scpt";
$VOLUME_CMD_SET = "osascript ".dirname($_SERVER['DOCUMENT_ROOT'])."/applescript/set.scpt %f";

$VOLUME_PARAM = "volume";
$VOLUME_PARAM_REVERT = "volume_revert";
$VOLUME_MIN = 0;
$VOLUME_MAX = 1;
$VOLUME_STEP = .01;
$VOLUME_FUDGE = .02;

$VOLUME_OLD_TXT = "Old volume: %.2f\n";
$VOLUME_CURR_TXT = "Current volume: %.2f\n";
$VOLUME_NEW_TXT =
  "  <label for='volume'>New volume:</label>\n".
  "  <input type='text' id=$VOLUME_PARAM name=$VOLUME_PARAM size='1' style='border:0; font-size:16px; font-family:serif;'/>";

$volume_old = NULL;
$volume_curr = NULL;
$volume_new = NULL;

// TODO: (jstockdale)
// Move this to a library
// Function to get system volume via exec.
function get_system_volume($get_cmd, $vol_min, $vol_max) {
  // Get the current volume of the system
  $output_array = array();
  $return_value = NULL;
  exec($get_cmd, $output_array, $return_value);
  
  // If the get command returned an error don't use it.
  if ($return_value != 0 || !is_numeric($output_array[0])) {
    throw new Exception("Cannot get current system volume!");
  } else {
    $volume_curr = floatval($output_array[0]);
    if ( $volume_curr > $vol_max || $volume_curr < $vol_min ) {
      throw new Exception("Current system volume value is invalid!");
    }
  }
  return $volume_curr;
}

// TODO: (jstockdale)
// Move this to a library
// Function to set system volume via exec.
function set_system_volume(&$volume_new, $set_cmd, $get_cmd, $vol_min, $vol_max, $vol_fudge) {
  // Set the new volume of the system
  $output_array = array();
  $return_value = NULL;
  exec(sprintf($set_cmd, $volume_new), $output_array, $return_value);
  if ($return_value != 0) {
    throw new Exception("Could not set new system volume!");
  }
  $volume_result = get_system_volume($get_cmd, $vol_min, $vol_max);
  if ($volume_result != $volume_new) {
    if (abs($volume_result - $volume_new) <= $vol_fudge) {
      $volume_new = $volume_result;
    } else {
      throw new Exception("Set new volume but system volume did not change!");
    }
  }
}

$volume_curr = get_system_volume($VOLUME_CMD_GET, $VOLUME_MIN, $VOLUME_MAX);

// If we have a volume request (post) go ahead and set volume_new
if (isset($_POST[$VOLUME_PARAM]) && is_numeric($_POST[$VOLUME_PARAM])) {
  $volume_request = $_POST[$VOLUME_PARAM];
} else if (isset($_POST[$VOLUME_PARAM_REVERT]) && is_numeric($_POST[$VOLUME_PARAM_REVERT])) {
  $volume_request = $_POST[$VOLUME_PARAM_REVERT];
}
if (isset($volume_request)) {
  $volume_new = floatval($volume_request);
  // Fixup bad volume values
  if ( $volume_new > $VOLUME_MAX ) {
    $volume_new = $VOLUME_MAX;
  } elseif ( $volume_new < $VOLUME_MIN ) {
    $volume_new = $VOLUME_MIN;
  }
  $volume_old = $volume_curr;
  set_system_volume($volume_new, $VOLUME_CMD_SET, $VOLUME_CMD_GET, $VOLUME_MIN, $VOLUME_MAX, $VOLUME_FUDGE);
  // TODO: (jstockdale)
  // Refactor to ajax submit and remove this!
  header("location: volume.php");
}

// TODO: (jstockdale)
// Put this into a template file

// Deal with tags and header
printf("<!DOCTYPE html>\n<html>\n");

printf("<head>\n");
$header =
  "  <title>Volume Control</title>\n".
  "  <link href='http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css' rel='stylesheet' type='text/css'/>\n".
  "  <script src='http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js'></script>\n".
  "  <script src='http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js'></script>\n".
  "    <style type='text/css'>\n".
  "    #slider {\n".
  "      margin: 10px;\n".
  "      height: 100px;\n".
  "      float: left;\n".
  "    }\n".
  "    .container {\n".
  "      border-style: solid;\n".
  "      border-width: 1px;\n".
  "      border-color: grey;\n".
  "      background-color: #DDDDDD;\n".
  "      padding: 10px;\n".
  "      height: 120px;\n".
  "      width: 320px;\n".
  "    }\n".
  "    .volume_div {\n".
  "      border-style: solid;\n".
  "      border-width: 1px;\n".
  "      border-color: grey;\n".
  "      background-color: #EEEEEE;\n".
  "      padding: 14px;\n".
  "      padding-right: 20px;\n".
  "      margin-top: 19px;\n".
  "      margin-right: 14px;\n".
  "      height: 56px;\n".
  "      float: right;\n".
  "    }\n".
  "    .submit_button {\n".
  "      float: right;\n".
  "      margin-left: 20px;\n".
  "    }\n".
  "     </style>\n";
printf($header);

$volume_slider = isset($volume_new) ? $volume_new : $volume_curr;
$slider_args =
  "min: ".$VOLUME_MIN.",\n".
  "max: ".$VOLUME_MAX.",\n".
  "step: ".$VOLUME_STEP.",\n".
  "value: ".$volume_slider.",\n".
  "orientation: 'vertical',\n".
  "slide: function( event, ui ) {\n".
  "  $('#".$VOLUME_PARAM."').attr( 'value', ui.value );\n".
  "}";
$slider_js =
  "<script>\n".
  "  $(document).ready(function() {\n".
  "  $('#slider').slider({".$slider_args."});\n".
  "  $('#".$VOLUME_PARAM."').attr( 'value', $('#slider').slider('value'));".
  "  });\n".
  "</script>\n";
printf($slider_js);

printf("</head>\n");

printf("<body>\n");

// Container div
printf("<div class='container'>");

// Slider div (for jQuery)
printf("<div id='slider'></div>");

printf("<div class='volume_div'>");

printf($VOLUME_CURR_TXT, get_system_volume($VOLUME_CMD_GET, $VOLUME_MIN, $VOLUME_MAX));
printf("<br />\n");
printf("<form method='POST'>\n");
printf($VOLUME_NEW_TXT);
printf("<div class='submit_button'><input type='submit' name='submit' value='Make it so'/></div>\n");
printf("</form>\n");

if (isset($volume_new)) {
  if ($volume_new != $volume_old) {
    printf("<form method='POST'>\n");
    printf($VOLUME_OLD_TXT, $volume_old);
    printf("<input type='hidden' id='".$VOLUME_PARAM_REVERT."' name='".$VOLUME_PARAM_REVERT."' value='".$volume_old."' />\n");
    printf("<div class='submit_button'><input type='submit' name='submit' value='Revert'/></div>\n");
    printf("</form>\n");
  }
}
// Close volume div
printf("</div>");

// Close container
printf("</div>");

// Close body and html tags
printf("</body>\n");
printf("</html>\n");

?>
