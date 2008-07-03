<?php

include_once( "lib/ezutils/classes/ezhttptool.php" );
include_once( "kernel/classes/datatypes/ezuser/ezuser.php" );

$http =& eZHTTPTool::instance();

$user =& eZUser::instance();

// Remove all temporary drafts
include_once( 'kernel/classes/ezcontentobject.php' );
eZContentObject::cleanupAllInternalDrafts( $user->attribute( 'contentobject_id' ) );

$user->logoutCurrent();
eZUserLoginHandler::sessionCleanup();

$http->setSessionVariable( 'force_logout', 1 );

$ini =& eZINI::instance();
$redirectURL = $ini->variable( 'UserSettings', 'LogoutRedirect' );

return $Module->redirectTo( $redirectURL );

?>