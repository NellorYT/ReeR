// UnionCase - Основные скрипты

// Глобальные переменные
let isSpinning = false;
let spinInterval = null;
let wonItem = null;
let spinSpeed = 30;
let spinPosition = 0;
let spinDuration = 0;
const SPIN_TIME = 3000; // 3 секунды вращения
const SLOW_DOWN_TIME = 2000; // 2 секунды замедления

// ===== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ =====

// Получение CSRF токена из скрытого поля
function getCsrfToken() {
    const tokenInput = document.getElementById('global-csrf-token');
    return tokenInput ? tokenInput.value : '';
}

// Форматирование цены
function formatPrice(price) {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price);
}

// Получение URL изображения
function getImageUrl(image) {
    if (!image) return '';
    const baseUrl = window.location.origin;
    const sitePath = window.location.pathname.includes('/UnionCase/') ? '/UnionCase' : '';
    return baseUrl + sitePath + '/assets/images/' + image;
}

// Цвета редкости
function getRarityColor(rarity) {
    const colors = {
        'common': '#b0b0b0',
        'uncommon': '#4bff91',
        'rare': '#4b8bff',
        'epic': '#b24bff',
        'legendary': '#ffd700'
    };
    return colors[rarity] || '#b0b0b0';
}

// Названия редкости
function getRarityName(rarity) {
    const names = {
        'common': 'Обычный',
        'uncommon': 'Необычный',
        'rare': 'Редкий',
        'epic': 'Эпический',
        'legendary': 'Легендарный'
    };
    return names[rarity] || 'Обычный';
}

// Уведомления
function showNotification(message, type = 'info') {
    const oldNotifications = document.querySelectorAll('.flash-message');
    oldNotifications.forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `flash-message flash-${type}`;
    
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    if (type === 'error') icon = 'times-circle';
    if (type === 'warning') icon = 'exclamation-triangle';
    
    notification.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('hiding');
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// Обновление баланса
function updateBalance(newBalance) {
    const balanceElement = document.getElementById('header-balance');
    if (balanceElement) {
        balanceElement.textContent = formatPrice(newBalance);
    }
}

// ===== МОБИЛЬНОЕ МЕНЮ =====
function toggleMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    if (menu) {
        menu.classList.toggle('active');
    }
}

// ===== МЕНЮ ПОЛЬЗОВАТЕЛЯ =====
function toggleUserMenu() {
    const dropdown = document.getElementById('user-dropdown');
    if (!dropdown) return;
    
    const isVisible = dropdown.style.opacity === '1';
    dropdown.style.opacity = isVisible ? '0' : '1';
    dropdown.style.visibility = isVisible ? 'hidden' : 'visible';
}

// Закрытие меню при клике вне
document.addEventListener('click', (e) => {
    const userMenu = document.querySelector('.user-menu');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileBtn = document.querySelector('.mobile-menu-btn');
    
    if (userMenu && !userMenu.contains(e.target)) {
        const dropdown = document.getElementById('user-dropdown');
        if (dropdown) {
            dropdown.style.opacity = '0';
            dropdown.style.visibility = 'hidden';
        }
    }
    
    if (mobileMenu && mobileBtn && !mobileMenu.contains(e.target) && !mobileBtn.contains(e.target)) {
        mobileMenu.classList.remove('active');
    }
});

