<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "linkhandler".
 *
 * Auto generated 20-04-2009 11:43
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'AOE link handler',
	'description' => 'Enables user friendly links to records like tt_news etc... Configure new Tabs to the link-wizard. (by AOE GmbH)',
	'category' => 'plugin',
	'author' => 'Daniel Poetzinger, Michael Klapper',
	'author_email' => 'dev@aoe.com',
	'author_company' => 'AOE GmbH',
	'shy' => 0,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'version' => '2.0.dev',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '6.0.0-6.2.99',
		),
		'conflicts' => array(
			'ch_rterecords',
			'tinymce_rte',
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
);
