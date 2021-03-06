<?php

$page = null;
$pages = null;
$pageReference = null;
$defaultPage = "me.php";

function getPageDetailsOfMultiPage($pages, $defaultPage, $path)
{
    $pageRequest = $_GET["page"] ?? $defaultPage;

    $isValid = $pages[$pageRequest] ?? null;
    $page = null;
    if ($isValid) {
        $page = $pages[$pageRequest];
        $page["request"] = $pageRequest;

        $base = basename($path, ".php");
        $file = "$base/${pageRequest}.php";
        $page["file"] = $file;
    }

    return $page;
}

// Session variables to make sure they are 'up to date'.
$dbChk = $_SESSION["dbChk"] ?? null;
$_SESSION["dbChk"] = $dbChk;

$res = $_SESSION["res"] ?? null;
$_SESSION["res"] = $res;

function connectToDatabase($dsn)
{
    // Enable verbose output of error (or include from config.php)
    error_reporting(-1);            // Report all type of errors.
    ini_set("display_errors", 1);   // Display all errors.

    // Open the database file and catch the exception if it fails.
    try {
        $db = new PDO($dsn);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo "Failed to connect to the database using DSN:<br>$dsn<br>";
        throw $e;
    }

    // Making sure the Session variables are kept 'up to date'.
    $dbChk = 0;
    $_SESSION["dbChk"] = $dbChk;

    return $db;
};

function sessionDestroy()
{
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
};

function printJettyResultsetToHTMLTable($res)
{
    $thead = null;
    if (isset($res[0]) && is_array($res[0])) {
        $thead = array_keys($res[0]);
        $thead = array_map("ucfirst", $thead);
    }

    ?>
    <?php if ($thead) : ?>
    <table>
        <tr>
        <?php foreach ($thead as $val) : ?>
            <th><?= $val ?></th>
        <?php endforeach; ?>
        </tr>

        <?php
        $i = 1;
        foreach ($res as $row) :
            ?>
            <tr>
                <?php foreach ($row as $val) : ?>
                    <td><a href="../me6/jetty.php?page=b&search=<?= $i ?>"><?= $val ?></a></td>
                <?php endforeach; ?>
                </tr>
            <?php
            $i = $i + 1;
        endforeach;
        ?>
    </table>
    <?php endif;
}

function printJettySearch()
{

    // Get incoming from search form
    $search = isset($_GET['search'])
        ? $_GET['search']
        : null;

    ?>
    <form>
        <fieldset>
            <input type="hidden" name="page" value="b">
            <input type="search" name="search" placeholder="Enter a number, use % as wildcard." value="<?=htmlentities($search)?>">
            <input type="submit" value="Search">
        </fieldset>
    </form>

    <?php
    // Break script if empty $search
    if (is_null($search)) {
        exit("<p>Nothing to display, please enter a searchstring.");
    }

    $connectedDB = connectToDatabase($_SESSION["dsnJetty"]);

    // Prepare the SQL statement
    $sql = "SELECT * FROM jetty WHERE position LIKE ?";
    $stmt = $connectedDB->prepare($sql);
    echo "<p>Preparing the SQL-statement:<br><code>$sql</code><p>";

    // Execute the SQL statement using parameters to replace the ?
    $params = [$search];
    $stmt->execute($params);
    echo "<p>Executing using parameters:<br><pre>" . htmlentities(print_r($params, true)) . "</pre>";

    // Get the results as an array with column names as array keys
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>The result contains " . count($res) . " rows.</p>";

    // Loop through the array and gather the data into table rows
    $rows = null;
    foreach ($res as $row) {
        $rows .= "<tr>";
        $rows .= "<td>{$row['position']}</td>";
        $rows .= "<td>{$row['boatType']}</td>";
        $rows .= "<td>{$row['boatEngine']}</td>";
        $rows .= "<td>{$row['boatLength']}</td>";
        $rows .= "<td>{$row['boatWidth']}</td>";
        $rows .= "<td>{$row['ownerName']}</td>";
        $rows .= "</tr>\n";
    }

    // Print out the result as a HTML table using PHP heredoc
    echo <<<EOD
    <table>
    <tr>
        <th>postion</th>
        <th>boatType</th>
        <th>boatEngine</th>
        <th>boatLength</th>
        <th>boatWidth</th>
        <th>ownerName</th>
    </tr>
    $rows
    </table>
    <p><a href="../me6/jetty.php?page=b">Clear search results</a>.</p>
    EOD;
}

