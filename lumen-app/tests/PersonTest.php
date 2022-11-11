<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Http\Controllers\PersonController;
use App\Models\Person;

class PersonTest extends TestCase
{
    use DatabaseMigrations;

    /*
    * test set person birthdate and timezone
    *
    * @return void
    */
    public function testStorePeopleBirthdate(): void {
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
        $this->assertEquals($goodRecord['name'], $resp[0]['name']);
        $this->assertEquals($goodRecord['birthdate'], $resp[0]['birthdate']);
        $this->assertEquals($goodRecord['timezone'], $resp[0]['timezone']);
        $this->assertEquals(7, count($resp[0]['interval'])); 
    }

    /*
    * test controller class isBirthdate function
    *
    * @return void
    */
    public function testIsBirthdate(): void {
        // Test constructor currentDate setup
        $dateTime = new DateTime("2022-11-12 00:05:00", new DateTimeZone("UTC"));
        $personCtrl = new PersonController(['currentDate' => $dateTime]);
        $this->assertEquals($dateTime, $personCtrl->currentDate);

        // Test isBirthdate() with same a timezone
        $dateTime2 = new DateTime("1990-11-12 3:15:00", new DateTimeZone("UTC"));
        $this->assertEquals(true, $personCtrl->isBirthdate($dateTime2));

        // Test isBirthdate() with different a timezone
        $dateTime3 = new DateTime("2002-11-12 3:05:00", new DateTimeZone("America/Los_Angeles"));
        $this->assertEquals(false, $personCtrl->isBirthdate($dateTime3));

    }

    /*
    * test controller class nextBirthdate function
    *
    * @return void
    */
    public function testNextBirthdate(): void {
        // Test constructor currentDate setup
        $dateTime = new DateTime("2022-11-12 00:05:00", new DateTimeZone("UTC"));
        $personCtrl = new PersonController(['currentDate' => $dateTime]);
    
        // Test nextBirthdate(): current year, not yet arrive
        $dateTime4 = new DateTime("2001-12-23 01:00:00", new DateTimeZone("UTC"));
        $nextBDate1 = $personCtrl->nextBirthdate($dateTime4);
        $this->assertEquals('2022-12-23', $nextBDate1->format('Y-m-d'));

        // Test nextBirthdate(): same date after timezone adjustment
        $dateTime5 = new DateTime("2001-11-11 09:00:00", new DateTimeZone("America/Los_Angeles"));
        $nextBDate2 = $personCtrl->nextBirthdate($dateTime5);
        $this->assertEquals('2022-11-11', $nextBDate2->format('Y-m-d'));

        // Test nextBirthdate(): just passed, go for another year.
        $dateTime2 = new DateTime("2022-11-12 17:05:00", new DateTimeZone("America/Los_Angeles"));
        $personCtrl2 = new PersonController(['currentDate' => $dateTime2]);
        $dateTime5 = new DateTime("2001-11-12 09:00:00", new DateTimeZone("UTC"));
        $nextBDate2 = $personCtrl2->nextBirthdate($dateTime5);
        $this->assertEquals('2023-11-12', $nextBDate2->format('Y-m-d'));
    }

    /*
    * test controller class createMessage function
    *
    * @return void
    */
    public function testCreateMessage(): void {
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
        $this->assertEquals("Ashely B is 1 years old today (22 hours remaining in America/New_York).", $message);
 
         // test birth date in few month
         $test_rec2 = [
            "name" => "John Williams",
            "birthdate" => "1950-09-09 03:31:00",
            "timezone" => "America/Los_Angeles",
            "interval" => [
                "y" => 0,
                "m" => 10,
                "d" => 0,
                "h" => 0,
                "i" => 20,
                "s" => 0,
                "_comment" => "f: 0.000000"
            ],
            "isBirthday" => false,
        ];
        $birthdate2 = new DateTime($test_rec2['birthdate'], new DateTimeZone($test_rec2['timezone']));
        $dateTime2 = new DateTime("2022-11-08 23:40:00", new DateTimeZone("America/Los_Angeles"));
        $personCtrl2 = new PersonController(['currentDate' => $dateTime2]);
        $message2 = $personCtrl2->createMessage($test_rec2, $birthdate2);
        $this->assertEquals("John Williams is 73 years old in 10 months, 0 days in America/Los_Angeles.", $message2);

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
        $this->assertEquals("Ryan is 22 years old in 23 hours 35 minutes in America/Los_Angeles.", $message3);
    }

