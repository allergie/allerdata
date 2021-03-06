<?php
/*
 * Plugin Bibliographie / Admin des biblios
 * Licence GPL
 * (c) 2008 C.Morin Yterium pour Allerdata SARL
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/actions');
include_spip('inc/editer');

function formulaires_editer_reactions_croisees_charger_dist($id_groupes_patient, $retour){
	$valeurs = array();
	$champs = array('id_produit1','id_produit2','niveau_rc_sens1','niveau_rc_sens2','remarques','risque_ccd') ;

	if ($id_groupes_patient = intval($id_groupes_patient)){
		$valeurs["id"] = $id_groupes_patient;
		$res = sql_select('id_reactions_croisee,id_version,'.implode(',',$champs),'tbl_reactions_croisees',"statut='publie' AND id_groupes_patient=".intval($id_groupes_patient),'','id_reactions_croisee');
		$valeurs["id_produit1-new"] = '';
		$valeurs["produit1-new"] = '';
		$valeurs["id_produit2-new"] = '';
		$valeurs["produit2-new"] = '';
		$valeurs["niveau_rc_sens1-new"] = '';
		$valeurs["niveau_rc_sens2-new"] = '';
		$valeurs["remarques-new"] = '';
		while ($row = sql_fetch($res)){
			$id = $row['id_reactions_croisee'];
			foreach($champs as $c){
				$valeurs["$c-$id"] = $row[$c];
			}
			$valeurs["id_version-$id"] = $row['id_version'];
			$valeurs['_hidden'] .= controles_md5($row,"ctr-$id-");

			// recuperer les nom des produits
			// purement indicatif -> sans valeur de controle
			include_spip('allerdata_fonctions');
			$n = sql_fetsel('nom_court,nom',allerdata_vue('tbl_items',$GLOBALS['spip_lang']),'id_item='.intval($row['id_produit1']));
			$valeurs["produit1-$id"] = $n['nom_court']?$n['nom_court']:$n['nom'];
			$n = sql_fetsel('nom_court,nom',allerdata_vue('tbl_items',$GLOBALS['spip_lang']),'id_item='.intval($row['id_produit2']));
			$valeurs["produit2-$id"] = $n['nom_court']?$n['nom_court']:$n['nom'];
			
			// proposer le dernier produit1 pour la prochaine saisie
			$valeurs["id_produit1-new"] = $valeurs["id_produit1-$id"];
			$valeurs["produit1-new"] = $valeurs["produit1-$id"];

			// proposer le dernier produit2 pour la prochaine saisie
			$valeurs["id_produit2-new"] = $valeurs["id_produit2-$id"];
			$valeurs["produit2-new"] = $valeurs["produit2-$id"];

			// ajouter cet id aux rc
			$valeurs['_liste_rc'][] = $id;
		}
		$valeurs['_liste_rc'][] = 'new';
		
	}
	return $valeurs;
}


function formulaires_editer_reactions_croisees_verifier_dist($id_groupes_patient, $retour){
	$erreurs = array();
	$liste_des_rc = explode(',',_request('_liste_rc'));
	$champs = array('id_produit1','id_produit2','niveau_rc_sens1','niveau_rc_sens2','remarques','risque_ccd') ;
	
	// regarder si on doit ajouter une nouvelle ligne !
	$ajoute_new = (_request('niveau_rc_sens1-new') OR _request('niveau_rc_sens2-new') OR _request('produit2-new') OR _request('remarques-new') OR _request('risque_CCD-new'));

	$couplets = array();
	foreach($liste_des_rc as $id_rc){
		if (intval($id_rc) OR $ajoute_new){
			$post = array();
			foreach($champs as $c)
				$post[$c] = _request("$c-$id_rc");
			foreach (array('id_produit1','id_produit2','niveau_rc_sens1') as $obli)
				if (!strlen($post[$obli]))
					$erreurs[preg_replace(',^id_,','',"$obli-$id_rc")] = _T('info_obligatoire');
			if ($post['id_produit1']==$post['id_produit2'])
				$erreurs["produit2-$id_rc"] = _T('editer_cohorte:erreur_produit_identique');

			// verifier qu'on a pas 2 RC avec les memes produits
			if (count(array_diff(array($post['id_produit1']."/".$post['id_produit2'],$post['id_produit2']."/".$post['id_produit1']),$couplets))<2){
				$erreurs["produit1-$id_rc"] = _T('editer_cohorte:rc_doublonne');
				$erreurs["produit2-$id_rc"] = _T('editer_cohorte:rc_doublonne');
			}
			else
				$couplets[] = $post['id_produit1']."/".$post['id_produit2'];

			// verifier le format des niveau_rc_sensx
			foreach (array('niveau_rc_sens1','niveau_rc_sens2') as $check){
				if (strlen($rc = $post[$check])){
					if (!preg_match(",^([+0]|[0-9]+/[0-9]+)$,",$rc))
						$erreurs["$check-$id_rc"] = _T('editer_cohorte:format_rc_invalide');
					else {
						$fraction = explode('/',$rc);
						if (count($fraction)==2 AND intval(reset($fraction))>intval(end($fraction)))
							$erreurs["$check-$id_rc"] = _T('editer_cohorte:format_rc_invalide');
					}
				}
			}
			if (intval($id_rc)){
				$conflits = controler_md5($post, $_POST, 'tbl_reactions_croisee', $id_rc, '', "ctr-$id_rc-");
				if (count($conflits)) {
					foreach($conflits as $champ=>$conflit){
						$erreurs["$champ-$id_rc"] .= _T("alerte_modif_info_concourante")."<br /><textarea readonly='readonly' class='forml'>".$conflit['base']."</textarea>";
					}
				}
			}
		}
	}

	return $erreurs;
}


function formulaires_editer_reactions_croisees_traiter_dist($id_groupes_patient, $retour){
	$liste_des_rc = explode(',',_request('_liste_rc'));
	$champs = array('id_produit1','id_produit2','niveau_rc_sens1','niveau_rc_sens2','remarques','risque_ccd') ;
	// vilain hack
	set_request('action','editer_tbl_reactions_croisee');
	$editer_tbl_reactions_croisee = charger_fonction('editer_tbl_reactions_croisee','action');
	
	$res = array('message_ok'=>'');
	
	// regarder si on doit ajouter une nouvelle ligne !
	$ajoute_new = (_request('niveau_rc_sens1-new') OR _request('niveau_rc_sens2-new') OR _request('produit2-new') OR _request('remarques-new') OR _request('risque_CCD-new'));
	foreach($liste_des_rc as $id_rc){
		if (intval($id_rc) OR $ajoute_new){
			$post = array('id_reactions_croisee'=>$id_rc,'id_groupes_patient'=>$id_groupes_patient);
			foreach($champs as $c)
				$post[$c] = _request("$c-$id_rc");
			// suppression eventuelle
			if (($s=_request("statut-$id_rc")) AND $s=='poubelle')
				$post['statut'] = $s;
			$post['risque_ccd'] = intval($post['risque_ccd']);
			$post['fleche_sens1'] = strlen($post['niveau_rc_sens1'])?
				($post['niveau_rc_sens1']=='+' OR intval($post['niveau_rc_sens1']))?'1':'0'
				:'';
			$post['fleche_sens2'] = strlen($post['niveau_rc_sens2'])?
				($post['niveau_rc_sens2']=='+' OR intval($post['niveau_rc_sens2']))?'1':'0'
				:'';

			list($id,$err) = $editer_tbl_reactions_croisee($id_rc,$post);
			if ($err OR !intval($id))
				$res['message_erreur'] .= "Erreur enregistrement #$id ";
			else {
				if (!intval($id_rc)){
					// annuler la saisie sur id new pour ne pas pre-remplir abusivement
					set_request('niveau_rc_sens1-new','');
					set_request('niveau_rc_sens2-new','');
					set_request('remarques-new','');
					set_request('risque_ccd-new','');
				}
				$res['message_ok'] .= "#$id ";
			}
		}
	}
	if ($res['message_ok']){
		$res['message_ok']  = 'rc ' . $res['message_ok']. 'enregistr&eacute;es';
		if (_request('finir')){
			if ($retour){
				$retour = parametre_url($retour,'retour|debutc_publie_prop|debutc_poubelle', '');
				$debut = "debutc_publie_prop";
				if (sql_getfetsel("statut", "tbl_groupes_patients", "id_groupes_patient=".intval($id_groupes_patient))=='poubelle')
				$debut = "debutc_poubelle";
				$res['redirect'] = ancre_url(parametre_url($retour,$debut,'@'.$id_groupes_patient),'cohorte'.$id_groupes_patient);
			}
		}
	}

	return $res;
}


?>