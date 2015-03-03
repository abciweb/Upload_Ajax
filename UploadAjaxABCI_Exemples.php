<?php
/*UploadAjaxABCI Version 2.4

Utilise le fichier "UploadAjaxABCI_Php_Load.php" comme destination Ajax

-------------------------------------------------------- 
ATTENTION : 

Pour faire des tests sur un serveur local il est conseillé d'utiliser le paramètre config.ajax_delai avec une valeur assez importante pour espacer l'envoi des requêtes. Je ne pourrai pas être tenu pour responsable si une sollicitation importante du système donne le coup de grâce d'un disque dur en cours d'agonie.

Les exemples ci-dessous sont paramétrés avec un délai de 750 millisecondes.
--------------------------------------------------------

PARAMETRAGE DE BASE MINIMUM :

1/ Javascript

- Initialisation de la fonction en indiquant le formulaire cible, l'adresse de destination de la requête ajax et le bloc html des informations ex :
var upload = new UploadAjaxABCI('#form','UploadAjaxABCI_Php_Load.php','#reponse_upload');

// configuration optionnelle (cf doc détaillée)
upload.config.ajax_delai = 750; // en milliseconde
//...

// Terminer l'initialisation de la fonction, DOM chargé, avec la fonction Start()
$(function()
{	
	upload.Start();
});

PLUS D'INFORMATIONS dans le mode d'emploi de la classe javascript situé dans le dossier "Mode_emploi"

2/ Formulaire

- En complément d'un (ou plusieurs) champ de type file, la classe javascript cherchera des champs nommés "UpAbci_uniqid_form" et "UpAbci_max_fragment". Si non trouvés, le script utilisera des valeurs par défaut (paramétrables) mais il est conseillé de les renseigner dans le formulaire, par sécurité concernant la variable "UpAbci_uniqid_form" qui fait office de jeton témoin d'envoi du formulaire, et pour faciliter la communication php/javascript concernant "UpAbci_max_fragment" qui défini la taille des fragments.

- Vous pouvez ajouter d'autres champs dans le formulaire, ils seront automatiquement transmis au script php par la requête ajax. Côté php vous pourrez les récupérer en utilisant la fonction "urldecode()" : $ma_variable = urldecode($_POST['ma_variable']);


3/ Html bloc d'information

- Utiliser les class pour faire afficher les informations désirées 
- Utiliser les dataCss pour appliquer des styles en fonction des évènements en cours 
cf. mode d'emploi et exemples ci-dessous


4/ Destination de la requête ajax : script d'upload php "UploadAjaxABCI_Php_Load.php" 

Configuration de la classe php d'upload "UploadAjaxABCIServeur". Renseignez au minimum :
- Adresse du dossier de destination
- Adresse du dossier de sauvegarde temporaire

+ d'info pour la configuration de la classe php d'upload dans les fichiers "UploadAjaxABCI_Php_Load_Basique.php" et "UploadAjaxABCI_Php_Load.php"
*/

session_start();
header('Content-type: text/html; charset=UTF-8');

function Unique_Id()
{
        return function_exists('openssl_random_pseudo_bytes')? hash("sha256",openssl_random_pseudo_bytes("128", $cstrong)) : hash("sha256",uniqid(rand(), true));
}

function Return_Octets($val)
{
	$val = str_replace(array(',',' '),array('.',''),$val);
	$val = rtrim($val, "oO");

	$last = strtolower(substr($val,-1));

	switch($last)
	{
		case 't':  $val *= 1024;
		case 'g':  $val *= 1024;
		case 'm': $val *= 1024;
		case 'k':  $val *= 1024;
	}
	return $val;
}

$taille_fragment = Return_Octets('8M'); /* Taille maximale des fragments de fichier. Détermine la fréquence de sauvegarde du fichier en cours d'upload (le fichier temporaire est sauvegardé à chaque fragment complet envoyé) et donc le nombre de requêtes ajax nécessaires à l'upload total (une requête envoie un fragment). Utile pour limiter la taille des fragments pour les serveur qui ont une valeur "upload_max_filesize" importante sinon les sauvegardes seraient peu fréquentes. Par ailleurs chaque fragment est lu en mémoire pour être compilé dans le fichier temporaire, il faut donc auusi prendre en compte l'occupation mémoire. On peut augmenter cette valeur suivant la vitesse d'upload et les capacités du serveur. Cette valeur doit évidemment être inférieure à "upload_max_filesize" sinon elle n'est pas prise en compte dans le code ci-dessous.

Note : la taille réelle des fragments sera inférieure de 10 Ko par rapport à la valeur indiquée car je décompte arbitrairement cette valeur pour tenir compte des éléments textuels de la requête ajax.
*/


