<?php 

class ScheduleMonth{

    private $days = array(),
            $numShifts = 4,
            $numDays = 30,
            $dayOff = 2, //if Sunday is the first day then 0, if Monday then 1, if tuesday then 2...
            $filledShifts = 0;

    public function __construct(){
        for($c = 0; $c < $this->numDays; $c++){
            $dayArray = array();
            for($d = 0; $d < $this->numShifts; $d++){
                $dayArray[$d] = UNFILLED;  //Assign default value to each shift
            }
            $this->days[$c] = $dayArray;
        }
    }

    public function showMonth(){
        $numRows = ceil(($this->numDays + $this->dayOff) / 7);
        echo     '<table class="CalendarTable"><tbody class="CalendarBody"><tr class="CalendarHeaderRow">  
                    <th class="CalendarHeader">Shifts</th><th class="CalendarHeader">Sunday</th><th class="CalendarHeader">Monday</th>
                    <th class="CalendarHeader">Tuesday</th><th class="CalendarHeader">Wednesday</th><th class="CalendarHeader">Thursday</th>
                    <th class="CalendarHeader">Friday</th><th class="CalendarHeader">Saturday</th>
                </tr>'; //table head
        for ($c = 0; $c < $numRows; $c++) {
            // echo '<tr><td>Early Day<br>Day<br>Midshift<br>Night</td>'; //Right column
            for ($d = 0; $d < 7; $d++) {     //Loops through array of days
                $actD = ($c * 7) + $d - $this->dayOff; //Takes into account dayoff in month
                if($actD >= $this->numDays || $actD < 0){
                    echo '<td></td>';
                }else{
                    $dayArray = $this->days[$actD];
                    echo '<td>';
                    for($shift = 0; $shift < count($dayArray); $shift++){  //Loops through shifts in day
                        $wid = $dayArray[$shift];
                        $foundEmployee = null;
                        foreach (Employee::$employees as $emp) { //Look for employee based on id
                            if($emp->getID() == $wid){
                                $foundEmployee = $emp;
                                break;
                            }
                        }
                        if(is_null($foundEmployee)){ //employee not found
                            echo 'unfilled';
                        }else{                         //employee found
                            echo $foundEmployee->first_name; 
                        }
                        if($shift < count($dayArray) - 1) echo '<br>';
                    }
                    echo '</td>';
                }
            }
            echo '</tr>';
        }

        echo '</tbody></table>';

    }

    public function &getDays(){
        return $this->days;
    }

    public function getMonthEmployeeNames(){

        $returnArray = array();

        for($day = 0; $day < $this->numDays; $day++){
            $dayArray = $this->days[$day];
            $returnDayArray = array();
            for($shift = 0; $shift < count($dayArray); $shift++){  //Loops through shifts in day
                $wid = $dayArray[$shift];
                $foundEmployee = null;
                foreach (Employee::$employees as $emp) { //Look for employee based on id
                    if($emp->getID() == $wid){
                        $foundEmployee = $emp;
                        break;
                    }
                }
                if(is_null($foundEmployee)){ //employee not found
                    $returnDayArray[$shift] = 'Unfilled';
                }else{                         //employee found
                    $returnDayArray[$shift] = $foundEmployee->first_name; 
                }
            }
            $returnArray[$day] = $returnDayArray;
        }

        return $returnArray;
    }

    public function getNumDays(){
        return $this->numDays;
    }

    public function getNumShifts(){
        return $this->numShifts;
    }

    public function getDayOff(){
        return $this->dayOff;
    }

    public function isDayWeekend($dayIndex){
        $weekDay = ($dayIndex + $this->dayOff) % 7;
        if($weekDay == 0 || $weekDay == 5 || $weekDay == 6){ //Sunday Friday or Saturday
            return true;
        }else{
            return false;
        }
    }

    public function requestShift($id, $requestedShift){
        $day = $requestedShift->day;
        $shift = $requestedShift->shift;
        if($day < 0 || $day >= $this->numDays) return true;

        if($this->days[$day][$shift] != UNFILLED){
            echo 'ERROR::Requested shift not open!!<br>';
            return false;
        }else{
            $this->days[$day][$shift] = $id; 
            $this->filledShifts++;
            return true;
        }
    }

    public function removeShift($removeShift){

        $day = $removeShift->day;
        $shift = $removeShift->shift;
        if($this->days[$day][$shift] != UNFILLED){ //in case shift is already removed
            $this->days[$day][$shift] = UNFILLED;
            $this->filledShifts--;
            return true;
        }else{
            return false;
        }
    }

    public function hasUnfilled(){
        return ($this->filledShifts < $this->numDays * $this->numShifts);
    }
}

class Shift{ //Represents a shift

    public $day, $shift;

    public function __construct($day, $shift){
        $this->day = $day;
        $this->shift = $shift;
    }

    public function isAjacentTo($aShift){ //Returns -1 if ajacent to the left +1 if ajacent to the right and 0 if not ajacent
        $difference = $aShift->day - $this->day;
        if($difference < -1 || $difference > 1){
            return 0;
        }else{
            return $difference;
        }
    }

    public function equals($oShift){
        if($oShift->day == $this->day && $oShift->shift == $this->shift){
            return true;
        }else{
            return false;
        }
    }
}

class ShiftString{ //Represents a string of shifts in a row

    public $members = array();
    private $lastMember, $firstMember;
    private $hasShift = false;

    public function __construct($shift){
        $this->members[0] = $shift;
        $this->lastMember = $shift;
        $this->firstMember = $shift;
    }

    public function testShiftForJoin($testShift){
        if($this->firstMember->isAjacentTo($testShift) < 0){

            //test for shift in shift
            if($this->firstMember->shift != $testShift->shift){
                $this->hasShift = true;
            }

            $this->firstMember = $testShift;
            array_splice($this->members, 0, 0, array($testShift));
            return true;
        }else if($this->lastMember->isAjacentTo($testShift) > 0){

            //test for shift in shift
            if($this->lastMember->shift != $testShift->shift){
                $this->hasShift = true;
            }

            $this->lastMember = $testShift;
            array_push($this->members, $testShift);
            return true;
        }else{
            return false;
        }

    }

    public function removeShift($rShift){ //return the new size of the shift
        for($c = count($this->members) - 1; $c >= 0 ; $c--){
            $shi = $this->members[$c];
            if($shi->equals($rShift)){
                array_splice($this->members, $c, 1);
                //First member
                if(count($this->members) == 0){ //Empty string
                    break;
                }else if($c == 0) {
                    $this->firstMember = $this->members[0];
                }else if($c == count($this->members)) { //because again we've removed one
                    $this->lastMember = $this->members[count($this->members) - 1]; //because it has already been removed
                }
                break;
            }
        }
        //in case there is no shift to remove
        return $this->getSize();
    }

    public function getSize(){
        return count($this->members);
    }

    public function getFirstMember(){
        return $this->firstMember;
    }

    public function getLastMember(){
        return $this->lastMember;
    }

    public function hasShift(){
        return $this->hasShift;
    }

}

?>