<?php 
require  'Scheduling/schedule.php';
require  'sys_vars.php';
require  'Scheduling/employee.php';

//Header
echo '<!DOCTYPE html>
<html>

<head>
<meta charset="UTF-8">
<title>Schedule</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="author" content="Carl Schriever">
<meta name="description" content="Scheduling">
<meta name="robots" content="index, follow">
<link rel="stylesheet" type="text/css" href="'. BASE_PATH .'schedule/Templates/calendar/calendar.css">
<link rel="shortcut icon" href="/favicon.ico"/>
</head>

<body>
';

$month = new ScheduleMonth();

//Set up HTML Schedule Template
include BASE_PATH . 'Templates/calendar/calendar.html';
echo '
<h3>As of right now all prefereces are hard coded but you can refresh and see different schedules generated. A page for inputing preferences is in the works.</h3>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<script type="text/javascript" src="Templates/calendar/calendar.js">
</script>
<script type="text/javascript">
    // var cal = new Calendar();
    // cal.setUpCalendar(' . $month->getNumDays() . ', ' . $month->getDayOff() . ', '. $month->getNumShifts() . ', ' . 1 . ');
</script>
';

/*echo '
<script type="text/javascript" src="Templates/calendar/calendar.js">
</script>
<script type="text/javascript">
    var cal2 = new Calendar();
    cal2.setUpCalendar(' . $month->getNumDays() . ', ' . $month->getDayOff() . ', '. $month->getNumShifts() . ', ' . 2 . ');
</script>
';*/

echo '
<script type="text/javascript" src="Templates/calendar/calendar.js">
</script>
<script type="text/javascript">
    var cal3 = new Calendar();
    cal3.setUpCalendar(' . $month->getNumDays() . ', ' . $month->getDayOff() . ', '. $month->getNumShifts() . ', ' . 3 . ');
</script>
';

function fillMonth($lowestOptionNumber, $lowestFirst){

    Global $month, $employeeCount;

    while($month->hasUnfilled()){
        $empQueue = array();

        for ($c = 0; $c < $employeeCount; $c++) { 
            $emp = Employee::$employees[$c];
            if($emp->isShiftsLocked()) continue;
            $emp->assignShiftWeights();
            $empQueue[$c] = $emp;
        }

        if($lowestFirst) usort($empQueue, array('Employee', 'sort_d'));
        else usort($empQueue, array('Employee', 'sort_a'));

        unset($shiftsTaken);
        $shiftsTaken = array();
        for ($c = 0; $c < count($empQueue); $c++) {
            if(!$month->hasUnfilled())break; 
            $shiftTaken = $empQueue[$c]->chooseShift($lowestOptionNumber, $shiftsTaken); //either returns the shift or false
            if(!$shiftTaken){ //Shift properly taken
                //echo 'No shift taken<br>';
            }else{
                /*var_dump($shiftTaken);
                $month->showMonth();*/
                array_push($shiftsTaken, $shiftTaken);
            }
        }

        if(count($shiftsTaken) <= 0){
            $lowestOptionNumber -= 10;
        }
    }
}

$month = new ScheduleMonth();
Employee::$scheduleManager = $month; //Set the employee's static schedule manager. Individual Employees use this to request shifts

createEmployees();
$employeeCount = count(Employee::$employees);

/*
//Employees one by one pick days
$empNum = 0;
while($month->hasUnfilled()){
    Employee::$employees[$empNum % $employeeCount]->chooseShifts($month->getDays());
    $empNum++;
}*/

fillMonth(0, true);

echo '
<script type="text/javascript">
    cal.updateSchedule(' . json_encode($month->getMonthEmployeeNames()) . ');
</script> 
';

//displayEmployees();

$numAttempts = 0;

do{

    echo 'attempt: ' . $numAttempts . '<br>';
    $badShift = false;
    $numAttempts++; //Do while for future possible early exit

    for ($c = 0; $c < $employeeCount; $c++) { 
        $emp = Employee::$employees[$c];
        if($emp->isShiftsLocked()) continue;
        else if($emp->removeBadShifts()) {
            $badShift = true;
        }
    }

    /*echo '
    <script type="text/javascript">
        cal2.updateSchedule(' . json_encode($month->getMonthEmployeeNames()) . ');
    </script> 
    ';*/

    if($badShift){
        for ($c = 0; $c < $employeeCount; $c++) { 
            $emp = Employee::$employees[$c];
            if($emp->isShiftsLocked()) continue;
            $emp->removeExtraShifts();
        }
    }


    fillMonth(100, false);

} while($badShift && $numAttempts < 10);

echo 'Number of attempts: ' . $numAttempts . '<br>';

echo '
<script type="text/javascript">
    cal3.updateSchedule(' . json_encode($month->getMonthEmployeeNames()) . ');
</script> 
';

displayEmployees();

//$month->showMonth();

//Tail
echo '</body></html>';
?>
