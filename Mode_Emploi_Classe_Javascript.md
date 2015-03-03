UploadAjaxABCI Version 2.4
auteur Alain Bontemps ABCIWEB

Upload de fichiers par fragments (chunk), reprise d'upload, affichage des informations en temps réel et styles événementiels.

Fichiers de test
- UploadAjaxABCI_Exemple_Basique.php 
- UploadAjaxABCI_Exemples.php

Nécessite 
- UploadAjaxABCI_DEV.js (classe javascript)
- UploadAjaxABCI_Php_Load.php (ou équivalent, destination de la requête Ajax)
- UploadAjaxABCIServeur.php (ou équivalent, classe d'upload serveur)
- Jquery.js

Compatible Navigateur html5 récents, Firefox, Chrome, IE 10

Upload les fichiers par fragments compilés dans un fichier temporaire quand ils dépassent une valeur paramétrable. L'upload terminé, le fichier temporaire est renommé avec son nom initial (éventuellement corrigé) et déplacé vers son emplacement définitif. 

- Permet de surpasser les limitations serveur "upload_max_filesize" et "post_max_size".

- Permet la reprise d'un téléchargement interrompu après un arrêt volontaire ou une panne internet ou serveur, en utilisant la partie sauvegardée.

- Supporte l'upload multiple et surpasse la configuration serveur "max_file_uploads" avec un système de file d'attente (queuing).

- Retour d'informations en temps réel, personnalisable et très souple :
	a/ Dix informations sont disponibles durant l'upload : nom, taille, vignette de prévisualisation (si c'est une image), progression graphique, pourcentage, progression textuelle, sauvegarde partielle effectuée, temps passé, temps restant estimé, status (en cours, ok, erreur, arrêt), ainsi que deux commandes : arrêter et arrêter-tout, qui permettent l'arrêt optimisé afin de pouvoir effectuer une reprise d'upload ultérieure dans les meilleures conditions.

	b/ Dispose de style événementiels qui s'appliquent en fonction des événements suivant : fichiers soumis, formulaire envoyé, sauvegarde partielle disponible, fichier en cours d'upload, temps restant requis (avant l'affichage du temps restant), upload terminé (quelque soit le résultat), upload terminé arrêté, upload terminé partiel, upload terminé erreur, upload terminé ok, fin d'upload (tous uploads terminés), sauvegarde pour au moins un fichier en fin d'upload, fin de traitement du formulaire (avec ou sans fichiers traités).


ATTENTION !  Pour faire des tests sur un serveur local il est conseillé d'utiliser le paramètre ".config.ajax_delai" avec une valeur assez importante pour espacer l'envoi des requêtes. Je ne pourrai pas être tenu pour responsable si une sollicitation importante du système donne le coup de grâce d'un disque dur en cours d'agonie.
 
Testé ok avec Firefox, IE10, Chrome

Il n'y a pas de limite à la taille des fichiers. J'ai testé avec succès des fichiers de plus de 9 Go en local. Sur serveurs mutualisés distants je me suis limité à des tests de 2,5 Go (car faut faire preuve de patience). La vitesse dépend de votre débit ascendant (1Mb actuellement avec adsl classique) mais aussi de la vitesse d'upload limitée par les paramétrages du serveur distant (souvent vers 500 kb pour les mutualisés d'entrée de gamme). 

Par ailleurs certains serveurs optimisés pour surveiller drastiquement l'activité (genre mutualisés ovh) renvoient plus ou moins souvent une erreur 500 au bout d'un certain temps. Cela permet toutefois d'envoyer dans les 300 à 500 Mo en une seule passe que l'on pourra éventuellement compléter en réinitialisant le téléchargement. Sur ces mêmes serveurs j'ai néanmoins pu télécharger des fichiers de 1,5 Go en une seule passe, c'est donc très variable et dépend très probablement de l'activité globale du serveur.


Détails techniques sur la sauvegarde :
En cas de besoin, la reprise d'upload s'effectue automatiquement dès l'envoi du même fichier ou après demande de confirmation. La durée de disponibilité de la sauvegarde dépend de la durée de vie du cookie envoyé par le script php d'upload. Prévoir un système automatique (ex : cron) pour nettoyer périodiquement les fichiers abandonnés en cours d'upload dans le répertoire temporaire. 

