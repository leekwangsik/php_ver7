<?php
namespace App;

use App\Db;

abstract class Controller {

    private static $instance = null;
    private static $fnames = [];

    /**
     * @brief 테이블명 리턴
     * @return 테이블명
     */
    protected abstract function getTableName();

    public function set(array $arr) {
        foreach($arr as $key => $val) {
            
            if(property_exists($this, $key)) {
                $this->$key = $val;
            }    
        }
        return $this;
    }

    public function add(array $arr) {

        foreach($arr as $key => $val) {
            $this->$key = $val; 
        }
        return $this;
    }
    
    public function create($data_arr = []) {
        $tbl_name = $this->getTableName();
        return Db::insert($tbl_name, $this);
    }

    public function update($except_param = []) {
        $tbl_name = $this->getTableName();
        Db::update($tbl_name, $this, "sid=?", $this->sid, $except_param);
    }

    public function delete() {
        $tbl_name = $this->getTableName();
        Db::update($tbl_name, [ "del" => 'Y', "deleted_at" => date('Y-m-d H:i:s') ], "sid=?", $this->sid);
    }

    public function setAll(array &$arr) {
        $tbl_name = $this->getTableName();

        // $fname = Db::query("SELECT column_name FROM information_schema.columns WHERE table_name = '" . $tbl_name . "'")->getAllCol();

        if(isset(self::$fnames[$tbl_name])) {
            $fname = self::$fnames[$tbl_name];
        } else {
            $fname = Db::query("SELECT column_name FROM information_schema.columns WHERE table_schema = '".DB::DB_NAME."' AND table_name = '" . $tbl_name . "'")->getAllCol();
            self::$fnames += [$tbl_name => $fname];
        }
        
        foreach($fname as $key => $val) {
            $this->$val = $arr[$key]; 
            unset($arr[$key]);
        }

        $arr = array_values($arr);
    }

    public function get(int $sid) {
        $tbl_name = $this->getTableName();

        $query = "select * from " . $tbl_name . " where sid = " . $sid;
        $tempList = Db::query($query)->getObj(get_class($this));
        if(isset($tempList)) {
            foreach($tempList as $key => $val) {
                $this->$key = $val;
            }
            unset($tempList);            
        }
        return $this;
    }

    public function getBy($key, $val) {
        $tbl_name = $this->getTableName();

        $query = "select * from " . $tbl_name . " where " . $key . " = ?";
        $tempList = Db::query($query, [$val])->getObj(get_class($this));
        
        if(isset($tempList)) {
            foreach($tempList as $key => $val) {
                $this->$key = $val;
            }
            unset($tempList);            
        }
        return $this;
    }

    /**
     * breif
     */
    public function setFile($filename, $sFile, $filepath) {
        
        $realfilename = str_replace("_file", "_realfile", $filename);
        $filesystem = new \FileUpload\FileSystem\Simple();
        $pathresolver = new \FileUpload\PathResolver\Simple($filepath);
        $randomGenerator = new \FileUpload\FileNameGenerator\Random();

        $fileupload = new \FileUpload\FileUpload($sFile, $_SERVER);
    
        $fileupload->setPathResolver($pathresolver);
        $fileupload->setFileNameGenerator($randomGenerator);
        $fileupload->setFileSystem($filesystem);
        
        $file = $fileupload->processAll()[0][0];

        $this->$filename = $file->getClientFileName();
        $this->$realfilename = $file->getFileName();

    }

    public function getFile($type, $filename, $filepath) {

        $realfilename = str_replace("_file", "_realfile", $filename);

        if($type == "url") {
            $return = Site::$CONFIG['pcUrl'] . $filepath . '/' . $this->$realfilename;

        } else if($type == "down") {
            $return = "/download.php?path=".urlencode(Site::$CONFIG['pcRoot'] . $filepath . '/' . $this->$realfilename)."&filename=".urlencode($this->$filename);
        }

        return $return;
    }

    public function Delfile($filename, $filepath) {

        $realfilename = str_replace("_file", "_realfile", $filename);

        self::getInstance();
        if(self::$instance->getTableName()) {
            if(unlink( SITE::$CONFIG['uploadRoot']. $filepath . "/".$this->$realfilename)) {
                $query = "update " . self::$instance->getTableName() . " set $filename='', $realfilename='' where sid = " . $this->sid;
                Db::exec($query);
                echo 'Y';
                exit;
            } else {
                echo "N";
                exit;
            }
        } else {
            echo "N";
        }
    }

    public function getCreatedAt() {
        return (isset($this->created_at) && !empty($this->created_at)) ? 
        (new \DateTime($this->created_at))->format('Y.m.d') :
        "";
    }

    public function getExplode($val, $gubun = '-') : array {
        if($this->$val) {
            $arr = explode($gubun, $this->val);
        } 

        return $arr ?? [];
    }

    public static function delete_list($sids) {
        self::getInstance();
        if(self::$instance->getTableName()) {
            Db::update(self::$instance->getTableName(), [ "del" => 'Y', "deleted_at" => date('Y-m-d H:i:s') ], "sid in (".$sids.")", []);
        }
    }

    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static;
        }
    }

}