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


        $this->post('/person', ['name' => 'John'])
            ->seeJsonEquals([
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

    }
}
