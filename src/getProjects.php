<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../css/homeMenu.css">
  <link rel="stylesheet" type="text/css" href="../css/table.css">
  <link rel="stylesheet" type="text/css" href="../css/submitButt.css">
  <link rel="stylesheet" type="text/css" href="../css/inputTypeText.css">
  <link rel="stylesheet" type="text/css" href="../css/textArea.css">
  <link rel="stylesheet" type="text/css" href="../bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" type="text/css" href="../plugins/datatables/css/dataTables.bootstrap.min.css">

  <script src="../plugins/jQuery/jquery-3.1.0.min.js"></script>

  <link rel="stylesheet" type="text/css" href="../plugins/select2/css/select2.min.css">

  <script src='../plugins/select2/js/select2.js'></script>
  <script type="text/css" src='../js/autoScale.js'></script>

<style>
  textarea {
    border-style: hidden;
    border-radius:5px;
    width:200px;
  }

  input[type="number"],input[type="time"]{
    color:darkblue;
    font-family: monospace;
    border-style: hidden;
    width:55px;
    padding:2px;
    border-radius:5px;
    min-width:90px;
  }
  div{
    float:left;
    margin:5px;
    display:block;
  }
</style>
</head>
<body>


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

<?php
$filterDate = substr(getCurrentTimestamp(),0,7); //granularity: default is year and month
$booked = '0';
$filterCompany = 0;
$filterClient = 0;
$filterProject = 0;
$filterUserID = 0;

//careful stairs
  if(!empty($_POST['filterYear'])){
    $filterDate = $_POST['filterYear'];
    if(!empty($_POST['filterMonth'])){
      $filterDate .= '-' . $_POST['filterMonth'];
      if(!empty($_POST['filterDay'])){
        $filterDate .= '-' . $_POST['filterDay'];
      }
    }
  }

  if(isset($_POST['filterCompany'])){
    $filterCompany = $_POST['filterCompany'];
  }

  if(isset($_POST['filterBooked'])){
    $booked = $_POST['filterBooked'];
  }

  if(isset($_POST['filterClient'])){
    $filterClient = $_POST['filterClient'];
  }

  if(isset($_POST['filterProject'])){
    $filterProject = $_POST['filterProject'];
  }

  if(isset($_POST['filterUserID'])){
    $filterUserID = $_POST['filterUserID'];
  }
?>


<form method='post' action='dailyReport.php'>
  <?php if($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
<h1><button type=submit style=background:none;border:none;><img src='../images/return.png' alt='return' style='width:35px;height:35px;border:0;margin-bottom:5px'></button><?php echo $lang['VIEW_PROJECTS']?></h1>

<input type=text name=filterDay style=display:none; value="<?php echo $filterDate .'-01-01' ?>" >
<input type=text name=booked style=display:none; value="<?php echo $booked; ?>" >
<input type=text name=filterUserID style=display:none; value="<?php echo $filterUserID; ?>" >
<?php else : ?>
  <h1><?php echo $lang['VIEW_PROJECTS']?></h1>
<?php endif; ?>

<br><br>
</form>

<form method='post'>
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if (isset($_POST['saveChanges']) && isset($_POST['editingIndeces'])) {
    for ($i = 0; $i < count($_POST['editingIndeces']); $i++) {
      $imm = $_POST['editingIndeces'][$i];
      $query = "SELECT $logTable.timeToUTC
      FROM $logTable, $projectBookingTable
      WHERE $projectBookingTable.id = $imm
      AND $projectBookingTable.timestampID = $logTable.indexIM";

      $result = mysqli_query($conn, $query);
      if($result && $result->num_rows>0){
        $row = $result->fetch_assoc();
        $toUtc = $row['timeToUTC'] * -1;

        $timeStart = carryOverAdder_Hours($_POST['dateFrom'][$i]." ".$_POST['timesFrom'][$i], $toUtc);
        $timeFin = carryOverAdder_Hours($_POST['dateTo'][$i]." ".$_POST['timesTo'][$i], $toUtc);

        $infoText = test_input($_POST['infoTextArea'][$i]);
        $sql = "UPDATE $projectBookingTable SET start='$timeStart', end='$timeFin', infoText='$infoText' WHERE id = $imm";
        $conn->query($sql);
        echo mysqli_error($conn);
      }
      echo mysqli_error($conn);
    }
  }
  if(isset($_POST['saveChanges']) && isset($_POST['noCheckCheckingIndeces']) && $_POST['filterBooked'] == 1){
    foreach ($_POST["noCheckCheckingIndeces"] as $e) {
      $sql = "UPDATE $projectBookingTable SET booked = 'TRUE'  WHERE id = $e;";
      $conn->query($sql);
    }
  }
  if(isset($_POST['saveChanges']) && isset($_POST['checkingIndeces']) && $_POST['filterBooked'] == 1){
    foreach ($_POST["checkingIndeces"] as $e) {
      $sql = "UPDATE $projectBookingTable SET booked = 'TRUE'  WHERE id = $e;";
      $conn->query($sql);

      $sql = "SELECT start, end, projectID FROM $projectBookingTable WHERE id = $e";

      if($result = $conn->query($sql)){
        $row = $result->fetch_assoc();
        $hours = timeDiff_Hours($row['start'], $row['end']);

        $sql = "UPDATE $projectTable SET hours = hours - $hours WHERE id = ".$row['projectID'];
        $conn->query($sql);
        echo mysqli_error($conn);
      }
    }
  }
}
?>

