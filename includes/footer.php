<footer>
    &copy; <?= date('Y') ?> <strong>IMBS Green Campus</strong> &nbsp;|&nbsp; CIT1074 – Web Application Development Project &nbsp;|&nbsp; DIT 93
</footer>

<script>
(function () {
    var storedTheme = localStorage.getItem('uniportal-theme');
    if (storedTheme === 'dark') {
        document.body.classList.add('dark-mode');
    }

    var toggle = document.getElementById('themeToggle');
    if (toggle) {
        toggle.textContent = document.body.classList.contains('dark-mode') ? 'Light Mode' : 'Dark Mode';
        toggle.addEventListener('click', function () {
            document.body.classList.toggle('dark-mode');
            var enabled = document.body.classList.contains('dark-mode');
            localStorage.setItem('uniportal-theme', enabled ? 'dark' : 'light');
            toggle.textContent = enabled ? 'Light Mode' : 'Dark Mode';
        });
    }
})();
</script>

</body>
</html>
