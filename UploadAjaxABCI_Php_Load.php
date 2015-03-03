<?php
/*UploadAjaxABCI_Php_Load_Basique Version 2.4c
require 'UploadAjaxABCIServeur.php';
*/
// Vous devez vérifier l'existence de ces dossiers ou changer ces adresses 
/*------------------------------------------------------*/
$dossier_destination = 'Destination_Upload/';
$dossier_destination2 = 'Destination_Upload2/';
$dossier_temporaire = 'Upload_Temp/';
/*------------------------------------------------------*/

session_start();
//UploadAjaxABCI_Php_Load Version 2.4

// maximise les possibilités de reprise d'upload en cas d'arrêt intempestif.
ignore_user_abort(true);

header('Content-type: text/html; charset=UTF-8');

/* NOTE : Si vous avez ajouté des champs input dans votre formulaire, utilisez la fonction urldecode() pour récupérer vos variables ex : $ma_variable = urldecode($_POST['ma_variable']) ;*/

// Charge la classe php d'upload
require 'UploadAjaxABCIServeur.php';


/* PARAMETRAGE DE LA CLASSE PHP D'UPLOAD

Le nom des répertoires passés en paramètre lors de l'initialisation de la classe doivent être suivis d'un slash '/'

Les deux premiers arguments sont indispensables, les suivants possèdent des valeurs par défaut.

$up = new UploadAjaxABCIServeur($dossier_destination, $dossier_temporaire, $cookie_heures = 24, $cookie_path = '/', $adresse_relative = null, $verif_filesize_sup2Go = false)

- l'adresse de destination des dossiers est créée avec :
$adresse_relative.$dossier_destination et $adresse_relative.$dossier_temporaire. Si le paramètre $adresse_relative n'est pas fourni, cette valeur prend par défaut l'adresse du script en cours et donc dans ce cas l'adresse des dossiers est à définir relativement à l'adresse de ce script.

- $cookie_heures = durée de vie du cookie de sauvegarde en heures
- $cookie_path = path du cookie

- Si $verif_filesize_sup2Go = true, l'intégrité des fichiers de plus de 2 Go sera vérifiée en utilisant la fonction php filesize(). Mais cette fonction peut renvoyer n'importe quoi sur certains serveurs avec des fichiers de plus de 2 Go (c.f. doc php). Il est préférable de mettre cette variable à true mais vérifier préalablement le comportement de filesize() avec des fichiers de plus de 2 Go sur votre serveur.

Ci-dessous quelques exemples d'utilisation. Voir le fichier 'UploadAjaxABCIServeur.php' pour plus de détails sur toutes les méthodes disponibles.
*/


$up = new UploadAjaxABCIServeur($dossier_destination, $dossier_temporaire);

// getUniqidForm() renvoie l'identifiant de formulaire
$unidid_form = $up->getUniqidForm();

// Il est conseillé de ne pas supprimer cette ligne car c'est le token qui assure que ce script est appelé depuis le formulaire
if(!(isset($unidid_form,$_SESSION['UploadAjaxABCI'][$unidid_form]))) $up->exitErreurFichier('Identifiant de formulaire non valide. Rafraîchissez la page');

