<?php
//=============================================================================================
// This progrm is released under GPLv3 (GNU通用公共許可證)	https://www.gnu.org/licenses/gpl-3.0.txt
//
// calibrePage - a simple PHP content server for calibre book library.
//  * show books from calibre library
//  * simple, self-contain, single php file
// 
// Log: 
//	2019/10/23  ck  v.0.30 poup or open book in new page. remove jQuery
//	2019/10/30  ck  v.0.50 show tags with count, corss linking. 
//	2020/07/10  ck  v.0.70 en/cn/b5 version, submit to github
//=============================================================================================
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-cache, no-store, must-revalidate");

//-----for debug-----
//error_reporting(-1);
//ini_set('display_errors', 'On');

//----------- all options for the page ---------------
$folder = 'calibre';			// calibre 的文件目录
$title  = '书库名称';
$subtitle = ' - 小标题';
$maxBookPerPage = 30;
$about  = 'Simple content server for Calibre Book Library. (v0.70@202007)';
$footer = '所有书籍收集自互联网。<b>私人收藏，禁止下载。</b>';

//----------------- end of options -------------------

$format = $_REQUEST['fm'];
$bookid = $_REQUEST['id'];
$keyword = $_REQUEST['key'];
$tag = $_REQUEST['tag'];

$userIP = getenv('REMOTE_ADDR');
$thisPrg = $_SERVER['SCRIPT_NAME'];
$calibre = new PDO("sqlite:$folder/metadata.db");

//=== read file for download if has format parameter
if (isset($format)) {
  $sql = " select path, d.name, title, author_sort from books a, data d where a.id=d.book and a.id=$bookid "; 
  $result = $calibre->query($sql);
  foreach($result as $row) { 
    $file = "$folder/" . $row['path'] . '/' . $row['name'] . '.' . strtolower($format);
    $name = $row['title'] . '(' . $row['author_sort'] . ').' . strtolower($format);
    if (file_exists($file)) {
      header('Content-Type: application/octet-stream');
      header("Content-Transfer-Encoding: Binary");
      header("Content-Disposition: attachment; filename=\"$name\"");
      header('Content-Length: ' . filesize($file));
      header("Pragma: public");
      header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
      ob_clean();
      flush();
      readfile($file);
      file_put_contents( "$folder.log", date("Y-m-d H:i:s")."@$userIP: download $bookid: $name \n", FILE_APPEND );
    } else {
      echo "not exist! \n $file => $name ";
    }
    exit;
  } 
}

//=== uncommet the following code to enable access log
//file_put_contents( "$folder.log", date("Y-m-d H:i:s") . "@$userIP: " . urldecode($_SERVER['REQUEST_URI']) ."\n", FILE_APPEND );
?>

<!DOCTYPE html>
<html>

<head>
  <TITLE><?=$title?></TITLE>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">        
  <meta http-equiv="x-ua-compatible" content="ie=9">
	<meta http-equiv="Cache-Control" content="no-store" />	
  <meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="shortcut icon" href="images\book-52.png" type="image/x-icon"/>
  <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
</head>

