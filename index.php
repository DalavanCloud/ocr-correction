<?php

// Create editable HTML for DjVu

require_once (dirname(__FILE__) . '/djvu/djvu_structure.php');


//--------------------------------------------------------------------------------------------------
/*
	Create HTML with hOCR for one page
*/
function export_html_fragement_dom($page, $image_url='')
{
	$doc = new DOMDocument('1.0');
	
	// Nice output
	$doc->preserveWhiteSpace = false;
    $doc->formatOutput = true;	

	$scale = $page->image->width/$page->page->bbox[2];
	
	$ocr_page = $doc->appendChild($doc->createElement('div'));
	$ocr_page->setAttribute('class', 'ocr_page');
	$ocr_page->setAttribute('title', 
		'bbox 0 0 ' . ($scale * $page->page->bbox[2]) . ' ' . ($scale * $page->page->bbox[1])
		. '; image ' . $image_url
		);
	$ocr_page->setAttribute('style', 'font-family:serif;');

	foreach ($page->lines as $line)
	{
		$ocr_page->appendChild($doc->createComment('line'));	
		$ocr_line = $ocr_page->appendChild($doc->createElement('div'));
		
		$ocr_line->setAttribute('id', $line->id);	
		
		$ocr_line->setAttribute('class', 'ocr_line');	

		$ocr_line->setAttribute('contenteditable', 'true');	
		
		$ocr_line->setAttribute('class', 'ocr_line');	
		$ocr_line->setAttribute('style', 'font-size:' . $line->fontsize . 'px;line-height:' . $line->fontsize . 'px;position:absolute;left:' . ($line->bbox[0] * $scale) . 'px;top:' . ($line->bbox[3] * $scale)  . 'px;min-width:' . ($scale *($line->bbox[2] - $line->bbox[0])) . 'px;height:' . ($scale *($line->bbox[1] - $line->bbox[3])) . 'px;');	
		
		// hOCR
		$ocr_line->setAttribute('title', 'bbox ' . ($line->bbox[0] * $scale) . ' ' . ($line->bbox[3] * $scale)  . ' ' . ($scale *$line->bbox[2])  . ' ' . ($scale *$line->bbox[1]) );					

		// handle edits
		$ocr_line->setAttribute('onfocus', 'entering(this)');					
		$ocr_line->setAttribute('onblur', 'leaving(this)');		
		
		// original OCR
		$ocr_line->setAttribute('data-ocr', $line->text);					
	
		$ocr_line->appendChild($doc->createTextNode($line->text));
	}
	
	// Box to contain OCR image
	$div = $ocr_page->appendChild($doc->createElement('div'));
	$div->setAttribute('id', 'ocr_image_container');
	$div->setAttribute('style', 'display:none;box-shadow: 4px 4px 4px rgba(64,64,64,0.5);position:absolute;top:0px;left:0px;border:1px solid rgb(192,192,192);width:100%;background:white;height:0px;');
	
	// OCR image (hidden)
	$img = $ocr_page->appendChild($doc->createElement('img'));	
	$img->setAttribute('id', 'ocr_image');
	$img->setAttribute('class', 'ocr_image');
	$img->setAttribute('src', $image_url);
	$img->setAttribute('style', 'left:0px;top:40px;position:absolute;display:none;');
	
	$ocr_page->appendChild($doc->createComment('Adjust font sizes so that text fits within bounding boxes'));	

	// https://coderwall.com/p/_8jxgw
	$script = $ocr_page->appendChild($doc->createElement('script'));
	$src = 
'$(".ocr_line").each(function(i, obj) {
    
    while ($(this).prop("scrollHeight") > $(this).prop("offsetHeight"))
    {
    	var elNewFontSize;
        elNewFontSize = (parseInt($(this).css("font-size").slice(0, -2)) - 1) + "px";
        return $(this).css("font-size", elNewFontSize);
    }
 });';
 	$script->appendChild($doc->createTextNode($src));
	

	return $doc->saveHTML();
}


//--------------------------------------------------------------------------------------------------

$PageID = 34570741;
//$PageID = 34565801;


// LXV.—On a new Banded Mungoose from Somaliland
//$PageID = 16002437;
$PageID = 16002438;

$xml_filename 	= 'examples/' . $PageID . '.xml';
$image_filename = 'examples/' . $PageID . '.png';

$page_data = structure($xml_filename);
extract_font_sizes($page_data);

//print_r($page_data);

$obj = new stdclass;
$obj->image = new stdclass;
$obj->image->width = 800;
$obj->page = new stdclass;
$obj->page->bbox = $page_data->bbox;
$obj->lines = array();

lines($page_data, $obj);

//print_r($obj);

//echo export_html_dom($obj, '');


// make a page

echo '<html>
<head>
<meta charset="utf-8">
<meta name="ocr-capabilities" content="ocr_carea ocr_line ocr_page ocr_par">

			<!-- Le styles -->
			<link href="assets/css/bootstrap.css" rel="stylesheet">
			<link href="assets/css/bootstrap-responsive.css" rel="stylesheet">
		
			<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
			<!--[if lt IE 9]>
			  <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
			<![endif]-->            


