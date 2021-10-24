<?php
/* * *
 * Copyright (C) 2017-2021    Giovanni Vella
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/restman/main.php';

$me = getMe();
if (!checkPriv(getLoggedToken(), 2)) {
    echo '<meta http-equiv="refresh" content="0;URL=/restman/login/">';
}
?>
<!DOCTYPE html>
<html> 
    <head>
        <meta http-equiv="refresh" content="50" >
        <meta charset="UTF-8">
        <meta name="author" content="Giovanni Vella">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo getRestName(); ?> - Touch</title>
        <link rel="stylesheet" type="text/css" href="/restman/style.css" />
        <link rel="stylesheet" type="text/css" href="/restman/touch/style.css" />
        <script src="/restman/jquery.min.js"></script>  
        <script lang="javascript">
            function goToTable(num) {
                location.href = "/restman/touch/table.php?num=" + num;
            }
            function newTable() {
                var num = document.getElementById('selTabNum').value;
                goToTable(num);
            }
        </script>
    </head>
    <body>
        <p class="title">
            <?php echo getRestName(); ?>
        </p>
        <div id="menu">
            <input type="submit" value="Tavoli" onclick="location.href = '/restman/touch/';" />
        </div>
        <hr />
        <div id="tables">
            
            <section id="newTableSec">
                Nuovo tavolo: 
                <select id="selTabNum">
                    <optgroup>
                        <?php
                        for ($i = 1; $i <= 50; $i++) {
                            echo '<option value="' . $i . '">' . $i . '</option>';
                        }
                        ?>
                    </optgroup>
                </select>
                <input id="newTableBtn" type="image" onclick="newTable()" src="/restman/take/Add-New.png" />
            </section>

            <br /><br />
            <?php
            $tables = array_reverse(getTables());
            $index = 0;
            for ($i = 0; $index < count($tables); $i++) {
                for ($j = 0; $j < 10 && $index < count($tables); $j++) {
                    $value = $tables[$index]->Number;
                    echo '<input type="button" class="tableNumBtn" onclick="goToTable(' . $value . ')" value="' . $value . '" />';
                    $index++;
                }
            }
            ?>
        </div>
    </body>
</html>