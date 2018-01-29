<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<style>/*  HEADER AND CONTENT  */
html,body{
  padding-top: 25px;
  font: 13px/1.4 Geneva, 'Lucida Sans', 'Lucida Grande', 'Lucida Sans Unicode', Verdana, sans-serif;
  text-align: center;
  line-height:200%;
}
#progressBar_grey{
  margin-left:10%;
  width:80%;
  background-color:#ddd;
  border-radius:10px;
}
#progress{
  width: 0%;
  background-color: #ff9900;
  color: #ff9900;
  border-radius:10px;
}
#progress_text{
  z-index:10;
  background-color : transparent;
  position: absolute;
  left:50%;
  color:white;
}
</style>

<script>
document.onreadystatechange = function () {
  var state = document.readyState
  if (state == 'complete') {
    document.getElementById("content").style.display = "block";
  } else {
    move();
  }
}
function move() {
  var elem = document.getElementById("progress");
  var elem_text = document.getElementById("progress_text");
  var width = 10;
  var id = setInterval(frame, 20); //calling frame every Xms, 10ms = 1 second
  function frame() {
    if (width >= 100) {
      clearInterval(id);
    } else {
      width++;
      elem.style.width = width + '%';
      elem_text.innerHTML = width * 1  + '%';
    }
  }
}
</script>

<body>
  <div id="progressBar_grey">
    <div id="progress_text">0%</div>
    <div id="progress">.</div>
  </div>
  <div id="content" style="display:none;">
<br>
<?php
require dirname(dirname(__DIR__)) . "/connection.php";
require dirname(dirname(__DIR__)) . "/utilities.php";
include dirname(dirname(__DIR__)) . '/validate.php';

$sql = "SELECT * FROM $adminLDAPTable;";
$result = mysqli_query($conn, $sql);
$row = $result->fetch_assoc();

if ($row['version'] < 100) {
    $conn->query("DELETE FROM holidays");

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

    $holidayFile = dirname(dirname(__DIR__)) . '/setup/Feiertage.txt';
    $holidayFile = icsToArray($holidayFile);
    for ($i = 1; $i < count($holidayFile); $i++) {
        if (trim($holidayFile[$i]['BEGIN']) == "VEVENT") {
            $start = substr($holidayFile[$i]['DTSTART;VALUE=DATE'], 0, 4) . "-" . substr($holidayFile[$i]['DTSTART;VALUE=DATE'], 4, 2) . "-" . substr($holidayFile[$i]['DTSTART;VALUE=DATE'], 6, 2) . " 00:00:00";
            $end = substr($holidayFile[$i]['DTEND;VALUE=DATE'], 0, 4) . "-" . substr($holidayFile[$i]['DTEND;VALUE=DATE'], 4, 2) . "-" . substr($holidayFile[$i]['DTEND;VALUE=DATE'], 6, 2) . " 20:00:00";
            $n = $holidayFile[$i]['SUMMARY'];
            $conn->query("INSERT INTO holidays(begin, end, name) VALUES ('$start', '$end', '$n');");
            echo $conn->error;
        }
    }

    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Repaired Wrong Charactersets';
    }
}

if ($row['version'] < 101) {
    $conn->query("ALTER TABLE articles ADD COLUMN iv VARCHAR(255)");
    $conn->query("ALTER TABLE articles ADD COLUMN iv2 VARCHAR(255)");
    $conn->query("ALTER TABLE articles CHANGE name name VARCHAR(255)"); //50 -> 255
    $conn->query("ALTER TABLE articles CHANGE description description VARCHAR(1200)"); //600 -> 1200
    $conn->query("ALTER TABLE products ADD COLUMN iv VARCHAR(255)");
    $conn->query("ALTER TABLE products ADD COLUMN iv2 VARCHAR(255)");
    $conn->query("ALTER TABLE products CHANGE name name VARCHAR(255)"); //50 -> 255
    $conn->query("ALTER TABLE products CHANGE description description VARCHAR(600)"); //300 -> 600
    $conn->query("UPDATE configurationData set masterPassword = ''");

    $conn->query("CREATE TABLE resticconfiguration(
    path VARCHAR(255),
    password VARCHAR(255),
    awskey VARCHAR(255),
    awssecret VARCHAR(255),
    location VARCHAR(255)
  )");
    $conn->query("INSERT INTO resticconfiguration () VALUES ()");
}

if ($row['version'] < 102) {
    $result = $conn->query("SELECT * FROM projectBookingData WHERE infoText LIKE '%_?_%'");
    $pool = array('ä', 'ö', 'ü');
    while ($row = $result->fetch_assoc()) {
        $letter = $pool[rand(0, 2)];
        $newText = str_ireplace('f?', 'fü', $row['infoText']);
        $newText = str_ireplace('l?r', 'lär', $newText);
        $newText = str_ireplace('s?tz', 'sätz', $newText);
        $newText = str_ireplace('tr?g', 'träg', $newText);
        $newText = str_ireplace('w?', 'wö', $newText);
        $newText = str_ireplace('k?', 'kö', $newText);
        $newText = str_ireplace('m?', 'mö', $newText);
        $newText = str_ireplace('l?', 'lö', $newText);
        $newText = str_ireplace('z?', 'zü', $newText);
        $newText = str_ireplace('sch?', 'schö', $newText);
        $newText = str_ireplace('?b', 'üb', $newText);
        $newText = str_ireplace('r?', 'rü', $newText);
        $newText = str_ireplace('?nd', 'änd', $newText);
        $newText = str_ireplace('?', $letter, $newText);
        $conn->query("UPDATE projectBookingData SET infoText = '$newText' WHERE id = " . $row['id']);
    }
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Repaired Wrong Charactersets';
    }
}

if ($row['version'] < 103) {
    $sql = "CREATE TABLE identification(
    id VARCHAR(60) UNIQUE NOT NULL
  )";
    if ($conn->query($sql)) {
        echo '<br> Created identification table';
    }

    $identifier = str_replace('.', '0', randomPassword() . uniqid('', true) . randomPassword() . uniqid('') . randomPassword()); //60 characters;
    $conn->query("INSERT INTO identification (id) VALUES ('$identifier')");
    if ($conn->query($sql)) {
        echo '<br> Insert unique ID';
    }
}

if ($row['version'] < 104) {
    $conn->query("ALTER TABLE paymentMethods ADD COLUMN daysNetto INT(4)");
    $conn->query("ALTER TABLE paymentMethods ADD COLUMN skonto1 DECIMAL(6,2)");
    $conn->query("ALTER TABLE paymentMethods ADD COLUMN skonto2 DECIMAL(6,2)");
    $conn->query("ALTER TABLE paymentMethods ADD COLUMN skonto1Days INT(4)");
    $conn->query("ALTER TABLE paymentMethods ADD COLUMN skonto2Days INT(4)");

    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Zahlungsmethoden Update';
    }
}

