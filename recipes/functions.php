<?php
include '../DB/connection.php';
$conn = createConnection();


function getRecipeFromDB($conn, $query) {
    $output = "<div class='container text-center'>"; // Centered container
    $sql = "SELECT * FROM Recipe WHERE LOWER(Name) LIKE ? LIMIT 1";
    $stmt = $conn->prepare($sql);

    $search_term = "%" . strtolower($query) . "%";
    $stmt->bind_param("s", $search_term);

    $stmt->execute();

    $result = $stmt->get_result();
    $similarRecipes = "";
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output .= "<h2 class='name-title'>" . $row["Name"] . "</h2>";
            $output .= "<div class='row justify-content-center mt-3'>"; // Centered row
            $output .= "<div class='col-md-10 mb-4'>"; // Centered column
            $output .= "<div class='card shadow mb-3'>"; // Ingredients card
            $output .= "<div class='card-header'><h3>Ingredients:</h3></div>";
            $output .= "<div class='card-body'><p>" . nl2br($row["Ingredients"]) . "</p></div>";
            $output .= "</div>"; // End of card
            
            $output .= "<div class='card shadow mb-3'>"; // Instructions card
            $output .= "<div class='card-header'><h3>Instructions:</h3></div>";
            $output .= "<div class='card-body'><p>" . nl2br($row["Instructions"]) . "</p></div>";
            $output .= "</div>"; // End of card
            
            $output .= "<div class='card shadow img-card mb-3'>"; // Image card
            $output .= "<div class='img-container'>";
            $output .= "<img class='card-img' src='" . $row["ImageURL"] . "' alt='" . $row["Name"] . "'>";
            $output .= "</div>"; // End of img-container
            $output .= "</div>"; // End of card
            
            $output .= "<div class='card shadow mb-3'>"; // Story card
            $output .= "<div class='card-header'><h3>Story:</h3></div>";
            $output .= "<div class='card-body'><p>" . nl2br($row["Story"]) . "</p></div>";
            $output .= "</div>"; // End of card
            
            $output .= "</div>"; // End of col-md-6
            $output .= "</div>"; // End of row
            if (!empty($row["SimilarRecipes"])) {
                $similarRecipes = explode(", ", $row["SimilarRecipes"]); // Assuming similar recipes are stored as comma-separated strings
            }
        }
    } else {
        $stmt->close();
        return null; // Return null if no rows are found
    }

    $stmt->close();
    $output .= "</div>"; // End of container
    return ['output' => $output, 'similarRecipes' => $similarRecipes];

}


/**IMAGE HANDLING***/
// function fetchImageFromAPI($name, $story, $api_key) {
//     // Filter handling
//     $story = filter_content($story); // Filter out any words/phrases that are not allowed

//     // make sure $story is less than 500 characters
//     $story = substr($story, 0, 500);
    

//     $data = [
//         "prompt" => "a painting of ".$name . " is a delightful and savory dish. " . $story . " Let's visualize a warm and inviting painting of this dish.\n\n",
//         "n" => 1,
//         "size" => "512x512",
//     ];

//     $ch = curl_init();

//     curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/images/generations");
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//     curl_setopt($ch, CURLOPT_POST, 1);
//     curl_setopt($ch, CURLOPT_VERBOSE, true);
//     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
//     curl_setopt($ch, CURLOPT_HTTPHEADER, [
//         "Content-Type: application/json",
//         "Authorization: Bearer " . $api_key,
//     ]);

//     $response = curl_exec($ch);

//     if ($response === false) {
//         die("CURL request failed: " . curl_error($ch));
//     }

//     curl_close($ch);
//     return $response;
// }// Closing brace for fetchImageFromAPI function

