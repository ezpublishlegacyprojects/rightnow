<?php /* #?ini charset="utf-8"?
[RightNowSettings]
APIInterface=http://<your_domain>/cgi-bin/<your_interface>.cfg/php/xml_api/parse.php
# Choose from http or email
APIInterfaceType=http
# RightNow Incoming Intragration variable II_SEC_WEB_STR
# Specifies the post variable from a Web page, to be compared for validation of the XML source for Web integrations. This is used to provide an interface to the RightNow XML API for third-party call management systems or other third-party systems. Default is blank.
# Location Management & Configuration -> System Configuration -> Settings -> RNT Common
SecretString=password
CustomizationClassPath=extension/rightnow/classes/rightnowcustomization.php
CustomizationClass=RightNowCustomization

[FAQImportSettings]
# folder which is use to import rightnow answers
FAQContainerNodeId=476
# ClassIdentifier of Class which is use for import
FAQClassIdentifier=faq

CategoryContainerNodeId=1107
CategoryClassIdentifier=faq_category

TopicContainerNodeId=1108
TopicClassIdentifier=faq_topic


# Settin how to create new answers in rightNow
# used in workflow event rightnowanswer
[RightNowAnswerCreateSettings]
access_mask=1
assgn_acct_id=59
assgn_group_id=13
#status_id  public(4), private(5), proposed(6), review (7)
status_id=7
lang_id=5

[FAQSettings]
HelpNodeId=1456
SearchTipsNodeId=1457
*/ ?>