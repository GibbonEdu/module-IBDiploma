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

if (isActionAccessible($guid, $connection2, '/modules/IB Diploma/cas_student_reflections.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    if (enroled($guid, $session->get('gibbonPersonID'), $connection2) == false) {
        //Acess denied
        $page->addError(__('You are not enroled in the IB Diploma programme.'));
    } else {
        $page->breadcrumbs
            ->add(__('Reflections'));

        try {
            $data = array('gibbonPersonID' => $session->get('gibbonPersonID'));
            $sql = 'SELECT * FROM ibDiplomaCASReflection WHERE gibbonPersonID=:gibbonPersonID ORDER BY timestamp';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $page->addError($e->getMessage());
        }

        echo "<div class='linkTop'>";
        echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/cas_student_reflections_add.php'><img title='New' src='./themes/".$session->get('gibbonThemeName')."/img/page_new.png'/></a>";
        echo '</div>';
        echo "<div class='linkTop'>";
        echo 'Filter Commitment: ';
        ?>
        <select name="searchInput" class="searchInput" style='float: none; width: 100px'>
            <option selected value=''>All</option>
            <option selected value='General'>General CAS</option>
            <?php
            try {
                $dataSelect = array('gibbonPersonID' => $session->get('gibbonPersonID'));
                $sqlSelect = 'SELECT DISTINCT ibDiplomaCASCommitment.ibDiplomaCASCommitmentID, name FROM ibDiplomaCASReflection JOIN ibDiplomaCASCommitment ON (ibDiplomaCASCommitment.ibDiplomaCASCommitmentID=ibDiplomaCASReflection.ibDiplomaCASCommitmentID) WHERE ibDiplomaCASReflection.gibbonPersonID=:gibbonPersonID ORDER BY timestamp';
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {
            }

            while ($rowSelect = $resultSelect->fetch()) {
                echo "<option value='".$rowSelect['ibDiplomaCASCommitmentID']."'>".htmlPrep($rowSelect['name']).'</option>';
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
            while ($row = $result->fetch()) {
                ++$count;

                $class = $row['ibDiplomaCASCommitmentID'];
                if ($class == '') {
                    $class = 'General';
                }
                echo "<tr class='$class'>";
                echo '<td>';
                if (is_null($row['ibDiplomaCASCommitmentID'])) {
                    echo '<b><i>General CAS</i></b>';
                } else {
                    try {
                        $dataCommitment = array('ibDiplomaCASCommitmentID' => $row['ibDiplomaCASCommitmentID']);
                        $sqlCommitment = 'SELECT * FROM ibDiplomaCASCommitment WHERE ibDiplomaCASCommitmentID=:ibDiplomaCASCommitmentID';
                        $resultCommitment = $connection2->prepare($sqlCommitment);
                        $resultCommitment->execute($dataCommitment);
                    } catch (PDOException $e) {
                        $page->addError($e->getMessage());
                    }
                    if ($resultCommitment->rowCount() == 1) {
                        $rowCommitment = $resultCommitment->fetch();
                        echo $rowCommitment['name'];
                    }
                }
                echo '</td>';
                echo '<td>';
                echo Format::date(substr($row['timestamp'], 0, 10));
                echo '</td>';
                echo '<td>';
                echo $row['title'];
                echo '</td>';
                echo '<td>';
                echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module').'/cas_student_reflections_delete.php&ibDiplomaCASReflectionID='.$row['ibDiplomaCASReflectionID']."'><img title='Delete' src='./themes/".$session->get('gibbonThemeName')."/img/garbage.png'/></a> ";
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
                echo $row['reflection'];
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
    }
}
?>
