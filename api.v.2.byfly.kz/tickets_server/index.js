const puppeteer = require('puppeteer');
const fs = require('fs');
const cheerio = require('cheerio');
var http = require('http');
var url = require('url');
const mysql = require('mysql2');

var browser = [];
var pages = []

async function clickToDate(page, dateSelect, yearSelect, monthSelect, countCalendar) {
    await page.waitForSelector('#root > section > div > div.widget-segments > div > div.widget-dates > div:nth-child(' + countCalendar + ') > div > div.react-datepicker-popper > div > div.react-datepicker__month-container > div.react-datepicker__header > div.react-datepicker__current-month');
    var month = {
        'Январь': 1, 'Февраль': 2, 'Март': 3, 'Апрель': 4, 'Май': 5, 'Июнь': 6, 'Июль': 7, 'Август': 8, 'Сентябрь': 9, 'Октябрь': 10, 'Ноябрь': 11, 'Декабрь': 12
    };
    const currentMonthYear = await page.$eval('#root > section > div > div.widget-segments > div > div.widget-dates > div:nth-child(' + countCalendar + ') > div > div.react-datepicker-popper > div > div.react-datepicker__month-container > div.react-datepicker__header > div.react-datepicker__current-month', el => el.innerText);

    var explDate = currentMonthYear.split(', ');
    var thisMonth = month[explDate[0]];
    var thisYear = explDate[1];


    var startDate = new Date(yearSelect, monthSelect);
    var currentDate = new Date(thisYear, thisMonth);

    var monthDifference = (startDate.getFullYear() - currentDate.getFullYear()) * 12 + (startDate.getMonth() - currentDate.getMonth());

    const nextMonthButton = await page.$('#root > section > div > div.widget-segments > div > div.widget-dates > div:nth-child(1) > div > div.react-datepicker-popper > div > a');
    for (let index = 0; index < monthDifference; index++) {
        await nextMonthButton.click();
        await sleep(50);
    }
    await sleep(400);

    await page.waitForSelector('[aria-label="day-' + dateSelect + '"]');
    const dayElement = await page.$('[aria-label="day-' + dateSelect + '"]');
    if (dayElement) {
        await dayElement.click();
        return true;
    } else {
        return false;
    }
}

function clickToDatePromise(page, dateSelect, yearSelect, monthSelect, countCalendar) {
    return new Promise(async (resolve, reject) => {
        try {
            const result = await clickToDate(page, dateSelect, yearSelect, monthSelect, countCalendar);
            resolve(result);
        } catch (error) {
            reject(error);
        }
    });
}

const sleep = ms => new Promise(res => setTimeout(res, ms));

async function pageClose(page, connection) {
    try {
        await pages[page].close();
        await browser[page].close();
        pages.splice(page, 1);
        browser.splice(page, 1);
    } catch (error) {
        //console.log('Dont close pages');
    }

    try {
        connection.end((err) => {
            if (err) {
                return console.log('Ошибка закрытия подключения: ' + err.message);
            }
        });
    } catch (error) {
        //console.log('Не удалось закрыть соединение с базой данных!');
    }
}

async function insertSearchId(connection, selectStartDay, selectStartMonth, selectStartYear, endDate, selectEndDay, selectEndMont, selectEndYear, typeAvia, countAdalt, countChildren, countSuperChildren, outCitys, toCitys) {
    return new Promise(async (resolve, reject) => {
        const sql = `INSERT INTO tickets_serarch 
        (id, start_date_day, start_date_month, start_date_year, return_tickets, return_date_day, return_date_month, return_date_year, avia_class, count_adult, count_children, count_infant, city_oute, city_to, user, status, count_tickets) 
        VALUES 
        (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '0', '0', '0')`;

        try {
            connection.query(sql, [
                selectStartDay, selectStartMonth, selectStartYear, endDate === 'true' ? 1 : 0, selectEndDay, selectEndMont, selectEndYear,
                typeAvia, countAdalt, countChildren, countSuperChildren, outCitys, toCitys
            ], (error, result) => {
                if (error) {
                    resolve({
                        type: false,
                        msg: 'Error create rows in data base...'
                    });
                    return;
                }

                resolve({
                    type: true,
                    id: result.insertId
                });
            });


        } catch (error) {
            resolve({
                type: false,
                msg: error.message,
            });
        }
    });
}