$upload_max_filesize = ini_get('upload_max_filesize');
$upload_max_filesize = !empty($upload_max_filesize) ? $upload_max_filesize : '4M';// si ini_get est désactivé 4 Mo = valeur minimum courante 
$upload_max_filesize = Return_Octets($upload_max_filesize);
$UpAbci_max_fragment = !empty($taille_fragment) && $upload_max_filesize > $taille_fragment ? $taille_fragment : $upload_max_filesize;

$UpAbci_uniqid_form = Unique_Id();

// Jeton de formulaire
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
	
// destination de la requête ajax
var script_php = 'UploadAjaxABCI_Php_Load.php';


// formulaire 1
var Up = new UploadAjaxABCI('#form_files1',script_php,'#reponse_upload1');

// configuration optionnelle (exemples)
Up.config.ajax_delai = 750; // en milliseconde, espacement entre deux requêtes ajax
Up.config.requete_upload_fin = true; // permet d'indiquer à php que tous les fichiers ont été traités (utile uniquement côté serveur si besoin).
Up.config.extensions_autorisees = ['avi','divx','mp4','mkv','jpg','jpeg','png']; // Extensions autorisées (un contrôle de sécurité supplémentaire est fait côté serveur). Inutile de mettre les variantes en majuscules.

// Plus d'informations sur les options de configuration dans le mode d'emploi.

// Démarrage de la fonction, DOM chargé
$(function(){Up.Start()});



// formulaire 2
var toto = new UploadAjaxABCI('#form_files2',script_php,'#reponse_upload2');

// configuration optionnelle (exemples)
toto.config.ajax_delai = 750; // en milliseconde, espacement entre deux requêtes ajax
toto.config.delai_refresh_infos_text = 3; // rafraîchissement textuel espacé de 3 secondes
toto.config.requete_sans_fichier = false; // le formulaire ne sera pas envoyé si aucun fichier n'est proposé en téléchargement
toto.config.max_filesize_utilisateur = '100 Mo'; // Limite la taille des fichiers à 100 Mo (un contrôle de sécurité supplémentaire est fait côté serveur)

toto.info.status.ok = "C'est tout bon !"; // Modification de la réponse textuelle du status si téléchargement ok

// Démarrage de la fonction, DOM chargé
$(function(){toto.Start()});



// formulaire 3
var titi = new UploadAjaxABCI('#form_files3',script_php,'#reponse_upload3');

// configuration optionnelle (exemples)
titi.config.ajax_delai = 750; // En milliseconde, espacement entre deux requêtes ajax
titi.config.confirme_reprise_sauvegarde = true; // Une confirmation sera demandée en cas de reprise d'upload 

// Démarrage de la fonction, DOM chargé
$(function(){titi.Start()});



// formulaire 4
var tata = new UploadAjaxABCI('#form_files4',script_php,'#reponse_upload4');

// configuration optionnelle (exemples)
tata.config.ajax_delai = 750;// en milliseconde, espacement entre deux requêtes ajax
// Attention il conviendrait de baisser (ou mettre à zéro) les valeurs ci-dessous pour les smartphones.
tata.config.max_weight_apercu = 8; // en Mo poids maximal de l'image pour la prévisualisation. Si au moins une image dépasse cette valeur aucune vignette ne sera affichée pour préserver la cohérence de l'affichage.
tata.config.total_max_weight_apercu = 14; // en Mo En cas d'upload multiple poids total maximum de toutes les photos. Au delà les vignettes ne seront pas affichées

tata.info.status.encours = 'en cours';

// Démarrage de la fonction, DOM chargé
$(function(){tata.Start()});



// formulaire 5 (idem formulaire 4 avec modification du status ok pour n'afficher que le retour php)
var form5 = new UploadAjaxABCI('#form_files5',script_php,'#reponse_upload5');

// configuration optionnelle (exemples)
form5.config.ajax_delai = 750;// en milliseconde, espacement entre deux requêtes ajax

// Attention il conviendrait de baisser (ou mettre à zéro) les valeurs ci-dessous pour les smartphones.
form5.config.max_weight_apercu = 8; // en Mo poids maximal de l'image pour la prévisualisation. Si au moins une image dépasse cette valeur aucune vignette ne sera affichée pour préserver la cohérence de l'affichage.
form5.config.total_max_weight_apercu = 14; // en Mo En cas d'upload multiple poids total maximum de toutes les photos. Au delà les vignettes ne seront pas affichées.

form5.info.status.encours = 'en cours';

form5.info.status.ok = ""; // Modification de la réponse textuelle du status si téléchargement ok. En mettant vide seules les informations envoyées par php en retour de requête ajax seront s'affichées.

// Démarrage de la fonction, DOM chargé
$(function(){form5.Start()});


