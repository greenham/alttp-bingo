<?php

require_once('inc/lib.php');

init_session();

if (!isset($_SESSION['admin']) || !isset($_GET['username']))
{
    header('Location: login.php');
}

$username = $_GET['username'];

$password = create_token();
$salted = salt_password($password);

$db = init_db();

$created = $db->query("INSERT INTO `bingo_users` (`username`, `password`, `salt`) VALUES ('{$username}', '{$salted['salted']}', '{$salted['salt']}')");

if ($created === true)
{
    echo "Created user for '{$username}' with password: <strong>{$password}</strong>";
}
else
{
    echo $db->error;
}
