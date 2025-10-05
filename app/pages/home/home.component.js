// Home Component - Economía Circular Canarias
class HomeComponent {
    constructor() {
        this.cssLoaded = false;
    }
    render() {
        // Devuelve un contenedor, el HTML se inyecta en afterRender
        return '<div class="home-component"></div>';
    }
    async afterRender() {
        // Cargar CSS solo una vez
        if (!this.cssLoaded) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'app/pages/home/home.component.css';
            document.head.appendChild(link);
            this.cssLoaded = true;
        }
        // Cargar HTML de forma asíncrona
        const container = document.querySelector('.home-component');
        if (container) {
            try {
                const response = await fetch('app/pages/home/home.component.html');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const html = await response.text();
                container.innerHTML = html;
                
                // Esperar a que el HTML esté en el DOM antes de inicializar lógica
                setTimeout(() => {
                    this.initializeNavigation();
                    this.animateStats();
                    this.initCanariasSlider();
                }, 350);
            } catch (e) {
                console.error('Error cargando home component:', e);
                container.innerHTML = `
                    <div style="padding: 40px; text-align: center;">
                        <h2>❌ Error cargando la página principal</h2>
                        <p>${e.message}</p>
                        <button onclick="window.location.reload()" style="margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            🔄 Recargar página
                        </button>
                    </div>
                `;
            }
        } else {
            console.error('Container .home-component no encontrado');
        }
    }
    initCanariasSlider() {
        const slider = document.getElementById('canariasSlider');
        if (!slider) return;
        
        const track = slider.querySelector('.slider-track');
        const prevBtn = document.getElementById('sliderPrevBtn');
        const nextBtn = document.getElementById('sliderNextBtn');
        const dotsContainer = document.getElementById('sliderDots');
        
        if (!track || !prevBtn || !nextBtn || !dotsContainer) return;
        
        const slides = Array.from(track.children);
        if (slides.length === 0) return;
        
        // Ajustar ancho igual al bloque hero-section
        const heroCard = document.querySelector('.hero-section .card');
        if (heroCard) {
            slider.style.maxWidth = getComputedStyle(heroCard).maxWidth || '600px';
            slider.style.width = getComputedStyle(heroCard).width;
        }
        
        let current = 0;
        // Autoplay cada 3 segundos
        let autoplay = setInterval(() => {
            current = (current + 1) % slides.length;
            updateSlider();
        }, 3000);
        // Pausar autoplay al interactuar
        [prevBtn, nextBtn, dotsContainer, track].forEach(el => {
            if (!el) return;
            el.addEventListener('mouseenter', () => clearInterval(autoplay));
            el.addEventListener('mouseleave', () => {
                autoplay = setInterval(() => {
                    current = (current + 1) % slides.length;
                    updateSlider();
                }, 3000);
            });
        });
        // Crear dots
        dotsContainer.innerHTML = '';
        slides.forEach((_, i) => {
            const dot = document.createElement('span');
            dot.className = 'slider-dot' + (i === 0 ? ' active' : '');
            dot.addEventListener('click', () => goToSlide(i));
            dotsContainer.appendChild(dot);
        });
        function updateSlider() {
            track.style.transform = `translateX(-${current * 100}%)`;
            dotsContainer.querySelectorAll('.slider-dot').forEach((dot, i) => {
                dot.classList.toggle('active', i === current);
            });
        }
        function goToSlide(idx) {
            current = idx;
            updateSlider();
        }
        prevBtn.onclick = () => {
            current = (current - 1 + slides.length) % slides.length;
            updateSlider();
        };
        nextBtn.onclick = () => {
            current = (current + 1) % slides.length;
            updateSlider();
        };
        // Swipe para móvil
        let startX = null;
        track.addEventListener('touchstart', e => {
            startX = e.touches[0].clientX;
        });
        track.addEventListener('touchend', e => {
            if (startX === null) return;
            const dx = e.changedTouches[0].clientX - startX;
            if (dx > 50) prevBtn.click();
            if (dx < -50) nextBtn.click();
            startX = null;
        });
        // Inicializar
        updateSlider();
    }
    initializeNavigation() {
        const element = this.getElement();
        if (!element) return;
        
        const navLinks = element.querySelectorAll('[data-navigate]');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const route = link.getAttribute('data-navigate');
                window.appRouter.navigate(route);
            });
        });
    }
    animateStats() {
        const element = this.getElement();
        if (!element) return;
        
        const statsNumbers = element.querySelectorAll('.stats-section h3');
        statsNumbers.forEach((stat, index) => {
            setTimeout(() => {
                stat.style.transform = 'scale(1.1)';
                stat.style.transition = 'transform 0.5s ease';
                setTimeout(() => {
                    stat.style.transform = 'scale(1)';
                }, 500);
            }, index * 200);
        });
    }
    getElement() {
        return document.querySelector('.home-component');
    }
}
// Exportar el componente
window.HomeComponent = HomeComponent;