// getFragment() retourne true si un fichier (ou un fragment de fichier) est joint au formulaire, sinon false
$fichier_en_cours = $up->getFragment();
if($fichier_en_cours)
{
	// Initialisation d'une variable qui sera testée dans la suite du script
	$destination_fichier = null;	
	
	// getFichierNomNettoye() retourne le nom du fichier nettoyé
	$nom_fichier_nettoye = $up->getFichierNomNettoye();
	
	// On peut aussi récupérer le nom originel du fichier getFichierNomOriginel();
	$nom_fichier_originel = $up->getFichierNomOriginel();


	// La fonction "getInputName()" retourne le nom du champ de type file
	$nom_input = $up->getInputName();
	/* Attention : ci-dessous je fais des tests sur $nom_input pour faire des contrôles (extension, taille max autorisée) sur les fichiers provenant de certains formulaires. Cela me permet d'utiliser le même code pour traiter tous les formulaires. Mais dans l'absolu des contrôles php ne devraient pas dépendre d'une variable externe. Il faudrait donc faire des fichiers php (comme celui-ci) distincts pour assurer une bonne sécurité si plusieurs formulaires demandent des contrôles différents*/
	
	
	// On vérifie les extensions (très recommandé) avec la fonction "VerifExtensions()". Si le paramètre passé à la fonction est un tableau vide, la vérification ne sera pas faite. 
	// Ici je vérifie uniquement les extensions du formulaire n°1
	if($nom_input == 'upload_0' || $nom_input == 'upload_1') 
	{
		$extensions_autorisees = array('avi','divx','mp4','mkv','jpg','jpeg','png');
	}
	else
	{// danger potentiel si un tableau vide est passé à la fonction (= pas de vérification des extensions)
		$extensions_autorisees = array();
	}
	
	$verif_extension = $up->VerifExtensions($nom_fichier_nettoye,$extensions_autorisees);
	if($verif_extension == false) 
	{
		// Utilisez la fonction "exitErreurFichier ($value)" pour sortir du script en ajoutant un message qui sera concaténé au statut "info.status.erreur" de la classe javascript et envoyé dans le bloc html ayant la classe "UpAbci_status"
		$up->exitErreurFichier('Extension du fichier non valide.');
	}	

	//Si le formulaire contient plusieurs input de type file avec des noms différents, on peut choisir une destination différente en fonction du nom du champ depuis lequel le fichier est téléchargé. 
	if($nom_input == 'upload_1')// 'upload_1' = deuxième champ de type file dans mon premier exemple du fichier "UploadAjaxABCI.php"
	{
		// "getAdresseRelative()" retourne l'adresse relative définie automatiquement par défaut ou celle renseignée dans l'initialisation de la classe php
		$adresse_relative = $up->getAdresseRelative();
		
		// construction de la nouvelle destination du fichier
		$destination_fichier = $adresse_relative.$dossier_destination2.$nom_fichier_nettoye;
	}
	
	
	// Pour vérification des doublons sur le serveur, on récupère la destination du fichier avec "getFichierDestination()" - qui retourne "$adresse_relative.$dossier_destination renseigné dans l'initialisation de la classe.$fichier nettoyé" -  si la variable $destination_fichier n'a pas déjà été définie dans la condition précédente "if($nom_input == 'upload_1')...".
	$destination_fichier = isset($destination_fichier) ? $destination_fichier : $up->getFichierDestination();

	 //Plutôt que de renommer le fichier en cas de doublon comme je le fais plus loin avec la fonction "RenommeDoublon()", on pourrait sortir du script avant d'employer la fonction "Upload()" et envoyer un message d'erreur en faisant :
	 /*
	if(is_file($destination_fichier))
	{
		$up->exitErreurFichier('Ce fichier existe déjà.');		
	}
	*/
	
	// Si aucune vérification ni renommage n'est fait, les fichiers du serveur ayant le même nom que les fichiers téléchargés seront écrasés.
	
	// INDISPENSABLE : on doit utiliser la fonction "Upload()" pour réaliser le processus de téléchargement
	$up->Upload();
	
	// Le processus d'upload est dissocié de celui du transfert, pour permettre de traiter le fichier complet (par exemple pour faire des redimensionnements d'images en php) avant de le déplacer vers son emplacement définitif.  La fonction "getFichierComplet()" qui doit être utilisée après la fonction "Upload()", retourne l'adresse du fichier temporaire lorsqu'il est complet, sinon false. 
	
	$fichier_complet = $up->getFichierComplet();
	
	/* Par défaut la fonction "RenommeDoublon($destination_fichier)" ajoute un identifiant unique (uniqid) au nom des fichiers en cas de doublon avec des fichiers de même nom sur le serveur. Avec un second argument optionnel quelconque (ex : $up->RenommeDoublon($destination_fichier,'incr');) le nom des fichiers sera incrémenté. Un troisième argument optionnel casse sensivitive est également disponible, mais ne l'utilisez qu'en connaissance de cause et sur des serveurs casse sensitive (ne PAS utiliser sur les serveurs WINDOWS).*/
	if($fichier_complet != false)
	{
		// la fonction de renommage est un peu gourmande en ressources (notamment si on utilise l'option d'incrémentation). Inutile de l'utiliser pour chaque fragment traité par ce script, d'où la condition "if($fichier_complet != false)" qui permet de ne l'utiliser qu'une fois le fichier complet)
		$destination_fichier = $up->RenommeDoublon($destination_fichier);
	
	
		// Exemple d'intégration d'une fonction externe de redimensionnement d'images. Pour être cohérent on devrait définir plus haut le tableau des extensions autorisées avec $extensions_autorisees = array('jpg','jpeg','png'). Ou alors faire un test avec la fonction "getFichierType()" pour redimensionner uniquement les images, mais laisser la possibilité de télécharger également d'autres types de fichiers.
		/*
		require 'fctredimimage.php';
		
		$basename = basename($fichier_complet);
		$dirname = dirname($fichier_complet).'/';
		//Attention getFichierComplet() retourne le nom du fichier temporaire qui est sans extension...
		$extension_fichier = strtolower(pathinfo($destination_fichier, PATHINFO_EXTENSION));
		
		$redim = fctredimimage(200, 200, '', '', $dirname, $basename,$extension_fichier);
		// (le fichier sera redimensionné dans la limite du paramètre "memory_limit" de votre serveur. La bibliothèque GD utilisée par la fonction de redimensionnement requière une mémoire disponible d'environ deux fois le poid de l'image. Si le paramètre "memory_limit" est à 128Mo vous pourrez redimensionner des photos d'une taille maximale d'environ 64Mo)
		if(!$redim) 
		{
			$up->exitErreurFichier("Erreur dans le redimentionnement du fichier.");
		}
		*/
		
		// Si l'on veut contrôler la taille du fichier c'est également dans cette condition qu'il convient de le faire sur $fichier_complet avant son transfert vers son emplacement définitif.
		// Ici je vérifie uniquement le poids des fichiers du formulaire n°2
		if($nom_input == 'upload_2')
		{
			if(filesize($fichier_complet) > $up->Return_Octets('100 Mo'))
			{
				$up->exitErreurFichier("Dépassement de la taille maximale autorisée.");
			}
		}
	}
	
	// INDISPENSABLE (pour transférer les fichiers du répertoire temporaire vers leur emplacement définitif) : on utilise la fonction "Transfert()" après la fonction "Upload()", avec comme argument optionnel la destination du fichier (retourne true en cas de succès, sinon false). 
	$transfert = $up->Transfert($destination_fichier);
	/* notes: 
	- Si je n'avais pas eu besoin de redéfinir la variable $destination_fichier, j'aurais pu faire simplement $up->Transfert(). Quand aucun paramètre n'est passé dans la fonction "Transfert", $destination_fichier est construite avec le nom du fichier nettoyé et a donc la même valeur que "getFichierDestination()" évoquée plus haut.
	- On aurait logiquement pu mettre cette ligne à l'intérieur de la condition "if($fichier_complet != false)". Je la met ici pour dire que la fonction "Transfert" fait automatiquement cette vérification, donc ce n'est pas indispensable.
	*/
	
	
	//Si l'on doit indiquer les droits d'accès du fichier, par exemple 604, on pourrait faire
	/*if($transfert && !@chmod($destination_fichier,0604))
	{
		$up->exitErreurFichier("Echec de la configuration des droits d'accès du fichier");
	}*/
}

