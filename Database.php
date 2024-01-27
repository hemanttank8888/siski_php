<?php

class DatabaseHandler
{
    private $conn;

    public function __construct($servername, $username, $password, $database)
    {
        $this->conn = new mysqli($servername, $username, $password, $database);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function createTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS machine (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            productName VARCHAR(255) NOT NULL,
            rating VARCHAR(255) NOT NULL,
            revievername VARCHAR(255) NOT NULL,
            reviever_message VARCHAR(255)
        )";

        if ($this->conn->query($sql) === TRUE) {
            echo "Table created successfully<br>";
        } else {
            echo "Error creating table: " . $this->conn->error;
        }
    }

    public function insertData($dataList)
    {
        foreach ($dataList as $data) {
            $productName = $this->sanitize($data['productName']);
            if ($productName === ""){
                continue;
            }
            $rating = $this->sanitize($data['rating']);

            foreach ($data['all_reviewer'] as $variant) {
                $revievername = $this->sanitize($variant['revievername']);
                $reviever_message = $this->sanitize($variant['reviever_message']);

    
                // Use prepared statement for SELECT query
                $stmtSelect = $this->conn->prepare("SELECT * FROM machine WHERE productName = ? AND revievername = ? AND reviever_message = ?");
                $stmtSelect->bind_param("sss", $productName, $revievername, $reviever_message);
                $stmtSelect->execute();
                $existingRecord = $stmtSelect->get_result()->fetch_assoc();
                $stmtSelect->close();
    
                if ($existingRecord) {
                    // Update existing record
                    $stmtUpdate = $this->conn->prepare("
                        UPDATE machine
                        SET rating = ?
                        WHERE productName = ? AND revievername = ? AND reviever_message = ?
                    ");
                    $stmtUpdate->bind_param("ssss", $rating, $productName, $revievername, $reviever_message);
                    $stmtUpdate->execute();
                    $stmtUpdate->close();
                } else {
                    // Insert new record
                    $stmtInsert = $this->conn->prepare("
                        INSERT INTO machine (productName, rating, revievername, reviever_message) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmtInsert->bind_param("ssss", $productName, $rating, $revievername, $reviever_message);
                    $stmtInsert->execute();
                    $stmtInsert->close();
                }
            }
        }
    }

    private function sanitize($input)
    {
        // Implement your own data sanitization logic here if needed
        return $input;
    }

    public function closeConnection()
    {
        $this->conn->close();
    }
}


?>
