<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Buttons;

use Hirtz\Skeleton\Html\Form;
use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Html\TextInput;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Widgets\Buttons\ConfirmButton;
use Hirtz\Skeleton\Widgets\Buttons\DeleteButton;
use Symfony\Component\DomCrawler\Crawler;

class ConfirmButtonTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheConfirmingButtonPostsToTheUrl(): void
    {
        $crawler = $this->render(ConfirmButton::make()
            ->icon('key')
            ->label('Reset password')
            ->url('/admin/user/reset?id=1'));

        $trigger = $crawler->filter('button[data-modal^="#"]');
        self::assertSame('btn btn-primary', $trigger->attr('class'));
        self::assertSame('Reset password', trim($trigger->text()));
        self::assertCount(1, $trigger->filter('.icon-text'));

        $modal = $crawler->filter((string)$trigger->attr('data-modal'));
        self::assertSame('Reset password', $modal->filter('.modal-title')->text());

        $confirm = $modal->filter('.modal-footer [hx-post]');
        self::assertSame('/admin/user/reset?id=1', $confirm->attr('hx-post'));
        self::assertSame('true', $confirm->attr('hx-push-url'));
        self::assertSame('outerHTML show:top', $confirm->attr('hx-swap'));
        self::assertSame('btn btn-primary', $confirm->attr('class'));
        self::assertSame('Reset password', $confirm->text());
    }

    /**
     * The trigger says what happens ("Make site owner"), the modal and its button what it costs.
     */
    public function testTheConfirmingButtonHasItsOwnLabelAndStyle(): void
    {
        $crawler = $this->render(ConfirmButton::make()
            ->label('Make site owner')
            ->confirmLabel('Transfer ownership')
            ->confirmStyle('danger')
            ->url('/admin/user/ownership?id=1'));

        $trigger = $crawler->filter('button[data-modal^="#"]');
        self::assertSame('Make site owner', $trigger->text());
        self::assertSame('btn btn-primary', $trigger->attr('class'));

        self::assertSame('Transfer ownership', $crawler->filter('.modal-title')->text());

        $confirm = $crawler->filter('.modal-footer [hx-post]');
        self::assertSame('Transfer ownership', $confirm->text());
        self::assertSame('btn btn-danger', $confirm->attr('class'));
    }

    public function testDangerStylesBothButtons(): void
    {
        $crawler = $this->render(ConfirmButton::make()
            ->danger()
            ->label('Log out')
            ->title('Log out other sessions?')
            ->url('/logout'));

        self::assertSame('btn btn-danger', $crawler->filter('button[data-modal^="#"]')->attr('class'));
        self::assertSame('btn btn-danger', $crawler->filter('.modal-footer [hx-post]')->attr('class'));
        self::assertSame('Log out other sessions?', $crawler->filter('.modal-title')->text());
    }

    public function testTheContentIsTheModalBody(): void
    {
        $crawler = $this->render(ConfirmButton::make()
            ->label('Restore')
            ->content(P::make()->text('Restore <b>Müller</b>?'))
            ->url('/restore'));

        self::assertSame('<p>Restore &lt;b&gt;Müller&lt;/b&gt;?</p>', $crawler->filter('.modal-body')->html());
    }

    /**
     * The form is posted rather than the URL, by the confirming button outside it.
     */
    public function testAFormIsPostedInsteadOfTheUrl(): void
    {
        $crawler = $this->render(ConfirmButton::make()
            ->label('Import')
            ->form(Form::make()->content(TextInput::make()->name('url')))
            ->url('/admin/file/create')
            ->pushHistory(false));

        $form = $crawler->filter('.modal-body form');
        self::assertSame('/admin/file/create', $form->attr('hx-post'));
        self::assertNull($form->attr('hx-push-url'));
        self::assertCount(1, $form->filter('input[name="url"]'));

        $confirm = $crawler->filter('.modal-footer [form]');
        self::assertSame($form->attr('id'), $confirm->attr('form'));
        self::assertSame('submit', $confirm->attr('type'));
        self::assertNull($confirm->attr('hx-post'));
    }

    public function testWithoutHistoryTheResponseSwapsAsTheBodySays(): void
    {
        $confirm = $this->render(ConfirmButton::make()
            ->label('Move')
            ->url('/move-all')
            ->include('[data-check]:checked')
            ->pushHistory(false))
            ->filter('.modal-footer [hx-post]');

        self::assertSame('[data-check]:checked', $confirm->attr('hx-include'));
        self::assertNull($confirm->attr('hx-push-url'));
        self::assertNull($confirm->attr('hx-swap'));
    }

    /**
     * `includes/tooltips.ts` takes the `title` off the element, so the label is the `aria-label` too.
     */
    public function testIconOnlyMakesTheLabelTheTooltip(): void
    {
        $crawler = $this->render(ConfirmButton::make()
            ->style('secondary')
            ->icon('rotate-left')
            ->iconOnly()
            ->label('Restore')
            ->url('/restore'));

        $trigger = $crawler->filter('button[data-modal^="#"]');
        self::assertSame('Restore', $trigger->attr('aria-label'));
        self::assertSame('Restore', $trigger->attr('title'));
        self::assertNotNull($trigger->attr('data-tooltip'));
        self::assertSame('', trim($trigger->text()));
        self::assertSame('btn btn-secondary', $trigger->attr('class'));

        self::assertSame('Restore', $crawler->filter('.modal-footer [hx-post]')->text());
        self::assertSame('btn btn-secondary', $crawler->filter('.modal-footer [hx-post]')->attr('class'));
    }

    public function testAnInvisibleButtonRendersNothing(): void
    {
        self::assertSame('', ConfirmButton::make()
            ->label('Reset')
            ->url('/reset')
            ->visible(false)
            ->render());
    }

    /**
     * The subclass keeps only its type-the-name form; the form is posted, with the history pushed like the button.
     */
    public function testTheDeleteButtonAsksForTheValue(): void
    {
        $user = $this->getUserFromFixture('admin');

        $crawler = $this->render(DeleteButton::make()
            ->model($user)
            ->property('email')
            ->url(['/admin/user/delete', 'id' => $user->id]));

        self::assertSame('btn btn-danger', $crawler->filter('button[data-modal^="#"]')->attr('class'));

        $form = $crawler->filter('.modal-body form');
        self::assertSame("/admin/user/delete?id=$user->id", $form->attr('hx-post'));
        self::assertSame('true', $form->attr('hx-push-url'));
        self::assertSame('^' . preg_quote($user->email, '/') . '$', $form->filter('input[name="value"]')->attr('pattern'));

        $confirm = $crawler->filter('.modal-footer [form]');
        self::assertSame($form->attr('id'), $confirm->attr('form'));
        self::assertSame('btn btn-danger', $confirm->attr('class'));
        self::assertCount(1, $crawler->filter('.modal-body p'));
    }

    private function render(ConfirmButton $button): Crawler
    {
        return new Crawler('<div>' . $button->render() . '</div>');
    }
}
