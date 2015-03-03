/* --------- 
UploadAjaxABCI Version 2.4
auteur Alain Bontemps ABCIWEB
----------- */


/* Configuration de l'upload, les deux premiers paramètres sont obligatoires, le troisième est indispensable pour un retour d'information.

- 1er paramètre : formulaire source (id ou class)
- 2eme paramètre : destination de la requête ajax (adresse du script d'upload côté serveur)
- 3eme paramètre : réponse de l'upload (id ou class)
- 4eme paramètre : booléen true ou false (false par défaut). Si le navigateur n'est pas compatible, false renvoie un message d'erreur, true envoie le formulaire à l'adresse indiquée dans l'attribut action du formulaire (permettrait l'upload directement en php pour des fichiers ne dépassant pas la configuration serveur 'upload_max_filesize').


var Up = new UploadAjaxABCI('#form_file','UploadAjaxABCI_Php_Load.php','#info_upload');

// exemple configuration optionnelle
Up.config.ajax_delai = 750;
// ...

// Terminer l'initialisation, DOM chargé, avec la fonction Start()
$(function()
{	
	Up.Start();
});
*/
var UploadAjaxABCI = function(form,upload_serveur,resultat,incompatible)
{	
	this.static_id_form = form;
	this.upload_serveur = upload_serveur;
	this.content_result_tag = resultat && resultat != '' ? resultat : '';
	this.incompatible = incompatible ? true : false;


	this.config = {};
	this.config.ajax_delai = 100; // en milli seconde, délai d'envoi de la requête ajax, utile pour éviter de saturer un serveur local poussif
	
	this.config.delai_refresh_infos_text = 1; // en seconde, fréquence de rafraîchissement des informations textuelles.  
	this.config.delai_calcul_temps_restant = 10; // en seconde, temps de calcul minimum avant l'affichage du temps restant estimé. Si trop court le résultat n'est pas significatif. Durant l'attente c'est le paramètre "info.temps_restant_calcul" qui s'affiche.
	
	this.config.temps_restant_requis = 30; // en secondes. Si le temps restant estimé pour l'upload du fichier est supérieur à cette valeur alors le formatage "data-upabcicss-temps-restant-requis" sera appliqué. Utilisée principalement pour ne pas faire afficher le temps restant pour les petits fichiers.
	
	this.config.confirme_reprise_sauvegarde = false; // booléen true ou false si reprise automatique sans demande de confirmation.
	
	this.config.static_id_form = true; //booléen true ou false. "true" lie les possibilités de reprise d'upload du fichier à l'identifiant du formulaire. Voir le mode d'emploi pour plus d'infos.
	
	this.config.requete_sans_fichier = true;// booléen true ou false si le formulaire ne doit pas être envoyé en absence de fichiers
	
	this.config.path_cookie = '/';// path du cookie 
	
	this.config.max_fragment = 4194304;// taille maximale du fragment en octet si le script ne trouve pas de champ nommé "UpAbci_max_fragment" (avec une valeur non nulle et supérieure à 1048576) dans le formulaire.
	
	this.config.uniqid_form = this.SHA1(this.Uniqid('UploadAjaxABCI',true));// valeur générée à l'affichage du script s'il ne trouve pas de champ nommé "UpAbci_uniqid_form" dans le formulaire
	
	this.config.requete_upload_fin = false;// booléen true ou false. Si = true, une requête additionnelle est envoyée au script php pour indiquer la fin de traitement de tous les fichiers (excepté si config.requete_sans_fichier = false et qu'aucun fichier n'a été soumis).
	
	this.config.max_weight_apercu = 4; // en Mo. Poids maximal de chaque image pour prévisualisation. Si cette valeur est dépassée pour au moins une image dans un upload multiple, aucune vignette ne sera affichée afin de préserver une cohérence dans l'affichage des informations 
	
	this.config.total_max_weight_apercu = 16; // en Mo. Poids total maximal de toutes les images (en téléchargement multiple) pour  prévisualisation. Si dépassé aucune vignette ne sera affichée

	this.config.max_width_apercu = 80; // en pixels, largeur maximale de l'aperçu
	
	this.config.max_height_apercu = 80; // en pixels, hauteur maximale de l'aperçu
	
	this.config.max_filesize_utilisateur = null;// taille maximale du fichier utilisateur. Si = 0 ou null aucune vérification n'est faite sur la taille du fichier. Accepte un nombre en octets ou une chaine de caractère formatée avec les unités. Les espaces et la lettre "o" sont optionnels et le formatage ne tient pas compte de la casse. Par exemple 1073741824 = '1073741824o' = '1024Mo' = '1024 M' = '1048576k' = '1048576 Ko' = '1 go' = '1 G' (les nombres décimaux sont acceptés). Faire un contrôle supplémentaire côté serveur par sécurité (pas obligatoire mais très vivement conseillé).
	
	this.config.extensions_autorisees = [];// Tableau des extensions autorisées (les variantes majuscules/minuscules sont automatiquement prises en compte). Si le tableau est vide aucune vérification n'est faite. Faire un contrôle supplémentaire côté serveur par sécurité (pas obligatoire mais très vivement conseillé).
	
	
	
	this.info = {};
	this.info.status = {};
	this.info.confirme_reprise_sauvegarde = {};
	
	// Annonces html (éventuellement vous pouvez rentrer du html plutôt que du texte plat)
	this.info.temps_restant_calcul = 'calcul en cours'; // s'affiche durant la période définie par la variable "config.delai_calcul_temps_restant"
	
	this.info.status.ok = 'Téléchargement ok';// complété éventuellement par un message provenant du script php d'upload
	
	this.info.status.encours = 'Téléchargement en cours';
	
	this.info.status.arret = 'Arrêt';// (sur demande utilisateur)
	
	this.info.status.erreur = 'Echec du téléchargement. ';// complété automatiquement par l'erreur serveur ou par une erreur générée par le script php d'upload ou par une erreur utilisateur (cf les deux lignes ci-dessous)
	
	this.info.status.erreur_taille = 'Dépassement de la taille maximale autorisée. ';// message si dépassement de "config.max_filesize_utilisateur"
	
	this.info.status.erreur_extension = 'Extension non valide. ';// message si l'extension ne fait pas partie du tableau "config.extensions_autorisees"
	
	this.info.status.incompatible = 'Navigateur incompatible.';
	
	this.info.status.espace = '&nbsp;';// espacement par défaut entre les valeurs et leurs unités
	
	// Annonce javascript (boite de confirmation javascript pour la reprise d'upload partiel sauvegardé) 
	this.info.confirme_reprise_sauvegarde.nom = 'Récupération du fichier : '; // Annonce javascript suivie du nom du fichier
	
	this.info.confirme_reprise_sauvegarde.taille = '\nSauvegarde en cours : '; // Annonce (suite) suivie de la taille de la sauvegarde (\n pour retour ligne car la boite de confirmation est en javascript)
	
	this.info.confirme_reprise_sauvegarde.message = '\n\nCliquez sur "OK" pour compléter ce fichier ou sur "Annuler" pour réinitialiser le téléchargement.';// Annonce (suite) dernière ligne


	this.params = $(['nom','taille','apercu_img','progressionG','progressionT','sauvegarde','pourcentage','temps_passe','temps_restant','status','arret']);
	
	/* non utilisé juste pour info. Ordre de l'application des styles :
this.CssData = $(['submit-file', 'submit', 'sauvegarde', 'encours', 'temps-restant-requis', 'result', 'result-arret', 'result-partiel', 'result-erreur', 'result-ok', 'fin-upload', 'sauvegarde-ft', 'fin-traitement']);

- A noter que si des fichiers sont joints dans le formulaire, 'submit-file' est persistant par rapport à 'submit' (le style 'submit-file' est appliqué avant et après le style appliqué par 'submit').
	*/
	
	this.content_result = $();
	this.tab_fichiers = [];	
	this.tab_fichiers_change = [];
	this.data_files = $();
	this.data_nofile = $();
	this.bouton_submit = $();
	this.obj_ini = {};
	this.obj_ini.html = {};
	this.obj_ini.es = {};
	this.count_files = 0;
	this.infos_form = $();
	this.arreter_tout = $();
	this.requete = true;
	

	this.GetCssData = function (attr_data)
	{
		var obj_css = {};
		var item_css = [];
		$.each(attr_data.split(';'), function(i,v)
		{
			item_css = v.split(':'); 
			if(item_css.length == 2)
			{
				obj_css[item_css[0]] = item_css[1];
			}
		});
		return obj_css;
	}
	
	
	this.GetTime = function ()
	{
		var t = new Date();
		return t.getTime();
	}
	
	
	this.FormateTime = function (temps,defaut,espace)
	{
		var time_format = {};
		time_format.h = Math.floor(temps/3600);
		var reste = temps%3600;
		time_format.m = Math.floor(reste/60);
		if(time_format.h == 0){time_format.s = Math.floor(reste%60)}	
		
		var time_f = '';
		$.each(time_format,function(f,v)
		{
			if(v > 0) {time_f += v+espace+f+espace}
		})
		
		time_f = time_f != '' ? time_f.substring(0,time_f.length-espace.length) : defaut;
		
		return time_f;
	}
	
	
	this.FormateBits = function (Bytes,espace)
	{
		var Type = ["", "K", "M", "G", "T", "P", "E", "Z", "Y"];
		var Index = 0;
		while(Bytes >= 1024)
		{
			Bytes /= 1024;
			Index++;
		}
		
		Bytes = Type[Index] == 'M' || Type[Index] == 'K' || Type[Index] == '' ?  Math.round(Bytes*10)/10 : Math.round(Bytes*1000)/1000;
		var unite = Type[Index] == '' ? 'octets' : 'o';
		return(Bytes+''+espace+''+Type[Index]+''+unite);
	}	
	
	
	this.ReturnOctets = function (bytes)
	{
		var val = String(bytes) ;
		val = val.replace(/,/,'.');
		val = val.replace(/ /g,'');
		val = val.replace(/[oO]/,'');
		
		var last = val.substr(val.length-1,1).toLowerCase();
		val = parseFloat(val);

		switch(last)
		{
			case 't':  val *= 1024;
			case 'g':  val *= 1024;
			case 'm': val *= 1024;
			case 'k':  val *= 1024;
		}
		return val;
	}
	
	
	this.ErreurUtilisateur = function (tab_erreur)
	{
		var message = '';
		$.each(tab_erreur, function(i,n) 
		{
			switch (n)
			{
				case 'taille' : message += that.info.status.erreur_taille; break;
				case 'extension' : message += that.info.status.erreur_extension; break;
			}	
		});
		return message;
	}
	
	
	this.Pourcentage = function (qte,size,espace)
	{
		return size != undefined && size > 0 ? Math.round(qte/size*100)+''+espace+'%' : 0+''+espace+'%';
	}	
		

	this.GetUniqueID = function (a,b,c,d)
	{
		var t = this.SHA1(a.toString()+b.toString()+c.toString()+d.toString());
		return 'a'+this.SHA1(this.Uniqid(t,true));
	}
	
	
	this.FormatIni = function (objet,lib)	
	{
		var html = objet.html();
		var html_split = $.trim(html).split(' ');
		var num = parseInt(html_split[0]);
		var html_split_length = html_split.length;
		
		var espace = html_split_length > 1 ? ' ' : '';

		html_split = html_split_length > 1 ? [] : $.trim(html).split('&nbsp;');
		espace = html_split.length > 1 ? '&nbsp;' : espace;
		
		espace = !isNaN(num)? espace : this.info.status.espace;
		
		this.obj_ini.html[lib] = html;
		this.obj_ini.es[lib] = espace;
	}

	
	this.QteSauvegarde = function (cook_name)
	{
		var cook_save_upload = this.docCookies.getItem(cook_name);
		var tab_cookie = cook_save_upload != null ? decodeURIComponent(cook_save_upload).split('|') : null;
		
		return tab_cookie != null && tab_cookie[1] != undefined ? parseInt(tab_cookie[1]) : 0;
	}
	
	
	var that = this;
	
	this.ImgPrevisualisation = function (fichier,cible)
	{
		var image = new Image();
		
		var charge = new FileReader();
		
		charge.onloadend = function()
		{
			image.onload = function() 
			{
				/* tout ce bin's pour compatibilité IE 10 qui ne supporte pas à la fois max-width et max-height.
				Sinon pas besoin de image.onload et on peut faire directement :
				$(image).css({'max-width':that.config.max_width_apercu,'max-height':that.config.max_height_apercu})
				*/
				var imageWidth = image.width,
					imageHeight = image.height;
		
				if (imageWidth > imageHeight) 
				{
				  if (imageWidth > that.config.max_width_apercu) 
				  {
					imageHeight *= that.config.max_width_apercu / imageWidth;
					imageWidth = that.config.max_width_apercu;
				  }
				}
				else 
				{
				  if (imageHeight > that.config.max_height_apercu) 
				  {
					imageWidth *= that.config.max_height_apercu / imageHeight;
					imageHeight = that.config.max_height_apercu;
				  }
				}	
				
				$(image).css({'width':imageWidth,'height':imageHeight});
				
				cible.append(image);
			}
			image.src = charge.result;
		}
		charge.readAsDataURL(fichier);
	}
		
	
	this.SetCssData = function (content,datacss)
	{
		var attr_data;
		
		attr_data = this.content_result.attr('data-upabcicss-'+datacss);
		if(attr_data != undefined) {this.content_result.css(this.GetCssData(attr_data))}
		
		attr_data = this.infos_form.attr('data-upabcicss-'+datacss);
		if(attr_data != undefined) {this.infos_form.css(this.GetCssData(attr_data))}
		
		attr_data = content.attr('data-upabcicss-'+datacss);
		if(attr_data != undefined) {content.css(this.GetCssData(attr_data))}
		
		content.find("*").each(function(){									
			attr_data = $(this).attr('data-upabcicss-'+datacss);
			if(attr_data != undefined) {$(this).css(that.GetCssData(attr_data))}
		});
	}


	this.ArretFormate = function (event)	
	{
		if(that.tab_fichiers_change[event.data.index] != undefined)
		{
			that.tab_fichiers_change[event.data.index]['arret'] = 1;
		}
		else if(that.tab_fichiers[event.data.index] != undefined)
		{
			that.tab_fichiers[event.data.index]['arret'] = 1;
		}
		
		if (event.data.qte_save > 0)
		{
			that.SetCssData(event.data.infos_html,'sauvegarde');
			that.SetCssData(event.data.infos_html,'result');
			that.SetCssData(event.data.infos_html,'result-arret');
			that.SetCssData(event.data.infos_html,'result-partiel');
		}
		else
		{
			that.SetCssData(event.data.infos_html,'result');
			that.SetCssData(event.data.infos_html,'result-arret');
		}
		
		if(event.data.status != undefined) {event.data.status.html(that.info.status.arret)}
	}
	
	
	this.ArreterToutAvantEnvoi = function()
	{
		if(that.arreter_tout && that.tab_fichiers_change.length > 0)
		{
			that.arreter_tout.off("click").one("click",function()
			{
				$.each(that.tab_fichiers_change,function(i,v)
				{
					if(v.obj.arret) {v.obj.arret.off("click")}

					v.arreter_tout = 1;
											
					var format = {};
					format.data = {};
					format.data.infos_html = v.infos_html;
					format.data.status = v.obj.status;
					format.data.index = i;
					format.data.qte_save = that.QteSauvegarde(v.cook_name);
			
					that.ArretFormate(format);
				});
			});
		}
	}

}







