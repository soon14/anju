<?php

class MySql {

    var $linkID;
    var $dbHost;
    var $dbUser;
    var $dbPwd;
    var $dbName;
    var $dbPrefix;
    var $result;
    var $queryString;
    var $parameters;
    var $isClose;
    var $safeCheck;
    var $recordLog = false; // 记录日志到data/mysqli_record_log.inc便于进行调试
    var $isInit = false;
    var $pconnect = false;

    //用外部定义的变量初始类，并连接数据库
    function __construct($pconnect=FALSE, $nconnect=FALSE) {
        $this->isClose = FALSE;
        $this->safeCheck = TRUE;
        $this->pconnect = $pconnect;
        if ($nconnect) {
            $this->Init($pconnect);
        }
    }

    function Init($pconnect=FALSE) {
        $this->linkID = 0;
        $this->dbHost = C("DB_HOST");
        $this->dbUser = C("DB_USER");
        $this->dbPwd = C("DB_PWD");
        $this->dbName = C("DB_NAME");
        $this->dbPrefix = C("DB_PREFIX");
        $this->result["me"] = 0;
        $this->Open($pconnect);
    }

    //用指定参数初始数据库信息
    function SetSource($host, $username, $pwd, $dbname, $dbprefix="lulin_") {
        $this->dbHost = $host;
        $this->dbUser = $username;
        $this->dbPwd = $pwd;
        $this->dbName = $dbname;
        $this->dbPrefix = $dbprefix;
        $this->result["me"] = 0;
    }

    function SelectDB($dbname) {
        mysql_select_db($dbname);
    }

    //设置SQL里的参数
    function SetParameter($key, $value) {
        $this->parameters[$key] = $value;
    }

    //连接数据库
    function Open($pconnect=FALSE) {
        //连接数据库
        if ($this && !$this->isClose && $this->isInit) {
            $this->linkID = $this->linkID;
        } else {
            $i = 0;

            while (!$this->linkID) {
                if ($i > 100)
                    break;

                if (!$pconnect) {
                    $this->linkID = @mysql_connect($this->dbHost, $this->dbUser, $this->dbPwd);
                } else {
                    $this->linkID = @mysql_pconnect($this->dbHost, $this->dbUser, $this->dbPwd);
                }
                $i++;
            }

            //复制一个对象副本
//            CopySQLPoint($this);
        }

        //处理错误，成功连接则选择数据库
        if (!$this->linkID) {
            $this->DisplayError("错误警告：<font color='red'>连接数据库失败，可能数据库密码不对或数据库服务器出错！</font>");
            exit();
        }
        $this->isInit = TRUE;
        @mysql_select_db($this->dbName);
        $mysqlver = explode('.', $this->GetVersion());
        $mysqlver = $mysqlver[0] . '.' . $mysqlver[1];

        if ($mysqlver > 4.0) {
            @mysql_query("SET NAMES '" . "utf8" . "', character_set_client=binary, sql_mode='', interactive_timeout=3600 ;", $this->linkID);
        }

        return TRUE;
    }

    //为了防止采集等需要较长运行时间的程序超时，在运行这类程序时设置系统等待和交互时间
    function SetLongLink() {
        @mysql_query("SET interactive_timeout=3600, wait_timeout=3600 ;", $this->linkID);
    }

    //获得错误描述
    function GetError() {
        $str = mysql_error();
        return $str;
    }

    //关闭数据库
    //mysql能自动管理非持久连接的连接池
    //实际上关闭并无意义并且容易出错，所以取消这函数
    function Close($isok=FALSE) {
        $this->FreeResultAll();
        if ($isok) {
            @mysql_close($this->linkID);
            $this->isClose = TRUE;
        }
    }

    //关闭指定的数据库连接
    function CloseLink($dblink) {
        @mysql_close($dblink);
    }

    function Esc($_str) {
        if (version_compare(phpversion(), '4.3.0', '>=')) {
            return @mysql_real_escape_string($_str);
        } else {
            return @mysql_escape_string($_str);
        }
    }

    
    //执行一个不返回结果的SQL语句，如update,delete,insert等
    function DoNotBack($sql='') {
        if (!$this->isInit) {
            $this->Init($this->pconnect);
        }
        if ($this->isClose) {
            $this->Open(FALSE);
            $this->isClose = FALSE;
        }
        if (!empty($sql)) {
            $this->SetQuery($sql);
        } else {
            return FALSE;
        }
        if (is_array($this->parameters)) {
            foreach ($this->parameters as $key => $value) {
                $this->queryString = str_replace("@" . $key, "'$value'", $this->queryString);
            }
        }
        //SQL语句安全检查
        if ($this->safeCheck)
            CheckSql($this->queryString, 'update');
        $rs = mysql_query($this->queryString, $this->linkID);

        return $rs;
    }
    
