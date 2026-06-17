// StarPad — JavaScript global
// ==========================================

// Toggle tema claro/escuro
function toggleTheme() {
    const html = document.documentElement;
    const body = document.body;
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    html.setAttribute('data-theme', newTheme);
    body.className = newTheme + '-theme';
    
    // Salvar em cookie (30 dias)
    document.cookie = 'theme=' + newTheme + ';path=/;max-age=' + (60*60*24*30) + ';SameSite=Lax';
}

// Toggle menu mobile
function toggleMobileMenu() {
    const nav = document.getElementById('mobileNav');
    nav.classList.toggle('show');
    nav.style.display = nav.classList.contains('show') ? 'flex' : 'none';
}

// Sistema de estrelas interativas
function initStarRating(containerId, hiddenInputId) {
    const container = document.getElementById(containerId);
    const hiddenInput = document.getElementById(hiddenInputId);
    if (!container || !hiddenInput) return;
    
    const stars = container.querySelectorAll('.star');
    const currentRating = parseInt(hiddenInput.value) || 0;
    
    function updateStars(rating) {
        stars.forEach((star, index) => {
            star.classList.toggle('selected', index < rating);
        });
    }
    
    updateStars(currentRating);
    
    stars.forEach((star, index) => {
        star.addEventListener('mouseenter', () => updateStars(index + 1));
        star.addEventListener('click', () => {
            hiddenInput.value = index + 1;
            updateStars(index + 1);
        });
    });
    
    container.addEventListener('mouseleave', () => {
        updateStars(parseInt(hiddenInput.value) || 0);
    });
}

// Contador de caracteres
function updateCharCount(textareaId, counterId, maxChars) {
    const textarea = document.getElementById(textareaId);
    const counter = document.getElementById(counterId);
    if (!textarea || !counter) return;
    
    textarea.addEventListener('input', () => {
        const remaining = maxChars - textarea.value.length;
        counter.textContent = textarea.value.length + '/' + maxChars + ' caracteres';
        counter.style.color = remaining < 100 ? 'var(--danger)' : 'var(--text-muted)';
    });
}

// Revelar spoiler
function revealSpoiler(element) {
    element.classList.toggle('revealed');
    if (element.classList.contains('revealed')) {
        element.style.filter = 'blur(0)';
        element.style.cursor = 'default';
    } else {
        element.style.filter = 'blur(8px)';
        element.style.cursor = 'pointer';
    }
}

// Votar em review (like/dislike) via AJAX
function voteReview(reviewId, voteType) {
    fetch('api.php?action=vote', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'review_id=' + reviewId + '&vote_type=' + voteType
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Atualizar contadores na página
            const likeBtn = document.getElementById('like-btn-' + reviewId);
            const dislikeBtn = document.getElementById('dislike-btn-' + reviewId);
            const likeCount = document.getElementById('like-count-' + reviewId);
            const dislikeCount = document.getElementById('dislike-count-' + reviewId);
            
            if (likeCount) likeCount.textContent = data.likes;
            if (dislikeCount) dislikeCount.textContent = data.dislikes;
            
            // Atualizar classes dos botões
            if (likeBtn) {
                likeBtn.classList.remove('voted-like', 'voted-dislike');
                if (data.user_vote === 'like') likeBtn.classList.add('voted-like');
            }
            if (dislikeBtn) {
                dislikeBtn.classList.remove('voted-like', 'voted-dislike');
                if (data.user_vote === 'dislike') dislikeBtn.classList.add('voted-dislike');
            }
        } else {
            alert(data.message || 'Erro ao votar.');
        }
    })
    .catch(err => console.error('Erro:', err));
}

// Inicializar ao carregar a página
document.addEventListener('DOMContentLoaded', () => {
    // Inicializar estrelas se existir o container
    if (document.getElementById('star-rating-container')) {
        initStarRating('star-rating-container', 'rating-input');
    }
    
    // Inicializar contador de caracteres
    if (document.getElementById('review-text')) {
        updateCharCount('review-text', 'char-count', 2000);
    }
    
    // Fechar menu mobile ao clicar em links
    document.querySelectorAll('.mobile-nav a').forEach(link => {
        link.addEventListener('click', () => {
            const nav = document.getElementById('mobileNav');
            if (nav) {
                nav.classList.remove('show');
                nav.style.display = 'none';
            }
        });
    });
});