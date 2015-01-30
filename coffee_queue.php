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

        if(!$this->exists($pose)) { //don't add duplicate pending requests
            $this->conn->query("insert into QUEUE values(NULL," . $pose["point_x"] . "," . $pose["point_y"] . "," . $pose["point_z"] . "," . $pose["quat_x"] . "," . $pose["quat_y"] . "," . $pose["quat_z"] . "," . $pose["quat_w"] . ",NOW()," . $this->status_to_numeric_value("pending") . ")") or die("queue insert failed");
        }
        echo "<h1>Coffee is on it's way!</h1>";
    }
    //end push

    //update
    public function update() {
        if(!isset($_GET['id']) || !is_numeric($_GET['id'])) die("missing id");
        $id = $_GET['id'];
        if(!isset($_GET['status'])) die("missing status");
        $status_id = $this->status_to_numeric_value($_GET['status']);
        $result = $this->conn->query("update QUEUE set status = $status_id where status = " . $this->status_to_numeric_value("pending") . " AND ID = $id") or die("update failed");

        $a = array();
        $a["updated"] = 1;
        header('Content-Type: application/json');
        echo json_encode($a);
        exit;
    }
    //end update

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
        header('Content-Type: application/json');
        echo json_encode($a);
        exit;
    }
    //end pop

    //exists
    //check if the pose already is pending (so we don't add duplicates)
    private function exists($pose) {
        $query = "select COUNT(*) from QUEUE where status = " . $this->status_to_numeric_value("pending") . " AND point_x  = " . $pose["point_x"] . " AND point_y = " . $pose["point_y"] . " AND point_z = " . $pose["point_z"] . " AND quat_x = " . $pose["quat_x"] . " AND quat_y = " . $pose["quat_y"] . " AND quat_z = " . $pose["quat_z"] . " AND quat_w = " . $pose["quat_w"];
        $result = $this->conn->query($query) or die("exists query failed");
        $row =  $result->fetch_row() or die("exists query failed 2");
        if($row[0] > 0) return true;
        return false;
    }
    //end exists

    //status_to_numeric_value
    //the database keeps track of the status as a numeric but to make the code more legible this allows us to use english words
    private function status_to_numeric_value($status) {
        if($status == "pending") return 0;
        if($status == "complete") return 1;
        if($status == "failed") return 2;
        die("unknown status status_to_numeric_value");
    }
    //end status_to_numeric_value
}
//end class