async function updateStatusById(connection, id, newStatus) {
    return new Promise(async (resolve, reject) => {
        try {
            const sql = 'UPDATE tickets_serarch SET status = ? WHERE id = ?';
            connection.query(sql, [newStatus, id], (error, result) => {
                if (error) {
                    console.log(error);
                    resolve({
                        type: false,
                        msg: 'Status not updated.'
                    });
                    return;
                }

                resolve({
                    type: true,
                    msg: 'Status updated.',
                });
            });


        } catch (error) {
            resolve({
                type: false,
                msg: 'Status not updated.',
            });
        }
    });

}

async function insertAviaTicket(connection, data) {
    return new Promise(async (resolve, reject) => {
        const sql = `INSERT INTO avia_tickets 
        (id, company_logo, departureDate, departureDayOfWeek, departureCity, arrivalCity, departureTime, arrivalTime, availableSeats, stops, stopDetails, travelTime, price, fareFeatures, clad, bagage, search_id) 
        VALUES 
        (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`;

        try {
            connection.query(sql, [
                data.logo,
                data.departureDate.replace(/[\n\t\r]+/g, ''),
                data.departureDayOfWeek.replace(/[\n\t\r]+/g, ''),
                data.departureCity.replace(/[\n\t\r]+/g, ''),
                data.arrivalCity.replace(/[\n\t\r]+/g, ''),
                data.departureTime.replace(/[\n\t\r]+/g, ''),
                data.arrivalTime.replace(/[\n\t\r]+/g, ''),
                data.availableSeats.replace(/[\n\t\r]+/g, ''),
                data.stops.replace(/[\n\t\r]+/g, ''),
                data.stopDetails,
                data.travelTime.replace(/[\n\t\r]+/g, ''),
                data.price,
                data.fareFeatures.join(','),
                data.clad.replace(/[\n\t\r]+/g, ''),
                data.bagage.replace(/[\n\t\r]+/g, ''),
                data.searchId
            ], (error, result) => {
                if (error) {
                    resolve({
                        type: false,
                        msg: error,
                    });
                    return;
                }

                resolve({
                    type: true,
                    id: result.insertId
                });
            });
        } catch (error) {
            resolve({
                type: false,
                msg: error.message,
            });
        }
    });
}