function fetchImageFromAPI($name, $story, $api_key) {
    // Filter handling
    $story = filter_content($story); // Filter out any words/phrases that are not allowed

    // make sure $story is less than 500 characters
    $story = substr($story, 0, 500);

    // Concise details for better image generation
    $promptDetails = "The image should be a close-up of the dish, with a shallow depth of field, and a blurred background. ";
    $prompt = "a photo of a delicious : " . $name . ", in the setting of the story: " . $story . ". " . $promptDetails . " Showcase any action described in the story to make the scene more engaging.";

    // Check if prompt exceeds 1000 characters
    if (strlen($prompt) > 1000) {
        die("Prompt exceeds 1000 characters.");
    }

    $data = [
        "prompt" => $prompt,
        "n" => 1,
        "size" => "512x512",
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/images/generations");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
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
    return $response;
}





function filter_content($text) {
    // Define a list of words/phrases to filter out
    $disallowed = [
        'riot',
        'warrior',
        'battle',
        'fighting',
        'fight',
        'war',
        'soldier',
        'soldiers',
        'army',
        'armies',
        'weapon',
        'weapons',
        'gun',
        'guns',
        'bomb',
        'bombs',
        'explosion',
        'explosions',
        'attack',
        'attacks',
        'attacking',
        'attacked',
        'assault',
        'assaults',
        'assaulting',
        'President',
        'President of the United States',
        'President of the United States of America',
        'President of the USA',
        'President of the US',
        'President of the U.S.',
        'politician',
        'politicians',
        'politics',
        'political',
        'government',
        // add more words/phrases as needed
        
    ];
    
    // Define a pattern to match any real names
    // This is a very basic example, and you might need a more robust solution depending on your needs
    $name_pattern = '/\b[A-Z][a-z]*\s[A-Z][a-z]*\b/';

    // Replace disallowed words/phrases with '[filtered]'
    foreach ($disallowed as $word) {
        $text = preg_replace('/\b'. preg_quote($word, '/') .'\b/i', '[filtered]', $text);
    }

    // Replace any real names with '[name]'
    $text = preg_replace($name_pattern, '[name]', $text);
    
    
    return $text;
}



function processImageResponse($imageResponse) {
    if ($imageResponse === false) {
        // Ideally, log this error to a file and/or return a user-friendly error message.
        return ['error' => "CURL request failed"];
    }

    $response_data = json_decode($imageResponse, true);
    if (!isset($response_data["data"][0]["url"])) {
        // Ideally, log this error to a file and/or return a user-friendly error message.
        return ['error' => 'API did not return a valid image URL. Response: ' . print_r($response_data, true)];
    }

    return ['image_url' => $response_data["data"][0]["url"]];
}


function getImageData($image_url) {
    if (empty($image_url)) return ['error' => 'Image URL is empty'];

    $image_data = @file_get_contents($image_url);
    if ($image_data === false) {
        // Ideally, log this error to a file and/or return a user-friendly error message.
        return ['error' => 'Failed to fetch image data: ' . print_r(error_get_last(), true)];
    }

    return ['image_data' => $image_data];
}

function saveImageData($image_data, $image_file_name) {
    // Check if the images folder exists, if not create it.
    if (!file_exists("images")) {
        mkdir("images", 0777, true);
    }

    $write_result = file_put_contents($image_file_name, $image_data);
    if ($write_result === false) {
        // Ideally, log this error to a file and/or return a user-friendly error message.
        return ['error' => 'Failed to save image: ' . print_r(error_get_last(), true)];
    }

    return ['success' => true];
}


function splitRecipe($generated_recipe) {
    $recipe_parts = preg_split("/\n(?=Ingredients:|Instructions:|Story:|Name:|Ingredients|Instructions|Story|Name)/", $generated_recipe);
    $parts = [];
    foreach ($recipe_parts as $part) {
        if (strpos($part, "Name:") === 0 || strpos($part, "Name") === 0) {
            $parts['name'] = trim(str_replace(["Name:", "Name"], "", $part));
        } elseif (strpos($part, "Ingredients:") === 0 || strpos($part, "Ingredients") === 0) {
            $parts['ingredients'] = trim(str_replace(["Ingredients:", "Ingredients"], "", $part));
        } elseif (strpos($part, "Instructions:") === 0 || strpos($part, "Instructions") === 0) {
            $parts['instructions'] = trim(str_replace(["Instructions:", "Instructions"], "", $part));
        } elseif (strpos($part, "Story:") === 0 || strpos($part, "Story") === 0) {
            $story_part = trim(str_replace(["Story:", "Story"], "", $part));
            
            // Check if "Similar Recipes:" is present in the story part
            if (strpos($story_part, "Similar Recipes:") !== false) {
                // Split the story part to extract the similar recipes
                list($story, $similar_recipes) = explode("Similar Recipes:", $story_part, 2);
                
                // Trim and assign the story and similar recipes to the parts array
                $parts['story'] = trim($story);
                $parts['similar_recipes'] = array_map('trim', explode(',', $similar_recipes)); // Splitting similar_recipes into an array
            } else {
                // If "Similar Recipes:" is not present, assign the whole story part to the story key
                $parts['story'] = $story_part;
            }
        }
    }
    return $parts;
}



function processGeneratedRecipe($response_data) {
    $generated_recipe = $response_data["choices"][0]["message"]["content"];
    $recipe_parts = splitRecipe($generated_recipe);
//correct format is name, ingredients, instructions, story and similar recipes
    if (!checkRecipeFormat($recipe_parts)) { // Check if the recipe is in the correct format
        return ['error' => 'Invalid Recipe Format.'];
    }
    
    return $recipe_parts;
}


function checkRecipeFormat($recipe_parts) {

    //echo "Keys present in recipe_parts: " . implode(', ', array_keys($recipe_parts)) . "<br>";
    // This function checks whether the received recipe is in the correct format or not.
    return isset($recipe_parts['name'], $recipe_parts['ingredients'], $recipe_parts['instructions'], $recipe_parts['story']); // Adjust as needed
}

function processName($name) {
    // This function processes the 'name' part of the recipe.
    return trim($name);
}

function processIngredients($ingredients) {
    // This function processes the 'ingredients' part of the recipe.
    return trim($ingredients);
}

function processInstructions($instructions) {
    // This function processes the 'instructions' part of the recipe.
    return trim($instructions);
}



function processStory($story, $query, $api_key) {
    // This function processes the 'story' part of the recipe, making an API call if necessary.
    $story = trim($story);
    if (empty($story)) {
        $story_data = fetchStoryFromAPI($query, $api_key); // This function should exist or be created to fetch story from API
        if (isset($story_data['error'])) {
            return ['error' => 'Failed to fetch story: ' . $story_data['error']];
        }
        $story = $story_data['story'];
    }
    return $story;
}




function fetchStoryFromAPI($query, $api_key) {
    $data = [
        "model" => "gpt-4",
        "messages" => [
            [
                "role" => "user",
                "content" => "Please write a short 1 paragraph 700 characters fictional friendly story related to the recipe or dish: $query."
            ],
        ],
        "temperature" => 0.7,
        "max_tokens" => 500,
        "top_p" => 1,
        "frequency_penalty" => 0,
        "presence_penalty" => 0,
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions"); // Adjust to the correct endpoint if this has changed
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $api_key,
    ]);

    $response = curl_exec($ch);
    
    if ($response === false) {
        return ['error' => "CURL request failed: " . curl_error($ch)];
    }

    curl_close($ch);

    $response_data = json_decode($response, true);

    // Assuming the story is present in the response, modify as per actual response structure.
    if (isset($response_data['choices'][0]['message']['content'])) {
        return ['story' => $response_data['choices'][0]['message']['content']];
    } else {
        return ['error' => 'Story not found in API response.'];
    }
}

