<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Tim Wentzlau (tim.wentzlau@auxilior.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Module News'letter for the 'aux_newsmailer' extension.
 *
 * @author	Tim Wentzlau <tim.wentzlau@auxilior.com>
 */
require_once (PATH_t3lib.'class.t3lib_stdgraphic.php');
require_once (PATH_t3lib.'class.t3lib_htmlmail.php');
require_once (PATH_site.'typo3/sysext/cms/tslib/class.tslib_content.php');
require_once (PATH_t3lib.'class.t3lib_scbase.php');  
require_once ($BACK_PATH.'init.php');
require_once ($BACK_PATH.'template.php');
#require_once (PATH_typo3.'sysext/lang/lang.php');
#$LANG->includeLLFile('EXT:aux_newsmailer/mod1/locallang.php');
class auxnewsmailer_corecron extends t3lib_SCbase {
	var $pageinfo;
	var $cObj;
	var $inBatch=false;
	var	$limit=10;
	var	$limit_create=10;	

	/**
	 * Loads a newsletter control record
	 *
	 * @param	int		$idctrl: the uid of a newsletter control
	 * @return	array		array with newsletter control record settings
	 */
	function loadControl($idctrl=0){

 		if (!$idctrl){
			 $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
	                '*',
	                'tx_auxnewsmailer_control',
	                'pid='.intval($this->id),
	                '',
	                '',
					''
		    );
		}
		else {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
	                '*',
	                'tx_auxnewsmailer_control',
	                'uid='.intval($idctrl),
	                '',
	                '',
					''
		    );

		}

		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$pid=$row['folders'];
			if ($pid=='')
				$pid=$row['pid'];
			$pid='('.$pid.')';
			$row['pages']=$pid;

			return $row;
		}
		return array();


	}

	/**
	 * Block a newsletter control record for send and creation operations 
	 *
	 * @param	int		$idctrl: the uid of a newsletter control
	 * @param	int		$type: locktime	 
	 * @return	bolean	if true newsletter record was blocked else it was already blocked if multilpe returns true
	 */
	
	function blockControl($idctrl=0, $type){
	
		$where='hidden=0';
		
		if ($idctrl){
			$where.=' and uid='.intval($idctrl);
		}
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                '*',
                'tx_auxnewsmailer_control',
            	$where,
                '',
                '',
                ''
        );	

 		if ($idctrl){
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
				if($row['control_status']==0){
					$updateArray=array(
						'control_status' => intval($type),	
					);
					$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_auxnewsmailer_control','uid='.intval($idctrl), $updateArray);
					return true;	
				}else{
					return false;
				}	
			}
		}
		else {
			while($rows = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)){			
				if($rows['control_status']==0){
					$updateArray=array(
						'control_status' => intval($type),	
					);
					$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_auxnewsmailer_control','uid='.intval($rows['uid']), $updateArray);								
				}
			}
			return true;
		}



	}

	/**
	 * Block a newsletter control record for send and creation operations 
	 *
	 * @param	int		$idctrl: the uid of a newsletter control
	 * @param	int		$type: locktime	 
	 * @return	bolean	if true newsletter record was blocked else it was already blocked if multilpe returns true
	 */
	
	function unblockControl($idctrl=0, $type){
	
		$where='hidden=0';
		
		if ($idctrl){
			$where.=' and uid='.intval($idctrl);
		}
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                '*',
                'tx_auxnewsmailer_control',
            	$where,
                '',
                '',
                ''
        );	

 		if ($idctrl){
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
				if($row['control_status']==intval($type)){
					$updateArray=array(
						'control_status' => 0,	
					);
					$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_auxnewsmailer_control','uid='.intval($idctrl), $updateArray);
					return true;	
				}else{
					return false;
				}	
			}
		}
		else {
			while($rows = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)){			
				if($rows['control_status']==intval($type)){
					$updateArray=array(
						'control_status' => 0,	
					);
					$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_auxnewsmailer_control','uid='.intval($rows['uid']), $updateArray);								
				}
			}
			return true;
		}



	}
		
	/**
	 * Executes the scan and mailing actions in cronjobs
	 *
	 * @param	string		$action: -s scans and creates messages. -m mails then next 50 mails pending.
	 * @return	void		outputs status to stdout.
	 */
	function batch($action, $idctrl=0){
	    global $LANG;	
#		$LANG->init($ctrl['lang']);	
		
	  	echo("Auxnewsmailer running in batch mode:\n--------\n");
	  	$this->cObj=t3lib_div::makeInstance("tslib_cObj");
		//$GLOBALS['TYPO3_DB']->debugOutput=true;
		
		$this->inBatch=true;
		if (($action=='')||($action=='-s')){
		  	echo("Scanning news:\n");
	  		echo($this->scanNews('email', $idctrl));
	  	}
	  	if (($action=='')||($action=='-c')){
		  	echo("Create messages:\n");
	  		echo($this->mailList($idctrl));
	  	}
	  	if (($action=='')||($action=='-m')){
		  	echo("\nSend e-mails:\n");
			// if a specified uid is called
			$start_time = time();
			if ($idctrl && $this->blockControl($idctrl, $start_time)){
				echo("Control UID".$idctrl.": ".$this->sendMail(true, $idctrl)." send \n");
				$this->unblockControl($idctrl, $start_time);				
			}else{
				// generic send		
				$where='hidden=0 and control_status='.intval($start_time);
				$this->blockControl($idctrl, $start_time);								
				$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
		                '*',
		                'tx_auxnewsmailer_control',
		            	$where,
		                '',
		                '',
		                ''
		        );	
				while($ctrl = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)){			
	  				echo("Control UID".$ctrl['uid'].": ".$this->sendMail(true, $ctrl['uid'])." send \n");	
					$this->unblockControl($ctrl['uid'], $start_time);				
				}				
			}
	  	}

	  	echo("\n-------\nBatch done\n");
	}

	/**
	 * creates the messages that should be send by mail.
	 *
	 * @param	[type]		$idctrl: uid of newsletter control
	 * @return	[type]		Number of messages created.
	 */
	function mailList($idctrl){
	
		$start_time = time();

		$where='hidden=0 and control_status='.intval($start_time);
		
		if ($idctrl){
			$where.=' and uid='.intval($idctrl);
			$this->blockControl($idctrl, $start_time);
		}else{
			$this->blockControl($idctrl, $start_time);
		}
		$cnt=0;

		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                '*',
                'tx_auxnewsmailer_control',
            	$where,
                '',
                '',
                ''
        );	
		// Create messages for all control records
		while($ctrl = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {


			//$ctrl=$this->loadControl($idctrl);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
	                '*',
	                'tx_auxnewsmailer_maillist',
	                'state=0 and msgtype=1 and idctrl='.intval($ctrl['uid']),
	                '',
	                'iduser, idnews',
					''
	            );

			$cid=0;
			$cnt=0;
			$this->limit_create = $ctrl['tx_auxnewsmailersplitcat_tmplcrtbounce'];
			$newslist='';
			$newslist_count = $GLOBALS['TYPO3_DB']->sql_affected_rows();	
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

				if ($row['iduser']!=$cid){
					if ($cid!=0){
                				if($cnt==$this->limit_create-1){break;}					
						$idmsg = $this->createMsg($cid,$newslist,$ctrl);
						$cnt = $cnt+1;
					}
					$newslist='';
					$cid=$row['iduser'];
				}
				if ($newslist=='')
					$newslist.=$row['idnews'];
				else
					$newslist.=','.$row['idnews'];
					

			}
			
			if ($newslist!=''){
				$idmsg = $this->createMsg($cid,$newslist,$ctrl);
				$cnt++;
			} 
