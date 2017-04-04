<?php

ini_set('display_errors', 'On');
ini_set('max_execution_time', 500000000);

class GenerateSQLliteFile
{

    public $data = "";

    const DB_SERVER = "localhost";
    const DB_USER = "root";
    const DB_PASSWORD = "";
    const DB = "hadithbd";

    private $db = NULL;
    private $SqlLiteDB=NULL;

    public function __construct()
    {
        error_reporting(-1);
        $this->dbConnect();
    }

    private function dbConnect(){
        $this->db = mysql_connect(self::DB_SERVER,self::DB_USER,self::DB_PASSWORD) or die(mysql_error());
        if($this->db)
            mysql_select_db(self::DB,$this->db);

        mysql_query("SET NAMES 'utf8'", $this->db);
    }

    public function MySQLQuery($query)
    {
        return mysql_query($query,$this->db);
    }

    public function SQLLiteQuery($query)
    {
        $this->SqlLiteDB->exec($query);
    }

}

class LogManager
{

    protected static $LOG_FILE_PATH = "D://xampp//htdocs//bangla_hadith_android_sqlite_generate_php//db_book.sql";

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
        file_put_contents(self::$LOG_FILE_PATH,$raw_string . "\n\n" , FILE_APPEND);
    }
	
	public static function setLogFilePath($log_file_name)
	{
		self::$LOG_FILE_PATH = "D://xampp//htdocs//bangla_hadith_android_sqlite_generate_php//db_book".$log_file_name.".sql";
	}
}


//$book_id = 2;

for ($book_id = 40; $book_id <= 47; $book_id++)
{
    $Generation = new GenerateSQLliteFile;

	LogManager::setLogFilePath(''.$book_id);

	LogManager::saveRawLog('BEGIN TRANSACTION;');

	LogManager::saveRawLog('CREATE TABLE IF NOT EXISTS "android_metadata" ("locale" TEXT);INSERT INTO android_metadata VALUES("en_US");');
	LogManager::saveRawLog('INSERT INTO android_metadata VALUES("en_US");');

	LogManager::saveRawLog('DROP TABLE IF EXISTS `section`;');
	LogManager::saveRawLog('CREATE TABLE section ("id" INTEGER, "name" INTEGER, "total_content" INTEGER, PRIMARY KEY("id"));');

	LogManager::saveRawLog('DROP TABLE IF EXISTS `content`;');
	LogManager::saveRawLog('CREATE TABLE `content` ("id" INTEGER, "section_id" INTEGER, PRIMARY KEY("id"));');

	LogManager::saveRawLog('DROP TABLE IF EXISTS `content_fts`;');
	LogManager::saveRawLog('CREATE VIRTUAL TABLE "content_fts" USING fts4 ("question" TEXT,"answer" TEXT,"note" TEXT);');


	$SqlQuery=$Generation->MySQLQuery("SELECT book_section.secID AS id, book_section.SectionName AS name, ( SELECT COUNT(books_content.contentID) AS total_content FROM books_content WHERE books_content.sectionID = book_section.secID ) AS total_content FROM book_section WHERE book_section.BookID = ".$book_id." AND book_section.sectionActive = 1");
	while ($row = mysql_fetch_array($SqlQuery))
	{
		LogManager::saveRawLog("INSERT INTO section ('id', 'name', 'total_content') VALUES (".$row['id'].", '".strip_tags(htmlentities($row['name'], ENT_QUOTES))."', ".$row['total_content'].");");
	}


	$SqlQuery=$Generation->MySQLQuery("SELECT books_content.contentID AS id, books_content.sectionID as section_id, books_content.MainQ as question, books_content.MainA as answer, IFNULL(books_content.Mnote,".'"'."".'"'.") as note FROM books_content WHERE books_content.bookID = ".$book_id." AND books_content.active = 1");
	while ($row = mysql_fetch_array($SqlQuery))
	{

		LogManager::saveRawLog("INSERT INTO content ('id', 'section_id') VALUES (".$row['id'].", ".$row['section_id'].");");

		LogManager::saveRawLog("INSERT INTO content_fts ('docid', 'question', 'answer', 'note') VALUES (".$row['id'].", '".strip_tags(htmlentities($row['question'], ENT_QUOTES))."', '".strip_tags(htmlentities($row['answer'], ENT_QUOTES))."', '".strip_tags(htmlentities($row['note'], ENT_QUOTES))."');");

	}

	LogManager::saveRawLog('COMMIT;');

	$output = shell_exec('D:/xampp/htdocs/bangla_hadith_android_sqlite_generate_php/sqlite3/sqlite3 "D:\xampp\htdocs\bangla_hadith_android_sqlite_generate_php\ob_'.$book_id.'.db" -init "D:\xampp\htdocs\bangla_hadith_android_sqlite_generate_php\db_book'.$book_id.'.sql"');
	echo "<pre>$output</pre>";
} 

?>