if ($row['version'] < 105) {
    $conn->query("ALTER TABLE proposals DROP COLUMN yourSign");
    $conn->query("ALTER TABLE proposals DROP COLUMN yourOrder");
    $conn->query("ALTER TABLE proposals DROP COLUMN ourMessage");
    $conn->query("ALTER TABLE proposals DROP COLUMN ourSign");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>ERP Bezugszeichenzeile aus Aufträge entfernt';
    }

    $conn->query("ALTER TABLE erpNumbers ADD COLUMN yourSign VARCHAR(30)");
    $conn->query("ALTER TABLE erpNumbers ADD COLUMN yourOrder VARCHAR(30)");
    $conn->query("ALTER TABLE erpNumbers ADD COLUMN ourMessage VARCHAR(30)");
    $conn->query("ALTER TABLE erpNumbers ADD COLUMN ourSign VARCHAR(30)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>ERP Bezugszeichenzeile zu Mandant hinzugefügt';
    }

    $conn->query("ALTER TABLE proposals ADD COLUMN header VARCHAR(400)");
    $conn->query("ALTER TABLE proposals ADD COLUMN referenceNumrow VARCHAR(10)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>ERP Kopftext und Bezugszeichenzeile an/aus Option';
    }

    $conn->query("ALTER TABLE clientInfoData DROP COLUMN daysNetto");
    $conn->query("ALTER TABLE clientInfoData DROP COLUMN skonto1");
    $conn->query("ALTER TABLE clientInfoData DROP COLUMN skonto2");
    $conn->query("ALTER TABLE clientInfoData DROP COLUMN skonto1Days");
    $conn->query("ALTER TABLE clientInfoData DROP COLUMN skonto2Days");

    $conn->query("ALTER TABLE proposals DROP COLUMN daysNetto");
    $conn->query("ALTER TABLE proposals DROP COLUMN skonto1");
    $conn->query("ALTER TABLE proposals DROP COLUMN skonto2");
    $conn->query("ALTER TABLE proposals DROP COLUMN skonto1Days");
    $conn->query("ALTER TABLE proposals DROP COLUMN skonto2Days");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>ERP: Zahlungsbedingung stark vereinfacht';
    }
}

