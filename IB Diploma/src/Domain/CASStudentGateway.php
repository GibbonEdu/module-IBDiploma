<?php
namespace Gibbon\Module\IBDiploma\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * CAS Student Gateway
 *
 * @version v21
 * @since   v21
 */
class CASStudentGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'ibDiplomaStudent';
    private static $primaryKey = 'ibDiplomaStudentID';
    private static $searchableColumns = ['surname', 'preferredName'];


    public function queryCASStudents($criteria, $gibbonSchoolYearID, $gibbonSchoolYearSequenceNumber, $gibbonPersonID) {
        $query = $this
            ->newQuery()
            ->from('ibDiplomaStudent')
            ->cols(['gibbonPerson.gibbonPersonID', 'ibDiplomaStudentID', 'surname', 'preferredName', 'start.name AS start', 'end.name AS end', 'gibbonYearGroup.nameShort AS yearGroup', 'gibbonformGroup.nameShort AS formGroup', 'gibbonformGroup.gibbonformGroupID', 'gibbonPersonIDCASAdvisor', 'casStatusSchool'])
            ->leftjoin('gibbonPerson', 'ibDiplomaStudent.gibbonPersonID=gibbonPerson.gibbonPersonID' )
            ->leftjoin('gibbonStudentEnrolment', 'ibDiplomaStudent.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->leftJoin('gibbonSchoolYear AS start', 'start.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDStart')
            ->leftJoin('gibbonSchoolYear AS end','end.gibbonSchoolYearID=ibDiplomaStudent.gibbonSchoolYearIDEnd')
            ->leftJoin('gibbonYearGroup','gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->leftJoin('gibbonformGroup', 'gibbonStudentEnrolment.gibbonformGroupID=gibbonformGroup.gibbonformGroupID')
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ')->bindvalue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonPerson.status="Full"')
            ->where('start.sequenceNumber<=:sequenceStart')->bindvalue('sequenceStart', $gibbonSchoolYearSequenceNumber)
            ->where('end.sequenceNumber>=:sequenceEnd')->bindvalue('sequenceEnd', $gibbonSchoolYearSequenceNumber);

        $criteria->addFilterRules([
            'gibbonformGroupID' => function ($query, $gibbonformGroupID) {
                return $query
                    ->where('gibbonformGroup.gibbonformGroupID=:gibbonformGroupID')
                    ->bindValue('gibbonformGroupID', $gibbonformGroupID);
            }
        ]);

       return $this->runQuery($query, $criteria);
    }
}
