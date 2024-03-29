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
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;

@session_start();

//Module includes
include './modules/'.$session->get('module').'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/IB Diploma/cas_adviseStudents_details.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $role = staffCASRole($guid, $session->get('gibbonPersonID'), $connection2);
    if ($role == false) { $page->addError(__('You are not enroled in the IB Diploma programme.'));
    } else {
        $gibbonPersonID = $_GET['gibbonPersonID'];
        if ($gibbonPersonID == '') {
            $page->addError(__('You have not specified a student.'));
        } else {
            try {
                if ($role == 'Coordinator') {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'sequenceStart' => $session->get('gibbonSchoolYearSequenceNumber'), 'sequenceEnd' => $session->get('gibbonSchoolYearSequenceNumber'), 'gibbonPersonID' => $gibbonPersonID);
                    $sql = "SELECT gibbonPerson.gibbonPersonID, ibDiplomaStudentID, surname, preferredName, image_240, start.name AS start, end.name AS end, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup, gibbonFormGroup.gibbonFormGroupID, gibbonPersonIDCASAdvisor, casStatusSchool FROM ibDiplomaStudent JOIN gibbonPerson ON (ibDiplomaStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (ibDiplomaStudent.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) LEFT JOIN gibbonSchoolYear AS start ON (start.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDStart) LEFT JOIN gibbonSchoolYear AS end ON (end.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDEnd) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) LEFT JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND start.sequenceNumber<=:sequenceStart AND end.sequenceNumber>=:sequenceEnd AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY formGroup, surname, preferredName";
                } else {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'sequenceStart' => $session->get('gibbonSchoolYearSequenceNumber'), 'sequenceEnd' => $session->get('gibbonSchoolYearSequenceNumber'), 'advisor' => $session->get('gibbonPersonID'), 'gibbonPersonID' => $gibbonPersonID);
                    $sql = "SELECT gibbonPerson.gibbonPersonID, ibDiplomaStudentID, surname, preferredName, image_240, start.name AS start, end.name AS end, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup, gibbonFormGroup.gibbonFormGroupID, gibbonPersonIDCASAdvisor, casStatusSchool FROM ibDiplomaStudent JOIN gibbonPerson ON (ibDiplomaStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (ibDiplomaStudent.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) LEFT JOIN gibbonSchoolYear AS start ON (start.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDStart) LEFT JOIN gibbonSchoolYear AS end ON (end.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDEnd) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) LEFT JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND start.sequenceNumber<=:sequenceStart AND end.sequenceNumber>=:sequenceEnd AND gibbonPersonIDCASAdvisor=:advisor AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY formGroup, surname, preferredName";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $page->addError($e->getMessage());
            }

            if ($result->rowCount() != 1) {
                $page->addError(__('The specified student does not exist, or you do not have access to them.'));
            } else {
                $values = $result->fetch();
                $image_240 = $values['image_240'];

                $page->breadcrumbs
                    ->add(__('Advise CAS Students'), 'cas_adviseStudents.php')
                    ->add(__('Advise Student'));

                echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                echo '<tr>';
                echo "<td style='width: 34%; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>Name</span><br/>";
                echo Format::name('', $values['preferredName'], $values['surname'], 'Student', true, true);
                echo '</td>';
                echo "<td style='width: 34%; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>Form Group</span><br/>";
                try {
                    $dataDetail = array('gibbonFormGroupID' => $values['gibbonFormGroupID']);
                    $sqlDetail = 'SELECT * FROM gibbonFormGroup WHERE gibbonFormGroupID=:gibbonFormGroupID';
                    $resultDetail = $connection2->prepare($sqlDetail);
                    $resultDetail->execute($dataDetail);
                } catch (PDOException $e) {
                    $page->addError($e->getMessage());
                }
                if ($resultDetail->rowCount() == 1) {
                    $valuesDetail = $resultDetail->fetch();
                    echo '<i>'.$valuesDetail['name'].'</i>';
                }
                echo '</td>';
                echo "<td style='width: 34%; vertical-align: top'>";
                $casStatusSchool = $values['casStatusSchool'];
                echo "<span style='font-size: 115%; font-weight: bold'>CAS Status</span><br/>";
                if ($values['casStatusSchool'] == 'At Risk') {
                    echo "<img title='At Risk' src='./themes/".$session->get('gibbonThemeName')."/img/iconCross.png'/>";
                } elseif ($values['casStatusSchool'] == 'On Task') {
                    echo "<img title='On Task' src='./themes/".$session->get('gibbonThemeName')."/img/iconTick.png'/>";
                } elseif ($values['casStatusSchool'] == 'Excellence') {
                    echo "<img title='Excellence' src='./themes/".$session->get('gibbonThemeName')."/img/iconTick.png'/>";
                } elseif ($values['casStatusSchool'] == 'Incomplete') {
                    echo "<img title='Incomplete' src='./themes/".$session->get('gibbonThemeName')."/img/iconCross.png'/> Incomplete";
                } elseif ($values['casStatusSchool'] == 'Complete') {
                    echo "<img title='Complete' src='./themes/".$session->get('gibbonThemeName')."/img/iconTick.png'/> Complete";
                }
                echo '</td>';
                echo '</tr>';
                echo '</table>';

                $subpage = null;
                if (isset($_GET['subpage'])) {
                    $subpage = $_GET['subpage'];
                }
                if ($subpage == '') {
                    $subpage = 'Overview';
                }

                echo '<h2>';
                echo $subpage;
                echo '</h2>';

                if ($subpage == 'Overview') {
                    try {
                        $data = array('gibbonPersonID' => $gibbonPersonID);
                        $sql = 'SELECT * FROM ibDiplomaCASCommitment WHERE gibbonPersonID=:gibbonPersonID ORDER BY approval, name';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $page->addError($e->getMessage());
                    }

                    if ($result->rowCount() < 1) {
                        $page->addError(__('There are no commitments to display.'));
                    } else {
                        echo "<table cellspacing='0' style='width: 100%'  class='colorOddEven'>";
                        echo "<tr class='head'>";
                        echo "<th style='vertical-align: bottom'>";
                        echo 'Commitment';
                        echo '</th>';
                        echo "<th style='vertical-align: bottom'>";
                        echo 'Status';
                        echo '</th>';
                        echo "<th style='vertical-align: bottom'>";
                        echo 'Timing';
                        echo '</th>';
                        echo "<th style='vertical-align: bottom'>";
                        echo 'Supervisor';
                        echo '</th>';
                        echo "<th style='vertical-align: bottom'>";
                        echo "<span title='Supervisor Feedback'>Feedback</span>";
                        echo '</th>';
                        echo "<th style='vertical-align: bottom'>";
                        echo 'Actions';
                        echo '</th>';
                        echo '</tr>';

                        $count = 0;
                        $valuesNum = 'odd';
                        $intended = array();
                        $complete = array();
                        while ($values = $result->fetch()) {

                                //COLOR ROW BY STATUS!
                                echo "<tr>";
                            echo '<td>';
                            echo $values['name'];
                            echo '</td>';
                            echo '<td>';
                            if ($values['approval'] == 'Pending' or $values['approval'] == 'Not Approved') {
                                echo $values['approval'];
                            } else {
                                echo $values['status'];
                            }
                            echo '</td>';
                            echo '<td>';
                            if (substr($values['dateStart'], 0, 4) == substr($values['dateEnd'], 0, 4)) {
                                if (substr($values['dateStart'], 5, 2) == substr($values['dateEnd'], 5, 2)) {
                                    echo date('F', mktime(0, 0, 0, substr($values['dateStart'], 5, 2))).' '.substr($values['dateStart'], 0, 4);
                                } else {
                                    echo date('F', mktime(0, 0, 0, substr($values['dateStart'], 5, 2))).' - '.date('F', mktime(0, 0, 0, substr($values['dateEnd'], 5, 2))).' '.substr($values['dateStart'], 0, 4);
                                }
                            } else {
                                echo date('F', mktime(0, 0, 0, substr($values['dateStart'], 5, 2))).' '.substr($values['dateStart'], 0, 4).' - '.date('F', mktime(0, 0, 0, substr($values['dateEnd'], 5, 2))).' '.substr($values['dateEnd'], 0, 4);
                            }
                            echo '</td>';
                            echo '<td>';
                            if ($values['supervisorEmail'] != '') {
                                echo "<a href='mailto:".$values['supervisorEmail']."'>".$values['supervisorName'].'</a>';
                            } else {
                                echo $values['supervisorName'];
                            }
                            echo '</td>';
                            echo '<td>';
                            try {
                                $dataFeedback = array('ibDiplomaCASCommitmentID' => $values['ibDiplomaCASCommitmentID']);
                                $sqlFeedback = "SELECT * FROM ibDiplomaCASSupervisorFeedback WHERE ibDiplomaCASCommitmentID=:ibDiplomaCASCommitmentID AND complete='Y'";
                                $resultFeedback = $connection2->prepare($sqlFeedback);
                                $resultFeedback->execute($dataFeedback);
                            } catch (PDOException $e) {
                                $page->addError($e->getMessage());
                            }

                            if ($resultFeedback->rowCount() == 1) {
                                echo "<img title='Supervisor Feedback Complete' src='./themes/".$session->get('gibbonThemeName')."/img/iconTick.png'/>";
                            } else {
                                try {
                                    $dataFeedback = array('ibDiplomaCASCommitmentID' => $values['ibDiplomaCASCommitmentID']);
                                    $sqlFeedback = "SELECT * FROM ibDiplomaCASSupervisorFeedback WHERE ibDiplomaCASCommitmentID=:ibDiplomaCASCommitmentID AND complete='N'";
                                    $resultFeedback = $connection2->prepare($sqlFeedback);
                                    $resultFeedback->execute($dataFeedback);
                                } catch (PDOException $e) {
                                    $page->addError($e->getMessage());
                                }

                                if ($resultFeedback->rowCount() > 0) {
                                    echo "<img title='Supervisor Feedback Requested' src='./themes/".$session->get('gibbonThemeName')."/img/iconTick_light.png'/>";
                                }
                            }
                            echo '</td>';
                            echo '<td>';
                            echo "<a class='thickbox' href='".$session->get('absoluteURL').'/fullscreen.php?q=/modules/'.$session->get('module')."/cas_adviseStudents_full.php&gibbonPersonID=$gibbonPersonID&ibDiplomaCASCommitmentID=".$values['ibDiplomaCASCommitmentID']."&width=1000&height=550'><img title='View' src='./themes/".$session->get('gibbonThemeName')."/img/page_right.png'/></a> ";
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                } elseif ($subpage == 'Reflection') {
                    try {
                        $data = array('gibbonPersonID' => $gibbonPersonID);
                        $sql = 'SELECT * FROM ibDiplomaCASReflection WHERE gibbonPersonID=:gibbonPersonID ORDER BY timestamp';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $page->addError($e->getMessage());
                    }

                    echo "<div class='linkTop'>";
                    echo 'Filter Commitment: '; ?>
                    <select name="searchInput" class="searchInput" style='float: none; width: 100px'>
                        <option selected value=''>All</option>
                        <option selected value='General'>General CAS</option>
                        <?php
                        try {
                            $dataSelect = array('gibbonPersonID' => $gibbonPersonID);
                            $sqlSelect = 'SELECT DISTINCT ibDiplomaCASCommitment.ibDiplomaCASCommitmentID, name FROM ibDiplomaCASReflection JOIN ibDiplomaCASCommitment ON (ibDiplomaCASCommitment.ibDiplomaCASCommitmentID=ibDiplomaCASReflection.ibDiplomaCASCommitmentID) WHERE ibDiplomaCASReflection.gibbonPersonID=:gibbonPersonID ORDER BY timestamp';
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                            $page->addError($e->getMessage());
                        }
                        while ($valuesSelect = $resultSelect->fetch()) {
                            echo "<option value='".$valuesSelect['ibDiplomaCASCommitmentID']."'>".htmlPrep($valuesSelect['name']).'</option>';
                        }
                        ?>
                    </select>
                    <?php
                    echo '</div>';

                    if ($result->rowCount() < 1) {
                        $page->addError(__('There are no reflections to display.'));
                    } else {
                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo "<th style='vertical-align: bottom'>";
                        echo 'Commitment';
                        echo '</th>';
                        echo "<th style='vertical-align: bottom'>";
                        echo 'Date';
                        echo '</th>';
                        echo "<th style='vertical-align: bottom'>";
                        echo 'Title';
                        echo '</th>';
                        echo "<th style='vertical-align: bottom'>";
                        echo 'Actions';
                        echo '</th>';
                        echo '</tr>';
                        echo "<tbody class='body'>";
                        $count = 0;
                        $valuesNum = 'odd';
                        while ($values = $result->fetch()) {
                            ++$count;

                            $class = $values['ibDiplomaCASCommitmentID'];
                            if ($class == '') {
                                $class = 'General';
                            }
                            echo "<tr class='$class'>";
                            echo '<td>';
                            if (is_null($values['ibDiplomaCASCommitmentID'])) {
                                echo '<b><i>General CAS</i></b>';
                            } else {
                                try {
                                    $dataCommitment = array('ibDiplomaCASCommitmentID' => $values['ibDiplomaCASCommitmentID']);
                                    $sqlCommitment = 'SELECT * FROM ibDiplomaCASCommitment WHERE ibDiplomaCASCommitmentID=:ibDiplomaCASCommitmentID';
                                    $resultCommitment = $connection2->prepare($sqlCommitment);
                                    $resultCommitment->execute($dataCommitment);
                                } catch (PDOException $e) {
                                    $page->addError($e->getMessage());
                                }
                                if ($resultCommitment->rowCount() == 1) {
                                    $valuesCommitment = $resultCommitment->fetch();
                                    echo $valuesCommitment['name'];
                                }
                            }
                            echo '</td>';
                            echo '<td>';
                            echo Format::date(substr($values['timestamp'], 0, 10));
                            echo '</td>';
                            echo '<td>';
                            echo $values['title'];
                            echo '</td>';
                            echo '<td>';
                            echo "<script type='text/javascript'>";
                            echo '$(document).ready(function(){';
                            echo "\$(\".comment-$count\").hide();";
                            echo "\$(\".show_hide-$count\").fadeIn(1000);";
                            echo "\$(\".show_hide-$count\").click(function(){";
                            echo "\$(\".comment-$count\").fadeToggle(1000);";
                            echo '});';
                            echo '});';
                            echo '</script>';
                            echo "<a class='show_hide-$count' onclick='false'  href='#'><img style='padding-right: 5px' src='".$session->get('absoluteURL')."/themes/Default/img/page_down.png' alt='Show Comment' onclick='return false;' /></a>";
                            echo '</td>';
                            echo '</tr>';
                            echo "<tr class='comment-$count' id='comment-$count'>";
                            echo "<td style='background-color: #D4F6DC;' colspan=4>";
                            echo $values['reflection'];
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo "</tbody'>";
                        echo '</table>';
                        ?>
                        <script type="text/javascript">
                            $(document).ready(function() {
                                $('.searchInput').val(1);
                                $('.body').find("tr:visible:odd").addClass('odd');
                                $('.body').find("tr:visible:even").addClass('even');

                                $(".searchInput").change(function(){
                                    $('.body').find("tr").hide() ;
                                    if ($('.searchInput :selected').val() == "" ) {
                                        $('.body').find("tr").show() ;
                                    }
                                    else {
                                        $('.body').find('.' + $('.searchInput :selected').val()).show();
                                    }

                                    $('.body').find("tr").removeClass('odd even');
                                    $('.body').find('tr:visible:odd').addClass('odd');
                                    $('.body').find('tr:visible:even').addClass('even');
                                });

                            });
                        </script>
                        <?php

                    }
                } elseif ($subpage == 'CAS Status') {
                    echo '<p>';
                    echo "This field is used to indicate whether or not the student has, in the school's opinion, completed the CAS component of the IB Diploma.";
                    echo '</p>';


                    $form = Form::create('casStatus', $session->get('absoluteURL').'/modules/'.$session->get('module').'/cas_adviseStudents_detailsStatusProcess.php', "post");

                    $form->setFactory(DatabaseFormFactory::create($pdo));


                    $form->addHiddenValue('address', $session->get('address'));
                    $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);

                     $row = $form->addRow();
                         $row->addHeading(__('Status *'));
                         $row->addSelect('casStatusSchool')->fromArray(array('' => __(''), 'At Risk' => __('At Risk'), 'On Task' => __('On Task'), 'Excellence' => __('Execellence'), 'Complete' => ('Complete'), 'Incomplete' => ('Incomplete')))->selected($casStatusSchool)->isRequired();

                    $row = $form->addRow();
                        $row->addFooter();
                        $row->addSubmit();

                    echo $form->getOutput();

                } elseif ($subpage == 'Interview 1') {
                    try {
                        $dataInterview = array('gibbonPersonID' => $gibbonPersonID);
                        $sqlInterview = 'SELECT ibDiplomaCASInterview.*, surname, preferredName FROM ibDiplomaCASInterview JOIN gibbonPerson ON (ibDiplomaCASInterview.1_gibbonPersonIDInterviewer=gibbonPerson.gibbonPersonID) WHERE gibbonPersonIDInterviewee=:gibbonPersonID';
                        $resultInterview = $connection2->prepare($sqlInterview);
                        $resultInterview->execute($dataInterview);
                    } catch (PDOException $e) {
                        $page->addError($e->getMessage());
                    }

                    if ($resultInterview->rowCount() > 1) {
                        $page->addError(__('Interview cannot be displayed.'));
                    } else {
                        if ($resultInterview->rowCount() == 1) {
                            $valuesInterview = $resultInterview->fetch();
                        }
                        try {
                            $dataCommitments = array('gibbonPersonID' => $gibbonPersonID);
                            $sqlCommitments = "SELECT * FROM ibDiplomaCASCommitment WHERE gibbonPersonID=:gibbonPersonID AND approval='Approved' ORDER BY name";
                            $resultCommitments = $connection2->prepare($sqlCommitments);
                            $resultCommitments->execute($dataCommitments);
                        } catch (PDOException $e) {
                            $page->addError($e->getMessage());
                        }

                        if ($resultCommitments->rowCount() < 1) {
                            echo "<div class='error'>";
                            echo 'There are no commitments to display.';
                            echo '</div>';
                        } else {

                        $form = Form::create('interview1', $session->get('absoluteURL').'/modules/'.$session->get('module')."/cas_adviseStudents_detailsInterview1Process.php");

                            $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
                            $form->addHiddenValue('address', $session->get('address'));

                            $form->addRow()->addHeading(__('Commitment Goals'));
                                $formRow = $form->addRow();
                                $formRow->addLabel('label', __('Work with the interviewee to determine a suitable, brief goal for each approved commitment.'));

                            $table = $form->addRow()->addTable()->setClass('mini fullWidth');
                                $row = $table->addHeaderRow();
                                    $row->addContent(__('Commitment'))->wrap('<div style="width: 120px;">', '</div>');
                                    $row->addContent(__('Timing'))->wrap('<div style="width: 300px;">', '</div>');
                                    $row->addContent(__('Goals'))->wrap('<div style="width: 120px;">', '</div>');

                            $count = 0;
                            while ($valuesCommitments = $resultCommitments->fetch()) {
                                ++$count;
                                $row = $table->addRow();
                                    $row->addContent(__($valuesCommitments['name']));
                                    if (substr($valuesCommitments['dateStart'], 0, 4) == substr($valuesCommitments['dateEnd'], 0, 4)) {
                                            if (substr($valuesCommitments['dateStart'], 5, 2) == substr($valuesCommitments['dateEnd'], 5, 2)) {
                                                $row->addContent(__(date('F', mktime(0, 0, 0, substr($valuesCommitments['dateStart'], 5, 2))).' '.substr($valuesCommitments['dateStart'], 0, 4)));
                                            } else {
                                                $row->addContent(__(date('F', mktime(0, 0, 0, substr($valuesCommitments['dateStart'], 5, 2))).' - '.date('F', mktime(0, 0, 0, substr($valuesCommitments['dateEnd'], 5, 2))).' '.substr($valuesCommitments['dateStart'], 0, 4)));
                                            }
                                        } else {
                                            $row->addContent(__(date('F', mktime(0, 0, 0, substr($valuesCommitments['dateStart'], 5, 2))).' '.substr($valuesCommitments['dateStart'], 0, 4).' - '.date('F', mktime(0, 0, 0, substr($valuesCommitments['dateEnd'], 5, 2))).' '.substr($valuesCommitments['dateEnd'], 0, 4)));
                                        }
                                    $form->addHiddenValue($count.'-ibDiplomaCASCommitmentID', $valuesCommitments['ibDiplomaCASCommitmentID']);
                                    $row->addTextField($count.'-goals')->setValue($valuesCommitments['goals'])->maxLength(255);
                            }

                            $form->addRow()->addHeading(__('Notes'));
                                $row = $form->addRow();
                                    $column = $row->addColumn();
                                        $column->addContent( __('Use this space to take notes on your conversation with the student. You may wish to consider:<i><ul><li>Is there a balance across commitments?</li><li>Are commitments genuine and meaningful?</li><li>Do commitments require student to show persistence and commitment?</li></ul></i>'));
                                        $column->addTextArea('notes')->setRows(15)->setValue($valuesInterview['1_notes'])->setClass('fullWidth');

                            $form->addRow()->addHeading(__('General Information'));
                                $row = $form->addRow();
                                    $row->addLabel('interviewer', __('Interviewer'));
                                    $row->addTextField('interviewer')->setValue(Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff', true, true))->readOnly()->isRequired();
                                $row = $form->addRow();
                                    $row->addLabel('date', __('Date'));
                                    $row->addDate('date')->setValue(Format::date($valuesInterview['1_date']))->isRequired();
                                $row = $form->addRow();
                                    $row->addLabel('casStatusSchool', __('CAS Status'));
                                    $row->addSelect('casStatusSchool')->fromArray(array('At Risk' =>__('At Risk'), 'On Task' => __('On Task'), 'Excellence' =>__('Excellence')))->selected($casStatusSchool)->isRequired();

                            $form->addHiddenValue("count", $count);
                            $row = $form->addRow();
                                $row->addFooter();
                                $row->addSubmit();
                            echo $form->getOutput();
                    }
                }
                } elseif ($subpage == 'Interview 2') {
                    try {
                        $dataInterview = array('gibbonPersonID' => $gibbonPersonID);
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
                            $page->addError(__('You have not yet completed Interview 1, and so cannot access Interview 2.'));
                        } else {
                            $valuesInterview = $resultInterview->fetch();


                            $form = Form::create('interview2', $session->get('absoluteURL').'/modules/'.$session->get('module')."/cas_adviseStudents_detailsInterview2Process.php");

                            $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
                            $form->addHiddenValue('address', $session->get('address'));

                            $form->addRow()->addHeading(__('Commitment Goals'));
                                $formRow = $form->addRow();
                                $formRow->addContent( __('Work with the interviewee to determine which commitments you think <b>might</b> satisfy each of the outcomes listed below. The student should have pre-filled this information before Interview 2.'));

                            try {
                                $dataList = array('gibbonPersonID' => $gibbonPersonID);
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
                                if ($valuesInterview["2_outcome".$i] != '') {
                                    $outcomeList = array();
                                    try {
                                        array_push($outcomeList, $valuesInterview['2_outcome'.$i]);
                                        $dataPrepopulate = ['outcomeList' => $valuesInterview['2_outcome'.$i]];
                                        $sqlPrepopulate = "SELECT ibDiplomaCASCommitmentID as value, name as name FROM ibDiplomaCASCommitment WHERE FIND_IN_SET(ibDiplomaCASCommitmentID, '".$dataPrepopulate['outcomeList']."')";
                                        $resultPrepopulate = $connection2->query($sqlPrepopulate);
                                    } catch (PDOException $e) {
                                        $page->addError($e->getMessage());
                                    }
                                    while ($valuesPrepopulate = $resultPrepopulate->fetch()) {
                                        $prepopulate = $pdo->select($sqlPrepopulate, $dataPrepopulate)->fetchKeyPair();
                                    }
                                }

                                    $data = array('gibbonPersonID' => $gibbonPersonID);
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
                            $form->addRow()->addHeading(__('Notes'));
                                $row = $form->addRow();
                                    $column = $row->addColumn();
                                        $column->addContent( __('Use this space to take notes on your conversation with the student. You may wish to consider:<i><ul><li>How is student progressing?</li><li>Are all outcomes begun?</li><li>Which outcomes require more thought and action?</li></ul></i>'));
                                        $column->addTextArea('notes')->setRows(15)->setValue($valuesInterview['2_notes'])->setClass('fullWidth');

                            $form->addRow()->addHeading(__('General Information'));
                                $row = $form->addRow();
                                    $row->addLabel('interviewer', __('Interviewer'));
                                    $row->addTextField('interviewer')->setValue(Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff', true, true))->readOnly()->isRequired();
                                $row = $form->addRow();
                                    $row->addLabel('date', __('Date'));
                                    $row->addDate('date')->setValue(Format::date($valuesInterview['2_date']))->isRequired();
                                $row = $form->addRow();
                                    $row->addLabel('casStatusSchool', __('CAS Status'));
                                    $row->addSelect('casStatusSchool')->fromArray(array('At Risk' =>__('At Risk'), 'On Task' => __('On Task'), 'Excellence' =>__('Excellence')))->selected($casStatusSchool)->isRequired();


                            $row = $form->addRow();
                                $row->addFooter();
                                $row->addSubmit();
                            echo $form->getOutput();
                        }
                    }
                } elseif ($subpage == 'Interview 3') {
                    try {
                        $dataInterview = array('gibbonPersonID' => $gibbonPersonID);
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
                            $page->addError(__('You have not yet completed Interview 1, and so cannot access Interview 2.'));
                        } else {
                            $valuesInterview = $resultInterview->fetch();

                            if (is_null($valuesInterview['2_date'])) {
                                $page->addError(__('You have not yet completed Interview 2, and so cannot access Interview 3.'));
                            } else {
                                $form = Form::create('interview3', $session->get('absoluteURL').'/modules/'.$session->get('module')."/cas_adviseStudents_detailsInterview3Process.php");

                                $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
                                $form->addHiddenValue('address', $session->get('address'));

                                $form->addRow()->addHeading(__('Outcomes'));
                                    $formRow = $form->addRow();
                                    $formRow->addContent( __('Work with the interviewee to determine which commitments you think <b>have</b> satisfied each of the outcomes listed below. The student should have pre-filled this information before Interview 3. Use the second box for each outcome to record notes from your discussion'));

                                try {
                                    $dataList = array('gibbonPersonID' => $gibbonPersonID);
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
                                    if ($valuesInterview["3_outcome".$i] != '') {
                                        echo "3_outcome".$i;
                                        $outcomeList = array();
                                        try {
                                            array_push($outcomeList, $valuesInterview['3_outcome'.$i]);
                                            $dataPrepopulate = ['outcomeList' => $valuesInterview['3_outcome'.$i]];
                                            $sqlPrepopulate = "SELECT ibDiplomaCASCommitmentID as value, name as name FROM ibDiplomaCASCommitment WHERE FIND_IN_SET(ibDiplomaCASCommitmentID, '".$dataPrepopulate['outcomeList']."')";
                                            $resultPrepopulate = $connection2->query($sqlPrepopulate);
                                        } catch (PDOException $e) {
                                            $page->addError($e->getMessage());
                                        }
                                        while ($valuesPrepopulate = $resultPrepopulate->fetch()) {
                                            $prepopulate = $pdo->select($sqlPrepopulate, $dataPrepopulate)->fetchKeyPair();
                                        }
                                    }

                                        $data = array('gibbonPersonID' => $gibbonPersonID);
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
                                            $column->addTextArea('outcome'.$i.'Notes')
                                                ->setRows(3)
                                                ->setValue($valuesInterview['3_outcome'.$i.'Notes'])
                                                ->setClass('fullWidth');
                                }
                                $form->addRow()->addHeading(__('Notes'));
                                    $row = $form->addRow();
                                        $column = $row->addColumn();
                                            $column->addContent( __('Use this space to take notes on your conversation with the student. You may wish to consider:<i><ul style="margin-bottom: 0px"><li>Are all outcomes satisfactorily completed?</li></ul></i><br/>'));
                                            $column->addTextArea('notes')->setRows(15)->setValue($valuesInterview['3_notes'])->setClass('fullWidth');

                                $form->addRow()->addHeading(__('General Information'));
                                    $row = $form->addRow();
                                        $row->addLabel('interviewer', __('Interviewer'));
                                        $row->addTextField('interviewer')->setValue(Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff', true, true))->readOnly()->isRequired();
                                    $row = $form->addRow();
                                        $row->addLabel('date', __('Date'));
                                        $row->addDate('date')->setValue(Format::date($valuesInterview['3_date']))->isRequired();
                                    $row = $form->addRow();
                                        $row->addLabel('casStatusSchool', __('CAS Status'));
                                        $row->addSelect('casStatusSchool')->fromArray(array('Complete' =>__('Complete'), 'Incomplete' => __('Incomplete')))->selected($casStatusSchool)->isRequired();


                                $row = $form->addRow();
                                    $row->addFooter();
                                    $row->addSubmit();
                                echo $form->getOutput();



                            }
                        }
                    }
                }

                //Set sidebar
                $sidebarExtra = Format::userPhoto($image_240, 240);
                $sidebarExtra .= '<br>';
                $sidebarExtra .= '<ul>';
                $style = '';
                if ($subpage == 'Overview') {
                    $style = "style='font-weight: bold'";
                }
                $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&subpage=Overview'>Overview</a></li>";

                $style = '';
                if ($subpage == 'Reflection') {
                    $style = "style='font-weight: bold'";
                }
                $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&subpage=Reflection'>Reflection</a></li>";

                $style = '';
                if ($subpage == 'CAS Status') {
                    $style = "style='font-weight: bold'";
                }
                $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&subpage=CAS Status'>CAS Status</a></li>";

                $style = '';
                if ($subpage == 'Interview 1') {
                    $style = "style='font-weight: bold'";
                }
                $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&subpage=Interview 1'>Interview 1</a></li>";

                $style = '';
                if ($subpage == 'Interview 2') {
                    $style = "style='font-weight: bold'";
                }
                $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&subpage=Interview 2'>Interview 2</a></li>";

                $style = '';
                if ($subpage == 'Interview 3') {
                    $style = "style='font-weight: bold'";
                }
                $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&subpage=Interview 3'>Interview 3</a></li>";

                $sidebarExtra .= '</ul>';

                $session->set('sidebarExtra', $sidebarExtra);
            }
        }
    }
}
?>
