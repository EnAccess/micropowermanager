<?php

namespace Tests\Feature;

use App\Http\Requests\PersonOnboardingRequest;
use App\Models\Person\Person;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Tests\CreateEnvironments;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class PersonOnboardingTest extends TestCase {
    use RefreshMultipleDatabases;
    use CreateEnvironments;

    public function testItStoresOnboardingAnswersForAPerson(): void {
        $person = $this->actAsAdminWithPerson();

        $response = $this->actingAs($this->user)->putJson(
            sprintf('/api/people/%d/onboarding', $person->id),
            ['answers' => [
                ['question' => 'Household size', 'answer' => '6'],
                ['question' => 'Primary cooking fuel', 'answer' => 'Charcoal'],
            ]]
        );

        $response->assertStatus(200);
        $answers = $person->fresh()->onboarding_json;
        $this->assertSame(['Household size' => '6', 'Primary cooking fuel' => 'Charcoal'], $answers);
        $this->assertSame(['Household size', 'Primary cooking fuel'], array_keys($answers));
    }

    public function testItReturnsTheSavedAnswersInTheUpdateResponse(): void {
        $person = $this->actAsAdminWithPerson();

        $response = $this->actingAs($this->user)->putJson(
            sprintf('/api/people/%d/onboarding', $person->id),
            ['answers' => [['question' => 'Household size', 'answer' => '6']]]
        );

        // The customer detail page reads the answers straight back out of this
        // response, so a missing or still-encoded value empties the list.
        $response->assertStatus(200);
        $this->assertSame(
            ['Household size' => '6'],
            $response->json('data.onboarding_json')
        );
    }

    public function testItKeepsAnEmptyAnswerAsNull(): void {
        $person = $this->actAsAdminWithPerson();

        $response = $this->actingAs($this->user)->putJson(
            sprintf('/api/people/%d/onboarding', $person->id),
            ['answers' => [['question' => 'Owns a fridge', 'answer' => '']]]
        );

        $response->assertStatus(200);
        $this->assertSame(['Owns a fridge' => null], $person->fresh()->onboarding_json);
    }

    public function testItReplacesTheFullAnswerSet(): void {
        $person = $this->actAsAdminWithPerson();

        $this->actingAs($this->user)->putJson(
            sprintf('/api/people/%d/onboarding', $person->id),
            ['answers' => [
                ['question' => 'Household size', 'answer' => '6'],
                ['question' => 'Owns a fridge', 'answer' => 'No'],
            ]]
        )->assertStatus(200);

        $response = $this->actingAs($this->user)->putJson(
            sprintf('/api/people/%d/onboarding', $person->id),
            ['answers' => [['question' => 'Household size', 'answer' => '7']]]
        );

        $response->assertStatus(200);
        $this->assertSame(['Household size' => '7'], $person->fresh()->onboarding_json);
    }

    public function testItClearsAnswersWhenAnEmptyArrayIsSent(): void {
        $person = $this->actAsAdminWithPerson();

        $this->actingAs($this->user)->putJson(
            sprintf('/api/people/%d/onboarding', $person->id),
            ['answers' => [['question' => 'Household size', 'answer' => '6']]]
        )->assertStatus(200);

        $response = $this->actingAs($this->user)->putJson(
            sprintf('/api/people/%d/onboarding', $person->id),
            ['answers' => []]
        );

        $response->assertStatus(200);
        $this->assertNull($person->fresh()->onboarding_json);
    }

    public function testItRejectsDuplicateQuestions(): void {
        $person = $this->actAsAdminWithPerson();

        $response = $this->actingAs($this->user)->putJson(
            sprintf('/api/people/%d/onboarding', $person->id),
            ['answers' => [
                ['question' => 'Household size', 'answer' => '6'],
                ['question' => 'household SIZE', 'answer' => '7'],
            ]]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('answers.1.question');
    }

    public function testItRejectsARowWithoutAQuestion(): void {
        $person = $this->actAsAdminWithPerson();

        $response = $this->actingAs($this->user)->putJson(
            sprintf('/api/people/%d/onboarding', $person->id),
            ['answers' => [['question' => '   ', 'answer' => '6']]]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('answers.0.question');
    }

    public function testItRejectsAnAnswerOverOneThousandCharacters(): void {
        $person = $this->actAsAdminWithPerson();

        $response = $this->actingAs($this->user)->putJson(
            sprintf('/api/people/%d/onboarding', $person->id),
            ['answers' => [['question' => 'Notes', 'answer' => str_repeat('a', 1001)]]]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('answers.0.answer');
    }

    public function testItRejectsMoreAnswersThanTheCap(): void {
        $person = $this->actAsAdminWithPerson();

        $answers = [];
        for ($index = 0; $index <= PersonOnboardingRequest::MAX_ANSWERS; ++$index) {
            $answers[] = ['question' => 'Question '.$index, 'answer' => (string) $index];
        }

        $response = $this->actingAs($this->user)->putJson(
            sprintf('/api/people/%d/onboarding', $person->id),
            ['answers' => $answers]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('answers');
    }

    public function testItRejectsAnObjectPayloadInsteadOfAList(): void {
        $person = $this->actAsAdminWithPerson();

        $response = $this->actingAs($this->user)->putJson(
            sprintf('/api/people/%d/onboarding', $person->id),
            ['answers' => ['Household size' => '6']]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('answers');
    }

    public function testItRejectsAMissingAnswersKey(): void {
        $person = $this->actAsAdminWithPerson();

        $response = $this->actingAs($this->user)->putJson(
            sprintf('/api/people/%d/onboarding', $person->id),
            []
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('answers');
    }

    public function testItReturnsOnboardingAnswersOnThePersonDetailEndpoint(): void {
        $person = $this->actAsAdminWithPerson();

        $this->actingAs($this->user)->putJson(
            sprintf('/api/people/%d/onboarding', $person->id),
            ['answers' => [['question' => 'Household size', 'answer' => '6']]]
        )->assertStatus(200);

        $response = $this->actingAs($this->user)->getJson(sprintf('/api/people/%d', $person->id));

        $response->assertStatus(200);
        $response->assertJsonPath('data.onboarding_json.Household size', '6');
    }

    public function testItReturnsNotFoundForAnUnknownPerson(): void {
        $this->actAsAdminWithPerson();

        $response = $this->actingAs($this->user)->putJson(
            '/api/people/999999/onboarding',
            ['answers' => [['question' => 'Household size', 'answer' => '6']]]
        );

        $response->assertStatus(404);
    }

    private function actAsAdminWithPerson(): Person {
        $user = UserFactory::new()->create();
        $this->user = $user;
        $this->assignRole($user, 'admin');

        return PersonFactory::new()->create();
    }
}