<script src="js/jquery.js"></script>
<style type="text/css">
	body {
	   margin:0px;
	   padding:0px;
	   background-color: #E9E9E9;
	}
	
	.ocr_page {
		background-color: white;
		/* box-shadow:2px 2px 10px #aaaaaa; */
		/* border:1px solid black; */
		position:relative;
		/* left: 20px;
		top: 20px; */
		width: 800px;
		height: 1316.9313957418px;
	}
	
	
	/* http://blog.vjeux.com/2011/css/css-one-line-justify.html */
	/* This ensures single line of text is justified */
	.ocr_line {
	  text-align: justify;
	}
	.ocr_line:after {
	  content: "";
	  display: inline-block;
	  width: 100%;
	}

	</style>
	
	<script src="js/pouchdb-1.1.0.min.js"></script>
	
	<script>
		var pageId = ' . $PageID . ';
	
		var remote = false;
		
		var couchdb;
		
		// local
		couchdb = "http://127.0.0.1:5984/ocr";
		
		// Cloudant
		couchdb = "http://<username>:<password>@rdmpage.cloudant.com:5984/ocr";
		
	   	if (remote) {
	   		// Write direct to CouchDB
			var db = new PouchDB(couchdb);
		} else {
			// Write to PouchDB, then replicate
			var db = new PouchDB("ocr");
			var remoteCouch = couchdb;
		}
	
		var before_text = "";
		
		function entering(element) {
			before_text = $(element).html();
				
			// Display image
			var title = $(element).prop("title");
			var parts = title.split(" ");
	
			// Clip to just this part of the image
			var clip = "rect(" + parts[2] + "px, " + parts[3] + "px, " + parts[4] + "px, " + parts[1] + "px)";
			$("#ocr_image").css("clip", clip);
			$("#ocr_image").show();
	
			/* Move image container */
			$("#ocr_image_container").css("top", -parts[2] + "px");
	
			// bottom of element (text line being edited)
			var bottom =  $(element).offset().top + $(element).outerHeight(true);
			
			bottom -= 30; // adjust for toolbar height
	
			$("#ocr_image_container").css("top", bottom + "px");
			$("#ocr_image_container").css("height",  (parts[4] - parts[2]) + 10 + "px");
			$("#ocr_image_container").show();
		}

		function leaving(element) {
			//console.log($(element).html());
			
			var after_text = $(element).html();
			
			if (after_text != before_text)
			{
			
				var html = $("#edits").html();
			
				//html += after_text + "<hr />";
				
				html += \'<div class="media">\';
				html += \'  <a class="pull-left" href="#">\';
    			html += \'    <img class="media-object" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI2NCIgaGVpZ2h0PSI2NCI+PHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiBmaWxsPSIjZWVlIj48L3JlY3Q+PHRleHQgdGV4dC1hbmNob3I9Im1pZGRsZSIgeD0iMzIiIHk9IjMyIiBzdHlsZT0iZmlsbDojYWFhO2ZvbnQtd2VpZ2h0OmJvbGQ7Zm9udC1zaXplOjEycHg7Zm9udC1mYW1pbHk6QXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWY7ZG9taW5hbnQtYmFzZWxpbmU6Y2VudHJhbCI+NjR4NjQ8L3RleHQ+PC9zdmc+" alt="...">\';
  				html += \'  </a>\';
  				html += \'  <div class="media-body">\';
    			html += \'    <h4 class="media-heading">User</h4>\';
				html += after_text;
				html += \'   </div>\';
				html += \'</div>\';
				html += \'<div style="clear:both;" />\';
			
				$("#edits").html(html);
				
				// Ten digit timestamp (Javascript is 13, PHP is 10, sigh)
				var timestamp = new Date().getTime();
				
				var timestamp10 = String(timestamp);
				timestamp10 = timestamp10.substring(0,10);
				
				
				// store
				db.post({
					type: "edit",
					time: parseInt(timestamp10),
  					pageId: pageId,
  					lineId: $(element).attr("id"),  
  					ocr: $(element).attr("data-ocr"),
  					text: after_text
				}, function(err, response) { });
				
				
				if (!remote) {
					// replicate
				 	db.replicate.to(remoteCouch);
				}
				

			}
		}
	</script>
	
</head>
<body>';


echo '	   <div class="navbar navbar-fixed-top">
			  <div class="navbar-inner">
				<div class="container">
				  <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				  </a>
				  <a class="brand" href=".">OCR</a>
				  <!--
				  <div class="nav-collapse">
					<ul class="nav">
					  <li class="active"><a href=".">Home</a></li>
					  <li><a href="?page=about">About</a></li>
					</ul>
				  </div> -->
				  <!--/.nav-collapse -->
				  
				</div>
			  </div>
			</div>
		
		
			<div style="margin-top:40px;" class="container-fluid">
				<div class="row-fluid">
					<div class="span8">';
					
						echo export_html_fragement_dom($obj, $image_filename);
					echo '</div>
					<div class="span4" id="edits" style="padding-right:40px;height:400px;overflow:auto;">
					
					</div>
				</div>';
				




echo '</div>';

echo '<!--Adjust font sizes so that text fits within bounding boxes-->
    <script>$(".ocr_line").each(function(i, obj) {
    
    while ($(this).prop("scrollHeight") > $(this).prop("offsetHeight"))
    {
    	var elNewFontSize;
        elNewFontSize = (parseInt($(this).css("font-size").slice(0, -2)) - 1) + "px";
        return $(this).css("font-size", elNewFontSize);
    }
 });</script>
 
	<script>
		/* grab edits */
		var url = "./edits.php?pageId=" + pageId;
		$.getJSON(url,
			function(data){
				if (data.status == 200) {
					if (data.results.length != 0) {
						// load edits
						for (var i in data.results) {
							$("#" + data.results[i].lineId).html(data.results[i].text);
						}
					}
				}
			});
	</script>
	
</body>
</html>';

?>