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
 */

/**
 * MYSQL settings.  Update any of the following values if you don't use defaults recommended in the tutorial.
 */

function global_val($key) {
    $a = array();
    $a["servername"] = "localhost"; //mysql servername
    $a["username"] = "root"; //mysql username
    $a["password"] = "turtlebot"; //mysql password
    $a["databasename"] = "turtlebot"; //mysql database
    if(!array_key_exists($key,$a)) {
        die("global value does not exist [$key]");
    }
    return $a[$key];
}