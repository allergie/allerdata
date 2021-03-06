<?php
session_start();
function rc($p1,$p2,$type_etude) {
	include_spip('base/abstract_sql');
	
	$css_path = dirname(find_in_path('proto.css'));
	
	$tableau_produits = $items_fils_de = array();
	if (is_numeric($p1)) $tableau_produits[] = $p1; 
	if (is_numeric($p2)) $tableau_produits[] = $p2;
	 
	$produits = implode(",", $tableau_produits);
	
	include_spip('allerdata_fonctions');
	$vue = allerdata_vue('tbl_items',$GLOBALS['spip_lang']);
	foreach ($tableau_produits as $id_item_source) {
		$query = "SELECT tbl_items.id_item FROM $vue AS tbl_items, tbl_est_dans
			WHERE est_dans_id_item = $id_item_source
			AND tbl_est_dans.id_item = tbl_items.id_item
			AND tbl_items.statut='publie'";
		$res = spip_query($query);

		while($row = sql_fetch($res)) {
			$items_fils_de[$row['id_item']][] = $id_item_source;
		}
	}

	// Requete identique a action/liste_des_rc
	$query = "SELECT DISTINCT 
			tbl_reactions_croisees.id_reactions_croisee, 
			tbl_items.id_item AS idp1, 
			tbl_items.nom AS p1, 
			tbl_items.id_type_item AS type1,
      tbl_reactions_croisees.id_produit1,
			tbi3.id_type_item AS id_type_item1, 
			tbl_reactions_croisees.fleche_sens1, 
			tbl_reactions_croisees.niveau_rc_sens1, 
			tbl_reactions_croisees.fleche_sens2, 
			tbl_reactions_croisees.niveau_rc_sens2, 
			tbi4.id_type_item AS id_type_item2, 
			tbl_reactions_croisees.id_produit2, 
			tbl_items_1.id_item AS idp2, 
			tbl_items_1.nom AS p2, 
			tbl_items_1.id_type_item AS type2,
      tbl_reactions_croisees.produits_differents, 
			tbl_est_dans.est_dans_id_item AS id_s1, 
			tbl_est_dans_1.est_dans_id_item AS id_s2
		FROM $vue as tbi3, $vue as tbi4,
			(((
				(tbl_est_dans AS tbl_est_dans_1 INNER JOIN (tbl_reactions_croisees INNER JOIN tbl_est_dans ON tbl_reactions_croisees.id_produit1 = tbl_est_dans.id_item) ON tbl_est_dans_1.id_item = tbl_reactions_croisees.id_produit2)
				INNER JOIN $vue AS tbl_items ON tbl_est_dans_1.id_item = tbl_items.id_item)
					INNER JOIN tbl_types_items ON tbl_items.id_type_item = tbl_types_items.id_type_item)
						INNER JOIN $vue AS tbl_items_1 ON tbl_est_dans.id_item = tbl_items_1.id_item)
							INNER JOIN tbl_types_items AS tbl_types_items_1 ON tbl_items_1.id_type_item = tbl_types_items_1.id_type_item
		WHERE (
				((tbl_est_dans.est_dans_id_item) In ($produits)) AND ((tbl_est_dans_1.est_dans_id_item) In ($produits))
				
				AND tbl_reactions_croisees.id_produit1 = tbi3.id_item
				AND tbl_reactions_croisees.id_produit2 = tbi4.id_item
				AND tbl_items.statut='publie'
				AND tbl_items_1.statut='publie'
				AND tbl_reactions_croisees.statut='publie'
			)";
	
  switch ($type_etude) {
    case 'pp' :
      $query .= "AND (tbl_items.id_type_item IN (5,3,13)) AND (tbl_items_1.id_type_item IN (5,3,13))";
      $title = _T("ad:titre_onglet_etudes_produits_produits");
    break;
    case 'pa' :
      $query .= "AND (
                  ((tbl_items.id_type_item IN (5,3,13)) AND (tbl_items_1.id_type_item NOT IN (5,3,13)))
                  OR ((tbl_items.id_type_item NOT IN (5,3,13)) AND (tbl_items_1.id_type_item IN (5,3,13)))
                )";
      $title = _T("ad:titre_onglet_etudes_produits_allergenes");
    break;
    case 'aa' :
      $query .= "AND (tbl_items.id_type_item NOT IN (5,3,13)) AND (tbl_items_1.id_type_item NOT IN (5,3,13))";
      $title = _T("ad:titre_onglet_etudes_allergenes_allergenes");
    break;
  }

  $res = spip_query($query);
	$result = '';
	$biblio = '';
	$count = 0;
	$premiere_ligne = true; 

	if (!sql_count($res)) {
  		$title .= " (0)";
    	return "<div id='main'><h1 class='title' style='display:none;'>$title</h1><h1 class='titArticle'>"._T('ad:aucune_etude_de_ce_type')."</h1>"
			  .get_minitexte($p1,$p2)
			  ."</div>";
  }
    
	while ($row = sql_fetch($res)){
		// Trouver le parent pour tester si les 2 produits sont dans la meme famille
		if (((isset($items_fils_de[$row['idp1']])) && (isset($items_fils_de[$row['idp2']]))
				&& array_intersect($items_fils_de[$row['idp1']],$items_fils_de[$row['idp2']])) == false) {
			
			$count += 1;
			$querybiblio = "SELECT tbl_bibliographies.id_bibliographie, tbl_bibliographies.citation, tbl_bibliographies.abstract,
								tbl_groupes_patients.id_groupes_patient, tbl_groupes_patients.pays, tbl_groupes_patients.description as description_groupe, tbl_groupes_patients.nb_sujets, tbl_groupes_patients.pool, tbl_groupes_patients.qualitatif,
								tbl_reactions_croisees.id_reactions_croisee, tbl_items.id_item as i1, tbl_items.nom as p1,tbl_items.fonction_classification as p1_fonction, tbl_reactions_croisees.niveau_rc_sens1, 
									tbl_reactions_croisees.niveau_rc_sens2, tbl_items_1.id_item as i2, tbl_items_1.nom as p2,tbl_items_1.fonction_classification as p2_fonction, tbl_reactions_croisees.remarques
								FROM $vue AS tbl_items_1
									INNER JOIN ($vue AS tbl_items
										INNER JOIN ((tbl_reactions_croisees 
											INNER JOIN tbl_groupes_patients ON tbl_reactions_croisees.id_groupes_patient = tbl_groupes_patients.id_groupes_patient) 
											INNER JOIN tbl_bibliographies ON tbl_groupes_patients.id_bibliographie = tbl_bibliographies.id_bibliographie) 
										ON tbl_items.id_item = tbl_reactions_croisees.id_produit1) 
									ON tbl_items_1.id_item = tbl_reactions_croisees.id_produit2
							WHERE tbl_reactions_croisees.id_reactions_croisee=".intval($row['id_reactions_croisee'])."
							  AND tbl_groupes_patients.statut='publie'
							  AND tbl_bibliographies.statut='publie'
							  AND tbl_reactions_croisees.statut='publie'";
							
			$resbiblio = spip_query($querybiblio);

			$label_pays = _T('ad:label_pays');//Pays
			$label_nb_sujets = _T('ad:label_nb_sujets');//Nb sujets
			$label_test_individuels = _T('ad:label_test_individuels');//S&eacute;rums test&eacute;s individuellement
			$label_test_quantitatif = _T('ad:label_test_quantitatif');//Test quantitatif
			$label_produit1 = _T('ad:label_produit1');//Produit1
			$label_rc1v2 = _T('ad:label_rc1v2');//RC 1-&gt; 2
			$label_rc2v1 = _T('ad:label_rc2v1');//RC 2-&gt; 1
			$label_produit2 = _T('ad:label_produit2');//Produit2
			$label_remarques = _T('ad:label_remarques');//Remarques
			$label_retour_synthese = _T('ad:label_retour_synthese');//Retour &agrave; la synth&egrave;se

			while ($rowbiblio = sql_fetch($resbiblio)){
				$linkbiblio = '<a href="#biblio'.$row['id_reactions_croisee'].'">';
				if (!$premiere_ligne) $biblio .= '<tr><td colspan="5">&nbsp;</td></tr>';
				$premiere_ligne = false;
				$biblio .= '<tr class="row_first"><th colspan="5"><a name="biblio'.$row['id_reactions_croisee'].'" id="biblio'
				           .$row['id_reactions_croisee'].'"></a><span class="left">'.$row['id_reactions_croisee']
				           .' : </span><a class="small right" href="#top_'.$type_etude.'">'.$label_retour_synthese.' <img src="'.find_in_path('img/arrow_up.gif').'" style="margin-bottom:-2px"/></a></th></tr>
								<tr'.((($count % 2) == 0)?' class="row_even"':' class="row_odd"').'><td colspan="5" rowspan="1">'
				."<a onclick=\"jQuery(this).next().toggle('fast');return false;\">".$rowbiblio['citation']."</a>"
				."<div style='display:none;padding:5px;font-size:0.9em;'>".$rowbiblio['abstract']."</div>"
				. '</td></tr>
								<tr'.((($count % 2) == 0)?' class="row_even"':' class="row_odd"')
				           .'><td><b>'.$label_pays.'</b>: '.traduit_pays($rowbiblio['pays'],$GLOBALS['spip_lang'])
				           .'</td><td colspan="4" rowspan="1">'.extraire_multi($rowbiblio['description_groupe'],$GLOBALS['spip_lang']).'</td></tr>
								<tr'.((($count % 2) == 0)?' class="row_even"':' class="row_odd"')
				           .'><td><b>'.$label_nb_sujets.'</b>: '.$rowbiblio['nb_sujets']
				           .'</td><td colspan="2" rowspan="1"><b>'.$label_test_individuels.'</b>: '.(($rowbiblio['pool']==1)?_T('ad:non'):_T('ad:oui'))
				           .'</td><td colspan="2" rowspan="1"><b>'.$label_test_quantitatif.'</b>: '.(($rowbiblio['qualitatif']==1)?_T('ad:non'):_T('ad:oui')).'</td></tr>
								<tr'.((($count % 2) == 0)?' class="row_even"':' class="row_odd"')
				           .'><td><b>'.$label_produit1.'</b></td><td><b>'.$label_rc1v2
				           .'</b></td><td><b>'.$label_rc2v1.'</b></td><td><b>'.$label_produit2.'</b></td><td><b>'.$label_remarques.'</b></td></tr>
								<tr'.((($count % 2) == 0)?' class="row_even"':' class="row_odd"')
				           .'><td>'.$rowbiblio['p1'].'</td><td>'.$rowbiblio['niveau_rc_sens1'].'</td><td>'
				           .$rowbiblio['niveau_rc_sens2'].'</td><td>'.$rowbiblio['p2'].'</td><td>'.extraire_multi($rowbiblio['remarques'],$GLOBALS['spip_lang']).'</td></tr>
								';
			}
			
			$flag_no_color = ($type_etude == 'pp')?'':'_nb';
      if (!in_array($row['type2'],array(7,8,9,10,13))) $link2 = '<a href="#" onclick="main_panel.updateTab(null,\''.addslashes($row['p2']).'\',\'spip.php?page=popup_item&amp;lang='.$GLOBALS['spip_lang'].'&amp;id_item='.$row['idp2'].'\'); return false">'.$row['p2'].($row['p2_fonction']?' ('.$row['p2_fonction'].')':'').'</a>';
      else $link2 = $row['p2'];
      if (!in_array($row['type1'],array(7,8,9,10,13))) $link1 = '<a href="#" onclick="main_panel.updateTab(null,\''.addslashes($row['p1']).'\',\'spip.php?page=popup_item&amp;lang='.$GLOBALS['spip_lang'].'&amp;id_item='.$row['idp1'].'\'); return false">'.$row['p1'].($row['p1_fonction']?' ('.$row['p1_fonction'].')':'').'</a>';
			else $link1 = $row['p1'];
      if ($p1 == $row['id_s1']) {
				$fl1 = (($row['fleche_sens1'] === '0') ? '<img src="'.$css_path.'/img/rc_jamais_lr'.$flag_no_color.'2.gif" alt="" title="" />': (($row['fleche_sens1'] === '1') ? '<img src="'.$css_path.'/img/rc_toujours_lr'.$flag_no_color.'2.gif" alt="" title="" />': '<span></span>'));
				$fl2 = (($row['fleche_sens2'] === '0') ? '<img src="'.$css_path.'/img/rc_jamais_rl'.$flag_no_color.'2.gif" alt="" title="" />': (($row['fleche_sens2'] === '1') ? '<img src="'.$css_path.'/img/rc_toujours_rl'.$flag_no_color.'2.gif" alt="" title="" />': '<span></span>'));
        $result .= '<tr'.((($count % 2) == 0)?' class="row_even"':' class="row_odd"').'><th>'.$linkbiblio. $row['id_reactions_croisee'].'</a></th><td>'.$link2.'</td><td style="text-align:center; width:70px">'.$fl1.'</a></td><td style="text-align:center; width:70px">'.$fl2.'</a></td><td>'.$link1.'</td></tr>';
			} else {
				$fl1 = (($row['fleche_sens1'] === '0') ? '<img src="'.$css_path.'/img/rc_jamais_rl'.$flag_no_color.'2.gif" alt="" title="" />': (($row['fleche_sens1'] === '1') ? '<img src="'.$css_path.'/img/rc_toujours_rl'.$flag_no_color.'2.gif" alt="" title="" />': '<span></span>'));
				$fl2 = (($row['fleche_sens2'] === '0') ? '<img src="'.$css_path.'/img/rc_jamais_lr'.$flag_no_color.'2.gif" alt="" title="" />': (($row['fleche_sens2'] === '1') ? '<img src="'.$css_path.'/img/rc_toujours_lr'.$flag_no_color.'2.gif" alt="" title="" />': '<span></span>'));
				$result .= '<tr'.((($count % 2) == 0)?' class="row_even"':' class="row_odd"').'><th>'.$linkbiblio.$row['id_reactions_croisee'].'</a></th><td>'.$link1.'</td><td style="text-align:center; width:70px">'.$fl2.'</a></td><td style="text-align:center; width:70px">'.$fl1.'</a></td><td>'.$link2.'</td></tr>';
			}


		}
		
	}
	$title .= " ($count)";
	if ($result) {
		$result = '<div class="blocContenuArticle"><a name="top_'.$type_etude.'" id="top_'.$type_etude.'"></a><h1 class="titArticle">'._T('ad:titre_synthese_popup_rc').'</h1>'
		  .get_minitexte($p1,$p2)
		  .'<table class="spip" width="95%"><thead><tr class="row_first"><th style="width:50px;">Biblio</th><th style="width:285px" colspan="2">'.produit($p1).'</th><th style="width:325px" colspan="2">'.produit($p2).'</th></tr></thead><tbody>'.$result.'</tbody></table></div>';
		if ($biblio) $result .= '<div class="blocContenuArticle"><h2 class="titArticle">'._T('ad:titre_bibliographies_popup_rc')
		                        .'</h2><table summary="'._T('ad:titre_detail_biblio').'" class="bibliographie spip"><tbody>'
		                        .$biblio.'</tbody></table><br class="nettoyeur"/></div>';
  } else
		$result = "<h1 class='titArticle'>"._T('ad:aucune_etude_de_ce_type')."</h1>"
			.get_minitexte($p1,$p2);
		
	return "<div id='main'><h1 class='title' style='display:none;'>$title</h1>".$result."</div>";
}

function get_minitexte($p1,$p2){
	if (!$row = minitext_find_rc($p1,$p2))
		return "";

	$texte = '<div class="blocContenuArticle minitexte">'.interdire_scripts(propre($row['texte'])).'</div>';
	return $texte;
}

function produit($produits) {
	if (!is_numeric($produits)) return;
	include_spip('base/abstract_sql');
	
	$result = '';
	return sql_getfetsel('nom',allerdata_vue('tbl_items',$GLOBALS['spip_lang']),'id_item='.intval($produits)." AND statut='publie'");
}
