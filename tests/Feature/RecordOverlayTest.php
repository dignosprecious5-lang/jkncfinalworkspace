<?php

namespace Tests\Feature;

use App\Models\Company;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every full-screen overlay must be bound to a visibility predicate.
 *
 * These containers are position: fixed over the whole viewport and stack above the
 * page or drawer they belong to. One that is never hidden is not a cosmetic slip:
 * it keeps covering whatever sits beneath it and intercepts every click aimed
 * there, so the controls below look dead while the overlay's own handler quietly
 * runs instead. ESC survives, because those handlers are window-scoped and do not
 * depend on where the pointer is.
 *
 * PHP cannot observe a click, so this asserts the structure that makes a click
 * reach the control underneath.
 */
class RecordOverlayTest extends TestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);
    }

    /**
     * The pages that carry full-screen overlays. The setup modal lives in the
     * layout, so every page renders it; the record drawer rides on the register
     * and the classify drawer on the unclassified queue.
     *
     * @return array<string, array{0: string}>
     */
    public static function overlayPages(): array
    {
        return [
            'register' => ['records.index'],
            'unclassified queue' => ['records.unclassified'],
            'dashboard' => ['records.dashboard'],
        ];
    }

    #[DataProvider('overlayPages')]
    public function test_no_full_screen_overlay_is_left_unbound(string $route): void
    {
        $content = $this->get(route($route))->assertOk()->getContent() ?? '';

        foreach (self::openingTags($content) as $tag) {
            if (! str_contains($tag, 'fixed') || ! str_contains($tag, 'x-data')) {
                continue;
            }

            $this->assertStringContainsString(
                'x-show',
                $tag,
                sprintf(
                    'The overlay on [%s] is fixed to the viewport but has no x-show, so it stays painted over the page and swallows clicks aimed at the controls underneath. Found: %s',
                    $route,
                    $this->summarise($tag),
                ),
            );
        }
    }

    /**
     * The specific regression: the Edit Metadata and Archive container is a z-[60]
     * overlay nested inside the z-50 record drawer. Unbound, it covered the drawer
     * for as long as the drawer was open and its Close buttons could not be
     * clicked — only ESC, which is window-scoped, still worked.
     */
    public function test_the_metadata_and_archive_container_is_hidden_until_it_opens(): void
    {
        $content = $this->get(route('records.index'))->assertOk()->getContent() ?? '';

        $container = null;

        foreach (self::openingTags($content) as $tag) {
            if (str_contains($tag, 'recordModals()')) {
                $container = $tag;
            }
        }

        $this->assertNotNull($container, 'The Edit Metadata and Archive container is not rendered at all.');

        $this->assertMatchesRegularExpression(
            '/x-show="mode !== null"/',
            $container,
            'The container is no longer hidden while no modal is open.',
        );

        $this->assertStringContainsString(
            'x-cloak',
            $container,
            'The container can paint before Alpine binds it.',
        );
    }

    /**
     * A double bind would hide the modal even once it opens, which is the same
     * class of mistake as the missing one: a predicate that disagrees with the
     * state the inner panels actually key off.
     */
    public function test_the_metadata_and_archive_panels_still_open(): void
    {
        $content = $this->get(route('records.index'))->assertOk()->getContent() ?? '';

        foreach (["mode === 'edit'", "mode === 'archive'"] as $panel) {
            $this->assertStringContainsString(
                $panel,
                $content,
                sprintf('The panel gated on [%s] is no longer rendered.', $panel),
            );
        }
    }

    /**
     * Every <div> opening tag, matched with quoted attribute values allowed to
     * contain a ">" so that an x-data expression cannot truncate the tag and hide
     * the bindings that follow it.
     *
     * @return list<string>
     */
    private static function openingTags(string $html): array
    {
        preg_match_all('/<div(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/', $html, $matches);

        return $matches[0];
    }

    private function summarise(string $tag): string
    {
        $tag = preg_replace('/\s+/', ' ', $tag) ?? $tag;

        return strlen($tag) > 160 ? substr($tag, 0, 157).'...' : $tag;
    }
}