function prepareAndSaveRecipe($conn, $name, $ingredients, $instructions, $story, $similarRecipes) {
    // This function makes necessary preparations and saves the recipe to the database.
    $sql = "INSERT INTO Recipe (Name, Ingredients, Instructions, Story, ImageURL, SimilarRecipes) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['error' => 'Prepare statement failed: ' . $conn->error];
    }
    $image_url = "";
    $similarRecipesStr = implode(", ", $similarRecipes); // Convert the array to a string
    $stmt->bind_param("ssssss", $name, $ingredients, $instructions, $story, $image_url, $similarRecipesStr); // Added one more 's' for the sixth parameter
    $stmt->execute();
    $stmt->close();
    return true;
}



function updateRecipeImageURL($conn, $image_file_name, $name) {
    // This function updates the image URL of the recipe in the database.
    $sql = "UPDATE Recipe SET ImageURL = ? WHERE Name = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['error' => 'Prepare statement failed: ' . $conn->error];
    }
    $stmt->bind_param("ss", $image_file_name, $name);
    $stmt->execute();
    $stmt->close();
    return true;
}

function renderRecipe($name, $ingredients, $instructions, $story, $image_file_name) {
    // $output = "<style>
    // .name-title {
    //     font-size: 3rem;
    //     font-weight: 700;
    //     color: #000;
    //     text-align: center;
    // }";
    $output = "";
    $output .= "<div class='container'>";
    $output .= "<h2 class='name-title'>" . $name . "</h2>";
    $output .= "<div class='row justify-content-center mt-3'>";
    $output .= "<div class='col-md-10 mb-4'>";
    
    // Ingredients Card
    $output .= "<div class='card shadow mb-3'>";
    $output .= "<div class='card-header'><h3>Ingredients:</h3></div>";
    $output .= "<div class='card-body'><p>" . nl2br($ingredients) . "</p></div>";
    $output .= "</div>";
    
    // Instructions Card
    $output .= "<div class='card shadow mb-3'>";
    $output .= "<div class='card-header'><h3>Instructions:</h3></div>";
    $output .= "<div class='card-body'><p>" . nl2br($instructions) . "</p></div>";
    $output .= "</div>";
    
    // Image Card
    $output .= "<div class='card shadow img-card mb-3'>";
    $output .= "<div class='img-container'>";
    $output .= "<img class='card-img' src='" . $image_file_name . "' alt='" . $name . "'>";
    $output .= "</div>";
    $output .= "</div>";
    
    // Story Card
    $output .= "<div class='card shadow mb-3'>";
    $output .= "<div class='card-header'><h3>Story:</h3></div>";
    $output .= "<div class='card-body'><p>" . nl2br($story) . "</p></div>";
    $output .= "</div>";

    
    
    $output .= "</div>"; // End of col-md-10
    $output .= "</div>"; // End of row
    $output .= "</div>"; // End of container
    
    return $output;
}


