<?php
$totalScore = 100;
$numCategories = 5;
$questionsPerCategory = 3;
$pointsPerQuestion = $totalScore / ($numCategories * $questionsPerCategory);
$listQuery = array();
$categories = $db->query("SELECT DISTINCT category FROM to_agents_start_test_quest");

if ($categories->num_rows > 0) {
    while ($row = $categories->fetch_assoc()) {
        $category = $row['category'];
        $questions = $db->query("
                SELECT * 
                FROM to_agents_start_test_quest 
                WHERE category = '$category' 
                ORDER BY RAND() 
                LIMIT $questionsPerCategory
            ");

        while ($question = $questions->fetch_assoc()) {
            $question['answer1_bal'] = $question['answer1_bal'] + 1;
            $question['answer2_bal'] = $question['answer1_bal'] + 1;
            $question['answer3_bal'] = $question['answer1_bal'] + 1;

            $listQuery[] = $question;
        }
    }
}

echo json_encode(
    array(
        "type" => true,
        "data" => $listQuery,
    ),
    JSON_UNESCAPED_UNICODE
);
?>