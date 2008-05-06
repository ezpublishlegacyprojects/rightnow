<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<form method="POST" action="{ezini('RightNowSettings', 'APIInterface','rightnow.ini')}" name="XML Form">
<h2>XML Data</h2>
<textarea cols="80" name="xml_doc" rows="20"></textarea>
<br><br>
<h2>Security String</h2>
<input name="sec_string" size="10" value="{ezini('RightNowSettings', 'SecretString', 'rightnow.ini' )}">
<br><br>
<input type="submit" value="Submit" name="B1">
<input type="reset" value="Reset" name="B2">
</form>