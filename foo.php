<?php
require_once('RecursiveRoute.php');


var_dump (explode('/',''));



$Root = new RecursiveRoute;

//$Route1 = new Route( 'mod/workflow/:module/:category/:function' );
$Route1 = new RecursiveRoute( 'mod/workflow/:module/:category/:function' );
$Root->addRoute( $Route1 );

///mod/../aap/noot/
///mod/../aap/1/

$SubRoute1 = new RecursiveRoute( ':object_type/:object_id' );
$SubRoute2 = new RecursiveRoute( ':object_type/:relation_id/:object_id' );

$Route1->addRoute( $SubRoute1 );
$Route1->addRoute( $SubRoute2 );

//var_dump( $Route1->is_match('/mod/workflow/') ); //false
//var_dump( $Route1->is_match('/mod/workflow/aap/noot/mies') ); //true
//var_dump( $Route1->is_match('mod/workflow/aap/noot/mies') ); //true
//var_dump( $Route1->is_match('mod/workflow/aap/noot/mies/') ); //true
//var_dump( $Route1->is_match('mod/workflow/aap/noot/mies/hopsa') ); //true
//var_dump( $Route1->is_match('mod/icas/aap/noot/mies/hopsa') ); //true

/*

var_dump( $Root->parse_url('/mod/workflow/') ); //false
var_dump( $Root->parse_url('/mod/workflow/aap/noot/mies') ); //true
var_dump( $Root->parse_url('mod/workflow/aap/noot/mies') ); //true
var_dump( $Root->parse_url('mod/workflow/aap/noot/mies/') ); //true
var_dump( $Root->parse_url('mod/workflow/aap/noot/mies/hopsa') ); //true
var_dump( $Root->parse_url('mod/workflow/aap/noot/mies/holadijee/hopsa') ); //true
var_dump( $Root->parse_url('mod/workflow/aap/noot/mies/holadijee/hopsa/klabam') ); //true
var_dump( $Root->parse_url('mod/icas/aap/noot/mies/hopsa') ); //true

*/

var_dump( $Root->parseUrl('mod/workflow/aap/noot/mies/holadijee/hopsa/klabam') ); //true
var_dump( $Root->parseUrl('mod/workflow/aap/noot/mies/holadijee/hopsa/klabam/foo/bar/') ); //true

var_dump( $Root->createUrl( array(
	'module' => 'hrm',
	'category' => 'hour',
	'function' => 'view_registration_template',
    'object_type' => 'myObjType',
    'object_id' => 'myObjId'
), 'workflow' ));

var_dump( $Root->createUrl( array(
	'module' => 'hrm',
	'category' => 'hour',
	'function' => 'view_registration_template',
    'object_type' => 'myObjType',
    'relation_id' => 'myRelId',
    'object_id' => 'myObjId',
    'foo' => 'bar'
), 'workflow' ));


//var_dump ($Route1);


//module/categorie/functie

// 'mod/workflow/hrm/hour/view_registration_template'
?>