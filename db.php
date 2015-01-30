<?php
/**
 * User: marksilliman
 * Date: 1/28/15
 * Time: 12:20 PM
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * */

/*
 * Database connection
 */

require_once "./config.php";

class db {
    private static $conn;

    //conn - connect to MYSQL
    //to make life easier for first time developers this function automatically creates the database & table if they are missing
    public static function conn() {
        self::$conn = new mysqli(global_val("servername"), global_val("username"), global_val("password"));

        // Check connection
        if (self::$conn->connect_error) {
            die("Connection failed: " . self::$conn->connect_error);
        }

        //check if we need to create the database
        self::create_database();
        self::$conn->select_db(global_val("databasename"));
        //check if we need to create tables
        self::create_tables();

        return self::$conn;
    }
    //end conn

    //create_database
    private static function create_database() {
        self::$conn->query("CREATE DATABASE IF NOT EXISTS " . global_val("databasename")) or die("create_database failed");
    }
    //end create_database

    //create_tables
    private static function create_tables() {
        $result = self::$conn->query("select * from QUEUE limit 1");
        if(empty($result)) {
            $query = "CREATE TABLE QUEUE (
                          ID INT AUTO_INCREMENT,
                          point_x DOUBLE,
                          point_y DOUBLE,
                          point_z DOUBLE,
                          quat_x DOUBLE,
                          quat_y DOUBLE,
                          quat_z DOUBLE,
                          quat_w DOUBLE,
                          date_created DATETIME,
                          status TINYINT,
                          PRIMARY KEY  (ID)
                          )";
            self::$conn->query($query) or die("create table failed");
        }
    }
    //end create_tables
} 