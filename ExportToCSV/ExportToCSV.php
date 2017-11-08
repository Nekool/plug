<?php
/*
Plugin Name: ExportToCSV

Plugin URI: http://zero-plugin.com

Description: export des client vers un fichier CSV

Version: 0.1

Author:nekool

Author URI: http://nekool.xyz

License: GPL2

*/
class ExportToCSV{
    /**
     * ExportToCSV constructor.
     */
    public function __construct()
    {
        $this->users = get_users();
        add_action('init',array($this ,'init'));
        add_action('wp_enqueue_scripts', 'add_js_scripts');
        add_action( 'wp_ajax_mon_action', Array( $this, 'FillCSV' ) );
        add_action( 'wp_ajax_nopriv_mon_action', Array( $this, 'FillCSV' ) );
        $this->db= new PDO('mysql:host=localhost;dbname=wordpress-test', 'root', '');
    }
    /*
      * Actions perform at loading of admin menu
    */
    public function add_js_scripts() {
        wp_enqueue_script( 'script', get_template_directory_uri().'/js/script.js', array('jquery'), '1.0', true );

        // pass Ajax Url to script.js
        wp_localize_script('script', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
    }
    public function init(){
        if ( is_admin( ) ) {
            add_action( 'admin_menu', Array( $this, 'admin_menus' ) );
        }
        if ($_GET['page']=='ExportCSV') {
            wp_register_style('ExportToCSV', plugins_url('css/style.css', __FILE__));
            wp_enqueue_style('ExportToCSV');
        }
        wp_enqueue_script( 'jquery-ui-tabs' );
        wp_enqueue_script( 'wpcsv-scripts', plugins_url( '/js/script.js', __FILE__ ), Array( ), '', TRUE );
        echo '<script
			  src="https://code.jquery.com/jquery-3.2.1.min.js"
			  integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
			  crossorigin="anonymous"></script>';
    }
    public function admin_menus( ) {
        $capability = 'manage_options';
        if ( function_exists( 'add_menu_page' ) && function_exists( 'add_submenu_page' ) ) {
            add_menu_page( __( 'ExportToCSV' ), __( 'ExportToCSV' ), $capability, 'ExportCSV',  Array( $this,'includeIndex'), NULL, NULL );
            //boutton dans la barre admin
         }
    }
    Public function ExportToCSV_Install(){
    }
    function ExportToCSV_Uninstall(){
    }
    public function includeIndex(){
        $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        ?>
        <h1>Export des données clients vers un fichier CSV</h1>
        <h2>Selectionnez les champs à inclure :</h2>
        <form action='<?php echo $actual_link;?>' method="post">
    <?php


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
    $request='SELECT DISTINCT `meta_key` FROM `wp_usermeta`';
    $resultat=$this->db->query($request);
    $données=$resultat->fetchall(PDO::FETCH_ASSOC);
    foreach ($données as $key){
        array_push($tableau_check,$key["meta_key"]);
    }
    //cree les labelle a partir du tableau des noms  d'elements meta key et données user
    $tableau_check=array_map('strtolower', $tableau_check);
    foreach ($tableau_check as $key){
        if($key=='id'){
            echo '<label><input type="checkbox" value="'.$key.'"name="'.$key.'" id="check'.$key.'"checked  onclick="return false;">'.$key.'</label>';
        }
        else{
            echo '<label><input type="checkbox" value="'.$key.'"name="'.$key.'" id="check'.$key.'">'.$key.'</label>';
        }
    }
    ?>
<input name="submit"  type="submit"id="requette"><input type="submit" value="Tout cocher" id="selectAll"><input type="submit" value="Tout décocher" id="selectNone">
</form>

        <?php
      //  var_dump($_POST);
    }

    public function FillCSV(){

        $tableau_entete=$_POST;
        $fp = fopen('php://output', 'w');
        ob_clean();
        fputcsv($fp, $tableau_entete,$escap_char=";",$enclosure = ' ');
        foreach ($this->users as $fields) {
                $ligne=get_user_meta ( $fields->ID);
                $tableau=array();
                foreach ($tableau_entete as $key ){
                        if($fields->$key && !is_array($fields->$key) ){
                            array_push($tableau,htmlentities($fields->$key,ENT_QUOTES));
                        }
                        else{
                            array_push($tableau,htmlentities($ligne[$key][0],ENT_QUOTES));
                        }
                }
            fputcsv($fp, $tableau,  $escap_char=";",$enclosure = '"');
            }
        fclose($fp);
            header('Content-Type: application/csv');
            header('Content-Disposition: attachement; filename="php://output"');
            exit();

    }

}
$ExportToCSV = new ExportToCSV();
if ($_POST['submit']){
    unset($_POST['submit']);
    $ExportToCSV->FillCSV();
}
