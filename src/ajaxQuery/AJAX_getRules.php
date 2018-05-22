<?php
require dirname(__DIR__)."/connection.php";

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        if(!empty($_POST['id'])){
            $id = intval($_POST['id']);
            $result = $conn->query("SELECT * FROM taskemailrules WHERE emailaccount = $id");
            if($result){
                $rules = array();
                $i = 0;
                while($row = $result->fetch_assoc()){
                    array_push($rules,$row);
                    $company = $conn->query("SELECT name FROM companyData WHERE id = ".$rules[$i]['company']);
                    if($company){
                        $rules[$i]['company'] = $company->fetch_assoc()['name'];
                    }

                    $company = $conn->query("SELECT name FROM clientData WHERE id = ".$rules[$i]['client']);
                    if($company){
                        $rules[$i]['client'] = $company->fetch_assoc()['name'];
                    }

                    $company = $conn->query("SELECT projectname FROM dynamicprojects WHERE projectid = '".$rules[$i]['parent']."'");
                    if($company){
                        $rules[$i]['parent'] = $company->fetch_assoc()['projectname'];
                    }

                    $company = $conn->query("SELECT CONCAT(firstname,' ',lastname) AS name FROM UserData WHERE id = ".$rules[$i]['owner']);
                    if($company){
                        $rules[$i]['owner'] = $company->fetch_assoc()['name'];
                    }

                    $company = $conn->query("SELECT CONCAT(firstname,' ',lastname) AS name FROM UserData WHERE id = ".$rules[$i]['leader']);
                    if($company){
                        $rules[$i]['leader'] = $company->fetch_assoc()['name'];
                    }

                    $employees = explode(",",$rules[$i]['employees']);
                        for($y = 0;$y<count($employees)-1;$y++){
                            if(strstr($employees[$y],'user;')){
                                $employees[$y] = strstr($employees[$y],'user;');
                                $employees[$y] = ltrim($employees[$y],'user;');
                                $company = $conn->query("SELECT CONCAT(firstname,' ',lastname) AS name FROM UserData WHERE id = ".$employees[$y]);
                                if($company){
                                    $employees[$y] = $company->fetch_assoc()['name'];
                                }
                            }elseif(strstr($employees[$y],'team;')){
                                $employees[$y] = strstr($employees[$y],'team;');
                                $employees[$y] = ltrim($employees[$y],'team;');
                                $company = $conn->query("SELECT name FROM teamData WHERE id = ".$employees[$y]);
                                if($company){
                                    $employees[$y] = $company->fetch_assoc()['name'];
                                }
                            }
                        }

                    $rules[$i]['employees'] = rtrim(implode(", ",$employees),", ");


                    if(!empty($rules[$i]['optionalemployees'])){
                        $employees = explode(",",$rules[$i]['optionalemployees']);
                        for($y = 0;$y<count($employees)-1;$y++){
                            if($employees[$y] = strstr($employees[$y],';')) $employees[$y] = ltrim($employees[$y],';');
                            $company = $conn->query("SELECT CONCAT(firstname,' ',lastname) AS name FROM UserData WHERE id = ".$employees[$y]);
                            if($company){
                                $employees[$y] = $company->fetch_assoc()['name'];
                            }
                        }
                        $rules[$i]['optionalemployees'] = rtrim(implode(", ",$employees),", ");
                    }
                    $i++;
                }

                echo json_encode($rules);
            }else{
                return;
            }
        }else{
            return;
        }
    }
    return;
?>