function printDinosaur()
{

    $db = connectToDatabase($_SESSION["dsnDinosaur"]);

    // Prepare and execute the SQL statement.
    $stmt = $db->prepare("SELECT * FROM dinosaurs");
    $stmt->execute();

    // Get the results as an array with column names as array keys
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $thead = null;
    if (isset($res[0]) && is_array($res[0])) {
        $thead = array_keys($res[0]);
        $thead = array_map("ucfirst", $thead);
    }

    ?>
    <?php if ($thead) : ?>
    <table>
        <tr>
        <?php foreach ($thead as $val) : ?>
            <th><?= $val ?></th>
        <?php endforeach; ?>
        </tr>

        <?php
        foreach ($res as $row) :
            ?>
            <tr>
                <?php foreach ($row as $val) : ?>
                    <td><?= $val ?></td>
                <?php endforeach; ?>
                </tr>
            <?php
        endforeach;
        ?>
    </table>
    <?php endif;
}

function searchDinosaur()
{
    // Get incoming from search form
    $search = isset($_GET['search'])
    ? $_GET['search']
    : null;

    if (isset($_GET['data'])) {
        printDinosaur();
    }

    ?>
    <form action="" method="GET">
        <input type="submit" name="data" value="All Data">
    </form>
    <form>
    <fieldset>
        <input type="hidden" name="page" value="b">
        <input type="search" name="search" placeholder="Enter a number, use % as wildcard." value="<?=htmlentities($search)?>">
        <input type="submit" value="Search">
    </fieldset>
    </form>

    <?php
    // Break script if empty $search
    if (is_null($search)) {
        exit("<p>Nothing to display, please enter a searchstring.");
    }

    $connectedDB = connectToDatabase($_SESSION["dsnDinosaur"]);

    // Prepare the SQL statement
    $sql = "SELECT * FROM Dinosaurs WHERE position LIKE ? OR latin LIKE ? OR diet LIKE ? OR leng LIKE ? OR wgt LIKE ?";
    $stmt = $connectedDB->prepare($sql);
    echo "<p>Preparing the SQL-statement:<br><code>$sql</code><p>";

    // Execute the SQL statement using parameters to replace the ?
    $params = [$search, $search, $search, $search, $search];
    $stmt->execute($params);
    echo "<p>Executing using parameters:<br><pre>" . htmlentities(print_r($params, true)) . "</pre>";

    // Get the results as an array with column names as array keys
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>The result contains " . count($res) . " rows.</p>";

    // Loop through the array and gather the data into table rows
    $rows = null;
    foreach ($res as $row) {
        $rows .= "<tr>";
        $rows .= "<td>{$row['position']}</td>";
        $rows .= "<td>{$row['latin']}</td>";
        $rows .= "<td>{$row['diet']}</td>";
        $rows .= "<td>{$row['leng']}</td>";
        $rows .= "<td>{$row['wgt']}</td>";
        $rows .= "</tr>\n";
    }

    // Print out the result as a HTML table using PHP heredoc
    echo <<<EOD
    <table>
    <tr>
    <th>Index</th>
    <th>Name</th>
    <th>Diet</th>
    <th>Length</th>
    <th>Weight</th>
    </tr>
    $rows
    </table>
    <p><a href="../me6/search.php">Clear search results</a></p>
    EOD;
}

