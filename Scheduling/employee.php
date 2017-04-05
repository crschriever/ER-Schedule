<?php

class Employee{

    static $MAX_ID = 1000;
    static $employees = array();
    static $scheduleManager;
    public $defaultOptions = array("NUMBER_OF_SHIFTS" => 10, "STRING_LENGTHS" => array(4, 4, 3, 3),
                         "SHIFT_DISTRIBUTION" => array(-1, -1, .25, .25), //Maximum number of each shift (-1 doesn't matter)
                         "DAY_DISTRIBUTION" => array(-1, -1, -1, -1, -1, -1, -1), //Just -1 and 0 for now could be improved
                         "SET_SHIFTS" => array(), "SET_OFFS" => array(), "SHIFTS_LOCKED" => false
                        );
    public $vars = array("NEVER" => -100000, 
                         "DAY_BETWEEN" => array(-50, -15, 0, 0, 0),
                         "SHIFT_PREFERENCE" => array(10, 10, 0, 0),
                         "TOO_MANY_SHIFT_TYPE" => -50,
                         "STRING_ALREADY_SHIFTED" => -70,
                         //Adjacent shift array corresponds to which days can be adjacent
                         "ADJACENT_SHIFT" => array(array(2, -2, -35, -35), array(-2, 2, -35, -35), array(-100, -70, 2, -2), array(-150, -90, -2, 20)),
                         "STRING_TOO_SMALL" => 15, "STRING_CLOSE" => 8,
                         "STRING_TOO_BIG" => -10, "STRING_WAY_TOO_BIG" => -20,
                         "TOO_FEW_SHIFTS" => 100, "ENOUGH_SHIFTS" => -5, "TOO_MANY_SHIFTS" => -100,
                         "KEEP_WEEKENDS_TOGETHER" => 15, "DONT_INVADE_WEEKENDS" => -25
                        );

    private $id;
    public $first_name;
    public $shifts = array();
    private $shiftsOutsideOfMonth = 0;
    public $shiftDistribution = array(0, 0, 0, 0);
    public $shiftStrings = array();

    private $topOptions = array();
    private $topValue = 0;

    public function __construct($first_name, $defaultChanges){
        $this->first_name = $first_name; //Set ID and make sure each employee ID is unique. This will change when SQL databases are included
        $this->id = $this::$MAX_ID++;
        foreach ($defaultChanges as $name => $value) {
            $this->defaultOptions[$name] = $value;
        }

        foreach ($this->defaultOptions["SET_SHIFTS"] as $shift) {
            if(Employee::$scheduleManager->requestShift($this->id, $shift)){ //request the shift. Returns true if request succedes
                $this->addShift($shift); //If succedes add shift
                if($shift->day < 0 || $shift->day >= Employee::$scheduleManager->getNumDays()) $this->shiftsOutsideOfMonth++;
            }
        }
    }

    public function getID(){
        return $this->id;
    }

