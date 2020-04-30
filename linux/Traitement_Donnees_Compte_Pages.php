<?php
//Adapter l'application pour le site pro; rechercher solution pour plugin WordPress

//Vérification du type et de la taille du fichier envoyé
if ($_FILES['file']['type'] != 'application/pdf') {
    echo "notPDF";
    return;
} else if ($_FILES['file']['size'] > 7000000) {
   echo "tooHeavy";
   return;
}

try {
   //Si dossier d'upload n'existe pas, le crée; déplace le fichier uploadé vers le dossier d'uploads
   if (!file_exists('uploads')) {
      mkdir('uploads', 0777);
   }
   $_FILES['file']['name'] =  str_replace(' ', '_', $_FILES['file']['name']);
   move_uploaded_file($_FILES['file']['tmp_name'], 'uploads/' . $_FILES['file']['name']);

   //Ligne de commande interrogeant GhostScript, récupérant un tableau de sortie de commande ($outputs), et un code d'execution de commande ($retour), où 0 est bien, et tout autre chiffre indique problème
   exec("Ghostscript/gs-950 -o - -sDEVICE=inkcov uploads/" . $_FILES['file']['name'] . " 2>&1", $outputs, $retour);

   //Nettoyage du tableau
   $ProfilsColosPagesTemp = [];
   foreach($outputs as $output) {
      if(substr($output, -1) == "K") { //Ne prend que les éléments se terminant par "K" (donc tout ceux avec les profils colorimétriques de la page) => Tout les éléments du tableau inutiles sont laissés de coté
         array_push($ProfilsColosPagesTemp, $output);
      }
   }

   //Création tableau de tableau pour parcours ultérieur
   $ProfilsColosPages = [];
   foreach($ProfilsColosPagesTemp as $ProfilTemp) {
      $ProfilTemp = explode(" ", $ProfilTemp);
      $Profil = [];
      //Suppression des éléments vides
      for($i = 0; $i < count($ProfilTemp); $i++) {
         if(!empty($ProfilTemp[$i])) {
            array_push($Profil, $ProfilTemp[$i]);
         }
      }
      array_push($ProfilsColosPages, $Profil);
   }

   //Tests couleurs, compte du nombre de pages couleurs et peuplement d'un tableau $tabPages avec numéros des pages couleurs
   $tabPagesCouleurs = [];
   $i = 1;
   $nbPages = count($ProfilsColosPages); //Nombre de pages total
   foreach($ProfilsColosPages as $Page) {
      if ($Page[0] > 0 || $Page[1] > 0 || $Page[2] > 0) {// && (($Page[0] != $Page[1]) || ($Page[2] != $Page[1]))) { //La partie commentée ajoute des conditions permettant de considérer le grayscale comme non-couleur. Produit des erreurs si une page possède la même répartition de CMYK
         array_push($tabPagesCouleurs, $i);
      }
      $i++;
   }

   //Nombre de pages noir et blanc
   $nbPagesNB = $nbPages - count($tabPagesCouleurs);

   //Création d'un tableau avec clés, puis transformation en JSON renvoyé à la page formulairePDF.ph pour affichage
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
