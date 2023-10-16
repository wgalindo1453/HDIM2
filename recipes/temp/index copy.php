<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "howdoimake";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$output = "";
$similarRecipes = "";
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["query"])) {
    $query = $_GET["query"];

    $sql = "SELECT * FROM Recipe WHERE LOWER(Name) LIKE ?";
    $stmt = $conn->prepare($sql);

    $search_term = "%" . strtolower($query) . "%";
    $stmt->bind_param("s", $search_term);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output .= "<div class='row'>";
            $output .= "<div class='col-md-6'>";
            $output .= "<h2 class='h3'>" . $row["Name"] . "</h2>";
            $output .=
                "<h3 class='h4'>Ingredients:</h3><p>" .
                nl2br($row["Ingredients"]) .
                "</p>";
            $output .=
                "<h3 class='h4'>Instructions:</h3><p>" .
                nl2br($row["Instructions"]) .
                "</p>";
            $output .=
                "<h3 class='h4'>Story:</h3><p>" . nl2br($row["Story"]) . "</p>";
            $output .= "</div>";
            $output .= "<div class='col-md-6'>";
            $output .=
                "<img src='" .
                $row["ImageURL"] .
                "' alt='" .
                $row["Name"] .
                "' class='img-fluid' style='max-height: 400px;'>";
            $output .= "</div>";
            $output .= "</div>";
        }

        // Fetch similar recipes from database
        $sql_similar =
            "SELECT * FROM Recipe WHERE LOWER(Name) LIKE ? ORDER BY CASE WHEN LOWER(Name) LIKE ? THEN 1 WHEN LOWER(Name) LIKE ? THEN 2 ELSE 3 END LIMIT 3";
        $stmt_similar = $conn->prepare($sql_similar);

        $exact_search_term = strtolower($query) . "%";
        $stmt_similar->bind_param(
            "sss",
            $search_term,
            $exact_search_term,
            $search_term,
        );
        $stmt_similar->execute();

        $result_similar = $stmt_similar->get_result();

        while ($row_similar = $result_similar->fetch_assoc()) {
            $similarRecipes .=
                "<div class='col-md-4 ml-auto pricing-box align-self-center'>
                                 <div class='card mb-4'>
                                   <div class='card-body p-4 text-center'>
                                     <h5 class='font-weight-normal'>" .
                $row_similar["Name"] .
                "</h5>
                                     <p class='mt-4'>" .
                substr($row_similar["Instructions"], 0, 80) .
                "... </p> 
                                   </div>
                                   <a class='btn btn-info-gradiant p-3 btn-block border-0 text-white' href='#'>CHOOSE RECIPE</a>
                                 </div>
                             </div>";
        }
        $stmt_similar->close();
    } else {
        $api_key = "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";

        $data = [
            "model" => "gpt-3.5-turbo",
            "messages" => [
                ["role" => "user", "content" => $query],
                [
                    "role" => "assistant",
                    "content" =>
                        'Please provide the recipe in this format :\nName\nIngredients\nInstructions and provide a short 1 paragraph funny fictional story related to the recipe with the format story: .',
                ],
            ],
            "temperature" => 1,
            "max_tokens" => 500,
            "top_p" => 1,
            "frequency_penalty" => 0,
            "presence_penalty" => 0,
        ];

        $ch = curl_init();

        curl_setopt(
            $ch,
            CURLOPT_URL,
            "https://api.openai.com/v1/chat/completions",
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $api_key,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            die("CURL request failed: " . curl_error($ch));
        }

        curl_close($ch);

        $response_data = json_decode($response, true);
        // print_r($response_data);
        $generated_recipe = $response_data["choices"][0]["message"]["content"];
        $recipe_parts = preg_split(
            "/\n(?=Ingredients:|Instructions:|Story:)/",
            $generated_recipe,
        );
        if (count($recipe_parts) == 4) {
            [$name, $ingredients, $instructions, $story] = $recipe_parts;
        } else {
            [$name, $ingredients, $instructions] = $recipe_parts;
            $story = ""; // Assign a default value or handle this case appropriately.
        }

        $name = trim($name);
        $name = str_replace("Name:", "", $name);

        $instructions = $recipe_parts[1];
        // Remove the 'Ingredients:' and 'Instructions:' titles from the ingredients and instructions
        $ingredients = str_replace("Ingredients:", "", $ingredients);
        $instructions = str_replace("Instructions:", "", $instructions);
        $story = str_replace("Story:", "", $story);

        $sql =
            "INSERT INTO Recipe (Name, Ingredients, Instructions, Story, ImageURL) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Prepare statement failed: " . $conn->error);
        }

        $image_url = "";

        $stmt->bind_param(
            "sssss",
            $name,
            $ingredients,
            $instructions,
            $story,
            $image_url,
        );
        $stmt->execute();
        $stmt->close();

        // Image handling
        $data = [
            "prompt" => $name . "delicious dish " . $story . "\n\n",
            "n" => 1,
            "size" => "512x512",
        ];
        // print_r($data);
        $ch = curl_init();

        curl_setopt(
            $ch,
            CURLOPT_URL,
            "https://api.openai.com/v1/images/generations",
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $api_key,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            die("CURL request failed: " . curl_error($ch));
        }

        curl_close($ch);

        $response_data = json_decode($response, true);
        if (isset($response_data["data"][0]["url"])) {
            $image_url = $response_data["data"][0]["url"];
        } else {
            die('API did not return a valid image URL. Response: ' . print_r($response_data, true));
        }
        

        if (!empty($image_url)) {
            $image_data = file_get_contents($image_url);
        }

        // Check if the images folder exists, if not create it
        if (!file_exists("images")) {
            mkdir("images", 0777, true);
        }

        // Save the image to the images folder
        $image_file_name ="images/" . preg_replace("/[^a-zA-Z0-9]/", "", $name) . ".png";
        // file_put_contents($image_file_name, $image_data);

        $image_data = @file_get_contents($image_url);
if ($image_data === false) {
    die('Failed to fetch image data: ' . print_r(error_get_last(), true));
}

        $write_result = file_put_contents($image_file_name, $image_data);
            if ($write_result === false) {
        die('Failed to save image: ' . print_r(error_get_last(), true));
        }


        // Update database with image URL
        $sql = "UPDATE Recipe SET ImageURL = ? WHERE Name = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Prepare statement failed: " . $conn->error);
        }

        $stmt->bind_param("ss", $image_file_name, $name);
        $stmt->execute();
        $stmt->close();

        $output .= "<div style='display: flex;'>";
        $output .= "<div style='flex: 1; padding-right: 20px;'>";
        $output .= "<h2>" . $name . "</h2>";
        $output .= "<h3>Ingredients:</h3><p>" . nl2br($ingredients) . "</p>";
        $output .= "<h3>Instructions:</h3><p>" . nl2br($instructions) . "</p>";
        $output .= "<h3>Story:</h3><p>" . nl2br($story) . "</p>";
        $output .= "</div>";
        $output .= "<div>";
        $output .=
            "<img src='" .
            $image_file_name .
            "' alt='" .
            $name .
            "' style='height: 400px;'>";
        $output .= "</div>";
        $output .= "</div>";
    }
}

$conn->close();

$html_content = file_get_contents("display.html");
$final_content = str_replace("<!--RECIPE_RESULTS-->", $output, $html_content);
$final_content = str_replace(
    "<!--SIMILAR_RECIPES-->",
    $similarRecipes,
    $final_content,
);
echo $final_content;
?>