UploadAjaxABCI.prototype.Start = function() 
{
	var formulaire = $(this.static_id_form);
	
	if(formulaire.length == 0) 
	{
		alert('Configuration UploadAjaxABCI : identifiant de formulaire non valide');
		return false
	}
	
	if(this.content_result_tag != '' && $(this.content_result_tag).length == 0)
	{
		alert('UploadAjaxABCI : identifiant de renvoi des résultats non valide');
		return false;
	}
	
	
	this.data_files = formulaire.find('input[type="file"]');
	
	this.content_result = $(this.content_result_tag);
	
	var uniqid_form = formulaire.find('input[name="UpAbci_uniqid_form"]');
	uniqid_form = uniqid_form.length > 0 ? uniqid_form.val() : '';
	this.config.uniqid_form = $.trim(uniqid_form) != '' ?  uniqid_form : this.config.uniqid_form;
			
	var max_filesize = formulaire.find('input[name="UpAbci_max_fragment"]');
	max_filesize = max_filesize.length > 0 ? parseInt(max_filesize.val()) : undefined ;
	max_filesize = isNaN(max_filesize) || max_filesize < 1048576 ? this.config.max_fragment : max_filesize;
	// - 10240 arbitrairement pour laisser un peu de marge pour les données texte si upload_max_filesize = post_max_size
	this.config.max_fragment = max_filesize - 10240;
	
	this.static_id_form = this.config.static_id_form == true ? this.static_id_form : '';

	this.config.max_weight_apercu = parseInt(this.config.max_weight_apercu) * 1048576; 
	this.config.total_max_weight_apercu = parseInt(this.config.total_max_weight_apercu) * 1048576;

	this.config.max_filesize_utilisateur = $.trim(this.config.max_filesize_utilisateur) != '' ? this.ReturnOctets(this.config.max_filesize_utilisateur) : 0;
	
	this.config.extensions_autorisees = $.map(this.config.extensions_autorisees, function(e) {return (e.toLowerCase())});
	
	var content_result_html = $();

	// On copie le contenu du conteneur des résultats pour retrouver les valeurs initiales en cas de soumission multiple du formulaire sans rafraîchissement de la page.
	if(this.content_result.length > 0) 
	{
		content_result_html = this.content_result.html();
	}	
	
			
	var tab_champ = [];
	var infos_up = $();
	var that = this;
	
	this.data_files.each(function(i,c)
	{
		$(this).on('click',function(){$(this).val('')}).on('change',function(event)
		{
			event.preventDefault();
				
			var content_infos_upload_temp = $('<div>');
		
			// Défini les variables "arreter_tout" et création d'un conteneur temporaire (avec "content_infos_upload_temp") pour intégrer les uploads multiples.
			if(that.content_result.length > 0) 
			{
				that.content_result.empty().append(content_result_html);
				
				that.arreter_tout = that.content_result.find(".UpAbci_arreter_tout");
					
				infos_up = that.content_result.find(".UpAbci_infos_upload");
								
				infos_up.wrap(content_infos_upload_temp);				
				
				// Nécessaire pour définir le bloc dans le DOM
				content_infos_upload_temp = infos_up.parent();			
			}
			
			// Conteneur vierge que l'on remplira avec des clônes de infos_up
			content_infos_upload_temp.empty();

			// Tableau des fichiers pour chaque champ, ajout du nom du champ de type file et info suppplémentaire pour savoir si le fichier est une image
			var sup_max_preview = false;
			var total_max_preview = 0;
			
			var upabci_input_nom = this.name;
			tab_champ[i] = [];
			$(this.files).each(function(j,f)
			{
				if (f.type.match('image.*'))
				{
					f.upabci_input_image = 1;
					total_max_preview += f.size;
					if(f.size > that.config.max_weight_apercu)
					{
						sup_max_preview = true;
					}
				}
				else
				{
					f.upabci_input_image = 0;
				}
				
				f.upabci_erreur = [];
				
				if(that.config.max_filesize_utilisateur > 0 && f.size > that.config.max_filesize_utilisateur)
				{
					f.upabci_erreur.push('taille');
				}
				
				if(that.config.extensions_autorisees.length > 0 && $.inArray(f.name.substr(f.name.lastIndexOf(".")+1).toLowerCase(),that.config.extensions_autorisees) == -1)
				{
					f.upabci_erreur.push('extension');				
				}
				
				f.upabci_input_nom = upabci_input_nom;
				tab_champ[i].push(f);
			})
			
			
			that.tab_fichiers_change = [];
			var index = 0;
			var params_fich = {};
			// Enregistrement des paramètres des fichiers dans that.tab_fichiers_change et construction du html d'information
			$.each(tab_champ, function(e,f)
			{
				if(f)
				{
					$.each(f,function(g,v)
					{	
						var obj = {};
						var status = $();
						var infos_html = $();
						var info_e = $();
		
						var uniqid_fichier = that.GetUniqueID(that.static_id_form,index,v.name,v.size);
						var cook_name = that.SHA1(that.static_id_form.toString()+v.name.toString()+v.size.toString());

						var qte_save = that.QteSauvegarde(cook_name);
							
						if(infos_up.length > 0) 
						{
							// On clône "infos_up" pour chaque fichier
							infos_html = infos_up.clone(true);
							status = infos_html.find('.UpAbci_status');
							
						
							// FormatIni récupère les valeurs par défaut + l'espacement entre les valeurs et les unités
							// + Initialisation des paramètres et enregistrement des objets dans "that.tab_fichiers_change"
							that.params.each(function(m,q)
							{			
								info_e = infos_html.find('.UpAbci_'+q);
								if(info_e.length > 0) 
								{
									switch(q)
									{
										case 'nom' 				: info_e.html(v.name); break;								
										
										case 'taille' 			: that.FormatIni(info_e,q);
																  info_e.html(that.FormateBits(v.size,that.obj_ini.es[q]));
																  break;
																  
										case 'apercu_img'		: if(v.upabci_input_image == 1)
																  {
																	  if(!sup_max_preview && that.config.total_max_weight_apercu >= total_max_preview)
																	  {
																	  	that.ImgPrevisualisation(v,info_e);
																	  }
																  }
																  break;
										
										case 'progressionG' 	: info_e.get(0).value = qte_save; 
																  info_e.get(0).max = v.size;
																  break;
																				
										case 'progressionT' 	: that.FormatIni(info_e,q); 
																  if(qte_save > 0){info_e.html(that.FormateBits(qte_save,that.obj_ini.es.progressionT))}
																  break;	
											
										case 'sauvegarde' 		: that.FormatIni(info_e,q); 
																  if(qte_save > 0){info_e.html(that.FormateBits(qte_save,that.obj_ini.es.sauvegarde))}
																  break;
																						
										case 'pourcentage' 		: that.FormatIni(info_e,q); 
																  info_e.html(that.Pourcentage(qte_save,v.size,that.obj_ini.es.pourcentage));
																  break;
												
										case 'temps_passe' 		: that.FormatIni(info_e,q); break;
											
										case 'temps_restant' 	: that.FormatIni(info_e,q); break;
																						
										case 'arret'			: $(info_e).one
																  (
																	"click",
																	{infos_html:infos_html, status:status, index:index,qte_save:qte_save}, 
																	that.ArretFormate
																  );
																  break;
									}
									obj[q] = info_e;
								}
							});
						}
						
						if (qte_save > 0)
						{
							that.SetCssData(infos_html,'sauvegarde');
						}
						
						// Ecriture des blocs d'information dans le conteneur temporaire
						content_infos_upload_temp.append(infos_html);
						index++;
						
						params_fich = {'fichier':v,'uniqid_fichier':uniqid_fichier,'obj':obj,'infos_html':infos_html,'cook_name':cook_name,'qte_save':qte_save,'qte_upload':0,'time_start':0,'timeaffiche':0,'arret':0,'arreter_tout':0,'nb_cycles':0};
						
						that.tab_fichiers_change.push(params_fich);					
					})
				}
			});
			
			
			// Suppression du conteneur temporaire (content_infos_upload_temp)
			if(infos_up.length > 0) {that.content_result.find(".UpAbci_infos_upload").unwrap()}
			
			that.ArreterToutAvantEnvoi();

			if (that.tab_fichiers_change.length > 0) {that.SetCssData(that.content_result,'submit-file')}
			
			return false;
		})
	});
	
	
	
	formulaire.on('submit',function(event)
	{
		tab_champ = [];
		
		if(window.FormData === undefined) 
		{
			if(that.incompatible) 
			{
				return true;
			}
			else 
			{
				alert(that.info.status.incompatible);
				return false;
			}
		}
		
		event.preventDefault();
		
		that.requete = true;	
		
		that.tab_fichiers = that.tab_fichiers_change;
		that.tab_fichiers_change = [];
		that.count_files = 0;
		
		if(that.tab_fichiers.length == 0 && content_result_html) 
		{// Réinitialise le bloc en cas de soumission multiple sans rafraîchissement de la page pour un second envoi sans fichiers faisant suite à un premier envoi avec fichiers
			that.content_result.empty().append(content_result_html)
		}

		that.infos_form = that.content_result.find(".UpAbci_infos_form");
		
		that.bouton_submit = $(this).find('input[type="submit"]');
		
		// Récupère les input du formulaire excepté les types file et le bouton submit
		that.data_nofile = $(this).serialize();

		$.each(that.tab_fichiers, function(e,f)
		{// compte le nombre de fichiers réellement soumis (que l'on a pas arrêté volontairement avant l'envoi)
			if(f.arret == 0){that.count_files++;}
		})
		
		that.data_files.each(function(i,c) 
		{	
			$(this).val('');// efface les contenus des champs files
		});
		
		// Applique les CSS submit
		if(that.content_result.length > 0){that.SetCssData(that.content_result,'submit')}
	
		if (that.count_files > 0 || that.config.requete_sans_fichier) 
		{
			if (that.count_files > 0) {that.SetCssData(that.content_result,'submit-file')};
			// Met disable pour éviter des fausses manoeuvres durant l'upload
			that.data_files.each(function(){$(this).prop("disabled",true)});
			that.bouton_submit.each(function(){$(this).prop("disabled",true)});
			
			that.Upload();
		} 
		else 
		{
			that.SetCssData(that.content_result,'fin-traitement');
		}
		
		return false;
	});	
}







