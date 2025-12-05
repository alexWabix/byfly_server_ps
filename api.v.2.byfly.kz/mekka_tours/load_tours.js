import puppeteer from 'puppeteer';
import axios from 'axios';

(async () => {
    let browser;
    const allTours = new Map();
    try {
        browser = await puppeteer.launch({
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
            ],
        });

        const startDate = new Date(2025, 0, 29);
        const endDate = new Date(startDate);
        endDate.setMonth(endDate.getMonth() + 6);

        const dateRanges = [];
        let currentDate = new Date(startDate);

        while (currentDate < endDate) {
            const checkinBeg = formatDate(currentDate);
            currentDate.setDate(currentDate.getDate() + 15);
            const checkinEnd = formatDate(currentDate);
            dateRanges.push({ checkinBeg, checkinEnd });
        }



        for (const { checkinBeg, checkinEnd } of dateRanges) {
            const url = `https://online.kompastour.kz/search_tour?TOWNFROMINC=9&STATEINC=124&FREIGHTTYPE=0&TOURINC=0&CHECKIN_BEG=${checkinBeg}&NIGHTS_FROM=7&CHECKIN_END=${checkinEnd}&NIGHTS_TILL=14&ADULT=2&CURRENCY=1&CHILD=0&TOWNS_ANY=1&TOWNS=&STARS_ANY=1&STARS=&hotelsearch=0&HOTELS_ANY=1&HOTELS=&MEALS_ANY=1&MEALS=&ROOMS_ANY=1&ROOMS=&FREIGHT=0&FILTER=0&HOTELTYPES=&PARTITION_PRICE=224&PRICEPAGE=1&rev=577594982&_=1738054426407&DOLOAD=1`;

            const page = await browser.newPage();
            await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 120000 });

            const data = await page.evaluate(() => {
                const rows = document.querySelectorAll('tr.price_info');
                const tours = [];

                rows.forEach((row) => {
                    const id = row.getAttribute('data-cat-claim') || null;
                    const link = row.querySelector('a')?.getAttribute('href') || null;
                    const checkInDate = row.querySelector('td.sortie')?.textContent?.trim() || null;
                    const checkInTime = row.querySelector('td.sortie .helpalt')?.textContent?.trim() || null;
                    const hotelName = row.querySelector('.link-hotel')?.textContent?.trim() || null;
                    const mealType = row.querySelector('td:nth-child(7)')?.textContent?.trim() || null;
                    const flightType = row.querySelector('.transport span')?.textContent?.trim() || 'Не указано';
                    const roomType = row.querySelector('td:nth-child(8) span')?.textContent?.trim() || null;
                    const price = row.querySelector('.price_button')?.textContent?.trim() || null;
                    const nights = row.querySelector('td.c')?.textContent?.trim() || null;

                    const uniqueKey = `${id || ''}_${hotelName}_${checkInDate}_${price}`;

                    tours.push({
                        id,
                        link,
                        checkInDate,
                        checkInTime,
                        hotelName,
                        mealType,
                        flightType,
                        count_adult: 2,
                        roomType,
                        price,
                        nights,
                        city_oute: 'Алматы',
                        city_oute_id: 60,
                    });
                });

                return tours;
            });


            data.forEach((tour) => {
                const uniqueKey = `${tour.id || ''}_${tour.hotelName}_${tour.checkInDate}_${tour.price}`;
                allTours.set(uniqueKey, tour);
            });

            await page.close();
            await delay(5000);
        }


    } catch (error) {
        console.error("❌ Ошибка во время выполнения скрипта:", error);
    } finally {
        if (browser) {
            await browser.close();
        }
    }


    try {
        browser = await puppeteer.launch({
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
            ],
        });

        const startDate = new Date(2025, 0, 29);
        const endDate = new Date(startDate);
        endDate.setMonth(endDate.getMonth() + 6);

        const dateRanges = [];
        let currentDate = new Date(startDate);

        while (currentDate < endDate) {
            const checkinBeg = formatDate(currentDate);
            currentDate.setDate(currentDate.getDate() + 15);
            const checkinEnd = formatDate(currentDate);
            dateRanges.push({ checkinBeg, checkinEnd });
        }



        for (const { checkinBeg, checkinEnd } of dateRanges) {
            const url = `https://online.kompastour.kz/search_tour?TOWNFROMINC=9&STATEINC=124&FREIGHTTYPE=0&TOURINC=0&CHECKIN_BEG=${checkinBeg}&NIGHTS_FROM=7&CHECKIN_END=${checkinEnd}&NIGHTS_TILL=14&ADULT=1&CURRENCY=1&CHILD=0&TOWNS_ANY=1&TOWNS=&STARS_ANY=1&STARS=&hotelsearch=0&HOTELS_ANY=1&HOTELS=&MEALS_ANY=1&MEALS=&ROOMS_ANY=1&ROOMS=&FREIGHT=0&FILTER=0&HOTELTYPES=&PARTITION_PRICE=224&PRICEPAGE=1&rev=577594982&_=1738054426407&DOLOAD=1`;

            const page = await browser.newPage();
            await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 120000 });

            const data = await page.evaluate(() => {
                const rows = document.querySelectorAll('tr.price_info');
                const tours = [];

                rows.forEach((row) => {
                    const id = row.getAttribute('data-cat-claim') || null;
                    const link = row.querySelector('a')?.getAttribute('href') || null;
                    const checkInDate = row.querySelector('td.sortie')?.textContent?.trim() || null;
                    const checkInTime = row.querySelector('td.sortie .helpalt')?.textContent?.trim() || null;
                    const hotelName = row.querySelector('.link-hotel')?.textContent?.trim() || null;
                    const mealType = row.querySelector('td:nth-child(7)')?.textContent?.trim() || null;
                    const flightType = row.querySelector('.transport span')?.textContent?.trim() || 'Не указано';
                    const roomType = row.querySelector('td:nth-child(8) span')?.textContent?.trim() || null;
                    const price = row.querySelector('.price_button')?.textContent?.trim() || null;
                    const nights = row.querySelector('td.c')?.textContent?.trim() || null;

                    const uniqueKey = `${id || ''}_${hotelName}_${checkInDate}_${price}`;

                    tours.push({
                        id,
                        link,
                        checkInDate,
                        checkInTime,
                        hotelName,
                        mealType,
                        count_adult: 1,
                        flightType,
                        roomType,
                        price,
                        nights,
                        city_oute: 'Алматы',
                        city_oute_id: 60,
                    });
                });

                return tours;
            });


            data.forEach((tour) => {
                const uniqueKey = `${tour.id || ''}_${tour.hotelName}_${tour.checkInDate}_${tour.price}`;
                allTours.set(uniqueKey, tour);
            });

            await page.close();
            await delay(5000);
        }


    } catch (error) {
        console.error("❌ Ошибка во время выполнения скрипта:", error);
    } finally {
        if (browser) {
            await browser.close();
        }
    }



    try {
        browser = await puppeteer.launch({
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
            ],
        });

        const startDate = new Date(2025, 0, 29);
        const endDate = new Date(startDate);
        endDate.setMonth(endDate.getMonth() + 6);

        const dateRanges = [];
        let currentDate = new Date(startDate);

        while (currentDate < endDate) {
            const checkinBeg = formatDate(currentDate);
            currentDate.setDate(currentDate.getDate() + 15);
            const checkinEnd = formatDate(currentDate);
            dateRanges.push({ checkinBeg, checkinEnd });
        }



        for (const { checkinBeg, checkinEnd } of dateRanges) {
            const url = `https://online.kompastour.kz/search_tour?TOWNFROMINC=17&STATEINC=124&FREIGHTTYPE=0&TOURINC=0&CHECKIN_BEG=${checkinBeg}&NIGHTS_FROM=7&CHECKIN_END=${checkinEnd}&NIGHTS_TILL=14&ADULT=2&CURRENCY=1&CHILD=0&TOWNS_ANY=1&TOWNS=&STARS_ANY=1&STARS=&hotelsearch=0&HOTELS_ANY=1&HOTELS=&MEALS_ANY=1&MEALS=&ROOMS_ANY=1&ROOMS=&FREIGHT=0&FILTER=0&HOTELTYPES=&PARTITION_PRICE=224&PRICEPAGE=1&rev=577594982&_=1738054426407&DOLOAD=1`;

            const page = await browser.newPage();
            await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 120000 });

            const data = await page.evaluate(() => {
                const rows = document.querySelectorAll('tr.price_info');
                const tours = [];

                rows.forEach((row) => {
                    const id = row.getAttribute('data-cat-claim') || null;
                    const link = row.querySelector('a')?.getAttribute('href') || null;
                    const checkInDate = row.querySelector('td.sortie')?.textContent?.trim() || null;
                    const checkInTime = row.querySelector('td.sortie .helpalt')?.textContent?.trim() || null;
                    const hotelName = row.querySelector('.link-hotel')?.textContent?.trim() || null;
                    const mealType = row.querySelector('td:nth-child(7)')?.textContent?.trim() || null;
                    const flightType = row.querySelector('.transport span')?.textContent?.trim() || 'Не указано';
                    const roomType = row.querySelector('td:nth-child(8) span')?.textContent?.trim() || null;
                    const price = row.querySelector('.price_button')?.textContent?.trim() || null;
                    const nights = row.querySelector('td.c')?.textContent?.trim() || null;

                    const uniqueKey = `${id || ''}_${hotelName}_${checkInDate}_${price}`;

                    tours.push({
                        id,
                        link,
                        checkInDate,
                        checkInTime,
                        hotelName,
                        mealType,
                        flightType,
                        count_adult: 2,
                        roomType,
                        price,
                        nights,
                        city_oute: 'Шымкент',
                        city_oute_id: 79,
                    });
                });

                return tours;
            });


            data.forEach((tour) => {
                const uniqueKey = `${tour.id || ''}_${tour.hotelName}_${tour.checkInDate}_${tour.price}`;
                allTours.set(uniqueKey, tour);
            });

            await page.close();
            await delay(5000);
        }


    } catch (error) {
        console.error("❌ Ошибка во время выполнения скрипта:", error);
    } finally {
        if (browser) {
            await browser.close();
        }

    }


    try {
        browser = await puppeteer.launch({
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
            ],
        });

        const startDate = new Date(2025, 0, 29);
        const endDate = new Date(startDate);
        endDate.setMonth(endDate.getMonth() + 6);

        const dateRanges = [];
        let currentDate = new Date(startDate);

        while (currentDate < endDate) {
            const checkinBeg = formatDate(currentDate);
            currentDate.setDate(currentDate.getDate() + 15);
            const checkinEnd = formatDate(currentDate);
            dateRanges.push({ checkinBeg, checkinEnd });
        }



        for (const { checkinBeg, checkinEnd } of dateRanges) {
            const url = `https://online.kompastour.kz/search_tour?TOWNFROMINC=17&STATEINC=124&FREIGHTTYPE=0&TOURINC=0&CHECKIN_BEG=${checkinBeg}&NIGHTS_FROM=7&CHECKIN_END=${checkinEnd}&NIGHTS_TILL=14&ADULT=1&CURRENCY=1&CHILD=0&TOWNS_ANY=1&TOWNS=&STARS_ANY=1&STARS=&hotelsearch=0&HOTELS_ANY=1&HOTELS=&MEALS_ANY=1&MEALS=&ROOMS_ANY=1&ROOMS=&FREIGHT=0&FILTER=0&HOTELTYPES=&PARTITION_PRICE=224&PRICEPAGE=1&rev=577594982&_=1738054426407&DOLOAD=1`;

            const page = await browser.newPage();
            await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 120000 });

            const data = await page.evaluate(() => {
                const rows = document.querySelectorAll('tr.price_info');
                const tours = [];

                rows.forEach((row) => {
                    const id = row.getAttribute('data-cat-claim') || null;
                    const link = row.querySelector('a')?.getAttribute('href') || null;
                    const checkInDate = row.querySelector('td.sortie')?.textContent?.trim() || null;
                    const checkInTime = row.querySelector('td.sortie .helpalt')?.textContent?.trim() || null;
                    const hotelName = row.querySelector('.link-hotel')?.textContent?.trim() || null;
                    const mealType = row.querySelector('td:nth-child(7)')?.textContent?.trim() || null;
                    const flightType = row.querySelector('.transport span')?.textContent?.trim() || 'Не указано';
                    const roomType = row.querySelector('td:nth-child(8) span')?.textContent?.trim() || null;
                    const price = row.querySelector('.price_button')?.textContent?.trim() || null;
                    const nights = row.querySelector('td.c')?.textContent?.trim() || null;

                    const uniqueKey = `${id || ''}_${hotelName}_${checkInDate}_${price}`;

                    tours.push({
                        id,
                        link,
                        checkInDate,
                        checkInTime,
                        hotelName,
                        mealType,
                        flightType,
                        count_adult: 1,
                        roomType,
                        price,
                        nights,
                        city_oute: 'Шымкент',
                        city_oute_id: 79,
                    });
                });

                return tours;
            });


            data.forEach((tour) => {
                const uniqueKey = `${tour.id || ''}_${tour.hotelName}_${tour.checkInDate}_${tour.price}`;
                allTours.set(uniqueKey, tour);
            });

            await page.close();
            await delay(5000);
        }


    } catch (error) {
        console.error("❌ Ошибка во время выполнения скрипта:", error);
    } finally {
        if (browser) {
            await browser.close();
        }

    }



    try {
        const toursArray = Array.from(allTours.values());
        if (toursArray.length === 0) {
            console.log('⚠️ Нет данных для отправки.');
            return;
        }

        const response = await axios.post('https://api.v.2.byfly.kz/mekka_tours/send_tours_mekka.php', {
            list: toursArray,
        });

        if (response.data && response.data.type) {
            console.log('✅ Результаты обновлены!');
        } else {
            console.log('❌ Ошибка на сервере:', response.data?.msg || 'Неизвестная ошибка');
        }
    } catch (error) {
        console.error('🚨 Ошибка при отправке:', error.response?.data || error.message);
    }

})();

// Функция форматирования даты (YYYYMMDD)
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}${month}${day}`;
}

// Функция задержки
function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}
