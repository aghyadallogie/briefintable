<?php

namespace App\Support;

/**
 * Where a briefing stands with respect to the signed-in user (see OwnDrafts::stateFor()).
 */
enum DraftState
{
    /** No such briefing. */
    case Missing;

    /** Someone else's briefing (or a different collection). */
    case Foreign;

    /** Yours, but already live, so it can no longer be changed. */
    case Published;

    /** Yours and still a draft: the only state that may be edited or deleted. */
    case Draft;

    /** Yours, drafted or published. Anything else is a 404, so ids don't reveal what exists. */
    public function isYours(): bool
    {
        return $this === self::Draft || $this === self::Published;
    }
}
