<?php

/**
 *	@author:	a4p ASD / Andreas Dorner
 *	@company:	apps4print / page one GmbH, Nürnberg, Germany
 *
 *
 *	@version:	1.0.0
 *	@date:		11.02.2015
 *
 *
 *	oxRestBasket.php
 *
 *	oxjson - add basket functionality
 *
 */

// ------------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------

use Tonic\Response;

/**
 * @uri /oxbasket/
 * @uri /oxbasket/:oxid
 * @uri /oxbasket/:oxid/:amount
 */
class oxRestBasket extends OxRestBase {
	
	// ------------------------------------------------------------------------------------------------
	// ------------------------------------------------------------------------------------------------
	
	/**
	 * @method GET
	 * @json
	 * @return Tonic\Response
	 */
	public function getBasket() {
		
		
		$this->_doLog( __CLASS__ . "::getBasket" );
				
		
		$class									= "oxbasket";
		
		
		try {
				
			/** @var oxBase $o */
			$o									= oxNew( $class );
			
			
			$o->load();
			$a_basketArticles					= $o->getBasketArticles();

						
			// ------------------------------------------------------------------------------------------------
			// convert basketcontents
			$a_ret								= array();
			foreach( $a_basketArticles as $s_key => $o_val ) {
				$a_ret[ $s_key ]		= $this->_oxObject2Array( $o_val );
			}

			return new Response( 200, $a_ret );

			
		} catch ( Exception $ex ) {
			
			$this->_doLog( "Error getting basket: " . $ex->getMessage() );
		}
		
		return new Response(404, "Not found");
		
	}
	
	// ------------------------------------------------------------------------------------------------
	
	/**
	 * @method POST
	 * @json
	 * @param string $s_oxarticles__oxid
	 * @param number $i_amount
	 * @return Tonic\Response
	 */
	public function addToBasket( $s_oxarticles__oxid, $i_amount = 1 ) {
		
		
		$this->_doLog( __CLASS__ . "::addToBasket" );
		

		$class									= "oxbasket";
		
		
		try {
		
			/** @var oxBase $o */
			$o									= oxNew( $class );
		

			$o->addTobasket( $s_oxarticles__oxid, $i_amount );
			
	
			return new Response( 200 );
		
			
		} catch ( Exception $ex ) {
			 
			$this->_doLog( "Error adding to basket: " . $ex->getMessage() );
		}
		
		return new Response(404, "Not found");
	
	}

	// ------------------------------------------------------------------------------------------------
	
	/**
	 * @method DELETE
	 * @json
	 * @param string $s_basketitem__oxid
	 * @return Tonic\Response
	 */
	public function deleteFromBasket( $s_basketitem__oxid ) {
	
		
		$this->_doLog( __CLASS__ . "::deleteFromBasket" );
		
		
		$class									= "oxbasket";
		
		
		try {
		
			/** @var oxBase $o */
			$o									= oxNew( $class );
				
			
			// ------------------------------------------------------------------------------------------------
			// load basket
			$o->load();

	
			$o->removeItem( $s_basketitem__oxid );
	
			
			// ------------------------------------------------------------------------------------------------
			// recalculate basket (force update)
			$o->calculateBasket( true );
			
			
			// ------------------------------------------------------------------------------------------------
			// update session
			oxRegistry::getSession()->delBasket();
			
			oxRegistry::getSession()->setBasket( $o );
			
			oxRegistry::getSession()->freeze();
			// ------------------------------------------------------------------------------------------------
			
			
			return new Response( 200 );
				
			
		} catch (Exception $ex) {
			
			$this->_doLog( "Error deleting from basket: " . $ex->getMessage() );
		}
		
		return new Response(404, "Not found");
	
	}
	
	// ------------------------------------------------------------------------------------------------
	
}

// ------------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------
