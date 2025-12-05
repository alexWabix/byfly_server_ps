<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аналогичные туры</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }

        .header {
            background: linear-gradient(to right, #ae011a, #4a000b);
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .operator-section {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .operator-header {
            background-color: #6c757d;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
        }

        .tour-table {
            width: 100%;
            margin-bottom: 0;
        }

        .tour-table th {
            background-color: #f8f9fa;
            padding: 8px 12px;
            font-size: 0.9rem;
        }

        .tour-table td {
            padding: 8px 12px;
            vertical-align: middle;
        }

        .price-tag {
            font-weight: bold;
            color: #dc3545;
            white-space: nowrap;
        }

        .original-tour {
            background-color: #e8f5e9;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header text-center">
            <h5><i class="bi bi-airplane"></i> Аналогичные туры</h5>
        </div>

        <div id="loader" class="text-center py-5">
            <div class="spinner-border text-danger" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
        </div>

        <div id="originalTourContainer" class="mb-4"></div>
        <div id="toursContainer"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const tourId = urlParams.get('id');

            if (tourId) {
                loadSimilarTours(tourId);
            } else {
                document.getElementById('loader').innerHTML =
                    '<div class="alert alert-danger">Не указан ID тура</div>';
            }
        });

        function loadSimilarTours(tourId) {
            fetch(`https://manager.byfly.kz/check_order.php?tourid=${tourId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loader').style.display = 'none';

                    if (data.error) {
                        document.getElementById('toursContainer').innerHTML =
                            `<div class="alert alert-danger">${data.error}</div>`;
                        return;
                    }

                    // Отображаем оригинальный тур
                    if (data.original_tour) {
                        const originalTourHtml = createTourTable([data.original_tour], 'Ваш текущий тур', true);
                        document.getElementById('originalTourContainer').innerHTML = originalTourHtml;
                    }

                    // Отображаем аналогичные туры
                    if (data.similar_tours && data.similar_tours.length > 0) {
                        // Группируем туры по операторам
                        const toursByOperator = groupToursByOperator(data.similar_tours);

                        // Создаем секции для каждого оператора
                        let toursHtml = '';
                        for (const [operator, tours] of Object.entries(toursByOperator)) {
                            toursHtml += createTourTable(tours, operator);
                        }

                        document.getElementById('toursContainer').innerHTML = toursHtml;
                    } else {
                        document.getElementById('toursContainer').innerHTML =
                            '<div class="alert alert-info">Аналогичные предложения не найдены</div>';
                    }
                })
                .catch(error => {
                    document.getElementById('loader').innerHTML =
                        `<div class="alert alert-danger">Ошибка при загрузке данных: ${error.message}</div>`;
                });
        }

        function groupToursByOperator(tours) {
            return tours.reduce((groups, tour) => {
                const operator = tour.operator || 'Неизвестный оператор';
                if (!groups[operator]) {
                    groups[operator] = [];
                }
                groups[operator].push(tour);
                return groups;
            }, {});
        }

        function createTourTable(tours, title, isOriginal = false) {
            return `
                <div class="operator-section ${isOriginal ? 'original-tour' : ''} mb-4">
                    <div class="operator-header">${title}</div>
                    <div class="table-responsive">
                        <table class="tour-table">
                            <thead>
                                <tr>
                                    <th>Отель</th>
                                    <th>Дата</th>
                                    <th>Ночи</th>
                                    <th>Питание</th>
                                    <th>Номер</th>
                                    <th>Цена</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                ${tours.map(tour => `
                                    <tr>
                                        <td>${tour.hotel || '-'}</td>
                                        <td>${tour.fly_date || '-'}</td>
                                        <td>${tour.nights || '-'}</td>
                                        <td>${tour.meal || '-'}</td>
                                        <td>${tour.room || '-'}</td>
                                        <td class="price-tag">${tour.price ? new Intl.NumberFormat('ru-RU').format(tour.price) : '0'} ${tour.currency || 'KZT'}</td>
                                        <td class="text-end">
                                            <a href="https://manager.byfly.kz/getlinkFromOperators.php?id=${tour.tourId}" target="_blank" class="btn btn-danger btn-sm">
                                                <i class="bi bi-bookmark-check"></i> Бронь
                                            </a>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }
    </script>
</body>

</html>