/*
			$updateArray=array(
				'state'=>'2'
			);
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_auxnewsmailer_maillist','state=0 and msgtype=1', $updateArray);
*/

			// Process Create Statistics
			$restat = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
		                '*',
		                'tx_auxnewsmailer_sendstat',
		                'idmsg='.intval($idmsg).' and pid='.intval($ctrl['pid']),
		                '',
		                'idmsg',
						''
		            );	
			$row_count = $GLOBALS['TYPO3_DB']->sql_affected_rows();						
			if ($row_count>0 && $newslist_count>0){
				while($rowstat = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($restat)) {
					$new_total_time = (time()-$start_time) + intval($rowstat['create_total_time_seconds']);
					$updateArray=array(
						'create_total_msg' => intval($rowstat['create_total_msg']+$cnt),	
						'create_total_time' => t3lib_BEfunc::calcAge($new_total_time),
						'create_total_time_seconds' => intval($new_total_time),
						'crdate' => time(),
					);
					$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_auxnewsmailer_sendstat','idmsg='.intval($idmsg).' and pid='.intval($ctrl['pid']), $updateArray);		
				}
			}else if($newslist_count>0){
				$new_total_time = time() - $start_time;			
				$insertArray = array(
					'tstamp' => time(),	
					'idmsg' => intval($idmsg),								
					'pid' => intval($ctrl['pid']),			
					'create_total_msg' => intval($cnt),	
					'create_total_time' => t3lib_BEfunc::calcAge($new_total_time),
					'create_total_time_seconds' => intval($new_total_time),
					'crdate' => time(),					
				);
				$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_auxnewsmailer_sendstat', $insertArray);	
			}	

		$this->unblockControl($idctrl, $start_time);
		$final_message .= "Control UID".$ctrl['uid'].": ".$cnt." created \n";
		}	

		return $final_message;

	}


	/**
	 * Formats a single news items for html
	 *
	 * @param	array		$ctrl: Newsletter control array.
	 * @param	array		$news: row from tt_news table.
	 * @return	string		newsitem html formmated.
	 */
	function formatHTML($ctrl,$news){
	    global $LANG;	

		$LANG->init($ctrl['lang']);
		$LANG->includeLLFile('EXT:aux_newsmailer_split_cat/locallang_db.xml');
		$LANG->includeLLFile('EXT:aux_newsmailer/mod1/locallang.xml');	
		$LANG->includeLLFile('EXT:mbl_newsevent/locallang.php');	
			
		$i=explode(',',$news['image']);
		$image=$i[0];
		$newsdate=strftime($ctrl['dateformat'], $news['datetime']);

		$showitems=$ctrl['showitems'];
		$result.='<div class="newsmailitem"">';

		$result.='	<div class="newsitemtext">';
		if (t3lib_div::inlist($showitems,'2'))
			$result.='	<div class="newsmailimage">'.$this->getImage($image,$ctrl['listimagew'],$ctrl['listimageh']).'</div>';
		if (t3lib_div::inlist($showitems,'1'))
			$result.='		<div class="newsmailtitle">'.$news['title'].'</div>';
		if (t3lib_div::inlist($showitems,'4'))
			$result.='		<div class="newsmaildate">'.$newsdate.'</div>';
		$result.='		<div class="newsmailshort">'.$news['short'].'</div>';
		if (t3lib_div::inlist($showitems,'3'))
			$result.='	<div class="newsmailbody">'.$this->formatStr($news['bodytext']).'</div>';
		else
			$result.='	<div class="newsmaillink"><a href="http://'.$ctrl['orgdomain'].'/index.php?id='.$ctrl['newspage'].'&tx_ttnews[tt_news]='.$news['uid'].'">'.$LANG->getLL("readmore").'</a></div>';
		$result.='	</div>';
		$result.='<div class="ffclear"></div></div>';
		return $result;


	}

	/**
	 * Formats a news item in plain text
	 *
	 * @param	array		$ctrl: Newsletter control array
	 * @param	array		$news: row from tt_news table
	 * @return	string		news item in plain text.
	 */
		function formatPlain($ctrl,$news){
	    global $LANG;	
		
		$LANG->init($ctrl['lang']);
		$LANG->includeLLFile('EXT:aux_newsmailer_split_cat/locallang_db.xml');
		$LANG->includeLLFile('EXT:aux_newsmailer/mod1/locallang.xml');	
		$LANG->includeLLFile('EXT:mbl_newsevent/locallang.php');	
		
		$newsdate=strftime($ctrl['dateformat'], $news['datetime']);

		$showitems=$ctrl['showitems'];
		$result.="\n";
		if (t3lib_div::inlist($showitems,'1'))
			$result.=$news['title']."\n";
		if (t3lib_div::inlist($showitems,'4'))
			$result.='('.$newsdate.")\n";
		$result.=$news['short']."\n";
		if (t3lib_div::inlist($showitems,'3'))
			$result.=$this->formatStr($news['bodytext'])."\n";
//		else
//			$result.='<a href="http://'.$ctrl['orgdomain'].'/index.php?id='.$ctrl['newspage'].'">'.$LANG->getLL("readmore").'</a></div>';
		$result.="\n";
		return $result;


	}


	/**
	 * Checks if there are unsend sms messages pending
	 *
	 * 
	 * @return	void
	 */

	function smsList()
	{

		$sql='select * from tx_auxnewsmailer_maillist where state=0 and msgtype=2 order by idnews limit 0,50';
		$dbres = mysql(TYPO3_db,$sql) or $content .= 'Error Mysql:'.mysql_error().'<br>';

		$cid=0;

		$clearitems='';
		$xmllist=array();
		while($row = mysql_fetch_array($dbres)) {
			$author='';
			$plain='';
			$title='';
			if ($clearitems=='')
				$clearitems='uid='.$row['uid'];
			else
				$clearitems.=' or uid='.$row['uid'];

			if ($row['idnews']!=$cid)
			{
				$cid=$row['idnews'];
				$sql='select author,title,short,bodytext from tt_news where uid='.$cid;
				$dbnewsres = mysql(TYPO3_db,$sql) or $content .= 'Error Mysql:'.mysql_error().'<br>';
				$newsrow = mysql_fetch_array($dbnewsres);
				$author=
				$title=
				$short=$newsrow['short'];
				$body=$this->formatStr($this->local_cObj->stdWrap($newsrow['bodytext'], $lConf['content_stdWrap.']));
				$plain=$short."\n\r".$body."\n\r\n\r".$author;
				$xmllist[$cid]['author']=$newsrow['author'];
				$xmllist[$cid]['authormail']=$newsrow['author_email'];
				$xmllist[$cid]['title']=$newsrow['title'];
				$xmllist[$cid]['short']=$newsrow['short'];
				$xmllist[$cid]['body']=$newsrow['bodytext'];
				$xmllist[$cid]['phones']=array();
			}
			$userinfo=$this->getUserInfo($row['iduser']);
			if ($userinfo['phone'])
				$xmllist[$cid]['phones'][]=$userinfo['phone'];








		}

		$xml='<smslist>';
		for(reset($xmllist);$k=key($xmllist);next($xmllist)){
			$c=current($xmllist);
			$xml.='<smsitem>';
			$xml.='<author>'.$c['author'].'</author>';
			$xml.='<authormail>'.$c['authormail'].'</authormail>';
			$xml.='<title>'.$c['title'].'</title>';
			$xml.='<short>'.$c['short'].'</short>';
			$xml.='<body>'.$c['body'].'</body>';
			$xml.='<phones>';
			$phones=$c['phones'];
			reset($phones);
			while (list($p,$pc)=each($phones)){
				$xml.='<phone>'.$pc.'</phone>';
			}
			$xml.='</phones></smsitem>';
		}
		$xml.='</smslist>';

		$content.=$xml;

		//$content.=serialize($xmllist);
  		if ($clearitems!='')
		{
			$sql='update tx_auxnewsmailer_maillist set state=1 where '.$clearitems;
			//$content.=$sql.'<br>';
		}
		//$dbres = mysql(TYPO3_db,$sql) or $content .= 'Error Mysql:'.mysql_error().'<br>';
		$content.='list sendt';
		return $content;
	}

	/**
	 * Look up are FE user in the table fe_users
	 *
	 * @param	int		$uid: id of the user.
	 * @return	array	fe_users field values.
	 */
	function getUserInfo($uid){

		/*$sql='select * from fe_users where uid='.$uid;
		$dbres = mysql(TYPO3_db,$sql) or $content .= 'Error Mysql:'.mysql_error().'<br>';
		$row = mysql_fetch_array($dbres);*/

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                '*',
                'fe_users',
            	'uid='.$uid,
                '',
                '',
                ''
            );

		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$userinfo=array();
		$userinfo['name']=$row['name'];
		$userinfo['mail']=$row['email'];
		$userinfo['phone']=$row['telephone'];
		$userinfo['html']=$row['tx_auxnewsmailer_html'];
		return $userinfo;
	}

	/**
	 * Returns a message tx_auxnewsmailer_msglist
	 *
	 * @param	int		$uid: id of message
	 * @return	array		array with message details and control info
	 */
	function getMessageInfo($uid){
		/*$sql='select * from tx_auxnewsmailer_msglist,tx_auxnewsmailer_control where tx_auxnewsmailer_msglist.uid='.$uid.' and tx_auxnewsmailer_control.uid=idctrl';

		$dbres = mysql(TYPO3_db,$sql) or $content .= 'Error Mysql:'.mysql_error().'<br>';
		$row = mysql_fetch_array($dbres);*/


		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                '*',
                'tx_auxnewsmailer_msglist,tx_auxnewsmailer_control',
            	'tx_auxnewsmailer_msglist.uid='.$uid.' and tx_auxnewsmailer_control.uid=idctrl',
                '',
                '',
                ''
            );

		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

		$newsinfo=array();
		//$newsinfo['title']=$row['title'];
		$newsinfo['plain']=$row['plaintext'];
		$newsinfo['html']=$row['htmltext'];
		$newsinfo['template']=$row['template'];
		$newsinfo['stylesheet']=$row['stylesheet'];
		$newsinfo['organisation']=$row['organisation'];
		$newsinfo['orgdomain']=$row['orgdomain'];
		$newsinfo['name']=$row['name'];
		$newsinfo['subject']=$row['subject'];
		$newsinfo['returnmail']=$row['returnmail'];
		$newsinfo['feprofilepage']=$row['userpage'];
		$newsinfo['userpid']=$row['userpid'];
		$newsinfo['image']=$row['image'];
		$newsinfo['imagew']=$row['imagew'];
		$newsinfo['imageh']=$row['imageh'];
		$newsinfo['listimagew']=$row['listimagew'];
		$newsinfo['listimageh']=$row['listimageh'];
		$newsinfo['usecat']=$row['usecat'];
		$newsinfo['pretext']=$row['pretext'];
		$newsinfo['posttext']=$row['posttext'];
		$newsinfo['autoscan']=$row['autoscan'];
		$newsinfo['showitems']=$row['showitems'];



		return $newsinfo;
	}

	/**
	 * Scans the tt_news table for unsend news items.
	 *
	 * @param	string		$list: if $list='mail' the messages are prepared for mail based newsletters.  if $list='sms' the messages are prepared for sms based newsletters
	 * @param	int			$idctrl: uid of a certain newsletter control record. if $idctrl=0 all newsletter controls are scanned
	 * @return	int			Number of news items scanned
	 */
	 
	function scanNews($list,$idctrl)
	{

		$msgType=1;
		$catfield='domail';

		if ($list=='sms'){
		  	$msgType=2;
		  	$catfield='dosms';
		}

		$start_time = time();
		/*
		$where='hidden=0';
		if ($idctrl){
			$where.=' and uid='.$idctrl;
		}
		*/
		$where='hidden=0 and control_status='.intval($start_time);
		
		if ($idctrl){
			$where.=' and uid='.intval($idctrl);
			$this->blockControl($idctrl, $start_time);
		}else{
			$this->blockControl($idctrl, $start_time);
		}
		
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                '*',
                'tx_auxnewsmailer_control',
            	$where,
                '',
                '',
                ''
        );
		
		$dbres_count = $GLOBALS['TYPO3_DB']->sql_affected_rows();
		if ($dbres_count==0){return "No newsletter control records to scan!";}

		$updateArray=array(
			'tx_auxnewsmailer_scanstate'=>'1'
		);
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_news','tx_auxnewsmailer_scanstate=0', $updateArray);


		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
			// check send schedule
			if (!$this->checkDuration($row)){
				$this->unblockControl($row['uid'], $start_time);
				$final_msg .= "Control UID".$row['uid'].":(access denied by automatic mailing)\n";
				#return '0 (access denied by automatic mailing)';
			}else{
				// Pid of news records
				$pid=$row['folders'];
				if ($pid=='')
					$pid=$row['pid'];
				$pid='('.$pid.')';	
				
				// check news limitation, endtime, archivedate
				$list_res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'distinct uid, endtime, starttime, archivedate',
				'tt_news',
				'deleted=0 and hidden=0 and tt_news.pid in '.$pid.' and tx_auxnewsmailer_scanstate<2 and starttime<'.time(),
				'',
				'uid',
				''
				);
				$newslist='';
				if ($list_res){
					while ($list_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($list_res)) {
		
						// check if end time is due
						if ($list_row['endtime']==0){
							$check_endtime=true;
						}else if ($row['endtime']!=0){
							if($list_row['endtime']<time()){
								$check_endtime=false;
							}else{
								$check_endtime=true;
							}
						}
						// check if archive date has begin		
						if ($list_row['archivedate']==0){
							$check_archivedate=true;
						}else if ($list_row['archivedate']!=0){
							if($list_row['archivedate']<time()){
								$check_archivedate=false;
							}else{
								$check_archivedate=true;		
							}
						}
								
						if ($newslist!='')
							if ($check_endtime and $check_archivedate){
							$newslist.=',';
							}
							if ($check_endtime and $check_archivedate){
							$newslist.=$list_row['uid'];	
							}
						
					}
				}			
		
				// Process News to Messages
	
				$sql='insert into tx_auxnewsmailer_maillist (idnews,iduser,msgtype,idctrl)';
				if ($row['usecat']){
					//scan for news items that FE users scubscribe to directly thru tt_news cat
					$sql.=' SELECT distinct tt_news.uid, iduser ,'.$msgType.','.$row['uid'];
					$sql.=' FROM fe_users,tt_news,tt_news_cat_mm catmm, tx_auxnewsmailer_usercat usercat ';
					$sql.=' WHERE ';
					$sql.='usercat.iduser=fe_users.uid and ';
					$sql.='uid_foreign=mailcat and ';
					$sql.='tt_news.uid=catmm.uid_local and ';
					$sql.='catmm.uid_foreign>0  and ';
					$sql.=$catfield.'>0 and ';
	
				} else
				{
					//scan for news items that are not send and join with FE users
	
					$sql.='SELECT distinct tt_news.uid, fe_users.uid,'.$msgType.','.$row['uid'];
					$sql.=' FROM tt_news,fe_users';
					$sql.=' WHERE ';
				}
	
				$sql.='fe_users.pid='.$row['userpid'].' and ';
				$sql.='fe_users.disable=0 and ';
				$sql.='fe_users.deleted=0 and ';						
				$sql.='fe_users.tx_auxnewsmailer_newsletter=1 and ';
				$sql.='tt_news.pid in '.$pid.' and ';
				$sql.='tt_news.hidden=0 and ';
				$sql.='tt_news.deleted=0 and ';
				$sql.='tt_news.starttime<'.time().' and ';		
				$sql.='tt_news.uid in ('.$newslist.') and ';	
				$sql.='tx_auxnewsmailer_scanstate=1';																	
				$sql.=' order by tt_news.uid';
	
				$res =$GLOBALS['TYPO3_DB']->sql_query($sql);
				$cnt=$GLOBALS['TYPO3_DB']->sql_affected_rows();
				// tag as scanned
				$updateArray=array(
					'tx_auxnewsmailer_scanstate'=>'2',
					'tx_auxnewsmailer_scanstate_control'=>intval($row['uid']) 
				);
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_news','tx_auxnewsmailer_scanstate=1 and tt_news.uid in ('.$newslist.')', $updateArray);
				// Prevent negative show
				if($cnt==-1){$cnt=0;};
				$final_msg .= "Control UID".$row['uid'].": scanned ".$cnt." messages\n";
				$this->unblockControl($row['uid'], $start_time);
			}
		 }
		
		return $final_msg;
	}

	/**
	 * check if a news control record are due for checking.
	 *
	 * @param	array		$ctrl: Newsletter control array
	 * @return	boolean		true if the newsletter control must be scanned
	 */
	function checkDuration($ctrl){
	  	$res=false;

	  	if (!$this->inBatch)
	  		$res=true;
		else if ($ctrl['duration']){
			$weekday=date('w');
			$today=intval(date('d'));
			$days=time()-$ctrl['lasttime'];
			$dayspan=$days/(60*60*24);

			$lastmonth=date('n',$ctrl['lasttime']);
			$lastyear=date('Y',$ctrl['lasttime']);
			$thismonth=date('n');
			$thisyear=date('Y'); 
			$monthspan=12-$lastmonth+($thisyear-$lastyear-1)*12+$thismonth;
			if ($today==15 && t3lib_div::inList($ctrl['duration'],'14'))
				//send day 15
				$res=true;			
			if ($today==1 && t3lib_div::inList($ctrl['duration'],'11'))
				//send day 1
				$res=true;			
			if (t3lib_div::inList($ctrl['duration'],'10'))
				//send as soon there is messages
				$res=true;
			if ((dayspan>0)&&(t3lib_div::inList($ctrl['duration'],$weekday)))
				//match day of week and only once each day
				$res=true;
			if (($monthspan>0)&&(t3lib_div::inList($ctrl['duration'],'9')))
				//match month checking only once each month
				$res=true;
			if (($dayspan>14)&&(t3lib_div::inList($ctrl['duration'],'8')))
				//match every 14th day
				$res=true;
		}
		if ($res){
			$updateArray=array(
				'lasttime'=>time()
			);
			$dbres = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_auxnewsmailer_control','uid='.$ctrl['uid'], $updateArray);
		}

	  	return $res;
	}
	

	/**
	 * Sends the next 50 e-mails that are pending.
	 *
	 * @param	boolean		$inbatch: if true the function is called by the cron job
	 * @return	int		number of mails send.
	 */
	function sendMail($inbatch=false, $idctrl){
	
		$start_time = time();
			
		if (!$inbatch){
			$ctrl=$this->loadControl($idctrl);
			$wctrl='and tx_auxnewsmailer_msglist.idctrl='.$ctrl['uid'];
		}else{
			$ctrl=$this->loadControl($idctrl);		
			$wctrl='and tx_auxnewsmailer_msglist.idctrl='.intval($idctrl);	
		}
		
		$this->limit = $ctrl['tx_auxnewsmailersplitcat_tmplmsgbounce'];
		
        $dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_auxnewsmailer_usrmsg.*',
                'tx_auxnewsmailer_usrmsg,tx_auxnewsmailer_msglist',
            	'tx_auxnewsmailer_usrmsg.state=0 and tx_auxnewsmailer_usrmsg.idmsg=tx_auxnewsmailer_msglist.uid '.$wctrl,
                '',
                'idmsg',
                '0,'.$this->limit
        );
		$cnt=0;
		$cid=0;		
		$msglist_count = $GLOBALS['TYPO3_DB']->sql_affected_rows();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
		
			// For statistics if diferent idmsg breakloop
			if ($cid==0){$idmsg=$row['idmsg'];}				
			if ($cid!=0 && $cid!=$row['idmsg']){break;}
			$cid = $row['idmsg'];
					
	  		$userinfo=$this->getUserInfo($row['iduser']);
			$msg=$this->getMessageInfo($row['idmsg']);

			$title=$msg['subject'];
			$fromEmail=$msg['returnmail'];
			$fromName=$msg['organisation'];
			if ($fromName!='')
				$fromName.='-';
			$fromName.=$msg['name'];

			$marker=array();
			$marker['###name###']=$userinfo['name'];
			$marker['###orgname###']=$msg['name'];
			$marker['###org###']=$msg['organisation'];
			$marker['###domain###']=$msg['orgdomain'];

			$plain=$this->cObj->substituteMarkerArray($msg['plain'],$marker);
			$title=$this->cObj->substituteMarkerArray($title,$marker);

			if ($userinfo['html'])
				$html=$this->cObj->substituteMarkerArray($msg['html'],$marker);
			else
				$html='';
			$this->domail($userinfo['mail'],$title,$plain,$fromEmail,$fromName,$html);
			/*$content.='----------------------</br>';
			$content.=$userinfo['mail'].'</br>';
			$content.=$msg['plain'].'</br>';*/
			$updateArray=array(
				'state' => '2',
				'tstamp'=>time(),
			);
			$ures = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_auxnewsmailer_usrmsg','idmsg='.$row['idmsg'].' and iduser='.$row['iduser'], $updateArray);
			$cnt++;
		}
		
		// Process Create Statistics
		$restat = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
	                '*',
	                'tx_auxnewsmailer_sendstat',
	                'idmsg='.intval($idmsg).' and pid='.intval($ctrl['pid']),
	                '',
	                'idmsg',
					''
	            );	
		$row_count = $GLOBALS['TYPO3_DB']->sql_affected_rows();						
		if ($row_count>0 && $msglist_count>0){
			while($rowstat = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($restat)) {
				$new_total_time = (time()-$start_time) + $rowstat['send_total_time_seconds'];	
				$updateArray=array(
					'send_total_msg' => intval($rowstat['send_total_msg']+$cnt),	
					'send_total_time' => t3lib_BEfunc::calcAge($new_total_time),
					'send_total_time_seconds' => intval($new_total_time),
					'crdate' => time(),				
				);
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_auxnewsmailer_sendstat','idmsg='.intval($idmsg).' and pid='.intval($ctrl['pid']), $updateArray);		
			}
		}else if ($msglist_count>0){ 
			$new_total_time = time() - $start_time;			
			$insertArray = array(
				'tstamp' => time(),	
				'idmsg' => intval($idmsg),								
				'pid' => intval($ctrl['pid']),			
				'send_total_msg' => intval($cnt),	
				'send_total_time' => t3lib_BEfunc::calcAge($new_total_time),
				'send_total_time_seconds' => intval($new_total_time),
				'crdate' => time(),				
			);
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_auxnewsmailer_sendstat', $insertArray);	
		}	
				
		return $cnt;

	}

	/**
	 * Sends a mail
	 *
	 * @param	[type]		$email: a-mail address
	 * @param	[type]		$subject: subject of the message
	 * @param	[type]		$message: the plain version of the message
	 * @param	[type]		$fromEMail: sender e-mail
	 * @param	[type]		$fromName: sender name
	 * @param	[type]		$html: html version of the mail
	 * @return	void
	 */
	function domail($email,$subject,$message,$fromEMail,$fromName,$html='')
	{

		$cls=t3lib_div::makeInstanceClassName('t3lib_htmlmail');

		if (class_exists($cls))
		{

			$Typo3_htmlmail = t3lib_div::makeInstance('t3lib_htmlmail');
			$Typo3_htmlmail->start();
			$Typo3_htmlmail->useBase64();

			$Typo3_htmlmail->subject = $subject;
			$Typo3_htmlmail->from_email = $fromEMail;
			$Typo3_htmlmail->from_name = $fromName;
			$Typo3_htmlmail->replyto_email = $Typo3_htmlmail->from_email;
			$Typo3_htmlmail->replyto_name = $Typo3_htmlmail->from_name;
			$Typo3_htmlmail->organisation = '';
			$Typo3_htmlmail->priority = 3;

			$Typo3_htmlmail->addPlain($message);
			if (trim($html)) {
				$Typo3_htmlmail->theParts['html']['content'] = $html;
				$Typo3_htmlmail->theParts['html']['path'] = '';
				$Typo3_htmlmail->extractMediaLinks();
				$Typo3_htmlmail->extractHyperLinks();
				$Typo3_htmlmail->fetchHTMLMedia();
				$Typo3_htmlmail->substMediaNamesInHTML(0); // 0 = relative
				$Typo3_htmlmail->substHREFsInHTML();
				$Typo3_htmlmail->setHTML($Typo3_htmlmail->encodeMsg($Typo3_htmlmail->theParts['html']['content']));
			}


			$Typo3_htmlmail->setHeaders();
			$Typo3_htmlmail->setContent();
			$Typo3_htmlmail->setRecipient(explode(',', $email));
			$Typo3_htmlmail->sendtheMail();
		}
	}



	/**
	 * Format string with general_stdWrap from configuration
	 *
	 * @param	string		$string to wrap
	 * @return	string		wrapped string
	 */
	function formatStr($str) {
		if (is_array($this->conf['general_stdWrap.'])) {
			$str = $this->local_cObj->stdWrap($str, $this->conf['general_stdWrap.']);
		}
		return $str;
	}

	/**
	 * initialises the storage pid
	 *
	 * @return	[type]		...
	 */
	function initPidList () {
		// pid_list is the pid/list of pids from where to fetch the news items.
		$pid_list = $this->lConf['storagePID'];
		$pid_list = $pid_list?$pid_list:1;

		$recursive = $this->lConf['recursive'];
		$recursive = is_numeric($recursive)?$recursive:false;
		// extend the pid_list by recursive levels
		$this->pid_list = $this->pi_getPidList($pid_list, $recursive);
		$this->pid_list = $this->pid_list?$this->pid_list:'';
		if ($this->pid_list!='')
			$this->pidQuery='pid IN (' . $this->pid_list . ')';
		else
			$this->pidQuery='';



	}

	/**
	 * creates an image tag imagemagic for sizing the image
	 *
	 * @param	string		$file: file name of image file in /uploads/pic
	 * @param	int			$height: height of the image
	 * @param	int			$width: width of the image
	 * @return	string		Fully qualyfied image tag
	 */
	function getImage($file,$height,$width) {
		// overwrite image sizes from TS with the values from the content-element if they exist.
		if ($file=='')
			return $file;


		$theImgCode = '';
		//$imgs = t3lib_div::trimExplode(',', $row['image'], 1);
		//$imgsCaptions = explode(chr(10), $row['imagecaption']);
		//$imgsAltTexts = explode(chr(10), $row['imagealttext']);
		//$imgsTitleTexts = explode(chr(10), $row['imagetitletext']);

		//reset($imgs);
		$lConf=array();

		$lConf['image.']['file.']['maxW']= $width;
		$lConf['image.']['file.']['maxH']= $height;



		$imgObj = t3lib_div::makeInstance('t3lib_stdGraphic'); // instantiate object for image manipulation
		$imgObj->init();
		//$imgObj->mayScaleUp = 1;
		$imgObj->absPrefix = PATH_site;
		$uploadfolder=PATH_site.'/uploads/pics/'.$file;

        if (!@is_file($uploadfolder))        die('Error: '.$uploadfolder.' was not a file');
		$imgObj->dontCheckForExistingTempFile=true;
		$imgInfo = $imgObj->imageMagickConvert($uploadfolder,'jpg',$width.' m',$height.' m','','',1);

		$url='../../../../'.substr($imgInfo[3],strlen(PATH_site));

		$lConf['image.']['file'] =$url;
		$lConf['image.']['altText'] = '';
		$lConf['image.']['titleText'] = '';


		//$theImgCode .= $this->local_cObj->IMAGE($lConf['image.']);
		$theImgCode.= '<img src="'. $url .'" border="0"/>';



		return $theImgCode;
	}


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/aux_newsmailer_split_cat/mailer/class_auxnewsmailer_corecron.php'])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/aux_newsmailer_split_cat/mailer/class_auxnewsmailer_corecron.php']);
	}

?>