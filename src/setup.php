<?php
//this will only be called on the setup for persons with private hosting
require 'connection_config.php';
require "validate.php"; denyToCloud();

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
  echo "<br>Connection Error: Could not Connect.<a href='setup_getInput.php'>Click here to return to previous page.</a><br>";
  die();
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbName";
if ($conn->query($sql)) {
  echo "Database was created. <br>";
} else {
    echo "<br>Invalid Database name: Could not instantiate a database.<a href='setup_getInput.php'>Return</a><br>";
  die();
}
$conn->close();

require 'connection.php';

if(isset($_GET)){
  $psw = $_GET['psw'];
  $companyName = rawurldecode($_GET['companyName']);
  $firstname = rawurldecode($_GET['first']);
  $lastname = rawurldecode($_GET['last']);
  $companyType = rawurldecode($_GET['login']);
  $loginname = $firstname . $lastname .'@' .$companyName.$companyType;
}
echo "<br><br><br> Your Login E-Mail: $loginname <br><br><br>";

require "setup_inc.php"; //creates all tables

$sql = "INSERT INTO $userTable (firstname, lastname, email, psw) VALUES ('$firstname', '$lastname', '$loginname', '$psw');";
if ($conn->query($sql)) {
  echo "registered admin as first user. <br>";
} else {
  echo mysqli_error($conn);
}

require 'version_number.php';
$sql = "INSERT INTO $adminLDAPTable (adminID, version) VALUES (1, $VERSION_NUMBER)";
if ($conn->query($sql)) {
  echo "Insert into ldap table. <br>";
} else {
  echo mysqli_error($conn);
}

$sql = "INSERT INTO $companyTable (name) VALUES ('$companyName')";
if ($conn->query($sql)) {
  echo "Insert default Administration company. <br>";
} else {
  echo mysqli_error($conn);
}

//insert company-client relationship
$sql = "INSERT INTO $companyToUserRelationshipTable(companyID, userID) VALUES(1,1)";
$conn->query($sql);

//insert bookingtable
$sql = "INSERT INTO $bookingTable (userID) VALUES (1);";
$conn->query($sql);

//insert vacationtable
$sql = "INSERT INTO $vacationTable (userID) VALUES(1);";
$conn->query($sql);

//insert roletable
$sql = "INSERT INTO $roleTable (userID, isCoreAdmin, isTimeAdmin, isProjectAdmin, canStamp) VALUES(1, 'TRUE', 'TRUE', 'TRUE', 'TRUE');";
$conn->query($sql);


$holi = icsToArray('../Feiertage.txt');
for($i = 1; $i < count($holi); $i++){
  if($holi[$i]['BEGIN'] == 'VEVENT'){
    $start = substr($holi[$i]['DTSTART;VALUE=DATE'], 0, 4) ."-" . substr($holi[$i]['DTSTART;VALUE=DATE'], 4, 2) . "-" . substr($holi[$i]['DTSTART;VALUE=DATE'], 6, 2) . " 00:00:00";
    $end = substr($holi[$i]['DTEND;VALUE=DATE'], 0, 4) ."-" . substr($holi[$i]['DTEND;VALUE=DATE'], 4, 2) . "-" . substr($holi[$i]['DTEND;VALUE=DATE'], 6, 2) . " 23:59:59";
    $n = $holi[$i]['SUMMARY'];

    $sql = "INSERT INTO $holidayTable(begin, end, name) VALUES ('$start', '$end', '$n');";
    $conn->query($sql);
  }
}


//------------------------------------------------------------------------------

function icsToArray($paramUrl) {
  $icsFile = file_get_contents($paramUrl);
  $icsData = explode("BEGIN:", $icsFile);
  foreach ($icsData as $key => $value) {
    $icsDatesMeta[$key] = explode("\n", $value);
  }
  foreach ($icsDatesMeta as $key => $value) {
    foreach ($value as $subKey => $subValue) {
      if ($subValue != "") {
        if ($key != 0 && $subKey == 0) {
          $icsDates[$key]["BEGIN"] = $subValue;
        } else {
          $subValueArr = explode(":", $subValue, 2);
          $icsDates[$key][$subValueArr[0]] = $subValueArr[1];
        }
      }
    }
  }
  return $icsDates;
}

//------------------------------------------------------------------------------

$repositoryPath = dirname(dirname(realpath("setup.php")));

//git init
$command = 'git -C ' .$repositoryPath. ' init 2>&1';
exec($command, $output, $returnValue);

//sslyverify false
$command = 'git -C ' .$repositoryPath. ' config http.sslVerify "false" 2>&1';
exec($command, $output, $returnValue);

//remote add
$command = "git -C $repositoryPath remote add -t master origin https://github.com/eitea/T-Time.git 2>&1";
exec($command, $output, $returnValue);

$command = "git -C $repositoryPath fetch --force 2>&1";
exec($command, $output, $returnValue);

$command = "git -C $repositoryPath reset --hard origin/master 2>&1";
exec($command, $output, $returnValue);

echo implode('<br>', $output);

//------------------------------------------------------------------------------
die ('<br><br> Setup Finished. Click here after writing down your Login E-Mail: <a href="login.php">Next</a>');
