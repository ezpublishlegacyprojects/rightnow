<?php
class RightNowCustomization
{

	function RightNowCustomization()
	{
		
	}
	
	function fillContact( &$contact, $contentObjectID )
	{
	    $c_user = eZUser::fetch( $contentObjectID, true );
        $co = eZContentObject::fetch( $contentObjectID );
        $c_user_dm = $co->dataMap();
        
        $login = $c_user->attribute("login");
        $email = $c_user->attribute("email");
        $password = $c_user->attribute("password_hash");
        $firstname = $c_user_dm["first_name"]->DataText;
        $lastname = $c_user_dm["last_name"]->DataText;

        $contact['login'] = (string)$login;
        $contact['first_name'] = (string)$firstname;
        $contact['last_name'] = (string)$lastname;
        $contact['email'] = (string)$email;
        $contact['password'] = (string)trim($password);

	}
	
}
?>