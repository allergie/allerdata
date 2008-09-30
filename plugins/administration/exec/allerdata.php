<?php
/*
 * Plugin allerdata / Admin du site
 * Licence GPL
 * (c) 2008 C.Morin Yterium pour Allerdata SARL
 *
 */

function allerdata_inserer_crayons($out){
	$out = pipeline('affichage_final', "</head>".$out);
	$out = str_replace("</head>","",$out);
	return $out;
}

function exec_allerdata_dist(){
	include_spip("inc/presentation");
	$titre = "Allerdata";
	$commencer_page = charger_fonction('commencer_page','inc');

	echo $commencer_page("&laquo; $titre &raquo;", "allerdata", "allerdata","");
	if (!autoriser('administrer','allerdata')) {
		echo _T('acces_interdit');
		echo fin_page();
		exit();
	}
	$page = _request('page');
	if ($page==null)
		$page= 'allerdata';

	echo debut_gauche('allerdata',true);
	include_spip('inc/allerdata');
	echo allerdata_barre_nav_gauche($page,array(
	array('titre'=>_L('Allerdata'),'page'=>'allerdata','icone'=>_DIR_PLUGIN_ALLERDATA."img_pack/allerdata-64.gif"),
	array('titre'=>_L("Comptes"),'page'=>'comptes','icone'=>_DIR_PLUGIN_ALLERDATA."img_pack/compte-64.gif",'url'=>generer_url_ecrire('allerdata','page=comptes')),
	array('titre'=>_T("allerdata:famille_taxos"),'page'=>'famille_taxos','icone'=>_DIR_PLUGIN_ALLERDATA."img_pack/album-64.png",'url'=>generer_url_ecrire('allerdata','page=famille_taxos')),
	array('titre'=>_T("allerdata:famille_mols"),'page'=>'famille_mols','icone'=>_DIR_PLUGIN_ALLERDATA."img_pack/album-red-64.png",'url'=>generer_url_ecrire('allerdata','page=famille_mols')),
	//array('titre'=>_L("Configuration"),'page'=>'cfg','icone'=>_DIR_PLUGIN_BOUTIQUE."img_pack/config-64.png",'url'=>generer_url_ecrire('allerdata','page=cfg')),
	));

	echo debut_droite('allerdata',true);
	$message = "";
	if (_request('creer_comptes')){
		include_spip('action/allerdata_creer_comptes');
		list($message,$ids) = allerdata_creer_comptes();				
	}

	switch ($page){
		case 'cfg':
			if (!$class || !class_exists($class)) 
				$class = 'cfg';
			$cfg = cfg_charger_classe($class);
			$config = & new $cfg(
				($nom = 'paiement'),
				$nom,
				''
				);
			if ($message = lire_meta('cfg_message_'.$GLOBALS['auteur_session']['id_auteur'])) {
				include_spip('inc/meta');
				effacer_meta('cfg_message_'.$GLOBALS['auteur_session']['id_auteur']);
				ecrire_metas();
				$config->message = $message;
			}
			$config->traiter();
			echo debut_cadre_trait_couleur('',true);
			echo $config->formulaire();
			echo fin_cadre_trait_couleur(true);
			break;
		default:
			$contexte = array('couleur_claire'=>$GLOBALS['couleur_claire'],'couleur_foncee'=>$GLOBALS['couleur_foncee'],'message'=>$message);
			$page = recuperer_fond("prive/$page",array_merge($contexte,$_GET));
			echo allerdata_inserer_crayons($page);
			break;
	}

	echo fin_gauche();
	echo fin_page();
}

?>