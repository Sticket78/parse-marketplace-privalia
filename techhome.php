<?php
class TechHome {
    public function __construct($db) {
        $this->conn = $db;
    }
    public function parse_privalia($url, $key, $el_id=''){
        /*
        * парсим привалию!
        */
        $post=array();
        // keypass из cookies после авторизации
        $keypass=$key;
        $headers = stream_context_create(
            array(
                'http' => array(
                    'method'  => 'POST',
                    'header'  => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL .
                        'Cookie: .ASPXAUTH='.$keypass,
                    'content' => http_build_query($post),
                )
            )
        );
        $res=file_get_contents($url, false, $headers);
        $dom = new DomDocument();
        $dom->loadHTML($res);
        $my_src=$dom->GetElementById('__NEXT_DATA__'); // скрипт в котором дата в документе из privalia
        $script_value=$my_src->nodeValue;

        //file_put_contents('privalia_data.txt', $script_value);
        $home_json = preg_replace('/^parseresponse\((.*)\);/', '$1', 'parseresponse('.$script_value.');');
        $res = json_decode($home_json, true);
        if (empty($el_id)) {
            // Home - путь в объекте ["props"]["initialState"]["Home"]["result"]["homes"][0]["modules"]
            $data = $res["props"]["initialState"];
            // подборка товаров с главной
            $modules_data = $data["Home"]["result"]["homes"][0]["modules"];
            $itog_data=$modules_data;
            $query = "DELETE FROM home_src;";
            $result = $this->conn->query($query);
            $query = "INSERT INTO home_src (`obj_src`)  VALUES (:obj);";
            $params = [
                'obj' => json_encode($modules_data, JSON_UNESCAPED_UNICODE)
            ];
            $result = $this->conn->query($query, $params);
            // локальные картинки для homepage
            self::make_home_local_imgs($modules_data);
        }
        else {
            $query="SELECT `cat_id` from catalog_src WHERE cat_id=:id;";
            $params=array('id'=>$el_id);
            $data = $res["props"]["initialState"];
            //return $data;
            $itog_data=$data;
            $check=$this->conn->query($query, $params);
            //print_r($check);
            $params = array(
                'cat_id' => $el_id,
                'src' => json_encode($data, JSON_UNESCAPED_UNICODE)
            );
            if (empty($check)) {
                $query = "INSERT INTO catalog_src (`cat_id`, `src`) VALUES (:cat_id, :src);";
            }
            else {
                $query="UPDATE catalog_src SET `src`=:src where `cat_id`=:cat_id;";
            }
                $result = $this->conn->query($query, $params);
                $new_id=$this->conn->last_id();
                print $new_id;

        }
        //new_id=$this->conn->last_id();
        return $itog_data;
    }
    public function make_home_local_imgs($home_obj){
        self::clean_home_img_dir();
        foreach ($home_obj as $group) {
            foreach ($group["banners"] as $el) {
                if ($el["advertisement"]) continue; // пропускаем рекламу
                print "id=".$el["id"]."<br>";
                $desk_img=self::get_desktop_image_url($el);
                $mob_img=self::get_mobile_image_url($el);
                $soon=($group["name"]=='soon') ? '_soon':'';
                if (!empty($desk_img)) {
                    if (file_exists(ROOT.'img/home/'.$el["id"]."_desk".$soon.'.webp')){
                        echo 'файл существует - '.$el["id"]."_desk".$soon.'.webp'."<br>";
                    }
                    else {
                        self::saveImgFromUrl($desk_img, $el["id"], "_desk" . $soon);
                    }
                }
                if (!empty($mob_img)) {
                    if (file_exists(ROOT.'img/home/'.$el["id"]."_mob".$soon.'.webp')){
                        echo 'файл существует - '.$el["id"]."_mob".$soon.'.webp'."<br>";
                    }
                    else {
                        self::saveImgFromUrl($mob_img, $el["id"], "_mob" . $soon);
                    }
                }
                // TO DO
                /*
                 * сделать хранение изображений в db по привязке к id
                 * + дополнительные поля для id (бренда) в одной или разных таблицах
                 *  отдельным полем хранить для группы из soon
                 *  при переходе из soon в активные группы - обновлять изображения в базе и на диске
                 */
            }
        }
    }
    public function get_desktop_image_url($el) {
        return $el["allDevicesImage"]["webImage"]["url"];
    }
    public function get_mobile_image_url($el) {
        return $el["allDevicesImage"]["mobileImage"]["url"];
    }
    private function clean_home_img_dir(){
        $files = glob(ROOT.'img/home/*'); // get all file names
        foreach($files as $file){ // iterate files
            if(is_file($file)) {
                unlink($file); // delete file
            }
        }
    }
    public function saveImgFromUrl($url, $id, $dop_name, $quality=75) {
        $name = $id.$dop_name;
        $orig_img=file_get_contents($url);
        $destination_img=ROOT.'img/home/'.$name;
        //file_put_contents($destination_img, $orig_img);
        $img = imageCreateFromString($orig_img);
        if($img!=false){
            $res=ROOT.'img/home/'.$name.'.'.'webp';
            imageWebp($img, $res, $quality);
            imagedestroy($img);
        }
        return $res;
    }
    public function convertToWebp($file, $filename, $quality=75) {
        $name_arr = explode('.', $filename);
        $name=$name_arr[0].'.webp';
        $orig_img=$file;
        $destination_img=ROOT.'uploads/';
        //file_put_contents($destination_img, $orig_img);
        $img = imageCreateFromPng($orig_img);
        if($img!=false){
            $res=$destination_img.$name;
            imageWebp($img, $res, $quality);
            imagedestroy($img);
            return $name;
        }
        return 'error';
    }
}
