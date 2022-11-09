<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use Log;

class PersonController extends Controller
{   
    public \DateTime $currentDate;

    public function __construct($args = []) {
        $this->currentDate = isset($args['currentDate'])? $args['currentDate'] : new \DateTime();
    }

    public function isBirthdate(\DateTime $birthdate) {
        $this->currentDate->setTimezone($birthdate->getTimezone());
        return ($birthdate->format('m-d') == $this->currentDate->format('m-d'));
    }
    
    public function nextBirthdate(\DateTime $birthdate): \DateTime {
        $this->currentDate->setTimezone($birthdate->getTimezone());
        $todayMonth = intval($this->currentDate->format("m"));
        $todayDay = intval($this->currentDate->format("d"));
        $year = intval($this->currentDate->format("Y"));
        $birthMonth = intval($birthdate->format("m"));
        $birthDay = intval($birthdate->format("d"));
        $nextBirthDayStr = sprintf("%d-%d-%d 00:00:00", $year, $birthMonth, $birthDay);
        $newBirthDay = new \DateTime($nextBirthDayStr, $birthdate->getTimezone());
        if (($todayMonth == $birthMonth &&
             $todayDay == $birthDay) ||
            ($newBirthDay > $this->currentDate)) {
            return $newBirthDay;
        }

        // add a year
        $year += 1;
        $nextBirthDayStr = sprintf("%d-%d-%d 00:00:00", $year, $birthMonth, $birthDay);
        $newBirthDay = new \DateTime($nextBirthDayStr, $birthdate->getTimezone());
        return $newBirthDay;
    }
    
    public function show(): JsonResponse {
        $people = Person::query()->get();

        $records = [];
        foreach ($people as $person) {
            $rec = [];
            $rec['name'] = $person->name;
            $rec['birthdate'] = $person->birthdate;
            $rec['timezone'] = $person->timezone;

            $birthdate = new \DateTime($person->birthdate, new \DateTimeZone($person->timezone));
            //$this->currentDate->setTimezone($birthdate->getTimezone());
            $nextBirthday = $this->nextBirthdate($birthdate);
            //$rec['nextbday'] = $nextBirthday->format("Y-m-d H:s:i e");
            //$rec['currentDate'] = $this->currentDate->format("Y-m-d H:s:i e");
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
            // check if today is the bday
            $age = intval($this->currentDate->format('Y')) - intval($birthdate->format('Y'));
            if ($this->isBirthdate($birthdate)) {
                $rec['isBirthday'] = true;
                $hoursRemaining = 24 - intval($this->currentDate->format('H'));
                $rec['message'] = sprintf("%s is %d years old today (%d hours remaining in %s).",
                    $rec['name'], $age, $hoursRemaining, $rec['timezone'] );
            } else {
                $rec['isBirthday'] = false;
                if ($rec['interval']['y'] == 0 && 
                    $rec['interval']['m'] == 0 &&
                    $rec['interval']['d'] == 0) {
                        $rec['message'] = sprintf("%s is %d years old in %d hours %d minutes.",
                        $rec['name'], $age + 1, $rec['interval']['h'], $rec['interval']['i'], $rec['timezone'] );
                } else {
                    $rec['message'] = sprintf("%s is %d years old in %d months, %d days in %s.",
                        $rec['name'], $age + 1, $rec['interval']['m'], $rec['interval']['d'], $rec['timezone'] );
                }
            }
            $records[] = $rec;
        }
        return response()->json($records);
    }

    public function store(Request $request): JsonResponse {
        
        $validator = Validator::make($request->all(),
            [
                'name' => 'required|max:255|name_string',
                'birthdate' => 'required|datetime_string',
                'timezone' => 'required|timezone_string'
            ],
            [
                'name.name_string' => "Name may only contain letters",
                'birthdate.datetime_string' => "Invalid birth date format. Please use this format: 'YYYY-MM-DD HH:MM:SS'",
                'timezone.timezone_string' => "Invalid timezone."
            ]
        );

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $person = new Person();
        $person->name = $request->name;
        // $date = new \DateTime($request->birthdate, new \DateTimeZone("UTC") );
        // $person->birthdate = new \MongoDB\BSON\UTCDateTime($date->getTimestamp()*1000);
        $person->birthdate = $request->birthdate;
        $person->timezone = $request->timezone;

        $person->save();
        return response()->json(["created" => true], 201);
    }
}