    public function assignShiftWeights(){

        if($this->defaultOptions["SHIFTS_LOCKED"]) { //No changes should be made
            echo 'ERROR :: trying to change a employee\'s shifts that is locked';
            return;
        }

        $options = array();
        $month = Employee::$scheduleManager->getDays();

        //Calculates if there are too many of any one shift
        $shiftWeight = array();
        $desiredShiftCount = $this->defaultOptions["NUMBER_OF_SHIFTS"];
        for($shift = 0; $shift < Employee::$scheduleManager->getNumShifts(); $shift++){
            if($this->defaultOptions["SHIFT_DISTRIBUTION"][$shift] < 0){ //Weight doesn't matter
                $shiftWeight[$shift] = 0;
            }else if($this->defaultOptions["SHIFT_DISTRIBUTION"][$shift] == 0){ //Make sure a shift is never taken if 0
                 $shiftWeight[$shift] = $this->vars["NEVER"];
            }else if($this->shiftDistribution[$shift] / max($desiredShiftCount, $this->getNumShiftsTaken() + 1) > $this->defaultOptions["SHIFT_DISTRIBUTION"][$shift]){ //TOO MANY OF THIS SHIFT
                $shiftWeight[$shift] = $this->vars["TOO_MANY_SHIFT_TYPE"];
            }else{ //COULD ACCEPT MORE OF THIS SHIFT
                $shiftWeight[$shift] = 0;
            }
        }

        //Set Weights going through each day and shift owned or not
        for($day = 0; $day < count($month); $day++){
            $shiftAmount = count($month[$day]);
            $shifts = array();

            $dayDesiredWeigth = $this->defaultOptions["DAY_DISTRIBUTION"][$day % 7];

            for($shift = 0; $shift < $shiftAmount; $shift++){
                //based on number of shifts
                if($this->getNumShiftsTaken() < $desiredShiftCount - 1){ //Not enough shifts
                    $shifts[$shift] = $this->vars["TOO_FEW_SHIFTS"];
                }else if($this->getNumShiftsTaken() >= $desiredShiftCount - 1 && $this->getNumShiftsTaken() < $desiredShiftCount){ //About Right
                    $shifts[$shift] = $this->vars["ENOUGH_SHIFTS"];
                }else{ //Too Many
                    $shifts[$shift] = $this->vars["TOO_MANY_SHIFTS"];
                }

                //based on which shifts are already filled
                if($month[$day][$shift] != UNFILLED){
                    $shifts[$shift] += $this->vars["NEVER"]; //Filled so don't go here
                }

                //based on how many of each shift type
                $shifts[$shift] += $shiftWeight[$shift];

                //based on which day the shift falls on and the employees shift preferences
                if($dayDesiredWeigth == 0){
                    $shifts[$shift] += $this->vars["NEVER"];
                }
            }
            $options[$day] = $shifts;
        }

        //Set weight based on requested off days
        foreach ($this->defaultOptions["SET_OFFS"] as $shift) {
            $this->options[$shift->day][$shift->shift] = $this->vars["NEVER"];
        }

        //Sets weights for each shift going through each shift already owned
        for($c = 0; $c < count($this->shifts); $c++){
            $sh = $this->shifts[$c];
            $day = $sh->day;
            $shift = $sh->shift;

            //Shifts owned before the month
            if($day < 0 || $day >= Employee::$scheduleManager->getNumDays())continue;

            $optionsDay = $options[$day];
            //No more than one per day
            for($shift = 0; $shift < count($optionsDay); $shift++){
                $optionsDay[$shift] += $this->vars["NEVER"];
            }
            $options[$day] = $optionsDay;
        }

        //Sets weights for each shift going through shift strings
        $optimalStringLengths = $this->defaultOptions["STRING_LENGTHS"];
        $optimalAdjacentShift = $this->vars["ADJACENT_SHIFT"];
        for ($c = 0; $c < count($this->shiftStrings); $c++) { 
            $shiftString = $this->shiftStrings[$c];
            $stringFirst = $shiftString->getFirstMember();
            $stringLast = $shiftString->getLastMember();
            $noLeft = $noRight = false; //If the day to the left or right exists
            if($stringFirst->day <= 0 || $stringFirst->day > Employee::$scheduleManager->getNumDays()){
                $noLeft = true;
            }
            if($stringLast->day >= Employee::$scheduleManager->getNumDays() - 1 || $stringLast->day < -1){
                $noRight = true;
            }

            if(!$noLeft)$shiftLeft = new Shift($stringFirst->day - 1, $stringFirst->shift);
            if(!$noRight)$shiftRight = new Shift($stringLast->day + 1, $stringLast->shift);

            $shiftInString = $shiftString->hasShift(); //Already changes which shift is in the string

            for($shi = 0; $shi < Employee::$scheduleManager->getNumShifts(); $shi++){
                
                //Set Weights for left and right day based on which shift it is
                //Set Weights for Left and Right day based on if its a weekend
                //Set Weights based on string size
                //Set Weights for if there is already a shift in a string

                if(!$noLeft){
                    $options[$shiftLeft->day][$shi] += $optimalAdjacentShift[$shi][$stringFirst->shift];
                    if(Employee::$scheduleManager->isDayWeekend($stringFirst->day)){ 
                        //First String day is a weekend and this is a weekend
                        if(Employee::$scheduleManager->isDayWeekend($shiftLeft->day))$options[$shiftLeft->day][$shi] += $this->vars["KEEP_WEEKENDS_TOGETHER"];
                        //First String day is a weekend is and this is not a weekend
                        else $options[$shiftLeft->day][$shi] += 0; //$this->vars["KEEP_WEEKENDS_TOGETHER"];
                    }else{
                        //First String day isn't a weekend and this is a weekend
                        if(Employee::$scheduleManager->isDayWeekend($shiftLeft->day))$options[$shiftLeft->day][$shi] += $this->vars["DONT_INVADE_WEEKENDS"];
                        //First String day isn't a weekend is this is not a weekend
                        else $options[$shiftLeft->day][$shi] += 0; //$this->vars["KEEP_WEEKENDS_TOGETHER"];
                    }

                    if($shiftString->getSize() < $optimalStringLengths[$stringFirst->shift] - 1){ //If the string length is less than one optimal - 1: really want more  
                        $options[$shiftLeft->day][$shi] += $this->vars["STRING_TOO_SMALL"];
                    }else if($shiftString->getSize() >= $optimalStringLengths[$stringFirst->shift] - 1 && $shiftString->getSize() <= $optimalStringLengths[$stringFirst->shift]){ //String size about right: could have more but its not a bad thing
                        $options[$shiftLeft->day][$shi] += $this->vars["STRING_CLOSE"];
                    }else if($shiftString->getSize() >= $optimalStringLengths[$stringFirst->shift] - 1 && $shiftString->getSize() <= $optimalStringLengths[$stringFirst->shift]){ //String a little big
                        $options[$shiftLeft->day][$shi] += $this->vars["STRING_TOO_BIG"];
                    }else{ //String way too big
                        $options[$shiftLeft->day][$shi] += $this->vars["STRING_WAY_TOO_BIG"];
                    }

                    if($shiftInString && $stringFirst->shift != $shi){
                        $options[$shiftLeft->day][$shi] += $this->vars["STRING_ALREADY_SHIFTED"];
                    }
                }
                if(!$noRight){
                    $options[$shiftRight->day][$shi] += $optimalAdjacentShift[$stringLast->shift][$shi];
                    if(Employee::$scheduleManager->isDayWeekend($stringLast->day)){ 
                        //Last String day is a weekend and this is a weekend
                        if(Employee::$scheduleManager->isDayWeekend($shiftRight->day))$options[$shiftRight->day][$shi] += $this->vars["KEEP_WEEKENDS_TOGETHER"];
                        //Last String day is a weekend and this is not a weekend
                        else $options[$shiftRight->day][$shi] += 0;//$this->vars["KEEP_WEEKENDS_TOGETHER"];
                    }else{
                        //Last String day isn't a weekend and this is a weekend
                        if(Employee::$scheduleManager->isDayWeekend($shiftRight->day))$options[$shiftRight->day][$shi] += $this->vars["DONT_INVADE_WEEKENDS"];
                        //Last String day isn't a weekend is this is not a weekend
                        else $options[$shiftRight->day][$shi] += 0; //$this->vars["KEEP_WEEKENDS_TOGETHER"];
                    }

                    if($shiftString->getSize() < $optimalStringLengths[$stringLast->shift] - 1){ //If the string length is less than one optimal - 1: really want more  
                        $options[$shiftRight->day][$shi] += $this->vars["STRING_TOO_SMALL"];
                    }else if($shiftString->getSize() >= $optimalStringLengths[$stringLast->shift] - 1 && $shiftString->getSize() <= $optimalStringLengths[$stringLast->shift]){ //String size about right: could have more but its not a bad thing
                        $options[$shiftRight->day][$shi] += $this->vars["STRING_CLOSE"];
                    }else if($shiftString->getSize() >= $optimalStringLengths[$stringLast->shift] - 1 && $shiftString->getSize() <= $optimalStringLengths[$stringLast->shift]){ //String a little big
                        $options[$shiftRight->day][$shi] += $this->vars["STRING_TOO_BIG"];
                    }else{ //String way too big
                        $options[$shiftRight->day][$shi] += $this->vars["STRING_WAY_TOO_BIG"];
                    }

                    if($shiftInString && $stringLast->shift != $shi){
                        $options[$shiftRight->day][$shi] += $this->vars["STRING_ALREADY_SHIFTED"];
                    }
                }
            }

            //Don't let strings get too close to each other
            $optimalDaysBetween = $this->vars["DAY_BETWEEN"];
            for($db = 0; $db < count($optimalDaysBetween); $db++){
                //-2 - $db and +2 + $db because we want to skip the immediately ajacent shift
                $leftDay = $stringFirst->day - 2 - $db; 
                $rightDay = $stringLast->day + 2 + $db;
                if($leftDay >= 0 && $leftDay < Employee::$scheduleManager->getNumDays()){ //Not before the month starts
                    for($shift = 0; $shift < Employee::$scheduleManager->getNumShifts(); $shift++){
                        $options[$leftDay][$shift] += $optimalDaysBetween[$db];
                    }
                }
                if($rightDay < Employee::$scheduleManager->getNumDays() && $rightDay >= 0){
                    for($shift = 0; $shift < Employee::$scheduleManager->getNumShifts(); $shift++){
                        $options[$rightDay][$shift] += $optimalDaysBetween[$db];
                    }
                }
            }
        }

        //$this->showMonth($options);

        unset($this->topOptions);
        $this->topOptions = array(); //All of the top options (if more than one shift has same value pick randomly)
        $this->topValue = $options[0][0];
        for($day = 0; $day < count($options); $day++){
            $shifts = $options[$day]; 
            for($shift = 0; $shift < count($shifts); $shift++){
                $nextValue = $shifts[$shift];
                if($nextValue == $this->topValue){
                    array_push($this->topOptions, $day, $shift); //push the index
                }else if($nextValue > $this->topValue){
                    $this->topOptions = array(); //clear array
                    $this->topValue = $nextValue;
                    array_push($this->topOptions, $day, $shift); //push the index
                }
            }
        }
    }

