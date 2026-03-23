<?php
require_once 'config.php';
require_once '../includes/Util.php';

Util::requireAuth();
session_destroy();
Util::redirect('../view/login.php');