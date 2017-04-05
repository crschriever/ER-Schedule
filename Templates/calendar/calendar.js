var Calendar = function(){

    this.$calendarTemplate = null; 
    this.$calendarTable = null;
    this.$calendarBody = null;
    this.cells = [];

    this.dayOffset = 0;

    this.setUpCalendar = function(numDays, dayOff, numShifts, id){
        /*        
        <tr class="CalendarWeekRow"></tr>
        <th class="CalendarCell"></th>
        */
        this.dayOffset = dayOff;
        this.$calendarTemplate = $('#CalendarTemplate'); //HTML Template containing div
        this.$calendarTable = this.$calendarTemplate.find('.CalendarTable').clone(); //Table with Weeks as rows
        this.$calendarTable.attr('id', id);
        this.$calendarBody = this.$calendarTable.find('.CalendarBody'); //<tbody> element

        var numRows = Math.ceil((numDays + dayOff) / 7); //Calculating the necessary number of rows
        for(var row = 0; row < numRows; row++){

            //$newRow = $('<tr class="CalendarWeekRow"><td class="CalendarRightCell">Early Day<br>Day<br>Midshift<br>Night</td></tr>');
            $newRow = $('<tr class="CalendarWeekRow"></tr>');

            for(var day = 0; day < 7; day++){ //Each of seven days in a row
                var actD = (row * 7) + day - dayOff;
                $newCell = $('<td class="CalendarCell"></td>');
                $newCell.shifts = []; //Array for each shift
                if(actD >= 0 && actD < numDays){
                    for(var shift = 0; shift < numShifts; shift++){ //Make a span for each shift
                        var $shift = $('<div class="ShiftSpan">Unfilled</div>');
                        $newCell.append($shift);
                        $newCell.shifts.push($shift); 
                    }
                }
                this.cells.push($newCell);
                $newRow.append($newCell);
            }

            this.$calendarBody.append($newRow);

        }

        this.$calendarTable.insertBefore(this.$calendarTemplate);
    };

    this.updateSchedule = function(newMonth){

        //Loop through each cell
        for(var cell = this.dayOffset; cell < this.cells.length; cell++){

            var $cell = this.cells[cell];
            for(var shift = 0; shift < $cell.shifts.length; shift++){

                var newName = newMonth[cell - this.dayOffset][shift];
                $cell.shifts[shift].html(newName);

            }

        }

    }
}