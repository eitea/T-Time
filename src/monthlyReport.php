<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" type="text/css" href="../css/table.css">
  <link rel="stylesheet" type="text/css" href="../css/submitButt.css">
  <link rel="stylesheet" type="text/css" href="../css/inputTypeText.css">
  <link rel="stylesheet" href="../css/homeMenu.css">
</head>

<body>
  <form method="post">
  <?php
  session_start();
  if (!isset($_SESSION['userid'])) {
    die('Please <a href="login.php">login</a> first.');
  }
  if ($_SESSION['userid'] != 1) {
    die('Access denied. <a href="logout.php">return</a>');
  }

  require "connection.php";
  require "createTimestamps.php";
  require "language.php";
  ?>
<h1><?php echo $lang['MONTHLY_REPORT']; ?></h1>
<br>

<?php
$filterMonth = substr(getCurrentTimestamp(),0,7);
$filterUserID = "0";

if(isset($_POST['filterMonth'])){
  $filterMonth = $_POST['filterMonth'];
}

if(isset($_POST['filterUserID']) && $_POST['filterUserID'] != 0){
  $filterUserID = $_POST['filterUserID'];
}
?>

<input type="month" name="filterMonth" value=<?php echo $filterMonth; ?> >

<select name='filterUserID'>
<?php
  $sql = "SELECT * FROM $userTable";
  $result = $conn->query($sql);
  if($result && $result->num_rows > 0){
    $result->fetch_assoc(); //admin
    echo "<option value=0>Select...</option>";
    while($row = $result->fetch_assoc()){
      $selected = "";
      if($filterUserID == $row['id']){
        $selected = "selected";
      }
      echo "<option $selected value=".$row['id'].">";
      echo $row['firstname'] . " " . $row['lastname'];
      echo "</option>";
    }
  }
?>
</select>

<input type="submit" class="button" name="filter" value="Display">
<br><br>
<table>
  <tr>
    <th><?php echo $lang['WEEKLY_DAY']; ?></th>
    <th><?php echo $lang['DATE']; ?></th>
    <th>Beginn</th>
    <th>Pause (h)</th>
    <th>Ende</th>
    <th></th>
    <th><?php echo $lang['ACTIVITY']; ?></th>
    <th>Soll (h)</th>
    <th>Ist (h)</th>
    <th>Differenz (h)</th>
    <th>Saldo</th>
  </tr>

<?php
if(isset($_POST['filterUserID']) && $_POST['filterUserID'] != 0){
  require 'Calculators/MonthlyCalculator.php';
  $filterMonth .= '-01 00:00:00';

  $calculator = new Monthly_Calculator($filterMonth, $filterUserID);
  $calculator->calculateValues();

  for($i = 0; $i < $calculator->days; $i++){
    if($calculator->end[$i] == '0000-00-00 00:00:00'){
      $endTime = getCurrentTimestamp();
    } else {
      $endTime = $calculator->end[$i];
    }
    $difference = timeDiff_Hours($calculator->start[$i], $endTime );


    /*
    -1 .... absent (should not occur!)
    0 ..... arrival
    1 ..... vacation
    2 ..... special leave
    3 .... sickness
    4 ..... time balancing
    */
    $style = "";
    $tinyEndTime = '-';
    if($calculator->end[$i] != '-' && $calculator->end[$i] != '0000-00-00 00:00:00' && $calculator->activity[$i] == 0 && $calculator->canBook == 'TRUE'){
      $sql = "SELECT bookingTimeBuffer FROM $configTable";
      $result = $conn->query($sql);
      $config = $result->fetch_assoc();

      $sql = "SELECT end FROM $projectBookingTable WHERE timestampID = " . $calculator->indecesIM[$i] ." ORDER BY end DESC";
      $result = $conn->query($sql);
      if($result && $result->num_rows > 0) {
        $config2 = $result->fetch_assoc();

        $bookingTimeDifference = timeDiff_Hours($config2['end'], $calculator->end[$i]) * 60;

        if($bookingTimeDifference <= $config['bookingTimeBuffer']){
          $style = "style=background-color:#abff99";
        }
        if($bookingTimeDifference > $config['bookingTimeBuffer']){
          $style = "style=background-color:#ffdf5b";
        }
        if($bookingTimeDifference > $config['bookingTimeBuffer'] * 2){
          $style = "style=background-color:#fc8542";
        }
        if($calculator->end[$i] != '-'){
          $tinyEndTime = substr(carryOverAdder_Hours($config2['end'], $calculator->timeToUTC[$i]),11,5);
        }
      }
    }

    if($calculator->start[$i] != '-'){
      $A = carryOverAdder_Hours($calculator->start[$i], $calculator->timeToUTC[$i]);
    } else {
      $A = $calculator->start[$i];
    }

    if($calculator->end[$i] != '-'){
      $B = carryOverAdder_Hours($calculator->end[$i], $calculator->timeToUTC[$i]);
    } else {
      $B = $calculator->end[$i];
    }

    echo "<tr $style>";
    echo "<td>" . $lang_weeklyDayToString[$calculator->dayOfWeek[$i]] . "</td>";
    echo "<td>" . $calculator->date[$i] . "</td>";
    echo "<td>" . substr($A,11,8) . "</td>";
    echo "<td>" . sprintf('%.2f', $calculator->lunchTime[$i]) . "</td>";
    echo "<td>" . substr($B,11,8)  . "</td>";
    echo "<td style='font-size:small; text-align:left;'>" . $tinyEndTime . "</td>";
    echo "<td>" . $lang_activityToString[$calculator->activity[$i]]. "</td>";
    echo "<td>" . $calculator->shouldTime[$i] . "</td>";
    echo "<td>" . sprintf('%.2f', $difference) . "</td>";
    echo "<td>" . sprintf('%+.2f', $difference - $calculator->shouldTime[$i]) . "</td>";
    echo "<td>" . sprintf('%+.2f', $difference - $calculator->shouldTime[$i] - $calculator->lunchTime[$i]) . "</td>";
    echo "</tr>";

/* TODO: continue here
    echo "<tr>";
    echo "<td>Summe: </td>";

    echo "</tr>"
*/
  }
}
?>

</table>
</form>
</body>
<br>

</body>
<br>