// ===== МОДАЛЬНОЕ ОКНО ПОПОЛНЕНИЯ =====
function openDepositModal() {
    const modal = document.getElementById('deposit-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeDepositModal() {
    const modal = document.getElementById('deposit-modal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function setDepositAmount(amount) {
    const input = document.getElementById('deposit-amount');
    if (input) {
        input.value = amount;
        input.focus();
    }
}

function processDeposit() {
    const input = document.getElementById('deposit-amount');
    const amount = parseFloat(input.value);
    
    if (!amount || amount <= 0) {
        showNotification('Введите корректную сумму', 'error');
        return;
    }
    
    if (amount > 100000) {
        showNotification('Максимальная сумма: 100 000 ₽', 'error');
        return;
    }
    
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        showNotification('Ошибка безопасности: CSRF токен не найден', 'error');
        return;
    }
    
    const baseUrl = window.location.origin;
    const sitePath = window.location.pathname.includes('/UnionCase/') ? '/UnionCase' : '';
    
    fetch(baseUrl + sitePath + '/ajax/deposit.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            amount: amount,
            csrf_token: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Баланс пополнен на ' + formatPrice(amount), 'success');
            updateBalance(data.balance);
            closeDepositModal();
        } else {
            showNotification(data.error || 'Ошибка пополнения', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Ошибка соединения с сервером', 'error');
    });
}

// ===== ОТКРЫТИЕ КЕЙСА =====
function openCase(caseId) {
    if (isSpinning) {
        console.log('Already spinning');
        return;
    }
    
    console.log('Opening case:', caseId);
    
    const btn = document.querySelector('.case-open-btn-large');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Вращаем...';
    }
    
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        showNotification('Ошибка безопасности: CSRF токен не найден', 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-unlock"></i> Открыть кейс';
        }
        return;
    }
    
    const baseUrl = window.location.origin;
    const sitePath = window.location.pathname.includes('/UnionCase/') ? '/UnionCase' : '';
    
    fetch(baseUrl + sitePath + '/ajax/open_case.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            case_id: caseId,
            csrf_token: csrfToken
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        
        if (data.success) {
            wonItem = data.item;
            console.log('Won item:', wonItem);
            
            // Запускаем анимацию с полученными предметами
            startRouletteAnimation(data.items);
            
            // Обновляем баланс
            updateBalance(data.balance);
            
            showNotification('Крутим!', 'info');
        } else {
            showNotification(data.error || 'Ошибка открытия кейса', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-unlock"></i> Открыть кейс';
            }
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        showNotification('Ошибка соединения с сервером', 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-unlock"></i> Открыть кейс';
        }
    });
}

// ===== АНИМАЦИЯ РУЛЕТКИ =====
function startRouletteAnimation(items) {
    console.log('Starting animation with', items.length, 'items');
    
    const track = document.getElementById('roulette-track');
    if (!track) {
        console.error('Roulette track not found!');
        return;
    }
    
    isSpinning = true;
    spinPosition = 0;
    spinDuration = 0;
    spinSpeed = 40; // Начальная скорость
    
    track.innerHTML = '';
    track.style.transition = 'none';
    track.style.transform = 'translateX(0)';
    
    // Создаем много предметов для анимации
    let itemsHtml = '';
    for (let i = 0; i < 40; i++) {
        items.forEach(item => {
            itemsHtml += `
                <div class="roulette-item" data-item-id="${item.id}" data-item-price="${item.price}">
                    ${item.image ? `<img src="${getImageUrl(item.image)}" alt="${item.name}">` : '<i class="fas fa-gift"></i>'}
                    <span>${item.name}</span>
                </div>
            `;
        });
    }
    track.innerHTML = itemsHtml;
    
    console.log('Track filled with', track.children.length, 'items');
    
    // Запускаем анимацию
    if (spinInterval) {
        clearInterval(spinInterval);
    }
    
    const startTime = Date.now();
    
    spinInterval = setInterval(() => {
        const elapsed = Date.now() - startTime;
        
        if (elapsed < SPIN_TIME) {
            // Быстрое вращение
            spinSpeed = 40;
        } else if (elapsed < SPIN_TIME + SLOW_DOWN_TIME) {
            // Замедление
            const slowdown = (elapsed - SPIN_TIME) / SLOW_DOWN_TIME;
            spinSpeed = 40 * (1 - slowdown * 0.8); // Плавно уменьшаем скорость до 8
        } else {
            // Останавливаем
            clearInterval(spinInterval);
            spinInterval = null;
            stopRoulette();
            return;
        }
        
        spinPosition += spinSpeed;
        
        // Если достигли конца, начинаем сначала
        if (spinPosition >= track.scrollWidth - track.parentElement.offsetWidth) {
            spinPosition = 0;
        }
        
        track.style.transform = `translateX(-${spinPosition}px)`;
    }, 16);
    
    console.log('Animation started');
}

