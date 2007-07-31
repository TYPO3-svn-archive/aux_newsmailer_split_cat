<?php
/** 
* User-Extension of ux_tx_auxnewsmailer_core class.
*
* @author����Philip Almeida <philip.almeida@gmail.com>
*/

class ux_auxnewsmailer_corecron extends auxnewsmailer_corecron {	
	var $prefixId = "tx_auxnewsmailer_pi1";


	/**
	 * Creates a message both plain version and html. 
	 * If the message contains the same news items as a former mail the new one is discarded and the uid of the old message is used.
	 *
	 * @param	int		$uid: ...
	 * @param	string		$newslist: list with news ids that should go into the message
	 * @param	array		$ctrl: Newsletter control array
	 * @param	string		$preview: if ommited the new message is stored in the tx_auxnewsmailer table. 
	 * @return	string		if $preview='plain' the plain version of the message is returned. preview='html' the html version is returned.
	 */
	function createMsg($uid,$newslist,$ctrl,$preview=false){
	    global $LANG;	
		
		$LANG->init($ctrl['lang']);
		$LANG->includeLLFile('EXT:aux_newsmailer_split_cat/locallang_db.xml');
		$LANG->includeLLFile('EXT:aux_newsmailer/mod1/locallang.xml');	
		$LANG->includeLLFile('EXT:mbl_newsevent/locallang.php');			

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'uid',
                'tx_auxnewsmailer_msglist',
                'msgsignature="'.md5($newslist).'"',
                '',
                '',
                ''
            );

