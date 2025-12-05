document.addEventListener('DOMContentLoaded', function () {
    // Инициализация анимаций
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true
    });

    // Обратный отсчет
    function updateCountdown() {
        const eventDate = new Date("<?= $event['date_event'] ?>").getTime();
        const now = new Date().getTime();
        const distance = eventDate - now;

        if (distance > 0) {
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

            document.getElementById("cd_days").innerHTML = days.toString().padStart(2, '0');
            document.getElementById("cd_hours").innerHTML = hours.toString().padStart(2, '0');
            document.getElementById("cd_mins").innerHTML = minutes.toString().padStart(2, '0');
        } else {
            document.getElementById("cd_days").innerHTML = "00";
            document.getElementById("cd_hours").innerHTML = "00";
            document.getElementById("cd_mins").innerHTML = "00";
        }
    }

    updateCountdown();
    setInterval(updateCountdown, 60000);

    // Preloader
    window.addEventListener('load', function () {
        setTimeout(function () {
            const preloader = document.querySelector('.preloader');
            if (preloader) {
                preloader.classList.add('fade-out');
                setTimeout(() => {
                    preloader.style.display = 'none';
                }, 500);
            }
        }, 1000);
    });

    // Navbar scroll effect
    window.addEventListener('scroll', function () {
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }
    });

    // Phone Input Functionality
    const initPhoneFields = () => {
        const countrySelect = document.getElementById('countrySelect');
        const phonePrefix = document.getElementById('phonePrefix');
        const inviterPhonePrefix = document.getElementById('inviterPhonePrefix');
        const phoneCheckStatus = document.getElementById('phoneCheckStatus');
        const inviterCheckStatus = document.getElementById('inviterCheckStatus');
        const inviterInfo = document.getElementById('inviter_info');
        const regFio = document.getElementById('reg_fio');
        const regInviterCode = document.getElementById('reg_inviter_code');

        // Функция для генерации аватара по умолчанию
        function generateDefaultAvatar(name) {
            const firstLetter = name ? name.charAt(0).toUpperCase() : '?';
            const colors = ['#FF5733', '#33FF57', '#3357FF', '#F333FF', '#33FFF5'];
            const color = colors[Math.floor(Math.random() * colors.length)];

            return `
                <div class="default-avatar" style="background-color: ${color}">
                    ${firstLetter}
                </div>
            `;
        }

        // Phone mask options с автоматической проверкой при полном вводе
        const getMaskOptions = (prefix) => ({
            mask: `+{${prefix}}(000)000-00-00`,
            lazy: false,
            placeholderChar: '_',
            blocks: {
                '0': { mask: IMask.MaskedRange, from: 0, to: 9 }
            },
            onComplete: function () {
                const phone = this.unmaskedValue;
                const countryCode = countrySelect?.value || 'KZ';
                if (phone.length >= 10) {
                    checkPhone(phone, countryCode);
                }
            }
        });

        // Initialize masks
        let phoneMask = IMask(document.querySelector('input[name="reg_phone"]'), getMaskOptions('7'));
        let inviterPhoneMask = IMask(document.querySelector('input[name="reg_inviter_phone"]'), getMaskOptions('7'));

        // Country change handler
        countrySelect?.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            const prefix = selected.getAttribute('data-prefix');
            const flag = selected.getAttribute('data-flag');

            // Update UI
            document.getElementById('countryFlag').src = `https://flagcdn.com/w20/${flag}.png`;
            phonePrefix.textContent = `+${prefix}`;
            inviterPhonePrefix.textContent = `+${prefix}`;

            // Update masks
            phoneMask.updateOptions(getMaskOptions(prefix));
            inviterPhoneMask.updateOptions(getMaskOptions(prefix));

            // Clear values
            phoneMask.value = '';
            inviterPhoneMask.value = '';
        });

        // Phone validation
        const validatePhone = (phone, countryCode) => {
            const phoneRegex = {
                'KZ': /^7[0-9]{10}$/,
                'RU': /^7[0-9]{10}$/,
                'UZ': /^998[0-9]{9}$/,
                'AZ': /^994[0-9]{9}$/,
                'BY': /^375[0-9]{9}$/,
                'KG': /^996[0-9]{9}$/,
                'GE': /^995[0-9]{9}$/,
                'AM': /^374[0-9]{8}$/
            };
            return phoneRegex[countryCode]?.test(phone) || false;
        };

        // Функция для поиска пользователя с улучшенным прелоадером
        async function searchUser(value, type) {
            try {
                // Показываем красивый прелоадер
                const loader = `
                    <div class="loader-container">
                        <div class="loader-spinner"></div>
                        <div class="loader-text">Ищем пользователя...</div>
                    </div>
                `;

                if (type === 'phone') {
                    phoneCheckStatus.innerHTML = loader;
                } else {
                    inviterCheckStatus.innerHTML = loader;
                }

                const response = await fetch('/api/search_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type, value })
                });
                return await response.json();
            } catch (error) {
                console.error('Ошибка поиска пользователя:', error);
                return { success: false, message: 'Ошибка соединения' };
            }
        }

        // Check phone handler с автоматической подстановкой ФИО
        const checkPhone = async (phone, countryCode) => {
            if (!validatePhone(phone, countryCode)) {
                phoneCheckStatus.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-circle"></i> Неверный формат телефона</div>';
                return;
            }

            try {
                const data = await searchUser(phone, 'phone');

                if (data.success && data.user) {
                    // Подставляем ФИО в поле (если оно есть)
                    if (regFio) {
                        const fullName = [
                            data.user.famale || '',
                            data.user.name || '',
                            data.user.surname || ''
                        ].filter(Boolean).join(' ');
                        regFio.value = fullName;
                    }

                    // Показываем информацию о пользователе
                    const avatar = data.user.avatar
                        ? `<img src="${data.user.avatar}" class="user-avatar">`
                        : generateDefaultAvatar(data.user.name);

                    phoneCheckStatus.innerHTML = `
                        <div class="user-info success-message">
                            ${avatar}
                            <div class="user-details">
                                <div class="user-name">${data.user.famale || ''} ${data.user.name || ''} ${data.user.surname || ''}</div>
                                <div class="user-phone">${data.user.phone}</div>
                            </div>
                        </div>
                    `;
                } else {
                    phoneCheckStatus.innerHTML = '<div class="info-message"><i class="fas fa-info-circle"></i> Пользователь не найден</div>';
                }
            } catch (error) {
                console.error('Error:', error);
                phoneCheckStatus.innerHTML = '<div class="error-message"><i class="fas fa-times-circle"></i> Ошибка проверки</div>';
            }
        };

        // Check inviter handler
        const checkInviter = async (phone, countryCode) => {
            if (!validatePhone(phone, countryCode)) {
                inviterCheckStatus.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-circle"></i> Неверный формат телефона</div>';
                return;
            }

            try {
                const data = await searchUser(phone, 'phone');

                if (data.success && data.user) {
                    inviterCheckStatus.innerHTML = '<div class="success-message"><i class="fas fa-check-circle"></i> Пользователь найден</div>';

                    // Показываем информацию о пригласителе
                    const avatar = data.user.avatar
                        ? `<img src="${data.user.avatar}" class="user-avatar">`
                        : generateDefaultAvatar(data.user.name);

                    inviterInfo.innerHTML = `
                        <div class="inviter-info">
                            ${avatar}
                            <div class="inviter-details">
                                <div class="inviter-name">${data.user.famale || ''} ${data.user.name || ''} ${data.user.surname || ''}</div>
                                <div class="inviter-phone">${data.user.phone}</div>
                                ${data.user.promo_code ? `<div class="inviter-promo">Промокод: ${data.user.promo_code}</div>` : ''}
                                <div class="inviter-bonus">Вы получите бонусы, если этот пользователь посетит мероприятие</div>
                            </div>
                        </div>
                    `;

                    if (regInviterCode) regInviterCode.value = data.user.promo_code;
                } else {
                    inviterCheckStatus.innerHTML = '<div class="error-message"><i class="fas fa-times-circle"></i> Пользователь не найден</div>';
                    inviterInfo.innerHTML = '';
                }
            } catch (error) {
                console.error('Error:', error);
                inviterCheckStatus.innerHTML = '<div class="error-message"><i class="fas fa-times-circle"></i> Ошибка проверки</div>';
            }
        };

        // Проверка по промокоду
        const checkInviterByCode = async (code) => {
            if (code.length < 3) return;

            try {
                const data = await searchUser(code, 'promo');

                if (data.success && data.user) {
                    inviterCheckStatus.innerHTML = '<div class="success-message"><i class="fas fa-check-circle"></i> Пользователь найден</div>';

                    // Показываем информацию о пригласителе
                    const avatar = data.user.avatar
                        ? `<img src="${data.user.avatar}" class="user-avatar">`
                        : generateDefaultAvatar(data.user.name);

                    inviterInfo.innerHTML = `
                        <div class="inviter-info">
                            ${avatar}
                            <div class="inviter-details">
                                <div class="inviter-name">${data.user.famale || ''} ${data.user.name || ''} ${data.user.surname || ''}</div>
                                <div class="inviter-phone">${data.user.phone}</div>
                                <div class="inviter-promo">Промокод: ${data.user.promo_code}</div>
                                <div class="inviter-bonus">Вы получите бонусы, если этот пользователь посетит мероприятие</div>
                            </div>
                        </div>
                    `;
                } else {
                    inviterCheckStatus.innerHTML = '<div class="error-message"><i class="fas fa-times-circle"></i> Промокод не найден</div>';
                    inviterInfo.innerHTML = '';
                }
            } catch (error) {
                console.error('Error:', error);
                inviterCheckStatus.innerHTML = '<div class="error-message"><i class="fas fa-times-circle"></i> Ошибка проверки</div>';
            }
        };

        // Event listeners for phone fields
        document.querySelector('input[name="reg_phone"]')?.addEventListener('input', function () {
            const phone = phoneMask.unmaskedValue;
            const countryCode = countrySelect?.value || 'KZ';
            if (phone.length >= 10) checkPhone(phone, countryCode);
        });

        document.querySelector('input[name="reg_inviter_phone"]')?.addEventListener('input', function () {
            const phone = inviterPhoneMask.unmaskedValue;
            const countryCode = countrySelect?.value || 'KZ';
            if (phone.length >= 10) checkInviter(phone, countryCode);
        });

        // Обработчик для промокода
        document.getElementById('reg_inviter_code')?.addEventListener('input', function () {
            const code = this.value.trim();
            checkInviterByCode(code);
        });

        // Clear inviter info when typing
        document.querySelector('input[name="reg_inviter_phone"]')?.addEventListener('input', function () {
            if (this.value.length > 0 && inviterInfo) {
                inviterInfo.innerHTML = '';
            }
        });

        document.getElementById('reg_inviter_code')?.addEventListener('input', function () {
            if (this.value.length > 0 && inviterInfo) {
                inviterInfo.innerHTML = '';
            }
        });
    };

    // Initialize phone fields functionality
    initPhoneFields();
});