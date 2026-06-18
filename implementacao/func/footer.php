</main><!-- .main-content -->

<footer class="main-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <span class="logo-icon">✩</span><strong>StarPad</strong>
            <p>Plataforma de reviews de jogos eletrônicos. Inspirada no Letterboxd.</p>
        </div>
        <div class="footer-links">
            <h4>Navegação</h4>
            <a href="<?= SITE_URL ?>/index.php">Início</a>
            <a href="<?= SITE_URL ?>/catalog.php">Catálogo</a>
            <?php if (isLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/my_lists.php">Minhas Listas</a>
            <?php endif; ?>
        </div>
        <div class="footer-credits">
            <p>© 2026 StarPad — Trabalho Final</p>
            <p>Carls Co.</p>
            <p style="font-size:0.8rem;opacity:0.7;">Tecnologia: HTML, CSS, JavaScript, PHP & MySQL (XAMPP)</p>
        </div>
    </div>
</footer>

<script src="<?= SITE_URL ?>/script.js"></script>
</body>
</html>