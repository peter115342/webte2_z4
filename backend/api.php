<?php
// Add headers for CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

// If the request method is OPTIONS, return 200 OK status
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

// Create connection
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(array("message" => "Connection failed: " . $conn->connect_error));
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Check if IP address is provided
    if (isset($data['ip'])) {
        // Get current time and date
        date_default_timezone_set('CET');
        $current_time = date('H:i:s');
        $current_date = date('Y-m-d');
        
        // Hash the IP address
        $ip_address = hash('sha256', $data['ip']);

        // Prepare and execute the query to check if IP exists within the last a60 minutes or if it's a new day
        $check_sql = "SELECT * FROM traffic WHERE ip_address = '$ip_address' AND date = '$current_date' ORDER BY time DESC LIMIT 1";
        $result = $conn->query($check_sql);
        
        if ($result === FALSE) {
            http_response_code(500);
            echo json_encode(array("message" => "Error executing query: " . $conn->error));
            exit();
        }
        
        // Check if the result has rows
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $last_entry_time = $row['time'];
            $time_difference = strtotime($current_time) - strtotime($last_entry_time);
            
            // Check if the time difference is at least one hour (3600 seconds)
            if ($time_difference >= 3600) {
                // IP address exists but more than an hour has passed since the last entry
                $insert_sql = "INSERT INTO traffic (ip_address, time, date) VALUES ('$ip_address', '$current_time', '$current_date')";
                $insert_result = $conn->query($insert_sql);
                
                if ($insert_result === TRUE) {
                    echo json_encode(array("message" => "IP address added successfully"));
                } else {
                    http_response_code(500);
                    echo json_encode(array("message" => "Failed to add IP address: " . $conn->error));
                }
            } else {
                echo json_encode(array("message" => "IP address already exists and was added within the last 60 minutes"));
            }
        } else {
            // IP address does not exist within the last 60 minutes, so insert it
            $insert_sql = "INSERT INTO traffic (ip_address, time, date) VALUES ('$ip_address', '$current_time', '$current_date')";
            $insert_result = $conn->query($insert_sql);
            
            if ($insert_result === TRUE) {
                echo json_encode(array("message" => "IP address added successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to add IP address: " . $conn->error));
            }
        }
    } else if (isset($data['country']) && isset($data['city'])) {
        // Handle search history entry

        // Escape user input to prevent SQL injection
        $country = $conn->real_escape_string($data['country']);
        $city = $conn->real_escape_string($data['city']);

        // Prepare and execute the query to insert search history entry
        $insert_search_sql = "INSERT INTO search_history (country, city) VALUES ('$country', '$city')";
        $insert_search_result = $conn->query($insert_search_sql);

        if ($insert_search_result === TRUE) {
            echo json_encode(array("message" => "Search history entry added successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to add search history entry: " . $conn->error));
        }
    } else {
        echo json_encode(array("message" => "IP address, country, or city not provided"));
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if the request is for traffic data
    if(isset($_GET['traffic']) && $_GET['traffic'] == 'true') {
        // Prepare and execute the query to select all entries from the traffic table
        $select_traffic_sql = "SELECT * FROM traffic";
        $traffic_result = $conn->query($select_traffic_sql);
        
        if ($traffic_result === FALSE) {
            http_response_code(500);
            echo json_encode(array("message" => "Error executing query: " . $conn->error));
            exit();
        }
        
        // Check if there are any rows returned
        if ($traffic_result->num_rows > 0) {
            // Fetch all rows and store them in an array
            $traffic_data = array();
            while ($row = $traffic_result->fetch_assoc()) {
                $traffic_data[] = $row;
            }
            
            // Output the traffic data as JSON
            echo json_encode($traffic_data);
        } else {
            // No traffic data found
            echo json_encode(array("message" => "No traffic data found"));
        }
    } else {
        // Prepare and execute the query to select all entries from the search_history table
        $select_sql = "SELECT * FROM search_history";
        $result = $conn->query($select_sql);
        
        if ($result === FALSE) {
            http_response_code(500);
            echo json_encode(array("message" => "Error executing query: " . $conn->error));
            exit();
        }
        
        // Check if there are any rows returned
        if ($result->num_rows > 0) {
            // Fetch all rows and store them in an array
            $search_history = array();
            while ($row = $result->fetch_assoc()) {
                $search_history[] = $row;
            }
            
            // Output the search history data as JSON
            echo json_encode($search_history);
        } else {
            // No search history entries found
            echo json_encode(array("message" => "No search history entries found"));
        }
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Method not allowed"));
}

// Close connection
$conn->close();
?>
