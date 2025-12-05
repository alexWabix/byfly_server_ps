<?php
$agents = array();
$listAgentsDB = $db->query("SELECT * FROM users WHERE astestation_bal > 0");
while ($listAgents = $listAgentsDB->fetch_assoc()) {
    $agents[] = array(
        "name" => $listAgents['name'],
        "famale" => $listAgents['famale'],
        "surname" => $listAgents['surname'],
        "id" => $listAgents['id'],
        "phone" => "+" . $listAgents['phone'],
    );
}

echo json_encode(
    array(
        "type" => true,
        "data" => $agents,
    ),
    JSON_UNESCAPED_UNICODE
);
?>