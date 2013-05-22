<?php
echo "<div id='loginbar'>";
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
}


// Käyttäjä on kirjautunut sisään
if (isset($_SESSION['Authenticated'])) {
    echo "<p>" . localize('Kirjautuneena sisään') . " <br/><strong>" . $_SESSION['yhtionNimi'] . " / " . $_SESSION['kayttaja'] . "</strong></p>";
    echo "<p><a href=index.php?sivu=login&amp;logout=1>" . localize('Kirjaudu ulos') . "</a></p>";
}
// Käyttäjä ei ole kirjautunut sisään
else {
    echo "<p>" . localize('Et ole kirjautuneena sisään') . "</p>";
    echo "<p><a href=index.php>" . localize('Kirjaudu sisään') . "</a></p>";
}
?>
</div> <!-- /loginbar -->
