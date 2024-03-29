<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

include '../../functions.php';
include '../../config.php';
require_once '../../gibbon.php';

//Module includes
include './moduleFunctions.php';

//New PDO DB connection
try {
    $connection2 = new PDO("mysql:host=$databaseServer;dbname=$databaseName", $databaseUsername, $databasePassword);
    $connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo $e->getMessage();
}

@session_start();

//Set timezone from session variable
date_default_timezone_set($session->get('timezone'));

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/cas_student_interview3.php';

if (isActionAccessible($guid, $connection2, '/modules/IB Diploma/cas_student_interview3.php') == false) {

    //Fail 0
    $URL = $URL.'&return=error0';
    header("Location: {$URL}");
} else {
    if (enroled($guid, $session->get('gibbonPersonID'), $connection2) == false) {
        //Fail 0
        $URL = $URL.'&return=error0';
        header("Location: {$URL}");
    } else {
        //See if interview exists
        try {
            $dataInterview = array('gibbonPersonID' => $session->get('gibbonPersonID'));
            $sqlInterview = 'SELECT ibDiplomaCASInterview.* FROM ibDiplomaCASInterview WHERE gibbonPersonIDInterviewee=:gibbonPersonID';
            $resultInterview = $connection2->prepare($sqlInterview);
            $resultInterview->execute($dataInterview);
        } catch (PDOException $e) {
            //Fail 2
            $URL = $URL.'&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($resultInterview->rowCount() != 1) {
            //Fail 2
            $URL = $URL.'&return=error2';
            header("Location: {$URL}");
        } else {
            $rowInterview = $resultInterview->fetch();

            if (is_null($rowInterview['2_date'])) {
                //Fail 2
                $URL = $URL.'&return=error2';
                header("Location: {$URL}");
            } else {
                for ($i = 1; $i < 9; ++$i) {
                    $outcome[$i] = trim($_POST["outcome$i"]);
                    substr($outcome[$i], strlen($outcome[$i]) - 1);
                    if (substr($outcome[$i], strlen($outcome[$i]) - 1) == ',') {
                        $outcome[$i] = substr($outcome[$i], 0, (strlen($outcome[$i]) - 1));
                    }
                }

                try {
                    $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'outcome1' => $outcome[1], 'outcome2' => $outcome[2], 'outcome3' => $outcome[3], 'outcome4' => $outcome[4], 'outcome5' => $outcome[5], 'outcome6' => $outcome[6], 'outcome7' => $outcome[7], 'outcome8' => $outcome[8]);
                    $sql = 'UPDATE ibDiplomaCASInterview SET 3_outcome1=:outcome1, 3_outcome2=:outcome2, 3_outcome3=:outcome3, 3_outcome4=:outcome4, 3_outcome5=:outcome5, 3_outcome6=:outcome6, 3_outcome7=:outcome7, 3_outcome8=:outcome8 WHERE gibbonPersonIDInterviewee=:gibbonPersonID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    //Fail2
                    $URL = $URL.'&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //Success 0
                $URL = $URL.'&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
