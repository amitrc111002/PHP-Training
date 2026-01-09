<!DOCTYPE html>
<html> 
<head>
    <title>Database Connection</title>
</head>
<body>
    <?php
    $host = "localhost";
    $dbname = "mydb";
    $username = "myuser";
    $password = "1234";

    try
    {
        $conn = new PDO("mysql:host=$host;dbname=$dbname",$username,$password);
        $conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        echo "Connected Successfully";
    }
    catch(PDOException $e)
    {
        echo "Connection failed: " . $e->getMessage();
    }
    ?>
</body>
</html>