UploadAjaxABCI.prototype.Upload = function(i, start, end) 
{
	var index = i != undefined ? i : 0;
	
	var ft = this.tab_fichiers[index];
		
	var start_cut = start != undefined ? start : 0;
	
	var end_cut = end != undefined ? end : this.config.max_fragment;

	var qte_save = 0;
	
	var cook_name = null;
	
	var temps_restant_req = 0;

	var that = this;
	
	if(ft == undefined) 
	{
		// On laisse passer une fois pour faire la requête ajax (témoin this.requete = true) si pas de fichiers et si config.requete_sans_fichier = true ou si config.requete_upload_fin = true et count_files > 0
		if(((this.count_files == 0 && this.config.requete_sans_fichier) || (this.config.requete_upload_fin && this.count_files > 0)) && this.requete)
		{
			this.requete = false;
		}
		else
		{
			// Remet enable les champs file et submit quand plus de fichiers dans tab_fichiers
			this.data_files.each(function(){$(this).prop("disabled",false)});
			this.bouton_submit.each(function(){$(this).prop("disabled",false)});
						
			if(this.count_files > 0) 
			{
				this.SetCssData(this.content_result,'fin-upload')
				
				var sav = false;
				var t = this;
				$.each(this.tab_fichiers, function(i,v)
				{
					if(t.docCookies.hasItem(v.cook_name)){sav = true}
				});
				if(sav){this.SetCssData(this.content_result,'sauvegarde-ft')};
			}
			
			this.SetCssData(this.content_result,'fin-traitement');
			
			return false;
		}
	}
	else
	{
		ft.nb_cycles++;
		
		cook_name = ft.cook_name;

		if(ft.fichier.upabci_erreur.length > 0)
		{
			if(ft.infos_html) 
			{
				// CSS 'result'
				this.SetCssData(ft.infos_html,'result');
				// CSS 'result-erreur'
				this.SetCssData(ft.infos_html,'result-erreur');
			}
			
			if(ft.obj.status)
			{
				var message = this.ErreurUtilisateur(ft.fichier.upabci_erreur);
				// HTML status erreur
				ft.obj.status.html(this.info.status.erreur+''+message);
			}	
			this.Upload(++index);
			return false;
		}
		
		
		// Première boucle du fichier (start_cut = 0) si l'arrêt n'est pas programmé
		if (start_cut == 0 && ft.arret == 0) 
		{		
			// CSS 'encours'
			if(ft.infos_html) this.SetCssData(ft.infos_html,'encours');
			
			// HTML 'encours'
			if(ft.obj.status) {ft.obj.status.html(this.info.status.encours)}
			
			qte_save = ft.qte_save;
			
			// si une sauvegarde existe
			if (qte_save > 0)
			{
				if(!this.config.confirme_reprise_sauvegarde || (confirm(this.info.confirme_reprise_sauvegarde.nom+''+ft.fichier.name+''+this.info.confirme_reprise_sauvegarde.taille+''+this.FormateBits(qte_save,this.obj_ini.es.sauvegarde)+''+this.info.confirme_reprise_sauvegarde.message)))
				{
					start_cut = qte_save;
					end_cut = start_cut + this.config.max_fragment;
				}
				else
				{
					qte_save = 0;
					ft.qte_save = 0;
					this.docCookies.removeItem(cook_name,this.config.path_cookie);
				}
			}
			
			// Initialisation pour afficher le temps restant
			ft.timeaffiche = 0;
			ft.time_start = this.GetTime();
		}
		else 
		{	
			// Cherche la quantité sauvegardée dans le cookie de récupération
			qte_save = this.QteSauvegarde(cook_name);
		}
		
		if(ft.infos_html && qte_save > 0) 
		{
			this.SetCssData(ft.infos_html,'sauvegarde');
		}
		
		// Si la variable end_cut est supérieure à la taille du fichier, alors end_cut = la taille du fichier
		end_cut = end_cut > ft.fichier.size ? ft.fichier.size : end_cut;
	
		// Envoyé côté serveur pour utiliser la fragmentation ou l'upload direct
		var blob_slice = ft.fichier.size > this.config.max_fragment ? 1 : 0;
	
		// On crée notre morceau de fichier si besoin (blob_slice == 1)
		var fragment = blob_slice == 1 ? ft.fichier.slice(start_cut, end_cut) : ft.fichier;
		
		// Envoyé pour finaliser l'upload côté serveur
		var fichier_fin = end_cut == ft.fichier.size ? 1 : 0;
	}	



	function ArretVerif()
	{// pour éviter que l'arrêt s'effectue durant le traitement serveur... (ft.nb_cycles-1)*this.config.ajax_delai = correction pour avoir la vitesse "réelle" de transfert (derait être pondéré également par le temps passé côté serveur).
		var time_diff = (that.GetTime() - ft.time_start - ((ft.nb_cycles-1)*that.config.ajax_delai))/1000;
		var vitesse = (ft.qte_upload - ft.qte_save)/time_diff;
		// ...on interrompt la requête au moins une seconde avant la fin de l'envoi du fragment. Sinon on laisse faire et c'est le changement d'état de ft.arret qui annulera le départ de la requête suivante.
		return end_cut - ft.qte_upload - vitesse > 0;
	}
	
			
	
	function ArreterTout (xhr)
	{
		if(that.arreter_tout && ft && ft.arreter_tout != 1)
		{	
			that.arreter_tout.off("click").one("click",function()
			{
				$.each(that.tab_fichiers,function(i,v)
				{
					if(v.obj.arret) {v.obj.arret.off("click")}

					v.arreter_tout = 1;
					if(i >= index) // On ne modifie pas les fichiers déjà traités
					{
						var format = {};
						format.data = {};
						format.data.infos_html = v.infos_html;
						format.data.status = v.obj.status;
						format.data.index = i;
						format.data.qte_save = that.QteSauvegarde(v.cook_name);
				
						that.ArretFormate(format);						
					}
				});
				
				if(ArretVerif())
				{
					xhr.abort();
				}				
			});
		}
	}



	function Arret (xhr)
	{
		if(ft)
		{
			var status = ft.obj.status ? ft.obj.status : $();
			var qte_s = that.QteSauvegarde(cook_name);

			if(ft.arret == 1)
			{
				xhr.abort();
				
				AfficheInfos(qte_s,'now');
				// Correction pour AfficheInfos qui affiche le temps restant, on remet les valeurs initiales
				if(ft.obj.temps_restant) {ft.obj.temps_restant.html(that.obj_ini.html.temps_restant)}

				AfficheProgressionG(qte_s);
				// on garde l'ordre d'application ds styles
				if(ft.infos_html)
				{
					if(qte_s > 0){that.SetCssData(ft.infos_html,'sauvegarde')}
					that.SetCssData(ft.infos_html,'result');
					that.SetCssData(ft.infos_html,'result-arret');
					if(qte_s > 0){that.SetCssData(ft.infos_html,'result-partiel')}
				}
				
				// HTML	status  -> info interne 'status.arret'
				if(ft.obj.status) {ft.obj.status.html(that.info.status.arret)}
								
				if(ft.obj.arret){ft.obj.arret.off("click")}
			}
			else if(ft.obj.arret)
			{
				ft.obj.arret.off("click").one("click",{infos_html:ft.infos_html, status:status, index:index, qte_save:qte_s},that.ArretFormate).one("click",function()
				{
					if(ArretVerif())
					{
						xhr.abort();
					}
				}); 
			}
		}
	}	


	function AfficheSauvegarde()
	{
		ft.obj.sauvegarde.html(that.FormateBits(qte_save,that.obj_ini.es.sauvegarde));
	}	


	function AffichePourcentage(loaded)
	{
		ft.obj.pourcentage.html(that.Pourcentage(loaded,ft.fichier.size,that.obj_ini.es.pourcentage));
	}


	function AfficheProgressionT(loaded)
	{
		ft.obj.progressionT.html(that.FormateBits(loaded,that.obj_ini.es.progressionT));
	}


	function AfficheTempsPasse(time_diff)
	{			
		ft.obj.temps_passe.html(that.FormateTime(time_diff, that.obj_ini.html.temps_passe, that.obj_ini.es.temps_passe));
	}


	function AfficheTempsRestant(time_diff,prevision_time)
	{		
		var time_f = that.FormateTime(prevision_time,that.obj_ini.html.temps_restant,that.obj_ini.es.temps_restant);
		
		// minimum avant l'affichage du temps restant (si trop court le résultat n'est pas significatif) 
		time_f = time_diff > that.config.delai_calcul_temps_restant ? time_f : that.info.temps_restant_calcul;
					
		ft.obj.temps_restant.html(time_f);
	}


	function CalculTempRestant(loaded,time_diff)
	{
		var vitesse = (loaded - ft.qte_save)/time_diff;
		return vitesse > 0 ? (ft.fichier.size - loaded)/vitesse : 0;
	}


	function AfficheInfos(loaded,now)
	{
		// time_now et timeaffiche pour éviter un affichage trop fréquent et visuellement gênant
		var get_time = that.GetTime();
		var time_diff = (get_time - ft.time_start)/1000;
		var time_now = get_time/1000;
		var prevision_time;
		
		if(temps_restant_req == 0)// n'a besoin d'être afficher qu'une fois
		{
			prevision_time = CalculTempRestant(loaded,time_diff);	
			if(prevision_time > that.config.temps_restant_requis && ft.infos_html)
			{
				that.SetCssData(ft.infos_html,'temps-restant-requis');
				temps_restant_req = 1;
			}
		}

		// minimum entre chaque rafraîchissement des informations textuelles
		if(((time_now - ft.timeaffiche) > that.config.delai_refresh_infos_text) || now != undefined)
		{
			ft.timeaffiche = time_now;
							
			if(ft.obj.sauvegarde) {AfficheSauvegarde()}
			
			if(ft.obj.pourcentage) {AffichePourcentage(loaded)}
			
			if(ft.obj.progressionT) {AfficheProgressionT(loaded)}
			
			if(ft.obj.temps_passe && ft.time_start > 0) {AfficheTempsPasse(time_diff)}
			
			if(ft.obj.temps_restant && ft.time_start > 0) 
			{
				prevision_time = prevision_time != undefined ? prevision_time : CalculTempRestant(loaded,time_diff); 
				AfficheTempsRestant(time_diff,prevision_time);
			}
		}
	}	


	function AfficheProgressionG(loaded)
	{
		if(ft.obj.progressionG)
		{
			ft.obj.progressionG.get(0).value = loaded;
			ft.obj.progressionG.get(0).max = ft.fichier.size;	
		}
	}


	function Progression(event)
	{
		var up = event.loaded;
		//var total = event.total;
		
		var uploaded = up+start_cut;
		
		if(ft) 
		{
			ft.qte_upload = uploaded;
			AfficheProgressionG(uploaded);
			AfficheInfos(uploaded);
		}
	}


	// On crée un formulaire et on insère les données
	var formData = new FormData();
	
	// Passe les input de type non file dans Formdata (option non indispensable pour le traitement des fichiers)
	if(this.data_nofile.length > 0)
	{
		var tabv = [];
		$.each(this.data_nofile.split('&'),function(i,v)
		{
		  tabv = v.split('=');
		  if(tabv[0] != 'UpAbci_uniqid_form')
		  {formData.append(tabv[0],tabv[1])}
		});
	}
	
	formData.append("UpAbci_uniqid_form",encodeURIComponent(this.config.uniqid_form)); // Identifiant unique du formulaire
	if(ft)
	{
		formData.append("UpAbci_static_id_form",encodeURIComponent(this.static_id_form));// Identifiant de formulaire
		formData.append("UpAbci_uniqid_fichier",encodeURIComponent(ft.uniqid_fichier));// Identifiant de fichier, sert pour créer l'adresse du fichier temporaire
		formData.append("UpAbci_fichier_nom",encodeURIComponent(ft.fichier.name)); // Le nom du fichier uploadé
		formData.append("UpAbci_fichier_size",encodeURIComponent(ft.fichier.size)); // La taille du fichier uploadé
		formData.append("UpAbci_fichier_type",encodeURIComponent(ft.fichier.type)); // Le type du fichier uploadé
		formData.append("UpAbci_blob_slice",encodeURIComponent(blob_slice)); // Indique si le fichier est tronqué en plusieurs morceaux
		formData.append("UpAbci_fichier_fin",encodeURIComponent(fichier_fin));// Indique le dernier morceau si le fichier est en plusieurs parties
		formData.append("UpAbci_input_name",encodeURIComponent(ft.fichier.upabci_input_nom));// Indique le nom du champ de type file (option non indispensable pour le traitement des fichiers mais permet par exemple d'avoir des répertoires de destination différents si plusieurs champs de types file dans le même formulaire)
		formData.append("UpAbci_fragment",fragment);// Le morceau du fichier ou le fichier entier si blob_slice vaut false
	}
	else if (this.config.requete_upload_fin == true)
	{
		// Envoie l'état de fin de traitement du formulaire en différenciant un formulaire sans fichiers  avec 0.
		var upload_fin = this.count_files == 0 ? 0 : 1;
		formData.append("UpAbci_upload_fin",encodeURIComponent(upload_fin));
	}

	$.ajax({
		url: this.upload_serveur,
		type: "POST",
		data: formData,
		xhr: function() 
		{
			var myXhr = $.ajaxSettings.xhr();
			if(myXhr.upload)
			{
				myXhr.upload.addEventListener('progress',Progression, false);
				/*myXhr.upload.addEventListener('loadend',FinUpload, false); 'loadend' pas fiable car le déclenchement de cet évènement s'effectue à des moments différents suivant les navigateurs : dès l'envoi de la requête complète avec chrome (comportement souhaité) mais firefox et IE attendent la réponse du serveur (en septembre 2014)*/
			}
			return myXhr;
		},
				
		processData: false, 
		contentType: false, 
		dataType: "json",
		
		beforeSend: function (xhr) 
		{
			ArreterTout (xhr);
			Arret (xhr);
		}})
		.done(function(reponse)
        {	
			var resultat = reponse.resultat != undefined ? reponse.resultat : null;
			var erreur_upload = reponse.erreur != undefined ? reponse.erreur : '';
			var add_ok = reponse.ok != undefined ? reponse.ok : '';
			var infos_form = reponse.infos_form != undefined ? reponse.infos_form : null;
			
			if(infos_form && that.infos_form)
			{	// Informations générale de formulaire 
				that.SetCssData(that.infos_form,'infos_form');
				that.infos_form.html(infos_form);
			}
			
			if(resultat == 'upload_fin') 
			{					
				that.Upload(++index);
			}
			else if(resultat == 'continu') 
			{					
				start_cut = end_cut;
				end_cut = end_cut + that.config.max_fragment;
				
				setTimeout(function(){that.Upload(index, start_cut, end_cut);}, that.config.ajax_delai);
			}
			else 
			{	
				if(ft)
				{
					if(ft.obj.arret){ft.obj.arret.off("click")}
									
					// HTML progression textuelle, remise de la valeur initiale
					if(ft.obj.progressionT) {ft.obj.progressionT.html(that.obj_ini.html.progressionT)}
					// HTML temps restant, remise de la valeur initiale
					if(ft.obj.temps_restant) {ft.obj.temps_restant.html(that.obj_ini.html.temps_restant)}
				}
				
				if(resultat == 'upload_ok') 
				{
					if(ft)
					{
						// CSS
						if(ft.infos_html) 
						{
							// CSS 'result'
							that.SetCssData(ft.infos_html,'result');
							// CSS 'result-ok'
							that.SetCssData(ft.infos_html,'result-ok');
						}
												
						// HTML progression graphique -> correction à 100% 
						if(ft.obj.progressionG) {AfficheProgressionG(ft.fichier.size)}
						// HTML pourcentage -> correction à 100% 
						if(ft.obj.pourcentage) {AffichePourcentage(ft.fichier.size)}
						// HTML	status  -> info interne	'status.ok'			
						if(ft.obj.status) {ft.obj.status.html(that.info.status.ok+''+add_ok)}
						// HTML sauvegarde -> remise de la valeur initiale
						if(ft.obj.sauvegarde) {ft.obj.sauvegarde.html(that.obj_ini.html.sauvegarde)}
					}
					
					that.Upload(++index);
				}
				else if(that.docCookies.hasItem(cook_name))
				{ 	
					if(ft)
					{
						// CSS
						if(ft.infos_html) 
						{
							//CSS 'sauvegarde'
							that.SetCssData(ft.infos_html,'sauvegarde');
							// CSS 'result'
							that.SetCssData(ft.infos_html,'result');
							// CSS 'result-partiel'
							that.SetCssData(ft.infos_html,'result-partiel');
							// CSS 'result-erreur'
							that.SetCssData(ft.infos_html,'result-erreur');
						}
							
						// HTML	status  -> info interne 'status.erreur' + erreur_upload renvoyée par le script php
						if(ft.obj.status){ft.obj.status.html(that.info.status.erreur+''+erreur_upload)}					
						// HTML	
						var qte_s = that.QteSauvegarde(cook_name);
						AfficheInfos(qte_s,'now');
						// Correction pour AfficheInfos qui affiche le temps restant, on remet les valeurs initiales
						if(ft.obj.temps_restant) {ft.obj.temps_restant.html(that.obj_ini.html.temps_restant)}
	
						AfficheProgressionG(qte_s);
					}
					
					that.Upload(++index);
				}
				else
				{	//console.log('success et pas de sauvegarde');
					// CSS
					if(ft)
					{
						if(ft.infos_html) 
						{
							// CSS 'result'
							that.SetCssData(ft.infos_html,'result');
							// CSS 'result-erreur'
							that.SetCssData(ft.infos_html,'result-erreur');
						}
										
						// HTML	status -> info interne 'status.erreur' + erreur_upload renvoyée par le script php
						if(ft.obj.status) {ft.obj.status.html(that.info.status.erreur+''+erreur_upload)}
						// HTML sauvegarde -> remise de la valeur initiale
						if(ft.obj.sauvegarde) {ft.obj.sauvegarde.html(that.obj_ini.html.sauvegarde)}
					}
					
					that.Upload(++index);
				}
			}
		})
		.fail(function( xhr, textStatus, errorThrown)
        {
			if(ft)
			{
				if(ft.obj.arret){ft.obj.arret.off("click")}
				// HTML progression textuelle, remise de la valeur initiale
				if(ft.obj.progressionT) {ft.obj.progressionT.html(that.obj_ini.html.progressionT)}
				// HTML temps restant, remise de la valeur initiale
				if(ft.obj.temps_restant) {ft.obj.temps_restant.html(that.obj_ini.html.temps_restant)}
			}
			
			if(that.docCookies.hasItem(cook_name))
			{	//console.log('erreur et sauvegarde, textStatus : '+textStatus+'erreur errorThrown : '+errorThrown);
				if(ft)
				{		
					// CSS 
					// ft.arret != 1 car l'annulation volontaire est traitée dans la fonction Arret
					if(ft.infos_html && ft.arret != 1) 
					{
						// CSS 'sauvegarde'
						that.SetCssData(ft.infos_html,'sauvegarde');
						// CSS 'result'
						that.SetCssData(ft.infos_html,'result');
						// CSS 'result-partiel'
						that.SetCssData(ft.infos_html,'result-partiel');
						// CSS 'result-erreur'
						that.SetCssData(ft.infos_html,'result-erreur');
					}
		
					// HTML	status  -> info interne 'status.erreur'
					if(ft.obj.status && ft.arret != 1) {ft.obj.status.html(that.info.status.erreur)}
					// HTML	
					var qte_s = that.QteSauvegarde(cook_name);
					AfficheInfos(qte_s,'now');
					// Correction pour AfficheInfos qui affiche le temps restant, on remet les valeurs initiales
					if(ft.obj.temps_restant) {ft.obj.temps_restant.html(that.obj_ini.html.temps_restant)}
		
					AfficheProgressionG(qte_s);
				}
			
				that.Upload(++index);
			}
			else
			{	//console.log('erreur sans sauvegarde, textStatus : '+textStatus+'erreur errorThrown : '+errorThrown);
				// textStatus != 'abort' car l'annulation volontaire est traitée dans la fonction Arret
				if(ft)
				{
					// CSS
					if(ft.infos_html && ft.arret != 1) 
					{
						// CSS 'result'
						that.SetCssData(ft.infos_html,'result');
						// CSS 'result-erreur'
						that.SetCssData(ft.infos_html,'result-erreur');
					}
										
					// HTML sauvegarde, remise de la valeur html initiale
					if(ft.obj.sauvegarde && ft.arret != 1) {ft.obj.sauvegarde.html(that.obj_ini.html.sauvegarde)}
					// HTML	status -> info interne 'status.erreur' + erreur serveur					
					if(ft.obj.status && ft.arret != 1) {ft.obj.status.html(that.info.status.erreur+''+errorThrown)}
				}
				
				that.Upload(++index);
			}
		})
	
	return false;
}







