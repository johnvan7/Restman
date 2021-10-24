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


ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
//error_reporting(E_ALL);

require $_SERVER['DOCUMENT_ROOT'] . '/restman/settings.php';

require $_SERVER['DOCUMENT_ROOT'] . '/restman/libs/escpos-php/autoload.php';

use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;

require $_SERVER['DOCUMENT_ROOT'] . '/restman/types.php';

function execQuery($query) {
    $sqlconn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    $result = mysqli_query($sqlconn, $query);
    return $result;
}

function execQueryReturnId($query) {
    $sqlconn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    mysqli_query($sqlconn, $query);
    return mysqli_insert_id($sqlconn);
}

function hashPass($q) {
    $result = password_hash($q, PASSWORD_DEFAULT, ['cost' => 12]);
    return $result;
}

function checkPass($q, $hash) {
    $result = password_verify($q, $hash);
    return $result;
}

function getRestName() {
    return REST_NAME;
}

function getRestHeader() {
    return REST_HEADER;
}

function getRestFooter() {
    return REST_FOOTER;
}

function getJson($obj) {
    header('Content-Type: application/json');
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
    if (is_a($obj, Response::class)) {
        $ok = ($obj->Code <= 299) ? TRUE : FALSE;
        $result = new Result($ok, [$obj]);
    } else {
        $result = new Result(TRUE, [$obj]);
    }
    return json_encode(get_object_vars($result));
}

function getLoggedToken() {
    if (!empty($_POST['token'])) {
        return $_POST['token'];
    } else if (isset($_COOKIE['auth_key'])) {
        return $_COOKIE['auth_key'];
    } else {
        return "";
    }
}

function generateToken() {
    return bin2hex(openssl_random_pseudo_bytes(TOKEN_LENGTH));
}

function checkPriv($token, $requiredPriv) {
    if (!empty($token)) {
        $query = "SELECT priv FROM users WHERE token='$token'";
        $result = execQuery($query);
        $numrows = mysqli_num_rows($result);
        if ($numrows > 0) {
            $resrow = mysqli_fetch_row($result);
            if (($resrow[0] >= $requiredPriv)) {
                return TRUE;
            }
        }
    }
    return FALSE;
}