    /*
    * test controller class birthdateRecord generator function
    *
    * @return void
    */
    public function testBirthdateRecord(): void {
        // test birth date in the current day, show hours remaining
        $test_rec1 = [
            "name" => "Ashely B",
            "birthdate" => "2021-11-09 03:31:00",
            "timezone" => "America/New_York",
        ];
        $dateTime1 = new DateTime("2022-11-08 23:40:00", new DateTimeZone("America/Los_Angeles"));
        $personCtrl1 = new PersonController(['currentDate' => $dateTime1]);
        $person1 = new Person();
        $person1->name = $test_rec1['name'];
        $person1->birthdate = $test_rec1['birthdate'];
        $person1->timezone = $test_rec1['timezone'];
        $personRecord1 = $personCtrl1->birthdateRecord($person1);
        $this->assertEquals("Ashely B is 1 years old today (22 hours remaining in America/New_York).", $personRecord1['message']);
        $this->assertEquals(true, $personRecord1['isBirthday']);

        // test birth date in few month
        $test_rec2 = [
            "name" => "John Williams",
            "birthdate" => "1950-09-09 03:31:00",
            "timezone" => "America/Los_Angeles",
        ];
        $dateTime2 = new DateTime("2022-11-08 23:40:00", new DateTimeZone("America/Los_Angeles"));
        $personCtrl2 = new PersonController(['currentDate' => $dateTime2]);
        $person2 = new Person();
        $person2->name = $test_rec2['name'];
        $person2->birthdate = $test_rec2['birthdate'];
        $person2->timezone = $test_rec2['timezone'];
        $personRecord2 = $personCtrl2->birthdateRecord($person2);
        $this->assertEquals("John Williams is 73 years old in 10 months, 0 days in America/Los_Angeles.", $personRecord2['message']);
        $this->assertEquals(false, $personRecord2['isBirthday']);

        // test birth date coming soon, within a day
        $test_rec3 = [
            "name" => "Ryan",
            "birthdate" => "2000-11-10 03:20:00",
            "timezone" => "America/Los_Angeles",
        ];
        $dateTime3 = new DateTime("2022-11-09 00:20:00", new DateTimeZone("America/Los_Angeles"));
        $personCtrl3 = new PersonController(['currentDate' => $dateTime3]);
        $person3 = new Person();
        $person3->name = $test_rec3['name'];
        $person3->birthdate = $test_rec3['birthdate'];
        $person3->timezone = $test_rec3['timezone'];
        $personRecord3 = $personCtrl3->birthdateRecord($person3);
        $this->assertEquals("Ryan is 22 years old in 23 hours 40 minutes in America/Los_Angeles.", $personRecord3['message']);
        $this->assertEquals(false, $personRecord3['isBirthday']);

        // test the intervals
        $this->assertEquals(0, $personRecord3['interval']['y']);
        $this->assertEquals(0, $personRecord3['interval']['m']);
        $this->assertEquals(0, $personRecord3['interval']['d']);
        $this->assertEquals(23, $personRecord3['interval']['h']);
        $this->assertEquals(40, $personRecord3['interval']['i']);
        $this->assertEquals(0, $personRecord3['interval']['s']);
        $this->assertEquals('f: 0.000000', $personRecord3['interval']['_comment']);
        
    }
}