function createDino()
{

    // Get incoming from search form
    $latin = isset($_POST['latin'])
    ? $_POST['latin']
    : null;
    $diet = isset($_POST['diet'])
    ? $_POST['diet']
    : null;
    $leng = isset($_POST['leng'])
    ? $_POST['leng']
    : null;
    $wgt = isset($_POST['wgt'])
    ? $_POST['wgt']
    : null;
    $position = null;

    // Get incoming from create form
    if (isset($_POST['submit'])) {
        $position = null;
        $latin = $_POST['latin'];
        $diet = $_POST['diet'];
        $wgt = $_POST['wgt'];
        $leng = $_POST['leng'];
    }

    ?>
    <form action="" method="POST">
    <fieldset>
        <input type="text" name="latin" placeholder="Latin" value="<?=htmlentities($latin)?>"><br>
        <input type="text" name="diet" placeholder="Diet" value="<?=htmlentities($diet)?>"><br>
        <input type="number" name="leng" placeholder="Length (meters)" value="<?=htmlentities($leng)?>"><br>
        <input type="number" name="wgt" placeholder="Weight (Kilograms)" value="<?=htmlentities($wgt)?>"><br>
        <input type="submit" name="submit">
    </fieldset>
    </form>
    <?php

    // Break script if empty $search
    if (is_null($latin)) {
        exit("<p>You must enter the Name of the dinosaur.");
    }

    $connectedDB = connectToDatabase($_SESSION["dsnDinosaur"]);

    // Prepare the SQL statement
    $sql = "INSERT INTO dinosaurs VALUES (?, ?, ?, ?, ?)";
    $stmt = $connectedDB->prepare($sql);
    echo "<p>Preparing the SQL-statement:<br><code>$sql</code><p>";

    // Execute the SQL statement using parameters to replace the ?
    $params = [$position, $latin, $diet, $leng, $wgt];
    // Execute the SQL to INSERT within a try-catch to catch any errors.
    try {
        $stmt->execute($params);
    } catch (PDOException $e) {
        echo "<p>Failed to insert a new row, dumping details for debug.</p>";
        echo "<p>Incoming \$_POST:<pre>" . print_r($_POST, true) . "</pre>";
        echo "<p>The error code: " . $stmt->errorCode();
        echo "<p>The error message:<pre>" . print_r($stmt->errorInfo(), true) . "</pre>";
        throw $e;
    }
    $url = "admin.php";
    header("Location: $url");
}

function deleteDinoTable()
{
    // Get incoming from Delete form
    $dinoPosDel = isset($_GET['dinoPosDel'])
        ? $_GET['dinoPosDel']
        : null;

    ?>
    <p>Enter the position of the entry you wish to delete.</p>
    <form>
        <fieldset>
            <input type="hidden" name="page" value="delete">
            <input type="search" name="dinoPosDel" placeholder="Enter the position." value="<?=htmlentities($dinoPosDel)?>">
            <input type="submit" value="Delete">
        </fieldset>
    </form>

    <?php
    // Break script if empty $search
    if (is_null($dinoPosDel)) {
        exit("<p>Nothing to display, please enter a searchstring.");
    }

    $connectedDB = connectToDatabase($_SESSION["dsnDinosaur"]);

    // Prepare SQL statement to DELETE a row in the table
    $sql = <<<EOD
    DELETE FROM dinosaurs
    WHERE
        position = $dinoPosDel
    EOD;
    $stmt = $connectedDB->prepare($sql);
    echo "<p>Preparing the SQL-statement:<br><code>$sql</code><p>";

    // Execute the SQL to INSERT within a try-catch to catch any errors.
    try {
        $stmt->execute();
        $url = "admin.php";
        header("Location: $url");
    } catch (PDOException $e) {
        echo "<p>Failed to insert a new row, dumping details for debug.</p>";
        echo "<p>Incoming \$_POST:<pre>" . print_r($_POST, true) . "</pre>";
        echo "<p>The error code: " . $stmt->errorCode();
        echo "<p>The error message:<pre>" . print_r($stmt->errorInfo(), true) . "</pre>";
        throw $e;
    }
}
