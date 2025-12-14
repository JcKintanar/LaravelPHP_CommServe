<?php

// Change working directory to the root
chdir(__DIR__ . '/../');

// Forward Vercel requests to Laravel's public/index.php
require __DIR__ . '/../public/index.php';
