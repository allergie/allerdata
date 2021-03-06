<?php

	function lise_des_rc($produits) {
		include_spip('base/abstract_sql');

		// par precaution, pour ne pas se faire injecter n'importe quoi
    $tableau_produits = array_map('intval',explode(',',$produits));
  	$produits = implode(",", $tableau_produits);
  	
	  include_spip('allerdata_fonctions');
	  $vue = allerdata_vue('tbl_items',$GLOBALS['spip_lang']);
  	// Requete de recherche : il faut tenir compte des reactions entre elements de type 3 ou 5
  	$query = "SELECT DISTINCT 
  			tbl_reactions_croisees.id_reactions_croisee, 
  			tbl_items.id_item AS idp1, 
  			tbl_items.nom AS p1, 
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
  			tbl_reactions_croisees.produits_differents, 
  			tbl_est_dans.est_dans_id_item AS id_s1, 
  			tbl_est_dans_1.est_dans_id_item AS id_s2
  		FROM 
        $vue as tbi3,
        $vue as tbi4,
        (
          (
            (
              (tbl_est_dans AS tbl_est_dans_1 
                INNER JOIN 
                  (tbl_reactions_croisees 
                    INNER JOIN tbl_est_dans ON tbl_reactions_croisees.id_produit1 = tbl_est_dans.id_item
                  ) 
                ON tbl_est_dans_1.id_item = tbl_reactions_croisees.id_produit2
              ) 
              INNER JOIN $vue AS tbl_items ON tbl_est_dans_1.id_item = tbl_items.id_item
            ) 
            INNER JOIN tbl_types_items ON tbl_items.id_type_item = tbl_types_items.id_type_item
          ) 
          INNER JOIN $vue AS tbl_items_1 ON tbl_est_dans.id_item = tbl_items_1.id_item
        ) 
        INNER JOIN tbl_types_items AS tbl_types_items_1 ON tbl_items_1.id_type_item = tbl_types_items_1.id_type_item
  		WHERE 
        (
  			  tbl_est_dans.est_dans_id_item In ($produits)
          AND tbl_est_dans_1.est_dans_id_item In ($produits)
  			
  				AND tbl_reactions_croisees.id_produit1 = tbi3.id_item
  				AND tbl_reactions_croisees.id_produit2 = tbi4.id_item
  				AND tbl_items.statut='publie'
  				AND tbl_items_1.statut='publie'
  				AND tbl_reactions_croisees.statut='publie'
  			);";
  	
  	$res = spip_query($query);
  	// $arc contient les arcs orientes
  	// $arc[a][b] correspond a l'arc de a vers b
  	$arc = array();
  	
  	while ($row = sql_fetch($res)){
  		
  		$id_s1 = $row['id_s1'];
  		$id_s2 = $row['id_s2'];
  
  		if ($row['fleche_sens1'] != '') {
  			if (!isset($arc[$id_s1])) $arc[$id_s1] = array();
  			if (!isset($arc[$id_s1][$id_s2])) {
  				if (($row['fleche_sens1'] != 0) || in_array($row['id_type_item1'],array(3,5))) {
  					$arc[$id_s1][$id_s2] = $row['fleche_sens1'];
  				}
  			} else {
  				if (($row['fleche_sens1'] != 0) || in_array($row['id_type_item1'],array(3,5))) {
  					$arc[$id_s1][$id_s2] = ($row['fleche_sens1'] + $arc[$id_s1][$id_s2]) /2;
  				}
  			}
  		}
  
  		if ($row['fleche_sens2'] != '') {
  			if (!isset($arc[$id_s2])) $arc[$id_s2] = array();
  			if (!isset($arc[$id_s2][$id_s1])) {
  				if (($row['fleche_sens2'] != 0) || in_array($row['id_type_item2'],array(3,5))) {
  					$arc[$id_s2][$id_s1] = $row['fleche_sens2'];
  				}
  			} else {
  				if (($row['fleche_sens2'] != 0) || in_array($row['id_type_item2'],array(3,5))) {
  					$arc[$id_s2][$id_s1] = ($row['fleche_sens2'] + $arc[$id_s2][$id_s1]) /2;
  				}
  			}
  		}
  	}
  	
  	$liste_des_rc = array();
  	
  	// Affectation des classes
  	// 1 correspond a 'toujours'
  	// 0 a 'jamais'
  	// entre les 2 c'est 'discordant'
  	foreach ($arc as $origine => $liens) {
  		foreach ($liens as $destination => $valeur) {
  			
  			if ($valeur == 1) $className = 'rc_toujours';
  			elseif ($valeur == 0) $className = 'rc_jamais';
  			else $className = 'rc_discordant';

				$mt = "";
				if ($row = minitext_find_rc($origine,$destination))
					$mt = $row['id_minitexte'];
  			$liste_des_rc[] = array('source' => $origine, 'dest' => $destination, 'classe' => $className, 'mt'=>$mt);
  		}
  	}
  	
    return json_encode($liste_des_rc);
 
  }
?>