<style>
A:link { text-decoration: none;  }
A:hover { background-color:skyblue; text-decoration:none; border-bottom:1px dashed; }
#header { background-color:#333; color:#eef; padding:10px; }
#heading { vertical-align:middle; text-shadow:1px 1px 1px grey; font-weight:700; }
#msg { position:fixed; width:100%; bottom:0px; padding:4px; background-color:#333; color:#eee; font-size:12px;}
.book-card {  margin:4px; width:210px; padding:6px; height:380px; max-width:47%; }
.book-image { margin:4px; width:250px; padding:6px; }  
.book-cover { width:100%; height:250px; }  
.book-name { color:#111; font-weight:600; font-size:18px }  
</style>

<body>

<div id=header class="w3-bar">
  <span id=heading onclick="window.location='/'"><?=$title?></span><?=$subtitle?>
  <span class="w3-right">
    <form>
      <input name=key size=12 placeholder='书名/作者/出版社' value="<?=$keyword?>"/> 
      <input type=submit value="查询"/>
    </form>
    
  </span>
</div> 

<div id=content> 
  <?
  // show tags and counts
  $sql = "SELECT 'tags' as type,  name,  (SELECT COUNT(id) FROM books_tags_link WHERE tag=tags.id) count  FROM tags order by 3 desc";
  //$sql .= "union all SELECT 'publisher', name,  (SELECT COUNT(id) FROM books_publishers_link WHERE publisher=publishers.id) count  FROM publishers ";
  $result = $calibre->query($sql);
  echo "<div class='w3-container'><p><b>[标签]</b> ";
  foreach ($result as $row) { 
    echo " <a href='$thisPrg?tag=". $row['name'] . "'>".  $row['name'];
    echo "<sup>(" . $row['count'] . ")</sup></a> ";
  }            
  echo "</p></div><div class='w3-row'>";
  
  $sql = "SELECT id, uuid, title, author_sort, path, pubdate ";
  $sql .= " , (select group_concat(name, ' | ') from authors x where x.id in (select author from books_authors_link y where book=a.id)) as author";
  $sql .= " , (select group_concat(name, ' | ') from publishers x where x.id in (select publisher from books_publishers_link y where book=a.id)) as publisher";
  $sql .= " , (select group_concat(name, ' | ') from tags x where x.id in (select tag from books_tags_link y where book=a.id)) as tags";
  $sql .= " , (select text from comments x where x.book=a.id) as description ";
  $sql .= " , (select val from identifiers x where x.book=a.id and type='isbn') as isbn ";
  $sql .= " , (select group_concat(format) from data x where x.book=a.id) as format ";
  $sql .= " from books a ";
  if (isset($keyword)) {
    $sql .= " where title like '%$keyword%' or  author_sort like '%$keyword%' ";
    $sql .= " or (select group_concat(name, ' | ') from publishers x where x.id in (select publisher from books_publishers_link y where book=a.id)) like '%$keyword%'";
  } else if (isset($bookid)) {
    $sql .= " where id=$bookid ";
  } else if (isset($tag)) {
    $sql .= " where exists (select 1 from books_tags_link y where book=a.id and tag=(select id from tags where name='$tag'))";
  }
  $sql .= " order by timestamp desc LIMIT $maxBookPerPage ";
  $result = $calibre->query($sql);

  $cnt = 0;
  foreach($result as $row) { 
    
      if (isset($bookid)) {
        echo "<div class='w3-container'><div class='w3-col w3-border book-card'> ";
        echo "<img class='book-cover' id='cover$cnt' src='/calibre/" . $row['path'] . "/cover.jpg' /></div>";
        echo "<div class='w3-col m8'><h3> " . $row['title'] . "</h3>";
        echo "作者: "; $flag=0; 
        $items = explode( ' | ', $row['author'] ); 
        foreach ($items as $item) echo (($flag++)==0? '' : '|')." <a href='$thisPrg?key=$item'>$item</a> ";
        echo "<br>出版社: "; $flag=0; 
        $items = explode( ' | ', $row['publisher'] ); 
        foreach ($items as $item) echo (($flag++)==0? '' : '|')." <a href='$thisPrg?key=$item'>$item</a> ";
        echo "<br>出版日期: " . substr($row['pubdate'],0,10);
        echo "<br>ISBN: " . $row['isbn'] . " (<a target=_NEW href='https://book.douban.com/isbn/" . $row['isbn'] . "'>豆瓣</a>)";
        echo "<br>标签: ";   $flag=0; 
        $items = explode( ' | ', $row['tags'] ); 
        foreach ($items as $item) echo (($flag++)==0? '' : '|')." <a href='$thisPrg?tag=$item'>$item</a> ";
        echo "</div> <div class='w3-col m12 w3-border'>" .  $row['description'] . "</div>";
        echo "<div class='w3-col m12'><br> ";
        $ebooks = explode( ',', $row['format'] );
        foreach ($ebooks as $item) echo " <button onclick='download(this)' id=dl".$row['id'].">$item</button> ";
        echo "<button class='w3-right' onclick='history.length==1? window.close() : history.back()'>Back</button><br><br>";
        echo "</div></div>";
      } else {
        $cnt++; 
        echo "<div class='w3-col w3-border book-card' id='book$cnt'>";
        echo "<img class='book-cover' id='cover$cnt' onclick='showBook($cnt)' src='/calibre/" . $row['path'] . "/cover.jpg' />";
        echo "<p class='book-name' id='name$cnt' ><a href='$thisPrg?id=".$row['id']."'>".$row['title'] . "</a></p>";
        echo "<p class='book-author' id='author$cnt'>" . $row['author'] . "</p>";
        echo "<div class='w3-hide' id='pubr$cnt'>" . $row['publisher'] . "</div>";
        echo "<div class='w3-hide' id='pubd$cnt'>" . $row['pubdate'] . "</div>";
        echo "<div class='w3-hide' id='isbn$cnt'>". $row['isbn'] . "</div>";
        echo "<div class='w3-hide' id='format$cnt'>" . $row['format'] . "</div>";
        echo "<div class='w3-hide' id='desc$cnt'>" . $row['description'] . "</div>";
        echo "<div class='w3-hide' id='tags$cnt'>" . $row['tags'] . "</div>";
        echo "<div class='w3-hide' id='bookid$cnt'>" . $row['id'] . "</div>";
        echo "</div>";
      }  
  }  
?>

  </div><br/><br/>
</div>

<div id=msg>
  <?=$footer?> 
  <span class="w3-right"><a href='cops'>OPDS 目录</a> | <a href=# onclick="about()">About 关于</a></span>
</div>

<div id="dialog" class="w3-modal">
  <div class="w3-modal-content" id="dialog-content"></div>
</div>

</body>

<script>
var $$ = function(id) { return document.getElementById(id) }

function showBook(id) {

  var bookid = $$('bookid'+id).innerHTML
  var format = $$('format'+id).innerHTML.split(',')
  var tags = $$('tags'+id).innerHTML.split(' | ')
  var pubr = $$('pubr'+id).innerHTML.split(' | ')
  var author = $$('author'+id).innerHTML.split(' | ')

  var i, html = '<span onclick="$$(\'dialog\').style.display=\'none\'" class="w3-button w3-display-topright">&times;</span>'
  html += '<div class="w3-border w3-container w3-row">'
  html += "<div class='w3-col book-image'><img width=100% src='" + $$('cover'+id).src + "'></div>";
  html += "<div class='w3-col m8'><h3> " + $$('name'+id).innerHTML + '</h3>'

  for (i=0; i<author.length; i++) html+=(i==0?'<br>作者: ':' | ')+'<a href="<?=$thisPrg?>?key='+author[i]+'">'+author[i]+'</a>'
  for (i=0; i<pubr.length; i++) html+=(i==0?'<br>出版社: ':' | ')+'<a href="<?=$thisPrg?>?key='+pubr[i]+'">'+pubr[i]+'</a>'
  html += "<br>出版日期: "+ $$('pubd'+id).innerHTML.substr(0,10)
  html += "<br>ISBN: "+ $$('isbn'+id).innerHTML 
  html += ' (<a target=_NEW href="https://book.douban.com/isbn/' +$$('isbn'+id).innerHTML + '">豆瓣</a>)'
  for (i=0; i<tags.length; i++) html+=(i==0?'<br>标签: ':' | ')+'<a href="<?=$thisPrg?>?tag='+tags[i]+'">'+tags[i]+'</a>'
  html += "</div><div class='w3-col m12 w3-border'>" + $$('desc'+id).innerHTML + "</div>";
  html += "<div class='w3-col m12'><br> ";
  for (i=0; i<format.length; i++) html+=' <button onclick="download(this)" id=dl'+bookid+'>'+format[i]+'</button> '
  html += '<button class="w3-right" onclick="$$(\'dialog\').style.display=\'none\'">Close</button><br><br></div></div>';

  $$('dialog-content').innerHTML = html
  $$('dialog').style.display = 'block'

}

function download(btn) {
  window.open( '<?=$thisPrg?>?id='+btn.id.substr(2)+'&fm='+btn.innerText )
}

function about() {
  alert( "<?=$about?>")
}  
</script>
</html>
