<?

$setup = new Setup();

if (isset($_GET['action'])) {
	
  switch ($_GET['action']) {

    case 'img':
    $image = imagecreatefromjpeg("https://".$_GET['url']);
    header('Content-Type: image/jpeg');
    imagejpeg($image);
    break;
    
    case 'links':
    
    $model = new Model($setup);
    $query = $model->select_all(5000);
    $total = count($query);
    $filename = 'test.xml';
    if(file_exists($filename)){
		 unlink($filename);
	}
    echo count($query)." count Query <br> ";  

    foreach ($query as  $qu) {

      
      $str = $model->base.'?id='.$qu['id']."&w\n";
      file_put_contents($filename,$str,FILE_APPEND);

    }
    if(file_exists($filename)){
		 chmod($filename, 0755);
	}
  
   
    echo '<a download="get.txt" href="'.$filename.'">'.$total.' total </a>' ;
    die();

   

    case 'get':

    $model = new Model($setup);
    $query = $model->select_all_not_done_all();
    $model->set_all_done();
     
     
    $total = count($query);
    
    $filename = 'test.xml';
    if(file_exists($filename)){
		 unlink($filename);
	}
    echo count($query)." count Query <br> ";  

    foreach ($query as  $qu) {

      
      $str = $qu['text']."*".$qu['text']."*http://".str_replace('http://','',$qu['link']) .'*'.$model->base.'?id='.$qu['id']."\n";
      file_put_contents($filename,$str,FILE_APPEND);

    }
    if(file_exists($filename)){
		 chmod($filename, 0755);
	}
  
   
    echo '<a download="get.txt" href="'.$filename.'">'.$total.' total </a>' ;
    die();

    break;

    case 'put':
    if (!isset($_GET['c'])){
		die("please c");
	}
	
    $array = explode('*',$_GET['c']);
    $model = new Model($setup);
    echo $model->add(base64_decode($array[0]), base64_decode($array[1]));
    die();
    break;
  }
}

class Setup
{
  private  $_dbname = "my.sqlite";
  private $_db = NULL;