    function AddNotBack($tab,$array){
        foreach ($array as $key => $value) {
            if($k == ""){
                $k .= "`$key`";
            }else{
                $k .= ",`$key`";
            }
            if($v == ""){
                $v .= "'$value'";
            }else{
                $v .= ",'$value'";
            }
        }
        $sql = "INSERT INTO `$tab` ($k) VALUES ($v);";
        return $this->DoNotBack($sql);
    }
    
    function EditNotBack($tab,$array,$where=""){
        foreach ($array as $key => $value) {
            if($kv == ""){
                $kv .= "`$key`='$value'";
            }else{
                $kv .= ",`$key`='$value'";
            }
        }
        if($where != ""){
            $where = "WHERE $where";
        }
        $sql = "UPDATE `$tab` SET $kv $where;";
        return $this->DoNotBack($sql);
    }
    
    function  DelNotBack($tab,$where=""){
        $sql = "DELETE FROM `$tab` WHERE $where;";
        return $this->DoNotBack($sql);
    }

    //执行一个返回影响记录条数的SQL语句，如update,delete,insert等
    function ExecuteNoneQuery2($sql='') {
        if (!$this->isInit) {
            $this->Init($this->pconnect);
        }
        if ($this->isClose) {
            $this->Open(FALSE);
            $this->isClose = FALSE;
        }

        if (!empty($sql)) {
            $this->SetQuery($sql);
        }
        if (is_array($this->parameters)) {
            foreach ($this->parameters as $key => $value) {
                $this->queryString = str_replace("@" . $key, "'$value'", $this->queryString);
            }
        }
        mysql_query($this->queryString, $this->linkID);

        return mysql_affected_rows($this->linkID);
    }

    function GetFetchRow($id='me') {
        return @mysql_fetch_row($this->result[$id]);
    }

    function GetAffectedRows() {
        return mysql_affected_rows($this->linkID);
    }

    //执行一个带返回结果的SQL语句，如SELECT，SHOW等
    function Execute($id="me", $sql='') {
        if (!$this->isInit) {
            $this->Init($this->pconnect);
        }
        if ($this->isClose) {
            $this->Open(FALSE);
            $this->isClose = FALSE;
        }
        if (!empty($sql)) {
            $this->SetQuery($sql);
        }

        //SQL语句安全检查
        if ($this->safeCheck) {
            CheckSql($this->queryString);
        }

        $this->result[$id] = mysql_query($this->queryString, $this->linkID);

        if (!empty($this->result[$id]) && $this->result[$id] === FALSE) {
            $this->DisplayError(mysql_error() . " <br />Error sql: <font color='red'>" . $this->queryString . "</font>");
        }
    }

    function Query($id="me", $sql='') {
        $this->Execute($id, $sql);
    }

    //执行一个SQL语句,返回前一条记录或仅返回一条记录
    function GetOne($sql='', $acctype=MYSQL_ASSOC) {
        if (!$this->isInit) {
            $this->Init($this->pconnect);
        }
        if ($this->isClose) {
            $this->Open(FALSE);
            $this->isClose = FALSE;
        }
        if (!empty($sql)) {
            if (!preg_match("/LIMIT/i", $sql))
                $this->SetQuery(preg_replace("/[,;]$/i", '', trim($sql)) . " LIMIT 0,1;");
            else
                $this->SetQuery($sql);
        }
        $this->Execute("one");
        $arr = $this->GetArray("one", $acctype);
        if (!is_array($arr)) {
            return '';
        } else {
            @mysql_free_result($this->result["one"]);
            return($arr);
        }
    }

    //执行一个不与任何表名有关的SQL语句,Create等
    function ExecuteSafeQuery($sql, $id="me") {
        if (!$this->isInit) {
            $this->Init($this->pconnect);
        }
        if ($this->isClose) {
            $this->Open(FALSE);
            $this->isClose = FALSE;
        }
        $this->result[$id] = @mysql_query($sql, $this->linkID);
    }

