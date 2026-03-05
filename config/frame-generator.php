<?php

return [
    'background_removal' => [
        'enabled' => env('FRAME_GENERATOR_BG_REMOVAL_ENABLED', true),
        'python_bin' => env(
            'FRAME_GENERATOR_PYTHON_BIN',
            is_file(base_path('.venv/bin/python')) ? base_path('.venv/bin/python') : 'python3',
        ),
        'script_path' => base_path('scripts/remove_bg.py'),
        'timeout_seconds' => (int) env('FRAME_GENERATOR_BG_REMOVAL_TIMEOUT', 60),
    ],
];
