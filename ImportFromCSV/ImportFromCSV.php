<?php

/*
Plugin Name: ImportFromCSV

Plugin URI: http://zero-plugin.com

Description: import via un CSV

Version: 0.1

Author:nekool

Author URI: http://nekool.xyz

License: GPL2

*/
class ImportFromCSV
{
    public function __construct()
    {
        $this->users = get_users();
        add_action('init',array($this ,'init'));
        global $wpdb;
        $this->db= new PDO("mysql:host=$wpdb->dbhost;dbname=$wpdb->dbname", "$wpdb->uname","$wpdb->pwd");
    }
    public function init(){
        if ( is_admin( ) ) {
            add_action( 'admin_menu', Array( $this, 'admin_menus' ) );
        }
        if ($_GET['page']=='ImportCSV') {
            wp_register_style('ImportFromCSV', plugins_url('css/style.css', __FILE__));
            wp_enqueue_style('ImportFromCSV');
        }
        wp_enqueue_script( 'wpcsv-scripts', plugins_url( '/js/script.js', __FILE__ ), Array( ), '', TRUE );
        echo '<script
			  src="https://code.jquery.com/jquery-3.2.1.min.js"
			  integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
			  crossorigin="anonymous"></script>';
    }
    public function admin_menus( ) {
        $capability = 'manage_options';
        if ( function_exists( 'add_menu_page' ) && function_exists( 'add_submenu_page' ) ) {
            add_menu_page( __( 'ImportFromCSV' ), __( 'ImportFromCSV' ), $capability, 'ImportCSV',  Array( $this,'includeIndex'), NULL, NULL );
            //boutton dans la barre admin
        }
    }

    /**
     * @return PDO
     */
    public function InsertImport()
    {
    }
    public function includeIndex(){
        $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
       ?>
        <form enctype="multipart/form-data" action="<?php echo $actual_link;?>" method="post">
            <label for="file">Choissisez le Fichier CSV a Importer :<input name="uploadfile" type="file" accept=".csv"></label>
            <br><input type="submit">
        </form>
    <?php
        //$path = ini_get('upload_tmp_dir');
        $requete_user='SELECT `COLUMN_NAME`
        FROM `INFORMATION_SCHEMA`.`COLUMNS`
        WHERE `TABLE_SCHEMA` = "wordpress-test"
        AND `TABLE_NAME` = "wp_users"
        LIMIT 0 , 30';
        $resultat_user=$this->db->query($requete_user);
        $données_user=$resultat_user->fetchall(PDO::FETCH_ASSOC);
        $tableau_check=array();
        foreach ($données_user as $key){
            array_push($tableau_check,$key[COLUMN_NAME]);
        }
        $tableau = array();
        $file =$_FILES['uploadfile']['name'];
        $ext = end(explode(".",$file));
        if ($_FILES['uploadfile']['tmp_name']&&$ext=="csv"){
            $fh = fopen($_FILES['uploadfile']['tmp_name'], 'r+');
            $row = 0;
            if (($handle = fopen($_FILES['uploadfile']['tmp_name'], "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                    $num = count($data);
                    for ($c=0; $c < $num; $c++) {
                            $tableau[$row] =$data;
                    }
                    $row++;
                }
                fclose($handle);
            }
            fclose($fh);

           for ($i=1;$i<count ($tableau);$i++){
               for ($j=1;$j<count ($tableau[$i]);$j++){
                    if(in_array($tableau[0][$j],$tableau_check)){

                        if(get_userdata( $tableau[$i][0] )==false){
                            $userdata = array();
                            while($tableau[$i][$j]){
                                $userdata[$tableau[0][$j]]=html_entity_decode ($tableau[$i][$j],ENT_QUOTES);
                                echo $tableau[$i][$j]." ///   ajout dans  ".$tableau[0][$j]."<br>";
                                $j++;
                            }
                           wp_insert_user( $userdata );
                        }
                        else{
                            $userdata = array();
                            $userdata['ID'] = $tableau[$i][0];
                            $userdata[$tableau[0][$j]] = $tableau[$i][$j];
                            wp_update_user($userdata);
                        }
                    }
                    else {
                        if (get_userdata($tableau[$i][0])) {
                            if($tableau[0][$j]=="wp_capabilities"){
                                $tableau[$i][$j]=explode(';',$tableau[$i][$j]);
                                $tableau[$i][$j]= explode('"',$tableau[$i][$j][1]);
                                $tableau[$i][$j]=$tableau[$i][$j][0];
                                $tableau[$i][$j] = substr($tableau[$i][$j], 0, -5);
                                $capabilities = array ( html_entity_decode ($tableau[$i][$j],ENT_QUOTES) => true );
                                update_user_meta($tableau[$i][0], $tableau[0][$j], $capabilities);
                            }
                            else{
                                update_user_meta($tableau[$i][0], $tableau[0][$j], html_entity_decode($tableau[$i][$j], ENT_QUOTES));
                            }
                        }
                    }
                }
           }//l'ID qui sera tj en fin de tableau puis la clef meta puis la valeur meta
        }
    }
}
$ImportFromCSV =new ImportFromCSV();