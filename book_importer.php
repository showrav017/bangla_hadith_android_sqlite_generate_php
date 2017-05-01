<?php

ini_set('display_errors', 'On');
ini_set('max_execution_time', 500000000);

class MySqlCOnnection
{

    public $data = "";

    const DB_SERVER = "localhost";
    const DB_USER = "root";
    const DB_PASSWORD = "";
    const DB = "hadithbd";

    private $db = NULL;

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

        //mysql_query("SET NAMES 'utf8'", $this->db);
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
	
	public function __destruct()
	{
		$this->db->close();
	}
}

class LogManager
{

    protected static $LOG_FILE_PATH = "D://xampp//htdocs//bangla_hadith_android_sqlite_generate_php//import.sql";

    public static function error($LOG_STRING)
    {
        self::saveLog("<span style='color: #ff0000'>ERROR</span>: ".$LOG_STRING);
    }

    public static function info($LOG_STRING)
    {
        self::saveLog("INFO : ".$LOG_STRING);
    }

    public static function success($LOG_STRING)
    {
        self::saveLog("<span style='color: green'>SUCCESS</span>: ".$LOG_STRING);
    }

    private function saveLog($html_message)
    {
        file_put_contents(self::$LOG_FILE_PATH,date("Y-m-d H:i:s") . " == " .  $html_message . "\n" , FILE_APPEND);
    }

    public static function saveRawLog($raw_string)
    {
        file_put_contents(self::$LOG_FILE_PATH,$raw_string, FILE_APPEND);
    }
	
	public static function setLogFilePath($log_file_name)
	{
		self::$LOG_FILE_PATH = "D://xampp//htdocs//bangla_hadith_android_sqlite_generate_php//db_hadith_".$log_file_name.".sql";
	}
}

LogManager::saveRawLog("DROP TABLE IF EXISTS `books`;CREATE TABLE 'books' ( 'id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 'hadithbd_id' INTEGER, 'book_name' TEXT, 'book_type' TEXT, 'device_book_version' TEXT, 'server_book_version' TEXT, 'current_book_size_in_bytes' TEXT, 'book_category_id' INTEGER, 'meta_data' TEXT, 'sort_priority' INTEGER, 'download_status' INTEGER, 'if_downloading' INTEGER );DROP TABLE IF EXISTS ob_categories;CREATE TABLE ob_categories ('id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 'name' TEXT, 'total_books' INTEGER);");

$mysqlConnect = new MySqlCOnnection();


$SQL = "SELECT hadithbook.BookID AS hadithbd_id, hadithbook.BookNameBD AS book_name, 'hb' AS book_type, '1' AS book_version, '0' AS book_category_id, CONCAT( '{hadithsource_info:{bangla:".'"'."', ( HTML_Encode ( IFNULL(( SELECT hadithsource.SourceNameBD FROM hadithsource WHERE hadithsource.SourceID = hadithbook.PubID ), ".'"'."".'"'." ))), '".'"'.", english:".'"'."', ( HTML_Encode ( IFNULL(( SELECT hadithsource.SourceNameEN FROM hadithsource WHERE hadithsource.SourceID = hadithbook.PubID ), ".'"'."".'"'." ))), '".'"'."}', ', no_of_sections:', ( IFNULL(( SELECT COUNT(*) AS total FROM hadithsection WHERE hadithsection.BookID = hadithbook.BookID ), 0 )), ', no_of_hadith:', ( IFNULL(( SELECT COUNT(*) AS total FROM hadithmain WHERE hadithmain.BookID = hadithbook.BookID ), 0 )), '}' ) AS meta_data, hadithbook.priority AS sort_priority, '0' AS download_status FROM hadithbook WHERE hadithbook.Active = 1";

$SQL .= " UNION ALL ";

$SQL .= "SELECT books_name.bookID AS hadithbd_id, books_name.Book_nameBD AS book_name, 'ob' AS book_type, '1' AS book_version, booktype AS book_category_id, CONCAT( '{writer_name:".'"'."', HTML_Encode ( IFNULL(( SELECT book_writter.writter_nameBN FROM book_writter WHERE book_writter.wrID = books_name.writterID ), ".'"'."".'"'." )), '".'"'.", no_of_section:', ( HTML_Encode ( IFNULL(( SELECT COUNT(*) FROM book_section WHERE book_section.BookID = books_name.bookID ), ".'"'."".'"'." ))), ', no_of_content:', ( HTML_Encode ( IFNULL(( SELECT COUNT(*) FROM books_content WHERE books_content.bookID = books_name.bookID ), ".'"'."".'"'." ))), '}' ) AS meta_data, 0 AS sort_priority, '1' AS download_status FROM books_name WHERE books_name.Active = 1";


$SqlQuery=$mysqlConnect->MySQLQuery($SQL);

while ($row = $SqlQuery->fetch_assoc())
{
    LogManager::saveRawLog("INSERT INTO books (`hadithbd_id`, `book_name`, `book_type`, `device_book_version`, `server_book_version`, `current_book_size_in_bytes`, `book_category_id`, `meta_data`, `sort_priority`, `download_status`, `if_downloading`) VALUES ('".$row['hadithbd_id']."', '".$row['book_name']."', '".$row['book_type']."', '".$row['book_version']."', '".$row['book_version']."', '51879936', '".$row['book_category_id']."', '".$row['meta_data']."', '".$row['sort_priority']."', '".$row['download_status']."', 0);");
}

$SqlQuery=$mysqlConnect->MySQLQuery("SELECT books_type.btypeID as id, books_type.bookCat as name, IFNULL(( SELECT COUNT(*) AS total FROM books_name WHERE books_name.booktype = books_type.btypeID ), 0 ) as total_books FROM books_type");

while ($row = $SqlQuery->fetch_assoc())
{
    LogManager::saveRawLog("INSERT INTO ob_categories (id, name, total_books) VALUES ('".$row['id']."', '".$row['name']."', ".$row['total_books'].");");
}

echo "Done";