<?php

require_once('inc/lib.php');
require_once('inc/Parsedown.php');

$rules_markdown = get_setting('rules_markdown');
$Parsedown = new Parsedown();

?>

<!DOCTYPE html>
<html>
<head>
    <title>A Link to the Past Bingo</title>
    <meta name="description" content="Generates 'Bingo' boards for Zelda: A Link to the Past." />
    <meta name="keywords" content="alttp bingo, zelda bingo" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="icon" type="image/ico" href="/favicon.ico">

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script> <!-- jquery -->
    <script src="assets/js/querystring.js"></script>

    <link href='//fonts.googleapis.com/css?family=Roboto:400,700,400italic,700italic' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" type="text/css" href="assets/css/styles.css">
</head>
<body>
    <div id="wrap">
        <div id="main">
            <div class="container" id="pageContent">
                <div id="bingoPage">
                    <div id="results">
                        <table id="bingo">
                            <tr>
                                <td class="popout" id="tlbr">TL-BR</td>
                                <td class="popout" id="col1">COL1</td>
                                <td class="popout" id="col2">COL2</td>
                                <td class="popout" id="col3">COL3</td>
                                <td class="popout" id="col4">COL4</td>
                                <td class="popout" id="col5">COL5</td>
                            </tr>
                            <tr>
                                <td class="popout" id="row1">ROW1</td>
                                <td class="row1 col1 tlbr" id="slot1"></td>
                                <td class="row1 col2" id="slot2"></td>
                                <td class="row1 col3" id="slot3"></td>
                                <td class="row1 col4" id="slot4"></td>
                                <td class="row1 col5 bltr" id="slot5"></td>
                            </tr>
                            <tr>
                                <td class="popout" id="row2">ROW2</td>
                                <td class="row2 col1" id="slot6"></td>
                                <td class="row2 col2 tlbr" id="slot7"></td>
                                <td class="row2 col3" id="slot8"></td>
                                <td class="row2 col4 bltr" id="slot9"></td>
                                <td class="row2 col5" id="slot10"></td>
                            </tr>
                            <tr>
                                <td class="popout" id="row3">ROW3</td>
                                <td class="row3 col1" id="slot11"></td>
                                <td class="row3 col2" id="slot12"></td>
                                <td class="row3 col3 tlbr bltr" id="slot13"></td>
                                <td class="row3 col4" id="slot14"></td>
                                <td class="row3 col5" id="slot15"></td>
                            </tr>
                            <tr>
                                <td class="popout" id="row4">ROW4</td>
                                <td class="row4 col1" id="slot16"></td>
                                <td class="row4 col2 bltr" id="slot17"></td>
                                <td class="row4 col3" id="slot18"></td>
                                <td class="row4 col4 tlbr" id="slot19"></td>
                                <td class="row4 col5" id="slot20"></td>
                            </tr>
                            <tr>
                                <td class="popout" id="row5">ROW5</td>
                                <td class="row5 col1 bltr" id="slot21"></td>
                                <td class="row5 col2" id="slot22"></td>
                                <td class="row5 col3" id="slot23"></td>
                                <td class="row5 col4" id="slot24"></td>
                                <td class="row5 col5 tlbr" id="slot25"></td>
                            </tr>
                            <tr>
                                <td class="popout" id="bltr">BL-TR</td>
                            </tr>
                        </table>
                        <span id="debug"></span>
                        <p id="credits">
                            <hr>
                            Goals created and optimized by <a href="http://www.twitch.tv/seancass" target="_blank">seancass</a>, <a href="http://www.twitch.tv/brahminmeat" target="_blank">brahminmeat</a>, & <a href="http://www.twitch.tv/christosowen" target="_blank">christosowen</a>.<br>
                            Coding by <a href="http://www.twitch.tv/greenham83" target="_blank">greenham</a>. Hosting courtesy of <a href="http://www.twitch.tv/screevo">screevo</a>.
                            <br><br>
                            Want to contribute? Check out the project on <a href="https://github.com/greenham/alttp-bingo" target="_blank">GitHub</a>!
                        </p>
                    </div>
                    <div id="about_bingo">
                        <h1>ALttP Bingo</h1>
                        <p>This is a "Bingo" board for A Link to the Past races.</p>
                        <p>To win, you must complete 5 of the tasks in a row horizontally, vertically, or diagonally.</p>
                        <p>The seed number is used to generate the board. Changing the seed will make a new board.</p>
                        <p>You can click on the squares to turn them green or red. This may help you organize your route planning.</p>

                        <p><br></p>

                        <h2>Rules</h2>
                        <?= $Parsedown->text($rules_markdown); ?>

                        <p><button id="new-board-btn">Make New Board</button></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/js/bingo.js" type="text/javascript"></script>
</body>
</html>