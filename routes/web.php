<?php

// Arayuz sayfalari
$Router->add('GET', '/', function () {
	require __DIR__ . '/../public/app.php';
});

$Router->add('GET', '/login', function () {
	require __DIR__ . '/../public/login.php';
});

$Router->add('GET', '/register', function () {
	require __DIR__ . '/../public/register.php';
});
