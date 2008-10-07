<?php
/*
 * Plugin allerdata / Admin du site
 * Licence GPL
 * (c) 2008 C.Morin Yterium pour Allerdata SARL
 *
 */

/**
 * Recuperer les enfants d'un item
 * - eventuellement d'un type donne
 * - directement contenus ou non
 * - publie ou en attente
 *
 * @param int $id_item
 * @param string $type_item
 * @param bool $direct
 * @param bool $tous
 * @return array
 */

function allerdata_les_enfants($id_item,$type_item='',$direct=true,$tous=true){
	include_spip('allerdata_fonctions');
	return array_map('reset',sql_allfetsel('i.id_item','tbl_est_dans as ed JOIN tbl_items AS i ON i.id_item=ed.id_item',
	'ed.est_dans_id_item='.intval($id_item)
	.($direct?" AND ed.directement_contenu=1":"")
	.($type_item?' AND '.sql_in('i.id_type_item',allerdata_id_type_item($type_item,$tous)):"")
	));
}


/**
 * Recuperer les parents d'un item
 * - eventuellement d'un type donne
 * - directement contenant ou non
 * - publie ou tous
 *
 * @param int $id_item
 * @param string $type_item
 * @param bool $direct
 * @param bool $tous
 * @return array
 */
function allerdata_les_parents($id_item,$type_item='',$direct=true,$tous=true){
	include_spip('allerdata_fonctions');
	return array_map('reset',sql_allfetsel('i.id_item','tbl_est_dans as ed JOIN tbl_items AS i ON i.id_item=ed.est_dans_id_item',
	'ed.id_item='.intval($id_item)
	.($direct?" AND ed.directement_contenu=1":"")
	.($type_item?' AND '.sql_in('i.id_type_item',allerdata_id_type_item($type_item,$tous)):"")
	));
}

function allerdata_modifier_les_parents($id_item,$parents){
	if (!is_array($parents)) $parents = array($parents);
	$anciens = allerdata_les_parents($id_item);
	// enlever les anciens qui ne sont plus la
	$remove = array_diff($anciens,$parents);
	foreach ($remove as $id_parent)
		allerdata_remove_filiation($id_item,$id_parent);
	// ajouter les nouveaux
	$add = array_diff($parents,$anciens);
	foreach ($add as $id_parent)
		allerdata_create_filiation($id_item,$id_parent);
}

/**
 * Affilier un ou des enfants a un parent en direct ou non, 
 * et propager a tous ses ascendants
 *
 * @param int/array $id_items
 * @param int $id_parent
 * @param bool $direct
 */
function allerdata_create_filiation($id_items, $id_parent, $direct = true, $propage = true) {
	
	// regarder si la filiation existe deja ou non
	$les_enfants = allerdata_les_enfants($id_parent,'',$direct);
	// accepter un appel avec un seul item
	$id_items = is_array($id_items)?$id_items:array($id_items);

	// prendre les items qui ne sont pas deja listes dans les enfants
	$id_items = array_diff($id_items,$les_enfants);
	if (count($id_items)){
		foreach($id_items as $id_item){
			$insert[] = array('id_item'=>$id_item,'est_dans_id_item'=>$id_parent,'date_est_dans'=>'NOW()','directement_contenu'=>$direct?1:0);
		}
		spip_log("+ items ".implode(',',$id_items)." affilies a l'item $id_parent".($direct ? " en direct":""),'allerdata_arbo');
		sql_insertq_multi('tbl_est_dans',$insert);

		if ($propage){
			include_spip('allerdata_fonctions');
			$racines = allerdata_item_racines($id_parent);
			foreach($racines as $racine)
				allerdata_verifier_filiations($racine);
		}
			/*
			if ($direct){
				// si c'est une affiliation directe, on met a jour les enfants indirects du parent
				// verifier la filiation des items que l'on vient d'affilier pour etre sur d'avoir tous les enfants
				// et lister de ce fait tous les enfants indirects 
				$les_enfants = array();
				foreach($id_items as $id_item)
					$les_enfants = array_merge($les_enfants,allerdata_verifier_filiations($id_item));
				// on en profite pour ajouter le parent dans la liste, pour assurer l'auto lien sur lui meme
				$les_enfants[] = $id_parent;
				// et les rattacher au parent
				allerdata_create_filiation($les_enfants, $id_parent, false);
			}
			else {
				// sinon on propage les enfants indirects aux parents directs
				$les_parents = allerdata_les_parents($id_parent);
				if (count($les_parents))
					foreach($les_parents as $parent){
						// on ajoute le parent pour verifier le lien sur lui meme
						allerdata_create_filiation(array_merge($id_items,array($parent)), $parent, false);
					}
			}
		}*/
	}
}


/**
 * De-Affilier un ou des enfants a un parent, 
 * et propager a tous ses ascendants
 *
 * @param int/array $id_items
 * @param int $id_parent
 * @param bool $direct
 */
function allerdata_remove_filiation($id_items, $id_parent, $propage = true) {
	
	// regarder si la filiation existe bien
	$les_enfants = allerdata_les_enfants($id_parent,'',false);
	// accepter un appel avec un seul item
	$id_items = is_array($id_items)?$id_items:array($id_items);

	// prendre les items qui sont bien listes dans les enfants
	$id_items = array_intersect($id_items,$les_enfants);
	if (count($id_items)){
		spip_log("X items ".implode(',',$id_items)." enleves de l'item $id_parent",'allerdata_arbo');
		sql_delete('tbl_est_dans',"est_dans_id_item=".$id_parent." AND ".sql_in("id_item",$id_items));
		if ($propage){
			include_spip('allerdata_fonctions');
			$racines = allerdata_item_racines($id_parent);
			foreach($racines as $racine)
				allerdata_verifier_filiations($racine);
		}
	}
}

function allerdata_verifier_filiations($id_item){
	$enfants = array();
	// verifier la filiation de tous les enfants directs
	$enfants_directs = allerdata_les_enfants($id_parent);
	foreach($enfants_directs as $id_item){
		$enfants = array_merge($enfants,allerdata_verifier_filiations($id_item));
	}
	// ajouter lui meme dans la liste des enfants indirect pour creer l'auto lien
	$enfants = array_merge($enfants,array($id_item));
	// creer les liens manquants eventuels
	allerdata_create_filiation($enfants, $id_item, false, false);
	// completer avec les enfants directs
	$enfants = array_merge($enfants,$enfants_directs);
	
	// verifier que l'info en base correspond bien
	$les_enfants = allerdata_les_enfants($id_item,'',false);
	// enlever les eventuels sur numeraires errones
	allerdata_remove_filiation(array_diff($les_enfants,$enfants),false);

	// renvoyer tous les enfants indirects de cet item pour que le parent se les recuperes
	return $enfants;
}

function allerdata_verifier_arbre(){
	// recuperer tous les items racine (sans parent autre qu'eux meme)
	$racines = sql_select("id_item","tbl_items","id_item NOT IN (".sql_get_select('DISTINCT zzz.id_item','tbl_est_dans AS zzz','zzz.id_item<>zzz.est_dans_id_item').")");
	while ($row = sql_fetch($racines))
		allerdata_verifier_filiations($row['id_item']);
}

?>