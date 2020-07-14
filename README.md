### Introduction

[calibrePage](https://campodesktop.com/calibrePage) is simple PHP content server for calibre.

* Single php file
* Self-contained, no depandence
* Responsive design, fit for mobile and desktop

It is a **single file** in very simple PHP coding, provides the following features

* List latest books
* List books by labels, author or publisher
* Search book by keyword
* show details of selected books
* download books

### Installation & Configuration

(1) Just simply copy any of below php file to your php server

* index-en.php		// English verion
* index-cn.php		// Simplified Chinese Version
* index-b5.php		// Traditional Chinese Version

(2) and copy calibre library to folder "calibre", and it works

(3) rename index-??.php to index.php, and edit the php for the following customization

      //----------- all options for the page ---------------
      $folder = 'calibre';			// folder of calibre files
      $title  = 'MyCalibre';
      $subtitle = ' - my book library';
      $maxBookPerPage = 10;
      $about  = 'Simple content server for Calibre Book Library. (v0.70@202007)';
      $footer = 'All books collected from internet. <b>for private use only</b>';
      //----------------- end of options -------------------

### Sample Site

* [English Version](http://zi5.epizy.com/index-en.php)
* [Simplified Chinese](http://zi5.epizy.com/index-cn.php)
* [Traditional Version](http://zi5.epizy.com/index-b5.php)

### Release

This progrm is released under [GPLv3](https://www.gnu.org/licenses/gpl-3.0.txt) (GNU通用公共許可證)



 