/*-------------------------------          Fonctions utilitaires            --------------------------------*/







UploadAjaxABCI.prototype.Uniqid = function(prefix, more_entropy) {
  //  discuss at: http://phpjs.org/functions/uniqid/
  // original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  //  revised by: Kankrelune (http://www.webfaktory.info/)
  //        note: Uses an internal counter (in php_js global) to avoid collision
  //        test: skip
  //   example 1: uniqid();
  //   returns 1: 'a30285b160c14'
  //   example 2: uniqid('foo');
  //   returns 2: 'fooa30285b1cd361'
  //   example 3: uniqid('bar', true);
  //   returns 3: 'bara20285b23dfd1.31879087'

  if (typeof prefix === 'undefined') {
    prefix = '';
  }

  var retId;
  var formatSeed = function(seed, reqWidth) {
    seed = parseInt(seed, 10)
      .toString(16); // to hex str
    if (reqWidth < seed.length) { // so long we split
      return seed.slice(seed.length - reqWidth);
    }
    if (reqWidth > seed.length) { // so short we pad
      return Array(1 + (reqWidth - seed.length))
        .join('0') + seed;
    }
    return seed;
  };

  // BEGIN REDUNDANT
  if (!this.php_js) {
    this.php_js = {};
  }
  // END REDUNDANT
  if (!this.php_js.uniqidSeed) { // init seed with big random int
    this.php_js.uniqidSeed = Math.floor(Math.random() * 0x75bcd15);
  }
  this.php_js.uniqidSeed++;

  retId = prefix; // start with prefix, add current milliseconds hex string
  retId += formatSeed(parseInt(new Date()
    .getTime() / 1000, 10), 8);
  retId += formatSeed(this.php_js.uniqidSeed, 5); // add seed hex string
  if (more_entropy) {
    // for more entropy we add a float lower to 10
    retId += (Math.random() * 10)
      .toFixed(8)
      .toString();
  }

  return retId;
}