Côté serveur, l'unicité du nom du fichier temporaire est assurée par trois uniqid générés à trois moments différents (affichage du formulaire, soumission du formulaire, réception de la requête ajax) et combinés à l'identifiant du formulaire** puis au nom et à la taille du fichier, pour garantir une signature unique.

Côté client, la signature d'un fichier incomplet en cours d'upload est composée de l'identifiant du formulaire**, du  nom et de la taille du fichier.  Cela est suffisant dans la mesure où il est extrêmement rare qu'on possède sur son ordinateur deux fichiers qui aient exactement le même nom et la même taille tout en étant différents, et que l'on télécharge ensuite le second à la suite de l'échec du téléchargement du premier. Pour cette raison la reprise d'upload est automatique par défaut mais on peut configurer le script pour qu'il demande une confirmation de reprise (cf options de configuration de la classe javascript ci-dessous). 

Les informations de sauvegarde sont enregistrées dans un cookie, on ne peut donc récupérer un fichier partiellement téléchargé que depuis le même navigateur et le même formulaire** qui a envoyé la partie sauvegardée.

Pour les reprises d'upload, faire attention de pouvoir récupérer les cookies envoyés par le script php. Un cookie envoyé sur http://monsite.net ne pourra pas être récupéré depuis http://www.monsite.net, et inversement. En cas de problème lors du téléchargement, un visiteur qui s'est connecté depuis la première adresse ne pourra pas récupérer la partie sauvegardée s'il se connecte depuis la seconde adresse lors de sa visite suivante. Pour éviter ce problème, on peut définir une adresse unique par réécriture d'url dans un .haccess.

** Voir notes sur "config.static_id_form" en toute fin de ce document pour modifier ce comportement.

Exemples dans les fichiers joints "UploadAjaxABCI_Exemple_Basique.php" et "UploadAjaxABCI_Exemples.php"

1/ SCRIPT PHP QUI ALIMENTE LE FORMULAIRE
(UploadAjaxABCI_Exemples.php)

