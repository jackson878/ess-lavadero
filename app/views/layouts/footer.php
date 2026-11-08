    </main>
</div>

<!-- Bootstrap (incluye Popper para menús desplegables y el offcanvas) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Funciones comunes: búsqueda en tablas y confirmaciones -->
<script src="<?= recurso('js/app.js') ?>"></script>
<?php if (!empty($scripts)): ?>
    <?php foreach ($scripts as $script): ?>
        <script src="<?= e($script) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