		if ((!$preview)&&($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$idmsg=$row['uid'];
		}
		else{
			$plain='';
            $html='';               
           
			if ($newslist && $ctrl['tx_auxnewsmailersplitcat_tmplcat']==0){
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
	                '*',
	                'tt_news',
	            	'tt_news.uid in ('.$newslist.')',
	                '',
	                'datetime ASC',
	                ''
	            );
	            while($newsrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
					$plain.=$this->formatPlain($ctrl,$newsrow);
					$html.=$this->formatHTML($ctrl,$newsrow);
				}
			}else{
		
				#$pageId   = $this->id;
				$pageId   = $ctrl['pid'];
				$tsetup = t3lib_div::makeInstance('t3lib_TStemplate');
				// do not log time-performance information
				$tsetup->tt_track = 0;
				$tsetup->init();
				
				// Get the root line
				$sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
				// the selected page in the BE is found
				// exactly as in t3lib_SCbase::init()
				$rootline = $sys_page->getRootLine($pageId);
				
				// This generates the constants/config + hierarchy info for the template.
				$tsetup->runThroughTemplates($rootline, 0);
				$tsetup->generateConfig();
				
				$this->conf =& $tsetup->setup['plugin.'][$this->prefixId.'.'];
				
				$template_ = $this->conf['file.']['template'];

				if ($template_== 'EXT:fe_rtenews/pi1/default.tmpl'){$template_ = null;}
		
				// Assign Template
				if($ctrl['template']){
					$file = '../../../../uploads/tx_auxnewsmailer/'.$ctrl['template'];
					$templateCode = t3lib_div::getURL($file);			
				}
				else if ($template_){
					$file = '../../../../'.$template_;
					$templateCode = t3lib_div::getURL($file);		
				}
			  	else{
					$file='../../aux_newsmailer_split_cat/res/template.tmpl';	
					$templateCode = t3lib_div::getURL($file);
				}
		
				
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
	                'distinct cat_mm.uid_foreign, cat.uid cat_uid, cat.title cat_title, cat.single_pid cat_single_pid',
	                'tt_news_cat_mm cat_mm, tt_news_cat cat, tt_news news',
	            	'news.uid=cat_mm.uid_local and cat_mm.uid_foreign=cat.uid and news.uid in ('.$newslist.')',
	                '',
	                'news.datetime ASC',
	                ''
	            );	
	            
	            while($newscat = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){	
	            	
					$marker=array();
					$wrapped=array();	            	
					$templateMarker = '###HTMLMAIL_NEWS_CAT_'.$newscat['cat_uid'].'###';
					$template = $this->cObj->getSubpart($templateCode, $templateMarker);
					$marker['###CAT_TITLE###']=	$newscat['cat_title'];					            	
	            	
					$res_item = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
		                '*, cat.image cat_image, cat.uid cat_uid, cat.title cat_title, cat.single_pid cat_single_pid',
		                'tt_news_cat_mm cat_mm, tt_news_cat cat, tt_news news',
		            	'news.uid=cat_mm.uid_local and cat_mm.uid_foreign=cat.uid and news.uid in ('.$newslist.') and cat.uid='.$newscat['cat_uid'],
		                '',
		                'news.datetime ASC',
		                ''
		            );		     
					$body='';       	
		            while($newsrow_item = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_item)){	
						$plain.=$this->formatPlain($ctrl,$newsrow_item);									
						$body.=$this->formatHTML_cat($ctrl,$newsrow_item,$templateCode);	
					}	
					$marker['###CAT_BODY###'] = $body;		
					$a[$newscat['cat_uid']]=$this->cObj->substituteMarkerArray($template,$marker);					         							
				}	

				$marker=array();
				$wrapped=array();	            	
				$templateMarker = '###HTMLMAIL_BODY###';
				$template = $this->cObj->getSubpart($templateCode, $templateMarker);
				// $marker['###CAT_TITLE###']=	$newscat['cat_title'];
				// Check all news categories
				$res_news_cat = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
	                'distinct cat.uid cat_uid',
	                'tt_news_cat cat',
	            	'',
	                '',
	                'cat.title ASC',
	                ''
	            );	
	            while($res_news_cat_list = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_news_cat)){	
					if($a[$res_news_cat_list['cat_uid']]==' '){
						$marker['###HTMLMAIL_DISPLAY_CAT_'.$res_news_cat_list['cat_uid'].'###']=	'';
					}else{
						$marker['###HTMLMAIL_DISPLAY_CAT_'.$res_news_cat_list['cat_uid'].'###'] = $a[$res_news_cat_list['cat_uid']];
					}
				}			


				$html=$this->cObj->substituteMarkerArray($template,$marker);																
			}
			$plain=$this->createNewsLetter($ctrl,$plain,'plain');
			$html=$this->createNewsLetter($ctrl,$html,'html');
			if (!$preview){
				$insertArray = array(
			    	'msgsignature'=>md5($newslist),
					'plaintext'=>$plain,
	   				'htmltext' =>$html,
	   				'idctrl'=>$ctrl['uid'],


				);


				$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_auxnewsmailer_msglist', $insertArray);
				$idmsg=$GLOBALS['TYPO3_DB']->sql_insert_id();
			}
		}

		$insertArray = array(
			'iduser'=>intval($uid),
			'idmsg'=>intval($idmsg),
			'tstamp'=>time(),
		);

		if (!$preview){
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_auxnewsmailer_usrmsg', $insertArray);
			$updateArray=array(
				'idmsg'=>intval($idmsg),
				'state'=>'2',
			);
			$query = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_auxnewsmailer_maillist','idnews in ('.$newslist.') AND iduser='.$uid.' AND idctrl='.$ctrl['uid'].' AND state=0 AND msgtype=1', $updateArray);	
			return $idmsg;											
		}
		if ($preview=='html'){
			return $html;
		}
		if ($preview=='plain'){
			return $plain;
		}
		return 0;
		
	}


	function formatHTML_cat($ctrl,$news,$templateCode){
	    global $LANG;	

		$LANG->init($ctrl['lang']);
		$LANG->includeLLFile('EXT:aux_newsmailer_split_cat/locallang_db.xml');
		$LANG->includeLLFile('EXT:aux_newsmailer/mod1/locallang.xml');	
		$LANG->includeLLFile('EXT:mbl_newsevent/locallang.php');	
		
		setlocale(LC_TIME, $ctrl['lang'].'_'.strtoupper($ctrl['lang']));
		$news_page_link;
				
		// Check for link
		if ($ctrl['tx_auxnewsmailersplitcat_tmplcatid']==1){
			$news_page_link = $news['cat_single_pid'];
		}else{
			$news_page_link = $ctrl['newspage'];	
		}
		
		$marker=array();
		$wrapped=array();             	
		$templateMarker = '###HTMLMAIL_NEWS_CAT_'.$news['cat_uid'].'_ITEM###';
		$template = $this->cObj->getSubpart($templateCode, $templateMarker);

		$i=explode(',',$news['image']);
		$image=$i[0];
		$newsdate=strftime($ctrl['dateformat'], $news['datetime']);
		$newstime=strftime($ctrl['timeformat'], $news['datetime']);
		$showitems=$ctrl['showitems'];

		if (t3lib_div::inlist($showitems,'2')){
			$marker['###IMAGE###'] = $this->getImage($image,$ctrl['listimagew'],$ctrl['listimageh']);
		}	
		if (t3lib_div::inlist($showitems,'1')){
			$marker['###TITLE###'] = $news['title'];
		}			
		if (t3lib_div::inlist($showitems,'4')){
			$marker['###DATE###'] = $newsdate;			
		}
		if (t3lib_div::inlist($showitems,'5')){
			$marker['###TIME###'] = $newstime;			
		}	
		if (t3lib_div::inlist($showitems,'6')){
			if($news['tx_mblnewsevent_isevent']==1){
				$marker['###EVENT_FROM_DATE###'] 	= strftime($ctrl['dateformat'], $news['tx_mblnewsevent_from']);
				$marker['###EVENT_FROM_TIME###'] 	= strftime($ctrl['timeformat'], $news['tx_mblnewsevent_from']);
				// Replace Organizer
				if(!empty($news['tx_mblnewsevent_organizer'])) {
					$table = substr($news['tx_mblnewsevent_organizer'], 0, strrpos($news['tx_mblnewsevent_organizer'], '_')); //The table to select from
					$sql = "SELECT * FROM " . $table . " WHERE uid = " . substr($news['tx_mblnewsevent_organizer'], strrpos($news['tx_mblnewsevent_organizer'], '_')+1);		
					$ores = mysql_query($sql);
						//If there is any organizer
						if(mysql_num_rows($ores) != 0) {
							//Get row
							$orow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($ores);	
							if($orow['name']!=''){								
								$marker['###EVENT_ORGANIZER###'] 	= $orow['name'];
							}							
						}	
				}else{
					$marker['###EVENT_ORGANIZER###'] 	= '';				
				}
					
				if($news['tx_mblnewsevent_from']!=$news['tx_mblnewsevent_to']){							
					#include(t3lib_extMgm::extPath('mbl_newsevent').'locallang.php');
					$marker['###EVENT_TO_TEXT###'] 	    = $LANG->getLL('to');	
					$marker['###EVENT_TO_DATE###'] 		= strftime($ctrl['dateformat'],$news['tx_mblnewsevent_to']);
					$marker['###EVENT_TO_TIME###'] 		= strftime($ctrl['timeformat'],$news['tx_mblnewsevent_to']);
				}else{
					$marker['###EVENT_TO_TEXT###'] 	    = '';	
					$marker['###EVENT_TO_DATE###'] 		= '';
					$marker['###EVENT_TO_TIME###'] 		= '';
				}				
			}												
		}	
		if (t3lib_div::inlist($showitems,'7')){

			$image = '<img src="http://'.$ctrl['orgdomain'].'/uploads/pics/'.$news['cat_image'].'" border="0" alt="'.$news['cat_title'].'"/>';					
			$marker['###CATEGORY_IMAGE###'] = '<a style="'.$ctrl['tx_auxnewsmailersplitcat_tmpllinkstyle'].'" href="http://'.$ctrl['orgdomain'].'/index.php?id='.$news_page_link.'" title="'.$news['cat_title'].'">'.$image.'</a>';
		}elseif(t3lib_div::inlist($showitems,'8')){
			$marker['###CATEGORY_IMAGE###'] = '<img src="http://'.$ctrl['orgdomain'].'/uploads/pics/'.$news['cat_image'].'" border="0" alt="'.$news['cat_title'].'" title="'.$news['cat_title'].'"/>';							
		}						
		if (t3lib_div::inlist($showitems,'3')){
			$marker['###BODY###'] = $this->formatStr($news['bodytext']);			
		}else{
			$marker['###SHORT###'] = $this->formatStr($news['short']);			
			$marker['###MORE###'] = '<a style="'.$ctrl['tx_auxnewsmailersplitcat_tmpllinkstyle'].'" href="http://'.$ctrl['orgdomain'].'/index.php?id='.$news_page_link.'&tx_ttnews[tt_news]='.$news['uid'].'" title="'.$LANG->getLL("readmore").'">'.$LANG->getLL("readmore").'</a>';
		}	
		
		$html=$this->cObj->substituteMarkerArray($template,$marker);			
		return $html;

	}
	
		/**
	 * Creates the compleate newsletter
	 *
	 * @param	array		$ctrl: Newsletter control array
	 * @param	array		$news: String with the news items that should go into the message.
	 * @param	string		$type: the encoding of the newsletter can be 'plain' or 'html'
	 * @return	string		The compleate message
	 */

	 
	function createNewsLetter($ctrl,$news,$type='html'){
		global $LANG, $BE_USER;

		$LANG->init($ctrl['lang']);
		$LANG->includeLLFile('EXT:aux_newsmailer_split_cat/locallang_db.xml');
		$LANG->includeLLFile('EXT:aux_newsmailer/mod1/locallang.xml');	
		$LANG->includeLLFile('EXT:mbl_newsevent/locallang.php');	
				
		setlocale(LC_TIME, $ctrl['lang'].'_'.strtoupper($ctrl['lang']));
			
		// retrieve number of newsletter;
		if ($ctrl['tx_auxnewsmailersplitcat_tmplcatid']==1){		
			$res_counter = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
	            'count(*)',
	            'tx_auxnewsmailer_msglist',
	        	'state=1 AND idctrl='.$ctrl['uid'],
	            '',
	            '',
	            ''
	        );		           	
	        while($message_counter = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_counter)){	
				$message_number = $message_counter['counter']+1;
			}		
		}else{
				$message_number = null;
		}

		#$pageId   = $this->id;
		$pageId   = $ctrl['pid'];
		$tsetup = t3lib_div::makeInstance('t3lib_TStemplate');
		// do not log time-performance information
		$tsetup->tt_track = 0;
		$tsetup->init();
		
		/* BUGBUGBUGBUGBUG
		// Get the root line
		$sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
		// the selected page in the BE is found
		// exactly as in t3lib_SCbase::init()
		$rootline = $sys_page->getRootLine($pageId);
		
		// This generates the constants/config + hierarchy info for the template.
		$tsetup->runThroughTemplates($rootline, 0);
		$tsetup->generateConfig();
		
		$this->conf =& $tsetup->setup['plugin.'][$this->prefixId.'.'];
		BUGBUGBUGBUGBUG */
		
		$this->conf =$GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId.'.'];		

		
		// Retrieve html template code
		$template_ = $this->conf['file.']['template'];
		// Retrieve css template code		
		$css_ = $this->conf['file.']['css'];
		
		// Check for correct template to load
		if ($template_== 'EXT:fe_rtenews/pi1/default.tmpl'){$template_ = null;}

		// Assign Template
		if($ctrl['template']){
			$file = '../../../../uploads/tx_auxnewsmailer/'.$ctrl['template'];
			$templateCode = t3lib_div::getURL($file);			
		}
		else if ($template_){
			$file = '../../../../'.$template_;
			$templateCode = t3lib_div::getURL($file);		
		}
	  	else{
			$file='../../aux_newsmailer_split_cat/res/template.tmpl';	
			$templateCode = t3lib_div::getURL($file);
		}	
						  		
		// Assign Css
		if($ctrl['stylesheet']){
			$stylesheet = '../../../../uploads/tx_auxnewsmailer/'.$ctrl['stylesheet'];			
		}
		else if ($css_){
			#$stylesheet = '../../../../'.$css_;	
			$stylesheet = 'http://'.$ctrl['orgdomain'].'/'.$css_;
		}
	  	else{
			$stylesheet = '../../aux_newsmailer_split_cat/res/mail.css';	
		}	

		if ($type=='html')
			$templateMarker = '###HTMLMAIL###';
		else
			$templateMarker = '###PLAINMAIL###';
		$template = $this->cObj->getSubpart($templateCode, $templateMarker);

		$html='';
		$marker=array();
		$wrapped=array();
		$marker['###NEWSLETTERNUMBER###'] = $LANG->getLL("number_tag").$message_number;
		$marker['###DATE###']=strftime('%Y-%m-%d %H:%M', time());
		$marker['###CSS###']=$stylesheet;
		$marker['###IMAGE###']=$this->getImage($ctrl['image'],$ctrl['imagew'],$ctrl['imageh']);
		$marker['###TITLE###']=$ctrl['subject'];


		if ($type=='html')
			$marker['###NEWSHEADER###']=nl2br($ctrl['pretext']);
		else
			$marker['###NEWSHEADER###']=$ctrl['pretext'];	
		if ($type=='html')
			$marker['###NEWSFOOTER###']=nl2br($ctrl['posttext']);
		else
			$marker['###NEWSFOOTER###']=$ctrl['posttext'];
		$marker['###PROFILEMESSAGE###']=$LANG->getLL('signoff');
		if ($type=='html')
			$marker['###PROFILELINK###'] ='<a style="'.$ctrl['tx_auxnewsmailersplitcat_tmpllinkstyle'].'" href="http://'.$ctrl['orgdomain'].'/index.php?id='.$ctrl['userpage'].'" title="'.$LANG->getLL('editprofile').'">'.$LANG->getLL('editprofile').'</a>';
		else
			$marker['###PROFILELINK###'] ='http://'.$ctrl['orgdomain'].'/index.php?id='.$ctrl['userpage'];
		$marker['###NEWSLIST###']=$news;
		$html.=$this->cObj->substituteMarkerArray($template,$marker);

	  	return $html;
	}



}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/aux_newsmailer_split_cat/mailer/class.ux_auxnewsmailer_corecron.php'])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/aux_newsmailer_split_cat/mailer/class.ux_auxnewsmailer_corecron.php']);
	}
?>