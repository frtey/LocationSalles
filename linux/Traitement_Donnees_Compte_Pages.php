<?php

//checks the type and size of sent file
if ($_FILES['file']['type'] != 'application/pdf') {
    echo "notPDF";
    return;
} else if ($_FILES['file']['size'] > 7000000) {
   echo "tooHeavy";
   return;
}

try {
   //If upload folder doesn't exist, creates it; moves uploaded file to new folder
   if (!file_exists('uploads')) {
      mkdir('uploads', 0777);
   }
   $_FILES['file']['name'] =  str_replace(' ', '_', $_FILES['file']['name']);
   move_uploaded_file($_FILES['file']['tmp_name'], 'uploads/' . $_FILES['file']['name']);

   //command line that asks Ghostscript for a page by page analysis of the uploaded file. Receives an array ($outputs), and a success code ($retour), where 0 is OK, anything else means an error occured
   exec("Ghostscript/gs-950 -o - -sDEVICE=inkcov uploads/" . $_FILES['file']['name'] . " 2>&1", $outputs, $retour);

   //Cleans the array
   $ProfilsColosPagesTemp = [];
   foreach($outputs as $output) {
      if(substr($output, -1) == "K") { //Only push in the array lines that finishes with 'K', meaning they contain color informations
         array_push($ProfilsColosPagesTemp, $output);
      }
   }

   //Create array of array
   $ProfilsColosPages = [];
   foreach($ProfilsColosPagesTemp as $ProfilTemp) {
      $ProfilTemp = explode(" ", $ProfilTemp);
      $Profil = [];
      //Deletes empty elements
      for($i = 0; $i < count($ProfilTemp); $i++) {
         if(!empty($ProfilTemp[$i])) {
            array_push($Profil, $ProfilTemp[$i]);
         }
      }
      array_push($ProfilsColosPages, $Profil);
   }

   //Color tests, count color pages and push these page numbers in an array
   $tabPagesCouleurs = [];
   $i = 1;
   $nbPages = count($ProfilsColosPages); //Total page number
   foreach($ProfilsColosPages as $Page) {
      if ($Page[0] > 0 || $Page[1] > 0 || $Page[2] > 0) {
         array_push($tabPagesCouleurs, $i);
      }
      $i++;
   }

   //Total of B&W pages
   $nbPagesNB = $nbPages - count($tabPagesCouleurs);

   //Creates array with keys, encodes it in json
   $tabFinal = [
      'NbPages' => $nbPages,
      'NbPagesC' => count($tabPagesCouleurs),
      'NbPagesNB' => $nbPagesNB,
      'TabPages' => $tabPages,
   ];
   $tabFinal = json_encode($tabFinal);
   print_r($tabFinal);
} catch(Expression $e) {
   echo "failure";
}
