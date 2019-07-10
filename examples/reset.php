<?php

$provider = require __DIR__ . '/provider.php';

unset($_SESSION['token'], $_SESSION['state'], $_SESSION['user']);

header('Location: https://it.pf.dk/fagprojekt/');