</script>
<style type="text/css">
html {
	font-family:"Trebuchet MS", Arial, Helvetica, sans-serif;
	font-size:0.8em;
}
.formajaxabci input[type=submit] {
	width:100px;
	font-size:1.2em;
}
.formajaxabci input[type=button] {
	font-family:"Trebuchet MS", Arial, Helvetica, sans-serif;
	font-size:0.8em;
}
.formajaxabci p, .reponse_form p {
	margin:0;
	padding:0;
}
.reponse_form progress {
	width:150px;
}
</style>
</head>

<body>

<h3 style="margin-top:2em">
Chaque formulaire supporte l'upload multiple et possède des comportements d'affichage spécifiques commentés dans le code source.</h3><br><br>

<form class="formajaxabci" id="form_files1" action="#" method="post"> 
 <fieldset style="color:#900;border:2px solid #900;border-radius:5px;padding:1em">
    <legend>Upload 1 : limité aux extensions avi, divx, mp4, mkv, jpg, jpeg, png</legend>
     <input type="file" multiple="multiple" name="upload_0" />
     <input type="file" multiple="multiple" name="upload_1" /><!-- pour l'exemple les fichiers téléchargés depuis ce bouton seront téléchargés dans le dossier "Destination_Upload2". C'est une option configurable côté serveur dans le fichier "UploadAjaxABCI_Php_Load.php" -->
     <input type="hidden" value="<?=$UpAbci_uniqid_form?>" name="UpAbci_uniqid_form" />
     <input type="hidden" value="<?=$UpAbci_max_fragment?>" name="UpAbci_max_fragment" />
     <input type="submit" value="Envoyer"  style="color:#900"  />
 </fieldset>
</form>

<!-- bloc conteneur des résultats, s'affichera avec ou sans fichiers joints -->
<div class="reponse_form" id="reponse_upload1" style="display:none;margin-bottom:10px;margin-top:10px;border-bottom:2px solid #900;padding:1em;" data-upabcicss-submit="display:block;" data-upabcicss-submit-file="display:block;">
	
    <!-- retour d'informations générales du formulaire alimenté par retour ajax. Le fichier php de traitement (UploadAjaxABCI_Php_Load.php) renvoie actuellement 'Aucun fichier traité' si aucun fichier n'a été soumis -->
    <div class="UpAbci_infos_form" style="display:none;color:#900;font-size:large" data-upabcicss-fin-traitement="display:block;"></div>

	<!-- bloc conteneur du résultat d'un fichier (sera dupliqué par la classe si upload multiple). -->
    <div class="UpAbci_infos_upload" style="margin-top:10px;display:none;" data-upabcicss-submit-file="display:block;">
     
     	<!-- lien arrêt, affiché en transparence 0.5 si résultat (= upload terminé) -->
		<span style="cursor:pointer;color:#900" class="UpAbci_arret" data-upabcicss-result="cursor:default;opacity:0.5">arrêt</span>
       
         <!-- nom du fichier -->
		 - <span class="UpAbci_nom"></span>
        
         <!-- taille du fichier -->
        <span class="UpAbci_taille">0 Mo</span>
       
         <!-- progression graphique -->
        <progress class="UpAbci_progressionG"></progress>
        
         <!-- pourcentage -->
      	<span class="UpAbci_pourcentage">0 %</span>
      
         <!-- progression textuelle -->
     	- en cours : <span class="UpAbci_progressionT">0 Mo</span>
        
         <!-- sauvegarde effectuée -->
       - sauvegardé : <span class="UpAbci_sauvegarde">0 Mo</span>
        
       	 <!-- temps passé -->
       - temps passé : <span class="UpAbci_temps_passe">0 s</span>
        
       	 <!-- temps restant estimé -->
       - temps restant : <span class="UpAbci_temps_restant">indéfini</span>
        
         <!-- status (en attente, ok, erreur, arrêt) -->
       - status : <span class="UpAbci_status">en attente</span>
    </div>
    
    <!-- on affiche le lien "arrêter tout" uniquement si des fichiers ont été soumis et on l'efface à la fin du traitement des fichiers --> 
 	<div class="UpAbci_arreter_tout" style="display:none;width:100px;margin:auto;font-size:0.9em;color:#900;cursor:pointer" data-upabcicss-submit-file="display:block;margin-top:15px;" data-upabcicss-fin-traitement="display:none">
    Arrêter tout
    </div>
    
	<!-- pourra s'afficher même si aucun fichier n'a été soumis, ce qui ne serait pas le cas en employant data-upabcicss-fin-upload qui signale la fin du traitement des fichiers uploadés -->
    <div style="margin-top:15px;display:none;text-align:center;font-weight:bold" data-upabcicss-fin-traitement="display:block">
    Traitement terminé
    </div> 
    
    <!-- on affiche cette ligne d'information si une sauvegarde est trouvée pour au moins un fichier à la fin du traitement (à noter qu'en cas de téléchargement complet d'un fichier il n'existe plus de sauvegarde)-->
	<div style="margin-top:10px;display:none" data-upabcicss-sauvegarde-ft="display:block">
    <span style="font-weight:bold">Note : </span>les fichiers partiellement sauvegardés peuvent être complétés pendant 24h suivant la dernière sauvegarde effectuée.
    </div> 
       