- Modifier éventuellement la variable $taille_fragment qui détermine la taille maximale des fragments sauvegardés (dans la mesure où cette valeur n'est pas supérieure à la configuration serveur 'upload_max_filesize').

Dans l'absolu le script peut fonctionner sans variables php transmises dans le formulaire puisque "$UpAbci_max_fragment" peut être renseignée par les options de configuration de la classe en javascript, de même pour "$UpAbci_uniqid_form" qui défini une variable de session. Mais la variable de session est utilisée comme jeton de sécurité, et pour un code plus générique il est judicieux de vérifier la configuration 'upload_max_filesize' du serveur en php pour ne pas la dépasser. Dans la plupart des cas vous pouvez laisser le code inchangé.




2/ CONFIGURATION de la classe javascript UploadAjaxABCI
(fichier exemples : UploadAjaxABCI_Exemples.php)

var MyUpload = new UploadAjaxABCI('#param1', 'param2', '#param3', false);
Les deux premiers paramètres sont obligatoires, le troisième est indispensable pour l'affichage des informations d'upload pour chaque fichier.

- 1er paramètre : formulaire cible (id ou class)
- 2eme paramètre : destination de la requête ajax (adresse du script d'upload côté serveur)
- 3eme paramètre : bloc d'information html de l'upload (id ou class)
- 4eme paramètre : booléen true ou false (false par défaut). Si le navigateur n'est pas compatible, false renvoie un message d'avertissement, true envoie le formulaire à l'adresse indiquée dans l'attribut action du formulaire.

var up = new UploadAjaxABCI('#form','Php_Upload/UploadAjaxABCI_Php_Load.php','#reponse_upload');

// Autres paramètres complémentaires optionnels  (cf liste ci-dessous : "Options de configuration")
up.config.ajax_delai = 750;
// …

// Pour terminer, utiliser la fonction Start() pour initialiser le script, DOM chargé :
$(function(){up.Start()});



Options de configuration javascript

Les paramètres ci-dessous peuvent être configurés en complément. Ils sont listés avec les valeurs par défaut. Les valeurs numériques doivent être rentrées sans guillemets. A noter que pour les informations de retour ".info" vous pouvez rentrer du html plutôt que du texte plat.


// options de configuration 
- .config.ajax_delai = 100; // en milli seconde, délai d'envoi de la requête ajax, utile pour éviter de saturer un serveur local poussif

- .config.delai_refresh_infos_text = 1; // en seconde, fréquence de rafraîchissement des informations textuelles.  

- .config.delai_calcul_temps_restant = 10; // en seconde, temps de calcul minimum avant l'affichage du temps restant estimé. Si trop court le résultat n'est pas significatif. Durant l'attente c'est le paramètre "info.temps_restant_calcul" qui s'affiche.

- .config.temps_restant_requis = 30; // en secondes. Si le temps restant estimé pour l'upload du fichier est supérieur à cette valeur alors le formatage "data-upabcicss-temps-restant-requis" sera appliqué. Utilisée principalement pour ne pas faire afficher le temps restant pour les petits fichiers.

- .config.confirme_reprise_sauvegarde = false; // booléen true ou false si reprise automatique sans demande de confirmation.

- .config.static_id_form = true; //booléen true ou false. true lie les possibilités de reprise d'upload du fichier à l'identifiant du formulaire. Voir note en bas de page pour plus d'infos.

- .config.requete_sans_fichier = true;// booléen true ou false si le formulaire ne doit pas être envoyé en absence de fichiers

- .config.path_cookie = '/';// path du cookie 

- .config.max_fragment = 4194304;// taille maximale du fragment en octet si le script ne trouve pas de champ nommé "UpAbci_max_fragment" (avec une valeur non nulle et supérieure à 1048576) dans le formulaire.

- .config.uniqid_form = this.SHA1(this.Uniqid('UploadAjaxABCI',true));// valeur générée à l'affichage du script s'il ne trouve pas de champ nommé "UpAbci_uniqid_form" dans le formulaire

- .config.requete_upload_fin = false;// booléen true ou false. Si = true, une requête additionnelle est envoyée au script php pour indiquer la fin de traitement de tous les fichiers (excepté si config.requete_sans_fichier = false et qu'aucun fichier n'a été soumis).

- .config.max_weight_apercu = 4; // en Mo. Poids maximal de chaque image pour prévisualisation. Si cette valeur est dépassée pour au moins une image dans un upload multiple, aucune vignette ne sera affichée afin de préserver une cohérence dans l'affichage des informations 

- .config.total_max_weight_apercu = 16; // en Mo. Poids total maximal de toutes les images (en téléchargement multiple) pour  prévisualisation. Si dépassé aucune vignette ne sera affichée

- .config.max_width_apercu = 80; // en pixels, largeur maximale de l'aperçu

- .config.max_height_apercu = 80; // en pixels, hauteur maximale de l'aperçu
	
- .config.max_filesize_utilisateur = null;// taille maximale du fichier utilisateur. Si = 0 ou null aucune vérification n'est faite sur la taille du fichier. Accepte un nombre en octets ou une chaine de caractère formatée avec les unités. Les espaces et la lettre "o" sont optionnels et le formatage ne tient pas compte de la casse. Par exemple 1073741824 = '1073741824o' = '1024Mo' = '1024 M' = '1048576k' = '1048576 Ko' = '1 go' = '1 G' (les nombres décimaux sont acceptés). Faire un contrôle supplémentaire côté serveur par sécurité (pas obligatoire mais très vivement conseillé).
	
- .config.extensions_autorisees = [];// Tableau des extensions autorisées. Les variantes majuscules/minuscules sont automatiquement prises en compte. Si le tableau est vide aucune vérification n'est faite. Faire un contrôle supplémentaire côté serveur par sécurité (pas obligatoire mais très vivement conseillé).



// Informations de retour

- .info.temps_restant_calcul = 'calcul en cours'; // s'affiche durant la période définie par la variable "config.delai_calcul_temps_restant"

- .info.status.ok = 'Téléchargement ok';// complété éventuellement par un message provenant du script php d'upload

- .info.status.encours = 'Téléchargement en cours';

- .info.status.arret = 'Arrêt';// (sur demande ou erreur utilisateur)

- .info.status.erreur = 'Echec du téléchargement. ';// complété automatiquement par l'erreur serveur ou par une erreur générée par le script php d'upload ou par une erreur utilisateur (cf les deux lignes ci-dessous)

- .info.status.erreur_taille = 'Dépassement de la taille maximale autorisée. ';// message si dépassement de "config.max_filesize_utilisateur"
	
- .info.status.erreur_extension = 'Extension non valide. ';// message si l'extension ne fait pas partie du tableau "config.extensions_autorisees"
	
- .info.status.incompatible = 'Navigateur incompatible.';

- .info.status.espace = '&nbsp;';// espacement par défaut entre les valeurs et leurs unités
	
// Annonce javascript (boite de confirmation javascript pour la reprise d'upload partiel sauvegardé) 
- .info.confirme_reprise_sauvegarde.nom = 'Récupération du fichier : '; // Annonce javascript suivie du nom du fichier

- .info.confirme_reprise_sauvegarde.taille = '\nSauvegarde en cours : '; // Annonce (suite) suivie de la taille de la sauvegarde (\n pour retour ligne car la boite de confirmation est en javascript)

- .info.confirme_reprise_sauvegarde.message = '\n\nCliquez sur "OK" pour compléter ce fichier ou sur "Annuler" pour réinitialiser le téléchargement.';// Annonce (suite) dernière ligne

- Attention la fonction de prévisualisation des images est gourmande en ressources (dépend du poids des fichiers). Si vous utilisez le bloc de prévisualisation des images ".UpAbci_apercu_img" décrit plus loin et que ce script doit fonctionner aussi avec les smartphones, vous avez intérêt de détecter le périphérique utilisé par le visiteur et le cas  échéant, modifier ou mettre à zéro les options de configuration ".config.max_weight_apercu" et ".config.total_max_weight_apercu".

- Notez également que la valeur de ".config.temps_restant_requis" sera supérieure à celle indiquée car la vitesse calculée au début du téléchargement pour estimer le temps restant est toujours surévaluée : avec une valeur de 30 secondes, le style défini par "data-upabcicss-temps-restant-requis" (décrit plus loin) ne sera peut-être pas appliqué pour des fichiers pouvant mettre jusqu'à un peu plus de 40 secondes pour se télécharger. Ce manque de précision est cependant pratiquement sans importance car ce style est principalement prévu pour éviter d'afficher le temps restant (sans intérêt) pour les  fichiers qui sont téléchargés rapidement.





3/ FORMULAIRE HTML
(fichier exemples : UploadAjaxABCI_Exemples)

- Le formulaire doit contenir un (ou plusieurs) champ de type file, avec ou sans attribut "multiple". 
- La classe javascript recherche également deux champs de type input nommés :
name = "UpAbci_uniqid_form"
name = "UpAbci_max_fragment"  
Si ces champs sont vides ou inexistants la classe  utilisera ses valeurs par défaut ou les valeurs configurées avec les options de configuration javascript. Il est cependant conseillé de les renseigner dans le formulaire, par sécurité concernant la variable "UpAbci_uniqid_form" qui fait office de jeton témoin d'envoi du formulaire, et pour faciliter la communication php/javascript concernant "UpAbci_max_fragment" qui défini la taille des fragments.

- Vous pouvez ajouter d'autres champs dans le formulaire, ils seront automatiquement transmis au script php par la requête ajax. Côté php vous pourrez les récupérer en utilisant la fonction "urldecode()" :
$ma_variable = urldecode($_POST['ma_variable']);





4/ CONFIGURATION DE L'AFFICHAGE DES INFORMATIONS HTML
(fichier exemple : UploadAjaxABCI_Exemples et UploadAjaxABCI_Exemple_Basique.php)

A1/ Retour d'information générale du formulaire
- .UpAbci_infos_form → retour d'information générale de formulaire 
Retour ajax d'information générale (non spécifique aux fichiers). 

A2/ Dix informations et deux comportements peuvent être affichées durant le téléchargement du ou des fichiers. Les informations s'afficheront, ou les comportements seront initialisés, dans les éléments html de votre choix en utilisant les classes suivantes :

- .UpAbci_arreter_tout → stop les téléchargements en cours (comportement onclick).
En cas d'upload multiple, arrête le téléchargement du fichier en cours et les fichiers suivants (pas d'incidence sur les téléchargements précédents).

- .UpAbci_infos_upload → conteneur indispensable pour afficher les informations sur les fichiers.
En cas d'upload multiple, ce bloc contenant les informations d'un fichier est dupliqué. En conséquence ne pas utiliser d'id. Idem pour les éléments html contenus qui peuvent utiliser les classes listées ci-dessous pour afficher les informations ou le comportement correspondant. 

- .UpAbci_nom → nom
- .UpAbci_taille → taille
- .UpAbci_progressionG → progression graphique dans une balise html <progress>
- .UpAbci_progressionT → progression textuelle
- .UpAbci_pourcentage → pourcentage effectué
- .UpAbci_sauvegarde → sauvegarde effectuée (0 si upload terminé)
- .UpAbci_temps_passe → temps passé
- .UpAbci_temps_restant → temps restant
- .UpAbci_status → status (en cours, ok, arrêt, erreur)
- .UpAbci_arret → arrêt de l'upload du fichier (comportement onclick)
- .UpAbci_apercu_img → affiche l'aperçu si c'est une image dans la limite des valeurs ".config.max_weight_apercu" et ".config.total_max_weight_apercu"

Vous pouvez écrire des valeurs par défaut dans vos éléments html (ex : 0 Mo), elles seront remplacées automatiquement en temps voulu. 

- Les valeurs par défaut des éléments utilisant les classes  "UpAbci_progressionT",  "UpAbci_sauvegarde" et  "UpAbci_temps_restant" sont restituées quand l'upload est terminé. 

- Les valeurs par défaut sont aussi analysées pour déterminer l'éventuel espacement à appliquer entre la valeur numérique et son unité correspondante. Si non renseignée ou si les premiers caractères ne sont pas numériques, l'espacement utilisé est celui de la configuration .info.status.espace ('&nbsp;' par défaut).


B/ Règles de styles événementielles (exemples dans le fichier de test "UploadAjaxABCI_Exemples.php"). 
Les règles de styles sont spécifiées (si besoin) avec l'attribut "data-upabcicss-..." dans vos éléments html et seront appliquées en fonction des événements suivants :

Styles applicables sur tous les blocs contenus dans le conteneur des résultats spécifié en troisième paramètre de l'initialisation de la classe javascript  :
- data-upabcicss-submit-file  		→ des fichiers ont été soumis 
- data-upabcicss-submit			→ le formulaire a été envoyé
- data-upabcicss-fin-upload		→ fin de traitement de tous les fichiers
- data-upabcicss-sauvegarde-ft		→ sauvegarde pour au moins un fichier en fin de traitement
- data-upabcicss-fin-traitement		→ fin de traitement du formulaire avec ou sans fichiers traités

Styles applicables sur tous les blocs contenus dans le bloc d'information des fichiers défini avec la classe css ".UpAbci_infos_upload" :
- data-upabcicss-sauvegarde  		→ sauvegarde partielle disponible (ancienne ou en cours)
- data-upabcicss-encours  		→ le fichier est en cours d'upload
- data-upabcicss-temps-restant-requis	→ s'applique après la durée de ".config.temps_restant_requis" 
- data-upabcicss-result  		→ upload terminé, quelque soit le résultat
- data-upabcicss-result-arret		→ upload terminé arrêté sur demande utilisateur
- data-upabcicss-result-ok  		→ upload terminé ok
- data-upabcicss-result-partiel 	→ upload terminé et sauvegarde existante
- data-upabcicss-result-erreur  	→ upload terminé erreur

Les styles doivent être écrits en respectant la syntaxe classique, exemple : 
<span style="display:none;width:15px;height:10px" data-upabcicss-result-ok="display:inline-block;background-color:green"></span>

A noter que les styles des éléments contenus dans le bloc conteneur général (spécifié en troisième paramètre de l'initialisation de la classe javascript)  sont réinitialisés à leur valeur initiale pour chaque nouvel upload sans nécessiter de rafraîchissement de la page. Ce n'est pas le cas pour le bloc conteneur lui-même sur lequel vous pouvez utiliser les styles événementiels "data-upabcicss-submit-file" et "data-upabcicss-submit" pour rétablir les valeurs originelles.

Ordre d'application des styles

Certains styles peuvent entrer en concurrence, ils sont appliqués dans l'ordre des événements suivants : 
'submit-file', 'submit', 'sauvegarde', 'encours', 'temps-restant-requis', 'result', 'result-arret', 'result-ok',  'result-partiel', 'result-erreur', 'fin-upload', 'sauvegarde-ft', 'fin-traitement'. 

- L'ordre d'écriture dans le html n'a aucune incidence. 
- Si des fichiers sont joints dans le formulaire, 'submit-file' est persistant par rapport à 'submit' : le style 'submit-file' est appliqué avant et après le style appliqué par 'submit', excepté si tous les fichiers sont arrêtés manuellement avant l'envoi du formulaire auquel cas il n'est appliqué qu'avant.





5/ CONFIGURATION DU SCRIPT PHP D'UPLOAD 
(fichier : UploadAjaxABCI_Php_Load.php ou UploadAjaxABCI_Php_Load_Basique.php)

Configuration de la classe php "UploadAjaxABCIServeur" 

$up = new UploadAjaxABCIServeur($dossier_destination, $dossier_temporaire, $cookie_heures = null, $cookie_path =null, $adresse_relative = null, $verif_filesize_sup2Go = false)

Seuls les deux premiers paramètres sont indispensables, les autres ont des valeurs par défaut.

- $dossier_destination 		→ dossier de destination de l'upload 
- $dossier_temporaire 		→ dossier temporaire où sont enregistrées les sauvegardes 
- $cookie_heures		→ la durée de vie du cookie de sauvegarde en heures
- $cookie_path 			→ le path du cookie envoyé en php. Doit pouvoir être récupéré par le script en javascript
- $adresse_relative		→ adresse relative des dossiers
- $verif_filesize_sup2Go 	→ mettre si possible à true après avoir lu l'avertissement

Des exemples sont disponibles dans le fichier  "UploadAjaxABCI_Php_Load.php" et toutes les fonctions disponibles de la classe php sont documentées dans le fichier  "UploadAjaxABCIServeur.php".





NOTES :

- Les options de configuration de la classe javascript ne sont pas testées avant d'être utilisées. Si vous rentrer une chaîne de caractères rompue ou des valeurs numériques entourées de guillemets, vous obtiendrez un dysfonctionnement sans message d'avertissement préalable.

- Ne pas utiliser d'outils genre console Firebug durant l'upload car dans ce cas l'occupation mémoire monte graduellement et à terme le serveur d'évaluation ne répond plus : il s'arrête pour ne pas saturer l'ordinateur et cela se traduit par une perte de session qui indique que le jeton de formulaire n'est pas valide. En conditions normales, l'occupation mémoire observée sur l'ordinateur - test en local pc wampserver sous W8 - ne dépasse pas 100 Mo maximum et stable, quelque soit la taille du fichier.

- .config.static_id_form = true; //booléen true ou false. "true" lie les possibilités de reprise d'upload du fichier à l'identifiant du formulaire.  
Si false, l'identifiant de formulaire n'est pas utilisé pour créer le nom du cookie de sauvegarde. Cela permet de récupérer l'éventuelle sauvegarde d'un fichier depuis différents formulaires, mais induit un problème dans le cas particulier où l'on télécharge simultanément des fichiers identiques depuis des  formulaires différents : le second upload va retrouver la sauvegarde en cours du premier et le fichier final sera corrompu (le script renverra  un message d'erreur en fin d'upload "Fichier temporaire non valide").  Même chose si config.static_id_form = true et que les identifiants de formulaires sont identiques dans différentes pages. 

- La vérification de l'intégrité d'un fichier utilise la fonction php "filesize()" qui peut renvoyer n'importe quoi avec des fichiers de plus de 2Go sur certains serveurs (c.f. doc php). Cette vérification est donc limitée par défaut aux fichiers de moins de 2Go. Si possible affectez la valeur true à la variable "$verif_filesize_sup2Go" (dans l'initialisation de la classe php)  après avoir vérifié le bon comportement de votre serveur.


Penser à utiliser périodiquement un script pour nettoyer les fichiers abandonnés en cours d'upload dans le dossier temporaire. Exemple dans "nettoyage_temp.php"
