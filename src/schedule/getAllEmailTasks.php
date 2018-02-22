<?php
require_once dirname(__DIR__)."/connection.php";
require_once dirname(__DIR__)."/utilities.php";
require_once dirname(dirname(__DIR__)).'/plugins/imap/autoload.php';

$result = $conn->query("SELECT * FROM emailprojects");
if($result){
    while($row = $result->fetch_assoc()){
        $security = empty($row['security']) ? '' : '/'.$row['security'];
        $mailbox = '{'.$row['server'] .':'. $row['port']. '/'.$row['service'] . $security.'/novalidate-cert}'.'INBOX';

        $conn->query("INSERT INTO emailprojectlogs VALUES(null,CURRENT_TIMESTAMP,'$mailbox')");
        $imap = new PhpImap\Mailbox($mailbox, $row['username'], $row['password'], __DIR__ ); //modified so nothing will be saved to disk
        $mailsIds = $imap->searchMailbox('ALL');

        $result = $conn->query("SELECT * FROM taskemailrules WHERE emailaccount = ".$row['id']); echo $conn->error;
        while($rule = $result->fetch_assoc()){
            foreach($mailsIds as $mail_number){
                $mail = $imap->getMail($mail_number);
                if($subject = strstr($mail->subject, $rule['identifier'])){
                    echo $subject .' - found<br>';
                    $id = uniqid();
                    $null = null;
                    $name = str_replace($rule['identifier'],"",$subject);
                    $description = convToUTF8($mail->textHtml);

                    $attachments = $mail->getAttachments();
                    foreach($attachments as $attach){ //easy custom rawData
                        $description = str_replace("cid:".$attach->contentId, "data:image/jpeg;base64,".base64_encode($attach->rawData), $description);
                    }

                    $company = $rule['company'];
                    $client = $rule['client'];
                    $project = $rule['clientproject'];
                    $color = $rule['color'];
                    $start = date('Y-m-d');
                    $end = '';
                    $status = $rule['status'];
                    $priority = $rule['priority']; //1-5
                    $parent = $rule['parent']; //dynamproject id
                    $owner = $rule['owner'];
                    $percentage = 0;
                    $series = null;
                    $projectleader = $rule['leader'];
                    // PROJECT
                    $stmt = $conn->prepare("INSERT INTO dynamicprojects(projectid, projectname, projectdescription, companyid, clientid, clientprojectid, projectcolor, projectstart, projectend, projectstatus,
                        projectpriority, projectparent, projectowner, projectnextdate, projectseries, projectpercentage, projectleader) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssbiiissssisisbii", $id, $name, $null, $company, $client, $project, $color, $start, $end, $status, $priority, $parent, $owner, $nextDate, $series, $percentage, $projectleader);
                    $stmt->send_long_data(2, $description);
                    $stmt->execute();
                    if(!$stmt->error){
                        $stmt->close();
                        //EMPLOYEES TEAMS
                        $stmt_emp = $conn->prepare("INSERT INTO dynamicprojectsemployees (projectid, userid, position) VALUES ('$id', ?, ?)"); echo $conn->error;
                        $stmt_emp->bind_param("is", $employee, $position);
                        $stmt_team = $conn->prepare("INSERT INTO dynamicprojectsteams (projectid, teamid) VALUES ('$id', ?)"); echo $conn->error;
                        $stmt_team->bind_param("i", $team);

                        $position = 'normal';
                        $employees = explode(",", $rule['employees']); //team;10, user;1, user;5
                        foreach($employees as $entry){
                            $entries = explode(";", $entry);
                            if($entries[0] == 'user'){
                                $employee = $entries[1];
                                $stmt_emp->execute();
                            } elseif($entries[0] == 'team'){
                                $team = $entries[1];
                                $stmt_team->execute();
                            }
                        }
                        if(!empty($rule['optionalemployees'])){
                            $position = 'optional';
                            $employees = explode(",",$rule['optionalemployees']);
                            foreach ($employees as $entry) {
                                $entries = explode(";", $entry);
                                if($entries[0] == 'user'){
                                    $employee = $entries[1];
                                    $stmt_emp->execute();
                                }
                            }
                        }
                        echo $stmt_emp->error;
                        echo $stmt_team->error;
                        $stmt_emp->close();
                        $stmt_team->close();

                        //$imap->deleteMail($mail_number);
                    } else {
                        echo $stmt->error;
                    }
                }
            }
        }
    }
} else {
    $conn->query("INSERT INTO emailprojectlogs VALUES(null,CURRENT_TIMESTAMP,'ERROR')");
}
?>
