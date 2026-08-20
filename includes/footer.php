</main>

<footer>

Sistema GenesisBar 1.0

<br><br>

<?php

echo date("d/m/Y");

echo " | ";

echo date("H:i:s");

?>

</footer>

<?php
if (!empty($extra_js) && is_array($extra_js)) {
    foreach ($extra_js as $js) {
?>
<script src="<?= htmlspecialchars($js); ?>"></script>
<?php
    }
}
?>

</body>

</html>
