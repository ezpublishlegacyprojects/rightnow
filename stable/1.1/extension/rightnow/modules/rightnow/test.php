<?php
/**
 * File test.php
 *
 * @package rightnow
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
$Module =& $Params['Module'];
$http =& eZHTTPTool::instance();

if ( $http->hasPostVariable('Skip') )
{
    $Module->redirectToView( 'menu' );
}

include_once( 'kernel/common/template.php' );
$tpl =& templateInit();

include_once( eZExtension::baseDirectory() . '/' . nameFromPath(__FILE__) . '/classes/rightnow.php' );


$contact['name'] = array( 'first' => (string)"James", 'last' => (string)"Armstrong" );
$contact['email'] = array( 'addr' => (string)"masterjames@example.com", 'cert' => '' );
$contact['state'] = array( 'css' => (int)1, 'ma' => (int)0, 'sa' => (int)0 );
$contact['login'] = (string)"masterjames";
$contact['password'] = (string)"openpass";
/*
$contact['first_name']=(string)'Joe';
$contact['last_name']=(string)'Smith';
$contact['sa_state']=(int)0;
$contact['ma_state']=(int)0;
$contact['css_state']=(int)1;
*/


$organisation['name'] = (string)"W1W 7Pa 23-31 Great Titchfield Street";
$organisation['state'] = array( 'css' => (int)1, 'ma' => (int)0, 'sa' => (int)0 );

// Outcode
RightNow::addCustomField( $organisation['custom_field'], 108, "W1W", RIGHTNOW_CUSTOMFIELD_DATATYPE_TEXTFIELD );
// Incode
RightNow::addCustomField( $organisation['custom_field'], 109, "7Pa", RIGHTNOW_CUSTOMFIELD_DATATYPE_TEXTFIELD );
// AddressLine1
RightNow::addCustomField( $organisation['custom_field'], 110, "Moray House", RIGHTNOW_CUSTOMFIELD_DATATYPE_TEXTFIELD );
// Number and street
RightNow::addCustomField( $organisation['custom_field'], 111, "23-31 Great Titchfield Street", RIGHTNOW_CUSTOMFIELD_DATATYPE_TEXTFIELD );
// Locality
RightNow::addCustomField( $organisation['custom_field'], 113, "", RIGHTNOW_CUSTOMFIELD_DATATYPE_TEXTFIELD );
// Town
RightNow::addCustomField( $organisation['custom_field'], 113, "London", RIGHTNOW_CUSTOMFIELD_DATATYPE_TEXTFIELD );
// Country
RightNow::addCustomField( $organisation['custom_field'], 121, "England", RIGHTNOW_CUSTOMFIELD_DATATYPE_TEXTFIELD );


#$createOrg = RightNow::createOrganization( $organisation );

#$contact['org_id'] = (int)$createOrg;

#$createOrg = RightNow::createCustomer( $contact );

$testme2 = RightNow::getOrganisationByCustomer( RightNow::getCustomerByEmail( 'soerenmeyer@example.com' ) );


#RightNow::getCustomer( RightNow::getCustomerByLogin( 'xrow' ) );

$Result = array();
$Result['left_menu'] = "design:parts/ezadmin/menu.tpl";
$Result['content'] = $tpl->fetch( "design:rightnow/test.tpl" );
$Result['path'] = array( array( 'url' => false,
                        'text' => 'API Test' ) );

?>
