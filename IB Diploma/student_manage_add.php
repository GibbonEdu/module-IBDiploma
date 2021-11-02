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

//Module includes
include './modules/'.$session->get('module').'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/IB Diploma/student_manage_add.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs
        ->add(__('Student Enrolment'), 'student_manage.php')
        ->add(__('Add Student Enrolment'));

    $form = Form::create('addStudent', $session->get('absoluteURL').'/modules/'.$session->get('module').'/student_manage_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Students'));
        $row->addSelectStudent('gibbonPersonID',$session->get('gibbonSchoolYearID'), ['allStudents' => true, 'byName' => false, 'byForm' => true, 'showForm' => true])->selectMultiple()->isRequired();

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

    echo $form->getOutput();


}
?>