if ($row['version'] < 106) {
    $conn->query("ALTER TABLE roles ADD COLUMN isFinanceAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE' ");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Finanzen: Admin Rolle hinzugefügt';
    }

    $sql = "CREATE TABLE accounts (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    companyID INT(6) UNSIGNED,
    num INT(4) UNSIGNED,
    name VARCHAR(50),
    type ENUM('1', '2', '3', '4') DEFAULT '1',
    UNIQUE KEY (companyID, num),
    FOREIGN KEY (companyID) REFERENCES companyData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
    if ($conn->query($sql)) {
        echo '<br>Finanzen: T-Konten hinzugefügt';
    } else {
        echo $conn->error;
    }

    $sql = "CREATE TABLE account_balance(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    docNum INT(6),
    payDate DATETIME,
    account INT(6) UNSIGNED,
    offAccount INT(6) UNSIGNED,
    info VARCHAR(70),
    tax INT(4) UNSIGNED,
    should DECIMAL(18,2),
    have DECIMAL(18,2),
    FOREIGN KEY (account) REFERENCES accounts(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (offAccount) REFERENCES accounts(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
    if ($conn->query($sql)) {
        echo '<br>Finanzen: Bank und Kassa hinzugefügt';
    } else {
        echo $conn->error;
    }

    $conn->query("ALTER TABLE taxRates ADD COLUMN account2 INT(4)");
    $conn->query("ALTER TABLE taxRates ADD COLUMN account3 INT(4)");
}

if ($row['version'] < 107) {
    $sql = "CREATE TABLE projectBookingData_audit(
    id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    changedat DATETIME,
    bookingID INT(6) UNSIGNED,
    statement VARCHAR(100)
  )";
    if ($conn->query($sql)) {
        echo '<br>Audit: Projektbuchungen hinzugefügt';
    } else {
        echo $conn->error;
    }

    //DELIMITERS are client syntax
    $sql = "CREATE TRIGGER projectBookingData_update_trigger
    BEFORE UPDATE ON projectBookingData
    FOR EACH ROW
  BEGIN
    SELECT COUNT(*) INTO @cnt FROM projectBookingData_audit;
    IF @cnt >= 150 THEN
      DELETE FROM projectBookingData_audit ORDER BY id LIMIT 1;
    END IF;
    INSERT INTO projectBookingData_audit
    SET changedat = UTC_TIMESTAMP, bookingID = NEW.id, statement = CONCAT('UPDATE ', OLD.id);
  END";
    if ($conn->query($sql)) {
        echo '<br>Audit: 150 Zeilen Trigger für Projektbuchungen eingesetzt';
    } else {
        echo $conn->error;
    }

    $conn->query("CREATE TRIGGER projectBookingData_delete_trigger
      BEFORE DELETE ON projectBookingData
      FOR EACH ROW
    BEGIN
      SELECT COUNT(*) INTO @cnt FROM projectBookingData_audit;
      IF @cnt >= 150 THEN
        DELETE FROM projectBookingData_audit ORDER BY id LIMIT 1;
      END IF;
      INSERT INTO projectBookingData_audit
      SET changedat = UTC_TIMESTAMP, bookingID = OLD.id, statement = 'DELETE';
    END");

    $conn->query("CREATE TRIGGER projectBookingData_insert_trigger
      AFTER INSERT ON projectBookingData
      FOR EACH ROW
    BEGIN
      SELECT COUNT(*) INTO @cnt FROM projectBookingData_audit;
      IF @cnt >= 150 THEN
        DELETE FROM projectBookingData_audit ORDER BY id LIMIT 1;
      END IF;
      INSERT INTO projectBookingData_audit
      SET changedat = UTC_TIMESTAMP, bookingID = NEW.id, statement = 'INSERT';
    END");
}

if ($row['version'] < 108) {
    $sql = "CREATE TABLE account_journal(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userID INT(6),
    taxID INT(4) UNSIGNED,
    docNum INT(6),
    payDate DATETIME,
    inDate DATETIME,
    account INT(6) UNSIGNED,
    offAccount INT(6) UNSIGNED,
    info VARCHAR(70),
    should DECIMAL(18,2),
    have DECIMAL(18,2),
    FOREIGN KEY (account) REFERENCES accounts(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (offAccount) REFERENCES accounts(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
    if ($conn->query($sql)) {
        echo '<br>Finanzen: Buchungsjournal hinzugefügt';
    } else {
        echo $conn->error;
    }

    $conn->query("ALTER TABLE accounts ADD COLUMN manualBooking VARCHAR(10) DEFAULT 'FALSE' ");
    if (!$conn->error) {
        echo '<br>Finanzen: Steuern';
    } else {
        echo $conn->error;
    }

    $conn->query("DROP TABLE account_balance");
    $sql = "CREATE TABLE account_balance(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    journalID INT(10) UNSIGNED,
    accountID INT(4) UNSIGNED,
    should DECIMAL(18,2),
    have DECIMAL(18,2),
    FOREIGN KEY (accountID) REFERENCES accounts(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (journalID) REFERENCES account_journal(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    } else {
        echo '<br>Buchhungsjournal Eintrag mit Buchungen verknüpft';
    }

    $conn->query("ALTER TABLE taxRates ADD COLUMN code INT(2)");
    echo $conn->error;
}

if ($row['version'] < 109) {
    $conn->query("ALTER TABLE companyData MODIFY column companyCity VARCHAR(60) ");
    if (!$conn->error) {
        echo '<br>Mandant: 54 Zeichen Ort';
    } else {
        echo $conn->error;
    }
}

if ($row['version'] < 110) {
    //WEB
    $sql = "CREATE TABLE receiptBook(
    id INT(8) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplierID INT(6) UNSIGNED,
    taxID INT(4) UNSIGNED,
    journalID INT(10) UNSIGNED,
    invoiceDate DATETIME,
    info VARCHAR(64),
    amount DECIMAL(10,2),
    FOREIGN KEY (supplierID) REFERENCES clientData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (journalID) REFERENCES account_journal(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE
  )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    } else {
        echo '<br>ERP: Wareneingangsbuch';
    }

    //suppliers
    $conn->query("ALTER TABLE clientData ADD COLUMN isSupplier VARCHAR(10) DEFAULT 'FALSE' ");
    if (!$conn->error) {
        echo '<br>ERP: Lieferanten';
    } else {
        echo $conn->error;
    }

    //new accounts
    $conn->query("DELETE FROM accounts");
    $conn->query("ALTER TABLE accounts AUTO_INCREMENT = 1");
    $file = fopen(dirname(dirname(__DIR__)) . '/setup/Kontoplan.csv', 'r');
    if ($file) {
        $stmt = $conn->prepare("INSERT INTO accounts (companyID, num, name, type) SELECT id, ?, ?, ? FROM companyData");
        $stmt->bind_param("iss", $num, $name, $type);
        while (($line = fgetcsv($file, 300, ';')) !== false) {
            $num = $line[0];
            $name = trim(iconv(mb_detect_encoding($line[1], mb_detect_order(), true), "UTF-8", $line[1]));
            if (!$name) {
                $name = trim(iconv('MS-ANSI', "UTF-8", $line[1]));
            }

            if (!$name) {
                $name = $line[1];
            }

            $type = trim($line[2]);
            $stmt->execute();
        }
        $stmt->close();
    } else {
        echo "<br>Error Opening csv File";
    }
    $conn->query("UPDATE accounts SET manualBooking = 'TRUE' WHERE name = 'Bank' OR name = 'Kassa' ");

    $sql = "CREATE TABLE closeUpData(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userID INT(6) UNSIGNED,
    lastDate DATETIME NOT NULL,
    saldo DECIMAL(6,2),
    FOREIGN KEY (userID) REFERENCES UserData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    } else {
        echo '<br>Jahresabschlusstabelle hinzugefügt';
    }

    $conn->query("ALTER TABLE UserData ADD COLUMN strikeCount INT(3) DEFAULT 0");
    if (!$conn->error) {
        echo '<br>Benutzer: Punktesystem';
    } else {
        echo $conn->error;
    }
}

if ($row['version'] < 111) {
    $i = 1;
    $conn->query("DELETE FROM taxRates");
    $file = fopen(dirname(dirname(__DIR__)) . '/setup/Steuerraten.csv', 'r');
    if ($file) {
        $stmt = $conn->prepare("INSERT INTO taxRates(id, description, percentage, account2, account3, code) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isiiii", $i, $name, $percentage, $account2, $account3, $code);
        while ($line = fgetcsv($file, 100, ';')) {
            $name = trim(iconv(mb_detect_encoding($line[0], mb_detect_order(), true), "UTF-8", $line[0]));
            if (!$name) {
                $name = trim(iconv('MS-ANSI', "UTF-8", $line[0]));
            }

            if (!$name) {
                $name = $line[0];
            }

            $percentage = $line[1];
            $account2 = $line[2] ? $line[2] : NULL;
            $account3 = $line[3] ? $line[3] : NULL;
            $code = $line[4] ? $line[4] : NULL;
            $stmt->execute();
            $i++;
        }
        $stmt->close();
        fclose($file);
    } else {
        echo "<br>Error Opening csv File";
    }
    if (!$conn->error) {
        echo '<br>Finanzen: Neue Steuersätze';
    } else {
        echo $conn->error;
    }

    $sql = "CREATE TABLE accountingLocks(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    companyID INT(6) UNSIGNED,
    lockDate DATE NOT NULL,
    FOREIGN KEY (companyID) REFERENCES companyData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    } else {
        echo '<br>Finanzen: Buchungsmonat-Sperre';
    }
}

if ($row['version'] < 113) {
    $conn->query("CREATE TABLE checkinLogs(
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    timestampID INT(10) UNSIGNED,
    remoteAddr VARCHAR(50),
    userAgent VARCHAR(150)
  )");
    if (!$conn->error) {
        echo '<br>Checkin: IP und Header Logs';
    }

    $conn->query("ALTER TABLE logs ADD COLUMN emoji INT(2) DEFAULT 0 ");
    if (!$conn->error) {
        echo '<br>Checkout: Emoji';
    }

    $conn->query("ALTER TABLE articles ADD COLUMN taxID INT(4) UNSIGNED");
    $conn->query("ALTER TABLE products ADD COLUMN taxID INT(4) UNSIGNED");
    $conn->query("UPDATE articles SET taxID = taxPercentage");
    $conn->query("ALTER TABLE articles DROP COLUMN taxPercentage");
    $conn->query("UPDATE products p1 SET taxID = (SELECT id FROM taxRates WHERE percentage = p1.taxPercentage LIMIT 1)");
    $conn->query("ALTER TABLE products DROP COLUMN taxPercentage");
}

if ($row['version'] < 114) {
    $conn->query("DELETE FROM account_balance");
    $result = $conn->query("SELECT account_journal.*, percentage, account2, account3, code FROM account_journal LEFT JOIN taxRates ON taxRates.id = taxID");
    echo $conn->error;

    $stmt = $conn->prepare("INSERT INTO account_balance (journalID, accountID, should, have) VALUES(?, ?, ?, ?)");
    echo $conn->error;
    $stmt->bind_param("iidd", $journalID, $account, $should, $have);

    while ($row = $result->fetch_assoc()) {
        $addAccount = $row['account'];
        $offAccount = $row['offAccount'];
        $docNum = $row['docNum'];
        $date = $row['payDate'];
        $text = $row['info'];
        $should = $temp_should = $row['should'];
        $have = $temp_have = $row['have'];
        $tax = $row['taxID'];
        $journalID = $row['id'];

        $res = $conn->query("SELECT num FROM accounts WHERE id = $addAccount");
        if ($res && ($rowP = $res->fetch_assoc())) {
            $accNum = $rowP['num'];
        }

        //prepare balance
        $account2 = $account3 = '';
        if ($row['account2']) {
            $res = $conn->query("SELECT id FROM accounts WHERE num = " . $row['account2'] . " AND companyID IN (SELECT companyID FROM accounts WHERE id = $offAccount) ");
            echo $conn->error;
            if ($res && $res->num_rows > 0) {
                $account2 = $res->fetch_assoc()['id'];
            }

        }
        if ($row['account3']) {
            $res = $conn->query("SELECT id FROM accounts WHERE num = " . $row['account3'] . " AND companyID IN (SELECT companyID FROM accounts WHERE id = $offAccount) ");
            echo $conn->error;
            if ($res && $res->num_rows > 0) {
                $account3 = $res->fetch_assoc()['id'];
            }

        }

        //tax calculation
        if ($account2 && $account3) {
            $should_tax = $should * ($row['percentage'] / 100);
            $have_tax = $have * ($row['percentage'] / 100);
        } else {
            $should_tax = $should - ($should * 100) / (100 + $row['percentage']);
            $have_tax = $have - ($have * 100) / (100 + $row['percentage']);

        }

        $should = $temp_have;
        $have = $temp_should;
        //account balance
        if ($account2) {
            $should = $have_tax;
            $have = $should_tax;
            $account = $account2;
            $stmt->execute();
            if ($account3) {
                $should = $temp_have;
                $have = $temp_should;
            } else {
                $have = $temp_should - $should_tax;
                $should = $temp_have - $have_tax;
            }
        }
        $account = $offAccount;
        $stmt->execute();

        //offAccount balance
        $have = $temp_have;
        $should = $temp_should;
        if ($account3) {
            $have = $have_tax;
            $should = $should_tax;
            $account = $account3;
            $stmt->execute();
            if ($account2) {
                $should = $temp_should;
                $have = $temp_have;
            } else {
                $should = $temp_should - $should_tax;
                $have = $temp_have - $have_tax;
            }
        }
        $account = $addAccount;
        $stmt->execute();

    }
}

if ($row['version'] < 115) {
    $conn->query("DELETE a1 FROM account_journal a1, account_journal a2 WHERE a1.id > a2.id AND a1.userID = a2.userID AND a1.inDate = a2.inDate");

    $conn->query("ALTER TABLE account_journal ADD UNIQUE KEY double_submit (userID, inDate)");
    if (!$conn->error) {
        echo '<br>Finanzen: Doppelte Buchungen Fix';
    }

    $conn->query("ALTER TABLE UserData ADD COLUMN keyCode VARCHAR(100)");
    if (!$conn->error) {
        echo '<br>Verschlüsselung: Master Passwort aktualisiert';
    }

    $conn->query("ALTER TABLE configurationData ADD COLUMN checkSum VARCHAR(20)");
}

if ($row['version'] < 116) {
    //encrypted values need sooo much more SPACE
    $conn->query("ALTER TABLE configurationData MODIFY COLUMN checkSum VARCHAR(40)");

    $conn->query("ALTER TABLE clientInfoBank MODIFY COLUMN bankName VARCHAR(400)");

    $conn->query("ALTER TABLE clientInfoBank MODIFY COLUMN iban VARCHAR(400)");

    $conn->query("ALTER TABLE accounts ADD COLUMN options VARCHAR(10) DEFAULT 'STAT' ");

    $conn->query("CREATE TABLE processHistory(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(12) NOT NULL,
    processID INT(6) UNSIGNED,
    FOREIGN KEY (processID) REFERENCES proposals(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )");
    echo $conn->error;

    $stmt = $conn->prepare("INSERT INTO processHistory (id_number, processID) VALUES(?, ?) ");
    $stmt->bind_param("si", $id_number, $processID);

    $result = $conn->query("SELECT id, id_number, history FROM proposals");
    while ($row = $result->fetch_assoc()) {
        $processID = $row['id'];
        $id_number = $row['id_number'];
        $stmt->execute();
        if ($row['history']) {
            $arr = explode(' ', $row['history']);
            foreach ($arr as $a) {
                $id_number = $a;
                $stmt->execute();
            }
        }
    }
    $stmt->close();
    echo $conn->error;

    $conn->query("ALTER TABLE proposals DROP COLUMN id_number");
    $conn->query("ALTER TABLE proposals DROP COLUMN history");
    echo $conn->error;

    $conn->query("ALTER TABLE products DROP FOREIGN KEY products_ibfk_1");
    echo $conn->error;
    $conn->query("ALTER TABLE products DROP COLUMN proposalID");
    $conn->query("ALTER TABLE products DROP INDEX position");

    $conn->query("ALTER TABLE products ADD COLUMN historyID INT(6) UNSIGNED");
    $conn->query("ALTER TABLE products ADD COLUMN origin VARCHAR(16)");
    $conn->query("ALTER TABLE products ADD FOREIGN KEY (historyID) REFERENCES processHistory(id) ON UPDATE CASCADE ON DELETE CASCADE");
    echo $conn->error;

    $result = $conn->query("SELECT * FROM products");
    $conn->query("DELETE FROM products");
    $stmt = $conn->prepare("INSERT INTO products (name, description, historyID, origin, price, quantity, unit, taxID, cash, purchase, position, iv, iv2) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisddsiddiss", $name, $desc, $historyID, $origin, $price, $quantity, $unit, $taxID, $cash, $purchase, $pos, $iv, $iv2);
    echo $stmt->error;
    while ($row = $result->fetch_assoc()) {
        $name = $row['name'];
        $desc = $row['description'];
        $price = $row['price'];
        $quantity = $row['quantity'];
        $unit = $row['unit'];
        $taxID = $row['taxID'];
        $cash = $row['cash'];
        $purchase = $row['purchase'];
        $pos = $row['position'];
        $iv = $row['iv'];
        $iv2 = $row['iv2'];
        $origin = randomPassword(16);

        $processRes = $conn->query("SELECT id FROM processHistory WHERE processID = " . $row['proposalID']);
        echo $conn->error;
        while ($processRes && ($processRow = $processRes->fetch_assoc())) {
            $historyID = $processRow['id'];
            $stmt->execute();
        }
    }
    echo $conn->error;
    $conn->query("ALTER TABLE products DROP COLUMN proposalID");
    echo $conn->error;
}

if ($row['version'] < 117) {
    $sql = "CREATE TABLE documents(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    companyID INT(6) UNSIGNED,
    name VARCHAR(50) NOT NULL,
    txt MEDIUMTEXT NOT NULL,
    version VARCHAR(15) NOT NULL DEFAULT 'latest',
    FOREIGN KEY (companyID) REFERENCES companyData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    } else {
        echo '<br>DSGVO: Dokumente erstellt';
    }

    $conn->query("ALTER TABLE templateData ADD COLUMN type VARCHAR(10) NOT NULL DEFAULT 'report' ");
    if (!$conn->error) {
        echo '<br>Vorlagen: E-Mail Templates erweitert';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "CREATE TABLE contactPersons (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clientID INT(6) UNSIGNED,
    firstname VARCHAR(150),
    lastname VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    position VARCHAR(250),
    responsibility VARCHAR(250),
    FOREIGN KEY (clientID) REFERENCES clientData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    } else {
        echo '<br>Kundenstamm: Ansprechpartner';
    }

    $sql = "CREATE TABLE documentProcess(
    id VARCHAR(16) NOT NULL PRIMARY KEY,
    docID INT(6) UNSIGNED,
    personID INT(6) UNSIGNED,
    password VARCHAR(60) NOT NULL,
    FOREIGN KEY (docID) REFERENCES documents(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (personID) REFERENCES contactPersons(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    } else {
        echo '<br>DSGVO: Dokument Sendevorgänge';
    }

    $sql = "CREATE TABLE documentProcessHistory(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    processID VARCHAR(16),
    logDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activity VARCHAR(20) NOT NULL,
    info VARCHAR(450),
    userAgent VARCHAR(150)
  )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    } else {
        echo '<br>DSGVO: Prozess Logs erstellt';
    }

    $sql = "ALTER TABLE roles ADD COLUMN isDSGVOAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'";
    if ($conn->query($sql)) {
        echo '<br>DSGVO Admin Rolle';
    } else {
        echo '<br>' . $conn->error;
    }
}

if ($row['version'] < 118) {
    $sql = "ALTER TABLE documents MODIFY COLUMN name VARCHAR(100) NOT NULL";
    if (!$conn->query($sql)) {
        echo '<br>' . $conn->error;
    } else {
        echo '<br>Verfahrensverzeichnis: Einstellungen';
    }

    $conn->query("ALTER TABLE clientInfoData ADD COLUMN address_Addition VARCHAR(150)");
    $conn->query("ALTER TABLE documents MODIFY COLUMN name VARCHAR(100) NOT NULL");

    $conn->query("ALTER TABLE clientInfoData ADD COLUMN billingMailAddress VARCHAR(100)");
    if (!$conn->error) {
        echo '<br>Kundenstamm: Rechnungs Email Adresse';
    }
    $sql = "CREATE TABLE dsgvo_vv_templates(
		id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		companyID INT(6) UNSIGNED,
		name VARCHAR(60) NOT NULL,
		type ENUM('base', 'app') NOT NULL,
		FOREIGN KEY (companyID) REFERENCES companyData(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE
	)";
    if (!$conn->query($sql)) {
        echo '<br>' . $conn->error;
    } else {
        echo '<br>Verfahrensverzeichnis: Templates';
    }
    $sql = "CREATE TABLE dsgvo_vv_template_settings(
		id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		templateID INT(6) UNSIGNED,
		opt_name VARCHAR(30) NOT NULL,
		opt_descr VARCHAR(350) NOT NULL,
		opt_status VARCHAR(15) NOT NULL DEFAULT 'ACTIVE',
		FOREIGN KEY (templateID) REFERENCES dsgvo_vv_templates(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE
	)";
    if (!$conn->query($sql)) {
        echo '<br>' . $conn->error;
    } else {
        echo '<br>Verfahrensverzeichnis: Template Einstellungen';
    }
    $sql = "CREATE TABLE dsgvo_vv(
		id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		templateID INT(6) UNSIGNED,
		name VARCHAR(60) NOT NULL,
		FOREIGN KEY (templateID) REFERENCES dsgvo_vv_templates(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE
	)";
    if (!$conn->query($sql)) {
        echo '<br>' . $conn->error;
    } else {
        echo '<br>Verfahrensverzeichnisse erstellt.';
    }
    $sql = "CREATE TABLE dsgvo_vv_settings(
		id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		vv_id INT(6) UNSIGNED,
		setting_id INT(10) UNSIGNED,
		setting VARCHAR(850) NOT NULL,
		category VARCHAR(50),
		FOREIGN KEY (vv_id) REFERENCES dsgvo_vv(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
		FOREIGN KEY (setting_id) REFERENCES dsgvo_vv_template_settings(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE
	)";
    if (!$conn->query($sql)) {
        echo '<br>' . $conn->error;
    } else {
        echo '<br>Verfahrensverzeichnis: Einstellungen';
    }

    //INSERT DEFAULT TEMPLATES
    $base_opts = array('', 'Awarness: Regelmäßige Mitarbeiter Schulung in Bezug auf Datenschutzmanagement', 'Awarness: Risikoanalyse', 'Awarness: Datenschutz-Folgeabschätzung',
        'Zutrittskontrolle: Schutz vor unbefugten Zutritt zu Server, Netzwerk und Storage', 'Zutrittskontrolle: Protokollierung der Zutritte in sensible Bereiche (z.B. Serverraum)',
        'Zugangskontrolle: regelmäßige Passwortänderung der Benutzerpasswörter per Policy (mind. Alle 180 Tage)', 'Zugangskontrolle: regelmäßige Passwortänderung der administrativen Zugänge,
	Systembenutzer (mind. Alle 180 Tage)', 'Zugangskontrolle: automaischer Sperrmechanismus der Zugänge um Brut Force Attacken abzuwehren', 'Zugangskontrolle: Zwei-Faktor-Authentifizierung für externe Zugänge (VPN)',
        'Wechseldatenträger: Sperre oder zumindest Einschränkung von Wechseldatenträger (USB-Stick, SD Karte, USB Geräte mit Speichermöglichkeiten…)',
        'Infrastruktur: Verschlüsselung der gesamten Festplatte in PC und Notebooks', 'Infrastruktur: Network Access Control (NAC) im Netzwerk aktiv',
        'Infrastruktur: Protokollierung der Verbindungen über die Firewall (mind. 180 Tage)', 'Infrastruktur: Einsatz einer Applikationsbasierter Firewall (next Generation Firewall)', 'Infrastruktur: Backup-Strategie, die mind. Alle 180 Tage getestet wird', 'Infrastruktur: Virenschutz (advanced Endpoint Protection)',
        'Infrastruktur: Regelmäßige Failover Tests, falls ein zweites Rechenzentrum vorhanden ist', 'Infrastruktur: Protokollierung von Zugriffen und Alarmierung bei unbefugten Lesen oder Schreiben',
        'Weitergabekontrolle: Kein unbefugtes Lesen, Kopieren, Verändern oder Entfernen bei elektronischer Übertragung oder Transport, zB: Verschlüsselung, Virtual Private Networks (VPN), elektronische Signatur',
        'Drucker und MFP Geräte: Verschlüsselung der eingebauten Datenträger.', 'Drucker und MFP Geräte: Secure Printing bei personenbezogenen Daten. Unter "secure printing" versteht man die zusätzliche Authentifizierung direkt am Drucker, um den Ausdruck zu erhalten.',
        'Drucker und MFP Geräte: Bei Leasinggeräten, oder pay per Page Verträgen muss der Datenschutz zwischen den Vertragspartner genau geregelt werden (Vertrag).',
        'Eingabekontrolle: Feststellung, ob und von wem personenbezogene Daten in Datenverarbeitungssysteme eingegeben, verändert oder entfernt worden sind, zB: Protokollierung, Dokumentenmanagement');
    $app_opt_1 = array('', 'Name der verantwortlichen Stelle für diese Applikation', 'Beschreibung der betroffenen Personengruppen und der diesbezüglichen Daten oder Datenkategorien',
        'Zweckbestimmung der Datenerhebung, Datenverarbeitung und Datennutzung', 'Regelfristen für die Löschung der Daten', 'Datenübermittlung in Drittländer', 'Einführungsdatum der Applikation', 'Liste der zugriffsberechtigten Personen');
    $app_opt_2 = array('', 'Pseudonymisierung: Falls die jeweilige Datenanwendung eine Pseudonymisierung unterstützt, wird diese aktiviert. Bei einer Pseudonymisierung werden personenbezogene Daten in der Anwendung entfernt und gesondert aufbewahrt.',
        'Verschlüsselung der Daten: Sofern von der jeweiligen Datenverarbeitung möglich, werden die personenbezogenen Daten verschlüsselt und nicht als Plain-Text Daten gespeichert',
        'Applikation: Backup-Strategie, die mind. Alle 180 Tage getestet wird', 'Applikation: Protokollierung von Zugriffen und Alarmierung bei unbefugten Lesen oder Schreiben',
        'Weitergabekontrolle: Kein unbefugtes Lesen, Kopieren, Verändern oder Entfernen bei elektronischer Übertragung oder Transport, zB: Verschlüsselung, Virtual Private Networks (VPN), elektronische Signatur',
        'Vertraglich (bei externer Betreuung): Gib eine schriftliche Übereinkunft der Leistung und Verpflichtung mit dem entsprechenden Dienstleister der Software?',
        'Eingabekontrolle: Feststellung, ob und von wem personenbezogene Daten in Datenverarbeitungssysteme eingegeben, verändert oder entfernt worden sind, zB: Protokollierung, Dokumentenmanagement');

    $stmt_vv = $conn->prepare("INSERT INTO dsgvo_vv(templateID, name) VALUES(?, 'Basis')");
    $stmt_vv->bind_param("i", $templateID);
    $stmt = $conn->prepare("INSERT INTO dsgvo_vv_template_settings(templateID, opt_name, opt_descr) VALUES(?, ?, ?)");
    $stmt->bind_param("iss", $templateID, $opt, $descr);
    $result = $conn->query("SELECT id FROM companyData");
    while ($row = $result->fetch_assoc()) {
        $cmpID = $row['id'];
        $conn->query("INSERT INTO dsgvo_vv_templates(companyID, name, type) VALUES ($cmpID, 'Default', 'base')");
        $templateID = $conn->insert_id;
        $stmt_vv->execute();
        //BASE
        $descr = '';
        $opt = 'DESCRIPTION';
        $stmt->execute();
        $descr = 'Leiter der Datenverarbeitung (IT Leitung)';
        $opt = 'GEN_1';
        $stmt->execute();
        $descr = 'Inhaber, Vorstände, Geschäftsführer oder sonstige gesetzliche oder nach der Verfassung des Unternehmens berufene Leiter';
        $opt = 'GEN_2';
        $stmt->execute();
        $descr = 'Rechtsgrundlage(n) für die Verwendung von Daten';
        $opt = 'GEN_3';
        $stmt->execute();
        $i = 1;
        while ($i < 24) {
            $opt = 'MULT_OPT_' . $i;
            $descr = $base_opts[$i];
            $stmt->execute();
            $i++;
        }

        $conn->query("INSERT INTO dsgvo_vv_templates(companyID, name, type) VALUES ($cmpID, 'Default', 'app')");
        $templateID = $conn->insert_id;
        //APPS
        $descr = '';
        $opt = 'DESCRIPTION';
        $stmt->execute();
        $i = 1;
        while ($i < 8) {
            $opt = 'GEN_' . $i;
            $descr = $app_opt_1[$i];
            $stmt->execute();
            $i++;
        }
        $i = 1;
        while ($i < 8) {
            $opt = 'MULT_OPT_' . $i;
            $descr = $app_opt_2[$i];
            $stmt->execute();
            $i++;
        }
        $descr = 'Angaben zum Datenverarbeitungsregister (DVR)';
        $opt = 'EXTRA_DVR';
        $stmt->execute();
        $descr = 'Wurde eine Datenschutz-Folgeabschätzung durchgeführt?';
        $opt = 'EXTRA_FOLGE';
        $stmt->execute();
        $descr = 'Gibt es eine aktuelle Dokumentation dieser Applikation?';
        $opt = 'EXTRA_DOC';
        $stmt->execute();

        $opt = 'APP_MATR_DESCR';
        $stmt->execute();
        $opt = 'APP_GROUP_1';
        $descr = 'Kunde';
        $stmt->execute();
        $opt = 'APP_GROUP_2';
        $descr = 'Lieferanten und Partner';
        $stmt->execute();
        $opt = 'APP_GROUP_3';
        $descr = 'Mitarbeiter';
        $stmt->execute();
        $i = 1;
        $cat_descr = array('', 'Firmenname', 'Ansprechpartner, E-Mail, Telefon', 'Straße', 'Ort', 'Bankverbindung', 'Zahlungsdaten', 'UID', 'Firmenbuchnummer');
        while ($i < 9) { //Kunde
            $opt = 'APP_CAT_1_' . $i;
            $descr = $cat_descr[$i];
            $stmt->execute();
            $i++;
        }
        $i = 1;
        while ($i < 9) { //Lieferanten und Partner
            $opt = 'APP_CAT_2_' . $i;
            $descr = $cat_descr[$i];
            $stmt->execute();
            $i++;
        }
        $cat_descr = array('', 'Nachname', 'Vorname', 'PLZ', 'Ort', 'Telefon', 'Geb. Datum', 'Lohn und Gehaltsdaten', 'Religion', 'Gewerkschaftszugehörigkeit', 'Familienstand',
            'Anwesenheitsdaten', 'Bankverbindung', 'Sozialversicherungsnummer', 'Beschäftigt als', 'Staatsbürgerschaft', 'Geschlecht', 'Name, Geb. Datum und Sozialversicherungsnummer des Ehegatten',
            'Name, Geb. Datum und Sozialversicherungsnummer der Kinder', 'Personalausweis, Führerschein', 'Abwesenheitsdaten', 'Kennung');
        $i = 1;
        while ($i < 22) { //Mitarbeiter
            $opt = 'APP_CAT_3_' . $i;
            $descr = $cat_descr[$i];
            $stmt->execute();
            $i++;
        }
        $descr = '';
        $i = 1;
        while ($i < 21) { //20 App Spaces
            $opt = 'APP_HEAD_' . $i;
            $descr = $cat_descr[$i];
            $stmt->execute();
            $i++;
        }
    }
    $stmt->close();
    $stmt_vv->close();
}

if ($row['version'] < 119) {
    $conn->query("CREATE TABLE erp_settings(
	companyID INT(6) UNSIGNED,
	erp_ang INT(5) DEFAULT 1,
	erp_aub INT(5) DEFAULT 1,
	erp_re INT(5) DEFAULT 1,
	erp_lfs INT(5) DEFAULT 1,
	erp_gut INT(5) DEFAULT 1,
	erp_stn INT(5) DEFAULT 1,
	yourSign VARCHAR(30),
	yourOrder VARCHAR(30),
	ourSign VARCHAR(30),
	ourMessage VARCHAR(30),
	clientNum VARCHAR(12),
	clientStep INT(2),
	supplierNum VARCHAR(12),
	supplierStep INT(2),
	FOREIGN KEY (companyID) REFERENCES companyData(id)
	ON UPDATE CASCADE
	ON DELETE CASCADE
	)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Zähleinstellungen: Lieferanten und Kunden';
    }
    $conn->query("INSERT INTO erp_settings (companyID, erp_ang, erp_aub, erp_re, erp_lfs, erp_gut, erp_stn, yourSign, yourOrder, ourSign, ourMessage, clientNum, clientStep, supplierNum, supplierStep)
	SELECT companyID, erp_ang, erp_aub, erp_re, erp_lfs, erp_gut, erp_stn, yourSign, yourOrder, ourSign, ourMessage, '1000', '1', '1000', '1' FROM erpNumbers");
    echo $conn->error;
    $conn->query("DROP TABLE erpNumbers");
    echo $conn->error;

    $conn->query("ALTER TABLE UserData ADD COLUMN supervisor INT(6) DEFAULT NULL ");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Benutzer: Vorgesetzter';
    }

    $conn->query("ALTER TABLE clientInfoData ADD COLUMN homepage VARCHAR(100)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Datenstamm: Homepage';
    }
    $conn->query("ALTER TABLE clientInfoData ADD COLUMN mail VARCHAR(100)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Datenstamm: Allgemeine E-Mails';
    }

    $conn->query("ALTER TABLE clientInfoData ADD COLUMN billDelivery VARCHAR(60)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Kundendetails: Rechnungsversand';
    }

    $conn->query("ALTER TABLE roles ADD COLUMN isDynamicProjectsAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'");
    $conn->query("ALTER TABLE modules ADD COLUMN enableDynamicProjects ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'");

    $sql = "CREATE TABLE dynamicprojects(
        projectid VARCHAR(100) NOT NULL,
        projectname VARCHAR(60) NOT NULL,
        projectdescription VARCHAR(500) NOT NULL,
        companyid INT(6),
        projectcolor VARCHAR(10),
        projectstart VARCHAR(12),
        projectend VARCHAR(12),
        projectstatus ENUM('ACTIVE', 'DEACTIVATED', 'DRAFT', 'COMPLETED') DEFAULT 'ACTIVE',
        projectpriority INT(6),
        projectparent VARCHAR(100),
        projectowner INT(6),
        PRIMARY KEY (`projectid`)
      );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Tasks';
    }

    $sql = "CREATE TABLE dynamicprojectsclients(
        projectid VARCHAR(100) NOT NULL,
        clientid INT(6),
        projectcompleted INT(6),
        FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
        ON UPDATE CASCADE
        ON DELETE CASCADE
      );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Kunden';
    }

    $sql = "CREATE TABLE dynamicprojectsemployees(
        projectid VARCHAR(100) NOT NULL,
        userid INT(6),
        PRIMARY KEY(projectid, userid),
        FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
        ON UPDATE CASCADE
        ON DELETE CASCADE
      );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Employees';
    }

    $sql = "CREATE TABLE dynamicprojectsoptionalemployees(
        projectid VARCHAR(100) NOT NULL,
        userid INT(6),
        FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
        ON UPDATE CASCADE
        ON DELETE CASCADE
      );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Optional Employees';
    }

    $sql = "CREATE TABLE dynamicprojectspictures(
        projectid VARCHAR(100) NOT NULL,
        picture MEDIUMBLOB,
        FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
        ON UPDATE CASCADE
        ON DELETE CASCADE
      );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Pictures';
    }

    $sql = "CREATE TABLE dynamicprojectsseries(
        projectid VARCHAR(100) NOT NULL,
        projectnextdate VARCHAR(12),
        projectseries MEDIUMBLOB,
        FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
        ON UPDATE CASCADE
        ON DELETE CASCADE
      );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Series';
    }

    $sql = "CREATE TABLE dynamicprojectsnotes(
        projectid VARCHAR(100) NOT NULL,
        noteid INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        notedate DATETIME DEFAULT CURRENT_TIMESTAMP,
        notetext VARCHAR(1000),
        notecreator INT(6),
        FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
        ON UPDATE CASCADE
        ON DELETE CASCADE
      );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Notizen';
    }

    $sql = "CREATE TABLE dynamicprojectsbookings(
        projectid VARCHAR(100) NOT NULL,
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        bookingstart DATETIME DEFAULT CURRENT_TIMESTAMP,
        bookingend DATETIME,
        bookingclient INT(6) UNSIGNED,
        userid INT(6) UNSIGNED,
        bookingtext VARCHAR(1000)
      );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    }
}

