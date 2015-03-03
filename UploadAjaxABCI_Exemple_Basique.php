<?php
/*UploadAjaxABCI_Basique Version 2.4 
Utilise le fichier "UploadAjaxABCI_Php_Load_Basique.php" comme destination Ajax
*/
session_start();
header('Content-type: text/html; charset=UTF-8');

$UpAbci_max_fragment = 6291456;// taille maximale des fragments (doit être inférieur ou égal à la configuration 'upload_max_filesize' du serveur). Les fichiers inférieurs à cette taille n'auront pas de sauvegarde temporaire.

$UpAbci_uniqid_form = hash("sha256",uniqid(rand(), true));

// Jeton de formulaire (token);
$_SESSION['UploadAjaxABCI'][$UpAbci_uniqid_form] = 1;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<title>UploadAjaxABCI : upload de fichiers par fragmentation avec Html5 et Ajax/Jquery/Php</title>
<script src="jquery.js"></script>
<script src="UploadAjaxABCI_DEV.js"></script>

<script type="text/javascript">
// Initialisation de la classe javascript (identifiant formulaire, destination ajax, identifiant réponse)
var up = new UploadAjaxABCI('#form_files','UploadAjaxABCI_Php_Load_Basique.php','#reponse_upload');

// configuration optionnelle (exemple). Voir le fichier "UploadAjaxABCI.php" pour plus d'infos.
up.config.ajax_delai = 750;// en milliseconde, espacement entre deux requêtes ajax (100 par défaut)

// Démarrage de la fonction, DOM chargé
$(function(){up.Start()});
</script>

</head>
<body style="font-family:Arial, Helvetica, sans-serif; font-size:1em;width:650px;margin:3em auto 0 auto">
<div>
<form id="form_files" action="#" method="post">
 <fieldset style="border:2px solid #000;border-radius:5px;padding:1em">
    <legend>Upload avec configuration de base : nom, progression graphique, status et arrêt</legend>
     <input type="file" name="upload_simple" multiple="multiple" />
     <input type="hidden" value="<?=$UpAbci_max_fragment?>" name="UpAbci_max_fragment" />
     <input type="hidden" value="<?=$UpAbci_uniqid_form?>" name="UpAbci_uniqid_form" />
     <input type="submit" value="Envoyer"/>
     
     <!-- Bloc conteneur du retour d'informations -->
     <div id="reponse_upload">
     	<!--information du fichier que l'on affiche uniquement si un fichier est soumis, pour éviter la barre de progression graphique vide si soumission du formulaire sans fichier-->
         <div class="UpAbci_infos_upload" style="display:none;margin-top:1em;" data-upabcicss-submit-file="display:block;">
         	- <span class="UpAbci_nom"></span><!-- nom du fichier -->
            <progress class="UpAbci_progressionG"></progress> <!-- progression graphique -->
            <span class="UpAbci_status" data-upabcicss-result-ok="color:green;font-weight:bold">en attente</span><!-- status (important sinon pas de retour d'information en cas d'erreur) -->
            <span class="UpAbci_arret" style="color:red;cursor:pointer;font-size:0.8em;float:right" data-upabcicss-result="display:none">stop</span>
         </div>
 	</div>
 </fieldset>
</form>
</div>
</body>
</html>