    public function chooseShift($lowerCutoff, $shiftsTaken){  //Choose a shift if it is greater than the lower cutoff

        if($this->defaultOptions["SHIFTS_LOCKED"]) { //No changes should be made
            echo 'ERROR :: trying to change a employee\'s shifts that is locked';
            return;
        }

        if($this->topValue < $lowerCutoff){
            //echo 'Candidate skipped because no good options <br> Best Option:' . $this->topValue . '<br>';
            return false; //Best Option too low
        }else{
            //remove taken shifts
            for($takenShift = 0; $takenShift < count($shiftsTaken); $takenShift++){
                $tShift = $shiftsTaken[$takenShift];
                for($checkShift = count($this->topOptions) - 2; $checkShift >= 0 ; $checkShift -= 2){  //-= 2 because this array is set up like (day, shift, day 2, shift2...)
                    if($tShift->day == $this->topOptions[$checkShift] && $tShift->shift == $this->topOptions[$checkShift + 1]){
                        array_splice($this->topOptions, $checkShift, 2);
                        break;
                    }
                }
            }

            //No shifts left
            if(count($this->topOptions) <= 0){
                return false; //No options left
            }else{ //shifts left

                $index = rand(0, (count($this->topOptions) / 2) - 1); //Random shift from the best taking into account that they're stored day, shift
                $requestShift = new Shift($this->topOptions[$index * 2], $this->topOptions[$index * 2 + 1]);
                if(Employee::$scheduleManager->requestShift($this->id, $requestShift)){ //request the shift. Returns true if request succedes
                    $this->addShift($requestShift); //If succedes add shift
                    return $requestShift; //Success
                }
            }
            echo 'ERROR: This code should not be reached!! employee.php<br>';
            return true; //Should never happen but the requested shift is not removed but is taken in the month
        }
    }