<script>
function showClients(company, client){
    $.ajax({
        url:'ajaxQuery/AJAX_client.php',
        data:{companyID:company, clientID:client},
        type: 'post',
        success : function(resp){
            $("#filterClient").html(resp);
        },
        error : function(resp){}
    });

    showProjects(client, 0);
};

function showProjects(client, project){
    $.ajax({
        url:'ajaxQuery/AJAX_project.php',
        data:{clientID:client, projectID:project},
        type: 'post',
        success : function(resp){
            $("#filterProject").html(resp);
        },
        error : function(resp){}
    });
};

function showUsers(company, user){
    $.ajax({
        url:'ajaxQuery/AJAX_user.php',
        data:{companyID:company, userID:user},
        type: 'post',
        success : function(resp){
            $("#filterUserID").html(resp);
        },
        error : function(resp){}
    });
};


function textAreaAdjust(o) {
    o.style.height = "1px";
    o.style.height = (o.scrollHeight)+"px";
}

function showFilters(){
  $('#myFilters').fadeIn('slow');
}


$(document).ready(function() {
  $(".js-example-basic-single").select2();
});
</script>


<!----------------------------------------------------------------------------->

  <div name='mandatory' stlye=clear:both;>
    <select style='width:200px' class="js-example-basic-single" name="filterYear">
      <?php
      for($i = substr($filterDate,0,4)-5; $i < substr($filterDate,0,4)+5; $i++){
        $selected = ($i == substr($filterDate,0,4))?'selected':'';
        echo "<option $selected value=$i>$i</option>";
      }
       ?>
    </select>
      <br><br>
    <select style='width:200px' class="js-example-basic-single" name="filterMonth">
      <?php
      for($i = 1; $i < 13; $i++) {
        $selected= '';
        if ($i == substr($filterDate,5,2)) {
          $selected = 'selected';
        }
        $dateObj = DateTime::createFromFormat('!m', $i);
        $option = $dateObj->format('F');
        echo "<option $selected name=filterUserID value=".sprintf("%02d",$i).">$option</option>";
      }
       ?>
    </select>
      <br><br>
    <select style='width:200px' class="js-example-basic-single" name="filterDay">
      <?php
      for($i = 1; $i < 32; $i++){
        echo "<option value=".sprintf("%02d",$i).">$i</option>";
      }
       ?>
    </select>
      <br><br>
    <select style='width:200px' id="filterCompany" name="filterCompany" onchange='showClients(this.value, 0); showUsers(this.value, 0); showFilters()' class="js-example-basic-single">
      <option value="0">Select Company...</option>
      <?php
      $sql = "SELECT * FROM $companyTable";
      $result = mysqli_query($conn, $sql);
      if($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        do {
          $checked = '';
          if($filterCompany == $row['id']) {
            $checked = 'selected';
          }
          echo "<option $checked value='".$row['id']."' >".$row['name']."</option>";

        } while($row = $result->fetch_assoc());
      }
      ?>
    </select>

  </div>

<span id='myFilters' style='display:none;float:left'>

  <div name='neededToEdit'>
      <select name="filterBooked" style='width:200px' class="js-example-basic-single">
        <option value='0' <?php if($booked == 0){echo 'selected';}?> >---</option>
        <option value='1' <?php if($booked == '1'){echo 'selected';}?> ><?php echo $lang['NOT_CHARGED']; ?></option>
        <option value='2' <?php if($booked == '2'){echo 'selected';}?> ><?php echo $lang['CHARGED']; ?></option>
      </select>
  </div>


  <div name='clientAndProject'>
    <select id="filterClient" name="filterClient" class="js-example-basic-single" style='width:200px' onchange='showProjects(this.value, 0)' >
    </select>
      <br><br>
    <select id="filterProject" name="filterProject" class="js-example-basic-single" style='width:200px'>
    </select>

  </div>


  <div name='theUser'>
    <select id="filterUserID" name="filterUserID" class="js-example-basic-single" style='width:200px'>
    </select>
  </div>


  <div style=clear:both;>
    <br>
    <input type="submit" name="filter" value="Filter"><br><br>
  </div>

