const express = require("express");
const puppeteer = require("puppeteer");
const fs = require("fs");
const path = require("path");

const app = express();
const PORT = 4534;
const BASE_URL = "https://api.v.2.byfly.kz/html_to_png/";
const SAVE_PATH = path.join(__dirname, "html_to_png");

if (!fs.existsSync(SAVE_PATH)) {
    fs.mkdirSync(SAVE_PATH, { recursive: true });
}

let browser;

(async () => {
    browser = await puppeteer.launch({
        executablePath: "/usr/bin/google-chrome-stable",
        headless: true,
        args: [
            "--no-sandbox",
            "--disable-setuid-sandbox",
            "--disable-dev-shm-usage",
            "--disable-gpu",
            "--single-process",
            "--no-zygote",
        ],
    });
})();

app.get("/convert", async (req, res) => {
    const { url } = req.query;
    let width = Number(req.query.width) || 1080;
    let height = (Number(req.query.height) - 20) || 1920;

    if (!url) {
        return res.status(400).json({ type: false, msg: "Ошибка: Укажите URL в параметре ?url" });
    }

    try {
        const page = await browser.newPage();
        await page.setViewport({ width, height, deviceScaleFactor: 2 });
        await page.goto(url, { waitUntil: "networkidle0" });

        // Ожидание загрузки всех <img>
        await page.evaluate(async () => {
            const images = Array.from(document.images);
            await Promise.all(
                images.map((img) => new Promise((resolve) => {
                    if (img.complete) resolve();
                    else img.onload = img.onerror = resolve;
                }))
            );
        });

        // Ожидание загрузки всех background-image
        await page.evaluate(async () => {
            function checkBackgroundImageLoaded(element) {
                return new Promise((resolve) => {
                    const style = window.getComputedStyle(element);
                    const bgImage = style.backgroundImage;
                    if (!bgImage || bgImage === "none") return resolve();

                    const urlMatch = bgImage.match(/url\(["']?(.*?)["']?\)/);
                    if (!urlMatch) return resolve();

                    const img = new Image();
                    img.src = urlMatch[1];
                    img.onload = img.onerror = () => resolve();
                });
            }

            const elements = Array.from(document.querySelectorAll("*"));
            await Promise.all(elements.map((el) => checkBackgroundImageLoaded(el)));
        });

        const fileName = `${Date.now()}.png`;
        const filePath = path.join(SAVE_PATH, fileName);
        await page.screenshot({ path: filePath, clip: { x: 0, y: 0, width, height } });

        await page.close();

        res.json({ type: true, link: `${BASE_URL}html_to_png/${fileName}` });

        setTimeout(() => fs.unlink(filePath, () => { }), 1200000);
    } catch (error) {
        res.status(500).json({ type: false, msg: `Ошибка при генерации PNG: ${error.message}` });
    }
});

app.listen(PORT, () => console.log(`Сервер запущен на порту ${PORT}`));
