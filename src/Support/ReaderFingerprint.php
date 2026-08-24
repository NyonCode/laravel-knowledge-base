<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Request;

/**
 * Enough to tell two readers apart, not enough to identify one.
 *
 * A signed-in reader is their id; everyone else is a salted hash of session
 * and address. The address is never stored: the question being answered is
 * "has this person already voted", which a one-way hash answers just as well
 * as the address would — and a hash cannot later be mined for something else.
 *
 * Salted with the app key so the same visitor is a different hash in a
 * different installation.
 */
final class ReaderFingerprint
{
    public static function make(?Authenticatable $reader = null): string
    {
        if ($reader !== null) {
            return hash('sha256', 'user:'.Cast::string($reader->getAuthIdentifier()).'|'.self::key());
        }

        $session = Request::hasSession() ? Request::session()->getId() : '';

        return hash('sha256', implode('|', [
            'guest',
            $session,
            Request::ip() ?? '',
            self::key(),
        ]));
    }

    private static function key(): string
    {
        $key = config('app.key');

        return is_string($key) ? $key : '';
    }
}