</div>




<!-- Autres exemples pour montrer différents retours possibles. Ci-dessous la liste des fichiers et les informations sont inclues dans le formulaire pour une mise en forme plus simple de type monobloc. -->
<div style="margin-top:2em">
<form class="formajaxabci" id="form_files2" action="#" method="post">
 <fieldset style="color:#090;border:2px solid #090;border-radius:5px;padding:1em;background:#FBFBFB">
    <legend>Upload 2 : limitation des fichiers à 100 Mo</legend>
     <input type="file" multiple="multiple" name="upload_2" />
     <input type="hidden" value="<?=$UpAbci_uniqid_form?>" name="UpAbci_uniqid_form" />
     <input type="hidden" value="<?=$UpAbci_max_fragment?>" name="UpAbci_max_fragment" />
     <input type="submit" value="Envoyer" style="color:#090" />

<!--  
- Une option javascript (.config.requete_sans_fichier = false) configure la classe javascript pour ne pas envoyer le formulaire si aucun fichier n'est joint.
- Une option javascript (.config.max_filesize_utilisateur = 100 Mo) configure la classe javascript pour ne pas envoyer les fichiers de plus de 100 Mo.
- Je choisi par ailleurs de ne pas faire afficher de réponse si aucun fichier n'est téléchargé.

Dans la ligne ci-dessous data-upabcicss-submit="display:none" sert pour réinitialiser l'affichage par défaut de ce bloc en cas d'utilisations successives de ce même formulaire sans rafraîchissement de la page. Cette rénitialisation par rapport au style originel (style="display:none") n'est nécessaire que sur ce bloc conteneur car les blocs CONTENUS (dans ce bloc) sont clônés avec leur valeurs originales et réinsérés à chaque envoi du formulaire.

Notez que dans cet exemple, cette réinitialisation sert à éviter qu'une réponse (vide) s'affiche dans le cas de figure où l'on envoie un formulaire vide (sans fichiers joints) à la suite d'un précédent envoi non vide. Car si le premier envoi est vide, data-upabcicss-submit-file n'affiche pas le bloc. Mais une fois affiché, c'est le rôle de data-upabcicss-submit="display:none" de le réinitialiser pour les envois suivants. Cette réinitialisation sert également à cacher ce bloc à l'envoi du formulaire en cas de fichiers sélectionnés mais tous arrêtés (volontairement avec les boutons d'arrêt) avant l'envoi du formulaire.
-->
    <div class="reponse_form" id="reponse_upload2" style="display:none;margin-top:1.5em;" data-upabcicss-submit-file="display:block;" data-upabcicss-submit="display:none">
    
        <div class="UpAbci_infos_upload" style="margin-top:10px">
        
            <!-- Dans cet exemple la réponse du formulaire est inclue dans le formulaire. Dans ce cas ne pas utiliser la balise <button> qui est interprétée comme un bouton de soumission de formulaire, contrairement à input de type "button" qui lui ne perturbera pas la soumission du formulaire.
            Le bouton d'arrêt s'affiche tant que le téléchargement n'est pas terminé -->   
            <input type="button" class="UpAbci_arret" style="cursor:pointer;color:#666" data-upabcicss-result="cursor:default;opacity:0.5" value="arrêt" />
            
            <!-- nom du fichier -->
            - <span class="UpAbci_nom"></span>
            
            <!-- taille du fichier -->
            <span class="UpAbci_taille">0 Mo</span>
           
            <!-- progression graphique s'affiche tant que l'upload n'est pas terminé et ok -->
            <progress class="UpAbci_progressionG" data-upabcicss-result-ok="display:none"></progress>
            
             <!-- pourcentage -->
            <span class="UpAbci_pourcentage">0%</span>
            
            <!-- progression textuelle s'efface dès que l'upload est terminé, quelque soit le résultat -->       
            <span data-upabcicss-result="display:none">- en cours : </span><span class="UpAbci_progressionT" data-upabcicss-result="display:none">0 Mo</span>
            
            <!-- sauvegarde effectuée s'affichera uniquement si résultat upload partiel (erreur ou arrêt mais sauvegarde existante) -->
            <span style="display:none" data-upabcicss-result-partiel="display:inline">- sauvegardé : </span><span class="UpAbci_sauvegarde" style="display:none" data-upabcicss-result-partiel="display:inline">0 Mo</span>
            
            - temps passé <span class="UpAbci_temps_passe">0 s</span>
            
            <!-- temps restant s'efface dès que l'upload est terminé, quelque soit le résultat -->
            <span data-upabcicss-result="display:none">- temps restant : </span><span class="UpAbci_temps_restant" data-upabcicss-result="display:none">0 s</span>
            
             <!-- status, couleur par défaut en attente, en vert si résultat ok, en rouge si erreur -->
            - status : <span class="UpAbci_status" data-upabcicss-result-ok="color:#090" data-upabcicss-result-erreur="color:red">en attente</span>       
        </div>
        
        <!-- on peut afficher le bouton "arrêter tout" dans tous les cas puisque le formulaire a été configuré :
        1/ avec une option javascript pour ne pas envoyer de requête en absence de fichiers 
        2/ avec data-upabcicss-submit="display:none" dans le bloc conteneur pour effacer l'affichage du bloc conteneur en cas d'utilisations successives de ce même formulaire sans rafraichissement de la page.
        -->
        <div style="margin-top:10px;">
        <input type="button" value="Arrêter tout" class="UpAbci_arreter_tout" style="display:block;width:100px;margin:auto;font-size:0.8em;color:#666;cursor:pointer;opacity:1" data-upabcicss-fin-upload="cursor:default;opacity:0.5" />
        </div>
    
        <!-- s'affichera uniquement en fin d'upload si des fichiers ont été uploadés-->
        <div style="margin-top:10px;display:none;text-align:center;font-weight:bold" data-upabcicss-fin-upload="display:block">
        Upload terminé
        </div> 
        
        <div style="margin-top:10px;display:none" data-upabcicss-sauvegarde-ft="display:block">
        <span style="font-weight:bold">Note : </span>les fichiers partiellement sauvegardés peuvent être complétés pendant 24h suivant la dernière sauvegarde effectuée.
        </div>
    </div>
 </fieldset>
