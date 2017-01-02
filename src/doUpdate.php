<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<?php
require  "connection.php";
require  "createTimestamps.php";
include 'validate.php';

$sql = "SELECT * FROM $adminLDAPTable;";
$result = mysqli_query($conn, $sql);
  $row = $result->fetch_assoc();

if($row['version'] < 29){
  $sql = "ALTER TABLE $roleTable ADD COLUMN canStamp ENUM('TRUE', 'FALSE') DEFAULT 'TRUE'";
  if($conn->query($sql)){
    echo "Updated roles - Add user role 1. <br>";
  } else {
    echo mysqli_error($conn);
  }
  $sql = "ALTER TABLE $roleTable ADD COLUMN canBook ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'";
  if($conn->query($sql)){
    echo "Updated roles - Add user role 2. <br>";
  } else {
    echo mysqli_error($conn);
  }

  $sql = "UPDATE $roleTable INNER JOIN $userTable ON $roleTable.userID = $userTable.id SET canBook = 'TRUE' WHERE enableProjecting = 'TRUE'";
  if($conn->query($sql)){
    echo "Copy data to match user role.<br>";
  } else {
    echo mysqli_error($conn);
  }

  $sql ="ALTER TABLE $userTable DROP COLUMN enableProjecting";
  if($conn->query($sql)){
    echo "Drop enableProjecting column <br>";
  } else {
    echo mysqli_error($conn);
  }
}

if($row['version'] < 30){
  $sql = "ALTER TABLE $projectBookingTable ADD COLUMN internInfo VARCHAR(500)";
  if($conn->query($sql)){
    echo "Expand booking table by internInfo.<br>";
  } else {
    echo mysqli_error($conn);
  }
}

if($row['version'] < 31){
  $sql = "ALTER TABLE $companyTable ADD COLUMN companyType ENUM('GmbH', 'AG', 'OG', 'KG', 'EU', '-') DEFAULT '-'";
  if($conn->query($sql)){
    echo "Expand company table by type.<br>";
  } else {
    echo mysqli_error($conn);
  }
}

if($row['version'] < 32){
  $sql = "ALTER TABLE $companyTable ADD COLUMN companyType ENUM('GmbH', 'AG', 'OG', 'KG', 'EU', '-') DEFAULT '-'";
  if($conn->query($sql)){
    echo "Expand company table by type.<br>";
  } else {
    echo mysqli_error($conn);
  }
}

if($row['version'] < 33){
  $sql = "ALTER TABLE $projectBookingTable ADD COLUMN chargedTimeStart DATETIME DEFAULT '0000-00-00 00:00:00'";
  if($conn->query($sql)){
    echo "Expand booking table by correction date-start.<br>";
  } else {
    echo mysqli_error($conn);
  }
  $sql = "ALTER TABLE $projectBookingTable ADD COLUMN chargedTimeEnd DATETIME DEFAULT '0000-00-00 00:00:00'";
  if($conn->query($sql)){
    echo "Expand booking table by correction date-end.<br>";
  } else {
    echo mysqli_error($conn);
  }
}

if($row['version'] < 34){
  $sql = "ALTER TABLE $projectBookingTable ADD COLUMN bookingType ENUM('project', 'break', 'drive')";
  if($conn->query($sql)){
    echo "Expand booking table by type.<br>";
  } else {
    echo mysqli_error($conn);
  }

  $conn->query("UPDATE $projectBookingTable SET bookingType = 'break' WHERE projectID IS NULL");
  echo mysqli_error($conn);
  $conn->query("UPDATE $projectBookingTable SET bookingType = 'project' WHERE projectID IS NOT NULL");
  echo mysqli_error($conn);
}

