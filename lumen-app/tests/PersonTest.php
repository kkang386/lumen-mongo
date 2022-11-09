<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Http\Controllers\PersonController;

class PersonTest extends TestCase
{
    use DatabaseMigrations;

    /*
    * test set person birthdate and timezone
    *
    * @return void
    */
    public function testStorePeopleBirthdate(): void
    {
        $goodRecord = ['name' => 'Sally',
            'birthdate' => '1990-11-20 13:30:00',
            'timezone' => 'America/New_York'
        ];

        $this->post('/person', $goodRecord)
            ->seeJsonEquals([
                'created' => true
            ])->seeStatusCode(201);


        $this->post('/person', ['name' => ''])
            ->seeJsonEquals([
                'name' => ["The name field is required."],
                'birthdate' => ["The birthdate field is required."],
                'timezone' => ["The timezone field is required."]
            ])->seeStatusCode(400);


        $this->post('/person', ['name' => 'Sally1234',
                'birthdate' => 'xyz19901120 13:30:00',
                'timezone' => 'America/New_York'
            ])->seeJsonEquals([
                'birthdate' => ["Invalid birth date format. Please use this format: 'YYYY-MM-DD HH:MM:SS'"],
                'name' => ["Name may only contain letters and spaces."],
            ])->seeStatusCode(400);

        $this->get('/person')->seeStatusCode(200);
        $resp = json_decode($this->response->getContent(), true);
        $this->assertEquals(1, count($resp));
        $this->assertEquals($resp[0]['name'], $goodRecord['name']);
        $this->assertEquals($resp[0]['birthdate'], $goodRecord['birthdate']);
        $this->assertEquals($resp[0]['timezone'], $goodRecord['timezone']);
        $this->assertEquals(count($resp[0]['interval']), 7); 
    }

    /*
    * test controller class functions
    *
    * @return void
    */
    public function testIsBirthdate(): void
    {
        // Test constructor currentDate setup
        $dateTime = new DateTime("2022-11-12 00:05:00", new DateTimeZone("UTC"));
        $personCtrl = new PersonController(['currentDate' => $dateTime]);
        $this->assertEquals($personCtrl->currentDate, $dateTime);

        // Test isBirthdate() with same a timezone
        $dateTime2 = new DateTime("1990-11-12 3:15:00", new DateTimeZone("UTC"));
        $this->assertEquals($personCtrl->isBirthdate($dateTime2), true);

        // Test isBirthdate() with different a timezone
        $dateTime3 = new DateTime("2002-11-12 3:05:00", new DateTimeZone("America/Los_Angeles"));
        $this->assertEquals($personCtrl->isBirthdate($dateTime3), false);

    }

    public function testNextBirthdate(): void
    {
        // Test constructor currentDate setup
        $dateTime = new DateTime("2022-11-12 00:05:00", new DateTimeZone("UTC"));
        $personCtrl = new PersonController(['currentDate' => $dateTime]);
    
        // Test nextBirthdate(): current year, not yet arrive
        $dateTime4 = new DateTime("2001-12-23 01:00:00", new DateTimeZone("UTC"));
        $nextBDate1 = $personCtrl->nextBirthdate($dateTime4);
        $this->assertEquals($nextBDate1->format('Y-m-d'), '2022-12-23');

        // Test nextBirthdate(): same date after timezone adjustment
        $dateTime5 = new DateTime("2001-11-11 09:00:00", new DateTimeZone("America/Los_Angeles"));
        $nextBDate2 = $personCtrl->nextBirthdate($dateTime5);
        $this->assertEquals($nextBDate2->format('Y-m-d'), '2022-11-11');

        // Test nextBirthdate(): just passed, go for another year.
        $dateTime2 = new DateTime("2022-11-12 17:05:00", new DateTimeZone("America/Los_Angeles"));
        $personCtrl2 = new PersonController(['currentDate' => $dateTime2]);
        $dateTime5 = new DateTime("2001-11-12 09:00:00", new DateTimeZone("UTC"));
        $nextBDate2 = $personCtrl2->nextBirthdate($dateTime5);
        $this->assertEquals($nextBDate2->format('Y-m-d'), '2023-11-12');

        // Test createMessage()
    }

    public function testCreateMessage(): void
    {
        // test birth date in the current day, show hours remaining
        $test_rec1 = [
            "name" => "Ashely B",
            "birthdate" => "2021-11-09 03:31:00",
            "timezone" => "America/New_York",
            "interval" => [
                "y" => 0,
                "m" => 0,
                "d" => 0,
                "h" => 2,
                "i" => 27,
                "s" => 32,
                "_comment" => "f: 0.840758"
            ],
            "isBirthday" => true,
        ];
        $birthdate1 = new DateTime($test_rec1['birthdate'], new DateTimeZone($test_rec1['timezone']));
        $dateTime1 = new DateTime("2022-11-08 23:40:00", new DateTimeZone("America/Los_Angeles"));
        $personCtrl1 = new PersonController(['currentDate' => $dateTime1]);
        $message = $personCtrl1->createMessage($test_rec1, $birthdate1);
        $this->assertEquals($message, "Ashely B is 1 years old today (22 hours remaining in America/New_York).");
 
         // test birth date in the current day, show hours remaining
         $test_rec2 = [
            "name" => "John Williams",
            "birthdate" => "1950-09-09 03:31:00",
            "timezone" => "America/Los_Angeles",
            "interval" => [
                "y" => 0,
                "m" => 9,
                "d" => 30,
                "h" => 23,
                "i" => 48,
                "s" => 42,
                "_comment" => "f: 0.840758"
            ],
            "isBirthday" => false,
        ];
        $birthdate2 = new DateTime($test_rec2['birthdate'], new DateTimeZone($test_rec2['timezone']));
        $dateTime2 = new DateTime("2022-11-08 23:40:00", new DateTimeZone("America/Los_Angeles"));
        $personCtrl2 = new PersonController(['currentDate' => $dateTime2]);
        $message2 = $personCtrl2->createMessage($test_rec2, $birthdate2);
        $this->assertEquals($message2, "John Williams is 73 years old in 9 months, 30 days in America/Los_Angeles.");

        // test birth date coming soon, within a day
        $test_rec3 = [
            "name" => "Ryan",
            "birthdate" => "2000-11-10 03:31:00",
            "timezone" => "America/Los_Angeles",
            "interval" => [
                "y" => 0,
                "m" => 0,
                "d" => 0,
                "h" => 23,
                "i" => 35,
                "s" => 34,
                "_comment" => "f: 0.840758"
            ],
            "isBirthday" => false,
        ];
        $birthdate3 = new DateTime($test_rec3['birthdate'], new DateTimeZone($test_rec3['timezone']));
        $dateTime3 = new DateTime("2022-11-09 00:25:00", new DateTimeZone("America/Los_Angeles"));
        $personCtrl3 = new PersonController(['currentDate' => $dateTime3]);
        $message3 = $personCtrl3->createMessage($test_rec3, $birthdate3);
        $this->assertEquals($message3, "Ryan is 23 years old in 23 hours 35 minutes.");
    }
}