// takes in the response from the API and ensures the correct format
function formatResponse($response) {
    $response = $response['choices'][0]['text'];
    $response = explode("\n", $response);
    $response = array_filter($response, function($value) { return $value !== ''; });
    $response = array_values($response);
    return $response;
}


//create a function from response and checks to make sure all the required fields are there
function checkResponse($response) {
    $required_fields = ['name', 'ingredients', 'instructions', 'story'];
    $response_fields = array_map(function($value) {
        return strtolower(explode(':', $value)[0]);
    }, $response);
    $missing_fields = array_diff($required_fields, $response_fields);
    if (count($missing_fields) > 0) {
        return false;
    }
    return true;
}




//  function fetchSimilarRecipes($conn, $query) {
//     $similarRecipes = "";
//     $search_term = strtolower($query) . "%";
//     $sql_similar = "SELECT * FROM Recipe WHERE LOWER(Name) LIKE ? ORDER BY CASE WHEN LOWER(Name) LIKE ? THEN 1 WHEN LOWER(Name) LIKE ? THEN 2 ELSE 3 END LIMIT 3";
    
//     $stmt_similar = $conn->prepare($sql_similar);

//     $exact_search_term = strtolower($query) . "%";
//     $stmt_similar->bind_param("sss", $search_term, $exact_search_term, $search_term);
    
//     $stmt_similar->execute();