function stopRoulette() {
    console.log('Stopping roulette automatically');
    
    if (!isSpinning || !wonItem) {
        console.log('Cannot stop: isSpinning=', isSpinning, 'wonItem=', wonItem);
        return;
    }
    
    isSpinning = false;
    
    const track = document.getElementById('roulette-track');
    const items = document.querySelectorAll('.roulette-item');
    
    if (!track || !items.length) {
        console.error('Track or items not found');
        return;
    }
    
    // Находим текущую позицию
    const currentTransform = track.style.transform;
    let currentPosition = 0;
    if (currentTransform) {
        const match = currentTransform.match(/translateX\(-([0-9.]+)px\)/);
        if (match) {
            currentPosition = parseFloat(match[1]);
        }
    }
    
    const containerWidth = track.parentElement.offsetWidth;
    const itemWidth = 108; // ширина + gap
    
    // Рассчитываем, какой предмет сейчас по центру под указателем
    const centerPosition = currentPosition + (containerWidth / 2);
    const currentIndex = Math.floor(centerPosition / itemWidth);
    
    console.log('Current position:', currentPosition);
    console.log('Center position:', centerPosition);
    console.log('Current index:', currentIndex);
    
    // Получаем ID предмета, на котором остановились
    let actualItemId = null;
    if (items[currentIndex]) {
        actualItemId = items[currentIndex].dataset.itemId;
        console.log('Actual item at stop:', actualItemId, 'Expected item:', wonItem.id);
    }
    
    // Если предмет не совпадает с выигрышным, корректируем позицию
    if (actualItemId != wonItem.id) {
        console.log('Items do not match, correcting position...');
        
        // Ищем ближайший выигрышный предмет
        let targetIndex = -1;
        let minDistance = Infinity;
        
        for (let i = 0; i < items.length; i++) {
            if (items[i].dataset.itemId == wonItem.id) {
                const itemCenter = i * itemWidth + (itemWidth / 2);
                const distance = Math.abs(itemCenter - centerPosition);
                if (distance < minDistance) {
                    minDistance = distance;
                    targetIndex = i;
                }
            }
        }
        
        if (targetIndex !== -1) {
            console.log('Correcting to index:', targetIndex);
            
            // Рассчитываем целевую позицию
            const targetPosition = targetIndex * itemWidth - (containerWidth / 2) + (itemWidth / 2);
            
            track.style.transition = 'transform 0.3s cubic-bezier(0.2, 0.9, 0.3, 1)';
            track.style.transform = `translateX(-${targetPosition}px)`;
            
            // Обновляем индекс для подсветки
            setTimeout(() => {
                items.forEach(item => item.classList.remove('winning'));
                if (items[targetIndex]) {
                    items[targetIndex].classList.add('winning');
                }
                
                // Показываем результат
                setTimeout(() => {
                    showResult(wonItem);
                }, 500);
            }, 300);
        } else {
            // Если не нашли, просто подсвечиваем текущий
            items.forEach(item => item.classList.remove('winning'));
            if (items[currentIndex]) {
                items[currentIndex].classList.add('winning');
            }
            
            setTimeout(() => {
                showResult(wonItem);
            }, 500);
        }
    } else {
        // Предметы совпадают - отлично!
        items.forEach(item => item.classList.remove('winning'));
        if (items[currentIndex]) {
            items[currentIndex].classList.add('winning');
        }
        
        setTimeout(() => {
            showResult(wonItem);
        }, 500);
    }
    
    // Восстанавливаем кнопку
    setTimeout(() => {
        const btn = document.querySelector('.case-open-btn-large');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-unlock"></i> Открыть кейс';
        }
    }, 1500);
}

