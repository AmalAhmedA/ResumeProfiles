<?php
require_once "pdo.php";
require_once "util.php";
require_once "head.php";
session_start();

if (!isset($_SESSION["user_id"])) {
    die("Not logged in");
}

if ( isset($_POST['cancel'])){
    // Redirect the browser to index.php
    header('Location:index.php');
    return;
}

if (isset($_POST["add"])) {
    // Input validation 
    // call the functions in position.js for validation 
    $position_validate = validatePos();
    $education_validate = validateEdu();

    if ($position_validate !== true) {
        $_SESSION["error"] = $position_validate;
        header("Location:add.php");
        return;
    }

    if ($education_validate !== true) {
        $_SESSION["error"] = $education_validate;
        header("Location:add.php");
        return;
    }

    if (strlen($_POST["first_name"]) < 1
        || strlen($_POST["last_name"]) < 1
        || strlen($_POST["email"]) < 1
        || strlen($_POST["headline"]) < 1
        || strlen($_POST["summary"]) < 1
    ) 

    {
        $_SESSION["error"] = "All fields are required";
        header('Location:add.php');
        return;
    }

    if (strpos($_POST["email"], "@") === false) {
        $_SESSION["error"] = "Email address must contain @";
        header('Location:add.php');
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO Profile
        (user_id, first_name, last_name, email, headline, summary)
        VALUES ( :uid, :fn, :ln, :em, :he, :su)'
    );

    $stmt->execute(
        array(
            ':uid' => $_SESSION['user_id'],
            ':fn' => $_POST['first_name'],
            ':ln' => $_POST['last_name'],
            ':em' => $_POST['email'],
            ':he' => $_POST['headline'],
            ':su' => $_POST['summary']
        )
    );

    $profile_id = $pdo->lastInsertId();
    $rank = 1;
    for ($i = 1; $i <= 9; $i++) {
        if (! isset($_POST['year'.$i]) ) {
            continue;
        }
        if (! isset($_POST['desc'.$i]) ) {
            continue;
        }
        
        $year = $_POST["year" . $i];
        $desc = $_POST["desc" . $i];
        
        $stmt = $pdo->prepare(
            'INSERT INTO position
            (profile_id, rank, year, description)
            VALUES ( :pid, :rank, :year, :desc)'
        );
       
        $stmt->execute(
            array(
            ':pid' => $profile_id,
            ':rank' => $rank,
            ':year' => $year,
            ':desc' => $desc)
        );
        
        $rank++;
    }

    $rank = 1;
   
    for ($i = 1; $i <= 9; $i++) {

        if (! isset($_POST['edu_year'.$i]) ) {
            continue;
        }
        
        if (! isset($_POST['edu_school'.$i]) ) {
            continue;
        }
        
        $year = $_POST['edu_year' . $i];
        $stmt = $pdo->prepare(
            "SELECT institution_id 
            FROM institution 
            WHERE name = :edu_school"
        );
        
        $stmt->execute(array(':edu_school' => $_POST["edu_school" .$i]));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row !== false) {
            $instid = $row["institution_id"];
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO institution 
                (name)
                VALUES (:school_name)"
            );

            $stmt->execute(array(':school_name' => $_POST["edu_school" .$i]));
            $instid = $pdo->lastInsertId();

        }

        $stmt = $pdo->prepare(
            'INSERT INTO education
            (profile_id, institution_id, rank, year)
            VALUES ( :pid, :instid, :rank, :year)'
        );

        $stmt->execute(
            array(
            ':pid' => $profile_id,
            ':rank' => $rank,
            ':year' => $year,
            ':instid' => $instid)
        );

        $rank++;
    }

    $_SESSION["success"] = "Profile added";
    header('Location:index.php');
    return;
}
?>
// The view 
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Amal Ahmed Profile Add</title>
</head>
<body>
    <div class="container">
        <h1>Adding Profile for <?php echo htmlentities($_SESSION["name"]); ?></h1>
        
        <?php
        if (isset($_SESSION["error"])) {
            echo('<p style="color: red;">'. $_SESSION["error"]);
            unset($_SESSION["error"]);
        }
        ?>
        <form method="post">
            <p>First Name:
                <input type="text" name="first_name" size="60"/>
            </p>
            <p>Last Name:
                <input type="text" name="last_name" size="60"/>
            </p>
            <p>Email:
                <input type="text" name="email" size="30"/>
            </p>
            <p>Headline:<br/>
                <input type="text" name="headline" size="80"/>
            </p>
            <p>Summary:<br/>
                <textarea name="summary" rows="8" cols="80"></textarea>
            </p>
            <p>
                <label>Education:</label>
                <input type="button" value="+" id="plus_education" class="plus_button">
                <br>
                <div id="edu_fields"></div>
            </p>
            <p>
                <label>Position:</label>
                <input type="button" value="+" id="plus_position" class="plus_button">
            </p>
            <br>
            <div id="position_fields"></div>
            <input type="submit" name="add" value="Add">
            <input type="submit" name="cancel" value="Cancel">
        </form>
    </div>
    <script type="text/javascript" src="js/position.js"></script>
</body>
</html>