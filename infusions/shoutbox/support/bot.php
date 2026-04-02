<?php

/**
 * Procedural glue for the virtual assistant bot.
 * Called after a user message is successfully created.
 */
function shoutbox_bot_try_respond(string $rawMessage): void
{
    $triggers = \App\Shoutbox\Assistant::loadTriggers();
    if (!$triggers) {
        return;
    }

    // Strip BBCode tags to get plain text for matching
    $plain = trim(preg_replace('/\[.*?\]/s', '', $rawMessage));

    $match = \App\Shoutbox\Assistant::matchTrigger($plain, $triggers);
    if (!$match) {
        return;
    }

    $botId = \App\Shoutbox\Assistant::botUserId();
    if (!$botId) {
        return;
    }

    \App\Shoutbox\Assistant::postReply($botId, (string)$match['response']);
}
