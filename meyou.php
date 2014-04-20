<?php
require_once 'simple_html_dom.php';
define( "PAGE_NO", 50 );

$Meyou = new Meyou($argv);
$Meyou->run();

class Meyou{
   function __construct($argv){
      $this->ranking_start        = $argv[1];
      $this->ranking_end          = $argv[2];
      $this->gt                = $argv[3]; 
      $this->file_name         = $argv[4] . ".csv";
      $this->page_no           = PAGE_NO;
      $this->url_base          = "http://meyou.jp/ranking/follower_allcat/";
      $this->error             = array();
      self::validation();
   }

   function get_url_list(){
      $url_list = array();
      for( $no = ceil( $this->ranking_start / $this-> page_no ); $no <= ceil( $this->ranking_end / $this-> page_no ); $no++){
         $page = ($no == 1)?"":($no-1)*50;
         array_push( $url_list, $this->url_base . $page);
      }
      return $url_list;
   }

   function validation(){
      if( empty( $this->file_name ) ){ 
          array_push( $this->error, "Please Output file name" );
      }
      if( empty( $this->ranking_start ) ){
          array_push( $this->error, "Please Input Page Start" );
      }
      if( empty( $this->ranking_end ) ){
          array_push( $this->error, "Please Input Page End" );
      }
      if( $this->ranking_start < 0 ){
          array_push( $this->error, "Please Input positive number at Page Start " );
      }
      if( $this->ranking_end < 0 ){
          array_push( $this->error, "Please Input positive number at Page End" );
      }
      if( $this->ranking_start > $this->ranking_end ){
          array_push( $this->error, "Please Page Start No < Page End No" );
      }
      if( count( $this->error )  > 0 ){ echo join( "\n", $this->error ) . "\n" ; exit ;}
   }

   function get_page_contents( $url ){
      return file_get_contents( $url );
   }

   function run(){
       echo Constant::get_firstline();
       $User = new User( $this->ranking_start, $this->ranking_end );

       $url_list = self::get_url_list();
       foreach( $url_list as $url ){
          $body = self::get_page_contents( $url );
          $User->run($body);
          sleep(1);
       }
   }
}

class User{
   function __construct( $start, $end ){
      $this->ranking_start        = $start;
      $this->ranking_end          = $end;
   }

   function run( $body ){
      $users = array();
      $lines = self::get_user_lines($body);
      foreach( $lines as $line ){
         $user = self::get_user_detail( $line ); 
         if( $user["ranking"] >= $this->ranking_start and $user["ranking"] <= $this->ranking_end ){
            Constant::print_lines( $user );
         }
      }
   }

   function get_user_lines( $body ){
      $html = str_get_html( $body );
      $tweets = $html->find( 'tr[class=tweet]' );
      return $tweets;
   }

   function get_user_detail( $line ){
       $user["ranking"]              = trim( $line->find( 'td' )[0]->plaintext );
       $user["twitter_account_name"] = trim( $line->find( 'td' )[2]->find("span")[0]->plaintext );
       $user["twitter_name"]         = trim( $line->find( 'td' )[2]->find("span")[1]->plaintext );
       $user["bio"]                  = trim( $line->find( 'td' )[2]->find("div")[0]->plaintext );
       $user["follow"]               = trim( $line->find( 'td' )[3]->plaintext );
       $user["follower"]             = trim( $line->find( 'td' )[4]->plaintext );
       $user["tweet"]                = trim( $line->find( 'td' )[5]->plaintext );
       return $user;
   }
}

class Constant{
   public static function get_columns(){
      return array (
         'ranking',
         'twitter_account_name',
         'twitter_name',
         'bio',
         'follow',
         'follower',
         'tweet'
      );
   }
   public static function get_firstline(){
      return '"' . join('","', self::get_columns() ) . '"'. "\n";
   }

   public function print_lines($line){
      foreach( self::get_columns() as $column ){
         $line[$column] = str_replace('"', '""', $line[$column] );
      }
      echo mb_convert_encoding( '"'.join('","', $line ), 'sjis-win', 'UTF-8' ). '"' . "\n";  
   }

}
?>
