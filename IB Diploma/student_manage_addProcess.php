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

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/student_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/IB Diploma/student_manage_add.php') == false) {

    //Fail 0
    $URL = $URL.'&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $gibbonSchoolYearIDStart = $_POST['gibbonSchoolYearIDStart'];
    $gibbonSchoolYearIDEnd = $_POST['gibbonSchoolYearIDEnd'];
    if ($_POST['gibbonPersonIDCASAdvisor'] != '') {
        $gibbonPersonIDCASAdvisor = $_POST['gibbonPersonIDCASAdvisor'];
    } else {
        $gibbonPersonIDCASAdvisor = null;
    }

    if ($gibbonSchoolYearIDStart == '' or $gibbonSchoolYearIDEnd == '') {
        //Fail 3
        $URL = $URL.'&return=error3';
        header("Location: {$URL}");
    } else {
        $update = true;
        $choices = $_POST['gibbonPersonID'];

        if (count($choices) < 1) {
            //Fail 2
            $URL = $URL.'&return=error1';
            header("Location: {$URL}");
        } else {
            foreach ($choices as $t) {
                //Check to see if student is already registered in this class
                try {
                    $data = array();
                    $sql = "SELECT * FROM ibDiplomaStudent WHERE gibbonPersonID=$t";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    //Fail 2
                    $URL = $URL.'&return=error1';
                    header("Location: {$URL}");
                    $update = false;
                }

                //If student not in course, add them
                if ($result->rowCount() == 0) {
                    try {
                        $data = array('gibbonPersonID' => $t, 'gibbonSchoolYearIDStart' => $gibbonSchoolYearIDStart, 'gibbonSchoolYearIDEnd' => $gibbonSchoolYearIDEnd, 'gibbonPersonIDCASAdvisor' => $gibbonPersonIDCASAdvisor);
                        $sql = 'INSERT INTO ibDiplomaStudent SET gibbonPersonID=:gibbonPersonID, gibbonSchoolYearIDStart=:gibbonSchoolYearIDStart, gibbonSchoolYearIDEnd=:gibbonSchoolYearIDEnd, gibbonPersonIDCASAdvisor=:gibbonPersonIDCASAdvisor';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $update = false;
                    }
                }
            }
            //Write to database
            if ($update == false) {
                //Fail 2
                $URL = $URL.'&return=error2';
                header("Location: {$URL}");
            } else {
                //Success 0
                $URL = $URL.'&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