</form>
</div>



<!-- Autre exemple pour montrer différents retours possibles -->
<div style="margin-top:2em">
<form class="formajaxabci" id="form_files3" action="#" method="post">
 <fieldset style="color:#03C; border:2px solid #03C;border-radius:5px;padding:1em; background-color: #F9F9FB">
    <legend>Upload 3 : demande de confirmation en cas de reprise d'upload</legend>
    <p>
     <input type="file" multiple="multiple" name="upload" />
     <input type="hidden" value="<?=$UpAbci_uniqid_form?>" name="UpAbci_uniqid_form" />
     <input type="hidden" value="<?=$UpAbci_max_fragment?>" name="UpAbci_max_fragment" />
     <input type="submit" value="Envoyer" style="color:#03C" />
    </p>
    <!--  
    - Une option javascript (.config.confirme_reprise_sauvegarde = true) configure la classe pour afficher une boite de confirmation javascript en cas de reprise d'upload (dans les exemples au dessus la reprise est atomatique)
    -->
    <div class="reponse_form" id="reponse_upload3" style="display:none;margin-top:1.5em;" data-upabcicss-submit-file="display:block;" data-upabcicss-submit="display:block;">
    
        <!-- retour d'informations générales du formulaire alimenté par retour ajax. Le fichier php de traitement (UploadAjaxABCI_Php_Load.php) renvoie pour l'instant 'Aucun fichier traité' si aucun fichier n'a été soumis -->
        <div class="UpAbci_infos_form" style="display:none;" data-upabcicss-fin-traitement="display:block;"></div>
        
        <!-- on affiche la ligne si des fichiers sont soumis -->
        <div class="UpAbci_infos_upload" style="margin-top:10px;display:none" data-upabcicss-submit-file="display:block">
        
            <!-- Bouton d'arrêt s'affiche tant que le téléchargement n'est pas terminé, si terminé simulation css disable -->   
            <input type="button" class="UpAbci_arret" style="cursor:pointer;display:inline-block;color:#666" data-upabcicss-result="cursor:default;opacity:0.5" value="arrêt" />
            
            <!-- nom du fichier -->
            - <span class="UpAbci_nom"></span>
            
            <!-- taille du fichier -->
            <span class="UpAbci_taille">0Mo</span>
            
           <!-- progression graphique s'affiche uniquement lors de la progression ou si une sauvegarde existe :
           
           - data-upabcicss-sauvegarde permet d'afficher la barre de progression si une sauvegarde existe pour un fichier en attente de traitement (en cas d'upload multiple)
           - data-upabcicss-encours affiche la barre de progression si le fichier est en cours de téléchargement
           - data-upabcicss-result cache la barre de progression dès que le traitement du fichier est terminé
           - data-upabcicss-result-partiel permet d'afficher la barre de progression si une sauvegarde existe à la fin du traitement du fichier (interrompu par un arrêt volontaire ou une erreur, à noter qu'en cas de téléchargement complet du fichier il n'existe plus de sauvegarde).       
           -->
            <progress style="display:none" class="UpAbci_progressionG" data-upabcicss-encours="display:inline" data-upabcicss-result="display:none" data-upabcicss-result-partiel="display:inline" data-upabcicss-sauvegarde="display:inline"></progress>
    
            <!-- temps_restant s'affiche uniquement lors de la progression -->
            <span style="display:none" data-upabcicss-encours="display:inline" data-upabcicss-result="display:none"> temps restant : </span><span class="UpAbci_temps_restant" style="display:none" data-upabcicss-encours="display:inline" data-upabcicss-result="display:none">0s</span>
            
             <!-- sauvegarde partielle s'affiche uniquement si sauvegarde existante ou résultat upload partiel (erreur ou arrêt mais sauvegarde existante) -->
            <span style="display:none" data-upabcicss-sauvegarde="display:inline" data-upabcicss-result="display:none" data-upabcicss-result-partiel="display:inline;color:#03C">- sauvegarde partielle </span><span class="UpAbci_sauvegarde" style="display:none" data-upabcicss-sauvegarde="display:inline" data-upabcicss-result="display:none" data-upabcicss-result-partiel="display:inline">0Mo</span>
            
            <!-- le status s'affiche en couleur par défaut pour un téléchargement en attente ou en cours, en couleur noir gras si arrêt, et si erreur en couleur rouge. Le status ok est traité séparément plus loin par l'affichage d'un carré bleu.
            Explication : par défaut le span affichant le status affiche "en attente". Pas de modification de style quand le fichier est en cours d'upload donc le status "en cours" s'affichera. Dès l'affichage d'un résultat le status est caché avec display none et il est réaffiché si le résultat est erreur ou arrêt. Noter que l'ordre d'écriture dans le html n'a pas d'incidence, les styles sont appliqués suivant l'ordre d'application des styles indiqué dans le mode d'emploi (chapitre 4/B ->Ordre d'application des styles).
            -->
            <span class="UpAbci_status" data-upabcicss-result-erreur="display:inline;color:red" data-upabcicss-result-arret="display:inline;color:#333;font-weight:bold" data-upabcicss-result="display:none">en attente</span>
                     
            <!-- si téléchargement ok on affiche un carré bleu -->       
            <span style="display:none;width:15px;height:15px;margin-left:1.5em;" data-upabcicss-result-ok="display:inline-block;background-color:#09F"></span>
            
        </div>
     
        <div style="margin-top:10px;display:none" data-upabcicss-fin-traitement="display:none" data-upabcicss-submit-file="display:block">
        <input type="button" value="Arrêter tout" class="UpAbci_arreter_tout" style="display:block;width:100px;margin:auto;font-size:0.8em;color:#666" />
        </div>
        
        <!-- fin de traitement du formulaire (avec ou sans fichiers) -->
        <div style="margin-top:10px;display:none;text-align:center;font-weight:bold" data-upabcicss-fin-traitement="display:block">
        Traitement terminé
        </div> 
        
        <div style="margin-top:10px;display:none" data-upabcicss-sauvegarde-ft="display:block">
        <span style="font-weight:bold">Note : </span>les fichiers partiellement sauvegardés peuvent être complétés pendant 24h suivant la dernière sauvegarde effectuée.
        </div> 
            
    </div>

 </fieldset>