if($row['version'] < 35){
  $sql = "CREATE TABLE $travelCountryTable(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(10) NOT NULL,
    countryName VARCHAR(50),
    dayPay DECIMAL(6,2) DEFAULT 0,
    nightPay DECIMAL(6,2) DEFAULT 0
  )";
  if($conn->query($sql)){
    echo "Created countries for travelling expenses.<br>";
  } else {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE $travelTable(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userID INT(6) UNSIGNED,
    countryID INT(6) UNSIGNED,
    travelDayStart DATETIME NOT NULL,
    travelDayEnd DATETIME NOT NULL,
    kmStart INT(8),
    kmEnd INT(8),
    infoText VARCHAR(500),
    hotelCosts DECIMAL(8,2) DEFAULT 0,
    hosting10 DECIMAL(6,2) DEFAULT 0,
    hosting20 DECIMAL(6,2) DEFAULT 0,
    expenses DECIMAL(8,2) DEFAULT 0,
    FOREIGN KEY (userID) REFERENCES $userTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (countryID) REFERENCES $travelCountryTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if($conn->query($sql)){
    echo "Created table for travelling expenses.<br>";
  } else {
    echo mysqli_error($conn);
  }

  $handle = fopen("../Laender.txt", "r");
  if ($handle) {
      while (($line = fgets($handle)) !== false) {
        $line = iconv('windows-1250', 'UTF-8', $line);
        $thisLineIsNotOK = true;
        while($thisLineIsNotOK){
          $data = preg_split('/\s+/', $line);
          array_pop($data);
          if(count($data) == 4){
            $short = test_Input($data[0]);
            $name = test_Input($data[1]);
            $dayPay = floatval($data[2]);
            $nightPay = floatval($data[3]);
            $sql = "INSERT INTO $travelCountryTable(identifier, countryName, dayPay, nightPay) VALUES('$short', '$name', '$dayPay' , '$nightPay') ";
            $conn->query($sql);
            echo mysqli_error($conn);
            $thisLineIsNotOK = false;
          } elseif(count($data) > 4) {
            $line = substr_replace($line, '_', strlen($data[0].' '.$data[1]), 1);
          } else {
            echo 'Nope. <br>';
            print_r ($data);
            die();
          }
        }
      }
    fclose($handle);
  } else {
      // error opening the file.
  }
}

if($row['version'] < 36){
  $sql = "ALTER TABLE $userTable ADD COLUMN kmMoney DECIMAL(4,2) DEFAULT 0.42";
  $conn->query($sql);
  echo mysqli_error($conn);
}


if($row['version'] < 37){
  $sql = "CREATE TABLE $deactivatedUserTable (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(30) NOT NULL,
    lastname VARCHAR(30) NOT NULL,
    psw VARCHAR(60) NOT NULL,
    terminalPin INT(8) DEFAULT 4321,
    sid VARCHAR(50),
    email VARCHAR(50) UNIQUE NOT NULL,
    gender ENUM('female', 'male'),
    overTimeLump INT(3) DEFAULT 0,
    pauseAfterHours DECIMAL(4,2) DEFAULT 6,
    hoursOfRest DECIMAL(4,2) DEFAULT 0.5,
    beginningDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    preferredLang ENUM('ENG', 'GER', 'FRA', 'ITA') DEFAULT 'GER',
    coreTime TIME DEFAULT '8:00',
    kmMoney DECIMAL(4,2) DEFAULT 0.42
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "Created deact. usertab <br>";
  }

  $sql = "CREATE TABLE $deactivatedUserLogs (
    indexIM INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    time DATETIME NOT NULL,
    timeEnd DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    status ENUM('-1', '0', '1', '2', '3', '4'),
    timeToUTC INT(2) DEFAULT '2',
    breakCredit	DECIMAL(4,2),
    userID INT(6) UNSIGNED,
    expectedHours DECIMAL(4,2),
    FOREIGN KEY (userID) REFERENCES $deactivatedUserTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "Created deact. userlogs <br>";
  }

  $sql = "CREATE TABLE $deactivatedUserUnLogs(
    negative_indexIM INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    time DATETIME NOT NULL,
    userID INT(6) UNSIGNED,
    mon DECIMAL(4,2) DEFAULT 8.5,
    tue DECIMAL(4,2) DEFAULT 8.5,
    wed DECIMAL(4,2) DEFAULT 8.5,
    thu DECIMAL(4,2) DEFAULT 8.5,
    fri DECIMAL(4,2) DEFAULT 4.5,
    sat DECIMAL(4,2) DEFAULT 0,
    sun DECIMAL(4,2) DEFAULT 0,
    FOREIGN KEY (userID) REFERENCES $deactivatedUserTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "Created deact. unlogs <br>";
  }


  $sql = "CREATE TABLE $deactivatedUserDataTable(
    userID INT(6) UNSIGNED,
    mon DECIMAL(4,2) DEFAULT 8.5,
    tue DECIMAL(4,2) DEFAULT 8.5,
    wed DECIMAL(4,2) DEFAULT 8.5,
    thu DECIMAL(4,2) DEFAULT 8.5,
    fri DECIMAL(4,2) DEFAULT 4.5,
    sat DECIMAL(4,2) DEFAULT 0,
    sun DECIMAL(4,2) DEFAULT 0,
    vacationHoursCredit DECIMAL(6,2) DEFAULT 0,
    daysPerYear INT(2) DEFAULT 25,
    FOREIGN KEY (userID) REFERENCES $deactivatedUserTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "Created deact. datatable <br>";
  }


  $sql = "CREATE TABLE $deactivatedUserProjects (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    start DATETIME NOT NULL,
    end DATETIME NOT NULL,
    chargedTimeStart DATETIME DEFAULT '0000-00-00 00:00:00',
    chargedTimeEnd DATETIME DEFAULT '0000-00-00 00:00:00',
    projectID INT(6) UNSIGNED,
    timestampID INT(10) UNSIGNED,
    infoText VARCHAR(500),
    internInfo VARCHAR(500),
    booked ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    bookingType ENUM('project', 'break', 'drive'),
    FOREIGN KEY (projectID) REFERENCES $projectTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (timestampID) REFERENCES $deactivatedUserLogs(indexIM)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "Created deact. projectbookings <br>";
  }

  $sql = "CREATE TABLE $deactivatedUserTravels(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userID INT(6) UNSIGNED,
    countryID INT(6) UNSIGNED,
    travelDayStart DATETIME NOT NULL,
    travelDayEnd DATETIME NOT NULL,
    kmStart INT(8),
    kmEnd INT(8),
    infoText VARCHAR(500),
    hotelCosts DECIMAL(8,2) DEFAULT 0,
    hosting10 DECIMAL(6,2) DEFAULT 0,
    hosting20 DECIMAL(6,2) DEFAULT 0,
    expenses DECIMAL(8,2) DEFAULT 0,
    FOREIGN KEY (userID) REFERENCES $deactivatedUserTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (countryID) REFERENCES $travelCountryTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "Created deact. travellogs <br>";
  }
}


