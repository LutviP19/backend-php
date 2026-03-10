    </main>
    <footer class="page-footer blue darken-3">
        <div class="container">
            <div class="row">
                <div class="col l6 s12">
                    <h5 class="white-text"><?= config('app.name'); ?></h5>
                    <p class="grey-text text-lighten-4">Developed by <a href="https://www.github.com/lutvip19" class="white-text">LutviP19</a></p>
                </div>
            </div>
        </div>
        <div class="footer-copyright">
            <div class="container">
                <h6 class="center">© <?= date('Y') ?> LutviP19</h6>
            </div>
        </div>
    </footer>
    <script src="<?= assets('/js/materialize.min.js') ?>"></script>
    <script>
        M.AutoInit();
        // Flash Message
        <?php if (session("status")): ?>
            M.toast({html: "<span><?= flash("status") ?></span>"});
        <?php endif ?>
    </script>
</body>
</html>