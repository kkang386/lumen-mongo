<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use Log;

/*
* Controller class for handling person's birthdate requests.
*/
class PersonController extends Controller
{   
    public \DateTime $currentDate;

    /*
     * Constructor function 
     * takes a optional current date parameter if provided  
     * set up the current date use the current time
     * 
     * @param {DateTime} $args['currentDate']: current date
     */
    public function __construct($args = []) {
        $this->currentDate = isset($args['currentDate'])? $args['currentDate'] : new \DateTime();
    }

    /*
    * function isBirthdate checkes if the date is birthday today
    * @param {DateTime} birthdate: a person's birthdate
    * @return {bool}: if it is currently a birth day
    */
    public function isBirthdate(\DateTime $birthdate): bool {
        $this->currentDate->setTimezone($birthdate->getTimezone());
        return ($birthdate->format('m-d') == $this->currentDate->format('m-d'));
    }
    
    /*
    * function nextBirthdate
    * compute the next birth day based on the provided birth date and current time(setup by the controller)
    * @param {DateTime} birthdate: a person's birth date
    * @return {DateTime} : when is the nearest next time should the person has a birth day.
    *   if it is today, return today's date.
    */
    public function nextBirthdate(\DateTime $birthdate): \DateTime {
        $this->currentDate->setTimezone($birthdate->getTimezone());
        $todayMonth = intval($this->currentDate->format("m"));
        $todayDay = intval($this->currentDate->format("d"));
        $year = intval($this->currentDate->format("Y"));
        $birthMonth = intval($birthdate->format("m"));
        $birthDay = intval($birthdate->format("d"));

        // assume coming birth day is in the current year, create that datetime
        $nextBirthDayStr = sprintf("%d-%d-%d 00:00:00", $year, $birthMonth, $birthDay);
        $newBirthDay = new \DateTime($nextBirthDayStr, $birthdate->getTimezone());

        // if in the same timezone, the current month and day is the birth month and day
        // return the created birthdate
        // or if created(assumed) coming birth day is in future
        // return the created birthdate
        if (($todayMonth == $birthMonth &&
             $todayDay == $birthDay) ||
            ($newBirthDay > $this->currentDate)) {
            return $newBirthDay;
        }

        // The person's birth day of this year is already passed.
        // Add a year, and create new time string.
        $year += 1;
        $nextBirthDayStr = sprintf("%d-%d-%d 00:00:00", $year, $birthMonth, $birthDay);
        $newBirthDay = new \DateTime($nextBirthDayStr, $birthdate->getTimezone());
        return $newBirthDay;
    }
    
    /*
    * function createMessage
    * @param {Array} rec: a person's record containing 
    *       [
    *       "name" {string},
    *       "birthdate" {string},
    *       "timezone" {string},
    *       "interval" {Array of time interval},
    *       "isBirthday" {bool},
    *       ];
    * @param {DateTime} birthdate: a person's birthdate in DateTime 
    * @return {string} : a message indicate persona' age and time remaining for the 
    *   nearest birthday coming in future or taking place today.
    */
    public function createMessage(Array $rec, \DateTime $birthdate): String {
        $this->currentDate->setTimezone($birthdate->getTimezone());
        $age = intval($this->currentDate->format('Y')) - intval($birthdate->format('Y'));
        $message = '';
        if ($rec['isBirthday']) {
            $hoursRemaining = 24 - intval($this->currentDate->format('H'));
            $message = sprintf("%s is %d years old today (%d hours remaining in %s).",
                $rec['name'], $age, $hoursRemaining, $rec['timezone'] );
        } else {
            if ($rec['interval']['y'] == 0 && 
                $rec['interval']['m'] == 0 &&
                $rec['interval']['d'] == 0) {
                    $message = sprintf("%s is %d years old in %d hours %d minutes in %s.",
                    $rec['name'], $age, $rec['interval']['h'], $rec['interval']['i'], $rec['timezone'] );
            } else {
                $message = sprintf("%s is %d years old in %d months, %d days in %s.",
                    $rec['name'], $age + 1, $rec['interval']['m'], $rec['interval']['d'], $rec['timezone'] );
            }
        }
        return $message;
    }

    /*
    * function birthdateRecord
    * @param {Person} $person: a person'a full birthdate record from the ORM model
    * @return {Array}: a person's full information including a message indicate persona' 
    * age and time remaining for the nearest birthday coming in future or taking place today.
    */
    public function birthdateRecord(Person $person): Array {
        $rec = [];
        $rec['name'] = $person->name;
        $rec['birthdate'] = $person->birthdate;
        $rec['timezone'] = $person->timezone;

        $birthdate = new \DateTime($person->birthdate, new \DateTimeZone($person->timezone));
        $nextBirthday = $this->nextBirthdate($birthdate);
        $interval = $this->currentDate->diff($nextBirthday);
        $rec['interval'] = [
            'y' => intval($interval->y),
            'm' => intval($interval->m),
            'd' => intval($interval->d),
            'h' => intval($interval->h),
            'i' => intval($interval->i),
            's' => intval($interval->s),
            '_comment' => sprintf("f: %f", $interval->f)
        ];
        $rec['isBirthday'] = $this->isBirthdate($birthdate);
        $rec['message'] = $this->createMessage($rec, $birthdate);
        return $rec;
    }

    /*
    * function show()
    * HTTP GET request handler
    * @param {}
    * @return {JsonResponse}: return all upcoming birthday records in 
    * the system in JSON
    */
    public function show(): JsonResponse {
        $people = Person::query()->get();

        $records = [];
        foreach ($people as $person) {
            $records[] = $this->birthdateRecord($person);
        }
        return response()->json($records);
    }

    /*
    * function store() 
    * HTTP POST request handler
    * Validate input, save the good records to db.
    *
    * @param {Request} request: HTTP request object received
    * @return {JsonResponse} : status 201 with JSON object on success,
    *   or with status 401 on any errors, with error message
    *   indicating the reason of the rejection. 
    */
    public function store(Request $request): JsonResponse {
        
        // Setup the input validation rules.
        // unique challenge: if I want to validate the birthday is not in future,
        // there isn't a way to combine two inputs (birthdate and timezone) input into
        // one validation rule. Something to think about how to do..
        $validator = Validator::make($request->all(),
            [
                'name' => 'required|max:255|name_string',
                'birthdate' => 'required|datetime_string',
                'timezone' => 'required|timezone_string'
            ],
            [
                'name.name_string' => "Name may only contain letters and spaces.",
                'birthdate.datetime_string' => "Invalid birth date format. Please use this format: 'YYYY-MM-DD HH:MM:SS'",
                'timezone.timezone_string' => "Invalid timezone."
            ]
        );

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $person = new Person();
        $person->name = $request->name;
        $person->birthdate = $request->birthdate;
        $person->timezone = $request->timezone;

        $person->save();
        return response()->json(["created" => true], 201);
    }
}
