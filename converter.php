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
    protected static $LOG_FILE_PATH = "F://php_applications//bangla_hadith//db_conversion//db.sql";

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
}

$book_id = 1;

$Generation = new GenerateSQLliteFile;

LogManager::saveRawLog('BEGIN TRANSACTION;');

LogManager::saveRawLog('CREATE TABLE IF NOT EXISTS "android_metadata" ("locale" TEXT);INSERT INTO android_metadata VALUES("en_US");');
LogManager::saveRawLog('INSERT INTO android_metadata VALUES("en_US");');

LogManager::saveRawLog('DROP TABLE IF EXISTS `section`;');
LogManager::saveRawLog('CREATE TABLE section ("id" INTEGER, "serial" INTEGER, "nameEnglish" VARCHAR,"nameBengali" VARCHAR, "hadith_number" INTEGER, "range_start" INTEGER, "range_end" INTEGER, PRIMARY KEY("id"));');

LogManager::saveRawLog('DROP TABLE IF EXISTS `chapter`;');
LogManager::saveRawLog('CREATE TABLE "chapter" ("id" INTEGER, "nameEnglish" VARCHAR,"nameBengali" VARCHAR, "nameArabic" VARCHAR, "sectionId" INTEGER,"hadith_number" INTEGER, PRIMARY KEY("id"));');

LogManager::saveRawLog('DROP TABLE IF EXISTS `explanation`;');
LogManager::saveRawLog('CREATE TABLE "explanation" ("id" INTEGER, "hadithId" INTEGER, "explanation" TEXT, PRIMARY KEY("id"));');

LogManager::saveRawLog('DROP TABLE IF EXISTS `content`;');
LogManager::saveRawLog('CREATE TABLE `content` ("id" INTEGER, "sequence" INTEGER, "statusId" INTEGER, "if_cross_checked" INTEGER, "chapterId" INTEGER, "sectionId" INTEGER, "hadithNo" INTEGER, PRIMARY KEY("id"));');

LogManager::saveRawLog('DROP TABLE IF EXISTS `content_fts`;');
LogManager::saveRawLog('CREATE VIRTUAL TABLE "content_fts" USING fts4 ("hadithBengali" TEXT,"hadithEnglish" TEXT,"hadithArabic" TEXT,"note" TEXT,"rabiNameBangla" TEXT,"rabiNameEnglish" TEXT,"publisherNameEnglish" TEXT,"publisherNameBangla" TEXT,"status_bn" TEXT,"status_en" TEXT);');


$SqlQuery=$Generation->MySQLQuery("SELECT hadithsection.SectionID AS id, hadithsection.serial, hadithsection.SectionBD AS nameBengali, hadithsection.SectionEN AS nameEnglish, ( SELECT COUNT(*) FROM hadithmain WHERE hadithmain.SectionID = hadithsection.SectionID ) AS hadith_number, ( SELECT min(hadithmain.HadithNo) FROM hadithmain WHERE hadithmain.SectionID = hadithsection.SectionID AND hadithmain.BookID = ".$book_id." ) AS range_start, ( SELECT max(hadithmain.HadithNo) FROM hadithmain WHERE hadithmain.SectionID = hadithsection.SectionID AND hadithmain.BookID = ".$book_id." ) AS range_end FROM hadithsection WHERE hadithsection.BookID = ".$book_id." AND hadithsection.SecActive = 1");
while ($row = mysql_fetch_array($SqlQuery))
{
    LogManager::saveRawLog("INSERT INTO section ('id', 'serial', 'nameEnglish', 'nameBengali', 'hadith_number', 'range_start', 'range_end') VALUES (".$row['id'].", ".$row['serial'].", '".strip_tags(htmlentities($row['nameEnglish'], ENT_QUOTES))."', '".strip_tags(htmlentities($row['nameBengali'], ENT_QUOTES))."', ".$row['hadith_number'].", ".$row['range_start'].", ".$row['range_end'].");");

}


$SqlQuery=$Generation->MySQLQuery("SELECT hadithchapter.chapID AS id, IFNULL(hadithchapter.ChapterBG, ".'"'."".'"'.") AS nameBengali, IFNULL(hadithchapter.ChapterEN, ".'"'."".'"'.") AS nameEnglish, IFNULL(hadithchapter.ChapterAR, ".'"'."".'"'.") AS nameArabic, IFNULL(hadithchapter.SectionID, ".'"'."".'"'.") AS sectionId, ( SELECT COUNT(*) FROM hadithmain WHERE hadithmain.chapterID = hadithchapter.chapID ) AS hadith_number FROM hadithchapter WHERE hadithchapter.StatusActive = 1 AND hadithchapter.BookID = ".$book_id);
while ($row = mysql_fetch_array($SqlQuery))
{
    LogManager::saveRawLog("INSERT INTO chapter ('id', 'nameEnglish', 'nameBengali', 'nameArabic', 'sectionId', 'hadith_number') VALUES (".$row['id'].", '".strip_tags(htmlentities($row['nameEnglish'], ENT_QUOTES))."', '".strip_tags(htmlentities($row['nameBengali'], ENT_QUOTES))."', '".strip_tags(htmlentities($row['nameArabic'], ENT_QUOTES))."', ".$row['sectionId'].", ".$row['hadith_number'].");");

}


