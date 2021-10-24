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

require $_SERVER['DOCUMENT_ROOT'].'/restman/main.php';
$me = getMe();
if(!checkPriv(getLoggedToken(), 1)){
    echo '<meta http-equiv="refresh" content="0;URL=/restman/login/">';
}
if(!empty($_POST['table'])){
    $tableNumber = $_POST['table'];
    $tableCover = $_POST['cover'];
    $alis = $_POST['alis'];
    $print = $_POST['print'];
    $alisNative = alimentsTextToObj($alis, false);
    $alisObj = alimentsTextToObj($alis, true);
    $table = setTable(new Table($tableNumber, $alisObj, time(), $tableCover), TRUE);
    $ord = setOrder(new Order(0, $alisObj, $me, $table, time()), TRUE);
    if($print == "true"){
        printOrderToThermal($ord, $table, $alisNative);
    }
    echo getJson($ord);
}
?>