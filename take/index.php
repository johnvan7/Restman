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
if (!checkPriv(getLoggedToken(), 1)) {
    echo '<meta http-equiv="refresh" content="0;URL=/restman/login/">';
}
$num;
if (!empty($_GET['table'])) {
    $num = intval($_GET['table']);
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
        <title><?php echo getRestName(); ?> - Take</title>
        <link rel="stylesheet" type="text/css" href="/restman/take/style.css" />
        <link rel="stylesheet" type="text/css" href="/restman/style.css" />
        <script src="/restman/jquery.min.js"></script>        
        <script lang="javascript">
            var canSepa = false;
            function updateTable() {
                var tableNumber = document.getElementById('table').value;
                window.location.href = "?table=" + tableNumber;
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
                    var s = "<tr>\n<td class=\"qTdBox\">\n" + q + "\n</td>\n<td>\n" + name + "\n</td>\n<td class=\"minusTdBox\">\n<input class=\"buttonDelAli\" type=\"image\" src=\"Minus.png\" onclick=\"deleteRow(this)\" />\n</td>\n</tr>";
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
                location.href = "#sendOrd";
            }
            function sendOrd(print) {
                if ($('#table_alis tr').length > 0) {
                    var tableNumber = document.getElementById('table').value;
                    if (tableNumber > 0) {
                        var cover = document.getElementById('cover').value;
                        if (cover > 0) {
                            var alis = tableToVar();
                            var params = "table=" + tableNumber + "&alis=" + alis + "&cover=" + cover + "&print=" + print;
                            document.getElementById("loaderdiv").style.display = "block";
                            document.getElementById("main").style.display = "none";
                            var resp = postRequest("sendOrd.php", params);
                            setTimeout(function () {
                                window.location.reload(true);
                            }, 2000);
                        } else {
                            alert("Inserire il coperto");
                        }
                    } else {
                        alert("Inserire il numero del tavolo");
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
        <?php echo $me->Name; ?>
        <br />
        <hr />
        <br />
    <center>
        <div id="loaderdiv" style="display: none">
            <b>Invio ordine in corso</b>
            <p class="loader"></p>
        </div>
    </center>
    <div id="main">
        <b>TAVOLO</b>
        <br />
        <input type="number" id="table" placeholder="Numero tavolo" min="1" onchange="updateTable()" required autocomplete="off" value="<?php echo ((!empty($_GET['table'])) ? $_GET['table'] : ""); ?>" />
        <br />
        <br />
        Coperto
        <br />
        <input type="number" id="cover" placeholder="Coperto tavolo" autocomplete="off" required <?php
        $cover;
        try {
            $table = getTable($_GET['table'], TRUE);
            $cover = $table->Cover;
            if (!empty($cover)) {
                echo 'value="' . $cover . '"';
            } else if (!empty($_GET['table'])) {
                echo 'autofocus';
            }
        } catch (Exception $ex) {
            //echo $ex->getMessage();
        }
        ?>/>
        <br />
        <br />
        <hr />
        <br />
        <b>AGGIUNGI</b>
        <br />
        <input type="number" id="q" value="1" autocomplete="off" required min="1" />
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
        <input id="add_ali" type="image" onclick="addAliUI()" src="Add-New.png" />
        <br />
        <input type="submit" onclick="sepOrd()" id="sepOrd" value="-----------------------------" />

        <section id="noteBtnSec">
            <input id="add_note" type="image" onclick="noteBar()" src="Note.png" />
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
                $table = getTable($_GET['table'], TRUE);
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
        <hr />
        <br />
        <input type="submit" onclick="sendOrd(true)" id="printOrd" value="Stampa" />
        <input type="submit" onclick="sendOrd(false)" id="saveOrd" value="Salva" />
    </div>
</body>
</html>