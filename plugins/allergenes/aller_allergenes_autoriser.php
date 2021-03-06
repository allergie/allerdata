<?php
/*
 * Plugin allerdata / Admin du site
 * Licence GPL
 * (c) 2008 C.Morin Yterium pour Allerdata SARL
 *
 */

/**
 * Verifier qu'on a le droit de supprimmer un allergene,
 * ce qui se ramene au droit de supprimer un item
 *
 * @param unknown_type $faire
 * @param unknown_type $quoi
 * @param unknown_type $id
 * @param unknown_type $qui
 * @param unknown_type $options
 * @return unknown
 */
function autoriser_allergene_supprimer_dist($faire,$quoi,$id,$qui,$options){
	return autoriser('supprimer','item',$id,$qui,$options);
}

/**
 * Verifier qu'on a le droit de modifier un allergene,
 * ce qui se ramene au droit de modifier un item
 *
 * @param unknown_type $faire
 * @param unknown_type $quoi
 * @param unknown_type $id
 * @param unknown_type $qui
 * @param unknown_type $options
 * @return unknown
 */
function autoriser_allergene_modifier_dist($faire,$quoi,$id,$qui,$options){
	return autoriser('modifier','item',$id,$qui,$options);
}

/**
 * Verifier qu'on a le droit de creer un allergene,
 * ce qui se ramene au droit de creer un item
 *
 * @param unknown_type $faire
 * @param unknown_type $quoi
 * @param unknown_type $id
 * @param unknown_type $qui
 * @param unknown_type $options
 * @return unknown
 */
function autoriser_allergene_creer_dist($faire,$quoi,$id,$qui,$options){
	return autoriser('creer','item',$id,$qui,$options);
}

?>