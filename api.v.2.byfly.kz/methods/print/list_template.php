<?php
$templates = array();
$listTemplateDB = $dbPrint->query("SELECT * FROM print_template ORDER BY id ASC");
while ($listTemplate = $listTemplateDB->fetch_assoc()) {
    $listTemplate['img'] = array();
    $listTemplate['templates'] = array();

    $imgDB = $dbPrint->query("SELECT * FROM print_images WHERE id_template='" . $listTemplate['id'] . "'");
    while ($img = $imgDB->fetch_assoc()) {
        array_push($listTemplate['img'], $img['link']);
    }

    $templatesDB = $dbPrint->query("SELECT * FROM templates WHERE print_id='" . $listTemplate['id'] . "'");
    while ($templatesSSSS = $templatesDB->fetch_assoc()) {
        $templatesSSSS['link_template'] = $templatesSSSS['link_template'] . '&user_id=' . $_POST['user_id'];
        array_push($listTemplate['templates'], $templatesSSSS);
    }

    array_push($templates, $listTemplate);
}

echo json_encode(
    array(
        "type" => true,
        "data" => $templates,
    ),
    JSON_UNESCAPED_UNICODE,
);
?>