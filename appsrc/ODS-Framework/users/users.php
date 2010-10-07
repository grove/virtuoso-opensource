<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!--
 -
 -  $Id$
 -
 -  This file is part of the OpenLink Software Virtuoso Open-Source (VOS)
 -  project.
 -
 -  Copyright (C) 1998-2008 OpenLink Software
 -
 -  This project is free software; you can redistribute it and/or modify it
 -  under the terms of the GNU General Public License as published by the
 -  Free Software Foundation; only version 2 of the License, dated June 1991.
 -
 -  This program is distributed in the hope that it will be useful, but
 -  WITHOUT ANY WARRANTY; without even the implied warranty of
 -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 -  General Public License for more details.
 -
 -  You should have received a copy of the GNU General Public License along
 -  with this program; if not, write to the Free Software Foundation, Inc.,
 -  51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 -
 -
-->
<html>
  <head>
    <title>Virtuoso Web Applications</title>
    <link rel="stylesheet" type="text/css" href="/ods/users/css/users.css" />
    <link rel="stylesheet" type="text/css" href="/ods/default.css" />
    <link rel="stylesheet" type="text/css" href="/ods/nav_framework.css" />
    <link rel="stylesheet" type="text/css" href="/ods/typeahead.css" />
    <link rel="stylesheet" type="text/css" href="/ods/ods-bar.css" />
    <link rel="stylesheet" type="text/css" href="/ods/rdfm.css" />
    <script type="text/javascript" src="/ods/users/js/users.js"></script>
    <script type="text/javascript" src="/ods/common.js"></script>
    <script type="text/javascript" src="/ods/typeahead.js"></script>
    <script type="text/javascript" src="/ods/tbl.js"></script>
    <script type="text/javascript" src="/ods/validate.js"></script>
    <script type="text/javascript">
      // OAT
      var toolkitPath="/ods/oat";
      var featureList = ["ajax", "json", "tab", "combolist", "calendar", "rdfmini", "grid", "graphsvg", "tagcloud", "map", "timeline", "anchor"];
    </script>
    <script type="text/javascript" src="/ods/oat/loader.js"></script>
  </head>
  <?php
    function parseUrl($url) {
      // parse the given URL
      $url = parse_url($url);
      if (!isset($url['port'])) {
        if ($url['scheme'] == 'http') {
          $url['port'] = 80;
        }
        elseif ($url['scheme'] == 'https') {
          $url['port']=443;
        }
      }
      if ($url['scheme'] == 'https')
        $url['scheme'] = 'ssl';

      elseif ($url['scheme'] == 'http')
        $url['scheme'] = 'tcp';

      $url['query'] = isset($url['query'])? $url['query']: '';
      $url['protocol'] = $url['scheme'] . '://';

      return $url;
    }

    function makeRequest($url, $headers) {
      // parse the given URL
      $content = "";
      $fp = fsockopen($url['protocol'] . $url['host'], $url['port'], $errno, $errstr, 30);
      if ($fp) {
        if (fwrite($fp, $headers)) {
        while (!feof($fp)) {
          $result .= fgets($fp, 128);
        }
        fclose($fp);

        // split the result header from the content
        $result = explode("\r\n\r\n", $result, 2);

        $header = isset($result[0]) ? $result[0] : '';
        $content = isset($result[1]) ? $result[1] : '';
        } else {
          fclose($fp);
        }
      }
      return $content;
    }

    function getRequest($url) {
      $url = parseUrl($url);
      $eol = "\r\n";
      $headers = "GET " . $url['path'] . "?" . $url['query'] . " HTTP/1.1" . $eol .
                 "Host: " . $url['host'].":".$url['port'] . $eol .
                 "Connection: close"  . $eol . $eol;
      return makeRequest ($url, $headers);
    }

    function postRequest($url, $data) {
      $url = parseUrl($url);
      $eol = "\r\n";
      $headers = "POST " . $url['path'] . " HTTP/1.1" . $eol.
                 "Host: " . $url['host'] . ":" . $url['port'] . $eol.
                 "Referer: " . $url['protocol'].$url['host'] . ":" . $url['port'] . $url['path'] . $eol.
                 "Content-Type: application/x-www-form-urlencoded" . $eol.
                 "Content-Length: " . strlen($data) . $eol . $eol . $data;
      return makeRequest ($url, $headers);
    }

    function selectList ($list, $param)
    {
      $V = Array ();
      $url = sprintf ("%s/lookup.list?key=%s", apiURL(), myUrlencode ($list));
      if ($param != "")
        $url = $url . sprintf ("&param=%s", myUrlencode ($param));
      $result = getRequest ($url);
      if ($result != "") {
      $xml = new SimpleXMLElement ($result);
      $items = $xml->xpath("/items/item");
      $N = 1;
      foreach ($items as $S)
      {
        if ($S <> "0")
          $V[$N] = $S;
        $N++;
      }
      }
      return $V;
    }

    function outFormTitle ($form)
    {
      if ($form == "login")
        print "Login";
      if ($form == "register")
        print "Register";
      if ($form == "user")
        print "View Profile";
      if ($form == "profile")
        print "Edit Profile";
    }

    function apiURL()
    {
      $pageURL = $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
      $pageURL .= $_SERVER['SERVER_PORT'] <> '80' ? $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"] : $_SERVER['SERVER_NAME'];
      return $pageURL.'/ods/api';
    }

    function myUrlencode ($S)
    {
      $S = urlencode($S);
      return $S;
    }

    $_REQUEST = array_merge($_GET, $_POST);
    $_error = "";
    if (isset ($_REQUEST['oid-form'])) {
      if ($_REQUEST['oid-form'] == 'lf')
        $_form = "login";
      if ($_REQUEST['oid-form'] == 'rf')
        $_form = "register";
    } else {
    $_form = (isset ($_REQUEST['form'])) ? $_REQUEST['form'] : "login";
    }
    $_formTab = intval((isset ($_REQUEST['formTab'])) ? $_REQUEST['formTab'] : "0");
    $_formSubtab = intval((isset ($_REQUEST['formSubtab'])) ? $_REQUEST['formSubtab'] : "0");
    $_formMode = (isset ($_REQUEST['formMode'])) ? $_REQUEST['formMode'] : "";
    $_sid = $_REQUEST['sid'];
    $_realm = "wa";

      if ($_form == "login")
      {
      if (isset ($_REQUEST['lf_register']) && ($_REQUEST['lf_register'] <> ""))
        $_form = "register";
      }

      if ($_form == "user")
      {
      if (isset ($_REQUEST['uf_profile']) && ($_REQUEST['uf_profile'] <> ""))
      {
          $_form = "profile";
        $_formTab = 0;
        $_formSubtab = 0;
      }
      }

      if ($_form == "profile")
      {
      if (isset ($_REQUEST['pf_update06']) && ($_REQUEST['pf_update06'] <> ""))
      {
        $_url = sprintf (
                          "%s/user.favorites.%s?sid=%s&realm=%s&id=%s&label=%s&uri=%s&properties=%s",
                          apiURL(),
                          $_formMode,
                          $_sid,
                          $_realm,
                          myUrlencode ($_REQUEST ["pf06_id"]),
                          myUrlencode ($_REQUEST ["pf06_label"]),
                          myUrlencode ($_REQUEST ["pf06_uri"]),
                          myUrlencode (str_replace ('\"', '"', $_REQUEST ["items"]))
                        );
        $_result = file_get_contents($_url);
        if (substr_count($_result, "<failed>") <> 0)
        {
          $_xml = simplexml_load_string($_result);
          $_error = $_xml->failed->message;
          $_form = "login";
        }
        $_formMode = "";
      }
      else if (isset ($_REQUEST['pf_update07']) && ($_REQUEST['pf_update07'] <> ""))
      {
        $_url = sprintf (
                          "%s/user.mades.%s?sid=%s&realm=%s&id=%s&property=%s&url=%s&description=%s",
                          apiURL(),
                          $_formMode,
                          $_sid,
                          $_realm,
                          myUrlencode ($_REQUEST ["pf07_id"]),
                          myUrlencode ($_REQUEST ["pf07_property"]),
                          myUrlencode ($_REQUEST ["pf07_url"]),
                          myUrlencode ($_REQUEST ["pf07_description"])
                        );
        $_result = file_get_contents($_url);
        if (substr_count($_result, "<failed>") <> 0)
        {
          $_xml = simplexml_load_string($_result);
          $_error = $_xml->failed->message;
          $_form = "login";
        }
        $_formMode = "";
      }
      else if (isset ($_REQUEST['pf_update08']) && ($_REQUEST['pf_update08'] <> ""))
      {
        $_url = sprintf (
                          "%s/user.offers.%s?sid=%s&realm=%s&id=%s&name=%s&comment=%s&properties=%s",
                          apiURL(),
                          $_formMode,
                          $_sid,
                          $_realm,
                          myUrlencode ($_REQUEST ["pf08_id"]),
                          myUrlencode ($_REQUEST ["pf08_name"]),
                          myUrlencode ($_REQUEST ["pf08_comment"]),
                          myUrlencode (str_replace ('\"', '"', $_REQUEST ["items"]))
                        );
        $_result = file_get_contents($_url);
        if (substr_count($_result, "<failed>") <> 0)
        {
          $_xml = simplexml_load_string($_result);
          $_error = $_xml->failed->message;
          $_form = "login";
        }
        $_formMode = "";
      }
      else if (isset ($_REQUEST['pf_update09']) && ($_REQUEST['pf_update09'] <> ""))
      {
        $_url = sprintf (
                          "%s/user.seeks.%s?sid=%s&realm=%s&id=%s&name=%s&comment=%s&properties=%s",
                          apiURL(),
                          $_formMode,
                          $_sid,
                          $_realm,
                          myUrlencode ($_REQUEST ["pf09_id"]),
                          myUrlencode ($_REQUEST ["pf09_name"]),
                          myUrlencode ($_REQUEST ["pf09_comment"]),
                          myUrlencode (str_replace ('\"', '"', $_REQUEST ["items"]))
                        );
        $_result = file_get_contents($_url);
        if (substr_count($_result, "<failed>") <> 0)
        {
          $_xml = simplexml_load_string($_result);
          $_error = $_xml->failed->message;
          $_form = "login";
        }
        $_formMode = "";
      }
      else if (isset ($_REQUEST['pf_update26']) && ($_REQUEST['pf_update26'] <> ""))
      {
        $_url = sprintf (
                          "%s/user.certificates.%s?sid=%s&realm=%s&id=%s&certificate=%s&enableLogin=%s",
                          apiURL(),
                          $_formMode,
                          $_sid,
                          $_realm,
                          myUrlencode ($_REQUEST ["pf26_id"]),
                          myUrlencode ($_REQUEST ["pf26_certificate"]),
                          myUrlencode ($_REQUEST ["pf26_enableLogin"])
                        );
        $_result = file_get_contents($_url);
        if (substr_count($_result, "<failed>") <> 0)
        {
          $_xml = simplexml_load_string($_result);
          $_error = $_xml->failed->message;
          $_form = "login";
        }
        $_formMode = "";
      }
      else if (isset ($_REQUEST['pf_cancel2']))
      {
        $_formMode = "";
      }
      else if (
          (isset ($_REQUEST['pf_update']) && ($_REQUEST['pf_update'] <> "")) ||
                (isset ($_REQUEST['pf_next']) && ($_REQUEST['pf_next'] <> ""))     ||
                (isset ($_REQUEST['pf_clear']) && ($_REQUEST['pf_clear'] <> ""))
         )
      {
        $_formMode = "";
        if ((($_formTab == 0) && ($_formSubtab == 3)) || (($_formTab == 1) && ($_formSubtab == 2)))
        {
          $_prefix = "x4";
          $_accountType = "P";
          if (($_formTab == 1) && ($_formSubtab == 2))
        {
            $_prefix = "y1";
            $_accountType = "B";
          }
          $_url = apiURL()."/user.onlineAccounts.delete?sid=".$_sid."&realm=".$_realm."&type=".$_accountType;
        $_result = file_get_contents($_url);
        if (substr_count($_result, "<failed>") <> 0)
          {
          $_xml = simplexml_load_string($_result);
            $_error = $_xml->failed->message;
            $_form = "login";
          }
          foreach($_REQUEST as $name => $value)
          {
            if ($_form == "login")
              break;

            if (substr_count($name, $_prefix."_fld_1_") <> 0)
            {
              $_sufix = str_replace($_prefix."_fld_1_", "", $name);
              $_url = apiURL()."/user.onlineAccounts.new?sid=".$_sid."&realm=".$_realm."&type=".$_accountType.
                      "&name=".myUrlencode ($_REQUEST[$_prefix."_fld_1_".$_sufix])."&url=".myUrlencode ($_REQUEST[$_prefix."_fld_2_".$_sufix]);
              $_result = file_get_contents($_url);
              if (substr_count($_result, "<failed>") <> 0)
              {
                $_xml = simplexml_load_string($_result);
                $_error = $_xml->failed->message;
                $_form = "login";
              }
            }
          }
        }
        else if (($_formTab == 0) && ($_formSubtab == 5))
        {
          $_prefix = "x5";
          $_url = apiURL()."/user.bioEvents.delete?sid=".$_sid."&realm=".$_realm;
          $_result = file_get_contents($_url);
          if (substr_count($_result, "<failed>") <> 0)
          {
            $_xml = simplexml_load_string($_result);
            $_error = $_xml->failed->message;
            $_form = "login";
          }
          foreach($_REQUEST as $name => $value)
          {
            if ($_form == "login")
              break;

            if (substr_count($name, $_prefix."_fld_1_") <> 0)
            {
              $_sufix = str_replace($_prefix."_fld_1_", "", $name);
              $_url = apiURL()."/user.bioEvents.new?sid=".$_sid."&realm=".$_realm.
                      "&event=".myUrlencode ($_REQUEST[$_prefix."_fld_1_".$_sufix])."&date=".myUrlencode ($_REQUEST[$_prefix."_fld_2_".$_sufix])."&place=".myUrlencode ($_REQUEST[$_prefix."_fld_3_".$_sufix]);
              $_result = file_get_contents($_url);
              if (substr_count($_result, "<failed>") <> 0)
              {
                $_xml = simplexml_load_string($_result);
                $_error = $_xml->failed->message;
                $_form = "login";
              }
            }
          }
        }
        else if (($_formTab == 2) && ($_formSubtab == 0))
        {
          if ($_REQUEST ["pf_newPassword"] != $_REQUEST ["pf_newPassword2"])
          {
            $_error = 'Bad new password. Please retype!';
          } else {
            $_url = sprintf (
                              "%s/user.password_change?sid=%s&realm=%s&old_password=%s&new_password=%s",
                              apiURL(),
                              $_sid,
                              $_realm,
                              myUrlencode ($_REQUEST ["pf_oldPassword"]),
                              myUrlencode ($_REQUEST ["pf_newPassword"])
                            );
            $_result = file_get_contents($_url);
            if (substr_count($_result, "<failed>") <> 0)
            {
              $_xml = simplexml_load_string($_result);
              $_error = $_xml->failed->message;
              $_form = "login";
            }
          }
        }
        else
        {
          $_url = apiURL()."/user.update.fields";
          $_params = "sid=".$_sid."&realm=".$_realm;
          if ($_formTab == 0)
          {
            if ($_formSubtab == 0)
            {
              // Import
              if ($_REQUEST['cb_item_i_name'] == '1')
                $_params .= '&nickName' . myUrlencode ($_REQUEST['i_nickName']);
              if ($_REQUEST['cb_item_i_title'] == '1')
                $_params .= '&title=' . myUrlencode ($_REQUEST['i_title']);
              if ($_REQUEST['cb_item_i_firstName'] == '1')
                $_params .= '&firstName=' . myUrlencode ($_REQUEST['i_firstName']);
              if ($_REQUEST['cb_item_i_lastName'] == '1')
                $_params .= '&lastName=' . myUrlencode ($_REQUEST['i_lastName']);
              if ($_REQUEST['cb_item_i_fullName'] == '1')
                $_params .= '&fullName=' . myUrlencode ($_REQUEST['i_fullName']);
              if ($_REQUEST['cb_item_i_gender'] == '1')
                $_params .= '&gender=' . myUrlencode ($_REQUEST['i_gender']);
              if ($_REQUEST['cb_item_i_mail'] == '1')
                $_params .= '&mail=' . myUrlencode ($_REQUEST['i_mail']);
              if ($_REQUEST['cb_item_i_birthday'] == '1')
                $_params .= '&birthday=' . myUrlencode ($_REQUEST['i_birthday']);
              if ($_REQUEST['cb_item_i_homepage'] == '1')
                $_params .= '&homepage=' . myUrlencode ($_REQUEST['i_homepage']);
              if ($_REQUEST['cb_item_i_icq'] == '1')
                $_params .= '&icq=' . myUrlencode ($_REQUEST['i_icq']);
              if ($_REQUEST['cb_item_i_aim'] == '1')
                $_params .= '&aim=' . myUrlencode ($_REQUEST['i_aim']);
              if ($_REQUEST['cb_item_i_yahoo'] == '1')
                $_params .= '&yahoo=' . myUrlencode ($_REQUEST['i_yahoo']);
              if ($_REQUEST['cb_item_i_msn'] == '1')
                $_params .= '&msn=' . myUrlencode ($_REQUEST['i_msn']);
              if ($_REQUEST['cb_item_i_skype'] == '1')
                $_params .= '&skype=' . myUrlencode ($_REQUEST['i_skype']);
              if ($_REQUEST['cb_item_i_homelat'] == '1')
                $_params .= '&homeLatitude=' . myUrlencode ($_REQUEST['i_homelat']);
              if ($_REQUEST['cb_item_i_homelng'] == '1')
                $_params .= '&homeLongitude=' . myUrlencode ($_REQUEST['i_homelng']);
              if ($_REQUEST['cb_item_i_homePhone'] == '1')
                $_params .= '&homePhone=' . myUrlencode ($_REQUEST['i_homePhone']);
              if ($_REQUEST['cb_item_i_businessOrganization'] == '1')
                $_params .= '&businessOrganization=' . myUrlencode ($_REQUEST['i_businessOrganization']);
              if ($_REQUEST['cb_item_i_businessHomePage'] == '1')
                $_params .= '&businessHomePage=' . myUrlencode ($_REQUEST['i_businessHomePage']);
              if ($_REQUEST['cb_item_i_summary'] == '1')
                $_params .= '&summary=' . myUrlencode ($_REQUEST['i_summary']);
              if ($_REQUEST['cb_item_i_tags'] == '1')
                $_params .= '&tags=' . myUrlencode ($_REQUEST['i_tags']);
              if ($_REQUEST['cb_item_i_sameAs'] == '1')
                $_params .= '&webIDs=' . myUrlencode ($_REQUEST['i_sameAs']);
              if ($_REQUEST['cb_item_i_interests'] == '1')
                $_params .= '&interests=' . myUrlencode ($_REQUEST['i_interests']);
              if ($_REQUEST['cb_item_i_topicInterests'] == '1')
                $_params .= '&topicInterests=' . myUrlencode ($_REQUEST['i_topicInterests']);
              if ($_REQUEST['cb_item_i_onlineAccounts'] == '1')
                $_params .= '&onlineAccounts=' . myUrlencode ($_REQUEST['i_onlineAccounts']);
            }
            else if ($_formSubtab == 1)
            {
              // Main
              $_params .=
                  "&nickName=".               myUrlencode ($_REQUEST['pf_nickName']).
                  "&mail=".                   myUrlencode ($_REQUEST['pf_mail']).
                  "&title=".                  myUrlencode ($_REQUEST['pf_title']).
                  "&firstName=".              myUrlencode ($_REQUEST['pf_firstName']).
                  "&lastName=".               myUrlencode ($_REQUEST['pf_lastName']).
                  "&fullName=".               myUrlencode ($_REQUEST['pf_fullName']).
                  "&gender=".                 myUrlencode ($_REQUEST['pf_gender']).
                  "&birthday=".               myUrlencode ($_REQUEST['pf_birthday']).
                  "&homepage=".               myUrlencode ($_REQUEST['pf_homepage']).
                  "&mailSignature=".          myUrlencode ($_REQUEST['pf_mailSignature']).
                  "&summary=".                myUrlencode ($_REQUEST['pf_summary']).
                  "&appSetting=".             myUrlencode ($_REQUEST['pf_appSetting']).
                  "&photo=".                  myUrlencode ($_REQUEST['pf_photo']).
                  "&audio=".                  myUrlencode ($_REQUEST['pf_audio']);
              if ($_FILES['pf_photoContent']['size'] > 0)
              {
                $_tmpName  = $_FILES['pf_photoContent']['tmp_name'];
                $_fp = fopen($_tmpName, 'r');
                $_content = fread($_fp, filesize($_tmpName));
                $_params .=
                  "&photoContent=".myUrlencode ($_content);
              }
              if ($_FILES['pf_audioContent']['size'] > 0)
              {
                $_tmpName  = $_FILES['pf_audioContent']['tmp_name'];
                $_fp = fopen($_tmpName, 'r');
                $_content = fread($_fp, filesize($_tmpName));
                $_params .=
                  "&audioContent=".myUrlencode ($_content);
              }
              $_tmp = "";
              foreach($_REQUEST as $name => $value)
              {
                if (substr_count($name, 'x1_fld_1_') <> 0)
                  $_tmp = $_tmp . $value . '\n';
              }
              $_params .= "&webIDs=" . myUrlencode ($_tmp);
              $_tmp = "";
              foreach($_REQUEST as $name => $value)
              {
                if (substr_count($name, 'x2_fld_1_') <> 0)
                {
                  $_sufix = str_replace("x2_fld_1_", "", $name);
                  $_tmp = $_tmp . $value . ";" . $_REQUEST['x2_fld_2_'.$_sufix] . "\n";
                }
              }
              $_params .= "&interests=" . myUrlencode ($_tmp);
              $_tmp = "";
              foreach($_REQUEST as $name => $value)
              {
                if (substr_count($name, 'x3_fld_1_') <> 0)
                {
                  $_sufix = str_replace("x3_fld_1_", "", $name);
                  $_tmp = $_tmp . $value . ";" . $_REQUEST['x3_fld_2_'.$_sufix] . '\n';
                }
              }
              $_params .= "&topicInterests=" . myUrlencode ($_tmp);
            }
            if ($_formSubtab == 2)
            {
              $_params .=
                  "&homeDefaultMapLocation=". myUrlencode ($_REQUEST['pf_homeDefaultMapLocation']).
                  "&homeCountry=".            myUrlencode ($_REQUEST['pf_homecountry']).
                  "&homeState=".              myUrlencode ($_REQUEST['pf_homestate']).
                  "&homeCity=".               myUrlencode ($_REQUEST['pf_homecity']).
                  "&homeCode=".               myUrlencode ($_REQUEST['pf_homecode']).
                  "&homeAddress1=".           myUrlencode ($_REQUEST['pf_homeaddress1']).
                  "&homeAddress2=".           myUrlencode ($_REQUEST['pf_homeaddress2']).
                  "&homeTimezone=".           myUrlencode ($_REQUEST['pf_homeTimezone']).
                  "&homeLatitude=".           myUrlencode ($_REQUEST['pf_homelat']).
                  "&homeLongitude=".          myUrlencode ($_REQUEST['pf_homelng']).
                  "&homePhone=".              myUrlencode ($_REQUEST['pf_homePhone']).
                  "&homePhoneExt=".           myUrlencode ($_REQUEST['pf_homePhoneExt']).
                  "&homeMobile=".             myUrlencode ($_REQUEST['pf_homeMobile']);
            }
            if ($_formSubtab == 4)
            {
              $_params .=
                  "&icq=".                    myUrlencode ($_REQUEST['pf_icq']).
                  "&skype=".                  myUrlencode ($_REQUEST['pf_skype']).
                  "&yahoo=".                  myUrlencode ($_REQUEST['pf_yahoo']).
                  "&aim=".                    myUrlencode ($_REQUEST['pf_aim']).
                  "&msn=".                    myUrlencode ($_REQUEST['pf_msn']);
              $_tmp = "";
              foreach($_REQUEST as $name => $value)
              {
                if (substr_count($name, 'x6_fld_1_') <> 0)
                {
                  $_sufix = str_replace("x6_fld_1_", "", $name);
                  $_tmp = $_tmp . $value . ";" . $_REQUEST['x6_fld_2_'.$_sufix] . '\n';
                }
              }
              $_params .= "&messaging=" . myUrlencode ($_tmp);
            }
          }
          if ($_formTab == 1)
          {
            if ($_formSubtab == 0)
            {
              $_params .=
                  "&businessIndustry=".       myUrlencode ($_REQUEST['pf_businessIndustry']).
                  "&businessOrganization=".   myUrlencode ($_REQUEST['pf_businessOrganization']).
                  "&businessHomePage=".       myUrlencode ($_REQUEST['pf_businessHomePage']).
                  "&businessJob=".            myUrlencode ($_REQUEST['pf_businessJob']).
                  "&businessRegNo=".          myUrlencode ($_REQUEST['pf_businessRegNo']).
                  "&businessCareer=".         myUrlencode ($_REQUEST['pf_businessCareer']).
                  "&businessEmployees=".      myUrlencode ($_REQUEST['pf_businessEmployees']).
                  "&businessVendor=".         myUrlencode ($_REQUEST['pf_businessVendor']).
                  "&businessService=".        myUrlencode ($_REQUEST['pf_businessService']).
                  "&businessOther=".          myUrlencode ($_REQUEST['pf_businessOther']).
                  "&businessNetwork=".        myUrlencode ($_REQUEST['pf_businessNetwork']).
                  "&businessResume=".         myUrlencode ($_REQUEST['pf_businessResume']);
            }
            if ($_formSubtab == 1)
            {
              $_params .=
                  "&businessCountry=".        myUrlencode ($_REQUEST['pf_businesscountry']).
                  "&businessState=".          myUrlencode ($_REQUEST['pf_businessstate']).
                  "&businessCity=".           myUrlencode ($_REQUEST['pf_businesscity']).
                  "&businessCode=".           myUrlencode ($_REQUEST['pf_businesscode']).
                  "&businessAddress1=".       myUrlencode ($_REQUEST['pf_businessaddress1']).
                  "&businessAddress2=".       myUrlencode ($_REQUEST['pf_businessaddress2']).
                  "&businessTimezone=".       myUrlencode ($_REQUEST['pf_businessTimezone']).
                  "&businessLatitude=".       myUrlencode ($_REQUEST['pf_businesslat']).
                  "&businessLongitude=".      myUrlencode ($_REQUEST['pf_businesslng']).
                  "&businessPhone=".          myUrlencode ($_REQUEST['pf_businessPhone']).
                  "&businessPhoneExt=".       myUrlencode ($_REQUEST['pf_businessPhoneExt']).
                  "&businessMobile=".         myUrlencode ($_REQUEST['pf_businessMobile']);
            }
            if ($_formSubtab == 3)
            {
              $_params .=
                  "&businessIcq=".            myUrlencode ($_REQUEST['pf_businessIcq']).
                  "&businessSkype=".          myUrlencode ($_REQUEST['pf_businessSkype']).
                  "&businessYahoo=".          myUrlencode ($_REQUEST['pf_businessYahoo']).
                  "&businessAim=".            myUrlencode ($_REQUEST['pf_businessAim']).
                  "&businessMsn=".            myUrlencode ($_REQUEST['pf_businessMsn']);
              $_tmp = "";
              foreach($_REQUEST as $name => $value)
              {
                if (substr_count($name, 'y2_fld_1_') <> 0)
                {
                  $_sufix = str_replace("y2_fld_1_", "", $name);
                  $_tmp = $_tmp . $value . ";" . $_REQUEST['y2_fld_2_'.$_sufix] . '\n';
                }
              }
              $_params .= "&businessMessaging=" . myUrlencode ($_tmp);
            }
          }
          if ($_formTab == 2)
          {
            if ($_formSubtab == 1)
              $_params .=
                  "&securitySecretQuestion=" . myUrlencode ($_REQUEST['pf_securitySecretQuestion']).
                  "&securitySecretAnswer=" . myUrlencode ($_REQUEST['pf_securitySecretAnswer']);

            if ($_formSubtab == 2)
              $_params .=
                  "&securityOpenID=" . myUrlencode ($_REQUEST['pf_securityOpenID']);

            if ($_formSubtab == 3)
            {
              if (isset ($_REQUEST['pf_clear']) && ($_REQUEST['pf_clear'] <> ""))
              {
                $_params .=
                    "&securityFacebookID=";
              } else {
              $_params .=
                  "&securityFacebookID=" . myUrlencode ($_REQUEST['pf_securityFacebookID']);
              }
            }

            if ($_formSubtab == 4)
              $_params .=
                  "&securitySiocLimit=" . myUrlencode ($_REQUEST['pf_securitySiocLimit']);
          }
          $_result = postRequest($_url, $_params);
          if (substr_count($_result, "<failed>") <> 0)
          {
            $_xml = simplexml_load_string($_result);
            $_error = $_xml->failed->message;
            $_form = "login";
          }
          $_url = apiURL()."/user.acl.update";
          $_tmp = "";
          foreach($_REQUEST as $name => $value)
          {
            if (substr_count($name, 'pf_acl_') <> 0)
              $_tmp = $_tmp . str_replace("pf_acl_", "", $name) . "=" . $value . '&';
          }
          $_params = "sid=".$_sid."&realm=".$_realm."&acls=" . myUrlencode ($_tmp);
          $_result = postRequest($_url, $_params);
          if (substr_count($_result, "<failed>") <> 0)
          {
            $_xml = simplexml_load_string($_result);
            $_error = $_xml->failed->message;
            $_form = "login";
          }
        }
        if (isset ($_REQUEST['pf_next']) && ($_REQUEST['pf_next'] <> ""))
        {
          $_formSubtab = $_formSubtab + 1;
          if (
              (($_formTab == 1) && ($_formSubtab > 3)) ||
              (($_formTab == 2) && ($_formSubtab > 6))
             )
          {
            $_formTab = $_formTab + 1;
            $_formSubtab = 0;
          }
          if ($_formTab == 3)
            $_formTab = 0;
          }
      }
      else if (isset ($_REQUEST['pf_cancel']) && ($_REQUEST['pf_cancel'] <> ""))
          {
            $_form = "user";
          }
        }

    if (($_form == "user") || ($_form == "profile"))
        {
      $_url = sprintf ("%s/user.info?sid=%s&realm=%s", apiURL(), $_sid, $_realm);
      if ($_form == "profile")
        $_url .= "&short=0";
      $_result = getRequest ($_url);
      $_xml = simplexml_load_string($_result);
      if (substr_count($_result, "<failed>") <> 0)
          {
        $_error = $_xml->failed->message;
            $_form = "login";
          }
      elseif ($_form == "profile")
      {
        $_url = sprintf ("%s/user.acl.info?sid=%s&realm=%s", apiURL(), $_sid, $_realm);
        $_result = getRequest ($_url);
        $_acl = simplexml_load_string($_result);
        if (substr_count($_result, "<failed>") <> 0)
        {
          $_error = $_xml->failed->message;
          $_form = "login";
        }
      else
        {
        $_industries = selectList ('Industry', '');
        $_countries = selectList ('Country', '');
        }
        $ACL = array ('public', '1', 'friends', '2', 'private', '3');
      }
      }

      if ($_form == "login")
      {
        $_sid = "";
        $_realm = "";
      }
  ?>
  <body onunload="myCheckLeave (document.forms['page_form'])">
    <form name="page_form" id="page_form" method="post" enctype="multipart/form-data" action="users.php">
      <input type="hidden" name="mode" id="mode" value="php" />
      <input type="hidden" name="sid" id="sid" value="<?php print($_sid); ?>" />
      <input type="hidden" name="realm" id="realm" value="<?php print($_realm); ?>" />
      <input type="hidden" name="form" id="form" value="<?php print($_form); ?>" />
      <input type="hidden" name="formTab" id="formTab" value="<?php print($_formTab); ?>" />
      <input type="hidden" name="formSubtab" id="formSubtab" value="<?php print($_formSubtab); ?>" />
      <input type="hidden" name="formMode" id="formMode" value="<?php print($_formMode); ?>" />
      <input type="hidden" name="items" id="items" value="" />
      <input type="hidden" name="securityNo" id="securityNo" value="" />
      <div id="ob">
        <div id="ob_left">
          <?php
            if (($_form == "profile") || ($_form == "user"))
              print sprintf ('<b>User</b>: %s, <b>Profile</b>: <a href="#" onclick="javascript: return profileSubmit();">Edit</a> / <a href="#" onclick="javascript: return userSubmit();">View</a>', $_xml->fullName);
          ?>
        </div>
        <div id="ob_right">
        <?php
          if (($_form <> 'login') && ($_form <> 'register'))
          {
        ?>
          <a href="#" onclick="javascript: return logoutSubmit();">Logout</a>&nbsp;
        <?php
          }
        ?>
      </div>
      </div>
      <div id="MD">
        <table cellspacing="0">
          <tr>
            <td valign="top">
              <img class="logo" src="/ods/images/odslogo_200.png" /><br />
            </td>
            <td>
              <?php
              if ($_form == 'login')
              {
              ?>
              <div id="lf" class="form">
                <?php
                  if ($_error <> '')
                  {
                    print "<div class=\"error\">".$_error."</div>";
                  }
                ?>
                <div class="header">
                  Identify Yourself
                </div>
                <ul id="lf_tabs" class="tabs">
                  <li id="lf_tab_0" title="Digest">Digest</li>
                  <li id="lf_tab_1" title="OpenID" style="display: none;">OpenID</li>
                  <li id="lf_tab_2" title="Facebook" style="display: none;">Facebook</li>
                  <li id="lf_tab_3" title="WebID" style="display: none;">WebID</li>
                </ul>
                <div style="min-height: 120px; border: 1px solid #aaa; margin: -13px 5px 5px 5px;">
                  <div id="lf_content"></div>
                  <div id="lf_page_0" class="tabContent" >
                <table class="form" cellspacing="5">
                  <tr>
                    <th width="30%">
                          <label for="lf_uid">User ID</label>
                    </th>
                        <td>
                          <input type="text" name="lf_uid" value="" id="lf_uid" style="width: 150px;" />
                    </td>
                  </tr>
                  <tr>
                    <th>
                      <label for="lf_password">Password</label>
                    </th>
                        <td>
                          <input type="password" name="lf_password" value="" id="lf_password" style="width: 150px;" />
                    </td>
                  </tr>
                    </table>
                  </div>
                  <div id="lf_page_1" class="tabContent" style="display: none">
                    <table class="form" cellspacing="5">
                  <tr>
                        <th width="30%">
                          <label for="lf_openId">OpenID URL</label>
                    </th>
                        <td>
                          <input type="text" name="lf_openId" value="" id="lf_openId" style="width: 300px;" />
                        </td>
                  </tr>
                    </table>
                  </div>
                  <div id="lf_page_2" class="tabContent" style="display: none">
                    <table class="form" cellspacing="5">
                  <tr>
                        <th width="30%">
                    </th>
                        <td>
                          <span id="lf_facebookData" style="min-height: 20px;"></span>
                          <br />
                          <script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php" type="text/javascript"></script>
                          <fb:login-button autologoutlink="true"></fb:login-button>
                    </td>
                  </tr>
                </table>
                  </div>
                  <div id="lf_page_3" class="tabContent" style="display: none">
                    <table id="lf_table_3" class="form" cellspacing="5">
                    </table>
                  </div>
                </div>
                <div class="footer">
                  <input type="submit" name="lf_login" value="Login" id="lf_login" onclick="javascript: return lfLoginSubmit();" />
                  <input type="submit" name="lf_register" value="Sign Up" id="lf_register" />
                </div>
              </div>
              <?php
              }
              if ($_form == 'register')
              {
              ?>
              <div id="rf" class="form">
                <div class="header">
                  User register
                </div>
                <ul id="rf_tabs" class="tabs">
                  <li id="rf_tab_0" title="Digest">Digest</li>
                  <li id="rf_tab_1" title="OpenID" style="display: none;">OpenID</li>
                  <li id="rf_tab_2" title="Facebook" style="display: none;">Facebook</li>
                  <li id="rf_tab_3" title="WebID" style="display: none;">WebID</li>
                </ul>
                <div style="min-height: 135px; border: 1px solid #aaa; margin: -13px 5px 5px 5px;">
                  <div id="rf_content"></div>
                  <div id="rf_page_0" class="tabContent" style="display: none">
                    <table id="rf_table_0" class="form" cellspacing="5">
                      <tr>
                        <th width="30%">
                          <label for="rf_uid">Login Name<div style="font-weight: normal; display:inline; color:red;"> *</div></label>
                        </th>
                        <td>
                          <input type="text" name="rf_uid" value="" id="rf_uid" style="width: 150px;" />
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="rf_email">E-mail<div style="font-weight: normal; display:inline; color:red;"> *</div></label>
                        </th>
                        <td>
                          <input type="text" name="rf_email" value="" id="rf_email" size="40"/>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="rf_password">Password<div style="font-weight: normal; display:inline; color:red;"> *</div></label>
                        </th>
                        <td>
                          <input type="password" name="rf_password" value="" id="rf_password" style="width: 150px;" />
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="rf_password2">Password (verify)<div style="font-weight: normal; display:inline; color:red;"> *</div></label>
                        </th>
                        <td>
                          <input type="password" name="rf_password2" value="" id="rf_password2" style="width: 150px;" />
                        </td>
                      </tr>
                    </table>
                  </div>
                  <div id="rf_page_1" class="tabContent" style="display: none">
                    <table id="rf_table_1" class="form" cellspacing="5">
                      <tr>
                        <th width="30%">
                          <label for="rf_openId">OpenID</label>
                        </th>
                        <td>
                          <input type="text" name="rf_openId" value="" id="rf_openId" size="40"/>
                        </td>
                      </tr>
                    </table>
                  </div>
                  <div id="rf_page_2" class="tabContent" style="display: none">
                    <table id="rf_table_2" class="form" cellspacing="5">
                      <tr>
                        <th width="30%">
                        </th>
                        <td>
                          <span id="rf_facebookData" style="min-height: 20px;"></span>
                          <br />
                          <script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php" type="text/javascript"></script>
                          <fb:login-button autologoutlink="true"></fb:login-button>
                        </td>
                      </tr>
                    </table>
                  </div>
                  <div id="rf_page_3" class="tabContent" style="display: none">
                    <table id="rf_table_3" class="form" cellspacing="5">
                    </table>
                  </div>
                </div>
                <div>
                  <table class="form" cellspacing="5">
                    <tr>
                      <th width="30%">
                      </th>
                      <td>
                        <input type="checkbox" name="rf_is_agreed" value="1" id="rf_is_agreed"/><label for="rf_is_agreed">I agree to the <a href="/ods/terms.html" target="_blank">Terms of Service</a>.</label>
                      </td>
                    </tr>
                  </table>
                </div>
                <div class="footer" id="rf_login_5">
                  <input type="button" id="rf_signup" name="rf_signup" value="Sign Up" onclick="javascript: return rfSignupSubmit();" />
                </div>
              </div>
              <?php
              }
              if ($_form == 'user')
              {
              ?>
              <div id="uf" class="form" style="width: 100%;">
                <div class="header">
                  User profile
                </div>

                <div id="uf_div_new" style="clear: both;">
                </div>
                <div id="uf_div" style="clear: both; display: none;">
              	  <div id="u_profile_l">
              	    <div id="user_info_w" class="widget user_info_w">
              	      <div class="w_title" id="userProfilePhotoName">
              	        <h3></h3>
              	      </div>
              	      <div class="w_content">
                        <div class="user_img_ctr">
                          <a href="javascript:void(0)">
                       		  <img alt="Profile image" id="userProfilePhotoImg" rel="foaf:depiction" class="prof_photo" src="/ods/images/profile.png"/>
                          </a>
                        </div> <!-- user_img_ctr -->
                        <div class="gems_ctr">
                          <div class="prof_user_gems" id="profileUserGems">
                            <div class="gem">
                              <a href="javascript:void(0)" id="uf_foaf_gem" target="_blank"><img src="/ods/images/icons/foaf.png" alt="FOAF"/></a>
                            </div>
                            <div class="gem">
                              <a href="javascript:void(0)" id="uf_sioc_gem" target="_blank"><img src="/ods/images/icons/sioc_button.png" alt="SIOC"/></a>
                            </div>
                            <div class="gem">
                              <a href="javascript:void(0)" id="uf_vcard_gem" target="_blank"><img src="/ods/images/icons/vcard.png" alt="VCARD"/></a>
                            </div>
                          </div> <!-- prof_user_gems -->
                        </div> <!-- gems_ctr -->
                      </div> <!-- w_content -->
          	        </div> <!-- .widget -->

            	      <div id="ds_w" class="widget ds_w">
            	        <div class="w_title">
                        <h3>Data Space</h3>
          		          <div class="w_title_bar_btns">
                    		  <img src="/ods/images/skin/default/menu_dd_handle_close.png" alt="Minimize" class="w_toggle" onclick="widgetToggle(this);"/>
                    		</div> <!-- w_title_bar_btns -->
          	          </div> <!-- w_title -->
            	        <div class="w_content">
                        <ul class="ds_list" id="ds_list">
                        </ul> <!-- ds_list -->
                        <div class="cmd_ctr">&nbsp;</div>
                      </div> <!-- w_content -->
                    </div> <!-- .widget -->

              	    <div id="connections_w" class="widget connections_w">
              	      <div class="w_title">
                        <h3 id="connPTitleTxt">Connections</h3>
                        <div class="w_title_bar_btns">
                          <img src="/ods/images/skin/default/menu_dd_handle_close.png" alt="Minimize" class="w_toggle" onclick="widgetToggle(this);"/>
                        </div> <!-- w_title_bar_btns -->
              	      </div> <!-- w_title -->
            	        <div class="w_content" id="connP1" style="height: 200px;">
                      </div> <!-- w_content -->
            	      </div> <!-- .widget -->

                    <div id="groups_w" class="widget groups_w" style="display: none;">
            	        <div class="w_title">
            	          <h3 id="discussionsTitleTxt">Discussion Groups ()</h3>
            	        </div>
            	        <div class="w_content" id="discussionsCtr">
                      </div> <!-- w_content -->
            	      </div> <!-- .widget -->
          	      </div>

              	  <div id="u_profile_r" style="width: 100%;">
                    <div class="widget w_contact" about="#THIS" instanceof="foaf:Person">
                      <div class="w_title">
                        <h3>Contact Information</h3>
                        <div class="w_title_bar_btns">
                    		  <img src="/ods/images/skin/default/menu_dd_handle_close.png" alt="Minimize" class="w_toggle" onclick="widgetToggle(this);"/>
                    		</div>
                      </div>
                      <div class="w_content">
                <ul id="uf_tabs" class="tabs">
                  <li id="uf_tab_0" title="Personal">Personal</li>
                  <li id="uf_tab_1" title="Messaging Services">Messaging Services</li>
                  <li id="uf_tab_2" title="Home">Home</li>
                  <li id="uf_tab_3" title="Business">Business</li>
                  <li id="uf_tab_4" title="Data Explorer">Data Explorer</li>
                </ul>
                <div style="min-height: 180px; border: 1px solid #aaa; margin: -13px 5px 5px 5px;">
                  <div id="uf_content"></div>
                  <div id="uf_page_0" class="tabContent" >
                    <table id="uf_table_0" class="form" cellspacing="5">
                    </table>
                  </div>
                          <div id="uf_page_1" class="tabContent" style="display: none">
                    <table id="uf_table_1" class="form" cellspacing="5">
                    </table>
                  </div>
                          <div id="uf_page_2" class="tabContent" style="display: none">
                    <table id="uf_table_2" class="form" cellspacing="5">
                    </table>
                  </div>
                          <div id="uf_page_3" class="tabContent" style="display: none">
                    <table id="uf_table_3" class="form" cellspacing="5">
                </table>
                  </div>
                          <div id="uf_page_4" class="tabContent" style="display: none">
                    <div id="uf_rdf_content">
                      &nbsp;
                    </div>
                  </div>
                  <script type="text/javascript">
                    OAT.MSG.attach(OAT, "PAGE_LOADED", function (){selectProfile();});
                    OAT.MSG.attach(OAT, "PAGE_LOADED", function (){cRDF.open("<?php print($_xml->iri); ?>");});
                  </script>
                </div>
                      </div>
                    </div>

                    <div id="notify" class="notify_w widget">
                      <div class="w_title">
                        <h3>Activities</h3>
                        <div class="w_title_bar_btns">
                      	  <img src="/ods/images/skin/default/menu_dd_handle_close.png" alt="Minimize" class="w_toggle" onclick="widgetToggle(this);"/>
                      	</div> <!-- w_title_bar_btns -->
                      </div>
                      <div class="w_content" id="notify_content">
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <?php
              }
              if ($_form == 'profile')
              {
              ?>

              <div id="pf" class="form" style="width: 100%;">
                <?php
                  if ($_error <> '')
                  {
                    print "<div class=\"error\">".$_error."</div>";
                  }
                ?>
                <div class="header">
                  Update user profile
                </div>
                <ul id="pf_tabs" class="tabs">
                  <li id="pf_tab_0" title="Personal">Personal</li>
                  <li id="pf_tab_1" title="Business">Business</li>
                  <li id="pf_tab_2" title="Security">Security</li>
                </ul>
                <div style="min-height: 180px; border-top: 1px solid #aaa; margin: -13px 5px 5px 5px;">
                  <div id="pf_page_0" class="tabContent" style="display:none;">
                    <ul id="pf_tabs_0" class="tabs">
                      <li id="pf_tab_0_0" title="Import">Profile Import</li>
                      <li id="pf_tab_0_1" title="Main">Main</li>
                      <li id="pf_tab_0_2" title="Address">Address</li>
                      <li id="pf_tab_0_3" title="Online Accounts">Online Accounts</li>
                      <li id="pf_tab_0_4" title="Messaging Services">Messaging Services</li>
                      <li id="pf_tab_0_5" title="Biographical Events">Biographical Events</li>
                      <li id="pf_tab_0_6" title="Favorite Things">Favorite Things</li>
                      <li id="pf_tab_0_7" title="Creator Of">Creator Of</li>
                      <li id="pf_tab_0_8" title="My Offers">My Offers</li>
                      <li id="pf_tab_0_9" title="Offers I Seek">Offers I Seek</li>
                    </ul>
                    <div style="min-height: 180px; min-width: 650px; border-top: 1px solid #aaa; margin: -13px 5px 5px 5px;">
                      <div id="pf_page_0_0" class="tabContent" style="display:none;">
                    <table class="form" cellspacing="5">
                      <tr>
                            <th>
                              <label for="pf_foaf">Profile Document URL</label>
                            </th>
                            <td>
                              <input type="text" name="pf_foaf" value="" id="pf_foaf" style="width: 400px;" />
                              <input type="button" value="Import" onclick="javascript: pfGetFOAFData($v('pf_foaf')); return false;" class="button" />
                              <img id="pf_import_image" alt="Import FOAF Data" src="/ods/images/oat/Ajax_throbber.gif" style="display: none" />
                            </td>
                          </tr>
                        </table>
                        <table id="i_tbl" class="listing" style="display: none;">
                          <thead>
                            <tr class="listing_header_row">
                              <th width="1%"><input type="checkbox" name="cb_all" value="Select All" onclick="selectAllCheckboxes(this, 'cb_item')" /></th>
                              <th>Field</th>
                              <th>Value</th>
                            </tr>
                          </thead>
                          <tbody id="i_tbody">
                          </tbody>
                        </table>
                      </div>
                      <div id="pf_page_0_1" class="tabContent" style="display:none;">
                        <table class="form" cellspacing="5">
                          <tr>
                            <th>
                              <label for="pf_loginName">Login Name</label>
                            </th>
                            <td>
                              <?php print($_xml->name); ?>
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_nickName">Nick Name</label>
                            </th>
                            <td>
                              <input type="text" name="pf_nickName" value="<?php print($_xml->nickName); ?>" id="pf_nickName" style="width: 220px;" />
                            </td>
                          </tr>
                          <tr>
                            <th width="30%">
                          <label for="pf_title">Title</label>
                        </th>
                        <td>
                          <select name="pf_title" id="pf_title">
                                <option></option>
                            <?php
                              $X = array ("Mr", "Mrs", "Dr", "Ms");
                              for ($N = 0; $N < count ($X); $N += 1)
                                print sprintf("<option %s>%s</option>", ((strcmp($X[$N], $_xml->title) == 0) ? "selected=\"selected\"" : ""), $X[$N]);
                            ?>
                          </select>
                              <select name="pf_acl_title" id="pf_acl_title">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->title) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_firstName">First Name</label>
                        </th>
                        <td>
                          <input type="text" name="pf_firstName" value="<?php print($_xml->firstName); ?>" id="pf_firstName" style="width: 220px;" />
                              <select name="pf_acl_firstName" id="pf_acl_firstName">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->firstName) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_lastName">Last Name</label>
                        </th>
                        <td>
                          <input type="text" name="pf_lastName" value="<?php print($_xml->lastName); ?>" id="pf_lastName" style="width: 220px;" />
                              <select name="pf_acl_lastName" id="pf_acl_lastName">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->lastName) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_fullName">Full Name</label>
                        </th>
                        <td>
                          <input type="text" name="pf_fullName" value="<?php print($_xml->fullName); ?>" id="pf_fullName" size="60" />
                              <select name="pf_acl_fullName" id="pf_acl_fullName">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->fullName) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_mail">E-mail</label>
                        </th>
                        <td>
                          <input type="text" name="pf_mail" value="<?php print($_xml->mail); ?>" id="pf_mail" style="width: 220px;" />
                              <select name="pf_acl_mail" id="pf_acl_mail">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->mail) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_gender">Gender</label>
                        </th>
                        <td>
                          <select name="pf_gender" value="" id="pf_gender">
                            <option></option>
                            <?php
                              $X = array ("Male", "Female");
                              for ($N = 0; $N < count ($X); $N += 1)
                                print sprintf("<option value=\"%s\" %s>%s</option>", strtolower($X[$N]), ((strcmp(strtolower($X[$N]), $_xml->gender) == 0) ? "selected=\"selected\"": ""), $X[$N]);
                            ?>
                          </select>
                              <select name="pf_acl_gender" id="pf_acl_gender">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->gender) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_birthday">Birthday</label>
                        </th>
                        <td>
                          <input name="pf_birthday" id="pf_birthday" value="<?php print($_xml->birthday); ?>" onclick="datePopup('pf_birthday');"/>
                              <select name="pf_acl_birthday" id="pf_acl_birthday">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->birthday) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_homepage">Personal Webpage</label>
                        </th>
                        <td>
                          <input type="text" name="pf_homepage" value="<?php print($_xml->homepage); ?>" id="pf_homepage" style="width: 220px;" />
                              <select name="pf_acl_homepage" id="pf_acl_homepage">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->homepage) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                            <th>
                              <label for="pf_foaf">Other Personal URIs (Web IDs)</label>
                        </th>
                            <td>
                              <table>
                                <tr>
                                  <td width="600px" style="padding: 0px;">
                                    <table id="x1_tbl" class="listing">
                                      <thead>
                                        <tr class="listing_header_row">
                                          <th>
                                            URI
                                          </th>
                                          <th width="65px">
                                            Action
                                          </th>
                                        </tr>
                                      </thead>
                                      <tr id="x1_tr_no" style="display: none;"><td colspan="2"><b>No Personal URIs</b></td></tr>
                                      <script type="text/javascript">
                                        OAT.MSG.attach(OAT, "PAGE_LOADED", function (){pfShowRows("x1", '<?php print(str_replace("\n", "\\n", $_xml->webIDs)); ?>', ["\n"], function(prefix, val1){TBL.createRow(prefix, null, {fld_1: {value: val1, className: '_validate_ _webid_ _canEmpty_'}});});});
                                      </script>
                                    </table>
                                  </td>
                                  <td valign="top" nowrap="nowrap">
                                    <span class="button pointer" onclick="TBL.createRow('x1', null, {fld_1: {className: '_validate_ _webid_ _canEmpty_'}});"><img class="button" src="/ods/images/icons/add_16.png" border="0" alt="Add Row" title="Add Row" /> Add</span>
                                    <select name="pf_acl_webIDs" id="pf_acl_webIDs">
                                      <?php
                                        for ($N = 0; $N < count ($ACL); $N += 2)
                                          print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->webIDs) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                      ?>
                                    </select>
                                  </td>
                                </tr>
                              </table>
                        </td>
                      </tr>
                      <tr>
                        <th>
                              <label for="pf_mailSignature">Mail Signature</label>
                        </th>
                        <td>
                              <textarea name="pf_mailSignature" id="pf_mailSignature" style="width: 400px;"><?php print($_xml->mailSignature); ?></textarea>
                        </td>
                      </tr>
                      <tr>
                        <th>
                              <label for="pf_summary">Summary</label>
                        </th>
                        <td>
                              <textarea name="pf_summary" id="pf_summary" style="width: 400px;"><?php print($_xml->summary); ?></textarea>
                              <select name="pf_acl_summary" id="pf_acl_summary">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->summary) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                              <label for="pf_foaf">Web page URL indicating a topic of interest</label>
                        </th>
                            <td>
                              <table>
                                <tr>
                                  <td width="600px" style="padding: 0px;">
                                    <table id="x2_tbl" class="listing">
                                      <thead>
                                        <tr class="listing_header_row">
                                          <th>
                                            URL
                                          </th>
                                          <th>
                                            Label
                                          </th>
                                          <th width="65px">
                                            Action
                                          </th>
                                        </tr>
                                      </thead>
                                      <tr id="x2_tr_no" style="display: none;"><td colspan="3"><b>No Topic of Interests</b></td></tr>
                                      <script type="text/javascript">
                                        OAT.MSG.attach(OAT, "PAGE_LOADED", function (){pfShowRows("x2", '<?php print(str_replace("\n", "\\n", $_xml->interests)); ?>', ["\n", ";"], function(prefix, val1, val2){TBL.createRow(prefix, null, {fld_1: {value: val1, className: '_validate_ _url_ _canEmpty_'}, fld_2: {value: val2}});});});
                                      </script>
                                    </table>
                                  </td>
                                  <td valign="top" nowrap="nowrap">
                                    <span class="button pointer" onclick="TBL.createRow('x2', null, {fld_1: {className: '_validate_ _url_ _canEmpty_'}, fld_2: {}});"><img class="button" src="/ods/images/icons/add_16.png" border="0" alt="Add Row" title="Add Row" /> Add</span>
                                    <select name="pf_acl_interests" id="pf_acl_interests">
                                      <?php
                                        for ($N = 0; $N < count ($ACL); $N += 2)
                                          print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->interests) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                      ?>
                                    </select>
                                  </td>
                                </tr>
                              </table>
                        </td>
                      </tr>
                      <tr>
                        <th>
                              <label for="pf_foaf">Resource URI indicating thing of interest</label>
                        </th>
                            <td>
                              <table>
                                <tr>
                                  <td width="600px" style="padding: 0px;">
                                    <table id="x3_tbl" class="listing">
                                      <thead>
                                        <tr class="listing_header_row">
                                          <th>
                                            URL
                                          </th>
                                          <th>
                                            Label
                                          </th>
                                          <th width="65px">
                                            Action
                                          </th>
                                        </tr>
                                      </thead>
                                      <tr id="x3_tr_no" style="display: none;"><td colspan="3"><b>No Thing of Interests</b></td></tr>
                                      <script type="text/javascript">
                                        OAT.MSG.attach(OAT, "PAGE_LOADED", function (){pfShowRows("x3", '<?php print(str_replace("\n", "\\n", $_xml->topicInterests)); ?>', ["\n", ";"], function(prefix, val1, val2){TBL.createRow(prefix, null, {fld_1: {value: val1, className: '_validate_ _url_ _canEmpty_'}, fld_2: {value: val2}});});});
                                      </script>
                                    </table>
                                  </td>
                                  <td valign="top" nowrap="nowrap">
                                    <span class="button pointer" onclick="TBL.createRow('x3', null, {fld_1: {className: '_validate_ _url_ _canEmpty_'}, fld_2: {}});"><img class="button" src="/ods/images/icons/add_16.png" border="0" alt="Add Row" title="Add Row" /> Add</span>
                                    <select name="pf_acl_topicInterests" id="pf_acl_topicInterests">
                                      <?php
                                        for ($N = 0; $N < count ($ACL); $N += 2)
                                          print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->topicInterests) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                      ?>
                                    </select>
                                  </td>
                                </tr>
                              </table>
                        </td>
                      </tr>
                          <tr>
                            <th>
                              <label for="pf_photoContent">Upload Photo</label>
                            </th>
                            <td nowrap="1" class="listing_col">
                              <input type="file" name="pf_photoContent" id="pf_photoContent"onblur="javascript: getFileName(this.form, this, this.form.pf_photo);">
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_photo">Photo</label>
                            </th>
                            <td nowrap="1" class="listing_col">
                              <input type="text" name="pf_photo" id="pf_photo" value="<?php print($_xml->photo); ?>" style="width: 400px;" >
                              <select name="pf_acl_photo" id="pf_acl_photo">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->photo) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_audioContent">Upload Audio</label>
                            </th>
                            <td nowrap="1" class="listing_col">
                              <input type="file" name="pf_audioContent" id="pf_audioContent"onblur="javascript: getFileName(this.form, this, this.form.pf_audio);">
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_audio">Audio</label>
                            </th>
                            <td nowrap="1" class="listing_col">
                              <input type="text" name="pf_audio" id="pf_audio"value="<?php print($_xml->audio); ?>" style="width: 400px;" >
                              <select name="pf_acl_audio" id="pf_acl_audio">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->audio) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_appSetting">Show &lt;a&gt;++ links</label>
                            </th>
                            <td>
                              <select name="pf_appSetting" id="pf_appSetting">
                                <?php
                                  $X = array ("0", "disabled", "1", "click", "2", "hover");
                                  for ($N = 0; $N < count ($X); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $X[$N], ((strcmp($X[$N], $_xml->appSetting) == 0) ? "selected=\"selected\"" : ""), $X[$N+1]);
                                ?>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_set_0_1">Set access for all fields as </label>
                            </th>
                            <td>
                              <select name="pf_set_0_1" id="pf_set_0_1" value="0" class="dummy" onchange="javascript: pfSetACLSelects (this)">
                                <option value="0">*no change*</option>
                                <option value="1">public</option>
                                <option value="2">friends</option>
                                <option value="3">private</option>
                              </select>
                            </td>
                          </tr>
                    </table>
                  </div>

                      <div id="pf_page_0_2" class="tabContent" style="display:none;">
                    <table class="form" cellspacing="5">
                      <tr>
                        <th width="30%">
                          <label for="pf_homecountry">Country</label>
                        </th>
                            <td>
                          <select name="pf_homecountry" id="pf_homecountry" onchange="javascript: return updateState('pf_homecountry', 'pf_homestate');" style="width: 220px;">
                            <option></option>
                            <?php
                              for ($N = 1; $N <= count ($_countries); $N += 1)
                                print sprintf("<option %s>%s</option>", ((strcmp($_countries[$N], $_xml->homeCountry) == 0) ? "selected=\"selected\"" : ""), $_countries[$N]);
                            ?>
                          </select>
                              <select name="pf_acl_homeCountry" id="pf_acl_homeCountry">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->homeCountry) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_homestate">State/Province</label>
                        </th>
                        <td>
                          <span id="span_pf_homestate">
                            <script type="text/javascript">
                                  OAT.MSG.attach(OAT, "PAGE_LOADED", function (){updateState("pf_homecountry", "pf_homestate", "<?php print($_xml->homeState); ?>");});
                            </script>
                          </span>
                              <select name="pf_acl_homeState" id="pf_acl_homeState">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->homeState) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_homecity">City/Town</label>
                        </th>
                        <td>
                              <input type="text" name="pf_homecity" value="<?php print($_xml->homeCity); ?>" id="pf_homecity" style="width: 216px;" />
                              <select name="pf_acl_homeCity" id="pf_acl_homeCity">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->homeCity) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_homecode">Zip/Postal Code</label>
                        </th>
                        <td>
                              <input type="text" name="pf_homecode" value="<?php print($_xml->homeCode); ?>" id="pf_homecode" style="width: 216px;"/>
                              <select name="pf_acl_homeCode" id="pf_acl_homeCode">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->homeCode) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_homeaddress1">Address1</label>
                        </th>
                            <td>
                              <input type="text" name="pf_homeaddress1" value="<?php print($_xml->homeAddress1); ?>" id="pf_homeaddress1" style="width: 216px;" />
                              <select name="pf_acl_homeAddress1" id="pf_acl_homeAddress1">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->homeAddress1) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_homeaddress2">Address2</label>
                        </th>
                            <td>
                              <input type="text" name="pf_homeaddress2" value="<?php print($_xml->homeAddress2); ?>" id="pf_homeaddress2" style="width: 216px;" />
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_homeTimezone">Time-Zone</label>
                        </th>
                        <td>
                              <select name="pf_homeTimezone" id="pf_homeTimezone" style="width: 114px;" >
                            <?php
                              for ($N = -12; $N <= 12; $N += 1)
                                print sprintf("<option value=\"%d\" %s>GMT %d:00</option>", $N, (($N == $_xml->homeTimezone) ? "selected=\"selected\"" : ""), $N);
                            ?>
                          </select>
                              <select name="pf_acl_homeTimezone" id="pf_acl_homeTimezone">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->homeTimezone) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_homelat">Latitude</label>
                        </th>
                            <td>
                              <input type="text" name="pf_homelat" value="<?php print($_xml->homeLatitude); ?>" id="pf_homelat" style="width: 110px;" />
                              <select name="pf_acl_homeLatitude" id="pf_acl_homeLatitude">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->homeLatitude) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        <td>
                      <tr>
                      <tr>
                        <th>
                          <label for="pf_homelng">Longitude</label>
                        </th>
                        <td>
                              <input type="text" name="pf_homelng" value="<?php print($_xml->homeLongitude); ?>" id="pf_homelng" style="width: 110px;" />
                              <label>
                                <input type="checkbox" name="pf_homeDefaultMapLocation" id="pf_homeDefaultMapLocation" onclick="javascript: setDefaultMapLocation('home', 'business');" />
                                Default Map Location
                              </label>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_homePhone">Phone</label>
                        </th>
                        <td>
                              <input type="text" name="pf_homePhone" value="<?php print($_xml->homePhone); ?>" id="pf_homePhone" style="width: 110px;" />
                              <b>Ext.</b>
                              <input type="text" name="pf_homePhoneExt" value="<?php print($_xml->homePhoneExt); ?>" id="pf_homePhoneExt" style="width: 40px;" />
                              <select name="pf_acl_homePhone" id="pf_acl_homePhone">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->homePhone) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_homeMobile">Mobile</label>
                        </th>
                        <td>
                              <input type="text" name="pf_homeMobile" value="<?php print($_xml->homeMobile); ?>" id="pf_homeMobile" style="width: 110px;" />
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_set_0_2">Set access for all fields as </label>
                            </th>
                            <td>
                              <select name="pf_set_0_2" id="pf_set_0_2" value="0" class="dummy" onchange="javascript: pfSetACLSelects (this)">
                                <option value="0">*no change*</option>
                                <option value="1">public</option>
                                <option value="2">friends</option>
                                <option value="3">private</option>
                              </select>
                        </td>
                      </tr>
                    </table>
                  </div>

                      <div id="pf_page_0_3" class="tabContent" style="display:none;">
                        <input type="hidden" name="c_nick" value="<?php print($_xml->nickName); ?>" id="c_nick" />
                        <table class="form" cellspacing="5">
                          <tr>
                            <td width="600px">
                              <table id="x4_tbl" class="listing">
                                <thead>
                                  <tr class="listing_header_row">
                                    <th>
                                      Select from Service List ot Type New One
                                    </th>
                                    <th>
                                      Member Home Page URI
                                    </th>
                                    <th width="65px">
                                      Action
                                    </th>
                                  </tr>
                                </thead>
                                <tr id="x4_tr_no" style="display: none;"><td colspan="3"><b>No Services</b></td></tr>
                                <script type="text/javascript">
                                  OAT.MSG.attach(OAT, "PAGE_LOADED", function (){pfShowOnlineAccounts("x4", "P", function(prefix, val0, val1, val2){TBL.createRow(prefix, null, {id: val0, fld_1: {mode: 10, value: val1}, fld_2: {value: val2, className: '_validate_ _uri_ _canEmpty_'}});});});
                                </script>
                              </table>
                            </td>
                            <td valign="top" nowrap="1">
                              <span class="button pointer" onclick="TBL.createRow('x4', null, {fld_1: {mode: 10}, fld_2: {className: '_validate_ _uri_ _canEmpty_'}});"><img class="button" src="/ods/images/icons/add_16.png" border="0" alt="Add Row" title="Add Row" /> Add</span>
                            </td>
                          </tr>
                        </table>
                      </div>

                      <div id="pf_page_0_4" class="tabContent" style="display:none;">
                        <table id="x6_tbl" class="form" cellspacing="5">
                          <tr>
                            <th width="30%">
                              <label for="pf_icq">ICQ</label>
                            </th>
                            <td colspan="2">
                              <input type="text" name="pf_icq" value="<?php print($_xml->icq); ?>" id="pf_icq" style="width: 220px;" />
                              <select name="pf_acl_icq" id="pf_acl_icq">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->icq) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_skype">Skype</label>
                            </th>
                            <td colspan="2">
                              <input type="text" name="pf_skype" value="<?php print($_xml->skype); ?>" id="pf_skype" style="width: 220px;" />
                              <select name="pf_acl_skype" id="pf_acl_skype">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->skype) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_yahoo">Yahoo</label>
                            </th>
                            <td colspan="2">
                              <input type="text" name="pf_yahoo" value="<?php print($_xml->yahoo); ?>" id="pf_yahoo" style="width: 220px;" />
                              <select name="pf_acl_yahoo" id="pf_acl_yahoo">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->yahoo) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_aim">AIM</label>
                            </th>
                            <td colspan="2">
                              <input type="text" name="pf_aim" value="<?php print($_xml->aim); ?>" id="pf_aim" style="width: 220px;" />
                              <select name="pf_acl_aim" id="pf_acl_aim">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->aim) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_msn">MSN</label>
                            </th>
                            <td colspan="2">
                              <input type="text" name="pf_msn" value="<?php print($_xml->msn); ?>" id="pf_msn" style="width: 220px;" />
                              <select name="pf_acl_msn" id="pf_acl_msn">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->msn) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th>Add other services</th>
                            <td>
                              <span class="button pointer" onclick="TBL.createRow('x6', null, {fld_1: {}, fld_2: {cssText: 'width: 220px;'}});"><img class="button" src="/ods/images/icons/add_16.png" border="0" alt="Add Row" title="Add Row" /> Add</span>
                            </td>
                            <td width="40%">
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_set_0_4">Set access for all fields as </label>
                            </th>
                            <td colspan="2">
                              <select name="pf_set_0_4" id="pf_set_0_4" value="0" class="dummy" onchange="javascript: pfSetACLSelects (this)">
                                <option value="0">*no change*</option>
                                <option value="1">public</option>
                                <option value="2">friends</option>
                                <option value="3">private</option>
                              </select>
                            </td>
                          </tr>
                          <script type="text/javascript">
                            OAT.MSG.attach(OAT, "PAGE_LOADED", function (){pfShowRows("x6", '<?php print(str_replace("\n", "\\n", $_xml->messaging)); ?>', ["\n", ";"], function(prefix, val1, val2){TBL.createRow(prefix, null, {fld_1: {value: val1}, fld_2: {value: val2, cssText: 'width: 220px;'}});});});
                          </script>
                        </table>
                      </div>

                      <div id="pf_page_0_5" class="tabContent" style="display:none;">
                        <table class="form" cellspacing="5">
                          <tr>
                            <td width="600px">
                              <table id="x5_tbl" class="listing">
                                <thead>
                                  <tr class="listing_header_row">
                                    <th width="15%">
                                      Event
                                    </th>
                                    <th width="15%">
                                      Date
                                    </th>
                                    <th>
                                      Place
                                    </th>
                                    <th width="65px">
                                      Action
                                    </th>
                                  </tr>
                                </thead>
                                <tr id="x5_tr_no" style="display: none;"><td colspan="4"><b>No Biographical Events</b></td></tr>
                                <script type="text/javascript">
                                  OAT.MSG.attach(OAT, "PAGE_LOADED", function (){pfShowBioEvents("x5", function(prefix, val0, val1, val2, val3){TBL.createRow(prefix, null, {id: val0, fld_1: {mode: 11, value: val1}, fld_2: {value: val2}, fld_3: {value: val3}});});});
                                </script>
                              </table>
                            </td>
                            <td valign="top" nowrap="1">
                              <span class="button pointer" onclick="TBL.createRow('x5', null, {fld_1: {mode: 11}, fld_2: {}, fld_3: {}});"><img class="button" src="/ods/images/icons/add_16.png" border="0" alt="Add Row" title="Add Row" /> Add</span>
                            </td>
                          </tr>
                        </table>
                      </div>

                      <?php
                      if ($_formTab == 0)
                      {
                        if ($_formSubtab == 6)
                        {
                      ?>
                      <div id="pf_page_0_6" class="tabContent" style="display:none;">
                        <h3>Favorites</h3>
                        <?php
                          if ($_formMode == "")
                          {
                        ?>
                        <div id="pf06_list">
                          <div style="padding: 0 0 0.5em 0;">
                            <span onclick="javascript: $('formMode').value = 'new'; $('page_form').submit();" class="button pointer"><img class="button" border="0" title="Add 'Favorite'" alt="Add 'Favorite'" src="/ods/images/icons/add_16.png"> Add</span>
                          </div>
                      	  <table id="pf06_tbl" class="listing">
                                <thead>
                                  <tr class="listing_header_row">
                        		    <th>Label</th>
                        		    <th>URI</th>
                        		    <th width="1%" nowrap="nowrap">Action</th>
                                  </tr>
                                </thead>
                      	    <tbody id="pf06_tbody">
                                  <script type="text/javascript">
                                    OAT.MSG.attach(OAT, "PAGE_LOADED", function (){pfShowFavorites();});
                                  </script>
                                </tbody>
                              </table>
                        </div>
                        <?php
                          }
                          else
                          {
                            print sprintf("<input type=\"hidden\" id=\"pf06_id\" name=\"pf06_id\" value=\"%s\" />", (isset ($_REQUEST['pf06_id'])) ? $_REQUEST['pf06_id'] : "0");
                        ?>
                        <div id="pf06_form">
                          <table class="form" cellspacing="5">
                            <tr>
                              <th width="25%">
                                Label (*)
                              </th>
                              <td>
                                <input type="text" name="pf06_label" id="pf06_label" value="" class="_validate_" style="width: 400px;">
                            </td>
                            </tr>
                            <tr>
                              <th>
                                External URI
                              </th>
                              <td>
                                <input type="text" name="pf06_uri" id="pf06_uri" value="" class="_validate_ _url_ _canEmpty_" style="width: 400px;">
                            </td>
                          </tr>
                            <tr>
                              <th valign="top">
                                Item Properties
                              </th>
                  		        <td width="800px">
                                <table id="r_tbl" class="listing">
                                  <tbody id="r_tbody">
                                  </tbody>
                        </table>
                              </td>
                            </tr>
                          </table>
                          <script type="text/javascript">
                            OAT.MSG.attach(OAT, "PAGE_LOADED", function (){pfShowFavorite();});
                          </script>
                          <div class="footer">
                            <input type="submit" name="pf_cancel2" value="Cancel" onclick="needToConfirm = false; "/>
                            <input type="submit" name="pf_update06" value="Save" onclick="myBeforeSubmit(); return validateInputs(this, 'pf06');"/>
                          </div>
                      </div>
                      <?php
                          }
                        ?>
                      </div>
                      <?php
                        }
                        else if ($_formSubtab == 7)
                        {
                      ?>
                      <div id="pf_page_0_7" class="tabContent" style="display:none;">
                        <h3>Creator Of</h3>
                        <?php
                          if ($_formMode == "")
                          {
                        ?>
                        <div id="pf07_list">
                          <div style="padding: 0 0 0.5em 0;">
                            <span onclick="javascript: $('formMode').value = 'new'; $('page_form').submit();" class="button pointer"><img class="button" border="0" title="Add Creator Of" alt="Add Creator Of" src="/ods/images/icons/add_16.png"> Add</span>
                          </div>
                      	  <table id="pf07_tbl" class="listing">
                      	    <thead>
                      	      <tr class="listing_header_row">
                        		    <th>Property</th>
                        		    <th>Description</th>
                        		    <th width="1%" nowrap="nowrap">Action</th>
                      	      </tr>
                            </thead>
                      	    <tbody id="pf07_tbody">
                              <script type="text/javascript">
                                OAT.MSG.attach(OAT, "PAGE_LOADED", function (){pfShowMades();});
                              </script>
                      	    </tbody>
                          </table>
                        </div>
                        <?php
                          }
                          else
                          {
                              print sprintf("<input type=\"hidden\" id=\"pf07_id\" name=\"pf07_id\" value=\"%s\" />", (isset ($_REQUEST['pf07_id'])) ? $_REQUEST['pf07_id'] : "0");
                        ?>
                        <div id="pf07_form">
                          <table class="form" cellspacing="5">
                            <tr>
                              <th width="25%">
                                Property (*)
                              </th>
                              <td id="if_opt">
                                <script type="text/javascript">
                                  function p_init ()
                                  {
                                    var fld = new OAT.Combolist([]);
                                    fld.input.name = 'pf07_property';
                                    fld.input.id = fld.input.name;
                                    fld.input.style.width = "400px";
                                    $("if_opt").appendChild(fld.div);
                                    fld.addOption("foaf:made");
                                    fld.addOption("dc:creator");
                                    fld.addOption("sioc:owner");
                                  }
                                  OAT.MSG.attach(OAT, "PAGE_LOADED", p_init)
                                </script>
                              </td>
                            </tr>
                            <tr>
                              <th>
                                URI
                              </th>
                              <td>
                                <input type="text" name="pf07_url" id="pf07_url" value="" class="_validate_ _url_ _canEmpty_" style="width: 400px;">
                              </td>
                            </tr>
                            <tr>
                              <th>
                                Description (*)
                              </th>
                              <td>
                                <textarea name="pf07_description" id="pf07_description" style="width: 400px;"></textarea>
                              </td>
                            </tr>
                            <tr>
                              <td />
                              <td>
  		                          <b>Note: The fields designated with '*' will be fetched from the source document if empty</b>
                              </td>
                            </tr>
                          </table>
                          <script type="text/javascript">
                            OAT.MSG.attach(OAT, "PAGE_LOADED", function (){pfShowMade();});
                          </script>
                          <div class="footer">
                            <input type="submit" name="pf_cancel2" value="Cancel" onclick="needToConfirm = false; "/>
                            <input type="submit" name="pf_update07" value="Save" onclick="needToConfirm = false; return validateInputs(this, 'pf07');"/>
                          </div>
                        </div>
                        <?php
                          }
                        ?>
                      </div>
                      <?php
                        }
                        else if ($_formSubtab == 8)
                        {
                      ?>
                      <div id="pf_page_0_8" class="tabContent" style="display:none;">
                        <h3>My Offers</h3>
                        <?php
                          if ($_formMode == "")
                          {
                        ?>
                        <div id="pf08_list">
                          <div style="padding: 0 0 0.5em 0;">
                            <span onclick="javascript: $('formMode').value = 'new'; $('page_form').submit();" class="button pointer"><img class="button" border="0" title="Add Offer" alt="Add Offer" src="/ods/images/icons/add_16.png"> Add</span>
                          </div>
                      	  <table id="pf08_tbl" class="listing">
                      	    <thead>
                      	      <tr class="listing_header_row">
                        		    <th>Name</th>
                        		    <th>Description</th>
                        		    <th width="1%" nowrap="nowrap">Action</th>
                      	      </tr>
                            </thead>
                      	    <tbody id="pf08_tbody">
                              <script type="text/javascript">
                                OAT.MSG.attach(OAT, "PAGE_LOADED", function (){pfShowOffers();});
                              </script>
                      	    </tbody>
                          </table>
                        </div>
                        <?php
                          }
                          else
                          {
                            print sprintf("<input type=\"hidden\" id=\"pf08_id\" name=\"pf08_id\" value=\"%s\" />", (isset ($_REQUEST['pf08_id'])) ? $_REQUEST['pf08_id'] : "0");
                        ?>
                        <div id="pf08_form">
                          <table class="form" cellspacing="5">
                            <tr>
                              <th width="25%">
                                Name
                              </th>
                              <td>
                                <input type="text" name="pf08_name" id="pf08_name" value="" class="_validate_" style="width: 400px;">
                              </td>
                            </tr>
                            <tr>
                              <th>
                                Comment
                              </th>
                              <td>
                                <textarea name="pf08_comment" id="pf08_comment" class="_validate_ _canEmpty_" style="width: 400px;"></textarea>
                              </td>
                            </tr>
                  				  <tr>
                  				    <th valign="top">
                  		          Products
                  		        </th>
                  		        <td width="800px">
                                <table id="ol_tbl" class="listing">
                                  <tbody id="ol_tbody">
                                  </tbody>
                                </table>
                                <input type="hidden" id="ol_no" name="ol_no" value="1" />
                              </td>
                            </tr>
                          </table>
                          <script type="text/javascript">
                            OAT.MSG.attach(OAT, "PAGE_LOADED", function (){pfShowOffer();});
                          </script>
                          <div class="footer">
                            <input type="submit" name="pf_cancel2" value="Cancel" onclick="needToConfirm = false;"/>
                            <input type="submit" name="pf_update08" value="Save" onclick="myBeforeSubmit(); return validateInputs(this, 'pf08');"/>
                          </div>
                        </div>
                        <?php
                          }
                        ?>
                      </div>
                      <?php
                        }
                        else if ($_formSubtab == 9)
                        {
                      ?>
                      <div id="pf_page_0_9" class="tabContent" style="display:none;">
                        <h3>Offers I Seek</h3>
                        <?php
                          if ($_formMode == "")
                          {
                        ?>
                        <div id="pf09_list">
                          <div style="padding: 0 0 0.5em 0;">
                            <span onclick="javascript: $('formMode').value = 'new'; $('page_form').submit();" class="button pointer"><img class="button" border="0" title="Add Seek" alt="Add Seek" src="/ods/images/icons/add_16.png"> Add</span>
                          </div>
                      	  <table id="pf09_tbl" class="listing">
                      	    <thead>
                      	      <tr class="listing_header_row">
                        		    <th>Name</th>
                        		    <th>Description</th>
                        		    <th width="1%" nowrap="nowrap">Action</th>
                      	      </tr>
                            </thead>
                      	    <tbody id="pf09_tbody">
                              <script type="text/javascript">
                                OAT.MSG.attach(OAT, "PAGE_LOADED", function (){pfShowSeeks();});
                              </script>
                      	    </tbody>
                          </table>
                        </div>
                        <?php
                          }
                          else
                          {
                            print sprintf("<input type=\"hidden\" id=\"pf09_id\" name=\"pf09_id\" value=\"%s\" />", (isset ($_REQUEST['pf09_id'])) ? $_REQUEST['pf09_id'] : "0");
                        ?>
                        <div id="pf09_form">
                          <table class="form" cellspacing="5">
                            <tr>
                              <th width="25%">
                                Name
                              </th>
                              <td>
                                <input type="text" name="pf09_name" id="pf09_name" value="" class="_validate_" style="width: 400px;">
                              </td>
                            </tr>
                            <tr>
                              <th>
                                Comment
                              </th>
                              <td>
                                <textarea name="pf09_comment" id="pf09_comment" class="_validate_ _canEmpty_" style="width: 400px;"></textarea>
                              </td>
                            </tr>
                  				  <tr>
                  				    <th valign="top">
                  		          Products
                  		        </th>
                  		        <td width="800px">
                                <table id="wl_tbl" class="listing">
                                  <tbody id="wl_tbody">
                                  </tbody>
                                </table>
                                <input type="hidden" id="wl_no" name="wl_no" value="1" />
                              </td>
                            </tr>
                          </table>
                          <script type="text/javascript">
                            OAT.MSG.attach(OAT, "PAGE_LOADED", function (){pfShowSeek();});
                          </script>
                          <div class="footer">
                            <input type="submit" name="pf_cancel2" value="Cancel" onclick="needToConfirm = false;"/>
                            <input type="submit" name="pf_update09" value="Save" onclick="myBeforeSubmit(); return validateInputs(this, 'pf09');"/>
                          </div>
                        </div>
                        <?php
                          }
                        ?>
                      </div>
                      <?php
                        }
                        else
                        {
                      ?>
                      <div class="footer">
                        <input type="submit" name="pf_cancel" value="Cancel" onclick="needToConfirm = false;"/>
                        <input type="submit" name="pf_update" value="Save" onclick="myBeforeSubmit(); return myValidateInputs(this);"/>
                        <input type="submit" name="pf_next" value="Save & Next" onclick="myBeforeSubmit(); return myValidateInputs(this);"/>
                      </div>
                      <?php
                        }
                      }
                      ?>
                    </div>
                  </div>

                  <div id="pf_page_1" class="tabContent" style="display:none;">
                    <ul id="pf_tabs_1" class="tabs">
                      <li id="pf_tab_1_0" title="Main">Main</li>
                      <li id="pf_tab_1_1" title="Address">Address</li>
                      <li id="pf_tab_1_2" title="Online Accounts">Online Accounts</li>
                      <li id="pf_tab_1_3" title="Messaging Services">Messaging Services</li>
                    </ul>
                    <div style="min-height: 180px; min-width: 650px; border-top: 1px solid #aaa; margin: -13px 5px 5px 5px;">
                      <div id="pf_page_1_0" class="tabContent" style="display:none;">
                    <table class="form" cellspacing="5">
                      <tr>
                        <th width="30%">
                          <label for="pf_businessIndustry">Industry</label>
                        </th>
                        <td>
                              <select name="pf_businessIndustry" id="pf_businessIndustry" style="width: 220px;">
                            <option></option>
                            <?php
                              for ($N = 1; $N <= count ($_industries); $N += 1)
                                print sprintf("<option %s>%s</option>", ((strcmp($_industries[$N], $_xml->businessIndustry) == 0) ? "selected=\"selected\"" : ""), $_industries[$N]);
                            ?>
                          </select>
                              <select name="pf_acl_businessIndustry" id="pf_acl_businessIndustry">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessIndustry) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_businessOrganization">Organization</label>
                        </th>
                        <td>
                              <input type="text" name="pf_businessOrganization" value="<?php print($_xml->businessOrganization); ?>" id="pf_businessOrganization" style="width: 216px;" />
                              <select name="pf_acl_businessOrganization" id="pf_acl_businessOrganization">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessOrganization) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_businessHomePage">Organization Home Page</label>
                        </th>
                            <td>
                              <input type="text" name="pf_businessHomePage" value="<?php print($_xml->businessHomePage); ?>" id="pf_businessNetwork" style="width: 216px;" />
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_businessJob">Job Title</label>
                        </th>
                        <td>
                              <input type="text" name="pf_businessJob" value="<?php print($_xml->businessJob); ?>" id="pf_businessJob" style="width: 216px;" />
                              <select name="pf_acl_businessJob" id="pf_acl_businessJob">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessJob) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                              <label for="pf_businessRegNo">VAT Reg number (EU only) or Tax ID</label>
                            </th>
                            <td>
                              <input type="text" name="pf_businessRegNo" value="<?php print($_xml->businessRegNo); ?>" id="pf_businessRegNo" style="width: 216px;" />
                              <select name="pf_acl_businessRegNo" id="pf_acl_businessRegNo">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessRegNo) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_businessCareer">Career / Organization Status</label>
                            </th>
                            <td>
                              <select name="pf_businessCareer" id="pf_businessCareer" style="width: 220px;">
                                <option></option>
                                <?php
                                  $X = array ("Job seeker-Permanent", "Job seeker-Temporary", "Job seeker-Temp/perm", "Employed-Unavailable", "Employer", "Agency", "Resourcing supplier");
                                  for ($N = 0; $N < count ($X); $N += 1)
                                    print sprintf("<option %s>%s</option>", ((strcmp($X[$N], $_xml->businessCareer) == 0) ? "selected=\"selected\"" : ""), $X[$N]);
                                ?>
                              </select>
                              <select name="pf_acl_businessCareer" id="pf_acl_businessCareer">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessCareer) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_businessEmployees">No. of Employees</label>
                            </th>
                            <td>
                              <select name="pf_businessEmployees" id="pf_businessEmployees" style="width: 220px;">
                                <option></option>
                                <?php
                                  $X = array ("1-100", "101-250", "251-500", "501-1000", ">1000");
                                  for ($N = 0; $N < count ($X); $N += 1)
                                    print sprintf("<option %s>%s</option>", ((strcmp($X[$N], $_xml->businessEmployees) == 0) ? "selected=\"selected\"" : ""), $X[$N]);
                                ?>
                              </select>
                              <select name="pf_acl_businessEmployees" id="pf_acl_businessEmployees">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessEmployees) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_businessVendor">Are you a technology vendor</label>
                            </th>
                            <td>
                              <select name="pf_businessVendor" id="pf_businessVendor" style="width: 220px;">
                                <option></option>
                                <?php
                                  $X = array ("Not a Vendor", "Vendor", "VAR", "Consultancy");
                                  for ($N = 0; $N < count ($X); $N += 1)
                                    print sprintf("<option %s>%s</option>", ((strcmp($X[$N], $_xml->businessVendor) == 0) ? "selected=\"selected\"" : ""), $X[$N]);
                                ?>
                              </select>
                              <select name="pf_acl_businessVendor" id="pf_acl_businessVendor">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessVendor) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_businessService">If so, what technology and/or service do you provide?</label>
                            </th>
                            <td>
                              <select name="pf_businessService" id="pf_businessService" style="width: 220px;">
                                <option></option>
                                <?php
                                  $X = array ("Enterprise Data Integration", "Business Process Management", "Other");
                                  for ($N = 0; $N < count ($X); $N += 1)
                                    print sprintf("<option %s>%s</option>", ((strcmp($X[$N], $_xml->businessService) == 0) ? "selected=\"selected\"" : ""), $X[$N]);
                                ?>
                              </select>
                              <select name="pf_acl_businessService" id="pf_acl_businessService">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessService) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_businessOther">Other Technology service</label>
                            </th>
                            <td>
                              <input type="text" name="pf_businessOther" value="<?php print($_xml->businessOther); ?>" id="pf_businessOther" style="width: 216px;" />
                              <select name="pf_acl_businessOther" id="pf_acl_businessOther">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessOther) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_businessNetwork">Importance of OpenLink Network for you</label>
                            </th>
                            <td>
                              <input type="text" name="pf_businessNetwork" value="<?php print($_xml->businessNetwork); ?>" id="pf_businessNetwork" style="width: 216px;" />
                              <select name="pf_acl_businessNetwork" id="pf_acl_businessNetwork">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessNetwork) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_businessResume">Resume</label>
                            </th>
                            <td>
                              <textarea name="pf_businessResume" id="pf_businessResume" style="width: 400px;"><?php print($_xml->businessResume); ?></textarea>
                              <select name="pf_acl_businessResume" id="pf_acl_businessResume">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessResume) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_set_1_0">Set access for all fields as </label>
                            </th>
                            <td>
                              <select name="pf_set_1_0" id="pf_set_1_0" value="0" class="dummy" onchange="javascript: pfSetACLSelects (this)">
                                <option value="0">*no change*</option>
                                <option value="1">public</option>
                                <option value="2">friends</option>
                                <option value="3">private</option>
                              </select>
                            </td>
                          </tr>
                        </table>
                      </div>

                      <div id="pf_page_1_1" class="tabContent" style="display:none;">
                        <table class="form" cellspacing="5">
                          <tr>
                            <th>
                          <label for="pf_businesscountry">Country</label>
                        </th>
                        <td>
                          <select name="pf_businesscountry" id="pf_businesscountry" onchange="javascript: return updateState('pf_businesscountry', 'pf_businessstate');" style="width: 220px;">
                            <option></option>
                            <?php
                              for ($N = 1; $N <= count ($_countries); $N += 1)
                                print sprintf("<option %s>%s</option>", ((strcmp($_countries[$N], $_xml->businessCountry) == 0) ? "selected=\"selected\"" : ""), $_countries[$N]);
                            ?>
                          </select>
                              <select name="pf_acl_businessCountry" id="pf_acl_businessCountry">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessCountry) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_businessstate">State/Province</label>
                        </th>
                        <td>
                          <span id="span_pf_businessstate">
                            <script type="text/javascript">
                                  OAT.MSG.attach(OAT, "PAGE_LOADED", function (){updateState("pf_businesscountry", "pf_businessstate", "<?php print($_xml->businessState); ?>");});
                            </script>
                          </span>
                              <select name="pf_acl_businessState" id="pf_acl_businessState">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessState) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_businesscity">City/Town</label>
                        </th>
                        <td>
                              <input type="text" name="pf_businesscity" value="<?php print($_xml->businessCity); ?>" id="pf_businesscity" style="width: 216px;" />
                              <select name="pf_acl_businessCity" id="pf_acl_businessCity">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessCity) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_businesscode">Zip/Postal Code</label>
                        </th>
                        <td>
                              <input type="text" name="pf_businesscode" value="<?php print($_xml->businessCode); ?>" id="pf_businesscode" style="width: 216px;" />
                              <select name="pf_acl_businessCode" id="pf_acl_businessCode">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessCode) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_businessaddress1">Address1</label>
                        </th>
                        <td>
                              <input type="text" name="pf_businessaddress1" value="<?php print($_xml->businessAddress1); ?>" id="pf_businessaddress1" style="width: 216px;" />
                              <select name="pf_acl_businessAddress1" id="pf_acl_businessAddress1">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessAddress1) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_businessaddress2">Address2</label>
                        </th>
                        <td>
                              <input type="text" name="pf_businessaddress2" value="<?php print($_xml->businessAddress2); ?>" id="pf_businessaddress2" style="width: 216px;" />
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_businessTimezone">Time-Zone</label>
                        </th>
                        <td>
                              <select name="pf_businessTimezone" id="pf_businessTimezone" style="width: 114px;">
                            <?php
                              for ($N = -12; $N <= 12; $N += 1)
                                print sprintf("<option value=\"%d\" %s>GMT %d:00</option>", $N, (($N == $_xml->businessTimezone) ? "selected=\"selected\"": ""), $N);
                            ?>
                          </select>
                              <select name="pf_acl_businessTimezone" id="pf_acl_businessTimezone">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessTimezone) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_businesslat">Latitude</label>
                        </th>
                        <td>
                              <input type="text" name="pf_businesslat" value="<?php print($_xml->businessLatitude); ?>" id="pf_businesslat" style="width: 110px;" />
                              <select name="pf_acl_businessLatitude" id="pf_acl_businessLatitude">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessLatitude) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        <td>
                          </tr>
                      <tr>
                        <th>
                          <label for="pf_businesslng">Longitude</label>
                        </th>
                        <td>
                              <input type="text" name="pf_businesslng" value="<?php print($_xml->businessLongitude); ?>" id="pf_businesslng" style="width: 110px;" />
                              <label>
                                <input type="checkbox" name="pf_businessDefaultMapLocation" id="pf_businessDefaultMapLocation" onclick="javascript: setDefaultMapLocation('business', 'home');" />
                                Default Map Location
                              </label>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_businessPhone">Phone</label>
                        </th>
                        <td>
                              <input type="text" name="pf_businessPhone" value="<?php print($_xml->businessPhone); ?>" id="pf_businessPhone" style="width: 110px;" />
                              <b>Ext.</b>
                              <input type="text" name="pf_businessPhoneExt" value="<?php print($_xml->businessPhoneExt); ?>" id="pf_businessPhoneExt" style="width: 40px;" />
                              <select name="pf_acl_businessPhone" id="pf_acl_businessPhone">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessPhone) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_businessMobile">Mobile</label>
                        </th>
                        <td>
                              <input type="text" name="pf_businessMobile" value="<?php print($_xml->businessMobile); ?>" id="pf_businessMobile" style="width: 110px;" />
                            </td>
                          </tr>
                          <tr>
                            <th>
                              <label for="pf_set_1_1">Set access for all fields as </label>
                            </th>
                            <td>
                              <select name="pf_set_1_1" id="pf_set_1_1" value="0" class="dummy" onchange="javascript: pfSetACLSelects (this)">
                                <option value="0">*no change*</option>
                                <option value="1">public</option>
                                <option value="2">friends</option>
                                <option value="3">private</option>
                              </select>
                        </td>
                      </tr>
                        </table>
                      </div>

                      <div id="pf_page_1_2" class="tabContent" style="display:none;">
                        <table class="form" cellspacing="5">
                      <tr>
                            <td width="600px">
                              <table id="y1_tbl" class="listing">
                                <thead>
                                  <tr class="listing_header_row">
                        <th>
                                      Select from Service List ot Type New One
                        </th>
                        <th>
                                      Member Home Page URI
                        </th>
                                    <th width="65px">
                                      Action
                        </th>
                                  </tr>
                                </thead>
                                <tr id="y1_tr_no" style="display: none;"><td colspan="3"><b>No Services</b></td></tr>
                                <script type="text/javascript">
                                  OAT.MSG.attach(OAT, "PAGE_LOADED", function (){pfShowOnlineAccounts("y1", "B", function(prefix, val0, val1, val2){TBL.createRow(prefix, null, {id: val0, fld_1: {mode: 10, value: val1}, fld_2: {value: val2, className: '_validate_ _uri_ _canEmpty_'}});});});
                                </script>
                              </table>
                            </td>
                            <td valign="top" nowrap="1">
                              <span class="button pointer" onclick="TBL.createRow('y1', null, {fld_1: {mode: 10}, fld_2: {className: '_validate_ _uri_ _canEmpty_'}});"><img class="button" src="/ods/images/icons/add_16.png" border="0" alt="Add Row" title="Add Row" /> Add</span>
                        </td>
                      </tr>
                        </table>
                      </div>

                      <div id="pf_page_1_3" class="tabContent" style="display:none;">
                        <table id="y2_tbl" class="form" cellspacing="5">
                      <tr>
                            <th width="30%">
                              <label for="pf_businessIcq">ICQ</label>
                        </th>
                            <td colspan="2">
                              <input type="text" name="pf_businessIcq" value="<?php print($_xml->businessIcq); ?>" id="pf_icq" style="width: 220px;" />
                              <select name="pf_acl_businessIcq" id="pf_acl_businessIcq">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessIcq) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                              <label for="pf_businessSkype">Skype</label>
                        </th>
                            <td colspan="2">
                              <input type="text" name="pf_businessSkype" value="<?php print($_xml->businessSkype); ?>" id="pf_businessSkype" style="width: 220px;" />
                              <select name="pf_acl_businessSkype" id="pf_acl_businessSkype">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessSkype) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                              <label for="pf_businessYahoo">Yahoo</label>
                        </th>
                            <td colspan="2">
                              <input type="text" name="pf_businessYahoo" value="<?php print($_xml->businessYahoo); ?>" id="pf_businessYahoo" style="width: 220px;" />
                              <select name="pf_acl_businessYahoo" id="pf_acl_businessYahoo">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessYahoo) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                              <label for="pf_businessAim">AIM</label>
                        </th>
                            <td colspan="2">
                              <input type="text" name="pf_businessAim" value="<?php print($_xml->businessAim); ?>" id="pf_businessAim" style="width: 220px;" />
                              <select name="pf_acl_businessAim" id="pf_acl_businessAim">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessAim) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                        </td>
                      </tr>
                      <tr>
                        <th>
                              <label for="pf_businessMsn">MSN</label>
                        </th>
                            <td colspan="2">
                              <input type="text" name="pf_businessMsn" value="<?php print($_xml->businessMsn); ?>" id="pf_businessMsn" style="width: 220px;" />
                              <select name="pf_acl_businessMsn" id="pf_acl_businessMsn">
                                <?php
                                  for ($N = 0; $N < count ($ACL); $N += 2)
                                    print sprintf("<option value=\"%s\" %s>%s</option>", $ACL[$N+1], ((strcmp($ACL[$N+1], $_acl->businessMsn) == 0) ? "selected=\"selected\"" : ""), $ACL[$N]);
                                ?>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th>Add other services</th>
                            <td>
                              <span class="button pointer" onclick="TBL.createRow('y2', null, {fld_1: {}, fld_2: {cssText: 'width: 220px;'}});"><img class="button" src="/ods/images/icons/add_16.png" border="0" alt="Add Row" title="Add Row" /> Add</span>
                            </td>
                            <td width="40%">
                        </td>
                      </tr>
                          <tr>
                            <th>
                              <label for="pf_set_1_3">Set access for all fields as </label>
                            </th>
                            <td>
                              <select name="pf_set_1_3" id="pf_set_1_3" value="0" class="dummy" onchange="javascript: pfSetACLSelects (this)">
                                <option value="0">*no change*</option>
                                <option value="1">public</option>
                                <option value="2">friends</option>
                                <option value="3">private</option>
                              </select>
                            </td>
                          </tr>
                          <script type="text/javascript">
                            OAT.MSG.attach(OAT, "PAGE_LOADED", function (){pfShowRows("y2", '<?php print(str_replace("\n", "\\n", $_xml->businessMessaging)); ?>', ["\n", ";"], function(prefix, val1, val2){TBL.createRow(prefix, null, {fld_1: {value: val1}, fld_2: {value: val2, cssText: 'width: 220px;'}});});});
                          </script>
                    </table>
                  </div>

                      <div class="footer">
                        <input type="submit" name="pf_cancel" value="Cancel" onclick="needToConfirm = false;"/>
                        <input type="submit" name="pf_update" value="Save" onclick="myBeforeSubmit(); return myValidateInputs(this);"/>
                        <input type="submit" name="pf_next" value="Save & Next" onclick="myBeforeSubmit(); return myValidateInputs(this);"/>
                      </div>
                    </div>
                  </div>

                  <div id="pf_page_2" class="tabContent" style="display:none;">
                    <ul id="pf_tabs_2" class="tabs">
                      <li id="pf_tab_2_0" title="Password Settings">Password Settings</li>
                      <li id="pf_tab_2_1" title="Password Recovery">Password Recovery</li>
                      <li id="pf_tab_2_2" title="OpenID">OpenID</li>
                      <li id="pf_tab_2_3" title="Facebook" style="display:none;">Facebook</li>
                      <li id="pf_tab_2_4" title="Limits">Limits</li>
                      <li id="pf_tab_2_5" title="Certificate Generator" style="display:none;">Certificate Generator</li>
                      <li id="pf_tab_2_6" title="X.509 Certificates">X.509 Certificates</li>
                    </ul>
                    <div style="min-height: 180px; min-width: 650px; border-top: 1px solid #aaa; margin: -13px 5px 5px 5px;">
                      <div id="pf_page_2_0" class="tabContent" style="display:none;">
                        <?php
                          print $_xml->noPassword;
                        ?>
                    <table class="form" cellspacing="5">
                      <tr>
                        <td align="center" colspan="2">
                          <span id="pf_change_txt"></span>
                        </td>
                      </tr>
                          <?php
                          if ($_xml->noPassword == '0')
                          {
                          ?>
                      <tr>
                        <th width="30%">
                          <label for="pf_oldPassword">Old Password</label>
                        </th>
                        <td>
                          <input type="password" name="pf_oldPassword" value="" id="pf_oldPassword" />
                        </td>
                      </tr>
                          <?php
                          }
                          ?>
                      <tr>
                            <th width="30%">
                          <label for="pf_newPassword">New Password</label>
                        </th>
                        <td>
                          <input type="password" name="pf_newPassword" value="" id="pf_newPassword" />
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_password">Repeat Password</label>
                        </th>
                        <td>
                          <input type="password" name="pf_newPassword2" value="" id="pf_newPassword2" />
                        </td>
                      </tr>
                      <tr>
                        <th>
                        </th>
                      </tr>
                        </table>
                      </div>
                      <div id="pf_page_2_1" class="tabContent" style="display:none;">
                        <table class="form" cellspacing="5">
                      <tr>
                        <th>
                          <label for="pf_securitySecretQuestion">Secret Question</label>
                        </th>
                        <td id="td_securitySecretQuestion">
                          <script type="text/javascript">
                            function categoryCombo ()
                            {
                              var cc = new OAT.Combolist([], "<?php print($_xml->securitySecretQuestion); ?>");
                              cc.input.name = "pf_securitySecretQuestion";
                              cc.input.id = "pf_securitySecretQuestion";
                              cc.input.style.cssText = "width: 220px;";
                              $("td_securitySecretQuestion").appendChild(cc.div);
                              cc.addOption("");
                              cc.addOption("First Car");
                              cc.addOption("Mothers Maiden Name");
                              cc.addOption("Favorite Pet");
                              cc.addOption("Favorite Sports Team");
                            }
                            OAT.MSG.attach(OAT, "PAGE_LOADED", categoryCombo);
                          </script>
                        </td>
                      </tr>
                      <tr>
                        <th>
                          <label for="pf_securitySecretAnswer">Secret Answer</label>
                        </th>
                        <td>
                          <input type="text" name="pf_securitySecretAnswer" value="<?php print($_xml->securitySecretAnswer); ?>" id="pf_securitySecretAnswer" style="width: 220px;" />
                        </td>
                      </tr>
                        </table>
                      </div>
                      <div id="pf_page_2_2" class="tabContent" style="display:none;">
                        <table class="form" cellspacing="5">
                      <tr>
                        <th>
                              <label for="pf_openID">OpenID URL</label>
                        </th>
                        <td>
                              <input type="text" name="pf_openID" value="<?php print($_xml->securityOpenID); ?>" id="pf_openID" style="width: 220px;" />
                        </td>
                      </tr>
                        </table>
                      </div>
                      <div id="pf_page_2_3" class="tabContent" style="display:none;">
                        <table class="form" cellspacing="5">
                      <tr>
                            <th>
                              Saved Facebook ID
                        </th>
                            <td>
                              <?php
                                if (isset ($_xml->securityFacebookID))
                                {
                                  print ($_xml->securityFacebookName);
                                } else {
                                  print ('not yet');
                                }
                              ?>
                            </td>
                      </tr>
                      <tr>
                        <th>
                        </th>
                        <td>
                              <span id="pf_facebookData" style="min-height: 20px;"></span>
                              <br />
                              <script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php" type="text/javascript"></script>
                              <fb:login-button autologoutlink="true"></fb:login-button>
                        </td>
                      </tr>
                        </table>
                      </div>
                      <div id="pf_page_2_4" class="tabContent" style="display:none;">
                        <table class="form" cellspacing="5">
                      <tr>
                        <th>
                              <label for="pf_securitySiocLimit">SIOC Query Result Limit</label>
                        </th>
                        <td>
                              <input type="text" name="pf_securitySiocLimit" value="<?php print($_xml->securitySiocLimit); ?>" id="pf_securitySiocLimit" />
                        </td>
                      </tr>
                        </table>
                      </div>
                      <?php
                      if (($_formTab == 2) and ($_formSubtab == 5))
                      {
                      ?>
                      <div id="pf_page_2_5" class="tabContent" style="display:none;">
            	          <iframe id="cert" src="/ods/cert.vsp?sid=<?php print($_sid); ?>" width="650" height="270" frameborder="0" scrolling="no">
            	            <p>Your browser does not support iframes.</p>
            	          </iframe>
                      </div>
                      <?php
                      }
                      else if (($_formTab == 2) and ($_formSubtab == 6))
                      {
                      ?>
                      <div id="pf_page_2_6" class="tabContent" style="display:none;">
                        <h3>X.509 Certificates</h3>
                        <?php
                          if ($_formMode == '')
                          {
                        ?>
                        <div id="pf26_list">
                          <div style="padding: 0 0 0.5em 0;">
                            <span onclick="javascript: $('formMode').value = 'new'; $('page_form').submit();" class="button pointer"><img class="button" border="0" title="Add 'Fovorite'" alt="Add 'Fovorite'" src="/ods/images/icons/add_16.png"> Add</span>
                          </div>
                      	  <table id="pf26_tbl" class="listing">
                      	    <thead>
                      	      <tr class="listing_header_row">
                        		    <th>Subject</th>
                          		  <th>Created</th>
                          		  <th>Fingerprint</th>
                          		  <th>Login enabled</th>
                        		    <th width="1%" nowrap="nowrap">Action</th>
                      </tr>
                            </thead>
                      	    <tbody id="pf26_tbody">
                              <script type="text/javascript">
                                OAT.MSG.attach(OAT, "PAGE_LOADED", function (){pfShowCertificates();});
                              </script>
                      	    </tbody>
                          </table>
                        </div>
              	      <?php
                          }
                          else
                          {
                            print sprintf("<input type=\"hidden\" id=\"pf26_id\" name=\"pf26_id\" value=\"%s\" />", (isset ($_REQUEST['pf26_id'])) ? $_REQUEST['pf26_id'] : "0");
                        ?>
                        <div id="pf26_form">
                          <table class="form" cellspacing="5">
                            <?php
                            if ($_formMode == 'edit')
              	        {
              	      ?>
                      <tr>
                        <th>
                	    	  Subject
                        </th>
                        <td>
                          		  <span id="pf26_subject"></span>
                    		</td>
                      </tr>
                      <tr>
                        <th>
                	    	  Agent ID
                        </th>
                        <td>
                          		  <span id="pf26_agentID"></span>
                          		</td>
                            </tr>
                            <tr>
                              <th>
                      	    	  Fingerprint
                              </th>
                              <td>
                          		  <span id="pf26_fingerPrint"></span>
                    		</td>
                      </tr>
            	        <?php
            	          }
            	        ?>
                      <tr>
                        <th valign="top">
                                <label for="pf26_certificate">Certificate</label>
                        </th>
                        <td>
                                <textarea name="pf26_certificate" id="pf26_certificate" rows="20" style="width: 560px;"></textarea>
                        </td>
                      </tr>
                      <tr>
                        <th></th>
                        <td>
                          <label>
                                  <input type="checkbox" name="pf26_enableLogin" id="pf26_enableLogin" value="1"/>
                            Enable Automatic WebID Login
                          </label>
                        </td>
                      </tr>
                    </table>
                          <script type="text/javascript">
                            OAT.MSG.attach(OAT, "PAGE_LOADED", function (){pfShowCertificate();});
                          </script>
                          <div class="footer">
                            <input type="submit" name="pf_cancel2" value="Cancel" onclick="needToConfirm = false;"/>
                            <input type="submit" name="pf_update26" value="Save" onclick="needToConfirm = false; return validateInputs(this, 'pf26');"/>
                          </div>
                        </div>
                        <?php
                          }
                        ?>
                      </div>
                      <?php
                        }
                        else
                        {
                      ?>
                    <div class="footer">
                      <input type="submit" name="pf_cancel" value="Cancel" onclick="needToConfirm = false;"/>
                        <?php
                        if (($_formTab == 2) and ($_formSubtab == 3) and isset($_xml->securityFacebookID))
                        {
                        ?>
                        <input type="submit" name="pf_clear" value="Clear" onclick="myBeforeSubmit(); return myValidateInputs(this);"/>
                        <?php
                        }
                        ?>
                        <input type="submit" name="pf_update" value="Save" onclick="myBeforeSubmit(); return myValidateInputs(this);"/>
                        <input type="submit" name="pf_next" value="Save & Next" onclick="myBeforeSubmit(); return myValidateInputs(this);"/>
                      </div>
                      <?php
                        }
                      ?>
                  </div>
                </div>
                </div>
              </div>
              <?php
              }
              ?>
            </td>
          </tr>
        </table>
      </div>
    </form>
    <div id="FT">
      <div id="FT_L">
        <a href="http://www.openlinksw.com/virtuoso"><img alt="Powered by OpenLink Virtuoso Universal Server" src="/ods/images/virt_power_no_border.png" border="0" /></a>
      </div>
      <div id="FT_R">
        <a href="/ods/faq.html">FAQ</a> | <a href="/ods/privacy.html">Privacy</a> | <a href="/ods/rabuse.vspx">Report Abuse</a>
        <div>
          Copyright &copy; 1999-2010 OpenLink Software
        </div>
      </div>
     </div>
  </body>
</html>