function getTiCkets(db_host, db_user, db_password, db_base, selectStartDay, selectStartMonth, selectStartYear, selectEndDay, selectEndMont, selectEndYear, endDate, typeAvia, countAdalt, countChildren, countSuperChildren, outCitys, toCitys) {
    return new Promise(async (resolve, reject) => {
        var connection = mysql.createConnection({
            host: db_host,
            user: db_user,
            password: db_password,
            database: db_base,
        });
        try {
            var indexPages = pages.length;
            browser[indexPages] = await puppeteer.launch({
                headless: true,
                args: [
                    '--start-maximized',
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-accelerated-2d-canvas',
                    '--disable-gpu'
                ],
                executablePath: '/usr/bin/chromium-browser',
                defaultViewport: {
                    width: 1080,
                    height: 1080
                }
            });


            pages[indexPages] = await browser[indexPages].newPage();
            await pages[indexPages].setCacheEnabled(false);

            await pages[indexPages].goto('https://www.transavia.kz');

            await pages[indexPages].waitForSelector('#react-select-2--value > div.Select-input > input');

            var tableId = await insertSearchId(connection, selectStartDay, selectStartMonth, selectStartYear, selectEndDay, selectEndMont, selectEndYear, endDate, typeAvia, countAdalt, countChildren, countSuperChildren, outCitys, toCitys);
            if (tableId.type) {
                resolve({ search_id: tableId.id });

                const input = await pages[indexPages].$('#react-select-2--value > div.Select-input > input');
                if (input) {
                    await input.type(outCitys);
                    await sleep(500);
                    await pages[indexPages].keyboard.press('Enter');
                } else {
                    await updateStatusById(connection, tableId.id, 500);
                    await pageClose(indexPages, connection);
                    return false;
                }

                await pages[indexPages].waitForSelector('#react-select-3--value > div.Select-input > input');
                const input2 = await pages[indexPages].$('#react-select-3--value > div.Select-input > input');
                if (input2) {
                    await input2.type(toCitys);
                    await sleep(500);
                    await pages[indexPages].keyboard.press('Enter');
                } else {
                    await updateStatusById(connection, tableId.id, 500);
                    await pageClose(indexPages, connection);
                    return false;
                }


                await pages[indexPages].waitForSelector('#root > section > div > div.widget-segments > div > div.widget-dates > div:nth-child(1) > div > div.react-datepicker-wrapper > div > div > div:nth-child(1) > input');
                const dateInput = await pages[indexPages].$('#root > section > div > div.widget-segments > div > div.widget-dates > div:nth-child(1) > div > div.react-datepicker-wrapper > div > div > div:nth-child(1) > input');
                await dateInput.click();
                await sleep(500);

                var selectedPickDate = await clickToDatePromise(pages[indexPages], selectStartDay, selectStartYear, selectStartMonth, 1);
                if (!selectedPickDate) {
                    await updateStatusById(connection, tableId.id, 500);
                    await pageClose(indexPages, connection);
                    return false;
                }

                if (endDate) {
                    await pages[indexPages].waitForSelector('#root > section > div > div.widget-segments > div > div.widget-dates > div:nth-child(2) > div > div.react-datepicker-wrapper > div > div > div:nth-child(1) > input');
                    const dateInput2 = await pages[indexPages].$('#root > section > div > div.widget-segments > div > div.widget-dates > div:nth-child(2) > div > div.react-datepicker-wrapper > div > div > div:nth-child(1) > input');
                    await dateInput2.click();
                    await sleep(500);

                    var selectedPickDate2 = await clickToDatePromise(pages[indexPages], selectEndDay, selectEndYear, selectEndMont, 2);
                    if (!selectedPickDate2) {
                        await updateStatusById(connection, tableId.id, 500);
                        await pageClose(indexPages, connection);
                        return false;
                    }
                }

                await pages[indexPages].waitForSelector('#root > section > div > div.widget-footer > div:nth-child(1) > div');
                const countAdaltInput = await pages[indexPages].$('#root > section > div > div.widget-footer > div:nth-child(1) > div');
                await countAdaltInput.click();
                await sleep(500);


                if (typeAvia == 'bussines') {
                    await pages[indexPages].waitForSelector('#root > section > div > div.widget-footer > div:nth-child(1) > div > div > div > div.widget-ui-dropdown__content > div > div.widget-passengers__items > div.widget-classType > div > div:nth-child(2)');
                    const typeAviaButton = await pages[indexPages].$('#root > section > div > div.widget-footer > div:nth-child(1) > div > div > div > div.widget-ui-dropdown__content > div > div.widget-passengers__items > div.widget-classType > div > div:nth-child(2)');
                    await typeAviaButton.click();
                }

                await pages[indexPages].waitForSelector('#root > section > div > div.widget-footer > div:nth-child(1) > div > div > div > div.widget-ui-dropdown__content > div > div.widget-passengers__items > div:nth-child(1) > div.widget-passengers__counter > div.widget-ui-icon.widget-passengers__icon.widget-passengers__plus');
                const countAdaltPlusButton = await pages[indexPages].$('#root > section > div > div.widget-footer > div:nth-child(1) > div > div > div > div.widget-ui-dropdown__content > div > div.widget-passengers__items > div:nth-child(1) > div.widget-passengers__counter > div.widget-ui-icon.widget-passengers__icon.widget-passengers__plus');

                for (let index = 0; index < (countAdalt - 1); index++) {
                    await countAdaltPlusButton.click();
                }


                await pages[indexPages].waitForSelector('#root > section > div > div.widget-footer > div:nth-child(1) > div > div > div > div.widget-ui-dropdown__content > div > div.widget-passengers__items > div:nth-child(2) > div.widget-passengers__counter > div.widget-ui-icon.widget-passengers__icon.widget-passengers__plus');
                const countChildrenPlusButton = await pages[indexPages].$('#root > section > div > div.widget-footer > div:nth-child(1) > div > div > div > div.widget-ui-dropdown__content > div > div.widget-passengers__items > div:nth-child(2) > div.widget-passengers__counter > div.widget-ui-icon.widget-passengers__icon.widget-passengers__plus');

                for (let index = 0; index < countChildren; index++) {
                    await countChildrenPlusButton.click();
                }


                await pages[indexPages].waitForSelector('#root > section > div > div.widget-footer > div:nth-child(1) > div > div > div > div.widget-ui-dropdown__content > div > div.widget-passengers__items > div:nth-child(3) > div.widget-passengers__counter > div.widget-ui-icon.widget-passengers__icon.widget-passengers__plus');
                const countSuperChildrenPlusButton = await pages[indexPages].$('#root > section > div > div.widget-footer > div:nth-child(1) > div > div > div > div.widget-ui-dropdown__content > div > div.widget-passengers__items > div:nth-child(3) > div.widget-passengers__counter > div.widget-ui-icon.widget-passengers__icon.widget-passengers__plus');

                for (let index = 0; index < countSuperChildren; index++) {
                    await countSuperChildrenPlusButton.click();
                }


                await pages[indexPages].waitForSelector('#root > section > div > div.widget-footer > div:nth-child(2) > button');
                const buttonSearch = await pages[indexPages].$('#root > section > div > div.widget-footer > div:nth-child(2) > button');

                await buttonSearch.click();

                const selector1 = 'body > div.nemo-common-pageWrapper > div.nemo-widget.nemo-widget_flights > div > div > div.nemo-flights-results.js-nemoApp__component > section.nemo-flights-results__content > section > div.nemo-flights-results__groupList.nemo-flights-results__groupList_list';
                const selector2 = 'body > div.nemo-common-pageWrapper > div.nemo-widget.nemo-widget_flights > div > div > div.nemo-flights-results.js-nemoApp__component > div > div.nemo-flights-results__error__header';

                const firstElement = await Promise.race([
                    pages[indexPages].waitForSelector(selector1, { timeout: 50000 }).then(() => 'list'),
                    pages[indexPages].waitForSelector(selector2, { timeout: 50000 }).then(() => 'error')
                ]);

                if (firstElement === 'error') {
                    await updateStatusById(connection, tableId.id, 500);
                    await pageClose(indexPages, connection);
                    return false;
                }



                const selector = 'body > div.nemo-common-pageWrapper > div.nemo-widget.nemo-widget_flights > div > div > div.nemo-flights-results.js-nemoApp__component > section.nemo-flights-results__content > section > div.nemo-flights-results__showAll > button';

                sleep(1000);

                const button = await pages[indexPages].$(selector);
                if (button) {
                    await button.click();
                }

                const items = await pages[indexPages].$$eval('.nemo-flights-results__groupList__item', items => {
                    const content = [];
                    items.forEach(item => {
                        content.push(item.innerHTML);
                    });
                    return content;
                });



                for (let index = 0; index < items.length; index++) {
                    const element = items[index];
                    const $ = cheerio.load(element);

                    const logo = $('.nemo-flights-results__flightsListGroup__segment__carrier__logo').attr('src');


                    const prices = $('.nemo-flights-results__flightsListGroup__segment__dropdown__option').map((i, el) => {
                        var stopped = $(el).find('.nemo-flights-results__flightsListGroup__segment__option__additionalDetails__transfers__points__point').map((i, el) => {
                            return $(el).text().trim().replace(/[\n\t\r]+/g, '');
                        }).get().join(', ');

                        var data = {
                            departureTime: $(el).find('.nemo-flights-results__flightsListGroup__full__segment__dropdown__option__departure').text().trim().replace(/[\n\t\r]+/g, ''),
                            arrivalTime: $(el).find('.nemo-flights-results__flightsListGroup__full__segment__dropdown__option__arrival').text().trim().replace(/[\n\t\r]+/g, ''),
                            stopped: stopped,
                            timeTravel: $(el).find('.nemo-flights-results__flightsListGroup__full__segment__option__additionalDetails__timeEnRoute__value').text().trim().replace(/[\n\t\r]+/g, ''),
                            availableSeats: $(el).find('.nemo-flights-results__flightsListGroup__segment__dropdown__option__availSeats').text().trim().replace(/[\n\t\r]+/g, ''),
                            stops: $(el).find('.nemo-flights-results__flightsListGroup__full__segment__option__additionalDetails__transfers__count').text().trim().replace(/[\n\t\r]+/g, ''),
                        };
                        if (data['departureTime']) {
                            return data;
                        }
                    }).get();

                    const departureDate = $('.nemo-flights-results__flightsGroup__leg__info__date__day').text().trim().replace(/[\n\t\r]+/g, '');
                    const departureDayOfWeek = $('.nemo-flights-results__flightsGroup__leg__info__date__dayOfWeek').text().trim().replace(/[\n\t\r]+/g, '');
                    const departureCity = $('.nemo-flights-results__flightsGroup__leg__info__route__departure .nemo-flights-results__flightsListGroup__full__IATA').next().text().trim().replace(/[\n\t\r]+/g, '');
                    const arrivalCity = $('.nemo-flights-results__flightsGroup__leg__info__route__arrival .nemo-flights-results__flightsListGroup__full__IATA').next().text().trim().replace(/[\n\t\r]+/g, '');
                    const price = $('.nemo-flights-results__flightsListGroup__buyButton__text').text().trim();

                    const fareFeatures = $('.nemo-flights-results__fareFamilies__features__feature').map((i, el) => {
                        return $(el).text().trim();
                    }).get();

                    if (prices.length > 0) {
                        for (let index = 0; index < prices.length; index++) {
                            const price_cont = prices[index];
                            if (price.length > 0) {
                                var bagage = '';
                                var clad = '';
                                fareFeatures.forEach(element => {
                                    if (element.includes('Ручная кладь')) {
                                        clad = element.replace(/\D/g, '');
                                    }
                                    if (element.includes('Багаж')) {
                                        bagage = element.replace(/\D/g, '');
                                    }
                                });

                                await insertAviaTicket(
                                    connection,
                                    {
                                        logo: 'https://b2c.transavia.kz' + logo,
                                        departureDate: departureDate,
                                        departureDayOfWeek: departureDayOfWeek,
                                        departureCity: departureCity,
                                        arrivalCity: arrivalCity,
                                        departureTime: price_cont['departureTime'],
                                        arrivalTime: price_cont['arrivalTime'],
                                        availableSeats: price_cont['availableSeats'],
                                        stops: price_cont['stops'],
                                        stopDetails: price_cont['stopped'],
                                        travelTime: price_cont['timeTravel'],
                                        price: price.replace(/\D/g, ''),
                                        fareFeatures: fareFeatures,
                                        clad: clad,
                                        bagage: bagage,
                                        searchId: tableId.id,
                                    }
                                );
                            }
                        }
                    }
                }

                await updateStatusById(connection, tableId.id, 200);
                await pageClose(indexPages, connection);
                return true;
            } else {
                await pageClose(indexPages, connection);
                resolve({
                    type: false,
                    msg: tableId.msg
                });
                return false;
            }

        } catch (error) {
            await pageClose(indexPages, connection);
            resolve({
                type: false,
                msg: 'Error searched tickets...'
            });
            return false;
        }
    });
}


(async () => {
    try {
        http.createServer(async function (req, res) {
            res.writeHead(200, { 'Content-Type': 'text/json' });
            var q = url.parse(req.url, true).query;

            if (q.type == 'create_search') {
                var data = await getTiCkets(
                    q.db_host,
                    q.db_user,
                    q.db_pass,
                    q.db_name,
                    parseInt(q.startDateDay),
                    parseInt(q.startDateMonth),
                    parseInt(q.startDateYeath),
                    parseInt(q.endDateDay),
                    parseInt(q.endDateMonth),
                    parseInt(q.endDateYearh),
                    q.endTickets === "true",
                    q.aviaClass,
                    parseInt(q.countAdult),
                    parseInt(q.countChildren),
                    parseInt(q.countInfant),
                    q.cityOute,
                    q.cityTo
                );

                var txt = JSON.stringify({
                    type: true,
                    id: data,
                });
                res.end(txt);

            } else {
                var txt = JSON.stringify({
                    type: false,
                    msg: 'Method not found!',
                });
                res.end(txt);
            }
        }).listen(1373);
        console.log('HTTP сервер запущен!');
    } catch (error) {
        console.log('Не удалось запустить сервер!', error);
    }

})();