</span>
<!----------------------------------------------------------------------------->

<br><br><br><br>
<?php
if($filterCompany != 0):
?>

<script>
showClients(<?php echo $filterCompany; ?>, <?php echo $filterClient; ?>);
showProjects(<?php echo $filterClient; ?>, <?php echo $filterProject; ?>);
showUsers(<?php echo $filterCompany; ?>, <?php echo $filterUserID; ?>);
$('#myFilters').fadeIn('fast');
</script>

<?php endif; ?>

<br><br>

<script>
function toggle(source) {
  checkboxes = document.getElementsByName('checkingIndeces[]');
  for(var i = 0; i<checkboxes.length; i++) {
    checkboxes[i].checked = source.checked;
  }
}
function toggle2(source) {
  checkboxes = document.getElementsByName('noCheckCheckingIndeces[]');
  for(var i = 0; i<checkboxes.length; i++) {
    checkboxes[i].checked = source.checked;
  }
}
</script>

<div style=float:left;clear:both;margin-top:50px;>
<?php
if(!isset($_POST['filterBooked']) || $_POST['filterBooked'] != '1'): ?>
<fieldset disabled>
<?php endif; ?>

<table id='blank' class="table table-striped table-bordered" cellspacing="0" width="100%">
  <thead>
<tr>
<th><?php echo $lang['CLIENT']; ?></th>
<th><?php echo $lang['PROJECT']; ?></th>
<th>Info</th>
<th><?php echo $lang['DATE']; ?></th>
<th><?php echo $lang['SUM']; ?> (min)</th>
<th><?php echo $lang['SUM']; ?> (0.25h)</th>
<th><?php echo $lang['HOURS_CREDIT']; ?></th>
<th>Person</th>
<th><?php echo $lang['CHARGED']; ?> <input type="checkbox" onClick="toggle(this)" /> / <?php echo $lang['NOT_CHARGEABLE']; ?> <input type="checkbox" onClick="toggle2(this)" /></th>
<th><?php echo $lang['HOURLY_RATE'];?> (€)</th>
</tr>
</thead>

<?php
require "../vendor/deblan/Csv.php";
use Deblan\Csv\Csv;

$csv = new Csv();
$csv->setLegend(array('Kunde', 'Projekt', 'Info', 'Datum', 'Summe (min)', 'Summe (0.25h)', 'Stundenkonto', 'Person', 'Stundenrate'));
$csv->setEncoding("UTF-8");