UploadAjaxABCI.prototype.SHA1 = function(msg) {
/**
*
*  Secure Hash Algorithm (SHA1)
*  http://www.webtoolkit.info/
*
**/
    function rotate_left(n,s) {
        var t4 = ( n<<s ) | (n>>>(32-s));
        return t4;
    };
    function lsb_hex(val) {
        var str="";
        var i;
        var vh;
        var vl;
        for( i=0; i<=6; i+=2 ) {
            vh = (val>>>(i*4+4))&0x0f;
            vl = (val>>>(i*4))&0x0f;
            str += vh.toString(16) + vl.toString(16);
        }
        return str;
    };
    function cvt_hex(val) {
        var str="";
        var i;
        var v;
        for( i=7; i>=0; i-- ) {
            v = (val>>>(i*4))&0x0f;
            str += v.toString(16);
        }
        return str;
    };
    function Utf8Encode(string) {
        string = string.replace(/\r\n/g,"\n");
        var utftext = "";
        for (var n = 0; n < string.length; n++) {
            var c = string.charCodeAt(n);
            if (c < 128) {
                utftext += String.fromCharCode(c);
            }
            else if((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            }
            else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }
        }
        return utftext;
    };
    var blockstart;
    var i, j;
    var W = new Array(80);
    var H0 = 0x67452301;
    var H1 = 0xEFCDAB89;
    var H2 = 0x98BADCFE;
    var H3 = 0x10325476;
    var H4 = 0xC3D2E1F0;
    var A, B, C, D, E;
    var temp;
    msg = Utf8Encode(msg);
    var msg_len = msg.length;
    var word_array = new Array();
    for( i=0; i<msg_len-3; i+=4 ) {
        j = msg.charCodeAt(i)<<24 | msg.charCodeAt(i+1)<<16 |
        msg.charCodeAt(i+2)<<8 | msg.charCodeAt(i+3);
        word_array.push( j );
    }
    switch( msg_len % 4 ) {
        case 0:
            i = 0x080000000;
        break;
        case 1:
            i = msg.charCodeAt(msg_len-1)<<24 | 0x0800000;
        break;
        case 2:
            i = msg.charCodeAt(msg_len-2)<<24 | msg.charCodeAt(msg_len-1)<<16 | 0x08000;
        break;
        case 3:
            i = msg.charCodeAt(msg_len-3)<<24 | msg.charCodeAt(msg_len-2)<<16 | msg.charCodeAt(msg_len-1)<<8    | 0x80;
        break;
    }
    word_array.push( i );
    while( (word_array.length % 16) != 14 ) word_array.push( 0 );
    word_array.push( msg_len>>>29 );
    word_array.push( (msg_len<<3)&0x0ffffffff );
    for ( blockstart=0; blockstart<word_array.length; blockstart+=16 ) {
        for( i=0; i<16; i++ ) W[i] = word_array[blockstart+i];
        for( i=16; i<=79; i++ ) W[i] = rotate_left(W[i-3] ^ W[i-8] ^ W[i-14] ^ W[i-16], 1);
        A = H0;
        B = H1;
        C = H2;
        D = H3;
        E = H4;
        for( i= 0; i<=19; i++ ) {
            temp = (rotate_left(A,5) + ((B&C) | (~B&D)) + E + W[i] + 0x5A827999) & 0x0ffffffff;
            E = D;
            D = C;
            C = rotate_left(B,30);
            B = A;
            A = temp;
        }
        for( i=20; i<=39; i++ ) {
            temp = (rotate_left(A,5) + (B ^ C ^ D) + E + W[i] + 0x6ED9EBA1) & 0x0ffffffff;
            E = D;
            D = C;
            C = rotate_left(B,30);
            B = A;
            A = temp;
        }
        for( i=40; i<=59; i++ ) {
            temp = (rotate_left(A,5) + ((B&C) | (B&D) | (C&D)) + E + W[i] + 0x8F1BBCDC) & 0x0ffffffff;
            E = D;
            D = C;
            C = rotate_left(B,30);
            B = A;
            A = temp;
        }
        for( i=60; i<=79; i++ ) {
            temp = (rotate_left(A,5) + (B ^ C ^ D) + E + W[i] + 0xCA62C1D6) & 0x0ffffffff;
            E = D;
            D = C;
            C = rotate_left(B,30);
            B = A;
            A = temp;
        }
        H0 = (H0 + A) & 0x0ffffffff;
        H1 = (H1 + B) & 0x0ffffffff;
        H2 = (H2 + C) & 0x0ffffffff;
        H3 = (H3 + D) & 0x0ffffffff;
        H4 = (H4 + E) & 0x0ffffffff;
    }
    var temp = cvt_hex(H0) + cvt_hex(H1) + cvt_hex(H2) + cvt_hex(H3) + cvt_hex(H4);
    return temp.toLowerCase();
}



