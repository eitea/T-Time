<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToCore($userID);?>
<!-- BODY -->
<title>TODOs</title>
<div class="page-header">
  <h3><?php echo $lang['FOUNDERRORS']; ?></h3>
</div>
  <?php

  if(isset($_POST['autoCorrect']) && isset($_POST['autoCorrects'])){
    foreach($_POST['autoCorrects'] as $indexIM){
      $sql = "SELECT $logTable.*, $userTable.hoursOfRest,$userTable.pauseAfterHours FROM $logTable,$userTable WHERE indexIM = $indexIM AND $logTable.userID = $userTable.id";
      $result = $conn->query($sql);
      $row = $result->fetch_assoc();

      $adjustedTime = carryOverAdder_Hours($row['time'], floor($row['expectedHours']));
      $adjustedTime = carryOverAdder_Minutes($adjustedTime, (($row['expectedHours'] * 60) % 60));

      if($row['expectedHours'] > $row['pauseAfterHours']){ //we dont have 2 check to see if we have to create a project lunchbreak, that gets validated by the illegal lunchbreak todo anyways.
        $adjustedTime = carryOverAdder_Minutes($adjustedTime, ($row['hoursOfRest'] * 60));
      }

      $sql = "UPDATE $logTable SET timeEnd = '$adjustedTime' WHERE indexIM =" .$row['indexIM'];
      $conn->query($sql);
      echo mysqli_error($conn);
    }
  }
  if(isset($_POST['saveNewBreaks']) && isset($_POST['lunchbreaks'])){
    for($i=0; $i < count($_POST['lunchbreaks']); $i++){
      if($_POST['lunchbreaks'][$i] - $_POST['oldBreakValue'][$i] <= 0 || $_POST['lunchbreaks'][$i] == 0){
        echo '<div class="alert alert-danger fade in">';
        echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>Error: </strong>Invalid setting of new lunchbreak, please try again.';
        echo '</div>';
        break;
      }
      $breakTime = ($_POST['lunchbreaks'][$i] - $_POST['oldBreakValue'][$i]) * 60;
      $indexIM = $_POST['lunchbreakIndeces'][$i];
      $date = substr($_POST['lunchbreakDate'][$i],0,10);

      $sql = "INSERT INTO $projectBookingTable(timestampID, start, end, infoText, booked)
      VALUES($indexIM, '$date 08:00:00', DATE_ADD('$date 08:00:00', INTERVAL $breakTime MINUTE), 'Repaired lunchbreak', 'FALSE')";
      $conn->query($sql);
      echo mysqli_error($conn);

      $breakTime = test_input($_POST['lunchbreaks'][$i]);
      $sql = "UPDATE $logTable SET breakCredit = (breakCredit + $breakTime) WHERE indexIM = $indexIM";
      $conn->query($sql);
      echo mysqli_error($conn);
    }
  }

  if(isset($_POST['deleteGemini']) && !empty($_POST['geminiIndeces'])){
    foreach(array_unique($_POST['geminiIndeces']) as $indexIM){
      $sql = "DELETE FROM $logTable WHERE indexIM = $indexIM";
      $conn->query($sql);
      echo mysqli_error($conn);
    }
  }
  ?>


  <?php
  $sql ="SELECT * FROM $userRequests WHERE status = '0'";
  $result = $conn->query($sql);
  if($result && $result->num_rows > 0):
    ?>
    <h4> <?php echo $lang['UNANSWERED_REQUESTS']; ?>: </h4>

    <?php
    echo $result->num_rows . " Urlaubsanfrage: ";
    echo "<a href=allowVacations.php > Beantworten</a><br><hr><br>";
  endif;
  ?>

  <!-- -------------------------------------------------------------------------->
  <form method=post>
  <?php
  $sql = "SELECT * FROM $logTable INNER JOIN $userTable ON $logTable.userID = $userTable.id
  WHERE timeEnd != '0000-00-00 00:00:00' AND TIMESTAMPDIFF(HOUR, time, timeEnd) > pauseAfterHours AND breakCredit < hoursOfRest AND status = '0'";

  $result = $conn->query($sql);
  if($result && $result->num_rows > 0):
    ?>
    <h4> <?php echo $lang['ILLEGAL_LUNCHBREAK']; ?>:</h4>
    <div class="h4 text-right">
      <a role="button" data-toggle="collapse" href="#illegal_lunchbreak_info" aria-expanded="false" aria-controls="illegal_lunchbreak_info">
        <i class="fa fa-info-circle"></i>
      </a>
    </div>
    <div class="collapse" id="illegal_lunchbreak_info">
      <div class="well">
        Mittagspause stimmt nicht mit den festgelegten Parametern überein: Die für den Benutzer definierte Pause, wurde nach der für den Benutzer definierte Zeit nicht vollständig konsumiert.
      </div>
    </div>

    <table class="table table-hover">
      <th>Name</th>
      <th><?php echo $lang['TIME']; ?></th>
      <th><?php echo $lang['HOURS']; ?></th>
      <th><?php echo $lang['LUNCHBREAK']; ?></th>
      <th></th>
      <tbody>
        <?php
        while($row = $result->fetch_assoc()){
          echo '<tr>';
          echo '<td>'. $row['firstname'] .' ' . $row['lastname'] .'</td>';
          echo '<td>'. carryOverAdder_Hours($row['time'], $row['timeToUTC']) .' - ' . carryOverAdder_Hours($row['timeEnd'], $row['timeToUTC']) .'</td>';
          echo '<td>'. number_format(timeDiff_Hours($row['time'], $row['timeEnd']), 2, '.', '') .'</td>';
          echo '<td><input type="number" step="any" class="form-control" style="width:100px" name="lunchbreaks[]" value="'.$row['breakCredit'].'" ></td>';
          echo '<td>
          <input type=text style=display:none name="lunchbreakIndeces[]" value='.$row['indexIM'].' >
          <input type=text style=display:none name="oldBreakValue[]" value='.$row['breakCredit'].' >
          <input type=text style=display:none name="lunchbreakDate[]" value="'.$row['time'].'" >
          </td>';
          echo '</tr>';
        }
        ?>
      </tbody>
    </table>
    <br>
    <button type='submit' class="btn btn-warning" name='saveNewBreaks' >Save</button>
    <br><hr><br>
  <?php endif;?>
  <!-- -------------------------------------------------------------------------->

  <?php
  $sql = "SELECT $userTable.firstname, $userTable.lastname, $logTable.*
  FROM $logTable
  INNER JOIN $userTable ON $userTable.id = $logTable.userID
  WHERE TIMESTAMPDIFF(HOUR, time, timeEnd) > 12 OR TIMESTAMPDIFF(HOUR, time, timeEnd) < 0";

  $result = $conn->query($sql);
  if($result && $result->num_rows > 0):
  ?>

    <h4><?php echo $lang['ILLEGAL_TIMESTAMPS']; ?>:</h4>
    <div class="h4 text-right">
      <a role="button" data-toggle="collapse" href="#illegal_timestamp_info" aria-expanded="false" aria-controls="illegal_lunchbreak_info">
        <i class="fa fa-info-circle"></i>
      </a>
    </div>
    <div class="collapse" id="illegal_timestamp_info">
      <div class="well">
        Die Differenz der Anfangs- und Endzeit ergibt weniger als 0, oder über 12 Stunden. <br>
        Die Autokorrektur passt die ausgewählten Zeitstempel einfach den erwarteten Stunden inkl. Mittagspause an. <br>
      </div>
    </div>
    <table id='illTS' class="table table-hover">
      <th>User</th>
      <th>Status</th>
      <th><?php echo $lang['TIME']; ?></th>
      <th><?php echo $lang['HOURS']; ?></th>
      <th>Autocorrect</th>
      <tbody>
        <?php
        while($row = $result->fetch_assoc()){
          echo '<tr>';
          echo '<td>'. $row['firstname'] .' ' . $row['lastname'] .'</td>';
          echo '<td>'. $lang_activityToString[$row['status']] .'</td>';
          echo '<td>'. carryOverAdder_Hours($row['time'], $row['timeToUTC']) .' - ' . carryOverAdder_Hours($row['timeEnd'], $row['timeToUTC']) .'</td>';
          echo '<td>'. number_format(timeDiff_Hours($row['time'], $row['timeEnd']), 2, '.', '') .'</td>';
          echo '<td><input type=checkbox name="autoCorrects[]" value='.$row['indexIM'].' ></td>';
          echo '</tr>';
        }

        ?>
      </tbody>
    </table>
    <br>
    <button type='submit' class="btn btn-warning" name='autoCorrect'>Autocorrect</button>
    <br><hr><br>
    <?php endif;  ?>

  <!-- -------------------------------------------------------------------------->

  <?php
  $sql = "SELECT * FROM $logTable l1, $userTable WHERE l1.userID = $userTable.id
  AND EXISTS(SELECT * FROM $logTable l2 WHERE DATE(l1.time) = DATE(l2.time) AND l1.userID = l2.userID AND l1.indexIM != l2.indexIM) ORDER BY l1.time DESC";

  $result = $conn->query($sql);
  if($result && $result->num_rows > 0):
    ?>
    <h4><?php echo $lang['ILLEGAL_TIMESTAMPS']; ?>: Gemini</h4>
    <div class="h4 text-right">
      <a role="button" data-toggle="collapse" href="#illegal_gemini_info" aria-expanded="false" aria-controls="illegal_lunchbreak_info">
        <i class="fa fa-info-circle"></i>
      </a>
    </div>
    <div class="collapse" id="illegal_gemini_info">
      <div class="well">
        Es existiert mehr als nur ein Zeitstempel für einen Benutzer an nur einem Tag.<br>
        Ein Benutzer darf allerdings pro Tag nur eine Art von Zeitstempel besitzen. <br>
        Bitte entscheiden Sie, welcher der beiden Zeitstempel gelöscht werden soll. Sie können auch beide Stempel löschen. <br>
        (Bemerkung: ZA ist kein Stempel)
      </div>
    </div>
    <table id='dubble' class="table table-hover">
      <th>User</th>
      <th width=40%><?php echo $lang['TIMESTAMPS']; ?> 1</th>
      <th width=40%><?php echo $lang['TIMESTAMPS']; ?> 2</th>
      <tbody>
        <?php
        $rowDP = $result->fetch_assoc();
        $rowDP2 = $result->fetch_assoc();
        $uneven = $rowDP;

        while(true) {
          //uneven row handling
          $row = $rowDP;
          if($rowDP['userID'] != $rowDP2['userID']){
            $row2 = $uneven;
          } else {
            $row2 = $rowDP2;
          }
          echo '<tr>';
          echo '<td>'. $row['firstname'] .' ' . $row['lastname'] .'</td>';

          echo '<td><div class="checkbox">';
          echo '<input type=checkbox name="geminiIndeces[]" value="'.$row['indexIM'].'" />';
          echo $lang_activityToString[$row['status']] .' - '.$row['indexIM']. ' - ';
          echo carryOverAdder_Hours($row['time'], $row['timeToUTC']) .' - ' . carryOverAdder_Hours($row['timeEnd'], $row['timeToUTC']);
          echo '</div></td>';

          echo '<td><div class="checkbox">';
          echo '<input type=checkbox name="geminiIndeces[]" value="'.$row2['indexIM'].'" />';
          echo $lang_activityToString[$row2['status']] .' - '.$row2['indexIM']. ' - ';
          echo carryOverAdder_Hours($row2['time'], $row2['timeToUTC']) .' - '. carryOverAdder_Hours($row2['timeEnd'], $row2['timeToUTC']);
          echo '</div></td>';
          echo '</tr>';
          //uneven incrementation
          if($rowDP['userID'] == $rowDP2['userID']){
            $uneven = $rowDP;
            if(!($rowDP = $result->fetch_assoc()) || !($rowDP2 = $result->fetch_assoc())){
              break;
            }
          } else {
            $rowDP = $result->fetch_assoc();
          }
        }

        ?>
      </tbody>
    </table>
    <br>
    <button type='submit' class="btn btn-warning" name='deleteGemini'><?php echo $lang['DELETE']; ?></button>
    <br><hr><br>
    <?php
  endif;
  echo mysqli_error($conn);
  ?>
  <!-- -------------------------------------------------------------------------->
</form>

<!-- /BODY -->
<?php include 'footer.php'; ?>