if ($row['version'] < 120) {
    $conn->query("ALTER TABLE projectData ADD COLUMN dynamicprojectid VARCHAR(100)");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>projectData: +dynamicprojectid';
    }
    $conn->query("ALTER TABLE dynamicprojects DROP COLUMN projectdataid");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>dynamicprojects: -dynamicprojectid';
    }
}

if ($row['version'] < 121) {
    $conn->query("ALTER TABLE dynamicprojectsclients ADD COLUMN projectcompleted INT(6)");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>dynamicprojectsclients: +projectcompleted';
    }
    $conn->query("ALTER TABLE dynamicprojects DROP COLUMN projectcompleted");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>dynamicprojects: -projectcompleted';
    }
}

if ($row['version'] < 122) {
    $conn->query("ALTER TABLE dynamicprojectsbookings ADD COLUMN bookingclient INT(6) UNSIGNED");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>dynamicprojectsbookings: +bookingclient';
    }
    $conn->query("ALTER TABLE modules MODIFY COLUMN enableDynamicProjects ENUM('TRUE', 'FALSE') DEFAULT 'TRUE'");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>Dynamic Projects by default';
    }
    $conn->query("UPDATE modules SET enableDynamicProjects = 'TRUE'");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>Dynamic Projects enabled';
    }
    $conn->query("CREATE TABLE dynamicprojectsteams(
    projectid VARCHAR(100) NOT NULL,
    teamid INT(6) UNSIGNED,
    FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
    ON UPDATE CASCADE
    ON DELETE CASCADE
    );");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>dynamicprojectsteams';
    }
    $conn->query("ALTER TABLE dynamicprojectsemployees ADD PRIMARY KEY(projectid, userid);");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>dynamicprojectsteams';
    }
}