    public function removeExtraShifts(){

        if($this->defaultOptions["SHIFTS_LOCKED"]) { //No changes should be made
            echo 'ERROR :: trying to change a employee\'s shifts that is locked';
            return;
        }

        if(count($this->shifts) < $this->defaultOptions["NUMBER_OF_SHIFTS"] - 1) {
            return;
        }

        for($c = 0; $c < count($this->shiftStrings); $c++){
            $string = $this->shiftStrings[$c];
            if($string->getSize() > 3){
                $this->removeShift($string->getFirstMember());
                $this->removeShift($string->getLastMember());
            }
        }
    }

    public function removeBadShifts() {

        if($this->defaultOptions["SHIFTS_LOCKED"]) { //No changes should be made
            echo 'ERROR :: trying to change a employee\'s shifts that is locked';
            return;
        }

        $shiftRemoved = false;

        //Check to make sure the right amount of shifts are taken
        if(abs($this->getNumShiftsTaken() - $this->defaultOptions["NUMBER_OF_SHIFTS"]) > 1) {
            $shiftRemoved = true;
        }

        //Go through strings to look for problems
        for($c = 0; $c < count($this->shiftStrings); $c++){
            $string = $this->shiftStrings[$c];

            if($string->getSize() == 1 && $string->getFirstMember()->shift == 3
                && $string->getFirstMember()->day < Employee::$scheduleManager->getNumDays() - 1){ //One night stands
                    
                if($this->removeShift($string->getFirstMember())) {
                     $shiftRemoved = true;
                }
            }else if($string->getSize() > $this->defaultOptions["STRING_LENGTHS"][$string->getFirstMember()->shift] + 1){ //Strings too long
                if($this->removeShift($string->getFirstMember()) &&
                    $this->removeShift($string->getLastMember())){
                    
                    $shiftRemoved = true;
                }
            }else if($string->getSize() < 3 && $string->getLastMember()->day < Employee::$scheduleManager->getNumDays() - 1){ //Strings too small
                //Don't remove any if you can't remove one
                $cantRemove = false;
                for($s = 0; $s < $string->getSize(); $s++){
                    if(!$this->canRemoveShift($string->getFirstMember())){
                        $cantRemove = true;
                    }
                }

                if(!$cantRemove){
                    $beginningSize = $string->getSize();
                    for($s = 0; $s < $beginningSize; $s++){
                        $this->removeShift($string->getFirstMember(), true);
                    }
                    //$shiftRemoved = true;
                }
            }
        }

        if($shiftRemoved) {
            echo $this->first_name . ' : ' . count($this->shifts) . '<br>';
        }

        return $shiftRemoved;
    }

