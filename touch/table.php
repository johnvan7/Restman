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
$num;
if (!empty($_GET['num'])) {
    $num = intval($_GET['num']);
}
$notes = "";
if(isset($_COOKIE['usual_notes'])) {
    $notes = urldecode($_COOKIE['usual_notes']);
}
?>
<!DOCTYPE html>
<html> 
    <head>
        <meta charset="UTF-8">
        <meta name="author" content="Giovanni Vella">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo getRestName(); ?> - Touch</title>
        <link rel="stylesheet" type="text/css" href="/restman/style.css" />
        <link rel="stylesheet" type="text/css" href="/restman/touch/style.css?<?php echo date('l jS \of F Y h:i:s A'); ?>" />
        <script src="/restman/jquery.min.js"></script>
        <script lang="javascript">
            var canSepa = false;
            function goToTable(num) {
                location.href = "/restman/touch/table.php?num=" + num;
            }
            function deleteRow(btn) {
                var row = btn.parentNode.parentNode;
                row.parentNode.removeChild(row);
            }
            function addAliUI() {
                var name = document.getElementById('select_aliments').value;
                var q = document.getElementById('q').value;
                addAli(name, q, true);
                document.getElementById('q').value = "1";
                document.getElementById("noteSec").style.display = "none";
                document.getElementById('noteBox').value = "";
            }
            function addAli(name, q, check) {
                var noteValue = document.getElementById('noteBox').value;
                if (noteValue != "") {
                    name += " | " + noteValue;
                    var usual_notes_html = document.getElementById("usual_notes").innerHTML;
                    if (!usual_notes_html.match(noteValue)) {
                        document.getElementById("usual_notes").innerHTML = usual_notes_html + "\n<option value=\"" + noteValue + "\">";
                        var cookieName = "usual_notes";
                        var oldCont = getCookie(cookieName);
                        if(!oldCont.match(noteValue))
                            setCookie("usual_notes", oldCont + encodeURI(noteValue + "--"), 1825);
                    }
                }
                var found = false;
                var rowCount = $('#table_alis tr').length;
                var tbl = $('#table_alis');
                if (check)
                    for (var i = 0; i < rowCount; i++) {
                        var ord_q = normalize($(tbl).find('tr').eq(i).find('td').eq(0).text());
                        var ord = normalize($(tbl).find('tr').eq(i).find('td').eq(1).text());
                        if (ord == name) {
                            var new_q = parseInt(ord_q) + parseInt(q);
                            $(tbl).find('tr').eq(i).find('td').eq(0).text(new_q);
                            found = true;
                            break;
                        }
                    }
                if (found == false) {
                    var table_alis = document.getElementById('table_alis').innerHTML;
                    var s = "<tr>\n<td class=\"qTdBox\">\n" + q + "\n</td>\n<td>\n" + name + "\n</td>\n<td class=\"minusTdBox\">\n<input class=\"buttonDelAli\" type=\"image\" src=\"/restman/take/Minus.png\" onclick=\"deleteRow(this)\" />\n</td>\n</tr>";
                    document.getElementById('table_alis').innerHTML = table_alis + s;
                    canSepa = true;
                }
                scrollToEndTable();
            }
            function tableToVar() {
                var rowCount = $('#table_alis tr').length;
                var str = "";
                var tbl = $('#table_alis');
                for (var i = 0; i < rowCount; i++) {
                    var q = normalize($(tbl).find('tr').eq(i).find('td').eq(0).text());
                    var ord = normalize($(tbl).find('tr').eq(i).find('td').eq(1).text());
                    str = str + q + "x" + ord + ";";
                }
                return str;
            }
            function normalize(str) {
                str = str.replace(/\s+/g, " ").replace(/^\s|\s$/g, "");
                str = str.replace(/^\s+/, '');
                for (var i = str.length - 1; i >= 0; i--) {
                    if (/\S/.test(str.charAt(i))) {
                        str = str.substring(0, i + 1);
                        break;
                    }
                }
                return str;
            }
            function scrollToEndTable() {
                location.href = "#end";
            }
            function sendOrd(print) {
                if ($('#table_alis tr').length > 0) {
                    var tableNumber = <?php echo $num; ?>;
                    var cover = document.getElementById('cover').value;
                    if (cover > 0) {
                        var alis = tableToVar();
                        var params = "table=" + tableNumber + "&alis=" + alis + "&cover=" + cover + "&print=" + print;
                        document.getElementById("loaderdiv").style.display = "block";
                        document.getElementById("main").style.display = "none";
                        var resp = postRequest("/restman/take/sendOrd.php", params);
                        setTimeout(function () {
                            window.location.reload(true);
                        }, 1000);
                    } else {
                        alert("Inserire il coperto");
                    }
                } else {
                    alert("Ordine vuoto!");
                }
            }
            function postRequest(url, params) {
                var xhr = new XMLHttpRequest();
                xhr.open("POST", url, true);
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhr.send(params);
                return xhr.responseText;
            }
            function sepOrd() {
                if (canSepa) {
                    addAli("-----------------------", 0, false);
                    canSepa = false;
                }
            }
            function noteBar() {
                if (document.getElementById("noteSec").style.display == "none") {
                    document.getElementById("noteSec").style.display = "block";
                } else {
                    document.getElementById("noteSec").style.display = "none";
                    document.getElementById('noteBox').value = "";
                }

            }
            function setCookie(cname, cvalue, exdays) {
                var d = new Date();
                d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
                var expires = "expires=" + d.toUTCString();
                document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
            }

            function getCookie(cname) {
                var name = cname + "=";
                var ca = document.cookie.split(';');
                for (var i = 0; i < ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == ' ') {
                        c = c.substring(1);
                    }
                    if (c.indexOf(name) == 0) {
                        return c.substring(name.length, c.length);
                    }
                }
                return "";
            }
        </script>
    </head>
    <body>
        <p class="title">
            <?php echo getRestName(); ?>            
        </p>
        <div id="menu">
            <input type="submit" value="Tavoli" onclick="location.href = '/restman/touch/';" />
            <input type="submit" value="Aggiorna" onclick="location.href = '/restman/touch/table.php?num=<?php echo $num; ?>';" />
            <input id="sendOrdBtn" type="submit" value="Salva" onclick="sendOrd(false)" />
            <input id="printOrdBtn" type="submit" value="Stampa" onclick="sendOrd(true)" />
        </div>
        <hr />
        <p class="title">
            Tavolo <?php echo $num; ?>           
        </p>
    <center>
        <div id="loaderdiv" style="display: none">
            <b>Invio ordine in corso</b>
            <p class="loader"></p>
        </div>
    </center>
    <div id="main">
        <p id="coverSec">
            Coperto:
            <?php
            $cover;
            try {
                $table = getTable($_GET['num'], TRUE);
                $cover = $table->Cover;
            } catch (Exception $ex) {
                //echo $ex->getMessage();
            }
            ?>
            <select id="cover">
                <optgroup>
                    <?php
                    for ($i = 1; $i <= 50; $i++) {
                        echo '<option value="' . $i . '"' . ((!empty($cover) && $cover == $i) ? ' selected' : '') . '>' . $i . '</option>';
                    }
                    ?>
                </optgroup>
            </select>
        </p>
        <b>AGGIUNGI</b>
        <br />
        <select id="q">
            <optgroup>
                <?php
                for ($i = 1; $i <= 50; $i++) {
                    echo '<option value="' . $i . '">' . $i . '</option>';
                }
                ?>
            </optgroup>
        </select>
        <select id="select_aliments">
            <?php
            $aliscats = getAlimentCategories(TRUE);
            $aliments = getAliments(TRUE);
            for ($i = 0; $i < count($aliscats); $i++) {
                $currCatName = $aliscats[$i]->Name;
                echo '<optgroup label="' . $currCatName . '">';
                for ($x = 0; $x < count($aliments); $x++) {
                    if ($aliments[$x]->Category->Name == $currCatName) {
                        $currAliName = $aliments[$x]->Name;
                        echo '<option value="' . $currAliName . '">' . $currAliName . '</option>';
                    }
                }
                echo '</optgroup>';
            }
            ?>
        </select>
        <input id="add_ali" type="image" onclick="addAliUI()" src="/restman/take/Add-New.png" />
        <br />
        <input type="submit" onclick="sepOrd()" id="sepOrd" value="-----------------------------" />
        
        <section id="noteBtnSec">
            <input id="add_note" type="image" onclick="noteBar()" src="/restman/take/Note.png" />
        </section>

        <section id="noteSec" style="display: none;">
            <hr>
            Nota
            <input type="text" id="noteBox" list="usual_notes" value="">

            <datalist id="usual_notes">
                <?php
                if (strlen($notes) > 1) {
                    $noteex = explode('--', $notes);
                    for ($i = 0; $i < count($noteex)-1; $i++) {
                        echo '<option value="' . $noteex[$i] . '">';
                    }
                }
                ?>
            </datalist>
        </section>
        
        <hr />
        <br />
        <table id="old_table_alis">
            <?php
            try {
                $table = getTable($num, TRUE);
                $alis = $table->Aliments;
                for ($i = 0; $i < count($alis); $i++) {
                    $q = $alis[$i]->Quantity;
                    $name = $alis[$i]->Name;
                    $note = $alis[$i]->Note;
                    if (strlen($note) > 0) {
                        $name = $name . " | " . $note;
                    }
                    echo "<tr>\n<td class=\"qTdBox\">\n" . $q . "\n</td>\n<td>\n" . $name . "\n</td>\n<td class=\"minusTdBox\">\n</td>\n</tr>";
                }
            } catch (Exception $ex) {
                echo $ex->getMessage();
            }
            ?>
        </table>
        <table id="table_alis">
        </table>
        <br />
        <hr id="end" />
    </div>
</body>
</html>