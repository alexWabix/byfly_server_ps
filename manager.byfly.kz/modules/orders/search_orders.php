<div class="search-bar d-flex">
    <input type="text" id="searchInput" class="form-control me-2" placeholder="Введите номер заявки...">
    <button class="btn btn-danger" onclick="performSearch()">Найти</button>
</div>

<script>
    function performSearch() {
        const query = document.getElementById('searchInput').value.trim();
        if (query) {
            window.location.href = `/index.php?page=search&query=${encodeURIComponent(query)}`;
        } else {
            alert('Введите текст для поиска!');
        }
    } 
</script>