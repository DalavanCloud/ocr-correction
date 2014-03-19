<?php
require_once 'djvu.class.php';

class DjVuView extends DjVu {

  private $image_width = 800;
  private $image_url = '';

  public function setImageWidth($width) {
    $this->image_width = $width;
    return $this;
  }

  public function setImageURL($url) {
    $this->image_url = $url;
    return $this;
  }

  public function addFontmetrics() {
    // Compute font sizes
    foreach ($this->page_structure->regions as $region)
    {
      foreach ($region->paragraphs as $paragraph)
      {

        $fontmetrics = new stdclass;

        $count = 0;
        $last_baseline = 0;
        foreach ($paragraph->lines as $line)
        {

          if ($count > 0 && isset($line->fontmetrics->baseline))
          {
            $fontmetrics->linespacing[] = $line->fontmetrics->baseline - $last_baseline;
          }
          $count++;
          $last_baseline = $line->fontmetrics->baseline;

          if (isset($line->fontmetrics->ascender)) { $fontmetrics->ascender[] = $line->fontmetrics->ascender; }
          if (isset($line->fontmetrics->capheight)) { $fontmetrics->capheight[] = $line->fontmetrics->capheight; }
          if (isset($line->fontmetrics->descender)) { $fontmetrics->descender[] = $line->fontmetrics->descender; }
        }

        $paragraph->fontmetrics = new stdclass;

        if (isset($fontmetrics->linespacing))
        {
          $paragraph->fontmetrics->linespacing = $this->mean($fontmetrics->linespacing);
        }
        else
        {
          $paragraph->fontmetrics->linespacing = -1;
        }
        if (isset($fontmetrics->ascender))
        {
          $paragraph->fontmetrics->ascender = $this->mean($fontmetrics->ascender);
        }
        if (isset($fontmetrics->capheight))
        {
          $paragraph->fontmetrics->capheight = $this->mean($fontmetrics->capheight);
        }
        if (isset($fontmetrics->descender))
        {
          $paragraph->fontmetrics->descender = $this->mean($fontmetrics->descender);
        }

      }
    }
    return $this;
  }

  public function addLines(){
    $scale = $this->image_width/$this->page_structure->bbox[2];

    $line_counter = 0;

    foreach ($this->page_structure->regions as $region){

      foreach ($region->paragraphs as $paragraph){
        // font height
        $fontsize = 0;

        // Compute font height based on capheight of font
        // e.g for Times New Roman we divide by 0.662
        if (isset($paragraph->fontmetrics->capheight))
        {
          $fontsize = $paragraph->fontmetrics->capheight/0.662;
        }

        $linespacing = $paragraph->fontmetrics->linespacing;
        if ($linespacing != -1)
        {
          $linespacing = round($linespacing/$this->page_structure->dpi * 72);
        }

        $fontsize *= $scale;

        // text
        foreach ($paragraph->lines as $line){
          $ocr_line = new stdclass;
          $ocr_line->id = "line" . $line_counter++;
          $ocr_line->fontsize = $fontsize;
          $ocr_line->bbox = $line->bbox;
          $ocr_line->text = preg_replace('/\s+$/', '', $line->text);

          $this->page_structure->lines[] = $ocr_line;
        }
      }
    }
    return $this;
  }

  private function mean($a){
    $average = 0;
    $n = count($a);
    $sum = 0;
    foreach ($a as $x) {
      $sum += $x;
    }
    $average = $sum/$n;
    return $average;
  }

  public function createHTML() {
    $doc = new DOMDocument('1.0');

    $doc->preserveWhiteSpace = false;
    $doc->formatOutput = true;

    $scale = $this->image_width/$this->page_structure->bbox[2];

    $ocr_page = $doc->appendChild($doc->createElement('div'));
    $ocr_page->setAttribute('class', 'ocr_page');
    $ocr_page->setAttribute('title', 
      'bbox 0 0 ' . ($scale * $this->page_structure->bbox[2]) . ' ' . ($scale * $this->page_structure->bbox[1])
      . '; image ' . $this->image_url
      );

    foreach ($this->page_structure->lines as $line){
      $ocr_line = $ocr_page->appendChild($doc->createElement('div'));

      $ocr_line->setAttribute('id', $line->id);
      $ocr_line->setAttribute('class', 'ocr_line');
      $ocr_line->setAttribute('contenteditable', 'true');
      $ocr_line->setAttribute('class', 'ocr_line');
      $ocr_line->setAttribute('style', 'font-size:' . $line->fontsize . 'px;line-height:' . $line->fontsize . 'px;position:absolute;left:' . ($line->bbox[0] * $scale) . 'px;top:' . ($line->bbox[3] * $scale)  . 'px;min-width:' . ($scale *($line->bbox[2] - $line->bbox[0])) . 'px;height:' . ($scale *($line->bbox[1] - $line->bbox[3])) . 'px;');
      $ocr_line->setAttribute('data-bbox', 'bbox ' . ($line->bbox[0] * $scale) . ' ' . ($line->bbox[3] * $scale)  . ' ' . ($scale *$line->bbox[2])  . ' ' . ($scale *$line->bbox[1]) );

      // original OCR
      $ocr_line->setAttribute('data-ocr', $line->text);

      $ocr_line->appendChild($doc->createTextNode($line->text));
    }

    return $doc->saveHTML();
  }

}

?>