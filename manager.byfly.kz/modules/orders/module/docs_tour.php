<?php
function docsTour($id, $db)
{
    $listDocumentsDB = $db->query("SELECT * FROM order_docs WHERE order_id='" . $id . "'");
    if ($listDocumentsDB->num_rows == 0) {
        $html = '<div class="alert alert-info">Нет документов для данного тура.</div>';
    } else {
        $html = '<div class="container mt-4">';
        $html .= '<h5>Документы:</h5>';
        $html .= '<div class="list-group" id="listGroup-' . $id . '">';

        while ($document = $listDocumentsDB->fetch_assoc()) {
            $isClientVisible = $document['show_to_client'] ? '<i class="icon ion-checkmark-circled text-success"></i>' : '<i class="icon ion-close-circled text-danger"></i>';

            $html .= '<div class="list-group-item d-flex justify-content-between align-items-center" id="doc-' . $document['id'] . '">';
            $html .= '<div>
                        <h6 class="mb-1">' . htmlspecialchars($document['title']) . ' ' . $isClientVisible . '</h6>
                        <small>' . htmlspecialchars($document['description']) . '</small>
                      </div>';
            $html .= '<div>
                        <a href="' . htmlspecialchars($document['docs_link']) . '" class="btn btn-primary btn-sm" download>Скачать</a>
                        <button class="btn btn-danger btn-sm ms-2" onclick="deleteDocument(' . $document['id'] . ', ' . $id . ')">Удалить</button>
                      </div>';
            $html .= '</div>';
        }

        $html .= '</div>';
    }

    $html .= <<<HTML
<div class="accordion mt-4" id="uploadDocumentAccordion-{$id}">
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingUpload-{$id}">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUpload-{$id}" aria-expanded="false" aria-controls="collapseUpload-{$id}">
                Загрузить новый документ
            </button>
        </h2>
        <div id="collapseUpload-{$id}" class="accordion-collapse collapse" aria-labelledby="headingUpload-{$id}" data-bs-parent="#uploadDocumentAccordion-{$id}">
            <div class="accordion-body">
                <form id="uploadDocumentForm-{$id}" enctype="multipart/form-data">
                    <input type="hidden" name="order_id" value="{$id}">
                    <div class="mb-3">
                        <label for="docTitle-{$id}" class="form-label">Название документа</label>
                        <input type="text" class="form-control" id="docTitle-{$id}" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="docDescription-{$id}" class="form-label">Описание документа</label>
                        <textarea class="form-control" id="docDescription-{$id}" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="docFile-{$id}" class="form-label">Выберите файл</label>
                        <input class="form-control" type="file" id="docFile-{$id}" name="file" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="showToClient-{$id}" name="show_to_client">
                        <label class="form-check-label" for="showToClient-{$id}">
                            Показывать клиенту
                        </label>
                    </div>
                    <button type="button" class="btn btn-success" onclick="uploadDocument({$id})">Загрузить</button>
                    <div id="uploadLoader-{$id}" class="spinner-border text-success ms-3 d-none" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function uploadDocument(orderId) {
    const loader = document.getElementById(`uploadLoader-`+orderId);
    const form = document.getElementById(`uploadDocumentForm-`+orderId);
    const listGroup = document.getElementById(`listGroup-`+orderId);
    const formData = new FormData(form);

    loader.classList.remove('d-none');

    fetch('https://manager.byfly.kz/upload_document.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const isClientVisible = data.show_to_client ? '<i class="icon ion-checkmark-circled text-success"></i>' : '<i class="icon ion-close-circled text-danger"></i>';

            const newDoc = '<div class="list-group-item d-flex justify-content-between align-items-center" id="doc-' + data.id + '">'+
                    '<div>'+ 
                        '<h6 class="mb-1">'+data.title+' '+isClientVisible+'</h6>'+ 
                        '<small>'+data.description+'</small>'+ 
                    '</div>'+ 
                    '<div>'+ 
                        '<a href="'+data.docs_link+'" class="btn btn-primary btn-sm" download>Скачать</a>'+ 
                        '<button class="btn btn-danger btn-sm ms-2" onclick="deleteDocument('+data.id+', '+orderId+')">Удалить</button>'+ 
                    '</div>'+ 
                '</div>';
            listGroup.insertAdjacentHTML('beforeend', newDoc);
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        alert('Произошла ошибка при загрузке. ' + JSON.stringify(error));
    })
    .finally(() => {
        loader.classList.add('d-none');
    });
}

function deleteDocument(docId, orderId) {
    if (!confirm('Вы уверены, что хотите удалить этот документ?')) {
        return;
    }

    const loader = document.getElementById(`uploadLoader-` + orderId);
    const docElement = document.getElementById(`doc-` + docId);

    const formData = new FormData();
    formData.append('id', docId);
    formData.append('operation', 'delete_docs');

    loader.classList.remove('d-none');

    fetch('https://manager.byfly.kz/index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            docElement.remove();
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        alert('Произошла ошибка при удалении. ' + JSON.stringify(error));
    })
    .finally(() => {
        loader.classList.add('d-none');
    });
}
</script>
HTML;

    $html .= '</div>';

    return $html;
}
?>