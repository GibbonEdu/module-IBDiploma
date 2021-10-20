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

require_once '../../gibbon.php';

require_once './moduleFunctions.php';


//Set timezone from session variable
date_default_timezone_set($session->get('timezone'));

$ibDiplomaCASCommitmentID = $_GET['ibDiplomaCASCommitmentID'];
$URL = $gibbon->session->get('absoluteURL') . '/index.php?q=/modules/IB Diploma/cas_approveCommitments.php';

if (isActionAccessible($guid, $connection2, '/modules/IB Diploma/cas_approveCommitments.php') == false) {
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
        if ($ibDiplomaCASCommitmentID == '') {
            //Fail1
            $URL = $URL.'&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                if ($role == 'Coordinator') {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'sequenceStart' => $session->get('gibbonSchoolYearSequenceNumber'), 'sequenceEnd' => $session->get('gibbonSchoolYearSequenceNumber'), 'ibDiplomaCASCommitmentID' => $ibDiplomaCASCommitmentID);
                    $sql = "SELECT ibDiplomaCASCommitment.*, gibbonPerson.gibbonPersonID, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID, ibDiplomaStudentID, surname, preferredName, start.name AS start, end.name AS end, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup, gibbonPersonIDCASAdvisor, casStatusSchool FROM ibDiplomaStudent JOIN gibbonPerson ON (ibDiplomaStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (ibDiplomaStudent.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) LEFT JOIN gibbonSchoolYear AS start ON (start.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDStart) LEFT JOIN gibbonSchoolYear AS end ON (end.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDEnd) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) LEFT JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) JOIN ibDiplomaCASCommitment ON (ibDiplomaCASCommitment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND start.sequenceNumber<=:sequenceStart AND end.sequenceNumber>=:sequenceEnd AND approval='Pending' AND ibDiplomaCASCommitmentID=:ibDiplomaCASCommitmentID";
                } else {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'sequenceStart' => $session->get('gibbonSchoolYearSequenceNumber'), 'sequenceEnd' => $session->get('gibbonSchoolYearSequenceNumber'), 'advisor' => $session->get('gibbonPersonID'), 'ibDiplomaCASCommitmentID' => $ibDiplomaCASCommitmentID);
                    $sql = "SELECT ibDiplomaCASCommitment.*, gibbonPerson.gibbonPersonID, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID, ibDiplomaStudentID, surname, preferredName, start.name AS start, end.name AS end, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup, gibbonPersonIDCASAdvisor, casStatusSchool FROM ibDiplomaStudent JOIN gibbonPerson ON (ibDiplomaStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (ibDiplomaStudent.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) LEFT JOIN gibbonSchoolYear AS start ON (start.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDStart) LEFT JOIN gibbonSchoolYear AS end ON (end.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDEnd) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) LEFT JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) JOIN ibDiplomaCASCommitment ON (ibDiplomaCASCommitment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND start.sequenceNumber<=:sequenceStart AND end.sequenceNumber>=:sequenceEnd AND gibbonPersonIDCASAdvisor=:advisor AND approval='Pending' AND ibDiplomaCASCommitmentID=:ibDiplomaCASCommitmentID";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                //Fail2
                $URL = $URL.'&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() != 1) {
                //Fail 2
                $URL = $URL.'&return=error2';
                header("Location: {$URL}");
            } else {
                //Proceed!
                $job = $_GET['job'];
                if ($job == 'approve') {
                    $job = 'Approved';
                } elseif ($job == 'reject') {
                    $job = 'Not Approved';
                }

                if ($job != 'Approved' and $job != 'Not Approved') {
                    //Fail 3
                    $URL = $URL.'&return=error3';
                    header("Location: {$URL}");
                } else {
                    try {
                        $data = array('job' => $job, 'ibDiplomaCASCommitmentID' => $ibDiplomaCASCommitmentID);
                        $sql = 'UPDATE ibDiplomaCASCommitment SET approval=:job WHERE ibDiplomaCASCommitmentID=:ibDiplomaCASCommitmentID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        //Fail 2
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
}