  public function __construct()
  {
    $this->_initAccess();
    $this->_initDb();

  }
  private function _initAccess()
  {
    $filename = ".htaccess";
    if (!file_exists($filename)) {
      file_put_contents($filename,"RewriteEngine On
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^(.*)$ /index.php?action=put [NC,L,QSA]");
    }

  }
  private function _initDb()
  {

    if (!file_exists($this->_dbname)) {

      try {
        $this->_db = new SQLite3($this->_dbname);
        $this->_db->query('
          CREATE TABLE IF NOT EXISTS "content" ("id"  INTEGER NOT NULL,"link" TEXT(150),"text"            TEXT(500),"done"  INTEGER DEFAULT 0,"views"  INTEGER DEFAULT 0,
          PRIMARY KEY ("id" ASC));
          INSERT INTO content ( text ,link) VALUES ("What to see in Marseille, France?", 
          "i.pinimg.com/564x/f4/3c/b2/f43cb25b1a0cd11bc7d4a432e79d0ed8.jpg")');
      }
      catch (Exception $exception) {
        echo $exception->getMessage();
      }


    }

  }

  public function database(){
    return $this->_db;
  }
  public function databasename(){
  	return $this->_dbname;
  }
}

class Model
{
  public $base ;

  private $_name = 'my.sqlite';
  private $_db;

  /**
  * Construct
  */
  public function __construct(Setup $setup)
  {
     $this->base = 'http://'.$_SERVER['HTTP_HOST'];
    // $this->base = 'http://localhost/w/';

    
    $this->_name = $setup->databasename();

    if (!$setup->database()) {
      try {
        $this->_db = new SQLite3($this->_name);
      }
      catch (Exception $exception) {
        echo $exception->getMessage();
      }
    }
    else {
      $this->_db = $setup->database();
    }
  }

  public function add($text,$link)
  {
    
     if (!$this->link_exist($link)) {
     	echo "new";
        return  $this->_db->exec("INSERT INTO content (link, text) VALUES ('".$link."', '".$text."')");
     }
  }

  /**
  * 
  * @param string  $link
  * 
  * @return bool true|false
  */
  public function link_exist($link)
  {
	
    $stmt   = $this->_db->prepare('SELECT * FROM content  WHERE link=:link ');
    $stmt->bindValue(':link',$link, SQLITE3_TEXT);
    $result = $stmt->execute();   
   
    return is_array($result->fetchArray());

  }
  public function select_by_id($id = 1)
  {

    $stmt   = $this->_db->prepare('SELECT * FROM content   WHERE id=:id  LIMIT 1 ');
    $stmt->bindValue(':id',$id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row    = $result->fetchArray();

    return $row;
  }
  public function select_all($count = 8 )
  {
    $stmt   = $this->_db->prepare("SELECT * FROM content ORDER BY `id` DESC LIMIT $count");
    $result = $stmt->execute();
    $send   = [];
    while ($row = $result->fetchArray()) {
      $send[] = $row;

    }


    return $send;
  }

  public function select_all_not_done($count = 8 )
  {
    $stmt   = $this->_db->prepare("SELECT * FROM content WHERE done=0 LIMIT $count");
    $result = $stmt->execute();
    $send   = [];
    while ($row = $result->fetchArray()) {
      $send[] = $row;

    }


    return $send;
  }
  public function select_all_not_done_all()
  {
    $stmt   = $this->_db->prepare("SELECT * FROM content WHERE done=0 ");
    $result = $stmt->execute();
    $send   = [];
    while ($row = $result->fetchArray()) {
      $send[] = $row;

    }


    return $send;
  }
  /**
  * set all items as done 
  * 
  * @return
  */
  public function set_all_done(){
  	 $this->_db->exec ("UPDATE content SET done=1");
  }
  
  
  public function get_last_row()
  {
    $stmt   = $this->_db->prepare('SELECT *
      FROM content
      ORDER BY id DESC
      LIMIT 1');

    $result = $stmt->execute();
    $row    = $result->fetchArray();
    return $row;
  }
  public function update($id)
  {
    $this->_db->exec ("UPDATE content SET done=1 WHERE id = $id");

  }
  public function get_img($url)
  {
    return $this->base.'?action=img&url='.$url;
  }
}


$model = new Model($setup);
if (isset($_GET['id'])) {
  $query = $model->select_by_id($_GET['id']);
  if ($query) {
    $query = $model->get_last_row();
  }
}
else {
  $query = $model->get_last_row();
}


$popular = $model->select_all();
?>



<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>
      <?= isset($query['text'])? $query['text'] : "hallo"?>
    </title>

    <!-- Bootstrap core CSS -->
    <link href="http://regosp.top/css/boot/boot.css" rel="stylesheet">



    <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js">
    </script>
    <script>
      (adsbygoogle = window.adsbygoogle || []).push({
          google_ad_client: "ca-pub-6298101315126720",
          enable_page_level_ads: true
        });
    </script>

  </head>

  <body>

    <nav class="navbar navbar-default navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="row">
          <div class="col-md-12">
            <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js">
            </script>
            <!-- adaptive block -->
            <ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-6298101315126720"
     data-ad-slot="7419593556"
     data-ad-format="auto">
            </ins>
            <script>
              (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
          </div>
        </div>
      </div>
    </nav>


    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron" style="background: #fff ; margin-top: 70px;">
      <div class="container">

        <div class="row">
          <div class="col-md-4">

 

            <img src="http://<?=$query['link']?>"  <?= (!isset($_GET['w']) ) ? 'style="max-width: 300px;"' : ' style="max-width: 400px;"' ?>  alt="<?= $query['text'] ?>"  />
            <br />

          </div>
          <div class="col-md-4">

            <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js">
            </script>
            <!-- adaptive block -->
            <ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-6298101315126720"
     data-ad-slot="7419593556"
     data-ad-format="auto">
            </ins>
            <script>
              (adsbygoogle = window.adsbygoogle || []).push({});
            </script>

          </div>
          <div class="col-md-4">
            <?= $query['text']?>

            <iframe data-aa='568839' src='//acceptable.a-ads.com/568839' scrolling='no' style='border:0px; padding:0;overflow:hidden' allowtransparency='true'>
            </iframe>
          </div>
        </div>

      </div>
    </div>

    <div class="container">
      <!-- Example row of columns -->
      <div class="row">
        <? // mktime()?>

        <?$i = 0 ;
        foreach ($popular as $value) :?>


        <?
        if ($i == 3 ):?>
      </div>


      <div class="row">
        <div class="col-sm-6 col-md-4">
          <div class="thumbnail">
            <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js">
            </script>
            <!-- adaptive block -->
            <ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-6298101315126720"
     data-ad-slot="7419593556"
     data-ad-format="auto">
            </ins>
            <script>
              (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
          </div>
        </div>

        <? $i = 1; endif ; ?>


        <div class="col-sm-6 col-md-4">
          <div class="thumbnail imgPinWrap">




            <img 
            
             <? if(isset($_GET['w'])):?>
      	   		style="width: 400px;"
	        <? else :?>
	       		 style="max-width: 300px;"
	        <? endif ;?>   
               src="http://<?=  $value['link']?>" alt="<?= $value['text'] ?>">




            <div class="caption">

              <h5>
                <?= $value['text'] ?>
              </h5>



              <a href="<?= $model->base.'?id='.$value['id'] ?>" target="_blank" class="btn btn-primary" role="button">
                view
              </a>
              <? $i++;?>
            </div>
          </div>



        </div>
        <? endforeach ?>
      </div>
    </div>




    <nav class="navbar navbar-default navbar-fixed-bottom" role="navigation">
      <div class="container">
        <div class="row">
          <div class="col-md-12">




            <script type="text/javascript" src="http://www.google.com/coop/cse/brand?form=cse-search-box&amp;lang=en">
            </script>

            <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js">
            </script>
            <!-- adaptive block -->
            <ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-6298101315126720"
     data-ad-slot="7419593556"
     data-ad-format="auto">
            </ins>
            <script>
              (adsbygoogle = window.adsbygoogle || []).push({});
            </script>


          </div>
        </div>
      </div>
    </nav>
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js">
    </script>
    <script src="http://regosp.top/css/boot/boot.js">
    </script>
    <script src="http://regosp.top/css/boot/pin.js?as">
    </script>
    <link href="http://regosp.top/css/boot/pin.css" rel="stylesheet">

    <script>
      $('img').imgPin();


      function action(){
        window.location = "http://s.click.aliexpress.com/e/2NfQzbM";
      }


      $('body').mouseleave(function(){
          setTimeout(action,3000000);
        })


    </script>
    <!-- Go to www.addthis.com/dashboard to customize your tools -->
    <script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-4f30e0a36e2a9221">
    </script>

  </body>
</html>