    //返回当前的一条记录并把游标移向下一记录
    // MYSQL_ASSOC、MYSQL_NUM、MYSQL_BOTH
    function GetArray($id="me", $acctype=MYSQL_ASSOC) {
        if ($this->result[$id] == 0) {
            return FALSE;
        } else {
            return mysql_fetch_array($this->result[$id], $acctype);
        }
    }

    function GetObject($id="me") {
        if ($this->result[$id] == 0) {
            return FALSE;
        } else {
            return mysql_fetch_object($this->result[$id]);
        }
    }

    // 检测是否存在某数据表
    function IsTable($tbname) {
        if (!$this->isInit) {
            $this->Init($this->pconnect);
        }
        $prefix = C("DB_PREFIX");
        $tbname = str_replace($prefix, C("DB_PREFIX"), $tbname);
        if (mysql_num_rows(@mysql_query("SHOW TABLES LIKE '" . $tbname . "'", $this->linkID))) {
            return TRUE;
        }
        return FALSE;
    }

    //获得MySql的版本号
    function GetVersion($isformat=TRUE) {
        if (!$this->isInit) {
            $this->Init($this->pconnect);
        }
        if ($this->isClose) {
            $this->Open(FALSE);
            $this->isClose = FALSE;
        }

        $rs = @mysql_query("SELECT VERSION();", $this->linkID);
        $row = @mysql_fetch_array($rs);
        $mysql_version = $row[0];
        @mysql_free_result($rs);
        if ($isformat) {
            $mysql_versions = explode(".", trim($mysql_version));
            $mysql_version = number_format($mysql_versions[0] . "." . $mysql_versions[1], 2);
        }
        return $mysql_version;
    }

    //获取特定表的信息
    function GetTableFields($tbname, $id="me") {
        if (!$this->isInit) {
            $this->Init($this->pconnect);
        }
        $this->result[$id] = mysql_list_fields($this->dbName, $tbname, $this->linkID);
    }

    //获取字段详细信息
    function GetFieldObject($id="me") {
        return mysql_fetch_field($this->result[$id]);
    }

    //获得查询的总记录数
    function GetTotalRow($id="me") {
        if ($this->result[$id] == 0) {
            return -1;
        } else {
            return mysql_num_rows($this->result[$id]);
        }
    }

    //获取上一步INSERT操作产生的ID
    function GetLastID() {
        //如果 AUTO_INCREMENT 的列的类型是 BIGINT，则 mysql_insert_id() 返回的值将不正确。
        //可以在 SQL 查询中用 MySQL 内部的 SQL 函数 LAST_INSERT_ID() 来替代。
        //$rs = mysql_query("Select LAST_INSERT_ID() as lid",$this->linkID);
        //$row = mysql_fetch_array($rs);
        //return $row["lid"];
        return mysql_insert_id($this->linkID);
    }

    //释放记录集占用的资源
    function FreeResult($id="me") {
        @mysql_free_result($this->result[$id]);
    }

    function FreeResultAll() {
        if (!is_array($this->result)) {
            return '';
        }
        foreach ($this->result as $kk => $vv) {
            if ($vv) {
                @mysql_free_result($vv);
            }
        }
    }

    //设置SQL语句，会自动把SQL语句里的#BL_替换为$this->dbPrefix(在配置文件中为$cfg_dbprefix)
    function SetQuery($sql) {
        $prefix = "###_";
        $sql = str_replace($prefix, $this->dbPrefix, $sql);
        $this->queryString = $sql;
    }

    function SetSql($sql) {
        $this->SetQuery($sql);
    }

    function RecordLog($runtime=0) {
        $RecordLogFile = dirname(__FILE__) . '/../data/mysqli_record_log.inc';
        $url = $this->GetCurUrl();
        $savemsg = <<<EOT

------------------------------------------
SQL:{$this->queryString}
Page:$url
Runtime:$runtime	
EOT;
        $fp = @fopen($RecordLogFile, 'a');
        @fwrite($fp, $savemsg);
        @fclose($fp);
    }