// ===== МОДАЛЬНОЕ ОКНО РЕЗУЛЬТАТА =====
function showResult(item) {
    console.log('Showing result for:', item);
    
    const modal = document.getElementById('result-modal');
    const content = document.getElementById('result-content');
    
    if (!modal || !content) return;
    
    content.innerHTML = `
        <div class="result-item">
            <div class="item-rarity" style="color: ${item.color || getRarityColor(item.rarity)}">
                ${getRarityName(item.rarity)}
            </div>
            <div class="item-image" style="--color: ${item.color || getRarityColor(item.rarity)}">
                ${item.image ? `<img src="${getImageUrl(item.image)}" alt="${item.name}">` : '<i class="fas fa-gift"></i>'}
            </div>
            <div class="item-name">${item.name}</div>
            <div class="item-price">${formatPrice(item.price)}</div>
            <div class="result-actions">
                <button class="btn btn-primary" onclick="closeResultModal()">
                    <i class="fas fa-check"></i> Отлично!
                </button>
                <a href="/UnionCase/profile.php?tab=inventory" class="btn btn-outline">
                    <i class="fas fa-archive"></i> В инвентарь
                </a>
            </div>
        </div>
    `;
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeResultModal() {
    const modal = document.getElementById('result-modal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// ===== ФИЛЬТР КЕЙСОВ =====
function initCaseFilter() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const caseCards = document.querySelectorAll('.case-card');
    
    if (!filterBtns.length || !caseCards.length) return;
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.dataset.filter;
            
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            caseCards.forEach(card => {
                if (filter === 'all' || card.dataset.marketplace === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
}

// ===== ПРЕДПРОСМОТР АВАТАРА =====
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatar-preview');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'inline-block';
                preview.style.width = '60px';
                preview.style.height = '60px';
                preview.style.borderRadius = '50%';
                preview.style.objectFit = 'cover';
                preview.style.marginLeft = '10px';
                preview.style.border = '2px solid var(--accent-primary)';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ===== ПОКАЗ/СКРЫТИЕ ПАРОЛЯ =====
function togglePassword(id) {
    const input = document.getElementById(id);
    const icon = document.getElementById(id + '-icon');
    
    if (!input || !icon) return;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// ===== ИНДИКАТОР СИЛЫ ПАРОЛЯ =====
function updatePasswordStrength(password) {
    const bar = document.getElementById('password-strength');
    if (!bar) return;
    
    let strength = 0;
    if (password.length >= 6) strength++;
    if (password.length >= 10) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    const labels = ['', 'Слабый', 'Слабый', 'Средний', 'Хороший', 'Отличный'];
    const colors = ['', '#ff4444', '#ff8800', '#ffcc00', '#88cc00', '#00cc44'];
    
    if (password) {
        bar.innerHTML = `
            <div class="strength-bar" style="width: ${strength * 20}%; background: ${colors[strength]}"></div>
            <span style="color: ${colors[strength]}">${labels[strength]}</span>
        `;
    } else {
        bar.innerHTML = '';
    }
}

// ===== МАССОВАЯ ПРОДАЖА =====
function sellAllItems() {
    const form = document.getElementById('sell-all-form');
    if (!form) return;
    
    const totalElement = document.getElementById('sell-all-total');
    const total = totalElement ? totalElement.value : '0';
    
    if (confirm('Продать все предметы из инвентаря? Сумма: примерно ' + formatPrice(parseFloat(total)))) {
        form.submit();
    }
}

// ===== ИНИЦИАЛИЗАЦИЯ ПРИ ЗАГРУЗКЕ =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing...');
    
    initCaseFilter();
    
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            updatePasswordStrength(this.value);
        });
    }
    
    document.querySelectorAll('.flash-message').forEach(msg => {
        setTimeout(() => {
            msg.classList.add('hiding');
            setTimeout(() => msg.remove(), 400);
        }, 4000);
    });
    
    // Предзагрузка предметов для анимации
    if (typeof caseItems !== 'undefined' && caseItems && caseItems.length > 0) {
        console.log('Preloading case items:', caseItems.length);
        const track = document.getElementById('roulette-track');
        if (track) {
            let itemsHtml = '';
            for (let i = 0; i < 20; i++) {
                caseItems.forEach(item => {
                    itemsHtml += `
                        <div class="roulette-item" data-item-id="${item.id}">
                            ${item.image ? `<img src="${getImageUrl(item.image)}" alt="${item.name}">` : '<i class="fas fa-gift"></i>'}
                            <span>${item.name}</span>
                        </div>
                    `;
                });
            }
            track.innerHTML = itemsHtml;
            console.log('Track preloaded');
        }
    }
    
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                if (this.id === 'deposit-modal') closeDepositModal();
                if (this.id === 'result-modal') closeResultModal();
            }
        });
    });
});

// Закрытие модалок по Esc
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDepositModal();
        closeResultModal();
    }
});
