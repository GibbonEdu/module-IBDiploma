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

if (isActionAccessible($guid, $connection2, '/modules/IB Diploma/cas_student_interview3.php') == false) {

    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    if (enroled($guid, $session->get('gibbonPersonID'), $connection2) == false) {
        //Acess denied
        $page->addError(__('You are not enroled in the IB Diploma programme.'));
    } else {

        $page->breadcrumbs->add(__('Student: Interview 3'));
        
        echo '<p>';
        echo 'This page allows you to pre-enter information about your outcomes prior to Interview 3. For each of the 8 outcomes below, indicate which commitments you think <b>have</b> satisfied that outcome. In the interview you will be asked to give verbal explanations and evidence (e.g. certificates) of how you met the outcomes.';
        echo '</p>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        try {
            $dataInterview = array('gibbonPersonID' => $session->get('gibbonPersonID'));
            $sqlInterview = 'SELECT ibDiplomaCASInterview.*, surname, preferredName FROM ibDiplomaCASInterview JOIN gibbonPerson ON (ibDiplomaCASInterview.1_gibbonPersonIDInterviewer=gibbonPerson.gibbonPersonID) WHERE gibbonPersonIDInterviewee=:gibbonPersonID';
            $resultInterview = $connection2->prepare($sqlInterview);
            $resultInterview->execute($dataInterview);
        } catch (PDOException $e) {
            $page->addError($e->getMessage());
        }

        if ($resultInterview->rowCount() > 1) {
            $page->addError(__('Interview cannot be displayed.'));
        } else {
            if ($resultInterview->rowCount() == 0) {
                $page->addError(__('You have not yet completed Interview 1, and so cannot prepare for Interview 3.'));
            } else {
                $rowInterview = $resultInterview->fetch();

                if (is_null($rowInterview['2_date'])) {
                    $page->addError(__('You have not yet completed Interview 2, and so cannot prepare for Interview 3.'));
                } else {
                $form = Form::create('interview3', $session->get('absoluteURL').'/modules/'.$session->get('module')."/cas_student_interview2Process.php");

                                $form->addHiddenValue('address', $session->get('address'));
                            
                                $form->addRow()->addHeading(__('Outcomes'));
                                    $formRow = $form->addRow();
                            
                                try {
                                    $dataList = array('gibbonPersonID' => $session->get('gibbonPersonID'));
                                    $sqlList = "SELECT * FROM ibDiplomaCASCommitment WHERE gibbonPersonID=:gibbonPersonID AND approval='Approved' ORDER BY name";
                                    $resultList = $connection2->prepare($sqlList);
                                    $resultList->execute($dataList);
                                } catch (PDOException $e) {
                                    $page->addError($e->getMessage());
                                }

                                $list = '';
                                while ($valuesList = $resultList->fetch()) {
                                    $list .= '{id: "'.$valuesList['ibDiplomaCASCommitmentID'].'", name: "'.$valuesList['name'].'"},';
                                }
                                $list = substr($list, 0, -1);
                                for ($i = 1; $i < 9; ++$i) {
                                    switch ($i) {
                                        case 1:
                                            $title = "<span style='font-weight: bold' title='They are able to see themselves as individuals with various skills and abilities, some more developed than others, and understand that they can make choices about how they wish to move forward.'>Increased their awareness of their own strengths and areas for growth</span>";
                                            break;
                                        case 2:
                                            $title = "<span style='font-weight: bold' title='A new challenge may be an unfamiliar activity, or an extension to an existing one.'>Undertaken new challenges</span>";
                                            break;
                                        case 3:
                                            $title = "<span style='font-weight: bold' title='Planning and initiation will often be in collaboration with others. It can be shown in activities that are part of larger projects, for example, ongoing school activities in the local community, as well as in small student-led activities.'>Planned and initiated activities</span>";
                                            break;
                                        case 4:
                                            $title = "<span style='font-weight: bold' title='Collaboration can be shown in many different activities, such as team sports, playing music in a band, or helping in a kindergarten. At least one project, involving collaboration and the integration of at least two of creativity, action and service, is required.'>Worked collaboratively with others</span>";
                                            break;
                                        case 5:
                                            $title = "<span style='font-weight: bold' title='At a minimum, this implies attending regularly and accepting a share of the responsibility for dealing with problems that arise in the course of activities.'>Shown perseverance and commitment in their activities</span>";
                                            break;
                                        case 6:
                                            $title = "<span style='font-weight: bold' title='Students may be involved in international projects but there are many global issues that can be acted upon locally or nationally (for example, environmental concerns, caring for the elderly).'>Engaged with issues of global importance</span>";
                                            break;
                                        case 7:
                                            $title = "<span style='font-weight: bold' title='Ethical decisions arise in almost any CAS activity (for example, on the sports field, in musical composition, in relationships with others involved in service activities). Evidence of thinking about ethical issues can be shown in various ways, including journal entries and conversations with CAS advisers.'>Considered the ethical implications of their actions</span>";
                                            break;
                                        case 8:
                                            $title = "<span style='font-weight: bold' title='As with new challenges, new skills may be shown in activities that the student has not previously undertaken, or in increased expertise in an established area.'>Developed new skills</span>";
                                            break;
                                    }
                                
                                    $prepopulate = '';
                                    if ($rowInterview["3_outcome$i"] != '') {
                                        $outcomeList = array();
                                        try {
                                            array_push($outcomeList, $rowInterview['3_outcome'.$i]);
                                            $dataPrepopulate = ['outcomeList' => $rowInterview['3_outcome'.$i]];
                                            $sqlPrepopulate = "SELECT ibDiplomaCASCommitmentID as value, name as name FROM ibDiplomaCASCommitment WHERE FIND_IN_SET(ibDiplomaCASCommitmentID, '".$dataPrepopulate['outcomeList']."')";
                                            $resultPrepopulate = $connection2->query($sqlPrepopulate);
                                        } catch (PDOException $e) {
                                            $page->addError($e->getMessage());
                                        }
                                        while ($valuesPrepopulate = $resultPrepopulate->fetch()) {
                                            $prepopulate = $pdo->select($sqlPrepopulate, $dataPrepopulate)->fetchKeyPair();
                                        }
                                    }
                                
                                        $data =  array('gibbonPersonID' => $session->get('gibbonPersonID'));
                                        $sql = "SELECT name as name, ibDiplomaCASCommitmentID as value FROM ibDiplomaCASCommitment WHERE gibbonPersonID=:gibbonPersonID AND approval='Approved'";
                                        $row = $form->addRow()->addClass('tags');
                                            $column = $row->addColumn();
                                            $column->addLabel('outcome'.$i, __('Outcome '.$i))
                                                ->description(__($title));
                                            $column->addFinder('outcome'.$i)
                                                ->fromQuery($pdo, $sql, $data)
                                                ->setParameter('hintText', __('Type the name of an approved commitment...'))
                                                ->setParameter('allowCreation', false)
                                                ->selected($prepopulate);
                                }
        
                            
                                $row = $form->addRow();
                                    $row->addFooter();
                                    $row->addSubmit();
                                echo $form->getOutput();

                }
            }
        }
    }
}
?>
