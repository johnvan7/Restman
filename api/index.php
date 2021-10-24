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

$self = str_replace("index.php", "", $_SERVER['PHP_SELF']);
$string = str_replace($self, "", $_SERVER['REQUEST_URI']);
$a = explode("/", $string);

echo getJson(("_" . $a[0])());

function _delAliment() {
    return delAliment($_POST['alimentId']);
}

function _delAlimentCategory() {
    return delAlimentCategory($_POST['categoryId']);
}

function _delTable() {
    return delTable($_POST['tableNumber']);
}

function _delUser() {
    return delUser($_POST['userName']);
}

function _getAliment() {
    return getAliment($_POST['alimentId']);
}

function _getAlimentByName() {
    return getAlimentByName($_POST['alimentName']);
}

function _getAlimentCategories() {
    return getAlimentCategories();
}

function _getAlimentCategory() {
    return getAlimentCategory($_POST['categoryId']);
}

function _getAlimentCategoryByName() {
    return getAlimentCategoryByName($_POST['categoryName']);
}

function _getAliments() {
    return getAliments();
}

function _getAlimentsFilterByCategoryId(){
    return getAlimentsFilterByCategoryId($_POST['categoryId']);
}

function _getCoverPrice() {
    return getCoverPrice();
}

function _getMe() {
    return getMe();
}

function _getOrder() {
    return getOrder($_POST['orderId']);
}

function _getOrders() {
    return getOrders();
}

function _getOrdersSelected() {
    return getOrdersSelected($_POST['from'], $_POST['to']);
}

function _getRestFooter() {
    return getRestFooter();
}

function _getRestHeader() {
    return getRestHeader();
}

function _getRestName() {
    return getRestName();
}

function _getTable() {
    return getTable($_POST['tableNumber']);
}

function _getTables() {
    return getTables();
}

function _getUser() {
    return getUser($_POST['userName']);
}

function _getUserToken() {
    return getUserToken($_POST['userName']);
}

function _getUsers() {
    return getUsers();
}

function _renewToken() {
    return renewToken($_POST['userName']);
}

function _setAliment() {
    $aliment = json_decode($_POST['obj']);
    return setAliment($aliment);
}

function _setAlimentCategory() {
    $category = json_decode($_POST['obj']);
    return setAlimentCategory($category);
}

function _setOrder() {
    $order = json_decode($_POST['obj']);
    return setOrder($order);
}

function _setTable() {
    $table = json_decode($_POST['obj']);
    return setTable($table);
}

function _setUser() {
    $user = json_decode($_POST['obj']);
    return setUser($user);
}

function _printBillToThermal() {
    $table = json_decode($_POST['table']);
    return printBillToThermal($table);
}

function _printOrderToThermal() {
    $order = json_decode($_POST['order']);
    $table = json_decode($_POST['table']);
    $alis = json_decode($_POST['alis']);
    return printOrderToThermal($order, $table, $alis);
}

function _login() {
    return login($_POST['userName'], $_POST['userPass']);
}