$sum_min = $sum25 = 0;

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  $filterCompany = $_POST['filterCompany'];

  if($booked == '2'){
    $bookedQuery= "AND $projectBookingTable.booked = 'TRUE'";
  } elseif($booked == '1'){
    $bookedQuery= "AND $projectBookingTable.booked = 'FALSE'";
  } else {
    $bookedQuery = "";
  }

  if($filterClient == 0){
    $filterClientAdd = "";
  } else {
    $filterClientAdd = "AND $clientTable.id = $filterClient";
  }

  if($filterProject == 0){
    $filterProjectAdd = "";
  } else {
    $filterProjectAdd = "AND $projectTable.id = $filterProject";
  }

  if($filterUserID == 0){
    $filterUserIDAdd = '';
  } else {
    $filterUserIDAdd = "AND $userTable.id = $filterUserID";
  }

  $sql="SELECT DISTINCT $projectTable.id AS projectID,
              $clientTable.id AS clientID,
              $clientTable.name AS clientName,
              $projectTable.name AS projectName,
              $projectBookingTable.booked,
              $projectBookingTable.id AS projectBookingID,
              $logTable.timeToUTC,
              $projectBookingTable.infoText,
              $projectBookingTable.start,
              $projectBookingTable.end,
              $userTable.firstname, $userTable.lastname,
              $projectTable.hours,
              $projectTable.hourlyPrice
        FROM $projectBookingTable, $logTable, $userTable, $clientTable, $projectTable, $companyTable
        WHERE $companyTable.id = $filterCompany
        AND $projectTable.clientID = $clientTable.id
        AND $clientTable.companyID = $companyTable.id
        AND $projectBookingTable.projectID = $projectTable.id
        AND $projectBookingTable.timestampID = $logTable.indexIM
        AND $userTable.id = $logTable.userID
        AND $projectBookingTable.start LIKE '$filterDate%'
        $bookedQuery
        $filterClientAdd $filterProjectAdd $filterUserIDAdd
        AND $projectBookingTable.projectID IS NOT NULL
        ORDER BY $projectBookingTable.start DESC";

  $result = mysqli_query($conn, $sql);
  if($result && $result->num_rows >0) {
    while($row = $result->fetch_assoc()) {

      $timeDiff = timeDiff_Hours($row['start'], $row['end']);
      $t = ceil($timeDiff * 4) / 4;

      $csv_Add = array();
      echo "<tr>";
      $csv_Add[] = $row['clientName'];
      echo "<td>" .$row['clientName']. "</td>";
      $csv_Add[] = $row['projectName'];
      echo "<td>" .$row['projectName']. "</td>";
      $csv_Add[] = $row['infoText'];
      echo "<td style='text-align:left'><textarea name='infoTextArea[]' onkeyup='textAreaAdjust(this)'>" .$row['infoText']. "</textarea></td>";
      $csv_Add[] = $row['start'] . " - " . $row['end'];
      echo "<td><input type='text' style='max-width:90px; background:none;' readonly name='dateFrom[]' value='".substr($row['start'], 0, 10)."'>
      <input maxlength='19' onkeydown='if(event.keyCode == 13){return false;}' type='time' name='timesFrom[]' value='". substr(carryOverAdder_Hours($row['start'],$row['timeToUTC']),11,19) ."'>
      -
      <input type='text' style='max-width:90px; background:none;' readonly name='dateTo[]' value='".substr($row['end'], 0, 10)."'>
      <input maxlength='19' onkeydown='if(event.keyCode == 13){return false;}' type='time' name='timesTo[]' value='". substr(carryOverAdder_Hours($row['end'],$row['timeToUTC']),11,19) ."'></td>";
      $csv_Add[] = number_format((timeDiff_Hours($row['start'], $row['end']))*60, 2, '.', '');
      echo "<td>" .number_format((timeDiff_Hours($row['start'], $row['end']))*60, 2, '.', '') . "</td>";
      $csv_Add[] = $t;
      echo "<td>$t</td>";
      $csv_Add[] = $row['hours'];
      echo "<td>" .$row['hours']. "</td>";
      $csv_Add[] = $row['firstname']." ".$row['lastname'];
      echo "<td>" .$row['firstname']." ".$row['lastname']. "</td>" ;

      if($row['booked'] != 'TRUE'){
        $selected = "";
      } else {
        $selected = "checked";
      }
      echo "<td><input type='checkbox' $selected name='checkingIndeces[]' value='".$row['projectBookingID']."'>"; //gotta know which ones he wants checked.
      echo " / <input type='checkbox' name='noCheckCheckingIndeces[]' value='".$row['projectBookingID']."'></td>";
      $csv_Add[] = $row['hourlyPrice'];
      echo "<td>".$row['hourlyPrice']."</td>";
      echo "</tr>";

      $csv->addLine($csv_Add);
      echo '<input type="text" style="display:none;" name="editingIndeces[]" value="' . $row['projectBookingID'] . '">'; //since we dont know what has been edited: save all.

      $sum_min += timeDiff_Hours($row['start'], $row['end']);
      $sum25 += $t;
    }
  } else {
    echo mysqli_error($conn);
    echo "-";
  }
}

echo "<tr>";
echo "<td style='font-weight:bold'>Summary</td><td></td><td></td><td></td>";
echo "<td>".number_format($sum_min*60, 2, '.', '')."</td><td>$sum25</td>";
echo "</tr>";

?>

<script>
for(var i = 0; i < document.getElementsByName('infoTextArea[]').length; i++){
  textAreaAdjust(document.getElementsByName('infoTextArea[]')[i]);
}
</script>
</table>
<?php
if(!isset($_POST['filterBooked']) || $_POST['filterBooked'] != '1'): ?>
</fieldset>
<?php endif; ?>
</div>

<br><br>
<?php if(isset($_POST['filterBooked']) && $_POST['filterBooked'] == '1'): ?>
<input type='submit' name='saveChanges' value='Save Changes'><br><br>
<?php endif; ?>

</form>
<div style=clear:both;float:left; >
<form action="csvDownload.php" method="post" target='_blank'>
<button type='submit' name=csv value=<?php echo rawurlencode($csv->compile()); ?>> Download as CSV </a>
</form>
</div>

<br><br>
</body>
