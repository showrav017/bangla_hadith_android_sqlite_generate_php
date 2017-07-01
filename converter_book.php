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

class LogManager
{

    protected static $LOG_FILE_PATH = "F://xampp//htdocs//bangla_hadith_android_sqlite_generate_php//db_book.sql";

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
		self::$LOG_FILE_PATH = "F://xampp//htdocs//bangla_hadith_android_sqlite_generate_php//db_book".$log_file_name.".sql";
	}
}

$BookIDs = array();

$Gen = new GenerateSQLliteFile;
$SqlQuery=$Gen->MySQLQuery("SELECT books_name.bookID FROM books_name WHERE books_name.Active = 1");

while ($row = $SqlQuery->fetch_assoc())
{
	$BookIDs[] = $row['bookID'];
}
	

//$bookIDs = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,24,23,25,26,27,28,29,30,31,32,33,39,35,36,37,38,40,41,42,43,44,45,46,47);

//$book_id = 2;


for ($iPointer = 0; $iPointer < count($BookIDs); $iPointer++)
{
	
	$book_id = $BookIDs[$iPointer];
	
    $Generation = new GenerateSQLliteFile;

	LogManager::setLogFilePath(''.$book_id);

	LogManager::saveRawLog('BEGIN TRANSACTION;');

	LogManager::saveRawLog('CREATE TABLE IF NOT EXISTS "android_metadata" ("locale" TEXT);INSERT INTO android_metadata VALUES("en_US");');
	LogManager::saveRawLog('INSERT INTO android_metadata VALUES("en_US");');

	LogManager::saveRawLog('DROP TABLE IF EXISTS `section`;');
	LogManager::saveRawLog('CREATE TABLE section ("id" INTEGER, "name" INTEGER, "total_content" INTEGER, PRIMARY KEY("id"));');

	LogManager::saveRawLog('DROP TABLE IF EXISTS `content`;');
	LogManager::saveRawLog('CREATE TABLE `content` ("id" INTEGER, "sequence" INTEGER, "section_id" INTEGER, PRIMARY KEY("id"));');

	LogManager::saveRawLog('DROP TABLE IF EXISTS `content_fts`;');
	LogManager::saveRawLog('CREATE VIRTUAL TABLE "content_fts" USING fts4 ("question" TEXT,"answer" TEXT,"note" TEXT);');


	$SqlQuery=$Generation->MySQLQuery("SELECT book_section.secID AS id, book_section.SectionName AS name, ( SELECT COUNT(books_content.contentID) AS total_content FROM books_content WHERE books_content.sectionID = book_section.secID ) AS total_content FROM book_section WHERE book_section.BookID = ".$book_id." AND book_section.sectionActive = 1");
	while ($row = $SqlQuery->fetch_assoc())
	{
		LogManager::saveRawLog("INSERT INTO section ('id', 'name', 'total_content') VALUES (".$row['id'].", '".strip_tags(htmlentities($row['name'], ENT_QUOTES))."', ".$row['total_content'].");");
	}

	$seq = 0;
	$sectionID = 0;
	
	$SqlQuery=$Generation->MySQLQuery("SELECT books_content.contentID AS id, books_content.sectionID as section_id, books_content.MainQ as question, books_content.MainA as answer, IFNULL(books_content.Mnote,".'"'."".'"'.") as note FROM books_content WHERE books_content.bookID = ".$book_id." AND books_content.active = 1 ORDER BY sectionID ASC");
	while ($row = $SqlQuery->fetch_assoc())
	{
		
		if($sectionID != intval($row['section_id']))
		{
			$sectionID = intval($row['section_id']);
			$seq = 0;
		}

		LogManager::saveRawLog("INSERT INTO content ('id', 'sequence', 'section_id') VALUES (".$row['id'].", ".$seq.", ".$row['section_id'].");");

		LogManager::saveRawLog("INSERT INTO content_fts ('docid', 'question', 'answer', 'note') VALUES (".$row['id'].", '".strip_tags(htmlentities($row['question'], ENT_QUOTES))."', '".strip_tags(htmlentities($row['answer'], ENT_QUOTES))."', '".strip_tags(htmlentities($row['note'], ENT_QUOTES))."');");
		
		$seq = $seq + 1;

	}

	LogManager::saveRawLog('COMMIT;');

	$output = shell_exec('F:/xampp/htdocs/bangla_hadith_android_sqlite_generate_php/sqlite3/sqlite3 "F:\xampp\htdocs\bangla_hadith_android_sqlite_generate_php\ob_'.$book_id.'.db" -init "F:\xampp\htdocs\bangla_hadith_android_sqlite_generate_php\db_book'.$book_id.'.sql" && "C:/Program Files/7-Zip/7z.exe" a -tzip ob_'.$book_id.'.zip "F:/xampp/htdocs/bangla_hadith_android_sqlite_generate_php/ob_'.$book_id.'.db"');
	echo "<pre>$output</pre>";
} 

?>