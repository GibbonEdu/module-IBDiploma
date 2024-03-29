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

//Module includes
include './modules/'.$session->get('module').'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/IB Diploma/cas_iboCheck_details.php') == false) {

    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $gibbonPersonID = $_GET['gibbonPersonID'];
    if ($gibbonPersonID == '') { $page->addError(__('You have not specified a student.'));
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'sequenceStart' => $session->get('gibbonSchoolYearSequenceNumber'), 'sequenceEnd' => $session->get('gibbonSchoolYearSequenceNumber'), 'gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID, ibDiplomaStudentID, surname, preferredName, start.name AS start, end.name AS end, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup, gibbonPersonIDCASAdvisor, casStatusSchool FROM ibDiplomaStudent JOIN gibbonPerson ON (ibDiplomaStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (ibDiplomaStudent.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) LEFT JOIN gibbonSchoolYear AS start ON (start.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDStart) LEFT JOIN gibbonSchoolYear AS end ON (end.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDEnd) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) LEFT JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND start.sequenceNumber<=:sequenceStart AND end.sequenceNumber=:sequenceEnd AND gibbonPerson.gibbonPersonID=:gibbonPersonID";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $page->addError($e->getMessage());
        }

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified student does not exist, or you do not have access to them.'));
        } else {
            $values = $result->fetch();

            $page->breadcrumbs
                ->add(__('IBO CAS Check'), 'cas_iboCheck.php')
                ->add(__('Student Details'));
                
            if (isset($_GET['updateReturn'])) {
                $updateReturn = $_GET['updateReturn'];
            } else {
                $updateReturn = '';
            }
            $updateReturnMessage = '';
            $class = 'error';
            if (!($updateReturn == '')) {
                if ($updateReturn == 'fail0') {
                    $updateReturnMessage = 'Update failed because you do not have access to this action.';
                } elseif ($updateReturn == 'fail1') {
                    $updateReturnMessage = 'Update failed because a required parameter was not set.';
                } elseif ($updateReturn == 'fail2') {
                    $updateReturnMessage = 'Update failed due to a database error.';
                } elseif ($updateReturn == 'fail3') {
                    $updateReturnMessage = 'Update failed because your inputs were invalid.';
                } elseif ($updateReturn == 'success0') {
                    $updateReturnMessage = 'Update was successful.';
                    $class = 'success';
                }
                echo "<div class='$class'>";
                echo $updateReturnMessage;
                echo '</div>';
            }

            if (isset($_GET['deleteReturn'])) {
                $deleteReturn = $_GET['deleteReturn'];
            } else {
                $deleteReturn = '';
            }
            $deleteReturnMessage = '';
            $class = 'error';
            if (!($deleteReturn == '')) {
                if ($deleteReturn == 'success0') {
                    $deleteReturnMessage = 'Delete was successful.';
                    $class = 'success';
                }
                echo "<div class='$class'>";
                echo $deleteReturnMessage;
                echo '</div>';
            }

            echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
            echo '<tr>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>Name</span><br/>";
            echo Format::name('', $values['preferredName'], $values['surname'], 'Student', true, true);
            echo '</td>';
            echo "<td style='width: 33%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>Year Group</span><br/>";
            try {
                $dataDetail = array('gibbonYearGroupID' => $values['gibbonYearGroupID']);
                $sqlDetail = 'SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID';
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
            echo '</tr>';
            echo '<tr>';
            echo "<td style='padding-top: 15px; width: 34%; vertical-align: top' colspan=3>";
            $casStatusSchool = $values['casStatusSchool'];
            echo "<span style='font-size: 115%; font-weight: bold'>CAS Status</span><br/>";
            if ($values['casStatusSchool'] == 'At Risk') {
                echo "<img title='At Risk' src='./themes/".$session->get('gibbonThemeName')."/img/iconCross.png'/>";
            } elseif ($values['casStatusSchool'] == 'On Task') {
                echo "<img title='On Task' src='./themes/".$session->get('gibbonThemeName')."/img/iconTick.png'/>";
            } elseif ($values['casStatusSchool'] == 'Excellence') {
                echo "<img title='Excellence' src='./themes/".$session->get('gibbonThemeName')."/img/like_on_small.png'/>";
            } elseif ($values['casStatusSchool'] == 'Incomplete') {
                echo "<img title='Incomplete' src='./themes/".$session->get('gibbonThemeName')."/img/iconCross.png'/> Incomplete";
            } elseif ($values['casStatusSchool'] == 'Complete') {
                echo "<img title='Complete' src='./themes/".$session->get('gibbonThemeName')."/img/iconTick.png'/> Complete";
            }
            echo '</td>';
            echo '</tr>';
            echo '</table>';

            echo '<h2>';
            echo 'Commitments';
            echo '</h2>';

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
                echo "<table cellspacing='0' style='width: 100%'>";
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
                echo 'Actions';
                echo '</tr>';

                $count = 0;
                $valuesNum = 'odd';
                $intended = array();
                $complete = array();
                while ($values = $result->fetch()) {
                    if ($count % 2 == 0) {
                        $valuesNum = 'even';
                    } else {
                        $valuesNum = 'odd';
                    }
                    ++$count;

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$valuesNum>";
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
                    echo "<a class='thickbox' href='".$session->get('absoluteURL').'/fullscreen.php?q=/modules/'.$session->get('module')."/cas_iboCheck_full.php&gibbonPersonID=$gibbonPersonID&ibDiplomaCASCommitmentID=".$values['ibDiplomaCASCommitmentID']."&width=1000&height=550'><img title='View' src='./themes/".$session->get('gibbonThemeName')."/img/page_right.png'/></a> ";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';

                echo '<h2>';
                echo 'Reflections';
                echo '</h2>';
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
                <script type="text/javascript">
                $(document).ready(function() {
                    $('.searchInput').val(1);
                    $('.body').find("tr:odd").addClass('odd');
                    $('.body').find("tr:even").addClass('even');
                        
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
                    echo 'Action';
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
                        echo "<td style='background-color: #D4F6DC' colspan=4>";
                        echo $values['reflection'];
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo "</tbody'>";
                    echo '</table>'; ?>
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
            }
        }
    }
}
?>