/* 
INDISPENSABLE* : La fonction "exitReponseAjax()" doit être appelée en fin de script et APRES l'utilisation de la fonction "Transfert()" pour retourner les informations nécessaires à la requête Ajax. 

Entre l'utilisation de ces deux fonctions, on peut réaliser d'autres opérations, par exemple :
*/
// Pour enregistrer le nom (ou l'adresse) du fichier en bdd après l'upload terminé et éventuellement compléter le message de réponse du fichier, on pourrait faire :

// "getFichierOk()" à utiliser après la fonction "Transfert()", renvoie true si le fichier complet a été déplacé avec succès vers son emplacement définitif, sinon false. A noter qu'on pourrait alternativement tester le retour de la fonction "Transfert()".

if($up->getFichierOk())
{
	// $bdd_ok = enregistrement du nom du fichier en bdd...
	// if ($bdd_ok) ...
	
	// Utilisez la fonction "addStatusOkAjax($value)" si vous souhaitez envoyer des messages qui seront concaténés au statut ".info.status.ok" (de la classe javascript) qui renseigne le bloc html ayant la classe "UpAbci_status" dans la ligne d'information du fichier. Par exemple :
	//$up->addStatusOkAjax(", enregistrement en bdd ok.");
	// affichera "Téléchargement ok, enregistrement en bdd ok." (si vous n'avez pas modifié la valeur de info.status.ok)
	
	// Dans le cas du formulaire n°5 le message javascript "info.status.ok" a été configuré vide. On souhaite uniquement afficher un message textuel si le fichier a été renommé
	if (isset($destination_fichier,$nom_input,$nom_fichier_originel) && $nom_input == 'upload5')
	{
		// On ne doit pas utiliser la variable "$nom_fichier_nettoye" précédemment définie car le nom du fichier peut être modifié en cas de doublon sur le serveur puisque j'utilise $up->RenommeDoublon() plus haut.
		$nom_final = pathinfo($destination_fichier, PATHINFO_FILENAME);
		$extension_fichier = pathinfo($destination_fichier, PATHINFO_EXTENSION);
		$nom_final = trim($extension_fichier) != '' ? $nom_final.'.'.$extension_fichier : $nom_final;
		
		if ($nom_fichier_originel != $nom_final)
		{
			$up->addStatusOkAjax("renommé ".$nom_final);
		}
	}
}


