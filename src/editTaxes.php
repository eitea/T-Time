<?php require "header.php"; enableToCore($userID); ?>
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['save'])){
    for($i = 0; $i < count($_POST['ids']); $i++){
      $x = $_POST['ids'][$i];
      $perc = $_POST['percentage'][$i];
      $desc = $_POST['description'][$i];
      if($perc === ""){
        $perc = "NULL";
      }
      if($desc){
        $conn->query("UPDATE taxRates SET percentage = $perc, description = '$desc'  WHERE id = $x");
      } else {
        $conn->query("UPDATE taxRates SET percentage = $perc  WHERE id = $x");
      }
    }
  }
}
?>

<div class="page-header">
  <h3><?php echo $lang['TAX_RATES']; ?></h3>
</div>

<form method="POST">
<table class="table table-hover">
  <thead>
    <th>Name</th>
    <th></th>
    <th></th>
  </thead>
  <tbody>
    <?php
    //TODO: make them editable and deactivateable (NULL as value) BUT NOT DELETABLE
    $result = $conn->query("SELECT * FROM taxRates");
    while ($result && ($row = $result->fetch_assoc())) {
      if($row['percentage']  || $row['percentage'] === "0"){
        $perc = $row['percentage'];
      } else {
        $perc = '';
      }
      echo '<tr>';
      echo '<td><input type="text" name="description[]" value="'.$row['description'].'" class="form-control" /></td>';
      echo '<td style="text-align:right"><input type="number" name="percentage[]" value="'.$perc.'" class="form-control" style="max-width:250px; display:inline;text-align:right;" /> %</td>';
      echo '<td><input type="hidden" name="ids[]" value="'.$row['id'].'" /></td>';
      echo '</tr>';
    }
     ?>
  </tbody>
</table>
<button type="submit" name="save" class="btn btn-warning"><?php echo $lang['SAVE']; ?></button>
</form>
<?php include "footer.php"; ?>