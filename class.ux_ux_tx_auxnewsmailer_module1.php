<?php
/** 
* User-Extension of ux_tx_auxnewsmailer_core class.
*
* @author����Philip Almeida <philip.almeida@gmail.com>
*/
$LANG->includeLLFile('EXT:aux_newsmailer_split_cat/locallang_db.xml');
class ux_ux_tx_auxnewsmailer_core extends ux_tx_auxnewsmailer_core {	
	var $prefixId = "tx_auxnewsmailer_pi1";
	var	$limit=10;	
	var	$limit_create=10;	

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
		
		// Prevent Error on preview if no news to scan
	    if(!$newslist){
			if ($preview=='html')
				return $LANG->getLL("nonews_tag");
			if ($preview=='plain')
				return $LANG->getLL("nonews_tag");					
		}

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
/** 
 * Add "tx_auxnewsmailersplitcat_tmplcat" check to see if category template is activated. 
 *
 *  
 */
          
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
				/** 
				 * Retrieve distinct categories and format headers. 
				 *
				 */   		

				$pageId   = $this->id;
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
				/*
				if($newslist){
					foreach ($a as $k => $v) {
					    	$marker['###HTMLMAIL_DISPLAY_CAT_'.$k.'###']=	$v;
					}
				}
				*/
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