UploadAjaxABCI.prototype.docCookies = {
/*\ var docCookies
|*| https://developer.mozilla.org/en-US/docs/DOM/document.cookie
|*| Syntaxes:
|*|
|*|  * docCookies.setItem(name, value[, end[, path[, domain[, secure]]]])
|*|  * docCookies.getItem(name)
|*|  * docCookies.removeItem(name[, path], domain)
|*|  * docCookies.hasItem(name)
|*|  * docCookies.keys()
\*/

  getItem: function (sKey) {
    return decodeURIComponent(document.cookie.replace(new RegExp("(?:(?:^|.*;)\\s*" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=\\s*([^;]*).*$)|^.*$"), "$1")) || null;
  },
  setItem: function (sKey, sValue, vEnd, sPath, sDomain, bSecure) {
    if (!sKey || /^(?:expires|max\-age|path|domain|secure)$/i.test(sKey)) { return false; }
    var sExpires = "";
    if (vEnd) {
      switch (vEnd.constructor) {
        case Number:
          sExpires = vEnd === Infinity ? "; expires=Fri, 31 Dec 9999 23:59:59 GMT" : "; max-age=" + vEnd;
          break;
        case String:
          sExpires = "; expires=" + vEnd;
          break;
        case Date:
          sExpires = "; expires=" + vEnd.toUTCString();
          break;
      }
    }
    document.cookie = encodeURIComponent(sKey) + "=" + encodeURIComponent(sValue) + sExpires + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "") + (bSecure ? "; secure" : "");
    return true;
  },
  removeItem: function (sKey, sPath, sDomain) {
    if (!sKey || !this.hasItem(sKey)) { return false; }
    document.cookie = encodeURIComponent(sKey) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT" + ( sDomain ? "; domain=" + sDomain : "") + ( sPath ? "; path=" + sPath : "");
    return true;
  },
  hasItem: function (sKey) {
    return (new RegExp("(?:^|;\\s*)" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=")).test(document.cookie);
  },
  keys: /* optional method: you can safely remove it! */ function () {
    var aKeys = document.cookie.replace(/((?:^|\s*;)[^\=]+)(?=;|$)|^\s*|\s*(?:\=[^;]*)?(?:\1|$)/g, "").split(/\s*(?:\=[^;]*)?;\s*/);
    for (var nIdx = 0; nIdx < aKeys.length; nIdx++) { aKeys[nIdx] = decodeURIComponent(aKeys[nIdx]); }
    return aKeys;
  }
}
