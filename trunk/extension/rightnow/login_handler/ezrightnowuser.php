<?php
/**
 * File containing the eZRightNowUser class.
 *
 * @package rightnow
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
//
// Definition of eZRightNowUser class
//
// Created on: <20-Sep-2006 14:06:48 bd>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

/*! \file ezrightnowuser.php
*/

/*!
  \class eZRightNowUser ezrightnowuser.php
  \ingroup eZDatatype
  \brief Handles logins for users from a RightNow system

  The handler will read the users from the RightNow CRM.

  Once a login is requested by a user the handler will do one of two things:
  - Login the user with the existing user object found in the system
  - Creates a new user with the information found in RightNow CRM and login with that user.

*/

include_once( "kernel/classes/datatypes/ezuser/ezusersetting.php" );
include_once( "kernel/classes/datatypes/ezuser/ezuser.php" );
include_once( 'lib/ezutils/classes/ezini.php' );
include_once( eZExtension::baseDirectory() . '/' . nameFromPath(__FILE__) . '/classes/rightnow.php' );

class eZRightNowUser extends eZUser
{
    /*!
     Constructor
    */
    function eZRightNowUser()
    {
    }

    /*!
    \static
     Logs in the user if applied username and password is
     valid. The userID is returned if succesful, false if not.
    */
    function &loginUser( $login, $password, $authenticationMatch = false )
    {
        $db =& eZDB::instance();

        if ( $authenticationMatch === false )
            $authenticationMatch = eZUser::authenticationMatch();

        $loginEscaped = $db->escapeString( $login );
        $passwordEscaped = $db->escapeString( $password );

        $loginArray = array();
        if ( $authenticationMatch & EZ_USER_AUTHENTICATE_LOGIN )
            $loginArray[] = "login='$loginEscaped'";
        if ( $authenticationMatch & EZ_USER_AUTHENTICATE_EMAIL )
            $loginArray[] = "email='$loginEscaped'";
        if ( count( $loginArray ) == 0 )
            $loginArray[] = "login='$loginEscaped'";
        $loginText = implode( ' OR ', $loginArray );

        $contentObjectStatus = EZ_CONTENT_OBJECT_STATUS_PUBLISHED;

        $ini =& eZINI::instance();
        $textFileIni =& eZINI::instance( 'textfile.ini' );
        $databaseImplementation = $ini->variable( 'DatabaseSettings', 'DatabaseImplementation' );
        // if mysql
        if ( $databaseImplementation == "ezmysql" )
        {
            $query = "SELECT contentobject_id, password_hash, password_hash_type, email, login
                      FROM ezuser, ezcontentobject
                      WHERE ( $loginText ) AND
                        ezcontentobject.status='$contentObjectStatus' AND
                        ( ezcontentobject.id=contentobject_id OR ( password_hash_type=4 AND ( $loginText ) AND password_hash=PASSWORD('$passwordEscaped') ) )";
        }
        else
        {
            $query = "SELECT contentobject_id, password_hash, password_hash_type, email, login
                      FROM ezuser, ezcontentobject
                      WHERE ( $loginText ) AND
                            ezcontentobject.status='$contentObjectStatus' AND
                            ezcontentobject.id=contentobject_id";
        }

        $users = $db->arrayQuery( $query );
        $exists = false;
        if ( count( $users ) >= 1 )
        {
            foreach ( array_keys( $users ) as $key )
            {
                $userRow =& $users[$key];
                $userID = $userRow['contentobject_id'];
                $hashType = $userRow['password_hash_type'];
                $hash = $userRow['password_hash'];
                $exists = eZUser::authenticateHash( $userRow['login'], $password, eZUser::site(),
                                                    $hashType,
                                                    $hash );

                // If hash type is MySql
                if ( $hashType == EZ_USER_PASSWORD_HASH_MYSQL and $databaseImplementation == "ezmysql" )
                {
                    $queryMysqlUser = "SELECT contentobject_id, password_hash, password_hash_type, email, login
                                       FROM ezuser, ezcontentobject
                                       WHERE ezcontentobject.status='$contentObjectStatus' AND
                                             password_hash_type=4 AND ( $loginText ) AND password_hash=PASSWORD('$passwordEscaped') ";
                    $mysqlUsers = $db->arrayQuery( $queryMysqlUser );
                    if ( count( $mysqlUsers ) >= 1 )
                        $exists = true;
                }

                eZDebugSetting::writeDebug( 'kernel-user', eZUser::createHash( $userRow['login'], $password, eZUser::site(),
                                                                               $hashType ), "check hash" );
                eZDebugSetting::writeDebug( 'kernel-user', $hash, "stored hash" );
                if ( $exists )
                {
                    $userSetting = eZUserSetting::fetch( $userID );
                    $isEnabled = $userSetting->attribute( "is_enabled" );
                    if ( $hashType != eZUser::hashType() and
                         strtolower( $ini->variable( 'UserSettings', 'UpdateHash' ) ) == 'true' )
                    {
                        $hashType = eZUser::hashType();
                        $hash = eZUser::createHash( $login, $password, eZUser::site(),
                                                    $hashType );
                        $db->query( "UPDATE ezuser SET password_hash='$hash', password_hash_type='$hashType' WHERE contentobject_id='$userID'" );
                    }
                    break;
                }
            }
        }
        
        if ( $exists and $isEnabled )
        {
            eZDebugSetting::writeDebug( 'kernel-user', $userRow, 'user row' );
            $user = new eZUser( $userRow );
            eZDebugSetting::writeDebug( 'kernel-user', $user, 'user' );
            $userID = $user->attribute( 'contentobject_id' );

            eZUser::updateLastVisit( $userID );
            eZUser::setCurrentlyLoggedInUser( $user, $userID );
            
            eZRightNowUser::setStaticCacheCookie( $user );
            
            return $user;
        }
        else
        {
            $defaultUserPlacement = $ini->variable( "UserSettings", "DefaultUserPlacement" );

                $userArray = RightNow::getCustomer( RightNow::getCustomerByLogin( $login ) );
                
                # Interface down or such user doesn't exist
                if ( empty( $userArray ) )
                {
                    $user = false;
                    return $user;
                }
                $uid = $userArray['login'];
                $email = $userArray['email'];
                $pass = $userArray['password'];
                $firstName = $userArray['first_name'];
                $lastName = $userArray['last_name'];
                
                $street=$userArray["street"];
                $phone=$userArray["ph_office"];
                $optin=$userArray["ma_opt_in"];
                $organisation=$userArray["ma_org_name"];
                $title=$userArray["title"];
                $postal_code=$userArray["postal_code"];
                
                
                if ( $login == $uid )
                {
                    if ( trim( $pass ) == $password )
                    {
                        $createNewUser = true;
                        $existUser = $this->fetchByName( $login );
                        if ( $existUser != null )
                        {
                            $createNewUser = false;
                        }
                        if ( $createNewUser )
                        {
                            $userClassID = $ini->variable( "UserSettings", "UserClassID" );
                            $userCreatorID = $ini->variable( "UserSettings", "UserCreatorID" );
                            $defaultSectionID = $ini->variable( "UserSettings", "DefaultSectionID" );

                            $class = eZContentClass::fetch( $userClassID );
                            $contentObject = $class->instantiate( $userCreatorID, $defaultSectionID );

                            $remoteID = "RightNow:customers:" . $userArray['c_id'];
                            $contentObject->setAttribute( 'remote_id', $remoteID );
                            $contentObject->store();

                            $contentObjectID = $contentObject->attribute( 'id' );
                            $userID = $contentObjectID;
                            $nodeAssignment = eZNodeAssignment::create( array( 'contentobject_id' => $contentObjectID,
                                                                               'contentobject_version' => 1,
                                                                               'parent_node' => $defaultUserPlacement,
                                                                               'is_main' => 1 ) );
                            $nodeAssignment->store();
                            $version =& $contentObject->version( 1 );
                            $version->setAttribute( 'modified', time() );
                            $version->setAttribute( 'status', EZ_VERSION_STATUS_DRAFT );
                            $version->store();

                            $contentObjectID = $contentObject->attribute( 'id' );
                            $contentObjectAttributes =& $version->contentObjectAttributes();
                            
                            $contentObjectAttributes[0]->setAttribute( 'data_text', $firstName );
                            $contentObjectAttributes[0]->store();

                            $contentObjectAttributes[1]->setAttribute( 'data_text', $lastName );
                            $contentObjectAttributes[1]->store();
                            
                            $contentObjectAttributes[3]->setAttribute( 'data_text', $phone );
                            $contentObjectAttributes[3]->store();
                            
                            $contentObjectAttributes[4]->setAttribute( 'data_int', $optin );
                            $contentObjectAttributes[4]->store();
                            
                            $contentObjectAttributes[5]->setAttribute( 'data_text', $street );
                            $contentObjectAttributes[5]->store();
                            
                            $contentObjectAttributes[6]->setAttribute( 'data_text', $organisation );
                            $contentObjectAttributes[6]->store();
                            
                            $contentObjectAttributes[7]->setAttribute( 'data_text', $title );
                            $contentObjectAttributes[7]->store();
                            
                            $contentObjectAttributes[8]->setAttribute( 'data_text', $postal_code );
                            $contentObjectAttributes[8]->store();
                            
                            $user = $this->create( $userID );
                            $user->setAttribute( 'login', $login );
                            $user->setAttribute( 'email', $email );
                            $user->setAttribute( 'password_hash', $userArray['password'] );
                            $user->setAttribute( 'password_hash_type', EZ_USER_PASSWORD_HASH_PLAINTEXT );
                            $user->store();

                            eZUser::updateLastVisit( $userID );
                            eZUser::setCurrentlyLoggedInUser( $user, $userID );
                            $GLOBALS['RIGHTNOW_NO_UPDATE'] = true;
                            include_once( 'lib/ezutils/classes/ezoperationhandler.php' );
                            $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $contentObjectID,
                                                                                               'version' => 1 ) );
                            $GLOBALS['RIGHTNOW_NO_UPDATE'] = false;
                            include_once("kernel/classes/ezcontentcachemanager.php");
                            eZContentCacheManager::clearContentCache($userID);
                            eZRightNowUser::setStaticCacheCookie( $user );
                            
                            return $user;
                        }
                        else
                        {
                            // Update user information
                            $userID = $existUser->attribute( 'contentobject_id' );
                            $contentObject =& eZContentObject::fetch( $userID );

                            $parentNodeID = $contentObject->attribute( 'main_parent_node_id' );
                            $currentVersion = $contentObject->attribute( 'current_version' );

                            $version =& $contentObject->attribute( 'current' );
                            $contentObjectAttributes =& $version->contentObjectAttributes();

                            $contentObjectAttributes[0]->setAttribute( 'data_text', $firstName );
                            $contentObjectAttributes[0]->store();

                            $contentObjectAttributes[1]->setAttribute( 'data_text', $lastName );
                            $contentObjectAttributes[1]->store();

                            $contentObjectAttributes[3]->setAttribute( 'data_text', $phone );
                            $contentObjectAttributes[3]->store();
                            
                            $contentObjectAttributes[4]->setAttribute( 'data_int', $optin );
                            $contentObjectAttributes[4]->store();
                            
                            $contentObjectAttributes[5]->setAttribute( 'data_text', $street );
                            $contentObjectAttributes[5]->store();
                            
                            $contentObjectAttributes[6]->setAttribute( 'data_text', $organisation );
                            $contentObjectAttributes[6]->store();
                            
                            $contentObjectAttributes[7]->setAttribute( 'data_text', $title );
                            $contentObjectAttributes[7]->store();
                            
                            $contentObjectAttributes[8]->setAttribute( 'data_text', $postal_code );
                            $contentObjectAttributes[8]->store();
                            
                            $existUser = eZUser::fetch(  $userID );
                            $existUser->setAttribute('email', $email );
                            $existUser->setAttribute('password_hash', $userArray['password'] );
                            $existUser->setAttribute('password_hash_type', EZ_USER_PASSWORD_HASH_PLAINTEXT );
                            $existUser->store();

                            if ( $defaultUserPlacement != $parentNodeID )
                            {
                                $newVersion = $contentObject->createNewVersion();
                                $newVersion->assignToNode( $defaultUserPlacement, 1 );
                                $newVersion->removeAssignment( $parentNodeID );
                                $newVersionNr = $newVersion->attribute( 'version' );
                                include_once( 'lib/ezutils/classes/ezoperationhandler.php' );
                                $GLOBALS['RIGHTNOW_NO_UPDATE'] = true;
                                $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $userID,
                                                                                                             'version' => $newVersionNr ) );
                                $GLOBALS['RIGHTNOW_NO_UPDATE'] = false;                                            
                            }

                            eZUser::updateLastVisit( $userID );
                            eZUser::setCurrentlyLoggedInUser( $existUser, $userID );
                            eZRightNowUser::setStaticCacheCookie( $user );
                            return $existUser;
                        }
                    }
                    else
                    {
                        $user = false;
                        return $user;
                    }
                }
        }
        $user = false;
        return $user;
    }
    function setStaticCacheCookie( $user )
    {
        $ini = eZINI::instance();
        $cookielifetime = (int)$ini->variable('Session','SessionTimeout');
        $object = $user->attribute( 'contentobject' );
    	eZRightNowUser::createCookie("USER_NAME", $object->attribute( 'name' ), $cookielifetime, '/' . eZSys::wwwDir() );
    	eZRightNowUser::createCookie("USER_ID", $user->attribute( 'contentobject_id' ), $cookielifetime, '/' . eZSys::wwwDir() );
    	eZDebug::writeDebug($_COOKIE,'Set static cache cookie');
    }
    function sessionCleanup()
    {
    	eZRightNowUser::createCookie("USER_NAME", '', 0, '/' . eZSys::wwwDir() );
    	eZRightNowUser::createCookie("USER_ID", '', 0, '/' . eZSys::wwwDir() );
    }
    
    /**
     * A better alternative (RFC 2109 compatible) to the php setcookie() function
     *
     * @param string Name of the cookie
     * @param string Value of the cookie
     * @param int Lifetime of the cookie
     * @param string Path where the cookie can be used
     * @param string Domain which can read the cookie
     * @param bool Secure mode?
     * @param bool Only allow HTTP usage?
     * @return bool True or false whether the method has successfully run
     */
    function createCookie($name, $value='', $maxage=0, $path='', $domain='', $secure=false, $HTTPOnly=false)
    {
        $ob = ini_get('output_buffering');

        // Abort the method if headers have already been sent, except when output buffering has been enabled
        if ( headers_sent() && (bool) $ob === false || strtolower($ob) == 'off' )
            return false;

        if ( !empty($domain) )
        {
            // Fix the domain to accept domains with and without 'www.'.
            if ( strtolower( substr($domain, 0, 4) ) == 'www.' ) $domain = substr($domain, 4);
            // Add the dot prefix to ensure compatibility with subdomains
            if ( substr($domain, 0, 1) != '.' ) $domain = '.'.$domain;

            // Remove port information.
            $port = strpos($domain, ':');

            if ( $port !== false ) $domain = substr($domain, 0, $port);
        }

        // Prevent "headers already sent" error with utf8 support (BOM)
        //if ( utf8_support ) header('Content-Type: text/html; charset=utf-8');

        header('Set-Cookie: '.rawurlencode($name).'='.rawurlencode($value)
                                    .(empty($domain) ? '' : '; Domain='.$domain)
                                    .(empty($maxage) ? '' : '; Max-Age='.$maxage)
                                    .(empty($path) ? '' : '; Path='.$path)
                                    .(!$secure ? '' : '; Secure')
                                    .(!$HTTPOnly ? '' : '; HttpOnly'), false);
        return true;
    } 
}

?>
