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

@session_start();
use Gibbon\Forms\Form;


//Module includes
include './modules/'.$session->get('module').'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/IB Diploma/cas_student_reflections_add.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    if (enroled($guid, $session->get('gibbonPersonID'), $connection2) == false) {
        //Acess denied
        $page->addError(__('You are not enroled in the IB Diploma programme.'));
    } else {
        $page->breadcrumbs
            ->add(__('Reflections'), 'cas_student_reflections.php')
            ->add(__('Add Reflection'));
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        $step = null;
        if (isset($_GET['step'])) {
            $step = $_GET['step'];
        }
        if ($step != 1 and $step != 2) {
            $step = 1;
        }

        //Step 1
        if ($step == 1) {
        
            $form = Form::create('reflectionType',$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module').'/cas_student_reflections_add.php&step=2');
            
            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('step', 2);
           
               $form->addRow()->addHeading(__('Reflection Source'));
               
            $row = $form->addRow();
            $row->addLabel('Reflection Type', __('Reflection Type'));
            $row->addRadio("type1")->fromArray(array("General CAS Reflection" =>__("General CAS Reflection"), "Commitment Reflection" =>__("Commitment Reflection")))->inline();
            
            $data = array('gibbonPersonID' => $session->get('gibbonPersonID'));
            $sql = "SELECT ibDiplomaCASCommitmentID as value, concat(ibDiplomaCASCommitment.name, ' (', ibDiplomaCASCommitment.supervisorName, ')') as name FROM ibDiplomaCASCommitment WHERE gibbonPersonID=:gibbonPersonID";
            
            $form->toggleVisibilityByClass('ibDiplomaCASCommitmentID')->onRadio('type1')->when('Commitment Reflection');
            $row = $form->addRow()->addClass('ibDiplomaCASCommitmentID');
                $row->addLabel('ibDiplomaCASCommitmentID', __('Choose Activity'));
                $row->addSelect('ibDiplomaCASCommitmentID')->fromQuery($pdo, $sql, $data)->placeholder();
                    
            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit("Go");
                
            echo $form->getOutput();
            
        //Step 2
        } else {
            $type = $_POST['type1'];
            if ($type != 'General CAS Reflection' and $type != 'Commitment Reflection') {
                $type = 'General CAS Reflection';
            }
            if ($type == 'Commitment Reflection') {
                $ibDiplomaCASCommitmentID = $_POST['ibDiplomaCASCommitmentID'];
                if ($ibDiplomaCASCommitmentID == '') {
                    echo "<div class='warning'>";
                    echo 'You have not specified a commitment.';
                    echo '</div>';
                } else {
                    try {
                        $dataActivity = array('ibDiplomaCASCommitmentID' => $ibDiplomaCASCommitmentID);
                        $sqlActivity = 'SELECT * FROM ibDiplomaCASCommitment WHERE ibDiplomaCASCommitmentID=:ibDiplomaCASCommitmentID';
                        $resultActivity = $connection2->prepare($sqlActivity);
                        $resultActivity->execute($dataActivity);
                    } catch (PDOException $e) {
                        $page->addError($e->getMessage());
                    }

                    if ($resultActivity->rowCount() != 1) {
                        echo "<div class='warning'>";
                        echo 'The specified commitment does not exist.';
                        echo '</div>';
                    } else {
                        $rowActivity = $resultActivity->fetch();
                    }
                }
            }
            $form = Form::create('reflectionAdd', $session->get('absoluteURL').'/modules/'.$session->get('module').'/cas_student_reflections_addProcess.php');
                
                $form->addHiddenValue('address', $session->get('address'));
                
            
                $row = $form->addRow();
                    $row->addLabel('type', __('Type'));
                    $row->addTextField('type')->setValue($type)->readOnly()->isRequired();
                    
                if ($type == 'Commitment Reflection') {
                $row = $form->addRow();
                    $row->addLabel('commitment', __('Commitment'));
                    $row->addTextField('commitment')->setValue($rowActivity['name'])->readOnly()->isRequired();
                    $form->addHiddenValue('ibDiplomaCASCommitmentID', $rowActivity['ibDiplomaCASCommitmentID']);
                }
                $row = $form->addRow();
                    $row->addLabel('title', __('Title'));
                    $row->addTextField('title')->setValue()->isRequired();


                $editor = getSettingByScope($connection2, 'reflection', '', 20, false, true);
                $row = $form->addRow();
                $column = $row->addColumn();
                if ($type == 'Commitment Reflection') {
                    $column->addLabel('reflection', __('Reflection'))->description('When describing your experience in this commitment you may wish to include:');
                } else {
                    $column->addLabel('reflection', __('Reflection'))->description('When describing your experience of CAS in general you may wish to include:<i><ul><li>What was the nature of your experience?</li><li>What have you learned or accomplished?</li><li>What aspects were new or challenging?</li><li>How could it have been more challenging?</li><li>Did it match your expectations, if not, how?</li><li>How might you do things differently in the future?</li></ul></i>');
                }                             

                $column->addEditor('reflection',$guid)->setRows(15)->setValue($editor)->isRequired();
    
                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();
                
                echo $form->getOutput();


        }
    }
}
?>
