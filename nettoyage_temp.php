<?php
// Exemple de fonction qui efface tous les fichiers de $repertoire dont la dernière date de modification a plus de $validite_heures, à l'exception du fichier .htaccess.
function NettoyageTemp($repertoire,$validite_heures)
{
	if(!is_dir($repertoire)) {return false;}

	$duree = $validite_heures*3600;
	$maintenant = time();

	$iterator = new DirectoryIterator($repertoire);
	foreach ($iterator as $fileinfo) 
	{
		if ($fileinfo->isFile() && $fileinfo->getFilename() != '.htaccess' && ($maintenant - $fileinfo->getMTime()) > $duree) 
		{
			unlink($fileinfo->getPathname());
		}
	}
	
	return true;
}


 
$repertoire = 'Upload_Temp';
$validite_heures = 24;

NettoyageTemp($repertoire,$validite_heures);
// la fonction retourne false si le répertoire n'existe pas ou si son adresse n'est pas correctement renseignée. Elle retourne true dans les autres cas.
?>