function renewToken($userName) {
    if (!empty($userName)) {
        if (checkPriv(getLoggedToken(), 3)) {
            $newtoken = generateToken();
            $query = "UPDATE users SET token='$newtoken' WHERE name='$userName'";
            execQuery($query);
            return $newtoken;
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function logout() {
    $token = getLoggedToken();
    if ($token != "") {
        setcookie("auth_key", "", time() + (1), "/");
        return new Response(200, "ok");
    } else {
        return new Response(403, "forbidden");
    }
}

function login($userName, $userPass) {
    if ((!empty($userName)) && (!empty($userPass))) {
        $query = "SELECT pass,token FROM users WHERE name='$userName'";
        $result = execQuery($query);
        $numrows = mysqli_num_rows($result);
        if ($numrows > 0) {
            $resrow = mysqli_fetch_row($result);
            if (checkPass($userPass, $resrow[0])) {
                $token = $resrow[1];
                setcookie("auth_key", $token, time() + (86400 * 1825), "/");
                return $token;
            } else {
                return new Response(403, "forbidden");
            }
        } else {
            return new Response(404, "not found");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function getMe() {
    $token = getLoggedToken();
    if ($token != "") {
        $query = "SELECT name,priv FROM users WHERE token='$token'";
        $result = execQuery($query);
        $numrows = mysqli_num_rows($result);
        if ($numrows > 0) {
            $resrow = mysqli_fetch_row($result);
            return new User($resrow[0], "", "", $resrow[1]);
        } else {
            return new Response(404, "not found");
        }
    } else {
        return new Response(403, "forbidden");
    }
}

function getUser($userName, $bypass = FALSE) {
    if (!empty($userName)) {
        if ($bypass == TRUE || checkPriv(getLoggedToken(), 3)) {
            $query = "SELECT priv,token FROM users WHERE name='$userName'";
            $result = execQuery($query);
            $numrows = mysqli_num_rows($result);
            if ($numrows > 0) {
                $resrow = mysqli_fetch_row($result);
                return new User($userName, "", "", $resrow[0]);
            } else {
                return new Response(404, "not found");
            }
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function getUserToken($userName, $bypass = FALSE) {
    if (!empty($userName)) {
        if ($bypass == TRUE || checkPriv(getLoggedToken(), 3)) {
            $query = "SELECT token FROM users WHERE name='$userName'";
            $result = execQuery($query);
            $numrows = mysqli_num_rows($result);
            if ($numrows > 0) {
                $resrow = mysqli_fetch_row($result);
                return $resrow[0];
            } else {
                return new Response(404, "not found");
            }
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function getUsers($bypass = FALSE) {
    if ($bypass == TRUE || checkPriv(getLoggedToken(), 3)) {
        $query = "SELECT name,priv FROM users";
        $result = execQuery($query);
        $numrows = mysqli_num_rows($result);
        $users = array();
        for ($i = 0; $i < $numrows; $i++) {
            $resrow = mysqli_fetch_row($result);
            $users[$i] = new User($resrow[0], "", "", $resrow[1]);
        }
        return $users;
    } else {
        return new Response(403, "forbidden");
    }
}

function setUser($user, $bypass = FALSE) {
    if (!empty($user)) {
        if ($bypass == TRUE || checkPriv(getLoggedToken(), 3)) {
            $userName = $user->Name;
            $userPass = $user->Pass;
            $userPriv = $user->Priv;
            $query = "SELECT name FROM users WHERE name='$userName'";
            $result = execQuery($query);
            $userToken = generateToken();
            $newUser = new User($userName, $userPass, "", $userPriv);
            $numrows = mysqli_num_rows($result);
            $passHash = hashPass($userPass);
            if ($numrows > 0) {
                if ($userPass != "") {
                    $query = "UPDATE users SET pass='$passHash',token='$userToken',priv='$userPriv' WHERE name='$userName'";
                } else {
                    $query = "UPDATE users SET priv='$userPriv' WHERE name='$userName'";
                }
                execQuery($query);
                return $newUser;
            } else {
                $query = "INSERT INTO users VALUES('$userName','$passHash','$userToken','$userPriv')";
                execQuery($query);
                return $newUser;
            }
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function delUser($userName) {
    if (!empty($userName)) {
        if (checkPriv(getLoggedToken(), 3)) {
            $query = "DELETE FROM users WHERE(name='$userName')";
            execQuery($query);
            return new Response(200, "ok");
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function getAlimentByName($alimentName, $bypass = FALSE) {
    if (!empty($alimentName)) {
        if ($bypass == TRUE || checkPriv(getLoggedToken(), 3)) {
            $query = "SELECT aliments.id,aliments.price,aliments.category_id,alimentsCategories.name,alimentsCategories.rank FROM aliments JOIN alimentsCategories ON alimentsCategories.id=aliments.category_id WHERE aliments.name='$alimentName'";
            $result = execQuery($query);
            $numrows = mysqli_num_rows($result);
            if ($numrows > 0) {
                $resrow = mysqli_fetch_row($result);
                return new Aliment($resrow[0], $alimentName, 0, $resrow[1], new AlimentCategory($resrow[2], $resrow[3], $resrow[4]), "");
            } else {
                return new Response(404, "not found");
            }
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function getAliment($alimentId, $bypass = FALSE) {
    if (!empty($alimentId)) {
        if ($bypass == TRUE || checkPriv(getLoggedToken(), 3)) {
            $query = "SELECT aliments.name,aliments.price,aliments.category_id,alimentsCategories.name,alimentsCategories.rank FROM aliments JOIN alimentsCategories ON alimentsCategories.id=aliments.category_id WHERE aliments.id='$alimentId'";
            $result = execQuery($query);
            $numrows = mysqli_num_rows($result);
            if ($numrows > 0) {
                $resrow = mysqli_fetch_row($result);
                return new Aliment($alimentId, $resrow[0], 0, $resrow[1], new AlimentCategory($resrow[2], $resrow[3], $resrow[4]), "");
            } else {
                return new Response(404, "not found");
            }
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function getAliments($bypass = FALSE) {
    if ($bypass == TRUE || checkPriv(getLoggedToken(), 3)) {
        $query = "SELECT aliments.id,aliments.name,aliments.price,aliments.category_id,alimentsCategories.name,alimentsCategories.rank FROM aliments JOIN alimentsCategories ON alimentsCategories.id=aliments.category_id ORDER BY alimentsCategories.rank,aliments.name";
        $result = execQuery($query);
        $numrows = mysqli_num_rows($result);
        $aliments = array();
        for ($i = 0; $i < $numrows; $i++) {
            $resrow = mysqli_fetch_row($result);
            $aliments[$i] = new Aliment($resrow[0], $resrow[1], 0, $resrow[2], new AlimentCategory($resrow[3], $resrow[4], $resrow[5]), "");
        }
        return $aliments;
    } else {
        return new Response(403, "forbidden");
    }
}

function getAlimentsFilterByCategoryId($categoryId, $bypass = FALSE) {
    if (!empty($categoryId)) {
        if ($bypass == TRUE || checkPriv(getLoggedToken(), 3)) {
            $query = "SELECT aliments.id,aliments.name,aliments.price FROM aliments JOIN alimentsCategories ON alimentsCategories.id=aliments.category_id WHERE aliments.category_id='$categoryId' ORDER BY aliments.name";
            $result = execQuery($query);
            $numrows = mysqli_num_rows($result);
            $aliments = array();
            $category = getAlimentCategory($categoryId, TRUE);
            for ($i = 0; $i < $numrows; $i++) {
                $resrow = mysqli_fetch_row($result);
                $aliments[$i] = new Aliment($resrow[0], $resrow[1], 0, $resrow[2], $category, "");
            }
            return $aliments;
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function setAliment($aliment) {
    if (!empty($aliment)) {
        if (checkPriv(getLoggedToken(), 3)) {
            $alimentId = $aliment->Id;
            $alimentName = $aliment->Name;
            $alimentPrice = $aliment->Price;
            $alimentCategoryId = $aliment->Category->Id;
            $query = "SELECT id FROM aliments WHERE id='$alimentId'";
            $result = execQuery($query);
            $numrows = mysqli_num_rows($result);
            if ($numrows > 0) {
                $query = "UPDATE aliments SET name='$alimentName',price='$alimentPrice',category_id='$alimentCategoryId' WHERE id='$alimentId'";
                execQuery($query);
            } else {
                $query = "INSERT INTO aliments VALUES(0,'$alimentName','$alimentPrice','$alimentCategoryId')";
                $alimentId = execQueryReturnId($query);
            }
            return new Aliment($alimentId, $alimentName, 0, $alimentPrice, getAlimentCategory($alimentCategoryId, TRUE), "");
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function delAliment($alimentId) {
    if (checkPriv(getLoggedToken(), 3)) {
        if (!empty($alimentId)) {
            $query = "DELETE FROM aliments WHERE id='$alimentId'";
            execQuery($query);
            return new Response(200, "ok");
        } else {
            return new Response(406, "illegal args");
        }
    } else {
        return new Response(403, "forbidden");
    }
}

function getOrder($orderId, $bypass = FALSE) {
    if (!empty($orderId)) {
        if ($bypass == TRUE || checkPriv(getLoggedToken(), 2)) {
            $query = "SELECT user_name,table_number,timestamp FROM orders WHERE id='$orderId'";
            $result = execQuery($query);
            $numrows = mysqli_num_rows($result);
            if ($numrows > 0) {
                $resrow = mysqli_fetch_row($result);
                $table = getTable($resrow[1], TRUE);
                if (is_a($table, Response::class)) {
                    $table = new Table($resrow[1], array(), $resrow[2], -1);
                }
                $user = getUser($resrow[0], TRUE);
                if (is_a($user, Response::class)) {
                    $user = new User($resrow[0], '', '', 1);
                }
                return new Order($orderId, getOrderAliments($orderId, TRUE), $user, $table, $resrow[2]);
            } else {
                return new Response(404, "not found");
            }
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function getOrderAliments($orderId, $bypass = FALSE) {
    if (!empty($orderId)) {
        if ($bypass == TRUE || checkPriv(getLoggedToken(), 2)) {
            $query = "SELECT ordersAliments.aliment_id,ordersAliments.aliment_name,ordersAliments.quantity,ordersAliments.price,ordersAliments.aliment_note, alimentsCategories.id, alimentsCategories.name, alimentsCategories.rank FROM ordersAliments JOIN aliments ON ordersAliments.id=aliments.id JOIN alimentsCategories ON alimentsCategories.id=aliments.category_id WHERE ordersAliments.order_id='$orderId'";
            $result = execQuery($query);
            $numrows = mysqli_num_rows($result);
            $aliments = array();
            if ($numrows > 0) {
                for ($i = 0; $i < $numrows; $i++) {
                    $resrow = mysqli_fetch_row($result);
                    $aliments[$i] = new Aliment($resrow[0], $resrow[1], $resrow[2], $resrow[3], new AlimentCategory($resrow[5], $resrow[6], $resrow[7]), $resrow[4]);
                }
            }
            return $aliments;
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function getOrders($bypass = FALSE) {
    if ($bypass == TRUE || checkPriv(getLoggedToken(), 2)) {
        $query = "SELECT id,user_name,table_number,timestamp FROM orders ORDER BY timestamp DESC";
        $result = execQuery($query);
        $numrows = mysqli_num_rows($result);
        $orders = array();
        for ($i = 0; $i < $numrows; $i++) {
            $resrow = mysqli_fetch_row($result);
            $table = getTable($resrow[2], TRUE);
            if (is_a($table, Response::class)) {
                $table = new Table($resrow[2], array(), $resrow[3], -1);
            }
            $user = getUser($resrow[1], TRUE);
            if (is_a($user, Response::class)) {
                $user = new User($resrow[1], '', '', 1);
            }
            $orders[$i] = new Order($resrow[0], getOrderAliments($resrow[0], TRUE), $user, $table, $resrow[3]);
        }
        return $orders;
    } else {
        return new Response(403, "forbidden");
    }
}

function getOrdersSelected($from, $to, $bypass = FALSE) {
    if ($bypass == TRUE || checkPriv(getLoggedToken(), 2)) {
        $query = "SELECT id FROM orders WHERE timestamp BETWEEN '$from' AND '$to' ORDER BY timestamp DESC";
        $result = execQuery($query);
        $numrows = mysqli_num_rows($result);
        $orders = array();
        for ($i = 0; $i < $numrows; $i++) {
            $resrow = mysqli_fetch_row($result);
            $table = getTable($resrow[2], TRUE);
            if (is_a($table, Response::class)) {
                $table = new Table($resrow[2], array(), $resrow[3], -1);
            }
            $user = getUser($resrow[1], TRUE);
            if (is_a($user, Response::class)) {
                $user = new User($resrow[1], '', '', 1);
            }
            $orders[$i] = new Order($resrow[0], getOrderAliments($resrow[0], TRUE), $user, $table, $resrow[3]);
        }
        return $orders;
    } else {
        return new Response(403, "forbidden");
    }
}

function setOrder($order, $bypass = FALSE) {
    if (!empty($order)) {
        if ($bypass == TRUE || checkPriv(getLoggedToken(), 1)) {
            $username = $order->User->Name;
            $tableNumber = $order->Table->Number;
            $timestamp = date('Y-m-d H:i:s');
            $query = "INSERT INTO orders VALUES(0,'$username','$tableNumber','$timestamp')";
            $curr = execQueryReturnId($query);
            $order->Table->Timestamp = $timestamp;
            setOrderAliments($curr, $order->Aliments, TRUE);
            $orderNew = new Order($curr, $order->Aliments, $order->User, getTable($order->Table->Number, TRUE), $timestamp);
            return $orderNew;
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function setOrderAliments($orderId, $aliments, $bypass = FALSE) {
    if (!empty($orderId)) {
        if ($bypass == TRUE || checkPriv(getLoggedToken(), 1)) {
            for ($i = 0; $i < count($aliments); $i++) {
                $id = $aliments[$i]->Id;
                $aliName = $aliments[$i]->Name;
                $aliNote = $aliments[$i]->Note;
                $quantity = $aliments[$i]->Quantity;
                $price = $aliments[$i]->Price;
                $query = "INSERT INTO ordersAliments VALUES(0,'$orderId','$id','$aliName','$aliNote','$quantity','$price')";
                execQuery($query);
            }
            return $aliments;
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function delOrder($orderId) {
    if (!empty($orderId)) {
        if (checkPriv(getLoggedToken(), 3)) {
            $query = "DELETE FROM orders WHERE id=$orderId";
            delOrderAliments($orderId);
            execQuery($query);
            return new Response(200, "ok");
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function delOrderAliments($orderId) {
    if (!empty($orderId)) {
        if (checkPriv(getLoggedToken(), 3)) {
            $query = "DELETE FROM ordersAliments WHERE order_id=$orderId";
            execQuery($query);
            return new Response(200, "ok");
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function getTable($number, $bypass = FALSE) {
    if (!empty($number)) {
        if ($bypass == TRUE || checkPriv(getLoggedToken(), 2)) {
            $query = "SELECT timestamp,cover FROM tables_ WHERE number='$number'";
            $result = execQuery($query);
            $numrows = mysqli_num_rows($result);
            if ($numrows > 0) {
                $resrow = mysqli_fetch_row($result);
                return new Table($number, getTableAliments($number, TRUE), $resrow[0], $resrow[1]);
            } else {
                return new Response(404, "not found");
            }
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function getTableAliments($tableNumber, $bypass = FALSE) {
    if (!empty($tableNumber)) {
        if ($bypass == TRUE || checkPriv(getLoggedToken(), 2)) {
            $query = "SELECT tablesAliments.aliment_id,tablesAliments.aliment_name,tablesAliments.quantity,tablesAliments.price,tablesAliments.aliment_note, alimentsCategories.id, alimentsCategories.name, alimentsCategories.rank FROM tablesAliments JOIN aliments ON tablesAliments.aliment_id=aliments.id JOIN alimentsCategories ON alimentsCategories.id=aliments.category_id WHERE tablesAliments.table_number='$tableNumber'";
            $result = execQuery($query);
            $numrows = mysqli_num_rows($result);
            $aliments = array();
            if ($numrows > 0) {
                for ($i = 0; $i < $numrows; $i++) {
                    $resrow = mysqli_fetch_row($result);
                    $aliments[$i] = new Aliment($resrow[0], $resrow[1], $resrow[2], $resrow[3], new AlimentCategory($resrow[5], $resrow[6], $resrow[7]), $resrow[4]);
                }
            }
            return $aliments;
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function setTable($table) {
    if (!empty($table)) {
        if (checkPriv(getLoggedToken(), 1)) {
            $number = $table->Number;
            $date = date_create($table->Timestamp);
            $timestamp = date('Y-m-d H:i:s');
            $cover = $table->Cover;
            $query = "SELECT timestamp,cover FROM tables_ WHERE number=$number";
            $result = execQuery($query);
            $numrows = mysqli_num_rows($result);
            $aliments = $table->Aliments;
            if ($numrows > 0) {
                $query = "UPDATE tables_ SET timestamp='$timestamp',cover='$cover' WHERE number='$number'";
            } else {
                $query = "INSERT INTO tables_ VALUES('$number','$timestamp','$cover')";
                delTableAliments($number, TRUE);
            }
            execQuery($query);
            $alimentsNew = $aliments;
            if ($aliments != NULL) {
                $alimentsNew = setTableAliments($number, $aliments, TRUE);
            }
            return new Table($number, $alimentsNew, $timestamp, $cover);
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function setTableAliments($tableNumber, $aliments, $bypass = FALSE) {
    if (!empty($tableNumber)) {
        if ($bypass == TRUE || checkPriv(getLoggedToken(), 1)) {
            for ($i = 0; $i < count($aliments); $i++) {
                $id = $aliments[$i]->Id;
                $aliName = $aliments[$i]->Name;
                $aliNote = $aliments[$i]->Note;
                $quantity = $aliments[$i]->Quantity;
                $price = $aliments[$i]->Price;
                $query = "INSERT INTO tablesAliments VALUES(0,'$tableNumber','$id','$aliName','$aliNote','$quantity','$price')";
                execQuery($query);
            }
            return $aliments;
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function delTable($number) {
    if (!empty($number)) {
        if (checkPriv(getLoggedToken(), 3)) {
            $query = "DELETE FROM tables_ WHERE number=$number";
            delTableAliments($number);
            execQuery($query);
            return new Response(200, "ok");
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function delTableAliments($number) {
    if (!empty($number)) {
        if (checkPriv(getLoggedToken(), 3)) {
            $query = "DELETE FROM tablesAliments WHERE table_number=$number";
            execQuery($query);
            return new Response(200, "ok");
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function getTables($bypass = FALSE) {
    if ($bypass == TRUE || checkPriv(getLoggedToken(), 2)) {
        $query = "SELECT number,timestamp,cover FROM tables_ ORDER BY timestamp DESC";
        $result = execQuery($query);
        $numrows = mysqli_num_rows($result);
        $tables = array();
        for ($i = 0; $i < $numrows; $i++) {
            $resrow = mysqli_fetch_row($result);
            $tableNumber = $resrow[0];
            $tables[$i] = new Table($tableNumber, getTableAliments($tableNumber, TRUE), $resrow[1], $resrow[2]);
        }
        return $tables;
    } else {
        return new Response(403, "forbidden");
    }
}

function getAlimentCategoryByName($name, $bypass = FALSE) {
    if ($name != null) {
        if ($bypass == TRUE || checkPriv(getLoggedToken(), 3)) {
            $query = "SELECT id,rank FROM alimentsCategories WHERE name='$name'";
            $result = execQuery($query);
            $numrows = mysqli_num_rows($result);
            if ($numrows > 0) {
                $resrow = mysqli_fetch_row($result);
                return new AlimentCategory($resrow[0], $name, $resrow[1]);
            } else {
                return new Response(404, "not found");
            }
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function getAlimentCategory($id, $bypass = FALSE) {
    if ($id != null) {
        if ($bypass == TRUE || checkPriv(getLoggedToken(), 3)) {
            $query = "SELECT name,rank FROM alimentsCategories WHERE id='$id'";
            $result = execQuery($query);
            $numrows = mysqli_num_rows($result);
            if ($numrows > 0) {
                $resrow = mysqli_fetch_row($result);
                return new AlimentCategory($id, $resrow[0], $resrow[1]);
            } else {
                return new Response(404, "not found");
            }
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function getAlimentCategories($bypass = FALSE) {
    if ($bypass == TRUE || checkPriv(getLoggedToken(), 3)) {
        $query = "SELECT id,name,rank FROM alimentsCategories ORDER BY rank";
        $result = execQuery($query);
        $numrows = mysqli_num_rows($result);
        $alimentcategories = array();
        for ($i = 0; $i < $numrows; $i++) {
            $resrow = mysqli_fetch_row($result);
            $alimentcategories[$i] = new AlimentCategory($resrow[0], $resrow[1], $resrow[2]);
        }
        return $alimentcategories;
    } else {
        return new Response(403, "forbidden");
    }
}

function setAlimentCategory($alimentCategory) {
    if (!empty($alimentCategory)) {
        if (checkPriv(getLoggedToken(), 3)) {
            $id = $alimentCategory->Id;
            $name = $alimentCategory->Name;
            $rank = $alimentCategory->Rank;
            $query = "SELECT id FROM alimentsCategories WHERE id=$id";
            $result = execQuery($query);
            $numrows = mysqli_num_rows($result);
            $query = "";
            if ($numrows > 0) {
                $query = "UPDATE alimentsCategories SET name='$name',rank='$rank' WHERE id='$id'";
            } else {
                $cats = getAlimentCategories(TRUE);
                $rank = $cats[count($cats) - 1]->Rank + 1;
                $query = "INSERT INTO alimentsCategories VALUES(0,'$name',$rank)";
            }
            execQuery($query);
            return new AlimentCategory($id, $name, $rank);
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function delAlimentCategory($id) {
    if (!empty($id)) {
        if (checkPriv(getLoggedToken(), 3)) {
            $query = "DELETE FROM alimentsCategories WHERE id=$id";
            execQuery($query);
            return new Response(200, "ok");
        } else {
            return new Response(403, "forbidden");
        }
    } else {
        return new Response(406, "illegal args");
    }
}

function alimentsTextToObj($alistext, $clear) {
    $aliarr = array();
    $c = 0;
    $aliarrtext = explode(";", $alistext);
    for ($i = 0; $i < count($aliarrtext) - 1; $i++) {
        $aliname = explode("x", $aliarrtext[$i])[1];
        $alinote = "";
        if (strpos($aliname, ' | ') !== false) {
            $alinote = explode(" | ", $aliname)[1];
            $aliname = explode(" | ", $aliname)[0];
        }
        if ($aliname == "-----------------------") {
            if ($clear == false) {
                $aliarr[$c] = new Aliment(0, $aliname, 0, 0, new AlimentCategory(-1, "special", -1));
                $c++;
            }
        } else {
            $aliarrquant = explode("x", $aliarrtext[$i])[0];
            $ali = getAlimentByName($aliname, TRUE);
            $ali->Quantity = $aliarrquant;
            $ali->Note = $alinote;
            $aliarr[$c] = $ali;
            $c++;
        }
    }
    return $aliarr;
}

function printOrderToThermal($order, $table, $aliments, $n = 2) {
    $connector = new FilePrintConnector(THERMAL_DEVICE);
    $printer = new Printer($connector);
    $line = "\n————————————————————————————————————————————————\n\n";

    for ($cont = 1; $cont <= $n; $cont++) {
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("-");
        $printer->feed(1);
        $image = EscposImage::load($_SERVER['DOCUMENT_ROOT'] . '/restman/res/logoThermal.png', false);
        $printer->bitImage($image);
        $printer->setLineSpacing(3);
        $printer->text($line);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer->text("Ordine N°" . $order->Id . "\n");
        $printer->selectPrintMode();
        $printer->text($line);
        $printer->text("TAVOLO: ");
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer->text($table->Number . "\n");
        $printer->selectPrintMode();
        $printer->text($line);
        $printer->text("Coperto: ");
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer->text($table->Cover . "\n");
        $printer->selectPrintMode();
        $printer->text($line);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        for ($i = 0; $i < count($aliments); $i++) {
            $quant = $aliments[$i]->Quantity;
            $name = $aliments[$i]->Name;
            $note = $aliments[$i]->Note;
            if ($name == "-----------------------") {
                $printer->text("——*******************——\n");
            } else {
                $printer->text($quant . "x " . $name . " " . strtoupper($note) . "\n");
            }
            $printer->feed(1);
        }
        $printer->selectPrintMode();
        $printer->text($line);
        $printer->text(date('H:i', time()) . "\n");
        $printer->text(date('d-m-Y', time()) . "\n");
        $printer->text($order->User->Name . "\n");
        $printer->text("Copia " . $cont . "\n");
        $printer->text($line);

        $printer->feed(2);

        $printer->cut();
        $printer->pulse();
    }
    $printer->close();

    if (BEEP_ENABLED) {
        runBeep();
    }
}

function printBillToThermal($table, $bypass = FALSE) {
    if ((checkPriv(getLoggedToken(), 3)) || $bypass == TRUE) {
        $connector = new NetworkPrintConnector(THERMAL_DEVICE_1, 9100);
        $printer = new Printer($connector);
        $line = "\n————————————————————————————————————————————————\n\n";

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("-Conto-");
        $printer->feed(1);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer->text(getRestName());
        $printer->selectPrintMode();
        $printer->feed(1);
        $image = EscposImage::load($_SERVER['DOCUMENT_ROOT'] . '/restman/res/logoThermal.png', false);
        $printer->bitImage($image);
        $printer->setLineSpacing(3);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer->text("Riepilogo tavolo N " . $table->Number);
        $printer->selectPrintMode();
        $printer->text($line);

        $total = 0;
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text($table->Cover . " x Coperto\n");
        $price = (COVER_PRICE * $table->Cover);
        $total += $price;
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->text(number_format($price, 2) . "\n");

        $aliments = $table->Aliments;
        for ($i = 0; $i < count($aliments); $i++) {
            $quant = $aliments[$i]->Quantity;
            $name = $aliments[$i]->Name;
            $price = $aliments[$i]->Price * $quant;
            $total += $price;
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text($quant . " x " . $name . "\n");
            $printer->setJustification(Printer::JUSTIFY_RIGHT);
            $printer->text(number_format($price, 2) . "\n");
        }

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->selectPrintMode();
        $printer->text($line);

        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer->text("TOTALE ");
        $printer->text(number_format($total, 2));

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->selectPrintMode();
        $printer->text($line);

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text(date('d-m-Y', time()) . " ");
        $printer->text(date('H:i', time()) . "\n");

        $printer->feed(1);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->selectPrintMode();
        $printer->text("Grazie e arrivederci");

        $printer->feed(2);
        $printer->cut();
        $printer->pulse();
        $printer->close();
    } else {
        return new Response(403, "forbidden");
    }
}

function runBeep() {
    shell_exec("gpio -g mode " . BEEP_PIN . " out && gpio -g write " . BEEP_PIN . " 1 && sleep 2 && gpio -g write " . BEEP_PIN . " 0");
}

function getCoverPrice() {
    if (checkPriv(getLoggedToken(), 3)) {
        return COVER_PRICE;
    } else {
        return new Response(403, "forbidden");
    }
}

?>