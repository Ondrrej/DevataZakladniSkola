// inicializace slideru
let swiper = new Swiper(".mySwiper", {
    spaceBetween: 30,
    centeredSlides: true,
    autoplay: {
        delay: 5000,
        disableOnInteraction: false,
    },
    pagination: {
        el: ".swiper-pagination",
        clickable: true,
    },
    navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
    },
});

// Funkce pro mobilní menu
function toggleMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    mobileMenu.classList.toggle('active');
}

// Funkce pro mobilní dropdown (rozbalovací) menu
function setupMobileDropdowns() {
    const mobileDropdownToggles = document.querySelectorAll('.mobile-dropdown-toggle');
    
    mobileDropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.parentElement;
            parent.classList.toggle('active');
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Nastavení mobilních dropdownů
    setupMobileDropdowns();
    
    // Načtení blog příspěvků
    loadInitialPosts();
    
    // Načtení galerie
    loadGallery();
    
    // Ovládání modálního okna
    setupModalHandling();
});

//Načtení prvních příspěvků
async function loadInitialPosts() {
    try {
        const response = await fetch('fetch_posts.php?page=1');
        if (response.ok) {
            const html = await response.text();
            document.getElementById('blog-posts').innerHTML = html;
            
            // Nastavení event listenerů pro odkazy "Číst více"
            setupReadMoreLinks();
            
            // Nastavení event listeneru pro tlačítko "Načíst další"
            setupLoadMoreButton();
        } else {
            document.getElementById('blog-posts').innerHTML = 
                '<p>Omlouváme se, při načítání příspěvků došlo k chybě.</p>';
        }
    } catch {
        document.getElementById('blog-posts').innerHTML = 
            '<p>Omlouváme se, při načítání příspěvků došlo k chybě.</p>';
    }
}

// Funkce pro nastavení odkazů "Číst více"
function setupReadMoreLinks() {
    let readMoreLinks = document.querySelectorAll('.read-more a');
    readMoreLinks.forEach(function(link) {
        link.addEventListener('click', async function(e) {
            e.preventDefault();
            let postId = this.getAttribute('data-id');
            
            try {
                const response = await fetch('get_post.php?id=' + postId);
                if (response.ok) {
                    const data = await response.text();
                    document.getElementById('modal-body').innerHTML = data;
                    document.getElementById('myModal').style.display = "block";
                } else {
                    document.getElementById('modal-body').innerHTML = '<p>Omlouváme se, při načítání příspěvku došlo k chybě.</p>';
                    document.getElementById('myModal').style.display = "block";
                }
            } catch {
                document.getElementById('modal-body').innerHTML = '<p>Omlouváme se, při načítání příspěvku došlo k chybě.</p>';
                document.getElementById('myModal').style.display = "block";
            }
        });
    });
}

// Funkce nastavení tlačítka "Načíst další"
function setupLoadMoreButton() {
    const loadMoreBtn = document.getElementById('load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', async function() {
            // Deaktivace tlačítka během načítání
            this.disabled = true;
            this.textContent = 'Načítání...';
            
            const nextPage = this.getAttribute('data-next-page');
            
            try {
                const response = await fetch('fetch_posts.php?page=' + nextPage);
                if (response.ok) {
                    const html = await response.text();
                    
                    // Odstranění tlačítka "Načíst další"
                    const loadMoreContainer = document.querySelector('.load-more-container');
                    if (loadMoreContainer) {
                        loadMoreContainer.remove();
                    }

                    // Vytvoření dočasného elementu 
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    
                    // Získání všech příspěvků z odpovědi
                    const newPosts = tempDiv.querySelectorAll('.post');
                    
                    // Kontejneru posts-grid
                    const postsGrid = document.querySelector('.posts-grid');
                    
                    // Přidání nových příspěvků
                    newPosts.forEach(post => {
                        postsGrid.appendChild(post);
                    });
                    
                    // Kontrola jestli existují další příspěvky
                    const loadMoreData = tempDiv.querySelector('.load-more-data');
                    const hasMore = loadMoreData ? (loadMoreData.getAttribute('data-has-more') === 'true') : false;
                    const nextPageNumber = loadMoreData ? loadMoreData.getAttribute('data-next-page') : null;
                    
                    // Pokud ano přidá se nové tlačítko "Načíst další"
                    if (hasMore && nextPageNumber) {
                        const newLoadMoreBtn = document.createElement('div');
                        newLoadMoreBtn.className = 'load-more-container';
                        newLoadMoreBtn.innerHTML = '<button id="load-more-btn" class="load-more-btn" data-next-page="' + nextPageNumber + '">Načíst další příspěvky</button>';
                        document.getElementById('blog-posts').appendChild(newLoadMoreBtn);
                        
                        // Znovu inicializace event listeneru pro nové tlačítko
                        setupLoadMoreButton();
                    }
                    
                    // Nastavení event listenerů pro nové příspěvky
                    setupReadMoreLinks();
                } else {
                    this.disabled = false;
                    this.textContent = 'Zkusit znovu';
                }
            } catch {
                this.disabled = false;
                this.textContent = 'Zkusit znovu';
            }
        });
    }
}

// Načtení galerie
async function loadGallery() {
    try {
        const response = await fetch('fetch_gallery.php');
        if (response.ok) {
            const html = await response.text();
            document.getElementById('gallery-photos').innerHTML = html;

            document.querySelectorAll('#gallery-photos .gallery-item').forEach(function(item) {
                item.addEventListener('click', function() {
                    const imgElement = this.querySelector('img');
                    const src = imgElement.getAttribute('src');
                    const title = this.getAttribute('data-title');
                    const description = this.getAttribute('data-description');

                    let modalContent = '<img src="' + src + '" alt="' + title + '">';
                    modalContent += '<h2 style="margin: 0 0 10px 0;">' + title + '</h2>';
                    modalContent += '<p style="margin: 0;">' + description + '</p>';
                    document.getElementById('modal-body').innerHTML = modalContent;
                    document.getElementById('myModal').style.display = "block";
                });
            });
        } else {
            document.getElementById('gallery-photos').innerHTML = '<p>Galerie není dostupná.</p>';
        }
    } catch {
        document.getElementById('gallery-photos').innerHTML = '<p>Galerie není dostupná.</p>';
    }
}

// Funkce pro nastavení ovládání modálního okna
function setupModalHandling() {
    let modal = document.getElementById('myModal');
    let span = document.getElementsByClassName("modal-close")[0];

    span.onclick = function() {
        modal.style.display = "none";
    };

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    };
}