/* Eventuellement utile en cas d'upload multiple : 
Si la classe javascript a été configurée avec l'option .config.requete_upload_fin = true qui envoie une requête pour indiquer la fin du traitement de tous les fichiers, on peut utiliser la fonction getUploadFin() qui retourne 0 si aucun fichier n'a été traité et 1 dans le cas contraire. Retourne null si l'option n'a pas été configurée ou si ce n'est pas la fin du traitement. 

A NOTER que l'on doit utiliser cette fonction EN DEHORS de test de l'existence d'un fragment car la requête qui envoie cette information n'inclus pas de fragment de fichier.*/
$fin_traitement = $up->getUploadFin();
/*if (isset($fin_traitement))
{
	
}*/

// On peut aussi retourner des informations dans le bloc html ayant la classe "UpAbci_infos_form". Ici je choisi de faire afficher un message si aucun fichier n'est joint lors de l'envoi du formulaire en utilisant la fonction "addInfoFormAjax ($value)"
if ((!$fichier_en_cours && !isset($fin_traitement)) || (isset($fin_traitement) && $fin_traitement == 0))
{ 
	$up->addInfoFormAjax('Aucun fichier traité.');
}
/* A noter que si je n'avais pas configuré un des formulaires avec "config.requete_upload_fin = true", j'aurais pu faire simplement if (!$fichier_en_cour) {...}. Mais comme dit plus haut si cette otpion est activée la requête de confirmation de fin de traitement qui est envoyée par javascript n'inclus pas de fichier et donc on entrerait dans cette condition même après l'upload de fichiers. 
D'un autre côté si je n'avais eu à traiter que le formulaire où j'ai configuré "config.requete_upload_fin = true" j'aurais pu faire simplement if(isset($fin_traitement) && $fin_traitement == 0){...}. 
La syntaxe est ici un peu compliquée car je traite simultanément différents cas de figure.
*/



// *INDISPENSABLE dans tous les cas : Envoi des informations nécessaires pour terminer le script javascript avec "exitReponseAjax()"
$up->exitReponseAjax();
?>
