<?php

namespace Tests\Feature\ParticipantTests;

use App\Models\Participant;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Carbon\Carbon;

class ParticipantStatusTest extends TestCase
{
    use RefreshDatabase;
    /**
     * @test
     */
    public function first_of_all_status_changed_at_column_must_be_null_and_after_every_change_it_must_be_updated_to_new_time()
    {
        $participant = new Participant();

        $this->assertEquals($participant->status_changed_at, null);

        $participant->status = 1;

        $this->assertEquals($participant->status_changed_at, Carbon::now()->toDateTimeString());

        sleep(2);

        $participant->status = 2;

        $this->assertEquals($participant->status_changed_at, Carbon::now()->toDateTimeString());

        sleep(2);

        $participant->status = 3;

        $this->assertEquals($participant->status_changed_at, Carbon::now()->toDateTimeString());
    }
}