if($row['version'] < 38){
  $sql = "ALTER TABLE $userTable ADD COLUMN exitDate DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "Expanded Users by Exit Date <br>";
  }

  $sql = "ALTER TABLE $deactivatedUserTable ADD COLUMN exitDate DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "Expanded Users by Exit Date <br>";
  }
}

if($row['version'] < 39){
  $sql = "CREATE TABLE $clientDetailTable(
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contactType ENUM('person', 'company'),
  gender ENUM('female', 'male'),
  title VARCHAR(30),
  name VARCHAR(45) NOT NULL,
  nameAddition VARCHAR(45),
  address_Street VARCHAR(100),
  address_Country VARCHAR(100),
  phone VARCHAR(20),

  debitNumber INT(10),
  datev INT(10),
  accountName VARCHAR(100),
  taxnumber INT(50),
  taxArea VARCHAR(50),
  customerGroup VARCHAR(50),
  representative VARCHAR(50),
  blockDelivery ENUM('true', 'false') DEFAULT 'false',

  paymentMethod VARCHAR(100),
  shipmentType VARCHAR(100),
  creditLimit DECIMAL(10,2),
  eBill ENUM('true', 'false') DEFAULT 'false',
  lastFaktura DATETIME,

  daysNetto INT(4),
  skonto1 DECIMAL(6,2),
  skonto2 DECIMAL(6,2),
  skonto1Days INT(4),
  skonto2Days INT(4),
  warningEnabled ENUM('true', 'false') DEFAULT 'true',
  karenztage INT(4),
  lastWarning DATETIME,
  warning1 DECIMAL(10,2),
  warning2 DECIMAL(10,2),
  warning3 DECIMAL(10,2),
  calculateInterest ENUM('true', 'false'),

  clientID INT(6) UNSIGNED,
  FOREIGN KEY (clientID) REFERENCES $clientTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "Created Client detail Table <br>";
  }

  $sql = "CREATE TABLE $clientDetailNotesTable(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    infoText VARCHAR(800),
    createDate DATETIME,
    parentID INT(6) UNSIGNED,
    FOREIGN KEY (parentID) REFERENCES $clientDetailTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "Created detail info <br>";
  }

  $sql = "CREATE TABLE $clientDetailBankTable(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bic VARCHAR(20),
    iban VARCHAR(50),
    bankName VARCHAR(100),
    parentID  INT(6) UNSIGNED,
    FOREIGN KEY (parentID) REFERENCES $clientDetailTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "Created detail bank <br>";
  }
}

if($row['version'] < 40){
  $sql = "CREATE TABLE $pdfTemplateTable(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    htmlCode TEXT
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "Created storage for pdf Templates <br>";
  }
}


//------------------------------------------------------------------------------
require 'version_number.php';
$sql = "UPDATE $adminLDAPTable SET version=$VERSION_NUMBER";
$conn->query($sql);
header("refresh:6;url=home.php");
die ('<br>Update Finished. Click here if not redirected automatically: <a href="home.php">redirect</a>');
