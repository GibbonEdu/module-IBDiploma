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

use Gibbon\Services\Format;

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

$gibbonPersonID = $_POST['gibbonPersonID'];
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/cas_adviseStudents_details.php&gibbonPersonID=$gibbonPersonID&subpage=Interview 2";

if (isActionAccessible($guid, $connection2, '/modules/IB Diploma/cas_adviseStudents_details.php') == false) {

    //Fail 0
    $URL = $URL.'&return=error0';
    header("Location: {$URL}");
} else {
    $role = staffCASRole($guid, $session->get('gibbonPersonID'), $connection2);
    if ($role == false) {
        //Fail 0
        $URL = $URL.'&return=error0';
        header("Location: {$URL}");
    } else {
        //Check if school year specified
        if ($gibbonPersonID == '') {
            //Fail1
            $URL = $URL.'&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                if ($role == 'Coordinator') {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'sequenceStart' => $session->get('gibbonSchoolYearSequenceNumber'), 'sequenceEnd' => $session->get('gibbonSchoolYearSequenceNumber'), 'gibbonPersonID' => $gibbonPersonID);
                    $sql = "SELECT gibbonPerson.gibbonPersonID, ibDiplomaStudentID, surname, preferredName, start.name AS start, end.name AS end, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup, gibbonFormGroup.gibbonFormGroupID, gibbonPersonIDCASAdvisor, casStatusSchool FROM ibDiplomaStudent JOIN gibbonPerson ON (ibDiplomaStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (ibDiplomaStudent.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) LEFT JOIN gibbonSchoolYear AS start ON (start.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDStart) LEFT JOIN gibbonSchoolYear AS end ON (end.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDEnd) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) LEFT JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND start.sequenceNumber<=:sequenceStart AND end.sequenceNumber>=:sequenceEnd AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY formGroup, surname, preferredName";
                } else {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'sequenceStart' => $session->get('gibbonSchoolYearSequenceNumber'), 'sequenceEnd' => $session->get('gibbonSchoolYearSequenceNumber'), 'advisor' => $session->get('gibbonPersonID'), 'gibbonPersonID' => $gibbonPersonID);
                    $sql = "SELECT gibbonPerson.gibbonPersonID, ibDiplomaStudentID, surname, preferredName, start.name AS start, end.name AS end, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup, gibbonFormGroup.gibbonFormGroupID, gibbonPersonIDCASAdvisor, casStatusSchool FROM ibDiplomaStudent JOIN gibbonPerson ON (ibDiplomaStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (ibDiplomaStudent.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) LEFT JOIN gibbonSchoolYear AS start ON (start.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDStart) LEFT JOIN gibbonSchoolYear AS end ON (end.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDEnd) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) LEFT JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND start.sequenceNumber<=:sequenceStart AND end.sequenceNumber>=:sequenceEnd AND gibbonPersonIDCASAdvisor=:advisor AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY formGroup, surname, preferredName";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                //Fail 2
                $URL = $URL.'&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() != 1) {
                //Fail 2
                $URL = $URL.'&return=error2';
                header("Location: {$URL}");
            } else {
                //See if interview exists
                try {
                    $dataInterview = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlInterview = 'SELECT ibDiplomaCASInterview.*, surname, preferredName FROM ibDiplomaCASInterview JOIN gibbonPerson ON (ibDiplomaCASInterview.1_gibbonPersonIDInterviewer=gibbonPerson.gibbonPersonID) WHERE gibbonPersonIDInterviewee=:gibbonPersonID';
                    $resultInterview = $connection2->prepare($sqlInterview);
                    $resultInterview->execute($dataInterview);
                } catch (PDOException $e) {
                    //Fail 2
                    $URL = $URL.'&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($resultInterview->rowCount() != 1) {
                    //Fail 5
                    $URL = $URL.'&return=error5';
                    header("Location: {$URL}");
                } else {
                    $rowInterview = $resultInterview->fetch();

                    //Set outcomes
                    for ($i = 1; $i < 9; ++$i) {
                        $outcome[$i] = $_POST["outcome$i"];
                    }

                    $partialFail = false;

                    //Update status
                    $casStatusSchool = $_POST['casStatusSchool'];
                    if ($casStatusSchool == '') {
                        $partialFail = true;
                    } else {

                        try {
                            $data = array('casStatusSchool' => $casStatusSchool, 'gibbonPersonID' => $gibbonPersonID);
                            $sql = 'UPDATE ibDiplomaStudent SET casStatusSchool=:casStatusSchool WHERE gibbonPersonID=:gibbonPersonID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                    }

                    //Get interview variables
                    $date = Format::dateConvert($_POST['date']);
                    $notes = $_POST['notes'];
                    if (is_null($rowInterview['2_gibbonPersonIDInterviewer'])) {
                        $gibbonPersonIDInterviewer = $session->get('gibbonPersonID');
                    } else {
                        $gibbonPersonIDInterviewer = $rowInterview['2_gibbonPersonIDInterviewer'];
                    }

                    if ($date == '') {
                        //Fail1
                        $URL = $URL.'&return=error1';
                        header("Location: {$URL}");
                    } else {
                        try {
                            //If exists, update
                            $data = array('notes' => $notes, 'date' => $date, 'gibbonPersonID' => $gibbonPersonID, 'outcome1' => $outcome[1], 'outcome2' => $outcome[2], 'outcome3' => $outcome[3], 'outcome4' => $outcome[4], 'outcome5' => $outcome[5], 'outcome6' => $outcome[6], 'outcome7' => $outcome[7], 'outcome8' => $outcome[8]);
                            $sql = "UPDATE ibDiplomaCASInterview SET 2_notes=:notes, 2_date=:date, 2_gibbonPersonIDInterviewer=:gibbonPersonID, 2_outcome1=:outcome1, 2_outcome2=:outcome2, 2_outcome3=:outcome3, 2_outcome4=:outcome4, 2_outcome5=:outcome5, 2_outcome6=:outcome6, 2_outcome7=:outcome7, 2_outcome8=:outcome8 WHERE gibbonPersonIDInterviewee=$gibbonPersonID";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);

                        } catch (PDOException $e) {
                            //Fail 2
                            $URL = $URL.'&return=error2';
                            header("Location: {$URL}");
                            exit();
                        }

                        //Return!
                        if ($partialFail == true) {
                            //Fail 4
                            $URL = $URL.'&return=error4';
                            header("Location: {$URL}");
                        } else {
                            //Success 0
                            $URL = $URL.'&return=success0';
                            header("Location: {$URL}");
                        }
                    }
                }
            }
        }
    }
}
