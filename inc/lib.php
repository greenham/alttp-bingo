<?php

require_once('inc/db.php');

const BINGO_VERSION = "2.2";

function init_db()
{
    global $db_config;

    extract($db_config);

    $db = mysqli_connect($host, $user, $pass, $name);

    if (!$db)
    {
        error_log("Error: Unable to connect to MySQL.");
        error_log("Debugging errno: " . mysqli_connect_errno());
        error_log("Debugging error: " . mysqli_connect_error());
        throw new Exception("Unable to connect to to MySQL: " . mysqli_connect_error());
    }

    return $db;
}

function output_json($data)
{
    $callback = (isset($_GET['callback']) ? $_GET['callback'] : null);
    if ($callback === null)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        return true;
    }

    header('Content-Type: application/javascript');
    echo $callback . '(' . json_encode($data) . ')';

    return true;
}

//////////////////////////////////////////////////////////////
// Board generation
function generate_board($seed, $mode = 'normal', $size = 5)
{
    srand($seed);

    $board = [];

    $goals = get_goals_by_difficulty();

    for ($i = 1; $i <= 25; $i++)
    {
        $difficulty = difficulty($i, $seed);
        $group_goals = $goals[$difficulty];
        shuffle($group_goals);

        // get a random goal with this difficulty
        $goal = $group_goals[array_rand($group_goals)];

        // add it to the final board
        $board[$i] = $goal;

        // remove it from the pool
        unset($goals[$difficulty][$goal->id]);
    }

    return $board;
}

function difficulty($cell, $seed)
{
    // To create the magic square we need 2 random orderings of the numbers 0, 1, 2, 3, 4.
    // The following creates those orderings and calls them Table5 and Table1

    $Num3 = $seed%1000;   // Table5 will use the ones, tens, and hundreds digits.

    $Rem8 = $Num3%8;
    $Rem4 = floor($Rem8/2);
    $Rem2 = $Rem8%2;
    $Rem5 = $Num3%5;
    $Rem3 = $Num3%3;  // Note that Rem2, Rem3, Rem4, and Rem5 are mathematically independent.
    $RemT = floor($Num3/120);    // This is between 0 and 8

    // The idea is to begin with an array containing a single number, 0.
    // Each number 1 through 4 is added in a random spot in the array's current size.
    // The result - the numbers 0 to 4 are in the array in a random (and uniform) order.
    $Table5 = [0];
    array_splice($Table5, $Rem2, 0, 1);
    array_splice($Table5, $Rem3, 0, 2);
    array_splice($Table5, $Rem4, 0, 3);
    array_splice($Table5, $Rem5, 0, 4);

    $Num3 = floor($seed/1000);   // Table1 will use the next 3 digits.
    $Num3 = $Num3%1000;

    $Rem8 = $Num3%8;
    $Rem4 = floor($Rem8/2);
    $Rem2 = $Rem8%2;
    $Rem5 = $Num3%5;
    $Rem3 = $Num3%3;
    $RemT = $RemT * 8 + floor($Num3/120);  // This is between 0 and 64.

    $Table1 = [0];
    array_splice($Table1, $Rem2, 0, 1);
    array_splice($Table1, $Rem3, 0, 2);
    array_splice($Table1, $Rem4, 0, 3);
    array_splice($Table1, $Rem5, 0, 4);

    $cell--;
    $RemT = $RemT%5;      //  Between 0 and 4, fairly uniformly.
    $x = ($cell+$RemT)%5;     //  RemT is horizontal shift to put any diagonal on the main diagonal.
    $y = floor($cell/5);

    // The Tables are set into a single magic square template
    // Some are the same up to some rotation, reflection, or row permutation.
    // However, all genuinely different magic squares can arise in this fashion.
    $e5 = $Table5[($x + 3*$y)%5];
    $e1 = $Table1[(3*$x + $y)%5];

    // Table5 controls the 5* part and Table1 controls the 1* part.
    $value = 5*$e5 + $e1 + 1;
    return $value;
}

function make_seed()
{
    return mt_rand(100000, 999999);
}

//////////////////////////////////////////////////////////////
// Goal "Model"
function get_goals()
{
    $db = init_db();

    $result = $db->query("SELECT * FROM `bingo_goals` ORDER BY `difficulty` DESC");
    if (!$result)
    {
        error_log("Invalid query: " . mysql_error());
        return false;
    }

    $goals = [];
    while ($goal = $result->fetch_object())
    {
        $goals[] = $goal;
    }

    $result->free();

    return $goals;
}

function get_goals_by_difficulty()
{
    $goals = get_goals();
    $goals_grouped = [];

    foreach($goals as $goal)
    {
        $goals_grouped[$goal->difficulty][$goal->id] = $goal;
    }

    return $goals_grouped;
}

function get_goal_stats()
{
    $db = init_db();

    $stats = [];
    $difficulties = 25;
    $flute_locations = 8;

    $select[] = "COUNT(*) AS `total_goals`";
    for($i = 1; $i <= $difficulties; $i++) {
        $select[] = "SUM(CASE WHEN (`difficulty` = {$i}) THEN 1 ELSE 0 END) AS `{$i}_difficulty_count`";
    }

    for($i = 1; $i <= $flute_locations; $i++)
    {
        $select[] = "SUM(CASE WHEN (`nearest_flute_location` = {$i}) THEN 1 ELSE 0 END) AS `{$i}_flute_location_count`";
    }

    $stats_sql = "SELECT ";
    $stats_sql .= implode(', ', $select);
    $stats_sql .= " FROM `bingo_goals`";

    $result = $db->query($stats_sql);
    if (!$result)
    {
        error_log("Invalid query: " . mysql_error());
        return $stats;
    }

    foreach($result->fetch_assoc() as $key => $value)
    {
        if (strpos($key, '_difficulty_count') !== false)
        {
            $difficulty = str_replace('_difficulty_count', '', $key);
            $stats['difficulties'][$difficulty] = $value;
        }
        else if (strpos($key, '_flute_location_count') !== false)
        {
            $location = str_replace('_flute_location_count', '', $key);
            $stats['flute_locations'][$location] = $value;
        }
        else
        {
            $stats[$key] = $value;
        }
    }

    return $stats;
}

