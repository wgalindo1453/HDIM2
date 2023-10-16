<?php
// Include your database connection file
include '../DB/connection.php'; // Replace with your actual file name
$conn = createConnection();
// Get the search query from the AJAX request

// Check if connection is successful
if ($conn->connect_error) {
    print_r($conn->connect_error);
    echo json_encode([]); // Return empty JSON array in case of connection error
    exit;
}

// Check if query parameter is set
if (!isset($_GET['query'])) {
    echo json_encode([]); // Return empty JSON array if query parameter is not set
    exit;
}

$query = $_GET['query'];

// Prepare the SQL statement
$sql = "SELECT Name FROM Recipe WHERE LOWER(Name) LIKE ? LIMIT 5"; // Limiting to 5 results for type-ahead
$stmt = $conn->prepare($sql);

// Check if statement preparation is successful
if (!$stmt) {
    echo json_encode([]); // Return empty JSON array in case of statement preparation error
    exit;
}

// Bind the parameters
$search_term = "%" . strtolower($query) . "%";
$stmt->bind_param("s", $search_term);

// Execute the statement
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Fetch the data
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row['Name'];
}

// Close the statement
$stmt->close();

// Return the JSON response
echo json_encode($data);
?>
