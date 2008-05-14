<?php
/**
 * File create.php
 *
 * @package rightnow
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
/*
	modul zum erzeugen eines neuen objectes über die eingebaute ez funktionen
	+ aber über get variablen ansprechbar 
	
	
	<a href="answer/create">

	
	
*/


$module =& $Params['Module'];
$http =& eZHTTPTool::instance();

$rightnowini = eZINI::instance( "rightnow.ini" );
// speicher GET Daten in POST daten

$_POST['NewButton']='New Answer';
$_POST['ClassIdentifier']= $rightnowini->variable( 'FAQImportSettings', 'FAQClassIdentifier' );
$_POST['NodeID']= $rightnowini->variable( 'FAQImportSettings', 'FAQContainerNodeId' );


$url = "content/action";
$Result['rerun_uri'] = $url;

return $module->setExitStatus( EZ_MODULE_STATUS_RERUN );


?>