</form>
</div>




<!-- Bouton d'envoi à l'intérieur de la réponse du formulaire (dans ce cas particulier la sélection de fichiers est nécessaire pour avoir accès au bouton "envoyer" mais on pourrait faire autrement). 

La prévisualisatoin des images est disponible dans la limite des options de configurations javascript "max_weight_apercu" et "total_max_weight_apercu" et avec les dimensions maximales "max_width_apercu" et "max_height_apercu" (c.f configuration javascript "formulaire 4" plus haut, et mode d'emploi chapitre 2 -> "Options de configuration") 

Par ailleurs le temps restant est affiché avec "data-upabcicss-temps-restant-requis" donc il ne s'affiche que si le temps restant estimé est supérieur à la variable de configuration .config.temps_restant_requis. Cela permet de ne faire afficher ce bloc d'information que pour les "gros fichiers".
-->
<div style="margin-top:2em">
<form class="formajaxabci" id="form_files4" action="#" method="post">
 <fieldset style="color:#333;border:2px solid #333;border-radius:5px;padding:1em;background:#FBFBFB">
    <legend>Upload 4 : aperçu des images sous certaines conditions</legend>
     <input type="file" multiple="multiple" name="upload" />
     <input type="hidden" value="<?=$UpAbci_uniqid_form?>" name="UpAbci_uniqid_form" />
     <input type="hidden" value="<?=$UpAbci_max_fragment?>" name="UpAbci_max_fragment" />

    <div class="reponse_form" id="reponse_upload4" style="display:none;margin-top:1.5em;" data-upabcicss-submit-file="display:block;">
    
 		<!-- retour d'informations générales du formulaire alimenté par retour ajax. Le fichier php de traitement (UploadAjaxABCI_Php_Load.php) renvoie pour l'instant 'Aucun fichier traité' si aucun fichier n'a été soumis. Dans le contexte de ce formulaire, puisque le bouton d'envoi du formulaire ne s'affiche qu'après avoir renseigné le bouton parcourir, il faut arrêter les fichiers avec le bouton "arrêt" avant la soumission du formulaire pour obtenir ce message -->
        <div class="UpAbci_infos_form" style="display:none;color:red;font-size:large" data-upabcicss-fin-traitement="display:block;"></div>
           
        <div class="UpAbci_infos_upload" style="margin-top:10px">
        
            <!-- Le bouton d'arrêt s'affiche tant que le téléchargement n'est pas terminé --> 
            <input type="button" class="UpAbci_arret" style="cursor:pointer;color:#666;vertical-align:text-bottom" data-upabcicss-result="cursor:default;opacity:0.5" value="arrêt" />
            
            <span class="UpAbci_apercu_img"></span>
				
                     
            <!-- nom du fichier -->
            &nbsp;&nbsp;<span class="UpAbci_nom"></span>
            
            <!-- taille du fichier -->
            <span class="UpAbci_taille">0 Mo</span>
           
            <!-- progression graphique -->
            <progress class="UpAbci_progressionG" data-upabcicss-result-ok="display:none"></progress>
                                                
             <!-- status -->
            &nbsp;<span class="UpAbci_status" data-upabcicss-encours="color:#090" data-upabcicss-result="display:none" data-upabcicss-result-arret="color:red;display:inline" data-upabcicss-result-erreur="color:red;display:inline">en attente</span>

            <!-- temps restant -->
            <span style="display:none" data-upabcicss-temps-restant-requis="display:inline" data-upabcicss-result="display:none"> temps restant : </span><span class="UpAbci_temps_restant" style="display:none" data-upabcicss-temps-restant-requis="display:inline" data-upabcicss-result="display:none">0 s	</span>
                 
            <!-- sauvegarde effectuée -->
            <span style="display:none" data-upabcicss-result="display:none" data-upabcicss-sauvegarde="display:inline" data-upabcicss-result-partiel="display:inline;color:#090;"> sauvegardé : </span><span class="UpAbci_sauvegarde" style="display:none" data-upabcicss-result="display:none" data-upabcicss-result-partiel="display:inline" data-upabcicss-sauvegarde="display:inline"></span>
                        
            
            <!-- si téléchargement ok on affiche un rond vert -->
           	<span style="display:none;width:13px;height:13px;margin-left:1em;border-radius:50%;border:1px solid #999;background-color:#0C0" data-upabcicss-result-ok="display:inline-block;"></span> 
             
            <!-- si erreur on affiche un rond rouge -->       
            <span style="display:none;width:13px;height:13px;margin-left:1em;border-radius:50%;border:1px solid #999;background-color:#F00;" data-upabcicss-result-erreur="display:inline-block"></span>                                     
       
        </div>
        
         <!-- arrêter tout + bouton envoyer (enlever le data-upabcicss-submit="display:none" du bouton submit si vous ne souhaitez pas le voir disparaître à la soumission du formulaire) -->
        <div style="margin-top:2em;">
        <input type="button" value="Arrêter tout" class="UpAbci_arreter_tout" style="float:left;font-size:0.8em;color:#666;cursor:pointer;opacity:1" data-upabcicss-fin-upload="cursor:default;opacity:0.5" /><input type="submit" value="Envoyer" style="color:#333;display:block;margin:auto;" data-upabcicss-submit="display:none" />
        </div>
    
        <!-- s'affichera uniquement en fin de traitement du formulaire-->
        <div style="margin-top:10px;display:none;text-align:center;font-weight:bold" data-upabcicss-fin-traitement="display:block">
        Upload terminé
        </div> 
        
        <div style="margin-top:10px;display:none" data-upabcicss-sauvegarde-ft="display:block">
        <span style="font-weight:bold">Note : </span>les fichiers partiellement sauvegardés peuvent être complétés pendant 24h suivant la dernière sauvegarde effectuée.
        </div>
    </div>
 </fieldset>
</form>
</div>


<!--Idem upload 4 précédent avec affichage du nouveau nom en cas de renommage du fichier (nettoyage ou renommage dû à un doublon sur le serveur) -->
<div style="margin-top:2em">
<form class="formajaxabci" id="form_files5" action="#" method="post">
 <fieldset style="color:#333;border:2px solid #333;border-radius:5px;padding:1em;background:#FBFBFB">
    <legend>Upload 5 : idem précédent avec information du nouveau nom de fichier si celui-ci a été renommé</legend>
     <input type="file" multiple="multiple" name="upload5" />
     <input type="hidden" value="<?=$UpAbci_uniqid_form?>" name="UpAbci_uniqid_form" />
     <input type="hidden" value="<?=$UpAbci_max_fragment?>" name="UpAbci_max_fragment" />

    <div class="reponse_form" id="reponse_upload5" style="display:none;margin-top:1.5em;" data-upabcicss-submit-file="display:block;">
    
 		<!-- retour d'informations générales du formulaire alimenté par retour ajax. Le fichier php de traitement (UploadAjaxABCI_Php_Load.php) renvoie pour l'instant 'Aucun fichier traité' si aucun fichier n'a été soumis. Dans le contexte de ce formulaire, puisque le bouton d'envoi du formulaire ne s'affiche qu'après avoir renseigné le bouton parcourir, il faut arrêter les fichiers avec le bouton "arrêt" avant la soumission du formulaire pour obtenir ce message -->
        <div class="UpAbci_infos_form" style="display:none;color:red;font-size:large" data-upabcicss-fin-traitement="display:block;"></div>
           
        <div class="UpAbci_infos_upload" style="margin-top:10px">
        
            <!-- Le bouton d'arrêt s'affiche tant que le téléchargement n'est pas terminé --> 
            <input type="button" class="UpAbci_arret" style="cursor:pointer;color:#666;vertical-align:text-bottom" data-upabcicss-result="cursor:default;opacity:0.5" value="arrêt" />
            
            <span class="UpAbci_apercu_img"></span>
				
                     
            <!-- nom du fichier -->
            &nbsp;&nbsp;<span class="UpAbci_nom"></span>
            
            <!-- taille du fichier -->
            <span class="UpAbci_taille">0 Mo</span>
           
            <!-- progression graphique -->
            <progress class="UpAbci_progressionG" data-upabcicss-result-ok="display:none"></progress>
             
             <!-- Côté javascript (voir plus haut) le retour ok a été défini vide avec form5.info.status.ok= ""; Ainsi seuls les messages provenant de php (UploadAjaxABCI_Php_Load.php) seront affichés. Dans le script php on renvoie un message si le fichier a été renommé qui s'affichera donc ici en marron (color:#916C00). -->                                 
             <!-- status -->
            &nbsp;<span class="UpAbci_status" data-upabcicss-encours="color:#090" data-upabcicss-result="display:none" data-upabcicss-result-arret="color:red;display:inline" data-upabcicss-result-erreur="color:red;display:inline" data-upabcicss-result-ok = "color:#916C00;display:inline" style="">en attente</span>

            <!-- temps restant -->
            <span style="display:none" data-upabcicss-temps-restant-requis="display:inline" data-upabcicss-result="display:none"> temps restant : </span><span class="UpAbci_temps_restant" style="display:none" data-upabcicss-temps-restant-requis="display:inline" data-upabcicss-result="display:none">0 s	</span>
                 
            <!-- sauvegarde effectuée -->
            <span style="display:none" data-upabcicss-result="display:none" data-upabcicss-sauvegarde="display:inline" data-upabcicss-result-partiel="display:inline;color:#090;"> sauvegardé : </span><span class="UpAbci_sauvegarde" style="display:none" data-upabcicss-result="display:none" data-upabcicss-result-partiel="display:inline" data-upabcicss-sauvegarde="display:inline"></span>
                        
            
            <!-- si téléchargement ok on affiche un rond vert -->
           	<span style="display:none;width:13px;height:13px;margin-left:1em;border-radius:50%;border:1px solid #999;background-color:#0C0" data-upabcicss-result-ok="display:inline-block;"></span> 
             
            <!-- si erreur on affiche un rond rouge -->       
            <span style="display:none;width:13px;height:13px;margin-left:1em;border-radius:50%;border:1px solid #999;background-color:#F00;" data-upabcicss-result-erreur="display:inline-block"></span>                                     
       
        </div>
        
         <!-- arrêter tout + bouton envoyer (enlever le data-upabcicss-submit="display:none" du bouton submit si vous ne souhaitez pas le voir disparaître à la soumission du formulaire) -->
        <div style="margin-top:2em;">
        <input type="button" value="Arrêter tout" class="UpAbci_arreter_tout" style="float:left;font-size:0.8em;color:#666;cursor:pointer;opacity:1" data-upabcicss-fin-upload="cursor:default;opacity:0.5" /><input type="submit" value="Envoyer" style="color:#333;display:block;margin:auto;" data-upabcicss-submit="display:none" />
        </div>
    
        <!-- s'affichera uniquement en fin de traitement du formulaire-->
        <div style="margin-top:10px;display:none;text-align:center;font-weight:bold" data-upabcicss-fin-traitement="display:block">
        Upload terminé
        </div> 
        
        <div style="margin-top:10px;display:none" data-upabcicss-sauvegarde-ft="display:block">
        <span style="font-weight:bold">Note : </span>les fichiers partiellement sauvegardés peuvent être complétés pendant 24h suivant la dernière sauvegarde effectuée.
        </div>
    </div>
 </fieldset>
</form>
</div>
</body>
</html>
