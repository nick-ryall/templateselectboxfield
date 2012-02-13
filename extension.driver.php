<?php

	Class extension_Templateselectboxfield extends Extension{

		
		/**
		 * @var template
		 */
		public $template;
		
		/**
		 * @var location
		 */
		public $location;
		
		public function about(){
			return array('name' => 'Field: Template Select Box',
						 'version' => '1.0',
						 'release-date' => '2012-02-06',
						 'author' => array('name' => 'Nick Ryall',
										   'website' => 'http://nickryall.com.au')
				 		);
		}

		public function uninstall(){
			Symphony::Database()->query("DROP TABLE `tbl_fields_templateselectbox`");
		}

		public function install(){
			return Symphony::Database()->query("CREATE TABLE `tbl_fields_templateselectbox` (
				`id` int(11) unsigned NOT NULL auto_increment,
				`field_id` int(11) unsigned NOT NULL,
				`destination` varchar(255) NOT NULL,
				PRIMARY KEY  (`id`),
				UNIQUE KEY `field_id` (`field_id`)
			) TYPE=MyISAM");

		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendOutputPreGenerate',
					'callback'	=> 'FrontendOutputPreGenerate'
				),		
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendPageResolved',
					'callback'	=> 'FrontendPageResolved'
				),
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendOutputPostGenerate',
					'callback'	=> 'FrontendOutputPostGenerate'
				),		
			);
		}
		
		
		public function FrontendPageResolved($context) {
			$this->location = $context['page_data']['filelocation'];
			$this->template = file_get_contents($this->location);
		}
		public function FrontendOutputPreGenerate($context) {
			$xml = new SimpleXMLElement($context['xml']);
			$new_template = $xml->xpath('//page-template//path');
			$new_template = $new_template[0];
			if($new_template == '') return;	
			$xsl = file_get_contents($new_template);
			
			//REPLACE THE PAGE XSLT
			file_put_contents($this->location, $xsl);
		}
		public function FrontendOutputPostGenerate() {
			//RESET THE PAGE XSLT
			file_put_contents($this->location, $this->template);
		}
		
/*-------------------------------------------------------------------------
	Utilities:
-------------------------------------------------------------------------*/		
	
		public function readTemplateName($template_file) {
			$template_contents = file_get_contents($template_file);
			preg_match_all("/Template:(.*)\n/siU",$template_contents,$template_name);
			$template_name = trim($template_name[1][0]);	
			return $template_name;
		}

	}
	
	
	