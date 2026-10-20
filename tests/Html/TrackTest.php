<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Html;

use Hirtz\Skeleton\Html\Track;
use Hirtz\Skeleton\Html\Video;
use Hirtz\Skeleton\Test\TestCase;

class TrackTest extends TestCase
{
    public function testTheTrackRendersItsAttributes(): void
    {
        $track = Track::make()
            ->kind('subtitles')
            ->src('/attachments/file/1/abc/subtitles.vtt')
            ->srclang('de')
            ->label('Deutsch')
            ->default();

        self::assertSame(
            '<track src="/attachments/file/1/abc/subtitles.vtt" kind="subtitles" srclang="de" label="Deutsch" default>',
            $track->render()
        );
    }

    public function testATrackThatIsNotTheDefault(): void
    {
        self::assertSame('<track srclang="en">', Track::make()->srclang('en')->default(false)->render());
    }

    public function testAVideoHoldsItsTracks(): void
    {
        $html = Video::make()
            ->controls()
            ->content(Track::make()->srclang('en'), Track::make()->srclang('de'))
            ->render();

        self::assertSame('<video controls><track srclang="en"><track srclang="de"></video>', $html);
    }
}
