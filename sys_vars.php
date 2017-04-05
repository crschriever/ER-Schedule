<?php
    //define('BASE_PATH', ($_SERVER["DOCUMENT_ROOT"] . "/"));
    define('BASE_PATH', '/');
    define('UNFILLED', -1);

    /*$randomNames = array('Rogelio Grabowski', 'Dotty Leith', 'Magdalen Mose','Jamison Chapple', 'Norine Westerlund', 'Hunter Celestine', 'Sena Doughtie', 'Marcie Brault', 'Petronila Darrah', 'Blake Kovats', 'Melonie Garney', 'Weldon Weedon', 'Jackeline Betancourt', 'Ray Humphrey', 'Shanel Wooldridge', 'Debbi Gaier', 'Kassandra Griffen', 'Nelson Vanderford', 'Sally Kellar', 'Everette Tramel');
    $randomLastNames = array('Schriever', 'Malcolm', 'Barnes', 'PJ', 'Viccerilli', 'Hardwell', 'Boulden', 'Saade', 'Anderson', 'Cline');*/
    $employeeData = array(
        array("Name" => 'Schriever', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 6,
             'SET_SHIFTS' => array(new Shift(-7, 1), new Shift(-6, 1), new Shift(-5, 2), new Shift(-4, 2), new Shift(-3, 2), new Shift(-2, 2), new Shift(-1, 2)))),
        array("Name" => 'Malcolm', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 11, "SHIFT_DISTRIBUTION" => array(0, 1, 0, 0),
             'SET_SHIFTS' => array(new Shift(-4, 1), new Shift(-3, 1), new Shift(-2, 1), new Shift(-1, 1)),
             'STRING_LENGTHS' => array(5, 5, 4, 4))),
        array("Name" => 'Barnes', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 13,
             'SET_SHIFTS' => array(new Shift(-13, 0), new Shift(-12, 1), new Shift(-11, 1), new Shift(-10, 1), new Shift(-9, 1), new Shift(-8, 1)))),
        array("Name" => 'PJ', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 13,
             'SET_SHIFTS' => array(new Shift(-3, 3), new Shift(-2, 3), new Shift(-1, 3),
                new Shift(0, 3), new Shift(5, 3), new Shift(6, 3), new Shift(7, 3),
                new Shift(12, 3), new Shift(13, 3), new Shift(14, 3),
                new Shift(19, 3), new Shift(20, 3), new Shift(21, 3),
                new Shift(26, 3), new Shift(27, 3), new Shift(28, 3)), 'SHIFTS_LOCKED' => true)),
        array("Name" => 'Viccerilli', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 14,
             'SET_SHIFTS' => array(new Shift(-1, 0)))),
        array("Name" => 'Hardwell', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 13,
             'SET_SHIFTS' => array(new Shift(-5, 0), new Shift(-4, 0)))),
        array("Name" => 'Carter', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 13,
             'SET_SHIFTS' => array(new Shift(-10, 0), new Shift(-9, 2), new Shift(-8, 2), new Shift(-7, 2)))),
        array("Name" => 'Bouldin', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 6,
             'SET_SHIFTS' => array(new Shift(-6, 3), new Shift(-5, 3), new Shift(-4, 3), new Shift(1, 3), new Shift(2, 3), new Shift(8, 3), new Shift(9, 3), new Shift(10, 3), new Shift(11, 2)),
             'SHIFTS_LOCKED' => true)),
        array("Name" => 'Saade', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 13,
             'SET_SHIFTS' => array(new Shift(-17, 1), new Shift(-16, 1)))),
        array("Name" => 'Anderson', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 5,
             'SET_SHIFTS' => array(new Shift(-3, 0), new Shift(-2, 0)))),
        array("Name" => 'Jackson', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 11,
             'SET_SHIFTS' => array(new Shift(-9, 0), new Shift(-8, 0), new Shift(-7, 0)))),
    );


    /*$employeeData = array(
        array("Name" => 'Schriever', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 7)),
        array("Name" => 'Malcolm', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 13)),
        array("Name" => 'Barnes', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 14)),
        array("Name" => 'PJ', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 12)),
        array("Name" => 'Viccerilli', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 15)),
        array("Name" => 'Hardwell', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 15)),
        array("Name" => 'Bouldin', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 14)),
        array("Name" => 'Saade', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 12)),
        array("Name" => 'Anderson', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 5)),
        array("Name" => 'Cline', "DefaultChanges" => array("NUMBER_OF_SHIFTS" => 12)),
    );*/
/*

Features:

Reasonable Strings of shifts
Slowly increase the allowance for good moves to a certain extent
Which shifts can be ajacent to each other
Attempt not to split weekends i.e. if you are working saturday you should work sunday
Be able to choose number of shifts each will work per month
Make sure there is no flip floppity inside strings (only one shift change)
Balance number of each shift
Strings repel other night strings
Should be able to block off days for an individual
Be able to manually put someone on the schedule and then have computer build around that person—for example one of our guys for the time being is working every sat-sun-monday night of every month. This may change.

After Check (Its okay to increase or decrease the number of shifts as long it isn't much)
-Nights:no ones

Ideas:

Typically everyone works same amount of weekends

After Check (Its okay to increase or decrease the number of shifts as long it isn't much)
-Nights:no > than 4s
-Short string hopefully won't switch
    -Check the string for a switch and try to pawn that shit off
-Try to pawn off single strings

LOOK AT THE PROBLEM OF ONE NIGHT STANDS AT BEGINNING AND END OF MONTHS

Equal holidays worked

Work on string shift functionality

*/

?>