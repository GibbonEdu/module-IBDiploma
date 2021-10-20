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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

@session_start();

//Module includes
include './modules/'.$session->get('module').'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/IB Diploma/student_manage_edit.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Student Enrolment'), 'student_manage.php')
        ->add(__('Edit Student Enrolment'));
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $ibDiplomaStudentID = $_GET['ibDiplomaStudentID'];
    if ($ibDiplomaStudentID == 'Y') {$page->addError(__('You have not specified an activity.'));
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'ibDiplomaStudentID' => $ibDiplomaStudentID);
            $sql = "SELECT ibDiplomaStudentID, surname, preferredName, start.name AS start, end.name AS end, gibbonSchoolYearIDStart, gibbonSchoolYearIDEnd, gibbonPersonIDCASAdvisor FROM ibDiplomaStudent JOIN gibbonPerson ON (ibDiplomaStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (ibDiplomaStudent.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) LEFT JOIN gibbonSchoolYear AS start ON (start.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDStart) LEFT JOIN gibbonSchoolYear AS end ON (end.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDEnd) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) LEFT JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND ibDiplomaStudentID=:ibDiplomaStudentID ORDER BY start.sequenceNumber, surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>";
            echo 'The student cannot be edited due to a database error.';
            echo '</div>';
        }

        if ($result->rowCount() != 1) {
            $page->addError(__('The selected activity does not exist.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('editStudent', $session->get('absoluteURL').'/modules/'.$session->get('module').'/student_manage_editProcess.php?ibDiplomaStudentID='.$ibDiplomaStudentID, 'post');
             
             $form->setFactory(DatabaseFormFactory::create($pdo));
            
            
             $form->addHiddenValue('address', $session->get('address'));
             
            
            $row = $form->addRow();
                $row->addLabel('Student',__('Student'));
                $row->addTextField('gibbonPersonName')->readOnly()->setValue(formatName('', $values['preferredName'], $values['surname'], 'Student', true, true));
            
            $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
            $sql = "SELECT gibbonSchoolYearID as value,name FROM gibbonSchoolYear ORDER BY sequenceNumber";
            $row = $form->addRow();
                $row->addLabel('gibbonSchoolYearIDStart', __('Start Year'));
                $row->addSelect('gibbonSchoolYearIDStart')->fromQuery($pdo, $sql)->placeholder()->isRequired();
                
            $row = $form->addRow();
                $row->addLabel('gibbonSchoolYearIDEnd', __('End Year'));
                $row->addSelect('gibbonSchoolYearIDEnd')->fromQuery($pdo, $sql)->placeholder()->isRequired();

            $sql = "SELECT gibbonPerson.gibbonPersonID as value, concat(gibbonPerson.firstName, ' ',gibbonPerson.surname) As name FROM gibbonPerson inner join ibDiplomaCASStaff on ibDiplomaCASStaff.gibbonPersonID = gibbonPerson.gibbonPersonID ORDER BY  gibbonPerson.firstName";
            $row = $form->addRow();
                $row->addLabel('gibbonPersonIDCASAdvisor', __('CAS Advisor'));
                $row->addSelect('gibbonPersonIDCASAdvisor')->fromQuery($pdo, $sql)->placeholder();
            
            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();
                
            $form->loadAllValuesFrom($values);
            
            echo $form->getOutput();
        }
    }
}
?>
