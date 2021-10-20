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

function enroled($guid, $gibbonPersonID, $connection2)
{
    $output = false;

    try {
        $data = array('gibbonPersonID' => $gibbonPersonID, 'sequenceStart' => $session->get('gibbonSchoolYearSequenceNumber'), 'sequenceEnd' => $session->get('gibbonSchoolYearSequenceNumber'));
        $sql = 'SELECT ibDiplomaStudent.*, start.sequenceNumber AS start, end.sequenceNumber AS end FROM ibDiplomaStudent JOIN gibbonSchoolYear AS start ON (start.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDStart) JOIN gibbonSchoolYear AS end ON (end.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDEnd) WHERE gibbonPersonID=:gibbonPersonID AND start.sequenceNumber<=:sequenceStart AND end.sequenceNumber>=:sequenceEnd';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    if ($result->rowCount() == 1) {
        $output = true;
    }

    return $output;
}

function staffCASRole($guid,  $gibbonPersonID, $connection2)
{
    $output = false;

    try {
        $data = array('gibbonPersonID' => $session->get('gibbonPersonID'));
        $sql = 'SELECT * FROM ibDiplomaCASStaff WHERE gibbonPersonID='.$session->get('gibbonPersonID');
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    if ($result->rowCount() == 1) {
        $values = $result->fetch();
        if ($values['role'] == 'Coordinator' or $values['role'] == 'Advisor') {
            $output = $values['role'];
        }
    }

    return $output;
}
