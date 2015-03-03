<?php
/*UploadAjaxABCI_Php_Load_Basique Version 2.4c
require 'UploadAjaxABCIServeur.php';
*/

// Vous devez vérifier l'existence de ces dossier ou changer ces adresses 
/*------------------------------------------------------*/
$dossier_destination = 'Destination_Upload/';
$dossier_temporaire = 'Upload_Temp/';
/*------------------------------------------------------*/

session_start();
// Voir le fichier "UploadAjaxABCI_Php_Load.php" pour plus d'exemples détaillés.

// maximise les possibilités de reprise d'upload en cas d'arrêt intempestif.
ignore_user_abort(true);

header('Content-type: text/html; charset=UTF-8');

// Charge la classe php d'upload
require 'UploadAjaxABCIServeur.php';

//Initialisation de la classe php (dossier de destination, dossier temporaire)
$up = new UploadAjaxABCIServeur($dossier_destination, $dossier_temporaire);

// getUniqidForm() renvoie l'identifiant de formulaire pour la vérification ci-dessous
$unidid_form = $up->getUniqidForm();

// Il est conseillé de ne pas supprimer cette ligne car c'est le token qui assure que ce script est appelé depuis le formulaire
if(!(isset($unidid_form,$_SESSION['UploadAjaxABCI'][$unidid_form]))) $up->exitErreurFichier('Identifiant de formulaire non valide. Rafraîchissez la page'); 

$up->Upload();
// Le processus d'upload est dissocié de celui du transfert, pour permettre de traiter le fichier complet (par exemple pour faire des redimensionnements d'images en php etc.) avant de le déplacer vers son emplacement définitif (c.f "UploadAjaxABCI_Php_Load.php" pour des exemples). 
$up->Transfert();

$up->exitReponseAjax();
?>
