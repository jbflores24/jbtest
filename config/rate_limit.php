<?php

declare(strict_types=1);

return [
    'max' => (int) ($_ENV['RATE_LIMIT_MAX'] ?? 120),
    'window' => (int) ($_ENV['RATE_LIMIT_WINDOW'] ?? 60),
];