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

require_once "./db.php";

//insert a pose into queue
if(isset($_GET['push'])) {
    $n = new coffee_queue();
    $n->push();
}

//turtlebot updates an ID when it has successfully reached a POSE (or failed)
if(isset($_GET['update'])) {
    $n = new coffee_queue();
    $n->update();
}

//pop gives it the next pending pose
if(isset($_GET['pop'])) {
    $n = new coffee_queue();
    $n->pop();
}

if(isset($_GET['statuscheck'])) {
    $n = new coffee_queue();
    $n->status();
}

if(isset($_GET['printqueue'])) {
    $n = new coffee_queue();
    $n->print_queue();
}

//class
class coffee_queue {
    private $conn;

    //constructor
    function __construct() {
        $this->conn = db::conn(); //connect to mysql
    }
    //end constructor

    //push
    public function push() {
        //defaults
        $pose = array();
        $pose["point_x"] = 0;
        $pose["point_y"] = 0;
        $pose["point_z"] = 0;
        $pose["quat_x"] = 0;
        $pose["quat_y"] = 0;
        $pose["quat_z"] = 0;
        $pose["quat_w"] = 0;

        //overwrite with passed values (from the URL)
        foreach($pose as $key => $val) {
            if(isset($_GET[$key]) && is_numeric($_GET[$key])) { //is_numeric is a very poor, incomplete security feature to prevent basic forms of SQL injection
                $pose[$key] = $_GET[$key];
            }
        }

        $a = array();
        if(!$this->exists($pose)) { //don't add duplicate pending requests
            $this->conn->query("insert into QUEUE values(NULL," . $pose["point_x"] . "," . $pose["point_y"] . "," . $pose["point_z"] . "," . $pose["quat_x"] . "," . $pose["quat_y"] . "," . $pose["quat_z"] . "," . $pose["quat_w"] . ",NOW()," . $this->status_to_numeric_value("pending") . ")") or die("queue insert failed");
            $a["status"] = "pushed";
            $a["id"] = (int) $this->conn->insert_id;
        }
        else {
            $a["status"] = "alreadypending";
            $a["id"] = 	$this->exists($pose,true);
        }

        $this->json_headers();
        echo json_encode($a);
        exit;
    }
    //end push

    //print_queue
    //for troubleshooting: dumps the last 100 rows of queue to the screen
    public function print_queue() {
        $result = $this->conn->query("select * from QUEUE order by id desc limit 100") or die("print_queue failed");
        while($row =  $result->fetch_assoc()) {
            echo "<div style='border:1px; margin:20px;'>";
            var_dump($row);
            echo "</div>";
        }
    }
    //end print_queue

    //status
    public function status() {
        if(!isset($_GET['id']) || !is_numeric($_GET['id'])) die("missing id");
        $id = $_GET['id'];

        //check current status
        $result = $this->conn->query("select status from QUEUE where id = " . $id) or die("status failed");
        if(!$row =  $result->fetch_assoc()) {
            $a = array();
            $a["doesntexist"] = 1;
            $this->json_headers();
            echo json_encode($a);
            exit;
        }

        $a = array();
        $a["status"] = $this->status_number_to_english($row["status"]);

        //count how many pending
        $result = $this->conn->query("select COUNT(*) as how_many_before from QUEUE where status = " . $this->status_to_numeric_value("pending") . " AND ID < " . $id) or die("status pending count failed");
        $row = $result->fetch_assoc() or die("status pending count 2 failed");
        $a["how-many-are-pending-before-id"] = (int) $row["how_many_before"];

        $this->json_headers();
        echo json_encode($a);
        exit;
    }
    //end status

    //update
    public function update() {
        if(!isset($_GET['id']) || !is_numeric($_GET['id'])) die("missing id");
        $id = $_GET['id'];
        if(!isset($_GET['status'])) die("missing status");
        $status_id = $this->status_to_numeric_value($_GET['status']);
        $result = $this->conn->query("update QUEUE set status = $status_id where status = " . $this->status_to_numeric_value("pending") . " AND ID = $id") or die("update failed");

        $a = array();
        $a["updated"] = 1;
        $this->json_headers();
        echo json_encode($a);
        exit;
    }
    //end update

    //json_headers
    private function json_headers() {
       header('Content-Type: application/json');
       header('Cache-Control: no-cache, must-revalidate');
       header("Pragma: no-cache");
       header("Expires: 0");
       header('Access-Control-Allow-Origin: *');
    }
    //end json_headers

    //pop
    //this isn't a true pop: poses remain in the queue until an update is sent via turtlebot (e.g. when it arrives at the pose)
    public function pop() {
        $a = array();
        $a["status"] = "empty";

        $result = $this->conn->query("select * from QUEUE where status = " . $this->status_to_numeric_value("pending") . " order by ID limit 1") or die("pop failed");
        if($row =  $result->fetch_assoc()) {
            $a["status"] = "pending";
            $a["id"] = $row["ID"];
            $a["point"] = array();
            $a["point"]["x"] = $row["point_x"];
            $a["point"]["y"] = $row["point_y"];
            $a["point"]["z"] = $row["point_z"];
            $a["quat"] = array();
            $a["quat"]["x"] = $row["quat_x"];
            $a["quat"]["y"] = $row["quat_y"];
            $a["quat"]["z"] = $row["quat_z"];
            $a["quat"]["w"] = $row["quat_w"];
        }
        $this->json_headers();
        echo json_encode($a);
        exit;
    }
    //end pop

    //exists
    //check if the pose already is pending (so we don't add duplicates)
    private function exists($pose,$return_id = false) {
        $query = "select id from QUEUE where status = " . $this->status_to_numeric_value("pending") . " AND point_x  = " . $pose["point_x"] . " AND point_y = " . $pose["point_y"] . " AND point_z = " . $pose["point_z"] . " AND quat_x = " . $pose["quat_x"] . " AND quat_y = " . $pose["quat_y"] . " AND quat_z = " . $pose["quat_z"] . " AND quat_w = " . $pose["quat_w"];
        $result = $this->conn->query($query) or die("exists query failed");
        if(!$row =  $result->fetch_assoc()) return 0;

        if($return_id) {
            return $row['id'];
        }
        return true;

        return false;
    }
    //end exists

    //status_array
    private function status_array() {
        $a = array();
        $a["pending"] = 0;
        $a["complete"] = 1;
        $a["failed"] = 2;

        return $a;
    }
    //end status_array

    //status_number_to_english
    private  function status_number_to_english($status_num) {
        foreach($this->status_array() as $name => $num) {
            if($num == $status_num) return $name;
        }
        die("unknown status name [" . htmlspecialchars($status_num) . "]");
    }
    //end status_number_to_english

    //status_to_numeric_value
    //the database keeps track of the status as a numeric but to make the code more legible this allows us to use english words
    private function status_to_numeric_value($status) {
        if(array_key_exists($status,$this->status_array())) {
            $a = $this->status_array();
            return $a[$status];
        }
        die("unknown status status_to_numeric_value [" . htmlspecialchars($status) . "]");
    }
    //end status_to_numeric_value
}
//end class
