<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <link rel="stylesheet" type="text/css" href="../bootstrap/css/bootstrap.min.css">

  <link rel='stylesheet' href='../plugins/fullcalendar/fullcalendar.css' />
  <script src='../plugins/fullcalendar/lib/jquery.min.js'></script>
  <script src='../plugins/fullcalendar/lib/moment.min.js'></script>
  <script src='../plugins/fullcalendar/fullcalendar.js'></script>

</head>
<body>
<?php
require 'connection.php';
$sql = "SELECT * FROM $userRequests INNER JOIN $userTable ON $userTable.id = $userRequests.userID WHERE $userRequests.status = '2'";
$result = $conn->query($sql);
$vacs = '';
if($result && $result->num_rows > 0){
  while($row = $result->fetch_assoc()){
    $title = 'Vacation:' . $row['firstname'] . ' ' . $row['lastname'];
    $start = substr($row['fromDate'], 0, 16);
    $end = substr($row['toDate'], 0, 16);

    $vacs .= "{ title: '$title', start: '$start', end: '$end'},";
  }
}
?>


  <div id='calendar' style='height: 850px;'></div>
  <script>
  $(document).ready(function() {
    $('#calendar').fullCalendar({
        height: 600,
        header: {
          left: 'prev,next today',
				      center: 'title',
          right: 'month,listWeek,agendaWeek'
        },
        defaultView: 'month',
        events: [<?php echo $vacs; ?>]
    })
  });
  </script>
</body>
