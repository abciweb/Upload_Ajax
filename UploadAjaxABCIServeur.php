<?php class UploadAjaxABCIServeur 
{
	private $version = '2.4';
	/* - IMPORTANT : Vous pouvez modifier les valeurs de $this->reponse_upload['erreur'] (bien qu'elles aient très peu de chances d'apparaître si les paramètres sont correctement configurés) mais PAS les valeurs de $this->reponse_upload['resultat'] qui sont des commandes ajax et qui de toutes façons n'apparaîtront jamais dans la réponse html.
	
	- Si vous étendez cette classe et que vous surchargez la fonction "Transfert", faire attention de supprimer le cookie correspondant au fichier en cas de succès, ainsi que d'attribuer la valeur "upload_ok" à la variable $this->reponse_upload['resultat'] afin de permettre au script javascript de se poursuivre correctement.
	*/
	
	public function getFragment ()
	{// retourne true si un fichier est joint dans la requête.
		return isset($this->UpAbci_fragment);
	}
		
	public function getFichierNomNettoye ()
	{// nom du fichier nettoyé
		return $this->UpAbci_fichier_nom_nettoye;
	}
	
	public function getFichierNomOriginel ()
	{// nom originel du fichier 
		return $this->UpAbci_fichier_nom_originel;
	}
	
	public function getUniqidForm ()
	{// identifiant du formulaire
		return $this->UpAbci_uniqid_form;
	}

	public function getFichierDestination ()
	{// destination avec nom de fichier éventuellement corrigé
		return $this->fichier_destination;
	}

	public function getFichierSize ()
	{// taille du fichier
		return $this->UpAbci_fichier_size;
	}
	
	public function getFichierType ()
	{// type du fichier
		return $this->UpAbci_fichier_type;
	}
		
	public function getInputName ()
	{// nom de l'input de type file
		return $this->UpAbci_input_name;
	}
	
	public function getAdresseRelative ()
	{// adresse par rapport à laquelle sont définis les chemins des dossiers
		return $this->adresse_relative;
	}
	
	public function getCookieName ()
	{// nom du cookie de sauvegarde 
		return $this->cookie_name;
	}

	public function Return_Octets($val)
	{// retourne des octets depuis une chaine formatée comme 100 Mo ou 1 g
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
	
	// à utiliser AVANT la fonction "Upload"
	public function exitErreurFichier ($value)
	{// Sort du script en ajoutant un message qui sera concaténé au statut "info.status.erreur" de la classe javascript et envoyé dans le bloc html ayant la classe "UpAbci_status"
		exit(json_encode(array('erreur' => $value)));
	}

	// à utiliser AVANT la fonction "Upload" (si vous l'utilisez après, le fichier devra être totalement téléchargé avant que le visiteur puisse avoir l'information)
	public function VerifExtensions($fichier,$extensions)
	{
		$extensions_autorisees = is_array($extensions) ? array_map('strtolower',$extensions) : array();
		$extension_fichier = strtolower(pathinfo($fichier, PATHINFO_EXTENSION));
		// si le tableau des extensions autorisées est vide on accepte toutes les extensions					 
		if (count($extensions_autorisees) == 0 || in_array($extension_fichier,$extensions_autorisees))				 
		return true;
		else
		return false;                  
	}

	// A utiliser AVANT la fonction "Transfert" et de préférence après la fonction "Upload".
	/* Renomme le fichier téléchargé si un fichier de même nom existe déjà sur le serveur. Par défaut la fonction "RenommeDoublon()" ajoute un identifiant unique (uniqid) au nom des fichiers, avec un second argument optionnel quelconque (ex : $up->RenommeDoublon($destination_fichier,'incr');) le nom des fichiers est incrémenté.  Un troisième argument optionnel casse sensivitive est également disponible, mais à n'utiliser que sur les serveurs casse sensitive (NE PAS UTILISER AVEC LES SERVEURS WINDOWS).*/
	public function RenommeDoublon($adresse_fichier, $incr = false, $unix = false, $stop = 0, $isfile = false)
	{
		if ($isfile || is_file($adresse_fichier))
		{
			$info = pathinfo($adresse_fichier);
			$extension = isset($info['extension']) && $info['extension'] != '' ? '.'.$info['extension'] : null;
			$dossier = $info['dirname'];
			$filename = $info['filename'];
			
			if (trim($incr) != false && $stop < 90)// le stop arbitrtaire est une mesure de sécurité au cas où...
			{
				$file = addcslashes($filename,'.');			
				$ext = isset($extension) ? addcslashes($extension,'.') : null;									
	
				$match = trim($unix) != false ? '#^'.$file.'_[0-9]+'.$ext.'$#' : '#^'.$file.'_[0-9]+'.$ext.'$#i';
				
				$tab_identique = array();
				
				$files = new RegexIterator(new DirectoryIterator($dossier),$match);
				foreach ($files as $fileinfo) $tab_identique[] = $fileinfo->getFilename();
	
				natsort($tab_identique);
				
				$dernier = array_pop($tab_identique);
				
				unset($tab_identique);
							
				$dernier = isset($dernier)? pathinfo($dernier, PATHINFO_FILENAME) : '';
				
				$file = preg_replace_callback('#([0-9]+$)#', create_function('$matches','return $matches[1]+1;'), $dernier, '1', $count);
	
				$filename = !empty($count)? $file : $filename.'_1';
			}
			else
			{
				$filename .= '_'.uniqid();
			}
																														
			$filename = isset($extension) ? $filename.$extension : $filename;												
																					 
			$adresse = $dossier.'/'.$filename;
			
			if (!is_file($adresse)) return $adresse;
			else																													
			return Rename_fich($adresse_fichier, $incr, $unix, ++$stop, true);                        
		}																				 
		else 
		{
			return $adresse_fichier;
		}
	}

	// à utiliser APRES la fonction "Upload"
	public function getFichierComplet()
	{// retourne l'adresse du fichier temporaire s'il est complet sinon false
		return $this->fichier_verif ? $this->fichier_temp_adresse : false;
	}
	
	// à utiliser APRES la fonction "Transfert"
	public function addStatusOkAjax ($value)
	{// ajoute un message qui sera concaténé au statut "this.info.status.ok" de la classe javascript et envoyé dans le bloc html ayant la classe "UpAbci_status"
		if($this->getFichierOk())
		{
			$this->reponse_upload = array_merge($this->reponse_upload,array("ok" => $value));
		}
	}
	
	// à utiliser APRES la fonction "Transfert"
	public function addInfoFormAjax ($value)
	{// ajoute un message dans le retour d'information général du formulaire ayant la classe "UpAbci_infos_form"
		$this->reponse_upload = array_merge($this->reponse_upload,array("infos_form" => $value));
	}
		
	// à utiliser APRES la fonction "Transfert" (a le même effet que de tester le retour de la fonction Transfert())
	public function getFichierOk ()
	{// le fichier est complet et a été déplacé avec succès vers son emplacement définitif
		return isset($this->reponse_upload['resultat']) && $this->reponse_upload['resultat'] == 'upload_ok';
	}
	
	// getUploadFin () à utiliser APRES la fonction "Transfert" et en dehors de test de l'existence d'un fragment car la dernière requête ajax optionnelle qui envoie cette information (option configurable avec ".config.requete_upload_fin") n'inclus pas de fragment de fichier. Eventuellement utile pour les upload multiples. Pour les upload simples la fonction "getFichierOk ()" serait suffisante pour le même résultat.
	
	// getUploadFin retourne 0 si aucun fichier n'a été traité et 1 dans le cas contraire. Retourne null si l'option n'a pas été configurée ou si ce n'est pas la fin du traitement. 
	public function getUploadFin ()
	{// Tous les fichiers ont été traités, le traitement du formulaire est terminé.
	     // on renvoie 'upload_fin' dans reponse_upload['resultat'] pour terminer la requête ajax
		if(isset($this->UpAbci_upload_fin)){$this->reponse_upload['resultat'] = 'upload_fin';}
		return $this->UpAbci_upload_fin;
	}
	
	// Retour d'information OBLIGATOIRE et INDISPENSABLE pour le script ajax. A utiliser à la fin du script php (et APRES la fonction "Transfert")
	public function exitReponseAjax()
	{
		exit(json_encode($this->reponse_upload));
	}


	// ------------------------------------- 
	
	
	// Variables ajax
	protected $UpAbci_uniqid_form;
	protected $UpAbci_fragment;
	protected $UpAbci_uniqid_fichier;
	protected $UpAbci_fichier_nom_nettoye;
	protected $UpAbci_fichier_nom_originel;
	protected $UpAbci_fichier_size;
	protected $UpAbci_fichier_type;
	protected $UpAbci_blob_slice;
	protected $UpAbci_fichier_fin;
	protected $UpAbci_upload_fin;
	protected $UpAbci_input_name;
	
	// Variables php
	protected $dossier_destination;
	protected $dossier_temporaire;
	protected $cookie_time;
	protected $cookie_path;
	protected $adresse_relative;
	protected $verif_filesize_sup2Go;
	protected $cookie_name;
	protected $cookie_filesize;
	protected $config_chmod;
	protected $fichier_temp_adresse;
	protected $fichier_destination;
	protected $fichier_verif = false;
	protected $reponse_upload = array();


	public function __construct($dossier_destination,$dossier_temporaire,$cookie_heures = null,$cookie_path = null,$adresse_relative = null,$verif_filesize_sup2Go = false)
	{
		$this->dossier_destination = trim($dossier_destination);
		$this->dossier_temporaire = trim($dossier_temporaire);
		$this->cookie_time = is_numeric($cookie_heures) && $cookie_heures > 0 ? time()+3600*$cookie_heures : time()+3600*24;
		$this->cookie_path = trim($cookie_path) != false ?  trim($cookie_path) : '/';		
		$this->adresse_relative = trim($adresse_relative) != '' ? $adresse_relative : str_replace('\\','/',__DIR__).'/';
		$this->verif_filesize_sup2Go = trim($verif_filesize_sup2Go) != false;
		
		$this->GetPostFile();
	}


	private function GetPostFile()
	{
		$this->UpAbci_uniqid_form = filter_input(INPUT_POST, 'UpAbci_uniqid_form');
		$this->UpAbci_uniqid_form = isset($this->UpAbci_uniqid_form)? urldecode($this->UpAbci_uniqid_form) : null;
		
		$this->UpAbci_upload_fin = filter_input(INPUT_POST, 'UpAbci_upload_fin');
		$this->UpAbci_upload_fin = isset($this->UpAbci_upload_fin)? intval(urldecode($this->UpAbci_upload_fin)) : null;
		
		
		$this->UpAbci_fragment = isset($_FILES['UpAbci_fragment']) ? $_FILES['UpAbci_fragment'] : null;
				
		if(isset($this->UpAbci_fragment)) 
		{
			$this->UpAbci_static_id_form = filter_input(INPUT_POST, 'UpAbci_static_id_form');
			$this->UpAbci_static_id_form = urldecode($this->UpAbci_static_id_form);
			
			$this->UpAbci_uniqid_fichier = filter_input(INPUT_POST, 'UpAbci_uniqid_fichier');
			$this->UpAbci_uniqid_fichier = urldecode($this->UpAbci_uniqid_fichier);
		
			$this->UpAbci_fichier_nom_originel = filter_input(INPUT_POST, 'UpAbci_fichier_nom');
			$this->UpAbci_fichier_nom_originel = urldecode($this->UpAbci_fichier_nom_originel);
			
			$this->UpAbci_fichier_nom_nettoye = $this->NettoieNomFichier($this->UpAbci_fichier_nom_originel);	
			
			$this->UpAbci_fichier_size = filter_input(INPUT_POST, 'UpAbci_fichier_size');
			$this->UpAbci_fichier_size = urldecode($this->UpAbci_fichier_size);		
			
			$this->UpAbci_fichier_type = filter_input(INPUT_POST, 'UpAbci_fichier_type');
			$this->UpAbci_fichier_type = urldecode($this->UpAbci_fichier_type);
			
			$this->UpAbci_blob_slice = filter_input(INPUT_POST, 'UpAbci_blob_slice');
			$this->UpAbci_blob_slice = urldecode($this->UpAbci_blob_slice);
			$this->UpAbci_blob_slice = $this->UpAbci_blob_slice == 1;
			
			$this->UpAbci_fichier_fin = filter_input(INPUT_POST, 'UpAbci_fichier_fin');
			$this->UpAbci_fichier_fin = urldecode($this->UpAbci_fichier_fin);
			$this->UpAbci_fichier_fin = $this->UpAbci_fichier_fin == 1;

			$this->UpAbci_input_name = filter_input(INPUT_POST, 'UpAbci_input_name');
			$this->UpAbci_input_name = urldecode($this->UpAbci_input_name);

			$this->cookie_name = sha1($this->UpAbci_static_id_form.$this->UpAbci_fichier_nom_originel.$this->UpAbci_fichier_size);
			$cook_save = isset($_COOKIE[$this->cookie_name]) ? urldecode($_COOKIE[$this->cookie_name]) : null;
			$cook_save = isset($cook_save) ? explode('|',$cook_save) : null;
			$cook_save_temp_adresse = isset($cook_save[0]) ? $cook_save[0] : null; 
			$this->cookie_filesize = isset($cook_save[1]) ? $cook_save[1] : 0; 
		
			$this->fichier_temp_adresse = isset($cook_save_temp_adresse) ? $cook_save_temp_adresse : $this->adresse_relative.$this->dossier_temporaire.hash("sha256",(uniqid($this->UpAbci_uniqid_form,true).uniqid($this->UpAbci_uniqid_fichier,true)));	

			$this->fichier_destination = $this->adresse_relative.$this->dossier_destination.$this->UpAbci_fichier_nom_nettoye;
		}	
	}
		
	
	public function Upload ()
	{				
		if(isset($this->UpAbci_fragment)) 
		{
			// vérifs			
			if(empty($this->UpAbci_uniqid_fichier))
			{
				$this->reponse_upload['erreur'] = 'Identifiant de fichier non valide.';
				return false;
			}
						
			if($this->UpAbci_fichier_nom_originel == '')
			{
				$this->reponse_upload['erreur'] = 'Nom de fichier non valide.';
				return false;
			}
				
			if($this->UpAbci_fichier_size == '')
			{
				$this->reponse_upload['erreur'] = 'Taille du fichier non valide.';
				return false;
			}
			
			if(!is_uploaded_file($this->UpAbci_fragment['tmp_name']))
			{
				$this->reponse_upload['erreur'] = 'Provenance du fichier non valide.';
				return false;					
			}
			
			// uploads
			if(!$this->UpAbci_blob_slice) // si le fichier est d'un seul morceau
			{
				$this->fichier_temp_adresse = $this->UpAbci_fragment['tmp_name'];
			}
			else
			{
				 // On ouvre ou on crée le fichier
				$fichier_cible = @fopen($this->fichier_temp_adresse, 'a+');
				if($fichier_cible === false)
				{
					$this->reponse_upload['erreur'] = 'Erreur d\'ouverture du fichier temporaire.';
					return false;
				}
							
				// On ouvre le contenu téléchargé
				$upload_file = @fopen($this->UpAbci_fragment['tmp_name'], 'rb');
				if($upload_file === false)
				{
					$this->reponse_upload['erreur'] = 'Erreur d\'ouverture du contenu téléchargé.';
					return false;
				}
				
				// On lit son contenu dans une variable
				$upload_size = $this->UpAbci_fragment['size'];
				$upload_content = @fread($upload_file, $upload_size);
				if($upload_content === false)
				{
					$this->reponse_upload['erreur'] = 'Erreur de lecture du contenu téléchargé.';
					return false;
				}	
				
				fclose($upload_file);
				
				// On l'écrit dans le fichier temporaire
				if(@fwrite($fichier_cible, $upload_content) === false)
				{
					$this->reponse_upload['erreur'] = 'Erreur d\'écriture du contenu téléchargé.';
					return false;
				}	
				
				unset($upload_content);
				
				fclose($fichier_cible);
				
				$new_file_size = $this->cookie_filesize + $upload_size;
						
				setcookie($this->cookie_name,urlencode($this->fichier_temp_adresse.'|'.$new_file_size),$this->cookie_time,$this->cookie_path);
		
				if (!$this->UpAbci_fichier_fin)
				{
					$this->reponse_upload['resultat'] = 'continu';
					return true;
				}
			}
			
			if ($this->UpAbci_fichier_fin)
			{
				$this->fichier_verif = true;

				// vérification de l'intégrité du fichier (automatique pour les fichiers de moins de 2 Go)
				if ($this->verif_filesize_sup2Go || $this->UpAbci_fichier_size < $this->Return_Octets('2 Go'))
				{
					$size_upload = @filesize($this->fichier_temp_adresse);
					
					if($size_upload != $this->UpAbci_fichier_size)
					{
						$this->fichier_verif = false;
						
						if($this->UpAbci_blob_slice)
						{
							setcookie($this->cookie_name,"",time()-3600,$this->cookie_path);
							
							$this->reponse_upload['erreur'] = 'Erreur possible : la sauvegarde utilisée a été enregistrée lors d\'un instant critique.';
							return false;
						}
						else
						{
							$this->reponse_upload['erreur'] = 'Erreurs dans la vérification de l\'intégrité du fichier.';
							return false;
						}
					}
				}
			}
			
			return true;
		}
		
		return false;
	}
	
	
	public function Transfert ($fichier_destination = null)
	{		
		$this->fichier_destination = trim($fichier_destination) != '' ? $fichier_destination : $this->fichier_destination;
				
		if($this->UpAbci_fichier_fin)
		{
			if(isset($_COOKIE[$this->cookie_name]))
			{
				setcookie($this->cookie_name,"",time()-3600,$this->cookie_path);
			}		
			
			if(trim($this->fichier_destination) == '')
			{
				$this->reponse_upload['erreur'] = 'Destination du fichier non valide.';
				return false;
			}
			
			if(!is_file($this->fichier_temp_adresse) || !$this->fichier_verif)
			{
				$this->reponse_upload['erreur'] = 'Fichier temporaire non valide.';
				return false;
			}
			
			if(@rename($this->fichier_temp_adresse,$this->fichier_destination)) 
			{
				$this->reponse_upload['resultat'] = 'upload_ok';
				return true;
			}
			else
			{
				$this->reponse_upload['resultat'] = false;
				$this->reponse_upload['erreur'] = 'Problème dans le transfert du fichier.';
			}
		}
		
		return false;
	}


	// La fonction "NettoieNomFichier" est utilisée par défaut dans la fonction "GetPostFile" elle-même appelée par le constructeur de la classe
	public function NettoieNomFichier($nom_fichier)
	{
		$cible = array(
		'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ă', 'Ą',
		'Ç', 'Ć', 'Č', 'Œ',
		'Ď', 'Đ',
		'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ă', 'ą',
		'ç', 'ć', 'č', 'œ',
		'ď', 'đ',
		'È', 'É', 'Ê', 'Ë', 'Ę', 'Ě',
		'Ğ',
		'Ì', 'Í', 'Î', 'Ï', 'İ',
		'Ĺ', 'Ľ', 'Ł',
		'è', 'é', 'ê', 'ë', 'ę', 'ě',
		'ğ',
		'ì', 'í', 'î', 'ï', 'ı',
		'ĺ', 'ľ', 'ł',
		'Ñ', 'Ń', 'Ň',
		'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ő',
		'Ŕ', 'Ř',
		'Ś', 'Ş', 'Š',
		'ñ', 'ń', 'ň',
		'ò', 'ó', 'ô', 'ö', 'ø', 'ő',
		'ŕ', 'ř',
		'ś', 'ş', 'š',
		'Ţ', 'Ť',
		'Ù', 'Ú', 'Û', 'Ų', 'Ü', 'Ů', 'Ű',
		'Ý', 'ß',
		'Ź', 'Ż', 'Ž',
		'ţ', 'ť',
		'ù', 'ú', 'û', 'ų', 'ü', 'ů', 'ű',
		'ý', 'ÿ',
		'ź', 'ż', 'ž',
		'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р',
		'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'р',
		'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я',
		'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я'
		);
					 
		$rempl = array(
		'A', 'A', 'A', 'A', 'A', 'A', 'AE', 'A', 'A',
		'C', 'C', 'C', 'CE',
		'D', 'D',
		'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'a', 'a',
		'c', 'c', 'c', 'ce',
		'd', 'd',
		'E', 'E', 'E', 'E', 'E', 'E',
		'G',
		'I', 'I', 'I', 'I', 'I',
		'L', 'L', 'L',
		'e', 'e', 'e', 'e', 'e', 'e',
		'g',
		'i', 'i', 'i', 'i', 'i',
		'l', 'l', 'l',
		'N', 'N', 'N',
		'O', 'O', 'O', 'O', 'O', 'O', 'O',
		'R', 'R',
		'S', 'S', 'S',
		'n', 'n', 'n',
		'o', 'o', 'o', 'o', 'o', 'o',
		'r', 'r',
		's', 's', 's',
		'T', 'T',
		'U', 'U', 'U', 'U', 'U', 'U', 'U',
		'Y', 'Y',
		'Z', 'Z', 'Z',
		't', 't',
		'u', 'u', 'u', 'u', 'u', 'u', 'u',
		'y', 'y',
		'z', 'z', 'z',
		'A', 'B', 'B', 'r', 'A', 'E', 'E', 'X', '3', 'N', 'N', 'K', 'N', 'M', 'H', 'O', 'N', 'P',
		'a', 'b', 'b', 'r', 'a', 'e', 'e', 'x', '3', 'n', 'n', 'k', 'n', 'm', 'h', 'o', 'p',
		'C', 'T', 'Y', 'O', 'X', 'U', 'u', 'W', 'W', 'b', 'b', 'b', 'E', 'O', 'R',
		'c', 't', 'y', 'o', 'x', 'u', 'u', 'w', 'w', 'b', 'b', 'b', 'e', 'o', 'r'
		);
			 
		$nom_fichier = str_replace($cible, $rempl, $nom_fichier);// préserve le maximum de caractères utiles

		$nom_fichier = preg_replace('#[^.a-z0-9_-]+#i', '-', $nom_fichier);// uniquement alphanumérique et . et _ et -
		$nom_fichier = preg_replace('#-{2,}#','-',$nom_fichier);// supprime les occurences successives de '-'
							 
		return $nom_fichier;
	}
}
?>