    public function addShift($requestShift){

        array_push($this->shifts, $requestShift); 
        $this->shiftDistribution[$requestShift->shift]++; //Add one to the distribution of the right shift
        //Test Shifts strings for fit

        $fitIntoString = false;
        for($c = 0; $c < count($this->shiftStrings); $c++){
            $shiftString = $this->shiftStrings[$c];
            if($shiftString->testShiftForJoin($requestShift)){
                $fitIntoString = true;
                break;
            }
        }

        if (!$fitIntoString) {
            array_push($this->shiftStrings, new ShiftString($requestShift));
        }
    }

    public function canRemoveShift($rShift) {
        if($this->defaultOptions["SHIFTS_LOCKED"]) { //No changes should be made
            echo 'ERROR :: trying to change a employee\'s shifts that is locked';
            return false;
        }

        //Check for shift in set shifts
        for ($c = 0; $c < count($this->defaultOptions["SET_SHIFTS"]); $c++) { 
            if($rShift->equals($this->defaultOptions["SET_SHIFTS"][$c])) {
                return false;
            }
        }

        return true;
    }

    public function removeShift($rShift, $noNeedToCheck = false){

        if(!$noNeedToCheck && !$this->canRemoveShift($rShift)){
            return false;
        }

        for($c = 0; $c < count($this->shifts); $c++){ //remove from shift array
            $shi = $this->shifts[$c];
            if($rShift->equals($shi)){
                array_splice($this->shifts, $c, 1);
                break;
            }
        }

        $this->shiftDistribution[$rShift->shift]--; //Subtract one from the distribution of the right shift
        
        //Test Shifts strings for fit
        for($c = count($this->shiftStrings) - 1; $c >= 0; $c--){
            $shiftString = $this->shiftStrings[$c];
            if($shiftString->removeShift($rShift) <= 0){
                array_splice($this->shiftStrings, $c, 1);
            }
        }

        //Let the month know
        Employee::$scheduleManager->removeShift($rShift);
        return true;
    }

