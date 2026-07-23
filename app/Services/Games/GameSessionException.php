<?php

namespace App\Services\Games;

use RuntimeException;

/**
 * A recoverable, user-facing problem in the game-session flow (bad state, no
 * questions, wrong turn). The controller maps it to a 422 JSON error.
 */
class GameSessionException extends RuntimeException {}
