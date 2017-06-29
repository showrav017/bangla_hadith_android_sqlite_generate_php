<?php

ini_set('display_errors', 'On');
ini_set('max_execution_time', 500000000);

class GenerateSQLliteFile
{

    public $data = "";

    const DB_SERVER = "localhost";
    const DB_USER = "root";
    const DB_PASSWORD = "";
    const DB = "hadithbd_bkp";

    private $db = NULL;
    private $SqlLiteDB=NULL;

    public function __construct()
    {
        error_reporting(-1);
        $this->dbConnect();
    }

    private function dbConnect(){
        $this->db = new mysqli(self::DB_SERVER,self::DB_USER,self::DB_PASSWORD, self::DB);
        if ($this->db->connect_errno) {
            echo "Sorry, this website is experiencing problems.";

            // Something you should not do on a public site, but this example will show you
            // anyways, is print out MySQL error related information -- you might log this
            echo "Error: Failed to make a MySQL connection, here is why: \n";
            echo "Errno: " . $this->db->connect_errno . "\n";
            echo "Error: " . $this->db->connect_error . "\n";

            // You might want to show them something nice, but we will simply exit
            exit;
        }

        $this->db->query("SET NAMES 'utf8'");
    }

    public function MySQLQuery($query)
    {
        if (!$result = $this->db->query($query)) {
            // Oh no! The query failed.
            echo "Sorry, the website is experiencing problems.";

            // Again, do not do this on a public site, but we'll show you how
            // to get the error information
            echo "Error: Our query failed to execute and here is why: \n";
            echo "Query: " . $query . "\n";
            echo "Errno: " . $this->db->errno . "\n";
            echo "Error: " . $this->db->error . "\n";
            exit;
        }

        return $result;
    }

    public function SQLLiteQuery($query)
    {
        $this->SqlLiteDB->exec($query);
    }

    public function __destruct()
    {
        $this->db->close();
    }

}

$Generation = new GenerateSQLliteFile;

$SqlQuery=$Generation->MySQLQuery("SELECT hadithbook.BookID AS hadithbd_id, hadithbook.BookNameBD AS book_name, 'hb' AS book_type, '1' AS book_version, '0' AS book_category_id, CONCAT( '{hadithsource_info:{bangla:".'"'."', ( HTML_Encode ( IFNULL(( SELECT hadithsource.SourceNameBD FROM hadithsource WHERE hadithsource.SourceID = hadithbook.PubID ), ".'"'."".'"'." ))), '".'"'.", english:".'"'."', ( HTML_Encode ( IFNULL(( SELECT hadithsource.SourceNameEN FROM hadithsource WHERE hadithsource.SourceID = hadithbook.PubID ), ".'"'."".'"'." ))), '".'"'."}', ', no_of_sections:', ( IFNULL(( SELECT COUNT(*) AS total FROM hadithsection WHERE hadithsection.BookID = hadithbook.BookID ), 0 )), ', no_of_hadith:', ( IFNULL(( SELECT COUNT(*) AS total FROM hadithmain WHERE hadithmain.BookID = hadithbook.BookID ), 0 )), '}' ) AS meta_data, hadithbook.priority AS sort_priority, '1' AS download_status FROM hadithbook WHERE hadithbook.Active = 1");
while ($row = $SqlQuery->fetch_assoc())
{
    echo "INSERT INTO `hadithbd_apk`.`books_info` (`hadithbd_book_id`, `book_name`, `book_type`, `book_category_id`, `book_sort_priority`, `current_version`, `meta_data`) VALUES ('".$row['hadithbd_id']."', '".$row['book_name']."', '".$row['book_type']."', ".$row['book_category_id'].", ".$row['sort_priority'].", '".$row['book_version']."', '".$row['meta_data']."');";

}

$SqlQuery=$Generation->MySQLQuery("SELECT books_name.bookID AS hadithbd_id, books_name.Book_nameBD AS book_name, 'ob' AS book_type, '1' AS book_version, booktype AS book_category_id, CONCAT( '{writer_name:".'"'."', HTML_Encode ( IFNULL(( SELECT book_writter.writter_nameBN FROM book_writter WHERE book_writter.wrID = books_name.writterID ), ".'"'."".'"'." )), '".'"'.", no_of_section:', ( HTML_Encode ( IFNULL(( SELECT COUNT(*) FROM book_section WHERE book_section.BookID = books_name.bookID ), ".'"'."".'"'." ))), ', no_of_content:', ( HTML_Encode ( IFNULL(( SELECT COUNT(*) FROM books_content WHERE books_content.bookID = books_name.bookID ), ".'"'."".'"'." ))), '}' ) AS meta_data, 0 AS sort_priority, '1' AS download_status FROM books_name WHERE books_name.Active = 1");
while ($row = $SqlQuery->fetch_assoc())
{
    echo "INSERT INTO `hadithbd_apk`.`books_info` (`hadithbd_book_id`, `book_name`, `book_type`, `book_category_id`, `book_sort_priority`, `current_version`, `meta_data`) VALUES ('".$row['hadithbd_id']."', '".$row['book_name']."', '".$row['book_type']."', ".$row['book_category_id'].", ".$row['sort_priority'].", '".$row['book_version']."', '".$row['meta_data']."');";

}