$SqlQuery=$Generation->MySQLQuery("SELECT hadithexplanation.expID AS id, hadithexplanation.hadithID AS hadithId, hadithexplanation.explanation AS explanation FROM `hadithexplanation` WHERE hadithexplanation.active = 1 AND FIND_IN_SET( hadithexplanation.hadithID, ( SELECT GROUP_CONCAT( hadithmain.HadithID SEPARATOR ',' ) AS hadithIDList FROM hadithmain WHERE hadithmain.BookID = ".$book_id." ))");
while ($row = mysql_fetch_array($SqlQuery))
{
    LogManager::saveRawLog("INSERT INTO explanation (id,hadithId,explanation) VALUES (".$row['id'].",".$row['hadithId'].",'".strip_tags(htmlentities($row['explanation'], ENT_QUOTES))."');");

}

$SqlQuery=$Generation->MySQLQuery("SELECT hadithmain.HadithID AS id, (@cnt := @cnt + 1) AS sequence, hadithmain.HadithStatus AS statusId, hadithmain.CheckStatus AS if_cross_checked, hadithmain.chapterID AS chapterId, hadithmain.SectionID AS sectionId, hadithmain.HadithNo AS hadithNo, hadithmain.BanglaHadith AS hadithBengali, hadithmain.EnglishHadith AS hadithEnglish, hadithmain.ArabicHadith AS hadithArabic, hadithmain.HadithNote AS note, ( SELECT rabihadith.rabiBangla FROM rabihadith WHERE rabihadith.rabiID = hadithmain.RabiID ) AS rabiNameBangla, ( SELECT rabihadith.rabiEnglish FROM rabihadith WHERE rabihadith.rabiID = hadithmain.RabiID ) AS rabiNameEnglish, ( SELECT hadithsource.SourceNameEN FROM hadithsource WHERE hadithsource.SourceID = hadithmain.SourceID ) AS publisherNameEnglish, ( SELECT hadithsource.SourceNameBD FROM hadithsource WHERE hadithsource.SourceID = hadithmain.SourceID ) AS publisherNameBangla, ( SELECT hadithstatus.StatusBG FROM hadithstatus WHERE hadithstatus.StatusID = hadithmain.HadithStatus ) AS status_bn, ( SELECT hadithstatus.StatusEN FROM hadithstatus WHERE hadithstatus.StatusID = hadithmain.HadithStatus ) AS status_en FROM `hadithmain` CROSS JOIN (SELECT @cnt := 0) AS dummy WHERE hadithmain.HadithActive = 1 AND hadithmain.BookID = 1 ORDER BY SectionID ASC");
while ($row = mysql_fetch_array($SqlQuery))
{
    LogManager::saveRawLog("INSERT INTO content (id,sequence,statusId,if_cross_checked,chapterId,sectionId,hadithNo) VALUES (".intval($row['id']).", ".intval($row['sequence']).", ".intval($row['statusId']).",".intval($row['if_cross_checked']).",".intval($row['chapterId']).",".intval($row['sectionId']).",".intval($row['hadithNo']).");");

    LogManager::saveRawLog('INSERT INTO content_fts (docid, hadithBengali, hadithEnglish, hadithArabic, note, rabiNameBangla, rabiNameEnglish, publisherNameEnglish, publisherNameBangla, status_bn, status_en) VALUES ('.$row['id'].', "'.strip_tags(htmlentities($row['hadithBengali'], ENT_QUOTES)).'", "'.strip_tags(htmlentities($row['hadithEnglish'], ENT_QUOTES)).'", "'.strip_tags(htmlentities($row['hadithArabic'], ENT_QUOTES)).'", "'.strip_tags(htmlentities($row['note'], ENT_QUOTES)).'", "'.strip_tags(htmlentities($row['rabiNameBangla'], ENT_QUOTES)).'", "'.strip_tags(htmlentities($row['rabiNameEnglish'], ENT_QUOTES)).'", "'.strip_tags(htmlentities($row['publisherNameEnglish'], ENT_QUOTES)).'", "'.strip_tags(htmlentities($row['publisherNameBangla'], ENT_QUOTES)).'", "'.strip_tags(htmlentities($row['status_bn'], ENT_QUOTES)).'", "'.strip_tags(htmlentities($row['status_en'], ENT_QUOTES)).'");');

}

LogManager::saveRawLog('COMMIT;');

$output = shell_exec('F:/php_applications/bangla_hadith/db_conversion/sqlite3/sqlite3 "F:\php_applications\bangla_hadith\db_conversion\database.db" -init "F:\php_applications\bangla_hadith\db_conversion\db.sql"');
echo "<pre>$output</pre>";

?>