if ($row['version'] < 123) {
    $conn->query("CREATE TABLE sharedfiles (
	  id int(11) NOT NULL AUTO_INCREMENT,
	  name varchar(20) NOT NULL COMMENT 'ursprünglicher Name der Datei',
	  type varchar(10) NOT NULL COMMENT 'Dateiendung',
	  owner int(11) NOT NULL COMMENT 'User der die Datei hochgeladen hat',
	  sharegroup int(11) NOT NULL COMMENT 'in welcher Gruppe sie hinterlegt ist (groupID)',
	  hashkey varchar(32) NOT NULL COMMENT 'der eindeutige, sichere Key für den Link',
	  filesize bigint(20) NOT NULL,
	  uploaddate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	  PRIMARY KEY (id),
	  UNIQUE KEY hashkey (hashkey),
	  KEY owner (owner)
	 )");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>s3Files';
    }

    $conn->query("CREATE TABLE sharedgroups (
	  id int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
	  name varchar(50) NOT NULL COMMENT 'Name der SharedGruppe',
	  dateOfBirth timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Tag der Erstellung',
	  ttl int(10) NOT NULL COMMENT 'Tage bis der Link ungültig ist',
	  uri varchar(128) NOT NULL COMMENT 'URL zu den Objekten',
	  owner int(11) NOT NULL COMMENT 'Besitzer der Gruppe',
	  files varchar(200) DEFAULT NULL,
	  company int(11) NOT NULL COMMENT 'Mandant',
	  PRIMARY KEY (id),
	  UNIQUE KEY url (uri),
	  KEY owner (owner)
	  )");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>s3Groups';
    }

    $conn->query("CREATE TABLE uploadedfiles (
	  id INT NOT NULL AUTO_INCREMENT ,
	  uploadername VARCHAR NOT NULL ,
	  filename VARCHAR(20) NOT NULL ,
	  filetype VARCHAR(10) NOT NULL ,
	  hashkey VARCHAR(32) NOT NULL ,
	  filesize BIGINT(20) NOT NULL ,
	  uploaddate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
	  notes TEXT NULL ,
	  PRIMARY KEY (id),
	  UNIQUE hashkey (hashkey)
	  )");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>s3Upload';
    }

    $conn->query("ALTER TABLE mailingoptions ADD COLUMN senderName VARCHAR(50) DEFAULT NULL");

    $conn->query("ALTER TABLE mailingoptions ADD COLUMN isDefault TINYINT(1) NOT NULL DEFAULT 1");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>mailingoptions';
    }

    $conn->query("ALTER TABLE userdata ADD COLUMN publicPGPKey TEXT DEFAULT NULL");

    $conn->query("ALTER TABLE userdata ADD COLUMN privatePGPKey TEXT DEFAULT NULL");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>PGPKeys';
    }

    $conn->query("ALTER TABLE contactPersons ADD COLUMN dial VARCHAR(20)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Kontaktpersonen: Durchwahl';
    }

    $conn->query("ALTER TABLE contactPersons ADD COLUMN faxDial VARCHAR(20)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Kontaktpersonen: Faxfurchwahl';
    }

    $conn->query("ALTER TABLE contactPersons ADD COLUMN phone VARCHAR(25)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Kontaktpersonen: Mobiltelefon';
    }

    //ALTER TABLE `documents` DROP INDEX `docID`;
    $conn->query("ALTER TABLE documents ADD COLUMN docID VARCHAR(40)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Vereinbarungen: Template ID';
    }

    $conn->query("ALTER TABLE documents ADD COLUMN isBase ENUM('TRUE', 'FALSE') NOT NULL DEFAULT 'FALSE' ");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Vereinbarungen: Basis Templates';
    }

    $conn->query("CREATE TABLE document_customs(
		id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		companyID INT(6) UNSIGNED,
		doc_id VARCHAR(40) NOT NULL,
		identifier VARCHAR(30),
		content VARCHAR(450),
		status VARCHAR(10),
		FOREIGN KEY (companyID) REFERENCES companyData(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE
	)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Vereinbarungen: Freitext';
    }

    $conn->query("ALTER TABLE documentProcess ADD COLUMN document_text MEDIUMTEXT NOT NULL");

    $conn->query("ALTER TABLE documentProcess ADD COLUMN document_headline VARCHAR(120) NOT NULL");

    $conn->query("ALTER TABLE documentProcess ADD COLUMN document_version VARCHAR(15) NOT NULL DEFAULT '1.0' ");
}