    public function showMonth($month){
        $numRows = ceil((count($month)) / 7);
        echo     '<table><br><strong>' . $this->first_name . ': ' . $this->getNumShiftsTaken() . '</strong><tr>  
                    <th>Shifts</th><th>Sun</th><th>Mon</th><th>Tues</th><th>Wed</th><th>Thur</th><th>Fri</th><th>Sat</th>
                </tr>'; //table head
        for ($c = 0; $c < $numRows; $c++) {
            echo '<tr><td>Early Day<br>Day<br>Midshift<br>Night</td>'; //Right column
            for ($d = 0; $d < 7; $d++) {     //Loops through array of days
                $actDay = $d + $c * 7;
                if($actDay >= count($month))break; //Break if we've passed the number of days in the month
                $dayArray = $month[$actDay];
                echo '<td>';
                for($shift = 0; $shift < count($dayArray); $shift++){  //Loops through shifts in day
                    $ownsShift = false;
                    for($s = 0; $s < count($this->shifts); $s++){
                        $shi = $this->shifts[$s];
                        if($shi->day == $actDay && $shi->shift == $shift){ //Employee owns this shift
                            $ownsShift = true;
                            break;
                        }
                    }
                    if($ownsShift){
                        echo 'Owned';
                    }else{
                        echo $dayArray[$shift];
                    }
                    if($shift < count($dayArray) - 1) echo '<br>';
                }
                echo '</td>';
            }
            echo '</tr>';
        }

    }

    public function getNumShiftsTaken() {
        return count($this->shifts) - $this->shiftsOutsideOfMonth;
    }

    public function getNumShiftsLeft(){
        return $this->defaultOptions["NUMBER_OF_SHIFTS"] - $this->getNumShiftsTaken();
    }

    public function isShiftsLocked(){
        return $this->defaultOptions["SHIFTS_LOCKED"];
    }

    public static function sort_d($a, $b){
        if ($a->getTopValue() == $b->getTopValue()) {
            if($a->getTopCount() == $b->getTopCount()){
                return 0;
            }
            return ($a->getTopCount() > $b->getTopCount()) ? 1 : -1;
        }
        return ($a->getTopValue() > $b->getTopValue()) ? 1 : -1; //should sort descending
    }

    public static function sort_a($a, $b){
        if ($a->getTopValue() == $b->getTopValue()) {
            if($a->getTopCount() == $b->getTopCount()){
                return 0;
            }
            return ($a->getTopCount() > $b->getTopCount()) ? 1 : -1; //If the same always do the one with the lowest amount of options
        }
        return ($a->getTopValue() > $b->getTopValue()) ? -1 : 1; //should sort ascending
    }

    public function getTopValue() {
        return $this->topValue;
    }

    public function getTopCount() {
        return count($this->topOptions);
    }
}

function createEmployees(){
    //Right now creates randomly
    //In future this will funciton differently
    global $employeeData;

    if(!isset(Employee::$scheduleManager)) {
        echo 'IMPORTANT:: Employee::$scheduleManager must first be set first';
    }

    for ($c=0; $c < count($employeeData); $c++) { 
        $empData = $employeeData[$c];
        Employee::$employees[$c] = new Employee($empData["Name"], $empData["DefaultChanges"]);
    }

}

function displayEmployees(){
    foreach (Employee::$employees as $employee) {
        echo '<br>' . $employee->first_name . ' has ' . $employee->getNumShiftsTaken() . ' shifts<br>';
        echo 'Distribution: ';
        for($c = 0; $c < count($employee->shiftDistribution); $c++){
            echo $employee->shiftDistribution[$c] . ', ';
        }
        $empStrings = $employee->shiftStrings;
        echo 'has ' . count($empStrings) . ' strings<br>';
        foreach ($employee->shiftStrings as $string) {
            echo 'String Start: ' . $string->getFirstMember()->day . ' Lenght: ' . $string->getSize() . ' Last Member: ' . $string->getLastMember()->day . '<br>';
            echo 'String has Shift?: ' . ($string->hasShift() ? 'YES' : 'NO') . '<br>';
        }
    }
}

?>