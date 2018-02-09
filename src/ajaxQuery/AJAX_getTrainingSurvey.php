<?php
session_start();
$userID = $_SESSION['userid'];
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";

function strip_questions($html){ // this will be the html type for the survey
    $regexp = '/\{.*?\}/';
    return preg_replace($regexp, "", $html);
}

function parse_questions($html){ // this will return an array of questions
    $questionRegex = '/\{.*?\}/';
    $htmlRegex = '/<\/*\w+\/*>/';
    $html = preg_replace($htmlRegex,"",$html); // strip all html tags
    preg_match($questionRegex,$html,$matches);
    // I only parse the first question for now
    if(sizeof($matches)==0) return array();
    $question = $matches[0]; // eg "{[-]wrong answer[+]right answer}"
    $answerRegex = '/\[([+-])\]([^\[\}]+)/';
    preg_match_all($answerRegex,$question,$matches);
    if(sizeof($matches)==0) return array();
    $ret_array = array();
    foreach ($matches[2] as $key => $value) {
        $ret_array[] = array("value"=>$key,"text"=>$value);
    }
    return $ret_array;
}

$result = $conn->query( // this gets all trainings the user can complete
    "SELECT tur.trainingID id, tr.name 
     FROM dsgvo_training_user_relations tur 
     INNER JOIN dsgvo_training tr 
     ON tr.id = tur.trainingID 
     WHERE tur.userID = $userID
     UNION
     SELECT ttr.trainingID id, tr.name 
     FROM dsgvo_training_team_relations ttr 
     INNER JOIN teamRelationshipData trd 
     ON trd.teamID = ttr.teamID  
     INNER JOIN dsgvo_training tr 
     ON tr.id = ttr.trainingID 
     WHERE trd.userID = $userID"
);
$trainingArray = array(); // those are the survey pages
while ($row = $result->fetch_assoc()){
    $questionArray = array();
    $trainingID = $row["id"];
    $result_question = $conn->query(
        "SELECT * FROM dsgvo_training_questions tq 
         WHERE tq.trainingID = $trainingID AND 
         NOT EXISTS (
             SELECT userID 
             FROM dsgvo_training_completed_questions 
             WHERE questionID = tq.id AND userID = $userID
         )"
    ); // only select not completed questions
    while($row_question = $result_question->fetch_assoc()){
        $questionArray[] = array(
            "type"=>"html",
            "name"=>"question",
            "html"=>strip_questions($row_question["text"])
        );
        $questionArray[] = array(
            "type"=>"radiogroup",
            "name"=>$row_question["id"],
            "title"=>"Welche dieser Antworten ist richtig?",
            "isRequired"=>true,
            "colCount"=>1,
            "choicesOrder"=>"random",
            "choices"=>parse_questions($row_question["text"])
        );
    }
    $trainingArray[] = array(
        "name"=>$row["name"],
        "title"=>$row["name"],
        "elements"=>$questionArray
    );
}
?>
    <script src='../plugins/node_modules/survey-jquery/survey.jquery.min.js'></script>

    <div class="modal fade">
        <div class="modal-dialog modal-content modal-md">
            <div class="modal-header">Bitte beantworten Sie folgende Fragen</div>
            <div class="modal-body">
                <div id="surveyElement"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
            </div>
        </div>
    </div>

    <script>
        Survey.Survey.cssType = "bootstrap";
        Survey.defaultBootstrapCss.navigationButton = "btn btn-warning";
        var json = <?php echo json_encode(array(
            "pages"=> $trainingArray,
            "showProgressBar"=>"top"    
        )) ?>;
        window.survey = new Survey.Model(json);
        survey
            .onComplete
            .add(function (result) {
                $.ajax({
                    url: 'ajaxQuery/AJAX_validateTrainingSurvey.php',
                    data: { result: JSON.stringify(result.data) },
                    type: 'post',
                    success: function (resp) {
                        alert(resp);
                    },
                    error: function (resp) { 
                        alert(resp);
                    }
                });
            });
        $("#surveyElement").Survey({ model: survey });
    </script>