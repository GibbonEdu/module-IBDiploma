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

$ibDiplomaCASCommitmentID = $_POST['ibDiplomaCASCommitmentID'];
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/cas_student_myCommitments_edit.php&ibDiplomaCASCommitmentID=$ibDiplomaCASCommitmentID";

if (isActionAccessible($guid, $connection2, '/modules/IB Diploma/cas_student_myCommitments_edit.php') == false) {
    //Fail 0
    $URL = $URL.'&return=error0';
    header("Location: {$URL}");
} else {
    if (enroled($guid, $session->get('gibbonPersonID'), $connection2) == false) {
        //Fail 0
        $URL = $URL.'&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if school year specified
        if ($ibDiplomaCASCommitmentID == '') {
            //Fail1
            $URL = $URL.'&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'ibDiplomaCASCommitmentID' => $ibDiplomaCASCommitmentID);
                $sql = 'SELECT * FROM ibDiplomaCASCommitment WHERE gibbonPersonID=:gibbonPersonID AND ibDiplomaCASCommitmentID=:ibDiplomaCASCommitmentID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                //Fail2
                $URL = $URL.'&deleteReturn=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() != 1) {
                //Fail 2
                $URL = $URL.'&return=error2';
                header("Location: {$URL}");
            } else {
                $name = $_POST['name'];
                $status = $_POST['status'];
                $dateStart = Format::dateConvert($_POST['dateStart']);
                if ($_POST['dateEnd'] == '') {
                    $dateEnd = null;
                } else {
                    $dateEnd = Format::dateConvert($_POST['dateEnd']);
                }
                $supervisorName = $_POST['supervisorName'];
                $supervisorEmail = $_POST['supervisorEmail'];
                $supervisorPhone = $_POST['supervisorPhone'];

                $description = $_POST['description'];

                if ($name == '' or $status == '' or $dateStart == '' or $supervisorName == '' or $supervisorEmail == '' or $supervisorPhone == '') {
                    //Fail 3
                    $URL = $URL.'&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'name' => $name, 'status' => $status, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'supervisorName' => $supervisorName, 'supervisorEmail' => $supervisorEmail, 'supervisorPhone' => $supervisorPhone, 'description' => $description);
                        $sql = "UPDATE ibDiplomaCASCommitment SET gibbonPersonID=:gibbonPersonID, name=:name, status=:status, dateStart=:dateStart, dateEnd=:dateEnd, supervisorName=:supervisorName, supervisorEmail=:supervisorEmail, supervisorPhone=:supervisorPhone, description=:description WHERE ibDiplomaCASCommitmentID=$ibDiplomaCASCommitmentID";
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
