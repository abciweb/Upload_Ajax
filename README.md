# Upload_Ajax
Classe d'upload Ajax, Html5-Jquery

Exemples complets et fonctionnels avec classe php côté serveur.

Upload les fichiers par fragments compilés dans un fichier temporaire quand ils dépassent une valeur paramétrable. 
L'upload terminé, le fichier temporaire est renommé avec son nom initial (éventuellement corrigé) 
et déplacé vers son emplacement définitif.

- Permet de surpasser les limitations serveur "upload_max_filesize" et "post_max_size".

- Permet la reprise d'un téléchargement interrompu après un arrêt volontaire ou une panne internet ou serveur
en utilisant la partie sauvegardée.

- Supporte l'upload multiple et surpasse la configuration serveur "max_file_uploads" avec un système de file d'attente 
(queuing).

- Retour d'informations en temps réel, personnalisable et très souple :

1/ Dix informations (toutes optionnelles) sont disponibles durant l'upload : 
nom, taille, vignette de prévisualisation (si c'est une image), 
progression graphique, pourcentage, progression textuelle, sauvegarde partielle effectuée, 
temps passé, temps restant estimé, status (en cours, erreur, ok, arrêt), 
ainsi que deux commandes : "arrêter" et "arrêter-tout" qui permettent l'arrêt optimisé afin de pouvoir
effectuer une reprise d'upload ultérieure dans les meilleures conditions.

2/ Styles événementiels (optionnels) qui s'appliquent en fonction des événements suivant : fichiers soumis,
formulaire envoyé, sauvegarde partielle disponible, fichier en cours d'upload, upload terminé (quelque soit le résultat),
upload terminé arrêté, upload terminé partiel, upload terminé erreur, upload terminé ok, 
fin d'upload (tous uploads terminés), sauvegarde pour au moins un fichier en fin d'upload,
fin de traitement du formulaire (avec ou sans fichiers traités).

Regardez en premier lieu "UploadAjaxABCI_Exemple_Basique.php" et "UploadAjaxABCI_Php_Load_Basique.php" qui suffisent pour une configuration basique.
Un dossier zip préconfiguré et prêt à l'emploi est disponible ici :
http://javascript.developpez.com/telecharger/detail/id/4130/Upload-de-fichiers-par-fragments-chunk-reprise-d-upload-affichage-des-informations-en-temps-reel-et-styles-evenementiels
