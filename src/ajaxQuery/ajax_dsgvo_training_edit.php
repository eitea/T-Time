<?php 
session_start();
if (!isset($_REQUEST["trainingID"])){
    echo "error";
    die();
}
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "utilities.php";

$trainingID = $_REQUEST["trainingID"];
$row = $conn->query("SELECT * FROM dsgvo_training WHERE id = $trainingID")->fetch_assoc();
$name = $row["name"];
$version = $row["version"];
$companyID = $row["companyID"];
$onLogin = $row["onLogin"];
$allowOverwrite = $row["allowOverwrite"];
$random = $row["random"];
$moduleID = $row["moduleID"];
$answerEveryNDays = $row["answerEveryNDays"];

$userArray = array();
$teamArray = array();
$companyArray = array();
$result = $conn->query("SELECT userID id FROM dsgvo_training_user_relations WHERE trainingID = $trainingID");
while($result && ($row = $result->fetch_assoc())){
    $userArray[] = $row["id"];
}
$result = $conn->query("SELECT teamID id FROM dsgvo_training_team_relations WHERE trainingID = $trainingID");
while($result && ($row = $result->fetch_assoc())){
    $teamArray[] = $row["id"];
}
$result = $conn->query("SELECT companyID id FROM dsgvo_training_company_relations WHERE trainingID = $trainingID");
showError($conn->error);
while($result && ($row = $result->fetch_assoc())){
    $companyArray[] = $row["id"];
}
?>
 <form method="POST">
 <div class="modal fade">
      <div class="modal-dialog modal-content modal-md">
        <div class="modal-header"><i class="fa fa-cube"></i> <?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS']['EDIT_MODULE'] ?></div>
        <div class="modal-body">
            <label>Name*</label>
            <input type="text" class="form-control" name="name" placeholder="Name des Sets" value="<?php echo $name ?>"/>
            <label>Set*</label>
            <select class="js-example-basic-single" name="module" required>
                <?php 
                $result = $conn->query("SELECT * FROM dsgvo_training_modules");
                while ($result && ($row = $result->fetch_assoc())) {
                    $name = $row["name"];
                    $id = $row["id"];
                    $selected = ($id == $moduleID)?"selected":"";
                    echo "<option $selected value='$id'>$name</option>";
                }
                ?>
            </select>
            <label><?php echo $lang["EMPLOYEE"]; ?>/Team/<?php echo $lang['COMPANY'] ?>*</label>
                <select class="select2-team-icons required-field" name="employees[]" multiple="multiple">
                <?php
                $result = $conn->query("SELECT UserData.id id, firstname, lastname FROM relationship_company_client INNER JOIN UserData on UserData.id = relationship_company_client.userID WHERE companyID = $companyID GROUP BY UserData.id");
                while($result && ($row = $result->fetch_assoc())){
                    $selected = '';
                    if(in_array($row['id'], $userArray)){
                        $selected = 'selected';
                    }
                    echo '<option title="Benutzer" value="user;'.$row['id'].'" data-icon="user" '.$selected.' >'.$row['firstname'].' '.$row['lastname'].'</option>';
                }
                $result = $conn->query("SELECT id, name, isDepartment FROM $teamTable");
                while ($result && ($row = $result->fetch_assoc())) {
                    $selected = '';
                    if(in_array($row['id'], $teamArray)){
                        $selected = 'selected';
                    }
                    $icon = $row["isDepartment"] === 'TRUE' ? "share-alt" : "group";
                    $type = $row["isDepartment"] === 'TRUE' ? "Abteilung" : "Team";                    
                    echo '<option title="'.$type.'" value="team;'.$row['id'].'" data-icon="'.$icon.'" '.$selected.' >'.$row['name'].'</option>';
                }
                $result = $conn->query("SELECT id, name FROM $companyTable");
                while ($row = $result->fetch_assoc()) {
                    $selected = '';
                    if(in_array($row['id'], $companyArray)){
                        $selected = 'selected';
                    }
                    echo '<option title="Mandant" value="company;'.$row['id'].'" data-icon="building" '.$selected.' >'.$row['name'].'</option>';
                }
                ?>
            </select><br>
            <label><?php echo $lang['TRAINING_SETTINGS']['ANSWER_TYPE'] ?></label><br/>
            <label><input type="radio" name="onLogin" value="TRUE" <?php if($onLogin == 'TRUE') echo "checked" ?> /><?php echo $lang['TRAINING_SETTINGS']['ANSWER_LOGIN'] ?></label><br/>
            <label><input type="radio" name="onLogin" value="FALSE" <?php if($onLogin == 'FALSE') echo "checked" ?> /><?php echo $lang['TRAINING_SETTINGS']['ANSWER_OPTIONAL'] ?></label>
            <br/><label><?php echo $lang['TRAINING_SETTINGS']['OVERWRITE_ANSWERS'] ?></label><br/>
            <label><input type="radio" name="allowOverwrite" value="TRUE" <?php if($allowOverwrite == 'TRUE') echo "checked" ?> /><?php echo $lang['TRAINING_SETTINGS']['OVERWRITE'] ?></label><br/>
            <label><input type="radio" name="allowOverwrite" value="FALSE" <?php if($allowOverwrite == 'FALSE') echo "checked" ?> /><?php echo $lang['TRAINING_SETTINGS']['NO_OVERWRITE'] ?></label>
            <br/><label><?php echo $lang['TRAINING_SETTINGS']['ANSWER_ORDER'] ?></label><br/>
            <label><input type="radio" name="random" value="TRUE" <?php if($random == 'TRUE') echo "checked" ?> /><?php echo $lang['TRAINING_SETTINGS']['RANDOM_ORDER'] ?></label><br/>
            <label><input type="radio" name="random" value="FALSE" <?php if($random == 'FALSE') echo "checked" ?> /><?php echo $lang['TRAINING_SETTINGS']['PRESERVE_ORDER'] ?></label>
            <br /><label><?php echo $lang['TRAINING_SETTINGS']['REPEAT_DAYS'] ?></label>
            <input type="number" title="<?php echo $lang['TRAINING_SETTINGS']['REPEAT_DAYS_NOTE'] ?>" min="1" max="365" class="form-control" name="answerEveryNDays" value="<?php echo $answerEveryNDays; ?>" <?php if($onLogin == 'FALSE' || $allowOverwrite == 'FALSE') echo "disabled" ?> />
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
          <button type="submit" class="btn btn-warning" name="editTraining" value="<?php echo $trainingID; ?>"><?php echo $lang['SAVE'] ?></button>
        </div>
      </div>
    </div>
</form>
<script>
$("input[name=onLogin][value='TRUE']").change(function(event){
    if($("input[name=allowOverwrite][value='TRUE']").is(':checked'))
        $("input[name=answerEveryNDays]").attr("disabled",false)
})
$("input[name=onLogin][value='FALSE']").change(function(event){
    $("input[name=answerEveryNDays]").attr("disabled",true)
})
$("input[name=allowOverwrite][value='TRUE']").change(function(event){
    if($("input[name=onLogin][value='TRUE']").is(':checked'))
        $("input[name=answerEveryNDays]").attr("disabled",false)
})
$("input[name=allowOverwrite][value='FALSE']").change(function(event){
    $("input[name=answerEveryNDays]").attr("disabled",true)
})
</script>