
<?php require_once "../plugins/phpMailer/class.phpmailer.php"; ?>
<?php require_once "../plugins/phpMailer/class.smtp.php"; ?>
<?php require_once "connection.php"; require_once "validate.php"; enableToReport($userID); ?>

<?php
$mail = new PHPMailer();
$mail->IsSMTP();
$mail->SMTPAuth   = true;

//get all mail content
$resultContent = $conn->query("SELECT * FROM $pdfTemplateTable WHERE repeatCount != '' AND repeatCount IS NOT NULL "); //<> == !=
while($resultContent && ($rowContent = $resultContent->fetch_assoc())){ //for each report, send a mail
  $reportID = $rowContent['id'];
  $content = $rowContent['htmlCode'];

  //grab positions
  $pos1 = strpos($content, "[REPEAT]");
  $pos2 = strpos($content, "[REPEAT END]");
  //explode my repeat pattern
  $html_head = substr($content, 0, $pos1);
  $html_foot = substr($content, $pos2 + 12);
  $repeat = substr($content, $pos1 + 12 , $pos2 - $pos1 - 12);
  //replace all findings
  $t = localtime(time(), true);
  $today = $t["tm_year"] + 1900 . "-" . sprintf("%02d", ($t["tm_mon"]+1)) . "-". sprintf("%02d", $t["tm_mday"]);

  $sql="SELECT $projectTable.id AS projectID,
  $clientTable.id AS clientID,
  $clientTable.name AS clientName,
  $projectTable.name AS projectName,
  $projectBookingTable.*,
  $projectBookingTable.id AS projectBookingID,
  $logTable.timeToUTC,
  $userTable.firstname, $userTable.lastname,
  $projectTable.hours,
  $projectTable.hourlyPrice,
  $projectTable.status
  FROM $projectBookingTable
  INNER JOIN $logTable ON $projectBookingTable.timeStampID = $logTable.indexIM
  INNER JOIN $userTable ON $logTable.userID = $userTable.id
  INNER JOIN $projectTable ON $projectBookingTable.projectID = $projectTable.id
  INNER JOIN $clientTable ON $projectTable.clientID = $clientTable.id
  INNER JOIN $companyTable ON $clientTable.companyID = $companyTable.id
  WHERE $projectBookingTable.start LIKE '$today %'
  ORDER BY $projectBookingTable.end ASC";

  $result = $conn->query($sql);
  while($result && ($row = $result->fetch_assoc())){
    $start = carryOverAdder_Hours($row['start'], $row['timeToUTC']);
    $end = carryOverAdder_Hours($row['end'], $row['timeToUTC']);

    $appendPattern = str_replace("[NAME]", $row['firstname'] . ' ' . $row['lastname'], $repeat);
    $appendPattern = str_replace("[CLIENT]", $row['clientName'], $appendPattern);
    $appendPattern = str_replace("[PROJECT]", $row['projectName'], $appendPattern);
    $appendPattern = str_replace("[CLIENT]", $row['clientName'], $appendPattern);
    $appendPattern = str_replace("[INFOTEXT]", $row['infoText'], $appendPattern);
    $appendPattern = str_replace("[HOURLY RATE]", $row['hourlyPrice'], $appendPattern);
    $appendPattern = str_replace("[DATE]", substr($start,0,10), $appendPattern);
    $appendPattern = str_replace("[FROM]", substr($start,11,5), $appendPattern);
    $appendPattern = str_replace("[TO]", substr($end,11,5), $appendPattern);

    $html_head .= $appendPattern;
  }
  //glue my html back together
  //'<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><link href="../plugins/homeMenu/template.css" rel="stylesheet" /></head>' .
  $content = $html_head . $html_foot;

  //get mail server options
  $result = $conn->query("SELECT * FROM $mailOptionsTable");
  $row = $result->fetch_assoc();

  $mail->Host       = $row['host'];
  $mail->Username   = $row['username'];
  $mail->Password   =  $row['password'];
  $mail->Port       = $row['port'];
  $mail->SMTPSecure = $row['smtpSecure'];
  $mail->setFrom($row['sender']);


  //add mail recipients
  $result = $conn->query("SELECT * FROM $mailReportsRecipientsTable WHERE reportID = $reportID");
  if(!$result || $result->num_rows <= 0){
    die("Please Define Recipients! ");
  } else {
    echo "<script>window.close();</script>";
  }
  while($result && ($row = $result->fetch_assoc())){
    $mail->addAddress($row['email']);     // Add a recipient, name is optional
  }

  $mail->isHTML(true);                       // Set email format to HTML
  $mail->Subject = $rowContent['name'];
  $mail->Body    = $content;
  $mail->AltBody = "If you can read this, your E-Mail provider does not support HTML." . $content;
  $mail->send();
}
?>
