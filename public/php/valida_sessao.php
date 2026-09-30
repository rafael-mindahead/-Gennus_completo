<?php
require_once __DIR__ . '/../../app/auth.php';
method('GET');
respond('ok', '', safe_user(require_user()));