function create_goal($data)
{
    $db = init_db();

    $required_fields = ['name', 'difficulty', 'nearest_flute_location'];
    foreach($required_fields as $field)
    {
        if (!isset($data[$field]))
        {
            throw new Exception("Missing '{$field}'");
        }

        $data[$field] = $db->real_escape_string($data[$field]);
    }

    $sql = "INSERT INTO `bingo_goals`";
    $sql .= ' (`' . implode('`, `', array_keys($data)) . '`)';
    $sql .= ' VALUES ';
    $sql .= " ('" . implode("', '", array_values($data)) . "')";

    $result = $db->query($sql);

    if ($result === false)
    {
        return false;
    }

    $new_goal = json_decode(json_encode($data));
    $new_goal->id = $db->insert_id();
    return $new_goal;
}

function update_goal($id, $data)
{
    $db = init_db();
    $id = $db->real_escape_string($id);

    $sql = "UPDATE `bingo_goals` SET ";

    $updates = [];
    foreach($data as $key => $value)
    {
        $value = $db->real_escape_string($value);
        $updates[] = "`{$key}` = '{$value}'";
    }

    $sql .= implode(', ', $updates);
    $sql .= " WHERE `id` = '{$id}'";

    $result = $db->query($sql);

    return $result;
}

function delete_goal($id)
{
    $db = init_db();
    $id = $db->real_escape_string($id);
    $result = $db->query("DELETE FROM `bingo_goals` WHERE `id` = '{$id}'");
    return $result;
}

//////////////////////////////////////////////////////////////
// User session stuff
function init_session()
{
    session_start();
}

function check_user_login($username, $password)
{
    if ($username === 'admin' && $password === 'sgqf')
    {
        return true;
    }
    else
    {
        return false;
    }
}


//////////////////////////////////////////////////////////////
// Old magic square generation stuff
/*
## PHP Magic Square (2014)
## Author:  Siamak Aghaeipour Motlagh
## Email:   siamak.aghaeipour@gmail.com
## Website: http://blacksrc.com
*/
function magic_square($size = 5)
{
    if ($size %2 != 0)
    {
        $first = ($size - 1) / 2;
        $min = 2;
        $max = $size * $size;

        $row = 0;
        $col = $first;

        $mt[$row][$col] =    1;
        for ($i = $min; $i <= $max; $i++)
        {
            // save the current point
            $lrow = $row;
            $lcol = $col;

            // find the next point
            $row = $row - 1;
            $col = $col - 1;
            if ($row < 0)
                $row = $size-1;
            if ($col < 0)
                $col = $size-1;

            // if it was not null
            if (isset($mt[$row][$col]))
            {
                $row = $lrow+1;
                $col = $lcol;
            }

            // fill the array
            $mt[$row][$col] = $i;
        }

        // sort the arrays
        ksort($mt);
        for ($i = 0; $i < $size; $i++) {
            ksort($mt[$i]);
        }

        return $mt;
    } else {
        throw new Exception('The algorithm is not ready for even numbers yet!');
    }
}

function reflect_diaganol_tlbr($board)
{
    // change x,y -> y,x for everything except the tlbr diag (0,0, 1,1, 2,2, 3,3, 4,4)
    $swapped = [];
    for ($x = 0; $x < 5; $x++)
    {
        for ($y = 0; $y < 5; $y++)
        {
            if ($x != $y && !in_array("{$x}{$y}", $swapped) && !in_array("{$x}{$y}", $swapped))
            {
                $first = $board[$x][$y];
                $second = $board[$y][$x];

                $board[$x][$y] = $second;
                $board[$y][$x] = $first;

                // don't swap this combo again
                $swapped[] = "{$x}{$y}";
                $swapped[] = "{$y}{$x}";
            }
        }
    }

    return $board;
}

function reflect_diaganol_trbl($board)
{
    $swaps = [
        [[0,3], [1,4]],
        [[0,2], [2,4]],
        [[0,1], [3,4]],
        [[1,2], [2,3]],
        [[1,1], [3,3]],
        [[0,0], [4,4]],
        [[1,0], [4,3]],
        [[2,1], [3,2]],
        [[2,0], [4,2]],
        [[3,0], [4,1]],
    ];

    foreach ($swaps as $swap)
    {
        $first = $board[$swap[0][0]][$swap[0][1]];
        $second = $board[$swap[1][0]][$swap[1][1]];
        $board[$swap[0][0]][$swap[0][1]] = $second;
        $board[$swap[1][0]][$swap[1][1]] = $first;
    }

    return $board;
}

function rotate_clockwise($board)
{
    $new_board = [];
    $row_count = 0;
    for ($y = 0; $y < 5; $y++)
    {
        $new_row = [];
        for ($x = 4; $x >= 0; $x--)
        {
            $new_row[] = $board[$x][$y];
        }
        $new_board[$row_count] = $new_row;
        $row_count++;
    }
    return $new_board;
}

function rotate_counterclockwise($board)
{
    $new_board = [];
    $row_count = 0;
    for ($y = 4; $y >= 0; $y--)
    {
        $new_row = [];
        for ($x = 0; $x < 5; $x++)
        {
            $new_row[] = $board[$x][$y];
        }
        $new_board[$row_count] = $new_row;
        $row_count++;
    }
    return $new_board;
}