	/**
	 * Formats a single news items for html
	 *
	 * @param	array		$ctrl: Newsletter control array.
	 * @param	array		$news: row from tt_news table.
	 * @param	string		$templateCode: Template File.	 
	 * @return	string		newsitem html formmated.
	 */
	function formatHTML_cat($ctrl,$news,$templateCode){
		global $LANG, $LOCAL_LANG;

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
					include(t3lib_extMgm::extPath('mbl_newsevent').'locallang.php');
					$marker['###EVENT_TO_TEXT###'] 	= $LANG->getLL('to');	
					$marker['###EVENT_TO_DATE###'] 	= strftime($ctrl['dateformat'],$news['tx_mblnewsevent_to']);
					$marker['###EVENT_TO_TIME###'] 	= strftime($ctrl['timeformat'],$news['tx_mblnewsevent_to']);
				}else{
					$marker['###EVENT_TO_TEXT###'] 	= '';	
					$marker['###EVENT_TO_DATE###'] 		= '';
					$marker['###EVENT_TO_TIME###'] 		= '';
				}				
			}												
		}	
		if (t3lib_div::inlist($showitems,'7')){
		/*
			$filepath 	= PATH_site.'uploads/pics/'.$news['cat_image'];
			$thumbspath = 'http://'.$ctrl['orgdomain'].'/'.TYPO3_mainDir.'thumbs.php';
			$image = t3lib_BEfunc::getThumbNail(
											   	$thumbspath,
											    $filepath,
											   	'hspace="0" vspace="0" border="0" alt="'.$news['cat_title'].'" title="'.$news['cat_title'].'"',
												30 
												); 
												
			*/
			
			$image = '<img src="http://'.$ctrl['orgdomain'].'/uploads/pics/'.$news['cat_image'].'" border="0" alt="'.$news['cat_title'].'"/>';					
			$marker['###CATEGORY_IMAGE###'] = '<a style="'.$ctrl['tx_auxnewsmailersplitcat_tmpllinkstyle'].'" href="http://'.$ctrl['orgdomain'].'/index.php?id='.$news_page_link.'" title="'.$news['cat_title'].'">'.$image.'</a>';
			#'<img src="http://'.$ctrl['orgdomain'].'/uploads/pics/'.$news['cat_image'].'" alt="'.$news['cat_title'].'" />';			
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
	        	'state=2 AND idctrl='.$ctrl['uid'],
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

		$pageId   = $this->id;
		$tsetup = t3lib_div::makeInstance('t3lib_TStemplate');
		// do not log time-performance information
		$tsetup->tt_track = 0;
		$tsetup->init();
		
		#===> BUGBUGBUGBUGBUGBUG	
		/*
		// Get the root line
		$sys_page = t3lib_div::makeInstance('t3lib_page');

		// the selected page in the BE is found
		// exactly as in t3lib_SCbase::init()
		$rootline = $sys_page->getRootLine($pageId);
												echo 'sdfasdfasdfasdfasdfasdfasdfasdf';
		// This generates the constants/config + hierarchy info for the template.
		$tsetup->runThroughTemplates($rootline, 0);
		$tsetup->generateConfig();

		$this->conf =& $tsetup->setup['plugin.'][$this->prefixId.'.'];
		*/
		#===> BUGBUGBUGBUGBUGBUG
				
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


	/**
	 * Creates the preview message of unsend news items.
	 *
	 * @param	string		$type: plain message $type='plain'. html message $type='html'
	 * @return	string		the message.
	 */
	function renderPreview($type){
		global $LANG, $LOCAL_LANG;
		
		$html='';
		$ctrl=$this->loadControl();
		
		$LANG->init($ctrl['lang']);	
		$LANG->includeLLFile('EXT:aux_newsmailer_split_cat/locallang_db.xml');
		$LANG->includeLLFile('EXT:aux_newsmailer/mod1/locallang.xml');	
		$LANG->includeLLFile('EXT:mbl_newsevent/locallang.php');	
			
		
		if ($ctrl['uid']){		
		  	$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
	                'distinct uid, endtime, starttime, archivedate',
	                'tt_news',
	                'deleted=0 and hidden=0 and tt_news.pid in '.$ctrl['pages'].' and tx_auxnewsmailer_scanstate<2 and starttime<'.time(),
	                '',
	                'uid',
					''
	        );
	
	 
			$newslist='';
			if ($res){
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

					// check if end time is due
					if ($row['endtime']==0){
						$check_endtime=true;
					}else if ($row['endtime']!=0){
						if($row['endtime']<time()){
							$check_endtime=false;
						}else{
							$check_endtime=true;
						}
					}
					// check if archive date has begin		
					if ($row['archivedate']==0){
						$check_archivedate=true;
					}else if ($row['archivedate']!=0){
						if($row['archivedate']<time()){
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
					    	$newslist.=$row['uid'];	
						}
					
				}
				$html=$this->createMsg(0,$newslist,$ctrl,$type);
			}
			else
				$html.='no news are ready ';
	
			$marker=array();
			$marker['###name###']='John Doe';
			$marker['###orgname###']=$ctrl['name'];
			$marker['###org###']=$ctrl['organisation'];
			$marker['###domain###']=$ctrl['orgdomain'];
	
			$html=$this->cObj->substituteMarkerArray($html,$marker);
	
			//$ctrl['html']=$html;
			//$html=$this->createHTMLMSG($ctrl,array());
		} else
			$html.='no news mail control record located in this folder';
		return $html;
	}

	/**
	 * Creates the overview page
	 *
	 * @return	string		the finished page
	 */
	function renderOverview(){
	  	global $LANG;

		$ctrl=$this->loadControl();
	  	$content='';

	  	if ($ctrl['uid']!=0)
	  	{

		  	$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
	                'distinct uid, endtime, starttime, archivedate',
	                'tt_news',
	                'deleted=0 and hidden=0 and tt_news.pid in '.$ctrl['pages'].' and tx_auxnewsmailer_scanstate<2 and starttime<'.time(),
	                '',
	                'uid',
					''
	        );
	
	 
			$newslist='';

			if ($res){
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
	
					// check if end time is due
					if ($row['endtime']==0){
						$check_endtime=true;
					}else if ($row['endtime']!=0){
						if($row['endtime']<time()){
							$check_endtime=false;
						}else{
							$check_endtime=true;
						}
					}
					// check if archive date has begin		
					if ($row['archivedate']==0){
						$check_archivedate=true;
					}else if ($row['archivedate']!=0){
						if($row['archivedate']<time()){
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
					    	$newslist.=$row['uid'];	
						}
					
				}
			}
			
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'uid,title,datetime,starttime',
                'tt_news',
                'deleted=0 and hidden=0 and tx_auxnewsmailer_scanstate<2 and tt_news.uid in ('.$newslist.')',
                '',
                'datetime',
				''
            );
			
			/*
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'uid,title,datetime,starttime',
                'tt_news',
                'deleted=0 and hidden=0 and tx_auxnewsmailer_scanstate<2 and tt_news.uid in '.$ctrl['pages'],
                '',
                'datetime',
				''
            );	
			*/			
					
							
		  	if ($res){
		  	  	$content.='</br><b>'.$LANG->getLL('unsendnews').'</b></br>';
		  	  	$content.='<br>'.$LANG->getLL('lastscan').strftime($ctrl['dateformat'].' '.$ctrl['timeformat'], $ctrl['lasttime']);
				$urlinvoke='index.php?id='.$this->id.'&cmd=scan&ctrl='.$ctrl['uid'];
				$content.='<br><a href="'.$urlinvoke.'">['.$LANG->getLL('createmsg').']</a></br>';

		  		$content.='<table border="1px">';
				$content.='<tr>';



				$content.='<td>'.$LANG->getLL('datetime').'</td>';
				$content.='<td>'.$LANG->getLL('starttime').'</td>';
				$content.='<td>'.$LANG->getLL('catcount').'</td>';
				$content.='<td>'.$LANG->getLL('newstitle').'</td>';
				
				$showcatmsg=false;
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				  	$catcount=$this->getCatCount($row['uid']);
					$content.='<tr>';
				  	$content.='<td>'.strftime($ctrl['dateformat'].' '.$ctrl['timeformat'], $row['datetime']).'</td>';
				  	$starttime='';
					
					if (($row['starttime'])&&($row['starttime']>time()))
						$starttime.=strftime($ctrl['dateformat'].' '.$ctrl['timeformat'], $row['starttime']);
					if (($ctrl['usecat'])&&($catcount==0)){
					  	$showcatmsg=true;
						if ($starttime) $starttime.='<br>';
						$starttime.='<font color="red">'.$LANG->getLL('nocat').'</font>';
					}
					if (!$starttime)
						$starttime=	$LANG->getLL('nextscan');
					
					$content.='<td>'.$starttime.'</td>';
					$content.='<td>'.$catcount.'</td>';
					$content.='<td>'.$row['title'].'</td>';
					$content.='</tr>';
					
				}
				$content.='</table>';
				if ($showcatmsg)
					$content.='<br>'.$LANG->getLL('catmsg').'<br>';
			}



			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                '*',
                'tx_auxnewsmailer_msglist',
                'state=0 and idctrl='.$ctrl['uid'],
                '',
                '',
				''
            );
		  	if ($res){

				$content.='<br><b>'.$LANG->getLL('pendingmsg').'</b>';
				$urlinvoke='index.php?id='.$this->id.'&cmd=invoke&msg=0';
				$content.='<br><a href="'.$urlinvoke.'">['.$LANG->getLL('invoke').']</a>';

		  		$content.='<table border="1px">';
				$content.='<tr>';


				$content.='<td>#</td>';
				//$content.='<td>'.$LANG->getLL('start').'</td>';
				//$content.='<td>'.$LANG->getLL('end').'</td>';
				$content.='<td>'.$LANG->getLL('unsend').'</td>';
				$content.='<td>'.$LANG->getLL('send').'</td>';

				$content.='<td>&nbsp;</td>';

				$content.='</tr>';

				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				  	$urlinvoke='index.php?id='.$this->id.'&cmd=markall&msg='.$row['uid'];
					$cnt=$this->getUsrMsgCount($row['uid']);
					if ($cnt['unsend']){
						$content.='<tr>';


						$content.='<td>'.$row[uid].'</td>';
						//$content.='<td>123456</td>';
						//$content.='<td>123456</td>';
						$content.='<td>'.$cnt['unsend'].'</td>';
						$content.='<td>'.$cnt['sendto'].'</td>';
						$content.='<td><a href="'.$urlinvoke.'">['.$LANG->getLL('markall').']</a></td>';

						$content.='</tr>';
					}
				}
				$content.='</table>';











			}
			else
				$content.='no news are ready ';
		}
		else
			$content.='no news mail control record located in this folder';
	  	return $content;
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

		$where='hidden=0';
		if ($idctrl){
			$where.=' and uid='.$idctrl;
		}

		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                '*',
                'tx_auxnewsmailer_control',
            	$where,
                '',
                '',
                ''
        );

		$updateArray=array(
			'tx_auxnewsmailer_scanstate'=>'1'
		);
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_news','tx_auxnewsmailer_scanstate=0', $updateArray);


		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
			// check send schedule
			if (!$this->checkDuration($row))
				return '0 (access denied by automatic mailing)';
				
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
			$sql.='fe_users.tx_auxnewsmailer_newsletter=1 and ';
			$sql.='fe_users.disable=0 and ';
			$sql.='fe_users.deleted=0 and ';			
			$sql.='tt_news.pid in '.$pid.' and ';
			$sql.='tt_news.hidden=0 and ';
			$sql.='tt_news.deleted=0 and ';
			$sql.='tt_news.starttime<'.time().' and ';		
			$sql.='tt_news.uid in ('.$newslist.') and ';	
			$sql.='tx_auxnewsmailer_scanstate=1';																	
			$sql.=' order by tt_news.uid';

			$res =$GLOBALS['TYPO3_DB']->sql_query($sql);
			$cnt=$GLOBALS['TYPO3_DB']->sql_affected_rows();
		 }
		$updateArray=array(
			'tx_auxnewsmailer_scanstate'=>'2'
		);
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_news','tx_auxnewsmailer_scanstate=1', $updateArray);

		// Prevent negative show
		if($cnt==-1){$cnt=0;};		
		return $cnt;
	}

	/**
	 * Sends the next 50 e-mails that are pending.
	 *
	 * @param	boolean		$inbatch: if true the function is called by the cron job
	 * @return	int		number of mails send.
	 */
	function sendMail($inbatch=false){
	
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
	 * creates the messages that should be send by mail.
	 *
	 * @param	[type]		$idctrl: uid of newsletter control
	 * @return	[type]		Number of messages created.
	 */
	function mailList($idctrl){

		$start_time = time();
		
		$where='hidden=0';
		
		if ($idctrl){
			$where.=' and uid='.intval($idctrl);
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
			$newslist='';
			$newslist_count = $GLOBALS['TYPO3_DB']->sql_affected_rows();			
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

				if ($row['iduser']!=$cid){

					if ($cid!=0){				
						$idmsg = $this->createMsg($cid,$newslist,$ctrl);						
						$cnt++;

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
					$new_total_time = (time()-$start_time) + $rowstat['create_total_time_seconds'];	
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
		}			
		return $cnt;
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
	
}

?>