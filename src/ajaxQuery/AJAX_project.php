
<?php

require "../connection.php";

$clientID = intval($_POST['clientID']);
$projectID= intval($_POST['projectID']);


$query = "SELECT * FROM $projectTable WHERE clientID = $clientID";
$result = mysqli_query($conn, $query);
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $selected = "";
    if($projectID == $row['id']){
      $selected = "selected";
    }
    echo "<option $selected name='prj' value='".$row['id']."'>". $row['name']."</option>";
  }
}
?>