    //显示数据链接错误信息
    function DisplayError($msg) {
        $emsg = '';
        $emsg .= "<div><h3>Error Warning!</h3>\r\n";
        $emsg .= "<div style='line-helght:160%;font-size:14px;color:green'>\r\n";
        $emsg .= "<div style='color:blue'><br />Error page: <font color='red'>" . $this->GetCurUrl() . "</font></div>\r\n";
        $emsg .= "<div>Error infos: {$msg}</div>\r\n";
        $emsg .= "<br /></div></div>\r\n";

        echo $emsg;
    }
    //获得当前的脚本网址
    function GetCurUrl()
    {
        if(!empty($_SERVER["REQUEST_URI"]))
        {
            $scriptName = $_SERVER["REQUEST_URI"];
            $nowurl = $scriptName;
        }
        else
        {
            $scriptName = $_SERVER["PHP_SELF"];
            if(empty($_SERVER["QUERY_STRING"])) {
                $nowurl = $scriptName;
            }
            else {
                $nowurl = $scriptName."?".$_SERVER["QUERY_STRING"];
            }
        }
        return $nowurl;
    }

}

//复制一个对象副本
//function CopySQLPoint(&$ndsql) {
//    $GLOBALS['dsql'] = $ndsql;
//}

//SQL语句过滤程序，由80sec提供，这里作了适当的修改
if (!function_exists('CheckSql')) {

    function CheckSql($db_string, $querytype='select') {
        $clean = '';
        $error = '';
        $old_pos = 0;
        $pos = -1;

        //如果是普通查询语句，直接过滤一些特殊语法
        if ($querytype == 'select') {
            $notallow1 = "[^0-9a-z@\._-]{1,}(union|sleep|benchmark|load_file|outfile)[^0-9a-z@\.-]{1,}";
        }

        //完整的SQL检查
        while (TRUE) {
            $pos = strpos($db_string, '\'', $pos + 1);
            if ($pos === FALSE) {
                break;
            }
            $clean .= substr($db_string, $old_pos, $pos - $old_pos);
            while (TRUE) {
                $pos1 = strpos($db_string, '\'', $pos + 1);
                $pos2 = strpos($db_string, '\\', $pos + 1);
                if ($pos1 === FALSE) {
                    break;
                } elseif ($pos2 == FALSE || $pos2 > $pos1) {
                    $pos = $pos1;
                    break;
                }
                $pos = $pos2 + 1;
            }
            $clean .= '$s$';
            $old_pos = $pos + 1;
        }
        $clean .= substr($db_string, $old_pos);
        $clean = trim(strtolower(preg_replace(array('~\s+~s'), array(' '), $clean)));

        //老版本的Mysql并不支持union，常用的程序里也不使用union，但是一些黑客使用它，所以检查它
        if (strpos($clean, 'union') !== FALSE && preg_match('~(^|[^a-z])union($|[^[a-z])~s', $clean) != 0) {
            $fail = TRUE;
            $error = "union detect";
        }

        //发布版本的程序可能比较少包括--,#这样的注释，但是黑客经常使用它们
        elseif (strpos($clean, '/*') > 2 || strpos($clean, '--') !== FALSE || strpos($clean, '#') !== FALSE) {
            $fail = TRUE;
            $error = "comment detect";
        }

        //这些函数不会被使用，但是黑客会用它来操作文件，down掉数据库
        elseif (strpos($clean, 'sleep') !== FALSE && preg_match('~(^|[^a-z])sleep($|[^[a-z])~s', $clean) != 0) {
            $fail = TRUE;
            $error = "slown down detect";
        } elseif (strpos($clean, 'benchmark') !== FALSE && preg_match('~(^|[^a-z])benchmark($|[^[a-z])~s', $clean) != 0) {
            $fail = TRUE;
            $error = "slown down detect";
        } elseif (strpos($clean, 'load_file') !== FALSE && preg_match('~(^|[^a-z])load_file($|[^[a-z])~s', $clean) != 0) {
            $fail = TRUE;
            $error = "file fun detect";
        } elseif (strpos($clean, 'into outfile') !== FALSE && preg_match('~(^|[^a-z])into\s+outfile($|[^[a-z])~s', $clean) != 0) {
            $fail = TRUE;
            $error = "file fun detect";
        }

        //老版本的MYSQL不支持子查询，我们的程序里可能也用得少，但是黑客可以使用它来查询数据库敏感信息
        elseif (preg_match('~\([^)]*?select~s', $clean) != 0) {
            $fail = TRUE;
            $error = "sub select detect";
        }
        if (!empty($fail)) {
            exit("<font size='5' color='red'>Safe Alert: Request Error step 2!</font>");
        } else {
            return $db_string;
        }
    }

    

}