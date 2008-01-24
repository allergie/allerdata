<?php


// Reprendre la requete dans une iframe avec les parametres (comme les popups)
// Ou charger le bloc en Ajax (permet de garder le donn�es)
// Voir l'iframe ne permet de pas de �rer l'historique.. (normaeme,t c'est avec le hash)

// Donc meme requete, avec le CCD en plus
// .. On calcule un chiffre en SPIP misez en forme (en image cliquable)
// Ajouter une zone cliquable (bulette) pour afficher une autre page/popup

// le clic appelle une fonction dans le parent, qui va effectuer la mise en avant des �l�ments concern�s (idem pour la popup)


function ccd($txt) {
	$tableau_produits = array();
	
	if (is_numeric($_REQUEST['p1'])) $tableau_produits[] = $_REQUEST['p1']; 
	if (is_numeric($_REQUEST['p2'])) $tableau_produits[] = $_REQUEST['p2'];
	if (is_numeric($_REQUEST['p3'])) $tableau_produits[] = $_REQUEST['p3'];
	if (is_numeric($_REQUEST['p4'])) $tableau_produits[] = $_REQUEST['p4'];
	if (is_numeric($_REQUEST['p5'])) $tableau_produits[] = $_REQUEST['p5'];
	
	if (!sizeof($tableau_produits)) return '';
	
	$produits = implode(",", $tableau_produits);
	
	$tt = '';
	
  /* ALGO : on recherche les familles mol�culaires (F) dont font partie certains �l�ments (A) ayant le champ "CCD possible" �gal � "1"
	 * qui eux-m�me sont contenus dans certains produits (P) du penta. 
	 * Les produits (P) du penta sont mis en avant. la valeur affich�e est le nombre de (P).
	 */
  $query = "SELECT DISTINCT tbl_items_2.id_item, tbl_items_2.nom AS produit
		FROM (((tbl_items INNER JOIN tbl_est_dans ON tbl_items.id_item = tbl_est_dans.id_item) 
			INNER JOIN tbl_est_dans AS tbl_est_dans_1 ON tbl_items.id_item = tbl_est_dans_1.id_item) 
			INNER JOIN tbl_items AS tbl_items_1 ON tbl_est_dans_1.est_dans_id_item = tbl_items_1.id_item) 
			INNER JOIN tbl_items AS tbl_items_2 ON tbl_est_dans.est_dans_id_item = tbl_items_2.id_item
		WHERE (((tbl_items_2.id_item) IN ($produits)) AND ((tbl_items_1.id_type_item)=5) AND ((tbl_items.ccd_possible)=1))
		";
			
	$liste_prod_ccd = array();
	$nb_ccd = 0;
	
	$res = spip_query($query);
		
	while ($row = spip_fetch_array($res)){
		$liste_prod_ccd[$row['id_item']] = $row['id_item'];
	}
	$nb_ccd = sizeof($liste_prod_ccd);
	
	$js_liste_prod_ccd = '['.implode(',',$liste_prod_ccd).']';
	return "<a href='#' class='outlineLink' onclick='CCD.outline_prod($js_liste_prod_ccd);return false;'>".$nb_ccd."</a>";
}
?>