//     $result_similar = $stmt_similar->get_result();

//     while ($row_similar = $result_similar->fetch_assoc()) {
//         $similarRecipes .= "<div class='col-md-4 ml-auto pricing-box align-self-center'>
//                                 <div class='card mb-4'>
//                                     <div class='card-body p-4 text-center'>
//                                         <h5 class='font-weight-normal'>". $row_similar["Name"] ."</h5>
//                                         <p class='mt-4'>". substr($row_similar["Instructions"], 0, 80) ."... </p> 
//                                     </div>
//                                     <a class='btn btn-info-gradiant p-3 btn-block border-0 text-white' href='#'>CHOOSE RECIPE</a>
//                                 </div>
//                             </div>";
//     }

//     $stmt_similar->close();
//     return $similarRecipes;
// }
// function renderSimilarRecipes($similarRecipesArray) {
//     $similarRecipes = ""; // Initialize the variable
//     foreach($similarRecipesArray as $recipe) {
//         $similarRecipes .= "<div class='col-md-4 ml-auto pricing-box align-self-center'>
//                                 <div class='card mb-4'>
//                                     <div class='card-body p-4 text-center'>
//                                         <h5 class='font-weight-normal'>". $recipe ."</h5>
//                                     </div>
//                                     <a class='btn btn-info-gradiant p-3 btn-block border-0 text-white' href='#'>CHOOSE RECIPE</a>
//                                 </div>
//                             </div>";
//     }
//     return $similarRecipes;
// }
// function renderSimilarRecipes($similarRecipesArray) {
//     $similarRecipes = ""; // Initialize the variable
//     foreach($similarRecipesArray as $recipe) {
//         $recipeQuery = urlencode($recipe); // URL encode the recipe name
//         $similarRecipes .= "<div class='col-md-4 ml-auto pricing-box align-self-center'>
//                                 <div class='card mb-4'>
//                                     <div class='card-body p-4 text-center'>
//                                         <h5 class='font-weight-normal'>". $recipe ."</h5>
//                                     </div>
//                                     <a class='btn btn-info-gradiant p-3 btn-block border-0 text-white' href='${encodeURIComponent($recipeQuery)}'>CHOOSE RECIPE</a>
//                                 </div>
//                             </div>";
//     }
//     return $similarRecipes;
// }
function renderSimilarRecipes($similarRecipesArray) {
    $similarRecipes = ""; // Initialize the variable
    foreach($similarRecipesArray as $recipe) {
        $recipeQuery = urlencode($recipe); // URL encode the recipe name
        $similarRecipes .= "<div class='col-md-4 ml-auto pricing-box align-self-center'>
                                <div class='card mb-4'>
                                    <div class='card-body p-4 text-center'>
                                        <h5 class='font-weight-normal'>". htmlspecialchars($recipe) ."</h5>
                                    </div>
                                    <a class='btn btn-info-gradiant p-3 btn-block border-0 text-white' href='javascript:void(0);' onclick='chooseRecipe(\"$recipeQuery\");'>CHOOSE RECIPE</a>
                                </div>
                            </div>";
    }
    return $similarRecipes;
}



function fetchRecipeFromAPI($query) {
    $api_key = "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";
    
    $data = [
        "model" => "gpt-4",
        "messages" => [
            [
                "role" => "user",
                "content" => 'Please check if '.$query.' is a recipe or dish or food. 
                If not only respond with NO nothing else
                else respond
                for it in this format :
                Name:
                Ingredients: (numbered listed with amount and unit)
                Instructions: (numbered listed)
                Story: (provide a short 700 characters paragraph fictional story related to the recipe)
                Similar: Recipes (3 similar recipes names separated by comma).'
            ],
        ],
        "temperature" => 0.7,
        "max_tokens" => 500,
        "top_p" => 1,
        "frequency_penalty" => 0,
        "presence_penalty" => 0,
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions"); // Adjust to the correct endpoint if this has changed
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

    $response = json_decode($response, true);
    return $response;
}



?>