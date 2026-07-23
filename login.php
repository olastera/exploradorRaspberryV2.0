<?php
require_once __DIR__ . '/config/bootstrap.php';
use App\Auth\Auth;
use App\Auth\UserManager;

// Force show login form
Auth::logout();
