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

class Table {
    public $Number;
    public $Aliments;
    public $Timestamp;
    public $Cover;
    
    
    public function __construct($number, $aliments, $timestamp, $cover) {
        $this->Number = $number;
        $this->Aliments = $aliments;
        $this->Timestamp = $timestamp;
        $this->Cover = $cover;
    }
}

class Aliment {
    public $Id;
    public $Name;
    public $Quantity;
    public $Price;
    public $Category;
    public $Note;
    
    public function __construct($id, $name,$quantity, $price, $category, $note) {
        $this->Id = $id;
        $this->Name = (is_string($name)) ? $name : "";
        $this->Quantity = $quantity;
        $this->Price =  $price;
        $this->Category = $category;
        $this->Note = (is_string($note)) ? $note : "";
    }
}
class AlimentCategory {
    public $Id;
    public $Name;
    public $Rank;
    
    public function __construct($id, $name, $rank) {
        $this->Id = $id;
        $this->Name = (is_string($name)) ? $name : "";
        $this->Rank = $rank;
    }
}

class User {
    public $Name;
    public $Pass;
    public $Token;
    public $Priv;


    public function __construct($name, $pass, $token, $priv) {
        $this->Name = (is_string($name)) ? $name : "";
        $this->Pass = (is_string($pass)) ? $pass : "";
        $this->Token = (is_string($token)) ? $token : "";
        $this->Priv = $priv;
    }
}

class Order {
    public $Id;
    public $Aliments;
    public $User;
    public $Table;
    public $Timestamp;
    
    public function __construct($id,$aliments, $user, $table, $timestamp) {
        $this->Id = $id;
        $this->Aliments = $aliments;
        $this->User = $user;
        $this->Table = $table;
        $this->Timestamp = $timestamp;
    }
}

class Response
{
    public $Code;
    public $Text;
    
    public function __construct($code, $text) {
        $this->Code = (is_int($code)) ? $code : -1;
        $this->Text = $text;
    }
}

class Result
{
    public $Ok;
    public $Results;
    
    public function __construct($ok, $results) {
        $this->Ok = (is_bool($ok)) ? $ok : NULL;
        $this->Results = $results;
    }
}
?>