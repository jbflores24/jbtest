<?php

declare(strict_types=1);

return [
    'enabled' => filter_var($_ENV['SECURITY_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'learning_mode' => filter_var($_ENV['SECURITY_LEARNING_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'fail_open' => filter_var($_ENV['SECURITY_FAIL_OPEN'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'permissions' => [],
];