if ($row['version'] < 124) {
    $conn->query("CREATE TABLE archiveconfig(endpoint VARCHAR(50),awskey VARCHAR(50),secret VARCHAR(50));");
    $conn->query("INSERT INTO archiveconfig VALUE (null,null,null)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>S3 Modul';
    }
    $conn->query("DROP TABLE modules");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Module: Auflösen';
    }
    $id = $conn->query("SELECT * FROM identification");
    $identifier = $id->fetch_assoc()['id'];
    $myfile = fopen(dirname(dirname(__DIR__)) .'/connection_config.php', 'a');
    $txt = '$identifier = "'.$identifier.'";
    $s3SharedFiles=$identifier."_sharedFiles";
    $s3uploadedFiles=$identifier."_uploadedFiles";';
    fwrite($myfile, $txt);
    fclose($myfile);

    echo '<br>S3 Configuration';

    $sql = "ALTER TABLE userRequestsData MODIFY COLUMN requestType VARCHAR(3) DEFAULT 'vac' NOT NULL;";
    if ($conn->query($sql)) {
        echo '<br> Extended requests by splitted lunchbreaks';
    }
}


if($row['version'] < 125){
    $conn->query("ALTER TABLE projectBookingData ADD COLUMN dynamicID VARCHAR(100)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Projektbuchungen: Task Referenz';
    }

    $result = $conn->query("SELECT projectid FROM dynamicprojects");
    while($row = $result->fetch_assoc()){
        $id = uniqid();
        $conn->query("UPDATE dynamicprojects SET projectid = '$id' WHERE projectid = '".$row['projectid']."'");
    }

    $result = $conn->query("SELECT id, dynamicprojectid FROM projectData WHERE dynamicprojectid IS NOT NULL");
    while($row = $result->fetch_assoc()){
        $conn->query("UPDATE projectBookingData SET dynamicID = '".$row['dynamicprojectid']."' WHERE projectID = ".$row['id']);
        echo $conn->error;

        $conn->query("UPDATE projectBookingData SET projectID = 350 WHERE projectID = ".$row['id']);
        echo $conn->error;
    }

    $conn->query("DELETE FROM projectData WHERE dynamicprojectid IS NOT NULL");
    $conn->query("ALTER TABLE projectData DROP COLUMN dynamicprojectid");


    $conn->query("ALTER TABLE dynamicprojects MODIFY COLUMN projectdescription TEXT NOT NULL");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Beschreibung';
    }

    $conn->query("ALTER TABLE dynamicprojects ADD COLUMN projectseries MEDIUMBLOB");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Routine Tasks';
    }

    $conn->query("ALTER TABLE dynamicprojects ADD COLUMN projectnextdate VARCHAR(12)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Umbelegung';
    }

    $conn->query("ALTER TABLE dynamicprojects ADD COLUMN clientid INT(6) UNSIGNED");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Kunde';
    }

    $conn->query("ALTER TABLE dynamicprojects ADD COLUMN clientprojectid INT(6) UNSIGNED");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Projekt ID';
    }

    $conn->query("ALTER TABLE dynamicprojects ADD COLUMN projectpercentage INT(3) DEFAULT 0");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Projekt Prozentsatz';
    }

    $conn->query("ALTER TABLE dynamicprojectsemployees ADD COLUMN position VARCHAR(10) DEFAULT 'normal' NOT NULL");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Position';
    }
    $conn->query("DROP TABLE dynamicprojectsclients");
    $conn->query("DROP TABLE dynamicprojectsseries");
    $conn->query("DROP TABLE dynamicprojectsbookings");

    $conn->query("INSERT INTO dynamicprojectsemployees (projectid, userid, position) SELECT (projectid, userid, 'optional') FROM dynamicprojectsoptionalemployees");
    $conn->query("DROP TABLE dynamicprojectsoptionalemployees");

    $conn->query("DELETE FROM dynamicprojectsemployees WHERE projectid IN (SELECT projectid FROM dynamicprojectsteams)");
}

