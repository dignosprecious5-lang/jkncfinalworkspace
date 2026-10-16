<?php

namespace Tests\Feature;

use App\Models\Company;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Flash feedback has to reach the page, not just the session.
 *
 * The rest of the suite asserts that a controller set a session key. That is not
 * the same thing as the message being rendered: a flash banner that silently
 * output nothing would leave every one of those tests green. These assert the
 * text actually appears, on every page that reports feedback.
 */
class FlashMessageTest extends TestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function feedbackPages(): array
    {
        return [
            'master register' => ['records.index'],
            'unclassified queue' => ['records.unclassified'],
            'intake upload' => ['records.intake.upload'],
            'audit log' => ['records.logs'],
            'dashboard' => ['records.dashboard'],
        ];
    }

    #[DataProvider('feedbackPages')]
    public function test_every_page_renders_a_confirmation_message(string $route): void
    {
        $this->withSession(['status' => 'Record ABCDE was updated.'])
            ->get(route($route))
            ->assertOk()
            ->assertSee('Record ABCDE was updated.');
    }

    #[DataProvider('feedbackPages')]
    public function test_every_page_renders_a_handled_error(string $route): void
    {
        $this->withSession(['error' => 'That link could not be created.'])
            ->get(route($route))
            ->assertOk()
            ->assertSee('That link could not be created.');
    }

    /**
     * Validation failures are exercised through a real submission rather than a
     * seeded error bag, so this covers the path a user actually takes.
     */
    public function test_a_rejected_submission_shows_its_validation_error_on_the_page(): void
    {
        $this->from(route('records.intake.upload'))
            ->post(route('records.intake.store'), ['title' => 'No files attached'])
            ->assertSessionHasErrors('files');

        $this->followingRedirects()
            ->post(route('records.intake.store'), ['title' => 'Still no files'])
            ->assertOk()
            ->assertSee('Choose at least one file to upload.');
    }
}
