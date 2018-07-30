<?php

class ModelExtensionPaymentMokaPayment extends Model {

    public function install() {
        $file = DIR_APPLICATION . 'moka_payment.sql';
        if (!file_exists($file)) {
            
        }
        $lines = file($file);
        if ($lines) {
            $sql = '';

            foreach ($lines as $line) {
                if ($line && (substr($line, 0, 2) != '--') && (substr($line, 0, 1) != '#')) {
                    $sql .= $line;

                    if (preg_match('/;\s*$/', $line)) {
                        $sql = str_replace("INSERT INTO `oc_", "INSERT INTO `" . DB_PREFIX, $sql);
                        $this->db->query($sql);
                        $sql = '';
                    }
                }
            }
        }
    }

    public function uninstall() {
         $this->db->query("DELETE FROM `" . DB_PREFIX . "modification` WHERE code='moka_payment'");
    }

    public function logger($message) {
        $log = new Log('moka_payment.log');
        $log->write($message);
    }

    public function createOrderEntry($data) {
        
    }

    public function updateOrderEntry($data, $id) {
        
    }
    

    public function versionCheck($opencart, $moka) {
        $serverdomain = $_SERVER['HTTP_HOST'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://api.kahvedigital.com/version');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "opencart=$opencart&moka=$moka&type=opencart&domain=$serverdomain");
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        return $response;
    }

    public function update($version_updatable) {

        function recurse_copy($src, $dst) {
            $dir = opendir($src);
            @mkdir($dst);
            while (false !== ( $file = readdir($dir))) {
                if (( $file != '.' ) && ( $file != '..' )) {
                    if (is_dir($src . '/' . $file)) {
                        recurse_copy($src . '/' . $file, $dst . '/' . $file);
                    } else {
                        copy($src . '/' . $file, $dst . '/' . $file);
                    }
                }
            }
            closedir($dir);
        }

        function rrmdir($dir) {
            if (is_dir($dir)) {
                $objects = scandir($dir);
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..") {
                        if (filetype($dir . "/" . $object) == "dir")
                            rrmdir($dir . "/" . $object);
                        else
                            unlink($dir . "/" . $object);
                    }
                }
                reset($objects);
                rmdir($dir);
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://api.kahvedigital.com/update');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "new_version=$version_updatable");
        $response = curl_exec($ch);
        $response = json_decode($response, true);

        $serveryol = $_SERVER['DOCUMENT_ROOT'];
        $ch = curl_init();
        $source = $response['file_dest'];
        curl_setopt($ch, CURLOPT_URL, $source);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        curl_close($ch);

        $foldername = $response['version_name'];
        $fullfoldername = $serveryol . '/' . $foldername;
        if (!file_exists($fullfoldername)) {
            mkdir($fullfoldername);
        }
        if (file_exists($fullfoldername)) {
            $unzipfilename = 'mokaupdate.zip';
            $file = fopen($fullfoldername . '/' . $unzipfilename, "w+");
            fputs($file, $data);
            fclose($file);

            $path = pathinfo(realpath($fullfoldername . '/' . $unzipfilename), PATHINFO_DIRNAME);
            $zip = new ZipArchive;
            $res = $zip->open($fullfoldername . '/' . $unzipfilename);
            if ($res === TRUE) {
                $zip->extractTo($path);
                $zip->close();
                $zip_name_folder = $response['zip_name_folder'];

                recurse_copy($fullfoldername . '/' . $zip_name_folder . '/admin', DIR_APPLICATION);
                recurse_copy($fullfoldername . '/' . $zip_name_folder . '/catalog', DIR_CATALOG);
                recurse_copy($fullfoldername . '/' . $zip_name_folder . '/system', DIR_SYSTEM);

                rrmdir($fullfoldername);
            } else {
                return 0;
            }
        } else {
            return 0;
        }
        return 1;
    }

}