if ($row['version'] < 126) { //25.01.2018
    $conn->query("ALTER TABLE dynamicprojects ADD COLUMN projectleader INT(6)");
    $conn->query("UPDATE dynamicprojects SET projectleader = projectowner");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Projektleiter';
    }

    $conn->query("ALTER TABLE dynamicprojects ADD estimatedHours INT(4) DEFAULT 0 NOT NULL");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Geschätzte Zeit';
    }

    $conn->query("CREATE TABLE dynamicprojectslogs(
        projectid VARCHAR(100) NOT NULL,
        activity VARCHAR(20) NOT NULL,
        logTime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        userID INT(6),
        extra1 VARCHAR(250),
        extra2 VARCHAR(450)
    )");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Aktivitäten Log';
    }
}

if($row['version'] < 127){ //29.01.2018
    $conn->query("ALTER TABLE teamRelationshipData ADD COLUMN skill INT(3) DEFAULT 0 NOT NULL");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Teams: Skill-Level';
    }

    $conn->query("ALTER TABLE dynamicprojects ADD COLUMN level INT(3) DEFAULT 0 NOT NULL");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Skill-Level';
    }
}

// ------------------------------------------------------------------------------

require dirname(dirname(__DIR__)) . '/version_number.php';
$conn->query("UPDATE $adminLDAPTable SET version=$VERSION_NUMBER");
echo '<br><br>Update wurde beendet. Klicken sie auf "Weiter", wenn sie nicht automatisch weitergeleitet werden: <a href="../user/home">Weiter</a>';
?>
<script type="text/javascript">
  window.setInterval(function(){
    window.location.href="../user/home";
  }, 4000);
</script>

<noscript>
  <meta http-equiv="refresh" content="0;url='.$url.'" />';
</noscript>
</div>
</body>
</html>